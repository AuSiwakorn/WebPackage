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
include PATH_PLUGIN . '/db/db.php';
include PATH_ADMIN . '/include/function.php';
include PATH_ADMIN . '/include/function_db.php';
include PATH_ADMIN . '/include/fix.req.php';

if (validateInstallAdmin() == false) {
	setRaiseMsg('Please install member module for login website.', _TIME_, 1);
	CustomRedirectToUrl("setup.php", true);
	exit;
}

include('template/' . TEMPLATE_NAME . '/resetpass.php');
