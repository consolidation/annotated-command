<?php
namespace Consolidation\AnnotationCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnnotationCommand extends Command
{
    protected $commandCallback;
    protected $specialParameterClasses = [];
    protected $commandProcessor;
    protected $annotationData;

    public function __construct(
        $name,
        $commandCallback,
        $commandProcessor,
        $annotationData
    ) {
        parent::__construct($name);

        $this->commandCallback = $commandCallback;
        $this->commandProcessor = $commandProcessor;
        $this->annotationData = $annotationData;
    }

    public function setSpecialParameterClasses($specialParameterClasses)
    {
        $this->specialParameterClasses = $specialParameterClasses;
    }

    protected function getArgsWithPassThrough($input)
    {
        $args = $input->getArguments();

        // When called via the Application, the first argument
        // will be the command name. The Application alters the
        // input definition to match, adding a 'command' argument
        // to the beginning.
        array_shift($args);
        if ($input instanceof PassThroughArgsInput) {
            return $this->appendPassThroughArgs($input, $args);
        }
        return $args;
    }

    protected function appendPassThroughArgs($input, $args)
    {
        $passThrough = $input->getPassThroughArgs();
        $definition = $this->getDefinition();
        $argumentDefinitions = $definition->getArguments();
        $lastParameter = end($argumentDefinitions);
        if ($lastParameter && $lastParameter->isArray()) {
            $args[$lastParameter->getName()] = array_merge($args[$lastParameter->getName()], $passThrough);
        } else {
            $args[$lastParameter->getName()] = implode(' ', $passThrough);
        }
        return $args;
    }

    protected function getSpecialParameters(InputInterface $input, OutputInterface $output)
    {
        $specialParameters = [];
        foreach ($this->specialParameterClasses as $className => $callback) {
            if (is_array($callback) && (count($callback) == 1)) {
                array_unshift($callback, $this);
            }
            $specialParameters[] = $callback($this, $input, $output, $className);
        }
        return $specialParameters;
    }

    protected function getCommandReference()
    {
        return $this;
    }

    protected function getInputReference(Command $command, InputInterface $input, OutputInterface $output)
    {
        return $input;
    }

    protected function getOutputReference(Command $command, InputInterface $input, OutputInterface $output)
    {
        return $output;
    }

    protected function getNames()
    {
        return array_merge(
            [$this->getName()],
            $this->getAliases()
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get passthrough args, and add the options on the end.
        $args = $this->getArgsWithPassThrough($input);
        $args[] = $input->getOptions();

        $specialParameters = $this->getSpecialParameters($input, $output);

        return $this->commandProcessor->process(
            $this->getNames(),
            $this->commandCallback,
            $this->annotationData,
            $specialParameters,
            $args,
            $output
        );
    }
}
