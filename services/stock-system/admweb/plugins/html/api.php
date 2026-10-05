<?php
/*
/////////////////////////////////////////////////////////////////////
การตั้งชื่อ function ให้ใช้ชื่อ module เป็นชื่อด้านหน้าและ ตัวขึ้นต้นตัวแรกให้เ็ป็นตัวพิมพ์ใหญ่
ตามด้วย _ และชื่อ function ตามที่ต้องการชื่อด้านหลังจะใช้ชื่ออะไรก็ได้ ตัวขึ้นต้นระหว่างคำให้ใช้เป็นตัวใหญ่

เพื่อป้องกันชื่อ function ซ้ำกันดังนั้นจำเป็นต้องเขียนแบบนี้และเป็น มาตรฐานเดียวกันเพื่อให้การพัฒนาเป็นไปได้ด้วยดี
*/
function ManageHtml_get($name) 
{
	global $lang;
	$langname = ($lang != '') ? $lang : 'th';
	return ManageHtml_getHTMLContent(PATH_UPLOAD . '/html/html_' . $name . '_'.$langname.'.html');
}

function ManageHtml_getHTMLContent($pathAdsHtml) 
{
    if (is_file($pathAdsHtml)) {
        $h = file($pathAdsHtml);
        $h = implode("",$h);
        $h = str_replace("\r","",$h);
        $h = str_replace("  "," ",$h);
        return stripslashes(str_replace("\t","",$h));
    } 
}

function ManageHtml_addCommitData($keysname, $name, $phone='', $email='', $message='')
{
	if (checkDataIsSend($keysname, $phone) == false && $phone != '') {
		$db = DB::singleton();
		$adddate = _TIME_;
		$sql = "INSERT INTO managehtml_commit (
					`commit_id` ,
					`keysname` ,
					`name` ,
					`phone` ,
					`email` ,
					`message` ,
					`adddate` ,
					`readdate` ,
					`is_read`
				) VALUES (
					NULL , 
					'{$keysname}', 
					'{$name}', 
					'{$phone}', 
					'{$email}', 
					'{$message}', 
					'{$adddate}', '0', '0'
				);
			";
		$db->query($sql, __FUNCTION__);
		return true;
	} else {
		return false;
	}
}

function checkDataIsSend($keysname, $phone)
{
	$db = DB::singleton();
	$sql = "SELECT commit_id 
			FROM managehtml_commit 
			WHERE keysname = '{$keysname}' 
			AND phone = '{$phone}' 
			AND is_read = '0';";
	$db->query($sql, __FUNCTION__);
	return ($db->num_rows() > 0) ? true : false;
}
?>