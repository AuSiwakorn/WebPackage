<?php
$__sql = "SELECT l.logs_id, l.user_id, l.admin_ip, l.logs_time, u.username, m.firstname, m.lastname
	FROM `" . _DBPREFIX_ . "member_admin_login_logs` l
	LEFT JOIN `" . _DBPREFIX_ . "member_user` u ON u.user_id = l.user_id
	LEFT JOIN `" . _DBPREFIX_ . "member_member` m ON m.user_id = l.user_id
	ORDER BY l.logs_time DESC";
$__r = DB_JOIN($__sql, array(), 10, 1);
$__rows = $__r['data'] ?? array();
?>
<div class="panel">
	<div class="panel-heading"><h3 class="panel-title">เข้าสู่ระบบล่าสุด (ผู้ดูแล)</h3></div>
	<div class="panel-body">
		<div class="table-responsive">
			<table class="table table-striped">
				<thead><tr><th>ผู้ใช้</th><th>ชื่อ</th><th class="text-center">IP</th><th class="text-center">เวลา</th></tr></thead>
				<tbody>
				<?php if (empty($__rows)) { ?>
					<tr><td colspan="4" class="text-center text-muted">ยังไม่มีข้อมูล</td></tr>
				<?php } else {
					foreach ($__rows as $__v) {
						$__name = trim(($__v['firstname'] ?? '') . ' ' . ($__v['lastname'] ?? '')); ?>
						<tr>
							<td><?php echo htmlspecialchars((string)($__v['username'] ?? '-')); ?></td>
							<td><?php echo ($__name !== '') ? htmlspecialchars($__name) : '-'; ?></td>
							<td class="text-center"><?php echo htmlspecialchars((string)($__v['admin_ip'] ?? '-')); ?></td>
							<td class="text-center"><?php echo $__v['logs_time'] ? api_strTimeFormat($__v['logs_time'], '%d/%m/%Y %H:%M', false) : '-'; ?></td>
						</tr>
				<?php } } ?>
				</tbody>
			</table>
		</div>
	</div>
</div>
