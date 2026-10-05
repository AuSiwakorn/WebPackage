<?php
$__dbConfigWebsite = [];
$config_path = PATH_AOWEBDATA . '/modules/' . $options['module'] . '/config/db_' . $options['confname'] . '.php';
if (file_exists($config_path)) {
  include_once $config_path;
}

?>
<div id="responseAreaConfig"></div>
<form method="post" enctype="multipart/form-data" autocomplete="off" hx-target="#responseAreaConfig" hx-post="/admbuilder/save.php" data-skip-before="true" data-none-reload="true">
  <div class="modal-header">
    <h5 class="modal-title">แก้ไขข้อมูล</h5>
  </div>
  <div class="modal-body">
    <input type="hidden" name="ac" value="saveconfig">
    <?php
    foreach ($__dbConfigWebsite as $key => $value) {

    ?>
      <div class="row" style="padding-bottom: 20px;">
        <?php
        foreach ($value as $k => $v) {
          $col = $v['col'] ?? '12';
          $val = SiteConfig_get($k);
        ?>
          <div class="col-<?php echo $col; ?> mb-3">
            <label for="<?php echo $k; ?>" class="form-label"><?php echo $v['title']; ?> </label>
            <?php if ($v['type'] == 'text') { ?>
              <input type="text" id="<?php echo $k; ?>" name="keys[<?php echo $k; ?>]" class="form-control" value="<?php echo $val; ?>">
            <?php } ?>
          </div>
        <?php
        }
        ?>
      </div>
    <?php
    }
    ?>

  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeAowebModal();">ปิด</button>
    <button type="submit" class="btn btn-success">บันทึก</button>
  </div>
</form>