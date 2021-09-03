<?php

namespace Consolidation\AnnotatedCommand\Attributes;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;

interface AttributeInterface
{
    public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo);
}
