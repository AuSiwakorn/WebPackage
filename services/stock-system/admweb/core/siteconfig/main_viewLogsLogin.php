<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Ban User ID ได้', 'redirect', 'SET');
$abcDel = REQ_get('del', 'post', 'array', '');
$page 	= (@$_REQUEST['page'] > 0) ? @$_REQUEST['page'] : 1;
$alog 	= _systemlogs_getLoginLogs($num = 20, $page);
$currentdate = date('dmY', _TIME_);
$befortime = date('dmY', _TIME_ - 86400);
if (_AC_ == 'delete') {
	PERMIT::_PERMIT(_MODULE_, 'module|mp|ac', 'สามารถลบ Ban User ID ได้', 'redirect', '');
	$dellogin = REQ_get('del', 'post', 'array');
	if (count($dellogin) > 0) {
		foreach ($dellogin as $k => $v) {
			DB_DEL('logs_login', ['logs_id' => $v]);
		}
	}
	setRaiseMsg('Data deletion is complete..', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
	exit;
}
_systemlogs_delete_admin_oldLoginlogs($num = 500);
?>
<script language="javascript">
	function ConfirmDelete() {
		if (confirm('Do you really want to delete the information you choose?')) {
			document.frmList.submit();
		}

		return false;
	}
</script>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">Error login history</h1>
	</div>
</div>
<div id="page-content">
	<div class="row">
		<div class="col-xs-12">
			<div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">Error login history</h3>
				</div>
				<div class="panel-body">
					<?php displayRaiseMsg(); ?>
					<form action="" method="post" name="frmList">
						<input type="hidden" name="ac" value="delete">
						<div style="padding-bottom: 10px;"><button id="demo-btn-addrow" class="btn btn-danger" onclick="return ConfirmDelete();">Delete selected data</button></div>
						<table class="table table-striped">
							<thead>
								<tr>
									<th><input type="checkbox" onclick="FunCheckedAll(this.checked, 'checkall');"></th>
									<th>LOGSID</th>
									<th>IP Address</th>
									<th>Login Des</th>
									<th>Login Time</th>
								</tr>
							</thead>
							<tbody>
								<?php
								if (count(@$alog['data']) > 0 && is_array(@$alog['data'])) {
									foreach ($alog['data'] as $k => $v) {
								?>
										<tr>
											<td><input type="checkbox" class="checkall" name="del[]" value="<?php echo $v['logs_id']; ?>"></td>
											<td><?php echo $v['logs_id']; ?></td>
											<td><?php echo $v['loginIp']; ?></td>
											<td align="left"><?php echo $v['loginDes']; ?></td>
											<td>
												<?php
												$checkdate = date('dmY', $v['logs_time']);
												if ($currentdate == $checkdate) {
													echo '<b>Today</b> ' . strTimeFormat($v['logs_time'], " %H:%M");
												} else {
													echo strTimeFormat($v['logs_time'], "%d %b %Y %H:%M");
												}
												?>
											</td>
										</tr>
									<?php }
								} else { ?>
									<tr height="50">
										<td colspan="4" align="center">No logs</td>
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