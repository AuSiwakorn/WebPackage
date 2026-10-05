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

$aModuleUse = (isset($aModuleUse) && count($aModuleUse) > 0) ? $aModuleUse : array();

//set default session
$_SESSION['url_current'] = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$_SESSION['url_referer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

///////////// get lang ///////////////
if (isset($_SESSION['current']) && $_SESSION['current']['lang'] != '') {
	$lang = $_SESSION['current']['lang'];
} else {
	$lang = DEFAULT_LANGEUAGE;
	$_SESSION['current']['lang'] = DEFAULT_LANGEUAGE;
}

////////// fix set Error lang /////////
if (!is_array(($_SESSION['current']))) {
	unset($_SESSION['current']);
}

include PATH_PLUGIN . '/db/db.php';
include PATH_PLUGIN . '/mail/function.php';
include PATH_PLUGIN . '/translate/function.php';
include PATH_PLUGIN . '/detect/function.php';
include PATH_PLUGIN . '/uploadfile/UpFile.php';
include PATH_PLUGIN . '/config/global.php';
include PATH_ADMIN . '/include/function.php';
include PATH_ADMIN . '/include/function_db.php';
include PATH_ADMIN . '/include/func.userinfo.php';
include PATH_ADMIN . '/include/func.useronline.php';
include PATH_ADMIN . '/include/class.threat.php';
Threat::runAllChecks();
include PATH_ADMIN . '/include/fix.req.php';

/* ///////////////////// */
global $aModules;
/* //////////////////// */

// default path
$mainPage = PATH_ADMIN . '/main.php';
$module			= REQ_get('module', 'get', 'str', 'main');
$modulePage 	= REQ_get('mp', 'get', 'str', '');
$sef_log		= REQ_get('sef_log', 'request', 'str', '');
$pg				= REQ_get('pg', 'get', 'str', '');
$inc			= REQ_get('inc', 'get', 'str', '');
$ac				= REQ_get('ac', 'requset', 'str', '');
$qsearch        = REQ_get('qsearch', 'requset', 'str', '');

define("_MODULE_", $module);
define("_MP_", $modulePage);
define("_AC_", $ac);

if (_MODULE_ != 'main') {
	if (_MODULE_ == 'none') {
		$aModuleFunc = PATH_PLUGIN . '/' . $pg . '/function.php';
		if (is_file($aModuleFunc)) {
			include_once($aModuleFunc);
		}

		$mainPage = PATH_PLUGIN . '/' . $pg . '/inc_' . $inc . '.php';
	} else {
		//include function and function db
		$fmodule = PATH_MODULE . '/' . _MODULE_ . '/function.php';
		$fcore = PATH_CORE . '/' . _MODULE_ . '/function.php';
		$aModuleFunc = is_file($fmodule) ? $fmodule : $fcore;
		if (is_file($aModuleFunc)) {
			include_once($aModuleFunc);
		}

		// include main
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
		} else {
			$mainPage = $mainPageModule;
		}
	}
} elseif (_MODULE_ == 'main' || _MODULE_ == '') {
	$mainPage = PATH_ADMIN . '/main.php';
	if ($pg != '' && $inc != '') {
		if (is_file(PATH_PLUGIN . '/' . $pg . '/inc_' . $inc . '.php')) {
			$mainPage = PATH_PLUGIN . '/' . $pg . '/inc_' . $inc . '.php';
		}
	}
}

//================================//
//include function custom//
//===============================//
if (is_file(PATH_ADMIN . '/function/func.php')) {
	include PATH_ADMIN . '/function/func.php';
}

/* =========== Add Check Include Global Function in ModuleUse ===========*/
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

/* ================================ */
////////////// hooks func ////////////
/* ================================ */
$aHooksMain = array();
foreach ($aModuleUse as $k => $v) {
	$fmodule = PATH_MODULE . '/' . $v . '/hooks/hooks_function.php';
	$fcore = PATH_CORE . '/' . $v . '/hooks/hooks_function.php';
	$hooksFunc = is_file($fmodule) ? $fmodule : $fcore;
	if (is_file($hooksFunc)) {
		include_once($hooksFunc);
	}
}

/* ================================ */
//////////////////////////////////////
/* ================================ */
include_once(PATH_ADMIN . '/include/class.login.php');
include_once(PATH_ADMIN . '/include/class.permit.php');

switch ($sef_log) {
	case 'login':
		$sef_secer = REQ_get('sef_secer', 'post', 'str', '');
		$sef_secer = md5($sef_secer);
		$username  = REQ_get('uname_' . $sef_secer, 'post', 'str', '');
		$password  = (isset($_POST['password']) && $_POST['password'] != '') ? $_POST['password'] : '';
		login_logout::adminLogin(trim($username), trim($password), '');
		exit;
		break;
	case 'twofa':
		$sef_secer = REQ_get('sef_secer', 'post', 'str', '');
		$sef_secer = md5($sef_secer);
		$twofa_code = REQ_get('twofa_code_' . $sef_secer, 'post', 'str', '');
		login_logout::ATH_Login_2fa($twofa_code);
		exit;
		break;
	case 'logout':
		login_logout::adminLogout();
		exit;
		break;
	case 'forgotpassword':

		$email = (isset($_POST['email']) && $_POST['email'] != '') ? $_POST['email'] : '';
		login_logout::forgotpassword($email);
		exit;
	default:
		$ok = login_logout::checkLogin();
		break;
}
///////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////

if (_AC_ === 'signon' && (login_logout::canSignonInit() || !empty($_SESSION['signon_origin_id']))) {
	$signonId = REQ_get('id', 'request', 'int', 0);
	if (login_logout::signon($signonId)) {
		setRaiseMsg('สิงร่างเป็น ' . login_logout::getAdminUsername() . ' เรียบร้อย', _TIME_, 0);
	} else {
		setRaiseMsg('สิงร่างไม่สำเร็จ (ไม่มีสิทธิ์ หรือไม่พบผู้ใช้)', _TIME_, 1);
	}
	CustomRedirectToUrl('index.php?module=member&mp=admin');
	exit;
}

_DETECT_AO();

$aSession = $_SESSION;

/* ================================ */
///// check validate path deleted ////
/* ================================ */
if (is_file(PATH_ADMIN . '/include/validate.php')) {
	include PATH_ADMIN . '/include/validate.php';
}

include TEMPLATE_PATH . '/header.php';
include TEMPLATE_PATH . '/mainGlobal.php';
include TEMPLATE_PATH . '/footer.php';

/* ================================ */
////////// Hook Index Req ////////////
/* ================================ */
foreach ($aModuleUse as $k => $v) {
	$fmodule = PATH_MODULE . '/' . $v . '/hooks/hook_index_req.php';
	$fcore = PATH_CORE . '/' . $v . '/hooks/hook_index_req.php';
	$hooksIndex = is_file($fmodule) ? $fmodule : $fcore;
	if (is_file($hooksIndex)) {
		include($hooksIndex);
	}
}
if (_MP_ != '') {
	PERMIT::PERMIT_REQ();
}

$oUser = login_logout::getLoginData();
if ($oUser->status === 'member') {
	$aMember = getUserMemberById($oUser->user_id);
	if ($aMember['pw'] === _builpassword(PW_RESET)) {
		if (_MODULE_ !== "member" || _MP_ !== "changePass") {
			$_SESSION['rejectPwd'] = false;
			CustomRedirectToUrl("index.php?module=member&mp=changePass");
			exit;
		}
	} else {
		$_SESSION['rejectPwd'] = true;
	}
}

/* ================================ */
///// debug session by Tao ////
/* ================================ */
// if (DEBUG_VIEW == true) {
// 	pre($_SESSION);
// }
ob_end_flush();
