<?php
namespace Consolidation\AnnotatedCommand;

use Composer\InstalledVersions;
use Consolidation\AnnotatedCommand\Hooks\HookManager;
use Consolidation\AnnotatedCommand\Options\AlterOptionsCommandEvent;
use Consolidation\TestUtils\ExampleCommandInfoAlterer;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Consolidation\AnnotatedCommand\Input\StdinHandler;

use PHPUnit\Framework\TestCase;

class AnnotatedCommandFactoryTest extends TestCase
{
    protected $commandFileInstance;
    protected $commandFactory;

    function testFibonacci()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'fibonacci');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);
        $this->assertEquals('fibonacci', $command->getName());
        $this->assertEquals('fibonacci [--graphic] [--] <start> <steps>', $command->getSynopsis());
        $this->assertEquals('Calculate the fibonacci sequence between two numbers.', $command->getDescription());

        $expectedHelp = "Graphic output will look like
+----+---+-------------+
|    |   |             |
|    |-+-|             |
|----+-+-+             |
|        |             |
|        |             |
|        |             |
+--------+-------------+";
        $actualHelp = $command->getHelp();

        $expectedHelp = preg_replace('#\r\n#ms', "\n", $expectedHelp);
        $actualHelp = preg_replace('#\r\n#ms', "\n", $actualHelp);

        $this->assertEquals($expectedHelp, $actualHelp);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);

        $input = new StringInput('help fibonacci');
        $this->assertRunCommandViaApplicationContains($command, $input, ['Display the sequence graphically using cube representation']);
    }

    function testSniff()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'sniff');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);
        $this->assertEquals('sniff', $command->getName());
        $this->assertEquals('sniff [--autofix] [--strict] [--] [<file>]', $command->getSynopsis());

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);

        $input = new StringInput('help sniff');
        $this->assertRunCommandViaApplicationContains($command, $input, ['A file or directory to analyze.']);

        $input = new StringInput('sniff --autofix --strict -- foo');
        $this->assertRunCommandViaApplicationContains($command, $input, ["'autofix' => true",
        "'strict' => true"]);
    }

    function testSymfony()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testSymfony');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);
        $this->assertEquals('test:symfony', $command->getName());
        // TODO: switch test based on Symfony version:
        // Symfony <4
        // $this->assertEquals('test:symfony [--foo FOO] [--] [<a>]...', $command->getSynopsis());
        // Symfony >=4
        // $this->assertEquals('test:symfony [--foo FOO] [--] [<a>...]', $command->getSynopsis());

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);

        $input = new StringInput('help test:symfony');
        $this->assertRunCommandViaApplicationContains($command, $input, ['A list of commandline parameters.', '--foo=FOO', '(multiple values allowed)']);

        $input = new StringInput('test:symfony a b c --foo=bar --foo=baz --foo=boz');
        $expected = <<<EOT
The parameters passed are:
array (
  0 => 'a',
  1 => 'b',
  2 => 'c',
)
The options passed via --foo are:
array (
  0 => 'bar',
  1 => 'baz',
  2 => 'boz',
)
EOT;

        $this->assertRunCommandViaApplicationEquals($command, $input, $expected);
    }

    function testOptionDefaultValue()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'defaultOptionOne');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);
        $this->assertEquals('default:option-one', $command->getName());
        $this->assertEquals('default:option-one [--foo [FOO]]', $command->getSynopsis());

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);

        $input = new StringInput('default:option-one');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Foo is 1');

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'defaultOptionTwo');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);
        $this->assertEquals('default:option-two', $command->getName());
        $this->assertEquals('default:option-two [--foo [FOO]]', $command->getSynopsis());

        $input = new StringInput('default:option-two');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Foo is 2');

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'defaultOptionNone');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);
        $this->assertEquals('default:option-none', $command->getName());
        $this->assertEquals('default:option-none [--foo FOO]', $command->getSynopsis());

        // Skip failing test until Symfony is fixed.
        $this->markTestSkipped('Symfony Console 3.2.5 and 3.2.6 do not handle default options with required values correctly.');

        $input = new StringInput('default:option-none --foo');
        $this->assertRunCommandViaApplicationContains($command, $input, ['The "--foo" option requires a value.'], 1);
    }

    function testGlobalOptionsOnly()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'globalOptionsOnly');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $input = new StringInput('global-options-only test');
        $this->assertRunCommandViaApplicationEquals($command, $input, "Arg is test, options[help] is false");
    }

    function testIgnoredCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();

        $commandList = $this->commandFactory->createCommandsFromClass($this->commandFileInstance);
        $this->assertArrayNotHasKey('ignoredCommand', $commandList);
    }

    function testIgnoredCommandByRegex()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();

        $commandList = $this->commandFactory->createCommandsFromClass($this->commandFileInstance);
        $this->assertArrayHasKey('ignoredCommandByRegex', $commandList);
        $this->commandFactory->addIgnoredCommandsRegexp('/ignored.ommand.y.egex/');
        $commandList = $this->commandFactory->createCommandsFromClass($this->commandFileInstance);
        $this->assertArrayNotHasKey('ignoredCommandByRegex', $commandList);
    }

    function testOptionWithOptionalValue()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'defaultOptionalValue');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        // Test to see if we can differentiate between a missing option, and
        // an option that has no value at all.
        $input = new StringInput('default:optional-value --foo=bar');
        $this->assertRunCommandViaApplicationEquals($command, $input, "Foo is 'bar'");

        $input = new StringInput('default:optional-value --foo');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Foo is true');

        $input = new StringInput('default:optional-value');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Foo is NULL');
    }

    function testOptionThatDefaultsToTrue()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'defaultOptionDefaultsToTrue');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        // Test to see if we can differentiate between a missing option, and
        // an option that has no value at all.
        $input = new StringInput('default:option-defaults-to-true --foo=bar');
        $this->assertRunCommandViaApplicationEquals($command, $input, "Foo is 'bar'");

        $input = new StringInput('default:option-defaults-to-true --foo');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Foo is true');

        $input = new StringInput('default:option-defaults-to-true');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Foo is true');

        $input = new StringInput('help default:option-defaults-to-true');
        $this->assertRunCommandViaApplicationContains(
            $command,
            $input,
            [
                '--no-foo',
                'Negate --foo option',
            ]
        );
        $input = new ArgvInput(['cli.php', 'default:option-defaults-to-true', '--no-foo']);
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Foo is false');

        // Do all of those things again, but with ArgvInput
        $input = new ArgvInput(['cli.php', 'default:option-defaults-to-true', '--foo=bar']);
        $this->assertRunCommandViaApplicationEquals($command, $input, "Foo is 'bar'");

        $input = new ArgvInput(['cli.php', 'default:option-defaults-to-true', '--foo']);
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Foo is true');

        $input = new ArgvInput(['cli.php', 'default:option-defaults-to-true']);
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Foo is true');

        $input = new ArgvInput(['cli.php', 'default:option-defaults-to-true', '--no-foo']);
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Foo is false');
    }
    /**
     * Test CommandInfo command caching.
     *
     * Sequence:
     *  - Create all of the command info objects from one class, caching them.
     *  - Change the method name of one of the items in the cache to a non-existent method
     *  - Restore all of the cached commandinfo objects
     *  - Ensure that the non-existent method cached commandinfo was not created
     *  - Ensure that the now-missing cached commandinfo was still created
     *
     * This tests both save/restore, plus adding a new command method to
     * a class, and removing a command method from a class.
     */
    function testAnnotatedCommandCache()
    {
        $testCacheStore = new \Consolidation\TestUtils\InMemoryCacheStore();

        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $this->commandFactory->setDataStore($testCacheStore);

        // Make commandInfo objects for every command in the test commandfile.
        // These will also be stored in our cache.
        $commandInfoList = $this->commandFactory->getCommandInfoListFromClass($this->commandFileInstance);

        $cachedClassName = get_class($this->commandFileInstance);

        $this->assertTrue($testCacheStore->has($cachedClassName));

        $cachedData = $testCacheStore->get($cachedClassName);
        $this->assertFalse(empty($cachedData));
        $this->assertTrue(array_key_exists('testArithmatic', $cachedData));

        $alterCommandInfoCache = $cachedData['testArithmatic'];
        unset($cachedData['testArithmatic']);
        $alterCommandInfoCache['method_name'] = 'nonExistentMethod';
        $cachedData[$alterCommandInfoCache['method_name']] = $alterCommandInfoCache;

        $testCacheStore->set($cachedClassName, $cachedData);

        $restoredCommandInfoList = $this->commandFactory->getCommandInfoListFromClass($this->commandFileInstance);

        $rebuiltCachedData = $testCacheStore->get($cachedClassName);

        $this->assertFalse(empty($rebuiltCachedData));
        $this->assertTrue(array_key_exists('testArithmatic', $rebuiltCachedData));
        $this->assertFalse(array_key_exists('nonExistentMethod', $rebuiltCachedData));
    }

    /**
     * Test CommandInfo command annotation parsing.
     */
    function testAnnotatedCommandCreation()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testArithmatic');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:arithmatic', $command->getName());
        $this->assertEquals('This is the test:arithmatic command', $command->getDescription());
        $this->assertEquals("This command will add one and two. If the --negate flag\nis provided, then the result is negated.", $command->getHelp());
        $this->assertEquals('arithmatic', implode(',', $command->getAliases()));
        $this->assertEquals('test:arithmatic [--negate] [--unused [UNUSED]] [--] <one> [<two>]', $command->getSynopsis());
        $this->assertEquals('test:arithmatic 2 2 --negate', implode(',', $command->getUsages()));

        $input = new StringInput('arithmatic 2 3 --negate');
        $this->assertRunCommandViaApplicationEquals($command, $input, '-5');
    }

    /**
     * Test CommandInfo command annotation altering.
     */
    function testAnnotatedCommandInfoAlteration()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'myEcho');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $annotationData = $command->getAnnotationData();
        $this->assertTrue($annotationData->has('arbitrary'));
        $this->assertFalse($annotationData->has('dynamic'));

        $this->commandFactory->addCommandInfoAlterer(new ExampleCommandInfoAlterer());

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'myEcho');
        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $annotationData = $command->getAnnotationData();
        $this->assertTrue($annotationData->has('arbitrary'));
        $this->assertTrue($annotationData->has('dynamic'));
    }

    function testMyEchoCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
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

    function testImprovedEchoCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'improvedEcho');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('improved:echo', $command->getName());
        $this->assertEquals('This is the improved version of the my:echo command', $command->getDescription());
        $this->assertEquals("This command will concatenate two parameters. If the --flip flag\nis provided, then the result is the concatenation of two and one.", $command->getHelp());
        $this->assertEquals('c', implode(',', $command->getAliases()));
        $this->assertEquals('improved:echo [--flip] [--] [<args>...]', $command->getSynopsis());
        $this->assertEquals('improved:echo bet alpha --flip', implode(',', $command->getUsages()));

        $input = new StringInput('improved:echo bet alpha --flip');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'alphabet');
    }

    function testImprovedOptionsCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'improvedOptions');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('improved:options', $command->getName());
        $this->assertEquals('This is the improved way to declare options.', $command->getDescription());
        $this->assertEquals("This command will echo its arguments and options", $command->getHelp());
        $this->assertEquals('c', implode(',', $command->getAliases()));
        $this->assertEquals('improved:options [--o1] [--o2] [--] <a1> <a2>', $command->getSynopsis());
        $this->assertEquals('improved:options a b --o1=x --o2=y', implode(',', $command->getUsages()));

        $input = new StringInput('improved:options a b');
        $this->assertRunCommandViaApplicationEquals($command, $input, "args are a and b, and options are 'one' and 'two'");

        $input = new StringInput('improved:options a b --o1=x --o2=y');
        $this->assertRunCommandViaApplicationEquals($command, $input, "args are a and b, and options are 'x' and 'y'");
    }

    function testCatCommand()
    {
        $path = __DIR__ . '/fixtures/stdin.txt';
        $selfEvidentPath = __DIR__ . '/fixtures/self-evident.txt';
        $stdinHandler = new StdinHandler();
        $stdinHandler->redirect($path);

        $this->commandFileInstance = new \Consolidation\TestUtils\StdinCommandFile;
        $this->commandFileInstance->setStdinHandler($stdinHandler);
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'cat');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('cat', $command->getName());

        $input = new StringInput('cat');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'hello world');

        if (DIRECTORY_SEPARATOR == '\\') {
            $this->markTestSkipped('StdinHandler input selection not working for Windows.');
        }

        $input = new StringInput('cat ' . $selfEvidentPath);
        $this->assertRunCommandViaApplicationEquals($command, $input, 'We hold these truths to be self-evident, that all men are created equal, that they are endowed by their Creator with certain unalienable Rights, that among these are Life, Liberty and the pursuit of Happiness.');

        $input = new StringInput('cat --file=' . $selfEvidentPath);
        $this->assertRunCommandViaApplicationContains($command, $input, ['The "--file" option does not exist.'], 1);
    }

    function testCatTooCommand()
    {
        $path = __DIR__ . '/fixtures/stdin.txt';
        $selfEvidentPath = __DIR__ . '/fixtures/self-evident.txt';
        $stdinHandler = new StdinHandler();
        $stdinHandler->redirect($path);

        $this->commandFileInstance = new \Consolidation\TestUtils\StdinCommandFile;
        $this->commandFileInstance->setStdinHandler($stdinHandler);
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'catToo');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('cat:too', $command->getName());

        $input = new StringInput('cat:too');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'hello world');

        if (DIRECTORY_SEPARATOR == '\\') {
            $this->markTestSkipped('StdinHandler input selection not working for Windows.');
        }

        $input = new StringInput('cat:too --file=' . $selfEvidentPath);
        $this->assertRunCommandViaApplicationEquals($command, $input, 'We hold these truths to be self-evident, that all men are created equal, that they are endowed by their Creator with certain unalienable Rights, that among these are Life, Liberty and the pursuit of Happiness.');

        $input = new StringInput('cat:too ' . $selfEvidentPath);

        $symfonyConsoleVersion = ltrim(InstalledVersions::getPrettyVersion('symfony/console'), 'v');
        if (version_compare($symfonyConsoleVersion, '5.2.0', '>=')) {
            $expectedMessage = 'No arguments expected for';
        } else {
            $expectedMessage = 'Too many arguments';
        }

        $this->assertRunCommandViaApplicationContains($command, $input, [$expectedMessage], 1);
    }

    function testCatNoDICommand()
    {
        if (!interface_exists('Symfony\Component\Console\Input\StreamableInputInterface')) {
            $this->markTestSkipped('The "no-di" variant of StdinHandler only works on versions of Symfony that have StreamableInputInterface');
        }
        $path = __DIR__ . '/fixtures/stdin.txt';
        $selfEvidentPath = __DIR__ . '/fixtures/self-evident.txt';

        $this->commandFileInstance = new \Consolidation\TestUtils\StdinCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'catNoDI');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('cat:no-di', $command->getName());

        $input = new StringInput('cat:no-di');
        $input->setStream(fopen($path, 'r'));
        $this->assertRunCommandViaApplicationEquals($command, $input, 'hello world');

        if (DIRECTORY_SEPARATOR == '\\') {
            $this->markTestSkipped('StdinHandler input selection not working for Windows.');
        }

        $input = new StringInput('cat:no-di --file=' . $selfEvidentPath);
        $input->setStream(fopen($path, 'r'));
        $this->assertRunCommandViaApplicationEquals($command, $input, 'We hold these truths to be self-evident, that all men are created equal, that they are endowed by their Creator with certain unalienable Rights, that among these are Life, Liberty and the pursuit of Happiness.');

        $symfonyConsoleVersion = ltrim(InstalledVersions::getPrettyVersion('symfony/console'), 'v');
        if (version_compare($symfonyConsoleVersion, '5.2.0', '>=')) {
            $expectedMessage = 'No arguments expected for';
        } else {
            $expectedMessage = 'Too many arguments';
        }

        $input = new StringInput('cat:no-di ' . $selfEvidentPath);
        $this->assertRunCommandViaApplicationContains($command, $input, [$expectedMessage], 1);
    }

    function testJoinCommandHelp()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'myJoin');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('my:join', $command->getName());
        $this->assertEquals('This is the my:join command', $command->getDescription());
        $this->assertEquals("This command will join its parameters together. It can also reverse and repeat its arguments.", $command->getHelp());
        // Older versions of Symfony: [<args>]...
        // Newer versions of Symfony: [<args>...]
        $synopsis = $command->getSynopsis();
        $synopsis = preg_replace('#]...$#', '...]', $synopsis);
        $this->assertEquals('my:join [--flip] [--repeat [REPEAT]] [--] [<args>...]', $synopsis);

        // TODO: Extra whitespace character if there are no options et. al. in the
        // usage. This is uncommon, and the defect is invisible. Maybe find it someday.
        $actualUsages = implode(',', $command->getUsages());
        $this->assertEquals('my:join a b,my:join ', $actualUsages);

        $input = new StringInput('my:join bet alpha --flip --repeat=2');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'alphabetalphabet');
    }

    function testDefaultsCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'defaults');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('defaults', $command->getName());
        $this->assertEquals('Test default values in arguments', $command->getDescription());

        $input = new StringInput('defaults');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'nothing provided');

        $input = new StringInput('defaults ichi');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'only ichi');

        $input = new StringInput('defaults I II');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'I and II');
    }

    function testCommandWithNoOptions()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'commandWithNoOptions');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('command:with-no-options', $command->getName());
        $this->assertEquals('This is a command with no options', $command->getDescription());
        $this->assertEquals("This command will concatenate two parameters.", $command->getHelp());
        $this->assertEquals('nope', implode(',', $command->getAliases()));
        $this->assertEquals('command:with-no-options <one> [<two>]', $command->getSynopsis());
        $this->assertEquals('command:with-no-options alpha bet', implode(',', $command->getUsages()));

        $input = new StringInput('command:with-no-options something');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'somethingdefault');

        $input = new StringInput('help command:with-no-options something');
        $this->assertRunCommandViaApplicationContains(
            $command,
            $input,
            [
                'The first parameter.',
                'The other parameter.',
            ]
        );
    }

    function testCommandWithIOParameters()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'commandWithIOParameters');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('command:with-io-parameters', $command->getName());
        $this->assertEquals("This command work with app's input and output", $command->getDescription());
        $this->assertEquals('', $command->getHelp());
        $this->assertEquals('command:with-io-parameters', $command->getSynopsis());

        $input = new StringInput('command:with-io-parameters');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'command:with-io-parameters');
    }

    function testCommandWithNoArguments()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'commandWithNoArguments');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('command:with-no-arguments', $command->getName());
        $this->assertEquals('This command has no arguments--only options', $command->getDescription());
        $this->assertEquals("Return a result only if not silent.", $command->getHelp());
        $this->assertEquals('command:with-no-arguments [-s|--silent]', $command->getSynopsis());

        $input = new StringInput('command:with-no-arguments');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Hello, world');
        $input = new StringInput('command:with-no-arguments -s');
        $this->assertRunCommandViaApplicationEquals($command, $input, '');
        $input = new StringInput('command:with-no-arguments --silent');
        $this->assertRunCommandViaApplicationEquals($command, $input, '');
    }

    function testCommandWithShortcutOnAnnotation()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'shortcutOnAnnotation');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('shortcut:on-annotation', $command->getName());
        $this->assertEquals('Shortcut on annotation', $command->getDescription());
        $this->assertEquals("This command defines the option shortcut on the annotation instead of in the options array.", $command->getHelp());
        $this->assertEquals('shortcut:on-annotation [-s|--silent]', $command->getSynopsis());

        $input = new StringInput('shortcut:on-annotation');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Hello, world');
        $input = new StringInput('shortcut:on-annotation -s');
        $this->assertRunCommandViaApplicationEquals($command, $input, '');
        $input = new StringInput('shortcut:on-annotation --silent');
        $this->assertRunCommandViaApplicationEquals($command, $input, '');
    }

    function testState()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile('secret secret');
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testState');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:state', $command->getName());

        $input = new StringInput('test:state');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'secret secret');
    }

    function testPassthroughArray()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testPassthrough');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:passthrough', $command->getName());

        $input = new StringInput('test:passthrough a b c -- x y z');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'a,b,c,x,y,z');
    }

    function testPassThroughNonArray()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'myJoin');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $input = new StringInput('my:join bet --flip -- x y z');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'zyxbet');
        // Can't look at 'hasOption' until after the command initializes the
        // option, because Symfony.
        $this->assertTrue($input->hasOption('flip'));
    }

    function testPassThroughWithInputManipulation()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'myJoin');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $input = new StringInput('my:join bet --repeat=2 -- x y z');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'betxyzbetxyz');
        // Symfony does not allow us to manipulate the options via setOption until
        // the definition from the command object has been set up.
        $input->setOption('repeat', 3);
        $this->assertEquals(3, $input->getOption('repeat'));

        $this->markTestSkipped('TODO: Input manipulation not working.');

        $input->setArgument(0, 'q');
        // Manipulating $input does not work -- the changes are not effective.
        // The end result here should be 'qx y yqx y yqx y y'
        $this->assertRunCommandViaApplicationEquals($command, $input, 'betxyzbetxyz');
    }

    function testRequiredArrayOption()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testRequiredArrayOption');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);
        $this->assertEquals('test:required-array-option [-a|--arr ARR]', $command->getSynopsis());

        $input = new StringInput('test:required-array-option --arr=1 --arr=2 --arr=3');
        $this->assertRunCommandViaApplicationEquals($command, $input, '1 2 3');

        $input = new StringInput('test:required-array-option -a 1 -a 2 -a 3');
        $this->assertRunCommandViaApplicationEquals($command, $input, '1 2 3');
    }

    function testArrayOption()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile;
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testArrayOption');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);
        $this->assertEquals('test:array-option [-a|--arr [ARR]]', $command->getSynopsis());

        $input = new StringInput('test:array-option');
        $this->assertRunCommandViaApplicationEquals($command, $input, '1 2 3');

        $input = new StringInput('test:array-option --arr=a --arr=b --arr=c');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'a b c');

        $input = new StringInput('test:array-option -a a');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'a');
    }

    function testHookedCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $this->commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'hookTestHook');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals('alter test:hook', $hookInfo->getAnnotation('hook'));

        $this->commandFactory->registerCommandHook($hookInfo, $this->commandFileInstance);

        $hookCallback = $this->commandFactory->hookManager()->get('test:hook', [HookManager::ALTER_RESULT]);
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('hookTestHook', $hookCallback[0][1]);

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testHook');
        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:hook', $command->getName());

        $input = new StringInput('test:hook bar');
        $this->assertRunCommandViaApplicationEquals($command, $input, '<[bar]>');

        $input = new StringInput('list --raw');
        $this->assertRunCommandViaApplicationContains($command, $input, ['This command wraps its parameter in []; its alter hook then wraps the result in .']);
    }

    function testReplaceCommandHook(){
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $this->commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'hookTestReplaceCommandHook');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals('replace-command test:replace-command', $hookInfo->getAnnotation('hook'));

        $this->commandFactory->registerCommandHook($hookInfo, $this->commandFileInstance);

        $hookCallback = $this->commandFactory->hookManager()->get('test:replace-command', [HookManager::REPLACE_COMMAND_HOOK]);
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('hookTestReplaceCommandHook', $hookCallback[0][1]);

        $input = new StringInput('test:replace-command foo');
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testReplaceCommand');
        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);
        $this->assertRunCommandViaApplicationEquals($command, $input, "bar", 0);
    }

    function testPostCommandCalledAfterCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $this->commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'hookTestPostCommandHook');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals('post-command test:post-command', $hookInfo->getAnnotation('hook'));

        $this->commandFactory->registerCommandHook($hookInfo, $this->commandFileInstance);

        $hookCallback = $this->commandFactory->hookManager()->get('test:post-command', [HookManager::POST_COMMAND_HOOK]);
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('hookTestPostCommandHook', $hookCallback[0][1]);

        $hookInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'hookTestPreCommandHook');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals('pre-command test:post-command', $hookInfo->getAnnotation('hook'));

        $this->commandFactory->registerCommandHook($hookInfo, $this->commandFileInstance);

        $hookCallback = $this->commandFactory->hookManager()->get('test:post-command', [HookManager::PRE_COMMAND_HOOK]);
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('hookTestPreCommandHook', $hookCallback[0][1]);

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testPostCommand');
        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:post-command', $command->getName());

        $input = new StringInput('test:post-command bar');
        $this->assertRunCommandViaApplicationEquals($command, $input, "foo\nbar\nbaz", 0, $this->commandFileInstance);
    }

    function testHookAllCommands()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleHookAllCommandFile();
        $this->commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'alterAllCommands');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals('alter', $hookInfo->getAnnotation('hook'));

        $this->commandFactory->registerCommandHook($hookInfo, $this->commandFileInstance);

        $hookCallback = $this->commandFactory->hookManager()->get('Consolidation\TestUtils\ExampleHookAllCommandFile', [HookManager::ALTER_RESULT]);
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('alterAllCommands', $hookCallback[0][1]);

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'doCat');
        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('do:cat', $command->getName());

        $input = new StringInput('do:cat bar');
        $this->assertRunCommandViaApplicationEquals($command, $input, '*** bar ***');
    }

    function testDoubleDashWithVersion()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleHookAllCommandFile();
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'doCat');
        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $input = new ArgvInput(['placeholder', 'do:cat', 'one', '--', '--version']);
        list($statusCode, $commandOutput) = $this->runCommandViaApplication($command, $input);

        if ($commandOutput == 'TestApplication version 0.0.0') {
            $this->markTestSkipped('Symfony/Console 2.x does not respect -- with --version');
        }
        $this->assertEquals('one--version', $commandOutput);
    }

    function testAnnotatedHookedCommand()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $this->commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'hookTestAnnotatedHook');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals('alter @hookme', $hookInfo->getAnnotation('hook'));

        $this->commandFactory->registerCommandHook($hookInfo, $this->commandFileInstance);
        $hookCallback = $this->commandFactory->hookManager()->get('@hookme', [HookManager::ALTER_RESULT]);
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('hookTestAnnotatedHook', $hookCallback[0][1]);

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testAnnotationHook');
        $annotationData = $commandInfo->getRawAnnotations();
        $this->assertEquals('hookme,before,after', implode(',', $annotationData->keys()));
        $this->assertEquals('@hookme,@before,@after', implode(',', array_map(function ($item) { return "@$item"; }, $annotationData->keys())));

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:annotation-hook', $command->getName());

        $input = new StringInput('test:annotation-hook baz');
        $this->assertRunCommandViaApplicationEquals($command, $input, '>(baz)<');
    }

    function testHookHasCommandAnnotation()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $this->commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'hookAddCommandName');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals('alter @addmycommandname', $hookInfo->getAnnotation('hook'));

        $this->commandFactory->registerCommandHook($hookInfo, $this->commandFileInstance);
        $hookCallback = $this->commandFactory->hookManager()->get('@addmycommandname', [HookManager::ALTER_RESULT]);
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('hookAddCommandName', $hookCallback[0][1]);

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'alterMe');
        $annotationData = $commandInfo->getRawAnnotations();
        $this->assertEquals('command,addmycommandname', implode(',', $annotationData->keys()));

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('alter-me', $command->getName());

        $input = new StringInput('alter-me');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'splendiferous from alter-me');

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'alterMeToo');
        $annotationData = $commandInfo->getRawAnnotations();
        $this->assertEquals('addmycommandname', implode(',', $annotationData->keys()));
        $annotationData = $commandInfo->getAnnotations();
        $this->assertEquals('addmycommandname,command,_path,_classname', implode(',', $annotationData->keys()));

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('alter:me-too', $command->getName());

        $input = new StringInput('alter:me-too');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'fantabulous from alter:me-too');
    }

    function testHookedCommandWithHookAddedLater()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $this->commandFactory = new AnnotatedCommandFactory();
        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testHook');

        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:hook', $command->getName());

        // Run the command once without the hook
        $input = new StringInput('test:hook foo');
        $this->assertRunCommandViaApplicationEquals($command, $input, '[foo]');

        // Register the hook and run the command again
        $hookInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'hookTestHook');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals('alter test:hook', $hookInfo->getAnnotation('hook'));

        $this->commandFactory->registerCommandHook($hookInfo, $this->commandFileInstance);
        $hookCallback = $this->commandFactory->hookManager()->get('test:hook', [HookManager::ALTER_RESULT]);;
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('hookTestHook', $hookCallback[0][1]);

        $input = new StringInput('test:hook bar');
        $this->assertRunCommandViaApplicationEquals($command, $input, '<[bar]>');
    }

    function testInitializeHook()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $this->commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'initializeTestHello');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals($hookInfo->getAnnotation('hook'), 'init test:hello');

        $this->commandFactory->registerCommandHook($hookInfo, $this->commandFileInstance);

        $hookCallback = $this->commandFactory->hookManager()->get('test:hello', [HookManager::INITIALIZE]);
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('initializeTestHello', $hookCallback[0][1]);

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testHello');
        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:hello', $command->getName());
        $commandGetNames = $this->callProtected($command, 'getNames');
        $this->assertEquals('test:hello,Consolidation\TestUtils\ExampleCommandFile', implode(',', $commandGetNames));

        $hookCallback = $command->commandProcessor()->hookManager()->get('test:hello', 'init');
        $this->assertTrue($hookCallback != null);
        $this->assertEquals('initializeTestHello', $hookCallback[0][1]);

        $input = new StringInput('test:hello');
        $this->assertRunCommandViaApplicationEquals($command, $input, "Hello, Huey.");
    }

    function testCommandEventHook()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $this->commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'commandEventTestHello');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals($hookInfo->getAnnotation('hook'), 'command-event test:hello');

        $this->commandFactory->registerCommandHook($hookInfo, $this->commandFileInstance);

        $hookCallback = $this->commandFactory->hookManager()->get('test:hello', [HookManager::COMMAND_EVENT]);
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('commandEventTestHello', $hookCallback[0][1]);

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testHello');
        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:hello', $command->getName());
        $commandGetNames = $this->callProtected($command, 'getNames');
        $this->assertEquals('test:hello,Consolidation\TestUtils\ExampleCommandFile', implode(',', $commandGetNames));

        $hookCallback = $command->commandProcessor()->hookManager()->get('test:hello', 'command-event');
        $this->assertTrue($hookCallback != null);
        $this->assertEquals('commandEventTestHello', $hookCallback[0][1]);

        $input = new StringInput('test:hello Pluto');
        $this->assertRunCommandViaApplicationEquals($command, $input, "Here comes Pluto!\nHello, Pluto.");
    }


    function testInteractAndValidate()
    {
        $this->commandFileInstance = new \Consolidation\TestUtils\ExampleCommandFile();
        $this->commandFactory = new AnnotatedCommandFactory();

        $hookInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'interactTestHello');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals($hookInfo->getAnnotation('hook'), 'interact test:hello');

        $this->commandFactory->registerCommandHook($hookInfo, $this->commandFileInstance);

        $hookInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'validateTestHello');

        $this->assertTrue($hookInfo->hasAnnotation('hook'));
        $this->assertEquals($hookInfo->getAnnotation('hook'), 'validate test:hello');

        $this->commandFactory->registerCommandHook($hookInfo, $this->commandFileInstance);

        $hookCallback = $this->commandFactory->hookManager()->get('test:hello', [HookManager::ARGUMENT_VALIDATOR]);
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('validateTestHello', $hookCallback[0][1]);

        $hookCallback = $this->commandFactory->hookManager()->get('test:hello', [HookManager::INTERACT]);
        $this->assertTrue($hookCallback != null);
        $this->assertEquals(1, count($hookCallback));
        $this->assertEquals(2, count($hookCallback[0]));
        $this->assertTrue(is_callable($hookCallback[0]));
        $this->assertEquals('interactTestHello', $hookCallback[0][1]);

        $commandInfo = $this->commandFactory->createCommandInfo($this->commandFileInstance, 'testHello');
        $command = $this->commandFactory->createCommand($commandInfo, $this->commandFileInstance);

        $this->assertInstanceOf('\Symfony\Component\Console\Command\Command', $command);
        $this->assertEquals('test:hello', $command->getName());
        $commandGetNames = $this->callProtected($command, 'getNames');
        $this->assertEquals('test:hello,Consolidation\TestUtils\ExampleCommandFile', implode(',', $commandGetNames));

        $testInteractInput = new StringInput('test:hello');
        $definition = new \Symfony\Component\Console\Input\InputDefinition(
            [
                new \Symfony\Component\Console\Input\InputArgument('application', \Symfony\Component\Console\Input\InputArgument::REQUIRED),
                new \Symfony\Component\Console\Input\InputArgument('who', \Symfony\Component\Console\Input\InputArgument::REQUIRED),
            ]
        );
        $testInteractInput->bind($definition);
        $testInteractOutput = new BufferedOutput();
        $command->commandProcessor()->interact(
            $testInteractInput,
            $testInteractOutput,
            $commandGetNames,
            $command->getAnnotationData()
        );
        $this->assertEquals('Goofey', $testInteractInput->getArgument('who'));

        $hookCallback = $command->commandProcessor()->hookManager()->get('test:hello', 'interact');
        $this->assertTrue($hookCallback != null);
        $this->assertEquals('interactTestHello', $hookCallback[0][1]);

        $input = new StringInput('test:hello "Mickey Mouse"');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Hello, Mickey Mouse.');

        $input = new StringInput('test:hello');
        $this->assertRunCommandViaApplicationEquals($command, $input, 'Hello, Goofey.');

        $input = new StringInput('test:hello "Donald Duck"');
        $this->assertRunCommandViaApplicationEquals($command, $input, "I won't say hello to Donald Duck.", 1);

        $input = new StringInput('test:hello "Drumph"');
        $this->assertRunCommandViaApplicationEquals($command, $input, "Irrational value error.", 1);

        // Try the last test again with a display error function installed.
        $this->commandFactory->commandProcessor()->setDisplayErrorFunction(
            function ($output, $message) {
                $output->writeln("*** $message ****");
            }
        );

        $input = new StringInput('test:hello "Drumph"');
        $this->assertRunCommandViaApplicationEquals($command, $input, "*** Irrational value error. ****", 1);
    }

    function callProtected($object, $method, $args = [])
    {
        $r = new \ReflectionMethod($object, $method);
        $r->setAccessible(true);
        return $r->invokeArgs($object, $args);
    }

    function assertRunCommandViaApplicationContains($command, $input, $containsList, $expectedStatusCode = 0)
    {
        list($statusCode, $commandOutput) = $this->runCommandViaApplication($command, $input);
        $commandOutput = preg_replace('#\r\n#ms', "\n", $commandOutput);

        foreach ($containsList as $contains) {
            $contains = preg_replace('#\r\n#ms', "\n", $contains);
            $this->assertStringContainsString($contains, $commandOutput);
        }
        $this->assertEquals($expectedStatusCode, $statusCode);
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
