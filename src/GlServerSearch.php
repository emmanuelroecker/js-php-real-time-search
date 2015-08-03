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
     * @var \SQLite3Stmt
     */
    private $stmt;

    /**
     * @var string
     */
    private $jsonStart;

    /**
     * @param string $dbname
     * @param string $table
     * @param array  $fields
     *
     * @throws \Exception
     */
    public function __construct($dbname, $table, $fields)
    {
        $this->dbname = $dbname;
        $this->db     = new \SQLite3($this->dbname, SQLITE3_OPEN_READONLY);

        $this->jsonStart = '{"fields":["' . implode('","', $fields) . '"],"results":[';

        $tableFilter   = $table . "F";
        $tableFullText = $table . "FT";
        $this->stmt    = $this->db->prepare(
                                  "SELECT json,offsets FROM $tableFilter JOIN (SELECT docid, offsets($tableFullText) AS offsets
           FROM $tableFullText WHERE $tableFullText MATCH :queryFullText) USING (docid)"
        );
    }

    /**
     * @param string $queryFullText
     *
     * @return string
     */
    public function queryJson($queryFullText)
    {
        $this->stmt->bindValue(":queryFullText", $queryFullText, SQLITE3_TEXT);
        $result = $this->stmt->execute();

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