<?php
/**
 * FILE: admweb/aowebdata/modules/stock/main_settings.php
 * ROLE: หลังบ้าน admweb — เปิด / ปิดเมนูของ POS (AOSTOCK) เป็นกลุ่มฟีเจอร์: รับคืน · ตรวจนับ · หมวดสินค้า · ประวัติเคลื่อนไหว · รายงานพนักงาน · ฝ่ายบัญชี · การแจ้งเตือน
 * DEPENDS: function.php → api.php (feature_groups, features_off, features_off_set, log_add, csrf_token, csrf_check)
 * TABLES: ao_stock_setting (key features_off), ao_stock_log (ประวัติชนิด setting ของระบบ)
 * TODO:
 *   - [x] ช่วงที่ 9: สวิตช์ 7 กลุ่ม — ปิดแล้วซ่อนเมนูใน POS + เปิด URL ตรงไม่ได้ (require_login) · ปิดฝ่ายบัญชี = บัญชีบทบาทนี้เข้าระบบไม่ได้
 *
 * เมนูหลักของ POS (ขาย เปิด–ปิดร้าน รับเข้า เบิก สินค้า ประวัติ หน้าของผู้ดูแล) ปิดไม่ได้ — แก้รายการกลุ่มที่ feature_groups() ใน api.php
 */
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด เปิด–ปิดเมนู AOSTOCK ได้', 'redirect', 'SET');

$linkSelf = 'index.php?module=' . _MODULE_ . '&mp=' . _MP_;
$aGroups  = feature_groups();

$ac = REQ_get('ac', 'post', 'str', '');
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
			$oLogin = login_logout::getLoginData();
			$aDetail['แก้โดย'] = (is_object($oLogin) && isset($oLogin->user)) ? $oLogin->user . ' (หลังบ้าน admweb)' : 'หลังบ้าน admweb';
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
$h       = function ($v) {
	return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">AOSTOCK — เปิด–ปิดเมนู</h1>
	</div>
</div>
<div id="page-content">
	<div class="row">
		<div class="col-xs-12">
			<?php displayRaiseMsg(); ?>
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
