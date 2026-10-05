<form method="post" enctype="multipart/form-data" hx-encoding="multipart/form-data" autocomplete="off" hx-post="/admbuilder/save.php" data-skip-before="true">
  <div class="modal-header">
    <h5 class="modal-title">แก้ไขข้อมูล</h5>
  </div>
  <div class="modal-body">
    <input type="hidden" name="ac" value="savebox">
    <input type="hidden" name="keysname" value="<?php echo $keysname; ?>">
    <?php if ($isTitle != ''): ?>
      <div class="col-12 mb-3">
        <label for="title" class="form-label"><?php echo $isTitle; ?> </label>
        <input type="text" id="title" name="title" class="form-control" value="<?php echo $_title; ?>">
      </div>
    <?php endif; ?>
    <?php if ($isMiniBox != ''): ?>
      <div class="col-12 mb-3">
        <label for="minibox" class="form-label"><?php echo $isMiniBox; ?></label>
        <div class="form-control click-edit-minibox border" style="cursor:pointer; min-height: 200px;padding: 15px;overflow-y: auto;" data-target="#minibox"><?php echo $_minibox; ?></div>
        <textarea id="minibox" name="minibox[]" style="display: none;"><?php echo htmlspecialchars($_minibox); ?></textarea>
      </div>
    <?php endif; ?>
    <?php if ($isDesc != ''): ?>
      <div class="col-12 mb-3">
        <label for="desc" class="form-label"><?php echo $isDesc; ?> </label>
        <textarea name="desc" id="desc" class="form-control" style="height: 100px !important;"><?php echo $_desc; ?></textarea>
      </div>
    <?php endif; ?>
    <?php if ($isLink != ''): ?>
      <div class="col-12 mb-3">
        <label for="link" class="form-label"><?php echo $isLink; ?></label>
        <input type="text" id="link" name="link" class="form-control" value="<?php echo $_link; ?>">
      </div>
    <?php endif; ?>
    <?php if ($isImg != ''): ?>
      <div class="col-12 mb-3">
        <label for="img" class="form-label"><?php echo $isImg; ?></label>
        <input type="file" id="img" name="img" class="form-control">
        <?php if ($_img != ''): ?>
          <div class="text-center mt-2">
            <a href="<?php echo URL_UPLOAD . '/' . $_img; ?>" target=" _blank" data-allow-link="true">
              <img src="<?php echo URL_UPLOAD . '/' . $_img; ?>" width="20%" height="20%"></img>
            </a>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeAowebModal();">ปิด</button>
    <button type="submit" class="btn btn-success">บันทึก</button>
  </div>
</form>