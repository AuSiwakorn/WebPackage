<?php
/**
 * FILE: themes/aostock/adm-dashboard.php
 * ROLE: [ผู้ดูแล] ภาพรวม (หน้าแรกของผู้ดูแลหลังเข้าระบบ)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_store_day, ao_stock_sale, ao_stock_return, ao_stock_log, ao_stock_issue (ผ่าน api.php)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 7: สถานะร้าน / ยอดขาย / เปิดใหม่หลังปิด / วันที่ลืมปิดร้าน อ่านจากตาราง · เอาแถบ "ข้อมูลสมมติ" ออก
 *   - [x] ช่วงที่ 8: แก้ "ตัดออก — สูญหาย" ในรายการที่ต้องตรวจ ให้อ่านใบตัดออกของวันนี้จากตาราง (เดิมอ่าน $_SESSION['issue'] ที่ไม่มีแล้ว)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   [ผู้ดูแล] ภาพรวม (หน้าแรกของผู้ดูแลหลังเข้าระบบ)
   ----------------------------------------------------------
   สิ่งที่เจ้าของร้านต้องเห็นทุกวัน
     1. สถานะร้านวันนี้ทุกสาขา — เปิด/ปิดกี่โมง ยอดขาย เงินขาด/เกินตอนปิดร้าน
     2. รายการที่ต้องตรวจ — ยกเลิก/แก้เอกสาร รับคืน+เงินคืน เปิดร้านใหม่หลังปิด
        ตัดออกเหตุผลสูญหาย เงินไม่ตรงตอนปิดร้าน · บอกว่าใครทำ
     3. สต๊อกแยกตามสาขา — มูลค่า ใกล้หมด หมด
   เลือกสาขาจากแถบบน (?branch=ALL | รหัสสาขา) · เปิดร้านใหม่หลังปิดทำได้จากหน้านี้ (POST act=reopen)
   ระบบจริง: SELECT จาก store_day, sale, stock_log, return ของวันนี้
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

/* ผู้ดูแล: เปิดร้านใหม่หลังปิด (ทำจากภาพรวม — ผู้ดูแลไม่เข้าหน้าเปิด/ปิดร้านแล้ว) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'reopen') {
    $rb = isset($_POST['b']) ? $_POST['b'] : '';
    $rr = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $bb = branches_active();
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null) || !isset($bb[$rb])) {
        $_SESSION['flash'] = 'เซสชันหมดอายุ หรือไม่พบสาขา';
    } elseif ($rr === '') {
        $_SESSION['flash'] = 'การเปิดร้านใหม่หลังปิดต้องระบุเหตุผล';
    } elseif (store_reopen($rb, $user, $rr) === null) {
        $_SESSION['flash'] = branch_name($rb) . ' ยังไม่ได้ปิดร้าน';
    } else {
        $_SESSION['flash'] = 'เปิดร้าน' . branch_name($rb) . 'ใหม่แล้ว — พนักงานขายต่อได้ และต้องปิดร้านอีกครั้ง';
    }
    header('Location: ' . url('adm-dashboard.php?branch=ALL'));
    exit;
}

$branch = resolve_branch($user, isset($_GET['branch']) ? $_GET['branch'] : null);

$PAGE_TITLE = 'ภาพรวมผู้ดูแล';
$PAGE_SUB   = branch_label($branch) . ' · ' . thai_date_full(time()) . ' ' . date('H:i') . ' น.';
$NAV_ACTIVE = 'adm-dashboard.php';
require dirname(__FILE__) . '/inc/header.php';

$__codes = ($branch === 'ALL') ? array_keys(branches_active()) : array($branch);

/* ---------- 1. สถานะร้าน + ยอดขาย ---------- */
$__rows = array();
$__tot  = array('total' => 0, 'bills' => 0, 'void' => 0, 'diff' => 0, 'refund' => 0);
foreach ($__codes as $c) {
    $st  = store_state($c);
    $sum = sale_summary($c);
    $row = array('code' => $c, 'st' => $st, 'sum' => $sum, 'diff' => null, 'refund' => store_refunds($c));
    $row['prev'] = false;
    if ($st !== null && $st['closed_at'] !== '') {
        $row['diff'] = (float) $st['cash_counted'] - (float) store_expected_cash($c);
    } elseif ($st !== null && $st['last_diff'] !== null) {
        /* เปิดร้านใหม่หลังปิด — แสดงผลของรอบที่ปิดไปก่อนหน้า จนกว่าจะปิดใหม่ */
        $row['diff'] = $st['last_diff'];
        $row['prev'] = true;
    }
    $row['unclosed'] = store_unclosed_days($c);       // วันก่อนที่ลืมปิดร้าน
    if ($row['diff'] !== null) {
        $__tot['diff'] += $row['diff'];           // นับเฉพาะผลล่าสุดของแต่ละสาขา ไม่นับซ้ำ
    }
    $__tot['total']  += $sum['total'];
    $__tot['bills']  += $sum['bills'];
    $__tot['void']   += $sum['void'];
    $__tot['refund'] += $row['refund'];
    $__rows[] = $row;
}

/* ---------- 2. รายการที่ต้องตรวจ ---------- */
$__review = array();
foreach ($__codes as $c) {
    foreach (log_today($c) as $r) {
        $why  = (isset($r['detail']['เหตุผล']) && $r['detail']['เหตุผล'] !== '') ? $r['detail']['เหตุผล'] : '';
        $kind = '';
        if (in_array($r['type'], array('void', 'rvoid', 'ivoid', 'avoid'), true)) {
            $kind = (strpos($r['title'], 'เพื่อแก้ไข') !== false) ? 'แก้ไขเอกสาร' : 'ยกเลิกเอกสาร';
        } elseif ($r['type'] === 'return') {
            $kind = 'รับคืน / คืนเงิน';
        } elseif ($r['type'] === 'open' && strpos($r['title'], 'เปิดร้านใหม่') === 0) {
            $kind = 'เปิดร้านใหม่หลังปิด';
        } elseif ($r['type'] === 'close' && isset($r['detail']['ผลต่าง']) && strpos($r['detail']['ผลต่าง'], 'ตรงพอดี') === false) {
            $kind = 'เงินไม่ตรงตอนปิดร้าน';
            $why  = $r['detail']['ผลต่าง'] . (isset($r['detail']['หมายเหตุ']) ? ' — ' . $r['detail']['หมายเหตุ'] : '');
        }
        if ($kind === '') {
            continue;
        }
        $__review[] = array('ts' => $r['ts'], 'time' => $r['time'], 'code' => $c, 'kind' => $kind,
                            'title' => $r['title'], 'by' => $r['by'], 'why' => $why,
                            'amount' => ($r['type'] === 'return') ? $r['amount'] : null);
    }
    /* ตัดออกเหตุผล "สูญหาย" ของวันนี้ที่ยังไม่ถูกยกเลิก (ao_stock_issue) */
    foreach (issues_today($c) as $d) {
        if ($d['reason'] !== 'lost' || !empty($d['void'])) {
            continue;
        }
        $__review[] = array('ts' => $d['ts'], 'time' => $d['time'], 'code' => $c,
                            'kind' => 'ตัดออก — สูญหาย', 'title' => 'ใบตัดออก ' . $d['no'] . ' · ' . number_format($d['qty']) . ' ชิ้น',
                            'by' => $d['by'], 'why' => $d['note'], 'amount' => null);
    }
}
usort($__review, 'dash_review_cmp');
$__tones = array('ยกเลิกเอกสาร' => 'bdg-out', 'แก้ไขเอกสาร' => 'bdg-warn', 'รับคืน / คืนเงิน' => 'bdg-out',
                 'เปิดร้านใหม่หลังปิด' => 'bdg-warn', 'เงินไม่ตรงตอนปิดร้าน' => 'bdg-out', 'ตัดออก — สูญหาย' => 'bdg-out');
?>

<section class="mini num adm-kpi" aria-label="สรุปวันนี้">
  <div class="m"><div class="lb">ยอดขายวันนี้</div><div class="nm"><?= e(money2($__tot['total'])) ?></div><div class="sb"><?= number_format($__tot['bills']) ?> บิล · <?= e(branch_label($branch)) ?></div></div>
  <div class="m"><div class="lb">เงินขาด / เกินตอนปิดร้าน</div>
    <div class="nm <?= $__tot['diff'] < 0 ? 'qty-out' : ($__tot['diff'] > 0 ? 'qty-in' : '') ?>"><?= $__tot['diff'] == 0 ? '0.00' : ($__tot['diff'] > 0 ? '+' : '−') . e(money2(abs($__tot['diff']))) ?></div>
    <div class="sb">ผลการปิดร้านล่าสุดของแต่ละสาขา</div></div>
  <div class="m"><div class="lb">รายการที่ต้องตรวจ</div><div class="nm <?= $__review ? 'qty-out' : '' ?>"><?= count($__review) ?></div><div class="sb">คืนเงินสดรวม <?= e(money2($__tot['refund'])) ?> บาท</div></div>
</section>

<!-- ==================== สถานะร้านวันนี้ ==================== -->
<section class="card">
  <div class="card-head"><div><h3>สถานะร้านวันนี้</h3><p><?= e(thai_date_full(time())) ?> · ยอดขายไม่นับบิลที่ยกเลิก</p></div></div>
  <div class="card-body card-body--flush">
    <div class="tbl-wrap">
      <table class="tbl num">
        <thead><tr><th>สาขา</th><th>ร้าน</th><th class="r">ยอดขาย</th><th class="r">เงินสด / โอน</th><th class="r">บิลยกเลิก</th><th class="r">เงินตอนปิดร้าน</th></tr></thead>
        <tbody>
          <?php foreach ($__rows as $r): $st = $r['st']; $s = $r['sum']; ?>
            <tr>
              <td data-label="สาขา"><b><?= e(branch_name($r['code'])) ?></b>
                <?php if ($r['unclosed']): ?>
                  <small class="warn-txt">ยังไม่ได้ปิดร้าน: <?= e(implode(', ', array_map(function ($d) { return thai_day_month(strtotime($d)); }, array_slice($r['unclosed'], 0, 5)))) ?><?= count($r['unclosed']) > 5 ? ' …' : '' ?></small>
                <?php endif; ?>
              </td>
              <td data-label="ร้าน">
                <?php if ($st === null): ?>
                  <span class="bdg bdg-adj">ยังไม่เปิด</span>
                <?php elseif (empty($st['closed_at'])): ?>
                  <span class="bdg bdg-ok">เปิดอยู่</span><small>เปิด <?= e($st['opened_at']) ?> น. · <?= e($st['opened_by']) ?><?= $st['reopen_count'] > 0 ? ' · เปิดใหม่ ' . (int) $st['reopen_count'] . ' ครั้ง' : '' ?></small>
                <?php else: ?>
                  <span class="bdg bdg-adj">ปิดแล้ว</span><small><?= e($st['opened_at']) ?>–<?= e($st['closed_at']) ?> น. · ปิดโดย <?= e($st['closed_by']) ?></small>
                  <form class="dash-reopen" method="post" action="adm-dashboard.php">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="act" value="reopen">
                    <input type="hidden" name="b" value="<?= e($r['code']) ?>">
                    <input class="input" type="text" name="reason" required placeholder="เหตุผลเปิดใหม่" aria-label="เหตุผลเปิดร้านใหม่">
                    <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-store"/></svg> เปิดใหม่</button>
                  </form>
                <?php endif; ?>
              </td>
              <td class="r" data-label="ยอดขาย"><?= e(money2($s['total'])) ?><small><?= number_format($s['bills']) ?> บิล</small></td>
              <td class="r" data-label="เงินสด / โอน"><?= e(money2($s['cash'])) ?><small>โอน <?= e(money2($s['transfer'])) ?></small></td>
              <td class="r" data-label="บิลยกเลิก"><?= $s['void'] ? '<span class="bdg bdg-warn">' . number_format($s['void']) . '</span>' : '—' ?></td>
              <td class="r" data-label="เงินตอนปิดร้าน">
                <?php if ($r['diff'] === null): ?>
                  <small><?= $st === null ? '—' : 'ยังไม่ปิดร้าน' ?></small>
                <?php elseif (abs($r['diff']) < 0.01): ?>
                  <span class="bdg bdg-ok">ตรงพอดี</span>
                <?php else: ?>
                  <span class="bdg bdg-out"><?= $r['diff'] > 0 ? 'เกิน +' : 'ขาด −' ?><?= e(money2(abs($r['diff']))) ?></span>
                <?php endif; ?>
                <?php if ($r['prev']): ?><small>ผลของรอบที่ปิดไปก่อนเปิดใหม่</small><?php endif; ?>
                <?php if ($r['refund'] > 0): ?><small>คืนเงินลูกค้า <?= e(money2($r['refund'])) ?></small><?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ==================== รายการที่ต้องตรวจ ==================== -->
<section class="card">
  <div class="card-head"><div><h3>รายการที่ต้องตรวจ</h3><p>ยกเลิก / แก้เอกสาร · รับคืนและเงินคืน · เปิดร้านใหม่หลังปิด · ตัดออกเพราะสูญหาย · เงินไม่ตรงตอนปิดร้าน</p></div></div>
  <?php if (!$__review): ?>
    <p class="empty"><svg class="ico"><use href="#i-check"/></svg>วันนี้ยังไม่มีรายการที่ต้องตรวจ</p>
  <?php else: ?>
    <div class="card-body card-body--flush">
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr><th>เวลา</th><th>เรื่อง</th><th>รายละเอียด</th><th>ผู้ทำ</th><th class="r"></th></tr></thead>
          <tbody>
            <?php foreach ($__review as $r): ?>
              <tr>
                <td class="num" data-label="เวลา"><?= e($r['time']) ?> น.<small><?= e(branch_name($r['code'])) ?></small></td>
                <td data-label="เรื่อง"><span class="bdg <?= e($__tones[$r['kind']]) ?>"><?= e($r['kind']) ?></span></td>
                <td data-label="รายละเอียด"><?= e($r['title']) ?>
                  <?php if ($r['why'] !== ''): ?><small>เหตุผล: <?= e($r['why']) ?></small><?php endif; ?>
                  <?php if ($r['amount'] !== null): ?><small>คืนเงินสด <?= e(money2($r['amount'])) ?> บาท</small><?php endif; ?>
                </td>
                <td data-label="ผู้ทำ"><?= e($r['by']) ?></td>
                <td class="r"><a class="btn btn-ghost btn-sm" href="adm-history.php?b=<?= e(rawurlencode($r['code'])) ?>">ดูประวัติ</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</section>

<!-- ==================== สต๊อกแยกตามสาขา ==================== -->
<section class="card">
  <div class="card-head"><div><h3>สต๊อกแยกตามสาขา</h3><p>มูลค่าตามทุน · สินค้าที่ถึงจุดสั่งซื้อ</p></div></div>
  <div class="card-body card-body--flush">
    <div class="tbl-wrap">
      <table class="tbl tbl--compact num">
        <thead><tr><th>สาขา</th><th class="r">มูลค่าสต๊อก</th><th class="r">ใกล้หมด</th><th class="r">หมด</th><th>ต้องสั่งก่อน</th></tr></thead>
        <tbody>
          <?php foreach ($__codes as $c): $s = stock_summary($c); $lw = low_stock_products($c, 2); ?>
            <tr>
              <td data-label="สาขา"><b><?= e(branch_name($c)) ?></b></td>
              <td class="r" data-label="มูลค่าสต๊อก"><?= e(money($s['value'])) ?></td>
              <td class="r" data-label="ใกล้หมด"><?= $s['low'] ? '<span class="bdg bdg-warn">' . number_format($s['low']) . '</span>' : '—' ?></td>
              <td class="r" data-label="หมด"><?= $s['out'] ? '<span class="bdg bdg-out">' . number_format($s['out']) . '</span>' : '—' ?></td>
              <td data-label="ต้องสั่งก่อน">
                <?php if (!$lw): ?>—<?php endif; ?>
                <?php foreach ($lw as $l): ?><small><?= e($l['product']['name']) ?> · เหลือ <?= number_format($l['qty']) ?></small><?php endforeach; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
