<?php
include PATH_PLUGIN . '/writelicense/resize.php';
function PG_banner_get_all_banner_by_keys($keysname='', $status='all'){
	$db = DB::singleton();
	$bystatus = ($status == 'all') ? '' : " AND status = '{$status}' ";
	$sql = "SELECT * FROM `"._DBPREFIX_."banner` WHERE keysname = '{$keysname}' {$bystatus} ORDER BY sort ASC; ";
	$db->query($sql, __FUNCTION__);
	$aData = array();
	while ($db->next_record()) {
		$aData[] = $db->allRows();
	}
	return $aData;
}
/* ================================ */
/**
 * Select all banner by id.
 * @param number $id
 * @return array
 */
function PG_banner_get_banner_by_id($id){
	$db = DB::singleton();
	$sql = "SELECT * FROM `"._DBPREFIX_."banner` WHERE banner_id = '{$id}' ORDER BY sort ASC; ";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->allRows();
}

function PG_banner_update_sort_banner($id,$n){
	$db = DB::singleton();
	$sql = "UPDATE "._DBPREFIX_."banner SET `sort` = '{$n}' WHERE banner_id ='{$id}';";
	$db->query($sql, __FUNCTION__);
}

function PG_banner_update_status($id, $upto=0){
	$db = DB::singleton();
	$sql = "UPDATE "._DBPREFIX_."banner SET `status` = '{$upto}' WHERE banner_id ='{$id}';";
	$db->query($sql, __FUNCTION__);
}

/**
 * Update banner form array data.
 * @param number $id
 * @param array $aUpdate
 * 
 * $aUpdate['title'];
	$aUpdate['detail'];
	$aUpdate['link'];
	$aUpdate['target'];
	$aUpdate['filename'];
 */
function PG_banner_update($id, $aUpdate){
	$db = DB::singleton();
	$sql = "
		UPDATE "._DBPREFIX_."banner SET 
			`title` = '{$aUpdate['title']}' ,
			`detail` = '{$aUpdate['detail']}' ,
			`link` = '{$aUpdate['link']}' ,
			`target` = '{$aUpdate['target']}' ,
			`filename` = '{$aUpdate['filename']}' ,
			`extra1` = '{$aUpdate['extra1']}' ,
			`extra2` = '{$aUpdate['extra2']}'  
		WHERE banner_id ='{$id}';";
	$db->query($sql, __FUNCTION__);
}

function PG_banner_delete_by_id($id){
	$db = DB::singleton();
	$sql = "DELETE FROM "._DBPREFIX_."banner WHERE banner_id ='{$id}'; ";
	$db->query($sql, __FUNCTION__);
}

/**
 * Insert banner form keysname and array data.
 * @param string $keysname
 * @param array $aBanner
 * @return boolean
 * 
 * $aBanner['title'];
	$aBanner['detail'];
	$aBanner['link'];
	$aBanner['target'];
	$aBanner['sort'];
	$aBanner['bannertype'];
	$aBanner['filename'];
 */
function PG_banner_insert($keysname='', $aBanner){
	$adddate = _TIME_;
	$db = DB::singleton();
	$sql = "
		INSERT INTO "._DBPREFIX_."banner (
			`banner_id` ,
			`title` ,
			`detail` ,
			`link` ,
			`target` ,
			`adddate` ,
			`sort` ,
			`bannertype` ,
			`keysname` ,
			`filename` ,
			`extra1` ,
			`extra2` 
		) VALUES (
			NULL , 
			'{$aBanner['title']}', 
			'{$aBanner['detail']}', 
			'{$aBanner['link']}', 
			'{$aBanner['target']}', 
			'{$adddate}', 
			'{$aBanner['sort']}', 
			'{$aBanner['bannertype']}', 
			'{$keysname}', 
			'{$aBanner['filename']}',
			'{$aBanner['extra1']}', 
			'{$aBanner['extra2']}'
		);
	";
	$ok = $db->query($sql, __FUNCTION__);
	return ($ok) ? $db->getInsertID() : false;
}

?>