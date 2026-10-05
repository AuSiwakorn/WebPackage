<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/return.php
 * ROLE: รับคืนสินค้า — ค้นบิล · เช็กกำหนดคืน · บันทึกใบรับคืน + รูปแนบ · อ่านใบรับคืนทั้งช่วง
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_return, ao_stock_return_item, ao_stock_sale, ao_stock_sale_item, ao_stock_move, ao_stock_balance, ao_stock_store_day, ao_stock_log · uploads/stock/returns/
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *   - [x] ช่วงที่ 11: ไม่มีสิทธิ์คืนเงินสด (refund_cash) = ยอดคืนเป็น 0 เสมอ
 *
 * ⚠ ไฟล์นี้ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb — มีได้แค่ define() และประกาศ function (ห้าม echo / header / query ตอนโหลด)
 */

/* ==========================================================
   รับคืนสินค้า
   ----------------------------------------------------------
   ค้นบิลเก่า → ดูว่ายังอยู่ในกำหนดวันไหม → เลือกรายการ/จำนวนที่คืน
   → เลือกเหตุผล → ยืนยันยอดเงินคืน → ออกใบรับคืน (RT-)

   กติกาที่ตกลงไว้
   - ต้องมีสิทธิ์เสริม refund
   - บิลต้องไม่เก่ากว่า backdate_days(สาขา) วัน (ผู้ดูแลตั้ง ค่าเริ่มต้น 7) · เกินกำหนดผู้ดูแลขยายจำนวนวันได้
   - คืนบางรายการ / บางชิ้นได้ · คืนเกินจำนวนที่ซื้อ (หักที่คืนไปแล้ว) ไม่ได้
   - ของกลับเข้าสต๊อกเฉพาะเหตุผลที่ขายต่อได้ (ซื้อผิดรุ่น/ผิดแบบ) — stock_move ชนิด return
     เหตุผลอื่นไม่เข้าสต๊อก — แยกเก็บไว้ เพราะไม่ควรเอาไปขายต่อ
   - คืนเป็นเงินสดจากลิ้นชักของวันนี้เท่านั้น (ไม่คืนด้วยการโอน)
     จึงต้องเปิดร้านก่อน · ยอดเงินคืนแก้ได้ แต่ไม่เกินราคาที่ลูกค้าจ่าย และถ้าแก้ต้องใส่เหตุผล

     ใบรับคืน     ao_stock_return + ao_stock_return_item (sale_item_id ชี้แถวในบิลเดิม)
     คืนไปแล้ว    SUM(return_item.qty) ของบิลนั้น — เช็กซ้ำหลังล็อกบิล กันคืนเกินจากสองเครื่องพร้อมกัน
     รูปถ่าย      uploads/stock/returns/{เลขที่ใบ}-{ลำดับ}.{jpg|png|webp} · path เก็บเป็น JSON ใน stock_return.photos
   ========================================================== */

/** ค้นบิลย้อนหลังได้กี่วัน — มากกว่ากำหนดคืน เพื่อให้เห็นบิลที่เกินกำหนดด้วย */
function return_lookback_days()
{
    return 14;
}

/* ---------- เหตุผลการคืน ----------
   restock = ของกลับเข้าสต๊อกขายต่อได้ · note = ต้องกรอกรายละเอียด */
function return_reasons()
{
    return array(
        'wrong'  => array('label' => 'ซื้อผิดรุ่น / ผิดแบบ',     'hint' => 'ของยังสมบูรณ์ ไม่ได้แกะใช้ — กลับเข้าสต๊อกขายต่อได้', 'restock' => true,  'note' => false),
        'defect' => array('label' => 'สินค้าชำรุด / ใช้งานไม่ได้', 'hint' => 'ไม่เข้าสต๊อก แยกเก็บไว้ส่งเคลม',                    'restock' => false, 'note' => false),
        'used'   => array('label' => 'ใช้แล้ว / สภาพไม่สมบูรณ์',   'hint' => 'ไม่เข้าสต๊อก',                                   'restock' => false, 'note' => false),
        'other'  => array('label' => 'อื่น ๆ',                     'hint' => 'ไม่เข้าสต๊อก · ต้องระบุรายละเอียด',               'restock' => false, 'note' => true),
    );
}

function return_reason_label($k)
{
    $r = return_reasons();
    return isset($r[$k]) ? $r[$k]['label'] : $k;
}

/* ==========================================================
   บิลที่ค้นได้
   ========================================================== */

/** สินค้าจาก SKU (รวมที่เลิกขาย — บิลเก่ายังอ้างถึงได้) */
function ret_product($sku)
{
    return product_by_sku($sku);
}

/** บิลทั้งหมดของสาขาในวันหนึ่ง รวมบิลที่ยกเลิก — ใช้กับประวัติย้อนหลัง
    TODO:
      - [x] อ่านจาก ao_stock_sale (เดิมข้อมูลสมมติ) */
function past_bills($code, $ts)
{
    return acct_bills($code, $ts);
}

/** ค้นบิลย้อนหลัง return_lookback_days() วัน — เลขบิล ชื่อสินค้า หรือ SKU · ใหม่สุดขึ้นก่อน (ไม่รวมบิลที่ยกเลิก)
    TODO:
      - [x] คิวรีเดียว (เดิมวนทีละวัน) */
function return_find_bills($code, $q, $limit = 100)
{
    $q      = trim((string) $q);
    $where  = 'b.code = ? AND s.status = \'paid\' AND s.sale_date >= ?';
    $params = array((string) $code, date('Y-m-d', strtotime('-' . return_lookback_days() . ' day', strtotime(date('Y-m-d')))));
    if ($q !== '') {
        $like     = '%' . addcslashes($q, '%_\\') . '%';
        $where   .= ' AND (s.doc_no LIKE ? OR EXISTS (SELECT 1 FROM ' . sdb_tb('sale_item') . ' i'
                  . ' WHERE i.sale_id = s.sale_id AND (i.product_name LIKE ? OR i.sku LIKE ?)))';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    return sale_bills_query($where, $params, 's.add_date DESC, s.sale_id DESC', $limit);
}

/** หาบิลจากเลขที่บิล (เช่น RS2026-09-0012 / RSV2026-09-0003) — ไม่รวมบิลที่ยกเลิก · เกินกำหนดคืนให้ bill_return_status บอก */
function return_bill($code, $no)
{
    $r = sale_bills_query('b.code = ? AND s.doc_no = ? AND s.status = \'paid\'', array((string) $code, (string) $no));
    return $r ? $r[0] : null;
}

/** บิลนี้ผ่านมากี่วันแล้ว (วันนี้ = 0) */
function bill_age_days($b)
{
    $d = strtotime($b['date']);
    return (int) round((strtotime(date('Y-m-d')) - $d) / 86400);
}

/* ==========================================================
   จำนวนที่คืนไปแล้ว
   ========================================================== */

/** จำนวนที่คืนไปแล้วของบิล — array( SKU => จำนวน ) (1 SKU มีแถวเดียวในบิล เพราะตะกร้าแยกตาม SKU)
    TODO:
      - [x] รวมจาก ao_stock_return_item (เดิม $_SESSION['ret_by_bill'] + ใบรับคืนสมมติ) */
function returned_of_bill($no, $bill = null)
{
    if ($bill !== null && !empty($bill['id'])) {
        $where = 'r.sale_id = ?';
        $param = (int) $bill['id'];
    } else {
        $where = 'r.sale_id = (SELECT sale_id FROM ' . sdb_tb('sale') . ' WHERE doc_no = ? LIMIT 1)';
        $param = (string) $no;
    }
    $out = array();
    foreach (sdb_rows('SELECT i.sku, SUM(i.qty) AS q FROM ' . sdb_tb('return_item') . ' i JOIN ' . sdb_tb('return') . ' r ON r.return_id = i.return_id'
                    . ' WHERE ' . $where . ' GROUP BY i.sku', array($param)) as $r) {
        $out[$r['sku']] = (int) $r['q'];
    }
    return $out;
}

function bill_has_returns($no, $bill = null)
{
    return array_sum(returned_of_bill($no, $bill)) > 0;
}

/** แต่ละรายการในบิล + คืนไปแล้ว + คืนได้อีก */
function return_lines($bill)
{
    $done = returned_of_bill($bill['no'], $bill);
    $out  = array();
    /* บิลที่มีส่วนลดท้ายบิล → คืนเงินตามราคาที่ลูกค้าจ่ายจริง (เฉลี่ยส่วนลดตามสัดส่วน) */
    $f = (!empty($bill['discount']) && !empty($bill['subtotal'])) ? $bill['total'] / $bill['subtotal'] : 1;
    foreach ($bill['lines'] as $l) {
        if ($f != 1) {
            $l['list_price'] = $l['price'];
            $l['price']      = round($l['price'] * $f, 2);
        }
        $back = isset($done[$l['sku']]) ? (int) $done[$l['sku']] : 0;
        $l['back']   = $back;
        $l['remain'] = max(0, (int) $l['qty'] - $back);
        $l['p']      = ret_product($l['sku']);
        $out[] = $l;
    }
    return $out;
}

/**
 * สถานะของบิลสำหรับการคืน
 * คืน array('ok' => bool, 'code' => ok|late|done, 'msg' => ข้อความ, 'left' => วันที่เหลือ)
 * บิลที่เกินกำหนดคืนไม่ได้ (ผู้ดูแลไม่ทำรับคืนเอง — ขยายจำนวนวันของสาขาได้)
 */
function bill_return_status($bill, $user = null)
{
    $age   = bill_age_days($bill);
    $limit = backdate_days($bill['branch']);
    $rem   = 0;
    foreach (return_lines($bill) as $l) {
        $rem += $l['remain'];
    }
    if ($rem === 0) {
        return array('ok' => false, 'code' => 'done', 'msg' => 'คืนครบทุกรายการแล้ว', 'left' => 0);
    }
    if ($age > $limit) {
        return array('ok' => false, 'code' => 'late',
                     'msg' => 'เกินกำหนดคืน ' . $limit . ' วัน (ผ่านมา ' . $age . ' วัน) — คืนไม่ได้'
                            . ' · ถ้าจำเป็น ผู้ดูแลขยายจำนวนวันได้ที่หน้าจัดการสาขา', 'left' => 0);
    }
    $left = $limit - $age;
    return array('ok' => true, 'code' => 'ok',
                 'msg' => $left === 0 ? 'คืนได้ถึงวันนี้' : 'คืนได้อีก ' . $left . ' วัน', 'left' => $left);
}

/* ==========================================================
   ใบรับคืน
   ========================================================== */

/** อ่านใบรับคืน — $where ต่อท้าย WHERE (alias r = ใบรับคืน · b = สาขา · s = บิลเดิม) · เรียงตามเวลา */
function returns_query($where, $params)
{
    $sql = 'SELECT r.*, b.code, u.username AS by_user, u.name AS by_name, s.doc_no AS bill_no, s.sale_date AS bill_date, su.name AS bill_by'
         . ' FROM ' . sdb_tb('return') . ' r JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = r.branch_id'
         . ' JOIN ' . sdb_tb('sale') . ' s ON s.sale_id = r.sale_id'
         . ' LEFT JOIN ' . sdb_tb('staff') . ' u ON u.staff_id = r.created_by'
         . ' LEFT JOIN ' . sdb_tb('staff') . ' su ON su.staff_id = s.created_by'
         . ' WHERE ' . $where . ' ORDER BY r.add_date, r.return_id';
    $heads = sdb_rows($sql, $params);
    if (!$heads) {
        return array();
    }
    $ids = array();
    foreach ($heads as $h) {
        $ids[] = (int) $h['return_id'];
    }
    $items = array();
    $sql   = 'SELECT i.*, p.unit FROM ' . sdb_tb('return_item') . ' i LEFT JOIN ' . sdb_tb('product') . ' p ON p.product_id = i.product_id'
           . ' WHERE i.return_id IN (' . implode(', ', $ids) . ') ORDER BY i.item_id';               // $ids เป็น int ทั้งหมด
    foreach (sdb_rows($sql) as $it) {
        $items[(int) $it['return_id']][] = $it;
    }
    $out = array();
    foreach ($heads as $h) {
        $lines = array();
        foreach (isset($items[(int) $h['return_id']]) ? $items[(int) $h['return_id']] : array() as $it) {
            $lines[] = array('iid' => (int) $it['sale_item_id'], 'sku' => $it['sku'], 'name' => $it['product_name'],
                             'unit' => ($it['unit'] !== null) ? $it['unit'] : 'ชิ้น', 'qty' => (int) $it['qty'],
                             'price' => (float) $it['unit_price'], 'sum' => (float) $it['line_total']);
        }
        $photos = array();
        $list   = ($h['photos'] !== '') ? json_decode($h['photos'], true) : array();
        foreach (is_array($list) ? $list : array() as $ph) {
            $photos[] = (defined('URL_UPLOAD') ? URL_UPLOAD : APP_BASE . '/uploads') . '/' . ltrim((string) $ph, '/');
        }
        $ts    = strtotime($h['add_date']);
        $out[] = array(
            'id'          => (int) $h['return_id'],
            'no'          => $h['doc_no'],
            'date'        => date('Ymd', strtotime($h['doc_date'])),
            'time'        => date('H:i', $ts),
            'ts'          => $ts,
            'branch'      => $h['code'],
            'by'          => ($h['by_name'] !== null) ? $h['by_name'] : '-',
            'by_user'     => ($h['by_user'] !== null) ? $h['by_user'] : '',
            'bill_no'     => $h['bill_no'],
            'bill_date'   => date('Ymd', strtotime($h['bill_date'])),
            'bill_by'     => ($h['bill_by'] !== null) ? $h['bill_by'] : '-',
            'reason'      => $h['reason'],
            'note'        => $h['note'],
            'restock'     => ((int) $h['restock'] === 1),
            'lines'       => $lines,
            'items'       => (int) $h['item_count'],
            'qty'         => (int) $h['total_qty'],
            'calc'        => (float) $h['calc_amount'],
            'refund'      => (float) $h['refund'],
            'refund_note' => $h['refund_note'],
            'photos'      => $photos,
        );
    }
    return $out;
}

/** ใบรับคืนของสาขาวันนี้
    TODO:
      - [x] อ่านจาก ao_stock_return (เดิม $_SESSION['ret']) */
function returns_today($code)
{
    return returns_of_day($code, time());
}

/** ใบรับคืนของวันนี้จากเลขที่ */
function return_by_no($code, $no)
{
    $r = returns_query('b.code = ? AND r.doc_no = ? AND r.doc_date = ?', array((string) $code, (string) $no, date('Y-m-d')));
    return $r ? $r[0] : null;
}

/**
 * บันทึกการรับคืน — ล็อกบิลเดิม → เช็กร้านเปิด + คืนไม่เกินที่เหลือ → ใบรับคืน + รายการ + stock_move (ถ้าเข้าสต๊อก) + ประวัติ
 * $qtys   = array( SKU => จำนวนที่คืน )
 * $refund = ยอดเงินคืนที่พนักงานยืนยัน (ว่าง = ใช้ยอดคำนวณ)
 * คืนค่า array('doc' => ใบรับคืน) หรือ array('error' => ข้อความ)
 * TODO:
 *   - [x] INSERT ao_stock_return + return_item + stock_move (return) · เลขที่ RT จาก stock_doc_seq
 */
function return_save($code, $user, $bill, $qtys, $reason, $note, $refund, $refundNote)
{
    if ($user['role'] !== 'staff' || !can($user, 'refund')) {
        return array('error' => 'ไม่มีสิทธิ์รับคืนสินค้า — การรับคืนเป็นหน้าที่ของพนักงานที่ได้รับมอบหมาย');
    }
    if (!store_is_open($code)) {
        return array('error' => 'ต้องเปิดร้านก่อน เพราะเงินคืนจ่ายจากลิ้นชักของวันนี้');
    }
    $st = bill_return_status($bill, $user);
    if (!$st['ok']) {
        return array('error' => $st['msg']);
    }

    $reasons = return_reasons();
    if (!isset($reasons[$reason])) {
        return array('error' => 'กรุณาเลือกเหตุผลการคืน');
    }
    if ($reasons[$reason]['note'] && trim($note) === '') {
        return array('error' => 'เหตุผล “' . $reasons[$reason]['label'] . '” ต้องกรอกรายละเอียดด้วย');
    }

    $lines = array();
    $calc  = 0;
    $qty   = 0;
    foreach (return_lines($bill) as $l) {
        $want = isset($qtys[$l['sku']]) ? (int) $qtys[$l['sku']] : 0;
        if ($want <= 0) {
            continue;
        }
        if ($want > $l['remain']) {
            return array('error' => $l['name'] . ' คืนได้อีกไม่เกิน ' . $l['remain'] . ' ' . $l['unit']);
        }
        $sum     = round($want * (float) $l['price'], 2);
        $lines[] = array('iid' => $l['iid'], 'pid' => $l['pid'], 'sku' => $l['sku'], 'name' => $l['name'], 'unit' => $l['unit'],
                         'qty' => $want, 'price' => (float) $l['price'], 'cost' => $l['cost'], 'sum' => $sum, 'line_qty' => (int) $l['qty']);
        $calc += $sum;
        $qty  += $want;
    }
    if (!$lines) {
        return array('error' => 'ยังไม่ได้ใส่จำนวนที่คืนสักรายการ');
    }
    $calc   = round($calc, 2);
    /* ไม่มีสิทธิ์คืนเงินสด = รับคืนแบบไม่คืนเงิน ยอดคืนเป็น 0 เสมอ (ช่วงที่ 11 ข้อ 2ก) */
    if (!can($user, 'refund_cash')) {
        $refund     = 0;
        $refundNote = 'รับคืนแบบไม่คืนเงินสด (ผู้รับคืนไม่มีสิทธิ์คืนเงิน)';
    }
    $refund = ($refund === '' || $refund === null) ? $calc : round((float) $refund, 2);
    if ($refund < 0 || $refund > $calc) {
        return array('error' => 'ยอดเงินคืนต้องอยู่ระหว่าง 0 ถึง ' . money2($calc) . ' บาท (ไม่เกินที่ลูกค้าจ่ายสำหรับรายการที่คืน)');
    }
    if (abs($refund - $calc) >= 0.01 && trim($refundNote) === '') {
        return array('error' => 'ยอดเงินคืนไม่เท่ากับยอดคำนวณ — กรุณาใส่เหตุผลที่ปรับยอด');
    }
    $restock = $reasons[$reason]['restock'];
    $bid     = branch_id_of($code);

    $no = sdb_tx(function () use ($code, $bid, $user, $bill, $lines, $reason, $note, $restock, $qty, $calc, $refund, $refundNote) {
        if (sdb_val('SELECT status FROM ' . sdb_tb('sale') . ' WHERE sale_id = ? FOR UPDATE', array($bill['id'])) !== 'paid') {
            return array('error' => 'บิลนี้ถูกยกเลิกไปแล้ว');
        }
        $day = sdb_row('SELECT day_id, status FROM ' . sdb_tb('store_day') . ' WHERE branch_id = ? AND store_date = ? LOCK IN SHARE MODE',
                       array($bid, date('Y-m-d')));
        if ($day === null || $day['status'] !== 'open') {
            return array('error' => 'ร้านปิดแล้ว — รับคืนไม่ได้ (เงินคืนจ่ายจากลิ้นชักของวันนี้)');
        }
        $done = returned_of_bill($bill['no'], $bill);                // อ่านหลังล็อกบิล — อีกเครื่องที่คืนพร้อมกันต้องรอ
        foreach ($lines as $l) {
            $left = $l['line_qty'] - (isset($done[$l['sku']]) ? $done[$l['sku']] : 0);
            if ($l['qty'] > $left) {
                return array('error' => $l['name'] . ' คืนได้อีกไม่เกิน ' . max(0, $left) . ' ' . $l['unit'] . ' (มีการคืนจากเครื่องอื่นไปแล้ว)');
            }
        }

        $uid = stock_uid($user);
        $now = date('Y-m-d H:i:s');
        $no  = stock_doc_next_no($bid, 'RT');
        $id  = sdb_insert('return', array(
            'doc_no'      => $no,
            'branch_id'   => $bid,
            'doc_date'    => date('Y-m-d'),
            'day_id'      => (int) $day['day_id'],
            'sale_id'     => $bill['id'],
            'reason'      => $reason,
            'note'        => stock_cut($note, 255),
            'restock'     => $restock ? 1 : 0,
            'item_count'  => count($lines),
            'total_qty'   => $qty,
            'calc_amount' => $calc,
            'refund'      => $refund,
            'refund_note' => stock_cut($refundNote, 255),
            'photos'      => '',
            'created_by'  => $uid,
            'add_date'    => $now,
        ));
        $pids = array();
        foreach ($lines as $l) {
            sdb_insert('return_item', array(
                'return_id'    => $id,
                'sale_item_id' => $l['iid'],
                'product_id'   => $l['pid'],
                'sku'          => $l['sku'],
                'product_name' => $l['name'],
                'qty'          => $l['qty'],
                'unit_price'   => $l['price'],
                'line_total'   => $l['sum'],
            ));
            $pids[] = $l['pid'];
        }
        if ($restock) {                                              // ของสมบูรณ์ → กลับเข้าสต๊อก
            $q = stock_lock_qty($bid, $pids);
            foreach ($lines as $l) {
                $q[$l['pid']] += $l['qty'];
                stock_move_add($bid, $l['pid'], 'return', $l['qty'], $q[$l['pid']], $l['cost'], 'return', $id, $no,
                               'คืนจากบิล ' . $bill['no'] . ' · ' . return_reason_label($reason), $uid, $now);
            }
        }

        /* ---- เก็บลงประวัติการทำรายการ ---- */
        $names = array();
        foreach ($lines as $l) {
            $names[] = $l['name'] . ' ×' . $l['qty'];
        }
        $detail = array(
            'บิลเดิม'      => $bill['no'] . ' · ' . thai_date_full(strtotime($bill['date'])) . ' ' . $bill['time'] . ' น. โดย ' . $bill['by'],
            'รายการที่คืน'  => implode(' · ', $names),
            'เหตุผล'       => return_reason_label($reason) . (trim($note) !== '' ? ' — ' . trim($note) : ''),
            'สต๊อก'        => $restock ? 'กลับเข้าสต๊อก ' . $qty . ' ชิ้น' : 'ไม่เข้าสต๊อก (แยกเก็บ)',
            'ยอดตามราคาขาย' => money2($calc) . ' บาท',
            'คืนเงินสด'     => money2($refund) . ' บาท' . (trim($refundNote) !== '' ? ' — ปรับยอด: ' . trim($refundNote) : ''),
            'ผู้รับคืน'      => $user['name'],
        );
        log_add($code, 'return', $user, 'รับคืนสินค้า ' . $no . ' (บิล ' . $bill['no'] . ')', $detail, $refund, $no);
        return $no;
    });

    if (is_array($no)) {
        return $no;
    }
    product_db_reset();
    $rt = return_by_no($code, $no);
    if ($rt !== null) {
        notify_on_refund($code, $rt);            // Telegram (ช่วงที่ 9)
    }
    return array('doc' => $rt);
}

/* ==========================================================
   รูปถ่ายแนบใบรับคืน (พนักงานถ่าย / เลือกรูปตอนรับคืน)
   เก็บไฟล์ที่ uploads/stock/returns/{เลขที่ใบ}-{ลำดับ}.{jpg|png|webp} (UpFile ของ admweb) · ไม่เกิน 3 รูป · รูปละไม่เกิน 5 MB
   uploads/.htaccess กันไฟล์สคริปต์ทุกชนิด · ตรวจเนื้อไฟล์ด้วย getimagesize ก่อนรับ
   ========================================================== */

/** ตรวจรูปที่อัปโหลดมา — คืน array('files' => รายการไฟล์ที่ผ่าน) หรือ array('error' => ข้อความ) */
function return_photos_check($field)
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
        return array('files' => array());
    }
    $f   = $_FILES[$field];
    $out = array();
    foreach ($f['name'] as $i => $name) {
        if ($f['error'][$i] === UPLOAD_ERR_NO_FILE || $name === '') {
            continue;
        }
        if ($f['error'][$i] !== UPLOAD_ERR_OK) {
            return array('error' => 'อัปโหลดรูป “' . $name . '” ไม่สำเร็จ (ไฟล์อาจใหญ่เกินที่เซิร์ฟเวอร์รับได้)');
        }
        if ($f['size'][$i] > 5 * 1024 * 1024) {
            return array('error' => 'รูป “' . $name . '” ใหญ่เกิน 5 MB');
        }
        $info = @getimagesize($f['tmp_name'][$i]);
        $ext  = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp');
        if ($info === false || !isset($ext[$info[2]])) {
            return array('error' => 'ไฟล์ “' . $name . '” ไม่ใช่รูป JPG / PNG / WEBP');
        }
        $out[] = array('tmp' => $f['tmp_name'][$i], 'ext' => $ext[$info[2]]);
    }
    if (count($out) > 3) {
        return array('error' => 'แนบรูปได้ไม่เกิน 3 รูป');
    }
    return array('files' => $out);
}

/** เก็บรูปที่ตรวจแล้ว แล้วผูกกับใบรับคืน (stock_return.photos) — คืน path ที่บันทึกได้ (ใต้ uploads/)
    TODO:
      - [x] UpFile → uploads/stock/returns · UPDATE ao_stock_return.photos (เดิม uploads/returns + session) */
function return_photos_store($code, $no, $files)
{
    if (!$files) {
        return array();
    }
    require_once PATH_PLUGIN . '/uploadfile/UpFile.php';
    $up    = new UpFile();
    $paths = array();
    foreach ($files as $i => $f) {
        $name  = preg_replace('/[^A-Za-z0-9-]/', '', $no) . '-' . ($i + 1);
        $saved = $up->uploadStandard(array($f['ext']), $f['tmp'], $name . '.' . $f['ext'], 'stock/returns', $name);
        if ($saved !== false) {
            $paths[] = $saved;
        }
    }
    if ($paths) {
        sdb_q('UPDATE ' . sdb_tb('return') . ' r JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = r.branch_id SET r.photos = ?'
            . ' WHERE b.code = ? AND r.doc_no = ?', array(json_encode($paths), (string) $code, (string) $no));
    }
    return $paths;
}

/** ใบรับคืนของวันหนึ่ง
    TODO:
      - [x] อ่านจาก ao_stock_return (เดิม session + ข้อมูลสมมติ) */
function returns_of_day($code, $ts)
{
    return returns_query('b.code = ? AND r.doc_date = ?', array((string) $code, date('Y-m-d', $ts)));
}

/** ใบรับคืนของหลายสาขา ช่วงวันที่ $from–$to (หน้าตรวจสอบของผู้ดูแล) — หัวใบ + รายการ 2 คิวรี เรียงตามเวลาที่ออกใบ
    TODO:
      - [x] ช่วงที่ 8: ทั้งช่วงในคิวรีเดียว (เดิมวน returns_of_day ทีละวันทีละสาขา) */
function returns_range($codes, $from, $to)
{
    list($in, $params) = sdb_in('b.code', $codes);
    array_unshift($params, date('Y-m-d', $from), date('Y-m-d', $to));
    return returns_query('r.doc_date BETWEEN ? AND ? AND ' . $in, $params);
}

/** หาใบรับคืนจากเลขที่ RT-ปปดดวว-NNNN (วันไหนก็ได้) */
function return_doc_find($code, $no)
{
    $r = returns_query('b.code = ? AND r.doc_no = ?', array((string) $code, (string) $no));
    return $r ? $r[0] : null;
}
