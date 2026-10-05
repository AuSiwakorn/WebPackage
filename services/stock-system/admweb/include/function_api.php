<?php
include 'func.cache.php';
/*
The function name starts with api_, followed by the name of the desired function.

*/
global $aConfig;

function PIC_Corp($filename, $nopic = '', $option = 'w=250')
{
	return ($filename != '') ? URL_UPLOAD . '/piccorp/webroot/img.php?src=' . $filename . '&' . $option . '&crop-to-fit' : $nopic;
}

/*
function _getReq($keys='')
{
	global $__myReq;
	if ($keys != '') {
		if (isset($__myReq[$keys]) && $__myReq[$keys] != '') {
			if (is_string($__myReq[$keys])) {
				return fixinjection($__myReq[$keys]);
			}
		}
	}
	return false;
}
*/
if (!function_exists('pre')) {
	function pre($a)
	{
		echo '<pre>';
		print_r($a);
		echo '</pre>';
	}
}

//ชนกับ smf siwakor error
function is_Admin() 
{
	$login = (isset($_SESSION['login']) && $_SESSION['login'] == 1) ? 1 : 0;
	$login_status = (isset($_SESSION['login_status']) && $_SESSION['login_status'] != '') ? $_SESSION['login_status'] : '';
	$login_user = (isset($_SESSION['login_user']) && $_SESSION['login_user'] != '') ? $_SESSION['login_user'] : '';
	if ($login == 1 && $login_user != '' && $login_status == 'admin') {
		return ($login_status == 'admin' || $login_status == 'admin_member') ? true : false;
	} else {
		return false;
	}
}

function api_header_download($filename)
{
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	header("Content-Type: application/force-download");
	header("Content-Type: application/octet-stream");
	header("Content-Type: application/download");
	header("Content-Disposition: attachment; filename=".basename($filename).";");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".filesize($filename));
	@readfile($filename);
	exit;
}

function api_clear_header_browser_cache() 
{
    header("Pragma: no-cache");
    header("Cache: no-cache");
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
}

function api_CustomRedirectToUrl($url='index.php')
{
	global $aConfig;
	if (IS_MATA_REDIRECT == true) {
		echo '<meta http-equiv="refresh" content="0;URL='.$url.'" />';
		exit;
	} else {
		@header("Location:".$url);
		exit;
	}
}

function __get_default_language()
{
	global $aConfig;
	return DEFAULT_LANGEUAGE;
}

function api_getCurrentLang() 
{
	global $aConfig;
	if (isset($_SESSION['current']['lang']) && $_SESSION['current']['lang'] != '') {
		$lang = $_SESSION['current']['lang'];
		if (@$aConfig['language'][$lang] != '') {
			return $_SESSION['current']['lang'];
			exit;
		}
	}
	
	$lang = __get_default_language();
	$_SESSION['current']['lang'] = $lang;
	return $lang;
}

function api_dirList($dir) 
{
	if ($dir[strlen($dir)-1] != '/') {
		$dir .= '/';
	}
    if (!is_dir($dir)) {
		return array();
	}
       
	$dir_handle  = opendir($dir);
    $dir_objects = array();
    while ($object = readdir($dir_handle)) {
		if (!in_array($object, array('.','..'))) {
            $filename    = $dir . $object;
            $file_object = array(
 	                            'name' => $object,
                                'size' => filesize($filename),
    //                            'perm' => permission($filename),
                                'type' => filetype($filename),
                                'time' => date("d F Y H:i:s", filemtime($filename))
                           );
           	$dir_objects[] = $file_object;
		}
	}
	return $dir_objects;
}

function api_strTimeFormat($timestamp='',$format="d m Y H:i:s") 
{
	$timestamp = ($timestamp) ? intval($timestamp) :_TIME_;
	return date($format,$timestamp);
}

function YouTube_src($weblink)
{
	$var  = parse_url($weblink, PHP_URL_QUERY);
	$var  = html_entity_decode($var);
	$var  = explode('&', $var);
	$arr  = array();
	
	foreach($var as $val)
	{
		$x          = explode('=', $val);
		$arr[$x[0]] = $x[1];
	}
	unset($val, $x, $var);
	return 'http://www.youtube.com/v/' . $arr['v'];
}

function YouTube_get_src($weblink, $type='swf', $xplode= '')
{
    $aData = array();
    if ($xplode == '') {
    	$exten  =  explode('.', $weblink);
	$exten  =  end($exten);
    	if ($exten == 'mp4') {
    		$aData['id'] = '';
    		$aData['img'] = '';
    		$aData['url'] = $weblink;
    		$aData['type'] = 'mp4';
    		return $aData;
    	}
    	
    	$x  =  explode('/', $weblink);
	$x  =  end($x);
    	if ($x != '') {
    	    if ($type == 'swf') {
    	        $aData['id'] = $x;
    	        $aData['img'] = 'https://img.youtube.com/vi/'.$x.'/0.jpg';
    	        $aData['url'] = 'https://www.youtube.com/v/' . $x;
    	        $aData['type'] = 'youtube';
    	        return $aData;
            } else {
                $aData['id'] = $x;
                $aData['img'] = 'https://img.youtube.com/vi/'.$x.'/0.jpg';
                $aData['url'] = 'https://www.youtube.com/embed/' . $x;
                $aData['type'] = 'youtube';
                return $aData;
            }
    	} else {
    		return '#';
    	}
    	
    } else {
        $x  =  end(explode($xplode, $weblink));
        if ($x != '') {
            if ($type == 'swf') {
                $aData['id'] = $x;
                $aData['url'] = 'https://www.youtube.com/v/' . $x;
                return $aData;
            } else {
                $aData['id'] = $x;
                $aData['url'] = 'https://www.youtube.com/embed/' . $x;
                return $aData;
            }
        } else {
            return '#';
        }
        
    }
}

function YouTube_image($weblink) 
{
	//SHAR
	$x  =  end(explode('/', $weblink));
	$url = 'http://img.youtube.com/vi/' . $x . '/0.jpg';
	
	//URL
	//$xx  =  explode('/watch?v=', $weblink);
	//$x	 = end($xx);
	//$url = 'http://img.youtube.com/vi/' . $x . '/default.jpg';
	//$url = 'http://i.ytimg.com/vi_webp/' . $x . '/default.webp';
	/*
    $resolution = array (
        'maxresdefault',
        'sddefault',
        'mqdefault',
        'hqdefault',
        'default'
    );*/
    return $url;
}

/*
วิธีการใช้งาน
// $currentpage = หน้าปัจจุบัน
// $url = URL ที่ต้องการให้แสดง
// $s_text = ข้อความก่อนหน้า
// $c_text = ข้อความกลางที่แสดงตัวเลขหน้า
// $e_text = ข้อความถัดไป
// $activename = ชื่อ class ที่ต้องการให้แสดงหน้าในปัจจุบัน

// ตัวอย่างการเรียกใช้งาน
BuilListPage($data, $url='?', $currentpage=1,
'<ul class="page-numbers"><li>a class="page-numbers" href="##BACKURL##">← &nbsp;&nbsp;&nbsp; Back Page </a></li>',
'<li><a class="page-numbers ##ACTIVE##" href="##PAGEURL##"> ##PAGE## </a></li>',
'<li><a class="next page-numbers" href="##NEXTURL##">Next Page</a></li></ul>',
'current'
);
*/

function BuilListPage($data, $url='?', $currentpage=1, $s_text='', $c_text='', $e_text='', $activename='')
{
	$currentpage = ($currentpage > 0) ? $currentpage : 1;
	$data['maxpage'] = ($data['maxpage'] > 0) ? $data['maxpage'] : 1;
	if ($data['num_rows'] > 0) {
		
		$rang = 3;
		$navi_start = $currentpage - $rang;
		$navi_end = $currentpage + $rang - 1;
		if($navi_start <= 0) $navi_start = 1;
		if ($navi_start == 1) {
			$navi_end = (($rang*2) > $data['maxpage']) ? $data['maxpage'] : ($rang*2);
		}
		if ($navi_end >= $data['maxpage'] && $navi_start != 1) {
			$navi_end = $data['maxpage'];
			$navi_start = $data['maxpage']-(($rang*2)-1);
		}

		$urlBackPage = $url.'&page='.$data['backpage'];
		$urlNextPage = $url.'&page='.$data['nextpage'];
		########### START ##########
		echo str_replace('##BACKURL##', $urlBackPage, $s_text);

		if ($navi_start > 1) {
			$txt = str_replace('##PAGEURL##', $url.'&page=1', $c_text);
			$txt = str_replace('##PAGE##', '1 ...', $txt);
			echo $txt;
		}

		for($i = $navi_start; $i <= $navi_end; $i++){
			$txt = str_replace('##ACTIVE##', (($currentpage == $i) ? 'current' : 'none'), $c_text);
			$txt = str_replace('##PAGEURL##', $url.'&page='.$i, $txt);
			$txt = str_replace('##PAGE##', $i, $txt);
			echo $txt;
		}

		if ($navi_end < $data['maxpage']) {
			$txt = str_replace('##PAGEURL##', $url.'&page='.$data['maxpage'], $c_text);
			$txt = str_replace('##PAGE##', $data['maxpage'], $txt);
			echo $txt;
		}
		
		echo str_replace('##PAGEURL##', $urlNextPage, $e_text);
		########### END ##########
	}
}

function ganarate_namecard()
{
	$id = array();
	$tmp_digit = array();
	$last_digit = 0;
	
	for($i = 0,$operand = 13;$i < 12; $i++,$operand--){
		if($i==0){
			$tmp_digit[$i] = $id[$i] = rand(1,9);
		}else{
			$tmp_digit[$i] = $id[$i] = rand(0,9);
		}
		$tmp_digit[$i] = $tmp_digit[$i] * $operand;
		$last_digit += $tmp_digit[$i];
	}
	$last_digit = $last_digit%11;
	$last_digit = 11-$last_digit;
	$last_digit = $last_digit%10;
	$id[12] = $last_digit;
	$strid = $id[0].$id[1].$id[2].$id[3].$id[4].$id[5].$id[6].$id[7].$id[8].$id[9].$id[10].$id[11].$id[12];
	return $strid;
}

function Api_readBathThai($number)
{
	$txtnum1 = array('ศูนย์','หนึ่ง','สอง','สาม','สี่','ห้า','หก','เจ็ด','แปด','เก้า','สิบ');
	$txtnum2 = array('','สิบ','ร้อย','พัน','หมื่น','แสน','ล้าน','สิบ','ร้อย','พัน','หมื่น','แสน','ล้าน');
	$number = str_replace(",","",$number);
	$number = str_replace(" ","",$number);
	$number = str_replace("บาท","",$number);
	$number = explode(".",$number);
	if(sizeof($number)>2){
		return 'ทศนิยมหลายตัวนะจ๊ะ';
		exit;
	}
	$strlen = strlen($number[0]);
	$convert = '';
	for($i=0;$i<$strlen;$i++){
		$n = substr($number[0], $i,1);
		if($n!=0){
			if($i==($strlen-1) AND $n==1){ $convert .= 'เอ็ด'; }
			elseif($i==($strlen-2) AND $n==2){  $convert .= 'ยี่'; }
			elseif($i==($strlen-2) AND $n==1){ $convert .= ''; }
			else{ $convert .= $txtnum1[$n]; }
			$convert .= $txtnum2[$strlen-$i-1];
		}
	}

	$convert .= 'บาท';
	if($number[1]=='0' OR $number[1]=='00' OR
	$number[1]==''){
		$convert .= 'ถ้วน';
	}else{
		$strlen = strlen($number[1]);
		for($i=0;$i<$strlen;$i++){
			$n = substr($number[1], $i,1);
			if($n!=0){
				if($i==($strlen-1) AND $n==1){$convert
				.= 'เอ็ด';}
				elseif($i==($strlen-2) AND
				$n==2){$convert .= 'ยี่';}
				elseif($i==($strlen-2) AND
				$n==1){$convert .= '';}
				else{ $convert .= $txtnum1[$n];}
				$convert .= $txtnum2[$strlen-$i-1];
			}
		}
		$convert .= 'สตางค์';
	}
	return $convert;
}

function _builpassword($pass)
{
	if (ENCODE_PASSWORD_MEMBER == true) {
		return md5(md5($pass));
	} else {
		return $pass;
	}
}

function setRaiseMsg($detail='',$key='',$type=0)
{
	if ($type == 1 || $type == 'error' && $type != 0) {
		//error There was a problem inserting the record
		$_SESSION['adminArror'][$key] = $detail != '' ? $detail : 'Error :: Cannot update record !';
	} else {
		$_SESSION['success'][$key] = $detail != '' ? $detail : 'Data successfully updated.';
	}
}
