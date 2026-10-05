<?php
function PG_config_getDefaultMetatags()
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
		'icon' => '/shared.jpg',
	); 
}

function PG_config_get_albumfile($keysname)
{
	$aFiles = array();
	$filePath = PATH_UPLOAD . '/' . $keysname;
	$fileUrl = URL_UPLOAD . '/' . $keysname;
	$aFiles = api_dirList($filePath);
	if (count($aFiles) > 0 && is_array($aFiles)) {
		foreach ($aFiles as $k => $v) {
			$aFiles[$k]['fileurl'] = $fileUrl . '/'.$v['name'];
		}
	}
	return $aFiles;
}

function PG_config_viewMetaTags($key, $defaultTitle='', $keysword='', $des='', $img='') ########## $img = src
{
	global $lang;
	$aDefault = PG_config_getDefaultMetatags();
	$aMetaSite = PG_config_getMetaTagsByLang('default', $lang);
	$aMeta = PG_config_getMetaTagsByLang($key, $lang);
	$schema_json = $aMeta['schema_json'];

	$aMeta = PG_config_arrayMergeMeta($aDefault, $aMetaSite, $aMeta);
	$aMeta['title'] = (@$aMeta['title'] != '') ? $aMeta['title'] : DOMAIN_NAME;
	$aMeta['title'] = ($defaultTitle != '') ? $defaultTitle : $aMeta['title'];
	$aMeta['description'] = ($des != '') ? $des : @$aMeta['description'];
	$aMeta['keywords'] = ($keysword != '') ? $keysword : @$aMeta['keywords'];
	
	$aFBimg = '';
	$aGPimg = '';
	$aTWimg = '';
	if ($img != '') {
		$aFBimg = '<meta property="og:image" content="'.$img.'">'."\n";
		$aGPimg = '<meta itemprop="image" content="'.$img.'">'."\n";
		$aTWimg = '<meta property="twitter:image" content="'.$img.'">'."\n";
	} else {
		$icon = ($aMeta['icon'] != '') ? URL_UPLOAD.'/'.$aMeta['icon'] : '';
		if ($icon != '') {
			$aFBimg = '<meta property="og:image" content="'.$icon.'">'."\n";
			$aGPimg = '<meta itemprop="image" content="'.$icon.'">'."\n";
			$aTWimg = '<meta property="twitter:image" content="'.$icon.'">'."\n";
		}
	}

	unset($aMeta['icon']);
	foreach ($aMeta as $k => $v) {
		if ($v != '') {
			if ($k == 'title'){
				echo "\n".'<title>'.$aMeta['title'].'</title>'."\n";
			} else {
				echo '<meta name="'.$k.'" CONTENT="'.$v.'" />' . "\n";
			}
		}
	}
	
	echo 
	"\n".'<!-- Facebook Propertie -->'."\n".
	'<meta property="og:type" content="website">'."\n".
	'<meta property="og:site_name" content="'.WEBSITE_NAME_SEO.'">'."\n".
	'<meta property="og:title" content="'.$aMeta['title'].'">'."\n".
	'<meta property="og:description" content="'.$aMeta['description'].'">'."\n".
	$aFBimg."\n".
	
	'<!-- Tweet Propertie -->'."\n".
	'<meta property="twitter:title" content="'.$aMeta['title'].'">'."\n".
	'<meta property="twitter:description" content="'.$aMeta['description'].'">'."\n".
	$aTWimg."\n".
	
	'<!-- Google Plus -->'."\n".
	'<meta itemprop="name" content="'.$aMeta['title'].'">'."\n".
	'<meta itemprop="description" content="'.$aMeta['description'].'">'."\n".
	$aGPimg."\n";
	
	if ($schema_json != '') {
		echo '
<script type="application/ld+json">
'.$schema_json.'
</script>
		';
	}

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
	); 

	/*	
<meta http-equiv="expires" content="Wed, 26 Feb 2004 08:21:57 GMT">
<meta http-equiv="Pragma" content="no-cache"> 
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<meta name="Generator" CONTENT="FrontPage 4.0">
<meta name="Language" CONTENT="english">
<meta name="keywords" CONTENT="oranges, lemons, limes">
<meta name="description" CONTENT="Citrus fruit wholesaler.">
<meta name="Designer" CONTENT="Art Vandaley">
<meta name="copyright" content="Copyright 2008"> 

<!-- Facebook Propertie -->
<meta property="og:title" content="ภาควิชาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยธรรมศาสตร์ ตั้งแต่ พุทธศักราช 2529">
<meta property="og:description" content="ภาควิชาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยธรรมศาสตร์ ตั้งแต่ พุทธศักราช 2529">
<meta property="og:image" content="images/logo.png">

<!-- Tweet Propertie -->
<meta property="tw:description" content="ภาควิชาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยธรรมศาสตร์ ตั้งแต่ พุทธศักราช 2529">

<!-- Google Plus -->
<meta itemprop="name" content="ภาควิชาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยธรรมศาสตร์ ตั้งแต่ พุทธศักราช 2529">
<meta itemprop="description" content="ภาควิชาวิทยาการคอมพิวเตอร์ มหาวิทยาลัยธรรมศาสตร์ ตั้งแต่ พุทธศักราช 2529">
<meta itemprop="image" content="images/logo.png">
	*/
}

function PG_config_getCustomName_db()
{
	$db = DB::singleton();
	$sql = "SELECT * FROM "._DBPREFIX_."site_configs ;";
	$db->query($sql, __FUNCTION__);
	$rows = array();
	while ($db->next_record()) {
		$aRow = $db->allRows();
		$rows[$aRow['keywords']] = $aRow['val'];
	}
	return $rows;
}

function PG_config_viewGoogleAnalytics() 
{
	echo SiteConfig_get('GoogleAnalytics');
}

function PG_config_viewIconTags($defatul='template/images/favicon.ico')
{
	$txt = '';
	$aIconMeta = PG_config_getCustomName_db();
	///////// iconshortcut /////////
	if (@$aIconMeta['iconshortcut'] != '') {
		$txt .= '<link rel="shortcut icon" href="'.URL_UPLOAD.'/'.@$aIconMeta['iconshortcut'].'" type="image/x-icon" />'."\n";
	} else {
		$txt .= '<link rel="shortcut icon" href="'.URL_ADMIN.'/'.$defatul.'" type="image/x-icon" />'."\n";
	}
	/////////// iconweb ////////////
	if (@$aIconMeta['iconweb'] != '') {
		$txt .= '<link rel="icon" href="'.URL_UPLOAD.'/'.@$aIconMeta['iconweb'].'" type="image/x-icon" />'."\n";
	} else {
		$txt .= '<link rel="icon" href="'.URL_ADMIN.'/'.$defatul.'" type="image/x-icon" />'."\n";
	}
	//////// apple-touch-icon ////////
	if (@$aIconMeta['apple-touch-icon'] != '') {
		$txt .= '<link rel="apple-touch-icon" href="'.URL_UPLOAD.'/'.@$aIconMeta['apple-touch-icon'].'" />'."\n";
	} else {
		$txt .= '<link rel="apple-touch-icon" href="'.URL_ADMIN.'/'.$defatul.'" />'."\n";
	}
	echo $txt;
}

function PG_config_arrayMergeMeta($a1=array(), $a2=array(), $a3=array())
{
	$aData = array();
	if (is_array($a1) && count($a1) > 0) {
		foreach ($a1 as $k => $v) {
			if (@$a3[$k] == '') {
				$aData[$k] = (@$a2[$k] != '') ? $a2[$k] : $v;
			} else {
				$aData[$k] = $a3[$k];
			}
		}
	}
	return $aData;
}

function PG_config_getMetaTagsByLang($key, $lang='th')
{
	$db = DB::singleton();
	$sql = "SELECT * FROM `"._DBPREFIX_."site_metatags` WHERE meta_key = '{$key}' AND lang = '{$lang}';";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return ($db->num_rows() > 0) ? $db->allRows() : array();
}
