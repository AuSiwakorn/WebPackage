<?php
$ac = REQ_get('ac', 'request', 'str', '');
$id = REQ_get('id', 'request', 'int', '');
///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array($ac, array('del', 'update_img')) && $isSecurMode == 1) {
	exit;
}
///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////
if ($ac == 'del' && $id != '') {
	$imgdel = PG_deleteArticlesPictureId($id);
	echo '<span style="color:#006600;">ลบรูปภาพเรียบร้อยแล้ว (' . $imgdel . ')</span>';
} elseif ($ac == 'delfile' && $id != '') {
	$imgdel = PG_deleteArticlesFileId($id);
	echo '<span style="color:#006600;">ลบไฟล์เรียบร้อยแล้ว (' . $imgdel . ')</span>';
} elseif ($ac == 'delimggroup' && $id != '') {
	$imgdel = 	PG_deletePictureGroupId($id);
	echo '<span style="color:#006600;">ลบรูปภาพเรียบร้อยแล้ว (' . $imgdel . ')</span>';
}
