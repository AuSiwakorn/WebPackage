<?php
/**
 * FILE: themes/aostock/inc/sale-bills.php
 * ROLE: หน้าต่าง "บิลของฉันวันนี้" ในหน้าขาย — บิลที่ตัวเองขายวันนี้ เลือกช่วงเวลาได้ · ดู / พิมพ์อย่างเดียว (แก้ / ยกเลิกไม่ได้)
 * DEPENDS: themes/aostock/include/function.php (Stock_getMyBillsToday · Stock_buildBillPrintButton) · หน้าต่างพิมพ์บิลใน inc/footer.php
 * TABLES: ao_stock_sale, ao_stock_sale_item, ao_stock_staff (ผ่าน api.php)
 * TODO:
 *   - [ ] ถ้าบิลของคนเดียวต่อวันเกิน 200 ใบบ่อย ๆ เพิ่มปุ่ม "แสดงเพิ่ม"
 *   - [x] ประวัติการแก้ไขดู docs/CHANGELOG.md
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ต้องกำหนดก่อน include: $user $code
   $BILLS_PART = 'dialog' → หน้าต่างเปล่า + สคริปต์ (ใส่ครั้งเดียวในหน้าเต็ม) · 'list' → รายการตามช่วงเวลา (sale.php?bills=1&bf=HH:MM&bt=HH:MM) */

require_once dirname(__FILE__) . '/../include/function.php';

$BILLS_PART = (isset($BILLS_PART) && $BILLS_PART === 'list') ? 'list' : 'dialog';

if ($BILLS_PART === 'dialog') { ?>
<dialog class="fm-modal mb-modal" id="mb-modal" aria-labelledby="mb-t">
  <div class="fm-head">
    <div><h2 id="mb-t">บิลของฉันวันนี้</h2><span class="sub">บิลที่คุณขายวันนี้ที่<?php echo e(branch_name($code)) ?> · กดดู / พิมพ์บิลที่ลืมพิมพ์ได้</span></div>
    <button type="button" class="icon-btn" data-mb-close aria-label="ปิด"><svg class="ico"><use href="#i-x"/></svg></button>
  </div>
  <div class="mb-body" id="mb-body" data-no-busy><p class="empty">กำลังโหลด…</p></div>
</dialog>
<script>
/* บิลของฉันวันนี้ — ปุ่มเปิดอยู่ในตะกร้าที่ htmx สลับ จึงผูกแบบ delegation · เปิดทุกครั้งโหลดรายการใหม่ */
(function () {
  var dlg = document.getElementById('mb-modal'), body = document.getElementById('mb-body');
  if (!dlg || !dlg.showModal) { return; }                // htmx โหลดทีหลังใน footer — ใช้ตอนกดเท่านั้น
  var pad = function (n) { return (n < 10 ? '0' : '') + n; };
  var hm  = function (d) { return pad(d.getHours()) + ':' + pad(d.getMinutes()); };
  document.addEventListener('click', function (ev) {
    if (!ev.target.closest) { return; }
    if (ev.target.closest('[data-mb-open]') && window.htmx) {
      body.innerHTML = '<p class="empty">กำลังโหลด…</p>';
      dlg.showModal();
      htmx.ajax('GET', 'sale.php?bills=1', { target: '#mb-body', swap: 'innerHTML' });
      return;
    }
    var q = ev.target.closest('#mb-modal [data-mb-range]');
    if (q) {                                           // ปุ่มช่วงเวลาลัด: N นาทีล่าสุด / ทั้งวัน
      var f = q.form, m = q.getAttribute('data-mb-range'), now = new Date();
      f.elements.bf.value = (m === 'all') ? '' : hm(new Date(Math.max(new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime(), now.getTime() - m * 60000)));
      f.elements.bt.value = (m === 'all') ? '' : hm(now);
      f.elements.r.value  = m;                         // ปุ่มที่กด — เซิร์ฟเวอร์ไฮไลต์ให้ในรายการที่ส่งกลับ
      htmx.trigger(f, 'submit');
      return;
    }
    if (ev.target.closest('#mb-modal [data-mb-close]') || ev.target === dlg) { dlg.close(); }
  });
  document.addEventListener('input', function (ev) {      // พิมพ์เวลาเอง = ไม่ใช่ช่วงลัดแล้ว
    var i = ev.target;
    if (i && i.form && i.closest && i.closest('#mb-modal') && (i.name === 'bf' || i.name === 'bt')) { i.form.elements.r.value = ''; }
  });
})();
</script>
<?php
    return;
}

/* ---------- รายการตามช่วงเวลา ---------- */
$hm   = function ($k) {
    $v = (isset($_GET[$k]) && is_string($_GET[$k])) ? $_GET[$k] : '';
    return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $v) ? $v : '';
};
$bf   = $hm('bf');
$bt   = $hm('bt');
$rk   = (isset($_GET['r']) && in_array($_GET['r'], array('30', '60', '180', 'all'), true)) ? $_GET['r'] : '';   // ปุ่มช่วงลัดที่กด
$rk   = ($rk === '' && $bf === '' && $bt === '') ? 'all' : $rk;
$rows = Stock_getMyBillsToday($code, $user, $bf, $bt, 201);
$more = count($rows) > 200;
$rows = array_slice($rows, 0, 200);
$sum  = 0.0;
$paid = 0;
foreach ($rows as $b) {
    if (!$b['void']) { $sum += $b['total']; $paid++; }
}
$range = ($bf === '' && $bt === '') ? 'ทั้งวัน' : (($bf !== '' ? $bf : '00:00') . ' – ' . ($bt !== '' ? $bt : '23:59') . ' น.');
?>
<form class="mb-range" hx-get="sale.php" hx-target="#mb-body" hx-swap="innerHTML" hx-trigger="change, submit" data-no-busy>
  <input type="hidden" name="bills" value="1">
  <input type="hidden" name="r" value="<?php echo e($rk) ?>">
  <label class="mb-t"><span>ตั้งแต่</span><input class="input" type="time" name="bf" value="<?php echo e($bf) ?>"></label>
  <label class="mb-t"><span>ถึง</span><input class="input" type="time" name="bt" value="<?php echo e($bt) ?>"></label>
  <div class="segs mb-quick" role="group" aria-label="ช่วงเวลาลัด">
    <?php foreach (array('30' => '30 นาที', '60' => '1 ชม.', '180' => '3 ชม.', 'all' => 'ทั้งวัน') as $k => $lb) { ?>
      <button type="button" class="seg<?php echo $rk === (string) $k ? ' on' : '' ?>" data-mb-range="<?php echo $k ?>"><?php echo $lb ?></button>
    <?php } ?>
  </div>
</form>
<p class="mb-sum">
  <?php echo $range ?> · <b><?php echo number_format(count($rows)) ?></b> ใบ
  <?php if ($rows) { ?> · ยอดรวม <b class="num"><?php echo money2($sum) ?></b> บาท<?php echo $paid < count($rows) ? ' (ไม่รวมบิลที่ยกเลิก)' : '' ?><?php } ?>
</p>
<?php if (!$rows) { ?>
  <p class="empty"><svg class="ico"><use href="#i-receipt"/></svg>ไม่มีบิลของคุณในช่วงเวลานี้<br><small>ลองเลือก “ทั้งวัน” หรือขยายช่วงเวลา</small></p>
<?php } else { ?>
  <ul class="mb-list">
    <?php foreach ($rows as $b) { $first = $b['lines'] ? $b['lines'][0]['name'] : ''; ?>
      <li class="mb-row<?php echo $b['void'] ? ' is-void' : '' ?>">
        <span class="mb-time num"><?php echo e($b['time']) ?></span>
        <span class="mb-no">
          <b><?php echo e($b['no']) ?></b><?php if ($b['void']) { ?> <span class="bdg bdg-out">ยกเลิก</span><?php } ?>
          <small><?php echo e($first) ?><?php echo $b['items'] > 1 ? ' + อีก ' . ($b['items'] - 1) . ' รายการ' : '' ?> · <?php echo number_format($b['qty']) ?> ชิ้น · <?php echo $b['method'] === 'cash' ? 'เงินสด' : 'โอน / พร้อมเพย์' ?></small>
        </span>
        <span class="mb-amt num"><?php echo money2($b['total']) ?></span>
        <?php echo Stock_buildBillPrintButton($b) ?>
      </li>
    <?php } ?>
  </ul>
  <?php if ($more) { ?><p class="adm-hint mb-more">แสดง 200 ใบล่าสุด — เลือกช่วงเวลาให้แคบลงเพื่อหาบิลที่เก่ากว่า</p><?php } ?>
<?php } ?>
