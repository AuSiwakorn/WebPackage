<?php
function GlobalConfig_get($keysword, $valueDefault = "", $type = "")
{
	global $aQData;
	if (isset($_SESSION['tablerecheck']) && count($_SESSION['tablerecheck']) > 1) {
		if (!in_array(_DBPREFIX_ . "site_configs", $_SESSION['tablerecheck'])) {
			return '';
		}
	} else {
		return '';
	}


	$db = DB::singleton();
	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_configs WHERE keywords = '{$keysword}' ;";
	$md5 = md5($sql);
	if (isset($aQData[$md5])) {
		return $aQData[$md5];
	}
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	$aVal = $db->allRows();
	if ($type == "upload" || $type == "uploads") {
		$aQData[$md5] = (@$aVal['val'] != '') ? URL_UPLOAD . '/' . $aVal['val'] : $valueDefault;
	} else {
		$aQData[$md5] = (@$aVal['val'] != '') ? $aVal['val'] : $valueDefault;
	}
	return $aQData[$md5];
}

function _global_config_value_on_keys($keysword)
{
	$db = DB::singleton();
	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_configs WHERE keywords = '{$keysword}' ;";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->allRows();
}

function GlobalConfig_update_config_keys($keysword = '', $val = '')
{
	if ($keysword != '') {
		$aOldConf = _global_config_value_on_keys($keysword);
		if ($aOldConf['conf_id'] != '' && $aOldConf['keywords'] != '') {
			$sql = "
    UPDATE " . _DBPREFIX_ . "site_configs
    SET `val`   = '{$val}'
    WHERE `keywords` = '{$keysword}';
   ";
		} else {
			$sql = "
    INSERT INTO " . _DBPREFIX_ . "site_configs (
     `conf_id` ,
     `keywords` ,
     `val`
    ) VALUES (
     NULL
     , '{$keysword}'
     , '{$val}'
    ); ";
		}

		$db = DB::singleton();
		$db->query($sql);
		return true;
	}
	return false;
}
