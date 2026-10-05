<?php
/**
 * FILE: themes/aostock/adm-day-sales.php
 * ROLE: [ผู้ดูแล] รายละเอียดยอดขายของวันหนึ่ง (ส่วนที่โหลดเข้า popup)
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_sale, ao_stock_sale_item (ผ่าน api.php)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 7: บิลของวันอ่านจากตาราง
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   [ผู้ดูแล] รายละเอียดยอดขายของวันหนึ่ง (ส่วนที่โหลดเข้า popup)
   ----------------------------------------------------------
   ?b=รหัสสาขา | ALL &d=ปปปปดดวว  → ส่งกลับเฉพาะ HTML ของเนื้อหา popup (ไม่มี header / footer)
   แท็บ "บิล" = บิลทุกใบของวันนั้น (กดดูรายการในบิลได้) · แท็บ "สินค้าที่ขาย" = รวมรายสินค้า
   เรียกจาก: ช่องยอดขายรายวันในหน้า adm-report-branch.php (ปุ่มที่มี data-day-sales)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user  = require_login();                // หน้า adm- : เฉพาะผู้ดูแล
$brAll = branches_all();
$b     = (isset($_GET['b']) && is_string($_GET['b']) && ($_GET['b'] === 'ALL' || isset($brAll[$_GET['b']]))) ? $_GET['b'] : 'ALL';
$ts    = isset($_GET['d']) ? strtotime(preg_replace('/\D/', '', (string) $_GET['d'])) : false;
if ($ts === false || $ts > time()) {
    http_response_code(400);
    echo '<p class="empty">วันที่ไม่ถูกต้อง</p>';
    exit;
}
$codes = $b === 'ALL' ? array_keys($brAll) : array($b);

$bills = array();
$sum   = array('bills' => 0, 'void' => 0, 'qty' => 0, 'total' => 0, 'cash' => 0, 'disc' => 0);
$prods = array();
foreach ($codes as $c) {
    foreach (acct_bills($c, $ts) as $bl) {
        $bl['branch'] = $c;
        $bills[] = $bl;
        if (!empty($bl['void'])) {
            $sum['void']++;
            continue;
        }
        $sum['bills']++;
        $sum['qty']   += $bl['qty'];
        $sum['total'] += $bl['total'];
        $sum['cash']  += $bl['method'] === 'cash' ? $bl['total'] : 0;
        $sum['disc']  += !empty($bl['discount']) ? $bl['discount'] : 0;
        $sub = !empty($bl['subtotal']) ? $bl['subtotal'] : $bl['total'];
        $fac = $sub > 0 ? $bl['total'] / $sub : 1;
        foreach ($bl['lines'] as $l) {
            if (!isset($prods[$l['sku']])) {
                $prods[$l['sku']] = array('name' => $l['name'], 'unit' => $l['unit'], 'qty' => 0, 'total' => 0, 'bills' => 0);
            }
            $prods[$l['sku']]['qty']   += $l['qty'];
            $prods[$l['sku']]['total'] += $l['sum'] * $fac;
            $prods[$l['sku']]['bills']++;
        }
    }
}
usort($bills, function ($x, $y) { return strcmp($y['time'], $x['time']); });
uasort($prods, function ($x, $y) { return $x['qty'] == $y['qty'] ? ($x['total'] < $y['total'] ? 1 : -1) : ($x['qty'] < $y['qty'] ? 1 : -1); });
$where = $b === 'ALL' ? 'ทุกสาขา' : $brAll[$b]['name'];
?>
<div class="ds-head">
  <h2><?php echo e($where) ?> · <?php echo e(thai_date_full($ts)) ?></h2>
  <p class="sub">ยอดขาย <b><?php echo e(money2($sum['total'])) ?></b> บาท · <?php echo number_format($sum['bills']) ?> บิล · <?php echo number_format($sum['qty']) ?> ชิ้น
    · เงินสด <?php echo e(money2($sum['cash'])) ?> · โอน <?php echo e(money2($sum['total'] - $sum['cash'])) ?>
    <?php echo $sum['disc'] > 0 ? ' · ส่วนลด ' . e(money2($sum['disc'])) : '' ?><?php echo $sum['void'] ? ' · ยกเลิก ' . $sum['void'] . ' ใบ' : '' ?></p>
</div>

<div class="cats tabbar ds-tabs" role="tablist">
  <button type="button" class="cat on" data-ds-tab="bills" role="tab" aria-selected="true">บิล <i><?php echo count($bills) ?></i></button>
  <button type="button" class="cat" data-ds-tab="prods" role="tab" aria-selected="false">สินค้าที่ขาย <i><?php echo count($prods) ?></i></button>
</div>

<div class="ds-pane" data-ds-pane="bills">
  <?php if (!$bills) { ?>
    <p class="empty">ไม่มีบิลในวันนี้</p>
  <?php } else { ?>
    <ul class="ds-bills">
      <?php foreach ($bills as $bl) { $void = !empty($bl['void']); ?>
        <li>
          <details<?php echo $void ? ' class="is-void"' : '' ?>>
            <summary>
              <span class="ds-t num"><?php echo e($bl['time']) ?></span>
              <span class="ds-no"><b><?php echo e($bl['no']) ?></b>
                <small><?php echo $b === 'ALL' ? e($brAll[$bl['branch']]['short']) . ' · ' : '' ?><?php echo e($bl['by']) ?> · <?php echo $bl['method'] === 'cash' ? 'เงินสด' : 'โอน' ?> · <?php echo !empty($bl['vat']) ? 'VAT' : 'ไม่ VAT' ?><?php echo $void ? ' · ยกเลิกแล้ว' : '' ?></small></span>
              <span class="ds-amt num"><?php echo (int) $bl['items'] ?> รายการ · <b><?php echo e(money2($bl['total'])) ?></b></span>
            </summary>
            <table class="ds-lines">
              <?php foreach ($bl['lines'] as $l) { ?>
                <tr><td><?php echo e($l['name']) ?> <small><?php echo e($l['sku']) ?></small></td>
                    <td class="r num"><?php echo number_format($l['qty']) ?> × <?php echo e(money2($l['price'])) ?></td>
                    <td class="r num"><?php echo e(money2($l['sum'])) ?></td></tr>
              <?php } ?>
              <?php if (!empty($bl['discount'])) { ?>
                <tr><td colspan="2" class="r">ส่วนลดท้ายบิล</td><td class="r num">−<?php echo e(money2($bl['discount'])) ?></td></tr>
              <?php } ?>
              <tr class="ds-tot"><td colspan="2" class="r">รวม</td><td class="r num"><b><?php echo e(money2($bl['total'])) ?></b></td></tr>
            </table>
            <p class="ds-pr"><button type="button" class="btn btn-ghost btn-sm" data-bill-print="<?php echo e(bill_print_url($bl['branch'], $ts, $bl['no'])) ?>"
               data-bill-no="<?php echo e($bl['no']) ?>"><svg class="ico"><use href="#i-print"/></svg> ดู / พิมพ์บิล</button></p>
          </details>
        </li>
      <?php } ?>
    </ul>
  <?php } ?>
</div>

<div class="ds-pane" data-ds-pane="prods" hidden>
  <?php if (!$prods) { ?>
    <p class="empty">ไม่มีสินค้าที่ขายในวันนี้</p>
  <?php } else { ?>
    <table class="ds-lines ds-prods">
      <thead><tr><th>สินค้า</th><th class="r">จำนวน</th><th class="r">บิล</th><th class="r">ยอดขาย</th></tr></thead>
      <tbody>
        <?php $i = 0; foreach ($prods as $sku => $p) { $i++; ?>
          <tr><td><span class="rep-rank"><?php echo $i ?></span><?php echo e($p['name']) ?> <small><?php echo e($sku) ?></small></td>
              <td class="r num"><?php echo number_format($p['qty']) ?> <?php echo e($p['unit']) ?></td>
              <td class="r num"><?php echo number_format($p['bills']) ?></td>
              <td class="r num"><?php echo e(money2($p['total'])) ?></td></tr>
        <?php } ?>
      </tbody>
    </table>
  <?php } ?>
</div>
