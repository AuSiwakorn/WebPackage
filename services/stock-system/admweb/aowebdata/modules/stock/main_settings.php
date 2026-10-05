<?php
/**
 * FILE: admweb/aowebdata/modules/stock/main_settings.php
 * ROLE: หลังบ้าน admweb — ตั้งค่า POS (AOSTOCK): จำนวนสาขาสูงสุด · เปิด / ปิดเมนูของ POS เป็นกลุ่มฟีเจอร์ (รับคืน · ตรวจนับ · หมวดสินค้า · ประวัติเคลื่อนไหว · รายงานพนักงาน · ฝ่ายบัญชี · การแจ้งเตือน)
 * DEPENDS: function.php → api.php (feature_groups, features_off, features_off_set, branch_limit, branch_limit_set, branch_limit_label, branches_all, branches_active, log_add, csrf_token, csrf_check)
 * TABLES: ao_stock_setting (key features_off, branch_limit), ao_stock_branch (นับสาขา), ao_stock_log (ประวัติชนิด setting ของระบบ)
 * TODO:
 *   - [x] ช่วงที่ 9: สวิตช์ 7 กลุ่ม — ปิดแล้วซ่อนเมนูใน POS + เปิด URL ตรงไม่ได้ (require_login) · ปิดฝ่ายบัญชี = บัญชีบทบาทนี้เข้าระบบไม่ได้
 *   - [x] ช่วงที่ 14: จำนวนสาขาสูงสุดที่ผู้ดูแล POS เพิ่มได้ (นับรวมสาขาที่ปิดใช้งาน · ว่าง / 0 = ไม่จำกัด) · เปลี่ยนชื่อหน้าเป็น "ตั้งค่า POS"
 *
 * เมนูหลักของ POS (ขาย เปิด–ปิดร้าน รับเข้า เบิก สินค้า ประวัติ หน้าของผู้ดูแล) ปิดไม่ได้ — แก้รายการกลุ่มที่ feature_groups() ใน api.php
 */
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด เปิด–ปิดเมนู AOSTOCK ได้', 'redirect', 'SET');

$linkSelf = 'index.php?module=' . _MODULE_ . '&mp=' . _MP_;
$aGroups  = feature_groups();
$oLogin   = login_logout::getLoginData();
$byWho    = (is_object($oLogin) && isset($oLogin->user)) ? $oLogin->user . ' (หลังบ้าน admweb)' : 'หลังบ้าน admweb';

$ac = REQ_get('ac', 'post', 'str', '');
if ($ac === 'limit') {
	/* จำนวนสาขาสูงสุด (ช่วงที่ 14) — ว่าง / 0 = ไม่จำกัด */
	$raw = trim((string) REQ_get('branch_limit', 'post', 'str', ''));
	if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
		setRaiseMsg('เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง', _TIME_, 1);
	} elseif ($raw !== '' && !preg_match('/^\d{1,3}$/', $raw)) {
		setRaiseMsg('จำนวนสาขาต้องเป็นตัวเลข 0–999 (เว้นว่างหรือ 0 = ไม่จำกัด)', _TIME_, 1);
	} elseif (branch_limit_set((int) $raw, $byWho)) {
		$max  = branch_limit();
		$nAll = count(branches_all());
		$msg  = 'บันทึกแล้ว — จำนวนสาขาสูงสุด ' . branch_limit_label($max);
		if ($max > 0 && $nAll > $max) {
			$msg .= ' · ตอนนี้มี ' . $nAll . ' สาขา มากกว่าที่ตั้งไว้: สาขาเดิมใช้งานต่อได้ แต่เพิ่มสาขาใหม่ไม่ได้';
		}
		setRaiseMsg($msg, _TIME_, 0);
	} else {
		setRaiseMsg('ไม่มีอะไรเปลี่ยน', _TIME_, 0);
	}
	CustomRedirectToUrl($linkSelf);
	exit;
}
if ($ac === 'save') {
	if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
		setRaiseMsg('เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง', _TIME_, 1);
	} else {
		$aOn  = (isset($_POST['on']) && is_array($_POST['on'])) ? array_map('strval', $_POST['on']) : array();
		$aOff = array_values(array_diff(array_keys($aGroups), $aOn));
		$aCh  = features_off_set($aOff, 0);
		if ($aCh) {
			$aDetail = array();
			$aTxt    = array();
			foreach ($aCh as $k => $isOn) {
				$aDetail[$aGroups[$k]['label']] = ($isOn) ? 'เปิด' : 'ปิด';
				$aTxt[] = (($isOn) ? 'เปิด ' : 'ปิด ') . $aGroups[$k]['label'];
			}
			$aDetail['แก้โดย'] = $byWho;
			log_add('', 'setting', null, 'เปิด–ปิดเมนู POS จากหลังบ้าน', $aDetail);
			setRaiseMsg('บันทึกแล้ว — ' . implode(' · ', $aTxt), _TIME_, 0);
		} else {
			setRaiseMsg('ไม่มีอะไรเปลี่ยน', _TIME_, 0);
		}
	}
	CustomRedirectToUrl($linkSelf);
	exit;
}

$aOffNow = features_off();
$csrf    = csrf_token();
$max     = branch_limit();
$nAll    = count(branches_all());
$nOn     = count(branches_active());
$isFull  = ($max > 0 && $nAll >= $max);
$h       = function ($v) {
	return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">AOSTOCK — ตั้งค่า POS</h1>
	</div>
</div>
<div id="page-content">
	<div class="row">
		<div class="col-xs-12">
			<?php displayRaiseMsg(); ?>
			<div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">จำนวนสาขาสูงสุด</h3>
				</div>
				<div class="panel-body">
					<p>ผู้ดูแล POS เพิ่มสาขาได้ไม่เกินจำนวนนี้ · นับรวมสาขาที่ปิดใช้งาน (เปิดสาขาเดิมกลับมาใช้ได้เสมอ) · ได้โควตาคืนเมื่อลบสาขาที่ยังไม่มีข้อมูล · เว้นว่างหรือ 0 = ไม่จำกัด</p>
					<p>
						ตั้งไว้ <span class="text-semibold"><?php echo $h(branch_limit_label($max)); ?></span>
						· มีอยู่ <span class="text-semibold <?php echo ($isFull) ? 'text-danger' : 'text-success'; ?>"><?php echo $nAll; ?><?php echo ($max > 0) ? ' / ' . $max : ''; ?> สาขา</span>
						(เปิดใช้งาน <?php echo $nOn; ?> · ปิดใช้งาน <?php echo $nAll - $nOn; ?>)
						<?php if ($isFull) { ?>
							— <span class="text-danger">ครบแล้ว ผู้ดูแล POS เพิ่มสาขาใหม่ไม่ได้</span>
						<?php } ?>
					</p>
					<?php if ($max > 0 && $nAll > $max) { ?>
						<p class="text-danger">มีสาขามากกว่าที่ตั้งไว้ — สาขาเดิมใช้งานต่อได้ทั้งหมด แต่เพิ่มสาขาใหม่ไม่ได้จนกว่าจะเพิ่มจำนวนหรือลบสาขาที่ไม่ใช้</p>
					<?php } ?>
					<form method="post" action="<?php echo $h($linkSelf); ?>" class="form-inline">
						<input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
						<input type="hidden" name="ac" value="limit">
						<div class="form-group">
							<label for="branch-limit">จำนวนสาขาสูงสุด</label>
							<input type="number" class="form-control" id="branch-limit" name="branch_limit" min="0" max="999" step="1" value="<?php echo ($max > 0) ? $max : ''; ?>" placeholder="ไม่จำกัด">
						</div>
						<button type="submit" class="btn btn-primary">บันทึก</button>
					</form>
				</div>
			</div>
			<div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">เมนูของ POS ที่เปิดให้ใช้</h3>
				</div>
				<div class="panel-body">
					<p>ติ๊กออก = ปิดกลุ่มนั้น: เมนูหายไปจาก POS ทุกคน และเปิดหน้าตรง ๆ ด้วย URL ไม่ได้ · ข้อมูลเดิมยังอยู่ครบ เปิดกลับเมื่อไรก็ใช้ต่อได้</p>
					<p class="text-muted">เมนูหลัก (ขาย · เปิด–ปิดร้าน · นำเข้า · เบิก / ตัดออก · สินค้า · ประวัติ · หน้าของผู้ดูแล) ปิดไม่ได้</p>
					<form method="post" action="<?php echo $h($linkSelf); ?>">
						<input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
						<input type="hidden" name="ac" value="save">
						<table class="table table-hover table-vcenter">
							<thead>
								<tr>
									<th>เปิดใช้</th>
									<th>กลุ่มเมนู</th>
									<th>มีผลกับ</th>
									<th>สถานะตอนนี้</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($aGroups as $k => $g) {
									$isOn = !in_array($k, $aOffNow, true);
								?>
									<tr>
										<td>
											<input type="checkbox" id="fg-<?php echo $h($k); ?>" name="on[]" value="<?php echo $h($k); ?>"<?php echo ($isOn) ? ' checked' : ''; ?>>
										</td>
										<td><label for="fg-<?php echo $h($k); ?>" class="text-main text-semibold"><?php echo $h($g['label']); ?></label></td>
										<td><?php echo $h($g['hint']); ?></td>
										<td>
											<?php if ($isOn) { ?>
												<span class="text-success text-semibold">เปิด</span>
											<?php } else { ?>
												<span class="text-danger text-semibold">ปิดอยู่</span>
											<?php } ?>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
						<button type="submit" class="btn btn-primary">บันทึก</button>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
