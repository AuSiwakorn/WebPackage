<?php
/* ==========================================================
   AOSTOCK DEMO — ข้อมูลของฝ่ายบัญชี
   บิลขายรายวัน (แยก VAT / ไม่ VAT) และเงินเข้าแยกสาขา
   ========================================================== */

require_once dirname(__FILE__) . '/sale.php';
require_once dirname(__FILE__) . '/store.php';
require_once dirname(__FILE__) . '/return.php';
require_once dirname(__FILE__) . '/billno.php';

/** บิลทั้งหมดของสาขาในวันหนึ่ง รวมบิลที่ยกเลิก (บัญชีต้องเห็นเลขที่ครบทุกใบ) */
function acct_bills($code, $ts)
{
    if (date('Ymd', $ts) === date('Ymd')) {
        $out = array();
        foreach (bills_today($code) as $b) {
            $b['date'] = date('Ymd');
            $b['vat']  = !empty($b['vat']);
            $out[] = $b;
        }
        return $out;
    }
    return past_bills($code, $ts);
}

/** เพิ่มยอดก่อน VAT / VAT ให้บิล */
function acct_bill_row($b, $code)
{
    $vs = $b['vat'] ? vat_split($b['total']) : array($b['total'], 0);
    $b['branch'] = $code;
    $b['base']   = $vs[0];
    $b['vatamt'] = $vs[1];
    return $b;
}

/**
 * สรุปของสาขาในวันหนึ่ง
 * คืน array(bills, v, n, void, total, base, vat, cash, transfer, refund, net, rows)
 */
function acct_day($code, $ts)
{
    $s = array('bills' => 0, 'v' => 0, 'n' => 0, 'void' => 0, 'total' => 0, 'base' => 0, 'vat' => 0,
               'cash' => 0, 'transfer' => 0, 'refund' => 0, 'net' => 0, 'rows' => array());
    foreach (acct_bills($code, $ts) as $b) {
        $b = acct_bill_row($b, $code);
        $s['rows'][] = $b;
        $s['bills']++;
        $s[$b['vat'] ? 'v' : 'n']++;
        if (!empty($b['void'])) {
            $s['void']++;
            continue;                                   // บิลยกเลิก: นับใบ แต่ไม่นับเงิน
        }
        $s['total'] += $b['total'];
        $s['base']  += $b['vat'] ? $b['base'] : 0;     // มูลค่าก่อน VAT นับเฉพาะบิล VAT
        $s['vat']   += $b['vatamt'];
        $s[$b['method'] === 'cash' ? 'cash' : 'transfer'] += $b['total'];
    }
    if (date('Ymd', $ts) === date('Ymd')) {
        $s['refund'] = store_refunds($code);
    }
    $s['net'] = $s['cash'] + $s['transfer'] - $s['refund'];
    return $s;
}

/** สรุปทั้งเดือน (ตั้งแต่วันที่ 1 ถึงวันนี้หรือสิ้นเดือน) ของสาขา */
function acct_month($code, $ts)
{
    $m = array('bills' => 0, 'v' => 0, 'n' => 0, 'void' => 0, 'total' => 0, 'base' => 0, 'vat' => 0,
               'cash' => 0, 'transfer' => 0, 'refund' => 0, 'net' => 0);
    $end = min(strtotime(date('Y-m-t', $ts)), strtotime(date('Y-m-d')));
    for ($d = strtotime(date('Y-m-01', $ts)); $d <= $end; $d = strtotime('+1 day', $d)) {
        $s = acct_day($code, $d);
        foreach ($m as $k => $v) {
            $m[$k] += $s[$k];
        }
    }
    return $m;
}
