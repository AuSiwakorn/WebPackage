<?php
if (_AC_ == 'save') {
	$email_mode      = @$_REQUEST['email_mode'];
	$smtp_sender     = @$_REQUEST['smtp_sender'];
	$smtp_sendername = @$_REQUEST['smtp_sendername'];

	GlobalConfig_update_config_keys('email_mode', $email_mode);
	GlobalConfig_update_config_keys('smtp_sender', $smtp_sender);
	GlobalConfig_update_config_keys('smtp_sendername', $smtp_sendername);

	$isSmtp = ($email_mode == 'smtp') ? 1 : 0;
	GlobalConfig_update_config_keys('isSmtp', $isSmtp);

	$smtp_host   = @$_REQUEST['smtp_host'];
	$smtp_port   = @$_REQUEST['smtp_port'];
	$smtp_user   = @$_REQUEST['smtp_user'];
	$SMTPSecure  = @$_REQUEST['SMTPSecure'];
	$smtp_pass   = trim(@$_REQUEST['smtp_pass']);

	GlobalConfig_update_config_keys('smtp_host', $smtp_host);
	GlobalConfig_update_config_keys('smtp_port', $smtp_port);
	GlobalConfig_update_config_keys('smtp_user', $smtp_user);
	GlobalConfig_update_config_keys('SMTPSecure', $SMTPSecure);

	if ($smtp_pass != '') {
		$smtp_pass_safe = base64_encode(base64_encode($smtp_pass));
		GlobalConfig_update_config_keys('smtp_pass', $smtp_pass_safe);
	}

	$msgraph_client_id     = @$_REQUEST['msgraph_client_id'];
	$msgraph_tenant_id     = @$_REQUEST['msgraph_tenant_id'];
	$msgraph_client_secret = trim(@$_REQUEST['msgraph_client_secret']);

	GlobalConfig_update_config_keys('msgraph_client_id', $msgraph_client_id);
	GlobalConfig_update_config_keys('msgraph_tenant_id', $msgraph_tenant_id);

	if ($msgraph_client_secret != '') {
		$msgraph_secret_safe = base64_encode(base64_encode($msgraph_client_secret));
		GlobalConfig_update_config_keys('msgraph_client_secret', $msgraph_secret_safe);
	}

	$smtpoauth_client_id     = @$_REQUEST['smtpoauth_client_id'];
	$smtpoauth_tenant_id     = @$_REQUEST['smtpoauth_tenant_id'];
	$smtpoauth_client_secret = trim(@$_REQUEST['smtpoauth_client_secret']);

	GlobalConfig_update_config_keys('smtpoauth_client_id', $smtpoauth_client_id);
	GlobalConfig_update_config_keys('smtpoauth_tenant_id', $smtpoauth_tenant_id);

	if ($smtpoauth_client_secret != '') {
		$smtpoauth_secret_safe = base64_encode(base64_encode($smtpoauth_client_secret));
		GlobalConfig_update_config_keys('smtpoauth_client_secret', $smtpoauth_secret_safe);
	}

	$gmail_client_id     = @$_REQUEST['gmail_client_id'];
	$gmail_client_secret = trim(@$_REQUEST['gmail_client_secret']);
	$gmail_refresh_token = trim(@$_REQUEST['gmail_refresh_token']);

	GlobalConfig_update_config_keys('gmail_client_id', $gmail_client_id);

	if ($gmail_client_secret != '') {
		$gmail_secret_safe = base64_encode(base64_encode($gmail_client_secret));
		GlobalConfig_update_config_keys('gmail_client_secret', $gmail_secret_safe);
	}
	if ($gmail_refresh_token != '') {
		$gmail_token_safe = base64_encode(base64_encode($gmail_refresh_token));
		GlobalConfig_update_config_keys('gmail_refresh_token', $gmail_token_safe);
	}

	echo 'บันทึกข้อมูลเรียบร้อยแล้ว';
	exit;
}


if (_AC_ == 'test') {
	if (@$_REQUEST['mailto'] == '') {
		echo '<div class="alert alert-danger">001 กรุณากรอกอีเมล์ผู้รับปลายทาง</div>';
		exit;
	}
	if (@$_REQUEST['title'] == '') {
		echo '<div class="alert alert-danger">002 กรุณากรอก Subject Mail ด้วย</div>';
		exit;
	}
	if (@$_REQUEST['message'] == '') {
		echo '<div class="alert alert-danger">003 กรุณากรอก Message ทดสอบ</div>';
		exit;
	}

	$subject = 'MailTest - ' . $_REQUEST['title'];

	$aMail = array();
	$aMail['title']          = $subject;
	$aMail['content']        = 'Body - ' . nl2br($_REQUEST['message']);
	$aMail['emailfrom']      = GlobalConfig_get('smtp_sendername');
	$aMail['emailname']      = GlobalConfig_get('smtp_sendername');
	$aMail['emailfrom_mail'] = GlobalConfig_get('smtp_sender');
	$aMail['emailto']        = $_REQUEST['mailto'];

	$res = Plugin_sendMail($subject, $aMail, true);

	if (isset($res['res']) && $res['res'] == 'ok') {
		echo '<div class="alert alert-success"><strong>สำเร็จ!</strong> ส่งอีเมลทดสอบเรียบร้อยแล้ว</div>';
	} else {
		echo '<div class="alert alert-danger"><strong>ล้มเหลว!</strong> เกิดข้อผิดพลาดในการส่ง</div>';
	}

	echo '<pre>';
	print_r($res);
	echo '</pre>';
	exit;
}
