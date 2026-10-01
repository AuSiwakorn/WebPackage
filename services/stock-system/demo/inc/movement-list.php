<?php
/* ==========================================================
   AOSTOCK DEMO — รายการสินค้าทางซ้ายของหน้าประวัติเคลื่อนไหว (htmx สลับได้)
   ต้องกำหนดก่อน include: $code $list $q $cat $sku $period
   ========================================================== */
?>
<ul class="mv-list" id="mv-list">
  <?php if (!$list): ?>
    <li class="mv-none">ไม่พบสินค้าที่ตรงกับคำค้น</li>
  <?php endif; ?>
  <?php foreach ($list as $p): ?>
    <li>
      <a class="mv-item<?= $p['sku'] === $sku ? ' on' : '' ?>"
         href="movements.php<?= e(move_qs($q, $cat, $p['sku'], $period)) ?>">
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
