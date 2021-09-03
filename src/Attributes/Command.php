<?php

namespace Consolidation\AnnotatedCommand\Attributes;

use Attribute;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

#[Attribute(Attribute::TARGET_METHOD)]
class Command implements AttributeInterface
{
    /**
     * @param $name
     *   The name of the command or hook.
     * @param $description
     *   A one line description.
     * @param $help
     *   A multi-line help text.
     * @param $hidden
     *   Hide this item from the command list.
     * @param $is_hook
     *   Is this item a hook or a command
     * @param $is_topic
     *   Should this command show up in the list of topics.
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public ?string $help,
        public ?bool $hidden = false,
        public ?bool $is_hook = false,
        public ?bool $is_topic = false
    ) {
    }

    public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo)
    {
        $args = $attribute->getArguments();
        $commandInfo->setName($args['name']);
        $annotation_name = isset($args['is_hook']) ? 'hook' : 'command';
        $commandInfo->addAnnotation($annotation_name, $args['name']);
        $commandInfo->setDescription(@$args['description']);
        $commandInfo->setHelp(@$args['help']);
        $commandInfo->setHidden(@$args['is_hidden']);
        $commandInfo->addAnnotation('topic', @$args['is_topic']);
    }
}
