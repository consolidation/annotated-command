<?php

namespace Consolidation\AnnotatedCommand\Attributes;

use Attribute;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Argument
{
    /**
     * @param $name
     *   The name of the argument.
     * @param $description
     *   A one line description.
     * @param $suggestedValues
     *   An array of suggestions or a Closure which gets them. See https://symfony.com/blog/new-in-symfony-6-1-improved-console-autocompletion#completion-values-in-input-definitions.
     */
    public function __construct(
        public string $name,
        public string $description,
        public array|\Closure $suggestedValues = []
    ) {
    }

    public static function handle(\ReflectionAttribute $attribute, CommandInfo $commandInfo)
    {
        $args = $attribute->getArguments();
        $commandInfo->addArgumentDescription($args['name'], @$args['description'], @$args['suggestedValues']);
    }
}
