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
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $argumentDescriptions = [];

    /**
     * @var array
     */
    protected $optionDescriptions = [];

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

    public function __construct($classNameOrInstance, $methodName)
    {
        $this->reflection = new \ReflectionMethod($classNameOrInstance, $methodName);
        $this->methodName = $methodName;
        // Set up a default name for the command from the method name.
        // This can be overridden via @command or @name annotations.
        $this->setDefaultName();
        $this->options = $this->determineOptionsFromParameters();
        $this->arguments = $this->determineAgumentClassifications();
    }

    public function getMethodName()
    {
        return $this->methodName;
    }

    public function getParameters()
    {
        return $this->reflection->getParameters();
    }

    /**
     * Get the synopsis of the command (~first line).
     */
    public function getDescription()
    {
        $this->parseDocBlock();
        return $this->description;
    }

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

    public function setHelp($help)
    {
        $this->help = $help;
    }

    public function getAliases()
    {
        $this->parseDocBlock();
        return $this->aliases;
    }

    public function setAliases($aliases)
    {
        if (is_string($aliases)) {
            $aliases = explode(',', static::convertListToCommaSeparated($aliases));
        }
        $this->aliases = array_filter($aliases);
    }

    public function getExampleUsages()
    {
        $this->parseDocBlock();
        return $this->exampleUsage;
    }

    public function getName()
    {
        $this->parseDocBlock();
        return $this->name;
    }

    public function setDefaultName()
    {
        $this->name = $this->convertName($this->reflection->name);
    }

    public function setName($name)
    {
        $this->name = $name;
    }

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

    public function getArguments()
    {
        return $this->arguments;
    }

    public function hasArgument($name)
    {
        return array_key_exists($name, $this->arguments);
    }

    public function setArgumentDefaultValue($name, $defaultValue)
    {
        $this->arguments[$name] = $defaultValue;
    }

    public function addArgument($name, $description, $defaultValue = null)
    {
        if (!$this->hasArgument($name) || isset($defaultValue)) {
            $this->arguments[$name] = $defaultValue;
        }
        unset($this->argumentDescriptions[$name]);
        if (isset($description)) {
            $this->argumentDescriptions[$name] = $description;
        }
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

    public function getOptions()
    {
        return $this->options;
    }

    public function hasOption($name)
    {
        return array_key_exists($name, $this->options);
    }

    public function setOptionDefaultValue($name, $defaultValue)
    {
        $this->options[$name] = $defaultValue;
    }

    public function addOption($name, $description, $defaultValue = false)
    {
        if (!$this->hasOption($name) || $defaultValue) {
            $this->options[$name] = $defaultValue;
        }
        unset($this->optionDescriptions[$name]);
        if (isset($description)) {
            $this->optionDescriptions[$name] = $description;
        }
    }

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

    public function getArgumentDescription($name)
    {
        $this->parseDocBlock();
        if (array_key_exists($name, $this->argumentDescriptions)) {
            return $this->argumentDescriptions[$name];
        }

        return '';
    }

    public function getOptionDescription($name)
    {
        $this->parseDocBlock();
        if (array_key_exists($name, $this->optionDescriptions)) {
            return $this->optionDescriptions[$name];
        }

        return '';
    }

    protected function isAssoc($arr)
    {
        if (!is_array($arr)) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function getAnnotations()
    {
        $this->parseDocBlock();
        return $this->otherAnnotations;
    }

    public function getAnnotation($annotation)
    {
        // hasAnnotation parses the docblock
        if (!$this->hasAnnotation($annotation)) {
            return null;
        }
        return $this->otherAnnotations[$annotation];
    }

    public function hasAnnotation($annotation)
    {
        $this->parseDocBlock();
        return array_key_exists($annotation, $this->otherAnnotations);
    }

    protected function convertName($camel)
    {
        $splitter="-";
        $camel=preg_replace('/(?!^)[[:upper:]][[:lower:]]/', '$0', preg_replace('/(?!^)[[:upper:]]+/', $splitter.'$0', $camel));
        $camel = preg_replace("/$splitter/", ':', $camel, 1);
        return strtolower($camel);
    }

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
        if (isset($this->options[$optionName])) {
            return $optionName;
        }
        // Check to see if we can find the option name in an existing option,
        // e.g. if the options array has 'silent|s' => false, and the annotation
        // is @silent.
        foreach ($this->options as $name => $default) {
            if (in_array($optionName, explode('|', $name))) {
                return $name;
            }
        }
        // Check the other direction: if the annotation contains @silent|s
        // and the options array has 'silent|s'.
        $checkMatching = explode('|', $optionName);
        if (count($checkMatching) > 1) {
            foreach ($checkMatching as $checkName) {
                if (isset($this->options[$checkName])) {
                    $this->options[$optionName] = $this->options[$checkName];
                    unset($this->options[$checkName]);
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
