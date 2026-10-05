<?php
/**
 * FILE: admweb/aowebdata/modules/stock/database.php
 * ROLE: โครงสร้างฐานข้อมูล AOSTOCK (ระบบบริหารสต๊อก + POS) — inc_install.php ของ admweb โหลดไฟล์นี้อัตโนมัติ
 * DEPENDS: - (ถูก include โดย admweb/plugins/db/inc_install.php · ต้องมี 'stock' ใน $aModuleUse)
 * TABLES: ao_stock_* 24 ตาราง (รายชื่อเดียวกับ $aTablename ใน aModuleConfig.php)
 * TODO:
 *   - [x] ย้ายจาก database/database.php เข้าโมดูล stock
 *   - [x] utf8mb4 ทุกตาราง (การเชื่อมต่อตั้ง charset=utf8mb4 ที่ plugins/db/function/php_v8.php)
 *   - [x] stock_doc_seq ใช้คอลัมน์ period รองรับทั้งเลขบิลรายเดือนและเลขเอกสารคลังรายวัน
 *   - [x] ช่วงที่ 7: stock_store_day.reopen_count ($sqlAlter) · เงินคืนลูกค้าไม่ลง stock_cash_move
 *   - [x] ช่วงที่ 10: stock_branch.daily_goal + stock_staff.pin_fp ($sqlAlter) · ตารางใหม่ stock_remember (จดจำการเข้าสู่ระบบ) — ต้องกด Reinstall
 *   - [x] ช่วงที่ 11: สิทธิ์พนักงานชุดใหม่ใน stock_staff.perms (อธิบายด้านล่าง) — โครงสร้างไม่เปลี่ยน ไม่ต้อง Reinstall
 *   - [ ] เพิ่มคอลัมน์ทีหลัง: ใส่ $sqlAlter[ตาราง][คอลัมน์] = "ALTER TABLE ... ADD COLUMN ..." (ไม่ต้องใส่ IF NOT EXISTS — ตัวติดตั้งเช็กคอลัมน์ให้เอง)
 */
/* ==========================================================
   PREFIX  : _DBPREFIX_ ของ admweb (= ao_) + stock_  →  ao_stock_{ชื่อตาราง}
   ติดตั้ง : หลังบ้าน admweb → index.php?module=siteconfig&mp=db → Run (ข้ามตารางที่มีอยู่แล้ว)
   MYSQL   : InnoDB · utf8mb4 / utf8mb4_unicode_ci · ต้องการ MySQL 5.6.5+ หรือ MariaDB 10.0+ (DATETIME DEFAULT CURRENT_TIMESTAMP)
             ทุก index ที่เป็น varchar ยาวไม่เกิน 191 ตัวอักษร (ขีดจำกัด 767 byte ของ InnoDB รุ่นเก่ากับ utf8mb4)
   PHP     : ไฟล์นี้มีแค่ string — admweb ต้องการ PHP 8

   หลักการ
   1. สต๊อกคงเหลือ = ผลรวมของ stock_move ทุกการเปลี่ยนแปลงสต๊อกต้องเขียน stock_move
      ห้ามแก้ยอดตรง ๆ · stock_balance เป็นแค่ cache ให้อ่านเร็ว สร้างใหม่จาก stock_move ได้เสมอ
   2. สาขาเป็นแกนกลาง — เอกสารทุกใบผูก branch_id ของ "ตอนทำรายการ" (ไม่อ่านจากโปรไฟล์พนักงาน)
   3. เอกสารไม่ลบ — ยกเลิกด้วย status='void' + เหตุผล แล้วเขียน stock_move กลับรายการ
      "แก้ไขใบ" = ยกเลิกใบเดิม (void_mode='edit') แล้วออกใบใหม่ที่ edit_of_id ชี้กลับมา
   4. รายการในใบเก็บ snapshot (sku, ชื่อ, ราคา/ทุน ณ ตอนนั้น) เพื่อให้รายงานย้อนหลังถูก
   5. ผู้ทำรายการทุกคน (พนักงาน PIN และผู้ดูแล) อยู่ใน stock_staff → created_by ใช้ staff_id ฟิลด์เดียว

   ขอบเขต
   - แกน AOSTOCK      : ตาราง 1–11
   - โมดูล POS (Option): เปิด–ปิดร้าน, เงินสด, บิลขาย, รับคืนสินค้า
   - ยังไม่ทำ          : โอนระหว่างสาขา (ใช้เบิกออก reason='branch' + นำเข้าแทน), IMEI/Serial,
                         ผู้จำหน่าย, ลูกค้า/สะสมแต้ม, จ่ายเงินหลายช่องทางในบิลเดียว

   aModuleConfig.php → $aTablename ต้องมีทุกตารางด้านล่าง (ดูรายการท้ายไฟล์)
   ========================================================== */


/* ==========================================================
   1. ข้อมูลหลัก
   ========================================================== */

/* 1) สาขา — รวมค่าตั้งของสาขาที่ผู้ดูแลกำหนด (วันเริ่มรอบนับ, จำนวนที่นับได้ระหว่างเปิดร้าน, วันย้อนหลัง, เงินทอน) */
$sqlArray[_DBPREFIX_ . 'stock_branch'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_branch` (
	`branch_id`        int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`code`             varchar(10)  NOT NULL COMMENT 'รหัสสั้น เช่น HQ, RS, BN',
	`name`             varchar(100) NOT NULL COMMENT 'ชื่อเต็ม เช่น สาขารังสิต',
	`short_name`       varchar(40)  NOT NULL DEFAULT '' COMMENT 'ชื่อย่อบนแถบ/การ์ด',
	`address`          varchar(255) NOT NULL DEFAULT '',
	`phone`            varchar(30)  NOT NULL DEFAULT '',
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
	`count_open_limit` smallint UNSIGNED NOT NULL DEFAULT 1 COMMENT 'ระหว่างร้านเปิดนับได้ครั้งละกี่รายการ (0=ไม่จำกัด)',
	`default_float`    decimal(12,2) NOT NULL DEFAULT 0 COMMENT 'เงินทอนมาตรฐาน (โมดูล POS)',
	`backdate_days`    smallint UNSIGNED NOT NULL DEFAULT 7 COMMENT 'แก้เอกสาร/รับคืนย้อนหลังได้กี่วัน ผู้ดูแลตั้ง',
	`daily_goal`       int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'เป้าชิ้นต่อคนต่อวัน (ภาพรวมพนักงาน) 0 = ไม่ตั้งเป้า',
	`allow_negative`   tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=ยอมให้สต๊อกติดลบ',
	`is_active`        tinyint(1) NOT NULL DEFAULT 1,
	`sort`             int NOT NULL DEFAULT 0,
	`add_date`         datetime DEFAULT CURRENT_TIMESTAMP,
	`edit_date`        datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY `uq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK สาขา';
";
/* เพิ่ม 5 ต.ค. 2026 (ช่วงที่ 10) — ฐานข้อมูลที่ติดตั้งก่อนหน้านี้ กด Reinstall อีกครั้งเพื่อเพิ่มคอลัมน์ */
$sqlAlter[_DBPREFIX_ . 'stock_branch']['daily_goal'] = "ALTER TABLE `" . _DBPREFIX_ . "stock_branch` ADD COLUMN `daily_goal` int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'เป้าชิ้นต่อคนต่อวัน (ภาพรวมพนักงาน) 0 = ไม่ตั้งเป้า' AFTER `backdate_days`";

/* 2) ผู้ใช้ระบบ — พนักงาน (PIN 4 หลัก + เลือกชื่อ) และผู้ดูแล (username/password) อยู่ตารางเดียวกัน
      role: staff=พนักงาน (เมนูตามที่ติ๊กใน perms เฉพาะสาขาตนเอง) · admin=ผู้ดูแล (ทุกสาขา ไม่ขาย)
      ไม่มีบทบาทหัวหน้าคลัง — ใช้สิทธิ์เสริม perms ติ๊กให้พนักงานรายคนแทน
      ผู้ดูแลเพิ่ม / แก้ / พักงาน / ลบ พนักงานได้ที่หน้า "จัดการพนักงาน" (ลบได้เฉพาะคนที่ยังไม่เคยทำรายการ)
      perms (คั่นด้วย ,) — ช่วงที่ 11 ขึ้นต้นด้วยตัวบอกรุ่น v2 ตามด้วยสิทธิ์ 21 ตัว (รายการเต็ม: perm_list ใน api/core.php)
        หน้าร้าน:  sale discount store store_reopen cash bill_fix
        งานคลัง:   receive issue stocktake doc_fix
        รับคืน:    refund refund_cash        หมวดสินค้า: category_add category_del
        ดูข้อมูล:  products movements history report
        สิทธิ์เสริม: void_others backdate report_branch
      แถวที่ไม่มี v2 = สิทธิ์ชุดเดิม 7 ตัว (sale receive issue stocktake history refund category) → แปลงตอนอ่าน ไม่ต้อง Reinstall
      admin ได้ทุกสิทธิ์ ยกเว้นงานหน้าร้าน / งานคลัง / หมวดสินค้าฝั่งพนักงาน (ผู้ดูแลไม่ขาย / ไม่เปิด-ปิดร้าน)
      branch_id = สาขาปัจจุบัน (admin ใส่สาขาหลักได้ แต่สลับดูทุกสาขา)
      fail_count / locked_until = กรอกผิด 5 ครั้งล็อก 15 นาที
      pin_fp = ลายนิ้วมือของ PIN (HMAC-SHA256 ด้วย AOSTOCK_SECRET_KEY) ไว้เช็ก PIN ซ้ำในสาขาตอนย้ายสาขา / เปิดใช้งานกลับ
               (pin_hash เทียบกันเองไม่ได้) · พนักงานเดิมได้ค่าตอนเข้าระบบด้วย PIN ครั้งถัดไป */
$sqlArray[_DBPREFIX_ . 'stock_staff'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_staff` (
	`staff_id`      int UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	`branch_id`     int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'สาขาปัจจุบัน',
	`username`      varchar(40)  NOT NULL COMMENT 'ใช้ภายใน/ผู้ดูแลใช้ login',
	`name`          varchar(60)  NOT NULL COMMENT 'ชื่อที่แสดงบนรายการ/เอกสาร',
	`initials`      varchar(6)   NOT NULL DEFAULT '' COMMENT 'อักษรย่อบนปุ่มเลือกชื่อ',
	`role`          enum('staff','admin','account') NOT NULL DEFAULT 'staff' COMMENT 'account = ฝ่ายบัญชี: ดูบิล/เงินเข้าทุกสาขา + ตั้งเลขที่บิล',
	`perms`         varchar(255) NOT NULL DEFAULT '' COMMENT 'เมนู + สิทธิ์เสริม คั่นด้วย , (admin ได้ทุกสิทธิ์ ยกเว้น sale)',
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
/* เพิ่ม 5 ต.ค. 2026 (ช่วงที่ 10) — กด Reinstall อีกครั้งเพื่อเพิ่มคอลัมน์ */
$sqlAlter[_DBPREFIX_ . 'stock_staff']['pin_fp'] = "ALTER TABLE `" . _DBPREFIX_ . "stock_staff` ADD COLUMN `pin_fp` char(64) NULL COMMENT 'HMAC ของ PIN ไว้เช็กซ้ำในสาขา (ช่วงที่ 10)' AFTER `pin_hash`";

/* 2b) จดจำการเข้าสู่ระบบ 30 วัน (ช่อง "จดจำการเข้าสู่ระบบ" ของผู้ดูแล / ฝ่ายบัญชี) — 1 แถว / 1 เครื่อง
       cookie = selector:validator · เก็บเฉพาะ sha256 ของ validator · ใช้แล้วเปลี่ยน validator ใหม่ทุกครั้ง
       ออกจากระบบ = ลบแถวของเครื่องนั้น · เปลี่ยนรหัสผ่าน / พักงาน / ลบบัญชี / validator ผิด = ลบทุกเครื่องของคนนั้น */
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

/* 3) ประวัติการประจำสาขาของพนักงาน — ใช้ตอบ "พนักงานคนนี้อยู่สาขาไหน ณ วันที่ X"
      แถวปัจจุบัน date_to = NULL · ย้ายสาขา = ปิดแถวเดิม + เพิ่มแถวใหม่ + อัปเดต stock_staff.branch_id */
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

/* 4) หมวดสินค้า — พนักงานที่มีสิทธิ์ category / ผู้ดูแลเพิ่มได้ · created_by 0 = ระบบ / นำเข้า (แสดงเป็น "หมวดตั้งต้น") */
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
/* เพิ่ม 2 ต.ค. 2026 — ฐานข้อมูลที่ติดตั้งก่อนหน้านี้ กด Reinstall อีกครั้งเพื่อเพิ่มคอลัมน์ */
$sqlAlter[_DBPREFIX_ . 'stock_category']['created_by'] = "ALTER TABLE `" . _DBPREFIX_ . "stock_category` ADD COLUMN `created_by` int UNSIGNED NOT NULL DEFAULT 0 COMMENT 'staff_id ผู้เพิ่ม (0 = ระบบ / นำเข้า)' AFTER `is_active`";

/* 5) สินค้า (SKU) — ไม่จำกัดจำนวน · 1 สี/รุ่น = 1 SKU
      barcode เก็บไว้ก่อน (เรื่องบาร์โค้ดยังรอตัดสิน) · image = path จาก UpFile */
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


/* ==========================================================
   2. สต๊อก
   ========================================================== */

/* 6) ยอดคงเหลือต่อสาขา (cache) — อัปเดตพร้อมกับทุกครั้งที่เขียน stock_move */
$sqlArray[_DBPREFIX_ . 'stock_balance'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_balance` (
	`branch_id`  int UNSIGNED NOT NULL,
	`product_id` int UNSIGNED NOT NULL,
	`qty`        int NOT NULL DEFAULT 0,
	`edit_date`  datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`branch_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ยอดคงเหลือ (cache จาก stock_move)';
";

/* 7) สมุดความเคลื่อนไหว (ledger) — แหล่งความจริงหนึ่งเดียว
      type : receive / receive_void · issue / issue_void · adjust / adjust_void · sale / sale_void / return (POS)
      qty  : มีเครื่องหมาย + เพิ่ม / − ลด
      ref_type + ref_id = เอกสารต้นทาง (receive, issue, count, sale) · doc_no เก็บซ้ำไว้แสดงผลเร็ว */
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


/* ==========================================================
   3. เอกสารคลัง — รับเข้า · เบิก/ตัดออก · ตรวจนับ
   ทุกใบมีโครงเดียวกัน: doc_no (XX-YYMMDD-NNNN รันต่อสาขาต่อวัน) · status posted/void
   · void_mode void=ยกเลิก / edit=ยกเลิกเพื่อแก้ · edit_of_id = ใบเดิมที่ถูกแก้ · void_reason บังคับกรอก
   ========================================================== */

/* 8) ใบรับเข้า (RC-) — ยกเลิกไม่ได้ถ้าของขาย/ตัดออกไปจนยอดไม่พอถอน */
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

/* 9) ใบเบิก / ตัดออก (IS-) — ตัดเกินยอดคงเหลือไม่ได้
      reason: use เบิกใช้ · damaged ชำรุด · expired หมดอายุ · return คืนผู้จำหน่าย
              · branch ส่งไปสาขาอื่น (ใช้แทนการโอน ต้องกรอก note) · lost สูญหาย · other อื่น ๆ */
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

/* 10) ใบตรวจนับ / ปรับยอด (AD-) — กรอก "ยอดที่นับได้จริง" ระบบคิดส่วนต่างกับยอด ณ ตอนบันทึก
       reason: miscount นับผิดครั้งก่อน · unlogged ใช้/เสียไม่ได้บันทึก · wrongkey บันทึกผิด
               · found เจอของเพิ่ม · lost ของหาย · other อื่น ๆ (lost/other ต้องกรอก note)
       รอบการนับ: คำนวณจาก stock_branch.count_day ไม่มีตารางรอบแยก
       "นับแล้วในรอบนี้" = มี count_item ของสินค้านั้นในช่วงรอบ ที่ใบยังไม่ถูก void */
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


/* ==========================================================
   4. ประวัติการทำรายการ (หน้า "ประวัติการทำรายการ")
   ========================================================== */

/* 11) log รายวันต่อสาขา — type: open close sale void receive rvoid issue ivoid adjust avoid cash stock
       detail เก็บเป็น JSON ของคู่ "หัวข้อ => ค่า" ที่แสดงในหน้าประวัติ (json_encode ใช้ได้ใน PHP 5.6) */
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


/* ==========================================================
   5. โมดูล POS (Option) — เปิด–ปิดร้าน · เงินสด · บิลขาย
   ติดตั้งเฉพาะลูกค้าที่ซื้อ POS · ถ้าไม่ใช้ ตัดส่วนนี้ออกได้โดยแกนยังทำงานครบ
   ========================================================== */

/* 12) เปิด–ปิดร้าน (1 แถว / สาขา / วัน) — เงินทอนเป็นก้อนหมุนเวียน keep_cash → float_carried ของวันถัดไป
       เปิดร้านใหม่หลังปิด (ผู้ดูแล): status กลับเป็น open · reopen_count + 1 · ค่า closed_* / counted_cash / expected_cash
       ยังเก็บผลของรอบที่ปิดไปจนกว่าจะปิดใหม่ · วันที่ลืมปิดค้าง status open ไว้ (วันถัดไปเปิดได้ เงินทอนยกมาใช้ default_float) */
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
/* เพิ่ม 2 ต.ค. 2026 (ช่วงที่ 7) — ฐานข้อมูลที่ติดตั้งก่อนหน้านี้ กด Reinstall อีกครั้งเพื่อเพิ่มคอลัมน์ */
$sqlAlter[_DBPREFIX_ . 'stock_store_day']['reopen_count'] = "ALTER TABLE `" . _DBPREFIX_ . "stock_store_day` ADD COLUMN `reopen_count` tinyint UNSIGNED NOT NULL DEFAULT 0 COMMENT 'ผู้ดูแลเปิดร้านใหม่หลังปิดกี่ครั้ง' AFTER `status`";

/* 13) เงินเข้า/ออกลิ้นชักระหว่างวัน (เติมเงินทอน / หยิบออก) ที่พนักงานกรอกเองในหน้าเปิด–ปิดร้าน
       เงินคืนลูกค้า (รับคืนสินค้า) ไม่ลงตารางนี้ — คิดจาก stock_return.refund ของ day_id เดียวกัน (ไม่เก็บซ้ำสองที่) */
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

/* 14) บิลขาย — ชำระ 1 ช่องทางต่อบิล (เงินสด / โอน-พร้อมเพย์)
       พนักงานเลือกตอนขายว่า VAT หรือไม่ · เลขที่แยกชุดตามสาขา + ชุด:
       {prefix}{YYYY}-{MM}-{เลขรัน 4 หลัก} เช่น BP2026-01-0001 นับใหม่ทุกเดือน (ดู stock_doc_seq)
       ราคาขายรวม VAT แล้ว → base_amount + vat_amount = total
       ส่วนลดท้ายบิล: คนขายแก้ "ยอดที่ต้องชำระ" ได้เอง (ลูกค้าต่อราคา) → discount = subtotal − total
       เช่น ราคาเต็ม 520 ลูกค้าต่อเหลือ 500 → subtotal 520 · discount 20 · total 500 (total ต้อง > 0 และไม่เกิน subtotal) */
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


/* 17) ใบรับคืนสินค้า (RT-) — อ้างอิงบิลเดิม คืนบางรายการ/บางชิ้นได้
       กติกา: ต้องมีสิทธิ์ refund · บิลไม่เก่ากว่า stock_branch.backdate_days
       · คืนเป็นเงินสดจากลิ้นชักของวันนี้เท่านั้น (day_id = วันนี้ · ยอดเงินที่ควรมีในลิ้นชักหัก refund ของ day_id นี้)
       · reason กำหนดว่าของกลับเข้าสต๊อกไหม: wrong ซื้อผิดรุ่น/ผิดแบบ → restock=1 (stock_move type=return)
         defect ชำรุด · used ใช้แล้ว · other อื่น ๆ → restock=0 ไม่เข้าสต๊อก แยกเก็บ
       · refund แก้ได้ 0..calc_amount ถ้าไม่เท่ากับ calc ต้องมี refund_note
       · บิลที่มีการรับคืนแล้ว ยกเลิก/แก้ทั้งบิลไม่ได้ */
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


/* 18) ตัวนับเลขที่เอกสาร — กันเลขซ้ำ/ข้ามเมื่อทำรายการพร้อมกันหลายเครื่อง
       ใช้ทั้งแกน AOSTOCK และโมดูล POS (ถ้าตัดโมดูล POS ออก ห้ามตัดตารางนี้)
       ออกเลข: SELECT ... FOR UPDATE แถว (branch, series, period) → last_no + 1 → UPDATE ในทรานแซกชันเดียวกับ INSERT เอกสาร
       series + period:
         vat / novat         + YYYYMM  (เช่น 202601)  เลขบิลขาย นับใหม่ทุกเดือน
         RC / IS / AD / RT   + YYMMDD  (เช่น 260115)  ใบรับเข้า / เบิก / ตรวจนับ / รับคืน นับใหม่ทุกวัน */
$sqlArray[_DBPREFIX_ . 'stock_doc_seq'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_doc_seq` (
	`branch_id` int UNSIGNED NOT NULL,
	`series`    varchar(10)  NOT NULL COMMENT 'vat, novat, RC, IS, AD, RT',
	`period`    varchar(8)   NOT NULL COMMENT 'YYYYMM (บิล) หรือ YYMMDD (เอกสารคลัง)',
	`last_no`   int UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (`branch_id`, `series`, `period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ตัวนับเลขที่เอกสาร';
";


/* 19) ค่าตั้งของระบบแบบ key / value (ใช้ร่วมทุกสาขา) — ตอนนี้ใช้กับการแจ้งเตือน (หน้า "ตั้งค่าการแจ้งเตือน")
       key ที่ใช้: tg_on, tg_token*, tg_chats, tg_events (JSON), tg_branches (JSON, ว่าง = ทุกสาขา), tg_silent,
                   mail_on, mail_to, mail_time (HH:MM), mail_days (open/all), mail_branches (JSON), mail_parts (JSON),
                   mail_last_sent (Y-m-d ที่ส่งไปแล้ว — กันส่งซ้ำ),
                   smtp_host, smtp_port, smtp_secure (tls/ssl/none), smtp_user, smtp_pass*, from_name, from_email
       * = ค่าลับ เก็บแบบเข้ารหัส (is_secret = 1) ไม่ส่งกลับไปแสดงบนหน้าเว็บ
       อีเมลรายวัน: cron ทุก 5 นาที → ถ้า mail_on = 1 และเวลาเลย mail_time และ mail_last_sent ≠ วันนี้ → ส่งแล้วบันทึก log */
$sqlArray[_DBPREFIX_ . 'stock_setting'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "stock_setting` (
	`skey`       varchar(40)  NOT NULL PRIMARY KEY,
	`svalue`     text NOT NULL,
	`is_secret`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = เข้ารหัสไว้ (token / รหัสผ่าน)',
	`updated_by` int UNSIGNED NOT NULL DEFAULT 0,
	`updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AOSTOCK ค่าตั้งของระบบ (การแจ้งเตือน ฯลฯ)';
";


/* 20) ประวัติการส่งแจ้งเตือน — ทั้งที่สำเร็จและไม่สำเร็จ (ใช้แสดง "ประวัติการส่งล่าสุด" และไล่ปัญหา)
       channel: tg / mail · event: close, cash_diff, void, refund, reopen, lost, open, low, backdate, daily, test */
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


/* ==========================================================
   ตารางทุกตัวด้านบนต้องลงทะเบียนใน $aTablename ของ aModuleConfig.php ด้วย
   ไม่งั้นตัวติดตั้ง (inc_install.php) จะข้าม ไม่สร้างตารางนั้น
   ========================================================== */
