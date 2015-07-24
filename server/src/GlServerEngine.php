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
use Symfony\Component\Stopwatch\Stopwatch;
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
     * @param string          $dbname
     * @param OutputInterface $output
     * @param boolean         $delete
     */
    public function __construct($dbname, OutputInterface $output, $delete = false)
    {
        $this->fs     = new Filesystem();
        $this->output = $output;
        $this->dbname = $dbname;

        if ($delete) {
            if ($this->fs->exists($this->dbname)) {
                $this->output->writeln("Delete file db : " . $this->dbname);
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
     * @param string $s
     *
     * @throws \Exception
     * @return string
     */
    private function normalizeUtf8String($s)
    {
        if (!class_exists("Normalizer", $autoload = false)) {
            throw new \Exception('Normalizer-class missing ! ');
        }

        $original_string = $s;

        $s = preg_replace('@\x{00c4}@u', "AE", $s);
        $s = preg_replace('@\x{00d6}@u', "OE", $s);
        $s = preg_replace('@\x{00dc}@u', "UE", $s);
        $s = preg_replace('@\x{00e4}@u', "ae", $s);
        $s = preg_replace('@\x{00f6}@u', "oe", $s);
        $s = preg_replace('@\x{00fc}@u', "ue", $s);
        $s = preg_replace('@\x{00f1}@u', "ny", $s);
        $s = preg_replace('@\x{00ff}@u', "yu", $s);

        $s = \Normalizer::normalize($s, \Normalizer::FORM_D);

        $s = preg_replace('@\pM@u', "", $s);

        $s = preg_replace('@\x{00df}@u', "ss", $s);
        $s = preg_replace('@\x{00c6}@u', "AE", $s);
        $s = preg_replace('@\x{00e6}@u', "ae", $s);
        $s = preg_replace('@\x{0132}@u', "IJ", $s);
        $s = preg_replace('@\x{0133}@u', "ij", $s);
        $s = preg_replace('@\x{0152}@u', "OE", $s);
        $s = preg_replace('@\x{0153}@u', "oe", $s);

        $s = preg_replace('@\x{00d0}@u', "D", $s);
        $s = preg_replace('@\x{0110}@u', "D", $s);
        $s = preg_replace('@\x{00f0}@u', "d", $s);
        $s = preg_replace('@\x{0111}@u', "d", $s);
        $s = preg_replace('@\x{0126}@u', "H", $s);
        $s = preg_replace('@\x{0127}@u', "h", $s);
        $s = preg_replace('@\x{0131}@u', "i", $s);
        $s = preg_replace('@\x{0138}@u', "k", $s);
        $s = preg_replace('@\x{013f}@u', "L", $s);
        $s = preg_replace('@\x{0141}@u', "L", $s);
        $s = preg_replace('@\x{0140}@u', "l", $s);
        $s = preg_replace('@\x{0142}@u', "l", $s);
        $s = preg_replace('@\x{014a}@u', "N", $s);
        $s = preg_replace('@\x{0149}@u', "n", $s);
        $s = preg_replace('@\x{014b}@u', "n", $s);
        $s = preg_replace('@\x{00d8}@u', "O", $s);
        $s = preg_replace('@\x{00f8}@u', "o", $s);
        $s = preg_replace('@\x{017f}@u', "s", $s);
        $s = preg_replace('@\x{00de}@u', "T", $s);
        $s = preg_replace('@\x{0166}@u', "T", $s);
        $s = preg_replace('@\x{00fe}@u', "t", $s);
        $s = preg_replace('@\x{0167}@u', "t", $s);

        $s = preg_replace('@[^\0-\x80]@u', "", $s);

        if (empty($s)) {
            return $original_string;
        } else {
            return $s;
        }
    }

    /**
     * @param string $s
     *
     * @return mixed
     */
    private function normalize($s)
    {
        return strtolower(preg_replace('/\r\n?/', "", \SQLite3::escapeString($this->normalizeUtf8String($s))));
    }

    /**
     * @param string $query
     *
     * @throws \Exception
     */
    private function search($query)
    {
        $query = $this->normalize($query);

        $stopwatch = new Stopwatch();
        $stopwatch->start('query');

        $db = new \SQLite3($this->dbname);

        $querySQL = "SELECT json,offsets FROM tableJson JOIN (SELECT docid, offsets({$this->tablenameIndex}) AS offsets
                     FROM {$this->tablenameIndex} WHERE {$this->tablenameIndex} MATCH '$query') USING (docid)";

        $result = $db->query($querySQL);
        if ($result === false) {
            $this->output->writeln($querySQL);
            $this->output->writeln($db->lastErrorCode() . " : " . $db->lastErrorMsg());
            throw new \Exception("cannot query");
        }

        $list = [];
        while ($row = $result->fetchArray(SQLITE3_NUM)) {
            $list[][0] = $row[0];
            $list[][1] = $row[1];
        }
        $event = $stopwatch->stop('query');

        foreach ($list as $elem) {
            var_dump($elem);
        }

        $periods = $event->getPeriods();
        foreach ($periods as $period) {
            $this->output->writeln("Time : " . $period->getDuration() . " ms");
        }
    }

    /**
     * @param string $table
     * @param string $yaml
     * @param array  $fields
     */
    public function importYaml($table, $yaml, $fields)
    {
        try {
            $list = Yaml::parse(
                        file_get_contents(
                            $yaml
                        )
            );
        } catch (ParseException $e) {
            $this->output->writeln('Unable to parse YAML string: %s', $e->getMessage());

            return;
        }

        $this->import($table, $list, $fields);
    }

    /**
     * @param string $table
     * @param array  $list
     * @param array  $fields
     *
     * @throws \Exception
     */
    public function import($table, $list, $fields)
    {
        $tableJson = $table . "Json";

        $createSQL = "CREATE TABLE $tableJson(docid INTEGER PRIMARY KEY, json)";
        if ($this->db->exec($createSQL) === false) {
            $this->output->writeln($createSQL);
            $this->output->writeln($this->db->lastErrorCode() . " : " . $this->db->lastErrorMsg());
            throw new \Exception("cannot create table : " . $tableJson);
        }

        $sqlfields = implode("','", $fields);
        $createSQL = "CREATE VIRTUAL TABLE $table USING fts4('$sqlfields');";

        if ($this->db->exec($createSQL) === false) {
            $this->output->writeln($createSQL);
            $this->output->writeln($this->db->lastErrorCode() . " : " . $this->db->lastErrorMsg());
            throw new \Exception("cannot create table : " . $table);
        }

        $id = 0;
        foreach ($list as $elem) {
            $values = [];
            foreach ($fields as $field) {
                if (isset($elem[$field])) {
                    $values[$field] = $this->normalize($elem[$field]);
                } else {
                    $values[$field] = '';
                }
            }
            if (sizeof($values) > 0) {
                $json         = \SQLite3::escapeString(json_encode($elem));
                $valuesString = implode("','", $values);
                $insertSQL    = "INSERT INTO tableJson VALUES ($id,'$json')";
                if ($this->db->exec($insertSQL) === false) {
                    $this->output->writeln($insertSQL);
                    $this->output->writeln($this->db->lastErrorCode() . " : " . $this->db->lastErrorMsg());
                    throw new \Exception("cannot insert");
                }

                $insertSQL = "INSERT INTO {$table}(docid,'$fields') VALUES ($id,'{$valuesString}')";
                if ($this->db->exec($insertSQL) === false) {
                    $this->output->writeln($insertSQL);
                    $this->output->writeln($this->db->lastErrorCode() . " : " . $this->db->lastErrorMsg());
                    throw new \Exception("cannot insert");
                }
                $id++;
            }
        }
    }
}