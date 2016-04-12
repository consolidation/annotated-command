<?php
namespace Consolidation\AnnotatedCommand;

/**
 * Validate the arguments for the current command.
 */
interface ValidatorInterface
{
    public function validate($args);
}
