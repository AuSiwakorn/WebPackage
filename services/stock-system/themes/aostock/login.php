<?php
/**
 * FILE: themes/aostock/login.php
 * ROLE: หน้าเข้าสู่ระบบ — พนักงานใช้ชื่อผู้ใช้ + PIN (เครื่องที่เคยเข้าแล้วแตะปุ่มชื่อ) · ผู้ดูแล / ฝ่ายบัญชีใช้ชื่อผู้ใช้ + รหัสผ่าน
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_staff (PIN / รหัสผ่าน · ล็อกเมื่อกรอกผิด) · ao_stock_branch · ao_stock_remember (จดจำการเข้าสู่ระบบ)
 *         · ao_stock_login_ip (ล็อกตาม IP) — ผ่าน api.php
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 5: ตรวจ PIN / รหัสผ่านกับตาราง (password_hash)
 *   - [x] ช่วงที่ 10: "จดจำการเข้าสู่ระบบ" ใช้ได้จริง 30 วัน · "ลืมรหัสผ่าน?" บอกวิธีรีเซ็ต · เอาป้ายระบบทดลองออก
 *         · ข้อความสิทธิ์ของพนักงานสร้างด้วย textContent (กัน XSS จากชื่อสาขา)
 *   - [x] ช่วงที่ 12: ปุ่มเลือกชื่อแสดง "ผู้จัดการสาขา" แทน "พนักงาน" (user_role_label)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 *   - [x] ช่วงที่ 13: ไม่แสดงรายชื่อพนักงานทั้งหมดแล้ว — พิมพ์ชื่อผู้ใช้ + PIN · เครื่องจำชื่อคนที่เคยเข้า (ปุ่มชื่อ + × เอาออก)
 *         · ติ๊ก "จำชื่อฉันไว้บนเครื่องนี้" ไว้ให้ก่อน · ข้อความผิดไม่บอกว่าชื่อผู้ใช้มีจริงไหม · ล็อกตาม IP ทั้ง 2 แท็บ
 *   - [x] วงหมุนกลางจอ "กำลังเข้าสู่ระบบ…" ทันทีที่ส่ง PIN / รหัสผ่าน (ข้อ ก) · กันกดซ้ำระหว่างส่ง · กดย้อนกลับมาแล้วซ่อนให้
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
require_once dirname(__FILE__) . '/include/function.php';

if (is_logged_in() || remember_login() !== null) {            // จดจำการเข้าสู่ระบบไว้ = เข้าให้เลย
    header('Location: ' . url(home_page(current_user())));
    exit;
}

$error     = '';
$mode      = 'pin';                      // แท็บที่เปิดอยู่: pin | admin
$username  = '';
$known     = known_staffs();             // คนที่เครื่องนี้จำไว้ (ช่วงที่ 13) — คนล่าสุดก่อน
$via       = $known ? 'pick' : 'type';   // แท็บ PIN: pick = แตะปุ่มชื่อ · type = พิมพ์ชื่อผู้ใช้
$staffPick = '';                         // ปุ่มชื่อที่เลือก
$typed     = '';                         // ชื่อผู้ใช้ที่พิมพ์
$keep      = true;                       // "จำชื่อฉันไว้บนเครื่องนี้" ติ๊กไว้ให้ก่อน (ข้อ 3ก)

/* ---- เอาชื่อออกจากเครื่องนี้ (ปุ่ม × บนปุ่มชื่อ) ---- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode']) && $_POST['mode'] === 'forget') {
    if (csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null) && isset($_POST['forget'])) {
        known_forget((int) $_POST['forget']);
    }
    header('Location: ' . url('login.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = (isset($_POST['mode']) && $_POST['mode'] === 'admin') ? 'admin' : 'pin';
    if ($mode === 'admin' && !role_enabled('admin')) {
        $mode = 'pin';                    // ปิดช่องทางผู้ดูแลไว้ก่อนในเฟสนี้
    }
    $lock = login_locked();

    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $error = 'เซสชันหมดอายุ กรุณาลองเข้าสู่ระบบอีกครั้ง';
    } elseif ($lock > 0) {
        $error = 'กรอกผิดหลายครั้งเกินไป กรุณารออีก ' . $lock . ' วินาที';
    } elseif (($ipLeft = ip_locked_left()) > 0) {            // ช่วงที่ 13: ทุกบัญชีรวมกันผิดเกินกำหนดจาก IP นี้
        $error = 'มีการกรอกผิดจากเครือข่ายนี้หลายครั้งเกินไป — ลองใหม่ในอีก ' . (int) ceil($ipLeft / 60) . ' นาที';
    } elseif ($mode === 'pin') {
        $via       = (isset($_POST['via']) && $_POST['via'] === 'type') ? 'type' : 'pick';
        $typed     = isset($_POST['staff_typed']) ? strtolower(trim((string) $_POST['staff_typed'])) : '';
        $keep      = !empty($_POST['keep']);
        $staffPick = isset($_POST['staff']) ? strtolower(trim((string) $_POST['staff'])) : '';
        $who       = ($via === 'type') ? $typed : $staffPick;
        $pin       = isset($_POST['pin']) ? trim($_POST['pin']) : '';

        if ($who === '') {
            $error = ($via === 'type') ? 'กรุณากรอกชื่อผู้ใช้' : 'กรุณาเลือกชื่อของคุณก่อน';
        } elseif (strlen(preg_replace('/\D/', '', $pin)) !== 4) {
            $error = 'กรุณากรอก PIN ให้ครบ 4 หลัก';
        } elseif (($left = staff_locked_left($who)) > 0) {
            $error = 'กรอก PIN ผิดหลายครั้ง บัญชีนี้ถูกล็อกชั่วคราว — ลองใหม่ในอีก ' . (int) ceil($left / 60) . ' นาที หรือให้ผู้จัดการสาขา / ผู้ดูแลรีเซ็ต PIN';
        } else {
            $user = attempt_pin_login($who, $pin);
            if ($user === null) {
                login_failed();
                staff_login_failed($who);
                ip_login_failed();
                /* ชื่อที่เครื่องนี้จำไว้อยู่แล้ว บอกตรง ๆ ได้ · ชื่อที่พิมพ์มา ไม่บอกว่ามีชื่อนี้จริงไหม (กันไล่เดาชื่อผู้ใช้) */
                $error = isset($known[$who]) ? 'PIN ไม่ถูกต้อง' : 'ชื่อผู้ใช้หรือ PIN ไม่ถูกต้อง';
            } else {
                staff_login_ok($who);
                login_user($user);
                if ($via === 'pick' || $keep) {
                    known_add($user);                            // จำชื่อไว้บนเครื่องนี้ / เลื่อนขึ้นเป็นคนแรก
                }
                header('Location: ' . url(home_page($user)));
                exit;
            }
        }
    } else {
        /* ชื่อช่อง adm_user / adm_pass — admweb (fix.req.php) แก้ค่าของช่องชื่อ username / password ทิ้ง ' และ = ก่อนถึงหน้านี้ */
        $username = isset($_POST['adm_user']) ? trim($_POST['adm_user']) : '';
        $password = isset($_POST['adm_pass']) ? (string) $_POST['adm_pass'] : '';

        $uKey = strtolower($username);
        if ($username === '' || $password === '') {
            $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบ';
        } elseif (($left = staff_locked_left($uKey)) > 0) {
            $error = 'กรอกรหัสผ่านผิดหลายครั้ง บัญชีนี้ถูกล็อกชั่วคราว — ลองใหม่ในอีก ' . (int) ceil($left / 60) . ' นาที';
        } else {
            $user = attempt_login($username, $password);
            if ($user === null) {
                login_failed();
                staff_login_failed($uKey);
                ip_login_failed();
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            } else {
                staff_login_ok($uKey);
                login_user($user);
                if (!empty($_POST['remember'])) {
                    remember_issue($user);               // จดจำเครื่องนี้ 30 วัน
                }
                header('Location: ' . url(home_page($user)));
                exit;
            }
        }
    }
}

/* ปุ่มชื่อ: เฉพาะคนที่เครื่องนี้จำไว้ (ไม่มีใคร = พิมพ์ชื่อผู้ใช้) · เลือกคนล่าสุดไว้ให้ก่อน */
if (!$known) {
    $via = 'type';
}
if ($staffPick === '' || !isset($known[$staffPick])) {
    $keys      = array_keys($known);
    $staffPick = $keys ? $keys[0] : '';
}
$hasStaff = false;                               // ระบบใหม่ยังไม่มีพนักงาน → บอกวิธีเพิ่ม
foreach (users_active() as $u) {
    if ($u['role'] === 'staff') {
        $hasStaff = true;
        break;
    }
}
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>เข้าสู่ระบบ | <?php echo e(APP_NAME) ?> — <?php echo e(APP_TITLE) ?></title>
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#07211B">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2064%2064'%3E%3Crect%20width='64'%20height='64'%20rx='14'%20fill='%2307211B'/%3E%3Cpath%20d='M14%2024l18-9%2018%209-18%209z'%20fill='%23C9A86A'/%3E%3Cpath%20d='M14%2024v16l18%209V33z'%20fill='%230E7A5F'/%3E%3Cpath%20d='M50%2024v16l-18%209V33z'%20fill='%2312946F'/%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo e(asset_url('app.css')) ?>?v=<?php echo (int) @filemtime(dirname(__FILE__) . '/assets/app.css') ?>">
</head>
<body>

<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <symbol id="i-devices" viewBox="0 0 24 24"><rect x="2.5" y="5" width="13" height="9" rx="1.5"/><path d="M6 18h7"/><rect x="17" y="9" width="4.5" height="10" rx="1.2"/></symbol>
    <symbol id="i-building" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M8.5 7h2M13.5 7h2M8.5 11h2M13.5 11h2M8.5 15h2M13.5 15h2M10 21v-3h4v3"/></symbol>
    <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3l8 3v6c0 5-3.4 8.2-8 9-4.6-.8-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></symbol>
    <symbol id="i-eye" viewBox="0 0 24 24"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/></symbol>
    <symbol id="i-eye-off" viewBox="0 0 24 24"><path d="M4 4l16 16"/><path d="M9.6 9.6A3 3 0 0 0 12 15a3 3 0 0 0 2.4-1.2"/><path d="M6.6 6.7C4.2 8.3 2.5 12 2.5 12s3.5 6.5 9.5 6.5c1.6 0 3-.4 4.2-1M10 5.8c.65-.2 1.3-.3 2-.3 6 0 9.5 6.5 9.5 6.5s-.9 1.7-2.5 3.3"/></symbol>
    <symbol id="i-alert" viewBox="0 0 24 24"><path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/></symbol>
    <symbol id="i-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></symbol>
    <symbol id="i-back" viewBox="0 0 24 24"><path d="M20 6H9.5L3 12l6.5 6H20a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1z"/><path d="M12.5 9.5l5 5M17.5 9.5l-5 5"/></symbol>
    <symbol id="i-login" viewBox="0 0 24 24"><path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4"/><path d="M15 8l4 4-4 4M19 12H9"/></symbol>
    <symbol id="i-x" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></symbol>
  </defs>
</svg>

<main class="login">

  <!-- ===== ฝั่งซ้าย: แบรนด์ ===== -->
  <section class="login-brand">
    <div>
      <span class="logo logo--light"><span class="logo-ao">AO</span><span class="logo-sk">STOCK</span></span>
      <div class="brand-by"><?php echo e(APP_TITLE) ?> · by AOSOFT</div>
    </div>

    <div class="login-brand-mid">
      <h1>ระบบสำหรับพนักงาน<br>บริหารสต๊อกทุกสาขาในที่เดียว</h1>
      <p>เข้าครั้งแรกบนเครื่องนี้ พิมพ์ชื่อผู้ใช้แล้วกด PIN 4 หลัก ครั้งต่อไปแตะชื่อได้เลย ระบบจะเปิดเฉพาะสาขาและเมนูที่คุณมีสิทธิ์เข้าถึง</p>

      <ul class="login-points">
        <li>
          <span class="pi"><svg class="ico"><use href="#i-devices"/></svg></span>
          <span><b>ออกแบบเพื่อแท็บเล็ต</b>กดง่ายด้วยนิ้ว ใช้ได้ทั้ง iPad มือถือ และโน้ตบุ๊ก</span>
        </li>
        <li>
          <span class="pi"><svg class="ico"><use href="#i-building"/></svg></span>
          <span><b>แยกข้อมูลรายสาขา</b>พนักงานเห็นเฉพาะสต๊อกสาขาตนเอง ผู้ดูแลเห็นภาพรวมทุกสาขา</span>
        </li>
        <li>
          <span class="pi"><svg class="ico"><use href="#i-shield"/></svg></span>
          <span><b>บันทึกทุกการแก้ไข</b>ทุกรายการมีชื่อผู้ทำและเวลากำกับ ตรวจย้อนหลังได้เสมอ</span>
        </li>
      </ul>
    </div>

    <div class="login-brand-foot">
      © <?php echo date('Y') ?> <?php echo e(APP_OWNER) ?> ·
      <a href="https://www.aosoft.co.th/services/stock-system/" target="_blank" rel="noopener">ดูรายละเอียดบริการ</a>
    </div>
  </section>

  <!-- ===== ฝั่งขวา: ฟอร์ม ===== -->
  <section class="login-panel">
    <div class="login-card">

      <h2>เข้าสู่ระบบ</h2>

      <?php if (role_enabled('admin')) { ?>
      <div class="lg-tabs" role="tablist">
        <button type="button" class="<?php echo $mode === 'pin' ? 'on' : '' ?>" data-tab="pin" role="tab"
                aria-selected="<?php echo $mode === 'pin' ? 'true' : 'false' ?>">พนักงาน (PIN)</button>
        <button type="button" class="<?php echo $mode === 'admin' ? 'on' : '' ?>" data-tab="admin" role="tab"
                aria-selected="<?php echo $mode === 'admin' ? 'true' : 'false' ?>">ผู้ดูแล / บัญชี</button>
      </div>
      <?php } ?>

      <?php if ($error !== '') { ?>
        <div class="alert alert-error" role="alert">
          <svg class="ico"><use href="#i-alert"/></svg>
          <span><?php echo e($error) ?></span>
        </div>
      <?php } ?>

      <!-- ===== แท็บ 1: PIN ===== -->
      <form class="login-form pane<?php echo $mode === 'pin' ? ' on' : '' ?>" id="pane-pin"
            method="post" action="login.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="mode" value="pin">
        <input type="hidden" name="via" id="via" value="<?php echo e($via) ?>">
        <input type="hidden" name="staff" id="staff" value="<?php echo e($staffPick) ?>">
        <input type="hidden" name="pin" id="pin" value="">

        <?php if ($known) { /* ช่วงที่ 13: ปุ่มชื่อเฉพาะคนที่เคยเข้าบนเครื่องนี้ */ ?>
        <div id="who-pick"<?php echo $via === 'type' ? ' hidden' : '' ?>>
          <span class="lbl">เลือกชื่อของคุณ</span>
          <div class="staffs" id="staffs">
            <?php foreach ($known as $uname => $u) { $role = user_role_label(array('role' => $u['role'], 'username' => $uname)); ?>
              <div class="staff-wrap">
                <button class="staff<?php echo $uname === $staffPick ? ' on' : '' ?>" type="button" data-user="<?php echo e($uname) ?>"
                        data-role="<?php echo e($role) ?>"
                        data-scope="<?php echo e(role_scope($u['role'])) ?>"
                        data-branch="<?php echo e(branch_name($u['branch'])) ?>">
                  <span class="av"><?php echo e(user_initial($u)) ?></span>
                  <span class="st">
                    <b><?php echo e($u['name']) ?></b>
                    <small><?php echo e($role) ?> · <?php echo e(branch_name($u['branch'])) ?></small>
                  </span>
                </button>
                <button class="staff-x" type="submit" form="forget-form" name="forget" value="<?php echo (int) $u['id'] ?>"
                        data-ask="<?php echo e('เอาชื่อ ' . $u['name'] . ' ออกจากเครื่องนี้? ครั้งหน้าต้องพิมพ์ชื่อผู้ใช้เอง') ?>"
                        aria-label="<?php echo e('เอาชื่อ ' . $u['name'] . ' ออกจากเครื่องนี้') ?>" title="เอาชื่อออกจากเครื่องนี้">
                  <svg class="ico"><use href="#i-x"/></svg>
                </button>
              </div>
            <?php } ?>
          </div>

          <p class="staff-perm" id="staff-perm">
            <svg class="ico"><use href="#i-shield"/></svg>
            <span id="perm-txt"></span>
          </p>
          <button type="button" class="lg-link" data-who="type">ไม่มีชื่อคุณ? พิมพ์ชื่อผู้ใช้</button>
        </div>
        <?php } ?>

        <div id="who-type"<?php echo $via === 'type' ? '' : ' hidden' ?>>
          <label class="lbl" for="staff-user">ชื่อผู้ใช้</label>
          <input class="input" type="text" id="staff-user" name="staff_typed" value="<?php echo e($typed) ?>" maxlength="20"
                 placeholder="เช่น nipa" autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false" enterkeyhint="next">
          <label class="check lg-keep"><input type="checkbox" name="keep" value="1"<?php echo $keep ? ' checked' : '' ?>> จำชื่อฉันไว้บนเครื่องนี้ (ครั้งหน้าแตะชื่อได้เลย)</label>
          <?php if ($known) { ?>
            <button type="button" class="lg-link" data-who="pick">‹ เลือกจากรายชื่อบนเครื่องนี้</button>
          <?php } ?>
          <?php if (!$hasStaff) { ?>
            <p class="staff-perm">ยังไม่มีพนักงานในระบบ — ผู้ดูแลเพิ่มพนักงานได้ที่เมนู "จัดการพนักงาน" (เข้าระบบที่แท็บผู้ดูแล)</p>
          <?php } ?>
        </div>

        <span class="lbl lbl-mt">กรอก PIN 4 หลัก</span>
        <div class="dots" id="dots" aria-live="polite" aria-label="จำนวนหลักที่กรอกแล้ว">
          <i></i><i></i><i></i><i></i>
        </div>

        <div class="keypad" id="keypad">
          <button type="button" data-k="1">1</button>
          <button type="button" data-k="2">2</button>
          <button type="button" data-k="3">3</button>
          <button type="button" data-k="4">4</button>
          <button type="button" data-k="5">5</button>
          <button type="button" data-k="6">6</button>
          <button type="button" data-k="7">7</button>
          <button type="button" data-k="8">8</button>
          <button type="button" data-k="9">9</button>
          <button type="button" class="k-soft" data-k="clear">ล้าง</button>
          <button type="button" data-k="0">0</button>
          <button type="button" class="k-soft" data-k="back" aria-label="ลบหนึ่งหลัก">
            <svg class="ico"><use href="#i-back"/></svg>
          </button>
        </div>
      </form>
      <?php if ($known) { /* ปุ่ม × บนปุ่มชื่อส่งฟอร์มนี้ (form="forget-form") — ฟอร์มซ้อนในฟอร์ม PIN ไม่ได้ */ ?>
      <form id="forget-form" method="post" action="login.php" hidden>
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="mode" value="forget">
      </form>
      <?php } ?>

      <!-- ===== แท็บ 2: ผู้ดูแล ===== -->
      <?php if (role_enabled('admin')) { ?>
      <form class="login-form pane<?php echo $mode === 'admin' ? ' on' : '' ?>" id="pane-admin"
            method="post" action="login.php" autocomplete="on">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="mode" value="admin">

        <div class="field">
          <label for="username">ชื่อผู้ใช้</label>
          <input class="input" type="text" id="username" name="adm_user"
                 value="<?php echo e($username) ?>" placeholder="เช่น somchai"
                 autocomplete="username" autocapitalize="none" spellcheck="false">
        </div>

        <div class="field">
          <label for="password">รหัสผ่าน</label>
          <div class="input-wrap">
            <input class="input" type="password" id="password" name="adm_pass"
                   placeholder="รหัสผ่าน" autocomplete="current-password">
            <button class="input-icon-btn" type="button" id="pw-toggle"
                    aria-label="แสดงรหัสผ่าน" aria-pressed="false">
              <svg class="ico"><use href="#i-eye"/></svg>
            </button>
          </div>
        </div>

        <div class="login-row">
          <label class="check"><input type="checkbox" name="remember" value="1"<?php echo !empty($_POST['remember']) ? ' checked' : '' ?>> จดจำการเข้าสู่ระบบ 30 วัน</label>
          <button type="button" class="link-btn" id="forgot-btn" aria-expanded="false" aria-controls="forgot-help">ลืมรหัสผ่าน?</button>
        </div>
        <div class="login-help" id="forgot-help" hidden>
          <b>ลืมรหัสผ่าน / บัญชีถูกล็อก</b>
          ให้ผู้ดูแลระบบเข้าหลังบ้าน (admweb) → AOSTOCK → <b>ผู้ดูแล POS</b> แล้วกด "ตั้งรหัสผ่านใหม่" ให้ — ตั้งแล้วบัญชีที่ถูกล็อกจะปลดล็อกด้วย
          และทุกเครื่องที่เคยจดจำไว้ต้องเข้าระบบใหม่ · ไม่ควรติ๊ก "จดจำ" บนเครื่องที่ใช้ร่วมกับคนอื่น
        </div>

        <button class="btn btn-primary btn-block" type="submit">
          <svg class="ico"><use href="#i-login"/></svg> เข้าสู่ระบบ
        </button>
      </form>
      <?php } ?>

      <p class="login-foot">ลืมชื่อผู้ใช้ / PIN หรือถูกล็อก — ติดต่อผู้จัดการสาขาหรือผู้ดูแลระบบ</p>
    </div>
  </section>

</main>

<!-- ===== กำลังเข้าสู่ระบบ: วงหมุนกลางจอ (แสดงตอนกดส่ง PIN / รหัสผ่าน) ===== -->
<div class="lg-busy" id="lg-busy" role="status" aria-live="polite" hidden>
  <div class="lg-busy-box">
    <span class="lg-spin" aria-hidden="true"></span>
    <b>กำลังเข้าสู่ระบบ…</b>
  </div>
</div>

<script>
(function () {
  /* ---- สลับแท็บ ---- */
  var tabs  = document.querySelectorAll('.lg-tabs button');
  var paneP = document.getElementById('pane-pin');
  var paneA = document.getElementById('pane-admin');
  for (var i = 0; i < tabs.length; i++) {
    tabs[i].addEventListener('click', function () {
      var k = this.getAttribute('data-tab');
      for (var j = 0; j < tabs.length; j++) {
        var on = tabs[j] === this;
        tabs[j].className = on ? 'on' : '';
        tabs[j].setAttribute('aria-selected', on ? 'true' : 'false');
      }
      paneP.className = 'login-form pane' + (k === 'pin' ? ' on' : '');
      if (paneA) { paneA.className = 'login-form pane' + (k === 'admin' ? ' on' : ''); }
    });
  }

  /* ---- เลือกพนักงาน ---- */
  var staffInput = document.getElementById('staff');
  var staffBtns  = document.querySelectorAll('.staff');
  for (var s = 0; s < staffBtns.length; s++) {
    staffBtns[s].addEventListener('click', function () {
      for (var t = 0; t < staffBtns.length; t++) {
        staffBtns[t].className = (staffBtns[t] === this) ? 'staff on' : 'staff';
      }
      staffInput.value = this.getAttribute('data-user');
      showPerm(this);
      clearPin();
      if (this.scrollIntoView) {
        this.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
      }
    });
  }

  /* ---- บรรทัดบอกสิทธิ์ของคนที่เลือก ---- */
  var permTxt = document.getElementById('perm-txt');
  function showPerm(btn) {
    if (!permTxt || !btn) { return; }
    /* สร้างด้วย textContent — ชื่อสาขามาจากผู้ดูแล ห้ามผ่าน innerHTML (ช่วงที่ 10) */
    var b = document.createElement('b');
    b.textContent = btn.getAttribute('data-role');
    permTxt.textContent = '';
    permTxt.appendChild(document.createTextNode('สิทธิ์ '));
    permTxt.appendChild(b);
    permTxt.appendChild(document.createTextNode(' · ' + btn.getAttribute('data-branch') + ' · เห็นข้อมูล' + btn.getAttribute('data-scope')));
  }
  for (var q = 0; q < staffBtns.length; q++) {
    if (staffBtns[q].className.indexOf('on') !== -1) { showPerm(staffBtns[q]); }
  }

  /* ---- แตะปุ่มชื่อ / พิมพ์ชื่อผู้ใช้ (ช่วงที่ 13) ---- */
  var via     = document.getElementById('via');
  var whoPick = document.getElementById('who-pick');
  var whoType = document.getElementById('who-type');
  var typed   = document.getElementById('staff-user');
  function setWho(k) {
    via.value = k;
    if (whoPick) { whoPick.hidden = (k !== 'pick'); }
    whoType.hidden = (k !== 'type');
    clearPin();
    if (k === 'type') { typed.focus(); }
  }
  var whoBtns = document.querySelectorAll('[data-who]');
  for (var w = 0; w < whoBtns.length; w++) {
    whoBtns[w].addEventListener('click', function () { setWho(this.getAttribute('data-who')); });
  }
  /* Enter ในช่องชื่อผู้ใช้ = ไปกด PIN ต่อ (ไม่ส่งฟอร์มทั้งที่ยังไม่มี PIN · มือถือปิดแป้นพิมพ์ให้เห็นแป้น PIN) */
  typed.addEventListener('keydown', function (ev) {
    if (ev.key === 'Enter') { ev.preventDefault(); typed.blur(); }
  });
  typed.addEventListener('input', function () { typed.setCustomValidity(''); });
  function needName() {
    if (via.value !== 'type' || typed.value.trim() !== '') { return false; }
    typed.setCustomValidity('กรุณากรอกชื่อผู้ใช้ก่อน');
    typed.reportValidity();
    typed.focus();
    return true;
  }
  if (via.value === 'type' && typed.value === '') { typed.focus(); }

  /* ---- ปุ่ม × เอาชื่อออกจากเครื่องนี้ ---- */
  var xs = document.querySelectorAll('.staff-x');
  for (var x = 0; x < xs.length; x++) {
    xs[x].addEventListener('click', function (ev) {
      if (!confirm(this.getAttribute('data-ask'))) { ev.preventDefault(); }
    });
  }

  /* ---- แป้น PIN ---- */
  var pinInput = document.getElementById('pin');
  var dots     = document.querySelectorAll('#dots i');
  var buf      = '';

  function paint() {
    for (var d = 0; d < dots.length; d++) {
      dots[d].className = (d < buf.length) ? 'f' : '';
    }
    pinInput.value = buf;
  }
  function clearPin() { buf = ''; paint(); }

  /* ---- วงหมุนกลางจอระหว่างส่งไปตรวจ (ข้อ ก: ขึ้นทันทีที่ส่ง · ผิด = หน้าโหลดใหม่พร้อมข้อความเหมือนเดิม) ---- */
  var busy    = document.getElementById('lg-busy');
  var sending = false;
  function showBusy() {
    sending = true;
    if (busy) { busy.hidden = false; }
  }
  /* กดย้อนกลับมาหน้านี้ (เบราว์เซอร์เก็บหน้าไว้ทั้งหน้า) → ซ่อนวงหมุน ล้าง PIN ให้กรอกใหม่ได้ */
  window.addEventListener('pageshow', function (ev) {
    if (ev.persisted) {
      sending = false;
      if (busy) { busy.hidden = true; }
      clearPin();
    }
  });
  if (paneA) {
    paneA.addEventListener('submit', function () { showBusy(); });
  }

  function press(k) {
    if (sending) { return; }                               // กำลังส่งอยู่ — กันกดซ้ำ
    if (k === 'clear') { clearPin(); return; }
    if (k === 'back')  { buf = buf.slice(0, -1); paint(); return; }
    if (buf.length >= 4) { return; }
    if (needName()) { return; }                            // พิมพ์ชื่อผู้ใช้ก่อนค่อยกด PIN
    buf += k;
    paint();
    if (buf.length === 4) {
      showBusy();
      setTimeout(function () { paneP.submit(); }, 140);   // ครบ 4 หลักส่งทันที
    }
  }

  var keys = document.querySelectorAll('#keypad button');
  for (var n = 0; n < keys.length; n++) {
    keys[n].addEventListener('click', function () { press(this.getAttribute('data-k')); });
  }

  /* พิมพ์จากคีย์บอร์ดได้ด้วย (สำหรับโน้ตบุ๊ก) */
  document.addEventListener('keydown', function (ev) {
    if (paneP.className.indexOf('on') === -1) { return; }
    if (ev.target === typed) { return; }                  // กำลังพิมพ์ชื่อผู้ใช้ — ตัวเลขไม่ลงช่อง PIN
    if (ev.key >= '0' && ev.key <= '9') { press(ev.key); }
    else if (ev.key === 'Backspace')    { ev.preventDefault(); press('back'); }
    else if (ev.key === 'Escape')       { press('clear'); }
  });
  paint();

  /* ---- ลืมรหัสผ่าน: แสดง / ซ่อนวิธีรีเซ็ต ---- */
  var fb = document.getElementById('forgot-btn');
  var fh = document.getElementById('forgot-help');
  if (fb && fh) {
    fb.addEventListener('click', function () {
      fh.hidden = !fh.hidden;
      fb.setAttribute('aria-expanded', String(!fh.hidden));
    });
  }

  /* ---- แสดง/ซ่อนรหัสผ่าน ---- */
  var pw = document.getElementById('password');
  var tg = document.getElementById('pw-toggle');
  if (pw && tg) {
    tg.addEventListener('click', function () {
      var show = pw.type === 'password';
      pw.type = show ? 'text' : 'password';
      tg.setAttribute('aria-pressed', String(show));
      tg.setAttribute('aria-label', show ? 'ซ่อนรหัสผ่าน' : 'แสดงรหัสผ่าน');
      tg.querySelector('use').setAttribute('href', show ? '#i-eye-off' : '#i-eye');
      pw.focus();
    });
  }
})();
</script>
</body>
</html>
