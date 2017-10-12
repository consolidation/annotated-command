<?php
namespace Consolidation\AnnotatedCommand\Parser\Internal;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Consolidation\AnnotatedCommand\Parser\DefaultsWithDescriptions;

/**
 * Given a class and method name, parse the annotations in the
 * DocBlock comment, and provide accessor methods for all of
 * the elements that are needed to create an annotated Command.
 */
class BespokeDocBlockParser extends AbstractCommandDocBlockParser
{
    /**
     * Parse the docBlock comment for this command, and set the
     * fields of this class with the data thereby obtained.
     */
    public function parse()
    {
        $doc = $this->reflection->getDocComment();
        $this->parseDocBlock($doc);
    }

    /**
     * Save any tag that we do not explicitly recognize in the
     * 'otherAnnotations' map.
     */
    protected function processGenericTag($tag)
    {
        $this->commandInfo->addAnnotation($tag->getTag(), $tag->getContent());
    }

    /**
     * Set the name of the command from a @command or @name annotation.
     */
    protected function processCommandTag($tag)
    {
        if (!$tag->hasWordAndDescription($matches)) {
            throw new \Exception('Could not determine command name from tag ' . (string)$tag);
        }
        $commandName = $matches['word'];
        $this->commandInfo->setName($commandName);
        // We also store the name in the 'other annotations' so that is is
        // possible to determine if the method had a @command annotation.
        $this->commandInfo->addAnnotation($tag->getTag(), $commandName);
    }

    /**
     * The @description and @desc annotations may be used in
     * place of the synopsis (which we call 'description').
     * This is discouraged.
     *
     * @deprecated
     */
    protected function processAlternateDescriptionTag($tag)
    {
        $this->commandInfo->setDescription($tag->getContent());
    }

    /**
     * Store the data from a @arg annotation in our argument descriptions.
     */
    protected function processArgumentTag($tag)
    {
        if (!$this->pregMatchNameAndDescription((string)$tag->getContent(), $match)) {
            return;
        }
        if ($match['name'] == $this->optionParamName()) {
            return;
        }
        $this->addOptionOrArgumentTag($tag, $this->commandInfo->arguments(), $match);
    }

    /**
     * Store the data from an @option annotation in our option descriptions.
     */
    protected function processOptionTag($tag)
    {
        if (!$this->pregMatchOptionNameAndDescription((string)$tag->getContent(), $match)) {
            return;
        }
        $this->addOptionOrArgumentTag($tag, $this->commandInfo->options(), $match);
    }

    protected function addOptionOrArgumentTag($tag, DefaultsWithDescriptions $set, $nameAndDescription)
    {
        $variableName = $this->commandInfo->findMatchingOption($nameAndDescription['name']);
        $desc = $nameAndDescription['description'];
        $description = static::removeLineBreaks($desc);
        $set->add($variableName, $description);
    }

    /**
     * Store the data from a @default annotation in our argument or option store,
     * as appropriate.
     */
    protected function processDefaultTag($tag)
    {
        if (!$this->pregMatchNameAndDescription((string)$tag->getContent(), $match)) {
            return;
        }
        $variableName = $match['name'];
        $defaultValue = $this->interpretDefaultValue($match['description']);
        if ($this->commandInfo->arguments()->exists($variableName)) {
            $this->commandInfo->arguments()->setDefaultValue($variableName, $defaultValue);
            return;
        }
        $variableName = $this->commandInfo->findMatchingOption($variableName);
        if ($this->commandInfo->options()->exists($variableName)) {
            $this->commandInfo->options()->setDefaultValue($variableName, $defaultValue);
        }
    }

    /**
     * Store the data from a @usage annotation in our example usage list.
     */
    protected function processUsageTag($tag)
    {
        $lines = explode("\n", $tag->getContent());
        $usage = trim(array_shift($lines));
        $description = static::removeLineBreaks(implode("\n", array_map(function ($line) {
            return trim($line);
        }, $lines)));

        $this->commandInfo->setExampleUsage($usage, $description);
    }

    /**
     * Process the comma-separated list of aliases
     */
    protected function processAliases($tag)
    {
        $this->commandInfo->setAliases((string)$tag->getContent());
    }

    /**
     * Store the data from a @return annotation in our argument descriptions.
     */
    protected function processReturnTag($tag)
    {
        if (!$tag->hasWordAndDescription($matches)) {
            throw new \Exception('Could not determine return type from tag ' . (string)$tag);
        }
        // TODO: look at namespace and `use` statments to make returnType a fqdn
        $returnType = $matches['word'];
        $this->commandInfo->setReturnType($returnType);
    }

    private function parseDocBlock($doc)
    {
        if (empty($doc)) {
            return;
        }

        $tagFactory = new TagFactory();
        $lines = [];

        foreach (explode("\n", $doc) as $row) {
            // Remove trailing whitespace and leading space + '*'s
            $row = rtrim($row);
            $row = preg_replace('#^[ \t]*\**#', '', $row);

            // Throw out the /** and */ lines ('*' trimmed from beginning)
            if ($row == '/**' || $row == '/') {
                continue;
            }

            if (!$tagFactory->parseLine($row)) {
                $lines[] = $row;
            }
        }

        $this->processDescriptionAndHelp($lines);
        $this->processAllTags($tagFactory->getTags());
    }

    protected function processDescriptionAndHelp($lines)
    {
        // Trim all of the lines individually.
        $lines =
            array_map(
                function ($line) {
                    return trim($line);
                },
                $lines
            );

        // Everything up to the first blank line goes in the description.
        $description = array_shift($lines);
        while (!empty($lines) && !empty(trim($lines[0]))) {
            $description .= ' ' . array_shift($lines);
        }

        // Everything else goes in the help.
        $help = trim(implode(PHP_EOL, $lines));

        $this->commandInfo->setDescription($description);
        $this->commandInfo->setHelp($help);
    }

    protected function processAllTags($tags)
    {
        // Iterate over all of the tags, and process them as necessary.
        foreach ($tags as $tag) {
            $processFn = [$this, 'processGenericTag'];
            if (array_key_exists($tag->getTag(), $this->tagProcessors)) {
                $processFn = [$this, $this->tagProcessors[$tag->getTag()]];
            }
            $processFn($tag);
        }
    }
}
