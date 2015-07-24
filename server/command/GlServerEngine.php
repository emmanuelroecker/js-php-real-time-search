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

namespace GlSearch;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class GlServerEngine
 */
class GlServerEngine extends Command
{
    /**
     * @var string
     */
    private $dbname;

    /**
     * @var string
     */
    private $tablenameIndex;

    /**
     * @var string
     */
    private $yamlFile;

    /**
     * @var string[]
     */
    private $fields;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var OutputInterface
     */
    private $output;

    protected function configure()
    {
        $this->setName('glicer:searchEngine')
            ->setDescription(
                'search Engine'
            )
            ->addOption("create", null, InputOption::VALUE_NONE, 'create database')
            ->addArgument("query", InputArgument::IS_ARRAY, "list words to search");
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

        $this->output->writeln($querySQL);

        $result = $db->query($querySQL);
        if ($result === false) {
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

    private function create()
    {
        try {
            $list = Yaml::parse(
                file_get_contents(
                    $this->yamlFile
                )
            );
        } catch (ParseException $e) {
            $this->output->writeln('Unable to parse YAML string: %s', $e->getMessage());

            return;
        }

        if ($this->fs->exists($this->dbname)) {
            $this->output->writeln("Delete file db : " . $this->dbname);
            $this->fs->remove($this->dbname);
        }

        $db = new \SQLite3($this->dbname);
        if ($db->exec('PRAGMA encoding = "UTF-8";') === false) {
            $this->output->writeln($db->lastErrorCode() . " : " . $db->lastErrorMsg());
            throw new \Exception("cannot set encoding UTF-8");
        }

        $fields    = implode("','", $this->fields);
        $createSQL = "CREATE TABLE tableJson(docid INTEGER PRIMARY KEY, json)";
        if ($db->exec($createSQL) === false) {
            $this->output->writeln($db->lastErrorCode() . " : " . $db->lastErrorMsg());
            throw new \Exception("cannot create");
        }

        $createSQL = "CREATE VIRTUAL TABLE {$this->tablenameIndex} USING fts4('$fields');";

        $this->output->writeln($createSQL);
        if ($db->exec($createSQL) === false) {
            $this->output->writeln($db->lastErrorCode() . " : " . $db->lastErrorMsg());
            throw new \Exception("cannot create");
        }

        $id = 0;
        foreach ($list as $elem) {
            $values = [];
            foreach ($this->fields as $field) {
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
                $this->output->writeln($insertSQL);
                if ($db->exec($insertSQL) === false) {
                    $this->output->writeln($db->lastErrorCode() . " : " . $db->lastErrorMsg());
                    throw new \Exception("cannot insert");
                }

                $insertSQL = "INSERT INTO {$this->tablenameIndex}(docid,'$fields') VALUES ($id,'{$valuesString}')";
                $this->output->writeln($insertSQL);
                if ($db->exec($insertSQL) === false) {
                    $this->output->writeln($db->lastErrorCode() . " : " . $db->lastErrorMsg());
                    throw new \Exception("cannot insert");
                }
                $id++;
            }
        }

        $db->close();
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
        $this->dbname   = __DIR__ . "/../search.db";
        $this->yamlFile = __DIR__ . "/../web.yml";
        $this->fields   = ['title', 'tags', 'description', 'address', 'city'];

        $this->tablenameIndex = "linkerIndex";
        $this->fs             = new Filesystem();
        $this->output         = $output;

        $output->writeln("start searchEngine");

        if ($input->getOption('create')) {
            $output->writeln("Create database");
            $this->create();
        }

        //$query = $input->getArgument('query');
        $query = "restaurant chinois";
        if ($query) {
            //$query = implode(' ', $query);
            $output->writeln("Query : $query");
            $this->search($query);
        }
    }
}