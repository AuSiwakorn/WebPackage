<?php 
$md5 = @$_REQUEST['md5'];
$ac = @$_REQUEST['ac'];
$filehelp = PATH_HELP . '/'.$md5.'.html'; 
if ($ac == 'save') {
	$content = @$_REQUEST['content'];
	save_help_content($filehelp, $content);
	setRaiseMsg('Help successfully updated ( ' . $md5 . ' ) .',_TIME_,0);
	CustomRedirectToUrl("popFrame.php?pg=help&inc=help&ty=plugin&md5=".$md5);
	
} elseif ($ac == 'upfile') {
	$fileExtraUpload = @$_FILES['filehelp'];
	if ($fileExtraUpload['name'] != '' && $fileExtraUpload['tmp_name'] != '') {
		$extentionAtt = strtolower(end(explode('.', $fileExtraUpload['name'])));
		//if(in_array($fileExtraUpload['type'], $aFileAllow))keeree
		if ($extentionAtt == 'html') {
			if (is_file($filehelp)) {
				unlink($filehelp);
			}
			move_uploaded_file($fileExtraUpload['tmp_name'], $filehelp);
			setRaiseMsg('Help successfully updated ( ' . $md5 . ' ) .',_TIME_,0);
		}
	}
	CustomRedirectToUrl("popFrame.php?pg=help&inc=help&ty=plugin&md5=".$md5);
}
?>
<script type="text/javascript">
	$(document).ready(function() {
		$(".button").button();
	});
</script>
<div style="background-color:#ccc;">
<table width="100%" cellpadding="7">
	<tr>
		<td align="left"><a href="popFrame.php?pg=help&inc=help&ty=plugin&md5=<?php echo $md5; ?>" class="button">กลับไปหน้าแสดงผล</a></td>
		<td align="right"><form action="" method="post" enctype="multipart/form-data"> อัพโหลดไฟล์  HELP (<?php echo $md5; ?>.html)<input type="hidden" name="ac" value="upfile" /> <input type="file" name="filehelp" /><input type="submit" /> </form></td>
	</tr>
</table>
</div>
<div style="padding:2px; font-size:12px;" align="center"><?php displayRaiseMsg(); ?></div>
<div>
<form action="" method="post">
	<input type="hidden" name="ac" value="save" />
	<textarea class="ckeditor" name="content" rows="5" style="width:100%;">
	<?php
	if (is_file($filehelp)) {
		include($filehelp);
	}
	?>
	</textarea>
	<div style="padding: 5px;" align="center">
	<input type="submit" class="button" value="UPDATE HELP CONTENT" />
	</div>
</form>
</div>
<div style="background-color:#222; padding:5px;color: #FFF;"><?php echo $filehelp; ?></div>
<?php 
$aConfig['editor_hide_upload'] = true;
$aConfig['editor_height'] = 260;
include('include/pages_inc/editor.php');
?>