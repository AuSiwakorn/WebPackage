<?php
/**
 * FILE: themes/aostock/adm-report-daily.php
 * ROLE: [ผู้ดูแล] สรุปยอดขายรายวัน (เทียบทุกสาขา)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_sale, ao_stock_branch (ผ่าน api.php — daily_matrix / sales_scan)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 8: ยอดจาก SQL ทั้งเดือน · เอาโหมด "ดูตัวอย่าง 10 สาขา" (สาขาจำลอง) ออก · เดือนแรก = เดือนที่มีบิลแรก
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   [ผู้ดูแล] สรุปยอดขายรายวัน (เทียบทุกสาขา)
   ----------------------------------------------------------
   ตารางเดือนละหน้า: แถว = ทุกวันของเดือน (1 → สิ้นเดือน) · หัวคอลัมน์ = สาขา (รองรับ 10 สาขาขึ้นไป)
     - คอลัมน์ "วันที่" และ "รวมทุกสาขา" ติดอยู่กับที่ เลื่อนดูสาขาแนวนอนได้
     - สีพื้นช่องเข้มตามยอด (เทียบทั้งเดือน) · ช่องตัวหนาสีเขียว = สาขาที่สูงสุดของวันนั้น
     - ท้ายตาราง: รวมทั้งเดือน · เฉลี่ยต่อวันที่ขาย · วันที่ยอดสูงสุด · สัดส่วน %
     - กดที่ยอด → popup บิลและสินค้าที่ขายของสาขานั้นในวันนั้น (adm-day-sales.php)
   ?m=ปปปป-ดด  เดือน · v = total | bills | qty · export=csv ดาวน์โหลด
   เลือกเดือนได้ตั้งแต่เดือนที่มีบิลแรก (sales_first_day)
   ข้อมูล: บิลชุดเดียวกับหน้าบัญชี / รายงาน (ไม่นับบิลยกเลิก) — daily_matrix() ใน api.php
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user  = require_login();                // หน้า adm- : เฉพาะผู้ดูแล
$brAll = branches_all();
$g     = function ($k, $def = '') { return (isset($_GET[$k]) && is_string($_GET[$k])) ? trim($_GET[$k]) : $def; };

$fV    = in_array($g('v'), array('total', 'bills', 'qty'), true) ? $g('v') : 'total';
$minTs = strtotime(date('Y-m-01', sales_first_day()));
$monTs = strtotime(date('Y-m-01'));
if (preg_match('/^\d{4}-\d{2}$/', $g('m')) && ($x = strtotime($g('m') . '-01')) !== false) {
    $monTs = max($minTs, min($x, strtotime(date('Y-m-01'))));
}

/* สาขาที่แสดง: เปิดใช้งาน หรือปิดแล้วแต่ยังมียอดในเดือนนี้ (เช็กหลังคำนวณ) */
$branches = $brAll;
$mx = daily_matrix($branches, $monTs, $fV);
foreach ($branches as $c => $b) {
    if (empty($b['active']) && $mx['col'][$c] == 0) {
        unset($branches[$c]);
    }
}
$codes = array_keys($branches);

$rq = function ($chg) use ($fV, $monTs) {
    $q = array_merge(array('m' => date('Y-m', $monTs), 'v' => $fV), $chg);
    if ($q['v'] === 'total') {
        unset($q['v']);
    }
    return 'adm-report-daily.php?' . http_build_query($q);
};
$fmt    = function ($v) use ($fV) { return $fV === 'total' ? money2($v) : number_format($v); };
$vLabel = array('total' => 'ยอดขาย (บาท)', 'bills' => 'จำนวนบิล', 'qty' => 'จำนวนชิ้น');
$opened = 0;                                                  // วันที่มีการขายอย่างน้อย 1 สาขา
foreach ($mx['rows'] as $r) {
    $opened += $r['sum'] > 0 ? 1 : 0;
}

/* ---------- CSV ---------- */
if ($g('export') === 'csv') {
    $head = array('วันที่', 'วัน');
    foreach ($codes as $c) {
        $head[] = $branches[$c]['name'];
    }
    $head[] = 'รวมทุกสาขา';
    $csv = array();
    foreach ($mx['rows'] as $k => $r) {
        $line = array(date('Y-m-d', $r['ts']), thai_dow_short($r['ts']));
        foreach ($codes as $c) {
            $line[] = $r['future'] ? '' : ($fV === 'total' ? csv_money($r['cells'][$c]) : $r['cells'][$c]);
        }
        $line[] = $r['future'] ? '' : ($fV === 'total' ? csv_money($r['sum']) : $r['sum']);
        $csv[] = $line;
    }
    $line = array('รวมทั้งเดือน', '');
    foreach ($codes as $c) {
        $line[] = $fV === 'total' ? csv_money($mx['col'][$c]) : $mx['col'][$c];
    }
    $line[] = $fV === 'total' ? csv_money($mx['grand']) : $mx['grand'];
    $csv[] = $line;
    csv_send('aostock-daily-sales-' . date('Y-m', $monTs) . '-' . $fV . '.csv', $head, $csv);
}

$rank = $mx['col'];
arsort($rank);
$topC = key($rank);

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = 'สรุปยอดขายรายวัน';
$PAGE_SUB       = $vLabel[$fV] . 'ของทุกสาขา แยกรายวัน · ' . thai_month_full($monTs);
$NAV_ACTIVE     = 'adm-report-daily.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter hist-filter">
  <form method="get" action="adm-report-daily.php" class="acct-row">
    <?php if ($monTs > $minTs): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($rq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>" aria-label="เดือนก่อนหน้า">‹</a>
    <?php endif; ?>
    <label class="sr-only" for="dm">เดือน</label>
    <input class="input acct-date" type="month" id="dm" name="m" value="<?= e(date('Y-m', $monTs)) ?>"
           min="<?= e(date('Y-m', $minTs)) ?>" max="<?= e(date('Y-m')) ?>" onchange="this.form.submit()">
    <?php if ($monTs < strtotime(date('Y-m-01'))): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($rq(array('m' => date('Y-m', strtotime('+1 month', $monTs))))) ?>" aria-label="เดือนถัดไป">›</a>
      <a class="btn btn-ghost btn-sm" href="<?= e($rq(array('m' => date('Y-m')))) ?>">เดือนนี้</a>
    <?php endif; ?>
    <?php if ($fV !== 'total'): ?><input type="hidden" name="v" value="<?= e($fV) ?>"><?php endif; ?>
    <div class="segs">
      <?php foreach (array('total' => 'ยอดขาย', 'bills' => 'บิล', 'qty' => 'ชิ้น') as $k => $lb): ?>
        <a class="seg<?= $fV === $k ? ' on' : '' ?>" href="<?= e($rq(array('v' => $k))) ?>"><?= e($lb) ?></a>
      <?php endforeach; ?>
    </div>
    <a class="btn btn-ghost btn-sm rep-csv" href="<?= e($rq(array('export' => 'csv'))) ?>"><svg class="ico"><use href="#i-in"/></svg> ดาวน์โหลด CSV</a>
  </form>
</section>

<!-- ==================== ตัวเลขสรุปของเดือน ==================== -->
<section class="mini num adm-kpi dly-kpi" aria-label="สรุปของเดือน">
  <div class="m"><div class="lb">รวมทั้งเดือน · <?= count($codes) ?> สาขา</div><div class="nm"><?= e($fmt($mx['grand'])) ?></div>
    <div class="sb">ขาย <?= number_format($opened) ?> วัน</div></div>
  <div class="m"><div class="lb">เฉลี่ยต่อวันที่ขาย</div><div class="nm"><?= e($fmt($opened ? $mx['grand'] / $opened : 0)) ?></div>
    <div class="sb">รวมทุกสาขา</div></div>
  <div class="m"><div class="lb">สาขาที่ทำได้มากสุด</div><div class="nm"><?= $mx['grand'] > 0 ? e($branches[$topC]['short']) : '—' ?></div>
    <div class="sb"><?= $mx['grand'] > 0 ? e($fmt($rank[$topC])) . ' · ' . number_format($rank[$topC] / $mx['grand'] * 100, 1) . '%' : 'ยังไม่มียอด' ?></div></div>
  <div class="m"><div class="lb">วันที่ยอดรวมสูงสุด</div>
    <div class="nm"><?= $mx['bestDay'][1] !== '' ? e(thai_dow_short(strtotime($mx['bestDay'][1])) . ' ' . thai_day_month(strtotime($mx['bestDay'][1]))) : '—' ?></div>
    <div class="sb"><?= $mx['bestDay'][1] !== '' ? e($fmt($mx['bestDay'][0])) : '' ?></div></div>
</section>

<!-- ==================== ตาราง ==================== -->
<section class="card">
  <div class="card-head">
    <div>
      <h2><?= e($vLabel[$fV]) ?> รายวัน · <?= e(thai_month_full($monTs)) ?></h2>
      <span class="sub">สีพื้นเข้ม = ยอดมาก (เทียบทั้งเดือน) · <b class="dly-k">ตัวหนาสีเขียว</b> = สาขาที่สูงสุดของวันนั้น
        · กดที่ยอดเพื่อดูบิลและสินค้าที่ขาย<?= count($codes) > 5 ? ' · เลื่อนตารางซ้าย–ขวาเพื่อดูสาขาอื่น' : '' ?></span>
    </div>
  </div>
  <div class="dly-wrap">
    <table class="dly num">
      <thead>
        <tr>
          <th class="dly-d">วันที่</th>
          <?php foreach ($codes as $c): ?>
            <th class="r" title="<?= e($branches[$c]['name']) ?>"><?= e($branches[$c]['short']) ?></th>
          <?php endforeach; ?>
          <th class="r dly-t">รวมทุกสาขา</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($mx['rows'] as $k => $r):
            $sun = (int) date('w', $r['ts']) === 0;
            $max = 0;
            foreach ($codes as $c) { $max = max($max, $r['cells'][$c]); } ?>
          <tr class="<?= $sun ? 'dly-sun' : '' ?><?= $r['future'] ? ' dly-fut' : '' ?><?= $k === date('Ymd') ? ' dly-today' : '' ?>">
            <th class="dly-d" scope="row"><b><?= (int) date('j', $r['ts']) ?></b> <small><?= e(thai_dow_short($r['ts'])) ?></small></th>
            <?php foreach ($codes as $c): $v = $r['cells'][$c];
                $heat = ($v > 0 && $mx['cellMax'] > 0) ? round(0.05 + 0.30 * $v / $mx['cellMax'], 3) : 0; ?>
              <td class="r<?= ($v > 0 && $v == $max && count($codes) > 1) ? ' dly-top' : '' ?>"<?= $heat ? ' style="--h:' . $heat . '"' : '' ?>>
                <?php if ($r['future']): ?><span class="dly-0"></span>
                <?php elseif ($v > 0): ?>
                  <button type="button" class="cell-link" data-day-sales="<?= e($c . '|' . $k) ?>" title="<?= e($branches[$c]['name']) ?> · ดูบิลและสินค้าที่ขาย"><?= e($fmt($v)) ?></button>
                <?php else: ?><span class="<?= $v ? '' : 'dly-0' ?>"><?= $v ? e($fmt($v)) : '—' ?></span><?php endif; ?>
              </td>
            <?php endforeach; ?>
            <td class="r dly-t">
              <?php if ($r['future']): ?><span class="dly-0"></span>
              <?php elseif ($r['sum'] > 0): ?>
                <button type="button" class="cell-link" data-day-sales="<?= e('ALL|' . $k) ?>" title="ดูบิลและสินค้าที่ขายของทุกสาขาวันนี้"><b><?= e($fmt($r['sum'])) ?></b></button>
              <?php else: ?><b class="<?= $r['sum'] ? '' : 'dly-0' ?>"><?= $r['sum'] ? e($fmt($r['sum'])) : '—' ?></b><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="dly-sum">
          <th class="dly-d" scope="row">รวมทั้งเดือน</th>
          <?php foreach ($codes as $c): ?><td class="r"><b><?= e($fmt($mx['col'][$c])) ?></b></td><?php endforeach; ?>
          <td class="r dly-t"><b><?= e($fmt($mx['grand'])) ?></b></td>
        </tr>
        <tr>
          <th class="dly-d" scope="row">เฉลี่ย / วันที่ขาย</th>
          <?php foreach ($codes as $c): ?>
            <td class="r"><?= $mx['open'][$c] ? e($fmt($mx['col'][$c] / $mx['open'][$c])) : '—' ?><small><?= number_format($mx['open'][$c]) ?> วัน</small></td>
          <?php endforeach; ?>
          <td class="r dly-t"><?= $opened ? e($fmt($mx['grand'] / $opened)) : '—' ?><small><?= number_format($opened) ?> วัน</small></td>
        </tr>
        <tr>
          <th class="dly-d" scope="row">วันที่ขายได้มากสุด</th>
          <?php foreach ($codes as $c): $bst = $mx['best'][$c]; ?>
            <td class="r"><?= $bst[1] !== '' ? e($fmt($bst[0])) . '<small>' . e(thai_dow_short(strtotime($bst[1])) . ' ' . thai_day_month(strtotime($bst[1]))) . '</small>' : '—' ?></td>
          <?php endforeach; ?>
          <td class="r dly-t"><?= $mx['bestDay'][1] !== '' ? e($fmt($mx['bestDay'][0])) . '<small>' . e(thai_dow_short(strtotime($mx['bestDay'][1])) . ' ' . thai_day_month(strtotime($mx['bestDay'][1]))) . '</small>' : '—' ?></td>
        </tr>
        <tr>
          <th class="dly-d" scope="row">สัดส่วนของทั้งหมด</th>
          <?php foreach ($codes as $c): ?>
            <td class="r"><?= $mx['grand'] > 0 ? number_format($mx['col'][$c] / $mx['grand'] * 100, 1) : '0.0' ?>%</td>
          <?php endforeach; ?>
          <td class="r dly-t">100%</td>
        </tr>
      </tfoot>
    </table>
  </div>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
