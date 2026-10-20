<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/issue.php
 * ROLE: เบิก / ตัดออกจากสต๊อก — เหตุผล · ใบร่าง (session) · บันทึก / ยกเลิก / แก้ไขใบตัดออก · อ่านใบตัดออกทั้งช่วง
 * DEPENDS: api.php (ตัวโหลด — ห้าม include ตรง)
 * TABLES: ao_stock_issue, ao_stock_issue_item, ao_stock_move, ao_stock_balance, ao_stock_log
 * TODO:
 *   - [x] ประวัติการแก้ไขดู docs/CHANGELOG.md
 * ⚠ โหลดทุก request — มีได้แค่ define() + function (ห้าม echo / header / query ตอนโหลด)
 */

/* ---------- เบิก / ตัดออก (ของที่ออกโดยไม่ได้ขาย): ร่างใน session (issue_draft / issue_meta) → บันทึกทั้งใบ ---------- */

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

/* ---------- ใบที่กำลังทำ (ร่าง) — จำนวนไม่เกินยอดคงเหลือของสาขา ---------- */

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
    $qty  = min(STOCK_LINE_MAX, (int) $qty);
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
    unset($_SESSION['issue_meta'], $_SESSION['draft_edit_of']['IS']);
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
    Stock_prefetchProducts(array_keys(idraft_all()));    // ดึงสินค้าในใบคิวรีเดียว (ช่วง 5)
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

/* ---------- ใบที่บันทึกแล้ว ---------- */

/** ใบเบิก / ตัดออกของสาขาวันนี้ (รวมที่ยกเลิก) เรียงตามเวลา */
function issues_today($code)
{
    return stock_docs_range(array($code), time(), time(), array('IS'));
}

/** ใบเบิก / ตัดออกของวันนี้จากเลขที่ — แก้ / ยกเลิกจากหน้าเบิกได้เฉพาะใบของวันนี้ */
function issue_by_no($code, $no)
{
    return stock_doc_by_no($code, $no, 'today', 'IS');
}

/** รายการที่ของไม่พอแล้ว (ว่าง = ผ่าน) — issue_save เช็กซ้ำหลังล็อกยอด */
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

/** บันทึกการตัดออกในทรานแซกชันเดียว · คืนใบ หรือ array('error') */
function issue_save($code, $user, $reason, $ref, $note, $lines)
{
    $bid = branch_id_of($code);
    if ($bid <= 0) {
        return array('error' => 'ไม่พบสาขาที่ทำรายการ');
    }
    $lines = array_values(array_filter($lines, function ($l) { return (int) $l['qty'] > 0; }));
    if (!$lines) {
        return array('error' => 'ยังไม่ได้เลือกสินค้าเข้าใบสักรายการ');
    }

    $no = sdb_tx(function () use ($code, $bid, $user, $reason, $ref, $note, $lines) {
        $pids = array();
        foreach ($lines as $l) {
            $pids[] = $l['p']['id'];
        }
        $q     = stock_lock_qty($bid, $pids);
        $short = array();
        foreach ($lines as $l) {
            $have = $q[$l['p']['id']];
            if ((int) $l['qty'] > $have) {
                $short[] = $l['name'] . ' (เหลือ ' . number_format($have) . ' แต่จะตัด ' . number_format($l['qty']) . ')';
            }
        }
        if ($short) {
            return array('short' => $short);
        }

        $uid  = stock_uid($user);
        $now  = date('Y-m-d H:i:s');
        $qty  = 0;
        $cost = 0;
        foreach ($lines as $l) {
            $qty  += (int) $l['qty'];
            $cost += (int) $l['qty'] * (float) $l['p']['cost'];
        }
        $no = stock_doc_next_no($bid, 'IS');
        $id = sdb_insert('issue', array(
            'doc_no'     => $no,
            'branch_id'  => $bid,
            'doc_date'   => date('Y-m-d'),
            'reason'     => substr((string) $reason, 0, 20),
            'ref_no'     => stock_cut($ref, 60),
            'note'       => stock_cut($note, 255),
            'item_count' => count($lines),
            'total_qty'  => $qty,
            'total_cost' => round($cost, 2),
            'edit_of_id' => stock_draft_edit_of('IS', $bid),
            'created_by' => $uid,
            'add_date'   => $now,
        ));
        foreach ($lines as $l) {
            $p   = $l['p'];
            $pid = $p['id'];
            sdb_insert('issue_item', array(
                'issue_id'     => $id,
                'product_id'   => $pid,
                'sku'          => $p['sku'],
                'product_name' => $p['name'],
                'qty'          => (int) $l['qty'],
                'unit_cost'    => (float) $p['cost'],
                'qty_before'   => $q[$pid],
            ));
            $q[$pid] -= (int) $l['qty'];
            stock_move_add($bid, $pid, 'issue', -(int) $l['qty'], $q[$pid], (float) $p['cost'], 'issue', $id, $no,
                           issue_reason_label($reason) . ($note !== '' ? ' · ' . $note : ''), $uid, $now);
        }

        $names = array();
        foreach ($lines as $l) {
            $names[] = $l['name'] . ' −' . number_format($l['qty']) . ' ' . $l['unit'];
        }
        $detail = array(
            'เหตุผล'       => issue_reason_label($reason),
            'จำนวน'       => count($lines) . ' รายการ · ' . number_format($qty) . ' ชิ้น',
            'มูลค่าต้นทุน'  => money2($cost) . ' บาท',
            'รายการ'      => implode(' · ', $names),
            'ผู้ทำรายการ'   => $user['name'] . ' · ' . branch_name($code),
        );
        if ($ref !== '') {
            $detail['ผู้ขอเบิก / อ้างอิง'] = $ref;
        }
        if ($note !== '') {
            $detail['หมายเหตุ'] = $note;
        }
        log_add($code, 'issue', $user, issue_reason_label($reason) . ' ' . $no, $detail, null, $no);
        return $no;
    });

    if (is_array($no)) {
        return array('error' => 'ของบางรายการไม่พอแล้ว (อาจถูกขายไประหว่างทำใบ) — ' . implode(' · ', $no['short']));
    }
    unset($_SESSION['draft_edit_of']['IS']);
    product_db_reset();
    $doc = issue_by_no($code, $no);
    if ($doc !== null) {
        notify_on_issue($code, $doc);            // Telegram: สูญหาย / ชำรุด
    }
    return $doc;
}

/** ยกเลิกใบตัดออกของวันนี้ (บวกกลับ) · $reopen = ดึงกลับมาแก้ · คืนใบ / null */
function issue_void($code, $no, $user, $reason, $reopen = false)
{
    $d = issue_by_no($code, $no);
    if ($d === null || $d['void']) {
        return null;
    }
    $reason = trim($reason);
    $detail = array(
        'ใบเดิม'          => $d['no'] . ' · ' . issue_reason_label($d['reason'])
                           . ' เมื่อ ' . $d['time'] . ' น. โดย ' . $d['by'],
        'ยอดที่คืนเข้าสต๊อก' => $d['items'] . ' รายการ · ' . number_format($d['qty']) . ' ชิ้น',
        'เหตุผล'          => $reason !== '' ? $reason : 'ไม่ได้ระบุ',
    );
    if ($reopen) {
        $detail['การทำต่อ'] = 'ดึงรายการกลับเข้าใบเพื่อบันทึกใหม่ทดแทน';
    }
    $r = stock_doc_void_core($d, $user, $reason, $reopen, array(
        ($reopen ? 'ยกเลิกเพื่อแก้ไข ใบตัดออก ' : 'ยกเลิกใบตัดออก ') . $d['no'], $detail, null,
    ));
    if ($r !== true) {
        return null;
    }
    notify_on_void($code, 'IS', $d, $user, $reason, $reopen);   // Telegram
    return issue_by_no($code, $no);
}

/** ดึงรายการทั้งใบกลับเข้าร่าง พร้อมหัวใบเดิม (ใบใหม่จะชี้ edit_of_id กลับมาที่ใบนี้) */
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
    $_SESSION['draft_edit_of']['IS'] = (int) $doc['id'];
    return idraft_count();
}

/* ---------- รายการรายสินค้าของใบเบิก / รับเข้า (หน้าผู้ดูแล) — คิวรีเดียวทั้งช่วง · มูลค่าจากทุน snapshot ---------- */

/** แถวรายสินค้าในใบเบิกของสาขาใน $codes ช่วง $from–$to */
function issue_rows_range($codes, $from, $to)
{
    $rows = array();
    foreach (stock_docs_range($codes, $from, $to, array('IS')) as $d) {
        foreach ($d['lines'] as $n => $l) {
            $rows[] = array(
                'date'   => $d['date'],
                'time'   => $d['time'],
                'ts'     => $d['ts'],
                'seq'    => $n,
                'branch' => $d['branch'],
                'no'     => $d['no'],
                'sku'    => $l['sku'],
                'name'   => $l['name'],
                'unit'   => $l['unit'],
                'cat'    => $l['cat'],
                'qty'    => $l['qty'],
                'cost'   => $l['cost'],
                'price'  => $l['price'],
                'value'  => $l['value'],
                'reason' => $d['reason'],
                'note'   => $d['note'],
                'ref'    => $d['ref'],
                'by'     => $d['by'],
                'void'   => $d['void'],
                'void_by'     => $d['void_by'],
                'void_reason' => $d['void_reason'],
            );
        }
    }
    return $rows;
}
