<?php
namespace Consolidation\AnnotatedCommand\Hooks;

/**
 * Interactively supply values for missing required arguments for
 * the current command.  Note that this hook is not called if
 * the --no-interaction flag is set.
 *
 * @see HookManager::addInteractor()
 */
interface InteractorInterface
{
    public function interact($input, $output, $annotationData);
}
