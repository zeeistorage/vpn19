<?php

/**
 * Functions - Some functions used across multiple objects/classes.
 */
class Functions {

    private $db;
    public $login_attempts;

    public function __construct($db) {
        $this->db = $db;
    }

    /*
     * getUserInfo - Returns the result array from an sql query asking for all 
     * information stored regarding the given email. If query fails, NULL is returned.
     */
    public function getUserInfo($email) {
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':email' => $email));
        $dbarray = $stmt->fetch();
        /* Error occurred, return given name by default */
        $result = count($dbarray);
        if (!$dbarray || $result < 1) {
            return NULL;
        }
        /* Return result array */
        return $dbarray;
    }

    /*
     * getUserInfoSingular - Returns the single user's info using the email as a variable.
     */
    public function getUserInfoSingular($asset, $email) {
        $asset = strip_tags($asset);
        $query = "SELECT $asset FROM users WHERE (email = :email OR email = :email)";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':email' => $email));
        return $usersinfo = $stmt->fetchColumn();
    }
    
    /*
     * getUserInfoSingularFromId - Returns the single user's info using the ID as a variable.
     */
    public function getUserInfoSingularFromId($asset, $id) {
        $asset = strip_tags($asset);
        $query = "SELECT $asset FROM users WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':id' => $id));
        return $usersinfo = $stmt->fetchColumn();
    }

    /**
     * emailTaken - Returns true if the email has been taken by another user, false otherwise.
     */
    public function usernameTaken($email) {
        $result = $this->db->query("SELECT email FROM users WHERE email = '$email'");
        $count = $result->rowCount();
        return ($count > 0);
    }

    /**
     * ipDisallowed - Returns true if the ip address has been disallowed.
     */
    function ipDisallowed($ip) {
        $query = "SELECT ban_id FROM banlist WHERE ban_ip = :ip_address";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':ip_address' => $ip));
        $count = $stmt->rowCount();
        return ($count > 0);
    }

    /*
     * updateUserField - Updates a field, specified by the field parameter, in the 
     * user's row of the database.
     */
    public function updateUserField($email, $field, $value) {
        $query = "UPDATE users SET " . $field . " = :value WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':email' => $email, ':value' => $value));
        return $count = $stmt->rowCount();
    }

    /*
     * addLastVisit - Updates the database with the users previous visit timestamp.
     */
    public function addLastVisit($email) {
        $admin_details = $this->getUserInfo($email);
        $admin_lastvisit = $admin_details['timestamp'];
        $this->updateUserField($email, "previous_visit", $admin_lastvisit);
    }

    /**
     * checkBanned - Returns true if the email has been banned by the administrator.
     */
    function checkBanned($email) {
        $unique_id = $this->getUserInfoSingular('id', $email);
        $query = "SELECT ban_unique_id FROM banlist WHERE ban_unique_id = :unique_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':unique_id' => $unique_id));
        $count = $stmt->rowCount();
        return ($count > 0);

    }
    
    /**
     * setIndividualPath - Checks whether it is turned on and sets the path.
     */
    function setIndividualPath() {
        $configs = new Configs($this->db);
        if($configs->getConfig('HOME_SETBYADMIN') == 1) { //Home page set by admin
            $path = $configs->getConfig('USER_HOME_PATH');
            $path = str_replace('%email%', $_POST['email'], $path);   
            // Admin?
            if((strtolower($_POST['email']) == strtolower(ADMIN_NAME)) && ($configs->getConfig('NO_ADMIN_REDIRECT') == 1)) {
                $path = $configs->loginPage();
                header("Location: " . $path);
            } else { 
                header("Location: " . $configs->getConfig('WEB_ROOT') . $path);
            }
        } else if ($configs->getConfig('HOME_SETBYADMIN') == 0) { //Home page set in users profile
            // Admin?
            if((strtolower($_POST['email']) == strtolower(ADMIN_NAME)) && ($configs->getConfig('NO_ADMIN_REDIRECT') == 1)) {
                $path = $configs->loginPage();
                header("Location: " . $path);
            } else {
                $email = $_POST['email'];
                $query = "SELECT user_home_path FROM users WHERE email = :email";
                $stmt = $this->db->prepare($query);
                $stmt->execute(array(':email' => $email));
                $path = $stmt->fetchColumn();
                $path = str_replace('%email%', $_POST['email'], $path);
                header("Location: " . $configs->getConfig('WEB_ROOT') . $path);
            }
        }
    }

    /*
     * addActiveUser - Updates email's last active timestamp in the database, and 
     * also adds him to the table of active users, or updates timestamp if already there.
     */
    public function addActiveUser($email, $time) {
        $configs = new Configs($this->db);
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':email' => $email));
        $dbarray = $stmt->fetch();

        $db_timestamp = $dbarray['timestamp'];
        $timeout = time() - $configs->getConfig('USER_TIMEOUT') * 60;

        // Logs off if inactive for too long (unless remember me set)
        if ($db_timestamp < $timeout && !isset($_COOKIE['cookname']) && !isset($_COOKIE['cookid'])) {
            header("Location:" . $configs->getConfig('WEB_ROOT') . "includes/process.php");
        }

        $query = "UPDATE users SET timestamp = :time WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':email' => $email, ':time' => $time));

        if (!$configs->getConfig('TRACK_VISITORS')) {
            return;
        }
        $query = "REPLACE INTO active_users VALUES (:email, :time)";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':email' => $email, ':time' => $time));
        $this->calcNumActiveUsers();
    }

    /*
     * calcNumActiveUsers - Finds out how many active users are viewing site and 
     * sets class variable accordingly. (used for viewactive????)
     */
    public function calcNumActiveUsers() {
        /* Calculate number of USERS at site */
        $sql = $this->db->query("SELECT * FROM active_users");
        return $num_active_users = $sql->rowCount();
    }
    
    /*
     * totalUsers - Finds out how many  users are members
     */
    public function totalUsers() {
        /* Calculate number of USERS at site */
        $sql = $this->db->query("SELECT id FROM users");
        return $total_users = $sql->rowCount();
    }

    /**
     * emailRegex - checks which regex is needed - returns false if the email
     * fails the selected regex. The regex is set in the configuration table
     * in the database.
     */
    public function emailRegex($subuser) {
        $configs = new Configs($this->db);
        $option = $configs->getConfig('email_REGEX');
        switch ($option) {
            case 'any_chars':
                $regex = '.+';
                break;

            case 'alphanumeric_only':
                $regex = '[A-Za-z0-9]+';
                break;

            case 'alphanumeric_spacers':
                $regex = '[A-Za-z0-9-[\]_+ ]+';
                break;

            case 'any_letter_num':
                $regex = '[a-zA-Z0-9]+';
                break;

            case 'letter_num_spaces':
            default:
                $regex = '[-\]_+ [a-zA-Z0-9]+';
                break;
        }
        if (preg_match('#^' . $regex . '$#u', $subuser)) {
            return 1;
        }
    }
    
    /**
     * emailCheck - Checks email address on registration / change
     */
    function emailCheck($email, $conf_email, $field) {
        $configs = new Configs($this->db);
        if (!$email || strlen($email = trim($email)) == 0) {
            Form::setError($field, "* Email not entered");
        } else {
            /* Check if valid email address using PHPs filter_var */
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Form::setError($field, "* Email invalid");
            }
            /* Check if emails match, not case-sensitive */ 
            else if (strcasecmp($email, $conf_email)) {
                Form::setError($field, "* Email addresses do not match");
            }
            /* Check if email is already in use */ 
            else if ($this->emailTaken($email)) {
                Form::setError($field, "* Email address already registered");               
            }
            /* Convert email to all lowercase */
            $email = strtolower($email);
        }
    }

    /**
     * nameCheck - Checks firstname & lastname fields
     */
    function nameCheck($name, $field, $name, $min, $max) {
        if (!$name) {
            Form::setError($field, "* " . $name . " not entered");
        } else {

            /* Check if field is too short */
            if (strlen($name) < $min) {
                Form::setError($field, "* " . $name . " too short");
            }
            /* Check if field is too long */ else if (strlen($name) > $max) {
                Form::setError($field, "* " . $name . " too long");
            }
            /* Check if field is not alphanumeric */ else if (!preg_match("#^[A-Za-z0-9-[\]_+ ]+$#u", ($name = trim($name)))) {
                Form::setError($field, "* " . $name . " not alphanumeric");
            }
        }
    }
    
    /**
     * emailTaken - Returns true if the email has been taken by another user, false otherwise.
     */
    function emailTaken($email) {
        $query = "SELECT email FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':email' => $email));
        $count = $stmt->rowCount();
        return ($count > 0);
    }

    /**
     * Functions to do with Group administration.
     */
    function checkGroupNumbers($db, $groupid) {
        $sql = $this->db->query("SELECT COUNT(group_id) FROM users_groups WHERE group_id = '$groupid'");
        $count = $sql->fetchColumn();
        return $count;
    }
    
    function getGroupId($db, $groupname) {
        $sql = $this->db->query("SELECT users_groups.group_id FROM groups INNER JOIN `users_groups` ON groups.group_id = users_groups.group_id WHERE group_name = '$groupname' LIMIT 1");
        return $group_id = $sql->fetchColumn();
    }
    
    function returnGroupInfo($db, $id) {
        $sql = $this->db->query("SELECT * FROM `groups` WHERE group_id = $id");
        return $groupinfo = $sql->fetch();
    }
    
    function returnGroupMembers($db, $id) {
        $sql = $this->db->query("SELECT users.email, users_groups.group_id FROM `users` INNER JOIN `users_groups` ON users.id=users_groups.unique_id WHERE users_groups.group_id = '$id'");
        return $groupinfo = $sql->fetch();
    }

    /**
     * generateRandID - Generates a string made up of randomized letters (lower 
     * and upper case) and digits and returns the md5 hash of it to be used as a unique_id.
     */
    public static function generateRandID() {
        return md5(self::generateRandStr(16));
    }

    /**
     * generateRandStr - Generates a string made up of randomized letters (lower 
     * and upper case) and digits, the length is a specified parameter.
     */
    public static function generateRandStr($length) {
        $randstr = "";
        for ($i = 0; $i < $length; $i++) {
            $randnum = mt_rand(0, 61);
            if ($randnum < 10) {
                $randstr .= chr($randnum + 48);
            } else if ($randnum < 36) {
                $randstr .= chr($randnum + 55);
            } else {
                $randstr .= chr($randnum + 61);
            }
        }
        return $randstr;
    }

}
