<?php
/**
 * FILE: themes/aostock/inc/adjust-live.php
 * ROLE: ส่วนที่ htmx สลับได้ของหน้าตรวจนับสต๊อก
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_count_item (นับแล้วในรอบนี้) · ao_stock_balance (ยอด) — ผ่าน api.php · ใบที่กำลังนับ (ร่าง) อยู่ใน $_SESSION['adj_draft']
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] สถานะ "นับแล้ว" อ่านจากตาราง (ช่วงที่ 6)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 3): แตะสินค้า / ปุ่มในใบ → ส่งกลับแค่ผลการนับ (#adj-cart) + การ์ดใบที่แตะ (hx-swap-oob)
 *         เดิมส่งการ์ดสินค้าทุกใบกลับมาวาดใหม่ทุกครั้ง กล่อง “ทำไมยอดถึงไม่ตรง” จึงโผล่ช้า · สคริปต์ adjSync ย้ายเข้าไปใน aside
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 4): การ์ดครั้งละ 60 ใบ + ปุ่ม “แสดงเพิ่ม” ($LIVE_PART 'more' = ส่งกลับแค่ชุดถัดไป)
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 5): ค้นสินค้าด้วย SQL แบบแบ่งหน้า (Stock_searchPickProducts) — ฐานข้อมูลกรอง / นับ ส่งมาแค่ 60 ตัวที่จะวาด (เดิมดึงทั้งร้านมากรองใน PHP)
 *   - [x] 14 ต.ค. (ผู้ใช้เลือก): + / − นับในเครื่องก่อนแล้วบันทึกเบื้องหลัง — ปุ่ม data-step · ช่อง data-qty (JS ใน inc/footer.php) · เดิมกดรัว ๆ แล้วหาย
 *   - [x] 14 ต.ค.: hx-ext="ao-live" — คำตอบที่มาหลังใบถูกวาดใหม่ลงตัวที่อยู่บนจอ
 *   - [x] 15 ต.ค. (ใบนับแบบรายการ ช่วง 1): เอาการล็อกการ์ด / ข้อความ “ร้านเปิด นับได้ครั้งละ N” ออก
 *   - [x] 15 ต.ค. (ผู้ใช้เลือก): ลองใบนับแบบตาราง (ช่วง 2–3) แล้วกลับมาใช้การ์ด · เก็บไว้: ชื่อ “ตรวจนับสต๊อก” · บรรทัดบอกให้ใช้เบิก / ตัดออก (.cnt-tip)
 *         · แท็บ “ทั้งหมด” ขึ้นก่อน · ช่องจำนวนในใบ form="adj-rows" (ไม่ติดไปกับฟอร์มบันทึก — ใบหลายร้อยแถวเกิน max_input_vars)
 *   - [x] 20 ต.ค. (Enter / ยิงบาร์โค้ดในช่องค้นหา): ช่องค้นหา form="adj-find" — ไม่ติดไปกับฟอร์มบันทึก
 *         เดิมยิงบาร์โค้ดตอนใบครบ = กล่องยืนยันขึ้น ยิงอีกครั้ง = บันทึกผลการนับ · Enter ยังค้นทันที (อีเวนต์ search ของช่อง)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ส่วนที่ htmx สลับได้ของหน้าตรวจนับสต๊อก
   ต้องกำหนดก่อน include: $user $code $q $cat
     $LIVE_PART  (ไม่บังคับ) 'all' = ทั้งส่วน #adj-live · 'cart' = เฉพาะผลการนับ #adj-cart + การ์ดของ $OOB_SKUS (hx-swap-oob) · 'more' = การ์ดชุดถัดไป (?from=) + ปุ่มแสดงเพิ่ม
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$LIVE_PART = (isset($LIVE_PART) && in_array($LIVE_PART, array('cart', 'more'), true)) ? $LIVE_PART : 'all';
$tabs  = count_tabs();
$tab   = (isset($tab) && isset($tabs[$tab])) ? $tab : 'all';
$round = count_round($code);
$stAll = count_status_all($code);

/* นับจำนวนของแต่ละแท็บ ตามคำค้นและหมวดที่เลือกอยู่ แล้วดึงเฉพาะการ์ดที่จะวาดรอบนี้ (ส่งกลับแค่ผลการนับ = ไม่ต้องใช้)
   ฐานข้อมูลกรอง / นับให้ (ช่วง 5) — นับแล้ว = SKU ที่อยู่ใน $stAll · ยังไม่นับ = ไม่อยู่ · $list = การ์ดที่จะวาด · $total = จำนวนในแท็บนี้ */
list($from, $end) = Stock_getListRange();     // การ์ดครั้งละ 60 ใบ + ปุ่ม “แสดงเพิ่ม” (ช่วง 4)
$tabN  = array('all' => 0, 'todo' => 0, 'done' => 0);
$list  = array();
$total = 0;
if ($LIVE_PART !== 'cart') {
    $counted      = array_keys($stAll);
    $tabN['all']  = Stock_searchPickProducts($code, $q, $cat, 0, 0)['total'];
    $tabN['done'] = $counted ? Stock_searchPickProducts($code, $q, $cat, 0, 0, $counted)['total'] : 0;
    $tabN['todo'] = $tabN['all'] - $tabN['done'];
    $found = Stock_searchPickProducts($code, $q, $cat, ($LIVE_PART === 'more') ? $from : 0, ($LIVE_PART === 'more') ? $end - $from : $end,
                                      ($tab === 'done') ? $counted : null, ($tab === 'todo') ? $counted : null);
    $list  = $found['rows'];
    $total = $found['total'];
}

/* ความคืบหน้าของรอบ (ทั้งสาขา ไม่สนตัวกรอง) */
$nAll  = ($LIVE_PART === 'all') ? Stock_searchPickProducts($code, '', '', 0, 0)['total'] : 0;
$nDone = count($stAll);
$pct   = $nAll > 0 ? (int) floor($nDone * 100 / $nAll) : 0;

$lines = adraft_lines($code);
$sum   = adj_sum($lines);
$net   = $sum['plus'] - $sum['minus'];
$qs    = adj_qs($q, $cat, $tab);
$base  = 'stocktake.php' . $qs;
$csrf  = csrf_token();
$valT  = ($sum['value'] < 0 ? '−' : ($sum['value'] > 0 ? '+' : '')) . money2(abs($sum['value'])) . ' บาท';

/* การ์ดสินค้าหนึ่งใบ — ใช้ทั้งตอนวาดรายการ และตอนส่งกลับใบเดียวแบบ hx-swap-oob */
$tile = function ($p, $oob) use ($base, $csrf, $stAll) {
    $inDoc = isset($_SESSION['adj_draft'][$p['sku']]); ?>
            <button class="good<?php echo $inDoc ? ' is-in is-count' : '' ?>" type="button" id="<?php echo e(Stock_buildTileId($p['sku'])) ?>"<?php echo $oob ? ' hx-swap-oob="true"' : '' ?>
                    <?php echo $inDoc ? 'disabled' : '' ?>
                    hx-post="<?php echo e($base) ?>" hx-target="#adj-cart" hx-swap="outerHTML"
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
<?php
};

/* กด “แสดงเพิ่ม” → การ์ดชุดถัดไป (ตามแท็บ / หมวด / คำค้นเดิม) + ปุ่มใหม่ */
if ($LIVE_PART === 'more') {
    foreach ($list as $p) {
        $tile($p, false);
    }
    Stock_renderMoreButton($end, $total, $base);
    return;
}
?>
<?php if ($LIVE_PART === 'all') { ?>
<div class="sale-wrap" id="adj-live" hx-ext="ao-live">

  <!-- ==================== เลือกสินค้าที่จะนับ ==================== -->
  <section class="card sale-pick">
    <div class="card-head">
      <div>
        <h2>เลือกสินค้าที่นับ</h2>
        <span class="sub">แตะสินค้าเพื่อเพิ่มเข้าใบ แล้วกรอกจำนวนที่นับได้จริงทางขวา</span>
      </div>
    </div>

    <?php /* หน้านี้ไว้นับของจริงเทียบกับระบบ — รู้สาเหตุอยู่แล้วให้ไปตัดออก (ผู้ใช้ขอ: ไม่ให้สับสนกับเบิก / ตัดออก) */ ?>
    <p class="cnt-tip">
      <svg class="ico"><use href="#i-info"/></svg>
      <span>รู้สาเหตุอยู่แล้ว (แตก / หาย / หมดอายุ) ใช้
        <?php if (page_ok($user, 'issue.php')) { ?><a href="issue.php">เบิก / ตัดออก</a><?php } else { ?><b>เบิก / ตัดออก</b><?php } ?>
        แทน — หน้านี้ใช้นับของจริงเทียบกับยอดในระบบ</span>
    </p>

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
          <?php /* form="adj-find" (ไม่มีฟอร์มชื่อนี้) = ช่องไม่ติดไปกับฟอร์มบันทึก — Enter / เครื่องยิงบาร์โค้ดไม่กดบันทึกให้ (20 ต.ค.)
                   · Enter ยังค้นทันทีด้วยอีเวนต์ search · htmx ส่ง q ของช่องตัวเองตามปกติ (เซิร์ฟเวอร์อ่าน q จาก $_GET เท่านั้น) */ ?>
          <input type="search" id="aq" name="q" value="<?php echo e($q) ?>" placeholder="ชื่อสินค้า หรือ SKU" form="adj-find"
                 hx-get="stocktake.php" hx-trigger="input changed delay:350ms, search"
                 hx-vals='<?php echo e(json_encode(array('cat' => $cat, 't' => $tab))) ?>'
                 hx-target="#adj-live" hx-swap="outerHTML"
                 hx-sync="this:replace" hx-push-url="true" hx-preserve="true">
        </div>
      </div>

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
          รอบนี้ยังไม่ได้นับรายการในกลุ่มนี้<br><small>เริ่มนับได้จากแท็บ “ทั้งหมด” หรือ “ยังไม่นับ”</small>
        </p>
      <?php } elseif (!$list) { ?>
        <p class="empty">
          <svg class="ico"><use href="#i-search"/></svg>
          ไม่พบสินค้าที่ตรงกับคำค้น<br><small>ลองพิมพ์ชื่อสั้นลง หรือเลือกหมวด “ทุกหมวด”</small>
        </p>
      <?php } else { ?>
        <div class="goods">
          <?php foreach ($list as $p) { $tile($p, false); } ?>
          <?php Stock_renderMoreButton($end, $total, $base); ?>
        </div>
      <?php } ?>
    </div>
  </section>
<?php } ?>

  <!-- ==================== ผลการนับ ==================== -->
  <aside class="sale-cart" id="adj-cart" hx-ext="ao-live">
    <div class="card cart-card">
      <div class="card-head">
        <div>
          <h2>ผลการนับ</h2>
          <span class="sub"><?php echo $sum['items'] ?> รายการ · ตรง <?php echo $sum['same'] ?> · เกิน <?php echo $sum['over'] ?> · ขาด <?php echo $sum['short'] ?></span>
        </div>
        <?php if ($lines) { ?>
          <button class="icon-btn" type="button" title="ล้างทั้งใบ" aria-label="ล้างทั้งใบ"
                  hx-post="<?php echo e($base) ?>" hx-target="#adj-cart" hx-swap="outerHTML"
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
              <?php /* + / − นับในเครื่องก่อนแล้วบันทึกเบื้องหลัง (data-step · JS ใน inc/footer.php — 14 ต.ค.)
                       นับได้ 0 = ยังอยู่ในใบ (ไม่มี data-tile — ป้ายบนการ์ดตรวจนับเป็นเครื่องหมายถูก ไม่ใช่จำนวน)
                       form="adj-rows" (ไม่มีฟอร์มชื่อนี้) = ช่องไม่ติดไปกับฟอร์มบันทึก · htmx ยังส่งค่าช่องตัวเองตามปกติ */ ?>
              <div class="cl-q">
                <button type="button" aria-label="ลดจำนวน" data-step="-1" <?php echo $l['counted'] <= 0 ? 'disabled' : '' ?>>
                  <svg class="ico"><use href="#i-minus"/></svg>
                </button>
                <input class="num rq-set" type="text" inputmode="numeric" value="<?php echo $l['counted'] ?>" form="adj-rows"
                       autocomplete="off" aria-label="จำนวนที่นับได้ของ <?php echo e($l['name']) ?>"
                       name="aq_<?php echo e($l['sku']) ?>" id="aq_<?php echo e($l['sku']) ?>"
                       data-qty data-no-busy data-sku="<?php echo e($l['sku']) ?>" data-min="0" data-max="<?php echo (int) STOCK_LINE_MAX ?>"
                       hx-post="<?php echo e($base) ?>" hx-trigger="change" hx-target="#adj-cart" hx-swap="outerHTML"
                       hx-vals='<?php echo e(json_encode(array('csrf' => $csrf, 'act' => 'set', 'sku' => $l['sku'], 'seen' => (int) $l['have']))) ?>'>
                <?php /* seen = ยอดในระบบที่แถวโชว์ตอนนับ — นับใหม่หลังยอดเปลี่ยน = รับรู้ยอดใหม่ (ป้ายเตือนหาย · บันทึกผ่านในครั้งเดียว) */ ?>
                <button type="button" aria-label="เพิ่มจำนวน" data-step="1">
                  <svg class="ico"><use href="#i-plus"/></svg>
                </button>
              </div>
              <div class="cl-s"><?php echo e($l['unit']) ?></div>
              <button class="cl-x" type="button" title="เอาออกจากใบ"
                      aria-label="เอา <?php echo e($l['name']) ?> ออกจากใบ"
                      hx-post="<?php echo e($base) ?>" hx-target="#adj-cart" hx-swap="outerHTML"
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

  <script>
  /* อยู่ใน aside เพื่อให้ทำงานทุกครั้งที่สลับแค่ผลการนับ (โชว์ / ซ่อนกล่องสาเหตุทันที) */
  if (window.adjSync) { window.adjSync(); }
  </script>
  </aside>
<?php if ($LIVE_PART === 'all') { ?>
</div>
<?php } else { ?>
<?php
    /* ส่งกลับแค่ผลการนับ → การ์ดที่เข้า / ออกจากใบ สลับใบเดียวแบบ hx-swap-oob */
    foreach (Stock_getPickProducts($code, isset($OOB_SKUS) ? $OOB_SKUS : array()) as $p) {
        $tile($p, true);
    }
} ?>
