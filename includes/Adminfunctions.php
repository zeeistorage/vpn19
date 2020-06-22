<?php

class Adminfunctions {

    private $db;
    public $functions;
    public $configs;
    public $stop_life = '86400'; //24 hours

    public function __construct($db, $functions, $configs, $logger) {
        $this->db = $db;
        $this->functions = $functions;
        $this->configs = $configs;
        $this->logger = $logger;
    }

    /**
     * checkLevel - Returns the userlevel - used by displayStatus function
     */
    function checkLevel($email) {
        $query = "SELECT userlevel FROM users WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':email' => $email));
        return $row = $stmt->fetchColumn();
    }

    /**
     * checkIPFormat - Returns true if the email has been banned by the administrator.
     */
    function checkIPFormat($ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $field = "ip_address";
            Form::setError($field, "* Incorrect IP Address format");
        } else {
            return true;
        }
    }
    
    /**
     * demoteUserFromAdmin - Demote user from admin (from level 9 to level 3 - remove from administrators group)
     */
    function demoteUserFromAdmin($email){
        if ($this->functions->getUserInfoSingular('userlevel', $email) != '9') {
            return false;
        } else {
            // Update to Level 3
            $this->functions->updateUserField($email, 'userlevel', '3');
            
            // Delete from Administrators Group
            $unique_id = $this->functions->getUserInfoSingular('id', $email);
            $demote = $this->db->prepare("DELETE FROM users_groups WHERE unique_id = :unique_id AND group_id = '1' LIMIT 1");
            $demote->execute(array(':unique_id' => $unique_id));
            
            /* Update Log */
            //if log turned on.. do the following...          
            $this->logger->logAction($unique_id, 'DEMOTED_FROM_ADMIN');
            
            return true;
        }
    }
    
    /**
     * promoteUserToAdmin - Promote user to admin (from level 3 to level 9 - add to administrators group)
     */
    function promoteUserToAdmin($email){
        if ($this->functions->getUserInfoSingular('userlevel', $email) == '9') {
            return false;
        } else {
            // Update to Level 9
            $this->functions->updateUserField($email, 'userlevel', '9');
            
            // Add to Administrators Group
            $unique_id = $this->functions->getUserInfoSingular('id', $email);
            $promote = $this->db->prepare("INSERT INTO users_groups (unique_id, group_id) VALUES (:unique_id, '1')");
            $promote->execute(array(':unique_id' => $unique_id));
            
            /* Update Log */
            //if log turned on.. do the following...          
            $this->logger->logAction($unique_id, 'PROMOTED_TO_ADMIN');
            
            return true;
        }
    }    

    /**
     * displayStatus
     */
    function displayStatus($email) {
        $level = $this->checkLevel($email);
        if ($level == 1) {
            return $status = '<span style="color:blue;">Awaiting E-mail Activation</span>';
        }
        if ($level == 2) {
            return $status = '<span style="color:blue;">Awaiting Admin Activation</span>';
        }
        if (($level == 3) && (!$this->functions->checkBanned($email))) {
            return $status = '<span style="color:green;">Registered</span>';
        }
        if ($this->functions->checkBanned($email)) {
            return $status = '<span style="color:red;">Banned</span>';
        }
        if ($level == ADMIN_LEVEL) { 
            return $status = 'Admin';
        }
        if ($level == SUPER_ADMIN_LEVEL) { 
            return $status = 'SuperAdmin';
        }
    }

    /**
     * displayDate - returns a variable formatted in the date format pulled from the configs
     * eg echo displayDate(time()); // echos 14th march 2014
     */
    public function displayDate($date_toedit) {
        if (isset($date_toedit)) {
            $date = $this->configs->getConfig('DATE_FORMAT');
            return date("$date", $date_toedit);
        }
    }

    /**
     * displayAdminActivation
     */
    public function displayAdminActivation($orderby) {
        $sql = $this->db->query("SELECT email, created_at, userlevel FROM users WHERE userlevel = " . ADMIN_ACT . " OR userlevel = " . ACT_EMAIL . " ORDER BY $orderby DESC");
        return $sql;
    }

    /**
     * adminEditAccount - function for admin to edit the user's account details.
     */
    public function adminEditAccount($subemail, $subname, $sublastname, $subnewpass, $subconfnewpass, $subemail, $subusertoedit) {
        /* New encrypted_password entered */
        if ($subnewpass) {
            /* New encrypted_password error checking */
            $field = "newpass";  //Use field name for new encrypted_password
            $this->userinfo = $this->functions->getUserInfoSingular('id', $subemail);

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

        if (($subconfnewpass) && (!$subnewpass)) {
            $field = "conf_newpass";
            Form::setError($field, "* You've only entered one new encrypted_password");
        }

        /* New email entered */
        if ($subemail) {
            /* email error checking */
            $field = "email";  //Use field name for userlevel
            if (!$this->functions->emailRegex($subemail)) {
                Form::setError($field, "* email does not match requirements");
            }
            /* Check email length doesnt exceed database limit of 36 */
            else if (strlen($subemail) > 36) {
                Form::setError($field, "* email above 36 characters permitted by database");
            }
            /* Check if email is reserved */
             else if (strcasecmp($subemail, GUEST_NAME) == 0) {
                Form::setError($field, "* email reserved word");
            }
            /* Check if email is already in use */ 
            else if ($subusertoedit !== $subemail && $this->functions->emailTaken($subemail)) {
                Form::setError($field, "* email already in use");
            }
        }

        /* name error checking */
        $this->functions->nameCheck($subname, 'name', 'First Name', 2, 30);

        /* Lastname error checking */
        $this->functions->nameCheck($sublastname, 'lastname', 'Last Name', 2, 30);

        /* Email error checking */
        $this->currentemail = $this->functions->getUserInfoSingular('email', $subemail);
        if($this->currentemail != $subemail){
            $this->functions->emailCheck($subemail, $subemail, 'email');
        }

        /* Errors exist, have user correct them */
        if (Form::$num_errors > 0) {
            return false;  //Errors with form
        }

        /* Update name since there were no errors */
        if ($subname) {
            $this->functions->updateUserField($subusertoedit, "name", $subname);
        }

        /* Update lastname since there were no errors */
        if ($sublastname) {
            $this->functions->updateUserField($subusertoedit, "lastname", $sublastname);
        }

        /* Update encrypted_password since there were no errors */
        if ($subnewpass) {
            $usersalt = Functions::generateRandStr(8);
            $this->functions->updateUserField($subusertoedit, "usersalt", $usersalt);
            $this->functions->updateUserField($subusertoedit, "encrypted_password", md5($subnewpass));
            
            /* Update Log */
            //if log turned on.. do the following...
            $id = $this->functions->getUserInfoSingular('id', $subusertoedit);
            $this->logger->logAction($id, 'PWD_CHANGED BY ADMIN'); 
        }

        /* Change Email */
        if($this->currentemail != $subemail){
            $this->functions->updateUserField($subusertoedit, "email", $subemail);
            
            /* Update Log */
            //if log turned on.. do the following...
            $id = $this->functions->getUserInfoSingular('id', $subusertoedit);
            $this->logger->logAction($id, 'EMAIL_CHANGED BY ADMIN'); 
        }

        /* Update email - this MUST GO LAST otherwise the email 
         * will change and subsequent changes like e-mail will not be changed.
         */
        if ($subemail) {
            $this->functions->updateUserField($subusertoedit, "email", $subemail);
        }

        /* Success! */
        return true;
    }

    /**
     * checkemail - Helper function for the above processing, it makes sure the 
     * submitted email is valid, if not, it adds the appropritate error to the form.
     */
    public function checkemail($email) {

        /* email error checking */
        $subuser = $email;
        $field = 'user';  //Use field name for email
        if (!$subuser || strlen($subuser = trim($subuser)) == 0) {
            Form::setError($field, "* email not entered<br>");
        } else {
            /* Make sure email is in database */
            if (strlen($subuser) < $this->configs->getConfig('min_user_chars') ||
                strlen($subuser) > $this->configs->getConfig('max_user_chars') ||
                (!$this->functions->emailRegex($subuser)) ||
                (!$this->functions->emailTaken($subuser))) {
                    Form::setError($field, "* email does not exist<br>");
            }
        }
        return $subuser;
    }

    /**
     * The following 3 functions are responsible for checking the validty of sensitive admin operations in 
     * an attempt at preventing CSRF attacks. They generate unique hashed ids that are passed from the 
     * POST or GET string requesting the sensitive change, to the script carrying out the change. 
     * If the IDs do not match the change is not carried out. 
     */
    function createStop($admin, $name) {
        $req_user_info = $this->functions->getUserInfo($admin);
        if (isset($req_user_info)) {
            $unique_id = $req_user_info['unique_id'];
            $stoptick = ceil(time() / ( $this->stop_life / 2 ));
            return md5($stoptick . $unique_id . $name);
        }
    }

    function verifyStop($admin, $name, $stop) {
        $req_user_info = $this->functions->getUserInfo($admin);
        if (isset($req_user_info)) {
            $unique_id = $req_user_info['unique_id'];
            $stoptick = ceil(time() / ( $this->stop_life / 2 ));
            if ((md5($stoptick . $unique_id . $name)) == $stop) {
                return 2;
            }
        }
    }

    function stopField($admin, $name) {
        $stop_field = '<input type="hidden" id="' . $name . '" name="' . $name . '" value="' . $this->createStop($admin, $name) . '" />';
        return $stop_field;
    }

    /* Returns the Previous Visit date of the submitted email */
    function previousVisit($email) {
        $lastvisit = $this->functions->getUserInfoSingular('previous_visit', $email);
        return $this->displayDate($lastvisit);
    }

    /* Users Since - returns registered users sincelast visit */
    function usersSince($email) {
        $lastvisit = $this->functions->getUserInfoSingular('previous_visit', $email);
        $query = $this->db->query("SELECT email FROM users WHERE created_at > " . $lastvisit);
        return $userssince = $query->rowCount();
    }
    
    /* Total Users - Total Users registered */
    function totalUsers() {
        $query = $this->db->query("SELECT email FROM users");
        $total_users = $query->rowCount();
        return $total_users;
    }
    
    /* recentlyOnline */
    function recentlyOnline($minutes) {
        $time = time() - ($minutes * 60);
        $query = $this->db->query("SELECT email FROM users WHERE timestamp > $time");
        $usersonline = "";
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $usersonline .= $row['email']. ", ";
        }
        $results = rtrim($usersonline, ", ");        
        return $results;
    }

}
