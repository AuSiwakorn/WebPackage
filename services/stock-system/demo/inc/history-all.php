<?php
/* ==========================================================
   AOSTOCK DEMO — ประวัติการทำรายการของทุกสาขา (เฉพาะผู้ดูแล)
   ----------------------------------------------------------
   ส่วนหนึ่งของ history.php — เรียกเมื่อผู้ดูแลเปิดหน้าประวัติโดยไม่ได้เลือกดูสาขาเดียว
   ตัวกรอง (GET):
     b    = ALL หรือรหัสสาขา (รวมสาขาที่ปิดใช้งาน)
     mode = day | month
     d    = วันที่ (Y-m-d) สำหรับรายวัน · m = เดือน (Y-m) สำหรับรายเดือน
     t    = ชนิดรายการ (คีย์ของ log_types) · ว่าง = ทุกชนิด
     sort = desc (ใหม่ → เก่า) | asc (เก่า → ใหม่)
     p    = หน้า (ทีละ 100 รายการ)

   ที่มาของข้อมูลในเดโม
     วันนี้      ประวัติจริงใน session (log_today) — ครบทุกชนิด
     วันก่อน    บิลขาย (past_bills) + เอกสารคลัง (past_docs) จากข้อมูลสมมติที่คงที่
   ระบบจริง: SELECT จาก ao_stock_activity_log JOIN branch WHERE created_at BETWEEN … ORDER BY created_at
   ตัวแปรจาก history.php: $user
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$brAll = demo_branches_all();
$today = strtotime(date('Y-m-d'));

/* ---------- อ่านตัวกรอง ---------- */
$fB    = (isset($_GET['b']) && is_string($_GET['b']) && isset($brAll[$_GET['b']])) ? $_GET['b'] : 'ALL';
$fMode = (isset($_GET['mode']) && $_GET['mode'] === 'month') ? 'month' : 'day';
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

if ($fMode === 'day') {
    $from = $dayTs;
    $to   = $dayTs;
} else {
    $from = $monTs;
    $to   = min(strtotime(date('Y-m-t', $monTs)), $today);
}

/** ลิงก์ของหน้านี้ โดยเปลี่ยนค่าบางตัว */
$hq = function ($chg) use ($fB, $fMode, $fSort, $fT, $dayTs, $monTs) {
    $q = array('b' => $fB, 'mode' => $fMode, 'd' => date('Y-m-d', $dayTs), 'm' => date('Y-m', $monTs),
               't' => $fT, 'sort' => $fSort);
    $q = array_merge($q, $chg);
    if ($q['mode'] === 'day') {
        unset($q['m']);
    } else {
        unset($q['d']);
    }
    foreach (array('t' => '', 'sort' => 'desc', 'b' => 'ALL', 'p' => 1) as $k => $def) {
        if (isset($q[$k]) && (string) $q[$k] === (string) $def) {
            unset($q[$k]);
        }
    }
    return 'history.php?' . http_build_query($q);
};

/* ---------- รวบรวมรายการ ---------- */
$codes  = $fB === 'ALL' ? array_keys($brAll) : array($fB);
$pastK  = array('RC' => 'receive', 'IS' => 'issue', 'AD' => 'adjust');
$pastL  = past_types();
$adminL = return_lookback_days();                 // ผู้ดูแลเปิดหน้าสาขาย้อนได้กี่วัน
$rows   = array();
$seq    = 0;

for ($d = $from; $d <= $to; $d = strtotime('+1 day', $d)) {
    $ymd = date('Ymd', $d);
    $age = (int) round(($today - $d) / 86400);
    foreach ($codes as $c) {
        $open = !empty($brAll[$c]['active']);
        $link = ($open && $age <= $adminL)
              ? 'history.php?view=branch&branch=' . rawurlencode($c) . ($age > 0 ? '&d=' . $ymd : '')
              : '';

        if ($age === 0) {
            /* วันนี้ — ประวัติจริงทุกชนิด */
            foreach (array_reverse(log_today($c)) as $r) {
                $kind = in_array($r['type'], array('receive', 'rvoid'), true) ? 'qty' : 'money';
                $rows[] = array('ts' => $r['ts'], 'seq' => $seq++, 'date' => $ymd, 'time' => $r['time'],
                                'branch' => $c, 'type' => $r['type'], 'title' => $r['title'],
                                'amount' => $r['amount'], 'kind' => $kind, 'by' => $r['by'],
                                'void' => false, 'note' => '', 'link' => $link);
            }
            continue;
        }

        /* วันก่อน — บิลขาย */
        foreach (past_bills($c, $d) as $b) {
            $note = $b['vat'] ? 'บิล VAT' : 'ไม่ VAT';
            $note .= ' · ' . ($b['method'] === 'cash' ? 'เงินสด' : 'โอน');
            if (bill_has_returns($b['no'], $b)) {
                $note .= ' · มีรับคืน';
            }
            $rows[] = array('ts' => strtotime(date('Y-m-d', $d) . ' ' . $b['time']), 'seq' => $seq++,
                            'date' => $ymd, 'time' => $b['time'], 'branch' => $c, 'type' => 'sale',
                            'title' => 'บิลขาย ' . $b['no'] . ' · ' . (int) $b['items'] . ' รายการ',
                            'amount' => $b['total'], 'kind' => 'money', 'by' => $b['by'],
                            'void' => !empty($b['void']), 'note' => $note, 'link' => $link);
        }
        /* วันก่อน — ใบรับคืน */
        foreach (past_returns($c, $d) as $rt) {
            $rows[] = array('ts' => strtotime(date('Y-m-d', $d) . ' ' . $rt['time']), 'seq' => $seq++,
                            'date' => $ymd, 'time' => $rt['time'], 'branch' => $c, 'type' => 'return',
                            'title' => 'รับคืน ' . $rt['no'] . ' · บิล ' . $rt['bill_no'],
                            'amount' => $rt['refund'], 'kind' => 'money', 'by' => $rt['by'], 'void' => false,
                            'note' => return_reason_label($rt['reason']) . ' · ' . ($rt['restock'] ? 'กลับเข้าสต๊อก' : 'ไม่เข้าสต๊อก'),
                            'link' => 'return.php?no=' . rawurlencode($rt['no']) . '&b=' . rawurlencode($c));
        }
        /* วันก่อน — เอกสารคลัง */
        foreach (past_docs($c, $d) as $doc) {
            $note = '';
            if ($doc['kind'] === 'IS') {
                $note = issue_reason_label($doc['reason']);
            } elseif ($doc['kind'] === 'AD') {
                $note = adj_reason_label($doc['reason']);
            } elseif ($doc['ref'] !== '') {
                $note = 'อ้างอิง ' . $doc['ref'];
            }
            if (!empty($doc['void'])) {
                $note .= ($note !== '' ? ' · ' : '') . 'ยกเลิกย้อนหลังโดย ' . $doc['void_by'];
            }
            $rows[] = array('ts' => strtotime(date('Y-m-d', $d) . ' ' . $doc['time']), 'seq' => $seq++,
                            'date' => $ymd, 'time' => $doc['time'], 'branch' => $c, 'type' => $pastK[$doc['kind']],
                            'title' => $pastL[$doc['kind']]['label'] . ' ' . $doc['no'] . ' · ' . (int) $doc['items'] . ' รายการ',
                            'amount' => $doc['kind'] === 'AD' ? null : ($doc['kind'] === 'IS' ? -$doc['qty'] : $doc['qty']),
                            'kind' => 'qty', 'by' => $doc['by'],
                            'void' => !empty($doc['void']), 'note' => $note, 'link' => $link);
        }
    }
}

/* ---------- นับตามชนิด / สรุป (ก่อนกรองชนิด) ---------- */
$counts = log_counts($rows);
$sum    = array('all' => count($rows), 'bills' => 0, 'sales' => 0, 'docs' => 0, 'perB' => array());
foreach ($rows as $r) {
    if (!isset($sum['perB'][$r['branch']])) {
        $sum['perB'][$r['branch']] = 0;
    }
    $sum['perB'][$r['branch']]++;
    if ($r['type'] === 'sale') {
        $sum['bills']++;
        if (!$r['void']) {
            $sum['sales'] += $r['amount'];
        }
    } elseif (in_array($r['type'], array('receive', 'issue', 'adjust'), true)) {
        $sum['docs']++;
    }
}

/* ---------- กรองชนิด + เรียงตามเวลา ---------- */
if ($fT !== '') {
    $rows = array_values(array_filter($rows, function ($r) use ($fT) {
        return $r['type'] === $fT;
    }));
}
$dir = $fSort === 'asc' ? 1 : -1;
usort($rows, function ($a, $b) use ($dir) {
    if ($a['ts'] !== $b['ts']) {
        return ($a['ts'] < $b['ts'] ? -1 : 1) * $dir;
    }
    return ($a['seq'] < $b['seq'] ? -1 : 1) * $dir;
});

/* ---------- แบ่งหน้า ---------- */
$per   = 100;
$total = count($rows);
$pages = max(1, (int) ceil($total / $per));
$pg    = isset($_GET['p']) ? max(1, min($pages, (int) $_GET['p'])) : 1;
$show  = array_slice($rows, ($pg - 1) * $per, $per);

$rangeTxt = $fMode === 'day' ? thai_date_full($dayTs) : thai_month_full($monTs);

$branch         = 'ALL';
$NO_BRANCH_PICK = true;                  // มีตัวกรองสาขาของหน้านี้เองแล้ว
$PAGE_TITLE     = 'ประวัติการทำรายการ';
$PAGE_SUB       = 'ทุกสาขา · ' . $rangeTxt;
$NAV_ACTIVE     = 'history.php';
require dirname(__FILE__) . '/header.php';
?>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter hist-filter">
  <form method="get" action="history.php" class="acct-row">
    <div class="segs">
      <a class="seg<?= $fMode === 'day' ? ' on' : '' ?>" href="<?= e($hq(array('mode' => 'day'))) ?>">รายวัน</a>
      <a class="seg<?= $fMode === 'month' ? ' on' : '' ?>" href="<?= e($hq(array('mode' => 'month', 'm' => date('Y-m', $dayTs)))) ?>">รายเดือน</a>
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
    <?php else: ?>
      <a class="btn btn-ghost btn-sm" href="<?= e($hq(array('m' => date('Y-m', strtotime('-1 month', $monTs))))) ?>" aria-label="เดือนก่อนหน้า">‹</a>
      <label class="sr-only" for="hm">เดือน</label>
      <input class="input acct-date" type="month" id="hm" name="m" value="<?= e(date('Y-m', $monTs)) ?>"
             max="<?= e(date('Y-m')) ?>" onchange="this.form.submit()">
      <?php if ($monTs < strtotime(date('Y-m-01'))): ?>
        <a class="btn btn-ghost btn-sm" href="<?= e($hq(array('m' => date('Y-m', strtotime('+1 month', $monTs))))) ?>" aria-label="เดือนถัดไป">›</a>
        <a class="btn btn-ghost btn-sm" href="<?= e($hq(array('m' => date('Y-m')))) ?>">เดือนนี้</a>
      <?php endif; ?>
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
        · วันก่อนแสดงบิลขาย รับคืน และเอกสารคลัง ส่วนวันนี้แสดงครบทุกชนิด (เปิด/ปิดร้าน รับคืน ตั้งค่า ฯลฯ)</span>
    </div>
  </div>

  <div class="cats">
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
            <th><?= $fMode === 'month' ? 'วันที่ · เวลา' : 'เวลา' ?></th>
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
              <td class="num nowrap"><?php if ($fMode === 'month'): ?><?= e(thai_day_month(strtotime($r['date']))) ?> · <?php endif; ?><?= e($r['time']) ?></td>
              <td><span class="hist-br"><?= e($brAll[$r['branch']]['short']) ?></span></td>
              <td><span class="badge b-<?= e($m['tone']) ?>"><?= e($m['label']) ?></span></td>
              <td>
                <b class="hist-t"><?= e($r['title']) ?></b>
                <?php if ($r['void']): ?><small class="hist-n hist-void">ยกเลิกแล้ว</small><?php endif; ?>
                <?php if ($r['note'] !== ''): ?><small class="hist-n"><?= e($r['note']) ?></small><?php endif; ?>
              </td>
              <td class="nowrap"><?= e($r['by']) ?></td>
              <td class="r num nowrap">
                <?php if ($r['amount'] === null): ?>—
                <?php elseif ($r['kind'] === 'qty'): ?><?= $r['amount'] > 0 ? '+' : ($r['amount'] < 0 ? '−' : '') ?><?= number_format(abs($r['amount'])) ?> ชิ้น
                <?php else: ?><?= e(money2($r['amount'])) ?> ฿<?php endif; ?>
              </td>
              <td class="r">
                <?php if ($r['link'] !== ''): ?>
                  <a class="btn btn-ghost btn-sm" href="<?= e($r['link']) ?>" title="เปิดประวัติของสาขานี้ในวันนั้น">ดู</a>
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

<?php require dirname(__FILE__) . '/footer.php'; ?>
