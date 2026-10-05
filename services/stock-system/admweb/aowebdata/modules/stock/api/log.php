<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/log.php
 * ROLE: ประวัติการทำรายการ (stock_log) — เขียน / อ่านวันเดียว / อ่านทั้งช่วงพร้อมแบ่งหน้า · ชนิดของรายการ
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_log, ao_stock_staff, ao_stock_branch · ao_stock_sale / receive / issue / count / return (สถานะยกเลิก / รับคืนใน log_range)
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *
 * ⚠ ไฟล์นี้ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb — มีได้แค่ define() และประกาศ function (ห้าม echo / header / query ตอนโหลด)
 */

/* ==========================================================
   ประวัติการทำรายการ — ตาราง ao_stock_log (1 แถว / 1 เหตุการณ์ ต่อสาขาต่อวัน)
   ----------------------------------------------------------
   หน้าเว็บใช้แถวรูปนี้ (log_shape):
     ts เวลา (unix) · time 'HH:MM' · type (คีย์ของ log_types) · by ชื่อผู้ทำ · by_user username
     title ข้อความหลัก · amount ตัวเลขที่เกี่ยวข้อง (หรือ null) · ref เลขที่เอกสาร · detail array('หัวข้อ' => 'ค่า')
   - เอกสารคลังเขียนประวัติในทรานแซกชันเดียวกับตัวเอกสาร
   - type 'setting' (ผู้ดูแลตั้งค่า) ไม่นับเป็น "ข้อมูลของสาขา" ตอนเช็กลบสาขา (branch_data_reason)
   ========================================================== */

/** บันทึกหนึ่งรายการ — สาขาที่ไม่มีในระบบ (เช่น ผู้ดูแลยังไม่เลือกสาขา) ลงเป็น branch_id 0
    TODO:
      - [x] INSERT ao_stock_log (detail เก็บเป็น JSON) */
function log_add($code, $type, $user, $title, $detail = array(), $amount = null, $ref = '')
{
    sdb_insert('log', array(
        'branch_id'  => branch_id_of((string) $code),
        'log_date'   => date('Y-m-d'),
        'type'       => substr((string) $type, 0, 20),
        'title'      => stock_cut($title, 200),
        'amount'     => ($amount === null) ? null : round((float) $amount, 2),
        'doc_no'     => substr((string) $ref, 0, 24),
        'detail'     => $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null,
        'created_by' => $user ? stock_uid($user) : 0,
        'add_date'   => date('Y-m-d H:i:s'),
    ));
    return true;
}

/** แถวใน ao_stock_log (+ ชื่อผู้ทำ) → รูปแบบที่หน้าเว็บใช้ */
function log_shape($r)
{
    $ts     = strtotime($r['add_date']);
    $detail = ($r['detail'] !== null && $r['detail'] !== '') ? json_decode($r['detail'], true) : array();
    return array(
        'id'      => (int) $r['log_id'],
        'ts'      => $ts,
        'time'    => date('H:i', $ts),
        'type'    => $r['type'],
        'by'      => ($r['by_name'] !== null) ? $r['by_name'] : 'ระบบ',
        'by_user' => ($r['by_user'] !== null) ? $r['by_user'] : '',
        'title'   => $r['title'],
        'amount'  => ($r['amount'] !== null) ? (float) $r['amount'] : null,
        'ref'     => $r['doc_no'],
        'detail'  => is_array($detail) ? $detail : array(),
    );
}

/** ประวัติของสาขาวันนี้ — เรียงใหม่สุดขึ้นก่อน
    TODO:
      - [x] SELECT ao_stock_log
      - [x] ช่วงที่ 8: วันอื่นใช้ log_of_day · หลายวันหลายสาขาใช้ log_range */
function log_today($code, $limit = 0)
{
    return log_of_day($code, time(), $limit);
}

/** ประวัติของสาขาในวันหนึ่ง — เรียงใหม่สุดขึ้นก่อน
    TODO:
      - [x] ช่วงที่ 8: ลำดับเหตุการณ์ของวันก่อน (inc/history-past.php) */
function log_of_day($code, $ts, $limit = 0)
{
    $sql = 'SELECT l.*, s.name AS by_name, s.username AS by_user FROM ' . sdb_tb('log') . ' l'
         . ' LEFT JOIN ' . sdb_tb('staff') . ' s ON s.staff_id = l.created_by'
         . ' WHERE l.branch_id = ? AND l.log_date = ? ORDER BY l.add_date DESC, l.log_id DESC'
         . ($limit > 0 ? ' LIMIT ' . (int) $limit : '');
    $out = array();
    foreach (sdb_rows($sql, array(branch_id_of((string) $code), date('Y-m-d', $ts))) as $r) {
        $out[] = log_shape($r);
    }
    return $out;
}

/**
 * ประวัติของหลายสาขา ช่วงวันที่ $from–$to (ประวัติรวมของผู้ดูแล) — นับและแบ่งหน้าใน SQL
 *   $type = '' ทุกชนิด หรือคีย์ของ log_types() · $dir = desc | asc · $page เริ่มที่ 1 · $per แถวต่อหน้า
 * คืน array(
 *   rows   => แถวของหน้านั้น: log_shape + branch (รหัสสาขา) + date (Ymd) + void / void_mode / void_by (เอกสารที่ถูกยกเลิกภายหลัง)
 *             + returned (บิลขายที่มีใบรับคืนแล้ว)
 *   total  => จำนวนแถวตามชนิดที่กรอง
 *   counts => array(ชนิด => จำนวน) ทั้งช่วงไม่สนชนิดที่กรอง · perB => array(สาขา => จำนวน)
 * )
 * TODO:
 *   - [x] ช่วงที่ 8: ทุกวันอ่านจาก ao_stock_log (เดิมวันก่อนรวมจากเอกสาร + ข้อมูลสมมติ)
 */
function log_range($codes, $from, $to, $type = '', $dir = 'desc', $page = 1, $per = 100)
{
    list($in, $params) = sdb_in('b.code', $codes);
    array_unshift($params, date('Y-m-d', $from), date('Y-m-d', $to));
    $join  = ' FROM ' . sdb_tb('log') . ' l JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = l.branch_id';
    $where = ' WHERE l.log_date BETWEEN ? AND ? AND ' . $in;

    $counts = array_fill_keys(array_keys(log_types()), 0);
    $perB   = array();
    foreach (sdb_rows('SELECT b.code, l.type, COUNT(*) AS n' . $join . $where . ' GROUP BY b.code, l.type', $params) as $r) {
        if (isset($counts[$r['type']])) {
            $counts[$r['type']] += (int) $r['n'];
        }
        $perB[$r['code']] = (isset($perB[$r['code']]) ? $perB[$r['code']] : 0) + (int) $r['n'];
    }
    $total = ($type === '') ? array_sum($perB) : (isset($counts[$type]) ? $counts[$type] : 0);

    $d   = ($dir === 'asc') ? 'ASC' : 'DESC';
    $per = max(1, (int) $per);
    $off = (max(1, (int) $page) - 1) * $per;
    $p2  = $params;
    if ($type !== '') {
        $p2[] = (string) $type;
    }
    $sql = 'SELECT l.*, b.code, s.name AS by_name, s.username AS by_user' . $join . ' LEFT JOIN ' . sdb_tb('staff') . ' s ON s.staff_id = l.created_by'
         . $where . ($type !== '' ? ' AND l.type = ?' : '')
         . ' ORDER BY l.add_date ' . $d . ', l.log_id ' . $d . ' LIMIT ' . $per . ' OFFSET ' . (int) $off;      // LIMIT / OFFSET ต่อเป็นตัวเลข (ห้ามใช้ ?)
    $rows = array();
    $want = array();                                  // เอกสารที่ต้องเช็กว่าถูกยกเลิกทีหลังไหม: ตาราง => array(เลขที่)
    $docT = array('sale' => 'sale', 'receive' => 'receive', 'issue' => 'issue', 'adjust' => 'count');
    foreach (sdb_rows($sql, $p2) as $r) {
        $x = log_shape($r);
        $x['branch']    = $r['code'];
        $x['date']      = date('Ymd', strtotime($r['log_date']));
        $x['void']      = false;
        $x['void_mode'] = '';
        $x['void_by']   = '';
        $x['returned']  = false;
        if (isset($docT[$x['type']]) && $x['ref'] !== '') {
            $want[$docT[$x['type']]][$x['ref']] = true;
        }
        $rows[] = $x;
    }
    $voided = array();                                // ตาราง|branch_id|เลขที่ => array(mode, by)
    foreach ($want as $tb => $nos) {
        list($nin, $np) = sdb_in('t.doc_no', array_keys($nos));
        $vs = 'SELECT t.branch_id, t.doc_no, t.void_mode, v.name FROM ' . sdb_tb($tb) . ' t LEFT JOIN ' . sdb_tb('staff') . ' v ON v.staff_id = t.void_by'
            . ' WHERE t.status = \'void\' AND ' . $nin;
        foreach (sdb_rows($vs, $np) as $v) {
            $voided[$tb . '|' . $v['branch_id'] . '|' . $v['doc_no']] = array((string) $v['void_mode'], ($v['name'] !== null) ? $v['name'] : '');
        }
    }
    $returned = array();                              // branch_id|เลขบิล ที่มีใบรับคืนแล้ว (ป้าย "มีรับคืน" แบบเดโมเดิม — ช่วงที่ 10)
    if (isset($want['sale'])) {
        list($nin, $np) = sdb_in('s.doc_no', array_keys($want['sale']));
        foreach (sdb_rows('SELECT DISTINCT s.branch_id, s.doc_no FROM ' . sdb_tb('return') . ' r JOIN ' . sdb_tb('sale') . ' s ON s.sale_id = r.sale_id'
                        . ' WHERE ' . $nin, $np) as $v) {
            $returned[$v['branch_id'] . '|' . $v['doc_no']] = true;
        }
    }
    foreach ($rows as $i => $x) {
        $k = (isset($docT[$x['type']]) ? $docT[$x['type']] : '') . '|' . branch_id_of($x['branch']) . '|' . $x['ref'];
        if (isset($voided[$k])) {
            $rows[$i]['void']      = true;
            $rows[$i]['void_mode'] = $voided[$k][0];
            $rows[$i]['void_by']   = $voided[$k][1];
        }
        if ($x['type'] === 'sale' && isset($returned[branch_id_of($x['branch']) . '|' . $x['ref']])) {
            $rows[$i]['returned'] = true;
        }
    }
    return array('rows' => $rows, 'total' => $total, 'counts' => $counts, 'perB' => $perB);
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
