<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api.php
 * ROLE: ตัวโหลด function ทั้งหมดของ AOSTOCK (POS + คลัง + หลังร้าน) — โค้ดจริงแยกเป็นไฟล์ตามหมวดในโฟลเดอร์ api/
 *       ฝั่งหน้าเว็บ mainApi.php ของ admweb โหลดไฟล์นี้ให้ทุก request (include_once) · หลังบ้านโหลดผ่าน function.php ของโมดูล · cron.php ผ่าน mainApi.php
 * DEPENDS: api/*.php (16 ไฟล์ด้านล่าง) · class DB + ค่าคงที่ของ admweb (_DBPREFIX_, URL_WEB_ROOT, PATH_UPLOAD, AOSTOCK_SECRET_KEY จาก fix.<โดเมน>.php) · THEME_URL จาก inc.php (ฝั่งหน้าเว็บ)
 * TABLES: ao_stock_* (ดู TABLES ในหัวของแต่ละไฟล์ใน api/)
 * TODO:
 *   - [x] ย้ายจาก themes/aostock/include/function.php + ค่าคงที่จาก themes/aostock/inc/config.php
 *   - [x] ช่วงที่ 5: สาขา พนักงาน หมวด สินค้า → ฐานข้อมูล (ตัวช่วย sdb_* — คำนำหน้า sdb_ เพราะ stock_* / db_* ชนกับของเดิม)
 *   - [x] ช่วงที่ 6: สมุดสต๊อก (stock_move / stock_balance) · ใบรับเข้า / เบิก / ตรวจนับ · เลขที่เอกสาร · ประวัติการทำรายการ (stock_log)
 *   - [x] ช่วงที่ 7: เปิด–ปิดร้าน · เงินเข้า/ออกลิ้นชัก · บิลขาย (เลขบิลจาก stock_doc_seq) · รับคืน + รูปแนบ — ไม่มีข้อมูลร้านใน session แล้ว
 *   - [x] ช่วงที่ 8: รายงาน / ภาพรวม / บัญชีรายเดือน เป็น SQL ทั้งช่วง · ลบตัวสร้างข้อมูลสมมติ
 *   - [x] ช่วงที่ 9: ค่าตั้งใน ao_stock_setting (ค่าลับเข้ารหัส) · Telegram ตอนเกิดเหตุการณ์จริง · อีเมลรายวันผ่าน cron.php · สวิตช์เปิด–ปิดเมนูจากหลังบ้าน
 *   - [x] ช่วงที่ 10: แยกเป็น 16 ไฟล์ในโฟลเดอร์ api/ (ไฟล์นี้เหลือแค่ตัวโหลด) · ลบฟังก์ชันที่ไม่มีใครใช้ 31 ตัว
 *         · จดจำการเข้าสู่ระบบ 30 วัน · ลายนิ้วมือ PIN (เช็ก PIN ซ้ำตอนย้ายสาขา / เปิดใช้งาน) · เป้าต่อคนต่อวันรายสาขา
 *
 * ⚠ ไฟล์นี้และทุกไฟล์ใน api/ ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb (รวม endpoint อื่น เช่น admweb/api/BACKUP/monitor.php)
 *   ห้ามมีโค้ดที่ทำงานทันทีตอนโหลด (echo, header, session, query, exit) — มีได้แค่ define() และประกาศ function
 *   ชื่อ function ของ PHP ไม่สนตัวพิมพ์ — ห้ามตั้งชื่อชนกับ admweb (เช่น db_get = DB_GET) · function ใหม่ตั้งชื่อเฉพาะ แล้วเช็กไม่ให้ซ้ำกับที่มีอยู่
 *
 * กติกา
 *   - function PHP ของ AOSTOCK ทุกตัวอยู่ในโฟลเดอร์ api/ เท่านั้น ห้ามประกาศ function ในหน้าเว็บหรือใน themes/aostock/inc/
 *   - เพิ่ม function ใหม่ให้ใส่ในไฟล์ของหมวดนั้น · หมวดใหม่ทั้งก้อน = สร้างไฟล์ใหม่ใน api/ แล้วเพิ่มบรรทัด require_once ด้านล่าง
 *   - closure ที่ใช้เฉพาะในหน้า (เช่น $qs = function(...)) อยู่ในหน้านั้นได้
 *
 * ไฟล์ในโฟลเดอร์ api/ (ลำดับการโหลดไม่มีผลกับ function — โหลดครบก่อนหน้าเว็บเรียกใช้)
 *   core.php          ค่าคงที่ของระบบ · e() / url() / money / วันที่ไทย / CSV · สวิตช์เมนู / บทบาท / สิทธิ์
 *   db.php            sdb_* (ฐานข้อมูล) · หน้าระบบขัดข้อง · ค่าตั้ง key/value + เข้ารหัสค่าลับ
 *   branch-staff.php  สาขา · พนักงาน / ผู้ดูแล / บัญชี · ค่าตั้งของสาขา · PIN · ตัวช่วยหน้าจัดการสาขา / พนักงาน
 *   auth.php          เข้า / ออกระบบ · CSRF · จดจำการเข้าสู่ระบบ · require_login
 *   product.php       สินค้า · หมวด · รูป · ยอดคงเหลือ / สถานะสต๊อก · หน้าสินค้าในสต๊อก
 *   ledger.php        สมุดสต๊อก · เลขที่เอกสาร · อ่าน / ยกเลิกเอกสารคลัง · ประวัติเคลื่อนไหว · แก้ / ยกเลิกย้อนหลัง
 *   log.php           ประวัติการทำรายการ (stock_log)
 *   store.php         เปิด / ปิดร้าน · เงินทอน · เงินเข้า / ออกลิ้นชัก
 *   sale.php          ตะกร้า · บิลขาย · ยกเลิก / แก้ไขบิล
 *   bill.php          เลขที่บิล / VAT · หัวบิล / พิมพ์บิล · ค่าตั้งฝ่ายบัญชี · สรุปบิลขายและเงินเข้า
 *   receive.php       รับสินค้าเข้า
 *   issue.php         เบิก / ตัดออก
 *   count.php         ตรวจนับ / ปรับยอด · รอบตรวจนับ
 *   return.php        รับคืนสินค้า
 *   report.php        รายงานยอดขาย · ตารางรายวัน · สินค้าขายดี · ภาพรวมพนักงาน / ผู้ดูแล
 *   notify.php        Telegram · อีเมลสรุปรายวัน · ประวัติการส่ง · งานของ cron
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
