<?php
namespace Consolidation\AnnotatedCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CommandData
{
    /** var AnnotationData */
    protected $annotatedData;
    /** var InputInterface */
    protected $input;
    /** var OutputInterface */
    protected $output;
    /** var boolean */
    protected $usesInputInterface;
    /** var boolean */
    protected $usesOutputInterface;

    public function __construct(
        AnnotationData $annotationData,
        InputInterface $input,
        OutputInterface $output,
        $usesInputInterface,
        $usesOutputInterface
    ) {
        $this->annotationData = $annotationData;
        $this->input = $input;
        $this->output = $output;
        $this->usesInputInterface = $usesInputInterface;
        $this->usesOutputInterface = $usesOutputInterface;
    }

    public function annotationData()
    {
        return $this->annotationData;
    }

    public function input()
    {
        return $this->input;
    }

    public function output()
    {
        return $this->output;
    }

    public function arguments()
    {
        return $this->input->getArguments();
    }

    public function options()
    {
        return $this->input->getOptions();
    }

    public function getArgsWithoutAppName()
    {
        $args = $this->arguments();

        // When called via the Application, the first argument
        // will be the command name. The Application alters the
        // input definition to match, adding a 'command' argument
        // to the beginning.
        array_shift($args);
        return $args;
    }

    public function getArgsAndOptions()
    {
        // Get passthrough args, and add the options on the end.
        $args = $this->getArgsWithoutAppName();
        $args['options'] = $this->options();
        return $args;
    }
}
