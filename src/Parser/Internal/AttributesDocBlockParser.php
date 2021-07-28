<?php
namespace Consolidation\AnnotatedCommand\Parser\Internal;

use Consolidation\AnnotatedCommand\CommandLineAttributes;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

/**
 * Given a class and method name, parse the attributes and provide accessor
 * methods for all of the elements needed to create an annotated Command.
 */
class AttributesDocBlockParser
{
    const COMMAND_ATTRIBUTE_CLASS_NAME = CommandLineAttributes::class;

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
            // Command classes may declare a custom Attribute name (e.g. DrushCommands).
            $commandAttributeClassName = $this->reflection->getDeclaringClass()->getStaticPropertyValue('commandAttributeClassName', self::COMMAND_ATTRIBUTE_CLASS_NAME);
            if ($attribute->getName() === $commandAttributeClassName) {
                foreach ($attribute->getArguments() as $argName => $argValue) {
                    switch ($argName) {
                        case 'command':
                        case 'hook':
                            $this->commandInfo->setName($argValue);
                            $this->commandInfo->addAnnotation($argName, $argValue);
                            break;
                        case 'name':
                            $this->commandInfo->setName($argValue);
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
                        case 'usages':
                            foreach ($argValue as $name => $description) {
                                $this->commandInfo->setExampleUsage($name, $description);
                            }
                            break;
                        case 'options':
                            $set = $this->commandInfo->options();
                            foreach ($argValue as $name => $description) {
                                $variableName = $this->commandInfo->findMatchingOption($name);
                                $set->add($variableName, $description);
                            }
                            break;
                        case 'params':
                            $set = $this->commandInfo->arguments();
                            foreach ($argValue as $name => $description) {
                                $variableName = $this->commandInfo->findMatchingOption($name);
                                $set->add($variableName, $description);
                            }
                            break;
                        default:
                            if (is_scalar($argValue)) {
                                $argValue = [$argName => [$argValue]];
                            }
                            foreach ($argValue as $name => $annotation) {
                                foreach ($annotation as $value) {
                                    $this->commandInfo->addAnnotation($name, $value);
                                    // Variables can't have dashes so set a dash variant if needed.
                                    // Ex: validate_entity_load => validate-entity-load.
                                    if (strpos($argName, '_') !== FALSE) {
                                        $this->commandInfo->addAnnotation(str_replace('_', '-', $name), $value);
                                    }
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
