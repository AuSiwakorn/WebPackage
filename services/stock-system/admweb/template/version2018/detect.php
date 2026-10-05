<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<title><?php echo ADMIN_TITLE; ?></title>
	<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700' rel='stylesheet' type='text/css'>
	<link href="<?php echo TEMPLATE_URL; ?>/css/bootstrap.min.css?v=2018" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/css/nifty.min.css?v=2018" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/css/demo/nifty-demo-icons.min.css?v=2018" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/plugins/pace/pace.min.css?v=2018" rel="stylesheet">
	<script src="<?php echo TEMPLATE_URL; ?>/plugins/pace/pace.min.js?v=2018"></script>
	<link href="<?php echo TEMPLATE_URL; ?>/css/demo/nifty-demo.min.css?v=2018" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/plugins/font-awesome/css/font-awesome.min.css?v=2018" rel="stylesheet">
</head>

<body>
	<div id="container" class="cls-container">
		<div class="cls-content">
			<div class="cls-content-sm panel">
				<div class="panel-body">
					<div class="mar-ver pad-btm">
						<h1 class="h3">Aosoft</h1>
						<span><?php echo DOMAIN_NAME; ?></span>
					</div>
					<div class="pad-btm mar-btm">
						<img alt="Profile Picture" style="max-height: 100px;" src="<?php echo TEMPLATE_URL . '/img/logoao.png' ?>">
					</div>
					<p>
						<?php $_SESSION['success'] = array();
						echo displayRaiseMsg(); ?>
					</p>
					<div>
						<a href="<?php echo URL_WEB_ROOT; ?>" class="btn-homepage btn btn-block btn-lg btn-primary">Back to Homepage</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script src="<?php echo TEMPLATE_URL; ?>/js/jquery.min.js?v=2018"></script>
	<script src="<?php echo TEMPLATE_URL; ?>/js/bootstrap.min.js?v=2018"></script>
	<script src="<?php echo TEMPLATE_URL; ?>/js/nifty.min.js?v=2018"></script>
</body>

</html>