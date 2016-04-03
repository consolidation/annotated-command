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
     * @param mixed $result
     * @param array $args
     * @return mixed $result
     */
    public function alter($result, array $args);
}
