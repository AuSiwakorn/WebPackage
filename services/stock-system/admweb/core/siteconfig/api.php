<?php
/* ================================ */
////////////// INC PLUGIN ////////////
/* ================================ */
include(PATH_PLUGIN . '/html/api.php');
include(PATH_PLUGIN . '/articles/api.php');
include(PATH_PLUGIN . '/config/api.php');
include(PATH_PLUGIN . '/banner/api.php');
/* ================================ */
////////////// INC PLUGIN ////////////
/* ================================ */
function Systemlog_AddMail($subject, $sendto = 'no subject', $header = 'no header', $content = 'no content')
{
	$db = DB::singleton();
	$regis_date = _TIME_;
	$sql = "
			INSERT INTO " . _DBPREFIX_ . "logs_mail (
				`logs_id` ,
				`subject` ,
				`sendto` ,
				`header` ,
				`content` ,
				`logs_time`
			)
			VALUES (
				NULL ,
				'{$subject}',
				'{$sendto}',
				'{$header}',
				'{$content}',
				'{$regis_date}'
			);
		";
	$ok = $db->query($sql, __FUNCTION__);
	return ($ok) ? $db->getInsertID() : false;
}

function SiteConfig_get($keysname, $defaultdata = '', $type = '')
{
	$a = SiteConfig_getCustomName_db();
	if ($type == 'uploads' || $type == 'upload') {
		return (@$a[$keysname] != '') ? URL_UPLOAD . '/' . $a[$keysname] : $defaultdata;
	} else {
		return (@$a[$keysname] != '') ? $a[$keysname] : $defaultdata;
	}
}

function SiteConfig_getCustomName_db()
{
	global $_aConfigLoad;
	if (is_array($_aConfigLoad)) {
		if (isset($_aConfigLoad) && count($_aConfigLoad) > 0 && is_array($_aConfigLoad)) {
			return $_aConfigLoad;
		} else {
			$_aConfigLoad = PG_config_getCustomName_db();
			return  $_aConfigLoad;
		}
	} else {
		$_aConfigLoad = PG_config_getCustomName_db();
		return  $_aConfigLoad;
	}
}

function SiteConfig_getDefaultMetatags()
{
	global $_aConfigLoadMeta;
	if (count($_aConfigLoadMeta) > 0 && is_array($_aConfigLoadMeta)) {
		return $_aConfigLoadMeta;
	} else {
		$_aConfigLoadMeta = PG_config_getDefaultMetatags();
		return  $_aConfigLoadMeta;
	}
}

function SiteConfig_get_albumfile($keysname)
{
	return PG_config_get_albumfile($keysname);
}

function SiteConfig_viewMetaTags($key = 'default', $title = '', $keysword = '', $des = '', $img = '')
{
	PG_config_viewMetaTags($key, $title, $keysword, $des, $img);
}

function SiteConfig_viewIconTags($defatul = '')
{
	PG_config_viewIconTags($defatul);
	PG_config_viewGoogleAnalytics();
}

function SiteConfig_arrayMergeMeta($a1 = array(), $a2 = array(), $a3 = array())
{
	return PG_config_arrayMergeMeta($a1, $a2, $a3);
}

function SiteConfig_getMetaTagsByLang($key, $lang = 'th')
{
	return PG_config_getMetaTagsByLang($key, $lang);
}

function CMC_add_Contact($first_last, $email, $subject, $message, $urlname)
{
	$date = date('Y-m-d');
	$db = DB::singleton();
	$sql = "INSERT INTO " . _DBPREFIX_ . "contact(
			`id`,
            `first_last`,
			`email`,
            `subject`,
			`message`,
            `add_date`,
            `url`
			) VALUES (
				NULL ,
            '{$first_last}',
            '{$email}',
            '{$subject}',
            '{$message}',
            '{$date}',
            '{$urlname}'
			); ";
	$ok = $db->query($sql, __FUNCTION__);
	return ($ok) ? true : false;
}
