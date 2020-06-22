<?php



class Session {

    public $email;     //email given on sign-up
    public $unique_id;       //Random value generated on current login
    public $userlevel;    //The level to which the user pertains
    public $time;         //Time user was last active (page loaded)
    public $id;           //Users unique ID
    public $logged_in;    //True if user is logged in, false otherwise
    public $userinfo = array();  //The array holding all user info
    public $url;          //The page url current being viewed
    public $referrer;     //Last recorded site page viewed
    public $num_members;  //Number of signed-up users
    public $num_active_users;   //Number of active users viewing site
    public $num_active_guests;  //Number of active guests viewing site
    public $db;           //The Database Connection

    /**
     * Note: referrer should really only be considered the actual page referrer 
     * in process.php, any other time it may be inaccurate.
     */
    /* Class constructor */

    function __construct($db) {

        $this->db = $db;
        $this->functions = new Functions($db);
        $this->logger = new Logger($db);
        $this->configs = new Configs($db);
        $this->time = time();
        $this->startSession();

        /**
         * Only query database to find out number of members when getNumMembers() 
         * is called for the first time, until then, default value set.
         */
        $this->num_members = -1;
        if ($this->configs->getConfig('TRACK_VISITORS')) {
            /* Calculate number of users at site */
            $this->functions->calcNumActiveUsers();
            /* Calculate number of guests at site */
            $this->calcNumActiveGuests();
        }

        // Calculates total users online each time a user visits/refreshes and adds to dbase if a record
        $total = $this->total_users_online = $this->functions->calcNumActiveUsers() + $this->calcNumActiveGuests();
        if ($total > $this->configs->getConfig('record_online_users')) {
            $this->configs->updateConfigs($total, 'record_online_users');
            $this->configs->updateConfigs($this->time, 'record_online_date');
        }
    }

    /**
     * startSession - Performs all the actions necessary to initialise this session object. 
     * Tries to determine if the user has logged in already, and sets the variables 
     * accordingly. Also takes advantage of this page load to update the active visitors tables.
     */
    function startSession() {

        session_start();   //Tell PHP to start the session

        /* Determine if user is logged in */
        $this->logged_in = $this->checkLogin();

        /**
         * Set guest value to users not logged in, and update
         * active guests table accordingly.
         */
        if (!$this->logged_in) {
            $this->email = $_SESSION['email'] = GUEST_NAME;
            $this->userlevel = GUEST_LEVEL;
            $this->addActiveGuest($_SERVER['REMOTE_ADDR'], $this->time);
        }
        /* Update users last active timestamp */ else {
            $this->functions->addActiveUser($this->email, $this->time);
        }

        /* Remove inactive visitors from database */
        $this->removeInactiveUsers();
        $this->removeInactiveGuests();

        /* Set referrer page */
        if (isset($_SESSION['url'])) {
            $this->referrer = $_SESSION['url'];
        } else {
            $this->referrer = "/";
        }

        /* Set current url */
        $this->url = $_SESSION['url'] = htmlentities($_SERVER['PHP_SELF']);
    }

    /**
     * checkLogin - Checks if the user has already previously logged in, and 
     * a session with the user has already been established. Also checks to see 
     * if user has been remembered. If so, the database is queried to make sure 
     * of the user's authenticity. Returns true if the user is logged in.
     */
    function checkLogin() {

        /* Check if user has been remembered */
        if (isset($_COOKIE['cookname']) && isset($_COOKIE['cookid'])) {
            $this->email = $_SESSION['email'] = $_COOKIE['cookname'];
            $this->unique_id = $_SESSION['unique_id'] = $_COOKIE['cookid'];
        }

        /* email and unique_id have been set and not guest */
        if (isset($_SESSION['email']) && isset($_SESSION['unique_id']) && $_SESSION['email'] != GUEST_NAME) {
            /* Confirm that email and unique_id are valid */
            if ($this->confirmunique_id($_SESSION['email'], $_SESSION['unique_id']) != 0) {
                /* Variables are incorrect, user not logged in */
                unset($_SESSION['email']);
                unset($_SESSION['unique_id']);
                return false;
            }

            /* User is logged in, set class variables */
            $this->userinfo = $this->functions->getUserInfo($_SESSION['email'], $this->db);
            $this->email = $this->userinfo['email'];
            $this->unique_id = $this->userinfo['unique_id'];
            $this->id = $this->userinfo['id'];
            $this->userlevel = $this->userinfo['userlevel'];
            return true;
        }
        /* User not logged in */ else {
            return false;
        }
    }

    /*
     * confirmunique_id - Checks whether or not the given email is in the database, 
     * if so it checks if the given unique_id is the same unique_id in the database
     * for that user. If the user doesn't exist or if the unique_ids don't match up, 
     * it returns an error code (1 or 2). On success it returns 0.
     */

    function confirmunique_id($email, $unique_id) {
        /* Verify that user is in database */
        $query = "SELECT unique_id FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':email' => $email));
        $count = $stmt->rowCount();

        if (!$stmt || $count < 1) {
            return 1; // Indicates email failure
        }

        $dbarray = $stmt->fetch();

        /* Validate that unique_id is correct */
        if ($unique_id == $dbarray['unique_id']) {
            return 0; //Success! email and unique_id confirmed
        } else {
            return 2; //Indicates unique_id invalid
        }
    }

    /* removeInactiveUsers */

    function removeInactiveUsers() {
        if (!$this->configs->getConfig('TRACK_VISITORS')) {
            return;
        }
        $timeout = time() - $this->configs->getConfig('USER_TIMEOUT') * 60;
        $stmt = $this->db->prepare("DELETE FROM active_users WHERE timestamp < $timeout");
        $stmt->execute();
        $this->functions->calcNumActiveUsers();
    }

    /* removeInactiveGuests */

    function removeInactiveGuests() {
        if (!$this->configs->getConfig('TRACK_VISITORS')) {
            return;
        }
        $timeout = time() - $this->configs->getConfig('TRACK_VISITORS') * 60;
        $stmt = $this->db->prepare("DELETE FROM active_guests WHERE timestamp < $timeout");
        $stmt->execute();
        $this->calcNumActiveGuests();
    }

    /*
     * calcNumActiveGuests - Finds out how many active guests are viewing site and 
     * sets class variable accordingly.
     */

    function calcNumActiveGuests() {
        /* Calculate number of GUESTS at site */
        $sql = $this->db->query("SELECT * FROM active_guests");
        return $num_active_guests = $sql->rowCount();
    }   

    /*
     * confirmUserPass - Checks whether or not the given email is in the database, 
     * if so it checks if the given encrypted_password is the same encrypted_password in the database
     * for that user. If the user doesn't exist or if the encrypted_passwords don't match up, 
     * it returns an error code (1 or 2). On success it returns 0.
     */

    function confirmUserPass($email, $encrypted_password) {
        /* Verify that user is in database */
        $query = "SELECT encrypted_password, userlevel, usersalt FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':email' => $email));
        $count = $stmt->rowCount();
        if (!$stmt || $count < 1) {
            return 1; // Indicates email failure
        }

        /* Retrieve encrypted_password and userlevel from result, strip slashes */
        $dbarray = $stmt->fetch();

        $sqlpass = md5(encrypted_password);

        /* Validate that encrypted_password matches and check if userlevel is equal to 1 */
        if (($dbarray['encrypted_password'] == $sqlpass) && ($dbarray['userlevel'] == 1)) {
            return 3; // Indicates account has not been activated
        }

        /* Validate the encrypted_password matches and check if userlevel is equal to 2 */
        if (($dbarray['encrypted_password'] == $sqlpass) && ($dbarray['userlevel'] == 2)) {
            return 4; // Indicates admin has not activated account
        }

        /* Validate the encrypted_password matches and check to see if banned */
        if (($dbarray['encrypted_password'] == $sqlpass) && ($dbarray['userlevel'] == 4)) {
            return 5; // Indicates account is banned
        }

        /* Validate that encrypted_password is correct */
        if ($dbarray['encrypted_password'] == $sqlpass) {
            return 0; // Success! email and encrypted_password confirmed
        } else {
            return 2; // Indicates encrypted_password failure
        }
    }

    /* removeActiveGuest */

    function removeActiveGuest($ip) {
        if (!$this->configs->getConfig('TRACK_VISITORS')) {
            return;
        }
        $sql = $this->db->prepare("DELETE FROM active_guests WHERE ip = '$ip'");
        $sql->execute();
        $this->calcNumActiveGuests();
    }

    /*
     * checkUserEmailMatch - Checks whether email and email match in forget encrypted_password form.
     */

    function checkUserEmailMatch($email, $email) {
        $query = "SELECT email FROM users WHERE email = :email AND email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':email' => $email, ':email' => $email));
        $number_of_rows = $stmt->rowCount();

        if (!$stmt || $number_of_rows < 1) {
            return 0;
        } else {
            return 1;
        }
    }

    /* getNumMembers - Returns the number of signed-up users of the website. */

    function getNumMembers() {
        if ($this->num_members < 0) {
            $result = $this->db->query("SELECT email FROM users");
            $this->num_members = $result->rowCount();
        }
        return $this->num_members;
    }

    /* getLastUserRegistered - Returns the email of the last member to sign up and the date. */

    function getLastUserRegisteredName() {
        $result = $this->db->query("SELECT email, created_at FROM users ORDER BY created_at DESC LIMIT 0,1");
        $this->lastuser_reg = $result->fetchColumn();
        return $this->lastuser_reg;
    }

    /*
     * getLastUserRegistered - Returns the email of the last member to sign up and the date.
     */

    function getLastUserRegisteredDate() {
        $result = $this->db->query("SELECT email, created_at FROM users ORDER BY created_at DESC LIMIT 0,1");
        $this->lastuser_reg = $result->fetchColumn(1);
        return $this->lastuser_reg;
    }

    /* removeActiveUser */

    function removeActiveUser($email) {
        if (!$this->configs->getConfig('TRACK_VISITORS')) {
            return;
        }
        $sql = $this->db->prepare("DELETE FROM active_users WHERE email = '$email'");
        $sql->execute();
        $this->functions->calcNumActiveUsers();
    }

    /**
     * *********************************************************************************************
     * logout - Gets called when the user wants to be logged out of the website. It deletes any 
     * 'remember me' cookies that were stored on the users computer, and also unsets session variables 
     * and demotes his user level to guest.
     * **********************************************************************************************
     */
    function logout() {       

        // Delete cookies - the time must be in the past, so just negate what you added when creating the cookie.
        if (isset($_COOKIE['cookname']) && isset($_COOKIE['cookid'])) {

            $cookie_expire = $this->configs->getConfig('COOKIE_EXPIRE');
            $cookie_path = $this->configs->getConfig('COOKIE_PATH');

            setcookie("cookname", "", time() - 60 * 60 * 24 * $cookie_expire, $cookie_path);
            setcookie("cookid", "", time() - 60 * 60 * 24 * $cookie_expire, $cookie_path);
        }
        
        /* Update Log */
        //if log turned on.. do the following...
        if(!empty($this->id)) { $this->logger->logAction($this->id, 'LOGOFF'); }

        /* Unset PHP session variables */
        unset($_SESSION['email']);
        unset($_SESSION['unique_id']);

        /* Reflect fact that user has logged out */
        $this->logged_in = false;

        /**
         * Remove from active users table and add to
         * active guests tables.
         */
        $this->removeActiveUser($this->email);
        $this->addActiveGuest($_SERVER['REMOTE_ADDR'], $this->time);      

        /* Set user level to guest */
        $this->email = GUEST_NAME;
        $this->userlevel = GUEST_LEVEL;

        /* Destroy session */
        session_destroy();
    }

    /**
     * **********************************************************************************************
     * editAccount - Attempts to edit the user's account information including the encrypted_password, which it 
     * first makes sure is correct if entered, if so and the new encrypted_password is in the right format, the 
     * change is made. All other fields are changed automatically.
     * **********************************************************************************************
     */
    function editAccount($subcurpass, $subnewpass, $subconfnewpass, $subemail, $form) {

        /* New encrypted_password entered */
        if ($subnewpass) {

            /* Current encrypted_password error checking */
            $field = "curpass";  //Use field name for current encrypted_password
            
            if (!$subcurpass) {
                Form::setError($field, "* Current encrypted_password not entered");
            } else if ($this->confirmUserPass($this->email, $subcurpass) != 0) {
                Form::setError($field, "* Current encrypted_password incorrect");
            }

            /* New encrypted_password error checking */
            $field = "newpass";  //Use field name for new encrypted_password
            
            /* check minimum encrypted_password length (default 8 characters) */
            if (strlen($subnewpass) < $this->configs->getConfig('min_pass_chars')) {
                Form::setError($field, "* New encrypted_password too short");
            }
            /* check maximum encrypted_password length (in an attempt to stop DOS attack for extra long encrypted_password) */
            else if (strlen($subnewpass) > $this->configs->getConfig('max_pass_chars')) {
                Form::setError($field, "* New encrypted_password too long");
            }
            /* Check if encrypted_passwords match */ 
            else if ($subnewpass != $subconfnewpass) {
                Form::setError($field, "* encrypted_passwords do not match");
            }
        }
        /* Current encrypted_password entered but new one not */ else if ($subcurpass) {
            $field = "newpass";  //Use field name for new encrypted_password
            Form::setError($field, "* New encrypted_password not entered");
        } else if ($subconfnewpass) {
            $field = "conf_newpass";  //Use field name for new encrypted_password
            Form::setError($field, "* Current encrypted_password not entered");
        }
        
        //Checks E-mail Address - $subemail is there twice on purpose
        $this->functions->emailCheck($subemail, $subemail, 'email');

        /* Errors exist, have user correct them */
        if (Form::$num_errors > 0) {
            return false;  //Errors with form
        }

        /* Update encrypted_password since there were no errors */
        if ($subcurpass && $subnewpass) {
            $usersalt = Functions::generateRandStr(8);
            $subnewpass = hash($this->configs->getConfig('hash'), $usersalt . $subnewpass);
            $this->functions->updateUserField($this->email, "encrypted_password", $subnewpass);
            $this->functions->updateUserField($this->email, "usersalt", $usersalt);
            
            /* Update Log */
            //if log turned on.. do the following...
            $this->logger->logAction($this->id, 'PWD_CHANGE'); 
        }

        /* Change Email */
        if ($subemail) {
            $change = $this->functions->updateUserField($this->email, "email", $subemail);
            
            if($change){
            /* Update Log */
            //if log turned on.. do the following...
            $this->logger->logAction($this->id, 'EMAIL_CHANGE');
            }
        }

        /* Success! */
        return true;
    }

    /**
     * ****************************************************************************************
     * isAdmin - Returns true if currently logged in user is an administrator, false otherwise.
     * ****************************************************************************************
     */
    function isAdmin() {
        return ($this->userlevel == ADMIN_LEVEL || $this->userlevel == SUPER_ADMIN_LEVEL);
    }
    
    /**
     * ****************************************************************************************************
     * isSuperAdmin - Returns true if currently logged in user is THE Super Administrator, false otherwise.
     * ****************************************************************************************************
     */
    function isSuperAdmin() {
        return ($this->userlevel == SUPER_ADMIN_LEVEL and
                $this->email == ADMIN_NAME);
    }
    
    /**
     * *************************************************************************************************
     * isMemberOfGroup - Returns true if currently logged in user is a member of a certain group.
     * *************************************************************************************************
     */
    function isMemberOfGroup($groupname) {
        $unique_id = $this->id;
        $group_id = $this->functions->getGroupId($this->db, $groupname);
        $sql = $this->db->query("SELECT unique_id FROM users_groups WHERE group_id = '$group_id' AND unique_id = '$unique_id' LIMIT 1");
        return $groupinfo = $sql->fetchColumn();
    }
    
    /**
     * *******************************************************************************************************************
     * isMemberOfGroupOverLevel - Returns true if currently logged in user is a member of a group over the specified level
     * *******************************************************************************************************************
     */
    function isMemberOfGroupOverLevel($level) {
        $unique_id = $this->id;
        $sql = $this->db->query("SELECT groups.group_level, groups.group_id, users_groups.group_id, users_groups.unique_id FROM `groups` INNER JOIN `users_groups` ON groups.group_id=users_groups.group_id WHERE users_groups.unique_id = '$unique_id' AND groups.group_level > '$level'");
        $count = $sql->rowCount();
        return ($count > 0);
    }

    /**
     * *************************************************************************************************
     * isUserlevel - Returns true if currently logged in user is at a certain userlevel, false otherwise.
     * *************************************************************************************************
     */
    function isUserlevel($level) {
        return ($this->userlevel == $level);
    }

    /**
     * *****************************************************************************************************
     * overUserlevel - Returns true if currently logged in user is over a certain userlevel, false otherwise.
     * *****************************************************************************************************
     */
    function overUserlevel($level) {
        if ($this->userlevel > $level) {
            return true;
        } else {
            return false;
        }
    }

    /* 
     * **************************************************
     * addActiveGuest - Adds guest to active guests table
     * ************************************************** 
     */
    function addActiveGuest($ip, $time) {
        if (!$this->configs->getConfig('TRACK_VISITORS')) {
            return;
        }
        $sql = $this->db->prepare("REPLACE INTO active_guests VALUES ('$ip', '$time')");
        $sql->execute();
        $this->calcNumActiveGuests();
    }
    
    /* 
     * ******************************************
     * activateUser - Process to activate Users
     * ****************************************** 
     */
    function activateUser($user, $actkey){
        
        $userlevel = $this->db->query("SELECT userlevel, actkey FROM users WHERE email = '$user' LIMIT 1");
        $row = $userlevel->fetch();

        // Checks if account needs activating (1 is email activation - 2 is admin activation)
        if (($row['userlevel'] == 1) or ( $row['userlevel'] == 2) && ($row['actkey'] == $actkey)) {

            $sql = $this->db->prepare("UPDATE users SET USERLEVEL = '3' WHERE email=:user AND actkey=:actkey");
            $sql->bindParam(":user", $user);
            $sql->bindParam(":actkey", $actkey);
            $sql->execute();

            //Checks if successful
            $count = $sql->rowCount();

            if ($count) {

                //Display Activation Success message and send e-mail confirming.
                $mailer = new Mailer($this->db, $this->configs);
                if ($row['userlevel'] == 2) {
                    echo "<div>Your have activated the account for " . $user . ".</div>";
                } else {
                    echo "<div>Your account is now activated.</div>";
                }
                $sql = $this->db->query("SELECT email FROM users WHERE email = '$user'");
                $email_array = $sql->fetch();
                $email = array_shift($email_array);
                $mailer->adminActivated($user, $email);

                //Generate new activation key so old e-mail cannot change userlevel at a later date
                $token = Functions::generateRandStr(16);
                $sql = $this->db->prepare("UPDATE users SET ACTKEY = '$token' WHERE email=:user");
                $sql->bindParam(":user", $user);
                $sql->execute();
            } else {
                echo "<div>Your account was not activated. Please contact Admin for more assistance.</div>";
            }
        } else if (($row['userlevel'] != 1 ) && ($row['actkey'] === $actkey)) {
            echo "<div>This account does not need activating.</div>";
        } else {
            echo "<div>An error has occured. Please contact Admin for more assistance.</div>";
        }
    }

}
