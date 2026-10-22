<?php
/**
 * FILE: themes/aostock/inc/product-form.php
 * ROLE: ฟอร์มสินค้าหน้าเดียวกันทั้งผู้ดูแล (adm-product-edit.php) และสาขา (product-add.php) — เพิ่ม / แก้ไข + การ์ดรูปและสถานะ
 * DEPENDS: themes/aostock/include/function.php · ต้องตั้ง $PF ก่อน include (ดูด้านล่าง)
 * TABLES: - (แสดงผลอย่างเดียว — บันทึกที่หน้าที่เรียกผ่าน Stock_doAddProduct / Stock_doSaveProduct ใน api.php)
 * TODO:
 *   - [x] 22 ต.ค. (ผู้ใช้ขอ): ฟอร์มเพิ่มสินค้าของผู้ดูแลกับผู้จัดการเหมือนกัน — SKU อัตโนมัติ / กรอกเอง · พิมพ์หมวดใหม่ · คำอธิบายใต้ช่อง
 *         · โหมดแก้ไข (ผู้ดูแล / ผู้จัดการแก้สินค้าของสาขาตัวเอง) + การ์ดรูปและสถานะ · ผู้ดูแลย้ายสินค้าของสาขาเป็นส่วนกลางได้
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ตั้งก่อน include:
     $PF = array(
       'action'  => ปลายทางของฟอร์ม (เช่น 'product-add.php?sku=P0001'),
       'p'       => สินค้าที่แก้ (product_shape) · null = เพิ่มใหม่,
       'v'       => function ($k, $def) — ค่าในช่อง (ค่าที่กรอกไว้ตอนบันทึกไม่ผ่าน หรือค่าเดิมของสินค้า),
       'canCat'  => แสดงช่อง “พิมพ์หมวดใหม่” (เพิ่มใหม่ + มีสิทธิ์เพิ่มหมวด),
       'scope'   => บรรทัดบอกว่าเป็นสินค้าของใคร (ส่วนกลาง / สาขา),
       'bad'     => ข้อความรูปไม่ผ่าน ('' = ไม่มี),
       'canMove' => แสดงปุ่ม “ย้ายเป็นสินค้าส่วนกลาง” (ผู้ดูแล + สินค้าของสาขา),
     )
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$pfP     = $PF['p'];
$pfNew   = ($pfP === null);
$pfV     = $PF['v'];
$pfCats  = product_cats();
$pfAct   = $PF['action'];
$pfMan   = ($pfV('sku_mode', 'auto') === 'manual');
$pfImg   = $pfNew ? '' : product_img($pfP);
?>
<section class="card">
  <div class="card-head">
    <div>
      <h2><?php echo $pfNew ? 'ข้อมูลสินค้าใหม่' : 'ข้อมูลสินค้า' ?></h2>
      <span class="sub"><?php echo e($PF['scope']) ?> · <?php echo $pfNew ? 'SKU ตั้งครั้งเดียว เปลี่ยนภายหลังไม่ได้ · ยอดเริ่มที่ 0 — รับของเข้าที่หน้า “นำเข้าสินค้า”'
                                                              : ($pfP['active'] ? 'ขายอยู่' : 'เลิกขายแล้ว') . ' · ยอดคงเหลือแก้ที่นี่ไม่ได้ (ใช้ใบรับเข้า / เบิก / ตรวจนับ)' ?></span>
    </div>
  </div>
  <?php if (!$pfCats && !$PF['canCat']) { ?>
    <p class="adm-sec np-nocat">ยังไม่มีหมวดสินค้าในระบบ — ต้องมีหมวดก่อนจึงจะเพิ่มสินค้าได้ ติดต่อผู้จัดการสาขาหรือผู้ดูแลให้เพิ่มหมวด (หรือเปิดสิทธิ์ “เพิ่มหมวด”)</p>
  <?php } else { ?>
  <form class="adm-sec" method="post" action="<?php echo e($pfAct) ?>" id="pf-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
    <input type="hidden" name="act" value="save">
    <div class="adm-fields">
      <div class="field">
        <label for="pf-name">ชื่อสินค้า</label>
        <input class="input" type="text" id="pf-name" name="name" value="<?php echo e($pfV('name', $pfNew ? '' : $pfP['name'])) ?>" maxlength="200" required autocomplete="off" placeholder="เช่น สายชาร์จ USB-C 1 เมตร">
      </div>
      <div class="field">
        <?php if ($pfNew) { ?>
          <span class="lbl" id="pf-sku-l">SKU (รหัสสินค้า)</span>
          <div class="np-sku" role="radiogroup" aria-labelledby="pf-sku-l">
            <label class="check"><input type="radio" name="sku_mode" value="auto"<?php echo $pfMan ? '' : ' checked' ?>> ตั้งให้อัตโนมัติ (P0001 …)</label>
            <label class="check"><input type="radio" name="sku_mode" value="manual"<?php echo $pfMan ? ' checked' : '' ?>> กรอกเอง</label>
          </div>
          <input class="input np-sku-in" type="text" id="pf-sku" name="sku" value="<?php echo e($pfV('sku', '')) ?>" maxlength="40" autocomplete="off" autocapitalize="characters"
                 placeholder="เช่น CS-010" aria-label="SKU ที่กรอกเอง"<?php echo $pfMan ? ' required' : ' hidden' ?>>
          <small class="adm-hint">A–Z 0–9 . _ - · 1 สี / รุ่น = 1 SKU · ตั้งแล้วเปลี่ยนไม่ได้</small>
        <?php } else { ?>
          <label for="pf-sku">SKU (รหัสสินค้า)</label>
          <input class="input" type="text" id="pf-sku" value="<?php echo e($pfP['sku']) ?>" disabled>
          <small class="adm-hint">ตั้งแล้วเปลี่ยนไม่ได้ (เอกสารเก่าอ้างถึง)</small>
        <?php } ?>
      </div>
      <div class="field">
        <label for="pf-cat">หมวด</label>
        <select class="input" id="pf-cat" name="cat"<?php echo $pfNew && $PF['canCat'] ? '' : ' required' ?>>
          <option value="">— เลือกหมวด —</option>
          <?php $pfCv = $pfV('cat', $pfNew ? '' : $pfP['cat']); foreach ($pfCats as $c) { ?>
            <option value="<?php echo e($c) ?>"<?php echo $pfCv === $c ? ' selected' : '' ?>><?php echo e($c) ?></option>
          <?php } ?>
        </select>
        <?php if ($pfNew && $PF['canCat']) { ?>
          <input class="input np-cat-new" type="text" name="cat_new" value="<?php echo e($pfV('cat_new', '')) ?>" maxlength="50" autocomplete="off"
                 placeholder="หรือพิมพ์หมวดใหม่ เช่น หูฟัง" aria-label="หมวดใหม่">
          <small class="adm-hint">พิมพ์หมวดใหม่ = สร้างหมวดนั้นให้ด้วย (หมวดใช้ร่วมกันทุกสาขา)</small>
        <?php } ?>
      </div>
      <div class="field">
        <label for="pf-unit">หน่วย</label>
        <input class="input" type="text" id="pf-unit" name="unit" value="<?php echo e($pfV('unit', $pfNew ? 'ชิ้น' : $pfP['unit'])) ?>" maxlength="20" list="pf-units" autocomplete="off">
        <datalist id="pf-units"><?php foreach (product_units() as $un) { ?><option value="<?php echo e($un) ?>"><?php } ?></datalist>
      </div>
      <div class="field">
        <label for="pf-cost">ทุนต่อหน่วย (บาท)</label>
        <input class="input" type="text" id="pf-cost" name="cost" value="<?php echo e($pfV('cost', $pfNew ? '' : Stock_formatPlainNumber($pfP['cost']))) ?>" inputmode="decimal" autocomplete="off" placeholder="0.00">
        <small class="adm-hint">ใช้คิดมูลค่าตอนรับเข้า / ตรวจนับ</small>
      </div>
      <div class="field">
        <label for="pf-price">ราคาขาย (บาท · รวม VAT)</label>
        <input class="input" type="text" id="pf-price" name="price" value="<?php echo e($pfV('price', $pfNew ? '' : Stock_formatPlainNumber($pfP['price']))) ?>" inputmode="decimal" autocomplete="off" placeholder="0.00">
        <small class="adm-hint">มีผลกับบิลทันทีที่บันทึก</small>
      </div>
      <div class="field">
        <label for="pf-reorder">จุดสั่งซื้อ</label>
        <input class="input" type="text" id="pf-reorder" name="reorder" value="<?php echo e($pfV('reorder', $pfNew ? '' : (string) $pfP['reorder'])) ?>" inputmode="numeric" autocomplete="off" placeholder="0">
        <small class="adm-hint">เหลือเท่านี้หรือน้อยกว่า = ใกล้หมด</small>
      </div>
      <div class="field">
        <label for="pf-barcode">บาร์โค้ด <small class="adm-none">(ไม่บังคับ)</small></label>
        <input class="input" type="text" id="pf-barcode" name="barcode" value="<?php echo e($pfV('barcode', $pfNew ? '' : $pfP['barcode'])) ?>" maxlength="40" autocomplete="off">
      </div>
      <div class="field">
        <label for="pf-image">รูปสินค้า <small class="adm-none">(ไม่บังคับ · JPG / PNG / WEBP · รูปใหญ่ระบบย่อให้เอง)</small></label>
        <input class="input" type="file" id="pf-image" name="image" accept="image/jpeg,image/png,image/webp" data-shrink data-prev="pf-image-prev" data-note="pf-image-note">
        <?php if ($PF['bad'] !== '') { ?><small class="adm-hint hist-void">รูปไม่ผ่าน: <?php echo e($PF['bad']) ?> — เลือกรูปใหม่อีกครั้ง</small><?php } ?>
        <?php if ($pfImg !== '') { ?><small class="adm-hint">มีรูปอยู่แล้ว — เลือกไฟล์ใหม่เพื่อแทนที่</small><?php } ?>
        <img class="prod-edit-img img-prev" id="pf-image-prev" alt="" hidden>
        <small class="adm-hint img-note" id="pf-image-note" hidden></small>
      </div>
    </div>
    <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-<?php echo $pfNew ? 'plus' : 'check' ?>"/></svg> <?php echo $pfNew ? 'เพิ่มสินค้า' : 'บันทึก' ?></button>
  </form>
  <?php } ?>
</section>

<?php if (!$pfNew) { ?>
<section class="card">
  <div class="card-head"><div><h2>รูปและสถานะ</h2><span class="sub">ยอดคงเหลือ: <?php
      $pfParts = array();
      foreach (branches_active() as $bc => $b) {
          if (Stock_isProductInBranch($pfP, $bc)) {
              $pfParts[] = $b['short'] . ' ' . number_format(product_qty($pfP, $bc));
          }
      }
      echo e($pfParts ? implode(' · ', $pfParts) : '—');
  ?></span></div></div>
  <?php /* รูปซ้าย คำอธิบาย + ปุ่มลบรูปขวา · แถวล่าง = สถานะการขาย · ผู้ดูแล + สินค้าของสาขา = ย้ายเป็นส่วนกลาง */ ?>
  <div class="adm-sec prod-media">
    <?php if ($pfImg !== '') { ?>
      <img class="prod-edit-img" src="<?php echo e($pfImg) ?>" alt="<?php echo e($pfP['name']) ?>" loading="lazy">
    <?php } else { ?>
      <?php echo thumb_html($pfP, 'prod-edit-thumb') ?>
    <?php } ?>
    <div class="prod-media-t">
      <b>รูปสินค้า</b>
      <small class="adm-hint"><?php echo $pfImg !== '' ? 'ใช้บนการ์ดหน้าขาย / นำเข้า / เบิก / ตรวจนับ · เปลี่ยนรูปที่ช่อง “รูปสินค้า” ด้านบน'
                                                   : 'ยังไม่มีรูป — การ์ดจะแสดงไอคอนตามหมวดแทน · เพิ่มรูปที่ช่อง “รูปสินค้า” ด้านบน' ?></small>
      <?php if ($pfP['image'] !== '') { ?>
        <form method="post" action="<?php echo e($pfAct) ?>" data-confirm="ask" data-ask="ลบรูปของ “<?php echo e($pfP['name']) ?>” ?" data-ask-ok="ลบรูป">
          <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
          <input type="hidden" name="act" value="noimg">
          <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-trash"/></svg> ลบรูป</button>
        </form>
      <?php } ?>
    </div>
  </div>
  <div class="adm-sec prod-state">
    <b>สถานะ</b>
    <span class="bdg <?php echo $pfP['active'] ? 'bdg-ok' : 'bdg-out' ?>"><?php echo $pfP['active'] ? 'ขายอยู่' : 'เลิกขายแล้ว' ?></span>
    <form method="post" action="<?php echo e($pfAct) ?>">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
      <input type="hidden" name="act" value="<?php echo $pfP['active'] ? 'off' : 'on' ?>">
      <button class="btn <?php echo $pfP['active'] ? 'btn-ghost' : 'btn-primary' ?> btn-sm" type="submit"><?php echo $pfP['active'] ? 'เลิกขายสินค้านี้' : 'เปิดขายอีกครั้ง' ?></button>
    </form>
    <small class="adm-hint"><?php echo $pfP['active'] ? 'เลิกขาย = ซ่อนจากหน้าขาย / นำเข้า / ตรวจนับ · ประวัติและรายงานยังอยู่ครบ' : 'เปิดขายอีกครั้ง = กลับมาขาย / นำเข้า / ตรวจนับได้ตามปกติ' ?></small>
  </div>
  <?php if (!empty($PF['canMove'])) { ?>
  <div class="adm-sec prod-state">
    <b>เจ้าของ</b>
    <span class="bdg bdg-adj"><?php echo e(Stock_formatProductOwner($pfP)) ?></span>
    <form method="post" action="<?php echo e($pfAct) ?>" data-confirm="ask" data-ask="ย้าย “<?php echo e($pfP['name']) ?>” เป็นสินค้าส่วนกลาง ?"
          data-ask-sub="ทุกสาขาขาย / นำเข้าได้ · ยอดของสาขาเดิมอยู่ครบ สาขาอื่นเริ่มที่ 0 · ผู้จัดการสาขาเดิมจะแก้สินค้านี้ไม่ได้แล้ว" data-ask-ok="ย้ายเป็นส่วนกลาง">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
      <input type="hidden" name="act" value="move">
      <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-boxes"/></svg> ย้ายเป็นสินค้าส่วนกลาง</button>
    </form>
    <small class="adm-hint">ตอนนี้ขาย / นำเข้าได้เฉพาะสาขาเจ้าของ · ย้ายแล้วทุกสาขาใช้ได้</small>
  </div>
  <?php } ?>
</section>
<?php } ?>

<script>
/* SKU: ตั้งให้อัตโนมัติ / กรอกเอง (เพิ่มใหม่) */
(function () {
  var f = document.getElementById('pf-form'), skuIn = document.getElementById('pf-sku');
  if (!f || !skuIn || skuIn.disabled) { return; }
  f.addEventListener('change', function (ev) {
    if (ev.target.name !== 'sku_mode') { return; }
    var manual = (ev.target.value === 'manual');
    skuIn.hidden = !manual;
    skuIn.required = manual;
    if (manual) { skuIn.focus(); }
  });
})();
</script>
