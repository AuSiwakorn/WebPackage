<?php
/* ==========================================================
   AOSTOCK DEMO — [ผู้ดูแล] ตรวจสอบการเบิก / ตัดออก ทุกสาขา
   ----------------------------------------------------------
   ผู้ดูแลไม่เบิก / ตัดออกเอง (เป็นหน้าที่ของพนักงานที่มีสิทธิ์ issue)
   หน้านี้แสดงทุกรายการสินค้าที่ถูกเบิก / ตัดออก — แถวละ 1 สินค้าในใบ

   ตัวกรอง (GET)
     b     = ALL | รหัสสาขา (รวมสาขาที่ปิดใช้งาน)
     mode  = day | month | year · d = Y-m-d · m = Y-m · y = Y
     why   = เหตุผล (คีย์ของ issue_reasons)
     void  = 1 แสดงใบที่ยกเลิกแล้วด้วย (ค่าเริ่มต้นซ่อน)
     q     = ค้นชื่อสินค้า / SKU / เลขที่ใบ
     sort  = time | price | value | qty · dir = desc | asc
     p     = หน้า (ทีละ 100 แถว)

   ราคาทุน / ราคาขาย ใช้ราคาปัจจุบันของสินค้า
   ระบบจริง: ใช้ราคาทุนที่ snapshot ไว้ใน ao_stock_issue_item ตอนบันทึก
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();
if ($user['role'] !== 'admin') {
    header('Location: ' . url('issue.php'));
    exit;
}

$brAll   = demo_branches_all();
$reasons = issue_reasons();
$today   = strtotime(date('Y-m-d'));
$yearMin = 2026;                                   // ปีแรกที่ระบบมีข้อมูล

/* ---------- อ่านตัวกรอง ---------- */
$g     = function ($k, $def = '') { return (isset($_GET[$k]) && is_string($_GET[$k])) ? trim($_GET[$k]) : $def; };
$fB    = isset($brAll[$g('b')]) ? $g('b') : 'ALL';
$fMode = in_array($g('mode'), array('day', 'month', 'year'), true) ? $g('mode') : 'month';
$fWhy  = isset($reasons[$g('why')]) ? $g('why') : '';
$fVoid = $g('void') === '1';
$fQ    = substr($g('q'), 0, 120);
$fSort = in_array($g('sort'), array('time', 'price', 'value', 'qty'), true) ? $g('sort') : 'time';
$fDir  = $g('dir') === 'asc' ? 'asc' : 'desc';

$dayTs = $today;
if (($x = strtotime($g('d'))) !== false && $g('d') !== '') {
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

if ($fMode === 'day') {
    $from = $dayTs;
    $to   = $dayTs;
    $rangeTxt = thai_date_full($dayTs);
} elseif ($fMode === 'month') {
    $from = $monTs;
    $to   = min(strtotime(date('Y-m-t', $monTs)), $today);
    $rangeTxt = thai_month_full($monTs);
} else {
    $from = strtotime($year . '-01-01');
    $to   = min(strtotime($year . '-12-31'), $today);
    $rangeTxt = 'ปี ' . ($year + 543);
}

/** ลิงก์ของหน้านี้ โดยเปลี่ยนค่าบางตัว */
$aq = function ($chg) use ($fB, $fMode, $fWhy, $fVoid, $fQ, $fSort, $fDir, $dayTs, $monTs, $year) {
    $q = array_merge(array('b' => $fB, 'mode' => $fMode, 'd' => date('Y-m-d', $dayTs), 'm' => date('Y-m', $monTs),
                           'y' => $year, 'why' => $fWhy, 'void' => $fVoid ? '1' : '', 'q' => $fQ,
                           'sort' => $fSort, 'dir' => $fDir), $chg);
    foreach (array('day' => 'd', 'month' => 'm', 'year' => 'y') as $md => $key) {
        if ($q['mode'] !== $md) {
            unset($q[$key]);
        }
    }
    foreach (array('b' => 'ALL', 'mode' => 'month', 'why' => '', 'void' => '', 'q' => '', 'sort' => 'time', 'dir' => 'desc', 'p' => 1) as $k => $def) {
        if (isset($q[$k]) && (string) $q[$k] === (string) $def) {
            unset($q[$k]);
        }
    }
    return 'adm-issue.php' . ($q ? '?' . http_build_query($q) : '');
};

/* ---------- รวบรวม ---------- */
$codes = $fB === 'ALL' ? array_keys($brAll) : array($fB);
$rows  = array();
for ($d = $from; $d <= $to; $d = strtotime('+1 day', $d)) {
    foreach ($codes as $c) {
        foreach (issue_rows_of_day($c, $d) as $r) {
            $rows[] = $r;
        }
    }
}

/* ค้นหา + ใบยกเลิก (ก่อนนับเหตุผล) */
$rows = array_values(array_filter($rows, function ($r) use ($fVoid, $fQ) {
    if (!$fVoid && $r['void']) {
        return false;
    }
    if ($fQ !== '') {
        return stripos($r['name'] . ' ' . $r['sku'] . ' ' . $r['no'] . ' ' . $r['note'], $fQ) !== false;
    }
    return true;
}));

$byWhy = array();
foreach ($rows as $r) {
    $byWhy[$r['reason']] = (isset($byWhy[$r['reason']]) ? $byWhy[$r['reason']] : 0) + 1;
}
$allCount = count($rows);
if ($fWhy !== '') {
    $rows = array_values(array_filter($rows, function ($r) use ($fWhy) { return $r['reason'] === $fWhy; }));
}

/* สรุป (ไม่รวมใบที่ยกเลิก) */
$sum = array('docs' => array(), 'qty' => 0, 'value' => 0, 'sale' => 0, 'perB' => array(), 'void' => 0);
foreach ($rows as $r) {
    if ($r['void']) {
        $sum['void']++;
        continue;
    }
    $sum['docs'][$r['branch'] . $r['no']] = true;
    $sum['qty']   += $r['qty'];
    $sum['value'] += $r['value'];
    $sum['sale']  += $r['price'] * $r['qty'];
    $sum['perB'][$r['branch']] = (isset($sum['perB'][$r['branch']]) ? $sum['perB'][$r['branch']] : 0) + $r['value'];
}

/* เรียงลำดับ */
$dir = $fDir === 'asc' ? 1 : -1;
$key = array('time' => 'ts', 'price' => 'cost', 'value' => 'value', 'qty' => 'qty');
$key = $key[$fSort];
usort($rows, function ($a, $b) use ($key, $dir) {
    if ($a[$key] != $b[$key]) {
        return ($a[$key] < $b[$key] ? -1 : 1) * $dir;
    }
    if ($a['ts'] !== $b['ts']) {
        return $a['ts'] < $b['ts'] ? 1 : -1;            // ค่าเท่ากัน → ใหม่ก่อน
    }
    return $a['seq'] - $b['seq'];
});

$per   = 100;
$total = count($rows);
$pages = max(1, (int) ceil($total / $per));
$pg    = max(1, min($pages, (int) $g('p', '1')));
$show  = array_slice($rows, ($pg - 1) * $per, $per);

/** หัวคอลัมน์ที่กดเรียงได้ */
$th = function ($k, $label, $cls = '') use ($fSort, $fDir, $aq) {
    $on   = ($fSort === $k);
    $next = ($on && $fDir === 'desc') ? 'asc' : 'desc';
    $mark = $on ? ($fDir === 'desc' ? ' ↓' : ' ↑') : '';
    return '<th class="' . $cls . '"' . ($on ? ' aria-sort="' . ($fDir === 'desc' ? 'descending' : 'ascending') . '"' : '') . '>'
         . '<a class="th-sort' . ($on ? ' on' : '') . '" href="' . e($aq(array('sort' => $k, 'dir' => $next))) . '">'
         . e($label) . $mark . '</a></th>';
};

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = 'เบิก / ตัดออก';
$PAGE_SUB       = 'ตรวจสอบการเบิก / ตัดออกของทุกสาขา · ' . $rangeTxt;
$NAV_ACTIVE     = 'adm-issue.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter hist-filter">
  <form method="get" action="adm-issue.php" class="acct-row">
    <div class="segs">
      <?php foreach (array('day' => 'รายวัน', 'month' => 'รายเดือน', 'year' => 'รายปี') as $md => $lb): ?>
        <a class="seg<?= $fMode === $md ? ' on' : '' ?>" href="<?= e($aq(array('mode' => $md))) ?>"><?= e($lb) ?></a>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="mode" value="<?= e($fMode) ?>">

    <?php if ($fMode === 'day'): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($aq(array('d' => date('Y-m-d', strtotime('-1 day', $dayTs))))) ?>" aria-label="วันก่อนหน้า">‹</a>
      <label class="sr-only" for="fd">วันที่</label>
      <input class="input acct-date" type="date" id="fd" name="d" value="<?= e(date('Y-m-d', $dayTs)) ?>" max="<?= e(date('Y-m-d')) ?>" onchange="this.form.submit()">
      <?php if ($dayTs < $today): ?>
        <a class="btn btn-ghost btn-sm" href="<?= e($aq(array('d' => date('Y-m-d', strtotime('+1 day', $dayTs))))) ?>" aria-label="วันถัดไป">›</a>
      <?php endif; ?>
    <?php elseif ($fMode === 'month'): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($aq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>" aria-label="เดือนก่อนหน้า">‹</a>
      <label class="sr-only" for="fm">เดือน</label>
      <input class="input acct-date" type="month" id="fm" name="m" value="<?= e(date('Y-m', $monTs)) ?>" max="<?= e(date('Y-m')) ?>" onchange="this.form.submit()">
      <?php if ($monTs < strtotime(date('Y-m-01'))): ?>
        <a class="btn btn-ghost btn-sm" href="<?= e($aq(array('m' => date('Y-m', strtotime('+1 month', $monTs))))) ?>" aria-label="เดือนถัดไป">›</a>
      <?php endif; ?>
    <?php else: ?>
      <label class="sr-only" for="fy">ปี</label>
      <select class="input acct-date" id="fy" name="y" onchange="this.form.submit()">
        <?php for ($y = (int) date('Y'); $y >= $yearMin; $y--): ?>
          <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>>ปี <?= $y + 543 ?></option>
        <?php endfor; ?>
      </select>
    <?php endif; ?>

    <label class="sr-only" for="fb">สาขา</label>
    <select class="input acct-branch" id="fb" name="b" onchange="this.form.submit()">
      <option value="ALL">ทุกสาขา</option>
      <?php foreach ($brAll as $c => $x): ?>
        <option value="<?= e($c) ?>" <?= $fB === $c ? 'selected' : '' ?>><?= e($x['name']) ?><?= empty($x['active']) ? ' (ปิดใช้งาน)' : '' ?></option>
      <?php endforeach; ?>
    </select>

    <label class="sr-only" for="fs">เรียงตาม</label>
    <select class="input acct-branch" id="fs" name="sd" onchange="var v=this.value.split('|');this.form.sort.value=v[0];this.form.dir.value=v[1];this.form.submit()">
      <?php foreach (array(
          'time|desc'  => 'ลำดับเวลา: ใหม่ → เก่า',
          'time|asc'   => 'ลำดับเวลา: เก่า → ใหม่',
          'price|desc' => 'ราคาสินค้า: มาก → น้อย',
          'price|asc'  => 'ราคาสินค้า: น้อย → มาก',
          'value|desc' => 'มูลค่ารวม: มาก → น้อย',
          'value|asc'  => 'มูลค่ารวม: น้อย → มาก',
          'qty|desc'   => 'จำนวน: มาก → น้อย',
          'qty|asc'    => 'จำนวน: น้อย → มาก',
      ) as $k => $lb): ?>
        <option value="<?= e($k) ?>" <?= $k === $fSort . '|' . $fDir ? 'selected' : '' ?>><?= e($lb) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="hidden" name="sort" value="<?= e($fSort) ?>">
    <input type="hidden" name="dir" value="<?= e($fDir) ?>">

    <label class="sr-only" for="fq">ค้นหา</label>
    <input class="input adm-q" type="search" id="fq" name="q" value="<?= e($fQ) ?>" placeholder="ค้นชื่อสินค้า / SKU / เลขที่ใบ">
    <?php if ($fWhy !== ''): ?><input type="hidden" name="why" value="<?= e($fWhy) ?>"><?php endif; ?>
    <?php if ($fVoid): ?><input type="hidden" name="void" value="1"><?php endif; ?>
    <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-search"/></svg> ค้นหา</button>
  </form>
</section>

<!-- ==================== สรุป ==================== -->
<section class="mini num adm-kpi" aria-label="สรุปการเบิก / ตัดออก">
  <div class="m"><div class="lb">ใบเบิก / ตัดออก</div><div class="nm"><?= number_format(count($sum['docs'])) ?></div>
    <div class="sb"><?= number_format($sum['qty']) ?> ชิ้น<?= $sum['void'] ? ' · ยกเลิก ' . number_format($sum['void']) . ' รายการ' : '' ?></div></div>
  <div class="m"><div class="lb">มูลค่าทุนที่ออกจากสต๊อก</div><div class="nm"><?= e(money2($sum['value'])) ?></div>
    <div class="sb">ถ้าขายได้ตามราคาขาย <?= e(money2($sum['sale'])) ?> บาท</div></div>
  <div class="m"><div class="lb">แยกสาขา (มูลค่าทุน)</div>
    <div class="sb adm-perb"><?php if (!$sum['perB']): ?>ไม่มีรายการ<?php else: arsort($sum['perB']); foreach ($sum['perB'] as $c => $v): ?>
      <span><b><?= e($brAll[$c]['short']) ?></b> <?= e(money2($v)) ?></span>
    <?php endforeach; endif; ?></div></div>
</section>

<!-- ==================== รายการ ==================== -->
<section class="card">
  <div class="card-head">
    <div>
      <h2><?= $fB === 'ALL' ? 'ทุกสาขา' : e($brAll[$fB]['name']) ?> · <?= e($rangeTxt) ?></h2>
      <span class="sub">แถวละ 1 สินค้า · เบิก / ตัดออกทำโดยพนักงานที่ได้รับสิทธิ์ · กดหัวคอลัมน์เพื่อเรียง</span>
    </div>
    <a class="btn btn-ghost btn-sm" href="<?= e($aq(array('void' => $fVoid ? '' : '1'))) ?>"><?= $fVoid ? 'ซ่อนใบที่ยกเลิก' : 'แสดงใบที่ยกเลิกด้วย' ?></a>
  </div>

  <div class="cats">
    <a class="cat<?= $fWhy === '' ? ' on' : '' ?>" href="<?= e($aq(array('why' => ''))) ?>">ทุกเหตุผล <i><?= number_format($allCount) ?></i></a>
    <?php foreach ($reasons as $k => $x): ?>
      <?php if (empty($byWhy[$k]) && $fWhy !== $k) { continue; } ?>
      <a class="cat<?= $fWhy === $k ? ' on' : '' ?>" href="<?= e($aq(array('why' => $k))) ?>"><?= e($x['label']) ?> <i><?= number_format(isset($byWhy[$k]) ? $byWhy[$k] : 0) ?></i></a>
    <?php endforeach; ?>
  </div>

  <?php if (!$show): ?>
    <p class="empty"><svg class="ico"><use href="#i-out"/></svg>ไม่มีการเบิก / ตัดออกในช่วงที่เลือก<br><small>ลองเปลี่ยนวัน สาขา เหตุผล หรือคำค้น</small></p>
  <?php else: ?>
    <div class="tbl-wrap">
      <table class="tbl hist-all adm-iss">
        <thead>
          <tr>
            <?= $th('time', $fMode === 'day' ? 'เวลา' : 'วันที่ · เวลา') ?>
            <th>สาขา</th>
            <th>สินค้า</th>
            <?= $th('qty', 'จำนวน', 'r') ?>
            <?= $th('price', 'ราคาทุน / ชิ้น', 'r') ?>
            <?= $th('value', 'มูลค่ารวม', 'r') ?>
            <th>เหตุผล</th>
            <th>ผู้ทำ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($show as $r): ?>
            <tr<?= $r['void'] ? ' class="is-void"' : '' ?>>
              <td class="num nowrap"><?php if ($fMode !== 'day'): ?><?= e(thai_day_month(strtotime($r['date']))) ?> · <?php endif; ?><?= e($r['time']) ?>
                <small class="hist-n"><?= e($r['no']) ?></small></td>
              <td><span class="hist-br"><?= e($brAll[$r['branch']]['short']) ?></span></td>
              <td>
                <b class="hist-t"><?= e($r['name']) ?></b>
                <small class="hist-n"><?= e($r['sku']) ?><?= $r['cat'] !== '' ? ' · ' . e($r['cat']) : '' ?></small>
                <?php if ($r['void']): ?><small class="hist-n hist-void">ยกเลิกโดย <?= e($r['void_by']) ?><?= $r['void_reason'] !== '' ? ' — ' . e($r['void_reason']) : '' ?></small><?php endif; ?>
              </td>
              <td class="r num nowrap"><?= number_format($r['qty']) ?> <?= e($r['unit']) ?></td>
              <td class="r num nowrap"><?= e(money2($r['cost'])) ?><small class="hist-n">ขาย <?= e(money2($r['price'])) ?></small></td>
              <td class="r num nowrap"><b><?= e(money2($r['value'])) ?></b></td>
              <td>
                <span class="bdg <?= in_array($r['reason'], array('lost', 'damaged', 'expired'), true) ? 'bdg-out' : 'bdg-adj' ?>"><?= e(issue_reason_label($r['reason'])) ?></span>
                <?php if ($r['note'] !== ''): ?><small class="hist-n"><?= e($r['note']) ?></small><?php endif; ?>
                <?php if ($r['ref'] !== ''): ?><small class="hist-n">อ้างอิง <?= e($r['ref']) ?></small><?php endif; ?>
              </td>
              <td class="nowrap"><?= e($r['by']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1): ?>
      <nav class="hist-pager" aria-label="หน้า">
        <?php if ($pg > 1): ?><a class="btn btn-ghost btn-sm" href="<?= e($aq(array('p' => $pg - 1))) ?>">‹ ก่อนหน้า</a><?php endif; ?>
        <span>หน้า <?= $pg ?> / <?= $pages ?> · <?= number_format($total) ?> รายการ</span>
        <?php if ($pg < $pages): ?><a class="btn btn-ghost btn-sm" href="<?= e($aq(array('p' => $pg + 1))) ?>">ถัดไป ›</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
