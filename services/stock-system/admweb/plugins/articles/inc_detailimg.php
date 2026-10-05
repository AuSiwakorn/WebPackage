<?php
$ac 		= REQ_get('ac', 'request', 'str', '');
$id 		= REQ_get('id', 'request', 'int', '');
$keytext 	= REQ_get('keysname', 'request', 'str', '');


///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array($ac, array('edit')) && $isSecurMode == 1) {
	setRaiseMsg('Secur Mode.', _TIME_, 0);
	CustomRedirectToUrl("doAjax.php?ty=plugin&module=" . $module . "&mp=" . $mp . "&inc=detailimg&id=" . $id);
	exit;
}
///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////

if ($ac == 'edit') {
	$detailimg = REQ_get('detailimg', 'request', 'str', '');
	$detailimg2 = REQ_get('detailimg2', 'request', 'str', '');
	PG_updateDetailFileAttach($id, $detailimg, $detailimg2);

	setRaiseMsg('Updated.', _TIME_, 0);
	CustomRedirectToUrl("doAjax.php?ty=plugin&module=" . $module . "&mp=" . $mp . "&inc=detailimg&id=" . $id);
	exit;
}

$aDetail = PG_getArticlesPictureByID($id);
displayRaiseMsg();


$text = ($keytext == 'products2') ? 'รายละเอียด (ภาษาไทย)' : 'รายละเอียดรูปภาพ';
$text2 = ($keytext == 'products2') ? 'รายละเอียด (ภาษาอังกฤษ)' : 'รายละเอียดรูปภาพ เพิ่มเติม';

?>
<form id="form1" name="form1" method="post" action="">
	<input type="hidden" name="ac" value="edit" />
	<input type="hidden" name="id" value="<?php echo $id; ?>" />
	<span style="font-size: 15px;"><?php echo @$text; ?></span><br />
	<textarea name="detailimg" class="ckeditor"><?php echo $aDetail['detail']; ?></textarea><br />
	<span style="font-size: 15px;"><?php echo @$text2; ?></span><br />
	<textarea name="detailimg2" class="ckeditor"><?php echo $aDetail['detail2']; ?></textarea><br />
	<input type="submit" value="อัพเดดข้อมูล">
</form>
<?php
$aConfig['editor_height'] = '230';
include('include/pages_inc/editor.php');
?>