<?php
/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_updateArticlesGroup($id, $parent_id, $sort, $exoption = '', $status = 0, $extra1 = '', $extra2 = '', $extra3 = '', $extra4 = '', $extra5 = '')
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	$parent_id = _input_validate_int($parent_id, true);
	$sql = "
	UPDATE " . _DBPREFIX_ . "site_articles_group
	SET	 `group_parent_id` 	= '{$parent_id}',
	`sort` 			= '{$sort}',
	`updatetime`	= '" . _TIME_ . "',
	`extraOption`	= '{$exoption}',
	`status`		= '{$status}',
	`extra_group1`		= '{$extra1}',
	`extra_group2`		= '{$extra2}',
	`extra_group3`		= '{$extra3}',
	`extra_group4`		= '{$extra4}',
	`extra_group5`		= '{$extra5}'
	WHERE group_id = '{$id}';
	";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return true;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_setArticlesGroupToTop($id, $sort = 0)
{
	$id = _input_validate_int($id, true);
	$sql = "UPDATE " . _DBPREFIX_ . "site_articles_group
		SET `sort` = '{$sort}',
			  `updatetime` = '" . _TIME_ . "'
			  WHERE group_id = '{$id}';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return true;
}

function PG_setArticlesGroupToStatus($id, $status = 0)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	$sql = "
		UPDATE " . _DBPREFIX_ . "site_articles_group
		SET `status` = '{$status}',
		`updatetime` = '" . _TIME_ . "'
		WHERE group_id = '{$id}';
		";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return true;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_deleteArticlesGroup($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_group WHERE group_id = '{$id}';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	if ($db->next_record()) {
		PG_unlinkFile($db->f('img'));
		PG_unlinkFile($db->f('icon'));
	}

	$aData = PG_getAllPictureByArticlesGroupID($id);
	if ($aData['num_rows'] > 0) {
		foreach ($aData['data'] as $v) {
			unlink(PATH_UPLOAD . '/' . $v['imgPathMini']);
			unlink(PATH_UPLOAD . '/' . $v['imgPathBig']);
		}
	}

	$sql = "DELETE FROM " . _DBPREFIX_ . "site_articles_group_img WHERE group_id = '{$id}';";
	$db->query($sql, __FUNCTION__);

	$sql = "DELETE FROM " . _DBPREFIX_ . "site_articles_group WHERE group_id = '{$id}';";
	$db->query($sql, __FUNCTION__);

	$sql = "DELETE FROM " . _DBPREFIX_ . "site_articles_group_content WHERE group_id = '{$id}';";
	$db->query($sql, __FUNCTION__);
	return true;
}

function PG_getAllPictureByArticlesGroupID($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_group_img WHERE group_id = '{$id}' ORDER BY ismark DESC, ctime ASC; ";
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


/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_getArticlesGroupByID($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_group WHERE group_id = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	if ($db->num_rows() > 0) {
		$aRows = $db->allRows();
		$aData = PG_getArticlesGroupContentByID($id);
		$aRows['content'] = (isset($aData['data']) && count($aData['data']) > 0) ? $aData['data'] : '';
	} else {
		return false;
	}
	return $aRows;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_getArticlesGroupContentByID($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT *  FROM " . _DBPREFIX_ . "site_articles_group_content WHERE group_id = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aData = array();
	while ($db->next_record()) {
		$aRow = $db->allRows();
		$aData['data'][$aRow['langkeys']] = $aRow;
	}
	return (isset($aData['data']) && count($aData['data']) > 0) ? $aData : array();
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_updateArticlesGroupContent($d)
{
	$db = DB::singleton();
	foreach ($d as $kLang => $v) {
		$id = $v['group_id'];
		$name = $v['name'];
		$slug = $v['slug'];

		//////////////////////////////////////////////////////
		/////////////////////Qoute //////////////////////
		//////////////////////////////////////////////////////
		$detail = $v['detail'];
		$detail = str_replace("'", "&#39;", $detail);
		$detailExtra1 = $v['detailExtra1'];
		$detailExtra1 = str_replace("'", "&#39;", $detailExtra1);
		$detailExtra2 = $v['detailExtra2'];
		$detailExtra2 = str_replace("'", "&#39;", $detailExtra2);
		//////////////////////////////////////////////////////
		/////////////////////Qoute //////////////////////
		//////////////////////////////////////////////////////

		if (PG_getCheckArticleGroup_ContentByID($id, $kLang) == true) {
			$sql = "UPDATE " . _DBPREFIX_ . "site_articles_group_content
			SET  `group_name` 	= '{$name}',
			`detail` 			= '{$detail}',
			`detailExtra1` 			= '{$detailExtra1}',
			`detailExtra2` 			= '{$detailExtra2}',
			`group_slug` 			= '{$slug}'
			WHERE group_id 		= '{$id}'
			AND langkeys 			= '{$kLang}';
			";
			$db->query($sql, __FUNCTION__);
		} else {
			$aAdd = array();
			$aAdd[$kLang] = $v;
			PG_addGroupContent($aAdd);
		}
	}
}

/* ========================= */
/**
 * @param number $id
 * @param string $langkeys
 * @return boolean
 */
function PG_getCheckArticleGroup_ContentByID($id, $langkeys)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "	SELECT group_content_id
				FROM " . _DBPREFIX_ . "site_articles_group_content
				WHERE langkeys = '{$langkeys}'
	AND group_id = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return ($db->num_rows() > 0) ? true : false;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_getAllGroupSelect($keysname)
{
	global $lang;
	$sql = "
		SELECT g.*, c.group_content_id, c.langkeys, c.group_name, c.detail, c.detailExtra1, c.detailExtra2
		FROM " . _DBPREFIX_ . "site_articles_group g
		LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content c ON c.group_id = g.group_id
		WHERE keysname = '{$keysname}'
		AND c.langkeys = '{$lang}'
	AND g.group_id IS NOT NULL
	GROUP BY g.group_id
	ORDER BY g.sort ASC, g.updatetime DESC, g.group_id ASC
	;";

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

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_getAllGroupByGID($gid)
{
	global $lang;

	////////////// Validate /////////////
	$gid = _input_validate_int($gid, true);
	/////////// End Validate ///////////

	$sql = "
	SELECT g.*, c.group_content_id, c.group_name, c.detail, c.detailExtra1, c.detailExtra2, c.langkeys
	FROM `" . _DBPREFIX_ . "site_articles_group` g
	LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content c ON c.group_id = g.group_id
	AND c.langkeys = '{$lang}'
	WHERE g.group_id IS NOT NULL
	AND g.group_parent_id = '{$gid}'
	ORDER BY sort ASC, group_id DESC;";

	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aData = array();
	while ($db->next_record()) {
		$aData[] = $db->allRows();
	}
	return $aData;
}

function PG_setArticlesGroupToTopBySort($id, $sortnum = 0)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "
		UPDATE " . _DBPREFIX_ . "site_articles_group
		SET `sort` = '{$sortnum}'
		WHERE group_id = '{$id}';
		";

	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return true;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_addArticleGroupContent($d)
{
	$db = DB::singleton();
	$aSql = array();

	$sql = "INSERT INTO " . _DBPREFIX_ . "site_articles_content (`content_id` ,`articles_id` ,`langkeys` ,`title` ,`shortMessage` ,`keywords` ,`content`,`author`,`content2`,`content3`,`content4`,`content_extra1`,`content_extra2`,`content_extra3`,`slug`) VALUES ";
	foreach ($d as $kLang => $v) {
		$articles_id = $v['articles_id'];
		$title = $v['title'];
		$shortMessage = $v['shortMessage'];
		$keywords = $v['keywords'];

		//////////////////////////////////////////////////////
		////////////////////////Qoute ////////////////////////
		//////////////////////////////////////////////////////
		$author = $v['author'];
		$slug = $v['slug'];
		$content = ($v['content']) ? str_replace("'", "&#39;", $v['content']) : '';
		$content2 = ($v['content2']) ? str_replace("'", "&#39;", $v['content2']) : '';
		$content3 = ($v['content3']) ? str_replace("'", "&#39;", $v['content3']) : '';
		$content4 = ($v['content4']) ? str_replace("'", "&#39;", $v['content4']) : '';

		$content_extra1 = ($v['content_extra1']) ? str_replace("'", "&#39;", $v['content_extra1']) : '';
		$content_extra2 = ($v['content_extra2']) ? str_replace("'", "&#39;", $v['content_extra2']) : '';
		$content_extra3 = ($v['content_extra3']) ? str_replace("'", "&#39;", $v['content_extra3']) : '';
		//////////////////////////////////////////////////////
		////////////////////////Qoute ////////////////////////
		//////////////////////////////////////////////////////

		$aSql[] = "(NULL , '{$articles_id}', '{$kLang}', '{$title}', '{$shortMessage}', '{$keywords}', '{$content}', '{$author}', '{$content2}' , '{$content3}' , '{$content4}', '{$content_extra1}', '{$content_extra2}', '{$content_extra3}', '{$slug}')";
	}
	$sql .= implode(',', $aSql);
	$sql .= ";";
	$db->query($sql, __FUNCTION__);
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_addGroupContent($d)
{
	$db = DB::singleton();
	$aSql = array();
	$sql = "INSERT INTO " . _DBPREFIX_ . "site_articles_group_content (`group_content_id` ,`group_id` ,`langkeys` ,`group_name` ,`detail` ,`detailExtra1` ,`detailExtra2`,`group_slug`) VALUES ";
	foreach ($d as $kLang => $v) {
		$group_id = $v['group_id'];
		$name = $v['name'];
		$slug = $v['slug'];

		//////////////////////////////////////////////////////
		/////////////////////Qoute //////////////////////
		//////////////////////////////////////////////////////
		$detail = $v['detail'];
		$detail = str_replace("'", "&#39;", $detail);

		$detailExtra1 = $v['detailExtra1'];
		$detailExtra1 = str_replace("'", "&#39;", $detailExtra1);

		$detailExtra2 = $v['detailExtra2'];
		$detailExtra2 = str_replace("'", "&#39;", $detailExtra2);
		//////////////////////////////////////////////////////
		/////////////////////Qoute //////////////////////
		//////////////////////////////////////////////////////

		$aSql[] = "(NULL , '{$group_id}', '{$kLang}', '{$name}', '{$detail}', '{$detailExtra1}', '{$detailExtra2}', '{$slug}')";
	}
	$sql .= implode(',', $aSql);
	$sql .= ";";
	$db->query($sql, __FUNCTION__);
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function pg_getAllGroup($keysname, $num = 0, $page = 0)
{
	global $lang, $aQData;
	$num = _input_validate_int($num, true);
	$page = _input_validate_int($page, true);
	$sql = "SELECT
				g.*,
				gc.group_content_id, gc.langkeys, gc.group_name, gc.detail, gc.detailExtra1, gc.detailExtra2
			FROM " . _DBPREFIX_ . "site_articles_group g
			LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content gc ON gc.group_id = g.group_id
			AND gc.langkeys = '{$lang}'
			WHERE g.keysname = '{$keysname}'
			AND  g.group_id IS NOT NULL
			ORDER BY sort ASC, updatetime DESC, group_id DESC
			";

	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

	return $aData;
}

function pg_getAllGroupParentID($keysname, $pgid, $num = 0, $page = 0)
{
	global $lang, $aQData;
	$num = _input_validate_int($num, true);
	$page = _input_validate_int($page, true);
	$sql = "SELECT
				g.*,
				gc.group_content_id, gc.langkeys, gc.group_name, gc.detail, gc.detailExtra1, gc.detailExtra2
			FROM " . _DBPREFIX_ . "site_articles_group g
			LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content gc ON gc.group_id = g.group_id
			AND gc.langkeys = '{$lang}'
			WHERE g.keysname = '{$keysname}'
			AND  g.group_parent_id = '{$pgid}'
			ORDER BY sort ASC, updatetime DESC, group_id DESC
			";

	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

	return $aData;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_addGroup($parent_id, $iconname, $sort, $keysname, $exoption = '', $status = 0, $extra1 = '', $extra2 = '', $extra3 = '', $extra4 = '', $extra5 = '')
{
	////////////// Validate /////////////
	$parent_id = setDefaultINT($parent_id);
	$parent_id = _input_validate_int($parent_id, true);

	$sort = setDefaultINT($sort);
	$status = setDefaultINT($status);
	$sql = "INSERT INTO " . _DBPREFIX_ . "site_articles_group (
	`group_id` ,
	`group_parent_id` ,
	`keysname`,
	`img` ,
	`sort`,
	`updatetime`,
	`extraOption`,
	`status`,
	`extra_group1`,
	`extra_group2`,
	`extra_group3`,
	`extra_group4`,
	`extra_group5`
	) VALUES (
	NULL
	, '{$parent_id}'
	, '{$keysname}'
	, '{$iconname}'
	, '{$sort}'
	, '" . _TIME_ . "'
	, '{$exoption}'
	, '{$status}'
	, '{$extra1}'
	, '{$extra2}'
	, '{$extra3}'
	, '{$extra4}'
	, '{$extra5}'
	); ";
	$db = DB::singleton();
	$ok = $db->query($sql, __FUNCTION__);
	return ($ok) ? $db->getInsertID() : false;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_updateIconGroup($id, $iconname, $iconname2 = '')
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	if ($iconname != '') {
		$db = DB::singleton();
		$sql = "SELECT img FROM " . _DBPREFIX_ . "site_articles_group WHERE group_id = '{$id}';";
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		if (is_file(PATH_UPLOAD . '/' . $db->f('img')) && $db->f('img') != '') {
			unlink(PATH_UPLOAD . '/' . $db->f('img'));
		}

		$sql = "UPDATE " . _DBPREFIX_ . "site_articles_group SET `img` = '{$iconname}' WHERE group_id = '{$id}'; ";
		$ok = $db->query($sql, __FUNCTION__);
	}

	if ($iconname2 != '') {
		$db = DB::singleton();
		$sql = "SELECT icon FROM " . _DBPREFIX_ . "site_articles_group WHERE group_id = '{$id}';";
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		if (is_file(PATH_UPLOAD . '/' . $db->f('icon')) && $db->f('icon') != '') {
			unlink(PATH_UPLOAD . '/' . $db->f('icon'));
		}

		$sql = "UPDATE " . _DBPREFIX_ . "site_articles_group SET `icon` = '{$iconname2}' WHERE group_id = '{$id}'; ";
		$ok = $db->query($sql, __FUNCTION__);
	}
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_deleteIconGroup($id)
{
	$id = _input_validate_int($id, true);
	if ($id != '') {
		$sql = "SELECT icon FROM " . _DBPREFIX_ . "site_articles_group WHERE group_id = '{$id}';";
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		if ($db->f('icon') != '') {
			$n = $db->f('icon');
			if (file_exists(PATH_UPLOAD . '/' . $n)) {
				unlink(PATH_UPLOAD . '/' . $n);
			}

			$sql = "UPDATE " . _DBPREFIX_ . "site_articles_group SET `icon` = '' WHERE group_id = '{$id}'; ";
			$db->query($sql, __FUNCTION__);
		}
	}
}

function PG_deleteImgGroup($id)
{
	$id = _input_validate_int($id, true);
	if ($id != '') {
		$sql = "SELECT img FROM " . _DBPREFIX_ . "site_articles_group WHERE group_id = '{$id}';";
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		if ($db->f('img') != '') {
			$n = $db->f('img');
			if (file_exists(PATH_UPLOAD . '/' . $n)) {
				unlink(PATH_UPLOAD . '/' . $n);
			}

			$sql = "UPDATE " . _DBPREFIX_ . "site_articles_group SET `img` = '' WHERE group_id = '{$id}'; ";
			$db->query($sql, __FUNCTION__);
		}
	}
}
