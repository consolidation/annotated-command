<?php
namespace Consolidation\AnnotatedCommand;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;

/**
 * Command cration listeners can be added to the annotation
 * command factory.  These will be notified whenever a new
 * commandfile is provided to the factory.  This is useful for
 * initializing new commandfile objects.
 *
 * @see AnnotatedCommandFactory::addListener()
 */
interface AutomaticOptionsProviderInterface
{
    public function automaticOptions(CommandInfo $commandInfo);
}
