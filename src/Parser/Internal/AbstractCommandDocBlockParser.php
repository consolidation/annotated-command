<?php
namespace Consolidation\AnnotatedCommand\Parser\Internal;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Consolidation\AnnotatedCommand\Parser\DefaultsWithDescriptions;

/**
 * Given a class and method name, parse the annotations in the
 * DocBlock comment, and provide accessor methods for all of
 * the elements that are needed to create an annotated Command.
 */
abstract class AbstractCommandDocBlockParser
{
    /**
     * @var CommandInfo
     */
    protected $commandInfo;

    /**
     * @var \ReflectionMethod
     */
    protected $reflection;

    /**
     * @var array
     */
    protected $tagProcessors = [
        'command' => 'processCommandTag',
        'name' => 'processCommandTag',
        'arg' => 'processArgumentTag',
        'param' => 'processParamTag',
        'return' => 'processReturnTag',
        'option' => 'processOptionTag',
        'default' => 'processDefaultTag',
        'aliases' => 'processAliases',
        'usage' => 'processUsageTag',
        'description' => 'processAlternateDescriptionTag',
        'desc' => 'processAlternateDescriptionTag',
    ];

    public function __construct(CommandInfo $commandInfo, \ReflectionMethod $reflection)
    {
        $this->commandInfo = $commandInfo;
        $this->reflection = $reflection;
    }

    /**
     * Parse the docBlock comment for this command, and set the
     * fields of this class with the data thereby obtained.
     */
    abstract public function parse();

    /**
     * Save any tag that we do not explicitly recognize in the
     * 'otherAnnotations' map.
     */
    abstract protected function processGenericTag($tag);

    /**
     * Set the name of the command from a @command or @name annotation.
     */
    abstract protected function processCommandTag($tag);

    /**
     * The @description and @desc annotations may be used in
     * place of the synopsis (which we call 'description').
     * This is discouraged.
     *
     * @deprecated
     */
    abstract protected function processAlternateDescriptionTag($tag);

    /**
     * Store the data from a @arg annotation in our argument descriptions.
     */
    abstract protected function processArgumentTag($tag);

    /**
     * Store the data from a @param annotation in our argument descriptions.
     */
    abstract protected function processParamTag($tag);

    /**
     * Store the data from a @return annotation in our argument descriptions.
     */
    abstract protected function processReturnTag($tag);

    /**
     * Store the data from an @option annotation in our option descriptions.
     */
    abstract protected function processOptionTag($tag);

    /**
     * Store the data from a @default annotation in our argument or option store,
     * as appropriate.
     */
    abstract protected function processDefaultTag($tag);

    /**
     * Process the comma-separated list of aliases
     */
    abstract protected function processAliases($tag);

    /**
     * Store the data from a @usage annotation in our example usage list.
     */
    abstract protected function processUsageTag($tag);

    protected function interpretDefaultValue($defaultValue)
    {
        $defaults = [
            'null' => null,
            'true' => true,
            'false' => false,
            "''" => '',
            '[]' => [],
        ];
        foreach ($defaults as $defaultName => $defaultTypedValue) {
            if ($defaultValue == $defaultName) {
                return $defaultTypedValue;
            }
        }
        return $defaultValue;
    }

    /**
     * Given a docblock description in the form "$variable description",
     * return the variable name and description via the 'match' parameter.
     */
    protected function pregMatchNameAndDescription($source, &$match)
    {
        $nameRegEx = '\\$(?P<name>[^ \t]+)[ \t]+';
        $descriptionRegEx = '(?P<description>.*)';
        $optionRegEx = "/{$nameRegEx}{$descriptionRegEx}/s";

        return preg_match($optionRegEx, $source, $match);
    }

    /**
     * Given a list that might be 'a b c' or 'a, b, c' or 'a,b,c',
     * convert the data into the last of these forms.
     */
    protected static function convertListToCommaSeparated($text)
    {
        return preg_replace('#[ \t\n\r,]+#', ',', $text);
    }

    /**
     * Take a multiline description and convert it into a single
     * long unbroken line.
     */
    protected static function removeLineBreaks($text)
    {
        return trim(preg_replace('#[ \t\n\r]+#', ' ', $text));
    }
}
