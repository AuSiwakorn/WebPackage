<?php
/**
 * FILE: admweb/core/counter/hooks/hooks_function.php
 * ROLE: Helper functions สำหรับ counter dashboard — โหลดอัตโนมัติจาก index.php
 * DEPENDS: func.counter.php (DB::singleton, _counter_table_exists, _counter_ref_table_exists)
 * TABLES: site_counter, site_counter_ref
 * TODO:
 *   - [x] func_counter_get_hours — peak hour chart
 *   - [x] func_counter_get_devices — mobile vs desktop split
 *   - [x] func_counter_get_top_refs — top referrer domains
 */

/**
 * ดึงยอด page view รายชั่วโมง (0–23) — สำหรับ Peak Hour Chart
 *
 * @param  int    $year
 * @param  int    $month
 * @param  string $type  'web' หรือ 'admin'
 * @return array  [0=>N, 1=>N, ..., 23=>N] ครบ 24 ชั่วโมงเสมอ (ไม่มีข้อมูล = 0)
 */
function func_counter_get_hours($year = 0, $month = 0, $type = 'web')
{
    if (!_counter_table_exists()) return array_fill(0, 24, 0);

    $year  = $year  > 0 ? (int)$year  : (int)date('Y');
    $month = $month > 0 ? (int)$month : (int)date('m');
    $db    = DB::singleton();

    $sql  = "SELECT hour, SUM(count) as total FROM `" . _DBPREFIX_ . "site_counter`
             WHERE page='' AND type=:t AND year=:y AND month=:m
             GROUP BY hour ORDER BY hour ASC";
    $rows = $db->getAll($sql, [':t' => $type, ':y' => $year, ':m' => $month]);

    $result = array_fill(0, 24, 0);
    foreach ((array)$rows as $r) {
        $h = (int)$r['hour'];
        if ($h >= 0 && $h <= 23) {
            $result[$h] = (int)$r['total'];
        }
    }
    return $result;
}

/**
 * ดึงยอด page view แยก mobile / desktop — สำหรับ Device Split
 *
 * @param  int    $year
 * @param  int    $month
 * @param  string $type  'web' หรือ 'admin'
 * @return array  ['mobile' => N, 'desktop' => N]
 */
function func_counter_get_devices($year = 0, $month = 0, $type = 'web')
{
    if (!_counter_table_exists()) return ['mobile' => 0, 'desktop' => 0];

    $year  = $year  > 0 ? (int)$year  : (int)date('Y');
    $month = $month > 0 ? (int)$month : (int)date('m');
    $db    = DB::singleton();

    $sql  = "SELECT device, SUM(count) as total FROM `" . _DBPREFIX_ . "site_counter`
             WHERE page='' AND type=:t AND year=:y AND month=:m
             GROUP BY device";
    $rows = $db->getAll($sql, [':t' => $type, ':y' => $year, ':m' => $month]);

    $result = ['mobile' => 0, 'desktop' => 0];
    foreach ((array)$rows as $r) {
        $dev = strtolower((string)$r['device']);
        if (isset($result[$dev])) {
            $result[$dev] = (int)$r['total'];
        }
    }
    return $result;
}

/**
 * ดึง top referrer domains — สำหรับ Top Referrers Table
 *
 * @param  int    $year
 * @param  int    $month  0 = ทุกเดือนในปีนั้น
 * @param  int    $limit
 * @return array  [['ref_domain' => '...', 'total' => N], ...]
 */
function func_counter_get_top_refs($year = 0, $month = 0, $limit = 10)
{
    if (!_counter_ref_table_exists()) return [];

    $year  = $year > 0 ? (int)$year : (int)date('Y');
    $limit = max(1, (int)$limit);
    $db    = DB::singleton();

    if ($month > 0) {
        $sql    = "SELECT ref_domain, SUM(count) as total FROM `" . _DBPREFIX_ . "site_counter_ref`
                   WHERE year=:y AND month=:m
                   GROUP BY ref_domain ORDER BY total DESC LIMIT {$limit}";
        $params = [':y' => $year, ':m' => (int)$month];
    } else {
        $sql    = "SELECT ref_domain, SUM(count) as total FROM `" . _DBPREFIX_ . "site_counter_ref`
                   WHERE year=:y
                   GROUP BY ref_domain ORDER BY total DESC LIMIT {$limit}";
        $params = [':y' => $year];
    }

    return (array)$db->getAll($sql, $params);
}
