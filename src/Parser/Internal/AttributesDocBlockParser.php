<?php
namespace Consolidation\AnnotatedCommand\Parser\Internal;

use Consolidation\AnnotatedCommand\Parser\CommandInfo;

/**
 * Given a class and method name, parse the attributes and provide accessor
 * methods for all of the elements needed to create an annotated Command.
 */
class AttributesDocBlockParser
{
    const COMMAND_ATTRIBUTE_CLASS_NAME = "Consolidation\AnnotatedCommand\CommandLineAttributes";

    protected $commandInfo;
    protected $reflection;
    protected $fqcnCache;

    public function __construct(CommandInfo $commandInfo, \ReflectionMethod $reflection, $fqcnCache = null)
    {
        $this->commandInfo = $commandInfo;
        $this->reflection = $reflection;
        $this->fqcnCache = $fqcnCache ?: new FullyQualifiedClassCache();
    }

    public function parse()
    {
        $attributes = $this->reflection->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getName() === self::COMMAND_ATTRIBUTE_CLASS_NAME) {
                foreach ($attribute->getArguments() as $argName => $argValue) {
                    switch ($argName) {
                        case 'name':
                            $this->commandInfo->setName($argValue);
                            // Make sure this method is recognized as a command.
                            $this->commandInfo->addAnnotation('command', $argValue);
                            break;
                        case 'description':
                            $this->commandInfo->setDescription($argValue);
                            break;
                        case 'help':
                            $this->commandInfo->setHelp($argValue);
                            break;
                        case 'aliases':
                            $this->commandInfo->setAliases($argValue);
                            break;
                        case 'usage':
                            $this->commandInfo->setExampleUsage(key($argValue), array_pop($argValue));
                            break;
                        case 'options':
                            $set = $this->commandInfo->options();
                            foreach ($argValue as $name => $option) {
                                $variableName = $this->commandInfo->findMatchingOption($name);
                                $description = trim(preg_replace('#[ \t\n\r]+#', ' ', $option['description']));
                                list($description, $defaultValue) = $this->splitOutDefault($description);
                                $set->add($variableName, $description);
                                if ($defaultValue !== null) {
                                    $set->setDefaultValue($variableName, $defaultValue);
                                }
                            }
                            break;
                        case 'params':
                            $set = $this->commandInfo->arguments();
                            foreach ($argValue as $name => $param) {
                                $variableName = $this->commandInfo->findMatchingOption($name);
                                $description = trim(preg_replace('#[ \t\n\r]+#', ' ', $param['description']));
                                list($description, $defaultValue) = $this->splitOutDefault($description);
                                $set->add($variableName, $description);
                                if ($defaultValue !== null) {
                                    $set->setDefaultValue($variableName, $defaultValue);
                                }
                            }
                            break;
                        default:
                            foreach ($argValue as $name => $annotation) {
                                foreach ($annotation as $value) {
                                    $this->commandInfo->addAnnotation($name, $value);
                                }
                            }
                    }
                }
            }
        }
    }

    /**
     * @todo Copied from BespokeDocBlockParser, refactor for reuse.
     */
    protected function splitOutDefault($description)
    {
        if (!preg_match('#(.*)(Default: *)(.*)#', trim($description), $matches)) {
            return [$description, null];
        }

        return [trim($matches[1]), $this->interpretDefaultValue(trim($matches[3]))];
    }

    /**
     * @todo Copied from BespokeDocBlockParser, refactor for reuse.
     */
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
}
