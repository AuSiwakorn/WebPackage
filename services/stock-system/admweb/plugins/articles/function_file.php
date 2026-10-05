<?php

function PG_deleteAllArticlesPicture($articles_id)
{
	$articles_id = _input_validate_int($articles_id, true);
	$sql = "DELETE FROM " . _DBPREFIX_ . "site_articles_img WHERE articles_id = '{$articles_id}';";
	$db = DB::singleton();
	$aData = PG_getAllPictureByArticlesID($articles_id);
	if ($aData['num_rows'] > 0) {
		foreach ($aData['data'] as $v) {
			unlink(PATH_UPLOAD . '/' . $v['imgPathMini']);
			unlink(PATH_UPLOAD . '/' . $v['imgPathBig']);
		}
	}
	$db->query($sql, __FUNCTION__);
}

function PG_deleteAllArticlesFile($articles_id)
{
	$articles_id = _input_validate_int($articles_id, true);
	$aData = PG_getAllFilesByArticlesID($articles_id);
	if ($aData['num_rows'] > 0) {
		foreach ($aData['data'] as $v) {
			unlink(PATH_UPLOAD . '/' . $v['filename']);
		}
	}
	$db = DB::singleton();
	echo $sql = "DELETE FROM " . _DBPREFIX_ . "site_articles_file WHERE articles_id = '{$articles_id}';";
	$db->query($sql, __FUNCTION__);
}

function PG_update_arSort_img($id, $ctime = '')
{
	$id = _input_validate_int($id, true);
	if ($ctime != '') {
		$sql = "UPDATE " . _DBPREFIX_ . "site_articles_img SET `ctime` = '{$ctime}' WHERE file_id = '{$id}'; ";
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
	}
}

function PG_update_arSort_Group_img($id, $ctime = '')
{
	$id = _input_validate_int($id, true);
	if ($ctime != '') {
		$sql = "UPDATE " . _DBPREFIX_ . "site_articles_group_img SET `ctime` = '{$ctime}' WHERE file_id = '{$id}'; ";
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
	}
}

function PG_updateIsMarkArticlesImg($id, $ismark = 0)
{
	$id = _input_validate_int($id, true);
	$sql = "UPDATE " . _DBPREFIX_ . "site_articles_img SET `ismark` = '{$ismark}' WHERE file_id = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
}

function PG_updateIsMarkGroupImg($id, $ismark = 0)
{
	$id = _input_validate_int($id, true);
	$sql = "UPDATE " . _DBPREFIX_ . "site_articles_group_img SET `ismark` = '{$ismark}' WHERE file_id = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
}

function updateKeysnamePicture($id, $keysname = '')
{
	$id = _input_validate_int($id, true);
	$sql = "UPDATE " . _DBPREFIX_ . "site_articles_img SET keysname = '{$keysname}' WHERE articles_id = '{$id}'; ";
	$db = DB::singleton();
	$ok = $db->query($sql, __FUNCTION__);
}


function updateKeysnamePictureGroup($id, $keysname = '')
{
	$id = _input_validate_int($id, true);
	$sql = "UPDATE " . _DBPREFIX_ . "site_articles_group_img  SET keysname = '{$keysname}' WHERE group_id = '{$id}'; ";
	$db = DB::singleton();
	$ok = $db->query($sql, __FUNCTION__);
}
