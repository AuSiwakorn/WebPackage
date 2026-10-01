<?php
/* ==========================================================
   AOSTOCK DEMO — [ผู้ดูแล] รายงานยอดขาย ทุกสาขา
   ----------------------------------------------------------
   ใช้ข้อมูลบิลชุดเดียวกับหน้าบัญชีและประวัติ (acct_bills: วันนี้จาก session · วันก่อนจากข้อมูลสมมติที่คงที่)
   ไม่นับบิลที่ยกเลิก · ยอดขาย = ยอดที่ลูกค้าจ่ายจริง (หลังส่วนลด) · ยอดรายสินค้าเฉลี่ยส่วนลดตามสัดส่วน

   ตัวกรอง (GET): b = ALL | รหัสสาขา (รวมสาขาที่ปิดใช้งาน)
                  mode = recent (30 วันล่าสุด — ค่าเริ่มต้น) | day | month | year · d = Y-m-d · m = Y-m · y = Y
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

$brAll   = demo_branches_all();
$today   = strtotime(date('Y-m-d'));
$yearMin = 2026;
$g       = function ($k, $def = '') { return (isset($_GET[$k]) && is_string($_GET[$k])) ? trim($_GET[$k]) : $def; };

$fB    = isset($brAll[$g('b')]) ? $g('b') : 'ALL';
$fMode = in_array($g('mode'), array('day', 'month', 'year'), true) ? $g('mode') : 'recent';
$dayTs = $today;
if ($g('d') !== '' && ($x = strtotime($g('d'))) !== false) {
    $dayTs = min(strtotime(date('Y-m-d', $x)), $today);
}
$monTs = strtotime(date('Y-m-01', $dayTs));
if (preg_match('/^\d{4}-\d{2}$/', $g('m')) && ($x = strtotime($g('m') . '-01')) !== false) {
    $monTs = min($x, strtotime(date('Y-m-01')));
}
$year = (int) date('Y', $dayTs);
if (preg_match('/^\d{4}$/', $g('y'))) {
    $year = max($yearMin, min((int) date('Y'), (int) $g('y')));
}
list($from, $to, $rangeTxt) = adm_range($fMode, $dayTs, $monTs, $year);

$rq = function ($chg) use ($fB, $fMode, $dayTs, $monTs, $year) {
    $q = array_merge(array('b' => $fB, 'mode' => $fMode, 'd' => date('Y-m-d', $dayTs), 'm' => date('Y-m', $monTs), 'y' => $year), $chg);
    foreach (array('day' => 'd', 'month' => 'm', 'year' => 'y') as $md => $k) {
        if ($q['mode'] !== $md) {
            unset($q[$k]);
        }
    }
    foreach (array('b' => 'ALL', 'mode' => 'recent') as $k => $def) {
        if ((string) $q[$k] === $def) {
            unset($q[$k]);
        }
    }
    return 'adm-report.php' . ($q ? '?' . http_build_query($q) : '');
};

/* ---------- รวบรวม ---------- */
$codes = $fB === 'ALL' ? array_keys($brAll) : array($fB);
$sum   = array('bills' => 0, 'qty' => 0, 'total' => 0, 'disc' => 0, 'vat' => 0, 'cash' => 0, 'void' => 0);
$perB  = array();
$staff = array();
$prods = array();
$trend = array();                         // รายวัน (day/month) หรือ รายเดือน (year)

for ($d = $from; $d <= $to; $d = strtotime('+1 day', $d)) {
    $tk = $fMode === 'year' ? date('Y-m', $d) : date('Y-m-d', $d);
    if (!isset($trend[$tk])) {
        $trend[$tk] = array('ts' => $d, 'total' => 0, 'bills' => 0);
    }
    foreach ($codes as $c) {
        if (!isset($perB[$c])) {
            $perB[$c] = array('bills' => 0, 'qty' => 0, 'total' => 0);
        }
        foreach (acct_bills($c, $d) as $b) {
            if (!empty($b['void'])) {
                $sum['void']++;
                continue;
            }
            $sub = !empty($b['subtotal']) ? $b['subtotal'] : $b['total'];
            $fac = $sub > 0 ? $b['total'] / $sub : 1;
            $sum['bills']++;
            $sum['qty']   += $b['qty'];
            $sum['total'] += $b['total'];
            $sum['disc']  += !empty($b['discount']) ? $b['discount'] : 0;
            $sum['vat']   += !empty($b['vat']) ? 1 : 0;
            $sum['cash']  += $b['method'] === 'cash' ? $b['total'] : 0;
            $perB[$c]['bills']++;
            $perB[$c]['qty']   += $b['qty'];
            $perB[$c]['total'] += $b['total'];
            $trend[$tk]['total'] += $b['total'];
            $trend[$tk]['bills']++;
            $sk = $b['by_user'];
            if (!isset($staff[$sk])) {
                $staff[$sk] = array('name' => $b['by'], 'branch' => $c, 'bills' => 0, 'qty' => 0, 'total' => 0);
            }
            $staff[$sk]['bills']++;
            $staff[$sk]['qty']   += $b['qty'];
            $staff[$sk]['total'] += $b['total'];
            foreach ($b['lines'] as $l) {
                if (!isset($prods[$l['sku']])) {
                    $prods[$l['sku']] = array('name' => $l['name'], 'unit' => $l['unit'], 'qty' => 0, 'total' => 0);
                }
                $prods[$l['sku']]['qty']   += $l['qty'];
                $prods[$l['sku']]['total'] += $l['sum'] * $fac;
            }
        }
    }
}
$byTotal = function ($a, $b) { return $a['total'] == $b['total'] ? 0 : ($a['total'] < $b['total'] ? 1 : -1); };
uasort($staff, $byTotal);
uasort($prods, $byTotal);
$prods = array_slice($prods, 0, 10, true);
$maxT  = 1;
foreach ($trend as $t) {
    $maxT = max($maxT, $t['total']);
}
$maxB = 1;
foreach ($perB as $x) {
    $maxB = max($maxB, $x['total']);
}

/* ---------- CSV: ยอดขายตามช่วงเวลา (รายวัน / รายเดือนถ้าเลือกรายปี) ---------- */
if ($g('export') === 'csv') {
    $csv = array();
    foreach ($trend as $tk => $t) {
        $csv[] = array($tk, $t['bills'], csv_money($t['total']));
    }
    $csv[] = array('รวม', $sum['bills'], csv_money($sum['total']));
    csv_send('aostock-sales-' . date('Ymd', $from) . '-' . date('Ymd', $to) . ($fB !== 'ALL' ? '-' . $fB : '') . '.csv',
             array($fMode === 'year' ? 'เดือน' : 'วันที่', 'บิล', 'ยอดขาย (บาท)'), $csv);
}

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = 'ภาพรวมยอดขาย';
$PAGE_SUB       = ($fB === 'ALL' ? 'ทุกสาขา' : $brAll[$fB]['name']) . ' · ' . $rangeTxt;
$NAV_ACTIVE     = 'adm-report.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter hist-filter">
  <form method="get" action="adm-report.php" class="acct-row">
    <div class="segs">
      <?php foreach (array('recent' => '30 วัน', 'day' => 'รายวัน', 'month' => 'รายเดือน', 'year' => 'รายปี') as $md => $lb): ?>
        <a class="seg<?= $fMode === $md ? ' on' : '' ?>" href="<?= e($rq(array('mode' => $md))) ?>"><?= e($lb) ?></a>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="mode" value="<?= e($fMode) ?>">
    <?php if ($fMode === 'day'): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($rq(array('d' => date('Y-m-d', strtotime('-1 day', $dayTs))))) ?>" aria-label="วันก่อนหน้า">‹</a>
      <label class="sr-only" for="rd">วันที่</label>
      <input class="input acct-date" type="date" id="rd" name="d" value="<?= e(date('Y-m-d', $dayTs)) ?>" max="<?= e(date('Y-m-d')) ?>" onchange="this.form.submit()">
      <?php if ($dayTs < $today): ?><a class="btn btn-ghost btn-sm" href="<?= e($rq(array('d' => date('Y-m-d', strtotime('+1 day', $dayTs))))) ?>" aria-label="วันถัดไป">›</a><?php endif; ?>
    <?php elseif ($fMode === 'month'): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($rq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>" aria-label="เดือนก่อนหน้า">‹</a>
      <label class="sr-only" for="rm">เดือน</label>
      <input class="input acct-date" type="month" id="rm" name="m" value="<?= e(date('Y-m', $monTs)) ?>" max="<?= e(date('Y-m')) ?>" onchange="this.form.submit()">
      <?php if ($monTs < strtotime(date('Y-m-01'))): ?><a class="btn btn-ghost btn-sm" href="<?= e($rq(array('m' => date('Y-m', strtotime('+1 month', $monTs))))) ?>" aria-label="เดือนถัดไป">›</a><?php endif; ?>
    <?php elseif ($fMode === 'year'): ?>
      <label class="sr-only" for="ry">ปี</label>
      <select class="input acct-date" id="ry" name="y" onchange="this.form.submit()">
        <?php for ($y = (int) date('Y'); $y >= $yearMin; $y--): ?>
          <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>>ปี <?= $y + 543 ?></option>
        <?php endfor; ?>
      </select>
    <?php endif; ?>
    <label class="sr-only" for="rb">สาขา</label>
    <select class="input acct-branch" id="rb" name="b" onchange="this.form.submit()">
      <option value="ALL">ทุกสาขา</option>
      <?php foreach ($brAll as $c => $x): ?>
        <option value="<?= e($c) ?>" <?= $fB === $c ? 'selected' : '' ?>><?= e($x['name']) ?><?= empty($x['active']) ? ' (ปิดใช้งาน)' : '' ?></option>
      <?php endforeach; ?>
    </select>
      <a class="btn btn-ghost btn-sm rep-csv" href="<?= e($rq(array('export' => 'csv'))) ?>"><svg class="ico"><use href="#i-in"/></svg> ดาวน์โหลด CSV</a>
  </form>
</section>

<!-- ==================== สรุป ==================== -->
<section class="mini num adm-kpi" aria-label="สรุปยอดขาย">
  <div class="m"><div class="lb">ยอดขาย</div><div class="nm"><?= e(money2($sum['total'])) ?></div>
    <div class="sb">เงินสด <?= e(money2($sum['cash'])) ?> · โอน <?= e(money2($sum['total'] - $sum['cash'])) ?></div></div>
  <div class="m"><div class="lb">บิล</div><div class="nm"><?= number_format($sum['bills']) ?></div>
    <div class="sb">เฉลี่ย <?= e(money2($sum['bills'] ? $sum['total'] / $sum['bills'] : 0)) ?> บาท / บิล · VAT <?= number_format($sum['vat']) ?> ใบ<?= $sum['void'] ? ' · ยกเลิก ' . number_format($sum['void']) : '' ?></div></div>
  <div class="m"><div class="lb">สินค้าที่ขายได้</div><div class="nm"><?= number_format($sum['qty']) ?> ชิ้น</div>
    <div class="sb"><?= $sum['disc'] > 0 ? 'ให้ส่วนลดรวม ' . e(money2($sum['disc'])) . ' บาท' : 'ไม่มีส่วนลด' ?></div></div>
</section>

<?php if ($fMode !== 'day'): ?>
<!-- ==================== แนวโน้ม ==================== -->
<section class="card">
  <div class="card-head"><div><h2>ยอดขาย<?= $fMode === 'year' ? 'รายเดือน' : 'รายวัน' ?></h2><span class="sub"><?= e($rangeTxt) ?></span></div></div>
  <div class="rep-bars" role="list">
    <?php foreach ($trend as $t): $h = (int) round($t['total'] / $maxT * 100); ?>
      <a class="rep-bar" role="listitem" href="<?= e($fMode === 'year' ? $rq(array('mode' => 'month', 'm' => date('Y-m', $t['ts']))) : $rq(array('mode' => 'day', 'd' => date('Y-m-d', $t['ts'])))) ?>"
         title="<?= e(($fMode === 'year' ? thai_month_full($t['ts']) : thai_date_full($t['ts'])) . ' · ' . money2($t['total']) . ' บาท · ' . $t['bills'] . ' บิล') ?>">
        <span class="rep-col" style="height:<?= max($t['total'] > 0 ? 3 : 0, $h) ?>%"></span>
        <small><?= e($fMode === 'year' ? thai_month_short($t['ts']) : date('j', $t['ts'])) ?></small>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- แยกสาขา / รายพนักงาน ย้ายไปหน้าเฉพาะแล้ว -->
<section class="card rep-links">
  <a class="rep-link" href="adm-report-branch.php"><svg class="ico"><use href="#i-building"/></svg>
    <span><b>ยอดขายแยกสาขา</b><small>ตารางเปรียบเทียบแต่ละสาขา ต่อวัน / ต่อเดือน / ต่อปี</small></span></a>
  <a class="rep-link" href="adm-report-staff.php"><svg class="ico"><use href="#i-users"/></svg>
    <span><b>ยอดขายตามพนักงาน</b><small>จัดอันดับพนักงานที่ขายได้มากที่สุด</small></span></a>
  <a class="rep-link" href="adm-report-products.php"><svg class="ico"><use href="#i-boxes"/></svg>
    <span><b>สินค้าขายดี 100 อันดับ</b><small>เรียงตามจำนวนที่ขายหรือยอดขาย</small></span></a>
</section>

<!-- ==================== สินค้าขายดี ==================== -->
<section class="card">
  <div class="card-head"><div><h2>สินค้าขายดี 10 อันดับ</h2><span class="sub">ตามยอดขาย (หักส่วนลดท้ายบิลตามสัดส่วนแล้ว)</span></div>
    <a class="btn btn-ghost btn-sm" href="adm-report-products.php">ดูทั้ง 100 อันดับ ›</a></div>
  <?php if (!$prods): ?>
    <p class="empty">ไม่มียอดขายในช่วงนี้</p>
  <?php else: ?>
    <div class="tbl-wrap">
      <table class="tbl num">
        <thead><tr><th>สินค้า</th><th class="r">จำนวน</th><th class="r">ยอดขาย</th></tr></thead>
        <tbody>
          <?php $i = 0; foreach ($prods as $sku => $p): $i++; ?>
            <tr>
              <td><b class="hist-t"><span class="rep-rank"><?= $i ?></span><?= e($p['name']) ?></b><small class="hist-n"><?= e($sku) ?></small></td>
              <td data-label="จำนวน" class="r"><?= number_format($p['qty']) ?> <?= e($p['unit']) ?></td>
              <td data-label="ยอดขาย" class="r"><b><?= e(money2($p['total'])) ?></b></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
