<?php
/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function plugin_getAllTypeByArticleID($articles_id, $num = 0, $page = 0)
{
	$db = DB::singleton();
	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_keys k LEFT JOIN " . _DBPREFIX_ . "site_articles_keysuse u ON u.kid = k.kid WHERE articles_id = '{$articles_id}' ";
	$db->query($sql, __FUNCTION__);
	$aData = array();
	$aData['data'] = array();
	$aData['num_rows'] = $db->num_rows();
	if ($num > 0) {
		$start = ($page == 0 || $page == 1) ? 0 : (($num * $page) - $num);
		$sql .= ($num > 0) ? ' LIMIT ' . $start . ' , ' . $num . ' ;' : ';';
	}

	$db->query($sql, __FUNCTION__);
	while ($db->next_record()) {
		$aData['data'][] = $db->allRows();
	}

	return $aData;
}
/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function plugin_getTypeKeysUse($kid)
{
	$db = DB::singleton();
	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_keys
			  WHERE kid = '{$kid}'
			  AND  kid IS NOT NULL;
			";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return ($db->num_rows() > 0) ? $db->allRows() : array();
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function plugin_getAllType($keysname, $num = 0, $page = 0)
{
	$db = DB::singleton();
	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_keys
			  WHERE keysname = '{$keysname}'
			  AND  kid IS NOT NULL
			 ORDER BY ksort ASC, kname ASC, kid DESC
			";
	$db->query($sql, __FUNCTION__);
	$aData = array();
	$aData['data'] = array();
	$aData['num_rows'] = $db->num_rows();
	if ($num > 0) {
		$start = ($page == 0 || $page == 1) ? 0 : (($num * $page) - $num);
		$sql .= ($num > 0) ? ' LIMIT ' . $start . ' , ' . $num . ' ;' : ';';
	}

	$db->query($sql, __FUNCTION__);
	while ($db->next_record()) {
		$aData['data'][] = $db->allRows();
	}

	return $aData;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function plugin_getAllArticleByTypeUse($kid, $keysname, $num = 0, $page = 0)
{
	global $lang;
	$sql = "SELECT  u.kuid, u.kid, a.*,
					c.content_id, c.langkeys, c.title, c.shortMessage, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3
				FROM " . _DBPREFIX_ . "site_articles_keysuse u
				LEFT JOIN " . _DBPREFIX_ . "site_articles a ON a.articles_id = u.articles_id
				AND a.keysname = '{$keysname}'
				LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = u.articles_id
				AND c.langkeys = '{$lang}'
				WHERE u.kid = '{$kid}'
				AND  a.articles_id IS NOT NULL
				ORDER BY a.sorttime ASC, a.articles_id DESC
			";
	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

	return $aData;
}

function plugin_getAllGroupByType($kid, $group_id = 0, $num = 0, $page = 0)
{
	global $lang;
	$sqlIsGroup = ($group_id > 0) ? " AND a.group_id = '{$group_id}' " : '';
	$sql = "SELECT  u.kuid, u.kid, a.group_id, k.*, g.group_name
			  FROM " . _DBPREFIX_ . "site_articles_keysuse u
			  LEFT JOIN " . _DBPREFIX_ . "site_articles_keys k ON k.kid = u.kid
			  LEFT JOIN " . _DBPREFIX_ . "site_articles a ON a.articles_id = u.articles_id
			  LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content g ON g.group_id = a.group_id
			  WHERE u.kid = '{$kid}'
			  {$sqlIsGroup}
			  AND  a.articles_id IS NOT NULL
			  GROUP BY a.group_id
			  ORDER BY k.ksort ASC, k.kname ASC
			";
	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

	return $aData;
}


function plugin_getArticleAllGroupByType($kid = 0, $group_id = 0, $keysname = '', $num = 0, $page = 0)
{
	global $lang;
	$sqlIsGroup = ($group_id > 0) ? " AND a.group_id = '{$group_id}' " : '';
	$sqlIsKid = ($kid > 0) ? " AND u.kid = '{$kid}' " : '';
	$sql = "
	SELECT 
		a.articles_id, a.group_id, a.keysname, a.status, a.add_time, a.displaytime, a.icon, 
		c.content_id, c.langkeys, c.title, c.shortMessage,
		u.*, k.*, g.group_name
	FROM " . _DBPREFIX_ . "site_articles a
	LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
	LEFT JOIN " . _DBPREFIX_ . "site_articles_keysuse u ON u.articles_id = a.articles_id
	LEFT JOIN " . _DBPREFIX_ . "site_articles_keys k ON k.kid = u.kid
	LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content g ON g.group_id = a.group_id
	WHERE a.keysname = '{$keysname}'
	AND c.langkeys = '{$lang}'
	AND g.langkeys = '{$lang}'
	{$sqlIsGroup}
	{$sqlIsKid}
	ORDER BY g.group_name DESC
	";
	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

	return $aData;
}
