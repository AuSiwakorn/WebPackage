<?php
include 'func.counter.php';

$_areq = array(
    'module',
    'mp',
    'pg',
    'ac',
    'inc',
    'ty',
    'option',
    'sef_log',
    'set',
    'id',
    'gid',
    'group_id',
    'groupid',
    'username',
    'password',
    'email',
    'meta',
);

foreach ($_areq as $v) {
    //check fix req
    if (isset($_GET[$v])) {
        $_GET[$v] = forQuote(@$_GET[$v]);
    }

    //check fix req
    if (isset($_POST[$v])) {
        $_POST[$v] = forQuote(@$_POST[$v]);
    }

    //check fix req
    if (isset($_REQUEST[$v])) {
        $_REQUEST[$v] = forQuote(@$_REQUEST[$v]);
    }
}

function forQuote($var)
{
    if (is_array($var)) {
        foreach ($var as $k => $v) {
            $a[$k] = is_array($v) ? forQuote($v) : ReqFixinjection($v);
        }
        return $a;
    } else {
        return ReqFixinjection($var);
    }
}

function Get($value = '')
{
    if ($value != '' &&  !is_numeric($value)) {
        return ReqFixinjection(@$_GET[$value]);
    }
}

function getIP()
{
    return (!empty($_SERVER['HTTP_CLIENT_IP'])) ? $_SERVER['HTTP_CLIENT_IP'] : $_SERVER['REMOTE_ADDR'];
}

function ReqFixinjection($value)
{
    $value = str_replace("\"", '&quot;', $value);
    $value = str_replace("\<", '&lt;', $value);
    $value = str_replace("\>", '&gt;', $value);
    $value = str_replace("\"", '&quot;', $value);
    $value = str_replace("=", '', $value);
    $value = str_replace("'", '', $value);
    $value = addslashes($value);
    return $value;
}

function fixinjection($value)
{
    return ReqFixinjection($value);
}

/*
$_GET['test']
$_POST['test']
$_REQUEST['test']
$_FILES['test']
change to

REQ_get(#1, #2, #3, #4)

1 = key
2 = (get, post, request, file or files)
3 = (str, int, array, data or detail)
4 = default data
*/
/**
 * @param string $ty get, post,request,file or files
 * @param string $validate str,int,array,data or detail
 */
function REQ_get($name = '', $ty = 'request', $validate = 'str', $default = '')
{
    if ($ty == 'get') {
        $str = isset($_GET[$name]) ? $_GET[$name] : '';
    } elseif ($ty == 'post') {
        $str = isset($_POST[$name]) ? $_POST[$name] : '';
    } elseif ($ty == 'file' || $ty == 'files') {
        return isset($_FILES[$name]) ? $_FILES[$name] : array();
    } elseif ($ty == 'email') {
        $str = isset($_REQUEST[$name]) ? $_REQUEST[$name] : '';
        return _checkEmail($str);
    } else {
        $str = isset($_REQUEST[$name]) ? $_REQUEST[$name] : '';
    }

    if ($validate == 'int') {
        $str = _input_validate_int($str, true, $validate . '|' . $name);
    } elseif ($validate == 'str') {
        $str = _input_validate_str($str);
    } elseif ($validate == 'url') {
        $str = _input_validate_url($str);
    } elseif ($validate == 'array') {
        if (!is_array($str)) {
            $str = (is_array($default)) ? $default : array();
        }
    }

    return ($str == '') ? $default : $str;
}

function REQ_get_browser()
{
	$u_agent = $_SERVER['HTTP_USER_AGENT'];
	$ub = '';
	if(preg_match('/MSIE/i',$u_agent)) {
		$ub = "ie";
	} elseif(preg_match('/Firefox/i',$u_agent)) {
		$ub = "firefox";
	} elseif(preg_match('/Safari/i',$u_agent)) {
		$ub = "safari";
	} elseif(preg_match('/Chrome/i',$u_agent)) {
		$ub = "chrome";
	} elseif(preg_match('/Flock/i',$u_agent)) {
		$ub = "flock";
	} elseif(preg_match('/Opera/i',$u_agent)) {
		$ub = "opera";
	}
	return $ub;
}

function _checkEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

#$a = _validate_int($_GET['id'], true);
function _input_validate_int($c, $isExit = false, $name = '')
{
    #preg_match('/^\d+$/',$c); Only numbers
    #is_numeric($_GET['id']); Can be used . 
    if ($c != '' && preg_match('/^\d+$/', $c)) {
        return $c;
    } else {
        if ($isExit == true && $c != '') {
            pre('Request is failed.(' . $name . '=' . $c . ')');
            exit;
        } else {
            return false;
        }
    }
}

function _checkNameCard($pid) 
{
	if(strlen($pid) != 13) {
		return false;
	}
	
	for($i=0, $sum=0; $i<12;$i++) {
		$sum += (int)($pid[$i])*(13-$i);
	}
	
	if((11-($sum%11))%10 == (int)($pid[12])) {
		return true;
	}
	return false;
}

function _input_validate_str($str = '')
{
    if ($str == '') {
        return '';
    }

    $str = strip_tags($str);
    $str = str_replace('"', '', $str);
    $str = str_replace('\'', '', $str);
    $str = htmlspecialchars($str);
    $str = str_replace(" update ", 'up_', $str);
    $str = str_replace(" insert", 'ins_', $str);
    $str = str_replace(' delete ', 'de_', $str);
    $str = str_replace(' select ', 'se_', $str);
    $str = str_replace(' where ', 'wh_', $str);
    $str = str_replace('script', '', $str);
    $str = str_replace(';', '', $str);
    $str = str_replace('=', '', $str);
    return $str;
}

function _input_validate_url($str = '')
{
    if ($str == '') {
        return '';
    }

    $str = strip_tags($str);
    $str = str_replace('"', '', $str);
    $str = str_replace("'", '', $str);
    $str = str_replace('<', '', $str);
    $str = str_replace('>', '', $str);
    if (preg_match('/^\s*javascript:/i', $str)) {
        $str = '';
    }
    return $str;
}

function REQ_clear($str = '', $rep = '')
{
    //$str = ereg_replace('/', '',$str);
    $str = trim($str);
    $str = preg_replace('/\s+/', $rep, $str);
    $str = preg_replace("/\?/", "", $str);
    $str = preg_replace("/\//", "", $str);
    $str = preg_replace('/,/', '', $str);
    $str = preg_replace("/ /", $rep, $str);
    $str = preg_replace("/--/", "-", $str);
    return preg_replace("/--/", $rep, $str);
}

function notifyLineMessage($message, $token = '', $chat_id = '')
{
    if (empty($message)) {
        return false;
    }

    if ($token === '') {
        $token = defined('TELEGRAM_TOKEN') ? TELEGRAM_TOKEN : '';
    }
    if ($chat_id === '') {
        $chat_id = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';
    }

    if (empty($token) || empty($chat_id)) {
        return false;
    }

    // ส่งข้อความไปยัง Telegram
    $url = "https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chat_id}&text=" . urlencode($message);
    file_get_contents($url);
    return true;
}

function is_mobile()
{
	@$op = strtolower($_SERVER['HTTP_X_OPERAMINI_PHONE']);
	@$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
	@$ac = strtolower($_SERVER['HTTP_ACCEPT']);

	return strpos($ac, 'application/vnd.wap.xhtml+xml') !== false
        || $op != ''
        || strpos($ua, 'sony') !== false 
        || strpos($ua, 'symbian') !== false 
        || strpos($ua, 'nokia') !== false 
        || strpos($ua, 'samsung') !== false 
        || strpos($ua, 'mobile') !== false
        || strpos($ua, 'windows ce') !== false
        || strpos($ua, 'epoc') !== false
        || strpos($ua, 'opera mini') !== false
        || strpos($ua, 'nitro') !== false
        || strpos($ua, 'j2me') !== false
        || strpos($ua, 'midp-') !== false
        || strpos($ua, 'cldc-') !== false
        || strpos($ua, 'netfront') !== false
        || strpos($ua, 'mot') !== false
        || strpos($ua, 'up.browser') !== false
        || strpos($ua, 'up.link') !== false
        || strpos($ua, 'audiovox') !== false
        || strpos($ua, 'blackberry') !== false
        || strpos($ua, 'ericsson,') !== false
        || strpos($ua, 'panasonic') !== false
        || strpos($ua, 'philips') !== false
        || strpos($ua, 'sanyo') !== false
        || strpos($ua, 'sharp') !== false
        || strpos($ua, 'sie-') !== false
        || strpos($ua, 'portalmmm') !== false
        || strpos($ua, 'blazer') !== false
        || strpos($ua, 'avantgo') !== false
        || strpos($ua, 'danger') !== false
        || strpos($ua, 'palm') !== false
        || strpos($ua, 'series60') !== false
        || strpos($ua, 'palmsource') !== false
        || strpos($ua, 'pocketpc') !== false
        || strpos($ua, 'smartphone') !== false
        || strpos($ua, 'rover') !== false
        || strpos($ua, 'ipaq') !== false
        || strpos($ua, 'au-mic,') !== false
        || strpos($ua, 'alcatel') !== false
        || strpos($ua, 'ericy') !== false
        || strpos($ua, 'up.link') !== false
        || strpos($ua, 'vodafone/') !== false
        || strpos($ua, 'wap1.') !== false
        || strpos($ua, 'wap2.') !== false;
}

function is_robot()
{
	@$ua = strtolower($_SERVER['HTTP_USER_AGENT']);
	if (empty($ua)) {
		return false;
	}
	$botlist = array("Teoma", "alexa", "froogle", "Gigabot", "inktomi",
	"looksmart", "URL_Spider_SQL", "Firefly", "NationalDirectory",
	"Ask Jeeves", "TECNOSEEK", "InfoSeek", "WebFindBot", "girafabot",
	"crawler", "www.galaxy.com", "Googlebot", "Scooter", "Slurp",
	"msnbot", "appie", "FAST", "WebBug", "Spade", "ZyBorg", "rabaz",
	"Baiduspider", "Feedfetcher-Google", "TechnoratiSnoop", "Rankivabot",
	"Mediapartners-Google", "Sogou web spider", "WebAlta Crawler","TweetmemeBot",
	"Butterfly","Twitturls","Me.dium","Twiceler");
 
	foreach($botlist as $bot){
		if (strpos($_SERVER['HTTP_USER_AGENT'],$bot)!==false) {
			return $bot;//true;	// Is a bot
		}
	}
	return false;	// Not a bot
}
