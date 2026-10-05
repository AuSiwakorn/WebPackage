<?php
/**
 * FILE: themes/aostock/report-sales.php
 * ROLE: รายงานยอดขาย
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_sale, ao_stock_staff, ao_stock_branch (ผ่าน api.php — sales_report)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 8: ทุกวันอ่านจากบิลจริงทั้งช่วงในคิวรีเดียว (เดิมวันก่อนเป็นข้อมูลตัวอย่าง)
 *   - [x] ช่วงที่ 11: เข้าหน้านี้ต้องมีสิทธิ์ "รายงานยอดขาย" (report) · ปุ่มไปเปิดร้านตามสิทธิ์
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   รายงานยอดขาย
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

<?php if ($notOpened) { ?>
  <div class="alert alert-info" role="status">
    <svg class="ico"><use href="#i-info"/></svg>
    <span>ยังไม่ได้เปิดร้านวันนี้ — ดูยอดขายย้อนหลังได้ตามปกติ ยอดของวันนี้จะเริ่มนับเมื่อเปิดร้านและขาย</span>
    <?php if (page_ok($user, 'store.php')) { ?>
    <div class="alert-act">
      <a class="btn btn-ghost btn-sm" href="store.php"><svg class="ico"><use href="#i-store"/></svg> ไปเปิดร้าน</a>
    </div>
    <?php } ?>
  </div>
<?php } ?>

<!-- ===== ตัวเลือกมุมมองและช่วงเวลา ===== -->
<section class="card rep-filter">
  <div class="rep-row">
    <span class="rep-lb">ดูของ</span>
    <div class="segs">
      <a class="seg<?php echo $scope === 'mine' ? ' on' : '' ?>" href="<?php echo e($qs(array('scope' => 'mine'))) ?>">
        <svg class="ico"><use href="#i-users"/></svg> เฉพาะฉัน
      </a>
      <?php if ($canBranch) { ?>
      <a class="seg<?php echo $scope === 'branch' ? ' on' : '' ?>" href="<?php echo e($qs(array('scope' => 'branch'))) ?>">
        <svg class="ico"><use href="#i-building"/></svg> ทั้ง<?php echo e(branch_name($code)) ?>
      </a>
      <?php } ?>
    </div>
  </div>

  <div class="rep-row">
    <span class="rep-lb">ช่วงเวลา</span>
    <div class="segs">
      <a class="seg<?php echo $mode === 'day' ? ' on' : '' ?>"   href="<?php echo e($qs(array('mode' => 'day',   'ref' => ''))) ?>">รายวัน</a>
      <a class="seg<?php echo $mode === 'month' ? ' on' : '' ?>" href="<?php echo e($qs(array('mode' => 'month', 'ref' => ''))) ?>">รายเดือน</a>
      <a class="seg<?php echo $mode === 'all' ? ' on' : '' ?>"   href="<?php echo e($qs(array('mode' => 'all',   'ref' => ''))) ?>">ทั้งหมด</a>
    </div>
  </div>

  <?php if ($mode === 'day') { ?>
    <div class="rep-row">
      <span class="rep-lb">เลือกวัน</span>
      <div class="cats">
        <?php for ($i = 0; $i < 7; $i++) { ?>
          <?php
          $d  = strtotime('-' . $i . ' day');
          $k  = date('Y-m-d', $d);
          $on = ($ref === $k) || ($ref === '' && $i === 0);
          ?>
          <a class="cat<?php echo $on ? ' on' : '' ?>" href="<?php echo e($qs(array('ref' => $k))) ?>">
            <?php echo $i === 0 ? 'วันนี้' : ($i === 1 ? 'เมื่อวาน' : short_day($d) . ' ' . date('j/n', $d)) ?>
          </a>
        <?php } ?>
      </div>
    </div>
  <?php } elseif ($mode === 'month') { ?>
    <div class="rep-row">
      <span class="rep-lb">เลือกเดือน</span>
      <div class="cats">
        <?php foreach (report_months($user) as $i => $m) { ?>
          <?php $on = ($ref === $m['key']) || ($ref === '' && $i === 0); ?>
          <a class="cat<?php echo $on ? ' on' : '' ?>" href="<?php echo e($qs(array('ref' => $m['key']))) ?>"><?php echo e($m['label']) ?></a>
        <?php } ?>
      </div>
    </div>
  <?php } ?>
</section>

<!-- ===== ตัวเลขรวม ===== -->
<section class="kpis num" aria-label="สรุปยอดขาย">
  <?php foreach (array(
      array('ยอดขายรวม', money($sum['total']), 'บาท · ' . $rep['label'],       'i-coin',  ''),
      array('จำนวนบิล',  number_format($sum['bills']), 'ใบ · ขายจริง ' . number_format($sum['days']) . ' วัน', 'i-receipt', ''),
      array('จำนวนชิ้น',  number_format($sum['qty']),  'ชิ้นที่ขายออกไป',        'i-box',   ''),
      array('เฉลี่ยต่อบิล', money2($avg), 'บาท · เฉลี่ยวันละ ' . money($perDay) . ' บาท', 'i-chart', ''),
  ) as $k) { ?>
    <div class="kpi<?php echo $k[4] ?>">
      <div class="kpi-top">
        <span><?php echo e($k[0]) ?></span>
        <span class="kpi-ic"><svg class="ico"><use href="#<?php echo e($k[3]) ?>"/></svg></span>
      </div>
      <b><?php echo $k[1] ?></b>
      <small><?php echo e($k[2]) ?></small>
    </div>
  <?php } ?>
</section>

<div class="grid-2 rep-grid">
  <div class="rep-col">

    <!-- ===== กราฟ ===== -->
    <section class="card">
      <div class="card-head">
        <div>
          <h2>ยอดขาย<?php echo $byMonth ? 'รายเดือน' : 'รายวัน' ?></h2>
          <span class="sub"><?php echo e($rep['label']) ?> · <?php echo count($bars) ?> <?php echo $byMonth ? 'เดือน' : 'วัน' ?></span>
        </div>
        <?php $__q = $_GET; $__q['export'] = 'csv'; ?>
        <a class="btn btn-ghost btn-sm" href="report-sales.php?<?php echo e(http_build_query($__q)) ?>"><svg class="ico"><use href="#i-in"/></svg> ดาวน์โหลด CSV</a>
      </div>
      <div class="card-body">
        <?php if ($sum['total'] <= 0) { ?>
          <p class="empty">
            <svg class="ico"><use href="#i-chart"/></svg>
            ยังไม่มียอดขายในช่วงนี้<br><small>ลองเลือกช่วงเวลาอื่น หรือเริ่มขายจากปุ่มขายสินค้าด้านบน</small>
          </p>
        <?php } else { ?>
          <?php
          $max = 1;
          foreach ($bars as $d) { if ($d['total'] > $max) { $max = $d['total']; } }
          $n  = count($bars);
          $bw = $n > 0 ? (100 / $n) : 100;
          ?>
          <div class="bars bars-rep" data-chart>
            <?php foreach ($bars as $d) { ?>
              <?php $h = $d['total'] > 0 ? max(3, round($d['total'] / $max * 100)) : 0; ?>
              <div class="bar-col" style="width:<?php echo number_format($bw, 4, '.', '') ?>%">
                <div class="bar-hit" tabindex="0"
                     data-value="<?php echo number_format($d['total']) ?>"
                     data-month="<?php echo e($byMonth ? thai_month_full($d['ts']) : thai_date_full($d['ts'])) ?>">
                  <i style="height:<?php echo $h ?>%"></i>
                </div>
                <span class="bar-x"><?php echo e($byMonth ? thai_month_short($d['ts']) : date('j', $d['ts'])) ?></span>
              </div>
            <?php } ?>
            <div class="chart-tip"></div>
          </div>
          <div class="sr-only">
            <table>
              <caption>ยอดขายแยกตาม<?php echo $byMonth ? 'เดือน' : 'วัน' ?></caption>
              <tbody>
                <?php foreach ($bars as $d) { ?>
                  <tr>
                    <th><?php echo e($byMonth ? thai_month_full($d['ts']) : thai_date_full($d['ts'])) ?></th>
                    <td><?php echo number_format($d['total']) ?> บาท</td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          </div>
        <?php } ?>
      </div>
    </section>

    <!-- ===== ตารางไล่วัน ===== -->
    <?php if ($sum['total'] > 0) { ?>
      <section class="card">
        <div class="card-head">
          <div><h2>แยกตาม<?php echo $byMonth ? 'เดือน' : 'วัน' ?></h2>
               <span class="sub">เฉพาะวันที่มียอดขาย</span></div>
        </div>
        <div class="tbl-wrap">
          <table class="tbl tbl--compact">
            <thead>
              <tr><th><?php echo $byMonth ? 'เดือน' : 'วันที่' ?></th><th class="r">บิล</th>
                  <th class="r">ชิ้น</th><th class="r">ยอดขาย</th></tr>
            </thead>
            <tbody>
              <?php foreach (array_reverse($bars) as $d) { ?>
                <?php if ($d['total'] <= 0) { continue; } ?>
                <tr>
                  <td data-label="<?php echo $byMonth ? 'เดือน' : 'วันที่' ?>">
                    <?php echo e($byMonth ? thai_month_full($d['ts']) : thai_date_full($d['ts'])) ?>
                  </td>
                  <td data-label="บิล"    class="r num"><?php echo number_format($d['bills']) ?></td>
                  <td data-label="ชิ้น"   class="r num"><?php echo number_format($d['qty']) ?></td>
                  <td data-label="ยอดขาย" class="r num"><b><?php echo money($d['total']) ?></b></td>
                </tr>
              <?php } ?>
            </tbody>
            <tfoot>
              <tr><th>รวม</th>
                  <th class="r num"><?php echo number_format($sum['bills']) ?></th>
                  <th class="r num"><?php echo number_format($sum['qty']) ?></th>
                  <th class="r num"><?php echo money($sum['total']) ?></th></tr>
            </tfoot>
          </table>
        </div>
      </section>
    <?php } ?>
  </div>

  <aside class="rep-col">
    <?php if ($scope === 'mine') { ?>
      <!-- ===== ของฉัน แยกตามสาขาที่เคยอยู่ ===== -->
      <section class="card">
        <div class="card-head">
          <div><h2>แยกตามสาขาที่ทำงาน</h2>
               <span class="sub">ยอดของฉันยังอยู่กับสาขาที่ขายจริง</span></div>
        </div>
        <?php if (!$rep['branches']) { ?>
          <p class="empty">ยังไม่มียอดขายในช่วงนี้</p>
        <?php } else { ?>
          <ul class="rklist">
            <?php foreach ($rep['branches'] as $b) { ?>
              <li class="rk<?php echo $b['branch'] === $code ? ' me' : '' ?>">
                <span class="n"><svg class="ico"><use href="#i-building"/></svg></span>
                <span class="nm">
                  <b><?php echo e(branch_name($b['branch'])) ?></b>
                  <small><?php echo $b['branch'] === $code ? 'สาขาปัจจุบัน' : 'เคยอยู่ · ล่าสุด ' . thai_date_full($b['last']) ?></small>
                </span>
                <span class="a num"><?php echo money($b['total']) ?><small><?php echo number_format($b['bills']) ?> บิล</small></span>
              </li>
            <?php } ?>
          </ul>
          <?php if (count($rep['branches']) > 1) { ?>
            <p class="hint">
              <svg class="ico"><use href="#i-info"/></svg>
              <span>คุณเคยทำงานมากกว่าหนึ่งสาขาในช่วงนี้ ยอดแต่ละก้อนยังนับเป็นของสาขาที่ขายจริง
                    ไม่ถูกย้ายตามตัวคุณมา</span>
            </p>
          <?php } ?>
        <?php } ?>
      </section>
    <?php } else { ?>
      <!-- ===== ทั้งสาขา แยกตามคน ===== -->
      <section class="card">
        <div class="card-head">
          <div><h2>แยกตามพนักงาน</h2>
               <span class="sub">เรียงจากยอดมากไปน้อย</span></div>
        </div>
        <?php if (!$rep['people']) { ?>
          <p class="empty">ยังไม่มียอดขายในช่วงนี้</p>
        <?php } else { ?>
          <ul class="rklist">
            <?php foreach ($rep['people'] as $i => $pp) { ?>
              <li class="rk<?php echo $pp['user'] === $user['username'] ? ' me' : '' ?>">
                <span class="n"><?php echo $i + 1 ?></span>
                <span class="nm">
                  <b><?php echo e($pp['name']) ?><?php echo $pp['user'] === $user['username'] ? ' (ฉัน)' : '' ?></b>
                  <small><?php echo number_format($pp['qty']) ?> ชิ้น</small>
                </span>
                <span class="a num"><?php echo money($pp['total']) ?><small><?php echo number_format($pp['bills']) ?> บิล</small></span>
              </li>
            <?php } ?>
          </ul>
        <?php } ?>
      </section>
    <?php } ?>

    <section class="card">
      <div class="card-head"><div><h2>วิธีนับของรายงานนี้</h2></div></div>
      <div class="card-body">
        <ul class="notes">
          <li>ยอดขายผูกกับ<b>สาขา ณ วันที่ขาย</b> ย้ายสาขาแล้วยอดเก่าไม่ย้ายตาม</li>
          <li>"เฉพาะฉัน" ตามตัวคนข้ามสาขา · "ทั้งสาขา" นับทุกคนที่เคยขายที่สาขานี้</li>
          <li>บิลที่ยกเลิกแล้ว<b>ไม่ถูกนับ</b>เป็นยอดขาย</li>
          <li>ยอดขาย = ยอดที่ลูกค้าจ่ายจริงหลังส่วนลด · ยังไม่หักเงินที่คืนลูกค้า (ดูที่หน้าบิลขายและเงินเข้า)</li>
        </ul>
      </div>
    </section>
  </aside>
</div>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
