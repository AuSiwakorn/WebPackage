<?php
include 'func.cache.php';
function PIC_Corp($filename, $nopic = '', $option = 'w=250')
{
	return ($filename != '') ? URL_UPLOAD . '/piccorp/webroot/img.php?src=' . $filename . '&' . $option . '&crop-to-fit' : $nopic;
}

function Func_HelpUse($filename)
{
	if (OPEN_HELP_API == true) {
		echo '<pre>';
		if (file_exists(PATH_PLUGIN . '/' . $filename)) {
			include PATH_PLUGIN . '/' . $filename;
		}
		echo '</pre>';
	}
}

function Func_Addlogs($txt = 'NULL')
{
	if (!is_dir(PATH_UPLOAD . "/logs")) {
		@mkdir(PATH_UPLOAD . "/logs", 0777, true);
	}
	if (!file_exists(PATH_UPLOAD . "/logs/logs.txt")) {
		$file = fopen(PATH_UPLOAD . "/logs/logs.txt", "w");
	}
	$oUser = login_logout::getLoginData();
	$txt = '[UID:' . $oUser->user_id . '] ' . $txt;

	$time = date('d-m-Y   H:i:s');
	$file = fopen(PATH_UPLOAD . "/logs/logs.txt", "a");
	fwrite($file, $time . '   ' . $txt . "\n");
	fclose($file);
}

function Func_SEOSet($n = '')
{
	global $aConfig; // for fix error in function มีการเรียกใช้งาน $aConfig ใน inc_metas.php
	$_REQUEST['metakey'] = $n;
	include_once(PATH_PLUGIN . '/seo/inc_metas.php');
}

function Func_encode_builpassword($pass)
{
	if (ENCODE_PASSWORD_MEMBER == true) {
		return password_hash($pass, PASSWORD_ARGON2ID,  [
			'MEMORY_COST' => MEMORY_COST,
			'time_cost'   => TIME_COST,
			'threads'     => THREADS
		]);
	} else {
		return $pass;
	}
}

function displaySize($size)
{
	if (is_numeric($size)) {
		$decr = 1024;
		$step = 0;
		$prefix = array('Byte', 'KB', 'MB', 'GB', 'TB', 'PB');

		while (($size / $decr) > 0.9) {
			$size = $size / $decr;
			$step++;
		}
		return round($size, 2) . ' ' . $prefix[$step];
	} else {
		return 'NaN';
	}
}

function disk_used_space2($dir_name)
{
	$dir_size = 0;
	if (is_dir($dir_name)) {
		if ($dh = opendir($dir_name)) {
			while (($file = readdir($dh)) !== false) {
				if ($file != "." && $file != "..") {
					if (is_file($dir_name . "/" . $file)) {
						$dir_size += filesize($dir_name . "/" . $file);
					}
					/* check for any new directory inside this directory */
					if (is_dir($dir_name . "/" . $file)) {
						$dir_size +=  disk_used_space2($dir_name . "/" . $file);
					}
				}
			}
		}
		closedir($dh);
	}
	return $dir_size;
}

/**
 * Use in new admin2018
 * by siwakorn induang
 */
function getOtbLink()
{
	$otb = REQ_get('otb', 'request', '');
	$a = explode('|', $otb);
	return $a;
}

function ADM_Link($link, $isNotOTB = false)
{
	return _admin_buil_link($link, $isNotOTB);
}

function _admin_buil_link($link, $isNotOTB = false)
{
	global $otb;
	if ($isNotOTB == true) {
		return $link;
	}

	$otb2 = REQ_get('otb', 'request', $otb);
	return $link . '&otb=' . $otb2;
}

function CustomRedirectToUrl($url = 'index.php', $is_NotBuilLink = false)
{
	if ($url == 'refresh') {
		$is_NotBuilLink = true;
		$url = $_SERVER['REQUEST_URI'];
	}

	if ($is_NotBuilLink == true) {
		if (IS_MATA_REDIRECT == true) {
			echo '<meta http-equiv="refresh" content="0;URL=' . $url . '" />';
		} else {
			@header("HTTP/1.1 301 Moved Permanently");
			@header("Location:" . $url);
		}
	} else {
		if (IS_MATA_REDIRECT == true) {
			echo '<meta http-equiv="refresh" content="0;URL=' . _admin_buil_link($url) . '" />';
		} else {
			@header("HTTP/1.1 301 Moved Permanently");
			@header("Location:" . _admin_buil_link($url));
		}
	}
	exit;
}

function exportJSAndCSS($aAddJsAndCss)
{
	if (count($aAddJsAndCss) > 0) {
		foreach ($aAddJsAndCss as $k => $v) {
			if ($v['type'] == 'css') {
				echo '<link type="text/css" href="' . $v['file'] . '" rel="stylesheet" />' . "\n";
			} elseif ($v['type'] == 'js') {
				echo '<script type="text/javascript" src="' . $v['file'] . '"></script>' . "\n";
			}
		}
	}
}

function dirList($dir)
{

	if ($dir[strlen($dir) - 1] != '/') {
		$dir .= '/';
	}
	if (!is_dir($dir)) {
		return array();
	}

	$dir_handle  = opendir($dir);
	$dir_objects = array();
	while ($object = readdir($dir_handle)) {
		if (!in_array($object, array('.', '..'))) {
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

function buil_php_config_data($aDatabuil, $aName = '$aTest')
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

function write_php_config($fname, $data)
{
	$fp = @fopen($fname, 'w');
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

function write_txt_json($fname, $data)
{
	$fp = @fopen($fname, 'w');
	if ($fp) {
		$str = json_encode($data);
		$len = strlen($str);
		@flock($fp, LOCK_EX);
		@fwrite($fp, $str, $len);
		@flock($fp, LOCK_UN);
		@fclose($fp);
		return true;
	}
}

function write_txt_json_a($fname, $data)
{
	$found = false;
	if (file_exists($fname)) {
		$fcont = file($fname, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($fcont as $l) {
			if (json_decode($l, true) === $data) {
				$found = true;
				break;
			}
		}
	}
	if (!$found) {
		$fp = fopen($fname, 'a');
		if ($fp) {
			$str = json_encode($data);
			if ($str !== false) {
				flock($fp, LOCK_EX);
				fwrite($fp, $str . PHP_EOL);
				flock($fp, LOCK_UN);
				fclose($fp);
				return true;
			}
		}
	}
	return false;
}

function read_txt_json($fname)
{
	$data = @file($fname);
	if (isset($data[0])) {
		$data = json_decode($data[0], true);
	} else {
		$data = [];
	}
	return $data;
}

function read_txt_json_a($fname)
{
	$data = [];
	if (file_exists($fname)) {
		$ls = array_map('trim', file($fname));
		foreach ($ls as $l) {
			$jsonData = json_decode($l, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				$data[] = read_txt_json_a_nested($jsonData);
			}
		}
	}
	return $data;
}

function read_txt_json_a_nested($data)
{
	foreach ($data as $k => &$v) {
		if (is_array($v) && count($v) === 1 && isset($v[0])) {
			$v = $v[0];
		} elseif (is_array($v)) {
			$v = read_txt_json_a_nested($v);
		}
	}
	return $data;
}

function permitMenuMaps($module)
{
	static $cache = [];
	if (isset($cache[$module])) {
		return $cache[$module];
	}
	$fmodule = PATH_MODULE . '/' . $module . '/__menu.php';
	$fcore = PATH_CORE . '/' . $module . '/__menu.php';
	$path = is_file($fmodule) ? $fmodule : $fcore;
	$out = ['title' => '', 'keyMap' => [], 'mpMap' => [], 'childMap' => []];
	if (is_file($path)) {
		$_aMenuList = array();
		$title = '';
		try {
			include $path;
		} catch (\Throwable $e) {
			$cache[$module] = $out;
			return $out;
		}
		$out['title'] = (string)$title;
		$mpOf = function ($link) {
			return preg_match('/[?&]mp=([^&#]+)/', (string)$link, $m) ? $m[1] : '';
		};
		$addChild = function ($node, $grp) use (&$out) {
			foreach ((array)($node['child'] ?? []) as $cmp) {
				$cmp = (string)$cmp;
				if ($cmp !== '' && !isset($out['childMap'][$cmp])) {
					$out['childMap'][$cmp] = $grp;
				}
			}
		};
		foreach (($_aMenuList['subhead'] ?? []) as $v) {
			if (!is_array($v)) {
				continue;
			}
			$grp = $v['name'] ?? '';
			$hlink = $v['link'] ?? '#';
			$out['keyMap'][md5($module . $hlink)] = [$grp, 'head'];
			$mp = $mpOf($hlink);
			if ($mp !== '' && !isset($out['mpMap'][$mp])) {
				$out['mpMap'][$mp] = $grp;
			}
			$addChild($v, $grp);
			foreach (($v['menu'] ?? []) as $vv) {
				if (!is_array($vv) || !isset($vv['link'])) {
					continue;
				}
				$out['keyMap'][md5($vv['link'])] = [$grp, 'submenu'];
				$mp2 = $mpOf($vv['link']);
				if ($mp2 !== '' && !isset($out['mpMap'][$mp2])) {
					$out['mpMap'][$mp2] = $grp;
				}
				$addChild($vv, $grp);
				foreach (($vv['menu'] ?? []) as $vvv) {
					if (!is_array($vvv) || !isset($vvv['link'])) {
						continue;
					}
					$out['keyMap'][md5($vvv['link'])] = [$grp, 'subsubmenu'];
					$mp3 = $mpOf($vvv['link']);
					if ($mp3 !== '' && !isset($out['mpMap'][$mp3])) {
						$out['mpMap'][$mp3] = $grp;
					}
					$addChild($vvv, $grp);
				}
			}
		}
	}
	$cache[$module] = $out;
	return $out;
}

function getPermitModuleTitle($module)
{
	$m = permitMenuMaps($module);
	return ($m['title'] !== '') ? $m['title'] : strtoupper($module);
}

function permitGuessParentMp($mp, $mpMap)
{
	if ($mp === '' || !is_array($mpMap)) {
		return '';
	}
	$prefixes = ['add_', 'edit_', 'view_', 'print_', 'data_', 'detail_'];
	$suffixes = ['_add', '_edit', '_editview', '_view', '_data', '_detail', '_list'];
	foreach ($prefixes as $p) {
		if (strncmp($mp, $p, strlen($p)) === 0) {
			$base = substr($mp, strlen($p));
			if (isset($mpMap[$base])) {
				return $mpMap[$base];
			}
		}
	}
	foreach ($suffixes as $s) {
		$len = strlen($s);
		if (strlen($mp) > $len && substr($mp, -$len) === $s) {
			$base = substr($mp, 0, -$len);
			if (isset($mpMap[$base])) {
				return $mpMap[$base];
			}
		}
	}
	return '';
}

function permitGroupRegistry()
{
	$reg = PERMIT::PERMIT_GET();
	$out = [];
	if (!is_array($reg)) {
		return $out;
	}
	foreach ($reg as $module => $keys) {
		if (!is_array($keys)) {
			continue;
		}
		$maps = permitMenuMaps($module);
		foreach ($keys as $key => $label) {
			if (preg_match('/^[0-9a-f]{32}$/', (string)$key)) {
				if (isset($maps['keyMap'][$key])) {
					$grp = $maps['keyMap'][$key][0];
					$lv = $maps['keyMap'][$key][1];
				} else {
					$grp = 'ทั่วไป';
					$lv = 'submenu';
				}
			} elseif (strpos((string)$key, '#') === 0) {
				$mp = preg_match('/#module:[^#]+##mp:([^#]+)#/', (string)$key, $m) ? $m[1] : '';
				$grp = 'ทั่วไป';
				if ($mp !== '') {
					if (isset($maps['mpMap'][$mp])) {
						$grp = $maps['mpMap'][$mp];
					} elseif (isset($maps['childMap'][$mp])) {
						$grp = $maps['childMap'][$mp];
					} else {
						$guess = permitGuessParentMp($mp, $maps['mpMap']);
						if ($guess !== '') {
							$grp = $guess;
						}
					}
				}
				$lv = 'page';
			} else {
				$grp = 'Option';
				$lv = 'option';
			}
			$out[$module][$grp][$lv][$key] = $label;
		}
	}
	return $out;
}

function permitPageKeyFromLink($link)
{
	$m = preg_match('/[?&]module=([^&]+)/', (string)$link, $a) ? $a[1] : '';
	$mp = preg_match('/[?&]mp=([^&]+)/', (string)$link, $b) ? $b[1] : '';
	if ($m === '' || $mp === '') {
		return '';
	}
	return '#module:' . $m . '##mp:' . $mp . '#';
}

function permitMenuVisible($module, $menuKey, $name, $link)
{
	$has = PERMIT::_PERMIT($module, $menuKey, $name, 'return');
	if ($has && PERMIT::PERMIT_HAS_KEY($menuKey)) {
		return true;
	}
	if (in_array('all', login_logout::getAdminPermission())) {
		return true;
	}
	$pk = permitPageKeyFromLink($link);
	return ($pk !== '' && PERMIT::PERMIT_VALIDATE($pk));
}

function getMenuAdmin3()
{
	global $aConfig;
	$a = array();
	foreach ($aConfig['aModuleUse'] as $moduleName) {
		$fmodule = PATH_MODULE . '/' . $moduleName . '/__menu.php';
		$fcore = PATH_CORE . '/' . $moduleName . '/__menu.php';
		$modulePath = is_file($fmodule) ? $fmodule : $fcore;
		if (is_file($modulePath)) {
			$_aMenuList = array();
			include($modulePath);
			if (isset($_aMenuList['subhead']) && is_array($_aMenuList['subhead']) && count((array)$_aMenuList['subhead']) > 0) {
				foreach ($_aMenuList['subhead'] as $k => $v) {
					if (!permitMenuVisible($moduleName, md5($moduleName . $v['link']), 'แสดงเมนู ' . $v['name'], $v['link'])) {
						unset($_aMenuList['subhead'][$k]);
					}
					if (isset($v['menu']) && count($v['menu']) > 0) {
						foreach ($v['menu'] as $kk => $vv) {
							if (!permitMenuVisible($moduleName, md5($vv['link']), 'แสดงเมนูย่อย ' . $vv['name'], $vv['link'])) {
								unset($_aMenuList['subhead'][$k]['menu'][$kk]);
							}
							if (isset($vv['menu']) && count($vv['menu']) > 0) {
								foreach ($vv['menu'] as $kkk => $vvv) {
									if (!permitMenuVisible($moduleName, md5($vvv['link']), 'แสดงเมนูย่อยในย่อย ' . $vvv['name'], $vvv['link'])) {
										unset($_aMenuList['subhead'][$k]['menu'][$kk]['menu'][$kkk]);
									}
								}
							}
						}
					}
				}
			}
			if (isset($_aMenuList['subhead']) && count((array)$_aMenuList['subhead']) > 0) {
				$a[$moduleName] = $_aMenuList;
			}
		}
	}
	return $a;
}

function displayRaiseMsg()
{
	if (isset($_SESSION['success']) && count($_SESSION['success']) > 0) {
		foreach ($_SESSION['success'] as $okKey => $okValue) {
			echo '
				<div class="alert alert-success">
					<button class="close" data-dismiss="alert"><i class="pci-cross pci-circle"></i></button>
					<strong>Successfully</strong> ' . $okValue . '
				</div>';
		}
		$_SESSION['success'] = array();
	}

	/* ----------------------------------------- */
	if (isset($_SESSION['adminArror']) && count($_SESSION['adminArror']) > 0) {
		// error
		foreach ($_SESSION['adminArror'] as $errorKeys => $errorValue) {
			echo '
				<div class="alert alert-danger">
					<button class="close" data-dismiss="alert"><i class="pci-cross pci-circle"></i></button>
					<strong>Error!</strong> ' . $errorValue . '
				</div>';
		}

		// reset data
		$_SESSION['adminArror'] = array();
	}
}

function setRaiseMsg($detail = '', $key = '', $type = 0)
{
	if ($type == 1 || $type == 'error' && $type != 0) {
		//error There was a problem inserting the record
		$_SESSION['adminArror'][$key] = $detail != '' ? $detail : 'Error :: Cannot update record !';
	} else {
		$_SESSION['success'][$key] = $detail != '' ? $detail : 'Data successfully updated.';
	}
}

function getHTMLTemp($filename)
{
	global $pathAdsHtml;
	if (is_file($pathAdsHtml . '/' . $filename . '.html')) {
		$h = file($pathAdsHtml . '/' . $filename . '.html');
		$h = implode("", $h);
		$h = str_replace("\r", "", $h);
		$h = str_replace("  ", " ", $h);
		return stripslashes(str_replace("\t", "", $h));
	}
}

function writeHTMLTemp($filename, $html)
{
	$fp = fopen(PATH_UPLOAD_HTML . '/' . $filename . '.html', 'w');
	fwrite($fp, $html);
	fclose($fp);
}

function mysqlquot($value)
{
	return str_replace("\"", "&quot;", $value);
}

function mysqlPrep($value)
{
	return $value;
}

function sanitizeSlug($slug)
{
	$slug = trim($slug);
	$slug = mb_strtolower($slug, 'UTF-8');
	$slug = preg_replace('/\s+/', '-', $slug);
	$slug = preg_replace('/[^a-z0-9\p{Thai}-]/u', '', $slug);
	$slug = preg_replace('/-+/', '-', $slug);
	$slug = trim($slug, '-');

	return $slug;
}


function api_strTimeFormat($timestamp = '', $format = "d-m-Y H:i:s", $isToday = false)
{
	return strTimeFormat($timestamp, $format, $isToday);
}

function strTimeFormat($timestamp = '', $format = "d-m-Y H:i:s", $isToday = false)
{
	if ($timestamp == 0 || $timestamp == '' || $timestamp == null) {
		return '-';
	}
	
	// รองรับทั้ง Unix timestamp (int) และ datetime string จาก MySQL
	if (is_string($timestamp) && !is_numeric($timestamp)) {
		$timestamp = strtotime($timestamp);
	}
	$timestamp = ($timestamp) ? (int)$timestamp : _TIME_;

	if ($format == '') {
		$format = "d-m-Y H:i:s";
	}

	if ($isToday == true) {
		// เช็คทั้ง วัน-เดือน-ปี เพื่อกัน false positive ปีอื่น
		if (date('Ymd', $timestamp) === date('Ymd', _TIME_)) {
			return '<b>วันนี้</b> ' . date('H:i:s', $timestamp);
		}
	}

	$format = preg_replace("~%~", "", $format);
	return date($format, $timestamp);
}

function BuilListPage($data, $url = '?', $currentpage = 1)
{
	$data['maxpage'] = (@$data['maxpage'] > 0) ? @$data['maxpage'] : 1;
	if (@$data['num_rows'] > 0) {
		if (@$data['backpage'] <= 1) {
			$c = 'disabled';
			$urlBack = 'javascript:;';
		} else {
			$c = 'noneDis';
			$urlBack = $url . '&page=' . $data['backpage'];
		}

		echo '
		<ul class="pagination">
			<li class="' . $c . '"><a href="' . $urlBack . '" class="demo-pli-arrow-left"></a></li>
		';
		$rang = 3;
		$navi_start = $currentpage - $rang;
		$navi_end = $currentpage + $rang - 1;
		if ($navi_start <= 0) $navi_start = 1;
		if ($navi_start == 1) {
			$navi_end = (($rang * 2) > $data['maxpage']) ? $data['maxpage'] : ($rang * 2);
		}
		if ($navi_end >= $data['maxpage'] && $navi_start != 1) {
			$navi_end = $data['maxpage'];
			$navi_start = $data['maxpage'] - (($rang * 2) - 1);
		}
		if ($navi_start > 1) {
			echo '<li><a href="' . $url . '&page=1">1</a></li>';
			echo '<li><span>...</span></li>';
		}
		for ($i = $navi_start; $i <= $navi_end; $i++) {
			$action = ($currentpage == $i) ? 'active' : 'page_n';
			echo '<li class="' . $action . '"><a href="' . $url . '&page=' . $i . '">' . $i . '</a></li>';
		}
		if ($navi_end < @$data['maxpage']) {
			echo '<li><a href="' . $url . '&page=' . @$data['maxpage'] . '">... ' . @$data['maxpage'] . '</a></li>';
		}

		if (@$data['nextpage'] >= @$data['maxpage']) {
			$c = 'disabled';
			$urlNext = 'javascript:;';
		} else {
			$c = 'noneDis';
			$urlNext = $url . '&page=' . @$data['nextpage'];
		}
		echo '<li class="' . $c . '"><a href="' . $urlNext . '" class="demo-pli-arrow-right"></a></li>';
		echo '</ul>';
	}
}

function write_php_ini($array, $file)
{
	$res = array();
	foreach ($array as $key => $val) {
		if (is_array($val)) {
			$res[] = "[$key]";
			foreach ($val as $skey => $sval) $res[] = "$skey = " . (is_numeric($sval) ? $sval : '"' . $sval . '"');
		} else $res[] = "$key = " . (is_numeric($val) ? $val : '"' . $val . '"');
	}
	return safefilerewrite($file, implode("\r\n", $res));
}

function safefilerewrite($fileName, $dataToSave)
{
	if ($fp = fopen($fileName, 'w+')) {
		$startTime = microtime();
		do {
			$canWrite = flock($fp, LOCK_EX);
			// If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
			if (!$canWrite) {
				usleep(round(rand(0, 100) * 1000));
			}
		} while ((!$canWrite) and ((microtime() - $startTime) < 1000));

		//file was locked so now we can store information
		if ($canWrite) {
			$dataToSave .= ";<?php exit; ?>\n" . $dataToSave;
			fwrite($fp, $dataToSave);
			flock($fp, LOCK_UN);
			fclose($fp);
			return true;
		}
		fclose($fp);
	}
	return 'is not writable!';
}

function pre($a, $isAdm = array())
{
	if ($isAdm != false) {
		$oUser = login_logout::getLoginData();
		if (in_array($oUser->user, $isAdm)) {
			echo '<pre>';
			print_r($a);
			echo '</pre>';
		}
	} else {
		echo '<pre>';
		print_r($a);
		echo '</pre>';
	}
}

function url_images_module($module = '', $name = '')
{
	if ($name != '' && $module != '') {
		$fmodule = PATH_MODULE . '/' . $module . '/images/' . $name;
		$fcore = PATH_CORE . '/' . $module . '/images/' . $name;
		$pathImage = is_file($fmodule) ? $fmodule : $fcore;
		if (file_exists($pathImage)) {
			$urlImage = ($pathImage == $fmodule) ? URL_MODULE : URL_CORE;
			return $urlImage . '/' . $module . '/images/' . $name;
		} else {
			return TEMPLATE_URL . '/img/' . $name;
		}
	}
}

function getCurrentLang()
{
	global $aConfig, $lang;
	if (isset($_SESSION['current']['lang']) && $_SESSION['current']['lang'] != '') {
		$lang = $_SESSION['current']['lang'];
		if ($aConfig['language'][$lang] != '') {
			return $_SESSION['current']['lang'];
			exit;
		}
	}

	$_SESSION['current']['lang'] = DEFAULT_LANGEUAGE;
	return DEFAULT_LANGEUAGE;
}

function Api_readBathThai($number)
{
	$txtnum1 = array('ศูนย์', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า', 'สิบ');
	$txtnum2 = array('', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน');
	$number = str_replace(",", "", $number);
	$number = str_replace(" ", "", $number);
	$number = str_replace("บาท", "", $number);
	$number = explode(".", $number);
	if (sizeof($number) > 2) {
		return 'ทศนิยมหลายตัวนะจ๊ะ';
		exit;
	}
	$strlen = strlen($number[0]);
	$convert = '';
	for ($i = 0; $i < $strlen; $i++) {
		$n = substr($number[0], $i, 1);
		if ($n != 0) {
			if ($i == ($strlen - 1) and $n == 1) {
				$convert .= 'เอ็ด';
			} elseif ($i == ($strlen - 2) and $n == 2) {
				$convert .= 'ยี่';
			} elseif ($i == ($strlen - 2) and $n == 1) {
				$convert .= '';
			} else {
				$convert .= $txtnum1[$n];
			}
			$convert .= $txtnum2[$strlen - $i - 1];
		}
	}

	$convert .= 'บาท';
	if ($number[1] == '0' || $number[1] == '00' || $number[1] == '') {
		$convert .= 'ถ้วน';
	} else {
		$strlen = strlen($number[1]);
		for ($i = 0; $i < $strlen; $i++) {
			$n = substr($number[1], $i, 1);
			if ($n != 0) {
				if ($i == ($strlen - 1) and $n == 1) {
					$convert .= 'เอ็ด';
				} elseif ($i == ($strlen - 2) && $n == 2) {
					$convert .= 'ยี่';
				} elseif ($i == ($strlen - 2) && $n == 1) {
					$convert .= '';
				} else {
					$convert .= $txtnum1[$n];
				}
				$convert .= $txtnum2[$strlen - $i - 1];
			}
		}
		$convert .= 'สตางค์';
	}
	return $convert;
}

function get_images_detail($picPath = '')
{
	if ($picPath == '' || !file_exists($picPath)) return false;
	$re = array();
	$re['size'] = filesize($picPath);
	if ($picPath != '' && function_exists('exif_read_data')) {
		$exif = @exif_read_data($picPath, 'EXIF', 1);
		$len = isset($exif['EXIF']['FocalLength']) ? explode('/', $exif['EXIF']['FocalLength']) : '';

		$re['fNumber'] = @$exif['COMPUTED']['ApertureFNumber'];
		$re['Model'] 	= @$exif['IFD0']['Model'];
		$re['speed'] 	= @$exif['EXIF']['ExposureTime'] . ' sec.';
		$re['iso'] 		= @$exif['EXIF']['ISOSpeedRatings'];
		$re['lenAll'] 		= @$exif['EXIF']['FocalLength'];
		$re['len']			= !empty($len) ? $len[0] . 'mm' : '';
		$re['all'] = $re['Model'] . ', ' . $re['speed'] . ', ' . $re['fNumber'] . ', ISO ' . $re['iso'] . ', Len ' . $re['len'];
		if ($len == '' || $re['fNumber'] == '' || $re['Model'] == '') {
			return array('size' => $re['size']);
		}
	}
	return $re;
}

function hashTextDecode($str)
{
	$hash = preg_replace('/_/', '=', $str);
	$hash = preg_replace('/@/', '0', $hash);
	$hash = base64_decode($hash);
	return proper_parse_str($hash);
}

function proper_parse_str($str)
{
	# result array
	$arr = array();

	# split on outer delimiter
	$pairs = explode('&', $str);

	# loop through each pair
	foreach ($pairs as $i) {
		# split into name and value
		list($name, $value) = explode('=', $i, 2);

		# if name already exists
		if (isset($arr[$name])) {
			# stick multiple values into an array
			if (is_array($arr[$name])) {
				$arr[$name][] = $value;
			} else {
				$arr[$name] = array($arr[$name], $value);
			}
		}
		# otherwise, simply stick it in a scalar
		else {
			$arr[$name] = $value;
		}
	}

	# return result array
	return $arr;
}

function OPT_CONF($keys, $name, $is = false, $example = '')
{
	global $isOpens, $tagname, $ex;
	$isOpens[$keys] = $is;
	$tagname[$keys] = $name;
	$ex[$keys] = $example;
}
