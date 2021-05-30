<?php
namespace Consolidation\AnnotatedCommand;

use Consolidation\AnnotatedCommand\Options\AlterOptionsCommandEvent;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

use PHPUnit\Framework\TestCase;

class AttributesCommandFactoryTest extends TestCase
{
    protected $commandFileInstance;
    protected $commandFactory;

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
