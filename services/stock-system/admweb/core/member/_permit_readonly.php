<?php
/**
 * FILE: core/member/_permit_readonly.php
 * ROLE: partial แสดง permission grid แบบ read-only (ไฮไลต์ตาม preset ที่เลือก ผ่าน JS presetModules)
 */
$__permit = permitGroupRegistry();
$__padLv = ['head' => 0, 'submenu' => 20, 'subsubmenu' => 38, 'page' => 0, 'option' => 0];
foreach ($__permit as $__module => $__subs) {
	if (!is_array($__subs)) {
		continue;
	}
	if ($__module === 'dashboardhome') {
		continue;
	}
	?>
	<div class="perm-module">
		<div class="perm-modhead"><span class="modtitle"><?php echo htmlspecialchars(getPermitModuleTitle($__module)); ?></span></div>
		<div class="perm-modbody">
			<div class="perm-headgrid">
				<?php foreach ($__subs as $__subname => $__levels) {
					if (!is_array($__levels)) {
						continue;
					}
					$__grpId = md5($__module . '|' . $__subname);
					$__isOption = ($__subname === 'Option'); ?>
					<div class="perm-headcard">
						<div class="panel panel-default">
							<div class="panel-heading collapsed" style="padding:6px 10px;cursor:pointer" data-toggle="collapse" data-target="#rogrp-<?php echo $__grpId; ?>">
								<i class="perm-caret fa fa-chevron-down"></i>
								<input class="magic-checkbox" type="checkbox" disabled id="rohead-master-<?php echo $__grpId; ?>">
								<label style="font-weight:600<?php echo $__isOption ? ';color:#197b30' : ''; ?>"><?php echo htmlspecialchars((string)$__subname); ?></label>
								<span class="perm-count" id="rocount-<?php echo $__grpId; ?>"></span>
							</div>
							<div id="rogrp-<?php echo $__grpId; ?>" class="collapse">
								<div style="padding:6px 12px">
									<?php foreach (['head', 'submenu', 'subsubmenu', 'page', 'option'] as $__lv) {
										if (empty($__levels[$__lv]) || !is_array($__levels[$__lv])) {
											continue;
										}
										$__pad = $__padLv[$__lv] ?? 0;
										foreach ($__levels[$__lv] as $__kk => $__vv) { ?>
											<div class="form-group checkbox" style="margin:3px 0;padding-left:<?php echo $__pad; ?>px">
												<input class="magic-checkbox roperm rohead-<?php echo $__grpId; ?>" type="checkbox" disabled value="<?php echo htmlspecialchars((string)$__kk, ENT_QUOTES); ?>" id="roperm-<?php echo md5($__kk . $__vv); ?>" />
												<label for="roperm-<?php echo md5($__kk . $__vv); ?>"><?php echo $__vv; ?></label>
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
