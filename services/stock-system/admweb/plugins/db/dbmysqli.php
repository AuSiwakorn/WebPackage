<?php

define("DB_HOST", "$db_host");
define("DB_NAME", "$db_name");
define("DB_USER", "$db_user");
define("DB_PWD", "$db_passwd");

$_SESSION['logs_sql'] = array();

$_db_inc = defined('DATABASE_INC') ? DATABASE_INC : 'php_v8';
$_db_inc_file = __DIR__ . '/function/' . $_db_inc . '.php';
if (is_file($_db_inc_file)) {
    include $_db_inc_file;
} else {
    include 'function/php_v8.php'; // fallback
}

$_db_func = __DIR__ . '/function/func_' . $_db_inc . '.php';
if (is_file($_db_func)) {
    include $_db_func;
} else {
    include 'function/func_v8.php'; // fallback
}

$db = &DB::singleton();
