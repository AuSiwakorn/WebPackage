<?php
function PG_getAllPictureByArticlesID($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_img WHERE articles_id = '{$id}' ORDER BY ismark DESC, ctime ASC; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aData = array();
	$aData['data'] = array();
	while ($db->next_record()) {
		$aData['data'][] = $db->allRows();
	}
	$aData['num_rows'] = $db->num_rows();
	return $aData;
}

function PG_getSortPictureByArticlesID($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT max(ctime) as sort FROM " . _DBPREFIX_ . "site_articles_img WHERE articles_id = '{$id}'";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aData = array();
	while ($db->next_record()) {
		$aData = $db->allRows();
	}
	return $aData;
}
////////////////// sort GroupPicture By AjarnFifa //////////////////////////
function PG_getSortGroupPictureByGroupID($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT max(ctime) as sort FROM " . _DBPREFIX_ . "site_articles_group_img WHERE group_id = '{$id}'";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aData = array();
	while ($db->next_record()) {
		$aData = $db->allRows();
	}
	return $aData;
}
////////////////// sort file By AjarnFifa //////////////////////////
function PG_getSortFileByArticlesID($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT max(ctime) as sort FROM " . _DBPREFIX_ . "site_articles_file WHERE articles_id = '{$id}'";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aData = array();
	while ($db->next_record()) {
		$aData = $db->allRows();
	}
	return $aData;
}

function PG_getAllFilesByArticlesID($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT *
			FROM " . _DBPREFIX_ . "site_articles_file
			WHERE articles_id = '{$id}'
			ORDER BY ctime ASC;
			";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aData = array();
	$aData['data'] = array();
	while ($db->next_record()) {
		$aData['data'][] = $db->allRows();
	}
	$aData['num_rows'] = $db->num_rows();
	return $aData;
}

function PG_addArticlesPicture($id, $pathMini = '', $pathBig = '', $title = '', $detail = '', $detail2 = '', $keysname = '')
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////
	$sql = "INSERT INTO " . _DBPREFIX_ . "site_articles_img (
	`file_id` ,
	`articles_id` ,
	`imgPathMini` ,
	`imgPathBig` ,
	`ctime`,
	`title`,
	`detail`,
    `detail2`,
	`keysname`,
	`ismark`
	) VALUES (
	NULL , '{$id}', '{$pathMini}', '{$pathBig}', '" . _TIME_ . "', '{$title}', '{$detail}', '{$detail2}','{$keysname}', '0'
	);";

	$db = DB::singleton();
	$ok = $db->query($sql, __FUNCTION__);
	return ($ok) ? $db->getInsertID() : false;
}

function PG_addArticlesPicture2($counter, $id, $pathMini = '', $pathBig = '', $title = '', $detail = '', $detail2 = '', $keysname = '')
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////
	$sql = "INSERT INTO " . _DBPREFIX_ . "site_articles_img (
	`file_id` ,
	`articles_id` ,
	`imgPathMini` ,
	`imgPathBig` ,
	`ctime`,
	`title`,
	`detail`,
    `detail2`,
	`keysname`,
	`ismark`
	) VALUES (
	NULL , '{$id}', '{$pathMini}', '{$pathBig}', '{$counter}', '{$title}', '{$detail}', '{$detail2}','{$keysname}', '0'
	);";

	$db = DB::singleton();
	$ok = $db->query($sql, __FUNCTION__);
	return ($ok) ? $db->getInsertID() : false;
}

function PG_getAllPicByArticlesID($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT *
			FROM " . _DBPREFIX_ . "site_articles_file
			WHERE articles_id = '{$id}'
			ORDER BY ctime ASC;
			";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aData = array();
	$aData['data'] = array();
	while ($db->next_record()) {
		$aData['data'][] = $db->allRows();
	}
	$aData['num_rows'] = $db->num_rows();
	return $aData;
}

function PG_addArticlesFiles($id, $filename = '', $detail = '', $detail_en = '', $keysname = '', $counterSortFile = '')
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////
	$sql = "INSERT INTO " . _DBPREFIX_ . "site_articles_file (
		`file_id` ,
		`articles_id` ,
		`filename` ,
		`ctime`,
		`detail`,
		`detail_en`,
		`keysname`
		) VALUES (
		NULL , '{$id}', '{$filename}', '{$counterSortFile}', '{$detail}','{$detail_en}','{$keysname}'
		);";

	$db = DB::singleton();
	$ok = $db->query($sql, __FUNCTION__);
	return ($ok) ? $db->getInsertID() : false;
}

function PG_deleteArticlesPictureId($file_id)
{
	////////////// Validate /////////////
	$file_id = _input_validate_int($file_id, true);
	/////////// End Validate ///////////

	$aData = PG_getArticlesPictureByID($file_id);
	if (is_file(PATH_UPLOAD . '/' . $aData['imgPathMini'])) {
		unlink(PATH_UPLOAD . '/' . $aData['imgPathMini']);
	}

	if (is_file(PATH_UPLOAD . '/' . $aData['imgPathBig'])) {
		unlink(PATH_UPLOAD . '/' . $aData['imgPathBig']);
	}

	$sql = "DELETE FROM " . _DBPREFIX_ . "site_articles_img WHERE file_id = '{$file_id}';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return $aData['imgPathMini'];
}

function PG_getArticlesPictureByID($file_id)
{
	////////////// Validate /////////////
	$file_id = _input_validate_int($file_id, true);
	/////////// End Validate ///////////

	$sql = "SELECT * FROM `" . _DBPREFIX_ . "site_articles_img` WHERE file_id = '{$file_id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->allRows();
}

function PG_getArticlesFileByID($file_id)
{
	////////////// Validate /////////////
	$file_id = _input_validate_int($file_id, true);
	/////////// End Validate ///////////

	$sql = "SELECT * FROM `" . _DBPREFIX_ . "site_articles_file` WHERE file_id = '{$file_id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->allRows();
}

function PG_deleteArticlesFileId($file_id)
{
	////////////// Validate /////////////
	$file_id = _input_validate_int($file_id, true);
	/////////// End Validate ///////////

	$aData = PG_getArticlesFileByID($file_id);
	if (is_file(PATH_UPLOAD . '/' . $aData['filename'])) {
		unlink(PATH_UPLOAD . '/' . $aData['filename']);
	}

	$sql = "DELETE FROM " . _DBPREFIX_ . "site_articles_file WHERE file_id = '{$file_id}';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return $file_id . '-' . $aData['filename'];
}

function PG_update_allData_img($id, $arData = array())
{
	$id = _input_validate_int($id, true);
	$sql = "UPDATE " . _DBPREFIX_ . "site_articles_img 
		SET `title` = '{$arData['title']}',
		`detail` = '{$arData['detail']}',
		`detail2` = '{$arData['detail2']}'
		WHERE file_id = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
}

function PG_update_allData_Group_img($id, $arData = array())
{
	$id = _input_validate_int($id, true);
	$sql = "UPDATE " . _DBPREFIX_ . "site_articles_group_img
		SET `title` = '{$arData['title']}',
		`detail` = '{$arData['detail']}',
		`detail2` = '{$arData['detail2']}'
		WHERE file_id = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
}

function PG_updateDetailFileAttach($id, $detail = '', $detail_en = '', $ctime = '')
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "UPDATE " . _DBPREFIX_ . "site_articles_file 
		SET `detail` = '{$detail}' 	,
		`detail_en` = '{$detail_en}', 
		`ctime` = '{$ctime}'
		WHERE file_id = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
}


function PG_addArticlesPictureGroup($id, $counterSort, $pathMini = '', $pathBig = '', $title = '', $detail = '', $detail2 = '', $keysname = '')
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////
	$sql = "INSERT INTO " . _DBPREFIX_ . "site_articles_group_img (
	`file_id` ,
	`group_id` ,
	`imgPathMini` ,
	`imgPathBig` ,
	`ctime`,
	`title`,
	`detail`,
	`detail2`,
	`keysname`,
	`ismark`
	) VALUES (
	NULL , '{$id}', '{$pathMini}', '{$pathBig}', '{$counterSort}','{$title}','{$detail}','{$detail2}','{$keysname}', '0'
	);";

	$db = DB::singleton();
	$ok = $db->query($sql, __FUNCTION__);
	return ($ok) ? $db->getInsertID() : false;
}

function PG_getAllPictureGroupByArticlesID($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT *FROM " . _DBPREFIX_ . "site_articles_group_img WHERE group_id = '{$id}'	ORDER BY ismark DESC, ctime ASC; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aData = array();
	$aData['data'] = array();
	while ($db->next_record()) {
		$aData['data'][] = $db->allRows();
	}
	$aData['num_rows'] = $db->num_rows();
	return $aData;
}

function PG_deleteArticlesPictureGroupId($file_id)
{
	////////////// Validate /////////////
	$file_id = _input_validate_int($file_id, true);
	/////////// End Validate ///////////

	$aData = PG_getAllPictureGroupByArticlesID($file_id);
	if (is_file(PATH_UPLOAD . '/' . $aData['imgPathMini'])) {
		unlink(PATH_UPLOAD . '/' . $aData['imgPathMini']);
	}

	if (is_file(PATH_UPLOAD . '/' . $aData['imgPathBig'])) {
		unlink(PATH_UPLOAD . '/' . $aData['imgPathBig']);
	}

	$sql = "DELETE FROM " . _DBPREFIX_ . "site_articles_group_img WHERE file_id = '{$file_id}';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return $aData['imgPathMini'];
}

function PG_getArticlesPictureGroupByID($file_id)
{
	////////////// Validate /////////////
	$file_id = _input_validate_int($file_id, true);
	/////////// End Validate ///////////

	$sql = "SELECT * FROM `" . _DBPREFIX_ . "site_articles_group_img` WHERE file_id = '{$file_id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->allRows();
}

function PG_deletePictureGroupId($file_id)
{
	////////////// Validate /////////////
	$file_id = _input_validate_int($file_id, true);
	/////////// End Validate ///////////

	$aData = PG_getArticlesPictureGroupByID($file_id);
	if (is_file(PATH_UPLOAD . '/' . $aData['imgPathMini'])) {
		unlink(PATH_UPLOAD . '/' . $aData['imgPathMini']);
	}

	if (is_file(PATH_UPLOAD . '/' . $aData['imgPathBig'])) {
		unlink(PATH_UPLOAD . '/' . $aData['imgPathBig']);
	}

	$sql = "DELETE FROM " . _DBPREFIX_ . "site_articles_group_img WHERE file_id = '{$file_id}';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return $aData['imgPathMini'];
}
