<?php
/* ==========================================================
   AOSTOCK DEMO — รายงานยอดขาย
   ดูได้ 2 มุม (เฉพาะฉัน / ทั้งสาขา) × 3 ช่วง (รายวัน / รายเดือน / ทั้งหมด)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();
$code = work_branch($user);          // ผู้ดูแลเลือกสาขาได้จากแถบบน

/* ยังไม่เปิดร้านก็เข้าหน้านี้ได้ — ดูอย่างเดียว ไม่ได้เปลี่ยนสต๊อกหรือเงินสด
   (หน้าที่ทำรายการ เช่น ขาย รับเข้า ตัดออก ยังต้องเปิดร้านก่อน) */
$notOpened = (store_state($code) === null);

$canBranch = can($user, 'report_branch');
$scope = ($canBranch && isset($_GET['scope']) && $_GET['scope'] === 'branch') ? 'branch' : 'mine';
if ($user['role'] === 'admin' && !isset($_GET['scope'])) {
    $scope = 'branch';                               // ผู้ดูแลไม่ได้ขายเอง เปิดมาเห็นทั้งสาขาเลย
}
$mode  = isset($_GET['mode']) ? $_GET['mode'] : 'month';
if (!in_array($mode, array('day', 'month', 'all'), true)) {
    $mode = 'month';
}
$ref = isset($_GET['ref']) ? trim($_GET['ref']) : '';

$rep    = sales_report(array_merge($user, array('branch' => $code)), $scope, $mode, $ref);
$sum    = $rep['sum'];

/* ---------- CSV: ยอดขายรายวันของช่วงที่เลือก ---------- */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csv = array();
    foreach ($rep['days'] as $d) {
        $csv[] = array(date('Y-m-d', $d['ts']), $d['bills'], $d['qty'], csv_money($d['total']));
    }
    $csv[] = array('รวม', $sum['bills'], $sum['qty'], csv_money($sum['total']));
    csv_send('aostock-my-sales-' . date('Ymd', $rep['from']) . '-' . date('Ymd', $rep['to']) . '.csv',
             array('วันที่', 'บิล', 'ชิ้น', 'ยอดขาย (บาท)'), $csv);
}
$avg    = $sum['bills'] > 0 ? $sum['total'] / $sum['bills'] : 0;
$perDay = $sum['days']  > 0 ? $sum['total'] / $sum['days']  : 0;

/* ช่วงยาวเกิน 45 วัน ให้ยุบกราฟเป็นรายเดือน */
$bars    = (count($rep['days']) > 45) ? group_by_month($rep['days']) : $rep['days'];
$byMonth = (count($rep['days']) > 45);

$qs = function ($o) use ($scope, $mode, $ref) {
    $a = array('scope' => $scope, 'mode' => $mode, 'ref' => $ref);
    foreach ($o as $k => $v) { $a[$k] = $v; }
    $p = array();
    foreach ($a as $k => $v) {
        if ($v !== '') { $p[] = $k . '=' . rawurlencode($v); }
    }
    return 'report-sales.php?' . implode('&', $p);
};

$branch     = $code;
$PAGE_TITLE = 'รายงานยอดขาย';
$PAGE_SUB   = ($scope === 'mine' ? 'ของฉัน' : branch_name($code)) . ' · ' . $rep['label'];
$NAV_ACTIVE = 'report-sales.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($notOpened): ?>
  <div class="alert alert-info" role="status">
    <svg class="ico"><use href="#i-info"/></svg>
    <span>ยังไม่ได้เปิดร้านวันนี้ — ดูยอดขายย้อนหลังได้ตามปกติ ยอดของวันนี้จะเริ่มนับเมื่อเปิดร้านและขาย</span>
    <div class="alert-act">
      <a class="btn btn-ghost btn-sm" href="store.php"><svg class="ico"><use href="#i-store"/></svg> ไปเปิดร้าน</a>
      
    </div>
  </div>
<?php endif; ?>

<!-- ===== ตัวเลือกมุมมองและช่วงเวลา ===== -->
<section class="card rep-filter">
  <div class="rep-row">
    <span class="rep-lb">ดูของ</span>
    <div class="segs">
      <a class="seg<?= $scope === 'mine' ? ' on' : '' ?>" href="<?= e($qs(array('scope' => 'mine'))) ?>">
        <svg class="ico"><use href="#i-users"/></svg> เฉพาะฉัน
      </a>
      <?php if ($canBranch): ?>
      <a class="seg<?= $scope === 'branch' ? ' on' : '' ?>" href="<?= e($qs(array('scope' => 'branch'))) ?>">
        <svg class="ico"><use href="#i-building"/></svg> ทั้ง<?= e(branch_name($code)) ?>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="rep-row">
    <span class="rep-lb">ช่วงเวลา</span>
    <div class="segs">
      <a class="seg<?= $mode === 'day' ? ' on' : '' ?>"   href="<?= e($qs(array('mode' => 'day',   'ref' => ''))) ?>">รายวัน</a>
      <a class="seg<?= $mode === 'month' ? ' on' : '' ?>" href="<?= e($qs(array('mode' => 'month', 'ref' => ''))) ?>">รายเดือน</a>
      <a class="seg<?= $mode === 'all' ? ' on' : '' ?>"   href="<?= e($qs(array('mode' => 'all',   'ref' => ''))) ?>">ทั้งหมด</a>
    </div>
  </div>

  <?php if ($mode === 'day'): ?>
    <div class="rep-row">
      <span class="rep-lb">เลือกวัน</span>
      <div class="cats">
        <?php for ($i = 0; $i < 7; $i++): ?>
          <?php
          $d  = strtotime('-' . $i . ' day');
          $k  = date('Y-m-d', $d);
          $on = ($ref === $k) || ($ref === '' && $i === 0);
          ?>
          <a class="cat<?= $on ? ' on' : '' ?>" href="<?= e($qs(array('ref' => $k))) ?>">
            <?= $i === 0 ? 'วันนี้' : ($i === 1 ? 'เมื่อวาน' : short_day($d) . ' ' . date('j/n', $d)) ?>
          </a>
        <?php endfor; ?>
      </div>
    </div>
  <?php elseif ($mode === 'month'): ?>
    <div class="rep-row">
      <span class="rep-lb">เลือกเดือน</span>
      <div class="cats">
        <?php foreach (report_months($user) as $i => $m): ?>
          <?php $on = ($ref === $m['key']) || ($ref === '' && $i === 0); ?>
          <a class="cat<?= $on ? ' on' : '' ?>" href="<?= e($qs(array('ref' => $m['key']))) ?>"><?= e($m['label']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</section>

<!-- ===== ตัวเลขรวม ===== -->
<section class="kpis num" aria-label="สรุปยอดขาย">
  <?php foreach (array(
      array('ยอดขายรวม', money($sum['total']), 'บาท · ' . $rep['label'],       'i-coin',  ''),
      array('จำนวนบิล',  number_format($sum['bills']), 'ใบ · ขายจริง ' . number_format($sum['days']) . ' วัน', 'i-receipt', ''),
      array('จำนวนชิ้น',  number_format($sum['qty']),  'ชิ้นที่ขายออกไป',        'i-box',   ''),
      array('เฉลี่ยต่อบิล', money2($avg), 'บาท · เฉลี่ยวันละ ' . money($perDay) . ' บาท', 'i-chart', ''),
  ) as $k): ?>
    <div class="kpi<?= $k[4] ?>">
      <div class="kpi-top">
        <span><?= e($k[0]) ?></span>
        <span class="kpi-ic"><svg class="ico"><use href="#<?= e($k[3]) ?>"/></svg></span>
      </div>
      <b><?= $k[1] ?></b>
      <small><?= e($k[2]) ?></small>
    </div>
  <?php endforeach; ?>
</section>

<div class="grid-2 rep-grid">
  <div class="rep-col">

    <!-- ===== กราฟ ===== -->
    <section class="card">
      <div class="card-head">
        <div>
          <h2>ยอดขาย<?= $byMonth ? 'รายเดือน' : 'รายวัน' ?></h2>
          <span class="sub"><?= e($rep['label']) ?> · <?= count($bars) ?> <?= $byMonth ? 'เดือน' : 'วัน' ?></span>
        </div>
        <?php $__q = $_GET; $__q['export'] = 'csv'; ?>
        <a class="btn btn-ghost btn-sm" href="report-sales.php?<?= e(http_build_query($__q)) ?>"><svg class="ico"><use href="#i-in"/></svg> ดาวน์โหลด CSV</a>
      </div>
      <div class="card-body">
        <?php if ($sum['total'] <= 0): ?>
          <p class="empty">
            <svg class="ico"><use href="#i-chart"/></svg>
            ยังไม่มียอดขายในช่วงนี้<br><small>ลองเลือกช่วงเวลาอื่น หรือเริ่มขายจากปุ่มขายสินค้าด้านบน</small>
          </p>
        <?php else: ?>
          <?php
          $max = 1;
          foreach ($bars as $d) { if ($d['total'] > $max) { $max = $d['total']; } }
          $n  = count($bars);
          $bw = $n > 0 ? (100 / $n) : 100;
          ?>
          <div class="bars bars-rep" data-chart>
            <?php foreach ($bars as $d): ?>
              <?php $h = $d['total'] > 0 ? max(3, round($d['total'] / $max * 100)) : 0; ?>
              <div class="bar-col" style="width:<?= number_format($bw, 4, '.', '') ?>%">
                <div class="bar-hit" tabindex="0"
                     data-value="<?= number_format($d['total']) ?>"
                     data-month="<?= e($byMonth ? thai_month_full($d['ts']) : thai_date_full($d['ts'])) ?>">
                  <i style="height:<?= $h ?>%"></i>
                </div>
                <span class="bar-x"><?= e($byMonth ? thai_month_short($d['ts']) : date('j', $d['ts'])) ?></span>
              </div>
            <?php endforeach; ?>
            <div class="chart-tip"></div>
          </div>
          <div class="sr-only">
            <table>
              <caption>ยอดขายแยกตาม<?= $byMonth ? 'เดือน' : 'วัน' ?></caption>
              <tbody>
                <?php foreach ($bars as $d): ?>
                  <tr>
                    <th><?= e($byMonth ? thai_month_full($d['ts']) : thai_date_full($d['ts'])) ?></th>
                    <td><?= number_format($d['total']) ?> บาท</td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- ===== ตารางไล่วัน ===== -->
    <?php if ($sum['total'] > 0): ?>
      <section class="card">
        <div class="card-head">
          <div><h2>แยกตาม<?= $byMonth ? 'เดือน' : 'วัน' ?></h2>
               <span class="sub">เฉพาะวันที่มียอดขาย</span></div>
        </div>
        <div class="tbl-wrap">
          <table class="tbl tbl--compact">
            <thead>
              <tr><th><?= $byMonth ? 'เดือน' : 'วันที่' ?></th><th class="r">บิล</th>
                  <th class="r">ชิ้น</th><th class="r">ยอดขาย</th></tr>
            </thead>
            <tbody>
              <?php foreach (array_reverse($bars) as $d): ?>
                <?php if ($d['total'] <= 0) { continue; } ?>
                <tr>
                  <td data-label="<?= $byMonth ? 'เดือน' : 'วันที่' ?>">
                    <?= e($byMonth ? thai_month_full($d['ts']) : thai_date_full($d['ts'])) ?>
                  </td>
                  <td data-label="บิล"    class="r num"><?= number_format($d['bills']) ?></td>
                  <td data-label="ชิ้น"   class="r num"><?= number_format($d['qty']) ?></td>
                  <td data-label="ยอดขาย" class="r num"><b><?= money($d['total']) ?></b></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr><th>รวม</th>
                  <th class="r num"><?= number_format($sum['bills']) ?></th>
                  <th class="r num"><?= number_format($sum['qty']) ?></th>
                  <th class="r num"><?= money($sum['total']) ?></th></tr>
            </tfoot>
          </table>
        </div>
      </section>
    <?php endif; ?>
  </div>

  <aside class="rep-col">
    <?php if ($scope === 'mine'): ?>
      <!-- ===== ของฉัน แยกตามสาขาที่เคยอยู่ ===== -->
      <section class="card">
        <div class="card-head">
          <div><h2>แยกตามสาขาที่ทำงาน</h2>
               <span class="sub">ยอดของฉันยังอยู่กับสาขาที่ขายจริง</span></div>
        </div>
        <?php if (!$rep['branches']): ?>
          <p class="empty">ยังไม่มียอดขายในช่วงนี้</p>
        <?php else: ?>
          <ul class="rklist">
            <?php foreach ($rep['branches'] as $b): ?>
              <li class="rk<?= $b['branch'] === $code ? ' me' : '' ?>">
                <span class="n"><svg class="ico"><use href="#i-building"/></svg></span>
                <span class="nm">
                  <b><?= e(branch_name($b['branch'])) ?></b>
                  <small><?= $b['branch'] === $code ? 'สาขาปัจจุบัน' : 'เคยอยู่ · ล่าสุด ' . thai_date_full($b['last']) ?></small>
                </span>
                <span class="a num"><?= money($b['total']) ?><small><?= number_format($b['bills']) ?> บิล</small></span>
              </li>
            <?php endforeach; ?>
          </ul>
          <?php if (count($rep['branches']) > 1): ?>
            <p class="hint">
              <svg class="ico"><use href="#i-info"/></svg>
              <span>คุณเคยทำงานมากกว่าหนึ่งสาขาในช่วงนี้ ยอดแต่ละก้อนยังนับเป็นของสาขาที่ขายจริง
                    ไม่ถูกย้ายตามตัวคุณมา</span>
            </p>
          <?php endif; ?>
        <?php endif; ?>
      </section>
    <?php else: ?>
      <!-- ===== ทั้งสาขา แยกตามคน ===== -->
      <section class="card">
        <div class="card-head">
          <div><h2>แยกตามพนักงาน</h2>
               <span class="sub">เรียงจากยอดมากไปน้อย</span></div>
        </div>
        <?php if (!$rep['people']): ?>
          <p class="empty">ยังไม่มียอดขายในช่วงนี้</p>
        <?php else: ?>
          <ul class="rklist">
            <?php foreach ($rep['people'] as $i => $pp): ?>
              <li class="rk<?= $pp['user'] === $user['username'] ? ' me' : '' ?>">
                <span class="n"><?= $i + 1 ?></span>
                <span class="nm">
                  <b><?= e($pp['name']) ?><?= $pp['user'] === $user['username'] ? ' (ฉัน)' : '' ?></b>
                  <small><?= number_format($pp['qty']) ?> ชิ้น</small>
                </span>
                <span class="a num"><?= money($pp['total']) ?><small><?= number_format($pp['bills']) ?> บิล</small></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <section class="card">
      <div class="card-head"><div><h2>วิธีนับของรายงานนี้</h2></div></div>
      <div class="card-body">
        <ul class="notes">
          <li>ยอดขายผูกกับ<b>สาขา ณ วันที่ขาย</b> ย้ายสาขาแล้วยอดเก่าไม่ย้ายตาม</li>
          <li>"เฉพาะฉัน" ตามตัวคนข้ามสาขา · "ทั้งสาขา" นับทุกคนที่เคยขายที่สาขานี้</li>
          <li>บิลที่ยกเลิกแล้ว<b>ไม่ถูกนับ</b>เป็นยอดขาย</li>
          <li>วันนี้เป็นข้อมูลจริงจากการขาย ส่วนย้อนหลังเป็นข้อมูลตัวอย่างของเดโม</li>
        </ul>
      </div>
    </section>
  </aside>
</div>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
