<?php
namespace Consolidation\AnnotatedCommand\Hooks;

use Consolidation\AnnotatedCommand\AnnotationData;
use Symfony\Component\Console\Input\InputInterface;

/**
 *
 *
 * @see HookManager::addCommandEventHook()
 */
interface CommandEventHookInterface
{
    // @todo replace with correct arguments. see http://symfony.com/doc/current/components/console/events.html
    public function initialize(ConsoleCommandEvent $event);
}
