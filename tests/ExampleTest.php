<?php
require_once 'Example.php';
/**
 * Tests the Redactor class.
 */
class ExampleTest extends PHPUnit_Framework_TestCase
{
    /** @var  \modmore\Alpacka\Test\Example */
    public $service;

    public function setUp()
    {
        global $modx;
        $this->service = new \modmore\Alpacka\Test\Example($modx);
    }

    public function testVersion()
    {
        $this->assertEquals($this->service->version->major, 1);
        $this->assertEquals($this->service->version->minor, 2);
        $this->assertEquals($this->service->version->patch, 3);
        $this->assertEquals($this->service->version->release, 'pl');
    }
}