<?php
$sqlType = 'php'; //sql, php
$aTablename = array(

	/************** Member *************************/
	_DBPREFIX_ . 'member_user',
	_DBPREFIX_ . 'member_member',
	_DBPREFIX_ . 'member_online',
	_DBPREFIX_ . 'member_admin_login_logs',
	_DBPREFIX_ . 'member_member_login_logs',
	_DBPREFIX_ . 'permission_preset'
);
$aPermission = array(
	PATH_UPLOAD . '/member'
);
