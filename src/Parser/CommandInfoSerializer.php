<?php
namespace Consolidation\AnnotatedCommand\Parser;

use Symfony\Component\Console\Input\InputOption;
use Consolidation\AnnotatedCommand\Parser\Internal\CommandDocBlockParser;
use Consolidation\AnnotatedCommand\Parser\Internal\CommandDocBlockParserFactory;
use Consolidation\AnnotatedCommand\AnnotationData;

/**
 * Serialize a CommandInfo object
 */
class CommandInfoSerializer
{
    public function serialize(CommandInfo $commandInfo)
    {
        $allAnnotations = $commandInfo->getAnnotations();
        $path = $allAnnotations['_path'];
        $className = $allAnnotations['_classname'];

        $info = [
            'schema' => CommandInfo::SERIALIZATION_SCHEMA_VERSION,
            'class' => $className,
            'method_name' => $commandInfo->getMethodName(),
            'name' => $commandInfo->getName(),
            'description' => $commandInfo->getDescription(),
            'help' => $commandInfo->getHelp(),
            'aliases' => $commandInfo->getAliases(),
            'annotations' => $commandInfo->getRawAnnotations()->getArrayCopy(),
            'example_usages' => $commandInfo->getExampleUsages(),
            'return_type' => $commandInfo->getReturnType(),
            'mtime' => filemtime($path),
        ];
        $info['arguments'] = $this->serializeDefaultsWithDescriptions($commandInfo->arguments());
        $info['options'] = $this->serializeDefaultsWithDescriptions($commandInfo->options());

        return $info;
    }

    protected function serializeDefaultsWithDescriptions(DefaultsWithDescriptions $defaults)
    {
        $result = [];
        foreach ($defaults->getValues() as $key => $val) {
            $result[$key] = [
                'description' => $defaults->getDescription($key),
            ];
            if ($defaults->hasDefault($key)) {
                $result[$key]['default'] = $val;
            }
        }
        return $result;
    }
}
