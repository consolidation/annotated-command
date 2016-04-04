<?php
namespace Consolidation\AnnotationCommand;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Manage named callback hooks
 */
class HookManager
{
    protected $hooks = [];

    const ARGUMENT_VALIDATOR = 'validate';
    const ALTER_RESULT = 'alter';
    const STATUS_DETERMINER = 'status';
    const EXTRACT_OUTPUT = 'extract';

    public function __construct()
    {
    }

    /**
     * Add a hook
     *
     * @param string   $name     The name of the command to hook
     * @param string   $hook     The name of the hook to add
     * @param callable $callback The callback function to call
     */
    public function add($name, $hook, callable $callback)
    {
        $this->hooks[$name][$hook][] = $callback;
    }

    /**
     * Get a set of hooks with the provided name(s).
     *
     * @param string|array $names The name of the function being hooked.
     * @param string $hook The specific hook name (e.g. alter)
     *
     * @return callable[]
     */
    public function get($names, $hook)
    {
        $hooks = [];
        foreach ((array)$names as $name) {
            $hooks = array_merge($hooks, $this->getHook($name, $hook));
        }
        return $hooks;
    }

    public function validateArguments($names, $args)
    {
        $validators = $this->getValidators($names);
        foreach ($validators as $validator) {
            $validated = $this->callValidator($validator, $args);
            if (is_object($validated)) {
                return $validated;
            }
            if (is_array($validated)) {
                $args = $validated;
            }
        }
        return $args;
    }

    /**
     * Allow all of the post-process hooks to run
     */
    public function alterResult($names, $result, $args)
    {
        // Process result and decide what to do with it.
        // Allow client to add transformation / interpretation
        // callbacks.
        $alterers = $this->getAlterResultHooks($names);
        foreach ($alterers as $alterer) {
            $result = $this->callAlterer($alterer, $result, $args);
        }

        return $result;
    }

    /**
     * Call all status determiners, and see if any of them
     * know how to convert to a status code.
     */
    public function determineStatusCode($names, $result)
    {
        // If the result (post-processing) is an object that
        // implements ExitCodeInterface, then we will ask it
        // to give us the status code.
        if ($result instanceof ExitCodeInterface) {
            return $result->getExitCode();
        }

        // If the result does not implement ExitCodeInterface,
        // then we'll see if there is a determiner that can
        // extract a status code from the result.
        $determiners = $this->getStatusDeterminers($names);
        foreach ($determiners as $determiner) {
            $status = $this->callDeterminer($determiner, $result);
            if (isset($status)) {
                return $status;
            }
        }
    }

    /**
     * Convert the result object to printable output in
     * structured form.
     */
    public function extractOutput($names, $result)
    {
        if ($result instanceof OutputDataInterface) {
            return $result->getOutputData();
        }

        $extractors = $this->getOutputExtractors($names);
        foreach ($extractors as $extractor) {
            $structuredOutput = $this->callExtractor($extractor, $result);
            if (isset($structuredOutput)) {
                return $structuredOutput;
            }
        }

        return $result;
    }

    protected function getValidators($names)
    {
        return $this->getHooks($names, self::ARGUMENT_VALIDATOR);
    }

    protected function getStatusDeterminers($names)
    {
        return $this->getHooks($names, self::STATUS_DETERMINER);
    }

    protected function getAlterResultHooks($names)
    {
        return $this->getHooks($names, self::ALTER_RESULT);
    }

    protected function getOutputExtractors($names)
    {
        return $this->getHooks($names, self::EXTRACT_OUTPUT);
    }

    /**
     * Get a set of hooks with the provided name(s). Include the
     * pre- and post- hooks, and also include the global hooks ('*')
     * in addition to the named hooks provided.
     *
     * @param string|array $names The name of the function being hooked.
     * @param string $hook The specific hook name (e.g. alter)
     *
     * @return callable[]
     */
    protected function getHooks($names, $hook)
    {
        $names = (array)$names;
        $names[] = '*';
        return array_merge(
            $this->get($names, "pre-$hook"),
            $this->get($names, $hook),
            $this->get($names, "post-$hook")
        );
    }

    /**
     * Get a single named hook.
     *
     * @param string $name The name of the hooked method
     * @param string $hook The specific hook name (e.g. alter)
     *
     * @return callable[]
     */
    protected function getHook($name, $hook)
    {
        if (isset($this->hooks[$name][$hook])) {
            return $this->hooks[$name][$hook];
        }
        return [];
    }

    protected function callValidator($validator, $args)
    {
        if ($validator instanceof ValidatorInterface) {
            return $validator->validate($args);
        }
        if (is_callable($validator)) {
            return $validator($args);
        }
    }

    protected function callAlterer($alterer, $result, $args)
    {
        if ($alterer instanceof AlterResultInterface) {
            return $alterer->alter($result, $args);
        }
        if (is_callable($alterer)) {
            return $alterer($result, $args);
        }
        return $result;
    }

    protected function callDeterminer($determiner, $result)
    {
        if ($determiner instanceof StatusDeterminerInterface) {
            return $determiner->determineStatusCode($result);
        }
        if (is_callable($determiner)) {
            return $determiner($result);
        }
    }

    protected function callExtractor($extractor, $result)
    {
        if ($extractor instanceof ExtractOutputInterface) {
            return $extractor->extractOutput($result);
        }
        if (is_callable($extractor)) {
            return $extractor($result);
        }
    }
}
