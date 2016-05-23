<?php

namespace modmore\Alpacka\Properties;

/**
 * Class SnippetProperty
 *
 * An abstract snippet property class that defines the basic getValue/setValue/reset logic.
 *
 * You'll need to use one of the derivatives (e.g. SimpleProperty or BooleanProperty) for proper type validation.
 *
 * @package modmore\Alpaca\Properties
 */
abstract class SnippetProperty {
    protected $default;
    protected $value;

    /**
     * Sets the value for the property.
     *
     * @param $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Returns the value for this property. If no value was specifically set, it will use the default value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Resets the property value to the default.
     */
    public function reset()
    {
        $this->setValue($this->default);
    }
}