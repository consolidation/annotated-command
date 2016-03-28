# Consolidation\AnnotationCommand

Initialize Symfony Console commands from annotated command class methods.

[![Travis CI](https://travis-ci.org/consolidation-org/annotation-command.svg?branch=master)](https://travis-ci.org/consolidation-org/annotation-command) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/consolidation-org/annotation-command/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/consolidation-org/annotation-command/?branch=master) [![License](https://poser.pugx.org/consolidation/annotation-command/license)](https://packagist.org/packages/consolidation/annotation-command)

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
If a command method returns an integer, it is used as the command exit status code. If the command method returns a string, it is printed.
## Access to Symfony Command
If you want access to the Symfony Command, e.g. to get a reference to the helpers in order to call some legacy code, simply typehint the first parameter of your command method as a \Symfony\Component\Console\Command\Command, and the command object will be passed in. The other parameters define your commands arguments and options, as usual.
```
class MyCommandClass
{
    public function testCommand(Command $command, $message)
    {
        $formatter = $command->getHelperSet()->get('formatter');
        return $formatter->formatSection('test', $message);
    }
}
```
## API Usage
To use annotated commands in an application, pass an instance of your command class in to AnnotationCommandFactory::createCommandsFromClass(). The result will be a list of Commands that may be added to your application.
```
$myCommandClassInstance = new MyCommandClass();
$commandFactory = new AnnotationCommandFactory();
$commandList = $commandFactory->createCommandsFromClass($myCommandClassInstance);
foreach ($commandList as $command) {
    $application->add($command);
}
```
You may have more than one command class, if you wish. If so, simply call AnnotationCommandFactory::createCommandsFromClass() multiple times.
## Comparison to Existing Solutions

The existing solutions used their own hand-rolled regex-based parsers to process the contents of the DocBlock comments. consolidation/annotation-command uses the phpdocumentor/reflection-docblock project (which is itsle a regex-based parser) to interpret DocBlock contents. 

## Caution Regarding Dependency Versions

Note that phpunit requires phpspec/prophecy, which in turn requires phpdocumentor/reflection-docblock version 2.x.  This blocks consolidation/annotation-command from using the 3.x version of reflection-docblock. When prophecy updates to a newer version of reflection-docblock, then annotation-command will be forced to follow (or pin to an older version of phpunit). The internal classes of reflection-docblock are not exposed to users of consolidation/annotation-command, though, so this upgrade should not affect clients of this project.
