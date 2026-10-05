<?php
#set keys meta tag
$ac 				    = REQ_get('ac', 'request', 'str');
$meta 			    = @$_REQUEST['meta']; //REQ_get('meta', 'request', 'str');
$metakey 		= REQ_get('metakey', 'request', 'str', $module);
$key_meta 		= $metakey;

///////////////////////////////////////////////////////
/////////////////// isSecurMode ////////////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array($ac, array('saveedit', 'save', 'add', 'edit', 'delete')) && $isSecurMode == 1) {
	setRaiseMsg('Secur Mode.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=metas" . $u);
	exit;
}
///////////////////////////////////////////////////////
/////////////////// isSecurMode ////////////////////
///////////////////////////////////////////////////////

if ($ac == 'save') {
	foreach ($meta as $k => $aMetaAdd) {
		updateMetaTags($key_meta, $k, $aMetaAdd);
	}

	$schema_json = REQ_get('schema_json', 'request', 'array');
	foreach ($schema_json as $k => $v) {
		DB_UP('site_metatags', ['schema_json' => $v], ['meta_key' => $key_meta, 'lang' => $k]);
	}

	$aFileUp = REQ_get('metaIcon', 'file');
	foreach ($aFileUp['tmp_name'] as $k => $v) {
		if ($v != '') {
			$fileName = md5($v);

			$aFile = [];
			$aFile['name'] = $aFileUp['name'][$k];
			$aFile['type'] = $aFileUp['type'][$k];
			$aFile['tmp_name'] = $aFileUp['tmp_name'][$k];
			$aFile['error'] = $aFileUp['error'][$k];
			$aFile['size'] = $aFileUp['size'][$k];

			$upFile = Func_uploads_file($aFile, ['jpg', 'png'], 'meta');
			if ($upFile != '') {
				$aDataDel = DB_GET('site_metatags', [
					'meta_key' => $key_meta,
					'lang'     => $k,
				]);
				if ($aDataDel['icon'] != '' && file_exists(PATH_UPLOAD . '/' . $aDataDel['icon'])) {
					unlink(PATH_UPLOAD . '/' . $aDataDel['icon']);
				}

				DB_UP('site_metatags', ['icon' => $upFile], ["meta_key" => $key_meta, 'lang' => $k]);
			}
		}
	}

	AO_Clear_All();
	setRaiseMsg('Save meta tags is successfully.', _TIME_, 0);
	$u = ($metakey != '') ? '&metakey=' . $metakey : '';
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=metas" . $u);
	exit;
}

if ($ac === 'del' && $metakey !== '' && $lang !== '') {

	PG_unlinkMetaIcon($metakey, $lang);

	setRaiseMsg('Delete icon successfully.', _TIME_, 0);
	$u = ($metakey != '') ? '&metakey=' . $metakey : '';
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=metas" . $u);
	exit;
}


$aDefaultMetaTxt 	= getDefaultMetatags();
foreach ($aConfig['language'] as $kLang => $vLang) {
	$aDefaultMeta[$kLang] 	= getMetaTagsByLang('default', $kLang);
	$aMeta[$kLang] 			= getMetaTagsByLang($key_meta, $kLang);
	$aMetaUse[$kLang] = arrayMergeMeta($aDefaultMetaTxt, $aDefaultMeta[$kLang], $aMeta[$kLang]);
}
