<?php

namespace modmore\Alpacka\Properties;

/**
 * Class BooleanProperty
 *
 * A property class that forces a boolean value of true or false. The following values are considered to be true:
 * - 'true'
 * - 'yes'
 * - '1'
 * - 1
 * - true
 *
 * All other values are considered false.
 *
 * @package modmore\Alpaca\Properties
 */
class BooleanProperty extends SnippetProperty {
    /**
     * Specify a boolean default value.
     *
     * @param bool $default
     */
    public function __construct($default = true)
    {
        $this->default = $default;
        $this->setValue($default);
    }

    /**
     * Sets the value for the property. Enforces the value to be true or false.
     *
     * @param $value
     */
    public function setValue($value)
    {
        if (in_array($value, array('true', 'yes', '1', 1, true), true)) {
            $this->value = true;
        }
        else {
            $this->value = false;
        }
    }
}
