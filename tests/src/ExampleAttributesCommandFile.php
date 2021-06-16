<?php
namespace Consolidation\TestUtils;

use Consolidation\AnnotatedCommand\CommandLineAttributes;

/**
 * Test file used in the Annotation Factory tests.  It is also
 * discovered in the testCommandDiscovery() test.
 *
 * The testCommandDiscovery test search base is the 'src' directory;
 * any command files located immediately inside the search base are
 * eligible for discovery, and will be included in the search results.
 */
class ExampleAttributesCommandFile
{
    protected $state;
    protected $output;

    public function __construct($state = '')
    {
        $this->state = $state;
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    #[CommandLineAttributes(
        name: 'my:echo',
        description: 'This is the my:echo command',
        help: "This command will concatenate two parameters. If the --flip flag\nis provided, then the result is the concatenation of two and one.",
        aliases: ['c'],
        usage: ['bet alpha --flip' => 'Concatenate "alpha" and "bet".'],
        options: [
            'flip' => [
                'description' => 'Whether or not the second parameter should come first in the result.'
            ]
        ]
    )]
    public function myEcho($one, $two = '', array $options = ['flip' => false])
    {
        if ($options['flip']) {
            return "{$two}{$one}";
        }
        return "{$one}{$two}";
    }

    #[CommandLineAttributes(
        name: 'test:arithmatic',
        description: 'This is the test:arithmatic command',
        help: "This command will add one and two. If the --negate flag\nis provided, then the result is negated.",
        aliases: ['arithmatic'],
        usage: ['2 2 --negate' => 'Add two plus two and then negate.'],
        options: [
            'negate' => ['description' => 'Whether or not the result should be negated.']
        ],
        params: [
            'one' => ['description' => 'The first number to add.'],
            'two' => ['description' => 'The other number to add.']
        ],
        custom: ['dup' => ['one', 'two']]
    )]
    public function testArithmatic($one, $two = 2, array $options = ['negate' => false, 'unused' => 'bob'])
    {
        $result = $one + $two;
        if ($options['negate']) {
            $result = -$result;
        }

        // Integer return codes are exit codes (errors), so
        // return a the result as a string so that it will be printed.
        return "$result";
    }
}
