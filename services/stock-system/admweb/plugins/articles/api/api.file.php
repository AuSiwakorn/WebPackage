<?php
function plugin_getAllFileByArticlesID($id, $num=0, $page=1)
{
	$sql = "SELECT *
			FROM "._DBPREFIX_."site_articles_file
			WHERE articles_id = '{$id}'
			ORDER BY ctime ASC
			";
			$db = DB::singleton();
			$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
		
	return $aData;
}

function PG_UpdatePath($id, $name='')
{
	////////////// Validate /////////////
	$sql = "
	UPDATE "._DBPREFIX_."site_articles_file
	SET `filename` = '{$name}'
	WHERE file_id = '{$id}';
	";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return true;
}