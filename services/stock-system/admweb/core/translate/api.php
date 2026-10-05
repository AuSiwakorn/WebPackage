<?php
/*
function ___lang($txt='', $isReturn=false)
{
	global $lang, $aosoft_lang;
	$txt = trim($txt);
	$aosoft_lang_array = array();
	$default_aosoft_lang = array();
	$aCustomLang = array();
	
	if ($txt == '') {
		$strReturn = '';
	}
	
	$isCheck = 0;
	if (is_array($aosoft_lang) && count($aosoft_lang) > 0) {
		foreach ($aosoft_lang as $k => $v) {
			if (count($v) > 0 && is_array($aosoft_lang)) {
				$isCheck = 1;
				break;
			}
		}
	}
	
	if ($isCheck == 0) {
		$language_default_file = PATH_AOWEBDATA . '/languages/default.php';
		if (is_file($language_default_file)) {
			include_once $language_default_file;
		}
		
		$language_file = PATH_AOWEBDATA . '/languages/'.$lang.'.php';
		if (is_file($language_file)) {
			include_once $language_file;
			$aosoft_lang_array = __custom_array_merge($default_aosoft_lang,$aosoft_lang);
		} else {
			$aosoft_lang_array = $default_aosoft_lang;
		}
		
		$language_custom_file = PATH_UPLOAD . '/translate/'.$lang.'.php';
		if (is_file($language_custom_file)) {
			include_once $language_custom_file;
			$aosoft_lang_array = __custom_array_merge($aosoft_lang_array,$aCustomLang);
		} else {
			$aosoft_lang_array = $aosoft_lang_array;
		}
		
		foreach ($aosoft_lang_array as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $kk => $vv) {
					$aosoft_lang[$kk] = $vv;
				}
			}
		}
	}
	
	if (isset($aosoft_lang) && isset($aosoft_lang[$txt])) {
		$strReturn = $aosoft_lang[$txt];
	} else {
		$strReturn = $txt;
		__add_lang_req_keys($txt);
	}
	
	if ($isReturn != '') {
		return $strReturn;
	} else {
		echo $strReturn;
	}
}
	*/
/*
function __add_lang_req_keys($keys)
{
	$fp = fopen(PATH_UPLOAD . '/translate/req.txt', 'a');
	fwrite($fp, "\n ".$keys);
	fclose($fp);
}

function __custom_array_merge($a1, $a2)
{
	$aArray = array();
	foreach ($a1 as $k => $v) {
		$aArray[$k] = isset($a2[$k]) ? array_merge($v,$a2[$k]) : $v;
	}
	return $aArray;
}
*/
