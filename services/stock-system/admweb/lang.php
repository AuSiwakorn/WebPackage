<?php
header("X-Robots-Tag: noindex, nofollow", true);
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
// header("Content-Security-Policy: default-src 'self'; 
//         script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; 
//         style-src  'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; 
//         font-src   'self' https://fonts.gstatic.com;");
session_start();

///////// config /////////
include dirname(__FILE__) . '/include/conf.ini.php';
include PATH_PLUGIN . '/db/db.php';
include PATH_ADMIN . '/include/function.php';
include PATH_ADMIN . '/include/function_db.php';
include PATH_ADMIN . '/include/fix.req.php';

$set = (isset($_GET['set']) && $_GET['set'] != '') ? $_GET['set'] : DEFAULT_LANGEUAGE;
$referer = (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '')
	? $_SERVER['HTTP_REFERER']
	: $_SESSION['url_current'];

if ($referer == '') {
	$referer = URL_ADMIN;
}
/////set new lang/////
$set = _input_validate_str($set);
$_SESSION['current']['lang']	= $set;
//redirect to default
CustomRedirectToUrl($referer,/*is_NotBuilLink*/ true);
echo '<meta http-equiv="refresh" content="0;URL=' . $referer . '" />';
exit;
