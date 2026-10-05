<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/store.php
 * ROLE: เปิด / ปิดร้านประจำวัน · เงินทอนยกมา · เงินเข้า / ออกลิ้นชัก · เปิดร้านใหม่หลังปิด
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_store_day, ao_stock_cash_move, ao_stock_sale, ao_stock_return, ao_stock_log
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *
 * ⚠ ไฟล์นี้ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb — มีได้แค่ define() และประกาศ function (ห้าม echo / header / query ตอนโหลด)
 */

/* ==========================================================
   เปิด / ปิดร้านประจำวัน และเงินทอนในลิ้นชัก — ao_stock_store_day + ao_stock_cash_move
   ----------------------------------------------------------
   หลักการ
   - ร้านเปิดวันละครั้งต่อสาขา (UNIQUE สาขา + วันที่) ใครมาถึงก่อนเป็นคนเปิด
     คนที่ login ทีหลังเข้าใช้งานได้เลย ไม่ต้องเปิดซ้ำ (สองเครื่องกดพร้อมกัน → แถวเดียว)
   - เงินทอนเริ่มวัน = ยอดที่แยกไว้ตอนปิดร้านครั้งก่อน (keep_cash) + ที่เติมตอนเปิด
     วันก่อนลืมปิดร้าน → ใช้เงินทอนมาตรฐานของสาขา แล้วเตือนให้นับเงินจริง (แจ้งยอดไม่ตรงได้)
   - ระหว่างวันเติม / หยิบเงินได้ (stock_cash_move) · เงินคืนลูกค้า = stock_return.refund ของวันนี้
   - ปิดร้าน: เก็บยอดที่ควรมี + นับได้ + แยกไว้พรุ่งนี้ + นำส่ง ลงแถวของวัน
   - ปิดแล้ว ขาย / ยกเลิกบิล / รับคืน / เติม–หยิบเงินไม่ได้ จนกว่าผู้ดูแลจะเปิดร้านใหม่
   ========================================================== */

/** เงินทอนมาตรฐานของสาขา (ฟิลด์ default_float) */
function branch_default_float($code)
{
    return branch_setting($code, 'default_float');      // ผู้ดูแลตั้งที่หน้า "จัดการสาขา"
}

/** แถวเปิด–ปิดร้านของสาขาในวันที่ $date (Y-m-d · ค่าเริ่มต้นวันนี้) + ชื่อคนเปิด / ปิด หรือ null
    TODO:
      - [x] cache ต่อ request · store_db_reset() ล้างหลังเขียน */
function store_day_row($code, $date = null, $reset = false)
{
    static $cache = array();
    if ($reset) {
        $cache = array();
        return null;
    }
    $date = ($date === null) ? date('Y-m-d') : $date;
    $k    = $code . '|' . $date;
    if (!array_key_exists($k, $cache)) {
        $sql = 'SELECT d.*, o.name AS opened_name, o.username AS opened_user, c.name AS closed_name FROM ' . sdb_tb('store_day') . ' d'
             . ' JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = d.branch_id'
             . ' LEFT JOIN ' . sdb_tb('staff') . ' o ON o.staff_id = d.opened_by'
             . ' LEFT JOIN ' . sdb_tb('staff') . ' c ON c.staff_id = d.closed_by'
             . ' WHERE b.code = ? AND d.store_date = ?';
        $cache[$k] = sdb_row($sql, array((string) $code, $date));
    }
    return $cache[$k];
}

/** ผลเปิด–ปิดร้านของสาขาในวันหนึ่ง (อีเมลสรุปรายวัน / ตัวอย่างข้อความแจ้งเตือน) — ไม่มีการเปิดร้านวันนั้น = null
    คืน array(opened_at, opened_by, float, closed, closed_at, closed_by, expect, counted, diff, keep, handover, note)
    · closed = false ถ้ายังไม่ได้ปิด (ค่าตอนปิดเป็น null)
    TODO:
      - [x] ช่วงที่ 8: อ่านจาก ao_stock_store_day (แทน past_store_events ที่เป็นข้อมูลสมมติ) */
function store_day_result($code, $ts)
{
    $r = store_day_row($code, date('Y-m-d', $ts));
    if ($r === null) {
        return null;
    }
    $closed = ($r['status'] === 'closed' && $r['closed_at'] !== null);
    $num    = function ($v) use ($closed) { return ($closed && $v !== null) ? (float) $v : null; };
    return array(
        'opened_at' => date('H:i', strtotime($r['opened_at'])),
        'opened_by' => ($r['opened_name'] !== null) ? $r['opened_name'] : '-',
        'float'     => (float) $r['open_cash'],
        'closed'    => $closed,
        'closed_at' => $closed ? date('H:i', strtotime($r['closed_at'])) : '',
        'closed_by' => ($closed && $r['closed_name'] !== null) ? $r['closed_name'] : '',
        'expect'    => $num($r['expected_cash']),
        'counted'   => $num($r['counted_cash']),
        'diff'      => ($closed && $r['counted_cash'] !== null && $r['expected_cash'] !== null)
                       ? round((float) $r['counted_cash'] - (float) $r['expected_cash'], 2) : null,
        'keep'      => $num($r['keep_cash']),
        'handover'  => $num($r['handover_cash']),
        'note'      => $r['close_note'],
    );
}

function store_db_reset()
{
    store_day_row('', null, true);
}

/** เงินทอนยกมาสำหรับเปิดร้านวันนี้ — array(amount, by, time, unclosed)
    = keep_cash ของวันล่าสุดก่อนวันนี้ (ถ้าปิดร้านแล้ว) · วันนั้นยังไม่ปิด (ลืมปิด) → เงินทอนมาตรฐาน + unclosed = ปปปปดดวว
    ยังไม่เคยเปิดร้าน → เงินทอนมาตรฐาน (by / time ว่าง)
    TODO:
      - [x] อ่านจาก ao_stock_store_day (เดิมสร้างชื่อ / เวลาสมมติ) */
function store_carry($code)
{
    $out = array('amount' => (float) branch_default_float($code), 'by' => '', 'time' => '', 'unclosed' => '');
    $sql = 'SELECT d.store_date, d.status, d.keep_cash, d.closed_at, c.name AS closed_name FROM ' . sdb_tb('store_day') . ' d'
         . ' JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = d.branch_id'
         . ' LEFT JOIN ' . sdb_tb('staff') . ' c ON c.staff_id = d.closed_by'
         . ' WHERE b.code = ? AND d.store_date < ? ORDER BY d.store_date DESC LIMIT 1';
    $r = sdb_row($sql, array((string) $code, date('Y-m-d')));
    if ($r === null) {
        return $out;
    }
    if ($r['status'] !== 'closed' || $r['keep_cash'] === null) {
        $out['unclosed'] = date('Ymd', strtotime($r['store_date']));
        return $out;
    }
    $out['amount'] = (float) $r['keep_cash'];
    $out['by']     = ($r['closed_name'] !== null) ? $r['closed_name'] : '-';
    $out['time']   = date('j/n H:i', strtotime($r['closed_at']));
    return $out;
}

/** วันก่อนวันนี้ที่ยังไม่ได้ปิดร้าน (ลืมปิด) — array ของ ปปปปดดวว ใหม่สุดก่อน */
function store_unclosed_days($code)
{
    $sql = 'SELECT d.store_date FROM ' . sdb_tb('store_day') . ' d JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = d.branch_id'
         . ' WHERE b.code = ? AND d.store_date < ? AND d.status = \'open\' ORDER BY d.store_date DESC LIMIT 31';
    $out = array();
    foreach (sdb_rows($sql, array((string) $code, date('Y-m-d'))) as $r) {
        $out[] = date('Ymd', strtotime($r['store_date']));
    }
    return $out;
}

/** เงินที่เติม / หยิบออกจากลิ้นชักวันนี้ — array(topup, withdraw)
    TODO:
      - [x] รวมจาก ao_stock_cash_move ของวันนี้ (เดิมข้อมูลสมมติ) */
function store_daily_cash($code)
{
    $st = store_day_row($code);
    if ($st === null) {
        return array('topup' => 0.0, 'withdraw' => 0.0);
    }
    $r = sdb_row('SELECT COALESCE(SUM(CASE WHEN direction = \'in\' THEN amount END), 0) AS i,'
               . ' COALESCE(SUM(CASE WHEN direction = \'out\' THEN amount END), 0) AS o FROM ' . sdb_tb('cash_move') . ' WHERE day_id = ?',
                 array((int) $st['day_id']));
    return array('topup' => (float) $r['i'], 'withdraw' => (float) $r['o']);
}

/** รายการเติม / หยิบเงินของวันนี้ (เก่า → ใหม่) — array(time, dir in|out, amount, reason, by) */
function store_cash_list($code)
{
    $st = store_day_row($code);
    if ($st === null) {
        return array();
    }
    $sql = 'SELECT m.*, s.name AS by_name FROM ' . sdb_tb('cash_move') . ' m LEFT JOIN ' . sdb_tb('staff') . ' s ON s.staff_id = m.created_by'
         . ' WHERE m.day_id = ? ORDER BY m.cash_id';
    $out = array();
    foreach (sdb_rows($sql, array((int) $st['day_id'])) as $r) {
        $out[] = array('time' => date('H:i', strtotime($r['add_date'])), 'dir' => $r['direction'], 'amount' => (float) $r['amount'],
                       'reason' => $r['reason'], 'by' => ($r['by_name'] !== null) ? $r['by_name'] : '-');
    }
    return $out;
}

/**
 * สถานะร้านของสาขาวันนี้ — null = ยังไม่เปิด
 * คืน array(id, branch, opened_at, opened_by, opened_user, carry, topup, float, counted, reason,
 *           closed_at (ว่าง = ยังเปิดอยู่), closed_by, cash_counted, keep, note, expected,
 *           reopen_count, last_diff = ขาด/เกินของรอบที่ปิดไปก่อนเปิดใหม่ (null ถ้าไม่มี))
 * TODO:
 *   - [x] อ่านจาก ao_stock_store_day (เดิม $_SESSION['store'])
 */
function store_state($code)
{
    $r = store_day_row($code);
    if ($r === null) {
        return null;
    }
    $open   = ($r['status'] === 'open');
    $closed = !$open;
    $hasRnd = ($r['counted_cash'] !== null && $r['expected_cash'] !== null);
    return array(
        'id'           => (int) $r['day_id'],
        'branch'       => $code,
        'opened_at'    => date('H:i', strtotime($r['opened_at'])),
        'opened_by'    => ($r['opened_name'] !== null) ? $r['opened_name'] : '-',
        'opened_user'  => ($r['opened_user'] !== null) ? $r['opened_user'] : '',
        'carry'        => (float) $r['float_carried'],
        'topup'        => (float) $r['float_topup'],
        'float'        => (float) $r['open_cash'],
        'counted'      => ($r['float_counted'] !== null) ? (float) $r['float_counted'] : null,
        'reason'       => $r['open_reason'],
        'closed_at'    => ($closed && $r['closed_at'] !== null) ? date('H:i', strtotime($r['closed_at'])) : '',
        'closed_by'    => ($r['closed_name'] !== null) ? $r['closed_name'] : '',
        'cash_counted' => ($r['counted_cash'] !== null) ? (float) $r['counted_cash'] : 0.0,
        'keep'         => ($r['keep_cash'] !== null) ? (float) $r['keep_cash'] : 0.0,
        'note'         => $r['close_note'],
        'expected'     => ($r['expected_cash'] !== null) ? (float) $r['expected_cash'] : null,
        'reopen_count' => (int) $r['reopen_count'],
        'last_diff'    => ($open && (int) $r['reopen_count'] > 0 && $hasRnd) ? (float) $r['counted_cash'] - (float) $r['expected_cash'] : null,
    );
}

function store_is_open($code)
{
    $st = store_state($code);
    return $st !== null && $st['closed_at'] === '';
}

function store_is_closed($code)
{
    $st = store_state($code);
    return $st !== null && $st['closed_at'] !== '';
}

/**
 * บันทึกการเปิดร้าน — คืนสถานะร้าน (อีกเครื่องเปิดไปก่อนแล้ว = สถานะของเครื่องนั้น ไม่ลงประวัติซ้ำ)
 * $counted = เงินทอนที่นับได้จริงเมื่อไม่ตรงกับที่ยกมา (null = ตรง)
 * TODO:
 *   - [x] INSERT ao_stock_store_day + ประวัติ ในทรานแซกชันเดียว
 */
function store_open($code, $user, $topup, $counted, $reason)
{
    $bid = branch_id_of($code);
    if ($bid <= 0) {
        return null;
    }
    $carry = store_carry($code);
    $topup = max(0, min(1000000, (float) $topup));
    $cnt   = ($counted !== null && $counted !== '' && (float) $counted != $carry['amount']) ? max(0, (float) $counted) : null;
    $float = ($cnt !== null ? $cnt : $carry['amount']) + $topup;
    $now   = date('Y-m-d H:i:s');

    $opened = sdb_tx(function () use ($code, $bid, $user, $carry, $topup, $cnt, $float, $reason, $now) {
        /* ล็อกแถวสาขาก่อน — สองเครื่องกดเปิดพร้อมกัน เครื่องที่สองรอแล้วเจอแถวของวันนี้ (ไม่ INSERT ชน UNIQUE
           ซึ่ง class DB ของ admweb จะเขียน error_log ทุกครั้ง) */
        sdb_val('SELECT branch_id FROM ' . sdb_tb('branch') . ' WHERE branch_id = ? FOR UPDATE', array($bid));
        if (sdb_val('SELECT day_id FROM ' . sdb_tb('store_day') . ' WHERE branch_id = ? AND store_date = ? FOR UPDATE', array($bid, date('Y-m-d'))) !== null) {
            return false;
        }
        sdb_insert('store_day', array(
            'branch_id'     => $bid,
            'store_date'    => date('Y-m-d'),
            'status'        => 'open',
            'opened_by'     => stock_uid($user),
            'opened_at'     => $now,
            'float_carried' => $carry['amount'],
            'float_counted' => $cnt,
            'open_reason'   => ($cnt !== null) ? stock_cut($reason, 255) : '',
            'float_topup'   => $topup,
            'open_cash'     => $float,
        ));

        /* ---- เก็บลงประวัติการทำรายการ ---- */
        $detail = array('เวลาที่เปิด' => date('H:i') . ' น.');
        if ($carry['unclosed'] !== '') {
            $detail['เงินทอนยกมา'] = money2($carry['amount']) . ' บาท (เงินทอนมาตรฐาน — ' . thai_date_full(strtotime($carry['unclosed'])) . ' ยังไม่ได้ปิดร้าน)';
        } elseif ($carry['by'] !== '') {
            $detail['เงินทอนยกมา'] = money2($carry['amount']) . ' บาท (ปิดร้านครั้งก่อนโดย ' . $carry['by'] . ' · ' . $carry['time'] . ' น.)';
        } else {
            $detail['เงินทอนยกมา'] = money2($carry['amount']) . ' บาท (เงินทอนมาตรฐานของสาขา)';
        }
        if ($cnt !== null) {
            $diff = $cnt - $carry['amount'];
            $detail['นับได้จริง']   = money2($cnt) . ' บาท';
            $detail['ผลต่างจากยกมา'] = ($diff > 0 ? '+' : '') . money2($diff) . ' บาท' . (trim($reason) !== '' ? ' — ' . trim($reason) : '');
        }
        $detail['เติมเงินทอนเพิ่ม']  = $topup > 0 ? '+' . money2($topup) . ' บาท' : 'ไม่ได้เติม';
        $detail['เงินทอนเริ่มวันนี้'] = money2($float) . ' บาท';
        log_add($code, 'open', $user, 'เปิดร้าน ' . branch_name($code), $detail, $float);
        return true;
    });
    store_db_reset();
    if ($opened === true) {
        notify_on_open($code);                    // Telegram: เปิดร้าน + สินค้าใกล้หมด (ช่วงที่ 9)
    }
    return store_state($code);
}

/** ยอดเงินที่ควรมีในลิ้นชักตอนนี้
    = เงินทอนเริ่มวัน + เติม + ขายเงินสด − หยิบออก − คืนเงินลูกค้า · ปิดร้านแล้ว = ยอดที่บันทึกไว้ตอนปิด */
function store_expected_cash($code)
{
    $st = store_state($code);
    if ($st === null) {
        return 0;
    }
    if ($st['closed_at'] !== '' && $st['expected'] !== null) {
        return $st['expected'];
    }
    $c = store_daily_cash($code);
    return round($st['float'] + $c['topup'] + store_cash_sales($code) - $c['withdraw'] - store_refunds($code), 2);
}

/**
 * บันทึกการปิดร้าน — เงินที่แยกไว้พรุ่งนี้ไม่เกินเงินที่นับได้ · นำส่ง = นับได้ − แยกไว้
 * คืนสถานะร้าน หรือ null ถ้าร้านไม่ได้เปิดอยู่
 * TODO:
 *   - [x] ล็อกแถวของวัน (บิล / รับคืนที่กำลังบันทึกต้องรอ แล้วเจอว่าร้านปิด) + UPDATE + ประวัติ
 */
function store_close($code, $user, $counted, $keep, $note)
{
    $st = store_state($code);
    if ($st === null || $st['closed_at'] !== '') {
        return null;
    }
    $ok = sdb_tx(function () use ($code, $st, $user, $counted, $keep, $note) {
        $s = sdb_val('SELECT status FROM ' . sdb_tb('store_day') . ' WHERE day_id = ? FOR UPDATE', array($st['id']));
        if ($s !== 'open') {
            return false;
        }
        $expected = store_expected_cash($code);
        $counted  = max(0, round((float) $counted, 2));
        $keep     = max(0, min(round((float) $keep, 2), $counted));
        $note     = stock_cut($note, 255);
        sdb_update('store_day', array(
            'status'        => 'closed',
            'closed_by'     => stock_uid($user),
            'closed_at'     => date('Y-m-d H:i:s'),
            'expected_cash' => $expected,
            'counted_cash'  => $counted,
            'keep_cash'     => $keep,
            'handover_cash' => round($counted - $keep, 2),
            'close_note'    => $note,
        ), array('day_id' => $st['id']));

        /* ---- เก็บลงประวัติการทำรายการ ---- */
        $diff   = round($counted - $expected, 2);
        $detail = array(
            'เวลาที่ปิด'      => date('H:i') . ' น.',
            'เงินที่ควรมี'     => money2($expected) . ' บาท',
            'นับได้จริง'      => money2($counted) . ' บาท',
            'ผลต่าง'         => ($diff > 0 ? 'เกิน +' : ($diff < 0 ? 'ขาด −' : 'ตรงพอดี ')) . money2(abs($diff)) . ' บาท',
            'เงินทอนที่แยกไว้พรุ่งนี้' => money2($keep) . ' บาท',
            'นำส่ง'          => money2($counted - $keep) . ' บาท',
        );
        if ($note !== '') {
            $detail['หมายเหตุ'] = $note;
        }
        log_add($code, 'close', $user, 'ปิดร้าน ' . branch_name($code), $detail, $counted);
        return true;
    });
    store_db_reset();
    if ($ok) {
        notify_on_close($code);                   // Telegram: ปิดร้าน + เงินขาด / เกิน (ช่วงที่ 9)
    }
    return $ok ? store_state($code) : null;
}

/** เปิดร้านใหม่หลังปิดไปแล้ว — ผู้ดูแลเท่านั้น ต้องมีเหตุผล
    ผลของรอบที่ปิดไป (นับได้ / ควรมี) ยังอยู่ในแถวและในประวัติ จนกว่าจะปิดใหม่อีกครั้ง
    TODO:
      - [x] status open + reopen_count + 1 ใน ao_stock_store_day */
function store_reopen($code, $user, $reason)
{
    $st = store_state($code);
    if ($st === null || $st['closed_at'] === '') {
        return null;
    }
    $ok = sdb_tx(function () use ($code, $st, $user, $reason) {
        $s = sdb_val('SELECT status FROM ' . sdb_tb('store_day') . ' WHERE day_id = ? FOR UPDATE', array($st['id']));
        if ($s !== 'closed') {
            return false;
        }
        sdb_q('UPDATE ' . sdb_tb('store_day') . ' SET status = \'open\', reopen_count = reopen_count + 1 WHERE day_id = ?', array($st['id']));
        log_add($code, 'open', $user, 'เปิดร้านใหม่หลังปิด ' . branch_name($code), array(
            'ปิดไปเมื่อ'          => $st['closed_at'] . ' น. โดย ' . $st['closed_by'],
            'เงินที่นับได้ตอนปิด' => money2($st['cash_counted']) . ' บาท',
            'เหตุผล'             => trim($reason),
            'ผู้เปิดใหม่'          => $user['name'] . ' (ผู้ดูแล)',
        ));
        return true;
    });
    store_db_reset();
    if ($ok) {
        notify_on_reopen($code, $user, $reason, $st);    // Telegram (ช่วงที่ 9)
    }
    return $ok ? store_state($code) : null;
}

/**
 * เติมเงินทอน (in) / หยิบเงินออก (out) ระหว่างวัน — ร้านต้องเปิดอยู่ · ต้องมีเหตุผล · หยิบเกินเงินที่ควรมีในลิ้นชักไม่ได้
 * คืน array('ok' => true) หรือ array('error' => ข้อความ)
 * TODO:
 *   - [x] INSERT ao_stock_cash_move + ประวัติ (ชนิด cash)
 */
function store_cash_add($code, $user, $dir, $amount, $reason)
{
    $dir    = ($dir === 'out') ? 'out' : 'in';
    $amount = round((float) $amount, 2);
    $reason = stock_cut($reason, 255);
    if ($amount <= 0 || $amount > 1000000) {
        return array('error' => 'กรุณากรอกจำนวนเงินมากกว่า 0');
    }
    if ($reason === '') {
        return array('error' => 'กรุณาระบุเหตุผลของการ' . ($dir === 'in' ? 'เติมเงินทอน' : 'หยิบเงินออก'));
    }
    $st = store_state($code);
    if ($st === null || $st['closed_at'] !== '') {
        return array('error' => 'ร้านไม่ได้เปิดอยู่ — เติม / หยิบเงินได้เฉพาะตอนร้านเปิด');
    }
    $err = sdb_tx(function () use ($code, $st, $user, $dir, $amount, $reason) {
        $s = sdb_val('SELECT status FROM ' . sdb_tb('store_day') . ' WHERE day_id = ? FOR UPDATE', array($st['id']));
        if ($s !== 'open') {
            return 'ร้านปิดไปแล้ว';
        }
        if ($dir === 'out' && $amount > store_expected_cash($code)) {
            return 'หยิบเงินออกได้ไม่เกินเงินที่ควรมีในลิ้นชัก (' . money2(store_expected_cash($code)) . ' บาท)';
        }
        sdb_insert('cash_move', array(
            'day_id'     => $st['id'],
            'branch_id'  => branch_id_of($code),
            'direction'  => $dir,
            'amount'     => $amount,
            'reason'     => $reason,
            'created_by' => stock_uid($user),
            'add_date'   => date('Y-m-d H:i:s'),
        ));
        log_add($code, 'cash', $user, ($dir === 'in' ? 'เติมเงินทอน ' : 'หยิบเงินออกจากลิ้นชัก ') . money2($amount) . ' บาท', array(
            'จำนวน'     => ($dir === 'in' ? '+' : '−') . money2($amount) . ' บาท',
            'เหตุผล'    => $reason,
            'ผู้ทำรายการ' => $user['name'] . ' · ' . branch_name($code),
        ), $dir === 'in' ? $amount : -$amount);
        return '';
    });
    return ($err === '') ? array('ok' => true) : array('error' => $err);
}

/** เงินสดที่คืนลูกค้าวันนี้ (รับคืนสินค้า) — จ่ายออกจากลิ้นชักของวันนี้เสมอ แม้บิลเดิมจะเป็นของวันก่อน
    TODO:
      - [x] รวม stock_return.refund ของวันนี้ (day_id) */
function store_refunds($code)
{
    $st = store_day_row($code);
    if ($st === null) {
        return 0.0;
    }
    return (float) sdb_val('SELECT COALESCE(SUM(refund), 0) FROM ' . sdb_tb('return') . ' WHERE day_id = ?', array((int) $st['day_id']));
}

/** ยอดขายเงินสดวันนี้ (ไม่นับบิลที่ยกเลิก · บิลโอน/พร้อมเพย์ไม่เข้าลิ้นชัก) */
function store_cash_sales($code)
{
    $st = store_day_row($code);
    if ($st === null) {
        return 0.0;
    }
    return (float) sdb_val('SELECT COALESCE(SUM(total), 0) FROM ' . sdb_tb('sale')
                         . ' WHERE day_id = ? AND status = \'paid\' AND pay_method = \'cash\'', array((int) $st['day_id']));
}
