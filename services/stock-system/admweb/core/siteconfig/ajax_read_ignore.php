<?php
$folder = REQ_get('folder', 'post', 'str', '');
$ignore = read_txt_json(PATH_UPLOAD . '/hash/ignore_' . $folder . '.txt');
$dirs = glob(PATH_WEB_ROOT . '/' . $folder . '/*', GLOB_ONLYDIR);
?>
<div class="panel-group accordion" id="demo-acc-info" style="margin-bottom: 0px;">
    <?php foreach ($dirs as $v) {
        $md5 = md5($v);
        $dirname = basename($v);
        $se = in_array($md5, $ignore) ? 'checked' : '';
        list($count, $count2) = getIgnoreCount($v, $ignore);
        $color = ($se === 'checked' || $count > 0) ? 'default' : 'info';
        $count = $se === 'checked' ? $count2 : $count; ?>
        <div class="panel panel-<?php echo $color; ?>">
            <div class="panel-heading" style="position: relative;">
                <h4 class="panel-title" style="margin: 0;">
                    <input type="hidden" name="dirname" value="<?php echo $folder; ?>">
                    <input <?php echo $se; ?> type="checkbox" class="checkHash-<?php echo $md5; ?>" style="width: 17px; height: 17px; position: absolute; left: 10px; top: 40%; transform: translateY(-50%);" onclick="return chackHashAll('<?php echo $md5; ?>');" name="ignore[<?php echo $md5; ?>]" value="<?php echo $md5; ?>">
                    <a data-parent="#demo-acc-info" data-toggle="collapse" href="#demo-acd-<?php echo $color; ?>-<?php echo $dirname ?>" aria-expanded="false" class="collapsed" style="display: block; padding-left: 40px;"><?php echo $dirname . ' ' . '(' . $count . '/' . $count2 . ')' ?></a>
                </h4>
            </div>
            <div class="panel-collapse collapse" id="demo-acd-<?php echo $color; ?>-<?php echo $dirname ?>" aria-expanded="false" style="height: 0px;">
                <div class="panel-body" style="margin-bottom: 0px;">
                    <?php getConfigIgnore($v, $ignore); ?>
                </div>
            </div>
        </div>
    <?php } ?>
</div>