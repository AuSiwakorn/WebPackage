<?php
/**
 * FILE: admbuilder/config.php
 * ROLE: ค่าตั้งต้น SEO / schema (Organization) ของ AO Builder — ต้องแก้ต่อโปรเจกต์
 * DEPENDS: URL_WEB_ROOT (define ก่อน include)
 * TABLES: -
 * TODO:
 *   - [ ] แก้ค่า org ทุก field ให้ตรงโปรเจกต์ (site_name / logo / ที่อยู่ / tel / email / social) — ตอนนี้เป็น placeholder
 *   - [ ] ตัวอย่างเติม FAQList / ProductData / ArticlesList ต่อหน้า + วิธีใช้ AO Builder เต็ม: ดู AOBUILDER-THEME-GUIDE.md
 */
// config.php ถูก include ก่อน define _LANG_ ในบาง entry (load.php/save.php) — ห้ามอ้าง _LANG_ ตรงนี้
// locale คำนวณตอน render ใน SEO::applyDefaults() แทน; URL_WEB_ROOT guard กันลำดับ include
$__seoBase = defined('URL_WEB_ROOT') ? rtrim(URL_WEB_ROOT, '/') : '';
if (strncmp($__seoBase, '//', 2) === 0) { // protocol-relative → absolute (SEO/schema ต้องการ scheme)
    $__seoBase = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https:' : 'http:') . $__seoBase;
}
$__aDefaultSchemaTypes = [
	// --- ตัวตนเว็บ (ใช้กับ og / schema) — TODO แก้ให้ตรงโปรเจกต์ ---
	'site_name'        => 'Your Company Co., Ltd.',
	'Company'          => 'Your Company Co., Ltd.',
	'CompanyBaseUrl'   => $__seoBase,
	'logo'             => $__seoBase . '/assets/logo.png',
	'default_image'    => $__seoBase . '/assets/logo.png',

	// --- ที่อยู่ / ติดต่อ (schema Organization/Contact) ---
	'streetAddress'    => '123 Example Road',
	'addressLocality'  => 'Bangkok',
	'addressRegion'    => 'Bangkok',
	'postalCode'       => '10000',
	'addressCountry'   => 'Thailand',
	'telephone'        => '+66-2-000-0000',
	'email'            => 'contact@example.com',
	'contactType'      => 'customer service',
	'areaServed'       => 'Thailand',
	'availableLanguage' => ['Thai', 'English'],
	'serviceType'      => [],
	'social'           => [
		'https://www.facebook.com/yourpage',
		'https://www.instagram.com/yourpage',
	],

	// --- ช่อง dynamic (เติมต่อหน้า — ตัวอย่างใน AOBUILDER-THEME-GUIDE.md) ---
	'FAQList'                => [],
	'ProductData'            => [],
	'ArticlesList'           => [],
	'Articles_image'         => '',
	'Articles_category_name' => '',
	'Articles_datePublished' => date('Y-m-d'),
];
?>
