<?php
/**
 * FILE: themes/aostock/adm-return.php
 * ROLE: [ผู้ดูแล] ตรวจสอบการรับคืนสินค้า ทุกสาขา
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_return, ao_stock_return_item, ao_stock_sale (ผ่าน api.php)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 7: อ่านใบรับคืนจากตาราง (รูปแนบเป็น URL ใต้ uploads/stock/returns/)
 *   - [x] ช่วงที่ 8: ดึงทั้งช่วงวันในคิวรีเดียว (returns_range — เดิมวนทีละวันทีละสาขา)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   [ผู้ดูแล] ตรวจสอบการรับคืนสินค้า ทุกสาขา
   ----------------------------------------------------------
   ผู้ดูแลไม่ได้ทำรับคืนเอง (เป็นหน้าที่ของพนักงานที่ได้รับสิทธิ์ refund)
   หน้านี้แสดงใบรับคืนทั้งหมดของทุกสาขา ไว้ตรวจสอบ และเปิดดูรายละเอียดทีละใบ

   ตัวกรอง (GET): b = ALL | รหัสสาขา · mode = recent (30 วันล่าสุด — ค่าเริ่มต้น) | day | month | year · d · m · y
                  why = เหตุผล · stock = in (กลับเข้าสต๊อก) | out (ไม่เข้าสต๊อก) · sort = desc | asc
   ดูรายละเอียด: ?no=RT-ปปดดวว-NNNN&b=รหัสสาขา

   ที่มาของข้อมูล: ao_stock_return + return_item ทั้งช่วงในคิวรีเดียว (returns_range)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

$brAll   = branches_all();
$today   = strtotime(date('Y-m-d'));
$reasons = return_reasons();

/* ---------- เปิดดูใบเดียว ---------- */
$view = null;
if (isset($_GET['no']) && is_string($_GET['no']) && isset($_GET['b']) && is_string($_GET['b']) && isset($brAll[$_GET['b']])) {
    $view = return_doc_find($_GET['b'], trim($_GET['no']));
}

/* ---------- ตัวกรอง ---------- */
$fB     = (isset($_GET['b']) && is_string($_GET['b']) && isset($brAll[$_GET['b']])) ? $_GET['b'] : 'ALL';
$fMode  = (isset($_GET['mode']) && in_array($_GET['mode'], array('day', 'month', 'year'), true)) ? $_GET['mode'] : 'recent';
$fSort  = (isset($_GET['sort']) && $_GET['sort'] === 'asc') ? 'asc' : 'desc';
$fWhy   = (isset($_GET['why']) && is_string($_GET['why']) && isset($reasons[$_GET['why']])) ? $_GET['why'] : '';
$fStock = (isset($_GET['stock']) && in_array($_GET['stock'], array('in', 'out'), true)) ? $_GET['stock'] : '';

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

$rq = function ($chg) use ($fB, $fMode, $fSort, $fWhy, $fStock, $dayTs, $monTs, $year) {
    $q = array_merge(array('b' => $fB, 'mode' => $fMode, 'd' => date('Y-m-d', $dayTs), 'm' => date('Y-m', $monTs), 'y' => $year,
                           'why' => $fWhy, 'stock' => $fStock, 'sort' => $fSort), $chg);
    foreach (array('day' => 'd', 'month' => 'm', 'year' => 'y') as $md => $k) {
        if ($q['mode'] !== $md) {
            unset($q[$k]);
        }
    }
    foreach (array('why' => '', 'stock' => '', 'sort' => 'desc', 'b' => 'ALL', 'mode' => 'recent', 'p' => 1) as $k => $def) {
        if (isset($q[$k]) && (string) $q[$k] === (string) $def) {
            unset($q[$k]);
        }
    }
    return 'adm-return.php' . ($q ? '?' . http_build_query($q) : '');
};

/* ---------- รวบรวมใบรับคืน ---------- */
$rows  = array();
$codes = $fB === 'ALL' ? array_keys($brAll) : array($fB);
foreach (returns_range($codes, $from, $to) as $r) {          // มี branch / ts (เวลาที่ออกใบ) ในแถวแล้ว
    $rows[] = $r;
}

$sum = array('docs' => 0, 'qty' => 0, 'refund' => 0, 'cut' => 0, 'in' => 0, 'out' => 0, 'why' => array());
$tabN = array('' => 0, 'in' => 0, 'out' => 0);                  // จำนวนใบบนแท็บ (ตามเหตุผลที่เลือก)
foreach ($rows as $r) {
    $sum['docs']++;
    $sum['qty']    += $r['qty'];
    $sum['refund'] += $r['refund'];
    $sum['cut']    += max(0, $r['calc'] - $r['refund']);
    $sum[$r['restock'] ? 'in' : 'out'] += $r['qty'];
    $sum['why'][$r['reason']] = (isset($sum['why'][$r['reason']]) ? $sum['why'][$r['reason']] : 0) + 1;
}

foreach ($rows as $r) {
    if ($fWhy === '' || $r['reason'] === $fWhy) {
        $tabN['']++;
        $tabN[$r['restock'] ? 'in' : 'out']++;
    }
}
$rows = array_values(array_filter($rows, function ($r) use ($fWhy, $fStock) {
    return ($fWhy === '' || $r['reason'] === $fWhy)
        && ($fStock === '' || ($fStock === 'in') === (bool) $r['restock']);
}));
$dir = $fSort === 'asc' ? 1 : -1;
usort($rows, function ($a, $b) use ($dir) {
    return $a['ts'] === $b['ts'] ? 0 : (($a['ts'] < $b['ts'] ? -1 : 1) * $dir);
});
$per   = 100;
$total = count($rows);
$pages = max(1, (int) ceil($total / $per));
$pg    = isset($_GET['p']) ? max(1, min($pages, (int) $_GET['p'])) : 1;
$show  = array_slice($rows, ($pg - 1) * $per, $per);


$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = $view ? 'ใบรับคืน ' . $view['no'] : 'รับคืนสินค้า';
$PAGE_SUB       = $view ? branch_name($view['branch']) . ' · ' . thai_date_full(strtotime($view['date']))
                        : 'ตรวจสอบใบรับคืนของทุกสาขา · ' . $rangeTxt;
$NAV_ACTIVE     = 'adm-return.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if (isset($_GET['no']) && $view === null) { ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span>ไม่พบใบรับคืนนี้</span></div>
<?php } ?>

<?php if ($view !== null) { $v = $view; $rs = $reasons[$v['reason']]; $cut = $v['calc'] - $v['refund']; ?>
  <!-- ==================== รายละเอียดใบรับคืน ==================== -->
  <p class="hist-back">
    <a class="btn btn-ghost btn-sm" href="<?php echo e($rq(array('b' => $fB, 'mode' => 'month', 'm' => date('Y-m', strtotime($v['date']))))) ?>">‹ กลับไปรายการรับคืน</a>
  </p>
  <section class="card ret-view">
    <div class="card-head">
      <div>
        <h2><?php echo e($v['no']) ?></h2>
        <span class="sub"><?php echo e(branch_name($v['branch'])) ?> · <?php echo e(thai_date_full(strtotime($v['date']))) ?> <?php echo e($v['time']) ?> น. · รับคืนโดย <?php echo e($v['by']) ?></span>
      </div>
      <span class="bdg <?php echo $v['restock'] ? 'bdg-ok' : 'bdg-out' ?>"><?php echo $v['restock'] ? 'กลับเข้าสต๊อก' : 'ไม่เข้าสต๊อก · แยกเก็บ' ?></span>
    </div>

    <dl class="ret-meta">
      <div><dt>บิลเดิม</dt><dd><b><?php echo e($v['bill_no']) ?></b> · ซื้อวันที่ <?php echo e(thai_date_full(strtotime($v['bill_date']))) ?> · ขายโดย <?php echo e($v['bill_by']) ?></dd></div>
      <div><dt>คืนหลังซื้อ</dt><dd><?php echo (int) round((strtotime($v['date']) - strtotime($v['bill_date'])) / 86400) ?> วัน</dd></div>
      <div><dt>เหตุผล</dt><dd><?php echo e($rs['label']) ?><?php echo $v['note'] !== '' ? ' — ' . e($v['note']) : '' ?></dd></div>
      <div><dt>สต๊อก</dt><dd><?php echo e($rs['hint']) ?></dd></div>
    </dl>

    <div class="ret-gallery">
      <h3>รูปถ่ายแนบ <small><?php echo !empty($v['photos']) ? count($v['photos']) . ' รูป · กดเพื่อดูภาพใหญ่' : '' ?></small></h3>
      <?php if (empty($v['photos'])) { ?>
        <p class="adm-none">ไม่ได้แนบรูป</p>
      <?php } else { ?>
        <div class="ret-pics">
          <?php foreach ($v['photos'] as $i => $ph) { ?>
            <button type="button" class="ret-pic" data-pic="<?php echo e($ph) ?>" aria-label="ดูรูปที่ <?php echo $i + 1 ?>"><img src="<?php echo e($ph) ?>" alt="รูปแนบที่ <?php echo $i + 1 ?> ของ <?php echo e($v['no']) ?>" loading="lazy"></button>
          <?php } ?>
        </div>
      <?php } ?>
    </div>

    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th>สินค้า</th><th class="r">จำนวน</th><th class="r">ราคาที่ลูกค้าจ่าย / ชิ้น</th><th class="r">รวม</th></tr></thead>
        <tbody>
          <?php foreach ($v['lines'] as $l) { ?>
            <tr>
              <td data-label="สินค้า"><b><?php echo e($l['name']) ?></b><small class="hist-n"><?php echo e($l['sku']) ?></small></td>
              <td data-label="จำนวน" class="r num"><?php echo number_format($l['qty']) ?> <?php echo e($l['unit']) ?></td>
              <td data-label="ราคา / ชิ้น" class="r num"><?php echo e(money2($l['price'])) ?></td>
              <td data-label="รวม" class="r num"><?php echo e(money2($l['sum'])) ?></td>
            </tr>
          <?php } ?>
        </tbody>
        <tfoot>
          <tr><td colspan="3" class="r">ยอดคำนวณ</td><td class="r num"><?php echo e(money2($v['calc'])) ?></td></tr>
          <?php if (abs($cut) >= 0.01) { ?>
            <tr><td colspan="3" class="r">ปรับยอด<?php echo $v['refund_note'] !== '' ? ' — ' . e($v['refund_note']) : '' ?></td><td class="r num">−<?php echo e(money2($cut)) ?></td></tr>
          <?php } ?>
          <tr class="ret-total"><td colspan="3" class="r"><b>คืนเงินสด (จากลิ้นชักวันที่คืน)</b></td><td class="r num"><b><?php echo e(money2($v['refund'])) ?></b></td></tr>
        </tfoot>
      </table>
    </div>
  </section>
  <dialog class="pic-modal" id="pic-modal" aria-label="รูปแนบ">
    <button type="button" class="icon-btn ds-close" data-pic-close aria-label="ปิด"><svg class="ico"><use href="#i-x"/></svg></button>
    <img id="pic-big" src="" alt="รูปแนบขนาดใหญ่">
  </dialog>
  <script>
  (function () {
    var dlg = document.getElementById('pic-modal'), big = document.getElementById('pic-big');
    if (!dlg || !dlg.showModal) { return; }
    document.addEventListener('click', function (ev) {
      var t = ev.target.closest ? ev.target.closest('[data-pic]') : null;
      if (t) { big.src = t.getAttribute('data-pic'); dlg.showModal(); return; }
      if ((ev.target.closest && ev.target.closest('[data-pic-close]')) || ev.target === dlg || ev.target === big) { dlg.close(); }
    });
  })();
  </script>
  <?php require dirname(__FILE__) . '/inc/footer.php'; exit; ?>
<?php } ?>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter hist-filter">
  <form method="get" action="adm-return.php" class="acct-row">
    <div class="segs">
      <?php foreach (array('recent' => '30 วัน', 'day' => 'รายวัน', 'month' => 'รายเดือน', 'year' => 'รายปี') as $md => $lb) { ?>
        <a class="seg<?php echo $fMode === $md ? ' on' : '' ?>" href="<?php echo e($rq(array('mode' => $md))) ?>"><?php echo e($lb) ?></a>
      <?php } ?>
    </div>
    <input type="hidden" name="mode" value="<?php echo e($fMode) ?>">
    <?php if ($fMode === 'day') { ?>
      <a class="btn btn-ghost btn-sm" href="<?php echo e($rq(array('d' => date('Y-m-d', strtotime('-1 day', $dayTs))))) ?>" aria-label="วันก่อนหน้า">‹</a>
      <label class="sr-only" for="rd">วันที่</label>
      <input class="input acct-date" type="date" id="rd" name="d" value="<?php echo e(date('Y-m-d', $dayTs)) ?>" max="<?php echo e(date('Y-m-d')) ?>" onchange="this.form.submit()">
      <?php if ($dayTs < $today) { ?>
        <a class="btn btn-ghost btn-sm" href="<?php echo e($rq(array('d' => date('Y-m-d', strtotime('+1 day', $dayTs))))) ?>" aria-label="วันถัดไป">›</a>
      <?php } ?>
    <?php } elseif ($fMode === 'month') { ?>
      <a class="btn btn-ghost btn-sm" href="<?php echo e($rq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>" aria-label="เดือนก่อนหน้า">‹</a>
      <label class="sr-only" for="rm">เดือน</label>
      <input class="input acct-date" type="month" id="rm" name="m" value="<?php echo e(date('Y-m', $monTs)) ?>" max="<?php echo e(date('Y-m')) ?>" onchange="this.form.submit()">
      <?php if ($monTs < strtotime(date('Y-m-01'))) { ?>
        <a class="btn btn-ghost btn-sm" href="<?php echo e($rq(array('m' => date('Y-m', strtotime('+1 month', $monTs))))) ?>" aria-label="เดือนถัดไป">›</a>
      <?php } ?>
    <?php } elseif ($fMode === 'year') { ?>
      <label class="sr-only" for="ry">ปี</label>
      <select class="input acct-date" id="ry" name="y" onchange="this.form.submit()">
        <?php for ($y = (int) date('Y'); $y >= 2026; $y--) { ?>
          <option value="<?php echo $y ?>" <?php echo $y === $year ? 'selected' : '' ?>>ปี <?php echo $y + 543 ?></option>
        <?php } ?>
      </select>
    <?php } ?>

    <label class="sr-only" for="rb">สาขา</label>
    <select class="input acct-branch" id="rb" name="b" onchange="this.form.submit()">
      <option value="ALL">ทุกสาขา</option>
      <?php foreach ($brAll as $c => $x) { ?>
        <option value="<?php echo e($c) ?>" <?php echo $fB === $c ? 'selected' : '' ?>><?php echo e($x['name']) ?><?php echo empty($x['active']) ? ' (ปิดใช้งาน)' : '' ?></option>
      <?php } ?>
    </select>
    <label class="sr-only" for="rw">เหตุผล</label>
    <select class="input acct-branch" id="rw" name="why" onchange="this.form.submit()">
      <option value="">ทุกเหตุผล</option>
      <?php foreach ($reasons as $k => $x) { ?>
        <option value="<?php echo e($k) ?>" <?php echo $fWhy === $k ? 'selected' : '' ?>><?php echo e($x['label']) ?></option>
      <?php } ?>
    </select>
    <label class="sr-only" for="rs">เรียงลำดับ</label>
    <select class="input acct-branch" id="rs" name="sort" onchange="this.form.submit()">
      <option value="desc" <?php echo $fSort === 'desc' ? 'selected' : '' ?>>เวลา: ใหม่ → เก่า</option>
      <option value="asc" <?php echo $fSort === 'asc' ? 'selected' : '' ?>>เวลา: เก่า → ใหม่</option>
    </select>
    <?php if ($fStock !== '') { ?><input type="hidden" name="stock" value="<?php echo e($fStock) ?>"><?php } ?>
    <noscript><button class="btn btn-ghost btn-sm" type="submit">ดู</button></noscript>
  </form>
</section>

<!-- ==================== สรุป ==================== -->
<section class="mini num adm-kpi" aria-label="สรุปการรับคืน">
  <div class="m"><div class="lb">ใบรับคืน</div><div class="nm"><?php echo number_format($sum['docs']) ?></div>
    <div class="sb"><?php echo number_format($sum['qty']) ?> ชิ้น</div></div>
  <div class="m"><div class="lb">คืนเงินสด</div><div class="nm"><?php echo e(money2($sum['refund'])) ?></div>
    <div class="sb"><?php echo $sum['cut'] > 0 ? 'หักไว้จากยอดคำนวณ ' . e(money2($sum['cut'])) . ' บาท' : 'คืนเต็มยอดทุกใบ' ?></div></div>
  <div class="m"><div class="lb">สินค้าที่รับคืน</div><div class="nm"><?php echo number_format($sum['in']) ?> / <?php echo number_format($sum['out']) ?></div>
    <div class="sb">กลับเข้าสต๊อก / แยกเก็บ (ไม่ขายต่อ)</div></div>
</section>

<!-- ==================== รายการ ==================== -->
<section class="card">
  <div class="card-head">
    <div>
      <h2><?php echo $fB === 'ALL' ? 'ทุกสาขา' : e($brAll[$fB]['name']) ?> · <?php echo e($rangeTxt) ?></h2>
      <span class="sub">การรับคืนทำโดยพนักงานที่ได้รับสิทธิ์ “รับคืนสินค้า” · กดดูเพื่อตรวจรายละเอียดแต่ละใบ</span>
    </div>
  </div>
  <div class="cats tabbar" role="tablist">
    <a class="cat<?php echo $fStock === '' ? ' on' : '' ?>" href="<?php echo e($rq(array('stock' => ''))) ?>">ทั้งหมด <i><?php echo number_format($tabN['']) ?></i></a>
    <a class="cat<?php echo $fStock === 'in' ? ' on' : '' ?>" href="<?php echo e($rq(array('stock' => 'in'))) ?>">กลับเข้าสต๊อก <i><?php echo number_format($tabN['in']) ?></i></a>
    <a class="cat<?php echo $fStock === 'out' ? ' on' : '' ?>" href="<?php echo e($rq(array('stock' => 'out'))) ?>">ไม่เข้าสต๊อก <i><?php echo number_format($tabN['out']) ?></i></a>
  </div>

  <?php if (!$show) { ?>
    <p class="empty"><svg class="ico"><use href="#i-receipt"/></svg>ไม่มีการรับคืนในช่วงที่เลือก
      <?php if ($fMode === 'month' && $monTs === strtotime(date('Y-m-01'))) { ?>
        <br><a class="btn btn-ghost btn-sm" href="<?php echo e($rq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>">ดูเดือนก่อน</a>
      <?php } ?>
    </p>
  <?php } else { ?>
    <div class="tbl-wrap">
      <table class="tbl hist-all">
        <thead>
          <tr>
            <th><?php echo $fMode !== 'day' ? 'วันที่ · เวลา' : 'เวลา' ?></th>
            <th>สาขา</th>
            <th>ใบรับคืน / บิลเดิม</th>
            <th>เหตุผล</th>
            <th>รูป</th>
            <th>รับคืนโดย</th>
            <th class="r">จำนวน</th>
            <th class="r">คืนเงิน</th>
            <th class="r"><span class="sr-only">ดู</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($show as $r) { ?>
            <tr>
              <td data-label="เวลา" class="num nowrap"><?php if ($fMode !== 'day') { ?><?php echo e(thai_day_month(strtotime($r['date']))) ?> · <?php } ?><?php echo e($r['time']) ?></td>
              <td data-label="สาขา"><span class="hist-br"><?php echo e($brAll[$r['branch']]['short']) ?></span></td>
              <td data-label="ใบรับคืน" class="nowrap"><b class="hist-t"><?php echo e($r['no']) ?></b><small class="hist-n">บิล <?php echo e($r['bill_no']) ?> · ซื้อ <?php echo e(thai_day_month(strtotime($r['bill_date']))) ?></small></td>
              <td data-label="เหตุผล">
                <span class="bdg <?php echo $r['restock'] ? 'bdg-ok' : 'bdg-out' ?>"><?php echo e($reasons[$r['reason']]['label']) ?></span>
              </td>
              <td data-label="รูป">
                <?php if (!empty($r['photos'])) { ?>
                  <a class="ret-mini" href="adm-return.php?no=<?php echo e(rawurlencode($r['no'])) ?>&amp;b=<?php echo e(rawurlencode($r['branch'])) ?>" title="ดูรูปแนบ">
                    <img src="<?php echo e($r['photos'][0]) ?>" alt="" loading="lazy"><?php if (count($r['photos']) > 1) { ?><i>+<?php echo count($r['photos']) - 1 ?></i><?php } ?>
                  </a>
                <?php } else { ?><span class="adm-none">—</span><?php } ?>
              </td>
              <td data-label="รับคืนโดย" class="nowrap"><?php echo e($r['by']) ?></td>
              <td data-label="จำนวน" class="r num nowrap"><?php echo number_format($r['qty']) ?> ชิ้น</td>
              <td data-label="คืนเงิน" class="r num nowrap"><?php echo e(money2($r['refund'])) ?> ฿<?php if ($r['calc'] - $r['refund'] >= 0.01) { ?><small class="hist-n">จาก <?php echo e(money2($r['calc'])) ?></small><?php } ?></td>
              <td data-label="" class="r"><a class="btn btn-ghost btn-sm" href="adm-return.php?no=<?php echo e(rawurlencode($r['no'])) ?>&amp;b=<?php echo e(rawurlencode($r['branch'])) ?>">ดู</a></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1) { ?>
      <nav class="hist-pager" aria-label="หน้า">
        <?php if ($pg > 1) { ?><a class="btn btn-ghost btn-sm" href="<?php echo e($rq(array('p' => $pg - 1))) ?>">‹ ก่อนหน้า</a><?php } ?>
        <span>หน้า <?php echo $pg ?> / <?php echo $pages ?> · <?php echo number_format($total) ?> ใบ</span>
        <?php if ($pg < $pages) { ?><a class="btn btn-ghost btn-sm" href="<?php echo e($rq(array('p' => $pg + 1))) ?>">ถัดไป ›</a><?php } ?>
      </nav>
    <?php } ?>
  <?php } ?>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
