<?php
$id = REQ_get('id', 'request', 'int', '');
$a = _systemlogs_getMailLogsId($id);
$currentdate = date('dmY', _TIME_);
$befortime = date('dmY', _TIME_ - 86400);
?>
<div class="tableclass">
	<table border="0" align="center" cellpadding="9" cellspacing="0" width="99%">
		<?php foreach ($a as $k => $v) { ?>
			<tr class="ttop2" style="height:38px;">
				<td align="left"><?php echo $k; ?></td>
			</tr>
			<tr>
				<td align="left" style="padding: 20px;">
					<?php
					if ($k == 'logs_time') {
						echo strTimeFormat(@$v);
					} else {
						echo $v;
					}
					?>
				</td>
			</tr>
		<?php } ?>
	</table>
</div>