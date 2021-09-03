<?php

namespace Consolidation\AnnotatedCommand\Attributes;

use Attribute;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

#[Attribute(Attribute::TARGET_METHOD)]
class Name implements AttributeInterface
{
    /**
     * @param $name
     *  The name of the command or hook.
     * @param string[] $aliases
     *   An array of alternative names for this item.
     * @param bool $is_hook,
     *   Is the item a hook (i.e. not a command).
     */
    public function __construct(
        public string $name,
        public array $aliases = [],
        public bool $is_hook = false
    ) {
    }

    public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo)
    {
        $args = $attribute->getArguments();
        $commandInfo->setName($args['name']);
        $annotation_name = isset($args['is_hook']) ? 'hook' : 'command';
        $commandInfo->addAnnotation($annotation_name, $args['name']);
        $commandInfo->setAliases(@$args['aliases']);
    }
}
