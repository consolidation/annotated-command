<?php
namespace Consolidation\AnnotationCommand;

/**
 * Validate the arguments for the current command.
 */
interface CommandCreationListenerInterface
{
    public function notifyCommandFileAdded($command);
}
