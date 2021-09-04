<?php

namespace Consolidation\AnnotatedCommand\Attributes;

use Attribute;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

#[Attribute(Attribute::TARGET_METHOD)]
class Hook implements AttributeInterface
{
    /**
     * @param $name
     *  The name of the command or hook.
     * @param $target
     *   Specifies which command(s) the hook will be attached to.
     */
    public function __construct(
        public string $name,
        public ?string $target
    ) {
    }

    public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo)
    {
        $args = $attribute->getArguments();
        $commandInfo->setName($args['name']);
        $commandInfo->addAnnotation('hook', $args['name'] . ' ' . $args['target']);
    }
}
