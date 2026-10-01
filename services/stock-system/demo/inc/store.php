<?php
/* ==========================================================
   AOSTOCK DEMO — เปิด / ปิดร้านประจำวัน และเงินทอนในลิ้นชัก
   ----------------------------------------------------------
   หลักการ
   - ร้านเปิดวันละครั้งต่อสาขา ใครมาถึงก่อนเป็นคนเปิด
     คนที่ login ทีหลังเข้าใช้งานได้เลย ไม่ต้องเปิดซ้ำ
   - เงินทอนเริ่มวัน = ยอดที่แยกไว้ตอนปิดร้านเมื่อวาน + ที่เติมเพิ่มวันนี้
   - ตอนปิดร้านจะนับเงินจริง เทียบกับยอดที่ควรมี แล้วแยกเงินทอนไว้สำหรับพรุ่งนี้

   เดโมเก็บสถานะไว้ใน session (หายเมื่อออกจากระบบ)
   ระบบจริง: ตาราง store_day + cash_move ตามที่ออกแบบไว้
   ========================================================== */

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/activity.php';
require_once dirname(__FILE__) . '/sale.php';        // sale_summary() — ยอดขายเงินสดวันนี้

/** เงินทอนมาตรฐานของสาขา (ฟิลด์ default_float) */
function branch_default_float($code)
{
    return branch_setting($code, 'default_float');      // ผู้ดูแลตั้งที่หน้า "ตั้งค่าสาขา"
}

/** เงินทอนที่แยกไว้ตอนปิดร้านเมื่อวาน (เดโมสร้างจากรหัสสาขา + วันที่) */
function store_carry($code)
{
    $y     = strtotime('-1 day');
    $s     = abs(crc32($code . date('Ymd', $y)));
    $staff = demo_users();
    $names = array();
    foreach ($staff as $u) {
        if ($u['branch'] === $code) {
            $names[] = $u['name'];
        }
    }
    if (!$names) {
        $names[] = 'พนักงาน';
    }

    return array(
        'amount' => branch_default_float($code),
        'by'     => $names[$s % count($names)],
        'time'   => date('j/n', $y) . ' ' . str_pad(18 + ($s % 4), 2, '0', STR_PAD_LEFT) . ':' . str_pad(($s >> 3) % 60, 2, '0', STR_PAD_LEFT),
    );
}

/** เงินที่เติม / หยิบออกจากลิ้นชักระหว่างวัน (เดโม) */
function store_daily_cash($code)
{
    $s = abs(crc32($code . date('Ymd')));
    return array(
        'topup'    => (($s % 3) === 0) ? 500 : 0,
        'withdraw' => 40 + (($s >> 5) % 160),
    );
}

/* ---------- สถานะร้านใน session ---------- */

function store_key($code)
{
    return $code . '|' . date('Ymd');
}

function store_state($code)
{
    $k = store_key($code);
    return isset($_SESSION['store'][$k]) ? $_SESSION['store'][$k] : null;
}

function store_is_open($code)
{
    $st = store_state($code);
    return $st !== null && empty($st['closed_at']);
}

function store_is_closed($code)
{
    $st = store_state($code);
    return $st !== null && !empty($st['closed_at']);
}

/** บันทึกการเปิดร้าน */
function store_open($code, $user, $topup, $counted, $reason)
{
    $carry = store_carry($code);
    $topup = max(0, (int) $topup);

    $state = array(
        'branch'      => $code,
        'opened_at'   => date('H:i'),
        'opened_by'   => $user['name'],
        'opened_user' => $user['username'],
        'carry'       => $carry['amount'],
        'topup'       => $topup,
        'float'       => $carry['amount'] + $topup,
        'counted'     => null,
        'reason'      => '',
        'closed_at'   => '',
    );

    /* กรณีแจ้งว่านับเงินทอนได้ไม่ตรงกับยอดยกมา */
    if ($counted !== null && $counted !== '' && (int) $counted !== (int) $carry['amount']) {
        $state['counted'] = (int) $counted;
        $state['reason']  = trim($reason);
        $state['float']   = (int) $counted + $topup;
    }

    $_SESSION['store'][store_key($code)] = $state;

    /* ---- เก็บลงประวัติการทำรายการ ---- */
    $detail = array(
        'เวลาที่เปิด'     => $state['opened_at'] . ' น.',
        'เงินทอนยกมา'    => money2($carry['amount']) . ' บาท (ปิดร้านเมื่อวานโดย ' . $carry['by'] . ')',
    );

    if ($state['counted'] !== null) {
        $diff = (int) $state['counted'] - (int) $carry['amount'];
        $detail['นับได้จริง']   = money2($state['counted']) . ' บาท';
        $detail['ผลต่างจากยกมา'] = ($diff > 0 ? '+' : '') . money2($diff) . ' บาท'
                                 . ($state['reason'] !== '' ? ' — ' . $state['reason'] : '');
    }

    $detail['เติมเงินทอนเพิ่ม'] = $topup > 0 ? '+' . money2($topup) . ' บาท' : 'ไม่ได้เติม';
    $detail['เงินทอนเริ่มวันนี้'] = money2($state['float']) . ' บาท';

    log_add($code, 'open', $user, 'เปิดร้าน ' . branch_name($code), $detail, $state['float']);

    return $state;
}

/** บันทึกการปิดร้าน */
function store_close($code, $user, $counted, $keep, $note)
{
    $k = store_key($code);
    if (!isset($_SESSION['store'][$k])) {
        return null;
    }
    $_SESSION['store'][$k]['closed_at']   = date('H:i');
    $_SESSION['store'][$k]['closed_by']   = $user['name'];
    $_SESSION['store'][$k]['cash_counted'] = (int) $counted;
    $_SESSION['store'][$k]['keep']        = max(0, (int) $keep);
    $_SESSION['store'][$k]['note']        = trim($note);

    /* ---- เก็บลงประวัติการทำรายการ ---- */
    $st       = $_SESSION['store'][$k];
    $expected = store_expected_cash($code);
    $diff     = (int) $st['cash_counted'] - (int) $expected;

    $detail = array(
        'เวลาที่ปิด'      => $st['closed_at'] . ' น.',
        'เงินที่ควรมี'     => money2($expected) . ' บาท',
        'นับได้จริง'      => money2($st['cash_counted']) . ' บาท',
        'ผลต่าง'         => ($diff > 0 ? 'เกิน +' : ($diff < 0 ? 'ขาด −' : 'ตรงพอดี ')) . money2(abs($diff)) . ' บาท',
        'เงินทอนที่แยกไว้พรุ่งนี้' => money2($st['keep']) . ' บาท',
        'นำส่ง'          => money2(max(0, (int) $st['cash_counted'] - (int) $st['keep'])) . ' บาท',
    );
    if ($st['note'] !== '') {
        $detail['หมายเหตุ'] = $st['note'];
    }

    log_add($code, 'close', $user, 'ปิดร้าน ' . branch_name($code), $detail, $st['cash_counted']);

    return $_SESSION['store'][$k];
}

/** เปิดร้านใหม่หลังปิดไปแล้ว — ผู้ดูแลเท่านั้น ต้องมีเหตุผล
    ยอดปิดร้านรอบก่อนเก็บไว้ในประวัติ แล้วร้านกลับมาเปิดขายต่อได้ ปิดใหม่อีกครั้งตามปกติ */
function store_reopen($code, $user, $reason)
{
    $k = store_key($code);
    if (!isset($_SESSION['store'][$k]) || empty($_SESSION['store'][$k]['closed_at'])) {
        return null;
    }
    $st = $_SESSION['store'][$k];
    $_SESSION['store'][$k]['reopens'][] = array(
        'at' => date('H:i'), 'by' => $user['name'], 'reason' => trim($reason),
        'closed_at' => $st['closed_at'], 'closed_by' => $st['closed_by'], 'counted' => $st['cash_counted'],
        'diff' => (float) $st['cash_counted'] - (float) store_expected_cash($code),   // ขาด/เกินของรอบที่ปิดไป
    );
    $_SESSION['store'][$k]['closed_at'] = '';

    log_add($code, 'open', $user, 'เปิดร้านใหม่หลังปิด ' . branch_name($code), array(
        'ปิดไปเมื่อ'  => $st['closed_at'] . ' น. โดย ' . $st['closed_by'],
        'เงินที่นับได้ตอนปิด' => money2($st['cash_counted']) . ' บาท',
        'เหตุผล'     => trim($reason),
        'ผู้เปิดใหม่'  => $user['name'] . ' (ผู้ดูแล)',
    ));
    return $_SESSION['store'][$k];
}

/** เงินสดที่คืนลูกค้าวันนี้ (รับคืนสินค้า) — จ่ายออกจากลิ้นชักของวันนี้เสมอ
    แม้บิลเดิมจะเป็นของวันที่ปิดร้านไปแล้ว */
function store_refunds($code)
{
    $k = $code . '|' . date('Ymd');
    $t = 0;
    if (isset($_SESSION['ret'][$k])) {
        foreach ($_SESSION['ret'][$k] as $r) {
            $t += $r['refund'];
        }
    }
    return $t;
}

/** ยอดขายเงินสดวันนี้ (ไม่นับบิลที่ยกเลิก · บิลโอน/พร้อมเพย์ไม่เข้าลิ้นชัก) */
function store_cash_sales($code)
{
    $s = sale_summary($code);
    return $s['cash'];
}

/** ยอดเงินที่ควรมีในลิ้นชักตอนนี้
    = เงินทอนเริ่มวัน + เติม + ขายเงินสด − หยิบออก − คืนเงินลูกค้า */
function store_expected_cash($code)
{
    $st = store_state($code);
    if ($st === null) {
        return 0;
    }
    $c = store_daily_cash($code);
    return $st['float'] + $c['topup'] + store_cash_sales($code) - $c['withdraw'] - store_refunds($code);
}

function money2($n)
{
    return number_format($n, 2);
}
