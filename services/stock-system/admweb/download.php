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

$hash 		= REQ_get('hash', 'get', 'str', '');
$__get  = hashTextDecode($hash);

$lang = (isset($_SESSION['current']) && $_SESSION['current']['lang'] != '') ? $_SESSION['current']['lang'] : DEFAULT_LANGEUAGE;
if ($module != '') {
	//include function and function db
	$fmodule = PATH_MODULE . '/' . $module . '/function.php';
	$fcore = PATH_CORE . '/' . $module . '/function.php';
	$aModuleFunc = is_file($fmodule) ? $fmodule : $fcore;
	if (is_file($aModuleFunc)) {
		include_once($aModuleFunc);
	}
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

$oUser = login_logout::getLoginData();
if ($oUser->status == 'member') {
	$aGetTechnicalByID = _Get_Technical_ByUSERANDID($__get['id']);
} else {
	$aGetTechnicalByID = member_Get_Technical_ById($__get['id']);
}

//$aGetTechnicalByID['id']
//$aGetTechnicalByID['file']
if ($aGetTechnicalByID['file'] != '') {
	$file = PATH_UPLOAD . '/' . $aGetTechnicalByID['file'];
	if (is_file($file)) {
		addDownloadHistory($oUser->user_id, $__get['id']);
		$fname = explode('/', $aGetTechnicalByID['file']);
		$fname = end($fname);

		$mimeTypes = array(
			'pdf' => 'application/pdf',
			'txt' => 'text/plain',
			'html' => 'text/html',
			'exe' => 'application/octet-stream',
			'zip' => 'application/zip',
			'doc' => 'application/msword',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',
			'gif' => 'image/gif',
			'png' => 'image/png',
			'jpeg' => 'image/jpg',
			'jpg' => 'image/jpg',
			'php' => 'text/plain'
		);

		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment;filename=" . $fname);
		header("Content-Length:" . filesize($file));
		header("Content-Transfer-Encoding: binary ");
		readfile($file);

		_Update_Technical_download($__get['id']);
	}
}
