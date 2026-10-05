<?php
/**
 * FILE: themes/aostock/inc/adjust-live.php
 * ROLE: ส่วนที่ htmx สลับได้ของหน้าตรวจนับ / ปรับยอด
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_count_item (นับแล้วในรอบนี้) · ao_stock_balance (ยอด) — ผ่าน api.php · ใบที่กำลังนับ (ร่าง) อยู่ใน $_SESSION['adj_draft']
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] สถานะ "นับแล้ว" อ่านจากตาราง (ช่วงที่ 6)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ส่วนที่ htmx สลับได้ของหน้าตรวจนับ / ปรับยอด
   ต้องกำหนดก่อน include: $user $code $q $cat
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$tabs  = count_tabs();
$tab   = (isset($tab) && isset($tabs[$tab])) ? $tab : 'todo';
$round = count_round($code);
$stAll = count_status_all($code);

/* นับจำนวนของแต่ละแท็บ ตามคำค้นและหมวดที่เลือกอยู่ */
$found = sale_products($code, $q, $cat);
$tabN  = array('todo' => 0, 'done' => 0, 'all' => count($found));
$list  = array();
foreach ($found as $p) {
    $isDone = isset($stAll[$p['sku']]);
    $tabN[$isDone ? 'done' : 'todo']++;
    if ($tab === 'all' || ($tab === 'done') === $isDone) {
        $list[] = $p;
    }
}

/* ความคืบหน้าของรอบ (ทั้งสาขา ไม่สนตัวกรอง) */
$nAll  = count(products_list());
$nDone = count($stAll);
$pct   = $nAll > 0 ? (int) floor($nDone * 100 / $nAll) : 0;

$lines = adraft_lines($code);
$limit = adj_limit($code);                 // null = ไม่จำกัด (ร้านยังไม่เปิด / ปิดแล้ว)
$full  = adj_full($code);
$sum   = adj_sum($lines);
$net   = $sum['plus'] - $sum['minus'];
$qs    = adj_qs($q, $cat, $tab);
$base  = 'stocktake.php' . $qs;
$csrf  = csrf_token();
$valT  = ($sum['value'] < 0 ? '−' : ($sum['value'] > 0 ? '+' : '')) . money2(abs($sum['value'])) . ' บาท';
?>
<div class="sale-wrap" id="adj-live">

  <!-- ==================== เลือกสินค้าที่จะนับ ==================== -->
  <section class="card sale-pick">
    <div class="card-head">
      <div>
        <h2>เลือกสินค้าที่นับ</h2>
        <span class="sub">แตะสินค้าเพื่อเพิ่มเข้าใบ แล้วกรอกจำนวนที่นับได้จริงทางขวา</span>
      </div>
    </div>

    <?php /* รอบการนับของสาขา — วันเริ่มรอบผู้ดูแลตั้งที่หน้า จัดการสาขา */ ?>
    <div class="round<?php echo $nDone >= $nAll ? ' is-done' : ($round['left'] <= 3 ? ' is-due' : '') ?>">
      <div class="round-t">
        <b>รอบนี้นับแล้ว <span class="num"><?php echo number_format($nDone) ?></span> / <span class="num"><?php echo number_format($nAll) ?></span> รายการ</b>
        <small>
          รอบ <?php echo e(thai_day_month($round['start'])) ?> – <?php echo e(thai_day_month($round['end'])) ?> ·
          <?php if ($nDone >= $nAll) { ?>
            นับครบแล้ว
          <?php } else { ?>
            ต้องนับให้ครบภายใน <?php echo e(thai_day_month($round['end'])) ?> (อีก <?php echo (int) $round['left'] ?> วัน)
          <?php } ?>
        </small>
      </div>
      <div class="round-bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo $pct ?>">
        <i style="width:<?php echo $pct ?>%"></i>
      </div>
    </div>

    <div class="sale-pick-body">
      <div class="sale-find">
        <div class="find-in">
          <svg class="ico"><use href="#i-search"/></svg>
          <label class="sr-only" for="aq">ค้นหาสินค้า</label>
          <input type="search" id="aq" name="q" value="<?php echo e($q) ?>" placeholder="ชื่อสินค้า หรือ SKU"
                 hx-get="stocktake.php" hx-trigger="input changed delay:350ms, search"
                 hx-vals='<?php echo e(json_encode(array('cat' => $cat, 't' => $tab))) ?>'
                 hx-target="#adj-live" hx-swap="outerHTML"
                 hx-sync="this:replace" hx-push-url="true" hx-preserve="true">
        </div>
      </div>

      <?php if ($limit !== null) { ?>
        <p class="open-note<?php echo $full ? ' is-full' : '' ?>">
          <svg class="ico"><use href="#i-store"/></svg>
          <span>
            <?php if ($full) { ?>
              <b>บันทึกรายการนี้ก่อน</b> แล้วค่อยนับตัวถัดไป — ร้านเปิดอยู่ นับได้ครั้งละ <?php echo (int) $limit ?> รายการ
            <?php } else { ?>
              ร้านเปิดอยู่ · <b>นับได้ครั้งละ <?php echo (int) $limit ?> รายการ</b> นับเสร็จแล้วบันทึกทันที ตัวเลขจะไม่คลาดจากการขาย
            <?php } ?>
          </span>
        </p>
      <?php } ?>

      <div class="segs count-tabs" role="tablist" hx-target="#adj-live" hx-swap="outerHTML" hx-push-url="true">
        <?php foreach ($tabs as $k => $label) { ?>
          <?php $u = 'stocktake.php' . adj_qs($q, $cat, $k); ?>
          <a class="seg<?php echo $tab === $k ? ' on' : '' ?>" role="tab" aria-selected="<?php echo $tab === $k ? 'true' : 'false' ?>"
             href="<?php echo e($u) ?>" hx-get="<?php echo e($u) ?>">
            <?php echo e($label) ?> <i class="num"><?php echo $tabN[$k] ?></i>
          </a>
        <?php } ?>
      </div>

      <div class="cats" hx-target="#adj-live" hx-swap="outerHTML" hx-push-url="true">
        <?php $u = 'stocktake.php' . adj_qs($q, '', $tab); ?>
        <a class="cat<?php echo $cat === '' ? ' on' : '' ?>" href="<?php echo e($u) ?>" hx-get="<?php echo e($u) ?>">ทุกหมวด</a>
        <?php foreach (product_cats() as $c) { ?>
          <?php $u = 'stocktake.php' . adj_qs($q, $c, $tab); ?>
          <a class="cat<?php echo $cat === $c ? ' on' : '' ?>" href="<?php echo e($u) ?>" hx-get="<?php echo e($u) ?>"><?php echo e($c) ?></a>
        <?php } ?>
      </div>

      <?php if (!$list && $tab === 'todo' && $tabN['all'] > 0) { ?>
        <p class="empty empty-ok">
          <svg class="ico"><use href="#i-check"/></svg>
          นับครบทุกรายการในกลุ่มนี้แล้ว<br><small>ดูผลได้ที่แท็บ “นับแล้ว” หรือเลือกหมวดอื่น</small>
        </p>
      <?php } elseif (!$list && $tab === 'done') { ?>
        <p class="empty">
          <svg class="ico"><use href="#i-clipboard"/></svg>
          รอบนี้ยังไม่ได้นับรายการในกลุ่มนี้<br><small>เริ่มนับได้จากแท็บ “ยังไม่นับ”</small>
        </p>
      <?php } elseif (!$list) { ?>
        <p class="empty">
          <svg class="ico"><use href="#i-search"/></svg>
          ไม่พบสินค้าที่ตรงกับคำค้น<br><small>ลองพิมพ์ชื่อสั้นลง หรือเลือกหมวด “ทุกหมวด”</small>
        </p>
      <?php } else { ?>
        <div class="goods">
          <?php foreach ($list as $p) { ?>
            <?php
            $inDoc = isset($_SESSION['adj_draft'][$p['sku']]);
            $lock  = (!$inDoc && $full);        // ร้านเปิด ใบเต็มแล้ว → ต้องบันทึกใบนี้ก่อน
            ?>
            <button class="good<?php echo $inDoc ? ' is-in is-count' : '' ?><?php echo $lock ? ' is-lock' : '' ?>" type="button"
                    <?php echo ($inDoc || $lock) ? 'disabled' : '' ?>
                    hx-post="<?php echo e($base) ?>" hx-target="#adj-live" hx-swap="outerHTML"
                    hx-vals='<?php echo e(json_encode(array('csrf' => $csrf, 'act' => 'pick', 'sku' => $p['sku']))) ?>'>
              <?php if ($inDoc) { ?><i class="g-bdg"><svg class="ico"><use href="#i-check"/></svg></i><?php } ?>
              <?php echo thumb_html($p, 'thumb--lg') ?>
              <span class="g-cat"><?php echo e($p['cat']) ?></span>
              <span class="g-nm"><?php echo e($p['name']) ?></span>
              <span class="g-qty"><?php echo $inDoc ? 'อยู่ในใบแล้ว' : 'ในระบบ ' . number_format($p['qty']) . ' ' . e($p['unit']) ?></span>
              <?php if (isset($stAll[$p['sku']])) { ?>
                <?php $cn = count_note($stAll[$p['sku']]); ?>
                <span class="g-cnt"><?php echo e($cn['when']) ?> · <span class="dchip <?php echo e($cn['tone']) ?>"><?php echo e($cn['res']) ?></span></span>
              <?php } ?>
            </button>
          <?php } ?>
        </div>
      <?php } ?>
    </div>
  </section>

  <!-- ==================== ผลการนับ ==================== -->
  <aside class="sale-cart">
    <div class="card cart-card">
      <div class="card-head">
        <div>
          <h2>ผลการนับ</h2>
          <span class="sub"><?php echo $sum['items'] ?> รายการ · ตรง <?php echo $sum['same'] ?> · เกิน <?php echo $sum['over'] ?> · ขาด <?php echo $sum['short'] ?></span>
        </div>
        <?php if ($lines) { ?>
          <button class="icon-btn" type="button" title="ล้างทั้งใบ" aria-label="ล้างทั้งใบ"
                  hx-post="<?php echo e($base) ?>" hx-target="#adj-live" hx-swap="outerHTML"
                  hx-vals='<?php echo e(json_encode(array('csrf' => $csrf, 'act' => 'clear'))) ?>'>
            <svg class="ico"><use href="#i-trash"/></svg>
          </button>
        <?php } ?>
      </div>

      <?php if (!$lines) { ?>
        <p class="empty empty-cart">
          <svg class="ico"><use href="#i-clipboard"/></svg>
          ยังไม่ได้เลือกสินค้า<br><small>แตะสินค้าทางซ้าย ระบบจะใส่ยอดในระบบไว้ให้ก่อน<br>แล้วแก้เป็นจำนวนที่นับได้จริง</small>
        </p>
      <?php } else { ?>
        <ul class="cart-list recv-list adj-list">
          <?php foreach ($lines as $l) { ?>
            <li class="<?php echo $l['diff'] === 0 ? 'is-eq' : 'is-diff' ?>" data-have="<?php echo (int) $l['have'] ?>" data-diff="<?php echo (int) $l['diff'] ?>">
              <div class="cl-t">
                <b><?php echo e($l['name']) ?></b>
                <small>ในระบบ <?php echo number_format($l['have']) ?> <?php echo e($l['unit']) ?> · <?php echo diff_chip($l['diff']) ?></small>
                <?php if (isset($_SESSION['adj_snap'][$l['sku']]) && (int) $_SESSION['adj_snap'][$l['sku']] !== $l['have']) { ?>
                  <small class="moved">
                    <svg class="ico"><use href="#i-alert"/></svg>
                    ยอดในระบบเปลี่ยนระหว่างนับ (<?php echo number_format($_SESSION['adj_snap'][$l['sku']]) ?> → <?php echo number_format($l['have']) ?>)
                    มีการขายหรือรับเข้าแทรก — นับตัวนี้ใหม่
                  </small>
                <?php } ?>
              </div>
              <div class="cl-q">
                <button type="button" aria-label="ลดจำนวน" <?php echo $l['counted'] <= 0 ? 'disabled' : '' ?>
                        hx-post="<?php echo e($base) ?>" hx-target="#adj-live" hx-swap="outerHTML"
                        hx-vals='<?php echo e(json_encode(array('csrf' => $csrf, 'act' => 'minus', 'sku' => $l['sku']))) ?>'>
                  <svg class="ico"><use href="#i-minus"/></svg>
                </button>
                <input class="num rq-set" type="text" inputmode="numeric" value="<?php echo $l['counted'] ?>"
                       autocomplete="off" aria-label="จำนวนที่นับได้ของ <?php echo e($l['name']) ?>"
                       name="aq_<?php echo e($l['sku']) ?>" id="aq_<?php echo e($l['sku']) ?>"
                       hx-post="<?php echo e($base) ?>" hx-trigger="change" hx-target="#adj-live" hx-swap="outerHTML"
                       hx-vals='<?php echo e(json_encode(array('csrf' => $csrf, 'act' => 'set', 'sku' => $l['sku']))) ?>'>
                <button type="button" aria-label="เพิ่มจำนวน"
                        hx-post="<?php echo e($base) ?>" hx-target="#adj-live" hx-swap="outerHTML"
                        hx-vals='<?php echo e(json_encode(array('csrf' => $csrf, 'act' => 'plus', 'sku' => $l['sku']))) ?>'>
                  <svg class="ico"><use href="#i-plus"/></svg>
                </button>
              </div>
              <div class="cl-s"><?php echo e($l['unit']) ?></div>
              <button class="cl-x" type="button" title="เอาออกจากใบ"
                      aria-label="เอา <?php echo e($l['name']) ?> ออกจากใบ"
                      hx-post="<?php echo e($base) ?>" hx-target="#adj-live" hx-swap="outerHTML"
                      hx-vals='<?php echo e(json_encode(array('csrf' => $csrf, 'act' => 'del', 'sku' => $l['sku']))) ?>'>
                <svg class="ico"><use href="#i-x"/></svg>
              </button>
            </li>
          <?php } ?>
        </ul>

        <div class="pay recv-total adj-total">
          <div class="pay-row">
            <span>มูลค่าส่วนต่าง (ราคาทุน)</span>
            <b class="num <?php echo $sum['value'] < 0 ? 'dn' : ($sum['value'] > 0 ? 'up' : '') ?>"><?php echo e($valT) ?></b>
          </div>
          <div class="pay-tot">
            <span>ส่วนต่างสุทธิ</span>
            <b class="num" id="adj-sum" data-items="<?php echo $sum['items'] ?>" data-over="<?php echo $sum['over'] ?>"
               data-short="<?php echo $sum['short'] ?>" data-value-t="<?php echo e($valT) ?>">
              <?php echo $net === 0 ? '0' : ($net > 0 ? '+' : '−') . number_format(abs($net)) ?><small>ชิ้น</small>
            </b>
          </div>
        </div>
      <?php } ?>
    </div>

    <?php
    /* สาเหตุ — อยู่ใต้ผลการนับเลย จะได้เห็นทันทีที่มีรายการไม่ตรง
       hx-preserve: htmx สลับเนื้อหาส่วนนี้เมื่อไร กล่องนี้ยังเป็นตัวเดิม
       ค่าที่เลือก/พิมพ์ไว้จึงไม่หาย (โชว์หรือซ่อนคุมด้วย JS ในหน้าหลัก) */
    $meta = adraft_meta();
    ?>
    <div class="card adj-why" id="adj-why" hx-preserve="true" hidden>
      <div class="card-head">
        <div>
          <h2>ทำไมยอดถึงไม่ตรง</h2>
          <span class="sub">เลือกสาเหตุก่อนบันทึก ไว้ตรวจย้อนหลังได้</span>
        </div>
      </div>
      <div class="why-wrap">
        <div class="why" role="radiogroup" aria-label="สาเหตุที่ยอดไม่ตรง">
          <?php foreach (adj_reasons() as $k => $r) { ?>
            <label class="why-o">
              <input type="radio" name="why" value="<?php echo e($k) ?>"
                     data-note="<?php echo $r['note'] ? '1' : '0' ?>" <?php echo $meta['reason'] === $k ? 'checked' : '' ?>>
              <span><b><?php echo e($r['label']) ?></b><small><?php echo e($r['hint']) ?></small></span>
            </label>
          <?php } ?>
        </div>
      </div>
      <div class="adj-note">
        <label for="note">หมายเหตุ <i class="req" id="note-req" hidden>จำเป็นสำหรับสาเหตุนี้</i></label>
        <input class="input" type="text" id="note" name="note" autocomplete="off"
               value="<?php echo e($meta['note']) ?>" placeholder="เช่น เจอเคส 3 ชิ้นตกอยู่หลังตู้โชว์">
      </div>
    </div>
  </aside>

  <script>
  if (window.adjSync) { window.adjSync(); }
  </script>
</div>
