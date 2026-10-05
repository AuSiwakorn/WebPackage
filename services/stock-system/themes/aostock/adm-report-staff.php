<?php
/**
 * FILE: themes/aostock/adm-report-staff.php
 * ROLE: [ผู้ดูแล] ยอดขายตามพนักงาน (จัดอันดับ)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_sale, ao_stock_staff, ao_stock_branch (ผ่าน api.php — sales_scan)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 8: SQL ทั้งช่วง (เดิมวนบิลทีละวันทีละสาขา)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   [ผู้ดูแล] ยอดขายตามพนักงาน (จัดอันดับ)
   ----------------------------------------------------------
   หาพนักงานขายที่ขายได้มากสุดเรียงตามลำดับ
     mode = recent (30 วันล่าสุด — ค่าเริ่มต้น) | day | month | year · d / m / y
     b    = ALL | รหัสสาขา (นับเฉพาะบิลของสาขานั้น)
     sort = total (ยอดขาย) | bills | qty | avg (เฉลี่ยต่อบิล)
   แสดงพนักงานทุกคน — คนที่ไม่มียอดในช่วงนี้แสดงท้ายตาราง (ยอด 0) · คนที่พักงาน / ลาออก มีป้ายกำกับ
   ข้อมูล: บิลชุดเดียวกับหน้าบัญชี / รายงาน (ไม่นับบิลยกเลิก) — sales_scan()
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

$brAll   = branches_all();
$today   = strtotime(date('Y-m-d'));
$yearMin = 2026;
$g       = function ($k, $def = '') { return (isset($_GET[$k]) && is_string($_GET[$k])) ? trim($_GET[$k]) : $def; };

$fB    = isset($brAll[$g('b')]) ? $g('b') : 'ALL';
$fMode = in_array($g('mode'), array('day', 'month', 'year'), true) ? $g('mode') : 'recent';
$fSort = in_array($g('sort'), array('total', 'bills', 'qty', 'avg'), true) ? $g('sort') : 'total';
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

$sq = function ($chg) use ($fB, $fMode, $fSort, $dayTs, $monTs, $year) {
    $q = array_merge(array('b' => $fB, 'mode' => $fMode, 'd' => date('Y-m-d', $dayTs), 'm' => date('Y-m', $monTs), 'y' => $year,
                           'sort' => $fSort), $chg);
    foreach (array('day' => 'd', 'month' => 'm', 'year' => 'y') as $md => $k) {
        if ($q['mode'] !== $md) {
            unset($q[$k]);
        }
    }
    foreach (array('b' => 'ALL', 'mode' => 'recent', 'sort' => 'total') as $k => $def) {
        if ((string) $q[$k] === $def) {
            unset($q[$k]);
        }
    }
    return 'adm-report-staff.php' . ($q ? '?' . http_build_query($q) : '');
};

$scan  = sales_scan($fB === 'ALL' ? array_keys($brAll) : array($fB), $from, $to);
$staff = $scan['staff'];

/* แสดงพนักงานทุกคน — คนที่ไม่มียอดในช่วงนี้ (รวมคนที่พักงาน / ลาออก) ยอด 0 ต่อท้าย */
$allUsers = users_all();
foreach ($allUsers as $un => $u) {
    if ($u['role'] !== 'staff' || isset($staff[$un])) {
        continue;
    }
    if ($fB !== 'ALL' && $u['branch'] !== $fB) {
        continue;
    }
    $staff[$un] = array('name' => $u['name'], 'branch' => $u['branch'], 'bills' => 0, 'qty' => 0, 'total' => 0, 'disc' => 0, 'days' => array());
}
foreach ($staff as $un => $s) {
    $staff[$un]['off'] = isset($allUsers[$un]) && !user_active($allUsers[$un]);
}

$grand = 0;
foreach ($staff as $un => $s) {
    $staff[$un]['avg'] = $s['bills'] ? $s['total'] / $s['bills'] : 0;
    $grand += $s['total'];
}
uasort($staff, function ($a, $b) use ($fSort) {
    if ($a[$fSort] == $b[$fSort]) {
        return $a['total'] == $b['total'] ? 0 : ($a['total'] < $b['total'] ? 1 : -1);
    }
    return $a[$fSort] < $b[$fSort] ? 1 : -1;
});
$top = reset($staff);
$max = 0;
foreach ($staff as $s) {
    $max = max($max, $s[$fSort]);
}
$sortLb = array('total' => 'ยอดขาย', 'bills' => 'จำนวนบิล', 'qty' => 'จำนวนชิ้น', 'avg' => 'เฉลี่ยต่อบิล');

/* ---------- CSV ---------- */
if ($g('export') === 'csv') {
    $csv = array();
    $i = 0;
    foreach ($staff as $un => $s) {
        $i++;
        $csv[] = array($i, $s['name'], $un, isset($brAll[$s['branch']]) ? $brAll[$s['branch']]['name'] : $s['branch'],
                       !empty($s['off']) ? 'พักงาน / ลาออก' : 'ใช้งาน', count($s['days']), $s['bills'], $s['qty'],
                       csv_money($s['avg']), csv_money($s['disc']), csv_money($s['total']),
                       $grand > 0 ? round($s['total'] / $grand * 100, 2) : 0);
    }
    csv_send('aostock-sales-by-staff-' . date('Ymd', $from) . '-' . date('Ymd', $to) . ($fB !== 'ALL' ? '-' . $fB : '') . '.csv',
             array('อันดับ', 'พนักงาน', 'ชื่อผู้ใช้', 'สาขา', 'สถานะ', 'วันที่ขาย', 'บิล', 'ชิ้น', 'เฉลี่ยต่อบิล', 'ส่วนลดที่ให้', 'ยอดขาย', '% ของยอดรวม'),
             $csv);
}

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = 'ยอดขายตามพนักงาน';
$PAGE_SUB       = ($fB === 'ALL' ? 'ทุกสาขา' : $brAll[$fB]['name']) . ' · ' . $rangeTxt . ' · เรียงตาม' . $sortLb[$fSort];
$NAV_ACTIVE     = 'adm-report-staff.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter hist-filter">
  <form method="get" action="adm-report-staff.php" class="acct-row">
    <div class="segs">
      <?php foreach (array('recent' => '30 วัน', 'day' => 'รายวัน', 'month' => 'รายเดือน', 'year' => 'รายปี') as $md => $lb): ?>
        <a class="seg<?= $fMode === $md ? ' on' : '' ?>" href="<?= e($sq(array('mode' => $md))) ?>"><?= e($lb) ?></a>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="mode" value="<?= e($fMode) ?>">
    <?php if ($fMode === 'day'): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($sq(array('d' => date('Y-m-d', strtotime('-1 day', $dayTs))))) ?>" aria-label="วันก่อนหน้า">‹</a>
      <label class="sr-only" for="sd">วันที่</label>
      <input class="input acct-date" type="date" id="sd" name="d" value="<?= e(date('Y-m-d', $dayTs)) ?>" max="<?= e(date('Y-m-d')) ?>" onchange="this.form.submit()">
      <?php if ($dayTs < $today): ?><a class="btn btn-ghost btn-sm" href="<?= e($sq(array('d' => date('Y-m-d', strtotime('+1 day', $dayTs))))) ?>" aria-label="วันถัดไป">›</a><?php endif; ?>
    <?php elseif ($fMode === 'month'): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($sq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>" aria-label="เดือนก่อนหน้า">‹</a>
      <label class="sr-only" for="sm">เดือน</label>
      <input class="input acct-date" type="month" id="sm" name="m" value="<?= e(date('Y-m', $monTs)) ?>" max="<?= e(date('Y-m')) ?>" onchange="this.form.submit()">
      <?php if ($monTs < strtotime(date('Y-m-01'))): ?><a class="btn btn-ghost btn-sm" href="<?= e($sq(array('m' => date('Y-m', strtotime('+1 month', $monTs))))) ?>" aria-label="เดือนถัดไป">›</a><?php endif; ?>
    <?php elseif ($fMode === 'year'): ?>
      <label class="sr-only" for="sy">ปี</label>
      <select class="input acct-date" id="sy" name="y" onchange="this.form.submit()">
        <?php for ($y = (int) date('Y'); $y >= $yearMin; $y--): ?>
          <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>>ปี <?= $y + 543 ?></option>
        <?php endfor; ?>
      </select>
    <?php endif; ?>
    <label class="sr-only" for="sb">สาขา</label>
    <select class="input acct-branch" id="sb" name="b" onchange="this.form.submit()">
      <option value="ALL">ทุกสาขา</option>
      <?php foreach ($brAll as $c => $x): ?>
        <option value="<?= e($c) ?>" <?= $fB === $c ? 'selected' : '' ?>><?= e($x['name']) ?><?= empty($x['active']) ? ' (ปิดใช้งาน)' : '' ?></option>
      <?php endforeach; ?>
    </select>
    <label class="sr-only" for="ss">เรียงตาม</label>
    <select class="input acct-branch" id="ss" name="sort" onchange="this.form.submit()">
      <?php foreach ($sortLb as $k => $lb): ?>
        <option value="<?= e($k) ?>" <?= $fSort === $k ? 'selected' : '' ?>>เรียงตาม<?= e($lb) ?></option>
      <?php endforeach; ?>
    </select>
      <a class="btn btn-ghost btn-sm rep-csv" href="<?= e($sq(array('export' => 'csv'))) ?>"><svg class="ico"><use href="#i-in"/></svg> ดาวน์โหลด CSV</a>
  </form>
</section>

<!-- ==================== 3 อันดับแรก ==================== -->
<section class="mini num adm-kpi" aria-label="3 อันดับแรก">
  <?php $i = 0; foreach ($staff as $s): $i++; if ($i > 3) { break; } ?>
    <div class="m rep-podium p<?= $i ?>">
      <div class="lb"><span class="rep-rank"><?= $i ?></span><?= e($s['name']) ?> · <?= e(isset($brAll[$s['branch']]) ? $brAll[$s['branch']]['short'] : $s['branch']) ?></div>
      <div class="nm"><?= e(money2($s['total'])) ?></div>
      <div class="sb"><?= number_format($s['bills']) ?> บิล · <?= number_format($s['qty']) ?> ชิ้น · <?= $grand > 0 ? number_format($s['total'] / $grand * 100, 1) : '0.0' ?>% ของยอดรวม</div>
    </div>
  <?php endforeach; ?>
</section>

<!-- ==================== ตารางอันดับ ==================== -->
<section class="card">
  <div class="card-head">
    <div>
      <h2>อันดับพนักงานขาย · <?= e($rangeTxt) ?></h2>
      <span class="sub">ยอดรวม <?= e(money2($grand)) ?> บาท · พนักงานทั้งหมด <?= count($staff) ?> คน (มียอด <?= count(array_filter($staff, function ($x) { return $x['total'] > 0; })) ?> คน) · ไม่นับบิลที่ยกเลิก · ยอดผูกกับสาขาที่ขาย ณ ตอนนั้น</span>
    </div>
  </div>
  <?php if (!$staff): ?>
    <p class="empty">ไม่มีข้อมูลในช่วงนี้</p>
  <?php else: ?>
    <div class="tbl-wrap">
      <table class="tbl hist-all num">
        <thead>
          <tr>
            <th>พนักงาน</th>
            <th class="r">วันที่ขาย</th>
            <th class="r">บิล</th>
            <th class="r">ชิ้น</th>
            <th class="r">เฉลี่ย / บิล</th>
            <th class="r">ให้ส่วนลด</th>
            <th class="r">ยอดขาย</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 0; foreach ($staff as $un => $s): $i++; ?>
            <tr>
              <td data-label="พนักงาน">
                <b class="hist-t"><span class="rep-rank<?= $i <= 3 && $s['total'] > 0 ? ' r' . $i : '' ?>"><?= $i ?></span><?= e($s['name']) ?>
                  <?php if (!empty($s['off'])): ?><span class="bdg bdg-out">พักงาน / ลาออก</span><?php elseif ($s['total'] == 0): ?><span class="bdg bdg-adj">ยังไม่มียอด</span><?php endif; ?></b>
                <small class="hist-n"><?= e(isset($brAll[$s['branch']]) ? $brAll[$s['branch']]['name'] : $s['branch']) ?> · <?= e($un) ?></small>
                <span class="rep-share"><i style="width:<?= $max > 0 ? (int) round($s[$fSort] / $max * 100) : 0 ?>%"></i></span>
              </td>
              <td data-label="วันที่ขาย" class="r"><?= number_format(count($s['days'])) ?> วัน</td>
              <td data-label="บิล" class="r"><?= number_format($s['bills']) ?></td>
              <td data-label="ชิ้น" class="r"><?= number_format($s['qty']) ?></td>
              <td data-label="เฉลี่ย / บิล" class="r"><?= e(money2($s['avg'])) ?></td>
              <td data-label="ให้ส่วนลด" class="r"><?= $s['disc'] > 0 ? e(money2($s['disc'])) : '—' ?></td>
              <td data-label="ยอดขาย" class="r"><b><?= e(money2($s['total'])) ?></b><small class="hist-n"><?= $grand > 0 ? number_format($s['total'] / $grand * 100, 1) : '0.0' ?>%</small></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
