<?php
namespace Consolidation\AnnotatedCommand\Parser\Internal;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;

/**
 * Create an appropriate CommandDocBlockParser.
 */
class CommandDocBlockParserFactory
{
    public static function parse(CommandInfo $commandInfo, \ReflectionMethod $reflection)
    {
        return static::create($commandInfo, $reflection)->parse();
    }

    private static function create(CommandInfo $commandInfo, \ReflectionMethod $reflection)
    {
        $attributes = $reflection->getAttributes();
        if (empty($attributes)) {
            return new BespokeDocBlockParser($commandInfo, $reflection);
        } else {
            return new AttributesDocBlockParser($commandInfo, $reflection);
        }
    }
}
