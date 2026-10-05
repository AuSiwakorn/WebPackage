<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/branch-staff.php
 * ROLE: สาขา · พนักงาน / ผู้ดูแล / ฝ่ายบัญชี · ค่าตั้งของสาขา · PIN · ตัวช่วยของหน้าจัดการสาขา / พนักงาน
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_branch, ao_stock_staff, ao_stock_staff_branch, ao_stock_remember (ลบตอนลบพนักงาน) · ตาราง ao_stock_* อื่น (เช็กก่อนลบสาขา / พนักงาน)
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *   - [x] ช่วงที่ 11: สิทธิ์ในคอลัมน์ perms มีตัวบอกรุ่น (perm_csv) · แปลงสิทธิ์ชุดเดิมตอนอ่าน · ช่องติ๊กสิทธิ์แบ่งหมวด · สรุป / ประวัติการเปลี่ยนสิทธิ์
 *   - [x] ช่วงที่ 12: งานจัดการพนักงานใช้ร่วมกัน (staff_act_*) ระหว่างผู้ดูแลกับผู้จัดการสาขา · ยืนยัน PIN ของผู้จัดการ · ประวัติชนิด category ไม่นับเป็นข้อมูลสาขา
 *   - [x] ช่วงที่ 13: ชื่อผู้ใช้ของพนักงานต้องกรอก (staff_username_error — เลิกตั้ง staffN ให้อัตโนมัติ) · เปลี่ยนชื่อผู้ใช้ได้ (staff_act_save + staff_rename)
 *
 * ⚠ ไฟล์นี้ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb — มีได้แค่ define() และประกาศ function (ห้าม echo / header / query ตอนโหลด)
 */

/* ---------- สาขา (ตาราง ao_stock_branch) ----------
   ผู้ดูแลเพิ่ม / แก้ไข / ปิดใช้งาน / ลบ ได้ที่หน้า "จัดการสาขา" (adm-branches.php)
   - รหัสสาขา (code เช่น HQ, RS) คือตัวอ้างอิงหลักในหน้าเว็บ — ตั้งตอนเพิ่มแล้วเปลี่ยนไม่ได้
   - ปิดใช้งาน = is_active 0 (ประวัติ บิล รายงานยังอยู่) · ลบได้เฉพาะสาขาที่ยังไม่มีข้อมูล
   - อ่านทั้งตารางครั้งเดียวต่อ request (static) — function ที่เขียนเรียก branch_db_rows(true) ล้าง cache ให้เอง */

/** ทุกแถวของ ao_stock_branch เรียงตาม sort — array( รหัส => แถว )
    TODO:
      - [x] cache ต่อ request · $reset = true ล้าง cache หลังเขียน */
function branch_db_rows($reset = false)
{
    static $rows = null;
    if ($reset) {
        $rows = null;
        return array();
    }
    if ($rows === null) {
        $rows = array();
        foreach (sdb_rows('SELECT * FROM ' . sdb_tb('branch') . ' ORDER BY sort, branch_id') as $r) {
            $rows[$r['code']] = $r;
        }
    }
    return $rows;
}

/** แถวของสาขาเดียว หรือ null */
function branch_row($code)
{
    $rows = branch_db_rows();
    return isset($rows[$code]) ? $rows[$code] : null;
}

/** ทุกสาขา รวมที่ปิดใช้งาน — ใช้ตอนต้องแสดงประวัติ / ชื่อสาขาเก่า
    TODO:
      - [x] อ่านจาก ao_stock_branch (เดิม demo_branches_all) */
function branches_all()
{
    $out = array();
    foreach (branch_db_rows() as $c => $r) {
        $out[$c] = array(
            'id'      => (int) $r['branch_id'],
            'name'    => $r['name'],
            'short'   => ($r['short_name'] !== '') ? $r['short_name'] : $r['name'],
            'address' => $r['address'],
            'phone'   => $r['phone'],
            'active'  => ((int) $r['is_active'] === 1),
        );
    }
    return $out;
}

/** สาขาที่เปิดใช้งาน — ใช้ทั่วไป (ตัวเลือกสาขา ย้ายพนักงาน ภาพรวม)
    TODO:
      - [x] เดิม demo_branches */
function branches_active()
{
    $out = array();
    foreach (branches_all() as $c => $b) {
        if ($b['active']) {
            $out[$c] = $b;
        }
    }
    return $out;
}

/** เพิ่มสาขาใหม่ — $info: name short address phone · $settings: ค่าตั้ง 4 ค่า (branch_setting_rules)
    เลขที่บิลเริ่มต้น {รหัส}V / {รหัส} · เลขผู้เสียภาษีคัดลอกจากสาขาแรก · เลขที่สาขา (ภาษี) = สูงสุด + 1
    TODO:
      - [x] INSERT ao_stock_branch ใน transaction · คืน branch_id */
function branch_create($code, $info, $settings)
{
    return sdb_tx(function () use ($code, $info, $settings) {
        $first  = sdb_row('SELECT tax_id FROM ' . sdb_tb('branch') . ' ORDER BY branch_id LIMIT 1');
        $taxNo  = (int) sdb_val('SELECT MAX(CAST(tax_branch AS UNSIGNED)) FROM ' . sdb_tb('branch'));
        $sort   = (int) sdb_val('SELECT MAX(sort) FROM ' . sdb_tb('branch'));
        $hasAny = ($first !== null);
        $row = array(
            'code'         => $code,
            'name'         => $info['name'],
            'short_name'   => $info['short'],
            'address'      => $info['address'],
            'phone'        => $info['phone'],
            'prefix_vat'   => $code . 'V',
            'prefix_novat' => $code,
            'tax_id'       => $hasAny ? $first['tax_id'] : '',
            'tax_branch'   => $hasAny ? str_pad((string) ($taxNo + 1), 5, '0', STR_PAD_LEFT) : '00000',
            'sort'         => $sort + 1,
        );
        $rules = branch_setting_rules();
        foreach ($settings as $k => $v) {
            if (isset($rules[$k])) {
                $row[$k] = max($rules[$k][0], min($rules[$k][1], (int) $v));
            }
        }
        $id = sdb_insert('branch', $row);
        branch_db_rows(true);
        return $id;
    });
}

/** แก้ชื่อ / ชื่อย่อ / ที่อยู่ / เบอร์โทรของสาขา — $info: name short address phone */
function branch_update_info($code, $info)
{
    sdb_update('branch', array(
        'name'       => $info['name'],
        'short_name' => $info['short'],
        'address'    => $info['address'],
        'phone'      => $info['phone'],
    ), array('code' => $code));
    branch_db_rows(true);
}

/** เปิด / ปิดใช้งานสาขา */
function branch_set_active($code, $on)
{
    sdb_update('branch', array('is_active' => $on ? 1 : 0), array('code' => $code));
    branch_db_rows(true);
}

/** ลบสาขา — เรียกหลังเช็ก branch_data_reason() แล้วเท่านั้น (สาขาที่ยังไม่มีข้อมูล)
    ประวัติชนิด setting ของสาขานี้ (เพิ่ม / แก้ / ปิด–เปิดสาขา) ลบไปด้วย ไม่ให้เหลือแถวที่ชี้สาขาที่ไม่มีแล้ว
    TODO:
      - [x] DELETE สาขา + ประวัติ setting ใน transaction */
function branch_delete($code)
{
    $id = branch_id_of($code);
    if ($id <= 0) {
        return;
    }
    sdb_tx(function () use ($id) {
        sdb_q('DELETE FROM ' . sdb_tb('log') . ' WHERE branch_id = ? AND type IN (\'setting\', \'category\')', array($id));
        sdb_q('DELETE FROM ' . sdb_tb('branch') . ' WHERE branch_id = ?', array($id));
    });
    branch_db_rows(true);
}

/* ---------- ค่าตั้งของสาขา (ผู้ดูแลแก้ได้ที่หน้า "จัดการสาขา") — คอลัมน์ใน ao_stock_branch ----------
   count_day        รอบตรวจนับเริ่มทุกวันที่เท่าไรของเดือน (1–28) รอบละ 1 เดือน
   backdate_days    แก้เอกสาร / รับคืนสินค้าย้อนหลังได้กี่วัน
   count_open_limit ระหว่างร้านเปิด นับได้ครั้งละกี่รายการ (0 = ไม่จำกัด)
   default_float    เงินทอนมาตรฐานของสาขา                                       */

/** ช่วงค่าที่ยอมให้ตั้ง: array(ต่ำสุด, สูงสุด) */
function branch_setting_rules()
{
    return array(
        'count_day'        => array(1, 28),
        'backdate_days'    => array(0, 60),
        'count_open_limit' => array(0, 50),
        'default_float'    => array(0, 100000),
        'daily_goal'       => array(0, 100000),     // เป้าชิ้นต่อคนต่อวัน (ช่วงที่ 10) · 0 = ไม่ตั้งเป้า
    );
}

function branch_setting($code, $key)
{
    $row = branch_row($code);
    $v   = ($row !== null && isset($row[$key])) ? $row[$key] : 0;
    $r   = branch_setting_rules();
    return isset($r[$key]) ? max($r[$key][0], min($r[$key][1], (int) $v)) : (int) $v;
}

function branch_setting_set($code, $key, $val)
{
    $r = branch_setting_rules();
    if (!isset($r[$key])) {
        return false;
    }
    sdb_update('branch', array($key => max($r[$key][0], min($r[$key][1], (int) $val))), array('code' => $code));
    branch_db_rows(true);
    return true;
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

/** ย้อนหลังได้ไม่เกินกี่วัน (สิทธิ์ backdate / refund) — ผู้ดูแลตั้งรายสาขา
    ระบบจริง: ao_stock_branch.backdate_days                                  */
function backdate_days($code = null)
{
    return $code === null ? 7 : branch_setting($code, 'backdate_days');
}

/* ---------- ผู้ใช้ระบบ (ตาราง ao_stock_staff + ao_stock_staff_branch) ----------
   - username คือตัวอ้างอิงหลักในหน้าเว็บ · staff_id ใช้ในเอกสาร (created_by)
   - พนักงาน (staff) เข้าด้วยชื่อผู้ใช้ + PIN 4 หลัก (เครื่องที่เคยเข้าแล้วแตะปุ่มชื่อได้ · ช่วงที่ 13) · ผู้ดูแล (admin) / ฝ่ายบัญชี (account) เข้าด้วยชื่อผู้ใช้ + รหัสผ่าน
   - PIN / รหัสผ่านเก็บเป็น password_hash() · กรอกผิด STAFF_LOCK_FAILS ครั้ง ล็อก STAFF_LOCK_MINUTES นาที (ต่อคน)
   - พนักงานจัดการที่หน้า "จัดการพนักงาน" ของ POS · ผู้ดูแล / บัญชี จัดการที่หลังบ้าน admweb (โมดูล stock → ผู้ดูแล POS)
   - ประวัติการประจำสาขาอยู่ใน ao_stock_staff_branch (แถวปัจจุบัน date_to = NULL) — ยอดเก่าผูกกับสาขาเดิมเสมอ */
define('STAFF_LOCK_FAILS', 5);
define('STAFF_LOCK_MINUTES', 15);

/** ทุกแถวของ ao_stock_staff (+ รหัสสาขา) — array( username => แถว ) · มี hash ด้วย ใช้ภายในเท่านั้น
    TODO:
      - [x] cache ต่อ request · $reset = true ล้าง cache หลังเขียน */
function staff_db_rows($reset = false)
{
    static $rows = null;
    if ($reset) {
        $rows = null;
        return array();
    }
    if ($rows === null) {
        $rows = array();
        $sql  = 'SELECT s.*, b.code AS branch_code FROM ' . sdb_tb('staff') . ' s'
              . ' LEFT JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = s.branch_id ORDER BY s.staff_id';
        foreach (sdb_rows($sql) as $r) {
            $rows[$r['username']] = $r;
        }
    }
    return $rows;
}

/** ประวัติการประจำสาขาของทุกคน — array( staff_id => array( array(branch, from, to|null), ... ) ) เรียงตามวันที่ */
function staff_db_history($reset = false)
{
    static $rows = null;
    if ($reset) {
        $rows = null;
        return array();
    }
    if ($rows === null) {
        $rows = array();
        $sql  = 'SELECT sb.staff_id, sb.date_from, sb.date_to, b.code FROM ' . sdb_tb('staff_branch') . ' sb'
              . ' JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = sb.branch_id ORDER BY sb.staff_id, sb.date_from, sb.sb_id';
        foreach (sdb_rows($sql) as $r) {
            $rows[(int) $r['staff_id']][] = array('branch' => $r['code'], 'from' => $r['date_from'], 'to' => $r['date_to']);
        }
    }
    return $rows;
}

/** ล้าง cache ของผู้ใช้หลังเขียน */
function staff_db_reset()
{
    staff_db_rows(true);
    staff_db_history(true);
}

/** สิทธิ์จากคอลัมน์ perms (คั่นด้วย ,) — เฉพาะที่มีใน perm_list เรียงตามลำดับเดิม
    TODO:
      - [x] ช่วงที่ 11: ไม่มีตัวบอกรุ่น PERM_VER = สิทธิ์ชุดเดิม → แปลงเป็นชุดใหม่ (perm_from_legacy) */
function staff_perms_csv($csv)
{
    $have = array_values(array_filter(array_map('trim', explode(',', (string) $csv)), 'strlen'));
    if (!in_array(PERM_VER, $have, true)) {
        return $have ? perm_from_legacy($have) : array();
    }
    return perm_clean($have);
}

/** สิทธิ์ → ค่าที่เก็บในคอลัมน์ perms (มีตัวบอกรุ่นนำหน้าเสมอ)
    TODO:
      - [x] ช่วงที่ 11 */
function perm_csv($perms)
{
    return implode(',', array_merge(array(PERM_VER), perm_clean($perms)));
}

/**
 * ผู้ใช้ทุกคน รวมที่พักงาน — array( username => ข้อมูล )
 *   id name role branch(รหัสสาขาปัจจุบัน) initials perms active
 *   since   = วันเริ่มประจำสาขาปัจจุบัน · history = ช่วงที่เคยอยู่สาขาอื่น array(branch, from, to)
 * ไม่มี hash ของ PIN / รหัสผ่าน (ดู staff_auth_row)
 * TODO:
 *   - [x] อ่านจาก ao_stock_staff + ao_stock_staff_branch (เดิม demo_users_all)
 */
function users_all()
{
    $hist = staff_db_history();
    $out  = array();
    foreach (staff_db_rows() as $k => $r) {
        $id    = (int) $r['staff_id'];
        $since = substr((string) $r['add_date'], 0, 10);
        $past  = array();
        foreach (isset($hist[$id]) ? $hist[$id] : array() as $h) {
            if ($h['to'] === null) {
                $since = $h['from'];
            } else {
                $past[] = $h;
            }
        }
        $out[$k] = array(
            'id'       => $id,
            'name'     => $r['name'],
            'role'     => $r['role'],
            'branch'   => ($r['branch_code'] !== null) ? $r['branch_code'] : '',
            'initials' => ($r['initials'] !== '') ? $r['initials'] : auto_initials($r['name']),
            'perms'    => staff_perms_csv($r['perms']),
            'active'   => ((int) $r['is_active'] === 1),
            'since'    => $since,
            'history'  => $past,
        );
    }
    return $out;
}

/** แถวที่ใช้ตรวจ PIN / รหัสผ่าน (มี hash + ตัวนับกรอกผิด) หรือ null */
function staff_auth_row($username)
{
    $rows = staff_db_rows();
    return isset($rows[$username]) ? $rows[$username] : null;
}

/** id ของสาขาจากรหัส (ไม่มี = 0) */
function branch_id_of($code)
{
    $row = branch_row($code);
    return ($row !== null) ? (int) $row['branch_id'] : 0;
}

/** เพิ่มผู้ใช้ — $d: username name initials role branch(รหัส) perms(array) และ pin (staff) หรือ password (admin / account)
    TODO:
      - [x] INSERT ao_stock_staff + แถวประจำสาขาใน transaction · คืน staff_id */
function staff_create($d)
{
    return sdb_tx(function () use ($d) {
        $isStaff = ($d['role'] === 'staff');
        $bid     = branch_id_of($d['branch']);
        $id = sdb_insert('staff', array(
            'branch_id'     => $bid,
            'username'      => $d['username'],
            'name'          => $d['name'],
            'initials'      => ($d['initials'] !== '') ? $d['initials'] : auto_initials($d['name']),
            'role'          => $d['role'],
            'perms'         => perm_csv(isset($d['perms']) ? $d['perms'] : array()),
            'pin_hash'      => $isStaff ? password_hash($d['pin'], PASSWORD_DEFAULT) : '',
            'pin_fp'        => $isStaff ? pin_fp($d['pin']) : null,
            'password_hash' => $isStaff ? '' : password_hash($d['password'], PASSWORD_DEFAULT),
            'is_active'     => 1,
        ));
        if ($bid > 0) {
            sdb_insert('staff_branch', array('staff_id' => $id, 'branch_id' => $bid, 'date_from' => date('Y-m-d')));
        }
        staff_db_reset();
        return $id;
    });
}

/** แก้ชื่อ / อักษรย่อ / สิทธิ์ */
function staff_update($username, $name, $initials, $perms)
{
    sdb_update('staff', array(
        'name'     => $name,
        'initials' => ($initials !== '') ? $initials : auto_initials($name),
        'perms'    => perm_csv($perms),
    ), array('username' => $username));
    staff_db_reset();
}

/** เปลี่ยนชื่อผู้ใช้ (ตรวจด้วย staff_username_error ก่อนเรียก) — เอกสาร / ประวัติ / การจดจำเครื่อง ผูกกับ staff_id จึงไม่ต้องแก้ที่อื่น
    คนที่กำลังเข้าระบบอยู่ด้วยชื่อเดิมถูกพาออกจากระบบที่หน้าถัดไป (require_login หาชื่อเดิมไม่เจอ) แล้วเข้าใหม่ด้วยชื่อใหม่
    TODO:
      - [x] ช่วงที่ 13 */
function staff_rename($username, $new)
{
    sdb_update('staff', array('username' => $new), array('username' => $username));
    staff_db_reset();
}

/** ตั้ง PIN ใหม่ (ปลดล็อกด้วย) */
function staff_set_pin($username, $pin)
{
    sdb_update('staff', array('pin_hash' => password_hash($pin, PASSWORD_DEFAULT), 'pin_fp' => pin_fp($pin), 'fail_count' => 0, 'locked_until' => null),
               array('username' => $username));
    staff_db_reset();
}

/** ตั้งรหัสผ่านใหม่ของผู้ดูแล / บัญชี (ปลดล็อกด้วย) */
function staff_set_password($username, $password)
{
    sdb_update('staff', array('password_hash' => password_hash($password, PASSWORD_DEFAULT), 'fail_count' => 0, 'locked_until' => null),
               array('username' => $username));
    remember_forget_staff($username);                // เปลี่ยนรหัสผ่าน = ทุกเครื่องที่จดจำไว้ต้องเข้าระบบใหม่ (ช่วงที่ 10)
    staff_db_reset();
}

/** ย้ายสาขาตั้งแต่วันนี้ — ปิดช่วงเดิม (ถึงเมื่อวาน) แล้วเปิดช่วงใหม่ · ย้ายซ้ำในวันเดียวกัน = แก้แถวของวันนี้
    TODO:
      - [x] transaction · ยอดเก่ายังผูกกับสาขาเดิม (user_branch_on) */
function staff_move($username, $toCode)
{
    sdb_tx(function () use ($username, $toCode) {
        $r     = staff_auth_row($username);
        $id    = (int) $r['staff_id'];
        $to    = branch_id_of($toCode);
        $today = date('Y-m-d');
        $cur   = sdb_row('SELECT sb_id, date_from FROM ' . sdb_tb('staff_branch') . ' WHERE staff_id = ? AND date_to IS NULL ORDER BY sb_id DESC LIMIT 1', array($id));
        if ($cur !== null && $cur['date_from'] >= $today) {
            sdb_update('staff_branch', array('branch_id' => $to), array('sb_id' => $cur['sb_id']));
        } else {
            if ($cur !== null) {
                sdb_update('staff_branch', array('date_to' => date('Y-m-d', strtotime('-1 day'))), array('sb_id' => $cur['sb_id']));
            }
            sdb_insert('staff_branch', array('staff_id' => $id, 'branch_id' => $to, 'date_from' => $today));
        }
        sdb_update('staff', array('branch_id' => $to), array('staff_id' => $id));
    });
    staff_db_reset();
}

/** พักงาน / เปิดใช้งาน */
function staff_set_active($username, $on)
{
    sdb_update('staff', array('is_active' => $on ? 1 : 0), array('username' => $username));
    if (!$on) {
        remember_forget_staff($username);            // พักงาน = ยกเลิกการจดจำทุกเครื่อง (ช่วงที่ 10)
    }
    staff_db_reset();
}

/** ลบผู้ใช้ — เรียกหลังเช็ก staff_data_reason() แล้วเท่านั้น (คนที่ยังไม่เคยทำรายการ) */
function staff_delete($username)
{
    $r = staff_auth_row($username);
    if ($r === null) {
        return;
    }
    sdb_tx(function () use ($r) {
        sdb_q('DELETE FROM ' . sdb_tb('remember') . ' WHERE staff_id = ?', array((int) $r['staff_id']));
        sdb_q('DELETE FROM ' . sdb_tb('staff_branch') . ' WHERE staff_id = ?', array((int) $r['staff_id']));
        sdb_q('DELETE FROM ' . sdb_tb('staff') . ' WHERE staff_id = ?', array((int) $r['staff_id']));
    });
    staff_db_reset();
}

/** ถูกล็อกเพราะกรอกผิดหลายครั้งอยู่ไหม — คืนจำนวนวินาทีที่เหลือ (0 = ไม่ล็อก) */
function staff_locked_left($username)
{
    $r = staff_auth_row($username);
    if ($r === null || empty($r['locked_until'])) {
        return 0;
    }
    $left = strtotime($r['locked_until']) - time();
    return $left > 0 ? $left : 0;
}

/** กรอกผิด 1 ครั้ง — ครบ STAFF_LOCK_FAILS ครั้ง ล็อก STAFF_LOCK_MINUTES นาที แล้วเริ่มนับใหม่ */
function staff_login_failed($username)
{
    $r = staff_auth_row($username);
    if ($r === null) {
        return;
    }
    $n = (int) $r['fail_count'] + 1;
    if ($n >= STAFF_LOCK_FAILS) {
        sdb_update('staff', array('fail_count' => 0, 'locked_until' => date('Y-m-d H:i:s', time() + STAFF_LOCK_MINUTES * 60)),
                   array('staff_id' => (int) $r['staff_id']));
    } else {
        sdb_update('staff', array('fail_count' => $n), array('staff_id' => (int) $r['staff_id']));
    }
    staff_db_reset();
}

/** เข้าระบบสำเร็จ — ล้างตัวนับ + บันทึกเวลาเข้าล่าสุด */
function staff_login_ok($username)
{
    sdb_update('staff', array('fail_count' => 0, 'locked_until' => null, 'last_login' => date('Y-m-d H:i:s')),
               array('username' => $username));
    staff_db_reset();
}

/** สาขาที่กำลังทำงานอยู่
    พนักงานผูกกับสาขาตัวเองเสมอ · ผู้ดูแลเลือกสาขาจากแถบบน (?branch=) แล้วระบบจำไว้ */
function work_branch($user)
{
    if (!$user || $user['role'] !== 'admin') {
        $all = users_all();
        return (isset($user['username']) && isset($all[$user['username']]))
             ? $all[$user['username']]['branch'] : $user['branch'];
    }
    $b = branches_active();
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
    return $keys ? $keys[0] : '';              // ยังไม่มีสาขาเลย (ระบบใหม่) — require_login พาผู้ดูแลไปเพิ่มสาขาแรก
}

/** พนักงาน (ไม่รวมผู้ดูแล) ที่ประจำสาขานี้ตอนนี้ */
function branch_staff($code)
{
    $out = array();
    foreach (users_all() as $k => $u) {
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

function users_active()
{
    $out = array();
    foreach (users_all() as $k => $u) {
        if (role_enabled($u['role']) && user_active($u)) {
            $out[$k] = $u;
        }
    }
    return $out;
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

function branch_name($code)
{
    $b = branches_all();                  // รวมสาขาที่ปิดแล้ว ประวัติเก่าจะได้ยังเห็นชื่อ
    return isset($b[$code]) ? $b[$code]['name'] : $code;
}

/** สาขาที่ผู้ใช้คนนี้เลือกดูได้ */
function visible_branches($user)
{
    $all = branches_active();
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

/* ==========================================================
   รายงานยอดขาย (report-sales.php)
   ----------------------------------------------------------
   หลักการสำคัญ (ตอบเรื่องพนักงานย้ายสาขา)

   ทุกบิลเก็บ 2 อย่างคู่กันเสมอ
     branch_id  = สาขา "ณ วันที่ขาย"   → ใช้ตอบคำถาม "สาขานี้ขายได้เท่าไร"
     created_by = คนขาย                → ใช้ตอบคำถาม "ฉันขายได้เท่าไร"

   สาขาถูกปั๊มลงในบิลตอนบันทึก ไม่ใช่ไปอ่านจากโปรไฟล์ตอนออกรายงาน
   ถ้าอ่านจากโปรไฟล์ พอพนักงานย้ายสาขา ยอดเก่าของสาขาเดิมจะย้ายตามไปด้วย
   ซึ่งผิด — ยอดของเดือนที่แล้วต้องไม่เปลี่ยนไม่ว่าจะย้ายคนไปไหน

   ทุกวันอ่านจาก ao_stock_sale (บิลที่ยกเลิกไม่นับ) ทั้งช่วงในคิวรีเดียว — sales_agg()
   ========================================================== */

/**
 * ข้อมูลเต็มของผู้ใช้ (รวมประวัติการย้ายสาขา) — session เก็บไว้แค่ไม่กี่ฟิลด์เพื่อให้เบา
 */
function full_user($user)
{
    $all = users_all();
    return isset($all[$user['username']]) ? $all[$user['username']] : $user;
}

/** สาขานี้มีข้อมูลแล้วหรือยัง — คืนเหตุผล (ว่าง = ยังไม่มี ลบได้)
    ประวัติชนิด setting (ผู้ดูแลเพิ่ม / แก้สาขา) และ category (เพิ่ม / ลบหมวด · ช่วงที่ 12) ไม่นับ — ลบไปพร้อมสาขาใน branch_delete()
    TODO:
      - [x] เช็กจากตาราง ao_stock_* (พนักงาน ประวัติประจำสาขา เอกสาร สต๊อก ประวัติการทำรายการ)
      - [x] ช่วงที่ 6: เอกสารคลังเช็กจากตารางแล้ว ไม่ดู session
      - [x] ช่วงที่ 7: บิล / เปิด–ปิดร้าน / รับคืน อยู่ในตารางแล้ว — ไม่ดู session อีก */
function branch_data_reason($code)
{
    $row = branch_row($code);
    if ($row === null) {
        return '';
    }
    $id = (int) $row['branch_id'];
    $checks = array(
        'staff'        => 'มีพนักงานประจำอยู่',
        'staff_branch' => 'เคยมีพนักงานประจำ (ยอดขายเก่าผูกกับสาขานี้)',
        'move'         => 'มีการเคลื่อนไหวสต๊อกแล้ว',
        'balance'      => 'มียอดสต๊อกแล้ว',
        'receive'      => 'มีใบรับเข้าแล้ว',
        'issue'        => 'มีใบเบิกแล้ว',
        'count'        => 'มีใบตรวจนับแล้ว',
        'sale'         => 'มีบิลขายแล้ว',
        'return'       => 'มีใบรับคืนแล้ว',
        'store_day'    => 'มีการเปิดร้านแล้ว',
        'log'          => 'มีประวัติการทำรายการแล้ว',
    );
    foreach ($checks as $t => $why) {
        $sql = 'SELECT 1 FROM ' . sdb_tb($t) . ' WHERE branch_id = ?' . ($t === 'log' ? ' AND type NOT IN (\'setting\', \'category\')' : '') . ' LIMIT 1';
        if (sdb_val($sql, array($id)) !== null) {
            return $why;
        }
    }
    return '';
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

/** พนักงานทั้งหมด (รวมที่พักงาน ไม่รวมผู้ดูแล / บัญชี) */
function staff_all()
{
    $out = array();
    foreach (users_all() as $k => $u) {
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

/** สิทธิ์ที่เปลี่ยน สำหรับลงประวัติ — array('เพิ่มสิทธิ์' => ชื่อ, 'เอาสิทธิ์ออก' => ชื่อ) เฉพาะฝั่งที่มี
    TODO:
      - [x] ช่วงที่ 11: สิทธิ์มีหลายตัวขึ้น ประวัติบอกเฉพาะที่เพิ่ม / เอาออก แทนการลงทั้งชุดเดิมและชุดใหม่ */
function perm_changes($before, $after)
{
    $out = array();
    $add = array_values(array_diff($after, $before));
    $del = array_values(array_diff($before, $after));
    if ($add) {
        $out['เพิ่มสิทธิ์'] = perm_names(array_intersect(array_keys(perm_list()), $add));     // เรียงตาม perm_list (ไม่ผ่าน perm_clean — ตัวพ่วงที่ตัวหลักมีอยู่แล้วต้องไม่หาย)
    }
    if ($del) {
        $out['เอาสิทธิ์ออก'] = perm_names(array_intersect(array_keys(perm_list()), $del));
    }
    return $out;
}

/** สรุปสิทธิ์รายหมวด สำหรับตารางรายชื่อ — array( หมวด => array(have, total, names) ) เฉพาะหมวดที่มีอย่างน้อย 1 ตัว
    ไม่นับ manager — ตำแหน่งผู้จัดการแสดงเป็นป้ายข้างชื่อแทน (ช่วงที่ 12)
    TODO:
      - [x] ช่วงที่ 11 */
function perm_summary($perms)
{
    $out = array();
    foreach (perm_list() as $k => $p) {
        if ($k === 'manager') {
            continue;
        }
        $g = $p['group'];
        if (!isset($out[$g])) {
            $out[$g] = array('have' => 0, 'total' => 0, 'names' => array());
        }
        $out[$g]['total']++;
        if (in_array($k, $perms, true)) {
            $out[$g]['have']++;
            $out[$g]['names'][] = $p['short'];
        }
    }
    return array_filter($out, function ($g) { return $g['have'] > 0; });
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

/** เคยทำรายการแล้วหรือยัง — คืนเหตุผล (ว่าง = ยังไม่เคย ลบได้)
    TODO:
      - [x] เช็กจากตาราง ao_stock_* (created_by / opened_by / closed_by / void_by)
      - [x] ช่วงที่ 6: เอกสารคลังเช็กจากตารางแล้ว ไม่ดู session
      - [x] ช่วงที่ 7: บิล / เปิด–ปิดร้าน / รับคืน อยู่ในตารางแล้ว — ไม่ดู session อีก */
function staff_data_reason($k)
{
    $r = staff_auth_row($k);
    if ($r !== null) {
        $id = (int) $r['staff_id'];
        $checks = array(
            array('sale', 'created_by', 'บิลขาย'),
            array('receive', 'created_by', 'ใบรับเข้า'),
            array('issue', 'created_by', 'ใบเบิก'),
            array('count', 'created_by', 'ใบตรวจนับ'),
            array('receive', 'void_by', 'การยกเลิกใบรับเข้า'),
            array('issue', 'void_by', 'การยกเลิกใบเบิก'),
            array('count', 'void_by', 'การยกเลิกใบตรวจนับ'),
            array('return', 'created_by', 'ใบรับคืน'),
            array('move', 'created_by', 'การเคลื่อนไหวสต๊อก'),
            array('store_day', 'opened_by', 'การเปิดร้าน'),
            array('store_day', 'closed_by', 'การปิดร้าน'),
            array('cash_move', 'created_by', 'การเติม / หยิบเงินในลิ้นชัก'),
            array('log', 'created_by', 'ประวัติการทำรายการ'),
        );
        foreach ($checks as $c) {
            if (sdb_val('SELECT 1 FROM ' . sdb_tb($c[0]) . ' WHERE `' . $c[1] . '` = ? LIMIT 1', array($id)) !== null) {
                return 'มี' . $c[2] . 'ที่เคยทำแล้ว';
            }
        }
    }
    return '';
}

/** อ่านสิทธิ์จากฟอร์ม (เรียงตามลำดับใน perm_list · ตัดสิทธิ์ที่ขาดตัวที่ต้องมีออก — ช่วงที่ 11) */
function read_perms()
{
    $in = (isset($_POST['perms']) && is_array($_POST['perms'])) ? $_POST['perms'] : array();
    return perm_clean(array_filter($in, 'is_string'));
}

/** ลายนิ้วมือของ PIN = HMAC-SHA256 ด้วย AOSTOCK_SECRET_KEY — เทียบ PIN ซ้ำได้โดยไม่ต้องรู้ PIN จริง · ไม่มีกุญแจ = null
    TODO:
      - [x] ช่วงที่ 10: คืนการเช็ก PIN ซ้ำตอนย้ายสาขา / เปิดใช้งานกลับ (ข้อ 4ก) */
function pin_fp($pin)
{
    if (!defined('AOSTOCK_SECRET_KEY') || strlen((string) AOSTOCK_SECRET_KEY) < 16) {
        return null;
    }
    return hash_hmac('sha256', 'pin|' . preg_replace('/\D/', '', (string) $pin), (string) AOSTOCK_SECRET_KEY);
}

/** PIN ซ้ำกับใครในสาขา (ว่าง = ไม่ซ้ำ) — ใช้ตอนตั้ง PIN ใหม่ (รู้ PIN จริง)
    คนที่มีลายนิ้วมือเทียบด้วย pin_fp · คนที่ยังไม่มี (ยังไม่ได้เข้าระบบหลังช่วงที่ 10) เทียบด้วย password_verify
    TODO:
      - [x] เทียบกับ pin_hash ของพนักงานที่ใช้งานอยู่ในสาขา
      - [x] ช่วงที่ 10: เทียบ pin_fp ก่อน (เร็วกว่า password_verify ทีละคน) */
function pin_owner($branch, $pin, $except)
{
    $fp = pin_fp($pin);
    foreach (branch_staff($branch) as $k => $o) {
        if ($k === $except) {
            continue;
        }
        $row = staff_auth_row($k);
        if ($row === null || $row['pin_hash'] === '') {
            continue;
        }
        if ($fp !== null && (string) $row['pin_fp'] !== '') {
            if (hash_equals((string) $row['pin_fp'], $fp)) {
                return $o['name'];
            }
        } elseif (password_verify($pin, $row['pin_hash'])) {
            return $o['name'];
        }
    }
    return '';
}

/** PIN ของ $username ซ้ำกับพนักงานที่ใช้งานอยู่ในสาขา $branch ไหม (ใช้ตอนย้ายสาขา / เปิดใช้งานกลับ — ไม่รู้ PIN จริง)
    คืนชื่อคนที่ซ้ำ · ว่าง = ไม่ซ้ำ หรือเช็กไม่ได้ (คนใดคนหนึ่งยังไม่มีลายนิ้วมือ PIN)
    TODO:
      - [x] ช่วงที่ 10: เทียบ pin_fp (ข้อ 4ก) */
function staff_pin_conflict($username, $branch)
{
    $me = staff_auth_row($username);
    if ($me === null || (string) $me['pin_fp'] === '') {
        return '';
    }
    foreach (branch_staff($branch) as $k => $o) {
        $row = staff_auth_row($k);
        if ($k !== $username && $row !== null && (string) $row['pin_fp'] !== '' && hash_equals((string) $row['pin_fp'], (string) $me['pin_fp'])) {
            return $o['name'];
        }
    }
    return '';
}

/** ช่องติ๊กสิทธิ์ แยกตามหมวด (perm_groups) — สิทธิ์ที่ต้องมีตัวอื่นก่อนมี data-needs ให้ footer.php ปิด/เปิดช่องตาม
    $only = หมวดที่แสดง (null = ทุกหมวด · หน้าผู้จัดการสาขาไม่แสดงหมวดสิทธิ์เสริม)
    TODO:
      - [x] ช่วงที่ 11: แบ่ง 6 หมวด · คำอธิบายใต้สิทธิ์ · บอกว่าต้องมีสิทธิ์ไหนก่อน
      - [x] ช่วงที่ 12: เลือกหมวดที่แสดงได้ · ผู้จัดการสาขาไม่อยู่ในช่องติ๊ก — เลือกที่ "ตำแหน่ง" (staff_position_field) */
function perm_boxes($checked, $branch, $only = null)
{
    $pl   = perm_list();
    $days = (int) backdate_days($branch) . ' วัน (ตั้งที่หน้าจัดการสาขา)';
    $hint = array(
        'sale'         => 'ไม่ติ๊ก = พนักงานคลังอย่างเดียว',
        'discount'     => 'ไม่ติ๊ก = ขายราคาเต็มเท่านั้น',
        'store'        => 'นับเงินทอนตอนเปิด · นับเงินสดตอนปิด',
        'store_reopen' => 'ใช้เมื่อปิดร้านไปแล้วแต่ยังต้องขายต่อ · ต้องระบุเหตุผล',
        'cash'         => 'เติมเงินทอน / หยิบเงินออกระหว่างวัน',
        'refund'       => 'บิลย้อนหลังได้ ' . $days,
        'refund_cash'  => 'ไม่ติ๊ก = รับคืนได้แต่ไม่คืนเงินสด',
        'backdate'     => 'ย้อนหลังได้ ' . $days,
    );
    foreach (perm_groups() as $g => $title) {
        if ($only !== null && !in_array($g, $only, true)) {
            continue;
        }
        echo '<h4 class="perm-h">' . e($title) . '</h4><div class="perm-grid">';
        foreach ($pl as $k => $p) {
            if ($p['group'] !== $g || $k === 'manager') {
                continue;
            }
            $needs = isset($p['needs']) ? $p['needs'] : array();
            echo '<label class="perm-o"><input type="checkbox" name="perms[]" value="' . e($k) . '"'
               . ($needs ? ' data-needs="' . e(implode(' ', $needs)) . '"' : '')
               . (in_array($k, $checked, true) ? ' checked' : '') . '><span><svg class="ico"><use href="#i-check"/></svg><b>'
               . e($p['label']) . '</b>';
            if (isset($hint[$k])) {
                echo '<small>' . e($hint[$k]) . '</small>';
            }
            if ($needs) {
                $nm = array();
                foreach ($needs as $n) {
                    $nm[] = $pl[$n]['short'];
                }
                echo '<small class="perm-needs">ต้องมี ' . e(implode(' หรือ ', $nm)) . ' ด้วย</small>';
            }
            echo '</span></label>';
        }
        echo '</div>';
    }
}

/* ==========================================================
   งานจัดการพนักงาน — ใช้ร่วมกันระหว่างผู้ดูแล (adm-users.php / adm-user-add.php) และผู้จัดการสาขา (team.php) · ช่วงที่ 12
   ----------------------------------------------------------
   staff_act_*: ตรวจข้อมูล → เขียน → ลงประวัติของสาขา (ชนิด setting) · คืน '' = สำเร็จ หรือข้อความผิดพลาด
   ใครแก้ใครได้ เช็กที่หน้า: ผู้ดูแลแก้พนักงานได้ทุกคน · ผู้จัดการสาขาใช้ manager_target_error ก่อนเรียก
   ข้อความผิดพลาด / ประวัติ เหมือนของหน้าผู้ดูแลเดิมทุกคำ (ย้ายมาจาก adm-users.php / adm-user-add.php)
   ========================================================== */

/** บทบาทของผู้ทำ ต่อท้ายชื่อในประวัติ — (ผู้ดูแล) / (ผู้จัดการสาขา)
    TODO:
      - [x] ช่วงที่ 12 */
function actor_label($user)
{
    if ($user['role'] === 'admin') {
        return 'ผู้ดูแล';
    }
    return user_role_label($user);
}

/** ตรวจชื่อผู้ใช้ของพนักงาน — '' = ใช้ได้ · $except = ชื่อเดิมของคนที่กำลังแก้ (ไม่เปลี่ยน = ผ่านเสมอ แม้รูปแบบเก่าจะไม่ตรงกติกา)
    พนักงานพิมพ์ชื่อนี้คู่กับ PIN ตอนเข้าระบบบนเครื่องใหม่ (ช่วงที่ 13) จึงต้องกรอกเองทุกครั้ง
    TODO:
      - [x] ช่วงที่ 13 */
function staff_username_error($uname, $except = '')
{
    if ($except !== '' && $uname === $except) {
        return '';
    }
    if ($uname === '') {
        return 'กรุณากรอกชื่อผู้ใช้ — พนักงานใช้ชื่อนี้คู่กับ PIN ตอนเข้าระบบ';
    }
    if (!preg_match('/^[a-z0-9_]{3,20}$/', $uname)) {
        return 'ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษพิมพ์เล็ก ตัวเลข หรือ _ ยาว 3–20 ตัว';
    }
    $everyone = users_all();
    if (isset($everyone[$uname])) {
        return 'ชื่อผู้ใช้ ' . $uname . ' มีคนใช้แล้ว';
    }
    return '';
}

/** เพิ่มพนักงาน — $in: name username initials branch pin perms
    คืน array('username' => ชื่อผู้ใช้ที่ได้) หรือ array('error' => ข้อความ)
    TODO:
      - [x] ช่วงที่ 12: ย้ายจาก adm-user-add.php ให้ team.php ใช้ด้วย
      - [x] ช่วงที่ 13: ชื่อผู้ใช้ต้องกรอก (staff_username_error) */
function staff_act_add($in, $actor)
{
    $br    = branches_active();
    $name  = trim(preg_replace('/\s+/u', ' ', (string) $in['name']));
    $uname = strtolower(trim((string) $in['username']));
    $ini   = trim((string) $in['initials']);
    $bc    = (string) $in['branch'];
    $pin   = preg_replace('/\D/', '', (string) $in['pin']);
    $perms = perm_clean($in['perms']);

    if ($name === '') {
        return array('error' => 'กรุณากรอกชื่อพนักงาน');
    } elseif (($ue = staff_username_error($uname)) !== '') {      // ช่วงที่ 13: ต้องกรอก (เลิกตั้ง staffN ให้อัตโนมัติ)
        return array('error' => $ue);
    } elseif (!isset($br[$bc])) {
        return array('error' => 'กรุณาเลือกสาขาที่ประจำ');
    } elseif (strlen($pin) !== 4) {
        return array('error' => 'PIN ต้องเป็นตัวเลข 4 หลัก');
    } elseif (($dup = pin_owner($bc, $pin, '')) !== '') {
        return array('error' => 'PIN นี้ซ้ำกับ ' . $dup . ' ในสาขาเดียวกัน กรุณาใช้เลขอื่น');
    } elseif (!$perms) {
        return array('error' => 'กรุณาเลือกสิทธิ์อย่างน้อย 1 อย่าง');
    }
    staff_create(array(
        'username' => $uname, 'name' => $name, 'initials' => $ini, 'role' => 'staff',
        'branch' => $bc, 'perms' => $perms, 'pin' => $pin,
    ));
    log_add($bc, 'setting', $actor, 'เพิ่มพนักงานใหม่ ' . $name, array(
        'ชื่อผู้ใช้' => $uname,
        'สิทธิ์'    => perm_names($perms),
        'เพิ่มโดย'  => $actor['name'] . ' (' . actor_label($actor) . ')',
    ));
    return array('username' => $uname);
}

/** แก้ชื่อ / อักษรย่อ / สิทธิ์ / ชื่อผู้ใช้ ($name ตัดช่องว่างซ้ำแล้ว · $newUser = null คือไม่เปลี่ยนชื่อผู้ใช้)
    TODO:
      - [x] ช่วงที่ 12: ย้ายจาก adm-users.php (act=save)
      - [x] ช่วงที่ 13: เปลี่ยนชื่อผู้ใช้ได้ (ผู้ดูแล / ผู้จัดการสาขา) — ตรวจครบก่อนเขียน · ลงประวัติรายการเดียวกัน */
function staff_act_save($username, $name, $ini, $perms, $actor, $newUser = null)
{
    $all = staff_all();
    if (!isset($all[$username])) {
        return 'ไม่พบพนักงานคนนี้';
    }
    $u       = $all[$username];
    $perms   = perm_clean($perms);
    $newUser = ($newUser === null) ? $username : strtolower(trim((string) $newUser));
    if ($name === '') {
        return 'กรุณากรอกชื่อพนักงาน';
    } elseif (($ue = staff_username_error($newUser, $username)) !== '') {
        return $ue;
    } elseif (!$perms) {
        return 'กรุณาเลือกสิทธิ์อย่างน้อย 1 อย่าง — ถ้าไม่ให้ใช้งานแล้ว ใช้ “พักงาน / ลาออก” แทน';
    }
    $before  = isset($u['perms']) ? $u['perms'] : array();
    $changes = array();
    if ($newUser !== $username) {
        $changes['ชื่อผู้ใช้'] = $username . ' → ' . $newUser;
        staff_rename($username, $newUser);
    }
    if ($name !== $u['name']) {
        $changes['ชื่อ'] = $u['name'] . ' → ' . $name;
    }
    $changes = array_merge($changes, perm_changes($before, $perms));     // ช่วงที่ 11: บอกเฉพาะที่เพิ่ม / เอาออก
    staff_update($newUser, $name, $ini, $perms);
    if ($changes) {
        $changes['แก้โดย'] = $actor['name'] . ' (' . actor_label($actor) . ')';
        log_add($u['branch'], 'setting', $actor, 'แก้ข้อมูลพนักงาน ' . $name, $changes);
    }
    return '';
}

/** รีเซ็ต PIN (ห้ามซ้ำกับคนอื่นในสาขา · ประวัติไม่แสดงเลข PIN)
    TODO:
      - [x] ช่วงที่ 12: ย้ายจาก adm-users.php (act=pin) */
function staff_act_pin($username, $pin, $actor)
{
    $all = staff_all();
    if (!isset($all[$username])) {
        return 'ไม่พบพนักงานคนนี้';
    }
    $u   = $all[$username];
    $pin = preg_replace('/\D/', '', (string) $pin);
    if (strlen($pin) !== 4) {
        return 'PIN ต้องเป็นตัวเลข 4 หลัก';
    } elseif (($dup = pin_owner($u['branch'], $pin, $username)) !== '') {
        return 'PIN นี้ซ้ำกับ ' . $dup . ' ในสาขาเดียวกัน กรุณาใช้เลขอื่น';
    }
    staff_set_pin($username, $pin);
    log_add($u['branch'], 'setting', $actor, 'รีเซ็ต PIN ของ ' . $u['name'], array(
        'แก้โดย' => $actor['name'] . ' (' . actor_label($actor) . ')', 'หมายเหตุ' => 'ไม่แสดงเลข PIN ในประวัติ',
    ));
    return '';
}

/** พักงาน / ลาออก ($on = false) หรือเปิดใช้งานอีกครั้ง ($on = true)
    TODO:
      - [x] ช่วงที่ 12: ย้ายจาก adm-users.php (act=off / on) */
function staff_act_active($username, $on, $why, $actor)
{
    $all = staff_all();
    if (!isset($all[$username])) {
        return 'ไม่พบพนักงานคนนี้';
    }
    $u   = $all[$username];
    $br  = branches_active();
    $why = trim((string) $why);
    if ($on && !isset($br[$u['branch']])) {
        return 'สาขาเดิมของ ' . $u['name'] . ' ถูกปิดใช้งานแล้ว — เปิดสาขาก่อน หรือย้ายสาขาก่อนเปิดใช้งาน';
    } elseif ($on && $u['role'] === 'staff' && ($dup = staff_pin_conflict($username, $u['branch'])) !== '') {
        return 'PIN ของ ' . $u['name'] . ' ซ้ำกับ ' . $dup . ' ในสาขาเดียวกันแล้ว — รีเซ็ต PIN ก่อนเปิดใช้งาน';     // ช่วงที่ 10
    }
    staff_set_active($username, $on);
    log_add($u['branch'], 'setting', $actor, ($on ? 'เปิดใช้งาน ' : 'พักงาน / ลาออก ') . $u['name'], array(
        'เหตุผล' => $on ? '—' : ($why !== '' ? $why : 'ไม่ได้ระบุ'),
        'แก้โดย' => $actor['name'] . ' (' . actor_label($actor) . ')',
    ));
    return '';
}

/** ลบพนักงาน — ได้เฉพาะคนที่ยังไม่เคยทำรายการ และต้องติ๊กยืนยัน ($sure)
    TODO:
      - [x] ช่วงที่ 12: ย้ายจาก adm-users.php (act=delete) */
function staff_act_delete($username, $sure, $actor)
{
    $all = staff_all();
    if (!isset($all[$username])) {
        return 'ไม่พบพนักงานคนนี้';
    }
    $u   = $all[$username];
    $why = staff_data_reason($username);
    if ($why !== '') {
        return 'ลบไม่ได้ เพราะ' . $why . ' — ใช้ “พักงาน / ลาออก” แทน ชื่อในเอกสารเก่าจะไม่หาย';
    } elseif (!$sure) {
        return 'กรุณาติ๊กยืนยันก่อนลบพนักงาน';
    }
    staff_delete($username);
    log_add($u['branch'], 'setting', $actor, 'ลบพนักงาน ' . $u['name'], array('แก้โดย' => $actor['name'] . ' (' . actor_label($actor) . ')'));
    return '';
}

/** ผู้จัดการสาขาแก้พนักงานคนนี้ได้ไหม — '' = ได้ หรือเหตุผลที่ไม่ได้
    ได้เฉพาะพนักงาน (ไม่ใช่ผู้จัดการ รวมตัวเอง) ที่ประจำสาขาเดียวกัน · ผู้จัดการคนอื่น / ย้ายสาขา / ตั้งผู้จัดการ เป็นของผู้ดูแล
    TODO:
      - [x] ช่วงที่ 12 (ข้อ 2ข) */
function manager_target_error($manager, $username)
{
    $all = staff_all();
    if (!isset($all[$username])) {
        return 'ไม่พบพนักงานคนนี้';
    }
    $u = $all[$username];
    if ($u['branch'] !== work_branch($manager)) {
        return 'พนักงานคนนี้ไม่ได้ประจำสาขาของคุณ';
    }
    if (in_array('manager', $u['perms'], true)) {
        return 'ผู้จัดการสาขาแก้ไขได้เฉพาะผู้ดูแล';
    }
    return '';
}

/** สิทธิ์หลังผู้จัดการสาขาบันทึก = สิทธิ์ที่ติ๊กมา (เฉพาะหมวดที่ผู้จัดการให้ได้) + สิทธิ์เสริมเดิมของคนนั้น (ผู้ดูแลให้ไว้ ห้ามหาย)
    TODO:
      - [x] ช่วงที่ 12 (ข้อ 2ก) */
function perm_merge_by_manager($ticked, $before)
{
    $grant = perm_manager_grantable();
    return perm_clean(array_merge(array_intersect((array) $ticked, $grant), array_diff((array) $before, $grant)));
}

/** ยืนยันตัวตนด้วย PIN ของตัวเองก่อนจัดการพนักงาน (เครื่อง POS ใช้ร่วมกัน) — '' = ผ่าน
    กรอกผิดนับรวมกับการเข้าระบบ: ครบ STAFF_LOCK_FAILS ครั้ง ล็อก STAFF_LOCK_MINUTES นาที (เข้าระบบใหม่ก็ไม่ได้จนพ้นเวลา)
    TODO:
      - [x] ช่วงที่ 12 (ข้อ 3ก) */
function staff_pin_confirm($username, $pin)
{
    $left = staff_locked_left($username);
    if ($left > 0) {
        return 'PIN ของคุณถูกล็อกอีก ' . (int) ceil($left / 60) . ' นาที เพราะกรอกผิดหลายครั้ง';
    }
    $row = staff_auth_row($username);
    $pin = preg_replace('/\D/', '', (string) $pin);
    if ($row === null || $row['pin_hash'] === '' || strlen($pin) !== 4 || !password_verify($pin, $row['pin_hash'])) {
        staff_login_failed($username);
        return 'PIN ยืนยันตัวตนไม่ถูกต้อง — กรอกผิด ' . STAFF_LOCK_FAILS . ' ครั้งจะถูกล็อก ' . STAFF_LOCK_MINUTES . ' นาที';
    }
    if ((int) $row['fail_count'] > 0) {
        sdb_update('staff', array('fail_count' => 0), array('staff_id' => (int) $row['staff_id']));
        staff_db_reset();
    }
    return '';
}

/* ---------- ตำแหน่ง: พนักงาน / ผู้จัดการสาขา (ช่วงที่ 12 ข้อ ก + ข) ----------
   ผู้จัดการสาขา = สิทธิ์ manager ในคอลัมน์ perms เหมือนเดิม แต่หน้าจอให้เลือกเป็น "ตำแหน่ง" แยกจากช่องติ๊กสิทธิ์
   ตั้งได้ 2 ทาง: หน้าจัดการพนักงาน (ช่องตำแหน่งบนสุด) และหน้าจัดการสาขา (เลือกผู้จัดการรายสาขา) */

/** สิทธิ์ชุดนี้ + ตั้ง / ปลดผู้จัดการ
    TODO:
      - [x] ช่วงที่ 12 */
function perm_set_manager($perms, $on)
{
    $p = array_values(array_diff((array) $perms, array('manager')));
    if ($on) {
        $p[] = 'manager';
    }
    return perm_clean($p);
}

/** ช่องเลือกตำแหน่ง (name="position" = staff | manager) — วางบนสุดของฟอร์มเพิ่ม / แก้พนักงานของผู้ดูแล
    TODO:
      - [x] ช่วงที่ 12 (ข้อ ก) */
function staff_position_field($isManager)
{
    $opts = array(
        'staff'   => array('พนักงาน', 'ทำงานได้ตามสิทธิ์ที่ติ๊กด้านล่าง'),
        'manager' => array('ผู้จัดการสาขา', 'เห็นทุกอย่างในสาขา + ได้สิทธิ์ดูข้อมูลและสิทธิ์เสริมทุกตัวอัตโนมัติ · เพิ่ม / แก้ / รีเซ็ต PIN / พักงานพนักงานในสาขาเองได้ (เมนู “พนักงานในสาขา”) · งานขาย / งานคลังยังตามที่ติ๊ก'),
    );
    echo '<h4 class="perm-h">ตำแหน่ง</h4><div class="perm-grid staff-pos">';
    foreach ($opts as $k => $o) {
        echo '<label class="perm-o"><input type="radio" name="position" value="' . e($k) . '"'
           . ((($k === 'manager') === (bool) $isManager) ? ' checked' : '') . '><span><svg class="ico"><use href="#i-check"/></svg><b>'
           . e($o[0]) . '</b><small>' . e($o[1]) . '</small></span></label>';
    }
    echo '</div>';
}

/** เขียนเฉพาะสิทธิ์ (ไม่แตะชื่อ / อักษรย่อ)
    TODO:
      - [x] ช่วงที่ 12 */
function staff_set_perms($username, $perms)
{
    sdb_update('staff', array('perms' => perm_csv($perms)), array('username' => $username));
    staff_db_reset();
}

/** ตั้ง / ปลดผู้จัดการสาขา (หน้าจัดการสาขา) — ไม่เปลี่ยน = ไม่ทำอะไร · คืน '' หรือข้อความผิดพลาด
    TODO:
      - [x] ช่วงที่ 12 (ข้อ ข) */
function staff_act_set_manager($username, $on, $actor)
{
    $all = staff_all();
    if (!isset($all[$username])) {
        return 'ไม่พบพนักงานคนนี้';
    }
    $u = $all[$username];
    if (in_array('manager', $u['perms'], true) === (bool) $on) {
        return '';
    }
    if ($on && !user_active($u)) {
        return $u['name'] . ' พักงานอยู่ — เปิดใช้งานก่อนจึงจะตั้งเป็นผู้จัดการได้';
    }
    staff_set_perms($username, perm_set_manager($u['perms'], $on));
    log_add($u['branch'], 'setting', $actor, ($on ? 'ตั้ง ' . $u['name'] . ' เป็นผู้จัดการสาขา' : 'ปลด ' . $u['name'] . ' จากผู้จัดการสาขา'), array(
        'สาขา'  => branch_name($u['branch']),
        'แก้โดย' => $actor['name'] . ' (' . actor_label($actor) . ')',
    ));
    return '';
}
