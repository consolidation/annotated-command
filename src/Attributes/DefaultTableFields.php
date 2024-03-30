<?php

namespace Consolidation\AnnotatedCommand\Attributes;

use Attribute;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Consolidation\OutputFormatters\Options\FormatterOptions;

#[Attribute(Attribute::TARGET_METHOD)]
class DefaultTableFields
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
        $commandInfo->addAnnotation(FormatterOptions::DEFAULT_TABLE_FIELDS, $args['fields']);
    }
}
