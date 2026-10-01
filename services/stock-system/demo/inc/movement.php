<?php
/* ==========================================================
   AOSTOCK DEMO — ประวัติเคลื่อนไหวรายสินค้า
   ----------------------------------------------------------
   รวมทุกอย่างที่ทำให้ยอดของสินค้าหนึ่งตัวขยับ: ขาย รับเข้า ตัดออก ตรวจนับ
   และการยกเลิกเอกสารเหล่านั้น แล้วเรียงตามเวลา พร้อมยอดคงเหลือหลังแต่ละรายการ

   ข้อมูลวันนี้มาจาก session จริง (บิลขาย ใบรับเข้า ใบตัดออก ใบตรวจนับ)
   ข้อมูลย้อนหลัง 13 วันเป็นข้อมูลสมมติ เพราะเดโมยังไม่มีฐานข้อมูล
   ยอดคงเหลือคำนวณย้อนจาก "ยอดตอนนี้" เสมอ ตัวเลขจึงต่อกันพอดี

   ระบบจริง: SELECT * FROM stock_move WHERE branch_id=? AND product_id=? ORDER BY created_at
   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

require_once dirname(__FILE__) . '/stock.php';
require_once dirname(__FILE__) . '/issue.php';
require_once dirname(__FILE__) . '/adjust.php';

define('MOVE_SEED_DAYS', 13);          // จำนวนวันย้อนหลังที่สร้างข้อมูลสมมติ (ไม่รวมวันนี้)

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
    if ($type === 'sale' || $type === 'void')      { return 'sale'; }
    if ($type === 'receive' || $type === 'rvoid')  { return 'receive'; }
    if ($type === 'issue' || $type === 'ivoid')    { return 'issue'; }
    return 'adjust';
}

/** แปลง "HH:MM" ของวันนี้เป็น timestamp */
function move_ts_today($hm)
{
    $p = explode(':', $hm);
    return mktime((int) $p[0], isset($p[1]) ? (int) $p[1] : 0, 0);
}

/* ---------- รายการของวันนี้ (จาก session) ---------- */
function move_today_rows($code, $sku)
{
    $rows = array();
    $seq  = 0;
    $add  = function ($ts, $type, $delta, $doc, $by, $note) use (&$rows, &$seq) {
        $rows[] = array('ts' => $ts, 'seq' => $seq++, 'type' => $type, 'delta' => (int) $delta,
                        'doc' => $doc, 'by' => $by, 'note' => $note);
    };

    foreach (bills_today($code) as $b) {
        foreach ($b['lines'] as $l) {
            if ($l['sku'] !== $sku) { continue; }
            $add(move_ts_today($b['time']), 'sale', -$l['qty'], $b['no'], $b['by'], '');
            if (!empty($b['void'])) {
                $add(move_ts_today($b['void_at']), 'void', $l['qty'], $b['no'], $b['void_by'], $b['void_reason']);
            }
        }
    }
    foreach (receives_today($code) as $d) {
        foreach ($d['lines'] as $l) {
            if ($l['sku'] !== $sku) { continue; }
            $add(move_ts_today($d['time']), 'receive', $l['qty'], $d['no'], $d['by'], 'อ้างอิง ' . $d['ref']);
            if (!empty($d['void'])) {
                $add(move_ts_today($d['void_at']), 'rvoid', -$l['qty'], $d['no'], $d['void_by'], $d['void_reason']);
            }
        }
    }
    foreach (issues_today($code) as $d) {
        foreach ($d['lines'] as $l) {
            if ($l['sku'] !== $sku) { continue; }
            $add(move_ts_today($d['time']), 'issue', -$l['qty'], $d['no'], $d['by'], issue_reason_label($d['reason']));
            if (!empty($d['void'])) {
                $add(move_ts_today($d['void_at']), 'ivoid', $l['qty'], $d['no'], $d['void_by'], $d['void_reason']);
            }
        }
    }
    foreach (adjs_today($code) as $d) {
        foreach ($d['lines'] as $l) {
            if ($l['sku'] !== $sku) { continue; }
            $note = 'นับได้ ' . number_format($l['counted'])
                  . ($d['reason'] !== '' ? ' · ' . adj_reason_label($d['reason']) : ' · ตรงกับระบบ');
            $add(move_ts_today($d['time']), 'adjust', $l['diff'], $d['no'], $d['by'], $note);
            if (!empty($d['void']) && $l['diff'] !== 0) {
                $add(move_ts_today($d['void_at']), 'avoid', -$l['diff'], $d['no'], $d['void_by'], $d['void_reason']);
            }
        }
    }
    return $rows;
}

/* ---------- ข้อมูลย้อนหลังสมมติ ---------- */

/** พนักงานของสาขา (ไว้ใส่ชื่อผู้ทำในข้อมูลสมมติ) */
function move_branch_staff($code)
{
    $out = array();
    foreach (demo_users_all() as $u) {
        if ($u['branch'] === $code) {
            $out[] = $u['name'];
        }
    }
    return $out ? $out : array('พนักงาน');
}

/** เหตุการณ์ย้อนหลังแบบสุ่มคงที่ (เปิดกี่ครั้งก็ได้ชุดเดิม) — ยังไม่ใส่ยอดคงเหลือ */
function move_seed_rows($code, $p)
{
    $rows  = array();
    $staff = move_branch_staff($code);
    $today = mktime(0, 0, 0);
    $round = count_round($code);
    $cnt   = count_seed($code, $p['sku'], $round);

    for ($d = MOVE_SEED_DAYS; $d >= 1; $d--) {
        $day = $today - $d * 86400;
        if ((int) date('w', $day) === 0) {
            continue;                                          // อาทิตย์ร้านปิด
        }
        $h  = abs(crc32($code . '|' . $p['sku'] . '|' . date('Ymd', $day)));
        $by = $staff[$h % count($staff)];
        $ds = date('ymd', $day);

        if ($h % 100 < 10) {                                   // ของเข้า
            $q = max(2, $p['reorder'] * (1 + ($h >> 5) % 2));
            $rows[] = array('ts' => $day + (9 * 3600) + (($h >> 3) % 50) * 60, 'type' => 'receive', 'delta' => $q,
                            'doc' => 'RC-' . $ds . '-' . sprintf('%04d', 1 + ($h >> 9) % 3), 'by' => $by,
                            'note' => 'อ้างอิง INV-' . substr($ds, 2) . sprintf('%02d', ($h >> 4) % 90));
        }
        $n = ($h >> 7) % 4;                                    // ขายวันละ 0–3 บิลที่มีตัวนี้
        for ($i = 0; $i < $n; $i++) {
            $hh = ($h >> ($i * 4 + 11));
            $rows[] = array('ts' => $day + (10 + $i * 3 + $hh % 3) * 3600 + ($hh % 60) * 60, 'type' => 'sale',
                            'delta' => -(1 + $hh % 2), 'doc' => 'S-' . $ds . '-' . sprintf('%04d', 3 + ($hh % 40)),
                            'by' => $staff[($hh >> 2) % count($staff)], 'note' => '');
        }
        if (($h >> 13) % 100 < 4) {                            // ของเสีย / เบิกใช้
            $rows[] = array('ts' => $day + 17 * 3600 + (($h >> 2) % 50) * 60, 'type' => 'issue', 'delta' => -1,
                            'doc' => 'IS-' . $ds . '-0001', 'by' => $by,
                            'note' => (($h >> 6) % 2) ? 'ชำรุด / เสียหาย' : 'เบิกใช้ภายใน');
        }
        if ($cnt !== null && date('Ymd', $cnt['at']) === date('Ymd', $day)) {
            $rows[] = array('ts' => $cnt['at'], 'type' => 'adjust', 'delta' => $cnt['diff'],
                            'doc' => 'AD-' . $ds . '-0001', 'by' => $by,
                            'note' => $cnt['diff'] === 0 ? 'ตรงกับระบบ' : 'นับผิดครั้งก่อน');
        }
    }
    return $rows;
}

function move_cmp($a, $b)
{
    if ($a['ts'] === $b['ts']) {
        $sa = isset($a['seq']) ? $a['seq'] : -1;
        $sb = isset($b['seq']) ? $b['seq'] : -1;
        return ($sa === $sb) ? 0 : (($sa < $sb) ? -1 : 1);
    }
    return ($a['ts'] < $b['ts']) ? -1 : 1;
}

/**
 * ความเคลื่อนไหวทั้งหมดของสินค้าหนึ่งตัว เรียงเก่า → ใหม่ พร้อม 'bal' = คงเหลือหลังรายการนั้น
 * คำนวณย้อนจากยอดปัจจุบัน — ถ้ารายการสมมติทำให้ยอดก่อนหน้าติดลบ จะถูกตัดทิ้ง
 */
function move_rows($code, $p)
{
    $now  = product_qty($p, $code);
    $real = move_today_rows($code, $p['sku']);
    usort($real, 'move_cmp');

    /* ยอดตอนต้นวันนี้ = ยอดตอนนี้ − ทุกอย่างที่ขยับวันนี้ */
    $startToday = $now;
    foreach ($real as $r) {
        $startToday -= $r['delta'];
    }

    /* ข้อมูลย้อนหลัง: เดินถอยหลังจากต้นวันนี้ */
    $seed = move_seed_rows($code, $p);
    usort($seed, 'move_cmp');
    $keep = array();
    $bal  = $startToday;
    for ($i = count($seed) - 1; $i >= 0; $i--) {
        $before = $bal - $seed[$i]['delta'];
        if ($before < 0) {
            continue;                                        // ทำให้ติดลบ → ตัดทิ้ง
        }
        $seed[$i]['bal'] = $bal;
        $keep[] = $seed[$i];
        $bal = $before;
    }
    $keep = array_reverse($keep);

    /* วันนี้: เดินไปข้างหน้าจากต้นวัน */
    $bal = $startToday;
    foreach ($real as $i => $r) {
        $bal += $r['delta'];
        $real[$i]['bal'] = $bal;
    }
    return array_merge($keep, $real);
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

function move_qs($q, $cat, $sku, $period)
{
    $a = array();
    if ($q !== '')                              { $a[] = 'q='   . rawurlencode($q); }
    if ($cat !== '')                            { $a[] = 'cat=' . rawurlencode($cat); }
    if ($sku !== '')                            { $a[] = 'sku=' . rawurlencode($sku); }
    if ($period !== '' && $period !== '7')      { $a[] = 'p='   . rawurlencode($period); }
    return $a ? '?' . implode('&', $a) : '';
}
