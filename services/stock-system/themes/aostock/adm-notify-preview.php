<?php
/**
 * FILE: themes/aostock/adm-notify-preview.php
 * ROLE: [ผู้ดูแล] ตัวอย่างอีเมลสรุปยอดขายรายวัน (แสดงใน popup ของหน้า adm-notify.php)
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_sale, ao_stock_sale_item, ao_stock_return, ao_stock_store_day, ao_stock_balance (ผ่าน api.php — daily_summary_data)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 8: ผลปิดร้านจริงจาก ao_stock_store_day · ยอดเป็น SQL ทั้งช่วง
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   [ผู้ดูแล] ตัวอย่างอีเมลสรุปยอดขายรายวัน (แสดงใน popup ของหน้า adm-notify.php)
   ----------------------------------------------------------
   ?p[]=ส่วนที่ใส่ &b[]=สาขา (ไม่ส่ง = ทุกสาขา) &d=ปปปปดดวว (ไม่ส่ง = วันล่าสุดที่มียอดขาย)
   ส่งกลับ HTML ของอีเมลทั้งฉบับ — ชุดเดียวกับที่ส่งจริง (daily_summary_email_html)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user  = require_login();                // หน้า adm- : เฉพาะผู้ดูแล
$br    = branches_active();
$parts = isset($_GET['p']) && is_array($_GET['p']) ? array_values(array_intersect(array_keys(notify_mail_parts()), $_GET['p'])) : notify_get('mail_parts');
$codes = isset($_GET['b']) && is_array($_GET['b']) ? array_values(array_intersect(array_keys($br), $_GET['b'])) : array();
if (!$codes) {
    $codes = array_keys($br);
}
$ts = isset($_GET['d']) ? strtotime(preg_replace('/\D/', '', (string) $_GET['d'])) : false;
if ($ts === false || $ts > time()) {
    $ts = notify_last_sales_day($codes);
}
$data = daily_summary_data($ts, $codes);
header('Content-Type: text/html; charset=utf-8');
echo daily_summary_email_html($ts, $data, $parts);
