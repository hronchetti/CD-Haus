<?php

abstract class recordSet {
    protected $db;
    protected $stmt;

    function __construct() {
        $this->db = pdoDB::getConnection();
    }

    /**
     * @param string $sql    The sql for the recordset
     * @param array $params  An optional associative array if you want a prepared statement
     * @return PDO_STATEMENT
     */
    function getRecordSet($sql, $elementName = 'ResultSet', $params = null) {
        if (is_array($params)) {
            $this->stmt = $this->db->prepare($sql);
            // execute the statement passing in the named placeholder and the value it'll have
            $this->stmt->execute($params);
        }
        else {
            $this->stmt = $this->db->query($sql);
        }
        return $this->stmt;
    }
}

/**
 * specialisation class that returns a record set as an json string
 * @author Harry Ronchetti
 */
class JSON_RecordSet extends recordSet {
    /**
     * function to return a record set as a json encoded string
     * @param $sql         string with sql to execute to retrieve the record set
     * @param $elementName string that will be the name of the repeating elements
     * @param $params      is an array that, if passed, is used for prepared statements, it should be an assoc array of param name => value
     * @return string      a json object showing the status, number of records and the records themselves if there are any
     */
    function getRecordSet($sql, $elementName = "ResultSet", $params = null) {
        $stmt     = parent::getRecordSet($sql, 'notneeded', $params);
        $recordSet = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $nRecords = count($recordSet);
        if ($nRecords == 0) {
            $status = 'error';
            $message = array("text" => "No records found");
            $result = '[]';
        }
        else {
            $status = 'ok';
            $message = array("text" => "");
            $result = $recordSet;
        }
        return json_encode(
            array(
                'status' => $status,
                'message' => $message,
                "$elementName" => array(
                    "RowCount"=>$nRecords,
                    "Result"=>$result
                )
            ),
            JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT
        );
    }
}
