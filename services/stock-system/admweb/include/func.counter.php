<?php
/**
 * FILE: admweb/include/func.counter.php
 * ROLE: ระบบนับ page view — เก็บข้อมูลใน DB (site_counter) แบบ atomic
 * DEPENDS: plugins/db/function/func_v8.php (DB_UPSERT, DB_GET), plugins/db/function/php_v8.php (DB::singleton)
 *          include/fix.req.php (is_mobile())
 * TABLES: site_counter, site_counter_ref
 * TODO:
 *   - [x] ย้ายจาก File-based JSON → DB (ไม่มี race condition)
 *   - [x] คง public API เดิมทุก function — code ที่เรียกใช้ไม่ต้องแก้
 *   - [x] เพิ่ม hour + device ใน _counter_db_write()
 *   - [ ] เขียน _counter_ref_write() — referrer domain tracking (Step 10)
 *   - [ ] เพิ่ม func_counter_clear($year, $month) สำหรับลบข้อมูลเก่า
 */

global $setcounteroneonly;

/* ══════════════════════════════════════════════════════
 *  PRIVATE HELPERS
 * ══════════════════════════════════════════════════════ */

/**
 * ตรวจสอบว่าตาราง site_counter ถูกสร้างแล้วหรือยัง
 * ใช้ $_SESSION['tablerecheck'] ที่ set ไว้ตอน boot (conf.ini.php)
 *
 * @return bool
 */
function _counter_table_exists()
{
    $table = _DBPREFIX_ . 'site_counter';
    return !empty($_SESSION['tablerecheck']) && in_array($table, $_SESSION['tablerecheck']);
}

/**
 * ตรวจสอบว่าตาราง site_counter_ref ถูกสร้างแล้วหรือยัง
 *
 * @return bool
 */
function _counter_ref_table_exists()
{
    $table = _DBPREFIX_ . 'site_counter_ref';
    return !empty($_SESSION['tablerecheck']) && in_array($table, $_SESSION['tablerecheck']);
}

/**
 * TODO: [x] atomic write ด้วย DB_UPSERT — capture hour + device อัตโนมัติ
 *
 * เพิ่ม count +1 แบบ atomic ผ่าน INSERT ... ON DUPLICATE KEY UPDATE
 * PK: (page, type, year, month, day, hour, device)
 *
 * @param string $page  ชื่อหน้า — '' คือ global counter
 * @param string $type  'web' หรือ 'admin'
 */
function _counter_db_write($page = '', $type = 'web')
{
    if (!_counter_table_exists()) return;

    $device = (function_exists('is_mobile') && is_mobile()) ? 'mobile' : 'desktop';

    DB_UPSERT('site_counter', [
        'page'   => $page,
        'type'   => $type,
        'year'   => (int)date('Y'),
        'month'  => (int)date('m'),
        'day'    => (int)date('d'),
        'hour'   => (int)date('G'),   // G = 0–23 ไม่มี leading zero
        'device' => $device,
        'count'  => 1,
    ], [
        'count' => ['EXPR', 'count + 1'],
    ]);
}

/**
 * TODO: [x] บันทึก referrer domain — skip self-referral, normalize www.
 *
 * อ่าน HTTP_REFERER แล้วแตก domain เก็บรายเดือนใน site_counter_ref
 * — เก็บเฉพาะ external domain ไม่เก็บ self หรือ empty
 *
 * @param string $page  ชื่อหน้าที่ถูกเปิด
 */
function _counter_ref_write($page = '')
{
    if (!_counter_ref_table_exists()) return;
    if (empty($_SERVER['HTTP_REFERER'])) return;

    $refDomain = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if (empty($refDomain)) return;

    // normalize — ตัด www. ออก
    $refDomain = preg_replace('/^www\./i', '', strtolower($refDomain));

    // skip self-referral
    $selfDomain = defined('DOMAIN_NAME') ? strtolower(DOMAIN_NAME) : '';
    $selfDomain = preg_replace('/^www\./i', '', $selfDomain);
    if ($selfDomain !== '' && $refDomain === $selfDomain) return;

    DB_UPSERT('site_counter_ref', [
        'page'       => $page,
        'ref_domain' => $refDomain,
        'year'       => (int)date('Y'),
        'month'      => (int)date('m'),
        'count'      => 1,
    ], [
        'count' => ['EXPR', 'count + 1'],
    ]);
}

/* ══════════════════════════════════════════════════════
 *  PUBLIC API — คง signature เดิมทุก function
 * ══════════════════════════════════════════════════════ */

/**
 * TODO: [x] entry point สำหรับเรียกจากหน้าเว็บ
 *
 * @param string $pagename    ชื่อหน้า (ปล่อยว่าง = ดึงจาก SCRIPT_NAME)
 * @param string $IntraInter  'web' = หน้าเว็บ, 'admin' = หลังบ้าน
 */
function func_counter_set($pagename = '', $IntraInter = 'web')
{
    global $setcounteroneonly;
    if ($setcounteroneonly != '1') {
        func_counter_page($pagename);
        func_counter_txt($pagename, $IntraInter);
        _counter_ref_write($pagename);
    }
}

/**
 * TODO: [x] นับ global counter และคืนค่าตามที่ขอ
 *
 * @param  string $txtname     'd'=วันนี้, 'm'=เดือนนี้, 'y'=ปีนี้, ''=ทั้งหมด
 * @param  string $IntraInter  'web' หรือ 'admin'
 * @return int    ยอดตามที่ขอ
 */
function func_counter_txt($txtname = '', $IntraInter = 'inter')
{
    if (!_counter_table_exists()) return 0;
    global $setCounter;

    $y  = (int)date('Y');
    $m  = (int)date('m');
    $d  = (int)date('d');
    $db = DB::singleton();

    // ป้องกันนับซ้ำใน request เดียวกัน
    if ($setCounter != 1) {
        $setCounter = 1;
        _counter_db_write('', $IntraInter);
    }

    // ใช้ SUM เสมอ เพราะ PK แยกตาม hour+device แล้ว
    if ($txtname == 'd') {
        $sql = "SELECT SUM(count) FROM `" . _DBPREFIX_ . "site_counter`
                WHERE page='' AND type=:t AND year=:y AND month=:m AND day=:d";
        return (int)$db->getOne($sql, [':t' => $IntraInter, ':y' => $y, ':m' => $m, ':d' => $d]);

    } elseif ($txtname == 'm') {
        $sql = "SELECT SUM(count) FROM `" . _DBPREFIX_ . "site_counter`
                WHERE page='' AND type=:t AND year=:y AND month=:m";
        return (int)$db->getOne($sql, [':t' => $IntraInter, ':y' => $y, ':m' => $m]);

    } elseif ($txtname == 'y') {
        $sql = "SELECT SUM(count) FROM `" . _DBPREFIX_ . "site_counter`
                WHERE page='' AND type=:t AND year=:y";
        return (int)$db->getOne($sql, [':t' => $IntraInter, ':y' => $y]);

    } else {
        $sql = "SELECT SUM(count) FROM `" . _DBPREFIX_ . "site_counter`
                WHERE page='' AND type=:t";
        return (int)$db->getOne($sql, [':t' => $IntraInter]);
    }
}

/**
 * TODO: [x] นับ per-page view counter
 *
 * @param string $pagename  ชื่อหน้า (ปล่อยว่าง = ดึงจาก SCRIPT_NAME)
 */
function func_counter_page($pagename = '')
{
    if ($pagename === '') {
        $pagename = basename($_SERVER['SCRIPT_NAME']);
    }
    _counter_db_write($pagename, 'web');
}

/**
 * TODO: [x] อ่านข้อมูล counter รายเดือน — return format เหมือนเดิม
 *
 * @param  string $mY         'm-Y' เช่น '05-2026', '' = เดือนปัจจุบัน + ยอดรวมทั้งหมด
 * @param  string $IntraInter 'web' หรือ 'admin'
 * @return array  [
 *   'all'   => N,                   // ยอดรวม all-time (เฉพาะตอน $mY='')
 *   'year'  => [2025=>N, 2026=>N],  // ยอดรายปี (เฉพาะตอน $mY='')
 *   'month' => [0=>รวมเดือน, 1=>วัน1, ..., 31=>วัน31]
 * ]
 */
function func_counter_get($mY = '', $IntraInter = 'web')
{
    if (!_counter_table_exists()) return ['all' => 0, 'year' => [], 'month' => [0 => 0]];
    global $aReturnGlobal;

    $cacheKey = $mY . $IntraInter;
    if (isset($aReturnGlobal[$cacheKey])) {
        return $aReturnGlobal[$cacheKey];
    }

    $db      = DB::singleton();
    $aReturn = [];

    if ($mY == '') {
        $mY = date('m-Y');

        // ยอดรวม all-time
        $sql = "SELECT SUM(count) FROM `" . _DBPREFIX_ . "site_counter`
                WHERE page='' AND type=:t";
        $aReturn['all'] = (int)$db->getOne($sql, [':t' => $IntraInter]);

        // ยอดรายปี
        $sql  = "SELECT year, SUM(count) as total FROM `" . _DBPREFIX_ . "site_counter`
                 WHERE page='' AND type=:t GROUP BY year ORDER BY year ASC";
        $rows = $db->getAll($sql, [':t' => $IntraInter]);
        $aReturn['year'] = [];
        foreach ((array)$rows as $r) {
            $aReturn['year'][(int)$r['year']] = (int)$r['total'];
        }
    }

    // parse 'm-Y'
    $parts = explode('-', $mY);
    $month = (int)($parts[0] ?? date('m'));
    $year  = (int)($parts[1] ?? date('Y'));

    // ยอดรายวัน — GROUP BY day เพื่อ aggregate ข้าม hour+device
    $sql  = "SELECT day, SUM(count) as total FROM `" . _DBPREFIX_ . "site_counter`
             WHERE page='' AND type=:t AND year=:y AND month=:m GROUP BY day";
    $rows = $db->getAll($sql, [':t' => $IntraInter, ':y' => $year, ':m' => $month]);

    $aReturn['month'] = [];
    $monthTotal       = 0;
    foreach ((array)$rows as $r) {
        $count = (int)$r['total'];
        $aReturn['month'][(int)$r['day']] = $count;
        $monthTotal += $count;
    }
    $aReturn['month'][0] = $monthTotal;

    $aReturnGlobal[$cacheKey] = $aReturn;
    return $aReturn;
}

/**
 * TODO: [x] อ่านข้อมูล per-page counter รายปี — return format เหมือนเดิม
 *
 * @param  int|string $year  ปีที่ต้องการ ('' = ปีปัจจุบัน)
 * @return array  ['page.html' => [year => [month => count]], ...] เรียงยอดรวมมากสุดก่อน
 */
function func_counter_page_get($year = '')
{
    if (!_counter_table_exists()) return [];
    $year = ($year == '') ? (int)date('Y') : (int)$year;
    $db   = DB::singleton();

    // GROUP BY page, month เพื่อ aggregate ข้าม hour+device
    $sql  = "SELECT page, month, SUM(count) as total
             FROM `" . _DBPREFIX_ . "site_counter`
             WHERE page != '' AND type='web' AND year=:y
             GROUP BY page, month";
    $rows = $db->getAll($sql, [':y' => $year]);

    $a = [];
    foreach ((array)$rows as $r) {
        $pg = $r['page'];
        $mo = (int)$r['month'];
        if (!isset($a[$pg][$year])) {
            $a[$pg][$year] = [];
        }
        $a[$pg][$year][$mo] = (int)$r['total'];
    }

    uasort($a, function ($ax, $bx) use ($year) {
        return array_sum($bx[$year] ?? []) - array_sum($ax[$year] ?? []);
    });

    return $a;
}
