<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Change Password ได้', 'redirect', 'SET');
$oUser = login_logout::getLoginData();
$id = $oUser->user_id;
if ($id == '') {
	setRaiseMsg('Error No membership ID is sent. Please try again.', _TIME_, 1);
	CustomRedirectToUrl("index.php");
	exit;
}
$aMember = getUserMemberById($id);
if (_AC_ == 'add') {
	PERMIT::_PERMIT(_MODULE_, 'module|mp|ac', 'สามารถอัพเดท Change Password ได้', 'redirect', '');
	$upws = REQ_get('upws', 'post', 'str', '');
	$upwscf = REQ_get('upwscf', 'post', 'str', '');
	$d = array();
	$d['password'] = $upws;
	if ($upws != $upwscf) {
		setRaiseMsg('กรุณากรอกรหัสผ่านให้ตรงกัน.', _TIME_, 1);
		CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
		exit;
	}
	if ($d['password'] != '') {
		$pass = htmlspecialchars(trim($d['password']));
		$pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])\S{8,}$/';
		if (!preg_match($pattern, $pass)) {
			setRaiseMsg('Password must have at least 8 character length with minimum 1 uppercase, 1 lowercase, 1 number, 1 special character, and no spaces.', _TIME_, 1);
			CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
			exit;
		}
		updatePassWord($id, $d);
	} else {
		setRaiseMsg('กรุณากรอกรหัสผ่าน.', _TIME_, 1);
		CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
		exit;
	}
	setRaiseMsg('Update information successfully.', _TIME_, 0);
	if ($_SESSION['rejectPwd'] == true) {
		unset($_SESSION['rejectPwd']);
		CustomRedirectToUrl("index.php", true);
		exit;
	}
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
	exit;
}
?>
<style>
	.field-icon {
		float: right;
		margin-right: 10px;
		margin-top: -25px;
		position: relative;
		z-index: 2;
		color: #888;
	}
</style>
<div id="page-head">
	<div id="page-title">
		<div class="row">
			<div class="col-md-4">
				<h1 class="page-header text-overflow">แก้ไขรหัสผ่าน</h1>
			</div>
			<div class="col-md-8 text-right">
				<a href="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=profile'); ?>" class="btn btn-info dbfont18" style="line-height: 16px">ข้อมูลส่วนตัว</a>
			</div>
		</div>
	</div>
</div>
<div id="page-content">
	<div class="row">
		<form action="" method="post" enctype="multipart/form-data" name="frmRegister" id="changepass" autocomplete='off' class="form-horizontal">
			<input type="hidden" name="ac" value="add" />
			<input type="hidden" value="<?php echo $aMember['node_member']; ?>" name="nodemember">
			<div class="col-md-12">
				<div class="panel">
					<div class="panel-body">
						<?php displayRaiseMsg(); ?>
						<div class="form-group">
							<label class="col-sm-3 control-label">USER ID</label>
							<div class="col-sm-8 control-label text-left"><?php echo $aMember['user_id']; ?></div>
						</div>
						<?php if ($aMember['status'] == 'admin') { ?>
							<div class="form-group">
								<label class="col-sm-3 control-label"> Username </label>
								<div class="col-sm-8">
									<input type="text" name="username" value="<?php echo $aMember['username']; ?>" placeholder=" username " class="form-control" readonly="readonly">
								</div>
							</div>
						<?php } else { ?>
							<div class="form-group">
								<label class="col-sm-3 control-label"> Username </label>
								<div class="col-sm-8 control-label text-left"><?php echo $aMember['username']; ?></div>
							</div>
						<?php } ?>
						<div style="<?php if ($aMember['pw'] == _builpassword(PW_RESET)) {
										echo 'border: 1px solid #FF0000;padding: 10px;margin-bottom: 30px;';
									} ?>">
							<div class="form-group">
								<label class="col-sm-3 control-label"> Password </label>
								<div class="col-sm-8">
									<?php if ($aMember['pw'] == _builpassword(PW_RESET)) {
										echo '<span class="text-danger">รหัสผ่านไม่ปลอดภัย กรุณาเปลี่ยนรหัสผ่านของคุณใหม่อีกครั้ง</span>';
									} ?>
									<input type="password" name="upws" id="pwdf" value="" placeholder=" password " class="form-control" maxlength="40" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*_=+-]).{8,30}$" title="Password must have at least 8 character length with minimum 1 uppercase, 1 lowercase, 1 number and 1 special characters." required="required">
									<span toggle="#pwdf" class="fa fa-fw fa-eye field-icon toggle-password" onclick="dpass('toggle-password', 'pwdf');"></span>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-3 control-label"></label>
								<div class="col-sm-8">
									<input type="password" name="upwscf" id="pwdfcf" placeholder=" password confirm " class="form-control" maxlength="40" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*_=+-]).{8,30}$" title="Password must have at least 8 character length with minimum 1 uppercase, 1 lowercase, 1 number and 1 special characters." required="required">
									<span class="fa fa-fw fa-eye field-icon toggle-password2" onclick="dpass('toggle-password2', 'pwdfcf');"></span>
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label"></label>
								<div class="col-sm-8">
									<ul id="cmessage">
										<li id="clangth">จำนวนตัวอักษร 8 ตัวขึ้นไป</li>
										<li id="cletter">ต้องมีตัวพิมพ์เล็กอย่างน้อย 1 ตัว</li>
										<li id="ccapital">ต้องมีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว</li>
										<li id="cspacialchar">ต้องมีอักขระพิเศษอย่างน้อย 1 ตัว</li>
										<li id="cnumber">ต้องมีตัวเลขอย่างน้อย 1 ตัว</li>
										<li id="comfpass">ยืนยันรหัสผ่านตรงกัน</li>
									</ul>
								</div>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-3"></div>
							<div class="col-sm-8">
								<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=profile"); ?>"><button class="btn btn-danger" type="button">Cancel</button></a>
								<button class="btn btn-mint" type="submit">Save & Update</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>