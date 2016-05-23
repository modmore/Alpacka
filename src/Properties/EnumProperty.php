<?php

namespace modmore\Alpacka\Properties;

/**
 * Class EnumProperty
 *
 * A snippet property class where the value must be one of the provided options. Options are normalised to UPPERCASE.
 *
 * @package modmore\Alpaca\Properties
 */
class EnumProperty extends SnippetProperty {
    protected $options = array();

    /**
     * EnumProperty constructor. Specify the accepted values in $options.
     *
     * @param $default
     * @param array $options
     */
    public function __construct($default, array $options)
    {
        $this->default = strtoupper($default);
        $this->options = array_map('strtoupper', $options);
        $this->setValue($default);
    }

    /**
     * Sets the value for the property, falling back to the default value if it is not an accepted value.
     *
     * @param $value
     */
    public function setValue($value)
    {
        $value = strtoupper($value);
        if (in_array($value, $this->options, true)) {
            $this->value = $value;
        }
        else {
            $this->value = $this->default;
        }
    }
}