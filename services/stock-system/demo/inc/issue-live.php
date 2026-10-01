<?php
/* ==========================================================
   AOSTOCK DEMO — ส่วนที่ htmx สลับได้ของหน้าเบิก / ตัดออก
   ต้องกำหนดก่อน include: $user $code $q $cat
   ปุ่มใช้ hx-post บน <button> ตรง ๆ เพราะทั้งหน้าอยู่ในฟอร์มบันทึกอยู่แล้ว
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$list  = sale_products($code, $q, $cat);
$lines = idraft_lines($code);
$sum   = idraft_count();
$cost  = idraft_cost($lines);
$flash = idraft_flash();
$qs    = ($q !== '' ? 'q=' . rawurlencode($q) : '') . ($cat !== '' ? ($q !== '' ? '&' : '') . 'cat=' . rawurlencode($cat) : '');
$base  = 'issue.php' . ($qs !== '' ? '?' . $qs : '');
$csrf  = csrf_token();
?>
<div class="sale-wrap" id="issue-live">

  <!-- ==================== เลือกสินค้าที่จะตัดออก ==================== -->
  <section class="card sale-pick">
    <div class="card-head">
      <div>
        <h2>เลือกสินค้าที่จะตัดออก</h2>
        <span class="sub">ค้นหาแล้วแตะเพื่อเพิ่มเข้าใบ · <?= count($list) ?> รายการที่ตรงเงื่อนไข</span>
      </div>
    </div>

    <div class="sale-pick-body">
      <div class="sale-find">
        <div class="find-in">
          <svg class="ico"><use href="#i-search"/></svg>
          <label class="sr-only" for="iq">ค้นหาสินค้า</label>
          <input type="search" id="iq" name="q" value="<?= e($q) ?>" placeholder="ชื่อสินค้า หรือ SKU"
                 hx-get="issue.php" hx-trigger="input changed delay:350ms, search"
                 hx-vals='<?= e(json_encode(array('cat' => $cat))) ?>'
                 hx-target="#issue-live" hx-swap="outerHTML"
                 hx-sync="this:replace" hx-push-url="true" hx-preserve="true">
        </div>
      </div>

      <div class="cats" hx-target="#issue-live" hx-swap="outerHTML" hx-push-url="true">
        <?php $u = 'issue.php' . ($q !== '' ? '?q=' . rawurlencode($q) : ''); ?>
        <a class="cat<?= $cat === '' ? ' on' : '' ?>" href="<?= e($u) ?>" hx-get="<?= e($u) ?>">ทั้งหมด</a>
        <?php foreach (product_cats() as $c): ?>
          <?php $u = 'issue.php?cat=' . rawurlencode($c) . ($q !== '' ? '&q=' . rawurlencode($q) : ''); ?>
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
            <?php
            $inDoc = isset($_SESSION['issue_draft'][$p['sku']]) ? (int) $_SESSION['issue_draft'][$p['sku']] : 0;
            $left  = $p['qty'] - $inDoc;              // ที่ยังเพิ่มเข้าใบได้อีก
            $off   = ($left <= 0);
            ?>
            <button class="good<?= $p['qty'] <= 0 ? ' is-out' : '' ?><?= $inDoc > 0 ? ' is-in is-cut' : '' ?>" type="button"
                    <?= $off ? 'disabled' : '' ?>
                    hx-post="<?= e($base) ?>" hx-target="#issue-live" hx-swap="outerHTML"
                    hx-vals='<?= e(json_encode(array('csrf' => $csrf, 'act' => 'add', 'sku' => $p['sku']))) ?>'>
              <?php if ($inDoc > 0): ?><i class="g-bdg"><?= $inDoc ?></i><?php endif; ?>
              <?= thumb_html($p, 'thumb--lg') ?>
              <span class="g-cat"><?= e($p['cat']) ?></span>
              <span class="g-nm"><?= e($p['name']) ?></span>
              <span class="g-qty<?= $p['qty'] <= 0 ? ' out' : ($p['qty'] <= $p['reorder'] ? ' low' : '') ?>">
                <?php if ($p['qty'] <= 0): ?>
                  หมด
                <?php elseif ($inDoc > 0 && $left <= 0): ?>
                  ใส่ครบทั้งหมดแล้ว
                <?php else: ?>
                  คงเหลือ <?= number_format($p['qty']) ?> <?= e($p['unit']) ?>
                <?php endif; ?>
              </span>
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- ==================== ใบตัดออกที่กำลังทำ ==================== -->
  <aside class="sale-cart">
    <div class="card cart-card">
      <div class="card-head">
        <div>
          <h2>รายการในใบนี้</h2>
          <span class="sub"><?= count($lines) ?> รายการ · <?= number_format($sum) ?> ชิ้น</span>
        </div>
        <?php if ($lines): ?>
          <button class="icon-btn" type="button" title="ล้างทั้งใบ" aria-label="ล้างทั้งใบ"
                  hx-post="<?= e($base) ?>" hx-target="#issue-live" hx-swap="outerHTML"
                  hx-vals='<?= e(json_encode(array('csrf' => $csrf, 'act' => 'clear'))) ?>'>
            <svg class="ico"><use href="#i-trash"/></svg>
          </button>
        <?php endif; ?>
      </div>

      <?php if ($flash !== ''): ?>
        <p class="cart-flash" role="status">
          <svg class="ico"><use href="#i-alert"/></svg><span><?= e($flash) ?></span>
        </p>
      <?php endif; ?>

      <?php if (!$lines): ?>
        <p class="empty empty-cart">
          <svg class="ico"><use href="#i-out"/></svg>
          ยังไม่ได้เลือกสินค้า<br><small>แตะสินค้าทางซ้ายเพื่อเพิ่มเข้าใบ</small>
        </p>
      <?php else: ?>
        <ul class="cart-list recv-list issue-list">
          <?php foreach ($lines as $l): ?>
            <li>
              <div class="cl-t">
                <b><?= e($l['name']) ?></b>
                <small>คงเหลือ <?= number_format($l['have']) ?> →
                       <span class="dn<?= $l['after'] <= 0 ? ' zero' : '' ?>"><?= number_format($l['after']) ?></span>
                       <?= e($l['unit']) ?></small>
              </div>
              <div class="cl-q">
                <button type="button" aria-label="ลดจำนวน"
                        hx-post="<?= e($base) ?>" hx-target="#issue-live" hx-swap="outerHTML"
                        hx-vals='<?= e(json_encode(array('csrf' => $csrf, 'act' => 'minus', 'sku' => $l['sku']))) ?>'>
                  <svg class="ico"><use href="#i-minus"/></svg>
                </button>
                <?php /* ชื่อไม่ซ้ำกัน เพราะ htmx ส่งค่าทั้งฟอร์มไปด้วย */ ?>
                <input class="num rq-set" type="text" inputmode="numeric" value="<?= $l['qty'] ?>"
                       autocomplete="off" aria-label="จำนวนที่ตัดออกของ <?= e($l['name']) ?>"
                       name="iq_<?= e($l['sku']) ?>"
                       hx-post="<?= e($base) ?>" hx-trigger="change" hx-target="#issue-live" hx-swap="outerHTML"
                       hx-vals='<?= e(json_encode(array('csrf' => $csrf, 'act' => 'set', 'sku' => $l['sku']))) ?>'>
                <button type="button" aria-label="เพิ่มจำนวน" <?= $l['after'] <= 0 ? 'disabled' : '' ?>
                        hx-post="<?= e($base) ?>" hx-target="#issue-live" hx-swap="outerHTML"
                        hx-vals='<?= e(json_encode(array('csrf' => $csrf, 'act' => 'add', 'sku' => $l['sku']))) ?>'>
                  <svg class="ico"><use href="#i-plus"/></svg>
                </button>
              </div>
              <div class="cl-s"><?= e($l['unit']) ?></div>
              <button class="cl-x" type="button" title="เอาออกจากใบ"
                      aria-label="เอา <?= e($l['name']) ?> ออกจากใบ"
                      hx-post="<?= e($base) ?>" hx-target="#issue-live" hx-swap="outerHTML"
                      hx-vals='<?= e(json_encode(array('csrf' => $csrf, 'act' => 'del', 'sku' => $l['sku']))) ?>'>
                <svg class="ico"><use href="#i-x"/></svg>
              </button>
            </li>
          <?php endforeach; ?>
        </ul>

        <div class="pay recv-total issue-total">
          <div class="pay-row">
            <span>มูลค่าต้นทุนที่ตัดออก</span>
            <b class="num" id="issue-cost"><?= money2($cost) ?> บาท</b>
          </div>
          <div class="pay-tot">
            <span>รวมที่ตัดออก</span>
            <b class="num" id="issue-sum" data-v="<?= (int) $sum ?>"><?= number_format($sum) ?><small>ชิ้น</small></b>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </aside>

  <script>
  if (window.issueSync) { window.issueSync(); }
  </script>
</div>
