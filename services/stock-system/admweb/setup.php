<?php
header("X-Robots-Tag: noindex, nofollow", true);
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
// header("Content-Security-Policy: default-src 'self'; 
//         script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; 
//         style-src  'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; 
//         font-src   'self' https://fonts.gstatic.com;");
ob_start();
session_start();

///////// config /////////
include dirname(__FILE__) . '/include/conf.ini.php';
include PATH_PLUGIN . '/db/db.php';
include PATH_ADMIN . '/include/function.php';
include PATH_ADMIN . '/include/function_db.php';
include PATH_ADMIN . '/include/fix.req.php';

$is_InstallAdmin = validateInstallAdmin();
$ac				= @$_POST['ac'];
$firstname		= @$_POST['firstname'];
$lastname		= @$_POST['lastname'];
$email			= @$_POST['email'];
$pass			= @$_POST['password'];
$ac = _input_validate_str($ac);
$firstname = _input_validate_str($firstname);
$lastname  = _input_validate_str($lastname);
$email = _input_validate_str($email);
$pass = _input_validate_str($pass);

if ($ac == 'setup') {
	if ($is_InstallAdmin == false && $pass == ADMIN_INSTALL_PASSWORD) {
		if (defined('DATABASE_TYPE') && DATABASE_TYPE == 'mssql') {
			//MSSQL
			$sqlArray = array();
			include('databases/mssql_default.sql.php');
			include('databases/mysql_insert.sql.php');
			$db = DB::singleton();
			foreach ($sqlArray as $stmt) {
				if (strlen($stmt) > 3) {
					$o = $db->query($stmt);
				}
			}
		} else {
			//MYSQL
			$sqlArray = array();
			$pass = Func_encode_builpassword(ADMIN_INSTALL_PASSWORD);
			include('databases/mysql_default.sql.php');
			include('databases/mysql_insert.sql.php');
			$db = DB::singleton();
			foreach ($sqlArray as $stmt) {
				if (strlen($stmt) > 3) {
					$o = $db->query($stmt);
				}
			}
		}

		setRaiseMsg('Setup module is successfully.', _TIME_, 0);
		CustomRedirectToUrl("login.php", true);
		exit;
	} else {
		setRaiseMsg('รหัสผ่านไม่ถูกต้องกรุณาลองใหม่อีกครั้ง.', _TIME_, 1);
		CustomRedirectToUrl("setup.php", true);
		exit;
	}
}
?>

<?php include('template/' . TEMPLATE_NAME . '/setup.php'); ?>
