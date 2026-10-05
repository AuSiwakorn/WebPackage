<?php
$__today = strtotime(date('Y-m-d') . ' 00:00:00');
$__r = DB_LIST('member_admin_login_logs', array('logs_time' => array('>=', $__today)), 0, 1);
$n = (int)($__r['num_rows'] ?? 0);
?>
<div class="panel panel-warning panel-colorful media middle pad-all">
	<div class="media-left">
		<div class="pad-hor"><i class="fa fa-sign-in fa-3x"></i></div>
	</div>
	<div class="media-body">
		<p class="text-2x mar-no text-semibold"><?php echo $n; ?></p>
		<p class="mar-no">เข้าระบบวันนี้ (ผู้ดูแล)</p>
	</div>
</div>
