<?php
namespace Consolidation\AnnotatedCommand;

/**
 * Given a class and method name, parse the annotations in the
 * DocBlock comment, and provide accessor methods for all of
 * the elements that are needed to create a Symfony Console Command.
 */
class CommandInfo
{
    /**
     * @var \ReflectionMethod
     */
    protected $reflection;

    /**
     * @var boolean
     * @var string
    */
    protected $docBlockIsParsed;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var string
     */
    protected $help = '';

    /**
     * @var DefaultsWithDescriptions
     */
    protected $options = [];

    /**
     * @var DefaultsWithDescriptions
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $exampleUsage = [];

    /**
     * @var array
     */
    protected $otherAnnotations = [];

    /**
     * @var array
     */
    protected $aliases = [];

    /**
     * @var string
     */
    protected $methodName;

    /**
     * Create a new CommandInfo class for a particular method of a class.
     *
     * @param string|mixed $classNameOrInstance The name of a class, or an
     *   instance of it.
     * @param string $methodName The name of the method to get info about.
     */
    public function __construct($classNameOrInstance, $methodName)
    {
        $this->reflection = new \ReflectionMethod($classNameOrInstance, $methodName);
        $this->methodName = $methodName;
        // Set up a default name for the command from the method name.
        // This can be overridden via @command or @name annotations.
        $this->name = $this->convertName($this->reflection->name);
        $this->options = new DefaultsWithDescriptions($this->determineOptionsFromParameters(), false);
        $this->arguments = new DefaultsWithDescriptions($this->determineAgumentClassifications());
    }

    /**
     * Recover the method name provided to the constructor.
     *
     * @return string
     */
    public function getMethodName()
    {
        return $this->methodName;
    }

    /**
     * Return the list of refleaction parameters.
     *
     * @return ReflectionParameter[]
     */
    public function getParameters()
    {
        return $this->reflection->getParameters();
    }

    /**
     * Get the synopsis of the command (~first line).
     *
     * @return string
     */
    public function getDescription()
    {
        $this->parseDocBlock();
        return $this->description;
    }

    /**
     * Set the command description.
     *
     * @param string $description The description to set.
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get the help text of the command (the description)
     */
    public function getHelp()
    {
        $this->parseDocBlock();
        return $this->help;
    }
    /**
     * Set the help text for this command.
     *
     * @param string $help The help text.
     */
    public function setHelp($help)
    {
        $this->help = $help;
    }

    /**
     * Return the list of aliases for this command.
     * @return string[]
     */
    public function getAliases()
    {
        $this->parseDocBlock();
        return $this->aliases;
    }

    /**
     * Set aliases that can be used in place of the command's primary name.
     *
     * @param string|string[] $aliases
     */
    public function setAliases($aliases)
    {
        if (is_string($aliases)) {
            $aliases = explode(',', static::convertListToCommaSeparated($aliases));
        }
        $this->aliases = array_filter($aliases);
    }

    /**
     * Return the examples for this command. This is @usage instead of
     * @example because the later is defined by the phpdoc standard to
     * be example method calls.
     *
     * @return string[]
     */
    public function getExampleUsages()
    {
        $this->parseDocBlock();
        return $this->exampleUsage;
    }

    /**
     * Return the primary name for this command.
     *
     * @return string
     */
    public function getName()
    {
        $this->parseDocBlock();
        return $this->name;
    }

    /**
     * Set the primary name for this command.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Examine the parameters of the method for this command, and
     * build a list of commandline arguements for them.
     *
     * @return array
     */
    protected function determineAgumentClassifications()
    {
        $args = [];
        $params = $this->reflection->getParameters();
        if (!empty($this->determineOptionsFromParameters())) {
            array_pop($params);
        }
        foreach ($params as $param) {
            $defaultValue = $this->getArgumentClassification($param);
            if ($defaultValue !== false) {
                $args[$param->name] = $defaultValue;
            }
        }
        return $args;
    }

    /**
     * Return the commandline arguments for this command. The key
     * contains the name of the argument, and the value contains its
     * default value. Required commands have a 'null' value.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments->getValues();
    }

    /**
     * Check to see if an argument with the specified name exits.
     *
     * @param string $name Argument to test for.
     * @return boolean
     */
    public function hasArgument($name)
    {
        return $this->arguments->exists($name);
    }

    /**
     * Set the default value for an argument. A default value of 'null'
     * indicates that the argument is required.
     *
     * @param string $name Name of argument to modify.
     * @param string $defaultValue New default value for that argument.
     */
    public function setArgumentDefaultValue($name, $defaultValue)
    {
        $this->arguments->setDefaultValue($name, $defaultValue);
    }

    /**
     * Add another argument to this command.
     *
     * @param string $name Name of the argument.
     * @param string $description Help text for the argument.
     * @param string $defaultValue The default value for the argument.
     */
    public function addArgument($name, $description, $defaultValue = null)
    {
        $this->arguments->add($name, $description, $defaultValue);
    }

    /**
     * Examine the provided parameter, and determine whether it
     * is a parameter that will be filled in with a positional
     * commandline argument.
     *
     * @return false|null|string|array
     */
    protected function getArgumentClassification($param)
    {
        $defaultValue = null;
        if ($param->isDefaultValueAvailable()) {
            $defaultValue = $param->getDefaultValue();
            if ($this->isAssoc($defaultValue)) {
                return false;
            }
        }
        if ($param->isArray()) {
            return [];
        }
        // Commandline arguments must be strings, so ignore
        // any parameter that is typehinted to anything else.
        if (($param->getClass() != null) && ($param->getClass() != 'string')) {
            return false;
        }
        return $defaultValue;
    }

    /**
     * Return the options for is command. The key is the options name,
     * and the value is its default value.
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options->getValues();
    }

    /**
     * Check to see if the specified option exists.
     *
     * @param string $name Name of the option to check.
     * @return boolean
     */
    public function hasOption($name)
    {
        return $this->options->exists($name);
    }

    /**
     * Change the default value for an option.
     *
     * @param string $name Option name.
     * @param string $defaultValue Option default value.
     */
    public function setOptionDefaultValue($name, $defaultValue)
    {
        $this->options->setDefaultValue($name, $defaultValue);
    }

    /**
     * Add another option to this command.
     *
     * @param string $name Option name.
     * @param string $description Option description.
     * @param string $defaultValue Option default value.
     */
    public function addOption($name, $description, $defaultValue = null)
    {
        $this->options->add($name, $description, $defaultValue);
    }

    /**
     * Examine the parameters of the method for this command, and determine
     * the disposition of the options from them.
     *
     * @return array
     */
    public function determineOptionsFromParameters()
    {
        $params = $this->reflection->getParameters();
        if (empty($params)) {
            return [];
        }
        $param = end($params);
        if (!$param->isDefaultValueAvailable()) {
            return [];
        }
        if (!$this->isAssoc($param->getDefaultValue())) {
            return [];
        }
        return $param->getDefaultValue();
    }

    /**
     * Get the description of one argument.
     *
     * @param string $name The name of the argument.
     * @return string
     */
    public function getArgumentDescription($name)
    {
        $this->parseDocBlock();
        return $this->arguments->getDescription($name);
    }

    /**
     * Get the description of one argument.
     *
     * @param string $name The name of the option.
     * @return string
     */
    public function getOptionDescription($name)
    {
        $this->parseDocBlock();
        return $this->options->getDescription($name);
    }

    /**
     * Helper; determine if an array is associative or not. An array
     * is not associative if its keys are numeric, and numbered sequentially
     * from zero. All other arrays are considered to be associative.
     *
     * @param arrau $arr The array
     * @return boolean
     */
    protected function isAssoc($arr)
    {
        if (!is_array($arr)) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Get any annotations included in the docblock comment for the
     * implementation method of this command that are not already
     * handled by the primary methods of this class.
     *
     * @return array
     */
    public function getAnnotations()
    {
        $this->parseDocBlock();
        return $this->otherAnnotations;
    }

    /**
     * Return a specific named annotation for this command.
     *
     * @param string $annotation The name of the annotation.
     * @return string
     */
    public function getAnnotation($annotation)
    {
        // hasAnnotation parses the docblock
        if (!$this->hasAnnotation($annotation)) {
            return null;
        }
        return $this->otherAnnotations[$annotation];
    }

    /**
     * Check to see if the specified annotation exists for this command.
     *
     * @param string $annotation The name of the annotation.
     * @return boolean
     */
    public function hasAnnotation($annotation)
    {
        $this->parseDocBlock();
        return array_key_exists($annotation, $this->otherAnnotations);
    }

    /**
     * Convert from a method name to the corresponding command name. A
     * method 'fooBar' will become 'foo:bar', and 'fooBarBazBoz' will
     * become 'foo:bar-baz-boz'.
     *
     * @param type $camel method name.
     * @return string
     */
    protected function convertName($camel)
    {
        $splitter="-";
        $camel=preg_replace('/(?!^)[[:upper:]][[:lower:]]/', '$0', preg_replace('/(?!^)[[:upper:]]+/', $splitter.'$0', $camel));
        $camel = preg_replace("/$splitter/", ':', $camel, 1);
        return strtolower($camel);
    }

    /**
     * Add an example usage for this command.
     *
     * @param string $usage An example of the command, including the command
     *   name and all of its example arguments and options.
     * @param string $description An explanation of what the example does.
     */
    public function setExampleUsage($usage, $description)
    {
        $this->exampleUsage[$usage] = $description;
    }

    /**
     * Parse the docBlock comment for this command, and set the
     * fields of this class with the data thereby obtained.
     */
    protected function parseDocBlock()
    {
        if (!$this->docBlockIsParsed) {
            $docblock = $this->reflection->getDocComment();
            $parser = new CommandDocBlockParser($this);
            $parser->parse($docblock);
            $this->docBlockIsParsed = true;
        }
    }

    /**
     * Save any tag that we do not explicitly recognize in the
     * 'otherAnnotations' map.
     */
    public function addOtherAnnotation($name, $content)
    {
        $this->otherAnnotations[$name] = $content;
    }

    /**
     * An option might have a name such as 'silent|s'. In this
     * instance, we will allow the @option or @default tag to
     * reference the option only by name (e.g. 'silent' or 's'
     * instead of 'silent|s').
     */
    public function findMatchingOption($optionName)
    {
        // Exit fast if there's an exact match
        if ($this->options->exists($optionName)) {
            return $optionName;
        }
        // Check to see if we can find the option name in an existing option,
        // e.g. if the options array has 'silent|s' => false, and the annotation
        // is @silent.
        foreach ($this->options->getValues() as $name => $default) {
            if (in_array($optionName, explode('|', $name))) {
                return $name;
            }
        }
        // Check the other direction: if the annotation contains @silent|s
        // and the options array has 'silent|s'.
        $checkMatching = explode('|', $optionName);
        if (count($checkMatching) > 1) {
            foreach ($checkMatching as $checkName) {
                if ($this->options->exists($checkName)) {
                    $this->options->rename($checkName, $optionName);
                    return $optionName;
                }
            }
        }
        return $optionName;
    }

    /**
     * Given a list that might be 'a b c' or 'a, b, c' or 'a,b,c',
     * convert the data into the last of these forms.
     */
    protected static function convertListToCommaSeparated($text)
    {
        return preg_replace('#[ \t\n\r,]+#', ',', $text);
    }
}
