<?php
/**
 * FILE: themes/aostock/store.php
 * ROLE: เปิด / ปิดร้านประจำวัน และเงินทอนในลิ้นชัก
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_store_day, ao_stock_cash_move, ao_stock_sale, ao_stock_return, ao_stock_log (ผ่าน api.php)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 7: เปิด–ปิดร้านลงตาราง · เงินทอนยกมาของจริง · เตือนเมื่อวันก่อนยังไม่ปิดร้าน
 *   - [x] ช่วงที่ 7: ฟอร์มเติมเงินทอน / หยิบเงินออกระหว่างวัน (act=cash) · ยอดเงินเป็นทศนิยม 2 ตำแหน่ง
 *   - [x] ช่วงที่ 8: "งานของสาขาวันนี้" นับจากเอกสารจริง (branch_rank_today — ขาย รับเข้า เบิก ตรวจนับ รับคืน)
 *   - [x] ช่วงที่ 11: แยกสิทธิ์ เปิด / ปิดร้าน (store) · เปิดร้านอีกครั้ง (store_reopen — เดิมเปิดได้เฉพาะผู้ดูแลจากหน้าภาพรวม adm-dashboard ซึ่งยังเปิดได้เหมือนเดิม) · เงินเข้า / ออก (cash)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
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

/* ---------- รับค่าจากฟอร์ม ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $error = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } else {
        $act  = isset($_POST['act']) ? $_POST['act'] : '';
        $need = array('open' => 'store', 'close' => 'store', 'reopen' => 'store_reopen', 'cash' => 'cash');   // ช่วงที่ 11

        if (isset($need[$act]) && !can($user, $need[$act])) {
            $pl    = perm_list();
            $error = 'ไม่มีสิทธิ์ “' . $pl[$need[$act]]['short'] . '” — ติดต่อผู้ดูแลเพื่อเปิดสิทธิ์';
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
            if ($reason === '') {
                $error = 'กรุณาระบุเหตุผลที่ต้องเปิดร้านใหม่';
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

            /* เงินไม่ตรงกับที่ควรมี = การแก้ไขตัวเลข ต้องมีหมายเหตุทุกครั้ง */
            if (abs($diff) >= 0.01 && trim($note) === '') {
                $error = 'เงินที่นับได้ไม่ตรงกับยอดที่ควรมี กรุณากรอกหมายเหตุว่าเกิดจากอะไร';
            } elseif (store_close($code, $user, $counted, $keep, $note) === null) {
                $error = 'ร้านปิดไปแล้ว (อาจมีเครื่องอื่นปิดก่อน)';
            } else {
                header('Location: ' . url('store.php?closed=1'));
                exit;
            }
        } elseif ($act === 'cash') {
            /* เติมเงินทอน / หยิบเงินออกระหว่างวัน — ต้องมีเหตุผลเสมอ */
            $r = store_cash_add($code, $user, isset($_POST['dir']) ? $_POST['dir'] : '',
                                isset($_POST['amount']) ? preg_replace('/[^0-9.]/', '', $_POST['amount']) : 0,
                                isset($_POST['why']) ? $_POST['why'] : '');
            if (isset($r['error'])) {
                $error = $r['error'];
            } else {
                header('Location: ' . url('store.php?cash=1'));
                exit;
            }
        }
    }
}

if (isset($_GET['opened'])) { $notice = 'เปิดร้านเรียบร้อย เริ่มทำงานได้เลย'; }
if (isset($_GET['closed'])) { $notice = 'ปิดร้านเรียบร้อย สรุปยอดถูกส่งให้ผู้ดูแลแล้ว'; }
if (isset($_GET['reopened'])) { $notice = 'เปิดร้านใหม่แล้ว ขายต่อได้ — ปิดร้านอีกครั้งเมื่อเสร็จ'; }
if (isset($_GET['cash'])) { $notice = 'บันทึกเงินเข้า / ออกลิ้นชักแล้ว'; }

$state    = store_state($code);
$isOpen   = store_is_open($code);
$isClosed = store_is_closed($code);
$cash     = store_daily_cash($code);                 // อ่านใหม่หลังบันทึก
$expected = store_expected_cash($code);
$cashList = $isOpen ? store_cash_list($code) : array();
$num      = function ($n) { return rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.'); };   // ค่าในช่อง input

/* สรุปงานของทั้งสาขาวันนี้ (ใช้ตอนปิดร้าน) */
$rank      = branch_rank_today($code);
$branchQty = 0;
$branchDoc = 0;
foreach ($rank as $r) { $branchQty += $r['qty']; $branchDoc += $r['docs']; }

$branch = $code;                       // ตัวเลือกสาขาบน topbar ล็อกที่สาขาของร้าน

$PAGE_TITLE = $isOpen ? 'ร้านเปิดอยู่' : ($isClosed ? 'ปิดร้านแล้ว' : 'เปิดร้านวันนี้');
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
    <h2>เปิดร้านวันนี้</h2>
    <p><?php echo e(branch_name($code)) ?> · <?php echo e(thai_date_full(time())) ?> · <?php echo e($user['name']) ?></p>
  </div>

  <?php if ($carry['unclosed'] !== '') { ?>
    <div class="alert alert-warn" role="alert">
      <svg class="ico"><use href="#i-alert"/></svg>
      <span><?php echo e(thai_date_full(strtotime($carry['unclosed']))) ?> ยังไม่ได้ปิดร้าน — เงินทอนยกมาใช้เงินทอนมาตรฐานของสาขา
            นับเงินในลิ้นชักก่อนเปิดร้าน ถ้าไม่เท่ากด “แจ้งยอดไม่ตรง” · ผู้ดูแลเห็นรายการนี้ในภาพรวม</span>
    </div>
  <?php } ?>

  <?php if (!can($user, 'store')) { ?>
  <p class="store-note">ร้านยังไม่เปิด — เปิดร้านได้เฉพาะคนที่มีสิทธิ์ “เปิด / ปิดร้าน” · ระหว่างนี้ทำงานอื่นที่ไม่ต้องเปิดร้านได้ตามปกติ</p>
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
        <input class="input cash" type="number" id="topup" name="topup" value="0" min="0" step="100" inputmode="numeric">
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
        array('stocktake.php',    'i-clipboard', 'ตรวจนับ / ปรับยอด'),
        array('movements.php',    'i-activity', 'ประวัติเคลื่อนไหว'),
        array('history.php',      'i-history', 'ประวัติการทำรายการ'),
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

  <!-- เงินเข้า / ออกลิ้นชักระหว่างวัน -->
  <section class="card store-cash">
    <div class="card-head"><div><h3>เติมเงินทอน / หยิบเงินออก</h3><p>ระหว่างวัน · ต้องระบุเหตุผลทุกครั้ง · ลงประวัติให้ผู้ดูแลเห็น</p></div></div>
    <div class="card-body num">
      <?php if ($cashList) { ?>
        <div class="kvl">
          <?php foreach ($cashList as $cm) { ?>
            <div><span><?php echo e($cm['time']) ?> น. · <?php echo e($cm['reason']) ?> <small>(<?php echo e($cm['by']) ?>)</small></span>
                 <b class="<?php echo $cm['dir'] === 'in' ? 'qty-in' : 'qty-out' ?>"><?php echo $cm['dir'] === 'in' ? '+' : '−' ?><?php echo e(money2($cm['amount'])) ?></b></div>
          <?php } ?>
        </div>
      <?php } ?>
      <?php if (can($user, 'cash')) { ?>
      <form method="post" action="store.php" class="store-cash-f">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="act" value="cash">
        <div class="field">
          <label for="cash-dir">รายการ</label>
          <select class="input" id="cash-dir" name="dir">
            <option value="in">เติมเงินทอนเข้าลิ้นชัก</option>
            <option value="out">หยิบเงินออกจากลิ้นชัก</option>
          </select>
        </div>
        <div class="field">
          <label for="cash-amt">จำนวนเงิน</label>
          <input class="input" type="number" id="cash-amt" name="amount" min="0.01" step="0.01" inputmode="decimal" required>
        </div>
        <div class="field field-why">
          <label for="cash-why">เหตุผล</label>
          <input class="input" type="text" id="cash-why" name="why" maxlength="200" required autocomplete="off"
                 placeholder="เช่น แลกเหรียญเพิ่ม / ซื้อถุงใส่ของ">
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

  <form method="post" action="store.php" class="store-two">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
    <input type="hidden" name="act" value="close">

    <!-- สรุปงานวันนี้ -->
    <section class="card">
      <div class="card-head">
        <div><h3>งานของสาขาวันนี้</h3><p><?php echo number_format($branchDoc) ?> เอกสาร · <?php echo count($rank) ?> คน</p></div>
      </div>
      <div class="card-body num">
        <div class="kvl">
          <?php foreach ($rank as $r) { ?>
            <div>
              <span><?php echo e($r['name']) ?><?php echo $r['username'] === $user['username'] ? ' (ฉัน)' : '' ?></span>
              <b><?php echo $r['idle'] ? '—' : number_format($r['docs']) . ' ใบ · ' . number_format($r['qty']) . ' ชิ้น' ?></b>
            </div>
          <?php } ?>
          <div class="tot"><span>รวมทั้งสาขา</span><b><?php echo number_format($branchQty) ?> ชิ้น</b></div>
        </div>
      </div>
    </section>

    <!-- เงินสดในลิ้นชัก -->
    <section class="card">
      <div class="card-head"><div><h3>เงินสดในลิ้นชัก</h3><p>เงินทอน + เงินขาย · ตรวจนับก่อนปิดร้าน</p></div></div>
      <div class="card-body num">
        <div class="kvl">
          <div><span>เงินทอนเริ่มวัน</span><b><?php echo e(money2($state['float'])) ?></b></div>
          <div><span>+ เติมระหว่างวัน</span><b><?php echo e(money2($cash['topup'])) ?></b></div>
          <div><span>+ ขายเงินสด</span><b><?php echo e(money2(store_cash_sales($code))) ?></b></div>
          <div><span>− หยิบออกใช้จ่าย</span><b>−<?php echo e(money2($cash['withdraw'])) ?></b></div>
          <?php if (store_refunds($code) > 0) { ?>
          <div><span>− คืนเงินลูกค้า (รับคืนสินค้า)</span><b>−<?php echo e(money2(store_refunds($code))) ?></b></div>
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
                 value="<?php echo (int) branch_default_float($code) ?>" min="0" step="100" inputmode="numeric">
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
    <form class="card-body" method="post" action="store.php">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
      <input type="hidden" name="act" value="reopen">
      <div class="field">
        <label for="reopen-reason">เหตุผล</label>
        <input class="input" type="text" id="reopen-reason" name="reason" required autocomplete="off"
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

  var adjLink = document.getElementById('adj-link');
  if (adjLink) {
    adjLink.addEventListener('click', function (e) {
      e.preventDefault();
      var box = document.getElementById('adj');
      box.hidden = false;
      document.getElementById('counted').focus();
      this.parentNode.style.display = 'none';
    });
  }

  /* ---- หน้าปิดร้าน ---- */
  var exp  = document.getElementById('expected');
  var cash = document.getElementById('cash_counted');
  var diff = document.getElementById('diff');
  function calcClose() {
    if (!exp || !cash || !diff) { return; }
    var d = (Number(cash.value) || 0) - Number(exp.dataset.v);
    diff.hidden = (d === 0);
    diff.className = 'diff' + (d > 0 ? ' over' : '');
    document.getElementById('diff-lb').textContent = d > 0 ? 'เกิน' : 'ขาด';
    document.getElementById('diff-v').textContent  = (d > 0 ? '+' : '−') + fmt(Math.abs(d));
  }
  if (cash) { cash.addEventListener('input', calcClose); calcClose(); }

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
