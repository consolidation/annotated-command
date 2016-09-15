<?php
namespace Consolidation\AnnotatedCommand\Hooks;

use Consolidation\AnnotatedCommand\AnnotationData;

/**
 * Validate the arguments for the current command.
 *
 * @see HookManager::addValidator()
 */
interface ValidatorInterface
{
    public function validate($args, AnnotationData $annotationData);
}
