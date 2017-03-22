<?php
namespace Consolidation\AnnotatedCommand\Hooks;

use Consolidation\AnnotatedCommand\AnnotationData;
use Symfony\Component\Console\Input\InputInterface;

/**
 *
 */
interface ReplaceCommandHookInterface
{
    public function replace(array $arguments);
}
