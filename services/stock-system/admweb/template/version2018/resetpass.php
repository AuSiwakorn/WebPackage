<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Login e-learning | <?php echo DOMAIN_NAME; ?></title>
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700' rel='stylesheet' type='text/css'>
    <link href="<?php echo TEMPLATE_URL; ?>/css/bootstrap.min.css?v=2018" rel="stylesheet">
    <link href="<?php echo TEMPLATE_URL; ?>/css/nifty.min.css?v=2018" rel="stylesheet">
    <link href="<?php echo TEMPLATE_URL; ?>/css/demo/nifty-demo-icons.min.css?v=2018" rel="stylesheet">
    <link href="<?php echo TEMPLATE_URL; ?>/plugins/pace/pace.min.css?v=2018" rel="stylesheet">
    <script src="<?php echo TEMPLATE_URL; ?>/plugins/pace/pace.min.js?v=2018"></script>
    <link href="<?php echo TEMPLATE_URL; ?>/css/demo/nifty-demo.min.css?v=2018" rel="stylesheet">
    
    <link href="<?php echo TEMPLATE_URL; ?>/plugins/font-awesome/css/font-awesome.min.css?v=2018" rel="stylesheet">
    <style>
		.text1 { color: #F68232;font-size: 25px;font-weight: bold; }
		.text2 { color: #44A6F0;font-size: 25px;font-weight: bold; }
		.text3 { color: #fff;font-size: 16px;color: #4d627ba6; }
	</style>
</head>

<body>
    <div id="container" class="cls-container">
		<div class="cls-content">
		    <div class="cls-content-sm panel">
		        <div class="panel-body">
		            <div class="mar-ver pad-btm">
		            	<img src="/images/logo.png">
		                <p style="margin-top: 10px;">โปรดกรอกอีเมล์เพื่อรับรหัสผ่านใหม่อีกครั้ง</p>
		            </div>
		            
		            <form id="form1" name="form1" method="post" action="index.php">
						<input type="hidden" name="sef_log" value="forgotpassword" />
		                <div style="padding:2px;color: #333;" align="left"><?php displayRaiseMsg(); ?></div>
		                <div class="form-group">
		                    <input type="text" name="email" class="form-control" placeholder="Username หรือ E-mail" autofocus value="">
		                </div>
		                <button class="btn btn-mint btn-lg btn-block" type="submit">ส่งรหัสผ่านใหม่ทางอีเมล์</button>
		            </form>
		        </div>
		        <div class="pad-all" style="color: #4d627ba6;">โปรแกรมนี้พัฒนาโดยทีมงาน <a href="http://www.aosoft.co.th" target="_blank" style="color: #4d627ba6;">Aosoft.co.th</a></div>
		    </div>
		</div>
    </div>
    
    <script src="<?php echo TEMPLATE_URL; ?>/js/jquery.min.js?v=2018"></script>
    <script src="<?php echo TEMPLATE_URL; ?>/js/bootstrap.min.js?v=2018"></script>
    <script src="<?php echo TEMPLATE_URL; ?>/js/nifty.min.js?v=2018"></script>
</body>
</html>