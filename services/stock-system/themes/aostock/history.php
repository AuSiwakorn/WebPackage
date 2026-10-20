<?php
/**
 * FILE: themes/aostock/history.php
 * ROLE: ประวัติการทำรายการ
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/history-past.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_log (ประวัติวันนี้) · ao_stock_receive / issue / count (แก้ / ยกเลิกเอกสารคลัง) — ผ่าน api.php · ao_stock_sale (แก้ / ยกเลิกบิลวันนี้)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ประวัติวันนี้อ่านจาก ao_stock_log · เอกสารคลังอ่าน / ยกเลิกจากตาราง (ช่วงที่ 6)
 *   - [x] บิลขาย / เปิด–ปิดร้าน ลงฐานข้อมูล (ช่วงที่ 7)
 *   - [x] ช่วงที่ 8: วันก่อนมีลำดับเหตุการณ์ทั้งวันจาก ao_stock_log (inc/history-past.php)
 *   - [x] ช่วงที่ 9: ซ่อนปุ่มไปรายงานยอดขายเมื่อเมนูนั้นถูกปิดจากหลังบ้าน
 *   - [x] ช่วงที่ 12: พนักงานทั่วไปไม่เห็นรายการ "ตั้งค่า" (เพิ่ม / แก้พนักงาน รีเซ็ต PIN แก้สาขา ฯลฯ) · ผู้จัดการสาขาเห็นครบ
 *   - [x] ช่วงที่ 11: ปุ่มแก้ / ยกเลิกตามสิทธิ์ใหม่ (บิล = bill_fix + แก้ไขต้องขายได้ · เอกสารคลัง = doc_fix + สิทธิ์หน้างานนั้น) · ลิงก์ตามสิทธิ์ (page_ok)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 *   - [x] ปุ่ม “ดู / พิมพ์บิล” ทุกบิลของวันนี้ (รวมบิลที่ยกเลิก)
 *   - [x] ผลทดสอบ 7 ต.ค. (M7): ยกเลิก / แก้บิลไม่ผ่าน (เช่น ร้านปิดแล้ว) แสดงข้อความ — เดิม redirect เงียบ ๆ
 *   - [x] 15 ต.ค. (เมนูประวัติ ช่วง 2): ปุ่มกรองเป็น 7 กลุ่ม (Stock_getLogGroups) แทน 15 ชนิด · รายการยกเลิกอยู่ในกลุ่มของเอกสารนั้น
 *   - [x] 15 ต.ค. (เมนูประวัติแยกหน้า ช่วง 4): 1 หน้า = 1 กลุ่ม ไม่มี “ทั้งหมด” / แถบปุ่มกรอง · ต้องมีสิทธิ์ของกลุ่ม (Stock_getAllowedLogGroups) · วันย้อนหลังแสดงเฉพาะกลุ่มนั้น
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ประวัติการทำรายการ
   วันนี้อ่านจาก ao_stock_log (log_today) · วันก่อน (?d=) ใช้ inc/history-past.php
   หน้าของพนักงาน — ผู้ดูแลใช้ adm-history.php (require_login พาไปเอง)
   ?t= = กลุ่มของ Stock_getLogGroups (1 เมนู = 1 กลุ่ม) · ไม่ระบุ / ไม่มีสิทธิ์ = กลุ่มแรกที่ดูได้
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();
$code = work_branch($user);

/* ---------- กลุ่มของหน้านี้ — ต้องมีสิทธิ์ของกลุ่ม และฟีเจอร์ของกลุ่มไม่ได้ถูกปิด ---------- */
$allowed = Stock_getAllowedLogGroups($user);
if (!$allowed) {
    $_SESSION['flash'] = 'เมนูนี้ถูกปิดใช้งานอยู่ — ติดต่อคนดูแลเว็บถ้าต้องการใช้';      // มีสิทธิ์แต่กลุ่มที่ดูได้ถูกปิดหมด
    Stock_sendRedirect(home_page($user));
}
$t = (isset($_GET['t']) && is_string($_GET['t'])) ? Stock_getLogGroupOf(trim($_GET['t'])) : '';   // ลิงก์เก่าแบบชนิด → กลุ่มของมัน
if (!isset($allowed[$t])) {
    $t = key($allowed);
}
$HIST_BASE     = 'history.php?t=' . $t;     // ลิงก์ / ฟอร์มทุกอันอยู่กลุ่มเดิม (hist_url)
$CONFIRM_MODAL = true;                      // ยกเลิก / แก้บิลและเอกสารต้องยืนยัน (footer.php)

/* ยังไม่เปิดร้านก็เข้าหน้านี้ได้ — ดูอย่างเดียว ไม่ได้เปลี่ยนสต๊อกหรือเงินสด
   (หน้าที่ทำรายการ เช่น ขาย รับเข้า ตัดออก ยังต้องเปิดร้านก่อน) */
$notOpened = (store_state($code) === null);

$err = '';

/* ---------- ดูย้อนหลัง (สิทธิ์เสริม backdate / ผู้ดูแล) ---------- */
$pastLimit = backdate_limit($user, $code);
$pastDay   = isset($_GET['d']) ? preg_replace('/\D/', '', $_GET['d']) : '';
$pastTs    = null;
if ($pastDay !== '' && $pastDay !== date('Ymd')) {
    $ts  = strtotime($pastDay);
    $age = $ts ? (int) round((strtotime(date('Y-m-d')) - $ts) / 86400) : -1;
    if ($ts && $age >= 1 && $age <= $pastLimit) {
        $pastTs  = $ts;
        $pastDay = date('Ymd', $ts);
    }
}

/* ---------- ยกเลิก / แก้ไขเอกสารย้อนหลัง ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act'])
    && ($_POST['act'] === 'past_void' || $_POST['act'] === 'past_edit')) {
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่';
    } else {
        $redo = ($_POST['act'] === 'past_edit');
        $r    = past_doc_void($code, isset($_POST['no']) ? trim($_POST['no']) : '', $user,
                              isset($_POST['reason']) ? $_POST['reason'] : '', $redo);
        if (isset($r['error'])) {
            $err = $r['error'];
        } else {
            header('Location: ' . url($redo ? $r['page'] : hist_url('d=' . rawurlencode($pastDay))));
            exit;
        }
    }
}

/* ---------- ยกเลิกบิล (ต้องมีหมายเหตุเสมอ) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $err === '' && !$pastTs) {
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่';
    } elseif (isset($_POST['act']) && ($_POST['act'] === 'void' || $_POST['act'] === 'edit')) {
        $no     = isset($_POST['no']) ? trim($_POST['no']) : '';
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
        $redo   = ($_POST['act'] === 'edit');

        $chk    = bill_by_no($code, $no);

        if ($chk !== null && !can_void_doc($user, $chk, 'bill')) {
            $err = ($chk['by_user'] === $user['username'])
                 ? 'ไม่มีสิทธิ์ “แก้บิลตัวเอง” — ติดต่อผู้ดูแลเพื่อเปิดสิทธิ์'
                 : 'บิลนี้เป็นของพนักงานคนอื่น — ต้องมีสิทธิ์แก้งานคนอื่นจึงจะแก้หรือยกเลิกได้';
        } elseif ($redo && !can($user, 'sale')) {
            $err = 'แก้ไขบิล = ออกบิลใหม่ในหน้าขาย ต้องมีสิทธิ์ “ขายสินค้า” — ยกเลิกบิลได้อย่างเดียว';
        } elseif (bill_returned_any($no)) {
            $err = 'บิลนี้มีการรับคืนสินค้าไปแล้ว — ยกเลิกหรือแก้ทั้งบิลไม่ได้ ให้ใช้การรับคืนแทน';
        } elseif ($reason === '') {
            $err = 'การยกเลิกหรือแก้ไขบิลต้องระบุหมายเหตุทุกครั้ง';
        } elseif ($redo && cart_count() > 0) {
            $err = 'ตะกร้ายังมีสินค้าค้างอยู่ ปิดรายการนั้นให้เสร็จก่อนจึงจะแก้ไขบิลเก่าได้';
        } else {
            $v = bill_void($code, $no, $user, $reason, $redo);
            if ($v === null) {
                $err = 'ไม่พบบิลนี้ หรือถูกยกเลิกไปแล้ว';
            } elseif (isset($v['error'])) {
                $err = $v['error'];          // ร้านปิดแล้ว / มีรับคืนแล้ว — เดิมไม่แสดง กดแล้วเงียบ (ผลทดสอบ M7)
            } elseif ($redo) {
                /* ดึงรายการกลับเข้าตะกร้าแล้วพาไปหน้าขายต่อทันที */
                cart_from_bill($v, $code);
                header('Location: ' . url('sale.php?edited=' . rawurlencode($v['no'])));
                exit;
            }
        }
    }
    if ($err === '') {
        header('Location: ' . url(hist_url()));
        exit;
    }
}

$rows   = log_filter(log_today($code, 0, !is_branch_manager($user)), $t);      // ช่วงที่ 12: พนักงานทั่วไปไม่เห็นรายการ "ตั้งค่า" · ผู้จัดการสาขาเห็นครบ
$counts = log_counts($rows);
$mine   = 0;
foreach ($rows as $r) {
    if ($r['by_user'] === $user['username']) {
        $mine++;
    }
}

$branch     = $code;
$PAGE_TITLE = 'ประวัติ' . $allowed[$t]['label'];
$PAGE_SUB   = branch_name($code) . ' · ' . thai_date_full($pastTs ? $pastTs : time()) . ($pastTs ? ' (ย้อนหลัง)' : '');
$NAV_ACTIVE = 'history.php?t=' . $t;                // เมนูของกลุ่มนี้ (header.php)
require dirname(__FILE__) . '/inc/header.php';
?>


<?php if ($notOpened) { ?>
  <div class="alert alert-info" role="status">
    <svg class="ico"><use href="#i-info"/></svg>
    <span>ยังไม่ได้เปิดร้านวันนี้ — ดูประวัติได้ตามปกติ งานคลัง (รับเข้า / ตัดออก / ตรวจนับ) ทำได้เลย ส่วนการขายต้องเปิดร้านก่อน</span>
    <div class="alert-act">
      <?php if (page_ok($user, 'store.php')) { ?>
      <a class="btn btn-ghost btn-sm" href="store.php"><svg class="ico"><use href="#i-store"/></svg> ไปเปิดร้าน</a>
      <?php } ?>
      <?php if (page_ok($user, 'report-sales.php')) { ?>
      <a class="btn btn-ghost btn-sm" href="report-sales.php"><svg class="ico"><use href="#i-chart"/></svg> ดูยอดขายย้อนหลัง</a>
      <?php } ?>
    </div>
  </div>
<?php } ?>

<?php if ($err !== '') { ?>
  <div class="alert alert-error" role="alert">
    <svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span>
  </div>
<?php } ?>

<?php if ($pastLimit > 0) { ?>
  <!-- เลือกวัน — เห็นเฉพาะคนที่มีสิทธิ์แก้ย้อนหลัง (ผู้ดูแลย้อนได้ทุกวันที่มีข้อมูล) -->
  <nav class="cats day-pick" aria-label="เลือกวันที่">
    <a class="cat<?php echo $pastTs ? '' : ' on' ?>" href="<?php echo e(hist_url()) ?>">วันนี้</a>
    <?php for ($i = 1; $i <= $pastLimit; $i++) { $dts = strtotime('-' . $i . ' day', strtotime(date('Y-m-d'))); ?>
      <a class="cat<?php echo ($pastTs && date('Ymd', $dts) === $pastDay) ? ' on' : '' ?>" href="<?php echo e(hist_url('d=' . date('Ymd', $dts))) ?>"><?php echo e(thai_day_month($dts)) ?></a>
    <?php } ?>
  </nav>
<?php } ?>

<?php if ($pastTs) { ?>
  <?php $PAST_GROUP = $t; require dirname(__FILE__) . '/inc/history-past.php'; ?>
  <?php require dirname(__FILE__) . '/inc/footer.php'; exit; ?>
<?php } ?>

<?php /* การ์ดที่ 3 ของแต่ละกลุ่ม: array(ชื่อ, ชนิดที่นับ, หน่วย) */
$third = array('sale' => array('บิลขาย', 'sale', 'ใบ'), 'receive' => array('ใบรับเข้า', 'receive', 'ใบ'), 'issue' => array('ใบเบิก / ตัดออก', 'issue', 'ใบ'),
               'adjust' => array('ใบตรวจนับ', 'adjust', 'ใบ'), 'return' => array('ใบรับคืน', 'return', 'ใบ'), 'store' => array('เงินเข้า / ออก', 'cash', 'รายการ'));
$c3 = $third[$t]; ?>
<section class="mini num" aria-label="สรุปรายการวันนี้">
  <div class="m"><div class="lb">รายการ</div><div class="nm"><?php echo number_format(count($rows)) ?></div><div class="sb">ของสาขาวันนี้</div></div>
  <div class="m"><div class="lb">ของฉัน</div><div class="nm"><?php echo number_format($mine) ?></div><div class="sb">ที่ฉันเป็นคนทำ</div></div>
  <div class="m"><div class="lb"><?php echo e($c3[0]) ?></div><div class="nm"><?php echo number_format($counts[$c3[1]]) ?></div><div class="sb"><?php echo e($c3[2]) ?></div></div>
</section>

<section class="card">
  <div class="card-head">
    <div>
      <h2>รายการวันนี้</h2>
      <span class="sub">เรียงจากใหม่ไปเก่า</span>
    </div>
  </div>

  <?php if (!$rows) { ?>
    <p class="empty">
      <svg class="ico"><use href="#i-history"/></svg>
      วันนี้ยังไม่มีรายการ<?php echo e($allowed[$t]['label']) ?><br><small>รายการจะถูกบันทึกอัตโนมัติเมื่อเปิดร้าน ขาย รับเข้า ตัดออก หรือปิดร้าน</small>
    </p>
  <?php } else { ?>
    <ol class="tl">
      <?php foreach ($rows as $r) { ?>
        <?php $m = log_type_of($r['type']); ?>
        <li class="tl-i tone-<?php echo e($m['tone']) ?><?php echo $r['by_user'] === $user['username'] ? ' me' : '' ?>">
          <span class="tl-ic"><svg class="ico"><use href="#<?php echo e($m['icon']) ?>"/></svg></span>
          <div class="tl-b">
            <div class="tl-h">
              <b><?php echo e($r['title']) ?></b>
              <span class="badge b-<?php echo e($m['tone']) ?>"><?php echo e($m['label']) ?></span>
              <?php if ($r['amount'] !== null && in_array($r['type'], array('receive', 'rvoid'), true)) { ?>
                <?php /* รับเข้าเก็บ amount เป็นจำนวนชิ้น ไม่ใช่เงิน */ ?>
                <span class="tl-amt num"><?php echo $r['type'] === 'receive' ? '+' : '−' ?><?php echo number_format($r['amount']) ?> ชิ้น</span>
              <?php } elseif ($r['amount'] !== null) { ?>
                <span class="tl-amt num"><?php echo money2($r['amount']) ?> ฿</span>
              <?php } ?>
            </div>
            <div class="tl-m"><?php echo e($r['time']) ?> น. · <?php echo e($r['by']) ?></div>

            <?php
            /* บิลขาย / ใบรับเข้า ที่ยังไม่ถูกยกเลิก — แก้หรือยกเลิกได้จากตรงนี้
               ทั้งสองทางต้องกรอกหมายเหตุก่อนเสมอ */
            $bill = ($r['type'] === 'sale' && $r['ref'] !== '') ? bill_by_no($code, $r['ref']) : null;
            $rdoc = ($r['type'] === 'receive' && $r['ref'] !== '') ? receive_by_no($code, $r['ref']) : null;
            $idoc = ($r['type'] === 'issue' && $r['ref'] !== '') ? issue_by_no($code, $r['ref']) : null;
            $adoc = ($r['type'] === 'adjust' && $r['ref'] !== '') ? adj_by_no($code, $r['ref']) : null;
            ?>

            <?php if ($adoc !== null && empty($adoc['void']) && can_void_doc($user, $adoc) && can($user, 'stocktake')) { ?>
              <?php $dat = ' data-bill="' . e($adoc['no']) . '" data-items="' . (int) $adoc['items'] . '"'; ?>
              <div class="tl-act">
                <?php foreach (array(
                    array('edit', 'แก้ไขใบนี้',  'i-arrow'),
                    array('void', 'ยกเลิกใบนี้', 'i-ban'),
                ) as $b) { ?>
                  <form method="post" action="stocktake.php" data-confirm="<?php echo e($b[0]) ?>"<?php echo $dat ?>>
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                    <input type="hidden" name="act" value="<?php echo e($b[0]) ?>">
                    <input type="hidden" name="no" value="<?php echo e($adoc['no']) ?>">
                    <input class="reason-fb" type="text" name="reason" value=""
                           placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุ <?php echo e($adoc['no']) ?>">
                    <button class="btn btn-ghost btn-sm" type="submit">
                      <svg class="ico"><use href="#<?php echo e($b[2]) ?>"/></svg> <?php echo e($b[1]) ?>
                    </button>
                  </form>
                <?php } ?>
              </div>
            <?php } elseif ($adoc !== null && !empty($adoc['void'])) { ?>
              <p class="tl-voided">
                <svg class="ico"><use href="#i-ban"/></svg>
                ใบนี้ถูก<?php echo (isset($adoc['void_mode']) && $adoc['void_mode'] === 'edit') ? 'ยกเลิกเพื่อแก้ไข' : 'ยกเลิก' ?>
                เมื่อ <?php echo e($adoc['void_at']) ?> น. โดย <?php echo e($adoc['void_by']) ?>
                — <?php echo e($adoc['void_reason']) ?>
              </p>
            <?php } ?>

            <?php if ($idoc !== null && empty($idoc['void']) && can_void_doc($user, $idoc) && can($user, 'issue')) { ?>
              <?php $dat = ' data-bill="' . e($idoc['no']) . '" data-qty="' . (int) $idoc['qty']
                         . '" data-items="' . (int) $idoc['items'] . '"'; ?>
              <div class="tl-act">
                <?php foreach (array(
                    array('edit', 'แก้ไขใบนี้',  'i-arrow'),
                    array('void', 'ยกเลิกใบนี้', 'i-ban'),
                ) as $b) { ?>
                  <form method="post" action="issue.php" data-confirm="<?php echo e($b[0]) ?>"<?php echo $dat ?>>
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                    <input type="hidden" name="act" value="<?php echo e($b[0]) ?>">
                    <input type="hidden" name="no" value="<?php echo e($idoc['no']) ?>">
                    <input class="reason-fb" type="text" name="reason" value=""
                           placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุ <?php echo e($idoc['no']) ?>">
                    <button class="btn btn-ghost btn-sm" type="submit">
                      <svg class="ico"><use href="#<?php echo e($b[2]) ?>"/></svg> <?php echo e($b[1]) ?>
                    </button>
                  </form>
                <?php } ?>
              </div>
            <?php } elseif ($idoc !== null && !empty($idoc['void'])) { ?>
              <p class="tl-voided">
                <svg class="ico"><use href="#i-ban"/></svg>
                ใบนี้ถูก<?php echo (isset($idoc['void_mode']) && $idoc['void_mode'] === 'edit') ? 'ยกเลิกเพื่อแก้ไข' : 'ยกเลิก' ?>
                เมื่อ <?php echo e($idoc['void_at']) ?> น. โดย <?php echo e($idoc['void_by']) ?>
                — <?php echo e($idoc['void_reason']) ?>
              </p>
            <?php } ?>

            <?php if ($rdoc !== null && empty($rdoc['void']) && can_void_doc($user, $rdoc) && can($user, 'receive')) { ?>
              <?php $dat = ' data-bill="' . e($rdoc['no']) . '" data-qty="' . (int) $rdoc['qty']
                         . '" data-items="' . (int) $rdoc['items'] . '"'; ?>
              <div class="tl-act">
                <?php foreach (array(
                    array('edit', 'แก้ไขใบนี้',  'i-arrow'),
                    array('void', 'ยกเลิกใบนี้', 'i-ban'),
                ) as $b) { ?>
                  <form method="post" action="receive.php" data-confirm="<?php echo e($b[0]) ?>"<?php echo $dat ?>>
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                    <input type="hidden" name="act" value="<?php echo e($b[0]) ?>">
                    <input type="hidden" name="no" value="<?php echo e($rdoc['no']) ?>">
                    <input class="reason-fb" type="text" name="reason" value=""
                           placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุ <?php echo e($rdoc['no']) ?>">
                    <button class="btn btn-ghost btn-sm" type="submit">
                      <svg class="ico"><use href="#<?php echo e($b[2]) ?>"/></svg> <?php echo e($b[1]) ?>
                    </button>
                  </form>
                <?php } ?>
              </div>
            <?php } elseif ($rdoc !== null && !empty($rdoc['void'])) { ?>
              <p class="tl-voided">
                <svg class="ico"><use href="#i-ban"/></svg>
                ใบนี้ถูก<?php echo (isset($rdoc['void_mode']) && $rdoc['void_mode'] === 'edit') ? 'ยกเลิกเพื่อแก้ไข' : 'ยกเลิก' ?>
                เมื่อ <?php echo e($rdoc['void_at']) ?> น. โดย <?php echo e($rdoc['void_by']) ?>
                — <?php echo e($rdoc['void_reason']) ?>
              </p>
            <?php } ?>
            <?php if ($bill !== null) { ?>
              <?php
              /* ดู / พิมพ์บิลได้ทุกใบ (ใบที่ยกเลิกพิมพ์ออกมามีตรา “ยกเลิก”) · แก้ / ยกเลิกตามสิทธิ์ */
              $fix = empty($bill['void']) && can_void_doc($user, $bill, 'bill') && !bill_returned_any($bill['no']);
              $act = hist_url();
              $dat = ' data-bill="' . e($bill['no']) . '" data-total="' . e(money2($bill['total']))
                   . '" data-qty="' . (int) $bill['qty'] . '" data-items="' . (int) $bill['items'] . '"';
              ?>
              <div class="tl-act">
                <?php echo Stock_buildBillPrintButton($bill) ?>
                <?php if ($fix) { foreach (array(
                    array('edit', 'แก้ไขบิลนี้',  'i-arrow', 'หมายเหตุการแก้ไขบิล'),
                    array('void', 'ยกเลิกบิลนี้', 'i-ban',   'หมายเหตุการยกเลิกบิล'),
                ) as $b) { if ($b[0] === 'edit' && !can($user, 'sale')) { continue; } ?>
                  <form method="post" action="<?php echo e($act) ?>" data-confirm="<?php echo e($b[0]) ?>"<?php echo $dat ?>>
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                    <input type="hidden" name="act" value="<?php echo e($b[0]) ?>">
                    <input type="hidden" name="no" value="<?php echo e($bill['no']) ?>">
                    <input class="reason-fb" type="text" name="reason" value=""
                           placeholder="หมายเหตุ (จำเป็น)"
                           aria-label="<?php echo e($b[3]) ?> <?php echo e($bill['no']) ?>">
                    <button class="btn btn-ghost btn-sm" type="submit">
                      <svg class="ico"><use href="#<?php echo e($b[2]) ?>"/></svg> <?php echo e($b[1]) ?>
                    </button>
                  </form>
                <?php } } ?>
              </div>
              <?php if (!empty($bill['void'])) { ?>
              <p class="tl-voided">
                <svg class="ico"><use href="#i-ban"/></svg>
                บิลนี้ถูก<?php echo (isset($bill['void_mode']) && $bill['void_mode'] === 'edit') ? 'ยกเลิกเพื่อแก้ไข' : 'ยกเลิก' ?>
                เมื่อ <?php echo e($bill['void_at']) ?> น. โดย <?php echo e($bill['void_by']) ?>
                — <?php echo e($bill['void_reason']) ?>
              </p>
              <?php } ?>
            <?php } ?>
            <?php if ($r['detail']) { ?>
              <dl class="tl-d">
                <?php foreach ($r['detail'] as $k => $v) { ?>
                  <div><dt><?php echo e($k) ?></dt><dd class="num"><?php echo e($v) ?></dd></div>
                <?php } ?>
              </dl>
            <?php } ?>
          </div>
        </li>
      <?php } ?>
    </ol>
  <?php } ?>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
