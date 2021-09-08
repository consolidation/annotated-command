<?php
namespace Consolidation\TestUtils;

use Consolidation\AnnotatedCommand\Attributes as CLI;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

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

    #[CLI\Command(name: 'my:echo', aliases: ['c'])]
    #[CLI\Help(description: 'This is the my:echo command', synopsis: "This command will concatenate two parameters. If the --flip flag\nis provided, then the result is the concatenation of two and one.",)]
    #[CLI\Argument(name: 'one', description: 'The first parameter')]
    #[CLI\Argument(name: 'two', description: 'The other parameter')]
    #[CLI\Option(name: 'flip', description: 'Whether or not the second parameter should come first in the result.')]
    #[CLI\Usage(name: 'bet alpha --flip', description: 'Concatenate "alpha" and "bet".')]
    public function myEcho($one, $two = '', array $options = ['flip' => false])
    {
        if ($options['flip']) {
            return "{$two}{$one}";
        }
        return "{$one}{$two}";
    }

    #[CLI\Command(name: 'improved:echo', aliases: ['c'])]
    #[CLI\Help(description: 'This is the improved:echo command', synopsis: "This command will concatenate two parameters. If the --flip flag\nis provided, then the result is the concatenation of two and one.",)]
    #[CLI\Argument(name: 'args', description: 'Any number of arguments separated by spaces.')]
    #[CLI\Option(name: 'flip', description: 'Whether or not the second parameter should come first in the result.')]
    #[CLI\Usage(name: 'bet alpha --flip', description: 'Concatenate "alpha" and "bet".')]
    public function improvedEcho(array $args, $flip = false)
    {
        if ($flip) {
            $args = array_reverse($args);
        }
        return implode(' ', $args);
    }

    #[CLI\Command(name: 'test:arithmatic', aliases: ['arithmatic'])]
    #[CLI\Help(description: 'This is the test:arithmatic command', synopsis: "This command will add one and two. If the --negate flag\nis provided, then the result is negated.",)]
    #[CLI\Argument(name: 'one', description: 'The first number to add.')]
    #[CLI\Argument(name: 'two', description: 'The other number to add.')]
    #[CLI\Option(name: 'negate', description: 'Whether or not the result should be negated.')]
    #[CLI\Usage(name: '2 2 --negate', description: 'Add two plus two and then negate.')]
    #[CLI\Misc(data: ['dup' => ['one', 'two']])]
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

    // Declare a hook with a target.
    #[CLI\Hook(type: HookManager::POST_COMMAND_HOOK, target: 'test:arithmatic')]
    #[CLI\Help(description: 'Add a text after test:arithmatic command')]
    public function postArithmatic()
    {
        $this->output->writeln('HOOKED');
    }

    // Exercise table formatter options.
    #[CLI\Command(name: 'birds')]
    #[CLI\FieldLabels(labels: ['name' => 'Name', 'color' => 'Color'])]
    public function birds(): RowsOfFields|null
    {
        $rows = [
            ['Bluebird' => 'blue'],
            ['Cardinal' => 'red'],
        ];
        return new RowsOfFields($rows);
    }
}
