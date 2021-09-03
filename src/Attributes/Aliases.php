<?php

namespace Consolidation\AnnotatedCommand\Attributes;

use Attribute;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

#[Attribute(Attribute::TARGET_METHOD)]
class Aliases implements AttributeInterface
{
    /**
     * @param $aliases
     *   An array of alternative names for this item.
     */
    public function __construct(
        public array $aliases,
    ) {
    }

    public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo)
    {
        $args = $attribute->getArguments();
        $commandInfo->setAliases($args['aliases']);
    }
}
