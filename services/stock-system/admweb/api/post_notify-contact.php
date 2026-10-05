<?php
include(dirname(__FILE__) . '/../mainApi.php');
include dirname(__FILE__) . 'oApi.php';
@header('Content-Type: text/html; charset=utf-8');


$mode = (isset($_POST['mode']) && $_POST['mode'] != '') ? $_POST['mode'] : '';
if ($mode == 'compliantcontactmini') {
    $name = @$_POST['name'];
    $email = @$_POST['email'];
    $subject = @$_POST['subject'];
    $message = @$_POST['message'];

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
    $aMail['emailto'] = SiteConfig_get('mailcomplain');
    $aMail['content'] = "
Contact Form Website cotcometalworks.co.th \n<br>
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

    $title = 'WEB CCM :' . $subject;
    $res = Plugin_sendMail($title, $aMail);
    echo 'ok';
}

function validateMail($email)
{
    return preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $email);
}

exit;
