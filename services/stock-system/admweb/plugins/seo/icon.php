<div class="row" style="margin-top:10px;">
    <label class="col-md-2 control-label text-right">&nbsp;</label>
    <div class="col-md-10">
        <div id="showfile_<?php echo $kLang; ?>"></div>
        <label for="file-upload_<?php echo $kLang; ?>" class="custom-file-upload btn btn-success">UPLOAD ICON</label>
        <input id="file-upload_<?php echo $kLang; ?>" type="file" id="iconview_<?php echo $kLang; ?>" name="metaIcon[<?php echo $kLang; ?>]" onchange="getUpPath(this, 'showfile_<?php echo $kLang; ?>');" />
    </div>
</div>
