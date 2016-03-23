<?php
namespace Consolidation\AnnotationCommand;

/**
 * If an annotated command method encounters an error, then it
 * should either throw an exception, or return a result object
 * that implements ExitCodeInterface.
 */
interface ExitCodeInterface
{
    public function getExitCode();
}
