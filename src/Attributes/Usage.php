<?php

namespace Consolidation\AnnotatedCommand\Attributes;

use Attribute;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Usage implements AttributeInterface
{
    /**
     * @param $command
     *   The example command.
     * @param $description
     *   A one line description.
     */
    public function __construct(
        public string $command,
        public ?string $description
    ) {
    }

    public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo)
    {
        $args = $attribute->getArguments();
        $commandInfo->setExampleUsage($args['command'], @$args['description']);
    }
}
