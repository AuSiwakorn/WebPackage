<?php
/**
 * FILE: admweb/aowebdata/modules/stock/cron.php
 * ROLE: งานตามเวลาของ AOSTOCK — ส่งอีเมลสรุปยอดขายรายวัน · รันจาก Cron Jobs ของโฮสต์ทุก 5 นาที (command line เท่านั้น เปิดจากเว็บไม่ได้)
 * DEPENDS: admweb/mainApi.php (ค่าตั้งจาก fix.<โดเมน>.php + api.php ของโมดูล stock) → notify_cron_run()
 * TABLES: ao_stock_setting, ao_stock_notify_log, ao_stock_sale, ao_stock_return, ao_stock_store_day (ผ่าน api.php)
 * TODO:
 *   - [x] ช่วงที่ 9: CLI เท่านั้น · รับชื่อโดเมนเป็น argument เพื่อเลือกไฟล์ fix.<โดเมน>.php
 *
 * ตั้งใน DirectAdmin → Advanced Features → Cron Jobs (ทุก 5 นาที · แก้ path ให้ตรงกับบัญชีโฮสต์):
 *   *\/5 * * * *   /usr/local/bin/php /home/<ผู้ใช้>/domains/web-siam.com/public_html/aostock/admweb/aowebdata/modules/stock/cron.php web-siam.com >/dev/null 2>&1
 *   (ต่อท้าย >/dev/null 2>&1 กันโฮสต์ส่งอีเมลผลลัพธ์ทุก 5 นาที)
 * ทดสอบเอง: php cron.php web-siam.com → พิมพ์เวลา + สิ่งที่ทำ (เช่น "ยังไม่ถึงเวลาส่ง (21:00 น.)")
 * หน้า "AOSTOCK — สถานะระบบ" ในหลังบ้านบอกเวลาที่ cron ทำงานล่าสุด
 */
if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	exit('403 Forbidden');
}

$host = isset($argv[1]) ? preg_replace('/[^a-z0-9.-]/', '', strtolower((string) $argv[1])) : '';
if ($host === '' || !is_file(dirname(__DIR__, 2) . '/fix.' . $host . '.php')) {
	fwrite(STDERR, "ใช้: php cron.php <โดเมน>  (ต้องมีไฟล์ admweb/aowebdata/fix.<โดเมน>.php)\n");
	exit(2);
}

// admweb เลือกไฟล์ fix จาก SERVER_NAME (include/conf.ini.php) — command line ไม่มีค่าเหล่านี้ ต้องตั้งเอง
$_SERVER['SERVER_NAME']     = $host;
$_SERVER['HTTP_HOST']       = $host;
$_SERVER['REQUEST_URI']     = '/aostock-cron';
$_SERVER['SCRIPT_NAME']     = '/aostock-cron';
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'AOSTOCK-cron';

ob_start();
require dirname(__DIR__, 3) . '/mainApi.php';
ob_end_clean();

if (!function_exists('notify_cron_run')) {
	fwrite(STDERR, "ไม่พบโมดูล stock — ตรวจ \$aModuleUse ในไฟล์ fix.{$host}.php\n");
	exit(1);
}
try {
	echo date('Y-m-d H:i:s') . ' ' . notify_cron_run() . "\n";
} catch (Throwable $e) {
	error_log('[AOSTOCK] cron: ' . $e->getMessage());
	fwrite(STDERR, date('Y-m-d H:i:s') . ' ผิดพลาด: ' . $e->getMessage() . "\n");
	exit(1);
}
