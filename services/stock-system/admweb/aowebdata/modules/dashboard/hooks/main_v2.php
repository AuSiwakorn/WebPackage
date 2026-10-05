<?php
$oUser = login_logout::getLoginData();

$_aWidgetList = array();
include __DIR__ . '/../widgets.php';
$widgets = is_array($_aWidgetList) ? $_aWidgetList : array();

$perm  = login_logout::getAdminPermission();
$perm  = is_array($perm) ? $perm : array();
$isAll = in_array('all', $perm, true);

$all = array();
foreach ($widgets as $id => $w) {
	$p = (string)($w['perm'] ?? '');
	if (!$isAll && !in_array($p, $perm, true)) {
		continue;
	}
	$w['id'] = $id;
	$all[] = $w;
}

// เรียงตาม widget_order ของ preset ผู้ใช้ (resolve จาก user_id → รองรับสิงร่าง)
$uid   = (int)($oUser->user_id ?? 0);
$order = array();
if ($uid > 0) {
	$__me = DB_GET('member_user', array('user_id' => $uid));
	$pid  = (int)($__me['preset_id'] ?? 0);
	if ($pid > 0) {
		$__pr = DB_GET('permission_preset', array('preset_id' => $pid));
		if (is_array($__pr) && !empty($__pr['widget_order'])) {
			$order = array_values(array_filter(array_map('trim', explode(',', (string)$__pr['widget_order']))));
		}
	}
}
if ($order) {
	$__pos = array_flip($order);
	$__big = count($order);
	$__i = 0;
	foreach ($all as &$w) {
		$w['__k'] = array($__pos[$w['id']] ?? $__big, $__i++);
	}
	unset($w);
	usort($all, function ($a, $b) {
		return ($a['__k'][0] <=> $b['__k'][0]) ?: ($a['__k'][1] <=> $b['__k'][1]);
	});
	foreach ($all as &$w) {
		unset($w['__k']);
	}
	unset($w);
}

$__wdir = __DIR__ . '/../widgets/';
if (empty($all)) {
	echo '<div class="alert alert-info">ยังไม่ได้รับสิทธิ์แสดง widget ใดบน dashboard</div>';
} else {
	$__used = 0;
	$__open = false;
	foreach ($all as $w) {
		$col = (int)($w['col'] ?? 12);
		if ($col < 1 || $col > 12) {
			$col = 12;
		}
		if (!$__open || ($__used + $col) > 12) {
			if ($__open) {
				echo '</div>';
			}
			echo '<div class="row">';
			$__open = true;
			$__used = 0;
		}
		$md = ($col >= 12) ? 12 : 6;
		echo '<div class="col-md-' . $md . ' col-lg-' . $col . '">';
		$__f = $__wdir . $w['id'] . '.php';
		if (is_file($__f)) {
			include $__f;
		}
		echo '</div>';
		$__used += $col;
	}
	if ($__open) {
		echo '</div>';
	}
}
