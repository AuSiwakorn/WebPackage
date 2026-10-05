<?php
$isSubOpt = $vals['isSubpost'] ?? 'รายการย่อย';
$backUrl = ((int) $articles_id > 0)
  ? '/admbuilder/load.php?ac=articlesEdit&id=' . (int) $articles_id . '&keysname=' . urlencode($keysname)
  : '/admbuilder/load.php?ac=articlesGroupEdit&id=' . (int) $group_id . '&keysname=' . urlencode($keysname);
?>
<div class="modal-header">
  <h5 class="modal-title">จัดการ<?php echo $isSubOpt; ?></h5>
</div>
<button
  type="button"
  class="btn btn-secondary mt-3 mb-3"
  hx-get="<?php echo $backUrl; ?>"
  hx-target=".ao-builder-content"
  data-size="<?php echo $size; ?>"
  hx-vals='{"data":"<?php echo $options ?>","size":"<?php echo $size ?>"}'
  hx-swap="innerHTML">
  กลับ
</button>
<div class="modal-body" id="ao_builder_body_subpost">
  <?php include __DIR__ . '/inner.articles.subpost.list.php'; ?>
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-secondary" onclick="closeAowebModal();">ปิด</button> &nbsp;
</div>
