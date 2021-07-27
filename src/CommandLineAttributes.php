<?php
namespace Consolidation\AnnotatedCommand;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class CommandLineAttributes
{
    /**
     * CommandLineAttributes constructor.
     *
     * @param $command
     *   The command's name.
     * @param $hook
     *   The command's name.
     * @param $custom
     *   Custom name/value pairs that may be used by command(s).
     * @param $name
     *   The name of the command. Usually use 'command' or 'hook' instead of 'name'.
     * @param $description
     *   One sentence describing the command or hook
     * @param $help
     *   A multi-sentence help text about the item.
     * @param $hidden
     *   Hide this method from help's command list.
     * @param $aliases
     *   A simple array of topic names.
     * @param $usages
     *   An array of use examples and descriptions.
     * @param $options
     *   An array of name -> description pairs.
     * @param $params
     *   An array of name -> description pairs.
     * @param $topic
     *   Indicate that a command is a help topic.
     * @param $topics
     *   A simple list of applicable help topics.
     */
    public function __construct(
        public ?array $aliases,
        public ?string $command,
        public ?array $custom,
        public ?string $description,
        public ?string $help,
        public ?string $hook,
        public ?bool $hidden,
        public ?string $name,
        public ?array $options,
        public ?array $params,
        public ?string $topic,
        public ?array $topics,
        public ?array $usages,
    ) {
    }
}
