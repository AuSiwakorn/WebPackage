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
$isGroupOpt = $vals['isGroup'] ?? 'หมวดหมู่';
$isGroupIcon = $vals['isGroupIcon'] ?? '';
$isGroupImg = $vals['isGroupImg'] ?? '';
?>
<div class="modal-header">
  <h5 class="modal-title">จัดการ<?php echo $isGroupOpt; ?></h5>
</div>
<div class="modal-body" id="ao_builder_body_articles_group">
  <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
    <button
      type="button"
      class="btn btn-primary"
      hx-get="/admbuilder/load.php?ac=articlesGroupEdit&keysname=<?php echo $keysname; ?>"
      hx-target=".ao-builder-content"
      data-size="<?php echo $size; ?>"
      hx-vals='{"data":"<?php echo $options ?>","size":"<?php echo $size ?>"}'
      hx-swap="innerHTML">
      เพิ่ม<?php echo $isGroupOpt; ?>
    </button>
    <button
      type="button"
      class="btn btn-outline-secondary"
      hx-get="/admbuilder/load.php?ac=articles&keysname=<?php echo $keysname; ?>"
      hx-target=".ao-builder-content"
      data-size="<?php echo $size; ?>"
      hx-vals='{"data":"<?php echo $options ?>","size":"<?php echo $size ?>"}'
      hx-swap="innerHTML">
      ดูเนื้อหาทั้งหมด
    </button>
  </div>
  <div style="overflow-y: auto; max-height: 500px;">
    <div class="table-responsive mt-3">
      <?php
      $aGroups = function_exists('plugin_getAllGroup') ? plugin_getAllGroup($keysname, 0, 0) : ['data' => []];
      if (!empty($aGroups['data'])) {
      ?>
        <table class="table table-striped table-vcenter" id="ao-table-sortable" data-sort-url="/admbuilder/save.php?ac=sortgroups" style="border-collapse: collapse;">
          <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
            <tr>
              <th class="text-center" width="110">ลำดับ</th>
              <th class="text-center" width="40">รหัส</th>
              <?php if ($isGroupIcon != '') { ?>
                <th class="text-center" width="80">ICON</th>
              <?php } ?>
              <?php if ($isGroupImg != '') { ?>
                <th class="text-center" width="80">รูป</th>
              <?php } ?>
              <th>ชื่อ<?php echo $isGroupOpt; ?></th>
              <th class="text-center" width="130">เนื้อหา</th>
              <th class="text-center" width="100"></th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sortPos = 0;
            foreach ($aGroups['data'] as $k => $v) {
              $sortPos++;
              $aInGroup = DB_LIST('site_articles', ['group_id' => $v['group_id'], 'keysname' => $keysname]);
              $numInGroup = (int)($aInGroup['num_rows'] ?? 0);
            ?>
              <tr data-id="<?php echo $v['group_id']; ?>">
                <td class="text-center">
                  <div class="d-flex align-items-center justify-content-center" style="gap:6px;">
                    <i class="fas fa-grip-vertical ao-drag-handle" title="ลากเพื่อจัดลำดับ"></i>
                    <input type="number" class="form-control form-control-sm no-spin ao-sort-input" value="<?php echo $sortPos; ?>" min="1" title="พิมพ์เลขลำดับ">
                  </div>
                </td>
                <td class="text-center"><?php echo $v['group_id']; ?></td>
                <?php if ($isGroupIcon != '') { ?>
                  <td class="text-center"><?php echo ($v['icon']) ? '<img src="' . URL_UPLOAD . '/' . $v['icon'] . '" width="80">' : '-'; ?></td>
                <?php } ?>
                <?php if ($isGroupImg != '') { ?>
                  <td class="text-center"><?php echo ($v['img']) ? '<img src="' . URL_UPLOAD . '/' . $v['img'] . '" width="80">' : '-'; ?></td>
                <?php } ?>
                <td class="text-left"><?php echo ($v['group_name'] != '') ? $v['group_name'] : '- - No title - -'; ?></td>
                <td class="text-center">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    hx-get="/admbuilder/load.php?ac=articles&keysname=<?php echo $keysname; ?>&gid=<?php echo $v['group_id']; ?>"
                    hx-target=".ao-builder-content"
                    data-size="<?php echo $size; ?>"
                    hx-vals='{"data":"<?php echo $options ?>","size":"<?php echo $size ?>"}'
                    hx-swap="innerHTML">
                    <i class="far fa-eye"></i> ดูเนื้อหา (<?php echo $numInGroup; ?>)
                  </button>
                </td>
                <td class="text-center">
                  <a
                    type="button"
                    hx-get="/admbuilder/load.php?ac=articlesGroupEdit&id=<?php echo $v['group_id']; ?>&keysname=<?php echo $keysname; ?>"
                    hx-target=".ao-builder-content"
                    data-size="<?php echo $size; ?>"
                    hx-vals='{"data":"<?php echo $options ?>","size":"<?php echo $size ?>"}'
                    hx-swap="innerHTML">
                    <i class="far fa-edit"></i>
                  </a> &nbsp;
                  <a
                    type="button"
                    hx-post="/admbuilder/save.articles.php?ac=groupDelete&id=<?php echo $v['group_id']; ?>&keysname=<?php echo $keysname; ?>"
                    hx-confirm="ยืนยันการลบ<?php echo $isGroupOpt; ?>นี้"
                    data-skip-before="true">
                    <i class="fas fa-trash-alt"></i>
                  </a>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      <?php } ?>
      <?php if (empty($aGroups['data'])) { ?>
        <div style="padding: 30px;margin: 10px;border: 1px #eee solid;" class="text-center text-muted fw-bold">ยังไม่มีข้อมูล</div>
      <?php } ?>
    </div>
  </div>
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-secondary" onclick="closeAowebModal();">ปิด</button> &nbsp;
</div>
