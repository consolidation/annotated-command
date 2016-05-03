<?php
namespace Consolidation\TestUtils\alpha;

use Consolidation\AnnotatedCommand\CommandError;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Test file used in the testCommandDiscovery() test.
 *
 * This commandfile is found by the test.  The test search base is the
 * 'src' directory, and 'alpha' is one of the search directories available
 * for searching.
 */
class AlphaCommandFile
{
    /**
     * @command always:fail
     */
    public function alwaysFail()
    {
        return new CommandError('This command always fails.', 13);
    }

    /**
     * @command simulated:status
     */
    public function simulatedStatus()
    {
        return ['status-code' => 42];
    }

    /**
     * @command example:output
     */
    public function exampleOutput()
    {
        return 'Hello, World.';
    }

    /**
     * @command example:cat
     */
    public function exampleCat($one, $two = '', $options = ['flip' => false])
    {
        if ($options['flip']) {
            return "{$two}{$one}";
        }
        return "{$one}{$two}";
    }

    /**
     * @command example:echo
     */
    public function exampleEcho(array $args)
    {
        return ['item-list' => $args];
    }

    /**
     * @command example:message
     */
    public function exampleMessage()
    {
        return ['message' => 'Shipwrecked; send bananas.'];
    }

    /**
     * Test command with formatters
     *
     * @command example:table
     * @field-labels
     *   first: I
     *   second: II
     *   third: III
     * @usage try:formatters --format=yaml
     * @usage try:formatters --format=csv
     * @usage try:formatters --fields=first,third
     * @usage try:formatters --fields=III,II
     * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
     */
    public function exampleTable($options = ['format' => 'table', 'fields' => ''])
    {
        $outputData = [
            [ 'first' => 'One',  'second' => 'Two',  'third' => 'Three' ],
            [ 'first' => 'Eins', 'second' => 'Zwei', 'third' => 'Drei'  ],
            [ 'first' => 'Ichi', 'second' => 'Ni',   'third' => 'San'   ],
            [ 'first' => 'Uno',  'second' => 'Dos',  'third' => 'Tres'  ],
        ];
        return new RowsOfFields($outputData);
    }

    /**
     * This command has no annotations; this means that it will not be
     * found when createCommandsFromClass() is called with
     * '$includeAllPublicMethods' set to false.
     */
    public function withoutAnnotations()
    {
        return 'ok';
    }
}
