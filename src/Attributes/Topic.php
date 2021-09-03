<?php

namespace Consolidation\AnnotatedCommand\Attributes;

use Attribute;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

#[Attribute(Attribute::TARGET_METHOD)]
class Topic implements AttributeInterface
{
    /**
     * @param string[] $topics
     *   An array of topics that are related to this command.
     * @param $is_topic
     *   This command should appear on the list of topics.
     */
    public function __construct(
        public ?array $topics,
        public ?bool $is_topic,
    ) {
    }

    public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo)
    {
        $args = $attribute->getArguments();
        $commandInfo->addAnnotation('topics', @$args['topics']);
        // @todo handle $is_topic
    }
}
