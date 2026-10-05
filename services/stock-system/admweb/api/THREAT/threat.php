<?php
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

$status = isset($_GET['status']) ? (int)$_GET['status'] : 0;
$token  = isset($_GET['token']) ? $_GET['token'] : '';
$expected = md5($status . 'ThreatSecretKey999');
if ($token === $expected) {
    include dirname(dirname(dirname(__FILE__))) . '/include/conf.ini.php';
    include PATH_PLUGIN . '/db/db.php';
    include PATH_PLUGIN . '/config/global.php';
    GlobalConfig_update_config_keys('threat_status', $status);
    echo 'SUCCESS';
} else {
    echo 'FAILED_UNAUTHORIZED';
}
