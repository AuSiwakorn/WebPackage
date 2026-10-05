<?php

$sqlArray[_DBPREFIX_ . 'site_articles'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_articles` (
  `articles_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของบทความ',
  `group_id` int(4) NOT NULL default '0' COMMENT 'อ้างอิงกลุ่มบทความ',
  `user_id` int(11) NOT NULL COMMENT 'อ้างอิงผู้สร้างบทความ',
  `keysname` varchar(32) NOT NULL COMMENT 'ชื่อ key สำหรับจัดกลุ่มประเภทบทความ',
  `status` int(1) NOT NULL default '0' COMMENT 'สถานะบทความ (1=เผยแพร่, 0=ซ่อน)',
  `add_time` int(11) NOT NULL COMMENT 'วันที่สร้างบทความ (Unix timestamp)',
  `end_time` int(11) NOT NULL default '0' COMMENT 'วันที่หมดอายุบทความ (Unix timestamp, 0=ไม่มีกำหนด)',
  `sorttime` int(11) NOT NULL COMMENT 'วันเวลาสำหรับเรียงลำดับบทความ',
  `preview` int(11) NOT NULL default '0' COMMENT 'จำนวนครั้งที่เปิดดู',
  `displaytime` int(11) NOT NULL COMMENT 'วันที่แสดงบทความ (Unix timestamp)',
  `icon` varchar(125) NOT NULL COMMENT 'path รูป icon หลักของบทความ',
  `icon2` varchar(125) NOT NULL COMMENT 'path รูป icon สำรองของบทความ',
  `file_attach` varchar(125) NOT NULL COMMENT 'path ไฟล์แนบของบทความ',
  `bigfile` varchar(255) DEFAULT NULL COMMENT 'path ไฟล์ขนาดใหญ่ของบทความ',
  `checkOption` int(2) NOT NULL COMMENT 'ตัวเลือกการแสดงผลเพิ่มเติม',
  `checkBoxOption` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'ตัวเลือก checkbox (1=เลือก, 0=ไม่เลือก)',
  `extra1` varchar(255) NOT NULL COMMENT 'ข้อมูลเสริมที่ 1',
  `extra2` varchar(255) NOT NULL COMMENT 'ข้อมูลเสริมที่ 2',
  `extra3` varchar(255) NOT NULL COMMENT 'ข้อมูลเสริมที่ 3',
  `extra4` varchar(255) NOT NULL COMMENT 'ข้อมูลเสริมที่ 4',
  `extra5` varchar(255) NOT NULL COMMENT 'ข้อมูลเสริมที่ 5',
  `extra6` varchar(255) NOT NULL COMMENT 'ข้อมูลเสริมที่ 6',
  `extra7` varchar(255) NOT NULL COMMENT 'ข้อมูลเสริมที่ 7',
  `extra8` varchar(255) NOT NULL COMMENT 'ข้อมูลเสริมที่ 8',
  `extra9` varchar(255) NOT NULL COMMENT 'ข้อมูลเสริมที่ 9',
  `extra10` varchar(255) NOT NULL COMMENT 'ข้อมูลเสริมที่ 10',
  PRIMARY KEY  (`articles_id`),
  KEY `group_id` (`group_id`,`status`,`keysname`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'site_articles_content'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_articles_content` (
  `content_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของเนื้อหาบทความ',
  `articles_id` int(11) NOT NULL COMMENT 'อ้างอิง articles_id จากตาราง site_articles',
  `langkeys` varchar(25) NOT NULL default 'th' COMMENT 'รหัสภาษา เช่น th, en',
  `title` varchar(255) NOT NULL COMMENT 'ชื่อเรื่องบทความ',
  `shortMessage` text NOT NULL COMMENT 'ย่อหน้าแรกหรือเนื้อหาสั้นๆ',
  `keywords` varchar(255) NOT NULL COMMENT 'คีย์เวิร์ด SEO ของบทความ',
  `content` MEDIUMTEXT NOT NULL COMMENT 'เนื้อหาหลักของบทความ',
  `author` varchar(255) NOT NULL COMMENT 'ชื่อผู้เขียนบทความ',
  `content2` MEDIUMTEXT NOT NULL COMMENT 'เนื้อหาส่วนที่ 2',
  `content3` text NOT NULL COMMENT 'เนื้อหาส่วนที่ 3',
  `content4` text NOT NULL COMMENT 'เนื้อหาส่วนที่ 4',
  `contentAttach` varchar(255) NOT NULL COMMENT 'path ไฟล์แนบของเนื้อหา',
  `content_icon` varchar(255) NOT NULL COMMENT 'path รูป icon ของเนื้อหา',
  `content_extra1` varchar(255) NOT NULL COMMENT 'ข้อมูลเนื้อหาเสริมที่ 1',
  `content_extra2` varchar(255) NOT NULL COMMENT 'ข้อมูลเนื้อหาเสริมที่ 2',
  `content_extra3` varchar(255) NOT NULL COMMENT 'ข้อมูลเนื้อหาเสริมที่ 3',
  `slug` varchar(255) NOT NULL COMMENT 'URL slug ของบทความ สำหรับ SEO',
  `meta_title` varchar(255) NULL COMMENT 'SEO meta title',
  `meta_description` varchar(255) NULL COMMENT 'SEO meta description',
  `meta_robots` varchar(64) NULL COMMENT 'SEO robots',
  `og_image` varchar(255) NULL COMMENT 'SEO OG image path',
  PRIMARY KEY  (`content_id`),
  KEY `articles_id` (`articles_id`,`langkeys`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'site_articles_keys'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_articles_keys` (
  `kid` int(5) NOT NULL auto_increment COMMENT 'Primary Key ของ tag/หมวดหมู่',
  `kname` varchar(255) NOT NULL COMMENT 'ชื่อ tag หรือหมวดหมู่',
  `kicon` varchar(255) NOT NULL COMMENT 'path icon ของ tag',
  `ksort` int(5) NOT NULL COMMENT 'ลำดับการแสดงผล',
  `keysname` varchar(32) NOT NULL COMMENT 'ชื่อ key สำหรับจัดกลุ่มประเภท',
  `url` varchar(255) NOT NULL COMMENT 'URL ของ tag',
  PRIMARY KEY  (`kid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'site_articles_keysuse'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_articles_keysuse` (
  `kuid` int(4) NOT NULL auto_increment COMMENT 'Primary Key',
  `kid` int(5) NOT NULL COMMENT 'อ้างอิง kid จากตาราง site_articles_keys',
  `articles_id` int(11) NOT NULL COMMENT 'อ้างอิง articles_id จากตาราง site_articles',
  PRIMARY KEY  (`kuid`),
  KEY `kid` (`kid`,`articles_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'site_articles_group'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_articles_group` (
  `group_id` int(4) NOT NULL auto_increment COMMENT 'Primary Key ของกลุ่มบทความ',
  `group_parent_id` int(4) NOT NULL COMMENT 'อ้างอิงกลุ่มแม่ (0=กลุ่มหลัก)',
  `keysname` varchar(32) NOT NULL COMMENT 'ชื่อ key สำหรับจัดกลุ่มประเภท',
  `img` varchar(100) NOT NULL COMMENT 'path รูปภาพของกลุ่ม',
  `img2` varchar(100) NULL COMMENT 'path รูปการ์ดหน้าแรก (home)',
  `icon` varchar(100) NULL COMMENT 'path icon ของกลุ่ม',
  `sort` int(3) NOT NULL default '0' COMMENT 'ลำดับการแสดงผล',
  `updatetime` int(11) NOT NULL default '0' COMMENT 'วันที่อัปเดตล่าสุด (Unix timestamp)',
  `extraOption` varchar(70) NOT NULL COMMENT 'ตัวเลือกเสริมของกลุ่ม',
  `status` int(2) NOT NULL COMMENT 'สถานะกลุ่ม (1=แสดง, 0=ซ่อน)',
  `extra_group1` varchar(255) NOT NULL COMMENT 'ข้อมูลกลุ่มเสริมที่ 1',
  `extra_group2` varchar(255) NOT NULL COMMENT 'ข้อมูลกลุ่มเสริมที่ 2',
  `extra_group3` varchar(255) NOT NULL COMMENT 'ข้อมูลกลุ่มเสริมที่ 3',
  `extra_group4` varchar(255) NOT NULL COMMENT 'ข้อมูลกลุ่มเสริมที่ 4',
  `extra_group5` varchar(255) NOT NULL COMMENT 'ข้อมูลกลุ่มเสริมที่ 5',
  PRIMARY KEY  (`group_id`),
  KEY `keysname` (`keysname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'site_articles_group_content'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_articles_group_content` (
  `group_content_id` int(4) NOT NULL auto_increment COMMENT 'Primary Key ของเนื้อหากลุ่ม',
  `group_id` int(4) NOT NULL COMMENT 'อ้างอิง group_id จากตาราง site_articles_group',
  `langkeys` varchar(25) NOT NULL default 'th' COMMENT 'รหัสภาษา เช่น th, en',
  `group_name` varchar(255) NOT NULL COMMENT 'ชื่อกลุ่มบทความ',
  `detail` text NOT NULL COMMENT 'รายละเอียดของกลุ่ม',
  `detailExtra1` text NOT NULL COMMENT 'รายละเอียดเสริมที่ 1',
  `detailExtra2` text NOT NULL COMMENT 'รายละเอียดเสริมที่ 2',
  `group_slug` varchar(255) NOT NULL COMMENT 'URL slug ของกลุ่ม สำหรับ SEO',
  `meta_title` varchar(255) NULL COMMENT 'SEO meta title',
  `meta_description` varchar(255) NULL COMMENT 'SEO meta description',
  `meta_robots` varchar(64) NULL COMMENT 'SEO robots',
  `og_image` varchar(255) NULL COMMENT 'SEO OG image path',
  PRIMARY KEY  (`group_content_id`),
  KEY `langkeys` (`langkeys`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'site_articles_img'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_articles_img` (
  `file_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของรูปภาพ',
  `articles_id` int(11) NOT NULL COMMENT 'อ้างอิง articles_id จากตาราง site_articles',
  `imgPathMini` varchar(255) NOT NULL COMMENT 'path รูปขนาดย่อ (thumbnail)',
  `imgPathBig` varchar(255) NOT NULL COMMENT 'path รูปขนาดเต็ม',
  `ctime` int(11) NOT NULL COMMENT 'วันที่อัปโหลด (Unix timestamp)',
  `title` varchar(255) NOT NULL COMMENT 'ชื่อหรือคำอธิบายรูปภาพ',
  `detail` text NOT NULL COMMENT 'รายละเอียดรูปภาพ',
  `detail2` text NOT NULL COMMENT 'รายละเอียดรูปภาพเพิ่มเติม',
  `keysname` varchar(32) NOT NULL COMMENT 'ชื่อ key สำหรับจัดกลุ่มประเภท',
  `ismark` int(1) NOT NULL default '0' COMMENT 'รูปหลัก (1=ใช่, 0=ไม่ใช่)',
  PRIMARY KEY  (`file_id`),
  KEY `articles_id` (`articles_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'site_articles_file'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_articles_file` (
  `file_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของไฟล์แนบ',
  `articles_id` int(11) NOT NULL COMMENT 'อ้างอิง articles_id จากตาราง site_articles',
  `filename` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์แนบ',
  `ctime` int(11) NOT NULL COMMENT 'วันที่อัปโหลด (Unix timestamp)',
  `detail` text NOT NULL COMMENT 'รายละเอียดไฟล์แนบ (ภาษาหลัก)',
  `detail_en` text NOT NULL COMMENT 'รายละเอียดไฟล์แนบ (ภาษาอังกฤษ)',
  `keysname` varchar(32) NOT NULL COMMENT 'ชื่อ key สำหรับจัดกลุ่มประเภท',
  PRIMARY KEY  (`file_id`),
  KEY `articles_id` (`articles_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'site_tags'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_tags` (
  `tags_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของ tag',
  `tags_name` varchar(62) NOT NULL COMMENT 'ชื่อ tag',
  `articles_tags_num` int(11) NOT NULL COMMENT 'จำนวนบทความที่ใช้ tag นี้',
  `keysname` varchar(32) NOT NULL COMMENT 'ชื่อ key สำหรับจัดกลุ่มประเภท',
  PRIMARY KEY  (`tags_id`),
  KEY `tags_id` (`tags_name`, `articles_tags_num`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'site_articles_group_file'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_articles_group_file` (
    `file_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของไฟล์แนบกลุ่ม',
    `group_id` int(11) NOT NULL COMMENT 'อ้างอิง group_id จากตาราง site_articles_group',
    `filename` varchar(255) NOT NULL COMMENT 'ชื่อไฟล์แนบ',
    `ctime` int(11) NOT NULL COMMENT 'วันที่อัปโหลด (Unix timestamp)',
    `detail` text NOT NULL COMMENT 'รายละเอียดไฟล์แนบ',
    `keysname` varchar(32) NOT NULL COMMENT 'ชื่อ key สำหรับจัดกลุ่มประเภท',
    PRIMARY KEY (`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
";

$sqlArray[_DBPREFIX_ . 'site_articles_group_img'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_articles_group_img` (
  `file_id` int(11) NOT NULL auto_increment COMMENT 'Primary Key ของรูปภาพกลุ่ม',
  `group_id` int(11) NOT NULL COMMENT 'อ้างอิง group_id จากตาราง site_articles_group',
  `imgPathMini` varchar(255) NOT NULL COMMENT 'path รูปขนาดย่อ (thumbnail)',
  `imgPathBig` varchar(255) NOT NULL COMMENT 'path รูปขนาดเต็ม',
  `ctime` int(11) NOT NULL COMMENT 'วันที่อัปโหลด (Unix timestamp)',
  `title` varchar(255) NULL COMMENT 'ชื่อหรือคำอธิบายรูปภาพ',
  `detail` text NOT NULL COMMENT 'รายละเอียดรูปภาพ',
  `detail2` text NULL COMMENT 'รายละเอียดรูปภาพเพิ่มเติม',
  `keysname` varchar(32) NOT NULL COMMENT 'ชื่อ key สำหรับจัดกลุ่มประเภท',
  `ismark` int(1) NOT NULL default '0' COMMENT 'รูปหลัก (1=ใช่, 0=ไม่ใช่)',
  PRIMARY KEY  (`file_id`),
  KEY `articles_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'site_articles_subpost'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_articles_subpost` (
  `subpost_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key ของ subpost',
  `group_id` int(11) NOT NULL DEFAULT '0' COMMENT 'อ้างอิงกรุ๊ปแม่ (0=ไม่ผูก)',
  `articles_id` int(11) NOT NULL DEFAULT '0' COMMENT 'อ้างอิงบทความแม่ (0=ไม่ผูก)',
  `keysname` varchar(32) NOT NULL DEFAULT '' COMMENT 'keysname ของแม่',
  `subIcon` varchar(255) NOT NULL DEFAULT '' COMMENT 'path รูปของ subpost',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT 'ลำดับการแสดงผล',
  `status` int(2) NOT NULL DEFAULT '0' COMMENT 'สถานะ (0=แสดง)',
  `subAddDate` int(11) NOT NULL DEFAULT '0' COMMENT 'วันที่สร้าง (Unix timestamp)',
  `subModifyDate` int(11) NOT NULL DEFAULT '0' COMMENT 'วันที่แก้ไขล่าสุด (Unix timestamp)',
  PRIMARY KEY (`subpost_id`),
  KEY `group_id` (`group_id`),
  KEY `articles_id` (`articles_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";

$sqlArray[_DBPREFIX_ . 'site_articles_subpost_content'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_articles_subpost_content` (
  `subpost_content_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key ของเนื้อหา subpost',
  `subpost_id` int(11) NOT NULL DEFAULT '0' COMMENT 'อ้างอิง subpost_id',
  `langkeys` varchar(25) NOT NULL DEFAULT 'th' COMMENT 'รหัสภาษา เช่น th, en',
  `subtitle` varchar(255) NOT NULL DEFAULT '' COMMENT 'หัวข้อ',
  `subMessage` varchar(255) NOT NULL DEFAULT '' COMMENT 'ข้อความสั้น',
  `subText` text NOT NULL COMMENT 'เนื้อหา',
  PRIMARY KEY (`subpost_content_id`),
  KEY `subpost_id` (`subpost_id`),
  KEY `langkeys` (`langkeys`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
";
