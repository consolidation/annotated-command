<?php

namespace Consolidation\AnnotatedCommand\Hooks\Dispatchers;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\AnnotatedCommand\Hooks\ProcessResultInterface;

/**
 * Call hooks
 */
class ProcessResultHookDispatcher extends HookDispatcher implements ProcessResultInterface
{
    /**
     * Process result and decide what to do with it.
     * Allow client to add transformation / interpretation
     * callbacks.
     */
    public function process($result, CommandData $commandData)
    {
        $processors = $this->getProcessResultHooks($commandData->annotationData());
        foreach ($processors as $processor) {
            $result = $this->callProcessor($processor, $result, $commandData);
        }
        $alterers = $this->getAlterResultHooks($commandData->annotationData());
        foreach ($alterers as $alterer) {
            $result = $this->callProcessor($alterer, $result, $commandData);
        }

        return $result;
    }

    protected function callProcessor($processor, $result, CommandData $commandData)
    {
        $processed = null;
        if ($processor instanceof ProcessResultInterface) {
            $processed = $processor->process($result, $commandData);
        }
        if (is_callable($processor)) {
            $processed = $processor($result, $commandData);
        }
        if (isset($processed)) {
            return $processed;
        }
        return $result;
    }

    protected function getProcessResultHooks(AnnotationData $annotationData)
    {
        return $this->getHooks(
            [
                HookManager::PRE_PROCESS_RESULT,
                HookManager::PROCESS_RESULT,
                HookManager::POST_PROCESS_RESULT
            ],
            $annotationData
        );
    }

    protected function getAlterResultHooks(AnnotationData $annotationData)
    {
        return $this->getHooks(
            [
                HookManager::PRE_ALTER_RESULT,
                HookManager::ALTER_RESULT,
                HookManager::POST_ALTER_RESULT,
                HookManager::POST_COMMAND_HOOK,
            ],
            $annotationData
        );
    }
}
