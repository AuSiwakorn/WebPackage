<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/core.php
 * ROLE: ค่าคงที่ของระบบ · ตัวช่วยทั่วไป (escape, URL, เงิน, วันที่ไทย, CSV) · สวิตช์เมนู / บทบาท / สิทธิ์
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_setting (สวิตช์เมนู ผ่าน stock_setting_get)
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *
 * ⚠ ไฟล์นี้ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb — มีได้แค่ define() และประกาศ function (ห้าม echo / header / query ตอนโหลด)
 */

/* ---------- ค่าคงที่ของระบบ (ย้ายจาก themes/aostock/inc/config.php) ---------- */
define('APP_NAME',  'AOSTOCK');
define('APP_TITLE', 'ระบบบริหารสต๊อกสินค้า');
define('APP_OWNER', 'บริษัท เอโอซอฟต์ จำกัด');

/* path ของหน้า AOSTOCK = โฟลเดอร์ที่ติดตั้งเว็บ (เช่น /aostock) — มาจาก $webBase ใน fix.<โดเมน>.php ผ่าน URL_WEB_ROOT */
define('APP_BASE', defined('URL_WEB_ROOT') ? rtrim((string) parse_url(URL_WEB_ROOT, PHP_URL_PATH), '/') : '');

/* ==========================================================
   สวิตช์เปิด–ปิดเมนู / บทบาท
   ----------------------------------------------------------
   active_roles / active_menus = สิ่งที่ระบบมี (นักพัฒนาแก้ในโค้ด)
   feature_groups = กลุ่มฟีเจอร์ที่ผู้ดูแลระบบปิดได้จากหลังบ้าน admweb (AOSTOCK → เปิด–ปิดเมนู · main_settings.php)
     เก็บกลุ่มที่ปิดไว้ใน ao_stock_setting key features_off (JSON) — ปิดแล้วซ่อนเมนู + เปิด URL ตรงไม่ได้ (require_login)
     เมนูหลัก (ขาย เปิด–ปิดร้าน รับเข้า เบิก สินค้า ประวัติ หน้าผู้ดูแล) ปิดไม่ได้
   ========================================================== */

/** บทบาทที่เปิดใช้งานอยู่ตอนนี้ — ฝ่ายบัญชีปิดได้จากหลังบ้าน (กลุ่ม account)
    TODO:
      - [x] ช่วงที่ 9: ตัดฝ่ายบัญชีออกเมื่อกลุ่ม account ถูกปิด */
function active_roles()
{
    $roles = array('staff', 'admin', 'account');   // ไม่มีหัวหน้าคลัง — ใช้สิทธิ์เสริมของพนักงานแทน
    return feature_group_on('account') ? $roles : array('staff', 'admin');
}

/** กลุ่มฟีเจอร์ที่เปิด–ปิดได้จากหลังบ้าน: คีย์ => array(label, hint, pages = หน้าที่ปิดตาม, role = บทบาทที่ปิดตาม)
    TODO:
      - [x] ช่วงที่ 9: 7 กลุ่มตามที่ตกลง (ข้อ 3ก) */
function feature_groups()
{
    return array(
        'refund'    => array('label' => 'รับคืนสินค้า', 'hint' => 'พนักงานรับคืนสินค้า / คืนเงินสด และหน้าตรวจใบรับคืนของผู้ดูแล',
                             'pages' => array('return.php', 'adm-return.php')),
        'stocktake' => array('label' => 'ตรวจนับ / ปรับยอด', 'hint' => 'หน้าตรวจนับของพนักงาน และงานค้าง "ตรวจนับรอบนี้ยังไม่ครบ"',
                             'pages' => array('stocktake.php')),
        'category'  => array('label' => 'หมวดสินค้า (ฝั่งพนักงาน)', 'hint' => 'พนักงานที่มีสิทธิ์เพิ่ม / ลบหมวดเอง — ผู้ดูแลยังจัดการหมวดได้ตามปกติ',
                             'pages' => array('categories.php')),
        'movements' => array('label' => 'ประวัติเคลื่อนไหวรายสินค้า', 'hint' => 'ทั้งของพนักงานและของผู้ดูแล',
                             'pages' => array('movements.php', 'adm-movements.php')),
        'report'    => array('label' => 'รายงานยอดขายของพนักงาน', 'hint' => 'หน้ารายงานยอดขายของพนักงาน — รายงานของผู้ดูแลยังเปิดอยู่',
                             'pages' => array('report-sales.php')),
        'account'   => array('label' => 'ฝ่ายบัญชี', 'hint' => 'บัญชีบทบาทฝ่ายบัญชีเข้าระบบไม่ได้ — ผู้ดูแลยังเปิดหน้าบิลขายและเงินเข้า / ตั้งค่าเลขที่บิลได้',
                             'pages' => array(), 'role' => 'account'),
        'notify'    => array('label' => 'การแจ้งเตือน Telegram / อีเมล', 'hint' => 'หยุดส่งทุกช่องทาง (รวมอีเมลรายวัน) และซ่อนหน้าตั้งค่าการแจ้งเตือน',
                             'pages' => array('adm-notify.php', 'adm-notify-preview.php')),
    );
}

/** กลุ่มที่ปิดอยู่ (คีย์ของ feature_groups) */
function features_off()
{
    $v = json_decode((string) stock_setting_get('features_off', '[]'), true);
    return is_array($v) ? array_values(array_intersect(array_keys(feature_groups()), $v)) : array();
}

function feature_group_on($key)
{
    return !in_array($key, features_off(), true);
}

/** บันทึกกลุ่มที่ปิด — คืนรายการที่เปลี่ยน array(คีย์ => true เปิด | false ปิด)
    TODO:
      - [x] ช่วงที่ 9: ใช้ในหน้า main_settings.php ของหลังบ้าน */
function features_off_set($off, $uid = 0)
{
    $off    = array_values(array_intersect(array_keys(feature_groups()), (array) $off));
    $before = features_off();
    stock_setting_set('features_off', json_encode($off), false, $uid);
    $ch = array();
    foreach (array_keys(feature_groups()) as $k) {
        if (in_array($k, $before, true) !== in_array($k, $off, true)) {
            $ch[$k] = !in_array($k, $off, true);
        }
    }
    return $ch;
}

/** หน้าที่ถูกปิดอยู่ตอนนี้ (ตามกลุ่มที่ปิด) */
function feature_pages_off()
{
    $g   = feature_groups();
    $out = array();
    foreach (features_off() as $k) {
        $out = array_merge($out, $g[$k]['pages']);
    }
    return $out;
}

/** เมนูที่ทำเสร็จแล้วและเปิดให้ใช้ (ยังไม่หักกลุ่มที่ปิด — ใช้ menu_enabled) */
function active_menus()
{
    return array('dashboard.php', 'store.php', 'sale.php', 'products.php', 'categories.php', 'receive.php', 'issue.php', 'stocktake.php', 'movements.php',
                 'history.php', 'return.php', 'report-sales.php',
                 'account.php', 'account-settings.php',
                 'adm-dashboard.php', 'adm-products.php', 'adm-categories.php', 'adm-receive.php', 'adm-issue.php', 'adm-return.php', 'adm-history.php',
                 'adm-movements.php', 'adm-report.php', 'adm-report-daily.php', 'adm-report-branch.php', 'adm-report-staff.php', 'adm-report-products.php', 'adm-users.php', 'adm-user-add.php', 'adm-branches.php',
                 'adm-notify.php');
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

/** เมนูนี้เปิดให้ใช้ไหม — ต้องมีในระบบ และกลุ่มของเมนูไม่ได้ถูกปิดจากหลังบ้าน
    TODO:
      - [x] ช่วงที่ 9: หักหน้าของกลุ่มที่ปิด (feature_pages_off) */
function menu_enabled($file)
{
    return in_array($file, active_menus(), true) && !in_array($file, feature_pages_off(), true);
}

/* ---------- สิทธิ์การใช้งาน ---------- */

/** บทบาทในระบบ: คีย์ => array(name ชื่อ, scope ขอบเขตที่เห็น)
    TODO:
      - [x] ช่วงที่ 10: เปลี่ยนชื่อจาก demo_roles */
function roles_all()
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
        'category'      => array('group' => 'menu',  'label' => 'จัดการหมวดสินค้า (เพิ่ม / ลบ)',     'short' => 'หมวดสินค้า'),
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
                 'stocktake.php' => 'stocktake', 'history.php' => 'history', 'return.php' => 'refund',
                 'categories.php' => 'category');
    return isset($map[$file]) ? $map[$file] : '';
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
        return array_values(array_diff(array_keys(perm_list()), array('sale', 'receive', 'issue', 'stocktake', 'category')));
    }
    if (isset($user['role']) && $user['role'] !== 'staff') {
        return array();
    }
    /* อ่านจากทะเบียนพนักงานทุกครั้ง — ผู้ดูแลเปิด/ปิดสิทธิ์แล้วมีผลทันทีไม่ต้องเข้าระบบใหม่ */
    $all = users_all();
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
    (ใช้กับเอกสารของวันนี้ — เอกสารวันก่อนใช้ past_can_edit ที่เช็กสิทธิ์ backdate และจำนวนวันเพิ่ม) */
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

/* ---------- helper ---------- */
function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function role_name($code)
{
    $r = roles_all();
    return isset($r[$code]) ? $r[$code]['name'] : $code;
}

function role_scope($code)
{
    $r = roles_all();
    return isset($r[$code]) ? $r[$code]['scope'] : '-';
}

function url($path)
{
    return APP_BASE . '/' . ltrim($path, '/');
}

/** URL ของไฟล์ใน themes/aostock/assets/ (css, js, รูป)
    หน้าเว็บวิ่งผ่าน router ของ admweb ที่ /aostock/ จึงอ้าง assets/ แบบ relative ไม่ได้ — ต้องต่อจาก THEME_URL (inc.php)
    TODO:
      - [x] ใช้ THEME_URL ของ admweb · ไม่มี (เรียกนอก router) ใช้ APP_BASE แทน */
function asset_url($path)
{
    $base = defined('THEME_URL') ? THEME_URL : APP_BASE;
    return rtrim($base, '/') . '/assets/' . ltrim($path, '/');
}

function money($n)
{
    return number_format($n, 0);
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

function money2($n)
{
    return number_format($n, 2);
}

/** ชื่อวันแบบย่อ อา. จ. อ. … */
function thai_dow_short($ts)
{
    $d = array('อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.');
    return $d[(int) date('w', $ts)];
}

/**
 * ส่งไฟล์ CSV ให้ดาวน์โหลดแล้วจบการทำงาน — ใส่ BOM ให้ Excel อ่านภาษาไทยถูก
 * $head = array หัวคอลัมน์ · $rows = array ของ array (ค่าตัวเลขส่งเป็นตัวเลขดิบ ไม่ใส่ comma)
 */
function csv_send($filename, $head, $rows)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $head);
    foreach ($rows as $r) {
        fputcsv($out, $r);
    }
    fclose($out);
    exit;
}

/** ตัวเลขเงินสำหรับ CSV (ทศนิยม 2 ตำแหน่ง ไม่มี comma) */
function csv_money($n)
{
    return number_format((float) $n, 2, '.', '');
}

function thai_day_month($ts)
{
    return (int) date('j', $ts) . ' ' . thai_month_short($ts);
}

function cmp_total_desc($a, $b)
{
    if ($a['total'] === $b['total']) {
        return 0;
    }
    return ($a['total'] < $b['total']) ? 1 : -1;
}

function thai_month_full($ts)
{
    $m = array('', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
               'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม');
    return $m[(int) date('n', $ts)] . ' ' . (((int) date('Y', $ts)) + 543);
}
