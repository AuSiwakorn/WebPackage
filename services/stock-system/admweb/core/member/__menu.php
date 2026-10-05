<?php
$title = 'MEMBER';
$des = 'You can manage members and manage system administrators in this section. Can set access rights in each of the various modules.';
$_aMenuList = array();
$_aMenuList['title'] = $title;
$_aMenuList['name'] = $title;
$_aMenuList['node'] = 'head';

$adminCount = Func_CountAdmin();
$memberCount = Func_CountMember();

################# MENU ####################
$subname = 'AdminManage';
$_aMenuList['subhead'][$subname] = array(
	'name' => 'Admin Manage',
	'headertitle' => 'ADMIN OPTION',
	'link' => 'index.php?module=member&mp=admin',
	'target' => '',
	'openPermission' => array('admin'),
	'class' => 'fa fa-user-circle',
	'num' => $adminCount,
	'menu' => array(),
);

/* ======================================= */
$subname = 'UserList';
$_aMenuList['subhead'][$subname] = array(
	'name' => 'User List',
	'headertitle' => '',
	'link' => 'index.php?module=member&mp=user',
	'target' => '',
	'openPermission' => array('admin'),
	'class' => 'fa fa-user-circle',
	'num' => $memberCount,
	'menu' => array(),
);

/* ======================================= */
$subname = 'AdminGroup';
$_aMenuList['subhead'][$subname] = array(
	'name' => 'กลุ่มผู้ดูแล (Preset)',
	'headertitle' => '',
	'link' => 'index.php?module=member&mp=group',
	'target' => '',
	'openPermission' => array('admin'),
	'class' => 'fa fa-users',
	'menu' => array(),
);
