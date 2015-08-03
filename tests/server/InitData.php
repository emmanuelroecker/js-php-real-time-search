<?php

use GlSearchEngine\GlServerEngine;

$output->writeln("Init Database");

$yamlFiles      = [__DIR__ . "/data/web.yml", __DIR__ . "/data/web2.yml"];
$dbname         = __DIR__ . "/data/web.db";
$table          = "web";
$fieldsFullText = ['title', 'tags', 'description', 'address', 'city'];
$fieldsFilter   = ['lat', 'lng', 'tags'];

$engine = new GlServerEngine($dbname, $output, true);
$engine->importYaml(
       $table,
           $fieldsFilter,
           $fieldsFullText,
           $yamlFiles,
           function () use ($output) {
               $output->write(".");
           }
);

$output->writeln("");
