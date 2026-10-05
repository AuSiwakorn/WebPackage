<?php
$sqlArray[_DBPREFIX_ . 'site_configs'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_configs` (
	`conf_id` int( 11 ) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key ของตาราง',
	`keywords` varchar( 64 ) NOT NULL COMMENT 'ชื่อ key สำหรับอ้างอิงค่า config',
	`val` text NOT NULL COMMENT 'ค่าของ config',
	PRIMARY KEY ( `conf_id` ) ,
	KEY `keywords` ( `keywords` )
) ENGINE = MYISAM DEFAULT CHARSET = utf8 AUTO_INCREMENT =1;
";

/*
 * repassword is the case where the password is changed because it is forgotten.
 * The system will need to create a new password to store it in this field. And send the code via email.
 * When a new login with this code will have to change the old code to this new code, but if there is a login with the old code.
 * The new code will be canceled by deleting the value.
 */

$sqlArray[_DBPREFIX_ . 'member_user'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "member_user` (
  `user_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของผู้ใช้งาน',
  `username` varchar(50) NOT NULL default '' COMMENT 'ชื่อผู้ใช้สำหรับ login',
  `password` varchar(255) NOT NULL default '' COMMENT 'รหัสผ่านที่เข้ารหัสแล้ว',
  `repassword` varchar(50) NULL default '' COMMENT 'รหัสผ่านชั่วคราวสำหรับกรณีลืมรหัสผ่าน ส่งทาง email',
  `twofa_status` enum('on','pending','off') NOT NULL DEFAULT 'off' COMMENT 'สถานะการใช้งาน Two-Factor Authentication',
  `twofa_secret` varchar(64) DEFAULT NULL COMMENT 'Secret key สำหรับ Two-Factor Authentication',
  `status` varchar(32) NULL default '' COMMENT 'สถานะของบัญชีผู้ใช้ เช่น active, inactive',
  `node_member` int(11) NULL default '0' COMMENT 'กลุ่ม/โหนดที่ผู้ใช้สังกัด',
  `ipaddress` varchar(20) NULL default '' COMMENT 'IP Address ที่ login ล่าสุด',
  `register_date` int(11) NULL COMMENT 'วันที่สมัครในรูปแบบ Unix timestamp',
  `modules` text NULL COMMENT 'รายการ module ที่ผู้ใช้มีสิทธิ์เข้าถึง (JSON/serialize)',
  `preset_id` int(11) NOT NULL default '0' COMMENT 'preset สิทธิ์ที่สังกัด (0=ยังไม่กำหนด) → permission_preset',
  PRIMARY KEY  (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
";

$sqlArray[_DBPREFIX_ . 'member_member'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "member_member` (
  `mem_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของสมาชิก',
  `user_id` int(11) NOT NULL COMMENT 'อ้างอิง user_id จากตาราง member_user',
  `salutation` varchar(125) NULL COMMENT 'คำนำหน้าชื่อ เช่น นาย นาง นางสาว',
  `firstname` varchar(50) NULL default '' COMMENT 'ชื่อจริง',
  `lastname` varchar(50) NULL default '' COMMENT 'นามสกุล',
  `picture` varchar(255) NULL default '' COMMENT 'path รูปโปรไฟล์',
  `email` varchar(50) NULL default '' COMMENT 'อีเมลของสมาชิก',
  `phone` varchar(20) NULL default '' COMMENT 'เบอร์โทรศัพท์',
  `mem_code` varchar(20) NULL default '' COMMENT 'รหัสสมาชิก',
  `mem_namecard` varchar(20) NULL default '' COMMENT 'รหัสนามบัตรสมาชิก',
  `position_id` int(11) NULL COMMENT 'อ้างอิงตำแหน่งของสมาชิก',
  `birthday` int(11) NULL COMMENT 'วันเกิดในรูปแบบ Unix timestamp',
  PRIMARY KEY  (`mem_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

/**
 * Table to store information on people online
 * The calculated number may be calculated as 15 minutes. If the page has not refreshed for more than 15 minutes, it will be out of online
 * user_id = Member system
 * onlineTime = Time stored into the system
 * onlineEndTime =  Time to Expiration online
 * actionView = Where are viewing or doing recently
 * ipaddress = Store IPs online
 */

$sqlArray[_DBPREFIX_ . 'member_online'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "member_online` (
  `online_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key',
  `user_id` int(11) NOT NULL default '0' COMMENT 'อ้างอิง user_id ของผู้ที่กำลัง online',
  `onlineTime` int(11) NULL default '0' COMMENT 'เวลาที่เข้าสู่ระบบในรูปแบบ Unix timestamp',
  `onlineEndTime` int(11) NULL default '0' COMMENT 'เวลาหมดอายุ online (ไม่มีการ refresh เกิน 15 นาที)',
  `actionView` varchar(255) NULL default '' COMMENT 'หน้าหรือ action ที่กำลังดูอยู่ล่าสุด',
  `ipaddress` varchar(20) NULL default '' COMMENT 'IP Address ของผู้ใช้',
  `viewurl` varchar(255) NULL default '' COMMENT 'URL ที่กำลังเปิดอยู่',
  PRIMARY KEY  (`online_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
";

$sqlArray[_DBPREFIX_ . 'admin_group'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "admin_group` (
		`group_admin_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของกลุ่ม Admin',
		`group_admin_name` varchar(120) NULL COMMENT 'ชื่อกลุ่ม Admin',
    `region_id` int(11) NULL default '0' COMMENT 'อ้างอิง region ที่กลุ่มนี้ดูแล',
  PRIMARY KEY  (`group_admin_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'member_admin_login_logs'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "member_admin_login_logs` (
  `logs_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของ log',
  `user_id` int(11) NOT NULL COMMENT 'อ้างอิง user_id ของ Admin ที่ login',
  `admin_ip` varchar(20) NULL COMMENT 'IP Address ของ Admin',
  `logs_time` int(11) NULL COMMENT 'เวลาที่ login ในรูปแบบ Unix timestamp',
  PRIMARY KEY  (`logs_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'member_member_login_logs'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "member_member_login_logs` (
  `logs_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของ log',
  `user_id` int(11) NOT NULL COMMENT 'อ้างอิง user_id ของสมาชิกที่ login',
  `member_ip` varchar(20) NULL COMMENT 'IP Address ของสมาชิก',
  `logs_time` int(11) NULL COMMENT 'เวลาที่ login ในรูปแบบ Unix timestamp',
  PRIMARY KEY  (`logs_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
";

$sqlArray[_DBPREFIX_ . 'logs_login'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "logs_login` (
  `logs_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของ log',
  `loginBan` int(1) NULL default '0' COMMENT 'สถานะถูก ban (1=ถูก ban, 0=ปกติ)',
  `loginFail` int(11) NULL COMMENT 'จำนวนครั้งที่ login ผิดพลาด',
  `loginIp` varchar(20) NULL COMMENT 'IP Address ที่พยายาม login',
  `loginDes` varchar(255) NULL COMMENT 'รายละเอียดเพิ่มเติมของ log',
  `logs_time` int(11) NULL COMMENT 'เวลาที่บันทึก log ในรูปแบบ Unix timestamp',
  PRIMARY KEY  (`logs_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'logs_mail'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "logs_mail` (
  `logs_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของ log',
  `subject` varchar(255) NULL default '' COMMENT 'หัวข้ออีเมลที่ส่ง',
  `sendto` varchar(255) NULL default '' COMMENT 'อีเมลผู้รับ',
  `header` text NULL COMMENT 'Header ของอีเมล',
  `content` text NULL COMMENT 'เนื้อหาของอีเมล',
  `logs_time` int(11) NULL COMMENT 'เวลาที่ส่งอีเมลในรูปแบบ Unix timestamp',
  PRIMARY KEY  (`logs_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'site_metatags'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_metatags` (
	`meta_id` int( 11 ) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
	`meta_key` varchar( 64 ) NULL COMMENT 'Key สำหรับอ้างอิง metatag เช่น ชื่อหน้าหรือ module',
	`title` varchar( 255 ) NULL COMMENT 'ชื่อหน้า (Title tag)',
	`description` varchar( 255 ) NULL COMMENT 'คำอธิบายหน้า (Meta description)',
	`keywords` varchar( 255 ) NULL COMMENT 'คีย์เวิร์ด SEO (Meta keywords)',
	`robots` varchar( 64 ) NULL COMMENT 'คำสั่ง robots เช่น index, noindex, follow',
	`googlebot` varchar( 64 ) NULL COMMENT 'คำสั่งสำหรับ Googlebot โดยเฉพาะ',
	`contact_addr` varchar( 64 ) NULL COMMENT 'ที่อยู่ติดต่อสำหรับ meta tag',
	`copyright` varchar( 64 ) NULL COMMENT 'ข้อความลิขสิทธิ์',
	`author` varchar( 64 ) NULL COMMENT 'ชื่อผู้เขียนหรือเจ้าของเว็บ',
	`revisit-after` varchar( 16 ) NULL COMMENT 'ความถี่ที่ให้ bot กลับมา crawl',
	`lang` varchar( 16 ) NULL COMMENT 'ภาษาของหน้า เช่น th, en',
  `icon` varchar( 255 ) NULL COMMENT 'path ของ favicon หรือ icon หน้า',
	PRIMARY KEY ( `meta_id` ) ,
	KEY `lang` ( `lang` ) ,
	KEY `meta_key` ( `meta_key` )
) ENGINE = MYISAM DEFAULT CHARSET = utf8 AUTO_INCREMENT =1;
";

$sqlAlter[_DBPREFIX_ . 'site_metatags']['schema_json'] = "
ALTER TABLE `" . _DBPREFIX_ . "site_metatags`
ADD COLUMN `schema_json` TEXT NULL COMMENT 'ข้อมูล Schema ในรูปแบบ JSON-LD' AFTER `icon`;";

$sqlAlter[_DBPREFIX_ . 'member_user']['preset_id'] = "
ALTER TABLE `" . _DBPREFIX_ . "member_user` ADD COLUMN IF NOT EXISTS `preset_id` int(11) NOT NULL default '0' COMMENT 'preset สิทธิ์ที่สังกัด (0=ยังไม่กำหนด) → permission_preset';";

$sqlAlter[_DBPREFIX_ . 'permission_preset']['widget_order'] = "
ALTER TABLE `" . _DBPREFIX_ . "permission_preset` ADD COLUMN IF NOT EXISTS `widget_order` text NULL COMMENT 'ลำดับ widget dashboard คั่นด้วย ,';";

$sqlArray[_DBPREFIX_ . 'site_seo_redir'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_seo_redir` (
  `redir_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของตาราง Redirect',
  `source` varchar(255) NOT NULL COMMENT 'URL ต้นทาง เช่น /old-page',
  `target` varchar(255) NOT NULL COMMENT 'URL ปลายทาง เช่น /new-page',
  `hit_count` int(11) NOT NULL default '0' COMMENT 'จำนวนการเรียกใช้ Redirect',
  PRIMARY KEY  (`redir_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'banner'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "banner` (
  `banner_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของ Banner',
  `title` varchar(125) NULL COMMENT 'ชื่อหรือหัวข้อของ Banner',
  `detail` varchar(255) NULL COMMENT 'รายละเอียดของ Banner',
  `link` varchar(255) NULL COMMENT 'URL ที่ Banner ชี้ไป',
  `target` varchar(64) NULL COMMENT 'วิธีเปิด link เช่น _blank, _self',
  `adddate` int(11) NULL COMMENT 'วันที่เพิ่ม Banner ในรูปแบบ Unix timestamp',
  `sort` int(5) NULL COMMENT 'ลำดับการแสดงผล',
  `bannertype` varchar(64) NULL COMMENT 'ประเภทของ Banner เช่น image, flash',
  `keysname` varchar(32) NULL COMMENT 'ชื่อ key สำหรับจัดกลุ่ม Banner',
  `filename` varchar(255) NULL COMMENT 'ชื่อไฟล์รูปภาพหรือสื่อของ Banner',
  `status` int(1) NULL default 0 COMMENT 'สถานะการแสดงผล (1=แสดง, 0=ซ่อน)',
  `extra1` varchar(255) NULL COMMENT 'ข้อมูลเสริมที่ 1 (สำรองไว้)',
  `extra2` varchar(255) NULL COMMENT 'ข้อมูลเสริมที่ 2 (สำรองไว้)',
  PRIMARY KEY  (`banner_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'contact'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "contact` (
  `id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของข้อความติดต่อ',
  `first_last` varchar(125) NULL COMMENT 'ชื่อ-นามสกุลของผู้ส่ง',
  `email` varchar(255) NULL COMMENT 'อีเมลของผู้ส่ง',
  `subject` varchar(255) NULL COMMENT 'หัวข้อข้อความ',
  `message` text NULL COMMENT 'เนื้อหาข้อความที่ส่งมา',
  `add_date` date NULL COMMENT 'วันที่ส่งข้อความ',
  `url` varchar(255) NULL COMMENT 'URL หน้าที่ผู้ใช้ส่งข้อความมา',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'hash_log'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "hash_log` (
    id INT NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
    message TEXT NULL COMMENT 'ผลการตรวจสอบหรือ error message',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'วันเวลาที่บันทึก log',
    PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

include('mysql_projects.sql.php');
include('mysql_articles.sql.php');
