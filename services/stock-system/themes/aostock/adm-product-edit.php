<?php
/**
 * FILE: themes/aostock/adm-product-edit.php
 * ROLE: [ผู้ดูแล] เพิ่มสินค้าส่วนกลาง / แก้ไขสินค้าทุกตัว (ส่วนกลาง + ของสาขา) · ย้ายสินค้าของสาขาเป็นส่วนกลาง
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/product-form.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_product, ao_stock_category, ao_stock_log (ผ่าน function ใน api.php)
 * TODO:
 *   - [x] เพิ่มสินค้าใหม่ · แก้ไข (SKU เปลี่ยนไม่ได้) · รูปสินค้า (UpFile → uploads/stock/products/) · เปิด / เลิกขาย
 *   - [ ] ยอดยกมาของสินค้าใหม่ = ทำใบรับเข้า (หน้า "นำเข้าสินค้า" ของพนักงาน)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 *   - [x] 15 ต.ค. (ผู้ใช้ขอ): มีเมนูแยก “เพิ่มสินค้า” ชี้มาหน้านี้ — เพิ่มใหม่ไฮไลต์เมนูนั้น · แก้ไขไฮไลต์ “สินค้าในสต๊อก”
 *   - [x] 22 ต.ค. (ผู้ใช้ขอ): ฟอร์มเดียวกับหน้าสาขา (inc/product-form.php) — SKU อัตโนมัติ / กรอกเอง · พิมพ์หมวดใหม่
 *         · ทุกการแก้ลงประวัติ “เดิม → ใหม่” (Stock_doSaveProduct · เดิมไม่ลง) · สินค้าของสาขามีป้ายเจ้าของ + ปุ่มย้ายเป็นส่วนกลาง
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ?sku=รหัส = แก้สินค้านั้น · ไม่มี = เพิ่มสินค้าส่วนกลางใหม่ (ทุกสาขาขาย / นำเข้าได้)
   - SKU ตั้งตอนเพิ่มแล้วเปลี่ยนไม่ได้ (เอกสารเก่าอ้างถึง) · เลิกขาย = ซ่อนจากหน้าขาย / นำเข้า / ตรวจนับ แต่ประวัติอยู่ครบ
   - ยอดคงเหลือไม่ได้ตั้งที่นี่ — สินค้าใหม่เริ่มที่ 0 ทุกสาขา แล้วพนักงานทำใบรับเข้า
   - สินค้าของสาขา (ผู้จัดการ / พนักงานเพิ่ม · 22 ต.ค.) แก้ได้ที่นี่เหมือนกัน + ย้ายเป็นสินค้าส่วนกลาง (act=move)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user  = require_login();                // หน้า adm- : เฉพาะผู้ดูแล
$code  = work_branch($user);             // สาขาที่ลงประวัติของสินค้าส่วนกลาง
$sku   = (isset($_GET['sku']) && is_string($_GET['sku'])) ? strtoupper(trim($_GET['sku'])) : '';
$p     = ($sku !== '') ? product_by_sku($sku) : null;
$isNew = ($p === null);
$err   = '';
$bad   = '';
$old   = array();
$self  = 'adm-product-edit.php' . ($isNew ? '' : '?sku=' . rawurlencode($sku));
$okMsg = array('add' => 'เพิ่มสินค้าแล้ว — สินค้าใหม่มียอด 0 ทุกสาขา ให้พนักงานทำใบรับเข้า', 'save' => 'บันทึกแล้ว มีผลทันที',
               'off' => 'เลิกขายแล้ว — ไม่ขึ้นในหน้าขาย / นำเข้า / ตรวจนับ', 'on' => 'เปิดขายอีกครั้งแล้ว', 'img' => 'ลบรูปแล้ว',
               'move' => 'ย้ายเป็นสินค้าส่วนกลางแล้ว — ทุกสาขาขาย / นำเข้าได้',
               'addimg' => 'เพิ่มสินค้าแล้ว แต่รูปบันทึกไม่ผ่าน', 'saveimg' => 'บันทึกข้อมูลสินค้าแล้ว แต่รูปบันทึกไม่ผ่าน');
$ok     = (isset($_GET['ok']) && is_string($_GET['ok']) && isset($okMsg[$_GET['ok']])) ? $_GET['ok'] : '';
$imgErr = ($ok === 'addimg' || $ok === 'saveimg');     // รูปไม่ผ่าน: แจ้งเป็นสีแดงแทน "บันทึกแล้ว"
$imgWhy = ($imgErr && isset($_SESSION['img_err'])) ? (string) $_SESSION['img_err'] : '';
unset($_SESSION['img_err']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? (string) $_POST['act'] : '';
    $go  = '';
    if (!$_POST && !empty($_SERVER['CONTENT_LENGTH'])) {          // ไฟล์ใหญ่เกิน post_max_size → PHP ทิ้งทั้งฟอร์ม
        $err = 'ไฟล์ที่ส่งมาใหญ่เกินที่เซิร์ฟเวอร์รับได้ — ยังไม่ได้บันทึกอะไร ลองเลือกรูปที่เล็กลงแล้วกดบันทึกอีกครั้ง';

    } elseif (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';

    } elseif ($act === 'save' && $isNew) {
        /* เพิ่มสินค้าส่วนกลาง — ตรวจ / SKU อัตโนมัติ / หมวดใหม่ แบบเดียวกับหน้าสาขา (Stock_doAddProduct) */
        if (($bad = Stock_checkProductImage('image')) !== '') {
            $err = 'ยังไม่ได้เพิ่มสินค้า — ' . $bad . ' · เลือกรูปใหม่ (หรือไม่ใส่รูป) แล้วกดเพิ่มอีกครั้ง';
            $old = $_POST;
        } else {
            $np = Stock_doAddProduct($user, $code, 'หน้าผู้ดูแล');
            if (isset($np['error'])) {
                $err = $np['error'];
                $old = $_POST;
            } else {
                $img = product_image_store($np['sku'], 'image', '');
                if (isset($img['error'])) {
                    $_SESSION['img_err'] = $img['error'];
                }
                $go = 'adm-product-edit.php?sku=' . rawurlencode($np['sku']) . '&ok=add' . (isset($img['error']) ? 'img' : '');
            }
        }

    } elseif ($isNew) {
        $err = 'ไม่พบสินค้านี้';

    } elseif ($act === 'save') {
        $r = Stock_doSaveProduct($user, $sku, $code);
        if (isset($r['error'])) {
            $err = $r['error'];
            $bad = isset($r['bad']) ? $r['bad'] : '';
            $old = $_POST;
        } else {
            if ($r['img_error'] !== '') {
                $_SESSION['img_err'] = $r['img_error'];
            }
            $go = $self . '&ok=' . ($r['img_error'] !== '' ? 'saveimg' : 'save');
        }

    } elseif ($act === 'off' || $act === 'on') {
        $err = Stock_doSetProductActive($user, $sku, $act === 'on', $code);
        $go  = $self . '&ok=' . $act;

    } elseif ($act === 'noimg') {
        $err = Stock_doRemoveProductImage($user, $sku, $code);
        $go  = $self . '&ok=img';

    } elseif ($act === 'move') {
        $err = Stock_doMoveProductToCentral($user, $sku, $code);
        $go  = $self . '&ok=move';
    }

    if ($go !== '' && $err === '') {
        header('Location: ' . url($go));
        exit;
    }
}

$v = function ($k, $def) use ($old) {
    return (isset($old[$k]) && is_string($old[$k])) ? $old[$k] : $def;
};

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$CONFIRM_MODAL  = true;                  // ลบรูป / ย้ายเป็นส่วนกลาง (data-confirm="ask")
$PAGE_TITLE     = $isNew ? 'เพิ่มสินค้า' : 'แก้ไขสินค้า';
$PAGE_SUB       = $isNew ? 'สินค้าส่วนกลาง เริ่มที่ยอด 0 ทุกสาขา — รับของเข้าที่หน้า "นำเข้าสินค้า"' : $p['sku'] . ' · ' . $p['name'] . ' · ' . Stock_formatProductOwner($p);
$NAV_ACTIVE     = $isNew ? 'adm-product-edit.php' : 'adm-products.php';   // เพิ่มใหม่ = เมนู “เพิ่มสินค้า”
require dirname(__FILE__) . '/inc/header.php';
?>

<p class="hist-back"><a class="btn btn-ghost btn-sm" href="adm-products.php">‹ กลับไปสินค้าในสต๊อก</a></p>

<?php if ($err !== '') { ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span></div>
<?php } elseif ($imgErr) { ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($okMsg[$ok] . ($imgWhy !== '' ? ' — ' . $imgWhy : '')) ?> · เลือกรูปใหม่ที่ช่อง “รูปสินค้า” แล้วกดบันทึกอีกครั้ง</span></div>
<?php } elseif ($ok !== '') { ?>
  <div class="alert alert-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span><?php echo e($okMsg[$ok]) ?></span></div>
<?php } ?>

<?php
$PF = array(
    'action'  => $self,
    'p'       => $p,
    'v'       => $v,
    'canCat'  => $isNew,                 // ผู้ดูแลเพิ่มหมวดได้เสมอ
    'scope'   => $isNew ? 'สินค้าส่วนกลาง (ทุกสาขาขาย / นำเข้าได้)'
                        : Stock_formatProductOwner($p) . ($p['owner'] !== '' ? ' (ขาย / นำเข้าได้เฉพาะสาขาเจ้าของ · ผู้จัดการสาขานั้นแก้ได้)' : ' (ทุกสาขา)'),
    'bad'     => $bad,
    'canMove' => !$isNew && $p['owner'] !== '',
);
require dirname(__FILE__) . '/inc/product-form.php';
?>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
