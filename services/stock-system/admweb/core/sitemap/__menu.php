<?php
$title      = 'Sitemap';
$des        = '';
$_aMenuList = array();
$_aMenuList['title'] = $title;
$_aMenuList['name']  = $title;
$_aMenuList['node']  = 'head';

################# MENU ####################
$subname = 'sitemap';
$_aMenuList['subhead'][$subname] = array(
	'name' => 'Sitemap',
	'headertitle' => '',
	'link' => 'index.php?module=sitemap&mp=sitemap',
	'target' => '',
	'openPermission' => array('admin'),
	'class' => 'fa fa-map',
	'menu' => array(),
);
