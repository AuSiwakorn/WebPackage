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
 */

// cookie ของ session — mainApi.php เรียก session_start() เองโดยไม่ตั้งค่า จึงต้องตั้งไว้ก่อน include
if (session_status() === PHP_SESSION_NONE) {
	session_set_cookie_params([
		'httponly' => true,
		'samesite' => 'Lax',
		'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
	]);
}

include 'admweb/mainApi.php';
include 'admweb/api/oApi.php';

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

