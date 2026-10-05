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
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   รายการสินค้าทางซ้ายของหน้าประวัติเคลื่อนไหว (htmx สลับได้)
   ต้องกำหนดก่อน include: $code $list $q $cat $sku $period
   ใช้ร่วมกับ adm-movements.php — ตั้ง $MV_PAGE (หน้าปลายทาง) และ $MV_EXTRA (เช่น array('b' => 'RS')) ได้
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';
?>
<ul class="mv-list" id="mv-list">
  <?php if (!$list): ?>
    <li class="mv-none">ไม่พบสินค้าที่ตรงกับคำค้น</li>
  <?php endif; ?>
  <?php foreach ($list as $p): ?>
    <li>
      <a class="mv-item<?= $p['sku'] === $sku ? ' on' : '' ?>"
         href="<?= e((isset($MV_PAGE) ? $MV_PAGE : 'movements.php') . move_qs($q, $cat, $p['sku'], $period, isset($MV_EXTRA) ? $MV_EXTRA : array())) ?>">
        <?= thumb_html($p) ?>
        <span class="mv-it">
          <b><?= e($p['name']) ?></b>
          <small><?= e($p['sku']) ?></small>
        </span>
        <span class="mv-q num<?= $p['qty'] <= 0 ? ' q-out' : ($p['qty'] <= $p['reorder'] ? ' q-low' : '') ?>">
          <?= number_format($p['qty']) ?><small><?= e($p['unit']) ?></small>
        </span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>
