<?php
namespace Consolidation\AnnotatedCommand;

use Consolidation\AnnotatedCommand\Hooks\Dispatchers\ReplaceCommandHookDispatcher;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

use Consolidation\OutputFormatters\FormatterManager;
use Consolidation\OutputFormatters\Options\FormatterOptions;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\AnnotatedCommand\Options\PrepareFormatter;

use Consolidation\AnnotatedCommand\Hooks\Dispatchers\InitializeHookDispatcher;
use Consolidation\AnnotatedCommand\Hooks\Dispatchers\OptionsHookDispatcher;
use Consolidation\AnnotatedCommand\Hooks\Dispatchers\InteractHookDispatcher;
use Consolidation\AnnotatedCommand\Hooks\Dispatchers\ValidateHookDispatcher;
use Consolidation\AnnotatedCommand\Hooks\Dispatchers\ProcessResultHookDispatcher;
use Consolidation\AnnotatedCommand\Hooks\Dispatchers\StatusDeterminerHookDispatcher;
use Consolidation\AnnotatedCommand\Hooks\Dispatchers\ExtracterHookDispatcher;

/**
 * Prepare parameter list for execurion. Handle injection of any
 * special values (e.g. $input and $output) into the parameter list.
 */
class ParameterInjection
{
    public function args($commandData)
    {
        return array_merge(
            $commandData->injectedInstances(),
            $commandData->getArgsAndOptions()
        );
    }

    public function injectIntoCommandData($commandData, $injectedClasses)
    {
        foreach ($injectedClasses as $injectedClass) {
            $injectedInstance = $this->getInstanceToInject($commandData, $injectedClass);
            $commandData->injectInstance($injectedInstance);
        }
    }

    protected function getInstanceToInject(CommandData $commandData, $injectedClass)
    {
        switch ($injectedClass) {
            case 'Symfony\Component\Console\Input\InputInterface':
                return $commandData->input();
            case 'Symfony\Component\Console\Output\OutputInterface':
                return $commandData->output();
        }

        return null;
    }
}
