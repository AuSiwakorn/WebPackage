<?php
$ac = @$_REQUEST['ac'];
$txt = @$isConnectError;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Please Install Aosoft Admin</title>
	<style type="text/css">
		 .style1 {
			font-size: 16px;
			color: #FFFFFF;
		}

		body,
		td {
			font-size: 12px;
		}

		.box {
			border: 2px #DDD solid;
			-webkit-border-radius: 10px;
			-moz-border-radius: 10px;
			border-radius: 10px;
			padding: 10px;
			margin: 10px;
			font-size: 20px;
			display: block;
			width: 700px;
			background-color: #FEFEDE;
			color: #888;
		} 
	</style>
</head>

<body>
	<p>&nbsp;</p>
	<p>&nbsp;</p>
	<div style="text-align: center;"><img src="https://www.aosoft.co.th/img/logo2.png" alt="WEB ADMIN" /></div>
	<p>&nbsp;</p>
	<div align="center" style="font-size: 30px;color: #AAAAAA;padding: 10px;">Please Install System</div>
	<div align="center" style="font-size: 16px;color: #c5a4a4ff; padding: 5px;">[ <?php echo @$txt; ?> ]</div>
	<?php if (isset($siteName)) { ?>
		<div align="center">
			<div align="center" class="box">
				<?php echo 'fix.' . $siteName . '.php'; ?>
			</div>
		</div>
	<?php } ?>
	<p align="center">This program is developed by AOSOFT CO., LTD. You can contact the company directly at www.aosoft.co.th
	</p>
</body>

</html>
<?php exit; ?>