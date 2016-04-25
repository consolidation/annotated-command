<?php
namespace Consolidation\AnnotatedCommand\Hooks;

/**
 * Validate the arguments for the current command.
 *
 * @see HookManager::addValidator()
 */
interface ValidatorInterface
{
    public function validate($args);
}
