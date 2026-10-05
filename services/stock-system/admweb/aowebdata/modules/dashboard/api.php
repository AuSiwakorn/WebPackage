<?php
function dashboard_update_online($actionname = '', $url = '')
{
	$actionname = ($actionname != '') ? $actionname : 'หน้าแรก';
	$url = ($url != '') ? $url : '';
	$time = _TIME_;
	$endTime = _TIME_ + (ONLINE_MINUTE * 60);
	$ipaddress = @$_SERVER['REMOTE_ADDR'];

	$oUser = login_logout::getLoginData();
	$uid = @$oUser->user_id;

	$db = DB::singleton();
	$sql = "DELETE FROM " . _DBPREFIX_ . "member_online WHERE onlineEndTime < '{$time}'; ";
	$db->query($sql, __FUNCTION__);

	$sql = "SELECT online_id FROM " . _DBPREFIX_ . "member_online WHERE ipaddress = '{$ipaddress}'; ";
	$db->query($sql, __FUNCTION__);
	if ($db->next_record()) {
		$sql = "
		UPDATE `" . _DBPREFIX_ . "member_online`
		SET `onlineEndTime` = '{$endTime}',
			`actionView` = '{$actionname}',
			`ipaddress` = '{$ipaddress}',
			`url` = '{$url}'
		WHERE `online_id` = '{$db->f('online_id')}'; ";
		$db->query($sql, __FUNCTION__);
	} else {
		$sql = "
		INSERT INTO " . _DBPREFIX_ . "member_online (
			`online_id` ,
			`user_id`,
			`onlineTime` ,
			`onlineEndTime`,
			`actionView` ,
			`ipaddress`,
			`url`
		) VALUES (
			NULL,
			'{$uid}',
			'{$time}',
			'{$endTime}',
			'{$actionname}',
			'{$ipaddress}',
			'{$url}'
		)";
		$db->query($sql, __FUNCTION__);
	}
}
