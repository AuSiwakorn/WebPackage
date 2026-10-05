<?php
/**
 * FILE: admweb/aowebdata/modules/stock/main_status.php
 * ROLE: หน้าสถานะระบบ AOSTOCK — ติดตั้งตาราง ao_stock_* ครบหรือยัง, charset ของการเชื่อมต่อ, ลิงก์ไปหน้าติดตั้งและหน้า POS
 * DEPENDS: aModuleConfig.php ($aTablename), function.php → api.php (sdb_rows / sdb_val — admweb โหลด function.php ให้อัตโนมัติ)
 * TABLES: - (อ่านรายชื่อตารางด้วย SHOW TABLES) · ao_stock_setting, ao_stock_notify_log (สถานะการแจ้งเตือน / cron — ผ่าน api.php)
 * TODO:
 *   - [x] แสดงตารางที่ติดตั้งแล้ว / ยังขาด + ลิงก์ไปหน้า Reinstall
 *   - [x] แสดง charset ของการเชื่อมต่อ (ต้องเป็น utf8mb4)
 *   - [ ] สรุปจำนวนสาขา / พนักงาน / สินค้า เมื่อมีหน้าจัดการข้อมูล (ช่วงที่ 5)
 *   - [x] ช่วงที่ 14: บรรทัดสาขา — มีอยู่กี่สาขา / จำนวนสาขาสูงสุดที่ตั้งไว้ (ลิงก์ไปหน้าตั้งค่า POS)
 *   - [x] ช่วงที่ 9: สถานะการแจ้งเตือน — กุญแจเข้ารหัส · Telegram · อีเมลรายวัน · cron ทำงานล่าสุด + คำสั่งที่ต้องตั้ง · เมนูที่ปิดอยู่
 */
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด สถานะระบบ AOSTOCK ได้', 'redirect', 'SET');

include __DIR__ . '/aModuleConfig.php';   // ได้ $aTablename ของโมดูล stock

// SHOW TABLES / SELECT @@... ไม่มี input จากผู้ใช้ — ใช้ตัวช่วยฐานข้อมูลของ AOSTOCK (api.php)
$aAllTables = array();
foreach (sdb_rows('SHOW TABLES') as $row) {
	$aAllTables[] = (string) reset($row);
}
$charset = (string) sdb_val('SELECT @@character_set_connection');

$aStatus = array();
$numInstalled = 0;
foreach ($aTablename as $tb) {
	$isOk = in_array($tb, $aAllTables, true);
	$aStatus[$tb] = $isOk;
	$numInstalled += ($isOk) ? 1 : 0;
}
$numTotal = count($aTablename);
$isAllOk = ($numInstalled === $numTotal);

$linkInstall = _admin_buil_link('index.php?module=siteconfig&mp=db');

// ---- สาขา / จำนวนสาขาสูงสุด (ช่วงที่ 14) — ตารางยังไม่ครบ = ข้าม ----
$aBr = null;
if (in_array(_DBPREFIX_ . 'stock_branch', $aAllTables, true) && in_array(_DBPREFIX_ . 'stock_setting', $aAllTables, true)) {
	$aBr = array('all' => count(branches_all()), 'on' => count(branches_active()), 'max' => branch_limit());
}
$linkSettings = 'index.php?module=' . _MODULE_ . '&mp=settings';

// ---- การแจ้งเตือน / cron (ช่วงที่ 9) — ตารางยังไม่ครบ = ข้าม ----
$aNt = null;
if (in_array(_DBPREFIX_ . 'stock_setting', $aAllTables, true) && in_array(_DBPREFIX_ . 'stock_notify_log', $aAllTables, true)) {
	$cronAt = (string) stock_setting_get('cron_last_run', '');
	$aOffNm = array();
	foreach (features_off() as $fk) {
		$aFg = feature_groups();
		$aOffNm[] = $aFg[$fk]['label'];
	}
	$aLast = array('tg' => null, 'mail' => null);
	foreach (notify_log_rows(50) as $lg) {
		if ($aLast[$lg['channel']] === null) {
			$aLast[$lg['channel']] = $lg;
		}
	}
	$aNt = array(
		'key'      => stock_secret_ready(),
		'keyWhy'   => !defined('AOSTOCK_SECRET_KEY') ? 'ยังไม่ได้ตั้ง AOSTOCK_SECRET_KEY ในไฟล์ fix ของเว็บ' : (!function_exists('openssl_encrypt') ? 'เซิร์ฟเวอร์ไม่มี PHP openssl' : 'AOSTOCK_SECRET_KEY สั้นเกินไป (อย่างน้อย 16 ตัว)'),
		'enabled'  => notify_enabled(),
		'tgOn'     => notify_get('tg_on') === '1',
		'tgChats'  => count(tg_chats_parse(notify_get('tg_chats'))[0]),
		'mailOn'   => notify_get('mail_on') === '1',
		'mailTime' => notify_get('mail_time'),
		'mailLast' => notify_get('mail_last_sent'),
		'cronAt'   => $cronAt,
		'cronOld'  => ($cronAt === '' || strtotime($cronAt) < time() - 900),
		'cronCmd'  => '/usr/local/bin/php ' . str_replace(DIRECTORY_SEPARATOR, '/', __DIR__) . '/cron.php ' . $_SERVER['SERVER_NAME'] . ' >/dev/null 2>&1',
		'last'     => $aLast,
		'off'      => $aOffNm,
	);
}
$linkPos = rtrim(URL_WEB_ROOT, '/') . '/';
?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">AOSTOCK — สถานะระบบ</h1>
	</div>
</div>
<div id="page-content">
	<div class="row">
		<div class="col-xs-12">
			<?php displayRaiseMsg(); ?>
			<div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">ตารางฐานข้อมูล</h3>
				</div>
				<div class="panel-body">
					<p>
						ติดตั้งแล้ว
						<span class="text-semibold <?php echo ($isAllOk) ? 'text-success' : 'text-danger'; ?>"><?php echo $numInstalled; ?> / <?php echo $numTotal; ?></span>
						ตาราง
						<?php if (!$isAllOk) { ?>
							— กด <a href="<?php echo htmlspecialchars($linkInstall, ENT_QUOTES, 'UTF-8'); ?>">หน้าติดตั้งฐานข้อมูล</a> แล้วกด Re Install Database (ต้องเข้าระบบเป็น superadmin)
						<?php } ?>
					</p>
					<p>
						charset ของการเชื่อมต่อ:
						<span class="text-semibold <?php echo ($charset === 'utf8mb4') ? 'text-success' : 'text-danger'; ?>"><?php echo htmlspecialchars($charset, ENT_QUOTES, 'UTF-8'); ?></span>
						<?php if ($charset !== 'utf8mb4') { ?>
							— ควรเป็น utf8mb4 (ตั้งด้วย SET NAMES ใน sdb() ของ modules/stock/api.php)
						<?php } ?>
					</p>
					<?php if ($aBr !== null) { ?>
						<p>
							สาขา: มีอยู่
							<span class="text-semibold <?php echo ($aBr['max'] > 0 && $aBr['all'] >= $aBr['max']) ? 'text-danger' : 'text-success'; ?>"><?php echo $aBr['all']; ?><?php echo ($aBr['max'] > 0) ? ' / ' . $aBr['max'] : ''; ?></span>
							สาขา (เปิดใช้งาน <?php echo $aBr['on']; ?> · ปิดใช้งาน <?php echo $aBr['all'] - $aBr['on']; ?>)
							· จำนวนสาขาสูงสุด <?php echo htmlspecialchars(branch_limit_label($aBr['max']), ENT_QUOTES, 'UTF-8'); ?>
							— ตั้งได้ที่ <a href="<?php echo htmlspecialchars($linkSettings, ENT_QUOTES, 'UTF-8'); ?>">ตั้งค่า POS</a>
						</p>
					<?php } ?>
					<p>
						<a class="btn btn-primary" href="<?php echo htmlspecialchars($linkPos, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">เปิดหน้า AOSTOCK</a>
					</p>
					<table class="table table-hover table-vcenter">
						<thead>
							<tr>
								<th>ตาราง</th>
								<th class="text-center">สถานะ</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($aStatus as $tb => $isOk) { ?>
								<tr>
									<td><span class="text-main text-semibold"><?php echo htmlspecialchars($tb, ENT_QUOTES, 'UTF-8'); ?></span></td>
									<td class="text-center">
										<?php if ($isOk) { ?>
											<span class="text-success text-semibold">ติดตั้งแล้ว</span>
										<?php } else { ?>
											<span class="text-danger text-semibold">ยังไม่มี</span>
										<?php } ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php if ($aNt !== null) { ?>
				<div class="panel">
					<div class="panel-heading">
						<h3 class="panel-title">การแจ้งเตือนและงานตามเวลา (cron)</h3>
					</div>
					<div class="panel-body">
						<p>
							กุญแจเข้ารหัสค่าลับ (token / รหัสผ่าน SMTP):
							<?php if ($aNt['key']) { ?>
								<span class="text-success text-semibold">พร้อม</span>
							<?php } else { ?>
								<span class="text-danger text-semibold">ยังไม่พร้อม</span> — <?php echo htmlspecialchars($aNt['keyWhy'], ENT_QUOTES, 'UTF-8'); ?>
							<?php } ?>
						</p>
						<?php if (!$aNt['enabled']) { ?>
							<p class="text-danger text-semibold">การแจ้งเตือนถูกปิดอยู่ที่หน้า "ตั้งค่า POS" — ไม่ส่งทั้ง Telegram และอีเมล</p>
						<?php } ?>
						<p>
							Telegram:
							<span class="text-semibold <?php echo ($aNt['tgOn']) ? 'text-success' : 'text-muted'; ?>"><?php echo ($aNt['tgOn']) ? 'เปิด' : 'ปิด'; ?></span>
							· <?php echo (int) $aNt['tgChats']; ?> แชต
							<?php if ($aNt['last']['tg'] !== null) { ?>
								· ส่งล่าสุด <?php echo date('d/m/Y H:i', $aNt['last']['tg']['ts']); ?>
								<?php echo ($aNt['last']['tg']['ok']) ? '<span class="text-success">สำเร็จ</span>' : '<span class="text-danger">ไม่สำเร็จ — ' . htmlspecialchars($aNt['last']['tg']['error'], ENT_QUOTES, 'UTF-8') . '</span>'; ?>
							<?php } ?>
						</p>
						<p>
							อีเมลสรุปยอดขายรายวัน:
							<span class="text-semibold <?php echo ($aNt['mailOn']) ? 'text-success' : 'text-muted'; ?>"><?php echo ($aNt['mailOn']) ? 'เปิด · ส่งเวลา ' . htmlspecialchars($aNt['mailTime'], ENT_QUOTES, 'UTF-8') . ' น.' : 'ปิด'; ?></span>
							<?php if ($aNt['mailLast'] !== '') { ?>
								· ส่งของวันที่ <?php echo htmlspecialchars($aNt['mailLast'], ENT_QUOTES, 'UTF-8'); ?> แล้ว
							<?php } ?>
							<?php if ($aNt['last']['mail'] !== null && !$aNt['last']['mail']['ok']) { ?>
								· <span class="text-danger">ครั้งล่าสุดไม่สำเร็จ — <?php echo htmlspecialchars($aNt['last']['mail']['error'], ENT_QUOTES, 'UTF-8'); ?></span>
							<?php } ?>
						</p>
						<p>
							cron ทำงานล่าสุด:
							<?php if ($aNt['cronAt'] === '') { ?>
								<span class="text-danger text-semibold">ยังไม่เคยทำงาน</span>
							<?php } else { ?>
								<span class="text-semibold <?php echo ($aNt['cronOld']) ? 'text-danger' : 'text-success'; ?>"><?php echo htmlspecialchars($aNt['cronAt'], ENT_QUOTES, 'UTF-8'); ?></span>
							<?php } ?>
							<?php if ($aNt['cronOld']) { ?>
								— อีเมลรายวันต้องใช้ cron ตั้งที่ DirectAdmin → Cron Jobs ให้รันทุก 5 นาที (Minute ใส่ */5 ช่องอื่นใส่ *) ด้วยคำสั่ง:
							<?php } else { ?>
								— คำสั่งที่ตั้งไว้ใน Cron Jobs:
							<?php } ?>
						</p>
						<pre><?php echo htmlspecialchars($aNt['cronCmd'], ENT_QUOTES, 'UTF-8'); ?></pre>
						<p class="text-muted">path ของ php บนโฮสต์อาจต่างจากนี้ (ดูได้จากหน้า Cron Jobs ของโฮสต์) · ตั้งค่า Telegram / อีเมล ที่หน้า "ตั้งค่าการแจ้งเตือน" ใน POS ของผู้ดูแล</p>
						<p>
							เมนู POS ที่ปิดอยู่:
							<?php echo ($aNt['off']) ? '<span class="text-danger">' . htmlspecialchars(implode(' · ', $aNt['off']), ENT_QUOTES, 'UTF-8') . '</span>' : '<span class="text-success">ไม่มี (เปิดครบ)</span>'; ?>
						</p>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
</div>
