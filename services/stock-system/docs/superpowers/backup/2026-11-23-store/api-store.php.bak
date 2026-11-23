<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/store.php
 * ROLE: เปิด / ปิดร้านประจำวัน · เงินทอนยกมา · เงินเข้า / ออกลิ้นชัก · บัญชีโอนของสาขา (ลิ้นชักที่สอง) · เปิดร้านใหม่หลังปิด
 * DEPENDS: api.php (ตัวโหลด — ห้าม include ตรง)
 * TABLES: ao_stock_store_day, ao_stock_cash_move, ao_stock_sale, ao_stock_return, ao_stock_log
 * TODO:
 *   - [x] ประวัติการแก้ไขดู docs/CHANGELOG.md
 * ⚠ โหลดทุก request — มีได้แค่ define() + function (ห้าม echo / header / query ตอนโหลด)
 */

/* ---------- เปิด / ปิดร้านประจำวัน + เงินในลิ้นชัก: วันละแถวต่อสาขา (เปิดก่อนได้ก่อน) · ปิดแล้วขาย / คืน / เติม–หยิบไม่ได้จนเปิดใหม่ ---------- */

/** เงินทอนมาตรฐานของสาขา (ฟิลด์ default_float) */
function branch_default_float($code)
{
    return branch_setting($code, 'default_float');      // ผู้ดูแลตั้งที่หน้า "จัดการสาขา"
}

/** แถวเปิด–ปิดร้านของสาขาในวันที่ $date (Y-m-d · ค่าเริ่มต้นวันนี้) + ชื่อคนเปิด / ปิด หรือ null */
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

/** ผลเปิด–ปิดร้านของสาขาในวันหนึ่ง — ไม่มีการเปิดร้าน = null */
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
        /* ปิดย้อนหลัง → เวลาปิดอยู่คนละวัน ใส่วันที่ด้วย เช่น 7/10 09:10 */
        'closed_at' => $closed ? date(substr($r['closed_at'], 0, 10) === $r['store_date'] ? 'H:i' : 'j/n H:i', strtotime($r['closed_at'])) : '',
        'closed_by' => ($closed && $r['closed_name'] !== null) ? $r['closed_name'] : '',
        'expect'    => $num($r['expected_cash']),
        'counted'   => $num($r['counted_cash']),
        'diff'      => ($closed && $r['counted_cash'] !== null && $r['expected_cash'] !== null)
                       ? round((float) $r['counted_cash'] - (float) $r['expected_cash'], 2) : null,
        'keep'      => $num($r['keep_cash']),
        'handover'  => $num($r['handover_cash']),
        'note'      => $r['close_note'],
        /* บัญชีโอน (null = วันนั้นไม่มีบัญชีโอน · ปิดย้อนหลังเว้นยอดในแอปได้) */
        'bank_expect'  => isset($r['bank_expected']) ? $num($r['bank_expected']) : null,
        'bank_counted' => isset($r['bank_counted']) ? $num($r['bank_counted']) : null,
        'bank_diff'    => ($closed && isset($r['bank_counted'], $r['bank_expected'])) ? round((float) $r['bank_counted'] - (float) $r['bank_expected'], 2) : null,
        /* ตอนเปิดร้าน: ยอดยกมา · ยอดในแอป (กด “ไม่ตรง”) · เหตุผล — Telegram เปิดร้าน */
        'bank_carry'        => isset($r['bank_carried']) ? (float) $r['bank_carried'] : null,
        'bank_open_counted' => isset($r['bank_open_counted']) ? (float) $r['bank_open_counted'] : null,
        'bank_open_reason'  => isset($r['bank_open_reason']) ? $r['bank_open_reason'] : '',
    );
}

function store_db_reset()
{
    store_day_row('', null, true);
}

/** เงินทอนยกมาสำหรับเปิดร้านวันนี้ — array(amount, by, time, unclosed) */
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

/** เงินสดที่เติม / หยิบออกจากลิ้นชักวันนี้ — array(topup, withdraw) (ไม่นับแบบโอน) */
function store_daily_cash($code)
{
    $st = store_day_row($code);
    if ($st === null) {
        return array('topup' => 0.0, 'withdraw' => 0.0);
    }
    $s = Stock_getDayChannelSums((int) $st['day_id'], 'cash');
    return array('topup' => $s['topup'], 'withdraw' => $s['withdraw']);
}

/** รายการเติม / หยิบเงินของวันนี้ (เก่า → ใหม่) — array(time, dir in|out, amount, reason, by, channel cash|transfer) */
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
                       'reason' => $r['reason'], 'by' => ($r['by_name'] !== null) ? $r['by_name'] : '-',
                       'channel' => isset($r['channel']) ? $r['channel'] : 'cash');
    }
    return $out;
}

/** สถานะร้านของสาขาวันนี้ — null = ยังไม่เปิด */
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
        /* บัญชีโอน — ค่าดิบของวัน (ตัวเลขเต็มดู Stock_getDayBank) · bank_carried null = วันนี้ไม่มีบัญชีโอน */
        'bank_carried'      => isset($r['bank_carried']) ? (float) $r['bank_carried'] : null,
        'bank_open_counted' => isset($r['bank_open_counted']) ? (float) $r['bank_open_counted'] : null,
        'bank_open_reason'  => isset($r['bank_open_reason']) ? $r['bank_open_reason'] : '',
        'bank_expected'     => isset($r['bank_expected']) ? (float) $r['bank_expected'] : null,
        'bank_counted'      => isset($r['bank_counted']) ? (float) $r['bank_counted'] : null,
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

/** บันทึกการเปิดร้าน · $counted = เงินทอนที่นับได้เมื่อไม่ตรง (null = ตรง) · คืนสถานะร้าน
 *  $bankCounted = ยอดในแอปธนาคารเมื่อไม่ตรงกับยอดบัญชีโอนยกมา (null = ตรง) · $bankReason = เหตุผล (หน้าเปิดร้านตรวจว่าต้องมี) */
function store_open($code, $user, $topup, $counted, $reason, $bankCounted = null, $bankReason = '')
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
    $bank  = Stock_getBankCarry($code);
    $bcnt  = ($bankCounted !== null && $bankCounted !== '' && abs((float) $bankCounted - $bank['amount']) >= 0.005) ? round((float) $bankCounted, 2) : null;

    $opened = sdb_tx(function () use ($code, $bid, $user, $carry, $topup, $cnt, $float, $reason, $now, $bank, $bcnt, $bankReason) {
        /* ล็อกแถวสาขาก่อน — สองเครื่องกดพร้อมกันไม่ INSERT ชน UNIQUE (class DB ของ admweb จะเขียน error_log) */
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
            'bank_carried'      => $bank['amount'],
            'bank_open_counted' => $bcnt,
            'bank_open_reason'  => ($bcnt !== null) ? stock_cut($bankReason, 255) : '',
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
        $detail['ยอดบัญชีโอนยกมา'] = money2($bank['amount']) . ' บาท' . ($bank['from'] === 'start' ? ' (ยอดตั้งต้นของสาขา)' : '');
        if ($bcnt !== null) {
            $bd = round($bcnt - $bank['amount'], 2);
            $detail['ยอดในแอปตอนเปิด']       = money2($bcnt) . ' บาท';
            $detail['เงินขยับระหว่างร้านปิด'] = ($bd > 0 ? '+' : '−') . money2(abs($bd)) . ' บาท' . (trim($bankReason) !== '' ? ' — ' . trim($bankReason) : '');
        }
        log_add($code, 'open', $user, 'เปิดร้าน ' . branch_name($code), $detail, $float);
        return true;
    });
    store_db_reset();
    if ($opened === true) {
        notify_on_open($code);                    // Telegram: เปิดร้าน + สินค้าใกล้หมด
    }
    return store_state($code);
}

/** ยอดเงินที่ควรมีในลิ้นชักตอนนี้ (ปิดแล้ว = ยอดที่บันทึกตอนปิด) */
function store_expected_cash($code)
{
    $st = store_state($code);
    if ($st === null) {
        return 0;
    }
    if ($st['closed_at'] !== '' && $st['expected'] !== null) {
        return $st['expected'];
    }
    $c = Stock_getDayCash($st['id']);
    return $c['expected'];
}

/** เงินในลิ้นชักของวันหนึ่ง (วันนี้หรือวันก่อน) — array(float, topup, withdraw, cash_in, cash_out, sales, refunds, void_refunds, expected)
 *  sales = รับเงินสด − ทอนเงินสดจริง (บิลที่ทอนเป็นโอนได้เงินสดเต็ม · 23 พ.ย.) · บิลเก่าได้ค่าเท่าเดิม (รับ − ทอน = ยอดบิล) */
function Stock_getDayCash($dayId)
{
    $dayId = (int) $dayId;
    $float = (float) sdb_val('SELECT open_cash FROM ' . sdb_tb('store_day') . ' WHERE day_id = ?', array($dayId));
    $s     = Stock_getDayChannelSums($dayId, 'cash');
    $c     = array('float' => $float, 'topup' => $s['topup'], 'withdraw' => $s['withdraw'], 'cash_in' => $s['in'], 'cash_out' => $s['out'],
                   'sales' => round($s['in'] - $s['out'], 2), 'refunds' => $s['refunds'], 'void_refunds' => $s['void_refunds']);
    $c['expected'] = round($float + $c['topup'] - $c['withdraw'] + $c['sales'] - $c['refunds'] - $c['void_refunds'], 2);
    return $c;
}

/** ผลรวมเงินของช่องทางหนึ่งในวันหนึ่ง ($channel = cash | transfer) — array(topup, withdraw, in รับจากขาย, out ทอน, refunds คืนจากรับคืน, void_refunds คืนจากยกเลิกบิล)
 *  บิลที่นับ = paid + ยกเลิกแบบเลือกช่องทางคืน (void_refund_channel) · บิลยกเลิกเก่า / ยกเลิกเพื่อแก้ไขไม่นับทั้งใบ (เหมือนเดิม)
 *  TODO:
 *    - [x] 23 พ.ย. (งานเงินโอน ช่วง 4): ลิ้นชัก (Stock_getDayCash) กับบัญชีโอน (Stock_getDayBank) ใช้ตัวนี้ร่วมกัน */
function Stock_getDayChannelSums($dayId, $channel)
{
    $ch  = ($channel === 'transfer') ? 'transfer' : 'cash';
    $q   = Stock_buildPaySql('s');
    $in  = ($ch === 'cash') ? $q['cash_in'] : $q['tr_in'];
    $out = ($ch === 'cash') ? $q['cash_out'] : $q['tr_out'];
    $m   = sdb_row('SELECT COALESCE(SUM(CASE WHEN direction = \'in\' THEN amount END), 0) AS i,'
                 . ' COALESCE(SUM(CASE WHEN direction = \'out\' THEN amount END), 0) AS o FROM ' . sdb_tb('cash_move') . ' WHERE day_id = ? AND channel = ?',
                   array((int) $dayId, $ch));
    $s   = sdb_row('SELECT COALESCE(SUM(' . $in . '), 0) AS i, COALESCE(SUM(' . $out . '), 0) AS o,'
                 . ' COALESCE(SUM(CASE WHEN s.status = \'void\' AND s.void_refund_channel = ? THEN s.total END), 0) AS v'
                 . ' FROM ' . sdb_tb('sale') . ' s WHERE s.day_id = ? AND (s.status = \'paid\' OR s.void_refund_channel IS NOT NULL)',
                   array($ch, (int) $dayId));
    $ref = (float) sdb_val('SELECT COALESCE(SUM(refund), 0) FROM ' . sdb_tb('return') . ' WHERE day_id = ? AND refund_channel = ?', array((int) $dayId, $ch));
    return array('topup' => (float) $m['i'], 'withdraw' => (float) $m['o'], 'in' => (float) $s['i'], 'out' => (float) $s['o'],
                 'refunds' => $ref, 'void_refunds' => (float) $s['v']);
}

/** บัญชีโอนของสาขาในวันหนึ่ง — null = วันนั้นไม่มีบัญชีโอน (เปิดก่อนเปิดใช้) · ไม่งั้น array(carried, open_counted, start, topup, withdraw, tr_in, tr_out, refunds, void_refunds, expected)
 *  start = ยอดในแอปตอนเปิด (กด “ไม่ตรง”) หรือยอดยกมา
 *  TODO:
 *    - [x] 23 พ.ย. (งานเงินโอน ช่วง 4): ลิ้นชักที่สองของสาขา (แบบ ก. ที่ผู้ใช้เลือก) */
function Stock_getDayBank($dayId)
{
    $r = sdb_row('SELECT bank_carried, bank_open_counted FROM ' . sdb_tb('store_day') . ' WHERE day_id = ?', array((int) $dayId));
    if ($r === null || $r['bank_carried'] === null) {
        return null;
    }
    $s = Stock_getDayChannelSums((int) $dayId, 'transfer');
    $b = array('carried' => (float) $r['bank_carried'], 'open_counted' => ($r['bank_open_counted'] !== null) ? (float) $r['bank_open_counted'] : null);
    $b['start'] = ($b['open_counted'] !== null) ? $b['open_counted'] : $b['carried'];
    $b += array('topup' => $s['topup'], 'withdraw' => $s['withdraw'], 'tr_in' => $s['in'], 'tr_out' => $s['out'],
                'refunds' => $s['refunds'], 'void_refunds' => $s['void_refunds']);
    $b['expected'] = round($b['start'] + $b['topup'] - $b['withdraw'] + $b['tr_in'] - $b['tr_out'] - $b['refunds'] - $b['void_refunds'], 2);
    return $b;
}

/** ยอดบัญชีโอนยกมาสำหรับเปิดร้านวันนี้ — array(amount, from day|start, ymd)
 *  วันก่อนมีตัวเลขบัญชี = ยอดในแอปตอนปิด (เว้นไว้ = ควรมี) · ไม่เคยมี = ยอดตั้งต้นของสาขา (bank_start)
 *  TODO:
 *    - [x] 23 พ.ย. (งานเงินโอน ช่วง 4): ยอดยกไปทั้งก้อน ไม่มีการแยกเก็บแบบเงินทอน */
function Stock_getBankCarry($code)
{
    $br  = branch_row($code);
    $out = array('amount' => ($br !== null && isset($br['bank_start'])) ? (float) $br['bank_start'] : 0.0, 'from' => 'start', 'ymd' => '');
    $r   = sdb_row('SELECT d.day_id, d.store_date, d.status, d.bank_carried, d.bank_expected, d.bank_counted FROM ' . sdb_tb('store_day') . ' d'
                 . ' JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = d.branch_id WHERE b.code = ? AND d.store_date < ? ORDER BY d.store_date DESC LIMIT 1',
                   array((string) $code, date('Y-m-d')));
    if ($r === null) {
        return $out;
    }
    if ($r['bank_counted'] !== null || $r['bank_expected'] !== null) {
        $out = array('amount' => (float) (($r['bank_counted'] !== null) ? $r['bank_counted'] : $r['bank_expected']), 'from' => 'day', 'ymd' => date('Ymd', strtotime($r['store_date'])));
    } elseif ($r['status'] === 'open' && $r['bank_carried'] !== null) {
        $b   = Stock_getDayBank((int) $r['day_id']);             // กันไว้ — ปกติเปิดวันใหม่ไม่ได้จนกว่าจะปิดวันค้าง
        $out = array('amount' => $b['expected'], 'from' => 'day', 'ymd' => date('Ymd', strtotime($r['store_date'])));
    }
    return $out;
}

/** สาขานี้ปิดร้านพร้อมตัวเลขบัญชีโอนแล้วหรือยัง — แล้ว = ยอดตั้งต้น (bank_start) ไม่มีผลอีก ปรับยอดด้วยเติม / หยิบแบบโอน
 *  TODO:
 *    - [x] 23 พ.ย. (งานเงินโอน ช่วง 8): หน้าแก้สาขาล็อกช่องยอดตั้งต้น */
function Stock_isBankStartUsed($code)
{
    return sdb_val('SELECT 1 FROM ' . sdb_tb('store_day') . ' d JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = d.branch_id'
                 . ' WHERE b.code = ? AND (d.bank_counted IS NOT NULL OR d.bank_expected IS NOT NULL) LIMIT 1', array((string) $code)) !== null;
}

/** ผู้ดูแลตั้งยอดตั้งต้นของบัญชีโอน —สาขาที่ยังไม่เคยปิดร้านพร้อมตัวเลขบัญชี: ยอดยกมาของวันที่เปิดอยู่ตามค่าใหม่ด้วย (แก้ได้ในวันแรก)
 *  TODO:
 *    - [x] 23 พ.ย. (งานเงินโอน ช่วง 4): หน้าเพิ่ม / แก้สาขาเรียกตัวนี้ (ช่วง 8) */
function Stock_setBankStart($code, $amount)
{
    $bid = branch_id_of($code);
    if ($bid <= 0) {
        return;
    }
    $rule   = branch_setting_rules();
    $amount = round(max($rule['bank_start'][0], min($rule['bank_start'][1], (float) $amount)), 2);
    sdb_update('branch', array('bank_start' => $amount), array('branch_id' => $bid));
    branch_db_rows(true);
    if (!Stock_isBankStartUsed($code)) {
        sdb_q('UPDATE ' . sdb_tb('store_day') . ' SET bank_carried = ? WHERE branch_id = ? AND status = \'open\' AND bank_carried IS NOT NULL', array($amount, $bid));
        store_db_reset();
    }
}

/** รายการเงินเข้า-ออกบัญชีโอนของวันหนึ่ง เรียงเวลา (ไว้เทียบกับแอปธนาคาร)
 *  แถว: array(time, kind sale|change|void|refund|topup|withdraw, ref เลขบิล / ใบคืน / เหตุผล, dir in|out, amount, by)
 *  TODO:
 *    - [x] 23 พ.ย. (งานเงินโอน ช่วง 4): การ์ดบัญชีโอนตอนปิดร้าน */
function Stock_getTransferList($dayId)
{
    $dayId = (int) $dayId;
    $q     = Stock_buildPaySql('s');
    $rows  = array();
    $sql   = 'SELECT s.doc_no, s.add_date, s.void_date, s.status, s.void_refund_channel, s.total, ' . $q['tr_in'] . ' AS ti, ' . $q['tr_out'] . ' AS tro,'
           . ' u.name AS by_name, v.name AS void_name FROM ' . sdb_tb('sale') . ' s'
           . ' LEFT JOIN ' . sdb_tb('staff') . ' u ON u.staff_id = s.created_by LEFT JOIN ' . sdb_tb('staff') . ' v ON v.staff_id = s.void_by'
           . ' WHERE s.day_id = ? AND (s.status = \'paid\' OR s.void_refund_channel IS NOT NULL)';
    foreach (sdb_rows($sql, array($dayId)) as $r) {
        $by = ($r['by_name'] !== null) ? $r['by_name'] : '-';
        if ((float) $r['ti'] > 0) {
            $rows[] = array('ts' => $r['add_date'], 'kind' => 'sale', 'ref' => $r['doc_no'], 'dir' => 'in', 'amount' => (float) $r['ti'], 'by' => $by);
        }
        if ((float) $r['tro'] > 0) {
            $rows[] = array('ts' => $r['add_date'], 'kind' => 'change', 'ref' => $r['doc_no'], 'dir' => 'out', 'amount' => (float) $r['tro'], 'by' => $by);
        }
        if ($r['status'] === 'void' && $r['void_refund_channel'] === 'transfer') {
            $rows[] = array('ts' => $r['void_date'], 'kind' => 'void', 'ref' => $r['doc_no'], 'dir' => 'out', 'amount' => (float) $r['total'],
                            'by' => ($r['void_name'] !== null) ? $r['void_name'] : '-');
        }
    }
    $sql = 'SELECT r.doc_no, r.add_date, r.refund, u.name AS by_name FROM ' . sdb_tb('return') . ' r LEFT JOIN ' . sdb_tb('staff') . ' u ON u.staff_id = r.created_by'
         . ' WHERE r.day_id = ? AND r.refund_channel = \'transfer\' AND r.refund > 0';
    foreach (sdb_rows($sql, array($dayId)) as $r) {
        $rows[] = array('ts' => $r['add_date'], 'kind' => 'refund', 'ref' => $r['doc_no'], 'dir' => 'out', 'amount' => (float) $r['refund'],
                        'by' => ($r['by_name'] !== null) ? $r['by_name'] : '-');
    }
    $sql = 'SELECT m.add_date, m.direction, m.amount, m.reason, u.name AS by_name FROM ' . sdb_tb('cash_move') . ' m LEFT JOIN ' . sdb_tb('staff') . ' u ON u.staff_id = m.created_by'
         . ' WHERE m.day_id = ? AND m.channel = \'transfer\'';
    foreach (sdb_rows($sql, array($dayId)) as $r) {
        $rows[] = array('ts' => $r['add_date'], 'kind' => ($r['direction'] === 'in') ? 'topup' : 'withdraw', 'ref' => $r['reason'],
                        'dir' => $r['direction'], 'amount' => (float) $r['amount'], 'by' => ($r['by_name'] !== null) ? $r['by_name'] : '-');
    }
    usort($rows, function ($a, $b) { return strcmp($a['ts'], $b['ts']); });
    foreach ($rows as $i => $r) {
        $rows[$i]['time'] = date('H:i', strtotime($r['ts']));
    }
    return $rows;
}

/** ปิดร้านย้อนหลัง (วันก่อนที่ยังเปิดค้าง) · ต้องมีหมายเหตุ · คืน array('ok', 'keep') หรือ array('error')
 *  $bankCounted = ยอดในแอปธนาคาร (ไม่บังคับ · ว่าง = ไม่เก็บ ยอดยกมาใช้ควรมีของวันนั้น) */
function Stock_closePastDay($code, $ymd, $user, $counted, $keep, $note, $bankCounted = '')
{
    $ymd = preg_replace('/\D/', '', (string) $ymd);
    $ts  = (strlen($ymd) === 8) ? strtotime($ymd) : false;
    if ($ts === false || date('Y-m-d', $ts) >= date('Y-m-d')) {
        return array('error' => 'ปิดย้อนหลังได้เฉพาะวันก่อนวันนี้');
    }
    $note = trim((string) $note);
    if ($note === '') {
        return array('error' => 'กรุณากรอกหมายเหตุว่าทำไมวันนั้นไม่ได้ปิดร้าน');
    }
    $row  = store_day_row($code, date('Y-m-d', $ts));
    $gone = 'วัน' . thai_date_full($ts) . ' ไม่ได้ค้างอยู่แล้ว (อาจมีคนปิดไปก่อน)';
    if ($row === null || $row['status'] !== 'open') {
        return array('error' => $gone);
    }
    $dayId = (int) $row['day_id'];
    $saved = array();
    $ok = sdb_tx(function () use ($code, $ts, $row, $dayId, $user, $counted, $keep, $note, $bankCounted, &$saved) {
        if (sdb_val('SELECT status FROM ' . sdb_tb('store_day') . ' WHERE day_id = ? FOR UPDATE', array($dayId)) !== 'open') {
            return false;
        }
        $expected = Stock_getDayCash($dayId);
        $expected = $expected['expected'];
        $counted  = max(0, round((float) $counted, 2));
        $keep     = max(0, min(round((float) $keep, 2), $counted));
        $note     = stock_cut($note, 255);
        $bank     = Stock_getDayBank($dayId);
        $bcnt     = ($bank !== null && trim((string) $bankCounted) !== '') ? round((float) $bankCounted, 2) : null;
        sdb_update('store_day', array(
            'status'        => 'closed',
            'closed_by'     => stock_uid($user),
            'closed_at'     => date('Y-m-d H:i:s'),
            'expected_cash' => $expected,
            'counted_cash'  => $counted,
            'keep_cash'     => $keep,
            'handover_cash' => round($counted - $keep, 2),
            'bank_expected' => ($bank !== null) ? $bank['expected'] : null,
            'bank_counted'  => $bcnt,
            'close_note'    => $note,
        ), array('day_id' => $dayId));

        /* ---- เก็บลงประวัติของวันนี้ (วันที่ปิด) ---- */
        $diff   = round($counted - $expected, 2);
        $detail = array(
            'ของวันที่'        => thai_date_full($ts),
            'เปิดร้านวันนั้น'    => date('H:i', strtotime($row['opened_at'])) . ' น. โดย ' . ($row['opened_name'] !== null ? $row['opened_name'] : '-'),
            'เงินที่ควรมี'       => money2($expected) . ' บาท',
            'นับได้จริง'        => money2($counted) . ' บาท',
            'ผลต่าง'           => ($diff > 0 ? 'เกิน +' : ($diff < 0 ? 'ขาด −' : 'ตรงพอดี ')) . money2(abs($diff)) . ' บาท',
            'เงินทอนที่แยกไว้'   => money2($keep) . ' บาท',
            'นำส่ง'            => money2($counted - $keep) . ' บาท',
        );
        $detail += Stock_buildBankCloseDetail($bank, $bcnt);
        $detail['หมายเหตุ'] = $note;
        $detail['ปิดโดย']  = $user['name'] . ' (' . actor_label($user) . ')';
        log_add($code, 'close', $user, 'ปิดร้านย้อนหลัง ' . branch_name($code) . ' · ของวัน' . thai_date_full($ts), $detail, $counted);
        $saved = array('keep' => $keep);
        return true;
    });
    store_db_reset();
    if ($ok !== true) {
        return array('error' => $gone);
    }
    notify_on_close($code, $ts);                  // Telegram: ปิดร้าน (ย้อนหลัง) + เงินขาด / เกิน
    return array('ok' => true, 'keep' => $saved['keep']);
}

/** บรรทัดบัญชีโอนในประวัติปิดร้าน — $bank = Stock_getDayBank (null = วันนั้นไม่มีบัญชีโอน) · $counted = ยอดในแอป (null = ไม่ได้กรอก)
 *  TODO:
 *    - [x] 23 พ.ย. (งานเงินโอน ช่วง 4): ปิดร้าน + ปิดร้านย้อนหลังใช้ร่วมกัน */
function Stock_buildBankCloseDetail($bank, $counted)
{
    if ($bank === null) {
        return array();
    }
    $d = array('บัญชีโอน ควรมี' => money2($bank['expected']) . ' บาท',
               'ยอดในแอป'     => ($counted !== null) ? money2($counted) . ' บาท' : 'ไม่ได้กรอก (ยกยอดที่ควรมีไปวันถัดไป)');
    if ($counted !== null) {
        $bd = round($counted - $bank['expected'], 2);
        $d['ผลต่างบัญชีโอน'] = ($bd > 0 ? 'เกิน +' : ($bd < 0 ? 'ขาด −' : 'ตรงพอดี ')) . money2(abs($bd)) . ' บาท';
    }
    return $d;
}

/** บันทึกการปิดร้าน · คืนสถานะร้าน หรือ null ถ้าไม่ได้เปิดอยู่ · $bankCounted = ยอดในแอปธนาคาร (วันที่มีบัญชีโอน · หน้าปิดร้านตรวจว่าต้องกรอก) */
function store_close($code, $user, $counted, $keep, $note, $bankCounted = null)
{
    $st = store_state($code);
    if ($st === null || $st['closed_at'] !== '') {
        return null;
    }
    $ok = sdb_tx(function () use ($code, $st, $user, $counted, $keep, $note, $bankCounted) {
        $s = sdb_val('SELECT status FROM ' . sdb_tb('store_day') . ' WHERE day_id = ? FOR UPDATE', array($st['id']));
        if ($s !== 'open') {
            return false;
        }
        $expected = store_expected_cash($code);
        $counted  = max(0, round((float) $counted, 2));
        $keep     = max(0, min(round((float) $keep, 2), $counted));
        $note     = stock_cut($note, 255);
        $bank     = Stock_getDayBank($st['id']);
        $bcnt     = ($bank !== null && $bankCounted !== null && $bankCounted !== '') ? round((float) $bankCounted, 2) : null;
        sdb_update('store_day', array(
            'status'        => 'closed',
            'closed_by'     => stock_uid($user),
            'closed_at'     => date('Y-m-d H:i:s'),
            'expected_cash' => $expected,
            'counted_cash'  => $counted,
            'keep_cash'     => $keep,
            'handover_cash' => round($counted - $keep, 2),
            'bank_expected' => ($bank !== null) ? $bank['expected'] : null,
            'bank_counted'  => $bcnt,
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
        $detail += Stock_buildBankCloseDetail($bank, $bcnt);
        if ($note !== '') {
            $detail['หมายเหตุ'] = $note;
        }
        log_add($code, 'close', $user, 'ปิดร้าน ' . branch_name($code), $detail, $counted);
        return true;
    });
    store_db_reset();
    if ($ok) {
        notify_on_close($code);                   // Telegram: ปิดร้าน + เงินขาด / เกิน
    }
    return $ok ? store_state($code) : null;
}

/** เปิดร้านใหม่หลังปิดไปแล้ว (ผู้ดูแล / สิทธิ์ store_reopen) · ต้องมีเหตุผล */
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
            'ผู้เปิดใหม่'          => $user['name'] . ' (' . actor_label($user) . ')',
        ));
        return true;
    });
    store_db_reset();
    if ($ok) {
        notify_on_reopen($code, $user, $reason, $st);    // Telegram
    }
    return $ok ? store_state($code) : null;
}

/** เหตุผลเปิดร้านใหม่หลังปิด (หน้าเปิดร้าน + หน้าภาพรวมผู้ดูแล) · คืน '' = ใช้ได้ · ข้อความ = ให้กรอกใหม่
 *  TODO:
 *    - [x] 21 ต.ค.: อย่างน้อย 5 ตัวอักษร — เดิมพิมพ์ตัวเดียว (เช่น “x”) ก็เปิดร้านใหม่ได้ ผู้ดูแลอ่านแล้วไม่รู้เหตุผล */
function Stock_checkReopenReason($reason)
{
    $reason = trim((string) $reason);
    if ($reason === '') {
        return 'กรุณาระบุเหตุผลที่ต้องเปิดร้านใหม่';
    }
    if (mb_strlen($reason, 'UTF-8') < 5) {
        return 'เหตุผลสั้นเกินไป — เขียนให้ผู้ดูแลอ่านแล้วเข้าใจ (อย่างน้อย 5 ตัวอักษร) เช่น ลูกค้ากลับมาซื้อหลังปิดร้าน';
    }
    return '';
}

/** เติม (in) / หยิบ (out) เงินระหว่างวัน · $channel = cash (ลิ้นชัก) | transfer (บัญชีโอนของสาขา) · คืน array('ok') หรือ array('error')
 *  หยิบเงินสดห้ามเกินที่ควรมีในลิ้นชัก · แบบโอนไม่ห้าม (ธนาคารเป็นตัวจริง · 23 พ.ย.) */
function store_cash_add($code, $user, $dir, $amount, $reason, $channel = 'cash')
{
    $dir    = ($dir === 'out') ? 'out' : 'in';
    $ch     = ($channel === 'transfer') ? 'transfer' : 'cash';
    $amount = round((float) $amount, 2);
    $reason = stock_cut($reason, 255);
    $what   = ($ch === 'cash') ? (($dir === 'in') ? 'เติมเงินทอน' : 'หยิบเงินออก') : (($dir === 'in') ? 'เติมเงินเข้าบัญชีโอน' : 'โอนเงินออกจากบัญชีโอน');
    if ($amount <= 0) {
        return array('error' => 'กรุณากรอกจำนวนเงินมากกว่า 0');
    }
    if ($amount > 1000000) {
        return array('error' => 'จำนวนเงินต่อครั้งต้องไม่เกิน 1,000,000 บาท');   // 21 ต.ค. — เดิมขึ้น "มากกว่า 0" ทั้งที่กรอกเกิน
    }
    if ($reason === '') {
        return array('error' => 'กรุณาระบุเหตุผลของการ' . $what);
    }
    $st = store_state($code);
    if ($st === null || $st['closed_at'] !== '') {
        return array('error' => 'ร้านไม่ได้เปิดอยู่ — เติม / หยิบเงินได้เฉพาะตอนร้านเปิด');
    }
    $err = sdb_tx(function () use ($code, $st, $user, $dir, $ch, $what, $amount, $reason) {
        $s = sdb_val('SELECT status FROM ' . sdb_tb('store_day') . ' WHERE day_id = ? FOR UPDATE', array($st['id']));
        if ($s !== 'open') {
            return 'ร้านปิดไปแล้ว';
        }
        if ($dir === 'out' && $ch === 'cash' && $amount > store_expected_cash($code)) {
            return 'หยิบเงินออกได้ไม่เกินเงินที่ควรมีในลิ้นชัก (' . money2(store_expected_cash($code)) . ' บาท)';
        }
        sdb_insert('cash_move', array(
            'day_id'     => $st['id'],
            'branch_id'  => branch_id_of($code),
            'direction'  => $dir,
            'amount'     => $amount,
            'channel'    => $ch,
            'reason'     => $reason,
            'created_by' => stock_uid($user),
            'add_date'   => date('Y-m-d H:i:s'),
        ));
        $title = ($ch === 'cash') ? (($dir === 'in') ? 'เติมเงินทอน ' : 'หยิบเงินออกจากลิ้นชัก ') : $what . ' ';
        log_add($code, 'cash', $user, $title . money2($amount) . ' บาท', array(
            'จำนวน'     => ($dir === 'in' ? '+' : '−') . money2($amount) . ' บาท',
            'ช่องทาง'    => ($ch === 'cash') ? 'เงินสดในลิ้นชัก' : 'บัญชีโอนของสาขา',
            'เหตุผล'    => $reason,
            'ผู้ทำรายการ' => $user['name'] . ' · ' . branch_name($code),
        ), $dir === 'in' ? $amount : -$amount);
        return '';
    });
    return ($err === '') ? array('ok' => true) : array('error' => $err);
}

/** เงินสดที่คืนลูกค้าวันนี้จากการรับคืน (จ่ายจากลิ้นชักวันนี้เสมอ แม้บิลเป็นของวันก่อน · คืนแบบโอนไม่นับ) */
function store_refunds($code)
{
    $st = store_day_row($code);
    if ($st === null) {
        return 0.0;
    }
    return (float) sdb_val('SELECT COALESCE(SUM(refund), 0) FROM ' . sdb_tb('return') . ' WHERE day_id = ? AND refund_channel = \'cash\'', array((int) $st['day_id']));
}

/** เงินสดจากการขายวันนี้ = รับเงินสด − ทอนเงินสดจริง (บิลที่ไม่ได้ยกเลิก · ส่วนที่โอนไม่เข้าลิ้นชัก) */
function store_cash_sales($code)
{
    $st = store_day_row($code);
    if ($st === null) {
        return 0.0;
    }
    $q = Stock_buildPaySql('s');
    return (float) sdb_val('SELECT COALESCE(SUM(' . $q['cash_in'] . ' - ' . $q['cash_out'] . '), 0) FROM ' . sdb_tb('sale') . ' s'
                         . ' WHERE s.day_id = ? AND s.status = \'paid\'', array((int) $st['day_id']));
}
