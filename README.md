# Consolidation\AnnotationCommand

Initialize Symfony Console commands from annotated command class methods.

## Component Status

In the process of factoring out of https://github.com/Codegyre/Robo

## Motivation

Symfony Console provides a set of classes that are widely used to implement command line tools. Increasingly, it is becoming popular to use annotations to describe the characteristics of the command (e.g. its arguments, options and so on) implemented by the annotated method.

Extant commandline tools that utilize this technique include:

- [Robo](https://github.com/codegyre/robo)
- [wp-cli](https://github.com/wp-cli/wp-cli)
- [Pantheon Terminus](https://github.com/pantheon-systems/terminus)

This library provides routines to produce the Symfony\Component\Console\Command\Command from all public methods defined in the provided class.

## Example Annotated Command Class
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
## API Usage
```
$myCommandClassInstance = new MyCommandClass();
$commandFactory = new AnnotationCommandFactory();
$commandList = $commandFactory->createCommandsFromClass($myCommandClassInstance);
foreach ($commandList as $command) {
    $application->add($command);
}
```
## Comparison to Existing Solutions

The existing solutions used their own hand-rolled regex-based parsers to process the contents of the DocBlock comments. consolidation/annotation-command uses the phpdocumentor/reflection-docblock project (which is itsle a regex-based parser) to interpret DocBlock contents. 

## Caution Regarding Dependency Versions

Note that phpunit requires phpspec/prophecy, which in turn requires phpdocumentor/reflection-docblock version 2.x.  This blocks consolidation/annotation-command from using the 3.x version of reflection-docblock. When prophecy updates to a newer version of reflection-docblock, then annotation-command will be forced to follow (or pin to an older version of phpunit). The internal classes of reflection-docblock are not exposed to users of consolidation/annotation-command, though, so this upgrade should not affect clients of this project.
