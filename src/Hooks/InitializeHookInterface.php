<?php
namespace Consolidation\AnnotatedCommand\Hooks;

/**
 * Non-interactively (e.g. via configuration files) apply configuration values to the Input object.
 *
 * @see HookManager::addInitializeHook()
 */
interface InitializeHookInterface
{
    public function injectConfiguration($input, $annotationData);
}
