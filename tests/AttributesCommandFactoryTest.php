<?php
namespace Consolidation\AnnotatedCommand;

use Consolidation\AnnotatedCommand\Options\AlterOptionsCommandEvent;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandCompletionTester;

class AttributesCommandFactoryTest extends TestCase
{
    protected $commandFileInstance;
    protected $commandFactory;

    /**
     * @requires PHP >= 8.0
     */
    function testMyEchoCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleAttributesCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'myEcho');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('my:echo', $command->getName());
        $this->assertEquals('This is the my:echo command', $command->getDescription());
        $this->assertEquals("This command will concatenate two parameters. If the --flip flag\nis provided, then the result is the concatenation of two and one.", $command->getHelp());
        $this->assertEquals('c', implode(',', $command->getAliases()));
        $this->assertEquals('my:echo [--flip] [--] <one> [<two>]', $command->getSynopsis());
        $this->assertEquals('my:echo bet alpha --flip', implode(',', $command->getUsages()));

        $input = new StringInput('my:echo bet alpha --flip');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'alphabet');
    }

    /**
     * @requires PHP >= 8.0
     */
    function testImprovedEchoCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleAttributesCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'improvedEcho');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('improved:echo', $command->getName());
        $this->assertEquals('This is the improved:echo command', $command->getDescription());
        $this->assertEquals("This command will concatenate two parameters. If the --flip flag\nis provided, then the result is the concatenation of two and one.", $command->getHelp());
        $this->assertEquals('c', implode(',', $command->getAliases()));
        $this->assertEquals('improved:echo [--flip] [--] [<args>...]', $command->getSynopsis());
        $this->assertEquals('improved:echo bet alpha --flip', implode(',', $command->getUsages()));

        $input = new StringInput('improved:echo thing other the and that this --flip');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'this that and the other thing');
    }

    function testSnakeEchoCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleAttributesCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'snakeCaseEcho');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('snake:echo', $command->getName());
        $this->assertEquals('This is the snake_case version of the my:echo command', $command->getDescription());
        $this->assertEquals("This command will concatenate two parameters. If the --flip-flag\nis provided, then the result is the concatenation of two and one.", $command->getHelp());
        $this->assertEquals('c', implode(',', $command->getAliases()));
        $this->assertEquals('snake:echo [--flip-flag] [--] [<args>...]', $command->getSynopsis());
        $this->assertEquals('snake:echo bet alpha --flip-flag', implode(',', $command->getUsages()));

        $input = new StringInput('snake:echo bet alpha --flip-flag');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'alphabet');
    }

    function testCamelEchoCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleAttributesCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'camelCaseEcho');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('camel:echo', $command->getName());
        $this->assertEquals('This is the camelCase version of the my:echo command', $command->getDescription());
        $this->assertEquals("This command will concatenate two parameters. If the --flip-flag\nis provided, then the result is the concatenation of two and one.", $command->getHelp());
        $this->assertEquals('c', implode(',', $command->getAliases()));
        $this->assertEquals('camel:echo [--flip-flag] [--] [<args>...]', $command->getSynopsis());
        $this->assertEquals('camel:echo bet alpha --flip-flag', implode(',', $command->getUsages()));

        $input = new StringInput('camel:echo bet alpha --flip-flag');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'alphabet');
    }

    /**
     * @requires PHP >= 8.0
     */
    function testImprovedOptionsCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleAttributesCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'improvedOptions');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('improved:options', $command->getName());
        $this->assertEquals('This is the improved way to declare options.', $command->getDescription());
        $this->assertEquals("This command will echo its arguments and options", $command->getHelp());
        $this->assertEquals('c', implode(',', $command->getAliases()));
        $this->assertEquals('improved:options [--o1 [O1]] [--o2 [O2]] [--] <a1> <a2>', $command->getSynopsis());
        $this->assertEquals('improved:options a b --o1=x --o2=y', implode(',', $command->getUsages()));

        $input = new StringInput('improved:options a b');
        $this->assertRunCommandViaApplicationEquals($command, $input, "args are a and b, and options are 'one' and 'two'");

        $input = new StringInput('improved:options a b --o1=x --o2=y');
        $this->assertRunCommandViaApplicationEquals($command, $input, "args are a and b, and options are 'x' and 'y'");
    }

    /**
     * @requires PHP >= 8.0
     */
    function testBirdsCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleAttributesCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'birds');
        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);
        $this->assertEquals(RowsOfFields::class, $command->getReturnType());
    }

    /**
     * @requires PHP >= 8.0
     */
    function testArithmeticCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleAttributesCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testArithmatic');
        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);
        $this->assertIsCallable($command->getCompletionCallback());

        if (!class_exists('\Symfony\Component\Console\Completion\Output\FishCompletionOutput')) {
            $this->markTestSkipped('Symfony Console 6.1+ needed for rest of test.');
        }

        $tester = new CommandCompletionTester($command);
        // Complete the input without any existing input (the empty string represents
        // the position of the cursor)
        $suggestions = $tester->complete(['']);
        $this->assertSame(['1', '2', '3', '4', '5'], $suggestions);

        $suggestions = $tester->complete(['1', '2', '--color']);
        $this->assertSame(['red', 'blue', 'green'], $suggestions);

        // CommandCompletionTester from Symfony doesnt test dynamic values as
        // that is our feature. Symfony uses closures for this but we can't use closures
        // in Attributes.
        // $suggestions = $tester->complete(['1', '12']);
        // $this->assertSame(['12', '121', '122'], $suggestions);
    }

    function assertRunCommandViaApplicationEquals($command, $input, $expectedOutput, $expectedStatusCode = 0)
    {
        list($statusCode, $commandOutput) = $this->runCommandViaApplication($command, $input);

        $expectedOutput = preg_replace('#\r\n#ms', "\n", $expectedOutput);
        $commandOutput = preg_replace('#\r\n#ms', "\n", $commandOutput);

        $this->assertEquals($expectedOutput, $commandOutput);
        $this->assertEquals($expectedStatusCode, $statusCode);
    }

    function runCommandViaApplication($command, $input)
    {
        $output = new BufferedOutput();
        if ($this->commandFileInstance && method_exists($this->commandFileInstance, 'setOutput')) {
            $this->commandFileInstance->setOutput($output);
        }

        $application = new Application('TestApplication', '0.0.0');
        $alterOptionsEventManager = new AlterOptionsCommandEvent($application);

        $eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
        $eventDispatcher->addSubscriber($this->commandFactory->commandProcessor()->hookManager());
        $this->commandFactory->commandProcessor()->hookManager()->addCommandEvent($alterOptionsEventManager);
        $application->setDispatcher($eventDispatcher);

        $application->setAutoExit(false);
        $application->add($command);

        $statusCode = $application->run($input, $output);
        $commandOutput = trim(str_replace("\r", '', $output->fetch()));

        return [$statusCode, $commandOutput];
    }
}
