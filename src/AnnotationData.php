<?php
namespace Consolidation\AnnotatedCommand;

class AnnotationData extends \ArrayObject
{
    public function get($key, $default)
    {
        return isset($this[$key]) ? $this[$key] : $default;
    }

    public function keys()
    {
        return array_keys($this->getArrayCopy());
    }
}
