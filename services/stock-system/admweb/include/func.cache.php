<?php
function api_get_cache($filename, $isCheck = true)
{
	if (is_file(PATH_UPLOAD . "/cache_html/" . $filename)) {
		if ($isCheck === true) {
			return true;
		} else {
			include(PATH_UPLOAD . "/cache_html/" . $filename);
		}
	} else {
		return false;
	}
}

function api_cache_new($txt = '', $filename = '', $isEcho = false)
{
	if (!is_dir(PATH_UPLOAD . "/cache_html")) {
		mkdir(PATH_UPLOAD . "/cache_html", 0777);
	}
	$file = fopen(PATH_UPLOAD . "/cache_html/" . $filename, "w");
	fwrite($file, $txt);
	fclose($file);

	if ($isEcho == 'echo') {
		echo $txt;
	}
}

/**
 * วิธีใช้งาน
 * //เพิ่มใน inc.php หรือ บนสุดของไฟล์
 * $isCachePage = 'home.ao.html';
 * $isCachePage = (isset($isCachePage)) ? $isCachePage : '';
 * include 'admweb/include/func.cache.php';
 * AO_Cache($isCachePage);
 * ob_start();
 * 
 * 
 * 
 * เพิ่มส่วนล่างสุดหลังปิด html แล้ว
 * if ($isCachePage != '') {
 *   AO_CacheEnd($isCachePage);
 * }
 * 
 */

function AO_CacheW2($txt = '', $filename = '')
{
	global $lang;
	$file = fopen(PATH_UPLOAD . "/html_cache/" . $lang . '.' . $filename . '.ao', "w");
	fwrite($file, $txt);
	fclose($file);
}

/**
 * AO_Cache('portfolio-inner-'.$id.'.html');
 * AO_Cache('home.ao.html');
 * ob_start();
 */
function AO_Cache($filename = '')
{
	global $lang;
	if ($filename == '') {
		return false;
	}

	$htmlCachePath = PATH_UPLOAD . '/html_cache';
	if (!is_dir($htmlCachePath)) {
		mkdir($htmlCachePath, 0777);
	}

	$htmlCachePath = $htmlCachePath . '/' . $lang . '.' . $filename . '.ao';
	if (file_exists($htmlCachePath)) {
		include $htmlCachePath;
		exit;
	}

	ob_start();
}

/**
 * $cache = ob_get_contents();
 * ob_end_clean();
 * AO_CacheEnd('portfolio-inner-'.$id.'.html', $cache);
 * AO_CacheEnd('portfolio', $cache);
 */
function AO_CacheEnd($filename)
{
	global $lang;
	$cache = ob_get_contents();
	ob_end_clean();

	if (!is_dir(PATH_UPLOAD . "/html_cache")) {
		mkdir(PATH_UPLOAD . "/html_cache", 0777);
	}

	$htmlCachePath = PATH_UPLOAD . '/html_cache/' . $lang . '.' . $filename . '.ao';
	file_put_contents($htmlCachePath, $cache);
	//AO_CacheW2($cache, $htmlCachePath);
	echo $cache;
	exit;
}

/**
 * $aHtmlCache = array()
 * $aHtmlCache[] = 'home.ao.html';
 * $aHtmlCache[] = 'about.ao.html';
 */
function AO_Cache_Clear($aHtmlCache)
{
	global $aConfig;
	$htmlCachePath = PATH_UPLOAD . '/html_cache';
	foreach ($aConfig['language'] as $langkey => $vv) {
		foreach ($aHtmlCache as $v) {
			if ($v != '') {
				$fcache = $htmlCachePath . '/' . $langkey . '.' . $v . '.ao';
				if (file_exists($fcache)) {
					unlink($fcache);
				}
			}
		}
	}
}

function AO_Clear_All()
{
	$htmlCachePath = PATH_UPLOAD . '/html_cache';
	$dirs = glob($htmlCachePath . '/*.ao');
	foreach ($dirs as $v) {
		if ($v != '') {
			if (file_exists($v)) {
				unlink($v);
			}
		}
	}
}
