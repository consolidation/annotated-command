<?php
namespace Consolidation\AnnotatedCommand;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class CommandLineAttributes
{
    /**
     * CommandLineAttributes constructor.
     *
     * @param string $command
     *   The command's name.
     * @param string $hook
     *   The command's name.
     * @param array $custom
     *   Custom name/value pairs that may be used by command(s).
     * @param string $name
     *   The name of the command. Usually use 'command' or 'hook' instead of 'name'.
     * @param string $description
     *   One sentence describing the command or hook
     * @param string $help
     *   A multi-sentence help text about the item.
     * @param array $aliases
     *   A simple array of topic names.
     * @param array $usages
     *   An array of use examples and descriptions.
     * @param array $options
     *   An array of name -> description pairs.
     * @param array $params
     *   An array of name -> description pairs.
     * @param string $topic
     *   Indicate that a command is a help topic.
     * @param array $topics
     *   A simple list of applicable help topics.
     */
    public function __construct(
        // Keep these params in alphabetic order for easier scanning in IDE.
        public array $aliases = [],
        public string $command = '',
        public array $custom,
        public string $description  = '',
        public string $help = '',
        public string $hook  = '',
        public string $name  = '',
        public array $options = [],
        public array $params = [],
        public string $topic = '',
        public array $topics = [],
        public array $usages  = [],
    ) {}
}
