<?php
namespace Consolidation\AnnotatedCommand;

use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Consolidation\OutputFormatters\FormatterManager;
use Consolidation\OutputFormatters\Options\FormatterOptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * AnnotatedCommands are created automatically by the
 * AnnotatedCommandFactory.  Each command method in a
 * command file will produce one AnnotatedCommand.  These
 * are then added to your Symfony Console Application object;
 * nothing else is needed.
 *
 * Optionally, though, you may extend AnnotatedCommand directly
 * to make a single command.  The usage pattern is the same
 * as for any other Symfony Console command, except that you may
 * omit the 'Confiure' method, and instead place your annotations
 * on the execute() method.
 *
 * @package Consolidation\AnnotatedCommand
 */
class AnnotatedCommand extends Command
{
    protected $commandCallback;
    protected $commandProcessor;
    protected $annotationData;
    protected $usesInputInterface;
    protected $usesOutputInterface;
    protected $returnType;

    public function __construct($name = null)
    {
        $commandInfo = false;

        // If this is a subclass of AnnotatedCommand, check to see
        // if the 'execute' method is annotated.  We could do this
        // unconditionally; it is a performance optimization to skip
        // checking the annotations if $this is an instance of
        // AnnotatedCommand.  Alternately, we break out a new subclass.
        // The command factory instantiates the subclass.
        if (get_class($this) != 'Consolidation\AnnotatedCommand\AnnotatedCommand') {
            $commandInfo = new CommandInfo($this, 'execute');
            if (!isset($name)) {
                $name = $commandInfo->getName();
            }
        }
        parent::__construct($name);
        if ($commandInfo && $commandInfo->hasAnnotation('command')) {
            $this->setCommandInfo($commandInfo);
            $this->setCommandOptions($commandInfo);
        }
    }

    public function setCommandCallback($commandCallback)
    {
        $this->commandCallback = $commandCallback;
    }

    public function setCommandProcessor($commandProcessor)
    {
        $this->commandProcessor = $commandProcessor;
    }

    public function commandProcessor()
    {
        // If someone is using an AnnotatedCommand, and is NOT getting
        // it from an AnnotatedCommandFactory OR not correctly injecting
        // a command processor via setCommandProcessor() (ideally via the
        // DI container), then we'll just give each annotated command its
        // own command processor. This is not ideal; preferably, there would
        // only be one instance of the command processor in the application.
        if (!isset($this->commandProcessor)) {
            $this->commandProcessor = new CommandProcessor(new HookManager());
        }
        return $this->commandProcessor;
    }

    public function getReturnType()
    {
        return $this->returnType;
    }

    public function setReturnType($returnType)
    {
        $this->returnType = $returnType;
    }

    public function getAnnotationData()
    {
        return $this->annotationData;
    }

    public function setAnnotationData($annotationData)
    {
        $this->annotationData = $annotationData;
    }

    public function setCommandInfo($commandInfo)
    {
        $this->setDescription($commandInfo->getDescription());
        $this->setHelp($commandInfo->getHelp());
        $this->setAliases($commandInfo->getAliases());
        $this->setAnnotationData($commandInfo->getAnnotationsForCommand());
        foreach ($commandInfo->getExampleUsages() as $usage => $description) {
            // Symfony Console does not support attaching a description to a usage
            $this->addUsage($usage);
        }
        $this->setCommandArguments($commandInfo);
        $this->setReturnType($commandInfo->getReturnType());
    }

    protected function setCommandArguments($commandInfo)
    {
        $this->setUsesInputInterface($commandInfo);
        $this->setUsesOutputInterface($commandInfo);
        $this->setCommandArgumentsFromParameters($commandInfo);
    }

    /**
     * Check whether the first parameter is an InputInterface.
     */
    protected function checkUsesInputInterface($params)
    {
        $firstParam = reset($params);
        return $firstParam instanceof InputInterface;
    }

    /**
     * Determine whether this command wants to get its inputs
     * via an InputInterface or via its command parameters
     */
    protected function setUsesInputInterface($commandInfo)
    {
        $params = $commandInfo->getParameters();
        $this->usesInputInterface = $this->checkUsesInputInterface($params);
    }

    /**
     * Determine whether this command wants to send its output directly
     * to the provided OutputInterface, or whether it will returned
     * structured output to be processed by the command processor.
     */
    protected function setUsesOutputInterface($commandInfo)
    {
        $params = $commandInfo->getParameters();
        $index = $this->checkUsesInputInterface($params) ? 1 : 0;
        $this->usesOutputInterface =
            (count($params) > $index) &&
            ($params[$index] instanceof OutputInterface);
    }

    protected function setCommandArgumentsFromParameters($commandInfo)
    {
        $args = $commandInfo->arguments()->getValues();
        foreach ($args as $name => $defaultValue) {
            $description = $commandInfo->arguments()->getDescription($name);
            $hasDefault = $commandInfo->arguments()->hasDefault($name);
            $parameterMode = $this->getCommandArgumentMode($hasDefault, $defaultValue);
            $this->addArgument($name, $parameterMode, $description, $defaultValue);
        }
    }

    protected function getCommandArgumentMode($hasDefault, $defaultValue)
    {
        if (!$hasDefault) {
            return InputArgument::REQUIRED;
        }
        if (is_array($defaultValue)) {
            return InputArgument::IS_ARRAY;
        }
        return InputArgument::OPTIONAL;
    }

    public function setCommandOptions($commandInfo, $automaticOptions = [])
    {
        $explicitOptions = $this->explicitOptions($commandInfo);

        $this->addOptions($explicitOptions + $automaticOptions, $automaticOptions);
    }

    protected function addOptions($inputOptions, $automaticOptions)
    {
        foreach ($inputOptions as $name => $inputOption) {
            $default = $inputOption->getDefault();
            $description = $inputOption->getDescription();

            if (empty($description) && isset($automaticOptions[$name])) {
                $description = $automaticOptions[$name]->getDescription();
            }

            // Recover the 'mode' value, because Symfony is stubborn
            $mode = 0;
            if ($inputOption->isValueRequired()) {
                $mode |= InputOption::VALUE_REQUIRED;
            }
            if ($inputOption->isValueOptional()) {
                $mode |= InputOption::VALUE_OPTIONAL;
            }
            if ($inputOption->isArray()) {
                $mode |= InputOption::VALUE_IS_ARRAY;
            }
            if (!$mode) {
                $mode = InputOption::VALUE_NONE;
                $default = null;
            }

            // Add the option; note that Symfony doesn't have a convenient
            // method to do this that takes an InputOption
            $this->addOption(
                $inputOption->getName(),
                $inputOption->getShortcut(),
                $mode,
                $description,
                $default
            );
        }
    }

    /**
     * Get the options that are explicitly defined, e.g. via
     * @option annotations, or via $options = ['someoption' => 'defaultvalue']
     * in the command method parameter list.
     *
     * @return InputOption[]
     */
    protected function explicitOptions($commandInfo)
    {
        $explicitOptions = [];

        $opts = $commandInfo->options()->getValues();
        foreach ($opts as $name => $defaultValue) {
            $description = $commandInfo->options()->getDescription($name);

            $fullName = $name;
            $shortcut = '';
            if (strpos($name, '|')) {
                list($fullName, $shortcut) = explode('|', $name, 2);
            }

            if (is_bool($defaultValue)) {
                $explicitOptions[$fullName] = new InputOption($fullName, $shortcut, InputOption::VALUE_NONE, $description);
            } elseif ($defaultValue === InputOption::VALUE_REQUIRED) {
                $explicitOptions[$fullName] = new InputOption($fullName, $shortcut, InputOption::VALUE_REQUIRED, $description);
            } else {
                $explicitOptions[$fullName] = new InputOption($fullName, $shortcut, InputOption::VALUE_OPTIONAL, $description, $defaultValue);
            }
        }

        return $explicitOptions;
    }

    protected function getArgsWithPassThrough($input)
    {
        $args = $input->getArguments();

        // When called via the Application, the first argument
        // will be the command name. The Application alters the
        // input definition to match, adding a 'command' argument
        // to the beginning.
        array_shift($args);
        if ($input instanceof PassThroughArgsInput) {
            return $this->appendPassThroughArgs($input, $args);
        }
        return $args;
    }

    protected function getArgsAndOptions($input)
    {
        if (!$input) {
            return [];
        }
        // Get passthrough args, and add the options on the end.
        $args = $this->getArgsWithPassThrough($input);
        $args[] = $input->getOptions();
        return $args;
    }

    protected function appendPassThroughArgs($input, $args)
    {
        $passThrough = $input->getPassThroughArgs();
        $definition = $this->getDefinition();
        $argumentDefinitions = $definition->getArguments();
        $lastParameter = end($argumentDefinitions);
        if ($lastParameter && $lastParameter->isArray()) {
            $args[$lastParameter->getName()] = array_merge($args[$lastParameter->getName()], $passThrough);
        } else {
            $args[$lastParameter->getName()] = implode(' ', $passThrough);
        }
        return $args;
    }

    /**
     * Returns all of the hook names that may be called for this command.
     *
     * @return array
     */
    protected function getNames()
    {
        return array_filter(
            array_merge(
                $this->getNamesUsingCommands(),
                [HookManager::getClassNameFromCallback($this->commandCallback)]
            )
        );
    }

    protected function getNamesUsingCommands()
    {
        return array_merge(
            [$this->getName()],
            $this->getAliases()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $this->commandProcessor()->interact(
            $input,
            $output,
            $this->getNames(),
            $this->annotationData
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get passthrough args, and add the options on the end.
        $args = $this->getArgsAndOptions($input);

        if ($this->usesInputInterface) {
            array_unshift($args, $input);
        }
        if ($this->usesOutputInterface) {
            array_unshift($args, $output);
        }

        // Validate, run, process, alter, handle results.
        return $this->commandProcessor()->process(
            $output,
            $this->getNames(),
            $this->commandCallback,
            $this->annotationData,
            $args
        );
    }

    public function processResults(InputInterface $input, OutputInterface $output, $results)
    {
        $commandProcessor = $this->commandProcessor();
        $names = $this->getNames();
        $args = $this->getArgsAndOptions($input);
        $results = $commandProcessor->processResults(
            $names,
            $results,
            $args,
            $this->annotationData
        );
        $options = end($args);
        return $commandProcessor->handleResults(
            $output,
            $names,
            $results,
            $this->annotationData,
            $options
        );
    }
}
