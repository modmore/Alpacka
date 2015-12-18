<?php
/**
 * Tests the Redactor class.
 */
class ServiceTest extends PHPUnit_Framework_TestCase {
    /** @var  \modmore\Alpacka\Alpacka */
    public $service;

    public function setUp() {
        global $modx;
        $this->service = new \modmore\Alpacka\Alpacka($modx);
    }

    public function testIsProperObject() {
        $this->assertTrue($this->service instanceof \modmore\Alpacka\Alpacka);
    }

    public function testHasValidConfigOptions() {
        $this->assertTrue(is_array($this->service->config), 'config is not an array.');
        $this->assertNotEmpty($this->service->config['core_path'], 'missing core_path config entry.');
        $this->assertNotEmpty($this->service->config['templates_path'], 'missing templates_path config entry.');
        $this->assertNotEmpty($this->service->config['controllers_path'], 'missing templates_path config entry.');
        $this->assertNotEmpty($this->service->config['model_path'], 'missing templates_path config entry.');
        $this->assertNotEmpty($this->service->config['processors_path'], 'missing templates_path config entry.');
        $this->assertNotEmpty($this->service->config['elements_path'], 'missing templates_path config entry.');
        $this->assertNotEmpty($this->service->config['assets_url'], 'missing assets_url config entry.');
        $this->assertNotEmpty($this->service->config['connector_url'], 'missing connector_url config entry.');
    }

    /**
     * @dataProvider providerExplodeAndTrim
     *
     * @param $string
     * @param $expected
     * @param $separator
     */
    public function testExplodeAndTrim ($string, $expected, $separator) {
        $this->assertEquals($expected, $this->service->explode($string, $separator));
    }

    public function providerExplodeAndTrim () {
        return array(
            array(
                'foo,bar,baz',
                array('foo', 'bar', 'baz'),
                ','
            ),
            array(
                'foo , bar , baz',
                array('foo', 'bar', 'baz'),
                ','
            ),
            array(
                'foo      ,      bar ,    baz',
                array('foo', 'bar', 'baz'),
                ','
            ),
            array(
                'foo;bar;baz',
                array('foo', 'bar', 'baz'),
                ';'
            ),
            array(
                'foo ; bar ; baz',
                array('foo', 'bar', 'baz'),
                ';'
            ),
            array(
                'foo      ;      bar ;    baz',
                array('foo', 'bar', 'baz'),
                ';'
            ),
        );
    }

    /**
     * @dataProvider providerGetBooleanOption
     *
     * @param $value
     * @param $expected
     */
    public function testGetBooleanOption($expected, $value)
    {
        $this->assertEquals($expected, $this->service->castValueToBool($value));
    }

    public function providerGetBooleanOption()
    {
        return array(
            array(true, true),
            array(true, 1),
            array(true, '1'),
            array(true, 'true'),
            array(true, 'yes'),

            array(false, false),
            array(false, 0),
            array(false, '0'),
            array(false, 'false'),
            array(false, 'no'),
        );
    }
}
