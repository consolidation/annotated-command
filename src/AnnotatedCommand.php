<?php
namespace Consolidation\AnnotatedCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

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

    public function getCommandProcessor()
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

    public function setAnnotationData($annotationData)
    {
        $this->annotationData = $annotationData;
    }

    public function setCommandInfo($commandInfo)
    {
        $this->setDescription($commandInfo->getDescription());
        $this->setHelp($commandInfo->getHelp());
        $this->setAliases($commandInfo->getAliases());
        $this->setAnnotationData($commandInfo->getAnnotations());
        foreach ($commandInfo->getExampleUsages() as $usage => $description) {
            // Symfony Console does not support attaching a description to a usage
            $this->addUsage($usage);
        }
        $this->setCommandArguments($commandInfo);
        $this->setCommandOptions($commandInfo);
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

    protected function setCommandOptions($commandInfo)
    {
        $opts = $commandInfo->options()->getValues();
        foreach ($opts as $name => $val) {
            $description = $commandInfo->options()->getDescription($name);

            $fullName = $name;
            $shortcut = '';
            if (strpos($name, '|')) {
                list($fullName, $shortcut) = explode('|', $name, 2);
            }

            if (is_bool($val)) {
                $this->addOption($fullName, $shortcut, InputOption::VALUE_NONE, $description);
            } else {
                $this->addOption($fullName, $shortcut, InputOption::VALUE_OPTIONAL, $description, $val);
            }
        }
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

    protected function getNames()
    {
        return array_merge(
            [$this->getName()],
            $this->getAliases()
        );
    }

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
        return $this->getCommandProcessor()->process(
            $output,
            $this->getNames(),
            $this->commandCallback,
            $this->annotationData,
            $args
        );
    }

    public function processResults(InputInterface $input, OutputInterface $output, $results)
    {
        $commandProcessor = $this->getCommandProcessor();
        $names = $this->getNames();
        $args = $this->getArgsAndOptions($input);
        $results = $commandProcessor->processResults(
            $names,
            $results,
            $args
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
