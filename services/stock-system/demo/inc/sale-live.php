<?php
/* ==========================================================
   AOSTOCK DEMO — ส่วนที่ htmx สลับได้ของหน้าขายสินค้า
   ----------------------------------------------------------
   ต้องกำหนดก่อน include:
     $user  $code  $q  $cat
   ไฟล์นี้ถูกเรียกสองทาง
     1) จาก sale.php ตอนโหลดหน้าเต็ม
     2) ตอบกลับคำขอของ htmx เพื่อสลับเฉพาะ #sale-live
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$list = sale_products($code, $q, $cat);
$cart = cart_lines();
$tot  = cart_total();
$sum  = sale_summary($code);
$qs   = ($q !== '' ? 'q=' . rawurlencode($q) : '') . ($cat !== '' ? ($q !== '' ? '&' : '') . 'cat=' . rawurlencode($cat) : '');
$base = 'sale.php' . ($qs !== '' ? '?' . $qs : '');
?>
<div class="sale-wrap" id="sale-live">

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
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="ชื่อสินค้า หรือ SKU"
               hx-get="sale.php" hx-trigger="input changed delay:350ms, search"
               hx-include="closest form" hx-target="#sale-live" hx-swap="outerHTML"
               hx-sync="this:replace" hx-push-url="true" hx-preserve="true">
      </div>
      <?php if ($cat !== ''): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
      <button class="btn" type="submit">ค้นหา</button>
    </form>

    <div class="cats" hx-target="#sale-live" hx-swap="outerHTML" hx-push-url="true">
      <a class="cat<?= $cat === '' ? ' on' : '' ?>"
         href="sale.php<?= $q !== '' ? '?q=' . rawurlencode($q) : '' ?>"
         hx-get="sale.php<?= $q !== '' ? '?q=' . rawurlencode($q) : '' ?>">ทั้งหมด</a>
      <?php foreach (product_cats() as $c): ?>
        <?php $u = 'sale.php?cat=' . rawurlencode($c) . ($q !== '' ? '&q=' . rawurlencode($q) : ''); ?>
        <a class="cat<?= $cat === $c ? ' on' : '' ?>" href="<?= e($u) ?>" hx-get="<?= e($u) ?>"><?= e($c) ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (!$list): ?>
      <p class="empty">
        <svg class="ico"><use href="#i-search"/></svg>
        ไม่พบสินค้าที่ตรงกับคำค้น<br><small>ลองพิมพ์ชื่อสั้นลง หรือเลือกหมวด “ทั้งหมด”</small>
      </p>
    <?php else: ?>
      <div class="goods">
        <?php foreach ($list as $p): ?>
          <?php $inCart = isset($_SESSION['cart'][$p['sku']]) ? (int) $_SESSION['cart'][$p['sku']] : 0; ?>
          <form method="post" action="<?= e($base) ?>" class="g-form"
                hx-post="<?= e($base) ?>" hx-target="#sale-live" hx-swap="outerHTML">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="act" value="add">
            <input type="hidden" name="sku" value="<?= e($p['sku']) ?>">
            <button class="good<?= $p['qty'] <= 0 ? ' is-out' : '' ?><?= $inCart > 0 ? ' is-in' : '' ?>"
                    type="submit" <?= $p['qty'] <= 0 ? 'disabled' : '' ?>>
              <?php if ($inCart > 0): ?><i class="g-bdg"><?= $inCart ?></i><?php endif; ?>
              <?= thumb_html($p, 'thumb--lg') ?>
              <span class="g-cat"><?= e($p['cat']) ?></span>
              <span class="g-nm"><?= e($p['name']) ?></span>
              <span class="g-pr num"><?= money2($p['price']) ?><small>บาท/<?= e($p['unit']) ?></small></span>
              <span class="g-qty<?= $p['qty'] <= 0 ? ' out' : ($p['qty'] <= $p['reorder'] ? ' low' : '') ?>">
                <?= $p['qty'] <= 0 ? 'หมด' : 'เหลือ ' . number_format($p['qty']) . ' ' . e($p['unit']) ?>
              </span>
            </button>
          </form>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- ==================== ตะกร้า ==================== -->
  <aside class="sale-cart">
    <div class="card cart-card">
      <div class="card-head">
        <div>
          <h2>ตะกร้า</h2>
          <span class="sub"><?= count($cart) ?> รายการ · <?= number_format(cart_count()) ?> ชิ้น</span>
        </div>
        <?php if ($cart): ?>
          <form method="post" action="<?= e($base) ?>" hx-post="<?= e($base) ?>"
                hx-target="#sale-live" hx-swap="outerHTML">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="act" value="clear">
            <button class="icon-btn" type="submit" title="ล้างตะกร้า" aria-label="ล้างตะกร้า">
              <svg class="ico"><use href="#i-trash"/></svg>
            </button>
          </form>
        <?php endif; ?>
      </div>

      <?php if (!$cart): ?>
        <p class="empty empty-cart">
          <svg class="ico"><use href="#i-cart"/></svg>
          ยังไม่มีสินค้าในตะกร้า<br><small>แตะสินค้าทางซ้ายเพื่อเริ่มขาย</small>
        </p>
      <?php else: ?>

        <ul class="cart-list">
          <?php foreach ($cart as $l): ?>
            <li>
              <div class="cl-t">
                <b><?= e($l['name']) ?></b>
                <small class="num"><?= money2($l['price']) ?> × <?= $l['qty'] ?> <?= e($l['unit']) ?></small>
              </div>
              <div class="cl-q" hx-target="#sale-live" hx-swap="outerHTML">
                <form method="post" action="<?= e($base) ?>" hx-post="<?= e($base) ?>">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="act" value="minus">
                  <input type="hidden" name="sku" value="<?= e($l['sku']) ?>">
                  <button type="submit" aria-label="ลดจำนวน"><svg class="ico"><use href="#i-minus"/></svg></button>
                </form>

                <?php /* พิมพ์จำนวนแก้ได้เลยเมื่อกดผิด ไม่ต้องกดลดทีละครั้ง */ ?>
                <form method="post" action="<?= e($base) ?>" hx-post="<?= e($base) ?>" class="cl-set"
                      hx-trigger="submit, change">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="act" value="set">
                  <input type="hidden" name="sku" value="<?= e($l['sku']) ?>">
                  <input class="num" type="text" inputmode="numeric" name="qty" value="<?= $l['qty'] ?>"
                         aria-label="จำนวนของ <?= e($l['name']) ?>" autocomplete="off">
                  <noscript><button type="submit">ตกลง</button></noscript>
                </form>

                <form method="post" action="<?= e($base) ?>" hx-post="<?= e($base) ?>">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="act" value="add">
                  <input type="hidden" name="sku" value="<?= e($l['sku']) ?>">
                  <button type="submit" aria-label="เพิ่มจำนวน"><svg class="ico"><use href="#i-plus"/></svg></button>
                </form>
              </div>
              <div class="cl-s num"><?= money2($l['sum']) ?></div>
              <form class="cl-del" method="post" action="<?= e($base) ?>"
                    hx-post="<?= e($base) ?>" hx-target="#sale-live" hx-swap="outerHTML">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="act" value="del">
                <input type="hidden" name="sku" value="<?= e($l['sku']) ?>">
                <button type="submit" aria-label="เอา <?= e($l['name']) ?> ออกจากตะกร้า"
                        title="เอาออกจากตะกร้า"><svg class="ico"><use href="#i-x"/></svg></button>
              </form>
            </li>
          <?php endforeach; ?>
        </ul>

        <form method="post" action="<?= e($base) ?>" class="pay" data-confirm="pay" hx-confirm="ยืนยันการรับเงิน"
              hx-post="<?= e($base) ?>" hx-target="#sale-live" hx-swap="outerHTML">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="act" value="pay">

          <?php /* ยอดที่ต้องชำระแก้ได้ — ลดราคาให้ลูกค้า ระบบลงเป็น "ส่วนลด" ในบิลให้เอง (เพิ่มเกินราคาจริงไม่ได้) */ ?>
          <div class="pay-tot pay-net">
            <label for="net">ยอดที่ต้องชำระ<small>แก้ได้ถ้าลดราคาให้ลูกค้า</small></label>
            <input class="num" type="text" inputmode="decimal" id="net" name="net" autocomplete="off"
                   value="<?= e(rtrim(rtrim(number_format($tot, 2, '.', ''), '0'), '.')) ?>" data-full="<?= e($tot) ?>"
                   aria-label="ยอดที่ต้องชำระ">
            <b class="num" id="total" data-v="<?= (int) $tot ?>" hidden><?= money2($tot) ?></b>
          </div>
          <div class="pay-disc" id="pay-disc" hidden>
            <div><span>ราคาเต็ม</span><b class="num"><?= money2($tot) ?></b></div>
            <div class="d"><span>ส่วนลด</span><b class="num" id="disc">−0.00</b></div>
          </div>
          <p class="pay-err" id="net-err" hidden>ยอดชำระต้องมากกว่า 0 และไม่เกินราคาเต็ม <?= money2($tot) ?> บาท</p>

          <?php /* บิล VAT กับไม่ VAT ใช้เลขที่คนละชุด — พนักงานเลือกตามที่ลูกค้าต้องการ */ ?>
          <div class="pay-how pay-vat">
            <label class="on"><input type="radio" name="vat" value="0" checked> ไม่ VAT</label>
            <label><input type="radio" name="vat" value="1"> VAT</label>
          </div>

          <div class="pay-how" id="pay-how">
            <label class="on"><input type="radio" name="method" value="cash" checked> เงินสด</label>
            <label><input type="radio" name="method" value="transfer"> โอน / พร้อมเพย์</label>
          </div>

          <div id="cash-box">
            <span class="lbl lbl-mt">รับเงินมา</span>
            <div class="cash-in">
              <input class="input num" type="text" inputmode="numeric" id="received" name="received"
                     value="<?= (int) $tot ?>" autocomplete="off" aria-label="จำนวนเงินที่รับมา">
              <span class="unit">บาท</span>
            </div>
            <div class="amt-quick" id="recv-quick">
              <button type="button" data-set="<?= (int) $tot ?>">พอดี</button>
              <?php
              $seen = array((int) $tot);
              foreach (array(100, 500, 1000) as $step) {
                  $v = (int) (ceil($tot / $step) * $step);
                  if (in_array($v, $seen, true)) { continue; }
                  $seen[] = $v;
                  echo '<button type="button" data-set="' . $v . '">' . money($v) . '</button>';
              }
              ?>
            </div>
            <div class="kv1 kv-chg"><span>เงินทอน</span><b class="num" id="change">0.00</b></div>
          </div>

          <button class="btn-xl btn-pay" type="submit">
            <svg class="ico"><use href="#i-check"/></svg> รับเงินและบันทึกบิล
          </button>
        </form>
      <?php endif; ?>
    </div>

    <!-- ===== ยอดขายวันนี้ ===== -->
    <div class="card">
      <div class="card-head">
        <div><h2>ยอดขายวันนี้</h2><span class="sub"><?= e(branch_name($code)) ?></span></div>
      </div>
      <div class="mini num mini-flat">
        <div class="m"><div class="lb">บิล</div><div class="nm"><?= number_format($sum['bills']) ?></div><div class="sb">ใบ</div></div>
        <div class="m"><div class="lb">ชิ้น</div><div class="nm"><?= number_format($sum['qty']) ?></div><div class="sb">รวมทุกบิล</div></div>
        <div class="m"><div class="lb">ยอดขาย</div><div class="nm"><?= money($sum['total']) ?></div><div class="sb">บาท</div></div>
      </div>
      <?php if ($sum['bills'] > 0): ?>
        <div class="kv1"><span>เงินสด</span><b class="num"><?= money2($sum['cash']) ?></b></div>
        <div class="kv1"><span>โอน / พร้อมเพย์</span><b class="num"><?= money2($sum['transfer']) ?></b></div>
        <a class="btn btn-block" href="history.php?t=sale">
          <svg class="ico"><use href="#i-history"/></svg> ดูประวัติบิลวันนี้
        </a>
      <?php endif; ?>
    </div>
  </aside>

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
      if (rec && !rec.getAttribute('data-touched')) { rec.value = String(Math.ceil(tot)); }
      var q0 = document.querySelector('#recv-quick button');
      if (q0) { q0.setAttribute('data-set', String(Math.ceil(tot))); }
      paint();
    }
    if (net) {
      net.addEventListener('input', netSync);
      net.addEventListener('focus', function () { this.select(); });
    }
    if (rec) { rec.addEventListener('input', function () { this.setAttribute('data-touched', '1'); }); }

    function paint() {
      if (!rec || !chg) { return; }
      var v = Number(String(rec.value).replace(/[^0-9]/g, '')) || 0;
      var d = v - tot;
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

    /* ปุ่มเลือกบิล VAT / ไม่ VAT */
    var vw = document.querySelector('.pay-vat');
    if (vw) {
      var vl = vw.querySelectorAll('label');
      var vsync = function () {
        for (var v = 0; v < vl.length; v++) {
          vl[v].className = vl[v].querySelector('input').checked ? 'on' : '';
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
</div>
