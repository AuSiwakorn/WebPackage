<?php
/* ==========================================================
   AOSTOCK DEMO — [ผู้ดูแล] ประวัติการนำเข้าสินค้า ทุกสาขา
   ----------------------------------------------------------
   ผู้ดูแลไม่นำเข้าเอง (เป็นหน้าที่ของพนักงานที่มีสิทธิ์ receive) — หน้านี้แสดงใบรับเข้าทั้งหมดไว้ตรวจสอบ
   และเปิดดูรายละเอียดทีละใบได้

   ตัวกรอง (GET)
     b    = ALL | รหัสสาขา (รวมสาขาที่ปิดใช้งาน)
     mode = recent (30 วันล่าสุด — ค่าเริ่มต้น) | day | month | year · d = Y-m-d · m = Y-m · y = Y
     q    = ค้นชื่อสินค้า / SKU / เลขที่ใบ / เอกสารอ้างอิง
     void = 1 แสดงใบที่ยกเลิกแล้วด้วย
     sort = time | value | qty | items · dir = desc | asc · p = หน้า (ทีละ 100 ใบ)
   ดูรายละเอียด: ?no=RC-ปปดดวว-NNNN&b=รหัสสาขา

   ที่มาของข้อมูลในเดโม: วันนี้ = ใบที่พนักงานทำจริงใน session · วันก่อน = ข้อมูลสมมติที่คงที่ (past_docs)
   มูลค่าคิดจากราคาทุนปัจจุบัน — ระบบจริงใช้ราคาทุนที่ snapshot ไว้ใน ao_stock_receive_item
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

$brAll   = demo_branches_all();
$today   = strtotime(date('Y-m-d'));
$yearMin = 2026;
$g       = function ($k, $def = '') { return (isset($_GET[$k]) && is_string($_GET[$k])) ? trim($_GET[$k]) : $def; };

/* ---------- เปิดดูใบเดียว ---------- */
$view = null;
if ($g('no') !== '' && isset($brAll[$g('b')])) {
    $view = receive_doc_find($g('b'), $g('no'));
}

/* ---------- ตัวกรอง ---------- */
$fB    = isset($brAll[$g('b')]) ? $g('b') : 'ALL';
$fMode = in_array($g('mode'), array('day', 'month', 'year'), true) ? $g('mode') : 'recent';
$fQ    = substr($g('q'), 0, 120);
$fVoid = $g('void') === '1';
$fSort = in_array($g('sort'), array('time', 'value', 'qty', 'items'), true) ? $g('sort') : 'time';
$fDir  = $g('dir') === 'asc' ? 'asc' : 'desc';
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

$cq = function ($chg) use ($fB, $fMode, $fQ, $fVoid, $fSort, $fDir, $dayTs, $monTs, $year) {
    $q = array_merge(array('b' => $fB, 'mode' => $fMode, 'd' => date('Y-m-d', $dayTs), 'm' => date('Y-m', $monTs), 'y' => $year,
                           'q' => $fQ, 'void' => $fVoid ? '1' : '', 'sort' => $fSort, 'dir' => $fDir), $chg);
    foreach (array('day' => 'd', 'month' => 'm', 'year' => 'y') as $md => $k) {
        if ($q['mode'] !== $md) {
            unset($q[$k]);
        }
    }
    foreach (array('b' => 'ALL', 'mode' => 'recent', 'q' => '', 'void' => '', 'sort' => 'time', 'dir' => 'desc', 'p' => 1) as $k => $def) {
        if (isset($q[$k]) && (string) $q[$k] === (string) $def) {
            unset($q[$k]);
        }
    }
    return 'adm-receive.php' . ($q ? '?' . http_build_query($q) : '');
};

/* ---------- รวบรวม ---------- */
$codes = $fB === 'ALL' ? array_keys($brAll) : array($fB);
$rows  = array();
for ($d = $from; $d <= $to; $d = strtotime('+1 day', $d)) {
    foreach ($codes as $c) {
        foreach (receive_docs_of_day($c, $d) as $doc) {
            if (!$fVoid && $doc['void']) {
                continue;
            }
            if ($fQ !== '') {
                $hay = $doc['no'] . ' ' . $doc['ref'] . ' ' . $doc['note'];
                foreach ($doc['lines'] as $l) {
                    $hay .= ' ' . $l['name'] . ' ' . $l['sku'];
                }
                if (stripos($hay, $fQ) === false) {
                    continue;
                }
            }
            $rows[] = $doc;
        }
    }
}

$sum = array('docs' => 0, 'qty' => 0, 'value' => 0, 'void' => 0, 'perB' => array(), 'staff' => array());
foreach ($rows as $r) {
    if ($r['void']) {
        $sum['void']++;
        continue;
    }
    $sum['docs']++;
    $sum['qty']   += $r['qty'];
    $sum['value'] += $r['value'];
    $sum['perB'][$r['branch']] = (isset($sum['perB'][$r['branch']]) ? $sum['perB'][$r['branch']] : 0) + $r['value'];
}

$dir = $fDir === 'asc' ? 1 : -1;
$key = array('time' => 'ts', 'value' => 'value', 'qty' => 'qty', 'items' => 'items');
$key = $key[$fSort];
usort($rows, function ($a, $b) use ($key, $dir) {
    if ($a[$key] != $b[$key]) {
        return ($a[$key] < $b[$key] ? -1 : 1) * $dir;
    }
    return $a['ts'] === $b['ts'] ? 0 : ($a['ts'] < $b['ts'] ? 1 : -1);
});
$per   = 100;
$total = count($rows);
$pages = max(1, (int) ceil($total / $per));
$pg    = max(1, min($pages, (int) $g('p', '1')));
$show  = array_slice($rows, ($pg - 1) * $per, $per);

$th = function ($k, $label, $cls = '') use ($fSort, $fDir, $cq) {
    $on   = ($fSort === $k);
    $next = ($on && $fDir === 'desc') ? 'asc' : 'desc';
    return '<th class="' . $cls . '"><a class="th-sort' . ($on ? ' on' : '') . '" href="' . e($cq(array('sort' => $k, 'dir' => $next))) . '">'
         . e($label) . ($on ? ($fDir === 'desc' ? ' ↓' : ' ↑') : '') . '</a></th>';
};

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = $view ? 'ใบรับเข้า ' . $view['no'] : 'นำเข้าสินค้า';
$PAGE_SUB       = $view ? branch_name($view['branch']) . ' · ' . thai_date_full(strtotime($view['date']))
                        : 'ประวัติการนำเข้าสินค้าของทุกสาขา · ' . $rangeTxt;
$NAV_ACTIVE     = 'adm-receive.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($g('no') !== '' && $view === null): ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span>ไม่พบใบรับเข้านี้</span></div>
<?php endif; ?>

<?php if ($view !== null): $v = $view; ?>
  <!-- ==================== รายละเอียดใบรับเข้า ==================== -->
  <p class="hist-back">
    <a class="btn btn-ghost btn-sm" href="<?= e($cq(array('b' => $v['branch'], 'mode' => 'month', 'm' => date('Y-m', strtotime($v['date']))))) ?>">‹ กลับไปประวัติการนำเข้า</a>
  </p>
  <section class="card ret-view">
    <div class="card-head">
      <div>
        <h2><?= e($v['no']) ?></h2>
        <span class="sub"><?= e(branch_name($v['branch'])) ?> · <?= e(thai_date_full(strtotime($v['date']))) ?> <?= e($v['time']) ?> น. · รับเข้าโดย <?= e($v['by']) ?></span>
      </div>
      <span class="bdg <?= $v['void'] ? 'bdg-out' : 'bdg-ok' ?>"><?= $v['void'] ? 'ยกเลิกแล้ว' : 'เข้าสต๊อกแล้ว' ?></span>
    </div>
    <dl class="ret-meta">
      <div><dt>เอกสารอ้างอิง (ใบส่งของ / ใบกำกับ)</dt><dd><?= $v['ref'] !== '' ? e($v['ref']) : '—' ?></dd></div>
      <div><dt>จำนวน</dt><dd><?= (int) $v['items'] ?> รายการ · <?= number_format($v['qty']) ?> ชิ้น</dd></div>
      <?php if ($v['note'] !== ''): ?><div><dt>หมายเหตุ</dt><dd><?= e($v['note']) ?></dd></div><?php endif; ?>
      <?php if ($v['void']): ?>
        <div><dt>การยกเลิก</dt><dd><?= e(($v['void_mode'] === 'edit' ? 'ยกเลิกเพื่อแก้ไข' : 'ยกเลิก') . ' เมื่อ ' . $v['void_at'] . ' น. โดย ' . $v['void_by'] . ($v['void_reason'] !== '' ? ' — ' . $v['void_reason'] : '')) ?></dd></div>
      <?php endif; ?>
    </dl>
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th>สินค้า</th><th class="r">จำนวน</th><th class="r">ราคาทุน / ชิ้น</th><th class="r">มูลค่า</th></tr></thead>
        <tbody>
          <?php foreach ($v['lines'] as $l): ?>
            <tr>
              <td data-label="สินค้า"><b><?= e($l['name']) ?></b><small class="hist-n"><?= e($l['sku']) ?><?= $l['cat'] !== '' ? ' · ' . e($l['cat']) : '' ?></small></td>
              <td data-label="จำนวน" class="r num"><?= number_format($l['qty']) ?> <?= e($l['unit']) ?></td>
              <td data-label="ราคาทุน / ชิ้น" class="r num"><?= e(money2($l['cost'])) ?></td>
              <td data-label="มูลค่า" class="r num"><?= e(money2($l['value'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr class="ret-total"><td colspan="3" class="r"><b>มูลค่ารวม (ทุน)</b></td><td class="r num"><b><?= e(money2($v['value'])) ?></b></td></tr>
        </tfoot>
      </table>
    </div>
  </section>
  <?php require dirname(__FILE__) . '/inc/footer.php'; exit; ?>
<?php endif; ?>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter hist-filter">
  <form method="get" action="adm-receive.php" class="acct-row">
    <div class="segs">
      <?php foreach (array('recent' => '30 วัน', 'day' => 'รายวัน', 'month' => 'รายเดือน', 'year' => 'รายปี') as $md => $lb): ?>
        <a class="seg<?= $fMode === $md ? ' on' : '' ?>" href="<?= e($cq(array('mode' => $md))) ?>"><?= e($lb) ?></a>
      <?php endforeach; ?>
    </div>
    <input type="hidden" name="mode" value="<?= e($fMode) ?>">
    <?php if ($fMode === 'day'): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($cq(array('d' => date('Y-m-d', strtotime('-1 day', $dayTs))))) ?>" aria-label="วันก่อนหน้า">‹</a>
      <label class="sr-only" for="cd">วันที่</label>
      <input class="input acct-date" type="date" id="cd" name="d" value="<?= e(date('Y-m-d', $dayTs)) ?>" max="<?= e(date('Y-m-d')) ?>" onchange="this.form.submit()">
      <?php if ($dayTs < $today): ?><a class="btn btn-ghost btn-sm" href="<?= e($cq(array('d' => date('Y-m-d', strtotime('+1 day', $dayTs))))) ?>" aria-label="วันถัดไป">›</a><?php endif; ?>
    <?php elseif ($fMode === 'month'): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($cq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>" aria-label="เดือนก่อนหน้า">‹</a>
      <label class="sr-only" for="cm">เดือน</label>
      <input class="input acct-date" type="month" id="cm" name="m" value="<?= e(date('Y-m', $monTs)) ?>" max="<?= e(date('Y-m')) ?>" onchange="this.form.submit()">
      <?php if ($monTs < strtotime(date('Y-m-01'))): ?><a class="btn btn-ghost btn-sm" href="<?= e($cq(array('m' => date('Y-m', strtotime('+1 month', $monTs))))) ?>" aria-label="เดือนถัดไป">›</a><?php endif; ?>
    <?php elseif ($fMode === 'year'): ?>
      <label class="sr-only" for="cy">ปี</label>
      <select class="input acct-date" id="cy" name="y" onchange="this.form.submit()">
        <?php for ($y = (int) date('Y'); $y >= $yearMin; $y--): ?>
          <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>>ปี <?= $y + 543 ?></option>
        <?php endfor; ?>
      </select>
    <?php endif; ?>
    <label class="sr-only" for="cb">สาขา</label>
    <select class="input acct-branch" id="cb" name="b" onchange="this.form.submit()">
      <option value="ALL">ทุกสาขา</option>
      <?php foreach ($brAll as $c => $x): ?>
        <option value="<?= e($c) ?>" <?= $fB === $c ? 'selected' : '' ?>><?= e($x['name']) ?><?= empty($x['active']) ? ' (ปิดใช้งาน)' : '' ?></option>
      <?php endforeach; ?>
    </select>
    <label class="sr-only" for="cq">ค้นหา</label>
    <input class="input adm-q" type="search" id="cq" name="q" value="<?= e($fQ) ?>" placeholder="ค้นสินค้า / SKU / เลขที่ใบ / เอกสารอ้างอิง">
    <?php if ($fVoid): ?><input type="hidden" name="void" value="1"><?php endif; ?>
    <?php if ($fSort !== 'time' || $fDir !== 'desc'): ?><input type="hidden" name="sort" value="<?= e($fSort) ?>"><input type="hidden" name="dir" value="<?= e($fDir) ?>"><?php endif; ?>
    <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-search"/></svg> ค้นหา</button>
  </form>
</section>

<!-- ==================== สรุป ==================== -->
<section class="mini num adm-kpi" aria-label="สรุปการนำเข้า">
  <div class="m"><div class="lb">ใบรับเข้า</div><div class="nm"><?= number_format($sum['docs']) ?></div>
    <div class="sb"><?= number_format($sum['qty']) ?> ชิ้น<?= $sum['void'] ? ' · ยกเลิก ' . number_format($sum['void']) . ' ใบ' : '' ?></div></div>
  <div class="m"><div class="lb">มูลค่าที่รับเข้า (ทุน)</div><div class="nm"><?= e(money2($sum['value'])) ?></div>
    <div class="sb">เฉลี่ย <?= e(money2($sum['docs'] ? $sum['value'] / $sum['docs'] : 0)) ?> บาท / ใบ</div></div>
  <div class="m"><div class="lb">แยกสาขา (มูลค่า)</div>
    <div class="sb adm-perb"><?php if (!$sum['perB']): ?>ไม่มีรายการ<?php else: arsort($sum['perB']); foreach ($sum['perB'] as $c => $x): ?>
      <span><b><?= e($brAll[$c]['short']) ?></b> <?= e(money2($x)) ?></span>
    <?php endforeach; endif; ?></div></div>
</section>

<!-- ==================== รายการ ==================== -->
<section class="card">
  <div class="card-head">
    <div>
      <h2><?= $fB === 'ALL' ? 'ทุกสาขา' : e($brAll[$fB]['name']) ?> · <?= e($rangeTxt) ?></h2>
      <span class="sub">นำเข้าโดยพนักงานที่ได้รับสิทธิ์ “นำเข้าสินค้า” · กดดูเพื่อตรวจรายการในใบ</span>
    </div>
    <a class="btn btn-ghost btn-sm" href="<?= e($cq(array('void' => $fVoid ? '' : '1'))) ?>"><?= $fVoid ? 'ซ่อนใบที่ยกเลิก' : 'แสดงใบที่ยกเลิกด้วย' ?></a>
  </div>

  <?php if (!$show): ?>
    <p class="empty"><svg class="ico"><use href="#i-in"/></svg>ไม่มีการนำเข้าในช่วงที่เลือก<br><small>ลองเปลี่ยนช่วงเวลา สาขา หรือคำค้น</small></p>
  <?php else: ?>
    <div class="tbl-wrap">
      <table class="tbl hist-all">
        <thead>
          <tr>
            <?= $th('time', $fMode === 'day' ? 'เวลา' : 'วันที่ · เวลา') ?>
            <th>สาขา</th>
            <th>ใบรับเข้า / สินค้า</th>
            <?= $th('items', 'รายการ', 'r') ?>
            <?= $th('qty', 'จำนวน', 'r') ?>
            <?= $th('value', 'มูลค่า (ทุน)', 'r') ?>
            <th>ผู้รับเข้า</th>
            <th class="r"><span class="sr-only">ดู</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($show as $r):
              $names = array();
              foreach ($r['lines'] as $l) { $names[] = $l['name'] . ' ×' . $l['qty']; } ?>
            <tr<?= $r['void'] ? ' class="is-void"' : '' ?>>
              <td data-label="เวลา" class="num nowrap"><?php if ($fMode !== 'day'): ?><?= e(thai_day_month(strtotime($r['date']))) ?> · <?php endif; ?><?= e($r['time']) ?></td>
              <td data-label="สาขา"><span class="hist-br"><?= e($brAll[$r['branch']]['short']) ?></span></td>
              <td data-label="ใบรับเข้า">
                <b class="hist-t"><?= e($r['no']) ?><?= $r['ref'] !== '' ? ' · ' . e($r['ref']) : '' ?></b>
                <small class="hist-n"><?= e(implode(' · ', $names)) ?></small>
                <?php if ($r['void']): ?><small class="hist-n hist-void">ยกเลิกโดย <?= e($r['void_by']) ?><?= $r['void_reason'] !== '' ? ' — ' . e($r['void_reason']) : '' ?></small><?php endif; ?>
              </td>
              <td data-label="รายการ" class="r num"><?= (int) $r['items'] ?></td>
              <td data-label="จำนวน" class="r num nowrap">+<?= number_format($r['qty']) ?> ชิ้น</td>
              <td data-label="มูลค่า (ทุน)" class="r num nowrap"><b><?= e(money2($r['value'])) ?></b></td>
              <td data-label="ผู้รับเข้า" class="nowrap"><?= e($r['by']) ?></td>
              <td data-label="" class="r"><a class="btn btn-ghost btn-sm" href="adm-receive.php?no=<?= e(rawurlencode($r['no'])) ?>&amp;b=<?= e(rawurlencode($r['branch'])) ?>">ดู</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1): ?>
      <nav class="hist-pager" aria-label="หน้า">
        <?php if ($pg > 1): ?><a class="btn btn-ghost btn-sm" href="<?= e($cq(array('p' => $pg - 1))) ?>">‹ ก่อนหน้า</a><?php endif; ?>
        <span>หน้า <?= $pg ?> / <?= $pages ?> · <?= number_format($total) ?> ใบ</span>
        <?php if ($pg < $pages): ?><a class="btn btn-ghost btn-sm" href="<?= e($cq(array('p' => $pg + 1))) ?>">ถัดไป ›</a><?php endif; ?>
      </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
