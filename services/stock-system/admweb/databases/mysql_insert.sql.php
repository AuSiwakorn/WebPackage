<?php
$time = _TIME_;
$ipaddress = '';
if (isset($_SERVER['HTTP_CLIENT_IP'])) {
	$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
} else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
	$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
} else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
	$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
} else if (isset($_SERVER['HTTP_FORWARDED'])) {
	$ipaddress = $_SERVER['HTTP_FORWARDED'];
} else if (isset($_SERVER['REMOTE_ADDR'])) {
	$ipaddress = $_SERVER['REMOTE_ADDR'];
} else {
	$ipaddress = 'UNKNOWN';
}


$sqlArray[] = "INSERT INTO `" . _DBPREFIX_ . "member_user` 
(`user_id`, `username`, `password`, `status`, `node_member`,`ipaddress`, `twofa_status`,`twofa_secret`, `register_date`, `modules`)  VALUES 
(1, 'superadmin', '{$pass}',  'admin','0', '{$ipaddress}','off','','{$time}','all' );";

$sqlArray[] = "INSERT INTO `" . _DBPREFIX_ . "member_member` 
(`mem_id`,`user_id`,`salutation`,`firstname`,`lastname`,`picture`,`email`,`phone`,`birthday`) VALUES 
(1,1,'นาย','Aosoft','DefaultLogin','','info@aosoft.co.th','0891400008','0');";

$sqlArray[] = "INSERT INTO `" . _DBPREFIX_ . "member_user` 
(`user_id`, `username`, `password`, `status`, `node_member`,`ipaddress`, `register_date`, `modules`)  VALUES 
(2, 'admin', '{$pass}',  'admin','0', '{$ipaddress}','{$time}','all' );";

$sqlArray[] = "INSERT INTO `" . _DBPREFIX_ . "member_member` 
(`mem_id`,`user_id`,`salutation`,`firstname`,`lastname`,`picture`,`email`,`phone`,`birthday`) VALUES 
(2,2,'','admin','ao','','','','0');";
