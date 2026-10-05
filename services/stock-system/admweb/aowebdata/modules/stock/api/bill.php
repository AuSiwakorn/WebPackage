<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/bill.php
 * ROLE: เลขที่บิลและ VAT · หัวบิล / พิมพ์บิล · ค่าตั้งของฝ่ายบัญชี · สรุปบิลขายและเงินเข้า (บัญชีรายวัน / รายเดือน / CSV)
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_branch (ค่าตั้งบิล), ao_stock_doc_seq, ao_stock_sale, ao_stock_return
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *
 * ⚠ ไฟล์นี้ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb — มีได้แค่ define() และประกาศ function (ห้าม echo / header / query ตอนโหลด)
 */

/* ---------- บิลและ VAT ---------- */
define('VAT_RATE', 7);
define('BILL_RUN_DIGITS', 4);

/* ==========================================================
   เลขที่บิลและ VAT (ฝ่ายบัญชีตั้งค่าได้)
   ----------------------------------------------------------
   - ตอนขาย พนักงานเลือกได้ว่าบิลนี้ "VAT" หรือ "ไม่ VAT"
   - บิล VAT กับบิลไม่ VAT ใช้เลขคนละชุด แต่ละสาขามีรหัสนำหน้าของตัวเอง 2 ตัว
       รูปแบบ  {รหัส}{ปี ค.ศ.}-{เดือน}-{เลขรัน 4 หลัก}   เช่น BP2026-01-0001
       เลขรันนับใหม่ทุกเดือน แยกตามสาขาและตามชุด (VAT / ไม่ VAT)
   - ราคาขายเป็นราคารวม VAT แล้ว → บิล VAT แยกยอดก่อน VAT และ VAT 7% ให้
   - ฝ่ายบัญชีตั้งรหัสนำหน้า + เลขผู้เสียภาษีของแต่ละสาขาได้เอง (หน้า "ตั้งค่าบัญชี")

   เก็บในคอลัมน์ของ ao_stock_branch (acct_setting_col) · เลขที่บิลออกตอนบันทึกบิล (stock_doc_seq) แล้วเก็บไว้ในบิล
   เปลี่ยนรหัสนำหน้ากลางเดือน → บิลใบถัดไปใช้รหัสใหม่ เลขรันนับต่อจากเดิม (ไม่ย้อนแก้บิลเก่า)
   ========================================================== */

/** ชื่อค่าตั้งบัญชี / หัวบิล → คอลัมน์ใน ao_stock_branch ('' = ไม่มีค่านี้)
    TODO:
      - [x] เดิมเก็บใน $_SESSION['cfg']['acct'] */
function acct_setting_col($key)
{
    $map = array(
        'prefix_vat'   => 'prefix_vat',
        'prefix_novat' => 'prefix_novat',
        'tax_id'       => 'tax_id',
        'tax_branch'   => 'tax_branch',
        'company'      => 'bill_company',
        'bill_address' => 'bill_address',
        'bill_phone'   => 'bill_phone',
        'bill_extra'   => 'bill_extra',
        'title_vat'    => 'bill_title_vat',
        'title_novat'  => 'bill_title_novat',
        'footer'       => 'receipt_footer',
        'paper'        => 'bill_paper',
        'novat_tax'    => 'bill_novat_tax',
    );
    return isset($map[$key]) ? $map[$key] : '';
}

/** ค่าตั้งบัญชี / หัวบิลของสาขา (string เสมอ — novat_tax = '1' / '0') */
function acct_setting($code, $key)
{
    $col = acct_setting_col($key);
    $row = branch_row($code);
    return ($col !== '' && $row !== null && isset($row[$col])) ? (string) $row[$col] : '';
}

/**
 * สิ่งที่พิมพ์บนบิล (ตั้งได้ทุกสาขาที่หน้า "ตั้งค่าเลขที่บิล" ของฝ่ายบัญชี)
 *   company      ชื่อผู้ประกอบการ / ชื่อร้าน (หัวบิล)
 *   bill_address ที่อยู่บนบิล — ว่าง = ใช้ที่อยู่สาขาจากหน้า "จัดการสาขา"
 *   bill_phone   เบอร์โทรบนบิล — ว่าง = ใช้เบอร์สาขา
 *   bill_extra   บรรทัดเสริมใต้ที่อยู่ เช่น LINE / เว็บไซต์ (ไม่บังคับ)
 *   title_vat    หัวกระดาษของบิล VAT
 *   title_novat  หัวกระดาษของบิลไม่ VAT
 *   footer       ข้อความท้ายบิล (ขึ้นบรรทัดใหม่ได้)
 *   paper        ขนาดกระดาษเริ่มต้น: 80 = เครื่องพิมพ์ใบเสร็จ 80 มม. · a4 = A4
 *   novat_tax    พิมพ์เลขผู้เสียภาษีบนบิลไม่ VAT ด้วยหรือไม่ (1 / 0)
 * TODO:
 *   - [x] เหลือแค่รายชื่อค่า — ค่าเริ่มต้นอยู่ที่ DEFAULT ของคอลัมน์ใน database.php (เดิม bill_head_defaults)
 */
function bill_head_keys()
{
    return array('company', 'bill_address', 'bill_phone', 'bill_extra', 'title_vat', 'title_novat', 'footer', 'paper', 'novat_tax');
}

/** ข้อมูลหัวบิลที่จะพิมพ์จริงของสาขา (ที่อยู่ / เบอร์ว่าง → ใช้ของสาขา) */
function bill_head($code)
{
    $br = branches_all();
    $b  = isset($br[$code]) ? $br[$code] : array('name' => $code, 'address' => '', 'phone' => '');
    $h  = array();
    foreach (bill_head_keys() as $k) {
        $h[$k] = acct_setting($code, $k);
    }
    $h['branch_name'] = $b['name'];
    if (trim($h['bill_address']) === '') {
        $h['bill_address'] = isset($b['address']) ? $b['address'] : '';
    }
    if (trim($h['bill_phone']) === '') {
        $h['bill_phone'] = isset($b['phone']) ? $b['phone'] : '';
    }
    $h['tax_id']     = acct_setting($code, 'tax_id');
    $h['tax_branch'] = acct_setting($code, 'tax_branch');
    return $h;
}

/** เลขผู้เสียภาษีแบบอ่านง่าย 0-1055-66012-34-5 */
function tax_id_format($id)
{
    $id = preg_replace('/\D/', '', (string) $id);
    if (strlen($id) !== 13) {
        return $id;
    }
    return substr($id, 0, 1) . '-' . substr($id, 1, 4) . '-' . substr($id, 5, 5) . '-' . substr($id, 10, 2) . '-' . substr($id, 12, 1);
}

/** ข้อความสาขาตามแบบกรมสรรพากร: 00000 = สำนักงานใหญ่ */
function tax_branch_label($no)
{
    return $no === '00000' ? 'สำนักงานใหญ่' : 'สาขาที่ ' . $no;
}

/** หาบิลจากเลขที่ของวันหนึ่ง (รวมบิลที่ยกเลิก — หน้าพิมพ์แสดงตราว่ายกเลิก)
    TODO:
      - [x] อ่านจาก ao_stock_sale */
function bill_find($code, $ts, $no)
{
    $r = sale_bills_query('b.code = ? AND s.doc_no = ? AND s.sale_date = ?', array((string) $code, (string) $no, date('Y-m-d', $ts)));
    return $r ? bill_print_fill($r[0]) : null;
}

/** เติมค่าที่บิลตัวอย่างไม่มี (ราคาเต็ม / ส่วนลด / รับเงิน / เงินทอน) ให้พิมพ์ได้ครบ */
function bill_print_fill($b)
{
    if (!isset($b['subtotal'])) {
        $b['subtotal'] = $b['total'];
    }
    if (!isset($b['discount'])) {
        $b['discount'] = 0;
    }
    if (!isset($b['received'])) {
        /* บิลสมมติ: ลูกค้าเงินสดจ่ายเป็นแบงก์ร้อยปัดขึ้น */
        $b['received'] = $b['method'] === 'cash' ? ceil($b['total'] / 100) * 100 : $b['total'];
    }
    $b['change'] = $b['received'] - $b['total'];
    return $b;
}

/** บิลตัวอย่างสำหรับดูหน้าตาจากหน้าตั้งค่า (ไม่ใช่บิลจริง ไม่มีเลขรัน) */
function bill_sample($code, $vat)
{
    $prods = products_list();
    $lines = array();
    foreach (array(0 => 1, 7 => 2, 13 => 1) as $i => $q) {
        $p = $prods[$i % count($prods)];
        $price = product_price($p);
        $lines[] = array('sku' => $p['sku'], 'name' => $p['name'], 'unit' => $p['unit'], 'qty' => $q, 'price' => $price, 'sum' => $price * $q);
    }
    $sub = 0;
    $qty = 0;
    foreach ($lines as $l) {
        $sub += $l['sum'];
        $qty += $l['qty'];
    }
    return bill_print_fill(array(
        'no' => bill_no_format(bill_prefix($code, $vat), time(), 1), 'vat' => (bool) $vat, 'date' => date('Ymd'),
        'time' => date('H:i'), 'branch' => $code, 'by' => 'พนักงานตัวอย่าง', 'method' => 'cash',
        'lines' => $lines, 'items' => count($lines), 'qty' => $qty,
        'subtotal' => $sub, 'discount' => 20, 'total' => $sub - 20, 'sample' => true,
    ));
}

/** จำนวนเงินเป็นตัวอักษรไทย เช่น 1,250.50 → หนึ่งพันสองร้อยห้าสิบบาทห้าสิบสตางค์ */
function thai_baht_text($amount)
{
    $amount = round((float) $amount, 2);
    $baht   = (int) floor($amount);
    $satang = (int) round(($amount - $baht) * 100);
    $out = ($baht > 0 ? thai_number_text($baht) . 'บาท' : ($satang > 0 ? '' : 'ศูนย์บาท'));
    return $out . ($satang > 0 ? thai_number_text($satang) . 'สตางค์' : 'ถ้วน');
}

/** ตัวเลขจำนวนเต็มเป็นคำอ่านไทย (รองรับหลักล้านซ้อน) */
function thai_number_text($n)
{
    $n = (int) $n;
    if ($n === 0) {
        return 'ศูนย์';
    }
    if ($n >= 1000000) {
        $rest = $n % 1000000;
        return thai_number_text((int) floor($n / 1000000)) . 'ล้าน' . ($rest === 1 ? 'เอ็ด' : ($rest ? thai_number_text($rest) : ''));
    }
    $digit = array('', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า');
    $place = array('', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน');
    $s   = (string) $n;
    $len = strlen($s);
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $d   = (int) $s[$i];
        $pos = $len - $i - 1;
        if ($d === 0) {
            continue;
        }
        if ($pos === 1 && $d === 1) {
            $out .= 'สิบ';
        } elseif ($pos === 1 && $d === 2) {
            $out .= 'ยี่สิบ';
        } elseif ($pos === 0 && $d === 1 && $len > 1) {
            $out .= 'เอ็ด';
        } else {
            $out .= $digit[$d] . $place[$pos];
        }
    }
    return $out;
}

/** ลิงก์หน้าพิมพ์บิล */
function bill_print_url($code, $ts, $no, $more = array())
{
    return 'bill-print.php?' . http_build_query(array_merge(array('b' => $code, 'd' => date('Ymd', $ts), 'no' => $no), $more));
}

function acct_setting_set($code, $key, $val)
{
    $col = acct_setting_col($key);
    if ($col === '') {
        return false;
    }
    sdb_update('branch', array($col => (string) $val), array('code' => $code));
    branch_db_rows(true);
    return true;
}

/** รหัสนำหน้าของชุดเลข: $vat = true → ชุด VAT */
function bill_prefix($code, $vat)
{
    return acct_setting($code, $vat ? 'prefix_vat' : 'prefix_novat');
}

/** ประกอบเลขที่บิล เช่น BP2026-01-0001 */
function bill_no_format($prefix, $ts, $n)
{
    return $prefix . date('Y', $ts) . '-' . date('m', $ts) . '-' . str_pad($n, BILL_RUN_DIGITS, '0', STR_PAD_LEFT);
}

/** แยกยอดรวม (รวม VAT แล้ว) เป็น array(ก่อน VAT, VAT) */
function vat_split($total)
{
    $vat = round($total * VAT_RATE / (100 + VAT_RATE), 2);
    return array(round($total - $vat, 2), $vat);
}

/** ชื่อชุดเลข */
function bill_type_label($vat)
{
    return $vat ? 'VAT' : 'ไม่ VAT';
}

/** เลขที่บิลใบถัดไปของเดือนนี้ (แสดงตัวอย่างเท่านั้น — เลขจริงออกตอนบันทึกบิลใน bill_save)
    เลขของบิลที่ถูกยกเลิกไม่นำมาใช้ซ้ำ
    TODO:
      - [x] อ่านตัวนับจาก ao_stock_doc_seq (ชุด vat / novat · งวด YYYYMM) — เดิมนับบิลสมมติ + session */
function bill_next_no_series($code, $vat)
{
    $n = (int) sdb_val('SELECT last_no FROM ' . sdb_tb('doc_seq') . ' WHERE branch_id = ? AND series = ? AND period = ?',
                       array(branch_id_of($code), $vat ? 'vat' : 'novat', date('Ym')));
    return bill_no_format(bill_prefix($code, $vat), time(), $n + 1);
}

/** ตรวจรหัสนำหน้า: A–Z / 0–9 ยาว 1–6 ตัว และไม่ซ้ำกับชุดอื่นทุกสาขา */
function acct_prefix_error($code, $key, $val, $all)
{
    if (!preg_match('/^[A-Z0-9]{1,6}$/', $val)) {
        return 'รหัสต้องเป็นตัวอักษรภาษาอังกฤษพิมพ์ใหญ่หรือตัวเลข 1–6 ตัว';
    }
    foreach ($all as $c => $pp) {
        foreach ($pp as $k => $v) {
            if (($c !== $code || $k !== $key) && $v === $val) {
                return 'รหัส ' . $val . ' ซ้ำกับชุดเลขอื่น (' . branch_name($c) . ' · ' . ($k === 'prefix_vat' ? 'VAT' : 'ไม่ VAT') . ')';
            }
        }
    }
    return '';
}

/* ==========================================================
   ข้อมูลของฝ่ายบัญชี
   บิลขายรายวัน (แยก VAT / ไม่ VAT) และเงินเข้าแยกสาขา — อ่านจาก ao_stock_sale / ao_stock_return
   TODO:
     - [x] ช่วงที่ 8: สรุปรายเดือน (acct_month → acct_range) / CSV ทั้งเดือน (acct_bills_range) เป็น SQL ทั้งช่วง แทนการวนทีละวัน
   ========================================================== */

/** บิลทั้งหมดของสาขาในวันหนึ่ง รวมบิลที่ยกเลิก (บัญชีต้องเห็นเลขที่ครบทุกใบ) เรียงตามเวลา
    TODO:
      - [x] อ่านจาก ao_stock_sale (เดิม session + ข้อมูลสมมติ) */
function acct_bills($code, $ts)
{
    return sale_bills_query('b.code = ? AND s.sale_date = ?', array((string) $code, date('Y-m-d', $ts)));
}

/** เงินสดที่คืนลูกค้าในวันหนึ่งของสาขา (ใบรับคืนที่ออกวันนั้น) */
function acct_refunds($code, $ts)
{
    return (float) sdb_val('SELECT COALESCE(SUM(r.refund), 0) FROM ' . sdb_tb('return') . ' r JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = r.branch_id'
                         . ' WHERE b.code = ? AND r.doc_date = ?', array((string) $code, date('Y-m-d', $ts)));
}

/** เพิ่มยอดก่อน VAT / VAT ให้บิล */
function acct_bill_row($b, $code)
{
    $vs = $b['vat'] ? vat_split($b['total']) : array($b['total'], 0);
    $b['branch'] = $code;
    $b['base']   = $vs[0];
    $b['vatamt'] = $vs[1];
    return $b;
}

/**
 * สรุปของสาขาในวันหนึ่ง
 * คืน array(bills, v, n, void, total, base, vat, cash, transfer, refund, net, rows)
 */
function acct_day($code, $ts)
{
    $s = array('bills' => 0, 'v' => 0, 'n' => 0, 'void' => 0, 'total' => 0, 'base' => 0, 'vat' => 0,
               'cash' => 0, 'transfer' => 0, 'refund' => 0, 'net' => 0, 'rows' => array());
    foreach (acct_bills($code, $ts) as $b) {
        $b = acct_bill_row($b, $code);
        $s['rows'][] = $b;
        $s['bills']++;
        $s[$b['vat'] ? 'v' : 'n']++;
        if (!empty($b['void'])) {
            $s['void']++;
            continue;                                   // บิลยกเลิก: นับใบ แต่ไม่นับเงิน
        }
        $s['total'] += $b['total'];
        $s['base']  += $b['vat'] ? $b['base'] : 0;     // มูลค่าก่อน VAT นับเฉพาะบิล VAT
        $s['vat']   += $b['vatamt'];
        $s[$b['method'] === 'cash' ? 'cash' : 'transfer'] += $b['total'];
    }
    $s['refund'] = acct_refunds($code, $ts);                // ทุกวัน (เดิมมีเฉพาะวันนี้)
    $s['net'] = $s['cash'] + $s['transfer'] - $s['refund'];
    return $s;
}

/** สรุปของสาขาช่วง $from–$to แบบเดียวกับ acct_day แต่ไม่มีรายการบิล — SQL 2 คำสั่ง (บิล + เงินคืน)
    คืน array(bills, v, n, void, total, base, vat, cash, transfer, refund, net)
    · bills / v / n นับรวมบิลที่ยกเลิก (เลขที่ต้องครบ) · ยอดเงินไม่นับบิลที่ยกเลิก · ก่อน VAT / VAT ใช้ค่าที่เก็บในบิล VAT
    TODO:
      - [x] ช่วงที่ 8: แทนการวน acct_day ทีละวัน */
function acct_range($code, $from, $to)
{
    $p   = array((string) $code, date('Y-m-d', $from), date('Y-m-d', $to));
    $r   = sdb_row('SELECT COUNT(*) AS bills, COALESCE(SUM(s.is_vat = 1), 0) AS v, COALESCE(SUM(s.is_vat = 0), 0) AS n,'
                 . ' COALESCE(SUM(s.status = \'void\'), 0) AS void_n,'
                 . ' COALESCE(SUM(CASE WHEN s.status = \'paid\' THEN s.total ELSE 0 END), 0) AS total,'
                 . ' COALESCE(SUM(CASE WHEN s.status = \'paid\' AND s.is_vat = 1 THEN s.base_amount ELSE 0 END), 0) AS base,'
                 . ' COALESCE(SUM(CASE WHEN s.status = \'paid\' AND s.is_vat = 1 THEN s.vat_amount ELSE 0 END), 0) AS vat,'
                 . ' COALESCE(SUM(CASE WHEN s.status = \'paid\' AND s.pay_method = \'cash\' THEN s.total ELSE 0 END), 0) AS cash,'
                 . ' COALESCE(SUM(CASE WHEN s.status = \'paid\' AND s.pay_method <> \'cash\' THEN s.total ELSE 0 END), 0) AS transfer'
                 . ' FROM ' . sdb_tb('sale') . ' s JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = s.branch_id'
                 . ' WHERE b.code = ? AND s.sale_date BETWEEN ? AND ?', $p);
    $ref = (float) sdb_val('SELECT COALESCE(SUM(r.refund), 0) FROM ' . sdb_tb('return') . ' r JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = r.branch_id'
                         . ' WHERE b.code = ? AND r.doc_date BETWEEN ? AND ?', $p);
    $m = array('bills' => (int) $r['bills'], 'v' => (int) $r['v'], 'n' => (int) $r['n'], 'void' => (int) $r['void_n'],
               'total' => (float) $r['total'], 'base' => (float) $r['base'], 'vat' => (float) $r['vat'],
               'cash' => (float) $r['cash'], 'transfer' => (float) $r['transfer'], 'refund' => $ref);
    $m['net'] = $m['cash'] + $m['transfer'] - $m['refund'];
    return $m;
}

/** สรุปทั้งเดือน (ตั้งแต่วันที่ 1 ถึงวันนี้หรือสิ้นเดือน) ของสาขา
    TODO:
      - [x] ช่วงที่ 8: SQL ทั้งเดือน (acct_range) */
function acct_month($code, $ts)
{
    return acct_range($code, strtotime(date('Y-m-01', $ts)), min(strtotime(date('Y-m-t', $ts)), strtotime(date('Y-m-d'))));
}

/** บิลทั้งหมด (รวมที่ยกเลิก) ของหลายสาขา ช่วง $from–$to — เรียงวัน → ลำดับสาขา → เวลา (ไฟล์ CSV ของฝ่ายบัญชี)
    TODO:
      - [x] ช่วงที่ 8: คิวรีเดียวทั้งเดือน (เดิมวน acct_bills ทีละวันทีละสาขา) */
function acct_bills_range($codes, $from, $to)
{
    list($in, $params) = sdb_in('b.code', $codes);
    array_unshift($params, date('Y-m-d', $from), date('Y-m-d', $to));
    return sale_bills_query('s.sale_date BETWEEN ? AND ? AND ' . $in, $params, 's.sale_date, b.sort, b.branch_id, s.add_date, s.sale_id');
}

function acct_row_cmp($a, $b)
{
    $c = strcmp($a['branch'], $b['branch']);
    if ($c !== 0) {
        return $c;
    }
    $c = strcmp($a['vat'] ? '1' : '0', $b['vat'] ? '1' : '0');
    return $c !== 0 ? $c : strcmp($a['no'], $b['no']);
}

/** รหัสเลขที่บิลนี้ถูกใช้แล้วหรือยัง (ทุกสาขา ทุกชุด) */
function prefix_in_use($p)
{
    foreach (array_keys(branches_all()) as $c) {
        if (acct_setting($c, 'prefix_vat') === $p || acct_setting($c, 'prefix_novat') === $p) {
            return true;
        }
    }
    return false;
}
