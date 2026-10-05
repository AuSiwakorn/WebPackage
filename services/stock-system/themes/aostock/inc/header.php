<?php
/**
 * FILE: themes/aostock/inc/header.php
 * ROLE: โครงหน้าจอหลัง login (sidebar + topbar)
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_branch, ao_stock_staff (ผ่าน api.php — ตัวเลือกสาขา / เมนูตามสิทธิ์)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 8: เอาข้อความ "ข้อมูลสมมติทั้งหมด ยังไม่เชื่อมฐานข้อมูล" ท้ายเมนูออก (ข้อมูลจริงทั้งหมดแล้ว)
 *   - [x] ช่วงที่ 11: เมนูแสดงตามสิทธิ์ของหน้า (page_ok) · ป้ายสถานะร้านกดไปหน้าเปิดร้านได้เฉพาะคนที่มีสิทธิ์
 *   - [x] ช่วงที่ 12: เมนู "พนักงานในสาขา" (เฉพาะผู้จัดการสาขา) · ใต้ชื่อแสดง "ผู้จัดการสาขา" (user_role_label)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   โครงหน้าจอหลัง login (sidebar + topbar)
   ต้องกำหนดตัวแปรก่อน include:
     $user        array จาก require_login()
     $branch      รหัสสาขาที่กำลังดู
     $PAGE_TITLE  ชื่อหน้า
     $PAGE_SUB    คำอธิบายใต้ชื่อหน้า (ไม่บังคับ)
     $NAV_ACTIVE  ชื่อไฟล์ของเมนูที่กำลังเปิด
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$PAGE_TITLE = isset($PAGE_TITLE) ? $PAGE_TITLE : 'ภาพรวม';
$PAGE_SUB   = isset($PAGE_SUB) ? $PAGE_SUB : '';
$NAV_ACTIVE = isset($NAV_ACTIVE) ? $NAV_ACTIVE : home_page($user);

/* เมนู — แสดงเฉพาะที่เปิดใช้ใน active_menus() (include/function.php)
   ผู้ดูแลใช้เมนูชุด adm- ของตัวเอง (ตรวจสอบทุกสาขา) · พนักงานและบัญชีใช้ชุดปกติ */
if ($user['role'] === 'admin') {
    $NAV_ALL = array(
        'ตรวจสอบ' => array(
            array('file' => 'adm-dashboard.php', 'label' => 'ภาพรวม',              'icon' => 'i-home'),
            array('file' => 'adm-products.php',  'label' => 'สินค้าในสต๊อก',        'icon' => 'i-boxes'),
            array('file' => 'adm-categories.php','label' => 'หมวดสินค้า',          'icon' => 'i-tag'),
            array('file' => 'adm-receive.php',   'label' => 'นำเข้าสินค้า',          'icon' => 'i-in'),
            array('file' => 'adm-issue.php',     'label' => 'เบิก / ตัดออก',        'icon' => 'i-out'),
            array('file' => 'adm-return.php',    'label' => 'รับคืนสินค้า',          'icon' => 'i-receipt'),
            array('file' => 'adm-history.php',   'label' => 'ประวัติการทำรายการ',    'icon' => 'i-history'),
            array('file' => 'adm-movements.php', 'label' => 'ประวัติเคลื่อนไหว',     'icon' => 'i-activity'),
        ),
        'รายงาน' => array(
            array('file' => 'adm-report.php',        'label' => 'ภาพรวมยอดขาย',        'icon' => 'i-chart'),
            array('file' => 'adm-report-daily.php',  'label' => 'สรุปยอดขายรายวัน',    'icon' => 'i-history'),
            array('file' => 'adm-report-branch.php', 'label' => 'ยอดขายแยกสาขา',      'icon' => 'i-building'),
            array('file' => 'adm-report-staff.php',  'label' => 'ยอดขายตามพนักงาน',    'icon' => 'i-users'),
            array('file' => 'adm-report-products.php', 'label' => 'สินค้าขายดี 100 อันดับ', 'icon' => 'i-boxes'),
        ),
        'บัญชี' => array(
            array('file' => 'account.php',          'label' => 'บิลขายและเงินเข้า', 'icon' => 'i-receipt'),
            array('file' => 'account-settings.php', 'label' => 'ตั้งค่าเลขที่บิล',   'icon' => 'i-settings'),
        ),
        'ตั้งค่า' => array(
            array('file' => 'adm-branches.php', 'label' => 'จัดการสาขา',    'icon' => 'i-building'),
            array('file' => 'adm-users.php',    'label' => 'จัดการพนักงาน', 'icon' => 'i-users'),
            array('file' => 'adm-notify.php',   'label' => 'ตั้งค่าการแจ้งเตือน', 'icon' => 'i-bell'),
        ),
    );
} else {
    $NAV_ALL = array(
        'ใช้งานประจำวัน' => array(
            array('file' => 'dashboard.php',  'label' => 'ภาพรวมของฉัน',      'icon' => 'i-home'),
            array('file' => 'store.php',      'label' => 'เปิด / ปิดร้าน',    'icon' => 'i-store'),
            array('file' => 'sale.php',       'label' => 'ขายสินค้า',         'icon' => 'i-cart'),
            array('file' => 'products.php',   'label' => 'สินค้าในสต๊อก',     'icon' => 'i-boxes'),
            array('file' => 'categories.php', 'label' => 'หมวดสินค้า',        'icon' => 'i-tag'),
            array('file' => 'receive.php',    'label' => 'นำเข้าสินค้า',      'icon' => 'i-in'),
            array('file' => 'issue.php',      'label' => 'เบิก / ตัดออก',     'icon' => 'i-out'),
            array('file' => 'stocktake.php',  'label' => 'ตรวจนับ / ปรับยอด',  'icon' => 'i-clipboard'),
            array('file' => 'return.php',     'label' => 'รับคืนสินค้า',       'icon' => 'i-receipt'),
            array('file' => 'history.php',    'label' => 'ประวัติการทำรายการ', 'icon' => 'i-history'),
            array('file' => 'movements.php',  'label' => 'ประวัติเคลื่อนไหว', 'icon' => 'i-activity'),
        ),
        'รายงาน' => array(
            array('file' => 'report-sales.php', 'label' => 'รายงานยอดขาย', 'icon' => 'i-chart'),
        ),
        'ผู้จัดการสาขา' => array(                                                  // ช่วงที่ 12 — page_ok กรองให้เหลือเฉพาะผู้จัดการ
            array('file' => 'team.php', 'label' => 'พนักงานในสาขา', 'icon' => 'i-users'),
        ),
        'บัญชี' => array(
            array('file' => 'account.php',          'label' => 'บิลขายและเงินเข้า', 'icon' => 'i-receipt',  'roles' => array('account')),
            array('file' => 'account-settings.php', 'label' => 'ตั้งค่าเลขที่บิล',   'icon' => 'i-settings', 'roles' => array('account')),
        ),
    );
}

$NAV = array();
foreach ($NAV_ALL as $group => $items) {
    $keep = array();
    foreach ($items as $it) {
        /* ใครเห็นเมนูนี้: ค่าเริ่มต้น = พนักงาน + ผู้ดูแล · ฝ่ายบัญชีเห็นเฉพาะเมนูที่ระบุ roles */
        $roles = isset($it['roles']) ? $it['roles'] : array('staff', 'admin');
        if (in_array($user['role'], $roles, true) && page_ok($user, $it['file'])) {      // สิทธิ์ของหน้าอยู่ใน page_perm (ช่วงที่ 11)
            $keep[] = $it;
        }
    }
    if ($keep) {
        $NAV[$group] = $keep;
    }
}

/* โหมดเมนูด้านข้าง
   - พนักงาน              : ย่อเป็นแถบไอคอน (rail) เป็นค่าเริ่มต้น
   - ผู้ดูแลระบบ           : กางเต็มเป็นค่าเริ่มต้น
   ผู้ใช้กดสลับเองได้ และจำไว้ใน cookie */
$navMode = ($user['role'] === 'admin') ? 'full' : 'rail';
if (isset($_COOKIE['ao_nav']) && in_array($_COOKIE['ao_nav'], array('rail', 'full'), true)) {
    $navMode = $_COOKIE['ao_nav'];
}

$branch   = isset($branch) ? $branch : (($user['role'] === 'admin') ? 'ALL' : $user['branch']);
$branches = visible_branches($user);
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<script>document.documentElement.className += ' js';</script>
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?php echo e($PAGE_TITLE) ?> | <?php echo e(APP_NAME) ?></title>
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#07211B">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2064%2064'%3E%3Crect%20width='64'%20height='64'%20rx='14'%20fill='%2307211B'/%3E%3Cpath%20d='M14%2024l18-9%2018%209-18%209z'%20fill='%23C9A86A'/%3E%3Cpath%20d='M14%2024v16l18%209V33z'%20fill='%230E7A5F'/%3E%3Cpath%20d='M50%2024v16l-18%209V33z'%20fill='%2312946F'/%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php /* ต่อท้ายด้วยเวลาแก้ไฟล์ล่าสุด แก้ CSS เมื่อไรเบราว์เซอร์จะโหลดใหม่เอง ไม่ติดแคชเก่า */ ?>
<link rel="stylesheet" href="<?php echo e(asset_url('app.css')) ?>?v=<?php echo (int) @filemtime(dirname(__FILE__) . '/../assets/app.css') ?>">
</head>
<body>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <symbol id="i-home" viewBox="0 0 24 24"><path d="M4 10.5L12 4l8 6.5V20a1 1 0 0 1-1 1h-4v-6H9v6H5a1 1 0 0 1-1-1z"/></symbol>
    <symbol id="i-boxes" viewBox="0 0 24 24"><rect x="3" y="12" width="8" height="8" rx="1"/><rect x="13" y="12" width="8" height="8" rx="1"/><rect x="8" y="3" width="8" height="8" rx="1"/></symbol>
    <symbol id="i-tag" viewBox="0 0 24 24"><path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="8.5" r="1.5"/></symbol>
    <symbol id="i-box" viewBox="0 0 24 24"><path d="M12 3l8 4v10l-8 4-8-4V7z"/><path d="M4 7l8 4 8-4M12 11v10"/></symbol>
    <symbol id="i-in" viewBox="0 0 24 24"><path d="M12 3v11"/><path d="M8 10l4 4 4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></symbol>
    <symbol id="i-out" viewBox="0 0 24 24"><path d="M12 14V3"/><path d="M8 7l4-4 4 4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></symbol>
    <symbol id="i-transfer" viewBox="0 0 24 24"><path d="M4 8h13l-3-3M20 16H7l3 3"/></symbol>
    <symbol id="i-clipboard" viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2.8h6V4"/><path d="M8.5 11.5l1.8 1.8 3.7-3.8M9 17h6"/></symbol>
    <symbol id="i-history" viewBox="0 0 24 24"><path d="M3.5 12a8.5 8.5 0 1 0 2.6-6.1"/><path d="M3 4v5h5"/><path d="M12 8v4l3 2"/></symbol>
    <symbol id="i-chart" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 6-7"/></symbol>
    <symbol id="i-alert" viewBox="0 0 24 24"><path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/></symbol>
    <symbol id="i-building" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M8.5 7h2M13.5 7h2M8.5 11h2M13.5 11h2M8.5 15h2M13.5 15h2M10 21v-3h4v3"/></symbol>
    <symbol id="i-users" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M21.5 20a6.5 6.5 0 0 0-3.8-5.9"/></symbol>
    <symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 14.2a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-1.8-.3 1.6 1.6 0 0 0-1 1.5v.2a2 2 0 1 1-4 0v-.1a1.6 1.6 0 0 0-1-1.5 1.6 1.6 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.6 1.6 0 0 0 .3-1.8 1.6 1.6 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.6 1.6 0 0 0 1.5-1 1.6 1.6 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.6 1.6 0 0 0 1.8.3H9a1.6 1.6 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.6 1.6 0 0 0 1 1.5 1.6 1.6 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0-.3 1.8V9a1.6 1.6 0 0 0 1.5 1h.2a2 2 0 1 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1z"/></symbol>
    <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M16.5 16.5L21 21"/></symbol>
    <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></symbol>
    <symbol id="i-logout" viewBox="0 0 24 24"><path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/><path d="M9 8l-4 4 4 4M5 12h10"/></symbol>
    <symbol id="i-coin" viewBox="0 0 24 24"><ellipse cx="12" cy="6.5" rx="7.5" ry="3.5"/><path d="M4.5 6.5v11c0 1.9 3.4 3.5 7.5 3.5s7.5-1.6 7.5-3.5v-11"/><path d="M4.5 12c0 1.9 3.4 3.5 7.5 3.5s7.5-1.6 7.5-3.5"/></symbol>
    <symbol id="i-ban" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M5.6 5.6l12.8 12.8"/></symbol>
    <symbol id="i-check" viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></symbol>
    <symbol id="i-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></symbol>
    <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></symbol>
    <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
    <symbol id="i-x" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></symbol>
    <symbol id="i-panel-l" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/></symbol>
    <symbol id="i-store" viewBox="0 0 24 24"><path d="M4 9h16v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/><path d="M3.2 9l1.4-5A1 1 0 0 1 5.6 3h12.8a1 1 0 0 1 1 .8L20.8 9"/><path d="M9 21v-6h6v6"/></symbol>
    <symbol id="i-store-off" viewBox="0 0 24 24"><path d="M4 9h16v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1z"/><path d="M3.2 9l1.4-5A1 1 0 0 1 5.6 3h12.8a1 1 0 0 1 1 .8L20.8 9"/><path d="M9.5 15.5h5"/></symbol>
    <symbol id="i-activity" viewBox="0 0 24 24"><path d="M3 12h4l2.5-6 5 12 2.5-6h4"/></symbol>
    <symbol id="i-phone" viewBox="0 0 24 24"><rect x="6.5" y="2.5" width="11" height="19" rx="2.2"/><path d="M10.5 18.5h3"/></symbol>
    <symbol id="i-tablet" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M11 18h2"/></symbol>
    <symbol id="i-camera" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="9" cy="9" r="2.5"/><circle cx="9" cy="16" r="2"/><circle cx="16" cy="9" r="1"/></symbol>
    <symbol id="i-cable" viewBox="0 0 24 24"><path d="M8 3v4M12 3v4M6.5 7h7v4a3.5 3.5 0 0 1-7 0z"/><path d="M10 14.5V17a4 4 0 0 0 8 0V9"/><path d="M16 5h4v4h-4z"/></symbol>
    <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3l8 3v6c0 5-3.4 8.2-8 9-4.6-.8-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></symbol>
    <symbol id="i-cart" viewBox="0 0 24 24"><path d="M2.5 4h2.2l2.3 11.2a1.5 1.5 0 0 0 1.5 1.2h8.6a1.5 1.5 0 0 0 1.5-1.2L20 8H6"/><circle cx="9.5" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/></symbol>
    <symbol id="i-print" viewBox="0 0 24 24"><path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M7 14h10v7H7z"/></symbol>
    <symbol id="i-bell" viewBox="0 0 24 24"><path d="M6 16V11a6 6 0 1 1 12 0v5l2 2H4z"/><path d="M10 21h4"/></symbol>
    <symbol id="i-receipt" viewBox="0 0 24 24"><path d="M5 3h14v18l-2.3-1.6-2.4 1.6-2.3-1.6L9.7 21l-2.4-1.6L5 21z"/><path d="M9 8h6M9 12h6"/></symbol>
    <symbol id="i-trash" viewBox="0 0 24 24"><path d="M4 7h16M10 7V4.8h4V7M6 7l1 13a1 1 0 0 0 1 .9h8a1 1 0 0 0 1-.9l1-13"/><path d="M10 11v6M14 11v6"/></symbol>
    <symbol id="i-minus" viewBox="0 0 24 24"><path d="M5 12h14"/></symbol>
  </defs>
</svg>

<div class="app<?php echo $navMode === 'rail' ? ' app--rail' : '' ?>" id="app">

  <!-- ===== SIDEBAR ===== -->
  <aside class="side" id="side">
    <button class="side-close" id="side-close" type="button" aria-label="ปิดเมนู">
      <svg class="ico"><use href="#i-x"/></svg>
    </button>
    <div class="side-head">
      <a class="side-brand" href="<?php echo e(home_page($user)) ?>" aria-label="<?php echo e(APP_NAME) ?> หน้าแรก">
        <span class="logo logo--light logo-full"><span class="logo-ao">AO</span><span class="logo-sk">STOCK</span></span>
        <span class="logo-mark">AO</span>
      </a>
      <small class="side-sub"><?php echo e(APP_TITLE) ?></small>
    </div>

    <nav class="side-nav" aria-label="เมนูหลัก">
      <?php foreach ($NAV as $group => $items) { ?>
        <?php if (count($NAV) > 1) { ?><div class="side-group"><?php echo e($group) ?></div><?php } ?>
        <ul>
          <?php foreach ($items as $it) { ?>
            <li>
              <a href="<?php echo e($it['file']) ?>" class="<?php echo $NAV_ACTIVE === $it['file'] ? 'on' : '' ?>"
                 data-t="<?php echo e($it['label']) ?>" aria-label="<?php echo e($it['label']) ?>">
                <svg class="ico"><use href="#<?php echo e($it['icon']) ?>"/></svg><span class="lbl"><?php echo e($it['label']) ?></span>
              </a>
            </li>
          <?php } ?>
        </ul>
      <?php } ?>
    </nav>

  </aside>
  <div class="side-backdrop" id="side-backdrop"></div>

  <!-- ===== MAIN ===== -->
  <div class="main">

    <header class="top">
      <button class="icon-btn side-toggle" id="side-toggle" type="button" aria-label="เปิดเมนู" aria-expanded="false">
        <svg class="ico"><use href="#i-menu"/></svg>
      </button>
      <button class="icon-btn nav-mode" id="nav-mode" type="button"
              aria-label="ย่อ / ขยายเมนูด้านข้าง" title="ย่อ / ขยายเมนูด้านข้าง">
        <svg class="ico"><use href="#i-panel-l"/></svg>
      </button>

      <div class="top-title">
        <h1><?php echo e($PAGE_TITLE) ?></h1>
        <?php if ($PAGE_SUB !== '') { ?><span class="top-sub"><?php echo e($PAGE_SUB) ?></span><?php } ?>
      </div>

      <?php
      $__code  = work_branch($user);
      $__open  = store_is_open($__code);
      $__shut  = store_is_closed($__code);
      $__state = store_state($__code);
      ?>
      <?php if (can($user, 'sale') || page_perm_ok($user, 'store.php')) { ?>
      <a class="store-chip <?php echo $__open ? 'is-open' : ($__shut ? 'is-shut' : 'is-wait') ?>"<?php echo page_ok($user, 'store.php') ? ' href="store.php"' : '' ?>>
        <span class="dot"></span>
        <span class="sc-t">
        <?php if ($__open) { ?>
          เปิดแล้ว <?php echo e($__state['opened_at']) ?> น.
        <?php } elseif ($__shut) { ?>
          ปิดร้านแล้ว
        <?php } else { ?>
          ยังไม่เปิดร้าน
        <?php } ?>
        </span>
      </a>
      <?php } ?>

      <div class="top-spacer"></div>

      <div class="top-tools">
        <?php /* พนักงานผูกกับสาขาเดียวตั้งแต่ตอนสร้างรหัส จึงไม่ต้องมีตัวเลือกสาขา
                 ช่องนี้จะโผล่เฉพาะสิทธิ์ที่เห็นได้หลายสาขาเท่านั้น */ ?>
        <?php if (empty($NO_BRANCH_PICK) && (($user['role'] === 'admin' && $NAV_ACTIVE === 'adm-dashboard.php') || ($user['role'] !== 'admin' && feature_enabled('branch_pick') && count($branches) > 1))) { ?>
        <?php /* ผู้ดูแล: ตัวเลือกสาขาบนแถบบนมีเฉพาะหน้าภาพรวม — หน้า adm- อื่นมีตัวกรองสาขาในหน้าเอง */ ?>
        <form class="branch-pick" method="get" action="<?php echo e($NAV_ACTIVE) ?>">
          <?php if (!empty($PICK_HIDDEN)) { foreach ($PICK_HIDDEN as $hk => $hv) { ?>
            <input type="hidden" name="<?php echo e($hk) ?>" value="<?php echo e($hv) ?>">
          <?php } } ?>
          <label class="sr-only" for="branch">สาขาที่กำลังดู</label>
          <select class="select" name="branch" id="branch" onchange="this.form.submit()">
            <?php foreach ($branches as $bcode => $b) { ?>
              <?php if ($bcode === 'ALL' && $NAV_ACTIVE !== 'adm-dashboard.php') { continue; } ?>
              <option value="<?php echo e($bcode) ?>" <?php echo $branch === $bcode ? 'selected' : '' ?>><?php echo e($b['name']) ?></option>
            <?php } ?>
          </select>
        </form>
        <?php } ?>

        <?php if (feature_enabled('search')) { ?>
        <div class="top-search">
          <svg class="ico"><use href="#i-search"/></svg>
          <label class="sr-only" for="q">ค้นหาสินค้า</label>
          <input type="search" id="q" placeholder="ค้นหาสินค้า / บาร์โค้ด">
        </div>
        <?php } ?>

        <div class="who">
          <span class="av"><?php echo e($user['initials']) ?></span>
          <span class="who-t">
            <b><?php echo e($user['name']) ?></b>
            <small><?php echo e(user_role_label($user)) ?> · <?php echo e(branch_name($user['branch'])) ?></small>
          </span>
          <a class="icon-btn" href="logout.php" title="ออกจากระบบ" aria-label="ออกจากระบบ">
            <svg class="ico"><use href="#i-logout"/></svg>
          </a>
        </div>

        <?php if (menu_enabled('sale.php') && can($user, 'sale')) { ?>
          <?php $__cart = cart_count(); ?>
          <a class="btn-sale<?php echo $__open ? '' : ' is-lock' ?>"
             href="<?php echo ($__open || !page_ok($user, 'store.php')) ? 'sale.php' : 'store.php' ?>"
             title="<?php echo $__open ? 'เปิดหน้าขายสินค้า' : 'ต้องเปิดร้านก่อนจึงจะขายได้' ?>">
            <svg class="ico"><use href="#i-cart"/></svg>
            <span class="t-full">ขายสินค้า</span>
            <span class="t-min">ขาย</span>
            <i id="cart-bdg" class="bdg<?php echo $__cart > 0 ? '' : ' none' ?>"><?php echo $__cart > 0 ? (int) $__cart : '' ?></i>
          </a>
        <?php } ?>
      </div>
    </header>

    <main class="page">
    <?php if (!empty($_SESSION['flash'])) { ?>
      <div class="alert alert-info" role="status"><svg class="ico"><use href="#i-info"/></svg><span><?php echo e($_SESSION['flash']) ?></span></div>
      <?php unset($_SESSION['flash']); ?>
    <?php } ?>
