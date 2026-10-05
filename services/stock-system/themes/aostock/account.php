<?php
/**
 * FILE: themes/aostock/account.php
 * ROLE: บิลขายและเงินเข้า (ฝ่ายบัญชี / ผู้ดูแล)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_sale, ao_stock_return (ผ่าน api.php)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 7: บิล / เงินคืนอ่านจากตาราง · เอาแถบ "ข้อมูลตัวอย่าง" ออก
 *   - [x] ช่วงที่ 8: สรุปรายเดือน (acct_month) และ CSV ทั้งเดือน (acct_bills_range) เป็น SQL ทั้งช่วง
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   บิลขายและเงินเข้า (ฝ่ายบัญชี / ผู้ดูแล)
   ----------------------------------------------------------
   - เลือกวัน สาขา และประเภทบิล (ทั้งหมด / VAT / ไม่ VAT)
   - สรุปต่อสาขา: จำนวนบิล VAT / ไม่ VAT · ยอดขาย · VAT · เงินสด · โอน · คืนเงิน · เงินเข้าสุทธิ
   - รายการบิลเรียงตามเลขที่ รวมบิลที่ยกเลิก (เลขที่ต้องครบ ห้ามหาย)
   - ทุกบิลกด "ดู / พิมพ์" ได้ → popup แสดงบิล (bill-print.php) เลือก 80 มม. / A4 แล้วสั่งพิมพ์
     บิล VAT = ใบเสร็จรับเงิน / ใบกำกับภาษีอย่างย่อ · บิลไม่ VAT = บิลเงินสด (ใช้ชุดเลขที่แยกกัน)
   - สรุปทั้งเดือน + ดาวน์โหลด CSV (เปิดด้วย Excel ได้) ไว้ทำรายงานภาษีขาย / ส่งสำนักงานบัญชี
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();
if (!in_array($user['role'], array('account', 'admin'), true)) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$today = strtotime(date('Y-m-d'));
$br    = branches_all();             // รวมสาขาที่ปิดใช้งานแล้ว — บิลเก่ายังต้องตรวจได้

/* ---------- ตัวกรอง ---------- */
$dIn = isset($_GET['d']) ? preg_replace('/[^0-9-]/', '', $_GET['d']) : '';
$ts  = $dIn !== '' ? strtotime($dIn) : $today;
if ($ts === false || $ts > $today || $ts < strtotime('-60 day', $today)) {
    $ts = $today;
}
$bSel = (isset($_GET['b']) && isset($br[$_GET['b']])) ? $_GET['b'] : 'ALL';
$tSel = (isset($_GET['t']) && in_array($_GET['t'], array('vat', 'novat'), true)) ? $_GET['t'] : 'all';
$codes = $bSel === 'ALL' ? array_keys($br) : array($bSel);

/* ---------- ดาวน์โหลด CSV ทั้งเดือน ---------- */
if (isset($_GET['export'])) {
    $fn = 'aostock-bills-' . date('Y-m', $ts) . ($bSel !== 'ALL' ? '-' . $bSel : '') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $fn . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");                       // BOM ให้ Excel อ่านภาษาไทยถูก
    fputcsv($out, array('วันที่', 'เวลา', 'สาขา', 'เลขผู้เสียภาษี', 'สาขาที่', 'เลขที่บิล', 'ประเภท',
                        'ชำระโดย', 'ราคาเต็ม', 'ส่วนลด', 'มูลค่าก่อน VAT', 'VAT', 'รวม', 'สถานะ', 'พนักงาน'));
    $end = min(strtotime(date('Y-m-t', $ts)), $today);
    foreach (acct_bills_range($codes, strtotime(date('Y-m-01', $ts)), $end) as $b) {   // คิวรีเดียวทั้งเดือน เรียงวัน → สาขา → เวลา
        $c = $b['branch'];
        $b = acct_bill_row($b, $c);
        if (($tSel === 'vat' && !$b['vat']) || ($tSel === 'novat' && $b['vat'])) {
            continue;
        }
        $void = !empty($b['void']);
        fputcsv($out, array(date('Y-m-d', strtotime($b['date'])), $b['time'], branch_name($c), acct_setting($c, 'tax_id'),
            acct_setting($c, 'tax_branch'), $b['no'], bill_type_label($b['vat']),
            $b['method'] === 'cash' ? 'เงินสด' : 'โอน/พร้อมเพย์',
            $void ? '0.00' : number_format(isset($b['subtotal']) ? $b['subtotal'] : $b['total'], 2, '.', ''),
            $void ? '0.00' : number_format(isset($b['discount']) ? $b['discount'] : 0, 2, '.', ''),
            $void ? '0.00' : number_format($b['base'], 2, '.', ''),
            $void ? '0.00' : number_format($b['vatamt'], 2, '.', ''),
            $void ? '0.00' : number_format($b['total'], 2, '.', ''),
            $void ? 'ยกเลิก' : 'ปกติ', $b['by']));
    }
    fclose($out);
    exit;
}

/* ---------- สรุปของวัน ---------- */
$days = array();
$tot  = array('bills' => 0, 'v' => 0, 'n' => 0, 'void' => 0, 'total' => 0, 'base' => 0, 'vat' => 0,
              'cash' => 0, 'transfer' => 0, 'refund' => 0, 'net' => 0);
$rows = array();
foreach ($codes as $c) {
    $days[$c] = acct_day($c, $ts);
    foreach ($tot as $k => $v) {
        $tot[$k] += $days[$c][$k];
    }
    foreach ($days[$c]['rows'] as $b) {
        if (($tSel === 'vat' && !$b['vat']) || ($tSel === 'novat' && $b['vat'])) {
            continue;
        }
        $rows[] = $b;
    }
}
usort($rows, 'acct_row_cmp');
/* ---------- สรุปทั้งเดือน ---------- */
$month = array();
foreach ($codes as $c) {
    $month[$c] = acct_month($c, $ts);
}

$qs = function ($o) use ($ts, $bSel, $tSel) {
    $a = array_merge(array('d' => date('Y-m-d', $ts), 'b' => $bSel, 't' => $tSel), $o);
    return 'account.php?' . http_build_query($a);
};

$branch     = $bSel;
$PAGE_TITLE = 'บิลขายและเงินเข้า';
$PAGE_SUB   = ($bSel === 'ALL' ? 'ทุกสาขา' : branch_name($bSel)) . ' · ' . thai_date_full($ts);
$NAV_ACTIVE = 'account.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter">
  <form method="get" action="account.php" class="acct-row">
    <a class="btn btn-ghost btn-sm" href="<?php echo e($qs(array('d' => date('Y-m-d', strtotime('-1 day', $ts))))) ?>" aria-label="วันก่อนหน้า">‹</a>
    <label class="sr-only" for="d">วันที่</label>
    <input class="input acct-date" type="date" id="d" name="d" value="<?php echo e(date('Y-m-d', $ts)) ?>"
           max="<?php echo e(date('Y-m-d')) ?>" min="<?php echo e(date('Y-m-d', strtotime('-60 day', $today))) ?>" onchange="this.form.submit()">
    <?php if ($ts < $today) { ?>
      <a class="btn btn-ghost btn-sm" href="<?php echo e($qs(array('d' => date('Y-m-d', strtotime('+1 day', $ts))))) ?>" aria-label="วันถัดไป">›</a>
      <a class="btn btn-ghost btn-sm" href="<?php echo e($qs(array('d' => date('Y-m-d')))) ?>">วันนี้</a>
    <?php } ?>
    <label class="sr-only" for="b">สาขา</label>
    <select class="input acct-branch" id="b" name="b" onchange="this.form.submit()">
      <option value="ALL">ทุกสาขา</option>
      <?php foreach ($br as $c => $x) { ?>
        <option value="<?php echo e($c) ?>" <?php echo $bSel === $c ? 'selected' : '' ?>><?php echo e($x['name']) ?><?php echo empty($x['active']) ? ' (ปิดใช้งาน)' : '' ?></option>
      <?php } ?>
    </select>
    <input type="hidden" name="t" value="<?php echo e($tSel) ?>">
  </form>
  <div class="acct-row">
    <div class="segs">
      <?php foreach (array('all' => 'ทุกบิล', 'vat' => 'บิล VAT', 'novat' => 'บิลไม่ VAT') as $k => $lb) { ?>
        <a class="seg<?php echo $tSel === $k ? ' on' : '' ?>" href="<?php echo e($qs(array('t' => $k))) ?>"><?php echo e($lb) ?></a>
      <?php } ?>
    </div>
    <a class="btn btn-ghost btn-sm acct-dl" href="<?php echo e($qs(array('export' => 1))) ?>">
      <svg class="ico"><use href="#i-in"/></svg> ดาวน์โหลดบิลทั้งเดือน <?php echo e(thai_month_short($ts)) ?> (CSV / Excel)
    </a>
  </div>
</section>

<!-- ==================== ตัวเลขของวัน ==================== -->
<section class="mini num adm-kpi acct-kpi" aria-label="สรุปของวัน">
  <div class="m"><div class="lb">บิลทั้งหมด</div><div class="nm"><?php echo number_format($tot['bills']) ?></div>
    <div class="sb">VAT <?php echo number_format($tot['v']) ?> · ไม่ VAT <?php echo number_format($tot['n']) ?><?php echo $tot['void'] ? ' · ยกเลิก ' . number_format($tot['void']) : '' ?></div></div>
  <div class="m"><div class="lb">ยอดขาย</div><div class="nm"><?php echo e(money2($tot['total'])) ?></div>
    <div class="sb">VAT ที่เก็บได้ <?php echo e(money2($tot['vat'])) ?> บาท</div></div>
  <div class="m"><div class="lb">เงินเข้าสุทธิ</div><div class="nm"><?php echo e(money2($tot['net'])) ?></div>
    <div class="sb">เงินสด <?php echo e(money2($tot['cash'])) ?> · โอน <?php echo e(money2($tot['transfer'])) ?><?php echo $tot['refund'] ? ' · คืน −' . e(money2($tot['refund'])) : '' ?></div></div>
</section>

<!-- ==================== แยกสาขา ==================== -->
<section class="card">
  <div class="card-head"><div><h3>แยกตามสาขา · <?php echo e(thai_date_full($ts)) ?></h3><p>เงินสดเทียบกับเงินที่สาขานำส่ง · เงินโอนเทียบกับ statement ธนาคาร</p></div></div>
  <div class="card-body card-body--flush">
    <div class="tbl-wrap">
      <table class="tbl num">
        <thead><tr><th>สาขา</th><th class="r">บิล VAT</th><th class="r">บิลไม่ VAT</th><th class="r">ยอดขาย</th><th class="r">VAT</th>
          <th class="r">เงินสด</th><th class="r">โอน / พร้อมเพย์</th><th class="r">คืนเงิน</th><th class="r">เงินเข้าสุทธิ</th></tr></thead>
        <tbody>
          <?php foreach ($codes as $c) { $s = $days[$c]; ?>
            <tr>
              <td data-label="สาขา"><b><?php echo e(branch_name($c)) ?></b><small><?php echo e(acct_setting($c, 'prefix_vat')) ?> / <?php echo e(acct_setting($c, 'prefix_novat')) ?></small></td>
              <td class="r" data-label="บิล VAT"><?php echo number_format($s['v']) ?></td>
              <td class="r" data-label="บิลไม่ VAT"><?php echo number_format($s['n']) ?><?php if ($s['void']) { ?><small>ยกเลิก <?php echo number_format($s['void']) ?></small><?php } ?></td>
              <td class="r" data-label="ยอดขาย"><?php echo e(money2($s['total'])) ?></td>
              <td class="r" data-label="VAT"><?php echo e(money2($s['vat'])) ?></td>
              <td class="r" data-label="เงินสด"><?php echo e(money2($s['cash'])) ?></td>
              <td class="r" data-label="โอน / พร้อมเพย์"><?php echo e(money2($s['transfer'])) ?></td>
              <td class="r" data-label="คืนเงิน"><?php echo $s['refund'] ? '−' . e(money2($s['refund'])) : '—' ?></td>
              <td class="r" data-label="เงินเข้าสุทธิ"><b><?php echo e(money2($s['net'])) ?></b></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ==================== รายการบิล ==================== -->
<section class="card">
  <div class="card-head"><div><h3>รายการบิล <?php echo $tSel === 'all' ? '' : '(' . ($tSel === 'vat' ? 'VAT' : 'ไม่ VAT') . ')' ?></h3>
    <p><?php echo count($rows) ?> ใบ · เรียงตามสาขาและเลขที่ · ยอดขายเป็นราคารวม VAT แล้ว · กด “ดู / พิมพ์” เพื่อเปิดบิลและสั่งพิมพ์ (บิล VAT พิมพ์เป็นใบกำกับภาษีอย่างย่อ)</p></div></div>
  <?php if (!$rows) { ?>
    <p class="empty"><svg class="ico"><use href="#i-receipt"/></svg>ไม่มีบิลในวันนี้</p>
  <?php } else { ?>
    <div class="card-body card-body--flush">
      <div class="tbl-wrap">
        <table class="tbl num">
          <thead><tr><th>เลขที่บิล</th><th>ประเภท</th><th>ชำระ</th><th class="r">ก่อน VAT</th><th class="r">VAT</th><th class="r">รวม</th><th class="r"><span class="sr-only">บิล</span></th></tr></thead>
          <tbody>
            <?php foreach ($rows as $b) { $void = !empty($b['void']); ?>
              <tr class="<?php echo $void ? 'off' : '' ?>">
                <td class="doc" data-label="เลขที่บิล"><?php echo e($b['no']) ?><small><?php echo e($b['time']) ?> น. · <?php echo e(branch_name($b['branch'])) ?> · <?php echo e($b['by']) ?></small></td>
                <td data-label="ประเภท"><span class="bdg <?php echo $b['vat'] ? 'bdg-move' : 'bdg-adj' ?>"><?php echo e(bill_type_label($b['vat'])) ?></span>
                  <?php if ($void) { ?><span class="bdg bdg-out">ยกเลิก</span><?php } ?></td>
                <td data-label="ชำระ"><?php echo $b['method'] === 'cash' ? 'เงินสด' : 'โอน / พร้อมเพย์' ?></td>
                <td class="r" data-label="ก่อน VAT"><?php echo $b['vat'] ? e(money2($b['base'])) : '—' ?></td>
                <td class="r" data-label="VAT"><?php echo $b['vat'] ? e(money2($b['vatamt'])) : '—' ?></td>
                <td class="r" data-label="รวม"><?php echo $void ? '<s>' . e(money2($b['total'])) . '</s>' : e(money2($b['total'])) ?>
                  <?php if (!empty($b['discount'])) { ?><small>ส่วนลด <?php echo e(money2($b['discount'])) ?></small><?php } ?></td>
                <td class="r" data-label="">
                  <button type="button" class="btn btn-ghost btn-sm bill-pr" data-bill-print="<?php echo e(bill_print_url($b['branch'], $ts, $b['no'])) ?>"
                          data-bill-no="<?php echo e($b['no']) ?>" title="เปิดดูและพิมพ์บิล"><svg class="ico"><use href="#i-print"/></svg> ดู / พิมพ์</button>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php } ?>
</section>

<!-- ==================== สรุปทั้งเดือน ==================== -->
<section class="card">
  <div class="card-head"><div><h3>สรุปเดือน <?php echo e(thai_month_short($ts)) ?> <?php echo date('Y', $ts) + 543 ?></h3>
    <p>ตั้งแต่วันที่ 1 ถึง<?php echo date('Ym', $ts) === date('Ym') ? 'วันนี้' : 'สิ้นเดือน' ?> · ใช้ประกอบรายงานภาษีขาย (ภ.พ.30 ยื่นภายในวันที่ 15 ของเดือนถัดไป)</p></div></div>
  <div class="card-body card-body--flush">
    <div class="tbl-wrap">
      <table class="tbl num">
        <thead><tr><th>สาขา</th><th class="r">บิล VAT</th><th class="r">บิลไม่ VAT</th><th class="r">ก่อน VAT (บิล VAT)</th><th class="r">VAT</th><th class="r">ยอดขายรวม</th></tr></thead>
        <tbody>
          <?php foreach ($codes as $c) { $m = $month[$c]; ?>
            <tr>
              <td data-label="สาขา"><b><?php echo e(branch_name($c)) ?></b><small>เลขผู้เสียภาษี <?php echo e(acct_setting($c, 'tax_id')) ?> · สาขาที่ <?php echo e(acct_setting($c, 'tax_branch')) ?></small></td>
              <td class="r" data-label="บิล VAT"><?php echo number_format($m['v']) ?></td>
              <td class="r" data-label="บิลไม่ VAT"><?php echo number_format($m['n']) ?></td>
              <td class="r" data-label="มูลค่าก่อน VAT"><?php echo e(money2($m['base'])) ?></td>
              <td class="r" data-label="VAT"><?php echo e(money2($m['vat'])) ?></td>
              <td class="r" data-label="ยอดขายรวม"><?php echo e(money2($m['total'])) ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
