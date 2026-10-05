<style>
  .no-spin::-webkit-inner-spin-button,
  .no-spin::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
  }

  .no-spin {
    -moz-appearance: textfield;
    appearance: textfield;
  }

  .ao-drag-handle { cursor: grab; color: #9aa0a6; }
  .ao-drag-handle:active { cursor: grabbing; }
  .ao-dragging { opacity: .5; background-color: #a4e7ba; }
  .ao-sort-input { width: 52px; text-align: center; padding: 2px 4px; }
</style>
<?php
// fragment list subpost — ฝังได้ทุกฟอร์มแม่ ต้องกำหนด: $keysname, $group_id, $articles_id, $vals, $options, $size
$isSubOpt = $vals['isSubpost'] ?? 'รายการย่อย';
$subParentQs = 'keysname=' . urlencode($keysname) . '&group_id=' . (int) $group_id . '&articles_id=' . (int) $articles_id;
$aSubs = function_exists('plugin_getAllSubpost') ? plugin_getAllSubpost($group_id, $articles_id, 0, 0) : ['data' => []];
?>
<div class="card mt-3">
  <div class="card-body">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2" style="gap:8px;">
      <label class="form-label mb-0"><?php echo $isSubOpt; ?></label>
      <button
        type="button"
        class="btn btn-sm btn-primary"
        hx-get="/admbuilder/load.php?ac=subpostEdit&<?php echo $subParentQs; ?>"
        hx-target=".ao-builder-content"
        data-size="<?php echo $size; ?>"
        hx-vals='{"data":"<?php echo $options ?>","size":"<?php echo $size ?>"}'
        hx-swap="innerHTML">
        <i class="fas fa-plus"></i> เพิ่ม
      </button>
    </div>
    <div style="overflow-y: auto; max-height: 400px;">
      <div class="table-responsive">
        <?php if (!empty($aSubs['data'])) { ?>
          <table class="table table-striped table-vcenter" id="ao-table-sortable" data-sort-url="/admbuilder/save.php?ac=sortsubpost" style="border-collapse: collapse;">
            <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
              <tr>
                <th class="text-center" width="100">ลำดับ</th>
                <th class="text-center" width="70">รูป</th>
                <th>หัวข้อ</th>
                <th class="text-center" width="80"></th>
              </tr>
            </thead>
            <tbody>
              <?php
              $sortPos = 0;
              foreach ($aSubs['data'] as $v) {
                $sortPos++;
              ?>
                <tr data-id="<?php echo $v['subpost_id']; ?>">
                  <td class="text-center">
                    <div class="d-flex align-items-center justify-content-center" style="gap:6px;">
                      <i class="fas fa-grip-vertical ao-drag-handle" title="ลากเพื่อจัดลำดับ"></i>
                      <input type="number" class="form-control form-control-sm no-spin ao-sort-input" value="<?php echo $sortPos; ?>" min="1" title="พิมพ์เลขลำดับ">
                    </div>
                  </td>
                  <td class="text-center"><?php echo ($v['subIcon']) ? '<img src="' . URL_UPLOAD . '/' . $v['subIcon'] . '" width="50">' : '-'; ?></td>
                  <td class="text-left"><?php echo ($v['subtitle'] != '') ? htmlspecialchars($v['subtitle']) : '- - ไม่มีหัวข้อ - -'; ?></td>
                  <td class="text-center">
                    <a
                      type="button"
                      hx-get="/admbuilder/load.php?ac=subpostEdit&id=<?php echo $v['subpost_id']; ?>&<?php echo $subParentQs; ?>"
                      hx-target=".ao-builder-content"
                      data-size="<?php echo $size; ?>"
                      hx-vals='{"data":"<?php echo $options ?>","size":"<?php echo $size ?>"}'
                      hx-swap="innerHTML">
                      <i class="far fa-edit"></i>
                    </a> &nbsp;
                    <a
                      type="button"
                      hx-post="/admbuilder/save.articles.php?ac=subpostDelete&id=<?php echo $v['subpost_id']; ?>"
                      hx-confirm="ยืนยันการลบรายการนี้"
                      data-skip-before="true">
                      <i class="fas fa-trash-alt"></i>
                    </a>
                  </td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        <?php } ?>
        <?php if (empty($aSubs['data'])) { ?>
          <div style="padding: 20px;border: 1px #eee solid;" class="text-center text-muted">ยังไม่มีข้อมูล</div>
        <?php } ?>
      </div>
    </div>
  </div>
</div>
