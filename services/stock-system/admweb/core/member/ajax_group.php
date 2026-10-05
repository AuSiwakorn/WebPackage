<?php
/**
 * FILE: core/member/ajax_group.php
 * ROLE: AJAX endpoint — assign preset ให้ user (doAjax.php?module=member&mp=group&ac=assign_preset)
 * TABLES: permission_preset, member_user
 */
if (!function_exists('preset_assign')) {
	require_once __DIR__ . '/function_preset.php';
}
header('Content-Type: application/json; charset=utf-8');

if (!PERMIT::_PERMIT(_MODULE_, 'module|mp', 'Page : กลุ่มผู้ดูแล', 'return')) {
	echo json_encode(['ok' => false, 'msg' => 'ไม่มีสิทธิ์'], JSON_UNESCAPED_UNICODE);
	exit;
}

if (_AC_ === 'assign_preset') {
	$userId = REQ_get('user_id', 'get', 'int', 0);
	$presetId = REQ_get('preset_id', 'get', 'int', 0);
	if ($userId <= 0) {
		echo json_encode(['ok' => false, 'msg' => 'user ไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE);
		exit;
	}
	$presetName = '— ว่าง —';
	if ($presetId > 0) {
		$p = preset_get($presetId);
		if (!is_array($p)) {
			echo json_encode(['ok' => false, 'msg' => 'ไม่พบ preset'], JSON_UNESCAPED_UNICODE);
			exit;
		}
		$presetName = (string)$p['preset_name'];
	}
	$okSave = preset_assign($userId, $presetId);
	echo json_encode([
		'ok'          => (bool)$okSave,
		'preset_id'   => $presetId,
		'preset_name' => $presetName,
		'msg'         => $okSave ? 'บันทึกแล้ว' : 'บันทึกไม่สำเร็จ',
	], JSON_UNESCAPED_UNICODE);
	exit;
}

echo json_encode(['ok' => false, 'msg' => 'unknown action'], JSON_UNESCAPED_UNICODE);
exit;
