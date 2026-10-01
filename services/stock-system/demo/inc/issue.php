<?php
/* ==========================================================
   AOSTOCK DEMO — เบิก / ตัดออกจากสต๊อก
   ----------------------------------------------------------
   ใช้กับของที่ออกจากสต๊อกโดย "ไม่ได้ขาย" เช่น เบิกใช้ภายใน ชำรุด หมดอายุ สูญหาย
   ทำเป็นใบแบบเดียวกับใบรับเข้า: เลือกสินค้า → ปรับจำนวน → เลือกเหตุผล → บันทึกทั้งใบ

   ยังไม่มีฐานข้อมูล:
     ใบที่กำลังทำ   $_SESSION['issue_draft'][ SKU ] = จำนวน
     หัวใบที่ค้างไว้  $_SESSION['issue_meta'] = reason / ref / note (ใช้ตอนดึงใบเก่ามาแก้)
     ใบที่บันทึกแล้ว $_SESSION['issue'][ 'BN|20260924' ][] = เอกสารหนึ่งใบ
   ระบบจริง: ตาราง issue_doc + issue_item + stock_move

   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

require_once dirname(__FILE__) . '/stock.php';
require_once dirname(__FILE__) . '/store.php';      // money2()
require_once dirname(__FILE__) . '/activity.php';

/* ---------- เหตุผลการตัดออก ----------
   note = ต้องกรอกหมายเหตุประกอบเสมอ (กรณีที่ต้องตรวจสอบย้อนหลังได้) */
function issue_reasons()
{
    return array(
        'use'     => array('label' => 'เบิกใช้ภายใน',   'hint' => 'ใช้ในร้าน / คลัง / สำนักงาน', 'note' => false),
        'damaged' => array('label' => 'ชำรุด / เสียหาย', 'hint' => 'แตก ขาด เปียก ใช้งานไม่ได้',   'note' => false),
        'expired' => array('label' => 'หมดอายุ',        'hint' => 'เลยวันหมดอายุ ขายไม่ได้',       'note' => false),
        'return'  => array('label' => 'คืนผู้จำหน่าย',    'hint' => 'ส่งคืนซัพพลายเออร์',            'note' => false),
        'branch'  => array('label' => 'ส่งไปสาขาอื่น',  'hint' => 'ระบุสาขาปลายทางในหมายเหตุ',     'note' => true),
        'lost'    => array('label' => 'สูญหาย',         'hint' => 'หาไม่พบ ต้องระบุรายละเอียด',   'note' => true),
        'other'   => array('label' => 'อื่น ๆ',          'hint' => 'ต้องระบุรายละเอียด',             'note' => true),
    );
}

function issue_reason_label($key)
{
    $r = issue_reasons();
    return isset($r[$key]) ? $r[$key]['label'] : $key;
}

function issue_reason_needs_note($key)
{
    $r = issue_reasons();
    return isset($r[$key]) && $r[$key]['note'];
}

/* ==========================================================
   ใบที่กำลังทำ (ร่าง)
   ต่างจากใบรับเข้าตรงที่ "ตัดเกินของที่มีไม่ได้"
   ทุกครั้งที่เพิ่ม/แก้จำนวน จะถูกจำกัดไว้ไม่เกินยอดคงเหลือของสาขา
   ========================================================== */

function idraft_all()
{
    return isset($_SESSION['issue_draft']) && is_array($_SESSION['issue_draft'])
         ? $_SESSION['issue_draft'] : array();
}

function idraft_count()
{
    $n = 0;
    foreach (idraft_all() as $q) {
        $n += (int) $q;
    }
    return $n;
}

/** จำข้อความเตือนไว้แสดงครั้งถัดไป (เช่น ปรับจำนวนลงให้เพราะของไม่พอ) */
function idraft_flash($msg = null)
{
    if ($msg !== null) {
        $_SESSION['issue_flash'] = $msg;
        return $msg;
    }
    $m = isset($_SESSION['issue_flash']) ? $_SESSION['issue_flash'] : '';
    unset($_SESSION['issue_flash']);
    return $m;
}

/** ตั้งจำนวน โดยไม่ให้เกินยอดคงเหลือ — คืนค่าจำนวนที่ตั้งได้จริง */
function idraft_set($code, $sku, $qty)
{
    $p = product_by_sku($sku);
    if ($p === null) {
        return 0;
    }
    $qty  = (int) $qty;
    $have = product_qty($p, $code);

    if ($qty > $have) {
        idraft_flash($have > 0
            ? $p['name'] . ' มีอยู่ ' . number_format($have) . ' ' . $p['unit'] . ' — ปรับจำนวนให้เท่าที่มี'
            : $p['name'] . ' หมดสต๊อกแล้ว ตัดออกไม่ได้');
        $qty = $have;
    }
    if ($qty <= 0) {
        unset($_SESSION['issue_draft'][$sku]);
        return 0;
    }
    $_SESSION['issue_draft'][$sku] = $qty;
    return $qty;
}

function idraft_add($code, $sku, $step = 1)
{
    $cur = isset($_SESSION['issue_draft'][$sku]) ? (int) $_SESSION['issue_draft'][$sku] : 0;
    return idraft_set($code, $sku, $cur + (int) $step);
}

function idraft_remove($sku)
{
    unset($_SESSION['issue_draft'][$sku]);
}

function idraft_clear()
{
    $_SESSION['issue_draft'] = array();
    unset($_SESSION['issue_meta']);
}

/** หัวใบที่จำไว้ (ตอนดึงใบเก่ามาแก้ จะได้ไม่ต้องเลือกเหตุผลใหม่) */
function idraft_meta()
{
    $m = isset($_SESSION['issue_meta']) && is_array($_SESSION['issue_meta']) ? $_SESSION['issue_meta'] : array();
    return array(
        'reason' => isset($m['reason']) ? $m['reason'] : '',
        'ref'    => isset($m['ref'])    ? $m['ref']    : '',
        'note'   => isset($m['note'])   ? $m['note']   : '',
    );
}

/** แปลงร่างเป็นรายการ พร้อมยอดก่อน/หลัง และมูลค่าต้นทุนที่ตัดออก */
function idraft_lines($code)
{
    $out = array();
    foreach (idraft_all() as $sku => $qty) {
        $p = product_by_sku($sku);
        if ($p === null) {
            continue;
        }
        $have  = product_qty($p, $code);
        $out[] = array(
            'sku'   => $sku,
            'name'  => $p['name'],
            'cat'   => $p['cat'],
            'unit'  => $p['unit'],
            'qty'   => (int) $qty,
            'have'  => $have,
            'after' => $have - (int) $qty,
            'cost'  => (int) $qty * (float) $p['cost'],
            'p'     => $p,
        );
    }
    return $out;
}

function idraft_cost($lines)
{
    $sum = 0;
    foreach ($lines as $l) {
        $sum += $l['cost'];
    }
    return $sum;
}

/* ==========================================================
   ใบที่บันทึกแล้ว
   ========================================================== */

function issue_key($code)
{
    return $code . '|' . date('Ymd');
}

function issues_today($code)
{
    $k = issue_key($code);
    return isset($_SESSION['issue'][$k]) ? $_SESSION['issue'][$k] : array();
}

function issue_next_no($code)
{
    return 'IS-' . date('ymd') . '-' . str_pad(count(issues_today($code)) + 1, 4, '0', STR_PAD_LEFT);
}

function issue_by_no($code, $no)
{
    foreach (issues_today($code) as $d) {
        if ($d['no'] === $no) {
            return $d;
        }
    }
    return null;
}

/**
 * ตรวจก่อนบันทึกว่าของยังพอ (ระหว่างทำใบ อาจมีคนขายตัวเดียวกันออกไปแล้ว)
 * คืนค่า array ของรายการที่ไม่พอ — ว่าง = ผ่าน
 */
function issue_shortage($lines)
{
    $short = array();
    foreach ($lines as $l) {
        if ($l['qty'] > $l['have']) {
            $short[] = $l['name'] . ' (เหลือ ' . number_format($l['have'])
                     . ' แต่จะตัด ' . number_format($l['qty']) . ')';
        }
    }
    return $short;
}

/** บันทึกการตัดออก: ลบสต๊อกแล้วลงประวัติ */
function issue_save($code, $user, $reason, $ref, $note, $lines)
{
    $qty = 0;
    foreach ($lines as $l) {
        $qty += (int) $l['qty'];
        stock_adj_add($code, $l['sku'], -(int) $l['qty']);      // ตัดออก = สต๊อกลด
    }

    $doc = array(
        'no'      => issue_next_no($code),
        'time'    => date('H:i'),
        'branch'  => $code,
        'by'      => $user['name'],
        'by_user' => $user['username'],
        'reason'  => $reason,
        'ref'     => $ref,
        'note'    => $note,
        'lines'   => $lines,
        'items'   => count($lines),
        'qty'     => $qty,
        'cost'    => idraft_cost($lines),
    );

    $k = issue_key($code);
    if (!isset($_SESSION['issue'][$k])) {
        $_SESSION['issue'][$k] = array();
    }
    $_SESSION['issue'][$k][] = $doc;

    $names = array();
    foreach ($lines as $l) {
        $names[] = $l['name'] . ' −' . number_format($l['qty']) . ' ' . $l['unit'];
    }
    $detail = array(
        'เหตุผล'       => issue_reason_label($reason),
        'จำนวน'       => $doc['items'] . ' รายการ · ' . number_format($qty) . ' ชิ้น',
        'มูลค่าต้นทุน'  => money2($doc['cost']) . ' บาท',
        'รายการ'      => implode(' · ', $names),
        'ผู้ทำรายการ'   => $user['name'] . ' · ' . branch_name($code),
    );
    if ($ref !== '') {
        $detail['ผู้ขอเบิก / อ้างอิง'] = $ref;
    }
    if ($note !== '') {
        $detail['หมายเหตุ'] = $note;
    }

    log_add($code, 'issue', $user,
            issue_reason_label($reason) . ' ' . $doc['no'], $detail, null, $doc['no']);

    return $doc;
}

/**
 * ยกเลิกใบตัดออก — คืนยอดกลับเข้าสต๊อก (ทำได้เสมอ เพราะเป็นการบวกกลับ)
 * $reopen = true คือยกเลิกเพื่อดึงรายการกลับมาแก้
 */
function issue_void($code, $no, $user, $reason, $reopen = false)
{
    $k = issue_key($code);
    if (!isset($_SESSION['issue'][$k])) {
        return null;
    }

    foreach ($_SESSION['issue'][$k] as $i => $d) {
        if ($d['no'] !== $no || !empty($d['void'])) {
            continue;
        }

        foreach ($d['lines'] as $l) {
            stock_adj_add($code, $l['sku'], (int) $l['qty']);       // คืนยอดที่ตัดไว้
        }

        $_SESSION['issue'][$k][$i]['void']        = true;
        $_SESSION['issue'][$k][$i]['void_at']     = date('H:i');
        $_SESSION['issue'][$k][$i]['void_by']     = $user['name'];
        $_SESSION['issue'][$k][$i]['void_user']   = $user['username'];
        $_SESSION['issue'][$k][$i]['void_reason'] = trim($reason);
        $_SESSION['issue'][$k][$i]['void_mode']   = $reopen ? 'edit' : 'void';

        $v      = $_SESSION['issue'][$k][$i];
        $detail = array(
            'ใบเดิม'          => $v['no'] . ' · ' . issue_reason_label($v['reason'])
                               . ' เมื่อ ' . $v['time'] . ' น. โดย ' . $v['by'],
            'ยอดที่คืนเข้าสต๊อก' => $v['items'] . ' รายการ · ' . number_format($v['qty']) . ' ชิ้น',
            'เหตุผล'          => $v['void_reason'] !== '' ? $v['void_reason'] : 'ไม่ได้ระบุ',
        );
        if ($reopen) {
            $detail['การทำต่อ'] = 'ดึงรายการกลับเข้าใบเพื่อบันทึกใหม่ทดแทน';
        }

        log_add($code, 'ivoid', $user,
                ($reopen ? 'ยกเลิกเพื่อแก้ไข ใบตัดออก ' : 'ยกเลิกใบตัดออก ') . $v['no'],
                $detail, null, $v['no']);

        return $v;
    }
    return null;
}

/** ดึงรายการทั้งใบกลับเข้าร่าง พร้อมหัวใบเดิม */
function idraft_from_issue($doc)
{
    $_SESSION['issue_draft'] = array();
    foreach ($doc['lines'] as $l) {
        $_SESSION['issue_draft'][$l['sku']] = (int) $l['qty'];
    }
    $_SESSION['issue_meta'] = array(
        'reason' => $doc['reason'],
        'ref'    => $doc['ref'],
        'note'   => $doc['note'],
    );
    return idraft_count();
}
