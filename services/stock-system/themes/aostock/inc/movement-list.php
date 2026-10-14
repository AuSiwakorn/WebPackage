<?php
/**
 * FILE: themes/aostock/inc/movement-list.php
 * ROLE: รายการสินค้าทางซ้ายของหน้าประวัติเคลื่อนไหว (htmx สลับได้)
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_move (ผ่าน api.php — move_rows / move_view)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] อ่าน / เขียนข้อมูลจากตาราง ao_stock_* ผ่าน api.php (ช่วงที่ 5–9)
 *   - [x] ช่วงที่ 10: แก้ File Header ให้ตรงกับระบบจริง
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 4): ครั้งละ 60 รายการ + ปุ่ม “แสดงเพิ่ม” (htmx ?from= ส่งกลับแค่ชุดถัดไป)
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 5): ค้นสินค้าด้วย SQL แบบแบ่งหน้า (Stock_searchPickProducts) — ฐานข้อมูลกรอง / นับ ส่งมาแค่ 60 ตัวที่จะวาด (เดิมดึงทั้งร้านมากรองใน PHP)
 *         ลิงก์สินค้าพก n= ไปด้วย — เลือกสินค้าที่อยู่ท้าย ๆ แล้วหน้าใหม่ยังแสดงจำนวนเท่าเดิม (สินค้าที่เลือกไม่หายจากรายการ)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   รายการสินค้าทางซ้ายของหน้าประวัติเคลื่อนไหว (htmx สลับได้)
   ต้องกำหนดก่อน include: $code $q $cat $sku $period (รายการสินค้าค้นเองด้วย Stock_searchPickProducts — ช่วง 5)
   ใช้ร่วมกับ adm-movements.php — ตั้ง $MV_PAGE (หน้าปลายทาง) และ $MV_EXTRA (เช่น array('b' => 'RS')) ได้
   htmx ?from=N (กด “แสดงเพิ่ม”) → ส่งกลับแค่ <li> ชุดถัดไป + ปุ่มใหม่
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$mvPage  = isset($MV_PAGE) ? $MV_PAGE : 'movements.php';
$mvExtra = isset($MV_EXTRA) ? $MV_EXTRA : array();
list($from, $end, $isMore) = Stock_getListRange();
/* ฐานข้อมูลกรอง นับ และส่งมาเฉพาะรายการที่จะวาดรอบนี้ (ช่วง 5) */
$found = Stock_searchPickProducts($code, $q, $cat, $isMore ? $from : 0, $isMore ? $end - $from : $end);
$list  = $found['rows'];
$total = $found['total'];

/* รายการสินค้าหนึ่งแถว — ลิงก์พก n= เมื่อแสดงเกิน 60 */
$item = function ($p) use ($mvPage, $mvExtra, $q, $cat, $sku, $period, $end) {
    $href = $mvPage . move_qs($q, $cat, $p['sku'], $period, $mvExtra);
    $href .= ($end > PRODUCT_PAGE_SIZE) ? (strpos($href, '?') === false ? '?' : '&') . 'n=' . $end : ''; ?>
    <li>
      <a class="mv-item<?php echo $p['sku'] === $sku ? ' on' : '' ?>" href="<?php echo e($href) ?>">
        <?php echo thumb_html($p) ?>
        <span class="mv-it">
          <b><?php echo e($p['name']) ?></b>
          <small><?php echo e($p['sku']) ?></small>
        </span>
        <span class="mv-q num<?php echo $p['qty'] <= 0 ? ' q-out' : ($p['qty'] <= $p['reorder'] ? ' q-low' : '') ?>">
          <?php echo number_format($p['qty']) ?><small><?php echo e($p['unit']) ?></small>
        </span>
      </a>
    </li>
<?php
};

$moreUrl = $mvPage . move_qs($q, $cat, $sku, $period, $mvExtra);
if ($isMore) {
    foreach ($list as $p) {
        $item($p);
    }
    Stock_renderMoreButton($end, $total, $moreUrl, 0, 'li');
    return;
}
?>
<ul class="mv-list" id="mv-list">
  <?php if (!$list) { ?>
    <li class="mv-none">ไม่พบสินค้าที่ตรงกับคำค้น</li>
  <?php } ?>
  <?php foreach ($list as $p) { $item($p); } ?>
  <?php Stock_renderMoreButton($end, $total, $moreUrl, 0, 'li'); ?>
</ul>
