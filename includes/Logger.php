<?php

/**
 * Logger - Class used to handle logging.
 */
class Logger { 

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /*
     * logAction - Use this funtion to log activity
     */
    public function logAction($unique_id, $logoperation) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
        $timestamp = time();
        $query = "INSERT INTO log_table (unique_id, log_operation, timestamp, ip) VALUES (:unique_id, :logop, $timestamp, '$ipaddress')";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':logop' => $logoperation, ':unique_id' => $unique_id));  
    }
    
    /*
     * purgeLogs - Delete Logs
     */
    public function purgeLogs() {
        $query = "truncate log_table";
        $stmt = $this->db->prepare($query);
        $stmt->execute();      
    }
    
    /*
     * purgeLogsOfUser - Delete Logs of individual user
     */
    public function purgeLogsOfUser($unique_id) {
        $query = $this->db->prepare("DELETE FROM log_table WHERE unique_id = '$unique_id'");
        $query->execute();   
    }
    
}
