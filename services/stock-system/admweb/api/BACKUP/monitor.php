<?php
include(dirname(dirname(dirname(__FILE__))) . '/mainApi.php');
include(dirname(dirname(__FILE__)) . '/oApi.php');

$allowedDomain = 'aosoft.co.th';

if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if (strpos($referer, $allowedDomain) === false) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied: Invalid referer');
    }
} elseif (isset($_SERVER['HTTP_ORIGIN'])) {
    $origin = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
    if (strpos($origin, $allowedDomain) === false) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied: Invalid origin');
    }
} else {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if (strpos($host, $allowedDomain) === false) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied: Host mismatch');
    }
}

$fname = PATH_UPLOAD . '/autobackup/' . strtolower(date('l'));
if ((!file_exists($fname) || date('Y-m-d', filemtime($fname)) != date('Y-m-d'))) {
    CORN_BACKUP_Create();
}
