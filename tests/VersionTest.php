<?php
use modmore\Alpacka\Version;

/**
 * Tests the Redactor class.
 */
class VersionTest extends PHPUnit_Framework_TestCase {
    public function testClassIsLoaded() {
        $this->assertTrue(class_exists('modmore\Alpacka\Version'));
    }

    /**
     * @dataProvider providerExpectedProperties
     *
     * @param $major
     * @param $minor
     * @param $patch
     * @param $release
     * @param $full
     */
    public function testExpectedProperties ($major, $minor, $patch, $release, $full) {
        $v = new Version($major, $minor, $patch, $release);
        $this->assertEquals($major, $v->major);
        $this->assertEquals($minor, $v->minor);
        $this->assertEquals($patch, $v->patch);
        $this->assertEquals($release, $v->release);
        $this->assertEquals($major.'.'.$minor.'.'.$patch, $v->float);
        $this->assertEquals($full, $v);
    }


    /**
     * Provides data for the testExpectedProperties test
     *
     * @return array
     */
    public function providerExpectedProperties() {
        return array(
            array(1, 0, 0, 'pl', '1.0.0-pl'),
            array(1, 4, 2, 'rc2', '1.4.2-rc2'),
            array(3, 999, 2, 'dev3', '3.999.2-dev3'),
        );
    }
}
