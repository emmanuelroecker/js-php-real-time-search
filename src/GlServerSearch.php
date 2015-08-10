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
 * Created : 29/07/15
 * File : GlServerSearch.php
 *
 */

namespace GlSearchEngine;

class GlServerSearch
{
    /**
     * @var string
     */
    private $dbname;

    /**
     * @var string
     */
    private $tableFilter;

    /**
     * @var string
     */
    private $tableFullText;

    /**
     * @var string
     */
    private $jsonStart;

    /**
     * @param string $dbname
     * @param string $table
     * @param array  $allFields
     *
     * @throws \Exception
     */
    public function __construct($dbname, $table, array $allFields)
    {
        $this->dbname = $dbname;
        $this->db     = new \SQLite3($this->dbname, SQLITE3_OPEN_READONLY);

        $this->jsonStart = '{"fields":["' . implode('","', $allFields) . '"],"results":[';

        $this->tableFilter   = $table . "F";
        $this->tableFullText = $table . "FT";
    }

    /**
     * @param string      $queryFullText
     * @param string|null $queryFilters
     *
     * @return string
     */
    public function queryJson($queryFullText, $queryFilters = null)
    {
        $sql = "SELECT json,offsets FROM {$this->tableFilter} JOIN (SELECT docid, offsets({$this->tableFullText}) AS offsets
           FROM {$this->tableFullText} WHERE {$this->tableFullText} MATCH '$queryFullText') USING (docid)";
        if (($queryFilters && strlen($queryFilters) > 0)) {
            $sql .= " WHERE ($queryFilters)";
        }

        $result = $this->db->query($sql);

        $json = $this->jsonStart;

        $first = true;
        while ($row = $result->fetchArray(SQLITE3_NUM)) {
            if (!$first) {
                $json .= ",";
            }
            $json .= '{"value":' . $row[0] . ',"highlights":"' . $row[1] . '"}';
            $first = false;
        }
        $json .= ']}';

        return $json;
    }
} 