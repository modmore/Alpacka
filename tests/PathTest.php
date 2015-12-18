<?php
use modmore\Alpacka\Alpacka;
global $modx;
$alpacka = new Alpacka($modx);

/**
 * Tests the Alpacka service class, in particular the path placeholder features.
 */
class PathTest extends PHPUnit_Framework_TestCase {
    /** @var Alpacka $Alpacka */
    public $alpacka;
    public $package;

    public function setUp() {
        global $alpacka;
        $this->alpacka = $alpacka;
    }

    public function testIsProperObject() {
        $this->assertInstanceOf('modmore\Alpacka\Alpacka', $this->alpacka);
    }

    /**
     * Tests "static" path placeholders, the ones that are hardcoded to be available based on user and settings.
     *
     * @dataProvider providerParsePathVariables
     *
     * @param $path
     * @param $expected
     */
    public function testParsePathVariables($path, $expected) {
        $this->assertEquals($expected, $this->alpacka->parsePathVariables($path));
    }

    /**
     * @return array
     */
    public function providerParsePathVariables() {
        // Gotta set variables on the global because the Provider is set up before this class
        /** @var Alpacka $Alpacka */
        global $alpacka;
        $alpacka->modx->getUser()->set('id', 15);
        $alpacka->modx->getUser()->set('username', 'my_user_name');
        $alpacka->modx->setOption('assets_url', 'assets/');
        $alpacka->modx->setOption('site_url', 'http://localhost/');

        $year = date('Y');
        $month = date('m');
        $day = date('d');
        return array(
            array(
                "assets/[[+year]]/[[+month]]/[[+day]]/",
                "assets/$year/$month/$day/"
            ),
            array(
                'assets/uploads/',
                'assets/uploads/'
            ),
            array(
                "assets/[[+username]]/",
                "assets/my_user_name/"
            ),
            array(
                "assets/[[+user]]_[[+username]]/",
                "assets/15_my_user_name/"
            ),
            array(
                "[[++assets_url]][[+year]]/[[+month]]/[[+date]]/",
                "assets/$year/$month/$day/"
            ),
            array(
                "[[++site_url]]uploads/",
                "http://localhost/uploads/"
            ),
        );
    }

    public function testSetPathVariables () {
        $this->alpacka->pathVariables = array();

        $this->alpacka->setPathVariables(array(
            'id' => 10,
            'pagetitle' => 'Foo Bar Baz',
            'alias' => 'foo_bar_baz',
            'context_key' => 'test'
        ));

        $this->assertEquals(10, $this->alpacka->pathVariables['id']);
        $this->assertEquals('Foo Bar Baz', $this->alpacka->pathVariables['pagetitle']);
        $this->assertEquals('foo_bar_baz', $this->alpacka->pathVariables['alias']);
        $this->assertEquals('test', $this->alpacka->pathVariables['context_key']);

        $this->alpacka->setPathVariables(array(
            'id' => 15,
            'context_key' => 'mgr',
            'foo' => 'bar'
        ));

        $this->assertEquals(15, $this->alpacka->pathVariables['id']);
        $this->assertEquals('Foo Bar Baz', $this->alpacka->pathVariables['pagetitle']);
        $this->assertEquals('foo_bar_baz', $this->alpacka->pathVariables['alias']);
        $this->assertEquals('mgr', $this->alpacka->pathVariables['context_key']);
        $this->assertEquals('bar', $this->alpacka->pathVariables['foo']);
    }


    /**
     * Tests resource path placeholders
     *
     * @dataProvider providerParseResourcePathVariables
     *
     * @param modResource $resource
     * @param $path
     * @param $expected
     */
    public function testParseResourcePathVariables(modResource $resource, $path, $expected) {
        global $modx;
        $alpacka = new Alpacka($modx);
        // Set the resource
        $alpacka->setResource($resource);

        // Test the path
        $this->assertEquals($expected, $alpacka->parsePathVariables($path));
    }

    /**
     * @return array
     */
    public function providerParseResourcePathVariables() {
        // Gotta set variables on the global because the Provider is set up before this class
        /** @var Alpacka $alpacka */
        global $alpacka;

        /** @var modResource $resource */
        $resource = $alpacka->modx->newObject('modResource');
        $alias = $alpacka->modx->filterPathSegment('unit_' . date(DATE_ATOM));
        $pagetitle = 'Unit Testing ' . date(DATE_ATOM);
        $resource->fromArray(array(
            'pagetitle' => $pagetitle,
            'description' => 'your-description',
            'alias' => $alias,
            'parent' => 0,
        ));
        $resource->set('id', 99999);

        return array(
            array(
                $resource,
                "assets/[[+alias]]/",
                "assets/$alias/"
            ),
            array(
                $resource,
                "assets/[[+alias]]/[[+pagetitle]]/",
                "assets/$alias/$pagetitle/"
            ),
            array(
                $resource,
                "assets/[[+alias]]/[[+pagetitle]]/[[+id]]/",
                "assets/$alias/$pagetitle/99999/"
            ),
            array(
                $resource,
                "assets/[[+alias]]/[[+pagetitle]]/[[+id]]/[[+description]]/",
                "assets/$alias/$pagetitle/99999/your-description/"
            ),
        );
    }
}
