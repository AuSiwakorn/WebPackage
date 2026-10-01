<?php
/* ==========================================================
   AOSTOCK DEMO — แก้ / ยกเลิกเอกสารย้อนหลัง (สิทธิ์เสริม backdate)
   ----------------------------------------------------------
   ใช้กับเอกสารคลัง 3 ชนิด: ใบรับเข้า (RC) · ใบเบิก/ตัดออก (IS) · ใบตรวจนับ (AD)
   บิลขายของวันก่อนไม่ยกเลิกย้อนหลัง — ใช้ "รับคืนสินค้า" แทน (เงินต้องคืนจากลิ้นชักวันนี้)

   กติกา
   - พนักงาน: ต้องมีสิทธิ์ backdate · ย้อนได้ไม่เกิน backdate_days(สาขา)
              ใบของคนอื่นต้องมีสิทธิ์ void_others เพิ่มด้วย
   - ผู้ดูแล: ย้อนได้ทุกวันที่ระบบมีข้อมูล
   - ต้องกรอกเหตุผลเสมอ · ใบเดิมไม่ถูกลบ ขึ้นว่า "ยกเลิกย้อนหลัง" พร้อมชื่อคนยกเลิก
   - สต๊อกถูกปรับ "วันนี้" (ไม่ย้อนไปแก้ยอดของวันเก่า) และลงประวัติของวันนี้
   - แก้ไข = ยกเลิกใบเดิม แล้วดึงรายการมาเป็นใบใหม่ของวันนี้ ให้แก้แล้วบันทึก

   ยังไม่มีฐานข้อมูล:
     เอกสารวันก่อน   สร้างจากข้อมูลสมมติที่คงที่ (past_docs)
     สถานะยกเลิก     $_SESSION['past_void'][ สาขา ][ เลขเอกสาร ]
   ระบบจริง: UPDATE xxx_doc SET status='void' + INSERT stock_move (วันนี้) อ้าง ref_id ใบเดิม

   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/data.php';
require_once dirname(__FILE__) . '/stock.php';
require_once dirname(__FILE__) . '/issue.php';
require_once dirname(__FILE__) . '/adjust.php';
require_once dirname(__FILE__) . '/activity.php';
require_once dirname(__FILE__) . '/return.php';     // past_bills(), return_lookback_days()

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
 * เอกสารคลังของวันก่อน (ข้อมูลสมมติที่คงที่)
 * พนักงานที่ประจำสาขานั้นในวันนั้นแต่ละคนมีเอกสารบ้างไม่มีบ้าง
 */
function past_docs($code, $ts)
{
    static $cache = array();
    $day = date('Ymd', $ts);
    $ck  = $code . '|' . $day;
    if (isset($cache[$ck])) {
        return past_apply_void($code, $cache[$ck]);
    }
    $out = array();
    if ((int) date('N', $ts) === 7) {                   // อาทิตย์ปิดร้าน
        $cache[$ck] = $out;
        return $out;
    }

    $prods  = demo_products();
    $np     = count($prods);
    $iss    = array('use', 'damaged', 'expired', 'lost', 'return');
    $adjR   = array('miscount', 'unlogged');      // ยอดขาด · ถ้ารวมแล้วเกินใช้ found
    $notes  = array('lost' => 'หาไม่พบหลังจัดชั้นวางใหม่', 'branch' => 'ส่งไปสาขาบางนา');
    $seq    = array('RC' => 0, 'IS' => 0, 'AD' => 0);
    $all    = demo_users_all();

    foreach ($all as $un => $u) {
        if ($u['role'] !== 'staff' || user_branch_on($u, $ts) !== $code) {
            continue;
        }
        $s = abs(crc32($un . '|doc|' . $day));

        $make = function ($kind, $salt) use ($prods, $np, $s, $un, $u, $day, $code) {
            $t     = abs(crc32($un . '|' . $kind . '|' . $day . '|' . $salt));
            $n     = 1 + ($t % 3);
            $lines = array();
            $used  = array();
            for ($j = 0; $j < $n; $j++) {
                $p = $prods[(($t >> 4) + $j * 5) % $np];
                if (isset($used[$p['sku']])) {
                    continue;
                }
                $used[$p['sku']] = true;
                $lines[] = array('p' => $p, 't' => $t >> ($j + 2));
            }
            $h = 9 + (($t >> 7) % 9);
            return array(
                'lines' => $lines,
                'time'  => str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad(($t >> 11) % 60, 2, '0', STR_PAD_LEFT),
                't'     => $t,
                'base'  => array('date' => $day, 'branch' => $code, 'by' => $u['name'], 'by_user' => $un),
            );
        };

        if ($s % 2 === 0) {                              // ใบรับเข้า
            $m = $make('RC', 1);
            $lines = array();
            $qty   = 0;
            foreach ($m['lines'] as $l) {
                $q = 6 + ($l['t'] % 19);
                $lines[] = array('sku' => $l['p']['sku'], 'name' => $l['p']['name'], 'unit' => $l['p']['unit'], 'qty' => $q);
                $qty += $q;
            }
            $out[] = array_merge($m['base'], array('kind' => 'RC', 'time' => $m['time'], 'lines' => $lines,
                     'items' => count($lines), 'qty' => $qty, 'ref' => 'INV-' . (4100 + ($m['t'] % 800)),
                     'reason' => '', 'note' => ''));
        }
        if ((($s >> 2) % 3) !== 1) {                     // ใบเบิก / ตัดออก
            $m   = $make('IS', 2);
            $why = $iss[($m['t'] >> 3) % count($iss)];
            $lines = array();
            $qty   = 0;
            foreach ($m['lines'] as $l) {
                $q = 1 + ($l['t'] % 3);
                $lines[] = array('sku' => $l['p']['sku'], 'name' => $l['p']['name'], 'unit' => $l['p']['unit'], 'qty' => $q);
                $qty += $q;
            }
            $out[] = array_merge($m['base'], array('kind' => 'IS', 'time' => $m['time'], 'lines' => $lines,
                     'items' => count($lines), 'qty' => $qty, 'ref' => '',
                     'reason' => $why, 'note' => isset($notes[$why]) ? $notes[$why] : ''));
        }
        if ((($s >> 4) % 2) === 0) {                     // ใบตรวจนับ
            $m = $make('AD', 3);
            $lines = array();
            $diffs = 0;
            $net   = 0;
            foreach ($m['lines'] as $l) {
                $h    = abs(crc32($l['p']['sku'] . '|cnt|' . $day));
                $have = 4 + ($h % 30);
                $d    = array(-2, -1, -1, 0, 0, 1);
                $d    = $d[($h >> 5) % 6];                     // ส่วนใหญ่ขาดเล็กน้อย บางตัวตรง บางตัวเกิน
                $net += $d;
                $lines[] = array('sku' => $l['p']['sku'], 'name' => $l['p']['name'], 'unit' => $l['p']['unit'],
                                 'have' => $have, 'counted' => max(0, $have + $d), 'diff' => max(0, $have + $d) - $have,
                                 'qty' => abs($d));
                $diffs += abs($d);
            }
            $out[] = array_merge($m['base'], array('kind' => 'AD', 'time' => $m['time'], 'lines' => $lines,
                     'items' => count($lines), 'qty' => $diffs, 'ref' => '',
                     'reason' => $net > 0 ? 'found' : $adjR[($m['t'] >> 5) % 2], 'note' => ''));
        }
    }

    usort($out, 'past_cmp_time');
    foreach ($out as $i => $d) {
        $seq[$d['kind']]++;
        $out[$i]['no'] = $d['kind'] . '-' . substr($day, 2) . '-' . str_pad($seq[$d['kind']], 4, '0', STR_PAD_LEFT);
    }
    $cache[$ck] = $out;
    return past_apply_void($code, $out);
}

function past_cmp_time($a, $b)
{
    return strcmp($a['time'], $b['time']);
}

/** ใส่สถานะ "ยกเลิกย้อนหลัง" ที่เก็บไว้ใน session */
function past_apply_void($code, $docs)
{
    foreach ($docs as $i => $d) {
        if (isset($_SESSION['past_void'][$code][$d['no']])) {
            $docs[$i] = array_merge($d, $_SESSION['past_void'][$code][$d['no']], array('void' => true));
        }
    }
    return $docs;
}

/** หาเอกสารจากเลขที่ (XX-YYMMDD-NNNN) */
function past_doc($code, $no)
{
    if (!preg_match('/^(RC|IS|AD)-(\d{6})-\d{4}$/', $no, $m)) {
        return null;
    }
    $ts = strtotime('20' . $m[2]);
    if ($ts === false) {
        return null;
    }
    foreach (past_docs($code, $ts) as $d) {
        if ($d['no'] === $no) {
            return $d;
        }
    }
    return null;
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

/** สต๊อกที่ต้องปรับวันนี้เมื่อยกเลิกใบนี้: array( SKU => +/- จำนวน ) */
function past_reverse_delta($doc)
{
    $out = array();
    foreach ($doc['lines'] as $l) {
        if ($doc['kind'] === 'RC') {
            $out[$l['sku']] = -(int) $l['qty'];          // ถอนของที่รับเข้า
        } elseif ($doc['kind'] === 'IS') {
            $out[$l['sku']] = (int) $l['qty'];           // คืนของที่ตัดออก
        } else {
            $out[$l['sku']] = -(int) $l['diff'];         // ถอยส่วนต่างจากการนับ
        }
    }
    return $out;
}

/**
 * ยกเลิก (หรือยกเลิกเพื่อแก้ไข) เอกสารย้อนหลัง
 * คืนค่า array('doc' => ใบเดิม, 'page' => หน้าที่ต้องไปแก้ต่อ) หรือ array('error' => ข้อความ)
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
    if (trim($reason) === '') {
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

    /* ถอยแล้วสต๊อกต้องไม่ติดลบ */
    $delta = past_reverse_delta($doc);
    $short = array();
    foreach ($delta as $sku => $d) {
        $p = product_by_sku($sku);
        if ($p !== null && $d < 0 && product_qty($p, $code) + $d < 0) {
            $short[] = $p['name'] . ' (เหลือ ' . number_format(product_qty($p, $code)) . ')';
        }
    }
    if ($short) {
        return array('error' => 'ยกเลิกไม่ได้ เพราะของบางส่วนถูกขาย/เบิกไปแล้ว — ' . implode(' · ', $short)
                              . ' · กรณีนี้ให้ใช้การตรวจนับ/ปรับยอดแทน');
    }
    foreach ($delta as $sku => $d) {
        if ($d !== 0) {
            stock_adj_add($code, $sku, $d);
        }
    }

    $_SESSION['past_void'][$code][$no] = array(
        'void_at'     => date('j/n') . ' ' . date('H:i'),
        'void_by'     => $user['name'],
        'void_user'   => $user['username'],
        'void_reason' => trim($reason),
        'void_mode'   => $redo ? 'edit' : 'void',
    );

    $names = array();
    foreach ($doc['lines'] as $l) {
        $names[] = $l['name'] . ($doc['kind'] === 'AD' ? ' (ส่วนต่าง ' . ($l['diff'] > 0 ? '+' : '') . $l['diff'] . ')' : ' ×' . $l['qty']);
    }
    $detail = array(
        'ใบเดิม'       => $doc['no'] . ' · ' . thai_date_full(strtotime($doc['date'])) . ' ' . $doc['time'] . ' น. โดย ' . $doc['by'],
        'รายการ'       => implode(' · ', $names),
        'ย้อนหลัง'      => past_age($doc) . ' วัน',
        'สต๊อก'        => 'ปรับยอดวันนี้ให้ตรงกับการยกเลิก',
        'เหตุผล'       => trim($reason),
    );
    if ($redo) {
        $detail['การทำต่อ'] = 'ดึงรายการมาเป็นใบใหม่ของวันนี้เพื่อแก้แล้วบันทึก';
    }
    log_add($code, $t['void'], $user,
            ($redo ? 'ยกเลิกเพื่อแก้ไขย้อนหลัง ' : 'ยกเลิกย้อนหลัง ') . $t['label'] . ' ' . $doc['no'],
            $detail, null, $doc['no']);

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
    return array('doc' => $doc, 'page' => $t['page']);
}
