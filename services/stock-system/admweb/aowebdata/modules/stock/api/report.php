<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/report.php
 * ROLE: รายงานยอดขาย (SQL ทั้งช่วง) · ตารางยอดขายรายวัน · สินค้าขายดี · ภาพรวมพนักงาน (งาน / อันดับ / งานค้าง) · ตัวช่วยของภาพรวมผู้ดูแล
 * DEPENDS: api.php (ตัวโหลด — ห้าม include ตรง)
 * TABLES: ao_stock_sale, ao_stock_sale_item, ao_stock_receive / issue / count / return (+ _item), ao_stock_branch, ao_stock_staff, ao_stock_store_day
 * TODO:
 *   - [x] ประวัติการแก้ไขดู docs/CHANGELOG.md
 * ⚠ โหลดทุก request — มีได้แค่ define() + function (ห้าม echo / header / query ตอนโหลด)
 */

/** งานค้างของสาขา (การ์ดงานที่รอดำเนินการ) — ว่าง = ไม่มี */
function pending_tasks($branch)
{
    $codes = ($branch === 'ALL') ? array_keys(branches_active()) : array($branch);
    $rows  = array();
    $count = menu_enabled('stocktake.php');            // ปิดเมนูตรวจนับจากหลังบ้าน = ไม่มีงานค้างของการตรวจนับ
    /* นับด้วย SQL — เดิมโหลดสินค้าทั้งร้านมานับใน PHP ทุกครั้งที่เปิดหน้าแรก */
    $nAll  = $count ? Stock_searchPickProducts('ALL', '', '', 0, 0)['total'] : 0;
    foreach ($codes as $c) {
        $done = $count ? count_status_all($c) : array();
        $left = $count ? Stock_searchPickProducts($c, '', '', 0, 0, null, array_keys($done))['total'] : 0;   // สินค้าที่ขายอยู่และยังไม่นับ
        if ($left > 0) {
            $rd     = count_round($c);
            $rows[] = array('type' => 'count', 'title' => 'ตรวจนับรอบนี้ยังไม่ครบ · เหลือ ' . number_format($left) . ' จาก ' . number_format($nAll) . ' รายการ',
                            'sub' => branch_name($c) . ' · ครบกำหนด ' . thai_day_month($rd['end']) . ' (อีก ' . $rd['left'] . ' วัน)',
                            'link' => 'stocktake.php', 'href' => 'stocktake.php?t=todo');   // href = เปิดแท็บ “ยังไม่นับ” (link ใช้เช็กสิทธิ์)
        }
        foreach (store_unclosed_days($c) as $ymd) {
            $rows[] = array('type' => 'store', 'title' => 'ยังไม่ได้ปิดร้านของวัน' . thai_date_full(strtotime($ymd)),
                            'sub' => branch_name($c) . ' · ปิดย้อนหลังได้ตอนเปิดร้านวันถัดไป (หน้าเปิด / ปิดร้าน) · ถ้าเปิดร้านไปแล้ว แจ้งผู้ดูแลให้ปิดย้อนหลัง',
                            'link' => '');
        }
    }
    return $rows;
}

/* ---------- ผลงานรายบุคคล (dashboard.php + สรุปตอนปิดร้าน): นับจากเอกสารที่คนนั้นบันทึก ไม่นับใบยกเลิก ---------- */

/** ชนิดงานที่นับ — ลำดับนี้คือลำดับที่แสดงในหน้า */
function staff_work_kinds()
{
    return array('sale' => 'ขายสินค้า', 'receive' => 'รับเข้า', 'issue' => 'เบิก / ตัดออก', 'count' => 'ตรวจนับ', 'return' => 'รับคืน');
}

/** เอกสารที่ไม่ยกเลิกช่วง $from–$to ของคนหนึ่งหรือสาขาหนึ่ง (SQL UNION คำสั่งเดียว) */
function staff_work_rows($col, $id, $from, $to)
{
    $col   = ($col === 'branch_id') ? 'branch_id' : 'created_by';
    $parts = array(
        'sale'    => array('sale',    'sale_date', "t.status = 'paid'",   't.total_qty'),
        'receive' => array('receive', 'doc_date',  "t.status = 'posted'", 't.total_qty'),
        'issue'   => array('issue',   'doc_date',  "t.status = 'posted'", 't.total_qty'),
        'count'   => array('count',   'doc_date',  "t.status = 'posted'",
                           '(SELECT COALESCE(SUM(ci.qty_counted), 0) FROM ' . sdb_tb('count_item') . ' ci WHERE ci.count_id = t.count_id)'),
        'return'  => array('return',  'doc_date',  '1 = 1',               't.total_qty'),
    );
    $sql    = array();
    $params = array();
    foreach ($parts as $k => $p) {
        $sql[] = "SELECT '" . $k . "' AS k, t." . $p[1] . ' AS d, t.created_by AS uid, t.item_count AS items, ' . $p[3] . ' AS qty'
               . ' FROM ' . sdb_tb($p[0]) . ' t WHERE t.' . $col . ' = ? AND t.' . $p[1] . ' BETWEEN ? AND ? AND ' . $p[2];
        array_push($params, (int) $id, date('Y-m-d', $from), date('Y-m-d', $to));
    }
    $sql = 'SELECT x.k, x.d, x.uid, COUNT(*) AS docs, SUM(x.items) AS items, SUM(x.qty) AS qty'
         . ' FROM (' . implode(' UNION ALL ', $sql) . ') x GROUP BY x.k, x.d, x.uid';
    $out = array();
    foreach (sdb_rows($sql, $params) as $r) {
        $out[] = array('k' => $r['k'], 'd' => $r['d'], 'uid' => (int) $r['uid'],
                       'docs' => (int) $r['docs'], 'items' => (int) $r['items'], 'qty' => (int) $r['qty']);
    }
    return $out;
}

/** ผลงานรายวันของคนหนึ่ง ครบทุกวันในช่วง (เก่า → ใหม่) */
function staff_days($username, $from, $to)
{
    $out = array();
    for ($d = $from; $d <= $to; $d = strtotime('+1 day', $d)) {
        $out[date('Y-m-d', $d)] = array('ts' => $d, 'idle' => true, 'docs' => 0, 'items' => 0, 'qty' => 0, 'kinds' => array());
    }
    foreach (staff_work_rows('created_by', stock_uid(array('username' => $username)), $from, $to) as $r) {
        if (!isset($out[$r['d']])) {
            continue;
        }
        $out[$r['d']]['idle']   = false;
        $out[$r['d']]['docs']  += $r['docs'];
        $out[$r['d']]['items'] += $r['items'];
        $out[$r['d']]['qty']   += $r['qty'];
        $out[$r['d']]['kinds'][$r['k']] = array('docs' => $r['docs'], 'qty' => $r['qty']);
    }
    return $out;
}

/** รวมผลงานหลายวัน — days = จำนวนวันที่มีเอกสาร */
function staff_sum($rows)
{
    $t = array('docs' => 0, 'items' => 0, 'qty' => 0, 'days' => 0);
    foreach ($rows as $r) {
        if ($r['idle']) {
            continue;
        }
        $t['docs']  += $r['docs'];
        $t['items'] += $r['items'];
        $t['qty']   += $r['qty'];
        $t['days']++;
    }
    return $t;
}

/** แบ่งงานของวันหนึ่งตามชนิดเอกสาร (เฉพาะชนิดที่มี) */
function staff_work_types($day)
{
    $total = max(1, $day['qty']);
    $out   = array();
    foreach (staff_work_kinds() as $k => $label) {
        if (!isset($day['kinds'][$k])) {
            continue;
        }
        $x     = $day['kinds'][$k];
        $out[] = array('type' => $k, 'label' => $label, 'docs' => $x['docs'], 'qty' => $x['qty'],
                       'pct' => (int) round(100 * $x['qty'] / $total));
    }
    return $out;
}

/** สินค้าที่คนนี้จัดการมากที่สุดวันนี้ (ทุกชนิดเอกสาร ไม่นับใบที่ยกเลิก) — array ของ array(product, qty) */
function staff_top_products($username, $limit = 4)
{
    $parts = array(
        array('sale_item',    'sale',    'sale_id',    'sale_date', "t.status = 'paid'",   'i.qty'),
        array('receive_item', 'receive', 'receive_id', 'doc_date',  "t.status = 'posted'", 'i.qty'),
        array('issue_item',   'issue',   'issue_id',   'doc_date',  "t.status = 'posted'", 'i.qty'),
        array('count_item',   'count',   'count_id',   'doc_date',  "t.status = 'posted'", 'i.qty_counted'),
        array('return_item',  'return',  'return_id',  'doc_date',  '1 = 1',               'i.qty'),
    );
    $uid    = stock_uid(array('username' => $username));
    $sql    = array();
    $params = array();
    foreach ($parts as $p) {
        $sql[] = 'SELECT i.sku, ' . $p[5] . ' AS q FROM ' . sdb_tb($p[0]) . ' i JOIN ' . sdb_tb($p[1]) . ' t ON t.' . $p[2] . ' = i.' . $p[2]
               . ' WHERE t.created_by = ? AND t.' . $p[3] . ' = ? AND ' . $p[4];
        array_push($params, $uid, date('Y-m-d'));
    }
    $sql = 'SELECT x.sku, SUM(x.q) AS q FROM (' . implode(' UNION ALL ', $sql) . ') x'
         . ' GROUP BY x.sku ORDER BY q DESC, x.sku LIMIT ' . max(1, (int) $limit);
    $out = array();
    foreach (sdb_rows($sql, $params) as $r) {
        $p = product_by_sku($r['sku']);
        if ($p !== null) {
            $out[] = array('product' => $p, 'qty' => (int) $r['q']);
        }
    }
    return $out;
}

/** อันดับงานวันนี้ในสาขา (รวมคนจากที่อื่นที่มาทำเอกสารของสาขานี้) */
function branch_rank_today($branchCode)
{
    $today = strtotime(date('Y-m-d'));
    $codes = ($branchCode === 'ALL') ? array_keys(branches_active()) : array($branchCode);
    $work  = array();
    foreach ($codes as $c) {
        foreach (staff_work_rows('branch_id', branch_id_of($c), $today, $today) as $r) {
            if (!isset($work[$r['uid']])) {
                $work[$r['uid']] = array('docs' => 0, 'qty' => 0);
            }
            $work[$r['uid']]['docs'] += $r['docs'];
            $work[$r['uid']]['qty']  += $r['qty'];
        }
    }
    $rows = array();
    foreach (users_all() as $uname => $u) {
        $w    = isset($work[$u['id']]) ? $work[$u['id']] : null;
        $here = ($u['role'] === 'staff' && user_active($u) && in_array($u['branch'], $codes, true));
        if (!$here && $w === null) {
            continue;
        }
        $rows[] = array(
            'username' => $uname,
            'name'     => $u['name'],
            'branch'   => $u['branch'],
            'idle'     => ($w === null),
            'docs'     => $w ? $w['docs'] : 0,
            'qty'      => $w ? $w['qty'] : 0,
        );
    }
    usort($rows, 'compare_rank_qty');
    return $rows;
}

function compare_rank_qty($a, $b)
{
    if ($a['idle'] !== $b['idle']) {
        return $a['idle'] ? 1 : -1;
    }
    if ($a['qty'] == $b['qty']) {
        return $b['docs'] - $a['docs'];
    }
    return $a['qty'] > $b['qty'] ? -1 : 1;
}

/** ช่วงวันที่ของหน้าตรวจสอบฝั่งผู้ดูแล — คืน array(จาก, ถึง, ข้อความ)
    $mode = recent (30 วันล่าสุด) | day | month | year */
function adm_range($mode, $dayTs, $monTs, $year)
{
    $today = strtotime(date('Y-m-d'));
    if ($mode === 'day') {
        return array($dayTs, $dayTs, thai_date_full($dayTs));
    }
    if ($mode === 'month') {
        return array($monTs, min(strtotime(date('Y-m-t', $monTs)), $today), thai_month_full($monTs));
    }
    if ($mode === 'year') {
        return array(strtotime($year . '-01-01'), min(strtotime($year . '-12-31'), $today), 'ปี ' . ($year + 543));
    }
    $from = strtotime('-29 day', $today);
    return array($from, $today, '30 วันล่าสุด (' . thai_day_month($from) . ' – ' . thai_day_month($today) . ')');
}

/** ยอดขายบิลที่ไม่ยกเลิก ช่วง $from–$to แยกวัน × สาขา × คนขาย · $uid > 0 = เฉพาะคนนี้ */
function sales_agg($codes, $from, $to, $uid = 0)
{
    list($in, $params) = sdb_in('b.code', $codes);
    array_unshift($params, date('Y-m-d', $from), date('Y-m-d', $to));
    if ($uid > 0) {
        $params[] = (int) $uid;
    }
    $sql = 'SELECT s.sale_date AS d, b.code, s.created_by AS uid, u.username, u.name, COUNT(*) AS bills, SUM(s.total_qty) AS qty,'
         . ' SUM(s.total) AS total, SUM(s.discount) AS disc, SUM(CASE WHEN s.pay_method = \'cash\' THEN s.total ELSE 0 END) AS cash,'
         . ' SUM(s.is_vat) AS vat'
         . ' FROM ' . sdb_tb('sale') . ' s JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = s.branch_id'
         . ' LEFT JOIN ' . sdb_tb('staff') . ' u ON u.staff_id = s.created_by'
         . ' WHERE s.status = \'paid\' AND s.sale_date BETWEEN ? AND ? AND ' . $in . ($uid > 0 ? ' AND s.created_by = ?' : '')
         . ' GROUP BY s.sale_date, b.sort, b.branch_id, b.code, s.created_by, u.username, u.name'
         . ' ORDER BY s.sale_date, b.sort, b.branch_id, s.created_by';
    $out = array();
    foreach (sdb_rows($sql, $params) as $r) {
        $out[] = array('d' => $r['d'], 'branch' => $r['code'], 'uid' => (int) $r['uid'],
                       'user' => ($r['username'] !== null) ? $r['username'] : '', 'name' => ($r['name'] !== null) ? $r['name'] : '-',
                       'bills' => (int) $r['bills'], 'qty' => (int) $r['qty'], 'total' => (float) $r['total'],
                       'disc' => (float) $r['disc'], 'cash' => (float) $r['cash'], 'vat' => (int) $r['vat']);
    }
    return $out;
}

/** จำนวนบิลที่ยกเลิก ช่วง $from–$to ของสาขาใน $codes */
function sales_void_count($codes, $from, $to)
{
    list($in, $params) = sdb_in('b.code', $codes);
    array_unshift($params, date('Y-m-d', $from), date('Y-m-d', $to));
    return (int) sdb_val('SELECT COUNT(*) FROM ' . sdb_tb('sale') . ' s JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = s.branch_id'
                       . ' WHERE s.status = \'void\' AND s.sale_date BETWEEN ? AND ? AND ' . $in, $params);
}

/** วันแรกที่มีบิลขาย (ยังไม่มี = วันนี้) */
function sales_first_day()
{
    static $ts = null;
    if ($ts === null) {
        $d  = sdb_val('SELECT MIN(sale_date) FROM ' . sdb_tb('sale'));
        $ts = ($d !== null) ? strtotime($d) : strtotime(date('Y-m-d'));
    }
    return $ts;
}

/** รวมยอดขาย (ไม่นับบิลยกเลิก) ช่วง $from–$to — รายวัน × สาขา และรายคน */
function sales_scan($codes, $from, $to)
{
    $day = array();
    for ($d = $from; $d <= $to; $d = strtotime('+1 day', $d)) {
        foreach ($codes as $c) {
            $day[date('Ymd', $d)][$c] = array('total' => 0, 'bills' => 0, 'qty' => 0);
        }
    }
    $staff = array();
    foreach (sales_agg($codes, $from, $to) as $r) {
        $k = date('Ymd', strtotime($r['d']));
        $day[$k][$r['branch']]['total'] += $r['total'];
        $day[$k][$r['branch']]['bills'] += $r['bills'];
        $day[$k][$r['branch']]['qty']   += $r['qty'];
        $u = $r['user'];
        if (!isset($staff[$u])) {
            $staff[$u] = array('name' => $r['name'], 'branch' => $r['branch'], 'bills' => 0, 'qty' => 0,
                               'total' => 0, 'disc' => 0, 'days' => array());
        }
        $staff[$u]['bills']   += $r['bills'];
        $staff[$u]['qty']     += $r['qty'];
        $staff[$u]['total']   += $r['total'];
        $staff[$u]['disc']    += $r['disc'];
        $staff[$u]['days'][$k] = true;
        $staff[$u]['branch']   = $r['branch'];          // สาขาล่าสุดที่ขาย (ย้ายสาขาได้) — แถวเรียงตามวันอยู่แล้ว
    }
    return array('day' => $day, 'staff' => $staff);
}

/** ตารางยอดขายรายวันของเดือน (แถว = วัน · คอลัมน์ = สาขา) · $v = total | bills | qty */
function daily_matrix($branches, $monTs, $v)
{
    $today = strtotime(date('Y-m-d'));
    $first = strtotime(date('Y-m-01', $monTs));
    $last  = strtotime(date('Y-m-t', $monTs));
    $codes = array_keys($branches);
    $scan  = ($codes && $first <= $today) ? sales_scan($codes, $first, min($last, $today)) : array('day' => array());

    $out = array('rows' => array(), 'col' => array(), 'open' => array(), 'best' => array(),
                 'grand' => 0, 'cellMax' => 0, 'bestDay' => array(0, ''));
    foreach ($codes as $c) {
        $out['col'][$c]  = 0;
        $out['open'][$c] = 0;
        $out['best'][$c] = array(0, '');
    }
    for ($d = $first; $d <= $last; $d = strtotime('+1 day', $d)) {
        $k   = date('Ymd', $d);
        $row = array('ts' => $d, 'future' => $d > $today, 'cells' => array(), 'sum' => 0);
        foreach ($codes as $c) {
            $val = isset($scan['day'][$k][$c]) ? $scan['day'][$k][$c][$v] : 0;
            $row['cells'][$c] = $val;
            $row['sum']      += $val;
            $out['col'][$c]  += $val;
            if ($val > 0) {
                $out['open'][$c]++;
            }
            if ($val > $out['best'][$c][0]) {
                $out['best'][$c] = array($val, $k);
            }
            $out['cellMax'] = max($out['cellMax'], $val);
        }
        $out['grand'] += $row['sum'];
        if ($row['sum'] > $out['bestDay'][0]) {
            $out['bestDay'] = array($row['sum'], $k);
        }
        $out['rows'][$k] = $row;
    }
    return $out;
}

/** ยอดขายรายสินค้า (ไม่นับบิลยกเลิก · หักส่วนลดท้ายบิลตามสัดส่วน) — SQL คำสั่งเดียว */
function product_sales($codes, $from, $to)
{
    list($in, $params) = sdb_in('b.code', $codes);
    array_unshift($params, date('Y-m-d', $from), date('Y-m-d', $to));
    $sql = 'SELECT i.sku, b.code, MAX(i.product_name) AS pname, SUM(i.qty) AS qty, COUNT(DISTINCT i.sale_id) AS bills,'
         . ' SUM(CASE WHEN s.subtotal > 0 THEN i.line_total * s.total / s.subtotal ELSE i.line_total END) AS total,'
         . ' MIN(s.add_date) AS first_at'
         . ' FROM ' . sdb_tb('sale_item') . ' i JOIN ' . sdb_tb('sale') . ' s ON s.sale_id = i.sale_id'
         . ' JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = s.branch_id'
         . ' WHERE s.status = \'paid\' AND s.sale_date BETWEEN ? AND ? AND ' . $in
         . ' GROUP BY i.sku, b.code ORDER BY first_at, i.sku';
    $out = array();
    $rs  = sdb_rows($sql, $params);
    Stock_prefetchProducts(array_column($rs, 'sku'));      // สินค้าทุกตัวในรายงานคิวรีเดียว (ช่วง 5)
    foreach ($rs as $r) {
        $k = $r['sku'];
        if (!isset($out[$k])) {
            $p = product_by_sku($k);
            $out[$k] = array('sku' => $k, 'name' => $p ? $p['name'] : $r['pname'], 'cat' => $p ? $p['cat'] : '',
                             'unit' => $p ? $p['unit'] : 'ชิ้น', 'qty' => 0, 'total' => 0, 'bills' => 0, 'branches' => array());
        }
        $out[$k]['qty']   += (int) $r['qty'];
        $out[$k]['total'] += (float) $r['total'];
        $out[$k]['bills'] += (int) $r['bills'];
        $out[$k]['branches'][$r['code']] = (int) $r['qty'];
    }
    foreach ($out as $k => $x) {                       // แยกสาขาเรียงตามลำดับสาขาที่ส่งมา
        $b = array();
        foreach ($codes as $c) {
            if (isset($x['branches'][$c])) {
                $b[$c] = $x['branches'][$c];
            }
        }
        $out[$k]['branches'] = $b;
    }
    return $out;
}

/** ช่วงวันที่ของแต่ละโหมด — คืน array(from_ts, to_ts, label) */
function report_range($mode, $ref, $user)
{
    $today = strtotime(date('Y-m-d'));

    if ($mode === 'day') {
        $d = $ref !== '' ? strtotime($ref) : $today;
        if ($d === false || $d > $today) {
            $d = $today;
        }
        return array($d, $d, thai_date_full($d));
    }

    if ($mode === 'month') {
        $m = $ref !== '' ? strtotime($ref . '-01') : strtotime(date('Y-m-01'));
        if ($m === false || $m > $today) {
            $m = strtotime(date('Y-m-01'));
        }
        $end = strtotime(date('Y-m-t', $m));
        if ($end > $today) {
            $end = $today;
        }
        return array($m, $end, thai_month_full($m));
    }

    /* ทั้งหมด — นับตั้งแต่วันเริ่มงาน */
    $start = strtotime(user_start_date(full_user($user)));
    return array($start, $today, 'ตั้งแต่ ' . thai_date_full($start));
}

/** รายงานยอดขายของพนักงาน (report-sales.php) · $scope = mine | branch */
function sales_report($user, $scope, $mode, $ref)
{
    $range = report_range($mode, $ref, $user);
    $from  = $range[0];
    $to    = $range[1];
    $uid   = stock_uid($user);
    if ($scope === 'mine') {
        $rows = ($uid > 0) ? sales_agg(array_keys(branches_all()), $from, $to, $uid) : array();
    } else {
        $rows = sales_agg(array($user['branch']), $from, $to);
    }
    $byDay = array();
    foreach ($rows as $r) {
        $byDay[$r['d']][] = $r;
    }

    $sum      = array('bills' => 0, 'qty' => 0, 'total' => 0, 'days' => 0);
    $days     = array();
    $people   = array();
    $branches = array();

    for ($ts = $from; $ts <= $to; $ts = strtotime('+1 day', $ts)) {
        $dayTotal = 0;
        $dayBills = 0;
        $dayQty   = 0;

        foreach (isset($byDay[date('Y-m-d', $ts)]) ? $byDay[date('Y-m-d', $ts)] : array() as $r) {
            $dayBills += $r['bills'];
            $dayQty   += $r['qty'];
            $dayTotal += $r['total'];

            $pk = $r['user'];
            if (!isset($people[$pk])) {
                $people[$pk] = array('user' => $pk, 'name' => $r['name'],
                                     'bills' => 0, 'qty' => 0, 'total' => 0);
            }
            $people[$pk]['bills'] += $r['bills'];
            $people[$pk]['qty']   += $r['qty'];
            $people[$pk]['total'] += $r['total'];

            $bk = $r['branch'];
            if (!isset($branches[$bk])) {
                $branches[$bk] = array('branch' => $bk, 'bills' => 0, 'qty' => 0,
                                       'total' => 0, 'first' => $ts, 'last' => $ts);
            }
            $branches[$bk]['bills'] += $r['bills'];
            $branches[$bk]['qty']   += $r['qty'];
            $branches[$bk]['total'] += $r['total'];
            $branches[$bk]['last']   = $ts;
        }

        $days[] = array('ts' => $ts, 'bills' => $dayBills, 'qty' => $dayQty, 'total' => $dayTotal);

        $sum['bills'] += $dayBills;
        $sum['qty']   += $dayQty;
        $sum['total'] += $dayTotal;
        if ($dayTotal > 0) {
            $sum['days']++;
        }
    }

    usort($people, 'cmp_total_desc');

    return array(
        'label'    => $range[2],
        'from'     => $from,
        'to'       => $to,
        'sum'      => $sum,
        'days'     => $days,
        'people'   => $people,
        'branches' => $branches,
    );
}

/** ยุบรายวันเป็นรายเดือน ใช้ตอนช่วงยาวเกินกว่าจะวาดทีละวัน */
function group_by_month($days)
{
    $out = array();
    foreach ($days as $d) {
        $k = date('Y-m', $d['ts']);
        if (!isset($out[$k])) {
            $out[$k] = array('ts' => strtotime(date('Y-m-01', $d['ts'])),
                             'bills' => 0, 'qty' => 0, 'total' => 0);
        }
        $out[$k]['bills'] += $d['bills'];
        $out[$k]['qty']   += $d['qty'];
        $out[$k]['total'] += $d['total'];
    }
    return array_values($out);
}

/** เดือนย้อนหลังที่เลือกได้ */
function report_months($user, $limit = 6)
{
    $out   = array();
    $start = strtotime(date('Y-m-01', strtotime(user_start_date(full_user($user)))));
    $m     = strtotime(date('Y-m-01'));
    while ($m >= $start && count($out) < $limit) {
        $out[] = array('key' => date('Y-m', $m), 'label' => thai_month_full($m));
        $m = strtotime('-1 month', $m);
    }
    return $out;
}

/** เรียงรายการที่ต้องตรวจ ใหม่สุดขึ้นก่อน */
function dash_review_cmp($a, $b)
{
    return $b['ts'] - $a['ts'];
}
