<?php
namespace Consolidation\TestUtils\beta;

/**
 * Test file used in the testCommandDiscovery() test.
 *
 * This commandfile is not found by the test.  The test search base is the
 * 'src' directory, but 'beta' is NOT one of the search directories available
 * for searching, so nothing in this folder will be examined.
 */
class BetaCommandFile
{
    public function unavailableCommand()
    {
        return 'This command is not available, because this commandfile is not in a location that is searched by the tests.';
    }
}
