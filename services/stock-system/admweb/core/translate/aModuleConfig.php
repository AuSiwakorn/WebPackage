<?php
$fileSql = dirname(__FILE__) . '/database.sql';
$aTablename = array();

$aPermission = array(
	PATH_UPLOAD . '/translate',
);

$name = 'translate';
$aModulePermission[$name] = array();
$aModulePermission[$name]['name'] = 'แปลภาษาในเว็บ';
$aModulePermission[$name]['permission']['managetranslate'] = 'จัดการข้อมูลภาษาต่างๆ';
