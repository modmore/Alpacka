<?php

namespace modmore\Alpacka;

use modmore\Alpacka\Exceptions\InvalidPropertyException;
use modmore\Alpacka\Properties\SnippetProperty;

/**
 * Class Snippet
 * 
 * A basic implementation of a slightly more object oriented snippet for MODX. The generic way to call these snippets
 * is using $service->runSnippet('ClassName', $scriptProperties);
 * 
 * @package modmore\Alpacka
 */
abstract class Snippet
{
    /** @var Alpacka $service */
    public $service;
    
    /** @var \modX $modx */
    public $modx;

    /**
     * An array of properties for this instance of this snippet.
     *
     * @var SnippetProperty[]
     */
    protected $properties = array();
    protected $_startTime = 0;
    protected $_debug = array();
    protected $_strict;

    /**
     * Snippet constructor. Enable strict mode to get exceptions thrown in possibly invalid situations, which might
     * flag issues during development, but may also result in false-positives while in use. 
     * 
     * @param Alpacka $service
     * @param bool $strict
     */
    final public function __construct(Alpacka $service, $strict = false)
    {
        $this->_startTime = microtime(true);
        $this->_strict = $strict;
        $this->service =& $service;
        $this->modx =& $service->modx;
        $this->properties = $this->getPropertiesDefinition();
    }

    /**
     * Execute the snippet. 
     * 
     * @param array $properties
     * @return string
     * @throws InvalidPropertyException
     */
    final public function run(array $properties = array())
    {
        $this->setProperties($properties);

        return $this->process();
    }

    /**
     * Defines the available properties for this snippet. This must be done using an array
     * of SnippetProperty instances, where those instances are sort-of value objects that
     * check the permitted values and store a default value. 
     * 
     * @return SnippetProperty[]
     */
    abstract public function getPropertiesDefinition();

    /**
     * This is where the actual snippet logic goes. 
     * 
     * @return string
     */
    abstract public function process();

    /**
     * @param $key
     * @param $value
     * @throws InvalidPropertyException
     */
    final public function setProperty($key, $value)
    {
        if (array_key_exists($key, $this->properties)) {
            $this->properties[$key]->setValue($value);
        }
        else {
            if ($this->_strict) {
                throw new InvalidPropertyException('Attempting to set a non-existant property ' . $key . '. Available propreties are ' . implode(',', array_keys($this->properties)));

            }
            else {
                $this->debug('Unknown property specified: ' . $key);
            }
        }
    }

    /**
     * Sets the specified values to the snippet. Any property that is not known will get ignored, and
     * when $strict was enabled when creating the snippet instance it will also throw an InvalidPropertyException.
     * 
     * @param array $properties
     * @throws InvalidPropertyException
     */
    final public function setProperties(array $properties = array())
    {
        foreach ($properties as $key => $value) {
            $this->setProperty($key, $value);
        }
    }

    /**
     * Gets the value for a property. You can't define a default per call, as that is handled in the snippet
     * definition instead to ensure consistency. 
     * 
     * @param $key
     * @return mixed
     * @throws InvalidPropertyException
     */
    final public function getProperty($key)
    {
        if (array_key_exists($key, $this->properties)) {
            return $this->properties[$key]->getValue();
        }
        throw new InvalidPropertyException('Property ' . $key . ' does not exist.');
    }

    /**
     * Returns a double sha1 hash that uniquely identifies this particular combination of properties.
     * Useful for cache keys.
     *
     * @return string
     * @throws \modmore\Alpacka\Exceptions\InvalidPropertyException
     */
    final public function getPropertyHash()
    {
        $values = '';
        foreach ($this->properties as $key => $property) {
            if ($property instanceof SnippetProperty) {
                $values .= '&' . $key . '=' . $property->getValue();
            }
            else {
                throw new \modmore\Alpacka\Exceptions\InvalidPropertyException($key . ' is not a proper property!');
            }
        }
        return sha1(sha1($values));
    }

    /**
     * Logs a message into the debug log for the snippet. 
     * 
     * @param $message
     */
    final public function debug($message)
    {
        $time = microtime(true);
        $relativeTime = $time - $this->_startTime;
        $this->_debug[] = array(
            'time' => microtime(true),
            'relativeTime' => $relativeTime,
            'message' => $message,
        );
    }
}
