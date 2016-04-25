<?php
namespace Consolidation\TestUtils\alpha;

use Consolidation\AnnotatedCommand\CommandError;

/**
 * Test file used in the testCommandDiscovery() test.
 *
 * This commandfile is found by the test.  The test search base is the
 * 'src' directory, and 'alpha' is one of the search directories available
 * for searching.
 */
class AlphaCommandFile
{
    public function alwaysFail()
    {
        return new CommandError('This command always fails.', 13);
    }

    public function simulatedStatus()
    {
        return ['status-code' => 42];
    }

    public function exampleOutput()
    {
        return 'Hello, World.';
    }

    public function exampleCat($one, $two = '', $options = ['flip' => false])
    {
        if ($options['flip']) {
            return "{$two}{$one}";
        }
        return "{$one}{$two}";
    }

    public function exampleEcho(array $args)
    {
        return ['item-list' => $args];
    }

    public function exampleMessage()
    {
        return ['message' => 'Shipwrecked; send bananas.'];
    }
}
