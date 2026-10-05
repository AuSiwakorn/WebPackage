<?php
include(dirname(__FILE__) . '/../mainApi.php');
include dirname(__FILE__) . 'oApi.php';
$set 	= REQ_get('set', 'get', 'str');
$redir = REQ_get('redir', 'get', 'str');
$lang 	= (@$set != '') ? $set : DEFAULT_LANGEUAGE;

$oApi->setLanguage($lang);
if (@$_SERVER['HTTP_REFERER'] != '' && $redir == '') {
	$redir = $_SERVER['HTTP_REFERER'];
}

if (preg_match("/" . DOMAIN_NAME . "/i", $redir)) {
	/*/////=======เก็บไว้รอระบบเปลี่ยนภาษา========/////
	if ($lang == 'th') {
		$str = str_replace('/en/', '/'.$lang.'/', $redir);
	} else {
		$str = str_replace('/th/', '/'.$lang.'/', $redir);
	}
	*/
	@header("Location:" . $redir);
	exit;
} else {
	@header("Location:" . URL_WEB_ROOT);
}
exit;
