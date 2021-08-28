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
                                $description = trim(preg_replace('#[ \t\n\r]+#', ' ', $option['description']));
                                $this->commandInfo->addOptionDescription($name, $description);
                            }
                            break;
                        case 'params':
                            $set = $this->commandInfo->arguments();
                            foreach ($argValue as $name => $param) {
                                $description = trim(preg_replace('#[ \t\n\r]+#', ' ', $param['description']));
                                $this->commandInfo->addArgumentDescription($name, $description);
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
}
