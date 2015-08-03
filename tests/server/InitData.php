<?php

use GlSearchEngine\GlServerEngine;

$output->writeln("Init Database");

$yamlFiles = [__DIR__ . "/data/web.yml", __DIR__ . "/data/web2.yml"];
$dbname    = __DIR__ . "/data/web.db";
$table     = "web";
$fields    = ['title', 'tags', 'description', 'address', 'city'];

$engine = new GlServerEngine($dbname, $output, true);
$engine->importYaml(
       $table,
           $fields,
           $yamlFiles,
           function () use ($output) {
               $output->write(".");
           }
);

$output->writeln("");
