<?php
include(dirname(__FILE__) . '/../mainApi.php');
include dirname(__FILE__) . 'oApi.php';

$_aMember = @$_SESSION['member'];
$lang = (isset($lang) && $lang != '') ? $lang : 'th';
/* ============================== */
///////// fix test request /////////
/* ===============================*/
foreach ($_REQUEST as $k => $v) {
	if (is_string($v)) {
		$_REQUEST[$k] = fixinjection($v);
	}
}

foreach ($_GET as $k => $v) {
	if (is_string($v)) {
		$_GET[$k] = fixinjection($v);
	}
}
//==================================//
$aCounter = Counter_get($n = 6);
/*
function print_img_file_type($txt)
{
	$txt = end(explode('.', $txt));
	if (is_file(PATH_WEB_ROOT . '/img/' . $txt . '.png')) {
		echo URL_WEB_ROOT . '/img/' . $txt . '.png';
	} else {
		echo URL_WEB_ROOT . '/img/icontxt1_03.png';
	}
}
*/