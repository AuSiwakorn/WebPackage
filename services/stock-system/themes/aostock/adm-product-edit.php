<?php
/**
 * FILE: themes/aostock/adm-product-edit.php
 * ROLE: [ผู้ดูแล] เพิ่ม / แก้ไขสินค้า (SKU, ชื่อ, หมวด, หน่วย, ทุน, ราคาขาย, จุดสั่งซื้อ, บาร์โค้ด, รูป, เปิด / เลิกขาย)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_product, ao_stock_category (ผ่าน function ใน api.php)
 * TODO:
 *   - [x] เพิ่มสินค้าใหม่ · แก้ไข (SKU เปลี่ยนไม่ได้) · รูปสินค้า (UpFile → uploads/stock/products/) · เปิด / เลิกขาย
 *   - [ ] ยอดยกมาของสินค้าใหม่ = ทำใบรับเข้า (หน้า "นำเข้าสินค้า" ของพนักงาน)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ?sku=รหัส = แก้สินค้านั้น · ไม่มี = เพิ่มสินค้าใหม่
   - SKU ตั้งตอนเพิ่มแล้วเปลี่ยนไม่ได้ (เอกสารเก่าอ้างถึง) · เลิกขาย = ซ่อนจากหน้าขาย / นำเข้า / ตรวจนับ แต่ประวัติอยู่ครบ
   - ยอดคงเหลือไม่ได้ตั้งที่นี่ — สินค้าใหม่เริ่มที่ 0 ทุกสาขา แล้วพนักงานทำใบรับเข้า
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

$sku   = (isset($_GET['sku']) && is_string($_GET['sku'])) ? strtoupper(trim($_GET['sku'])) : '';
$rows  = product_db_rows();
$isNew = ($sku === '' || !isset($rows[$sku]));
$p     = $isNew ? null : product_by_sku($sku);
$err   = '';
$old   = array();
$okMsg = array('add' => 'เพิ่มสินค้าแล้ว — สินค้าใหม่มียอด 0 ทุกสาขา ให้พนักงานทำใบรับเข้า', 'save' => 'บันทึกแล้ว มีผลทันที',
               'off' => 'เลิกขายแล้ว — ไม่ขึ้นในหน้าขาย / นำเข้า / ตรวจนับ', 'on' => 'เปิดขายอีกครั้งแล้ว', 'img' => 'ลบรูปแล้ว');
$ok    = (isset($_GET['ok']) && is_string($_GET['ok']) && isset($okMsg[$_GET['ok']])) ? $_GET['ok'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? $_POST['act'] : '';
    $go  = '';
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';

    } elseif ($act === 'save') {
        list($d, $err) = product_read_form($isNew, $sku);
        $old = $d;
        if ($err === '') {
            product_save($isNew, $d);
            $img = product_image_store($d['sku'], 'image', $isNew ? '' : $p['image']);
            if (isset($img['error'])) {
                $_SESSION['flash'] = 'บันทึกข้อมูลสินค้าแล้ว แต่' . $img['error'];
            }
            $go = 'adm-product-edit.php?sku=' . rawurlencode($d['sku']) . '&ok=' . ($isNew ? 'add' : 'save');
        }

    } elseif ($isNew) {
        $err = 'ไม่พบสินค้านี้';

    } elseif ($act === 'off' || $act === 'on') {
        product_set_active($sku, $act === 'on');
        $go = 'adm-product-edit.php?sku=' . rawurlencode($sku) . '&ok=' . $act;

    } elseif ($act === 'noimg') {
        product_image_remove($sku, $p['image']);
        $go = 'adm-product-edit.php?sku=' . rawurlencode($sku) . '&ok=img';
    }

    if ($go !== '' && $err === '') {
        header('Location: ' . url($go));
        exit;
    }
}

$cats  = cat_registry();
$units = product_units();
$v = function ($k, $def) use ($old) {
    return isset($old[$k]) ? $old[$k] : $def;
};
$num = function ($n) {
    return rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
};
$img = $p ? product_img($p) : '';

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = $isNew ? 'เพิ่มสินค้า' : 'แก้ไขสินค้า';
$PAGE_SUB       = $isNew ? 'สินค้าใหม่เริ่มที่ยอด 0 ทุกสาขา — รับของเข้าที่หน้า "นำเข้าสินค้า"' : $p['sku'] . ' · ' . $p['name'];
$NAV_ACTIVE     = 'adm-products.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<p class="hist-back"><a class="btn btn-ghost btn-sm" href="adm-products.php">‹ กลับไปสินค้าในสต๊อก</a></p>

<?php if ($err !== ''): ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
<?php elseif ($ok !== ''): ?>
  <div class="alert alert-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span><?= e($okMsg[$ok]) ?></span></div>
<?php endif; ?>

<?php if (!$cats): ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span>ยังไม่มีหมวดสินค้า — เพิ่มหมวดก่อนที่หน้า <a href="adm-categories.php">หมวดสินค้า</a> (หรือให้พนักงานที่มีสิทธิ์เพิ่ม)</span></div>
<?php endif; ?>

<section class="card">
  <div class="card-head">
    <div>
      <h2><?= $isNew ? 'ข้อมูลสินค้าใหม่' : 'ข้อมูลสินค้า' ?></h2>
      <span class="sub"><?= $isNew ? 'SKU ตั้งครั้งเดียว เปลี่ยนภายหลังไม่ได้' : ($p['active'] ? 'ขายอยู่' : 'เลิกขายแล้ว') . ' · ยอดคงเหลือแก้ที่นี่ไม่ได้ (ใช้ใบรับเข้า / เบิก / ตรวจนับ)' ?></span>
    </div>
  </div>
  <form class="adm-sec" method="post" action="adm-product-edit.php<?= $isNew ? '' : '?sku=' . e(rawurlencode($sku)) ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="act" value="save">
    <div class="adm-fields">
      <div class="field">
        <label for="p-sku">SKU</label>
        <?php if ($isNew): ?>
          <input class="input" type="text" id="p-sku" name="sku" value="<?= e($v('sku', '')) ?>" maxlength="40" required autocomplete="off" autocapitalize="characters" placeholder="เช่น CS-010">
          <small class="adm-hint">A–Z 0–9 . _ - · 1 สี / รุ่น = 1 SKU</small>
        <?php else: ?>
          <input class="input" type="text" id="p-sku" value="<?= e($sku) ?>" disabled>
        <?php endif; ?>
      </div>
      <div class="field">
        <label for="p-name">ชื่อสินค้า</label>
        <input class="input" type="text" id="p-name" name="name" value="<?= e($v('name', $p ? $p['name'] : '')) ?>" maxlength="200" required autocomplete="off">
      </div>
      <div class="field">
        <label for="p-cat">หมวด</label>
        <select class="input" id="p-cat" name="cat" required>
          <option value="">— เลือกหมวด —</option>
          <?php $cv = $v('cat', $p ? $p['cat'] : ''); foreach ($cats as $c => $x): ?>
            <option value="<?= e($c) ?>" <?= $cv === $c ? 'selected' : '' ?>><?= e($c) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="p-unit">หน่วย</label>
        <input class="input" type="text" id="p-unit" name="unit" value="<?= e($v('unit', $p ? $p['unit'] : 'ชิ้น')) ?>" maxlength="20" list="p-units" autocomplete="off">
        <datalist id="p-units"><?php foreach ($units as $u): ?><option value="<?= e($u) ?>"><?php endforeach; ?></datalist>
      </div>
      <div class="field">
        <label for="p-cost">ทุนต่อหน่วย (บาท)</label>
        <input class="input" type="text" id="p-cost" name="cost" value="<?= e($v('cost', $p ? $num($p['cost']) : '')) ?>" inputmode="decimal" autocomplete="off" placeholder="0.00">
      </div>
      <div class="field">
        <label for="p-price">ราคาขาย (บาท · รวม VAT)</label>
        <input class="input" type="text" id="p-price" name="price" value="<?= e($v('price', $p ? $num($p['price']) : '')) ?>" inputmode="decimal" autocomplete="off" placeholder="0.00">
      </div>
      <div class="field">
        <label for="p-reorder">จุดสั่งซื้อ</label>
        <input class="input" type="text" id="p-reorder" name="reorder" value="<?= e($v('reorder', $p ? $p['reorder'] : '')) ?>" inputmode="numeric" autocomplete="off" placeholder="0">
        <small class="adm-hint">เหลือเท่านี้หรือน้อยกว่า = ใกล้หมด (ใช้ทุกสาขา)</small>
      </div>
      <div class="field">
        <label for="p-barcode">บาร์โค้ด <small class="adm-none">(ไม่บังคับ)</small></label>
        <input class="input" type="text" id="p-barcode" name="barcode" value="<?= e($v('barcode', $p ? $p['barcode'] : '')) ?>" maxlength="40" autocomplete="off">
      </div>
      <div class="field">
        <label for="p-image">รูปสินค้า <small class="adm-none">(ไม่บังคับ · JPG / PNG / WEBP ไม่เกิน 2 MB)</small></label>
        <input class="input" type="file" id="p-image" name="image" accept="image/jpeg,image/png,image/webp">
        <?php if ($img !== ''): ?><small class="adm-hint">มีรูปอยู่แล้ว — เลือกไฟล์ใหม่เพื่อแทนที่</small><?php endif; ?>
      </div>
    </div>
    <button class="btn btn-primary" type="submit"<?= $cats ? '' : ' disabled' ?>><svg class="ico"><use href="#i-<?= $isNew ? 'plus' : 'check' ?>"/></svg> <?= $isNew ? 'เพิ่มสินค้า' : 'บันทึก' ?></button>
  </form>
</section>

<?php if (!$isNew): ?>
<section class="card">
  <div class="card-head"><div><h2>รูปและสถานะ</h2><span class="sub">ยอดคงเหลือ: <?php
      $parts = array();
      foreach (branches_active() as $bc => $b) {
          $parts[] = $b['short'] . ' ' . number_format(product_qty($p, $bc));
      }
      echo e($parts ? implode(' · ', $parts) : '—');
  ?></span></div></div>
  <div class="adm-sec">
    <?php if ($img !== ''): ?>
      <p><img class="prod-edit-img" src="<?= e($img) ?>" alt="<?= e($p['name']) ?>" loading="lazy"></p>
      <?php if ($p['image'] !== ''): ?>
        <form method="post" action="adm-product-edit.php?sku=<?= e(rawurlencode($sku)) ?>">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="act" value="noimg">
          <button class="btn btn-ghost btn-sm" type="submit">ลบรูป</button>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <p class="adm-hint">ยังไม่มีรูป — หน้าขายจะแสดงไอคอนตามหมวดแทน</p>
    <?php endif; ?>
    <form method="post" action="adm-product-edit.php?sku=<?= e(rawurlencode($sku)) ?>">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="act" value="<?= $p['active'] ? 'off' : 'on' ?>">
      <button class="btn <?= $p['active'] ? 'btn-ghost' : 'btn-primary' ?> btn-sm" type="submit"><?= $p['active'] ? 'เลิกขายสินค้านี้' : 'เปิดขายอีกครั้ง' ?></button>
      <small class="adm-hint"><?= $p['active'] ? 'ซ่อนจากหน้าขาย / นำเข้า / ตรวจนับ · ประวัติและรายงานยังอยู่ครบ' : 'กลับมาขาย / นำเข้า / ตรวจนับได้ตามปกติ' ?></small>
    </form>
  </div>
</section>
<?php endif; ?>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
