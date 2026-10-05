<?php
function plugin_articles_getpages($keysname) 
{
	global $lang;
	$time = _TIME_;
	$db = DB::singleton();
	$sql = "SELECT 
				a.*, 
				c.content_id, c.author, c.langkeys, c.title, c.shortMessage, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
			FROM "._DBPREFIX_."site_articles a
			LEFT JOIN "._DBPREFIX_."site_articles_content c ON c.articles_id = a.articles_id
			AND c.langkeys = '{$lang}'
			WHERE a.keysname = '{$keysname}'
			AND a.status = '0'
			AND a.displaytime <= '{$time}'
			";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	$aData = array();
	$aData['sql'] = $sql;
	$aData['data'] = ($db->num_rows() > 0) ? $db->allRows() : array();
	$aData['picture'] = array();
	return $aData;
}

function plugin_page_col($keysname, $col, $isUpload=false, $defaultImg='')
{
	global $aQData;
	$hash = md5($keysname);
	if (isset($aQData[$hash])) {
		$aPageData = $aQData[$hash];
	} else {
		$aPageData = plugin_articles_getpages($keysname);
		$aPageData = $aPageData['data'];
		$aQData[$hash] = $aPageData;
	}

	if (isset($aPageData[$col]) && $aPageData[$col] != '') {
		if ($isUpload != false) {
			return URL_UPLOAD.'/'.$aPageData[$col];
		} else {
			return $aPageData[$col];
		}
		
	} else {
		if ($isUpload != false) {
			return $defaultImg;
		} else {
			return '';
		}
	}
}
?>
