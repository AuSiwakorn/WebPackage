<?php
/**
 * FILE: admweb/aowebdata/fix.localhost.php
 * ROLE: ค่าตั้งของเครื่องทดสอบ http://localhost:8098/aostock/ — ⚠ ไม่ต้องอัปโหลดขึ้นเซิร์ฟเวอร์ · conf.ini.php เลือกไฟล์นี้เมื่อเปิดผ่าน localhost
 * DEPENDS: - (ถูก include โดย admweb/include/conf.ini.php)
 * TABLES: -
 * TODO:
 *   - [x] เปลี่ยนชื่อจาก fix.admweb-8.0.web-siam.com.php + ใช้ฐานข้อมูล websiam_stock
 *   - [x] รองรับการวางในโฟลเดอร์ย่อย ($webBase)
 *   - [x] aConfigPages = หน้าของ AOSTOCK (themes/aostock) ที่ router เปิดได้ โดยไม่ลง sitemap
 *   - [x] $aModuleUse: เพิ่ม stock · เอาชื่อโมดูลที่ไม่มีโฟลเดอร์ออก (ir, menu, billing, chat, calendar, example, html)
 *   - [x] ช่วงที่ 9: AOSTOCK_SECRET_KEY กุญแจเข้ารหัสค่าลับของการแจ้งเตือน (สุ่มแยกต่อเว็บ)
 *   - [x] คัดลอกจาก fix.web-siam.com.php — ต่างกันแค่ฐานข้อมูล, DOMAIN_NAME, ADMIN_INSTALL_PASSWORD
 *   - [ ] แก้ fix.web-siam.com.php เมื่อไร (เช่นเพิ่มหน้าใน aConfigPages / โมดูลใน $aModuleUse) ต้องแก้ไฟล์นี้ให้ตรงกันด้วย
 */
date_default_timezone_set("Asia/Bangkok");
setlocale(LC_ALL, 'en_EN.UTF8');


# database setting
$db_host         = '127.0.0.1;port=3399';   // MariaDB ของ XAMPP ที่เปิดเฉพาะตอนทดสอบ (port แยกจาก 3306)
$db_name         = 'aostock_local';
$db_user         = 'root';
$db_passwd       = '';   // root ของ MariaDB ทดสอบไม่มีรหัสผ่าน

# ให้กำหนดเป็น on เมื่อติดตั้ง admweb แล้วหากยังไม่ได้ติดตั้งต้องกำหนด off เท่านั้น
define("_IS_COOKIE_LOGIN_TIME_", 'off');
define("DATABASE_INC", 'php_v8'); // ใช้ได้เฉพาะ (php_v8, php_v7)

// ถ้าติดตั้งไม่ได้ให้ลด MEMORY_COST ลง
define("MEMORY_COST", 1 << 17);
define("TIME_COST", 4);
define("THREADS", 1);

define("_HTTPSREQ_", false);
define("DATABASE_TYPE", 'mysqli');
define("_DBPREFIX_", 'ao_');
define("OPEN_ERROR_REPORT", true);
define("OPEN_HELP_API", false);
define("DISPLAY_MAX_UPLOAD", true);
define("ADMIN_PRO_TITLE_MAIN", 'AOSOFT');
define("PRONAME", 'AOSOFT');
define("ADMIN_TITLE", 'ADMIN AOSOFT WEB');
define("ADMIN_FOOTER_TITLE", '© webapp.com');
define("ADMIN_INSTALL_PASSWORD", "local-6afa16210fed");   // ใช้ติดตั้งในเครื่องเท่านั้น
define("ADMIN_EMAIL", "webapp.com");

# Mail setting
define("MAIL_SMTP_HOST", "webapp.com");
define("MAIL_SMTP_PORT", "25");
define("MAIL_SMTP_USER", "");
define("MAIL_SMTP_PASS", "");

define("ONLINE_MINUTE", 10);
define("DASHBOARD_NAME", 'main_v2');

# web setting
// โฟลเดอร์ที่ติดตั้งเว็บนี้ใต้โดเมน (ขึ้นต้นด้วย / ไม่มี / ท้าย) — ติดตั้งที่ root ของโดเมนให้ใส่ ''
// ต้องตรงกับ RewriteBase ใน .htaccess
$webBase = '/aostock';
$webUrl = '//' . $_SERVER['HTTP_HOST'] . $webBase;
$webPath = (@$webPath != '') ? $webPath : dirname(dirname(dirname(__FILE__)));

// เฉพาะโมดูลที่มีโฟลเดอร์จริงใน aowebdata/modules/ หรือ admweb/core/ (ชื่อที่ไม่มีโฟลเดอร์ระบบข้ามไปเงียบ ๆ)
$aModuleUse = array(
    'stock',        // AOSTOCK — ตาราง ao_stock_* + เมนูในหลังบ้าน
    'threat',
    'search',
    'counter',
    'dashboard',
    'member',
    'sitemap',
    'seo',
    'translate',
    'siteconfig',
);

$aConfig['language'] = array(
    'th' => 'Thai',
    'en' => 'English',
);

/* #####################Sitemap###########################
    $aConfigSitemap ระบุหน้าทั่วไปแต่ละหน้าฝั่ง FrontEnd
    ถ้าหน้าไหนมี slug ต่อหลังให้สร้าง value เป็น array
    พร้อมระบุ Keysname ที่ต้องใช้ query ในหน้านั้น

    changefreq คือความถี่ในการเปลี่ยนแปลงข้อมูลของหน้านั้น ได้แก่:
    - always: เปลี่ยนตลอดเวลา (หน้าแรก, Feed ข่าว)
    - hourly: ทุกชั่วโมง (พยากรณ์อากาศ, หุ้น)
    - daily: ทุกวัน (หน้าข่าวหน้าหลัก)
    - weekly: ทุกสัปดาห์ (บทความทั่วไป, หน้าสินค้า)
    - monthly: ทุกเดือน (หน้าบริการ, FAQ)
    - yearly: ทุกปี (หน้าเกี่ยวกับเรา, นโยบาย)
    - never: ไม่เคยเปลี่ยน (หน้า Archive เก่าๆ)

    priority คือลำดับความสำคัญของหน้านั้น (ค่าระหว่าง 0.0 - 1.0) ได้แก่:
    - 1.0: หน้าหลักของเว็บไซต์ (Homepage)
    - 0.8 - 0.9: หน้าหมวดหมู่หลัก หรือหน้าบทความยอดนิยม
    - 0.5 - 0.7: หน้าบทความทั่วไป หรือหน้าบริการ
    - 0.1 - 0.4: หน้าข้อมูลรอง เช่น นโยบายความเป็นส่วนตัว หรือหน้าติดต่อเรา
*/  ######################################################
// ไม่มีหน้าสาธารณะที่ต้องลง sitemap — หน้า AOSTOCK ทั้งหมดอยู่หลัง login (ดู aConfigPages ด้านล่าง)
$aConfig['aConfigSitemap'] = [];

/* หน้าที่ router (index.php) เปิดได้ แต่ไม่ลง sitemap — หน้าของ AOSTOCK ใน themes/aostock/
   ชื่อไฟล์ไม่มี .php · หน้าแรก (/aostock/) = home.php ไม่ต้องใส่ · เพิ่มหน้าใหม่ต้องเติมที่นี่ ไม่งั้นได้ 404 */
$aConfig['aConfigPages'] = [
    // เข้า / ออกระบบ
    'login',
    'logout',
    // พนักงาน
    'dashboard',
    'store',
    'sale',
    'bill-print',
    'products',
    'categories',
    'receive',
    'issue',
    'stocktake',
    'movements',
    'history',
    'return',
    'report-sales',
    // ฝ่ายบัญชี
    'account',
    'account-settings',
    // ผู้ดูแล
    'adm-dashboard',
    'adm-products',
    'adm-product-edit',
    'adm-categories',
    'adm-receive',
    'adm-issue',
    'adm-return',
    'adm-history',
    'adm-movements',
    'adm-day-sales',
    'adm-report',
    'adm-report-daily',
    'adm-report-branch',
    'adm-report-staff',
    'adm-report-products',
    'adm-users',
    'adm-user-add',
    'adm-branches',
    'adm-notify',
    'adm-notify-preview',
];

define("TELEGRAM_TOKEN", '');
define("TELEGRAM_CHAT_ID", '');

/* AOSTOCK: กุญแจเข้ารหัสค่าลับในตาราง ao_stock_setting (Bot token Telegram / รหัสผ่าน SMTP) — สุ่มไว้ต่อเว็บ ห้ามเปิดเผย
   เปลี่ยนหลังใช้งานแล้ว ค่าลับที่บันทึกไว้จะอ่านไม่ออก ต้องกรอก token / รหัสผ่านใหม่ในหน้า "ตั้งค่าการแจ้งเตือน" ของ POS */
define("AOSTOCK_SECRET_KEY", 'u0T9CbC3cNX6yUZq3scIOXyXtQBy_tEqCtNF7XwKz4s');
/* AOSTOCK: เฉพาะเครื่องทดสอบ — ส่ง Telegram ไปเซิร์ฟเวอร์จำลองในเครื่อง (ห้ามมีในไฟล์ fix ของเว็บจริง) */
define("AOSTOCK_TG_API", 'http://127.0.0.1:8099');
define("WEBSITE_NAME_SEO", 'STU FOR TEST');
define("DOMAIN_NAME", 'localhost');
define("PATH_WEB_ROOT", $webPath);
define("URL_WEB_ROOT", $webUrl);

define("PATH_ADMIN", PATH_WEB_ROOT . '/admweb');
define("URL_ADMIN", URL_WEB_ROOT . '/admweb');

define("PATH_AOWEBDATA", PATH_ADMIN . '/aowebdata');
define("URL_AOWEBDATA", URL_ADMIN . '/aowebdata');

# upload setting
define("PATH_UPLOAD", PATH_WEB_ROOT . '/uploads');
define("URL_UPLOAD", URL_WEB_ROOT . '/uploads');

# Html Template
define("PATH_UPLOAD_HTML", PATH_UPLOAD . '/_temp');
define("URL_UPLOAD_HTML", URL_UPLOAD . '/_temp');

# Config
define("PATH_UPLOAD_CONFIG", PATH_UPLOAD . '/config');
define("URL_UPLOAD_CONFIG", URL_UPLOAD . '/config');

# Admin Module
define("PATH_CORE", PATH_ADMIN . '/core');
define("URL_CORE", URL_ADMIN . '/core');

# Admin Custom Module
define("PATH_MODULE", PATH_AOWEBDATA . '/modules');
define("URL_MODULE", URL_AOWEBDATA . '/modules');

# Plugin
define("PATH_PLUGIN", PATH_ADMIN . '/plugins');
define("URL_PLUGIN", URL_ADMIN . '/plugins');

# Help
define("PATH_HELP", PATH_ADMIN . '/help');
define("URL_HELP", URL_ADMIN . '/help');

define("TEMPLATE_NAME", 'version2018');
define("CACHE_VERSION", '2018');
define("THEMENAME", 'custom1');

define("TEMPLATE_PATH", PATH_ADMIN . '/template/' . TEMPLATE_NAME);
define("TEMPLATE_URL", URL_ADMIN . '/template/' . TEMPLATE_NAME);

$aFileAllow = array(
    'image/jpeg',
    'image/gif',
    'image/pjpeg',
    'image/x-png',
    'image/webp',
    'image/avif',
);

$allowExtention = array(
    'jpg',
    'jpeg',
    'gif',
    'png',
    'webp',
    'avif',
);

define("IS_SHOW_SEARCH", false);
define("IS_NAME_SEARCH", 'ค้นหาข้อมูล'); //input name is fix qsearch
define("IS_LINK_SEARCH", URL_ADMIN . '/index.php?module=search&mp=search&ac=search');

define("DEBUG_VIEW", true);
define("DEBUG_SQL", (DOMAIN_NAME == 'localhost') ? true : false);
define("ERROR_REPORT_ALL", "0");
define("CHARSET", "utf-8"); //utf-8, windows-874
define("IS_SHOW_UPLOAD_SIZE", false);
define("IS_SHOW_HELP", true);
define("IS_FORGOT_PASS", false);
define("IS_LOGIN_PROVIDER", true);
define("IS_MATA_REDIRECT", false);
define("DEFAULT_LANGEUAGE", "th");
define("ENCODE_PASSWORD_MEMBER", true);
define("PW_RESET", "1a2b3c");
define("_TIME_", time());
define("_YY_", date('Y'));
define("_MM_", date('m'));
define("_DD_", date('d'));

$aMemberNameDisable = array(
    'admin',
    'member'
);

$aConfig['aModuleUse']      = $aModuleUse;
$aConfig['aUserDisable']    = $aMemberNameDisable;
$aConfig['codeBlock']       = 'true';
$aConfig['insertHTML']      = 'true';
$aConfigSitemap             = $aConfig['aConfigSitemap'];

include_once(PATH_ADMIN . '/include/a.province.php');
include_once(PATH_ADMIN . '/include/a.months.php');
