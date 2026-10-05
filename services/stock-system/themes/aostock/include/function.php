<?php
/**
 * FILE: themes/aostock/include/function.php
 * ROLE: ตัวเริ่มต้นของหน้า AOSTOCK — ตรวจว่าโมดูล stock ถูกโหลด + ตั้งหน้า "ระบบขัดข้อง" (function ทั้งหมดอยู่ที่โมดูล stock)
 * DEPENDS: admweb/aowebdata/modules/stock/api.php (mainApi.php โหลดให้ผ่าน inc.php)
 * TABLES: -
 * TODO:
 *   - [x] ย้าย function ทั้งหมดไป admweb/aowebdata/modules/stock/api.php · ค่าคงที่จาก inc/config.php ไปไว้ที่ api.php ด้วย
 *   - [x] เก็บ error ทุกระดับลง error_log (ไม่แสดงบนหน้าเว็บ)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}

/* function ของ AOSTOCK มาจาก api.php ของโมดูล stock — ไม่มี = ยังไม่ได้ใส่ 'stock' ใน $aModuleUse ของไฟล์ fix */
if (!function_exists('require_login')) {
    http_response_code(500);
    exit('AOSTOCK: ยังไม่ได้เปิดโมดูล stock — เพิ่ม \'stock\' ใน $aModuleUse ของ admweb/aowebdata/fix.<โดเมน>.php');
}

/* mainApi.php สั่ง error_reporting(0) (UPSTREAM.md D11) — หน้า AOSTOCK เก็บทุก error ลง error_log แต่ไม่แสดงบนหน้าเว็บ */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

/* error ที่ไม่ได้จับ (เช่นฐานข้อมูลผิดพลาด) → หน้า "ระบบขัดข้อง" · รายละเอียดอยู่ใน error_log */
set_exception_handler('stock_error_page');
