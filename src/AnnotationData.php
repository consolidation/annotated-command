<?php
namespace Consolidation\AnnotatedCommand;

use Consolidation\AnnotatedCommand\Parser\Internal\CsvUtils;

class AnnotationData extends \ArrayObject
{
    public function get($key, $default = '')
    {
        return $this->has($key) ? CsvUtils::toString($this[$key]) : $default;
    }

    public function getList($key, $default = [])
    {
        return $this->has($key) ? CsvUtils::toList($this[$key]) : $default;
    }

    public function has($key)
    {
        return isset($this[$key]);
    }

    public function keys()
    {
        return array_keys($this->getArrayCopy());
    }

    public function set($key, $default = '')
    {
        $this->offsetSet($key, $default);
        return $this;
    }

    public function append($key, $default = '')
    {
        $data = $this->offsetGet($key);
        if (is_array($data)) {
            $this->offsetSet($key, array_merge($data, $default));
        }
        elseif (is_numeric($data)) {
            $this->offsetSet($key, $data + $default);
        }
        elseif (is_scalar($data)) {
            $this->offsetSet($key, $data . $default);
        }
        return $this;
    }
}
