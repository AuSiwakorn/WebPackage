<?php
/**
 * FILE: themes/aostock/product-add.php
 * ROLE: เพิ่มสินค้าของสาขา (พนักงานที่มีสิทธิ์ “เพิ่มสินค้าใหม่”) · แก้ไขสินค้าของสาขา (ผู้จัดการสาขา · ?sku=)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/product-form.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_product, ao_stock_category, ao_stock_doc_seq (SKU อัตโนมัติ), ao_stock_log (ผ่าน Stock_doAddProduct / Stock_doSaveProduct ใน api.php)
 * TODO:
 *   - [x] 15 ต.ค. (ผู้ใช้ขอ): เมนูแยก “เพิ่มสินค้า” — เดิมเพิ่มได้แค่จากหน้าต่างในหน้านำเข้า หาไม่เจอ
 *         ฟอร์ม / การตรวจเหมือนหน้าต่างในหน้านำเข้า (Stock_doAddProduct) · ผู้ดูแลถูกพาไป adm-product-edit.php
 *   - [x] 22 ต.ค. (ผู้ใช้ขอ): สินค้าที่เพิ่มจากสาขาเป็นของสาขานั้น (ไม่ทับส่วนกลาง) · ผู้จัดการแก้สินค้าของสาขาตัวเองได้ทุกช่อง (?sku=)
 *         · ฟอร์มเดียวกับผู้ดูแล (inc/product-form.php) · ทุกการแก้ลงประวัติ
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ไม่มี ?sku= = เพิ่มสินค้าใหม่ (สิทธิ์ product_add) → เป็นสินค้าของสาขานี้ ขาย / นำเข้าได้เฉพาะสาขานี้
   ?sku=รหัส = แก้สินค้าของสาขาตัวเอง (ผู้จัดการสาขา · Stock_canEditProduct) — สินค้าส่วนกลางแก้ได้เฉพาะผู้ดูแล
   บันทึกแล้วกลับมาหน้าเดิม (?ok=) · ยอดคงเหลือแก้ที่นี่ไม่ได้ (ใบรับเข้า / เบิก / ตรวจนับ)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // ไม่มีสิทธิ์ product_add / ไม่ใช่ผู้จัดการ → กลับหน้าแรก · ผู้ดูแล → adm-product-edit.php
$code = work_branch($user);
$sku  = (isset($_GET['sku']) && is_string($_GET['sku'])) ? strtoupper(trim($_GET['sku'])) : '';
$p    = ($sku !== '') ? product_by_sku($sku) : null;
$isEdit  = ($sku !== '');
$canEdit = $isEdit && Stock_canEditProduct($user, $p);
$canAdd  = can($user, 'product_add');
$err  = '';
$bad  = '';                              // รูปไม่ผ่าน — ข้อความแดงใต้ช่องรูป
$old  = array();                         // ค่าที่กรอกไว้ตอนบันทึกไม่ผ่าน
$done = null;
$self = 'product-add.php' . ($isEdit ? '?sku=' . rawurlencode($sku) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? (string) $_POST['act'] : 'save';
    $go  = '';
    if (!$_POST && !empty($_SERVER['CONTENT_LENGTH'])) {          // ไฟล์ใหญ่เกิน post_max_size → PHP ทิ้งทั้งฟอร์ม
        $err = 'ไฟล์ที่ส่งมาใหญ่เกินที่เซิร์ฟเวอร์รับได้ — ยังไม่ได้บันทึก ลองเลือกรูปที่เล็กลงแล้วกดอีกครั้ง';
    } elseif (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } elseif ($isEdit && !$canEdit) {
        $err = 'แก้ได้เฉพาะสินค้าของสาขาตัวเอง (ผู้จัดการสาขา) — สินค้าส่วนกลางให้ผู้ดูแลแก้';
    } elseif ($isEdit && $act === 'save') {
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
    } elseif ($isEdit && ($act === 'off' || $act === 'on')) {
        $err = Stock_doSetProductActive($user, $sku, $act === 'on', $code);
        $go  = $self . '&ok=' . $act;
    } elseif ($isEdit && $act === 'noimg') {
        $err = Stock_doRemoveProductImage($user, $sku, $code);
        $go  = $self . '&ok=img';
    } elseif (!$isEdit && !$canAdd) {
        $err = 'ไม่มีสิทธิ์ “เพิ่มสินค้าใหม่” — ติดต่อผู้ดูแลเพื่อเปิดสิทธิ์';
    } elseif (!$isEdit && ($bad = Stock_checkProductImage('image')) !== '') {  // รูปไม่ผ่าน = ยังไม่เพิ่มสินค้า
        $err = 'ยังไม่ได้เพิ่มสินค้า — ' . $bad . ' · เลือกรูปใหม่ (หรือไม่ใส่รูป) แล้วกดเพิ่มอีกครั้ง';
        $old = $_POST;
    } elseif (!$isEdit) {
        $np = Stock_doAddProduct($user, $code, 'หน้าเพิ่มสินค้า');
        if (isset($np['error'])) {
            $err = $np['error'];
            $old = $_POST;
        } else {
            $img = product_image_store($np['sku'], 'image', '');
            if (isset($img['error'])) {
                $_SESSION['img_err'] = $img['error'];                 // ตรวจผ่านแต่เก็บไม่ได้ (เช่นเขียนโฟลเดอร์ไม่ได้)
            }
            $go = 'product-add.php?ok=' . rawurlencode($np['sku']);
        }
    }
    if ($go !== '' && $err === '') {
        header('Location: ' . url($go));
        exit;
    }
}

/* ผลหลังบันทึก — เพิ่มใหม่: ?ok=SKU · แก้ไข: ?ok=save / saveimg / off / on / img */
$okMsg  = array('save' => 'บันทึกแล้ว มีผลทันที', 'saveimg' => 'บันทึกข้อมูลสินค้าแล้ว แต่รูปบันทึกไม่ผ่าน',
                'off' => 'เลิกขายแล้ว — ไม่ขึ้นในหน้าขาย / นำเข้า / ตรวจนับ', 'on' => 'เปิดขายอีกครั้งแล้ว', 'img' => 'ลบรูปแล้ว');
$ok      = (isset($_GET['ok']) && is_string($_GET['ok'])) ? $_GET['ok'] : '';
$imgFail = isset($_SESSION['img_err']) ? (string) $_SESSION['img_err'] : '';
unset($_SESSION['img_err']);
if (!$isEdit && $ok !== '') {
    $done = product_by_sku(trim($ok));
}

$v = function ($k, $def) use ($old) {                                     // ค่าที่กรอกไว้ (ตอนบันทึกไม่ผ่าน) หรือค่าเดิม
    return (isset($old[$k]) && is_string($old[$k])) ? $old[$k] : $def;
};

/* ยังไม่ได้ Reinstall (ไม่มีคอลัมน์เจ้าของ) = เพิ่มแล้วเป็นส่วนกลางแบบเดิม — บอกตามจริง */
$addScope = Stock_hasProductOwnerColumn() ? 'สินค้าของ' . branch_name($code) . ' (ขาย / นำเข้าได้เฉพาะสาขานี้)' : 'สินค้าส่วนกลาง (ใช้ได้ทุกสาขา)';

$branch     = $code;
$PAGE_TITLE = $isEdit ? 'แก้ไขสินค้า' : 'เพิ่มสินค้า';
$PAGE_SUB   = $isEdit ? ($p ? $p['sku'] . ' · ' . $p['name'] : 'ไม่พบสินค้า')
                      : $addScope . ' · ยอดเริ่มที่ 0 — รับของเข้าที่หน้า “นำเข้าสินค้า”';
$NAV_ACTIVE = $isEdit ? 'products.php' : 'product-add.php';                // แก้ไข = เมนู “สินค้าในสต๊อก”
$CONFIRM_MODAL = true;                   // ลบรูป (data-confirm="ask")
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($isEdit) { ?>
  <p class="hist-back"><a class="btn btn-ghost btn-sm" href="products.php">‹ กลับไปสินค้าในสต๊อก</a></p>
<?php } ?>

<?php if ($err !== '') { ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span></div>
<?php } elseif ($isEdit && isset($okMsg[$ok])) { ?>
  <div class="alert <?php echo $ok === 'saveimg' ? 'alert-error' : 'alert-ok' ?>" role="status">
    <svg class="ico"><use href="#i-<?php echo $ok === 'saveimg' ? 'alert' : 'check' ?>"/></svg>
    <span><?php echo e($okMsg[$ok] . ($ok === 'saveimg' && $imgFail !== '' ? ' — ' . $imgFail . ' · เลือกรูปใหม่แล้วกดบันทึกอีกครั้ง' : '')) ?></span>
  </div>
<?php } elseif ($done !== null) { ?>
  <div class="alert alert-ok" role="status">
    <svg class="ico"><use href="#i-check"/></svg>
    <?php if (product_img($done) !== '') { echo thumb_html($done, 'pa-thumb'); } ?>
    <span>เพิ่มสินค้า <b><?php echo e($done['name']) ?></b> (SKU <?php echo e($done['sku']) ?>) แล้ว · <?php echo e(Stock_formatProductOwner($done)) ?> · ยอดเริ่มที่ 0</span>
    <div class="alert-act">
      <?php if (page_ok($user, 'receive.php')) { ?>
        <a class="btn btn-primary btn-sm" href="receive.php?q=<?php echo e(rawurlencode($done['sku'])) ?>"><svg class="ico"><use href="#i-in"/></svg> นำเข้าสินค้านี้</a>
      <?php } ?>
      <?php if (Stock_canEditProduct($user, $done)) { ?>
        <a class="btn btn-ghost btn-sm" href="product-add.php?sku=<?php echo e(rawurlencode($done['sku'])) ?>"><svg class="ico"><use href="#i-edit"/></svg> แก้ไขสินค้านี้</a>
      <?php } ?>
      <?php if (page_ok($user, 'products.php')) { ?>
        <a class="btn btn-ghost btn-sm" href="products.php?q=<?php echo e(rawurlencode($done['sku'])) ?>"><svg class="ico"><use href="#i-boxes"/></svg> ดูในสินค้าในสต๊อก</a>
      <?php } ?>
    </div>
  </div>
  <?php if ($imgFail !== '') { ?>
    <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span>แต่รูปบันทึกไม่ผ่าน (<?php echo e($imgFail) ?>) — สินค้าเพิ่มแล้วโดยยังไม่มีรูป<?php echo Stock_canEditProduct($user, $done) ? ' เพิ่มรูปได้ที่ “แก้ไขสินค้านี้”' : ' ให้ผู้จัดการสาขาเพิ่มรูปที่หน้าแก้ไขสินค้า' ?></span></div>
  <?php } ?>
<?php } ?>

<?php
if ($isEdit && !$canEdit) { ?>
  <p class="empty"><svg class="ico"><use href="#i-boxes"/></svg>
    <?php echo $p === null ? 'ไม่พบสินค้านี้' : 'แก้ได้เฉพาะสินค้าของสาขาตัวเอง (ผู้จัดการสาขา) — “' . e($p['name']) . '” เป็น' . e(Stock_formatProductOwner($p))
                                              . ($p['owner'] === '' ? ' ให้ผู้ดูแลแก้' : ' แก้ได้เฉพาะผู้จัดการสาขานั้นหรือผู้ดูแล') ?>
  </p>
<?php } elseif (!$isEdit && !$canAdd) { ?>
  <p class="empty"><svg class="ico"><use href="#i-plus"/></svg>ยังไม่มีสิทธิ์ “เพิ่มสินค้าใหม่” — ติดต่อผู้ดูแลเพื่อเปิดสิทธิ์ · แก้สินค้าของสาขาได้จากปุ่ม “แก้ไข” ในหน้าสินค้าในสต๊อก</p>
<?php } else {
    $PF = array(
        'action'  => $self,
        'p'       => $isEdit ? $p : null,
        'v'       => $v,
        'canCat'  => !$isEdit && can($user, 'category_add'),
        'scope'   => $isEdit ? Stock_formatProductOwner($p) . ' (ขาย / นำเข้าได้เฉพาะสาขานี้)' : $addScope,
        'bad'     => $bad,
        'canMove' => false,
    );
    require dirname(__FILE__) . '/inc/product-form.php';
} ?>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
