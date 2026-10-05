<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด System setup', 'redirect', 'UNSET');
include('aModuleConfig.php');
define("PLUGIN_INC", 'db');
if (is_file(PATH_PLUGIN . '/' . PLUGIN_INC . '/inc_install.php')) {
	include(PATH_PLUGIN . '/' . PLUGIN_INC . '/inc_install.php');
}
