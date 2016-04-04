<?php
namespace Consolidation\AnnotationCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

use Consolidation\Formatters\FormatterManager;

/**
 * Process a command, including hooks and other callbacks
 */
class CommandProcessor
{
    protected $hookManager;
    protected $formatterManager;

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

    public function getFormatter($format, $annotationData)
    {
        if (!isset($this->formatterManager)) {
            return;
        }
        return $this->formatterManager->get($format, $annotationData);
    }

    public function getValidators($names)
    {
        return $this->getHooks($names, self::ARGUMENT_VALIDATOR);
    }

    public function getStatusDeterminers($names)
    {
        return $this->getHooks($names, self::STATUS_DETERMINER);
    }

    public function getAlterResultHooks($names)
    {
        return $this->getHooks($names, self::ALTER_RESULT);
    }

    public function getOutputExtractors($names)
    {
        return $this->getHooks($names, self::EXTRACT_OUTPUT);
    }

    protected function getHooks($names, $hook)
    {
        $names = (array)$names;
        $names[] = '*';
        return array_merge(
            $this->hookManager->get($names, "pre-$hook"),
            $this->hookManager->get($names, $hook),
            $this->hookManager->get($names, "post-$hook")
        );
    }

    public function process(
        $names,
        $commandCallback,
        $annotationData,
        $specialParameters,
        $args,
        OutputInterface $output
    ) {
        // Recover options from the end of the args
        $options = end($args);

        // Validators return any object to signal a validation error;
        // if the return an array, it replaces the arguments.
        $validated = $this->validateArguments($names, $args);
        if (is_object($validated)) {
            return $this->handleResult($names, $validated, $annotationData, $options, $output);
        }
        if (is_array($validated)) {
            $args = $validated;
        }

        // Run the command, alter the results, and then handle output and status
        $result = $this->runCommandCallback($commandCallback, $specialParameters, $args);
        $result = $this->alterResult($names, $result, $args);
        return $this->handleResult($names, $result, $annotationData, $options, $output);
    }

    protected function validateArguments($names, $args)
    {
        $validators = $this->getValidators($names);
        foreach ($validators as $validator) {
            $validated = $this->callValidator($validator, $args);
            if (is_object($validated)) {
                return $validated;
            }
            if (is_array($validated)) {
                $args = $validated;
            }
        }
        return $args;
    }

    protected function callValidator($validator, $args)
    {
        if ($validator instanceof ValidatorInterface) {
            return $validator->validate($args);
        }
        if (is_callable($validator)) {
            return $validator($args);
        }
    }

    /**
     * Handle the result output and status code calculation.
     */
    protected function handleResult($names, $result, $annotationData, $options, OutputInterface $output)
    {
        $status = $this->determineStatusCode($names, $result);
        if (is_integer($result) && !isset($status)) {
            $status = $result;
            $result = null;
        }
        $status = $this->interpretStatusCode($status);

        // Get the structured output, the output stream and the formatter
        $outputText = $this->extractOutput($names, $result);
        $output = $this->chooseOutputStream($output, $status);
        $formatter = $this->chooseFormatter($annotationData, $options, $status);

        // Output the result text and return status code.
        $this->writeCommandOutput($outputText, $formatter, $options, $output);
        return $status;
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
    protected function alterResult($names, $result, $args)
    {
        // Process result and decide what to do with it.
        // Allow client to add transformation / interpretation
        // callbacks.
        $alterers = $this->getAlterResultHooks($names);
        foreach ($alterers as $alterer) {
            $result = $this->callAlterer($alterer, $result, $args);
        }

        return $result;
    }

    protected function callAlterer($alterer, $result, $args)
    {
        if ($alterer instanceof AlterResultInterface) {
            return $alterer->alter($result, $args);
        }
        if (is_callable($alterer)) {
            return $alterer($result, $args);
        }
        return $result;
    }

    /**
     * Call all status determiners, and see if any of them
     * know how to convert to a status code.
     */
    protected function determineStatusCode($names, $result)
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
        $determiners = $this->getStatusDeterminers($names);
        foreach ($determiners as $determiner) {
            $status = $this->callDeterminer($determiner, $result);
            if (isset($status)) {
                return $status;
            }
        }
    }

    protected function callDeterminer($determiner, $result)
    {
        if ($determiner instanceof StatusDeterminerInterface) {
            return $determiner->determineStatusCode($result);
        }
        if (is_callable($determiner)) {
            return $determiner($result);
        }
    }

    /**
     * Convert the result object to printable output in
     * structured form.
     */
    protected function extractOutput($names, $result)
    {
        if ($result instanceof OutputDataInterface) {
            return $result->getOutputData();
        }

        $extractors = $this->getOutputExtractors($names);
        foreach ($extractors as $extractor) {
            $structuredOutput = $this->callExtractor($extractor, $result);
            if (isset($structuredOutput)) {
                return $structuredOutput;
            }
        }

        return $result;
    }

    protected function callExtractor($extractor, $result)
    {
        if ($extractor instanceof ExtractOutputInterface) {
            return $extractor->extractOutput($result);
        }
        if (is_callable($extractor)) {
            return $extractor($result);
        }
    }

    /**
     * Select the formatter to use.
     *
     * Note that if there is an error (status code is nonzero),
     * then the result object is going to be an error object. This
     * object may have a string that may be extracted and printed,
     * but it should never be formatted per the --format option.
     */
    protected function chooseFormatter($annotationData, $options, $status)
    {
        if ($status) {
            return;
        }
        $format = $this->getFormat($options);
        return $this->getFormatter($format, $annotationData);
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
     * Determine whether we should use stdout or stderr.
     */
    protected function chooseOutputStream(OutputInterface $output, $status)
    {
        // If the status code indicates an error, then print the
        // result to stderr rather than stdout
        if ($status && ($output instanceof ConsoleOutputInterface)) {
            return $output->getErrorOutput();
        }
        return $output;
    }

    /**
     * If the result object is a string, then print it.
     */
    protected function writeCommandOutput(
        $outputText,
        $formatter,
        $options,
        OutputInterface $output
    ) {
        // If there is a formatter, use it.
        if ($formatter) {
            $formatter->write($outputText, $options, $output);
            return;
        }
        // If there is no formatter, we will print strings,
        // but can do no more than that.
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
