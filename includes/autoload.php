<?php


function autoload($class) {

    if (file_exists(dirname(__FILE__) . "/" . $class . ".php")) {
        require (dirname(__FILE__) . "/" . $class . ".php");
    } else {
        exit('The file ' . $class . '.php is missing in the includes folder.');
    }
}

spl_autoload_register("autoload");
