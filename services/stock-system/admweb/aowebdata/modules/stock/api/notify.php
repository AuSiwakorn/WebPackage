<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/notify.php
 * ROLE: การแจ้งเตือน — ค่าตั้ง · Telegram ตอนเกิดเหตุการณ์จริง (คิว + ส่งตอนจบ request) · อีเมลสรุปยอดขายรายวัน (SMTP) · ประวัติการส่ง · งานของ cron
 * DEPENDS: โหลดโดย admweb/aowebdata/modules/stock/api.php (ตัวโหลด — ห้าม include ไฟล์นี้ตรง ๆ) · ใช้ function จากไฟล์อื่นใน api/ ได้ทุกตัว
 * TABLES: ao_stock_setting, ao_stock_notify_log · ao_stock_sale / return / store_day / issue / log (ข้อมูลของข้อความ)
 * TODO:
 *   - [x] ช่วงที่ 10: แยกจาก api.php เดิม (ย้ายโค้ดทั้งก้อน ไม่แก้ตรรกะ) · จัดฟังก์ชันที่เคยปนอยู่หมวดอื่นให้มาอยู่หมวดนี้
 *
 * ⚠ ไฟล์นี้ถูกโหลดในทุก request ฝั่งหน้าเว็บของ admweb — มีได้แค่ define() และประกาศ function (ห้าม echo / header / query ตอนโหลด)
 */

/* ==========================================================
   การแจ้งเตือน Telegram / อีเมลสรุปยอดรายวัน (หน้า adm-notify.php)
   ----------------------------------------------------------
   ค่าตั้งอยู่ใน ao_stock_setting (notify_get / notify_set) · token Telegram และรหัสผ่าน SMTP เข้ารหัสไว้
   ประวัติการส่งทุกครั้ง (สำเร็จ / ไม่สำเร็จ / ข้าม) อยู่ใน ao_stock_notify_log
   Telegram: เหตุการณ์จริงเรียก notify_event() หลังบันทึกลงฐานข้อมูลแล้ว → เข้าคิว → ส่งตอนจบ request (notify_flush)
             PHP-FPM ตอบหน้าเว็บกลับไปก่อน (fastcgi_finish_request) แล้วค่อยส่ง · ต่อ Telegram ไม่ได้ = ข้ามข้อความที่เหลือของรอบนั้น
   อีเมลรายวัน: cron.php (Cron Jobs ของโฮสต์ ทุก 5 นาที) → notify_cron_run() ส่งสรุปของวันนี้เมื่อเลยเวลาที่ตั้ง วันละครั้ง
   กลุ่มฟีเจอร์ notify ถูกปิดจากหลังบ้าน = ไม่ส่งอะไรเลย
   ========================================================== */

/** ค่าเริ่มต้นของการแจ้งเตือน */
function notify_defaults()
{
    return array(
        'tg_on'         => '0',
        'tg_token'      => '',
        'tg_chats'      => '',
        'tg_events'     => array('close', 'cash_diff', 'void', 'refund', 'reopen', 'lost'),
        'tg_branches'   => array(),             // ว่าง = ทุกสาขา
        'tg_silent'     => '0',                 // 1 = ส่งแบบไม่มีเสียง
        'mail_on'       => '0',
        'mail_to'       => '',
        'mail_time'     => '21:00',
        'mail_days'     => 'open',              // open = เฉพาะวันที่มียอดขาย · all = ทุกวัน
        'mail_branches' => array(),
        'mail_parts'    => array('pay', 'vat', 'refund', 'cash', 'top', 'month'),
        'smtp_host'     => '',
        'smtp_port'     => '587',
        'smtp_secure'   => 'tls',               // tls | ssl | none
        'smtp_user'     => '',
        'smtp_pass'     => '',
        'from_name'     => 'AOSTOCK รายงานยอดขาย',
        'from_email'    => '',
    );
}

/** คีย์ที่เป็นค่าลับ (เก็บแบบเข้ารหัส ไม่ส่งกลับไปแสดงบนหน้าเว็บ) */
function notify_secret_keys()
{
    return array('tg_token', 'smtp_pass');
}

/** ค่าตั้งการแจ้งเตือน — ค่าที่ค่าเริ่มต้นเป็น array คืนเป็น array (เก็บเป็น JSON)
    TODO:
      - [x] ช่วงที่ 9: อ่านจาก ao_stock_setting (เดิม $_SESSION['cfg']['notify']) */
function notify_get($k)
{
    $d   = notify_defaults();
    $def = isset($d[$k]) ? $d[$k] : '';
    $v   = stock_setting_get($k, null);
    if ($v === null) {
        return $def;
    }
    if (is_array($def)) {
        $a = json_decode($v, true);
        return is_array($a) ? array_values(array_map('strval', $a)) : $def;
    }
    return $v;
}

/** บันทึกค่าตั้งการแจ้งเตือน — ผู้แก้ = ผู้ใช้ที่เข้าระบบอยู่ (cron = 0)
    TODO:
      - [x] ช่วงที่ 9: เขียน ao_stock_setting · tg_token / smtp_pass เข้ารหัส */
function notify_set($k, $v)
{
    $u = current_user();
    stock_setting_set($k, is_array($v) ? json_encode(array_values($v)) : (string) $v,
                      in_array($k, notify_secret_keys(), true), $u ? stock_uid($u) : 0);
}

/** ส่งแจ้งเตือนได้ไหมโดยรวม — กลุ่มฟีเจอร์ notify ไม่ได้ถูกปิดจากหลังบ้าน */
function notify_enabled()
{
    return feature_group_on('notify');
}

/** เหตุการณ์ที่เลือกส่งเข้า Telegram ได้: key => array(ชื่อ, คำอธิบาย) */
function notify_tg_events()
{
    return array(
        'close'     => array('ปิดร้าน · สรุปยอดของสาขา', 'ยอดขาย จำนวนบิล เงินสด / โอน ทันทีที่สาขาปิดร้าน'),
        'cash_diff' => array('เงินในลิ้นชักขาด / เกิน', 'ตอนปิดร้านนับเงินได้ไม่ตรงกับที่ควรมี'),
        'void'      => array('ยกเลิกบิล / แก้เอกสาร', 'พร้อมเหตุผลและชื่อคนยกเลิก'),
        'refund'    => array('รับคืนสินค้า · คืนเงินสด', 'เลขที่บิลเดิม ยอดเงินคืน และเหตุผล'),
        'reopen'    => array('เปิดร้านใหม่หลังปิด', 'ผู้ดูแลเปิดร้านอีกครั้งหลังปิดไปแล้ว'),
        'lost'      => array('ตัดออกเพราะสูญหาย / ชำรุด', 'ใบเบิก/ตัดออกที่เหตุผลเป็นของหายหรือเสียหาย'),
        'open'      => array('เปิดร้าน', 'เวลาเปิดร้านและเงินทอนเริ่มวันของแต่ละสาขา'),
        'low'       => array('สินค้าใกล้หมด / หมด', 'วันละครั้งตอนสาขาเปิดร้าน เฉพาะสินค้าที่ถึงจุดสั่งซื้อ'),
        'backdate'  => array('ยกเลิกเอกสารย้อนหลัง', 'ใบรับเข้า / เบิก / ตรวจนับของวันก่อนถูกยกเลิก'),
    );
}

/** ส่วนที่เลือกใส่ในอีเมลสรุปรายวันได้ (ยอดขายแยกสาขามีเสมอ) */
function notify_mail_parts()
{
    return array(
        'pay'    => array('เงินสด / โอน แยกช่องทาง', ''),
        'vat'    => array('บิล VAT / ไม่ VAT และยอด VAT', ''),
        'refund' => array('รับคืนสินค้า · เงินคืน', ''),
        'cash'   => array('เงินขาด / เกินตอนปิดร้าน', ''),
        'top'    => array('สินค้าขายดี 5 อันดับของวัน', ''),
        'month'  => array('ยอดสะสมตั้งแต่ต้นเดือน', ''),
        'low'    => array('สินค้าใกล้หมด / หมด', ''),
    );
}

/** ซ่อนค่าลับ เหลือ 4 ตัวท้าย */
function mask_secret($v)
{
    $v = (string) $v;
    if ($v === '') {
        return '';
    }
    return str_repeat('•', 8) . substr($v, -4);
}

/** ตรวจรูปแบบ Bot token ของ Telegram เช่น 123456789:AA… */
function tg_token_valid($t)
{
    return (bool) preg_match('/^\d{6,12}:[A-Za-z0-9_-]{30,50}$/', $t);
}

/** แยก Chat ID หลายค่า (คั่นด้วยบรรทัด / จุลภาค / ช่องว่าง) คืน array(ids, ค่าที่ผิด) */
function tg_chats_parse($s)
{
    $ok  = array();
    $bad = array();
    foreach (preg_split('/[\s,]+/', trim((string) $s)) as $c) {
        if ($c === '') {
            continue;
        }
        if (preg_match('/^(-?\d{4,20}|@[A-Za-z][A-Za-z0-9_]{4,31})$/', $c)) {
            $ok[$c] = $c;
        } else {
            $bad[] = $c;
        }
    }
    return array(array_values($ok), $bad);
}

/** แยกอีเมลหลายรายการ คืน array(อีเมล, ค่าที่ผิด) */
function mail_list_parse($s)
{
    $ok  = array();
    $bad = array();
    foreach (preg_split('/[\s,;]+/', trim((string) $s)) as $m) {
        if ($m === '') {
            continue;
        }
        if (filter_var($m, FILTER_VALIDATE_EMAIL)) {
            $ok[strtolower($m)] = $m;
        } else {
            $bad[] = $m;
        }
    }
    return array(array_values($ok), $bad);
}

/** เรียก Telegram Bot API · คืน array(ok, ข้อมูล | ข้อความผิดพลาด, เชื่อมต่อไม่ได้เลย true/false)
    $timeout = วินาทีรวม (ต่อสาย = ครึ่งหนึ่ง) · แจ้งเหตุการณ์ใช้ 5 วินาที ไม่ให้หน้าร้านรอนาน
    AOSTOCK_TG_API (ตั้งใน fix.localhost.php เท่านั้น) = ส่งไปเซิร์ฟเวอร์จำลองตอนทดสอบในเครื่อง
    TODO:
      - [x] ช่วงที่ 9: timeout ปรับได้ · บอกว่าเชื่อมต่อไม่ได้ (ใช้ข้ามข้อความที่เหลือของรอบ) */
function tg_api($token, $method, $params = array(), $timeout = 10)
{
    $base = defined('AOSTOCK_TG_API') ? rtrim((string) AOSTOCK_TG_API, '/') : 'https://api.telegram.org';
    $url  = $base . '/bot' . $token . '/' . $method;
    $body = http_build_query($params);
    $raw  = false;
    $err  = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true,
                                     CURLOPT_CONNECTTIMEOUT => max(2, (int) ceil($timeout / 2)), CURLOPT_TIMEOUT => (int) $timeout));
        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
        }
        curl_close($ch);
    } else {
        $ctx = stream_context_create(array('http' => array('method' => 'POST', 'timeout' => (int) $timeout, 'ignore_errors' => true,
                                     'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => $body)));
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            $err = 'เชื่อมต่อ api.telegram.org ไม่ได้';
        }
    }
    if ($raw === false) {
        return array(false, 'ส่งไม่สำเร็จ: ' . ($err !== '' ? $err : 'เชื่อมต่อไม่ได้') . ' — ตรวจว่าเซิร์ฟเวอร์ออกอินเทอร์เน็ตได้', true);
    }
    $j = json_decode($raw, true);
    if (!is_array($j)) {
        return array(false, 'Telegram ตอบกลับผิดรูปแบบ');
    }
    if (empty($j['ok'])) {
        $d = isset($j['description']) ? $j['description'] : 'ไม่ทราบสาเหตุ';
        if (stripos($d, 'unauthorized') !== false) {
            $d = 'Bot token ไม่ถูกต้อง (Unauthorized)';
        } elseif (stripos($d, 'chat not found') !== false) {
            $d = 'ไม่พบ Chat ID นี้ — ต้องทักบอทก่อน หรือเพิ่มบอทเข้ากลุ่มก่อน';
        } elseif (stripos($d, 'bot was blocked') !== false) {
            $d = 'ผู้ใช้บล็อกบอทนี้อยู่';
        }
        return array(false, $d);
    }
    return array(true, isset($j['result']) ? $j['result'] : array());
}

/** หา Chat ID จากข้อความล่าสุดที่คนทักบอท (getUpdates) คืน array(ok, array(id => ชื่อ) | ข้อความผิดพลาด) */
function tg_find_chats($token)
{
    $r = tg_api($token, 'getUpdates', array('limit' => 50));
    if (!$r[0]) {
        return $r;
    }
    $out = array();
    foreach ($r[1] as $u) {
        foreach (array('message', 'channel_post', 'my_chat_member', 'edited_message') as $k) {
            if (!empty($u[$k]['chat']['id'])) {
                $c = $u[$k]['chat'];
                $nm = isset($c['title']) ? $c['title'] : trim((isset($c['first_name']) ? $c['first_name'] : '') . ' ' . (isset($c['last_name']) ? $c['last_name'] : ''));
                $out[(string) $c['id']] = ($nm !== '' ? $nm : (isset($c['username']) ? '@' . $c['username'] : 'แชต'))
                                         . ' · ' . ($c['type'] === 'private' ? 'แชตส่วนตัว' : ($c['type'] === 'channel' ? 'ช่อง' : 'กลุ่ม'));
            }
        }
    }
    return array(true, $out);
}

/** วันล่าสุดก่อนวันนี้ที่มีบิลขาย (ไว้ทำตัวอย่าง) — ยังไม่มีเลย = เมื่อวาน
    TODO:
      - [x] ช่วงที่ 8: SQL คำสั่งเดียว (เดิมวนย้อนทีละวัน 10 วัน) */
function notify_last_sales_day($codes)
{
    list($in, $params) = sdb_in('b.code', $codes);
    array_unshift($params, date('Y-m-d'));
    $d = sdb_val('SELECT MAX(s.sale_date) FROM ' . sdb_tb('sale') . ' s JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = s.branch_id'
               . ' WHERE s.sale_date < ? AND ' . $in, $params);
    return ($d !== null) ? strtotime($d) : strtotime('-1 day', strtotime(date('Y-m-d')));
}

/* ==========================================================
   ข้อความ Telegram ของแต่ละเหตุการณ์ (HTML แบบที่ Telegram รองรับ: b, i)
   ----------------------------------------------------------
   notify_tg_text($event, $d) สร้างข้อความจากข้อมูลชุด $d — ใช้ทั้งตอนเกิดเหตุการณ์จริง (notify_on_*)
   และตัวอย่างในหน้าตั้งค่า (notify_tg_sample — ใช้ข้อมูลล่าสุดที่มีจริงของสาขา ถ้ายังไม่มีใช้ค่าตัวอย่าง)
   $d ที่ใช้ร่วม: branch (ชื่อสาขา) · day (วันที่) · time (HH:MM) · by (ชื่อผู้ทำ)
   ========================================================== */

/** ข้อความของเหตุการณ์ — คืน '' ถ้าไม่รู้จักชนิด
    TODO:
      - [x] ช่วงที่ 9: ทุกชนิดสร้างจากข้อมูลจริง (เดิมตัวอย่างบางชนิดเป็นข้อความแต่งขึ้น) */
function notify_tg_text($event, $d)
{
    $e = function ($k) use ($d) { return htmlspecialchars(isset($d[$k]) ? (string) $d[$k] : '', ENT_QUOTES, 'UTF-8'); };
    $m = function ($k) use ($d) { return money2(isset($d[$k]) ? (float) $d[$k] : 0); };
    switch ($event) {
        case 'open':
            $t = "🟢 <b>เปิดร้าน · " . $e('branch') . "</b>\n📅 " . $e('day') . ' · ' . $e('time') . ' น. · ' . $e('by')
               . "\nเงินทอนเริ่มวัน <b>" . $m('float') . " บาท</b>";
            if (isset($d['counted']) && $d['counted'] !== null) {
                $t .= "\n⚠️ นับเงินทอนยกมาได้ " . $m('counted') . ' (ยกมา ' . $m('carry') . ')' . ($e('reason') !== '' ? ' — <i>' . $e('reason') . '</i>' : '');
            }
            if (!empty($d['unclosed'])) {
                $t .= "\n⚠️ " . $e('unclosed') . ' ยังไม่ได้ปิดร้าน';
            }
            return $t;
        case 'low':
            $items = isset($d['items']) ? $d['items'] : array();
            $t = "📉 <b>สินค้าใกล้หมด · " . $e('branch') . "</b> (" . number_format(count($items)) . " รายการ)\n📅 " . $e('day');
            foreach (array_slice($items, 0, 10) as $x) {
                $t .= "\n• " . htmlspecialchars($x['name'], ENT_QUOTES, 'UTF-8') . ' เหลือ ' . number_format($x['qty']) . ' '
                    . htmlspecialchars($x['unit'], ENT_QUOTES, 'UTF-8') . ($x['qty'] <= 0 ? ' (หมด)' : '');
            }
            if (count($items) > 10) {
                $t .= "\n… และอีก " . number_format(count($items) - 10) . ' รายการ';
            }
            return $t;
        case 'close':
            $diff = isset($d['diff']) ? (float) $d['diff'] : 0;
            $t = "🏪 <b>ปิดร้าน · " . $e('branch') . "</b>\n📅 " . $e('day') . ' · ' . $e('time') . ' น. · ปิดโดย ' . $e('by')
               . "\n\n💰 ยอดขาย <b>" . $m('total') . " บาท</b> (" . number_format((int) $d['bills']) . " บิล"
               . (!empty($d['void']) ? ' · ยกเลิก ' . number_format((int) $d['void']) : '') . ")"
               . "\n   เงินสด " . $m('cash') . ' · โอน ' . $m('transfer');
            if (!empty($d['refund'])) {
                $t .= "\n↩️ คืนเงินลูกค้า " . $m('refund') . ' บาท';
            }
            $t .= "\n🧾 ลิ้นชัก ควรมี " . $m('expect') . ' · นับได้ ' . $m('counted')
                . (abs($diff) < 0.005 ? ' ✅ ตรง' : "\n⚠️ เงิน" . ($diff < 0 ? 'ขาด ' : 'เกิน ') . money2(abs($diff)) . ' บาท')
                . "\n💼 นำส่ง " . $m('handover') . ' · เก็บเป็นเงินทอน ' . $m('keep');
            return $t;
        case 'cash_diff':
            $diff = isset($d['diff']) ? (float) $d['diff'] : 0;
            return "⚠️ <b>เงิน" . ($diff < 0 ? 'ขาด ' : 'เกิน ') . money2(abs($diff)) . " บาท · " . $e('branch') . "</b>\n📅 " . $e('day')
                 . ' · ปิดโดย ' . $e('by') . ' ' . $e('time') . ' น.'
                 . "\nควรมีในลิ้นชัก " . $m('expect') . ' · นับได้ ' . $m('counted')
                 . ($e('note') !== '' ? "\nหมายเหตุ: <i>" . $e('note') . '</i>' : '');
        case 'void':
            return "🚫 <b>" . (!empty($d['edit']) ? 'ยกเลิกเพื่อแก้ไข' : 'ยกเลิก') . $e('label') . ' ' . $e('no') . "</b>\n"
                 . $e('branch') . ' · ' . $e('by') . ' · ' . $e('time') . ' น.'
                 . "\n" . $e('amount') . "\nเหตุผล: <i>" . ($e('reason') !== '' ? $e('reason') : 'ไม่ได้ระบุ') . '</i>';
        case 'refund':
            return "↩️ <b>รับคืนสินค้า · คืนเงินสด " . $m('refund') . " บาท</b>\n" . $e('branch') . ' · ' . $e('no') . ' · บิลเดิม ' . $e('bill_no')
                 . "\nสินค้า: " . $e('items')
                 . "\nเหตุผล: <i>" . $e('reason') . '</i> · ' . (!empty($d['restock']) ? 'กลับเข้าสต๊อก' : 'ไม่เข้าสต๊อก')
                 . "\nโดย " . $e('by') . ' · ' . $e('time') . ' น.';
        case 'reopen':
            return "🔓 <b>เปิดร้านใหม่หลังปิด · " . $e('branch') . "</b>\nโดย " . $e('by') . ' · ' . $e('time') . ' น.'
                 . "\nปิดไปเมื่อ " . $e('closed_at') . ' น. โดย ' . $e('closed_by') . ' · นับเงินได้ ' . $m('counted')
                 . "\nเหตุผล: <i>" . ($e('reason') !== '' ? $e('reason') : 'ไม่ได้ระบุ') . '</i>';
        case 'lost':
            return "📦 <b>ตัดออก · " . $e('reason') . "</b>\n" . $e('branch') . ' · ' . $e('no')
                 . "\n" . $e('items') . "\nรวม " . number_format((int) $d['qty']) . ' ชิ้น · มูลค่าทุน ' . $m('cost') . ' บาท'
                 . "\nโดย " . $e('by') . ' · ' . $e('time') . ' น.' . ($e('note') !== '' ? "\nหมายเหตุ: <i>" . $e('note') . '</i>' : '');
        case 'backdate':
            return "⏪ <b>" . (!empty($d['edit']) ? 'ยกเลิกเพื่อแก้ไขย้อนหลัง ' : 'ยกเลิกย้อนหลัง ') . $e('label') . ' ' . $e('no') . "</b>\n"
                 . $e('branch') . ' · ใบของวัน' . $e('doc_day') . "\nเหตุผล: <i>" . $e('reason') . '</i> · โดย ' . $e('by') . ' · ' . $e('time') . ' น.';
        case 'test':
            return "✅ <b>ทดสอบการแจ้งเตือนจาก " . APP_NAME . "</b>\nตั้งค่าเรียบร้อย ข้อความแจ้งเตือนจะส่งมาที่แชตนี้\n"
                 . date('d/m/') . (date('Y') + 543) . ' ' . date('H:i') . ' น.';
    }
    return '';
}

/** ข้อมูลพื้นฐานของข้อความ: สาขา วันที่ เวลา ผู้ทำ */
function notify_d_base($code, $by = '', $ts = null)
{
    $ts = ($ts === null) ? time() : $ts;
    return array('branch' => branch_name($code), 'day' => thai_date_full($ts), 'time' => date('H:i', $ts), 'by' => $by);
}

/** ชื่อชนิดเอกสารในข้อความ */
function notify_doc_label($kind)
{
    $l = array('SA' => 'บิล', 'RC' => 'ใบรับเข้า', 'IS' => 'ใบตัดออก', 'AD' => 'ใบตรวจนับ');
    return isset($l[$kind]) ? $l[$kind] : 'เอกสาร';
}

/** รายชื่อสินค้าแบบสั้นในข้อความ — "ชื่อ ×จำนวน · …" ไม่เกิน 5 รายการ */
function notify_items_text($lines, $qtyKey = 'qty')
{
    $out = array();
    foreach (array_slice($lines, 0, 5) as $l) {
        $out[] = $l['name'] . ' ×' . number_format((int) $l[$qtyKey]);
    }
    return implode(' · ', $out) . (count($lines) > 5 ? ' · และอีก ' . (count($lines) - 5) . ' รายการ' : '');
}

/** ข้อมูลข้อความเปิดร้าน / ปิดร้าน / เงินขาดเกินของวันหนึ่ง (จาก ao_stock_store_day + บิลของวันนั้น) — ไม่มีการเปิดร้าน = null */
function notify_d_store($code, $ts)
{
    $sd = store_day_result($code, $ts);
    if ($sd === null) {
        return null;
    }
    $s = acct_day($code, $ts);
    $d = notify_d_base($code, $sd['closed'] ? $sd['closed_by'] : $sd['opened_by'], $ts);
    return array_merge($d, array(
        'open_time' => $sd['opened_at'], 'open_by' => $sd['opened_by'], 'float' => $sd['float'], 'closed' => $sd['closed'],
        'time' => $sd['closed'] ? $sd['closed_at'] : $sd['opened_at'],
        'total' => $s['total'], 'bills' => $s['bills'] - $s['void'], 'void' => $s['void'], 'cash' => $s['cash'],
        'transfer' => $s['transfer'], 'refund' => $s['refund'], 'expect' => $sd['expect'], 'counted' => $sd['counted'],
        'diff' => $sd['diff'], 'keep' => $sd['keep'], 'handover' => $sd['handover'], 'note' => $sd['note'],
    ));
}

/* ---------- คิวการส่ง ---------- */

/** เหตุการณ์นี้ของสาขานี้ต้องส่งเข้า Telegram ไหม (เปิดใช้ · เลือกเหตุการณ์ไว้ · สาขาอยู่ในขอบเขต · กลุ่มฟีเจอร์ไม่ถูกปิด)
    TODO:
      - [x] ช่วงที่ 9 */
function notify_wants($event, $code)
{
    if (!notify_enabled() || notify_get('tg_on') !== '1' || !in_array($event, notify_get('tg_events'), true)) {
        return false;
    }
    $brs = notify_get('tg_branches');
    return !$brs || in_array((string) $code, $brs, true);
}

/** แจ้งเหตุการณ์เข้า Telegram — เข้าคิวไว้ ส่งตอนจบ request (หลังบันทึกลงฐานข้อมูลแล้ว) · คืน true ถ้าเข้าคิว
    TODO:
      - [x] ช่วงที่ 9: เรียกจาก notify_on_* หลัง transaction สำเร็จ */
function notify_event($event, $code, $text, $ref = '')
{
    if ($text === '' || !notify_wants($event, $code)) {
        return false;
    }
    $u = current_user();
    notify_queue(array('event' => $event, 'code' => (string) $code, 'text' => $text, 'ref' => (string) $ref, 'uid' => $u ? stock_uid($u) : 0));
    return true;
}

/** คิวของ request นี้ — ใส่ $item = เพิ่ม (ลงทะเบียน notify_flush ตอนจบ request ครั้งแรก) · ไม่ใส่ = ดึงทั้งคิวออก */
function notify_queue($item = null)
{
    static $q = array();
    static $hooked = false;
    if ($item === null) {
        $out = $q;
        $q   = array();
        return $out;
    }
    $q[] = $item;
    if (!$hooked) {
        $hooked = true;
        register_shutdown_function('notify_flush');
    }
    return count($q);
}

/** ส่งทุกข้อความในคิวเข้าทุกแชต แล้วลงประวัติ — ทำงานตอนจบ request (register_shutdown_function)
    PHP-FPM: ตอบหน้าเว็บกลับไปก่อน · ต่อ Telegram ไม่ได้ครั้งแรก = ข้ามที่เหลือของรอบนี้ (ลงประวัติว่าข้าม) ไม่ให้หน้าร้านรอซ้ำ
    TODO:
      - [x] ช่วงที่ 9: ไม่ให้ error ใด ๆ หลุดออกไปหน้าเว็บ (หน้าเว็บตอบไปแล้ว) */
function notify_flush()
{
    $items = notify_queue();
    if (!$items) {
        return;
    }
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    ignore_user_abort(true);
    try {
        $token  = notify_get('tg_token');
        $chats  = tg_chats_parse(notify_get('tg_chats'));
        $silent = notify_get('tg_silent') === '1' ? 'true' : 'false';
        $down   = '';
        foreach ($items as $it) {
            $subj = notify_subject($it['text']);
            if ($token === '' || !$chats[0]) {
                notify_log_add('tg', $it['event'], '-', false, $subj, 'ยังไม่ได้ตั้ง Bot token / Chat ID', $it['code'], $it['ref'], $it['uid']);
                continue;
            }
            foreach ($chats[0] as $c) {
                if ($down !== '') {
                    notify_log_add('tg', $it['event'], $c, false, $subj, 'ข้าม — ' . $down, $it['code'], $it['ref'], $it['uid']);
                    continue;
                }
                $r = tg_api($token, 'sendMessage', array('chat_id' => $c, 'text' => $it['text'], 'parse_mode' => 'HTML',
                                                         'disable_notification' => $silent), 5);
                if (!$r[0] && !empty($r[2])) {
                    $down = 'เชื่อมต่อ Telegram ไม่ได้ในรอบนี้';
                }
                notify_log_add('tg', $it['event'], $c, $r[0], $subj, $r[0] ? '' : (string) $r[1], $it['code'], $it['ref'], $it['uid']);
            }
        }
    } catch (Throwable $e) {
        error_log('[AOSTOCK] notify_flush: ' . $e->getMessage());
    }
}

/** หัวเรื่องสั้นของข้อความ (บรรทัดแรก ไม่มีแท็ก) ไว้ลงประวัติ */
function notify_subject($text)
{
    $first = strtok(strip_tags((string) $text), "\n");
    return stock_cut(trim((string) $first), 255);
}

/* ---------- เหตุการณ์จริง — เรียกหลังบันทึกสำเร็จ ---------- */

/** เปิดร้าน (+ สินค้าใกล้หมดของสาขา วันละครั้งตอนเปิดร้าน)
    TODO:
      - [x] ช่วงที่ 9: เรียกจาก store_open */
function notify_on_open($code)
{
    if (notify_wants('open', $code)) {
        $st = store_state($code);
        $un = store_unclosed_days($code);
        if ($st !== null) {
            $d = array_merge(notify_d_base($code, $st['opened_by']), array(
                'time' => $st['opened_at'], 'float' => $st['float'], 'carry' => $st['carry'], 'counted' => $st['counted'],
                'reason' => $st['reason'], 'unclosed' => $un ? 'วัน' . thai_date_full(strtotime($un[0])) : '',
            ));
            notify_event('open', $code, notify_tg_text('open', $d));
        }
    }
    if (notify_wants('low', $code)) {
        $items = array();
        foreach (low_stock_products($code, 1000) as $r) {
            $items[] = array('name' => $r['product']['name'], 'qty' => $r['qty'], 'unit' => $r['product']['unit']);
        }
        if ($items) {
            notify_event('low', $code, notify_tg_text('low', array_merge(notify_d_base($code), array('items' => $items))));
        }
    }
}

/** ปิดร้าน + เงินขาด / เกิน
    TODO:
      - [x] ช่วงที่ 9: เรียกจาก store_close */
function notify_on_close($code)
{
    if (!notify_wants('close', $code) && !notify_wants('cash_diff', $code)) {
        return;
    }
    $d = notify_d_store($code, time());
    if ($d === null || !$d['closed']) {
        return;
    }
    notify_event('close', $code, notify_tg_text('close', $d));
    if ($d['diff'] !== null && abs($d['diff']) >= 0.01) {
        notify_event('cash_diff', $code, notify_tg_text('cash_diff', $d));
    }
}

/** เปิดร้านใหม่หลังปิด — $st = สถานะก่อนเปิดใหม่ (ยังมีเวลา / คนปิด / เงินที่นับได้)
    TODO:
      - [x] ช่วงที่ 9: เรียกจาก store_reopen */
function notify_on_reopen($code, $user, $reason, $st)
{
    if (!notify_wants('reopen', $code)) {
        return;
    }
    $d = array_merge(notify_d_base($code, $user['name'] . ' (ผู้ดูแล)'), array(
        'closed_at' => $st['closed_at'], 'closed_by' => $st['closed_by'], 'counted' => $st['cash_counted'], 'reason' => trim($reason),
    ));
    notify_event('reopen', $code, notify_tg_text('reopen', $d));
}

/** ยกเลิก / ยกเลิกเพื่อแก้ไข บิลหรือเอกสารคลังของวันนี้ — $doc = บิล (SA) หรือเอกสาร (RC/IS/AD) ก่อนยกเลิก
    TODO:
      - [x] ช่วงที่ 9: เรียกจาก bill_void / receive_void / issue_void / adj_void */
function notify_on_void($code, $kind, $doc, $user, $reason, $edit)
{
    if (!notify_wants('void', $code)) {
        return;
    }
    if ($kind === 'SA') {
        $amount = 'ยอด ' . money2($doc['total']) . ' บาท · ' . number_format($doc['qty']) . ' ชิ้น · ขายโดย ' . $doc['by'] . ' ' . $doc['time'] . ' น.';
    } elseif ($kind === 'AD') {
        $amount = number_format($doc['items']) . ' รายการ · นับโดย ' . $doc['by'] . ' ' . $doc['time'] . ' น.';
    } else {
        $amount = number_format($doc['items']) . ' รายการ · ' . number_format($doc['qty']) . ' ชิ้น · ทำโดย ' . $doc['by'] . ' ' . $doc['time'] . ' น.';
    }
    $d = array_merge(notify_d_base($code, $user['name']), array(
        'label' => notify_doc_label($kind), 'no' => $doc['no'], 'amount' => $amount, 'reason' => trim($reason), 'edit' => (bool) $edit,
    ));
    notify_event('void', $code, notify_tg_text('void', $d), $doc['no']);
}

/** รับคืนสินค้า — $rt = ใบรับคืนที่บันทึกแล้ว (return_by_no)
    TODO:
      - [x] ช่วงที่ 9: เรียกจาก return_save */
function notify_on_refund($code, $rt)
{
    if (!notify_wants('refund', $code)) {
        return;
    }
    $d = array_merge(notify_d_base($code, $rt['by'], $rt['ts']), array(
        'no' => $rt['no'], 'bill_no' => $rt['bill_no'], 'refund' => $rt['refund'], 'items' => notify_items_text($rt['lines']),
        'reason' => return_reason_label($rt['reason']) . ($rt['note'] !== '' ? ' — ' . $rt['note'] : ''), 'restock' => $rt['restock'],
    ));
    notify_event('refund', $code, notify_tg_text('refund', $d), $rt['no']);
}

/** ตัดออกเพราะสูญหาย / ชำรุด — $doc = ใบตัดออกที่บันทึกแล้ว (เหตุผลอื่นไม่แจ้ง)
    TODO:
      - [x] ช่วงที่ 9: เรียกจาก issue_save */
function notify_on_issue($code, $doc)
{
    if (!in_array($doc['reason'], array('lost', 'damaged'), true) || !notify_wants('lost', $code)) {
        return;
    }
    $d = array_merge(notify_d_base($code, $doc['by'], $doc['ts']), array(
        'no' => $doc['no'], 'reason' => issue_reason_label($doc['reason']), 'items' => notify_items_text($doc['lines']),
        'qty' => $doc['qty'], 'cost' => isset($doc['cost']) ? $doc['cost'] : $doc['value'], 'note' => $doc['note'],
    ));
    notify_event('lost', $code, notify_tg_text('lost', $d), $doc['no']);
}

/** ยกเลิกเอกสารคลังย้อนหลัง — $doc = เอกสารก่อนยกเลิก
    TODO:
      - [x] ช่วงที่ 9: เรียกจาก past_doc_void */
function notify_on_backdate($code, $doc, $user, $reason, $edit)
{
    if (!notify_wants('backdate', $code)) {
        return;
    }
    $d = array_merge(notify_d_base($code, $user['name']), array(
        'label' => notify_doc_label($doc['kind']), 'no' => $doc['no'], 'doc_day' => thai_date_full(strtotime($doc['date'])),
        'reason' => trim($reason), 'edit' => (bool) $edit,
    ));
    notify_event('backdate', $code, notify_tg_text('backdate', $d), $doc['no']);
}

/* ---------- ตัวอย่างข้อความในหน้าตั้งค่า ---------- */

/** ตัวอย่างข้อความของแต่ละเหตุการณ์ — ใช้ข้อมูลล่าสุดที่มีจริงของสาขา (ยังไม่มีเหตุการณ์ชนิดนั้น = ค่าตัวอย่าง)
    TODO:
      - [x] ช่วงที่ 9: ใช้ notify_tg_text ตัวเดียวกับตอนส่งจริง */
function notify_tg_sample($event, $code = '')
{
    $br = branches_active();
    if ($code === '' || !isset($br[$code])) {
        $code = key($br);
    }
    $bid  = branch_id_of($code);
    $base = notify_d_base($code, 'พนักงานตัวอย่าง');
    switch ($event) {
        case 'open':
        case 'close':
        case 'cash_diff':
            $where = ($event === 'open') ? '' : ' AND d.status = \'closed\'' . ($event === 'cash_diff' ? ' AND d.counted_cash <> d.expected_cash' : '');
            $day = sdb_val('SELECT MAX(d.store_date) FROM ' . sdb_tb('store_day') . ' d WHERE d.branch_id = ?' . $where, array($bid));
            $d   = ($day !== null) ? notify_d_store($code, strtotime($day)) : null;
            if ($d === null) {
                $d = array_merge($base, array('float' => branch_default_float($code), 'total' => 0, 'bills' => 0, 'cash' => 0, 'transfer' => 0,
                                              'expect' => 3500, 'counted' => 3450, 'diff' => -50, 'keep' => 2000, 'handover' => 1450));
            }
            if ($event === 'open') {
                $d['time'] = isset($d['open_time']) ? $d['open_time'] : $d['time'];
                $d['by']   = isset($d['open_by']) ? $d['open_by'] : $d['by'];
            }
            return notify_tg_text($event, $d);
        case 'low':
            $items = array();
            foreach (low_stock_products($code, 1000) as $r) {
                $items[] = array('name' => $r['product']['name'], 'qty' => $r['qty'], 'unit' => $r['product']['unit']);
            }
            return notify_tg_text('low', array_merge($base, array('items' => $items)));
        case 'void':
            $b = sale_bills_query('s.branch_id = ? AND s.status = \'void\'', array($bid), 's.void_date DESC, s.sale_id DESC', 1);
            if ($b) {
                $b = $b[0];
                return notify_tg_text('void', array_merge(notify_d_base($code, $b['void_by'], strtotime(date('Y-m-d', $b['ts']) . ' ' . substr($b['void_at'], -5))), array(
                    'label' => 'บิล', 'no' => $b['no'], 'reason' => $b['void_reason'], 'edit' => $b['void_mode'] === 'edit',
                    'amount' => 'ยอด ' . money2($b['total']) . ' บาท · ' . number_format($b['qty']) . ' ชิ้น · ขายโดย ' . $b['by'] . ' ' . $b['time'] . ' น.')));
            }
            return notify_tg_text('void', array_merge($base, array('label' => 'บิล', 'no' => $code . date('Y-m') . '-0001', 'reason' => 'คิดเงินผิด',
                                                                     'amount' => 'ยอด 590.00 บาท · 2 ชิ้น')));
        case 'refund':
            $rt = returns_query('r.return_id = (SELECT MAX(x.return_id) FROM ' . sdb_tb('return') . ' x WHERE x.branch_id = ?)', array($bid));
            if ($rt) {
                $rt = $rt[0];
                $d  = array_merge(notify_d_base($code, $rt['by'], $rt['ts']), array(
                    'no' => $rt['no'], 'bill_no' => $rt['bill_no'], 'refund' => $rt['refund'], 'items' => notify_items_text($rt['lines']),
                    'reason' => return_reason_label($rt['reason']), 'restock' => $rt['restock']));
                return notify_tg_text('refund', $d);
            }
            return notify_tg_text('refund', array_merge($base, array('no' => 'RT-' . date('ymd') . '-0001', 'bill_no' => '-', 'refund' => 390,
                                                                       'items' => 'ฟิล์มกระจก ×1', 'reason' => 'ชำรุด', 'restock' => false)));
        case 'reopen':
            $lg = sdb_row('SELECT l.detail, l.add_date, s.name FROM ' . sdb_tb('log') . ' l LEFT JOIN ' . sdb_tb('staff') . ' s ON s.staff_id = l.created_by'
                        . ' WHERE l.branch_id = ? AND l.type = \'open\' AND l.title LIKE ? ORDER BY l.add_date DESC, l.log_id DESC LIMIT 1',
                          array($bid, 'เปิดร้านใหม่หลังปิด%'));
            if ($lg !== null) {
                $dt = json_decode((string) $lg['detail'], true);
                $dt = is_array($dt) ? $dt : array();
                $cl = isset($dt['ปิดไปเมื่อ']) ? explode(' น. โดย ', $dt['ปิดไปเมื่อ']) : array('-', '-');
                return notify_tg_text('reopen', array_merge(notify_d_base($code, (string) $lg['name'] . ' (ผู้ดูแล)', strtotime($lg['add_date'])), array(
                    'closed_at' => $cl[0], 'closed_by' => isset($cl[1]) ? $cl[1] : '-',
                    'counted' => isset($dt['เงินที่นับได้ตอนปิด']) ? (float) str_replace(',', '', $dt['เงินที่นับได้ตอนปิด']) : 0,
                    'reason' => isset($dt['เหตุผล']) ? $dt['เหตุผล'] : '')));
            }
            return notify_tg_text('reopen', array_merge($base, array('by' => 'ผู้ดูแลตัวอย่าง', 'closed_at' => '20:15', 'closed_by' => 'พนักงานตัวอย่าง',
                                                                       'counted' => 15000, 'reason' => 'ลูกค้ามารับของที่จองไว้')));
        case 'lost':
            $docs = stock_docs_query('IS', 'd.issue_id = (SELECT MAX(x.issue_id) FROM ' . sdb_tb('issue') . ' x WHERE x.branch_id = ? AND x.status = \'posted\''
                                         . ' AND x.reason IN (\'lost\', \'damaged\'))', array($bid));
            if ($docs) {
                $doc = $docs[0];
                return notify_tg_text('lost', array_merge(notify_d_base($code, $doc['by'], $doc['ts']), array(
                    'no' => $doc['no'], 'reason' => issue_reason_label($doc['reason']), 'items' => notify_items_text($doc['lines']),
                    'qty' => $doc['qty'], 'cost' => $doc['cost'], 'note' => $doc['note'])));
            }
            return notify_tg_text('lost', array_merge($base, array('no' => 'IS-' . date('ymd') . '-0001', 'reason' => 'สูญหาย',
                                                                     'items' => 'สายชาร์จ USB-C ×2', 'qty' => 2, 'cost' => 180, 'note' => 'หาไม่พบตอนตรวจชั้น')));
        case 'backdate':
            foreach (stock_doc_kinds() as $k => $kd) {
                if ($k === 'SA') {
                    continue;                                   // บิลของวันก่อนยกเลิกย้อนหลังไม่ได้
                }
                $docs = stock_docs_query($k, 'd.' . $kd['id'] . ' = (SELECT MAX(x.' . $kd['id'] . ') FROM ' . sdb_tb($kd['tb']) . ' x WHERE x.branch_id = ?'
                                             . ' AND x.status = \'void\' AND DATE(x.void_date) > x.doc_date)', array($bid));
                if ($docs) {
                    $doc = $docs[0];
                    return notify_tg_text('backdate', array_merge(notify_d_base($code, $doc['void_by']), array(
                        'label' => notify_doc_label($k), 'no' => $doc['no'], 'doc_day' => thai_date_full(strtotime($doc['date'])),
                        'reason' => $doc['void_reason'], 'edit' => $doc['void_mode'] === 'edit', 'time' => substr($doc['void_at'], -5))));
                }
            }
            return notify_tg_text('backdate', array_merge($base, array('by' => 'ผู้ดูแลตัวอย่าง', 'label' => 'ใบรับเข้า', 'no' => 'RC-' . date('ymd', strtotime('-1 day')) . '-0001',
                                                                         'doc_day' => thai_date_full(strtotime('-1 day')), 'reason' => 'รับเข้าซ้ำ')));
        case 'test':
            return notify_tg_text('test', array());
    }
    return '';
}

/**
 * ข้อมูลสรุปยอดขายของวันหนึ่ง สำหรับอีเมลรายวัน
 * คืน array(rows => สาขา => สรุป, sum => รวม, prev => ยอดวันขายก่อนหน้า, month => ยอดสะสมเดือน, top, low)
 * TODO:
 *   - [x] ช่วงที่ 8: ผลปิดร้านจาก ao_stock_store_day (store_day_result) · วันขายก่อนหน้า / ยอดสะสมเดือน / สินค้าขายดีเป็น SQL
 */
function daily_summary_data($ts, $codes)
{
    $br   = branches_all();
    $rows = array();
    $sum  = array('bills' => 0, 'void' => 0, 'v' => 0, 'n' => 0, 'total' => 0, 'vat' => 0, 'cash' => 0, 'transfer' => 0,
                  'ret' => 0, 'refund' => 0, 'net' => 0, 'diff' => 0, 'closed' => 0);
    foreach ($codes as $c) {
        $s = acct_day($c, $ts);
        $r = array('name' => isset($br[$c]) ? $br[$c]['name'] : $c, 'bills' => $s['bills'] - $s['void'], 'void' => $s['void'],
                   'v' => $s['v'], 'n' => $s['n'], 'total' => $s['total'], 'vat' => $s['vat'], 'cash' => $s['cash'],
                   'transfer' => $s['transfer'], 'ret' => 0, 'refund' => 0, 'diff' => null, 'close_by' => '', 'close_time' => '');
        foreach (returns_of_day($c, $ts) as $rt) {
            if (empty($rt['void'])) {
                $r['ret']++;
                $r['refund'] += $rt['refund'];
            }
        }
        $sd = store_day_result($c, $ts);                       // ผลปิดร้านจริงของวันนั้น (ยังไม่ปิด = diff null)
        if ($sd !== null && $sd['closed']) {
            $r['diff']       = $sd['diff'];
            $r['close_by']   = $sd['closed_by'];
            $r['close_time'] = $sd['closed_at'];
        }
        $r['net'] = $r['total'] - $r['refund'];
        $rows[$c] = $r;
        foreach (array('bills', 'void', 'v', 'n', 'total', 'vat', 'cash', 'transfer', 'ret', 'refund', 'net') as $k) {
            $sum[$k] += $r[$k];
        }
        if ($r['diff'] !== null) {
            $sum['diff'] += $r['diff'];
            $sum['closed']++;
        }
    }
    /* วันขายก่อนหน้า (ข้ามวันที่ไม่มียอด ย้อนได้ 7 วัน) ไว้เทียบ — SQL คำสั่งเดียว */
    $prev = array('ts' => 0, 'total' => 0);
    list($in, $params) = sdb_in('b.code', $codes);
    array_unshift($params, date('Y-m-d', strtotime('-7 day', $ts)), date('Y-m-d', strtotime('-1 day', $ts)));
    $p = sdb_row('SELECT s.sale_date, SUM(s.total) AS t FROM ' . sdb_tb('sale') . ' s JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = s.branch_id'
               . ' WHERE s.status = \'paid\' AND s.sale_date BETWEEN ? AND ? AND ' . $in
               . ' GROUP BY s.sale_date HAVING SUM(s.total) > 0 ORDER BY s.sale_date DESC LIMIT 1', $params);
    if ($p !== null) {
        $prev = array('ts' => strtotime($p['sale_date']), 'total' => (float) $p['t']);
    }
    $month = 0;
    foreach (sales_agg($codes, strtotime(date('Y-m-01', $ts)), $ts) as $x) {
        $month += $x['total'];
    }
    $top = product_sales($codes, $ts, $ts);
    uasort($top, function ($a, $b) { return $a['qty'] == $b['qty'] ? ($a['total'] < $b['total'] ? 1 : -1) : ($a['qty'] < $b['qty'] ? 1 : -1); });
    $low = array();
    foreach ($codes as $c) {
        foreach (low_stock_products($c, 3) as $l) {
            $low[] = array('branch' => isset($br[$c]) ? $br[$c]['short'] : $c, 'name' => $l['product']['name'], 'qty' => $l['qty'], 'unit' => $l['product']['unit']);
        }
    }
    return array('rows' => $rows, 'sum' => $sum, 'prev' => $prev, 'month' => $month, 'top' => array_slice($top, 0, 5, true), 'low' => $low);
}

/** หัวเรื่องอีเมลสรุปรายวัน */
function daily_summary_subject($ts, $data)
{
    return 'สรุปยอดขาย ' . thai_date_full($ts) . ' · ' . money2($data['sum']['total']) . ' บาท · ' . count($data['rows']) . ' สาขา';
}

/** เนื้อหาอีเมลสรุปยอดขายรายวัน (HTML แบบ inline style ให้เปิดได้ทุกโปรแกรมอีเมล) */
function daily_summary_email_html($ts, $data, $parts)
{
    $e   = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
    $has = function ($k) use ($parts) { return in_array($k, $parts, true); };
    $td  = 'padding:8px 10px;border-bottom:1px solid #e6ebe9;font-size:13px;';
    $th  = 'padding:8px 10px;background:#f1f5f3;color:#4a5a55;font-size:12px;font-weight:600;text-align:right;border-bottom:1px solid #d9e2de;';
    $s   = $data['sum'];
    $chg = $data['prev']['total'] > 0 ? ($s['total'] - $data['prev']['total']) / $data['prev']['total'] * 100 : null;

    $h  = '<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>' . $e(daily_summary_subject($ts, $data)) . '</title></head>'
        . '<body style="margin:0;padding:0;background:#eef2f0;font-family:Tahoma,\'Segoe UI\',sans-serif;color:#13201c">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f0;padding:20px 10px"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:680px;background:#fff;border-radius:12px;overflow:hidden">';
    $h .= '<tr><td style="background:#07211b;color:#fff;padding:20px 24px">'
        . '<div style="font-size:12px;letter-spacing:.08em;color:#c9a86a">' . $e(APP_NAME) . ' · รายงานประจำวัน</div>'
        . '<div style="font-size:20px;font-weight:700;margin-top:4px">สรุปยอดขาย ' . $e(thai_date_full($ts)) . '</div>'
        . '<div style="font-size:13px;color:#b9cdc6;margin-top:2px">' . count($data['rows']) . ' สาขา · ไม่นับบิลที่ยกเลิก</div></td></tr>';

    /* ตัวเลขหลัก */
    $kpi = array(array('ยอดขายรวม', money2($s['total']) . ' ฿',
                       $chg === null ? '' : ($chg >= 0 ? '▲ ' : '▼ ') . number_format(abs($chg), 1) . '% จาก ' . thai_day_month($data['prev']['ts'])),
                 array('จำนวนบิล', number_format($s['bills']), $s['void'] ? 'ยกเลิก ' . $s['void'] . ' ใบ' : ''),
                 array('เงินเข้าสุทธิ', money2($s['net']) . ' ฿', $s['refund'] ? 'หักเงินคืน ' . money2($s['refund']) : 'ไม่มีเงินคืน'));
    if ($has('month')) {
        $kpi[] = array('สะสมเดือนนี้', money2($data['month']) . ' ฿', thai_month_full($ts));
    }
    $h .= '<tr><td style="padding:18px 16px 4px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>';
    foreach ($kpi as $k) {
        $h .= '<td style="padding:4px" valign="top"><div style="border:1px solid #e1e8e5;border-radius:10px;padding:10px 12px">'
            . '<div style="font-size:11px;color:#6b7a75">' . $e($k[0]) . '</div>'
            . '<div style="font-size:17px;font-weight:700;margin-top:2px;white-space:nowrap">' . $e($k[1]) . '</div>'
            . '<div style="font-size:11px;color:' . (strpos($k[2], '▼') === 0 ? '#c0392b' : (strpos($k[2], '▲') === 0 ? '#0e7a5f' : '#6b7a75')) . '">' . $e($k[2]) . '&nbsp;</div></div></td>';
    }
    $h .= '</tr></table></td></tr>';

    /* ตารางแยกสาขา */
    $cols = array(array('บิล', 'bills', 'n'), array('ยอดขาย', 'total', 'm'));
    if ($has('pay')) {
        $cols[] = array('เงินสด', 'cash', 'm');
        $cols[] = array('โอน', 'transfer', 'm');
    }
    if ($has('vat')) {
        $cols[] = array('VAT / ไม่ VAT', 'vn', 's');
        $cols[] = array('VAT', 'vat', 'm');
    }
    if ($has('refund')) {
        $cols[] = array('คืนเงิน', 'refund', 'm');
    }
    if ($has('cash')) {
        $cols[] = array('เงินขาด/เกิน', 'diff', 'd');
    }
    $cell = function ($r, $c) use ($e) {
        $v = $c[1] === 'vn' ? $r['v'] . ' / ' . $r['n'] : (isset($r[$c[1]]) ? $r[$c[1]] : 0);
        if ($c[2] === 'm') {
            return $c[1] === 'refund' && $v > 0 ? '−' . money2($v) : ($v ? money2($v) : '—');
        }
        if ($c[2] === 'd') {
            if ($v === null) {
                return '<span style="color:#8a9692">ยังไม่ปิด</span>';
            }
            return $v == 0 ? '<span style="color:#0e7a5f">ตรง</span>' : '<b style="color:' . ($v < 0 ? '#c0392b' : '#b7791f') . '">' . ($v < 0 ? 'ขาด ' : 'เกิน ') . money2(abs($v)) . '</b>';
        }
        return $e(is_numeric($v) ? number_format($v) : $v);
    };
    $h .= '<tr><td style="padding:14px 20px 4px"><div style="font-size:15px;font-weight:700">ยอดขายแยกสาขา</div></td></tr>'
        . '<tr><td style="padding:6px 20px 0"><div style="overflow-x:auto"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;min-width:520px">'
        . '<tr><th style="' . $th . 'text-align:left">สาขา</th>';
    foreach ($cols as $c) {
        $h .= '<th style="' . $th . '">' . $e($c[0]) . '</th>';
    }
    $h .= '</tr>';
    foreach ($data['rows'] as $r) {
        $h .= '<tr><td style="' . $td . '"><b>' . $e($r['name']) . '</b>'
            . ($r['close_by'] !== '' ? '<div style="font-size:11px;color:#8a9692">ปิด ' . $e($r['close_time']) . ' น. · ' . $e($r['close_by']) . '</div>' : '') . '</td>';
        foreach ($cols as $c) {
            $h .= '<td style="' . $td . 'text-align:right;white-space:nowrap">' . $cell($r, $c) . '</td>';
        }
        $h .= '</tr>';
    }
    $tot = $s;
    $tot['diff'] = $s['closed'] ? $s['diff'] : null;
    $h .= '<tr><td style="' . $td . 'background:#f7faf8;border-top:2px solid #07211b"><b>รวม</b></td>';
    foreach ($cols as $c) {
        $h .= '<td style="' . $td . 'background:#f7faf8;border-top:2px solid #07211b;text-align:right;white-space:nowrap"><b>' . $cell($tot, $c) . '</b></td>';
    }
    $h .= '</tr></table></div></td></tr>';

    /* สินค้าขายดี */
    if ($has('top') && $data['top']) {
        $h .= '<tr><td style="padding:18px 20px 4px"><div style="font-size:15px;font-weight:700">สินค้าขายดีของวัน</div></td></tr><tr><td style="padding:6px 20px 0">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">';
        $i = 0;
        foreach ($data['top'] as $p) {
            $i++;
            $h .= '<tr><td style="' . $td . 'width:24px;color:#8a9692">' . $i . '</td><td style="' . $td . '">' . $e($p['name']) . '</td>'
                . '<td style="' . $td . 'text-align:right;white-space:nowrap">' . number_format($p['qty']) . ' ' . $e($p['unit']) . '</td>'
                . '<td style="' . $td . 'text-align:right;white-space:nowrap">' . money2($p['total']) . '</td></tr>';
        }
        $h .= '</table></td></tr>';
    }
    /* สินค้าใกล้หมด */
    if ($has('low') && $data['low']) {
        $h .= '<tr><td style="padding:18px 20px 4px"><div style="font-size:15px;font-weight:700">สินค้าใกล้หมด / หมด</div></td></tr><tr><td style="padding:6px 20px 0">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">';
        foreach ($data['low'] as $l) {
            $h .= '<tr><td style="' . $td . 'color:#6b7a75;width:70px">' . $e($l['branch']) . '</td><td style="' . $td . '">' . $e($l['name']) . '</td>'
                . '<td style="' . $td . 'text-align:right;white-space:nowrap;color:' . ($l['qty'] <= 0 ? '#c0392b' : '#b7791f') . '">'
                . ($l['qty'] <= 0 ? 'หมด' : 'เหลือ ' . number_format($l['qty']) . ' ' . $e($l['unit'])) . '</td></tr>';
        }
        $h .= '</table></td></tr>';
    }
    if ($has('refund') && $s['ret']) {
        $h .= '<tr><td style="padding:12px 20px 0;font-size:12px;color:#4a5a55">รับคืนสินค้า ' . number_format($s['ret']) . ' ใบ · คืนเงินสดรวม ' . money2($s['refund']) . ' บาท</td></tr>';
    }
    $h .= '<tr><td style="padding:22px 20px 20px;font-size:11px;color:#8a9692;border-top:1px solid #e6ebe9;margin-top:16px">'
        . 'อีเมลนี้ส่งอัตโนมัติจากระบบ ' . $e(APP_NAME) . ' ตามเวลาที่ตั้งไว้ · ดูรายละเอียดเพิ่มเติมได้ที่เมนู "สรุปยอดขายรายวัน" และ "บิลขายและเงินเข้า"'
        . '<br>เปลี่ยนผู้รับหรือหยุดส่ง: เมนู ตั้งค่า → ตั้งค่าการแจ้งเตือน</td></tr>';
    $h .= '</table></td></tr></table></body></html>';
    return $h;
}

/**
 * ส่งอีเมล HTML ผ่าน SMTP (รองรับ TLS / SSL / ไม่เข้ารหัส + AUTH LOGIN) · ไม่ตั้ง smtp_host = ใช้ mail() ของ PHP
 * $c = ค่าตั้ง (smtp_host, smtp_port, smtp_secure, smtp_user, smtp_pass, from_name, from_email)
 * คืน array(ok, ข้อความ)
 */
function smtp_send($c, $to, $subject, $html)
{
    $enc  = function ($v) {
        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($v, 'UTF-8', 'B', "\r\n");     // ตัดเป็นช่วงสั้นตามมาตรฐาน (ไม่เกิน 75 ตัว)
        }
        return '=?UTF-8?B?' . base64_encode($v) . '?=';
    };
    $from = $c['from_email'] !== '' ? $c['from_email'] : $c['smtp_user'];
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        return array(false, 'ยังไม่ได้ตั้งอีเมลผู้ส่ง');
    }
    $host = preg_replace('/[^A-Za-z0-9.-]/', '', $c['smtp_host']);
    $head = 'From: ' . $enc($c['from_name']) . ' <' . $from . ">\r\n"
          . "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n"
          . 'Date: ' . date('r') . "\r\n" . 'Message-ID: <' . md5(uniqid('', true)) . '@' . ($host !== '' ? $host : 'aostock.local') . ">\r\n";
    $body = chunk_split(base64_encode($html));

    if ($host === '') {
        $ok = @mail(implode(', ', $to), $enc($subject), $body, $head);
        return $ok ? array(true, 'ส่งด้วย mail() ของเซิร์ฟเวอร์แล้ว') : array(false, 'mail() ของเซิร์ฟเวอร์ส่งไม่สำเร็จ — แนะนำให้ตั้งค่า SMTP');
    }
    $port   = (int) $c['smtp_port'];
    $secure = $c['smtp_secure'];
    $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, 10);
    if (!$fp) {
        return array(false, 'เชื่อมต่อ ' . $host . ':' . $port . ' ไม่ได้' . ($errstr !== '' ? ' (' . $errstr . ')' : ''));
    }
    stream_set_timeout($fp, 12);
    $read = function () use ($fp) {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $step = function ($cmd, $want) use ($fp, $read) {
        if ($cmd !== null) {
            fwrite($fp, $cmd . "\r\n");
        }
        $r = $read();
        return in_array((int) substr($r, 0, 3), (array) $want, true) ? '' : (trim($r) !== '' ? trim($r) : 'เซิร์ฟเวอร์ไม่ตอบ');
    };
    $me  = isset($_SERVER['SERVER_NAME']) ? preg_replace('/[^A-Za-z0-9.-]/', '', $_SERVER['SERVER_NAME']) : 'localhost';
    $err = $step(null, 220);
    if ($err === '') {
        $err = $step('EHLO ' . $me, 250);
    }
    if ($err === '' && $secure === 'tls') {
        $err = $step('STARTTLS', 220);
        if ($err === '' && !@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $err = 'เปิด TLS ไม่สำเร็จ — ลองเปลี่ยนเป็น SSL พอร์ต 465';
        }
        if ($err === '') {
            $err = $step('EHLO ' . $me, 250);
        }
    }
    if ($err === '' && $c['smtp_user'] !== '') {
        $err = $step('AUTH LOGIN', 334);
        if ($err === '') {
            $err = $step(base64_encode($c['smtp_user']), 334);
        }
        if ($err === '') {
            $err = $step(base64_encode($c['smtp_pass']), 235);
            if ($err !== '') {
                $err = 'ชื่อผู้ใช้หรือรหัสผ่าน SMTP ไม่ถูกต้อง (' . $err . ')';
            }
        }
    }
    if ($err === '') {
        $err = $step('MAIL FROM:<' . $from . '>', 250);
    }
    foreach ($to as $t) {
        if ($err === '') {
            $err = $step('RCPT TO:<' . $t . '>', array(250, 251));
        }
    }
    if ($err === '') {
        $err = $step('DATA', 354);
    }
    if ($err === '') {
        $msg = $head . 'To: ' . implode(', ', $to) . "\r\n" . 'Subject: ' . $enc($subject) . "\r\n\r\n" . $body;
        $msg = preg_replace('/^\./m', '..', $msg);
        $err = $step($msg . "\r\n.", 250);
    }
    @fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return $err === '' ? array(true, 'ส่งแล้วถึง ' . implode(', ', $to)) : array(false, 'ส่งไม่สำเร็จ: ' . $err);
}

/** บันทึกประวัติการส่งลง ao_stock_notify_log — $ok + $error ไม่ว่าง = ข้าม (เช่น วันนี้ไม่มียอดขาย)
    $code = สาขาที่เกี่ยวข้อง ('' = ทุกสาขา) · $uid = ผู้ทำ (null = ผู้ใช้ที่เข้าระบบอยู่ · cron = 0)
    TODO:
      - [x] ช่วงที่ 9: INSERT ao_stock_notify_log (เดิม $_SESSION['notify_log']) */
function notify_log_add($channel, $event, $to, $ok, $subject, $error = '', $code = '', $ref = '', $uid = null)
{
    if ($uid === null) {
        $u   = current_user();
        $uid = $u ? stock_uid($u) : 0;
    }
    sdb_insert('notify_log', array(
        'channel'    => ($channel === 'mail') ? 'mail' : 'tg',
        'event'      => substr((string) $event, 0, 20),
        'branch_id'  => ($code !== '') ? branch_id_of((string) $code) : 0,
        'ref_no'     => substr((string) $ref, 0, 24),
        'send_to'    => stock_cut((string) $to, 500),
        'subject'    => stock_cut((string) $subject, 255),
        'is_ok'      => $ok ? 1 : 0,
        'error'      => stock_cut((string) $error, 255),
        'created_by' => (int) $uid,
        'add_date'   => date('Y-m-d H:i:s'),
    ));
}

/** ประวัติการส่งล่าสุด — array ของ array(ts, channel, event, label, to, ok, skip, subject, error, branch, ref)
    TODO:
      - [x] ช่วงที่ 9: SELECT ao_stock_notify_log (ใหม่สุดก่อน) */
function notify_log_rows($limit = 30)
{
    $sql = 'SELECT l.*, b.code FROM ' . sdb_tb('notify_log') . ' l LEFT JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = l.branch_id'
         . ' ORDER BY l.add_date DESC, l.log_id DESC LIMIT ' . max(1, (int) $limit);
    $ev  = notify_tg_events();
    $out = array();
    foreach (sdb_rows($sql) as $r) {
        $e = $r['event'];
        $out[] = array(
            'ts'      => strtotime($r['add_date']),
            'channel' => $r['channel'],
            'event'   => $e,
            'label'   => ($e === 'test') ? 'ทดสอบ' : ($e === 'daily' ? 'อีเมลสรุปรายวัน' : (isset($ev[$e]) ? $ev[$e][0] : $e)),
            'to'      => $r['send_to'],
            'ok'      => ((int) $r['is_ok'] === 1),
            'skip'    => ((int) $r['is_ok'] === 1 && $r['error'] !== ''),
            'subject' => $r['subject'],
            'error'   => $r['error'],
            'branch'  => ($r['code'] !== null) ? branch_name($r['code']) : '',
            'ref'     => $r['ref_no'],
        );
    }
    return $out;
}

/** ค่า SMTP ที่ใช้ส่ง (smtp_send) */
function notify_smtp_cfg()
{
    $cfg = array();
    foreach (array('smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass', 'from_name', 'from_email') as $k) {
        $cfg[$k] = notify_get($k);
    }
    return $cfg;
}

/**
 * งานของ cron (admweb/aowebdata/modules/stock/cron.php — Cron Jobs ของโฮสต์ทุก 5 นาที)
 * ส่งอีเมลสรุปยอดขายของ "วันนี้" ครั้งเดียวต่อวัน เมื่อ: กลุ่มแจ้งเตือนไม่ถูกปิด · เปิดอีเมลรายวัน · เลยเวลาที่ตั้ง · วันนี้ยังไม่ได้ส่ง
 *   - "เฉพาะวันที่มียอดขาย" แล้ววันนี้ไม่มีบิล → ไม่ส่ง ลงประวัติว่าข้าม
 *   - ส่งไม่สำเร็จ → ลองใหม่รอบถัดไป ไม่เกิน 3 ครั้งต่อวัน (mail_fail = วันที่|จำนวนครั้ง)
 *   - cron สองตัวทำงานซ้อนกัน: จองวันนี้ใน mail_last_sent ด้วย UPDATE แบบมีเงื่อนไขก่อนส่ง ตัวที่จองไม่ได้ไม่ส่ง
 * คืนข้อความสั้น ๆ ว่าทำอะไร (cron.php พิมพ์ออก)
 * TODO:
 *   - [x] ช่วงที่ 9
 */
function notify_cron_run($now = null)
{
    $now   = ($now === null) ? time() : (int) $now;
    $today = date('Y-m-d', $now);
    stock_setting_set('cron_last_run', date('Y-m-d H:i:s', $now));
    if (!notify_enabled()) {
        return 'การแจ้งเตือนถูกปิดจากหลังบ้าน';
    }
    if (notify_get('mail_on') !== '1') {
        return 'อีเมลรายวันปิดอยู่';
    }
    if (date('H:i', $now) < notify_get('mail_time')) {
        return 'ยังไม่ถึงเวลาส่ง (' . notify_get('mail_time') . ' น.)';
    }
    $last = notify_get('mail_last_sent');
    if ($last === $today) {
        return 'วันนี้ส่งแล้ว';
    }
    $fail  = explode('|', notify_get('mail_fail'));
    $nFail = ($fail[0] === $today && isset($fail[1])) ? (int) $fail[1] : 0;
    if ($nFail >= 3) {
        return 'วันนี้ส่งไม่สำเร็จครบ 3 ครั้งแล้ว — ตรวจค่าเซิร์ฟเวอร์อีเมล (SMTP)';
    }

    /* จองวันนี้ก่อนส่ง — แถวยังไม่มีให้สร้างก่อน */
    if (stock_setting_get('mail_last_sent', null) === null) {
        stock_setting_set('mail_last_sent', '');
    }
    $got = sdb_q('UPDATE ' . sdb_tb('setting') . ' SET svalue = ?, updated_by = 0, updated_at = ? WHERE skey = \'mail_last_sent\' AND svalue <> ?',
                 array($today, date('Y-m-d H:i:s', $now), $today))->rowCount();
    stock_setting_rows(true);
    if ($got !== 1) {
        return 'cron อีกตัวกำลังส่งอยู่ / ส่งไปแล้ว';
    }

    $br    = branches_active();
    $codes = array_values(array_intersect(notify_get('mail_branches'), array_keys($br)));
    $codes = $codes ? $codes : array_keys($br);
    $ts    = strtotime($today);
    $data  = daily_summary_data($ts, $codes);
    $subj  = daily_summary_subject($ts, $data);
    $to    = mail_list_parse(notify_get('mail_to'));
    $to    = $to[0];
    if (!$to) {
        notify_log_add('mail', 'daily', '-', false, $subj, 'ยังไม่ได้ตั้งผู้รับ', '', '', 0);
        return 'ยังไม่ได้ตั้งผู้รับ';
    }
    if (notify_get('mail_days') === 'open' && (int) $data['sum']['bills'] === 0) {
        notify_log_add('mail', 'daily', implode(', ', $to), true, $subj, 'ข้าม — วันนี้ไม่มียอดขาย', '', '', 0);
        return 'ข้าม — วันนี้ไม่มียอดขาย';
    }
    $r = smtp_send(notify_smtp_cfg(), $to, $subj, daily_summary_email_html($ts, $data, notify_get('mail_parts')));
    notify_log_add('mail', 'daily', implode(', ', $to), $r[0], $subj, $r[0] ? '' : $r[1], '', '', 0);
    if (!$r[0]) {
        stock_setting_set('mail_fail', $today . '|' . ($nFail + 1));
        stock_setting_set('mail_last_sent', $last);                 // คืนการจอง ให้รอบถัดไปลองใหม่
        return 'ส่งไม่สำเร็จ (ครั้งที่ ' . ($nFail + 1) . '/3): ' . $r[1];
    }
    return 'ส่งแล้ว: ' . $subj;
}
