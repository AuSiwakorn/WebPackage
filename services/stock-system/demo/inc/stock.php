<?php
/* ==========================================================
   AOSTOCK DEMO — ตัวช่วยของหน้ารายการสินค้าในสต๊อก
   ยอดคงเหลือมาจาก product_qty() ซึ่งรวมส่วนที่ขยับไว้ใน session แล้ว
   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/data.php';
require_once dirname(__FILE__) . '/sale.php';

/* ==========================================================
   รูปสินค้า
   ----------------------------------------------------------
   วางไฟล์รูปไว้ที่  assets/products/<SKU>.jpg  (หรือ .png .webp)
   เช่น assets/products/CS-001.jpg  แล้วระบบจะหยิบมาใช้เอง
   ไม่มีรูป = วาดรูปแทนจากหมวดสินค้าให้อัตโนมัติ ไม่มีช่องว่าง
   ระบบจริง: เก็บชื่อไฟล์ไว้ในตารางสินค้าแล้วอ่านจากฟิลด์นั้นแทน
   ========================================================== */

function product_img($p)
{
    $dir = dirname(__FILE__) . '/../assets/products/';
    foreach (array('jpg', 'jpeg', 'png', 'webp') as $ext) {
        if (is_file($dir . $p['sku'] . '.' . $ext)) {
            return 'assets/products/' . $p['sku'] . '.' . $ext;
        }
    }
    return '';
}

/** ไอคอนและโทนสีประจำหมวด ใช้ตอนที่ยังไม่มีรูปจริง */
function cat_style($cat)
{
    $map = array(
        'เคสมือถือ'          => array('i-phone',   'a'),
        'เคส iPad & Tablet'  => array('i-tablet',  'b'),
        'ฟิล์มมือถือ'         => array('i-shield',  'c'),
        'ฟิล์ม iPad & Tablet' => array('i-shield',  'b'),
        'กระจกเลนส์กล้อง'    => array('i-camera',  'd'),
        'ฟิล์มไฮโดรเจล'       => array('i-shield',  'a'),
        'สายชาร์จ'           => array('i-cable',   'd'),
    );
    if (isset($map[$cat])) {
        return $map[$cat];
    }
    $tones = array('a', 'b', 'c', 'd');
    return array('i-box', $tones[abs(crc32($cat)) % 4]);
}

/** กล่องรูปสินค้า — ใช้ได้ทั้งในตารางและบนการ์ดหน้าขาย */
function thumb_html($p, $extra = '')
{
    $img = product_img($p);
    $sty = cat_style($p['cat']);
    $cls = 'thumb thumb--' . $sty[1] . ($extra !== '' ? ' ' . $extra : '');

    if ($img !== '') {
        return '<span class="' . $cls . '"><img src="' . e($img) . '" alt="" loading="lazy"></span>';
    }
    return '<span class="' . $cls . '" aria-hidden="true">'
         . '<svg class="ico"><use href="#' . $sty[0] . '"/></svg></span>';
}

/* ==========================================================
   รับสินค้าเข้าสต๊อก
   ----------------------------------------------------------
   $_SESSION['recv'][ 'BN|20260923' ][] = เอกสารรับเข้าหนึ่งใบ
   ระบบจริง: ตาราง receive_doc + receive_item + stock_move
   ========================================================== */

/* ---------- ใบรับของที่กำลังทำอยู่ (ร่าง) ----------
   เลือกสินค้าเข้ามาทีละตัว ปรับจำนวนได้ แล้วค่อยบันทึกทั้งใบครั้งเดียว
   เก็บไว้ใน $_SESSION['recv_draft'] เหมือนตะกร้าของหน้าขาย              */

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
    $new = $cur + (int) $step;
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
    $qty = (int) $qty;
    if ($qty <= 0) {
        unset($_SESSION['recv_draft'][$sku]);
        return true;
    }
    $_SESSION['recv_draft'][$sku] = $qty;      // รับเข้าไม่มีเพดาน ของมาเท่าไรก็รับเท่านั้น
    return true;
}

function rdraft_remove($sku)
{
    unset($_SESSION['recv_draft'][$sku]);
}

function rdraft_clear()
{
    $_SESSION['recv_draft'] = array();
}

/** แปลงร่างเป็นรายการพร้อมยอดคงเหลือก่อน/หลัง */
function rdraft_lines($code)
{
    $out = array();
    foreach (rdraft_all() as $sku => $qty) {
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
            'after' => $have + (int) $qty,
            'p'     => $p,
        );
    }
    return $out;
}

function recv_key($code)
{
    return $code . '|' . date('Ymd');
}

function receives_today($code)
{
    $k = recv_key($code);
    return isset($_SESSION['recv'][$k]) ? $_SESSION['recv'][$k] : array();
}

function receive_next_no($code)
{
    return 'RC-' . date('ymd') . '-' . str_pad(count(receives_today($code)) + 1, 4, '0', STR_PAD_LEFT);
}

/**
 * บันทึกการรับเข้า: บวกสต๊อกแล้วลงประวัติ
 * ผู้รับเข้าและสาขา มาจากบัญชีที่ล็อกอิน ไม่ต้องกรอกซ้ำ
 */
function receive_save($code, $user, $ref, $note, $lines)
{
    $qty = 0;
    foreach ($lines as $l) {
        $qty += (int) $l['qty'];
        stock_adj_add($code, $l['sku'], (int) $l['qty']);      // รับเข้า = สต๊อกเพิ่ม
    }

    $doc = array(
        'no'       => receive_next_no($code),
        'time'     => date('H:i'),
        'branch'   => $code,
        'by'       => $user['name'],
        'by_user'  => $user['username'],
        'ref'      => $ref,
        'note'     => $note,
        'lines'    => $lines,
        'items'    => count($lines),
        'qty'      => $qty,
    );

    $k = recv_key($code);
    if (!isset($_SESSION['recv'][$k])) {
        $_SESSION['recv'][$k] = array();
    }
    $_SESSION['recv'][$k][] = $doc;

    $names = array();
    foreach ($lines as $l) {
        $names[] = $l['name'] . ' +' . number_format($l['qty']) . ' ' . $l['unit'];
    }
    $detail = array(
        'เอกสารอ้างอิง' => $ref,
        'จำนวน'        => $doc['items'] . ' รายการ · ' . number_format($qty) . ' ชิ้น',
        'รายการ'       => implode(' · ', $names),
        'ผู้รับเข้า'     => $user['name'] . ' · ' . branch_name($code),
    );
    if ($note !== '') {
        $detail['หมายเหตุ'] = $note;
    }

    log_add($code, 'receive', $user, 'รับสินค้าเข้า ' . $doc['no'], $detail, $qty, $doc['no']);

    return $doc;
}

/**
 * ยกเลิกใบรับเข้า — ถอนยอดที่เคยบวกไว้ออกจากสต๊อก
 * ----------------------------------------------------------
 * ถ้าของถูกขายออกไปบางส่วนแล้ว การถอนจะทำให้สต๊อกติดลบ
 * กรณีนั้นถอนไม่ได้ ต้องไปใช้การตรวจนับ/ปรับยอดแทน
 * คืนค่า: array ใบที่ยกเลิก | 'sold' ถ้าของออกไปแล้ว | null ถ้าไม่พบ
 */
function receive_void($code, $no, $user, $reason, $reopen = false)
{
    $k = recv_key($code);
    if (!isset($_SESSION['recv'][$k])) {
        return null;
    }

    foreach ($_SESSION['recv'][$k] as $i => $d) {
        if ($d['no'] !== $no || !empty($d['void'])) {
            continue;
        }

        /* ตรวจก่อนว่าถอนแล้วสต๊อกจะไม่ติดลบ */
        $short = array();
        foreach ($d['lines'] as $l) {
            $p = product_by_sku($l['sku']);
            if ($p === null) {
                continue;
            }
            $have = product_qty($p, $code);
            if ($have < (int) $l['qty']) {
                $short[] = $l['name'] . ' (เหลือ ' . number_format($have)
                         . ' แต่ต้องถอน ' . number_format($l['qty']) . ')';
            }
        }
        if ($short) {
            return array('error' => 'sold', 'items' => $short);
        }

        foreach ($d['lines'] as $l) {
            stock_adj_add($code, $l['sku'], -(int) $l['qty']);      // ถอนยอดที่รับเข้าไว้
        }

        $_SESSION['recv'][$k][$i]['void']        = true;
        $_SESSION['recv'][$k][$i]['void_at']     = date('H:i');
        $_SESSION['recv'][$k][$i]['void_by']     = $user['name'];
        $_SESSION['recv'][$k][$i]['void_user']   = $user['username'];
        $_SESSION['recv'][$k][$i]['void_reason'] = trim($reason);
        $_SESSION['recv'][$k][$i]['void_mode']   = $reopen ? 'edit' : 'void';

        $v      = $_SESSION['recv'][$k][$i];
        $detail = array(
            'ใบเดิม'      => $v['no'] . ' · รับเข้าเมื่อ ' . $v['time'] . ' น. โดย ' . $v['by'],
            'เอกสารอ้างอิง' => $v['ref'],
            'ยอดที่ถอนออก'  => $v['items'] . ' รายการ · ' . number_format($v['qty']) . ' ชิ้น',
            'เหตุผล'      => $v['void_reason'] !== '' ? $v['void_reason'] : 'ไม่ได้ระบุ',
        );
        if ($reopen) {
            $detail['การทำต่อ'] = 'ดึงรายการกลับเข้าใบเพื่อรับเข้าใหม่ทดแทน';
        }

        log_add($code, 'rvoid', $user,
                ($reopen ? 'ยกเลิกเพื่อแก้ไข ใบรับเข้า ' : 'ยกเลิกใบรับเข้า ') . $v['no'],
                $detail, $v['qty'], $v['no']);

        return $v;
    }
    return null;
}

/** ดึงรายการทั้งใบกลับเข้าร่าง เพื่อแก้แล้วรับเข้าใหม่ */
function rdraft_from_receive($doc)
{
    $_SESSION['recv_draft'] = array();
    foreach ($doc['lines'] as $l) {
        $_SESSION['recv_draft'][$l['sku']] = (int) $l['qty'];
    }
    return rdraft_count();
}

function receive_by_no($code, $no)
{
    foreach (receives_today($code) as $d) {
        if ($d['no'] === $no) {
            return $d;
        }
    }
    return null;
}

function stock_status_tabs()
{
    return array(
        ''    => 'ทั้งหมด',
        'low' => 'ใกล้หมด',
        'out' => 'หมดแล้ว',
        'ok'  => 'พอใช้',
    );
}

function stock_sorts()
{
    return array(
        'urgent' => 'ด่วนก่อน (ใกล้หมดขึ้นก่อน)',
        'name'   => 'ชื่อสินค้า ก–ฮ',
        'qty'    => 'คงเหลือมากไปน้อย',
        'qtyasc' => 'คงเหลือน้อยไปมาก',
        'cat'    => 'หมวดสินค้า',
    );
}

function stock_label($st)
{
    if ($st === 'out') { return 'หมดแล้ว'; }
    if ($st === 'low') { return 'ใกล้หมด'; }
    return 'พอใช้';
}

function stock_tone($st)
{
    if ($st === 'out') { return 'out'; }
    if ($st === 'low') { return 'adj'; }
    return 'in';
}

/** ประกอบ query string ของตัวกรอง โดยข้ามค่าที่ว่าง */
function stock_qs($q, $cat, $st, $sort)
{
    $parts = array();
    if ($q !== '')              { $parts[] = 'q='    . rawurlencode($q); }
    if ($cat !== '')            { $parts[] = 'cat='  . rawurlencode($cat); }
    if ($st !== '')             { $parts[] = 'st='   . rawurlencode($st); }
    if ($sort !== 'urgent' && $sort !== '') { $parts[] = 'sort=' . rawurlencode($sort); }
    return $parts ? '?' . implode('&', $parts) : '';
}

/**
 * รายการสินค้าของสาขาหนึ่ง พร้อมสถานะและระดับสต๊อก
 * pct = ยอดคงเหลือเทียบกับจุดสั่งซื้อ (เกิน 100 คือปลอดภัย)
 */
function stock_rows($code, $q, $cat, $st, $sort)
{
    $q    = trim($q);
    $rows = array();

    foreach (demo_products() as $p) {
        if ($cat !== '' && $p['cat'] !== $cat) {
            continue;
        }
        if ($q !== '' && stripos($p['name'], $q) === false && stripos($p['sku'], $q) === false) {
            continue;
        }
        $qty    = product_qty($p, $code);
        $status = branch_status($p, $code);
        if ($st !== '' && $status !== $st) {
            continue;
        }
        $rows[] = array(
            'p'      => $p,
            'qty'    => $qty,
            'status' => $status,
            'pct'    => $p['reorder'] > 0 ? (int) round($qty / $p['reorder'] * 100) : 100,
        );
    }

    usort($rows, stock_sorter($sort));
    return $rows;
}

function stock_sorter($sort)
{
    if ($sort === 'name')   { return 'stock_cmp_name'; }
    if ($sort === 'qty')    { return 'stock_cmp_qty_desc'; }
    if ($sort === 'qtyasc') { return 'stock_cmp_qty_asc'; }
    if ($sort === 'cat')    { return 'stock_cmp_cat'; }
    return 'stock_cmp_urgent';
}

function stock_cmp_name($a, $b)
{
    return strcmp($a['p']['name'], $b['p']['name']);
}

function stock_cmp_qty_desc($a, $b)
{
    if ($a['qty'] === $b['qty']) { return stock_cmp_name($a, $b); }
    return ($a['qty'] < $b['qty']) ? 1 : -1;
}

function stock_cmp_qty_asc($a, $b)
{
    if ($a['qty'] === $b['qty']) { return stock_cmp_name($a, $b); }
    return ($a['qty'] > $b['qty']) ? 1 : -1;
}

function stock_cmp_cat($a, $b)
{
    $c = strcmp($a['p']['cat'], $b['p']['cat']);
    return ($c !== 0) ? $c : stock_cmp_name($a, $b);
}

/** ของที่ใกล้หมดที่สุดขึ้นก่อน แล้วค่อยเรียงตามชื่อ */
function stock_cmp_urgent($a, $b)
{
    if ($a['pct'] === $b['pct']) { return stock_cmp_name($a, $b); }
    return ($a['pct'] > $b['pct']) ? 1 : -1;
}

/** สรุปเฉพาะรายการที่แสดงอยู่ตอนนี้ — การ์ดด้านบนใช้ชุดนี้ ตัวเลขจะได้ตรงกับตาราง */
function stock_view_sum($rows)
{
    $sum = array('skus' => 0, 'qty' => 0, 'low' => 0, 'out' => 0);
    foreach ($rows as $r) {
        $sum['skus']++;
        $sum['qty'] += $r['qty'];
        if ($r['status'] === 'low') { $sum['low']++; }
        if ($r['status'] === 'out') { $sum['out']++; }
    }
    return $sum;
}

/** มีตัวกรองอะไรเปิดอยู่บ้าง — ไว้บอกผู้ใช้ว่าตัวเลขที่เห็นมาจากอะไร */
function stock_filter_words($q, $cat, $st)
{
    $w = array();
    if ($cat !== '') { $w[] = 'หมวด ' . $cat; }
    if ($st !== '')  {
        $tabs = stock_status_tabs();
        $w[]  = isset($tabs[$st]) ? $tabs[$st] : $st;
    }
    if ($q !== '')   { $w[] = 'คำค้น “' . $q . '”'; }
    return $w;
}

/** นับจำนวนของแต่ละแท็บสถานะ โดยใช้คำค้นและหมวดเดิม แต่ไม่สนสถานะ */
function stock_tab_counts($code, $q, $cat)
{
    $all = stock_rows($code, $q, $cat, '', 'name');
    $out = array('' => count($all), 'low' => 0, 'out' => 0, 'ok' => 0);
    foreach ($all as $r) {
        $out[$r['status']]++;
    }
    return $out;
}

/** สรุปทั้งสาขา (ไม่สนตัวกรอง) ไว้โชว์เป็นการ์ดด้านบน */
function stock_branch_sum($code)
{
    $sum = array('skus' => 0, 'qty' => 0, 'low' => 0, 'out' => 0, 'value' => 0);
    foreach (demo_products() as $p) {
        $qty = product_qty($p, $code);
        $sum['skus']++;
        $sum['qty']   += $qty;
        $sum['value'] += $qty * product_price($p);
        $status = branch_status($p, $code);
        if ($status === 'low') { $sum['low']++; }
        if ($status === 'out') { $sum['out']++; }
    }
    return $sum;
}
