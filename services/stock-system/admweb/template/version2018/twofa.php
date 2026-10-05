<?php
$number = rand(1, 23);
$bg = TEMPLATE_URL . '/img/bg-img/' . $number . '.jpg';
$bg = GlobalConfig_get('adminbgweblogin', $bg, 'upload');
?>
<!doctype html>
<html lang="th">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo DOMAIN_NAME; ?></title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		body {
			height: 100vh;
			margin: 0;
		}

		.left-panel {
			background: #fff;
			padding: 40px;
			display: flex;
			flex-direction: column;
			justify-content: center;
		}

		.right-panel {
			position: relative;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #fff;
			padding: 40px;
			overflow: hidden;
			background: url("<?php echo $bg; ?>") no-repeat center center;
			background-size: cover;
		}

		.right-panel::before {
			content: "";
			position: absolute;
			inset: 0;
			background: rgba(32, 144, 145, 0.90);
		}

		.login-box {
			position: relative;
			width: 100%;
			max-width: 380px;
		}

		.alert {
			font-size: 14px;
		}

		.close {
			display: none;
		}
	</style>
</head>

<body>
	<div class="container-fluid h-100">
		<div class="row h-100">

			<div class="col-md-4 text-center left-panel p-4">
				<h2 class="fw-bold">Two-Factor Authentication</h2>
				<p>
					Please enter the Two-Factor Authentication code shown in your Google Authenticator app
				</p>
				<div class="text-center"><img src="template/version2018/img/login.png" style="width: 80%;" /></div>
			</div>

			<div class="col-md-8 right-panel">
				<div class="login-box rounded p-4">
					<div class="text-center p-5">
						<img src="<?php echo GlobalConfig_get('adminiconweb', 'template/version2018/img/logoao2.png', 'upload'); ?>" style="max-height: 100px;" />
					</div>
					<form id="form1" name="form1" method="post" action="index.php" autocomplete="off">
						<input type="hidden" name="sef_log" value="twofa" />
						<input type="hidden" name="sef_secer" value="<?php echo _TIME_; ?>" />
						
						<div style="padding:2px;color: #333;" align="left"><?php displayRaiseMsg(); ?></div>
						<div class="mb-3 text-center">
							<h4 class="text-white">Welcome!, <strong><?php echo login_logout::getLoginData()->user; ?></strong></h4>
							<br>
							<span>Please enter the 2FA code shown in your Authenticator app.</span>
						</div>
						<div class="mb-3">
							<input type="text" name="twofa_code_<?php echo md5(_TIME_); ?>" class="form-control" placeholder="Enter 2FA Code" autofocus value="" autocomplete="off" maxlength="6" pattern="[0-9]*" inputmode="numeric">
						</div>
						<button type="submit" class="btn btn-primary w-100">Verify</button>
					</form>

					<div class="text-center">
						<a href="<?php echo URL_ADMIN . '/logout.php'; ?>"
							class="btn btn-danger w-100 mt-3"
							onclick="return confirm('Are you sure you want to logout?');">
							Logout
						</a>

					</div>
					<br>
					<div class="text-center text-bold small">
						<?php echo "Your IP Address : " . UserInfo::get_ip(); ?><br>
						www.<?php echo DOMAIN_NAME; ?>
					</div>
				</div>
			</div>

		</div>
	</div>
	<script src="<?php echo TEMPLATE_URL; ?>/js/jquery.min.js?v=2018"></script>
	<script src="<?php echo TEMPLATE_URL; ?>/js/bootstrap.min.js?v=2018"></script>
	<script>
		function dpass(sname, inputid) {
			$('.' + sname).toggleClass("fa-eye fa-eye-slash");
			var input = $('#' + inputid);
			if (input.attr("type") == "password") {
				input.attr("type", "text");
			} else {
				input.attr("type", "password");
			}
		}
	</script>
</body>

</html>
<?php exit; ?>