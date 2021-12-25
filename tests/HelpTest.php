<?php
namespace Consolidation\AnnotatedCommand;

use Composer\InstalledVersions;
use Consolidation\AnnotatedCommand\Help\HelpCommand;

use Consolidation\AnnotatedCommand\Options\AlterOptionsCommandEvent;
use Consolidation\OutputFormatters\FormatterManager;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Consolidation\TestUtils\ApplicationWithTerminalWidth;
use Consolidation\AnnotatedCommand\Options\PrepareTerminalWidthOption;
use PHPUnit\Framework\TestCase;

/**
 * Test our 'help' command.
 */
class HelpTest extends TestCase
{
    protected $application;
    protected $commandFactory;

    function setup(): void
    {
        $this->application = new ApplicationWithTerminalWidth('TestApplication', '0.0.0');
        $this->commandFactory = new AnnotatedCommandFactory();
        // $factory->addListener(...);
        $alterOptionsEventManager = new AlterOptionsCommandEvent($this->application);
        $eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
        $eventDispatcher->addSubscriber($this->commandFactory->commandProcessor()->hookManager());
        $this->commandFactory->commandProcessor()->hookManager()->addCommandEvent($alterOptionsEventManager);
        $this->application->setDispatcher($eventDispatcher);
        $this->application->setAutoExit(false);

        $discovery = new CommandFileDiscovery();
        $discovery
          ->setSearchPattern('*CommandFile.php')
          ->setIncludeFilesAtBase(false)
          ->setSearchLocations(['alpha']);

        chdir(__DIR__);
        $commandFiles = $discovery->discover('.', '\Consolidation\TestUtils');

        $formatter = new FormatterManager();
        $formatter->addDefaultFormatters();
        $formatter->addDefaultSimplifiers();
        $terminalWidthOption = new PrepareTerminalWidthOption();
        $terminalWidthOption->setApplication($this->application);
        $this->commandFactory->commandProcessor()->setFormatterManager($formatter);
        $this->commandFactory->commandProcessor()->addPrepareFormatter($terminalWidthOption);

        $this->commandFactory->setIncludeAllPublicMethods(false);
        $this->addDiscoveredCommands($this->commandFactory, $commandFiles);

        $helpCommandfile = new HelpCommand($this->application);
        $commandList = $this->commandFactory->createCommandsFromClass($helpCommandfile);
        foreach ($commandList as $command) {
            $this->application->add($command);
        }
    }

    public function addDiscoveredCommands($factory, $commandFiles) {
        foreach ($commandFiles as $path => $commandClass) {
            $this->assertFileExists($path);
            if (!class_exists($commandClass)) {
                include $path;
            }
            $commandInstance = new $commandClass();
            $commandList = $factory->createCommandsFromClass($commandInstance);
            foreach ($commandList as $command) {
                $this->application->add($command);
            }
        }
    }

    function assertRunCommandViaApplicationEquals($cmd, $expectedOutput, $expectedStatusCode = 0)
    {
        $input = new StringInput($cmd);
        $output = new BufferedOutput();

        $statusCode = $this->application->run($input, $output);
        $commandOutput = trim($output->fetch());

        $expectedOutput = $this->simplifyWhitespace($expectedOutput);
        $commandOutput = $this->simplifyWhitespace($commandOutput);

        $this->assertEquals($expectedOutput, $commandOutput);
        $this->assertEquals($expectedStatusCode, $statusCode);
    }

    function simplifyWhitespace($data)
    {
        $data = preg_replace('#\r\n#ms', "\n", $data);
        return trim(preg_replace('#\s+$#m', '', $data));
    }

    function testHelp()
    {
        $symfonyConsoleVersion = ltrim(InstalledVersions::getPrettyVersion('symfony/console'), 'v');
        if (version_compare($symfonyConsoleVersion, '5.3.0', '>=')) {
            $expectedAnsiMessage = 'Force (or disable --no-ansi) ANSI output';
            $expectedNoAnsiMessage = 'Negate the "--ansi" option';
        } else {
            $expectedAnsiMessage = 'Force ANSI output';
            $expectedNoAnsiMessage = 'Disable ANSI output';
        }

        if (version_compare($symfonyConsoleVersion, '5.2.0', '>=')) {
            $expectedHelpMessage = 'Display help for the given command. When no command is given display help for the <info>list</info> command';
        } else {
            $expectedHelpMessage = 'Display this help message';
        }

        $htmlEncodedHelpMessage = htmlspecialchars($expectedHelpMessage);

        $expectedXML = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<command id="example:table" name="example:table">
  <usages>
    <usage>example:table [--format [FORMAT]] [--fields [FIELDS]] [--field FIELD] [--] [&lt;unused&gt;]</usage>
  </usages>
  <examples>
    <example>
      <usage>example:table --format=yml</usage>
      <description>Show the example table in yml format.</description>
    </example>
    <example>
      <usage>example:table --fields=first,third</usage>
      <description>Show only the first and third fields in the table.</description>
    </example>
    <example>
      <usage>example:table --fields=II,III</usage>
      <description>Note that either the field ID or the visible field label may be used.</description>
    </example>
  </examples>
  <description>Test command with formatters</description>
  <arguments>
    <argument name="unused" is_required="0" is_array="0">
      <description>An unused argument</description>
      <defaults/>
    </argument>
  </arguments>
  <options>
    <option name="--format" shortcut="" accept_value="1" is_value_required="0" is_multiple="0">
      <description>Format the result data. Available formats: csv,json,list,null,php,print-r,sections,string,table,tsv,var_export,xml,yaml</description>
      <defaults>
        <default>table</default>
      </defaults>
    </option>
    <option name="--fields" shortcut="" accept_value="1" is_value_required="0" is_multiple="0">
      <description>Available fields: I (first), II (second), III (third)</description>
      <defaults/>
    </option>
    <option name="--field" shortcut="" accept_value="1" is_value_required="1" is_multiple="0">
      <description>Select just one field, and force format to 'string'.</description>
      <defaults/>
    </option>
    <option name="--help" shortcut="-h" accept_value="0" is_value_required="0" is_multiple="0">
      <description>$htmlEncodedHelpMessage</description>\n
    </option>
    <option name="--quiet" shortcut="-q" accept_value="0" is_value_required="0" is_multiple="0">
      <description>Do not output any message</description>
    </option>
    <option name="--verbose" shortcut="-v" shortcuts="-v|-vv|-vvv" accept_value="0" is_value_required="0" is_multiple="0">
      <description>Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug</description>
    </option>
    <option name="--version" shortcut="-V" accept_value="0" is_value_required="0" is_multiple="0">
      <description>Display this application version</description>
    </option>
    <option name="--ansi" shortcut="" accept_value="0" is_value_required="0" is_multiple="0">
      <description>$expectedAnsiMessage</description>
    </option>
    <option name="--no-ansi" shortcut="" accept_value="0" is_value_required="0" is_multiple="0">
      <description>$expectedNoAnsiMessage</description>
    </option>
    <option name="--no-interaction" shortcut="-n" accept_value="0" is_value_required="0" is_multiple="0">
      <description>Do not ask any interactive question</description>
    </option>
  </options>
  <help>Test command with formatters</help>
  <aliases>
    <alias>extab</alias>
  </aliases>
  <topics>
    <topic>docs-tables</topic>
  </topics>
</command>
EOT;

        $this->assertRunCommandViaApplicationEquals('my-help --format=xml example:table', $expectedXML);

        $encodedAnsiMessage = json_encode($expectedAnsiMessage);
        $encodedNoAnsiMessage = json_encode($expectedNoAnsiMessage);
        $encodedHelpMessage = json_encode(strip_tags($expectedHelpMessage));

        $expectedJSON = <<<EOT
{
    "id": "example:table",
    "name": "example:table",
    "usages": [
        "example:table [--format [FORMAT]] [--fields [FIELDS]] [--field FIELD] [--] [<unused>]"
    ],
    "examples": [
        {
            "usage": "example:table --format=yml",
            "description": "Show the example table in yml format."
        },
        {
            "usage": "example:table --fields=first,third",
            "description": "Show only the first and third fields in the table."
        },
        {
            "usage": "example:table --fields=II,III",
            "description": "Note that either the field ID or the visible field label may be used."
        }
    ],
    "description": "Test command with formatters",
    "arguments": {
        "unused": {
            "name": "unused",
            "is_required": "0",
            "is_array": "0",
            "description": "An unused argument"
        }
    },
    "options": {
        "format": {
            "name": "--format",
            "shortcut": "",
            "accept_value": "1",
            "is_value_required": "0",
            "is_multiple": "0",
            "description": "Format the result data. Available formats: csv,json,list,null,php,print-r,sections,string,table,tsv,var_export,xml,yaml",
            "defaults": [
                "table"
            ]
        },
        "fields": {
            "name": "--fields",
            "shortcut": "",
            "accept_value": "1",
            "is_value_required": "0",
            "is_multiple": "0",
            "description": "Available fields: I (first), II (second), III (third)"
        },
        "field": {
            "name": "--field",
            "shortcut": "",
            "accept_value": "1",
            "is_value_required": "1",
            "is_multiple": "0",
            "description": "Select just one field, and force format to 'string'."
        },
        "help": {
            "name": "--help",
            "shortcut": "-h",
            "accept_value": "0",
            "is_value_required": "0",
            "is_multiple": "0",
            "description": $encodedHelpMessage
        },
        "quiet": {
            "name": "--quiet",
            "shortcut": "-q",
            "accept_value": "0",
            "is_value_required": "0",
            "is_multiple": "0",
            "description": "Do not output any message"
        },
        "verbose": {
            "name": "--verbose",
            "shortcut": "-v",
            "shortcuts": "-v|-vv|-vvv",
            "accept_value": "0",
            "is_value_required": "0",
            "is_multiple": "0",
            "description": "Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug"
        },
        "version": {
            "name": "--version",
            "shortcut": "-V",
            "accept_value": "0",
            "is_value_required": "0",
            "is_multiple": "0",
            "description": "Display this application version"
        },
        "ansi": {
            "name": "--ansi",
            "shortcut": "",
            "accept_value": "0",
            "is_value_required": "0",
            "is_multiple": "0",
            "description": $encodedAnsiMessage
        },
        "no-ansi": {
            "name": "--no-ansi",
            "shortcut": "",
            "accept_value": "0",
            "is_value_required": "0",
            "is_multiple": "0",
            "description": $encodedNoAnsiMessage
        },
        "no-interaction": {
            "name": "--no-interaction",
            "shortcut": "-n",
            "accept_value": "0",
            "is_value_required": "0",
            "is_multiple": "0",
            "description": "Do not ask any interactive question"
        }
    },
    "help": "Test command with formatters",
    "aliases": [
        "extab"
    ],
    "topics": [
        "docs-tables"
    ]
}
EOT;
        $this->assertRunCommandViaApplicationEquals('my-help --format=json example:table', $expectedJSON);
    }
}
