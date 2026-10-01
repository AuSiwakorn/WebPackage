<?php
require_once dirname(__FILE__) . '/inc/auth.php';

if (is_logged_in()) {
    header('Location: ' . url(home_page(current_user())));
    exit;
}

$error     = '';
$mode      = 'pin';                      // แท็บที่เปิดอยู่: pin | admin
$username  = '';
$staffPick = '';

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
    } elseif ($mode === 'pin') {
        $staffPick = isset($_POST['staff']) ? trim($_POST['staff']) : '';
        $pin       = isset($_POST['pin']) ? trim($_POST['pin']) : '';

        if ($staffPick === '') {
            $error = 'กรุณาเลือกชื่อของคุณก่อน';
        } elseif (strlen(preg_replace('/\D/', '', $pin)) !== 4) {
            $error = 'กรุณากรอก PIN ให้ครบ 4 หลัก';
        } else {
            $user = attempt_pin_login($staffPick, $pin);
            if ($user === null) {
                login_failed();
                $error = 'PIN ไม่ถูกต้อง';
            } else {
                login_user($user);
                header('Location: ' . url(home_page($user)));
                exit;
            }
        }
    } else {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) ? (string) $_POST['password'] : '';

        if ($username === '' || $password === '') {
            $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่านให้ครบ';
        } else {
            $user = attempt_login($username, $password);
            if ($user === null) {
                login_failed();
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            } else {
                login_user($user);
                header('Location: ' . url(home_page($user)));
                exit;
            }
        }
    }
}

$staffs = array();
foreach (demo_users() as $k => $u) {
    if ($u['role'] === 'staff') {                 // ผู้ดูแล / บัญชี ใช้แท็บชื่อผู้ใช้ + รหัสผ่าน
        $staffs[$k] = $u;
    }
}
if ($staffPick === '' || !isset($staffs[$staffPick])) {
    $keys      = array_keys($staffs);
    $staffPick = $keys[0];
}
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>เข้าสู่ระบบ | <?= e(APP_NAME) ?> — <?= e(APP_TITLE) ?></title>
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#07211B">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2064%2064'%3E%3Crect%20width='64'%20height='64'%20rx='14'%20fill='%2307211B'/%3E%3Cpath%20d='M14%2024l18-9%2018%209-18%209z'%20fill='%23C9A86A'/%3E%3Cpath%20d='M14%2024v16l18%209V33z'%20fill='%230E7A5F'/%3E%3Cpath%20d='M50%2024v16l-18%209V33z'%20fill='%2312946F'/%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/app.css">
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
  </defs>
</svg>

<main class="login">

  <!-- ===== ฝั่งซ้าย: แบรนด์ ===== -->
  <section class="login-brand">
    <div>
      <span class="logo logo--light"><span class="logo-ao">AO</span><span class="logo-sk">STOCK</span></span>
      <div class="brand-by"><?= e(APP_TITLE) ?> · by AOSOFT</div>
    </div>

    <div class="login-brand-mid">
      <h1>ระบบสำหรับพนักงาน<br>บริหารสต๊อกทุกสาขาในที่เดียว</h1>
      <p>แตะชื่อของคุณแล้วกด PIN 4 หลัก ระบบจะเปิดเฉพาะสาขาและเมนูที่คุณมีสิทธิ์เข้าถึง</p>

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
      © <?= date('Y') ?> <?= e(APP_OWNER) ?> ·
      <a href="https://www.aosoft.co.th/services/stock-system/" target="_blank" rel="noopener">ดูรายละเอียดบริการ</a>
    </div>
  </section>

  <!-- ===== ฝั่งขวา: ฟอร์ม ===== -->
  <section class="login-panel">
    <div class="login-card">

      <span class="demo-tag"><svg class="ico"><use href="#i-info"/></svg> ระบบทดลองใช้งาน</span>
      <h2>เข้าสู่ระบบ</h2>

      <?php if (role_enabled('admin')): ?>
      <div class="lg-tabs" role="tablist">
        <button type="button" class="<?= $mode === 'pin' ? 'on' : '' ?>" data-tab="pin" role="tab"
                aria-selected="<?= $mode === 'pin' ? 'true' : 'false' ?>">พนักงาน (PIN)</button>
        <button type="button" class="<?= $mode === 'admin' ? 'on' : '' ?>" data-tab="admin" role="tab"
                aria-selected="<?= $mode === 'admin' ? 'true' : 'false' ?>">ผู้ดูแล / บัญชี</button>
      </div>
      <?php endif; ?>

      <?php if ($error !== ''): ?>
        <div class="alert alert-error" role="alert">
          <svg class="ico"><use href="#i-alert"/></svg>
          <span><?= e($error) ?></span>
        </div>
      <?php endif; ?>

      <!-- ===== แท็บ 1: PIN ===== -->
      <form class="login-form pane<?= $mode === 'pin' ? ' on' : '' ?>" id="pane-pin"
            method="post" action="login.php" autocomplete="off">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="mode" value="pin">
        <input type="hidden" name="staff" id="staff" value="<?= e($staffPick) ?>">
        <input type="hidden" name="pin" id="pin" value="">

        <span class="lbl">เลือกชื่อของคุณ</span>
        <div class="staffs" id="staffs">
          <?php foreach ($staffs as $uname => $u): ?>
            <button class="staff<?= $uname === $staffPick ? ' on' : '' ?>" type="button" data-user="<?= e($uname) ?>"
                      data-role="<?= e(role_name($u['role'])) ?>"
                      data-scope="<?= e(role_scope($u['role'])) ?>"
                      data-branch="<?= e(branch_name($u['branch'])) ?>">
              <span class="av"><?= e(user_initial($u)) ?></span>
              <span class="st">
                <b><?= e($u['name']) ?></b>
                <small><?= e(role_name($u['role'])) ?> · <?= e(branch_name($u['branch'])) ?></small>
              </span>
            </button>
          <?php endforeach; ?>
        </div>

        <p class="staff-perm" id="staff-perm">
          <svg class="ico"><use href="#i-shield"/></svg>
          <span id="perm-txt"></span>
        </p>

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

      <!-- ===== แท็บ 2: ผู้ดูแล ===== -->
      <?php if (role_enabled('admin')): ?>
      <form class="login-form pane<?= $mode === 'admin' ? ' on' : '' ?>" id="pane-admin"
            method="post" action="login.php" autocomplete="on">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="mode" value="admin">

        <div class="field">
          <label for="username">ชื่อผู้ใช้</label>
          <input class="input" type="text" id="username" name="username"
                 value="<?= e($username) ?>" placeholder="เช่น somchai"
                 autocomplete="username" autocapitalize="none" spellcheck="false">
        </div>

        <div class="field">
          <label for="password">รหัสผ่าน</label>
          <div class="input-wrap">
            <input class="input" type="password" id="password" name="password"
                   placeholder="รหัสผ่าน" autocomplete="current-password">
            <button class="input-icon-btn" type="button" id="pw-toggle"
                    aria-label="แสดงรหัสผ่าน" aria-pressed="false">
              <svg class="ico"><use href="#i-eye"/></svg>
            </button>
          </div>
        </div>

        <div class="login-row">
          <label class="check"><input type="checkbox" name="remember" value="1"> จดจำการเข้าสู่ระบบ</label>
          <a href="#" onclick="return false">ลืมรหัสผ่าน?</a>
        </div>

        <button class="btn btn-primary btn-block" type="submit">
          <svg class="ico"><use href="#i-login"/></svg> เข้าสู่ระบบ
        </button>
      </form>
      <?php endif; ?>

      <!-- บัญชีสำหรับทดลอง -->
      <details class="demo-accounts"<?= $error !== '' ? ' open' : '' ?>>
        <summary>บัญชีสำหรับทดลอง — แตะเพื่อดู PIN</summary>
        <ul>
          <?php foreach ($staffs as $uname => $u): ?>
            <li>
              <span class="du-n"><?= e($u['name']) ?> · <?= e(role_name($u['role'])) ?></span>
              <span class="du-k">PIN <b><?= e($u['pin']) ?></b></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <?php if (role_enabled('admin')): ?>
          <p>ผู้ดูแล: ชื่อผู้ใช้ <b>somchai</b> รหัสผ่าน <b>1234</b></p>
          <p>ฝ่ายบัญชี: ชื่อผู้ใช้ <b>pim</b> รหัสผ่าน <b>1234</b></p>
        <?php else: ?>
          <p>เฟสนี้เปิดเฉพาะสิทธิ์พนักงาน สิทธิ์ผู้ดูแลและหัวหน้าสาขาจะเปิดในขั้นถัดไป</p>
        <?php endif; ?>
      </details>

      <p class="login-foot">ข้อมูลในระบบทดลองเป็นข้อมูลสมมติทั้งหมด ยังไม่เชื่อมต่อฐานข้อมูลจริง</p>
    </div>
  </section>

</main>

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
    permTxt.innerHTML = 'สิทธิ์ <b>' + btn.getAttribute('data-role') + '</b> · ' +
                        btn.getAttribute('data-branch') + ' · เห็นข้อมูล' +
                        btn.getAttribute('data-scope');
  }
  for (var q = 0; q < staffBtns.length; q++) {
    if (staffBtns[q].className.indexOf('on') !== -1) { showPerm(staffBtns[q]); }
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

  function press(k) {
    if (k === 'clear') { clearPin(); return; }
    if (k === 'back')  { buf = buf.slice(0, -1); paint(); return; }
    if (buf.length >= 4) { return; }
    buf += k;
    paint();
    if (buf.length === 4) {
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
    if (ev.key >= '0' && ev.key <= '9') { press(ev.key); }
    else if (ev.key === 'Backspace')    { ev.preventDefault(); press('back'); }
    else if (ev.key === 'Escape')       { press('clear'); }
  });
  paint();

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
