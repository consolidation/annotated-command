<?php
namespace Consolidation\AnnotatedCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;

/**
 * PassThroughArgsInput may be used in conjunction with
 * an ArgvInput to include pass-through args (anything after
 * a '--' option) to the application.
 *
 * @package Consolidation\AnnotatedCommand
 */
class PassThroughArgsInput implements InputInterface
{
    /**
     * @var array
     */
    protected $passThroughArgs;

    /**
     * @var InputInterface
     */
    protected $delegate;

    public function __construct($passThroughArgs, $delegate = null)
    {
        $this->passThroughArgs = $passThroughArgs;
        $this->delegate = $delegate;

        if (!$this->delegate) {
            $this->delegate = new ArgvInput();
        }
    }

    public function getPassThroughArgs()
    {
        return $this->passThroughArgs;
    }

    /**
     * {@inheritdoc}
     */
    public function getFirstArgument()
    {
        return $this->delegate->getFirstArgument();
    }

    /**
     * {@inheritdoc}
     */
    public function hasParameterOption($values, $onlyParams = false)
    {
        return $this->delegate->hasParameterOption($values, $onlyParams);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterOption($values, $default = false, $onlyParams = false)
    {
        return $this->delegate->getParameterOption($values, $default, $onlyParams);
    }

    /**
     * {@inheritdoc}
     */
    public function bind(InputDefinition $definition)
    {
        return $this->delegate->bind($definition);
    }

    /**
     * {@inheritdoc}
     */
    public function validate()
    {
        $this->delegate->validate();
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return $this->delegate->getArguments();
    }

    /**
     * {@inheritdoc}
     */
    public function getArgument($name)
    {
        return $this->delegate->getArgument($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setArgument($name, $value)
    {
        return $this->delegate->setArgument($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function hasArgument($name)
    {
        return $this->delegate->hasArgument($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->delegate->getOptions();
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($name)
    {
        return $this->delegate->getOption($name);
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($name, $value)
    {
        $this->delegate->setOption($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function hasOption($name)
    {
        return $this->delegate->hasOption($name);
    }

    /**
     * {@inheritdoc}
     */
    public function isInteractive()
    {
        return $this->delegate->isInteractive();
    }

    /**
     * {@inheritdoc}
     */
    public function setInteractive($interactive)
    {
        $this->delegate->setInteractive($interactive);
    }
}
