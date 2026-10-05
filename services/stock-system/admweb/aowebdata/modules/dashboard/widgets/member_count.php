<?php $n = (int)Func_CountMember(); ?>
<div class="panel panel-mint panel-colorful media middle pad-all">
	<div class="media-left">
		<div class="pad-hor"><a href="<?php echo _admin_buil_link('index.php?module=member&mp=user'); ?>" style="color:#fff"><i class="fa fa-users fa-3x"></i></a></div>
	</div>
	<div class="media-body">
		<p class="text-2x mar-no text-semibold"><a href="<?php echo _admin_buil_link('index.php?module=member&mp=user'); ?>" style="color:#fff"><?php echo $n; ?></a></p>
		<p class="mar-no">จำนวนสมาชิก</p>
	</div>
</div>
