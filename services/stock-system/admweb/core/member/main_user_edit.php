<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด User Edit ได้', 'redirect', 'SET');
$redir 	= REQ_get('redir', 'request', 'str', 'user');
$id 	= REQ_get('id', 'request', 'str', 'str');
$keysname = REQ_get('keysname', 'request', 'str', '');
if ($id == '') {
	setRaiseMsg('Error No membership ID is sent. Please try again.', _TIME_, 1);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=user");
	exit;
}
if (!is_dir(PATH_UPLOAD)) {
	mkdir(PATH_UPLOAD, 0777, true);
}

if (!is_dir(PATH_UPLOAD . '/member')) {
	@mkdir(PATH_UPLOAD . '/member', 0777);
}

if (_AC_ == 'edit') {
	PERMIT::_PERMIT(_MODULE_, 'You can add/edit/del user', 'สามารถ เพิ่ม แก้ไข ลบ User ได้', 'redirect', '');
	$d = array();
	$d['phone'] = @$_POST['phone'];
	$d['phone'] = str_replace('(', '', @$_POST['phone']);
	$d['phone'] = str_replace(')', '', @$d['phone']);
	$d['phone'] = str_replace(' ', '', @$d['phone']);
	$d['phone'] = str_replace('-', '', @$d['phone']);
	$d['salutation'] = @$_POST['salutation'];
	$d['firstname'] = @$_POST['firstname'];
	$d['lastname'] = @$_POST['lastname'];
	$d['mem_code'] = @$_POST['mem_code'];
	$d['email'] = @$_POST['email'];
	$d['username'] = @$_POST['username'];
	$d['password'] = @$_POST['password'];

	$d['bankname'] = @$_POST['bankname'];
	$d['bankusername'] = @$_POST['bankusername'];
	$d['bankid'] = @$_POST['bankid'];
	$d['nodemember'] = @$_POST['node_member'];
	$ch = (preg_match("/^[^@]*@[^@]*\.[^@]*$/", $d['email'])) ? 1 : 0;
	if ($ch == 0) {
		setRaiseMsg("Email filled out incorrectly.", _TIME_, 0);
		CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&id=" . $id);
		exit;
	}

	updateMemberData($id, $d);

	$pic = @$_FILES['picture'];
	$extention = end(explode('.', $pic['name']));
	$extention = strtolower($extention);
	if ($extention == 'jpg' || $extention == 'png') {
		$namepic = 'member/' . $id . '.' . $extention;
		$filePic = PATH_UPLOAD . '/' . $namepic;
		if (is_file($filePic)) {
			unlink($filePic);
		}


		if (move_uploaded_file($pic['tmp_name'], $filePic)) {
			Func_Update_MemberPicture($id, $namepic);
		}
	}

	Func_Addlogs("[Member] Edit User ID {$id} ");
	setRaiseMsg('Update information successfully.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&id=" . $id);
	exit;
} elseif (_AC_ == 'editBank') {
	PERMIT::_PERMIT(_MODULE_, 'You can add/edit/del user', 'สามารถ เพิ่ม แก้ไข ลบ User ได้', 'redirect', '');
	$d = array();
	$d['bankType'] = 'btc';
	$d['bankName'] = '';
	$d['bankAccount'] = '';
	$d['bankId'] = @$_REQUEST['address_btc'];
	//Func_Add_Update_Bank($id, $d);    //เรียกใช้ตัวเอง

	$d = array();
	$d['bankType'] = 'usd';
	$d['bankName'] = @$_REQUEST['bankName'];
	$d['bankAccount'] = @$_REQUEST['bankAccount'];
	$d['bankId'] = @$_REQUEST['bankId'];
	//Func_Add_Update_Bank($id, $d);    //เรียกใช้ตัวเอง
	setRaiseMsg('Update information successfully.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&id=" . $id);
}

$aProvince = Arrays_province($lang);
$aMonths = Arrays_months($lang);
$aMember = getUserMemberById($id);


?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">Create Member User</h1>
	</div>
</div>

<div id="page-content">
	<div class="row">
		<form action="" method="post" enctype="multipart/form-data" name="frmRegister" id="frmRegister" autocomplete='off' class="form-horizontal">
			<input type="hidden" name="ac" value="edit" />

			<div class="col-md-8">
				<div class="panel">
					<div class="panel-heading">
						<h3 class="panel-title">User Imformation</h3>
					</div>
					<div class="panel-body">
						<div><?php displayRaiseMsg(); ?></div>
						<div class="form-group">
							<label class="col-sm-3 control-label">USER ID</label>
							<div class="col-sm-8 control-label text-left"><?php echo $aMember['user_id']; ?></div>
						</div>
						<!-- <div class="form-group">
                               			<label class="col-sm-3 control-label">Upline ID </label>
                                     	<div class="col-sm-8"><input type="text" name="node_member" value="<?php echo $aMember['node_member']; ?>" placeholder=" Upline ID " class="form-control"></div>
                             		</div> -->
						<hr>
						<div class="form-group">
							<label class="col-sm-3 control-label"> Title Name</label>
							<div class="col-sm-8">
								<label style="margin-right: 3px;"><input name="salutation" type="text" value="<?php echo $aMember['salutation']; ?>" class="form-control"></label>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label"> First name</label>
							<div class="col-sm-8"><input type="text" class="form-control" name="firstname" value="<?php echo $aMember['firstname']; ?>" placeholder="firstname" required></div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label"> Last names</label>
							<div class="col-sm-8"><input type="text" class="form-control" name="lastname" value="<?php echo $aMember['lastname']; ?>" placeholder=lastname></div>
						</div>

						<div class="form-group">
							<label class="col-sm-3 control-label"> E-mail</label>
							<div class="col-sm-8">
								<input type="email" name="email" value="<?php echo $aMember['email']; ?>" class="form-control" placeholder="e-mail " required>
							</div>
						</div>

						<div class="form-group">
							<label for="msk-phone" class="col-sm-3 control-label"> Phone</label>
							<div class="col-sm-8">
								<input type="tel" id="msk-phone" class="form-control msk-phone" name="phone" placeholder="(999) 999-9999" value="<?php echo $aMember['phone']; ?>">
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label"> Change member image</label>
							<div class="col-sm-8">
								<?php
								if ($aMember['picture'] != '') {
									$userPic = Func_Pic_Corp($aMember['picture'], TEMPLATE_URL . '/img/profile-photos/1.png', 'w=150&h=150');
								?>
									<div><img alt="Profile" src="<?php echo $userPic; ?>?time=<?php echo _TIME_; ?>" class="img-circle img-lg"></div>
								<?php } ?>
								<br>
								<span class="pull-left btn btn-dark btn-file dbfont19">
									<i class="demo-pli-upload-to-cloud icon-5x" style="font-size: 18px;"></i> Choose a personal picture... <input type="file" name="picture" value="" onchange="readURL(this);">
								</span>
								<div class="displayselect" style="display: none;"><br clear="all"><br clear="all"><img src="" class="blah" width="150"></div>
							</div>
						</div>
						<hr>
						<div class="form-group">
							<label class="col-sm-3 control-label"> Username </label>
							<div class="col-sm-8">
								<input type="text" name="username" value="<?php echo $aMember['username']; ?>" placeholder=" username " class="form-control" required>
							</div>
						</div>
						<div style="<?php if ($aMember['pw'] == _builpassword(PW_RESET)) {
										echo 'border: 1px solid #FF0000;padding: 10px;margin-bottom: 30px;';
									} ?>">
							<div class="form-group">
								<label class="col-sm-3 control-label"> Password
									<br>
									<a href="javascript::" onclick="AddResetPass();" class="text-danger text-sm" id="AddResetPass">Add Reset Pass</a>
								</label>
								<div class="col-sm-8">
									<?php if ($aMember['pw'] == _builpassword(PW_RESET)) {
										echo '<span class="text-danger">ผู้ใช้งานท่านนี้ ยังไม่เปลี่ยนรหัสผ่าน</span>';
									} ?>
									<input type="text" name="password" value="" placeholder=" password " id="pwsf" class="form-control" maxlength="40">
								</div>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-3"></div>
							<div class="col-sm-8">
								<button class="btn btn-primary" type="submit">Save all edit</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<script>
	function readURL(input) {
		if (input.files && input.files[0]) {
			var reader = new FileReader();

			reader.onload = function(e) {
				$('.blah')
					.attr('src', e.target.result).width(150);
			};

			reader.readAsDataURL(input.files[0]);
		}
		$('.displayselect').fadeIn();
	}
</script>