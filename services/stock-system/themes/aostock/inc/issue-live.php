<?php
/**
 * FILE: themes/aostock/inc/issue-live.php
 * ROLE: ส่วนที่ htmx สลับได้ของหน้าเบิก / ตัดออก
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_product / ao_stock_balance (ผ่าน api.php) · ใบที่กำลังทำ (ร่าง) อยู่ใน $_SESSION['issue_draft']
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ใบที่บันทึกแล้วอยู่ในตาราง (ช่วงที่ 6) — ไฟล์นี้แสดงเฉพาะร่าง
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ส่วนที่ htmx สลับได้ของหน้าเบิก / ตัดออก
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
        <span class="sub">ค้นหาแล้วแตะเพื่อเพิ่มเข้าใบ · <?php echo count($list) ?> รายการที่ตรงเงื่อนไข</span>
      </div>
    </div>

    <div class="sale-pick-body">
      <div class="sale-find">
        <div class="find-in">
          <svg class="ico"><use href="#i-search"/></svg>
          <label class="sr-only" for="iq">ค้นหาสินค้า</label>
          <input type="search" id="iq" name="q" value="<?php echo e($q) ?>" placeholder="ชื่อสินค้า หรือ SKU"
                 hx-get="issue.php" hx-trigger="input changed delay:350ms, search"
                 hx-vals='<?php echo e(json_encode(array('cat' => $cat))) ?>'
                 hx-target="#issue-live" hx-swap="outerHTML"
                 hx-sync="this:replace" hx-push-url="true" hx-preserve="true">
        </div>
      </div>

      <div class="cats" hx-target="#issue-live" hx-swap="outerHTML" hx-push-url="true">
        <?php $u = 'issue.php' . ($q !== '' ? '?q=' . rawurlencode($q) : ''); ?>
        <a class="cat<?php echo $cat === '' ? ' on' : '' ?>" href="<?php echo e($u) ?>" hx-get="<?php echo e($u) ?>">ทั้งหมด</a>
        <?php foreach (product_cats() as $c) { ?>
          <?php $u = 'issue.php?cat=' . rawurlencode($c) . ($q !== '' ? '&q=' . rawurlencode($q) : ''); ?>
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
          <?php foreach ($list as $p) { ?>
            <?php
            $inDoc = isset($_SESSION['issue_draft'][$p['sku']]) ? (int) $_SESSION['issue_draft'][$p['sku']] : 0;
            $left  = $p['qty'] - $inDoc;              // ที่ยังเพิ่มเข้าใบได้อีก
            $off   = ($left <= 0);
            ?>
            <button class="good<?php echo $p['qty'] <= 0 ? ' is-out' : '' ?><?php echo $inDoc > 0 ? ' is-in is-cut' : '' ?>" type="button"
                    <?php echo $off ? 'disabled' : '' ?>
                    hx-post="<?php echo e($base) ?>" hx-target="#issue-live" hx-swap="outerHTML"
                    hx-vals='<?php echo e(json_encode(array('csrf' => $csrf, 'act' => 'add', 'sku' => $p['sku']))) ?>'>
              <?php if ($inDoc > 0) { ?><i class="g-bdg"><?php echo $inDoc ?></i><?php } ?>
              <?php echo thumb_html($p, 'thumb--lg') ?>
              <span class="g-cat"><?php echo e($p['cat']) ?></span>
              <span class="g-nm"><?php echo e($p['name']) ?></span>
              <span class="g-qty<?php echo $p['qty'] <= 0 ? ' out' : ($p['qty'] <= $p['reorder'] ? ' low' : '') ?>">
                <?php if ($p['qty'] <= 0) { ?>
                  หมด
                <?php } elseif ($inDoc > 0 && $left <= 0) { ?>
                  ใส่ครบทั้งหมดแล้ว
                <?php } else { ?>
                  คงเหลือ <?php echo number_format($p['qty']) ?> <?php echo e($p['unit']) ?>
                <?php } ?>
              </span>
            </button>
          <?php } ?>
        </div>
      <?php } ?>
    </div>
  </section>

  <!-- ==================== ใบตัดออกที่กำลังทำ ==================== -->
  <aside class="sale-cart">
    <div class="card cart-card">
      <div class="card-head">
        <div>
          <h2>รายการในใบนี้</h2>
          <span class="sub"><?php echo count($lines) ?> รายการ · <?php echo number_format($sum) ?> ชิ้น</span>
        </div>
        <?php if ($lines) { ?>
          <button class="icon-btn" type="button" title="ล้างทั้งใบ" aria-label="ล้างทั้งใบ"
                  hx-post="<?php echo e($base) ?>" hx-target="#issue-live" hx-swap="outerHTML"
                  hx-vals='<?php echo e(json_encode(array('csrf' => $csrf, 'act' => 'clear'))) ?>'>
            <svg class="ico"><use href="#i-trash"/></svg>
          </button>
        <?php } ?>
      </div>

      <?php if ($flash !== '') { ?>
        <p class="cart-flash" role="status">
          <svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($flash) ?></span>
        </p>
      <?php } ?>

      <?php if (!$lines) { ?>
        <p class="empty empty-cart">
          <svg class="ico"><use href="#i-out"/></svg>
          ยังไม่ได้เลือกสินค้า<br><small>แตะสินค้าทางซ้ายเพื่อเพิ่มเข้าใบ</small>
        </p>
      <?php } else { ?>
        <ul class="cart-list recv-list issue-list">
          <?php foreach ($lines as $l) { ?>
            <li>
              <div class="cl-t">
                <b><?php echo e($l['name']) ?></b>
                <small>คงเหลือ <?php echo number_format($l['have']) ?> →
                       <span class="dn<?php echo $l['after'] <= 0 ? ' zero' : '' ?>"><?php echo number_format($l['after']) ?></span>
                       <?php echo e($l['unit']) ?></small>
              </div>
              <div class="cl-q">
                <button type="button" aria-label="ลดจำนวน"
                        hx-post="<?php echo e($base) ?>" hx-target="#issue-live" hx-swap="outerHTML"
                        hx-vals='<?php echo e(json_encode(array('csrf' => $csrf, 'act' => 'minus', 'sku' => $l['sku']))) ?>'>
                  <svg class="ico"><use href="#i-minus"/></svg>
                </button>
                <?php /* ชื่อไม่ซ้ำกัน เพราะ htmx ส่งค่าทั้งฟอร์มไปด้วย */ ?>
                <input class="num rq-set" type="text" inputmode="numeric" value="<?php echo $l['qty'] ?>"
                       autocomplete="off" aria-label="จำนวนที่ตัดออกของ <?php echo e($l['name']) ?>"
                       name="iq_<?php echo e($l['sku']) ?>"
                       hx-post="<?php echo e($base) ?>" hx-trigger="change" hx-target="#issue-live" hx-swap="outerHTML"
                       hx-vals='<?php echo e(json_encode(array('csrf' => $csrf, 'act' => 'set', 'sku' => $l['sku']))) ?>'>
                <button type="button" aria-label="เพิ่มจำนวน" <?php echo $l['after'] <= 0 ? 'disabled' : '' ?>
                        hx-post="<?php echo e($base) ?>" hx-target="#issue-live" hx-swap="outerHTML"
                        hx-vals='<?php echo e(json_encode(array('csrf' => $csrf, 'act' => 'add', 'sku' => $l['sku']))) ?>'>
                  <svg class="ico"><use href="#i-plus"/></svg>
                </button>
              </div>
              <div class="cl-s"><?php echo e($l['unit']) ?></div>
              <button class="cl-x" type="button" title="เอาออกจากใบ"
                      aria-label="เอา <?php echo e($l['name']) ?> ออกจากใบ"
                      hx-post="<?php echo e($base) ?>" hx-target="#issue-live" hx-swap="outerHTML"
                      hx-vals='<?php echo e(json_encode(array('csrf' => $csrf, 'act' => 'del', 'sku' => $l['sku']))) ?>'>
                <svg class="ico"><use href="#i-x"/></svg>
              </button>
            </li>
          <?php } ?>
        </ul>

        <div class="pay recv-total issue-total">
          <div class="pay-row">
            <span>มูลค่าต้นทุนที่ตัดออก</span>
            <b class="num" id="issue-cost"><?php echo money2($cost) ?> บาท</b>
          </div>
          <div class="pay-tot">
            <span>รวมที่ตัดออก</span>
            <b class="num" id="issue-sum" data-v="<?php echo (int) $sum ?>"><?php echo number_format($sum) ?><small>ชิ้น</small></b>
          </div>
        </div>
      <?php } ?>
    </div>
  </aside>

  <script>
  if (window.issueSync) { window.issueSync(); }
  </script>
</div>
