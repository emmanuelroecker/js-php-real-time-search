<?php
include(__DIR__ . "/../../src/GlServerSearch.php");

use GlSearchEngine\GlServerSearch;

$query = null;
$filter = null;

if (isset($_GET['q'])) {
    $query  = $_GET['q'];
}

if (!$query) {
    return;
}

if (isset($_GET['f'])) {
    $filter = $_GET['f'];
}

$dbname = __DIR__ . "/../server/data/web.db";
$fields = ['title', 'tags', 'description', 'address', 'city'];

$search = new GlServerSearch($dbname, "web", $fields);
$json = $search->queryJson($query, $filter);

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json;charset=utf-8;');
echo $json;
