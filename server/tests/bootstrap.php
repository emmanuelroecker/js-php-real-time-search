<?php
require __DIR__ . '/../vendor/autoload.php';

use GlSearchEngine\GlServerEngine;
use Symfony\Component\Console\Output\ConsoleOutput;

$output = new ConsoleOutput();

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
