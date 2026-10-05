<?php
/**
 * FILE: core/member/main_edit_group.php
 * ROLE: หน้าแก้ preset สิทธิ์ (accordion grid) — สร้าง/แก้ + save modules
 * TABLES: permission_preset
 */
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'Page : แก้ไข Preset สิทธิ์', 'redirect', 'SET');
$id = REQ_get('id', 'get', 'int', 0);

if (_AC_ == 'savepreset') {
	$name = trim(REQ_get('preset_name', 'post', 'str'));
	$note = trim(REQ_get('note', 'post', 'str', ''));
	$mlist = REQ_get('mlist', 'request', 'array');
	$checkall = REQ_get('checkall', 'request', 'array', '');
	if (!is_array($mlist)) {
		$mlist = [];
	}
	if ($name === '') {
		setRaiseMsg('กรุณาตั้งชื่อ preset', _TIME_, 1);
		CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=edit_group" . ($id > 0 ? "&id=" . $id : ""));
		exit;
	}
	if (!empty($checkall) || in_array('all', $mlist, true)) {
		$modulesStr = 'all';
	} elseif (count($mlist) > 0) {
		$modulesStr = implode(',', $mlist);
	} else {
		$modulesStr = '';
	}
	$_aWidgetList = array();
	@include(PATH_MODULE . '/dashboard/widgets.php');
	$__validW = is_array($_aWidgetList) ? array_keys($_aWidgetList) : array();
	$__rawOrder = REQ_get('widget_order', 'post', 'str', '');
	$widgetOrder = '';
	if ($__rawOrder !== '') {
		$__ids = array_filter(array_map('trim', explode(',', $__rawOrder)));
		$__ids = array_values(array_unique(array_filter($__ids, function ($x) use ($__validW) {
			return in_array($x, $__validW, true);
		})));
		$widgetOrder = implode(',', $__ids);
	}
	$newId = preset_save($id, $name, $modulesStr, $note, $widgetOrder);
	Func_Addlogs("[Member] Save preset ID {$newId}");
	setRaiseMsg('บันทึก preset แล้ว', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=group");
	exit;
}

$preset = ($id > 0) ? preset_get($id) : false;
$presetName = is_array($preset) ? (string)$preset['preset_name'] : '';
$presetNote = is_array($preset) ? (string)$preset['note'] : '';
$presetChecked = (is_array($preset) && isset($preset['modules'])) ? explode(',', (string)$preset['modules']) : [];
$_aWidgetList = array();
@include(PATH_MODULE . '/dashboard/widgets.php');
if (!is_array($_aWidgetList)) {
	$_aWidgetList = array();
}
$__savedOrder = array_filter(array_map('trim', explode(',', (string)(is_array($preset) ? ($preset['widget_order'] ?? '') : ''))));
$__orderedIds = array();
foreach ($__savedOrder as $__wid) {
	if (isset($_aWidgetList[$__wid]) && !in_array($__wid, $__orderedIds, true)) {
		$__orderedIds[] = $__wid;
	}
}
foreach ($_aWidgetList as $__wid => $__w) {
	if (!in_array($__wid, $__orderedIds, true)) {
		$__orderedIds[] = $__wid;
	}
}
?>
<div id="page-head">
	<div id="page-title" style="display:flex;align-items:center;gap:10px">
		<h1 class="page-header text-overflow" style="flex:1 1 auto;margin-bottom:0"><?php echo ($id > 0) ? 'แก้ไข Preset สิทธิ์' : 'สร้าง Preset สิทธิ์'; ?></h1>
		<button type="submit" form="form1" class="btn btn-mint"><i class="fa fa-save"></i> <?php echo ($id > 0) ? 'บันทึก Preset' : 'สร้าง Preset'; ?></button>
	</div>
</div>
<form name="thisform" id="form1" action="" method="post">
	<div id="page-content">
		<?php displayRaiseMsg(); ?>
		<div class="panel">
			<div class="panel-body">
				<div class="form-group">
					<label>ชื่อ Preset</label>
					<input type="text" name="preset_name" value="<?php echo htmlspecialchars($presetName, ENT_QUOTES); ?>" class="form-control" required="required" placeholder="เช่น เซลล์, บัญชี, ช่าง">
				</div>
				<div class="form-group">
					<label>หมายเหตุ</label>
					<input type="text" name="note" value="<?php echo htmlspecialchars($presetNote, ENT_QUOTES); ?>" class="form-control">
				</div>
			</div>
		</div>
			<?php if ($__orderedIds) { ?>
			<style>
			.wdg-item{background:#f7f7f7;border:1px solid #e2e2e2;border-radius:4px;padding:8px 10px;margin:5px 0;display:flex;align-items:center;gap:8px}
			.wdg-item.wdg-off{opacity:.5}
			.wdg-handle{cursor:move;color:#aaa}
			.wdg-item .wdg-name{flex:1 1 auto;margin:0;font-weight:normal}
			.wdg-placeholder{border:1px dashed #bbb;background:#fffbe6;height:40px;margin:5px 0;border-radius:4px}
			</style>
			<div class="panel">
				<div class="panel-heading"><h3 class="panel-title"><i class="fa fa-th-large"></i> Widget บน Dashboard <small class="text-muted">(ติ๊ก = ใช้ได้ · ลาก = จัดลำดับ)</small></h3></div>
				<div class="panel-body">
					<p class="text-muted" style="margin-bottom:12px"><i class="fa fa-info-circle"></i> ติ๊ก = ให้สิทธิ์เห็นบน dashboard · ลากเพื่อจัดลำดับ · ใช้ร่วมทั้ง preset · ตัวที่กว้างไม่เต็มแถวจะต่อกันเองจนครบ 12</p>
					<ul class="list-unstyled" id="wdgSortAll" style="margin:0;padding:0">
						<?php foreach ($__orderedIds as $__wid) {
							$__w = $_aWidgetList[$__wid];
							$__perm = (string)($__w['perm'] ?? '');
							$__chk = (in_array($__perm, $presetChecked, true) || in_array('all', $presetChecked, true)) ? ' checked="checked"' : '';
							$__cid = 'wdgchk-' . $__wid;
							$__type = $__w['type'] ?? 'card';
							if ($__type === 'table') { $__badge = 'ตาราง'; $__bcls = 'label-mint'; }
							elseif ($__type === 'chart') { $__badge = 'กราฟ'; $__bcls = 'label-purple'; }
							else { $__badge = 'การ์ด'; $__bcls = 'label-info'; }
							$__col = (int)($__w['col'] ?? 12);
							if ($__col < 1 || $__col > 12) { $__col = 12; }
							$__colTxt = ($__col >= 12) ? 'เต็มแถว' : ('กว้าง ' . $__col . '/12'); ?>
							<li class="wdg-item" data-id="<?php echo htmlspecialchars($__wid, ENT_QUOTES); ?>">
								<i class="fa fa-bars wdg-handle"></i>
								<input class="magic-checkbox checkpoint wdg-check" type="checkbox" name="mlist[]" value="<?php echo htmlspecialchars($__perm, ENT_QUOTES); ?>" id="<?php echo htmlspecialchars($__cid, ENT_QUOTES); ?>"<?php echo $__chk; ?>>
								<label class="wdg-name" for="<?php echo htmlspecialchars($__cid, ENT_QUOTES); ?>"><?php echo htmlspecialchars($__w['name'] ?? $__wid); ?></label>
								<span class="label <?php echo $__bcls; ?>"><?php echo $__badge; ?></span>
								<span class="label label-default"><?php echo $__colTxt; ?></span>
							</li>
						<?php } ?>
					</ul>
					<input type="hidden" name="widget_order" id="widget_order" value="<?php echo htmlspecialchars((string)(is_array($preset) ? ($preset['widget_order'] ?? '') : ''), ENT_QUOTES); ?>">
				</div>
			</div>
			<?php } ?>
		<div class="panel">
			<div class="panel-heading">
				<h3 class="panel-title">Permission</h3>
			</div>
			<div class="panel-body">
				<div class="checkbox" style="margin-bottom:30px">
					<?php $seall = (in_array('all', $presetChecked)) ? ' checked="checked" ' : ''; ?>
					<input <?php echo $seall; ?> class="magic-checkbox checkall" type="checkbox" name="checkall[]" onclick="return checkAllList();" id="checkallpermission">
					<label for="checkallpermission">Enable all permissions</label>
				</div>
				<?php
				$permit = permitGroupRegistry();
				$semo = ($seall === ' checked="checked" ') ? ' disabled' : '';
				$__padLv = ['head' => 0, 'submenu' => 20, 'subsubmenu' => 38, 'page' => 0, 'option' => 0];
				foreach ($permit as $module => $subs) {
					if (!is_array($subs)) {
						continue;
					}
					if ($module === 'dashboardhome') {
						continue;
					}
					$modId = md5($module); ?>
					<div class="perm-module">
						<div class="perm-modhead">
							<div class="checkbox">
								<input <?php echo $semo; ?> class="magic-checkbox checkallmodule checkall-<?php echo $modId; ?>" type="checkbox" onclick="return checkAllModule('<?php echo $modId; ?>');" id="checkallmodule-<?php echo $modId; ?>">
								<label for="checkallmodule-<?php echo $modId; ?>"><span class="modtitle"><?php echo htmlspecialchars(getPermitModuleTitle($module)); ?></span></label>
							</div>
						</div>
						<div class="perm-modbody">
							<div class="perm-headgrid">
								<?php foreach ($subs as $subname => $levels) {
									if (!is_array($levels)) {
										continue;
									}
									$grpId = md5($module . '|' . $subname);
									$isOption = ($subname === 'Option');
									$grpTitle = (string)$subname; ?>
									<div class="perm-headcard">
										<div class="panel panel-default">
											<div class="panel-heading collapsed" style="padding:6px 10px;cursor:pointer" data-toggle="collapse" data-target="#grp-<?php echo $grpId; ?>" aria-expanded="false">
												<i class="perm-caret fa fa-chevron-down"></i>
												<input <?php echo $semo; ?> class="magic-checkbox checkallhead checkhead-<?php echo $grpId; ?> <?php echo $modId; ?>" type="checkbox" onclick="event.stopPropagation();return checkAllHead('<?php echo $grpId; ?>');" id="checkallhead-<?php echo $grpId; ?>">
												<label for="checkallhead-<?php echo $grpId; ?>" style="font-weight:600<?php echo $isOption ? ';color:#197b30' : ''; ?>" onclick="event.stopPropagation()"><?php echo htmlspecialchars($grpTitle); ?></label>
												<span class="perm-count" id="permcount-<?php echo $grpId; ?>"></span>
											</div>
											<div id="grp-<?php echo $grpId; ?>" class="collapse">
												<div style="padding:6px 12px">
													<?php foreach (['head', 'submenu', 'subsubmenu', 'page', 'option'] as $__lv) {
														if (empty($levels[$__lv]) || !is_array($levels[$__lv])) {
															continue;
														}
														$__pad = $__padLv[$__lv] ?? 0;
														foreach ($levels[$__lv] as $kk => $vv) {
															$se = (in_array($kk, $presetChecked) || in_array('all', $presetChecked)) ? ' checked="checked" ' : ''; ?>
															<div class="form-group checkbox" style="margin:3px 0;padding-left:<?php echo $__pad; ?>px">
																<input class="permissioncheck magic-checkbox checkpoint <?php echo $modId; ?> head-<?php echo $grpId; ?><?php echo $__lv === 'head' ? ' perm-grouphead' : ''; ?>"<?php echo $__lv === 'head' ? ' data-grp="' . $grpId . '"' : ''; ?> type="checkbox" <?php echo $se; ?> name="mlist[]" value="<?php echo htmlspecialchars((string)$kk, ENT_QUOTES); ?>" id="permission-<?php echo md5($kk . $vv); ?>" />
																<label for="permission-<?php echo md5($kk . $vv); ?>"><?php echo $vv; ?></label>
															</div>
													<?php }
													} ?>
												</div>
											</div>
										</div>
									</div>
								<?php } ?>
							</div>
						</div>
					</div>
				<?php } ?>
				<button type="submit" class="btn btn-mint">บันทึก Preset</button>
				<a href="<?php echo _admin_buil_link('index.php?module=member&mp=group'); ?>" class="btn btn-default">ยกเลิก</a>
			</div>
		</div>
	</div>
	<input type="hidden" name="id" value="<?php echo (int)$id; ?>" />
	<input type="hidden" name="ac" value="savepreset">
</form>
