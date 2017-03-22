<?php

namespace Consolidation\AnnotatedCommand\Hooks\Dispatchers;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Call hooks.
 */
class ReplaceCommandHookDispatcher extends HookDispatcher
{

    /**
     * @param \Consolidation\AnnotatedCommand\CommandData $commandData
     *
     * @return int
     */
    public function hasReplaceCommandHook(CommandData $commandData) {
        return count($this->getReplaceCommandHooks($commandData));
    }

    /**
     * @param \Consolidation\AnnotatedCommand\CommandData $commandData
     *
     * @return \callable[]
     */
    public function getReplaceCommandHooks(CommandData $commandData) {
        $hooks = [
            HookManager::REPLACE_COMMAND_HOOK,
        ];
        $replaceCommandHooks = $this->getHooks($hooks);

        return $replaceCommandHooks;
    }

    /**
     * @param \Consolidation\AnnotatedCommand\CommandData $commandData
     *
     * @return callable
     */
    public function getReplacementCommand(CommandData $commandData) {
        $replaceCommandHooks = $this->getReplaceCommandHooks($commandData);

        // We only take the first hook implementation of "replace-command" as the replacement. Commands shouldn't have
        // more than one replacement.
        $replacementCommand = reset($replaceCommandHooks);

        return $replacementCommand;
    }
}
