<?php


class Form {

    public static $values = array();    
    public static $errors = array();    
    public static $num_errors;         

 

    function __construct() {

       
        if (isset($_SESSION['value_array']) && isset($_SESSION['error_array'])) {
            self::$values = $_SESSION['value_array'];
            self::$errors = $_SESSION['error_array'];
            self::$num_errors = count(self::$errors);
            unset($_SESSION['value_array']);
            unset($_SESSION['error_array']);
        } else {
            $num_errors = 0;
        }
    }

   
    public static function setValue($field, $value) {
        self::$values[$field] = $value;
    }

   
    public static function setError($field, $errmsg) {
        self::$errors[$field] = $errmsg;
        return self::$num_errors = count(self::$errors);
    }

   
    public static function value($field) {
        if (array_key_exists($field, self::$values)) {
            return htmlspecialchars(stripslashes(self::$values[$field]));
        } else {
            return "";
        }
    }

    
    public static function error($field) {
        if (array_key_exists($field, self::$errors)) {
            return self::$errors[$field];
        } else {
            return "";
        }
    }

   
    public static function getErrorArray() {
        return self::$errors;
    }

}
