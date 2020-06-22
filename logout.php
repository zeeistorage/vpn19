<?php
include_once 'includes/controller.php';



if(isset($_GET['path'])) {
    if ($_GET['path'] == 'admin'){
        $path = $configs->homePage();
        logout($session, $path);
    } else if($_GET['path'] == 'referrer'){
        $path = $session->referrer;
        logout($session, $path);
    }
       
} else {
    $path = $configs->loginPage();
    logout($session, $path);
}

function logout($session, $path) {
    $session->logout();
    header("Location: " . $path);
}
?>