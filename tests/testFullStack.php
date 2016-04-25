<?php
namespace Consolidation\AnnotatedCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Application;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;

use Consolidation\AnnotatedCommand\Hooks\ValidatorInterface;
use Consolidation\AnnotatedCommand\Hooks\ProcessResultInterface;
use Consolidation\AnnotatedCommand\Hooks\AlterResultInterface;
use Consolidation\AnnotatedCommand\Hooks\ExtractOutputInterface;
use Consolidation\AnnotatedCommand\Hooks\StatusDeterminerInterface;

/**
 * Do a test of all of the classes in this project, top-to-bottom.
 */
class FullStackTests extends \PHPUnit_Framework_TestCase
{
    function setup() {
        $this->application = new Application('TestApplication', '0.0.0');
        $this->application->setAutoExit(false);
    }

    function testCommandsAndHooks()
    {
        // First, search for commandfiles in the 'alpha'
        // directory. Note that this same functionality
        // is tested more thoroughly in isolation in
        // testCommandFileDiscovery.php
        $discovery = new CommandFileDiscovery();
        $discovery
          ->setSearchPattern('*CommandFile.php')
          ->setIncludeFilesAtBase(false)
          ->setSearchLocations(['alpha']);

        chdir(__DIR__);
        $commandFiles = $discovery->discover('.', '\Consolidation\TestUtils');

        // Create a new factory, and load all of the files
        // discovered above.  The command factory class is
        // tested in isolation in testAnnotatedCommandFactory.php,
        // but this is the only place where
        $factory = new AnnotatedCommandFactory();
        // $factory->addListener(...);
        foreach ($commandFiles as $path => $commandClass) {
            $this->assertFileExists($path);
            include $path;
            $commandInstance = new $commandClass();
            $commandList = $factory->createCommandsFromClass($commandInstance);
            foreach ($commandList as $command) {
                $this->application->add($command);
            }
        }

        // Control: run commands without hooks.
        $this->assertRunCommandViaApplicationEquals('always:fail', 'This command always fails.', 13);
        $this->assertRunCommandViaApplicationEquals('simulated:status', '');
        $this->assertRunCommandViaApplicationEquals('example:output', 'Hello, World.');
        $this->assertRunCommandViaApplicationEquals('example:cat bet alpha --flip', 'alphabet');
        $this->assertRunCommandViaApplicationEquals('example:echo a b c', '');
        $this->assertRunCommandViaApplicationEquals('example:message', '');

        // Add some hooks.
        $factory->hookManager()->addValidator(new ExampleValidator());
        $factory->hookManager()->addResultProcessor(new ExampleResultProcessor());
        $factory->hookManager()->addAlterResult(new ExampleResultAlterer());
        $factory->hookManager()->addStatusDeterminer(new ExampleStatusDeterminer());
        $factory->hookManager()->addOutputExtractor(new ExampleOutputExtractor());

        // Run the same commands as before, and confirm that results
        // are different now that the hooks are in place.
        $this->assertRunCommandViaApplicationEquals('simulated:status', '', 42);
        $this->assertRunCommandViaApplicationEquals('example:output', 'Hello, World!');
        $this->assertRunCommandViaApplicationEquals('example:cat bet alpha --flip', 'alphabeta');
        $this->assertRunCommandViaApplicationEquals('example:echo a b c', 'a,b,c');
        $this->assertRunCommandViaApplicationEquals('example:message', 'Shipwrecked; send bananas.');
    }

    function assertRunCommandViaApplicationEquals($cmd, $expectedOutput, $expectedStatusCode = 0)
    {
        $input = new StringInput($cmd);
        $output = new BufferedOutput();

        $statusCode = $this->application->run($input, $output);
        $commandOutput = trim($output->fetch());

        $this->assertEquals($expectedOutput, $commandOutput);
        $this->assertEquals($expectedStatusCode, $statusCode);
    }
}

class ExampleValidator implements ValidatorInterface
{
    public function validate($args)
    {
        if (isset($args['one']) && ($args['one'] == 'bet')) {
            $args['one'] = 'beta';
            return $args;
        }
    }
}

class ExampleResultProcessor implements ProcessResultInterface
{
    public function process($result, array $args)
    {
        if (is_array($result) && array_key_exists('item-list', $result)) {
            return implode(',', $result['item-list']);
        }
    }
}

class ExampleResultAlterer implements AlterResultInterface
{
    public function process($result, array $args)
    {
        if (is_string($result) && ($result == 'Hello, World.')) {
            return 'Hello, World!';
        }
    }
}

class ExampleStatusDeterminer implements StatusDeterminerInterface
{
    public function determineStatusCode($result)
    {
        if (is_array($result) && array_key_exists('status-code', $result)) {
            return $result['status-code'];
        }
    }
}

class ExampleOutputExtractor implements ExtractOutputInterface
{
    public function extractOutput($result)
    {
        if (is_array($result) && array_key_exists('message', $result)) {
            return $result['message'];
        }
    }
}
