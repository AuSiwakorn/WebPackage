<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Admin Install Website</title>
	<!--Open Sans Font [ OPTIONAL ]-->
	<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700' rel='stylesheet' type='text/css'>
	<link href="<?php echo TEMPLATE_URL; ?>/css/bootstrap.min.css" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/css/nifty.min.css" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/css/demo/nifty-demo-icons.min.css" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/plugins/pace/pace.min.css" rel="stylesheet">
	<script src="<?php echo TEMPLATE_URL; ?>/plugins/pace/pace.min.js"></script>
	<link href="<?php echo TEMPLATE_URL; ?>/css/demo/nifty-demo.min.css" rel="stylesheet">
</head>

<!--TIPS-->
<!--You may remove all ID or Class names which contain "demo-", they are only used for demonstration. -->

<body>
	<div id="container" class="cls-container">
		<!-- <div id="bg-overlay"></div>  -->
		<div class="cls-content">
			<div class="cls-content-lg panel">
				<div class="panel-body">
					<div class="mar-ver pad-btm">
						<h1 class="h3">Install New Website</h1>
						<p>ติดตั้งเว็บไซต์ใหม่ กรณีที่เปิดการใช้งานเว็บไซต์ใหม่ ต้องยืนยันที่จะดำเนินการติดตั้งใหม่</p>
					</div>
					<?php if ($is_InstallAdmin == false) { ?>
						<form id="form1" name="form1" method="post" action="setup.php">
							<input type="hidden" name="ac" value="setup" />
							<div class="row">
								<div class="col-sm-6">
									<div class="form-group">
										<input type="text" class="form-control" placeholder="Firstname" name="firstname">
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<input type="text" class="form-control" placeholder="Lastname" name="lastname">
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-sm-6">
									<div class="form-group">
										<input type="text" class="form-control" placeholder="Email" name="email">
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<input type="password" class="form-control" placeholder="Password" name="password">
									</div>
								</div>
							</div>
							<div class="checkbox pad-btm text-left">
								<input id="demo-form-checkbox" class="magic-checkbox" type="checkbox">
								<label for="demo-form-checkbox">ต้องการลบข้อมูลเก่าทั้งหมดและทำการติดตั้งใหม่?</label>
							</div>
							<div style="padding:2px;color: #ff0000;" align="left"><?php displayRaiseMsg(); ?></div>
							<button class="btn btn-primary btn-lg btn-block" type="submit">INSTALL</button>
						</form>
					<?php } ?>
				</div>
				<div class="pad-all">โปรแกรมนี้พัฒนาโดยทีมงาน <a href="http://www.aosoft.co.th" target="_blank">Aosoft.co.th</a></div>
			</div>
		</div>
	</div>
	<script src="<?php echo TEMPLATE_URL; ?>/js/jquery.min.js"></script>
	<script src="<?php echo TEMPLATE_URL; ?>/js/bootstrap.min.js"></script>
	<script src="<?php echo TEMPLATE_URL; ?>/js/nifty.min.js"></script>
</body>

</html>