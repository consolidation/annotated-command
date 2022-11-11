<?php
namespace Consolidation\TestUtils;

use Consolidation\AnnotatedCommand\Attributes as CLI;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;

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

    /**
     * This is the my:echo command
     *
     * This command will concatenate two parameters. If the --flip flag
     * is provided, then the result is the concatenation of two and one.
     */
    #[CLI\Command(name: 'my:echo', aliases: ['c'])]
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

    /**
     * This is the improved:echo command
     *
     * This command will concatenate two parameters. If the --flip flag
     * is provided, then the result is the concatenation of two and one.
     */
    #[CLI\Command(name: 'improved:echo', aliases: ['c'])]
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

    /**
     * This is the improved way to declare options.
     *
     * This command will echo its arguments and options
     */
    #[CLI\Command(name: 'improved:options', aliases: ['c'])]
    #[CLI\Argument(name: 'a1', description: 'an arg')]
    #[CLI\Argument(name: 'a2', description: 'another arg')]
    #[CLI\Option(name: 'o1', description: 'an option')]
    #[CLI\Option(name: 'o2', description: 'another option')]
    #[CLI\Usage(name: 'a b --o1=x --o2=y', description: 'Print some example values')]
    public function improvedOptions($a1, $a2, $o1 = 'one', $o2 = 'two')
    {
        return "args are $a1 and $a2, and options are " . var_export($o1, true) . ' and ' . var_export($o2, true);
    }

    /**
     * This is the test:arithmatic command
     *
     * This command will add one and two. If the --negate flag
     * is provided, then the result is negated.
     *
     * @param string $one The first parameter
     * @param string $two The second parameter
     * @param array $options The list of options
     *
     * Any text after the attributes is omitted from the help description.
     */
    #[CLI\Command(name: 'test:arithmatic', aliases: ['arithmatic'])]
    #[CLI\Argument(name: 'one', description: 'The first number to add.', suggestedValues: [1,2,3,4,5])]
    #[CLI\Argument(name: 'two', description: 'The other number to add.')]
    #[CLI\Option(name: 'negate', description: 'Whether or not the result should be negated.')]
    #[CLI\Usage(name: '2 2 --negate', description: 'Add two plus two and then negate.')]
    #[CLI\Complete(method_name_or_callable: 'testArithmaticComplete')]
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

    // Exercise table formatter options and Union return type.
    #[CLI\Command(name: 'birds')]
    #[CLI\FieldLabels(labels: ['name' => 'Name', 'color' => 'Color'])]
    #[CLI\DefaultFields(fields: ['color'])]
    public function birds(): RowsOfFields|int
    {
        $rows = [
            ['Bluebird' => 'blue'],
            ['Cardinal' => 'red'],
        ];
        return new RowsOfFields($rows);
    }

    /*
     * An argument completion callback.
     */
    public function testArithmaticComplete(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('two')) {
            $suggestions->suggestValues(range(10, 15));
        }
    }
}
