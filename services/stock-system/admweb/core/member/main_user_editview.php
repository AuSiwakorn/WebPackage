<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด User EditView ได้', 'redirect', 'SET');
$redir 	= REQ_get('redir', 'request', 'str', 'user');
$id 	= REQ_get('id', 'request', 'int', '');
//$aYourBalance = Func_Get_Balance($id);

if ($id == '') {
	setRaiseMsg('Error No membership ID is sent. Please try again..', _TIME_, 1);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=user");
	exit;
}

if (!is_dir(PATH_UPLOAD)) {
	mkdir(PATH_UPLOAD, 0777, true);
}

if (!is_dir(PATH_UPLOAD . '/member')) {
	@mkdir(PATH_UPLOAD . '/member', 0777);
}

$aProvince = Arrays_province($lang);
$aMonths = Arrays_months($lang);
$aMember = getUserMemberById($id);
//$aYourBalance = Func_Get_Balance($id);
//$aBank = Array_Bank();

?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">Edit Member User</h1>
	</div>
</div>

<div id="page-content">
	<div class="row">
		<?php displayRaiseMsg(); ?>
		<form action="" class="form-horizontal">
			<div class="col-md-6">
				<div class="row">
					<div class="panel">
						<div class="panel-heading">
							<h3 class="panel-title">User Imformation</h3>
						</div>
						<div class="panel-body">
							<div class="form-group">
								<label class="col-xs-3 control-label text-right"><b>รหัสผู้ใช้</b></label>
								<div class="col-xs-8">
									<label style="margin-right: 3px;" class="control-label"><?php echo $aMember['user_id']; ?></label>
								</div>
							</div>
							<div class="form-group">
								<label class="col-xs-3 control-label text-right"><b> คำนำหน้า </b></label>
								<div class="col-xs-8">
									<label style="margin-right: 3px;" class="control-label"><?php echo $aMember['salutation']; ?></label>
								</div>
							</div>
							<div class="form-group">
								<label class="col-xs-3 control-label text-right"><b> ชื่อ </b></label>
								<div class="col-xs-8 control-label text-left"><?php echo $aMember['firstname']; ?></div>
							</div>
							<div class="form-group">
								<label class="col-xs-3 control-label text-right"><b> นามสกุล </b></label>
								<div class="col-xs-8 control-label text-left"><?php echo $aMember['lastname']; ?></div>
							</div>

							<div class="form-group">
								<label class="col-xs-3 control-label text-right"><b> อีเมลล์</b></label>
								<div class="col-xs-8 control-label text-left"><?php echo $aMember['email']; ?></div>
							</div>
							<div class="form-group">
								<label for="msk-phone" class="col-xs-3 control-label text-right"><b> เบอร์โทรศัพท์</b></label>
								<div class="col-xs-8">
									<label id="msk-phone" class="msk-phone control-label">
										<?php
										if (preg_match('/^(\d{3})(\d{3})(\d{4})$/', $aMember['phone'],  $matches)) {
											echo $matches[1] . '-' . $matches[2] . '-' . $matches[3];
										} else {
											echo ($aMember['phone'] != '') ? $aMember['phone'] : '-- Did not enter the contact number --';
										}
										?></label>
								</div>
							</div>
							<?php
							if ($aMember['picture'] != '') {
								$userPic = Func_Pic_Corp($aMember['picture'], TEMPLATE_URL . '/img/profile-photos/1.png', 'w=150&h=150');
							?>
								<div class="form-group">
									<label class="col-xs-3 control-label text-right"></label>
									<div class="col-xs-8">
										<div><img alt="Profile" src="<?php echo $userPic; ?>?time=<?php echo _TIME_; ?>" class="img-circle img-lg"></div>
									</div>
								</div>
							<?php } ?>
							<div class="form-group">
								<label class="col-xs-3 control-label text-right"><b> Username</b></label>
								<div class="col-xs-8 control-label text-left"><?php echo $aMember['username']; ?></div>
							</div>
							<hr>
							<div class="form-group">
								<label class="col-xs-3 control-label text-right"></label>
								<div class="col-xs-8">
									<a class="btn btn-danger" href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=user_edit&id=" . $aMember['user_id']); ?>">Edit member's profile</a>
								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
		</form>
		<?php
		/*
		$aCash = Func_GetAmountCash($aMember['user_id']);
		$aBalance = Func_Get_Balance($aMember['user_id']);
		$priceAll = 0;
		foreach ($aBalance['data'] as $bp => $vp) {
			if ($vp['balance_status'] == 0) {
				$priceAll = $priceAll+$vp['balance_activated'];
			}
		}*/
		?>
		<div class="col-md-8">
			<div class="row">

			</div>
		</div>
	</div>
</div>