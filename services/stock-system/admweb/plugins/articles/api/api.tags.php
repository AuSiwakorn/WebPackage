<?php

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function plugin_getAllTags($keysname, $num=0, $page=0) 
{
	$db = DB::singleton();
	$sql = "SELECT * FROM "._DBPREFIX_."site_tags
			  WHERE keysname = '{$keysname}'
			  AND  tags_name IS NOT NULL
			 ORDER BY articles_tags_num DESC, tags_name ASC
			";
	$db->query($sql, __FUNCTION__);
	$aData = array();
	$aData['data'] = array();
	$aData['num_rows'] = $db->num_rows();
	if ($num > 0) {
		$start = ($page == 0 || $page == 1) ? 0 : (($num*$page)-$num);
		$sql .= ($num > 0) ? ' LIMIT '.$start.' , '.$num.' ;' : ';';
	}
	
	$db->query($sql, __FUNCTION__);
	while ($db->next_record()) {
		$aData['data'][] = $db->allRows();
	}
	
	return $aData;
}
?>