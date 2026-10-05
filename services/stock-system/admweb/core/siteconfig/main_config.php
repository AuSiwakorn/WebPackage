<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp|keysname', 'สามารถเปิด ตั้งค่าระบบ ได้', 'redirect', 'SET');
$lang 		= getCurrentLang();
$mp		 	= REQ_get('mp', 'request', 'str', '');
$module 	= REQ_get('module', 'request', 'str', '');
$ac 		= REQ_get('ac', 'request', 'str', '');
$inc 		= REQ_get('inc', 'request', 'str', 'config');
$keysname 	= REQ_get('keysname', 'request', 'str', 'config');
define("PLUGIN_INC", 'config');

$aSettagsname = array();
$aSettagsname['config'] = 'ตั้งค่าพื้นฐานเว็บไซต์';
$aSettagsname['specialmenu'] = 'ปรับแต่ง Special Menu';
//$aSettagsname['specialmenu'] = 'แบนเนอร์ หน้าอื่นๆ';

$aPgConfig = array();
$fmodule = PATH_MODULE . '/' . _MODULE_ . '/config/db_' . $keysname . '.php';
$fcore = PATH_CORE . '/' . _MODULE_ . '/config/db_' . $keysname . '.php';
$pathConfig = is_file($fmodule) ? $fmodule : $fcore;
$aPgConfig['upload_extention'] 	= array('pdf', 'doc', 'jpg', 'gif', 'png', 'ico');
$aPgConfig['load_config_file'] 		= $pathConfig;


$tagname['name'] = (@$aSettagsname[$keysname] != '') ? $aSettagsname[$keysname] : 'ตั้งค่าระบบ';


/////////////////////////////////////////////////////////////////////
////////////////////////// INC PLUGIN /////////////////////////////
/////////////////////////////////////////////////////////////////////
if (is_file(PATH_PLUGIN . '/' . PLUGIN_INC . '/function.php')) {
	include(PATH_PLUGIN . '/' . PLUGIN_INC . '/function.php');
} else {
	echo '<li> Cannon get Function. ( ' . PATH_PLUGIN . '/' . PLUGIN_INC . '/function.php )</li> ';
}
if (is_file(PATH_PLUGIN . '/' . PLUGIN_INC . '/inc_' . $inc . '.php')) {
	include(PATH_PLUGIN . '/' . PLUGIN_INC . '/inc_' . $inc . '.php');
} else {
	echo '<li> INC page is fail. ( ' . PATH_PLUGIN . '/' . PLUGIN_INC . '/inc_' . $inc . '.php )</li>';
}
