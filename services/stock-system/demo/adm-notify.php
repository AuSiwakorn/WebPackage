<?php
/* ==========================================================
   AOSTOCK DEMO — [ผู้ดูแล] ตั้งค่าการแจ้งเตือน
   ----------------------------------------------------------
   แอคคอร์เดียน 4 ส่วน (เปิดส่วนแรกไว้)
   1) Telegram — เปิด/ปิด · Bot token · Chat ID (หลายแชต/กลุ่มได้) · ค้นหา Chat ID จากบอท
                 เลือกเหตุการณ์ที่จะแจ้ง · เลือกสาขา · ส่งแบบไม่มีเสียง · ส่งข้อความทดสอบจริง
                 ด้านขวาแสดงตัวอย่างข้อความของเหตุการณ์ที่เลือก
   2) อีเมลสรุปยอดขายรายวัน — เปิด/ปิด · ผู้รับหลายคน · เวลาส่ง · ส่งทุกวัน/เฉพาะวันที่มียอด
                 เลือกสาขา · เลือกส่วนที่ใส่ในอีเมล · ดูตัวอย่างอีเมล · ส่งทดสอบ
   3) เซิร์ฟเวอร์อีเมล (SMTP) — host / port / TLS-SSL / ผู้ใช้ / รหัสผ่าน / ชื่อและอีเมลผู้ส่ง
   4) ประวัติการส่งล่าสุด
   token และรหัสผ่านไม่แสดงกลับบนหน้า (เห็น 4 ตัวท้าย) · เว้นว่าง = ใช้ค่าเดิม
   การแก้ทุกครั้งลงประวัติการทำรายการ (ไม่บันทึกค่าลับ)
   ฟังก์ชันอยู่ที่ include/function.php หมวด "การแจ้งเตือน"
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล
$br   = demo_branches();
$evs  = notify_tg_events();
$mps  = notify_mail_parts();
$err  = array();                         // ส่วน => ข้อความ
$old  = array();                         // ค่าที่กรอกไว้เมื่อบันทึกไม่ผ่าน
$found = null;                           // ผลค้นหา Chat ID
$sec  = '';                              // ส่วนที่ต้องเปิดไว้หลังส่งฟอร์ม
$P    = function ($k, $def = '') { return isset($_POST[$k]) && is_string($_POST[$k]) ? trim(str_replace("\r", '', $_POST[$k])) : $def; };
$PA   = function ($k, $allow) {
    $v = isset($_POST[$k]) && is_array($_POST[$k]) ? $_POST[$k] : array();
    return array_values(array_intersect(array_keys($allow), array_map('strval', $v)));
};
$logChange = function ($title, $changes) use ($user) {
    if ($changes) {
        $changes['แก้โดย'] = $user['name'] . ' (ผู้ดูแล)';
        log_add(work_branch($user), 'setting', $user, $title, $changes);
    }
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $P('act');
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err['top'] = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } elseif (in_array($act, array('save_tg', 'test_tg', 'find_tg'), true)) {
        /* ---------- Telegram ---------- */
        $sec   = 'tg';
        $token = $P('tg_token') !== '' ? $P('tg_token') : notify_get('tg_token');
        $in = array(
            'tg_on'       => !empty($_POST['tg_on']) ? '1' : '0',
            'tg_chats'    => $P('tg_chats'),
            'tg_events'   => $PA('tg_events', $evs),
            'tg_branches' => $P('tg_scope') === 'some' ? $PA('tg_branches', $br) : array(),
            'tg_silent'   => !empty($_POST['tg_silent']) ? '1' : '0',
        );
        $old['tg'] = $in;
        $chats = tg_chats_parse($in['tg_chats']);
        if ($token !== '' && !tg_token_valid($token)) {
            $err['tg'] = 'Bot token รูปแบบไม่ถูกต้อง — ต้องเป็นแบบ 123456789:ABC… ที่ได้จาก @BotFather';
        } elseif ($chats[1]) {
            $err['tg'] = 'Chat ID ไม่ถูกต้อง: ' . implode(', ', $chats[1]) . ' — ใช้ตัวเลข (กลุ่มขึ้นต้นด้วย -100) หรือ @ชื่อช่อง';
        } elseif ($act === 'find_tg') {
            if ($token === '') {
                $err['tg'] = 'กรอก Bot token ก่อน แล้วกดค้นหา Chat ID';
            } else {
                $r = tg_find_chats($token);
                if (!$r[0]) {
                    $err['tg'] = $r[1];
                } else {
                    $found = $r[1];
                    if ($P('tg_token') !== '') {
                        $old['tg']['tg_token_new'] = $P('tg_token');     // ยังไม่บันทึก แต่ต้องคงค่าไว้ในช่อง
                    }
                }
            }
        } elseif ($act === 'test_tg') {
            if ($token === '' || !$chats[0]) {
                $err['tg'] = 'กรอก Bot token และ Chat ID อย่างน้อย 1 แชตก่อนส่งทดสอบ';
            } else {
                $res = array();
                foreach ($chats[0] as $c) {
                    $r = tg_api($token, 'sendMessage', array('chat_id' => $c, 'text' => notify_tg_sample('test'), 'parse_mode' => 'HTML',
                                                             'disable_notification' => $in['tg_silent'] === '1' ? 'true' : 'false'));
                    $res[] = ($r[0] ? '✓ ' : '✗ ') . $c . ($r[0] ? '' : ' — ' . $r[1]);
                    notify_log_add('tg', 'test', $c, $r[0], $r[0] ? 'ข้อความทดสอบ' : $r[1]);
                }
                $allOk = strpos(implode('', $res), '✗') === false;
                if ($allOk) {
                    $_SESSION['flash_nt'] = array('tg', 'ok', 'ส่งข้อความทดสอบแล้ว: ' . implode(' · ', $res) . ' — ตรวจดูใน Telegram ได้เลย (ยังไม่ได้บันทึกค่า กด “บันทึก” ด้วย)');
                } else {
                    $err['tg'] = 'ส่งทดสอบไม่สำเร็จ: ' . implode(' · ', $res);
                }
                if ($P('tg_token') !== '') {
                    $old['tg']['tg_token_new'] = $P('tg_token');
                }
            }
        } else {
            if ($in['tg_on'] === '1' && ($token === '' || !$chats[0])) {
                $err['tg'] = 'เปิดใช้การแจ้งเตือน Telegram ต้องมี Bot token และ Chat ID อย่างน้อย 1 แชต';
            } elseif ($in['tg_on'] === '1' && !$in['tg_events']) {
                $err['tg'] = 'เลือกเหตุการณ์ที่จะแจ้งอย่างน้อย 1 อย่าง';
            } elseif ($P('tg_scope') === 'some' && !$in['tg_branches']) {
                $err['tg'] = 'เลือกสาขาอย่างน้อย 1 สาขา หรือเลือก “ทุกสาขา”';
            } else {
                $ch = array();
                if (notify_get('tg_on') !== $in['tg_on']) {
                    $ch['Telegram'] = $in['tg_on'] === '1' ? 'เปิด' : 'ปิด';
                }
                if ($P('tg_token') !== '' && $P('tg_token') !== notify_get('tg_token')) {
                    $ch['Bot token'] = 'เปลี่ยนใหม่ (ลงท้าย ' . substr($token, -4) . ')';
                    notify_set('tg_token', $token);
                }
                if (implode(',', $chats[0]) !== implode(',', tg_chats_parse(notify_get('tg_chats')))) {
                    $ch['Chat ID'] = implode(', ', $chats[0]);
                }
                if ($in['tg_events'] != notify_get('tg_events')) {
                    $nm = array();
                    foreach ($in['tg_events'] as $k) {
                        $nm[] = $evs[$k][0];
                    }
                    $ch['เหตุการณ์'] = implode(' · ', $nm);
                }
                $in['tg_chats'] = implode("\n", $chats[0]);
                foreach ($in as $k => $v) {
                    notify_set($k, $v);
                }
                $logChange('ตั้งค่าการแจ้งเตือน Telegram', $ch);
                $_SESSION['flash_nt'] = array('tg', 'ok', 'บันทึกการแจ้งเตือน Telegram แล้ว' . ($in['tg_on'] === '1' ? ' — เปิดใช้งานอยู่' : ' — ปิดอยู่'));
                header('Location: ' . url('adm-notify.php#nt-tg'));
                exit;
            }
        }
    } elseif (in_array($act, array('save_mail', 'test_mail'), true)) {
        /* ---------- อีเมลรายวัน ---------- */
        $sec = 'mail';
        $in = array(
            'mail_on'       => !empty($_POST['mail_on']) ? '1' : '0',
            'mail_to'       => $P('mail_to'),
            'mail_time'     => preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $P('mail_time')) ? $P('mail_time') : '',
            'mail_days'     => $P('mail_days') === 'all' ? 'all' : 'open',
            'mail_branches' => $P('mail_scope') === 'some' ? $PA('mail_branches', $br) : array(),
            'mail_parts'    => $PA('mail_parts', $mps),
        );
        $old['mail'] = $in;
        $to = mail_list_parse($in['mail_to']);
        if ($to[1]) {
            $err['mail'] = 'อีเมลไม่ถูกต้อง: ' . implode(', ', $to[1]);
        } elseif ($in['mail_time'] === '') {
            $err['mail'] = 'เวลาส่งไม่ถูกต้อง (เช่น 21:00)';
        } elseif ($P('mail_scope') === 'some' && !$in['mail_branches']) {
            $err['mail'] = 'เลือกสาขาอย่างน้อย 1 สาขา หรือเลือก “ทุกสาขา”';
        } elseif ($act === 'test_mail') {
            $tt = mail_list_parse($P('mail_test'));
            $dest = $tt[0] ? $tt[0] : $to[0];
            if ($tt[1]) {
                $err['mail'] = 'อีเมลทดสอบไม่ถูกต้อง: ' . implode(', ', $tt[1]);
            } elseif (!$dest) {
                $err['mail'] = 'กรอกผู้รับ หรืออีเมลสำหรับส่งทดสอบก่อน';
            } else {
                $codes = $in['mail_branches'] ? $in['mail_branches'] : array_keys($br);
                $d     = notify_last_sales_day($codes);
                $data  = daily_summary_data($d, $codes);
                $cfg   = array();
                foreach (array('smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass', 'from_name', 'from_email') as $k) {
                    $cfg[$k] = notify_get($k);
                }
                $r = smtp_send($cfg, $dest, '[ทดสอบ] ' . daily_summary_subject($d, $data), daily_summary_email_html($d, $data, $in['mail_parts']));
                notify_log_add('mail', 'test', implode(', ', $dest), $r[0], $r[1]);
                if ($r[0]) {
                    $_SESSION['flash_nt'] = array('mail', 'ok', 'ส่งอีเมลทดสอบแล้ว (สรุปของ' . thai_date_full($d) . ') ' . $r[1] . ' — ถ้าไม่เห็นให้ดูในโฟลเดอร์ Spam');
                } else {
                    $err['mail'] = $r[1] . ' — ตรวจค่าที่ส่วน “เซิร์ฟเวอร์อีเมล (SMTP)”';
                }
            }
        } else {
            if ($in['mail_on'] === '1' && !$to[0]) {
                $err['mail'] = 'เปิดใช้อีเมลรายวัน ต้องมีผู้รับอย่างน้อย 1 คน';
            } elseif ($in['mail_on'] === '1' && notify_get('from_email') === '' && notify_get('smtp_user') === '') {
                $err['mail'] = 'ยังไม่ได้ตั้งค่าเซิร์ฟเวอร์อีเมล — ตั้งค่าที่ส่วน “เซิร์ฟเวอร์อีเมล (SMTP)” ก่อนเปิดใช้งาน';
            } else {
                $ch = array();
                if (notify_get('mail_on') !== $in['mail_on']) {
                    $ch['อีเมลรายวัน'] = $in['mail_on'] === '1' ? 'เปิด' : 'ปิด';
                }
                if (implode(',', $to[0]) !== implode(',', array_map('strval', mail_list_parse(notify_get('mail_to'))[0]))) {
                    $ch['ผู้รับ'] = implode(', ', $to[0]);
                }
                if (notify_get('mail_time') !== $in['mail_time']) {
                    $ch['เวลาส่ง'] = notify_get('mail_time') . ' → ' . $in['mail_time'] . ' น.';
                }
                $in['mail_to'] = implode("\n", $to[0]);
                foreach ($in as $k => $v) {
                    notify_set($k, $v);
                }
                $logChange('ตั้งค่าอีเมลสรุปยอดขายรายวัน', $ch);
                $_SESSION['flash_nt'] = array('mail', 'ok', 'บันทึกอีเมลสรุปยอดขายรายวันแล้ว' . ($in['mail_on'] === '1' ? ' — ส่งทุกวันเวลา ' . $in['mail_time'] . ' น.' : ' — ปิดอยู่'));
                header('Location: ' . url('adm-notify.php#nt-mail'));
                exit;
            }
        }
    } elseif ($act === 'save_smtp') {
        /* ---------- SMTP ---------- */
        $sec = 'smtp';
        $in = array(
            'smtp_host'   => preg_replace('/[^A-Za-z0-9.-]/', '', $P('smtp_host')),
            'smtp_port'   => (string) (int) $P('smtp_port'),
            'smtp_secure' => in_array($P('smtp_secure'), array('tls', 'ssl', 'none'), true) ? $P('smtp_secure') : 'tls',
            'smtp_user'   => $P('smtp_user'),
            'from_name'   => function_exists('mb_substr') ? mb_substr($P('from_name'), 0, 60, 'UTF-8') : $P('from_name'),
            'from_email'  => $P('from_email'),
        );
        $old['smtp'] = $in;
        if ($in['smtp_host'] !== '' && ((int) $in['smtp_port'] < 1 || (int) $in['smtp_port'] > 65535)) {
            $err['smtp'] = 'พอร์ตไม่ถูกต้อง (ปกติ 587 สำหรับ TLS · 465 สำหรับ SSL · 25 ไม่เข้ารหัส)';
        } elseif ($in['from_email'] !== '' && !filter_var($in['from_email'], FILTER_VALIDATE_EMAIL)) {
            $err['smtp'] = 'อีเมลผู้ส่งไม่ถูกต้อง';
        } elseif ($in['from_email'] === '' && !filter_var($in['smtp_user'], FILTER_VALIDATE_EMAIL)) {
            $err['smtp'] = 'กรอกอีเมลผู้ส่ง (หรือใช้ชื่อผู้ใช้ SMTP ที่เป็นอีเมล)';
        } elseif ($in['from_name'] === '') {
            $err['smtp'] = 'กรอกชื่อผู้ส่ง';
        } else {
            $ch = array();
            foreach (array('smtp_host' => 'SMTP host', 'smtp_port' => 'พอร์ต', 'smtp_secure' => 'การเข้ารหัส', 'smtp_user' => 'ผู้ใช้', 'from_name' => 'ชื่อผู้ส่ง', 'from_email' => 'อีเมลผู้ส่ง') as $k => $lb) {
                if (notify_get($k) !== $in[$k]) {
                    $ch[$lb] = (notify_get($k) === '' ? '(ว่าง)' : notify_get($k)) . ' → ' . ($in[$k] === '' ? '(ว่าง)' : $in[$k]);
                }
                notify_set($k, $in[$k]);
            }
            if (!empty($_POST['smtp_pass_clear'])) {
                notify_set('smtp_pass', '');
                $ch['รหัสผ่าน'] = 'ลบออก';
            } elseif (isset($_POST['smtp_pass']) && is_string($_POST['smtp_pass']) && $_POST['smtp_pass'] !== '') {
                notify_set('smtp_pass', $_POST['smtp_pass']);
                $ch['รหัสผ่าน'] = 'เปลี่ยนใหม่';
            }
            $logChange('ตั้งค่าเซิร์ฟเวอร์อีเมล (SMTP)', $ch);
            $_SESSION['flash_nt'] = array('smtp', 'ok', 'บันทึกเซิร์ฟเวอร์อีเมลแล้ว — ลองกด “ส่งทดสอบ” ที่ส่วนอีเมลสรุปยอดขายรายวัน');
            header('Location: ' . url('adm-notify.php#nt-smtp'));
            exit;
        }
    }
}

/* ---------- ค่าที่แสดงในฟอร์ม ---------- */
$v = function ($s, $k) use ($old) {
    return isset($old[$s][$k]) ? $old[$s][$k] : notify_get($k);
};
$flash = isset($_SESSION['flash_nt']) ? $_SESSION['flash_nt'] : null;
unset($_SESSION['flash_nt']);
if ($sec === '' && $flash) {
    $sec = $flash[0];
}
$open = $sec !== '' ? $sec : 'tg';
$tgOn   = notify_get('tg_on') === '1';
$mailOn = notify_get('mail_on') === '1';
$nChat  = count(tg_chats_parse(notify_get('tg_chats'))[0]);
$nTo    = count(mail_list_parse(notify_get('mail_to'))[0]);
$smtpOk = notify_get('smtp_host') !== '' || notify_get('from_email') !== '';
$tgBr   = $v('tg', 'tg_branches');
$mlBr   = $v('mail', 'mail_branches');
$sampleBr = $tgBr ? $tgBr[0] : key($br);

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = 'ตั้งค่าการแจ้งเตือน';
$PAGE_SUB       = 'Telegram ' . ($tgOn ? 'เปิด' : 'ปิด') . ' · อีเมลสรุปยอดขายรายวัน ' . ($mailOn ? 'เปิด (' . notify_get('mail_time') . ' น.)' : 'ปิด');
$NAV_ACTIVE     = 'adm-notify.php';
require dirname(__FILE__) . '/inc/header.php';

$msg = function ($s) use ($err, $flash) {
    if (isset($err[$s])) {
        return '<div class="alert alert-error adm-ok" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span>' . e($err[$s]) . '</span></div>';
    }
    if ($flash && $flash[0] === $s) {
        return '<div class="alert alert-ok adm-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span>' . e($flash[2]) . '</span></div>';
    }
    return '';
};
?>

<?php if (isset($err['top'])): ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err['top']) ?></span></div>
<?php endif; ?>

<!-- ==================== 1) Telegram ==================== -->
<details class="card acc-item" id="nt-tg"<?= $open === 'tg' ? ' open' : '' ?>>
  <summary class="card-head">
    <div>
      <h2>แจ้งเตือนทาง Telegram</h2>
      <span class="sub"><span class="nt-st <?= $tgOn ? 'on' : '' ?>"><?= $tgOn ? 'เปิดอยู่' : 'ปิดอยู่' ?></span>
        <?= $tgOn ? $nChat . ' แชต · ' . count(notify_get('tg_events')) . ' เหตุการณ์' : 'แจ้งทันทีเมื่อเกิดเหตุการณ์สำคัญในสาขา' ?></span>
    </div>
    <svg class="ico acc-chev" aria-hidden="true"><use href="#i-arrow"/></svg>
  </summary>
  <?= $msg('tg') ?>
  <form class="adm-sec nt-grid" method="post" action="adm-notify.php#nt-tg">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="nt-main">
      <label class="nt-sw"><input type="checkbox" name="tg_on" value="1" <?= $v('tg', 'tg_on') === '1' ? 'checked' : '' ?>>
        <span class="nt-knob" aria-hidden="true"></span><b>เปิดการแจ้งเตือน Telegram</b></label>

      <div class="field">
        <label for="tg-token">Bot token</label>
        <input class="input mono" type="text" id="tg-token" name="tg_token" autocomplete="off" spellcheck="false"
               value="<?= e(isset($old['tg']['tg_token_new']) ? $old['tg']['tg_token_new'] : '') ?>"
               placeholder="<?= notify_get('tg_token') !== '' ? e(mask_secret(notify_get('tg_token'))) . ' (บันทึกไว้แล้ว — เว้นว่าง = ใช้ค่าเดิม)' : '123456789:AAH…' ?>">
        <small class="adm-hint">ได้จาก <b>@BotFather</b> ใน Telegram → พิมพ์ /newbot → ตั้งชื่อบอท → คัดลอก token มาวาง</small>
      </div>

      <div class="field">
        <label for="tg-chats">Chat ID ที่จะรับข้อความ</label>
        <textarea class="input mono" id="tg-chats" name="tg_chats" rows="2" spellcheck="false"
                  placeholder="เช่น 123456789 (คน) หรือ -1001234567890 (กลุ่ม) · หลายแชตขึ้นบรรทัดใหม่"><?= e($v('tg', 'tg_chats')) ?></textarea>
        <small class="adm-hint">ทักบอทก่อน 1 ครั้ง (หรือเพิ่มบอทเข้ากลุ่มแล้วพิมพ์อะไรก็ได้) แล้วกด “ค้นหา Chat ID” ระบบจะดึงรายชื่อแชตมาให้เลือก</small>
        <?php if ($found !== null): ?>
          <div class="nt-found">
            <?php if (!$found): ?>
              <span class="adm-none">ยังไม่พบแชต — ทักบอทหรือพิมพ์ในกลุ่มก่อน แล้วกดค้นหาอีกครั้ง</span>
            <?php else: foreach ($found as $id => $nm): ?>
              <button type="button" class="btn btn-ghost btn-sm" data-add-chat="<?= e($id) ?>"><svg class="ico"><use href="#i-plus"/></svg> <?= e($nm) ?> <code><?= e($id) ?></code></button>
            <?php endforeach; endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <fieldset class="nt-set">
        <legend>แจ้งเมื่อ</legend>
        <?php $sel = $v('tg', 'tg_events'); foreach ($evs as $k => $x): ?>
          <label class="bs-check nt-ev"><input type="checkbox" name="tg_events[]" value="<?= e($k) ?>" data-ev="<?= e($k) ?>" <?= in_array($k, $sel, true) ? 'checked' : '' ?>>
            <span><?= e($x[0]) ?><small><?= e($x[1]) ?></small></span></label>
        <?php endforeach; ?>
      </fieldset>

      <fieldset class="nt-set">
        <legend>สาขา</legend>
        <label class="bs-check"><input type="radio" name="tg_scope" value="all" <?= !$tgBr ? 'checked' : '' ?> data-scope="tg"> ทุกสาขา (รวมสาขาที่เพิ่มภายหลัง)</label>
        <label class="bs-check"><input type="radio" name="tg_scope" value="some" <?= $tgBr ? 'checked' : '' ?> data-scope="tg"> เลือกสาขา</label>
        <div class="nt-brs" data-scope-list="tg"<?= $tgBr ? '' : ' hidden' ?>>
          <?php foreach ($br as $c => $b): ?>
            <label class="bs-check"><input type="checkbox" name="tg_branches[]" value="<?= e($c) ?>" <?= in_array($c, $tgBr, true) ? 'checked' : '' ?>> <?= e($b['name']) ?></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <label class="bs-check"><input type="checkbox" name="tg_silent" value="1" <?= $v('tg', 'tg_silent') === '1' ? 'checked' : '' ?>>
        ส่งแบบไม่มีเสียงแจ้งเตือน (ข้อความยังเข้า แต่โทรศัพท์ไม่ดัง)</label>

      <div class="nt-btns">
        <button class="btn btn-primary" type="submit" name="act" value="save_tg"><svg class="ico"><use href="#i-check"/></svg> บันทึก</button>
        <button class="btn btn-ghost" type="submit" name="act" value="test_tg" formnovalidate><svg class="ico"><use href="#i-activity"/></svg> ส่งข้อความทดสอบ</button>
        <button class="btn btn-ghost" type="submit" name="act" value="find_tg" formnovalidate><svg class="ico"><use href="#i-search"/></svg> ค้นหา Chat ID</button>
      </div>
    </div>

    <aside class="nt-prev" aria-label="ตัวอย่างข้อความ">
      <div class="nt-phone">
        <div class="nt-phone-h"><span class="nt-ava">AO</span><div><b><?= e(APP_NAME) ?> Bot</b><small>ตัวอย่างข้อความ</small></div></div>
        <div class="nt-chat">
          <?php foreach ($evs as $k => $x): $t = notify_tg_sample($k, $sampleBr); ?>
            <div class="nt-msg" data-ev-prev="<?= e($k) ?>"<?= in_array($k, $sel, true) ? '' : ' hidden' ?>><?= nl2br($t) ?><span class="nt-time"><?= e($k === 'open' ? '08:42' : '20:11') ?></span></div>
          <?php endforeach; ?>
          <p class="nt-none" <?= $sel ? 'hidden' : '' ?>>เลือกเหตุการณ์ทางซ้ายเพื่อดูตัวอย่าง</p>
        </div>
      </div>
      <small class="adm-hint">ตัวอย่างใช้ข้อมูลของ<?= e($br[$sampleBr]['name']) ?> · ข้อความจริงส่งทันทีเมื่อเกิดเหตุการณ์</small>
    </aside>
  </form>
</details>

<!-- ==================== 2) อีเมลรายวัน ==================== -->
<details class="card acc-item" id="nt-mail"<?= $open === 'mail' ? ' open' : '' ?>>
  <summary class="card-head">
    <div>
      <h2>อีเมลสรุปยอดขายรายวัน</h2>
      <span class="sub"><span class="nt-st <?= $mailOn ? 'on' : '' ?>"><?= $mailOn ? 'เปิดอยู่' : 'ปิดอยู่' ?></span>
        <?= $mailOn ? 'ส่งทุกวัน ' . e(notify_get('mail_time')) . ' น. ถึง ' . $nTo . ' คน' : 'ยอดขายแต่ละสาขาของวัน ส่งเข้าอีเมลทุกวันอัตโนมัติ' ?></span>
    </div>
    <svg class="ico acc-chev" aria-hidden="true"><use href="#i-arrow"/></svg>
  </summary>
  <?= $msg('mail') ?>
  <?php if (!$smtpOk): ?>
    <div class="alert adm-ok nt-warn" role="note"><svg class="ico"><use href="#i-info"/></svg>
      <span>ยังไม่ได้ตั้งค่าเซิร์ฟเวอร์อีเมล — ตั้งที่ส่วน <a href="#nt-smtp">“เซิร์ฟเวอร์อีเมล (SMTP)”</a> ด้านล่างก่อนเปิดใช้งาน</span></div>
  <?php endif; ?>
  <form class="adm-sec" method="post" action="adm-notify.php#nt-mail" id="mail-form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label class="nt-sw"><input type="checkbox" name="mail_on" value="1" <?= $v('mail', 'mail_on') === '1' ? 'checked' : '' ?>>
      <span class="nt-knob" aria-hidden="true"></span><b>เปิดส่งอีเมลสรุปยอดขายรายวัน</b></label>

    <div class="adm-fields">
      <div class="field bs-wide">
        <label for="mail-to">ผู้รับ</label>
        <textarea class="input" id="mail-to" name="mail_to" rows="2" spellcheck="false"
                  placeholder="owner@company.com&#10;account@company.com"><?= e($v('mail', 'mail_to')) ?></textarea>
        <small class="adm-hint">หลายคนขึ้นบรรทัดใหม่หรือคั่นด้วยจุลภาค · เช่น เจ้าของร้าน ฝ่ายบัญชี ผู้จัดการเขต</small>
      </div>
      <div class="field">
        <label for="mail-time">เวลาส่ง</label>
        <input class="input" type="time" id="mail-time" name="mail_time" value="<?= e($v('mail', 'mail_time')) ?>" required>
        <small class="adm-hint">ควรหลังเวลาปิดร้านของทุกสาขา · สรุปของ “วันนั้น”</small>
      </div>
      <div class="field">
        <span class="lbl">ส่งวันไหน</span>
        <label class="bs-check"><input type="radio" name="mail_days" value="open" <?= $v('mail', 'mail_days') !== 'all' ? 'checked' : '' ?>> เฉพาะวันที่มียอดขาย</label>
        <label class="bs-check"><input type="radio" name="mail_days" value="all" <?= $v('mail', 'mail_days') === 'all' ? 'checked' : '' ?>> ทุกวัน (วันหยุดแจ้งว่าไม่มียอด)</label>
      </div>
    </div>

    <div class="nt-cols">
      <fieldset class="nt-set">
        <legend>สาขาในรายงาน</legend>
        <label class="bs-check"><input type="radio" name="mail_scope" value="all" <?= !$mlBr ? 'checked' : '' ?> data-scope="mail"> ทุกสาขา</label>
        <label class="bs-check"><input type="radio" name="mail_scope" value="some" <?= $mlBr ? 'checked' : '' ?> data-scope="mail"> เลือกสาขา</label>
        <div class="nt-brs" data-scope-list="mail"<?= $mlBr ? '' : ' hidden' ?>>
          <?php foreach ($br as $c => $b): ?>
            <label class="bs-check"><input type="checkbox" name="mail_branches[]" value="<?= e($c) ?>" <?= in_array($c, $mlBr, true) ? 'checked' : '' ?>> <?= e($b['name']) ?></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <fieldset class="nt-set">
        <legend>ในอีเมลมี</legend>
        <label class="bs-check"><input type="checkbox" checked disabled> ยอดขาย · จำนวนบิล แยกสาขา (มีเสมอ)</label>
        <?php $pp = $v('mail', 'mail_parts'); foreach ($mps as $k => $x): ?>
          <label class="bs-check"><input type="checkbox" name="mail_parts[]" value="<?= e($k) ?>" <?= in_array($k, $pp, true) ? 'checked' : '' ?>> <?= e($x[0]) ?></label>
        <?php endforeach; ?>
      </fieldset>
    </div>

    <div class="nt-btns">
      <button class="btn btn-primary" type="submit" name="act" value="save_mail"><svg class="ico"><use href="#i-check"/></svg> บันทึก</button>
      <button class="btn btn-ghost" type="button" id="mail-prev"><svg class="ico"><use href="#i-receipt"/></svg> ดูตัวอย่างอีเมล</button>
      <span class="nt-test">
        <label class="sr-only" for="mail-test">ส่งทดสอบไปที่</label>
        <input class="input" type="email" id="mail-test" name="mail_test" placeholder="ส่งทดสอบไปที่ (ว่าง = ผู้รับทั้งหมด)" autocomplete="email">
        <button class="btn btn-ghost" type="submit" name="act" value="test_mail" formnovalidate><svg class="ico"><use href="#i-activity"/></svg> ส่งทดสอบ</button>
      </span>
    </div>
  </form>
</details>

<!-- ==================== 3) SMTP ==================== -->
<details class="card acc-item" id="nt-smtp"<?= $open === 'smtp' ? ' open' : '' ?>>
  <summary class="card-head">
    <div>
      <h2>เซิร์ฟเวอร์อีเมล (SMTP)</h2>
      <span class="sub"><?= notify_get('smtp_host') !== '' ? e(notify_get('smtp_host')) . ':' . e(notify_get('smtp_port')) . ' · ' . strtoupper(e(notify_get('smtp_secure'))) . ' · ผู้ส่ง ' . e(notify_get('from_email') !== '' ? notify_get('from_email') : notify_get('smtp_user'))
                       : 'ใช้ส่งอีเมลรายวัน · ใช้อีเมลบริษัท, Google Workspace หรือ Microsoft 365 ได้' ?></span>
    </div>
    <svg class="ico acc-chev" aria-hidden="true"><use href="#i-arrow"/></svg>
  </summary>
  <?= $msg('smtp') ?>
  <form class="adm-sec" method="post" action="adm-notify.php#nt-smtp">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="act" value="save_smtp">
    <div class="nt-presets">
      <span class="adm-hint">ตั้งค่าด่วน:</span>
      <button type="button" class="btn btn-ghost btn-sm" data-smtp="smtp.gmail.com|587|tls">Gmail / Google Workspace</button>
      <button type="button" class="btn btn-ghost btn-sm" data-smtp="smtp.office365.com|587|tls">Microsoft 365</button>
      <button type="button" class="btn btn-ghost btn-sm" data-smtp="mail.yourdomain.com|465|ssl">อีเมลบนโฮสติ้ง</button>
    </div>
    <div class="adm-fields">
      <div class="field">
        <label for="sm-host">SMTP host</label>
        <input class="input" type="text" id="sm-host" name="smtp_host" value="<?= e($v('smtp', 'smtp_host')) ?>" placeholder="smtp.gmail.com" autocomplete="off">
        <small class="adm-hint">เว้นว่าง = ใช้ mail() ของเซิร์ฟเวอร์ (มักเข้า Spam ไม่แนะนำ)</small>
      </div>
      <div class="field nt-pp">
        <div>
          <label for="sm-port">พอร์ต</label>
          <input class="input" type="number" id="sm-port" name="smtp_port" value="<?= e($v('smtp', 'smtp_port')) ?>" min="1" max="65535" inputmode="numeric">
        </div>
        <div>
          <label for="sm-sec">การเข้ารหัส</label>
          <select class="input" id="sm-sec" name="smtp_secure">
            <?php foreach (array('tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'ไม่เข้ารหัส (25)') as $k => $lb): ?>
              <option value="<?= e($k) ?>" <?= $v('smtp', 'smtp_secure') === $k ? 'selected' : '' ?>><?= e($lb) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field">
        <label for="sm-user">ชื่อผู้ใช้</label>
        <input class="input" type="text" id="sm-user" name="smtp_user" value="<?= e($v('smtp', 'smtp_user')) ?>" placeholder="report@company.com" autocomplete="off">
      </div>
      <div class="field">
        <label for="sm-pass">รหัสผ่าน</label>
        <input class="input" type="password" id="sm-pass" name="smtp_pass" value="" autocomplete="new-password"
               placeholder="<?= notify_get('smtp_pass') !== '' ? 'บันทึกไว้แล้ว — เว้นว่าง = ใช้ค่าเดิม' : '' ?>">
        <small class="adm-hint">Gmail / Google Workspace ใช้ “App password” 16 ตัว ไม่ใช่รหัสผ่านปกติ
          <?php if (notify_get('smtp_pass') !== ''): ?> · <label class="nt-inline"><input type="checkbox" name="smtp_pass_clear" value="1"> ลบรหัสผ่านที่บันทึกไว้</label><?php endif; ?></small>
      </div>
      <div class="field">
        <label for="sm-fn">ชื่อผู้ส่ง</label>
        <input class="input" type="text" id="sm-fn" name="from_name" value="<?= e($v('smtp', 'from_name')) ?>" maxlength="60" required>
      </div>
      <div class="field">
        <label for="sm-fe">อีเมลผู้ส่ง</label>
        <input class="input" type="email" id="sm-fe" name="from_email" value="<?= e($v('smtp', 'from_email')) ?>" placeholder="ว่าง = ใช้ชื่อผู้ใช้">
        <small class="adm-hint">ควรเป็นโดเมนเดียวกับ SMTP ไม่อย่างนั้นอาจเข้า Spam</small>
      </div>
    </div>
    <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-check"/></svg> บันทึกเซิร์ฟเวอร์อีเมล</button>
  </form>
</details>

<!-- ==================== 4) ประวัติการส่ง ==================== -->
<?php $logs = notify_log_rows(); ?>
<details class="card acc-item" id="nt-log"<?= $open === 'log' ? ' open' : '' ?>>
  <summary class="card-head">
    <div><h2>ประวัติการส่งล่าสุด</h2><span class="sub"><?= $logs ? count($logs) . ' รายการล่าสุด · ส่งไม่สำเร็จจะเห็นสาเหตุ' : 'ยังไม่มีการส่ง' ?></span></div>
    <svg class="ico acc-chev" aria-hidden="true"><use href="#i-arrow"/></svg>
  </summary>
  <?php if (!$logs): ?>
    <p class="empty">ยังไม่มีการส่ง — เปิดใช้งานหรือกดส่งทดสอบแล้วจะแสดงที่นี่</p>
  <?php else: ?>
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th>เวลา</th><th>ช่องทาง</th><th>เรื่อง</th><th>ถึง</th><th>ผล</th></tr></thead>
        <tbody>
          <?php foreach ($logs as $l): ?>
            <tr>
              <td data-label="เวลา" class="num"><?= e(thai_day_month($l['ts'])) ?> <?= date('H:i', $l['ts']) ?><?= !empty($l['sample']) ? '<small>ตัวอย่าง</small>' : '' ?></td>
              <td data-label="ช่องทาง"><span class="bdg <?= $l['channel'] === 'tg' ? 'bdg-move' : 'bdg-adj' ?>"><?= $l['channel'] === 'tg' ? 'Telegram' : 'อีเมล' ?></span></td>
              <td data-label="เรื่อง"><?= $l['event'] === 'test' ? 'ทดสอบ' : e($l['msg']) ?></td>
              <td data-label="ถึง"><small><?= e($l['to']) ?></small></td>
              <td data-label="ผล"><?= $l['ok'] ? '<span class="nt-ok">สำเร็จ</span>' : '<span class="nt-bad" title="' . e($l['msg']) . '">ไม่สำเร็จ</span><small>' . e($l['msg']) . '</small>' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</details>

<!-- popup ตัวอย่างอีเมล -->
<dialog class="bill-modal is-a4" id="mail-modal" aria-label="ตัวอย่างอีเมล">
  <div class="bill-bar"><b>ตัวอย่างอีเมลสรุปยอดขายรายวัน</b><span class="bill-sp"></span>
    <button type="button" class="icon-btn" data-mail-close aria-label="ปิด"><svg class="ico"><use href="#i-x"/></svg></button></div>
  <iframe id="mail-frame" title="ตัวอย่างอีเมล" src="about:blank"></iframe>
</dialog>

<script>
(function () {
  /* เลือกเหตุการณ์ → แสดง/ซ่อนตัวอย่างข้อความ */
  function syncPrev() {
    var any = false;
    document.querySelectorAll('[data-ev]').forEach(function (cb) {
      var m = document.querySelector('[data-ev-prev="' + cb.getAttribute('data-ev') + '"]');
      if (m) { m.hidden = !cb.checked; }
      any = any || cb.checked;
    });
    var n = document.querySelector('.nt-none');
    if (n) { n.hidden = any; }
  }
  document.addEventListener('change', function (ev) {
    if (ev.target.hasAttribute('data-ev')) { syncPrev(); }
    var sc = ev.target.getAttribute('data-scope');
    if (sc) {
      var l = document.querySelector('[data-scope-list="' + sc + '"]');
      if (l) { l.hidden = ev.target.value !== 'some'; }
    }
  });
  /* เพิ่ม Chat ID ที่ค้นเจอลงช่อง */
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-add-chat]');
    if (b) {
      var ta = document.getElementById('tg-chats'), id = b.getAttribute('data-add-chat');
      var list = ta.value.split(/[\s,]+/).filter(Boolean);
      if (list.indexOf(id) < 0) { list.push(id); }
      ta.value = list.join('\n');
      b.disabled = true;
      return;
    }
    var p = ev.target.closest('[data-smtp]');
    if (p) {
      var x = p.getAttribute('data-smtp').split('|');
      document.getElementById('sm-host').value = x[0];
      document.getElementById('sm-port').value = x[1];
      document.getElementById('sm-sec').value = x[2];
      document.getElementById('sm-host').focus();
    }
  });
  /* ตัวอย่างอีเมลตามที่ติ๊กอยู่ (ยังไม่ต้องบันทึก) */
  var dlg = document.getElementById('mail-modal'), fr = document.getElementById('mail-frame');
  document.getElementById('mail-prev').addEventListener('click', function () {
    var f = document.getElementById('mail-form'), q = [];
    f.querySelectorAll('[name="mail_parts[]"]:checked').forEach(function (c) { q.push('p[]=' + encodeURIComponent(c.value)); });
    if (f.querySelector('[name="mail_scope"]:checked').value === 'some') {
      f.querySelectorAll('[name="mail_branches[]"]:checked').forEach(function (c) { q.push('b[]=' + encodeURIComponent(c.value)); });
    }
    fr.src = 'adm-notify-preview.php?' + q.join('&');
    dlg.showModal();
  });
  dlg.addEventListener('click', function (ev) {
    if (ev.target === dlg || ev.target.closest('[data-mail-close]')) { dlg.close(); }
  });
  dlg.addEventListener('close', function () { fr.src = 'about:blank'; });
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
