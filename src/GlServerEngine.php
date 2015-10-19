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
 * File : GlServerEngine.php
 *
 */

namespace GlSearchEngine;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GlServerEngine
 */
class GlServerEngine
{
    /**
     * @var \SQLite3
     */
    private $db;

    /**
     * @var string
     */
    private $dbname;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param string          $dbname database name
     * @param OutputInterface $output standard verbode output
     * @param boolean         $renew  if true delete and recreate database
     *
     * @throws \Exception
     */
    public function __construct($dbname, OutputInterface $output, $renew = false)
    {
        $this->fs     = new Filesystem();
        $this->output = $output;
        $this->dbname = $dbname;

        if ($renew) {
            if ($this->fs->exists($this->dbname)) {
                $this->fs->remove($this->dbname);
            }
        }

        $this->db = new \SQLite3($this->dbname);
        if ($this->db->exec('PRAGMA encoding = "UTF-8";') === false) {
            $this->output->writeln($this->db->lastErrorCode() . " : " . $this->db->lastErrorMsg());
            throw new \Exception("cannot set encoding UTF-8");
        }
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->db->close();
    }

    /**
     * @param int           &$startid
     * @param GlServerIndex $index
     * @param string        $yaml
     * @param callable      $callback
     */
    private function importOneYaml(&$startid, GlServerIndex $index, $yaml, callable $callback)
    {
        try {
            $data = Yaml::parse(
                        file_get_contents(
                            $yaml
                        )
            );
        } catch (ParseException $e) {
            $this->output->writeln('Unable to parse YAML string: %s in file %s', $e->getMessage(), $yaml);
            return;
        }
        if ($data == null) {
            $this->output->writeln('Unable to parse YAML file : %s', $yaml);
            return;
        }
        $index->import($startid, $data, $callback);
    }

    /**
     * @param string       $table
     * @param array        $fieldsFilter   ,
     * @param array        $fieldsFullText ,
     * @param array|string $yamls
     * @param callable     $callback
     */
    public function importYaml(
        $table,
        array $fieldsFilter,
        array $fieldsFullText,
        $yamls,
        callable $callback
    ) {
        $id    = 0;
        $index = new GlServerIndex($this->db, $table, $fieldsFilter, $fieldsFullText, $this->output);
        if (is_array($yamls)) {
            foreach ($yamls as $yaml) {
                $this->importOneYaml($id, $index, $yaml, $callback);
            }
        } else {
            $this->importOneYaml($id, $index, $yamls, $callback);
        }
    }
}