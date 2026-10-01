<?php
/* ==========================================================
   AOSTOCK DEMO — ตรวจนับ / ปรับยอด (แบบเบา)
   ----------------------------------------------------------
   เห็นของจริงไม่ตรงกับระบบ → เลือกสินค้า → กรอก "จำนวนที่นับได้จริง"
   ระบบคำนวณส่วนต่างให้เอง แล้วปรับสต๊อกให้ตรงกับของจริง

   ต่างจากรับเข้า/ตัดออกตรงที่ กรอก "ยอดจริง" ไม่ใช่ "จำนวนที่เพิ่ม/ลด"
   ส่วนต่างคำนวณตอนกดบันทึก เทียบกับยอดในระบบ ณ ตอนนั้น
   → ถ้ามีการขายระหว่างที่นับ ผลก็ยังถูก เพราะเทียบกับยอดล่าสุดเสมอ

   ยังไม่มีฐานข้อมูล:
     ใบที่กำลังทำ   $_SESSION['adj_draft'][ SKU ] = จำนวนที่นับได้
     หัวใบที่ค้างไว้  $_SESSION['adj_meta']
     ใบที่บันทึกแล้ว $_SESSION['adj'][ 'BN|20260924' ][] = เอกสารหนึ่งใบ
   ระบบจริง: ตาราง count_doc + count_item + stock_move

   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

require_once dirname(__FILE__) . '/stock.php';
require_once dirname(__FILE__) . '/store.php';      // money2()
require_once dirname(__FILE__) . '/activity.php';

/* ---------- สาเหตุที่ยอดไม่ตรง ---------- */
function adj_reasons()
{
    return array(
        'miscount' => array('label' => 'นับผิดครั้งก่อน',       'hint' => 'ยอดเดิมในระบบคลาดมาตั้งแต่ต้น', 'note' => false),
        'unlogged' => array('label' => 'ใช้/เสียไม่ได้บันทึก',  'hint' => 'หยิบใช้ ชำรุด ไม่ได้ตัดออก',   'note' => false),
        'wrongkey' => array('label' => 'บันทึกผิด',           'hint' => 'รับเข้า / ขาย กรอกจำนวนผิด',  'note' => false),
        'found'    => array('label' => 'เจอของเพิ่ม',          'hint' => 'ของตกหล่น วางผิดที่',          'note' => false),
        'lost'     => array('label' => 'ของหาย',             'hint' => 'หาไม่พบ ต้องระบุรายละเอียด',  'note' => true),
        'other'    => array('label' => 'อื่น ๆ',              'hint' => 'ต้องระบุรายละเอียด',            'note' => true),
    );
}

function adj_reason_label($key)
{
    $r = adj_reasons();
    return isset($r[$key]) ? $r[$key]['label'] : $key;
}

function adj_reason_needs_note($key)
{
    $r = adj_reasons();
    return isset($r[$key]) && $r[$key]['note'];
}

/* ==========================================================
   ใบที่กำลังทำ (ร่าง) — เก็บ "จำนวนที่นับได้จริง"
   ========================================================== */

function adraft_all()
{
    return isset($_SESSION['adj_draft']) && is_array($_SESSION['adj_draft'])
         ? $_SESSION['adj_draft'] : array();
}

function adraft_size()
{
    return count(adraft_all());
}

/** แตะสินค้าเข้าใบ: เริ่มต้นที่ยอดในระบบ (ส่วนต่าง 0) แล้วค่อยแก้เป็นยอดที่นับได้ */
/** ใบนี้ใส่ได้อีกกี่รายการ (null = ไม่จำกัด) — จำกัดเฉพาะตอนร้านเปิด */
function adj_limit($code)
{
    $max = count_open_limit($code);
    return ($max > 0 && store_is_open($code)) ? $max : null;
}

function adj_full($code)
{
    $max = adj_limit($code);
    return $max !== null && adraft_size() >= $max;
}

/**
 * แตะสินค้าเข้าใบ: เริ่มที่ยอดในระบบ (ส่วนต่าง 0) แล้วค่อยแก้เป็นยอดที่นับได้
 * จดยอดในระบบ ณ ตอนนี้ไว้ด้วย (snap) — ตอนบันทึกถ้ายอดเปลี่ยนไป แปลว่ามีการขายแทรก ต้องนับใหม่
 */
function adraft_pick($code, $sku)
{
    $p = product_by_sku($sku);
    if ($p === null || isset($_SESSION['adj_draft'][$sku]) || adj_full($code)) {
        return false;
    }
    $have = max(0, product_qty($p, $code));
    $_SESSION['adj_draft'][$sku] = $have;
    $_SESSION['adj_snap'][$sku]  = $have;
    return true;
}

/**
 * รายการที่ยอดในระบบเปลี่ยนไประหว่างนับ (มีการขาย/รับเข้า/ตัดออกแทรก)
 * คืนค่าชื่อสินค้า และอัปเดต snap เป็นยอดล่าสุด ให้พนักงานนับใหม่แล้วกดบันทึกอีกครั้ง
 */
function adj_moved($code)
{
    $out = array();
    foreach (adraft_all() as $sku => $cnt) {
        $p = product_by_sku($sku);
        if ($p === null || !isset($_SESSION['adj_snap'][$sku])) {
            continue;
        }
        $now = product_qty($p, $code);
        if ($now !== (int) $_SESSION['adj_snap'][$sku]) {
            $out[] = $p['name'] . ' (ตอนเริ่มนับ ' . number_format($_SESSION['adj_snap'][$sku])
                   . ' ตอนนี้ ' . number_format($now) . ')';
            $_SESSION['adj_snap'][$sku] = $now;
        }
    }
    return $out;
}

function adraft_set($sku, $qty)
{
    if (product_by_sku($sku) === null) {
        return false;
    }
    $_SESSION['adj_draft'][$sku] = max(0, (int) $qty);    // นับได้ 0 ก็คือของหมดจริง ยังอยู่ในใบ
    return true;
}

function adraft_step($sku, $step)
{
    $cur = isset($_SESSION['adj_draft'][$sku]) ? (int) $_SESSION['adj_draft'][$sku] : 0;
    return adraft_set($sku, $cur + (int) $step);
}

function adraft_remove($sku)
{
    unset($_SESSION['adj_draft'][$sku], $_SESSION['adj_snap'][$sku]);
}

function adraft_clear()
{
    $_SESSION['adj_draft'] = array();
    $_SESSION['adj_snap']  = array();
    unset($_SESSION['adj_meta']);
}

function adraft_meta()
{
    $m = isset($_SESSION['adj_meta']) && is_array($_SESSION['adj_meta']) ? $_SESSION['adj_meta'] : array();
    return array(
        'reason' => isset($m['reason']) ? $m['reason'] : '',
        'note'   => isset($m['note'])   ? $m['note']   : '',
    );
}

/** แปลงร่างเป็นรายการ: ยอดในระบบตอนนี้ · นับได้ · ส่วนต่าง · มูลค่าส่วนต่าง */
function adraft_lines($code)
{
    $out = array();
    foreach (adraft_all() as $sku => $counted) {
        $p = product_by_sku($sku);
        if ($p === null) {
            continue;
        }
        $have  = product_qty($p, $code);
        $diff  = (int) $counted - $have;
        $out[] = array(
            'sku'     => $sku,
            'name'    => $p['name'],
            'cat'     => $p['cat'],
            'unit'    => $p['unit'],
            'have'    => $have,
            'counted' => (int) $counted,
            'diff'    => $diff,
            'value'   => $diff * (float) $p['cost'],
            'p'       => $p,
        );
    }
    return $out;
}

/** สรุปส่วนต่างของทั้งใบ */
function adj_sum($lines)
{
    $s = array('items' => count($lines), 'same' => 0, 'over' => 0, 'short' => 0,
               'plus' => 0, 'minus' => 0, 'value' => 0);
    foreach ($lines as $l) {
        if ($l['diff'] > 0)      { $s['over']++;  $s['plus']  += $l['diff']; }
        elseif ($l['diff'] < 0)  { $s['short']++; $s['minus'] += -$l['diff']; }
        else                     { $s['same']++; }
        $s['value'] += $l['value'];
    }
    return $s;
}

/* ==========================================================
   ใบที่บันทึกแล้ว
   ========================================================== */

function adj_key($code)
{
    return $code . '|' . date('Ymd');
}

function adjs_today($code)
{
    $k = adj_key($code);
    return isset($_SESSION['adj'][$k]) ? $_SESSION['adj'][$k] : array();
}

function adj_next_no($code)
{
    return 'AD-' . date('ymd') . '-' . str_pad(count(adjs_today($code)) + 1, 4, '0', STR_PAD_LEFT);
}

function adj_by_no($code, $no)
{
    foreach (adjs_today($code) as $d) {
        if ($d['no'] === $no) {
            return $d;
        }
    }
    return null;
}

/**
 * บันทึกผลการนับ: ปรับสต๊อกเท่ากับส่วนต่าง แล้วลงประวัติ
 * $reason ว่างได้ถ้าทุกรายการตรงกันหมด (เป็นการยืนยันว่านับแล้วตรง)
 */
function adj_save($code, $user, $reason, $note, $lines)
{
    foreach ($lines as $l) {
        if ($l['diff'] !== 0) {
            stock_adj_add($code, $l['sku'], $l['diff']);
        }
    }
    $sum = adj_sum($lines);

    $doc = array(
        'no'      => adj_next_no($code),
        'time'    => date('H:i'),
        'branch'  => $code,
        'by'      => $user['name'],
        'by_user' => $user['username'],
        'reason'  => $reason,
        'note'    => $note,
        'lines'   => $lines,
        'items'   => $sum['items'],
        'sum'     => $sum,
    );

    $k = adj_key($code);
    if (!isset($_SESSION['adj'][$k])) {
        $_SESSION['adj'][$k] = array();
    }
    $_SESSION['adj'][$k][] = $doc;

    $names = array();
    foreach ($lines as $l) {
        $names[] = $l['name'] . ' ' . number_format($l['have']) . '→' . number_format($l['counted'])
                 . ($l['diff'] === 0 ? ' (ตรง)' : ' (' . ($l['diff'] > 0 ? '+' : '−') . number_format(abs($l['diff'])) . ')');
    }
    $detail = array(
        'นับ'           => $sum['items'] . ' รายการ · ตรง ' . $sum['same'] . ' · เกิน ' . $sum['over']
                          . ' · ขาด ' . $sum['short'],
        'ส่วนต่างสุทธิ'    => ($sum['plus'] - $sum['minus'] >= 0 ? '+' : '−')
                          . number_format(abs($sum['plus'] - $sum['minus'])) . ' ชิ้น · '
                          . ($sum['value'] < 0 ? '−' : '') . money2(abs($sum['value'])) . ' บาท',
        'รายการ'        => implode(' · ', $names),
        'ผู้ตรวจนับ'      => $user['name'] . ' · ' . branch_name($code),
    );
    if ($reason !== '') {
        $detail['สาเหตุ'] = adj_reason_label($reason);
    }
    if ($note !== '') {
        $detail['หมายเหตุ'] = $note;
    }

    $title = ($sum['over'] + $sum['short'] === 0)
           ? 'ตรวจนับตรงทุกรายการ ' . $doc['no']
           : 'ตรวจนับ / ปรับยอด ' . $doc['no'];
    log_add($code, 'adjust', $user, $title, $detail, null, $doc['no']);

    return $doc;
}

/**
 * ยกเลิกใบปรับยอด — ถอยส่วนต่างที่เคยปรับไว้กลับ
 * ถ้าใบเดิม "บวก" ของเพิ่มแล้วของนั้นถูกขายไปแล้ว การถอยจะทำให้ติดลบ → ยกเลิกไม่ได้
 * ต้องนับใหม่อีกรอบแทน
 */
function adj_void($code, $no, $user, $reason, $reopen = false)
{
    $k = adj_key($code);
    if (!isset($_SESSION['adj'][$k])) {
        return null;
    }

    foreach ($_SESSION['adj'][$k] as $i => $d) {
        if ($d['no'] !== $no || !empty($d['void'])) {
            continue;
        }

        $short = array();
        foreach ($d['lines'] as $l) {
            $p = product_by_sku($l['sku']);
            if ($p !== null && $l['diff'] > 0 && product_qty($p, $code) < $l['diff']) {
                $short[] = $l['name'];
            }
        }
        if ($short) {
            return array('error' => 'sold', 'items' => $short);
        }

        foreach ($d['lines'] as $l) {
            if ($l['diff'] !== 0) {
                stock_adj_add($code, $l['sku'], -$l['diff']);
            }
        }

        $_SESSION['adj'][$k][$i]['void']        = true;
        $_SESSION['adj'][$k][$i]['void_at']     = date('H:i');
        $_SESSION['adj'][$k][$i]['void_by']     = $user['name'];
        $_SESSION['adj'][$k][$i]['void_user']   = $user['username'];
        $_SESSION['adj'][$k][$i]['void_reason'] = trim($reason);
        $_SESSION['adj'][$k][$i]['void_mode']   = $reopen ? 'edit' : 'void';

        $v      = $_SESSION['adj'][$k][$i];
        $detail = array(
            'ใบเดิม' => $v['no'] . ' · นับเมื่อ ' . $v['time'] . ' น. โดย ' . $v['by'],
            'ผล'     => 'ถอยยอดทุกรายการกลับเป็นก่อนตรวจนับ',
            'เหตุผล' => $v['void_reason'] !== '' ? $v['void_reason'] : 'ไม่ได้ระบุ',
        );
        if ($reopen) {
            $detail['การทำต่อ'] = 'ดึงรายการกลับเข้าใบเพื่อนับ/บันทึกใหม่';
        }
        log_add($code, 'avoid', $user,
                ($reopen ? 'ยกเลิกเพื่อแก้ไข ใบตรวจนับ ' : 'ยกเลิกใบตรวจนับ ') . $v['no'],
                $detail, null, $v['no']);

        return $v;
    }
    return null;
}

function adraft_from_adj($doc)
{
    $_SESSION['adj_draft'] = array();
    foreach ($doc['lines'] as $l) {
        $_SESSION['adj_draft'][$l['sku']] = (int) $l['counted'];
    }
    $_SESSION['adj_meta'] = array('reason' => $doc['reason'], 'note' => $doc['note']);
    $_SESSION['adj_snap'] = array();
    foreach ($doc['lines'] as $l) {
        $p = product_by_sku($l['sku']);
        if ($p !== null) {
            $_SESSION['adj_snap'][$l['sku']] = product_qty($p, $doc['branch']);   // ยอดหลังถอยใบเดิมแล้ว
        }
    }
    return adraft_size();
}

/* ==========================================================
   รอบการตรวจนับ — "นับแล้ว" หมายถึงนับแล้วภายในรอบปัจจุบัน
   ========================================================== */

/** รอบปัจจุบันของสาขา: array(start, end, due) เป็น timestamp เที่ยงคืน */
function count_round($code, $now = null)
{
    $now = ($now === null) ? time() : $now;
    $day = count_round_day($code);
    $y   = (int) date('Y', $now);
    $m   = (int) date('n', $now);

    if ((int) date('j', $now) < $day) {          // ยังไม่ถึงวันเริ่มของเดือนนี้ → รอบเริ่มเดือนก่อน
        $m--;
        if ($m < 1) { $m = 12; $y--; }
    }
    $start = mktime(0, 0, 0, $m, $day, $y);
    $next  = mktime(0, 0, 0, $m + 1, $day, $y);
    return array(
        'start' => $start,
        'end'   => $next - 86400,                 // วันสุดท้ายของรอบ = กำหนดส่ง
        'left'  => (int) floor(($next - mktime(0, 0, 0)) / 86400),   // เหลือกี่วัน (รวมวันนี้)
    );
}

function thai_day_month($ts)
{
    return (int) date('j', $ts) . ' ' . thai_month_short($ts);
}

/**
 * ประวัติการนับในรอบนี้ก่อนวันนี้ (ข้อมูลสมมติ ให้เดโมมีบางรายการที่นับไปแล้ว)
 * ระบบจริง: SELECT ล่าสุดจาก count_item ของสาขานี้ ที่ created_at >= วันเริ่มรอบ
 */
function count_seed($code, $sku, $round)
{
    $today = mktime(0, 0, 0);
    $days  = (int) floor(($today - $round['start']) / 86400);    // จำนวนวันที่ผ่านไปแล้วในรอบ
    if ($days < 1) {
        return null;
    }
    $h = abs(crc32($code . '|' . $sku . '|' . date('Ymd', $round['start'])));
    if ($h % 100 >= min(60, 8 + $days * 3)) {                     // ยิ่งผ่านไปหลายวัน ยิ่งนับไปเยอะ
        return null;
    }
    $v    = ($h >> 7) % 10;
    $diff = ($v <= 6) ? 0 : ($v === 7 ? -1 : ($v === 8 ? -2 : 1));
    return array(
        'at'   => $round['start'] + (($h >> 11) % $days) * 86400 + (9 + ($h >> 3) % 9) * 3600,
        'diff' => $diff,
        'by'   => '',
        'no'   => '',
    );
}

/** สถานะการนับของทุกสินค้าในรอบนี้: array( SKU => array(at, diff, by, no) ) */
function count_status_all($code)
{
    $round = count_round($code);
    $out   = array();
    foreach (demo_products() as $p) {
        $s = count_seed($code, $p['sku'], $round);
        if ($s !== null) {
            $out[$p['sku']] = $s;
        }
    }
    /* ใบที่นับวันนี้ (ไม่รวมที่ถูกยกเลิก) ทับของเดิม — ใบหลังสุดชนะ */
    $today = mktime(0, 0, 0);
    foreach (adjs_today($code) as $d) {
        if (!empty($d['void'])) {
            continue;
        }
        $parts = explode(':', $d['time']);
        $at    = $today + (int) $parts[0] * 3600 + (int) $parts[1] * 60;
        foreach ($d['lines'] as $l) {
            $out[$l['sku']] = array('at' => $at, 'diff' => $l['diff'], 'by' => $d['by'], 'no' => $d['no']);
        }
    }
    return $out;
}

/** query string ของหน้าตรวจนับ (แท็บ "ยังไม่นับ" เป็นค่าเริ่มต้น ไม่ต้องใส่) */
function adj_qs($q, $cat, $tab)
{
    $a = array();
    if ($q !== '')                   { $a[] = 'q='   . rawurlencode($q); }
    if ($cat !== '')                 { $a[] = 'cat=' . rawurlencode($cat); }
    if ($tab !== '' && $tab !== 'todo') { $a[] = 't=' . rawurlencode($tab); }
    return $a ? '?' . implode('&', $a) : '';
}

function count_tabs()
{
    return array('todo' => 'ยังไม่นับ', 'done' => 'นับแล้ว', 'all' => 'ทั้งหมด');
}

/** ข้อความสั้นบนการ์ด: "นับ 12 ก.ย. · ตรง" / "นับวันนี้ 10:32 · ขาด 2" */
function count_note($st)
{
    $when = (date('Ymd', $st['at']) === date('Ymd'))
          ? 'วันนี้ ' . date('H:i', $st['at'])
          : thai_day_month($st['at']);
    if ($st['diff'] === 0)  { $res = 'ตรง'; }
    elseif ($st['diff'] > 0) { $res = 'เกิน ' . number_format($st['diff']); }
    else                     { $res = 'ขาด ' . number_format(-$st['diff']); }
    return array('when' => 'นับ ' . $when, 'res' => $res, 'tone' => $st['diff'] === 0 ? 'eq' : ($st['diff'] > 0 ? 'up' : 'dn'));
}

/** ป้ายส่วนต่าง: +3 / −2 / ตรง */
function diff_chip($d)
{
    if ($d > 0) {
        return '<span class="dchip up">+' . number_format($d) . '</span>';
    }
    if ($d < 0) {
        return '<span class="dchip dn">−' . number_format(-$d) . '</span>';
    }
    return '<span class="dchip eq">ตรง</span>';
}
