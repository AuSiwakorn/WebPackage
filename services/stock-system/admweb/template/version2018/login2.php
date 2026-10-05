<?php 
$number = rand(1, 23);
$bg = TEMPLATE_URL . '/img/bg-img/' . $number . '.jpg';
$bg = GlobalConfig_get('adminbgweblogin', $bg, 'upload');
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<title><?php echo DOMAIN_NAME; ?></title>
	<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700' rel='stylesheet' type='text/css'>
	<link href="<?php echo TEMPLATE_URL; ?>/css/bootstrap.min.css?v=2018" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/css/nifty.min.css?v=2018" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/css/demo/nifty-demo-icons.min.css?v=2018" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/plugins/pace/pace.min.css?v=2018" rel="stylesheet">
	<script src="<?php echo TEMPLATE_URL; ?>/plugins/pace/pace.min.js?v=2018"></script>
	<link href="<?php echo TEMPLATE_URL; ?>/css/demo/nifty-demo.min.css?v=2018" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/plugins/font-awesome/css/font-awesome.min.css?v=2018" rel="stylesheet">
	<style>
		.text1 {
			color: #F68232;
			font-size: 25px;
			font-weight: bold;
		}

		.text2 {
			color: #44A6F0;
			font-size: 25px;
			font-weight: bold;
		}

		.text3 {
			color: #fff;
			font-size: 16px;
			color: #4d627ba6;
		}

		.field-icon {
			float: right;
			margin-right: 10px;
			margin-top: -25px;
			position: relative;
			z-index: 2;
			color: #888;
		}
	</style>
</head>

<body>
	<div id="container" class="cls-container" style="padding-top: 80px;">

		<div id="bg-overlay" class="bg-img" style="background-image: url('<?php echo $bg; ?>');"></div>
		<div class="cls-content">
			<img src="<?php echo GlobalConfig_get('adminiconweb', 'template/version2018/img/logoao.png', 'upload'); ?>" style="max-height: 100px;" /></a>
			<br><br>
			<div class="cls-content-sm panel">
				<div class="panel-body">
					<div class="mar-ver pad-btm">
						<div style="text-transform: uppercase;">www.<?php echo DOMAIN_NAME; ?></div>
					</div>
					<?php if (@$_COOKIE['login_status'] <= 4) { ?>
						<form id="form1" name="form1" method="post" action="index.php" autocomplete="off">
							<input type="hidden" name="sef_log" value="login" />
							<input type="hidden" name="sef_secer" value="<?php echo _TIME_; ?>" />
							<div style="padding:2px;color: #333;" align="left"><?php displayRaiseMsg(); ?></div>
							<div class="form-group">
								<input type="text" name="uname_<?php echo md5(_TIME_); ?>" class="form-control" placeholder="Username" autofocus value="" autocomplete="off">
							</div>
							<div class="form-group">
								<input type="password" name="password" id="pwdf" class="form-control" placeholder="Password" value="" aria-invalid="false" autocomplete="off">
								<span toggle="#pwdf" class="fa fa-fw fa-eye field-icon toggle-password" onclick="dpass('toggle-password', 'pwdf');"></span>
							</div>
							<button class="btn btn-primary btn-lg btn-block" type="submit">เข้าสู่ระบบ</button>
							<br>
							<div class="form-group">
								<div class="text-bold"><?php echo "Your IP Address : " . UserInfo::get_ip(); ?></div>
							</div>
							<!-- <a class="btn btn-mint btn-lg btn-block" href="register.php">สมัครสมาชิกใหม่</a> -->
						</form>
					<?php } else { ?>
						<div align="center" style="color: #ff0000;padding: 20px;">คุณ Login เกินจำนวนที่กำหนด กรุณาลองใหม่อีกครั้งใน 5 นาทีข้างหน้า</div>
					<?php } ?>
				</div>
				<?php /* ?>
				<div><a href="resetpass.php">ลืมรหัสผ่าน</a></div>
		        <div class="pad-all" style="color: #4d627ba6;">โปรแกรมนี้พัฒนาโดยทีมงาน <a href="http://www.aosoft.co.th" target="_blank" style="color: #4d627ba6;">Aosoft.co.th</a></div>
                <? */ ?>
			</div>
		</div>
	</div>

	<script src="<?php echo TEMPLATE_URL; ?>/js/jquery.min.js?v=2018"></script>
	<script src="<?php echo TEMPLATE_URL; ?>/js/bootstrap.min.js?v=2018"></script>
	<script src="<?php echo TEMPLATE_URL; ?>/js/nifty.min.js?v=2018"></script>
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