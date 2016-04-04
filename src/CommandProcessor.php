<?php
namespace Consolidation\AnnotationCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Consolidation\Formatters\FormatterManager;

/**
 * Process a command, including hooks and other callbacks
 */
class CommandProcessor
{
    protected $hookManager;
    protected $formatterManager;
    protected $globalHooks = [];

    const ARGUMENT_VALIDATOR = 'validate';
    const ALTER_RESULT = 'alter';
    const STATUS_DETERMINER = 'status';
    const EXTRACT_OUTPUT = 'extract';

    public function __construct($hookManager)
    {
        $this->hookManager = $hookManager;
    }

    public function hookManager()
    {
        return $this->hookManager;
    }

    public function setFormatterManager(FormatterManager $formatterManager)
    {
        $this->formatterManager = $formatterManager;
    }

    public function formatterManager()
    {
        return $this->formatterManager;
    }

    public function getFormatter($format)
    {
        if (!isset($this->formatterManager)) {
            return;
        }
        return $this->formatterManager->get($format);
    }

    public function setValidator($validator)
    {
        $this->globalHooks[self::ARGUMENT_VALIDATOR][] = $validator;
    }

    public function setStatusDeterminer($statusDeterminer)
    {
        $this->globalHooks[self::STATUS_DETERMINER][] = $statusDeterminer;
    }

    public function setAlterResultHook($resultProcessor)
    {
        $this->globalHooks[self::ALTER_RESULT][] = $resultProcessor;
    }

    public function getOutputExtractor($extractor)
    {
        $this->globalHooks[self::EXTRACT_OUTPUT][] = $extractor;
    }

    public function getValidators($name)
    {
        return $this->getHooks($name, self::ARGUMENT_VALIDATOR);
    }

    public function getStatusDeterminers($name)
    {
        return $this->getHooks($name, self::STATUS_DETERMINER);
    }

    public function getAlterResultHooks($name)
    {
        return $this->getHooks($name, self::ALTER_RESULT);
    }

    public function getOutputExtractors($name)
    {
        return $this->getHooks($name, self::EXTRACT_OUTPUT);
    }

    protected function getHooks($names, $hook)
    {
        $hooks = [];
        if (!is_array($names)) {
            $names = [$names];
        }
        $names[] = '*';
        foreach ($names as $name) {
            foreach (['pre-', '', 'post-'] as $stage) {
                $hooks = array_merge($hooks, $this->hookManager->get($name, "$stage$hook"));
            }
        }
        if (isset($this->globalHooks[$hook])) {
            $hooks = array_merge($hooks, $this->globalHooks[$hook]);
        }
        return $hooks;
    }

    public function process($name, $commandCallback, $specialParameters, $args, $output)
    {
        // Recover options from the end of the args
        $options = end($args);

        // Validate and change the command arguments as needed
        $validated = $this->validateArguments($name, $args);

        // Any non-array object returned signals a validation error.
        if (is_object($validated)) {
            // TODO: Perhaps this should not be formatted
            return $this->handleResult($name, $validated, $options, $output);
        }
        // If an array is returned, then the validation results replace
        // the arguments.
        if (is_array($validated)) {
            $args = $validated;
        }

        // Run command
        $result = $this->runCommandCallback($commandCallback, $specialParameters, $args);

        // Alter results
        $result = $this->alterResult($name, $result, $args);

        return $this->handleResult($name, $result, $options, $output);
    }

    protected function validateArguments($name, $args)
    {
        $validators = $this->getValidators($name);
        foreach ($validators as $validator) {
            $validated = null;
            if ($validator instanceof ValidatorInterface) {
                $validated = $validator->validate($args);
            }
            if (is_callable($validator)) {
                $validated = $validator($args);
            }
            if (is_object($validated)) {
                return $validated;
            }
            if (is_array($validated)) {
                $args = $validated;
            }
        }
        return $args;
    }

    protected function handleResult($name, $result, $options, $output)
    {
        // Determine status value
        // If the result (post-processing) is an object that
        // implements ExitCodeInterface, then we will ask it
        // to give us the status code. Otherwise, we assume success.
        $status = $this->determineStatusCode($name, $result);
        if (is_integer($result) && !isset($status)) {
            $status = $result;
            $result = null;
        }

        // TODO:  If status is non-zero, call rollback hooks
        // (unless we can just rely on Collection rollbacks)

        // Extract structured output from result
        $outputText = $this->extractOutput($name, $result);

        // Format structured output into printable text. Note that if
        // the status code is nonzero, then the result object is probably
        // an error object, and therefore should not be formatted per
        // the user's selected formatting options.
        if ($status == 0) {
            $outputText = $this->formatCommandResults($outputText, $options);
        }

        // Output the result text.
        if (isset($outputText)) {
            $this->writeCommandOutput($outputText, $output);
        }

        // Return appropriate status code
        return $this->interpretStatusCode($status);
    }

    /**
     * Run the main command callback
     */
    protected function runCommandCallback($commandCallback, $specialParameters, $args)
    {
        $result = false;
        try {
            $args = array_merge($specialParameters, $args);
            $result = call_user_func_array($commandCallback, $args);
        } catch (\Exception $e) {
            $result = new CommandError($e->getMessage(), $e->getCode());
        }
        return $result;
    }

    /**
     * Allow all of the post-process hooks to run
     */
    protected function alterResult($name, $result, $args)
    {
        // Process result and decide what to do with it.
        // Allow client to add transformation / interpretation
        // callbacks.
        $processors = $this->getAlterResultHooks($name);
        foreach ($processors as $processor) {
            if ($processor instanceof AlterResultInterface) {
                $result = $processor->alter($result, $args);
            }
            if (is_callable($processor)) {
                $result = $processor($result, $args);
            }
        }

        return $result;
    }

    /**
     * Call all status determiners, and see if any of them
     * know how to convert to a status code.
     */
    protected function determineStatusCode($name, $result)
    {
        // If the result (post-processing) is an object that
        // implements ExitCodeInterface, then we will ask it
        // to give us the status code.
        if ($result instanceof ExitCodeInterface) {
            return $result->getExitCode();
        }

        // If the result does not implement ExitCodeInterface,
        // then we'll see if there is a determiner that can
        // extract a status code from the result.
        $determiners = $this->getStatusDeterminers($name);
        foreach ($determiners as $determiner) {
            $status = null;
            if ($determiner instanceof StatusDeterminerInterface) {
                $status = $determiner->determineStatusCode($result);
            }
            if (is_callable($determiner)) {
                $status = $determiner($result);
            }
            if (isset($status)) {
                return $status;
            }
        }
    }

    /**
     * Convert the result object to printable output in
     * structured form.
     */
    protected function extractOutput($name, $result)
    {
        if ($result instanceof OutputDataInterface) {
            return $result->getOutputData();
        }

        $extractors = $this->getOutputExtractors($name);
        foreach ($extractors as $extractor) {
            $structuredOutput = null;
            if ($extractor instanceof ExtractOutputInterface) {
                $structuredOutput = $extractor->extractOutput($result);
            }
            if (is_callable($extractor)) {
                $structuredOutput = $extractor($result);
            }
            if (isset($structuredOutput)) {
                return $structuredOutput;
            }
        }

        return $result;
    }

    /**
     * Convert the structured output into a formatted
     * string for printing.
     */
    protected function formatCommandResults($outputText, $options)
    {
        $format = $this->getFormat($options);
        $formatter = $this->getFormatter($format);
        if (isset($formatter)) {
            $outputText = $formatter->format($outputText);
        }

        return $outputText;
    }

    /**
     * Determine the formatter that should be used to render
     * output.
     *
     * If the user specified a format via the --format option,
     * then always return that.  Otherwise, return the default
     * format, unless --pipe was specified, in which case
     * return the default pipe format, format-pipe.
     *
     * n.b. --pipe is a handy option introduced in Drush 2
     * (or perhaps even Drush 1) that indicates that the command
     * should select the output format that is most appropriate
     * for use in scripts (e.g. to pipe to another command).
     */
    protected function getFormat($options)
    {
        $options += [
            'default-format' => false,
            'pipe' => false,
        ];
        $options += [
            'format' => $options['default-format'],
            'format-pipe' => $options['default-format'],
        ];

        $format = $options['format'];
        if ($options['pipe']) {
            $format = $options['format-pipe'];
        }
        return $format;
    }

    /**
     * If the result object is a string, then print it.
     */
    protected function writeCommandOutput($outputText, OutputInterface $output)
    {
        // If $res is a string, then print it.
        if (is_string($outputText)) {
            $output->writeln($outputText);
        }
    }

    /**
     * If a status code was set, then return it; otherwise,
     * presume success.
     */
    protected function interpretStatusCode($status)
    {
        if (isset($status)) {
            return $status;
        }
        return 0;
    }
}
