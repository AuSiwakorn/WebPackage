<?php
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

require_once dirname(__FILE__) . '/config.php';

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
