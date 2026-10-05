<?php
/**
 * FILE: themes/aostock/adm-issue.php
 * ROLE: [ผู้ดูแล] ตรวจสอบการเบิก / ตัดออก ทุกสาขา
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_issue, ao_stock_issue_item (ผ่าน api.php)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] อ่านใบเบิกจากฐานข้อมูลทั้งช่วงในคิวรีเดียว (ช่วงที่ 6) · ราคาทุนใช้ snapshot ในใบ
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   [ผู้ดูแล] ตรวจสอบการเบิก / ตัดออก ทุกสาขา
   ----------------------------------------------------------
   ผู้ดูแลไม่เบิก / ตัดออกเอง (เป็นหน้าที่ของพนักงานที่มีสิทธิ์ issue)
   หน้านี้แสดงทุกรายการสินค้าที่ถูกเบิก / ตัดออก — แถวละ 1 สินค้าในใบ

   ตัวกรอง (GET)
     b     = ALL | รหัสสาขา (รวมสาขาที่ปิดใช้งาน)
     mode  = recent (30 วันล่าสุด — ค่าเริ่มต้น) | day | month | year · d = Y-m-d · m = Y-m · y = Y
     why   = เหตุผล (คีย์ของ issue_reasons)
     void  = 1 แสดงใบที่ยกเลิกแล้วด้วย (ค่าเริ่มต้นซ่อน)
     q     = ค้นชื่อสินค้า / SKU / เลขที่ใบ
     sort  = time | price | value | qty · dir = desc | asc
     p     = หน้า (ทีละ 100 แถว)

   ราคาทุน / มูลค่า ใช้ราคาทุนที่ snapshot ไว้ใน ao_stock_issue_item ตอนบันทึก · ราคาขายใช้ราคาปัจจุบันของสินค้า
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();
if ($user['role'] !== 'admin') {
    header('Location: ' . url('issue.php'));
    exit;
}

$brAll   = branches_all();
$reasons = issue_reasons();
$today   = strtotime(date('Y-m-d'));
$yearMin = 2026;                                   // ปีแรกที่ระบบมีข้อมูล

/* ---------- อ่านตัวกรอง ---------- */
$g     = function ($k, $def = '') { return (isset($_GET[$k]) && is_string($_GET[$k])) ? trim($_GET[$k]) : $def; };
$fB    = isset($brAll[$g('b')]) ? $g('b') : 'ALL';
$fMode = in_array($g('mode'), array('day', 'month', 'year'), true) ? $g('mode') : 'recent';
$fWhy  = isset($reasons[$g('why')]) ? $g('why') : '';
$fVoid = $g('void') === '1';
$fQ    = (string) substr($g('q'), 0, 120);
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

list($from, $to, $rangeTxt) = adm_range($fMode, $dayTs, $monTs, $year);

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
    foreach (array('b' => 'ALL', 'mode' => 'recent', 'why' => '', 'void' => '', 'q' => '', 'sort' => 'time', 'dir' => 'desc', 'p' => 1) as $k => $def) {
        if (isset($q[$k]) && (string) $q[$k] === (string) $def) {
            unset($q[$k]);
        }
    }
    return 'adm-issue.php' . ($q ? '?' . http_build_query($q) : '');
};

/* ---------- รวบรวม ---------- */
$codes = $fB === 'ALL' ? array_keys($brAll) : array($fB);
$rows  = issue_rows_range($codes, $from, $to);          // ทั้งช่วงในคิวรีเดียว

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
      <?php foreach (array('recent' => '30 วัน', 'day' => 'รายวัน', 'month' => 'รายเดือน', 'year' => 'รายปี') as $md => $lb) { ?>
        <a class="seg<?php echo $fMode === $md ? ' on' : '' ?>" href="<?php echo e($aq(array('mode' => $md))) ?>"><?php echo e($lb) ?></a>
      <?php } ?>
    </div>
    <input type="hidden" name="mode" value="<?php echo e($fMode) ?>">

    <?php if ($fMode === 'day') { ?>
      <a class="btn btn-ghost btn-sm" href="<?php echo e($aq(array('d' => date('Y-m-d', strtotime('-1 day', $dayTs))))) ?>" aria-label="วันก่อนหน้า">‹</a>
      <label class="sr-only" for="fd">วันที่</label>
      <input class="input acct-date" type="date" id="fd" name="d" value="<?php echo e(date('Y-m-d', $dayTs)) ?>" max="<?php echo e(date('Y-m-d')) ?>" onchange="this.form.submit()">
      <?php if ($dayTs < $today) { ?>
        <a class="btn btn-ghost btn-sm" href="<?php echo e($aq(array('d' => date('Y-m-d', strtotime('+1 day', $dayTs))))) ?>" aria-label="วันถัดไป">›</a>
      <?php } ?>
    <?php } elseif ($fMode === 'month') { ?>
      <a class="btn btn-ghost btn-sm" href="<?php echo e($aq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>" aria-label="เดือนก่อนหน้า">‹</a>
      <label class="sr-only" for="fm">เดือน</label>
      <input class="input acct-date" type="month" id="fm" name="m" value="<?php echo e(date('Y-m', $monTs)) ?>" max="<?php echo e(date('Y-m')) ?>" onchange="this.form.submit()">
      <?php if ($monTs < strtotime(date('Y-m-01'))) { ?>
        <a class="btn btn-ghost btn-sm" href="<?php echo e($aq(array('m' => date('Y-m', strtotime('+1 month', $monTs))))) ?>" aria-label="เดือนถัดไป">›</a>
      <?php } ?>
    <?php } elseif ($fMode === 'year') { ?>
      <label class="sr-only" for="fy">ปี</label>
      <select class="input acct-date" id="fy" name="y" onchange="this.form.submit()">
        <?php for ($y = (int) date('Y'); $y >= $yearMin; $y--) { ?>
          <option value="<?php echo $y ?>" <?php echo $y === $year ? 'selected' : '' ?>>ปี <?php echo $y + 543 ?></option>
        <?php } ?>
      </select>
    <?php } ?>

    <label class="sr-only" for="fb">สาขา</label>
    <select class="input acct-branch" id="fb" name="b" onchange="this.form.submit()">
      <option value="ALL">ทุกสาขา</option>
      <?php foreach ($brAll as $c => $x) { ?>
        <option value="<?php echo e($c) ?>" <?php echo $fB === $c ? 'selected' : '' ?>><?php echo e($x['name']) ?><?php echo empty($x['active']) ? ' (ปิดใช้งาน)' : '' ?></option>
      <?php } ?>
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
      ) as $k => $lb) { ?>
        <option value="<?php echo e($k) ?>" <?php echo $k === $fSort . '|' . $fDir ? 'selected' : '' ?>><?php echo e($lb) ?></option>
      <?php } ?>
    </select>
    <input type="hidden" name="sort" value="<?php echo e($fSort) ?>">
    <input type="hidden" name="dir" value="<?php echo e($fDir) ?>">

    <label class="sr-only" for="fq">ค้นหา</label>
    <input class="input adm-q" type="search" id="fq" name="q" value="<?php echo e($fQ) ?>" placeholder="ค้นชื่อสินค้า / SKU / เลขที่ใบ">
    <?php if ($fWhy !== '') { ?><input type="hidden" name="why" value="<?php echo e($fWhy) ?>"><?php } ?>
    <?php if ($fVoid) { ?><input type="hidden" name="void" value="1"><?php } ?>
    <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-search"/></svg> ค้นหา</button>
  </form>
</section>

<!-- ==================== สรุป ==================== -->
<section class="mini num adm-kpi" aria-label="สรุปการเบิก / ตัดออก">
  <div class="m"><div class="lb">ใบเบิก / ตัดออก</div><div class="nm"><?php echo number_format(count($sum['docs'])) ?></div>
    <div class="sb"><?php echo number_format($sum['qty']) ?> ชิ้น<?php echo $sum['void'] ? ' · ยกเลิก ' . number_format($sum['void']) . ' รายการ' : '' ?></div></div>
  <div class="m"><div class="lb">มูลค่าทุนที่ออกจากสต๊อก</div><div class="nm"><?php echo e(money2($sum['value'])) ?></div>
    <div class="sb">ถ้าขายได้ตามราคาขาย <?php echo e(money2($sum['sale'])) ?> บาท</div></div>
  <div class="m"><div class="lb">แยกสาขา (มูลค่าทุน)</div>
    <div class="sb adm-perb"><?php if (!$sum['perB']) { ?>ไม่มีรายการ<?php } else { arsort($sum['perB']); foreach ($sum['perB'] as $c => $v) { ?>
      <span><b><?php echo e($brAll[$c]['short']) ?></b> <?php echo e(money2($v)) ?></span>
    <?php } } ?></div></div>
</section>

<!-- ==================== รายการ ==================== -->
<section class="card">
  <div class="card-head">
    <div>
      <h2><?php echo $fB === 'ALL' ? 'ทุกสาขา' : e($brAll[$fB]['name']) ?> · <?php echo e($rangeTxt) ?></h2>
      <span class="sub">แถวละ 1 สินค้า · เบิก / ตัดออกทำโดยพนักงานที่ได้รับสิทธิ์ · กดหัวคอลัมน์เพื่อเรียง</span>
    </div>
    <a class="btn btn-ghost btn-sm" href="<?php echo e($aq(array('void' => $fVoid ? '' : '1'))) ?>"><?php echo $fVoid ? 'ซ่อนใบที่ยกเลิก' : 'แสดงใบที่ยกเลิกด้วย' ?></a>
  </div>

  <div class="cats tabbar" role="tablist">
    <a class="cat<?php echo $fWhy === '' ? ' on' : '' ?>" href="<?php echo e($aq(array('why' => ''))) ?>">ทุกเหตุผล <i><?php echo number_format($allCount) ?></i></a>
    <?php foreach ($reasons as $k => $x) { ?>
      <?php if (empty($byWhy[$k]) && $fWhy !== $k) { continue; } ?>
      <a class="cat<?php echo $fWhy === $k ? ' on' : '' ?>" href="<?php echo e($aq(array('why' => $k))) ?>"><?php echo e($x['label']) ?> <i><?php echo number_format(isset($byWhy[$k]) ? $byWhy[$k] : 0) ?></i></a>
    <?php } ?>
  </div>

  <?php if (!$show) { ?>
    <p class="empty"><svg class="ico"><use href="#i-out"/></svg>ไม่มีการเบิก / ตัดออกในช่วงที่เลือก<br><small>ลองเปลี่ยนวัน สาขา เหตุผล หรือคำค้น</small></p>
  <?php } else { ?>
    <div class="tbl-wrap">
      <table class="tbl hist-all adm-iss">
        <thead>
          <tr>
            <?php echo $th('time', $fMode === 'day' ? 'เวลา' : 'วันที่ · เวลา') ?>
            <th>สาขา</th>
            <th>สินค้า</th>
            <?php echo $th('qty', 'จำนวน', 'r') ?>
            <?php echo $th('price', 'ราคาทุน / ชิ้น', 'r') ?>
            <?php echo $th('value', 'มูลค่ารวม', 'r') ?>
            <th>เหตุผล</th>
            <th>ผู้ทำ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($show as $r) { ?>
            <tr<?php echo $r['void'] ? ' class="is-void"' : '' ?>>
              <td data-label="เวลา" class="num nowrap"><?php if ($fMode !== 'day') { ?><?php echo e(thai_day_month(strtotime($r['date']))) ?> · <?php } ?><?php echo e($r['time']) ?>
                <small class="hist-n"><?php echo e($r['no']) ?></small></td>
              <td data-label="สาขา"><span class="hist-br"><?php echo e($brAll[$r['branch']]['short']) ?></span></td>
              <td data-label="สินค้า">
                <b class="hist-t"><?php echo e($r['name']) ?></b>
                <small class="hist-n"><?php echo e($r['sku']) ?><?php echo $r['cat'] !== '' ? ' · ' . e($r['cat']) : '' ?></small>
                <?php if ($r['void']) { ?><small class="hist-n hist-void">ยกเลิกโดย <?php echo e($r['void_by']) ?><?php echo $r['void_reason'] !== '' ? ' — ' . e($r['void_reason']) : '' ?></small><?php } ?>
              </td>
              <td data-label="จำนวน" class="r num nowrap"><?php echo number_format($r['qty']) ?> <?php echo e($r['unit']) ?></td>
              <td data-label="ราคาทุน / ชิ้น" class="r num nowrap"><?php echo e(money2($r['cost'])) ?><small class="hist-n">ขาย <?php echo e(money2($r['price'])) ?></small></td>
              <td data-label="มูลค่ารวม" class="r num nowrap"><b><?php echo e(money2($r['value'])) ?></b></td>
              <td data-label="เหตุผล">
                <span class="bdg <?php echo in_array($r['reason'], array('lost', 'damaged', 'expired'), true) ? 'bdg-out' : 'bdg-adj' ?>"><?php echo e(issue_reason_label($r['reason'])) ?></span>
                <?php if ($r['note'] !== '') { ?><small class="hist-n"><?php echo e($r['note']) ?></small><?php } ?>
                <?php if ($r['ref'] !== '') { ?><small class="hist-n">อ้างอิง <?php echo e($r['ref']) ?></small><?php } ?>
              </td>
              <td data-label="ผู้ทำ" class="nowrap"><?php echo e($r['by']) ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1) { ?>
      <nav class="hist-pager" aria-label="หน้า">
        <?php if ($pg > 1) { ?><a class="btn btn-ghost btn-sm" href="<?php echo e($aq(array('p' => $pg - 1))) ?>">‹ ก่อนหน้า</a><?php } ?>
        <span>หน้า <?php echo $pg ?> / <?php echo $pages ?> · <?php echo number_format($total) ?> รายการ</span>
        <?php if ($pg < $pages) { ?><a class="btn btn-ghost btn-sm" href="<?php echo e($aq(array('p' => $pg + 1))) ?>">ถัดไป ›</a><?php } ?>
      </nav>
    <?php } ?>
  <?php } ?>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
