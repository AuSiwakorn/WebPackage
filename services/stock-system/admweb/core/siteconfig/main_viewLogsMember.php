<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Viewe Logs Member ได้', 'redirect', 'SET');
$abcDel = REQ_get('del', 'post', 'str', '');
$page 	= (@$_REQUEST['page'] > 0) ? @$_REQUEST['page'] : 1;
$alog 	= _systemlogs_getMemberLogs($num = 25, $page);
$currentdate = date('dmY', _TIME_);
$befortime = date('dmY', _TIME_ - 86400);
if (_AC_ == "delete" && count($abcDel) > 0) {
	PERMIT::_PERMIT(_MODULE_, 'module|mp|ac', 'สามารถลบ Viewe Logs Member ได้', 'redirect', '');
	foreach ($abcDel as $k => $v) {
		_systemlogs_delete_MemberLogs($v);
	}
	setRaiseMsg('Data deletion is complete. please wait.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
	exit;
}
_systemlogs_delete_Member_oldlogs($num = 500);
?>
<script language="javascript">
	function mOvr(src, clrOver) {
		src.style.cursor = 'hand';
		src.bgColor = clrOver;
	}

	function mOut(src, clrIn) {
		src.style.cursor = 'default';
		src.bgColor = clrIn;
	}

	function ConfirmDelete() {
		if (confirm('Do you really want to delete the information you choose?')) {
			document.frmList.submit();
		}
	}

	function selectRow(row) {
		document.getElementById(row).style.background = "#D6DEEC";
	}

	function deselectRow(row, color) {
		document.getElementById(row).style.background = color;
	}

	$(function() {
		$(".submit").button();
	});
</script>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">Member login history</h1>
	</div>
</div>
<div id="page-content">
	<div class="row">
		<div class="col-xs-12">
			<div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">Member login history</h3>
				</div>
				<div class="panel-body">
					<?php displayRaiseMsg(); ?>
					<form action="" method="post" name="frmList">
						<input type="hidden" name="ac" value="delete">
						<table border=0 cellpadding=5 cellspacing=0 width=98% align=center>
							<tr>
								<td align="left"><a href="javascript:ConfirmDelete()" class="submit">Delete selected data</a></td>
								<td align="right"><?php BuilListPage($alog, _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . ''), $page); ?></td>
							</tr>
						</table>
						<table border=0 cellpadding=5 cellspacing=0 width=98% align=center>
							<tr bgcolor="#2f97ff" height="24">
								<td width="40" align="center" bgcolor="#993300" class="gridTitle"><input type="checkbox" id="checkAll" onclick="selectAll()"></td>
								<td align="center" bgcolor="#CCCCCC" width="70"><?php ___lang('LOGSID'); ?></td>
								<td align="center" bgcolor="#CCCCCC" width="80"><?php ___lang('MEMBER ID'); ?></td>
								<td align="left" bgcolor="#CCCCCC"><?php ___lang('Fullname'); ?></td>
								<td align="center" bgcolor="#CCCCCC"><?php ___lang('Username'); ?></td>
								<td align="center" bgcolor="#CCCCCC"><?php ___lang('Ipaddress'); ?></td>
								<td align="center" bgcolor="#CCCCCC" width="150"><?php ___lang('Login Time'); ?></td>
							</tr>
							<?php
							$count = 0;
							$bgc = "#F6F6F6";
							$abgcolor = array();
							if (count(@$alog['data']) > 0 && is_array(@$alog['data'])) {
								foreach ($alog['data'] as $k => $v) {
									$count++;
									$bgc = ($bgc == "#F6F6F6") ? "#f2f2f2" : "#F6F6F6";
									$abgcolor[$count] = $bgc;
									$v['fullname'] = $v['firstname'] . ' ' . $v['lastname'];

							?>
									<tr id="row<?php echo $count; ?>" bgcolor="<?php echo $bgc; ?>" height="22" onMouseOver='mOvr(this,"#E6E6E6");' onMouseOut='mOut(this,"<?php echo $bgc; ?>");'>
										<td class="gridRow" align="center"><input type="checkbox" id="check<?php echo $count; ?>" name="del[]" value="<?php echo $v['logs_id']; ?>" onclick="if(this.checked==true){ selectRow('row<?php echo $count; ?>'); }else{ deselectRow('row<?php echo $count; ?>', '<?php echo $bgc; ?>'); }"></td>
										<td align="center"><?php echo $v['logs_id']; ?></td>
										<td align="center"><?php echo $v['user_id']; ?></td>
										<td align="left"><?php echo $v['fullname']; ?></td>
										<td align="center"><?php echo $v['username']; ?></td>
										<td align="center" bgcolor="#CCCCCC"><?php ___lang('Ipaddress'); //keeree25/8/2 69,90
																				?></td>
										<td class="gridRow" align="left">
											<?php
											$checkdate = date('dmY', $v['logs_time']);
											if ($currentdate == $checkdate) {
												echo '<b>Today</b> ' . strTimeFormat($v['logs_time'], " %H:%M");
												/*
			} elseif ($befortime == $checkdate) {
				echo '<b>xx</b> xx ' . strTimeFormat($v['logs_time'], " %H:%M");
				*/
											} else {
												echo strTimeFormat($v['logs_time'], "%d %b %Y %H:%M");
											}
											?>
										</td>
									</tr>
								<?php }
							} else { ?>
								<tr id="row<?php echo $count; ?>" bgcolor="<?php echo $bgc; ?>" height="22" onMouseOver='mOvr(this,"#E6E6E6");' onMouseOut='mOut(this,"<?php echo $bgc; ?>");'>
									<td colspan="7" align="center" class="gridRow">There is no member information in the system.</td>
								</tr>
							<?php } ?>
						</table>
						<div style="padding: 10px;" align="right">
							<?php BuilListPage($alog, _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=viewLogs'), $page); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script language="javascript">
	function selectAll() {
		if (document.getElementById("checkAll").checked == true) {
			document.getElementById("checkAll").checked = true;
			<? for ($i = 1; $i <= $count; $i++) {
				echo "document.getElementById(\"check$i\").checked=true; document.getElementById(\"row$i\").style.background='#D6DEEC'; \n";
			}	?>
		} else {
			document.getElementById("checkAll").checked = false;
			<? for ($i = 1; $i <= $count; $i++) {
				echo "document.getElementById(\"check$i\").checked=false; document.getElementById(\"row$i\").style.background='" . $abgcolor[$i] . "'; \n";
			} ?>
		}
	}
</script>