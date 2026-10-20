<?php
/**
 * FILE: admweb/aowebdata/modules/stock/__menu.php
 * ROLE: เมนูของโมดูล stock (AOSTOCK) ในแถบซ้ายของหลังบ้าน — รันทุกหน้าของ admweb ห้าม query ฐานข้อมูลในไฟล์นี้
 * DEPENDS: -
 * TABLES: -
 * TODO:
 *   - [x] ประวัติการแก้ไขดู docs/CHANGELOG.md
 */
$title = 'AOSTOCK';
$_aMenuList = array();
$_aMenuList['title'] = $title;
$_aMenuList['name'] = $title;

$module_name = 'stock';
$link_status = 'index.php?module=' . $module_name . '&mp=status';
$link_admins = 'index.php?module=' . $module_name . '&mp=admins';
$link_settings = 'index.php?module=' . $module_name . '&mp=settings';

$subname = 'stockstatus';
$_aMenuList['subhead'][$subname] = array(
	'name' => 'สถานะระบบ',
	'headertitle' => '',
	'link' => $link_status,
	'target' => '',
	'openPermission' => array('admin'),
	'class' => 'fa fa-boxes',
	'menu' => array(),
);

$subname = 'stockadmins';
$_aMenuList['subhead'][$subname] = array(
	'name' => 'ผู้ดูแล POS',
	'headertitle' => '',
	'link' => $link_admins,
	'target' => '',
	'openPermission' => array('admin'),
	'class' => 'fa fa-user-shield',
	'menu' => array(),
);

$subname = 'stocksettings';
$_aMenuList['subhead'][$subname] = array(
	'name' => 'ตั้งค่า POS',
	'headertitle' => '',
	'link' => $link_settings,
	'target' => '',
	'openPermission' => array('admin'),
	'class' => 'fa fa-sliders-h',
	'menu' => array(),
);
