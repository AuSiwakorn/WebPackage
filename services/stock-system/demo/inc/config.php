<?php
/* ==========================================================
   AOSTOCK DEMO — ค่าคงที่ของระบบ และเริ่ม session
   ----------------------------------------------------------
   ไฟล์นี้เก็บเฉพาะค่าคงที่และการเริ่ม session — function ทั้งหมดอยู่ที่ include/function.php
   (include/function.php เรียกไฟล์นี้ให้เองแล้ว หน้าเว็บ include แค่ function.php ไฟล์เดียว)
   เดโมนี้ยังไม่ต่อฐานข้อมูล ข้อมูลตัวอย่างอยู่ใน function demo_*() ของ function.php
   เมื่อทำระบบจริง: ย้าย demo_users() ไปเป็นตารางในฐานข้อมูล
   และเก็บรหัสผ่านด้วย password_hash() / ตรวจด้วย password_verify()

   เขียนให้รองรับ PHP 5.4 ขึ้นไป (ไม่ใช้ ??, <=>, const array)
   ========================================================== */

define('APP_NAME',  'AOSTOCK');
define('APP_TITLE', 'ระบบบริหารสต๊อกสินค้า');
define('APP_OWNER', 'บริษัท เอโอซอฟต์ จำกัด');

$__script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/';
define('APP_BASE', rtrim(str_replace('\\', '/', dirname($__script)), '/'));
unset($__script);

/* ---------- บิลและ VAT ---------- */
define('VAT_RATE', 7);
define('BILL_RUN_DIGITS', 4);

/* ---------- ประวัติเคลื่อนไหว ---------- */
define('MOVE_SEED_DAYS', 13);          // จำนวนวันย้อนหลังที่สร้างข้อมูลสมมติ (ไม่รวมวันนี้)

/* ---------- session (ยังไม่ต่อฐานข้อมูล ใช้ session เก็บทุกอย่าง) ---------- */
if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']);
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array(
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $secure,
        ));
    } else {
        // PHP 5.x – 7.2 ยังไม่รองรับรูปแบบ array
        session_set_cookie_params(0, '/', '', $secure, true);
    }
    session_start();
}
