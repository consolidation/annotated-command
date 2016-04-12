<?php
namespace Consolidation\AnnotatedCommand;

/**
 * Validate the arguments for the current command.
 */
interface CommandCreationListenerInterface
{
    public function notifyCommandFileAdded($command);
}
