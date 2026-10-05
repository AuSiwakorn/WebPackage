<?php
include(dirname(__FILE__) . '/../mainApi.php');
include dirname(__FILE__) . 'oApi.php';
@header('Content-Type: text/html; charset=utf-8');

$mode = (isset($_POST['mode']) && $_POST['mode'] != '') ? $_POST['mode'] : '';

switch ($mode) {
    case 'service':
        ###############################################
        ###############################################
        $weburl = $_SERVER['SERVER_NAME'];
        $aFormAllow = array(
            'namehouse' => false,
            'lasthouse' => false,
            'address' => 'No Address.',
            'subject' => 'Contact: ' . $weburl,
            'title' => 'SIRAROM CONTACT',
            'work' => 'No Work.',
            'message' => 'No Message.',
        );

        $subject =  $namehouse = $lasthouse = $address = $work = $message = $title = '';
        foreach ($aFormAllow as $k => $v) {
            if (isset($_POST[$k]) && $_POST[$k] != '') {
                if ($k == 'mailfrom') {
                    if (validateMail($_POST[$k])) {
                        $$k = $_POST[$k];
                    } else {
                        displayError('Error : Email is not match.');
                        return;
                    }
                } else {
                    $$k = $_POST[$k];
                }
            } else {
                if ($v != false) {
                    $$k = $v;
                } else {
                    $msg = 'Error : ' . ucwords($k) . ' is null.';
                    displayError($msg);
                    return;
                }
            }
        }

        $content = "<pre>\n" .
            "ชื่อเจ้าของบ้าน : " . $namehouse . "\n" .
            "นามสกุล : " . $lasthouse . "\n" .
            "บ้านเลขที่ : " . $address . "\n" .
            "โครงการ : " . $work . "\n";

        $content .= "\n-------------------------------------------------------------\n";
        $content .= "รายละเอียด : " . $message;
        $content .= "\n-------------------------------------------------------------\n</pre>";

        //////////////////////////// SMTP ///////////////////////
        $aMail = array();
        $aMail['title'] = $title;
        $aMail['content'] = $content;
        $aMail['emailfrom'] = ($email != '') ? $email : 'noreply@' . DOMAIN_NAME;
        $aMail['emailname'] = $name;
        $aMail['isHtml'] = false;
        $aMailSentto = array(
            'sirarom.project1@gmail.com',
            'chalermkhwan2540@gmail.com',
        );
        foreach ($aMailSentto as $k => $v) {
            $aMail['emailto'] = $v;
            $res = Plugin_sendMail(true, $aMail);
        }

        //////////////////////////// SMTP ///////////////////////
        if ($res['res']) {
            displaySuccess('Contact is successfully sent.');
        } else {
            displayError('Error : Cannot send contact.');
        }
        ###############################################
        ###############################################
        break;

    case 'contact2':
        ###############################################
        ###############################################
        $weburl = $_SERVER['SERVER_NAME'];
        $aFormAllow = array(
            'name' => false,
            'email' => false,
            'lastname' => 'No Lastname.',
            'phone' => 'No Phone.',
            'subject' => 'Contact: ' . $weburl,
            'title' => 'Asterabless [CONTACT & APPOINTMENT]',
            'message' => 'No message.',
            'radiocheck' => 'None',
            'appointment' => 'No Appointment',
        );

        $subject = $appointment = $name = $lastname = $phone = $email = $message = $title = '';
        foreach ($aFormAllow as $k => $v) {
            if (isset($_POST[$k]) && $_POST[$k] != '') {
                if ($k == 'mailfrom') {
                    if (validateMail($_POST[$k])) {
                        $$k = $_POST[$k];
                    } else {
                        displayError('Error : Email is not match.');
                        return;
                    }
                } else {
                    $$k = $_POST[$k];
                }
            } else {
                if ($v != false) {
                    $$k = $v;
                } else {
                    $msg = 'Error : ' . ucwords($k) . ' is null.';
                    displayError($msg);
                    return;
                }
            }
        }

        $content = "<pre>\n" .
            "Date Appointment : " . $appointment . "\n" .
            "First Name : " . $name . "\n" .
            "Last Name : " . $lastname . "\n" .
            "Phone : " . $phone . "\n" .
            "E-mail : " . $email . "\n";

        $content .= "\n-------------------------------------------------------------\n";
        $content .= "Messages/Comment : " . $message;
        $content .= "\n-------------------------------------------------------------\n</pre>";

        //////////////////////////// SMTP ///////////////////////
        $aMail = array();
        $aMail['title'] = $title;
        $aMail['content'] = $content;
        $aMail['emailfrom'] = ($email != '') ? $email : 'noreply@' . DOMAIN_NAME;
        $aMail['emailname'] = $name;
        $aMail['isHtml'] = false;
        $aMailSentto = array(
            'info@aosoft.co.th',
        );
        foreach ($aMailSentto as $k => $v) {
            $aMail['emailto'] = $v;
            $res = Plugin_sendMail(true, $aMail);
        }

        //////////////////////////// SMTP ///////////////////////
        if ($res['res']) {
            displaySuccess('Contact is successfully sent.');
        } else {
            displayError('Error : Cannot send contact.');
        }
        ###############################################
        ###############################################
        break;
    default:
        displayError('Error : Cannot get mode.');
        break;
}

###############################################
//////////////// function /////////////////////
###############################################
function displaySuccess($data)
{
    echo '<font color="#f9d3d3">ขอบคุณที่ส่งเมล์มา ทางเราจะรีบติดต่อกลับ</font>';
}

function displayError($data)
{
    echo '<center><font color="#FF0000">' . $data . '</font></center>';
}

function validateMail($email)
{
    return preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $email);
}
