<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด My Profile ได้', 'redirect', 'SET');
$oUser = login_logout::getLoginData();
$id = $oUser->user_id;
if ($id == '') {
	setRaiseMsg('Error No membership ID is sent. Please try again..', _TIME_, 1);
	CustomRedirectToUrl("index.php");
	exit;
}
$aMember = getUserMemberById($id);
?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">My Profile</h1>
	</div>
</div>
<?php if ($aMember['status'] == 'admin') { ?>
	<div id="page-content">
		<div class="row">
			<?php displayRaiseMsg(); ?>
			<div class="col-md-7">
				<div class="panel">
					<div class="panel-heading">
						<h3 class="panel-title">Personal Information</h3>
					</div>
					<div class="panel-body">
						<form autocomplete='off' class="form-horizontal">
							<div class="form-group">
								<label class="col-xs-3 control-label">Name Title :</label>
								<div class="col-xs-6">
									<label style="margin-right: 3px;" class="control-label"><?php echo $aMember['salutation']; ?></label>
								</div>
							</div>
							<div class="form-group">
								<label class="col-xs-3 control-label"> First Name :</label>
								<div class="col-xs-6 control-label text-left"><?php echo $aMember['firstname']; ?></div>
							</div>
							<div class="form-group">
								<label class="col-xs-3 control-label"> Last Name :</label>
								<div class="col-xs-6 control-label text-left"><?php echo $aMember['lastname']; ?></div>
							</div>
							<div class="form-group">
								<label class="col-xs-3 control-label"> E-mail :</label>
								<div class="col-xs-6 control-label text-left"><?php echo ($aMember['email'] != '') ? $aMember['email'] : '-'; ?></div>
							</div>
							<div class="form-group">
								<label for="msk-phone" class="col-xs-3 control-label">Mobile Phone :</label>
								<div class="col-xs-6">
									<label class="msk-phone control-label">
										<?php
										if ($aMember['phone'] != '') {
											if (preg_match('/^(\d{3})(\d{3})(\d{4})$/', $aMember['phone'],  $matches)) {
												echo $matches[1] . '-' . $matches[2] . '-' . $matches[3];
											} else {
												echo $aMember['phone'];
											}
										} else {
											echo '-';
										}
										?></label>
								</div>
							</div>
							<div class="form-group">
								<label class="col-xs-3 control-label"> Birthday :</label>
								<?php $date = date('d-m-Y', $aMember['birthday']); ?>
								<div class="col-xs-6 control-label text-left"><?php echo !empty($aMember['birthday']) ? $date : '-'; ?></div>
							</div>
							<?php $userPic = Func_Pic_Corp($aMember['picture'], TEMPLATE_URL . '/img/profile-photos/1.png', 'w=150&h=150'); ?>
							<div class="form-group">
								<label class="col-xs-3 control-label"> Profile image :</label>
								<div class="col-xs-6">
									<div><img alt="Profile" src="<?php echo $userPic . '?t=' . _TIME_; ?>" class="img-circle img-lg"></div>
									<div> &nbsp;</div>
								</div>
							</div>
							<hr>
							<div class="form-group">
								<div class="col-xs-3"></div>
								<div class="col-xs-6">
									<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=editprofile"); ?>" class="btn btn-mint dbfont18" style="line-height: 16px">Edit Profile</a>
									<a href="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=changePass'); ?>" class="btn btn-purple dbfont18" style="line-height: 16px">เปลี่ยนรหัสผ่าน</a>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } else { ?>
	<div id="page-content">
		<div class="row">
			<?php displayRaiseMsg(); ?>
			<div class="col-md-10">
				<div class="panel">
					<div class="panel-heading">
						<h3 class="panel-title">Personal Information</h3>
					</div>
					<div class="panel-body">
						<form autocomplete='off' class="form-horizontal">
							<div class="form-group">
								<label class="col-xs-3 control-label">Name Title :</label>
								<div class="col-xs-6">
									<label style="margin-right: 3px;" class="control-label"><?php echo $aMember['salutation']; ?></label>
								</div>
							</div>
							<div class="form-group">
								<label class="col-xs-3 control-label"> First Name :</label>
								<div class="col-xs-6 control-label text-left"><?php echo $aMember['firstname']; ?></div>
							</div>
							<div class="form-group">
								<label class="col-xs-3 control-label"> Last Name :</label>
								<div class="col-xs-6 control-label text-left"><?php echo $aMember['lastname']; ?></div>
							</div>
							<div class="form-group">
								<label class="col-xs-3 control-label"> E-mail :</label>
								<div class="col-xs-6 control-label text-left"><?php echo ($aMember['email'] != '') ? $aMember['email'] : '-'; ?></div>
							</div>
							<div class="form-group">
								<label for="msk-phone" class="col-xs-3 control-label">Mobile Phone :</label>
								<div class="col-xs-6">
									<label class="msk-phone control-label">
										<?php
										if ($aMember['phone'] != '') {
											if (preg_match('/^(\d{3})(\d{3})(\d{4})$/', $aMember['phone'],  $matches)) {
												echo $matches[1] . '-' . $matches[2] . '-' . $matches[3];
											} else {
												echo $aMember['phone'];
											}
										} else {
											echo '-';
										}
										?></label>
								</div>
							</div>
							<div class="form-group">
								<label class="col-xs-3 control-label"> Birthday :</label>
								<?php $date = date('d-m-Y', $aMember['birthday']); ?>
								<div class="col-xs-6 control-label text-left"><?php echo !empty($aMember['birthday']) ? $date : '-'; ?></div>
							</div>
							<?php $userPic = Func_Pic_Corp($aMember['picture'], TEMPLATE_URL . '/img/profile-photos/1.png', 'w=150&h=150'); ?>
							<div class="form-group">
								<label class="col-xs-3 control-label"> Profile image :</label>
								<div class="col-xs-6">
									<div><img alt="Profile" src="<?php echo $userPic . '?t=' . _TIME_; ?>" class="img-circle img-lg"></div>
									<div> &nbsp;</div>
								</div>
							</div>
							<hr>
							<div class="form-group">
								<div class="col-xs-3"></div>
								<div class="col-xs-6">
									<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=editprofile"); ?>" class="btn btn-mint dbfont20" style="line-height: 16px">แก้ไขข้อมูลส่วนตัว</a>
									<a href="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=changePass'); ?>" class="btn btn-purple dbfont20" style="line-height: 16px">เปลี่ยนรหัสผ่าน</a>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>