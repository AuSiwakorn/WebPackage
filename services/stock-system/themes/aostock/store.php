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
$num    = function ($n) { return rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.'); };   // ค่าในช่อง input
$cntVal  = '';                           // ยอดนับ / แยกเงินทอน / หมายเหตุที่กรอกไว้ — ส่งปิดไม่ผ่านแล้วไม่หาย
$keepVal = '';                           // '' = เงินทอนมาตรฐาน
$noteVal = '';

/* ---------- รับค่าจากฟอร์ม ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $error = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } else {
        $act  = isset($_POST['act']) ? $_POST['act'] : '';
        $need = array('open' => 'store', 'close' => 'store', 'close_past' => 'store', 'reopen' => 'store_reopen');   // ช่วงที่ 11 · 17 · เติม / หยิบเงินอยู่ cash.php

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
                                        isset($_POST['note']) ? $_POST['note'] : '');
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

            if ($counted !== null && abs($counted - $carry['amount']) >= 0.01 && trim($reason) === '') {
                $error = 'กรุณาระบุเหตุผลเมื่อยอดเงินทอนไม่ตรงกับที่ยกมา';
            } else {
                store_open($code, $user, $topup, $counted, $reason);
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
            /* นับตรง = ปิดได้ · ไม่ตรง = ต้องกรอกเหตุผล (ไม่มีเหตุผล → เซิร์ฟเวอร์ตรวจซ้ำหลังล็อก ไม่ตรงไม่ปิด) */
            $cntIn   = isset($_POST['cash_counted']) ? trim((string) $_POST['cash_counted']) : '';
            $counted = round((float) $cntIn, 2);
            $keep    = isset($_POST['keep']) ? round((float) preg_replace('/[^0-9.]/', '', $_POST['keep']), 2) : branch_default_float($code);
            $note    = isset($_POST['note']) ? trim((string) $_POST['note']) : '';
            $r       = (is_numeric($cntIn) && $counted >= 0) ? store_close($code, $user, $counted, $keep, $note, $note === '') : 'blank';
            $keepVal = $num($keep);
            $noteVal = $note;
            if ($r === 'blank') {
                $error = 'กรุณากรอกยอดเงินที่นับได้';
            } elseif ($r === false) {
                $exNow  = store_expected_cash($code);
                $diff   = round($counted - $exNow, 2);
                $cntVal = $num($counted);
                $error  = 'นับเงินได้ไม่ตรงกับยอดควรมีในลิ้นชัก — ควรมี ' . money2($exNow) . ' · นับได้ ' . money2($counted)
                        . ' (' . ($diff < 0 ? 'ขาด ' : 'เกิน ') . money2(abs($diff)) . ') · กรอกเหตุผลที่นับไม่ตรงแล้วกดปิดร้านอีกครั้ง';
            } elseif ($r === null) {
                $error = 'ร้านปิดไปแล้ว (อาจมีเครื่องอื่นปิดก่อน)';
            } else {
                header('Location: ' . url('store.php?closed=1'));
                exit;
            }
        }
    }
}

if (isset($_GET['opened'])) { $notice = 'เปิดร้านเรียบร้อย เริ่มทำงานได้เลย'; }
if (isset($_GET['closed'])) { $notice = 'ปิดร้านเรียบร้อย สรุปยอดถูกส่งให้ผู้ดูแลแล้ว'; }
if (isset($_GET['reopened'])) { $notice = 'เปิดร้านใหม่แล้ว ขายต่อได้ — ปิดร้านอีกครั้งเมื่อเสร็จ'; }
if (isset($_GET['pastclosed']) && ($pcTs = strtotime(preg_replace('/\D/', '', (string) $_GET['pastclosed']))) !== false) {
    $notice = 'ปิดร้านของวัน' . thai_date_full($pcTs) . 'แล้ว — เปิดร้านวันนี้ต่อได้เลย เงินทอนที่แยกไว้ยกมาให้แล้ว';
}

/* ช่วงที่ 17: วันล่าสุดก่อนวันนี้ยังไม่ได้ปิด → ขั้นที่ 1 ปิดร้านของวันนั้นก่อน (นับเงินในลิ้นชักก่อนขายบิลแรก) */
$carry = store_carry($code);                         // อ่านใหม่หลังบันทึก
$past  = null;
if (!store_is_open($code) && !store_is_closed($code) && $carry['unclosed'] !== '') {
    $pr = store_day_row($code, date('Y-m-d', strtotime($carry['unclosed'])));
    if ($pr !== null && $pr['status'] === 'open') {
        $past = array('ymd' => $carry['unclosed'], 'cash' => Stock_getDayCash($pr['day_id']),
                      'opened' => date('H:i', strtotime($pr['opened_at'])), 'by' => ($pr['opened_name'] !== null) ? $pr['opened_name'] : '-');
    }
}

$state    = store_state($code);
$isOpen   = store_is_open($code);
$isClosed = store_is_closed($code);
$cash     = store_daily_cash($code);                 // อ่านใหม่หลังบันทึก
$expected = store_expected_cash($code);
$dayCash  = ($state !== null) ? Stock_getDayCash($state['id']) : null;

/* หน้าต่างบิลของวันนี้ (?bills=cash|transfer · กดจำนวนบิลในการ์ด) → แค่รายการ */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['bills'])) {
    $BILLS_PART = 'list';
    require dirname(__FILE__) . '/inc/store-bills.php';
    exit;
}

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
    </p>
    <a class="btn btn-outline" href="dashboard.php">
      <svg class="ico"><use href="#i-home"/></svg> ไปหน้าภาพรวมของฉัน
    </a>
  </div>

  <?php if (!can($user, 'store')) { ?>
  <p class="store-note">ปิดร้านได้เฉพาะคนที่มีสิทธิ์ “เปิด / ปิดร้าน”</p>
  <?php } else { ?>
  <h3 class="store-h3" id="close">สรุปยอดประจำวัน</h3>
  <p class="store-sub">นับเงินในลิ้นชักแล้วส่งสรุปให้ผู้ดูแล · ส่งแล้วทุกคนในสาขาขาย / บันทึกเพิ่มไม่ได้ จนกว่าจะเปิดร้านใหม่</p>

  <?php /* การ์ดสินค้าที่ขายวันนี้ — ล่างสุดเต็มแถว (นอกฟอร์ม) */
  ob_start(); ?>
    <!-- สินค้าที่ขายวันนี้ -->
    <section class="card store-sold-card">
      <div class="card-head">
        <div><h3>สินค้าที่ขายวันนี้</h3><p><?php echo number_format(count($sold)) ?> รายการ · <?php echo number_format($soldQty) ?> ชิ้น · ไม่นับบิลที่ยกเลิก</p></div>
      </div>
      <div class="card-body num">
        <div class="kvl">
          <div><span>ยอดขาย (<?php echo number_format($daySum['bills'] - $daySum['void']) ?> บิล)</span><b><?php echo e(money2($daySum['total'])) ?></b></div>
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
  <?php $soldCard = ob_get_clean();

  /* ตัวเลข 3 คอลัมน์ (23 พ.ย.) — บิลสด + โอนนับทั้งสองฝั่ง */
  $trDay  = Stock_getDayChannelSums($state['id'], 'transfer');
  $cashRf = $dayCash['refunds'] + $dayCash['void_refunds'];
  $trRf   = $trDay['refunds'] + $trDay['void_refunds'];
  $trNet  = round($trDay['in'] - $trDay['out'] - $trRf, 2);
  $billsN = function ($n, $ch) {                     // จำนวนบิล — มีบิลแล้วกดดูรายชื่อได้ (inc/store-bills.php)
      return ($n > 0) ? '<button type="button" class="store-bills-link" data-sb-open="' . $ch . '">' . number_format($n) . ' บิล</button>' : number_format($n) . ' บิล';
  }; ?>

  <form method="post" action="store.php" class="store-close">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
    <input type="hidden" name="act" value="close">

    <div class="store-3col">
      <section class="card store-c3">
        <div class="card-head"><div><h3>เงินทอน</h3><p>เงินทอนในลิ้นชักตั้งแต่เปิดร้าน</p></div></div>
        <div class="card-body num">
          <div class="kvl">
            <div><span>เงินทอนเริ่มวัน</span><b><?php echo e(money2($state['float'])) ?></b></div>
            <div><span>+ เติมระหว่างวัน</span><b><?php echo e(money2($dayCash['topup'])) ?></b></div>
            <?php if ($dayCash['withdraw'] > 0) { ?>
            <div><span>− หยิบออก</span><b><?php echo e(money2($dayCash['withdraw'])) ?></b></div>
            <?php } ?>
          </div>
        </div>
      </section>

      <section class="card store-c3">
        <div class="card-head"><div><h3>ขายเป็นเงินสด</h3><p>เงินสดรวม = เงินทอนเริ่มวัน + เติม − หยิบออก + เงินสดจากขาย − คืนเงินสด</p></div></div>
        <div class="card-body num">
          <div class="kvl">
            <div><span>ขายเงินสด</span><b><?php echo $billsN($dayCash['bills'], 'cash') ?></b></div>
            <div><span>+ เงินสดจากขาย</span><b><?php echo e(money2($dayCash['sales'])) ?></b></div>
            <?php if ($cashRf > 0) { ?>
            <div><span>− คืนเงินสด</span><b><?php echo e(money2($cashRf)) ?></b></div>
            <?php } ?>
            <div class="tot"><span>เงินสดรวม</span><b><?php echo e(money2($expected)) ?></b></div>
          </div>
        </div>
      </section>

      <section class="card store-c3">
        <div class="card-head"><div><h3>ขายเป็นเงินโอน</h3><p>โอน / พร้อมเพย์ · ไม่อยู่ในลิ้นชัก</p></div></div>
        <div class="card-body num">
          <div class="kvl">
            <div><span>ลูกค้าโอนจ่าย</span><b><?php echo $billsN($trDay['bills'], 'transfer') ?></b></div>
            <div><span>+ ยอดโอน</span><b><?php echo e(money2($trDay['in'])) ?></b></div>
            <?php if ($trDay['out'] > 0) { ?>
            <div><span>− ทอนเป็นโอน</span><b><?php echo e(money2($trDay['out'])) ?></b></div>
            <?php } ?>
            <?php if ($trRf > 0) { ?>
            <div><span>− โอนคืนลูกค้า</span><b><?php echo e(money2($trRf)) ?></b></div>
            <?php } ?>
            <div class="tot"><span>ยอดโอนทั้งหมด</span><b><?php echo e(money2($trNet)) ?></b></div>
          </div>
        </div>
      </section>
    </div><!-- /store-3col -->

    <!-- นับเงิน — กลางใต้ 3 คอลัมน์ -->
    <section class="card store-count">
      <div class="card-body num">
        <div class="store-count-exp"><span>ควรมีในลิ้นชัก</span><b id="expected" data-v="<?php echo e($num($expected)) ?>"><?php echo e(money2($expected)) ?></b></div>

        <div class="field">
          <label for="cash_counted">นับเงินได้จริง</label>
          <input class="input cash" type="number" id="cash_counted" name="cash_counted" value="<?php echo e($cntVal) ?>"
                 min="0" step="0.01" inputmode="decimal" required>
        </div>

        <div class="diff" id="diff" hidden><span id="diff-lb">ขาด</span><span id="diff-v">0.00</span></div>

        <?php $keepDef = $num(branch_default_float($code)); $keepNow = ($keepVal !== '') ? $keepVal : $keepDef; $keepChg = ($keepNow !== $keepDef); ?>
        <div class="kvl">
          <div class="tot"><span>แยกเงินทอนไว้สำหรับพรุ่งนี้</span><b id="keep-view"><?php echo e(money2((float) $keepNow)) ?></b></div>
        </div>
        <p class="hint-note"<?php echo $keepChg ? ' hidden' : '' ?>>
          เท่าเดิมโดยอัตโนมัติ · <a href="#" id="keep-link">เปลี่ยนจำนวน</a>
        </p>
        <div id="keep-box"<?php echo $keepChg ? '' : ' hidden' ?>>
          <input class="input cash" type="number" id="keep" name="keep" data-def="<?php echo e($keepDef) ?>"
                 value="<?php echo e($keepNow) ?>" min="0" step="0.01" inputmode="decimal">
          <p class="hint-note">เช่น ลดเหลือ 1,500 เพราะแบงก์ย่อยหมด หรือเพิ่มก่อนวันหยุดยาว</p>
        </div>

        <?php /* นับไม่ตรง → สคริปต์เปลี่ยนเป็น “เหตุผลที่นับไม่ตรง (ต้องกรอก)” */ ?>
        <div class="field">
          <label for="note" id="note-lb">หมายเหตุ</label>
          <input class="input" type="text" id="note" name="note" value="<?php echo e($noteVal) ?>" placeholder="ถ้ามี" maxlength="255">
        </div>

        <button class="btn btn-primary btn-block btn-xl" type="submit" id="close-btn" disabled>
          <svg class="ico"><use href="#i-store-off"/></svg> ปิดร้านและส่งสรุปให้ผู้ดูแล
        </button>
        <p class="hint-note store-close-hint">กดได้เมื่อนับเงินตรง หรือกรอกเหตุผลที่นับไม่ตรง</p>
        <p class="store-note">ปิดแล้วจะบันทึกรายการเพิ่มไม่ได้ ถ้าจำเป็นต้องขายต่อ ให้ผู้ดูแลหรือคนที่มีสิทธิ์ “เปิดร้านอีกครั้ง” เปิดร้านใหม่</p>
      </div>
    </section>
  </form>
  <div class="store-sold-wide">
  <?php echo $soldCard; ?>
  </div>
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

  /* ---- ปิดร้านวันก่อน: ขาด / เกิน ของเงินสด (#diff) ---- */
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
  /* ---- ปิดร้าน: นับตรง = กดปิดได้ · ไม่ตรง = ช่องหมายเหตุเป็น “เหตุผลที่นับไม่ตรง (ต้องกรอก)” กรอกแล้วกดได้ ---- */
  var closeBtn = document.getElementById('close-btn');
  if (closeBtn) {
    var cEx = document.getElementById('expected'), cIn = document.getElementById('cash_counted'), cBox = document.getElementById('diff');
    var cWhy = document.getElementById('note'), cWhyLb = document.getElementById('note-lb');
    var cRun = function () {
      var has = cIn.value !== '';
      var d   = has ? Math.round(((Number(cIn.value) || 0) - Number(cEx.dataset.v)) * 100) / 100 : 0;
      var off = has && d !== 0;
      cBox.hidden = !has;
      cBox.className = 'diff' + (d === 0 ? ' ok' : (d > 0 ? ' over' : ''));
      document.getElementById('diff-lb').textContent = (d === 0) ? 'ตรงกับยอดควรมี' : (d > 0 ? 'เกิน' : 'ขาด');
      document.getElementById('diff-v').textContent  = (d === 0) ? '✓' : (d > 0 ? '+' : '−') + fmt(Math.abs(d));
      cWhyLb.textContent = off ? 'เหตุผลที่นับไม่ตรง (ต้องกรอก)' : 'หมายเหตุ';
      cWhy.required    = off;
      cWhy.placeholder = off ? 'เช่น ทอนเงินผิด' : 'ถ้ามี';
      closeBtn.disabled = !has || (off && cWhy.value.trim() === '');
    };
    cIn.addEventListener('input', cRun);
    cWhy.addEventListener('input', cRun);
    cRun();
  } else {
    diffOf('expected', 'cash_counted', 'diff', 'diff-lb', 'diff-v', false);
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

<?php if ($isOpen) { $BILLS_PART = 'dialog'; require dirname(__FILE__) . '/inc/store-bills.php'; } ?>
<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
