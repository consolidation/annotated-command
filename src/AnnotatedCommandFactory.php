<?php
namespace Consolidation\AnnotatedCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

/**
 * The AnnotatedCommandFactory creates commands for your application.
 * Use with a Dependency Injection Container and the CommandFactory.
 * Alternately, use the CommandFileDiscovery to find commandfiles, and
 * then use AnnotatedCommandFactory::createCommandsFromClass() to create
 * commands.  See the README for more information.
 *
 * @package Consolidation\AnnotatedCommand
 */
class AnnotatedCommandFactory
{
    protected $commandProcessor;
    protected $listeners = [];

    public function __construct()
    {
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

    public function hookManager()
    {
        return $this->commandProcessor()->hookManager();
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

    public function createCommandsFromClass($commandFileInstance, $includeAllPublicMethods = true)
    {
        $this->notify($commandFileInstance);
        $commandInfoList = $this->getCommandInfoListFromClass($commandFileInstance);
        $this->registerCommandHooksFromClassInfo($commandInfoList, $commandFileInstance);
        return $this->createCommandsFromClassInfo($commandInfoList, $commandFileInstance, $includeAllPublicMethods);
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

    public function createCommandsFromClassInfo($commandInfoList, $commandFileInstance, $includeAllPublicMethods = true)
    {
        $commandList = [];

        foreach ($commandInfoList as $commandInfo) {
            if ($this->isCommandMethod($commandInfo, $includeAllPublicMethods)) {
                $command = $this->createCommand($commandInfo, $commandFileInstance);
                $commandList[] = $command;
            }
        }

        return $commandList;
    }

    protected function isCommandMethod($commandInfo, $includeAllPublicMethods)
    {
        if ($commandInfo->hasAnnotation('hook')) {
            return false;
        }
        if ($commandInfo->hasAnnotation('command')) {
            return true;
        }
        return $includeAllPublicMethods;
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
     * of the command being hooked (in a different commandFile).
     *
     * If no hook is provided, then we will presume that ALTER_RESULT
     * is intended.
     *
     * @param CommandInfo $commandInfo Information about the command hook method.
     * @param object $commandFileInstance An instance of the CommandFile class.
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
        $this->commandProcessor()->hookManager()->add($callback, $hook, $commandName);
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
        $command = new AnnotatedCommand($commandInfo->getName());
        $commandCallback = [$commandFileInstance, $commandInfo->getMethodName()];
        $command->setCommandCallback($commandCallback);
        $command->setCommandProcessor($this->commandProcessor);
        $command->setCommandInfo($commandInfo);
        // Annotation commands are never bootstrap-aware, but for completeness
        // we will notify on every created command, as some clients may wish to
        // use this notification for some other purpose.
        $this->notify($command);
        return $command;
    }
}
