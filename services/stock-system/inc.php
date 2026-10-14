<?php
/**
 * FILE: inc.php
 * ROLE: bootstrap ฝั่งเว็บ — โหลด admweb (mainApi), ภาษา, ค่าคงที่ของธีม และรายชื่อหน้าที่ router เปิดได้
 * DEPENDS: admweb/mainApi.php, admweb/api/oApi.php
 * TABLES: - (SEORedir301 อ่านตารางของโมดูล seo)
 * TODO:
 *   - [x] ตั้ง cookie ของ session (HttpOnly / SameSite / Secure) ก่อน mainApi.php เริ่ม session
 *   - [x] THEME = aostock (ระบบ AOSTOCK ย้ายจาก demo/ เข้า themes/aostock/)
 *   - [x] รวมรายชื่อหน้า aConfigPages (ไม่ลง sitemap) เข้ากับ aConfigSitemap
 *   - [x] 14 ต.ค. ตัววัดเวลาฝั่งเซิร์ฟเวอร์ (หาสาเหตุเดโมค้างนานเป็นบางครั้ง): เริ่ม session ที่นี่เองเพื่อจับเวลา
 *         ส่งหัว Server-Timing / X-AO-Diag เฉพาะผู้ที่ล็อกอินแล้วและมี cookie aodiag (ตั้งเองตอนทดสอบ)
 *   - [ ] ได้ผลวัดแล้ว ตัดสินใจว่าจะเก็บตัววัดไว้หรือเอาออก
 *   - [x] 14 ต.ค. (ผู้ใช้เลือก): session ไม่หลุดระหว่างวัน — cookie ชื่อ AOSTOCKSID · เก็บในโฟลเดอร์ของตัวเอง (Stock_SessionStore)
 *         ไม่ได้ใช้เกิน 12 ชั่วโมงถึงหลุด · ปิดเบราว์เซอร์แล้วหลุด (เดิมโฮสต์ลบทิ้งเมื่อไม่ได้ใช้ 24 นาที)
 */

/* ตัววัดเวลา — จุดเริ่มของสคริปต์ (ใช้คู่กับ $_SERVER['REQUEST_TIME_FLOAT'] และหัว X-AO-Diag ด้านล่าง) */
$aoDiagT0 = microtime(true);

define('AOSTOCK_SESS_IDLE', 12 * 3600);      // ไม่ได้ใช้นานเท่านี้ (วินาที) ถึงหลุด

/** เก็บ session ในโฟลเดอร์ของ AOSTOCK เอง — การเก็บกวาดของโฮสต์ / เว็บอื่น (24 นาที) ไม่ลบของเรา · ล็อกไฟล์แบบเดิมของ PHP */
class Stock_SessionStore extends SessionHandler
{
	private $dir;

	public function __construct($dir)
	{
		$this->dir = $dir;
	}

	public function open(string $path, string $name): bool
	{
		return parent::open($this->dir, $name);
	}

	public function gc(int $max_lifetime): int|false
	{
		return parent::gc(AOSTOCK_SESS_IDLE);
	}
}

// cookie ของ session — mainApi.php เรียก session_start() เองโดยไม่ตั้งค่า จึงต้องตั้งไว้ก่อน include
// เริ่ม session ที่นี่เองเพื่อจับเวลารอล็อก / เก็บกวาด session (mainApi.php เรียก @session_start() ซ้ำแล้วไม่มีผล)
$aoSessOwn = false;
if (session_status() === PHP_SESSION_NONE) {
	$aoSessDir = (string) ini_get('session.save_path');
	$aoSessDir = ($aoSessDir !== '') ? substr($aoSessDir, strrpos(';' . $aoSessDir, ';')) : sys_get_temp_dir();
	$aoSessDir = rtrim($aoSessDir, '/\\') . '/aostock-' . substr(md5(__DIR__), 0, 8);
	if ((is_dir($aoSessDir) || @mkdir($aoSessDir, 0700, true)) && is_writable($aoSessDir)) {
		session_set_save_handler(new Stock_SessionStore($aoSessDir), true);
		$aoSessOwn = true;
	}
	session_name('AOSTOCKSID');
	session_set_cookie_params([
		'path'     => rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/',
		'httponly' => true,
		'samesite' => 'Lax',
		'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
	]);
	@session_start();
}
$aoDiagT1 = microtime(true);

include 'admweb/mainApi.php';
include 'admweb/api/oApi.php';
$aoDiagT2 = microtime(true);

/* ส่งเวลาแต่ละส่วนกลับในหัวคำตอบ — เฉพาะผู้ที่ล็อกอินแล้วและมี cookie aodiag · ผู้ใช้ทั่วไปไม่มีอะไรเพิ่ม
   ao-start = PHP รับคำขอ → เริ่มสคริปต์ · ao-sess = เปิด session · ao-boot = โหลด admweb + ต่อฐานข้อมูล · ao-page = ทำหน้า
   X-AO-Diag: at = เวลาเครื่องเซิร์ฟเวอร์ตอนเริ่มสคริปต์ (ms) ใช้เทียบว่าคำขอรอคิวก่อนถึง PHP ไหม · ค่าตั้ง session / opcache
   ob_start() พักผลลัพธ์ไว้ จึงใส่หัวตอนจบ request ได้
   TODO:
     - [x] 14 ต.ค. ตัววัดชั่วคราว (closure ใน register_shutdown_function ไม่ได้ตั้งชื่อ function ใหม่) */
if (isset($_COOKIE['aodiag']) && function_exists('current_user') && current_user() !== null) {
	ob_start();
	register_shutdown_function(function () use ($aoDiagT0, $aoDiagT1, $aoDiagT2, $aoSessOwn) {
		if (headers_sent()) {
			return;
		}
		$ms = function ($a, $b) {
			return round(($b - $a) * 1000, 1);
		};
		$rt = isset($_SERVER['REQUEST_TIME_FLOAT']) ? (float) $_SERVER['REQUEST_TIME_FLOAT'] : $aoDiagT0;
		$sp = (string) ini_get('session.save_path');
		$sp = ($sp === '') ? 'default' : ((strpos($sp, '/tmp') === 0) ? 'tmp' : ((strpos($sp, '/home/') === 0) ? 'home' : 'other'));
		header('Server-Timing: ao-start;dur=' . $ms($rt, $aoDiagT0) . ', ao-sess;dur=' . $ms($aoDiagT0, $aoDiagT1)
			. ', ao-boot;dur=' . $ms($aoDiagT1, $aoDiagT2) . ', ao-page;dur=' . $ms($aoDiagT2, microtime(true)));
		header('X-AO-Diag: at=' . round($aoDiagT0 * 1000) . '; gc=' . (int) ini_get('session.gc_probability') . '/' . (int) ini_get('session.gc_divisor')
			. '; life=' . (int) ini_get('session.gc_maxlifetime') . '; save=' . ini_get('session.save_handler') . ':' . $sp . '; store=' . ($aoSessOwn ? 'own' : 'default')
			. '; sapi=' . PHP_SAPI . '; opc=' . (int) ini_get('opcache.enable') . '; php=' . PHP_VERSION
			. '; mem=' . round(memory_get_peak_usage() / 1048576, 1));
	});
}

#SEO REDIRECT 301
SEORedir301();

if (@$_GET['lang'] != '' && in_array($_GET['lang'], array('en', 'th'))) {
	$lang = $_REQUEST['lang'];
	$oApi->setLanguage($lang);
}

define("_LANG_", ($lang) ? $lang : DEFAULT_LANGEUAGE);

////////////////////////////////////////////
///////////// THEME (frontend) /////////////
////////////////////////////////////////////
// ย้าย frontend (หน้า + include + assets) เข้า themes/<THEME>/ แล้วอ้างผ่าน constant เหล่านี้
// - THEME_DIR : path ระบบไฟล์ ใช้กับ include/require    (เช่น include THEME_DIR.'/include/header.php')
/* - THEME_URL : URL เว็บ (absolute) ใช้กับ asset src/href (เช่น <img src="<?=THEME_URL?>/assets/logo.webp">)*/
define('THEME', 'aostock');
define('THEME_DIR', __DIR__ . '/themes/' . THEME);
define('THEME_URL', rtrim(URL_WEB_ROOT, '/') . '/themes/' . THEME);

////////////////////////////////////////////
///////////// fix test request /////////////
////////////////////////////////////////////

$aosoftwebsiteconfig = [
    'defaultLang' => $lang,
    'supportedLangs' => $langConf, // ถ้าโปรเจ็คไหนมีภาษาเดียวก็เปลี่ยนเป็น ['th'] ได้เลย
    'baseUrl' => '/',
    // หน้าที่ router เปิดได้ = หน้าใน sitemap + หน้าที่ไม่ลง sitemap (aConfigPages เช่นหน้าหลัง login ของ AOSTOCK)
    'pages' => array_merge(
        $aConfig['aConfigSitemap'],
        (isset($aConfig['aConfigPages']) && is_array($aConfig['aConfigPages'])) ? $aConfig['aConfigPages'] : []
    )
];

////////////////////////////////////////////

function sharedSocial($url, $type = 'href', $socialname = 'fb')
{
	if ($type == 'href') {
		$u = ($socialname == 'fb')
			? 'https://www.facebook.com/sharer.php?u=' . URL_WEB_ROOT . '/' . $url
			: 'https://twitter.com/intent/tweet?text=' . $url;
	} else {
		$u = "window.open(this.href, 'mywin','left=50,top=50,width=600,height=350,toolbar=0'); return false;";
	}
	return $u;
}

