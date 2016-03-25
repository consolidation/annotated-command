<?php
namespace Consolidation\AnnotationCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnnotationCommand extends Command
{
    protected $commandCallback;
    protected $passThrough;

    public function __construct($name, $commandCallback, $passThrough)
    {
        parent::__construct($name);

        $this->commandCallback = $commandCallback;
        $this->passThrough = $passThrough;
    }

    protected function getArgsWithPassThrough($input)
    {
        $definition = $this->getDefinition();
        $argumentDefinitions = $definition->getArguments();
        $alteredByApplication = (key($argumentDefinitions) == 'command');
        $args = $input->getArguments();
        if ($alteredByApplication) {
            array_shift($args);
        }
        if ($this->passThrough) {
            $args[key(array_slice($args, -1, 1, true))] = $this->passThrough;
        }
        return $args;
    }

    protected function runCommandCallback($args, &$status)
    {
        $result = false;
        try {
            $result = call_user_func_array($this->commandCallback, $args);
        } catch (\Exception $e) {
            $status = $e->getCode();
        }
        return $result;
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
