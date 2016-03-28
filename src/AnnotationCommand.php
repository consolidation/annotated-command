<?php
namespace Consolidation\AnnotationCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnnotationCommand extends Command
{
    protected $commandCallback;
    protected $specialParameterClasses;

    public function __construct($name, $commandCallback, $specialParameterClasses)
    {
        parent::__construct($name);

        $this->commandCallback = $commandCallback;
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

    protected function runCommandCallback($args, &$status)
    {
        $result = false;
        $specialParameters = $this->getSpecialParameters();
        $args = array_merge($specialParameters, $args);
        try {
            $result = call_user_func_array($this->commandCallback, $args);
        } catch (\Exception $e) {
            $status = $e->getCode();
        }
        return $result;
    }

    protected function getSpecialParameters()
    {
        $specialParameters = [];
        foreach ($this->specialParameterClasses as $className => $callback) {
            if (is_array($callback) && (count($callback) == 1)) {
                array_unshift($callback, $this);
            }
            $specialParameters[] = $callback($className, $this);
        }
        return $specialParameters;
    }

    protected function getCommandReference()
    {
        return $this;
    }

    protected function processCommandResults($result, &$status)
    {
        // TODO:  Process result and decide what to do with it.
        // Allow client to add transformation / interpretation
        // callbacks.

        // If the result (post-processing) is an object that
        // implements ExitCodeInterface, then we will ask it
        // to give us the status code. Otherwise, we assume success.
        if ($result instanceof ExitCodeInterface) {
            $status = $result->getExitCode();
        }

        return $result;
    }

    protected function writeCommandOutput($result, OutputInterface $output)
    {
        // If $res is a string, then print it.
        if (is_string($result)) {
            $output->writeln($result);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get passthrough args, and add the options on the end.
        $args = $this->getArgsWithPassThrough($input);
        $args[] = $input->getOptions();

        // TODO: Call any validate / pre-hooks registered for this command

        // Run!
        $status = 0;
        $result = $this->runCommandCallback($args, $status);

        // Process!
        $result = $this->processCommandResults($result, $status);

        // TODO:  If status is non-zero, call rollback hooks
        // (unless we can just rely on Collection rollbacks)

        // Output!
        $this->writeCommandOutput($result, $output);

        return $status;
    }
}
