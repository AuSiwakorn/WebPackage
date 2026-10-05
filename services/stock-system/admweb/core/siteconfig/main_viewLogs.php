<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Admin Login ได้', 'redirect', 'SET');
$abcDel 	= REQ_get('del', 'post', 'array', '');
$page 		= (@$_REQUEST['page'] > 0) ? @$_REQUEST['page'] : 1;
$oUser = login_logout::getAdminUsername();
if ($oUser !== 'superadmin') {
	$alog 		= _systemlogs_getAdminLogs2($num = 20, $page);
	$logCount 		= _systemlogs_getAdminLogsCount2();
} else {
	$alog 		= _systemlogs_getAdminLogs($num = 20, $page);
	$logCount 		= _systemlogs_getAdminLogsCount();
}
$sum = $page > 1 ? $page - 1 : 0;
$count = (count($logCount['data']) + 1) - ($sum * 20);
$currentdate = date('dmY', _TIME_);
$befortime 	= date('dmY', _TIME_ - 86400);
$dellogin = @$_POST['del'];
if (_AC_ == "delete") {
	PERMIT::_PERMIT(_MODULE_, 'module|mp|ac', 'สามารถลบ Admin Login ได้', 'redirect', '');
	foreach ($dellogin as $k => $v) {
		_systemlogs_delete_adminLogs($v);
	}
	setRaiseMsg('Data deletion is complete..', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
	exit;
}

_systemlogs_delete_admin_oldlogs($num = 500);
?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">Administrator access history</h1>
	</div>
</div>
<div id="page-content">

	<div class="row">
		<div class="col-xs-12">
			<div class="panel">
				<div class="panel-body">
					<?php displayRaiseMsg(); ?>
					<form action="" method="post" name="frmList">
						<input type="hidden" name="ac" value="delete">
						<div style="padding-bottom: 10px;"><button id="demo-btn-addrow" class="btn btn-danger" onclick="return ConfirmDelete();">Delete selected data</button></div>
						<table class="table table-striped">
							<thead>
								<tr>
									<th width="5%"><input type="checkbox" onclick="FunCheckedAll(this.checked, 'checkall');"></th>
									<th width="10%">No.</th>
									<th>Username</th>
									<th>IP Address</th>
									<th>Login Time</th>
								</tr>
							</thead>
							<tbody>
								<?php
								if (count(@$alog['data']) > 0 && is_array(@$alog['data'])) {
									foreach ($alog['data'] as $k => $v) {
										$count--; ?>
										<tr>
											<td><input type="checkbox" class="checkall" name="del[]" value="<?php echo $v['logs_id']; ?>"></td>
											<td><?php echo $count; ?></td>
											<td><?php echo $v['username']; ?></td>
											<td><?php echo $v['admin_ip']; ?></td>
											<td>
												<?php
												$checkdate = date('dmY', $v['logs_time']);
												if ($currentdate == $checkdate) {
													echo '<b>Today</b> เวลา ' . strTimeFormat($v['logs_time'], " %H:%M");
												} else {
													echo strTimeFormat($v['logs_time'], "%d %m %Y %H:%M");
												}
												?>
											</td>
										</tr>
									<?php }
								} else { ?>
									<tr height="50">
										<td colspan="6" align="center">There is no member information in the system.</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>

						<div style="padding-top: 10px;" align="right">
							<?php BuilListPage($alog, _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . ''), $page); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>