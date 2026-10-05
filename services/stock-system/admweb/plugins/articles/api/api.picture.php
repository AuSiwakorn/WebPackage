<?php

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function plugin_getAllPictureByArticlesID($id, $num = 0, $page = 1, $isRandom = false)
{
	$orderBy = ($isRandom == true) ? " ORDER BY RAND() " : " ORDER BY ctime ASC ";
	$sql = "SELECT * 
			FROM " . _DBPREFIX_ . "site_articles_img
			WHERE articles_id = '{$id}'
			{$orderBy}
			";

	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

	return $aData;
}

function plugin_getAllPictureMarkByArticlesID($id, $num = 0, $page = 1)
{
	$sql = "SELECT *
	FROM " . _DBPREFIX_ . "site_articles_img
	WHERE articles_id = '{$id}'
	AND ismark = '1'
	ORDER BY ctime ASC
	";
	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

	return $aData;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function plugin_getAllPicture($num = 0, $page = 1, $isRandom = false)
{
	if ($isRandom == true) {
		$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_img ORDER BY RAND() ";
	} else {
		$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_img ORDER BY ctime ASC	";
	}

	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

	return $aData;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function plugin_getAllPictureByKeysname($keysname = '', $num = 0, $page = 1, $isRandom = false)
{
	if (is_array($keysname)) {
		$ex = implode("','", $keysname);
		$ksearch = " keysname IN ('" . $ex . "') ";
	} else {
		$ksearch = " keysname = '{$keysname}' ";
	}

	if ($isRandom == true) {
		$sql = "SELECT *
		FROM " . _DBPREFIX_ . "site_articles_img
		WHERE {$ksearch}
		ORDER BY RAND()
		";
	} else {
		$sql = "SELECT *
		FROM " . _DBPREFIX_ . "site_articles_img
		WHERE {$ksearch}
		ORDER BY ctime ASC
		";
	}

	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

	return $aData;
}

////////////////////////////////////////16.2.63////////////////////////////////////////////////
function plugin_getAllPictureGroupByArticlesID($id, $num = 0, $page = 1, $isRandom = false)
{
	$orderBy = ($isRandom == true) ? " ORDER BY RAND() " : " ORDER BY ctime ASC ";
	$sql = "SELECT *
			FROM " . _DBPREFIX_ . "site_articles_group_img
			WHERE group_id = '{$id}'
			{$orderBy}
			";

			$db = DB::singleton();
			$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
		
	return $aData;
}
