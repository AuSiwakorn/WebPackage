<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด User Add ได้', 'redirect', 'SET');
$keysname = REQ_get('keysname', 'request', 'str', '');
$aProvince = Arrays_province($lang);
$aMonths = Arrays_months($lang);
if (!is_dir(PATH_UPLOAD)) {
	mkdir(PATH_UPLOAD, 0777, true);
}

if (!is_dir(PATH_UPLOAD . '/member')) {
	@mkdir(PATH_UPLOAD . '/member', 0777);
}

if (_AC_ == 'add') {
	PERMIT::_PERMIT(_MODULE_, 'You can add/edit/del user', 'สามารถ เพิ่ม แก้ไข ลบ User ได้', 'redirect', '');
	$d = array();
	$d['namecard'] 		= REQ_get('namecard', 'post', 'str', '');
	$phone 		= REQ_get('phone', 'post', 'str', '');
	$phone_sms = REQ_get('phone', 'post', 'str', '');
	$salutation 	= REQ_get('salutation', 'post', 'str', '');
	$firstname 	= REQ_get('firstname', 'post', 'str', '');
	$lastname 		= REQ_get('lastname', 'post', 'str', '');
	$email 		= REQ_get('email', 'post', 'str', '');
	$d['email2'] 		= REQ_get('email2', 'post', 'str', '');
	$d['address'] 		= REQ_get('address', 'post', 'str', '');
	$d['company'] 		= REQ_get('company', 'post', 'str', '');
	$d['line'] 			= REQ_get('line', 'post', 'str', '');
	$address_bill = REQ_get('address_bill', 'post', 'str', '');
	$address_sent = REQ_get('address_sent', 'post', 'str', '');

	$username 		= REQ_get('username', 'post', 'str', '');
	$password 		= REQ_get('password', 'post', 'str', '');
	$d['nodemember'] 	= REQ_get('nodemember', 'post', 'str', '');


	$d['mem_code'] = @$_POST['member_code'];
	if (checkEmailExist(@$_POST['email'])) {
		$isExistsMember = true;
		setRaiseMsg('This email has already been used by other members.', _TIME_, 1);
		CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
		exit;
	}

	if (checkUsernameExist($username)) {
		$isExistsMember = true;
		setRaiseMsg('This username is already used..', _TIME_, 1);
		CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
		exit;
	}

	if ($password == '') {
		setRaiseMsg('Please enter the password..', _TIME_, 1);
		CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
		exit;
	}


	$password = _builpassword($password);
	$a = array();
	//$a['user_id'] = NULL;
	$a['username'] = $username;
	$a['password'] = $password;
	$a['status'] = 'member';
	$a['node_member'] = 0;
	$a['ipaddress'] = getIP();
	$a['register_date'] = _TIME_;
	$a['modules'] = '';

	$id = DB_ADD('member_user', $a);

	$a = array();
	//$a['mem_id'] = NULL;
	$a['user_id'] = 	$id;
	$a['salutation'] = $salutation;
	$a['firstname'] = $firstname;
	$a['lastname'] = $lastname;
	$a['email'] = $email;
	$a['phone'] = $phone;
	$a['mem_code'] = 0;

	$id = DB_ADD('member_member', $a);

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	if ($id == false) {
		setRaiseMsg('Cannot add members.', _TIME_, 1);
		CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
		exit;
	}

	if ($_FILES['pic']['name'] != '') {
		include(PATH_PLUGIN . '/writelicense/resize.php');
		$exname = strtolower(end(explode('.', $_FILES['pic']['name'])));
		if (in_array($exname, array('jpg', 'png', 'gif'))) {
			$picture = 'user/user_' . $id . '_' . _TIME_ . '.' . $exname;
			$picturefile = PATH_UPLOAD . '/' . $picture;
			if (!is_dir(PATH_UPLOAD . '/user')) {
				@mkdir(PATH_UPLOAD . '/user', 0777, true);
			}

			@move_uploaded_file($_FILES['pic']['tmp_name'], $picturefile);
			_updateMemberPicture($id, $picture);
			$thumb = new thumbnail();
			$thumbinc = $thumb->resize_thumbnail($picturefile);
			$thumb->size_width(200);
			$thumb->jpeg_quality(100);
			$thumb->save($picturefile);
		}
	}

	Func_Addlogs("[Member] Add User ID {$id} ");
	setRaiseMsg('Adding the login information successfully.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=user");
	exit;
}

?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">Create Member User</h1>
	</div>
</div>

<div id="page-content">
	<div class="row">
		<div class="col-md-8 col-md-offset-2">
			<div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">Create User Account</h3>
				</div>
				<form action="" method="post" enctype="multipart/form-data" name="frmRegister" id="frmRegister" autocomplete='off' class="form-horizontal">
					<input type="hidden" name="ac" value="add" />
					<div class="panel-body">
						<div class="row">
							<div class="col-sm-12"><?php displayRaiseMsg(); ?></div>
							<div class="col-sm-6">
								<div class="form-group">
									<label><input type="radio" name="salutation" value="นาย" /> Mr.</label> &nbsp;
									<label><input type="radio" name="salutation" value="นาง" /> Mrs.</label> &nbsp;
									<label><input type="radio" name="salutation" value="นางสาว" /> Miss.</label> &nbsp;
									<label><input type="radio" name="salutation" value="other" checked="checked" /> Other</label>
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group" style="padding-right: 5px;">
									<input type="text" class="form-control" placeholder="คำนำหน้า" name="salutation_txt">
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group" style="padding-right: 5px;">
									<input type="text" class="form-control" placeholder="ชื่อ" name="firstname" required="required">
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group" style="padding-right: 5px;">
									<input type="text" class="form-control" placeholder="นามสกุล " name="lastname" required="required">
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group" style="padding-right: 5px;">
									<input type="text" class="form-control" placeholder="เบอร์ติดต่อ" name="phone" required />
								</div>
							</div>

							<div class="col-sm-6">
								<div class="form-group" style="padding-right: 5px;">
									<input type="text" class="form-control" placeholder="เบอร์สำหรับส่ง SMS" name="phone_sms" required />
								</div>
							</div>

							<div class="col-sm-6">
								<div class="form-group" style="padding-right: 5px;">
									<input type="text" class="form-control" placeholder="Line ID" name="line" required />
								</div>
							</div>

							<div class="col-sm-6">
								<div class="form-group" style="padding-right: 5px;">
									<input type="text" class="form-control" placeholder="ชื่อบริษัท" name="company" required="required">
								</div>
							</div>

							<div class="col-sm-12">
								<div class="form-group" style="padding-right: 5px;">
									<input type="text" class="form-control" placeholder="ที่อยู่สำหรับออกใบเสร็จ" name="address_bill" required="required">
								</div>
							</div>
							<div class="col-sm-12">
								<div class="form-group" style="padding-right: 5px;">
									<input type="text" class="form-control" placeholder="ที่อยู่สำหรับส่งเมล์" name="address_sent" required="required">
								</div>
							</div>
							<div class="col-sm-12">
								<div class="form-group" style="padding-right: 5px;">
									<input type="text" class="form-control" placeholder="Taxid" name="taxid" required="required">
								</div>
							</div>

							<div class="col-sm-6">
								<div class="form-group" style="padding-right: 5px;">
									<input type="text" class="form-control" placeholder="E-mail" name="email" required />
								</div>
							</div>

							<div class="col-sm-6">
								<div class="form-group" style="padding-right: 5px;">
									<input type="text" class="form-control" placeholder="E-mailสำหรับส่งเมล์" name="email2" required />
								</div>
							</div>


							<div class="col-sm-6">
								<div class="form-group" style="padding-right: 5px;">
									<input type="text" class="form-control" placeholder="Username" name="username" required />
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group" style="padding-right: 5px;">
									<input type="password" class="form-control" placeholder="Password" name="password" required />
								</div>
							</div>

							<div class="col-sm-12">
								<textarea placeholder="Message" rows="13" class="form-control" placeholder="comment" name="comment_adm"></textarea>
							</div>
						</div><br clear="All">
						<button class="btn btn-primary btn-lg btn-block" type="submit">Create User</button>
					</div>
				</form>

			</div>
		</div>
	</div>
</div>