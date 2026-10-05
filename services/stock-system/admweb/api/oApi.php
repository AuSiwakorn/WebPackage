<?php
global $aConfig;
class oApi
{
	public $aConfig;
	public static function getConfig($key = '')
	{
		global $aConfig;
		if ($key != '') {
			return @$aConfig[$key];
		}
	}

	public static function getLoginData()
	{
		if ($_SESSION['mdusertext'] != '') {
			return json_decode(base64_decode($_SESSION['mdusertext']));
		}
	}

	public static function setLanguage($langKey = DEFAULT_LANGEUAGE)
	{
		unset($_SESSION['current']);
		unset($_SESSION['current']['lang']);
		$aLanguage = oApi::getConfig('language');

		if (isset($aLanguage[$langKey]) && $aLanguage[$langKey] != '') {
			$_SESSION['current']['lang'] = $langKey;
		} else {
			//error
			// LANG49 No activation Your preferred language , Please contact the site administrator
		}
	}

	function getCurrentLang()
	{
		return api_getCurrentLang();
	}

	public static function regisMessage($message, $type = 'ok')
	{
		$_SESSION['success']['messageinfo'] = $message;
		$_SESSION['success']['messageinfo_type'] = $type;
	}

	public static function displayMessage()
	{
		$message = (isset($_SESSION['success']['messageinfo']) && $_SESSION['success']['messageinfo'] != '') ? $_SESSION['success']['messageinfo'] : '';
		$messageinfo_type = (isset($_SESSION['success']['messageinfo_type'])) ? $_SESSION['success']['messageinfo_type'] : 'none';
		if ($message != '') {
			if ($messageinfo_type == 'ok') {
				echo '<div class="ok">' . $message . '</div>';
			} elseif ($messageinfo_type == 'error') {
				echo '<div class="error">' . $message . '</div>';
			} elseif ($messageinfo_type == 'notis') {
				echo '<div class="notis">' . $message . '</div>';
			}
			$_SESSION['success'] = array();
		}
	}

	public static function is_User()
	{
		if (isset($_SESSION['login']) && $_SESSION['login'] == 1) {
			$a = oApi::getLoginData();
			if ($a->status == 'admin') {
				return false;
			} else {
				if ($a->user_id > 1) {
					return true;
				} else {
					return false;
				}
			}
		}

		return false;
	}

	public static function is_Login()
	{
		if (isset($_SESSION['login']) && $_SESSION['login'] == 1) {
			return true;
		}

		return false;
	}

	public static function is_Admin()
	{
		if (isset($_SESSION['login']) && $_SESSION['login'] == 1) {
			$a = oApi::getLoginData();
			if ($a->status == 'admin') {
				return 1;
			} else {
				return 0;
			}
		}

		return 0;
	}
}

$oApi = new oApi();
