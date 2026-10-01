<?php
/* ==========================================================
   AOSTOCK DEMO — รวม function ทั้งหมดของระบบไว้ที่เดียว
   ----------------------------------------------------------
   วิธีใช้: ทุกหน้า include ไฟล์นี้ไฟล์เดียวที่บรรทัดแรก ๆ
       require_once dirname(__FILE__) . '/include/function.php';        (หน้าใน demo/)
       require_once dirname(__FILE__) . '/../include/function.php';     (ไฟล์ใน demo/inc/)
   ไฟล์นี้เรียก inc/config.php (ค่าคงที่ + เริ่ม session) ให้เองแล้ว

   กติกา
   - function PHP ทุกตัวต้องอยู่ในไฟล์นี้เท่านั้น ห้ามประกาศ function ในหน้าเว็บหรือใน inc/
     (ใช้ซ้ำได้ทุกหน้า ไม่ต้องเขียนใหม่ · ก่อนเขียนใหม่ให้ค้นในไฟล์นี้ก่อน)
   - แบ่งเป็นหมวดตามเรื่อง ดูสารบัญด้านล่าง แล้วค้นด้วยชื่อหมวด เช่น "หมวด: ขายสินค้า"
   - closure ที่ใช้เฉพาะในหน้า (เช่น $qs = function(...)) ยังอยู่ในหน้านั้นได้
   - inc/ เหลือเฉพาะไฟล์ส่วนแสดงผล (header, footer, ส่วนที่ htmx โหลดใหม่)

   สารบัญหมวด
    1. ตั้งค่า ข้อมูลตัวอย่าง ผู้ใช้ สิทธิ์ สาขา
    2. ข้อมูลตัวอย่าง (ยังไม่ต่อฐานข้อมูล)
    3. ประวัติการทำรายการ
    4. การยืนยันตัวตนด้วย session (ยังไม่ต่อฐานข้อมูล)
    5. เลขที่บิลและ VAT (ฝ่ายบัญชีตั้งค่าได้)
    6. เปิด / ปิดร้านประจำวัน และเงินทอนในลิ้นชัก
    7. ขายสินค้า (ตะกร้า + บิล)
    8. สต๊อกสินค้า รูปสินค้า และรับเข้า
    9. เบิก / ตัดออกจากสต๊อก
   10. ตรวจนับ / ปรับยอด (แบบเบา)
   11. ประวัติเคลื่อนไหวรายสินค้า
   12. รายงานยอดขาย
   13. รับคืนสินค้า
   14. แก้ / ยกเลิกเอกสารย้อนหลัง (สิทธิ์เสริม backdate)
   15. ข้อมูลของฝ่ายบัญชี
   16. หน้าบิลขายและเงินเข้า (account.php)
   17. หน้าจัดการสาขา (adm-branches.php)
   18. หน้าประวัติการทำรายการ (history.php)
   19. หน้าจัดการพนักงาน (adm-users.php)
   20. หน้าขายสินค้า (sale.php)
   21. ภาพรวมของผู้ดูแล (adm-dashboard.php)

   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

require_once dirname(__FILE__) . '/../inc/config.php';


/* ##########################################################
   หมวด: ตั้งค่า ข้อมูลตัวอย่าง ผู้ใช้ สิทธิ์ สาขา
   ########################################################## */

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
                 'history.php', 'return.php', 'report-sales.php',
                 'account.php', 'account-settings.php',
                 'adm-dashboard.php', 'adm-products.php', 'adm-receive.php', 'adm-issue.php', 'adm-return.php', 'adm-history.php',
                 'adm-movements.php', 'adm-report.php', 'adm-users.php', 'adm-branches.php');
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

/* ---------- สาขา ----------
   ผู้ดูแลเพิ่ม / แก้ไข / ปิดใช้งาน / ลบ ได้ที่หน้า "จัดการสาขา" (adm-branches.php)
   เดโมเก็บสิ่งที่แก้ไว้ใน $_SESSION['cfg']['branches'][ รหัส ] = array(
       'name', 'short', 'address', 'phone',   ข้อมูลที่แก้ / สาขาที่เพิ่มใหม่
       'active'  => false,                    ปิดใช้งาน (ประวัติยังอยู่ครบ)
       'deleted' => true,                     ลบแล้ว (ได้เฉพาะสาขาที่ยังไม่มีข้อมูล)
       'added'   => true )                    สาขาที่ผู้ดูแลเพิ่มเอง
   ระบบจริง: ตาราง ao_stock_branch (is_active) */
function demo_branches_base()
{
    return array(
        'HQ' => array('name' => 'สำนักงานใหญ่', 'short' => 'สนญ.',  'address' => '99/9 ถ.พหลโยธิน ต.คลองหนึ่ง อ.คลองหลวง จ.ปทุมธานี', 'phone' => '02-957-8755'),
        'RS' => array('name' => 'สาขารังสิต',   'short' => 'รังสิต', 'address' => 'ฟิวเจอร์พาร์ค รังสิต ชั้น 2 จ.ปทุมธานี',            'phone' => '02-111-2222'),
        'BN' => array('name' => 'สาขาบางนา',    'short' => 'บางนา',  'address' => 'เซ็นทรัล บางนา ชั้น 3 กรุงเทพฯ',                    'phone' => '02-333-4444'),
    );
}

/** ทุกสาขา รวมที่ปิดใช้งาน (ไม่รวมที่ลบแล้ว) — ใช้ตอนต้องแสดงประวัติ / ชื่อสาขาเก่า */
function demo_branches_all()
{
    $out = array();
    foreach (demo_branches_base() as $c => $b) {
        $b['active'] = true;
        $b['added']  = false;
        $out[$c] = $b;
    }
    if (!empty($_SESSION['cfg']['branches'])) {
        foreach ($_SESSION['cfg']['branches'] as $c => $o) {
            if (!empty($o['deleted'])) {
                unset($out[$c]);
                continue;
            }
            $base    = isset($out[$c]) ? $out[$c] : array('name' => $c, 'short' => $c, 'address' => '', 'phone' => '', 'active' => true, 'added' => true);
            $out[$c] = array_merge($base, $o);
        }
    }
    return $out;
}

/** สาขาที่เปิดใช้งาน — ใช้ทั่วไป (ตัวเลือกสาขา ย้ายพนักงาน ภาพรวม) */
function demo_branches()
{
    $out = array();
    foreach (demo_branches_all() as $c => $b) {
        if (!empty($b['active'])) {
            $out[$c] = $b;
        }
    }
    return $out;
}

/* ---------- ค่าตั้งของสาขา (ผู้ดูแลแก้ได้ที่หน้า "จัดการสาขา") ----------
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
    $new = array('count_day' => 1, 'backdate_days' => 7, 'count_open_limit' => 1, 'default_float' => 2000);  // สาขาที่เพิ่มใหม่
    $v   = isset($def[$code][$key]) ? $def[$code][$key] : (isset($new[$key]) ? $new[$key] : 0);
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
        /* group menu = เข้าเมนูนั้นได้ · group extra = สิทธิ์เสริมที่ต้องไว้ใจ */
        'sale'          => array('group' => 'menu',  'label' => 'ขายสินค้า และเปิด / ปิดร้าน',       'short' => 'ขายสินค้า'),
        'receive'       => array('group' => 'menu',  'label' => 'นำเข้าสินค้า (รับเข้าสต๊อก)',        'short' => 'นำเข้าสินค้า'),
        'issue'         => array('group' => 'menu',  'label' => 'เบิก / ตัดออกสินค้า',              'short' => 'เบิก / ตัดออก'),
        'stocktake'     => array('group' => 'menu',  'label' => 'ตรวจนับ / ปรับยอด',               'short' => 'ตรวจนับ'),
        'history'       => array('group' => 'menu',  'label' => 'ประวัติการทำรายการของสาขา',       'short' => 'ประวัติรายการ'),
        'refund'        => array('group' => 'menu',  'label' => 'รับคืนสินค้า / คืนเงินสดให้ลูกค้า', 'short' => 'รับคืนสินค้า'),
        'void_others'   => array('group' => 'extra', 'label' => 'แก้/ยกเลิกเอกสารของคนอื่นในสาขา',  'short' => 'แก้งานคนอื่น'),
        'backdate'      => array('group' => 'extra', 'label' => 'แก้/ยกเลิกเอกสารย้อนหลัง',        'short' => 'แก้ย้อนหลัง'),
        'report_branch' => array('group' => 'extra', 'label' => 'ดูรายงานยอดขายทั้งสาขา',         'short' => 'รายงานทั้งสาขา'),
    );
}

/** สิทธิ์เริ่มต้นของพนักงานที่เพิ่มใหม่ */
function perm_default()
{
    return array('sale', 'receive', 'issue', 'stocktake', 'history');
}

/** ชื่อสิทธิ์ที่ต้องมีเพื่อเปิดหน้านั้น */
function page_perm($file)
{
    $map = array('sale.php' => 'sale', 'store.php' => 'sale', 'receive.php' => 'receive', 'issue.php' => 'issue',
                 'stocktake.php' => 'stocktake', 'history.php' => 'history', 'return.php' => 'refund');
    return isset($map[$file]) ? $map[$file] : '';
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
        /* ผู้ดูแลได้ทุกสิทธิ์ ยกเว้น
             sale     ขายสินค้า / เปิด–ปิดร้าน (หน้าที่ของพนักงานหน้าร้าน)
             receive / issue / stocktake  งานคลัง (หน้าที่ของพนักงานที่ได้รับมอบหมาย)
           ผู้ดูแลตรวจสอบงานพวกนี้จากหน้าชุด adm- แทน */
        return array_values(array_diff(array_keys(perm_list()), array('sale', 'receive', 'issue', 'stocktake')));
    }
    if (isset($user['role']) && $user['role'] !== 'staff') {
        return array();
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
            'perms' => array('sale', 'receive', 'issue', 'stocktake', 'history', 'refund', 'void_others', 'backdate', 'report_branch'),
        ),
        'anan' => array(
            'pin' => '3333', 'password' => '1234',
            'perms' => array('sale', 'receive', 'issue', 'stocktake', 'history'),
            'name' => 'อนันต์ ศรีสุข',  'role' => 'staff',   'branch' => 'BN', 'initials' => 'อน',
            'since' => '2026-09-01',
            /* เคยอยู่สาขาไหนมาก่อน — ยอดขายเก่ายังผูกกับสาขาเดิมเสมอ */
            'history' => array(
                array('branch' => 'RS', 'from' => '2026-06-01', 'to' => '2026-08-31'),
            ),
        ),
        'kan' => array(
            'pin' => '4444', 'password' => '1234',
            'perms' => array('sale', 'receive', 'issue', 'stocktake', 'history'),
            'name' => 'กานต์ พรมมา',    'role' => 'staff',   'branch' => 'HQ', 'initials' => 'กต',
            'since' => '2026-06-01', 'history' => array(),
        ),
        'mint' => array(
            'pin' => '5555', 'password' => '1234',
            'perms' => array('sale', 'receive', 'issue', 'stocktake', 'history'),
            'name' => 'มิ้นท์ สุขใจ',    'role' => 'staff',   'branch' => 'BN', 'initials' => 'มท',
            'since' => '2026-06-01', 'history' => array(),
        ),
        'bee' => array(
            'pin' => '6666', 'password' => '1234',
            'perms' => array('sale', 'receive', 'issue', 'stocktake', 'history'),
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
    /* พนักงานที่ผู้ดูแลเพิ่มเองที่หน้า "จัดการพนักงาน" */
    if (!empty($_SESSION['cfg']['newuser'])) {
        foreach ($_SESSION['cfg']['newuser'] as $k => $u) {
            $all[$k] = $u;
        }
    }
    if (empty($_SESSION['cfg']['user'])) {
        return $all;
    }
    foreach ($_SESSION['cfg']['user'] as $k => $o) {
        if (!isset($all[$k])) {
            continue;
        }
        if (!empty($o['deleted'])) {
            unset($all[$k]);
            continue;
        }
        foreach (array('name', 'initials', 'active') as $f) {
            if (isset($o[$f])) {
                $all[$k][$f] = $o[$f];
            }
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
    if (isset($_SESSION['admin_branch']) && isset($b[$_SESSION['admin_branch']])) {
        return $_SESSION['admin_branch'];
    }
    if (isset($b[$user['branch']])) {
        return $user['branch'];
    }
    $keys = array_keys($b);                    // สาขาประจำถูกปิด → ใช้สาขาแรกที่ยังเปิด
    return $keys[0];
}

/** พนักงาน (ไม่รวมผู้ดูแล) ที่ประจำสาขานี้ตอนนี้ */
function branch_staff($code)
{
    $out = array();
    foreach (demo_users_all() as $k => $u) {
        if ($u['role'] === 'staff' && $u['branch'] === $code && user_active($u)) {
            $out[$k] = $u;
        }
    }
    return $out;
}

/** บัญชีนี้ยังใช้งานอยู่ไหม (พนักงานที่พักงาน / ลาออก = ไม่ใช้งาน แต่ประวัติยังอยู่) */
function user_active($u)
{
    return !isset($u['active']) || $u['active'];
}

function demo_users()
{
    $out = array();
    foreach (demo_users_all() as $k => $u) {
        if (role_enabled($u['role']) && user_active($u)) {
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
    $b = demo_branches_all();                  // รวมสาขาที่ปิดแล้ว ประวัติเก่าจะได้ยังเห็นชื่อ
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

/* ##########################################################
   หมวด: ข้อมูลตัวอย่าง (ยังไม่ต่อฐานข้อมูล)
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — ข้อมูลตัวอย่าง (ยังไม่ต่อฐานข้อมูล)
   ทุกตัวเลขบนหน้าจอคำนวณจากข้อมูลในไฟล์นี้
   เมื่อทำระบบจริง: แทนที่ฟังก์ชันด้านล่างด้วย query ไปยัง MySQL
   รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

/* ---------- สินค้า (ข้อมูลทดสอบ: ร้านอุปกรณ์มือถือ) ----------
   price = ราคาขายหน้าร้าน · cost = ต้นทุนต่อหน่วย (สมมติ ~50% ของราคาขาย)
   stock = ยอดคงเหลือแยกตามสาขา · reorder = จุดสั่งซื้อ
   สินค้าที่ราคาเป็นช่วง (เช่น 290–590) แยกเป็นหลาย SKU ตามรุ่น            */
function demo_products()
{
    return array(
        array('sku' => 'CS-001', 'name' => 'เคสมือถือ แฟชั่น', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 50.00, 'price' => 100, 'reorder' => 20, 'stock' => array('HQ' => 60, 'RS' => 2, 'BN' => 50)),
        array('sku' => 'CS-002', 'name' => 'เคสมือถือ แฟชั่น MOOPHE รุ่นมาตรฐาน', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 145.00, 'price' => 290, 'reorder' => 8, 'stock' => array('HQ' => 7, 'RS' => 7, 'BN' => 20)),
        array('sku' => 'CS-003', 'name' => 'เคสมือถือ แฟชั่น MOOPHE รุ่นพรีเมียม', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 295.00, 'price' => 590, 'reorder' => 8, 'stock' => array('HQ' => 21, 'RS' => 17, 'BN' => 13)),
        array('sku' => 'CS-004', 'name' => 'เคสมือถือ SwitchEasy รุ่นมาตรฐาน', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 345.00, 'price' => 690, 'reorder' => 5, 'stock' => array('HQ' => 1, 'RS' => 4, 'BN' => 14)),
        array('sku' => 'CS-005', 'name' => 'เคสมือถือ SwitchEasy รุ่นพรีเมียม', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 445.00, 'price' => 890, 'reorder' => 5, 'stock' => array('HQ' => 10, 'RS' => 9, 'BN' => 4)),
        array('sku' => 'CS-006', 'name' => 'เคสมือถือ Mutural รุ่นมาตรฐาน', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 345.00, 'price' => 690, 'reorder' => 5, 'stock' => array('HQ' => 17, 'RS' => 10, 'BN' => 13)),
        array('sku' => 'CS-007', 'name' => 'เคสมือถือ Mutural รุ่นพรีเมียม', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 445.00, 'price' => 890, 'reorder' => 5, 'stock' => array('HQ' => 14, 'RS' => 1, 'BN' => 12)),
        array('sku' => 'CS-008', 'name' => 'เคสมือถือ HI Shield รุ่นมาตรฐาน', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 445.00, 'price' => 890, 'reorder' => 4, 'stock' => array('HQ' => 3, 'RS' => 0, 'BN' => 7)),
        array('sku' => 'CS-009', 'name' => 'เคสมือถือ HI Shield รุ่นพรีเมียม', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 745.00, 'price' => 1490, 'reorder' => 4, 'stock' => array('HQ' => 14, 'RS' => 4, 'BN' => 6)),
        array('sku' => 'TC-001', 'name' => 'เคส iPad MOOPHE รุ่นมาตรฐาน', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 245.00, 'price' => 490, 'reorder' => 4, 'stock' => array('HQ' => 3, 'RS' => 9, 'BN' => 7)),
        array('sku' => 'TC-002', 'name' => 'เคส iPad MOOPHE รุ่นพรีเมียม', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 295.00, 'price' => 590, 'reorder' => 4, 'stock' => array('HQ' => 7, 'RS' => 8, 'BN' => 5)),
        array('sku' => 'TC-003', 'name' => 'เคส iPad Domo รุ่นมาตรฐาน', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 345.00, 'price' => 690, 'reorder' => 3, 'stock' => array('HQ' => 3, 'RS' => 3, 'BN' => 0)),
        array('sku' => 'TC-004', 'name' => 'เคส iPad Domo รุ่นพรีเมียม', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 395.00, 'price' => 790, 'reorder' => 3, 'stock' => array('HQ' => 4, 'RS' => 4, 'BN' => 3)),
        array('sku' => 'TC-005', 'name' => 'เคส iPad Moshi รุ่นมาตรฐาน', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 395.00, 'price' => 790, 'reorder' => 3, 'stock' => array('HQ' => 8, 'RS' => 4, 'BN' => 8)),
        array('sku' => 'TC-006', 'name' => 'เคส iPad Moshi รุ่นพรีเมียม', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 495.00, 'price' => 990, 'reorder' => 3, 'stock' => array('HQ' => 6, 'RS' => 1, 'BN' => 6)),
        array('sku' => 'TC-007', 'name' => 'เคส iPad ลายการ์ตูน รุ่นมาตรฐาน', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 245.00, 'price' => 490, 'reorder' => 4, 'stock' => array('HQ' => 10, 'RS' => 1, 'BN' => 10)),
        array('sku' => 'TC-008', 'name' => 'เคส iPad ลายการ์ตูน รุ่นพรีเมียม', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 295.00, 'price' => 590, 'reorder' => 4, 'stock' => array('HQ' => 12, 'RS' => 9, 'BN' => 3)),
        array('sku' => 'FM-001', 'name' => 'กระจกใส ไร้ขอบ', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 40.00, 'price' => 79, 'reorder' => 30, 'stock' => array('HQ' => 60, 'RS' => 16, 'BN' => 85)),
        array('sku' => 'FM-002', 'name' => 'กระจกใส เต็มจอ', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 75.00, 'price' => 150, 'reorder' => 25, 'stock' => array('HQ' => 33, 'RS' => 54, 'BN' => 54)),
        array('sku' => 'FM-003', 'name' => 'กระจกใส เต็มจอ เกรดพิเศษ', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 100.00, 'price' => 200, 'reorder' => 20, 'stock' => array('HQ' => 51, 'RS' => 29, 'BN' => 36)),
        array('sku' => 'FM-004', 'name' => 'กระจกใส Focus iPhone', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 175.00, 'price' => 349, 'reorder' => 10, 'stock' => array('HQ' => 29, 'RS' => 18, 'BN' => 22)),
        array('sku' => 'FM-005', 'name' => 'กระจกใส Focus Android', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 125.00, 'price' => 249, 'reorder' => 10, 'stock' => array('HQ' => 11, 'RS' => 22, 'BN' => 11)),
        array('sku' => 'FM-006', 'name' => 'กระจกด้าน Focus', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 245.00, 'price' => 490, 'reorder' => 6, 'stock' => array('HQ' => 8, 'RS' => 2, 'BN' => 13)),
        array('sku' => 'FM-007', 'name' => 'กระจกด้าน U&i', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 145.00, 'price' => 290, 'reorder' => 8, 'stock' => array('HQ' => 5, 'RS' => 13, 'BN' => 14)),
        array('sku' => 'FM-008', 'name' => 'กระจกด้าน คิงคอง', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 195.00, 'price' => 390, 'reorder' => 8, 'stock' => array('HQ' => 30, 'RS' => 16, 'BN' => 6)),
        array('sku' => 'FM-009', 'name' => 'กระจกกรองแสงสีฟ้า Focus รุ่นมาตรฐาน', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 245.00, 'price' => 490, 'reorder' => 5, 'stock' => array('HQ' => 11, 'RS' => 13, 'BN' => 12)),
        array('sku' => 'FM-010', 'name' => 'กระจกกรองแสงสีฟ้า Focus รุ่นพรีเมียม', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 5, 'stock' => array('HQ' => 8, 'RS' => 10, 'BN' => 14)),
        array('sku' => 'FM-011', 'name' => 'กระจกกันมอง ใส U&i', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 145.00, 'price' => 290, 'reorder' => 8, 'stock' => array('HQ' => 26, 'RS' => 9, 'BN' => 10)),
        array('sku' => 'FM-012', 'name' => 'กระจกกันมอง ใส คิงคอง', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 195.00, 'price' => 390, 'reorder' => 8, 'stock' => array('HQ' => 2, 'RS' => 0, 'BN' => 16)),
        array('sku' => 'FM-013', 'name' => 'กระจกกันมอง ใส Leeplus', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 245.00, 'price' => 490, 'reorder' => 6, 'stock' => array('HQ' => 1, 'RS' => 6, 'BN' => 15)),
        array('sku' => 'FM-014', 'name' => 'กระจกกันมอง ใส Focus', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 6, 'stock' => array('HQ' => 15, 'RS' => 11, 'BN' => 7)),
        array('sku' => 'FM-015', 'name' => 'กระจกกันมอง ด้าน Focus', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 5, 'stock' => array('HQ' => 8, 'RS' => 14, 'BN' => 0)),
        array('sku' => 'FM-016', 'name' => 'กระจกประกัน 1 ปี ใส Focus รุ่นมาตรฐาน', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 4, 'stock' => array('HQ' => 14, 'RS' => 8, 'BN' => 10)),
        array('sku' => 'FM-017', 'name' => 'กระจกประกัน 1 ปี ใส Focus รุ่นพรีเมียม', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 445.00, 'price' => 890, 'reorder' => 4, 'stock' => array('HQ' => 1, 'RS' => 8, 'BN' => 11)),
        array('sku' => 'FM-018', 'name' => 'กระจกประกัน 1 ปี กันมอง Focus รุ่นมาตรฐาน', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 495.00, 'price' => 990, 'reorder' => 3, 'stock' => array('HQ' => 6, 'RS' => 0, 'BN' => 8)),
        array('sku' => 'FM-019', 'name' => 'กระจกประกัน 1 ปี กันมอง Focus รุ่นพรีเมียม', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 645.00, 'price' => 1290, 'reorder' => 3, 'stock' => array('HQ' => 1, 'RS' => 8, 'BN' => 7)),
        array('sku' => 'FM-020', 'name' => 'กระจกประกัน 1 ปี AR Focus', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 595.00, 'price' => 1190, 'reorder' => 3, 'stock' => array('HQ' => 8, 'RS' => 5, 'BN' => 6)),
        array('sku' => 'FT-001', 'name' => 'ฟิล์ม iPad กระจกใส Startec รุ่นมาตรฐาน', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 195.00, 'price' => 390, 'reorder' => 4, 'stock' => array('HQ' => 4, 'RS' => 9, 'BN' => 8)),
        array('sku' => 'FT-002', 'name' => 'ฟิล์ม iPad กระจกใส Startec รุ่นพรีเมียม', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 245.00, 'price' => 490, 'reorder' => 4, 'stock' => array('HQ' => 11, 'RS' => 8, 'BN' => 10)),
        array('sku' => 'FT-003', 'name' => 'ฟิล์ม iPad กระจกใส Focus รุ่นมาตรฐาน', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 4, 'stock' => array('HQ' => 9, 'RS' => 8, 'BN' => 11)),
        array('sku' => 'FT-004', 'name' => 'ฟิล์ม iPad กระจกใส Focus รุ่นพรีเมียม', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 4, 'stock' => array('HQ' => 0, 'RS' => 2, 'BN' => 7)),
        array('sku' => 'FT-005', 'name' => 'ฟิล์ม iPad กระจกด้าน Startec', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 3, 'stock' => array('HQ' => 0, 'RS' => 2, 'BN' => 5)),
        array('sku' => 'FT-006', 'name' => 'ฟิล์ม iPad กระจกด้าน Focus', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 3, 'stock' => array('HQ' => 5, 'RS' => 7, 'BN' => 2)),
        array('sku' => 'FT-007', 'name' => 'ฟิล์ม iPad ผิวกระดาษ Startec', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 3, 'stock' => array('HQ' => 9, 'RS' => 7, 'BN' => 8)),
        array('sku' => 'FT-008', 'name' => 'ฟิล์ม iPad ผิวกระดาษ Focus', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 395.00, 'price' => 790, 'reorder' => 3, 'stock' => array('HQ' => 5, 'RS' => 4, 'BN' => 2)),
        array('sku' => 'LN-001', 'name' => 'Color Ring Android รุ่นมาตรฐาน', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 100.00, 'price' => 200, 'reorder' => 6, 'stock' => array('HQ' => 16, 'RS' => 15, 'BN' => 15)),
        array('sku' => 'LN-002', 'name' => 'Color Ring Android รุ่นพรีเมียม', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 125.00, 'price' => 250, 'reorder' => 6, 'stock' => array('HQ' => 21, 'RS' => 2, 'BN' => 17)),
        array('sku' => 'LN-003', 'name' => 'Color Ring ZEON รุ่นมาตรฐาน', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 125.00, 'price' => 250, 'reorder' => 6, 'stock' => array('HQ' => 21, 'RS' => 5, 'BN' => 16)),
        array('sku' => 'LN-004', 'name' => 'Color Ring ZEON รุ่นพรีเมียม', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 145.00, 'price' => 290, 'reorder' => 6, 'stock' => array('HQ' => 21, 'RS' => 15, 'BN' => 8)),
        array('sku' => 'LN-005', 'name' => 'Color Ring Startec', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 195.00, 'price' => 390, 'reorder' => 5, 'stock' => array('HQ' => 10, 'RS' => 14, 'BN' => 8)),
        array('sku' => 'LN-006', 'name' => 'Color Ring Focus', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 245.00, 'price' => 490, 'reorder' => 5, 'stock' => array('HQ' => 19, 'RS' => 9, 'BN' => 8)),
        array('sku' => 'LN-007', 'name' => 'Color Ring Hi Shield', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 295.00, 'price' => 590, 'reorder' => 4, 'stock' => array('HQ' => 14, 'RS' => 3, 'BN' => 8)),
        array('sku' => 'LN-008', 'name' => 'Color Ring Hi Shield SAPPHIRE รุ่นมาตรฐาน', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 595.00, 'price' => 1190, 'reorder' => 2, 'stock' => array('HQ' => 2, 'RS' => 3, 'BN' => 2)),
        array('sku' => 'LN-009', 'name' => 'Color Ring Hi Shield SAPPHIRE รุ่นพรีเมียม', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 645.00, 'price' => 1290, 'reorder' => 2, 'stock' => array('HQ' => 4, 'RS' => 3, 'BN' => 3)),
        array('sku' => 'LN-010', 'name' => 'Diamond Look ZEON', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 145.00, 'price' => 290, 'reorder' => 5, 'stock' => array('HQ' => 3, 'RS' => 13, 'BN' => 10)),
        array('sku' => 'LN-011', 'name' => 'Diamond Look Startec รุ่นมาตรฐาน', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 195.00, 'price' => 390, 'reorder' => 4, 'stock' => array('HQ' => 2, 'RS' => 4, 'BN' => 4)),
        array('sku' => 'LN-012', 'name' => 'Diamond Look Startec รุ่นพรีเมียม', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 245.00, 'price' => 490, 'reorder' => 4, 'stock' => array('HQ' => 3, 'RS' => 5, 'BN' => 0)),
        array('sku' => 'LN-013', 'name' => 'Diamond Look Hi Shield', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 345.00, 'price' => 690, 'reorder' => 3, 'stock' => array('HQ' => 1, 'RS' => 8, 'BN' => 8)),
        array('sku' => 'LN-014', 'name' => 'ฟิล์มเลนส์ ZEON', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 100.00, 'price' => 200, 'reorder' => 6, 'stock' => array('HQ' => 11, 'RS' => 0, 'BN' => 9)),
        array('sku' => 'LN-015', 'name' => 'ฟิล์มเลนส์ Focus รุ่นมาตรฐาน', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 145.00, 'price' => 290, 'reorder' => 5, 'stock' => array('HQ' => 10, 'RS' => 5, 'BN' => 11)),
        array('sku' => 'LN-016', 'name' => 'ฟิล์มเลนส์ Focus รุ่นพรีเมียม', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 195.00, 'price' => 390, 'reorder' => 5, 'stock' => array('HQ' => 16, 'RS' => 6, 'BN' => 9)),
        array('sku' => 'HG-001', 'name' => 'Hydrogel Clear STARTEC Lite', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 125.00, 'price' => 250, 'reorder' => 5, 'stock' => array('HQ' => 18, 'RS' => 10, 'BN' => 13)),
        array('sku' => 'HG-002', 'name' => 'Hydrogel Clear STARTEC Standard', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 195.00, 'price' => 390, 'reorder' => 5, 'stock' => array('HQ' => 9, 'RS' => 3, 'BN' => 5)),
        array('sku' => 'HG-003', 'name' => 'Hydrogel Clear STARTEC Plus', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 245.00, 'price' => 490, 'reorder' => 5, 'stock' => array('HQ' => 11, 'RS' => 9, 'BN' => 8)),
        array('sku' => 'HG-004', 'name' => 'Hydrogel Clear STARTEC Pro', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 5, 'stock' => array('HQ' => 5, 'RS' => 10, 'BN' => 8)),
        array('sku' => 'HG-005', 'name' => 'Hydrogel Clear FOCUS', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 145.00, 'price' => 290, 'reorder' => 8, 'stock' => array('HQ' => 27, 'RS' => 21, 'BN' => 14)),
        array('sku' => 'HG-006', 'name' => 'Hydrogel Matte STARTEC Lite', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 145.00, 'price' => 290, 'reorder' => 5, 'stock' => array('HQ' => 15, 'RS' => 11, 'BN' => 9)),
        array('sku' => 'HG-007', 'name' => 'Hydrogel Matte STARTEC Standard', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 245.00, 'price' => 490, 'reorder' => 5, 'stock' => array('HQ' => 4, 'RS' => 11, 'BN' => 12)),
        array('sku' => 'HG-008', 'name' => 'Hydrogel Matte STARTEC Plus', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 5, 'stock' => array('HQ' => 14, 'RS' => 8, 'BN' => 0)),
        array('sku' => 'HG-009', 'name' => 'Hydrogel Matte STARTEC Pro', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 5, 'stock' => array('HQ' => 0, 'RS' => 8, 'BN' => 11)),
        array('sku' => 'HG-010', 'name' => 'Hydrogel Matte FOCUS', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 175.00, 'price' => 350, 'reorder' => 8, 'stock' => array('HQ' => 18, 'RS' => 0, 'BN' => 16)),
        array('sku' => 'HG-011', 'name' => 'Hydrogel Privacy STARTEC', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 195.00, 'price' => 390, 'reorder' => 5, 'stock' => array('HQ' => 5, 'RS' => 5, 'BN' => 4)),
        array('sku' => 'HG-012', 'name' => 'UV Film STARTEC', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 3, 'stock' => array('HQ' => 0, 'RS' => 4, 'BN' => 8)),
        array('sku' => 'HG-013', 'name' => 'UV Film Focus', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 3, 'stock' => array('HQ' => 4, 'RS' => 4, 'BN' => 3)),
        array('sku' => 'CB-001', 'name' => 'สายชาร์จ USB to Type-C UB-66C', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 195.00, 'price' => 390, 'reorder' => 5, 'stock' => array('HQ' => 18, 'RS' => 3, 'BN' => 6)),
        array('sku' => 'CB-002', 'name' => 'สายชาร์จ USB to Type-C UB-65', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 145.00, 'price' => 290, 'reorder' => 5, 'stock' => array('HQ' => 11, 'RS' => 6, 'BN' => 12)),
        array('sku' => 'CB-003', 'name' => 'สายชาร์จ USB to Type-C CB-N02C', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 100.00, 'price' => 200, 'reorder' => 8, 'stock' => array('HQ' => 19, 'RS' => 15, 'BN' => 8)),
        array('sku' => 'CB-004', 'name' => 'สายชาร์จ USB to Type-C CB-R01C', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 50.00, 'price' => 100, 'reorder' => 10, 'stock' => array('HQ' => 5, 'RS' => 28, 'BN' => 4)),
        array('sku' => 'CB-005', 'name' => 'สายชาร์จ Type-C to Type-C C3X-Pro', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 130.00, 'price' => 259, 'reorder' => 6, 'stock' => array('HQ' => 0, 'RS' => 16, 'BN' => 0)),
        array('sku' => 'CB-006', 'name' => 'สายชาร์จ Type-C to Type-C BO-X288C-C', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 195.00, 'price' => 390, 'reorder' => 5, 'stock' => array('HQ' => 16, 'RS' => 8, 'BN' => 10)),
        array('sku' => 'CB-007', 'name' => 'สายชาร์จ Type-C to Type-C BO-X288C-2M (2 ม.)', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 245.00, 'price' => 490, 'reorder' => 5, 'stock' => array('HQ' => 1, 'RS' => 12, 'BN' => 9)),
    );
}

/* ---------- ความเคลื่อนไหวล่าสุด ----------
   type: receive | issue | adjust | count
   (โอนระหว่างสาขา — ยังไม่ทำ ใช้เบิกออก/นำเข้าแทน)
   status: done | pending | waiting  */
function demo_movements()
{
    return array(
        array('doc' => 'RC-2609-0142', 'type' => 'receive',  'branch' => 'HQ', 'to' => null, 'time' => '23 ก.ย. 09:12', 'items' => 3,  'qty' => 86,   'by' => 'สมชาย ใจดี',   'status' => 'done'),
        array('doc' => 'IS-2609-0208', 'type' => 'issue',    'branch' => 'BN', 'to' => null, 'time' => '22 ก.ย. 17:30', 'items' => 5,  'qty' => -12,  'by' => 'อนันต์ ศรีสุข', 'status' => 'done'),
        array('doc' => 'AD-2609-0011', 'type' => 'adjust',   'branch' => 'RS', 'to' => null, 'time' => '22 ก.ย. 15:02', 'items' => 1,  'qty' => -2,   'by' => 'นิภา วงศ์ทอง',  'status' => 'done'),
        array('doc' => 'RC-2609-0141', 'type' => 'receive',  'branch' => 'RS', 'to' => null, 'time' => '22 ก.ย. 11:20', 'items' => 4,  'qty' => 60,   'by' => 'นิภา วงศ์ทอง',  'status' => 'done'),
        array('doc' => 'IS-2609-0207', 'type' => 'issue',    'branch' => 'HQ', 'to' => null, 'time' => '21 ก.ย. 16:10', 'items' => 2,  'qty' => -6,   'by' => 'สมชาย ใจดี',   'status' => 'done'),
        array('doc' => 'ST-2609-0004', 'type' => 'count',    'branch' => 'BN', 'to' => null, 'time' => '20 ก.ย. 18:00', 'items' => 48, 'qty' => -3,   'by' => 'อนันต์ ศรีสุข', 'status' => 'pending'),
    );
}

function movement_types()
{
    return array(
        'receive'  => array('label' => 'รับเข้า', 'tone' => 'in'),
        'issue'    => array('label' => 'เบิกออก', 'tone' => 'out'),
        'adjust'   => array('label' => 'ปรับยอด', 'tone' => 'adj'),
        'count'    => array('label' => 'ตรวจนับ', 'tone' => 'adj'),
    );
}

function movement_status()
{
    return array(
        'done'    => array('label' => 'สำเร็จ',    'tone' => 'ok'),
        'waiting' => array('label' => 'รอรับของ',  'tone' => 'warn'),
        'pending' => array('label' => 'รออนุมัติ', 'tone' => 'warn'),
    );
}

function movement_type_of($key)
{
    $t = movement_types();
    return isset($t[$key]) ? $t[$key] : array('label' => $key, 'tone' => 'adj');
}

function movement_status_of($key)
{
    $s = movement_status();
    return isset($s[$key]) ? $s[$key] : array('label' => $key, 'tone' => 'ok');
}

/* รูปทรงของกราฟ 12 เดือน (สัดส่วนเทียบเดือนล่าสุด) */
function stock_trend_shape()
{
    return array(0.78, 0.82, 0.86, 0.81, 0.88, 0.92, 0.87, 0.94, 0.97, 0.91, 0.96, 1.00);
}

function stock_trend_months()
{
    return array('ต.ค.', 'พ.ย.', 'ธ.ค.', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.');
}

/* ==========================================================
   ฟังก์ชันสรุปข้อมูล — $branch = รหัสสาขา หรือ 'ALL'
   ========================================================== */

/* ---------- ยอดที่ขยับระหว่างวัน (เก็บใน session) ----------
   ยังไม่มีฐานข้อมูล จึงจำ "ส่วนต่าง" ของแต่ละสาขา/SKU ไว้ใน session
   $_SESSION['stock_adj'][ รหัสสาขา ][ SKU ] = จำนวนที่บวก/ลบจากยอดตั้งต้น
   ระบบจริง: ยอดนี้มาจากตาราง stock_move                                */

function stock_adj_get($code, $sku)
{
    return isset($_SESSION['stock_adj'][$code][$sku]) ? (int) $_SESSION['stock_adj'][$code][$sku] : 0;
}

function stock_adj_add($code, $sku, $delta)
{
    if (!isset($_SESSION['stock_adj'][$code])) {
        $_SESSION['stock_adj'][$code] = array();
    }
    $_SESSION['stock_adj'][$code][$sku] = stock_adj_get($code, $sku) + (int) $delta;
}

/**
 * ยอดตั้งต้นของสินค้าในสาขา (ก่อนรวมส่วนต่างใน session)
 * สาขาที่ผู้ดูแลเพิ่มเองในเดโมไม่มีตัวเลขในข้อมูลตัวอย่าง → สร้างตัวเลขตัวอย่างให้ (คงที่ทุกครั้ง)
 * ราว 40–90% ของสำนักงานใหญ่ · บางตัวหมด บางตัวใกล้หมด จะได้เห็นสถานะครบ
 * ระบบจริง: สาขาใหม่เริ่มที่ 0 แล้วยอดมาจากการรับเข้า — ไม่ต้องมีส่วนนี้
 */
function product_base_qty($p, $code)
{
    if (isset($p['stock'][$code])) {
        return (int) $p['stock'][$code];
    }
    $h   = abs(crc32($p['sku'] . '|seed|' . $code));
    $ref = isset($p['stock']['HQ']) ? (int) $p['stock']['HQ'] : (int) $p['reorder'] * 2;
    if ($h % 12 === 0) {
        return 0;                                            // หมด
    }
    if ($h % 7 === 0) {
        return max(1, (int) floor($p['reorder'] * 0.6));     // ต่ำกว่าจุดสั่งซื้อ
    }
    return max(1, (int) round($ref * (40 + ($h >> 4) % 51) / 100));
}

/** ยอดคงเหลือจริงตอนนี้ = ยอดตั้งต้น + ส่วนต่างใน session */
function product_qty($p, $branch)
{
    if ($branch === 'ALL') {
        $sum = 0;
        foreach (array_keys(demo_branches()) as $code) {     // รวมสาขาที่เพิ่มใหม่ด้วย
            $sum += product_base_qty($p, $code) + stock_adj_get($code, $p['sku']);
        }
        return $sum;
    }
    return product_base_qty($p, $branch) + stock_adj_get($branch, $p['sku']);
}

/** ราคาขายต่อหน่วย (เดโมคิดจากต้นทุน + กำไร แล้วปัดให้ลงตัว 5 บาท) */
function product_price($p)
{
    if (isset($p['price'])) {
        return (float) $p['price'];                  // ราคาตั้งจริงจากร้าน
    }
    $raw = $p['cost'] * 1.4;
    $r   = round($raw / 5) * 5;
    return $r > 0 ? $r : 5;
}

/** สถานะของสินค้าในสาขาหนึ่ง */
function branch_status($p, $code)
{
    $qty = product_qty($p, $code);
    if ($qty <= 0) {
        return 'out';
    }
    return $qty <= $p['reorder'] ? 'low' : 'ok';
}

/** ค้นสินค้าจาก SKU */
function product_by_sku($sku)
{
    foreach (demo_products() as $p) {
        if ($p['sku'] === $sku) {
            return $p;
        }
    }
    return null;
}

/**
 * สถานะรวมของสินค้า
 * - ดูรายสาขา: เทียบยอดสาขานั้นกับจุดสั่งซื้อ
 * - ดูทุกสาขา: ใช้สถานะที่แย่ที่สุดในบรรดาสาขาทั้งหมด
 */
function product_status($p, $branch)
{
    if ($branch !== 'ALL') {
        return branch_status($p, $branch);
    }
    $worst = 'ok';
    foreach (array_keys(demo_branches()) as $code) {
        $st = branch_status($p, $code);
        if ($st === 'out') {
            return 'out';
        }
        if ($st === 'low') {
            $worst = 'low';
        }
    }
    return $worst;
}

/** สาขาที่วิกฤตที่สุดของสินค้านี้ (ใช้ตอนดูทุกสาขา) */
function worst_branch($p)
{
    $best      = null;
    $bestRatio = INF;
    foreach (array_keys(demo_branches()) as $code) {
        $qty   = product_qty($p, $code);
        $ratio = $p['reorder'] > 0 ? $qty / $p['reorder'] : 0;
        if ($ratio < $bestRatio) {
            $bestRatio = $ratio;
            $best      = $code;
        }
    }
    if ($best === null) {
        $keys = array_keys(demo_branches());
        $best = $keys[0];
    }
    return $best;
}

function stock_summary($branch)
{
    $items = 0;
    $value = 0.0;
    $low   = 0;
    $out   = 0;

    foreach (demo_products() as $p) {
        $items++;
        $value += product_qty($p, $branch) * $p['cost'];
        $st = product_status($p, $branch);
        if ($st === 'low') {
            $low++;
        } elseif ($st === 'out') {
            $out++;
        }
    }

    return array('items' => $items, 'value' => $value, 'low' => $low, 'out' => $out);
}

/** สินค้าที่ถึงจุดสั่งซื้อหรือหมด เรียงจากวิกฤตที่สุด */
function low_stock_products($branch, $limit = 6)
{
    $rows = array();
    foreach (demo_products() as $p) {
        $st = product_status($p, $branch);
        if ($st === 'ok') {
            continue;
        }
        $code    = $branch === 'ALL' ? worst_branch($p) : $branch;
        $qty     = product_qty($p, $code);
        $reorder = $p['reorder'];
        $rows[]  = array(
            'product' => $p,
            'branch'  => $code,
            'qty'     => $qty,
            'reorder' => $reorder,
            'status'  => $st,
            'ratio'   => $reorder > 0 ? $qty / $reorder : 0,
        );
    }
    usort($rows, 'compare_low_ratio');

    return array_slice($rows, 0, $limit);
}

function compare_low_ratio($a, $b)
{
    if ($a['ratio'] == $b['ratio']) {
        return 0;
    }
    return $a['ratio'] < $b['ratio'] ? -1 : 1;
}

function recent_movements($branch, $limit = 7)
{
    $rows = array();
    foreach (demo_movements() as $m) {
        if ($branch === 'ALL' || $m['branch'] === $branch || $m['to'] === $branch) {
            $rows[] = $m;
        }
    }
    return array_slice($rows, 0, $limit);
}

function pending_tasks($branch)
{
    $rows = array();
    foreach (demo_movements() as $m) {
        if ($m['status'] === 'done') {
            continue;
        }
        if ($branch === 'ALL' || $m['branch'] === $branch || $m['to'] === $branch) {
            $rows[] = $m;
        }
    }
    return $rows;
}

/** ชุดข้อมูลกราฟ 12 เดือน — เดือนล่าสุดเท่ากับมูลค่าสต๊อกปัจจุบันเสมอ */
function stock_trend($branch)
{
    $sum     = stock_summary($branch);
    $current = $sum['value'];
    $months  = stock_trend_months();
    $out     = array();

    foreach (stock_trend_shape() as $i => $ratio) {
        $out[] = array(
            'month' => isset($months[$i]) ? $months[$i] : '',
            'value' => round($current * $ratio),
        );
    }
    return $out;
}

/** สาขาที่ผู้ใช้คนนี้เลือกดูได้ */
function visible_branches($user)
{
    $all = demo_branches();
    if ($user['role'] === 'admin') {
        return array_merge(
            array('ALL' => array('name' => 'ทุกสาขา', 'short' => 'ทุกสาขา')),
            $all
        );
    }
    $code = $user['branch'];
    return array($code => $all[$code]);
}

function resolve_branch($user, $requested)
{
    $allowed = array_keys(visible_branches($user));
    if ($requested !== null && in_array($requested, $allowed, true)) {
        return $requested;
    }
    return $user['role'] === 'admin' ? 'ALL' : $user['branch'];
}

function branch_label($code)
{
    return $code === 'ALL' ? 'ทุกสาขา' : branch_name($code);
}

function money($n)
{
    return number_format($n, 0);
}

/** ย่อตัวเลขให้อ่านง่ายบนการ์ด เช่น 2,847,500 → 2.85 ล. */
function money_short($n)
{
    if ($n >= 1000000) {
        return number_format($n / 1000000, 2) . ' ล.';
    }
    if ($n >= 1000) {
        return number_format($n / 1000, 1) . ' พ.';
    }
    return number_format($n, 0);
}

/* ==========================================================
   ผลงานรายบุคคล — ใช้ในหน้า "ภาพรวมของฉัน" ของพนักงาน
   ----------------------------------------------------------
   เดโมสร้างตัวเลขแบบคงที่จากชื่อผู้ใช้ + วันที่ (ค่าเดิมทุกครั้งที่เปิด)
   ระบบจริง: แทนด้วย SELECT จากตาราง stock_move ตาม created_by + วันที่
   ========================================================== */

function staff_seed($username, $ts)
{
    return abs(crc32($username . '|' . date('Ymd', $ts)));
}

/** วันหยุดประจำสัปดาห์ของแต่ละคน (คนละวัน) */
function staff_dayoff($username)
{
    return abs(crc32($username)) % 7;      // 0 = อาทิตย์
}

/** ผลงานของพนักงานหนึ่งคนในหนึ่งวัน */
function staff_day_stat($username, $ts)
{
    $off = ((int) date('w', $ts) === staff_dayoff($username));
    if ($off) {
        return array('off' => true, 'docs' => 0, 'items' => 0, 'qty' => 0);
    }
    $s     = staff_seed($username, $ts);
    $docs  = 4 + ($s % 9);                               // 4–12 เอกสาร
    $items = $docs * (3 + (($s >> 4) % 5));              // 3–7 รายการ/เอกสาร
    $qty   = $items * (2 + (($s >> 9) % 8));             // 2–9 ชิ้น/รายการ

    return array('off' => false, 'docs' => $docs, 'items' => $items, 'qty' => $qty);
}

/** ผลงานย้อนหลัง n วัน (เรียงจากเก่าไปใหม่ วันสุดท้ายคือวันนี้) */
function staff_recent_days($username, $n = 7)
{
    $out = array();
    for ($i = $n - 1; $i >= 0; $i--) {
        $ts  = strtotime('-' . $i . ' day');
        $row = staff_day_stat($username, $ts);
        $row['ts'] = $ts;
        $out[] = $row;
    }
    return $out;
}

/** รวมผลงานตั้งแต่วันที่ 1 ของเดือนถึงวันนี้ */
function staff_month_days($username)
{
    $out   = array();
    $today = (int) date('j');
    for ($d = 1; $d <= $today; $d++) {
        $ts  = mktime(0, 0, 0, (int) date('n'), $d, (int) date('Y'));
        $row = staff_day_stat($username, $ts);
        $row['ts'] = $ts;
        $out[] = $row;
    }
    return $out;
}

function staff_sum($rows)
{
    $t = array('docs' => 0, 'items' => 0, 'qty' => 0, 'days' => 0);
    foreach ($rows as $r) {
        if ($r['off']) {
            continue;
        }
        $t['docs']  += $r['docs'];
        $t['items'] += $r['items'];
        $t['qty']   += $r['qty'];
        $t['days']++;
    }
    return $t;
}

/** เป้าประจำวัน (ชิ้น) — ระบบจริงตั้งค่าได้รายคน/รายสาขา */
function staff_goal($username)
{
    return 300 + (abs(crc32($username)) % 5) * 50;       // 300–500 ชิ้น (ร้านอุปกรณ์มือถือ)
}

/** แบ่งงานวันนี้ตามประเภทเอกสาร */
function staff_work_types($username)
{
    $today = staff_day_stat($username, time());
    $s     = staff_seed($username, time());
    $keys  = array('receive', 'issue', 'count');
    $w     = array(
        30 + ($s % 20),
        20 + (($s >> 3) % 20),
        5  + (($s >> 9) % 10),
    );
    $total = array_sum($w);
    $out   = array();
    foreach ($keys as $i => $k) {
        $out[] = array(
            'type' => $k,
            'qty'  => (int) round($today['qty'] * $w[$i] / $total),
            'pct'  => (int) round(100 * $w[$i] / $total),
        );
    }
    return $out;
}

/** สินค้าที่พนักงานคนนี้แตะบ่อยที่สุดวันนี้ */
function staff_top_products($username, $limit = 4)
{
    $p   = demo_products();
    $n   = count($p);
    $s   = staff_seed($username, time());
    $out = array();
    for ($i = 0; $i < $limit; $i++) {
        $idx   = ($s >> ($i * 3)) % $n;
        $out[] = array(
            'product' => $p[$idx],
            'qty'     => 18 + (($s >> ($i * 5)) % 120),
        );
    }
    return $out;
}

/** อันดับผลงานวันนี้ของพนักงานในสาขาเดียวกัน */
function branch_rank_today($branchCode)
{
    $rows = array();
    foreach (demo_users() as $uname => $u) {
        if ($branchCode !== 'ALL' && $u['branch'] !== $branchCode) {
            continue;
        }
        $d      = staff_day_stat($uname, time());
        $rows[] = array(
            'username' => $uname,
            'name'     => $u['name'],
            'branch'   => $u['branch'],
            'off'      => $d['off'],
            'docs'     => $d['docs'],
            'qty'      => $d['qty'],
        );
    }
    usort($rows, 'compare_rank_qty');
    return $rows;
}

function compare_rank_qty($a, $b)
{
    if ($a['qty'] == $b['qty']) {
        return 0;
    }
    return $a['qty'] > $b['qty'] ? -1 : 1;
}

/** ชื่อวันแบบสั้น เช่น "อา 21" */
function short_day($ts)
{
    $d = array('อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส');
    return $d[(int) date('w', $ts)] . ' ' . (int) date('j', $ts);
}

function thai_month_short($ts)
{
    $m = array('', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
               'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.');
    return $m[(int) date('n', $ts)];
}

function thai_date_full($ts)
{
    $d = array('อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์');
    return $d[(int) date('w', $ts)] . ' ' . (int) date('j', $ts) . ' ' . thai_month_short($ts);
}

/* ##########################################################
   หมวด: ประวัติการทำรายการ
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — ประวัติการทำรายการ
   ----------------------------------------------------------
   ยังไม่มีฐานข้อมูล จึงเก็บไว้ใน session โดยใช้ "สาขา|วันที่" เป็นคีย์
   แบบเดียวกับการเปิด/ปิดร้านและบิลขาย

     $_SESSION['log'][ 'BN|20260923' ][] = array(
         'ts'     => เวลา (unix),
         'time'   => 'HH:MM',
         'type'   => open | close | sale | cash | stock,
         'by'     => ชื่อผู้ทำ,
         'by_user'=> username,
         'title'  => ข้อความหลัก,
         'amount' => ตัวเลขที่เกี่ยวข้อง (หรือ null),
         'ref'    => เลขที่เอกสารที่อ้างถึง เช่น เลขบิล (ถ้ามี),
         'detail' => array( 'หัวข้อ' => 'ค่า', ... )
     )

   ระบบจริง: ตาราง activity_log (branch_id, user_id, type, ref, created_at)
   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

function log_key($code)
{
    return $code . '|' . date('Ymd');
}

/** บันทึกหนึ่งรายการ */
function log_add($code, $type, $user, $title, $detail = array(), $amount = null, $ref = '')
{
    $k = log_key($code);
    if (!isset($_SESSION['log'][$k])) {
        $_SESSION['log'][$k] = array();
    }
    $_SESSION['log'][$k][] = array(
        'ts'      => time(),
        'time'    => date('H:i'),
        'type'    => $type,
        'by'      => isset($user['name']) ? $user['name'] : 'ระบบ',
        'by_user' => isset($user['username']) ? $user['username'] : '',
        'title'   => $title,
        'amount'  => $amount,
        'ref'     => $ref,
        'detail'  => $detail,
    );
    return true;
}

/** ประวัติของสาขาวันนี้ — เรียงใหม่สุดขึ้นก่อน */
function log_today($code, $limit = 0)
{
    $k    = log_key($code);
    $rows = isset($_SESSION['log'][$k]) ? $_SESSION['log'][$k] : array();
    $rows = array_reverse($rows);
    if ($limit > 0 && count($rows) > $limit) {
        $rows = array_slice($rows, 0, $limit);
    }
    return $rows;
}

function log_count($code)
{
    $k = log_key($code);
    return isset($_SESSION['log'][$k]) ? count($_SESSION['log'][$k]) : 0;
}

/** ชนิดของรายการ — ป้ายกำกับ สี และไอคอน */
function log_types()
{
    return array(
        'open'  => array('label' => 'เปิดร้าน',  'tone' => 'in',   'icon' => 'i-store'),
        'close' => array('label' => 'ปิดร้าน',   'tone' => 'adj',  'icon' => 'i-store-off'),
        'sale'  => array('label' => 'ขายสินค้า', 'tone' => 'sale', 'icon' => 'i-cart'),
        'void'  => array('label' => 'ยกเลิกบิล', 'tone' => 'out',  'icon' => 'i-ban'),
        'receive' => array('label' => 'รับเข้า',      'tone' => 'in',  'icon' => 'i-in'),
        'rvoid'   => array('label' => 'ยกเลิกรับเข้า', 'tone' => 'out', 'icon' => 'i-out'),
        'issue'   => array('label' => 'ตัดออก',       'tone' => 'out', 'icon' => 'i-out'),
        'ivoid'   => array('label' => 'ยกเลิกตัดออก',  'tone' => 'in',  'icon' => 'i-in'),
        'adjust'  => array('label' => 'ตรวจนับ',      'tone' => 'adj', 'icon' => 'i-clipboard'),
        'avoid'   => array('label' => 'ยกเลิกตรวจนับ', 'tone' => 'out', 'icon' => 'i-ban'),
        'return'  => array('label' => 'รับคืนสินค้า',  'tone' => 'out', 'icon' => 'i-receipt'),
        'setting' => array('label' => 'ผู้ดูแลตั้งค่า', 'tone' => 'adj', 'icon' => 'i-settings'),
        'cash'  => array('label' => 'เงินสด',    'tone' => 'move', 'icon' => 'i-coin'),
        'stock' => array('label' => 'สต๊อก',     'tone' => 'adj',  'icon' => 'i-box'),
    );
}

function log_type_of($key)
{
    $all = log_types();
    return isset($all[$key]) ? $all[$key] : array('label' => $key, 'tone' => 'adj', 'icon' => 'i-info');
}

/** กรองตามชนิด — ใช้กับปุ่มกรองบนหน้าประวัติ */
function log_filter($rows, $type)
{
    if ($type === '') {
        return $rows;
    }
    $out = array();
    foreach ($rows as $r) {
        if ($r['type'] === $type) {
            $out[] = $r;
        }
    }
    return $out;
}

/** สรุปจำนวนรายการแยกตามชนิด (ไว้โชว์บนปุ่มกรอง) */
function log_counts($rows)
{
    $out = array();
    foreach (array_keys(log_types()) as $t) {
        $out[$t] = 0;
    }
    foreach ($rows as $r) {
        if (isset($out[$r['type']])) {
            $out[$r['type']]++;
        }
    }
    return $out;
}

/* ##########################################################
   หมวด: การยืนยันตัวตนด้วย session (ยังไม่ต่อฐานข้อมูล)
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — การยืนยันตัวตนด้วย session (ยังไม่ต่อฐานข้อมูล)
   รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

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
    $page = basename($_SERVER['SCRIPT_NAME']);
    if ($u['role'] === 'account' && !in_array($page, account_pages(), true)) {
        header('Location: ' . url('account.php'));
        exit;
    }
    /* พนักงานที่ถูกปิดใช้งานระหว่างที่ยังเข้าระบบอยู่ → ออกจากระบบ */
    $all = demo_users_all();
    if (!isset($all[$u['username']]) || !user_active($all[$u['username']])) {
        logout_user();
        header('Location: ' . url('login.php'));
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
    return array('account.php', 'account-settings.php', 'logout.php');
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
    return array('account.php', 'account-settings.php', 'logout.php', 'login.php', 'index.php');
}

/* ##########################################################
   หมวด: เลขที่บิลและ VAT (ฝ่ายบัญชีตั้งค่าได้)
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — เลขที่บิลและ VAT (ฝ่ายบัญชีตั้งค่าได้)
   ----------------------------------------------------------
   - ตอนขาย พนักงานเลือกได้ว่าบิลนี้ "VAT" หรือ "ไม่ VAT"
   - บิล VAT กับบิลไม่ VAT ใช้เลขคนละชุด แต่ละสาขามีรหัสนำหน้าของตัวเอง 2 ตัว
       รูปแบบ  {รหัส}{ปี ค.ศ.}-{เดือน}-{เลขรัน 4 หลัก}   เช่น BP2026-01-0001
       เลขรันนับใหม่ทุกเดือน แยกตามสาขาและตามชุด (VAT / ไม่ VAT)
   - ราคาขายเป็นราคารวม VAT แล้ว → บิล VAT แยกยอดก่อน VAT และ VAT 7% ให้
   - ฝ่ายบัญชีตั้งรหัสนำหน้า + เลขผู้เสียภาษีของแต่ละสาขาได้เอง (หน้า "ตั้งค่าบัญชี")

   เดโมเก็บค่าที่ตั้งไว้ใน $_SESSION['cfg']['acct'][ สาขา ][ ชื่อค่า ]
   ระบบจริง: คอลัมน์ใน ao_stock_branch และเก็บเลขที่บิลตอนออกบิล (ไม่คำนวณย้อนหลัง)
   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

function acct_setting_defaults()
{
    return array(
        'HQ' => array('prefix_vat' => 'HQV', 'prefix_novat' => 'HQ', 'tax_id' => '0105566012345', 'tax_branch' => '00000'),
        'RS' => array('prefix_vat' => 'RSV', 'prefix_novat' => 'RS', 'tax_id' => '0105566012345', 'tax_branch' => '00001'),
        'BN' => array('prefix_vat' => 'BNV', 'prefix_novat' => 'BN', 'tax_id' => '0105566012345', 'tax_branch' => '00002'),
    );
}

function acct_setting($code, $key)
{
    if (isset($_SESSION['cfg']['acct'][$code][$key])) {
        return $_SESSION['cfg']['acct'][$code][$key];
    }
    $d = acct_setting_defaults();
    return isset($d[$code][$key]) ? $d[$code][$key] : '';
}

function acct_setting_set($code, $key, $val)
{
    $_SESSION['cfg']['acct'][$code][$key] = $val;
}

/** รหัสนำหน้าของชุดเลข: $vat = true → ชุด VAT */
function bill_prefix($code, $vat)
{
    return acct_setting($code, $vat ? 'prefix_vat' : 'prefix_novat');
}

/** ประกอบเลขที่บิล เช่น BP2026-01-0001 */
function bill_no_format($prefix, $ts, $n)
{
    return $prefix . date('Y', $ts) . '-' . date('m', $ts) . '-' . str_pad($n, BILL_RUN_DIGITS, '0', STR_PAD_LEFT);
}

/** แยกยอดรวม (รวม VAT แล้ว) เป็น array(ก่อน VAT, VAT) */
function vat_split($total)
{
    $vat = round($total * VAT_RATE / (100 + VAT_RATE), 2);
    return array(round($total - $vat, 2), $vat);
}

/** ชื่อชุดเลข */
function bill_type_label($vat)
{
    return $vat ? 'VAT' : 'ไม่ VAT';
}

/**
 * จำนวนบิลที่ออกไปแล้วในเดือนนี้ก่อนวันที่ $ts แยกตามชุด — ใช้ต่อเลขรัน
 * วันก่อน ๆ มาจากข้อมูลสมมติ (past_bills_gen ใน return.php)
 * คืน array('v' => n, 'n' => n)
 */
function month_bill_counts($code, $ts)
{
    static $cache = array();
    $ck = $code . '|' . date('Ymd', $ts);
    if (isset($cache[$ck])) {
        return $cache[$ck];
    }
    $cnt   = array('v' => 0, 'n' => 0);
    $first = strtotime(date('Y-m-01', $ts));
    for ($d = $first; $d < strtotime(date('Y-m-d', $ts)); $d = strtotime('+1 day', $d)) {
        foreach (past_bills_gen($code, $d) as $b) {
            $cnt[$b['vat'] ? 'v' : 'n']++;
        }
    }
    $cache[$ck] = $cnt;
    return $cnt;
}

/** เลขที่บิลใบถัดไปของวันนี้ (นับรวมบิลที่ถูกยกเลิก — เลขที่ใช้ไปแล้วห้ามใช้ซ้ำ) */
function bill_next_no_series($code, $vat)
{
    $cnt = month_bill_counts($code, time());
    $n   = $cnt[$vat ? 'v' : 'n'];
    foreach (bills_today($code) as $b) {
        if (!empty($b['vat']) === (bool) $vat) {
            $n++;
        }
    }
    return bill_no_format(bill_prefix($code, $vat), time(), $n + 1);
}

/** ตรวจรหัสนำหน้า: A–Z / 0–9 ยาว 1–6 ตัว และไม่ซ้ำกับชุดอื่นทุกสาขา */
function acct_prefix_error($code, $key, $val, $all)
{
    if (!preg_match('/^[A-Z0-9]{1,6}$/', $val)) {
        return 'รหัสต้องเป็นตัวอักษรภาษาอังกฤษพิมพ์ใหญ่หรือตัวเลข 1–6 ตัว';
    }
    foreach ($all as $c => $pp) {
        foreach ($pp as $k => $v) {
            if (($c !== $code || $k !== $key) && $v === $val) {
                return 'รหัส ' . $val . ' ซ้ำกับชุดเลขอื่น (' . branch_name($c) . ' · ' . ($k === 'prefix_vat' ? 'VAT' : 'ไม่ VAT') . ')';
            }
        }
    }
    return '';
}

/* ##########################################################
   หมวด: เปิด / ปิดร้านประจำวัน และเงินทอนในลิ้นชัก
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — เปิด / ปิดร้านประจำวัน และเงินทอนในลิ้นชัก
   ----------------------------------------------------------
   หลักการ
   - ร้านเปิดวันละครั้งต่อสาขา ใครมาถึงก่อนเป็นคนเปิด
     คนที่ login ทีหลังเข้าใช้งานได้เลย ไม่ต้องเปิดซ้ำ
   - เงินทอนเริ่มวัน = ยอดที่แยกไว้ตอนปิดร้านเมื่อวาน + ที่เติมเพิ่มวันนี้
   - ตอนปิดร้านจะนับเงินจริง เทียบกับยอดที่ควรมี แล้วแยกเงินทอนไว้สำหรับพรุ่งนี้

   เดโมเก็บสถานะไว้ใน session (หายเมื่อออกจากระบบ)
   ระบบจริง: ตาราง store_day + cash_move ตามที่ออกแบบไว้
   ========================================================== */

/** เงินทอนมาตรฐานของสาขา (ฟิลด์ default_float) */
function branch_default_float($code)
{
    return branch_setting($code, 'default_float');      // ผู้ดูแลตั้งที่หน้า "จัดการสาขา"
}

/** เงินทอนที่แยกไว้ตอนปิดร้านเมื่อวาน (เดโมสร้างจากรหัสสาขา + วันที่) */
function store_carry($code)
{
    $y     = strtotime('-1 day');
    $s     = abs(crc32($code . date('Ymd', $y)));
    $staff = demo_users();
    $names = array();
    foreach ($staff as $u) {
        if ($u['branch'] === $code) {
            $names[] = $u['name'];
        }
    }
    if (!$names) {
        $names[] = 'พนักงาน';
    }

    return array(
        'amount' => branch_default_float($code),
        'by'     => $names[$s % count($names)],
        'time'   => date('j/n', $y) . ' ' . str_pad(18 + ($s % 4), 2, '0', STR_PAD_LEFT) . ':' . str_pad(($s >> 3) % 60, 2, '0', STR_PAD_LEFT),
    );
}

/** เงินที่เติม / หยิบออกจากลิ้นชักระหว่างวัน (เดโม) */
function store_daily_cash($code)
{
    $s = abs(crc32($code . date('Ymd')));
    return array(
        'topup'    => (($s % 3) === 0) ? 500 : 0,
        'withdraw' => 40 + (($s >> 5) % 160),
    );
}

/* ---------- สถานะร้านใน session ---------- */

function store_key($code)
{
    return $code . '|' . date('Ymd');
}

function store_state($code)
{
    $k = store_key($code);
    return isset($_SESSION['store'][$k]) ? $_SESSION['store'][$k] : null;
}

function store_is_open($code)
{
    $st = store_state($code);
    return $st !== null && empty($st['closed_at']);
}

function store_is_closed($code)
{
    $st = store_state($code);
    return $st !== null && !empty($st['closed_at']);
}

/** บันทึกการเปิดร้าน */
function store_open($code, $user, $topup, $counted, $reason)
{
    $carry = store_carry($code);
    $topup = max(0, (int) $topup);

    $state = array(
        'branch'      => $code,
        'opened_at'   => date('H:i'),
        'opened_by'   => $user['name'],
        'opened_user' => $user['username'],
        'carry'       => $carry['amount'],
        'topup'       => $topup,
        'float'       => $carry['amount'] + $topup,
        'counted'     => null,
        'reason'      => '',
        'closed_at'   => '',
    );

    /* กรณีแจ้งว่านับเงินทอนได้ไม่ตรงกับยอดยกมา */
    if ($counted !== null && $counted !== '' && (int) $counted !== (int) $carry['amount']) {
        $state['counted'] = (int) $counted;
        $state['reason']  = trim($reason);
        $state['float']   = (int) $counted + $topup;
    }

    $_SESSION['store'][store_key($code)] = $state;

    /* ---- เก็บลงประวัติการทำรายการ ---- */
    $detail = array(
        'เวลาที่เปิด'     => $state['opened_at'] . ' น.',
        'เงินทอนยกมา'    => money2($carry['amount']) . ' บาท (ปิดร้านเมื่อวานโดย ' . $carry['by'] . ')',
    );

    if ($state['counted'] !== null) {
        $diff = (int) $state['counted'] - (int) $carry['amount'];
        $detail['นับได้จริง']   = money2($state['counted']) . ' บาท';
        $detail['ผลต่างจากยกมา'] = ($diff > 0 ? '+' : '') . money2($diff) . ' บาท'
                                 . ($state['reason'] !== '' ? ' — ' . $state['reason'] : '');
    }

    $detail['เติมเงินทอนเพิ่ม'] = $topup > 0 ? '+' . money2($topup) . ' บาท' : 'ไม่ได้เติม';
    $detail['เงินทอนเริ่มวันนี้'] = money2($state['float']) . ' บาท';

    log_add($code, 'open', $user, 'เปิดร้าน ' . branch_name($code), $detail, $state['float']);

    return $state;
}

/** บันทึกการปิดร้าน */
function store_close($code, $user, $counted, $keep, $note)
{
    $k = store_key($code);
    if (!isset($_SESSION['store'][$k])) {
        return null;
    }
    $_SESSION['store'][$k]['closed_at']   = date('H:i');
    $_SESSION['store'][$k]['closed_by']   = $user['name'];
    $_SESSION['store'][$k]['cash_counted'] = (int) $counted;
    $_SESSION['store'][$k]['keep']        = max(0, (int) $keep);
    $_SESSION['store'][$k]['note']        = trim($note);

    /* ---- เก็บลงประวัติการทำรายการ ---- */
    $st       = $_SESSION['store'][$k];
    $expected = store_expected_cash($code);
    $diff     = (int) $st['cash_counted'] - (int) $expected;

    $detail = array(
        'เวลาที่ปิด'      => $st['closed_at'] . ' น.',
        'เงินที่ควรมี'     => money2($expected) . ' บาท',
        'นับได้จริง'      => money2($st['cash_counted']) . ' บาท',
        'ผลต่าง'         => ($diff > 0 ? 'เกิน +' : ($diff < 0 ? 'ขาด −' : 'ตรงพอดี ')) . money2(abs($diff)) . ' บาท',
        'เงินทอนที่แยกไว้พรุ่งนี้' => money2($st['keep']) . ' บาท',
        'นำส่ง'          => money2(max(0, (int) $st['cash_counted'] - (int) $st['keep'])) . ' บาท',
    );
    if ($st['note'] !== '') {
        $detail['หมายเหตุ'] = $st['note'];
    }

    log_add($code, 'close', $user, 'ปิดร้าน ' . branch_name($code), $detail, $st['cash_counted']);

    return $_SESSION['store'][$k];
}

/** เปิดร้านใหม่หลังปิดไปแล้ว — ผู้ดูแลเท่านั้น ต้องมีเหตุผล
    ยอดปิดร้านรอบก่อนเก็บไว้ในประวัติ แล้วร้านกลับมาเปิดขายต่อได้ ปิดใหม่อีกครั้งตามปกติ */
function store_reopen($code, $user, $reason)
{
    $k = store_key($code);
    if (!isset($_SESSION['store'][$k]) || empty($_SESSION['store'][$k]['closed_at'])) {
        return null;
    }
    $st = $_SESSION['store'][$k];
    $_SESSION['store'][$k]['reopens'][] = array(
        'at' => date('H:i'), 'by' => $user['name'], 'reason' => trim($reason),
        'closed_at' => $st['closed_at'], 'closed_by' => $st['closed_by'], 'counted' => $st['cash_counted'],
        'diff' => (float) $st['cash_counted'] - (float) store_expected_cash($code),   // ขาด/เกินของรอบที่ปิดไป
    );
    $_SESSION['store'][$k]['closed_at'] = '';

    log_add($code, 'open', $user, 'เปิดร้านใหม่หลังปิด ' . branch_name($code), array(
        'ปิดไปเมื่อ'  => $st['closed_at'] . ' น. โดย ' . $st['closed_by'],
        'เงินที่นับได้ตอนปิด' => money2($st['cash_counted']) . ' บาท',
        'เหตุผล'     => trim($reason),
        'ผู้เปิดใหม่'  => $user['name'] . ' (ผู้ดูแล)',
    ));
    return $_SESSION['store'][$k];
}

/** เงินสดที่คืนลูกค้าวันนี้ (รับคืนสินค้า) — จ่ายออกจากลิ้นชักของวันนี้เสมอ
    แม้บิลเดิมจะเป็นของวันที่ปิดร้านไปแล้ว */
function store_refunds($code)
{
    $k = $code . '|' . date('Ymd');
    $t = 0;
    if (isset($_SESSION['ret'][$k])) {
        foreach ($_SESSION['ret'][$k] as $r) {
            $t += $r['refund'];
        }
    }
    return $t;
}

/** ยอดขายเงินสดวันนี้ (ไม่นับบิลที่ยกเลิก · บิลโอน/พร้อมเพย์ไม่เข้าลิ้นชัก) */
function store_cash_sales($code)
{
    $s = sale_summary($code);
    return $s['cash'];
}

/** ยอดเงินที่ควรมีในลิ้นชักตอนนี้
    = เงินทอนเริ่มวัน + เติม + ขายเงินสด − หยิบออก − คืนเงินลูกค้า */
function store_expected_cash($code)
{
    $st = store_state($code);
    if ($st === null) {
        return 0;
    }
    $c = store_daily_cash($code);
    return $st['float'] + $c['topup'] + store_cash_sales($code) - $c['withdraw'] - store_refunds($code);
}

function money2($n)
{
    return number_format($n, 2);
}

/* ##########################################################
   หมวด: ขายสินค้า (ตะกร้า + บิล)
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — ขายสินค้า (ตะกร้า + บิล)
   ----------------------------------------------------------
   ยังไม่มีฐานข้อมูล ทุกอย่างอยู่ใน session ทั้งหมด
   ใช้ "สาขา|วันที่" เป็นคีย์ แบบเดียวกับการเปิด/ปิดร้าน

     $_SESSION['cart']                      ตะกร้าที่กำลังขาย  SKU => จำนวน
     $_SESSION['sale'][ 'BN|20260923' ]     บิลของสาขานั้นในวันนั้น
     $_SESSION['stock_adj'][ 'BN' ][ SKU ]  ยอดสต๊อกที่ขยับแล้ว

   ระบบจริง: ตาราง sale_bill + sale_item + stock_move
   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

/* ---------- ตะกร้า ---------- */

function cart_all()
{
    return isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : array();
}

function cart_count()
{
    $n = 0;
    foreach (cart_all() as $q) {
        $n += (int) $q;
    }
    return $n;
}

/** เพิ่มจำนวนในตะกร้า ไม่ให้เกินยอดคงเหลือของสาขา */
function cart_add($sku, $branch, $step = 1)
{
    $p = product_by_sku($sku);
    if ($p === null) {
        return false;
    }
    $have = product_qty($p, $branch);
    $cur  = isset($_SESSION['cart'][$sku]) ? (int) $_SESSION['cart'][$sku] : 0;
    $new  = $cur + (int) $step;

    if ($new <= 0) {
        unset($_SESSION['cart'][$sku]);
        return true;
    }
    if ($new > $have) {
        $new = $have;
    }
    if ($new <= 0) {
        unset($_SESSION['cart'][$sku]);
        return false;
    }
    $_SESSION['cart'][$sku] = $new;
    return true;
}

/** กำหนดจำนวนตรง ๆ (พนักงานพิมพ์แก้เองเมื่อกดผิด) */
function cart_set($sku, $branch, $qty)
{
    $p = product_by_sku($sku);
    if ($p === null) {
        return false;
    }
    $qty = (int) $qty;
    if ($qty <= 0) {
        unset($_SESSION['cart'][$sku]);
        return true;
    }
    /* สต๊อกยังไม่ถูกหักจนกว่าจะบันทึกบิล ยอดคงเหลือจึงเป็นเพดานโดยตรง */
    $have = product_qty($p, $branch);
    $ok   = true;
    if ($qty > $have) {
        $qty = $have;
        $ok  = false;
    }
    $_SESSION['cart'][$sku] = $qty;
    return $ok;
}

function cart_remove($sku)
{
    unset($_SESSION['cart'][$sku]);
}

function cart_clear()
{
    $_SESSION['cart'] = array();
}

/** แปลงตะกร้าเป็นรายการพร้อมราคา */
function cart_lines()
{
    $out = array();
    foreach (cart_all() as $sku => $qty) {
        $p = product_by_sku($sku);
        if ($p === null) {
            continue;
        }
        $price = product_price($p);
        $out[] = array(
            'sku'   => $sku,
            'name'  => $p['name'],
            'unit'  => $p['unit'],
            'price' => $price,
            'qty'   => (int) $qty,
            'sum'   => $price * (int) $qty,
        );
    }
    return $out;
}

function cart_total()
{
    $t = 0;
    foreach (cart_lines() as $l) {
        $t += $l['sum'];
    }
    return $t;
}

/* ---------- บิล ---------- */

function sale_key($code)
{
    return $code . '|' . date('Ymd');
}

function bills_today($code)
{
    $k = sale_key($code);
    return isset($_SESSION['sale'][$k]) ? $_SESSION['sale'][$k] : array();
}

/** เลขที่บิลใบถัดไป — ชุดตามที่เลือก VAT / ไม่ VAT (ดูหมวด "เลขที่บิลและ VAT") */
function bill_next_no($code, $vat = false)
{
    return bill_next_no_series($code, $vat);
}

/**
 * บันทึกบิล: ตัดสต๊อกของสาขา แล้วเก็บบิลไว้ใน session
 * คืนค่าเป็นบิลที่บันทึกแล้ว หรือ null ถ้าตะกร้าว่าง
 */
function bill_save($code, $user, $method, $received, $vat = false, $net = null)
{
    $lines = cart_lines();
    if (!$lines) {
        return null;
    }
    $subtotal = cart_total();
    /* ผู้ขายลดราคาได้ด้วยการแก้ยอดที่ต้องชำระ → ส่วนต่างลงเป็นส่วนลดท้ายบิล (เพิ่มเกินราคาเต็มไม่ได้) */
    $total = ($net !== null && $net > 0 && $net <= $subtotal) ? round($net, 2) : $subtotal;
    $discount = round($subtotal - $total, 2);
    $recv  = ($method === 'cash') ? (int) $received : $total;
    if ($recv < $total) {
        $recv = $total;
    }

    $bill = array(
        'no'       => bill_next_no($code, $vat),
        'vat'      => (bool) $vat,
        'time'     => date('H:i'),
        'branch'   => $code,
        'by'       => $user['name'],
        'by_user'  => $user['username'],
        'method'   => ($method === 'transfer') ? 'transfer' : 'cash',
        'lines'    => $lines,
        'items'    => count($lines),
        'qty'      => cart_count(),
        'subtotal' => $subtotal,
        'discount' => $discount,
        'total'    => $total,
        'received' => $recv,
        'change'   => $recv - $total,
    );

    foreach ($lines as $l) {
        stock_adj_add($code, $l['sku'], -$l['qty']);      // ขายออก = สต๊อกลด
    }

    $k = sale_key($code);
    if (!isset($_SESSION['sale'][$k])) {
        $_SESSION['sale'][$k] = array();
    }
    $_SESSION['sale'][$k][] = $bill;
    cart_clear();

    /* ---- เก็บลงประวัติการทำรายการ ---- */
    $vs    = vat_split($total);
    $names = array();
    foreach ($lines as $l) {
        $names[] = $l['name'] . ' ×' . $l['qty'];
    }
    $detail = array(
        'จำนวน'    => $bill['items'] . ' รายการ · ' . $bill['qty'] . ' ชิ้น',
        'รายการ'   => implode(' · ', $names),
        'ยอดรวม'   => number_format($total, 2) . ' บาท'
                      . ($discount > 0 ? ' (ราคาเต็ม ' . number_format($subtotal, 2) . ' ส่วนลด ' . number_format($discount, 2) . ')' : ''),
        'ประเภทบิล' => $vat ? 'VAT (ก่อน VAT ' . number_format($vs[0], 2) . ' + VAT ' . number_format($vs[1], 2) . ')' : 'ไม่ VAT',
        'ชำระโดย'  => ($bill['method'] === 'cash') ? 'เงินสด' : 'โอน / พร้อมเพย์',
    );
    if ($bill['method'] === 'cash') {
        $detail['รับมา'] = number_format($recv, 2) . ' บาท';
        $detail['เงินทอน'] = number_format($bill['change'], 2) . ' บาท';
    }

    log_add($code, 'sale', $user, 'ขายสินค้า บิล ' . $bill['no'], $detail, $total, $bill['no']);

    return $bill;
}

/**
 * ยกเลิกบิลที่บันทึกไปแล้ว — คืนสต๊อกกลับให้ครบ แล้วทำเครื่องหมายว่ายกเลิก
 * บิลไม่ถูกลบทิ้ง ยังเห็นในประวัติเสมอ เพื่อให้ตรวจย้อนหลังได้
 * คืนค่า: บิลที่ยกเลิกแล้ว หรือ null ถ้าไม่พบ / ยกเลิกไปแล้ว
 */
function bill_void($code, $no, $user, $reason, $reopen = false)
{
    $k = sale_key($code);
    if (!isset($_SESSION['sale'][$k])) {
        return null;
    }
    foreach ($_SESSION['sale'][$k] as $i => $b) {
        if ($b['no'] !== $no || !empty($b['void'])) {
            continue;
        }
        foreach ($b['lines'] as $l) {
            stock_adj_add($code, $l['sku'], $l['qty']);          // คืนของกลับเข้าสต๊อก
        }
        $_SESSION['sale'][$k][$i]['void']        = true;
        $_SESSION['sale'][$k][$i]['void_at']     = date('H:i');
        $_SESSION['sale'][$k][$i]['void_by']     = $user['name'];
        $_SESSION['sale'][$k][$i]['void_user']   = $user['username'];
        $_SESSION['sale'][$k][$i]['void_reason'] = trim($reason);

        $_SESSION['sale'][$k][$i]['void_mode'] = $reopen ? 'edit' : 'void';

        $v      = $_SESSION['sale'][$k][$i];
        $detail = array(
            'บิลเดิม'    => $v['no'] . ' · ขายเมื่อ ' . $v['time'] . ' น. โดย ' . $v['by'],
            'ยอดที่คืน'   => number_format($v['total'], 2) . ' บาท',
            'จำนวนที่คืนสต๊อก' => $v['items'] . ' รายการ · ' . $v['qty'] . ' ชิ้น',
            'เหตุผล'     => $v['void_reason'] !== '' ? $v['void_reason'] : 'ไม่ได้ระบุ',
        );
        if ($reopen) {
            $detail['การทำต่อ'] = 'ดึงรายการกลับเข้าตะกร้าเพื่อออกบิลใหม่ทดแทน';
        }

        log_add($code, 'void', $user,
                ($reopen ? 'ยกเลิกเพื่อแก้ไข บิล ' : 'ยกเลิกบิล ') . $v['no'],
                $detail, $v['total'], $v['no']);

        return $v;
    }
    return null;
}

/**
 * ดึงรายการทั้งบิลกลับเข้าตะกร้า — ใช้คู่กับการยกเลิกเพื่อแก้ไข
 * พนักงานไม่ต้องยิงบาร์โค้ดหรือกรอกใหม่ทั้งบิล แก้เฉพาะแถวที่ผิดแล้วรับเงินใหม่
 * จำนวนที่คืนเข้าสต๊อกจากการยกเลิกทำให้ใส่กลับได้ครบเสมอ
 */
function cart_from_bill($bill, $branch)
{
    $_SESSION['cart'] = array();
    foreach ($bill['lines'] as $l) {
        cart_set($l['sku'], $branch, $l['qty']);
    }
    return cart_count();
}

/** บิลนี้มีการรับคืนสินค้าไปแล้วหรือยัง — ถ้ามีแล้ว ห้ามยกเลิก/แก้ทั้งบิล (ของจะถูกคืนซ้ำ) */
function bill_returned_any($no)
{
    return !empty($_SESSION['ret_by_bill'][$no]) && array_sum($_SESSION['ret_by_bill'][$no]) > 0;
}

function bill_by_no($code, $no)
{
    foreach (bills_today($code) as $b) {
        if ($b['no'] === $no) {
            return $b;
        }
    }
    return null;
}

/** สรุปยอดขายวันนี้ของสาขา */
function sale_summary($code)
{
    $sum = array('bills' => 0, 'qty' => 0, 'total' => 0, 'cash' => 0, 'transfer' => 0, 'void' => 0);
    foreach (bills_today($code) as $b) {
        if (!empty($b['void'])) {                 // บิลที่ยกเลิกไม่นับเป็นยอดขาย
            $sum['void']++;
            continue;
        }
        $sum['bills']++;
        $sum['qty']   += $b['qty'];
        $sum['total'] += $b['total'];
        if ($b['method'] === 'cash') {
            $sum['cash'] += $b['total'];
        } else {
            $sum['transfer'] += $b['total'];
        }
    }
    return $sum;
}

/** ยอดขายวันนี้ของพนักงานคนหนึ่ง */
function sale_summary_user($code, $username)
{
    $sum = array('bills' => 0, 'qty' => 0, 'total' => 0);
    foreach (bills_today($code) as $b) {
        if ($b['by_user'] !== $username || !empty($b['void'])) {
            continue;
        }
        $sum['bills']++;
        $sum['qty']   += $b['qty'];
        $sum['total'] += $b['total'];
    }
    return $sum;
}

/** หมวดสินค้าทั้งหมด (ไว้ทำปุ่มกรอง) */
function product_cats()
{
    $out = array();
    foreach (demo_products() as $p) {
        if (!in_array($p['cat'], $out, true)) {
            $out[] = $p['cat'];
        }
    }
    return $out;
}

/** ค้นหาสินค้าสำหรับหน้าขาย */
function sale_products($branch, $q, $cat)
{
    $q   = trim($q);
    $out = array();
    foreach (demo_products() as $p) {
        if ($cat !== '' && $p['cat'] !== $cat) {
            continue;
        }
        if ($q !== '' && stripos($p['name'], $q) === false && stripos($p['sku'], $q) === false) {
            continue;
        }
        $p['qty']   = product_qty($p, $branch);
        $p['price'] = product_price($p);
        $out[]      = $p;
    }
    return $out;
}

/* ##########################################################
   หมวด: สต๊อกสินค้า รูปสินค้า และรับเข้า
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — ตัวช่วยของหน้ารายการสินค้าในสต๊อก
   ยอดคงเหลือมาจาก product_qty() ซึ่งรวมส่วนที่ขยับไว้ใน session แล้ว
   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

/* ==========================================================
   รูปสินค้า
   ----------------------------------------------------------
   วางไฟล์รูปไว้ที่  assets/products/<SKU>.jpg  (หรือ .png .webp)
   เช่น assets/products/CS-001.jpg  แล้วระบบจะหยิบมาใช้เอง
   ไม่มีรูป = วาดรูปแทนจากหมวดสินค้าให้อัตโนมัติ ไม่มีช่องว่าง
   ระบบจริง: เก็บชื่อไฟล์ไว้ในตารางสินค้าแล้วอ่านจากฟิลด์นั้นแทน
   ========================================================== */

function product_img($p)
{
    $dir = dirname(__FILE__) . '/../assets/products/';
    foreach (array('jpg', 'jpeg', 'png', 'webp') as $ext) {
        if (is_file($dir . $p['sku'] . '.' . $ext)) {
            return 'assets/products/' . $p['sku'] . '.' . $ext;
        }
    }
    return '';
}

/** ไอคอนและโทนสีประจำหมวด ใช้ตอนที่ยังไม่มีรูปจริง */
function cat_style($cat)
{
    $map = array(
        'เคสมือถือ'          => array('i-phone',   'a'),
        'เคส iPad & Tablet'  => array('i-tablet',  'b'),
        'ฟิล์มมือถือ'         => array('i-shield',  'c'),
        'ฟิล์ม iPad & Tablet' => array('i-shield',  'b'),
        'กระจกเลนส์กล้อง'    => array('i-camera',  'd'),
        'ฟิล์มไฮโดรเจล'       => array('i-shield',  'a'),
        'สายชาร์จ'           => array('i-cable',   'd'),
    );
    if (isset($map[$cat])) {
        return $map[$cat];
    }
    $tones = array('a', 'b', 'c', 'd');
    return array('i-box', $tones[abs(crc32($cat)) % 4]);
}

/** กล่องรูปสินค้า — ใช้ได้ทั้งในตารางและบนการ์ดหน้าขาย */
function thumb_html($p, $extra = '')
{
    $img = product_img($p);
    $sty = cat_style($p['cat']);
    $cls = 'thumb thumb--' . $sty[1] . ($extra !== '' ? ' ' . $extra : '');

    if ($img !== '') {
        return '<span class="' . $cls . '"><img src="' . e($img) . '" alt="" loading="lazy"></span>';
    }
    return '<span class="' . $cls . '" aria-hidden="true">'
         . '<svg class="ico"><use href="#' . $sty[0] . '"/></svg></span>';
}

/* ==========================================================
   รับสินค้าเข้าสต๊อก
   ----------------------------------------------------------
   $_SESSION['recv'][ 'BN|20260923' ][] = เอกสารรับเข้าหนึ่งใบ
   ระบบจริง: ตาราง receive_doc + receive_item + stock_move
   ========================================================== */

/* ---------- ใบรับของที่กำลังทำอยู่ (ร่าง) ----------
   เลือกสินค้าเข้ามาทีละตัว ปรับจำนวนได้ แล้วค่อยบันทึกทั้งใบครั้งเดียว
   เก็บไว้ใน $_SESSION['recv_draft'] เหมือนตะกร้าของหน้าขาย              */

function rdraft_all()
{
    return isset($_SESSION['recv_draft']) && is_array($_SESSION['recv_draft'])
         ? $_SESSION['recv_draft'] : array();
}

function rdraft_count()
{
    $n = 0;
    foreach (rdraft_all() as $q) {
        $n += (int) $q;
    }
    return $n;
}

function rdraft_add($sku, $step = 1)
{
    if (product_by_sku($sku) === null) {
        return false;
    }
    $cur = isset($_SESSION['recv_draft'][$sku]) ? (int) $_SESSION['recv_draft'][$sku] : 0;
    $new = $cur + (int) $step;
    if ($new <= 0) {
        unset($_SESSION['recv_draft'][$sku]);
        return true;
    }
    $_SESSION['recv_draft'][$sku] = $new;
    return true;
}

function rdraft_set($sku, $qty)
{
    if (product_by_sku($sku) === null) {
        return false;
    }
    $qty = (int) $qty;
    if ($qty <= 0) {
        unset($_SESSION['recv_draft'][$sku]);
        return true;
    }
    $_SESSION['recv_draft'][$sku] = $qty;      // รับเข้าไม่มีเพดาน ของมาเท่าไรก็รับเท่านั้น
    return true;
}

function rdraft_remove($sku)
{
    unset($_SESSION['recv_draft'][$sku]);
}

function rdraft_clear()
{
    $_SESSION['recv_draft'] = array();
}

/** แปลงร่างเป็นรายการพร้อมยอดคงเหลือก่อน/หลัง */
function rdraft_lines($code)
{
    $out = array();
    foreach (rdraft_all() as $sku => $qty) {
        $p = product_by_sku($sku);
        if ($p === null) {
            continue;
        }
        $have  = product_qty($p, $code);
        $out[] = array(
            'sku'   => $sku,
            'name'  => $p['name'],
            'cat'   => $p['cat'],
            'unit'  => $p['unit'],
            'qty'   => (int) $qty,
            'have'  => $have,
            'after' => $have + (int) $qty,
            'p'     => $p,
        );
    }
    return $out;
}

function recv_key($code)
{
    return $code . '|' . date('Ymd');
}

function receives_today($code)
{
    $k = recv_key($code);
    return isset($_SESSION['recv'][$k]) ? $_SESSION['recv'][$k] : array();
}

function receive_next_no($code)
{
    return 'RC-' . date('ymd') . '-' . str_pad(count(receives_today($code)) + 1, 4, '0', STR_PAD_LEFT);
}

/**
 * บันทึกการรับเข้า: บวกสต๊อกแล้วลงประวัติ
 * ผู้รับเข้าและสาขา มาจากบัญชีที่ล็อกอิน ไม่ต้องกรอกซ้ำ
 */
function receive_save($code, $user, $ref, $note, $lines)
{
    $qty = 0;
    foreach ($lines as $l) {
        $qty += (int) $l['qty'];
        stock_adj_add($code, $l['sku'], (int) $l['qty']);      // รับเข้า = สต๊อกเพิ่ม
    }

    $doc = array(
        'no'       => receive_next_no($code),
        'time'     => date('H:i'),
        'branch'   => $code,
        'by'       => $user['name'],
        'by_user'  => $user['username'],
        'ref'      => $ref,
        'note'     => $note,
        'lines'    => $lines,
        'items'    => count($lines),
        'qty'      => $qty,
    );

    $k = recv_key($code);
    if (!isset($_SESSION['recv'][$k])) {
        $_SESSION['recv'][$k] = array();
    }
    $_SESSION['recv'][$k][] = $doc;

    $names = array();
    foreach ($lines as $l) {
        $names[] = $l['name'] . ' +' . number_format($l['qty']) . ' ' . $l['unit'];
    }
    $detail = array(
        'เอกสารอ้างอิง' => $ref,
        'จำนวน'        => $doc['items'] . ' รายการ · ' . number_format($qty) . ' ชิ้น',
        'รายการ'       => implode(' · ', $names),
        'ผู้รับเข้า'     => $user['name'] . ' · ' . branch_name($code),
    );
    if ($note !== '') {
        $detail['หมายเหตุ'] = $note;
    }

    log_add($code, 'receive', $user, 'รับสินค้าเข้า ' . $doc['no'], $detail, $qty, $doc['no']);

    return $doc;
}

/**
 * ยกเลิกใบรับเข้า — ถอนยอดที่เคยบวกไว้ออกจากสต๊อก
 * ----------------------------------------------------------
 * ถ้าของถูกขายออกไปบางส่วนแล้ว การถอนจะทำให้สต๊อกติดลบ
 * กรณีนั้นถอนไม่ได้ ต้องไปใช้การตรวจนับ/ปรับยอดแทน
 * คืนค่า: array ใบที่ยกเลิก | 'sold' ถ้าของออกไปแล้ว | null ถ้าไม่พบ
 */
function receive_void($code, $no, $user, $reason, $reopen = false)
{
    $k = recv_key($code);
    if (!isset($_SESSION['recv'][$k])) {
        return null;
    }

    foreach ($_SESSION['recv'][$k] as $i => $d) {
        if ($d['no'] !== $no || !empty($d['void'])) {
            continue;
        }

        /* ตรวจก่อนว่าถอนแล้วสต๊อกจะไม่ติดลบ */
        $short = array();
        foreach ($d['lines'] as $l) {
            $p = product_by_sku($l['sku']);
            if ($p === null) {
                continue;
            }
            $have = product_qty($p, $code);
            if ($have < (int) $l['qty']) {
                $short[] = $l['name'] . ' (เหลือ ' . number_format($have)
                         . ' แต่ต้องถอน ' . number_format($l['qty']) . ')';
            }
        }
        if ($short) {
            return array('error' => 'sold', 'items' => $short);
        }

        foreach ($d['lines'] as $l) {
            stock_adj_add($code, $l['sku'], -(int) $l['qty']);      // ถอนยอดที่รับเข้าไว้
        }

        $_SESSION['recv'][$k][$i]['void']        = true;
        $_SESSION['recv'][$k][$i]['void_at']     = date('H:i');
        $_SESSION['recv'][$k][$i]['void_by']     = $user['name'];
        $_SESSION['recv'][$k][$i]['void_user']   = $user['username'];
        $_SESSION['recv'][$k][$i]['void_reason'] = trim($reason);
        $_SESSION['recv'][$k][$i]['void_mode']   = $reopen ? 'edit' : 'void';

        $v      = $_SESSION['recv'][$k][$i];
        $detail = array(
            'ใบเดิม'      => $v['no'] . ' · รับเข้าเมื่อ ' . $v['time'] . ' น. โดย ' . $v['by'],
            'เอกสารอ้างอิง' => $v['ref'],
            'ยอดที่ถอนออก'  => $v['items'] . ' รายการ · ' . number_format($v['qty']) . ' ชิ้น',
            'เหตุผล'      => $v['void_reason'] !== '' ? $v['void_reason'] : 'ไม่ได้ระบุ',
        );
        if ($reopen) {
            $detail['การทำต่อ'] = 'ดึงรายการกลับเข้าใบเพื่อรับเข้าใหม่ทดแทน';
        }

        log_add($code, 'rvoid', $user,
                ($reopen ? 'ยกเลิกเพื่อแก้ไข ใบรับเข้า ' : 'ยกเลิกใบรับเข้า ') . $v['no'],
                $detail, $v['qty'], $v['no']);

        return $v;
    }
    return null;
}

/** ดึงรายการทั้งใบกลับเข้าร่าง เพื่อแก้แล้วรับเข้าใหม่ */
function rdraft_from_receive($doc)
{
    $_SESSION['recv_draft'] = array();
    foreach ($doc['lines'] as $l) {
        $_SESSION['recv_draft'][$l['sku']] = (int) $l['qty'];
    }
    return rdraft_count();
}

function receive_by_no($code, $no)
{
    foreach (receives_today($code) as $d) {
        if ($d['no'] === $no) {
            return $d;
        }
    }
    return null;
}

function stock_status_tabs()
{
    return array(
        ''    => 'ทั้งหมด',
        'low' => 'ใกล้หมด',
        'out' => 'หมดแล้ว',
        'ok'  => 'พอใช้',
    );
}

function stock_sorts()
{
    return array(
        'urgent' => 'ด่วนก่อน (ใกล้หมดขึ้นก่อน)',
        'name'   => 'ชื่อสินค้า ก–ฮ',
        'qty'    => 'คงเหลือมากไปน้อย',
        'qtyasc' => 'คงเหลือน้อยไปมาก',
        'cat'    => 'หมวดสินค้า',
    );
}

function stock_label($st)
{
    if ($st === 'out') { return 'หมดแล้ว'; }
    if ($st === 'low') { return 'ใกล้หมด'; }
    return 'พอใช้';
}

function stock_tone($st)
{
    if ($st === 'out') { return 'out'; }
    if ($st === 'low') { return 'adj'; }
    return 'in';
}

/** ประกอบ query string ของตัวกรอง โดยข้ามค่าที่ว่าง */
function stock_qs($q, $cat, $st, $sort)
{
    $parts = array();
    if ($q !== '')              { $parts[] = 'q='    . rawurlencode($q); }
    if ($cat !== '')            { $parts[] = 'cat='  . rawurlencode($cat); }
    if ($st !== '')             { $parts[] = 'st='   . rawurlencode($st); }
    if ($sort !== 'urgent' && $sort !== '') { $parts[] = 'sort=' . rawurlencode($sort); }
    return $parts ? '?' . implode('&', $parts) : '';
}

/**
 * รายการสินค้าของสาขาหนึ่ง พร้อมสถานะและระดับสต๊อก
 * pct = ยอดคงเหลือเทียบกับจุดสั่งซื้อ (เกิน 100 คือปลอดภัย)
 */
function stock_rows($code, $q, $cat, $st, $sort)
{
    $q    = trim($q);
    $rows = array();

    foreach (demo_products() as $p) {
        if ($cat !== '' && $p['cat'] !== $cat) {
            continue;
        }
        if ($q !== '' && stripos($p['name'], $q) === false && stripos($p['sku'], $q) === false) {
            continue;
        }
        $qty    = product_qty($p, $code);
        $status = branch_status($p, $code);
        if ($st !== '' && $status !== $st) {
            continue;
        }
        $rows[] = array(
            'p'      => $p,
            'qty'    => $qty,
            'status' => $status,
            'pct'    => $p['reorder'] > 0 ? (int) round($qty / $p['reorder'] * 100) : 100,
        );
    }

    usort($rows, stock_sorter($sort));
    return $rows;
}

function stock_sorter($sort)
{
    if ($sort === 'name')   { return 'stock_cmp_name'; }
    if ($sort === 'qty')    { return 'stock_cmp_qty_desc'; }
    if ($sort === 'qtyasc') { return 'stock_cmp_qty_asc'; }
    if ($sort === 'cat')    { return 'stock_cmp_cat'; }
    return 'stock_cmp_urgent';
}

function stock_cmp_name($a, $b)
{
    return strcmp($a['p']['name'], $b['p']['name']);
}

function stock_cmp_qty_desc($a, $b)
{
    if ($a['qty'] === $b['qty']) { return stock_cmp_name($a, $b); }
    return ($a['qty'] < $b['qty']) ? 1 : -1;
}

function stock_cmp_qty_asc($a, $b)
{
    if ($a['qty'] === $b['qty']) { return stock_cmp_name($a, $b); }
    return ($a['qty'] > $b['qty']) ? 1 : -1;
}

function stock_cmp_cat($a, $b)
{
    $c = strcmp($a['p']['cat'], $b['p']['cat']);
    return ($c !== 0) ? $c : stock_cmp_name($a, $b);
}

/** ของที่ใกล้หมดที่สุดขึ้นก่อน แล้วค่อยเรียงตามชื่อ */
function stock_cmp_urgent($a, $b)
{
    if ($a['pct'] === $b['pct']) { return stock_cmp_name($a, $b); }
    return ($a['pct'] > $b['pct']) ? 1 : -1;
}

/** สรุปเฉพาะรายการที่แสดงอยู่ตอนนี้ — การ์ดด้านบนใช้ชุดนี้ ตัวเลขจะได้ตรงกับตาราง */
function stock_view_sum($rows)
{
    $sum = array('skus' => 0, 'qty' => 0, 'low' => 0, 'out' => 0);
    foreach ($rows as $r) {
        $sum['skus']++;
        $sum['qty'] += $r['qty'];
        if ($r['status'] === 'low') { $sum['low']++; }
        if ($r['status'] === 'out') { $sum['out']++; }
    }
    return $sum;
}

/** มีตัวกรองอะไรเปิดอยู่บ้าง — ไว้บอกผู้ใช้ว่าตัวเลขที่เห็นมาจากอะไร */
function stock_filter_words($q, $cat, $st)
{
    $w = array();
    if ($cat !== '') { $w[] = 'หมวด ' . $cat; }
    if ($st !== '')  {
        $tabs = stock_status_tabs();
        $w[]  = isset($tabs[$st]) ? $tabs[$st] : $st;
    }
    if ($q !== '')   { $w[] = 'คำค้น “' . $q . '”'; }
    return $w;
}

/** นับจำนวนของแต่ละแท็บสถานะ โดยใช้คำค้นและหมวดเดิม แต่ไม่สนสถานะ */
function stock_tab_counts($code, $q, $cat)
{
    $all = stock_rows($code, $q, $cat, '', 'name');
    $out = array('' => count($all), 'low' => 0, 'out' => 0, 'ok' => 0);
    foreach ($all as $r) {
        $out[$r['status']]++;
    }
    return $out;
}

/** สรุปทั้งสาขา (ไม่สนตัวกรอง) ไว้โชว์เป็นการ์ดด้านบน */
function stock_branch_sum($code)
{
    $sum = array('skus' => 0, 'qty' => 0, 'low' => 0, 'out' => 0, 'value' => 0);
    foreach (demo_products() as $p) {
        $qty = product_qty($p, $code);
        $sum['skus']++;
        $sum['qty']   += $qty;
        $sum['value'] += $qty * product_price($p);
        $status = branch_status($p, $code);
        if ($status === 'low') { $sum['low']++; }
        if ($status === 'out') { $sum['out']++; }
    }
    return $sum;
}

/* ##########################################################
   หมวด: เบิก / ตัดออกจากสต๊อก
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — เบิก / ตัดออกจากสต๊อก
   ----------------------------------------------------------
   ใช้กับของที่ออกจากสต๊อกโดย "ไม่ได้ขาย" เช่น เบิกใช้ภายใน ชำรุด หมดอายุ สูญหาย
   ทำเป็นใบแบบเดียวกับใบรับเข้า: เลือกสินค้า → ปรับจำนวน → เลือกเหตุผล → บันทึกทั้งใบ

   ยังไม่มีฐานข้อมูล:
     ใบที่กำลังทำ   $_SESSION['issue_draft'][ SKU ] = จำนวน
     หัวใบที่ค้างไว้  $_SESSION['issue_meta'] = reason / ref / note (ใช้ตอนดึงใบเก่ามาแก้)
     ใบที่บันทึกแล้ว $_SESSION['issue'][ 'BN|20260924' ][] = เอกสารหนึ่งใบ
   ระบบจริง: ตาราง issue_doc + issue_item + stock_move

   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

/* ---------- เหตุผลการตัดออก ----------
   note = ต้องกรอกหมายเหตุประกอบเสมอ (กรณีที่ต้องตรวจสอบย้อนหลังได้) */
function issue_reasons()
{
    return array(
        'use'     => array('label' => 'เบิกใช้ภายใน',   'hint' => 'ใช้ในร้าน / คลัง / สำนักงาน', 'note' => false),
        'damaged' => array('label' => 'ชำรุด / เสียหาย', 'hint' => 'แตก ขาด เปียก ใช้งานไม่ได้',   'note' => false),
        'expired' => array('label' => 'หมดอายุ',        'hint' => 'เลยวันหมดอายุ ขายไม่ได้',       'note' => false),
        'return'  => array('label' => 'คืนผู้จำหน่าย',    'hint' => 'ส่งคืนซัพพลายเออร์',            'note' => false),
        'branch'  => array('label' => 'ส่งไปสาขาอื่น',  'hint' => 'ระบุสาขาปลายทางในหมายเหตุ',     'note' => true),
        'lost'    => array('label' => 'สูญหาย',         'hint' => 'หาไม่พบ ต้องระบุรายละเอียด',   'note' => true),
        'other'   => array('label' => 'อื่น ๆ',          'hint' => 'ต้องระบุรายละเอียด',             'note' => true),
    );
}

function issue_reason_label($key)
{
    $r = issue_reasons();
    return isset($r[$key]) ? $r[$key]['label'] : $key;
}

function issue_reason_needs_note($key)
{
    $r = issue_reasons();
    return isset($r[$key]) && $r[$key]['note'];
}

/* ==========================================================
   ใบที่กำลังทำ (ร่าง)
   ต่างจากใบรับเข้าตรงที่ "ตัดเกินของที่มีไม่ได้"
   ทุกครั้งที่เพิ่ม/แก้จำนวน จะถูกจำกัดไว้ไม่เกินยอดคงเหลือของสาขา
   ========================================================== */

function idraft_all()
{
    return isset($_SESSION['issue_draft']) && is_array($_SESSION['issue_draft'])
         ? $_SESSION['issue_draft'] : array();
}

function idraft_count()
{
    $n = 0;
    foreach (idraft_all() as $q) {
        $n += (int) $q;
    }
    return $n;
}

/** จำข้อความเตือนไว้แสดงครั้งถัดไป (เช่น ปรับจำนวนลงให้เพราะของไม่พอ) */
function idraft_flash($msg = null)
{
    if ($msg !== null) {
        $_SESSION['issue_flash'] = $msg;
        return $msg;
    }
    $m = isset($_SESSION['issue_flash']) ? $_SESSION['issue_flash'] : '';
    unset($_SESSION['issue_flash']);
    return $m;
}

/** ตั้งจำนวน โดยไม่ให้เกินยอดคงเหลือ — คืนค่าจำนวนที่ตั้งได้จริง */
function idraft_set($code, $sku, $qty)
{
    $p = product_by_sku($sku);
    if ($p === null) {
        return 0;
    }
    $qty  = (int) $qty;
    $have = product_qty($p, $code);

    if ($qty > $have) {
        idraft_flash($have > 0
            ? $p['name'] . ' มีอยู่ ' . number_format($have) . ' ' . $p['unit'] . ' — ปรับจำนวนให้เท่าที่มี'
            : $p['name'] . ' หมดสต๊อกแล้ว ตัดออกไม่ได้');
        $qty = $have;
    }
    if ($qty <= 0) {
        unset($_SESSION['issue_draft'][$sku]);
        return 0;
    }
    $_SESSION['issue_draft'][$sku] = $qty;
    return $qty;
}

function idraft_add($code, $sku, $step = 1)
{
    $cur = isset($_SESSION['issue_draft'][$sku]) ? (int) $_SESSION['issue_draft'][$sku] : 0;
    return idraft_set($code, $sku, $cur + (int) $step);
}

function idraft_remove($sku)
{
    unset($_SESSION['issue_draft'][$sku]);
}

function idraft_clear()
{
    $_SESSION['issue_draft'] = array();
    unset($_SESSION['issue_meta']);
}

/** หัวใบที่จำไว้ (ตอนดึงใบเก่ามาแก้ จะได้ไม่ต้องเลือกเหตุผลใหม่) */
function idraft_meta()
{
    $m = isset($_SESSION['issue_meta']) && is_array($_SESSION['issue_meta']) ? $_SESSION['issue_meta'] : array();
    return array(
        'reason' => isset($m['reason']) ? $m['reason'] : '',
        'ref'    => isset($m['ref'])    ? $m['ref']    : '',
        'note'   => isset($m['note'])   ? $m['note']   : '',
    );
}

/** แปลงร่างเป็นรายการ พร้อมยอดก่อน/หลัง และมูลค่าต้นทุนที่ตัดออก */
function idraft_lines($code)
{
    $out = array();
    foreach (idraft_all() as $sku => $qty) {
        $p = product_by_sku($sku);
        if ($p === null) {
            continue;
        }
        $have  = product_qty($p, $code);
        $out[] = array(
            'sku'   => $sku,
            'name'  => $p['name'],
            'cat'   => $p['cat'],
            'unit'  => $p['unit'],
            'qty'   => (int) $qty,
            'have'  => $have,
            'after' => $have - (int) $qty,
            'cost'  => (int) $qty * (float) $p['cost'],
            'p'     => $p,
        );
    }
    return $out;
}

function idraft_cost($lines)
{
    $sum = 0;
    foreach ($lines as $l) {
        $sum += $l['cost'];
    }
    return $sum;
}

/* ==========================================================
   ใบที่บันทึกแล้ว
   ========================================================== */

function issue_key($code)
{
    return $code . '|' . date('Ymd');
}

function issues_today($code)
{
    $k = issue_key($code);
    return isset($_SESSION['issue'][$k]) ? $_SESSION['issue'][$k] : array();
}

function issue_next_no($code)
{
    return 'IS-' . date('ymd') . '-' . str_pad(count(issues_today($code)) + 1, 4, '0', STR_PAD_LEFT);
}

function issue_by_no($code, $no)
{
    foreach (issues_today($code) as $d) {
        if ($d['no'] === $no) {
            return $d;
        }
    }
    return null;
}

/**
 * ตรวจก่อนบันทึกว่าของยังพอ (ระหว่างทำใบ อาจมีคนขายตัวเดียวกันออกไปแล้ว)
 * คืนค่า array ของรายการที่ไม่พอ — ว่าง = ผ่าน
 */
function issue_shortage($lines)
{
    $short = array();
    foreach ($lines as $l) {
        if ($l['qty'] > $l['have']) {
            $short[] = $l['name'] . ' (เหลือ ' . number_format($l['have'])
                     . ' แต่จะตัด ' . number_format($l['qty']) . ')';
        }
    }
    return $short;
}

/** บันทึกการตัดออก: ลบสต๊อกแล้วลงประวัติ */
function issue_save($code, $user, $reason, $ref, $note, $lines)
{
    $qty = 0;
    foreach ($lines as $l) {
        $qty += (int) $l['qty'];
        stock_adj_add($code, $l['sku'], -(int) $l['qty']);      // ตัดออก = สต๊อกลด
    }

    $doc = array(
        'no'      => issue_next_no($code),
        'time'    => date('H:i'),
        'branch'  => $code,
        'by'      => $user['name'],
        'by_user' => $user['username'],
        'reason'  => $reason,
        'ref'     => $ref,
        'note'    => $note,
        'lines'   => $lines,
        'items'   => count($lines),
        'qty'     => $qty,
        'cost'    => idraft_cost($lines),
    );

    $k = issue_key($code);
    if (!isset($_SESSION['issue'][$k])) {
        $_SESSION['issue'][$k] = array();
    }
    $_SESSION['issue'][$k][] = $doc;

    $names = array();
    foreach ($lines as $l) {
        $names[] = $l['name'] . ' −' . number_format($l['qty']) . ' ' . $l['unit'];
    }
    $detail = array(
        'เหตุผล'       => issue_reason_label($reason),
        'จำนวน'       => $doc['items'] . ' รายการ · ' . number_format($qty) . ' ชิ้น',
        'มูลค่าต้นทุน'  => money2($doc['cost']) . ' บาท',
        'รายการ'      => implode(' · ', $names),
        'ผู้ทำรายการ'   => $user['name'] . ' · ' . branch_name($code),
    );
    if ($ref !== '') {
        $detail['ผู้ขอเบิก / อ้างอิง'] = $ref;
    }
    if ($note !== '') {
        $detail['หมายเหตุ'] = $note;
    }

    log_add($code, 'issue', $user,
            issue_reason_label($reason) . ' ' . $doc['no'], $detail, null, $doc['no']);

    return $doc;
}

/**
 * ยกเลิกใบตัดออก — คืนยอดกลับเข้าสต๊อก (ทำได้เสมอ เพราะเป็นการบวกกลับ)
 * $reopen = true คือยกเลิกเพื่อดึงรายการกลับมาแก้
 */
function issue_void($code, $no, $user, $reason, $reopen = false)
{
    $k = issue_key($code);
    if (!isset($_SESSION['issue'][$k])) {
        return null;
    }

    foreach ($_SESSION['issue'][$k] as $i => $d) {
        if ($d['no'] !== $no || !empty($d['void'])) {
            continue;
        }

        foreach ($d['lines'] as $l) {
            stock_adj_add($code, $l['sku'], (int) $l['qty']);       // คืนยอดที่ตัดไว้
        }

        $_SESSION['issue'][$k][$i]['void']        = true;
        $_SESSION['issue'][$k][$i]['void_at']     = date('H:i');
        $_SESSION['issue'][$k][$i]['void_by']     = $user['name'];
        $_SESSION['issue'][$k][$i]['void_user']   = $user['username'];
        $_SESSION['issue'][$k][$i]['void_reason'] = trim($reason);
        $_SESSION['issue'][$k][$i]['void_mode']   = $reopen ? 'edit' : 'void';

        $v      = $_SESSION['issue'][$k][$i];
        $detail = array(
            'ใบเดิม'          => $v['no'] . ' · ' . issue_reason_label($v['reason'])
                               . ' เมื่อ ' . $v['time'] . ' น. โดย ' . $v['by'],
            'ยอดที่คืนเข้าสต๊อก' => $v['items'] . ' รายการ · ' . number_format($v['qty']) . ' ชิ้น',
            'เหตุผล'          => $v['void_reason'] !== '' ? $v['void_reason'] : 'ไม่ได้ระบุ',
        );
        if ($reopen) {
            $detail['การทำต่อ'] = 'ดึงรายการกลับเข้าใบเพื่อบันทึกใหม่ทดแทน';
        }

        log_add($code, 'ivoid', $user,
                ($reopen ? 'ยกเลิกเพื่อแก้ไข ใบตัดออก ' : 'ยกเลิกใบตัดออก ') . $v['no'],
                $detail, null, $v['no']);

        return $v;
    }
    return null;
}

/** ดึงรายการทั้งใบกลับเข้าร่าง พร้อมหัวใบเดิม */
function idraft_from_issue($doc)
{
    $_SESSION['issue_draft'] = array();
    foreach ($doc['lines'] as $l) {
        $_SESSION['issue_draft'][$l['sku']] = (int) $l['qty'];
    }
    $_SESSION['issue_meta'] = array(
        'reason' => $doc['reason'],
        'ref'    => $doc['ref'],
        'note'   => $doc['note'],
    );
    return idraft_count();
}

/* ==========================================================
   รายการเบิก / ตัดออกแบบรายสินค้า — ใช้กับหน้าตรวจสอบของผู้ดูแล (inc/issue-all.php)
   วันนี้อ่านจาก session · วันก่อนเป็นข้อมูลสมมติที่คงที่ (past_docs ชนิด IS)
   ========================================================== */

/** หนึ่งแถวต่อสินค้าหนึ่งรายการในใบ: ราคาทุน / ราคาขาย / มูลค่าทุน คิดจากข้อมูลสินค้าปัจจุบัน */
function issue_rows_of_day($code, $ts)
{
    $day  = date('Ymd', $ts);
    $docs = array();
    if ($day === date('Ymd')) {
        foreach (issues_today($code) as $d) {
            $d['date'] = $day;
            $docs[] = $d;
        }
    } elseif ($day < date('Ymd')) {
        foreach (past_docs($code, $ts) as $d) {
            if ($d['kind'] === 'IS') {
                $docs[] = $d;
            }
        }
    }
    $rows = array();
    foreach ($docs as $d) {
        foreach ($d['lines'] as $n => $l) {
            $p    = product_by_sku($l['sku']);
            $cost = $p ? (float) $p['cost'] : 0;
            $rows[] = array(
                'date'   => $day,
                'time'   => $d['time'],
                'ts'     => strtotime(date('Y-m-d', $ts) . ' ' . $d['time']),
                'seq'    => $n,
                'branch' => $code,
                'no'     => $d['no'],
                'sku'    => $l['sku'],
                'name'   => $l['name'],
                'unit'   => $l['unit'],
                'cat'    => $p ? $p['cat'] : '',
                'qty'    => (int) $l['qty'],
                'cost'   => $cost,
                'price'  => $p ? (float) $p['price'] : 0,
                'value'  => $cost * (int) $l['qty'],
                'reason' => $d['reason'],
                'note'   => isset($d['note']) ? $d['note'] : '',
                'ref'    => isset($d['ref']) ? $d['ref'] : '',
                'by'     => $d['by'],
                'void'   => !empty($d['void']),
                'void_by'     => isset($d['void_by']) ? $d['void_by'] : '',
                'void_reason' => isset($d['void_reason']) ? $d['void_reason'] : '',
            );
        }
    }
    return $rows;
}

/** ใบรับเข้าของสาขาในวันหนึ่ง — วันนี้จาก session · วันก่อนเป็นข้อมูลสมมติ (past_docs ชนิด RC)
    เติม date / value (มูลค่าทุนตามราคาปัจจุบัน) / void ให้ทุกใบ — ใช้กับหน้า adm-receive.php */
function receive_docs_of_day($code, $ts)
{
    $day  = date('Ymd', $ts);
    $docs = array();
    if ($day === date('Ymd')) {
        $docs = receives_today($code);
    } elseif ($day < date('Ymd')) {
        foreach (past_docs($code, $ts) as $d) {
            if ($d['kind'] === 'RC') {
                $docs[] = $d;
            }
        }
    }
    $out = array();
    foreach ($docs as $d) {
        $value = 0;
        foreach ($d['lines'] as $i => $l) {
            $p    = product_by_sku($l['sku']);
            $cost = $p ? (float) $p['cost'] : 0;
            $d['lines'][$i]['cost']  = $cost;
            $d['lines'][$i]['value'] = $cost * (int) $l['qty'];
            $d['lines'][$i]['cat']   = $p ? $p['cat'] : '';
            $value += $cost * (int) $l['qty'];
        }
        $d['date']   = $day;
        $d['branch'] = $code;
        $d['value']  = $value;
        $d['ts']     = strtotime(date('Y-m-d', $ts) . ' ' . $d['time']);
        $d['void']   = !empty($d['void']);
        foreach (array('ref', 'note', 'void_by', 'void_at', 'void_reason', 'void_mode') as $k) {
            if (!isset($d[$k])) {
                $d[$k] = '';
            }
        }
        $out[] = $d;
    }
    return $out;
}

/** หาใบรับเข้าจากเลขที่ RC-ปปดดวว-NNNN (วันอยู่ในเลขที่) */
function receive_doc_find($code, $no)
{
    if (!preg_match('/^RC-(\d{6})-\d{4}$/', $no, $m) || ($ts = strtotime('20' . $m[1])) === false) {
        return null;
    }
    foreach (receive_docs_of_day($code, $ts) as $d) {
        if ($d['no'] === $no) {
            return $d;
        }
    }
    return null;
}

/** ช่วงวันที่ของหน้าตรวจสอบฝั่งผู้ดูแล — คืน array(จาก, ถึง, ข้อความ)
    $mode = recent (30 วันล่าสุด) | day | month | year */
function adm_range($mode, $dayTs, $monTs, $year)
{
    $today = strtotime(date('Y-m-d'));
    if ($mode === 'day') {
        return array($dayTs, $dayTs, thai_date_full($dayTs));
    }
    if ($mode === 'month') {
        return array($monTs, min(strtotime(date('Y-m-t', $monTs)), $today), thai_month_full($monTs));
    }
    if ($mode === 'year') {
        return array(strtotime($year . '-01-01'), min(strtotime($year . '-12-31'), $today), 'ปี ' . ($year + 543));
    }
    $from = strtotime('-29 day', $today);
    return array($from, $today, '30 วันล่าสุด (' . thai_day_month($from) . ' – ' . thai_day_month($today) . ')');
}

/**
 * ตัวเลขประกอบหน้าสินค้าในสต๊อกของผู้ดูแล (adm-products.php) ย้อนหลัง $days วัน รวมวันนี้
 * คืน array(
 *   'sold' => array( SKU => array( สาขา => จำนวนที่ขาย ) )      ไม่นับบิลที่ยกเลิก
 *   'recv' => array( SKU => array( สาขา => วันที่รับเข้าล่าสุด Ymd ) ) ไม่นับใบที่ยกเลิก
 * )
 */
function product_flow_stats($codes, $days = 30)
{
    $sold  = array();
    $recv  = array();
    $today = strtotime(date('Y-m-d'));
    for ($i = $days - 1; $i >= 0; $i--) {
        $ts = strtotime('-' . $i . ' day', $today);
        foreach ($codes as $c) {
            foreach (acct_bills($c, $ts) as $b) {
                if (!empty($b['void'])) {
                    continue;
                }
                foreach ($b['lines'] as $l) {
                    $sold[$l['sku']][$c] = (isset($sold[$l['sku']][$c]) ? $sold[$l['sku']][$c] : 0) + (int) $l['qty'];
                }
            }
            foreach (receive_docs_of_day($c, $ts) as $d) {
                if ($d['void']) {
                    continue;
                }
                foreach ($d['lines'] as $l) {
                    $recv[$l['sku']][$c] = $d['date'];       // เดินจากเก่าไปใหม่ → ค่าสุดท้ายคือล่าสุด
                }
            }
        }
    }
    return array('sold' => $sold, 'recv' => $recv);
}

/* ##########################################################
   หมวด: ตรวจนับ / ปรับยอด (แบบเบา)
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — ตรวจนับ / ปรับยอด (แบบเบา)
   ----------------------------------------------------------
   เห็นของจริงไม่ตรงกับระบบ → เลือกสินค้า → กรอก "จำนวนที่นับได้จริง"
   ระบบคำนวณส่วนต่างให้เอง แล้วปรับสต๊อกให้ตรงกับของจริง

   ต่างจากรับเข้า/ตัดออกตรงที่ กรอก "ยอดจริง" ไม่ใช่ "จำนวนที่เพิ่ม/ลด"
   ส่วนต่างคำนวณตอนกดบันทึก เทียบกับยอดในระบบ ณ ตอนนั้น
   → ถ้ามีการขายระหว่างที่นับ ผลก็ยังถูก เพราะเทียบกับยอดล่าสุดเสมอ

   ยังไม่มีฐานข้อมูล:
     ใบที่กำลังทำ   $_SESSION['adj_draft'][ SKU ] = จำนวนที่นับได้
     หัวใบที่ค้างไว้  $_SESSION['adj_meta']
     ใบที่บันทึกแล้ว $_SESSION['adj'][ 'BN|20260924' ][] = เอกสารหนึ่งใบ
   ระบบจริง: ตาราง count_doc + count_item + stock_move

   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

/* ---------- สาเหตุที่ยอดไม่ตรง ---------- */
function adj_reasons()
{
    return array(
        'miscount' => array('label' => 'นับผิดครั้งก่อน',       'hint' => 'ยอดเดิมในระบบคลาดมาตั้งแต่ต้น', 'note' => false),
        'unlogged' => array('label' => 'ใช้/เสียไม่ได้บันทึก',  'hint' => 'หยิบใช้ ชำรุด ไม่ได้ตัดออก',   'note' => false),
        'wrongkey' => array('label' => 'บันทึกผิด',           'hint' => 'รับเข้า / ขาย กรอกจำนวนผิด',  'note' => false),
        'found'    => array('label' => 'เจอของเพิ่ม',          'hint' => 'ของตกหล่น วางผิดที่',          'note' => false),
        'lost'     => array('label' => 'ของหาย',             'hint' => 'หาไม่พบ ต้องระบุรายละเอียด',  'note' => true),
        'other'    => array('label' => 'อื่น ๆ',              'hint' => 'ต้องระบุรายละเอียด',            'note' => true),
    );
}

function adj_reason_label($key)
{
    $r = adj_reasons();
    return isset($r[$key]) ? $r[$key]['label'] : $key;
}

function adj_reason_needs_note($key)
{
    $r = adj_reasons();
    return isset($r[$key]) && $r[$key]['note'];
}

/* ==========================================================
   ใบที่กำลังทำ (ร่าง) — เก็บ "จำนวนที่นับได้จริง"
   ========================================================== */

function adraft_all()
{
    return isset($_SESSION['adj_draft']) && is_array($_SESSION['adj_draft'])
         ? $_SESSION['adj_draft'] : array();
}

function adraft_size()
{
    return count(adraft_all());
}

/** แตะสินค้าเข้าใบ: เริ่มต้นที่ยอดในระบบ (ส่วนต่าง 0) แล้วค่อยแก้เป็นยอดที่นับได้ */
/** ใบนี้ใส่ได้อีกกี่รายการ (null = ไม่จำกัด) — จำกัดเฉพาะตอนร้านเปิด */
function adj_limit($code)
{
    $max = count_open_limit($code);
    return ($max > 0 && store_is_open($code)) ? $max : null;
}

function adj_full($code)
{
    $max = adj_limit($code);
    return $max !== null && adraft_size() >= $max;
}

/**
 * แตะสินค้าเข้าใบ: เริ่มที่ยอดในระบบ (ส่วนต่าง 0) แล้วค่อยแก้เป็นยอดที่นับได้
 * จดยอดในระบบ ณ ตอนนี้ไว้ด้วย (snap) — ตอนบันทึกถ้ายอดเปลี่ยนไป แปลว่ามีการขายแทรก ต้องนับใหม่
 */
function adraft_pick($code, $sku)
{
    $p = product_by_sku($sku);
    if ($p === null || isset($_SESSION['adj_draft'][$sku]) || adj_full($code)) {
        return false;
    }
    $have = max(0, product_qty($p, $code));
    $_SESSION['adj_draft'][$sku] = $have;
    $_SESSION['adj_snap'][$sku]  = $have;
    return true;
}

/**
 * รายการที่ยอดในระบบเปลี่ยนไประหว่างนับ (มีการขาย/รับเข้า/ตัดออกแทรก)
 * คืนค่าชื่อสินค้า และอัปเดต snap เป็นยอดล่าสุด ให้พนักงานนับใหม่แล้วกดบันทึกอีกครั้ง
 */
function adj_moved($code)
{
    $out = array();
    foreach (adraft_all() as $sku => $cnt) {
        $p = product_by_sku($sku);
        if ($p === null || !isset($_SESSION['adj_snap'][$sku])) {
            continue;
        }
        $now = product_qty($p, $code);
        if ($now !== (int) $_SESSION['adj_snap'][$sku]) {
            $out[] = $p['name'] . ' (ตอนเริ่มนับ ' . number_format($_SESSION['adj_snap'][$sku])
                   . ' ตอนนี้ ' . number_format($now) . ')';
            $_SESSION['adj_snap'][$sku] = $now;
        }
    }
    return $out;
}

function adraft_set($sku, $qty)
{
    if (product_by_sku($sku) === null) {
        return false;
    }
    $_SESSION['adj_draft'][$sku] = max(0, (int) $qty);    // นับได้ 0 ก็คือของหมดจริง ยังอยู่ในใบ
    return true;
}

function adraft_step($sku, $step)
{
    $cur = isset($_SESSION['adj_draft'][$sku]) ? (int) $_SESSION['adj_draft'][$sku] : 0;
    return adraft_set($sku, $cur + (int) $step);
}

function adraft_remove($sku)
{
    unset($_SESSION['adj_draft'][$sku], $_SESSION['adj_snap'][$sku]);
}

function adraft_clear()
{
    $_SESSION['adj_draft'] = array();
    $_SESSION['adj_snap']  = array();
    unset($_SESSION['adj_meta']);
}

function adraft_meta()
{
    $m = isset($_SESSION['adj_meta']) && is_array($_SESSION['adj_meta']) ? $_SESSION['adj_meta'] : array();
    return array(
        'reason' => isset($m['reason']) ? $m['reason'] : '',
        'note'   => isset($m['note'])   ? $m['note']   : '',
    );
}

/** แปลงร่างเป็นรายการ: ยอดในระบบตอนนี้ · นับได้ · ส่วนต่าง · มูลค่าส่วนต่าง */
function adraft_lines($code)
{
    $out = array();
    foreach (adraft_all() as $sku => $counted) {
        $p = product_by_sku($sku);
        if ($p === null) {
            continue;
        }
        $have  = product_qty($p, $code);
        $diff  = (int) $counted - $have;
        $out[] = array(
            'sku'     => $sku,
            'name'    => $p['name'],
            'cat'     => $p['cat'],
            'unit'    => $p['unit'],
            'have'    => $have,
            'counted' => (int) $counted,
            'diff'    => $diff,
            'value'   => $diff * (float) $p['cost'],
            'p'       => $p,
        );
    }
    return $out;
}

/** สรุปส่วนต่างของทั้งใบ */
function adj_sum($lines)
{
    $s = array('items' => count($lines), 'same' => 0, 'over' => 0, 'short' => 0,
               'plus' => 0, 'minus' => 0, 'value' => 0);
    foreach ($lines as $l) {
        if ($l['diff'] > 0)      { $s['over']++;  $s['plus']  += $l['diff']; }
        elseif ($l['diff'] < 0)  { $s['short']++; $s['minus'] += -$l['diff']; }
        else                     { $s['same']++; }
        $s['value'] += $l['value'];
    }
    return $s;
}

/* ==========================================================
   ใบที่บันทึกแล้ว
   ========================================================== */

function adj_key($code)
{
    return $code . '|' . date('Ymd');
}

function adjs_today($code)
{
    $k = adj_key($code);
    return isset($_SESSION['adj'][$k]) ? $_SESSION['adj'][$k] : array();
}

function adj_next_no($code)
{
    return 'AD-' . date('ymd') . '-' . str_pad(count(adjs_today($code)) + 1, 4, '0', STR_PAD_LEFT);
}

function adj_by_no($code, $no)
{
    foreach (adjs_today($code) as $d) {
        if ($d['no'] === $no) {
            return $d;
        }
    }
    return null;
}

/**
 * บันทึกผลการนับ: ปรับสต๊อกเท่ากับส่วนต่าง แล้วลงประวัติ
 * $reason ว่างได้ถ้าทุกรายการตรงกันหมด (เป็นการยืนยันว่านับแล้วตรง)
 */
function adj_save($code, $user, $reason, $note, $lines)
{
    foreach ($lines as $l) {
        if ($l['diff'] !== 0) {
            stock_adj_add($code, $l['sku'], $l['diff']);
        }
    }
    $sum = adj_sum($lines);

    $doc = array(
        'no'      => adj_next_no($code),
        'time'    => date('H:i'),
        'branch'  => $code,
        'by'      => $user['name'],
        'by_user' => $user['username'],
        'reason'  => $reason,
        'note'    => $note,
        'lines'   => $lines,
        'items'   => $sum['items'],
        'sum'     => $sum,
    );

    $k = adj_key($code);
    if (!isset($_SESSION['adj'][$k])) {
        $_SESSION['adj'][$k] = array();
    }
    $_SESSION['adj'][$k][] = $doc;

    $names = array();
    foreach ($lines as $l) {
        $names[] = $l['name'] . ' ' . number_format($l['have']) . '→' . number_format($l['counted'])
                 . ($l['diff'] === 0 ? ' (ตรง)' : ' (' . ($l['diff'] > 0 ? '+' : '−') . number_format(abs($l['diff'])) . ')');
    }
    $detail = array(
        'นับ'           => $sum['items'] . ' รายการ · ตรง ' . $sum['same'] . ' · เกิน ' . $sum['over']
                          . ' · ขาด ' . $sum['short'],
        'ส่วนต่างสุทธิ'    => ($sum['plus'] - $sum['minus'] >= 0 ? '+' : '−')
                          . number_format(abs($sum['plus'] - $sum['minus'])) . ' ชิ้น · '
                          . ($sum['value'] < 0 ? '−' : '') . money2(abs($sum['value'])) . ' บาท',
        'รายการ'        => implode(' · ', $names),
        'ผู้ตรวจนับ'      => $user['name'] . ' · ' . branch_name($code),
    );
    if ($reason !== '') {
        $detail['สาเหตุ'] = adj_reason_label($reason);
    }
    if ($note !== '') {
        $detail['หมายเหตุ'] = $note;
    }

    $title = ($sum['over'] + $sum['short'] === 0)
           ? 'ตรวจนับตรงทุกรายการ ' . $doc['no']
           : 'ตรวจนับ / ปรับยอด ' . $doc['no'];
    log_add($code, 'adjust', $user, $title, $detail, null, $doc['no']);

    return $doc;
}

/**
 * ยกเลิกใบปรับยอด — ถอยส่วนต่างที่เคยปรับไว้กลับ
 * ถ้าใบเดิม "บวก" ของเพิ่มแล้วของนั้นถูกขายไปแล้ว การถอยจะทำให้ติดลบ → ยกเลิกไม่ได้
 * ต้องนับใหม่อีกรอบแทน
 */
function adj_void($code, $no, $user, $reason, $reopen = false)
{
    $k = adj_key($code);
    if (!isset($_SESSION['adj'][$k])) {
        return null;
    }

    foreach ($_SESSION['adj'][$k] as $i => $d) {
        if ($d['no'] !== $no || !empty($d['void'])) {
            continue;
        }

        $short = array();
        foreach ($d['lines'] as $l) {
            $p = product_by_sku($l['sku']);
            if ($p !== null && $l['diff'] > 0 && product_qty($p, $code) < $l['diff']) {
                $short[] = $l['name'];
            }
        }
        if ($short) {
            return array('error' => 'sold', 'items' => $short);
        }

        foreach ($d['lines'] as $l) {
            if ($l['diff'] !== 0) {
                stock_adj_add($code, $l['sku'], -$l['diff']);
            }
        }

        $_SESSION['adj'][$k][$i]['void']        = true;
        $_SESSION['adj'][$k][$i]['void_at']     = date('H:i');
        $_SESSION['adj'][$k][$i]['void_by']     = $user['name'];
        $_SESSION['adj'][$k][$i]['void_user']   = $user['username'];
        $_SESSION['adj'][$k][$i]['void_reason'] = trim($reason);
        $_SESSION['adj'][$k][$i]['void_mode']   = $reopen ? 'edit' : 'void';

        $v      = $_SESSION['adj'][$k][$i];
        $detail = array(
            'ใบเดิม' => $v['no'] . ' · นับเมื่อ ' . $v['time'] . ' น. โดย ' . $v['by'],
            'ผล'     => 'ถอยยอดทุกรายการกลับเป็นก่อนตรวจนับ',
            'เหตุผล' => $v['void_reason'] !== '' ? $v['void_reason'] : 'ไม่ได้ระบุ',
        );
        if ($reopen) {
            $detail['การทำต่อ'] = 'ดึงรายการกลับเข้าใบเพื่อนับ/บันทึกใหม่';
        }
        log_add($code, 'avoid', $user,
                ($reopen ? 'ยกเลิกเพื่อแก้ไข ใบตรวจนับ ' : 'ยกเลิกใบตรวจนับ ') . $v['no'],
                $detail, null, $v['no']);

        return $v;
    }
    return null;
}

function adraft_from_adj($doc)
{
    $_SESSION['adj_draft'] = array();
    foreach ($doc['lines'] as $l) {
        $_SESSION['adj_draft'][$l['sku']] = (int) $l['counted'];
    }
    $_SESSION['adj_meta'] = array('reason' => $doc['reason'], 'note' => $doc['note']);
    $_SESSION['adj_snap'] = array();
    foreach ($doc['lines'] as $l) {
        $p = product_by_sku($l['sku']);
        if ($p !== null) {
            $_SESSION['adj_snap'][$l['sku']] = product_qty($p, $doc['branch']);   // ยอดหลังถอยใบเดิมแล้ว
        }
    }
    return adraft_size();
}

/* ==========================================================
   รอบการตรวจนับ — "นับแล้ว" หมายถึงนับแล้วภายในรอบปัจจุบัน
   ========================================================== */

/** รอบปัจจุบันของสาขา: array(start, end, due) เป็น timestamp เที่ยงคืน */
function count_round($code, $now = null)
{
    $now = ($now === null) ? time() : $now;
    $day = count_round_day($code);
    $y   = (int) date('Y', $now);
    $m   = (int) date('n', $now);

    if ((int) date('j', $now) < $day) {          // ยังไม่ถึงวันเริ่มของเดือนนี้ → รอบเริ่มเดือนก่อน
        $m--;
        if ($m < 1) { $m = 12; $y--; }
    }
    $start = mktime(0, 0, 0, $m, $day, $y);
    $next  = mktime(0, 0, 0, $m + 1, $day, $y);
    return array(
        'start' => $start,
        'end'   => $next - 86400,                 // วันสุดท้ายของรอบ = กำหนดส่ง
        'left'  => (int) floor(($next - mktime(0, 0, 0)) / 86400),   // เหลือกี่วัน (รวมวันนี้)
    );
}

function thai_day_month($ts)
{
    return (int) date('j', $ts) . ' ' . thai_month_short($ts);
}

/**
 * ประวัติการนับในรอบนี้ก่อนวันนี้ (ข้อมูลสมมติ ให้เดโมมีบางรายการที่นับไปแล้ว)
 * ระบบจริง: SELECT ล่าสุดจาก count_item ของสาขานี้ ที่ created_at >= วันเริ่มรอบ
 */
function count_seed($code, $sku, $round)
{
    $today = mktime(0, 0, 0);
    $days  = (int) floor(($today - $round['start']) / 86400);    // จำนวนวันที่ผ่านไปแล้วในรอบ
    if ($days < 1) {
        return null;
    }
    $h = abs(crc32($code . '|' . $sku . '|' . date('Ymd', $round['start'])));
    if ($h % 100 >= min(60, 8 + $days * 3)) {                     // ยิ่งผ่านไปหลายวัน ยิ่งนับไปเยอะ
        return null;
    }
    $v    = ($h >> 7) % 10;
    $diff = ($v <= 6) ? 0 : ($v === 7 ? -1 : ($v === 8 ? -2 : 1));
    return array(
        'at'   => $round['start'] + (($h >> 11) % $days) * 86400 + (9 + ($h >> 3) % 9) * 3600,
        'diff' => $diff,
        'by'   => '',
        'no'   => '',
    );
}

/** สถานะการนับของทุกสินค้าในรอบนี้: array( SKU => array(at, diff, by, no) ) */
function count_status_all($code)
{
    $round = count_round($code);
    $out   = array();
    foreach (demo_products() as $p) {
        $s = count_seed($code, $p['sku'], $round);
        if ($s !== null) {
            $out[$p['sku']] = $s;
        }
    }
    /* ใบที่นับวันนี้ (ไม่รวมที่ถูกยกเลิก) ทับของเดิม — ใบหลังสุดชนะ */
    $today = mktime(0, 0, 0);
    foreach (adjs_today($code) as $d) {
        if (!empty($d['void'])) {
            continue;
        }
        $parts = explode(':', $d['time']);
        $at    = $today + (int) $parts[0] * 3600 + (int) $parts[1] * 60;
        foreach ($d['lines'] as $l) {
            $out[$l['sku']] = array('at' => $at, 'diff' => $l['diff'], 'by' => $d['by'], 'no' => $d['no']);
        }
    }
    return $out;
}

/** query string ของหน้าตรวจนับ (แท็บ "ยังไม่นับ" เป็นค่าเริ่มต้น ไม่ต้องใส่) */
function adj_qs($q, $cat, $tab)
{
    $a = array();
    if ($q !== '')                   { $a[] = 'q='   . rawurlencode($q); }
    if ($cat !== '')                 { $a[] = 'cat=' . rawurlencode($cat); }
    if ($tab !== '' && $tab !== 'todo') { $a[] = 't=' . rawurlencode($tab); }
    return $a ? '?' . implode('&', $a) : '';
}

function count_tabs()
{
    return array('todo' => 'ยังไม่นับ', 'done' => 'นับแล้ว', 'all' => 'ทั้งหมด');
}

/** ข้อความสั้นบนการ์ด: "นับ 12 ก.ย. · ตรง" / "นับวันนี้ 10:32 · ขาด 2" */
function count_note($st)
{
    $when = (date('Ymd', $st['at']) === date('Ymd'))
          ? 'วันนี้ ' . date('H:i', $st['at'])
          : thai_day_month($st['at']);
    if ($st['diff'] === 0)  { $res = 'ตรง'; }
    elseif ($st['diff'] > 0) { $res = 'เกิน ' . number_format($st['diff']); }
    else                     { $res = 'ขาด ' . number_format(-$st['diff']); }
    return array('when' => 'นับ ' . $when, 'res' => $res, 'tone' => $st['diff'] === 0 ? 'eq' : ($st['diff'] > 0 ? 'up' : 'dn'));
}

/** ป้ายส่วนต่าง: +3 / −2 / ตรง */
function diff_chip($d)
{
    if ($d > 0) {
        return '<span class="dchip up">+' . number_format($d) . '</span>';
    }
    if ($d < 0) {
        return '<span class="dchip dn">−' . number_format(-$d) . '</span>';
    }
    return '<span class="dchip eq">ตรง</span>';
}

/* ##########################################################
   หมวด: ประวัติเคลื่อนไหวรายสินค้า
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — ประวัติเคลื่อนไหวรายสินค้า
   ----------------------------------------------------------
   รวมทุกอย่างที่ทำให้ยอดของสินค้าหนึ่งตัวขยับ: ขาย รับเข้า ตัดออก ตรวจนับ
   และการยกเลิกเอกสารเหล่านั้น แล้วเรียงตามเวลา พร้อมยอดคงเหลือหลังแต่ละรายการ

   ข้อมูลวันนี้มาจาก session จริง (บิลขาย ใบรับเข้า ใบตัดออก ใบตรวจนับ)
   ข้อมูลย้อนหลัง 13 วันเป็นข้อมูลสมมติ เพราะเดโมยังไม่มีฐานข้อมูล
   ยอดคงเหลือคำนวณย้อนจาก "ยอดตอนนี้" เสมอ ตัวเลขจึงต่อกันพอดี

   ระบบจริง: SELECT * FROM stock_move WHERE branch_id=? AND product_id=? ORDER BY created_at
   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

function move_types()
{
    return array(
        'sale'    => array('label' => 'ขาย',          'tone' => 'sale', 'icon' => 'i-cart'),
        'void'    => array('label' => 'ยกเลิกบิล',     'tone' => 'in',   'icon' => 'i-ban'),
        'receive' => array('label' => 'รับเข้า',       'tone' => 'in',   'icon' => 'i-in'),
        'rvoid'   => array('label' => 'ยกเลิกรับเข้า',  'tone' => 'out',  'icon' => 'i-ban'),
        'issue'   => array('label' => 'ตัดออก',       'tone' => 'out',  'icon' => 'i-out'),
        'ivoid'   => array('label' => 'ยกเลิกตัดออก',  'tone' => 'in',   'icon' => 'i-ban'),
        'adjust'  => array('label' => 'ตรวจนับ',      'tone' => 'adj',  'icon' => 'i-clipboard'),
        'avoid'   => array('label' => 'ยกเลิกตรวจนับ', 'tone' => 'adj',  'icon' => 'i-ban'),
    );
}

function move_type_of($k)
{
    $t = move_types();
    return isset($t[$k]) ? $t[$k] : array('label' => $k, 'tone' => 'adj', 'icon' => 'i-info');
}

/** กลุ่มสรุปบนหัวหน้า: รับเข้า / ขาย / ตัดออก / ปรับยอด */
function move_group($type)
{
    if ($type === 'sale' || $type === 'void')      { return 'sale'; }
    if ($type === 'receive' || $type === 'rvoid')  { return 'receive'; }
    if ($type === 'issue' || $type === 'ivoid')    { return 'issue'; }
    return 'adjust';
}

/** แปลง "HH:MM" ของวันนี้เป็น timestamp */
function move_ts_today($hm)
{
    $p = explode(':', $hm);
    return mktime((int) $p[0], isset($p[1]) ? (int) $p[1] : 0, 0);
}

/* ---------- รายการของวันนี้ (จาก session) ---------- */
function move_today_rows($code, $sku)
{
    $rows = array();
    $seq  = 0;
    $add  = function ($ts, $type, $delta, $doc, $by, $note) use (&$rows, &$seq) {
        $rows[] = array('ts' => $ts, 'seq' => $seq++, 'type' => $type, 'delta' => (int) $delta,
                        'doc' => $doc, 'by' => $by, 'note' => $note);
    };

    foreach (bills_today($code) as $b) {
        foreach ($b['lines'] as $l) {
            if ($l['sku'] !== $sku) { continue; }
            $add(move_ts_today($b['time']), 'sale', -$l['qty'], $b['no'], $b['by'], '');
            if (!empty($b['void'])) {
                $add(move_ts_today($b['void_at']), 'void', $l['qty'], $b['no'], $b['void_by'], $b['void_reason']);
            }
        }
    }
    foreach (receives_today($code) as $d) {
        foreach ($d['lines'] as $l) {
            if ($l['sku'] !== $sku) { continue; }
            $add(move_ts_today($d['time']), 'receive', $l['qty'], $d['no'], $d['by'], 'อ้างอิง ' . $d['ref']);
            if (!empty($d['void'])) {
                $add(move_ts_today($d['void_at']), 'rvoid', -$l['qty'], $d['no'], $d['void_by'], $d['void_reason']);
            }
        }
    }
    foreach (issues_today($code) as $d) {
        foreach ($d['lines'] as $l) {
            if ($l['sku'] !== $sku) { continue; }
            $add(move_ts_today($d['time']), 'issue', -$l['qty'], $d['no'], $d['by'], issue_reason_label($d['reason']));
            if (!empty($d['void'])) {
                $add(move_ts_today($d['void_at']), 'ivoid', $l['qty'], $d['no'], $d['void_by'], $d['void_reason']);
            }
        }
    }
    foreach (adjs_today($code) as $d) {
        foreach ($d['lines'] as $l) {
            if ($l['sku'] !== $sku) { continue; }
            $note = 'นับได้ ' . number_format($l['counted'])
                  . ($d['reason'] !== '' ? ' · ' . adj_reason_label($d['reason']) : ' · ตรงกับระบบ');
            $add(move_ts_today($d['time']), 'adjust', $l['diff'], $d['no'], $d['by'], $note);
            if (!empty($d['void']) && $l['diff'] !== 0) {
                $add(move_ts_today($d['void_at']), 'avoid', -$l['diff'], $d['no'], $d['void_by'], $d['void_reason']);
            }
        }
    }
    return $rows;
}

/* ---------- ข้อมูลย้อนหลังสมมติ ---------- */

/** พนักงานของสาขา (ไว้ใส่ชื่อผู้ทำในข้อมูลสมมติ) */
function move_branch_staff($code)
{
    $out = array();
    foreach (demo_users_all() as $u) {
        if ($u['branch'] === $code) {
            $out[] = $u['name'];
        }
    }
    return $out ? $out : array('พนักงาน');
}

/** เหตุการณ์ย้อนหลังแบบสุ่มคงที่ (เปิดกี่ครั้งก็ได้ชุดเดิม) — ยังไม่ใส่ยอดคงเหลือ */
function move_seed_rows($code, $p)
{
    $rows  = array();
    $staff = move_branch_staff($code);
    $today = mktime(0, 0, 0);
    $round = count_round($code);
    $cnt   = count_seed($code, $p['sku'], $round);

    for ($d = MOVE_SEED_DAYS; $d >= 1; $d--) {
        $day = $today - $d * 86400;
        if ((int) date('w', $day) === 0) {
            continue;                                          // อาทิตย์ร้านปิด
        }
        $h  = abs(crc32($code . '|' . $p['sku'] . '|' . date('Ymd', $day)));
        $by = $staff[$h % count($staff)];
        $ds = date('ymd', $day);

        if ($h % 100 < 10) {                                   // ของเข้า
            $q = max(2, $p['reorder'] * (1 + ($h >> 5) % 2));
            $rows[] = array('ts' => $day + (9 * 3600) + (($h >> 3) % 50) * 60, 'type' => 'receive', 'delta' => $q,
                            'doc' => 'RC-' . $ds . '-' . sprintf('%04d', 1 + ($h >> 9) % 3), 'by' => $by,
                            'note' => 'อ้างอิง INV-' . substr($ds, 2) . sprintf('%02d', ($h >> 4) % 90));
        }
        $n = ($h >> 7) % 4;                                    // ขายวันละ 0–3 บิลที่มีตัวนี้
        for ($i = 0; $i < $n; $i++) {
            $hh = ($h >> ($i * 4 + 11));
            $rows[] = array('ts' => $day + (10 + $i * 3 + $hh % 3) * 3600 + ($hh % 60) * 60, 'type' => 'sale',
                            'delta' => -(1 + $hh % 2), 'doc' => 'S-' . $ds . '-' . sprintf('%04d', 3 + ($hh % 40)),
                            'by' => $staff[($hh >> 2) % count($staff)], 'note' => '');
        }
        if (($h >> 13) % 100 < 4) {                            // ของเสีย / เบิกใช้
            $rows[] = array('ts' => $day + 17 * 3600 + (($h >> 2) % 50) * 60, 'type' => 'issue', 'delta' => -1,
                            'doc' => 'IS-' . $ds . '-0001', 'by' => $by,
                            'note' => (($h >> 6) % 2) ? 'ชำรุด / เสียหาย' : 'เบิกใช้ภายใน');
        }
        if ($cnt !== null && date('Ymd', $cnt['at']) === date('Ymd', $day)) {
            $rows[] = array('ts' => $cnt['at'], 'type' => 'adjust', 'delta' => $cnt['diff'],
                            'doc' => 'AD-' . $ds . '-0001', 'by' => $by,
                            'note' => $cnt['diff'] === 0 ? 'ตรงกับระบบ' : 'นับผิดครั้งก่อน');
        }
    }
    return $rows;
}

function move_cmp($a, $b)
{
    if ($a['ts'] === $b['ts']) {
        $sa = isset($a['seq']) ? $a['seq'] : -1;
        $sb = isset($b['seq']) ? $b['seq'] : -1;
        return ($sa === $sb) ? 0 : (($sa < $sb) ? -1 : 1);
    }
    return ($a['ts'] < $b['ts']) ? -1 : 1;
}

/**
 * ความเคลื่อนไหวทั้งหมดของสินค้าหนึ่งตัว เรียงเก่า → ใหม่ พร้อม 'bal' = คงเหลือหลังรายการนั้น
 * คำนวณย้อนจากยอดปัจจุบัน — ถ้ารายการสมมติทำให้ยอดก่อนหน้าติดลบ จะถูกตัดทิ้ง
 */
function move_rows($code, $p)
{
    $now  = product_qty($p, $code);
    $real = move_today_rows($code, $p['sku']);
    usort($real, 'move_cmp');

    /* ยอดตอนต้นวันนี้ = ยอดตอนนี้ − ทุกอย่างที่ขยับวันนี้ */
    $startToday = $now;
    foreach ($real as $r) {
        $startToday -= $r['delta'];
    }

    /* ข้อมูลย้อนหลัง: เดินถอยหลังจากต้นวันนี้ */
    $seed = move_seed_rows($code, $p);
    usort($seed, 'move_cmp');
    $keep = array();
    $bal  = $startToday;
    for ($i = count($seed) - 1; $i >= 0; $i--) {
        $before = $bal - $seed[$i]['delta'];
        if ($before < 0) {
            continue;                                        // ทำให้ติดลบ → ตัดทิ้ง
        }
        $seed[$i]['bal'] = $bal;
        $keep[] = $seed[$i];
        $bal = $before;
    }
    $keep = array_reverse($keep);

    /* วันนี้: เดินไปข้างหน้าจากต้นวัน */
    $bal = $startToday;
    foreach ($real as $i => $r) {
        $bal += $r['delta'];
        $real[$i]['bal'] = $bal;
    }
    return array_merge($keep, $real);
}

function move_periods()
{
    return array('today' => 'วันนี้', '7' => '7 วัน', '14' => '14 วัน');
}

/** ตัดตามช่วงเวลา แล้วสรุปยอดต้นงวด/ปลายงวด และยอดรวมของแต่ละกลุ่ม */
function move_view($rows, $period, $now)
{
    $days  = ($period === 'today') ? 0 : ((int) $period - 1);
    $from  = mktime(0, 0, 0) - $days * 86400;
    $out   = array();
    $open  = null;
    $sum   = array('receive' => 0, 'sale' => 0, 'issue' => 0, 'adjust' => 0);

    foreach ($rows as $r) {
        if ($r['ts'] < $from) {
            $open = $r['bal'];                   // ยอดหลังรายการสุดท้ายก่อนช่วง = ยอดยกมา
            continue;
        }
        $out[] = $r;
        $sum[move_group($r['type'])] += $r['delta'];
    }
    if ($open === null) {
        $open = $out ? $out[0]['bal'] - $out[0]['delta'] : $now;
    }
    return array('rows' => array_reverse($out), 'open' => $open, 'close' => $now, 'sum' => $sum, 'from' => $from);
}

function move_when($ts)
{
    if (date('Ymd', $ts) === date('Ymd')) {
        return 'วันนี้ ' . date('H:i', $ts);
    }
    if (date('Ymd', $ts) === date('Ymd', time() - 86400)) {
        return 'เมื่อวาน ' . date('H:i', $ts);
    }
    $wd = array('อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.');
    return $wd[(int) date('w', $ts)] . ' ' . thai_day_month($ts) . ' ' . date('H:i', $ts);
}

function move_qs($q, $cat, $sku, $period, $extra = array())
{
    $a = array();
    foreach ($extra as $k => $v) {                 // ค่าเพิ่มของหน้า เช่น b=สาขา (หน้าผู้ดูแล)
        if ((string) $v !== '') {
            $a[] = rawurlencode($k) . '=' . rawurlencode($v);
        }
    }
    if ($q !== '')                              { $a[] = 'q='   . rawurlencode($q); }
    if ($cat !== '')                            { $a[] = 'cat=' . rawurlencode($cat); }
    if ($sku !== '')                            { $a[] = 'sku=' . rawurlencode($sku); }
    if ($period !== '' && $period !== '7')      { $a[] = 'p='   . rawurlencode($period); }
    return $a ? '?' . implode('&', $a) : '';
}

/* ##########################################################
   หมวด: รายงานยอดขาย
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — รายงานยอดขาย
   ----------------------------------------------------------
   หลักการสำคัญ (ตอบเรื่องพนักงานย้ายสาขา)

   ทุกบรรทัดของยอดขายเก็บ 2 อย่างคู่กันเสมอ
     branch  = สาขา "ณ วันที่ขาย"   → ใช้ตอบคำถาม "สาขานี้ขายได้เท่าไร"
     by_user = คนขาย                → ใช้ตอบคำถาม "ฉันขายได้เท่าไร"

   สาขาถูกปั๊มลงในเอกสารตอนบันทึก ไม่ใช่ไปอ่านจากโปรไฟล์ตอนออกรายงาน
   ถ้าอ่านจากโปรไฟล์ พอพนักงานย้ายสาขา ยอดเก่าของสาขาเดิมจะย้ายตามไปด้วย
   ซึ่งผิด — ยอดของเดือนที่แล้วต้องไม่เปลี่ยนไม่ว่าจะย้ายคนไปไหน

   วันนี้ = ข้อมูลจริงจาก session (บิลที่ยกเลิกไม่นับ)
   ย้อนหลัง = ข้อมูลสมมติที่คำนวณจาก crc32 จึงคงที่ทุกครั้งที่เปิด
   ระบบจริง: SELECT ... FROM sale_bill WHERE branch_id=? AND sold_at BETWEEN ?
   ========================================================== */

/**
 * ข้อมูลเต็มของผู้ใช้ — session เก็บไว้แค่ไม่กี่ฟิลด์เพื่อให้เบา
 * ประวัติการย้ายสาขาจึงต้องอ่านจากทะเบียนพนักงานอีกที
 * ระบบจริง: SELECT * FROM users WHERE id = ?
 */
function full_user($user)
{
    $all = demo_users_all();
    return isset($all[$user['username']]) ? $all[$user['username']] : $user;
}

/** ยอดขายของพนักงานคนหนึ่งในวันหนึ่ง — ข้อมูลสมมติที่คงที่ */
function demo_sales_of($username, $ts)
{
    $all = demo_users_all();
    if (!isset($all[$username])) {
        return null;
    }
    $u      = $all[$username];
    $branch = user_branch_on($u, $ts);
    if ($branch === '') {
        return null;                                   // ยังไม่ได้เริ่มงาน
    }
    if ((int) date('N', $ts) === 7) {
        return null;                                   // อาทิตย์ปิดร้าน
    }
    if (staff_dayoff($username) === (int) date('N', $ts)) {
        return null;                                   // วันหยุดของคนนี้
    }

    $s     = abs(crc32($username . '|sale|' . date('Ymd', $ts)));
    $bills = 6 + ($s % 19);                            // 6–24 บิล
    $qty   = $bills * (1 + (($s >> 4) % 3));           // 1–3 ชิ้นต่อบิล
    $total = $qty * (15 + (($s >> 9) % 31)) * 10;      // 150–450 บาทต่อชิ้น

    return array(
        'user'   => $username,
        'name'   => $u['name'],
        'branch' => $branch,
        'bills'  => $bills,
        'qty'    => $qty,
        'total'  => $total,
    );
}

/** ยอดขายจริงของวันนี้จาก session (ไม่นับบิลที่ยกเลิก) */
function today_sales_rows($code)
{
    $out = array();
    foreach (bills_today($code) as $b) {
        if (!empty($b['void'])) {
            continue;
        }
        $k = $b['by_user'];
        if (!isset($out[$k])) {
            $out[$k] = array('user' => $k, 'name' => $b['by'], 'branch' => $code,
                             'bills' => 0, 'qty' => 0, 'total' => 0);
        }
        $out[$k]['bills']++;
        $out[$k]['qty']   += $b['qty'];
        $out[$k]['total'] += $b['total'];
    }
    return array_values($out);
}

/**
 * ยอดขายทุกคนของวันหนึ่ง
 * วันนี้ใช้ของจริงจาก session ถ้ายังไม่มีบิลก็คืน array ว่าง (ยังไม่ได้ขาย)
 */
function sales_of_day($ts)
{
    $isToday = (date('Ymd', $ts) === date('Ymd'));
    $rows    = array();

    foreach (demo_users_all() as $username => $u) {
        if ($u['role'] !== 'staff') {
            continue;
        }
        if ($isToday) {
            continue;                                  // วันนี้อ่านจาก session แทน
        }
        $r = demo_sales_of($username, $ts);
        if ($r !== null) {
            $rows[] = $r;
        }
    }

    if ($isToday) {
        foreach (array_keys(demo_branches()) as $code) {
            foreach (today_sales_rows($code) as $r) {
                $rows[] = $r;
            }
        }
    }
    return $rows;
}

/** ช่วงวันที่ของแต่ละโหมด — คืน array(from_ts, to_ts, label) */
function report_range($mode, $ref, $user)
{
    $today = strtotime(date('Y-m-d'));

    if ($mode === 'day') {
        $d = $ref !== '' ? strtotime($ref) : $today;
        if ($d === false || $d > $today) {
            $d = $today;
        }
        return array($d, $d, thai_date_full($d));
    }

    if ($mode === 'month') {
        $m = $ref !== '' ? strtotime($ref . '-01') : strtotime(date('Y-m-01'));
        if ($m === false || $m > $today) {
            $m = strtotime(date('Y-m-01'));
        }
        $end = strtotime(date('Y-m-t', $m));
        if ($end > $today) {
            $end = $today;
        }
        return array($m, $end, thai_month_full($m));
    }

    /* ทั้งหมด — นับตั้งแต่วันเริ่มงาน */
    $start = strtotime(user_start_date(full_user($user)));
    return array($start, $today, 'ตั้งแต่ ' . thai_date_full($start));
}

/**
 * ดึงรายงานตามเงื่อนไข
 *   $scope = 'mine' (เฉพาะฉัน ทุกสาขาที่เคยอยู่) | 'branch' (ทั้งสาขาปัจจุบัน)
 * คืน array: sum, days (ไล่วัน), people (แยกคน), branches (แยกสาขา)
 */
function sales_report($user, $scope, $mode, $ref)
{
    $range = report_range($mode, $ref, $user);
    $from  = $range[0];
    $to    = $range[1];

    $sum      = array('bills' => 0, 'qty' => 0, 'total' => 0, 'days' => 0);
    $days     = array();
    $people   = array();
    $branches = array();

    for ($ts = $from; $ts <= $to; $ts = strtotime('+1 day', $ts)) {
        $dayTotal = 0;
        $dayBills = 0;
        $dayQty   = 0;

        foreach (sales_of_day($ts) as $r) {
            if ($scope === 'mine' && $r['user'] !== $user['username']) {
                continue;
            }
            if ($scope === 'branch' && $r['branch'] !== $user['branch']) {
                continue;
            }

            $dayBills += $r['bills'];
            $dayQty   += $r['qty'];
            $dayTotal += $r['total'];

            $pk = $r['user'];
            if (!isset($people[$pk])) {
                $people[$pk] = array('user' => $pk, 'name' => $r['name'],
                                     'bills' => 0, 'qty' => 0, 'total' => 0);
            }
            $people[$pk]['bills'] += $r['bills'];
            $people[$pk]['qty']   += $r['qty'];
            $people[$pk]['total'] += $r['total'];

            $bk = $r['branch'];
            if (!isset($branches[$bk])) {
                $branches[$bk] = array('branch' => $bk, 'bills' => 0, 'qty' => 0,
                                       'total' => 0, 'first' => $ts, 'last' => $ts);
            }
            $branches[$bk]['bills'] += $r['bills'];
            $branches[$bk]['qty']   += $r['qty'];
            $branches[$bk]['total'] += $r['total'];
            $branches[$bk]['last']   = $ts;
        }

        $days[] = array('ts' => $ts, 'bills' => $dayBills, 'qty' => $dayQty, 'total' => $dayTotal);

        $sum['bills'] += $dayBills;
        $sum['qty']   += $dayQty;
        $sum['total'] += $dayTotal;
        if ($dayTotal > 0) {
            $sum['days']++;
        }
    }

    usort($people, 'cmp_total_desc');

    return array(
        'label'    => $range[2],
        'from'     => $from,
        'to'       => $to,
        'sum'      => $sum,
        'days'     => $days,
        'people'   => $people,
        'branches' => $branches,
    );
}

function cmp_total_desc($a, $b)
{
    if ($a['total'] === $b['total']) {
        return 0;
    }
    return ($a['total'] < $b['total']) ? 1 : -1;
}

/** ยุบรายวันเป็นรายเดือน ใช้ตอนช่วงยาวเกินกว่าจะวาดทีละวัน */
function group_by_month($days)
{
    $out = array();
    foreach ($days as $d) {
        $k = date('Y-m', $d['ts']);
        if (!isset($out[$k])) {
            $out[$k] = array('ts' => strtotime(date('Y-m-01', $d['ts'])),
                             'bills' => 0, 'qty' => 0, 'total' => 0);
        }
        $out[$k]['bills'] += $d['bills'];
        $out[$k]['qty']   += $d['qty'];
        $out[$k]['total'] += $d['total'];
    }
    return array_values($out);
}

/** เดือนย้อนหลังที่เลือกได้ */
function report_months($user, $limit = 6)
{
    $out   = array();
    $start = strtotime(date('Y-m-01', strtotime(user_start_date(full_user($user)))));
    $m     = strtotime(date('Y-m-01'));
    while ($m >= $start && count($out) < $limit) {
        $out[] = array('key' => date('Y-m', $m), 'label' => thai_month_full($m));
        $m = strtotime('-1 month', $m);
    }
    return $out;
}

function thai_month_full($ts)
{
    $m = array('', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
               'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม');
    return $m[(int) date('n', $ts)] . ' ' . (((int) date('Y', $ts)) + 543);
}

/* ##########################################################
   หมวด: รับคืนสินค้า
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — รับคืนสินค้า
   ----------------------------------------------------------
   ค้นบิลเก่า → ดูว่ายังอยู่ในกำหนดวันไหม → เลือกรายการ/จำนวนที่คืน
   → เลือกเหตุผล → ยืนยันยอดเงินคืน → ออกใบรับคืน (RT-)

   กติกาที่ตกลงไว้
   - ต้องมีสิทธิ์เสริม refund
   - บิลต้องไม่เก่ากว่า backdate_days(สาขา) วัน (ผู้ดูแลตั้ง ค่าเริ่มต้น 7) · เกินกำหนดผู้ดูแลคืนให้ได้
   - คืนบางรายการ / บางชิ้นได้ · คืนเกินจำนวนที่ซื้อ (หักที่คืนไปแล้ว) ไม่ได้
   - ของกลับเข้าสต๊อกเฉพาะเหตุผลที่ขายต่อได้ (ซื้อผิดรุ่น/ผิดแบบ)
     เหตุผลอื่นไม่เข้าสต๊อก — แยกเก็บไว้ เพราะไม่ควรเอาไปขายต่อ
   - คืนเป็นเงินสดจากลิ้นชักของวันนี้เท่านั้น (ไม่คืนด้วยการโอน)
     จึงต้องเปิดร้านก่อน · ยอดเงินคืนแก้ได้ แต่ไม่เกินราคาที่ลูกค้าจ่าย
     และถ้าแก้ต้องใส่เหตุผล

   ยังไม่มีฐานข้อมูล:
     บิลวันก่อน   สร้างจากข้อมูลสมมติที่คงที่ (past_bills)
     บิลวันนี้    อ่านจาก $_SESSION['sale'] เหมือนหน้าขาย
     ใบรับคืน     $_SESSION['ret'][ 'RS|20260925' ][] = เอกสารหนึ่งใบ
     คืนไปแล้ว    $_SESSION['ret_by_bill'][ เลขบิล ][ SKU ] = จำนวน
   ระบบจริง: ตาราง return_doc + return_item + stock_move + cash_move

   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

/** เดโมสร้างบิลย้อนหลังกี่วัน — มากกว่ากำหนดคืน เพื่อให้เห็นบิลที่เกินกำหนดด้วย */
function return_lookback_days()
{
    return 14;
}

/* ---------- เหตุผลการคืน ----------
   restock = ของกลับเข้าสต๊อกขายต่อได้ · note = ต้องกรอกรายละเอียด */
function return_reasons()
{
    return array(
        'wrong'  => array('label' => 'ซื้อผิดรุ่น / ผิดแบบ',     'hint' => 'ของยังสมบูรณ์ ไม่ได้แกะใช้ — กลับเข้าสต๊อกขายต่อได้', 'restock' => true,  'note' => false),
        'defect' => array('label' => 'สินค้าชำรุด / ใช้งานไม่ได้', 'hint' => 'ไม่เข้าสต๊อก แยกเก็บไว้ส่งเคลม',                    'restock' => false, 'note' => false),
        'used'   => array('label' => 'ใช้แล้ว / สภาพไม่สมบูรณ์',   'hint' => 'ไม่เข้าสต๊อก',                                   'restock' => false, 'note' => false),
        'other'  => array('label' => 'อื่น ๆ',                     'hint' => 'ไม่เข้าสต๊อก · ต้องระบุรายละเอียด',               'restock' => false, 'note' => true),
    );
}

function return_reason_label($k)
{
    $r = return_reasons();
    return isset($r[$k]) ? $r[$k]['label'] : $k;
}

/* ==========================================================
   บิลที่ค้นได้
   ========================================================== */

function ret_product($sku)
{
    foreach (demo_products() as $p) {
        if ($p['sku'] === $sku) {
            return $p;
        }
    }
    return null;
}

/**
 * บิลของวันก่อน (ข้อมูลสมมติที่คงที่) — ใช้จำนวนบิลจากข้อมูลรายงาน แต่จำกัดคนละไม่เกิน 3 ใบต่อวัน
 * ผูกสาขาตาม "สาขา ณ วันนั้น" ของพนักงาน เหมือนรายงานยอดขาย
 */
/** บิลของวันก่อนพร้อมเลขที่ — เลขรันต่อจากบิลก่อนหน้าในเดือนเดียวกัน แยกชุด VAT / ไม่ VAT */
function past_bills($code, $ts)
{
    $rows = past_bills_gen($code, $ts);
    $cnt  = month_bill_counts($code, $ts);
    foreach ($rows as $i => $b) {
        $k = $b['vat'] ? 'v' : 'n';
        $cnt[$k]++;
        $rows[$i]['no'] = bill_no_format(bill_prefix($code, $b['vat']), $ts, $cnt[$k]);
        $rows[$i]['seed_i'] = $i;                    // ใช้หาใบรับคืนสมมติของบิลนี้
    }
    return $rows;
}

/** สร้างบิลสมมติของวันหนึ่ง (ยังไม่มีเลขที่) — คงที่ทุกครั้งที่เรียก */
function past_bills_gen($code, $ts)
{
    static $cache = array();
    $day = date('Ymd', $ts);
    $ck  = $code . '|' . $day;
    if (isset($cache[$ck])) {
        return $cache[$ck];
    }

    $prods = demo_products();
    $np    = count($prods);
    $rows  = array();

    foreach (demo_users_all() as $un => $u) {
        if ($u['role'] !== 'staff') {
            continue;
        }
        $r = demo_sales_of($un, $ts);
        if ($r === null || $r['branch'] !== $code) {
            continue;
        }
        $n = min(3, (int) $r['bills']);
        for ($i = 0; $i < $n; $i++) {
            $s     = abs(crc32($un . '|bill|' . $day . '|' . $i));
            $lines = array();
            $nl    = 1 + ($s % 2);
            $used  = array();
            for ($j = 0; $j < $nl; $j++) {
                $p = $prods[(($s >> 3) + $j * 7) % $np];
                if (isset($used[$p['sku']])) {
                    continue;
                }
                $used[$p['sku']] = true;
                $q     = 1 + (($s >> (5 + $j)) % 2);
                $price = product_price($p);
                $lines[] = array('sku' => $p['sku'], 'name' => $p['name'], 'unit' => $p['unit'],
                                 'qty' => $q, 'price' => $price, 'sum' => $price * $q);
            }
            $qty = 0;
            $tot = 0;
            foreach ($lines as $l) {
                $qty += $l['qty'];
                $tot += $l['sum'];
            }
            $h = 10 + (($i * 2 + ($s % 2)) % 10);
            $rows[] = array(
                'no'      => '',
                'date'    => $day,
                'time'    => str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad(($s >> 7) % 60, 2, '0', STR_PAD_LEFT),
                'branch'  => $code,
                'by'      => $u['name'],
                'by_user' => $un,
                'method'  => ((($s >> 11) % 3) === 0) ? 'transfer' : 'cash',
                'vat'     => ((($s >> 13) % 10) < 3),          // ราว 30% ลูกค้าขอบิล VAT
                'lines'   => $lines,
                'items'   => count($lines),
                'qty'     => $qty,
                'total'   => $tot,
            );
        }
    }

    usort($rows, 'ret_cmp_time');
    $cache[$ck] = $rows;
    return $rows;
}

function ret_cmp_time($a, $b)
{
    return strcmp($a['time'], $b['time']);
}

/** บิลทุกใบของวันหนึ่ง — วันนี้อ่านจาก session (ไม่รวมบิลที่ยกเลิก) */
function bills_of_day($code, $ts)
{
    if (date('Ymd', $ts) === date('Ymd')) {
        $out = array();
        foreach (bills_today($code) as $b) {
            if (!empty($b['void'])) {
                continue;
            }
            $b['date'] = date('Ymd');
            $out[] = $b;
        }
        return $out;
    }
    return past_bills($code, $ts);
}

/** ค้นบิลย้อนหลัง — เลขบิล ชื่อสินค้า หรือ SKU · ใหม่สุดขึ้นก่อน */
function return_find_bills($code, $q, $limit = 100)
{
    $q     = trim($q);
    $today = strtotime(date('Y-m-d'));
    $out   = array();
    for ($d = 0; $d <= return_lookback_days(); $d++) {
        $ts   = strtotime('-' . $d . ' day', $today);
        $list = array_reverse(bills_of_day($code, $ts));
        foreach ($list as $b) {
            if ($q !== '' && !ret_bill_match($b, $q)) {
                continue;
            }
            $out[] = $b;
            if (count($out) >= $limit) {
                return $out;
            }
        }
    }
    return $out;
}

function ret_bill_match($b, $q)
{
    if (stripos($b['no'], $q) !== false) {
        return true;
    }
    foreach ($b['lines'] as $l) {
        if (stripos($l['name'], $q) !== false || stripos($l['sku'], $q) !== false) {
            return true;
        }
    }
    return false;
}

/** หาบิลจากเลขที่บิล (เช่น RS2026-09-0012 / RSV2026-09-0003) */
function return_bill($code, $no)
{
    /* เลขที่บิลไม่มีวันที่ในตัว (มีแค่ปี-เดือน) จึงไล่หาย้อนหลังตามช่วงที่ค้นได้ */
    $today = strtotime(date('Y-m-d'));
    for ($d = 0; $d <= return_lookback_days(); $d++) {
        foreach (bills_of_day($code, strtotime('-' . $d . ' day', $today)) as $b) {
            if ($b['no'] === $no) {
                return $b;
            }
        }
    }
    return null;
}

/** บิลนี้ผ่านมากี่วันแล้ว (วันนี้ = 0) */
function bill_age_days($b)
{
    $d = strtotime($b['date']);
    return (int) round((strtotime(date('Y-m-d')) - $d) / 86400);
}

/* ==========================================================
   จำนวนที่คืนไปแล้ว
   ========================================================== */

function returned_of_bill($no, $bill = null)
{
    $out = (isset($_SESSION['ret_by_bill'][$no]) && is_array($_SESSION['ret_by_bill'][$no]))
         ? $_SESSION['ret_by_bill'][$no] : array();
    if ($bill !== null) {                                 // รวมใบรับคืนสมมติของวันก่อน (เดโม)
        foreach (seeded_returned_of_bill($bill) as $sku => $q) {
            $out[$sku] = (isset($out[$sku]) ? $out[$sku] : 0) + $q;
        }
    }
    return $out;
}

function bill_has_returns($no, $bill = null)
{
    return array_sum(returned_of_bill($no, $bill)) > 0;
}

/** แต่ละรายการในบิล + คืนไปแล้ว + คืนได้อีก */
function return_lines($bill)
{
    $done = returned_of_bill($bill['no'], $bill);
    $out  = array();
    /* บิลที่มีส่วนลดท้ายบิล → คืนเงินตามราคาที่ลูกค้าจ่ายจริง (เฉลี่ยส่วนลดตามสัดส่วน) */
    $f = (!empty($bill['discount']) && !empty($bill['subtotal'])) ? $bill['total'] / $bill['subtotal'] : 1;
    foreach ($bill['lines'] as $l) {
        if ($f != 1) {
            $l['list_price'] = $l['price'];
            $l['price']      = round($l['price'] * $f, 2);
        }
        $back = isset($done[$l['sku']]) ? (int) $done[$l['sku']] : 0;
        $l['back']   = $back;
        $l['remain'] = max(0, (int) $l['qty'] - $back);
        $l['p']      = ret_product($l['sku']);
        $out[] = $l;
    }
    return $out;
}

/**
 * สถานะของบิลสำหรับการคืน
 * คืน array('ok' => bool, 'code' => ok|late|done, 'msg' => ข้อความ, 'left' => วันที่เหลือ)
 * บิลที่เกินกำหนดคืนไม่ได้ (ผู้ดูแลไม่ทำรับคืนเอง — ขยายจำนวนวันของสาขาได้)
 */
function bill_return_status($bill, $user = null)
{
    $age   = bill_age_days($bill);
    $limit = backdate_days($bill['branch']);
    $rem   = 0;
    foreach (return_lines($bill) as $l) {
        $rem += $l['remain'];
    }
    if ($rem === 0) {
        return array('ok' => false, 'code' => 'done', 'msg' => 'คืนครบทุกรายการแล้ว', 'left' => 0);
    }
    if ($age > $limit) {
        return array('ok' => false, 'code' => 'late',
                     'msg' => 'เกินกำหนดคืน ' . $limit . ' วัน (ผ่านมา ' . $age . ' วัน) — คืนไม่ได้'
                            . ' · ถ้าจำเป็น ผู้ดูแลขยายจำนวนวันได้ที่หน้าจัดการสาขา', 'left' => 0);
    }
    $left = $limit - $age;
    return array('ok' => true, 'code' => 'ok',
                 'msg' => $left === 0 ? 'คืนได้ถึงวันนี้' : 'คืนได้อีก ' . $left . ' วัน', 'left' => $left);
}

/* ==========================================================
   ใบรับคืน
   ========================================================== */

function ret_key($code)
{
    return $code . '|' . date('Ymd');
}

function returns_today($code)
{
    $k = ret_key($code);
    return isset($_SESSION['ret'][$k]) ? $_SESSION['ret'][$k] : array();
}

function return_next_no($code)
{
    return 'RT-' . date('ymd') . '-' . str_pad(count(returns_today($code)) + 1, 4, '0', STR_PAD_LEFT);
}

function return_by_no($code, $no)
{
    foreach (returns_today($code) as $r) {
        if ($r['no'] === $no) {
            return $r;
        }
    }
    return null;
}

/**
 * บันทึกการรับคืน
 * $qtys   = array( SKU => จำนวนที่คืน )
 * $refund = ยอดเงินคืนที่พนักงานยืนยัน (ว่าง = ใช้ยอดคำนวณ)
 * คืนค่า array('doc' => ใบรับคืน) หรือ array('error' => ข้อความ)
 */
function return_save($code, $user, $bill, $qtys, $reason, $note, $refund, $refundNote)
{
    if ($user['role'] !== 'staff' || !can($user, 'refund')) {
        return array('error' => 'ไม่มีสิทธิ์รับคืนสินค้า — การรับคืนเป็นหน้าที่ของพนักงานที่ได้รับมอบหมาย');
    }
    if (!store_is_open($code)) {
        return array('error' => 'ต้องเปิดร้านก่อน เพราะเงินคืนจ่ายจากลิ้นชักของวันนี้');
    }
    $st = bill_return_status($bill, $user);
    if (!$st['ok']) {
        return array('error' => $st['msg']);
    }

    $reasons = return_reasons();
    if (!isset($reasons[$reason])) {
        return array('error' => 'กรุณาเลือกเหตุผลการคืน');
    }
    if ($reasons[$reason]['note'] && trim($note) === '') {
        return array('error' => 'เหตุผล “' . $reasons[$reason]['label'] . '” ต้องกรอกรายละเอียดด้วย');
    }

    $lines = array();
    $calc  = 0;
    $qty   = 0;
    foreach (return_lines($bill) as $l) {
        $want = isset($qtys[$l['sku']]) ? (int) $qtys[$l['sku']] : 0;
        if ($want <= 0) {
            continue;
        }
        if ($want > $l['remain']) {
            return array('error' => $l['name'] . ' คืนได้อีกไม่เกิน ' . $l['remain'] . ' ' . $l['unit']);
        }
        $sum     = $want * (float) $l['price'];
        $lines[] = array('sku' => $l['sku'], 'name' => $l['name'], 'unit' => $l['unit'],
                         'qty' => $want, 'price' => (float) $l['price'], 'sum' => $sum);
        $calc += $sum;
        $qty  += $want;
    }
    if (!$lines) {
        return array('error' => 'ยังไม่ได้ใส่จำนวนที่คืนสักรายการ');
    }

    $refund = ($refund === '' || $refund === null) ? $calc : round((float) $refund, 2);
    if ($refund < 0 || $refund > $calc) {
        return array('error' => 'ยอดเงินคืนต้องอยู่ระหว่าง 0 ถึง ' . money2($calc) . ' บาท (ไม่เกินที่ลูกค้าจ่ายสำหรับรายการที่คืน)');
    }
    if (abs($refund - $calc) >= 0.01 && trim($refundNote) === '') {
        return array('error' => 'ยอดเงินคืนไม่เท่ากับยอดคำนวณ — กรุณาใส่เหตุผลที่ปรับยอด');
    }

    $restock = $reasons[$reason]['restock'];
    $doc = array(
        'no'          => return_next_no($code),
        'time'        => date('H:i'),
        'branch'      => $code,
        'by'          => $user['name'],
        'by_user'     => $user['username'],
        'bill_no'     => $bill['no'],
        'bill_date'   => $bill['date'],
        'bill_by'     => $bill['by'],
        'reason'      => $reason,
        'note'        => trim($note),
        'restock'     => $restock,
        'lines'       => $lines,
        'items'       => count($lines),
        'qty'         => $qty,
        'calc'        => $calc,
        'refund'      => $refund,
        'refund_note' => trim($refundNote),
    );

    foreach ($lines as $l) {
        if ($restock) {
            stock_adj_add($code, $l['sku'], $l['qty']);          // ของสมบูรณ์ → กลับเข้าสต๊อก
        }
        if (!isset($_SESSION['ret_by_bill'][$bill['no']][$l['sku']])) {
            $_SESSION['ret_by_bill'][$bill['no']][$l['sku']] = 0;
        }
        $_SESSION['ret_by_bill'][$bill['no']][$l['sku']] += $l['qty'];
    }

    $k = ret_key($code);
    if (!isset($_SESSION['ret'][$k])) {
        $_SESSION['ret'][$k] = array();
    }
    $_SESSION['ret'][$k][] = $doc;

    /* ---- เก็บลงประวัติการทำรายการ ---- */
    $names = array();
    foreach ($lines as $l) {
        $names[] = $l['name'] . ' ×' . $l['qty'];
    }
    $detail = array(
        'บิลเดิม'      => $bill['no'] . ' · ' . thai_date_full(strtotime($bill['date'])) . ' ' . $bill['time'] . ' น. โดย ' . $bill['by'],
        'รายการที่คืน'  => implode(' · ', $names),
        'เหตุผล'       => return_reason_label($reason) . ($doc['note'] !== '' ? ' — ' . $doc['note'] : ''),
        'สต๊อก'        => $restock ? 'กลับเข้าสต๊อก ' . $qty . ' ชิ้น' : 'ไม่เข้าสต๊อก (แยกเก็บ)',
        'ยอดตามราคาขาย' => money2($calc) . ' บาท',
        'คืนเงินสด'     => money2($refund) . ' บาท' . ($doc['refund_note'] !== '' ? ' — ปรับยอด: ' . $doc['refund_note'] : ''),
        'ผู้รับคืน'      => $user['name'],
    );
    log_add($code, 'return', $user, 'รับคืนสินค้า ' . $doc['no'] . ' (บิล ' . $bill['no'] . ')', $detail, $refund, $doc['no']);

    return array('doc' => $doc);
}

/* ==========================================================
   ใบรับคืนของวันก่อน (ข้อมูลสมมติที่คงที่) — ให้หน้าตรวจสอบของผู้ดูแลมีข้อมูลให้ดู
   ----------------------------------------------------------
   บิลสมมติราว 1 ใน 15 ใบ ถูกคืน 1 ชิ้นภายใน 0–2 วันหลังซื้อ (เฉพาะวันที่ผ่านไปแล้ว)
   ผูกกับบิลด้วย สาขา|วันที่|ลำดับบิล จึงไม่เปลี่ยนตามรหัสเลขที่บิลที่บัญชีตั้ง
   ระบบจริง: SELECT จาก ao_stock_return — ไม่ต้องมีส่วนนี้
   ========================================================== */

/** ใบรับคืนสมมติของบิลนี้ (ไม่มี = null) — $i = ลำดับบิลในวันนั้น */
function past_bill_seed_return($bill, $i)
{
    $h = abs(crc32($bill['branch'] . '|' . $bill['date'] . '|' . $i . '|ret'));
    if ($h % 15 !== 0 || !$bill['lines']) {
        return null;
    }
    $rts = strtotime('+' . (($h >> 5) % 3) . ' day', strtotime($bill['date']));
    if (date('Ymd', $rts) >= date('Ymd') || (int) date('N', $rts) === 7) {
        return null;                                      // ยังไม่ถึง / วันอาทิตย์ร้านปิด
    }
    $why  = array('wrong', 'wrong', 'defect', 'defect', 'used', 'other');
    $why  = $why[($h >> 8) % count($why)];
    $rs   = return_reasons();
    $l    = $bill['lines'][($h >> 11) % count($bill['lines'])];
    $fac  = (!empty($bill['subtotal']) && $bill['subtotal'] > 0) ? $bill['total'] / $bill['subtotal'] : 1;
    $calc = round($l['price'] * $fac, 2);
    $cut  = ($why === 'used') ? round($calc * 0.2) : 0;  // ใช้แล้ว → หักค่าเสื่อมบางส่วน
    $hh   = 10 + (($h >> 14) % 9);
    if ($rts === strtotime($bill['date']) && sprintf('%02d', $hh) <= substr($bill['time'], 0, 2)) {
        $hh = min(20, (int) substr($bill['time'], 0, 2) + 1);
    }
    return array(
        'no'          => '',
        'date'        => date('Ymd', $rts),
        'time'        => sprintf('%02d:%02d', $hh, ($h >> 18) % 60),
        'branch'      => $bill['branch'],
        'by'          => $bill['by'],
        'by_user'     => $bill['by_user'],
        'bill_no'     => $bill['no'],
        'bill_date'   => $bill['date'],
        'bill_by'     => $bill['by'],
        'reason'      => $why,
        'note'        => $why === 'other' ? 'ลูกค้าเปลี่ยนใจ ของยังไม่แกะ แต่กล่องยับ' : ($why === 'defect' ? 'ลูกค้าแจ้งว่าใช้ได้ 1 วันแล้วเสีย' : ''),
        'restock'     => $rs[$why]['restock'],
        'lines'       => array(array('sku' => $l['sku'], 'name' => $l['name'], 'unit' => $l['unit'],
                                     'qty' => 1, 'price' => $calc, 'sum' => $calc)),
        'items'       => 1,
        'qty'         => 1,
        'calc'        => $calc,
        'refund'      => $calc - $cut,
        'refund_note' => $cut > 0 ? 'หักค่าสภาพสินค้า ' . money2($cut) . ' บาท (ลูกค้ายินยอม)' : '',
        'seed'        => true,
    );
}

/** ใบรับคืนสมมติที่ออก "ในวันนั้น" ของสาขา (เรียงตามเวลา มีเลขที่ RT-ปปดดวว-NNNN) */
function past_returns($code, $ts)
{
    static $cache = array();
    $day = date('Ymd', $ts);
    if (isset($cache[$code . '|' . $day])) {
        return $cache[$code . '|' . $day];
    }
    $out = array();
    if ($day < date('Ymd')) {
        for ($k = 0; $k <= 2; $k++) {
            $bts = strtotime('-' . $k . ' day', strtotime($day));
            foreach (past_bills($code, $bts) as $i => $b) {
                $r = past_bill_seed_return($b, $i);
                if ($r !== null && $r['date'] === $day && empty($b['void'])) {
                    $out[] = $r;
                }
            }
        }
        usort($out, 'ret_cmp_time');
        foreach ($out as $i => $r) {
            $out[$i]['no'] = 'RT-' . substr($day, 2) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
        }
    }
    $cache[$code . '|' . $day] = $out;
    return $out;
}

/** จำนวนที่คืนไปแล้วจากใบรับคืนสมมติ: array( SKU => จำนวน ) — นับเฉพาะใบที่ออกก่อนวันนี้ */
function seeded_returned_of_bill($bill)
{
    if (empty($bill['date']) || $bill['date'] >= date('Ymd') || !isset($bill['seed_i'])) {
        return array();
    }
    $r = past_bill_seed_return($bill, $bill['seed_i']);
    if ($r === null) {
        return array();
    }
    return array($r['lines'][0]['sku'] => 1);
}

/** ใบรับคืนของวันหนึ่ง — วันนี้อ่านจาก session · วันก่อนเป็นข้อมูลสมมติ */
function returns_of_day($code, $ts)
{
    if (date('Ymd', $ts) === date('Ymd')) {
        $out = array();
        foreach (returns_today($code) as $r) {
            $r['date'] = date('Ymd');
            $out[] = $r;
        }
        return $out;
    }
    return past_returns($code, $ts);
}

/** หาใบรับคืนจากเลขที่ RT-ปปดดวว-NNNN (วันอยู่ในเลขที่) */
function return_doc_find($code, $no)
{
    if (!preg_match('/^RT-(\d{6})-\d{4}$/', $no, $m) || ($ts = strtotime('20' . $m[1])) === false) {
        return null;
    }
    foreach (returns_of_day($code, $ts) as $r) {
        if ($r['no'] === $no) {
            return $r;
        }
    }
    return null;
}

/* ##########################################################
   หมวด: แก้ / ยกเลิกเอกสารย้อนหลัง (สิทธิ์เสริม backdate)
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — แก้ / ยกเลิกเอกสารย้อนหลัง (สิทธิ์เสริม backdate)
   ----------------------------------------------------------
   ใช้กับเอกสารคลัง 3 ชนิด: ใบรับเข้า (RC) · ใบเบิก/ตัดออก (IS) · ใบตรวจนับ (AD)
   บิลขายของวันก่อนไม่ยกเลิกย้อนหลัง — ใช้ "รับคืนสินค้า" แทน (เงินต้องคืนจากลิ้นชักวันนี้)

   กติกา
   - พนักงาน: ต้องมีสิทธิ์ backdate · ย้อนได้ไม่เกิน backdate_days(สาขา)
              ใบของคนอื่นต้องมีสิทธิ์ void_others เพิ่มด้วย
   - ผู้ดูแล: ย้อนได้ทุกวันที่ระบบมีข้อมูล
   - ต้องกรอกเหตุผลเสมอ · ใบเดิมไม่ถูกลบ ขึ้นว่า "ยกเลิกย้อนหลัง" พร้อมชื่อคนยกเลิก
   - สต๊อกถูกปรับ "วันนี้" (ไม่ย้อนไปแก้ยอดของวันเก่า) และลงประวัติของวันนี้
   - แก้ไข = ยกเลิกใบเดิม แล้วดึงรายการมาเป็นใบใหม่ของวันนี้ ให้แก้แล้วบันทึก

   ยังไม่มีฐานข้อมูล:
     เอกสารวันก่อน   สร้างจากข้อมูลสมมติที่คงที่ (past_docs)
     สถานะยกเลิก     $_SESSION['past_void'][ สาขา ][ เลขเอกสาร ]
   ระบบจริง: UPDATE xxx_doc SET status='void' + INSERT stock_move (วันนี้) อ้าง ref_id ใบเดิม

   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

/** ชนิดเอกสารที่แก้ย้อนหลังได้ */
function past_types()
{
    return array(
        'RC' => array('type' => 'receive', 'label' => 'ใบรับเข้า',       'void' => 'rvoid', 'page' => 'receive.php',   'tone' => 'in'),
        'IS' => array('type' => 'issue',   'label' => 'ใบเบิก / ตัดออก', 'void' => 'ivoid', 'page' => 'issue.php',     'tone' => 'out'),
        'AD' => array('type' => 'count',   'label' => 'ใบตรวจนับ',      'void' => 'avoid', 'page' => 'stocktake.php', 'tone' => 'adj'),
    );
}

/** ผู้ใช้คนนี้ย้อนดู/แก้ได้กี่วันในสาขานี้ (0 = ไม่มีสิทธิ์) */
function backdate_limit($user, $code)
{
    if ($user['role'] === 'admin') {
        return return_lookback_days();
    }
    return can($user, 'backdate') ? backdate_days($code) : 0;
}

/**
 * เอกสารคลังของวันก่อน (ข้อมูลสมมติที่คงที่)
 * พนักงานที่ประจำสาขานั้นในวันนั้นแต่ละคนมีเอกสารบ้างไม่มีบ้าง
 */
function past_docs($code, $ts)
{
    static $cache = array();
    $day = date('Ymd', $ts);
    $ck  = $code . '|' . $day;
    if (isset($cache[$ck])) {
        return past_apply_void($code, $cache[$ck]);
    }
    $out = array();
    if ((int) date('N', $ts) === 7) {                   // อาทิตย์ปิดร้าน
        $cache[$ck] = $out;
        return $out;
    }

    $prods  = demo_products();
    $np     = count($prods);
    $iss    = array('use', 'damaged', 'expired', 'lost', 'return');
    $adjR   = array('miscount', 'unlogged');      // ยอดขาด · ถ้ารวมแล้วเกินใช้ found
    $notes  = array('lost' => 'หาไม่พบหลังจัดชั้นวางใหม่', 'branch' => 'ส่งไปสาขาบางนา');
    $seq    = array('RC' => 0, 'IS' => 0, 'AD' => 0);
    $all    = demo_users_all();

    foreach ($all as $un => $u) {
        if ($u['role'] !== 'staff' || user_branch_on($u, $ts) !== $code) {
            continue;
        }
        $s = abs(crc32($un . '|doc|' . $day));

        $make = function ($kind, $salt) use ($prods, $np, $s, $un, $u, $day, $code) {
            $t     = abs(crc32($un . '|' . $kind . '|' . $day . '|' . $salt));
            $n     = 1 + ($t % 3);
            $lines = array();
            $used  = array();
            for ($j = 0; $j < $n; $j++) {
                $p = $prods[(($t >> 4) + $j * 5) % $np];
                if (isset($used[$p['sku']])) {
                    continue;
                }
                $used[$p['sku']] = true;
                $lines[] = array('p' => $p, 't' => $t >> ($j + 2));
            }
            $h = 9 + (($t >> 7) % 9);
            return array(
                'lines' => $lines,
                'time'  => str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad(($t >> 11) % 60, 2, '0', STR_PAD_LEFT),
                't'     => $t,
                'base'  => array('date' => $day, 'branch' => $code, 'by' => $u['name'], 'by_user' => $un),
            );
        };

        if ($s % 2 === 0) {                              // ใบรับเข้า
            $m = $make('RC', 1);
            $lines = array();
            $qty   = 0;
            foreach ($m['lines'] as $l) {
                $q = 6 + ($l['t'] % 19);
                $lines[] = array('sku' => $l['p']['sku'], 'name' => $l['p']['name'], 'unit' => $l['p']['unit'], 'qty' => $q);
                $qty += $q;
            }
            $out[] = array_merge($m['base'], array('kind' => 'RC', 'time' => $m['time'], 'lines' => $lines,
                     'items' => count($lines), 'qty' => $qty, 'ref' => 'INV-' . (4100 + ($m['t'] % 800)),
                     'reason' => '', 'note' => ''));
        }
        if ((($s >> 2) % 3) !== 1) {                     // ใบเบิก / ตัดออก
            $m   = $make('IS', 2);
            $why = $iss[($m['t'] >> 3) % count($iss)];
            $lines = array();
            $qty   = 0;
            foreach ($m['lines'] as $l) {
                $q = 1 + ($l['t'] % 3);
                $lines[] = array('sku' => $l['p']['sku'], 'name' => $l['p']['name'], 'unit' => $l['p']['unit'], 'qty' => $q);
                $qty += $q;
            }
            $out[] = array_merge($m['base'], array('kind' => 'IS', 'time' => $m['time'], 'lines' => $lines,
                     'items' => count($lines), 'qty' => $qty, 'ref' => '',
                     'reason' => $why, 'note' => isset($notes[$why]) ? $notes[$why] : ''));
        }
        if ((($s >> 4) % 2) === 0) {                     // ใบตรวจนับ
            $m = $make('AD', 3);
            $lines = array();
            $diffs = 0;
            $net   = 0;
            foreach ($m['lines'] as $l) {
                $h    = abs(crc32($l['p']['sku'] . '|cnt|' . $day));
                $have = 4 + ($h % 30);
                $d    = array(-2, -1, -1, 0, 0, 1);
                $d    = $d[($h >> 5) % 6];                     // ส่วนใหญ่ขาดเล็กน้อย บางตัวตรง บางตัวเกิน
                $net += $d;
                $lines[] = array('sku' => $l['p']['sku'], 'name' => $l['p']['name'], 'unit' => $l['p']['unit'],
                                 'have' => $have, 'counted' => max(0, $have + $d), 'diff' => max(0, $have + $d) - $have,
                                 'qty' => abs($d));
                $diffs += abs($d);
            }
            $out[] = array_merge($m['base'], array('kind' => 'AD', 'time' => $m['time'], 'lines' => $lines,
                     'items' => count($lines), 'qty' => $diffs, 'ref' => '',
                     'reason' => $net > 0 ? 'found' : $adjR[($m['t'] >> 5) % 2], 'note' => ''));
        }
    }

    usort($out, 'past_cmp_time');
    foreach ($out as $i => $d) {
        $seq[$d['kind']]++;
        $out[$i]['no'] = $d['kind'] . '-' . substr($day, 2) . '-' . str_pad($seq[$d['kind']], 4, '0', STR_PAD_LEFT);
    }
    $cache[$ck] = $out;
    return past_apply_void($code, $out);
}

function past_cmp_time($a, $b)
{
    return strcmp($a['time'], $b['time']);
}

/** ใส่สถานะ "ยกเลิกย้อนหลัง" ที่เก็บไว้ใน session */
function past_apply_void($code, $docs)
{
    foreach ($docs as $i => $d) {
        if (isset($_SESSION['past_void'][$code][$d['no']])) {
            $docs[$i] = array_merge($d, $_SESSION['past_void'][$code][$d['no']], array('void' => true));
        }
    }
    return $docs;
}

/** หาเอกสารจากเลขที่ (XX-YYMMDD-NNNN) */
function past_doc($code, $no)
{
    if (!preg_match('/^(RC|IS|AD)-(\d{6})-\d{4}$/', $no, $m)) {
        return null;
    }
    $ts = strtotime('20' . $m[2]);
    if ($ts === false) {
        return null;
    }
    foreach (past_docs($code, $ts) as $d) {
        if ($d['no'] === $no) {
            return $d;
        }
    }
    return null;
}

function past_age($doc)
{
    return (int) round((strtotime(date('Y-m-d')) - strtotime($doc['date'])) / 86400);
}

/** แก้/ยกเลิกใบนี้ได้ไหม — คืน array(ok, msg) */
function past_can_edit($user, $doc)
{
    if (!empty($doc['void'])) {
        return array(false, 'ใบนี้ถูกยกเลิกไปแล้ว');
    }
    $age = past_age($doc);
    if ($user['role'] === 'admin') {
        return array(true, '');
    }
    if (!can($user, 'backdate')) {
        return array(false, 'ไม่มีสิทธิ์แก้เอกสารย้อนหลัง');
    }
    $lim = backdate_days($doc['branch']);
    if ($age > $lim) {
        return array(false, 'เกินกำหนด ' . $lim . ' วัน — ต้องให้ผู้ดูแลทำ');
    }
    if ($doc['by_user'] !== $user['username'] && !can($user, 'void_others')) {
        return array(false, 'ใบของ ' . $doc['by'] . ' — ต้องมีสิทธิ์แก้งานคนอื่นด้วย');
    }
    return array(true, '');
}

/** สต๊อกที่ต้องปรับวันนี้เมื่อยกเลิกใบนี้: array( SKU => +/- จำนวน ) */
function past_reverse_delta($doc)
{
    $out = array();
    foreach ($doc['lines'] as $l) {
        if ($doc['kind'] === 'RC') {
            $out[$l['sku']] = -(int) $l['qty'];          // ถอนของที่รับเข้า
        } elseif ($doc['kind'] === 'IS') {
            $out[$l['sku']] = (int) $l['qty'];           // คืนของที่ตัดออก
        } else {
            $out[$l['sku']] = -(int) $l['diff'];         // ถอยส่วนต่างจากการนับ
        }
    }
    return $out;
}

/**
 * ยกเลิก (หรือยกเลิกเพื่อแก้ไข) เอกสารย้อนหลัง
 * คืนค่า array('doc' => ใบเดิม, 'page' => หน้าที่ต้องไปแก้ต่อ) หรือ array('error' => ข้อความ)
 */
function past_doc_void($code, $no, $user, $reason, $redo)
{
    $doc = past_doc($code, $no);
    if ($doc === null) {
        return array('error' => 'ไม่พบเอกสารนี้');
    }
    $chk = past_can_edit($user, $doc);
    if (!$chk[0]) {
        return array('error' => $chk[1]);
    }
    if (trim($reason) === '') {
        return array('error' => 'การยกเลิกหรือแก้ไขย้อนหลังต้องระบุหมายเหตุทุกครั้ง');
    }
    $t = past_types();
    $t = $t[$doc['kind']];

    if ($redo) {
        $busy = ($doc['kind'] === 'RC' && rdraft_count() > 0) || ($doc['kind'] === 'IS' && idraft_count() > 0)
             || ($doc['kind'] === 'AD' && adraft_size() > 0);
        if ($busy) {
            return array('error' => 'ยังมี' . $t['label'] . 'ที่ทำค้างอยู่ บันทึกหรือล้างใบนั้นก่อนจึงจะแก้ใบเก่าได้');
        }
    }

    /* ถอยแล้วสต๊อกต้องไม่ติดลบ */
    $delta = past_reverse_delta($doc);
    $short = array();
    foreach ($delta as $sku => $d) {
        $p = product_by_sku($sku);
        if ($p !== null && $d < 0 && product_qty($p, $code) + $d < 0) {
            $short[] = $p['name'] . ' (เหลือ ' . number_format(product_qty($p, $code)) . ')';
        }
    }
    if ($short) {
        return array('error' => 'ยกเลิกไม่ได้ เพราะของบางส่วนถูกขาย/เบิกไปแล้ว — ' . implode(' · ', $short)
                              . ' · กรณีนี้ให้ใช้การตรวจนับ/ปรับยอดแทน');
    }
    foreach ($delta as $sku => $d) {
        if ($d !== 0) {
            stock_adj_add($code, $sku, $d);
        }
    }

    $_SESSION['past_void'][$code][$no] = array(
        'void_at'     => date('j/n') . ' ' . date('H:i'),
        'void_by'     => $user['name'],
        'void_user'   => $user['username'],
        'void_reason' => trim($reason),
        'void_mode'   => $redo ? 'edit' : 'void',
    );

    $names = array();
    foreach ($doc['lines'] as $l) {
        $names[] = $l['name'] . ($doc['kind'] === 'AD' ? ' (ส่วนต่าง ' . ($l['diff'] > 0 ? '+' : '') . $l['diff'] . ')' : ' ×' . $l['qty']);
    }
    $detail = array(
        'ใบเดิม'       => $doc['no'] . ' · ' . thai_date_full(strtotime($doc['date'])) . ' ' . $doc['time'] . ' น. โดย ' . $doc['by'],
        'รายการ'       => implode(' · ', $names),
        'ย้อนหลัง'      => past_age($doc) . ' วัน',
        'สต๊อก'        => 'ปรับยอดวันนี้ให้ตรงกับการยกเลิก',
        'เหตุผล'       => trim($reason),
    );
    if ($redo) {
        $detail['การทำต่อ'] = 'ดึงรายการมาเป็นใบใหม่ของวันนี้เพื่อแก้แล้วบันทึก';
    }
    log_add($code, $t['void'], $user,
            ($redo ? 'ยกเลิกเพื่อแก้ไขย้อนหลัง ' : 'ยกเลิกย้อนหลัง ') . $t['label'] . ' ' . $doc['no'],
            $detail, null, $doc['no']);

    if ($redo) {
        if ($doc['kind'] === 'RC') {
            rdraft_from_receive($doc);
        } elseif ($doc['kind'] === 'IS') {
            idraft_from_issue($doc);
        } else {
            adraft_from_adj($doc);
        }
        $_SESSION['flash'] = 'ยกเลิก' . $t['label'] . ' ' . $doc['no'] . ' ของวันที่ '
                           . thai_date_full(strtotime($doc['date'])) . ' แล้ว — รายการเดิมอยู่ในใบใหม่ของวันนี้ แก้แล้วกดบันทึกได้เลย';
    }
    return array('doc' => $doc, 'page' => $t['page']);
}

/* ##########################################################
   หมวด: ข้อมูลของฝ่ายบัญชี
   ########################################################## */

/* ==========================================================
   AOSTOCK DEMO — ข้อมูลของฝ่ายบัญชี
   บิลขายรายวัน (แยก VAT / ไม่ VAT) และเงินเข้าแยกสาขา
   ========================================================== */

/** บิลทั้งหมดของสาขาในวันหนึ่ง รวมบิลที่ยกเลิก (บัญชีต้องเห็นเลขที่ครบทุกใบ) */
function acct_bills($code, $ts)
{
    if (date('Ymd', $ts) === date('Ymd')) {
        $out = array();
        foreach (bills_today($code) as $b) {
            $b['date'] = date('Ymd');
            $b['vat']  = !empty($b['vat']);
            $out[] = $b;
        }
        return $out;
    }
    return past_bills($code, $ts);
}

/** เพิ่มยอดก่อน VAT / VAT ให้บิล */
function acct_bill_row($b, $code)
{
    $vs = $b['vat'] ? vat_split($b['total']) : array($b['total'], 0);
    $b['branch'] = $code;
    $b['base']   = $vs[0];
    $b['vatamt'] = $vs[1];
    return $b;
}

/**
 * สรุปของสาขาในวันหนึ่ง
 * คืน array(bills, v, n, void, total, base, vat, cash, transfer, refund, net, rows)
 */
function acct_day($code, $ts)
{
    $s = array('bills' => 0, 'v' => 0, 'n' => 0, 'void' => 0, 'total' => 0, 'base' => 0, 'vat' => 0,
               'cash' => 0, 'transfer' => 0, 'refund' => 0, 'net' => 0, 'rows' => array());
    foreach (acct_bills($code, $ts) as $b) {
        $b = acct_bill_row($b, $code);
        $s['rows'][] = $b;
        $s['bills']++;
        $s[$b['vat'] ? 'v' : 'n']++;
        if (!empty($b['void'])) {
            $s['void']++;
            continue;                                   // บิลยกเลิก: นับใบ แต่ไม่นับเงิน
        }
        $s['total'] += $b['total'];
        $s['base']  += $b['vat'] ? $b['base'] : 0;     // มูลค่าก่อน VAT นับเฉพาะบิล VAT
        $s['vat']   += $b['vatamt'];
        $s[$b['method'] === 'cash' ? 'cash' : 'transfer'] += $b['total'];
    }
    if (date('Ymd', $ts) === date('Ymd')) {
        $s['refund'] = store_refunds($code);
    }
    $s['net'] = $s['cash'] + $s['transfer'] - $s['refund'];
    return $s;
}

/** สรุปทั้งเดือน (ตั้งแต่วันที่ 1 ถึงวันนี้หรือสิ้นเดือน) ของสาขา */
function acct_month($code, $ts)
{
    $m = array('bills' => 0, 'v' => 0, 'n' => 0, 'void' => 0, 'total' => 0, 'base' => 0, 'vat' => 0,
               'cash' => 0, 'transfer' => 0, 'refund' => 0, 'net' => 0);
    $end = min(strtotime(date('Y-m-t', $ts)), strtotime(date('Y-m-d')));
    for ($d = strtotime(date('Y-m-01', $ts)); $d <= $end; $d = strtotime('+1 day', $d)) {
        $s = acct_day($code, $d);
        foreach ($m as $k => $v) {
            $m[$k] += $s[$k];
        }
    }
    return $m;
}

/* ##########################################################
   หมวด: หน้าบิลขายและเงินเข้า (account.php)
   ########################################################## */

function acct_row_cmp($a, $b)
{
    $c = strcmp($a['branch'], $b['branch']);
    if ($c !== 0) {
        return $c;
    }
    $c = strcmp($a['vat'] ? '1' : '0', $b['vat'] ? '1' : '0');
    return $c !== 0 ? $c : strcmp($a['no'], $b['no']);
}

/* ##########################################################
   หมวด: หน้าจัดการสาขา (adm-branches.php)
   ########################################################## */

/** สาขานี้มีข้อมูลแล้วหรือยัง — คืนเหตุผล (ว่าง = ยังไม่มี ลบได้) */
function branch_data_reason($code)
{
    $b = demo_branches_all();
    if (isset($b[$code]) && empty($b[$code]['added'])) {
        return 'มีข้อมูลการขายและสต๊อกย้อนหลังแล้ว';
    }
    foreach (demo_users_all() as $u) {
        if ($u['branch'] === $code) {
            return 'มีพนักงานประจำอยู่';
        }
        if (!empty($u['history'])) {
            foreach ($u['history'] as $h) {
                if ($h['branch'] === $code) {
                    return 'เคยมีพนักงานประจำ (ยอดขายเก่าผูกกับสาขานี้)';
                }
            }
        }
    }
    foreach (array('sale' => 'บิลขาย', 'recv' => 'ใบรับเข้า', 'issue' => 'ใบเบิก', 'adj' => 'ใบตรวจนับ',
                   'ret' => 'ใบรับคืน', 'store' => 'การเปิดร้าน') as $k => $lb) {
        if (!empty($_SESSION[$k])) {
            foreach ($_SESSION[$k] as $key => $rows) {
                if (strpos($key, $code . '|') === 0 && !empty($rows)) {
                    return 'มี' . $lb . 'แล้ว';
                }
            }
        }
    }
    if (!empty($_SESSION['stock_adj'][$code])) {
        return 'มีการเคลื่อนไหวสต๊อกแล้ว';
    }
    return '';
}

/** รหัสเลขที่บิลนี้ถูกใช้แล้วหรือยัง (ทุกสาขา ทุกชุด) */
function prefix_in_use($p)
{
    foreach (array_keys(demo_branches_all()) as $c) {
        if (acct_setting($c, 'prefix_vat') === $p || acct_setting($c, 'prefix_novat') === $p) {
            return true;
        }
    }
    return false;
}

/** อ่านค่าตั้ง 4 ค่าจากฟอร์ม — คืน array(ค่า, ข้อผิดพลาด) */
function read_settings($fields)
{
    $rules = branch_setting_rules();
    $out   = array();
    foreach ($fields as $k => $f) {
        $raw = isset($_POST[$k]) ? trim($_POST[$k]) : '';
        if ($raw === '' || !preg_match('/^\d+$/', $raw)) {
            return array($out, $f['label'] . ' ต้องเป็นตัวเลข');
        }
        $v = (int) $raw;
        if ($v < $rules[$k][0] || $v > $rules[$k][1]) {
            return array($out, $f['label'] . ' ต้องอยู่ระหว่าง ' . number_format($rules[$k][0]) . '–' . number_format($rules[$k][1]));
        }
        $out[$k] = $v;
    }
    return array($out, '');
}

function read_info($info)
{
    $out = array();
    foreach ($info as $k => $f) {
        $v = isset($_POST[$k]) ? trim(preg_replace('/\s+/u', ' ', $_POST[$k])) : '';
        if ($f['req'] && $v === '') {
            return array($out, 'กรุณากรอก' . $f['label']);
        }
        if (function_exists('mb_strlen') ? mb_strlen($v, 'UTF-8') > $f['max'] : strlen($v) > $f['max'] * 3) {
            return array($out, $f['label'] . 'ยาวเกินไป');
        }
        $out[$k] = $v;
    }
    if ($out['short'] === '') {
        $out['short'] = $out['name'];
    }
    return array($out, '');
}

function branch_list_cmp($a, $b)
{
    return (int) empty($a['active']) - (int) empty($b['active']);   // เปิดใช้งานก่อน แล้วค่อยที่ปิดแล้ว
}

/* ##########################################################
   หมวด: หน้าประวัติการทำรายการ (history.php)
   ########################################################## */

/** ลิงก์ของหน้าประวัติ — ใช้ร่วมกันระหว่าง history.php (พนักงาน) กับ adm-history.php (ผู้ดูแล)
    หน้าไหนต้องการฐานลิงก์อื่น ให้ตั้ง $HIST_BASE ก่อนเรียก เช่น 'adm-history.php?view=day&b=RS' */
function hist_url($q = '')
{
    global $HIST_BASE;
    $base = !empty($HIST_BASE) ? $HIST_BASE : 'history.php';
    if ($q === '') {
        return $base;
    }
    return $base . (strpos($base, '?') === false ? '?' : '&') . $q;
}

/* ##########################################################
   หมวด: หน้าจัดการพนักงาน (adm-users.php)
   ########################################################## */

/** พนักงานทั้งหมด (รวมที่พักงาน ไม่รวมผู้ดูแล / บัญชี) */
function staff_all()
{
    $out = array();
    foreach (demo_users_all() as $k => $u) {
        if ($u['role'] === 'staff') {
            $out[$k] = $u;
        }
    }
    return $out;
}

function perm_names($keys)
{
    $pl  = perm_list();
    $out = array();
    foreach ($keys as $k) {
        if (isset($pl[$k])) {
            $out[] = $pl[$k]['short'];
        }
    }
    return $out ? implode(' · ', $out) : 'ไม่มี';
}

/** อักษรย่อจากชื่อ — ตัวแรกของชื่อและนามสกุล (ข้ามสระ/วรรณยุกต์ที่วางบน-ล่าง) */
function auto_initials($name)
{
    $parts = preg_split('/\s+/u', trim($name));
    $out   = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        if (preg_match('/[\p{L}\p{N}]/u', $p, $m)) {
            $out .= $m[0];
        }
    }
    return $out !== '' ? $out : '-';
}

/** เคยทำรายการแล้วหรือยัง — คืนเหตุผล (ว่าง = ยังไม่เคย ลบได้) */
function staff_data_reason($k)
{
    if (array_key_exists($k, demo_users_base())) {
        return 'มียอดขายและประวัติการทำงานแล้ว';
    }
    foreach (array('sale' => 'บิลขาย', 'recv' => 'ใบรับเข้า', 'issue' => 'ใบเบิก', 'adj' => 'ใบตรวจนับ', 'ret' => 'ใบรับคืน') as $s => $lb) {
        if (!empty($_SESSION[$s])) {
            foreach ($_SESSION[$s] as $rows) {
                foreach ($rows as $r) {
                    if ((isset($r['by_user']) && $r['by_user'] === $k)) {
                        return 'มี' . $lb . 'ที่เคยทำแล้ว';
                    }
                }
            }
        }
    }
    if (!empty($_SESSION['store'])) {
        foreach ($_SESSION['store'] as $st) {
            if (isset($st['opened_user']) && $st['opened_user'] === $k) {
                return 'เคยเปิดร้านแล้ว';
            }
        }
    }
    return '';
}

/** อ่านสิทธิ์จากฟอร์ม (เรียงตามลำดับใน perm_list) */
function read_perms()
{
    $in  = (isset($_POST['perms']) && is_array($_POST['perms'])) ? $_POST['perms'] : array();
    $out = array();
    foreach (array_keys(perm_list()) as $p) {
        if (in_array($p, $in, true)) {
            $out[] = $p;
        }
    }
    return $out;
}

/** PIN ซ้ำกับใครในสาขา (ว่าง = ไม่ซ้ำ) */
function pin_owner($branch, $pin, $except)
{
    foreach (branch_staff($branch) as $k => $o) {
        if ($k !== $except && $o['pin'] === $pin) {
            return $o['name'];
        }
    }
    return '';
}

/** ช่องติ๊กสิทธิ์ แยกกลุ่ม "เมนูที่ใช้ได้" / "สิทธิ์เสริม" */
function perm_boxes($checked, $branch)
{
    $pl = perm_list();
    foreach (array('menu' => 'เมนูที่ใช้ได้', 'extra' => 'สิทธิ์เสริม (ให้เฉพาะคนที่ไว้ใจ)') as $g => $title) {
        echo '<h4 class="perm-h">' . e($title) . '</h4><div class="perm-grid">';
        foreach ($pl as $k => $p) {
            if ($p['group'] !== $g) {
                continue;
            }
            echo '<label class="perm-o"><input type="checkbox" name="perms[]" value="' . e($k) . '"'
               . (in_array($k, $checked, true) ? ' checked' : '') . '><span><svg class="ico"><use href="#i-check"/></svg><b>'
               . e($p['label']) . '</b>';
            if ($k === 'backdate' || $k === 'refund') {
                echo '<small>ย้อนหลังได้ ' . (int) backdate_days($branch) . ' วัน (ตั้งที่หน้าจัดการสาขา)</small>';
            }
            if ($k === 'sale') {
                echo '<small>ไม่ติ๊ก = พนักงานคลังอย่างเดียว ไม่ต้องเปิดร้านก่อนใช้งาน</small>';
            }
            echo '</span></label>';
        }
        echo '</div>';
    }
}

/* ##########################################################
   หมวด: หน้าขายสินค้า (sale.php)
   ########################################################## */

/* ---------- ข้อความแจ้งผล (ส่งกลับแบบ out-of-band ให้ htmx ด้วย) ---------- */
function sale_flash($err, $done, $voided, $oob)
{
    echo '<div id="sale-flash"' . ($oob ? ' hx-swap-oob="true"' : '') . '>';

    if ($err !== '') {
        echo '<div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span>'
           . e($err) . '</span></div>';

    } elseif ($voided !== null) {
        $isEdit = (isset($voided['void_mode']) && $voided['void_mode'] === 'edit');
        echo '<div class="alert ' . ($isEdit ? 'alert-info' : 'alert-warn') . '" role="status">'
           . '<svg class="ico"><use href="#' . ($isEdit ? 'i-arrow' : 'i-ban') . '"/></svg><span>';
        if ($isEdit) {
            echo 'ยกเลิกบิล <b>' . e($voided['no']) . '</b> และดึง ' . (int) $voided['items']
               . ' รายการกลับเข้าตะกร้าให้แล้ว — แก้จำนวนที่ผิดแล้วกดรับเงินใหม่ได้เลย '
               . 'ระบบจะออกเลขบิลใหม่ให้อัตโนมัติ';
        } else {
            echo 'ยกเลิกบิล <b>' . e($voided['no']) . '</b> แล้ว · คืนสต๊อก '
               . (int) $voided['qty'] . ' ชิ้น · เหตุผล: ' . e($voided['void_reason']);
        }
        echo '</span></div>';

    } elseif ($done !== null && empty($done['void'])) {
        echo '<div class="alert alert-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span>'
           . 'บันทึกบิล <b>' . e($done['no']) . '</b> แล้ว · ยอด <b class="num">' . money2($done['total']) . '</b> บาท · '
           . (!empty($done['discount']) ? 'ส่วนลด <b class="num">' . money2($done['discount']) . '</b> บาท · ' : '');
        if ($done['method'] === 'cash') {
            echo 'รับเงิน <span class="num">' . money2($done['received']) . '</span> บาท · '
               . 'เงินทอน <b class="num">' . money2($done['change']) . '</b> บาท';
        } else {
            echo 'ชำระโดยการโอน / พร้อมเพย์';
        }
        echo '</span>';

        /* กดผิดก็แก้ได้ทันทีจากตรงนี้ — ทั้งสองทางต้องกรอกหมายเหตุก่อน */
        $atts = ' data-bill="' . e($done['no']) . '" data-total="' . money2($done['total']) . '"'
              . ' data-qty="' . (int) $done['qty'] . '" data-items="' . (int) $done['items'] . '"'
              . ' hx-post="sale.php" hx-target="#sale-live" hx-swap="outerHTML"';

        echo '<div class="alert-act">';

        echo '<form method="post" action="sale.php" data-confirm="edit" hx-confirm="แก้ไขบิล"' . $atts . '>'
           . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
           . '<input type="hidden" name="act" value="edit">'
           . '<input type="hidden" name="no" value="' . e($done['no']) . '">'
           . '<input class="reason-fb" type="text" name="reason" value=""'
           . ' placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุการแก้ไขบิล">'
           . '<button class="btn btn-ghost btn-sm" type="submit">'
           . '<svg class="ico"><use href="#i-arrow"/></svg> แก้ไขบิลนี้</button>'
           . '</form>';

        echo '<form method="post" action="sale.php" data-confirm="void" hx-confirm="ยกเลิกบิล"' . $atts . '>'
           . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
           . '<input type="hidden" name="act" value="void">'
           . '<input type="hidden" name="no" value="' . e($done['no']) . '">'
           . '<input class="reason-fb" type="text" name="reason" value=""'
           . ' placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุการยกเลิกบิล">'
           . '<button class="btn btn-ghost btn-sm" type="submit">'
           . '<svg class="ico"><use href="#i-ban"/></svg> ยกเลิกบิลนี้</button>'
           . '</form>';

        echo '</div>';

        echo '</div>';
    }
    echo '</div>';
}

/* ##########################################################
   หมวด: ภาพรวมของผู้ดูแล (adm-dashboard.php)
   ########################################################## */

/** เรียงรายการที่ต้องตรวจ ใหม่สุดขึ้นก่อน */
function dash_review_cmp($a, $b)
{
    return $b['ts'] - $a['ts'];
}
