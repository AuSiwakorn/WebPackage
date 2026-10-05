<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Edit Profile ได้', 'redirect', 'SET');
$oUser = login_logout::getLoginData();
$id = $oUser->user_id;
if ($id == '') {
	setRaiseMsg('Error No membership ID is sent. Please try again.', _TIME_, 1);
	CustomRedirectToUrl("index.php");
	exit;
}
if (!is_dir(PATH_UPLOAD)) {
	mkdir(PATH_UPLOAD, 0777, true);
}
if (!is_dir(PATH_UPLOAD . '/member')) {
	@mkdir(PATH_UPLOAD . '/member', 0777);
}
$aMember = getUserMemberById($id);
if (_AC_ == 'edit') {
	PERMIT::_PERMIT(_MODULE_, 'module|mp|ac', 'สามารถแก้ไข Profile ได้', 'redirect', '');
	$d = array();
	$d['username'] = REQ_get('username', 'post', 'str', '');
	$d['mem_namecard'] = REQ_get('mem_namecard', 'post', 'str', '');
	$d['phone'] = str_replace('(', '', REQ_get('phone', 'post', 'str', ''));
	$d['phone'] = str_replace(')', '', @$d['phone']);
	$d['phone'] = str_replace(' ', '', @$d['phone']);
	$d['phone'] = str_replace('-', '', @$d['phone']);
	$d['birthday'] = REQ_get('birthday', 'post', 'str', '');
	if ($d['birthday'] != '') {
		$d['birthday'] = strtotime($d['birthday']);
	}
	$d['salutation'] = REQ_get('salutation', 'post', 'str', '');
	$d['firstname'] = REQ_get('firstname', 'post', 'str', '');
	$d['lastname'] = REQ_get('lastname', 'post', 'str', '');
	$d['email'] = REQ_get('email', 'post', 'str', '');
	$d['mem_code'] = @$aMember['mem_code'];
	if (strlen($d['phone']) > 15) {
		setRaiseMsg('Please enter the correct phone number.', _TIME_, 1);
		CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
		exit;
	}
	updateMemberData2($id, $d);
	$pic = @$_FILES['picture'];
	$extention = end(explode('.', $pic['name']));
	$extention = strtolower($extention);
	if (!empty($pic['name'])) {
		if ($extention == 'jpg' || $extention == 'png') {
			$namepic = 'member/' . $id . '.' . $extention;
			$filePic = PATH_UPLOAD . '/' . $namepic;
			if (is_file($filePic)) {
				unlink($filePic);
			}
			if (move_uploaded_file($pic['tmp_name'], $filePic)) {
				Func_Update_MemberPicture($id, $namepic);
			}
		} else {
			setRaiseMsg('Please insert image .jpg or .png', _TIME_, 1);
			CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
			exit;
		}
	}
	setRaiseMsg('Update information successfully.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=profile");
	exit;
}
?>
<div id="page-head">
	<div id="page-title">
		<div class="row">
			<div class="col-md-4">
				<h1 class="page-header text-overflow">My Profile</h1>
			</div>
			<div class="col-md-8 text-right">
				<a href="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=changePass'); ?>" class="btn btn-purple dbfont18" style="line-height: 16px">เปลี่ยนรหัสผ่าน</a>
			</div>
		</div>
	</div>
</div>
<div id="page-content">
	<div class="row">
		<?php displayRaiseMsg(); ?>
		<form action="" method="post" enctype="multipart/form-data" name="frmRegister" id="saveProfile" autocomplete='off' class="form-horizontal">
			<input type="hidden" name="ac" value="edit" />
			<div class="col-md-10">
				<div class="panel">
					<div class="panel-heading">
						<h3 class="panel-title">Personal Information</h3>
					</div>
					<div class="panel-body">
						<div class="form-group">
							<label class="col-sm-3 control-label">User ID</label>
							<div class="col-sm-8 control-label text-left"><?php echo $aMember['user_id']; ?></div>
						</div>
						<?php if ($aMember['status'] == 'admin') { ?>
							<div class="form-group">
								<label class="col-sm-3 control-label"> Username :</label>
								<div class="col-sm-8">
									<input type="text" name="username" value="<?php echo $aMember['username']; ?>" placeholder=" username " class="form-control" readonly="readonly">
								</div>
							</div>
						<?php } ?>
						<div class="form-group">
							<label class="col-sm-3 control-label"> Name Title :</label>
							<div class="col-sm-8">
								<?php
								$salutations = [
									'นาย' => 'Mr.',
									'นาง' => 'Mrs.',
									'นางสาว' => 'Miss.',
									'other' => 'Other'
								];
								foreach ($salutations as $k => $v) {
									$se = ($aMember['salutation'] == $k) ? 'checked="checked"' : '';
									echo '<label><input type="radio" name="salutation" value="' . $k . '" ' . $se . ' /> ' . $v . '</label> &nbsp;';
								}
								?>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label"><span class="text-danger">*</span> First Name :</label>
							<div class="col-sm-8">
								<input type="text" class="form-control" name="firstname" value="<?php echo $aMember['firstname']; ?>" placeholder="First Name" required="required">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label"><span class="text-danger">*</span> Last Name :</label>
							<div class="col-sm-8">
								<input type="text" class="form-control" name="lastname" value="<?php echo $aMember['lastname']; ?>" placeholder="Last Name" required="required">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label"><span class="text-danger">*</span> E-mail :</label>
							<div class="col-sm-8">
								<input type="email" name="email" value="<?php echo $aMember['email']; ?>" class="form-control" placeholder="E-mail" required="required">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label"><span class="text-danger">*</span> Mobile Phone :</label>
							<div class="col-sm-8">
								<input type="tel" class="form-control msk-phone" name="phone" value="<?php echo $aMember['phone']; ?>" placeholder="Mobile phone" required="required">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label"> Birthday :</label>
							<div class="col-sm-8">
								<?php $date = date('d-m-Y', $aMember['birthday']); ?>
								<input type="text" name="birthday" id="datePicker" value="<?php echo !empty($aMember['birthday']) ? $date : ''; ?>" class="form-control birthday2" placeholder="ยังไม่มีข้อมูล">
								<a href="javascript::" class="text-sm text-danger" onclick="resetBirthday();">ล้างข้อมูลวันเกิด</a>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label"> Profile image :</label>
							<div class="col-sm-8">
								<?php $userPic = Func_Pic_Corp($aMember['picture'], TEMPLATE_URL . '/img/profile-photos/1.png', 'w=150&h=150'); ?>
								<div><img alt="Profile" src="<?php echo $userPic . '?t=' . _TIME_; ?>?time=<?php echo _TIME_; ?>" class="img-circle img-lg"></div>
								<br>
								<span class="pull-left btn btn-dark btn-file dbfont19">
									<i class="demo-pli-upload-to-cloud icon-5x" style="font-size: 18px;"></i> Choose a personal picture... <input type="file" name="picture" onchange="readURL(this);">
								</span>
								<div class="displayselect" style="display: none;"><br clear="all"><br clear="all"><img src="" class="blah" width="150"></div>
							</div>
						</div>
						<hr>
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