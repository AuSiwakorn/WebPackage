<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/ledger.php
 * ROLE: สมุดสต๊อก (stock_move / stock_balance) · เลขที่เอกสาร · อ่าน / ยกเลิกเอกสารคลัง · ประวัติเคลื่อนไหวรายสินค้า · แก้ / ยกเลิกเอกสารย้อนหลัง
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_move, ao_stock_balance, ao_stock_doc_seq, ao_stock_receive / issue / count (+ _item), ao_stock_sale (ชนิด SA), ao_stock_log
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *
 * ⚠ ไฟล์นี้ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb — มีได้แค่ define() และประกาศ function (ห้าม echo / header / query ตอนโหลด)
 */

/* ---------- ประวัติเคลื่อนไหวรายสินค้า: ย้อนหลังกี่วัน (+ วันนี้) — move_rows_multi() ---------- */
define('MOVE_LOOKBACK_DAYS', 13);

define('STOCK_LINE_MAX', 1000000);       // จำนวนสูงสุดต่อรายการในใบ (กันตัวเลขล้นคอลัมน์ int)

/* ==========================================================
   หลักการ (ดู database.php)
   - ยอดคงเหลือ = ผลรวมของ ao_stock_move · ao_stock_balance เป็น cache ที่อัปเดตในทรานแซกชันเดียวกัน
   - เอกสารคลัง 3 ชนิด: RC ใบรับเข้า (ao_stock_receive) · IS ใบเบิก / ตัดออก (ao_stock_issue) · AD ใบตรวจนับ (ao_stock_count)
   - บันทึก = ล็อกยอดของสินค้าในใบ (FOR UPDATE เรียงตาม product_id กัน deadlock) → ออกเลขที่ → หัวใบ + รายการ
              → stock_move ทีละรายการ + ยอดใหม่ใน stock_balance → stock_log — ทั้งหมดใน sdb_tx เดียว
   - ยกเลิก = status 'void' + stock_move กลับรายการ (ไม่ลบ) · แก้ไข = ยกเลิกแบบ void_mode 'edit' แล้วใบใหม่ใส่ edit_of_id ชี้ใบเดิม
   - บิลขาย (SA) / ใบรับคืน (RT) ใช้ ledger เดียวกัน (stock_move ชนิด sale / sale_void / return) — หมวด: ขายสินค้า / รับคืนสินค้า
   - ยอดติดลบไม่ได้ทั้งเอกสารคลังและการขาย (ไม่ใช้ allow_negative ของสาขา — ตะกร้าจำกัดไม่ให้เกินยอดอยู่แล้ว)
   - เวลาในเอกสารใช้นาฬิกาของ PHP (Asia/Bangkok จาก fix.<โดเมน>.php) ไม่ใช้ DEFAULT CURRENT_TIMESTAMP ของ MySQL
   - ไม่มียอดสต๊อกใน session แล้ว (ช่วงที่ 7) — ทุกเครื่องเห็นยอดเดียวกันจาก stock_balance
   ========================================================== */

/** ชนิดเอกสาร: ตาราง · คอลัมน์ id · ชนิดใน stock_move · ชนิดใน stock_log (บันทึก / ยกเลิก)
    RC IS AD ใช้ stock_docs_query / stock_doc_void_core · SA (บิลขาย) ใช้เฉพาะ stock_draft_edit_of — บิลมีชุด function ของตัวเอง
    TODO:
      - [x] RC IS AD
      - [x] SA บิลขาย (ช่วงที่ 7 — ใบใหม่ที่ทำแทนบิล "ยกเลิกเพื่อแก้ไข") */
function stock_doc_kinds()
{
    return array(
        'RC' => array('tb' => 'receive', 'id' => 'receive_id', 'item' => 'receive_item', 'move' => 'receive', 'log' => 'receive', 'vlog' => 'rvoid'),
        'IS' => array('tb' => 'issue',   'id' => 'issue_id',   'item' => 'issue_item',   'move' => 'issue',   'log' => 'issue',   'vlog' => 'ivoid'),
        'AD' => array('tb' => 'count',   'id' => 'count_id',   'item' => 'count_item',   'move' => 'adjust',  'log' => 'adjust',  'vlog' => 'avoid'),
        'SA' => array('tb' => 'sale',    'id' => 'sale_id',    'item' => 'sale_item',    'move' => 'sale',    'log' => 'sale',    'vlog' => 'void'),
    );
}

/** staff_id ของผู้ใช้ที่ล็อกอิน (session เก่าที่ยังไม่มี id → หาจาก username) · ไม่พบ = 0
    TODO:
      - [x] ใช้กับ created_by / void_by ทุกตาราง */
function stock_uid($user)
{
    if (!empty($user['id'])) {
        return (int) $user['id'];
    }
    $r = isset($user['username']) ? staff_auth_row($user['username']) : null;
    return ($r !== null) ? (int) $r['staff_id'] : 0;
}

/** ตัดข้อความให้ไม่เกินความยาวคอลัมน์ (นับเป็นตัวอักษร ไม่ใช่ byte) */
function stock_cut($s, $len)
{
    return mb_substr(trim((string) $s), 0, $len, 'UTF-8');
}

/** เลขรันถัดไปของชุด (สาขา + ชุด + งวด) — ต้องเรียกใน sdb_tx
    INSERT ... ON DUPLICATE KEY UPDATE ล็อกแถวตัวนับไว้จน COMMIT → เครื่องที่บันทึกพร้อมกันต้องรอ เลขไม่ซ้ำ ไม่ข้าม
    TODO:
      - [x] ใช้ร่วมกันทั้งเอกสาร (RC IS AD RT · งวด ปปดดวว) และบิล (vat / novat · งวด YYYYMM) */
function stock_seq_next($branchId, $series, $period)
{
    $tb = sdb_tb('doc_seq');
    sdb_q('INSERT INTO ' . $tb . ' (branch_id, series, period, last_no) VALUES (?, ?, ?, 1)'
        . ' ON DUPLICATE KEY UPDATE last_no = last_no + 1', array($branchId, $series, $period));
    return (int) sdb_val('SELECT last_no FROM ' . $tb . ' WHERE branch_id = ? AND series = ? AND period = ?',
                         array($branchId, $series, $period));
}

/** ออกเลขที่เอกสาร {ชุด}-{ปปดดวว}-{NNNN} รันต่อสาขาต่อวัน — ต้องเรียกใน sdb_tx
    TODO:
      - [x] RC IS AD
      - [x] RT ใบรับคืน (ช่วงที่ 7) */
function stock_doc_next_no($branchId, $series)
{
    $period = date('ymd');
    return $series . '-' . $period . '-' . str_pad((string) stock_seq_next($branchId, $series, $period), 4, '0', STR_PAD_LEFT);
}

/** ล็อกยอดคงเหลือของสินค้าในสาขา (FOR UPDATE) — ต้องเรียกใน sdb_tx · คืน array( product_id => ยอดในฐานข้อมูล )
    สินค้าที่ยังไม่มีแถวใน stock_balance สร้างแถว 0 ให้ก่อน · ล็อกเรียงตาม product_id เสมอ กัน deadlock
    TODO:
      - [x] ใช้กับบันทึก / ยกเลิกเอกสารคลัง
      - [x] บิลขาย / ยกเลิกบิล / รับคืน (ช่วงที่ 7) */
function stock_lock_qty($branchId, $productIds)
{
    $ids = array_values(array_unique(array_map('intval', $productIds)));
    sort($ids);
    if (!$ids) {
        return array();
    }
    $tb     = sdb_tb('balance');
    $vals   = array();
    $params = array();
    foreach ($ids as $id) {
        $vals[]   = '(?, ?, 0)';
        $params[] = $branchId;
        $params[] = $id;
    }
    sdb_q('INSERT INTO ' . $tb . ' (branch_id, product_id, qty) VALUES ' . implode(', ', $vals)
        . ' ON DUPLICATE KEY UPDATE qty = qty', $params);

    $out = array();
    $in  = implode(', ', array_fill(0, count($ids), '?'));
    $sql = 'SELECT product_id, qty FROM ' . $tb . ' WHERE branch_id = ? AND product_id IN (' . $in . ') ORDER BY product_id FOR UPDATE';
    foreach (sdb_rows($sql, array_merge(array($branchId), $ids)) as $r) {
        $out[(int) $r['product_id']] = (int) $r['qty'];
    }
    return $out;
}

/** เขียนความเคลื่อนไหว 1 แถว + ตั้งยอดใหม่ใน stock_balance — ต้องเรียกใน sdb_tx หลัง stock_lock_qty() */
function stock_move_add($branchId, $productId, $type, $qty, $qtyAfter, $unitCost, $refType, $refId, $docNo, $note, $uid, $at)
{
    sdb_insert('move', array(
        'branch_id'  => $branchId,
        'product_id' => $productId,
        'type'       => $type,
        'qty'        => $qty,
        'qty_after'  => $qtyAfter,
        'unit_cost'  => $unitCost,
        'ref_type'   => $refType,
        'ref_id'     => $refId,
        'doc_no'     => $docNo,
        'note'       => stock_cut($note, 255),
        'created_by' => $uid,
        'add_date'   => $at,
    ));
    sdb_q('UPDATE ' . sdb_tb('balance') . ' SET qty = ? WHERE branch_id = ? AND product_id = ?',
          array($qtyAfter, $branchId, $productId));
}

/** ใบที่ "ยกเลิกเพื่อแก้ไข" ซึ่งร่างตอนนี้ทำแทน — ใช้เป็น edit_of_id ของใบใหม่ (ต้องเรียกใน sdb_tx)
    id เก็บใน $_SESSION['draft_edit_of'][ชนิด] ตอนดึงรายการกลับเข้าร่าง · ใบเดิมมีใบแทนแล้วจะไม่ใช้ซ้ำ */
function stock_draft_edit_of($kind, $branchId)
{
    $id = isset($_SESSION['draft_edit_of'][$kind]) ? (int) $_SESSION['draft_edit_of'][$kind] : 0;
    if ($id <= 0) {
        return null;
    }
    $k   = stock_doc_kinds();
    $k   = $k[$kind];
    $tb  = sdb_tb($k['tb']);
    $sql = 'SELECT 1 FROM ' . $tb . ' d WHERE d.' . $k['id'] . ' = ? AND d.branch_id = ? AND d.status = \'void\' AND d.void_mode = \'edit\''
         . ' AND NOT EXISTS (SELECT 1 FROM ' . $tb . ' x WHERE x.edit_of_id = d.' . $k['id'] . ')';
    return (sdb_val($sql, array($id, $branchId)) !== null) ? $id : null;
}

/** อ่านเอกสารคลังชนิดหนึ่ง — $where ต่อท้าย WHERE (alias d = หัวใบ · b = สาขา) · คืน array ของ stock_doc_shape() เรียงเก่า → ใหม่
    TODO:
      - [x] รายการในใบ 1 คิวรีต่อชนิด (ไม่วนทีละใบ) */
function stock_docs_query($kind, $where, $params)
{
    $k   = stock_doc_kinds();
    $k   = $k[$kind];
    $sql = 'SELECT d.*, d.' . $k['id'] . ' AS id, b.code, s.username AS by_user, s.name AS by_name,'
         . ' v.username AS void_user, v.name AS void_name'
         . ' FROM ' . sdb_tb($k['tb']) . ' d JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = d.branch_id'
         . ' LEFT JOIN ' . sdb_tb('staff') . ' s ON s.staff_id = d.created_by'
         . ' LEFT JOIN ' . sdb_tb('staff') . ' v ON v.staff_id = d.void_by'
         . ' WHERE ' . $where . ' ORDER BY d.add_date, d.' . $k['id'];
    $heads = sdb_rows($sql, $params);
    if (!$heads) {
        return array();
    }
    $ids = array();
    foreach ($heads as $h) {
        $ids[] = (int) $h['id'];
    }
    $items = array();
    $sql   = 'SELECT i.*, p.unit, p.sell_price, c.name AS cat_name FROM ' . sdb_tb($k['item']) . ' i'
           . ' LEFT JOIN ' . sdb_tb('product') . ' p ON p.product_id = i.product_id'
           . ' LEFT JOIN ' . sdb_tb('category') . ' c ON c.cate_id = p.cate_id'
           . ' WHERE i.' . $k['id'] . ' IN (' . implode(', ', $ids) . ') ORDER BY i.item_id';    // $ids เป็น int ทั้งหมด
    foreach (sdb_rows($sql) as $it) {
        $items[(int) $it[$k['id']]][] = $it;
    }
    $out = array();
    foreach ($heads as $h) {
        $out[] = stock_doc_shape($kind, $h, isset($items[(int) $h['id']]) ? $items[(int) $h['id']] : array());
    }
    return $out;
}

/** แถวในฐานข้อมูล → รูปเอกสารที่หน้าเว็บใช้ (no time by lines items qty value void_* ...) เหมือนตอนเก็บใน session
    มูลค่าคิดจากราคาทุนที่ snapshot ไว้ในรายการของใบ (ไม่ใช่ทุนปัจจุบัน) */
function stock_doc_shape($kind, $h, $items)
{
    $ts  = strtotime($h['add_date']);
    $vts = !empty($h['void_date']) ? strtotime($h['void_date']) : 0;
    $doc = array(
        'kind'        => $kind,
        'id'          => (int) $h['id'],
        'no'          => $h['doc_no'],
        'date'        => date('Ymd', strtotime($h['doc_date'])),
        'time'        => date('H:i', $ts),
        'ts'          => $ts,
        'branch'      => $h['code'],
        'by'          => ($h['by_name'] !== null) ? $h['by_name'] : '-',
        'by_user'     => ($h['by_user'] !== null) ? $h['by_user'] : '',
        'ref'         => isset($h['ref_no']) ? $h['ref_no'] : '',
        'note'        => $h['note'],
        'reason'      => isset($h['reason']) ? $h['reason'] : '',
        'void'        => ($h['status'] === 'void'),
        'void_mode'   => (string) $h['void_mode'],
        'void_reason' => $h['void_reason'],
        'void_by'     => ($h['void_name'] !== null) ? $h['void_name'] : '',
        'void_user'   => ($h['void_user'] !== null) ? $h['void_user'] : '',
        'void_at'     => $vts ? date(date('Ymd', $vts) === date('Ymd', $ts) ? 'H:i' : 'j/n H:i', $vts) : '',
        'edit_of_id'  => (int) $h['edit_of_id'],
        'lines'       => array(),
        'items'       => (int) $h['item_count'],
        'qty'         => 0,
        'value'       => 0.0,
    );
    foreach ($items as $it) {
        $cost = (float) $it['unit_cost'];
        $l    = array(
            'pid'   => (int) $it['product_id'],
            'sku'   => $it['sku'],
            'name'  => $it['product_name'],
            'unit'  => ($it['unit'] !== null) ? $it['unit'] : 'ชิ้น',
            'cat'   => (string) $it['cat_name'],
            'cost'  => $cost,
            'price' => (float) $it['sell_price'],
        );
        if ($kind === 'AD') {
            $l['have']    = (int) $it['qty_system'];
            $l['counted'] = (int) $it['qty_counted'];
            $l['diff']    = (int) $it['diff'];
            $l['qty']     = abs($l['diff']);
            $l['value']   = $l['diff'] * $cost;
        } else {
            $l['qty']   = (int) $it['qty'];
            $l['have']  = (int) $it['qty_before'];
            $l['after'] = ($kind === 'RC') ? $l['have'] + $l['qty'] : $l['have'] - $l['qty'];
            $l['value'] = $l['qty'] * $cost;
        }
        $doc['lines'][] = $l;
        $doc['qty']    += $l['qty'];
        $doc['value']  += $l['value'];
    }
    if ($kind === 'IS') {
        $doc['cost'] = $doc['value'];
    }
    if ($kind === 'AD') {
        $doc['sum'] = adj_sum($doc['lines']);
    }
    return $doc;
}

/** เอกสารคลังของสาขาใน $codes ช่วงวันที่ $from–$to (timestamp) รวมใบที่ยกเลิก — เรียงตามเวลา
    TODO:
      - [x] คิวรีเดียวต่อชนิดทั้งช่วง (หน้าผู้ดูแลเดิมวนทีละวันทีละสาขา) */
function stock_docs_range($codes, $from, $to, $kinds = array('RC', 'IS', 'AD'))
{
    $codes = array_values($codes);
    if (!$codes) {
        return array();
    }
    $in     = implode(', ', array_fill(0, count($codes), '?'));
    $params = array_merge($codes, array(date('Y-m-d', $from), date('Y-m-d', $to)));
    $out    = array();
    foreach ($kinds as $kind) {
        $out = array_merge($out, stock_docs_query($kind, 'b.code IN (' . $in . ') AND d.doc_date BETWEEN ? AND ?', $params));
    }
    usort($out, function ($a, $b) {
        return ($a['ts'] === $b['ts']) ? strcmp($a['no'], $b['no']) : (($a['ts'] < $b['ts']) ? -1 : 1);
    });
    return $out;
}

/** เอกสารคลังใบเดียวจากเลขที่ (ชนิดดูจากตัวหน้า RC / IS / AD)
    $day: 'today' = เฉพาะใบของวันนี้ · 'past' = เฉพาะใบของวันก่อน · '' = วันไหนก็ได้ · $kind = บังคับชนิด (ว่าง = ตามเลขที่) */
function stock_doc_by_no($code, $no, $day = '', $kind = '')
{
    if (!preg_match('/^(RC|IS|AD)-\d{6}-\d{4,}$/', (string) $no, $m) || ($kind !== '' && $m[1] !== $kind)) {
        return null;
    }
    $where  = 'b.code = ? AND d.doc_no = ?';
    $params = array((string) $code, $no);
    if ($day === 'today') {
        $where   .= ' AND d.doc_date = ?';
        $params[] = date('Y-m-d');
    } elseif ($day === 'past') {
        $where   .= ' AND d.doc_date < ?';
        $params[] = date('Y-m-d');
    }
    $r = stock_docs_query($m[1], $where, $params);
    return $r ? $r[0] : null;
}

/** ยกเลิกเอกสารคลัง: status void + stock_move กลับรายการ + ประวัติ — ทั้งหมดใน transaction เดียว
    $log = array(หัวข้อ, รายละเอียด, จำนวน) ของประวัติการทำรายการ
    คืน true | null (ใบถูกยกเลิกไปก่อนแล้ว) | array('short' => รายการที่ของไม่พอให้ถอย)
    TODO:
      - [x] ใบรับเข้า / ใบตรวจนับที่ปรับเพิ่ม: ถอยแล้วยอดต้องไม่ติดลบ (ของถูกขาย / เบิกไปแล้ว) */
function stock_doc_void_core($doc, $user, $reason, $redo, $log)
{
    $k   = stock_doc_kinds();
    $k   = $k[$doc['kind']];
    $res = sdb_tx(function () use ($doc, $user, $reason, $redo, $log, $k) {
        $st = sdb_val('SELECT status FROM ' . sdb_tb($k['tb']) . ' WHERE ' . $k['id'] . ' = ? FOR UPDATE', array($doc['id']));
        if ($st !== 'posted') {
            return null;
        }
        $bid  = branch_id_of($doc['branch']);
        $back = array();                                     // array(รายการ, จำนวนที่ถอย +/-)
        $need = array();                                     // product_id => รวมที่ถอย
        foreach ($doc['lines'] as $l) {
            $d = ($doc['kind'] === 'RC') ? -$l['qty'] : (($doc['kind'] === 'IS') ? $l['qty'] : -$l['diff']);
            if ($d !== 0) {
                $back[] = array($l, $d);
                $need[$l['pid']] = (isset($need[$l['pid']]) ? $need[$l['pid']] : 0) + $d;
            }
        }
        $q     = stock_lock_qty($bid, array_keys($need));
        $short = array();
        foreach ($back as $b) {
            $pid  = $b[0]['pid'];
            $have = $q[$pid];
            if ($need[$pid] < 0 && $have + $need[$pid] < 0 && !isset($short[$pid])) {
                $short[$pid] = $b[0]['name'] . ' (เหลือ ' . number_format($have) . ' แต่ต้องถอน ' . number_format(-$need[$pid]) . ')';
            }
        }
        if ($short) {
            return array('short' => array_values($short));
        }

        $uid = stock_uid($user);
        $now = date('Y-m-d H:i:s');
        sdb_update($k['tb'], array(
            'status'      => 'void',
            'void_mode'   => $redo ? 'edit' : 'void',
            'void_reason' => stock_cut($reason, 255),
            'void_by'     => $uid,
            'void_date'   => $now,
        ), array($k['id'] => $doc['id']));
        foreach ($back as $b) {
            $pid      = $b[0]['pid'];
            $q[$pid] += $b[1];
            stock_move_add($bid, $pid, $k['move'] . '_void', $b[1], $q[$pid], $b[0]['cost'], $k['tb'], $doc['id'], $doc['no'],
                           $reason, $uid, $now);
        }
        log_add($doc['branch'], $k['vlog'], $user, $log[0], $log[1], $log[2], $doc['no']);
        return true;
    });
    product_db_reset();
    return $res;
}

/* ==========================================================
   ประวัติเคลื่อนไหวรายสินค้า
   ----------------------------------------------------------
   รวมทุกอย่างที่ทำให้ยอดของสินค้าหนึ่งตัวขยับ: ขาย รับเข้า ตัดออก ตรวจนับ
   และการยกเลิกเอกสารเหล่านั้น แล้วเรียงตามเวลา พร้อมยอดคงเหลือหลังแต่ละรายการ

   ที่มา: ao_stock_move ย้อนหลัง MOVE_LOOKBACK_DAYS วัน + วันนี้ (ขาย รับคืน รับเข้า ตัดออก ตรวจนับ และการยกเลิก)
   ยอดคงเหลือคำนวณย้อนจาก "ยอดตอนนี้" เสมอ (เท่ากับ qty_after ของแต่ละแถว)
   ========================================================== */

function move_types()
{
    return array(
        'sale'    => array('label' => 'ขาย',          'tone' => 'sale', 'icon' => 'i-cart'),
        'void'    => array('label' => 'ยกเลิกบิล',     'tone' => 'in',   'icon' => 'i-ban'),
        'receive' => array('label' => 'รับเข้า',       'tone' => 'in',   'icon' => 'i-in'),
        'rvoid'   => array('label' => 'ยกเลิกรับเข้า',  'tone' => 'out',  'icon' => 'i-ban'),
        'issue'   => array('label' => 'ตัดออก',       'tone' => 'out',  'icon' => 'i-out'),
        'ivoid'   => array('label' => 'ยกเลิกตัดออก',  'tone' => 'in',   'icon' => 'i-ban'),
        'adjust'  => array('label' => 'ตรวจนับ',      'tone' => 'adj',  'icon' => 'i-clipboard'),
        'avoid'   => array('label' => 'ยกเลิกตรวจนับ', 'tone' => 'adj',  'icon' => 'i-ban'),
        'return'  => array('label' => 'รับคืน',       'tone' => 'in',   'icon' => 'i-receipt'),
    );
}

function move_type_of($k)
{
    $t = move_types();
    return isset($t[$k]) ? $t[$k] : array('label' => $k, 'tone' => 'adj', 'icon' => 'i-info');
}

/** กลุ่มสรุปบนหัวหน้า: รับเข้า / ขาย / ตัดออก / ปรับยอด */
function move_group($type)
{
    if ($type === 'sale' || $type === 'void' || $type === 'return') { return 'sale'; }     // รับคืนเข้าสต๊อก = หักยอดขาย
    if ($type === 'receive' || $type === 'rvoid')  { return 'receive'; }
    if ($type === 'issue' || $type === 'ivoid')    { return 'issue'; }
    return 'adjust';
}

/* ---------- รายการจากฐานข้อมูล (ao_stock_move) ---------- */

/** ชนิดใน stock_move → คีย์ของ move_types() */
function move_type_key($t)
{
    $map = array('receive_void' => 'rvoid', 'issue_void' => 'ivoid', 'adjust_void' => 'avoid', 'sale_void' => 'void');
    return isset($map[$t]) ? $map[$t] : $t;
}

/** ความเคลื่อนไหวใน ao_stock_move ตั้งแต่ $fromTs ของสาขาใน $codes (เฉพาะสินค้า $pid ถ้าระบุ)
    คืน array( สาขา => array( SKU => array( แถว ts seq type delta doc by note ) ) ) เรียงเก่า → ใหม่
    TODO:
      - [x] คิวรีเดียวทุกสาขา / ทุกสินค้า */
function move_db_rows($codes, $pid, $fromTs)
{
    $codes = array_values($codes);
    if (!$codes) {
        return array();
    }
    $in     = implode(', ', array_fill(0, count($codes), '?'));
    $params = array_merge($codes, array(date('Y-m-d H:i:s', $fromTs)));
    $sql    = 'SELECT m.move_id, m.type, m.qty, m.doc_no, m.note, m.add_date, b.code, p.sku, s.name AS by_name'
            . ' FROM ' . sdb_tb('move') . ' m JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = m.branch_id'
            . ' JOIN ' . sdb_tb('product') . ' p ON p.product_id = m.product_id'
            . ' LEFT JOIN ' . sdb_tb('staff') . ' s ON s.staff_id = m.created_by'
            . ' WHERE b.code IN (' . $in . ') AND m.add_date >= ?';
    if ($pid > 0) {
        $sql     .= ' AND m.product_id = ?';
        $params[] = (int) $pid;
    }
    $out = array();
    foreach (sdb_rows($sql . ' ORDER BY m.add_date, m.move_id', $params) as $r) {
        $out[$r['code']][$r['sku']][] = array(
            'ts'    => strtotime($r['add_date']),
            'seq'   => (int) $r['move_id'],
            'type'  => move_type_key($r['type']),
            'delta' => (int) $r['qty'],
            'doc'   => $r['doc_no'],
            'by'    => ($r['by_name'] !== null) ? $r['by_name'] : '-',
            'note'  => $r['note'],
        );
    }
    return $out;
}

/* ---------- อ่านความเคลื่อนไหวย้อนหลังจาก ao_stock_move ---------- */

/**
 * ความเคลื่อนไหวของสินค้าในสาขาใน $codes (ทุกสินค้า หรือเฉพาะ $pid) ย้อนหลัง MOVE_LOOKBACK_DAYS วัน + วันนี้
 * คืน array( สาขา => array( SKU => แถวเรียงเก่า → ใหม่ พร้อม 'bal' = คงเหลือหลังรายการนั้น ) )
 * bal คำนวณย้อนจากยอดปัจจุบัน (product_qty) — สินค้าที่เลิกขายไม่แสดง
 * TODO:
 *   - [x] ao_stock_move (ช่วงที่ 7: ไม่มีบิลใน session แล้ว)
 */
function move_rows_multi($codes, $pid = 0)
{
    $from = mktime(0, 0, 0) - MOVE_LOOKBACK_DAYS * 86400;
    $db   = move_db_rows($codes, $pid, $from);
    $out  = array();
    foreach ($codes as $c) {
        foreach (isset($db[$c]) ? array_keys($db[$c]) : array() as $sku) {
            $p = product_by_sku($sku);
            if ($p === null || !$p['active'] || ($pid > 0 && $p['id'] !== (int) $pid)) {
                continue;
            }
            $rows = $db[$c][$sku];                                  // เรียง เวลา + move_id แล้วจาก SQL
            $bal = product_qty($p, $c);
            for ($i = count($rows) - 1; $i >= 0; $i--) {
                $rows[$i]['bal'] = $bal;
                $bal -= $rows[$i]['delta'];
            }
            $out[$c][$sku] = $rows;
        }
    }
    return $out;
}

/**
 * ความเคลื่อนไหวทั้งหมดของสินค้าหนึ่งตัวในสาขาหนึ่ง เรียงเก่า → ใหม่ พร้อม 'bal' = คงเหลือหลังรายการนั้น
 * TODO:
 *   - [x] อ่านจาก ao_stock_move (move_rows_multi) แทนข้อมูลสมมติ
 */
function move_rows($code, $p)
{
    $m = move_rows_multi(array($code), $p['id']);
    return isset($m[$code][$p['sku']]) ? $m[$code][$p['sku']] : array();
}

function move_periods()
{
    return array('today' => 'วันนี้', '7' => '7 วัน', '14' => '14 วัน');
}

/** ตัดตามช่วงเวลา แล้วสรุปยอดต้นงวด/ปลายงวด และยอดรวมของแต่ละกลุ่ม */
function move_view($rows, $period, $now)
{
    $days  = ($period === 'today') ? 0 : ((int) $period - 1);
    $from  = mktime(0, 0, 0) - $days * 86400;
    $out   = array();
    $open  = null;
    $sum   = array('receive' => 0, 'sale' => 0, 'issue' => 0, 'adjust' => 0);

    foreach ($rows as $r) {
        if ($r['ts'] < $from) {
            $open = $r['bal'];                   // ยอดหลังรายการสุดท้ายก่อนช่วง = ยอดยกมา
            continue;
        }
        $out[] = $r;
        $sum[move_group($r['type'])] += $r['delta'];
    }
    if ($open === null) {
        $open = $out ? $out[0]['bal'] - $out[0]['delta'] : $now;
    }
    return array('rows' => array_reverse($out), 'open' => $open, 'close' => $now, 'sum' => $sum, 'from' => $from);
}

function move_when($ts)
{
    if (date('Ymd', $ts) === date('Ymd')) {
        return 'วันนี้ ' . date('H:i', $ts);
    }
    if (date('Ymd', $ts) === date('Ymd', time() - 86400)) {
        return 'เมื่อวาน ' . date('H:i', $ts);
    }
    $wd = array('อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.');
    return $wd[(int) date('w', $ts)] . ' ' . thai_day_month($ts) . ' ' . date('H:i', $ts);
}

function move_qs($q, $cat, $sku, $period, $extra = array())
{
    $a = array();
    foreach ($extra as $k => $v) {                 // ค่าเพิ่มของหน้า เช่น b=สาขา (หน้าผู้ดูแล)
        if ((string) $v !== '') {
            $a[] = rawurlencode($k) . '=' . rawurlencode($v);
        }
    }
    if ($q !== '')                              { $a[] = 'q='   . rawurlencode($q); }
    if ($cat !== '')                            { $a[] = 'cat=' . rawurlencode($cat); }
    if ($sku !== '')                            { $a[] = 'sku=' . rawurlencode($sku); }
    if ($period !== '' && $period !== '7')      { $a[] = 'p='   . rawurlencode($period); }
    return $a ? '?' . implode('&', $a) : '';
}

/**
 * รายการเคลื่อนไหวล่าสุดของทุกสินค้า (ใช้ตอนยังไม่ได้เลือกสินค้า)
 * $codes = สาขาที่ต้องการ · $period = today | 7 | 14 · คืนรายการใหม่สุดก่อน ไม่เกิน $limit แถว
 * แต่ละแถวมี p (สินค้า) และ branch เพิ่มจาก move_rows()
 * TODO:
 *   - [x] คิวรีเดียวทุกสินค้า (move_rows_multi) — เดิมวนทีละสินค้าทีละสาขา
 */
function movement_feed($codes, $period, $limit = 100)
{
    $out = array();
    foreach (move_rows_multi($codes) as $c => $bySku) {
        foreach ($bySku as $sku => $rows) {
            $p = product_by_sku($sku);
            $v = move_view($rows, $period, product_qty($p, $c));
            foreach ($v['rows'] as $r) {
                $r['p']      = $p;
                $r['branch'] = $c;
                $out[]       = $r;
            }
        }
    }
    usort($out, function ($a, $b) {
        return $a['ts'] === $b['ts'] ? 0 : ($a['ts'] < $b['ts'] ? 1 : -1);
    });
    return array('rows' => array_slice($out, 0, $limit), 'total' => count($out));
}

/* ==========================================================
   แก้ / ยกเลิกเอกสารย้อนหลัง (สิทธิ์เสริม backdate)
   ----------------------------------------------------------
   ใช้กับเอกสารคลัง 3 ชนิด: ใบรับเข้า (RC) · ใบเบิก/ตัดออก (IS) · ใบตรวจนับ (AD)
   บิลขายของวันก่อนไม่ยกเลิกย้อนหลัง — ใช้ "รับคืนสินค้า" แทน (เงินต้องคืนจากลิ้นชักวันนี้)

   กติกา
   - พนักงาน: ต้องมีสิทธิ์ backdate · ย้อนได้ไม่เกิน backdate_days(สาขา)
              ใบของคนอื่นต้องมีสิทธิ์ void_others เพิ่มด้วย
   - ผู้ดูแล: ย้อนได้ทุกวันที่ระบบมีข้อมูล
   - ต้องกรอกเหตุผลเสมอ · ใบเดิมไม่ถูกลบ ขึ้นว่า "ยกเลิกย้อนหลัง" พร้อมชื่อคนยกเลิก
   - สต๊อกถูกปรับ "วันนี้" (stock_move ลงวันที่วันนี้ อ้าง ref_id ใบเดิม) และลงประวัติของวันนี้
   - แก้ไข = ยกเลิกใบเดิม แล้วดึงรายการมาเป็นใบใหม่ของวันนี้ ให้แก้แล้วบันทึก (ใบใหม่ edit_of_id = ใบเดิม)
   ========================================================== */

/** ชนิดเอกสารที่แก้ย้อนหลังได้ */
function past_types()
{
    return array(
        'RC' => array('type' => 'receive', 'label' => 'ใบรับเข้า',       'void' => 'rvoid', 'page' => 'receive.php',   'tone' => 'in'),
        'IS' => array('type' => 'issue',   'label' => 'ใบเบิก / ตัดออก', 'void' => 'ivoid', 'page' => 'issue.php',     'tone' => 'out'),
        'AD' => array('type' => 'count',   'label' => 'ใบตรวจนับ',      'void' => 'avoid', 'page' => 'stocktake.php', 'tone' => 'adj'),
    );
}

/** ผู้ใช้คนนี้ย้อนดู/แก้ได้กี่วันในสาขานี้ (0 = ไม่มีสิทธิ์) */
function backdate_limit($user, $code)
{
    if ($user['role'] === 'admin') {
        return return_lookback_days();
    }
    return can($user, 'backdate') ? backdate_days($code) : 0;
}

/**
 * เอกสารคลังของสาขาในวันหนึ่ง (รวมใบที่ยกเลิก) เรียงตามเวลา — ใช้กับประวัติย้อนหลัง
 * TODO:
 *   - [x] อ่านจาก ao_stock_receive / issue / count (เดิมข้อมูลสมมติ + สถานะยกเลิกใน session)
 */
function past_docs($code, $ts)
{
    return stock_docs_range(array($code), $ts, $ts);
}

/** หาเอกสารของวันก่อนจากเลขที่ (XX-YYMMDD-NNNN) — ใบของวันนี้แก้ / ยกเลิกจากหน้างานคลังแทน */
function past_doc($code, $no)
{
    return stock_doc_by_no($code, $no, 'past');
}

function past_age($doc)
{
    return (int) round((strtotime(date('Y-m-d')) - strtotime($doc['date'])) / 86400);
}

/** แก้/ยกเลิกใบนี้ได้ไหม — คืน array(ok, msg) */
function past_can_edit($user, $doc)
{
    if (!empty($doc['void'])) {
        return array(false, 'ใบนี้ถูกยกเลิกไปแล้ว');
    }
    $age = past_age($doc);
    if ($user['role'] === 'admin') {
        return array(true, '');
    }
    if (!can($user, 'backdate')) {
        return array(false, 'ไม่มีสิทธิ์แก้เอกสารย้อนหลัง');
    }
    $lim = backdate_days($doc['branch']);
    if ($age > $lim) {
        return array(false, 'เกินกำหนด ' . $lim . ' วัน — ต้องให้ผู้ดูแลทำ');
    }
    if ($doc['by_user'] !== $user['username'] && !can($user, 'void_others')) {
        return array(false, 'ใบของ ' . $doc['by'] . ' — ต้องมีสิทธิ์แก้งานคนอื่นด้วย');
    }
    return array(true, '');
}

/**
 * ยกเลิก (หรือยกเลิกเพื่อแก้ไข) เอกสารย้อนหลัง — สต๊อกถูกปรับวันนี้ และลงประวัติของวันนี้
 * คืนค่า array('doc' => ใบเดิม, 'page' => หน้าที่ต้องไปแก้ต่อ) หรือ array('error' => ข้อความ)
 * TODO:
 *   - [x] status void + stock_move กลับรายการ (stock_doc_void_core) — เดิมเก็บสถานะใน $_SESSION['past_void']
 */
function past_doc_void($code, $no, $user, $reason, $redo)
{
    $doc = past_doc($code, $no);
    if ($doc === null) {
        return array('error' => 'ไม่พบเอกสารนี้');
    }
    $chk = past_can_edit($user, $doc);
    if (!$chk[0]) {
        return array('error' => $chk[1]);
    }
    $reason = trim($reason);
    if ($reason === '') {
        return array('error' => 'การยกเลิกหรือแก้ไขย้อนหลังต้องระบุหมายเหตุทุกครั้ง');
    }
    $t = past_types();
    $t = $t[$doc['kind']];

    if ($redo) {
        $busy = ($doc['kind'] === 'RC' && rdraft_count() > 0) || ($doc['kind'] === 'IS' && idraft_count() > 0)
             || ($doc['kind'] === 'AD' && adraft_size() > 0);
        if ($busy) {
            return array('error' => 'ยังมี' . $t['label'] . 'ที่ทำค้างอยู่ บันทึกหรือล้างใบนั้นก่อนจึงจะแก้ใบเก่าได้');
        }
    }

    $names = array();
    foreach ($doc['lines'] as $l) {
        $names[] = $l['name'] . ($doc['kind'] === 'AD' ? ' (ส่วนต่าง ' . ($l['diff'] > 0 ? '+' : '') . $l['diff'] . ')' : ' ×' . $l['qty']);
    }
    $detail = array(
        'ใบเดิม'       => $doc['no'] . ' · ' . thai_date_full(strtotime($doc['date'])) . ' ' . $doc['time'] . ' น. โดย ' . $doc['by'],
        'รายการ'       => implode(' · ', $names),
        'ย้อนหลัง'      => past_age($doc) . ' วัน',
        'สต๊อก'        => 'ปรับยอดวันนี้ให้ตรงกับการยกเลิก',
        'เหตุผล'       => $reason,
    );
    if ($redo) {
        $detail['การทำต่อ'] = 'ดึงรายการมาเป็นใบใหม่ของวันนี้เพื่อแก้แล้วบันทึก';
    }
    $r = stock_doc_void_core($doc, $user, $reason, $redo, array(
        ($redo ? 'ยกเลิกเพื่อแก้ไขย้อนหลัง ' : 'ยกเลิกย้อนหลัง ') . $t['label'] . ' ' . $doc['no'], $detail, null,
    ));
    if ($r === null) {
        return array('error' => 'ใบนี้ถูกยกเลิกไปแล้ว');
    }
    if (is_array($r)) {
        return array('error' => 'ยกเลิกไม่ได้ เพราะของบางส่วนถูกขาย/เบิกไปแล้ว — ' . implode(' · ', $r['short'])
                              . ' · กรณีนี้ให้ใช้การตรวจนับ/ปรับยอดแทน');
    }
    notify_on_backdate($code, $doc, $user, $reason, $redo);   // Telegram (ช่วงที่ 9)

    if ($redo) {
        if ($doc['kind'] === 'RC') {
            rdraft_from_receive($doc);
        } elseif ($doc['kind'] === 'IS') {
            idraft_from_issue($doc);
        } else {
            adraft_from_adj($doc);
        }
        $_SESSION['flash'] = 'ยกเลิก' . $t['label'] . ' ' . $doc['no'] . ' ของวันที่ '
                           . thai_date_full(strtotime($doc['date'])) . ' แล้ว — รายการเดิมอยู่ในใบใหม่ของวันนี้ แก้แล้วกดบันทึกได้เลย';
    }
    return array('doc' => past_doc($code, $no), 'page' => $t['page']);
}
