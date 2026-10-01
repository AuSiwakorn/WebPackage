<?php
/* ==========================================================
   AOSTOCK DEMO — ค่าคงที่และข้อมูลตัวอย่าง
   ----------------------------------------------------------
   เดโมนี้ยังไม่ต่อฐานข้อมูล ข้อมูลทั้งหมดเก็บเป็น array ในไฟล์นี้
   เมื่อทำระบบจริง: ย้าย demo_users() ไปเป็นตารางในฐานข้อมูล
   และเก็บรหัสผ่านด้วย password_hash() / ตรวจด้วย password_verify()

   เขียนให้รองรับ PHP 5.4 ขึ้นไป (ไม่ใช้ ??, <=>, const array)
   ========================================================== */

define('APP_NAME',  'AOSTOCK');
define('APP_TITLE', 'ระบบบริหารสต๊อกสินค้า');
define('APP_OWNER', 'บริษัท เอโอซอฟต์ จำกัด');

$__script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/';
define('APP_BASE', rtrim(str_replace('\\', '/', dirname($__script)), '/'));
unset($__script);

/* ==========================================================
   สวิตช์ขอบเขตของเดโม — ทำทีละสิทธิ์ ทีละเมนู
   ----------------------------------------------------------
   จะเปิดสิทธิ์หรือเมนูเพิ่ม ให้แก้แค่สองฟังก์ชันนี้ที่เดียว
   ========================================================== */

/** สิทธิ์ที่เปิดใช้งานอยู่ตอนนี้ */
function active_roles()
{
    return array('staff', 'admin', 'account');   // ไม่มีหัวหน้าคลัง — ใช้สิทธิ์เสริมของพนักงานแทน
}

/** เมนูที่ทำเสร็จแล้วและเปิดให้ใช้ */
function active_menus()
{
    return array('dashboard.php', 'store.php', 'sale.php', 'products.php', 'receive.php', 'issue.php', 'stocktake.php', 'movements.php',
                 'history.php', 'return.php', 'report-sales.php', 'users.php', 'branches.php',
                 'account.php', 'account-settings.php');
}

/** ส่วนประกอบที่ยังไม่ได้ใช้ เปิดทีหลังโดยเติมชื่อลงใน array นี้
    'search'      = ช่องค้นหาบนแถบบน
    'branch_pick' = ตัวเลือกสาขาบนแถบบน (พนักงานผูกสาขาเดียวอยู่แล้ว) */
function active_features()
{
    return array();
}

function feature_enabled($f)
{
    return in_array($f, active_features(), true);
}

function role_enabled($role)
{
    return in_array($role, active_roles(), true);
}

function menu_enabled($file)
{
    return in_array($file, active_menus(), true);
}

/* ---------- สาขา ---------- */
function demo_branches()
{
    return array(
        'HQ' => array('name' => 'สำนักงานใหญ่', 'short' => 'สนญ.'),
        'RS' => array('name' => 'สาขารังสิต',   'short' => 'รังสิต'),
        'BN' => array('name' => 'สาขาบางนา',    'short' => 'บางนา'),
    );
}

/* ---------- ค่าตั้งของสาขา (ผู้ดูแลแก้ได้ที่หน้า "ตั้งค่าสาขา") ----------
   count_day        รอบตรวจนับเริ่มทุกวันที่เท่าไรของเดือน (1–28) รอบละ 1 เดือน
   backdate_days    แก้เอกสาร / รับคืนสินค้าย้อนหลังได้กี่วัน
   count_open_limit ระหว่างร้านเปิด นับได้ครั้งละกี่รายการ (0 = ไม่จำกัด)
   default_float    เงินทอนมาตรฐานของสาขา
   เดโมเก็บค่าที่ผู้ดูแลแก้ไว้ใน $_SESSION['cfg']['branch'][ สาขา ][ ชื่อค่า ]
   ระบบจริง: คอลัมน์ในตาราง ao_stock_branch                                   */
function branch_setting_defaults()
{
    return array(
        'HQ' => array('count_day' => 1,  'backdate_days' => 7, 'count_open_limit' => 1, 'default_float' => 3000),
        'RS' => array('count_day' => 1,  'backdate_days' => 7, 'count_open_limit' => 1, 'default_float' => 2000),
        'BN' => array('count_day' => 15, 'backdate_days' => 7, 'count_open_limit' => 1, 'default_float' => 2000),
    );
}

/** ช่วงค่าที่ยอมให้ตั้ง: array(ต่ำสุด, สูงสุด) */
function branch_setting_rules()
{
    return array(
        'count_day'        => array(1, 28),
        'backdate_days'    => array(0, 60),
        'count_open_limit' => array(0, 50),
        'default_float'    => array(0, 100000),
    );
}

function branch_setting($code, $key)
{
    $def = branch_setting_defaults();
    $v   = isset($def[$code][$key]) ? $def[$code][$key] : 0;
    if (isset($_SESSION['cfg']['branch'][$code][$key])) {
        $v = $_SESSION['cfg']['branch'][$code][$key];
    }
    $r = branch_setting_rules();
    return isset($r[$key]) ? max($r[$key][0], min($r[$key][1], (int) $v)) : (int) $v;
}

function branch_setting_set($code, $key, $val)
{
    $r = branch_setting_rules();
    if (!isset($r[$key])) {
        return false;
    }
    $_SESSION['cfg']['branch'][$code][$key] = max($r[$key][0], min($r[$key][1], (int) $val));
    return true;
}

/** วันเริ่มรอบตรวจนับของทุกสาขา */
function count_round_days()
{
    $out = array();
    foreach (array_keys(demo_branches()) as $c) {
        $out[$c] = branch_setting($c, 'count_day');
    }
    return $out;
}

function count_round_day($code)
{
    return branch_setting($code, 'count_day');
}

/** ระหว่างร้านเปิด นับได้ครั้งละกี่รายการ (0 = ไม่จำกัด)
    นับทีละรายการแล้วบันทึกทันที โอกาสที่จะมีการขายแทรกระหว่างนับแทบไม่มี
    ร้านยังไม่เปิด / ปิดแล้ว ไม่จำกัด */
function count_open_limit($code = null)
{
    return $code === null ? 1 : branch_setting($code, 'count_open_limit');
}

/* ---------- สิทธิ์การใช้งาน ---------- */
function demo_roles()
{
    return array(
        'admin'   => array('name' => 'ผู้ดูแลระบบ', 'scope' => 'ทุกสาขา'),
        'account' => array('name' => 'ฝ่ายบัญชี',   'scope' => 'ทุกสาขา · ดูบิลและเงินเข้า'),
        'staff'   => array('name' => 'พนักงาน',    'scope' => 'เฉพาะสาขาตนเอง'),
    );
}

/* ---------- สิทธิ์เสริมของพนักงาน ----------
   พนักงานทุกคนทำงานพื้นฐานได้เท่ากัน: ขาย เปิด–ปิดร้าน รับเข้า เบิก ตรวจนับ
   และแก้/ยกเลิกเอกสารของตัวเองที่ทำวันนี้
   สิทธิ์ด้านล่างผู้ดูแลติ๊กให้เฉพาะบางคน (คนเดียวหรือหลายคนต่อสาขาก็ได้)
   ระบบจริง: เก็บในคอลัมน์ ao_stock_staff.perms                            */
function perm_list()
{
    return array(
        'void_others'   => array('label' => 'แก้/ยกเลิกเอกสารของคนอื่นในสาขา', 'short' => 'แก้งานคนอื่น'),
        'backdate'      => array('label' => 'แก้/ยกเลิกเอกสารย้อนหลัง',       'short' => 'แก้ย้อนหลัง'),
        'refund'        => array('label' => 'รับคืนสินค้า / คืนเงินสดให้ลูกค้า', 'short' => 'รับคืนสินค้า'),
        'report_branch' => array('label' => 'ดูรายงานยอดขายทั้งสาขา',        'short' => 'รายงานทั้งสาขา'),
    );
}

/** ย้อนหลังได้ไม่เกินกี่วัน (สิทธิ์ backdate / refund) — ผู้ดูแลตั้งรายสาขา
    ระบบจริง: ao_stock_branch.backdate_days                                  */
function backdate_days($code = null)
{
    return $code === null ? 7 : branch_setting($code, 'backdate_days');
}

/** สิทธิ์เสริมที่ผู้ใช้คนนี้มี */
function user_perms($user)
{
    if (!$user) {
        return array();
    }
    if (isset($user['role']) && $user['role'] === 'admin') {
        return array_keys(perm_list());        // ผู้ดูแลได้ทุกสิทธิ์
    }
    /* อ่านจากทะเบียนพนักงานทุกครั้ง — ผู้ดูแลเปิด/ปิดสิทธิ์แล้วมีผลทันทีไม่ต้องเข้าระบบใหม่ */
    $all = demo_users_all();
    if (isset($user['username']) && isset($all[$user['username']])) {
        $u = $all[$user['username']];
        return (isset($u['perms']) && is_array($u['perms'])) ? $u['perms'] : array();
    }
    return (isset($user['perms']) && is_array($user['perms'])) ? $user['perms'] : array();
}

function can($user, $perm)
{
    return in_array($perm, user_perms($user), true);
}

/** แก้/ยกเลิกเอกสารใบนี้ได้ไหม — ของตัวเองได้เสมอ ของคนอื่นต้องมีสิทธิ์ void_others
    (เอกสารในเดโมเป็นของวันนี้ทั้งหมด เงื่อนไขย้อนหลังจะเช็กเพิ่มเมื่อมีเอกสารวันก่อน) */
function can_void_doc($user, $doc)
{
    if (!$user || !$doc) {
        return false;
    }
    if (isset($doc['by_user']) && $doc['by_user'] === $user['username']) {
        return true;
    }
    return can($user, 'void_others');
}

/* ---------- บัญชีพนักงานสำหรับทดลอง ----------
   DEMO ONLY — PIN และรหัสผ่านเก็บเป็นข้อความธรรมดาเพื่อให้ทดลองง่าย
   ระบบจริงต้อง:
     - เก็บเป็น password_hash() ในฐานข้อมูล
     - จำกัดจำนวนครั้งที่กรอกผิด (PIN 4 หลักเดาได้ง่ายกว่ารหัสผ่านมาก)
     - ผูก PIN กับสาขา ไม่ให้ PIN ซ้ำกันภายในสาขาเดียว
   ค่าที่ผู้ดูแลแก้ (สิทธิ์เสริม / PIN / ย้ายสาขา) ซ้อนทับทีหลังใน demo_users_all() */
function demo_users_base()
{
    return array(
        'somchai' => array(
            'pin' => '1111', 'password' => '1234',
            'name' => 'สมชาย ใจดี',     'role' => 'admin',   'branch' => 'HQ', 'initials' => 'สช',
        ),
        /* ฝ่ายบัญชี — ดูบิลขายและเงินเข้าได้ทุกสาขา ตั้งรหัสเลขที่บิลของแต่ละสาขาเองได้ ไม่เห็นงานคลัง */
        'pim' => array(
            'pin' => '0000', 'password' => '1234',
            'name' => 'พิมพ์ชนก บัญชีดี', 'role' => 'account', 'branch' => 'HQ', 'initials' => 'พช',
        ),
        'nipa' => array(
            'pin' => '2222', 'password' => '1234',
            'name' => 'นิภา วงศ์ทอง',   'role' => 'staff',   'branch' => 'RS', 'initials' => 'นภ',
            /* พนักงานที่เจ้าของไว้ใจ — ได้สิทธิ์เสริมครบ (แทนตำแหน่งหัวหน้าคลัง) */
            'perms' => array('void_others', 'backdate', 'refund', 'report_branch'),
        ),
        'anan' => array(
            'pin' => '3333', 'password' => '1234',
            'name' => 'อนันต์ ศรีสุข',  'role' => 'staff',   'branch' => 'BN', 'initials' => 'อน',
            'since' => '2026-09-01',
            /* เคยอยู่สาขาไหนมาก่อน — ยอดขายเก่ายังผูกกับสาขาเดิมเสมอ */
            'history' => array(
                array('branch' => 'RS', 'from' => '2026-06-01', 'to' => '2026-08-31'),
            ),
        ),
        'kan' => array(
            'pin' => '4444', 'password' => '1234',
            'name' => 'กานต์ พรมมา',    'role' => 'staff',   'branch' => 'HQ', 'initials' => 'กต',
            'since' => '2026-06-01', 'history' => array(),
        ),
        'mint' => array(
            'pin' => '5555', 'password' => '1234',
            'name' => 'มิ้นท์ สุขใจ',    'role' => 'staff',   'branch' => 'BN', 'initials' => 'มท',
            'since' => '2026-06-01', 'history' => array(),
        ),
        'bee' => array(
            'pin' => '6666', 'password' => '1234',
            'name' => 'เบียร์ ทองดี',    'role' => 'staff',   'branch' => 'RS', 'initials' => 'บย',
            'since' => '2026-07-15', 'history' => array(
                array('branch' => 'HQ', 'from' => '2026-06-01', 'to' => '2026-07-14'),
            ),
        ),
    );
}

/**
 * ทะเบียนพนักงาน + สิ่งที่ผู้ดูแลแก้ไว้
 * $_SESSION['cfg']['user'][ username ] = array(
 *     'perms' => array(...),                                   สิทธิ์เสริม
 *     'pin'   => '1234',                                       PIN ใหม่
 *     'moves' => array( array('branch' => 'BN', 'from' => 'Y-m-d'), ... )   ย้ายสาขา
 * )
 * ระบบจริง: UPDATE ao_stock_staff + INSERT ao_stock_staff_branch
 */
function demo_users_all()
{
    $all = demo_users_base();
    if (empty($_SESSION['cfg']['user'])) {
        return $all;
    }
    foreach ($_SESSION['cfg']['user'] as $k => $o) {
        if (!isset($all[$k])) {
            continue;
        }
        if (isset($o['perms'])) {
            $all[$k]['perms'] = $o['perms'];
        }
        if (isset($o['pin'])) {
            $all[$k]['pin'] = $o['pin'];
        }
        if (!empty($o['moves'])) {
            foreach ($o['moves'] as $m) {
                $u    = $all[$k];
                $hist = isset($u['history']) ? $u['history'] : array();
                $from = isset($u['since']) ? $u['since'] : '2026-06-01';
                if ($from < $m['from']) {
                    /* ปิดช่วงสาขาเดิมไว้ในประวัติ — ยอดเก่ายังผูกกับสาขาเดิม */
                    $hist[] = array('branch' => $u['branch'], 'from' => $from,
                                    'to' => date('Y-m-d', strtotime($m['from'] . ' -1 day')));
                }
                $all[$k]['history'] = $hist;
                $all[$k]['branch']  = $m['branch'];
                $all[$k]['since']   = $m['from'];
            }
        }
    }
    return $all;
}

/** สาขาที่กำลังทำงานอยู่
    พนักงานผูกกับสาขาตัวเองเสมอ · ผู้ดูแลเลือกสาขาจากแถบบน (?branch=) แล้วระบบจำไว้ */
function work_branch($user)
{
    if (!$user || $user['role'] !== 'admin') {
        $all = demo_users_all();
        return (isset($user['username']) && isset($all[$user['username']]))
             ? $all[$user['username']]['branch'] : $user['branch'];
    }
    $b = demo_branches();
    if (isset($_GET['branch']) && is_string($_GET['branch']) && isset($b[$_GET['branch']])) {
        $_SESSION['admin_branch'] = $_GET['branch'];
    }
    return (isset($_SESSION['admin_branch']) && isset($b[$_SESSION['admin_branch']]))
         ? $_SESSION['admin_branch'] : $user['branch'];
}

/** พนักงาน (ไม่รวมผู้ดูแล) ที่ประจำสาขานี้ตอนนี้ */
function branch_staff($code)
{
    $out = array();
    foreach (demo_users_all() as $k => $u) {
        if ($u['role'] === 'staff' && $u['branch'] === $code) {
            $out[$k] = $u;
        }
    }
    return $out;
}

/** รายชื่อที่ใช้งานได้จริงตามสิทธิ์ที่เปิดอยู่ */
function demo_users()
{
    $out = array();
    foreach (demo_users_all() as $k => $u) {
        if (role_enabled($u['role'])) {
            $out[$k] = $u;
        }
    }
    return $out;
}

/**
 * พนักงานคนนี้อยู่สาขาไหน ณ วันที่กำหนด
 * ----------------------------------------------------------
 * หลักสำคัญของรายงาน: ยอดขายต้องผูกกับสาขา "ณ ตอนที่ขาย"
 * ไม่ใช่ไปอ่านสาขาปัจจุบันจากโปรไฟล์ตอนออกรายงาน
 * ไม่งั้นพอพนักงานย้ายสาขา ยอดเก่าของสาขาเดิมจะหายไปทั้งก้อน
 */
function user_branch_on($u, $ts)
{
    $d = date('Y-m-d', $ts);

    if (isset($u['history'])) {
        foreach ($u['history'] as $h) {
            if ($d >= $h['from'] && $d <= $h['to']) {
                return $h['branch'];
            }
        }
    }
    if (isset($u['since']) && $d < $u['since']) {
        return '';                     // ยังไม่ได้เริ่มงานในวันนั้น
    }
    return $u['branch'];
}

/** วันแรกที่เริ่มงาน (ไว้กำหนดช่วง "ทั้งหมด") */
function user_start_date($u)
{
    if (isset($u['history']) && $u['history']) {
        return $u['history'][0]['from'];
    }
    return isset($u['since']) ? $u['since'] : date('Y-m-01');
}

/** ชื่อย่อสำหรับวงกลม avatar */
function user_initial($u)
{
    return isset($u['initials']) ? $u['initials'] : '-';
}

/* ---------- helper ---------- */
function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function branch_name($code)
{
    $b = demo_branches();
    return isset($b[$code]) ? $b[$code]['name'] : $code;
}

function role_name($code)
{
    $r = demo_roles();
    return isset($r[$code]) ? $r[$code]['name'] : $code;
}

function role_scope($code)
{
    $r = demo_roles();
    return isset($r[$code]) ? $r[$code]['scope'] : '-';
}

function url($path)
{
    return APP_BASE . '/' . ltrim($path, '/');
}

/* ---------- polyfill สำหรับ PHP รุ่นเก่า ---------- */
if (!function_exists('hash_equals')) {
    function hash_equals($known, $user)
    {
        $known = (string) $known;
        $user  = (string) $user;
        if (strlen($known) !== strlen($user)) {
            return false;
        }
        $diff = 0;
        for ($i = 0, $n = strlen($known); $i < $n; $i++) {
            $diff |= ord($known[$i]) ^ ord($user[$i]);
        }
        return $diff === 0;
    }
}

if (!function_exists('array_column')) {
    function array_column($rows, $col)
    {
        $out = array();
        foreach ($rows as $row) {
            if (isset($row[$col])) {
                $out[] = $row[$col];
            }
        }
        return $out;
    }
}

/** สุ่มสตริงสำหรับ CSRF token — ใช้ได้ทั้ง PHP 5 และ 7 */
function demo_random_token()
{
    if (function_exists('random_bytes')) {
        return bin2hex(random_bytes(32));
    }
    if (function_exists('openssl_random_pseudo_bytes')) {
        $b = openssl_random_pseudo_bytes(32);
        if ($b !== false) {
            return bin2hex($b);
        }
    }
    return hash('sha256', uniqid(mt_rand(), true) . microtime(true));
}
