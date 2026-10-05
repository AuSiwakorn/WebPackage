<?php
/*
/////////////////////////////////////////////////////////////////////
การตั้งชื่อ function ให้ใช้ชื่อ module เป็นชื่อด้านหน้าและ ตัวขึ้นต้นตัวแรกให้เ็ป็นตัวพิมพ์ใหญ่
ตามด้วย _ และชื่อ function ตามที่ต้องการชื่อด้านหลังจะใช้ชื่ออะไรก็ได้ ตัวขึ้นต้นระหว่างคำให้ใช้เป็นตัวใหญ่

เพื่อป้องกันชื่อ function ซ้ำกันดังนั้นจำเป็นต้องเขียนแบบนี้และเป็น มาตรฐานเดียวกันเพื่อให้การพัฒนาเป็นไปได้ด้วยดี
*/

function plugin_banner_get_banner_by_keys($keysname='')
{
	$db = DB::singleton();
	$sql = "SELECT * FROM `"._DBPREFIX_."banner` WHERE keysname = '{$keysname}' AND status = '0' ORDER BY sort ASC; ";
	$db->query($sql, __FUNCTION__);
	$aData = array();
	while ($db->next_record()) {
		$aData[] = $db->allRows();
	}
	return $aData;
}

?>