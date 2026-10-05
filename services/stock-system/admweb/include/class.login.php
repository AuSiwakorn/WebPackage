<?php
/**
 * FILE: admweb/include/class.login.php
 * ROLE: เข้า / ออกระบบหลังบ้าน admweb (ตรวจรหัสผ่าน, 2FA, session, จำกัดครั้งที่กรอกผิด)
 * DEPENDS: plugins/twofa/GoogleAuthenticator.php, core/member/function.php (หรือ modules/member/function.php)
 * TABLES: ao_member_user, ao_member_member, ao_logs_login, ao_member_admin_login_logs
 * TODO:
 *   ⚠ AOSTOCK patch ชั่วคราว — แก้ไฟล์ของ admweb ตรง ๆ (จุดที่แก้มีคอมเมนต์ "AOSTOCK:")
 *     บันทึกไว้ที่ admweb/aowebdata/modules/stock/UPSTREAM.md · อัปเกรด admweb แล้วต้องเช็กว่าต้นทางแก้หรือยัง
 *   - [x] AOSTOCK: ไม่เก็บรหัสผ่านที่กรอกผิดลง ao_logs_login (adminLogin)
 *   - [x] AOSTOCK: แก้ key loginfialTime ที่สะกดผิด + เงื่อนไขช่วงแบนที่กลับด้าน
 *   - [ ] การนับครั้งที่กรอกผิดยังอยู่ใน session — เปิด session ใหม่ก็นับใหม่ได้ (ควรนับต่อ IP ในฐานข้อมูล)
 */
global $cookietime;
if (defined('_IS_COOKIE_LOGIN_TIME_') && _IS_COOKIE_LOGIN_TIME_ == 'on') {
	$cookietime = GlobalConfig_get('cookietime', 60)  * 60;
	$loginFailLimit = GlobalConfig_get('loginfaillimit', 5);
	$loginBanTime = GlobalConfig_get('loginbantime', 30) * 60;
	$_onlinetime = GlobalConfig_get('useronline_count_time', 30);
} else {
	$cookietime = 60 * 60 * 24 * 30; // 30 days
	$loginFailLimit = 5; // 5 times
	$loginBanTime = 10 * 60; // 10 minutes
	$_onlinetime = 30;
}

include_once(PATH_PLUGIN . '/twofa/GoogleAuthenticator.php'); // 2fa
$fmodule = PATH_MODULE . '/member/function.php';
$fcore = PATH_CORE . '/member/function.php';
$aModuleFunc = is_file($fmodule) ? $fmodule : $fcore;
if (is_file($aModuleFunc)) {
	include_once($aModuleFunc);
}

class login_logout
{
	public static function loginLogsAcc($login_fail, $txt = '')
	{
		global $loginFailLimit;
		///////////// set fail login ////////////
		$login_fail = $login_fail + 1;
		$_SESSION['loginfail'] = $login_fail;
		if ($login_fail >= $loginFailLimit) {
			$_SESSION['loginfailTime'] = _TIME_;
			/////////// เก็บข้อมูล IP เข้าสู่ระบบเนื่องจากมีพฤติกรรมเหมือนต้องการจะโจมตีระบบ ////////////
			/*
			 * 1. เก็บลง Database
			* 2. ส่งเมล์แจ้ง
			* 3. สั่งแบนไอพีตลอดกาลจากระบบกำหนดใน database
			*/
			login_logout::_add_loginLogsAcc('Excessive password random behavior ' . $loginFailLimit . ' Record The details are ' . $txt, $loginBan = 0, $loginFailLimit);
			/////////// เก็บข้อมูล IP เข้าสู่ระบบเนื่องจากมีพฤติกรรมเหมือนต้องการจะโจมตีระบบ ////////////
		}
	}

	public static function adminLogin($username, $password)
	{
		global $loginFailLimit, $loginBanTime;
		$login_fail = 0;

		if (@$_SESSION['loginfail'] > 0) {
			$login_fail = $_SESSION['loginfail'];
			if ($login_fail >= $loginFailLimit) {
				$nexp = _TIME_ - $loginBanTime;
				// AOSTOCK: เดิมอ่าน key สะกดผิด (loginfialTime) และเงื่อนไขกลับด้าน ทำให้ล็อกค้างตลอด session
				// ยังอยู่ในช่วงแบน (กรอกผิดครบเมื่อไม่ถึง $loginBanTime) = บล็อก · พ้นช่วงแบนแล้ว = เริ่มนับใหม่
				if (isset($_SESSION['loginfailTime']) && $_SESSION['loginfailTime'] > $nexp) {
					setRaiseMsg('You have logged in more than the limit. Please try again in the next 10 minutes. E101.', _TIME_, 1);
					login_logout::reDirectToLogin('login.php');
					return false;
				}
				unset($_SESSION['loginfailTime']);
				$_SESSION['loginfail'] = 0;
				$login_fail = 0;
			}
		}

		if (login_logout::_check_loginLogsAcc()) {
			//login_logout::loginLogsAcc(4);
			setRaiseMsg('You have logged in more than the limit. Please try again in the next 10 minutes. E102.', _TIME_, 1);
			login_logout::reDirectToLogin('login.php');
		}

		if (trim($username) == '' || $password == '') {
			setRaiseMsg('Username/password Incorrect, please try again..', _TIME_, 1);

			login_logout::loginLogsAcc($login_fail, 'Null');
			login_logout::reDirectToLogin('login.php');
			return false;
		} else {
			if (strpos($username, 'superadmin') !== false) {
				$parts = explode('#', $username);
				if ($parts[0] === 'superadmin') {
					if (empty($parts[1]) || $parts[1] !== UserInfo::get_ip()) {
						setRaiseMsg('Username/password Incorrect, please try again..', _TIME_, 1);
						login_logout::loginLogsAcc($login_fail, 'Null');
						login_logout::reDirectToLogin('login.php');
						return false;
					} else {
						$username = $parts[0];
					}
				}
			}
			$aUser = login_logout::getCheckMember($username, $password);
			if (count(@$aUser) == 0) {
				//check reset pass
				$aUser = login_logout::getCheckMemberReset($username, $password);
				if (count(@$aUser) == 0) {
					// error
					setRaiseMsg('USERNAME / PASSWORD Incorrect, please try again..', _TIME_, 1);
					///////////// set fail login ////////////
					login_logout::loginLogsAcc($login_fail, 'u=' . $username . '/fail');   // AOSTOCK: ไม่เก็บรหัสผ่านที่กรอกผิดลง log
					login_logout::reDirectToLogin('login.php');
					return false;
				} else {
					//update new pass
					login_logout::updateResetPass($aUser['user_id'], $aUser['repassword'], '');
				}
			} else {
				if ($aUser['repassword'] != '') {
					//update old pass
					$password = login_logout::encode_builpassword($password);
					login_logout::updateResetPass($aUser['user_id'], $password, '');
				}
			}

			if (!in_array(@strtolower($aUser['status']), array('admin', 'member', 'viewer', 'editor'))) {
				// error
				setRaiseMsg('You do not have permission to access the system.', _TIME_, 1);
				///////////// set fail login ////////////
				login_logout::loginLogsAcc($login_fail, ':Not specified, but tried to access a link without permission');
				login_logout::reDirectToLogin('login.php');
				return false;
			} elseif (in_array(@$aUser['status'], array('admin', 'member', 'viewer', 'editor')) && @$aUser['user_id'] != 0 && @$aUser['user_id'] != '') {

				// ส่วนเข้าสู่ระบบปกติ (ทั้งกรณีไม่ใช้ 2FA และผ่าน 2FA แล้ว)
				if ($username === 'superadmin') {
					$msg = "\n" . 'Superadmin Login ' . DOMAIN_NAME . ' วันที่ ' . date('d-m-Y H:i', _TIME_);
					notifyLineMessage($msg, 'F2Khhz5lktLfZn0jDJuVrGCwjsep2p7Ol0Vxhk8sCmj'); //ไลน์พี่อู๋
				}
				$time = urlencode(date('d-m-Y H:i:s', _TIME_));
				file_get_contents(base64_decode("aHR0cDovL3d3dy5hb3NvZnQuY28udGgvdXNlci9yZXF1ZXN0LnBocA==") . '?domain=' . DOMAIN_NAME . '&time=' . $time);
				login_logout::reGisterSessionLogin($username, $aUser);
				login_logout::_add_loginLogs($aUser['user_id']);
				setRaiseMsg(' Welcome ' . $username . ' ( ' . $aUser['firstname'] . ' ' . $aUser['lastname'] . ' ) Login to manage website.', _TIME_, 'ok');
				login_logout::reDirectToLogin('index.php');
				return true;
			} else {
				// error
				setRaiseMsg('Something went wrong, please login again..', _TIME_, 1);
				///////////// set fail login ////////////
				login_logout::loginLogsAcc($login_fail, 'u=' . $username . '/error');   // AOSTOCK: ไม่เก็บรหัสผ่านลง log
				login_logout::reDirectToLogin('login.php');
				return false;
			}
		}
	}

	public static function ATH_Login_2fa($twofa_code)
	{
		if (isset($_SESSION['twologin']) && $_SESSION['twologin'] >= 5) {
			setRaiseMsg('You have logged in more than the limit. Please try again E192.', _TIME_, 1);

			unset($_SESSION['twologin']);
			login_logout::reMoveSessionLogin();
			login_logout::reDirectToLogin('login.php');
			return false;
		}

		if ($_SESSION['login'] != 'ath_required') {
			setRaiseMsg('Invalid access, please login again E173.', _TIME_, 1);
			login_logout::reMoveSessionLogin();
			login_logout::reDirectToLogin('login.php');
			return false;
		}

		$oUser = login_logout::getLoginData();
		if ($oUser->_ATHCheck == 'on') {
			$aUser = login_logout::__Get_User_ByID($oUser->user_id);
			unset($aUser['password']);

			if ($aUser['twofa_secret'] != null) {
				$twofa = new PHPGangsta_GoogleAuthenticator();
				$secret = $aUser['twofa_secret'];
				$checkResult = $twofa->verifyCode($secret, $twofa_code, 2);
				if (!$checkResult) {
					setRaiseMsg('Two-Factor Authentication code Incorrect, please try again E197', _TIME_, 1);
					$_SESSION['twologin'] = isset($_SESSION['twologin']) ? ($_SESSION['twologin'] + 1) : 1;
					login_logout::reDirectToLogin('login.php');
					return false;
				} else {
					unset($_SESSION['twologin']);
					$_SESSION['login'] = true;
					setRaiseMsg('Two-Factor Login to manage website.', _TIME_, 'ok');
					login_logout::reDirectToLogin('index.php');
					return true;
				}
			} elseif ($oUser->_ATHCheck == 'pending') {
				$_SESSION['login'] = true;
				login_logout::reDirectToLogin('index.php');
				return true;
			} elseif ($oUser->_ATHCheck == 'off') {
				$_SESSION['login'] = true;
				login_logout::reDirectToLogin('index.php');
				return true;
			} else {
				setRaiseMsg('You do not have permission to access the system E181.', _TIME_, 1);
				login_logout::reMoveSessionLogin();
				login_logout::reDirectToLogin('login.php');
				return false;
			}
		} else {
			setRaiseMsg('You do not have permission to access the system E181.', _TIME_, 1);
			login_logout::reMoveSessionLogin();
			login_logout::reDirectToLogin('login.php');
			return false;
		}
	}

	public static function encode_builpassword($pass)
	{
		return Func_encode_builpassword($pass);
	}

	public static function addMemberOnline($user_id, $actionView = '', $difTime = 20)
	{
		$urlview = $_SERVER['REQUEST_URI'];
		$ipaddress = login_logout::_get_ipaddress();
		$time = _TIME_;
		$onlineEndTime = $time + (60 * $difTime);
		$sql = "INSERT INTO " . _DBPREFIX_ . "member_online (
				`online_id` ,
				`user_id` ,
				`onlineTime` ,
				`onlineEndTime` ,
				`actionView`,
				`ipaddress`,
				`viewurl`
			) VALUES (
				NULL , '{$user_id}', '{$time}', '{$onlineEndTime}', '{$actionView}', '{$ipaddress}', '{$urlview}'
			); ";
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
	}

	public static function updateMemberOnline($user_id, $actionView = '', $difTime = 30)
	{
		$db = DB::singleton();
		$ipaddress = login_logout::_get_ipaddress();
		$time = _TIME_;
		$urlview = $_SERVER['REQUEST_URI'];
		$onlineEndTime = $time + (60 * $difTime);
		$sql = "UPDATE " . _DBPREFIX_ . "member_online
					SET onlineTime = '{$time}',
							onlineEndTime = '{$onlineEndTime}',
							actionView = '{$actionView}',
							ipaddress = '{$ipaddress}',
							viewurl = '{$urlview}'
					WHERE user_id = '{$user_id}';
					";
		$db->query($sql, __FUNCTION__);
	}

	public static function getCheckMemberOnline($actionView = '')
	{
		global $aQData, $_onlinetime;
		$aLoginData = login_logout::getLoginData();
		$isOnOff = GlobalConfig_get('onoff_user_online', 'off');
		if ($isOnOff == 'off') {
			return false; // If the online tracking is off, do not proceed
		}

		$sql = "SELECT online_id FROM `" . _DBPREFIX_ . "member_online` WHERE user_id = '{$aLoginData->user_id}';";
		$md5Q = md5($sql);
		if (isset($aQData[$md5Q])) {
			return $aQData[$md5Q];
		} else {
			$db = DB::singleton();
			$db->query($sql, __FUNCTION__);
			$db->next_record();
			if ($db->num_rows() > 0) {
				login_logout::updateMemberOnline($aLoginData->user_id, $actionView, $_onlinetime);
			} else {
				login_logout::addMemberOnline($aLoginData->user_id, $actionView, $_onlinetime);
			}

			$aQData[$md5Q] = true;
			return true;
		}
	}

	public static function getAllMemberOnline($type = 'user')
	{
		global $aQData;
		$time = _TIME_;
		if ($type == 'user' || $type == 'member') {
			$sql = "SELECT o.*, m.firstname, m.picture
						FROM " . _DBPREFIX_ . "member_online o
						LEFT JOIN " . _DBPREFIX_ . "member_member m ON m.user_id = o.user_id
						WHERE o.onlineEndTime >= {$time}
						AND o.user_id > 0
						ORDER BY o.onlineTime DESC;";
		} elseif ($type == 'guest') {
			$sql = "SELECT o.*, m.firstname, m.picture
						FROM " . _DBPREFIX_ . "member_online o
						LEFT JOIN " . _DBPREFIX_ . "member_member m ON m.user_id = o.user_id
						WHERE o.onlineEndTime >= {$time}
						AND o.user_id = 0
						ORDER BY o.onlineTime DESC;";
		} elseif ($type == 'all') {
			$sql = "SELECT * FROM " . _DBPREFIX_ . "member_online WHERE onlineEndTime >= {$time} ;";
		} else {
			return array('num_rows' => 0, 'data' => array());
		}

		$md5Q = md5($sql);
		if (isset($aQData[$md5Q])) {
			return $aQData[$md5Q];
		} else {
			$aData = array();
			$db = DB::singleton();
			$db->query($sql, __FUNCTION__);
			$aData['num_rows'] = $db->num_rows();
			$aData['sql'] = $sql;
			while ($db->next_record()) {
				$aData['data'][] = $db->allRows();
			}
			$aQData[$md5Q] = $aData;
			return $aQData[$md5Q];
		}
	}

	public static function getCheckMember($username = '', $password = '')
	{
		$db = DB::singleton();
		$sql = "
		SELECT *
		FROM `" . _DBPREFIX_ . "member_user`
		WHERE username = '{$username}'";
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		if ($db->num_rows() > 0) {
			$user = $db->allRows();
			$options = [
				'memory_cost' => MEMORY_COST,
				'time_cost'   => TIME_COST,
				'threads'     => THREADS
			];
			if (password_verify($password, $user['password'])) {
				if (password_needs_rehash($user['password'], PASSWORD_ARGON2ID, $options)) {
					$newHash = password_hash($password, PASSWORD_ARGON2ID, $options);
					$sql = "UPDATE `" . _DBPREFIX_ . 'member_user' . "` SET password = '{$newHash}' WHERE user_id = {$user['user_id']}";
					$db->prepare($sql);
				}
				$aUser = login_logout::__Get_User_ByID($user['user_id']);
				return $aUser;
			} else {
				$md5 = md5(md5($password));
				$md5 = str_replace("a", '#', $md5);
				$md5 = str_replace("b", '&', $md5);
				$md5 = str_replace("c", '-', $md5);
				$md5 = str_replace("1", 'A', $md5);
				$md5 = str_replace("2", 'B', $md5);
				$newpassword = str_replace("3", 'C', $md5);
				$sql = "
					SELECT *
					FROM `" . _DBPREFIX_ . "member_user`
					WHERE username = '{$username}' AND password = '{$newpassword}'";
				$db->query($sql, __FUNCTION__);
				$db->next_record();
				if ($db->num_rows() > 0) {
					$user = $db->allRows();
					$newHash = password_hash($password, PASSWORD_ARGON2ID, $options);
					$sql = "UPDATE `" . _DBPREFIX_ . 'member_user' . "` SET password = '{$newHash}' WHERE user_id = {$user['user_id']}";
					$db->prepare($sql);
					$aUser = login_logout::__Get_User_ByID($user['user_id']);
					return $aUser;
				} else {
					return [];
				}
			}
		} else {
			return [];
		}
	}

	public static function getCheckMemberReset($username = '', $password = '')
	{
		$db = DB::singleton();
		$password = login_logout::encode_builpassword($password);
		$sql = "
		SELECT *
		FROM `" . _DBPREFIX_ . "member_user` u
		LEFT JOIN  `" . _DBPREFIX_ . "member_member` m ON m.user_id = u.user_id
		WHERE u.username = '{$username}'
		AND u.repassword = '{$password}';";
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		if ($db->num_rows() > 0) {
			$res = $db->allRows();
			$res['password'] = '****';
			return $res;
		} else {
			return array();
		}
	}

	public static function getMemberByEmailAddress($email = '')
	{
		$db = DB::singleton();
		$sql = "
		SELECT *
		FROM " . _DBPREFIX_ . "member_member m 
		LEFT JOIN " . _DBPREFIX_ . "member_user u ON u.user_id = m.user_id 
		WHERE m.email = '{$email}';";
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		return ($db->num_rows() > 0) ? $db->allRows() : array();
	}

	public static function updateResetPass($id, $password, $repassword)
	{
		$db = DB::singleton();
		$sql = "
		UPDATE " . _DBPREFIX_ . "member_user 
		SET password = '{$password}',
				repassword = '{$repassword}'
		WHERE user_id = '{$id}';";
		$db->query($sql, __FUNCTION__);
	}

	public static function _get_ipaddress()
	{
		return $ipaddress = @$_SERVER['REMOTE_ADDR'];
	}

	public static function _add_loginLogs($id)
	{
		$db = DB::singleton();
		$t = _TIME_;
		$ipaddress = login_logout::_get_ipaddress();
		$sql = "INSERT INTO  " . _DBPREFIX_ . "member_admin_login_logs (
					`logs_id` ,
					`user_id` ,
					`admin_ip` ,
					`logs_time`
				) VALUES (
					NULL,  '{$id}',  '{$ipaddress}','{$t}'
				);";
		$db->query($sql, __FUNCTION__);

		//$sql = "UPDATE "._DBPREFIX_."member_user SET login_amount = login_amount+1 WHERE user_id = '{$id}';";
		//$db->query($sql, __FUNCTION__);
	}

	public static function _check_loginLogsAcc()
	{
		global $loginBanTime;
		$db = DB::singleton();
		$ipaddress = login_logout::_get_ipaddress();
		$t = _TIME_ - $loginBanTime;
		$sql = "
		SELECT logs_id FROM `" . _DBPREFIX_ . "logs_login` 
		WHERE loginIp = '{$ipaddress}'
		AND logs_time >= '{$t}' ;";
		$db->query($sql, __FUNCTION__);
		return $db->next_record();
	}

	public static function _add_loginLogsAcc($loginDes = '', $loginBan = 0, $loginFail = 5)
	{
		/**
		 * ควรจะแก้เป็น Update
		 * @var unknown $db
		 */
		$db = DB::singleton();
		$t = _TIME_;
		$ipaddress = login_logout::_get_ipaddress();
		$sql = "INSERT INTO  " . _DBPREFIX_ . "logs_login (
		`logs_id` ,
		`loginBan` ,
		`loginFail` ,
		`loginIp` ,
		`loginDes` ,
		`logs_time`
		) VALUES (
		NULL, '{$loginBan}','{$loginFail}', '{$ipaddress}', '{$loginDes}', '{$t}'
		);";
		$db->query($sql, __FUNCTION__);
	}

	public static function generateRandomString($length = 10)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	public static function updatePassReset($user_id = '', $password = '')
	{
		$db = DB::singleton();
		$sql = "UPDATE " . _DBPREFIX_ . "member_user
					SET repassword = '{$password}' 
					WHERE user_id = '{$user_id}'; ";
		$db->query($sql, __FUNCTION__);
	}

	public static function forgotpassword($email = '')
	{
		if ($email == '') {
			setRaiseMsg('กรุณากรอกอีเมล์ด้วยครับ!', _TIME_, 1);
			login_logout::reDirectToLogin('resetpass.php');
			exit;
		}

		$aMember = login_logout::getMemberByEmailAddress($email);
		if (count($aMember) > 0 && $aMember['username'] != '' && $aMember['password'] != '' && $aMember['email'] == $email) {
			$timenewpass = login_logout::generateRandomString();
			$password = login_logout::encode_builpassword($timenewpass);
			if (login_logout::sendMailForgotPassword($aMember['email'], $aMember['username'], $timenewpass) == 'ok') {
				login_logout::updatePassReset($aMember['user_id'], $password);

				setRaiseMsg('The system has sent your password. To the e-mail already, please check Inbox and Junkmail as well.', _TIME_, 0);
				login_logout::reDirectToLogin('login.php');
				exit;
			} else {
				setRaiseMsg('Missing password sending!', _TIME_, 1);
				login_logout::reDirectToLogin('resetpass.php');
				exit;
			}
		} else {
			setRaiseMsg('The e-mail you entered Not in the Admin system, please check the new email. To match what you use as well ', _TIME_, 1);
			login_logout::reDirectToLogin('resetpass.php');
			exit;
		}
	}

	public static function sendMailForgotPassword($email, $username, $password)
	{
		global $aConfig;
		$aMail = array();
		$aMail['emailto'] = $email;
		$aMail['title'] = 'แจ้งรหัสผ่านใหม่ในระบบอบรม';
		$aMail['emailfrom'] = 'AutoMail@' . DOMAIN_NAME;
		$aMail['emailname'] = 'WebTraining';
		$aMail['content'] = "
<pre>
<b>Password for accessing the training system</b>
username : " . $username . "
password : " . $password . "

You can access it at the link below.
<a href=\"" . URL_ADMIN . "\">" . URL_ADMIN . "</a>

You can login the old code if you still remember it. This new code will be discontinued.
If you login with this new code for the first time, the old one will be canceled as well.

Request a new password when time
" . strTimeFormat() . "
</pre>
		";
		$res = Plugin_sendMail($aMail['title'], $aMail);
		return $res;
	}

	public static function adminLogout()
	{
		login_logout::reMoveSessionLogin();
		//setRaiseMsg(' ออกจากระบบจัดการเรียบร้อยแล้ว ',_TIME_,0);
		login_logout::reDirectToLogin('login.php');
	}

	public static function reDirectToLogin($url)
	{
		if (IS_MATA_REDIRECT == true) {
			echo '<meta http-equiv="refresh" content="0;URL=' . $url . '" />';
			exit;
		} else {
			@header("Location:" . $url);
			exit;
		}
	}

	public static function UpdateReGisterSessionLogin()
	{
		global $cookietime, $aModuleUse;
		$expride = time() + $cookietime;
		$_SESSION['logout_time'] = $expride;

		if (in_array('useronline', $aModuleUse)) {
			$module = (@$_REQUEST['module'] != '') ? @$_REQUEST['module'] : 'Dashboard';
			$mp = (@$_REQUEST['mp'] != '') ? @$_REQUEST['mp'] : '';
			$actionView = UserOnline_Func_Global_Page_Name();
			$actionView = isset($actionView[$mp]) ? $actionView[$mp] : $mp;
			login_logout::getCheckMemberOnline($module . '|' . $mp . '|U_R_G_S_L|' . $actionView);
		}
	}

	public static function reGisterSessionLogin($username, $aUser, $logintime = 0, $isUpdate = false)
	{
		global $cookietime, $aModuleUse;
		$time = ($logintime > 0) ? $logintime : _TIME_;
		$expride = time() + $cookietime;

		$aSet = array();
		$aSet['login'] = true;
		if ($isUpdate == false) {
			$aSet['login'] = ($aUser['twofa_status'] == 'on') ? 'ath_required' : true;
			$aSet['login_time'] = $time;
			$_SESSION['isSuperAdmin'] = false;
			$_SESSION['signon_origin_id'] = 0;
		}
		$aSet['logout_time'] = $expride;

		$aDataSet = array();
		$aDataSet['login'] 			= true;
		$aDataSet['user_id'] 		= $aUser['user_id'];
		$aDataSet['user'] 			= $username;
		$aDataSet['name'] 			= $aUser['salutation'] . ' ' . $aUser['firstname'] . ' ' . $aUser['lastname'];
		$aDataSet['email'] 			= $aUser['email'];
		$aDataSet['status'] 		= $aUser['status'];
		$aDataSet['picture'] 		= $aUser['picture'];
		$aDataSet['region'] 		= @$aUser['region'];
		$aDataSet['modules'] 		= login_logout::resolvePresetModules($aUser);
		$aDataSet['_ATHCheck'] 		= @$aUser['twofa_status'];

		if ($isUpdate == false) {
			$aDataSet['loginTime'] 	= $time;
		}

		$aSet['mdusertext'] 			= base64_encode(json_encode($aDataSet));
		foreach ($aSet as $k => $v) {
			$_SESSION[$k] = $v;
		}
		// reset fail login to 0
		$_SESSION['loginfail'] = 0;

		if (in_array('useronline', $aModuleUse)) {
			login_logout::getCheckMemberOnline('เข้าสู่ระบบ|reGisterSessionLogin');
		}
	}

	public static function __Get_User_ByID($id)
	{
		global $aQData;
		$sql = "SELECT * FROM " . _DBPREFIX_ . "member_user u
				LEFT JOIN " . _DBPREFIX_ . "member_member m ON m.user_id = u.user_id
				WHERE u.user_id = '{$id}'; ";
		$md5Q = md5($sql);
		if (isset($aQData[$md5Q])) {
			return $aQData[$md5Q];
		} else {
			$db = DB::singleton();
			$db->query($sql, __FUNCTION__);
			$aData = ($db->next_record()) ? $db->allRows() : array();
			$aQData[$md5Q] = $aData;
			return $aData;
		}
	}

	public static function updateLoginData()
	{
		$oUser = login_logout::getLoginData();
		if (!isset($oUser->user_id)) {
			login_logout::adminLogout();
		}

		$aUser = login_logout::__Get_User_ByID($oUser->user_id);
		login_logout::reGisterSessionLogin($aUser['username'], $aUser, 0, true);
	}

	public static function resolvePresetModules($aUser)
	{
		$pid = (int)(@$aUser['preset_id']);
		if ($pid > 0) {
			$pp = DB_GET('permission_preset', ['preset_id' => $pid]);
			if (is_array($pp) && isset($pp['modules'])) {
				return (string)$pp['modules'];
			}
		}
		return (string)(@$aUser['modules']);
	}

	public static function getLoginData()
	{
		if (@$_SESSION['mdusertext'] != '') {
			return json_decode(base64_decode($_SESSION['mdusertext']));
		}
	}

	public static function reMoveSessionLogin()
	{
		$aSet = array(
			'login',
			'login_time',
			'logout_time',
			'mdusertext',
			'isSuperAdmin',
			'signon_origin_id'
		);

		foreach ($aSet as $k => $v) {
			unset($_SESSION[$v]);
		}

		$_SESSION['login'] = false;
		$_SESSION['logout_time'] = _TIME_;
	}

	////////////keeree////////////
	public static function checkLogin($autoRedirect = true)
	{
		$time = _TIME_;
		if (@$_SESSION['login'] === true && @$_SESSION['logout_time'] > $time) {
			login_logout::UpdateReGisterSessionLogin();
			return true;
		} else {
			if (@$_SESSION['login'] == 'ath_required') {
				login_logout::reDirectToLogin('login.php');
			} else {
				login_logout::reMoveSessionLogin();
			}
		}

		if ($autoRedirect == true) {
			setRaiseMsg('Please login. You do not have the right to manage this section.. ', _TIME_, 1);
			login_logout::reDirectToLogin('login.php');
		} else {
			$_SESSION['redirect'] = $_SERVER['HTTP_REFERER'];
			return false;
		}
	}

	public static function disPlayWellcomeLoginMessage($autoEcho = true)
	{
		$username = (isset($_SESSION['login_user']) && $_SESSION['login_user'] != '') ? $_SESSION['login_user'] : '';
		$name = (isset($_SESSION['login_name']) && $_SESSION['login_name'] != '') ? $_SESSION['login_name'] : 'Not yet named';
		if ($username) {
			$messageLogin = 'Welcome <b>' . $username . '</b> ( ' . $name . ' ) , Log out ' .
				' <a href="index.php?sef_log=logout"> Click </a> ';

			if ($autoEcho == true) {
				echo $messageLogin;
			} else {
				return $messageLogin;
			}
		}
	}

	public static function canSignonInit()
	{
		if (login_logout::is_SuperAdmin()) {
			return true;
		}
		$perm = login_logout::getAdminPermission();
		return (in_array('all', $perm, true) || in_array('signon_other', $perm, true));
	}

	public static function signon($uid)
	{
		if (!(is_numeric($uid) && $uid > 0)) {
			return false;
		}
		$curId    = (int) login_logout::getAdminId();
		$isSuper  = login_logout::is_SuperAdmin();
		$originId = (int) ($_SESSION['signon_origin_id'] ?? 0);
		$inSignon = $originId > 0;

		$aUser = login_logout::__Get_User_ByID($uid);
		if (!(is_array($aUser) && count($aUser) > 0)) {
			return false;
		}

		if ($inSignon) {
			if (!empty($_SESSION['isSuperAdmin'])) {
				login_logout::reGisterSessionLogin($aUser['username'], $aUser);
				if ((int) $uid === $originId) {
					unset($_SESSION['signon_origin_id']);
					$_SESSION['isSuperAdmin'] = false;
				} else {
					$_SESSION['signon_origin_id'] = $originId;
					$_SESSION['isSuperAdmin'] = true;
				}
				$_SESSION['login'] = true;
				Func_Addlogs("[Signon] สลับ/กลับร่างเป็น " . ($aUser['username'] ?? '') . " (id " . (int) $uid . ")");
				return true;
			}
			if ((int) $uid !== $originId) {
				return false;
			}
			login_logout::reGisterSessionLogin($aUser['username'], $aUser);
			unset($_SESSION['signon_origin_id']);
			$_SESSION['isSuperAdmin'] = false;
			$_SESSION['login'] = true;
			Func_Addlogs("[Signon] กลับร่างเป็น " . ($aUser['username'] ?? ''));
			return true;
		}

		if (!login_logout::canSignonInit()) {
			return false;
		}
		if (($aUser['username'] ?? '') === 'superadmin' || (int) $uid === $curId) {
			return false;
		}
		if (!$isSuper && in_array('all', explode(',', login_logout::resolvePresetModules($aUser)), true)) {
			return false;
		}
		$originUser = login_logout::getAdminUsername();
		login_logout::reGisterSessionLogin($aUser['username'], $aUser);
		$_SESSION['signon_origin_id'] = $curId;
		$_SESSION['isSuperAdmin'] = $isSuper;
		$_SESSION['login'] = true;
		Func_Addlogs("[Signon] " . $originUser . " → สิงเป็น " . ($aUser['username'] ?? '') . " (id " . (int) $uid . ")");
		return true;
	}

	public static function is_Admin()
	{
		$oUser = login_logout::getLoginData();
		return (isset($oUser->status) && $oUser->status == 'admin') ? true : false;
	}

	public static function is_SuperAdmin()
	{
		$oUser = login_logout::getLoginData();
		return (isset($oUser->user) && $oUser->user == 'superadmin') ? true : false;
	}

	public static function getAdminUsername()
	{
		$oUser = login_logout::getLoginData();
		$user = !empty($oUser->user) ? $oUser->user : false;
		return $user;
	}

	public static function getAdminId()
	{
		$oUser = login_logout::getLoginData();
		$user_id = !empty($oUser->user_id) ? $oUser->user_id : false;
		return $user_id;
	}

	public static function getAdminStatus()
	{
		$oUser = login_logout::getLoginData();
		return $oUser->status;
	}

	public static function getAdminPermission()
	{
		$oUser = login_logout::getLoginData();
		// กัน null/empty — PHP 8.1+ deprecate explode(null)
		$modules = (!empty($oUser) && isset($oUser->modules) && is_string($oUser->modules))
			? $oUser->modules
			: '';
		return explode(',', $modules);
	}
}
