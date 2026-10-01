<?php
/* ==========================================================
   AOSTOCK DEMO — [ผู้ดูแล] ตัวอย่างอีเมลสรุปยอดขายรายวัน (แสดงใน popup ของหน้า adm-notify.php)
   ----------------------------------------------------------
   ?p[]=ส่วนที่ใส่ &b[]=สาขา (ไม่ส่ง = ทุกสาขา) &d=ปปปปดดวว (ไม่ส่ง = วันล่าสุดที่มียอดขาย)
   ส่งกลับ HTML ของอีเมลทั้งฉบับ — ชุดเดียวกับที่ส่งจริง (daily_summary_email_html)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user  = require_login();                // หน้า adm- : เฉพาะผู้ดูแล
$br    = demo_branches();
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
