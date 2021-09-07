<?php

namespace Consolidation\AnnotatedCommand\Attributes;

use Consolidation\AnnotatedCommand\Attributes\AttributeInterface;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

class Generic implements AttributeInterface
{
    protected const NAME = 'annotation-name';

    public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo)
    {
        $commandInfo->addAnnotation(static::NAME, null);
    }
}
