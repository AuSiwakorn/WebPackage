<?php
$siteName = $_SERVER['SERVER_NAME'];
$siteName = preg_replace('/^www\./i', '', $siteName);
define("ADMIN_VERSION", '4.8');

if (is_file(dirname(dirname(__FILE__)) . '/aowebdata/fix.' . $siteName . '.php')) {
	include_once(dirname(dirname(__FILE__)) . '/aowebdata/fix.' . $siteName . '.php');
	try {
		$con = new PDO(
			"mysql:host={$db_host};dbname={$db_name};charset=utf8",
			$db_user,
			$db_passwd,
			[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
		);
		$stmt = $con->query("SHOW TABLES FROM `{$db_name}`");
		$flat = $stmt->fetchAll(PDO::FETCH_COLUMN);
		$_SESSION['tablerecheck'] = $flat;
		$con = null;
	} catch (PDOException $e) {
		$isConnectError = 'Please Check DB USER-PASSWORD';
		include_once(dirname(__FILE__) . '/autoSetup.php');
		echo '<!-- fix.' . $siteName . '.php' . ' -->';
	}
} else {
	$isConnectError = 'File is not Exists';
	include_once(dirname(__FILE__) . '/autoSetup.php');
	echo '<!-- fix.' . $siteName . '.php' . ' -->';
}
