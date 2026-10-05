<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Admin Manage ได้', 'redirect', 'SET');
$login_user = (isset($_SESSION['login_user']) && $_SESSION['login_user'] != '') ? $_SESSION['login_user'] : '';
$login_status = (isset($_SESSION['login_status']) && $_SESSION['login_status'] != '') ? $_SESSION['login_status'] : '';

///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array(_AC_, array('saveedit', 'save', 'add', 'edit', 'delete')) && $isSecurMode == 1) {
	setRaiseMsg('Secur Mode.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
	exit;
}
///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////

if (_AC_ == 'delete') {
	PERMIT::_PERMIT(_MODULE_, 'You can add/edit/del admin', 'สามารถ เพิ่ม แก้ไข ลบ Admin ได้', 'redirect', '');
	$id = @$_REQUEST['id'];
	$getAdmin = getAdminUserById($id);
	if ($getAdmin['status'] == 'admin') {
		if (checkCanDeleteAdminOk() == false) {
			setRaiseMsg('Need to have at least 1 Admin for the entire system.', _TIME_, 1);
			CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
			exit;
		}
	}

	DB_DEL('member_user', ['user_id' => $id]);
	Func_Addlogs("[Member] Delete Admin ID {$id} ");
	setRaiseMsg('Data deletion is complete. please wait.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
	exit;
}

$aMember = getAllAdminMember();
$canSignonInit = login_logout::canSignonInit();
$signonOrigin = (int) ($_SESSION['signon_origin_id'] ?? 0);
$inSignon = ($signonOrigin > 0 && (int) login_logout::getAdminId() !== $signonOrigin);

?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">Admin</h1>
	</div>
</div>
<div id="page-content">
	<div class="row">
		<div class="col-sm-12 toolbar-left">
			<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=admin_add"); ?>"><button id="demo-btn-addrow" class="btn btn-purple">Create admin user</button></a>
			<?php if ($inSignon) { ?>
				<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=admin&ac=signon&id=" . $signonOrigin); ?>" class="btn btn-danger"><i class="fa fa-reply"></i> กลับร่าง</a>
			<?php } ?>
		</div>
	</div>
	<div class="row">
		<?php displayRaiseMsg(); ?>
		<?php
		if (count(@$aMember['data']) > 0) {
			$count = 0;
			foreach ($aMember['data'] as $k => $row) {
				$oUserUsername = login_logout::getAdminUsername();
				if ($row['user_id'] == 1 && $oUserUsername !== "superadmin") {
					continue;
				}
				$txtAdminStatus = ($row['status'] == 'admin') ? 'Admin' : 'Operator Staff';
				$count++;
				$aAdmin = getAdminUserById($row['user_id']);
				$aAdmin['salutation'] = isset($aAdmin['salutation']) ? $aAdmin['salutation'] : '';
				$fullname = $aAdmin['salutation'] . ' ' . $aAdmin['firstname'] . '  ' . $aAdmin['lastname'];
				$userPic = Func_Pic_Corp($aAdmin['picture'], TEMPLATE_URL . '/img/profile-photos/1.png', 'w=150&h=150');
				if ($aAdmin['firstname'] == '' && $aAdmin['lastname'] == '') {
					$fullname = $row['username'];
				}
		?>
				<div class="col-sm-4 col-md-4 col-lg-4">
					<div class="panel pos-rel">
						<div class="widget-control text-right">
							<?php if ($row['status'] == 'admin') { ?>
								<a href="#" class="add-tooltip btn btn-trans" data-original-title="Admin"><span class="favorite-color"><i class="demo-psi-star icon-lg"></i></span></a>
							<?php } else { ?>
								<a href="#" class="add-tooltip btn btn-trans" data-original-title="Operator Staff"><span class="unfavorite-color"><i class="demo-psi-star icon-lg"></i></span></a>
							<?php } ?>
							<?php
							$oUser = login_logout::getLoginData();
							if ($oUser->status == 'admin' && $oUser->user_id != $row['user_id'] && $row['user_id'] != '1' && $row['username'] != 'superadmin') {
							?>
								<div class="btn-group dropdown">
									<a href="#" class="dropdown-toggle btn btn-trans" data-toggle="dropdown" aria-expanded="false"><i class="demo-psi-dot-vertical icon-lg"></i></a>
									<ul class="dropdown-menu dropdown-menu-right">
										<li><a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=admin_edit&id=" . $row['user_id']); ?>"><i class="icon-lg icon-fw demo-psi-pen-5"></i> Edit</a></li>
										<li><a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&id=" . $row['user_id'] . "&ac=delete"); ?>" onclick="return confirm('Please confirm the deletion. This moderator.');"><i class="icon-lg icon-fw demo-pli-recycling"></i> Remove</a></li>
									</ul>
								</div>
							<?php } ?>
						</div>
						<div class="pad-all">
							<div class="media pad-ver">
								<div class="media-left">
									<a href="#" class="box-inline"><img alt="Profile Picture" class="img-md img-circle" src="<?php echo $userPic; ?>"></a>
								</div>
								<div class="media-body pad-top">
									<div class="box-inline">
										<span class="text-lg text-semibold text-main"><?php echo $fullname ?></span>
										<div class="text-md"><b>Status : </b> <?php echo $txtAdminStatus; ?></div>
										<div class="text-md"><b>Username : </b> <?php echo $row['username']; ?></div>
										<div class="text-md"><b>E-mail : </b> <?php echo $aAdmin['email']; ?></div>
									</div>
								</div>
							</div>
							<div class="text-center pad-to">
								<div class="btn-group">
									<?php /*?>
					                        	<a href="#" class="btn btn-icon demo-pli-facebook icon-lg add-tooltip" data-original-title="Facebook" data-container="body"></a>
					                        	<a href="#" class="btn btn-icon demo-pli-instagram icon-lg add-tooltip" data-original-title="Instagram" data-container="body"></a>
					                        	<a href="#" class="btn btn-icon demo-pli-consulting icon-lg add-tooltip" data-original-title="Tel:<?php echo $aAdmin['phone']; ?>" data-container="body"></a>
					                        <?php */ ?>
									<a href="mailto:<?php echo $aAdmin['email']; ?>" class="btn btn-icon demo-pli-mail icon-lg add-tooltip" data-original-title="Email" data-container="body"></a>
									<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=admin_edit&id=" . $row['user_id']); ?>" class="btn btn-icon demo-pli-pen-5 icon-lg add-tooltip" data-original-title="Edit" data-container="body"></a>
									<?php if ($canSignonInit && $row['username'] != 'superadmin' && (int) $row['user_id'] !== (int) $oUser->user_id && (int) $row['user_id'] !== 1) { ?>
										<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=admin&ac=signon&id=" . $row['user_id']); ?>" class="btn btn-icon icon-lg add-tooltip" style="color:#6f42c1" data-original-title="สิงร่าง (เข้าใช้งานแทน)" data-container="body"><i class="fa fa-user-secret"></i></a>
									<?php } ?>

								</div>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		<?php } ?>
	</div>
</div>