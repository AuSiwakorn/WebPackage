<?php
$keysname = (@$_REQUEST['keysname'] != '') ? $_REQUEST['keysname'] : 'config';
if ($keysname == '') {
	setRaiseMsg('Cannot get keysname. ',_TIME_,1);
	CustomRedirectToUrl("index.php?module=siteconfig");
	exit;
}
$time = _TIME_;
$ac = @$_REQUEST['ac'];
$f = @$_REQUEST['f'];
$filePath = PATH_UPLOAD . '/' . $keysname;
$fileUrl = URL_UPLOAD . '/' . $keysname;
$aFilesDir = dirList($filePath);

if ($ac == 'add' && $keysname != '') {
	if (count($_FILES) > 0) {
		foreach ($_FILES as $k => $v) {
			if ($v['name'] != '') {
				$extention = strtolower(end(explode('.', $v['name'])));
				//if(in_array($v['type'], $aFileAllow))keeree
				if (in_array($extention, array('pdf', 'doc', 'jpg', 'gif', 'png'))) {
					$filename = $time.'.'.$extention;
					$filePath = PATH_UPLOAD . '/' . $keysname . '/' . $filename;
					if (!is_dir(PATH_UPLOAD.'/'.$keysname)) {
						@mkdir(PATH_UPLOAD.'/'.$keysname, 0777, true);
					}
					@move_uploaded_file($v['tmp_name'], $filePath);
					$time++;
				}
			}
		}
	}
	setRaiseMsg('FILES UPDATED.',_TIME_,0);
	CustomRedirectToUrl("index.php?module=siteconfig&mp=albumfile&keysname=".$keysname);
	exit;
} elseif ($ac == 'delete' && $f != '') {
	$filepathUn = $filePath.'/'.$f;
	if (is_file($filepathUn) && file_exists($filepathUn)) {
		unlink($filepathUn);
	}
	setRaiseMsg('FILES UPDATED.',_TIME_,0);
	CustomRedirectToUrl("index.php?module=siteconfig&mp=albumfile&keysname=".$keysname);
	exit;
}

?>
<script type="text/javascript">
	$(function()
			{
		$(".submit, .button").button();
	});
</script>
<h1>Upload File </h1>
<div class="ui-widget-content" style="padding:10px;border-radius:10px;-khtml-border-radius:10px;-moz-border-radius:10px;">
<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">
<input name="ac" type="hidden" value="add" />
<table width="100%" border="0" cellspacing="0" cellpadding="5">
  <tr>
    <td align="left">FILE1 : 
      <input type="file" name="file1" /></td>
    <td align="left">FILE3 : 
      <input type="file" name="file3" /></td>
    </tr>
  <tr>
    <td align="left">FILE2 : 
      <input type="file" name="file2" /></td>
    <td align="left">FILE4 : 
      <input type="file" name="file4" /></td>
    </tr>
	<tr>
    <td colspan="2" align="left"><hr /></td>
    </tr>
  <tr>
    <td align="left"><input type="submit" name="Submit" value="Upload All Files"  class="button"/></td>
    <td align="left">&nbsp;</td>
  </tr>
</table>
</form>
</div>
<div style="height:10px;"></div>
<div class="ui-widget-content" style="padding:10px;border-radius:10px;-khtml-border-radius:10px;-moz-border-radius:10px;">
<table width="100%" border="0" cellspacing="0" cellpadding="3">
<?php 
if (count($aFilesDir) > 0 && is_array($aFilesDir)) {
	$i = 0;
	foreach ($aFilesDir as $k => $v) {
		$i++;
?>
  <tr>
    <td width="50" align="center"><?php echo $i; ?></td>
    <td align="left"><a href="<?php echo $fileUrl.'/'.$v['name']; ?>" target="_blank" title="<?php echo $v['time']; ?>"><?php echo $v['name']; ?></a></td>
	<td width="100" align="center"><?php echo displaySize($v['size']); ?></td>
	<td width="70" align="center"><a href="<?php echo $fileUrl.'/'.$v['name']; ?>" target="_blank">Download</a></td>
    <td width="70" align="center"><a href="index.php?module=siteconfig&mp=albumfile&keysname=bgweb&ac=delete&f=<?php echo $v['name']; ?>" onclick="return confirm('Delete ?');">Delete</a></td>
  </tr>
 <?php
 	}
}
 ?>
</table>
</div>