<?php
/**
 * FILE: index.php
 * ROLE: front controller ฝั่งเว็บ — route URL เข้าหน้าใน themes/<THEME>/ (ไม่มีหน้า = 404)
 * DEPENDS: inc.php, admbuilder/config.php, admbuilder/autoload.php
 * TABLES: - (MemberOnline บันทึกสถิติผู้เข้าชม)
 * TODO:
 *   - [x] รองรับการวางในโฟลเดอร์ย่อย — ตัด base path จาก URL_WEB_ROOT ก่อน route
 *   - [x] ไม่มีธีม / หาหน้าไม่เจอ ตอบ 404 (ลบธีมตัวอย่าง mgroup ออกแล้ว)
 *   - [x] ส่งชื่อไฟล์หน้าให้หน้าใน theme ผ่าน $_getdata['page'] (AOSTOCK ใช้ตรวจสิทธิ์)
 */
include 'inc.php';
define('ALLOW_DIRECT_ACCESS', true);

// โหลด engine ส่วนกลาง (AO Builder) ให้ทุกหน้าก่อน include หน้าใน theme — autoload เป็น include_once กันโหลดซ้ำ
include_once 'admbuilder/config.php';
include_once 'admbuilder/autoload.php';

$_getdata = [];
$pagesDir = THEME_DIR . '/'; // หน้าเว็บย้ายเข้า themes/<THEME>/ แล้ว (เดิม __DIR__.'/')
$defaultLang = $lang;
$supportedLangs = ['th', 'en'];

// ดึง path จาก URL
// 1. ดึง URI มา เช่น /en/article/how-to-grow?foo=bar
$uri = $_SERVER['REQUEST_URI'];

// 2. ตัด query string ทิ้ง (ถ้ามี)
$uri = (string) parse_url($uri, PHP_URL_PATH);

// 2.5 ตัดโฟลเดอร์ที่ติดตั้งเว็บ (เช่น /aostock) ออก — ค่ามาจาก $webBase ใน fix.<โดเมน>.php ผ่าน URL_WEB_ROOT
$basePath = rtrim((string) parse_url(URL_WEB_ROOT, PHP_URL_PATH), '/');
if ($basePath !== '' && ($uri === $basePath || strpos($uri, $basePath . '/') === 0)) {
    $uri = (string) substr($uri, strlen($basePath));
}

// 3. ตัด / ออกข้างหน้าและข้างหลัง
$route = trim($uri, '/');

// 3.5 รองรับลิงก์แบบ xxx.php (หน้าของ AOSTOCK ลิงก์ด้วย .php ทั้งหมด) — ตัด .php ท้าย segment ออก
// ต้องทำก่อนข้อ 4 เพราะข้อ 4 ลบ "." ทิ้ง ทำให้ login.php กลายเป็น loginphp (ข้อ 6.5 เดิมจึงไม่เคยทำงาน)
$route = preg_replace('#\.php(?=/|$)#i', '', $route);

// 4. ล้างค่า เพื่อความปลอดภัย
$route = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $route);

// 5. แยกเป็น array
$parts = explode('/', $route);

//เพิ่มจุดนี้: ป้องกัน path traversal เช่น /../../config
foreach ($parts as $part) {
    if (strpos($part, '..') !== false) {
        http_response_code(400);
        exit('400 Bad Request');
    }
}

// 6. ตรวจสอบภาษา
$lang = $defaultLang;
if (in_array($parts[0], $supportedLangs)) {
    $lang = array_shift($parts);
}

// 6.5 รองรับลิงก์เดิมแบบ xxx.php — ตัด .php ท้าย segment แรกออกก่อน match whitelist
// (หน้าเว็บถูกย้ายเข้า themes/<THEME>/ แล้ว Apache จึงไม่เสิร์ฟ /xxx.php ตรงอีก ต้องวิ่งผ่าน router)
if (!empty($parts[0]) && strtolower(substr($parts[0], -4)) === '.php') {
    $parts[0] = substr($parts[0], 0, -4);
}

// 7. Routing Logic
if (empty($route) || $route === '' || (count($parts) === 1 && $parts[0] === '')) {
    if (is_file($pagesDir . 'home.php')) {
        $_getdata['page'] = 'home.php';
        MemberOnline('home.php');
        include $pagesDir . 'home.php';
        exit;
    }
    http_response_code(404);
    exit('404 Not Found');
}

if (!empty($parts[0]) && in_array($parts[0], $aosoftwebsiteconfig['pages'], true) || isset($aosoftwebsiteconfig['pages'][$parts[0]])) {
    $filename = basename($parts[0]);
    $file = rtrim($pagesDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename . '.php';
    $realPagesDir = realpath($pagesDir);
    $realFilePath = realpath($file);
    if ($realPagesDir && $realFilePath && strpos($realFilePath, $realPagesDir) === 0 && file_exists($realFilePath)) {
        if (isset($parts[1]) && $parts[1] !== '' && ctype_digit($parts[1])) {
            $_getdata['id'] = $parts[1];
        } else {
            $_getdata['slug'] = $parts[1] ?? '';
        }

        $_getdata['key2'] = $parts[2] ?? '';
        $_getdata['key3'] = $parts[3] ?? '';
        $_getdata['lang'] = $lang ?? DEFAULT_LANGEUAGE;
        $_getdata['page'] = $filename . '.php';   // ชื่อไฟล์หน้า — หน้าใน theme ใช้แทน SCRIPT_NAME (ซึ่งเป็น index.php เสมอ)

        MemberOnline($filename . '.php');
        include $realFilePath;
        exit;
    }
} else {
    if ($uri === '/index.php' && is_file($pagesDir . 'home.php')) {
        $_getdata['page'] = 'home.php';
        MemberOnline('home.php');
        include $pagesDir . 'home.php';
        exit;
    }
}

http_response_code(404);
exit('404 Not Found');
