<?php

function getHtml($name) 
{
	return getHTMLContent(PATH_UPLOAD . '/html/html_' . $name . '.html');
}

function getHTMLContent($pathAdsHtml) 
{
    if (is_file($pathAdsHtml)) {
        $h = file($pathAdsHtml);
        $h = implode("", $h);
        $h = str_replace("\r", "", $h);
        $h = str_replace("  ", " ", $h);
        return stripslashes(str_replace("\t", "", $h));
    }
}

function writeHTMLContent($pathAdsHtml, $html)
{
	$fp = fopen($pathAdsHtml, 'w');
	fwrite($fp, $html);
	fclose($fp);
}

function getDataRead($id)
{/*ฟีฟ่าหาคนใช้ไม่เจอ
	$db = DB::singleton();
	$sql = "SELECT * FROM managehtml_commit WHERE commit_id = '{$id}'; ";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->allRows();*/
}

function updateReaddate($id)
{/*ฟีฟ่าเหมือนข้างบน
	$db = DB::singleton();
	$readdate = _TIME_;
	$sql = "
	UPDATE managehtml_commit 
	SET `readdate` = '{$readdate}' ,
		`is_read` = '1' 
	WHERE commit_id = '{$id}';";
	$db->query($sql, __FUNCTION__);*/
}

function getAllDataAllRead($keysname, $n=100, $start=0)
{/*ฟีฟ่า same
	$db = DB::singleton();
	$sql = "SELECT * FROM managehtml_commit WHERE keysname = '{$keysname}' ";
	$db->query($sql, __FUNCTION__);
	$aData = array();
	$aData['num_rows'] = ($db->num_rows() > 0) ? $db->num_rows() : 0;
	
	$sql .= " ORDER BY is_read ASC, adddate DESC, commit_id DESC
			LIMIT {$start}, {$n} ;";
	$db->query($sql, __FUNCTION__);
	while ($db->next_record()) {
		$aData['data'][] = $db->allRows();
	}
	return $aData;*/
}

function deleteCommitDataById($id)
{/*
	$db = DB::singleton();
	$sql = "DELETE FROM managehtml_commit WHERE commit_id = '{$id}'";
	$db->query($sql, __FUNCTION__);*/
}

?>