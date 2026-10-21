<?php
/**
 * FILE: admweb/aowebdata/modules/stock/database.php
 * ROLE: โครงสร้างฐานข้อมูล AOSTOCK (ระบบบริหารสต๊อก + POS) — inc_install.php ของ admweb โหลดไฟล์นี้อัตโนมัติ
 * DEPENDS: - (ถูก include โดย admweb/plugins/db/inc_install.php · ต้องมี 'stock' ใน $aModuleUse)
 * TABLES: ao_stock_* 25 ตาราง (รายชื่อเดียวกับ $aTablename ใน aModuleConfig.php)
 * TODO:
 *   - [ ] เพิ่มคอลัมน์ทีหลัง: ใส่ $sqlAlter[ตาราง][คอลัมน์] = "ALTER TABLE ... ADD COLUMN ..." (ไม่ต้องใส่ IF NOT EXISTS — ตัวติดตั้งเช็กคอลัมน์ให้เอง)
 *   - [x] ประวัติการแก้ไขดู docs/CHANGELOG.md
 */
/* ---------- ตาราง ao_stock_* (InnoDB · utf8mb4) — คำอธิบายตาราง / หลักการดู DATABASE.md ---------- */


/* ---------- 1. ข้อมูลหลัก ---------- */

/* 1) สาขา + ค่าตั้งของสาขา */
$sqlArray[_DBPREFIX_ . 'stock_branch'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_branch` (
	`branch_id`        int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`code`             varchar(10)  NOT NULL COMMENT 'รหัสสั้น เช่น HQ, RS, BN',
	`name`             varchar(100) NOT NULL COMMENT 'ชื่อเต็ม เช่น สาขารังสิต',
	`short_name`       varchar(40)  NOT NULL DEFAULT '' COMMENT 'ชื่อย่อบนแถบ/การ์ด',
	`address`          varchar(255) NOT NULL DEFAULT '',
	`phone`            varchar(30)  NOT NULL DEFAULT '',
	`sell_vat`         tinyint(1)   NOT NULL DEFAULT 0 COMMENT '1 = สาขานี้ขายแบบ VAT ได้ (ผู้ดูแลตั้ง) · 0 = ออกบิลไม่ VAT อย่างเดียว',
	`tax_id`           varchar(20)  NOT NULL DEFAULT '' COMMENT 'เลขผู้เสียภาษี 13 หลัก (บัญชีตั้ง)',
	`tax_branch`       varchar(5)   NOT NULL DEFAULT '00000' COMMENT 'เลขที่สาขาตามที่จด VAT (00000 = สำนักงานใหญ่)',
	`prefix_vat`       varchar(6)   NOT NULL DEFAULT '' COMMENT 'รหัสนำหน้าเลขที่บิล VAT เช่น BPV (บัญชีตั้ง)',
	`prefix_novat`     varchar(6)   NOT NULL DEFAULT '' COMMENT 'รหัสนำหน้าเลขที่บิลไม่ VAT เช่น BP (บัญชีตั้ง)',
	`bill_company`     varchar(120) NOT NULL DEFAULT '' COMMENT 'ชื่อร้าน / บริษัทบนหัวบิล',
	`bill_address`     varchar(255) NOT NULL DEFAULT '' COMMENT 'ที่อยู่บนบิล (ว่าง = ใช้ address ของสาขา)',
	`bill_phone`       varchar(40)  NOT NULL DEFAULT '' COMMENT 'เบอร์บนบิล (ว่าง = ใช้ phone ของสาขา)',
	`bill_extra`       varchar(120) NOT NULL DEFAULT '' COMMENT 'บรรทัดเสริมใต้ที่อยู่ เช่น LINE / เว็บไซต์',
	`bill_title_vat`   varchar(60)  NOT NULL DEFAULT 'ใบเสร็จรับเงิน / ใบกำกับภาษีอย่างย่อ' COMMENT 'หัวกระดาษบิล VAT',
	`bill_title_novat` varchar(60)  NOT NULL DEFAULT 'บิลเงินสด / ใบเสร็จรับเงิน' COMMENT 'หัวกระดาษบิลไม่ VAT',
	`bill_paper`       enum('80','a4') NOT NULL DEFAULT '80' COMMENT 'ขนาดกระดาษเริ่มต้นตอนพิมพ์บิล',
	`bill_novat_tax`   tinyint(1)   NOT NULL DEFAULT 0 COMMENT '1 = พิมพ์เลขผู้เสียภาษีบนบิลไม่ VAT ด้วย',
	`receipt_footer`   varchar(300) NOT NULL DEFAULT '' COMMENT 'ข้อความท้ายบิล ขึ้นบรรทัดใหม่ได้ (หน้าตั้งค่าเลขที่บิล)',
	`count_day`        tinyint UNSIGNED NOT NULL DEFAULT 1 COMMENT 'รอบตรวจนับเริ่มวันที่เท่าไรของเดือน (1-28) ผู้ดูแลตั้ง',
	`default_float`    decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'เงินทอนมาตรฐาน (โมดูล POS)',
	`backdate_days`    smallint UNSIGNED NOT NULL DEFAULT 7 COMMENT 'แก้เอกสาร/รับคืนย้อนหลังได้กี่วัน ผู้ดูแลตั้ง',
	`allow_negative`   tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=ยอมให้สต๊อกติดลบ',
	`is_active`        tinyint(1) NOT NULL DEFAULT 1,
	`sort`             int NOT NULL DEFAULT 0,
	`add_date`         datetime DEFAULT CURRENT_TIMESTAMP,
	`edit_date`        datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY `uq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK สาขา';
";
/* daily_goal / count_open_limit เลิกใช้แล้ว — คอลัมน์ในฐานข้อมูลเดิมปล่อยไว้ได้ */
/* เพิ่มทีหลัง — Reinstall เพื่อเพิ่มคอลัมน์ · สาขาเดิมได้ 0 (ไม่ขาย VAT) ผู้ดูแลเปิดเองที่หน้าจัดการสาขา (21 ต.ค.) */
$sqlAlter[_DBPREFIX_ . 'stock_branch']['sell_vat'] = "ALTER TABLE `" . _DBPREFIX_ . "stock_branch` ADD COLUMN `sell_vat` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = สาขานี้ขายแบบ VAT ได้ (ผู้ดูแลตั้ง) · 0 = ออกบิลไม่ VAT อย่างเดียว' AFTER `phone`";

/* 2) ผู้ใช้ระบบ (พนักงาน PIN · ผู้ดูแล / บัญชี รหัสผ่าน) */
$sqlArray[_DBPREFIX_ . 'stock_staff'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_staff` (
	`staff_id`      int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`branch_id`     int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'สาขาปัจจุบัน',
	`username`      varchar(40)  NOT NULL COMMENT 'ชื่อผู้ใช้ตอนเข้าระบบ (พนักงานใช้คู่กับ PIN)',
	`name`          varchar(60)  NOT NULL COMMENT 'ชื่อที่แสดงบนรายการ/เอกสาร',
	`initials`      varchar(6)   NOT NULL DEFAULT '' COMMENT 'อักษรย่อบนปุ่มชื่อหน้าเข้าระบบ',
	`role`          enum('staff','admin','account') NOT NULL DEFAULT 'staff' COMMENT 'account = ฝ่ายบัญชี: ดูบิล/เงินเข้าทุกสาขา + ตั้งเลขที่บิล',
	`perms`         text NOT NULL COMMENT 'เมนู + สิทธิ์เสริม คั่นด้วย , (admin ได้ทุกสิทธิ์ ยกเว้น sale)',
	`pin_hash`      varchar(255) NOT NULL DEFAULT '' COMMENT 'password_hash ของ PIN 4 หลัก',
	`pin_fp`        char(64) NULL COMMENT 'HMAC ของ PIN ไว้เช็กซ้ำในสาขา (ช่วงที่ 10)',
	`password_hash` varchar(255) NOT NULL DEFAULT '' COMMENT 'เฉพาะผู้ดูแล',
	`fail_count`    tinyint UNSIGNED NOT NULL DEFAULT 0,
	`locked_until`  datetime NULL,
	`last_login`    datetime NULL,
	`is_active`     tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=ลาออก/พัก (เอกสารเก่ายังอ้างถึงได้)',
	`add_date`      datetime DEFAULT CURRENT_TIMESTAMP,
	`edit_date`     datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY `uq_username` (`username`),
	KEY `idx_branch` (`branch_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ผู้ใช้ระบบ';
";
/* เพิ่มทีหลัง — Reinstall เพื่อเพิ่มคอลัมน์ */
$sqlAlter[_DBPREFIX_ . 'stock_staff']['pin_fp'] = "ALTER TABLE `" . _DBPREFIX_ . "stock_staff` ADD COLUMN `pin_fp` char(64) NULL COMMENT 'HMAC ของ PIN ไว้เช็กซ้ำในสาขา (ช่วงที่ 10)' AFTER `pin_hash`";
/* perms เดิม varchar(255) สั้นไป (สิทธิ์ครบ 275 ตัว) → text · คีย์ perms_text ไม่ใช่คอลัมน์จริง ตัวติดตั้งจึงรันทุกครั้งที่ Reinstall (ผลเหมือนเดิม) */
$sqlAlter[_DBPREFIX_ . 'stock_staff']['perms_text'] = "ALTER TABLE `" . _DBPREFIX_ . "stock_staff` MODIFY COLUMN `perms` text NOT NULL COMMENT 'เมนู + สิทธิ์เสริม คั่นด้วย , (admin ได้ทุกสิทธิ์ ยกเว้น sale)'";

/* 2b) จดจำการเข้าสู่ระบบ 30 วัน (1 แถว / 1 เครื่อง) */
$sqlArray[_DBPREFIX_ . 'stock_remember'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_remember` (
	`remember_id` int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`staff_id`    int UNSIGNED NOT NULL,
	`selector`    char(24) NOT NULL COMMENT 'ส่วนหน้าของ cookie (ค้นหาแถว)',
	`token_hash`  char(64) NOT NULL COMMENT 'sha256 ของ validator (ไม่เก็บค่าจริง)',
	`expires_at`  datetime NOT NULL,
	`user_agent`  varchar(200) NOT NULL DEFAULT '',
	`last_used`   datetime NULL,
	`add_date`    datetime DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY `uq_selector` (`selector`),
	KEY `idx_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK จดจำการเข้าสู่ระบบ';
";

/* 2c) นับการกรอกผิดตาม IP (1 แถว / 1 IP) */
$sqlArray[_DBPREFIX_ . 'stock_login_ip'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_login_ip` (
	`ip`           varchar(45) NOT NULL PRIMARY KEY COMMENT 'IPv4 หรือวง IPv6 /64',
	`fail_count`   smallint UNSIGNED NOT NULL DEFAULT 0 COMMENT 'จำนวนครั้งที่ผิดในรอบนี้',
	`first_fail`   datetime NOT NULL COMMENT 'เวลาที่ผิดครั้งแรกของรอบนี้',
	`last_fail`    datetime NOT NULL,
	`locked_until` datetime NULL COMMENT 'NULL = ไม่ล็อก',
	KEY `idx_last` (`last_fail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK นับการกรอกผิดตาม IP';
";

/* 3) ประวัติการประจำสาขาของพนักงาน */
$sqlArray[_DBPREFIX_ . 'stock_staff_branch'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_staff_branch` (
	`sb_id`     int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`staff_id`  int UNSIGNED NOT NULL,
	`branch_id` int UNSIGNED NOT NULL,
	`date_from` date NOT NULL,
	`date_to`   date NULL COMMENT 'NULL = ยังประจำอยู่',
	`add_date`  datetime DEFAULT CURRENT_TIMESTAMP,
	KEY `idx_staff` (`staff_id`, `date_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ประวัติสาขาของพนักงาน';
";

/* 4) หมวดสินค้า */
$sqlArray[_DBPREFIX_ . 'stock_category'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_category` (
	`cate_id`    int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`name`       varchar(100) NOT NULL,
	`sort`       int NOT NULL DEFAULT 0,
	`is_active`  tinyint(1) NOT NULL DEFAULT 1,
	`created_by` int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'staff_id ผู้เพิ่ม (0 = ระบบ / นำเข้า)',
	`add_date`   datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK หมวดสินค้า';
";
/* เพิ่มทีหลัง — Reinstall เพื่อเพิ่มคอลัมน์ */
$sqlAlter[_DBPREFIX_ . 'stock_category']['created_by'] = "ALTER TABLE `" . _DBPREFIX_ . "stock_category` ADD COLUMN `created_by` int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'staff_id ผู้เพิ่ม (0 = ระบบ / นำเข้า)' AFTER `is_active`";

/* 5) สินค้า (1 สี / รุ่น = 1 SKU) */
$sqlArray[_DBPREFIX_ . 'stock_product'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_product` (
	`product_id`    int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`sku`           varchar(40)  NOT NULL,
	`barcode`       varchar(40)  NULL,
	`name`          varchar(200) NOT NULL,
	`cate_id`       int UNSIGNED NOT NULL DEFAULT 0,
	`unit`          varchar(20)  NOT NULL DEFAULT 'ชิ้น',
	`cost_price`    decimal(10,2) NOT NULL DEFAULT 0 COMMENT 'ทุนล่าสุด',
	`sell_price`    decimal(10,2) NOT NULL DEFAULT 0,
	`reorder_point` int NOT NULL DEFAULT 0 COMMENT 'ต่ำกว่านี้ = ใกล้หมด (ใช้ทุกสาขา)',
	`image`         varchar(255) NOT NULL DEFAULT '',
	`is_active`     tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=เลิกขาย ประวัติยังอยู่',
	`add_date`      datetime DEFAULT CURRENT_TIMESTAMP,
	`edit_date`     datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY `uq_sku` (`sku`),
	KEY `idx_barcode` (`barcode`),
	KEY `idx_cate` (`cate_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK สินค้า';
";


/* ---------- 2. สต๊อก ---------- */

/* 6) ยอดคงเหลือต่อสาขา (cache ของ stock_move) */
$sqlArray[_DBPREFIX_ . 'stock_balance'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_balance` (
	`branch_id`  int UNSIGNED NOT NULL,
	`product_id` int UNSIGNED NOT NULL,
	`qty`        int NOT NULL DEFAULT 0,
	`edit_date`  datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`branch_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ยอดคงเหลือ (cache จาก stock_move)';
";

/* 7) สมุดความเคลื่อนไหว (ledger) — แหล่งความจริงหนึ่งเดียว */
$sqlArray[_DBPREFIX_ . 'stock_move'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_move` (
	`move_id`    bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`branch_id`  int UNSIGNED NOT NULL,
	`product_id` int UNSIGNED NOT NULL,
	`type`       varchar(20)  NOT NULL,
	`qty`        int NOT NULL,
	`qty_after`  int NOT NULL COMMENT 'ยอดคงเหลือหลังรายการนี้ (audit)',
	`unit_cost`  decimal(10,2) NOT NULL DEFAULT 0,
	`ref_type`   varchar(20)  NOT NULL,
	`ref_id`     int UNSIGNED NOT NULL,
	`doc_no`     varchar(24)  NOT NULL DEFAULT '',
	`note`       varchar(255) NOT NULL DEFAULT '',
	`created_by` int UNSIGNED NOT NULL COMMENT 'staff_id',
	`add_date`   datetime DEFAULT CURRENT_TIMESTAMP,
	KEY `idx_bp_date` (`branch_id`, `product_id`, `add_date`),
	KEY `idx_ref` (`ref_type`, `ref_id`),
	KEY `idx_staff_date` (`created_by`, `add_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ความเคลื่อนไหวสต๊อก';
";


/* ---------- 3. เอกสารคลัง — รับเข้า · เบิก / ตัดออก · ตรวจนับ ---------- */

/* 8) ใบรับเข้า (RC-) */
$sqlArray[_DBPREFIX_ . 'stock_receive'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_receive` (
	`receive_id`  int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`doc_no`      varchar(24)  NOT NULL,
	`branch_id`   int UNSIGNED NOT NULL,
	`doc_date`    date NOT NULL,
	`ref_no`      varchar(60)  NOT NULL DEFAULT '' COMMENT 'เลขบิลซื้อ/เอกสารอ้างอิง',
	`note`        varchar(255) NOT NULL DEFAULT '',
	`item_count`  int NOT NULL DEFAULT 0,
	`total_qty`   int NOT NULL DEFAULT 0,
	`total_cost`  decimal(12,2) NOT NULL DEFAULT 0,
	`status`      enum('posted','void') NOT NULL DEFAULT 'posted',
	`void_mode`   enum('void','edit') NULL,
	`void_reason` varchar(255) NOT NULL DEFAULT '',
	`void_by`     int UNSIGNED NULL,
	`void_date`   datetime NULL,
	`edit_of_id`  int UNSIGNED NULL COMMENT 'ใบนี้ออกแทนใบไหน',
	`created_by`  int UNSIGNED NOT NULL,
	`add_date`    datetime DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY `uq_doc` (`branch_id`, `doc_no`),
	KEY `idx_branch_date` (`branch_id`, `doc_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ใบรับเข้า';
";

$sqlArray[_DBPREFIX_ . 'stock_receive_item'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_receive_item` (
	`item_id`      int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`receive_id`   int UNSIGNED NOT NULL,
	`product_id`   int UNSIGNED NOT NULL,
	`sku`          varchar(40)  NOT NULL,
	`product_name` varchar(200) NOT NULL,
	`qty`          int NOT NULL,
	`unit_cost`    decimal(10,2) NOT NULL DEFAULT 0,
	`qty_before`   int NOT NULL DEFAULT 0 COMMENT 'ยอดในระบบก่อนรับ (แสดงในใบ)',
	KEY `idx_receive` (`receive_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK รายการรับเข้า';
";

/* 9) ใบเบิก / ตัดออก (IS-) */
$sqlArray[_DBPREFIX_ . 'stock_issue'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_issue` (
	`issue_id`    int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`doc_no`      varchar(24)  NOT NULL,
	`branch_id`   int UNSIGNED NOT NULL,
	`doc_date`    date NOT NULL,
	`reason`      varchar(20)  NOT NULL,
	`ref_no`      varchar(60)  NOT NULL DEFAULT '',
	`note`        varchar(255) NOT NULL DEFAULT '' COMMENT 'บังคับเมื่อ reason = branch, lost, other',
	`item_count`  int NOT NULL DEFAULT 0,
	`total_qty`   int NOT NULL DEFAULT 0,
	`total_cost`  decimal(12,2) NOT NULL DEFAULT 0,
	`status`      enum('posted','void') NOT NULL DEFAULT 'posted',
	`void_mode`   enum('void','edit') NULL,
	`void_reason` varchar(255) NOT NULL DEFAULT '',
	`void_by`     int UNSIGNED NULL,
	`void_date`   datetime NULL,
	`edit_of_id`  int UNSIGNED NULL,
	`created_by`  int UNSIGNED NOT NULL,
	`add_date`    datetime DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY `uq_doc` (`branch_id`, `doc_no`),
	KEY `idx_branch_date` (`branch_id`, `doc_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ใบเบิก/ตัดออก';
";

$sqlArray[_DBPREFIX_ . 'stock_issue_item'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_issue_item` (
	`item_id`      int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`issue_id`     int UNSIGNED NOT NULL,
	`product_id`   int UNSIGNED NOT NULL,
	`sku`          varchar(40)  NOT NULL,
	`product_name` varchar(200) NOT NULL,
	`qty`          int NOT NULL COMMENT 'จำนวนที่ตัดออก (บวก)',
	`unit_cost`    decimal(10,2) NOT NULL DEFAULT 0,
	`qty_before`   int NOT NULL DEFAULT 0,
	KEY `idx_issue` (`issue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK รายการเบิก/ตัดออก';
";

/* 10) ใบตรวจนับสต๊อก (AD-) */
$sqlArray[_DBPREFIX_ . 'stock_count'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_count` (
	`count_id`    int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`doc_no`      varchar(24)  NOT NULL,
	`branch_id`   int UNSIGNED NOT NULL,
	`doc_date`    date NOT NULL,
	`round_start` date NOT NULL COMMENT 'วันเริ่มรอบนับที่ใบนี้อยู่',
	`reason`      varchar(20)  NOT NULL DEFAULT '',
	`note`        varchar(255) NOT NULL DEFAULT '',
	`item_count`  int NOT NULL DEFAULT 0,
	`n_same`      int NOT NULL DEFAULT 0,
	`n_over`      int NOT NULL DEFAULT 0,
	`n_short`     int NOT NULL DEFAULT 0,
	`diff_value`  decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'มูลค่าส่วนต่างรวม (ทุน)',
	`status`      enum('posted','void') NOT NULL DEFAULT 'posted',
	`void_mode`   enum('void','edit') NULL,
	`void_reason` varchar(255) NOT NULL DEFAULT '',
	`void_by`     int UNSIGNED NULL,
	`void_date`   datetime NULL,
	`edit_of_id`  int UNSIGNED NULL,
	`created_by`  int UNSIGNED NOT NULL,
	`add_date`    datetime DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY `uq_doc` (`branch_id`, `doc_no`),
	KEY `idx_branch_round` (`branch_id`, `round_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ใบตรวจนับ';
";

$sqlArray[_DBPREFIX_ . 'stock_count_item'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_count_item` (
	`item_id`      int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`count_id`     int UNSIGNED NOT NULL,
	`branch_id`    int UNSIGNED NOT NULL COMMENT 'ซ้ำจากหัวใบ ใช้หา นับแล้วในรอบ เร็วขึ้น',
	`product_id`   int UNSIGNED NOT NULL,
	`sku`          varchar(40)  NOT NULL,
	`product_name` varchar(200) NOT NULL,
	`qty_system`   int NOT NULL COMMENT 'ยอดในระบบตอนบันทึก',
	`qty_counted`  int NOT NULL,
	`diff`         int NOT NULL COMMENT 'counted - system',
	`unit_cost`    decimal(10,2) NOT NULL DEFAULT 0,
	`add_date`     datetime DEFAULT CURRENT_TIMESTAMP,
	KEY `idx_count` (`count_id`),
	KEY `idx_bp_date` (`branch_id`, `product_id`, `add_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK รายการตรวจนับ';
";


/* ---------- 4. ประวัติการทำรายการ ---------- */

/* 11) ประวัติรายวันต่อสาขา */
$sqlArray[_DBPREFIX_ . 'stock_log'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_log` (
	`log_id`     bigint UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`branch_id`  int UNSIGNED NOT NULL,
	`log_date`   date NOT NULL,
	`type`       varchar(20)  NOT NULL,
	`title`      varchar(200) NOT NULL,
	`amount`     decimal(12,2) NULL,
	`doc_no`     varchar(24)  NOT NULL DEFAULT '',
	`detail`     text NULL,
	`created_by` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = ระบบ',
	`add_date`   datetime DEFAULT CURRENT_TIMESTAMP,
	KEY `idx_branch_date` (`branch_id`, `log_date`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ประวัติการทำรายการ';
";


/* ---------- 5. โมดูล POS — เปิด–ปิดร้าน · เงินสด · บิลขาย · รับคืน ---------- */

/* 12) เปิด–ปิดร้าน (1 แถว / สาขา / วัน) */
$sqlArray[_DBPREFIX_ . 'stock_store_day'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_store_day` (
	`day_id`        int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`branch_id`     int UNSIGNED NOT NULL,
	`store_date`    date NOT NULL,
	`status`        enum('open','closed') NOT NULL DEFAULT 'open',
	`reopen_count`  tinyint UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ผู้ดูแลเปิดร้านใหม่หลังปิดกี่ครั้ง',
	`opened_by`     int UNSIGNED NOT NULL,
	`opened_at`     datetime NOT NULL,
	`float_carried` decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'ยกมาจาก keep_cash เมื่อวาน',
	`float_counted` decimal(12,2) NULL COMMENT 'กรอกเมื่อนับเงินทอนยกมาได้ไม่ตรง',
	`open_reason`   varchar(255) NOT NULL DEFAULT '',
	`float_topup`   decimal(12,2) NOT NULL DEFAULT 0,
	`open_cash`     decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'เงินทอนเริ่มวัน',
	`closed_by`     int UNSIGNED NULL,
	`closed_at`     datetime NULL,
	`expected_cash` decimal(12,2) NULL,
	`counted_cash`  decimal(12,2) NULL,
	`keep_cash`     decimal(12,2) NULL COMMENT 'แยกไว้เป็นเงินทอนพรุ่งนี้',
	`handover_cash` decimal(12,2) NULL COMMENT 'counted - keep = นำส่ง',
	`close_note`    varchar(255) NOT NULL DEFAULT '',
	UNIQUE KEY `uq_branch_date` (`branch_id`, `store_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK POS เปิด-ปิดร้าน';
";
/* เพิ่มทีหลัง — Reinstall เพื่อเพิ่มคอลัมน์ */
$sqlAlter[_DBPREFIX_ . 'stock_store_day']['reopen_count'] = "ALTER TABLE `" . _DBPREFIX_ . "stock_store_day` ADD COLUMN `reopen_count` tinyint UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ผู้ดูแลเปิดร้านใหม่หลังปิดกี่ครั้ง' AFTER `status`";

/* 13) เงินเข้า / ออกลิ้นชักระหว่างวัน */
$sqlArray[_DBPREFIX_ . 'stock_cash_move'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_cash_move` (
	`cash_id`    int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`day_id`     int UNSIGNED NOT NULL,
	`branch_id`  int UNSIGNED NOT NULL,
	`direction`  enum('in','out') NOT NULL,
	`amount`     decimal(12,2) NOT NULL,
	`reason`     varchar(255) NOT NULL DEFAULT '',
	`created_by` int UNSIGNED NOT NULL,
	`add_date`   datetime DEFAULT CURRENT_TIMESTAMP,
	KEY `idx_day` (`day_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK POS เงินเข้าออกลิ้นชัก';
";

/* 14) บิลขาย */
$sqlArray[_DBPREFIX_ . 'stock_sale'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_sale` (
	`sale_id`       int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`doc_no`        varchar(24)  NOT NULL,
	`branch_id`     int UNSIGNED NOT NULL,
	`day_id`        int UNSIGNED NOT NULL COMMENT 'stock_store_day ที่บิลนี้อยู่',
	`sale_date`     date NOT NULL,
	`pay_method`    enum('cash','transfer') NOT NULL DEFAULT 'cash',
	`is_vat`        tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = บิล VAT (ใช้เลขชุด prefix_vat)',
	`base_amount`   decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'มูลค่าก่อน VAT (บิล VAT)',
	`vat_amount`    decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'VAT 7% (บิล VAT)',
	`item_count`    int NOT NULL DEFAULT 0,
	`total_qty`     int NOT NULL DEFAULT 0,
	`subtotal`      decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'ราคาเต็มรวมทุกรายการ ก่อนส่วนลด',
	`discount`      decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'ส่วนลดท้ายบิล = subtotal - total',
	`total`         decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'ยอดที่ลูกค้าจ่ายจริง (หลังส่วนลด)',
	`total_cost`    decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'ไว้คิดกำไรขั้นต้น',
	`received`      decimal(12,2) NOT NULL DEFAULT 0,
	`change_amount` decimal(12,2) NOT NULL DEFAULT 0,
	`status`        enum('paid','void') NOT NULL DEFAULT 'paid',
	`void_mode`     enum('void','edit') NULL,
	`void_reason`   varchar(255) NOT NULL DEFAULT '',
	`void_by`       int UNSIGNED NULL,
	`void_date`     datetime NULL,
	`edit_of_id`    int UNSIGNED NULL,
	`created_by`    int UNSIGNED NOT NULL COMMENT 'พนักงานที่ขาย',
	`add_date`      datetime DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY `uq_doc` (`branch_id`, `doc_no`),
	KEY `idx_branch_date` (`branch_id`, `sale_date`, `status`),
	KEY `idx_staff_date` (`created_by`, `sale_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK POS บิลขาย';
";

$sqlArray[_DBPREFIX_ . 'stock_sale_item'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_sale_item` (
	`item_id`      int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`sale_id`      int UNSIGNED NOT NULL,
	`product_id`   int UNSIGNED NOT NULL,
	`sku`          varchar(40)  NOT NULL,
	`product_name` varchar(200) NOT NULL,
	`qty`          int NOT NULL,
	`unit_price`   decimal(10,2) NOT NULL,
	`unit_cost`    decimal(10,2) NOT NULL DEFAULT 0,
	`line_total`   decimal(12,2) NOT NULL,
	KEY `idx_sale` (`sale_id`),
	KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK POS รายการในบิล';
";


/* 17) ใบรับคืนสินค้า (RT-) */
$sqlArray[_DBPREFIX_ . 'stock_return'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_return` (
	`return_id`   int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`doc_no`      varchar(24)  NOT NULL,
	`branch_id`   int UNSIGNED NOT NULL,
	`doc_date`    date NOT NULL,
	`day_id`      int UNSIGNED NOT NULL COMMENT 'stock_store_day วันที่จ่ายเงินคืน',
	`sale_id`     int UNSIGNED NOT NULL COMMENT 'บิลเดิม',
	`reason`      varchar(20)  NOT NULL,
	`note`        varchar(255) NOT NULL DEFAULT '',
	`restock`     tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=ของกลับเข้าสต๊อก',
	`item_count`  int NOT NULL DEFAULT 0,
	`total_qty`   int NOT NULL DEFAULT 0,
	`calc_amount` decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'ตามราคาที่ขาย',
	`refund`      decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'เงินสดที่คืนจริง',
	`refund_note` varchar(255) NOT NULL DEFAULT '',
	`photos`      varchar(600) NOT NULL DEFAULT '' COMMENT 'รูปแนบ JSON array ของพาธใต้ uploads/ (สูงสุด 3 รูป · stock/returns/)',
	`created_by`  int UNSIGNED NOT NULL COMMENT 'ผู้รับคืน',
	`add_date`    datetime DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY `uq_doc` (`branch_id`, `doc_no`),
	KEY `idx_sale` (`sale_id`),
	KEY `idx_branch_date` (`branch_id`, `doc_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK POS ใบรับคืนสินค้า';
";

$sqlArray[_DBPREFIX_ . 'stock_return_item'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_return_item` (
	`item_id`      int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`return_id`    int UNSIGNED NOT NULL,
	`sale_item_id` int UNSIGNED NOT NULL COMMENT 'แถวในบิลเดิม — ใช้รวมยอดที่คืนไปแล้ว',
	`product_id`   int UNSIGNED NOT NULL,
	`sku`          varchar(40)  NOT NULL,
	`product_name` varchar(200) NOT NULL,
	`qty`          int NOT NULL,
	`unit_price`   decimal(10,2) NOT NULL,
	`line_total`   decimal(12,2) NOT NULL,
	KEY `idx_return` (`return_id`),
	KEY `idx_sale_item` (`sale_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK POS รายการรับคืน';
";


/* 18) ตัวนับเลขที่เอกสาร */
$sqlArray[_DBPREFIX_ . 'stock_doc_seq'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_doc_seq` (
	`branch_id` int UNSIGNED NOT NULL,
	`series`    varchar(10)  NOT NULL COMMENT 'vat, novat, RC, IS, AD, RT',
	`period`    varchar(8)   NOT NULL COMMENT 'YYYYMM (บิล) หรือ YYMMDD (เอกสารคลัง)',
	`last_no`   int UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (`branch_id`, `series`, `period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ตัวนับเลขที่เอกสาร';
";


/* 19) ค่าตั้งของระบบ key / value */
$sqlArray[_DBPREFIX_ . 'stock_setting'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_setting` (
	`skey`       varchar(40)  NOT NULL PRIMARY KEY,
	`svalue`     text NOT NULL,
	`is_secret`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = เข้ารหัสไว้ (token / รหัสผ่าน)',
	`updated_by` int UNSIGNED NOT NULL DEFAULT 0,
	`updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ค่าตั้งของระบบ (การแจ้งเตือน ฯลฯ)';
";


/* 20) ประวัติการส่งแจ้งเตือน */
$sqlArray[_DBPREFIX_ . 'stock_notify_log'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_notify_log` (
	`log_id`     int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`channel`    enum('tg','mail') NOT NULL,
	`event`      varchar(20)  NOT NULL,
	`branch_id`  int UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = ทุกสาขา / ไม่ผูกสาขา',
	`ref_no`     varchar(24)  NOT NULL DEFAULT '' COMMENT 'เลขที่เอกสารที่เกี่ยวข้อง',
	`send_to`    varchar(500) NOT NULL DEFAULT '' COMMENT 'chat id / อีเมลผู้รับ',
	`subject`    varchar(255) NOT NULL DEFAULT '',
	`is_ok`      tinyint(1) NOT NULL DEFAULT 0,
	`error`      varchar(255) NOT NULL DEFAULT '',
	`created_by` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = ระบบ (cron)',
	`add_date`   datetime DEFAULT CURRENT_TIMESTAMP,
	KEY `idx_date` (`add_date`),
	KEY `idx_event` (`channel`, `event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ประวัติการส่งแจ้งเตือน Telegram / อีเมล';
";


/* ทุกตารางต้องอยู่ใน $aTablename ของ aModuleConfig.php — ไม่งั้นตัวติดตั้งข้าม */
