<?php
/**
 * FILE: themes/aostock/adm-history.php
 * ROLE: [ผู้ดูแล] ประวัติการทำรายการ ทุกสาขา
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/adm-history-day.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_log (ทุกวัน) · ao_stock_sale / receive / issue / count (สถานะยกเลิกภายหลัง + ยอดขาย) — ผ่าน api.php
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 6: วันนี้อ่าน ao_stock_log · เอกสารคลังวันก่อนอ่านจากตารางทั้งช่วงในคิวรีเดียว
 *   - [x] ช่วงที่ 8: ทุกวันอ่านจาก ao_stock_log ช่วงวันที่ — นับ / แบ่งหน้าใน SQL (log_range)
 *   - [x] ช่วงที่ 9: แถวรับคืนลิงก์ไปหน้ารายละเอียดของวันแทน เมื่อเมนูรับคืนถูกปิดจากหลังบ้าน
 *   - [x] ช่วงที่ 10: คืนหมายเหตุแบบเดโมเดิม — บิลที่ "มีรับคืน" · ใบรับคืน "กลับเข้าสต๊อก / ไม่เข้าสต๊อก"
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   [ผู้ดูแล] ประวัติการทำรายการ ทุกสาขา
   ----------------------------------------------------------
   หน้ารวม (GET):
     b    = ALL หรือรหัสสาขา (รวมสาขาที่ปิดใช้งาน)
     mode = recent (30 วันล่าสุด — ค่าเริ่มต้น) | day | month | year · d = วันที่ · m = เดือน · y = ปี
     t    = ชนิดรายการ (คีย์ของ log_types) · sort = desc | asc · p = หน้า (ทีละ 100)
   รายละเอียดของสาขาหนึ่งในวันหนึ่ง: ?view=day&b=รหัสสาขา&d=ปปปปดดวว
     วันนี้    = ประวัติของสาขา (ดูอย่างเดียว)
     วันก่อน  = เอกสารคลัง + บิลขาย + ลำดับเหตุการณ์ทั้งวัน · ผู้ดูแลยกเลิกเอกสารคลังย้อนหลังได้ (ต้องมีเหตุผล)
              "แก้ไขใบ" ไม่มีให้ผู้ดูแล เพราะต้องทำใบใหม่ในหน้างานคลังของพนักงาน

   ที่มาของข้อมูล
     ทุกวัน     ประวัติใน ao_stock_log (log_range) — เปิด–ปิดร้าน เงินเข้าออก บิล ยกเลิก รับคืน เอกสารคลัง ตั้งค่า
                บิล / เอกสารคลังที่ถูกยกเลิกภายหลัง → แถวเดิมขีดฆ่า + บอกว่าใครยกเลิก
     สรุปยอดขาย  บิลที่ไม่ยกเลิกใน ao_stock_sale (sales_agg)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล (พนักงานถูกพากลับเอง)

/* ==================== รายละเอียดสาขา / วัน ==================== */
if (isset($_GET['view']) && $_GET['view'] === 'day') {
    require dirname(__FILE__) . '/inc/adm-history-day.php';
    exit;
}

$brAll = branches_all();
$today = strtotime(date('Y-m-d'));

/* ---------- อ่านตัวกรอง ---------- */
$fB    = (isset($_GET['b']) && is_string($_GET['b']) && isset($brAll[$_GET['b']])) ? $_GET['b'] : 'ALL';
$fMode = (isset($_GET['mode']) && in_array($_GET['mode'], array('day', 'month', 'year'), true)) ? $_GET['mode'] : 'recent';
$fSort = (isset($_GET['sort']) && $_GET['sort'] === 'asc') ? 'asc' : 'desc';
$fT    = isset($_GET['t']) && is_string($_GET['t']) ? $_GET['t'] : '';
if ($fT !== '' && !array_key_exists($fT, log_types())) {
    $fT = '';
}

$dayTs = $today;
if (isset($_GET['d']) && is_string($_GET['d']) && ($x = strtotime($_GET['d'])) !== false) {
    $dayTs = min(strtotime(date('Y-m-d', $x)), $today);
}
$monTs = strtotime(date('Y-m-01', $dayTs));
if (isset($_GET['m']) && is_string($_GET['m']) && preg_match('/^\d{4}-\d{2}$/', $_GET['m'])
    && ($x = strtotime($_GET['m'] . '-01')) !== false) {
    $monTs = min($x, strtotime(date('Y-m-01')));
}

$year = (int) date('Y', $dayTs);
if (isset($_GET['y']) && is_string($_GET['y']) && preg_match('/^\d{4}$/', $_GET['y'])) {
    $year = max(2026, min((int) date('Y'), (int) $_GET['y']));
}
list($from, $to, $rangeTxt) = adm_range($fMode, $dayTs, $monTs, $year);

/** ลิงก์ของหน้านี้ โดยเปลี่ยนค่าบางตัว */
$hq = function ($chg) use ($fB, $fMode, $fSort, $fT, $dayTs, $monTs, $year) {
    $q = array('b' => $fB, 'mode' => $fMode, 'd' => date('Y-m-d', $dayTs), 'm' => date('Y-m', $monTs), 'y' => $year,
               't' => $fT, 'sort' => $fSort);
    $q = array_merge($q, $chg);
    foreach (array('day' => 'd', 'month' => 'm', 'year' => 'y') as $md => $k) {
        if ($q['mode'] !== $md) {
            unset($q[$k]);
        }
    }
    foreach (array('t' => '', 'sort' => 'desc', 'b' => 'ALL', 'mode' => 'recent', 'p' => 1) as $k => $def) {
        if (isset($q[$k]) && (string) $q[$k] === (string) $def) {
            unset($q[$k]);
        }
    }
    return 'adm-history.php?' . http_build_query($q);
};

/* ---------- รวบรวมรายการ — ทุกวันอ่านจาก ao_stock_log (ช่วงที่ 8) · นับและแบ่งหน้าใน SQL (log_range) ---------- */
$codes = $fB === 'ALL' ? array_keys($brAll) : array($fB);
$per   = 100;
$pgIn  = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$lr    = log_range($codes, $from, $to, $fT, $fSort, $pgIn, $per);
$total = $lr['total'];
$pages = max(1, (int) ceil($total / $per));
$pg    = min($pgIn, $pages);
if ($pg !== $pgIn) {
    $lr = log_range($codes, $from, $to, $fT, $fSort, $pg, $per);      // ขอหน้าเกินจำนวนที่มี → หน้าสุดท้าย
}
$counts = $lr['counts'];

/* ---------- สรุปทั้งช่วง (ไม่สนชนิดที่กรอง) ---------- */
$sales = 0;
foreach (sales_agg($codes, $from, $to) as $x) {
    $sales += $x['total'];                                            // ยอดขายของบิลที่ไม่ยกเลิก
}
$sum = array('all' => array_sum($lr['perB']), 'bills' => $counts['sale'], 'sales' => $sales,
             'docs' => $counts['receive'] + $counts['issue'] + $counts['adjust'], 'perB' => array());
foreach ($codes as $c) {
    if (!empty($lr['perB'][$c])) {
        $sum['perB'][$c] = $lr['perB'][$c];
    }
}

/* ---------- แถวของหน้านี้ ---------- */
$noteKeys = array('ประเภทบิล', 'ชำระโดย', 'สต๊อก', 'เหตุผล', 'ผลต่าง', 'เอกสารอ้างอิง');      // สต๊อก = รับคืนกลับเข้าสต๊อกไหม
$show     = array();
foreach ($lr['rows'] as $r) {
    $note = array();
    foreach ($noteKeys as $k) {                                     // สรุปสั้น ๆ จากรายละเอียด — ตัวเต็มอยู่ในหน้ารายละเอียดของวัน
        if (isset($r['detail'][$k]) && $r['detail'][$k] !== '' && count($note) < 2) {
            $note[] = preg_replace('/\s*\(.*$/u', '', (string) $r['detail'][$k]);
        }
    }
    if ($r['returned']) {
        $note[] = 'มีรับคืน';                                     // ป้ายเดิมของเดโม (คืนในช่วงที่ 10)
    }
    if ($r['void']) {
        array_unshift($note, ($r['void_mode'] === 'edit' ? 'ยกเลิกเพื่อแก้ไขภายหลัง' : 'ยกเลิกภายหลัง') . ($r['void_by'] !== '' ? ' โดย ' . $r['void_by'] : ''));
    }
    $link = ($r['type'] === 'return' && $r['ref'] !== '' && menu_enabled('adm-return.php'))
          ? 'adm-return.php?no=' . rawurlencode($r['ref']) . '&b=' . rawurlencode($r['branch'])
          : 'adm-history.php?view=day&b=' . rawurlencode($r['branch']) . '&d=' . $r['date'];
    $show[] = array('ts' => $r['ts'], 'date' => $r['date'], 'time' => $r['time'], 'branch' => $r['branch'], 'type' => $r['type'],
                    'title' => $r['title'], 'amount' => $r['amount'],
                    'kind' => in_array($r['type'], array('receive', 'rvoid'), true) ? 'qty' : 'money',
                    'by' => $r['by'], 'void' => $r['void'], 'note' => implode(' · ', $note), 'link' => $link);
}

$branch         = 'ALL';
$NO_BRANCH_PICK = true;                  // มีตัวกรองสาขาของหน้านี้เองแล้ว
$PAGE_TITLE     = 'ประวัติการทำรายการ';
$PAGE_SUB       = 'ทุกสาขา · ' . $rangeTxt;
$NAV_ACTIVE     = 'adm-history.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter hist-filter">
  <form method="get" action="adm-history.php" class="acct-row">
    <div class="segs">
      <?php foreach (array('recent' => '30 วัน', 'day' => 'รายวัน', 'month' => 'รายเดือน', 'year' => 'รายปี') as $md => $lb): ?>
        <a class="seg<?= $fMode === $md ? ' on' : '' ?>" href="<?= e($hq(array('mode' => $md))) ?>"><?= e($lb) ?></a>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="mode" value="<?= e($fMode) ?>">

    <?php if ($fMode === 'day'): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($hq(array('d' => date('Y-m-d', strtotime('-1 day', $dayTs))))) ?>" aria-label="วันก่อนหน้า">‹</a>
      <label class="sr-only" for="hd">วันที่</label>
      <input class="input acct-date" type="date" id="hd" name="d" value="<?= e(date('Y-m-d', $dayTs)) ?>"
             max="<?= e(date('Y-m-d')) ?>" onchange="this.form.submit()">
      <?php if ($dayTs < $today): ?>
        <a class="btn btn-ghost btn-sm" href="<?= e($hq(array('d' => date('Y-m-d', strtotime('+1 day', $dayTs))))) ?>" aria-label="วันถัดไป">›</a>
        <a class="btn btn-ghost btn-sm" href="<?= e($hq(array('d' => date('Y-m-d')))) ?>">วันนี้</a>
      <?php endif; ?>
    <?php elseif ($fMode === 'month'): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($hq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>" aria-label="เดือนก่อนหน้า">‹</a>
      <label class="sr-only" for="hm">เดือน</label>
      <input class="input acct-date" type="month" id="hm" name="m" value="<?= e(date('Y-m', $monTs)) ?>"
             max="<?= e(date('Y-m')) ?>" onchange="this.form.submit()">
      <?php if ($monTs < strtotime(date('Y-m-01'))): ?>
        <a class="btn btn-ghost btn-sm" href="<?= e($hq(array('m' => date('Y-m', strtotime('+1 month', $monTs))))) ?>" aria-label="เดือนถัดไป">›</a>
        <a class="btn btn-ghost btn-sm" href="<?= e($hq(array('m' => date('Y-m')))) ?>">เดือนนี้</a>
      <?php endif; ?>
    <?php elseif ($fMode === 'year'): ?>
      <label class="sr-only" for="hy">ปี</label>
      <select class="input acct-date" id="hy" name="y" onchange="this.form.submit()">
        <?php for ($y = (int) date('Y'); $y >= 2026; $y--): ?>
          <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>>ปี <?= $y + 543 ?></option>
        <?php endfor; ?>
      </select>
    <?php endif; ?>

    <label class="sr-only" for="hb">สาขา</label>
    <select class="input acct-branch" id="hb" name="b" onchange="this.form.submit()">
      <option value="ALL">ทุกสาขา</option>
      <?php foreach ($brAll as $c => $x): ?>
        <option value="<?= e($c) ?>" <?= $fB === $c ? 'selected' : '' ?>><?= e($x['name']) ?><?= empty($x['active']) ? ' (ปิดใช้งาน)' : '' ?></option>
      <?php endforeach; ?>
    </select>

    <label class="sr-only" for="hs">เรียงลำดับ</label>
    <select class="input acct-branch" id="hs" name="sort" onchange="this.form.submit()">
      <option value="desc" <?= $fSort === 'desc' ? 'selected' : '' ?>>เวลา: ใหม่ → เก่า</option>
      <option value="asc" <?= $fSort === 'asc' ? 'selected' : '' ?>>เวลา: เก่า → ใหม่</option>
    </select>
    <?php if ($fT !== ''): ?><input type="hidden" name="t" value="<?= e($fT) ?>"><?php endif; ?>
    <noscript><button class="btn btn-ghost btn-sm" type="submit">ดู</button></noscript>
  </form>
</section>

<!-- ==================== สรุป ==================== -->
<section class="mini num adm-kpi" aria-label="สรุปช่วงที่เลือก">
  <div class="m"><div class="lb">รายการทั้งหมด</div><div class="nm"><?= number_format($sum['all']) ?></div>
    <div class="sb"><?php $px = array(); foreach ($sum['perB'] as $c => $n) { $px[] = $brAll[$c]['short'] . ' ' . number_format($n); } echo e($px ? implode(' · ', $px) : 'ไม่มีรายการ'); ?></div></div>
  <div class="m"><div class="lb">บิลขาย</div><div class="nm"><?= number_format($sum['bills']) ?></div>
    <div class="sb">ยอดขาย <?= e(money2($sum['sales'])) ?> บาท (ไม่รวมบิลยกเลิก)</div></div>
  <div class="m"><div class="lb">เอกสารคลัง</div><div class="nm"><?= number_format($sum['docs']) ?></div>
    <div class="sb">รับเข้า · ตัดออก · ตรวจนับ</div></div>
</section>

<!-- ==================== รายการ ==================== -->
<section class="card">
  <div class="card-head">
    <div>
      <h2><?= $fB === 'ALL' ? 'ทุกสาขา' : e($brAll[$fB]['name']) ?> · <?= e($rangeTxt) ?></h2>
      <span class="sub">เรียงตามเวลา<?= $fSort === 'asc' ? 'จากเก่าไปใหม่' : 'จากใหม่ไปเก่า' ?>
        · ทุกรายการที่บันทึกในระบบ · บิลหรือเอกสารที่ถูกยกเลิกภายหลังมีขีดฆ่า</span>
    </div>
  </div>

  <div class="cats tabbar" role="tablist">
    <a class="cat<?= $fT === '' ? ' on' : '' ?>" href="<?= e($hq(array('t' => ''))) ?>">ทั้งหมด <i><?= number_format($sum['all']) ?></i></a>
    <?php foreach (log_types() as $k => $meta): ?>
      <?php if ($counts[$k] === 0 && $fT !== $k) { continue; } ?>
      <a class="cat<?= $fT === $k ? ' on' : '' ?>" href="<?= e($hq(array('t' => $k))) ?>"><?= e($meta['label']) ?> <i><?= number_format($counts[$k]) ?></i></a>
    <?php endforeach; ?>
  </div>

  <?php if (!$show): ?>
    <p class="empty"><svg class="ico"><use href="#i-history"/></svg>ไม่มีรายการในช่วงที่เลือก<br><small>ลองเปลี่ยนวัน สาขา หรือชนิดรายการ</small>
      <?php if ($fMode === 'day' && $dayTs === $today): ?>
        <br><a class="btn btn-ghost btn-sm" href="<?= e($hq(array('d' => date('Y-m-d', strtotime('-1 day', $today))))) ?>">ดูของเมื่อวาน</a>
      <?php elseif ($fMode === 'month' && $monTs === strtotime(date('Y-m-01'))): ?>
        <br><a class="btn btn-ghost btn-sm" href="<?= e($hq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>">ดูเดือนก่อน</a>
      <?php endif; ?>
    </p>
  <?php else: ?>
    <div class="tbl-wrap">
      <table class="tbl hist-all">
        <thead>
          <tr>
            <th><?= $fMode !== 'day' ? 'วันที่ · เวลา' : 'เวลา' ?></th>
            <th>สาขา</th>
            <th>ชนิด</th>
            <th>รายการ</th>
            <th>ผู้ทำ</th>
            <th class="r">จำนวน</th>
            <th class="r"><span class="sr-only">ดู</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($show as $r): $m = log_type_of($r['type']); ?>
            <tr<?= $r['void'] ? ' class="is-void"' : '' ?>>
              <td data-label="เวลา" class="num nowrap"><?php if ($fMode !== 'day'): ?><?= e(thai_day_month(strtotime($r['date']))) ?> · <?php endif; ?><?= e($r['time']) ?></td>
              <td data-label="สาขา"><span class="hist-br"><?= e($brAll[$r['branch']]['short']) ?></span></td>
              <td data-label="ชนิด"><span class="badge b-<?= e($m['tone']) ?>"><?= e($m['label']) ?></span></td>
              <td data-label="รายการ">
                <b class="hist-t"><?= e($r['title']) ?></b>
                <?php if ($r['void']): ?><small class="hist-n hist-void">ยกเลิกแล้ว</small><?php endif; ?>
                <?php if ($r['note'] !== ''): ?><small class="hist-n"><?= e($r['note']) ?></small><?php endif; ?>
              </td>
              <td data-label="ผู้ทำ" class="nowrap"><?= e($r['by']) ?></td>
              <td data-label="จำนวน" class="r num nowrap">
                <?php if ($r['amount'] === null): ?>—
                <?php elseif ($r['kind'] === 'qty'): ?><?= $r['amount'] > 0 ? '+' : ($r['amount'] < 0 ? '−' : '') ?><?= number_format(abs($r['amount'])) ?> ชิ้น
                <?php else: ?><?= e(money2($r['amount'])) ?> ฿<?php endif; ?>
              </td>
              <td data-label="" class="r">
                <?php if ($r['link'] !== ''): ?>
                  <a class="btn btn-ghost btn-sm" href="<?= e($r['link']) ?>" title="เปิดรายการของสาขานี้ในวันนั้น">ดู</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($pages > 1): ?>
      <nav class="hist-pager" aria-label="หน้า">
        <?php if ($pg > 1): ?><a class="btn btn-ghost btn-sm" href="<?= e($hq(array('p' => $pg - 1))) ?>">‹ ก่อนหน้า</a><?php endif; ?>
        <span>หน้า <?= $pg ?> / <?= $pages ?> · <?= number_format($total) ?> รายการ</span>
        <?php if ($pg < $pages): ?><a class="btn btn-ghost btn-sm" href="<?= e($hq(array('p' => $pg + 1))) ?>">ถัดไป ›</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
