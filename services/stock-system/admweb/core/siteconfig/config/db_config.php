<?php
$__dbConfigWebsite = array();
$imgConfigDisplay = '<img src="' . URL_CORE . '/siteconfig/images/csstype.jpg"  alt="Theme image">';
$imgConfigDisplay2 = '<img src="' . URL_CORE . '/siteconfig/images/csstheme.jpg"  alt="Theme image">';

$_k = 1;
$__dbConfigWebsite[$_k]['adminiconweb']['title']         = 'Login Logo :';
$__dbConfigWebsite[$_k]['adminiconweb']['des']             = 'อัพโหลดไฟล์นามสกุล (png) ไว้สำหรับสร้างโลโก้ของเว็บขนาดควรจะอยู่ที่ (393px x 59px)';
$__dbConfigWebsite[$_k]['adminiconweb']['type']         = 'file';
$__dbConfigWebsite[$_k]['adminiconweb']['notics']         = '';
$__dbConfigWebsite[$_k]['adminiconweb']['customtags']     = '';

$__dbConfigWebsite[$_k]['adminfavicon']['title']         = 'Browser Default Favicon :';
$__dbConfigWebsite[$_k]['adminfavicon']['des']             = 'อัพโหลดไฟล์นามสกุล (png) ไว้สำหรับสร้างโลโก้บนแท็บ';
$__dbConfigWebsite[$_k]['adminfavicon']['type']         = 'file';
$__dbConfigWebsite[$_k]['adminfavicon']['notics']         = '';
$__dbConfigWebsite[$_k]['adminfavicon']['customtags']     = '';

$__dbConfigWebsite[$_k]['adminlogoheader']['title']         = 'Header Logo :';
$__dbConfigWebsite[$_k]['adminlogoheader']['des']             = 'อัพโหลดไฟล์นามสกุล (png) ไว้สำหรับสร้างโลโก้ส่วนบนของเว็บขนาดควรจะอยู่ที่ (150px x 25px)';
$__dbConfigWebsite[$_k]['adminlogoheader']['type']         = 'file';
$__dbConfigWebsite[$_k]['adminlogoheader']['notics']         = '';
$__dbConfigWebsite[$_k]['adminlogoheader']['customtags']     = '';

$_k++;
$__dbConfigWebsite[$_k]['adminbgweblogin']['title']          = 'BG หน้า Login :';
$__dbConfigWebsite[$_k]['adminbgweblogin']['des']            = 'ถ้าไม่ระบุจะใช้สุ่มภาพให้เอง';
$__dbConfigWebsite[$_k]['adminbgweblogin']['type']           = 'file';
$__dbConfigWebsite[$_k]['adminbgweblogin']['notics']         = '';
$__dbConfigWebsite[$_k]['adminbgweblogin']['customtags']     = '';

$_k++;
$__dbConfigWebsite[$_k]['csstype']['title']         = 'Color type (Theme) :';
$__dbConfigWebsite[$_k]['csstype']['des']             = $imgConfigDisplay;
$__dbConfigWebsite[$_k]['csstype']['type']         = 'select';
$__dbConfigWebsite[$_k]['csstype']['notics']         = '';
$__dbConfigWebsite[$_k]['csstype']['customtags']     = '';
$__dbConfigWebsite[$_k]['csstype']['data']     = array(
    'type-e' => 'ค่าพื้นฐาน',
    'type-a' => 'Header',
    'type-b' => 'Brand',
    'type-c' => 'Navigation',
    'type-d' => 'Full Top Bar'
);

$__dbConfigWebsite[$_k]['csstheme']['title']         = 'Color (Theme) :';
$__dbConfigWebsite[$_k]['csstheme']['des']             = $imgConfigDisplay2;
$__dbConfigWebsite[$_k]['csstheme']['type']         = 'select';
$__dbConfigWebsite[$_k]['csstheme']['notics']         = '';
$__dbConfigWebsite[$_k]['csstheme']['customtags']     = '';
$__dbConfigWebsite[$_k]['csstheme']['data']     = array(
    'theme-dark' => 'สีดำ',
    'theme-gray' => 'สีเทา',
    'theme-navy' => 'สีน้ำเงินกรมท่า',
    'theme-ocean' => 'สีน้ำทะเล',
    'theme-lime' => 'สีเขียวมะนาว',
    'theme-purple' => 'สีม่วง',
    'theme-dust' => 'สีส้ม',
    'theme-mint' => 'สีเขียวมิ้นท์',
    'theme-yellow' => 'สีเหลือง',
    'theme-well-red' => 'สีส้มแดง',
    'theme-coffee' => 'สีน้ำตาล',
    'theme-prickly-pear' => 'สีแดงเข้ม'
);

$__dbConfigWebsite[$_k]['cssfulltheme']['title']         = 'Theme Dark Full :';
$__dbConfigWebsite[$_k]['cssfulltheme']['des']             = '';
$__dbConfigWebsite[$_k]['cssfulltheme']['type']         = 'checkbox';
$__dbConfigWebsite[$_k]['cssfulltheme']['notics']         = 'Theme Dark';
$__dbConfigWebsite[$_k]['cssfulltheme']['customtags']     = '';

if (defined('_IS_COOKIE_LOGIN_TIME_') && _IS_COOKIE_LOGIN_TIME_ == 'on') {
    $_k++;
    $__dbConfigWebsite[$_k]['loginfaillimit']['title']          = 'กำหนดจำนวนครั้งของการล็อกอินผิด :';
    $__dbConfigWebsite[$_k]['loginfaillimit']['des']            = 'กรอกจำนวนเลข (หน่วยครั้ง)';
    $__dbConfigWebsite[$_k]['loginfaillimit']['type']           = 'text';
    $__dbConfigWebsite[$_k]['loginfaillimit']['notics']         = '';
    $__dbConfigWebsite[$_k]['loginfaillimit']['customtags']     = '';

    $__dbConfigWebsite[$_k]['loginbantime']['title']          = 'กำหนดเวลาแบนของการล็อกอิน :';
    $__dbConfigWebsite[$_k]['loginbantime']['des']            = 'กรอกจำนวนเลข (หน่วยนาที)';
    $__dbConfigWebsite[$_k]['loginbantime']['type']           = 'text';
    $__dbConfigWebsite[$_k]['loginbantime']['notics']         = '';
    $__dbConfigWebsite[$_k]['loginbantime']['customtags']     = '';

    $__dbConfigWebsite[$_k]['cookietime']['title']          = 'กำหนดเวลาออนไลน์ในหน้าเพจ :';
    $__dbConfigWebsite[$_k]['cookietime']['des']            = 'กรอกจำนวนเลข (หน่วยนาที)';
    $__dbConfigWebsite[$_k]['cookietime']['type']           = 'text';
    $__dbConfigWebsite[$_k]['cookietime']['notics']         = '';
    $__dbConfigWebsite[$_k]['cookietime']['customtags']     = '';
}

$_k++;
$__dbConfigWebsite[$_k]['editortype']['title']         = 'ชื่อ Editor ที่ใช้งาน :';
$__dbConfigWebsite[$_k]['editortype']['des']             = 'เลือก Editor ที่ต้องการใช้งาน';
$__dbConfigWebsite[$_k]['editortype']['type']         = 'select';
$__dbConfigWebsite[$_k]['editortype']['notics']         = '';
$__dbConfigWebsite[$_k]['editortype']['customtags']     = '';
$__dbConfigWebsite[$_k]['editortype']['data']     = array(
    'ckeditor' => 'ckeditor',
    'ckeditor5' => 'ckeditor5',
    'summernote' => 'summernote',
);

$__dbConfigWebsite[$_k]['previewonoff']['title']         = 'เปิด - ปิด Preview :';
$__dbConfigWebsite[$_k]['previewonoff']['des']             = '';
$__dbConfigWebsite[$_k]['previewonoff']['type']         = 'checkbox';
$__dbConfigWebsite[$_k]['previewonoff']['notics']         = '';
$__dbConfigWebsite[$_k]['previewonoff']['customtags']     = '';

