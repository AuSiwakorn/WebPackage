<?php
/**
 * FILE: core/member/main_group.php
 * ROLE: หน้า list preset สิทธิ์ + assign user ที่ยังไม่มี preset
 * TABLES: permission_preset, member_user, member_member
 */
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'Page : กลุ่มผู้ดูแล', 'redirect', 'SET');
$ac = REQ_get('ac', 'request', 'str');

if ($ac == 'delete') {
	$id = REQ_get('id', 'get', 'int');
	if (preset_delete($id)) {
		Func_Addlogs("[Member] Delete preset ID {$id}");
		setRaiseMsg('ลบ preset แล้ว', _TIME_, 0);
	} else {
		setRaiseMsg('ลบไม่ได้ — preset นี้ยังมีสมาชิกอยู่ (ย้ายคนออกก่อน)', _TIME_, 1);
	}
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=group");
	exit;
}

if (_AC_ == 'sort') {
	$sorts = REQ_get('sort', 'post', 'array');
	if (is_array($sorts)) {
		foreach ($sorts as $pid => $s) {
			DB_UP('permission_preset', ['sort' => (int)$s], ['preset_id' => (int)$pid]);
		}
		setRaiseMsg('บันทึกการเรียงลำดับ preset แล้ว', _TIME_, 0);
	}
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=group");
	exit;
}

$presets = preset_list();
?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">จัดการ Preset สิทธิ์</h1>
	</div>
</div>
<div id="page-content">
	<?php displayRaiseMsg(); ?>
	<form action="<?php echo _admin_buil_link('index.php?module=member&mp=group&ac=sort'); ?>" method="post" onsubmit="return confirm('บันทึกการเรียงลำดับ preset?');">
		<input type="hidden" name="ac" value="sort" />
		<div class="row">
			<div class="col-sm-12" style="margin-bottom:12px">
				<a href="<?php echo _admin_buil_link('index.php?module=member&mp=edit_group'); ?>" class="btn btn-purple"><i class="fa fa-plus"></i> สร้าง preset ใหม่</a>
				<button type="submit" class="btn btn-info"><i class="fa fa-save"></i> บันทึกการเรียงลำดับ</button>
			</div>
		</div>
		<div class="panel">
			<div class="panel-body table-responsive">
				<table class="table table-striped table-bordered table-vcenter">
					<thead>
						<tr>
							<th width="70">ลำดับ</th>
							<th width="40">ID</th>
							<th>ชื่อ Preset</th>
							<th class="text-center" width="120">สมาชิก</th>
							<th class="text-center" width="100">สิทธิ์</th>
							<th class="text-center" width="160">จัดการ</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($presets as $p):
							$cnt = preset_member_count($p['preset_id']);
							$nk  = ($p['modules'] === 'all') ? 'ALL' : count(array_filter(explode(',', (string)$p['modules'])));
							$eu  = _admin_buil_link('index.php?module=member&mp=edit_group&id=' . $p['preset_id']);
							$du  = _admin_buil_link('index.php?module=member&mp=group&ac=delete&id=' . $p['preset_id']);
						?>
							<tr>
								<td><input type="number" name="sort[<?php echo (int)$p['preset_id']; ?>]" value="<?php echo (int)$p['sort']; ?>" class="form-control"></td>
								<td><?php echo htmlspecialchars($p['preset_id']); ?></td>
								<td><?php echo htmlspecialchars($p['preset_name']); ?></td>
								<td class="text-center">
									<?php if ($cnt > 0): ?>
										<button type="button" class="btn btn-link" style="padding:0;" data-toggle="modal" data-target="#presetMembers<?php echo (int)$p['preset_id']; ?>"><i class="fa fa-users"></i> <?php echo $cnt; ?></button>
									<?php else: ?>
										<span class="text-muted">0</span>
									<?php endif; ?>
								</td>
								<td class="text-center"><?php echo $nk; ?></td>
								<td class="text-center">
									<a href="<?php echo $eu; ?>" class="btn btn-sm btn-primary"><i class="fa fa-pencil"></i> แก้สิทธิ์</a>
									<?php if ($cnt > 0): ?>
										<button type="button" class="btn btn-sm disabled" disabled title="ลบไม่ได้ — ยังมีสมาชิก"><i class="fa fa-trash"></i></button>
									<?php else: ?>
										<a href="<?php echo $du; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ลบ preset นี้?');"><i class="fa fa-trash"></i></a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						<?php if (count($presets) === 0): ?>
							<tr><td colspan="6" class="text-center text-muted">ยังไม่มี preset — สร้างอันแรกด้านบน</td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</form>
	<?php $noPreset = preset_unassigned_users(); ?>
	<?php if (count($noPreset) > 0): ?>
		<div class="panel panel-warning">
			<div class="panel-heading">
				<h3 class="panel-title"><i class="fa fa-user-times"></i> User ที่ยังไม่มี Preset (<?php echo count($noPreset); ?>)</h3>
			</div>
			<div class="panel-body table-responsive">
				<table class="table table-striped table-bordered table-vcenter">
					<thead>
						<tr>
							<th width="60">ID</th>
							<th>Username</th>
							<th>ชื่อ</th>
							<th class="text-center" width="220">กำหนด Preset</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($noPreset as $u):
							$eu  = _admin_buil_link('index.php?module=member&mp=admin_edit&id=' . (int)$u['user_id']);
							$mod = (string)($u['modules'] ?? '');
							$acc = ($mod === 'all') ? 'ALL' : ($mod === '' ? '— ว่าง —' : count(array_filter(explode(',', $mod))) . ' สิทธิ์ (เก่า)');
						?>
							<tr>
								<td><?php echo (int)$u['user_id']; ?></td>
								<td><a href="<?php echo $eu; ?>"><i class="fa fa-user"></i> <?php echo htmlspecialchars((string)$u['username']); ?></a></td>
								<td><?php echo htmlspecialchars(trim(($u['firstname'] ?? '') . ' ' . ($u['lastname'] ?? ''))); ?></td>
								<td class="text-center">
									<select class="assign-preset form-control input-sm" data-uid="<?php echo (int)$u['user_id']; ?>" style="display:inline-block;width:auto;min-width:140px;">
										<option value="0">— ว่าง —</option>
										<?php foreach ($presets as $pp): ?>
											<option value="<?php echo (int)$pp['preset_id']; ?>"><?php echo htmlspecialchars($pp['preset_name']); ?></option>
										<?php endforeach; ?>
									</select>
									<?php if ($mod !== ''): ?><small class="text-muted">(เดิม: <?php echo htmlspecialchars($acc); ?>)</small><?php endif; ?>
									<span class="assign-status"></span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php
foreach ($presets as $p):
	$cntM = preset_member_count($p['preset_id']);
	if ($cntM <= 0) {
		continue;
	}
	$members = preset_members($p['preset_id']);
?>
	<div class="modal fade" id="presetMembers<?php echo (int)$p['preset_id']; ?>" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title"><i class="fa fa-users"></i> สมาชิก: <?php echo htmlspecialchars($p['preset_name']); ?> (<?php echo $cntM; ?>)</h4>
				</div>
				<div class="modal-body" style="max-height:60vh;overflow:auto;">
					<table class="table table-striped table-bordered table-vcenter" style="margin:0;">
						<thead>
							<tr><th width="60">ID</th><th>Username</th><th>ชื่อ</th></tr>
						</thead>
						<tbody>
							<?php foreach ($members as $m):
								$mu = _admin_buil_link('index.php?module=member&mp=admin_edit&id=' . (int)$m['user_id']);
							?>
								<tr>
									<td><?php echo (int)$m['user_id']; ?></td>
									<td><a href="<?php echo $mu; ?>"><?php echo htmlspecialchars((string)$m['username']); ?></a></td>
									<td><?php echo htmlspecialchars(trim(($m['firstname'] ?? '') . ' ' . ($m['lastname'] ?? ''))); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">ปิด</button>
				</div>
			</div>
		</div>
	</div>
<?php endforeach; ?>
