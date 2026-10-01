<?php
/* ==========================================================
   AOSTOCK DEMO — การยืนยันตัวตนด้วย session (ยังไม่ต่อฐานข้อมูล)
   รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

require_once dirname(__FILE__) . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']);
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array(
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $secure,
        ));
    } else {
        // PHP 5.x – 7.2 ยังไม่รองรับรูปแบบ array
        session_set_cookie_params(0, '/', '', $secure, true);
    }
    session_start();
}

/* ---------- CSRF ---------- */
function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = demo_random_token();
    }
    return $_SESSION['csrf'];
}

function csrf_check($token)
{
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

/* ---------- สถานะผู้ใช้ ---------- */
function current_user()
{
    return isset($_SESSION['user']) ? $_SESSION['user'] : null;
}

function is_logged_in()
{
    return current_user() !== null;
}

/* ---------- จำกัดจำนวนครั้งที่กรอกผิด (เดโม: เก็บใน session) ---------- */
function login_locked()
{
    if (empty($_SESSION['fail_until'])) {
        return 0;
    }
    $left = $_SESSION['fail_until'] - time();
    return $left > 0 ? $left : 0;
}

function login_failed()
{
    $n = isset($_SESSION['fail_count']) ? (int) $_SESSION['fail_count'] : 0;
    $n++;
    $_SESSION['fail_count'] = $n;
    if ($n >= 5) {
        $_SESSION['fail_until'] = time() + 60;   // ล็อก 1 นาที
        $_SESSION['fail_count'] = 0;
    }
}

function login_reset_fail()
{
    unset($_SESSION['fail_count'], $_SESSION['fail_until']);
}

/** แปลงข้อมูลผู้ใช้เป็นรูปที่เก็บใน session */
function user_session_row($key, $u)
{
    return array(
        'username' => $key,
        'name'     => $u['name'],
        'role'     => $u['role'],
        'branch'   => $u['branch'],
        'initials' => $u['initials'],
        'perms'    => isset($u['perms']) ? $u['perms'] : array(),
    );
}

/** เข้าระบบด้วย PIN — ใช้กับพนักงานหน้างาน (แท็บเล็ต/มือถือ) */
function attempt_pin_login($username, $pin)
{
    $users = demo_users();
    $key   = strtolower(trim($username));

    if (!isset($users[$key]) || $users[$key]['role'] !== 'staff') {
        return null;                       // ผู้ดูแล / บัญชี เข้าด้วยชื่อผู้ใช้ + รหัสผ่านเท่านั้น
    }
    $pin = preg_replace('/\D/', '', (string) $pin);
    if (!hash_equals($users[$key]['pin'], $pin)) {
        return null;
    }
    return user_session_row($key, $users[$key]);
}

/** ตรวจสอบรหัสผ่าน — ระบบจริงเปลี่ยนเป็น query + password_verify() */
function attempt_login($username, $password)
{
    $users = demo_users();
    $key   = strtolower(trim($username));

    if (!isset($users[$key]) || !in_array($users[$key]['role'], array('admin', 'account'), true)) {
        return null;                       // แท็บนี้สำหรับผู้ดูแลและบัญชี — พนักงานใช้ PIN
    }
    $u = $users[$key];
    if (!hash_equals($u['password'], (string) $password)) {
        return null;
    }

    return user_session_row($key, $u);
}

function login_user($user)
{
    login_reset_fail();
    session_regenerate_id(true);
    $_SESSION['user']       = $user;
    $_SESSION['login_time'] = time();
}

function logout_user()
{
    /* เดโม: ข้อมูลร้าน (บิล ใบรับเข้า ประวัติ เปิด–ปิดร้าน สต๊อก) อยู่ใน session เดียวกัน
       หน้าร้านใช้แท็บเล็ตเครื่องเดียวร่วมกันหลายคน → ออกจากระบบล้างเฉพาะของผู้ใช้คนนั้น
       (ตะกร้า / ใบที่ทำค้าง) คนถัดไปที่เข้าระบบจึงเห็นเอกสารของคนก่อนหน้าได้
       ระบบจริง: ข้อมูลอยู่ในฐานข้อมูล ตรงนี้เหลือแค่ล้าง session ตามปกติ */
    $personal = array('user', 'login_time', 'cart', 'recv_draft', 'issue_draft', 'issue_meta',
                      'issue_flash', 'adj_draft', 'adj_meta', 'adj_snap');
    foreach ($personal as $k) {
        unset($_SESSION[$k]);
    }
    session_regenerate_id(true);
}

/** ใส่บรรทัดนี้ไว้บนสุดของทุกหน้าที่ต้องล็อกอินก่อน */
function require_login()
{
    if (!is_logged_in()) {
        header('Location: ' . url('login.php'));
        exit;
    }
    $u = current_user();
    /* ฝ่ายบัญชีเข้าได้เฉพาะหน้าของบัญชี — ไม่เห็นงานขาย/งานคลัง */
    if ($u['role'] === 'account' && !in_array(basename($_SERVER['SCRIPT_NAME']), account_pages(), true)) {
        header('Location: ' . url('account.php'));
        exit;
    }
    return $u;
}

/** หน้าที่ฝ่ายบัญชีเข้าได้ */
function account_pages()
{
    return array('account.php', 'account-settings.php', 'logout.php');
}

/** หน้าแรกหลังเข้าระบบของแต่ละบทบาท */
function home_page($user)
{
    return $user['role'] === 'account' ? 'account.php' : 'dashboard.php';
}
