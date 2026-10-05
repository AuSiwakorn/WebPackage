<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/auth.php
 * ROLE: เข้าระบบ / ออกจากระบบ / CSRF / จดจำการเข้าสู่ระบบ 30 วัน · กันหน้าตามบทบาท สิทธิ์ และเมนูที่ปิด (require_login)
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_staff, ao_stock_remember
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *
 * ⚠ ไฟล์นี้ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb — มีได้แค่ define() และประกาศ function (ห้าม echo / header / query ตอนโหลด)
 */

/* ==========================================================
   เข้าระบบ — ตรวจกับ ao_stock_staff (PIN / รหัสผ่านเก็บเป็น password_hash)
   - พนักงาน: แตะชื่อ + PIN 4 หลัก · ผู้ดูแล / ฝ่ายบัญชี: ชื่อผู้ใช้ + รหัสผ่าน
   - กรอกผิด 5 ครั้งล็อกบัญชี 15 นาที (staff_login_failed — ในตาราง) + ล็อก session 1 นาที (login_failed)
   - จดจำการเข้าสู่ระบบ 30 วัน เฉพาะผู้ดูแล / ฝ่ายบัญชี (ตาราง ao_stock_remember · remember_*)
   - session เก็บเฉพาะผู้ใช้ที่เข้าระบบอยู่ + ของที่ทำค้าง (ตะกร้า / ใบร่าง) — ข้อมูลร้านอยู่ในฐานข้อมูลทั้งหมด
   ========================================================== */

/* ---------- CSRF ---------- */

/** สุ่มสตริงสำหรับ CSRF token (64 ตัวอักษร hex)
    TODO:
      - [x] ช่วงที่ 10: เปลี่ยนชื่อจาก demo_random_token · PHP 8 ใช้ random_bytes ได้เสมอ */
function csrf_random_token()
{
    return bin2hex(random_bytes(32));
}

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = csrf_random_token();
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

/* ---------- จำกัดจำนวนครั้งที่กรอกผิดใน session เดียวกัน (ล็อก 1 นาที) — ล็อกรายบัญชีอยู่ที่ staff_login_failed ---------- */
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
        'id'       => isset($u['id']) ? (int) $u['id'] : 0,
        'username' => $key,
        'name'     => $u['name'],
        'role'     => $u['role'],
        'branch'   => $u['branch'],
        'initials' => $u['initials'],
        'perms'    => isset($u['perms']) ? $u['perms'] : array(),
    );
}

/** เข้าระบบด้วย PIN — ใช้กับพนักงานหน้างาน (แท็บเล็ต/มือถือ)
    ผู้เรียกต้องเช็ก staff_locked_left() ก่อน และเรียก staff_login_failed() / staff_login_ok() ตามผล
    TODO:
      - [x] password_verify กับ pin_hash ในฐานข้อมูล */
function attempt_pin_login($username, $pin)
{
    $users = users_active();
    $key   = strtolower(trim($username));
    if (!isset($users[$key]) || $users[$key]['role'] !== 'staff') {
        return null;                       // ผู้ดูแล / บัญชี เข้าด้วยชื่อผู้ใช้ + รหัสผ่านเท่านั้น
    }
    $pin = preg_replace('/\D/', '', (string) $pin);
    $row = staff_auth_row($key);
    if ($row === null || $row['pin_hash'] === '' || !password_verify($pin, $row['pin_hash'])) {
        return null;
    }
    if (password_needs_rehash($row['pin_hash'], PASSWORD_DEFAULT)) {
        staff_set_pin($key, $pin);
    } elseif ((string) $row['pin_fp'] === '' && ($fp = pin_fp($pin)) !== null) {
        sdb_update('staff', array('pin_fp' => $fp), array('username' => $key));     // พนักงานเดิม: เก็บลายนิ้วมือ PIN ตอนเข้าระบบครั้งแรกหลังช่วงที่ 10
        staff_db_reset();
    }
    return user_session_row($key, $users[$key]);
}

/** ตรวจรหัสผ่านของผู้ดูแล / ฝ่ายบัญชี
    TODO:
      - [x] password_verify กับ password_hash ในฐานข้อมูล */
function attempt_login($username, $password)
{
    $users = users_active();
    $key   = strtolower(trim($username));
    if (!isset($users[$key]) || !in_array($users[$key]['role'], array('admin', 'account'), true)) {
        return null;                       // แท็บนี้สำหรับผู้ดูแลและบัญชี — พนักงานใช้ PIN
    }
    $row = staff_auth_row($key);
    if ($row === null || $row['password_hash'] === '' || !password_verify((string) $password, $row['password_hash'])) {
        return null;
    }
    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        staff_set_password($key, (string) $password);
    }
    return user_session_row($key, $users[$key]);
}

function login_user($user)
{
    login_reset_fail();
    session_regenerate_id(true);
    $_SESSION['user']           = $user;
    $_SESSION['pos_login_time'] = time();   // ไม่ใช้ชื่อ login_time — ชนกับ session ของ admweb
}

function logout_user()
{
    remember_forget();                     // ลบการจดจำของเครื่องนี้ (ช่วงที่ 10)
    /* ข้อมูลร้านทั้งหมดอยู่ในฐานข้อมูลแล้ว (ช่วงที่ 7) — session เหลือแค่ของผู้ใช้คนนั้น (ตะกร้า / ใบที่ทำค้าง)
       ล้างเฉพาะคีย์ของ POS ไม่ทำลายทั้ง session เพราะ session เดียวกันใช้ร่วมกับหลังบ้าน admweb (mainApi.php) */
    $personal = array('user', 'pos_login_time', 'cart', 'recv_draft', 'issue_draft', 'issue_meta',
                      'issue_flash', 'adj_draft', 'adj_meta', 'adj_snap', 'draft_edit_of');
    foreach ($personal as $k) {
        unset($_SESSION[$k]);
    }
    session_regenerate_id(true);
}

/* ---------- จดจำการเข้าสู่ระบบ 30 วัน (ผู้ดูแล / ฝ่ายบัญชี ติ๊ก "จดจำการเข้าสู่ระบบ") ----------
   cookie aostock_rm = selector:validator (httponly · SameSite=Lax · secure เมื่อเป็น https · path = โฟลเดอร์ของเว็บ)
   ตาราง ao_stock_remember เก็บ selector + sha256(validator) — ใช้แล้วเปลี่ยน validator ใหม่ทุกครั้ง
   validator ผิด (cookie อาจถูกขโมย) / เปลี่ยนรหัสผ่าน / พักงาน / ลบบัญชี = ลบทุกเครื่องของคนนั้น · ออกจากระบบ = ลบเครื่องนี้ */
define('REMEMBER_DAYS', 30);
define('REMEMBER_COOKIE', 'aostock_rm');

/** ตั้ง / ลบ cookie จดจำ ($value = '' คือลบ)
    TODO:
      - [x] ช่วงที่ 10 */
function remember_cookie($value, $expires)
{
    if (headers_sent()) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    setcookie(REMEMBER_COOKIE, $value, array('expires' => $expires, 'path' => APP_BASE . '/', 'secure' => $https,
                                             'httponly' => true, 'samesite' => 'Lax'));
    if ($value === '') {
        unset($_COOKIE[REMEMBER_COOKIE]);
    }
}

/** เริ่มจดจำเครื่องนี้ให้ผู้ใช้ที่เพิ่งเข้าระบบ (เรียกหลัง login_user)
    TODO:
      - [x] ช่วงที่ 10: ข้อ 3ก */
function remember_issue($user)
{
    $uid = stock_uid($user);
    if ($uid <= 0) {
        return false;
    }
    $sel = bin2hex(random_bytes(12));
    $val = bin2hex(random_bytes(32));
    $exp = time() + REMEMBER_DAYS * 86400;
    sdb_q('DELETE FROM ' . sdb_tb('remember') . ' WHERE expires_at < ?', array(date('Y-m-d H:i:s')));      // เก็บกวาดแถวที่หมดอายุ
    sdb_insert('remember', array(
        'staff_id'   => $uid,
        'selector'   => $sel,
        'token_hash' => hash('sha256', $val),
        'expires_at' => date('Y-m-d H:i:s', $exp),
        'user_agent' => stock_cut(isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '', 200),
        'add_date'   => date('Y-m-d H:i:s'),
    ));
    remember_cookie($sel . ':' . $val, $exp);
    return true;
}

/** ยังไม่ได้เข้าระบบแต่มี cookie จดจำ → ตรวจแล้วเข้าระบบให้ (เปลี่ยน validator ใหม่) · คืนผู้ใช้ หรือ null
    ไม่เข้าระบบให้ถ้า: หมดอายุ · บัญชีพักงาน / บทบาทถูกปิด / ไม่ใช่ผู้ดูแลหรือฝ่ายบัญชี · บัญชีถูกล็อกจากการกรอกผิด
    TODO:
      - [x] ช่วงที่ 10: เรียกจาก require_login และหน้า login */
function remember_login()
{
    $c = isset($_COOKIE[REMEMBER_COOKIE]) ? (string) $_COOKIE[REMEMBER_COOKIE] : '';
    if ($c === '') {
        return null;
    }
    if (!preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $c, $m)) {
        remember_cookie('', time() - 3600);
        return null;
    }
    $row = sdb_row('SELECT r.*, s.username FROM ' . sdb_tb('remember') . ' r JOIN ' . sdb_tb('staff') . ' s ON s.staff_id = r.staff_id'
                 . ' WHERE r.selector = ?', array($m[1]));
    if ($row === null || strtotime($row['expires_at']) < time()) {
        if ($row !== null) {
            sdb_q('DELETE FROM ' . sdb_tb('remember') . ' WHERE remember_id = ?', array((int) $row['remember_id']));
        }
        remember_cookie('', time() - 3600);
        return null;
    }
    if (!hash_equals((string) $row['token_hash'], hash('sha256', $m[2]))) {
        sdb_q('DELETE FROM ' . sdb_tb('remember') . ' WHERE staff_id = ?', array((int) $row['staff_id']));   // อาจถูกขโมย cookie
        remember_cookie('', time() - 3600);
        return null;
    }
    $users = users_active();
    $key   = $row['username'];
    if (!isset($users[$key]) || !in_array($users[$key]['role'], array('admin', 'account'), true) || staff_locked_left($key) > 0) {
        sdb_q('DELETE FROM ' . sdb_tb('remember') . ' WHERE remember_id = ?', array((int) $row['remember_id']));
        remember_cookie('', time() - 3600);
        return null;
    }
    $user = user_session_row($key, $users[$key]);
    login_user($user);
    staff_login_ok($key);
    $val = bin2hex(random_bytes(32));
    sdb_update('remember', array('token_hash' => hash('sha256', $val), 'last_used' => date('Y-m-d H:i:s')),
               array('remember_id' => (int) $row['remember_id']));
    remember_cookie($m[1] . ':' . $val, strtotime($row['expires_at']));
    return $user;
}

/** ลบการจดจำของเครื่องนี้ (ออกจากระบบ) */
function remember_forget()
{
    $c = isset($_COOKIE[REMEMBER_COOKIE]) ? (string) $_COOKIE[REMEMBER_COOKIE] : '';
    if ($c === '') {
        return;
    }
    if (preg_match('/^([a-f0-9]{24}):/', $c, $m)) {
        sdb_q('DELETE FROM ' . sdb_tb('remember') . ' WHERE selector = ?', array($m[1]));
    }
    remember_cookie('', time() - 3600);
}

/** ลบการจดจำทุกเครื่องของผู้ใช้คนนี้ (เปลี่ยนรหัสผ่าน / พักงาน) */
function remember_forget_staff($username)
{
    $r = staff_auth_row($username);
    if ($r !== null) {
        sdb_q('DELETE FROM ' . sdb_tb('remember') . ' WHERE staff_id = ?', array((int) $r['staff_id']));
    }
}

/** ชื่อไฟล์ของหน้าที่กำลังเปิด เช่น sale.php
    หน้าเว็บวิ่งผ่าน index.php ของ admweb → SCRIPT_NAME เป็น index.php ทุกหน้า จึงอ่านชื่อจาก router ($_getdata['page'])
    TODO:
      - [x] อ่านจาก $_getdata['page'] ที่ index.php ตั้งให้ · ไม่มี (เรียกนอก router) ใช้ชื่อสคริปต์แทน */
function current_page()
{
    global $_getdata;
    if (isset($_getdata['page']) && is_string($_getdata['page']) && $_getdata['page'] !== '') {
        return $_getdata['page'];
    }
    return basename($_SERVER['SCRIPT_NAME']);
}

/** ใส่บรรทัดนี้ไว้บนสุดของทุกหน้าที่ต้องล็อกอินก่อน */
function require_login()
{
    if (!is_logged_in() && remember_login() === null) {     // จดจำการเข้าสู่ระบบ 30 วัน (ช่วงที่ 10)
        header('Location: ' . url('login.php'));
        exit;
    }
    $u = current_user();
    /* ฝ่ายบัญชีเข้าได้เฉพาะหน้าของบัญชี — ไม่เห็นงานขาย/งานคลัง */
    $page = current_page();
    if ($u['role'] === 'account' && !in_array($page, account_pages(), true)) {
        header('Location: ' . url('account.php'));
        exit;
    }
    /* พนักงานที่ถูกปิดใช้งาน / บทบาทถูกปิดจากหลังบ้าน ระหว่างที่ยังเข้าระบบอยู่ → ออกจากระบบ */
    $all = users_all();
    if (!isset($all[$u['username']]) || !user_active($all[$u['username']]) || !role_enabled($u['role'])) {
        logout_user();
        header('Location: ' . url('login.php'));
        exit;
    }
    /* หน้าของกลุ่มฟีเจอร์ที่ผู้ดูแลระบบปิดไว้ (หลังบ้าน admweb) → กลับหน้าแรก (ช่วงที่ 9) */
    if (in_array($page, feature_pages_off(), true)) {
        $_SESSION['flash'] = 'เมนูนี้ถูกปิดใช้งานอยู่ — ติดต่อผู้ดูแลระบบถ้าต้องการใช้';
        header('Location: ' . url(home_page($u)));
        exit;
    }
    /* ระบบใหม่ยังไม่มีสาขาเลย → ผู้ดูแลไปเพิ่มสาขาแรกก่อน (หน้าอื่นต้องมีสาขา) */
    if ($u['role'] === 'admin' && !branches_active() && !in_array($page, array('adm-branches.php', 'logout.php'), true)) {
        $_SESSION['flash'] = 'ยังไม่มีสาขาในระบบ — เพิ่มสาขาแรกก่อน แล้วจึงเพิ่มพนักงานและสินค้า';
        header('Location: ' . url('adm-branches.php'));
        exit;
    }
    /* แยกชุดหน้า: ผู้ดูแลใช้หน้า adm-* · พนักงานเข้าหน้า adm-* ไม่ได้ */
    $isAdm = (strpos($page, 'adm-') === 0);
    if ($isAdm && $u['role'] !== 'admin') {
        header('Location: ' . url(home_page($u)));
        exit;
    }
    if (!$isAdm && $u['role'] === 'admin' && !in_array($page, admin_shared_pages(), true)) {
        $map = admin_page_map();
        if (isset($map[$page]) && $map[$page] !== '') {
            $to = $map[$page];
            $qs = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
            if ($qs !== '' && $_SERVER['REQUEST_METHOD'] === 'GET' && in_array($page, array('products.php', 'movements.php'), true)) {
                $to .= '?' . $qs;                          // ส่งคำค้น / หมวดต่อไปด้วย
            }
        } else {
            $_SESSION['flash'] = 'หน้านี้เป็นงานของพนักงานที่ได้รับมอบหมาย — ผู้ดูแลตรวจสอบได้จากเมนูของผู้ดูแล';
            $to = home_page($u);
        }
        header('Location: ' . url($to));
        exit;
    }
    /* หน้าที่ต้องมีสิทธิ์เฉพาะ (ขาย / นำเข้า / เบิก / ตรวจนับ / ประวัติ / รับคืน) */
    $need = page_perm($page);
    if ($need !== '' && !can($u, $need)) {
        $pl = perm_list();
        $_SESSION['flash'] = 'ไม่มีสิทธิ์เข้าหน้า “' . $pl[$need]['short'] . '” — ติดต่อผู้ดูแลเพื่อเปิดสิทธิ์';
        header('Location: ' . url(home_page($u)));
        exit;
    }
    return $u;
}

/** หน้าที่ฝ่ายบัญชีเข้าได้ */
function account_pages()
{
    return array('account.php', 'account-settings.php', 'bill-print.php', 'logout.php');
}

/** หน้าแรกหลังเข้าระบบของแต่ละบทบาท */
function home_page($user)
{
    if ($user['role'] === 'account') {
        return 'account.php';
    }
    return $user['role'] === 'admin' ? 'adm-dashboard.php' : 'dashboard.php';
}

/** หน้าของพนักงาน → หน้าชุด adm- ที่คู่กันของผู้ดูแล ('' = ผู้ดูแลไม่ใช้หน้านี้ กลับหน้าแรก) */
function admin_page_map()
{
    return array(
        'dashboard.php'    => 'adm-dashboard.php',
        'products.php'     => 'adm-products.php',
        'issue.php'        => 'adm-issue.php',
        'receive.php'      => 'adm-receive.php',
        'categories.php'   => 'adm-categories.php',
        'return.php'       => 'adm-return.php',
        'history.php'      => 'adm-history.php',
        'movements.php'    => 'adm-movements.php',
        'report-sales.php' => 'adm-report.php',
        'store.php'        => '',
        'sale.php'         => '',
        'stocktake.php'    => '',
    );
}

/** หน้าที่ผู้ดูแลใช้ร่วมกับบทบาทอื่น (ไม่ต้องมีชุด adm-) */
function admin_shared_pages()
{
    return array('account.php', 'account-settings.php', 'bill-print.php', 'logout.php', 'login.php', 'index.php');
}
