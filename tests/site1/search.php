<?php
include(__DIR__ . "/../../src/GlServerSearch.php");

use GlSearchEngine\GlServerSearch;

$query  = $_GET['q'];
$dbname = __DIR__ . "/../server/data/web.db";
$fields = ['title', 'tags', 'description', 'address', 'city'];

$search = new GlServerSearch($dbname, "web", $fields);
$json = $search->queryJson($query);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json;charset=utf-8;');
echo $json;
