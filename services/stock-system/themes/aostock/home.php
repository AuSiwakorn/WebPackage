<?php
/**
 * FILE: themes/aostock/home.php
 * ROLE: หน้าแรกของเว็บ — ส่งไปหน้า login หรือหน้าแรกของบทบาทที่เข้าระบบอยู่
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_staff (สถานะการเข้าระบบ ผ่าน api.php)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] อ่าน / เขียนข้อมูลจากตาราง ao_stock_* ผ่าน api.php (ช่วงที่ 5–9)
 *   - [x] ช่วงที่ 10: แก้ File Header ให้ตรงกับระบบจริง
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* เข้าที่หน้าแรกของเว็บ (/aostock/) → ยังไม่เข้าระบบไปหน้า login · เข้าแล้วไปหน้าแรกของบทบาทนั้น */
require_once dirname(__FILE__) . '/include/function.php';

header('Location: ' . url(is_logged_in() ? home_page(current_user()) : 'login.php'));
exit;
