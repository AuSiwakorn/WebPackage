<?php

function buil_php_config_data2($aDatabuil, $aName = '$aTest')
{

	$txt = '';
	if (is_array($aDatabuil)) {
		foreach ($aDatabuil as $k => $v) {
			if (is_array($v)) {
				$txt .= '/* ' . $k . ' */' . "\n";
				$txt .= buil_php_config_data($v, $aName . '[\'' . $k . '\']');
			} else {
				$txt .= $aName . '[\'' . $k . '\'] = "' . $v . '"; ' . "\n";
			}
		}
	}
	return $txt;
}


function write_php_config($filename, $data)
{
	$fp = @fopen($filename, 'w');
	if ($fp) {
		$string = "<?php\n" . $data . "\n ?>";
		$len = strlen($string);
		@flock($fp, LOCK_EX);
		@fwrite($fp, $string, $len);
		@flock($fp, LOCK_UN);
		@fclose($fp);
		return true;
	}
}

function func_translate_get_default2()
{
	global $aDefaultLang;

	$languagefile = PATH_AOWEBDATA . '/languages/default.php';
	if (is_file($languagefile) && count($aDefaultLang) == 0) {
		include_once $languagefile;
		$aDefaultLang = $default_aosoft_lang;
	}
	return $aDefaultLang;
}

function func_translate_get_lang2($forlang = '', $keysname = '')
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


function get_data_array_custom_lang2($forlang = 'th')
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


function custom_array_merge2($a1, $a2)
{
	$aArray = array();
	foreach ($a1 as $k => $v) {
		$aArray[$k] = isset($a2[$k]) ? array_merge($v, $a2[$k]) : $v;
	}
	return $aArray;
}
