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
 * Created : 03/08/15
 * File : GlServerIndex.php
 *
 */

namespace GlSearchEngine;

use Symfony\Component\Console\Output\OutputInterface;


class GlServerIndex
{
    const SQLITE_ERROR_CODE_CONSTRAINT = 19;

    /**
     * @var \SQLite3
     */
    private $db;

    /**
     * @var OutputInterface
     */
    private $output;


    /**
     * @var array
     */
    private $fieldsFullText;

    /**
     * @var array
     */
    private $fieldsFilter;


    /**
     * @var \SQLite3Stmt
     */
    private $stmtInsertFilter;


    /**
     * @var \SQLite3Stmt
     */
    private $stmtInsertFullText;

    /**
     * @param string $s
     *
     * @return string
     */

    private function removeDiacritics($s)
    {
        $s = mb_strtolower($s, 'UTF-8');

        $s = str_replace(['ä', 'â', 'à'], "a", $s);
        $s = str_replace(['é', 'è', 'ë', 'ê'], "e", $s);
        $s = str_replace(['ï', 'î'], "i", $s);
        $s = str_replace(['ö', 'ô'], "o", $s);
        $s = str_replace(['ù', 'ü', 'û'], "u", $s);
        $s = str_replace('ç', "c", $s);
        $s = str_replace('œ', "oe", $s);
        $s = str_replace("’", "'", $s);

        return $s;
    }

    /**
     * @param string $s
     *
     * @return mixed
     */
    private function normalize($s)
    {
        return preg_replace('/\r\n?/', "", $this->removeDiacritics($s));
    }

    /**
     * @param \SQLite3        $db
     * @param string          $table
     * @param array           $fieldsFilter
     * @param array           $fieldsFullText
     * @param boolean         $useuid
     * @param OutputInterface $output
     *
     * @throws \Exception
     */
    public function __construct(
        $db,
        $table,
        array $fieldsFilter,
        array $fieldsFullText,
        $useuid,
        OutputInterface $output
    ) {
        $this->output = $output;
        $this->db     = $db;

        $this->fieldsFilter   = $fieldsFilter;
        $this->fieldsFullText = $fieldsFullText;
        $this->useuid         = $useuid;
        $tableFilter          = "{$table}F";
        $tableFullText        = "{$table}FT";

        if ($useuid) {
            $useuid = 'uid UNIQUE,';
        }

        if (sizeof($fieldsFilter) > 0) {
            $sqlfieldsFilter = implode("','", array_keys($fieldsFilter));
            $createSQLFilter = "CREATE TABLE {$tableFilter}(docid INTEGER PRIMARY KEY, {$useuid} json, '{$sqlfieldsFilter}')";
        } else {
            $createSQLFilter = "CREATE TABLE {$tableFilter}(docid INTEGER PRIMARY KEY, {$useuid} json)";
        }

        if ($this->db->exec($createSQLFilter) === false) {
            $this->output->writeln($createSQLFilter);
            $this->output->writeln($this->db->lastErrorCode() . " : " . $this->db->lastErrorMsg());
            throw new \Exception("cannot create table : " . $tableFilter);
        }

        if (sizeof($fieldsFullText) <= 0) {
            throw new \Exception("You must have at least one field full text");
        }

        $sqlfieldsFullText = implode("','", array_keys($fieldsFullText));
        $createSQLFullText = "CREATE VIRTUAL TABLE {$tableFullText} USING fts4('{$sqlfieldsFullText}');";
        if ($this->db->exec($createSQLFullText) === false) {
            $this->output->writeln($createSQLFullText);
            $this->output->writeln($this->db->lastErrorCode() . " : " . $this->db->lastErrorMsg());
            throw new \Exception("cannot create table : " . $tableFullText);
        }

        $values                 = implode(",", array_fill(0, sizeof($this->fieldsFilter), '?'));
        $prepareInsert          = "INSERT INTO {$tableFilter} VALUES (?, ?, ?, $values)";
        $this->stmtInsertFilter = $this->db->prepare($prepareInsert);


        $values                   = implode(",", array_fill(0, sizeof($this->fieldsFullText), '?'));
        $prepareInsert            = "INSERT INTO {$tableFullText}(docid,'{$sqlfieldsFullText}') VALUES (?,$values)";
        $this->stmtInsertFullText = $this->db->prepare($prepareInsert);
    }

    /**
     * @param &$data
     *
     * @return array
     */
    private function valuesFullText(&$data)
    {
        $valuesFullText = [];
        foreach ($this->fieldsFullText as $fieldFullText => $fctfieldFullText) {
            if (isset($data[$fieldFullText])) {
                if ($fctfieldFullText) {
                    $data[$fieldFullText] = $fctfieldFullText($data[$fieldFullText]);
                }
                $valuesFullText[$fieldFullText] = $this->normalize($data[$fieldFullText]);
            } else {
                $valuesFullText[$fieldFullText] = '';
            }
        }

        return $valuesFullText;
    }

    /**
     * @param &$data
     *
     * @return array
     */
    private function valuesFilter(&$data)
    {
        $valuesFilter = [];
        foreach ($this->fieldsFilter as $fieldFilter => $fctfieldFilter) {
            if (isset($data[$fieldFilter])) {
                if ($fctfieldFilter) {
                    $data[$fieldFilter] = $fctfieldFilter($data[$fieldFilter]);
                }
                $valuesFilter[$fieldFilter] = $data[$fieldFilter];
            } else {
                $valuesFilter[$fieldFilter] = null;
            }
        }

        return $valuesFilter;
    }

    /**
     * @param int      $id
     * @param array    $data
     * @param callable $callback
     *
     * @throws \Exception
     */
    public function import(
        &$id,
        array $data,
        callable $callback
    ) {
        foreach ($data as $uid => $elem) {
            $valuesFullText = $this->valuesFullText($elem);
            $valuesFilter   = $this->valuesFilter($elem);

            if (sizeof($valuesFullText) > 0) {
                $json = json_encode($elem);

                $num = 1;
                $this->stmtInsertFilter->bindValue($num++, $id, SQLITE3_INTEGER);
                if ($this->useuid) {
                    $this->stmtInsertFilter->bindValue($num++, $uid, SQLITE3_TEXT);
                }
                $this->stmtInsertFilter->bindValue($num++, $json, SQLITE3_TEXT);
                foreach ($valuesFilter as $valueFilter) {
                    $this->stmtInsertFilter->bindValue($num++, $valueFilter);
                }
                if (@$this->stmtInsertFilter->execute() === false) {
                    $lasterror = $this->db->lastErrorCode();
                    if ($lasterror != self::SQLITE_ERROR_CODE_CONSTRAINT) {
                        $this->output->writeln($lasterror . " : " . $this->db->lastErrorMsg());
                        throw new \Exception("cannot insert filter fields");
                    } else {
                        @$this->stmtInsertFilter->reset();
                        continue;
                    }
                }

                $num = 1;
                $this->stmtInsertFullText->bindValue($num++, $id, SQLITE3_INTEGER);
                foreach ($valuesFullText as $valueFullText) {
                    $this->stmtInsertFullText->bindValue($num++, $valueFullText, SQLITE3_TEXT);
                }
                if ($this->stmtInsertFullText->execute() === false) {
                    $this->output->writeln($this->db->lastErrorCode() . " : " . $this->db->lastErrorMsg());
                    throw new \Exception("cannot insert full text fields");
                }
                $id++;
            }
            $callback();
        }
    }
} 