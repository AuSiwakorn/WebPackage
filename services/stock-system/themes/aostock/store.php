<?php
/**
 * FILE: themes/aostock/store.php
 * ROLE: เปิด / ปิดร้านประจำวัน และเงินทอนในลิ้นชัก
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_store_day, ao_stock_cash_move, ao_stock_sale, ao_stock_sale_item, ao_stock_return, ao_stock_log (ผ่าน api.php)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 7: เปิด–ปิดร้านลงตาราง · เงินทอนยกมาของจริง · เตือนเมื่อวันก่อนยังไม่ปิดร้าน
 *   - [x] ช่วงที่ 7: ฟอร์มเติมเงินทอน / หยิบเงินออกระหว่างวัน (act=cash) · ยอดเงินเป็นทศนิยม 2 ตำแหน่ง
 *   - [x] การ์ดตอนปิดร้าน = สินค้าที่ขายวันนี้ + ยอดเงินสด / โอน (เดิม "งานของสาขาวันนี้" — ดู CHANGELOG 2 พ.ย.)
 *   - [x] ช่วงที่ 11: แยกสิทธิ์ เปิด / ปิดร้าน (store) · เปิดร้านอีกครั้ง (store_reopen — เดิมเปิดได้เฉพาะผู้ดูแลจากหน้าภาพรวม adm-dashboard ซึ่งยังเปิดได้เหมือนเดิม) · เงินเข้า / ออก (cash)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 *   - [x] ช่อง “เติมเงินทอนวันนี้” / “แยกเงินทอนไว้สำหรับพรุ่งนี้” รับยอดใดก็ได้ (ทศนิยม 2 ตำแหน่ง) — เดิม step 100 กรอก 250 แล้วส่งไม่ได้
 *   - [x] ช่วงที่ 17: วันล่าสุดก่อนวันนี้ยังไม่ได้ปิด → ขั้นที่ 1 ปิดร้านของวันนั้น (act=close_past) ก่อนเปิดร้านวันนี้ · เปิดร้านถูกกันไว้จนกว่าจะปิด
 *   - [x] 8 ต.ค.: การ์ด “เปิดร้านใหม่” ปุ่มติดใต้ช่องเหตุผล → ฟอร์ม class store-reopen-f เว้นระยะแบบเดียวกับฟอร์มผู้ดูแล
 *   - [x] 12 ต.ค.: แถว “− หยิบออกใช้จ่าย” / “− คืนเงินลูกค้า” ขึ้น −0.00 (ลบซ้ำ) → เครื่องหมายอยู่ที่ป้ายอย่างเดียว แบบเดียวกับแถว +
 *   - [x] 12 ต.ค.: กด “แจ้งยอดไม่ตรง” แล้วเหลือกล่องคำแนะนำเปล่า (ซ่อนแค่ข้อความ) → ซ่อนทั้งกล่อง
 *   - [x] 15 ต.ค. (ใบนับแบบรายการ ช่วง 5): เปลี่ยนชื่อ “ตรวจนับ / ปรับยอด” เป็น “ตรวจนับสต๊อก” (ทางลัดในหน้าร้าน)
 *   - [x] 15 ต.ค. (เมนูประวัติแยกหน้า ช่วง 5): ทางลัด “ประวัติการทำรายการ” → “ประวัติ” (พาไปกลุ่มแรกที่ดูได้)
 *   - [x] 16 ต.ค. (ผู้ใช้ขอ): ทางลัด “ประวัติเคลื่อนไหว” → “เคลื่อนไหวรายสินค้า” ให้ตรงกับเมนู
 *   - [x] 21 ต.ค.: ฟอร์มเงินเข้า / ออก — บันทึกไม่ผ่านแล้วค่าที่กรอกไม่หาย · ผลแสดงในการ์ดของฟอร์ม (#cash-card) · เหตุผลเปิดร้านใหม่อย่างน้อย 5 ตัวอักษร
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
require_once dirname(__FILE__) . '/include/function.php';

$user   = require_login();
$code   = work_branch($user);            // พนักงาน = สาขาตัวเอง · ผู้ดูแล = สาขาที่เลือกบนแถบบน
$carry  = store_carry($code);
$cash   = store_daily_cash($code);
$notice = '';
$error  = '';
$cashErr  = '';                          // ฟอร์มเงินเข้า / ออก: ข้อความแสดงในการ์ดของฟอร์ม · ค่าที่กรอกไว้ไม่หายเมื่อบันทึกไม่ผ่าน (21 ต.ค.)
$cashNote = '';
$cashPost = array();

/* ---------- รับค่าจากฟอร์ม ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $error = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } else {
        $act  = isset($_POST['act']) ? $_POST['act'] : '';
        $need = array('open' => 'store', 'close' => 'store', 'close_past' => 'store', 'reopen' => 'store_reopen', 'cash' => 'cash');   // ช่วงที่ 11 · 17

        if (isset($need[$act]) && !can($user, $need[$act])) {
            $pl    = perm_list();
            $error = 'ไม่มีสิทธิ์ “' . $pl[$need[$act]]['short'] . '” — ติดต่อผู้ดูแลเพื่อเปิดสิทธิ์';
        } elseif ($act === 'open' && !store_is_open($code) && $carry['unclosed'] !== '') {
            /* ช่วงที่ 17: วันก่อนยังไม่ได้ปิด — ต้องปิดก่อน (เงินในลิ้นชักตอนนี้ยังเป็นของวันนั้น) */
            $error = 'ปิดร้านของวัน' . thai_date_full(strtotime($carry['unclosed'])) . 'ก่อน แล้วจึงเปิดร้านวันนี้';
        } elseif ($act === 'close_past' && !store_is_open($code) && !store_is_closed($code)) {
            /* ช่วงที่ 17: ปิดร้านย้อนหลัง — พนักงานปิดได้เฉพาะวันล่าสุดที่ค้าง ก่อนเปิดร้านวันนี้ (วันเก่ากว่านั้นให้ผู้ดูแลปิด) */
            $day = isset($_POST['day']) ? preg_replace('/\D/', '', (string) $_POST['day']) : '';
            if ($day === '' || $day !== $carry['unclosed']) {
                $error = 'ปิดย้อนหลังได้เฉพาะวันล่าสุดที่ยังไม่ได้ปิด — วันอื่นให้ผู้ดูแลปิดที่หน้าภาพรวม';
            } else {
                $r = Stock_closePastDay($code, $day, $user,
                                        isset($_POST['cash_counted']) ? preg_replace('/[^0-9.]/', '', $_POST['cash_counted']) : 0,
                                        isset($_POST['keep']) ? preg_replace('/[^0-9.]/', '', $_POST['keep']) : branch_default_float($code),
                                        isset($_POST['note']) ? $_POST['note'] : '',
                                        isset($_POST['bank_counted']) ? preg_replace('/[^0-9.\-]/', '', $_POST['bank_counted']) : '');
                if (isset($r['error'])) {
                    $error = $r['error'];
                } else {
                    header('Location: ' . url('store.php?pastclosed=' . $day));
                    exit;
                }
            }
        } elseif ($act === 'open' && !store_is_open($code)) {
            $topup   = isset($_POST['topup']) ? round((float) preg_replace('/[^0-9.]/', '', $_POST['topup']), 2) : 0;
            $counted = (isset($_POST['counted']) && $_POST['counted'] !== '') ? round((float) preg_replace('/[^0-9.]/', '', $_POST['counted']), 2) : null;
            $reason  = isset($_POST['reason']) ? $_POST['reason'] : '';
            /* ยอดบัญชีโอนยกมาไม่ตรงกับในแอป (23 พ.ย.) — ว่าง = ตรง */
            $bankIn  = isset($_POST['bank_counted']) ? preg_replace('/[^0-9.\-]/', '', (string) $_POST['bank_counted']) : '';
            $bankCnt = ($bankIn === '' || !is_numeric($bankIn)) ? null : round((float) $bankIn, 2);
            $bankWhy = isset($_POST['bank_reason']) ? (string) $_POST['bank_reason'] : '';
            $bankCar = Stock_getBankCarry($code);

            if ($counted !== null && abs($counted - $carry['amount']) >= 0.01 && trim($reason) === '') {
                $error = 'กรุณาระบุเหตุผลเมื่อยอดเงินทอนไม่ตรงกับที่ยกมา';
            } elseif ($bankCnt !== null && abs($bankCnt - $bankCar['amount']) >= 0.005 && trim($bankWhy) === '') {
                $error = 'กรุณาระบุเหตุผลเมื่อยอดบัญชีโอนไม่ตรงกับที่ยกมา';
            } else {
                store_open($code, $user, $topup, $counted, $reason, $bankCnt, $bankWhy);
                header('Location: ' . url('store.php?opened=1'));
                exit;
            }
        } elseif ($act === 'reopen' && store_is_closed($code)) {
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            if (($why = Stock_checkReopenReason($reason)) !== '') {
                $error = $why;
            } else {
                store_reopen($code, $user, $reason);
                header('Location: ' . url('store.php?reopened=1'));
                exit;
            }
        } elseif ($act === 'close' && store_is_open($code)) {
            $counted = isset($_POST['cash_counted']) ? round((float) preg_replace('/[^0-9.]/', '', $_POST['cash_counted']), 2) : 0;
            $keep    = isset($_POST['keep']) ? round((float) preg_replace('/[^0-9.]/', '', $_POST['keep']), 2) : branch_default_float($code);
            $note    = isset($_POST['note']) ? $_POST['note'] : '';
            $diff    = round($counted - store_expected_cash($code), 2);
            /* บัญชีโอน (วันที่มี) — ต้องกรอกยอดในแอป · ไม่ตรงต้องมีหมายเหตุ (23 พ.ย.) */
            $bankDay = Stock_getDayBank(store_state($code)['id']);
            $bankIn  = isset($_POST['bank_counted']) ? preg_replace('/[^0-9.\-]/', '', (string) $_POST['bank_counted']) : '';
            $bankCnt = ($bankIn === '' || !is_numeric($bankIn)) ? null : round((float) $bankIn, 2);

            /* เงินไม่ตรงกับที่ควรมี = การแก้ไขตัวเลข ต้องมีหมายเหตุทุกครั้ง */
            if (abs($diff) >= 0.01 && trim($note) === '') {
                $error = 'เงินที่นับได้ไม่ตรงกับยอดที่ควรมี กรุณากรอกหมายเหตุว่าเกิดจากอะไร';
            } elseif ($bankDay !== null && $bankCnt === null) {
                $error = 'กรุณากรอกยอดคงเหลือในแอปธนาคารของบัญชีสาขา';
            } elseif ($bankDay !== null && abs($bankCnt - $bankDay['expected']) >= 0.005 && trim($note) === '') {
                $error = 'ยอดในบัญชีโอนไม่ตรงกับยอดที่ควรมี กรุณากรอกหมายเหตุว่าเกิดจากอะไร';
            } elseif (store_close($code, $user, $counted, $keep, $note, $bankCnt) === null) {
                $error = 'ร้านปิดไปแล้ว (อาจมีเครื่องอื่นปิดก่อน)';
            } else {
                header('Location: ' . url('store.php?closed=1'));
                exit;
            }
        } elseif ($act === 'cash') {
            /* เติมเงินทอน / หยิบเงินออกระหว่างวัน — ต้องมีเหตุผลเสมอ */
            $r = store_cash_add($code, $user, isset($_POST['dir']) ? $_POST['dir'] : '',
                                isset($_POST['amount']) ? preg_replace('/[^0-9.]/', '', $_POST['amount']) : 0,
                                isset($_POST['why']) ? $_POST['why'] : '',
                                (isset($_POST['channel']) && $_POST['channel'] === 'transfer') ? 'transfer' : 'cash');
            if (isset($r['error'])) {
                $cashErr  = $r['error'];
                $cashPost = $_POST;
            } else {
                header('Location: ' . url('store.php?cash=1#cash-card'));
                exit;
            }
        }
    }
}

if (isset($_GET['opened'])) { $notice = 'เปิดร้านเรียบร้อย เริ่มทำงานได้เลย'; }
if (isset($_GET['closed'])) { $notice = 'ปิดร้านเรียบร้อย สรุปยอดถูกส่งให้ผู้ดูแลแล้ว'; }
if (isset($_GET['reopened'])) { $notice = 'เปิดร้านใหม่แล้ว ขายต่อได้ — ปิดร้านอีกครั้งเมื่อเสร็จ'; }
if (isset($_GET['cash'])) { $cashNote = 'บันทึกเงินเข้า / ออกลิ้นชักแล้ว'; }
if (isset($_GET['pastclosed']) && ($pcTs = strtotime(preg_replace('/\D/', '', (string) $_GET['pastclosed']))) !== false) {
    $notice = 'ปิดร้านของวัน' . thai_date_full($pcTs) . 'แล้ว — เปิดร้านวันนี้ต่อได้เลย เงินทอนที่แยกไว้ยกมาให้แล้ว';
}

/* ช่วงที่ 17: วันล่าสุดก่อนวันนี้ยังไม่ได้ปิด → ขั้นที่ 1 ปิดร้านของวันนั้นก่อน (นับเงินในลิ้นชักก่อนขายบิลแรก) */
$carry = store_carry($code);                         // อ่านใหม่หลังบันทึก
$past  = null;
if (!store_is_open($code) && !store_is_closed($code) && $carry['unclosed'] !== '') {
    $pr = store_day_row($code, date('Y-m-d', strtotime($carry['unclosed'])));
    if ($pr !== null && $pr['status'] === 'open') {
        $past = array('ymd' => $carry['unclosed'], 'cash' => Stock_getDayCash($pr['day_id']), 'bank' => Stock_getDayBank($pr['day_id']),
                      'opened' => date('H:i', strtotime($pr['opened_at'])), 'by' => ($pr['opened_name'] !== null) ? $pr['opened_name'] : '-');
    }
}

$state    = store_state($code);
$isOpen   = store_is_open($code);
$isClosed = store_is_closed($code);
$cash     = store_daily_cash($code);                 // อ่านใหม่หลังบันทึก
$expected = store_expected_cash($code);
$cashList = $isOpen ? store_cash_list($code) : array();
/* เงินสองกอง (23 พ.ย.): ลิ้นชัก + บัญชีโอนของสาขา · บัญชีโอน null = วันนี้ไม่มี (เปิดก่อนเปิดใช้) */
$dayCash   = ($state !== null) ? Stock_getDayCash($state['id']) : null;
$bank      = ($state !== null) ? Stock_getDayBank($state['id']) : null;
$bankList  = ($isOpen && $bank !== null) ? Stock_getTransferList($state['id']) : array();
$bankCarry = (!$isOpen && !$isClosed) ? Stock_getBankCarry($code) : null;
$num      = function ($n) { return rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.'); };   // ค่าในช่อง input
$cv       = function ($k) use ($cashPost) { return isset($cashPost[$k]) ? trim((string) $cashPost[$k]) : ''; };   // ค่าที่กรอกไว้ในฟอร์มเงินเข้า / ออก

/* ยอดขายวันนี้ (เงินสด / โอน) + สินค้าที่ขายได้ — การ์ดตอนปิดร้าน และสรุปหลังปิด */
$daySum  = ($isOpen || $isClosed) ? acct_range($code, strtotime(date('Y-m-d')), strtotime(date('Y-m-d'))) : null;
$sold    = $isOpen ? Stock_getSoldProductsOfDay($code, time()) : array();
$soldQty = 0;
foreach ($sold as $sp) { $soldQty += $sp['qty']; }

$branch = $code;                       // ตัวเลือกสาขาบน topbar ล็อกที่สาขาของร้าน

$PAGE_TITLE = $isOpen ? 'ร้านเปิดอยู่' : ($isClosed ? 'ปิดร้านแล้ว' : ($past !== null ? 'ปิดร้านของวันก่อน' : 'เปิดร้านวันนี้'));
$PAGE_SUB   = branch_name($code) . ' · ' . thai_date_full(time());
$NAV_ACTIVE = 'store.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($notice !== '') { ?>
  <div class="alert alert-info" style="margin:0">
    <svg class="ico"><use href="#i-check"/></svg><span><?php echo e($notice) ?></span>
  </div>
<?php } ?>

<?php if ($error !== '') { ?>
  <div class="alert alert-error" style="margin:0">
    <svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($error) ?></span>
  </div>
<?php } ?>

<?php if (!$isOpen && !$isClosed) { ?>
<!-- ==================== ยังไม่เปิดร้าน ==================== -->
<div class="store-wrap">
  <div class="store-hero">
    <span class="ic"><svg class="ico"><use href="#i-store"/></svg></span>
    <h2><?php echo $past !== null && can($user, 'store') ? 'ปิดร้านของวันก่อน แล้วเปิดร้านวันนี้' : 'เปิดร้านวันนี้' ?></h2>
    <p><?php echo e(branch_name($code)) ?> · <?php echo e(thai_date_full(time())) ?> · <?php echo e($user['name']) ?></p>
  </div>

  <?php if ($carry['unclosed'] !== '') { ?>
    <div class="alert alert-warn" role="alert">
      <svg class="ico"><use href="#i-alert"/></svg>
      <span>วัน<?php echo e(thai_date_full(strtotime($carry['unclosed']))) ?> ยังไม่ได้กดปิดร้าน —
            <?php echo ($past !== null && can($user, 'store'))
                ? 'ปิดร้านของวันนั้นก่อน (ขั้นที่ 1) แล้วจึงเปิดร้านวันนี้ (ขั้นที่ 2) · ผู้ดูแลเห็นรายการนี้ใน “รายการที่ต้องตรวจ”'
                : 'แจ้งคนที่มีสิทธิ์ “เปิด / ปิดร้าน” ให้ปิดร้านของวันนั้นก่อน แล้วจึงเปิดร้านวันนี้' ?></span>
    </div>
  <?php } ?>

  <?php if (!can($user, 'store')) { ?>
  <p class="store-note">ร้านยังไม่เปิด — เปิดร้านได้เฉพาะคนที่มีสิทธิ์ “เปิด / ปิดร้าน” · ระหว่างนี้ทำงานอื่นที่ไม่ต้องเปิดร้านได้ตามปกติ</p>
  <?php } elseif ($past !== null) { $pc = $past['cash']; ?>
  <!-- ช่วงที่ 17 · ขั้นที่ 1: ปิดร้านของวันที่ลืมปิด — ยังไม่ได้ขายของวันนี้ เงินในลิ้นชักตอนนี้คือเงินของวันนั้น -->
  <form class="card" method="post" action="store.php" id="past-form">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
    <input type="hidden" name="act" value="close_past">
    <input type="hidden" name="day" value="<?php echo e($past['ymd']) ?>">
    <div class="card-head">
      <div><h3>ขั้นที่ 1 · ปิดร้านของวัน<?php echo e(thai_date_full(strtotime($past['ymd']))) ?></h3>
        <p>เปิดร้านวันนั้น <?php echo e($past['opened']) ?> น. โดย <?php echo e($past['by']) ?> · ยังไม่ได้ขายของวันนี้ เงินในลิ้นชักตอนนี้คือเงินของวันนั้น — นับแล้วกรอกด้านล่าง</p></div>
    </div>
    <div class="card-body num">
      <div class="kvl">
        <div><span>เงินทอนเริ่มวัน</span><b><?php echo e(money2($pc['float'])) ?></b></div>
        <div><span>+ เติมระหว่างวัน</span><b><?php echo e(money2($pc['topup'])) ?></b></div>
        <div><span>+ ขายเงินสด</span><b><?php echo e(money2($pc['sales'])) ?></b></div>
        <div><span>− หยิบออกใช้จ่าย</span><b><?php echo e(money2($pc['withdraw'])) ?></b></div>
        <?php if ($pc['refunds'] + $pc['void_refunds'] > 0) { ?>
        <div><span>− คืนเงินลูกค้า (รับคืน · ยกเลิกบิล)</span><b><?php echo e(money2($pc['refunds'] + $pc['void_refunds'])) ?></b></div>
        <?php } ?>
        <div class="tot"><span>ควรมีในลิ้นชัก</span><b id="expected" data-v="<?php echo e($num($pc['expected'])) ?>"><?php echo e(money2($pc['expected'])) ?></b></div>
      </div>

      <div class="field">
        <label for="cash_counted">นับเงินในลิ้นชักได้จริง</label>
        <input class="input cash" type="number" id="cash_counted" name="cash_counted"
               value="<?php echo e($num($pc['expected'])) ?>" min="0" step="0.01" inputmode="decimal">
      </div>
      <div class="diff" id="diff" hidden><span id="diff-lb">ขาด</span><span id="diff-v">0.00</span></div>

      <div class="kvl">
        <div class="tot"><span>แยกเงินทอนไว้ (ยกมาเป็นเงินทอนวันนี้)</span><b id="keep-view"><?php echo e(money2(branch_default_float($code))) ?></b></div>
      </div>
      <p class="hint-note">เท่าเงินทอนมาตรฐานของสาขา · <a href="#" id="keep-link">เปลี่ยนจำนวน</a></p>
      <div id="keep-box" hidden>
        <input class="input cash" type="number" id="keep" name="keep"
               value="<?php echo e($num(branch_default_float($code))) ?>" min="0" step="0.01" inputmode="decimal">
      </div>

      <?php if ($past['bank'] !== null) { /* บัญชีโอนของวันนั้น — ยอดในแอปไม่บังคับ (มักไม่รู้ยอดย้อนหลัง) */ ?>
      <div class="kvl">
        <div class="tot"><span>บัญชีโอน ควรมี</span><b><?php echo e(money2($past['bank']['expected'])) ?></b></div>
      </div>
      <div class="field">
        <label for="bank_counted">ยอดในแอปธนาคาร <small class="adm-none">(ไม่บังคับ)</small></label>
        <input class="input" type="number" id="bank_counted" name="bank_counted" step="0.01" inputmode="decimal" placeholder="<?php echo e($num($past['bank']['expected'])) ?>">
        <small class="adm-hint">เว้นว่าง = ยกยอดที่ควรมีไปเป็นยอดตั้งต้นของวันนี้</small>
      </div>
      <?php } ?>

      <div class="field">
        <label for="note">ทำไมวันนั้นไม่ได้ปิดร้าน <i class="req">จำเป็น</i></label>
        <input class="input" type="text" id="note" name="note" required maxlength="255" autocomplete="off"
               placeholder="เช่น ลืมกดปิดร้านก่อนกลับบ้าน">
      </div>

      <button class="btn btn-primary btn-block btn-xl" type="submit">
        <svg class="ico"><use href="#i-store-off"/></svg> ปิดร้านของวัน<?php echo e(thai_day_month(strtotime($past['ymd']))) ?>
      </button>
      <p class="store-note">ขั้นที่ 2 · เปิดร้านวันนี้ — ขึ้นให้หลังปิดเสร็จ เงินทอนที่แยกไว้จะยกมาอัตโนมัติ</p>
    </div>
  </form>
  <?php } else { ?>
  <form class="card" method="post" action="store.php" id="open-form">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
    <input type="hidden" name="act" value="open">

    <div class="card-body num">
      <div class="kvl">
        <div><span>เงินทอนยกมา</span><b id="carry" data-v="<?php echo e($num($carry['amount'])) ?>"><?php echo e(money2($carry['amount'])) ?></b></div>
        <div class="sub"><span>
          <?php if ($carry['by'] !== '') { ?>แยกไว้ตอนปิดร้านครั้งก่อนโดย <?php echo e($carry['by']) ?> · <?php echo e($carry['time']) ?> น.
          <?php } else { ?>เงินทอนมาตรฐานของสาขา<?php echo $carry['unclosed'] !== '' ? ' (วันก่อนยังไม่ได้ปิดร้าน)' : ' (ยังไม่มีการปิดร้านก่อนหน้า)' ?><?php } ?>
        </span><span></span></div>
      </div>

      <div class="field">
        <label for="topup">เติมเงินทอนวันนี้ (ถ้ามี)</label>
        <input class="input cash" type="number" id="topup" name="topup" value="0" min="0" step="0.01" inputmode="decimal">
      </div>

      <div class="amt-quick">
        <button type="button" data-amt="0">ไม่เติม</button>
        <button type="button" data-amt="500">+500</button>
        <button type="button" data-amt="1000">+1,000</button>
        <button type="button" data-amt="2000">+2,000</button>
      </div>

      <div class="kvl">
        <div class="tot"><span>เงินทอนเริ่มวันนี้</span><b id="float-today"><?php echo e(money2($carry['amount'])) ?></b></div>
      </div>

      <div class="hint">
        <svg class="ico"><use href="#i-info"/></svg>
        <span>ปกติกด “เปิดร้าน” ได้เลย เงินทอนยกมาให้อัตโนมัติ ·
              ถ้านับแล้วไม่ตรง <?php echo e(money2($carry['amount'])) ?>
              <a href="#" id="adj-link">แจ้งยอดไม่ตรง</a></span>
      </div>

      <div id="adj" hidden>
        <div class="field">
          <label for="counted">นับเงินทอนได้จริง</label>
          <input class="input" type="number" id="counted" name="counted" min="0" step="0.01"
                 inputmode="decimal" placeholder="เช่น <?php echo e($num(max(0, $carry['amount'] - 50))) ?>">
        </div>
        <div class="field">
          <label for="reason">เหตุผล</label>
          <input class="input" type="text" id="reason" name="reason" placeholder="เช่น เหรียญหายไป 50 บาท">
        </div>
        <p class="hint-note">บันทึกเป็นรายการปรับเงินทอน และแจ้งผู้ดูแลอัตโนมัติ</p>
      </div>

      <?php /* บัญชีโอนของสาขา — ช่องยอดในแอปขึ้นเลย ไม่บังคับ (ว่าง = ตรง) · ไม่ตรง → ผลต่าง + เหตุผล (จำเป็น) · วันแรกเติมเหตุผลให้ (24 พ.ย.) */
      $bankFirst = ($bankCarry['from'] === 'start'); ?>
      <div class="kvl store-bank-open">
        <div><span>ยอดบัญชีโอนยกมา</span><b id="bank-carry" data-v="<?php echo e($num($bankCarry['amount'])) ?>"><?php echo e(money2($bankCarry['amount'])) ?></b></div>
        <div class="sub"><span><?php echo $bankFirst ? 'ยอดตั้งต้นของสาขา (ยังไม่เคยปิดร้านพร้อมยอดบัญชี)'
                                         : 'ยอดในแอปตอนปิดร้านวัน' . e(thai_date_full(strtotime($bankCarry['ymd']))) ?></span><span></span></div>
      </div>
      <div class="field store-bank-in">
        <label for="bank_counted">ยอดในแอปธนาคารตอนนี้ <small class="adm-none">(ไม่บังคับ)</small></label>
        <input class="input" type="number" id="bank_counted" name="bank_counted" step="0.01" inputmode="decimal"
               placeholder="<?php echo e($num($bankCarry['amount'])) ?>">
        <small class="hint-note">เปิดแอปธนาคารของบัญชีสาขาดูยอดคงเหลือ · ว่างไว้ = ตรงกับยอดยกมา</small>
      </div>
      <p class="bank-open-diff" id="bank-open-diff" hidden>ต่างจากยอดยกมา <b id="bank-open-diff-v"></b></p>
      <div id="bank-adj" hidden>
        <div class="field">
          <label for="bank_reason">เหตุผลที่ไม่ตรง <i class="req">จำเป็น</i></label>
          <input class="input" type="text" id="bank_reason" name="bank_reason" maxlength="255" autocomplete="off"
                 value="<?php echo $bankFirst ? 'ยอดเริ่มต้นวันแรก' : '' ?>" placeholder="เช่น ฝากเงินนำส่งเมื่อวาน / ลูกค้าโอนมาหลังปิดร้าน">
        </div>
        <p class="hint-note">ยอดในแอปเป็นยอดตั้งต้นของวันนี้ · ผลต่างลงประวัติเป็น “เงินขยับระหว่างร้านปิด” ไม่นับเป็นขาด / เกินตอนปิดร้าน</p>
      </div>

      <button class="btn btn-primary btn-block btn-xl" type="submit">
        <svg class="ico"><use href="#i-store"/></svg> เปิดร้าน
      </button>
    </div>
  </form>
  <?php } ?>

  <p class="store-note">
    ร้านเปิดวันละครั้ง — พนักงานคนอื่นที่เข้าระบบทีหลังใช้งานได้เลย ไม่ต้องเปิดซ้ำ ·
    เงินทอนที่แยกไว้ตอนปิดร้านจะยกไปวันถัดไปอัตโนมัติ
  </p>

  <?php /* ยังไม่เปิดร้านก็ทำงานคลังและดูข้อมูลย้อนหลังได้ — ขายอย่างเดียวที่ต้องเปิดร้าน */ ?>
  <div class="store-peek">
    <span>ยังไม่เปิดร้านก็ใช้ได้</span>
    <?php foreach (array(
        array('products.php',     'i-boxes',   'สินค้าในสต๊อก'),
        array('receive.php',      'i-in',      'นำเข้าสินค้า'),
        array('issue.php',        'i-out',     'เบิก / ตัดออก'),
        array('stocktake.php',    'i-clipboard', 'ตรวจนับสต๊อก'),
        array('movements.php',    'i-activity', 'เคลื่อนไหวรายสินค้า'),
        array('history.php',      'i-history', 'ประวัติ'),                  // กลุ่มแรกที่ดูได้ (15 ต.ค.)
        array('report-sales.php', 'i-chart',   'รายงานยอดขาย'),
    ) as $lk) { ?>
      <?php if (page_ok($user, $lk[0])) { ?>
        <a class="btn btn-ghost btn-sm" href="<?php echo e($lk[0]) ?>"><svg class="ico"><use href="#<?php echo e($lk[1]) ?>"/></svg> <?php echo e($lk[2]) ?></a>
      <?php } ?>
    <?php } ?>
  </div>
</div>

<?php } elseif ($isOpen) { ?>
<!-- ==================== ร้านเปิดอยู่ · ปิดร้าน ==================== -->
<div class="store-wrap store-wrap--wide">

  <div class="store-hero">
    <span class="ic ic--ok"><svg class="ico"><use href="#i-store"/></svg></span>
    <h2>ร้านเปิดอยู่ <span class="bdg bdg-ok">เปิดแล้ว <?php echo e($state['opened_at']) ?> น.</span></h2>
    <p>
      <?php echo e(branch_name($code)) ?> · <?php echo e(thai_date_full(time())) ?> ·
      เปิดโดย <?php echo e($state['opened_by']) ?> · เงินทอนเริ่มวัน <?php echo e(money2($state['float'])) ?>
      <?php if ($state['counted'] !== null) { ?>
        <br><span class="warn-txt">แจ้งยอดไม่ตรง: นับได้ <?php echo e(money2($state['counted'])) ?> · <?php echo e($state['reason']) ?></span>
      <?php } ?>
      <?php if ($state['bank_open_counted'] !== null) { ?>
        <br><span class="warn-txt">ยอดบัญชีโอนไม่ตรง: ในแอป <?php echo e(money2($state['bank_open_counted'])) ?> · <?php echo e($state['bank_open_reason']) ?></span>
      <?php } ?>
    </p>
    <a class="btn btn-outline" href="dashboard.php">
      <svg class="ico"><use href="#i-home"/></svg> ไปหน้าภาพรวมของฉัน
    </a>
  </div>

  <!-- เงินเข้า / ออกลิ้นชักระหว่างวัน -->
  <section class="card store-cash" id="cash-card">
    <div class="card-head"><div><h3>เติมเงินทอน / หยิบเงินออก</h3><p>ระหว่างวัน · เงินสดในลิ้นชัก หรือบัญชีโอนของสาขา · ต้องระบุเหตุผลทุกครั้ง · ลงประวัติให้ผู้ดูแลเห็น</p></div></div>
    <div class="card-body num">
      <?php /* ผลของฟอร์มนี้แสดงในการ์ด (#cash-card) — เดิมขึ้นบนสุดของหน้า ต้องเลื่อนขึ้นไปดู และค่าที่กรอกหายหมด (21 ต.ค.) */ ?>
      <?php if ($cashErr !== '') { ?>
        <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($cashErr) ?></span></div>
      <?php } elseif ($cashNote !== '') { ?>
        <div class="alert alert-info" role="status"><svg class="ico"><use href="#i-check"/></svg><span><?php echo e($cashNote) ?></span></div>
      <?php } ?>
      <?php if ($cashList) { ?>
        <div class="kvl">
          <?php foreach ($cashList as $cm) { ?>
            <div><span><?php echo e($cm['time']) ?> น. · <?php echo e($cm['reason']) ?> <small>(<?php echo e($cm['by']) ?>)</small></span>
                 <b class="<?php echo $cm['dir'] === 'in' ? 'qty-in' : 'qty-out' ?>"><?php echo $cm['dir'] === 'in' ? '+' : '−' ?><?php echo e(money2($cm['amount'])) ?><?php echo $cm['channel'] === 'transfer' ? ' <small>โอน</small>' : '' ?></b></div>
          <?php } ?>
        </div>
      <?php } ?>
      <?php if (can($user, 'cash')) { ?>
      <form method="post" action="store.php#cash-card" class="store-cash-f">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="act" value="cash">
        <?php /* ช่องทาง / รายการเป็นปุ่มเลือก (24 พ.ย.) — คำของรายการ + ตัวอย่างเหตุผลเปลี่ยนตามช่องทาง (สคริปต์ท้ายหน้า) */
              $cvTr  = ($cv('channel') === 'transfer');
              $cvOut = ($cv('dir') === 'out');
              $dirs  = array('in'  => array('i-plus', 'เติมเงินทอนเข้าลิ้นชัก', 'เติมเงินเข้าบัญชีโอน'),
                             'out' => array('i-minus', 'หยิบเงินออกจากลิ้นชัก', 'โอนเงินออกจากบัญชีโอน'));
              $phs   = array('cash' => 'เช่น แลกเหรียญเพิ่ม / ซื้อถุงใส่ของ', 'transfer' => 'เช่น เจ้าของโอนเข้าไว้ทอน / ค่าธรรมเนียมธนาคาร'); ?>
        <div class="cash-picks">
        <div class="field cash-pick">
          <span class="lbl" id="cash-ch-lb">ช่องทาง</span>
          <div class="segs cash-segs" id="cash-ch" role="radiogroup" aria-labelledby="cash-ch-lb">
            <label class="seg<?php echo $cvTr ? '' : ' on' ?>"><input type="radio" name="channel" value="cash"<?php echo $cvTr ? '' : ' checked' ?>><svg class="ico"><use href="#i-coin"/></svg> เงินสดในลิ้นชัก</label>
            <label class="seg<?php echo $cvTr ? ' on' : '' ?>"><input type="radio" name="channel" value="transfer"<?php echo $cvTr ? ' checked' : '' ?>><svg class="ico"><use href="#i-transfer"/></svg> บัญชีโอนของสาขา</label>
          </div>
        </div>
        <div class="field cash-pick">
          <span class="lbl" id="cash-dir-lb">รายการ</span>
          <div class="segs cash-segs cash-segs-dir" id="cash-dir" role="radiogroup" aria-labelledby="cash-dir-lb">
            <?php foreach ($dirs as $dk => $dv) { $on = ($dk === 'out') === $cvOut; ?>
            <label class="seg<?php echo $on ? ' on' : '' ?>"><input type="radio" name="dir" value="<?php echo $dk ?>"<?php echo $on ? ' checked' : '' ?>><svg class="ico"><use href="#<?php echo $dv[0] ?>"/></svg>
              <span data-cash="<?php echo e($dv[1]) ?>" data-transfer="<?php echo e($dv[2]) ?>"><?php echo e($cvTr ? $dv[2] : $dv[1]) ?></span></label>
            <?php } ?>
          </div>
        </div>
        </div>
        <div class="field cash-amt-f">
          <label for="cash-amt">จำนวนเงิน</label>
          <div class="adm-unit">
            <input class="input" type="number" id="cash-amt" name="amount" min="0.01" max="1000000" step="0.01" inputmode="decimal" required
                   value="<?php echo e($cv('amount')) ?>" placeholder="0.00">
            <span>บาท</span>
          </div>
        </div>
        <div class="field field-why">
          <label for="cash-why">เหตุผล</label>
          <input class="input" type="text" id="cash-why" name="why" maxlength="200" required autocomplete="off"
                 value="<?php echo e($cv('why')) ?>" placeholder="<?php echo e($phs[$cvTr ? 'transfer' : 'cash']) ?>"
                 data-ph-cash="<?php echo e($phs['cash']) ?>" data-ph-transfer="<?php echo e($phs['transfer']) ?>">
        </div>
        <button class="btn btn-ghost" type="submit"><svg class="ico"><use href="#i-coin"/></svg> บันทึก</button>
      </form>
      <?php } else { ?>
        <p class="hint-note">เติมเงินทอน / หยิบเงินออกได้เฉพาะคนที่มีสิทธิ์ “เงินเข้า / ออกลิ้นชัก”</p>
      <?php } ?>
    </div>
  </section>

  <?php if (!can($user, 'store')) { ?>
  <p class="store-note">ปิดร้านได้เฉพาะคนที่มีสิทธิ์ “เปิด / ปิดร้าน”</p>
  <?php } else { ?>
  <h3 class="store-h3">ปิดร้าน</h3>
  <p class="store-sub">ทำตอนเลิกงาน — ปิดแล้วจะบันทึกรายการเพิ่มไม่ได้</p>

  <?php /* การ์ดสินค้าที่ขายวันนี้ — วันที่มีบัญชีโอน: ล่างสุดเต็มแถว (ซ้าย = บัญชีโอน · ขวา = เงินสด · 24 พ.ย.) · ไม่มีบัญชีโอน: คอลัมน์ซ้ายแบบเดิม */
  ob_start(); ?>
    <!-- สินค้าที่ขายวันนี้ + ยอดเงินสด / โอน -->
    <section class="card store-sold-card">
      <div class="card-head">
        <div><h3>สินค้าที่ขายวันนี้</h3><p><?php echo number_format(count($sold)) ?> รายการ · <?php echo number_format($soldQty) ?> ชิ้น · ไม่นับบิลที่ยกเลิก</p></div>
      </div>
      <div class="card-body num">
        <div class="kvl">
          <div><span>ยอดขาย (<?php echo number_format($daySum['bills'] - $daySum['void']) ?> บิล)</span><b><?php echo e(money2($daySum['total'])) ?></b></div>
          <div><span>เงินสด</span><b><?php echo e(money2($daySum['cash'])) ?></b></div>
          <div><span>โอน / พร้อมเพย์</span><b><?php echo e(money2($daySum['transfer'])) ?></b></div>
          <?php if ($daySum['void'] > 0) { ?>
          <div class="sub"><span>ยกเลิก <?php echo number_format($daySum['void']) ?> บิล (ไม่นับในยอด)</span></div>
          <?php } ?>
        </div>
      </div>
      <?php if (!$sold) { ?>
        <p class="empty">ยังไม่มีการขายวันนี้</p>
      <?php } else { ?>
        <div class="store-sold">
          <table class="ds-lines">
            <thead><tr><th>สินค้า</th><th class="r">จำนวน</th><th class="r">ยอดขาย</th></tr></thead>
            <tbody>
              <?php $i = 0; foreach ($sold as $sku => $sp) { $i++; ?>
                <tr><td><span class="rep-rank<?php echo $i <= 3 ? ' r' . $i : '' ?>"><?php echo $i ?></span><?php echo e($sp['name']) ?> <small><?php echo e($sku) ?></small></td>
                    <td class="r num nowrap"><?php echo number_format($sp['qty']) ?> <?php echo e($sp['unit']) ?></td>
                    <td class="r num nowrap"><?php echo e(money2($sp['total'])) ?></td></tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      <?php } ?>
    </section>
  <?php $soldCard = ob_get_clean(); ?>

  <form method="post" action="store.php" class="store-two">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
    <input type="hidden" name="act" value="close">

    <div class="store-col">
    <?php if ($bank === null) { echo $soldCard; } ?>

    <?php if ($bank !== null) { /* บัญชีโอนของสาขา — ลิ้นชักที่สอง (23 พ.ย.) · กรอกยอดในแอปธนาคารก่อนปิด */
    $bkKind = array('sale' => 'ลูกค้าโอนจ่าย', 'change' => 'ทอนเป็นโอน', 'void' => 'ยกเลิกบิล คืนแบบโอน', 'refund' => 'คืนเงินลูกค้า (รับคืน)',
                    'topup' => 'เติมเข้าบัญชี', 'withdraw' => 'โอนออก'); ?>
    <section class="card store-bank-card">
      <div class="card-head"><div><h3>บัญชีโอนของสาขา</h3><p>เปิดแอปธนาคารดูยอดคงเหลือ · เทียบรายการด้านล่างก่อนกรอก</p></div></div>
      <div class="card-body num">
        <div class="kvl">
          <?php if ($bank['open_counted'] !== null) { ?>
          <div><span>ยอดในแอปตอนเปิด</span><b><?php echo e(money2($bank['open_counted'])) ?></b></div>
          <div class="sub"><span>ยกมา <?php echo e(money2($bank['carried'])) ?> · <?php echo e($state['bank_open_reason']) ?></span><span></span></div>
          <?php } else { ?>
          <div><span>ยอดยกมา</span><b><?php echo e(money2($bank['carried'])) ?></b></div>
          <?php } ?>
          <div><span>+ เติมเข้าบัญชี</span><b><?php echo e(money2($bank['topup'])) ?></b></div>
          <div><span>+ ลูกค้าโอนจ่าย</span><b><?php echo e(money2($bank['tr_in'])) ?></b></div>
          <div><span>− ทอนเป็นโอน</span><b><?php echo e(money2($bank['tr_out'])) ?></b></div>
          <?php if ($bank['refunds'] + $bank['void_refunds'] > 0) { ?>
          <div><span>− คืนเงินลูกค้าแบบโอน (รับคืน · ยกเลิกบิล)</span><b><?php echo e(money2($bank['refunds'] + $bank['void_refunds'])) ?></b></div>
          <?php } ?>
          <div><span>− โอนออก</span><b><?php echo e(money2($bank['withdraw'])) ?></b></div>
          <div class="tot"><span>ควรมีในบัญชี</span><b id="bank-expected" data-v="<?php echo e($num($bank['expected'])) ?>"><?php echo e(money2($bank['expected'])) ?></b></div>
        </div>

        <div class="field">
          <label for="bank_counted">ยอดคงเหลือในแอปธนาคาร <i class="req">จำเป็น</i></label>
          <input class="input cash" type="number" id="bank_counted" name="bank_counted" step="0.01" inputmode="decimal" required
                 placeholder="<?php echo e($num($bank['expected'])) ?>">
        </div>
        <div class="diff" id="bank-diff" hidden><span id="bank-diff-lb">ขาด</span><span id="bank-diff-v">0.00</span></div>
        <p class="hint-note">ยอดนี้ยกไปเป็นยอดตั้งต้นของพรุ่งนี้ · ไม่ตรงต้องกรอกหมายเหตุ (ช่องเดียวกับเงินสด)</p>
      </div>
      <?php if (!$bankList) { ?>
        <p class="empty">ยังไม่มีเงินเข้า-ออกบัญชีวันนี้</p>
      <?php } else { ?>
        <div class="store-sold">
          <table class="ds-lines">
            <thead><tr><th>เวลา</th><th>รายการ</th><th class="r">เข้า / ออก</th></tr></thead>
            <tbody>
              <?php foreach ($bankList as $tr) { ?>
                <tr class="tl-row"><td class="nowrap"><?php echo e($tr['time']) ?></td>
                    <td><?php echo e($bkKind[$tr['kind']]) ?> <small><?php echo e($tr['ref']) ?> · <?php echo e($tr['by']) ?></small></td>
                    <td class="r num nowrap <?php echo $tr['dir'] === 'in' ? 'qty-in' : 'qty-out' ?>"><?php echo $tr['dir'] === 'in' ? '+' : '−' ?><?php echo e(money2($tr['amount'])) ?></td></tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      <?php } ?>
    </section>
    <?php } ?>
    </div>

    <!-- เงินสดในลิ้นชัก -->
    <section class="card">
      <div class="card-head"><div><h3>เงินสดในลิ้นชัก</h3><p>เงินทอน + เงินขาย · ตรวจนับก่อนปิดร้าน</p></div></div>
      <div class="card-body num">
        <div class="kvl">
          <div><span>เงินทอนเริ่มวัน</span><b><?php echo e(money2($state['float'])) ?></b></div>
          <div><span>+ เติมระหว่างวัน</span><b><?php echo e(money2($dayCash['topup'])) ?></b></div>
          <div><span>+ รับเงินสดจากขาย</span><b><?php echo e(money2($dayCash['cash_in'])) ?></b></div>
          <div><span>− ทอนเงินสด</span><b><?php echo e(money2($dayCash['cash_out'])) ?></b></div>
          <div><span>− หยิบออกใช้จ่าย</span><b><?php echo e(money2($dayCash['withdraw'])) ?></b></div>
          <?php if ($dayCash['refunds'] + $dayCash['void_refunds'] > 0) { ?>
          <div><span>− คืนเงินลูกค้า (รับคืน · ยกเลิกบิล)</span><b><?php echo e(money2($dayCash['refunds'] + $dayCash['void_refunds'])) ?></b></div>
          <?php } ?>
          <div class="tot"><span>ควรมีในลิ้นชัก</span><b id="expected" data-v="<?php echo e($num($expected)) ?>"><?php echo e(money2($expected)) ?></b></div>
        </div>

        <div class="field">
          <label for="cash_counted">นับเงินได้จริง</label>
          <input class="input cash" type="number" id="cash_counted" name="cash_counted"
                 value="<?php echo e($num($expected)) ?>" min="0" step="0.01" inputmode="decimal">
        </div>

        <div class="diff" id="diff" hidden><span id="diff-lb">ขาด</span><span id="diff-v">0.00</span></div>

        <div class="kvl">
          <div class="tot"><span>แยกเงินทอนไว้สำหรับพรุ่งนี้</span><b id="keep-view"><?php echo e(money2(branch_default_float($code))) ?></b></div>
        </div>
        <p class="hint-note">
          เท่าเดิมโดยอัตโนมัติ · <a href="#" id="keep-link">เปลี่ยนจำนวน</a>
        </p>
        <div id="keep-box" hidden>
          <input class="input cash" type="number" id="keep" name="keep"
                 value="<?php echo e($num(branch_default_float($code))) ?>" min="0" step="0.01" inputmode="decimal">
          <p class="hint-note">เช่น ลดเหลือ 1,500 เพราะแบงก์ย่อยหมด หรือเพิ่มก่อนวันหยุดยาว</p>
        </div>

        <div class="field">
          <label for="note">หมายเหตุ</label>
          <input class="input" type="text" id="note" name="note" placeholder="เช่น ทอนผิดช่วงบ่าย">
        </div>

        <button class="btn btn-primary btn-block btn-xl" type="submit">
          <svg class="ico"><use href="#i-store-off"/></svg> ปิดร้านและส่งสรุปให้ผู้ดูแล
        </button>
        <p class="store-note">ปิดแล้วจะบันทึกรายการเพิ่มไม่ได้ ถ้าจำเป็นต้องขายต่อ ให้ผู้ดูแลหรือคนที่มีสิทธิ์ “เปิดร้านอีกครั้ง” เปิดร้านใหม่</p>
      </div>
    </section>
  </form>
  <?php if ($bank !== null) { ?>
  <div class="store-sold-wide">
  <?php echo $soldCard; ?>
  </div>
  <?php } ?>
  <?php } ?>
</div>

<?php } else { ?>
<!-- ==================== ปิดร้านแล้ว ==================== -->
<div class="store-wrap">
  <div class="store-hero">
    <span class="ic ic--off"><svg class="ico"><use href="#i-store-off"/></svg></span>
    <h2>ปิดร้านแล้ว <?php echo e($state['closed_at']) ?> น.</h2>
    <p><?php echo e(branch_name($code)) ?> · <?php echo e(thai_date_full(time())) ?> · ปิดโดย <?php echo e($state['closed_by']) ?></p>
  </div>

  <section class="card">
    <div class="card-head"><div><h3>สรุปการปิดร้าน</h3></div></div>
    <div class="card-body num">
      <div class="kvl">
        <div><span>เปิดร้าน</span><b><?php echo e($state['opened_at']) ?> น. โดย <?php echo e($state['opened_by']) ?></b></div>
        <div><span>ยอดขาย (<?php echo number_format($daySum['bills'] - $daySum['void']) ?> บิล)</span><b><?php echo e(money2($daySum['total'])) ?></b></div>
        <div><span>เงินสด · โอน / พร้อมเพย์</span><b><?php echo e(money2($daySum['cash'])) ?> · <?php echo e(money2($daySum['transfer'])) ?></b></div>
        <div><span>เงินทอนเริ่มวัน</span><b><?php echo e(money2($state['float'])) ?></b></div>
        <div><span>ควรมีในลิ้นชัก</span><b><?php echo e(money2($expected)) ?></b></div>
        <div><span>นับได้จริง</span><b><?php echo e(money2($state['cash_counted'])) ?></b></div>
        <?php $d = $state['cash_counted'] - $expected; ?>
        <div>
          <span>ส่วนต่าง</span>
          <b class="<?php echo $d < 0 ? 'qty-out' : ($d > 0 ? 'qty-in' : '') ?>">
            <?php echo $d == 0 ? 'ตรงพอดี' : ($d > 0 ? 'เกิน ' : 'ขาด ') . money2(abs($d)) ?>
          </b>
        </div>
        <div class="tot"><span>แยกเงินทอนไว้พรุ่งนี้</span><b><?php echo e(money2($state['keep'])) ?></b></div>
        <?php if ($state['bank_expected'] !== null) { $bd = ($state['bank_counted'] !== null) ? round($state['bank_counted'] - $state['bank_expected'], 2) : null; ?>
        <div><span>บัญชีโอน ควรมี</span><b><?php echo e(money2($state['bank_expected'])) ?></b></div>
        <div><span>ยอดในแอป</span><b><?php echo $state['bank_counted'] !== null ? e(money2($state['bank_counted'])) : 'ไม่ได้กรอก' ?></b></div>
        <?php if ($bd !== null) { ?>
        <div><span>ส่วนต่างบัญชีโอน</span>
          <b class="<?php echo $bd < 0 ? 'qty-out' : ($bd > 0 ? 'qty-in' : '') ?>"><?php echo $bd == 0 ? 'ตรงพอดี' : ($bd > 0 ? 'เกิน ' : 'ขาด ') . e(money2(abs($bd))) ?></b></div>
        <?php } ?>
        <?php } ?>
        <?php if ($state['note'] !== '') { ?>
          <div><span>หมายเหตุ</span><b><?php echo e($state['note']) ?></b></div>
        <?php } ?>
      </div>
    </div>
  </section>

  <p class="store-note">ร้านปิดแล้วสำหรับวันนี้ · พรุ่งนี้เปิดใหม่ เงินทอนจะยกมา <?php echo e(money2($state['keep'])) ?> บาทอัตโนมัติ</p>

  <?php if (can($user, 'store_reopen')) { ?>
  <section class="card">
    <div class="card-head"><div><h3>เปิดร้านใหม่</h3><p>ใช้เมื่อปิดร้านไปแล้วแต่ยังต้องขายหรือแก้รายการต่อ · ต้องระบุเหตุผล</p></div></div>
    <form class="card-body store-reopen-f" method="post" action="store.php">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
      <input type="hidden" name="act" value="reopen">
      <div class="field">
        <label for="reopen-reason">เหตุผล</label>
        <input class="input" type="text" id="reopen-reason" name="reason" required minlength="5" maxlength="200" autocomplete="off"
               placeholder="เช่น ลูกค้ากลับมาซื้อหลังปิดร้าน / ปิดร้านผิดสาขา">
      </div>
      <button class="btn btn-ghost" type="submit"><svg class="ico"><use href="#i-store"/></svg> เปิดร้านใหม่</button>
    </form>
  </section>
  <?php } else { ?>
  <p class="store-note">ถ้าจำเป็นต้องขายต่อหลังปิดร้าน ให้ผู้ดูแลเปิดร้านใหม่ หรือให้คนที่มีสิทธิ์ “เปิดร้านอีกครั้ง” เป็นคนเปิด</p>
  <?php } ?>
</div>
<?php } ?>

<script>
(function () {
  var fmt = function (n) { return Number(n).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

  /* ---- หน้าเปิดร้าน ---- */
  var topup = document.getElementById('topup');
  var carry = document.getElementById('carry');
  var out   = document.getElementById('float-today');
  var cnt   = document.getElementById('counted');

  function calcOpen() {
    if (!topup || !carry || !out) { return; }
    var base = cnt && cnt.value !== '' ? Number(cnt.value) : Number(carry.dataset.v);
    out.textContent = fmt((base || 0) + (Number(topup.value) || 0));
  }
  if (topup) { topup.addEventListener('input', calcOpen); }
  if (cnt)   { cnt.addEventListener('input', calcOpen); }

  var qs = document.querySelectorAll('.amt-quick button');
  for (var i = 0; i < qs.length; i++) {
    qs[i].addEventListener('click', function () {
      topup.value = this.getAttribute('data-amt');
      for (var j = 0; j < qs.length; j++) { qs[j].className = ''; }
      this.className = 'on';
      calcOpen();
    });
  }

  /* บัญชีโอนยกมา (หน้าเปิดร้าน): กรอกยอดในแอปไม่ตรง → บอกผลต่าง + ช่องเหตุผล (จำเป็น) · ว่าง / ตรง = ซ่อน */
  var bCar = document.getElementById('bank-carry');
  if (bCar) {
    var bForm = bCar.closest('form'), bIn = bForm.querySelector('input[name=bank_counted]'), bWhy = bForm.querySelector('input[name=bank_reason]');
    var bAdj = document.getElementById('bank-adj'), bDiff = document.getElementById('bank-open-diff'), bDv = document.getElementById('bank-open-diff-v');
    var bRun = function () {
      var d   = (bIn.value === '') ? 0 : Math.round(((Number(bIn.value) || 0) - Number(bCar.getAttribute('data-v'))) * 100) / 100;
      var off = (d !== 0);
      bAdj.hidden = !off;
      bDiff.hidden = !off;
      bWhy.required = off;
      if (off) { bDv.textContent = (d > 0 ? '+' : '−') + Math.abs(d).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    };
    bIn.addEventListener('input', bRun);
    bRun();
  }

  /* แจ้งยอดไม่ตรง — เงินทอนยกมา (#adj-link → #adj) */
  var adjLinks = [document.getElementById('adj-link')];
  for (var a = 0; a < adjLinks.length; a++) {
    if (!adjLinks[a]) { continue; }
    adjLinks[a].addEventListener('click', function (e) {
      e.preventDefault();
      document.getElementById(this.getAttribute('data-adj') || 'adj').hidden = false;
      document.getElementById(this.getAttribute('data-focus') || 'counted').focus();
      /* ซ่อนทั้งกล่องคำแนะนำ — เดิมซ่อนแค่ข้อความ เหลือกล่องเปล่ากับไอคอน (12 ต.ค.) */
      var hint = this.closest('.hint');
      (hint || this.parentNode).style.display = 'none';
    });
  }

  /* ---- หน้าปิดร้าน: ขาด / เกิน ของเงินสด (#diff) และบัญชีโอน (#bank-diff · ช่องว่าง = ยังไม่กรอก ไม่ขึ้น) ---- */
  function diffOf(expId, inId, boxId, lbId, vId, emptyHides) {
    var ex = document.getElementById(expId), inp = document.getElementById(inId), box = document.getElementById(boxId);
    if (!ex || !inp || !box) { return; }
    var run = function () {
      var d = Math.round(((Number(inp.value) || 0) - Number(ex.dataset.v)) * 100) / 100;
      box.hidden = (d === 0) || (emptyHides && inp.value === '');
      box.className = 'diff' + (d > 0 ? ' over' : '');
      document.getElementById(lbId).textContent = d > 0 ? 'เกิน' : 'ขาด';
      document.getElementById(vId).textContent  = (d > 0 ? '+' : '−') + fmt(Math.abs(d));
    };
    inp.addEventListener('input', run);
    run();
  }
  diffOf('expected', 'cash_counted', 'diff', 'diff-lb', 'diff-v', false);
  diffOf('bank-expected', 'bank_counted', 'bank-diff', 'bank-diff-lb', 'bank-diff-v', true);

  /* ---- เติม / หยิบเงิน: ปุ่มเลือก (ไฮไลต์ตัวที่เลือก) · คำของรายการ + ตัวอย่างเหตุผลตามช่องทาง (ลิ้นชัก / บัญชีโอน) ---- */
  var cch = document.getElementById('cash-ch'), cdir = document.getElementById('cash-dir'), cwhy = document.getElementById('cash-why');
  if (cch && cdir) {
    var segOn = function (box) {
      var ls = box.querySelectorAll('label.seg');
      for (var i = 0; i < ls.length; i++) { ls[i].classList.toggle('on', ls[i].querySelector('input').checked); }
    };
    cdir.addEventListener('change', function () { segOn(cdir); });
    cch.addEventListener('change', function () {
      segOn(cch);
      var ch = cch.querySelector('input:checked').value;
      var ts = cdir.querySelectorAll('[data-' + ch + ']');
      for (var i = 0; i < ts.length; i++) { ts[i].textContent = ts[i].getAttribute('data-' + ch); }
      if (cwhy) { cwhy.placeholder = cwhy.getAttribute('data-ph-' + ch); }
    });
  }

  var keepLink = document.getElementById('keep-link');
  if (keepLink) {
    keepLink.addEventListener('click', function (e) {
      e.preventDefault();
      document.getElementById('keep-box').hidden = false;
      this.parentNode.style.display = 'none';
      document.getElementById('keep').focus();
    });
  }
  var keep = document.getElementById('keep');
  if (keep) {
    keep.addEventListener('input', function () {
      document.getElementById('keep-view').textContent = fmt(Number(this.value) || 0);
    });
  }
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
