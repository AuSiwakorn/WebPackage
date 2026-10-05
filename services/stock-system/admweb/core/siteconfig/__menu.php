<?php
$title = 'Settings';
$des = 'This section is the basic configuration of the system to support SEO or make it easier to rank on the search site.';
$_aMenuList = array();
$_aMenuList['title'] = $title;
$_aMenuList['name'] = $title;
$_aMenuList['node'] = 'head';

$module_name = 'siteconfig';
$link_config            = 'index.php?module=' . $module_name . '&mp=config';
$link_permit            = 'index.php?module=' . $module_name . '&mp=permit';
$link_detect            = 'index.php?module=' . $module_name . '&mp=detect';
$link_hashfile          = 'index.php?module=' . $module_name . '&mp=hashfile';
$link_email             = 'index.php?module=' . $module_name . '&mp=emailconnect';

$subname = 'settingconfig';
$_aMenuList['subhead'][$subname] = array(
    'name' => 'AdmWeb Template',
    'headertitle' => '',
    'link' => $link_config . '&keysname=config',
    'target' => '',
    'openPermission' => array('admin'),
    'class' => 'fa fa-cogs',
    'menu' => array(),
);

$subname = 'viewLogs';
$_aMenuList['subhead'][$subname] = array(
    'name' => 'Access history',
    'headertitle' => 'LOGS SYSTEM',
    'link' => 'index.php?module=siteconfig&mp=viewLogs',
    'target' => '',
    'openPermission' => array('admin'),
    'class' => 'fa fa-history',
    'menu' => array(),
);
$_aMenuList['subhead'][$subname]['menu'][] = array(
    'name' => 'Admin Login',
    'link' => 'index.php?module=siteconfig&mp=viewLogs',
    'isSearch' => 0,
    'help' => '',
    'target' => '',
);
$_aMenuList['subhead'][$subname]['menu'][] = array(
    'name' => 'Mailing history',
    'link' => 'index.php?module=siteconfig&mp=mailsend',
    'isSearch' => 0,
    'help' => '',
    'target' => '',
);
$_aMenuList['subhead'][$subname]['menu'][] = array(
    'name' => 'View File Logs',
    'link' => 'index.php?module=siteconfig&mp=viewfilelogs',
    'isSearch' => 0,
    'help' => '',
    'target' => '',
);

/* ==================================== */
$subname = 'installation';
$_aMenuList['subhead'][$subname] = array(
    'name' => 'Installation',
    'headertitle' => '',
    'link' => 'index.php?module=siteconfig&mp=db',
    'target' => 'bannermanage',
    'openPermission' => array('admin'),
    'class' => 'fa fa-download',
    'menu' => array(),
);
$_aMenuList['subhead'][$subname]['menu'][] = array(
    'name' => 'System setup',
    'link' => 'index.php?module=siteconfig&mp=db',
    'isSearch' => false,
    'openPermission' => array('admin'),
    'help' => '',
    'target' => '',
);
$_aMenuList['subhead'][$subname]['menu'][] = array(
    'name' => 'Permission',
    'link' => $link_permit,
    'isSearch' => false,
    'openPermission' => array('admin'),
    'help' => '',
    'target' => '',
);

/* ======================================= */
$subname = 'm4';
$_aMenuList['subhead'][$subname] = array(
    'name' => 'Security',
    'headertitle' => '',
    'link' => $link_detect,
    'target' => '',
    'openPermission' => array('admin'),
    'class' => 'fa fa-shield',
    'menu' => array(),
);
$_aMenuList['subhead'][$subname]['menu'][] = array(
    'name' => 'Detect Config',
    'link' => $link_detect,
    'openPermission' => array('admin'),
);
$_aMenuList['subhead'][$subname]['menu'][] = array(
    'name' => 'Ban User ID',
    'link' => 'index.php?module=siteconfig&mp=viewLogsLogin',
    'openPermission' => array('admin'),
);


$subname = 'MonitorCodeScan';
$_aMenuList['subhead'][$subname] = array(
    'name' => 'Monitor Code Scan',
    'headertitle' => '',
    'link' => $link_hashfile,
    'target' => '',
    'openPermission' => array('admin'),
    'class' => 'fa fa-shield',
    'menu' => array(),
);


$subname = 'backupdb';
$_aMenuList['subhead'][$subname] = array(
    'name' => 'Backup Database',
    'headertitle' => '',
    'link' => 'index.php?module=siteconfig&mp=backupdb',
    'target' => '',
    'openPermission' => array('admin'),
    'class' => 'fa fa-database ',
    'menu' => array(),
);

$subname = 'useronline';
$_aMenuList['subhead'][$subname] = array(
    'name' => 'User Online',
    'headertitle' => '',
    'link' => 'index.php?module=siteconfig&mp=userOnline',
    'target' => '',
    'openPermission' => array('admin'),
    'class' => 'fa fa-database ',
    'menu' => array(),
);

$subname = 'm5';
$_aMenuList['subhead'][$subname] = array(
    'name' => 'Email Connection',
    'headertitle' => '',
    'link' => $link_email,
    'target' => '',
    'openPermission' => array('admin'),
    'class' => 'fa fa-paper-plane',
    'menu' => array(),
);
