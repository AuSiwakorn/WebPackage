<?php
/**
 * FILE: admweb/aowebdata/modules/stock/__menu.php
 * ROLE: เมนูของโมดูล stock (AOSTOCK) ในแถบซ้ายของหลังบ้าน — รันทุกหน้าของ admweb ห้าม query ฐานข้อมูลในไฟล์นี้
 * DEPENDS: -
 * TABLES: -
 * TODO:
 *   - [x] เมนูสถานะระบบ (ช่วงที่ 4)
 *   - [x] เมนูผู้ดูแล POS (ช่วงที่ 5)
 *   - [x] ช่วงที่ 9: เมนูเปิด–ปิดเมนูของ POS (main_settings.php) — การแจ้งเตือนตั้งในหน้า POS ของผู้ดูแล (ตกลงไว้ ข้อ 1ก)
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
	'name' => 'เปิด–ปิดเมนู POS',
	'headertitle' => '',
	'link' => $link_settings,
	'target' => '',
	'openPermission' => array('admin'),
	'class' => 'fa fa-toggle-on',
	'menu' => array(),
);
