<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once PATH_PLUGIN . '/mail/src/Exception.php';
require_once PATH_PLUGIN . '/mail/src/PHPMailer.php';
require_once PATH_PLUGIN . '/mail/src/SMTP.php';
require_once PATH_PLUGIN . '/mail/src/OAuthTokenProvider.php';

if (!class_exists('MyRawOAuthProvider')) {
    class MyRawOAuthProvider implements \PHPMailer\PHPMailer\OAuthTokenProvider
    {
        private $email;
        private $token;
        public function __construct($email, $token)
        {
            $this->email = $email;
            $this->token = $token;
        }
        public function getOauth64()
        {
            return base64_encode("user=" . $this->email . "\001auth=Bearer " . $this->token . "\001\001");
        }
    }
}

if (!function_exists('Aosoft_HttpRequest')) {
    function Aosoft_HttpRequest($url, $method = 'GET', $postData = null, $headers = [])
    {
        $opts = [
            'http' => [
                'method'        => $method,
                'header'        => implode("\r\n", $headers),
                'content'       => $postData,
                'ignore_errors' => true,
                'timeout'       => 60
            ],
            "ssl" => [
                "verify_peer"      => false,
                "verify_peer_name" => false
            ]
        ];

        $context = stream_context_create($opts);
        $response = @file_get_contents($url, false, $context);

        $httpCode = 0;
        if (isset($http_response_header)) {
            if (preg_match("#^HTTP/[0-9\.]+\s+([0-9]+)#", $http_response_header[0], $out)) {
                $httpCode = (int)$out[1];
            }
        }

        return [
            'response'  => $response,
            'http_code' => $httpCode,
            'error'     => ($response === false) ? error_get_last()['message'] : null
        ];
    }
}

if (!function_exists('base64url_encode_gmail')) {
    function base64url_encode_gmail($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

function Log_Save_Email_History($title, $aMail, $status = '')
{
    try {
        $db = DB::singleton();
        $time_stamp = time();

        $send_to = isset($aMail['emailto']) ? $aMail['emailto'] : '';
        $bcc_list = (isset($aMail['BCC']) && is_array($aMail['BCC'])) ? $aMail['BCC'] : array();

        if (isset($aMail['data_id']) && !empty($aMail['data_id'])) {
            $data_id = "'" . intval($aMail['data_id']) . "'";
        } else {
            $data_id = "NULL";
        }

        $log_data = array(
            'status'   => $status,
            'send_to'  => $send_to,
            'send_bcc' => $bcc_list
        );

        $json_str = json_encode($log_data, JSON_UNESCAPED_UNICODE);

        $safe_content = base64_encode($json_str);
        $safe_title = addslashes($title);
        $safe_sendto = addslashes($send_to);

        $header_info = 'System Mail';

        $sql = "INSERT INTO " . _DBPREFIX_ . "logs_mail (
                `logs_id`,
                `subject`,
                `sendto`,
                `header`,
                `content`,
                `logs_time`
                ) VALUES (
                    NULL,
                '{$safe_title}',
                '{$safe_sendto}',
                '{$header_info}',
                '{$safe_content}', 
                '{$time_stamp}'
                ); ";

        $db->query($sql, __FUNCTION__);
    } catch (\Exception $e) {
        return false;
    }
}

function Plugin_sendMail($title, $aMail, $isShow = false)
{
    $emailMode = GlobalConfig_get('email_mode', 'smtp');

    switch ($emailMode) {
        case 'msgraph':
            return Plugin_sendMail_MSGraph($title, $aMail, $isShow);
        case 'smtp_oauth2': // เพิ่มโหมดใหม่ตรงนี้
            return Plugin_sendMail_SMTP_OAuth2($title, $aMail, $isShow);
        case 'gmail':
            return Plugin_sendMail_Gmail($title, $aMail, $isShow);
        case 'smtp':
        default:
            return Plugin_sendMail_SMTP($title, $aMail, $isShow);
    }
}

function Plugin_sendMail_SMTP_OAuth2($title, $aMail, $isShow = false)
{
    $clientId     = GlobalConfig_get('smtpoauth_client_id');
    $clientSecretEncoded = GlobalConfig_get('smtpoauth_client_secret');
    $clientSecret = base64_decode(base64_decode($clientSecretEncoded));
    $tenantId     = GlobalConfig_get('smtpoauth_tenant_id');
    $sender       = GlobalConfig_get('smtp_sender');
    $sendername   = GlobalConfig_get('smtp_sendername');

    $token_url = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
    $post_fields = http_build_query([
        "client_id"     => $clientId,
        "scope"         => "https://outlook.office365.com/.default",
        "client_secret" => $clientSecret,
        "grant_type"    => "client_credentials"
    ]);

    $req = Aosoft_HttpRequest($token_url, 'POST', $post_fields, ["Content-Type: application/x-www-form-urlencoded"]);
    $token_data = json_decode($req['response'], true);
    $accessToken = isset($token_data['access_token']) ? $token_data['access_token'] : null;

    if (!$accessToken) {
        return array('res' => 'error', 'logs' => 'OAuth Token Failed', 'header' => array('ErrorInfo' => $req['response']));
    }

    $mail = new PHPMailer(false);

    $mail->SMTPDebug = ($isShow == true) ? SMTP::DEBUG_CONNECTION : SMTP::DEBUG_OFF;
    $mail->isSMTP();
    $mail->Host       = 'smtp.office365.com';
    $mail->Port       = 587;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPAuth   = true;
    $mail->AuthType   = 'XOAUTH2';
    $mail->Username   = $sender;

    $mail->setOAuth(new MyRawOAuthProvider($sender, $accessToken));

    $mail->isHTML(true);
    $mail->setFrom($sender, $sendername);
    $mail->addAddress($aMail['emailto']);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = $title;
    $mail->Body    = $aMail['content'];

    if (isset($aMail['BCC']) && is_array($aMail['BCC'])) {
        foreach ($aMail['BCC'] as $v) {
            if (!empty($v)) $mail->addBCC($v);
        }
    }

    if (!$mail->send()) {
        $res = 'error';
        $errorInfo = $mail->ErrorInfo;
    } else {
        $res = 'ok';
        $errorInfo = '';
    }

    Log_Save_Email_History($title, $aMail, $res);

    return array(
        'res' => $res,
        'logs' => base64_encode(json_encode(['token_used' => 'XOAUTH2_V7'])),
        'header' => array('ErrorInfo' => $errorInfo)
    );
}

function Plugin_sendMail_SMTP($title, $aMail, $isShow = false)
{
    date_default_timezone_set('Asia/Bangkok');
    $mail = new PHPMailer(false);

    $isSmtp = GlobalConfig_get('isSmtp', 1);
    $sender     = GlobalConfig_get('smtp_sender');
    $sendername = GlobalConfig_get('smtp_sendername');

    $mail->SMTPDebug = ($isShow == true) ? SMTP::DEBUG_CONNECTION : SMTP::DEBUG_OFF;

    if ($isSmtp == 1) {
        $mail->isSMTP();
        $mail->Host       = GlobalConfig_get('smtp_host');
        $mail->Port       = GlobalConfig_get('smtp_port');
        $mail->Username   = GlobalConfig_get('smtp_user');

        $PasswordEncoded = GlobalConfig_get('smtp_pass');
        $mail->Password  = base64_decode(base64_decode($PasswordEncoded));

        $secureType = GlobalConfig_get('SMTPSecure');
        if ($secureType == 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($secureType == 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail->SMTPAuth   = true;
        $mail->SMTPOptions = array(
            'ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true)
        );
    } else {
        $mail->isMail();
    }

    $mail->isHTML(true);
    $mail->setFrom($sender, $sendername);
    $mail->addAddress($aMail['emailto'], $sendername);
    $mail->addReplyTo($sender, $sendername);
    $mail->CharSet = 'UTF-8';
    $mail->WordWrap = 50;
    $mail->Subject  = $title;
    $mail->Body     =  $aMail['content'];
    $mail->AltBody  =  strip_tags($aMail['content']);

    if (isset($aMail['BCC']) && is_array($aMail['BCC'])) {
        foreach ($aMail['BCC'] as $v) {
            if (!empty($v)) $mail->addBCC($v);
        }
    }

    if (!$mail->send()) {
        $res = 'error';
        $errorInfo = $mail->ErrorInfo;
    } else {
        $res = 'ok';
        $errorInfo = '';
    }

    Log_Save_Email_History($title, $aMail, $res);

    $header = array();
    $header['Subject'] = $mail->Subject;
    $header['Port'] = $mail->Port;
    $header['Host'] = $mail->Host;
    $header['From'] = $sender;
    $header['Username'] = $mail->Username;
    $header['Password'] = '*****';
    $header['To'] = $aMail['emailto'];

    if ($res == 'error') {
        $header['ErrorInfo'] = $errorInfo;
    }

    return array('res' => $res, 'logs' => base64_encode(json_encode(['version' => 'PHPMailer_v7'])), 'header' => $header);
}

function Plugin_sendMail_MSGraph($title, $aMail, $isShow = false)
{
    $clientId     = GlobalConfig_get('msgraph_client_id');
    $clientSecretEncoded = GlobalConfig_get('msgraph_client_secret');
    $clientSecret = base64_decode(base64_decode($clientSecretEncoded));
    $tenantId     = GlobalConfig_get('msgraph_tenant_id');
    $sender       = GlobalConfig_get('smtp_sender');
    $sendername   = GlobalConfig_get('smtp_sendername');

    $token_url = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
    $post_fields = http_build_query([
        "client_id"     => $clientId,
        "scope"         => "https://graph.microsoft.com/.default",
        "client_secret" => $clientSecret,
        "grant_type"    => "client_credentials"
    ]);

    $req = Aosoft_HttpRequest($token_url, 'POST', $post_fields, [
        "Content-Type: application/x-www-form-urlencoded"
    ]);

    $token_data = json_decode($req['response'], true);
    $accessToken = isset($token_data['access_token']) ? $token_data['access_token'] : null;

    if (!$accessToken) {
        $log = base64_encode(json_encode(['error' => 'No Token', 'response' => $req['response']]));
        Log_Save_Email_History($title, $aMail, 'error');
        return array('res' => 'error', 'logs' => $log, 'header' => array('ErrorInfo' => $req['response']));
    }

    $body = array(
        "message" => array(
            "subject" => $title,
            "body"    => ["contentType" => "HTML", "content" => $aMail['content']],
            "toRecipients" => [
                ["emailAddress" => ["address" => $aMail['emailto']]]
            ]
        ),
        "saveToSentItems" => true
    );

    if (isset($aMail['BCC']) && is_array($aMail['BCC']) && !empty($aMail['BCC'])) {
        $body['message']['bccRecipients'] = [];
        foreach ($aMail['BCC'] as $bcc) {
            if ($bcc) $body['message']['bccRecipients'][] = ["emailAddress" => ["address" => $bcc]];
        }
    }

    $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE);
    $sendMailUrl = "https://graph.microsoft.com/v1.0/users/{$sender}/sendMail";

    $reqSend = Aosoft_HttpRequest($sendMailUrl, 'POST', $jsonBody, [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: application/json"
    ]);

    $httpcode = $reqSend['http_code'];
    $res = ($httpcode == 202 || $httpcode == 200) ? 'ok' : 'error';

    // บันทึกประวัติ
    Log_Save_Email_History($title, $aMail, $res);

    $log = base64_encode(json_encode(['http_code' => $httpcode, 'response' => $reqSend['response']]));
    $header = array(
        'Subject' => $title,
        'Host' => 'graph.microsoft.com',
        'From' => $sender,
        'To' => $aMail['emailto']
    );

    if ($res == 'error') {
        $header['ErrorInfo'] = $reqSend['response'];
    }
    return array('res' => $res, 'logs' => $log, 'header' => $header);
}

// =================================================================================
// โหมด 3: Gmail API
// =================================================================================
function Plugin_sendMail_Gmail($title, $aMail, $isShow = false)
{
    $clientId      = GlobalConfig_get('gmail_client_id');
    $clientSecretEncoded = GlobalConfig_get('gmail_client_secret');
    $clientSecret = base64_decode(base64_decode($clientSecretEncoded));
    $refreshTokenEncoded = GlobalConfig_get('gmail_refresh_token');
    $refreshToken = base64_decode(base64_decode($refreshTokenEncoded));
    $sender        = GlobalConfig_get('smtp_sender');
    $sendername    = GlobalConfig_get('smtp_sendername');
    $toEmail       = $aMail['emailto'];

    // 1. Get Access Token
    $token_url = 'https://oauth2.googleapis.com/token';
    $post_fields = http_build_query([
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type'    => 'refresh_token'
    ]);

    $req = Aosoft_HttpRequest($token_url, 'POST', $post_fields, [
        "Content-Type: application/x-www-form-urlencoded"
    ]);

    $token_data = json_decode($req['response'], true);
    $accessToken = isset($token_data['access_token']) ? $token_data['access_token'] : null;

    if (!$accessToken) {
        $log = base64_encode(json_encode(['error' => 'No Token', 'response' => $req['response']]));
        Log_Save_Email_History($title, $aMail, 'error');
        return array('res' => 'error', 'logs' => $log, 'header' => array('ErrorInfo' => $req['response']));
    }

    // 2. Prepare Raw Message
    $headers = [
        "From: {$sendername} <{$sender}>",
        "To: <{$toEmail}>",
        "Subject: =?UTF-8?B?" . base64_encode($title) . "?=",
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8"
    ];

    if (isset($aMail['BCC']) && is_array($aMail['BCC']) && !empty($aMail['BCC'])) {
        $bccList = [];
        foreach ($aMail['BCC'] as $b) {
            if ($b) $bccList[] = "<$b>";
        }
        if ($bccList) $headers[] = "Bcc: " . implode(', ', $bccList);
    }

    $rawMessageString = implode("\r\n", $headers) . "\r\n\r\n" . $aMail['content'];
    $raw = base64url_encode_gmail($rawMessageString);
    $sendBody = json_encode(['raw' => $raw]);

    // 3. Send
    $sendUrl = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';

    $reqSend = Aosoft_HttpRequest($sendUrl, 'POST', $sendBody, [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: application/json"
    ]);

    $httpcode = $reqSend['http_code'];
    $res = ($httpcode == 200) ? 'ok' : 'error';

    // บันทึกประวัติ
    Log_Save_Email_History($title, $aMail, $res);

    $log = base64_encode(json_encode(['http_code' => $httpcode, 'response' => $reqSend['response']]));
    $header = array(
        'Subject' => $title,
        'Host' => 'gmail.googleapis.com',
        'From' => $sender,
        'To' => $toEmail
    );

    if ($res == 'error') {
        $header['ErrorInfo'] = $reqSend['response'];
    }
    return array('res' => $res, 'logs' => $log, 'header' => $header);
}
