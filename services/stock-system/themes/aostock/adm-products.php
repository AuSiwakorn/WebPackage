<?php
/**
 * FILE: themes/aostock/adm-products.php
 * ROLE: [ผู้ดูแล] สินค้าในสต๊อก เทียบทุกสาขา
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_product, ao_stock_category, ao_stock_balance · ao_stock_sale / sale_item + receive / receive_item (ยอดขาย / รับเข้า 30 วัน) — ผ่าน api.php
 *         เขียน: ao_stock_receive / receive_item / move / balance / log (เพิ่มยอดเข้าสาขา · Stock_saveAdminReceive)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] อ่าน / เขียนข้อมูลจากตาราง ao_stock_* ผ่าน api.php (ช่วงที่ 5–9)
 *   - [x] ช่วงที่ 10: แก้ File Header ให้ตรงกับระบบจริง
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 *   - [x] ผลทดสอบ 7 ต.ค. (C11): ปุ่ม “แก้ไข” ท้ายทุกแถว (เดิมต้องรู้ว่าแตะชื่อสินค้า)
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 4): ตารางครั้งละ 60 แถว + ปุ่ม “แสดงเพิ่ม” — htmx ?from= ส่งกลับแค่แถวชุดถัดไป (เดิมวาดทุกแถว สินค้า 3,000 ตัว = 4.4 MB)
 *   - [x] 21 ต.ค. (ผลทดสอบ · ผู้ใช้เลือก): สถานะรวมนับเฉพาะสาขาที่เคยมีสินค้านั้น (มีแถวยอดคงเหลือ) — สาขาที่ไม่เคยมีแสดง “—” ไม่ทำให้ขึ้น “หมดแล้ว”
 *   - [x] 22 ต.ค. (สินค้าของสาขา): ป้าย “สินค้าสาขา…” ใต้ชื่อ · สาขาอื่นของสินค้าสาขาแสดง “—” (ไม่ใช่สินค้าของสาขานั้น)
 *   - [x] รูปสินค้าหน้าชื่อ (thumb_html แบบหน้าสินค้าของพนักงาน — ดู CHANGELOG 2 พ.ย.)
 *   - [x] แตะช่องยอดของสาขา → หน้าต่างเพิ่มยอดเข้าสาขา #rc-modal (ดู CHANGELOG 3 พ.ย.)
 *   - [x] ช่องค้นหาย้ายมาไว้หัวการ์ด “ยอดคงเหลือรายสินค้า” (#stock-list) · แท็บ / หัวคอลัมน์เลื่อนมาที่ตาราง (3 พ.ย.)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   [ผู้ดูแล] สินค้าในสต๊อก เทียบทุกสาขา
   ----------------------------------------------------------
   แถวละ 1 สินค้า · คอลัมน์ละ 1 สาขา (เฉพาะสาขาที่เปิดใช้งาน) + รวมทุกสาขา
   สีของช่องบอกสถานะของสาขานั้น: หมด / ใกล้หมด (เหลือไม่เกินจำนวนที่ระบบจะแจ้งเตือนสินค้าใกล้หมด) / พอใช้
   ผู้ดูแลเพิ่ม / แก้สินค้าได้ (ปุ่ม "เพิ่มสินค้า" / ปุ่ม "แก้ไข" ท้ายแถว หรือแตะชื่อสินค้า → adm-product-edit.php)
   แตะช่องยอดของสาขา = เพิ่มยอดเข้าสาขานั้น (ออกเป็นใบรับเข้า 1 ใบ) · ลดยอด / เบิก / ตรวจนับ ยังเป็นงานของพนักงาน
     กดไม่ได้: สินค้าของสาขาอื่น (นำเข้าได้เฉพาะสาขาเจ้าของ) · สินค้าเลิกขาย
     เพิ่มแล้ว: กล่องแจ้งผลมีปุ่ม “ยกเลิกใบนี้” (act=rcvoid · เฉพาะใบของวันนี้ที่ผู้ดูแลคนนั้นทำ)

   ตัวกรอง (GET)
     q    = ค้นชื่อสินค้า / SKU (ช่องอยู่หัวการ์ดยอดคงเหลือ) · cat = หมวด
     st   = '' | low | out  (สินค้าที่มีอย่างน้อย 1 สาขาใกล้หมด / หมด) | off (เลิกขาย)
     b    = รหัสสาขาที่ใช้กรองสถานะและเรียงจำนวน ('' = ดูทุกสาขารวมกัน)
     sort = urgent | name | qty | value | sold | cover · dir = desc | asc
   ตัวเลขประกอบ (30 วันล่าสุด): รับเข้าล่าสุด · ยอดขายรวมบนกล่องสรุป
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

$br    = branches_active();
$g     = function ($k, $def = '') { return (isset($_GET[$k]) && is_string($_GET[$k])) ? trim($_GET[$k]) : $def; };
$fQ    = (string) substr($g('q'), 0, 120);
$cats  = product_cats();
$fCat  = in_array($g('cat'), $cats, true) ? $g('cat') : '';
$fSt   = in_array($g('st'), array('low', 'out', 'off'), true) ? $g('st') : '';
$fB    = isset($br[$g('b')]) ? $g('b') : '';
$fSort = in_array($g('sort'), array('urgent', 'name', 'qty', 'value', 'sold', 'cover'), true) ? $g('sort') : 'urgent';
$fDir  = $g('dir') === 'asc' ? 'asc' : 'desc';

$pq = function ($chg) use ($fQ, $fCat, $fSt, $fB, $fSort, $fDir) {
    $q = array_merge(array('q' => $fQ, 'cat' => $fCat, 'st' => $fSt, 'b' => $fB, 'sort' => $fSort, 'dir' => $fDir), $chg);
    foreach (array('q' => '', 'cat' => '', 'st' => '', 'b' => '', 'sort' => 'urgent', 'dir' => 'desc') as $k => $def) {
        if ((string) $q[$k] === $def) {
            unset($q[$k]);
        }
    }
    return 'adm-products.php' . ($q ? '?' . http_build_query($q) : '');
};

/* ---------- เพิ่มยอดเข้าสาขา (หน้าต่าง #rc-modal) ----------
   ผ่าน = กลับมาที่แถวเดิม + กล่องแจ้งผลมีปุ่ม “ยกเลิกใบนี้” (ขึ้นครั้งเดียว) · ไม่ผ่าน = เปิดหน้าต่างค้างไว้พร้อมค่าที่กรอก */
$rcErr  = '';
$rcOld  = null;
$rcVErr = '';                                                    // ยกเลิกใบที่เพิ่งเพิ่มไม่ผ่าน
$act    = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && is_string($_POST['act'])) ? $_POST['act'] : '';
$pv     = function ($k) { return (isset($_POST[$k]) && is_string($_POST[$k])) ? $_POST[$k] : ''; };
$back   = function ($pid, $n) use ($pq) {                        // กลับหน้าเดิม ตัวกรองเดิม · เคยกด “แสดงเพิ่ม” = โหลดให้ถึงแถวนั้น
    $go = $pq(array());
    if ($n > PRODUCT_PAGE_SIZE) {
        $go .= (strpos($go, '?') === false ? '?' : '&') . 'n=' . min(100000, $n);
    }
    header('Location: ' . url($go . '#p-' . (int) $pid));
    exit;
};
if ($act === 'recv') {
    $rcOld = array('sku' => $pv('sku'), 'b' => $pv('b'), 'qty' => $pv('qty'), 'ref' => $pv('ref'), 'note' => $pv('note'), 'n' => $pv('n'));
    if (!csrf_check($pv('csrf'))) {
        $rcErr = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } else {
        $d = Stock_saveAdminReceive($rcOld['b'], $user, $rcOld['sku'], $rcOld['qty'], $rcOld['ref'], $rcOld['note']);
        if (!is_array($d) || isset($d['error'])) {
            $rcErr = is_array($d) ? $d['error'] : 'บันทึกไม่สำเร็จ ลองใหม่อีกครั้ง';
        } else {
            $p = product_by_sku($rcOld['sku']);
            $n = ctype_digit($rcOld['n']) ? (int) $rcOld['n'] : 0;   // ลำดับแถวที่แตะ
            $_SESSION['rc_done'] = array('b' => $rcOld['b'], 'no' => $d['no'], 'qty' => (int) $d['qty'], 'pid' => (int) $p['id'], 'n' => $n,
                'msg' => 'เพิ่ม “' . $p['name'] . '” +' . number_format((int) $d['qty']) . ' ' . $p['unit'] . ' เข้า' . branch_name($rcOld['b'])
                       . ' แล้ว · ใบรับเข้า ' . $d['no'] . ' · ตอนนี้มี ' . number_format(product_qty($p, $rcOld['b'])) . ' ' . $p['unit']);
            $back($p['id'], $n);
        }
    }
} elseif ($act === 'rcvoid') {
    /* ยกเลิกใบที่เพิ่งเพิ่ม — เฉพาะใบของวันนี้ที่ผู้ดูแลคนนี้ทำ (ใบวันก่อนยกเลิกจากประวัติของวันนั้น) */
    $b   = $pv('b');
    $doc = isset($br[$b]) ? receive_by_no($b, trim($pv('no'))) : null;
    $why = trim($pv('reason'));
    if (!csrf_check($pv('csrf'))) {
        $rcVErr = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } elseif ($doc === null || $doc['void'] || $doc['by_user'] !== $user['username']) {
        $rcVErr = 'ยกเลิกไม่ได้ — ไม่พบใบนี้ ใบถูกยกเลิกไปแล้ว หรือไม่ใช่ใบที่คุณเพิ่มวันนี้';
    } elseif ($why === '') {
        $rcVErr = 'ยังไม่ได้ยกเลิก — ต้องกรอกหมายเหตุว่าทำไมยกเลิก';
    } else {
        $v = receive_void($b, $doc['no'], $user, $why);
        if (isset($v['error'])) {
            $rcVErr = 'ยกเลิกไม่ได้ เพราะของบางส่วนถูกขายออกไปแล้ว — ' . implode(' · ', $v['items']) . ' · กรณีนี้ให้พนักงานตรวจนับสต๊อกแทน';
        } elseif ($v === null) {
            $rcVErr = 'ยกเลิกไม่สำเร็จ ลองใหม่อีกครั้ง';
        } else {
            $l = $doc['lines'][0];
            $p = product_by_sku($l['sku']);
            $_SESSION['flash'] = 'ยกเลิกใบรับเข้า ' . $doc['no'] . ' แล้ว — ถอน “' . $l['name'] . '” −' . number_format($doc['qty']) . ' ' . $l['unit']
                               . ' ออกจาก' . branch_name($b) . ($p !== null ? ' · ตอนนี้มี ' . number_format(product_qty($p, $b)) . ' ' . $l['unit'] : '');
            $back($l['pid'], ctype_digit($pv('n')) ? (int) $pv('n') : 0);
        }
    }
}
$rcDone = isset($_SESSION['rc_done']) ? $_SESSION['rc_done'] : null;   // กล่องแจ้งผลขึ้นครั้งเดียว
unset($_SESSION['rc_done']);

/* ---------- ตัวเลขประกอบ 30 วันล่าสุด ---------- */
$flowDays = 30;
$flow     = product_flow_stats(array_keys($br), $flowDays);

/* ---------- สร้างแถว ---------- */
$rank = array('out' => 0, 'low' => 1, 'ok' => 2, 'none' => 3);   // none = สาขานี้ไม่เคยมีสินค้านี้
$rows = array();
$sum  = array('skus' => 0, 'qty' => 0, 'value' => 0, 'sale' => 0, 'low' => 0, 'out' => 0, 'sold' => 0);
$perB = array();
foreach (array_keys($br) as $c) {
    $perB[$c] = array('qty' => 0, 'value' => 0, 'low' => 0, 'out' => 0);
}
foreach (products_list(true) as $p) {
    if ($p['active'] === ($fSt === 'off')) {               // เลิกขาย: แสดงเฉพาะแท็บ "เลิกขาย"
        continue;
    }
    if ($fCat !== '' && $p['cat'] !== $fCat) {
        continue;
    }
    if ($fQ !== '' && stripos($p['name'], $fQ) === false && stripos($p['sku'], $fQ) === false) {
        continue;
    }
    $cells = array();
    $tot   = 0;
    $worst = 'ok';
    $had   = 0;
    foreach (array_keys($br) as $c) {
        $q  = product_qty($p, $c);
        /* สาขาที่ไม่เคยมีสินค้านี้ (ไม่มีแถวยอดคงเหลือ — ไม่เคยรับเข้า / นับ) = 'none' ไม่นับเป็น “หมด”
           (21 ต.ค. ผู้ใช้เลือก — เดิมสาขาที่ไม่ได้ขายตัวนี้ เช่นสาขาทดสอบ ทำให้ขึ้น “หมดแล้ว” ทุกตัว) · เคยมีแล้วเหลือ 0 = หมดตามเดิม */
        $st = (isset($p['stock'][$c]) && Stock_isProductInBranch($p, $c)) ? branch_status($p, $c) : 'none';
        $cells[$c] = array('qty' => $q, 'st' => $st, 'add' => $p['active'] && Stock_isProductInBranch($p, $c));   // add = แตะเพิ่มยอดได้
        $tot += $q;
        if ($st === 'none') {
            continue;
        }
        $had++;
        if ($rank[$st] < $rank[$worst]) {
            $worst = $st;
        }
    }
    if ($had === 0) {
        $worst = 'out';                                     // ยังไม่มีสาขาไหนเคยมีของ = ไม่มีขายที่ไหนเลย
    }
    $focus = $fB !== '' ? $cells[$fB]['st'] : $worst;
    if ($fSt !== '' && $fSt !== 'off' && !($fSt === 'low' ? ($focus === 'low' || $focus === 'out') : $focus === 'out')) {
        continue;
    }
    foreach ($cells as $c => $x) {
        $perB[$c]['qty']   += $x['qty'];
        $perB[$c]['value'] += $x['qty'] * $p['cost'];
        if ($x['st'] === 'low' || $x['st'] === 'out') {
            $perB[$c][$x['st']]++;
        }
    }
    $sum['skus']++;
    $sum['qty']   += $tot;
    $sum['value'] += $tot * $p['cost'];
    $sum['sale']  += $tot * $p['price'];
    if ($worst !== 'ok') {
        $sum[$worst]++;
    }
    /* ขายได้ / รับเข้าล่าสุด ของสาขาที่เลือก หรือรวมทุกสาขา */
    $sold = 0;
    $last = '';
    foreach (array_keys($br) as $c) {
        if ($fB !== '' && $c !== $fB) {
            continue;
        }
        $sold += isset($flow['sold'][$p['sku']][$c]) ? $flow['sold'][$p['sku']][$c] : 0;
        if (isset($flow['recv'][$p['sku']][$c]) && $flow['recv'][$p['sku']][$c] > $last) {
            $last = $flow['recv'][$p['sku']][$c];
        }
    }
    $qtyNow = $fB !== '' ? $cells[$fB]['qty'] : $tot;
    $cover  = $sold > 0 ? $qtyNow / ($sold / $flowDays) : null;    // null = ไม่มีขายในช่วงนี้
    $sum['sold'] += $sold;
    $rows[] = array('p' => $p, 'cells' => $cells, 'tot' => $tot, 'worst' => $worst, 'focus' => $focus,
                    'sortqty' => $qtyNow, 'value' => $qtyNow * $p['cost'],
                    'sold' => $sold, 'cover' => $cover, 'last' => $last);
}

$dir = $fDir === 'asc' ? 1 : -1;
usort($rows, function ($a, $b) use ($fSort, $dir, $rank) {
    if ($fSort === 'name') {
        return strcmp($a['p']['name'], $b['p']['name']) * -$dir;      // desc = ก→ฮ ตามค่าเริ่มต้นของหัวคอลัมน์
    }
    if ($fSort === 'qty' && $a['sortqty'] !== $b['sortqty']) {
        return ($a['sortqty'] < $b['sortqty'] ? -1 : 1) * $dir;
    }
    if ($fSort === 'value' && $a['value'] != $b['value']) {
        return ($a['value'] < $b['value'] ? -1 : 1) * $dir;
    }
    if ($fSort === 'sold' && $a['sold'] !== $b['sold']) {
        return ($a['sold'] < $b['sold'] ? -1 : 1) * $dir;
    }
    if ($fSort === 'cover') {                                         // ไม่มีขาย = ขายได้อีกนานมาก
        $ca = $a['cover'] === null ? 99999 : $a['cover'];
        $cb = $b['cover'] === null ? 99999 : $b['cover'];
        if ($ca != $cb) {
            return ($ca < $cb ? -1 : 1) * $dir;
        }
    }
    if ($rank[$a['focus']] !== $rank[$b['focus']]) {                     // urgent: หมด → ใกล้หมด → พอใช้
        return $rank[$a['focus']] - $rank[$b['focus']];
    }
    return $a['sortqty'] - $b['sortqty'];
});

$th = function ($k, $label, $cls = '') use ($fSort, $fDir, $pq) {
    $on   = ($fSort === $k);
    $next = ($on && $fDir === 'desc') ? 'asc' : 'desc';
    $mark = $on ? ($fDir === 'desc' ? ' ↓' : ' ↑') : '';
    return '<th class="' . $cls . '"><a class="th-sort' . ($on ? ' on' : '') . '" href="' . e($pq(array('sort' => $k, 'dir' => $next))) . '#stock-list">'
         . e($label) . $mark . '</a></th>';
};

/* แถวสินค้าหนึ่งแถว — ใช้ทั้งตอนวาดตาราง และตอนกด “แสดงเพิ่ม” */
$row = function ($r) use ($br, $flowDays, $fB) {
    $p = $r['p'];
    $tip = ($p['owner'] === '') ? '' : (($p['owner'][0] === '#') ? 'สินค้าของสาขาที่ไม่มีแล้ว — ย้ายเป็นสินค้าส่วนกลางก่อน (ปุ่ม “แก้ไข”) จึงจะเพิ่มยอดได้'
                                                                  : Stock_formatProductOwner($p) . ' — เพิ่มยอดได้เฉพาะสาขานั้น');   // ช่องที่แตะไม่ได้ ?>
            <tr id="p-<?php echo (int) $p['id'] ?>" data-sku="<?php echo e($p['sku']) ?>" data-name="<?php echo e($p['name']) ?>" data-unit="<?php echo e($p['unit']) ?>"<?php echo $p['active'] ? '' : ' class="prod-off"' ?>>
              <td data-label="สินค้า">
                <span class="pcell">
                  <?php echo thumb_html($p) /* รูปสินค้า · ไม่มีรูป = ไอคอนตามหมวด */ ?>
                  <span class="pcell-t">
                    <b class="hist-t"><a href="adm-product-edit.php?sku=<?php echo e(rawurlencode($p['sku'])) ?>" title="แก้ไขสินค้า"><?php echo e($p['name']) ?></a></b>
                    <small class="hist-n"><?php echo e($p['sku']) ?> · <?php echo e($p['cat']) ?><?php echo $p['active'] ? '' : ' · เลิกขาย' ?><?php if ($p['owner'] !== '') { ?> · <span class="own-tag"><?php echo e(Stock_formatProductOwner($p)) ?></span><?php } ?></small>
                  </span>
                </span>
              </td>
              <?php foreach ($r['cells'] as $c => $x) { ?>
                <td data-label="<?php echo e($br[$c]['short']) ?>" class="r num nowrap"><?php if ($x['add']) { /* แตะ = หน้าต่างเพิ่มยอดเข้าสาขา (#rc-modal) */ ?><button type="button" class="stk stk-<?php echo e($x['st']) ?> stk-add" data-b="<?php echo e($c) ?>" data-q="<?php echo (int) $x['qty'] ?>" title="<?php echo $x['st'] === 'none' ? 'ยังไม่มีในสาขานี้ · ' : '' ?>แตะเพื่อเพิ่มยอด"><?php echo $x['st'] === 'none' ? '—' : number_format($x['qty']) ?></button><?php } else { ?><span class="stk stk-<?php echo e($x['st']) ?>"<?php echo $p['active'] ? ' title="' . e($tip) . '"' : ($x['st'] === 'none' ? ' title="สาขานี้ไม่มีสินค้านี้"' : '') ?>><?php echo $x['st'] === 'none' ? '—' : number_format($x['qty']) ?></span><?php } ?></td>
              <?php } ?>
              <td data-label="<?php echo $fB !== '' ? 'จำนวน (' . e($br[$fB]['short']) . ')' : 'รวมทุกสาขา' ?>" class="r num nowrap"><b><?php echo number_format($r['sortqty']) ?></b> <small><?php echo e($p['unit']) ?></small></td>
              <td data-label="รับของเข้าล่าสุด" class="nowrap"><?php echo $r['last'] !== '' ? e(thai_day_month(strtotime($r['last']))) . ' <small>(' . (int) round((strtotime(date('Y-m-d')) - strtotime($r['last'])) / 86400) . ' วันก่อน)</small>' : '<small>ไม่ได้รับของเข้าใน ' . $flowDays . ' วัน</small>' ?></td>
              <td data-label="มูลค่า (ราคาทุน)" class="r num nowrap"><?php echo e(money2($r['value'])) ?></td>
              <td data-label="สถานะ"><?php if ($r['focus'] === 'none') { ?><span class="bdg">ไม่มีในสาขานี้</span><?php } else { ?><span class="bdg <?php echo $r['focus'] === 'out' ? 'bdg-out' : ($r['focus'] === 'low' ? 'bdg-adj' : 'bdg-ok') ?>"><?php echo e(stock_label($r['focus'])) ?></span><?php } ?></td>
              <td class="r nowrap"><a class="btn btn-ghost btn-sm" href="adm-product-edit.php?sku=<?php echo e(rawurlencode($p['sku'])) ?>" aria-label="แก้ไข <?php echo e($p['name']) ?>">แก้ไข</a></td>
            </tr>
<?php
};

/* ตารางครั้งละ 60 แถว + ปุ่ม “แสดงเพิ่ม” (งานความเร็ว ช่วง 4) — htmx ?from= ส่งกลับแค่แถวชุดถัดไป */
list($from, $end) = Stock_getListRange();
$cols = count($br) + 6;
if (isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true' && isset($_GET['from'])) {
    foreach (array_slice($rows, $from, $end - $from) as $r) {
        $row($r);
    }
    Stock_renderMoreButton($end, count($rows), $pq(array()), $cols);
    exit;
}

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = 'สินค้าในสต๊อก';
$PAGE_SUB       = 'เทียบยอดคงเหลือทุกสาขา ณ ตอนนี้';
$NAV_ACTIVE     = 'adm-products.php';
$CONFIRM_MODAL  = true;                  // ปุ่ม “ยกเลิกใบนี้” ต้องยืนยัน + กรอกหมายเหตุ (footer.php)
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($rcVErr !== '') { ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($rcVErr) ?></span></div>
<?php } elseif ($rcDone !== null) { /* เพิ่งเพิ่มยอด — ผิดยกเลิกได้ทันที (ใบของวันนี้ ผู้ดูแลยกเลิกจากประวัติไม่ได้) */ ?>
  <div class="alert alert-ok rc-done" role="status">
    <svg class="ico"><use href="#i-check"/></svg>
    <span><?php echo e($rcDone['msg']) ?></span>
    <div class="alert-act">
      <form method="post" action="<?php echo e($pq(array())) ?>" data-confirm="void" data-doc="recv" data-bill="<?php echo e($rcDone['no']) ?>" data-qty="<?php echo (int) $rcDone['qty'] ?>" data-items="1">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="act" value="rcvoid">
        <input type="hidden" name="b" value="<?php echo e($rcDone['b']) ?>">
        <input type="hidden" name="no" value="<?php echo e($rcDone['no']) ?>">
        <input type="hidden" name="n" value="<?php echo (int) $rcDone['n'] ?>">
        <input class="reason-fb" type="text" name="reason" value="" placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุที่ยกเลิก <?php echo e($rcDone['no']) ?>">
        <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-ban"/></svg> ยกเลิกใบนี้</button>
      </form>
    </div>
  </div>
<?php } ?>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter hist-filter">
  <form method="get" action="adm-products.php" class="acct-row">
    <?php if ($fQ !== '') { ?><input type="hidden" name="q" value="<?php echo e($fQ) ?>"><?php } /* ช่องค้นหาอยู่หัวการ์ดยอดคงเหลือ (#stock-list) — เปลี่ยนหมวด / สาขาแล้วคำค้นไม่หาย */ ?>
    <label class="sr-only" for="pc">หมวด</label>
    <select class="input acct-branch" id="pc" name="cat" onchange="this.form.submit()">
      <option value="">ทุกหมวด</option>
      <?php foreach ($cats as $c) { ?>
        <option value="<?php echo e($c) ?>" <?php echo $fCat === $c ? 'selected' : '' ?>><?php echo e($c) ?></option>
      <?php } ?>
    </select>
    <label class="sr-only" for="pb">ดูสาขา</label>
    <select class="input acct-branch" id="pb" name="b" onchange="this.form.submit()">
      <option value="">ดูทุกสาขารวมกัน</option>
      <?php foreach ($br as $c => $x) { ?>
        <option value="<?php echo e($c) ?>" <?php echo $fB === $c ? 'selected' : '' ?>>ดูเฉพาะ<?php echo e($x['name']) ?></option>
      <?php } ?>
    </select>
    <?php if ($fSt !== '') { ?><input type="hidden" name="st" value="<?php echo e($fSt) ?>"><?php } ?>
    <?php if ($fSort !== 'urgent') { ?><input type="hidden" name="sort" value="<?php echo e($fSort) ?>"><input type="hidden" name="dir" value="<?php echo e($fDir) ?>"><?php } ?>
  </form>
</section>

<!-- ==================== สรุป ==================== -->
<section class="mini num adm-kpi" aria-label="สรุปสต๊อก">
  <div class="m"><div class="lb">สินค้า</div><div class="nm"><?php echo number_format($sum['skus']) ?></div>
    <div class="sb">คงเหลือ <?php echo number_format($sum['qty']) ?> ชิ้น · ขายไป <?php echo number_format($sum['sold']) ?> ชิ้นใน <?php echo $flowDays ?> วัน</div></div>
  <div class="m"><div class="lb">มูลค่าของในร้าน (ราคาทุน)</div><div class="nm"><?php echo e(money2($sum['value'])) ?></div>
    <div class="sb">ถ้าขายหมดได้ <?php echo e(money2($sum['sale'])) ?> บาท</div></div>
  <div class="m"><div class="lb">ต้องเติมของ</div><div class="nm"><?php echo number_format($sum['out'] + $sum['low']) ?></div>
    <div class="sb">หมด <?php echo number_format($sum['out']) ?> · ใกล้หมด <?php echo number_format($sum['low']) ?></div></div>
</section>

<!-- ==================== แยกสาขา ==================== -->
<section class="card">
  <div class="card-head"><div><h2>แยกตามสาขา</h2><span class="sub">ตามหมวด / สาขา / คำค้นที่เลือก</span></div></div>
  <div class="tbl-wrap">
    <table class="tbl num">
      <thead><tr><th>สาขา</th><th class="r">จำนวน</th><th class="r">มูลค่า (ราคาทุน)</th><th class="r">ใกล้หมด</th><th class="r">หมด</th></tr></thead>
      <tbody>
        <?php foreach ($perB as $c => $x) { ?>
          <tr>
            <td data-label="สาขา"><a href="<?php echo e($pq(array('b' => $c))) ?>"><?php echo e($br[$c]['name']) ?></a></td>
            <td data-label="จำนวน" class="r"><?php echo number_format($x['qty']) ?></td>
            <td data-label="มูลค่า (ราคาทุน)" class="r"><?php echo e(money2($x['value'])) ?></td>
            <td data-label="ใกล้หมด" class="r"><?php echo $x['low'] ? '<span class="bdg bdg-adj">' . number_format($x['low']) . '</span>' : '—' ?></td>
            <td data-label="หมด" class="r"><?php echo $x['out'] ? '<span class="bdg bdg-out">' . number_format($x['out']) . '</span>' : '—' ?></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
</section>

<!-- ==================== รายการสินค้า ==================== -->
<section class="card" id="stock-list">
  <div class="card-head">
    <div>
      <h2>ยอดคงเหลือรายสินค้า</h2>
      <span class="sub">สีแดง = หมด · สีเหลือง = ใกล้หมด · — = สาขานั้นไม่มีสินค้านี้ · <?php echo $fB !== '' ? 'สถานะและจำนวนเป็นของ' . e($br[$fB]['name']) : 'สถานะดูจากสาขาที่ของเหลือน้อยที่สุด' ?></span>
    </div>
    <div class="segs head-tabs">
      <a class="seg<?php echo $fSt === '' ? ' on' : '' ?>" href="<?php echo e($pq(array('st' => ''))) ?>#stock-list">ทั้งหมด</a>
      <a class="seg<?php echo $fSt === 'low' ? ' on' : '' ?>" href="<?php echo e($pq(array('st' => 'low'))) ?>#stock-list">ต้องเติมของ</a>
      <a class="seg<?php echo $fSt === 'out' ? ' on' : '' ?>" href="<?php echo e($pq(array('st' => 'out'))) ?>#stock-list">หมดแล้ว</a>
      <a class="seg<?php echo $fSt === 'off' ? ' on' : '' ?>" href="<?php echo e($pq(array('st' => 'off'))) ?>#stock-list">เลิกขาย</a>
    </div>
    <a class="btn btn-primary btn-sm" href="adm-product-edit.php"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มสินค้า</a>
  </div>
  <?php /* ค้นชื่อ / รหัสสินค้า — ย้ายจากการ์ดตัวกรองบนสุดมาไว้ติดตาราง (3 พ.ย.) · ค้นแล้วเลื่อนลงมาที่ตารางเลย */ ?>
  <form method="get" action="adm-products.php#stock-list" class="stock-q" role="search">
    <label class="sr-only" for="pq">ค้นชื่อหรือรหัสสินค้า</label>
    <input class="input" type="search" id="pq" name="q" value="<?php echo e($fQ) ?>" placeholder="ค้นชื่อหรือรหัสสินค้า" autocomplete="off">
    <?php foreach (array('cat' => $fCat, 'b' => $fB, 'st' => $fSt) as $k => $x) { if ($x !== '') { ?><input type="hidden" name="<?php echo $k ?>" value="<?php echo e($x) ?>"><?php } } ?>
    <?php if ($fSort !== 'urgent') { ?><input type="hidden" name="sort" value="<?php echo e($fSort) ?>"><input type="hidden" name="dir" value="<?php echo e($fDir) ?>"><?php } ?>
    <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-search"/></svg> ค้นหา</button>
    <?php if ($fQ !== '') { ?><a class="btn btn-ghost btn-sm" href="<?php echo e($pq(array('q' => ''))) ?>#stock-list"><svg class="ico"><use href="#i-x"/></svg> ล้างคำค้น</a><?php } ?>
  </form>
  <?php if (!$rows) { ?>
    <p class="empty"><svg class="ico"><use href="#i-boxes"/></svg><?php if ($fQ !== '') { ?>ไม่พบสินค้าที่ตรงกับ “<?php echo e($fQ) ?>”<?php echo ($fCat !== '' || $fSt !== '') ? ' ในหมวด / แท็บที่เลือก' : '' ?> · <a href="<?php echo e($pq(array('q' => ''))) ?>#stock-list">ล้างคำค้น</a><?php } else { ?>ไม่พบสินค้าตามตัวกรอง<?php } ?></p>
  <?php } else { ?>
    <div class="tbl-wrap">
      <table class="tbl hist-all adm-stock">
        <thead>
          <tr>
            <?php echo $th('name', 'สินค้า') ?>
            <?php foreach ($br as $c => $x) { ?><th class="r<?php echo $fB === $c ? ' is-focus' : '' ?>"><?php echo e($x['short']) ?></th><?php } ?>
            <?php echo $th('qty', $fB !== '' ? 'จำนวน (' . $br[$fB]['short'] . ')' : 'รวมทุกสาขา', 'r') ?>
            <th>รับของเข้าล่าสุด</th>
            <?php echo $th('value', 'มูลค่า (ราคาทุน)', 'r') ?>
            <?php echo $th('urgent', 'สถานะ') ?>
            <th><span class="sr-only">แก้ไข</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($rows, 0, $end) as $r) { $row($r); } ?>
          <?php Stock_renderMoreButton($end, count($rows), $pq(array()), $cols); ?>
        </tbody>
      </table>
    </div>
  <?php } ?>
</section>

<?php
/* หน้าต่างเพิ่มยอดเข้าสาขา — ใช้ร่วมทุกช่อง (สคริปต์ใส่สินค้า / สาขาของช่องที่แตะ) · บันทึกไม่ผ่าน = เปิดค้าง ค่าที่กรอกยังอยู่ */
$rcP    = ($rcOld !== null) ? product_by_sku($rcOld['sku']) : null;
$rcB    = ($rcOld !== null && isset($br[$rcOld['b']])) ? $rcOld['b'] : '';
$rcV    = function ($k) use ($rcOld) { return ($rcOld !== null) ? $rcOld[$k] : ''; };
$rcHave = ($rcP !== null && $rcB !== '') ? product_qty($rcP, $rcB) : 0;
$rcUnit = ($rcP !== null) ? $rcP['unit'] : 'ชิ้น';
?>
<dialog class="fm-modal rc-modal" id="rc-modal" aria-labelledby="rc-t" data-have="<?php echo (int) $rcHave ?>"<?php echo $rcErr !== '' ? ' data-fm-auto' : '' ?>>
  <div class="fm-head">
    <div><h2 id="rc-t">เพิ่มยอดเข้าสาขา</h2><span class="sub"><b id="rc-name"><?php echo $rcP ? e($rcP['name']) : '' ?></b> · SKU <span id="rc-sku"><?php echo $rcP ? e($rcP['sku']) : '' ?></span></span></div>
    <button type="button" class="icon-btn" data-fm-close aria-label="ปิด"><svg class="ico"><use href="#i-x"/></svg></button>
  </div>
  <?php if ($rcErr !== '') { ?>
    <div class="alert alert-error adm-ok" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($rcErr) ?></span></div>
  <?php } ?>
  <form class="adm-sec" method="post" action="<?php echo e($pq(array())) ?>">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
    <input type="hidden" name="act" value="recv">
    <input type="hidden" name="sku" id="rc-sku-in" value="<?php echo e($rcV('sku')) ?>">
    <input type="hidden" name="b" id="rc-b" value="<?php echo e($rcV('b')) ?>">
    <input type="hidden" name="n" id="rc-n" value="<?php echo e($rcV('n')) ?>">
    <p class="rc-now">ตอนนี้ <b id="rc-bn"><?php echo $rcB !== '' ? e($br[$rcB]['name']) : '' ?></b> มี <b id="rc-have"><?php echo number_format($rcHave) ?></b> <span class="rc-unit"><?php echo e($rcUnit) ?></span></p>
    <div class="adm-fields">
      <div class="field">
        <label for="rc-qty">จำนวนที่เพิ่ม</label>
        <div class="adm-unit">
          <input class="input" type="number" id="rc-qty" name="qty" value="<?php echo e($rcV('qty')) ?>" min="1" max="<?php echo (int) STOCK_LINE_MAX ?>" step="1" inputmode="numeric" required>
          <span class="rc-unit"><?php echo e($rcUnit) ?></span>
        </div>
        <small class="adm-hint">หลังเพิ่มจะมี <b id="rc-after">—</b> <span class="rc-unit"><?php echo e($rcUnit) ?></span></small>
      </div>
      <div class="field">
        <label for="rc-ref">เลข INV / เลขที่เอกสารนำเข้า <small class="adm-none">(ไม่บังคับ)</small></label>
        <input class="input" type="text" id="rc-ref" name="ref" value="<?php echo e($rcV('ref')) ?>" maxlength="60" autocomplete="off" placeholder="เช่น INV-2026-0012">
      </div>
      <div class="field br-wide">
        <label for="rc-note">หมายเหตุ <small class="adm-none">(ไม่บังคับ)</small></label>
        <input class="input" type="text" id="rc-note" name="note" value="<?php echo e($rcV('note')) ?>" maxlength="255" autocomplete="off" placeholder="เช่น ของโอนมาจากคลังกลาง">
      </div>
    </div>
    <small class="adm-hint rc-hint">บันทึกเป็นใบรับเข้าของสาขานี้ (วันนี้) ใช้ราคาทุนของสินค้าตอนนี้ · ยอด ประวัตินำเข้า และเคลื่อนไหวรายสินค้าขึ้นทันที</small>
    <div class="fm-foot">
      <button class="btn btn-ghost" type="button" data-fm-close>ยกเลิก</button>
      <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มเข้าสาขา</button>
    </div>
  </form>
</dialog>

<script>
/* แตะช่องยอด (.stk-add) → ใส่สินค้า / สาขาของช่องนั้นแล้วเปิดหน้าต่าง · แถวจาก “แสดงเพิ่ม” ก็ใช้ได้ (delegation) · แตะพื้นหลังไม่ปิด */
(function () {
  var dlg = document.getElementById('rc-modal');
  if (!dlg || !dlg.showModal) { return; }
  var BR  = <?php echo json_encode(array_map(function ($x) { return $x['name']; }, $br), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
  var el  = function (id) { return document.getElementById(id); };
  var qty = el('rc-qty');
  var fmt = function (n) { return n.toLocaleString('en-US'); };
  function upd() {
    var have = parseInt(dlg.getAttribute('data-have'), 10) || 0, n = parseInt(qty.value, 10);
    el('rc-after').textContent = (n > 0) ? fmt(have + n) : '—';
  }
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('.stk-add');
    if (b) {
      var tr = b.closest('tr'), q = parseInt(b.getAttribute('data-q'), 10) || 0;
      el('rc-name').textContent = tr.getAttribute('data-name');
      el('rc-sku').textContent  = tr.getAttribute('data-sku');
      el('rc-sku-in').value     = tr.getAttribute('data-sku');
      el('rc-b').value          = b.getAttribute('data-b');
      el('rc-bn').textContent   = BR[b.getAttribute('data-b')] || '';
      el('rc-n').value          = Array.prototype.indexOf.call(tr.parentNode.children, tr) + 1;   // ลำดับแถว → โหลดกลับมาให้ถึงแถวนี้
      el('rc-have').textContent = fmt(q);
      dlg.setAttribute('data-have', q);
      dlg.querySelectorAll('.rc-unit').forEach(function (u) { u.textContent = tr.getAttribute('data-unit'); });
      qty.value = el('rc-ref').value = el('rc-note').value = '';
      var al = dlg.querySelector('.alert');
      if (al) { al.parentNode.removeChild(al); }                     // ข้อความผิดพลาดของรอบก่อน
      upd();
      dlg.showModal();
      qty.focus();
      return;
    }
    if (ev.target.closest('[data-fm-close]') && dlg.contains(ev.target)) { dlg.close(); }
  });
  qty.addEventListener('input', upd);
  upd();
  if (dlg.hasAttribute('data-fm-auto')) { dlg.showModal(); qty.focus(); }
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
