<?php
namespace Consolidation\AnnotationCommand;

class CommandFileDiscoveryTests extends \PHPUnit_Framework_TestCase
{
    function testCommandDiscovery()
    {
        $discovery = new CommandFileDiscovery();
        $discovery
          ->setSearchPattern('*CommandFile.php')
          ->setSearchLocations(['alpha']);

        chdir(__DIR__);
        $commandFiles = $discovery->discover('src');

        // Ensure that the command files that we expected to
        // find were all found.
        $this->assertContains('src/TestCommandFile.php', $commandFiles);
        $this->assertContains('src/alpha/AlphaCommandFile.php', $commandFiles);
        $this->assertContains('src/alpha/Include/IncludedCommandFile.php', $commandFiles);

        // Make sure that there are no additional items found.
        $this->assertEquals(3, count($commandFiles));
    }
}
