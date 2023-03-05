<?php

namespace Consolidation\AnnotatedCommand\Attributes;

use Attribute;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

#[Attribute(Attribute::TARGET_METHOD)]
class Topic
{
    /**
     * @param $path
     *  A path containing a file to show when this command is shown
     */
    public function __construct(
        public string|bool $path = true,
    ) {
    }

    public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo)
    {
        $instance = $attribute->newInstance();
        $commandInfo->addAnnotation('topic', $instance->path);
    }
}
