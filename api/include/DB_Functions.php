<?php
class DB_Functions {

    private $conn;

    
    function __construct() {
        require_once 'DB_Connect.php';
        $db = new Db_Connect();
        $this->conn = $db->connect();
    }

 
    function __destruct() {
        
    }


    public function storeUser($name, $email, $password) {

        $uuid = uniqid('',true);
        $passw = md5($password);
        $encrypted_password = $passw;
        $salt= trim(' ');
        $userlevel = "3";
        $time = time();
		

        $stmt = $this->conn->prepare("INSERT INTO users (unique_id, name, email, encrypted_password, created_at, userlevel, timestamp) VALUES (?, ?, ?, ?, $time, $userlevel, $time)");

        $stmt->bind_param("ssss",$uuid, $name, $email, $encrypted_password);
   
        $result = $stmt->execute();
  		
		
        $stmt->close();




        if ($result) {

            $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $user;
        } else {

            return false;
        }
    }


    public function getUserByEmailAndPassword($email, $password) {

        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");

        $stmt->bind_param("s", $email);

        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

                   $encrypted_password = $user['encrypted_password'];
    
            if ($encrypted_password == md5($password)) {
				$time = time();
				$stmt = $this->conn->prepare("UPDATE users SET previous_visit = $time WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                return $user;
            }
        } else {
            return NULL;
        }
    }

    public function isUserExisted($email) {
        $stmt = $this->conn->prepare("SELECT email from users WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
       
            $stmt->close();
            return true;
        } else {
         
            $stmt->close();
            return false;
        }
    }

 

    public function hashSSHA($password) {

        $salt = sha1(rand());
        $salt = substr($salt, 0, 10);
        $encrypted = base64_encode(sha1($password . $salt, true) . $salt);
        $hash = array("salt" => $salt, "encrypted" => $encrypted);
        return array ($hash,$salt);
    }

    public function checkhashSSHA($salt, $password) {

        $hash = base64_encode(sha1($password . $salt, true) . $salt);

        return $hash;
    }

}

?>