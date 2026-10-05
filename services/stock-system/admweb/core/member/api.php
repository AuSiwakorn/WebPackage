<?php

function api_getAllUserMember($num = 20, $page = 0)
{
	$db = DB::singleton();
	$sql = "
	SELECT u.* , m.mem_id,m.salutation,m.firstname,m.lastname,m.picture,m.email,m.phone,m.mem_code,m.cash,m.address,m.province,m.facebook,m.line
	FROM " . _DBPREFIX_ . "member_user u
    LEFT JOIN " . _DBPREFIX_ . "member_member m ON m.user_id = u.user_id
    WHERE u.user_id != ''
    AND u.status = 'member'
	ORDER BY u.register_date DESC, u.user_id DESC
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

function Func_GetAmountCash($user_id)
{
	global $aQData;
	$db = DB::singleton();
	$sql = "
	SELECT u.*, m.*
	FROM " . _DBPREFIX_ . "member_user u
	LEFT JOIN " . _DBPREFIX_ . "member_member m ON m.user_id = u.user_id
	WHERE u.user_id = '{$user_id}'
	";
	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		$aQData[$md5Q] = $db->allRows();
		return $aQData[$md5Q];
	}
}
/*
function Func_UpdateRegisterWallet($user_id, $wallet)
{
	$db = DB::singleton();
	$sql = "
		UPDATE `"._DBPREFIX_."member_member`
		SET `registerWallet` = '".$wallet."'
		WHERE user_id = '{$user_id}'; ";
	return $db->query($sql, __FUNCTION__);
}*/

function Func_UpdateCash($user_id, $cash)
{
	$db = DB::singleton();
	$sql = "
		UPDATE `" . _DBPREFIX_ . "member_member`
		SET `cash` = '" . $cash . "'
		WHERE user_id = '{$user_id}'; ";
	return $db->query($sql, __FUNCTION__);
}

function Api_updateMemberData($id, $d)
{
	$db = DB::singleton();
	if ($d['password'] != '') {
		//$password = _builpassword($d['password']);
		$password = login_logout::encode_builpassword($d['password']);
		$sql = "
			UPDATE " . _DBPREFIX_ . "member_user
			SET `password` 		= '{$password}'
			WHERE user_id = '{$id}';";
		$db->query($sql, __FUNCTION__);
	}

	$sql = "
		UPDATE " . _DBPREFIX_ . "member_member
		SET `salutation` 		= '{$d['salutation']}',
			`firstname` 		= '{$d['firstname']}',
			`lastname` 			= '{$d['lastname']}',
			`email` 			= '{$d['email']}',
			`phone` 			= '{$d['phone']}',
			`mem_code` 		    = '{$d['mem_code']}',
            `province` 			= '{$d['province']}',
            `address` 			= '{$d['address']}',
            `facebook` 			= '{$d['facebook']}',
            `line` 			= '{$d['line']}',
            `img` 			= '{$d['img']}',
            `about` 			= '{$d['about']}'
		WHERE user_id = '{$id}';";
	$db->query($sql, __FUNCTION__);
	/*
	$sql = "
		UPDATE "._DBPREFIX_."member_member
		SET `bankname` 		= '{$d['bankname']}',
			`bankid` 	    = '{$d['bankid']}',
			`bankusername` 		= '{$d['bankusername']}',
			`country` 		= '{$d['country']}',
			`sortcode` 		= '{$d['sortcode']}',
			`swiftcode` 		= '{$d['swiftcode']}',
			`iban` 		        = '{$d['iban']}'
		WHERE user_id = '{$id}';";
	$db->query($sql, __FUNCTION__);
	*/
}

function  Api_updatePicture($user_id, $iconname = '')
{
	if ($iconname != '') {
		$sql = "
    		UPDATE " . _DBPREFIX_ . "member_member
    		SET `picture` 			= '{$iconname}'
    		WHERE user_id = '{$user_id}';";
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
	}
}

function Member_isUserMemberByEmail($email, $isNot = 0)
{
	$db = DB::singleton();
	$sqlIsNot = ($isNot > 0) ? " AND user_id != '{$isNot}' " : '';
	$sql = "
	SELECT 
	  user_id,
	  firstname,
	  lastname
	FROM
	  " . _DBPREFIX_ . "member_member
	WHERE 
	  email = '{$email}'
	  {$sqlIsNot}
	";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return ($db->num_rows() > 0) ? true : false;
}

function getUserById($id)
{
	global $aQData;
	$sql = "
	SELECT u.user_id, u.username, u.status, u.node_member, u.ipaddress, u.register_date, u.modules, m.*
	FROM " . _DBPREFIX_ . "member_user u
	LEFT JOIN  " . _DBPREFIX_ . "member_member m ON m.user_id = u.user_id
	WHERE u.user_id='{$id}';";
	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		$aQData[$md5Q] = $db->allRows();
		return $aQData[$md5Q];
	}
}

function getUserByUsername($username)
{
	global $aQData;
	$sql = "
	SELECT u.user_id, u.username, u.status
	FROM " . _DBPREFIX_ . "member_user u
	LEFT JOIN  " . _DBPREFIX_ . "member_member m ON m.user_id = u.user_id
	WHERE u.username='{$username}';";
	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		$aQData[$md5Q] = $db->allRows();
		return $aQData[$md5Q];
	}
}
