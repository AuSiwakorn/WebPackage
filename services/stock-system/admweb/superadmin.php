<?php
header("X-Robots-Tag: noindex, nofollow", true);
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
// header("Content-Security-Policy: default-src 'self'; 
//         script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; 
//         style-src  'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; 
//         font-src   'self' https://fonts.gstatic.com;");
ob_start();
session_start();

///////// config /////////
include dirname(__FILE__) . '/include/conf.ini.php';

if (OPEN_ERROR_REPORT == true) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

if (_HTTPSREQ_ == true) {
    if ($_SERVER['REQUEST_SCHEME'] != 'https') {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location:https:" . URL_ADMIN);
        exit;
    }
}

include PATH_ADMIN . '/plugins/db/db.php';
include PATH_PLUGIN . '/detect/function.php';
include PATH_ADMIN . '/include/function.php';
include PATH_ADMIN . '/include/function_db.php';
include PATH_ADMIN . '/include/func.userinfo.php';
include PATH_ADMIN . '/include/func.useronline.php';
include PATH_PLUGIN . '/config/global.php';
include PATH_ADMIN . '/include/class.login.php';
include PATH_ADMIN . '/include/class.threat.php';
Threat::runAllChecks();
include PATH_ADMIN . '/include/fix.req.php';


if (validateInstallAdmin() == false) {
    setRaiseMsg('Please install member module for login website.', _TIME_, 1);
    CustomRedirectToUrl("setup.php", true);
    exit;
}

include('template/' . TEMPLATE_NAME . '/login.php');
