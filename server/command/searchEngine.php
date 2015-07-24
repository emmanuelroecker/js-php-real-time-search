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
 * Created : 15/07/15
 * File : testsqlite.php
 *
 */
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class searchEngine
 */
class searchEngine extends Command
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
     * @throws Exception
     * @return string
     */
    private function normalizeUtf8String($s)
    {
        if (!class_exists("Normalizer", $autoload = false)) {
            throw new \Exception('Normalizer-class missing ! ');
        }

        $original_string = $s;

        // maps German (umlauts) and other European characters onto two characters before just removing diacritics
        $s = preg_replace('@\x{00c4}@u', "AE", $s); // umlaut Ä => AE
        $s = preg_replace('@\x{00d6}@u', "OE", $s); // umlaut Ö => OE
        $s = preg_replace('@\x{00dc}@u', "UE", $s); // umlaut Ü => UE
        $s = preg_replace('@\x{00e4}@u', "ae", $s); // umlaut ä => ae
        $s = preg_replace('@\x{00f6}@u', "oe", $s); // umlaut ö => oe
        $s = preg_replace('@\x{00fc}@u', "ue", $s); // umlaut ü => ue
        $s = preg_replace('@\x{00f1}@u', "ny", $s); // ñ => ny
        $s = preg_replace('@\x{00ff}@u', "yu", $s); // ÿ => yu


        // maps special characters (characters with diacritics) on their base-character followed by the diacritical mark
        // exmaple:  Ú => U´,  á => a`
        $s = Normalizer::normalize($s, Normalizer::FORM_D);


        $s = preg_replace('@\pM@u', "", $s); // removes diacritics


        $s = preg_replace('@\x{00df}@u', "ss", $s); // maps German ß onto ss
        $s = preg_replace('@\x{00c6}@u', "AE", $s); // Æ => AE
        $s = preg_replace('@\x{00e6}@u', "ae", $s); // æ => ae
        $s = preg_replace('@\x{0132}@u', "IJ", $s); // ? => IJ
        $s = preg_replace('@\x{0133}@u', "ij", $s); // ? => ij
        $s = preg_replace('@\x{0152}@u', "OE", $s); // Œ => OE
        $s = preg_replace('@\x{0153}@u', "oe", $s); // œ => oe

        $s = preg_replace('@\x{00d0}@u', "D", $s); // Ð => D
        $s = preg_replace('@\x{0110}@u', "D", $s); // Ð => D
        $s = preg_replace('@\x{00f0}@u', "d", $s); // ð => d
        $s = preg_replace('@\x{0111}@u', "d", $s); // d => d
        $s = preg_replace('@\x{0126}@u', "H", $s); // H => H
        $s = preg_replace('@\x{0127}@u', "h", $s); // h => h
        $s = preg_replace('@\x{0131}@u', "i", $s); // i => i
        $s = preg_replace('@\x{0138}@u', "k", $s); // ? => k
        $s = preg_replace('@\x{013f}@u', "L", $s); // ? => L
        $s = preg_replace('@\x{0141}@u', "L", $s); // L => L
        $s = preg_replace('@\x{0140}@u', "l", $s); // ? => l
        $s = preg_replace('@\x{0142}@u', "l", $s); // l => l
        $s = preg_replace('@\x{014a}@u', "N", $s); // ? => N
        $s = preg_replace('@\x{0149}@u', "n", $s); // ? => n
        $s = preg_replace('@\x{014b}@u', "n", $s); // ? => n
        $s = preg_replace('@\x{00d8}@u', "O", $s); // Ø => O
        $s = preg_replace('@\x{00f8}@u', "o", $s); // ø => o
        $s = preg_replace('@\x{017f}@u', "s", $s); // ? => s
        $s = preg_replace('@\x{00de}@u', "T", $s); // Þ => T
        $s = preg_replace('@\x{0166}@u', "T", $s); // T => T
        $s = preg_replace('@\x{00fe}@u', "t", $s); // þ => t
        $s = preg_replace('@\x{0167}@u', "t", $s); // t => t

        // remove all non-ASCii characters
        $s = preg_replace('@[^\0-\x80]@u', "", $s);


        // possible errors in UTF8-regular-expressions
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
        return strtolower(preg_replace('/\r\n?/', "", SQLite3::escapeString($this->normalizeUtf8String($s))));
    }

    /**
     * @param string $query
     *
     * @throws Exception
     */
    private function search($query)
    {
        $query = $this->normalize($query);

        $stopwatch = new Stopwatch();
        $stopwatch->start('query');

        $db = new SQLite3($this->dbname);

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

        $db = new SQLite3($this->dbname);
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
                $json         = SQLite3::escapeString(json_encode($elem));
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