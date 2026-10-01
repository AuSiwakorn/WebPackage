<?php
/* ==========================================================
   AOSTOCK DEMO — รับคืนสินค้า
   ----------------------------------------------------------
   ค้นบิลเก่า → ดูว่ายังอยู่ในกำหนดวันไหม → เลือกรายการ/จำนวนที่คืน
   → เลือกเหตุผล → ยืนยันยอดเงินคืน → ออกใบรับคืน (RT-)

   กติกาที่ตกลงไว้
   - ต้องมีสิทธิ์เสริม refund
   - บิลต้องไม่เก่ากว่า backdate_days(สาขา) วัน (ผู้ดูแลตั้ง ค่าเริ่มต้น 7) · เกินกำหนดผู้ดูแลคืนให้ได้
   - คืนบางรายการ / บางชิ้นได้ · คืนเกินจำนวนที่ซื้อ (หักที่คืนไปแล้ว) ไม่ได้
   - ของกลับเข้าสต๊อกเฉพาะเหตุผลที่ขายต่อได้ (ซื้อผิดรุ่น/ผิดแบบ)
     เหตุผลอื่นไม่เข้าสต๊อก — แยกเก็บไว้ เพราะไม่ควรเอาไปขายต่อ
   - คืนเป็นเงินสดจากลิ้นชักของวันนี้เท่านั้น (ไม่คืนด้วยการโอน)
     จึงต้องเปิดร้านก่อน · ยอดเงินคืนแก้ได้ แต่ไม่เกินราคาที่ลูกค้าจ่าย
     และถ้าแก้ต้องใส่เหตุผล

   ยังไม่มีฐานข้อมูล:
     บิลวันก่อน   สร้างจากข้อมูลสมมติที่คงที่ (past_bills)
     บิลวันนี้    อ่านจาก $_SESSION['sale'] เหมือนหน้าขาย
     ใบรับคืน     $_SESSION['ret'][ 'RS|20260925' ][] = เอกสารหนึ่งใบ
     คืนไปแล้ว    $_SESSION['ret_by_bill'][ เลขบิล ][ SKU ] = จำนวน
   ระบบจริง: ตาราง return_doc + return_item + stock_move + cash_move

   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/data.php';
require_once dirname(__FILE__) . '/sale.php';
require_once dirname(__FILE__) . '/store.php';
require_once dirname(__FILE__) . '/activity.php';
require_once dirname(__FILE__) . '/report.php';     // demo_sales_of()
require_once dirname(__FILE__) . '/adjust.php';     // thai_day_month()
require_once dirname(__FILE__) . '/billno.php';     // เลขที่บิล / VAT

/** เดโมสร้างบิลย้อนหลังกี่วัน — มากกว่ากำหนดคืน เพื่อให้เห็นบิลที่เกินกำหนดด้วย */
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

function ret_product($sku)
{
    foreach (demo_products() as $p) {
        if ($p['sku'] === $sku) {
            return $p;
        }
    }
    return null;
}

/**
 * บิลของวันก่อน (ข้อมูลสมมติที่คงที่) — ใช้จำนวนบิลจากข้อมูลรายงาน แต่จำกัดคนละไม่เกิน 5 ใบต่อวัน
 * ผูกสาขาตาม "สาขา ณ วันนั้น" ของพนักงาน เหมือนรายงานยอดขาย
 */
/** บิลของวันก่อนพร้อมเลขที่ — เลขรันต่อจากบิลก่อนหน้าในเดือนเดียวกัน แยกชุด VAT / ไม่ VAT */
function past_bills($code, $ts)
{
    $rows = past_bills_gen($code, $ts);
    $cnt  = month_bill_counts($code, $ts);
    foreach ($rows as $i => $b) {
        $k = $b['vat'] ? 'v' : 'n';
        $cnt[$k]++;
        $rows[$i]['no'] = bill_no_format(bill_prefix($code, $b['vat']), $ts, $cnt[$k]);
    }
    return $rows;
}

/** สร้างบิลสมมติของวันหนึ่ง (ยังไม่มีเลขที่) — คงที่ทุกครั้งที่เรียก */
function past_bills_gen($code, $ts)
{
    static $cache = array();
    $day = date('Ymd', $ts);
    $ck  = $code . '|' . $day;
    if (isset($cache[$ck])) {
        return $cache[$ck];
    }

    $prods = demo_products();
    $np    = count($prods);
    $rows  = array();

    foreach (demo_users_all() as $un => $u) {
        if ($u['role'] !== 'staff') {
            continue;
        }
        $r = demo_sales_of($un, $ts);
        if ($r === null || $r['branch'] !== $code) {
            continue;
        }
        $n = min(5, (int) $r['bills']);
        for ($i = 0; $i < $n; $i++) {
            $s     = abs(crc32($un . '|bill|' . $day . '|' . $i));
            $lines = array();
            $nl    = 1 + ($s % 2);
            $used  = array();
            for ($j = 0; $j < $nl; $j++) {
                $p = $prods[(($s >> 3) + $j * 7) % $np];
                if (isset($used[$p['sku']])) {
                    continue;
                }
                $used[$p['sku']] = true;
                $q     = 1 + (($s >> (5 + $j)) % 2);
                $price = product_price($p);
                $lines[] = array('sku' => $p['sku'], 'name' => $p['name'], 'unit' => $p['unit'],
                                 'qty' => $q, 'price' => $price, 'sum' => $price * $q);
            }
            $qty = 0;
            $tot = 0;
            foreach ($lines as $l) {
                $qty += $l['qty'];
                $tot += $l['sum'];
            }
            $h = 10 + (($i * 2 + ($s % 2)) % 10);
            $rows[] = array(
                'no'      => '',
                'date'    => $day,
                'time'    => str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad(($s >> 7) % 60, 2, '0', STR_PAD_LEFT),
                'branch'  => $code,
                'by'      => $u['name'],
                'by_user' => $un,
                'method'  => ((($s >> 11) % 3) === 0) ? 'transfer' : 'cash',
                'vat'     => ((($s >> 13) % 10) < 3),          // ราว 30% ลูกค้าขอบิล VAT
                'lines'   => $lines,
                'items'   => count($lines),
                'qty'     => $qty,
                'total'   => $tot,
            );
        }
    }

    usort($rows, 'ret_cmp_time');
    $cache[$ck] = $rows;
    return $rows;
}

function ret_cmp_time($a, $b)
{
    return strcmp($a['time'], $b['time']);
}

/** บิลทุกใบของวันหนึ่ง — วันนี้อ่านจาก session (ไม่รวมบิลที่ยกเลิก) */
function bills_of_day($code, $ts)
{
    if (date('Ymd', $ts) === date('Ymd')) {
        $out = array();
        foreach (bills_today($code) as $b) {
            if (!empty($b['void'])) {
                continue;
            }
            $b['date'] = date('Ymd');
            $out[] = $b;
        }
        return $out;
    }
    return past_bills($code, $ts);
}

/** ค้นบิลย้อนหลัง — เลขบิล ชื่อสินค้า หรือ SKU · ใหม่สุดขึ้นก่อน */
function return_find_bills($code, $q, $limit = 40)
{
    $q     = trim($q);
    $today = strtotime(date('Y-m-d'));
    $out   = array();
    for ($d = 0; $d <= return_lookback_days(); $d++) {
        $ts   = strtotime('-' . $d . ' day', $today);
        $list = array_reverse(bills_of_day($code, $ts));
        foreach ($list as $b) {
            if ($q !== '' && !ret_bill_match($b, $q)) {
                continue;
            }
            $out[] = $b;
            if (count($out) >= $limit) {
                return $out;
            }
        }
    }
    return $out;
}

function ret_bill_match($b, $q)
{
    if (stripos($b['no'], $q) !== false) {
        return true;
    }
    foreach ($b['lines'] as $l) {
        if (stripos($l['name'], $q) !== false || stripos($l['sku'], $q) !== false) {
            return true;
        }
    }
    return false;
}

/** หาบิลจากเลขที่บิล (เช่น RS2026-09-0012 / RSV2026-09-0003) */
function return_bill($code, $no)
{
    /* เลขที่บิลไม่มีวันที่ในตัว (มีแค่ปี-เดือน) จึงไล่หาย้อนหลังตามช่วงที่ค้นได้ */
    $today = strtotime(date('Y-m-d'));
    for ($d = 0; $d <= return_lookback_days(); $d++) {
        foreach (bills_of_day($code, strtotime('-' . $d . ' day', $today)) as $b) {
            if ($b['no'] === $no) {
                return $b;
            }
        }
    }
    return null;
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

function returned_of_bill($no)
{
    return (isset($_SESSION['ret_by_bill'][$no]) && is_array($_SESSION['ret_by_bill'][$no]))
         ? $_SESSION['ret_by_bill'][$no] : array();
}

function bill_has_returns($no)
{
    return bill_returned_any($no);            // อยู่ใน sale.php — หน้าขาย/ประวัติใช้กันยกเลิกบิลที่มีการคืนแล้ว
}

/** แต่ละรายการในบิล + คืนไปแล้ว + คืนได้อีก */
function return_lines($bill)
{
    $done = returned_of_bill($bill['no']);
    $out  = array();
    foreach ($bill['lines'] as $l) {
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
 * คืน array('ok' => bool, 'code' => ok|late|late_admin|done, 'msg' => ข้อความ, 'left' => วันที่เหลือ)
 * บิลที่เกินกำหนด: พนักงานคืนไม่ได้ · ผู้ดูแลคืนให้ได้ (late_admin)
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
    if ($age > $limit && $user && $user['role'] === 'admin') {
        return array('ok' => true, 'code' => 'late_admin',
                     'msg' => 'เกินกำหนด ' . $limit . ' วัน (ผ่านมา ' . $age . ' วัน) — ผู้ดูแลคืนให้ได้', 'left' => 0);
    }
    if ($age > $limit) {
        return array('ok' => false, 'code' => 'late',
                     'msg' => 'เกินกำหนดคืน ' . $limit . ' วัน (ผ่านมา ' . $age . ' วัน) — ต้องให้ผู้ดูแลทำ', 'left' => 0);
    }
    $left = $limit - $age;
    return array('ok' => true, 'code' => 'ok',
                 'msg' => $left === 0 ? 'คืนได้ถึงวันนี้' : 'คืนได้อีก ' . $left . ' วัน', 'left' => $left);
}

/* ==========================================================
   ใบรับคืน
   ========================================================== */

function ret_key($code)
{
    return $code . '|' . date('Ymd');
}

function returns_today($code)
{
    $k = ret_key($code);
    return isset($_SESSION['ret'][$k]) ? $_SESSION['ret'][$k] : array();
}

function return_next_no($code)
{
    return 'RT-' . date('ymd') . '-' . str_pad(count(returns_today($code)) + 1, 4, '0', STR_PAD_LEFT);
}

function return_by_no($code, $no)
{
    foreach (returns_today($code) as $r) {
        if ($r['no'] === $no) {
            return $r;
        }
    }
    return null;
}

/**
 * บันทึกการรับคืน
 * $qtys   = array( SKU => จำนวนที่คืน )
 * $refund = ยอดเงินคืนที่พนักงานยืนยัน (ว่าง = ใช้ยอดคำนวณ)
 * คืนค่า array('doc' => ใบรับคืน) หรือ array('error' => ข้อความ)
 */
function return_save($code, $user, $bill, $qtys, $reason, $note, $refund, $refundNote)
{
    if (!can($user, 'refund')) {
        return array('error' => 'ไม่มีสิทธิ์รับคืนสินค้า');
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
        $sum     = $want * (float) $l['price'];
        $lines[] = array('sku' => $l['sku'], 'name' => $l['name'], 'unit' => $l['unit'],
                         'qty' => $want, 'price' => (float) $l['price'], 'sum' => $sum);
        $calc += $sum;
        $qty  += $want;
    }
    if (!$lines) {
        return array('error' => 'ยังไม่ได้ใส่จำนวนที่คืนสักรายการ');
    }

    $refund = ($refund === '' || $refund === null) ? $calc : round((float) $refund, 2);
    if ($refund < 0 || $refund > $calc) {
        return array('error' => 'ยอดเงินคืนต้องอยู่ระหว่าง 0 ถึง ' . money2($calc) . ' บาท (ไม่เกินที่ลูกค้าจ่ายสำหรับรายการที่คืน)');
    }
    if (abs($refund - $calc) >= 0.01 && trim($refundNote) === '') {
        return array('error' => 'ยอดเงินคืนไม่เท่ากับยอดคำนวณ — กรุณาใส่เหตุผลที่ปรับยอด');
    }

    $restock = $reasons[$reason]['restock'];
    $doc = array(
        'no'          => return_next_no($code),
        'time'        => date('H:i'),
        'branch'      => $code,
        'by'          => $user['name'],
        'by_user'     => $user['username'],
        'bill_no'     => $bill['no'],
        'bill_date'   => $bill['date'],
        'bill_by'     => $bill['by'],
        'reason'      => $reason,
        'note'        => trim($note),
        'restock'     => $restock,
        'lines'       => $lines,
        'items'       => count($lines),
        'qty'         => $qty,
        'calc'        => $calc,
        'refund'      => $refund,
        'refund_note' => trim($refundNote),
    );

    foreach ($lines as $l) {
        if ($restock) {
            stock_adj_add($code, $l['sku'], $l['qty']);          // ของสมบูรณ์ → กลับเข้าสต๊อก
        }
        if (!isset($_SESSION['ret_by_bill'][$bill['no']][$l['sku']])) {
            $_SESSION['ret_by_bill'][$bill['no']][$l['sku']] = 0;
        }
        $_SESSION['ret_by_bill'][$bill['no']][$l['sku']] += $l['qty'];
    }

    $k = ret_key($code);
    if (!isset($_SESSION['ret'][$k])) {
        $_SESSION['ret'][$k] = array();
    }
    $_SESSION['ret'][$k][] = $doc;

    /* ---- เก็บลงประวัติการทำรายการ ---- */
    $names = array();
    foreach ($lines as $l) {
        $names[] = $l['name'] . ' ×' . $l['qty'];
    }
    $detail = array(
        'บิลเดิม'      => $bill['no'] . ' · ' . thai_date_full(strtotime($bill['date'])) . ' ' . $bill['time'] . ' น. โดย ' . $bill['by'],
        'รายการที่คืน'  => implode(' · ', $names),
        'เหตุผล'       => return_reason_label($reason) . ($doc['note'] !== '' ? ' — ' . $doc['note'] : ''),
        'สต๊อก'        => $restock ? 'กลับเข้าสต๊อก ' . $qty . ' ชิ้น' : 'ไม่เข้าสต๊อก (แยกเก็บ)',
        'ยอดตามราคาขาย' => money2($calc) . ' บาท',
        'คืนเงินสด'     => money2($refund) . ' บาท' . ($doc['refund_note'] !== '' ? ' — ปรับยอด: ' . $doc['refund_note'] : ''),
        'ผู้รับคืน'      => $user['name'],
    );
    log_add($code, 'return', $user, 'รับคืนสินค้า ' . $doc['no'] . ' (บิล ' . $bill['no'] . ')', $detail, $refund, $doc['no']);

    return array('doc' => $doc);
}
