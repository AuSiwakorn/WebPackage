<?php
/**
 * FILE: themes/aostock/logout.php
 * ROLE: ออกจากระบบ (ล้างเฉพาะข้อมูลของผู้ใช้คนนั้น) แล้วกลับหน้า login
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_remember (ลบการจดจำของเครื่องนี้ ผ่าน api.php — logout_user)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] อ่าน / เขียนข้อมูลจากตาราง ao_stock_* ผ่าน api.php (ช่วงที่ 5–9)
 *   - [x] ช่วงที่ 10: แก้ File Header ให้ตรงกับระบบจริง · ออกจากระบบแล้วลบการจดจำของเครื่องนี้ (logout_user)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
require_once dirname(__FILE__) . '/include/function.php';

logout_user();
header('Location: ' . url('login.php'));
exit;
