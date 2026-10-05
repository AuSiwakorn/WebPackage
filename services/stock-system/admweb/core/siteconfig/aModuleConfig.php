<?php
$aTablename = array(
	_DBPREFIX_ . 'site_metatags',
	_DBPREFIX_ . 'site_configs',
	_DBPREFIX_ . 'site_seo_redir',

	/************** Article Group ******************/
	_DBPREFIX_ . 'site_articles',					// article main
	_DBPREFIX_ . 'site_articles_content',
	_DBPREFIX_ . 'site_articles_group',			// group main
	_DBPREFIX_ . 'site_articles_group_content',
	_DBPREFIX_ . 'site_articles_group_img',
	_DBPREFIX_ . 'site_articles_img',
	_DBPREFIX_ . 'site_articles_file',
	_DBPREFIX_ . 'site_articles_keys',
	_DBPREFIX_ . 'site_articles_keysuse',
	_DBPREFIX_ . 'site_articles_subpost',
	_DBPREFIX_ . 'site_articles_subpost_content',

	/************** Tags SEO ***********************/
	_DBPREFIX_ . 'site_tags',

	/************** Banner *************************/
	_DBPREFIX_ . 'banner',

	/************** Search *************************/
	//_DBPREFIX_.'search',
	//_DBPREFIX_.'ip_to_country',

	/************** Logs ****************************/
	_DBPREFIX_ . 'logs_login',
	_DBPREFIX_ . 'logs_mail',

	/************** contact ****************************/
	_DBPREFIX_ . 'contact',
	/************** hash ****************************/
	_DBPREFIX_ . 'hash_log'
);

$aPermission = array(
	PATH_UPLOAD,
	PATH_HELP,
	PATH_UPLOAD_CONFIG,
	PATH_UPLOAD . '/config_file',
	/////////////////////////////////////////////////
	/////////////// HTML Manage /////////////////
	/////////////////////////////////////////////////
	PATH_UPLOAD . '/html',
	/////////////////////////////////////////////////
	/////////////// ARTICLE Path /////////////////
	/////////////////////////////////////////////////
	PATH_UPLOAD . '/articles_icon',
	PATH_UPLOAD . '/articles_attc',
	PATH_UPLOAD . '/articles_pic',
	/////////////////////////////////////////////////
	/////////////// Banner Manage ////////////////
	//////////////////////////////////////////////////
	PATH_UPLOAD . '/banner',
);

$aFunctionReq = array(
	'filesize',
	//	'system',
	'opendir',
	'readdir',
	'move_uploaded_file',
);
