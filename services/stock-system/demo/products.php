<?php
/* ==========================================================
   AOSTOCK DEMO — รายการสินค้าในสต๊อกของสาขาตัวเอง
   ----------------------------------------------------------
   พนักงานเห็นเฉพาะสาขาที่ผูกไว้กับรหัสของตัวเอง
   ยอดคงเหลือรวมของที่ขายออก / คืนกลับ ระหว่างวันแล้ว (อยู่ใน session)
   ========================================================== */

require_once dirname(__FILE__) . '/inc/auth.php';
require_once dirname(__FILE__) . '/inc/store.php';
require_once dirname(__FILE__) . '/inc/stock.php';

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
