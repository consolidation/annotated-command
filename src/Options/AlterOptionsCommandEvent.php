<?php
namespace Consolidation\AnnotatedCommand\Options;

use Consolidation\AnnotatedCommand\AnnotatedCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * AlterOptionsCommandEvent is a subscriber to the Command Event
 * that looks up any additional options (e.g. from an OPTION_HOOK)
 * that should be added to the command.  Options need to be added
 * in two circumstances:
 *
 * 1. When 'help' for the command is called, so that the additional
 *    command options may be listed in the command description.
 *
 * 2. When the command itself is called, so that option validation
 *    may be done.
 *
 * We defer the addition of options until these times so that we
 * do not invoke the option hooks for every command on every run
 * of the program, and so that we do not need to defer the addition
 * of all of the application hooks until after all of the application
 * commands have been added. (Hooks may appear in the same command files
 * as command implementations; applications may support command file
 * plug-ins, and hooks may add options to commands defined in other
 * commandfiles.)
 */
class AlterOptionsCommandEvent implements EventSubscriberInterface
{
    /** var Application */
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function alterCommandOptions(ConsoleCommandEvent $event)
    {
        /* @var Command $command */
        $command = $event->getCommand();
        if ($command->getName() == 'help') {
            $nameOfCommandToDescribe = $event->getInput()->getArgument('command_name');
            $commandToDescribe = $this->application->find($nameOfCommandToDescribe);
            $this->findAndAddHookOptions($commandToDescribe);
        } else {
            $this->findAndAddHookOptions($command);
        }
    }

    public function findAndAddHookOptions($command)
    {
        if (!$command instanceof AnnotatedCommand) {
            return;
        }
        $command->optionsHook();
    }


    /**
     * @{@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [ConsoleEvents::COMMAND => 'alterCommandOptions'];
    }
}
