<?php
/**
 * FILE: admweb/core/counter/database.php
 * ROLE: Schema ของ counter module — โหลดอัตโนมัติโดย setupModuleDB / inc_install
 * DEPENDS: -
 * TABLES: site_counter, site_counter_ref
 * TODO:
 *   - [x] สร้าง site_counter ด้วย Composite PK รองรับ DB_UPSERT
 *   - [x] เพิ่ม hour, device ใน PK สำหรับ analytics
 *   - [x] สร้าง site_counter_ref สำหรับ referrer domain tracking
 */

// ── Fresh install: CREATE TABLE (รวม hour + device ใน PK) ────────────────────
$sqlArray[_DBPREFIX_ . 'site_counter'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_counter` (
  `page`    varchar(255)  NOT NULL DEFAULT '' COMMENT 'ชื่อหน้า — ว่างคือ global counter',
  `type`    varchar(20)   NOT NULL DEFAULT 'web' COMMENT 'ประเภท: web หรือ admin',
  `year`    smallint      NOT NULL COMMENT 'ปี เช่น 2026',
  `month`   tinyint       NOT NULL COMMENT 'เดือน 1–12',
  `day`     tinyint       NOT NULL COMMENT 'วัน 1–31',
  `hour`    tinyint       NOT NULL DEFAULT 0 COMMENT 'ชั่วโมง 0–23',
  `device`  varchar(10)   NOT NULL DEFAULT 'desktop' COMMENT 'mobile หรือ desktop',
  `count`   int UNSIGNED  NOT NULL DEFAULT 0 COMMENT 'จำนวน page view',
  PRIMARY KEY (page, type, year, month, day, hour, device),
  INDEX idx_ym (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

// ── Existing install: เพิ่ม column ใหม่ ──────────────────────────────────────
// inc_install จะเช็ค column ก่อนรัน — ไม่ re-run ถ้ามีอยู่แล้ว
// ⚠️ หลังเพิ่ม column ต้อง rebuild PK ด้วยตนเองบน existing DB:
//    ALTER TABLE `{prefix}site_counter`
//      DROP PRIMARY KEY,
//      ADD PRIMARY KEY (page, type, year, month, day, hour, device);

$sqlAlter[_DBPREFIX_ . 'site_counter']['hour'] = "
ALTER TABLE `" . _DBPREFIX_ . "site_counter`
ADD COLUMN IF NOT EXISTS `hour` tinyint NOT NULL DEFAULT 0
  COMMENT 'ชั่วโมง 0–23' AFTER `day`;";

$sqlAlter[_DBPREFIX_ . 'site_counter']['device'] = "
ALTER TABLE `" . _DBPREFIX_ . "site_counter`
ADD COLUMN IF NOT EXISTS `device` varchar(10) NOT NULL DEFAULT 'desktop'
  COMMENT 'mobile หรือ desktop' AFTER `hour`;";

// ── Referrer Domain tracking ──────────────────────────────────────────────────
$sqlArray[_DBPREFIX_ . 'site_counter_ref'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_counter_ref` (
  `page`        varchar(255)  NOT NULL DEFAULT '' COMMENT 'ชื่อหน้าที่ถูกเปิด',
  `ref_domain`  varchar(255)  NOT NULL DEFAULT '' COMMENT 'domain ของ referrer (ไม่มี www. นำหน้า)',
  `year`        smallint      NOT NULL COMMENT 'ปี เช่น 2026',
  `month`       tinyint       NOT NULL COMMENT 'เดือน 1–12',
  `count`       int UNSIGNED  NOT NULL DEFAULT 0 COMMENT 'จำนวน traffic จาก referrer นี้ต่อเดือน',
  PRIMARY KEY (page, ref_domain, year, month),
  INDEX idx_ym (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";
