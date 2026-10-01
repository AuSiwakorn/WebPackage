<?php
/* ==========================================================
   AOSTOCK DEMO — รายงานยอดขาย
   ----------------------------------------------------------
   หลักการสำคัญ (ตอบเรื่องพนักงานย้ายสาขา)

   ทุกบรรทัดของยอดขายเก็บ 2 อย่างคู่กันเสมอ
     branch  = สาขา "ณ วันที่ขาย"   → ใช้ตอบคำถาม "สาขานี้ขายได้เท่าไร"
     by_user = คนขาย                → ใช้ตอบคำถาม "ฉันขายได้เท่าไร"

   สาขาถูกปั๊มลงในเอกสารตอนบันทึก ไม่ใช่ไปอ่านจากโปรไฟล์ตอนออกรายงาน
   ถ้าอ่านจากโปรไฟล์ พอพนักงานย้ายสาขา ยอดเก่าของสาขาเดิมจะย้ายตามไปด้วย
   ซึ่งผิด — ยอดของเดือนที่แล้วต้องไม่เปลี่ยนไม่ว่าจะย้ายคนไปไหน

   วันนี้ = ข้อมูลจริงจาก session (บิลที่ยกเลิกไม่นับ)
   ย้อนหลัง = ข้อมูลสมมติที่คำนวณจาก crc32 จึงคงที่ทุกครั้งที่เปิด
   ระบบจริง: SELECT ... FROM sale_bill WHERE branch_id=? AND sold_at BETWEEN ?
   ========================================================== */

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/data.php';
require_once dirname(__FILE__) . '/sale.php';

/**
 * ข้อมูลเต็มของผู้ใช้ — session เก็บไว้แค่ไม่กี่ฟิลด์เพื่อให้เบา
 * ประวัติการย้ายสาขาจึงต้องอ่านจากทะเบียนพนักงานอีกที
 * ระบบจริง: SELECT * FROM users WHERE id = ?
 */
function full_user($user)
{
    $all = demo_users_all();
    return isset($all[$user['username']]) ? $all[$user['username']] : $user;
}

/** ยอดขายของพนักงานคนหนึ่งในวันหนึ่ง — ข้อมูลสมมติที่คงที่ */
function demo_sales_of($username, $ts)
{
    $all = demo_users_all();
    if (!isset($all[$username])) {
        return null;
    }
    $u      = $all[$username];
    $branch = user_branch_on($u, $ts);
    if ($branch === '') {
        return null;                                   // ยังไม่ได้เริ่มงาน
    }
    if ((int) date('N', $ts) === 7) {
        return null;                                   // อาทิตย์ปิดร้าน
    }
    if (staff_dayoff($username) === (int) date('N', $ts)) {
        return null;                                   // วันหยุดของคนนี้
    }

    $s     = abs(crc32($username . '|sale|' . date('Ymd', $ts)));
    $bills = 6 + ($s % 19);                            // 6–24 บิล
    $qty   = $bills * (1 + (($s >> 4) % 3));           // 1–3 ชิ้นต่อบิล
    $total = $qty * (15 + (($s >> 9) % 31)) * 10;      // 150–450 บาทต่อชิ้น

    return array(
        'user'   => $username,
        'name'   => $u['name'],
        'branch' => $branch,
        'bills'  => $bills,
        'qty'    => $qty,
        'total'  => $total,
    );
}

/** ยอดขายจริงของวันนี้จาก session (ไม่นับบิลที่ยกเลิก) */
function today_sales_rows($code)
{
    $out = array();
    foreach (bills_today($code) as $b) {
        if (!empty($b['void'])) {
            continue;
        }
        $k = $b['by_user'];
        if (!isset($out[$k])) {
            $out[$k] = array('user' => $k, 'name' => $b['by'], 'branch' => $code,
                             'bills' => 0, 'qty' => 0, 'total' => 0);
        }
        $out[$k]['bills']++;
        $out[$k]['qty']   += $b['qty'];
        $out[$k]['total'] += $b['total'];
    }
    return array_values($out);
}

/**
 * ยอดขายทุกคนของวันหนึ่ง
 * วันนี้ใช้ของจริงจาก session ถ้ายังไม่มีบิลก็คืน array ว่าง (ยังไม่ได้ขาย)
 */
function sales_of_day($ts)
{
    $isToday = (date('Ymd', $ts) === date('Ymd'));
    $rows    = array();

    foreach (demo_users_all() as $username => $u) {
        if ($u['role'] !== 'staff') {
            continue;
        }
        if ($isToday) {
            continue;                                  // วันนี้อ่านจาก session แทน
        }
        $r = demo_sales_of($username, $ts);
        if ($r !== null) {
            $rows[] = $r;
        }
    }

    if ($isToday) {
        foreach (array_keys(demo_branches()) as $code) {
            foreach (today_sales_rows($code) as $r) {
                $rows[] = $r;
            }
        }
    }
    return $rows;
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

/**
 * ดึงรายงานตามเงื่อนไข
 *   $scope = 'mine' (เฉพาะฉัน ทุกสาขาที่เคยอยู่) | 'branch' (ทั้งสาขาปัจจุบัน)
 * คืน array: sum, days (ไล่วัน), people (แยกคน), branches (แยกสาขา)
 */
function sales_report($user, $scope, $mode, $ref)
{
    $range = report_range($mode, $ref, $user);
    $from  = $range[0];
    $to    = $range[1];

    $sum      = array('bills' => 0, 'qty' => 0, 'total' => 0, 'days' => 0);
    $days     = array();
    $people   = array();
    $branches = array();

    for ($ts = $from; $ts <= $to; $ts = strtotime('+1 day', $ts)) {
        $dayTotal = 0;
        $dayBills = 0;
        $dayQty   = 0;

        foreach (sales_of_day($ts) as $r) {
            if ($scope === 'mine' && $r['user'] !== $user['username']) {
                continue;
            }
            if ($scope === 'branch' && $r['branch'] !== $user['branch']) {
                continue;
            }

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

function cmp_total_desc($a, $b)
{
    if ($a['total'] === $b['total']) {
        return 0;
    }
    return ($a['total'] < $b['total']) ? 1 : -1;
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

function thai_month_full($ts)
{
    $m = array('', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
               'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม');
    return $m[(int) date('n', $ts)] . ' ' . (((int) date('Y', $ts)) + 543);
}
