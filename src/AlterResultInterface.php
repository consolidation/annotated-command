<?php
namespace Consolidation\AnnotationCommand;

/**
 * A StatusDeterminer maps from a result to a status exit code.
 */
interface AlterResultInterface
{
    /**
     * After a command has executed, inspect and
     * alter the result as necessary.
     *
     * @param  mixed $result Altered result
     * @param  array $args Reference to commandline arguments and options
     *
     * @return mixed $result
     */
    public function alter($result, array $args);
}
