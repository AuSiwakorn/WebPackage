<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/count.php
 * ROLE: ตรวจนับ / ปรับยอด — สาเหตุ · ใบร่าง (session) · บันทึก / ยกเลิก / แก้ไขใบตรวจนับ · รอบตรวจนับ
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_count, ao_stock_count_item, ao_stock_move, ao_stock_balance, ao_stock_log
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *
 * ⚠ ไฟล์นี้ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb — มีได้แค่ define() และประกาศ function (ห้าม echo / header / query ตอนโหลด)
 */

/* ==========================================================
   ตรวจนับ / ปรับยอด (แบบเบา)
   ----------------------------------------------------------
   เห็นของจริงไม่ตรงกับระบบ → เลือกสินค้า → กรอก "จำนวนที่นับได้จริง"
   ระบบคำนวณส่วนต่างให้เอง แล้วปรับสต๊อกให้ตรงกับของจริง

   ต่างจากรับเข้า/ตัดออกตรงที่ กรอก "ยอดจริง" ไม่ใช่ "จำนวนที่เพิ่ม/ลด"
   ส่วนต่างคำนวณตอนกดบันทึก เทียบกับยอดที่ล็อกไว้ในฐานข้อมูล ณ ตอนนั้น
   → ถ้ายอดขยับไประหว่างที่นับ (มีการขาย / รับเข้าแทรก) ระบบให้นับตัวนั้นใหม่ (adj_moved + เช็กซ้ำใน adj_save)

     ใบที่กำลังทำ   $_SESSION['adj_draft'][ SKU ] = จำนวนที่นับได้ · $_SESSION['adj_snap'] = ยอดตอนเริ่มนับ (ร่าง)
     หัวใบที่ค้างไว้  $_SESSION['adj_meta']
     ใบที่บันทึกแล้ว ao_stock_count + ao_stock_count_item + ao_stock_move (ทุกรายการที่นับ — ตรง = แถว qty 0)
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
    $_SESSION['adj_draft'][$sku] = max(0, min(STOCK_LINE_MAX, (int) $qty));    // นับได้ 0 ก็คือของหมดจริง ยังอยู่ในใบ
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
    unset($_SESSION['adj_meta'], $_SESSION['draft_edit_of']['AD']);
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

/** ใบตรวจนับของวันนี้จากเลขที่ — แก้ / ยกเลิกจากหน้าตรวจนับได้เฉพาะใบของวันนี้ */
function adj_by_no($code, $no)
{
    return stock_doc_by_no($code, $no, 'today', 'AD');
}

/**
 * บันทึกผลการนับ: ปรับสต๊อกเท่ากับส่วนต่าง + ledger + ประวัติ ในทรานแซกชันเดียว
 * $reason ว่างได้ถ้าทุกรายการตรงกันหมด (เป็นการยืนยันว่านับแล้วตรง)
 * ทุกรายการลง count_item (ใช้ตอบ "นับแล้วในรอบนี้") และ stock_move (ตรง = qty 0 · ยกเลิกใบถอยเฉพาะที่มีส่วนต่าง)
 * ถ้ายอดที่ล็อกได้ไม่ตรงกับยอดตอนแสดงหน้า (มีรายการแทรกระหว่างกดบันทึก) → ไม่บันทึก ให้นับตัวนั้นใหม่
 * คืนค่า: ใบที่บันทึก | array('error' => ข้อความ)
 * TODO:
 *   - [x] INSERT ao_stock_count + count_item + stock_move · round_start จาก count_round()
 *   - [x] ใบที่ทำแทนใบ "ยกเลิกเพื่อแก้ไข" ใส่ edit_of_id
 */
function adj_save($code, $user, $reason, $note, $lines)
{
    $bid = branch_id_of($code);
    if ($bid <= 0) {
        return array('error' => 'ไม่พบสาขาที่ทำรายการ');
    }
    if (!$lines) {
        return array('error' => 'ยังไม่ได้เลือกสินค้าที่นับสักรายการ');
    }

    $no = sdb_tx(function () use ($code, $bid, $user, $reason, $note, $lines) {
        $pids = array();
        foreach ($lines as $l) {
            $pids[] = $l['p']['id'];
        }
        $q     = stock_lock_qty($bid, $pids);
        $moved = array();
        foreach ($lines as $l) {
            $sys = $q[$l['p']['id']];
            if ($sys !== (int) $l['have']) {
                $moved[$l['sku']] = array($l['name'] . ' (ตอนเริ่มนับ ' . number_format($l['have']) . ' ตอนนี้ ' . number_format($sys) . ')', $sys);
            }
        }
        if ($moved) {
            return array('moved' => $moved);
        }

        $uid   = stock_uid($user);
        $now   = date('Y-m-d H:i:s');
        $sum   = adj_sum($lines);
        $round = count_round($code);
        $no    = stock_doc_next_no($bid, 'AD');
        $id    = sdb_insert('count', array(
            'doc_no'      => $no,
            'branch_id'   => $bid,
            'doc_date'    => date('Y-m-d'),
            'round_start' => date('Y-m-d', $round['start']),
            'reason'      => substr((string) $reason, 0, 20),
            'note'        => stock_cut($note, 255),
            'item_count'  => $sum['items'],
            'n_same'      => $sum['same'],
            'n_over'      => $sum['over'],
            'n_short'     => $sum['short'],
            'diff_value'  => round($sum['value'], 2),
            'edit_of_id'  => stock_draft_edit_of('AD', $bid),
            'created_by'  => $uid,
            'add_date'    => $now,
        ));
        $why = ($reason !== '') ? adj_reason_label($reason) : 'ตรงกับระบบ';
        foreach ($lines as $l) {
            $p   = $l['p'];
            $pid = $p['id'];
            sdb_insert('count_item', array(
                'count_id'     => $id,
                'branch_id'    => $bid,
                'product_id'   => $pid,
                'sku'          => $p['sku'],
                'product_name' => $p['name'],
                'qty_system'   => (int) $l['have'],
                'qty_counted'  => (int) $l['counted'],
                'diff'         => (int) $l['diff'],
                'unit_cost'    => (float) $p['cost'],
                'add_date'     => $now,
            ));
            /* นับแล้วตรง (ส่วนต่าง 0) ก็ลงแถว qty 0 ไว้ด้วย — ประวัติเคลื่อนไหวรายสินค้าจะเห็นว่าตรวจนับแล้ว */
            $q[$pid] += (int) $l['diff'];
            stock_move_add($bid, $pid, 'adjust', (int) $l['diff'], $q[$pid], (float) $p['cost'], 'count', $id, $no,
                           'นับได้ ' . number_format($l['counted']) . ' · ' . $why, $uid, $now);
        }

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
               ? 'ตรวจนับตรงทุกรายการ ' . $no
               : 'ตรวจนับ / ปรับยอด ' . $no;
        log_add($code, 'adjust', $user, $title, $detail, null, $no);
        return $no;
    });

    if (is_array($no)) {
        $names = array();
        foreach ($no['moved'] as $sku => $m) {
            $names[] = $m[0];
            $_SESSION['adj_snap'][$sku] = $m[1];             // นับใหม่แล้วกดบันทึกอีกครั้งได้เลย
        }
        return array('error' => 'ยอดในระบบเปลี่ยนไประหว่างที่นับ (มีการขายหรือรับเข้าแทรก) — '
                              . implode(' · ', $names) . ' · กรุณานับตัวนี้ใหม่แล้วกดบันทึกอีกครั้ง');
    }
    unset($_SESSION['draft_edit_of']['AD']);
    product_db_reset();
    return adj_by_no($code, $no);
}

/**
 * ยกเลิกใบตรวจนับของวันนี้ — ถอยส่วนต่างที่เคยปรับไว้กลับ
 * ถ้าใบเดิม "บวก" ของเพิ่มแล้วของนั้นถูกขายไปแล้ว การถอยจะทำให้ติดลบ → ยกเลิกไม่ได้ ต้องนับใหม่อีกรอบแทน
 * คืนค่า: ใบที่ยกเลิก | array('error' => 'sold', 'items' => ...) | null ถ้าไม่พบ / ยกเลิกไปแล้ว
 * TODO:
 *   - [x] status void + stock_move adjust_void (stock_doc_void_core)
 */
function adj_void($code, $no, $user, $reason, $reopen = false)
{
    $d = adj_by_no($code, $no);
    if ($d === null || $d['void']) {
        return null;
    }
    $reason = trim($reason);
    $detail = array(
        'ใบเดิม' => $d['no'] . ' · นับเมื่อ ' . $d['time'] . ' น. โดย ' . $d['by'],
        'ผล'     => 'ถอยยอดทุกรายการกลับเป็นก่อนตรวจนับ',
        'เหตุผล' => $reason !== '' ? $reason : 'ไม่ได้ระบุ',
    );
    if ($reopen) {
        $detail['การทำต่อ'] = 'ดึงรายการกลับเข้าใบเพื่อนับ/บันทึกใหม่';
    }
    $r = stock_doc_void_core($d, $user, $reason, $reopen, array(
        ($reopen ? 'ยกเลิกเพื่อแก้ไข ใบตรวจนับ ' : 'ยกเลิกใบตรวจนับ ') . $d['no'], $detail, null,
    ));
    if ($r === null) {
        return null;
    }
    if (is_array($r)) {
        return array('error' => 'sold', 'items' => $r['short']);
    }
    notify_on_void($code, 'AD', $d, $user, $reason, $reopen);   // Telegram (ช่วงที่ 9)
    return adj_by_no($code, $no);
}

/** ดึงรายการทั้งใบกลับเข้าร่าง (ใบใหม่จะชี้ edit_of_id กลับมาที่ใบนี้) — ยอดตั้งต้นของการนับ = ยอดหลังถอยใบเดิมแล้ว */
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
            $_SESSION['adj_snap'][$l['sku']] = product_qty($p, $doc['branch']);
        }
    }
    $_SESSION['draft_edit_of']['AD'] = (int) $doc['id'];
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

/** สถานะการนับของทุกสินค้าในรอบนี้: array( SKU => array(at, diff, by, no) )
    "นับแล้ว" = มี count_item ในใบที่ยังไม่ยกเลิก ตั้งแต่วันเริ่มรอบปัจจุบัน — ใบหลังสุดชนะ
    TODO:
      - [x] อ่านจาก ao_stock_count_item (เดิมข้อมูลสมมติ + ใบใน session) */
function count_status_all($code)
{
    $round = count_round($code);
    $sql   = 'SELECT i.sku, i.diff, i.add_date, c.doc_no, s.name AS by_name FROM ' . sdb_tb('count_item') . ' i'
           . ' JOIN ' . sdb_tb('count') . ' c ON c.count_id = i.count_id'
           . ' LEFT JOIN ' . sdb_tb('staff') . ' s ON s.staff_id = c.created_by'
           . ' WHERE i.branch_id = ? AND c.status = \'posted\' AND c.doc_date >= ? ORDER BY i.add_date, i.item_id';
    $out = array();
    foreach (sdb_rows($sql, array(branch_id_of($code), date('Y-m-d', $round['start']))) as $r) {
        $out[$r['sku']] = array('at' => strtotime($r['add_date']), 'diff' => (int) $r['diff'],
                                'by' => ($r['by_name'] !== null) ? $r['by_name'] : '-', 'no' => $r['doc_no']);
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
