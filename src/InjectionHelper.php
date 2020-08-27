<?php
namespace Consolidation\AnnotatedCommand;

use Consolidation\AnnotatedCommand\Output\OutputAwareInterface;
use Symfony\Component\Console\Input\InputAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InjectionHelper
{
    /**
     * Inject $input and $output into the command instance if it is set up to receive them.
     *
     * @param Callable|object $callback
     * @param OutputInterface $output
     */
    public static function injectIntoCallbackObject($callback, InputInterface $input, OutputInterface $output = null)
    {
        $callbackObject = static::recoverCallbackObject($callback);
        if (!$callbackObject) {
            return;
        }

        if ($callbackObject instanceof InputAwareInterface) {
            $callbackObject->setInput($input);
        }
        if (isset($output) && $callbackObject instanceof OutputAwareInterface) {
            $callbackObject->setOutput($output);
        }
    }

    /**
     * If the command callback is a method of an object, return the object.
     *
     * @param Callable|object $callback
     * @return object|bool
     */
    protected static function recoverCallbackObject($callback)
    {
        if (is_object($callback)) {
            return $callback;
        }

        if (!is_array($callback)) {
            return false;
        }

        if (!is_object($callback[0])) {
            return false;
        }

        return $callback[0];
    }
}
