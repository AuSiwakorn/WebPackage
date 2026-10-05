<?php
function Func_Pic_Corp($filename, $nopic = '', $option = 'w=250')
{
	return ($filename != '') ? URL_UPLOAD . '/piccorp/webroot/img.php?src=' . $filename . '&' . $option . '&crop-to-fit' : $nopic;
}

function Func_Update_MemberPicture($user_id, $picture)
{
	$db = DB::singleton();
	$sql = "
		UPDATE `" . _DBPREFIX_ . "member_member`
		SET `picture` = '" . $picture . "'
		WHERE user_id = '{$user_id}'; ";
	return $db->query($sql, __FUNCTION__);
}

function Func_CountAdmin()
{
	global $aQData;
	$oUser = login_logout::getAdminUsername();
	if ($oUser === 'superadmin') {
		$sql = "SELECT user_id FROM " . _DBPREFIX_ . "member_user WHERE status != 'member'; ";
	} else {
		$sql = "SELECT user_id FROM " . _DBPREFIX_ . "member_user WHERE status != 'member' AND username != 'superadmin'; ";
	}
	$md5 = md5($sql);
	if (isset($aQData[$md5])) {
		return $aQData[$md5];
	}

	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aQData[$md5] = $db->num_rows();
	return $aQData[$md5];
}

function Func_CountMember()
{
	global $aQData;
	$sql = "SELECT user_id FROM " . _DBPREFIX_ . "member_user WHERE status = 'member'; ";
	$md5 = md5($sql);
	if (isset($aQData[$md5])) {
		return $aQData[$md5];
	}

	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aQData[$md5] = $db->num_rows();
	return $aQData[$md5];
}

function Func_checkEmailExist($email = '')
{
	$db = DB::singleton();
	$sql = "
	SELECT user_id
	FROM " . _DBPREFIX_ . "member_member
	WHERE email = '{$email}';
	";
	$db->query($sql, __FUNCTION__);
	return $db->next_record() ? true : false;
}

function Func_getAllSubUserMember($uid)
{
	global $aQData;
	$db = DB::singleton();
	$sql = "SELECT u.* , m.*
	FROM " . _DBPREFIX_ . "member_user u
	LEFT JOIN " . _DBPREFIX_ . "member_member m ON m.user_id = u.user_id
	WHERE u.user_id IS NOT NULL
	AND u.node_member = '{$uid}'
	AND u.status = 'member'
	ORDER BY u.user_id DESC
	";
	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$aData = array();
		$aData['num_rows'] = $db->num_rows();
		$aData['sql']	= $sql;
		$db->query($sql, __FUNCTION__);
		while ($db->next_record()) {
			$aData['data'][] = $db->allRows();
		}
		$aQData[$md5Q] = $aData;
		return $aData;
	}
}

function Func_checkMemberCodeExist($mem_code = '')
{
	if ($mem_code == '') {
		return true;
	}
	$db = DB::singleton();
	$sql = "
	SELECT mem_code
	FROM " . _DBPREFIX_ . "member_member
	WHERE mem_code = '{$mem_code}';
	";
	$db->query($sql, __FUNCTION__);
	return $db->next_record() ? true : false;
}

function Func_checkUsernameExist($username = '')
{
	$db = DB::singleton();
	echo $sql = "
	SELECT user_id
	FROM " . _DBPREFIX_ . "member_user
	WHERE username = '{$username}';
	";
	$db->query($sql, __FUNCTION__);
	return $db->next_record() ? true : false;
}

function Func_insertAdminMember($username, $password, $status = '', $modules = 'all', $d = array())
{
	$db = DB::singleton();
	$ip = getIP();
	$time = _TIME_;
	$password = Func_encode_builpassword($password);
	$d['node_member'] = (@$d['node_member'] != '') ? $d['node_member'] : 0;
	$sql = "INSERT INTO `" . _DBPREFIX_ . "member_user`
	(`user_id`, `username`, `password`, `status`, `node_member`,`ipaddress`, `register_date`, `modules`)  VALUES
	(NULL, '{$username}', '{$password}',  '{$status}','{$d['node_member']}', '{$ip}','{$time}','{$modules}');";
	$ok = $db->query($sql, __FUNCTION__);
	$id = ($ok) ? $db->getInsertID() : false;

	$d['picture'] 				= isset($d['picture']) ? $d['picture'] : '';
	if ($id != false) {
		$sql = "INSERT INTO `" . _DBPREFIX_ . "member_member`
		(`mem_id`,`user_id`,`salutation`,`firstname`,`lastname`,`picture`,`email`,`phone`,`mem_code`) VALUES
		(NULL,'{$id}','{$d['salutation']}','{$d['firstname']}','{$d['lastname']}','{$d['picture']}','{$d['email']}','{$d['phone']}','{$d['mem_code']}');";
		$ok = $db->query($sql, __FUNCTION__);
		return ($ok) ? $db->getInsertID() : false;
	} else {
		return false;
	}
}

function Func_check_namecard($pid)
{
	if (strlen($pid) != 13) {
		return false;
	}

	for ($i = 0, $sum = 0; $i < 12; $i++) {
		$sum += (int)($pid[$i]) * (13 - $i);
	}

	if ((11 - ($sum % 11)) % 10 == (int)($pid[12])) {
		return true;
	}
	return false;
}
