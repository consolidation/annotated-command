<?php

namespace Consolidation\AnnotatedCommand\Attributes;

use Attribute;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

#[Attribute(Attribute::TARGET_METHOD)]
class DefaultTableFields implements AttributeInterface
{
    /**
     * @param $fields
     *   An array of field names to show by default when using table formatter.
     */
    public function __construct(
        public array $fields,
    ) {
    }

    public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo)
    {
        $args = $attribute->getArguments();
        $commandInfo->addAnnotation($args['default-table-fields'], $args['fields']);
    }
}
