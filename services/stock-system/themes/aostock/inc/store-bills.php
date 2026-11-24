<?php
/**
 * FILE: themes/aostock/inc/store-bills.php
 * ROLE: หน้าต่าง "บิลของวันนี้" ในหน้าปิดร้าน — กดจำนวนบิล (ขายเงินสด / ลูกค้าโอนจ่าย) ดูรายชื่อบิลของทุกคนในสาขา · ดู / พิมพ์อย่างเดียว
 * DEPENDS: themes/aostock/include/function.php (Stock_getDayBillsByChannel · Stock_buildBillPrintButton) · หน้าต่างพิมพ์บิลใน inc/footer.php
 * TABLES: ao_stock_sale, ao_stock_sale_item, ao_stock_staff (ผ่าน api.php)
 * TODO:
 *   - [ ] ถ้าวันหนึ่งเกิน 300 ใบบ่อย ๆ เพิ่มปุ่ม "แสดงเพิ่ม"
 *   - [x] ประวัติการแก้ไขดู docs/CHANGELOG.md
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ต้องกำหนดก่อน include: $user $code
   $BILLS_PART = 'dialog' → หน้าต่างเปล่า + สคริปต์ (ใส่ครั้งเดียวในหน้าปิดร้าน) · 'list' → รายการ (store.php?bills=cash|transfer) */

require_once dirname(__FILE__) . '/../include/function.php';

$BILLS_PART = (isset($BILLS_PART) && $BILLS_PART === 'list') ? 'list' : 'dialog';

if ($BILLS_PART === 'dialog') { ?>
<dialog class="fm-modal mb-modal" id="sb-modal" aria-labelledby="sb-t">
  <div class="fm-head">
    <div><h2 id="sb-t">บิลของวันนี้</h2><span class="sub">บิลของทุกคนที่<?php echo e(branch_name($code)) ?> วันนี้ · กดดู / พิมพ์บิลได้</span></div>
    <button type="button" class="icon-btn" data-sb-close aria-label="ปิด"><svg class="ico"><use href="#i-x"/></svg></button>
  </div>
  <div class="mb-body" id="sb-body" data-no-busy><p class="empty">กำลังโหลด…</p></div>
</dialog>
<script>
/* กดจำนวนบิลในการ์ดหน้าปิดร้าน → โหลดรายการใหม่ทุกครั้ง */
(function () {
  var dlg = document.getElementById('sb-modal'), body = document.getElementById('sb-body'), ttl = document.getElementById('sb-t');
  if (!dlg || !dlg.showModal) { return; }
  document.addEventListener('click', function (ev) {
    if (!ev.target.closest) { return; }
    var b = ev.target.closest('[data-sb-open]');
    if (b) {
      var ch = (b.getAttribute('data-sb-open') === 'transfer') ? 'transfer' : 'cash';
      ttl.textContent = (ch === 'transfer') ? 'บิลลูกค้าโอนจ่ายวันนี้' : 'บิลขายเงินสดวันนี้';
      body.innerHTML = '<p class="empty">กำลังโหลด…</p>';
      dlg.showModal();
      fetch('store.php?bills=' + ch, { credentials: 'same-origin' })
        .then(function (r) { return r.text(); })
        .then(function (t) { body.innerHTML = t; })
        .catch(function () { body.innerHTML = '<p class="empty">โหลดรายการไม่ได้ — ลองใหม่อีกครั้ง</p>'; });
      return;
    }
    if (ev.target.closest('#sb-modal [data-sb-close]') || ev.target === dlg) { dlg.close(); }
  });
})();
</script>
<?php
    return;
}

/* ---------- รายการ ---------- */
$ch = (isset($_GET['bills']) && $_GET['bills'] === 'transfer') ? 'transfer' : 'cash';
$st = store_is_open($code) ? store_state($code) : null;
if ($st === null) { ?>
  <p class="empty"><svg class="ico"><use href="#i-receipt"/></svg>ร้านไม่ได้เปิดอยู่</p>
<?php
    return;
}
$rows = Stock_getDayBillsByChannel($st['id'], $ch, 301);
$more = count($rows) > 300;
$rows = array_slice($rows, 0, 300);
$isTr = ($ch === 'transfer');
$paid = 0;
$void = 0;
$sumIn = 0.0;
$sumOut = 0.0;
$sumTrOut = 0.0;
foreach ($rows as $b) {
    $in = $isTr ? $b['pay_transfer'] : $b['pay_cash'];
    if ($b['void']) { $void++; } elseif ($in > 0) { $paid++; }
    $sumIn    += $in;
    $sumOut   += $isTr ? 0 : $b['change_cash'];
    $sumTrOut += $b['change_transfer'];
}
?>
<p class="mb-sum">
  <b><?php echo number_format($paid) ?></b> บิล ·
  <?php if ($isTr) { ?>
    ยอดโอน <b class="num"><?php echo money2($sumIn) ?></b> บาท<?php echo $sumTrOut > 0 ? ' · ทอนเป็นโอน <b class="num">' . money2($sumTrOut) . '</b> บาท' : '' ?>
  <?php } else { ?>
    เงินสดจากขาย <b class="num"><?php echo money2($sumIn - $sumOut) ?></b> บาท (รับ − ทอน)
  <?php } ?>
  <?php echo $void ? ' · ยกเลิกแล้วคืนเงิน ' . number_format($void) . ' ใบ (ท้ายรายการ)' : '' ?>
</p>
<?php if (!$rows) { ?>
  <p class="empty"><svg class="ico"><use href="#i-receipt"/></svg>ยังไม่มีบิล<?php echo $isTr ? 'ที่ลูกค้าโอนจ่าย' : 'ขายเงินสด' ?>วันนี้</p>
<?php } else { ?>
  <ul class="mb-list">
    <?php foreach ($rows as $b) {
        $first = $b['lines'] ? $b['lines'][0]['name'] : '';
        $money = array();                                  // เงินของช่องทางนี้ในบิล
        if ($isTr) {
            if ($b['pay_transfer'] > 0) { $money[] = 'โอน ' . money2($b['pay_transfer']); }
        } else {
            $money[] = 'รับ ' . money2($b['pay_cash']);
            if ($b['change_cash'] > 0) { $money[] = 'ทอน ' . money2($b['change_cash']); }
        }
        if ($b['change_transfer'] > 0) { $money[] = 'ทอนเป็นโอน ' . money2($b['change_transfer']); } ?>
      <li class="mb-row<?php echo $b['void'] ? ' is-void' : '' ?>">
        <span class="mb-time num"><?php echo e($b['time']) ?></span>
        <span class="mb-no">
          <b><?php echo e($b['no']) ?></b><?php if ($b['void']) { ?> <span class="bdg bdg-out">ยกเลิก · <?php echo $b['void_refund_channel'] === 'transfer' ? 'คืนเป็นโอน' : 'คืนเงินสด' ?></span><?php } ?>
          <small><?php echo e($first) ?><?php echo $b['items'] > 1 ? ' + อีก ' . ($b['items'] - 1) . ' รายการ' : '' ?> · <?php echo number_format($b['qty']) ?> ชิ้น · <?php echo e($b['by']) ?></small>
          <small class="num"><?php echo e(implode(' · ', $money)) ?></small>
        </span>
        <span class="mb-amt num"><?php echo money2($b['total']) ?></span>
        <?php echo Stock_buildBillPrintButton($b) ?>
      </li>
    <?php } ?>
  </ul>
  <?php if ($more) { ?><p class="adm-hint mb-more">แสดง 300 ใบแรก — ดูบิลทั้งหมดได้ที่หน้าประวัติขาย</p><?php } ?>
<?php } ?>
