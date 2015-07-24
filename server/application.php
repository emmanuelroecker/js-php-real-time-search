<?php

/**
 * {SHORT_DESCRIPTION}
 *
 * PHP version 5.4
 *
 * @category  GLICER
 * @package   Contact
 * @author    Emmanuel ROECKER <emmanuel.roecker@gmail.com>
 * @author    Rym BOUCHAGOUR <rym.bouchagour@free.fr>
 * @copyright 2012-2013 GLICER
 * @license   Proprietary property of GLICER
 * @link      http://www.glicer.com
 *
 * Created : 19/03/15
 * File : application.php
 *
 */

require_once('vendor/autoload.php');

include('command/searchEngine.php');

use Symfony\Component\Console\Application;

$command = new searchEngine();

$application = new Application();
$application->add($command);
$application->run();
