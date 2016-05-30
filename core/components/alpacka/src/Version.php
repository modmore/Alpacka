<?php

namespace modmore\Alpacka;

/**
 * Abstraction of a version number to provide access to parts of the version
 * or the full version in specific formats.
 *
 * @todo Make the individual properties protected and add getters (and maybe a magic __get method)
 *
 * Class Version
 */
class Version
{
    public $major = 0;
    public $minor = 0;
    public $patch = 0;
    public $release = 'pl';
    public $float;

    /**
     * Sets the version information on the internal properties
     *
     * @param $major
     * @param $minor
     * @param $patch
     * @param string $release
     */
    public function __construct($major, $minor, $patch, $release = 'pl')
    {
        $this->major = $major;
        $this->minor = $minor;
        $this->patch = $patch;
        $this->release = $release;
        $this->float = ("$major.$minor.$patch");
    }

    /**
     * Returns the full version signature
     *
     * @return string
     */
    public function __toString()
    {
        return $this->major . '.' . $this->minor . '.' . $this->patch . '-' . $this->release;
    }
}
