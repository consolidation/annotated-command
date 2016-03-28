<?php
namespace Consolidation\AnnotationCommand;

use Symfony\Component\Console\Input\ArgvInput;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PassThroughArgsInput extends ArgvInput
{
    protected $passThroughArgs;

    public function __construct(array $argv = null, InputDefinition $definition = null)
    {
        // Alas, ArgvInput simply throws away the pass-through args.
        if (null === $argv) {
            $argv = $_SERVER['argv'];
        }

        parent::__construct($argv, $definition);
    }
}
