<?php

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_updateTypeKeysSort($id, $sort = 0)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	$sql = "UPDATE " . _DBPREFIX_ . "site_articles_keys SET `ksort` = '{$sort}' WHERE kid = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_deleteType($kid)
{
	////////////// Validate /////////////
	$kid = _input_validate_int($kid, true);
	$db = DB::singleton();
	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_keys WHERE kid = '{$kid}';";
	$db->query($sql, __FUNCTION__);
	if ($db->next_record()) {
		PG_unlinkFile($db->f('kicon'));
	}
	$sql = "DELETE FROM " . _DBPREFIX_ . "site_articles_keys WHERE kid = '{$kid}';";
	$db->query($sql, __FUNCTION__);
}

/* ========================= */
///////////////////////////////
/* ========================= */
function PG_addArticleKeyType($id, $akid)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	$db = DB::singleton();
	$aSql = array();
	$sql = "INSERT INTO " . _DBPREFIX_ . "site_articles_keysuse (`kuid` ,`kid` ,`articles_id`) VALUES ";
	foreach ($akid as $k => $kid) {
		$aSql[] = "(NULL , '{$kid}', '{$id}')";
	}
	$sql .= implode(',', $aSql);
	$sql .= ";";
	$db->query($sql, __FUNCTION__);
}

/* ========================= */
///////////////////////////////
/* ========================= */
function PG_deleteArticleKeyUseType($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	$sql = "DELETE FROM " . _DBPREFIX_ . "site_articles_keysuse WHERE articles_id = '{$id}';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
}

/* ========================= */
///////////////////////////////
/* ========================= */
function PG_deleteIconType($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	if ($id != '') {
		$db = DB::singleton();
		$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_keys WHERE kid = '{$id}';";
		$db->query($sql, __FUNCTION__);
		if ($db->next_record()) {
			PG_unlinkFile($db->f('kicon'));
		}

		$sql = "UPDATE " . _DBPREFIX_ . "site_articles_keys SET `kicon` = '' WHERE kid = '{$id}'; ";
		$ok = $db->query($sql, __FUNCTION__);
	}
}

/* ========================= */
///////////////////////////////
/* ========================= */
function PG_addType($name, $iconname = '', $sort = 0, $keysname = '')
{
	$sql = "INSERT INTO " . _DBPREFIX_ . "site_articles_keys (
	`kid` ,
	`kname` ,
	`kicon`,
	`ksort` ,
	`keysname`
	) VALUES (
	NULL
	, '{$name}'
	, '{$iconname}'
	, '{$sort}'
	, '{$keysname}'
	); ";
	$db = DB::singleton();
	$ok = $db->query($sql, __FUNCTION__);
	return ($ok) ? $db->getInsertID() : false;
}
/* ========================= */
///////////////////////////////
/* ========================= */
function PG_getArticlesTypeByID($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_keys WHERE kid = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return ($db->num_rows() > 0) ? $db->allRows() : array();
}

/* ========================= */
///////////////////////////////
/* ========================= */
function PG_updateArticlesType($id, $name = '', $iconname = '', $sort = 0)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	$sql = "
	UPDATE " . _DBPREFIX_ . "site_articles_keys
	SET `kname` = '{$name}'
	,`kicon` = '{$iconname}'
	,`ksort` = '{$sort}'
	WHERE kid = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function pg_getAllType($keysname, $num = 0, $page = 0)
{
	////////////// Validate /////////////
	$num = _input_validate_int($num, true);
	$page = _input_validate_int($page, true);
	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_keys
	WHERE keysname = '{$keysname}'
	AND  kid IS NOT NULL
	ORDER BY ksort ASC, kname ASC, kid DESC
	";
	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
	return $aData;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_getAllTags($keysname, $num = 0, $page = 0)
{
	$num = _input_validate_int($num, true);
	$page = _input_validate_int($page, true);
	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_tags
	WHERE keysname = '{$keysname}'
	AND  tags_name IS NOT NULL
	ORDER BY tags_name ASC
	";
	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
	return $aData;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function pg_getAllTypeUseById($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	$sql = "SELECT k.*, u.articles_id FROM " . _DBPREFIX_ . "site_articles_keysuse u
	LEFT JOIN " . _DBPREFIX_ . "site_articles_keys k ON k.kid = u.kid
	WHERE articles_id = '{$id}' AND  k.kid IS NOT NULL ORDER BY k.ksort ASC, k.kid DESC ;";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aData = array();
	$aData['num_rows'] = $db->num_rows();
	$db->query($sql, __FUNCTION__);
	while ($db->next_record()) {
		$row = $db->allRows();
		$aData['data'][] = $row;
		$aData['kid_list'][$row['kid']] = $row['kid'];
	}

	return $aData;
}

function PG_editArticleTags($keysword = '', $keysname = '')
{
	if ($keysword == '') {
		return false;
	}

	if (is_array($keysword)) {
		return false;
	} else {
		$aTags = explode(",", $keysword);
		foreach ($aTags as $k => $v) {
			$a = PG_getTagsByKeys($keysname, trim($v));
			if ($a['tags_id'] != '') {
				$num = PG_validateArticleNumInTags($keysname, trim($v));
				PG_updateTagsNum($a['tags_id'], $num + 1);
			} else {
				PG_addTagsNum($keysname, trim($v), 1);
			}
		}
	}
}

function PG_addTagsNum($keysname, $tagsname, $num = 0)
{
	$num = _input_validate_int($num, true);
	$sql = "INSERT INTO " . _DBPREFIX_ . "site_tags (
	`tags_id` ,
	`tags_name` ,
	`articles_tags_num`,
	`keysname`
	) VALUES (
	NULL
	, '{$tagsname}'
	, '{$num}'
	, '{$keysname}'
	); ";
	$db = DB::singleton();
	$ok = $db->query($sql, __FUNCTION__);
	return ($ok) ? $db->getInsertID() : false;
}

function PG_updateTagsNum($tags_id, $num = 0)
{
	$tags_id = _input_validate_int($tags_id, true);
	$num = _input_validate_int($num, true);
	$sql = "
	UPDATE " . _DBPREFIX_ . "site_tags
	SET `articles_tags_num` = '{$num}'
	WHERE tags_id = '{$tags_id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
}

function PG_updateTagsName($tags_id, $name)
{
	$tags_id = _input_validate_int($tags_id, true);
	$sql = "
	UPDATE " . _DBPREFIX_ . "site_tags
	SET `tags_name` = '{$name}'
	WHERE tags_id = '{$tags_id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
}

function PG_getTagsByKeys($keysname = '', $tagsname = '')
{
	if ($keysname != '') {
		$sql = "SELECT * FROM " . _DBPREFIX_ . "site_tags WHERE keysname = '{$keysname}' AND tags_name = '{$tagsname}'; ";
	} else {
		$sql = "SELECT * FROM " . _DBPREFIX_ . "site_tags WHERE tags_name = '{$tagsname}'; ";
	}

	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return ($db->num_rows() > 0) ? $db->allRows() : array();
}

function PG_getTags($keysname = '', $tags_id = '')
{
	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_tags WHERE keysname = '{$keysname}' And tags_id = '{$tags_id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return ($db->num_rows() > 0) ? $db->allRows() : array();
}

function PG_deleteTags($id)
{
	$id = _input_validate_int($id, true);
	$db = DB::singleton();
	$sql = "DELETE FROM " . _DBPREFIX_ . "site_tags WHERE tags_id = '{$id}';";
	$db->query($sql, __FUNCTION__);
}
