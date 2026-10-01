<?php
/* ==========================================================
   AOSTOCK DEMO — บิลขายและเงินเข้า (ฝ่ายบัญชี / ผู้ดูแล)
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
$br    = demo_branches_all();             // รวมสาขาที่ปิดใช้งานแล้ว — บิลเก่ายังต้องตรวจได้

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
    for ($d = strtotime(date('Y-m-01', $ts)); $d <= $end; $d = strtotime('+1 day', $d)) {
        foreach ($codes as $c) {
            foreach (acct_bills($c, $d) as $b) {
                $b = acct_bill_row($b, $c);
                if (($tSel === 'vat' && !$b['vat']) || ($tSel === 'novat' && $b['vat'])) {
                    continue;
                }
                $void = !empty($b['void']);
                fputcsv($out, array(date('Y-m-d', $d), $b['time'], branch_name($c), acct_setting($c, 'tax_id'),
                    acct_setting($c, 'tax_branch'), $b['no'], bill_type_label($b['vat']),
                    $b['method'] === 'cash' ? 'เงินสด' : 'โอน/พร้อมเพย์',
                    $void ? '0.00' : number_format(isset($b['subtotal']) ? $b['subtotal'] : $b['total'], 2, '.', ''),
                    $void ? '0.00' : number_format(isset($b['discount']) ? $b['discount'] : 0, 2, '.', ''),
                    $void ? '0.00' : number_format($b['base'], 2, '.', ''),
                    $void ? '0.00' : number_format($b['vatamt'], 2, '.', ''),
                    $void ? '0.00' : number_format($b['total'], 2, '.', ''),
                    $void ? 'ยกเลิก' : 'ปกติ', $b['by']));
            }
        }
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

<div class="demo-bar">
  <svg class="ico"><use href="#i-info"/></svg>
  <span>ข้อมูลตัวอย่าง · บิลของวันก่อนเป็นข้อมูลสมมติ ส่วนวันนี้มาจากรายการที่ทดลองขายในเดโม</span>
</div>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter">
  <form method="get" action="account.php" class="acct-row">
    <a class="btn btn-ghost btn-sm" href="<?= e($qs(array('d' => date('Y-m-d', strtotime('-1 day', $ts))))) ?>" aria-label="วันก่อนหน้า">‹</a>
    <label class="sr-only" for="d">วันที่</label>
    <input class="input acct-date" type="date" id="d" name="d" value="<?= e(date('Y-m-d', $ts)) ?>"
           max="<?= e(date('Y-m-d')) ?>" min="<?= e(date('Y-m-d', strtotime('-60 day', $today))) ?>" onchange="this.form.submit()">
    <?php if ($ts < $today): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($qs(array('d' => date('Y-m-d', strtotime('+1 day', $ts))))) ?>" aria-label="วันถัดไป">›</a>
      <a class="btn btn-ghost btn-sm" href="<?= e($qs(array('d' => date('Y-m-d')))) ?>">วันนี้</a>
    <?php endif; ?>
    <label class="sr-only" for="b">สาขา</label>
    <select class="input acct-branch" id="b" name="b" onchange="this.form.submit()">
      <option value="ALL">ทุกสาขา</option>
      <?php foreach ($br as $c => $x): ?>
        <option value="<?= e($c) ?>" <?= $bSel === $c ? 'selected' : '' ?>><?= e($x['name']) ?><?= empty($x['active']) ? ' (ปิดใช้งาน)' : '' ?></option>
      <?php endforeach; ?>
    </select>
    <input type="hidden" name="t" value="<?= e($tSel) ?>">
  </form>
  <div class="acct-row">
    <div class="segs">
      <?php foreach (array('all' => 'ทุกบิล', 'vat' => 'บิล VAT', 'novat' => 'บิลไม่ VAT') as $k => $lb): ?>
        <a class="seg<?= $tSel === $k ? ' on' : '' ?>" href="<?= e($qs(array('t' => $k))) ?>"><?= e($lb) ?></a>
      <?php endforeach; ?>
    </div>
    <a class="btn btn-ghost btn-sm acct-dl" href="<?= e($qs(array('export' => 1))) ?>">
      <svg class="ico"><use href="#i-in"/></svg> ดาวน์โหลดบิลทั้งเดือน <?= e(thai_month_short($ts)) ?> (CSV / Excel)
    </a>
  </div>
</section>

<!-- ==================== ตัวเลขของวัน ==================== -->
<section class="mini num adm-kpi acct-kpi" aria-label="สรุปของวัน">
  <div class="m"><div class="lb">บิลทั้งหมด</div><div class="nm"><?= number_format($tot['bills']) ?></div>
    <div class="sb">VAT <?= number_format($tot['v']) ?> · ไม่ VAT <?= number_format($tot['n']) ?><?= $tot['void'] ? ' · ยกเลิก ' . number_format($tot['void']) : '' ?></div></div>
  <div class="m"><div class="lb">ยอดขาย</div><div class="nm"><?= e(money2($tot['total'])) ?></div>
    <div class="sb">VAT ที่เก็บได้ <?= e(money2($tot['vat'])) ?> บาท</div></div>
  <div class="m"><div class="lb">เงินเข้าสุทธิ</div><div class="nm"><?= e(money2($tot['net'])) ?></div>
    <div class="sb">เงินสด <?= e(money2($tot['cash'])) ?> · โอน <?= e(money2($tot['transfer'])) ?><?= $tot['refund'] ? ' · คืน −' . e(money2($tot['refund'])) : '' ?></div></div>
</section>

<!-- ==================== แยกสาขา ==================== -->
<section class="card">
  <div class="card-head"><div><h3>แยกตามสาขา · <?= e(thai_date_full($ts)) ?></h3><p>เงินสดเทียบกับเงินที่สาขานำส่ง · เงินโอนเทียบกับ statement ธนาคาร</p></div></div>
  <div class="card-body card-body--flush">
    <div class="tbl-wrap">
      <table class="tbl num">
        <thead><tr><th>สาขา</th><th class="r">บิล VAT</th><th class="r">บิลไม่ VAT</th><th class="r">ยอดขาย</th><th class="r">VAT</th>
          <th class="r">เงินสด</th><th class="r">โอน / พร้อมเพย์</th><th class="r">คืนเงิน</th><th class="r">เงินเข้าสุทธิ</th></tr></thead>
        <tbody>
          <?php foreach ($codes as $c): $s = $days[$c]; ?>
            <tr>
              <td data-label="สาขา"><b><?= e(branch_name($c)) ?></b><small><?= e(acct_setting($c, 'prefix_vat')) ?> / <?= e(acct_setting($c, 'prefix_novat')) ?></small></td>
              <td class="r" data-label="บิล VAT"><?= number_format($s['v']) ?></td>
              <td class="r" data-label="บิลไม่ VAT"><?= number_format($s['n']) ?><?php if ($s['void']): ?><small>ยกเลิก <?= number_format($s['void']) ?></small><?php endif; ?></td>
              <td class="r" data-label="ยอดขาย"><?= e(money2($s['total'])) ?></td>
              <td class="r" data-label="VAT"><?= e(money2($s['vat'])) ?></td>
              <td class="r" data-label="เงินสด"><?= e(money2($s['cash'])) ?></td>
              <td class="r" data-label="โอน / พร้อมเพย์"><?= e(money2($s['transfer'])) ?></td>
              <td class="r" data-label="คืนเงิน"><?= $s['refund'] ? '−' . e(money2($s['refund'])) : '—' ?></td>
              <td class="r" data-label="เงินเข้าสุทธิ"><b><?= e(money2($s['net'])) ?></b></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ==================== รายการบิล ==================== -->
<section class="card">
  <div class="card-head"><div><h3>รายการบิล <?= $tSel === 'all' ? '' : '(' . ($tSel === 'vat' ? 'VAT' : 'ไม่ VAT') . ')' ?></h3>
    <p><?= count($rows) ?> ใบ · เรียงตามสาขาและเลขที่ · ยอดขายเป็นราคารวม VAT แล้ว · กด “ดู / พิมพ์” เพื่อเปิดบิลและสั่งพิมพ์ (บิล VAT พิมพ์เป็นใบกำกับภาษีอย่างย่อ)</p></div></div>
  <?php if (!$rows): ?>
    <p class="empty"><svg class="ico"><use href="#i-receipt"/></svg>ไม่มีบิลในวันนี้</p>
  <?php else: ?>
    <div class="card-body card-body--flush">
      <div class="tbl-wrap">
        <table class="tbl num">
          <thead><tr><th>เลขที่บิล</th><th>ประเภท</th><th>ชำระ</th><th class="r">ก่อน VAT</th><th class="r">VAT</th><th class="r">รวม</th><th class="r"><span class="sr-only">บิล</span></th></tr></thead>
          <tbody>
            <?php foreach ($rows as $b): $void = !empty($b['void']); ?>
              <tr class="<?= $void ? 'off' : '' ?>">
                <td class="doc" data-label="เลขที่บิล"><?= e($b['no']) ?><small><?= e($b['time']) ?> น. · <?= e(branch_name($b['branch'])) ?> · <?= e($b['by']) ?></small></td>
                <td data-label="ประเภท"><span class="bdg <?= $b['vat'] ? 'bdg-move' : 'bdg-adj' ?>"><?= e(bill_type_label($b['vat'])) ?></span>
                  <?php if ($void): ?><span class="bdg bdg-out">ยกเลิก</span><?php endif; ?></td>
                <td data-label="ชำระ"><?= $b['method'] === 'cash' ? 'เงินสด' : 'โอน / พร้อมเพย์' ?></td>
                <td class="r" data-label="ก่อน VAT"><?= $b['vat'] ? e(money2($b['base'])) : '—' ?></td>
                <td class="r" data-label="VAT"><?= $b['vat'] ? e(money2($b['vatamt'])) : '—' ?></td>
                <td class="r" data-label="รวม"><?= $void ? '<s>' . e(money2($b['total'])) . '</s>' : e(money2($b['total'])) ?>
                  <?php if (!empty($b['discount'])): ?><small>ส่วนลด <?= e(money2($b['discount'])) ?></small><?php endif; ?></td>
                <td class="r" data-label="">
                  <button type="button" class="btn btn-ghost btn-sm bill-pr" data-bill-print="<?= e(bill_print_url($b['branch'], $ts, $b['no'])) ?>"
                          data-bill-no="<?= e($b['no']) ?>" title="เปิดดูและพิมพ์บิล"><svg class="ico"><use href="#i-print"/></svg> ดู / พิมพ์</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</section>

<!-- ==================== สรุปทั้งเดือน ==================== -->
<section class="card">
  <div class="card-head"><div><h3>สรุปเดือน <?= e(thai_month_short($ts)) ?> <?= date('Y', $ts) + 543 ?></h3>
    <p>ตั้งแต่วันที่ 1 ถึง<?= date('Ym', $ts) === date('Ym') ? 'วันนี้' : 'สิ้นเดือน' ?> · ใช้ประกอบรายงานภาษีขาย (ภ.พ.30 ยื่นภายในวันที่ 15 ของเดือนถัดไป)</p></div></div>
  <div class="card-body card-body--flush">
    <div class="tbl-wrap">
      <table class="tbl num">
        <thead><tr><th>สาขา</th><th class="r">บิล VAT</th><th class="r">บิลไม่ VAT</th><th class="r">ก่อน VAT (บิล VAT)</th><th class="r">VAT</th><th class="r">ยอดขายรวม</th></tr></thead>
        <tbody>
          <?php foreach ($codes as $c): $m = $month[$c]; ?>
            <tr>
              <td data-label="สาขา"><b><?= e(branch_name($c)) ?></b><small>เลขผู้เสียภาษี <?= e(acct_setting($c, 'tax_id')) ?> · สาขาที่ <?= e(acct_setting($c, 'tax_branch')) ?></small></td>
              <td class="r" data-label="บิล VAT"><?= number_format($m['v']) ?></td>
              <td class="r" data-label="บิลไม่ VAT"><?= number_format($m['n']) ?></td>
              <td class="r" data-label="มูลค่าก่อน VAT"><?= e(money2($m['base'])) ?></td>
              <td class="r" data-label="VAT"><?= e(money2($m['vat'])) ?></td>
              <td class="r" data-label="ยอดขายรวม"><?= e(money2($m['total'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
