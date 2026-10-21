<?php
/**
 * FILE: themes/aostock/inc/sale-live.php
 * ROLE: ส่วนที่ htmx สลับได้ของหน้าขายสินค้า
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_product / ao_stock_balance / ao_stock_sale (ผ่าน api.php) · ตะกร้าอยู่ใน $_SESSION['cart']
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 7: บิลที่บันทึกแล้วอยู่ในตาราง — ไฟล์นี้แสดงตะกร้า + สรุปยอดวันนี้
 *   - [x] ช่วงที่ 11: ช่องแก้ยอดชำระ (ส่วนลด) เฉพาะคนที่มีสิทธิ์ discount · ลิงก์ประวัติตามสิทธิ์
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 *   - [x] 12 ต.ค.: ช่อง “รับเงินมา” / ปุ่ม “พอดี” รับและใส่ยอดที่มีสตางค์ได้ (แป้น decimal) — เดิมตัดเป็นบาทเต็ม เงินทอนติดลบ
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 3): แตะสินค้า / ปุ่มในตะกร้า / รับเงิน → ส่งกลับแค่ตะกร้า (#sale-cart) + การ์ดใบที่แตะ (hx-swap-oob)
 *         เดิมส่งการ์ดสินค้าทุกใบกลับมาวาดใหม่ทุกครั้ง (สินค้า 3,000 ตัว = 3 MB ต่อการแตะ) · สคริปต์ช่องรับเงินย้ายเข้าไปใน aside
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 4): การ์ดครั้งละ 60 ใบ + ปุ่ม “แสดงเพิ่ม” ($LIVE_PART 'more' = ส่งกลับแค่ชุดถัดไป)
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 5): ค้นสินค้าด้วย SQL แบบแบ่งหน้า (Stock_searchPickProducts) — ฐานข้อมูลกรอง / นับ ส่งมาแค่ 60 ตัวที่จะวาด (เดิมดึงทั้งร้านมากรองใน PHP)
 *   - [x] 14 ต.ค. (ผู้ใช้เลือก): + / − นับในเครื่องก่อนแล้วบันทึกเบื้องหลัง — ปุ่ม data-step · ช่อง data-qty (JS ใน inc/footer.php) · เดิมกดรัว ๆ แล้วหาย
 *   - [x] 14 ต.ค. (ผู้ใช้เลือก): แตะสินค้าตัวใหม่ขึ้นตะกร้าทันที — แถวเป็น $row ใช้ทั้งวาดตะกร้าและแม่แบบ #pick-row · การ์ดมี data-pick · hx-ext="ao-live" (คำตอบที่มาช้าลงตัวที่อยู่บนจอ)
 *   - [x] 15 ต.ค. (เมนูประวัติแยกหน้า ช่วง 4): ปุ่ม “ดูประวัติบิลวันนี้” เฉพาะคนที่มีสิทธิ์ดูประวัติขาย
 *   - [x] 21 ต.ค. (ผู้ใช้ขอ): สาขาที่ไม่ขาย VAT (Stock_isBranchVat) ไม่มีปุ่มเลือก VAT / ไม่ VAT — ช่องซ่อน vat=0 แทน
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ส่วนที่ htmx สลับได้ของหน้าขายสินค้า
   ----------------------------------------------------------
   ต้องกำหนดก่อน include:
     $user  $code  $q  $cat
     $LIVE_PART  (ไม่บังคับ) 'all' = ทั้งส่วน #sale-live · 'cart' = เฉพาะตะกร้า #sale-cart + การ์ดของ $OOB_SKUS · 'more' = การ์ดชุดถัดไป (?from=) + ปุ่มแสดงเพิ่ม
     $OOB_SKUS   (ไม่บังคับ) SKU ที่ต้องวาดการ์ดใหม่ (ตะกร้าเปลี่ยน / ขายแล้วยอดเปลี่ยน)
   ไฟล์นี้ถูกเรียกสามทาง
     1) จาก sale.php ตอนโหลดหน้าเต็ม
     2) htmx ค้นหา / เลือกหมวด → สลับทั้ง #sale-live
     3) htmx แตะสินค้า / ปุ่มในตะกร้า / รับเงิน → สลับแค่ #sale-cart + การ์ดที่แตะแบบ hx-swap-oob
        (เดิมส่งการ์ดทุกใบกลับมาทุกครั้ง — สินค้า 3,000 ตัว = 3 MB และ 14 วินาทีบน iPad ต่อการแตะ)
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$LIVE_PART = (isset($LIVE_PART) && in_array($LIVE_PART, array('cart', 'more'), true)) ? $LIVE_PART : 'all';
list($from, $end) = Stock_getListRange();     // การ์ดครั้งละ 60 ใบ + ปุ่ม “แสดงเพิ่ม” (ช่วง 4)
/* ฐานข้อมูลกรอง นับ และส่งมาเฉพาะการ์ดที่จะวาดรอบนี้ (ช่วง 5) — $list = การ์ดที่จะวาด · $total = จำนวนที่ตรงทั้งหมด */
$found = ($LIVE_PART === 'cart') ? array('rows' => array(), 'total' => 0)
       : Stock_searchPickProducts($code, $q, $cat, ($LIVE_PART === 'more') ? $from : 0, ($LIVE_PART === 'more') ? $end - $from : $end);
$list  = $found['rows'];
$total = $found['total'];
$cart = cart_lines();
$tot  = cart_total();
$sum  = sale_summary($code);
$qs   = ($q !== '' ? 'q=' . rawurlencode($q) : '') . ($cat !== '' ? ($q !== '' ? '&' : '') . 'cat=' . rawurlencode($cat) : '');
$base = 'sale.php' . ($qs !== '' ? '?' . $qs : '');

/* การ์ดสินค้าหนึ่งใบ — ใช้ทั้งตอนวาดรายการ และตอนส่งกลับใบเดียวแบบ hx-swap-oob */
$tile = function ($p, $oob) use ($base) {
    $inCart = isset($_SESSION['cart'][$p['sku']]) ? (int) $_SESSION['cart'][$p['sku']] : 0; ?>
          <form method="post" action="<?php echo e($base) ?>" class="g-form" id="<?php echo e(Stock_buildTileId($p['sku'])) ?>" data-sku="<?php echo e($p['sku']) ?>"<?php echo $oob ? ' hx-swap-oob="true"' : '' ?>
                hx-post="<?php echo e($base) ?>" hx-target="#sale-cart" hx-swap="outerHTML"
                data-pick='<?php echo e(json_encode(array('name' => $p['name'], 'unit' => $p['unit'], 'price' => (float) $p['price'], 'have' => (int) $p['qty'], 'max' => (int) $p['qty']))) ?>'>
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
            <input type="hidden" name="act" value="add">
            <input type="hidden" name="sku" value="<?php echo e($p['sku']) ?>">
            <button class="good<?php echo $p['qty'] <= 0 ? ' is-out' : '' ?><?php echo $inCart > 0 ? ' is-in' : '' ?>"
                    type="submit" <?php echo $p['qty'] <= 0 ? 'disabled' : '' ?>>
              <?php if ($inCart > 0) { ?><i class="g-bdg"><?php echo $inCart ?></i><?php } ?>
              <?php echo thumb_html($p, 'thumb--lg') ?>
              <span class="g-cat"><?php echo e($p['cat']) ?></span>
              <span class="g-nm"><?php echo e($p['name']) ?></span>
              <span class="g-pr num"><?php echo money2($p['price']) ?><small>บาท/<?php echo e($p['unit']) ?></small></span>
              <span class="g-qty<?php echo $p['qty'] <= 0 ? ' out' : ($p['qty'] <= $p['reorder'] ? ' low' : '') ?>">
                <?php echo $p['qty'] <= 0 ? 'หมด' : 'เหลือ ' . number_format($p['qty']) . ' ' . e($p['unit']) ?>
              </span>
            </button>
          </form>
<?php
};

/* แถวในตะกร้า — ใช้วาดตะกร้า และเป็นแม่แบบ #pick-row ให้ JS สร้างแถวตอนแตะสินค้าตัวใหม่ ({sku} {name} {unit} · data-f = ช่องที่ JS เติม) */
$row = function ($l, $max) use ($base) { ?>
            <li>
              <div class="cl-t">
                <b><?php echo e($l['name']) ?></b>
                <small class="num"><span data-f="price"><?php echo money2($l['price']) ?></span> × <span data-f="qty"><?php echo $l['qty'] ?></span> <?php echo e($l['unit']) ?></small>
              </div>
              <?php /* + / − นับในเครื่องก่อนแล้วบันทึกเบื้องหลัง (data-step · JS ใน inc/footer.php — 14 ต.ค.)
                       ฟอร์ม + / − ไม่มี hx-post แล้ว — ปิด JavaScript ยังส่งแบบปกติได้ · ช่องจำนวน (data-qty) ส่ง act=set ตอน change */ ?>
              <div class="cl-q" hx-target="#sale-cart" hx-swap="outerHTML">
                <form method="post" action="<?php echo e($base) ?>">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                  <input type="hidden" name="act" value="minus">
                  <input type="hidden" name="sku" value="<?php echo e($l['sku']) ?>">
                  <button type="submit" aria-label="ลดจำนวน" data-step="-1"><svg class="ico"><use href="#i-minus"/></svg></button>
                </form>

                <?php /* พิมพ์จำนวนแก้ได้เลยเมื่อกดผิด ไม่ต้องกดลดทีละครั้ง */ ?>
                <form method="post" action="<?php echo e($base) ?>" hx-post="<?php echo e($base) ?>" class="cl-set"
                      hx-trigger="submit, change" data-no-busy>
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                  <input type="hidden" name="act" value="set">
                  <input type="hidden" name="sku" value="<?php echo e($l['sku']) ?>">
                  <input class="num" type="text" inputmode="numeric" name="qty" value="<?php echo $l['qty'] ?>"
                         aria-label="จำนวนของ <?php echo e($l['name']) ?>" autocomplete="off"
                         data-qty data-tile data-sku="<?php echo e($l['sku']) ?>" data-min="0" data-max="<?php echo (int) $max ?>">
                  <noscript><button type="submit">ตกลง</button></noscript>
                </form>

                <form method="post" action="<?php echo e($base) ?>">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                  <input type="hidden" name="act" value="add">
                  <input type="hidden" name="sku" value="<?php echo e($l['sku']) ?>">
                  <button type="submit" aria-label="เพิ่มจำนวน" data-step="1"><svg class="ico"><use href="#i-plus"/></svg></button>
                </form>
              </div>
              <div class="cl-s num" data-f="sum"><?php echo money2($l['sum']) ?></div>
              <form class="cl-del" method="post" action="<?php echo e($base) ?>"
                    hx-post="<?php echo e($base) ?>" hx-target="#sale-cart" hx-swap="outerHTML">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                <input type="hidden" name="act" value="del">
                <input type="hidden" name="sku" value="<?php echo e($l['sku']) ?>">
                <button type="submit" aria-label="เอา <?php echo e($l['name']) ?> ออกจากตะกร้า"
                        title="เอาออกจากตะกร้า"><svg class="ico"><use href="#i-x"/></svg></button>
              </form>
            </li>
<?php
};

/* กด “แสดงเพิ่ม” → การ์ดชุดถัดไป + ปุ่มใหม่ (สลับแทนปุ่มเดิมในกริด) */
if ($LIVE_PART === 'more') {
    foreach ($list as $p) {
        $tile($p, false);
    }
    Stock_renderMoreButton($end, $total, $base);
    return;
}
?>
<?php if ($LIVE_PART === 'all') { ?>
<div class="sale-wrap" id="sale-live" hx-ext="ao-live">

  <!-- ==================== เลือกสินค้า ==================== -->
  <section class="card sale-pick">
    <?php /* พิมพ์แล้วกรองทันที — htmx ส่งทั้งฟอร์ม (q + cat) ให้ครบ */ ?>
    <form class="sale-find" method="get" action="sale.php"
          hx-get="sale.php" hx-target="#sale-live" hx-swap="outerHTML" hx-push-url="true">
      <div class="find-in">
        <svg class="ico"><use href="#i-search"/></svg>
        <label class="sr-only" for="q">ค้นหาสินค้า</label>
        <?php /* ใช้ input ไม่ใช่ keyup เพราะพิมพ์ไทยผ่าน IME หรือแป้นบนจอ
                 บางเครื่องไม่ส่ง keyup · hx-preserve ทำให้เคอร์เซอร์ยังอยู่ในช่อง */ ?>
        <input type="search" id="q" name="q" value="<?php echo e($q) ?>" placeholder="ชื่อสินค้า หรือ SKU"
               hx-get="sale.php" hx-trigger="input changed delay:350ms, search"
               hx-include="closest form" hx-target="#sale-live" hx-swap="outerHTML"
               hx-sync="this:replace" hx-push-url="true" hx-preserve="true">
      </div>
      <?php if ($cat !== '') { ?><input type="hidden" name="cat" value="<?php echo e($cat) ?>"><?php } ?>
      <button class="btn" type="submit">ค้นหา</button>
    </form>

    <div class="cats" hx-target="#sale-live" hx-swap="outerHTML" hx-push-url="true">
      <a class="cat<?php echo $cat === '' ? ' on' : '' ?>"
         href="sale.php<?php echo $q !== '' ? '?q=' . rawurlencode($q) : '' ?>"
         hx-get="sale.php<?php echo $q !== '' ? '?q=' . rawurlencode($q) : '' ?>">ทั้งหมด</a>
      <?php foreach (product_cats() as $c) { ?>
        <?php $u = 'sale.php?cat=' . rawurlencode($c) . ($q !== '' ? '&q=' . rawurlencode($q) : ''); ?>
        <a class="cat<?php echo $cat === $c ? ' on' : '' ?>" href="<?php echo e($u) ?>" hx-get="<?php echo e($u) ?>"><?php echo e($c) ?></a>
      <?php } ?>
    </div>

    <?php if (!$list) { ?>
      <p class="empty">
        <svg class="ico"><use href="#i-search"/></svg>
        ไม่พบสินค้าที่ตรงกับคำค้น<br><small>ลองพิมพ์ชื่อสั้นลง หรือเลือกหมวด “ทั้งหมด”</small>
      </p>
    <?php } else { ?>
      <div class="goods">
        <?php foreach ($list as $p) { $tile($p, false); } ?>
        <?php Stock_renderMoreButton($end, $total, $base); ?>
      </div>
    <?php } ?>
  </section>
<?php } ?>

  <!-- ==================== ตะกร้า ==================== -->
  <aside class="sale-cart" id="sale-cart" hx-ext="ao-live">
    <template id="pick-row" data-dir="0"><ul class="cart-list"><?php $row(array('sku' => '{sku}', 'name' => '{name}', 'unit' => '{unit}', 'price' => 0, 'qty' => 0, 'sum' => 0), 0); ?></ul></template>
    <div class="card cart-card">
      <div class="card-head">
        <div>
          <h2>ตะกร้า</h2>
          <span class="sub"><?php echo count($cart) ?> รายการ · <?php echo number_format(cart_count()) ?> ชิ้น</span>
        </div>
        <?php if ($cart) { ?>
          <form method="post" action="<?php echo e($base) ?>" hx-post="<?php echo e($base) ?>"
                hx-target="#sale-cart" hx-swap="outerHTML">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
            <input type="hidden" name="act" value="clear">
            <button class="icon-btn" type="submit" title="ล้างตะกร้า" aria-label="ล้างตะกร้า">
              <svg class="ico"><use href="#i-trash"/></svg>
            </button>
          </form>
        <?php } ?>
      </div>

      <?php if (!$cart) { ?>
        <p class="empty empty-cart">
          <svg class="ico"><use href="#i-cart"/></svg>
          ยังไม่มีสินค้าในตะกร้า<br><small>แตะสินค้าทางซ้ายเพื่อเริ่มขาย</small>
        </p>
      <?php } else { ?>

        <ul class="cart-list">
          <?php foreach ($cart as $l) { $row($l, product_qty($l['p'], $code)); } ?>
        </ul>

        <form method="post" action="<?php echo e($base) ?>" class="pay" data-confirm="pay" hx-confirm="ยืนยันการรับเงิน"
              hx-post="<?php echo e($base) ?>" hx-target="#sale-cart" hx-swap="outerHTML">
          <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
          <input type="hidden" name="act" value="pay">
          <input type="hidden" name="full" value="<?php echo e(number_format($tot, 2, '.', '')) ?>"><?php /* ยอดที่จอเห็น — ไม่ตรงตะกร้าจริง = ไม่บันทึก */ ?>

          <?php /* ยอดที่ต้องชำระแก้ได้ — ลดราคาให้ลูกค้า ระบบลงเป็น "ส่วนลด" ในบิลให้เอง (เพิ่มเกินราคาจริงไม่ได้)
                   ไม่มีสิทธิ์ให้ส่วนลด = แสดงยอดเต็มอย่างเดียว (ช่วงที่ 11 — ฝั่งเซิร์ฟเวอร์คิดราคาเต็มเสมออยู่แล้ว) */ ?>
          <?php if (!can($user, 'discount')) { ?>
          <div class="pay-tot">
            <span>ยอดที่ต้องชำระ</span>
            <b class="num" id="total" data-v="<?php echo (int) $tot ?>"><?php echo money2($tot) ?></b>
          </div>
          <?php } else { ?>
          <div class="pay-tot pay-net">
            <label for="net">ยอดที่ต้องชำระ<small>แก้ได้ถ้าลดราคาให้ลูกค้า</small></label>
            <input class="num" type="text" inputmode="decimal" id="net" name="net" autocomplete="off"
                   value="<?php echo e(rtrim(rtrim(number_format($tot, 2, '.', ''), '0'), '.')) ?>" data-full="<?php echo e($tot) ?>"
                   aria-label="ยอดที่ต้องชำระ">
            <b class="num" id="total" data-v="<?php echo (int) $tot ?>" hidden><?php echo money2($tot) ?></b>
          </div>
          <div class="pay-disc" id="pay-disc" hidden>
            <div><span>ราคาเต็ม</span><b class="num"><?php echo money2($tot) ?></b></div>
            <div class="d"><span>ส่วนลด</span><b class="num" id="disc">−0.00</b></div>
          </div>
          <p class="pay-err" id="net-err" hidden>ยอดชำระต้องมากกว่า 0 และไม่เกินราคาเต็ม <?php echo money2($tot) ?> บาท</p>
          <?php } ?>

          <?php /* บิล VAT กับไม่ VAT ใช้เลขที่คนละชุด — พนักงานเลือกตามที่ลูกค้าต้องการ
                   สาขายังไม่ได้ตั้งเลขผู้เสียภาษี / ชื่อบนหัวบิล → ปุ่ม VAT กดไม่ได้ + บอกว่าขาดอะไร (20 ต.ค. · bill_save ตรวจซ้ำ)
                   สาขาที่ไม่ขาย VAT (ผู้ดูแลตั้งที่หน้าจัดการสาขา · 21 ต.ค.) → ไม่มีตัวเลือก VAT เลย ส่ง vat=0 เสมอ (bill_save ตรวจซ้ำ) */
          if (!Stock_isBranchVat($code)) { ?>
          <input type="hidden" name="vat" value="0">
          <?php } else {
          $vatMiss = Stock_getVatMissing($code); ?>
          <div class="pay-how pay-vat">
            <label class="on"><input type="radio" name="vat" value="0" checked> ไม่ VAT</label>
            <label<?php echo $vatMiss ? ' class="off"' : '' ?>><input type="radio" name="vat" value="1"<?php echo $vatMiss ? ' disabled aria-describedby="vat-off"' : '' ?>> VAT</label>
          </div>
          <?php if ($vatMiss) { ?>
          <?php /* หน้าขายมีแต่พนักงาน (ผู้ดูแล / บัญชีถูกพาไปหน้าของตัวเอง) — จึงบอกให้แจ้งคนที่ตั้งได้ ไม่มีลิงก์ */ ?>
          <p class="pay-off" id="vat-off">ออกบิล VAT ไม่ได้ — สาขานี้ยังไม่ได้ตั้ง<?php echo e(implode(' · ', $vatMiss)) ?>
            · แจ้งฝ่ายบัญชี / ผู้ดูแลให้ตั้งที่หน้า “ตั้งค่าเลขที่บิล”</p>
          <?php } ?>
          <?php } ?>

          <div class="pay-how" id="pay-how">
            <label class="on"><input type="radio" name="method" value="cash" checked> เงินสด</label>
            <label><input type="radio" name="method" value="transfer"> โอน / พร้อมเพย์</label>
          </div>

          <?php /* รับสตางค์ได้ — ยอดลงตัวแสดงเป็นบาทเต็ม (980) มีสตางค์แสดง 2 ตำแหน่ง (99.50) · เดิม (int) ตัดเป็น 99 เงินทอนติดลบ (12 ต.ค.) */
          $totIn = (abs($tot - round($tot)) < 0.005) ? (string) (int) round($tot) : number_format($tot, 2, '.', ''); ?>
          <div id="cash-box">
            <span class="lbl lbl-mt">รับเงินมา</span>
            <div class="cash-in">
              <input class="input num" type="text" inputmode="decimal" id="received" name="received"
                     value="<?php echo e($totIn) ?>" autocomplete="off" aria-label="จำนวนเงินที่รับมา">
              <span class="unit">บาท</span>
            </div>
            <div class="amt-quick" id="recv-quick">
              <button type="button" data-set="<?php echo e($totIn) ?>">พอดี</button>
              <?php
              $seen = array(round($tot, 2));
              foreach (array(100, 500, 1000) as $step) {
                  $v = (int) (ceil($tot / $step) * $step);
                  if (in_array($v, $seen)) { continue; }
                  $seen[] = $v;
                  echo '<button type="button" data-set="' . $v . '">' . money($v) . '</button>';
              }
              ?>
            </div>
            <div class="kv1 kv-chg"><span>เงินทอน</span><b class="num" id="change">0.00</b></div>
          </div>

          <button class="btn-xl btn-pay" type="submit" data-qty-guard="pay">
            <svg class="ico"><use href="#i-check"/></svg> รับเงินและบันทึกบิล
          </button>
        </form>
      <?php } ?>
    </div>

    <!-- ===== ยอดขายวันนี้ ===== -->
    <div class="card">
      <div class="card-head">
        <div><h2>ยอดขายวันนี้</h2><span class="sub"><?php echo e(branch_name($code)) ?></span></div>
      </div>
      <div class="mini num mini-flat">
        <div class="m"><div class="lb">บิล</div><div class="nm"><?php echo number_format($sum['bills']) ?></div><div class="sb">ใบ</div></div>
        <div class="m"><div class="lb">ชิ้น</div><div class="nm"><?php echo number_format($sum['qty']) ?></div><div class="sb">รวมทุกบิล</div></div>
        <div class="m"><div class="lb">ยอดขาย</div><div class="nm"><?php echo money($sum['total']) ?></div><div class="sb">บาท</div></div>
      </div>
      <?php if ($sum['bills'] > 0) { ?>
        <div class="kv1"><span>เงินสด</span><b class="num"><?php echo money2($sum['cash']) ?></b></div>
        <div class="kv1"><span>โอน / พร้อมเพย์</span><b class="num"><?php echo money2($sum['transfer']) ?></b></div>
        <?php if (page_ok($user, 'history.php') && isset(Stock_getAllowedLogGroups($user)['sale'])) { ?>
        <a class="btn btn-block" href="history.php?t=sale">
          <svg class="ico"><use href="#i-history"/></svg> ดูประวัติบิลวันนี้
        </a>
        <?php } ?>
      <?php } ?>
      <?php $myBills = Stock_getMyBillsToday($code, $user, '', '', 0);   /* บิลที่ลืมพิมพ์ — ดู / พิมพ์ได้ทุกคน (เฉพาะบิลของตัวเอง) */ ?>
      <?php if ($myBills > 0) { ?>
        <button type="button" class="btn btn-ghost btn-block mb-open" data-mb-open>
          <svg class="ico"><use href="#i-print"/></svg> บิลของฉันวันนี้ (<?php echo number_format($myBills) ?> ใบ)
        </button>
      <?php } ?>
    </div>

  <?php /* อยู่ใน aside เพื่อให้สลับไปพร้อมตะกร้า (ตอนส่งกลับแค่ #sale-cart สคริปต์ก็ทำงานใหม่ด้วย) */ ?>
  <script>
  (function () {
    var totEl = document.getElementById('total');
    if (!totEl) { return; }
    var full = Number(totEl.getAttribute('data-v')) || 0;
    var tot  = full;
    var rec  = document.getElementById('received');
    var chg  = document.getElementById('change');
    var net  = document.getElementById('net');
    var dbox = document.getElementById('pay-disc');
    var disc = document.getElementById('disc');
    var nerr = document.getElementById('net-err');
    var fmt2 = function (n) { return n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };
    /* ค่าในช่องรับเงิน: ลงตัว = บาทเต็ม · มีสตางค์ = 2 ตำแหน่ง (แบบเดียวกับ $totIn ฝั่ง PHP) */
    var plain = function (n) { return Math.abs(n - Math.round(n)) < 0.005 ? String(Math.round(n)) : n.toFixed(2); };

    /* ยอดที่ต้องชำระเปลี่ยน → คำนวณส่วนลด และตั้งเงินที่รับมาให้เท่ายอดใหม่ */
    function netSync() {
      if (!net) { return; }
      var raw = String(net.value).replace(/[^0-9.]/g, '');
      var v   = raw === '' ? 0 : Number(raw);
      var ok  = v > 0 && v <= full + 0.001;
      tot = ok ? v : full;
      var d = Math.max(0, full - tot);
      dbox.hidden = !(ok && d >= 0.01);
      nerr.hidden = ok;
      disc.textContent = '−' + fmt2(d);
      totEl.setAttribute('data-v', String(tot));
      totEl.textContent = fmt2(tot);
      var pay = document.querySelector('.btn-pay');
      if (pay) { pay.disabled = !ok; }
      if (rec && !rec.getAttribute('data-touched')) { rec.value = plain(tot); }
      var q0 = document.querySelector('#recv-quick button');
      if (q0) { q0.setAttribute('data-set', plain(tot)); }
      paint();
    }
    if (net) {
      net.addEventListener('input', netSync);
      net.addEventListener('focus', function () { this.select(); });
    }
    if (rec) { rec.addEventListener('input', function () { this.setAttribute('data-touched', '1'); }); }

    function paint() {
      if (!rec || !chg) { return; }
      var v = Number(String(rec.value).replace(/[^0-9.]/g, '')) || 0;
      var d = Math.round((v - tot) * 100) / 100;
      chg.textContent = (d < 0 ? '-' : '') + Math.abs(d).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      chg.className = 'num' + (d < 0 ? ' neg' : '');
    }
    if (rec) { rec.addEventListener('input', paint); paint(); }

    var quick = document.getElementById('recv-quick');
    if (quick && rec) {
      var qb = quick.querySelectorAll('button');
      for (var i = 0; i < qb.length; i++) {
        qb[i].addEventListener('click', function () {
          rec.value = this.getAttribute('data-set');
          paint();
        });
      }
    }

    /* ปุ่มเลือกบิล VAT / ไม่ VAT (ปุ่มที่ปิดไว้คง class off) */
    var vw = document.querySelector('.pay-vat');
    if (vw) {
      var vl = vw.querySelectorAll('label');
      var vsync = function () {
        for (var v = 0; v < vl.length; v++) {
          var vi = vl[v].querySelector('input');
          vl[v].className = vi.disabled ? 'off' : (vi.checked ? 'on' : '');
        }
      };
      for (var v2 = 0; v2 < vl.length; v2++) {
        vl[v2].querySelector('input').addEventListener('change', vsync);
      }
    }

    var how = document.getElementById('pay-how');
    var box = document.getElementById('cash-box');
    if (how && box) {
      var labels = how.querySelectorAll('label');
      var sync = function () {
        var cash = how.querySelector('input[value="cash"]').checked;
        for (var k = 0; k < labels.length; k++) {
          labels[k].className = (labels[k].querySelector('input').checked) ? 'on' : '';
        }
        box.style.display = cash ? '' : 'none';
      };
      for (var j = 0; j < labels.length; j++) {
        labels[j].querySelector('input').addEventListener('change', sync);
      }
      sync();
    }
  })();
  </script>
  </aside>
<?php if ($LIVE_PART === 'all') { ?>
</div>
<?php } else { ?>
<?php
    /* ส่งกลับแค่ตะกร้า → การ์ดที่ยอดในตะกร้า / ยอดคงเหลือเปลี่ยน สลับใบเดียวแบบ hx-swap-oob (ใบที่ไม่อยู่บนจอ htmx ข้ามไปเอง) */
    foreach (Stock_getPickProducts($code, isset($OOB_SKUS) ? $OOB_SKUS : array()) as $p) {
        $tile($p, true);
    }
} ?>
