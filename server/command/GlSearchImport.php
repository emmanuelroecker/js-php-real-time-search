<?php

/**
 *
 * PHP version 5.4
 *
 * @category  GLICER
 * @package   GlSearch
 * @author    Emmanuel ROECKER
 * @author    Rym BOUCHAGOUR
 * @copyright 2015 GLICER
 * @license   GNU 2
 * @link      http://dev.glicer.com/
 *
 * Created : 24/07/15
 * File : GlSearchImport.php
 *
 */

namespace GlSearchImport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GlSearchEngine\GlServerEngine;

/**
 * Class GlSearchImport
 */
class GlSearchImport extends Command
{
    protected function configure()
    {
        $this->setName('glicer:search:import')
             ->setDescription(
             'search Import'
            )
             ->addOption("fields", null, InputOption::VALUE_REQUIRED, 'fields list to index')
             ->addOption("renew", null, InputOption::VALUE_NONE, "new database")
             ->addArgument("yamlfile", InputArgument::REQUIRED, "yaml file name");
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $yamlFile = $input->getArgument("yamlfile");

        $table  = basename($yamlFile, ".yml");
        $dbname = $table . ".db";
        $fields = explode(" ", $input->getOption("fields"));

        $renew = false;
        if ($input->getOption("renew")) {
            $renew = true;
        }

        $engine = new GlServerEngine($dbname, $output, $renew);
        $engine->importYaml(
               $table,
                   $fields,
                   $yamlFile,
                   function () use ($output) {
                       $output->write(".");
                   }
        );
    }
}