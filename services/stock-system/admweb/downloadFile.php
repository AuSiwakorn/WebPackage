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
include PATH_PLUGIN . '/mail/function.php';
include PATH_ADMIN . '/include/function.php';
include PATH_ADMIN . '/include/function_db.php';
include PATH_PLUGIN . '/config/global.php';
include PATH_ADMIN . '/include/class.login.php';
include PATH_ADMIN . '/include/fix.req.php';
include PATH_ADMIN . '/include/func.userinfo.php';
include PATH_ADMIN . '/include/func.useronline.php';

login_logout::checkLogin();
include PATH_PLUGIN . '/detect/function.php';
_DETECT_AO();

$module 				  = REQ_get('module', 'get', 'str', '');
$modulePage 		= REQ_get('mp', 'get', 'str', '');
$sef_log 					= REQ_get('sef_log', 'get', 'str', '');
$pg 						    = REQ_get('pg', 'get', 'str', '');
$inc 						   = REQ_get('inc', 'get', 'str', '');
$ty 						    = REQ_get('ty', 'get', 'str', '');
$ac 			     = REQ_get('ac', 'requset', 'str', '');

define("_MODULE_", $module);
define("_MP_", $modulePage);
define("_AC_", $ac);

$lang = (isset($_SESSION['current']) && $_SESSION['current']['lang'] != '') ? $_SESSION['current']['lang'] : DEFAULT_LANGEUAGE;
if (_MODULE_ != '') {
	//include function and function db
	$fmodule = PATH_MODULE . '/' . _MODULE_ . '/function.php';
	$fcore = PATH_CORE . '/' . _MODULE_ . '/function.php';
	$aModuleFunc = is_file($fmodule) ? $fmodule : $fcore;
	if (is_file($aModuleFunc)) {
		include_once($aModuleFunc);
	}

	if (count($aConfig['aModuleUse']) > 0) {
		foreach ($aConfig['aModuleUse'] as $k => $v) {
			$fmodule = PATH_MODULE . '/' . $v . '/__funcGlobal.php';
			$fcore = PATH_CORE . '/' . $v . '/__funcGlobal.php';
			$aFuncGlobal = is_file($fmodule) ? $fmodule : $fcore;
			if (is_file($aFuncGlobal)) {
				include_once($aFuncGlobal);
			}
		}
	}
	if (_MP_ != '') {
		$fmodule = PATH_MODULE . '/' . _MODULE_ . '/main_' . _MP_ . '.php';
		$fcore = PATH_CORE . '/' . _MODULE_ . '/main_' . _MP_ . '.php';
		$mainPageModule = is_file($fmodule) ? $fmodule : $fcore;
	} else {
		$fmodule = PATH_MODULE . '/' . _MODULE_ . '/main_' . _MODULE_ . '.php';
		$fcore = PATH_CORE . '/' . _MODULE_ . '/main_' . _MODULE_ . '.php';
		$mainPageModule = is_file($fmodule) ? $fmodule : $fcore;
	}

	if (!is_file($mainPageModule)) {
		setRaiseMsg('Cannot get file (/modules/' . _MODULE_ . '/main_' . _MP_ . '.php)', _TIME_, 1);
		$mainPageModule = '';
	}
}

if ($mainPageModule != '') {
	include($mainPageModule);
}
