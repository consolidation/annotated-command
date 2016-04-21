<?php

namespace Consolidation\AnnotatedCommand;

/**
 * An associative array that maps from key to default value;
 * each entry can also have a description.
 */
class DefaultsWithDescriptions
{
    /**
     * @var array Associative array of key : default mappings
     */
    protected $values;

    /**
     * @var array Associative array of key : description mappings
     */
    protected $descriptions;

    /**
     * @var mixed Default value that the default value of items in
     * the collection should take when not specified in the 'add' method.
     */
    protected $defaultDefault;

    public function __construct($values, $defaultDefault = null)
    {
        $this->values = $values;
        $this->descriptions = [];
        $this->defaultDefault = $defaultDefault;
    }

    /**
     * Return just the key : default values mapping
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Check to see whether the speicifed key exists in the collection.
     *
     * @param type $key
     * @return type
     */
    public function exists($key)
    {
        return array_key_exists($key, $this->values);
    }

    public function setDefaultValue($key, $defaultValue)
    {
        $this->values[$key] = $defaultValue;
    }

    /**
     * Add another argument to this command.
     *
     * @param string $key Name of the argument.
     * @param string $description Help text for the argument.
     * @param string $defaultValue The default value for the argument.
     */
    public function add($key, $description, $defaultValue = null)
    {
        if (!$this->exists($key) || isset($defaultValue)) {
            $this->values[$key] = isset($defaultValue) ? $defaultValue : $this->defaultDefault;
        }
        unset($this->descriptions[$key]);
        if (isset($description)) {
            $this->descriptions[$key] = $description;
        }
    }

    /**
     * Get the description of one entry.
     *
     * @param string $key The key of the item.
     * @return string
     */
    public function getDescription($key)
    {
        if (array_key_exists($key, $this->descriptions)) {
            return $this->descriptions[$key];
        }
        return '';
    }

    /**
     * Rename an existing option to something else.
     */
    public function rename($oldName, $newName)
    {
        $this->values[$newName] = $this->values[$oldName];
        $this->descriptions[$newName] = $this->descriptions[$oldName];
        unset($this->values[$oldName]);
        unset($this->descriptions[$oldName]);
    }
}
