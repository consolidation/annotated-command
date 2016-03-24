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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get passthru args
        $args = $input->getArguments();
        array_shift($args);
        if ($this->passThrough) {
            $args[key(array_slice($args, -1, 1, true))] = $this->passThrough;
        }
        $args[] = $input->getOptions();

        // TODO: Call any validate / pre-hooks registered for this command

        $status = 0;
        try {
            $result = call_user_func_array($this->commandCallback, $args);
        } catch (\Exception $e) {
            $status = $e->getCode();
        }

        // TODO:  Process result and decide what to do with it.
        // Allow client to add transformation / interpretation
        // callbacks.

        // If the result (post-processing) is an object that
        // implements ExitCodeInterface, then we will ask it
        // to give us the status code. Otherwise, we assume success.
        if ($result instanceof ExitCodeInterface) {
            $status = $result->getExitCode();
        }

        // TODO:  If result is non-zero, call rollback hooks
        // (unless we can just rely on Collection rollbacks)

        // If $res is a string, then print it.
        if (is_string($result)) {
            $output->writeln($result);
        }

        return $status;
    }
}
