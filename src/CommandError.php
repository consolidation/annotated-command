<?php
namespace Consolidation\AnnotatedCommand;

class CommandError implements ExitCodeInterface, OutputDataInterface
{
    protected $message;
    protected $exitCode;

    public function __construct($message = null, $exitCode = 1)
    {
        $this->message = $message;
        $this->exitCode = $exitCode;
    }
    public function getExitCode()
    {
        return $this->exitCode;
    }

    public function getOutputData()
    {
        return $this->message;
    }
}
