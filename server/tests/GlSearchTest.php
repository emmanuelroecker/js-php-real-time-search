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

/**
 * @covers \GlSearchEngine\GlServerSearch
 */
class GlSearchTest extends \PHPUnit_Framework_TestCase
{
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
} 