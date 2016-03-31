<?php
namespace Consolidation\AnnotationCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
        $commandFactory = new AnnotationCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'testArithmatic');

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
        $commandFactory = new AnnotationCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'myCat');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf(Command::class, $command);
        $this->assertEquals('my:cat', $command->getName());
        $this->assertEquals('This is the my:cat command', $command->getDescription());
        $this->assertEquals("This command will concatinate two parameters. If the --flip flag\nis provided, then the result is the concatination of two and one.", $command->getHelp());
        $this->assertEquals('c', implode(',', $command->getAliases()));
        // Symfony Console composes the synopsis; perhaps we should not test it. Remove if this gives false failures.
        $this->assertEquals('my:cat [--flip] [--] <one> [<two>]', $command->getSynopsis());
        $this->assertEquals('my:cat bet alpha --flip', implode(',', $command->getUsages()));

        $input = new StringInput('my:cat bet alpha --flip');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'alphabet');
    }

    function testState()
    {
        $commandFileInstance = new \Consolidation\TestUtils\TestCommandFile('secret secret');
        $commandFactory = new AnnotationCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'testState');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf(Command::class, $command);
        $this->assertEquals('test:state', $command->getName());

        $input = new StringInput('test:state');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'secret secret');
    }

    function testSpecialCommandParameter()
    {
        $commandFileInstance = new \Consolidation\TestUtils\TestCommandFile();
        $commandFactory = new AnnotationCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'testCommand');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf(Command::class, $command);
        $this->assertEquals('test:command', $command->getName());

        $input = new StringInput('test:command Message');
        $this->assertRunCommandViaApplicationEquals($command, $input, '[test] Message');
    }

    function testSpecialIOParameter()
    {
        $commandFileInstance = new \Consolidation\TestUtils\TestCommandFile();
        $commandFactory = new AnnotationCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'testIo');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf(Command::class, $command);
        $this->assertEquals('test:io', $command->getName());

        $input = new StringInput('test:io Message');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Message');
    }

    function testPassthroughArray()
    {
        $commandFileInstance = new \Consolidation\TestUtils\TestCommandFile;
        $commandFactory = new AnnotationCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'testPassthrough');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        $this->assertInstanceOf(Command::class, $command);
        $this->assertEquals('test:passthrough', $command->getName());

        $input = new StringInput('test:passthrough a b c');
        $input = new PassThroughArgsInput(['x', 'y', 'z'], $input);
        $this->assertRunCommandViaApplicationEquals($command, $input, 'a,b,c,x,y,z');
    }

    function testPassThroughNonArray()
    {
        $commandFileInstance = new \Consolidation\TestUtils\TestCommandFile;
        $commandFactory = new AnnotationCommandFactory();
        $commandInfo = $commandFactory->createCommandInfo($commandFileInstance, 'myCat');

        $command = $commandFactory->createCommand($commandInfo, $commandFileInstance);

        // If we run the command using the Application, though, then it alters the
        // command definition.
        $input = new StringInput('my:cat bet --flip');
        $input = new PassThroughArgsInput(['x', 'y', 'z'], $input);
        $this->assertRunCommandViaApplicationEquals($command, $input, 'x y zbet');
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
}
