<?php
namespace Consolidation\AnnotatedCommand\Hooks;

/**
 * Interactively (or perhaps non-interactively) supply values for
 * missing required arguments for the current command.
 *
 * @see HookManager::addInteractor()
 */
interface InteractorInterface
{
    public function interact($input, $output, $annotationData);
}
