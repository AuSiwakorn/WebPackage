<?php
@session_start();
error_reporting(0);

///////////// config /////////////////
include dirname(__FILE__) . '/include/conf.ini.php';
include PATH_PLUGIN . '/db/db.php';
include PATH_PLUGIN . '/config/global.php';
include PATH_PLUGIN . '/mail/function.php';
include PATH_PLUGIN . '/translate/api.php';
include PATH_ADMIN . '/include/function_api.php';
include PATH_ADMIN . '/include/func.useronline.php';
include PATH_ADMIN . '/include/class.threat.php';
Threat::runAllChecks();
include PATH_ADMIN . '/include/fix.req.php';

///////////// get lang ///////////////
$lang = api_getCurrentLang();

if (is_file(PATH_ADMIN . '/function/func.php')) {
	include PATH_ADMIN . '/function/func.php';
}

//////////// language ////////////////
foreach ($aConfig['language'] as $k => $v) {
	$languagefile = PATH_AOWEBDATA . '/languages/' . $k . '.php';
	if (is_file($languagefile) && $k != $lang) {
		include $languagefile;
	}
}

$currentlanguagefile = PATH_AOWEBDATA . '/languages/' . $lang . '.php';
if (is_file($currentlanguagefile)) {
	include $currentlanguagefile;
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

// load api all module
if (isset($aModuleUse) && count($aModuleUse) > 0) {
	foreach ($aModuleUse as $k => $v) {
		$fmodule = PATH_MODULE . '/' . $v . '/api.php';
		$fcore = PATH_CORE . '/' . $v . '/api.php';
		$modulePath = is_file($fmodule) ? $fmodule : $fcore;
		if (is_file($modulePath) && $v != '') {
			include_once $modulePath;
		}
	}
}
