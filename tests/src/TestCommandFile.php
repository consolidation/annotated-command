<?php
namespace Consolidation\TestUtils;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Consolidation\AnnotationCommand\CommandError;

class TestCommandFile
{
    protected $state;

    public function __construct($state = '')
    {
        $this->state = $state;
    }

    /**
     * This is the my:cat command
     *
     * This command will concatinate two parameters. If the --flip flag
     * is provided, then the result is the concatination of two and one.
     *
     * @param integer $one The first parameter.
     * @param integer $two The other parameter.
     * @option $flip Whether or not the second parameter should come first in the result.
     * @aliases c
     * @usage bet alpha --flip
     *   Concatinate "alpha" and "bet".
     */
    public function myCat($one, $two = '', $options = ['flip' => false])
    {
        if ($options['flip']) {
            return "{$two}{$one}";
        }
        return "{$one}{$two}";
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

    public function testCommand(Command $command, $one)
    {
        $formatter = $command->getHelperSet()->get('formatter');
        return $formatter->formatSection('test', $one);
    }

    public function testIo(InputInterface $input, OutputInterface $output, $one)
    {
        if (!$input instanceof InputInterface) {
            throw new \RuntimeException('$input is not an InputInterface');
        }
        if (!$output instanceof OutputInterface) {
            throw new \RuntimeException('$output is not an OutputInterface');
        }
        return $one;
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

    public function testHello($who)
    {
        return "Hello, $who.";
    }

    /**
     * @hook validate test:hello
     */
    public function validateTestHello($args)
    {
        if ($args['who'] == 'Donald Duck') {
            return new CommandError("I won't say hello to Donald Duck.");
        }
    }
}
