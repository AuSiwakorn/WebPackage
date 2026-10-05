<?php
include(dirname(__FILE__) . '/../mainApi.php');
include dirname(__FILE__) . 'oApi.php';
@header('Content-Type: text/html; charset=utf-8');

//=====================================//
$_aMember = @$_SESSION['member'];
$action = @$_REQUEST['action'];
$isRegister = false;
//=====================================//

function randomPassword()
{
	$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
	$pass = array(); //remember to declare $pass as an array
	$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
	for ($i = 0; $i < 8; $i++) {
		$n = rand(0, $alphaLength);
		$pass[] = $alphabet[$n];
	}
	return implode($pass);
}

switch ($action) {
	case 'login':
		$username = (isset($_POST['username']) && $_POST['username'] != '') ? $_POST['username'] : '';
		$password = (isset($_POST['password']) && $_POST['password'] != '') ? $_POST['password'] : '';
		$redirect = (@$_REQUEST['redir'] != '') ? @$_REQUEST['redir'] : 'index.php';
		$acmod = @$_REQUEST['req_type'];
		$typelogin = @$_REQUEST['typelogin'];
		if ($typelogin == 'admin') {
			Member_adminLogin($username, $password);
			echo 1;
		} else {
			if ($acmod == 'ajax') {
				$res = Member_ajax_login($username, $password);
				echo ($res == true) ? 1 : 0;
			} else {
				echo 1;
				//$res = Member_registerLoginMember($username, $password, false);
				$res = Member_ajax_login($username, $password);
				CustomRedirectToUrl($redirect);
				exit;
			}
		}

		break;
	case 'forgotpasswprd':
		$username = @$_REQUEST['username']; //@$_REQUEST['email'];
		if ($username != '') {
			$MemberData = Member_get_member_by_username($username);
			if (@$MemberData['email'] != '' && @$MemberData['password'] != '') {
				$isSmtp = false;
				$password = randomPassword();
				Member_change_password($MemberData['user_id'], $password);
				$headers = "From: " . DOMAIN_NAME . " <" . ADMIN_EMAIL . ">\r\n";
				$headers .= "MIME-Version: 1.0\r\n";
				$headers .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
				$headers .= "Content-Transfer-Encoding: 7bit\r\n";
				$content = "Password for accessing the website " . DOMAIN_NAME . "\n\n<br />";
				$content .= "USERNAME : " . $username . "\n<br />";
				$content .= "PASSWORD : " . $password . "\n<br />";
				$content .= "------------------------------------\n<br />";
				$content .= "Tip: Copy the password and paste it into Notepad. The password is verified. Apply to login\n<br />";
				$aMail['content'] = $content;
				$aMail['emailfrom'] = 'admin@' . DOMAIN_NAME;
				$aMail['emailname'] = DOMAIN_NAME;
				$aMail['isHtml'] = true;
				$aMail['title'] = "Give password FORGOT PASSWORD";
				$aMail['emailto'] = $MemberData['email'];
				$res = _semd_email_core($isPHPMailer = true, $aMail);
				//$res = defaultSendMail($MemberData['email'],"Give password FORGOT PASSWORD",$content,$headers);
				if ($res) {
					oApi::regisMessage("Send the password to you via E-mail. Please check your email to receive the password.", 'ok');
					echo 1;
					exit;
				}
			}
		}
		oApi::regisMessage("Can't send password Because such members could not be found or the email address of such members could not be mailed.", 'error');
		echo 0;
		exit;
		break;
	case 'registervalidate':
		$type = $_REQUEST['type'];
		$value = $_REQUEST['value'];
		switch ($type) {
			case 'email':
				$ch = (preg_match("/^[^@]*@[^@]*\.[^@]*$/", $value)) ? 1 : 0;
				if ($ch == 1) {
					if (Member_isUserMemberByEmail($value)) {
						echo '<img src="img/no.gif" /> This email has already been used';
					} else {
						echo '<img src="img/ok.gif" />';
					}
				} else {
					echo '<img src="img/no.gif" /> Invalid email';
				}
				break;
			case 'username':
				if (Member_isUserMemberByUser($value) && $value != '') {
					echo '<img src="img/no.gif" /> This username is already used';
				} else {
					echo '<img src="img/ok.gif" />';
				}
				break;
		}
		break;
	case 'register':
		if (@$isRegister == true) {
			$ac = @$_REQUEST['ac'];
			if ($ac == 'register') {
				$d = array();
				$d['firstname'] 		= @$_POST['firstname'];
				$d['lastname'] 			= @$_POST['lastname'];
				$d['bday'] 			    = @$_POST['y'] . '-' . @$_POST['m'] . '-' . @$_POST['d'];
				$d['address'] 			 = @$_POST['address'];
				$d['zipcode'] 		  = @$_POST['zipcode'];
				$d['province_id']	= @$_POST['province_id'];
				$d['phone'] 		    = @$_POST['phone'];
				$d['fax'] 				    = @$_POST['fax'];
				$d['email'] 		    = @$_POST['email'];
				$d['username'] 		 = @$_POST['username'];
				$d['password'] 		 = @$_POST['password'];
				$d['password_confirm'] = @$_POST['password_confirm'];
				$d['website'] 			      = '';
				$d['picture'] 			      = '';
				$d['job_title'] 		     = '';
				$d['job_experience']   = '';

				if ($d['username'] == '' || $d['password'] == '') {
					oApi::regisMessage("Please enter your Username and Password", 'error');
					echo 0;
					exit;
				}
				if ($d['password_confirm'] != $d['password']) {
					oApi::regisMessage("Please enter the password exactly as well", 'error');
					echo 0;
					exit;
				}
				if ($d['firstname'] == '' || $d['lastname'] == '') {
					oApi::regisMessage("Please fill in first and last name as well", 'error');
					echo 0;
					exit;
				}
				/*
			if ($d['address'] == '') {
				oApi::regisMessage("Please fill in the address information", 'error');
				echo 0;
				exit;
			}
			if ($d['zipcode'] == '') {
				oApi::regisMessage("Please fill in the postal code as well", 'error');
				echo 0;
				exit;
			}
			if ($d['phone'] == '') {
				oApi::regisMessage("Please fill in your contact number", 'error');
				echo 0;
				exit;
			}
			if (!preg_match("/[0-9]/", $d['zipcode'])) {
				oApi::regisMessage("Enter the postal code", 'error');
				echo 0;
				exit;
			}
			if (!preg_match("/[0-9]/", $d['phone'])) {
				oApi::regisMessage("Enter contact number", 'error');
				echo 0;
				exit;
			}
			*/
				if ($d['email'] == '') {
					oApi::regisMessage("Please enter your email address", 'error');
					echo 0;
					exit;
				}

				$ch = (preg_match("/^[^@]*@[^@]*\.[^@]*$/", $d['email'])) ? 1 : 0;
				if ($ch == 0) {
					oApi::regisMessage("The email was entered incorrectly", 'error');
					echo 0;
					exit;
				}

				if (Member_isUserMemberByUser($d['username'])) {
					oApi::regisMessage("This username is already active", 'error');
					echo 0;
					exit;
				}
				if (Member_isUserMemberByEmail($d['email'])) {
					oApi::regisMessage("This email is already used by members", 'error');
					echo 0;
					exit;
				}
				Member_register_new($d, '0');
				$txt = Member_sendEmailForgetPasswordByEmail($d['email']);
				if ($txt != '') {
					$headers = "From: AUTO WELCOME\r\n";
					$headers .= "MIME-Version: 1.0\r\n";
					$headers .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
					$headers .= "Content-Transfer-Encoding: 7bit\r\n";
					$content = "Password " . $_SERVER['HTTP_HOST'] . "\n\n";
					$content .= "
				" . $txt['txt'] . "
				";
					$res = defaultSendMail($txt['email'], "Welcome By " . $_SERVER['HTTP_HOST'], $content, $headers);
				}
				Member_ajax_login($d['username'], $d['password']);
				oApi::regisMessage("Already registered.", 'ok');
				echo 1;
				exit;
			}
		} else {
			echo 'Closed for new memberships';
		}
		break;
	case 'changepassword':
		if (isset($_aMember['login']) && $_aMember['login'] && $_aMember['id'] > 0) {
			$password = @$_REQUEST['password'];
			$confirmpassword = @$_REQUEST['confirmpassword'];
			if ($password == $confirmpassword) {
				if (strlen($password) >= 6) {
					Member_change_password($_aMember['id'], $password);
					//=============================================//
					$aMemberMail = array();
					$aMemberMail['username'] = $_aMember['username'];
					$aMemberMail['name'] = $_aMember['name'];
					$aMemberMail['email'] = $_aMember['email'];
					_mail_send_changepassword($aMemberMail);
					//=============================================//
					echo 1;
					exit;
				} else {
					echo 'Password must be 6 characters or more';
					exit;
				}
			} else {
				echo 'The password and confirmation password do not match';
				exit;
			}
		} else {
			echo 'Unable to verify SESSION, please login again';
			exit;
		}
		break;
	case 'registervalidate':
		$type = $_REQUEST['type'];
		$value = $_REQUEST['value'];
		switch ($type) {
			case 'email':
				$ch = (preg_match("/^[^@]*@[^@]*\.[^@]*$/", $value)) ? 1 : 0;
				if ($ch == 1) {
					if (Member_isUserMemberByEmail($value)) {
						echo '<img src="img/no.gif" /> This email has already been used.';
					} else {
						echo '<img src="img/ok.gif" />';
					}
				} else {
					echo '<img src="img/no.gif" /> Invalid email';
				}
				break;
			case 'username':
				if (Member_isUserMemberByUser($value) && $value != '') {
					echo '<img src="img/no.gif" /> This username is already used.';
				} else {
					echo '<img src="img/ok.gif" />';
				}
				break;
		}
		break;
}
