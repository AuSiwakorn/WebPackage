<?php
/**
 * FILE: core/member/function_preset.php
 * ROLE: service layer ของระบบ Preset สิทธิ์ (CRUD + query สมาชิก)
 * TABLES: permission_preset, member_user, member_member
 */
if (!function_exists('preset_list')) {

	function preset_list()
	{
		$r = DB_LIST('permission_preset', [], 1000, 1, 'ORDER BY sort ASC, preset_name ASC, preset_id ASC');
		return $r['data'] ?? [];
	}

	function preset_get($id)
	{
		return DB_GET('permission_preset', ['preset_id' => (int)$id]);
	}

	function preset_member_count($id)
	{
		$where = ['preset_id' => (int)$id];
		if (!login_logout::is_SuperAdmin()) {
			$where['user_id'] = ['!=', 1];
		}
		$r = DB_LIST('member_user', $where, 1, 1);
		return (int)($r['num_rows'] ?? 0);
	}

	function preset_members($id)
	{
		$sql = "SELECT u.user_id, u.username, m.firstname, m.lastname
				FROM `" . _DBPREFIX_ . "member_user` u
				LEFT JOIN `" . _DBPREFIX_ . "member_member` m ON m.user_id = u.user_id
				WHERE u.preset_id = :pid";
		if (!login_logout::is_SuperAdmin()) {
			$sql .= " AND u.user_id <> 1";
		}
		$r = DB_JOIN($sql, [':pid' => (int)$id], 1000, 1, 'ORDER BY u.username ASC');
		return $r['data'] ?? [];
	}

	function preset_save($id, $name, $modulesStr, $note = '', $widgetOrder = '')
	{
		$now = date('Y-m-d H:i:s');
		$data = [
			'preset_name'  => $name,
			'modules'      => $modulesStr,
			'note'         => $note,
			'widget_order' => (string)$widgetOrder,
			'edit_date'    => $now,
		];
		if ((int)$id > 0) {
			DB_UP('permission_preset', $data, ['preset_id' => (int)$id]);
			return (int)$id;
		}
		$data['add_date'] = $now;
		return (int)DB_ADD('permission_preset', $data);
	}

	function preset_delete($id)
	{
		$r = DB_LIST('member_user', ['preset_id' => (int)$id], 1, 1);
		if ((int)($r['num_rows'] ?? 0) > 0) {
			return false;
		}
		return DB_DEL('permission_preset', ['preset_id' => (int)$id]);
	}

	function preset_assign($userId, $presetId)
	{
		return DB_UP('member_user', ['preset_id' => (int)$presetId], ['user_id' => (int)$userId]);
	}

	function preset_unassigned_users()
	{
		$sql = "SELECT u.user_id, u.username, u.status, u.modules, m.firstname, m.lastname
				FROM `" . _DBPREFIX_ . "member_user` u
				LEFT JOIN `" . _DBPREFIX_ . "member_member` m ON m.user_id = u.user_id
				WHERE (u.preset_id = 0 OR u.preset_id IS NULL) AND u.status != 'member'";
		if (!login_logout::is_SuperAdmin()) {
			$sql .= " AND u.user_id <> 1";
		}
		$sql .= " ORDER BY u.username ASC";
		$r = DB_JOIN($sql, [], 1000, 1);
		return $r['data'] ?? [];
	}
}
