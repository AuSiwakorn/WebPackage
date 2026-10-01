<?php
/* ==========================================================
   AOSTOCK DEMO — ส่วนที่ htmx สลับได้ของหน้านำเข้าสินค้า
   ต้องกำหนดก่อน include: $user $code $q $cat
   ----------------------------------------------------------
   ปุ่มในนี้ใช้ hx-post บน <button> ตรง ๆ ไม่ห่อด้วย <form>
   เพราะทั้งหน้าอยู่ในฟอร์มบันทึกอยู่แล้ว ฟอร์มซ้อนฟอร์มไม่ได้
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$list  = sale_products($code, $q, $cat);
$lines = rdraft_lines($code);
$sum   = rdraft_count();
$qs    = ($q !== '' ? 'q=' . rawurlencode($q) : '') . ($cat !== '' ? ($q !== '' ? '&' : '') . 'cat=' . rawurlencode($cat) : '');
$base  = 'receive.php' . ($qs !== '' ? '?' . $qs : '');
$csrf  = csrf_token();
?>
<div class="sale-wrap" id="recv-live">

  <!-- ==================== เลือกสินค้าเข้าใบ ==================== -->
  <section class="card sale-pick">
    <div class="card-head">
      <div>
        <h2>เลือกสินค้าที่รับเข้า</h2>
        <span class="sub">ค้นหาแล้วแตะเพื่อเพิ่มเข้าใบ · <?= count($list) ?> รายการที่ตรงเงื่อนไข</span>
      </div>
    </div>

    <div class="sale-pick-body">
      <div class="sale-find">
        <div class="find-in">
          <svg class="ico"><use href="#i-search"/></svg>
          <label class="sr-only" for="rq">ค้นหาสินค้า</label>
          <input type="search" id="rq" name="q" value="<?= e($q) ?>" placeholder="ชื่อสินค้า หรือ SKU"
                 hx-get="receive.php" hx-trigger="input changed delay:350ms, search"
                 hx-vals='<?= e(json_encode(array('cat' => $cat))) ?>'
                 hx-target="#recv-live" hx-swap="outerHTML"
                 hx-sync="this:replace" hx-push-url="true" hx-preserve="true">
        </div>
      </div>

      <div class="cats" hx-target="#recv-live" hx-swap="outerHTML" hx-push-url="true">
        <?php $u = 'receive.php' . ($q !== '' ? '?q=' . rawurlencode($q) : ''); ?>
        <a class="cat<?= $cat === '' ? ' on' : '' ?>" href="<?= e($u) ?>" hx-get="<?= e($u) ?>">ทั้งหมด</a>
        <?php foreach (product_cats() as $c): ?>
          <?php $u = 'receive.php?cat=' . rawurlencode($c) . ($q !== '' ? '&q=' . rawurlencode($q) : ''); ?>
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
            <?php $inDoc = isset($_SESSION['recv_draft'][$p['sku']]) ? (int) $_SESSION['recv_draft'][$p['sku']] : 0; ?>
            <button class="good<?= $inDoc > 0 ? ' is-in' : '' ?>" type="button"
                    hx-post="<?= e($base) ?>" hx-target="#recv-live" hx-swap="outerHTML"
                    hx-vals='<?= e(json_encode(array('csrf' => $csrf, 'act' => 'add', 'sku' => $p['sku']))) ?>'>
              <?php if ($inDoc > 0): ?><i class="g-bdg"><?= $inDoc ?></i><?php endif; ?>
              <?= thumb_html($p, 'thumb--lg') ?>
              <span class="g-cat"><?= e($p['cat']) ?></span>
              <span class="g-nm"><?= e($p['name']) ?></span>
              <span class="g-qty">คงเหลือ <?= number_format($p['qty']) ?> <?= e($p['unit']) ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ==================== ใบรับของที่กำลังทำ ==================== -->
  <aside class="sale-cart">
    <div class="card cart-card">
      <div class="card-head">
        <div>
          <h2>รายการในใบนี้</h2>
          <span class="sub"><?= count($lines) ?> รายการ · <?= number_format($sum) ?> ชิ้น</span>
        </div>
        <?php if ($lines): ?>
          <button class="icon-btn" type="button" title="ล้างทั้งใบ" aria-label="ล้างทั้งใบ"
                  hx-post="<?= e($base) ?>" hx-target="#recv-live" hx-swap="outerHTML"
                  hx-vals='<?= e(json_encode(array('csrf' => $csrf, 'act' => 'clear'))) ?>'>
            <svg class="ico"><use href="#i-trash"/></svg>
          </button>
        <?php endif; ?>
      </div>

      <?php if (!$lines): ?>
        <p class="empty empty-cart">
          <svg class="ico"><use href="#i-in"/></svg>
          ยังไม่ได้เลือกสินค้า<br><small>แตะสินค้าทางซ้ายเพื่อเพิ่มเข้าใบ</small>
        </p>
      <?php else: ?>
        <ul class="cart-list recv-list">
          <?php foreach ($lines as $l): ?>
            <li>
              <div class="cl-t">
                <b><?= e($l['name']) ?></b>
                <small>คงเหลือ <?= number_format($l['have']) ?> →
                       <span class="up"><?= number_format($l['after']) ?></span> <?= e($l['unit']) ?></small>
              </div>
              <div class="cl-q">
                <button type="button" aria-label="ลดจำนวน"
                        hx-post="<?= e($base) ?>" hx-target="#recv-live" hx-swap="outerHTML"
                        hx-vals='<?= e(json_encode(array('csrf' => $csrf, 'act' => 'minus', 'sku' => $l['sku']))) ?>'>
                  <svg class="ico"><use href="#i-minus"/></svg>
                </button>
                <?php /* ชื่อต้องไม่ซ้ำกัน เพราะ htmx ส่งค่าทั้งฟอร์มไปด้วย
                         ถ้าทุกแถวชื่อ qty เหมือนกัน PHP จะอ่านได้แค่แถวสุดท้าย */ ?>
                <input class="num rq-set" type="text" inputmode="numeric" value="<?= $l['qty'] ?>"
                       autocomplete="off" aria-label="จำนวนที่รับเข้าของ <?= e($l['name']) ?>"
                       name="rq_<?= e($l['sku']) ?>"
                       hx-post="<?= e($base) ?>" hx-trigger="change" hx-target="#recv-live" hx-swap="outerHTML"
                       hx-vals='<?= e(json_encode(array('csrf' => $csrf, 'act' => 'set', 'sku' => $l['sku']))) ?>'>
                <button type="button" aria-label="เพิ่มจำนวน"
                        hx-post="<?= e($base) ?>" hx-target="#recv-live" hx-swap="outerHTML"
                        hx-vals='<?= e(json_encode(array('csrf' => $csrf, 'act' => 'add', 'sku' => $l['sku']))) ?>'>
                  <svg class="ico"><use href="#i-plus"/></svg>
                </button>
              </div>
              <div class="cl-s"><?= e($l['unit']) ?></div>
              <button class="cl-x" type="button" title="เอาออกจากใบ"
                      aria-label="เอา <?= e($l['name']) ?> ออกจากใบ"
                      hx-post="<?= e($base) ?>" hx-target="#recv-live" hx-swap="outerHTML"
                      hx-vals='<?= e(json_encode(array('csrf' => $csrf, 'act' => 'del', 'sku' => $l['sku']))) ?>'>
                <svg class="ico"><use href="#i-x"/></svg>
              </button>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="pay recv-total">
          <div class="pay-tot">
            <span>รวมที่รับเข้า</span>
            <b class="num" id="recv-sum" data-v="<?= (int) $sum ?>"><?= number_format($sum) ?><small>ชิ้น</small></b>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </aside>

  <script>
  /* แจ้งหน้าหลักว่าเนื้อหาเปลี่ยนแล้ว ให้คำนวณผลต่างกับเอกสารใหม่ */
  if (window.recvSync) { window.recvSync(); }
  </script>
</div>
