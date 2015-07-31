<?php

use GlSearchEngine\GlServerEngine;

$output->writeln("Init Database");

$yamlFile = __DIR__ . "/data/web.yml";
$dbname   = __DIR__ . "/data/web.db";
$table    = "web";
$fields   = ['title', 'tags', 'description', 'address', 'city'];

$engine = new GlServerEngine($dbname, $output, true);
$engine->importYaml(
       $table,
           $fields,
           $yamlFile,
           function () use ($output) {
               $output->write(".");
           }
);

$output->writeln("");