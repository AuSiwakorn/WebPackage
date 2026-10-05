<?php
$title = 'SEO';
$_aMenuList = array();
$_aMenuList['title'] = $title;
$_aMenuList['name'] = $title;

$module_name = 'seo';
$link_metas             = 'index.php?module=' . $module_name . '&mp=metas';
$link_seolist           = 'index.php?module=' . $module_name . '&mp=seolist';
$link_seogrouplist      = 'index.php?module=' . $module_name . '&mp=seogrouplist';
$link_seotxt            = 'index.php?module=' . $module_name . '&mp=seotxt';

$subname = 'seohome';
$_aMenuList['subhead'][$subname] = array(
    'name' => 'META TAGs',
    'headertitle' => '',
    'link' => $link_metas . '&metakey=home',
    'target' => '',
    'openPermission' => array('admin'),
    'class' => 'fa fa-cogs" aria-hidden="true"',
    'menu' => array(),
);

$_aMenuList['subhead'][$subname]['menu'][] = array(
    'name' => 'หน้าแรก',
    'link' => $link_metas . '&metakey=home',
);

$_aMenuList['subhead'][$subname]['menu'][] = array(
    'name' => 'บริการ',
    'link' => $link_metas . '&metakey=service',
);

$_aMenuList['subhead'][$subname]['menu'][] = array(
    'name' => 'เกี่ยวกับเรา',
    'link' => $link_metas . '&metakey=aboutus',
);

$_aMenuList['subhead'][$subname]['menu'][] = array(
    'name' => 'บทความ',
    'link' => $link_seolist . '&metakey=blogs',
);

$_aMenuList['subhead'][$subname]['menu'][] = array(
    'name' => 'ติดต่อ',
    'link' => $link_metas . '&metakey=contact',
);

/* ================================== */
$subname = 'addon';
$_aMenuList['subhead'][$subname] = array(
    'name' => 'AddOn Script',
    'link' => $link_seotxt,
    'openPermission' => array('admin'),
    'class' => 'fa fa-cogs fa-align-justify',
    'menu' => array(),
);
/* ================================== */
$subname = 're301';
$_aMenuList['subhead'][$subname] = array(
    'name' => '301 Redirect',
    'link' => 'index.php?module=' . $module_name . '&mp=redirect301',
    'openPermission' => array('admin'),
    'class' => 'fa fa-cogs fa-align-justify',
    'menu' => array(),
);
