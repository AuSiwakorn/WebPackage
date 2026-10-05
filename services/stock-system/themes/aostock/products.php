<?php
/**
 * FILE: themes/aostock/products.php
 * ROLE: รายการสินค้าในสต๊อกของสาขาตัวเอง
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/products-live.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_product, ao_stock_category, ao_stock_balance (ผ่าน api.php)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] อ่าน / เขียนข้อมูลจากตาราง ao_stock_* ผ่าน api.php (ช่วงที่ 5–9)
 *   - [x] ช่วงที่ 10: แก้ File Header ให้ตรงกับระบบจริง
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   รายการสินค้าในสต๊อกของสาขาตัวเอง
   ----------------------------------------------------------
   พนักงานเห็นเฉพาะสาขาที่ผูกไว้กับรหัสของตัวเอง
   ยอดคงเหลือรวมของที่ขายออก / คืนกลับ ระหว่างวันแล้ว (อยู่ใน session)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();
$code = work_branch($user);          // ผู้ดูแลเลือกสาขาได้จากแถบบน

/* งานคลังไม่ผูกกับการเปิดร้าน — ของมาส่งเช้าก่อนเปิดร้านก็รับเข้าได้
   มีแค่หน้าขายสินค้าที่ต้องเปิดร้านก่อน (เพราะเกี่ยวกับลิ้นชักเงินสด) */

$isHx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
$q    = isset($_GET['q'])   ? trim($_GET['q'])   : '';
$cat  = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$st   = isset($_GET['st'])  ? trim($_GET['st'])  : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'urgent';

$__tabs  = stock_status_tabs();
$__sorts = stock_sorts();
if (!isset($__tabs[$st]))    { $st   = ''; }
if (!isset($__sorts[$sort])) { $sort = 'urgent'; }

/* คำขอจาก htmx: ส่งกลับเฉพาะส่วนที่เปลี่ยน */
if ($isHx) {
    require dirname(__FILE__) . '/inc/products-live.php';
    exit;
}

$branch     = $code;
$PAGE_TITLE = 'รายการสินค้า';
$PAGE_SUB   = branch_name($code) . ' · ยอดคงเหลือ ณ ตอนนี้';
$NAV_ACTIVE = 'products.php';
require dirname(__FILE__) . '/inc/header.php';
?>


<?php require dirname(__FILE__) . '/inc/products-live.php'; ?>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
