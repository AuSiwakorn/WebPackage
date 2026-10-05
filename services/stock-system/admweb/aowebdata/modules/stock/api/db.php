<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/db.php
 * ROLE: ตัวช่วยฐานข้อมูล (sdb_*) ใช้การเชื่อมต่อของ admweb · หน้าระบบขัดข้อง · ค่าตั้งแบบ key/value + เข้ารหัสค่าลับ
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_setting
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *
 * ⚠ ไฟล์นี้ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb — มีได้แค่ define() และประกาศ function (ห้าม echo / header / query ตอนโหลด)
 */

/* ==========================================================
   ใช้การเชื่อมต่อเดียวกับ admweb (DB::singleton())
   - ตั้ง utf8mb4 ตอนใช้ครั้งแรก (class DB ของ admweb เชื่อมต่อด้วย utf8 = utf8mb3 บันทึกอีโมจิไม่ได้)
   - error: class DB เขียน error_log เองแล้ว แต่ halt() echo SQL + ค่าที่ bind ออกหน้าเว็บด้วย
     → เก็บ output นั้นทิ้ง แล้วโยน RuntimeException แทน (หน้าเว็บแสดงหน้า "ระบบขัดข้อง" ผ่าน stock_error_page)
   - ไม่ใช้ DB_UP / DB_DEL / DB_GET / DB_LIST ของ admweb กับข้อมูล AOSTOCK (ชื่อ sdb_* ตั้งให้ไม่ชนกับ DB_* — ชื่อ function ของ PHP ไม่สนตัวพิมพ์):
     เขียนล้มเหลวแต่คืน true · cache ผล SELECT ไม่ล้างหลังเขียน (UPSTREAM.md D3, D4)
   - ห้ามใช้ ? กับ LIMIT / OFFSET (PDO แบบ emulate ใส่ ' ให้) — ต่อเป็นตัวเลขด้วย (int)
   ========================================================== */

/** ชื่อตารางของ AOSTOCK พร้อม backtick — sdb_tb('branch') = `ao_stock_branch`
    TODO:
      - [x] รับเฉพาะ a-z 0-9 _ */
function sdb_tb($name)
{
    return '`' . _DBPREFIX_ . 'stock_' . preg_replace('/[^a-z0-9_]/', '', $name) . '`';
}

/** การเชื่อมต่อฐานข้อมูล (ตัวเดียวกับ admweb) — ตั้ง utf8mb4 ครั้งเดียวต่อ request
    TODO:
      - [x] SET NAMES utf8mb4 */
function sdb()
{
    static $ready = false;
    $db = DB::singleton();
    if (!$ready) {
        $ready = true;
        ob_start();
        $db->query('SET NAMES utf8mb4');
        ob_end_clean();
    }
    return $db;
}

/** รัน SQL พร้อมค่าที่ bind (? หรือ :ชื่อ) — คืน PDOStatement · ผิดพลาดโยน RuntimeException
    TODO:
      - [x] เก็บ output ของ halt() ทิ้ง ไม่ให้ SQL โผล่บนหน้าเว็บ */
function sdb_q($sql, $params = array())
{
    $db = sdb();
    ob_start();
    $st = $db->prepare($sql, $params);
    ob_end_clean();
    if ($st === false) {
        throw new RuntimeException('AOSTOCK DB: ' . $db->error);
    }
    return $st;
}

/** ทุกแถว (array ของ array) */
function sdb_rows($sql, $params = array())
{
    return sdb_q($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
}

/** แถวแรก หรือ null */
function sdb_row($sql, $params = array())
{
    $r = sdb_q($sql, $params)->fetch(PDO::FETCH_ASSOC);
    return ($r === false) ? null : $r;
}

/** ค่าคอลัมน์แรกของแถวแรก หรือ null */
function sdb_val($sql, $params = array())
{
    $v = sdb_q($sql, $params)->fetchColumn();
    return ($v === false) ? null : $v;
}

/** INSERT หนึ่งแถว — $data = array(คอลัมน์ => ค่า) · คืน id ที่ได้
    TODO:
      - [x] ชื่อคอลัมน์รับเฉพาะ a-z 0-9 _ */
function sdb_insert($table, $data)
{
    $cols = array();
    $marks = array();
    foreach (array_keys($data) as $c) {
        $cols[]  = '`' . preg_replace('/[^a-z0-9_]/', '', $c) . '`';
        $marks[] = '?';
    }
    sdb_q('INSERT INTO ' . sdb_tb($table) . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $marks) . ')', array_values($data));
    return (int) sdb()->getInsertID();
}

/** UPDATE ตามเงื่อนไขเท่ากับ (AND) — $where ห้ามว่าง · คืนจำนวนแถวที่เปลี่ยน
    TODO:
      - [x] กัน WHERE ว่าง (update ทั้งตาราง) */
function sdb_update($table, $data, $where)
{
    if (!$data || !$where) {
        throw new InvalidArgumentException('sdb_update: ต้องมีทั้ง data และ where');
    }
    $set = array();
    $cond = array();
    $params = array();
    foreach ($data as $c => $v) {
        $set[] = '`' . preg_replace('/[^a-z0-9_]/', '', $c) . '` = ?';
        $params[] = $v;
    }
    foreach ($where as $c => $v) {
        $cond[] = '`' . preg_replace('/[^a-z0-9_]/', '', $c) . '` = ?';
        $params[] = $v;
    }
    return sdb_q('UPDATE ' . sdb_tb($table) . ' SET ' . implode(', ', $set) . ' WHERE ' . implode(' AND ', $cond), $params)->rowCount();
}

/** ทำงานใน transaction — $fn ทำงานสำเร็จ = COMMIT · โยน exception = ROLLBACK แล้วโยนต่อ
    เรียกซ้อนกันได้ (ชั้นในไม่เปิด transaction ใหม่)
    TODO:
      - [x] START TRANSACTION / COMMIT / ROLLBACK ผ่าน query() เพราะ class DB ของ admweb ไม่มี beginTransaction (UPSTREAM.md D1) */
function sdb_tx($fn)
{
    static $depth = 0;
    if ($depth > 0) {
        return $fn();
    }
    $db = sdb();
    ob_start();
    $ok = $db->query('START TRANSACTION');
    ob_end_clean();
    if ($ok === false) {
        throw new RuntimeException('AOSTOCK DB: เริ่ม transaction ไม่ได้ — ' . $db->error);
    }
    $depth++;
    try {
        $result = $fn();
        $depth--;
        ob_start();
        $ok = $db->query('COMMIT');
        ob_end_clean();
        if ($ok === false) {
            throw new RuntimeException('AOSTOCK DB: COMMIT ไม่สำเร็จ — ' . $db->error);
        }
        return $result;
    } catch (Throwable $e) {
        if ($depth > 0) {
            $depth--;
        }
        ob_start();
        $db->query('ROLLBACK');
        ob_end_clean();
        throw $e;
    }
}

/** หน้า "ระบบขัดข้อง" — ใช้กับ set_exception_handler() ของหน้า AOSTOCK (themes/aostock/include/function.php)
    รายละเอียดเก็บใน error_log เท่านั้น ไม่แสดงบนหน้าเว็บ
    TODO:
      - [x] ตอบ 500 + ข้อความสั้น ๆ */
function stock_error_page($e)
{
    error_log('[AOSTOCK] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><meta name="robots" content="noindex">'
       . '<title>ระบบขัดข้อง | ' . APP_NAME . '</title></head><body>'
       . '<p>ระบบขัดข้องชั่วคราว กรุณาลองใหม่อีกครั้ง — ถ้ายังไม่ได้ แจ้งผู้ดูแลระบบพร้อมเวลาที่เกิดเหตุ ('
       . date('Y-m-d H:i:s') . ')</p><p><a href="' . htmlspecialchars(APP_BASE . '/', ENT_QUOTES, 'UTF-8') . '">กลับหน้าแรก</a></p></body></html>';
}

/* ==========================================================
   ยอดขายช่วงวันที่ — SQL ทั้งช่วงคำสั่งเดียว (ไม่วนอ่านบิลทีละวันทีละสาขา)
   ----------------------------------------------------------
   ยอดขาย = ยอดที่ลูกค้าจ่ายจริงของบิลที่ไม่ยกเลิก (หลังส่วนลดท้ายบิล) · ยังไม่หักรับคืน (เงินคืนแสดงแยกในหน้าบัญชี)
   แถวพื้นฐาน = วัน × สาขา × คนขาย (sales_agg) แล้วแต่ละหน้ารวมต่อเอง · ใช้ดัชนี sale(branch_id, sale_date, status)
   ใช้กับ: รายงานผู้ดูแล 4 หน้า · รายงานยอดขายของพนักงาน · ตารางรายวัน · อีเมลสรุปรายวัน
   ========================================================== */

/** เงื่อนไข IN (?, ?, …) — คืน array(sql, params) · ไม่มีค่า = เงื่อนไขเท็จ (ไม่มีแถว)
    TODO:
      - [x] ช่วงที่ 8: ใช้กับรายการรหัสสาขาของคิวรีรายงาน */
function sdb_in($col, $vals)
{
    $vals = array_values($vals);
    if (!$vals) {
        return array('1 = 0', array());
    }
    return array($col . ' IN (' . implode(', ', array_fill(0, count($vals), '?')) . ')', $vals);
}

/* ==========================================================
   ค่าตั้งแบบ key / value ใช้ร่วมทุกสาขา — การแจ้งเตือน · สวิตช์เปิด–ปิดเมนู · สถานะ cron
   - ค่าที่เป็น array เก็บเป็น JSON (ผู้เรียกแปลงเอง)
   - ค่าลับ (is_secret = 1) เข้ารหัส AES-256-GCM ด้วยกุญแจจาก AOSTOCK_SECRET_KEY ในไฟล์ fix.<โดเมน>.php
     ไม่มีกุญแจ / ไม่มี openssl = บันทึกค่าลับไม่ได้ (stock_secret_ready) · กุญแจเปลี่ยน = อ่านค่าลับเดิมไม่ออก (คืนค่าเริ่มต้น)
   - อ่านทั้งตารางครั้งเดียวต่อ request (static) · เขียนแล้วล้าง cache
   ========================================================== */

/** ทุกแถวของ ao_stock_setting — array( skey => array(svalue, is_secret, updated_at) )
    TODO:
      - [x] ช่วงที่ 9: cache ต่อ request · $reset = true ล้างหลังเขียน */
function stock_setting_rows($reset = false)
{
    static $rows = null;
    if ($reset) {
        $rows = null;
        return array();
    }
    if ($rows === null) {
        $rows = array();
        foreach (sdb_rows('SELECT skey, svalue, is_secret, updated_at FROM ' . sdb_tb('setting')) as $r) {
            $rows[$r['skey']] = $r;
        }
    }
    return $rows;
}

/** ค่าตั้งหนึ่งค่า (string) — ไม่มี / ถอดรหัสไม่ได้ = $def
    TODO:
      - [x] ช่วงที่ 9 */
function stock_setting_get($key, $def = null)
{
    $rows = stock_setting_rows();
    if (!isset($rows[$key])) {
        return $def;
    }
    if ((int) $rows[$key]['is_secret'] === 1 && $rows[$key]['svalue'] !== '') {
        $v = stock_secret_dec($rows[$key]['svalue']);
        return ($v === null) ? $def : $v;
    }
    return (string) $rows[$key]['svalue'];
}

/** บันทึกค่าตั้ง (เพิ่มหรือแทนที่) — $secret = เข้ารหัสก่อนเก็บ (ค่าว่างเก็บว่าง) · กุญแจไม่พร้อม = โยน RuntimeException
    TODO:
      - [x] ช่วงที่ 9: INSERT … ON DUPLICATE KEY UPDATE */
function stock_setting_set($key, $value, $secret = false, $uid = 0)
{
    $v = (string) $value;
    if ($secret && $v !== '') {
        $v = stock_secret_enc($v);
        if ($v === null) {
            throw new RuntimeException('AOSTOCK: บันทึกค่าลับไม่ได้ — ยังไม่ได้ตั้ง AOSTOCK_SECRET_KEY หรือเซิร์ฟเวอร์ไม่มี openssl');
        }
    }
    sdb_q('INSERT INTO ' . sdb_tb('setting') . ' (skey, svalue, is_secret, updated_by, updated_at) VALUES (?, ?, ?, ?, ?)'
        . ' ON DUPLICATE KEY UPDATE svalue = VALUES(svalue), is_secret = VALUES(is_secret), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)',
          array(substr((string) $key, 0, 40), $v, $secret ? 1 : 0, (int) $uid, date('Y-m-d H:i:s')));
    stock_setting_rows(true);
    return true;
}

/** เข้ารหัสค่าลับได้ไหม (มีกุญแจยาวพอ + มี openssl) */
function stock_secret_ready()
{
    return defined('AOSTOCK_SECRET_KEY') && strlen((string) AOSTOCK_SECRET_KEY) >= 16 && function_exists('openssl_encrypt');
}

/** เข้ารหัส → 'v1:' + base64(iv 12 ไบต์ + tag 16 ไบต์ + ข้อมูล) · ไม่พร้อม = null
    TODO:
      - [x] ช่วงที่ 9: AES-256-GCM (กุญแจ = sha256 ของ AOSTOCK_SECRET_KEY) */
function stock_secret_enc($plain)
{
    if (!stock_secret_ready()) {
        return null;
    }
    $iv  = random_bytes(12);
    $tag = '';
    $c   = openssl_encrypt((string) $plain, 'aes-256-gcm', hash('sha256', (string) AOSTOCK_SECRET_KEY, true), OPENSSL_RAW_DATA, $iv, $tag);
    return ($c === false) ? null : 'v1:' . base64_encode($iv . $tag . $c);
}

/** ถอดรหัสค่าจาก stock_secret_enc — กุญแจผิด / ข้อมูลเสีย = null */
function stock_secret_dec($stored)
{
    if (!stock_secret_ready() || strpos((string) $stored, 'v1:') !== 0) {
        return null;
    }
    $raw = base64_decode(substr((string) $stored, 3), true);
    if ($raw === false || strlen($raw) < 29) {
        return null;
    }
    $p = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', hash('sha256', (string) AOSTOCK_SECRET_KEY, true), OPENSSL_RAW_DATA,
                         substr($raw, 0, 12), substr($raw, 12, 16));
    return ($p === false) ? null : $p;
}
