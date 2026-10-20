<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api.php
 * ROLE: ตัวโหลด function ทั้งหมดของ AOSTOCK — โค้ดจริงแยกตามหมวดใน api/ (รายชื่อ function: FUNCTIONS.md)
 * DEPENDS: api/*.php · class DB + ค่าคงที่ของ admweb (_DBPREFIX_, URL_WEB_ROOT, PATH_UPLOAD, AOSTOCK_SECRET_KEY) · THEME_URL (inc.php)
 * TABLES: ao_stock_* (ดู DATABASE.md)
 * TODO:
 *   - [x] ประวัติการแก้ไขดู docs/CHANGELOG.md
 *
 * ⚠ ไฟล์นี้และทุกไฟล์ใน api/ โหลดทุก request ฝั่งหน้าเว็บ (mainApi.php) · หลังบ้านผ่าน function.php · cron ผ่าน mainApi.php
 *   ห้ามมีโค้ดทำงานตอนโหลด (echo / header / session / query / exit) — มีได้แค่ define() + function
 *   ชื่อ function ไม่สนตัวพิมพ์ — ห้ามชนกับ admweb (เช่น db_get = DB_GET) · ตั้งชื่อตาม CLAUDE.md (Stock_verbCamel)
 * กติกา: function ทุกตัวอยู่ใน api/ (ไฟล์ของหมวดนั้น · หมวดใหม่ = ไฟล์ใหม่ + require_once ด้านล่าง) · closure เฉพาะหน้าอยู่ในหน้านั้นได้
 * ไฟล์ใน api/:
 *   core ค่าคงที่ / ตัวช่วย / สิทธิ์ · db ฐานข้อมูล / ค่าตั้ง · branch-staff สาขา / พนักงาน · auth เข้าระบบ · product สินค้า / หมวด
 *   ledger สมุดสต๊อก / เอกสารคลัง · log ประวัติ · store เปิด–ปิดร้าน · sale ขาย · bill เลขที่บิล / บัญชี · receive รับเข้า
 *   issue เบิก / ตัดออก · count ตรวจนับ · return รับคืน · report รายงาน · notify แจ้งเตือน / cron
 */
require_once __DIR__ . '/api/core.php';
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/branch-staff.php';
require_once __DIR__ . '/api/auth.php';
require_once __DIR__ . '/api/product.php';
require_once __DIR__ . '/api/ledger.php';
require_once __DIR__ . '/api/log.php';
require_once __DIR__ . '/api/store.php';
require_once __DIR__ . '/api/sale.php';
require_once __DIR__ . '/api/bill.php';
require_once __DIR__ . '/api/receive.php';
require_once __DIR__ . '/api/issue.php';
require_once __DIR__ . '/api/count.php';
require_once __DIR__ . '/api/return.php';
require_once __DIR__ . '/api/report.php';
require_once __DIR__ . '/api/notify.php';
