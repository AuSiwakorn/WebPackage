<?php
/* ==========================================================
   AOSTOCK DEMO — [ผู้ดูแล] รายงานเปรียบเทียบยอดขายแยกสาขา
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

$brAll   = demo_branches_all();
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
    $from = max(strtotime($year . '-01-01'), strtotime(DEMO_DATA_START));   // เดือนก่อนเริ่มใช้ระบบไม่ต้องแสดง
    $to   = min(strtotime($year . '-12-31'), $today);
    $rangeTxt = 'ปี ' . ($year + 543);
} else {
    $from = strtotime(DEMO_DATA_START);
    $to   = $today;
    $rangeTxt = 'ตั้งแต่เริ่มใช้ระบบ (' . thai_month_full(strtotime(DEMO_DATA_START)) . ')';
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
        $sub = date('w', $ts) === '0' ? 'อาทิตย์ (ร้านปิด)' : '';
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
      <?php foreach (array('day' => 'ต่อวัน', 'month' => 'ต่อเดือน', 'year' => 'ต่อปี') as $md => $lb): ?>
        <a class="seg<?= $fMode === $md ? ' on' : '' ?>" href="<?= e($rq(array('mode' => $md))) ?>"><?= e($lb) ?></a>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="mode" value="<?= e($fMode) ?>">
    <?php if ($fMode === 'day'): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($rq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>" aria-label="เดือนก่อนหน้า">‹</a>
      <label class="sr-only" for="bm">เดือน</label>
      <input class="input acct-date" type="month" id="bm" name="m" value="<?= e(date('Y-m', $monTs)) ?>" max="<?= e(date('Y-m')) ?>" onchange="this.form.submit()">
      <?php if ($monTs < strtotime(date('Y-m-01'))): ?><a class="btn btn-ghost btn-sm" href="<?= e($rq(array('m' => date('Y-m', strtotime('+1 month', $monTs))))) ?>" aria-label="เดือนถัดไป">›</a><?php endif; ?>
    <?php elseif ($fMode === 'month'): ?>
      <label class="sr-only" for="by">ปี</label>
      <select class="input acct-date" id="by" name="y" onchange="this.form.submit()">
        <?php for ($y = (int) date('Y'); $y >= $yearMin; $y--): ?>
          <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>>ปี <?= $y + 543 ?></option>
        <?php endfor; ?>
      </select>
    <?php endif; ?>
    <div class="segs">
      <?php foreach (array('total' => 'ยอดขาย', 'bills' => 'บิล', 'qty' => 'ชิ้น') as $k => $lb): ?>
        <a class="seg<?= $fV === $k ? ' on' : '' ?>" href="<?= e($rq(array('v' => $k))) ?>"><?= e($lb) ?></a>
      <?php endforeach; ?>
    </div>
      <a class="btn btn-ghost btn-sm rep-csv" href="<?= e($rq(array('export' => 'csv'))) ?>"><svg class="ico"><use href="#i-in"/></svg> ดาวน์โหลด CSV</a>
  </form>
</section>

<!-- ==================== อันดับสาขา ==================== -->
<section class="mini num adm-kpi rep-rankb" aria-label="อันดับสาขาในช่วงนี้">
  <?php $i = 0; foreach ($rank as $c => $v): if (!in_array($c, $show, true)) { continue; } $i++; if ($i > 3) { break; } ?>
    <div class="m">
      <div class="lb">อันดับ <?= $i ?> · <?= e($brAll[$c]['name']) ?></div>
      <div class="nm"><?= e($fmt($v)) ?></div>
      <div class="sb"><?= $grand > 0 ? number_format($v / $grand * 100, 1) : '0.0' ?>% ของทั้งหมด</div>
    </div>
  <?php endforeach; ?>
</section>

<!-- ==================== ตารางเปรียบเทียบ ==================== -->
<section class="card">
  <div class="card-head">
    <div>
      <h2><?= e($vLabel[$fV]) ?> <?= $fMode === 'day' ? 'รายวัน' : ($fMode === 'month' ? 'รายเดือน' : 'รายปี') ?> · <?= e($rangeTxt) ?></h2>
      <span class="sub">ช่องสีเขียว = สาขาที่สูงสุดของแถวนั้น · ล่าสุดอยู่บน · ไม่นับบิลที่ยกเลิก
        · <?= $fMode === 'day' ? 'กดที่ยอดเพื่อดูบิลและสินค้าที่ขายของวันนั้น' : 'กดชื่อ' . ($fMode === 'month' ? 'เดือน' : 'ปี') . 'เพื่อดูละเอียดขึ้น' ?></span>
    </div>
  </div>
  <?php if (!$rows): ?>
    <p class="empty">ไม่มีข้อมูลในช่วงนี้</p>
  <?php else: ?>
    <div class="tbl-wrap">
      <table class="tbl hist-all rep-cmp num">
        <thead>
          <tr>
            <th><?= $fMode === 'day' ? 'วันที่' : ($fMode === 'month' ? 'เดือน' : 'ปี') ?></th>
            <?php foreach ($show as $c): ?><th class="r"><?= e($brAll[$c]['short']) ?></th><?php endforeach; ?>
            <th class="r">รวม</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $rk => $r):
              $max = 0;
              foreach ($show as $c) { $max = max($max, $r['cells'][$c]); }
              $link = $fMode === 'month' ? $rq(array('mode' => 'day', 'm' => $rk)) : ($fMode === 'year' ? $rq(array('mode' => 'month', 'y' => $rk)) : ''); ?>
            <tr>
              <td data-label="ช่วง">
                <?php if ($link !== ''): ?><a href="<?= e($link) ?>"><b><?= e($r['label']) ?></b></a><?php else: ?><b><?= e($r['label']) ?></b><?php endif; ?>
                <?php if ($r['sub'] !== ''): ?><small class="hist-n"><?= e($r['sub']) ?></small><?php endif; ?>
              </td>
              <?php foreach ($show as $c): $v = $r['cells'][$c]; ?>
                <td data-label="<?= e($brAll[$c]['short']) ?>" class="r">
                  <?php $cls = ($v > 0 && $v == $max) ? 'cmp-top' : ($v == 0 ? 'adm-none' : ''); ?>
                  <?php if ($fMode === 'day' && $v > 0): ?>
                    <button type="button" class="cell-link <?= $cls ?>" data-day-sales="<?= e($c . '|' . $rk) ?>" title="ดูบิลและสินค้าที่ขายของ<?= e($brAll[$c]['name']) ?> วันนี้"><?= e($fmt($v)) ?></button>
                  <?php else: ?>
                    <span class="<?= $cls ?>"><?= $v ? e($fmt($v)) : '—' ?></span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
              <td data-label="รวม" class="r">
                <?php if ($fMode === 'day' && $r['sum'] > 0): ?>
                  <button type="button" class="cell-link" data-day-sales="<?= e('ALL|' . $rk) ?>" title="ดูบิลและสินค้าที่ขายของทุกสาขาวันนี้"><b><?= e($fmt($r['sum'])) ?></b></button>
                <?php else: ?><b><?= e($fmt($r['sum'])) ?></b><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="ret-total">
            <td><b>รวมทั้งช่วง</b></td>
            <?php foreach ($show as $c): ?>
              <td data-label="<?= e($brAll[$c]['short']) ?>" class="r"><b><?= e($fmt($colSum[$c])) ?></b>
                <small class="hist-n"><?= $grand > 0 ? number_format($colSum[$c] / $grand * 100, 1) : '0.0' ?>%</small></td>
            <?php endforeach; ?>
            <td data-label="รวม" class="r"><b><?= e($fmt($grand)) ?></b></td>
          </tr>
        </tfoot>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
