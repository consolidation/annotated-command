<?php
namespace Consolidation\AnnotationCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnnotationCommandFactory
{
    protected $specialParameterClasses = [
        Command::class => ['getCommandReference'],
        InputInterface::class => ['getInputReference'],
        OutputInterface::class => ['getOutputReference'],
    ];

    protected $commandProcessor;

    public function __construct($specialParameterClasses = [])
    {
        $this->specialParameterClasses += $specialParameterClasses;
        $this->commandProcessor = new CommandProcessor(new HookManager());
    }

    public function setCommandProcessor($commandProcessor)
    {
        $this->commandProcessor = $commandProcessor;
    }

    public function commandProcessor()
    {
        return $this->commandProcessor;
    }

    public function addListener($listener)
    {
        $this->listeners[] = $listener;
    }

    protected function notify($commandFileInstance)
    {
        foreach ($this->listeners as $listener) {
            if ($listener instanceof CommandCreationListenerInterface) {
                $listener->notifyCommandFileAdded($commandFileInstance);
            }
            if (is_callable($listener)) {
                $listener($commandFileInstance);
            }
        }
    }

    public function createCommandsFromClass($commandFileInstance)
    {
        $this->notify($commandFileInstance);
        $commandInfoList = $this->getCommandInfoListFromClass($commandFileInstance);
        return $this->createCommandsFromClassInfo($commandInfoList, $commandFileInstance);
    }

    public function getCommandInfoListFromClass($classNameOrInstance)
    {
        $commandInfoList = [];

        // Ignore special functions, such as __construct and __call, and
        // accessor methods such as getFoo and setFoo, while allowing
        // set or setup.
        $commandMethodNames = array_filter(
            get_class_methods($classNameOrInstance) ?: [],
            function ($m) {
                return !preg_match('#^(_|get[A-Z]|set[A-Z])#', $m);
            }
        );

        foreach ($commandMethodNames as $commandMethodName) {
            $commandInfoList[] = new CommandInfo($classNameOrInstance, $commandMethodName);
        }

        return $commandInfoList;
    }

    public function createCommandInfo($classNameOrInstance, $commandMethodName)
    {
        return new CommandInfo($classNameOrInstance, $commandMethodName);
    }

    public function createCommandsFromClassInfo($commandInfoList, $commandFileInstance)
    {
        $commandList = [];

        foreach ($commandInfoList as $commandInfo) {
            if (!$commandInfo->hasAnnotation('hook')) {
                $command = $this->createCommand($commandInfo, $commandFileInstance);
                $commandList[] = $command;
            }
        }

        return $commandList;
    }

    public function registerCommandHooksFromClassInfo($commandInfoList, $commandFileInstance)
    {
        foreach ($commandInfoList as $commandInfo) {
            if ($commandInfo->hasAnnotation('hook')) {
                $this->registerCommandHook($commandInfo, $commandFileInstance);
            }
        }
    }

    /**
     * Register a command hook given the CommandInfo for a method.
     *
     * The hook format is:
     *
     *   @hook type name type
     *
     * For example, the pre-validate hook for the core-init command is:
     *
     *   @hook pre-validate core-init
     *
     * If no command name is provided, then we will presume
     * that the name of this method is the same as the name
     * of the command being hooked (in a different commandfile).
     *
     * If no hook is provided, then we will presume that ALTER_RESULT
     * is intended.
     *
     * @param CommandInfo $commandInfo Information about the command hook method.
     * @param object $commandFileInstance An instance of the Commandfile class.
     */
    public function registerCommandHook(CommandInfo $commandInfo, $commandFileInstance)
    {
        // Ignore if the command info has no @hook
        if (!$commandInfo->hasAnnotation('hook')) {
            return;
        }
        $hookData = $commandInfo->getAnnotation('hook');
        $hook = $this->getNthWord($hookData, 0, HookManager::ALTER_RESULT);
        $commandName = $this->getNthWord($hookData, 1, $commandInfo->getName());

        // Register the hook
        $callback = [$commandFileInstance, $commandInfo->getMethodName()];
        $this->commandProcessor()->hookManager()->add($commandName, $hook, $callback);
    }

    protected function getNthWord($string, $n, $default, $delimiter = ' ')
    {
        $words = explode($delimiter, $string);
        if (!empty($words[$n])) {
            return $words[$n];
        }
        return $default;
    }

    public function createCommand(CommandInfo $commandInfo, $commandFileInstance)
    {
        $commandCallback = [$commandFileInstance, $commandInfo->getMethodName()];
        $command = new AnnotationCommand($commandInfo->getName(), $commandCallback, $this->commandProcessor, $commandInfo->getAnnotations());
        $this->setCommandInfo($command, $commandInfo);
        $this->setCommandArguments($command, $commandInfo);
        $this->setCommandOptions($command, $commandInfo);
        // Annotation commands are never bootstrap-aware, but for completeness
        // we will notify on every created command, as some clients may wish to
        // use this notification for some other purpose.
        $this->notify($command);
        return $command;
    }

    protected function setCommandInfo($command, $commandInfo)
    {
        $command->setDescription($commandInfo->getDescription());
        $command->setHelp($commandInfo->getHelp());
        // TODO: Symfony Console commands by default put aliases and example
        // usages together in a list, one per line, in the "Usage" section.
        // There is no way to attach a description to a sample usage.  We
        // need to figure out how to replace the built-in help command with
        // our own version that has additional help sections (e.g. topics).
        $command->setAliases($commandInfo->getAliases());
        foreach ($commandInfo->getExampleUsages() as $usage => $description) {
            $command->addUsage($usage);
        }
    }

    protected function setCommandArguments($command, $commandInfo)
    {
        $args = $commandInfo->getArguments();
        $params = $commandInfo->getParameters();
        $this->setCommandSpecialParameterClasses($command, $args, $params);

        foreach ($args as $name => $defaultValue) {
            $description = $commandInfo->getArgumentDescription($name);
            $parameterMode = $this->getCommandArgumentMode($defaultValue);
            $command->addArgument($name, $parameterMode, $description, $defaultValue);
        }
    }

    protected function setCommandSpecialParameterClasses($command, &$args, $params)
    {
        $specialParams = [];
        while (!empty($params) && ($special = $this->calculateSpecialParameterClass(reset($params)))) {
            $specialParams += $special;
            array_shift($params);
            array_shift($args);
        }
        $command->setSpecialParameterClasses($specialParams);
    }

    protected function calculateSpecialParameterClass($param)
    {
        $typeHintClass = $param->getClass();
        if (!$typeHintClass) {
            return false;
        }
        foreach ($this->specialParameterClasses as $specialClass => $methodName) {
            if ($this->specialParameterClassMatches($typeHintClass, new \ReflectionClass($specialClass))) {
                return [$specialClass => $methodName];
            }
        }
        return false;
    }

    protected function specialParameterClassMatches(\ReflectionClass $typeHintClass, \ReflectionClass $specialClass)
    {
        if ($typeHintClass->name == $specialClass->name) {
            return true;
        }
        if ($specialClass->isInterface()) {
            return $typeHintClass->implementsInterface($specialClass);
        }
        return $typeHintClass->isSubclassOf($specialClass);
    }

    protected function getCommandArgumentMode($defaultValue)
    {
        if (!isset($defaultValue)) {
            return InputArgument::REQUIRED;
        }
        if (is_array($defaultValue)) {
            return InputArgument::IS_ARRAY;
        }
        return InputArgument::OPTIONAL;
    }

    protected function setCommandOptions($command, $commandInfo)
    {
        $opts = $commandInfo->getOptions();
        foreach ($opts as $name => $val) {
            $description = $commandInfo->getOptionDescription($name);

            $fullName = $name;
            $shortcut = '';
            if (strpos($name, '|')) {
                list($fullName, $shortcut) = explode('|', $name, 2);
            }

            if (is_bool($val)) {
                $command->addOption($fullName, $shortcut, InputOption::VALUE_NONE, $description);
            } else {
                $command->addOption($fullName, $shortcut, InputOption::VALUE_OPTIONAL, $description, $val);
            }
        }
    }
}
