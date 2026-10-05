<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/core.php
 * ROLE: ค่าคงที่ของระบบ · ตัวช่วยทั่วไป (escape, URL, เงิน, วันที่ไทย, CSV) · สวิตช์เมนู / บทบาท / สิทธิ์
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_setting (สวิตช์เมนู ผ่าน stock_setting_get)
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *   - [x] ช่วงที่ 11: แตกสิทธิ์พนักงานเป็น 18 ตัว + สิทธิ์เสริม 3 ตัว · สิทธิ์ที่พ่วงกัน (needs) · แปลงสิทธิ์ชุดเดิม · page_ok สำหรับเมนู / ลิงก์
 *   - [x] ช่วงที่ 12: สิทธิ์ผู้จัดการสาขา (manager) ได้สิทธิ์ดูข้อมูล + สิทธิ์เสริมอัตโนมัติ · หน้า team.php · ชื่อบทบาท "ผู้จัดการสาขา"
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
                 'history.php', 'return.php', 'report-sales.php', 'team.php',
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

/* ---------- สิทธิ์ของพนักงาน (ช่วงที่ 11: แตกให้ละเอียด) ----------
   ผู้ดูแลติ๊กให้รายคน · เก็บในคอลัมน์ ao_stock_staff.perms คั่นด้วย , ขึ้นต้นด้วยตัวบอกรุ่น PERM_VER
   แถวที่ยังไม่มีตัวบอกรุ่น = สิทธิ์ชุดเดิม 7 ตัว → แปลงเป็นชุดใหม่ตอนอ่าน (perm_from_legacy) ไม่ต้อง Reinstall
   group = หมวดบนหน้าติ๊กสิทธิ์ (perm_groups)
   needs = ต้องมีสิทธิ์ตัวใดตัวหนึ่งในนี้ด้วย — ไม่มีแล้วสิทธิ์ตัวนี้หลุดตามเอง (perm_clean)
   ทุกสิทธิ์เช็กที่เซิร์ฟเวอร์ ไม่ใช่แค่ซ่อนปุ่ม                                      */
define('PERM_VER', 'v2');

/** รายการสิทธิ์ทั้งหมด เรียงตามที่แสดงบนหน้าติ๊กสิทธิ์
    TODO:
      - [x] ช่วงที่ 11: แตกสิทธิ์เมนู 7 ตัวเป็น 18 ตัว (หน้าร้าน / งานคลัง / รับคืน / หมวดสินค้า / ดูข้อมูล) + สิทธิ์เสริม 3 ตัวเดิม */
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
        'issue'         => array('group' => 'stock',    'label' => 'เบิก / ตัดออกสินค้า',                'short' => 'เบิก / ตัดออก'),
        'stocktake'     => array('group' => 'stock',    'label' => 'ตรวจนับ / ปรับยอด',                 'short' => 'ตรวจนับ'),
        'doc_fix'       => array('group' => 'stock',    'label' => 'แก้ / ยกเลิกเอกสารคลังของตัวเอง (วันนี้)', 'short' => 'แก้เอกสารคลังตัวเอง',
                                 'needs' => array('receive', 'issue', 'stocktake')),
        'refund'        => array('group' => 'refund',   'label' => 'รับคืนสินค้า',                      'short' => 'รับคืนสินค้า'),
        'refund_cash'   => array('group' => 'refund',   'label' => 'คืนเงินสดให้ลูกค้า',                 'short' => 'คืนเงินสด', 'needs' => array('refund')),
        'category_add'  => array('group' => 'category', 'label' => 'เพิ่มหมวดสินค้า',                    'short' => 'เพิ่มหมวด'),
        'category_del'  => array('group' => 'category', 'label' => 'ลบหมวดสินค้า',                      'short' => 'ลบหมวด'),
        'products'      => array('group' => 'view',     'label' => 'ดูสินค้าในสต๊อก',                    'short' => 'สินค้าในสต๊อก'),
        'movements'     => array('group' => 'view',     'label' => 'ดูประวัติเคลื่อนไหวรายสินค้า',          'short' => 'ประวัติเคลื่อนไหว'),
        'history'       => array('group' => 'view',     'label' => 'ดูประวัติการทำรายการของสาขา',         'short' => 'ประวัติรายการ'),
        'report'        => array('group' => 'view',     'label' => 'ดูรายงานยอดขายของตัวเอง',            'short' => 'รายงานยอดขาย'),
        'void_others'   => array('group' => 'extra',    'label' => 'แก้/ยกเลิกเอกสารของคนอื่นในสาขา',     'short' => 'แก้งานคนอื่น'),
        'backdate'      => array('group' => 'extra',    'label' => 'แก้/ยกเลิกเอกสารย้อนหลัง',           'short' => 'แก้ย้อนหลัง'),
        'report_branch' => array('group' => 'extra',    'label' => 'ดูรายงานยอดขายทั้งสาขา',            'short' => 'รายงานทั้งสาขา', 'needs' => array('report')),
        'manager'       => array('group' => 'extra',    'label' => 'ผู้จัดการสาขา',                     'short' => 'ผู้จัดการสาขา'),
    );
}

/** สิทธิ์ที่ผู้จัดการสาขาได้อัตโนมัติ (ไม่ต้องติ๊กซ้ำ) — ดูข้อมูลทุกอย่าง + สิทธิ์เสริมทุกตัว
    งานหน้าร้าน / งานคลัง / รับคืน / หมวด ยังเป็นไปตามที่ผู้ดูแลติ๊กให้รายคน
    TODO:
      - [x] ช่วงที่ 12: ผู้จัดการสาขา (ข้อ 1ก 2ก) */
function perm_manager_implies()
{
    return array('products', 'movements', 'history', 'report', 'void_others', 'backdate', 'report_branch');
}

/** สิทธิ์ที่ผู้จัดการสาขาติ๊กให้พนักงานในสาขาได้ = ทุกหมวดยกเว้นสิทธิ์เสริม (สิทธิ์เสริม + ตั้งผู้จัดการ เป็นของผู้ดูแล)
    TODO:
      - [x] ช่วงที่ 12 */
function perm_manager_grantable()
{
    return array_keys(array_filter(perm_list(), function ($p) { return $p['group'] !== 'extra'; }));
}

/** เป็นผู้จัดการสาขาไหม (พนักงานที่ผู้ดูแลติ๊กสิทธิ์ manager — สาขาหนึ่งมีได้หลายคน)
    TODO:
      - [x] ช่วงที่ 12 */
function is_branch_manager($user)
{
    return $user && isset($user['role']) && $user['role'] === 'staff' && can($user, 'manager');
}

/** ชื่อบทบาทที่แสดงใต้ชื่อ (แถบบน / หน้าเข้าระบบ) — ผู้จัดการสาขาแยกจากพนักงาน
    TODO:
      - [x] ช่วงที่ 12 */
function user_role_label($user)
{
    return is_branch_manager($user) ? 'ผู้จัดการสาขา' : role_name($user['role']);
}

/** หมวดของสิทธิ์ (หัวข้อบนหน้าติ๊กสิทธิ์) — extra = สิทธิ์เสริมที่ต้องไว้ใจ
    TODO:
      - [x] ช่วงที่ 11 */
function perm_groups()
{
    return array(
        'shop'     => 'หน้าร้าน',
        'stock'    => 'งานคลัง',
        'refund'   => 'รับคืนสินค้า',
        'category' => 'หมวดสินค้า',
        'view'     => 'ดูข้อมูล',
        'extra'    => 'สิทธิ์เสริม (ให้เฉพาะคนที่ไว้ใจ)',
    );
}

/** สิทธิ์เริ่มต้นของพนักงานที่เพิ่มใหม่ — เท่ากับที่พนักงานใหม่ได้ก่อนช่วงที่ 11
    (เปิดร้านอีกครั้ง / รับคืน / หมวดสินค้า / สิทธิ์เสริม ไม่ติ๊กให้ — ผู้ดูแลติ๊กเองรายคน)
    TODO:
      - [x] ช่วงที่ 11: ชุดใหม่ (ข้อ 3ก) */
function perm_default()
{
    return array('sale', 'discount', 'store', 'cash', 'bill_fix', 'receive', 'issue', 'stocktake', 'doc_fix',
                 'products', 'movements', 'history', 'report');
}

/** เรียงตาม perm_list · ตัดคีย์ที่ไม่รู้จัก · ตัดสิทธิ์ที่ขาดตัวที่ต้องมี (needs) ออก
    TODO:
      - [x] ช่วงที่ 11: ใช้ทั้งตอนอ่านจากฐานข้อมูลและตอนบันทึกจากฟอร์ม */
function perm_clean($keys)
{
    $keys = array_map('strval', (array) $keys);
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

/** สิทธิ์ชุดเดิม (ก่อนช่วงที่ 11) → ชุดใหม่ ให้ทำได้เท่าเดิมทุกอย่าง ไม่มีใครเสียสิทธิ์
      sale     → ขาย ส่วนลด เปิด–ปิดร้าน เงินเข้าออก แก้บิลตัวเอง (เปิดร้านอีกครั้งเดิมทำได้เฉพาะผู้ดูแล → ไม่ให้)
      receive / issue / stocktake → + แก้เอกสารคลังตัวเอง · refund → + คืนเงินสด · category → เพิ่ม + ลบหมวด
      ทุกคน    → ดูสินค้า / ประวัติเคลื่อนไหว / รายงานของตัวเอง (เดิมเปิดได้ทุกคน)
    TODO:
      - [x] ช่วงที่ 11 */
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

/** สิทธิ์ที่ต้องมีเพื่อเปิดหน้านั้น — '' = ไม่ต้องมี · array = มีตัวใดตัวหนึ่งก็พอ
    TODO:
      - [x] ช่วงที่ 11: หน้าสินค้า / ประวัติเคลื่อนไหว / รายงาน มีสิทธิ์ของตัวเอง · หน้าร้านเข้าได้ถ้ามีงานใดงานหนึ่งในหน้านั้น */
function page_perm($file)
{
    $map = array('sale.php' => 'sale', 'store.php' => array('store', 'store_reopen', 'cash'),
                 'receive.php' => 'receive', 'issue.php' => 'issue', 'stocktake.php' => 'stocktake',
                 'history.php' => 'history', 'return.php' => 'refund', 'categories.php' => array('category_add', 'category_del'),
                 'products.php' => 'products', 'movements.php' => 'movements', 'report-sales.php' => 'report',
                 'team.php' => 'manager');
    return isset($map[$file]) ? $map[$file] : '';
}

/** ผู้ใช้คนนี้เปิดหน้านี้ได้ไหม (ไม่สนว่าเมนูถูกปิดจากหลังบ้านหรือเปล่า — ใช้ page_ok สำหรับลิงก์) */
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

/** ลิงก์ไปหน้านี้ควรแสดงไหม — เมนูเปิดอยู่ และผู้ใช้มีสิทธิ์
    TODO:
      - [x] ช่วงที่ 11: ใช้กับเมนูด้านข้างและปุ่มลิงก์ระหว่างหน้า */
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
        /* ผู้ดูแลได้ทุกสิทธิ์ ยกเว้นงานหน้าร้าน งานคลัง และหมวดสินค้าฝั่งพนักงาน (หน้าที่ของพนักงานที่ได้รับมอบหมาย)
           ผู้ดูแลตรวจสอบงานพวกนี้จากหน้าชุด adm- แทน */
        return array_values(array_diff(array_keys(perm_list()), array(
            'sale', 'discount', 'store', 'store_reopen', 'cash', 'bill_fix',
            'receive', 'issue', 'stocktake', 'doc_fix', 'category_add', 'category_del',
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
    /* ผู้จัดการสาขาได้สิทธิ์ดูข้อมูล + สิทธิ์เสริมทุกตัวอัตโนมัติ (ช่วงที่ 12) */
    return in_array('manager', $p, true) ? perm_clean(array_merge($p, perm_manager_implies())) : $p;
}

function can($user, $perm)
{
    return in_array($perm, user_perms($user), true);
}

/** แก้/ยกเลิกเอกสารใบนี้ได้ไหม — ของตัวเองต้องมีสิทธิ์ bill_fix (บิล) / doc_fix (เอกสารคลัง) · ของคนอื่นต้องมีสิทธิ์ void_others
    $kind = 'bill' บิลขาย · 'doc' ใบรับเข้า / เบิก / ตรวจนับ
    (ใช้กับเอกสารของวันนี้ — เอกสารวันก่อนใช้ past_can_edit ที่เช็กสิทธิ์ backdate และจำนวนวันเพิ่ม)
    TODO:
      - [x] ช่วงที่ 11: ของตัวเองต้องมีสิทธิ์แก้บิล / แก้เอกสารคลังของตัวเอง (เดิมได้เสมอ) */
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
