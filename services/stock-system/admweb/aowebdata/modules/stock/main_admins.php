<?php
/**
 * FILE: admweb/aowebdata/modules/stock/main_admins.php
 * ROLE: หลังบ้าน admweb — จัดการผู้ดูแล / ฝ่ายบัญชีของ POS (AOSTOCK): เพิ่ม · ตั้งรหัสผ่านใหม่ · พักงาน / เปิดใช้งาน
 * DEPENDS: function.php → api.php (users_all, staff_create, staff_set_password, staff_set_active, csrf_token, csrf_check)
 * TABLES: ao_stock_staff, ao_stock_staff_branch, ao_stock_branch
 * TODO:
 *   - [x] เพิ่มผู้ดูแล / ฝ่ายบัญชี (รหัสผ่านเก็บเป็น password_hash)
 *   - [x] ตั้งรหัสผ่านใหม่ (ปลดล็อกด้วย) · พักงาน / เปิดใช้งาน
 *   - [ ] ลบบัญชีที่ยังไม่เคยทำรายการ (ยังไม่จำเป็น — ใช้พักงานแทน)
 *
 * พนักงานหน้าร้าน (PIN) จัดการที่หน้า "จัดการพนักงาน" ของ POS — หน้านี้เฉพาะ role admin / account
 * ชื่อช่องในฟอร์มขึ้นต้น pos_ — fix.req.php ของ admweb แก้ค่าช่องชื่อ username / password (ตัด ' และ =)
 */
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด ผู้ดูแล POS ได้', 'redirect', 'SET');

$linkSelf  = 'index.php?module=' . _MODULE_ . '&mp=' . _MP_;
$aRoleName = array('admin' => 'ผู้ดูแล POS', 'account' => 'ฝ่ายบัญชี');
$minPass   = 8;

$ac = REQ_get('ac', 'post', 'str', '');
if ($ac !== '') {
	$token = isset($_POST['csrf']) ? $_POST['csrf'] : null;
	$uname = strtolower(trim(isset($_POST['pos_user']) ? (string) $_POST['pos_user'] : ''));
	$pass  = isset($_POST['pos_pass']) ? (string) $_POST['pos_pass'] : '';
	$pass2 = isset($_POST['pos_pass2']) ? (string) $_POST['pos_pass2'] : '';
	$all   = users_all();
	$isPos = isset($all[$uname]) && isset($aRoleName[$all[$uname]['role']]);
	$err   = '';

	if (!csrf_check($token)) {
		$err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
	} elseif ($ac === 'add') {
		$name = trim(preg_replace('/\s+/u', ' ', isset($_POST['pos_name']) ? (string) $_POST['pos_name'] : ''));
		$role = isset($_POST['pos_role']) ? (string) $_POST['pos_role'] : '';
		if (!preg_match('/^[a-z0-9_]{3,20}$/', $uname)) {
			$err = 'ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษพิมพ์เล็ก ตัวเลข หรือ _ ยาว 3–20 ตัว';
		} elseif (isset($all[$uname])) {
			$err = 'ชื่อผู้ใช้ ' . $uname . ' มีคนใช้แล้ว (รวมพนักงานหน้าร้าน)';
		} elseif ($name === '') {
			$err = 'กรุณากรอกชื่อที่แสดง';
		} elseif (!isset($aRoleName[$role])) {
			$err = 'กรุณาเลือกบทบาท';
		} elseif (mb_strlen($pass, 'UTF-8') < $minPass) {
			$err = 'รหัสผ่านต้องยาวอย่างน้อย ' . $minPass . ' ตัว';
		} elseif ($pass !== $pass2) {
			$err = 'ยืนยันรหัสผ่านไม่ตรงกัน';
		} else {
			$br = array_keys(branches_active());
			staff_create(array(
				'username' => $uname, 'name' => $name, 'initials' => '', 'role' => $role,
				'branch' => $br ? $br[0] : '', 'perms' => array(), 'password' => $pass,
			));
			setRaiseMsg('เพิ่ม ' . $aRoleName[$role] . ' ' . $name . ' (' . $uname . ') แล้ว — เข้าระบบที่หน้า AOSTOCK แท็บผู้ดูแล', _TIME_, 0);
		}
	} elseif (!$isPos) {
		$err = 'ไม่พบบัญชีผู้ดูแล / ฝ่ายบัญชีนี้';
	} elseif ($ac === 'password') {
		if (mb_strlen($pass, 'UTF-8') < $minPass) {
			$err = 'รหัสผ่านต้องยาวอย่างน้อย ' . $minPass . ' ตัว';
		} elseif ($pass !== $pass2) {
			$err = 'ยืนยันรหัสผ่านไม่ตรงกัน';
		} else {
			staff_set_password($uname, $pass);
			setRaiseMsg('ตั้งรหัสผ่านใหม่ของ ' . $all[$uname]['name'] . ' แล้ว (ปลดล็อกด้วย)', _TIME_, 0);
		}
	} elseif ($ac === 'off' || $ac === 'on') {
		staff_set_active($uname, $ac === 'on');
		setRaiseMsg(($ac === 'on' ? 'เปิดใช้งาน ' : 'พักงาน ') . $all[$uname]['name'] . ' แล้ว', _TIME_, 0);
	} else {
		$err = 'ไม่รู้จักคำสั่งนี้';
	}

	if ($err !== '') {
		setRaiseMsg($err, _TIME_, 1);
	}
	CustomRedirectToUrl($linkSelf);
	exit;
}

$aAdmins = array();
foreach (users_all() as $k => $u) {
	if (isset($aRoleName[$u['role']])) {
		$aAdmins[$k] = $u;
	}
}
$aAuth  = staff_db_rows();
$csrf   = csrf_token();
$h      = function ($v) {
	return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">AOSTOCK — ผู้ดูแล POS</h1>
	</div>
</div>
<div id="page-content">
	<div class="row">
		<div class="col-xs-12">
			<?php displayRaiseMsg(); ?>
			<div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">ผู้ดูแลและฝ่ายบัญชีของ POS</h3>
				</div>
				<div class="panel-body">
					<p>เข้าระบบ POS ที่แท็บ "ผู้ดูแล" ด้วยชื่อผู้ใช้ + รหัสผ่าน · พนักงานหน้าร้าน (PIN) จัดการที่หน้า "จัดการพนักงาน" ของ POS</p>
					<?php if (!$aAdmins) { ?>
						<p class="text-danger text-semibold">ยังไม่มีผู้ดูแล POS — เพิ่มคนแรกด้านล่าง แล้วเข้าระบบ POS เพื่อเพิ่มสาขา / พนักงาน / สินค้า</p>
					<?php } else { ?>
						<table class="table table-hover table-vcenter">
							<thead>
								<tr>
									<th>ชื่อผู้ใช้</th>
									<th>ชื่อ</th>
									<th>บทบาท</th>
									<th>สถานะ</th>
									<th>เข้าระบบล่าสุด</th>
									<th>ตั้งรหัสผ่านใหม่</th>
									<th></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($aAdmins as $k => $u) {
									$a      = isset($aAuth[$k]) ? $aAuth[$k] : array();
									$locked = !empty($a['locked_until']) && strtotime($a['locked_until']) > time();
								?>
									<tr>
										<td><span class="text-main text-semibold"><?php echo $h($k); ?></span></td>
										<td><?php echo $h($u['name']); ?></td>
										<td><?php echo $h($aRoleName[$u['role']]); ?></td>
										<td>
											<?php if (!$u['active']) { ?>
												<span class="text-muted">พักงาน</span>
											<?php } elseif ($locked) { ?>
												<span class="text-danger">ล็อก (กรอกผิดหลายครั้ง)</span>
											<?php } else { ?>
												<span class="text-success">ใช้งาน</span>
											<?php } ?>
										</td>
										<td><?php echo !empty($a['last_login']) ? $h($a['last_login']) : '—'; ?></td>
										<td>
											<form method="post" action="<?php echo $h($linkSelf); ?>" class="form-inline" autocomplete="off">
												<input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
												<input type="hidden" name="ac" value="password">
												<input type="hidden" name="pos_user" value="<?php echo $h($k); ?>">
												<input type="password" name="pos_pass" class="form-control input-sm" placeholder="รหัสผ่านใหม่" autocomplete="new-password">
												<input type="password" name="pos_pass2" class="form-control input-sm" placeholder="ยืนยัน" autocomplete="new-password">
												<button type="submit" class="btn btn-default btn-sm">บันทึก</button>
											</form>
										</td>
										<td class="text-right">
											<form method="post" action="<?php echo $h($linkSelf); ?>">
												<input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
												<input type="hidden" name="ac" value="<?php echo $u['active'] ? 'off' : 'on'; ?>">
												<input type="hidden" name="pos_user" value="<?php echo $h($k); ?>">
												<button type="submit" class="btn btn-sm <?php echo $u['active'] ? 'btn-danger' : 'btn-success'; ?>"><?php echo $u['active'] ? 'พักงาน' : 'เปิดใช้งาน'; ?></button>
											</form>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					<?php } ?>
				</div>
			</div>

			<div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">เพิ่มผู้ดูแล / ฝ่ายบัญชี</h3>
				</div>
				<div class="panel-body">
					<form method="post" action="<?php echo $h($linkSelf); ?>" autocomplete="off">
						<input type="hidden" name="csrf" value="<?php echo $h($csrf); ?>">
						<input type="hidden" name="ac" value="add">
						<div class="row">
							<div class="col-sm-3 form-group">
								<label>ชื่อผู้ใช้ (a-z 0-9 _)</label>
								<input type="text" name="pos_user" class="form-control" maxlength="20" placeholder="เช่น owner" autocapitalize="none" spellcheck="false">
							</div>
							<div class="col-sm-3 form-group">
								<label>ชื่อที่แสดง</label>
								<input type="text" name="pos_name" class="form-control" maxlength="60" placeholder="เช่น สมชาย ใจดี">
							</div>
							<div class="col-sm-2 form-group">
								<label>บทบาท</label>
								<select name="pos_role" class="form-control">
									<?php foreach ($aRoleName as $rk => $rn) { ?>
										<option value="<?php echo $h($rk); ?>"><?php echo $h($rn); ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="col-sm-2 form-group">
								<label>รหัสผ่าน (อย่างน้อย <?php echo (int) $minPass; ?> ตัว)</label>
								<input type="password" name="pos_pass" class="form-control" autocomplete="new-password">
							</div>
							<div class="col-sm-2 form-group">
								<label>ยืนยันรหัสผ่าน</label>
								<input type="password" name="pos_pass2" class="form-control" autocomplete="new-password">
							</div>
						</div>
						<button type="submit" class="btn btn-primary">เพิ่ม</button>
					</form>
					<p class="text-muted">ผู้ดูแล POS: ดูทุกสาขา จัดการสาขา / พนักงาน / สินค้า / รายงาน (ไม่ขายหน้าร้าน) · ฝ่ายบัญชี: ดูบิลขายและเงินเข้าทุกสาขา + ตั้งเลขที่บิล</p>
				</div>
			</div>
		</div>
	</div>
</div>
