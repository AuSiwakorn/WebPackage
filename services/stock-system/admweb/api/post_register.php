<?php
include(dirname(__FILE__) . '/../mainApi.php');
include dirname(__FILE__) . 'oApi.php';
@header('Content-Type: text/html; charset=utf-8');
$income = SiteConfig_get('mail_income');
$outgoing = SiteConfig_get('mail_outgoing');

$mode = (isset($_POST['mode']) && $_POST['mode'] != '') ? $_POST['mode'] : '';

switch ($mode) {
    case 'contactmini':
        ###############################################
        ###############################################
        $weburl = $_SERVER['SERVER_NAME'];
        $aFormAllow = array(
            'name' => false,
            'email' => false,
            'lastname' => 'No Lastname.',
            'phone' => 'No Phone.',
            'subject' => 'Contact: ' . $weburl,
            'title' => 'Asterabless [Register]',
            'message' => 'No message.',
            'budget' => 'None',
            'media' => 'None',
            'appointment' => 'No Appointment',
        );

        $subject = $appointment = $name = $lastname = $phone = $email = $message = $title = $budget = $media = '';
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
            "First Name : " . $name . "\n" .
            "Last Name : " . $lastname . "\n" .
            "Phone : " . $phone . "\n" .
            "E-mail : " . $email . "\n" .
            "Budget : " . $budget . "\n"  .
            "Media : " . $media . "\n";

        $content .= "\n-------------------------------------------------------------\n";
        // $content .= "Messages/Comment : " . $message;
        $content .= "\n-------------------------------------------------------------\n</pre>";

        //////////////////////////// SMTP ///////////////////////
        $aMail = array();
        $aMail['title'] = $title;
        $aMail['content'] = $content;
        $aMail['emailfrom'] = ($email != '') ? $email : (($outgoing != '') ? $outgoing : 'noreply@' . DOMAIN_NAME);
        $aMail['emailname'] = $name;
        $aMail['isHtml'] = false;
        $aMailSentto = array(
            $income,
            //       'Vmpc.group@gmail.com',
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

//===========================================//
//////////////// function /////////////////////
//===========================================//
function displaySuccess($data)
{
    echo '<div class="alert alert-success" role="alert">ขอบคุณที่ลงทะเบียน</div>';
}

function displayError($data)
{
    echo '<div class="alert alert-danger" role="alert">' . $data . '</div>';
}

function validateMail($email)
{
    return preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $email);
}
