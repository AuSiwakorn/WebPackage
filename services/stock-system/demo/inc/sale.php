<?php
/* ==========================================================
   AOSTOCK DEMO — ขายสินค้า (ตะกร้า + บิล)
   ----------------------------------------------------------
   ยังไม่มีฐานข้อมูล ทุกอย่างอยู่ใน session ทั้งหมด
   ใช้ "สาขา|วันที่" เป็นคีย์ แบบเดียวกับการเปิด/ปิดร้าน

     $_SESSION['cart']                      ตะกร้าที่กำลังขาย  SKU => จำนวน
     $_SESSION['sale'][ 'BN|20260923' ]     บิลของสาขานั้นในวันนั้น
     $_SESSION['stock_adj'][ 'BN' ][ SKU ]  ยอดสต๊อกที่ขยับแล้ว

   ระบบจริง: ตาราง sale_bill + sale_item + stock_move
   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/data.php';
require_once dirname(__FILE__) . '/activity.php';
require_once dirname(__FILE__) . '/stock.php';
require_once dirname(__FILE__) . '/billno.php';     // เลขที่บิลแยกชุด VAT / ไม่ VAT
require_once dirname(__FILE__) . '/return.php';     // past_bills_gen() ใช้ต่อเลขรันของเดือน

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
    $qty = (int) $qty;
    if ($qty <= 0) {
        unset($_SESSION['cart'][$sku]);
        return true;
    }
    /* สต๊อกยังไม่ถูกหักจนกว่าจะบันทึกบิล ยอดคงเหลือจึงเป็นเพดานโดยตรง */
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
}

/** แปลงตะกร้าเป็นรายการพร้อมราคา */
function cart_lines()
{
    $out = array();
    foreach (cart_all() as $sku => $qty) {
        $p = product_by_sku($sku);
        if ($p === null) {
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

/* ---------- บิล ---------- */

function sale_key($code)
{
    return $code . '|' . date('Ymd');
}

function bills_today($code)
{
    $k = sale_key($code);
    return isset($_SESSION['sale'][$k]) ? $_SESSION['sale'][$k] : array();
}

/** เลขที่บิลใบถัดไป — ชุดตามที่เลือก VAT / ไม่ VAT (ดู inc/billno.php) */
function bill_next_no($code, $vat = false)
{
    return bill_next_no_series($code, $vat);
}

/**
 * บันทึกบิล: ตัดสต๊อกของสาขา แล้วเก็บบิลไว้ใน session
 * คืนค่าเป็นบิลที่บันทึกแล้ว หรือ null ถ้าตะกร้าว่าง
 */
function bill_save($code, $user, $method, $received, $vat = false, $net = null)
{
    $lines = cart_lines();
    if (!$lines) {
        return null;
    }
    $subtotal = cart_total();
    /* ผู้ขายลดราคาได้ด้วยการแก้ยอดที่ต้องชำระ → ส่วนต่างลงเป็นส่วนลดท้ายบิล (เพิ่มเกินราคาเต็มไม่ได้) */
    $total = ($net !== null && $net > 0 && $net <= $subtotal) ? round($net, 2) : $subtotal;
    $discount = round($subtotal - $total, 2);
    $recv  = ($method === 'cash') ? (int) $received : $total;
    if ($recv < $total) {
        $recv = $total;
    }

    $bill = array(
        'no'       => bill_next_no($code, $vat),
        'vat'      => (bool) $vat,
        'time'     => date('H:i'),
        'branch'   => $code,
        'by'       => $user['name'],
        'by_user'  => $user['username'],
        'method'   => ($method === 'transfer') ? 'transfer' : 'cash',
        'lines'    => $lines,
        'items'    => count($lines),
        'qty'      => cart_count(),
        'subtotal' => $subtotal,
        'discount' => $discount,
        'total'    => $total,
        'received' => $recv,
        'change'   => $recv - $total,
    );

    foreach ($lines as $l) {
        stock_adj_add($code, $l['sku'], -$l['qty']);      // ขายออก = สต๊อกลด
    }

    $k = sale_key($code);
    if (!isset($_SESSION['sale'][$k])) {
        $_SESSION['sale'][$k] = array();
    }
    $_SESSION['sale'][$k][] = $bill;
    cart_clear();

    /* ---- เก็บลงประวัติการทำรายการ ---- */
    $vs    = vat_split($total);
    $names = array();
    foreach ($lines as $l) {
        $names[] = $l['name'] . ' ×' . $l['qty'];
    }
    $detail = array(
        'จำนวน'    => $bill['items'] . ' รายการ · ' . $bill['qty'] . ' ชิ้น',
        'รายการ'   => implode(' · ', $names),
        'ยอดรวม'   => number_format($total, 2) . ' บาท'
                      . ($discount > 0 ? ' (ราคาเต็ม ' . number_format($subtotal, 2) . ' ส่วนลด ' . number_format($discount, 2) . ')' : ''),
        'ประเภทบิล' => $vat ? 'VAT (ก่อน VAT ' . number_format($vs[0], 2) . ' + VAT ' . number_format($vs[1], 2) . ')' : 'ไม่ VAT',
        'ชำระโดย'  => ($bill['method'] === 'cash') ? 'เงินสด' : 'โอน / พร้อมเพย์',
    );
    if ($bill['method'] === 'cash') {
        $detail['รับมา'] = number_format($recv, 2) . ' บาท';
        $detail['เงินทอน'] = number_format($bill['change'], 2) . ' บาท';
    }

    log_add($code, 'sale', $user, 'ขายสินค้า บิล ' . $bill['no'], $detail, $total, $bill['no']);

    return $bill;
}

/**
 * ยกเลิกบิลที่บันทึกไปแล้ว — คืนสต๊อกกลับให้ครบ แล้วทำเครื่องหมายว่ายกเลิก
 * บิลไม่ถูกลบทิ้ง ยังเห็นในประวัติเสมอ เพื่อให้ตรวจย้อนหลังได้
 * คืนค่า: บิลที่ยกเลิกแล้ว หรือ null ถ้าไม่พบ / ยกเลิกไปแล้ว
 */
function bill_void($code, $no, $user, $reason, $reopen = false)
{
    $k = sale_key($code);
    if (!isset($_SESSION['sale'][$k])) {
        return null;
    }
    foreach ($_SESSION['sale'][$k] as $i => $b) {
        if ($b['no'] !== $no || !empty($b['void'])) {
            continue;
        }
        foreach ($b['lines'] as $l) {
            stock_adj_add($code, $l['sku'], $l['qty']);          // คืนของกลับเข้าสต๊อก
        }
        $_SESSION['sale'][$k][$i]['void']        = true;
        $_SESSION['sale'][$k][$i]['void_at']     = date('H:i');
        $_SESSION['sale'][$k][$i]['void_by']     = $user['name'];
        $_SESSION['sale'][$k][$i]['void_user']   = $user['username'];
        $_SESSION['sale'][$k][$i]['void_reason'] = trim($reason);

        $_SESSION['sale'][$k][$i]['void_mode'] = $reopen ? 'edit' : 'void';

        $v      = $_SESSION['sale'][$k][$i];
        $detail = array(
            'บิลเดิม'    => $v['no'] . ' · ขายเมื่อ ' . $v['time'] . ' น. โดย ' . $v['by'],
            'ยอดที่คืน'   => number_format($v['total'], 2) . ' บาท',
            'จำนวนที่คืนสต๊อก' => $v['items'] . ' รายการ · ' . $v['qty'] . ' ชิ้น',
            'เหตุผล'     => $v['void_reason'] !== '' ? $v['void_reason'] : 'ไม่ได้ระบุ',
        );
        if ($reopen) {
            $detail['การทำต่อ'] = 'ดึงรายการกลับเข้าตะกร้าเพื่อออกบิลใหม่ทดแทน';
        }

        log_add($code, 'void', $user,
                ($reopen ? 'ยกเลิกเพื่อแก้ไข บิล ' : 'ยกเลิกบิล ') . $v['no'],
                $detail, $v['total'], $v['no']);

        return $v;
    }
    return null;
}

/**
 * ดึงรายการทั้งบิลกลับเข้าตะกร้า — ใช้คู่กับการยกเลิกเพื่อแก้ไข
 * พนักงานไม่ต้องยิงบาร์โค้ดหรือกรอกใหม่ทั้งบิล แก้เฉพาะแถวที่ผิดแล้วรับเงินใหม่
 * จำนวนที่คืนเข้าสต๊อกจากการยกเลิกทำให้ใส่กลับได้ครบเสมอ
 */
function cart_from_bill($bill, $branch)
{
    $_SESSION['cart'] = array();
    foreach ($bill['lines'] as $l) {
        cart_set($l['sku'], $branch, $l['qty']);
    }
    return cart_count();
}

/** บิลนี้มีการรับคืนสินค้าไปแล้วหรือยัง — ถ้ามีแล้ว ห้ามยกเลิก/แก้ทั้งบิล (ของจะถูกคืนซ้ำ) */
function bill_returned_any($no)
{
    return !empty($_SESSION['ret_by_bill'][$no]) && array_sum($_SESSION['ret_by_bill'][$no]) > 0;
}

function bill_by_no($code, $no)
{
    foreach (bills_today($code) as $b) {
        if ($b['no'] === $no) {
            return $b;
        }
    }
    return null;
}

/** สรุปยอดขายวันนี้ของสาขา */
function sale_summary($code)
{
    $sum = array('bills' => 0, 'qty' => 0, 'total' => 0, 'cash' => 0, 'transfer' => 0, 'void' => 0);
    foreach (bills_today($code) as $b) {
        if (!empty($b['void'])) {                 // บิลที่ยกเลิกไม่นับเป็นยอดขาย
            $sum['void']++;
            continue;
        }
        $sum['bills']++;
        $sum['qty']   += $b['qty'];
        $sum['total'] += $b['total'];
        if ($b['method'] === 'cash') {
            $sum['cash'] += $b['total'];
        } else {
            $sum['transfer'] += $b['total'];
        }
    }
    return $sum;
}

/** ยอดขายวันนี้ของพนักงานคนหนึ่ง */
function sale_summary_user($code, $username)
{
    $sum = array('bills' => 0, 'qty' => 0, 'total' => 0);
    foreach (bills_today($code) as $b) {
        if ($b['by_user'] !== $username || !empty($b['void'])) {
            continue;
        }
        $sum['bills']++;
        $sum['qty']   += $b['qty'];
        $sum['total'] += $b['total'];
    }
    return $sum;
}

/** หมวดสินค้าทั้งหมด (ไว้ทำปุ่มกรอง) */
function product_cats()
{
    $out = array();
    foreach (demo_products() as $p) {
        if (!in_array($p['cat'], $out, true)) {
            $out[] = $p['cat'];
        }
    }
    return $out;
}

/** ค้นหาสินค้าสำหรับหน้าขาย */
function sale_products($branch, $q, $cat)
{
    $q   = trim($q);
    $out = array();
    foreach (demo_products() as $p) {
        if ($cat !== '' && $p['cat'] !== $cat) {
            continue;
        }
        if ($q !== '' && stripos($p['name'], $q) === false && stripos($p['sku'], $q) === false) {
            continue;
        }
        $p['qty']   = product_qty($p, $branch);
        $p['price'] = product_price($p);
        $out[]      = $p;
    }
    return $out;
}
