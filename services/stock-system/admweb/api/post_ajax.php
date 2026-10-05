<?php
include(dirname(__FILE__) . '/../mainApi.php');
include dirname(__FILE__) . 'oApi.php';
@header('Content-Type: text/html; charset=utf-8');

$mode = (isset($_POST['mode']) && $_POST['mode'] != '') ? $_POST['mode'] : '';
if ($mode == 'contactmini') {
	$name = @$_POST['name'];
	$email = @$_POST['email'];
	$subject = @$_POST['subject'];
	$message = @$_POST['message'];
	$urlname = @$_POST['urlname'];
	if ($subject == '') {
		echo '<strong>Error!</strong> Verification failed.  กรุณากรอกหัวข้อสำหรับการติดต่อ (Subject)';
		exit;
	}

	if ($name == '') {
		echo '<strong>Error!</strong> Verification failed.  กรุณากรอกชื่อผู้ติดต่อ (Name)';
		exit;
	}

	if ($email == '') {
		echo '<strong>Error!</strong> Verification failed. กรุณากรอก E-mail';
		exit;
	}

	if (!validateMail($email)) {
		echo '<strong>Error!</strong> Verification failed. / Email is not match';
		exit;
	}

	if ($message == '') {
		echo '<strong>Error!</strong> Verification failed. กรุณากรอกรายละเอียด (Message)';
		exit;
	}

	$datetime = date('d-m-Y H:i:s');
	$aMail = array();
	$aMail['emailfrom'] = ADMIN_EMAIL;

	$aMailSentto = SiteConfig_get('ContactMailUs');

	$aMail['content'] = "
        Contact Form Website cmcgroupasia.com \n<br>
        =================================\n<br>
        Subject : {$subject}\n<br>
        Name : {$name}\n<br>
        E-Mail : {$email}\n<br>
        Message :\n\n<br><br>
        {$message}
        \n\n<br><br>
        =================================\n<br>
        Time : {$datetime}\n<br>
        ";

	$title = 'WEB CMC :' . $subject;
	$aMailSentto = explode(',', $aMailSentto);
	foreach ($aMailSentto as $k => $v) {
		$aMail['emailto'] = $v;
		$res = Plugin_sendMail($title, $aMail);
	}
	CMC_add_Contact($name, $email, $subject, $message, $urlname);
	echo 'บริษัทได้รับเรื่องการติดต่อเรียบร้อยแล้ว กรุณารอเจ้าหน้าที่ติดต่อกลับ ขอบคุณครับ';
}

function validateMail($email)
{
	return preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $email);
}

exit;
