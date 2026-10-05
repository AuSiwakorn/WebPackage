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

$module 				 = REQ_get('module', 'get', 'str', '');
$modulePage 			 = REQ_get('mp', 'get', 'str', '');
$sef_log 				 = REQ_get('sef_log', 'get', 'str', '');
$pg 					 = REQ_get('pg', 'get', 'str', '');
$inc 					 = REQ_get('inc', 'get', 'str', '');
$ty 				     = REQ_get('ty', 'get', 'str', '');
$option 			     = REQ_get('option', 'get', 'str', '');
$ac 					 = REQ_get('ac', 'requset', 'str', '');

define("_MODULE_", $module);
define("_MP_", $modulePage);
define("_AC_", $ac);

$lang 						= (isset($_SESSION['current']) && $_SESSION['current']['lang'] != '') ? $_SESSION['current']['lang'] : DEFAULT_LANGEUAGE;
if (_MODULE_ != '') {
	//include function and function db
	$fmodule = PATH_MODULE . '/' . _MODULE_ . '/function.php';
	$fcore = PATH_CORE . '/' . _MODULE_ . '/function.php';
	$aModuleFunc = is_file($fmodule) ? $fmodule : $fcore;
	if (is_file($aModuleFunc)) {
		include_once($aModuleFunc);
	}
	// include main
	if ($ty == 'plugin') {
		$fmodule = PATH_MODULE . '/' . _MODULE_ . '/main_' . _MP_ . '.php';
		$fcore = PATH_CORE . '/' . _MODULE_ . '/main_' . _MP_ . '.php';
		$mainPageModule = is_file($fmodule) ? $fmodule : $fcore;
		if (!is_file($mainPageModule)) {
			setRaiseMsg('Cannot get file (/modules/' . _MODULE_ . '/main_' . _MP_ . '.php)', _TIME_, 1);
			$mainPageModule = '';
		}
	} else {
		if (_MP_ != '') {
			$fmodule = PATH_MODULE . '/' . _MODULE_ . '/popup_' . _MP_ . '.php';
			$fcore = PATH_CORE . '/' . _MODULE_ . '/popup_' . _MP_ . '.php';
			$mainPageModule = is_file($fmodule) ? $fmodule : $fcore;
		} else {
			$fmodule = PATH_MODULE . '/' . _MODULE_ . '/popup_' . _MODULE_ . '.php';
			$fcore = PATH_CORE . '/' . _MODULE_ . '/popup_' . _MODULE_ . '.php';
			$mainPageModule = is_file($fmodule) ? $fmodule : $fcore;
		}

		if (!is_file($mainPageModule)) {
			setRaiseMsg('Cannot get file (/modules/' . _MODULE_ . '/popup_' . _MP_ . '.php)', _TIME_, 1);
			$mainPageModule = '';
		}
	}
} elseif (_MODULE_ == '' && $ty == 'plugin') {
	$mainPageModule = PATH_PLUGIN . '/' . $pg . '/inc_' . $inc . '.php';
	$filefunction = PATH_PLUGIN . '/' . $pg . '/function.php';
	if (is_file($filefunction)) {
		include($filefunction);
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link type="text/css" href="template/version2/styles.css" rel="stylesheet" />
	<title>POPUP VIEW</title>

	<?php exportJSAndCSS($aConfig['aAddJsAndCss']); ?>
	<script type="text/javascript">
		function confirmClickAction() {
			agree = confirm("Please confirm action ?");
			if (agree) {
				return true;
			} else {
				return false;
			}
		}
	</script>

	<style type="text/css" media="print">
		.hidforprint {
			display: none;
		}
	</style>
</head>

<body>
	<!-- comment start print icon -->
	<?php if ($option == 'print') { ?>
		<div class="hidforprint">
			<table width="100%" border="0" cellspacing="0" cellpadding="5">
				<tr>
					<td align="right">
						<a onclick="javascript:window.print();" href="javascript:;">
							<img src="template/images/action_print.gif" border="0" />
						</a>
					</td>
				</tr>
			</table>
		</div>
	<?php } ?>

	<!-- comment main popup -->
	<?php
	if ($mainPageModule != '') {
		include($mainPageModule);
	}
	?>

	<!-- comment start print icon -->
	<?php if ($option == 'print') { ?>
		<div class="hidforprint">
			<table width="100%" border="0" cellspacing="0" cellpadding="5">
				<tr>
					<td align="right">
						<a onclick="javascript:window.print();" href="javascript:;">
							<img src="template/images/action_print.gif" border="0" />
						</a>
					</td>
				</tr>
			</table>
		</div>
	<?php } ?>
</body>

</html>