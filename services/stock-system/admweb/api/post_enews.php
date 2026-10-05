<?php
include(dirname(__FILE__) . '/../mainApi.php');
include dirname(__FILE__) . 'oApi.php';
@header('Content-Type: text/html; charset=utf-8');

$name = $_POST['username'];
$email = $_POST['email'];
$id = 0;
if ($email != '' && $name != '') {
	if (api_checkEmail($email)) {
		$id = Enews_insert_email_to_db($email, $name, $isSubscribe);
	} else {
		$txt = array("status" => "error", "txt" => "Invalid e-mail");
	}
} else {
	$txt = array("status" => "error", "txt" => "Please complete all information.");
}
if ($id != 0) {
	$txt = array("status" => "OK", "txt" => "Thank you for receiving news");
}

echo json_encode($txt);
