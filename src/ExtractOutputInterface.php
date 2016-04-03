<?php
namespace Consolidation\AnnotationCommand;

/**
 * Extract Output hooks are used to select the particular
 * data elements of the result that should be printed as
 * the command output -- perhaps after being formatted by
 * a formatter.
 */
interface ExtractOutputInterface
{
    public function extractOutput($result);
}
