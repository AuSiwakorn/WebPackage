<?php
class userInfo
{
	private static function get_user_agent()
	{
		return  $_SERVER['HTTP_USER_AGENT'];
	}

	public static function get_ip()
	{
		// หลัง Cloudflare: CF-Connecting-IP = IP จริงของ client (เชื่อถือได้ที่สุด)
		if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
			return $_SERVER['HTTP_CF_CONNECTING_IP'];
		}
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
			if (filter_var($ip, FILTER_VALIDATE_IP)) {
				return $ip;
			}
		}
		$proxyHeaders = ['HTTP_CLIENT_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED'];
		foreach ($proxyHeaders as $h) {
			if (!empty($_SERVER[$h])) {
				$ip = trim($_SERVER[$h]);
				if (filter_var($ip, FILTER_VALIDATE_IP)) {
					return $ip;
				}
			}
		}
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN';
	}

	public static function get_os()
	{

		$user_agent = self::get_user_agent();
		$os_platform    =   "Unknown OS Platform";
		$os_array       =   array(
			'/windows/i'     		=>  'windows',
			'/win98/i'              =>  'windows',
			'/win95/i'              =>  'windows',
			'/win16/i'              =>  'windows',
			'/macintosh|mac/i' 		=>  'macOS',
			'/mac_powerpc/i'        =>  'macOS',
			'/linux/i'              =>  'linux',
			'/ubuntu/i'             =>  'ubuntu',
			'/iphone/i'             =>  'iphone',
			'/ipod/i'               =>  'ipod',
			'/ipad/i'               =>  'ipad',
			'/android/i'            =>  'android',
			'/blackberry/i'         =>  'blackberry',
			'/webos/i'              =>  'webos'
		);

		foreach ($os_array as $regex => $value) {
			if (preg_match($regex, $user_agent)) {
				$os_platform    =   $value;
			}
		}
		return $os_platform;
	}

	public static function  get_browser()
	{
		$user_agent = self::get_user_agent();
		$browser        =   "Unknown Browser";
		$browser_array  =   array(
			'/msie/i'       =>  'msie',
			'/Trident/i'    =>  'Trident',
			'/firefox/i'    =>  'firefox',
			'/safari/i'     =>  'safari',
			'/chrome/i'     =>  'chrome',
			'/edg/i'       	=>  'edg',
			'/opr/i'      	=>  'opr',
			'/netscape/i'   =>  'netscape',
			'/maxthon/i'    =>  'maxthon',
			'/konqueror/i'  =>  'konqueror',
			'/ubrowser/i'   =>  'ubrowser',
			'/mobile/i'     =>  'mobile'
		);
		foreach ($browser_array as $regex => $value) {
			if (preg_match($regex, $user_agent)) {
				$browser    =   $value;
			}
		}
		return $browser;
	}
	public static function  get_device()
	{
		$tablet_browser = 0;
		$mobile_browser = 0;
		if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
			$tablet_browser++;
		}
		if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
			$mobile_browser++;
		}
		if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') > 0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
			$mobile_browser++;
		}
		$mobile_ua = strtolower(substr(self::get_user_agent(), 0, 4));
		$mobile_agents = array(
			'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
			'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
			'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
			'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
			'newt', 'noki', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
			'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
			'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
			'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
			'wapr', 'webc', 'winw', 'winw', 'xda ', 'xda-'
		);
		if (in_array($mobile_ua, $mobile_agents)) {
			$mobile_browser++;
		}
		if (strpos(strtolower(self::get_user_agent()), 'opera mini') > 0) {
			$mobile_browser++;
			//Check for tablets on opera mini alternative headers
			$stock_ua = strtolower(isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']) ? $_SERVER['HTTP_X_OPERAMINI_PHONE_UA'] : (isset($_SERVER['HTTP_DEVICE_STOCK_UA']) ? $_SERVER['HTTP_DEVICE_STOCK_UA'] : ''));
			if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $stock_ua)) {
				$tablet_browser++;
			}
		}
		if ($tablet_browser > 0) {
			// do something for tablet devices
			return 'tablet';
		} else if ($mobile_browser > 0) {
			// do something for mobile devices
			return 'mobile';
		} else {
			// do something for everything else
			return 'computer';
		}
	}
}
