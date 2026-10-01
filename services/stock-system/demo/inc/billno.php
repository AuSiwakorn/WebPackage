<?php
/* ==========================================================
   AOSTOCK DEMO — เลขที่บิลและ VAT (ฝ่ายบัญชีตั้งค่าได้)
   ----------------------------------------------------------
   - ตอนขาย พนักงานเลือกได้ว่าบิลนี้ "VAT" หรือ "ไม่ VAT"
   - บิล VAT กับบิลไม่ VAT ใช้เลขคนละชุด แต่ละสาขามีรหัสนำหน้าของตัวเอง 2 ตัว
       รูปแบบ  {รหัส}{ปี ค.ศ.}-{เดือน}-{เลขรัน 4 หลัก}   เช่น BP2026-01-0001
       เลขรันนับใหม่ทุกเดือน แยกตามสาขาและตามชุด (VAT / ไม่ VAT)
   - ราคาขายเป็นราคารวม VAT แล้ว → บิล VAT แยกยอดก่อน VAT และ VAT 7% ให้
   - ฝ่ายบัญชีตั้งรหัสนำหน้า + เลขผู้เสียภาษีของแต่ละสาขาได้เอง (หน้า "ตั้งค่าบัญชี")

   เดโมเก็บค่าที่ตั้งไว้ใน $_SESSION['cfg']['acct'][ สาขา ][ ชื่อค่า ]
   ระบบจริง: คอลัมน์ใน ao_stock_branch และเก็บเลขที่บิลตอนออกบิล (ไม่คำนวณย้อนหลัง)
   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

require_once dirname(__FILE__) . '/config.php';

define('VAT_RATE', 7);
define('BILL_RUN_DIGITS', 4);

function acct_setting_defaults()
{
    return array(
        'HQ' => array('prefix_vat' => 'HQV', 'prefix_novat' => 'HQ', 'tax_id' => '0105566012345', 'tax_branch' => '00000'),
        'RS' => array('prefix_vat' => 'RSV', 'prefix_novat' => 'RS', 'tax_id' => '0105566012345', 'tax_branch' => '00001'),
        'BN' => array('prefix_vat' => 'BNV', 'prefix_novat' => 'BN', 'tax_id' => '0105566012345', 'tax_branch' => '00002'),
    );
}

function acct_setting($code, $key)
{
    if (isset($_SESSION['cfg']['acct'][$code][$key])) {
        return $_SESSION['cfg']['acct'][$code][$key];
    }
    $d = acct_setting_defaults();
    return isset($d[$code][$key]) ? $d[$code][$key] : '';
}

function acct_setting_set($code, $key, $val)
{
    $_SESSION['cfg']['acct'][$code][$key] = $val;
}

/** รหัสนำหน้าของชุดเลข: $vat = true → ชุด VAT */
function bill_prefix($code, $vat)
{
    return acct_setting($code, $vat ? 'prefix_vat' : 'prefix_novat');
}

/** ประกอบเลขที่บิล เช่น BP2026-01-0001 */
function bill_no_format($prefix, $ts, $n)
{
    return $prefix . date('Y', $ts) . '-' . date('m', $ts) . '-' . str_pad($n, BILL_RUN_DIGITS, '0', STR_PAD_LEFT);
}

/** แยกยอดรวม (รวม VAT แล้ว) เป็น array(ก่อน VAT, VAT) */
function vat_split($total)
{
    $vat = round($total * VAT_RATE / (100 + VAT_RATE), 2);
    return array(round($total - $vat, 2), $vat);
}

/** ชื่อชุดเลข */
function bill_type_label($vat)
{
    return $vat ? 'VAT' : 'ไม่ VAT';
}

/**
 * จำนวนบิลที่ออกไปแล้วในเดือนนี้ก่อนวันที่ $ts แยกตามชุด — ใช้ต่อเลขรัน
 * วันก่อน ๆ มาจากข้อมูลสมมติ (past_bills_gen ใน return.php)
 * คืน array('v' => n, 'n' => n)
 */
function month_bill_counts($code, $ts)
{
    static $cache = array();
    $ck = $code . '|' . date('Ymd', $ts);
    if (isset($cache[$ck])) {
        return $cache[$ck];
    }
    $cnt   = array('v' => 0, 'n' => 0);
    $first = strtotime(date('Y-m-01', $ts));
    for ($d = $first; $d < strtotime(date('Y-m-d', $ts)); $d = strtotime('+1 day', $d)) {
        foreach (past_bills_gen($code, $d) as $b) {
            $cnt[$b['vat'] ? 'v' : 'n']++;
        }
    }
    $cache[$ck] = $cnt;
    return $cnt;
}

/** เลขที่บิลใบถัดไปของวันนี้ (นับรวมบิลที่ถูกยกเลิก — เลขที่ใช้ไปแล้วห้ามใช้ซ้ำ) */
function bill_next_no_series($code, $vat)
{
    $cnt = month_bill_counts($code, time());
    $n   = $cnt[$vat ? 'v' : 'n'];
    foreach (bills_today($code) as $b) {
        if (!empty($b['vat']) === (bool) $vat) {
            $n++;
        }
    }
    return bill_no_format(bill_prefix($code, $vat), time(), $n + 1);
}

/** ตรวจรหัสนำหน้า: A–Z / 0–9 ยาว 1–6 ตัว และไม่ซ้ำกับชุดอื่นทุกสาขา */
function acct_prefix_error($code, $key, $val, $all)
{
    if (!preg_match('/^[A-Z0-9]{1,6}$/', $val)) {
        return 'รหัสต้องเป็นตัวอักษรภาษาอังกฤษพิมพ์ใหญ่หรือตัวเลข 1–6 ตัว';
    }
    foreach ($all as $c => $pp) {
        foreach ($pp as $k => $v) {
            if (($c !== $code || $k !== $key) && $v === $val) {
                return 'รหัส ' . $val . ' ซ้ำกับชุดเลขอื่น (' . branch_name($c) . ' · ' . ($k === 'prefix_vat' ? 'VAT' : 'ไม่ VAT') . ')';
            }
        }
    }
    return '';
}
