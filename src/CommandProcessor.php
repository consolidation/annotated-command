<?php
namespace Consolidation\AnnotatedCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

use Consolidation\OutputFormatters\FormatterManager;
use Consolidation\OutputFormatters\FormatterOptions;
use Consolidation\AnnotatedCommand\Hooks\HookManager;

/**
 * Process a command, including hooks and other callbacks.
 * There should only be one command processor per application.
 * Provide your command processor to the AnnotatedCommandFactory
 * via AnnotatedCommandFactory::setCommandProcessor().
 */
class CommandProcessor
{
    /** var HookManager */
    protected $hookManager;
    /** var FormatterManager */
    protected $formatterManager;

    public function __construct(HookManager $hookManager)
    {
        $this->hookManager = $hookManager;
    }

    /**
     * Return the hook manager
     * @return HookManager
     */
    public function hookManager()
    {
        return $this->hookManager;
    }

    public function setFormatterManager(FormatterManager $formatterManager)
    {
        $this->formatterManager = $formatterManager;
    }

    /**
     * Return the formatter manager
     * @return FormatterManager
     */
    public function formatterManager()
    {
        return $this->formatterManager;
    }

    public function process(
        OutputInterface $output,
        $names,
        $commandCallback,
        $annotationData,
        $args
    ) {
        $result = [];
        // Recover options from the end of the args
        $options = end($args);
        try {
            $result = $this->validateRunAndAlter(
                $names,
                $commandCallback,
                $args
            );
            return $this->handleResults($output, $names, $result, $annotationData, $options);
        } catch (\Exception $e) {
            $result = new CommandError($e->getCode(), $e->getMessage());
            return $this->handleResults($output, $names, $result, $annotationData, $options);
        }
    }

    public function validateRunAndAlter(
        $names,
        $commandCallback,
        $args
    ) {
        // Validators return any object to signal a validation error;
        // if the return an array, it replaces the arguments.
        $validated = $this->hookManager()->validateArguments($names, $args);
        if (is_object($validated)) {
            return $validated;
        }
        if (is_array($validated)) {
            $args = $validated;
        }

        // Run the command, alter the results, and then handle output and status
        $result = $this->runCommandCallback($commandCallback, $args);
        return $this->processResults($names, $result, $args);
    }

    public function processResults($names, $result, $args = [])
    {
        return $this->hookManager()->alterResult($names, $result, $args);
    }

    /**
     * Handle the result output and status code calculation.
     */
    public function handleResults(OutputInterface $output, $names, $result, $annotationData, $options = [])
    {
        $status = $this->hookManager()->determineStatusCode($names, $result);
        // If the result is an integer and no separate status code was provided, then use the result as the status and do no output.
        if (is_integer($result) && !isset($status)) {
            return $result;
        }
        $status = $this->interpretStatusCode($status);

        // Get the structured output, the output stream and the formatter
        $structuredOutput = $this->hookManager()->extractOutput($names, $result);
        $output = $this->chooseOutputStream($output, $status);
        if (($status == 0) && isset($this->formatterManager)) {
            $this->writeUsingFormatter($output, $structuredOutput, $annotationData, $options);
        } else {
            $this->writeCommandOutput($output, $structuredOutput);
        }
        return $status;
    }

    /**
     * Run the main command callback
     */
    protected function runCommandCallback($commandCallback, $args)
    {
        $result = false;
        try {
            $result = call_user_func_array($commandCallback, $args);
        } catch (\Exception $e) {
            $result = new CommandError($e->getMessage(), $e->getCode());
        }
        return $result;
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
     *
     * @return string
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
     * Call the formatter to output the provided data.
     */
    protected function writeUsingFormatter(OutputInterface $output, $structuredOutput, $annotationData, $options)
    {
        $format = $this->getFormat($options);
        $formatterOptions = new FormatterOptions($annotationData, $options);
        $this->formatterManager->write(
            $output,
            $format,
            $structuredOutput,
            $formatterOptions
        );
    }

    /**
     * If the result object is a string, then print it.
     */
    protected function writeCommandOutput(
        OutputInterface $output,
        $structuredOutput
    ) {
        // If there is no formatter, we will print strings,
        // but can do no more than that.
        if (is_string($structuredOutput)) {
            $output->writeln($structuredOutput);
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
