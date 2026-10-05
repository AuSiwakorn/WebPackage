<?php
/**
 * FILE: themes/aostock/movements.php
 * ROLE: ประวัติเคลื่อนไหวรายสินค้า
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/movement-list.php, themes/aostock/inc/header.php, themes/aostock/inc/movement-feed.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_move (ผ่าน api.php)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ความเคลื่อนไหวอ่านจาก ao_stock_move (ช่วงที่ 6)
 *   - [x] บิลขาย / รับคืนเขียน stock_move แล้ว ไม่อ่าน session (ช่วงที่ 7)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ประวัติเคลื่อนไหวรายสินค้า
   ----------------------------------------------------------
   ซ้าย: เลือกสินค้า · ขวา: ทุกครั้งที่ยอดของสินค้านั้นขยับ
   (ขาย รับเข้า ตัดออก ตรวจนับ และการยกเลิก) พร้อมยอดคงเหลือหลังแต่ละรายการ
   ต่างจาก "ประวัติการทำรายการ" ที่ดูทั้งสาขาตามเวลา — หน้านี้ดูทีละสินค้า
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();
$code = work_branch($user);          // ผู้ดูแลเลือกสาขาได้จากแถบบน

$isHx   = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
$q      = isset($_GET['q'])   ? trim($_GET['q'])   : '';
$cat    = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$sku    = isset($_GET['sku']) ? trim($_GET['sku']) : '';
$period = isset($_GET['p'])   ? trim($_GET['p'])   : '7';
$pers   = move_periods();
if (!isset($pers[$period])) { $period = '7'; }

$prod = ($sku !== '') ? product_by_sku($sku) : null;
if ($prod === null) { $sku = ''; }

/* ---------- รายการสินค้าทางซ้าย ---------- */
$list = sale_products($code, $q, $cat);

if ($isHx) {
    require dirname(__FILE__) . '/inc/movement-list.php';
    exit;
}

if ($prod !== null) {
    $now  = product_qty($prod, $code);
    $view = move_view(move_rows($code, $prod), $period, $now);
}

$branch     = $code;
$PAGE_TITLE = 'ประวัติเคลื่อนไหว';
$PAGE_SUB   = branch_name($code) . ' · ดูทีละสินค้า ว่ายอดขยับเพราะอะไร เมื่อไร โดยใคร';
$NAV_ACTIVE = 'movements.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<div class="mv-wrap<?php echo $prod !== null ? ' has-pick' : '' ?>">

  <!-- ==================== เลือกสินค้า ==================== -->
  <section class="card mv-pick">
    <div class="card-head">
      <div>
        <h2>เลือกสินค้า</h2>
        <span class="sub"><?php echo count(products_list()) ?> รายการในสาขา</span>
      </div>
    </div>
    <div class="mv-find">
      <div class="find-in">
        <svg class="ico"><use href="#i-search"/></svg>
        <label class="sr-only" for="mq">ค้นหาสินค้า</label>
        <input type="search" id="mq" name="q" value="<?php echo e($q) ?>" placeholder="ชื่อสินค้า หรือ SKU"
               hx-get="movements.php" hx-trigger="input changed delay:300ms, search"
               hx-include="#mcat" hx-vals='<?php echo e(json_encode(array('sku' => $sku, 'p' => $period))) ?>'
               hx-target="#mv-list" hx-swap="outerHTML" hx-sync="this:replace">
      </div>
      <label class="sr-only" for="mcat">หมวดสินค้า</label>
      <select class="input mv-cat" id="mcat" name="cat"
              hx-get="movements.php" hx-trigger="change" hx-include="#mq"
              hx-vals='<?php echo e(json_encode(array('sku' => $sku, 'p' => $period))) ?>'
              hx-target="#mv-list" hx-swap="outerHTML">
        <option value="">ทุกหมวด</option>
        <?php foreach (product_cats() as $c) { ?>
          <option value="<?php echo e($c) ?>" <?php echo $cat === $c ? 'selected' : '' ?>><?php echo e($c) ?></option>
        <?php } ?>
      </select>
    </div>
    <?php require dirname(__FILE__) . '/inc/movement-list.php'; ?>
  </section>

  <!-- ==================== ความเคลื่อนไหว ==================== -->
  <section class="card mv-detail">
    <?php if ($prod === null) { ?>
      <?php $feedCodes = array($code); require dirname(__FILE__) . '/inc/movement-feed.php'; ?>
    <?php } else { ?>
      <?php $s = $view['sum']; ?>
      <div class="mv-head">
        <a class="mv-back" href="movements.php<?php echo e(move_qs($q, $cat, '', $period)) ?>">
          <svg class="ico"><use href="#i-arrow"/></svg> เลือกสินค้าอื่น
        </a>
        <div class="mv-prod">
          <?php echo thumb_html($prod, 'thumb--lg') ?>
          <div>
            <small><?php echo e($prod['sku']) ?> · <?php echo e($prod['cat']) ?></small>
            <h2><?php echo e($prod['name']) ?></h2>
            <span class="mv-now">คงเหลือตอนนี้ <b class="num"><?php echo number_format($view['close']) ?></b> <?php echo e($prod['unit']) ?></span>
          </div>
        </div>
        <div class="segs mv-per">
          <?php foreach ($pers as $k => $label) { ?>
            <a class="seg<?php echo $period === $k ? ' on' : '' ?>" href="movements.php<?php echo e(move_qs($q, $cat, $sku, $k)) ?>"><?php echo e($label) ?></a>
          <?php } ?>
        </div>
      </div>

      <?php
      /* สมการยอด: ยกมา + รับเข้า − ขาย − ตัดออก ± ปรับยอด = คงเหลือ */
      $cells = array(
          array('ยกมา',    $view['open'],  'b', ''),
          array('รับเข้า',  $s['receive'],  's', 'in'),
          array('ขาย',     $s['sale'],     's', 'sale'),
          array('ตัดออก',  $s['issue'],    's', 'out'),
          array('ปรับยอด', $s['adjust'],   's', 'adj'),
          array('คงเหลือ',  $view['close'], 'b', 'end'),
      );
      ?>
      <div class="mv-eq num" aria-label="สรุปยอดในช่วงที่เลือก">
        <?php foreach ($cells as $c) { ?>
          <div class="mv-c<?php echo $c[3] !== '' ? ' c-' . $c[3] : '' ?>">
            <span><?php echo e($c[0]) ?></span>
            <b><?php
              if ($c[2] === 'b')     { echo number_format($c[1]); }
              elseif ($c[1] > 0)     { echo '+' . number_format($c[1]); }
              elseif ($c[1] < 0)     { echo '−' . number_format(-$c[1]); }
              else                   { echo '0'; }
            ?></b>
          </div>
        <?php } ?>
      </div>

      <?php if (!$view['rows']) { ?>
        <p class="empty">
          <svg class="ico"><use href="#i-activity"/></svg>
          ไม่มีความเคลื่อนไหวใน<?php echo e($period === 'today' ? 'วันนี้' : ' ' . $pers[$period] . 'ล่าสุด') ?><br>
          <small>ยอดคงที่อยู่ที่ <?php echo number_format($view['close']) ?> <?php echo e($prod['unit']) ?> · ลองเลือกช่วงเวลาที่ยาวขึ้น</small>
        </p>
      <?php } else { ?>
        <div class="tbl-wrap">
          <table class="tbl tbl-mv">
            <thead>
              <tr>
                <th>เมื่อ</th>
                <th>รายการ</th>
                <th>โดย</th>
                <th class="r">เข้า / ออก</th>
                <th class="r">คงเหลือ</th>
              </tr>
            </thead>
            <tbody>
              <?php $lastDay = ''; ?>
              <?php foreach ($view['rows'] as $r) { ?>
                <?php $m = move_type_of($r['type']); ?>
                <tr class="mv-r tone-<?php echo e($m['tone']) ?><?php echo date('Ymd', $r['ts']) === date('Ymd') ? ' is-today' : '' ?>">
                  <td data-label="เมื่อ" class="mv-when"><?php echo e(move_when($r['ts'])) ?></td>
                  <td data-label="รายการ">
                    <span class="badge b-<?php echo e($m['tone']) ?>"><?php echo e($m['label']) ?></span>
                    <span class="doc"><?php echo e($r['doc']) ?></span>
                    <?php if ($r['note'] !== '') { ?><small><?php echo e($r['note']) ?></small><?php } ?>
                  </td>
                  <td data-label="โดย"><?php echo e($r['by']) ?></td>
                  <td data-label="เข้า / ออก" class="r num">
                    <?php if ($r['delta'] > 0) { ?>
                      <b class="mv-up">+<?php echo number_format($r['delta']) ?></b>
                    <?php } elseif ($r['delta'] < 0) { ?>
                      <b class="mv-dn">−<?php echo number_format(-$r['delta']) ?></b>
                    <?php } else { ?>
                      <span class="mv-eq0">0</span>
                    <?php } ?>
                  </td>
                  <td data-label="คงเหลือ" class="r num"><b><?php echo number_format($r['bal']) ?></b></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
        <p class="mv-foot">
          ดูย้อนหลังได้สูงสุด 14 วัน · ยอดคงเหลือหลังแต่ละรายการคำนวณย้อนจากยอดปัจจุบัน
        </p>
      <?php } ?>
    <?php } ?>
  </section>
</div>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
