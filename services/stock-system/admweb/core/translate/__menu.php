<?php
$_aKeysTrans = array(
	'Header',
	'Menu',
	'SideBar',
	'Home',
	'AboutUs',
	'ContactUs',
	'Services',
	'OurTeam',
	'Footer'
);

$title = 'TRANSLATE';
$des = 'จัดการแปลภาษา';
$_aMenuList = array();
$_aMenuList['title'] = $title;
$_aMenuList['name'] = $title;
$_aMenuList['node'] = 'head';
/* ==================================== */
$subname = 'trans';
$_aMenuList['subhead'][$subname] = array(
	'name' => 'แปลภาษา',
	'headertitle' => 'แปลภาษาในเว็บ',
	'link' => 'index.php?module=translate',
	'target' => '',
	'openPermission' => array('admin'),
	'class' => 'zoomxicon',
	'menu' => array(),
);
//////////////////////////////////////////////////////////////////////////////////
/* ================All================= */
$_aMenuList['subhead'][$subname]['menu'][] = array(
	'name' => 'แปลภาษาทั้งหมด',
	'headertitle' => 'แปลภาษาในเว็บ',
	'link' => 'index.php?module=translate',
	'target' => '',
	'openPermission' => array('admin'),
	'class' => 'zoomxicon',
	'menu' => array(),
);

foreach ($_aKeysTrans as $v) {
	//////////////////////////////////////////////////////////////////////////////////
	/* ================Header================= */
	$_aMenuList['subhead'][$subname]['menu'][] = array(
		'name' => $v,
		'link' => 'index.php?module=translate&keysname=' . $v,
		'target' => '',
		'openPermission' => array('admin'),
		'class' => 'zoomxicon',
		'menu' => array(),
	);
}


/* ================setting================= */
$subname = 'transsetting';
$_aMenuList['subhead'][$subname] = array(
	'name' => 'ตั้งค่า',
	'headertitle' => '',
	'link' => '',
	'target' => '',
	'openPermission' => array('admin'),
	'class' => 'fa fa-cogs',
	'menu' => array(),
);

$_aMenuList['subhead'][$subname]['menu'][] = array(
	'name' => 'คีย์ที่ยังไม่ได้แปล',
	'link' => 'index.php?module=translate&mp=req',
	'target' => '',
	'openPermission' => array('admin'),
	'class' => 'zoomxicon',
	'menu' => array(),
);
$_aMenuList['subhead'][$subname]['menu'][] = array(
	'name' => 'ล้างข้อมูลที่ตั้งค่า',
	'link' => 'index.php?module=translate&mp=clear',
	'target' => '',
	'openPermission' => array('admin'),
	'class' => 'zoomxicon',
	'menu' => array(),
);
/* ================setting================= */