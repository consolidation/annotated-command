<?php
namespace Consolidation\AnnotatedCommand\Parser\Internal;

use Consolidation\AnnotatedCommand\Attributes\AttributeInterface;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;

/**
 * Given a class and method name, let each attribute handle its own
 * properties, populating the CommandInfo object.
 */
class AttributesDocBlockParser
{
    protected $commandInfo;
    protected $reflection;
    protected $fqcnCache;

    public function __construct(CommandInfo $commandInfo, \ReflectionMethod $reflection, $fqcnCache = null)
    {
        $this->commandInfo = $commandInfo;
        $this->reflection = $reflection;
        // @todo Unused. Lets just remove from this class?
        $this->fqcnCache = $fqcnCache ?: new FullyQualifiedClassCache();
    }

    /**
     * Call the handle method of each attribute, which alters the CommandInfo object.
     */
    public function parse()
    {
        $attributes = $this->reflection->getAttributes();
        foreach ($attributes as $attribute) {
            if (in_array(AttributeInterface::class, class_implements($attribute->getName()))) {
                call_user_func([$attribute->getName(), 'handle'], $attribute, $this->commandInfo);
            }
        }
        // Use any return type declaration.
        if ($type = $this->reflection->getReturnType()) {
            $this->commandInfo->setReturnType($type->getName());
        }
    }
}
