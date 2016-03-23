<?php
namespace Consolidation\AnnotationCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AnnotationCommandFactory
{
    public function createCommandsFromClass($commandFileInstance, $passThrough = null)
    {
        $commandInfoList = $this->getCommandInfoListFromClass($commandFileInstance);
        return $this->createCommandsFromClassInfo($commandInfoList, $commandFileInstance, $passThrough);
    }

    public function getCommandInfoListFromClass($classNameOrInstance)
    {
        $commandInfoList = [];

        // Ignore special functions, such as __construct and __call, and
        // accessor methods such as getFoo and setFoo, while allowing
        // set or setup.
        $commandMethodNames = array_filter(get_class_methods($classNameOrInstance), function ($m) {
            return !preg_match('#^(_|get[A-Z]|set[A-Z])#', $m);
        });

        foreach ($commandMethodNames as $commandMethodName) {
            $commandInfoList[] = new CommandInfo($classNameOrInstance, $commandMethodName);
        }

        return $commandInfoList;
    }

    public function createCommandsFromClassInfo($commandInfoList, $commandFileInstance, $passThrough = null)
    {
        $commandList = [];

        foreach ($commandInfoList as $commandInfo) {
            $command = $this->createCommand($commandInfo, $commandFileInstance, $passThrough);
            $commandList[] = $command;
        }

        return $commandList;
    }

    public function createCommand(CommandInfo $commandInfo, $commandFileInstance, $passThrough = null)
    {
        $command = new Command($commandInfo->getName());
        $this->setCommandInfo($command, $commandInfo);
        $this->setCommandArguments($command, $commandInfo);
        $this->setCommandOptions($command, $commandInfo);
        $commandCallback = [$commandFileInstance, $commandInfo->getMethodName()];
        $this->setCommandHandler($command, $commandCallback, $passThrough);
        return $command;
    }

    protected function setCommandInfo($command, $commandInfo)
    {
        $command->setDescription($commandInfo->getDescription());
        $command->setHelp($commandInfo->getHelp());
        // TODO: Symfony Console commands by default put aliases and example
        // usages together in a list, one per line, in the "Usage" section.
        // There is no way to attach a description to a sample usage.  We
        // need to figure out how to replace the built-in help command with
        // our own version that has additional help sections (e.g. topics).
        $command->setAliases($commandInfo->getAliases());
        foreach ($commandInfo->getExampleUsages() as $usage => $description) {
            $command->addUsage($usage);
        }
    }

    protected function setCommandArguments($command, $commandInfo)
    {
        $args = $commandInfo->getArguments();
        foreach ($args as $name => $val) {
            $description = $commandInfo->getArgumentDescription($name);
            if ($val === CommandInfo::PARAM_IS_REQUIRED) {
                $command->addArgument($name, InputArgument::REQUIRED, $description);
            } elseif (is_array($val)) {
                $command->addArgument($name, InputArgument::IS_ARRAY, $description, $val);
            } else {
                $command->addArgument($name, InputArgument::OPTIONAL, $description, $val);
            }
        }
    }

    protected function setCommandOptions($command, $commandInfo)
    {
        $opts = $commandInfo->getOptions();
        foreach ($opts as $name => $val) {
            $description = $commandInfo->getOptionDescription($name);

            $fullName = $name;
            $shortcut = '';
            if (strpos($name, '|')) {
                list($fullName, $shortcut) = explode('|', $name, 2);
            }

            if (is_bool($val)) {
                $command->addOption($fullName, $shortcut, InputOption::VALUE_NONE, $description);
            } else {
                $command->addOption($fullName, $shortcut, InputOption::VALUE_OPTIONAL, $description, $val);
            }
        }
    }

    protected function setCommandHandler($command, $commandCallback, $passThrough)
    {
        $command->setCode(function (InputInterface $input, OutputInterface $output) use ($commandCallback, $passThrough) {
            // get passthru args
            $args = $input->getArguments();
            array_shift($args);
            if ($passThrough) {
                $args[key(array_slice($args, -1, 1, true))] = $passThrough;
            }
            $args[] = $input->getOptions();

            // TODO: Call any validate / pre-hooks registered for this command

            $status = 0;
            try {
                $result = call_user_func_array($commandCallback, $args);
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
        });
    }
}
