<?php
/**
 * FILE: core/member/database.php
 * ROLE: schema ตาราง permission_preset (ระบบ preset สิทธิ์) — auto-load โดย setupModuleDB.php
 * TABLES: permission_preset
 */
$sqlArray[_DBPREFIX_ . 'permission_preset'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "permission_preset` (
  `preset_id`   int(11) NOT NULL auto_increment COMMENT 'Primary Key ของ preset สิทธิ์',
  `preset_name` varchar(120) NOT NULL default '' COMMENT 'ชื่อ preset / ตำแหน่ง',
  `modules`     mediumtext NULL COMMENT 'permission key คั่นด้วย , | all',
  `note`        varchar(255) NOT NULL default '' COMMENT 'หมายเหตุ',
  `sort`        int(6) NOT NULL default 0 COMMENT 'ลำดับการแสดง',
  `add_date`    datetime NULL COMMENT 'วันที่สร้าง',
  `edit_date`   datetime NULL COMMENT 'วันที่แก้ไขล่าสุด',
  `widget_order` text NULL COMMENT 'ลำดับ widget dashboard คั่นด้วย ,',
  PRIMARY KEY  (`preset_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
";
