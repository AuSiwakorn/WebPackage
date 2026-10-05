<?php
/**
 * FILE: themes/aostock/adm-report-branch.php
 * ROLE: [ผู้ดูแล] รายงานเปรียบเทียบยอดขายแยกสาขา
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_sale, ao_stock_branch (ผ่าน api.php — sales_scan)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 8: SQL ทั้งช่วง · ช่วง "ตั้งแต่เริ่มใช้ระบบ" เริ่มที่วันแรกที่มีบิล (sales_first_day)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   [ผู้ดูแล] รายงานเปรียบเทียบยอดขายแยกสาขา
   ----------------------------------------------------------
   ตาราง: แถว = ช่วงเวลา · คอลัมน์ = สาขา + รวม · ช่องที่สูงสุดของแถวไฮไลต์
     mode = day   → รายวันของเดือนที่เลือก (m = Y-m)
            month → รายเดือนของปีที่เลือก (y = Y)   ← ค่าเริ่มต้น
            year  → รายปี ตั้งแต่ปีที่เริ่มมีข้อมูล
     v    = total (ยอดขาย) | bills (จำนวนบิล) | qty (จำนวนชิ้น)
     export=csv → ดาวน์โหลดตารางที่เห็นเป็น CSV
   ข้อมูล: บิลชุดเดียวกับหน้าบัญชี / รายงาน (ไม่นับบิลยกเลิก) — sales_scan()
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

$brAll   = branches_all();
$codes   = array_keys($brAll);
$today   = strtotime(date('Y-m-d'));
$yearMin = 2026;
$g       = function ($k, $def = '') { return (isset($_GET[$k]) && is_string($_GET[$k])) ? trim($_GET[$k]) : $def; };

$fMode = in_array($g('mode'), array('day', 'month', 'year'), true) ? $g('mode') : 'month';
$fV    = in_array($g('v'), array('total', 'bills', 'qty'), true) ? $g('v') : 'total';
$monTs = strtotime(date('Y-m-01'));
if (preg_match('/^\d{4}-\d{2}$/', $g('m')) && ($x = strtotime($g('m') . '-01')) !== false) {
    $monTs = min($x, strtotime(date('Y-m-01')));
}
$year = (int) date('Y');
if (preg_match('/^\d{4}$/', $g('y'))) {
    $year = max($yearMin, min((int) date('Y'), (int) $g('y')));
}

/* ช่วงที่ต้องดึง + วิธีจัดกลุ่มเป็นแถว */
if ($fMode === 'day') {
    $from = $monTs;
    $to   = min(strtotime(date('Y-m-t', $monTs)), $today);
    $rangeTxt = thai_month_full($monTs);
} elseif ($fMode === 'month') {
    $from = max(strtotime($year . '-01-01'), sales_first_day());   // เดือนก่อนเริ่มใช้ระบบ (บิลแรก) ไม่ต้องแสดง
    $to   = min(strtotime($year . '-12-31'), $today);
    $rangeTxt = 'ปี ' . ($year + 543);
} else {
    $from = sales_first_day();
    $to   = $today;
    $rangeTxt = 'ตั้งแต่เริ่มใช้ระบบ (' . thai_month_full(sales_first_day()) . ')';
}

$rq = function ($chg) use ($fMode, $fV, $monTs, $year) {
    $q = array_merge(array('mode' => $fMode, 'v' => $fV, 'm' => date('Y-m', $monTs), 'y' => $year), $chg);
    if ($q['mode'] !== 'day') {
        unset($q['m']);
    }
    if ($q['mode'] !== 'month') {
        unset($q['y']);
    }
    foreach (array('mode' => 'month', 'v' => 'total') as $k => $def) {
        if ((string) $q[$k] === $def) {
            unset($q[$k]);
        }
    }
    return 'adm-report-branch.php' . ($q ? '?' . http_build_query($q) : '');
};

$scan = sales_scan($codes, $from, $to);

/* ---------- จัดเป็นแถว ---------- */
$rows = array();
foreach ($scan['day'] as $ymd => $per) {
    $ts = strtotime($ymd);
    if ($fMode === 'day') {
        $rk = $ymd;
        $lb = thai_day_month($ts);
        $sub = date('w', $ts) === '0' ? 'วันอาทิตย์' : '';
    } elseif ($fMode === 'month') {
        $rk = date('Y-m', $ts);
        $lb = thai_month_full($ts);
        $sub = '';
    } else {
        $rk = date('Y', $ts);
        $lb = 'ปี ' . ((int) date('Y', $ts) + 543);
        $sub = '';
    }
    if (!isset($rows[$rk])) {
        $rows[$rk] = array('label' => $lb, 'sub' => $sub, 'ts' => $ts, 'cells' => array(), 'sum' => 0);
        foreach ($codes as $c) {
            $rows[$rk]['cells'][$c] = 0;
        }
    }
    foreach ($per as $c => $v) {
        $rows[$rk]['cells'][$c] += $v[$fV];
        $rows[$rk]['sum']       += $v[$fV];
    }
}
$rows = array_reverse($rows, true);                 // ล่าสุดขึ้นก่อน

/* รวมทั้งช่วง ต่อสาขา */
$colSum = array_fill_keys($codes, 0);
$grand  = 0;
foreach ($rows as $r) {
    foreach ($r['cells'] as $c => $v) {
        $colSum[$c] += $v;
    }
    $grand += $r['sum'];
}
/* ซ่อนสาขาที่ปิดใช้งานและไม่มียอดในช่วงนี้ */
$show = array();
foreach ($codes as $c) {
    if (!empty($brAll[$c]['active']) || $colSum[$c] > 0) {
        $show[] = $c;
    }
}
$rank = $colSum;
arsort($rank);
$fmt = function ($v) use ($fV) {
    return $fV === 'total' ? money2($v) : number_format($v);
};
$vLabel = array('total' => 'ยอดขาย (บาท)', 'bills' => 'จำนวนบิล', 'qty' => 'จำนวนชิ้น');

/* ---------- CSV: ตารางเปรียบเทียบตามที่เห็น (ทุกตัววัด) ---------- */
if ($g('export') === 'csv') {
    $head = array($fMode === 'day' ? 'วันที่' : ($fMode === 'month' ? 'เดือน' : 'ปี'));
    foreach ($show as $c) {
        $head[] = $brAll[$c]['name'] . ' (' . $vLabel[$fV] . ')';
    }
    $head[] = 'รวม';
    $csv = array();
    foreach ($rows as $rk => $r) {
        $line = array($rk);
        foreach ($show as $c) {
            $line[] = $fV === 'total' ? csv_money($r['cells'][$c]) : $r['cells'][$c];
        }
        $line[] = $fV === 'total' ? csv_money($r['sum']) : $r['sum'];
        $csv[] = $line;
    }
    $line = array('รวมทั้งช่วง');
    foreach ($show as $c) {
        $line[] = $fV === 'total' ? csv_money($colSum[$c]) : $colSum[$c];
    }
    $line[] = $fV === 'total' ? csv_money($grand) : $grand;
    $csv[] = $line;
    csv_send('aostock-sales-by-branch-' . $fMode . '-' . $fV . '-' . date('Ymd', $from) . '-' . date('Ymd', $to) . '.csv', $head, $csv);
}

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = 'ยอดขายแยกสาขา';
$PAGE_SUB       = 'เปรียบเทียบ' . $vLabel[$fV] . 'ของแต่ละสาขา · ' . $rangeTxt;
$NAV_ACTIVE     = 'adm-report-branch.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter hist-filter">
  <form method="get" action="adm-report-branch.php" class="acct-row">
    <div class="segs">
      <?php foreach (array('day' => 'ต่อวัน', 'month' => 'ต่อเดือน', 'year' => 'ต่อปี') as $md => $lb) { ?>
        <a class="seg<?php echo $fMode === $md ? ' on' : '' ?>" href="<?php echo e($rq(array('mode' => $md))) ?>"><?php echo e($lb) ?></a>
      <?php } ?>
    </div>
    <input type="hidden" name="mode" value="<?php echo e($fMode) ?>">
    <?php if ($fMode === 'day') { ?>
      <a class="btn btn-ghost btn-sm" href="<?php echo e($rq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>" aria-label="เดือนก่อนหน้า">‹</a>
      <label class="sr-only" for="bm">เดือน</label>
      <input class="input acct-date" type="month" id="bm" name="m" value="<?php echo e(date('Y-m', $monTs)) ?>" max="<?php echo e(date('Y-m')) ?>" onchange="this.form.submit()">
      <?php if ($monTs < strtotime(date('Y-m-01'))) { ?><a class="btn btn-ghost btn-sm" href="<?php echo e($rq(array('m' => date('Y-m', strtotime('+1 month', $monTs))))) ?>" aria-label="เดือนถัดไป">›</a><?php } ?>
    <?php } elseif ($fMode === 'month') { ?>
      <label class="sr-only" for="by">ปี</label>
      <select class="input acct-date" id="by" name="y" onchange="this.form.submit()">
        <?php for ($y = (int) date('Y'); $y >= $yearMin; $y--) { ?>
          <option value="<?php echo $y ?>" <?php echo $y === $year ? 'selected' : '' ?>>ปี <?php echo $y + 543 ?></option>
        <?php } ?>
      </select>
    <?php } ?>
    <div class="segs">
      <?php foreach (array('total' => 'ยอดขาย', 'bills' => 'บิล', 'qty' => 'ชิ้น') as $k => $lb) { ?>
        <a class="seg<?php echo $fV === $k ? ' on' : '' ?>" href="<?php echo e($rq(array('v' => $k))) ?>"><?php echo e($lb) ?></a>
      <?php } ?>
    </div>
      <a class="btn btn-ghost btn-sm rep-csv" href="<?php echo e($rq(array('export' => 'csv'))) ?>"><svg class="ico"><use href="#i-in"/></svg> ดาวน์โหลด CSV</a>
  </form>
</section>

<!-- ==================== อันดับสาขา ==================== -->
<section class="mini num adm-kpi rep-rankb" aria-label="อันดับสาขาในช่วงนี้">
  <?php $i = 0; foreach ($rank as $c => $v) { if (!in_array($c, $show, true)) { continue; } $i++; if ($i > 3) { break; } ?>
    <div class="m">
      <div class="lb">อันดับ <?php echo $i ?> · <?php echo e($brAll[$c]['name']) ?></div>
      <div class="nm"><?php echo e($fmt($v)) ?></div>
      <div class="sb"><?php echo $grand > 0 ? number_format($v / $grand * 100, 1) : '0.0' ?>% ของทั้งหมด</div>
    </div>
  <?php } ?>
</section>

<!-- ==================== ตารางเปรียบเทียบ ==================== -->
<section class="card">
  <div class="card-head">
    <div>
      <h2><?php echo e($vLabel[$fV]) ?> <?php echo $fMode === 'day' ? 'รายวัน' : ($fMode === 'month' ? 'รายเดือน' : 'รายปี') ?> · <?php echo e($rangeTxt) ?></h2>
      <span class="sub">ช่องสีเขียว = สาขาที่สูงสุดของแถวนั้น · ล่าสุดอยู่บน · ไม่นับบิลที่ยกเลิก
        · <?php echo $fMode === 'day' ? 'กดที่ยอดเพื่อดูบิลและสินค้าที่ขายของวันนั้น' : 'กดชื่อ' . ($fMode === 'month' ? 'เดือน' : 'ปี') . 'เพื่อดูละเอียดขึ้น' ?></span>
    </div>
  </div>
  <?php if (!$rows) { ?>
    <p class="empty">ไม่มีข้อมูลในช่วงนี้</p>
  <?php } else { ?>
    <div class="tbl-wrap">
      <table class="tbl hist-all rep-cmp num">
        <thead>
          <tr>
            <th><?php echo $fMode === 'day' ? 'วันที่' : ($fMode === 'month' ? 'เดือน' : 'ปี') ?></th>
            <?php foreach ($show as $c) { ?><th class="r"><?php echo e($brAll[$c]['short']) ?></th><?php } ?>
            <th class="r">รวม</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $rk => $r) {
              $max = 0;
              foreach ($show as $c) { $max = max($max, $r['cells'][$c]); }
              $link = $fMode === 'month' ? $rq(array('mode' => 'day', 'm' => $rk)) : ($fMode === 'year' ? $rq(array('mode' => 'month', 'y' => $rk)) : ''); ?>
            <tr>
              <td data-label="ช่วง">
                <?php if ($link !== '') { ?><a href="<?php echo e($link) ?>"><b><?php echo e($r['label']) ?></b></a><?php } else { ?><b><?php echo e($r['label']) ?></b><?php } ?>
                <?php if ($r['sub'] !== '') { ?><small class="hist-n"><?php echo e($r['sub']) ?></small><?php } ?>
              </td>
              <?php foreach ($show as $c) { $v = $r['cells'][$c]; ?>
                <td data-label="<?php echo e($brAll[$c]['short']) ?>" class="r">
                  <?php $cls = ($v > 0 && $v == $max) ? 'cmp-top' : ($v == 0 ? 'adm-none' : ''); ?>
                  <?php if ($fMode === 'day' && $v > 0) { ?>
                    <button type="button" class="cell-link <?php echo $cls ?>" data-day-sales="<?php echo e($c . '|' . $rk) ?>" title="ดูบิลและสินค้าที่ขายของ<?php echo e($brAll[$c]['name']) ?> วันนี้"><?php echo e($fmt($v)) ?></button>
                  <?php } else { ?>
                    <span class="<?php echo $cls ?>"><?php echo $v ? e($fmt($v)) : '—' ?></span>
                  <?php } ?>
                </td>
              <?php } ?>
              <td data-label="รวม" class="r">
                <?php if ($fMode === 'day' && $r['sum'] > 0) { ?>
                  <button type="button" class="cell-link" data-day-sales="<?php echo e('ALL|' . $rk) ?>" title="ดูบิลและสินค้าที่ขายของทุกสาขาวันนี้"><b><?php echo e($fmt($r['sum'])) ?></b></button>
                <?php } else { ?><b><?php echo e($fmt($r['sum'])) ?></b><?php } ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>
        <tfoot>
          <tr class="ret-total">
            <td><b>รวมทั้งช่วง</b></td>
            <?php foreach ($show as $c) { ?>
              <td data-label="<?php echo e($brAll[$c]['short']) ?>" class="r"><b><?php echo e($fmt($colSum[$c])) ?></b>
                <small class="hist-n"><?php echo $grand > 0 ? number_format($colSum[$c] / $grand * 100, 1) : '0.0' ?>%</small></td>
            <?php } ?>
            <td data-label="รวม" class="r"><b><?php echo e($fmt($grand)) ?></b></td>
          </tr>
        </tfoot>
      </table>
    </div>
  <?php } ?>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
