<?php
namespace Consolidation\TestUtils;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Consolidation\AnnotatedCommand\CommandError;
use Consolidation\AnnotatedCommand\AnnotationData;

/**
 * Test file used in the Annotation Factory tests.  It is also
 * discovered in the testCommandDiscovery() test.
 *
 * The testCommandDiscovery test search base is the 'src' directory;
 * any command files located immediately inside the search base are
 * eligible for discovery, and will be included in the search results.
 */
class ExampleCommandFile
{
    protected $state;

    public function __construct($state = '')
    {
        $this->state = $state;
    }

    /**
     * This is the my:cat command
     *
     * This command will concatenate two parameters. If the --flip flag
     * is provided, then the result is the concatenation of two and one.
     *
     * @param string $one The first parameter.
     * @param string $two The other parameter.
     * @option boolean $flip Whether or not the second parameter should come first in the result.
     * @aliases c
     * @usage bet alpha --flip
     *   Concatenate "alpha" and "bet".
     */
    public function myCat($one, $two = '', $options = ['flip' => false])
    {
        if ($options['flip']) {
            return "{$two}{$one}";
        }
        return "{$one}{$two}";
    }

    public function myRepeat($one, $two = '', $options = ['repeat' => 1])
    {
        return str_repeat("{$one}{$two}", $options['repeat']);
    }

    /**
     * This is a command with no options
     *
     * This command will concatenate two parameters.
     *
     * @param $one The first parameter.
     * @param $two The other parameter.
     * @aliases nope
     * @usage alpha bet
     *   Concatenate "alpha" and "bet".
     */
    public function commandWithNoOptions($one, $two = 'default')
    {
        return "{$one}{$two}";
    }

    /**
     * This command has no arguments--only options
     *
     * Return a result only if not silent.
     *
     * @option $silent Supress output.
     */
    public function commandWithNoArguments($opts = ['silent|s' => false])
    {
        if (!$opts['silent']) {
            return "Hello, world";
        }
    }

    /**
     * Shortcut on annotation
     *
     * This command defines the option shortcut on the annotation instead of in the options array.
     *
     * @param $opts The options
     * @option $silent|s Supress output.
     */
    public function shortcutOnAnnotation($opts = ['silent' => false])
    {
        if (!$opts['silent']) {
            return "Hello, world";
        }
    }

    /**
     * This is the test:arithmatic command
     *
     * This command will add one and two. If the --negate flag
     * is provided, then the result is negated.
     *
     * @param integer $one The first number to add.
     * @param integer $two The other number to add.
     * @option $negate Whether or not the result should be negated.
     * @aliases arithmatic
     * @usage 2 2 --negate
     *   Add two plus two and then negate.
     */
    public function testArithmatic($one, $two, $options = ['negate' => false])
    {
        $result = $one + $two;
        if ($options['negate']) {
            $result = -$result;
        }

        // Integer return codes are exit codes (errors), so
        // return a the result as a string so that it will be printed.
        return "$result";
    }

    /**
     * This is the test:state command
     *
     * This command tests to see if the state of the Commandfile instance
     */
    public function testState()
    {
        return $this->state;
    }

    /**
     * This is the test:passthrough command
     *
     * This command takes a variable number of parameters as
     * an array and returns them as a csv.
     */
    public function testPassthrough(array $params)
    {
        return implode(',', $params);
    }

    /**
     * This command wraps its parameter in []; its alter hook
     * then wraps the result in <>.
     */
    public function testHook($parameter)
    {
        return "[$parameter]";
    }

    /**
     * Wrap the results of test:hook in <>.
     *
     * @hook alter test:hook
     */
    public function hookTestHook($result)
    {
        return "<$result>";
    }

    /**
     * This test is very similar to the preceding test, except
     * it uses an annotation hook instead of a named-function hook.
     *
     * @hookme
     * @before >
     * @after <
     */
    public function testAnnotationHook($parameter)
    {
        return "($parameter)";
    }

    /**
     * Wrap the results of test:hook in whatever the @before and @after
     * annotations contain.
     *
     * @hook alter @hookme
     */
    public function hookTestAnnotatedHook($result, $args, AnnotationData $annotationData)
    {
        $before = $annotationData->get('before', '-');
        $after = $annotationData->get('after', '-');
        return "$before$result$after";
    }

    public function testHello($who)
    {
        return "Hello, $who.";
    }

    public function testException($what)
    {
        throw new \Exception($what);
    }

    /**
     * @hook validate test:hello
     */
    public function validateTestHello($args)
    {
        if ($args['who'] == 'Donald Duck') {
            return new CommandError("I won't say hello to Donald Duck.");
        }
        if ($args['who'] == 'Drumph') {
            throw new \Exception('Irrational value error.');
        }
    }

    /**
     * Test default values in arguments
     *
     * @param string|null $one
     * @param string|null $two
     * @return string
     */
    public function defaults($one = null, $two = null)
    {
        if ($one && $two) {
            return "$one and $two";
        }
        if ($one) {
            return "only $one";
        }
        return "nothing provided";
    }
}
