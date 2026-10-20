<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/core.php
 * ROLE: ค่าคงที่ของระบบ · ตัวช่วยทั่วไป (escape, URL, เงิน, วันที่ไทย, CSV) · สวิตช์เมนู / บทบาท / สิทธิ์
 * DEPENDS: api.php (ตัวโหลด — ห้าม include ตรง)
 * TABLES: ao_stock_setting (สวิตช์เมนู ผ่าน stock_setting_get)
 * TODO:
 *   - [x] ประวัติการแก้ไขดู docs/CHANGELOG.md
 * ⚠ โหลดทุก request — มีได้แค่ define() + function (ห้าม echo / header / query ตอนโหลด)
 */

/* ---------- ค่าคงที่ของระบบ (ย้ายจาก themes/aostock/inc/config.php) ---------- */
define('APP_NAME',  'AOSTOCK');
define('APP_TITLE', 'ระบบบริหารสต๊อกสินค้า');
define('APP_OWNER', 'บริษัท เอโอซอฟต์ จำกัด');

/* path ของหน้า AOSTOCK = โฟลเดอร์ที่ติดตั้งเว็บ (เช่น /aostock) — มาจาก $webBase ใน fix.<โดเมน>.php ผ่าน URL_WEB_ROOT */
define('APP_BASE', defined('URL_WEB_ROOT') ? rtrim((string) parse_url(URL_WEB_ROOT, PHP_URL_PATH), '/') : '');

/* ---------- สวิตช์เมนู / บทบาท: active_* = ที่ระบบมี · feature_groups = กลุ่มที่ปิดได้จากหลังบ้าน (setting features_off) ---------- */

/** บทบาทที่เปิดใช้งานอยู่ตอนนี้ — ฝ่ายบัญชีปิดได้จากหลังบ้าน (กลุ่ม account) */
function active_roles()
{
    $roles = array('staff', 'admin', 'account');   // ไม่มีหัวหน้าคลัง — ใช้สิทธิ์เสริมของพนักงานแทน
    return feature_group_on('account') ? $roles : array('staff', 'admin');
}

/** กลุ่มฟีเจอร์ที่เปิด–ปิดได้จากหลังบ้าน: คีย์ => array(label, hint, pages = หน้าที่ปิดตาม, role = บทบาทที่ปิดตาม) */
function feature_groups()
{
    return array(
        'refund'    => array('label' => 'รับคืนสินค้า', 'hint' => 'พนักงานรับคืนสินค้า / คืนเงินสด และหน้าตรวจใบรับคืนของผู้ดูแล',
                             'pages' => array('return.php', 'adm-return.php')),
        'stocktake' => array('label' => 'ตรวจนับสต๊อก', 'hint' => 'หน้าตรวจนับของพนักงาน และงานค้าง "ตรวจนับรอบนี้ยังไม่ครบ"',
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

/** บันทึกกลุ่มที่ปิด — คืนรายการที่เปลี่ยน array(คีย์ => true เปิด | false ปิด) */
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
    return array('dashboard.php', 'store.php', 'sale.php', 'products.php', 'product-add.php', 'categories.php', 'receive.php', 'issue.php', 'stocktake.php', 'movements.php',
                 'history.php', 'return.php', 'report-sales.php', 'team.php',
                 'account.php', 'account-settings.php',
                 'adm-dashboard.php', 'adm-products.php', 'adm-product-edit.php', 'adm-categories.php', 'adm-receive.php', 'adm-issue.php', 'adm-return.php', 'adm-history.php',
                 'adm-movements.php', 'adm-report.php', 'adm-report-daily.php', 'adm-report-branch.php', 'adm-report-staff.php', 'adm-report-products.php', 'adm-users.php', 'adm-user-add.php', 'adm-branches.php',
                 'adm-notify.php');
}

/** ส่วนประกอบที่ยังไม่ใช้ ('search' · 'branch_pick') — เปิดโดยเติมชื่อ */
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

/** เมนูนี้เปิดให้ใช้ไหม — ต้องมีในระบบ และกลุ่มของเมนูไม่ได้ถูกปิดจากหลังบ้าน */
function menu_enabled($file)
{
    return in_array($file, active_menus(), true) && !in_array($file, feature_pages_off(), true);
}

/* ---------- สิทธิ์การใช้งาน ---------- */

/** บทบาทในระบบ: คีย์ => array(name ชื่อ, scope ขอบเขตที่เห็น) */
function roles_all()
{
    return array(
        'admin'   => array('name' => 'ผู้ดูแลระบบ', 'scope' => 'ทุกสาขา'),
        'account' => array('name' => 'ฝ่ายบัญชี',   'scope' => 'ทุกสาขา · ดูบิลและเงินเข้า'),
        'staff'   => array('name' => 'พนักงาน',    'scope' => 'เฉพาะสาขาตนเอง'),
    );
}

/* ---------- สิทธิ์ของพนักงาน: perms = CSV ขึ้นต้น PERM_VER · needs = ต้องมีตัวใดตัวหนึ่งด้วย · เช็กที่เซิร์ฟเวอร์เสมอ ---------- */
define('PERM_VER', 'v2');

/** รายการสิทธิ์ทั้งหมด เรียงตามที่แสดงบนหน้าติ๊กสิทธิ์ */
function perm_list()
{
    return array(
        'sale'          => array('group' => 'shop',     'label' => 'ขายสินค้า (ตะกร้า ชำระเงิน พิมพ์บิล)', 'short' => 'ขายสินค้า'),
        'discount'      => array('group' => 'shop',     'label' => 'ให้ส่วนลดท้ายบิล (แก้ยอดที่ต้องชำระ)', 'short' => 'ให้ส่วนลด', 'needs' => array('sale')),
        'store'         => array('group' => 'shop',     'label' => 'เปิด / ปิดร้าน',                    'short' => 'เปิด / ปิดร้าน'),
        'store_reopen'  => array('group' => 'shop',     'label' => 'เปิดร้านอีกครั้งหลังปิดแล้ว',          'short' => 'เปิดร้านอีกครั้ง', 'needs' => array('store')),
        'cash'          => array('group' => 'shop',     'label' => 'เงินเข้า / ออกลิ้นชัก',              'short' => 'เงินเข้า / ออก'),
        'bill_fix'      => array('group' => 'shop',     'label' => 'แก้ / ยกเลิกบิลของตัวเอง (วันนี้)',     'short' => 'แก้บิลตัวเอง', 'needs' => array('sale')),
        'receive'       => array('group' => 'stock',    'label' => 'นำเข้าสินค้า (รับเข้าสต๊อก)',          'short' => 'นำเข้าสินค้า'),
        'product_add'   => array('group' => 'stock',    'label' => 'เพิ่มสินค้าใหม่ (จากหน้านำเข้า)',       'short' => 'เพิ่มสินค้าใหม่', 'needs' => array('receive')),
        'issue'         => array('group' => 'stock',    'label' => 'เบิก / ตัดออกสินค้า',                'short' => 'เบิก / ตัดออก'),
        'stocktake'     => array('group' => 'stock',    'label' => 'ตรวจนับสต๊อก',                      'short' => 'ตรวจนับ'),
        'doc_fix'       => array('group' => 'stock',    'label' => 'แก้ / ยกเลิกเอกสารคลังของตัวเอง (วันนี้)', 'short' => 'แก้เอกสารคลังตัวเอง',
                                 'needs' => array('receive', 'issue', 'stocktake')),
        'refund'        => array('group' => 'refund',   'label' => 'รับคืนสินค้า',                      'short' => 'รับคืนสินค้า'),
        'refund_cash'   => array('group' => 'refund',   'label' => 'คืนเงินสดให้ลูกค้า',                 'short' => 'คืนเงินสด', 'needs' => array('refund')),
        'category_add'  => array('group' => 'category', 'label' => 'เพิ่มหมวดสินค้า',                    'short' => 'เพิ่มหมวด'),
        'category_del'  => array('group' => 'category', 'label' => 'ลบหมวดสินค้า',                      'short' => 'ลบหมวด'),
        'products'      => array('group' => 'view',     'label' => 'ดูสินค้าในสต๊อก',                    'short' => 'สินค้าในสต๊อก'),
        'report'        => array('group' => 'view',     'label' => 'ดูรายงานยอดขายของตัวเอง',            'short' => 'รายงานยอดขาย'),
        /* ประวัติของสาขาแยกสิทธิ์รายเมนู (คีย์ของกลุ่มอยู่ใน Stock_getLogGroups) — เดิม history ตัวเดียว */
        'hist_sale'     => array('group' => 'history',  'label' => 'ดูประวัติขาย / ตั้งค่า',              'short' => 'ประวัติขาย'),       // ตั้งค่าเห็นเฉพาะผู้จัดการ (คำอธิบายใน perm_boxes)
        'hist_receive'  => array('group' => 'history',  'label' => 'ดูประวัตินำเข้าสินค้า',               'short' => 'ประวัตินำเข้า'),
        'hist_issue'    => array('group' => 'history',  'label' => 'ดูประวัติเบิก / ตัดออก',              'short' => 'ประวัติตัดออก'),
        'hist_count'    => array('group' => 'history',  'label' => 'ดูประวัติตรวจนับสต๊อก',              'short' => 'ประวัติตรวจนับ'),
        'hist_return'   => array('group' => 'history',  'label' => 'ดูประวัติรับคืนสินค้า',               'short' => 'ประวัติรับคืน'),
        'hist_store'    => array('group' => 'history',  'label' => 'ดูประวัติเปิด / ปิดร้าน และเงินเข้า / ออก', 'short' => 'ประวัติเปิด / ปิดร้าน'),
        'movements'     => array('group' => 'history',  'label' => 'ดูประวัติเคลื่อนไหวรายสินค้า',          'short' => 'เคลื่อนไหวรายสินค้า'),     // short ตรงกับชื่อเมนู
        'void_others'   => array('group' => 'extra',    'label' => 'แก้/ยกเลิกเอกสารของคนอื่นในสาขา',     'short' => 'แก้งานคนอื่น'),
        'backdate'      => array('group' => 'extra',    'label' => 'แก้/ยกเลิกเอกสารย้อนหลัง',           'short' => 'แก้ย้อนหลัง'),
        'report_branch' => array('group' => 'extra',    'label' => 'ดูรายงานยอดขายทั้งสาขา',            'short' => 'รายงานทั้งสาขา', 'needs' => array('report')),
        'manager'       => array('group' => 'extra',    'label' => 'ผู้จัดการสาขา',                     'short' => 'ผู้จัดการสาขา'),
    );
}

/** สิทธิ์ที่ผู้จัดการสาขาได้อัตโนมัติ (ดูข้อมูล · ดูประวัติ · สิทธิ์เสริม) */
function perm_manager_implies()
{
    return array_merge(array('products', 'report', 'movements'), Stock_getHistoryPerms(), array('void_others', 'backdate', 'report_branch'));
}

/** สิทธิ์ดูประวัติรายเมนู 6 ตัว (แทนคีย์เดิม history) */
function Stock_getHistoryPerms()
{
    return array('hist_sale', 'hist_receive', 'hist_issue', 'hist_count', 'hist_return', 'hist_store');
}

/** สิทธิ์ที่ผู้จัดการติ๊กให้ลูกน้องได้ = ทุกหมวดยกเว้นสิทธิ์เสริม */
function perm_manager_grantable()
{
    return array_keys(array_filter(perm_list(), function ($p) { return $p['group'] !== 'extra'; }));
}

/** เป็นผู้จัดการสาขาไหม (พนักงานที่ผู้ดูแลติ๊กสิทธิ์ manager — สาขาหนึ่งมีได้หลายคน) */
function is_branch_manager($user)
{
    return $user && isset($user['role']) && $user['role'] === 'staff' && can($user, 'manager');
}

/** ชื่อบทบาทที่แสดงใต้ชื่อ (แถบบน / หน้าเข้าระบบ) — ผู้จัดการสาขาแยกจากพนักงาน */
function user_role_label($user)
{
    return is_branch_manager($user) ? 'ผู้จัดการสาขา' : role_name($user['role']);
}

/** หมวดของสิทธิ์ (หัวข้อบนหน้าติ๊กสิทธิ์) — extra = สิทธิ์เสริมที่ต้องไว้ใจ */
function perm_groups()
{
    return array(
        'shop'     => 'หน้าร้าน',
        'stock'    => 'งานคลัง',
        'refund'   => 'รับคืนสินค้า',
        'category' => 'หมวดสินค้า',
        'view'     => 'ดูข้อมูล',
        'history'  => 'ดูประวัติ',                     // 1 สิทธิ์ = 1 เมนูประวัติ
        'extra'    => 'สิทธิ์เสริม (ให้เฉพาะคนที่ไว้ใจ)',
    );
}

/** สิทธิ์เริ่มต้นของพนักงานใหม่ */
function perm_default()
{
    return array_merge(array('sale', 'discount', 'store', 'cash', 'bill_fix', 'receive', 'issue', 'stocktake', 'doc_fix',
                             'products', 'movements', 'report'), Stock_getHistoryPerms());       // history → 6 ตัว (เท่าเดิม)
}

/** เรียงตาม perm_list · ตัดคีย์ที่ไม่รู้จัก · ตัดสิทธิ์ที่ขาดตัวที่ต้องมี (needs) ออก */
function perm_clean($keys)
{
    $keys = array_map('strval', (array) $keys);
    if (in_array('history', $keys, true)) {
        $keys = array_merge($keys, Stock_getHistoryPerms());
    }
    $out  = array();
    foreach (perm_list() as $k => $p) {
        if (in_array($k, $keys, true)) {
            $out[] = $k;
        }
    }
    $pl = perm_list();
    return array_values(array_filter($out, function ($k) use ($pl, $out) {
        return !isset($pl[$k]['needs']) || (bool) array_intersect($pl[$k]['needs'], $out);
    }));
}

/** สิทธิ์ชุดเดิม (ไม่มี PERM_VER) → ชุดใหม่ ทำได้เท่าเดิม */
function perm_from_legacy($old)
{
    $map = array(
        'sale'      => array('sale', 'discount', 'store', 'cash', 'bill_fix'),
        'receive'   => array('receive', 'doc_fix'),
        'issue'     => array('issue', 'doc_fix'),
        'stocktake' => array('stocktake', 'doc_fix'),
        'refund'    => array('refund', 'refund_cash'),
        'category'  => array('category_add', 'category_del'),
    );
    $out = array('products', 'movements', 'report');
    foreach ((array) $old as $k) {
        $out = array_merge($out, isset($map[$k]) ? $map[$k] : array($k));
    }
    return perm_clean($out);
}

/** สิทธิ์ที่ต้องมีเพื่อเปิดหน้านั้น — '' = ไม่ต้องมี · array = มีตัวใดตัวหนึ่งก็พอ */
function page_perm($file)
{
    $map = array('sale.php' => 'sale', 'store.php' => array('store', 'store_reopen', 'cash'),
                 'receive.php' => 'receive', 'issue.php' => 'issue', 'stocktake.php' => 'stocktake',
                 'history.php' => Stock_getHistoryPerms(), 'return.php' => 'refund', 'categories.php' => array('category_add', 'category_del'),
                 'products.php' => 'products', 'product-add.php' => 'product_add', 'movements.php' => 'movements', 'report-sales.php' => 'report',
                 'team.php' => 'manager');
    return isset($map[$file]) ? $map[$file] : '';
}

/** เปิดหน้านี้ได้ไหมตามสิทธิ์ (ไม่ดูเมนูที่ถูกปิด — ลิงก์ใช้ page_ok) */
function page_perm_ok($user, $file)
{
    $need = page_perm($file);
    if ($need === '') {
        return true;
    }
    foreach ((array) $need as $p) {
        if (can($user, $p)) {
            return true;
        }
    }
    return false;
}

/** ลิงก์ไปหน้านี้ควรแสดงไหม — เมนูเปิดอยู่ และผู้ใช้มีสิทธิ์ */
function page_ok($user, $file)
{
    return menu_enabled($file) && page_perm_ok($user, $file);
}

/** สิทธิ์เสริมที่ผู้ใช้คนนี้มี */
function user_perms($user)
{
    if (!$user) {
        return array();
    }
    if (isset($user['role']) && $user['role'] === 'admin') {
        /* ผู้ดูแลไม่ทำงานหน้าร้าน / คลัง / หมวดฝั่งพนักงาน — ตรวจจากหน้า adm- แทน */
        return array_values(array_diff(array_keys(perm_list()), array(
            'sale', 'discount', 'store', 'store_reopen', 'cash', 'bill_fix',
            'receive', 'product_add', 'issue', 'stocktake', 'doc_fix', 'category_add', 'category_del',
        )));
    }
    if (isset($user['role']) && $user['role'] !== 'staff') {
        return array();
    }
    /* อ่านจากทะเบียนพนักงานทุกครั้ง — ผู้ดูแลเปิด/ปิดสิทธิ์แล้วมีผลทันทีไม่ต้องเข้าระบบใหม่ */
    $all = users_all();
    if (isset($user['username']) && isset($all[$user['username']])) {
        $u = $all[$user['username']];
        $p = (isset($u['perms']) && is_array($u['perms'])) ? $u['perms'] : array();
    } else {
        $p = (isset($user['perms']) && is_array($user['perms'])) ? $user['perms'] : array();
    }
    /* ผู้จัดการสาขาได้สิทธิ์ดูข้อมูล + สิทธิ์เสริมทุกตัวอัตโนมัติ */
    return in_array('manager', $p, true) ? perm_clean(array_merge($p, perm_manager_implies())) : $p;
}

function can($user, $perm)
{
    return in_array($perm, user_perms($user), true);
}

/** แก้ / ยกเลิกเอกสารวันนี้ได้ไหม — $kind = 'bill' | 'doc' */
function can_void_doc($user, $doc, $kind = 'doc')
{
    if (!$user || !$doc) {
        return false;
    }
    if (can($user, 'void_others')) {
        return true;
    }
    return isset($doc['by_user']) && $doc['by_user'] === $user['username'] && can($user, $kind === 'bill' ? 'bill_fix' : 'doc_fix');
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

/** URL ไฟล์ใน themes/aostock/assets/ (ต่อจาก THEME_URL — router อ้าง relative ไม่ได้) */
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

/** ส่ง CSV ให้ดาวน์โหลดแล้วจบ (มี BOM ให้ Excel อ่านไทยได้) */
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
