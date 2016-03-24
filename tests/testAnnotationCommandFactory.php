<?php
namespace Consolidation\AnnotationCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Application;

class AnnotationCommandFactoryTests extends \PHPUnit_Framework_TestCase
{
    /**
     * Test CommandInfo command annotation parsing.
     */
    function testAnnotationCommandCreation()
    {
        $commandFileInstance = new \Consolidation\TestUtils\TestCommandFile;
        $commandInfo = new CommandInfo($commandFileInstance, 'testArithmatic');
        $commandFactory = new AnnotationCommandFactory();

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf(Command::class, $command);
        $this->assertEquals('test:arithmatic', $command->getName());
        $this->assertEquals('This is the test:arithmatic command', $command->getDescription());
        $this->assertEquals("This command will add one and two. If the --negate flag\nis provided, then the result is negated.", $command->getHelp());
        $this->assertEquals('arithmatic', implode(',', $command->getAliases()));
        // Symfony Console composes the synopsis; perhaps we should not test it. Remove if this gives false failures.
        $this->assertEquals('test:arithmatic [--negate] [--] <one> <two>', $command->getSynopsis());
        $this->assertEquals('test:arithmatic 2 2 --negate', implode(',', $command->getUsages()));

        $input = new StringInput('arithmatic 2 3 --negate');
        $this->assertRunCommandViaApplicationEquals($command, $input, '-5');
    }

    function testMyCatCommand()
    {
        $commandFileInstance = new \Consolidation\TestUtils\TestCommandFile;
        $commandInfo = new CommandInfo($commandFileInstance, 'myCat');
        $commandFactory = new AnnotationCommandFactory();

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf(Command::class, $command);
        $this->assertEquals('my:cat', $command->getName());
        $this->assertEquals('This is the my:cat command', $command->getDescription());
        $this->assertEquals("This command will concatinate two parameters. If the --flip flag\nis provided, then the result is the concatination of two and one.", $command->getHelp());
        $this->assertEquals('c', implode(',', $command->getAliases()));
        // Symfony Console composes the synopsis; perhaps we should not test it. Remove if this gives false failures.
        $this->assertEquals('my:cat [--flip] [--] <one> <two>', $command->getSynopsis());
        $this->assertEquals('my:cat bet alpha --flip', implode(',', $command->getUsages()));

        // The first time we run a command directly, it only expects the parameters
        // that the command defines.
        $input = new StringInput('some one');
        $this->assertRunCommandDirectlyEquals($command, $input, 'someone');

        // If we run the command using the Application, though, then it alters the
        // command definition.
        $input = new StringInput('my:cat bet alpha --flip');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'alphabet');

        // Now the command expects that its first parameter should be the application name.
        // This does not exactly seem to be friendly behavior.
        $input = new StringInput('my:cat some one');
        $this->assertRunCommandDirectlyEquals($command, $input, 'someone');
    }

    function testState()
    {
        $commandFileInstance = new \Consolidation\TestUtils\TestCommandFile('secret secret');
        $commandInfo = new CommandInfo($commandFileInstance, 'testState');
        $commandFactory = new AnnotationCommandFactory();

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf(Command::class, $command);
        $this->assertEquals('test:state', $command->getName());

        $input = new StringInput('test:state');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'secret secret');
    }

    function assertRunCommandViaApplicationEquals($command, $input, $expectedOutput, $expectedStatusCode = 0)
    {
        $output = new BufferedOutput();

        $application = new Application('TestApplication', '0.0.0');
        $application->setAutoExit(false);
        $application->add($command);

        $statusCode = $application->run($input, $output);
        $commandOutput = trim($output->fetch());

        $this->assertEquals($expectedOutput, $commandOutput);
        $this->assertEquals($expectedStatusCode, $statusCode);
    }

    function assertRunCommandDirectlyEquals($command, $input, $expectedOutput, $expectedStatusCode = 0)
    {
        $output = new BufferedOutput();

        $statusCode = $command->run($input, $output);
        $commandOutput = trim($output->fetch());

        $this->assertEquals($expectedOutput, $commandOutput);
        $this->assertEquals($expectedStatusCode, $statusCode);
    }
}
