<?php


class DB_Connect {
    private $conn;


    public function connect() {
        require_once '../includes/constants.php';
        
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        return $this->conn;
    }
}

?>