<?php


class Login {

    public $time;   
    private $db;
    public $session;

    public function __construct($db, $session, $functions, $configs, $logger) {
        $this->db = $db;
        $this->session = $session;
        $this->functions = $functions;
        $this->configs = $configs;
        $this->logger = $logger;
        $this->time = time();
    }

  
    function login($subuser, $subpass, $subremember) {
        
        /* email checking */
        $field = "email";  //Use field name for email
        if (!$subuser || strlen($subuser = trim($subuser)) == 0) {
            Form::setError($field, "* email or Email not entered");
        }

        /* encrypted_password checking */
        $field = "encrypted_password";  //Use field name for encrypted_password
        if (!$subpass) {
            Form::setError($field, "* encrypted_password not entered");
        }

        /* Return if form errors exist */
        if (Form::$num_errors > 0) {
            $_SESSION['value_array'] = $_POST;
            $_SESSION['error_array'] = Form::getErrorArray();
            return false;
        }

        /* Checks that email/email is in database and encrypted_password is correct */
        $result = $this->confirmUserPass($subuser, $subpass);

        /* Check error codes */

        if ($result == 1) {
            $field = "email";
            // email doesn't match
            Form::setError($field, "* Login is invalid. Please try again");
        } else if ($result == 2) {
            $field = "email";
            // encrypted_password incorrect
            Form::setError($field, "* Login is invalid. Please try again");

        
            $num_of_attemps = $this->addLoginAttempt($subuser);
            if ($num_of_attemps > 10) {
                $num_of_attemps = 10;
            }
            sleep($num_of_attemps);
        } else if ($result == 3) {
            $field = "email";
            Form::setError($field, "* Your account has not been activated yet");
        } else if ($result == 4) {
            $field = "email";
            Form::setError($field, "* Your account has not been activated by admin yet");
        } else if ($result == 5) {
            $field = "email";
            Form::setError($field, "* Your user account has been banned");
        } else if ($result == 6) {
            $field = "email";
            Form::setError($field, "* Your IP address has been banned");
        }

        /* Return if form errors exist */
        if (Form::$num_errors > 0) {
            $_SESSION['value_array'] = $_POST;
            $_SESSION['error_array'] = Form::getErrorArray();
            return false;
        }

        /* email and encrypted_password correct, register session variables */
        $this->userinfo = $this->getUserInfo($subuser, $this->db);
        $this->email = $_SESSION['email'] = $this->userinfo['email'];
        $this->unique_id = $_SESSION['unique_id'] = Functions::generateRandID();
        $this->userlevel = $this->userinfo['userlevel'];
        $this->id = $this->userinfo['id'];

        /* Insert unique_id, lastip into database and update active users table */
        $this->functions->updateUserField($this->email, "lastip", $_SERVER['REMOTE_ADDR']);
        $this->functions->updateUserField($this->email, "unique_id", $this->unique_id);
        $this->functions->addLastVisit($this->email);
        $this->functions->addActiveUser($this->email, $this->time);
        $this->resetLoginAttempts($this->email);
        $this->session->removeActiveGuest($_SERVER['REMOTE_ADDR']);
        
        /* Update Log */
        //if log turned on.. do the following...
        $this->logger->logAction($this->id, 'LOGIN');

        /* Remember Me Cookie - Expires on the time set in the control panel */
        if ($subremember) {

            $cookie_expire = $this->configs->getConfig('COOKIE_EXPIRE');
            $cookie_path = $this->configs->getConfig('COOKIE_PATH');

            setcookie("cookname", $this->email, time() + 60 * 60 * 24 * $cookie_expire, $cookie_path);
            setcookie("cookid", $this->unique_id, time() + 60 * 60 * 24 * $cookie_expire, $cookie_path);
        }

        /* Login completed successfully */
        return true;
    }



    function confirmUserPass($email, $encrypted_password) {
        /* Verify that user is in database */
        $query = "SELECT encrypted_password, userlevel, usersalt FROM users WHERE (email = :email OR email = :email)";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':email' => $email));
        $count = $stmt->rowCount();
        if (!$stmt || $count < 1) {
            return 1; // Indicates email failure
        }

        /* Retrieve encrypted_password, usersalt and userlevel from result */
        $dbarray = $stmt->fetch();

        $sqlpass = md5($encrypted_password);

        /* Validate that encrypted_password matches and check if userlevel is equal to 1 */
        if (($dbarray['encrypted_password'] == $sqlpass) && ($dbarray['userlevel'] == 1)) {
            return 3; // Indicates account has not been activated
        }

        /* Validate the encrypted_password matches and check if userlevel is equal to 2 */
        if (($dbarray['encrypted_password'] == $sqlpass) && ($dbarray['userlevel'] == 2)) {
            return 4; // Indicates admin has not activated account
        }

        /* Validate the encrypted_password matches and check to see if banned */
        if (($dbarray['encrypted_password'] == $sqlpass) && ($this->functions->checkBanned($email))) {
            return 5; // Indicates account is banned
        }

        /* Validate the encrypted_password matches and check to see if IP address is banned */
        if (($dbarray['encrypted_password'] == $sqlpass) && ($this->functions->ipDisallowed($_SERVER['REMOTE_ADDR']))) {
            return 6; // Indicates IP address is banned
        }

        /* Validate that encrypted_password is correct */
        if ($dbarray['encrypted_password'] == $sqlpass) {
            return 0; // Success! email and encrypted_password confirmed
        } else {
            return 2; // Indicates encrypted_password failure
        }
    }

   
    public function getUserInfo($email) {
        $query = "SELECT * FROM users WHERE (email = :email OR email = :email)";
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

 

    public function addLoginAttempt($email) {
        $num_of_attempts = (($num_of_attempts = $this->getLoginAttempts($email)) + 1);
        $sql = $this->db->query("UPDATE users SET user_login_attempts = '$num_of_attempts' WHERE (email = '$email' OR email = '$email')");
        return $num_of_attempts;
    }

    // Failed login attempts is reset to zero on successful login
    public function resetLoginAttempts($email) {
        $sql = $this->db->query("UPDATE users SET user_login_attempts = '0' WHERE (email = '$email' OR email = '$email')");
    }

    public function getLoginAttempts($email) {
        $stmt = $this->db->query("SELECT user_login_attempts FROM users WHERE (email = '$email' OR email = '$email')");
        return $login_attempts = $stmt->fetchColumn();
    }

}
