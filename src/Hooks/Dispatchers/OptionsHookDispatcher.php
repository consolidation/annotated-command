<?php

namespace Consolidation\AnnotatedCommand\Hooks\Dispatchers;

use Symfony\Component\Console\Command\Command;
use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\AnnotatedCommand\Hooks\OptionHookInterface;

/**
 * Call hooks
 */
class OptionsHookDispatcher extends HookDispatcher implements OptionHookInterface
{
    public function getOptions(
        Command $command,
        AnnotationData $annotationData
    ) {
        $optionHooks = $this->getOptionHooks($annotationData);
        foreach ($optionHooks as $optionHook) {
            $this->callOptionHook($optionHook, $command, $annotationData);
        }
        $commandInfoList = $this->hookManager->getHookOptionsForCommand($command);
        $command->optionsHookForHookAnnotations($commandInfoList);
    }

    protected function callOptionHook($optionHook, $command, AnnotationData $annotationData)
    {
        if ($optionHook instanceof OptionHookInterface) {
            return $optionHook->getOptions($command, $annotationData);
        }
        if (is_callable($optionHook)) {
            return $optionHook($command, $annotationData);
        }
    }

    protected function getOptionHooks(AnnotationData $annotationData)
    {
        return $this->getHooks(
            [
                HookManager::PRE_OPTION_HOOK,
                HookManager::OPTION_HOOK,
                HookManager::POST_OPTION_HOOK
            ],
            $annotationData
        );
    }
}
