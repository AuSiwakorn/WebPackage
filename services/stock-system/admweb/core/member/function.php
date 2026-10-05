<?php
function _updateMemberPicture($user_id, $picture)
{
	$db = DB::singleton();
	$sql = "SELECT picture FROM " . _DBPREFIX_ . "member_member WHERE user_id = '{$user_id}'; ";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	if ($db->f('picture') != '') {
		if (is_file(PATH_UPLOAD . '/' . $db->f('picture'))) {
			unlink(PATH_UPLOAD . '/' . $db->f('picture'));
		}
	}

	$sql = "
		UPDATE `" . _DBPREFIX_ . "member_member` 
		SET `picture` = '" . $picture . "'
		WHERE user_id = '{$user_id}'; ";
	return $db->query($sql, __FUNCTION__);
}

/* --------------------------- ADMIN -------------------------- */

function checkUsernameIsExists($username)
{
	$db = DB::singleton();
	$sql = "SELECT user_id FROM `" . _DBPREFIX_ . "member_user` WHERE username = '{$username}';";
	$db->query($sql, __FUNCTION__);
	return ($db->num_rows() > 0) ? true : false;
}

function Func_CheckUser_Exists($id, $username)
{
	$db = DB::singleton();
	$sql = "SELECT user_id FROM `" . _DBPREFIX_ . "member_user` WHERE username = '{$username}' AND user_id != '{$id}';";
	$db->query($sql, __FUNCTION__);
	return ($db->num_rows() > 0) ? true : false;
}

function checkCanDeleteAdminOk()
{
	$db = DB::singleton();
	$sql = "SELECT user_id FROM `" . _DBPREFIX_ . "member_user` WHERE status = 'admin';";
	$db->query($sql, __FUNCTION__);
	return ($db->num_rows() > 1) ? true : false;
}

function checkEmailExist($email = '')
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

function checkUsernameExist($username = '')
{
	$db = DB::singleton();
	$sql = "
	SELECT user_id 
	FROM " . _DBPREFIX_ . "member_user
	WHERE username = '{$username}';
	";
	$db->query($sql, __FUNCTION__);
	return $db->next_record() ? true : false;
}

function getAllAdminMember()
{
	$db = DB::singleton();
	$sql = "SELECT * FROM `" . _DBPREFIX_ . "member_user` WHERE status != 'member' ORDER BY user_id";
	$db->query($sql, __FUNCTION__);
	$aData = array();
	while ($db->next_record()) {
		$aData['data'][] = $db->allRows();
	}
	return $aData;
}

function _member_getMemberLogs($num = 50, $page = 1)
{ //keeree 25/8/2015 165
	$db = DB::singleton();
	$sql = "
	SELECT 	ma.logs_id,m.user_id, m.firstname, m.lastname, ma.logs_time, ma.member_ip
	FROM " . _DBPREFIX_ . "member_member m,
	     " . _DBPREFIX_ . "member_member_login_logs ma
	WHERE m.user_id = ma.user_id
	ORDER BY ma.logs_id DESC
	";
	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
	return $aData;
}

function _member_getMemberLogsByID($id)
{
	$db = DB::singleton();
	$sql = "
	SELECT 	* FROM " . _DBPREFIX_ . "member_admin_login_logs 
	WHERE user_id = '{$id}'";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->allRows();
}

function _builpassword($pass)
{
	return Func_encode_builpassword($pass);
}

function insertAdminMember($username, $password, $status, $modules = 'all', $d = array())
{
	////เต๋าลบออก เปลี่ยนไปใช้ DB_Add
}

function getAdminUserById($id)
{
	$db = DB::singleton();
	$sql = "
SELECT u.user_id, u.username, u.status, u.	ipaddress, u.register_date, u.modules, u.preset_id, m.firstname, m.lastname, m.picture, m.email, m.phone
FROM " . _DBPREFIX_ . "member_user u
LEFT JOIN  " . _DBPREFIX_ . "member_member m ON m.user_id = u.user_id
WHERE u.user_id='{$id}';";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->allRows();
}

function updataDataMemberAdmin($id, $firstname, $lastname, $email, $phone, $username, $password, $status, $modules = 'all')
{
	DB_UP('member_member', [
		'firstname' => $firstname,
		'lastname' => $lastname,
		'email' => $email,
		'phone' => $phone
	], ['user_id' => $id]);

	DB_UP('member_user', [
		'username' => $username,
		'status' => $status,
		'modules' => $modules
	], ['user_id' => $id]);

	if ($password != '') {
		$password = _builpassword($password);
		DB_UP('member_user', ['password' => $password], ['user_id' => $id]);
	}
}

function Func_Delete_Online_User($user_id)
{
	$db = DB::singleton();
	$sql = "DELETE FROM " . _DBPREFIX_ . "member_online WHERE user_id = '{$user_id}';";
	$db->query($sql, __FUNCTION__);
	return true;
}

/* ----------------------- USER --------------------- */

function deleteUserMember($id)
{
	$db = DB::singleton();
	$sql = "DELETE FROM " . _DBPREFIX_ . "member_member WHERE user_id='{$id}'; ";
	$db->query($sql, __FUNCTION__);
	__hooks_delete_member($id);
}

/* ----------------------- HOOKS --------------------- */

function __hooks_delete_member($id)
{
	$db = DB::singleton();
	$sql = array();
	$sql[] = "DELETE FROM " . _DBPREFIX_ . "member_member WHERE user_id='{$id}'; ";
	//$sql[] = "DELETE FROM "._DBPREFIX_."course_billing WHERE user_id='{$id}'; ";
	//$sql[] = "DELETE FROM "._DBPREFIX_."member_address WHERE user_id='{$id}'; ";
	//$sql[] = "DELETE FROM "._DBPREFIX_."member_address_bill WHERE user_id='{$id}'; ";
	//$sql[] = "DELETE FROM "._DBPREFIX_."member_address_card WHERE user_id='{$id}'; ";
	//$sql[] = "DELETE FROM "._DBPREFIX_."member_member_login_logs WHERE user_id='{$id}'; ";
	foreach ($sql as $k => $v) {
		$db->query($v);
	}
}

/* ----------------------- HOOKS --------------------- */

function getAllUserMember($num = 20, $page = 0)
{
	$db = DB::singleton();
	$sql = " SELECT u.* , m.salutation, m.firstname, m.lastname, m.picture, m.email, m.phone
	FROM " . _DBPREFIX_ . "member_user u
    LEFT JOIN " . _DBPREFIX_ . "member_member m ON m.user_id = u.user_id
    WHERE u.user_id IS NOT NULL
    AND u.status = 'member'
	ORDER BY u.register_date DESC, u.user_id DESC ";
	$db->query($sql, __FUNCTION__);
	$aData = array();
	$aData['num_rows'] = $db->num_rows();
	$aData['maxpage']	= ($num > 0) ? ceil($aData['num_rows'] / $num) : 0;
	$aData['nextpage']	= (($page + 1) <= $aData['maxpage']) ? ($page + 1) : $aData['maxpage'];
	$aData['backpage']	= (($page - 1) > 1) ? ($page - 1) : 1;
	if ($num < $aData['num_rows']) {
		if ($num > 0) {
			$start = ($page == 0 || $page == 1) ? 0 : (($num * $page) - $num);
			$sql .= ($num > 0) ? ' LIMIT ' . $start . ' , ' . $num . ' ;' : ';';
		}
		$db->query($sql, __FUNCTION__);
	}
	$aData['sql'] = $sql;
	while ($db->next_record()) {
		$res = $db->allRows();
		$res['password'] = '****';
		$aData['data'][] = $res;
	}
	return $aData;
}

function getAllSubUserMember($workpart_id, $num = 20, $page = 0)
{
	$db = DB::singleton();
	$sql = "
	SELECT u.* , m.*
	FROM " . _DBPREFIX_ . "member_user u
    LEFT JOIN " . _DBPREFIX_ . "member_member m ON m.user_id = u.user_id
    WHERE u.user_id IS NOT NULL
    AND u.status = 'member'
	ORDER BY u.user_id DESC
	";
	$db->query($sql, __FUNCTION__);
	$aData = array();
	$aData['num_rows'] = $db->num_rows();
	$aData['maxpage']	= ($num > 0) ? ceil($aData['num_rows'] / $num) : 0;
	$aData['nextpage']	= (($page + 1) <= $aData['maxpage']) ? ($page + 1) : $aData['maxpage'];
	$aData['backpage']	= (($page - 1) > 1) ? ($page - 1) : 1;
	if ($num < $aData['num_rows']) {
		if ($num > 0) {
			$start = ($page == 0 || $page == 1) ? 0 : (($num * $page) - $num);
			$sql .= ($num > 0) ? ' LIMIT ' . $start . ' , ' . $num . ' ;' : ';';
		}
		$db->query($sql, __FUNCTION__);
	}
	$aData['sql'] = $sql;
	while ($db->next_record()) {
		$res = $db->allRows();
		$res['password'] = '****';
		$aData['data'][] = $res;
	}

	return $aData;
}

function getSearchUserMember($keysword = '', $num = 20, $page = 0)
{
	$sql = "
	SELECT u.* , m.*
	FROM " . _DBPREFIX_ . "member_user u
    LEFT JOIN " . _DBPREFIX_ . "member_member m ON m.user_id = u.user_id
    WHERE u.user_id IS NOT NULL
    AND u.status = 'member'
	AND (
		m.user_id LIKE '{$keysword}%'
		OR m.firstname LIKE '{$keysword}%'
		OR m.lastname LIKE '%{$keysword}%'
		OR m.email LIKE '%{$keysword}%'
	)
	ORDER BY u.user_id DESC
	";

	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
	return $aData;
}

function getUserMemberById($id)
{
	$db = DB::singleton();
	$sql = "
	SELECT u.*, m.*
	FROM " . _DBPREFIX_ . "member_user u 
	LEFT JOIN " . _DBPREFIX_ . "member_member m ON m.user_id = u.user_id
	WHERE u.user_id = '{$id}'
	";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	$a = $db->allRows();
	$a['pw'] = $a['password'];
	unset($a['password']);
	return $a;
}

function updatePassWord($id, $d)
{
	if ($d['password'] != '') {
		$password = _builpassword($d['password']);
		$sql = "UPDATE " . _DBPREFIX_ . "member_user SET `password` 		= '{$password}' WHERE user_id = '{$id}';";
		$db = DB::singleton();
		$db->query($sql);
	}
}

function updateMemberData($id, $d)
{
	$db = DB::singleton();
	if ($d['password'] != '') {
		$password = _builpassword($d['password']);
		$sql = "
			UPDATE " . _DBPREFIX_ . "member_user
			SET `password` 		= '{$password}'
			WHERE user_id = '{$id}';";
		$db->query($sql, __FUNCTION__);
	}
	$sql = "
			UPDATE " . _DBPREFIX_ . "member_user
			SET `node_member` 		= '{$d['nodemember']}'
			WHERE user_id = '{$id}';";
	$db->query($sql, __FUNCTION__);

	if ($d['username'] != '') {
		$ch = Func_CheckUser_Exists($id, $d['username']);
		if ($ch != true) {
			$sql = "
					UPDATE " . _DBPREFIX_ . "member_user
					SET `username` 		= '{$d['username']}'
					WHERE user_id = '{$id}';";
			$db->query($sql, __FUNCTION__);
		}
	}
	$sql = "
		UPDATE " . _DBPREFIX_ . "member_member 
		SET `salutation` 		= '{$d['salutation']}',
			`firstname` 		= '{$d['firstname']}',
			`lastname` 			= '{$d['lastname']}',
			`email` 			= '{$d['email']}',
			`phone` 			= '{$d['phone']}',
			`mem_code` 		    = '{$d['mem_code']}'
		WHERE user_id = '{$id}';";
	$db->query($sql, __FUNCTION__);
}

function updateMemberData2($id, $d)
{
	$db = DB::singleton();
	if ($d['username'] != '') {
		$ch = Func_CheckUser_Exists($id, $d['username']);
		if ($ch != true) {
			$sql = "
					UPDATE " . _DBPREFIX_ . "member_user
					SET `username` 		= '{$d['username']}'
					WHERE user_id = '{$id}';";
			$db->query($sql, __FUNCTION__);
		}
	}
	$sql = "
		UPDATE " . _DBPREFIX_ . "member_member 
		SET `salutation` 		= '{$d['salutation']}',
			`firstname` 		= '{$d['firstname']}',
			`lastname` 			= '{$d['lastname']}',
			`email` 			= '{$d['email']}',
			`phone` 			= '{$d['phone']}',
			`mem_code` 		    = '{$d['mem_code']}',
			`birthday` 			='{$d['birthday']}'
		WHERE user_id = '{$id}';";
	$db->query($sql, __FUNCTION__);
}

/////////////////////////////////////////////////

function _get_member_address($id)
{
	$db = DB::singleton();
	$sql = "SELECT * FROM " . _DBPREFIX_ . "member_address WHERE user_id = '{$id}'; ";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return ($db->num_rows() > 0) ? $db->allRows() : array();
}

function _update_address($id, $aData)
{
	$a = _get_member_address($id);
	if (count($a) > 0) {
		$sql = "
			UPDATE `" . _DBPREFIX_ . "member_address` 
			SET `home_num` = '" . $aData['home_num'] . "', 
				`home_moo` = '" . $aData['home_moo'] . "', 
				`soi` = '" . $aData['soi'] . "',
				`home_name` = '" . $aData['home_name'] . "',
				`road` = '" . $aData['road'] . "',
				`amphoe` = '" . $aData['amphoe'] . "',
				`tambon` = '" . $aData['tambon'] . "',
				`zipcode` = '" . $aData['zipcode'] . "',
				`province_id` = '" . $aData['province_id'] . "',
				`phone` = '" . $aData['phone'] . "',
				`tel` = '" . $aData['tel'] . "',
				`tel_office` = '" . $aData['tel_office'] . "',
				`tel_office_next` = '" . $aData['tel_office_next'] . "'
			WHERE user_id = '{$id}'; ";
		$db = DB::singleton();
		return $db->query($sql, __FUNCTION__);
	} else {
		_register_member_address($id, $aData);
	}
}

function _register_member_address($id, $d)
{
	$sql = "
	INSERT INTO " . _DBPREFIX_ . "member_address (
		`member_address_id` ,
		`user_id` ,
		`home_num` ,
		`home_moo` ,
		`soi` ,
		`home_name` ,
		`road` ,
		`amphoe` ,
		`tambon` ,
		`zipcode` ,
		`province_id` ,
		`phone` ,
		`tel` ,
		`tel_office` ,
		`tel_office_next`
	) VALUES (
		NULL, 
		'{$id}', 
		'{$d['home_num']}', 
		'{$d['home_moo']}', 
		'{$d['soi']}', 
		'{$d['home_name']}', 
		'{$d['road']}', 
		'{$d['amphoe']}', 
		'{$d['tambon']}', 
		'{$d['zipcode']}', 
		'{$d['province_id']}', 
		'{$d['phone']}', 
		'{$d['tel']}', 
		'{$d['tel_office']}', 
		'{$d['tel_office_next']}'
	)";
	$db = DB::singleton();
	$ok = $db->query($sql, __FUNCTION__);
	return ($ok) ? $db->getInsertID() : false;
}

///////////////////////////////////////////////

function _get_member_address_bill($id)
{
	$db = DB::singleton();
	$sql = "SELECT * FROM " . _DBPREFIX_ . "member_address_bill WHERE user_id = '{$id}'; ";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return ($db->num_rows() > 0) ? $db->allRows() : array();
}

///////////////////////////////////////////////

function sendMailToUser($email, $title = 'no title', $message = 'no message')
{
	$headers = "From: Admin:" . DOMAIN_NAME . " <" . ADMIN_EMAIL . ">\r\n";
	$headers .= "MIME-Version: 1.0\r\n";
	$headers .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
	$headers .= "Content-Transfer-Encoding: 7bit\r\n";
	$content = "\n\n" .
		$message .
		"\n\n ================================= \n" .
		"Time : " . strTimeFormat() . "\n";
	return (mail($email, $title, $content, $headers)) ? true : false;
}

function _member_check_namecard($pid)
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

function TWOFAenable($id)
{
	$aUser = DB_GET('member_user', ['user_id' => $id['user_id'],]);
	if ($aUser['twofa_status'] == 'off' && empty($aUser['twofa_secret'])) {
		$twfa = new PHPGangsta_GoogleAuthenticator();
		$secret = $twfa->createSecret();
		$gettwfa = DB_UP(
			'member_user',
			['twofa_status' => 'pending', 'twofa_secret' => $secret],
			['user_id' => $id['user_id']]
		);
		return $gettwfa ? $secret : false;
	} elseif ($aUser['twofa_status'] == 'pending' && !empty($aUser['twofa_secret'])) {
		return $aUser['twofa_secret'];
	} else {
		return false;
	}
}
function TWOFAverify($id, $code)
{
	$twfa = new PHPGangsta_GoogleAuthenticator();
	$gettwfa = DB_GET('member_user', [
		'user_id'      => $id['user_id'],
	]);
	if (!$gettwfa || empty($gettwfa['twofa_secret'])) {
		return false;
	}
	if ($twfa->verifyCode($gettwfa['twofa_secret'], $code, 2)) {
		DB_UP('member_user', ['twofa_status' => 'on'], ['user_id' => $id['user_id']]);
		return true;
	}
	return false;
}
function TWOFAqrcodegen($secret)
{
	$twfa = new PHPGangsta_GoogleAuthenticator();
	$qrCodeUrl = $twfa->getQRCodeGoogleUrl(DOMAIN_NAME, $secret);
	return $qrCodeUrl;
}

function TWOAunable($id)
{
	$gettwfa = DB_UP(
		'member_user',
		['twofa_status' => 'off', 'twofa_secret' => ''],
		['user_id' => $id['user_id']]
	);
	return $gettwfa;
}

require_once __DIR__ . '/function_preset.php';
