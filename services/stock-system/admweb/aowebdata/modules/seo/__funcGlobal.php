<?php
function _articles_SEOgetAll($keysname)
{
	global $lang, $aQData;
	$db = DB::singleton();
	$sql = "SELECT
				a.*,
				c.content_id, c.langkeys, c.title, c.shortMessage
			FROM "._DBPREFIX_."site_articles a
			LEFT JOIN "._DBPREFIX_."site_articles_content c ON c.articles_id = a.articles_id
			AND c.langkeys = '{$lang}'
			WHERE a.keysname = '{$keysname}'
			AND a.displaytime <= '"._TIME_."'
			AND a.status = '0'
			AND  a.articles_id IS NOT NULL
			ORDER BY a.sorttime ASC, a.group_id ASC, a.add_time DESC , a.articles_id DESC;
			";
	$db->query($sql, __FUNCTION__);
	$aData = array();
	$aData['data'] = array();
	$aData['num_rows'] = $db->num_rows();
	while ($db->next_record()) {
		$aData['data'][] = $db->allRows();
	}
	
	return $aData;
}

function _articles_SEOgetGroupAll($keysname)
{
	global $lang, $aQData;
	$db = DB::singleton();
	$sql = "SELECT
				g.*,
				gc.group_content_id, gc.langkeys, gc.group_name, gc.detail, gc.detailExtra1, gc.detailExtra2
			FROM "._DBPREFIX_."site_articles_group g
			LEFT JOIN "._DBPREFIX_."site_articles_group_content gc ON gc.group_id = g.group_id
			AND gc.langkeys = '{$lang}'
			WHERE g.keysname = '{$keysname}'
			AND g.group_id IS NOT NULL
			AND g.status = '0'
			ORDER BY g.sort ASC, g.group_id DESC , g.updatetime DESC
			";
$db->query($sql, __FUNCTION__);
$aData = array();
$aData['data'] = array();
$aData['num_rows'] = $db->num_rows();
while ($db->next_record()) {
	$aData['data'][] = $db->allRows();
}

return $aData;
}
