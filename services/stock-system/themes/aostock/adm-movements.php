<?php
/**
 * FILE: themes/aostock/adm-movements.php
 * ROLE: [ผู้ดูแล] ประวัติเคลื่อนไหวรายสินค้า ทุกสาขา
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
   [ผู้ดูแล] ประวัติเคลื่อนไหวรายสินค้า ทุกสาขา
   ----------------------------------------------------------
   เหมือนหน้าของพนักงาน แต่เลือกสาขาได้ในหน้า (b = ALL | รหัสสาขา)
   ทุกสาขา: รวมรายการของทุกสาขาเรียงตามเวลา · "คงเหลือ" ในแต่ละแถวเป็นยอดของสาขานั้น
   สมการด้านบน = ผลรวมของทุกสาขา
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล
$br   = branches_active();
$fB   = (isset($_GET['b']) && is_string($_GET['b']) && isset($br[$_GET['b']])) ? $_GET['b'] : 'ALL';
$code = $fB;
$MV_PAGE  = 'adm-movements.php';
$MV_EXTRA = array('b' => $fB === 'ALL' ? '' : $fB);

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
    if ($fB !== 'ALL') {
        $view = move_view(move_rows($code, $prod), $period, $now);
        foreach ($view['rows'] as $i => $r) {
            $view['rows'][$i]['branch'] = $fB;
        }
    } else {
        /* รวมทุกสาขา: ดึงทีละสาขาแล้วต่อกัน */
        $view = array('rows' => array(), 'open' => 0, 'close' => 0,
                      'sum' => array('receive' => 0, 'sale' => 0, 'issue' => 0, 'adjust' => 0));
        foreach (array_keys($br) as $c) {
            $v = move_view(move_rows($c, $prod), $period, product_qty($prod, $c));
            foreach ($v['rows'] as $r) {
                $r['branch'] = $c;
                $view['rows'][] = $r;
            }
            $view['open']  += $v['open'];
            $view['close'] += $v['close'];
            foreach ($v['sum'] as $k => $n) {
                $view['sum'][$k] += $n;
            }
        }
        usort($view['rows'], function ($a, $b) { return $a['ts'] === $b['ts'] ? 0 : ($a['ts'] < $b['ts'] ? 1 : -1); });
    }
}

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = 'ประวัติเคลื่อนไหว';
$PAGE_SUB       = ($fB === 'ALL' ? 'ทุกสาขา' : branch_name($fB)) . ' · ดูทีละสินค้า ว่ายอดขยับเพราะอะไร เมื่อไร โดยใคร';
$NAV_ACTIVE     = 'adm-movements.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<div class="mv-wrap<?php echo $prod !== null ? ' has-pick' : '' ?>">

  <!-- ==================== เลือกสินค้า ==================== -->
  <section class="card mv-pick">
    <div class="card-head">
      <div>
        <h2>เลือกสินค้า</h2>
        <span class="sub"><?php echo count(products_list()) ?> รายการ · จำนวน<?php echo $fB === 'ALL' ? 'รวมทุกสาขา' : 'ของ' . e(branch_name($fB)) ?></span>
      </div>
    </div>
    <form class="mv-branch" method="get" action="adm-movements.php">
      <?php if ($sku !== '') { ?><input type="hidden" name="sku" value="<?php echo e($sku) ?>"><?php } ?>
      <?php if ($period !== '7') { ?><input type="hidden" name="p" value="<?php echo e($period) ?>"><?php } ?>
      <label class="sr-only" for="mb">สาขา</label>
      <select class="input" id="mb" name="b" onchange="this.form.submit()">
        <option value="ALL">ทุกสาขา</option>
        <?php foreach ($br as $c => $x) { ?>
          <option value="<?php echo e($c) ?>" <?php echo $fB === $c ? 'selected' : '' ?>><?php echo e($x['name']) ?></option>
        <?php } ?>
      </select>
    </form>
    <div class="mv-find">
      <div class="find-in">
        <svg class="ico"><use href="#i-search"/></svg>
        <label class="sr-only" for="mq">ค้นหาสินค้า</label>
        <input type="search" id="mq" name="q" value="<?php echo e($q) ?>" placeholder="ชื่อสินค้า หรือ SKU"
               hx-get="adm-movements.php" hx-trigger="input changed delay:300ms, search"
               hx-include="#mcat" hx-vals='<?php echo e(json_encode(array('sku' => $sku, 'p' => $period, 'b' => $fB))) ?>'
               hx-target="#mv-list" hx-swap="outerHTML" hx-sync="this:replace">
      </div>
      <label class="sr-only" for="mcat">หมวดสินค้า</label>
      <select class="input mv-cat" id="mcat" name="cat"
              hx-get="adm-movements.php" hx-trigger="change" hx-include="#mq"
              hx-vals='<?php echo e(json_encode(array('sku' => $sku, 'p' => $period, 'b' => $fB))) ?>'
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
      <?php $feedCodes = $fB === 'ALL' ? array_keys($br) : array($fB); require dirname(__FILE__) . '/inc/movement-feed.php'; ?>
    <?php } else { ?>
      <?php $s = $view['sum']; ?>
      <div class="mv-head">
        <a class="mv-back" href="adm-movements.php<?php echo e(move_qs($q, $cat, '', $period, $MV_EXTRA)) ?>">
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
            <a class="seg<?php echo $period === $k ? ' on' : '' ?>" href="adm-movements.php<?php echo e(move_qs($q, $cat, $sku, $k, $MV_EXTRA)) ?>"><?php echo e($label) ?></a>
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
                <?php if ($fB === 'ALL') { ?><th>สาขา</th><?php } ?>
                <th>โดย</th>
                <th class="r">เข้า / ออก</th>
                <th class="r"><?php echo $fB === 'ALL' ? 'คงเหลือ (สาขานั้น)' : 'คงเหลือ' ?></th>
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
                  <?php if ($fB === 'ALL') { ?><td data-label="สาขา"><span class="hist-br"><?php echo e($br[$r['branch']]['short']) ?></span></td><?php } ?>
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
