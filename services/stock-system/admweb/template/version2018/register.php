<?php 
$ac = @$_REQUEST['ac'];
if ($ac == 'register') {
	$_SESSION['register'] = array();
	$d = array();
	$d['namecard'] = @$_POST['namecard'];
	$d['phone'] = @$_POST['phone'];
	$d['salutation'] = @$_POST['salutation'];
	$d['salutation_txt'] = @$_POST['salutation_txt'];
	$d['firstname'] = @$_POST['firstname'];
	$d['lastname'] = @$_POST['lastname'];
	$d['birthday'] = 0;
	$d['email'] = @$_POST['email'];
	$d['username'] = @$_POST['email'];
	$d['password'] = @$_POST['password'];
	
	$d['mem_code'] = @$_POST['member_code'];
	$d['mem_namecard'] = @$_POST['namecard'];
	$d['position_id'] = @$_POST['position_id'];
	
	if ($d['salutation'] == 'other') {
		$d['is_other'] = true;
		if ($d['salutation_txt'] != '') {
			$d['salutation'] = $d['salutation_txt']; 
			$d['salutation'] = $d['salutation_txt'];
		} else {
			$d['salutation_txt'] = '';
		}
	} else {
		$d['is_other'] = false;
	}
	
	$_SESSION['register'] = $d;
	if ($d['salutation'] == '') {
		setRaiseMsg('กรุณาเลือกคำนำหน้าชื่อ.',_TIME_,1);
		CustomRedirectToUrl("register.php", true);
		exit;
	} elseif ($d['salutation'] == 'other' && $d['salutation_txt'] == '') {
		setRaiseMsg('กรุณาระบุคำนำหน้าชื่อ.',_TIME_,1);
		CustomRedirectToUrl("register.php", true);
		exit;
	}
	
	if ($d['firstname'] == '' || $d['lastname'] == '') {
		setRaiseMsg('กรุณากรอก ชื่อ-นามสกุล ของคุณ.',_TIME_,1);
		CustomRedirectToUrl("register.php", true);
		exit;
	}
	
	if (Func_check_namecard($d['mem_namecard']) == false) {
		setRaiseMsg('กรอกรหัสบัตรประชาชนผิด.',_TIME_,1);
		CustomRedirectToUrl("register.php", true);
		exit;
	}
	
	if (Func_checkMemberCodeExist($d['mem_code'])) {
		setRaiseMsg('รหัสพนักงาน นี้มีสมาชิกท่านอื่นใช้งานแล้ว.',_TIME_,1);
		CustomRedirectToUrl("register.php", true);
		exit;
	}
	
	if ($d['position_id'] == '0' || $d[''] == '0') {
		setRaiseMsg('กรุณาเลือกส่วนงาน และ ตำแหน่งงาน.',_TIME_,1);
		CustomRedirectToUrl("register.php", true);
		exit;
	}
	
	if ($d['email'] == '') {
		setRaiseMsg('กรุณากรอกอีเมล์ของคุณ.',_TIME_,1);
		CustomRedirectToUrl("register.php", true);
		exit;
	}
	
	if (Func_checkEmailExist($d['email'])) {
		setRaiseMsg('อีเมล์นี้มีสมาชิกท่านอื่นใช้งานแล้ว.',_TIME_,1);
		CustomRedirectToUrl("register.php", true);
		exit;
	}
	
	if (Func_checkUsernameExist(@$d['username'])) {
		setRaiseMsg('Email นี้มีคนใช้แล้ว.',_TIME_,1);
		CustomRedirectToUrl("register.php", true);
		exit;
	}
	
	if ($d['phone'] == '') {
		setRaiseMsg('กรุณากรอก เบอร์ติดต่อ.',_TIME_,1);
		CustomRedirectToUrl("register.php", true);
		exit;
	}
	
	if ($d['password'] == '') {
		setRaiseMsg('กรุณากรอกรหัสผ่านของคุณ.',_TIME_,1);
		CustomRedirectToUrl("register.php", true);
		exit;
	}
	
	if (strlen($d['password']) < 6) {
		setRaiseMsg('กรุณากรอกรหัสผ่าน 6 ตัวอักษรขึ้นไป.',_TIME_,1);
		CustomRedirectToUrl("register.php", true);
		exit;
	}
	
	$id = Func_insertAdminMember($d['username'], $d['password'], 'member', '', $d);
	if ($id == false) {
		setRaiseMsg('ไม่สามารถเพิ่มสมาชิกได้.',_TIME_,1);
		CustomRedirectToUrl("register.php", true);
		exit;
	} else {
		$_SESSION['register']  = array();
		setRaiseMsg('ทำการลงทะเบียนเรียบร้อยแล้ว. ID ของคุณคือ '.$id,_TIME_,0);
		CustomRedirectToUrl("login.php", true);
		exit;
	}
}

$o = $_SESSION['register'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title>Register</title>
    <!--=================================================-->
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700' rel='stylesheet' type='text/css'>
    <link href="<?php echo TEMPLATE_URL; ?>/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo TEMPLATE_URL; ?>/css/nifty.min.css" rel="stylesheet">
    <link href="<?php echo TEMPLATE_URL; ?>/css/demo/nifty-demo-icons.min.css" rel="stylesheet">
    <!--=================================================-->
    <link href="<?php echo TEMPLATE_URL; ?>/plugins/pace/pace.min.css" rel="stylesheet">
    <script src="<?php echo TEMPLATE_URL; ?>/plugins/pace/pace.min.js"></script>
    <link href="<?php echo TEMPLATE_URL; ?>/css/demo/nifty-demo.min.css" rel="stylesheet">
</head>

<!--TIPS-->
<!--You may remove all ID or Class names which contain "demo-", they are only used for demonstration. -->

<body>
    <div id="container" class="cls-container">
        
		
		<!-- BACKGROUND IMAGE -->
		<!--===================================================-->
		<div id="bg-overlay"></div>
		
		
		<!-- REGISTRATION FORM -->
		<!--===================================================-->
		<div class="cls-content">
		    <div class="cls-content-lg panel">
		        <div class="panel-body">
		            <div class="mar-ver pad-btm">
		                <h1 class="h3">สมัครสมาชิกหน้าระบบอบรม</h1>
		                <p>โปรดกรอกข้อมูลสมาชิกของคุณ เพื่อเข้าใช้งานระบบ โดยระบบจะเก็บประวัติการอบรมของคุณไว้ให้สามารถดูรายละเอียดย้อนหลัง</p>
		            </div>
		            <form action="" method="post">
		            	<input type="hidden" class="form-control" name="ac" value="register">
		                <div class="row">
		                	<div class="col-sm-12"><?php displayRaiseMsg(); ?></div>
		                	<div class="col-sm-6">
		                        <div class="form-group">
		                        	<label><input type="radio" name="salutation" value="นาย"  <?php echo (@$o['salutation'] == 'นาย') ? ' checked="checked"  ' : ''; ?> /> นาย</label> &nbsp;
		                        	<label><input type="radio" name="salutation" value="นาง"  <?php echo (@$o['salutation'] == 'นาง') ? ' checked="checked"  ' : ''; ?>> นาง</label> &nbsp;
		                        	<label><input type="radio" name="salutation" value="นางสาว"  <?php echo (@$o['salutation'] == 'นางสาว') ? ' checked="checked"  ' : ''; ?> /> นางสาว</label> &nbsp;
		                        	<label><input type="radio" name="salutation" value="other"  <?php echo (@$o['is_other'] == true) ? ' checked="checked"  ' : ''; ?> /> อื่นๆ</label>
		                        </div>
		                    </div>
		                    <div class="col-sm-6">
		                        <div class="form-group">
		                        	<input type="text" class="form-control" placeholder="คำนำหน้าชื่อ อื่นๆ" name="salutation_txt" value="<?php echo @$o['salutation_txt']; ?>">
		                        </div>
		                    </div>
		                     <div class="col-sm-6">
		                        <div class="form-group">
		                            <input type="text" class="form-control" placeholder="ชื่อ (ภาษาไทย)" name="firstname" required="required" value="<?php echo @$o['firstname']; ?>">
		                        </div>
		                    </div>
		                     <div class="col-sm-6">
		                        <div class="form-group">
		                            <input type="text" class="form-control" placeholder="นามสกุล (ภาษาไทย)" name="lastname" required="required" value="<?php echo @$o['lastname']; ?>">
		                        </div>
		                    </div>
		                    <div class="col-sm-6">
		                        <div class="form-group">
		                            <input type="text" class="form-control" placeholder="รหัสพนักงาน" name="member_code" required="required" value="<?php echo @$o['mem_code']; ?>">
		                        </div>
		                    </div>
		                    <div class="col-sm-6">
		                        <div class="form-group">
		                            <input type="text" class="form-control" placeholder="เลขที่บัตรประชาชน" name="namecard" required="required" value="<?php echo @$o['namecard']; ?>">
		                        </div>
		                    </div>
		                    
		                    <div class="col-sm-6">
		                        <div class="form-group">
		                            <input type="text" class="form-control" placeholder="E-mail" name="email" value="<?php echo @$o['email']; ?>">
		                        </div>
		                    </div>
		                    <div class="col-sm-6">
		                        <div class="form-group">
		                            <input type="text" class="form-control" placeholder="เบอร์โทรศัพท์" name="phone" value="<?php echo @$o['phone']; ?>">
		                        </div>
		                    </div>
		                    <?php /* ?>
		                    <div class="col-sm-6">
		                        <div class="form-group">
		                            <input type="text" class="form-control" placeholder="Username" name="username" value="<?php echo @$o['username']; ?>">
		                        </div>
		                   </div>
		                   <?php */ ?>
		                    <div class="col-sm-12">
		                        <div class="form-group">
		                            <input type="password" class="form-control" placeholder="Password 6 ตัวอักษรขึ้นไป" name="password">
		                        </div>
		                    </div>
		                </div>
		                <div style="padding-bottom: 10px;">กรุณากรอกข้อมูลให้ครบทุกช่อง</div>
		                <button class="btn btn-primary btn-lg btn-block" type="submit">สมัครสมาชิก</button>
		            </form>
		        </div>
		        <div class="pad-all">
		        	<div>หากคุณมี Account อยู่แล้วโปรดเข้าสู่ระบบ &nbsp; <a href="login.php" class="btn-link mar-rgt text-bold">เข้าสู่ระบบ</a></div>
		        	<div>กรณีที่ลืมรหัสผ่าน โปรดติดต่อผู้ดูแลระบบ</div>
		        </div>
		    </div>
		</div>
    </div>
    <!--===================================================-->
    <script src="<?php echo TEMPLATE_URL; ?>/js/jquery.min.js"></script>
    <script src="<?php echo TEMPLATE_URL; ?>/js/bootstrap.min.js"></script>
    <script src="<?php echo TEMPLATE_URL; ?>/js/nifty.min.js"></script>
</body>
</html>
