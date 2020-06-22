<?php

include_once 'controller.php';


if (isset($_POST['form_submission'])) {

    $form_submission = $_POST['form_submission'];
    switch ($form_submission) {

        case "adminlogin" :
            adminLogin($db, $session, $functions, $configs, $logger);
            break;
        case "login" :
            login($db, $session, $functions, $configs, $logger);
            break;
        case "register" :
            register($db, $session, $configs, $functions, $logger);
            break;
        case "forgot_encrypted_password" :
            forgotPass($db, $session, $configs, $functions);
            break;
        case "edit_account" :
            editAccount($session);
            break;
        default :
            if ($session->logged_in) {
                logout($session, $configs);
            } else {
                header("Location: " . $configs->homePage());
            }
    }
} else {
    logout($session, $configs);
}

/**
 * *************************************************************************
 * adminLogin - Admin process for logging in the admin to the control 
 * *************************************************************************
 */
function adminLogin($db, $session, $functions, $configs, $logger) {
    $login = new Login($db, $session, $functions, $configs, $logger);
    /* Login attempt */
    $success = $login->login($_POST['email'], $_POST['encrypted_password'], isset($_POST['remember']));

    /* Login successful */
    if ($success) {
        header("Location: " . $configs->homePage());
    } else {
        $_SESSION['value_array'] = $_POST;
        $_SESSION['error_array'] = Form::getErrorArray();
        header("Location: " . $session->referrer);
    }
}

/**
 * *******************************************************************************************************
 * login - Processes the user submitted login form, if errors are found, the user is redirected to correct 
 * the information, if not, the user is effectively logged in to the system.
 * *******************************************************************************************************
 */
function login($db, $session, $functions, $configs, $logger) {
    $login = new Login($db, $session, $functions, $configs, $logger);
    /* Login attempt */
    $success = $login->login($_POST['email'], $_POST['encrypted_password'], isset($_POST['remember']));

    /* Login successful */
    if ($success) {
        if(isset($_GET['path'])) {
            if($_GET['path'] == 'admin') {
                $path = $configs->homePage();
                header("Location: " . $path);
            } else if($_GET['path'] == 'referrer') {
                $path = $session->referrer;
                header("Location: " . $path);
            }    
            // else if($_GET['path'] == 'example') {
            //    $path = 'http://www.example.com';
            //    header("Location: " . $path);
            // }
        /* Individual User Homepage Set? */    
        } else if($configs->getConfig('TURN_ON_INDIVIDUAL') == 1) {
            $functions->setIndividualPath();
        } else {
            $path = $configs->loginPage();
            header("Location: " . $path);
        }
    /* Login failed */
    } else {
        $_SESSION['value_array'] = $_POST;
        $_SESSION['error_array'] = Form::getErrorArray();
        header("Location: " . $session->referrer);
    }
}

/**
 * ************************************************************************************************************
 * procLogout - Simply attempts to log the user out of the system given that there is no logout form to process.
 * ************************************************************************************************************
 */
function logout($session, $configs) {
    $session->logout();
    header("Location: " . $configs->homePage());
}

/**
 * **********************************************************************************************************************
 * register - Processes the user submitted registration form, if errors are found, the user is redirected to correct the
 * information, if not, the user is effectively registered with the system and an email is (optionally) sent to the newly
 * created user.
 * **********************************************************************************************************************
 */
function register($db, $session, $configs, $functions, $logger) {
    $registration = new Registration($db, $session, $configs, $functions, $logger);

    /* Checks if registration is disabled */
    if ($configs->getConfig('ACCOUNT_ACTIVATION') == 4) {
        $_SESSION['reguname'] = $_POST['user'];
        $_SESSION['regsuccess'] = 6;
        header("Location: " . $session->referrer);
    }

    /* Convert email to all lowercase (by option) */
    if ($configs->getConfig('ALL_LOWERCASE') == 1) {
        $_POST['user'] = strtolower($_POST['user']);
    }

    /* Hidden form field captcha deisgned to catch out auto-fill spambots */
    if (!empty($_POST['killbill'])) {
        $retval = 2;
    } else {
        /* Registration attempt */
        $retval = $registration->register($_POST['user'], $_POST['name'], $_POST['lastname'], $_POST['pass'], $_POST['conf_pass'], $_POST['email'], $_POST['conf_email'], 0);
    }

    /* Registration Successful */
    if ($retval == 0) {
        $_SESSION['reguname'] = $_POST['user'];
        $_SESSION['regsuccess'] = 0;
        header("Location: " . $session->referrer . "#register");
    }

    /* E-mail Activation */ else if ($retval == 3) {
        $_SESSION['reguname'] = $_POST['user'];
        $_SESSION['regsuccess'] = 3;
        header("Location: " . $session->referrer . "#register");
    }

    /* Admin Activation */ else if ($retval == 4) {
        $_SESSION['reguname'] = $_POST['user'];
        $_SESSION['regsuccess'] = 4;
        header("Location: " . $session->referrer . "#register");
    }

    /* No Activation Needed but E-mail going out */ else if ($retval == 5) {
        $_SESSION['reguname'] = $_POST['user'];
        $_SESSION['regsuccess'] = 5;
        header("Location: " . $session->referrer . "#register");
    }

    /* Error found with form */ else if ($retval == 1) {
        header("Location: " . $session->referrer . "#register");
    }

    /* Registration attempt failed */ else if ($retval == 2) {
        $_SESSION['reguname'] = $_POST['user'];
        $_SESSION['regsuccess'] = 2;
        header("Location: " . $session->referrer . "#register");
    }
}

/**
 * ******************************************************************************************************
 * forgotPass - Validates the given email then if everything is fine, a new encrypted_password is generated and
 * emailed to the address the user gave on sign up.
 * ******************************************************************************************************
 */
function forgotPass($db, $session, $configs, $functions) {
    $mailer = new Mailer($db, $configs);
  
    $subemail = $_POST['email'];
    if(!$subemail || strlen($subemail = trim($subemail)) == 0) {
        $field = "email";  //Use field name for email
        Form::setError($field, "* Email Address not entered<br>");
    }  else {
        $field = "email";  //Use field name for username
        if (strcasecmp($subemail, ADMIN_NAME) == 0) {
            Form::setError($field, "* The password for this account cannot be reset");
        } else
        /* Make sure username is in database */
        if (strlen($subemail) < $configs->getConfig('min_user_chars') || strlen($subemail) > $configs->getConfig('max_user_chars')) {
            Form::setError($field, "* Username or E-mail is incorrect<br>");
        } else if ($session->checkUserEmailMatch($subemail, $subemail) == 0) {
            Form::setError($field, "* Username or E-mail is incorrect<br>");
        }
    }

    /* Errors exist, have user correct them */
    if (Form::$num_errors > 0) {
        $_SESSION['value_array'] = $_POST;
        $_SESSION['error_array'] = Form::getErrorArray();
    } else {
        /* Generate new encrypted_password */
        $newpass = Functions::generateRandStr(8);

        /* Get email of user */
        $usrinfo = $functions->getUserInfo($subemail, $db);
        $email = $usrinfo['email'];

        /* Attempt to send the email with new encrypted_password */
        if ($mailer->sendNewPass($email, $newpass)) {
            /* Email sent, update database */
            //$usersalt = Functions::generateRandStr(8);
            $newpass = md5($newpass);
            $functions->updateUserField($email, "encrypted_password", $newpass);
            //$functions->updateUserField($subemail, "usersalt", $usersalt);
            $_SESSION['forgotpass'] = true;
        } else {
            /* Email failure, do not change encrypted_password */ 
            $_SESSION['forgotpass'] = false;
        }
    }
    header("Location: " . $session->referrer . "#reset");
}

/**
 * ********************************************************************************************************************
 * editAccount - Attempts to edit the user's account information, including the encrypted_password, which must be verified before 
 * a change is made.
 * ********************************************************************************************************************
 */
function editAccount($session) {

    /* Account edit attempt */
    $form = new Form();
    $retval = $session->editAccount($_POST['curpass'], $_POST['newpass'], $_POST['conf_newpass'], $_POST['email'], $form);

    /* Account edit successful */
    if ($retval) {
        $_SESSION['useredit'] = true;
        header("Location: " . $session->referrer);
    }

    /* Error found with form */ else {
        $_SESSION['value_array'] = $_POST;
        $_SESSION['error_array'] = Form::getErrorArray();
        header("Location: " . $session->referrer . "#link-edit-user");
    }
}
