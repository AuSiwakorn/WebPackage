<?php
$metakey = REQ_get('metakey', 'request', 'str', '');
PERMIT::_PERMIT(_MODULE_, 'module|mp|metakey', 'สามารถเปิด ' . $metakey . ' ได้', 'redirect', 'SET');
define("PLUGIN_INC", 'seo');

if (is_file(PATH_PLUGIN . '/' . PLUGIN_INC . '/inc_main_metas.php')) {
	include(PATH_PLUGIN . '/' . PLUGIN_INC . '/inc_main_metas.php');
}
