<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/sale.php
 * ROLE: ขายสินค้า — ตะกร้า (session) · บิลขาย · ยกเลิก / แก้ไขบิล · ข้อความหลังบันทึกของหน้าขาย
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_sale, ao_stock_sale_item, ao_stock_move, ao_stock_balance, ao_stock_store_day, ao_stock_return, ao_stock_log
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *   - [x] ช่วงที่ 11: ส่วนลดท้ายบิลต้องมีสิทธิ์ discount · ปุ่มแก้ / ยกเลิกบิลหลังบันทึกต้องมีสิทธิ์ bill_fix
 *
 * ⚠ ไฟล์นี้ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb — มีได้แค่ define() และประกาศ function (ห้าม echo / header / query ตอนโหลด)
 */

/* ==========================================================
   ขายสินค้า (ตะกร้า + บิล)
   ----------------------------------------------------------
     ตะกร้าที่กำลังขาย   $_SESSION['cart'] = SKU => จำนวน (ของผู้ใช้คนนั้น ยังไม่ใช่ข้อมูลจริง)
     บิลที่บันทึกแล้ว    ao_stock_sale + ao_stock_sale_item + ao_stock_move (ชนิด sale / sale_void)
   - ขายได้เฉพาะตอนร้านเปิด · บิลผูก day_id ของวันนั้น (ยอดเงินสดในลิ้นชัก)
   - เลขที่บิลแยกชุด VAT / ไม่ VAT รันต่อสาขาต่อเดือน (stock_doc_seq ชุด vat / novat งวด YYYYMM) · เลขของบิลที่ยกเลิกไม่นำมาใช้ซ้ำ
   - ราคาขาย / ทุน ณ ตอนขายเก็บไว้ในรายการของบิล (รายงานย้อนหลังไม่เปลี่ยนตามราคาใหม่)
   - ยกเลิกบิลได้เฉพาะบิลของวันนี้ ตอนร้านยังเปิด และยังไม่มีการรับคืน — วันก่อนใช้ "รับคืนสินค้า"
   ========================================================== */

/* ---------- ตะกร้า ---------- */

function cart_all()
{
    return isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : array();
}

function cart_count()
{
    $n = 0;
    foreach (cart_all() as $q) {
        $n += (int) $q;
    }
    return $n;
}

/** เพิ่มจำนวนในตะกร้า ไม่ให้เกินยอดคงเหลือของสาขา */
function cart_add($sku, $branch, $step = 1)
{
    $p = product_by_sku($sku);
    if ($p === null) {
        return false;
    }
    $have = product_qty($p, $branch);
    $cur  = isset($_SESSION['cart'][$sku]) ? (int) $_SESSION['cart'][$sku] : 0;
    $new  = $cur + (int) $step;

    if ($new <= 0) {
        unset($_SESSION['cart'][$sku]);
        return true;
    }
    if ($new > $have) {
        $new = $have;
    }
    if ($new <= 0) {
        unset($_SESSION['cart'][$sku]);
        return false;
    }
    $_SESSION['cart'][$sku] = $new;
    return true;
}

/** กำหนดจำนวนตรง ๆ (พนักงานพิมพ์แก้เองเมื่อกดผิด) */
function cart_set($sku, $branch, $qty)
{
    $p = product_by_sku($sku);
    if ($p === null) {
        return false;
    }
    $qty = min(STOCK_LINE_MAX, (int) $qty);
    if ($qty <= 0) {
        unset($_SESSION['cart'][$sku]);
        return true;
    }
    /* สต๊อกยังไม่ถูกหักจนกว่าจะบันทึกบิล ยอดคงเหลือจึงเป็นเพดานโดยตรง (เช็กซ้ำอีกครั้งตอนบันทึก) */
    $have = product_qty($p, $branch);
    $ok   = true;
    if ($qty > $have) {
        $qty = $have;
        $ok  = false;
    }
    $_SESSION['cart'][$sku] = $qty;
    return $ok;
}

function cart_remove($sku)
{
    unset($_SESSION['cart'][$sku]);
}

function cart_clear()
{
    $_SESSION['cart'] = array();
    unset($_SESSION['draft_edit_of']['SA']);
}

/** แปลงตะกร้าเป็นรายการพร้อมราคา (ราคาขายปัจจุบันของสินค้า) */
function cart_lines()
{
    $out = array();
    foreach (cart_all() as $sku => $qty) {
        $p = product_by_sku($sku);
        if ($p === null || (int) $qty <= 0) {
            continue;
        }
        $price = product_price($p);
        $out[] = array(
            'sku'   => $sku,
            'name'  => $p['name'],
            'unit'  => $p['unit'],
            'price' => $price,
            'qty'   => (int) $qty,
            'sum'   => $price * (int) $qty,
            'p'     => $p,
        );
    }
    return $out;
}

function cart_total()
{
    $t = 0;
    foreach (cart_lines() as $l) {
        $t += $l['sum'];
    }
    return $t;
}

/* ---------- บิลในฐานข้อมูล ---------- */

/** อ่านบิล — $where ต่อท้าย WHERE (alias s = บิล · b = สาขา) · คืน array ของบิลรูปเดียวกับที่หน้าเว็บใช้
    TODO:
      - [x] รายการในบิล 1 คิวรี (ไม่วนทีละบิล) */
function sale_bills_query($where, $params, $order = 's.add_date, s.sale_id', $limit = 0)
{
    $sql = 'SELECT s.*, b.code, u.username AS by_user, u.name AS by_name, v.username AS void_user, v.name AS void_name'
         . ' FROM ' . sdb_tb('sale') . ' s JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = s.branch_id'
         . ' LEFT JOIN ' . sdb_tb('staff') . ' u ON u.staff_id = s.created_by'
         . ' LEFT JOIN ' . sdb_tb('staff') . ' v ON v.staff_id = s.void_by'
         . ' WHERE ' . $where . ' ORDER BY ' . $order . ($limit > 0 ? ' LIMIT ' . (int) $limit : '');
    $heads = sdb_rows($sql, $params);
    if (!$heads) {
        return array();
    }
    $ids = array();
    foreach ($heads as $h) {
        $ids[] = (int) $h['sale_id'];
    }
    $items = array();
    $sql   = 'SELECT i.*, p.unit FROM ' . sdb_tb('sale_item') . ' i LEFT JOIN ' . sdb_tb('product') . ' p ON p.product_id = i.product_id'
           . ' WHERE i.sale_id IN (' . implode(', ', $ids) . ') ORDER BY i.item_id';            // $ids เป็น int ทั้งหมด
    foreach (sdb_rows($sql) as $it) {
        $items[(int) $it['sale_id']][] = $it;
    }
    $out = array();
    foreach ($heads as $h) {
        $out[] = sale_bill_shape($h, isset($items[(int) $h['sale_id']]) ? $items[(int) $h['sale_id']] : array());
    }
    return $out;
}

/** แถวในฐานข้อมูล → รูปบิลที่หน้าเว็บใช้ (no vat time by method lines total received change void_* ...) */
function sale_bill_shape($h, $items)
{
    $ts    = strtotime($h['add_date']);
    $vts   = !empty($h['void_date']) ? strtotime($h['void_date']) : 0;
    $lines = array();
    foreach ($items as $it) {
        $lines[] = array(
            'iid'   => (int) $it['item_id'],
            'pid'   => (int) $it['product_id'],
            'sku'   => $it['sku'],
            'name'  => $it['product_name'],
            'unit'  => ($it['unit'] !== null) ? $it['unit'] : 'ชิ้น',
            'price' => (float) $it['unit_price'],
            'cost'  => (float) $it['unit_cost'],
            'qty'   => (int) $it['qty'],
            'sum'   => (float) $it['line_total'],
        );
    }
    return array(
        'id'          => (int) $h['sale_id'],
        'no'          => $h['doc_no'],
        'day_id'      => (int) $h['day_id'],
        'vat'         => ((int) $h['is_vat'] === 1),
        'date'        => date('Ymd', strtotime($h['sale_date'])),
        'time'        => date('H:i', $ts),
        'ts'          => $ts,
        'branch'      => $h['code'],
        'by'          => ($h['by_name'] !== null) ? $h['by_name'] : '-',
        'by_user'     => ($h['by_user'] !== null) ? $h['by_user'] : '',
        'method'      => $h['pay_method'],
        'lines'       => $lines,
        'items'       => (int) $h['item_count'],
        'qty'         => (int) $h['total_qty'],
        'subtotal'    => (float) $h['subtotal'],
        'discount'    => (float) $h['discount'],
        'total'       => (float) $h['total'],
        'base'        => (float) $h['base_amount'],
        'vatamt'      => (float) $h['vat_amount'],
        'cost'        => (float) $h['total_cost'],
        'received'    => (float) $h['received'],
        'change'      => (float) $h['change_amount'],
        'void'        => ($h['status'] === 'void'),
        'void_mode'   => (string) $h['void_mode'],
        'void_reason' => $h['void_reason'],
        'void_by'     => ($h['void_name'] !== null) ? $h['void_name'] : '',
        'void_user'   => ($h['void_user'] !== null) ? $h['void_user'] : '',
        'void_at'     => $vts ? date(date('Ymd', $vts) === date('Ymd', $ts) ? 'H:i' : 'j/n H:i', $vts) : '',
        'edit_of_id'  => (int) $h['edit_of_id'],
    );
}

/** บิลของวันนี้จากเลขที่ — แก้ / ยกเลิกได้เฉพาะบิลของวันนี้ */
function bill_by_no($code, $no)
{
    $r = sale_bills_query('b.code = ? AND s.doc_no = ? AND s.sale_date = ?', array((string) $code, (string) $no, date('Y-m-d')));
    return $r ? $r[0] : null;
}

/**
 * บันทึกบิลจากตะกร้า: ล็อกยอด → เช็กร้านเปิด + ของพอ → ออกเลขบิล → บิล + รายการ + stock_move + ประวัติ (ทรานแซกชันเดียว)
 * คืนค่า: บิลที่บันทึก | null ถ้าตะกร้าว่าง | array('error' => ข้อความ)
 * TODO:
 *   - [x] INSERT ao_stock_sale + sale_item + stock_move · เลขบิลจาก stock_doc_seq (vat / novat · YYYYMM)
 *   - [x] บิลที่ทำแทนบิล "ยกเลิกเพื่อแก้ไข" ใส่ edit_of_id
 */
function bill_save($code, $user, $method, $received, $vat = false, $net = null)
{
    $lines = cart_lines();
    if (!$lines) {
        return null;
    }
    $bid = branch_id_of($code);
    if ($bid <= 0) {
        return array('error' => 'ไม่พบสาขาที่ทำรายการ');
    }
    $method   = ($method === 'transfer') ? 'transfer' : 'cash';
    $vat      = (bool) $vat;
    $subtotal = round(cart_total(), 2);
    /* ผู้ขายลดราคาได้ด้วยการแก้ยอดที่ต้องชำระ → ส่วนต่างลงเป็นส่วนลดท้ายบิล (เพิ่มเกินราคาเต็มไม่ได้)
       ไม่มีสิทธิ์ให้ส่วนลด = คิดราคาเต็มเสมอ แม้ส่งยอดต่ำกว่ามา (ช่วงที่ 11) */
    if (!can($user, 'discount')) {
        $net = null;
    }
    $total    = ($net !== null && $net > 0 && $net <= $subtotal) ? round($net, 2) : $subtotal;
    $discount = round($subtotal - $total, 2);
    $recv     = ($method === 'cash') ? (float) (int) $received : $total;
    if ($recv < $total) {
        $recv = $total;
    }
    $prefix = bill_prefix($code, $vat);
    if ($prefix === '') {
        return array('error' => 'สาขานี้ยังไม่ได้ตั้งรหัสเลขที่บิล' . ($vat ? ' VAT' : '') . ' — แจ้งฝ่ายบัญชี / ผู้ดูแล');
    }

    $no = sdb_tx(function () use ($code, $bid, $user, $method, $vat, $lines, $subtotal, $total, $discount, $recv, $prefix) {
        $day = sdb_row('SELECT day_id, status FROM ' . sdb_tb('store_day') . ' WHERE branch_id = ? AND store_date = ? LOCK IN SHARE MODE',
                       array($bid, date('Y-m-d')));
        if ($day === null || $day['status'] !== 'open') {
            return array('error' => 'ร้านปิดแล้ว — บันทึกบิลไม่ได้ (ให้ผู้ดูแลเปิดร้านใหม่ถ้าจำเป็น)');
        }
        $pids = array();
        foreach ($lines as $l) {
            $pids[] = $l['p']['id'];
        }
        $q     = stock_lock_qty($bid, $pids);
        $short = array();
        foreach ($lines as $l) {
            if ($l['qty'] > $q[$l['p']['id']]) {
                $short[] = $l['name'] . ' (เหลือ ' . number_format($q[$l['p']['id']]) . ')';
            }
        }
        if ($short) {
            return array('error' => 'ของไม่พอ (อาจถูกขายจากเครื่องอื่นไปแล้ว) — ' . implode(' · ', $short) . ' · แก้จำนวนในตะกร้าแล้วรับเงินใหม่');
        }

        $uid  = stock_uid($user);
        $now  = date('Y-m-d H:i:s');
        $qty  = 0;
        $cost = 0;
        foreach ($lines as $l) {
            $qty  += $l['qty'];
            $cost += $l['qty'] * (float) $l['p']['cost'];
        }
        $vs = $vat ? vat_split($total) : array(0, 0);
        $no = bill_no_format($prefix, time(), stock_seq_next($bid, $vat ? 'vat' : 'novat', date('Ym')));
        $id = sdb_insert('sale', array(
            'doc_no'        => $no,
            'branch_id'     => $bid,
            'day_id'        => (int) $day['day_id'],
            'sale_date'     => date('Y-m-d'),
            'pay_method'    => $method,
            'is_vat'        => $vat ? 1 : 0,
            'base_amount'   => $vs[0],
            'vat_amount'    => $vs[1],
            'item_count'    => count($lines),
            'total_qty'     => $qty,
            'subtotal'      => $subtotal,
            'discount'      => $discount,
            'total'         => $total,
            'total_cost'    => round($cost, 2),
            'received'      => $recv,
            'change_amount' => round($recv - $total, 2),
            'status'        => 'paid',
            'edit_of_id'    => stock_draft_edit_of('SA', $bid),
            'created_by'    => $uid,
            'add_date'      => $now,
        ));
        foreach ($lines as $l) {
            $p   = $l['p'];
            $pid = $p['id'];
            sdb_insert('sale_item', array(
                'sale_id'      => $id,
                'product_id'   => $pid,
                'sku'          => $p['sku'],
                'product_name' => $p['name'],
                'qty'          => $l['qty'],
                'unit_price'   => $l['price'],
                'unit_cost'    => (float) $p['cost'],
                'line_total'   => round($l['sum'], 2),
            ));
            $q[$pid] -= $l['qty'];
            stock_move_add($bid, $pid, 'sale', -$l['qty'], $q[$pid], (float) $p['cost'], 'sale', $id, $no, '', $uid, $now);
        }

        /* ---- เก็บลงประวัติการทำรายการ ---- */
        $names = array();
        foreach ($lines as $l) {
            $names[] = $l['name'] . ' ×' . $l['qty'];
        }
        $detail = array(
            'จำนวน'    => count($lines) . ' รายการ · ' . $qty . ' ชิ้น',
            'รายการ'   => implode(' · ', $names),
            'ยอดรวม'   => number_format($total, 2) . ' บาท'
                          . ($discount > 0 ? ' (ราคาเต็ม ' . number_format($subtotal, 2) . ' ส่วนลด ' . number_format($discount, 2) . ')' : ''),
            'ประเภทบิล' => $vat ? 'VAT (ก่อน VAT ' . number_format($vs[0], 2) . ' + VAT ' . number_format($vs[1], 2) . ')' : 'ไม่ VAT',
            'ชำระโดย'  => ($method === 'cash') ? 'เงินสด' : 'โอน / พร้อมเพย์',
        );
        if ($method === 'cash') {
            $detail['รับมา']   = number_format($recv, 2) . ' บาท';
            $detail['เงินทอน'] = number_format($recv - $total, 2) . ' บาท';
        }
        log_add($code, 'sale', $user, 'ขายสินค้า บิล ' . $no, $detail, $total, $no);
        return $no;
    });

    if (is_array($no)) {
        return $no;
    }
    cart_clear();
    product_db_reset();
    return bill_by_no($code, $no);
}

/**
 * ยกเลิกบิลของวันนี้ — คืนสต๊อกด้วย stock_move sale_void แล้วทำเครื่องหมายว่ายกเลิก (บิลไม่ถูกลบ)
 * ร้านต้องยังเปิดอยู่ (ปิดแล้วยอดเงินในลิ้นชักสรุปไปแล้ว) และบิลต้องยังไม่มีการรับคืน
 * คืนค่า: บิลที่ยกเลิกแล้ว | null ถ้าไม่พบ / ยกเลิกไปแล้ว | array('error' => ข้อความ)
 * TODO:
 *   - [x] status void + stock_move sale_void + ประวัติ ในทรานแซกชันเดียว
 */
function bill_void($code, $no, $user, $reason, $reopen = false)
{
    $b = bill_by_no($code, $no);
    if ($b === null || $b['void']) {
        return null;
    }
    $reason = trim($reason);
    $res = sdb_tx(function () use ($code, $b, $user, $reason, $reopen) {
        $st = sdb_val('SELECT status FROM ' . sdb_tb('sale') . ' WHERE sale_id = ? FOR UPDATE', array($b['id']));
        if ($st !== 'paid') {
            return null;
        }
        $day = sdb_val('SELECT status FROM ' . sdb_tb('store_day') . ' WHERE day_id = ? LOCK IN SHARE MODE', array($b['day_id']));
        if ($day !== 'open') {
            return array('error' => 'ร้านปิดแล้ว — ยกเลิกบิลไม่ได้ (ให้ผู้ดูแลเปิดร้านใหม่ หรือใช้การรับคืนสินค้า)');
        }
        if (sdb_val('SELECT 1 FROM ' . sdb_tb('return') . ' WHERE sale_id = ? LIMIT 1', array($b['id'])) !== null) {
            return array('error' => 'บิลนี้มีการรับคืนสินค้าไปแล้ว — ยกเลิกหรือแก้ทั้งบิลไม่ได้ ให้ใช้การรับคืนแทน');
        }
        $bid  = branch_id_of($code);
        $pids = array();
        foreach ($b['lines'] as $l) {
            $pids[] = $l['pid'];
        }
        $q   = stock_lock_qty($bid, $pids);
        $uid = stock_uid($user);
        $now = date('Y-m-d H:i:s');
        sdb_update('sale', array(
            'status'      => 'void',
            'void_mode'   => $reopen ? 'edit' : 'void',
            'void_reason' => stock_cut($reason, 255),
            'void_by'     => $uid,
            'void_date'   => $now,
        ), array('sale_id' => $b['id']));
        foreach ($b['lines'] as $l) {
            $q[$l['pid']] += $l['qty'];
            stock_move_add($bid, $l['pid'], 'sale_void', $l['qty'], $q[$l['pid']], $l['cost'], 'sale', $b['id'], $b['no'], $reason, $uid, $now);
        }

        $detail = array(
            'บิลเดิม'          => $b['no'] . ' · ขายเมื่อ ' . $b['time'] . ' น. โดย ' . $b['by'],
            'ยอดที่คืน'         => number_format($b['total'], 2) . ' บาท',
            'จำนวนที่คืนสต๊อก' => $b['items'] . ' รายการ · ' . $b['qty'] . ' ชิ้น',
            'เหตุผล'           => $reason !== '' ? $reason : 'ไม่ได้ระบุ',
        );
        if ($reopen) {
            $detail['การทำต่อ'] = 'ดึงรายการกลับเข้าตะกร้าเพื่อออกบิลใหม่ทดแทน';
        }
        log_add($code, 'void', $user, ($reopen ? 'ยกเลิกเพื่อแก้ไข บิล ' : 'ยกเลิกบิล ') . $b['no'], $detail, $b['total'], $b['no']);
        return true;
    });
    product_db_reset();
    if ($res === true) {
        notify_on_void($code, 'SA', $b, $user, $reason, $reopen);   // Telegram (ช่วงที่ 9)
        return bill_by_no($code, $no);
    }
    return $res;
}

/**
 * ดึงรายการทั้งบิลกลับเข้าตะกร้า — ใช้คู่กับการยกเลิกเพื่อแก้ไข (บิลใหม่ชี้ edit_of_id กลับมาที่บิลนี้)
 * พนักงานไม่ต้องยิงบาร์โค้ดหรือกรอกใหม่ทั้งบิล แก้เฉพาะแถวที่ผิดแล้วรับเงินใหม่
 * จำนวนที่คืนเข้าสต๊อกจากการยกเลิกทำให้ใส่กลับได้ครบเสมอ
 */
function cart_from_bill($bill, $branch)
{
    $_SESSION['cart'] = array();
    foreach ($bill['lines'] as $l) {
        cart_set($l['sku'], $branch, $l['qty']);
    }
    $_SESSION['draft_edit_of']['SA'] = (int) $bill['id'];
    return cart_count();
}

/** บิลนี้มีการรับคืนสินค้าไปแล้วหรือยัง — ถ้ามีแล้ว ห้ามยกเลิก/แก้ทั้งบิล (ของจะถูกคืนซ้ำ)
    เลขที่บิลไม่ซ้ำข้ามสาขา (รหัสนำหน้าห้ามซ้ำ — acct_prefix_error) จึงหาจากเลขที่อย่างเดียวได้ */
function bill_returned_any($no)
{
    return sdb_val('SELECT 1 FROM ' . sdb_tb('return') . ' r JOIN ' . sdb_tb('sale') . ' s ON s.sale_id = r.sale_id'
                 . ' WHERE s.doc_no = ? LIMIT 1', array((string) $no)) !== null;
}

/** สรุปยอดขายวันนี้ของสาขา (บิลที่ยกเลิกนับแยก ไม่นับเป็นยอดขาย) */
function sale_summary($code)
{
    $r = sdb_row('SELECT COUNT(CASE WHEN s.status = \'paid\' THEN 1 END) AS bills,'
               . ' COALESCE(SUM(CASE WHEN s.status = \'paid\' THEN s.total_qty END), 0) AS qty,'
               . ' COALESCE(SUM(CASE WHEN s.status = \'paid\' THEN s.total END), 0) AS total,'
               . ' COALESCE(SUM(CASE WHEN s.status = \'paid\' AND s.pay_method = \'cash\' THEN s.total END), 0) AS cash,'
               . ' COALESCE(SUM(CASE WHEN s.status = \'paid\' AND s.pay_method = \'transfer\' THEN s.total END), 0) AS transfer,'
               . ' COUNT(CASE WHEN s.status = \'void\' THEN 1 END) AS void'
               . ' FROM ' . sdb_tb('sale') . ' s JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = s.branch_id'
               . ' WHERE b.code = ? AND s.sale_date = ?', array((string) $code, date('Y-m-d')));
    return array('bills' => (int) $r['bills'], 'qty' => (int) $r['qty'], 'total' => (float) $r['total'],
                 'cash' => (float) $r['cash'], 'transfer' => (float) $r['transfer'], 'void' => (int) $r['void']);
}

/* ---------- ข้อความแจ้งผล (ส่งกลับแบบ out-of-band ให้ htmx ด้วย) ---------- */
function sale_flash($err, $done, $voided, $oob)
{
    echo '<div id="sale-flash"' . ($oob ? ' hx-swap-oob="true"' : '') . '>';

    if ($err !== '') {
        echo '<div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span>'
           . e($err) . '</span></div>';

    } elseif ($voided !== null) {
        $isEdit = (isset($voided['void_mode']) && $voided['void_mode'] === 'edit');
        echo '<div class="alert ' . ($isEdit ? 'alert-info' : 'alert-warn') . '" role="status">'
           . '<svg class="ico"><use href="#' . ($isEdit ? 'i-arrow' : 'i-ban') . '"/></svg><span>';
        if ($isEdit) {
            echo 'ยกเลิกบิล <b>' . e($voided['no']) . '</b> และดึง ' . (int) $voided['items']
               . ' รายการกลับเข้าตะกร้าให้แล้ว — แก้จำนวนที่ผิดแล้วกดรับเงินใหม่ได้เลย '
               . 'ระบบจะออกเลขบิลใหม่ให้อัตโนมัติ';
        } else {
            echo 'ยกเลิกบิล <b>' . e($voided['no']) . '</b> แล้ว · คืนสต๊อก '
               . (int) $voided['qty'] . ' ชิ้น · เหตุผล: ' . e($voided['void_reason']);
        }
        echo '</span></div>';

    } elseif ($done !== null && empty($done['void'])) {
        echo '<div class="alert alert-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span>'
           . 'บันทึกบิล <b>' . e($done['no']) . '</b> แล้ว · ยอด <b class="num">' . money2($done['total']) . '</b> บาท · '
           . (!empty($done['discount']) ? 'ส่วนลด <b class="num">' . money2($done['discount']) . '</b> บาท · ' : '');
        if ($done['method'] === 'cash') {
            echo 'รับเงิน <span class="num">' . money2($done['received']) . '</span> บาท · '
               . 'เงินทอน <b class="num">' . money2($done['change']) . '</b> บาท';
        } else {
            echo 'ชำระโดยการโอน / พร้อมเพย์';
        }
        echo '</span>';

        /* กดผิดก็แก้ได้ทันทีจากตรงนี้ — ทั้งสองทางต้องกรอกหมายเหตุก่อน · ต้องมีสิทธิ์แก้บิลของตัวเอง (ช่วงที่ 11) */
        if (!can_void_doc(current_user(), $done, 'bill')) {
            echo '</div></div>';
            return;
        }
        $atts = ' data-bill="' . e($done['no']) . '" data-total="' . money2($done['total']) . '"'
              . ' data-qty="' . (int) $done['qty'] . '" data-items="' . (int) $done['items'] . '"'
              . ' hx-post="sale.php" hx-target="#sale-live" hx-swap="outerHTML"';

        echo '<div class="alert-act">';

        echo '<form method="post" action="sale.php" data-confirm="edit" hx-confirm="แก้ไขบิล"' . $atts . '>'
           . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
           . '<input type="hidden" name="act" value="edit">'
           . '<input type="hidden" name="no" value="' . e($done['no']) . '">'
           . '<input class="reason-fb" type="text" name="reason" value=""'
           . ' placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุการแก้ไขบิล">'
           . '<button class="btn btn-ghost btn-sm" type="submit">'
           . '<svg class="ico"><use href="#i-arrow"/></svg> แก้ไขบิลนี้</button>'
           . '</form>';

        echo '<form method="post" action="sale.php" data-confirm="void" hx-confirm="ยกเลิกบิล"' . $atts . '>'
           . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
           . '<input type="hidden" name="act" value="void">'
           . '<input type="hidden" name="no" value="' . e($done['no']) . '">'
           . '<input class="reason-fb" type="text" name="reason" value=""'
           . ' placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุการยกเลิกบิล">'
           . '<button class="btn btn-ghost btn-sm" type="submit">'
           . '<svg class="ico"><use href="#i-ban"/></svg> ยกเลิกบิลนี้</button>'
           . '</form>';

        echo '</div>';

        echo '</div>';
    }
    echo '</div>';
}
