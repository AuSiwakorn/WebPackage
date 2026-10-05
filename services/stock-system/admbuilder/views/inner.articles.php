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
// หมวดหมู่ (คู่กับ option isGroup ของ AOSET) — ถ้าไม่เปิด option นี้ หน้าตา/พฤติกรรมเดิมทุกอย่าง
$isGroupOpt = $vals['isGroup'] ?? '';
$gid = isset($gid) ? (int)$gid : 0;
$aGroupName = [];
if ($isGroupOpt != '' && function_exists('plugin_getAllGroup')) {
  $aGroupsAll = plugin_getAllGroup($keysname, 0, 0);
  foreach (($aGroupsAll['data'] ?? []) as $g) {
    $aGroupName[$g['group_id']] = $g['group_name'];
  }
}
?>
<div class="modal-header">
  <h5 class="modal-title">
    ข้อมูลบทความ<?php echo ($isGroupOpt != '' && $gid > 0 && isset($aGroupName[$gid])) ? ' — ' . $isGroupOpt . ': ' . $aGroupName[$gid] : ''; ?>
  </h5>
</div>
<div class="modal-body" id="ao_builder_body_articles">
  <div class="d-flex flex-wrap align-items-center" style="gap:8px;">
    <?php if ($isGroupOpt != '') { ?>
      <button
        type="button"
        class="btn btn-secondary"
        hx-get="/admbuilder/load.php?ac=articlesGroup&keysname=<?php echo $keysname; ?>"
        hx-target=".ao-builder-content"
        data-size="<?php echo $size; ?>"
        hx-vals='{"data":"<?php echo $options ?>","size":"<?php echo $size ?>"}'
        hx-swap="innerHTML">
        กลับ
      </button>
    <?php } ?>
    <button
      type="button"
      class="btn btn-primary"
      hx-get="/admbuilder/load.php?ac=articles&inner=form&keysname=<?php echo $keysname; ?>&gid=<?php echo $gid; ?>"
      hx-target=".ao-builder-content"
      data-size="<?php echo $size; ?>"
      hx-vals='{"data":"<?php echo $options ?>","size":"<?php echo $size ?>"}'
      hx-swap="innerHTML">
      เพิ่มข้อมูล
    </button>
    <?php if ($isGroupOpt != '') { ?>
      <select
        name="gid"
        class="form-select form-select-sm w-auto ms-auto"
        title="กรองตาม<?php echo $isGroupOpt; ?>"
        hx-get="/admbuilder/load.php?ac=articles&keysname=<?php echo $keysname; ?>"
        hx-target=".ao-builder-content"
        hx-trigger="change"
        data-size="<?php echo $size; ?>"
        hx-vals='{"data":"<?php echo $options ?>","size":"<?php echo $size ?>"}'
        hx-swap="innerHTML">
        <option value="0"><?php echo $isGroupOpt; ?>: ทั้งหมด</option>
        <?php foreach ($aGroupName as $gk => $gv) { ?>
          <option value="<?php echo $gk; ?>" <?php echo ($gid == $gk) ? 'selected' : ''; ?>><?php echo $gv; ?></option>
        <?php } ?>
      </select>
    <?php } ?>
  </div>
  <div style="overflow-y: auto; max-height: 500px;">
    <div class="table-responsive mt-3">
      <?php
      //pre($vals);
      $aData = ($isGroupOpt != '' && $gid > 0)
        ? plugin_articles_getAllByGroup($keysname, $gid, 0, 0)
        : plugin_articles_getAll($keysname, 0, 0);
      if (!empty($aData['data'])) {
      ?>
        <table class="table table-striped table-vcenter" id="ao-table-sortable" style="border-collapse: collapse;">
          <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
            <tr>
              <th class="text-center" width="110">ลำดับ</th>
              <th class="text-center" width="40">รหัส</th>
              <?php if ($vals['isIcon'] != '') { ?>
                <th class="text-center" width="80">ICON</th>
              <?php } ?>
              <th>ชื่อหัวข้อ</th>
              <?php if ($isGroupOpt != '' && $gid == 0) { ?>
                <th class="text-center" width="140"><?php echo $isGroupOpt; ?></th>
              <?php } ?>
              <th class="text-center" width="100">วันที่เพิ่ม</th>
              <th class="text-center" width="100"></th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sortPos = 0;
            foreach ($aData['data'] as $k => $v) {
              $sortPos++; ?>
              <tr data-id="<?php echo $v['articles_id']; ?>">
                <td class="text-center">
                  <div class="d-flex align-items-center justify-content-center" style="gap:6px;">
                    <i class="fas fa-grip-vertical ao-drag-handle" title="ลากเพื่อจัดลำดับ"></i>
                    <input type="number" class="form-control form-control-sm no-spin ao-sort-input" value="<?php echo $sortPos; ?>" min="1" title="พิมพ์เลขลำดับ">
                  </div>
                </td>
                <td class="text-center"><?php echo $v['articles_id']; ?></td>
                <?php if ($vals['isIcon'] != '') { ?>
                  <td class="text-center"><?php echo ($v['icon']) ? '<img src="' . URL_UPLOAD . '/' . $v['icon'] . '" width="80">' : '-'; ?></td>
                <?php } ?>
                <td class="text-left"><?php echo $v['title']; ?></td>
                <?php if ($isGroupOpt != '' && $gid == 0) { ?>
                  <td class="text-center"><?php echo $aGroupName[$v['group_id']] ?? '-'; ?></td>
                <?php } ?>
                <td class="text-center"><?php echo date('d/m/Y', $v['add_time']); ?></td>
                <td class="text-center">
                  <a
                    type="button"
                    hx-get="/admbuilder/load.php?ac=articlesEdit&inner=form&id=<?php echo $v['articles_id']; ?>&keysname=<?php echo $keysname; ?>&gid=<?php echo $gid; ?>"
                    hx-target=".ao-builder-content"
                    data-size="<?php echo $size; ?>"
                    hx-vals='{"data":"<?php echo $options ?>","size":"<?php echo $size ?>"}'
                    hx-swap="innerHTML">
                    <i class="far fa-edit"></i>
                  </a> &nbsp;
                  <a
                    type="button"
                    hx-post="/admbuilder/save.articles.php?ac=delete&id=<?php echo $v['articles_id']; ?>&keysname=<?php echo $keysname; ?>"
                    hx-confirm="ยืนยันการลบข้อมูลนี้"
                    data-skip-before="true">
                    <i class="fas fa-trash-alt"></i>
                  </a>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      <?php } ?>
      <?php if (empty($aData['data'])) { ?>
        <div style="padding: 30px;margin: 10px;border: 1px #eee solid;" class="text-center text-muted fw-bold">ยังไม่มีข้อมูล</div>
      <?php } ?>
    </div>
  </div>
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-secondary" onclick="closeAowebModal();">ปิด</button> &nbsp;
</div>