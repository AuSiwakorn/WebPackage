<?php
/**
 * FILE: themes/aostock/product-add.php
 * ROLE: เพิ่มสินค้าใหม่ (พนักงานที่มีสิทธิ์ “เพิ่มสินค้าใหม่”) — เพิ่มเข้าระบบอย่างเดียว แล้วค่อยนำเข้า
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_product, ao_stock_category, ao_stock_doc_seq (SKU อัตโนมัติ), ao_stock_log (ผ่าน Stock_doAddProduct ใน api.php)
 * TODO:
 *   - [x] 15 ต.ค. (ผู้ใช้ขอ): เมนูแยก “เพิ่มสินค้า” — เดิมเพิ่มได้แค่จากหน้าต่างในหน้านำเข้า หาไม่เจอ
 *         ฟอร์ม / การตรวจเหมือนหน้าต่างในหน้านำเข้า (Stock_doAddProduct) · ผู้ดูแลถูกพาไป adm-product-edit.php
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   เพิ่มสินค้าใหม่ (พนักงาน)
   - สินค้าใช้ร่วมกันทุกสาขา · ยอดเริ่มที่ 0 → รับของเข้าที่หน้า “นำเข้าสินค้า”
   - SKU ตั้งให้อัตโนมัติ (P0001 …) หรือกรอกเอง · หมวดใหม่พิมพ์ได้ถ้ามีสิทธิ์เพิ่มหมวด
   - บันทึกแล้วกลับมาหน้านี้ (?ok=SKU) พร้อมปุ่มนำเข้าสินค้านี้ / เพิ่มอีกตัว
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // ไม่มีสิทธิ์ product_add → กลับหน้าแรก · ผู้ดูแล → adm-product-edit.php
$code = work_branch($user);
$err  = '';
$bad  = '';                              // รูปไม่ผ่าน (ยังไม่เพิ่มสินค้า) — ข้อความแดงใต้ช่องรูป
$done = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_POST && !empty($_SERVER['CONTENT_LENGTH'])) {          // ไฟล์ใหญ่เกิน post_max_size → PHP ทิ้งทั้งฟอร์ม
        $err = 'ไฟล์ที่ส่งมาใหญ่เกินที่เซิร์ฟเวอร์รับได้ — ยังไม่ได้เพิ่มสินค้า ลองเลือกรูปที่เล็กลงแล้วกดเพิ่มอีกครั้ง';
    } elseif (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } elseif (($bad = Stock_checkProductImage('image')) !== '') {  // รูปไม่ผ่าน = ยังไม่เพิ่มสินค้า
        $err = 'ยังไม่ได้เพิ่มสินค้า — ' . $bad . ' · เลือกรูปใหม่ (หรือไม่ใส่รูป) แล้วกดเพิ่มอีกครั้ง';
    } else {
        $np = Stock_doAddProduct($user, $code, 'หน้าเพิ่มสินค้า');
        if (isset($np['error'])) {
            $err = $np['error'];
        } else {
            $img = product_image_store($np['sku'], 'image', '');
            if (isset($img['error'])) {
                $_SESSION['img_err'] = $img['error'];                 // ตรวจผ่านแต่เก็บไม่ได้ (เช่นเขียนโฟลเดอร์ไม่ได้)
            }
            header('Location: ' . url('product-add.php?ok=' . rawurlencode($np['sku'])));
            exit;
        }
    }
}
$imgFail = '';
if (isset($_GET['ok']) && is_string($_GET['ok'])) {
    $done    = product_by_sku(trim($_GET['ok']));
    $imgFail = isset($_SESSION['img_err']) ? (string) $_SESSION['img_err'] : '';
}
unset($_SESSION['img_err']);

$cats   = product_cats();
$canCat = can($user, 'category_add');
$v = function ($k, $def = '') use ($err) {                               // ค่าที่กรอกไว้ (ตอนบันทึกไม่ผ่าน)
    return ($err !== '' && isset($_POST[$k]) && is_string($_POST[$k])) ? $_POST[$k] : $def;
};
$manual = ($v('sku_mode') === 'manual');

$branch     = $code;
$PAGE_TITLE = 'เพิ่มสินค้า';
$PAGE_SUB   = 'เพิ่มเข้าระบบ (ใช้ได้ทุกสาขา) · ยอดเริ่มที่ 0 — รับของเข้าที่หน้า “นำเข้าสินค้า”';
$NAV_ACTIVE = 'product-add.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($err !== '') { ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span></div>
<?php } elseif ($done !== null) { ?>
  <div class="alert alert-ok" role="status">
    <svg class="ico"><use href="#i-check"/></svg>
    <?php if (product_img($done) !== '') { echo thumb_html($done, 'pa-thumb'); } ?>
    <span>เพิ่มสินค้า <b><?php echo e($done['name']) ?></b> (SKU <?php echo e($done['sku']) ?>) แล้ว · ยอดเริ่มที่ 0 ทุกสาขา</span>
    <div class="alert-act">
      <?php if (page_ok($user, 'receive.php')) { ?>
        <a class="btn btn-primary btn-sm" href="receive.php?q=<?php echo e(rawurlencode($done['sku'])) ?>"><svg class="ico"><use href="#i-in"/></svg> นำเข้าสินค้านี้</a>
      <?php } ?>
      <?php if (page_ok($user, 'products.php')) { ?>
        <a class="btn btn-ghost btn-sm" href="products.php?q=<?php echo e(rawurlencode($done['sku'])) ?>"><svg class="ico"><use href="#i-boxes"/></svg> ดูในสินค้าในสต๊อก</a>
      <?php } ?>
    </div>
  </div>
  <?php if ($imgFail !== '') { ?>
    <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span>แต่รูปบันทึกไม่ผ่าน (<?php echo e($imgFail) ?>) — สินค้าเพิ่มแล้วโดยยังไม่มีรูป ให้ผู้ดูแลเพิ่มรูปที่หน้าแก้ไขสินค้า</span></div>
  <?php } ?>
<?php } ?>

<section class="card">
  <div class="card-head">
    <div>
      <h2>ข้อมูลสินค้าใหม่</h2>
      <span class="sub">SKU ตั้งครั้งเดียว เปลี่ยนภายหลังไม่ได้ · เพิ่มแล้วแก้เองไม่ได้ ตรวจข้อมูลและรูปก่อนกดเพิ่ม</span>
    </div>
  </div>
  <?php if (!$cats && !$canCat) { ?>
    <p class="adm-sec np-nocat">ยังไม่มีหมวดสินค้าในระบบ — ต้องมีหมวดก่อนจึงจะเพิ่มสินค้าได้ ติดต่อผู้จัดการสาขาหรือผู้ดูแลให้เพิ่มหมวด (หรือเปิดสิทธิ์ “เพิ่มหมวด”)</p>
  <?php } else { ?>
  <form class="adm-sec" method="post" action="product-add.php" id="pa-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
    <div class="adm-fields">
      <div class="field">
        <label for="np-name">ชื่อสินค้า</label>
        <input class="input" type="text" id="np-name" name="name" value="<?php echo e($v('name')) ?>" maxlength="200" required autocomplete="off" placeholder="เช่น สายชาร์จ USB-C 1 เมตร">
      </div>
      <div class="field">
        <span class="lbl" id="np-sku-l">SKU (รหัสสินค้า)</span>
        <div class="np-sku" role="radiogroup" aria-labelledby="np-sku-l">
          <label class="check"><input type="radio" name="sku_mode" value="auto"<?php echo $manual ? '' : ' checked' ?>> ตั้งให้อัตโนมัติ (P0001 …)</label>
          <label class="check"><input type="radio" name="sku_mode" value="manual"<?php echo $manual ? ' checked' : '' ?>> กรอกเอง</label>
        </div>
        <input class="input np-sku-in" type="text" id="np-sku" name="sku" value="<?php echo e($v('sku')) ?>" maxlength="40" autocomplete="off" autocapitalize="characters"
               placeholder="เช่น CS-010" aria-label="SKU ที่กรอกเอง"<?php echo $manual ? ' required' : ' hidden' ?>>
        <small class="adm-hint">A–Z 0–9 . _ - · ตั้งแล้วเปลี่ยนไม่ได้</small>
      </div>
      <div class="field">
        <label for="np-cat">หมวด</label>
        <select class="input" id="np-cat" name="cat">
          <option value="">— เลือกหมวด —</option>
          <?php $cv = $v('cat'); foreach ($cats as $c) { ?>
            <option value="<?php echo e($c) ?>"<?php echo $cv === $c ? ' selected' : '' ?>><?php echo e($c) ?></option>
          <?php } ?>
        </select>
        <?php if ($canCat) { ?>
          <input class="input np-cat-new" type="text" name="cat_new" value="<?php echo e($v('cat_new')) ?>" maxlength="50" autocomplete="off"
                 placeholder="หรือพิมพ์หมวดใหม่ เช่น หูฟัง" aria-label="หมวดใหม่">
          <small class="adm-hint">พิมพ์หมวดใหม่ = สร้างหมวดนั้นให้ด้วย (ใช้ได้ทุกสาขา)</small>
        <?php } ?>
      </div>
      <div class="field">
        <label for="np-unit">หน่วย</label>
        <input class="input" type="text" id="np-unit" name="unit" value="<?php echo e($v('unit', 'ชิ้น')) ?>" maxlength="20" list="np-units" autocomplete="off">
        <datalist id="np-units"><?php foreach (product_units() as $un) { ?><option value="<?php echo e($un) ?>"><?php } ?></datalist>
      </div>
      <div class="field">
        <label for="np-cost">ทุนต่อหน่วย (บาท)</label>
        <input class="input" type="text" id="np-cost" name="cost" value="<?php echo e($v('cost')) ?>" inputmode="decimal" autocomplete="off" placeholder="0.00">
        <small class="adm-hint">ใช้คิดมูลค่าตอนรับเข้า / ตรวจนับ</small>
      </div>
      <div class="field">
        <label for="np-price">ราคาขาย (บาท · รวม VAT)</label>
        <input class="input" type="text" id="np-price" name="price" value="<?php echo e($v('price')) ?>" inputmode="decimal" autocomplete="off" placeholder="0.00">
        <small class="adm-hint">มีผลกับบิลทันทีที่บันทึก</small>
      </div>
      <div class="field">
        <label for="np-reorder">จุดสั่งซื้อ</label>
        <input class="input" type="text" id="np-reorder" name="reorder" value="<?php echo e($v('reorder')) ?>" inputmode="numeric" autocomplete="off" placeholder="0">
        <small class="adm-hint">เหลือเท่านี้หรือน้อยกว่า = ใกล้หมด</small>
      </div>
      <div class="field">
        <label for="np-barcode">บาร์โค้ด <small class="adm-none">(ไม่บังคับ)</small></label>
        <input class="input" type="text" id="np-barcode" name="barcode" value="<?php echo e($v('barcode')) ?>" maxlength="40" autocomplete="off">
      </div>
      <div class="field">
        <label for="np-image">รูปสินค้า <small class="adm-none">(ไม่บังคับ · JPG / PNG / WEBP · รูปใหญ่ระบบย่อให้เอง)</small></label>
        <input class="input" type="file" id="np-image" name="image" accept="image/jpeg,image/png,image/webp" data-shrink data-prev="np-image-prev" data-note="np-image-note">
        <?php if ($bad !== '') { ?><small class="adm-hint hist-void">รูปไม่ผ่าน: <?php echo e($bad) ?> — เลือกรูปใหม่อีกครั้ง</small><?php } ?>
        <img class="prod-edit-img img-prev" id="np-image-prev" alt="" hidden>
        <small class="adm-hint img-note" id="np-image-note" hidden></small>
      </div>
    </div>
    <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มสินค้า</button>
  </form>
  <?php } ?>
</section>

<script>
/* SKU: ตั้งให้อัตโนมัติ / กรอกเอง */
(function () {
  var f = document.getElementById('pa-form'), skuIn = document.getElementById('np-sku');
  if (!f || !skuIn) { return; }
  f.addEventListener('change', function (ev) {
    if (ev.target.name !== 'sku_mode') { return; }
    var manual = (ev.target.value === 'manual');
    skuIn.hidden = !manual;
    skuIn.required = manual;
    if (manual) { skuIn.focus(); }
  });
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
