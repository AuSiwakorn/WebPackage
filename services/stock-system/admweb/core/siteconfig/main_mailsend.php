<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Mailing History ได้', 'redirect', 'SET');
$abcDel = REQ_get('del', 'post', 'array', '');
$page 	= (@$_REQUEST['page'] > 0) ? @$_REQUEST['page'] : 1;
$alog 	= _systemlogs_getMailLogs($num = 20, $page);
$currentdate = date('dmY', _TIME_);
$befortime = date('dmY', _TIME_ - 86400);

if (_AC_ == "delete" && count($abcDel) > 0) {
	PERMIT::_PERMIT(_MODULE_, 'module|mp|ac', 'สามารถลบ Mailing History ได้', 'redirect', '');
	foreach ($abcDel as $k => $v) {
		_systemlogs_delete_mailLogs($v);
	}
	setRaiseMsg('Data deletion is complete. please wait.', _TIME_, 0);
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
				<div class="panel-heading">
					<h3 class="panel-title">Mailsend History</h3>
				</div>
				<div class="panel-body">
					<?php displayRaiseMsg(); ?>
					<h2><?php echo @$_tabs['current']['name']; ?></h2>
					<form action="" method="post" name="frmList">
						<input type="hidden" name="ac" value="delete">
						<div style="padding-bottom: 10px;">
							<button id="demo-btn-addrow" class="btn btn-danger" onclick="return ConfirmDelete();">Delete selected data</button>
						</div>

						<table class="table table-striped table-hover">
							<thead>
								<tr>
									<td width="20"><input type="checkbox" onclick="FunCheckedAll(this.checked, 'checkall');"></td>
									<td width="50" align="center">ID</td>
									<td width="80" align="center">Status</td>
									<td>Subject</td>
									<td>To</td>
									<td>BCC List</td>
									<td width="150" align="center">Time</td>
								</tr>
							</thead>
							<tbody>
								<?php
								$count = 0;
								$bgc = "#F6F6F6";
								$abgcolor = array();
								if (count(@$alog['data']) > 0 && is_array(@$alog['data'])) {
									foreach ($alog['data'] as $k => $v) {
										$count++;
										$bgc = ($bgc == "#F6F6F6") ? "#f2f2f2" : "#F6F6F6";
										$abgcolor[$count] = $bgc;
										$decode_content = json_decode(base64_decode($v['content']), true);

										$status_text = isset($decode_content['status']) ? $decode_content['status'] : '-';
										if ($status_text == 'ok') {
											$status_label = '<span class="label label-success">OK</span>';
										} elseif ($status_text == 'error') {
											$status_label = '<span class="label label-danger">ERROR</span>';
										} else {
											$status_label = '<span class="label label-default">' . $status_text . '</span>';
										}

										$bcc_show = '-';
										if (isset($decode_content['send_bcc']) && is_array($decode_content['send_bcc']) && count($decode_content['send_bcc']) > 0) {
											$bcc_show = implode(', ', $decode_content['send_bcc']);
										}

								?>
										<tr id="row<?php echo $count; ?>" bgcolor="<?php echo $bgc; ?>" height="22" onMouseOver='mOvr(this,"#E6E6E6");' onMouseOut='mOut(this,"<?php echo $bgc; ?>");'>
											<td class="gridRow" align="center"><input type="checkbox" id="check<?php echo $count; ?>" name="del[]" value="<?php echo $v['logs_id']; ?>" onclick="if(this.checked==true){ selectRow('row<?php echo $count; ?>'); }else{ deselectRow('row<?php echo $count; ?>', '<?php echo $bgc; ?>'); }"></td>
											<td align="center"><?php echo $v['logs_id']; ?></td>

											<td align="center"><?php echo $status_label; ?></td>

											<td align="left"><?php echo $v['subject']; ?></td>
											<td align="left"><?php echo $v['sendto']; ?></td>

											<td align="left" style="color: #666; font-size: 0.9em;"><?php echo $bcc_show; ?></td>

											<td class="gridRow" align="center">
												<?php
												$checkdate = date('dmY', $v['logs_time']);
												if ($currentdate == $checkdate) {
													echo '<b>วันนี้</b> ' . strTimeFormat($v['logs_time'], " %H:%i");
												} elseif ($befortime == $checkdate) {
													echo '<b>เมื่อวาน</b> ' . strTimeFormat($v['logs_time'], " %H:%i");
												} else {
													echo strTimeFormat($v['logs_time'], "d/m/Y %H:%i");
												}
												?>
											</td>
										</tr>
									<?php }
								} else { ?>
									<tr height="50">
										<td colspan="7" align="center">There is no data to send mail from the system.</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
						<div style="padding: 10px;" align="right"></div>
						<?php BuilListPage($alog, _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . ''), $page); ?>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script language="javascript">
	function FunCheckedAll(isChecked, mode) {
		if (isChecked == true) {
			<?php for ($i = 1; $i <= $count; $i++) {
				echo "if(document.getElementById(\"check$i\")){ \n";
				echo "   document.getElementById(\"check$i\").checked=true; \n";
				echo "   document.getElementById(\"row$i\").style.background='#D6DEEC'; \n";
				echo "} \n";
			} ?>
		} else {
			<?php for ($i = 1; $i <= $count; $i++) {
				echo "if(document.getElementById(\"check$i\")){ \n";
				echo "   document.getElementById(\"check$i\").checked=false; \n";
				echo "   document.getElementById(\"row$i\").style.background='" . $abgcolor[$i] . "'; \n";
				echo "} \n";
			} ?>
		}
	}
</script>