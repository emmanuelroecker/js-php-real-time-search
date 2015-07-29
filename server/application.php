<?php

/**
 *
 * PHP version 5.4
 *
 * @category  GLICER
 * @author    Emmanuel ROECKER
 * @author    Rym BOUCHAGOUR
 * @copyright 2015 GLICER
 * @license   GNU 2
 * @link      http://dev.glicer.com/
 *
 * Created : 24/07/15
 * File : application.php
 *
 */

require_once('vendor/autoload.php');

include('command/GlSearchImport.php');

use Symfony\Component\Console\Application;
use GlSearchImport\GlSearchImport;

$glservercommand = new GlSearchImport();

$application = new Application();
$application->add($glservercommand);
$application->run();
