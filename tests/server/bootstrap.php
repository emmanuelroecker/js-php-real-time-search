<?php
require __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Console\Output\ConsoleOutput;

$output = new ConsoleOutput();

include("WebServer.php");
include("InitData.php");
include("ClientTests.php");
