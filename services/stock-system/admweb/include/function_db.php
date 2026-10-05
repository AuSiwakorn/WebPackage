<?php

function validateInstallAdmin()
{
	$aTablename = array(
		_DBPREFIX_.'member_user',
		_DBPREFIX_.'member_member',
	);

	$aData = array();
	$db = DB::singleton();
	$sql = "SHOW TABLES;";
	$db->query($sql, __FUNCTION__);
	while ($db->next_record()) {
		$a = array_values($db->allRows());
		$aData[] = $a[0];
	}
	
	foreach ($aTablename as $k => $v) {
		if (!in_array($v, $aData)) {
			return false;
		}
	}
	return true;
}

/* //////////////////////// for use all module //////////////////////// */
#set default meta tags and detail tags for use all modules
# return array()
function getDefaultMetatags()
{
	return array(
		'title' 		=> '',
		'description' 	=> '',
		'keywords' 		=> '',
		'robots' 		=> 'index, follow',
		'googlebot' 	=> 'index, follow',
		'contact_addr' 	=> '',
		'copyright' 	=> 'Copyright 2010 By Aosoft co., ltd.',
		'author' 		=> 'Aosoft co., ltd.',
		'revisit-after' => '1 days',
		'icon' => '',
	); 
}

function arrayMergeMeta($a1=array(), $a2=array(), $a3=array())
{
	$aData = array();
	foreach ($a1 as $k => $v) {
		if (@$a3[$k] == '') {
			$aData[$k] = (@$a2[$k] != '') ? $a2[$k] : $v;
		} else {
			$aData[$k] = $a3[$k];
		}
	}
	return $aData;
}

function updateMetaTags($key, $lang=DEFAULT_LANGEUAGE, $aMeta=array())
{
	global $db;
	$key = fixinjection($key);
	$aMeta = (count($aMeta) > 0) ? $aMeta : getDefaultMetatags();
	
	$ch = getMetaTagsByLang($key, $lang);
	if (count($ch) > 0) {
		$sql = "UPDATE "._DBPREFIX_."site_metatags 
				SET `title` 		= '{$aMeta['title']}' ,
					`description` 	= '{$aMeta['description']}' ,
					`keywords` 		= '{$aMeta['keywords']}' ,
					`robots` 		= '{$aMeta['robots']}' ,
					`googlebot` 	= '{$aMeta['googlebot']}' ,
					`contact_addr` 	= '{$aMeta['contact_addr']}' ,
					`copyright` 	= '{$aMeta['copyright']}' ,
					`author` 		= '{$aMeta['author']}' ,
					`revisit-after` = '{$aMeta['revisit-after']}'
				WHERE meta_key ='{$key}'
				AND lang = '{$lang}' ;";
	} else {
		$sql = "INSERT INTO "._DBPREFIX_."site_metatags (
					`meta_id` ,
					`meta_key` ,`title` ,`description` ,`keywords` ,
					`robots` ,`googlebot` ,
					`contact_addr` ,`copyright` ,`author` ,`revisit-after` ,
					`lang`
				) VALUES (
					NULL , '{$key}', '{$aMeta['title']}', '{$aMeta['description']}', '{$aMeta['keywords']}', 
					'{$aMeta['robots']}', '{$aMeta['googlebot']}', 
					'{$aMeta['contact_addr']}', '{$aMeta['copyright']}', '{$aMeta['author']}', '{$aMeta['revisit-after']}', 
					'{$lang}'
				);";
	}
	$db->query($sql, __FUNCTION__);
}

function getMetaTagsByLang($key, $lang=DEFAULT_LANGEUAGE)
{
	global $db;
	$key = fixinjection($key);
	$sql = "SELECT * FROM `"._DBPREFIX_."site_metatags` WHERE meta_key = '{$key}' AND lang = '{$lang}';";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return ($db->num_rows() > 0) ? $db->allRows() : array();
}
