<?php

global $globallang, $aDefaultLang;
$aDefaultLang = array();

function ___lang($name = '', $isReturn = false)
{
	//global $admin_ao_lang;
	$txt = (isset($admin_ao_lang[$name])) ? $admin_ao_lang[$name] : $name;

	if ($isReturn == false) {
		echo $txt;
	} else {
		return $txt;
	}
}

function func_translate_get_default()
{
	global $aDefaultLang;
	$languagefile = PATH_AOWEBDATA . '/languages/default.php';
	if (is_file($languagefile) && count($aDefaultLang) == 0) {
		include_once $languagefile;
		$aDefaultLang = $default_aosoft_lang;
	}
	return $aDefaultLang;
}

function func_translate_get_lang($forlang = '', $keysname = '')
{
	global $lang, $globallang, $aDefaultLang;
	$forlang = ($forlang == '') ? $lang : $forlang;
	$aByLang = array();
	if (!isset($globallang[$forlang]) || count($aDefaultLang) == 0) {
		$aDefaultLang = func_translate_get_default();
		$languagefile = PATH_AOWEBDATA . '/languages/' . $forlang . '.php';
		if (is_file($languagefile)) {
			include_once $languagefile;
			$aByLang = $aosoft_lang;
		}
		$globallang[$forlang] = custom_array_merge($aDefaultLang, $aByLang);
	}
	if ($keysname != '') {
		$a[$keysname] = isset($globallang[$forlang][$keysname]) ? $globallang[$forlang][$keysname] : array();
		return $a;
	} else {
		return isset($globallang[$forlang]) ? $globallang[$forlang] : array();
	}
}

function custom_array_merge($a1, $a2)
{
	$aArray = array();
	foreach ($a1 as $k => $v) {
		$aArray[$k] = isset($a2[$k]) ? array_merge($v, $a2[$k]) : $v;
	}
	return $aArray;
}

function get_data_array_custom_lang($forlang = 'th')
{
	global $aCustomLangBy;
	if (!isset($aCustomLangBy[$forlang])) {
		$languagefile = PATH_UPLOAD . '/translate/' . $forlang . '.php';
		if (is_file($languagefile)) {
			include_once $languagefile;
			$aCustomLangBy = array();
			$aCustomLangBy[$forlang] = (isset($aCustomLang) && count($aCustomLang) > 0) ? $aCustomLang : array();
		}
	}
	return isset($aCustomLangBy[$forlang]) ? $aCustomLangBy[$forlang] : array();
}
