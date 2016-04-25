# Consolidation\AnnotatedCommand

Initialize Symfony Console commands from annotated command class methods.

[![Travis CI](https://travis-ci.org/consolidation-org/annotated-command.svg?branch=master)](https://travis-ci.org/consolidation-org/annotated-command) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/consolidation-org/annotated-command/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/consolidation-org/annotated-command/?branch=master) [![Coverage Status](https://coveralls.io/repos/github/consolidation-org/annotated-command/badge.svg?branch=master)](https://coveralls.io/github/consolidation-org/annotated-command?branch=master) [![License](https://poser.pugx.org/consolidation/annotated-command/license)](https://packagist.org/packages/consolidation/annotated-command)

## Component Status

Currently in use in [Robo](https://github.com/Codegyre/Robo).

## Motivation

Symfony Console provides a set of classes that are widely used to implement command line tools. Increasingly, it is becoming popular to use annotations to describe the characteristics of the command (e.g. its arguments, options and so on) implemented by the annotated method.

Extant commandline tools that utilize this technique include:

- [Robo](https://github.com/codegyre/robo)
- [wp-cli](https://github.com/wp-cli/wp-cli)
- [Pantheon Terminus](https://github.com/pantheon-systems/terminus)

This library provides routines to produce the Symfony\Component\Console\Command\Command from all public methods defined in the provided class.

## Example Annotated Command Class
The public methods of the command class define its commands, and the parameters of each method define its arguments and options. The command options, if any, are declared as the last parameter of the methods. The options will be passed in as an associative array; the default options of the last parameter should list the options recognized by the command.

The rest of the parameters are arguments. Parameters with a default value are optional; those without a default value are required.
```
class MyCommandClass
{
    /**
     * This is the my:cat command
     *
     * This command will concatinate two parameters. If the --flip flag
     * is provided, then the result is the concatination of two and one.
     *
     * @param integer $one The first parameter.
     * @param integer $two The other parameter.
     * @option $flip Whether or not the second parameter should come first in the result.
     * @aliases c
     * @usage bet alpha --flip
     *   Concatinate "alpha" and "bet".
     */
    public function myCat($one, $two, $options = ['flip' => false])
    {
        if ($options['flip']) {
            return "{$two}{$one}";
        }
        return "{$one}{$two}";
    }
}
``` 
## Hooks

Commandfiles may provide hooks in addition to commands. A commandfile method that contains a @hook annotation is registered as a hook instead of a command.  The format of the hook annotation is:
```
@hook type commandname
```
The commandname may be the command's primary name (e.g. `my:command`), it's method name (e.g. myCommand) or any of its aliases.

There are five types of hooks supported:

- Validate
- Process
- Alter
- Status
- Extract

Each of these also have "pre" and "post" varieties, to give more flexibility vis-a-vis hook ordering (and for consistency). Note that many validate, process and alter hooks may run, but the first status or extract hook that successfully returns a result will halt processing of further hooks of the same type.

### Validate Hook

Validation hooks examine the arguments and options passed to a command. A validation hook may take one of several actions:

- Do nothing. This indicates that validation succeeded.
- Return an array. This alters the arguments that will be used during command execution.
- Return a CommandError. Validation fails, and execution stops. The CommandError contains a status result code and a message, which is printed.
- Throw an exception. The exception is converted into a CommandError.

Any number of validation hooks may run, but if any fails, then execution of the command stops.

### Process Hook

Some commands will return an object that generates a result, rather than returning a result object directly. When this is supported, the operation should be executed during the process hook.

An example of this is implemented in Robo; if a Robo command returns a TaskInterface, then a Robo process hook will execute the task and return the result. This allows a pre-process hook to alter the task, e.g. by adding more operations to a task collection.

### Alter Hook

An alter hook changes the result object. Alter hooks should only operate on result objects of a type they explicitly recognize. They may return an object of the same type, or they may convert the object to some other type.

If something goes wrong, and the alter hooks wishes to force the command to fail, then it may either return a CommandError object, or throw an exception.

### Status Hook

The result object returned by a command may be a compound object that contains multiple bits of information about the command result.  If the result object implements ExitCodeInterface, then the `getExitCode()` method of the result object is called to determine what the status result code for the command should be. If ExitCodeInterface is not implemented, then all of the status hooks attached to this command are executed; the first one that successfully returns a result will stop further execution of status hooks, and the result it returned will be used as the status result code for this operation.

If no status hook returns any result, then success is presumed.

### Extract Hook

The result object returned by a command may be a compound object that contains multiple bits of information about the command result.  If the result object implements OutputDataInterface, then the `getOutputData()` method of the result object is called to determine what information should be displayed to the user as a result of the command's execution. If ExitCodeInterface is not implemented, then all of the extract hooks attached to this command are executed; the first one that successfully returns output data will stop further execution of extract hooks.

If no extract hook returns any data, then the result object itself is printed if it is a string; otherwise, no output is emitted (other than any produced by the command itself).

## Output

If a command method returns an integer, it is used as the command exit status code. If the command method returns a string, it is printed.

If the [Consolidation/OutputFormatters](https://github.com/consolidation-org/output-formatters) project is used, then users may specify a --format option to select the formatter to use to transform the output from whatever form the command provides to a string.  To make this work, the application must provide a formatter to the AnnotatedCommandFactory.  See [API Usage](#api-usage) below.

## Logging

The Annotated-Command project is completely agnostic to logging. If a command wishes to log progress, then the CommandFile class should implement LoggerAwareInterface, and the Commandline tool should inject a logger for its use via the LoggerAwareTrait `setLogger()` method.  Using [Robo](https://github.com/codegyre/robo) is recommended.

## Access to Symfony Objects

If you want to use annotations, but still want access to the Symfony Command, e.g. to get a reference to the helpers in order to call some legacy code, you may create an ordinary Symfony Command that extends \Consolidation\AnnotatedCommand\AnnotatedCommand, which is a \Symfony\Component\Console\Command\Command. Omit the configure method, and place your annotations on the `execute()` method.

It is also possible to add InputInterface or OutputInterface parameters to any annotated method of a command file.

## API Usage

To use annotated commands in an application, pass an instance of your command class in to AnnotatedCommandFactory::createCommandsFromClass(). The result will be a list of Commands that may be added to your application.
```
$myCommandClassInstance = new MyCommandClass();
$commandFactory = new AnnotatedCommandFactory();
$commandFactory->commandProcessor()->setFormatterManager(new FormatterManager());
$commandList = $commandFactory->createCommandsFromClass($myCommandClassInstance);
foreach ($commandList as $command) {
    $application->add($command);
}
```
You may have more than one command class, if you wish. If so, simply call AnnotatedCommandFactory::createCommandsFromClass() multiple times.

Note that the `setFormatterManager()` operation is optional; omit this if not using [Consolidation/OutputFormatters](https://github.com/consolidation-org/output-formatters).

A discovery class, CommandFileDiscovery, is also provided to help find command files on the filesystem. Usage is as follows:
```
$discovery = new CommandFileDiscovery();
$myCommandFiles = $discovery->discover($path, '\Drupal');
foreach ($myCommandFiles as $myCommandClass) {
    $myCommandClassInstance = new $myCommandClass();
    // ... as above
}
```
For a discussion on command file naming conventions and search locations, see https://github.com/consolidation-org/annotated-command/issues/12.

If different namespaces are used at different command file paths, change the call to discover as follows:
```
$myCommandFiles = $discovery->discover(['\Ns1' => $path1, '\Ns2' => $path2]);
```
As a shortcut for the above, the method `discoverNamespaced()` will take the last directory name of each path, and append it to the base namespace provided. This matches the conventions used by Drupal modules, for example.

## Comparison to Existing Solutions

The existing solutions used their own hand-rolled regex-based parsers to process the contents of the DocBlock comments. consolidation/annotated-command uses the phpdocumentor/reflection-docblock project (which is itsle a regex-based parser) to interpret DocBlock contents. 

## Caution Regarding Dependency Versions

Note that phpunit requires phpspec/prophecy, which in turn requires phpdocumentor/reflection-docblock version 2.x.  This blocks consolidation/annotated-command from using the 3.x version of reflection-docblock. When prophecy updates to a newer version of reflection-docblock, then annotated-command will be forced to follow (or pin to an older version of phpunit). The internal classes of reflection-docblock are not exposed to users of consolidation/annotated-command, though, so this upgrade should not affect clients of this project.
