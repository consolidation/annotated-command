<?php
namespace Consolidation\AnnotatedCommand\Hooks;

/**
 * Add options to a command.
 *
 * @see HookManager::addOptionHook()
 * @see AnnotatedCommandFactory::addListener()
 */
interface OptionHookInterface
{
    public function getOptions($command, $annotationData);
}
