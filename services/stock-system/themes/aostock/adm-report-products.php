<?php
/**
 * FILE: themes/aostock/adm-report-products.php
 * ROLE: [ผู้ดูแล] สินค้าขายดี 100 อันดับ
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_sale, ao_stock_sale_item, ao_stock_product, ao_stock_balance (ผ่าน api.php — product_sales)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 8: SQL คำสั่งเดียวทั้งช่วง (เดิมวนบิลทีละวันทีละสาขา)
 *   - [x] ช่วงที่ 9: ชื่อสินค้าไม่เป็นลิงก์เมื่อเมนูประวัติเคลื่อนไหวถูกปิดจากหลังบ้าน
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   [ผู้ดูแล] สินค้าขายดี 100 อันดับ
   ----------------------------------------------------------
   mode = recent (30 วันล่าสุด — ค่าเริ่มต้น) | day | month | year · d / m / y
   b    = ALL | รหัสสาขา · cat = หมวด · sort = qty (จำนวนชิ้น) | total (ยอดขาย)
   export=csv → ดาวน์โหลดทั้ง 100 อันดับเป็น CSV (เปิดใน Excel ได้)
   ยอดเงินรายสินค้าหักส่วนลดท้ายบิลตามสัดส่วน · ไม่นับบิลยกเลิก — product_sales()
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

$brAll   = branches_all();
$cats    = product_cats();
$today   = strtotime(date('Y-m-d'));
$yearMin = 2026;
$limit   = 100;
$g       = function ($k, $def = '') { return (isset($_GET[$k]) && is_string($_GET[$k])) ? trim($_GET[$k]) : $def; };

$fB    = isset($brAll[$g('b')]) ? $g('b') : 'ALL';
$fCat  = in_array($g('cat'), $cats, true) ? $g('cat') : '';
$fMode = in_array($g('mode'), array('day', 'month', 'year'), true) ? $g('mode') : 'recent';
$fSort = $g('sort') === 'total' ? 'total' : 'qty';
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

$pq = function ($chg) use ($fB, $fCat, $fMode, $fSort, $dayTs, $monTs, $year) {
    $q = array_merge(array('b' => $fB, 'cat' => $fCat, 'mode' => $fMode, 'd' => date('Y-m-d', $dayTs), 'm' => date('Y-m', $monTs),
                           'y' => $year, 'sort' => $fSort), $chg);
    foreach (array('day' => 'd', 'month' => 'm', 'year' => 'y') as $md => $k) {
        if ($q['mode'] !== $md) {
            unset($q[$k]);
        }
    }
    foreach (array('b' => 'ALL', 'cat' => '', 'mode' => 'recent', 'sort' => 'qty') as $k => $def) {
        if ((string) $q[$k] === $def) {
            unset($q[$k]);
        }
    }
    return 'adm-report-products.php' . ($q ? '?' . http_build_query($q) : '');
};

$codes = $fB === 'ALL' ? array_keys($brAll) : array($fB);
$rows  = product_sales($codes, $from, $to);
if ($fCat !== '') {
    $rows = array_filter($rows, function ($r) use ($fCat) { return $r['cat'] === $fCat; });
}
$grandQty = 0;
$grand    = 0;
foreach ($rows as $r) {
    $grandQty += $r['qty'];
    $grand    += $r['total'];
}
uasort($rows, function ($a, $b) use ($fSort) {
    $o = $fSort === 'total' ? 'qty' : 'total';
    if ($a[$fSort] == $b[$fSort]) {
        return $a[$o] == $b[$o] ? 0 : ($a[$o] < $b[$o] ? 1 : -1);
    }
    return $a[$fSort] < $b[$fSort] ? 1 : -1;
});
$soldSku = count($rows);
$rows    = array_slice($rows, 0, $limit, true);
$max     = 0;
foreach ($rows as $r) {
    $max = max($max, $r[$fSort]);
}

/* ---------- CSV ---------- */
if ($g('export') === 'csv') {
    $csv = array();
    $i = 0;
    foreach ($rows as $r) {
        $i++;
        $p = product_by_sku($r['sku']);
        $csv[] = array($i, $r['sku'], $r['name'], $r['cat'], $r['qty'], $r['unit'], csv_money($r['total']), $r['bills'],
                       $grandQty > 0 ? round($r['qty'] / $grandQty * 100, 2) : 0, $p ? product_qty($p, $fB) : '');
    }
    csv_send('aostock-top-products-' . date('Ymd', $from) . '-' . date('Ymd', $to) . ($fB !== 'ALL' ? '-' . $fB : '') . '.csv',
             array('อันดับ', 'SKU', 'สินค้า', 'หมวด', 'ขายได้', 'หน่วย', 'ยอดขาย (บาท)', 'จำนวนบิล', '% ของจำนวนที่ขาย', 'คงเหลือตอนนี้'),
             $csv);
}

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = 'สินค้าขายดี ' . $limit . ' อันดับ';
$PAGE_SUB       = ($fB === 'ALL' ? 'ทุกสาขา' : $brAll[$fB]['name']) . ' · ' . $rangeTxt . ' · เรียงตาม' . ($fSort === 'qty' ? 'จำนวนที่ขาย' : 'ยอดขาย');
$NAV_ACTIVE     = 'adm-report-products.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter hist-filter">
  <form method="get" action="adm-report-products.php" class="acct-row">
    <div class="segs">
      <?php foreach (array('recent' => '30 วัน', 'day' => 'รายวัน', 'month' => 'รายเดือน', 'year' => 'รายปี') as $md => $lb) { ?>
        <a class="seg<?php echo $fMode === $md ? ' on' : '' ?>" href="<?php echo e($pq(array('mode' => $md))) ?>"><?php echo e($lb) ?></a>
      <?php } ?>
    </div>
    <input type="hidden" name="mode" value="<?php echo e($fMode) ?>">
    <?php if ($fMode === 'day') { ?>
      <a class="btn btn-ghost btn-sm" href="<?php echo e($pq(array('d' => date('Y-m-d', strtotime('-1 day', $dayTs))))) ?>" aria-label="วันก่อนหน้า">‹</a>
      <label class="sr-only" for="pd">วันที่</label>
      <input class="input acct-date" type="date" id="pd" name="d" value="<?php echo e(date('Y-m-d', $dayTs)) ?>" max="<?php echo e(date('Y-m-d')) ?>" onchange="this.form.submit()">
      <?php if ($dayTs < $today) { ?><a class="btn btn-ghost btn-sm" href="<?php echo e($pq(array('d' => date('Y-m-d', strtotime('+1 day', $dayTs))))) ?>" aria-label="วันถัดไป">›</a><?php } ?>
    <?php } elseif ($fMode === 'month') { ?>
      <a class="btn btn-ghost btn-sm" href="<?php echo e($pq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>" aria-label="เดือนก่อนหน้า">‹</a>
      <label class="sr-only" for="pm">เดือน</label>
      <input class="input acct-date" type="month" id="pm" name="m" value="<?php echo e(date('Y-m', $monTs)) ?>" max="<?php echo e(date('Y-m')) ?>" onchange="this.form.submit()">
      <?php if ($monTs < strtotime(date('Y-m-01'))) { ?><a class="btn btn-ghost btn-sm" href="<?php echo e($pq(array('m' => date('Y-m', strtotime('+1 month', $monTs))))) ?>" aria-label="เดือนถัดไป">›</a><?php } ?>
    <?php } elseif ($fMode === 'year') { ?>
      <label class="sr-only" for="py">ปี</label>
      <select class="input acct-date" id="py" name="y" onchange="this.form.submit()">
        <?php for ($y = (int) date('Y'); $y >= $yearMin; $y--) { ?>
          <option value="<?php echo $y ?>" <?php echo $y === $year ? 'selected' : '' ?>>ปี <?php echo $y + 543 ?></option>
        <?php } ?>
      </select>
    <?php } ?>
    <label class="sr-only" for="pb">สาขา</label>
    <select class="input acct-branch" id="pb" name="b" onchange="this.form.submit()">
      <option value="ALL">ทุกสาขา</option>
      <?php foreach ($brAll as $c => $x) { ?>
        <option value="<?php echo e($c) ?>" <?php echo $fB === $c ? 'selected' : '' ?>><?php echo e($x['name']) ?><?php echo empty($x['active']) ? ' (ปิดใช้งาน)' : '' ?></option>
      <?php } ?>
    </select>
    <label class="sr-only" for="pc">หมวด</label>
    <select class="input acct-branch" id="pc" name="cat" onchange="this.form.submit()">
      <option value="">ทุกหมวด</option>
      <?php foreach ($cats as $c) { ?>
        <option value="<?php echo e($c) ?>" <?php echo $fCat === $c ? 'selected' : '' ?>><?php echo e($c) ?></option>
      <?php } ?>
    </select>
    <label class="sr-only" for="ps">เรียงตาม</label>
    <select class="input acct-branch" id="ps" name="sort" onchange="this.form.submit()">
      <option value="qty" <?php echo $fSort === 'qty' ? 'selected' : '' ?>>เรียงตามจำนวนที่ขาย</option>
      <option value="total" <?php echo $fSort === 'total' ? 'selected' : '' ?>>เรียงตามยอดขาย</option>
    </select>
    <a class="btn btn-ghost btn-sm rep-csv" href="<?php echo e($pq(array('export' => 'csv'))) ?>"><svg class="ico"><use href="#i-in"/></svg> ดาวน์โหลด CSV</a>
  </form>
</section>

<!-- ==================== สรุป ==================== -->
<section class="mini num adm-kpi" aria-label="สรุปสินค้าขายดี">
  <div class="m"><div class="lb">สินค้าที่ขายได้</div><div class="nm"><?php echo number_format($soldSku) ?> รายการ</div>
    <div class="sb">จากทั้งหมด <?php echo number_format(count(products_list())) ?> รายการ · แสดง <?php echo number_format(count($rows)) ?> อันดับแรก</div></div>
  <div class="m"><div class="lb">จำนวนที่ขาย</div><div class="nm"><?php echo number_format($grandQty) ?> ชิ้น</div>
    <div class="sb"><?php echo $fCat !== '' ? 'เฉพาะหมวด ' . e($fCat) : 'ทุกหมวด' ?></div></div>
  <div class="m"><div class="lb">ยอดขาย</div><div class="nm"><?php echo e(money2($grand)) ?></div>
    <div class="sb">หักส่วนลดท้ายบิลแล้ว · ไม่นับบิลยกเลิก</div></div>
</section>

<!-- ==================== ตาราง ==================== -->
<section class="card">
  <div class="card-head">
    <div>
      <h2>สินค้าขายดี <?php echo $limit ?> อันดับ · <?php echo e($rangeTxt) ?></h2>
      <span class="sub">กดชื่อสินค้าเพื่อดูประวัติเคลื่อนไหว</span>
    </div>
  </div>
  <?php if (!$rows) { ?>
    <p class="empty"><svg class="ico"><use href="#i-chart"/></svg>ไม่มียอดขายในช่วงนี้</p>
  <?php } else { ?>
    <div class="tbl-wrap">
      <table class="tbl hist-all num">
        <thead>
          <tr>
            <th>สินค้า</th>
            <th class="r">ขายได้</th>
            <th class="r">ยอดขาย</th>
            <th class="r">บิล</th>
            <?php if ($fB === 'ALL') { ?><th>แยกสาขา (ชิ้น)</th><?php } ?>
            <th class="r">คงเหลือตอนนี้</th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 0; foreach ($rows as $r) { $i++; $p = product_by_sku($r['sku']); ?>
            <tr>
              <td data-label="สินค้า">
                <b class="hist-t"><span class="rep-rank<?php echo $i <= 3 ? ' r' . $i : '' ?>"><?php echo $i ?></span><?php if (menu_enabled('adm-movements.php')) { ?><a class="mv-feed-p" href="adm-movements.php?sku=<?php echo e(rawurlencode($r['sku'])) ?>"><?php echo e($r['name']) ?></a><?php } else { ?><?php echo e($r['name']) ?><?php } ?></b>
                <small class="hist-n"><?php echo e($r['sku']) ?> · <?php echo e($r['cat']) ?></small>
                <span class="rep-share"><i style="width:<?php echo $max > 0 ? (int) round($r[$fSort] / $max * 100) : 0 ?>%"></i></span>
              </td>
              <td data-label="ขายได้" class="r nowrap"><b><?php echo number_format($r['qty']) ?></b> <small><?php echo e($r['unit']) ?></small>
                <small class="hist-n"><?php echo $grandQty > 0 ? number_format($r['qty'] / $grandQty * 100, 1) : '0.0' ?>%</small></td>
              <td data-label="ยอดขาย" class="r nowrap"><?php echo e(money2($r['total'])) ?></td>
              <td data-label="บิล" class="r"><?php echo number_format($r['bills']) ?></td>
              <?php if ($fB === 'ALL') { ?>
                <td data-label="แยกสาขา">
                  <?php foreach ($r['branches'] as $c => $n) { ?><span class="rep-chip"><?php echo e(isset($brAll[$c]) ? $brAll[$c]['short'] : $c) ?> <b><?php echo number_format($n) ?></b></span><?php } ?>
                </td>
              <?php } ?>
              <td data-label="คงเหลือตอนนี้" class="r nowrap">
                <?php if ($p) { $st = $fB === 'ALL' ? product_status($p, 'ALL') : branch_status($p, $fB); ?>
                  <span class="stk stk-<?php echo e($st) ?>"><?php echo number_format(product_qty($p, $fB)) ?></span>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } ?>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
