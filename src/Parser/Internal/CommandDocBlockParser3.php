<?php
namespace Consolidation\AnnotatedCommand\Parser\Internal;

use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Consolidation\AnnotatedCommand\Parser\DefaultsWithDescriptions;

/**
 * Given a class and method name, parse the annotations in the
 * DocBlock comment, and provide accessor methods for all of
 * the elements that are needed to create an annotated Command.
 */
class CommandDocBlockParser3 extends AbstractCommandDocBlockParser
{
    /**
     * Parse the docBlock comment for this command, and set the
     * fields of this class with the data thereby obtained.
     */
    public function parse()
    {
        // DocBlockFactory::create fails if the comment is empty.
        if (empty($this->reflection->getDocComment())) {
            return;
        }
        $phpdoc = $this->createDocBlock();

        // First set the description (synopsis) and help.
        $this->commandInfo->setDescription((string)$phpdoc->getSummary());
        $this->commandInfo->setHelp((string)$phpdoc->getDescription());

        // Iterate over all of the tags, and process them as necessary.
        foreach ($phpdoc->getTags() as $tag) {
            $processFn = [$this, 'processGenericTag'];
            if (array_key_exists($tag->getName(), $this->tagProcessors)) {
                $processFn = [$this, $this->tagProcessors[$tag->getName()]];
            }
            $processFn($tag);
        }
    }

    public function createDocBlock()
    {
        $docBlockFactory = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
        $contextFactory = new \phpDocumentor\Reflection\Types\ContextFactory();

        return $docBlockFactory->create(
            $this->reflection,
            $contextFactory->createFromReflector($this->reflection)
        );
    }

    /**
     * Save any tag that we do not explicitly recognize in the
     * 'otherAnnotations' map.
     */
    protected function processGenericTag($tag)
    {
        $this->commandInfo->addOtherAnnotation($tag->getName(), (string)$tag);
    }

    /**
     * Set the name of the command from a @command or @name annotation.
     */
    protected function processCommandTag($tag)
    {
        $this->commandInfo->setName((string)$tag);
        // We also store the name in the 'other annotations' so that is is
        // possible to determine if the method had a @command annotation.
        $this->processGenericTag($tag);
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
        $this->commandInfo->setDescription((string)$tag);
    }

    /**
     * Store the data from a @arg annotation in our argument descriptions.
     */
    protected function processArgumentTag($tag)
    {
        $this->addOptionOrArgumentTag($tag, $this->commandInfo->arguments());
    }

    /**
     * Store the data from a @param annotation in our argument descriptions.
     */
    protected function processParamTag($tag)
    {
        if (!$tag instanceof Param) {
            return;
        }
        $variableName = $tag->getVariableName();
        $variableName = str_replace('$', '', $variableName);
        $description = static::removeLineBreaks((string)$tag->getDescription());
        if ($variableName == $this->commandInfo->optionParamName()) {
            return;
        }
        $this->commandInfo->arguments()->add($variableName, $description);
    }

    /**
     * Store the data from a @return annotation in our argument descriptions.
     */
    protected function processReturnTag($tag)
    {
        if (!$tag instanceof Return_) {
            return;
        }
        // If there is a spurrious trailing space on the return type, remove it.
        $this->commandInfo->setReturnType(trim((string)$tag));
    }

    /**
     * Store the data from an @option annotation in our option descriptions.
     */
    protected function processOptionTag($tag)
    {
        $this->addOptionOrArgumentTag($tag, $this->commandInfo->options());
    }

    protected function addOptionOrArgumentTag($tag, DefaultsWithDescriptions $set)
    {
        if (!$this->pregMatchNameAndDescription((string)$tag->getDescription(), $match)) {
            return;
        }
        $variableName = $this->commandInfo->findMatchingOption($match['name']);
        $desc = $match['description'];
        $description = static::removeLineBreaks($desc);
        $set->add($variableName, $description);
    }

    /**
     * Store the data from a @default annotation in our argument or option store,
     * as appropriate.
     */
    protected function processDefaultTag($tag)
    {
        if (!$this->pregMatchNameAndDescription((string)$tag->getDescription(), $match)) {
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
     * Process the comma-separated list of aliases
     */
    protected function processAliases($tag)
    {
        $this->commandInfo->setAliases((string)$tag->getDescription());
    }

    /**
     * Store the data from a @usage annotation in our example usage list.
     */
    protected function processUsageTag($tag)
    {
        $lines = explode("\n", (string)$tag);
        $usage = array_shift($lines);
        $description = static::removeLineBreaks(implode("\n", $lines));

        $this->commandInfo->setExampleUsage($usage, $description);
    }
}
