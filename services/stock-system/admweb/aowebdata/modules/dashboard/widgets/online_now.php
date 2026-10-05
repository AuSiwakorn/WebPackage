<?php
$__r = DB_LIST('member_online', array('online_id' => array('>', 0)), 0, 1);
$n = (int)($__r['num_rows'] ?? 0);
?>
<div class="panel panel-purple panel-colorful media middle pad-all">
	<div class="media-left">
		<div class="pad-hor"><i class="fa fa-circle fa-3x"></i></div>
	</div>
	<div class="media-body">
		<p class="text-2x mar-no text-semibold"><?php echo $n; ?></p>
		<p class="mar-no">ออนไลน์ตอนนี้</p>
	</div>
</div>
