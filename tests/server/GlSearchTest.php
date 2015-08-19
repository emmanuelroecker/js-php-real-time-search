<?php
/**
 * Test GlEngine
 *
 * PHP version 5.4
 *
 * @category  GLICER
 * @package   GlEngine\Tests
 * @author    Emmanuel ROECKER
 * @author    Rym BOUCHAGOUR
 * @copyright 2015 GLICER
 * @license   GNU 2
 * @link      http://dev.glicer.com/
 *
 * Created : 29/07/15
 * File : GlSearchTest.php
 *
 */
namespace GlEngine\Tests;

use GlSearchEngine\GlServerSearch;
use GlSearchEngine\GlServerIndex;

/**
 * @covers        \GlSearchEngine\GlServerSearch
 * @backupGlobals disabled
 */
class GlSearchTest extends \PHPUnit_Framework_TestCase
{
    protected static function getObjectAndMethod($classname, $name)
    {
        $class  = new \ReflectionClass($classname);
        $obj    = $class->newInstanceWithoutConstructor();
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return ['object' => $obj, 'method' => $method];
    }

    protected static function callMethod($obj_method, $args)
    {
        return $obj_method['method']->invokeArgs($obj_method['object'], $args);
    }


    public function testNormalizeIndex()
    {
        $obj_method = self::getObjectAndMethod('GlSearchEngine\GlServerIndex', 'normalize');

        $test1 = self::callMethod($obj_method, ["L’Âme Sœur"]);
        $this->assertEquals("l'ame soeur", $test1);

        $test2 = self::callMethod($obj_method, ["Le Comptoir d'Oz"]);
        $this->assertEquals("le comptoir d'oz", $test2);
    }

    public function testSearch1()
    {
        $fields = ['title', 'tags', 'description', 'address', 'city'];

        $search = new GlServerSearch(__DIR__ . "/data/web.db", "web", $fields);

        $json = $search->queryJson("rest* chaponnay");

        $obj = json_decode($json);

        $this->assertEquals($fields, $obj->fields);
        $this->assertContains("Aklé", $obj->results[0]->value->title);
        $this->assertEquals("1 0 0 10 3 1 8 9", $obj->results[0]->highlights);
    }

    public function testSearch2()
    {
        $fields = ['title', 'tags', 'description', 'address', 'city'];

        $search = new GlServerSearch(__DIR__ . "/data/web.db", "web", $fields);

        $json = $search->queryJson("zol*");

        $obj = json_decode($json);

        $this->assertEquals($fields, $obj->fields);
        $this->assertContains("Le Zola", $obj->results[0]->value->title);
    }

    public function testSearch3()
    {
        $fields = ['title', 'tags', 'description', 'address', 'city'];

        $search = new GlServerSearch(__DIR__ . "/data/web.db", "web", $fields);

        $json = $search->queryJson("lyon", "gps IS NULL");

        $obj = json_decode($json);

        $this->assertEquals($fields, $obj->fields);
        $this->assertContains("Gym Suédoise Lyon", $obj->results[0]->value->title);
    }

    public function testSearch4()
    {
        $fields = ['title', 'tags', 'description', 'address', 'city'];

        $search = new GlServerSearch(__DIR__ . "/data/web.db", "web", $fields);

        $json = $search->queryJson("tags:cinema");
        $obj  = json_decode($json);

        $this->assertEquals(2, count($obj->results));
        $this->assertContains("Cinéma Comoedia", $obj->results[0]->value->title);
        $this->assertContains("Le Zola", $obj->results[1]->value->title);
    }

    public function testSearch5()
    {
        $fields = ['title', 'tags', 'description', 'address', 'city'];

        $search = new GlServerSearch(__DIR__ . "/data/web.db", "web", $fields);

        $json = $search->queryJson("l'ame soeur");
        $obj  = json_decode($json);

        $this->assertEquals(1, count($obj->results));
    }
} 