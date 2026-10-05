<?php
function _update_config_keys($keysword = '', $val = '')
{
	if ($keysword != '') {
		$aOldConf = _get_config_value_on_keys($keysword);
		if ($aOldConf['conf_id'] != '' && $aOldConf['keywords'] != '') {
			$sql = "
				UPDATE " . _DBPREFIX_ . "site_configs
				SET `val` 		= '{$val}'
				WHERE keywords = '{$keysword}';
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
		$db->query($sql, __FUNCTION__);
		return true;
	}
	return false;
}

function _get_config_all()
{
	$db = DB::singleton();
	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_configs ;";
	$db->query($sql, __FUNCTION__);
	$rows = array();
	while ($db->next_record()) {
		$aRow = $db->allRows();
		$rows[$aRow['keywords']] = $aRow['val'];
	}
	return $rows;
}

function _get_config_value_on_keys($keysword)
{
	$db = DB::singleton();
	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_configs WHERE keywords = '{$keysword}' ;";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->allRows();
}

function _set_txt_comment($txt = '', $classname = 'cr')
{
	return (@$txt != '') ? '<div style="padding:3px;"><span class="' . $classname . '">' . $txt . '</span></div>' : '';
}

function _switch_input($aDefaultData, $keysname = '', $customvalue = '')
{
	$module = @$_REQUEST['module'];
	$mp = @$_REQUEST['mp'];
	$keysurl = @$_REQUEST['keysname'];
	$customtags = @$aDefaultData['customtags'];
	switch (@$aDefaultData['type']) {
		case 'text':
			echo _set_txt_comment(@$aDefaultData['des'], 'cg');
			echo _set_txt_comment(@$aDefaultData['notics'], 'cr');
			echo '
					';
			echo '<div style="padding:3px;"><input type="text" class="input_config_g" name="keys[' . $keysname . ']" ' . $customtags . ' value="' . $customvalue . '" /></div>';
			break;
		case 'checkbox':
			echo _set_txt_comment($aDefaultData['notics'], 'cr');
			echo '<div style="padding:3px;" align="left"><input type="checkbox" name="keys[' . $keysname . ']" value="1" ' . (($customvalue == 1) ? ' checked="checked" ' : '') . ' /> ' . $aDefaultData['des'] . ' </div>';
			break;
		case 'textarea':
			echo _set_txt_comment($aDefaultData['des'], 'cg');
			echo _set_txt_comment($aDefaultData['notics'], 'cr');
			echo '<div style="padding:3px;"><textarea rows="3" name="keys[' . $keysname . ']" class="input_config_g" ' . $customtags . ' >' . $customvalue . '</textarea></div>';
			break;
		case 'file':
			$txtdeletefile = ($customvalue != '') ? ' [ <a href="' . _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&ac=deletefile&keysname=' . $keysurl . '&namekeys=' . $keysname) . '" class="notload" onclick="return confirm(\'Delete ?\');">DELETE</a> ]' : '';
			$txt = ($customvalue != '') ? '<a href="' . URL_UPLOAD . '/' . $customvalue . '" target="_blank" class="notload">' . $customvalue . '</a> &nbsp; &nbsp; &nbsp; ' . $txtdeletefile : '';
			echo _set_txt_comment($aDefaultData['des'], 'cg');
			echo _set_txt_comment($aDefaultData['notics'], 'cr');
			echo '<div style="padding:3px;"><input type="file" name="' . $keysname . '" class="input_config_g" ' . $customtags . ' /></div>';
			echo _set_txt_comment($txt, 'cg');
			break;
		case 'link':
			echo _set_txt_comment(@$aDefaultData['des'], 'cg');
			echo _set_txt_comment(@$aDefaultData['notics'], 'cr');
			echo '<a href="' . @$aDefaultData['url'] . '" ' . $customtags . ' class="button">' . @$aDefaultData['linkname'] . '</a>';
			break;
		case 'detail':
			echo _set_txt_comment(@$aDefaultData['des'], 'cg');
			echo _set_txt_comment(@$aDefaultData['notics'], 'cr');
			break;
	}
}
