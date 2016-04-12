<?php
namespace Consolidation\AnnotatedCommand;

/**
 * A StatusDeterminer maps from a result to a status exit code.
 */
interface ProcessResultInterface
{
    /**
     * After a command has executed, if the result is something
     * that needs to be processed, e.g. a collection of tasks to
     * run, then execute it and return the new result.
     *
     * @param  mixed $result Result to (potentially) be processed
     * @param  array $args Reference to commandline arguments and options
     *
     * @return mixed $result
     */
    public function process($result, array $args);
}
