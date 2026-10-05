<?php
global $aQData;
/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function plugin_getArticlesGroupContentByID($id)
{
	global $aQData;
	$sql = "SELECT *  FROM " . _DBPREFIX_ . "site_articles_group_content WHERE group_id = '{$id}'; ";
	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$aData = array();
		$aData['data'] = array();
		while ($db->next_record()) {
			$aRow = $db->allRows();
			$aData['data'][$aRow['langkeys']] = $aRow;
		}
		$aQData[$md5Q] = (isset($aData['data']) && count($aData['data']) > 0) ? $aData : array();
		return $aQData[$md5Q];
	}
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function plugin_getAllGroup($keysname, $num = 0, $page = 0)
{
	global $lang, $aQData;
	$sql = "SELECT 
				g.*, 
				gc.group_content_id, gc.langkeys, gc.group_name, gc.detail, gc.detailExtra1, gc.detailExtra2, gc.group_slug
			FROM " . _DBPREFIX_ . "site_articles_group g
			LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content gc ON gc.group_id = g.group_id
			AND gc.langkeys = '{$lang}'
			WHERE g.keysname = '{$keysname}'
			AND g.group_id IS NOT NULL
			AND g.status = '0'
			ORDER BY g.sort ASC, g.group_id DESC , g.updatetime DESC
			";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);


		$aQData[$md5Q] = $aData;
		return $aData;
	}
}

function plugin_getAllGroupParentId($keysname, $parent_id = 0, $num = 0, $page = 0)
{
	global $lang, $aQData;
	$sql = "SELECT 
				g.*, 
				gc.group_content_id, gc.langkeys, gc.group_name, gc.detail, gc.detailExtra1, gc.detailExtra2, gc.group_slug
			FROM " . _DBPREFIX_ . "site_articles_group g
			LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content gc ON gc.group_id = g.group_id
			AND gc.langkeys = '{$lang}'
			WHERE g.keysname = '{$keysname}'
			AND g.group_parent_id = '{$parent_id}'
			AND g.group_id IS NOT NULL
			AND g.status = '0'
			ORDER BY g.sort ASC, g.group_id DESC , g.updatetime DESC
			";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

		$aQData[$md5Q] = $aData;
		return $aData;
	}
}

function plugin_getAllAnyGroup($aKeysname, $num = 0, $page = 0)
{
	global $lang, $aQData;
	$strKeys = implode("','", $aKeysname);
	$sql = "SELECT 
				g.*, 
				gc.group_content_id, gc.langkeys, gc.group_name, gc.detail, gc.detailExtra1, gc.detailExtra2, gc.group_slug
			FROM " . _DBPREFIX_ . "site_articles_group g
			LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content gc ON gc.group_id = g.group_id
			AND gc.langkeys = '{$lang}'
			WHERE g.keysname IN ('{$strKeys}')
			AND g.group_id IS NOT NULL
			AND g.status = '0'
			ORDER BY g.sort ASC, g.updatetime DESC, g.group_id DESC
			";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

		$aQData[$md5Q] = $aData;
		return $aData;
	}
}

function plugin_getAllGroupByExtra($extra1, $num = 0, $page = 0)
{
	global $lang, $aQData;
	$sql = "SELECT 
				g.*, 
				gc.group_content_id, gc.langkeys, gc.group_name, gc.detail,, gc.detailExtra1, gc.detailExtra2, gc.shortMessage, gc.group_slug
			FROM " . _DBPREFIX_ . "site_articles_group g
			LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content gc ON gc.group_id = g.group_id
			AND gc.langkeys = '{$lang}'
			WHERE g.extra1 = '{$extra1}'
			AND  g.group_id IS NOT NULL
			AND g.status = '0'
			ORDER BY g.sort ASC, g.updatetime DESC, g.group_id DESC
			";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

		$aQData[$md5Q] = $aData;
		return $aData;
	}
}
/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function plugin_getArticlesGroupByID($id)
{
	global $lang, $aQData;
	$sql = "SELECT 
				g.*, 
				gc.group_content_id, gc.langkeys, gc.group_name, gc.detail, gc.detailExtra1, gc.detailExtra2, gc.group_slug
			FROM " . _DBPREFIX_ . "site_articles_group g
			LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content gc ON gc.group_id = g.group_id
			AND gc.langkeys = '{$lang}'
			WHERE g.group_id = '{$id}'
			AND  g.group_id IS NOT NULL
			AND g.status = '0'
			ORDER BY g.sort ASC, g.updatetime DESC, g.group_id DESC ;
			";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$aQData[$md5Q] = ($db->next_record()) ? $db->allRows() : array();
		return $aQData[$md5Q];
	}
}

function plugin_getArticlesGroupByExtra1($extra1)
{
	global $lang, $aQData;
	$sql = "SELECT
				g.*,
				gc.group_content_id, gc.langkeys, gc.group_name, gc.detail, gc.detailExtra1, gc.detailExtra2, gc.group_slug
			FROM " . _DBPREFIX_ . "site_articles_group g
			LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content gc ON gc.group_id = g.group_id
			AND gc.langkeys = '{$lang}'
			WHERE g.extra_group1 = '{$extra1}'
			AND  g.group_id IS NOT NULL
			AND g.status = '0'
			ORDER BY g.sort ASC, g.updatetime DESC, g.group_id DESC ;
			";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$aQData[$md5Q] = ($db->next_record()) ? $db->allRows() : array();
		return $aQData[$md5Q];
	}
}

function plugin_getArticlesGroupBySlug($slug)
{
	global $lang, $aQData;
	$sql = "SELECT
				g.*,
				gc.group_content_id, gc.langkeys, gc.group_name, gc.detail, gc.detailExtra1, gc.detailExtra2, gc.group_slug
			FROM " . _DBPREFIX_ . "site_articles_group g
			LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content gc ON gc.group_id = g.group_id
			AND gc.langkeys = '{$lang}'
			WHERE gc.group_slug = '{$slug}'
			AND  g.group_id IS NOT NULL
			AND g.status = '0'
			ORDER BY g.sort ASC, g.updatetime DESC, g.group_id DESC ;
			";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$aQData[$md5Q] = ($db->next_record()) ? $db->allRows() : array();
		return $aQData[$md5Q];
	}
}
