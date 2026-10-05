<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/auth.php
 * ROLE: เข้าระบบ / ออกจากระบบ / CSRF / จดจำการเข้าสู่ระบบ 30 วัน · กันหน้าตามบทบาท สิทธิ์ และเมนูที่ปิด (require_login)
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_staff, ao_stock_remember, ao_stock_login_ip
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *   - [x] ช่วงที่ 11: เช็กสิทธิ์เข้าหน้าด้วย page_perm_ok (หน้าเดียวใช้ได้หลายสิทธิ์)
 *   - [x] ช่วงที่ 12: ผู้ดูแลเปิด team.php → ไปหน้าจัดการพนักงาน
 *   - [x] ช่วงที่ 13: พนักงานเข้าด้วยชื่อผู้ใช้ + PIN · เครื่องจำชื่อคนที่เคยเข้า (known_*) · จำกัดการกรอกผิดตาม IP (ip_* / client_ip หลัง Cloudflare)
 *
 * ⚠ ไฟล์นี้ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb — มีได้แค่ define() และประกาศ function (ห้าม echo / header / query ตอนโหลด)
 */

/* ==========================================================
   เข้าระบบ — ตรวจกับ ao_stock_staff (PIN / รหัสผ่านเก็บเป็น password_hash)
   - พนักงาน: ชื่อผู้ใช้ + PIN 4 หลัก (เครื่องที่เคยเข้าแล้วแตะปุ่มชื่อแทนการพิมพ์ — known_*) · ผู้ดูแล / ฝ่ายบัญชี: ชื่อผู้ใช้ + รหัสผ่าน
   - กรอกผิด 5 ครั้งล็อกบัญชี 15 นาที (staff_login_failed — ในตาราง) + ล็อก session 1 นาที (login_failed)
     + ทุกบัญชีรวมกันผิด 20 ครั้งใน 15 นาทีจาก IP เดียว ล็อก IP นั้น 15 นาที (ip_login_failed · ช่วงที่ 13)
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

/** ตั้ง / ลบ cookie ของ POS ($value = '' คือลบ) — httponly · SameSite=Lax · secure เมื่อเป็น https · path = โฟลเดอร์ของเว็บ
    TODO:
      - [x] ช่วงที่ 13: แยกจาก remember_cookie ให้ cookie เครื่องจำชื่อ (known_save) ใช้ด้วย */
function pos_cookie($name, $value, $expires)
{
    if (headers_sent()) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    setcookie($name, $value, array('expires' => $expires, 'path' => APP_BASE . '/', 'secure' => $https,
                                   'httponly' => true, 'samesite' => 'Lax'));
    if ($value === '') {
        unset($_COOKIE[$name]);
    }
}

/** ตั้ง / ลบ cookie จดจำ ($value = '' คือลบ)
    TODO:
      - [x] ช่วงที่ 10
      - [x] ช่วงที่ 13: ใช้ pos_cookie */
function remember_cookie($value, $expires)
{
    pos_cookie(REMEMBER_COOKIE, $value, $expires);
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

/* ---------- จำกัดการกรอกผิดตาม IP (ช่วงที่ 13) ----------
   ล็อกรายบัญชี (staff_login_failed) กันการสุ่ม PIN ของคนเดียว แต่คนนอกยังสุ่มบัญชีละไม่กี่ครั้งกับหลายบัญชีได้
   → นับการกรอกผิดของทุกบัญชีรวมกันต่อ IP (ตาราง ao_stock_login_ip) · ผิดครบ IP_LOCK_FAILS ครั้งภายใน IP_LOCK_MINUTES นาที
     = IP นั้นเข้าระบบไม่ได้ IP_LOCK_MINUTES นาที (ทั้งแท็บ PIN และแท็บผู้ดูแล) · เข้าระบบสำเร็จไม่ล้างตัวนับ (หมดรอบเอง)
   ยังไม่ได้กด Reinstall (ยังไม่มีตาราง) = ข้ามการจำกัดตาม IP ไม่ทำให้เข้าระบบไม่ได้ */
define('IP_LOCK_FAILS', 20);
define('IP_LOCK_MINUTES', 15);

/** ช่วง IP ของ Cloudflare (https://www.cloudflare.com/ips/) — เว็บอยู่หลัง Cloudflare: REMOTE_ADDR เป็นเครื่องของ Cloudflare ไม่ใช่ผู้ใช้
    TODO:
      - [x] ช่วงที่ 13
      - [ ] Cloudflare ประกาศช่วงใหม่เมื่อไร เติมที่นี่ (ไม่เติม = ผู้ใช้ที่ผ่านช่วงใหม่ถูกนับรวมเป็น IP ของ Cloudflare) */
function cf_ip_ranges()
{
    return array(
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
        '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
        '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
        '2a06:98c0::/29', '2c0f:f248::/32',
    );
}

/** $ip อยู่ในช่วง $cidr ไหม (IPv4 / IPv6)
    TODO:
      - [x] ช่วงที่ 13 */
function ip_in_cidr($ip, $cidr)
{
    $p = explode('/', $cidr, 2);
    $a = @inet_pton((string) $ip);
    $b = @inet_pton($p[0]);
    if ($a === false || $b === false || strlen($a) !== strlen($b)) {
        return false;
    }
    $bits  = isset($p[1]) ? (int) $p[1] : strlen($b) * 8;
    $bytes = intdiv($bits, 8);
    if (substr($a, 0, $bytes) !== substr($b, 0, $bytes)) {
        return false;
    }
    $rest = $bits % 8;
    if ($rest === 0) {
        return true;
    }
    $mask = (0xFF << (8 - $rest)) & 0xFF;
    return (ord($a[$bytes]) & $mask) === (ord($b[$bytes]) & $mask);
}

/** IP ของผู้ใช้ที่ส่งคำขอนี้
    - ผ่านเครื่องตัวกลางในวงภายใน (REMOTE_ADDR เป็น 127.x / 10.x / 192.168.x ฯลฯ) → ใช้ IP ท้ายสุดใน X-Forwarded-For (ตัวกลางเติมให้)
    - เครื่องที่ต่อเข้ามาเป็นของ Cloudflare → ใช้ CF-Connecting-IP
    - นอกนั้นใช้ REMOTE_ADDR — header ที่คนนอกปลอมส่งตรงเข้าเซิร์ฟเวอร์ไม่มีผล
    TODO:
      - [x] ช่วงที่ 13 */
function client_ip()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '';
    if (stripos($ip, '::ffff:') === 0 && strpos($ip, '.') !== false) {
        $ip = substr($ip, 7);                                  // IPv4 ที่เขียนในรูป IPv6
    }
    $local = filter_var($ip, FILTER_VALIDATE_IP) && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    if ($local && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $xff  = array_map('trim', explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']));
        $last = end($xff);
        if (filter_var($last, FILTER_VALIDATE_IP)) {
            $ip = $last;
        }
    }
    $cf = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? trim((string) $_SERVER['HTTP_CF_CONNECTING_IP']) : '';
    if ($cf !== '' && filter_var($cf, FILTER_VALIDATE_IP)) {
        foreach (cf_ip_ranges() as $r) {
            if (ip_in_cidr($ip, $r)) {
                return $cf;
            }
        }
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/** คีย์ของ IP ในตาราง — IPv6 นับรวมทั้งวง /64 (เครื่องเดียวเปลี่ยน IPv6 ในวงเดียวกันได้เรื่อย ๆ)
    TODO:
      - [x] ช่วงที่ 13 */
function ip_key($ip)
{
    $bin = @inet_pton((string) $ip);
    if ($bin !== false && strlen($bin) === 16) {
        return inet_ntop(substr($bin, 0, 8) . str_repeat("\0", 8)) . '/64';
    }
    return (string) $ip;
}

/** มีตาราง ao_stock_login_ip แล้วหรือยัง (ยังไม่ได้กด Reinstall = ยังไม่มี) — เช็กครั้งเดียวต่อ request ไม่ให้ error_log เต็ม
    TODO:
      - [x] ช่วงที่ 13 */
function ip_table_ok()
{
    static $ok = null;
    if ($ok === null) {
        $ok = (sdb_val('SHOW TABLES LIKE ?', array(str_replace('_', '\_', _DBPREFIX_ . 'stock_login_ip'))) !== null);
    }
    return $ok;
}

/** IP นี้ถูกล็อกจากการกรอกผิดอยู่ไหม — คืนจำนวนวินาทีที่เหลือ (0 = ไม่ล็อก)
    TODO:
      - [x] ช่วงที่ 13 */
function ip_locked_left()
{
    if (!ip_table_ok()) {
        return 0;
    }
    $until = sdb_val('SELECT locked_until FROM ' . sdb_tb('login_ip') . ' WHERE ip = ?', array(ip_key(client_ip())));
    $left  = ($until !== null) ? strtotime($until) - time() : 0;
    return $left > 0 ? $left : 0;
}

/** กรอกผิด 1 ครั้งจาก IP นี้ (บัญชีไหนก็ได้ รวมชื่อผู้ใช้ที่ไม่มีจริง) — รอบละ IP_LOCK_MINUTES นาทีนับจากครั้งแรก
    ครบ IP_LOCK_FAILS ครั้งในรอบเดียว = ล็อก IP_LOCK_MINUTES นาที แล้วเริ่มนับใหม่
    TODO:
      - [x] ช่วงที่ 13 */
function ip_login_failed()
{
    if (!ip_table_ok()) {
        return;
    }
    $tb  = sdb_tb('login_ip');
    $ip  = ip_key(client_ip());
    $now = date('Y-m-d H:i:s');
    $old = date('Y-m-d H:i:s', time() - IP_LOCK_MINUTES * 60);
    sdb_q('DELETE FROM ' . $tb . ' WHERE last_fail < ? AND (locked_until IS NULL OR locked_until < ?)',
          array(date('Y-m-d H:i:s', time() - 86400), $now));                       // เก็บกวาดแถวเก่า
    sdb_q('INSERT INTO ' . $tb . ' (ip, fail_count, first_fail, last_fail) VALUES (?, 1, ?, ?)'
        . ' ON DUPLICATE KEY UPDATE fail_count = IF(first_fail < ?, 1, fail_count + 1),'
        . ' first_fail = IF(first_fail < ?, ?, first_fail), last_fail = ?',
          array($ip, $now, $now, $old, $old, $now, $now));
    if ((int) sdb_val('SELECT fail_count FROM ' . $tb . ' WHERE ip = ?', array($ip)) >= IP_LOCK_FAILS) {
        sdb_q('UPDATE ' . $tb . ' SET fail_count = 0, first_fail = ?, locked_until = ? WHERE ip = ?',
              array($now, date('Y-m-d H:i:s', time() + IP_LOCK_MINUTES * 60), $ip));
    }
}

/* ---------- เครื่องจำชื่อพนักงาน (ช่วงที่ 13) ----------
   หน้าเข้าระบบไม่แสดงรายชื่อพนักงานทั้งหมดแล้ว — เครื่องที่ยังไม่เคยมีใครเข้า ต้องพิมพ์ชื่อผู้ใช้ + PIN
   เข้าสำเร็จ (ติ๊ก "จำชื่อฉันไว้บนเครื่องนี้" หรือแตะปุ่มชื่อ) → cookie aostock_known เก็บ staff_id ของคนที่เคยเข้าบนเครื่องนี้
   (คนล่าสุดก่อน · สูงสุด KNOWN_MAX คน · KNOWN_DAYS วันนับจากเข้าครั้งล่าสุด) → ครั้งต่อไปขึ้นเป็นปุ่มชื่อให้แตะ
   ลงลายเซ็น HMAC ด้วย AOSTOCK_SECRET_KEY — แก้ cookie เองเพื่อดูชื่อคนอื่นไม่ได้ · ไม่มีกุญแจ = ไม่จำ (พิมพ์ชื่อผู้ใช้ทุกครั้ง)
   ออกจากระบบไม่ลบ (เป็นของเครื่อง ไม่ใช่ของ session) · ลบรายคนได้ที่ปุ่ม × บนหน้าเข้าระบบ */
define('KNOWN_COOKIE', 'aostock_known');
define('KNOWN_MAX', 12);
define('KNOWN_DAYS', 180);

/** ลายเซ็นของรายการ id ใน cookie (null = ไม่มีกุญแจ)
    TODO:
      - [x] ช่วงที่ 13 */
function known_sign($raw)
{
    if (!defined('AOSTOCK_SECRET_KEY') || strlen((string) AOSTOCK_SECRET_KEY) < 16) {
        return null;
    }
    return hash_hmac('sha256', 'known|' . $raw, (string) AOSTOCK_SECRET_KEY);
}

/** staff_id ที่เครื่องนี้จำไว้ (คนล่าสุดก่อน) — cookie ผิดรูป / ลายเซ็นไม่ตรง = ไม่มี
    TODO:
      - [x] ช่วงที่ 13 */
function known_ids()
{
    $c = isset($_COOKIE[KNOWN_COOKIE]) ? (string) $_COOKIE[KNOWN_COOKIE] : '';
    if (!preg_match('/^(\d{1,10}(?:-\d{1,10}){0,' . (KNOWN_MAX - 1) . '})\.([a-f0-9]{64})$/', $c, $m)) {
        return array();
    }
    $sig = known_sign($m[1]);
    if ($sig === null || !hash_equals($sig, $m[2])) {
        return array();
    }
    return array_map('intval', explode('-', $m[1]));
}

/** เขียนรายการ id ลง cookie (ว่าง = ลบ cookie)
    TODO:
      - [x] ช่วงที่ 13 */
function known_save($ids)
{
    $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', $ids)))), 0, KNOWN_MAX);
    $raw = implode('-', $ids);
    $sig = known_sign($raw);
    if (!$ids || $sig === null) {
        pos_cookie(KNOWN_COOKIE, '', time() - 3600);
        return;
    }
    $_COOKIE[KNOWN_COOKIE] = $raw . '.' . $sig;
    pos_cookie(KNOWN_COOKIE, $_COOKIE[KNOWN_COOKIE], time() + KNOWN_DAYS * 86400);
}

/** จำคนนี้ไว้บนเครื่อง (ขึ้นเป็นคนแรก) — เรียกหลังเข้าระบบด้วย PIN สำเร็จ
    TODO:
      - [x] ช่วงที่ 13 */
function known_add($user)
{
    $id = isset($user['id']) ? (int) $user['id'] : 0;
    if ($id > 0) {
        known_save(array_merge(array($id), array_diff(known_ids(), array($id))));
    }
}

/** เอาชื่อนี้ออกจากเครื่อง
    TODO:
      - [x] ช่วงที่ 13 */
function known_forget($id)
{
    known_save(array_diff(known_ids(), array((int) $id)));
}

/** พนักงานที่เครื่องนี้จำไว้และยังเข้าระบบด้วย PIN ได้ — array( username => ข้อมูล ) คนล่าสุดก่อน
    คนที่พักงาน / ถูกลบ / ไม่ใช่บทบาทพนักงานแล้ว ไม่แสดง (ไม่ต้องแก้ cookie) · เปลี่ยนชื่อผู้ใช้แล้วยังขึ้น (จำด้วย staff_id)
    TODO:
      - [x] ช่วงที่ 13 */
function known_staffs()
{
    $all  = users_active();
    $byId = array();
    foreach ($all as $k => $u) {
        if ($u['role'] === 'staff') {
            $byId[(int) $u['id']] = $k;
        }
    }
    $out = array();
    foreach (known_ids() as $id) {
        if (isset($byId[$id])) {
            $out[$byId[$id]] = $all[$byId[$id]];
        }
    }
    return $out;
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
    /* หน้าที่ต้องมีสิทธิ์เฉพาะ (page_perm — ช่วงที่ 11 บางหน้ามีได้หลายสิทธิ์ ตัวใดตัวหนึ่งก็เข้าได้) */
    if (!page_perm_ok($u, $page)) {
        $need = (array) page_perm($page);
        $pl   = perm_list();
        $_SESSION['flash'] = 'ไม่มีสิทธิ์เข้าหน้า “' . $pl[$need[0]]['short'] . '” — ติดต่อผู้ดูแลเพื่อเปิดสิทธิ์';
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
        'team.php'         => 'adm-users.php',      // ช่วงที่ 12: หน้าผู้จัดการสาขา → ผู้ดูแลใช้หน้าจัดการพนักงาน
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
