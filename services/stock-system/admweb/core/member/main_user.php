<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด User List ได้', 'redirect', 'SET');
$delid 		= REQ_get('delid', 'request', 'int', '');
$page 		= REQ_get('page', 'request', 'int', '1');
$id 		= REQ_get('id', 'request', 'int', '');
$keysword 	= REQ_get('keysword', 'request', 'str', '');
if (_AC_ == "del") {
	PERMIT::_PERMIT(_MODULE_, 'You can add/edit/del user', 'สามารถ เพิ่ม แก้ไข ลบ User ได้', 'redirect', '');
	if ($delid != '' && strlen($delid) < 5) {
		DB_DEL('member_user', ['user_id' => $delid]);
		deleteUserMember($delid);
		//Func_Hooks_DeleteMember($delid);
		//Func_Delete_Online_User($delid);
		Func_Addlogs("[Member] Delete User ID {$delid} ");
		setRaiseMsg('Data deletion is complete..', _TIME_, 0);
		CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
		exit;
	}
} elseif (_AC_ == 'search') {
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&keysword=" . $keysword . "&ac=viewsearch");
	exit;
} elseif (_AC_ == 'viewsearch') {
	PERMIT::_PERMIT(_MODULE_, 'module|mp|ac', 'สามารถค้นหา User List ได้', 'redirect', '');
	$aUser = getSearchUserMember($keysword, 30, $page);
	$pagelink = _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&ac=' . _AC_ . '&keysword=' . $keysword);
} else {
	$aUser = getAllUserMember(30, $page);
	$pagelink = _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '');
}

?>
<script language="javascript">
	function ConfirmDelete() {
		if (confirm('Do you really want to delete the information you choose?')) {
			document.frmList.submit();
		}
	}
</script>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">Member</h1>
	</div>
</div>
<div id="page-content">
	<div class="row">
		<div class="row pad-btm">
			<form action="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . ''); ?>" method="post" class="col-xs-12 col-sm-10 col-sm-offset-1 pad-hor">
				<input type="hidden" name="ac" value="search">
				<div class="input-group mar-btm">
					<input type="text" placeholder="Search for members list" class="form-control input-lg" name="keysword" value="<?php echo $keysword; ?>">
					<span class="input-group-btn">
						<button class="btn btn-primary btn-lg input-lg" type="submit" style="margin-left: 5px;">SEARCH</button>
					</span>
				</div>
			</form>
		</div>
	</div>
	<div class="row">
		<div class="text-left">
			<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=user_add"); ?>"><button id="demo-btn-addrow" class="btn btn-purple">Create Member User</button></a>
		</div>
		<hr class="new-section-xs bord-no">
		<?php if (!empty($aUser['data']) && count($aUser['data']) > 0) { ?>
			<div class="panel">
				<div class="panel-body">
					<div class="table-responsive">
						<div class="col-md-12"><?php displayRaiseMsg(); ?></div>
						<table class="table table-striped table-vcenter">
							<thead>
								<tr>
									<th width="50"></th>
									<th width="70">ID</th>
									<th>Username</th>
									<th>Title name</th>
									<th>E-mail</th>
									<th>Phone</th>
									<th>Register date</th>
									<th>Last access</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ($aUser['data'] as $k => $row) {
									//$del = _admin_buil_link("index.php?module=member&mp=user&ac=del&delid=".$row['user_id']);
									$memberlog = _member_getMemberLogsByID($row['user_id']);
									$userPic = Func_Pic_Corp($row['picture'], TEMPLATE_URL . '/img/profile-photos/1.png', 'w=150&h=150');
								?>
									<tr>
										<td><img alt="Profile Picture" class="img-xs img-circle" src="<?php echo $userPic; ?>?time=<?php echo _TIME_; ?>"></td>
										<td><?php echo $row['user_id']; ?></td>
										<td><?php echo $row['username']; ?></td>
										<td>
											<a class="btn-link" href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=user_editview&id=" . $row['user_id']); ?>">
												<?php echo $row['salutation']; ?>
												<?php echo $row['firstname'] . "&nbsp;&nbsp;" . $row['lastname']; ?>
											</a>
										</td>
										<td><?php echo $row['email']; ?></td>
										<td><?php echo $row['phone']; ?></td>
										<td><span class="text-muted"><?php echo strTimeFormat($row['register_date']) ?></span></td>
										<?php $memberLogs_time = !empty($memberlog['logs_time']) ?  $memberlog['logs_time'] : '' ?>
										<td><span class="text-muted"><?php echo strTimeFormat($memberLogs_time) ?></span></td>
										<td class="min-width">
											<div class="btn-groups">
												<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=user_editview&id=" . $row['user_id']); ?>" class="btn btn-icon demo-pli-pen-5 icon-lg add-tooltip" data-original-title="Edit Post" data-container="body"></a>
												<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&ac=del&delid=" . $row['user_id']); ?>" class="btn btn-icon demo-pli-trash icon-lg add-tooltip" data-original-title="Remove" data-container="body" onclick="return ConfirmDelete();"></a>
											</div>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
					<div class="row">
						<div class="col-sm-5">
							<div>Find all <?php echo $aUser['num_rows']; ?> items / display 30 items per page</div>
						</div>
						<div class="col-sm-7 text-right">
							<?php BuilListPage($aUser, $pagelink, $page); ?>
						</div>
					</div>
				</div>
			</div>
		<?php } else { ?>
			<br><br><br>
			<div class="col-md-12"><?php displayRaiseMsg(); ?></div>
			<div class="text-center">
				<p class="h4 text-uppercase text-bold">There are no members according to the details you searched.</p>
				<div class="pad-btm">
					Sorry, but the member you are looking for has not been found on our database.
				</div>
				<div><a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . ""); ?>" class="btn btn-mint dbfont22" data-original-title="Black">Back to the total page</a></div>
			</div>
		<?php } ?>
	</div>
</div>