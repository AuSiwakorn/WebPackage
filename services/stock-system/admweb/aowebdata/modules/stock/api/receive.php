<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/receive.php
 * ROLE: รับสินค้าเข้าสต๊อก — ใบร่าง (session) · บันทึก / ยกเลิก / แก้ไขใบรับเข้า · อ่านใบรับเข้าทั้งช่วง
 * DEPENDS: api.php (ตัวโหลด — ห้าม include ตรง)
 * TABLES: ao_stock_receive, ao_stock_receive_item, ao_stock_move, ao_stock_balance, ao_stock_log
 * TODO:
 *   - [x] ประวัติการแก้ไขดู docs/CHANGELOG.md
 * ⚠ โหลดทุก request — มีได้แค่ define() + function (ห้าม echo / header / query ตอนโหลด)
 */

/* ---------- รับสินค้าเข้า: ทุนในใบ = ทุนล่าสุด ณ ตอนรับ (snapshot) ---------- */

/* ---------- ใบรับที่กำลังทำ (ร่างใน $_SESSION['recv_draft']) ---------- */

function rdraft_all()
{
    return isset($_SESSION['recv_draft']) && is_array($_SESSION['recv_draft'])
         ? $_SESSION['recv_draft'] : array();
}

function rdraft_count()
{
    $n = 0;
    foreach (rdraft_all() as $q) {
        $n += (int) $q;
    }
    return $n;
}

function rdraft_add($sku, $step = 1)
{
    if (product_by_sku($sku) === null) {
        return false;
    }
    $cur = isset($_SESSION['recv_draft'][$sku]) ? (int) $_SESSION['recv_draft'][$sku] : 0;
    $new = min(STOCK_LINE_MAX, $cur + (int) $step);
    if ($new <= 0) {
        unset($_SESSION['recv_draft'][$sku]);
        return true;
    }
    $_SESSION['recv_draft'][$sku] = $new;
    return true;
}

function rdraft_set($sku, $qty)
{
    if (product_by_sku($sku) === null) {
        return false;
    }
    $qty = min(STOCK_LINE_MAX, (int) $qty);
    if ($qty <= 0) {
        unset($_SESSION['recv_draft'][$sku]);
        return true;
    }
    $_SESSION['recv_draft'][$sku] = $qty;      // รับเข้าไม่มีเพดาน (นอกจากกันตัวเลขล้น) ของมาเท่าไรก็รับเท่านั้น
    return true;
}

function rdraft_remove($sku)
{
    unset($_SESSION['recv_draft'][$sku]);
}

function rdraft_clear()
{
    $_SESSION['recv_draft'] = array();
    unset($_SESSION['recv_meta'], $_SESSION['draft_edit_of']['RC']);
}

/** หัวใบรับเข้าที่จำไว้ตอนดึงใบเก่ามาแก้ (เลขที่เอกสารอ้างอิง / หมายเหตุ) — ไม่มี = ค่าว่าง
 *  TODO:
 *    - [x] 21 ต.ค. (ผลทดสอบ): เดิมแก้ไขใบรับเข้าแล้วเลขอ้างอิง / หมายเหตุหาย ต้องกรอกใหม่ (ใบตัดออก / ตรวจนับจำไว้อยู่แล้ว) */
function Stock_getReceiveDraftMeta()
{
    $m = isset($_SESSION['recv_meta']) && is_array($_SESSION['recv_meta']) ? $_SESSION['recv_meta'] : array();
    return array(
        'ref'  => isset($m['ref'])  ? (string) $m['ref']  : '',
        'note' => isset($m['note']) ? (string) $m['note'] : '',
    );
}

/** แปลงร่างเป็นรายการพร้อมยอดคงเหลือก่อน/หลัง */
function rdraft_lines($code)
{
    $out = array();
    Stock_prefetchProducts(array_keys(rdraft_all()));    // ดึงสินค้าในใบคิวรีเดียว (ช่วง 5)
    foreach (rdraft_all() as $sku => $qty) {
        $p = product_by_sku($sku);
        if ($p === null || !Stock_isProductInBranch($p, $code)) {     // สินค้าของสาขาอื่นรับเข้าไม่ได้ (22 ต.ค.)
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
            'after' => $have + (int) $qty,
            'p'     => $p,
        );
    }
    return $out;
}

/** บันทึกการรับเข้าในทรานแซกชันเดียว · คืนใบ หรือ array('error') */
function receive_save($code, $user, $ref, $note, $lines)
{
    $bid = branch_id_of($code);
    if ($bid <= 0) {
        return array('error' => 'ไม่พบสาขาที่ทำรายการ');
    }
    $lines = array_values(array_filter($lines, function ($l) { return (int) $l['qty'] > 0; }));
    if (!$lines) {
        return array('error' => 'ยังไม่ได้เลือกสินค้าเข้าใบสักรายการ');
    }

    $no = sdb_tx(function () use ($code, $bid, $user, $ref, $note, $lines) {
        $uid  = stock_uid($user);
        $now  = date('Y-m-d H:i:s');
        $pids = array();
        $qty  = 0;
        $cost = 0;
        foreach ($lines as $l) {
            $pids[] = $l['p']['id'];
            $qty   += (int) $l['qty'];
            $cost  += (int) $l['qty'] * (float) $l['p']['cost'];
        }
        $q  = stock_lock_qty($bid, $pids);
        $no = stock_doc_next_no($bid, 'RC');
        $id = sdb_insert('receive', array(
            'doc_no'     => $no,
            'branch_id'  => $bid,
            'doc_date'   => date('Y-m-d'),
            'ref_no'     => stock_cut($ref, 60),
            'note'       => stock_cut($note, 255),
            'item_count' => count($lines),
            'total_qty'  => $qty,
            'total_cost' => round($cost, 2),
            'edit_of_id' => stock_draft_edit_of('RC', $bid),
            'created_by' => $uid,
            'add_date'   => $now,
        ));
        foreach ($lines as $l) {
            $p   = $l['p'];
            $pid = $p['id'];
            sdb_insert('receive_item', array(
                'receive_id'   => $id,
                'product_id'   => $pid,
                'sku'          => $p['sku'],
                'product_name' => $p['name'],
                'qty'          => (int) $l['qty'],
                'unit_cost'    => (float) $p['cost'],
                'qty_before'   => $q[$pid],
            ));
            $q[$pid] += (int) $l['qty'];
            stock_move_add($bid, $pid, 'receive', (int) $l['qty'], $q[$pid], (float) $p['cost'], 'receive', $id, $no,
                           $ref !== '' ? 'อ้างอิง ' . $ref : stock_cut($note, 120), $uid, $now);   // ไม่มีเลขเอกสาร = ใช้หมายเหตุ
        }

        $names = array();
        foreach ($lines as $l) {
            $names[] = $l['name'] . ' +' . number_format($l['qty']) . ' ' . $l['unit'];
        }
        $detail = $ref !== '' ? array('เอกสารอ้างอิง' => $ref) : array();
        $detail += array(
            'จำนวน'        => count($lines) . ' รายการ · ' . number_format($qty) . ' ชิ้น',
            'รายการ'       => implode(' · ', $names),
            'ผู้รับเข้า'     => $user['name'] . ' · ' . branch_name($code),
        );
        if ($note !== '') {
            $detail['หมายเหตุ'] = $note;
        }
        log_add($code, 'receive', $user, 'รับสินค้าเข้า ' . $no, $detail, $qty, $no);
        return $no;
    });

    unset($_SESSION['draft_edit_of']['RC']);
    product_db_reset();
    return receive_by_no($code, $no);
}

/** ยกเลิกใบรับเข้าของวันนี้ (ถอนยอดออก) · คืนใบ / array('error' => 'sold') / null */
function receive_void($code, $no, $user, $reason, $reopen = false)
{
    $d = receive_by_no($code, $no);
    if ($d === null || $d['void']) {
        return null;
    }
    $reason = trim($reason);
    $detail = array(
        'ใบเดิม'      => $d['no'] . ' · รับเข้าเมื่อ ' . $d['time'] . ' น. โดย ' . $d['by'],
        'เอกสารอ้างอิง' => $d['ref'] !== '' ? $d['ref'] : 'ไม่มี · หมายเหตุ: ' . $d['note'],
        'ยอดที่ถอนออก'  => $d['items'] . ' รายการ · ' . number_format($d['qty']) . ' ชิ้น',
        'เหตุผล'      => $reason !== '' ? $reason : 'ไม่ได้ระบุ',
    );
    if ($reopen) {
        $detail['การทำต่อ'] = 'ดึงรายการกลับเข้าใบเพื่อรับเข้าใหม่ทดแทน';
    }
    $r = stock_doc_void_core($d, $user, $reason, $reopen, array(
        ($reopen ? 'ยกเลิกเพื่อแก้ไข ใบรับเข้า ' : 'ยกเลิกใบรับเข้า ') . $d['no'], $detail, $d['qty'],
    ));
    if ($r === null) {
        return null;
    }
    if (is_array($r)) {
        return array('error' => 'sold', 'items' => $r['short']);
    }
    notify_on_void($code, 'RC', $d, $user, $reason, $reopen);   // Telegram
    return receive_by_no($code, $no);
}

/** ดึงรายการทั้งใบกลับเข้าร่าง เพื่อแก้แล้วรับเข้าใหม่ (ใบใหม่จะชี้ edit_of_id กลับมาที่ใบนี้) */
function rdraft_from_receive($doc)
{
    $_SESSION['recv_draft'] = array();
    foreach ($doc['lines'] as $l) {
        $_SESSION['recv_draft'][$l['sku']] = (int) $l['qty'];
    }
    $_SESSION['recv_meta'] = array('ref' => $doc['ref'], 'note' => $doc['note']);   // หัวใบเดิม (Stock_getReceiveDraftMeta · 21 ต.ค.)
    $_SESSION['draft_edit_of']['RC'] = (int) $doc['id'];
    return rdraft_count();
}

/** ใบรับเข้าของวันนี้จากเลขที่ — แก้ / ยกเลิกจากหน้ารับเข้าได้เฉพาะใบของวันนี้ */
function receive_by_no($code, $no)
{
    return stock_doc_by_no($code, $no, 'today', 'RC');
}

/** ใบรับเข้าของสาขาใน $codes ช่วง $from–$to (รวมที่ยกเลิก) — มี date / value / void ครบทุกใบ */
function receive_docs_range($codes, $from, $to)
{
    return stock_docs_range($codes, $from, $to, array('RC'));
}

/** หาใบรับเข้าจากเลขที่ RC-ปปดดวว-NNNN (วันไหนก็ได้) */
function receive_doc_find($code, $no)
{
    return stock_doc_by_no($code, $no, '', 'RC');
}
