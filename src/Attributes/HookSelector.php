<?php

namespace Consolidation\AnnotatedCommand\Attributes;

use Attribute;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

#[Attribute(Attribute::TARGET_METHOD)]
class HookSelector
{
    /**
     * @param $name
     *  The name of the hook selector that must be present for that hook to run.
     * @param $value
     *   An value which can be used by the hook.
     */
    public function __construct(
        public string $name,
        public ?string $value,
    ) {
    }

    public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo)
    {
        $instance = $attribute->newInstance();
        $commandInfo->addAnnotation($instance->name, $instance->value);
    }
}
