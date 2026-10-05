<?php
/**
 * FILE: themes/aostock/receive.php
 * ROLE: นำเข้าสินค้า (รับของเข้าสต๊อกสาขา)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/receive-live.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_receive, ao_stock_receive_item, ao_stock_move, ao_stock_balance, ao_stock_doc_seq, ao_stock_log (ผ่าน api.php) · ใบที่กำลังทำ (ร่าง) อยู่ใน $_SESSION['recv_draft']
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ใบที่บันทึกแล้วเก็บในตาราง ao_stock_* (ช่วงที่ 6) · บันทึกไม่ผ่านแสดงข้อความแทนหน้า error
 *   - [x] ช่วงที่ 11: แก้ / ยกเลิกใบของตัวเองต้องมีสิทธิ์ doc_fix · ปุ่มแก้ / ยกเลิกและลิงก์ยอดคงเหลือแสดงตามสิทธิ์
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   นำเข้าสินค้า (รับของเข้าสต๊อกสาขา)
   ----------------------------------------------------------
   วิธีทำงาน: ค้นหา → แตะเพิ่มเข้าใบ → ปรับจำนวน → บันทึกทั้งใบครั้งเดียว
   ของมาเป็นใบส่งของ จึงควรเป็นเอกสารรับเข้าใบเดียว เลขอ้างอิงเดียว
   ยังไม่กดบันทึก = ยังไม่แตะสต๊อก เลิกกลางคันได้

   ใบที่กำลังทำอยู่ใน $_SESSION['recv_draft'] (ร่าง)
   บันทึกแล้ว: ใบรับเข้า + stock_move + ยอดใน stock_balance + ประวัติ ในทรานแซกชันเดียว (receive_save)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();
$code = work_branch($user);          // ผู้ดูแลเลือกสาขาได้จากแถบบน

/* งานคลังไม่ผูกกับการเปิดร้าน — ของมาส่งเช้าก่อนเปิดร้านก็รับเข้าได้
   มีแค่หน้าขายสินค้าที่ต้องเปิดร้านก่อน (เพราะเกี่ยวกับลิ้นชักเงินสด) */

$isHx        = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
$q           = isset($_GET['q'])   ? trim($_GET['q'])   : '';
$cat         = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$err         = '';
$done        = null;
$oldRef  = '';
$oldNote = '';
$voided  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่';
    } else {
        $act = isset($_POST['act']) ? $_POST['act'] : '';
        $sku = isset($_POST['sku']) ? trim($_POST['sku']) : '';

        if ($act === 'add') {
            rdraft_add($sku, 1);
        } elseif ($act === 'minus') {
            rdraft_add($sku, -1);
        } elseif ($act === 'set') {
            $k = 'rq_' . $sku;
            rdraft_set($sku, isset($_POST[$k]) ? preg_replace('/\D/', '', $_POST[$k]) : 0);
        } elseif ($act === 'del') {
            rdraft_remove($sku);
        } elseif ($act === 'clear') {
            rdraft_clear();
        } elseif ($act === 'void' || $act === 'edit') {
            /* ยกเลิกใบที่บันทึกไปแล้ว — ต้องมีหมายเหตุเสมอ
               act=edit คือยกเลิกแล้วดึงรายการเดิมกลับเข้าใบเพื่อแก้ */
            $no     = isset($_POST['no']) ? trim($_POST['no']) : '';
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            $redo   = ($act === 'edit');

            $chk    = receive_by_no($code, $no);

            if ($chk !== null && !can_void_doc($user, $chk)) {
                $err = ($chk['by_user'] === $user['username'])
                     ? 'ไม่มีสิทธิ์ “แก้เอกสารคลังตัวเอง” — ติดต่อผู้ดูแลเพื่อเปิดสิทธิ์'
                     : 'ใบรับเข้านี้เป็นของพนักงานคนอื่น — ต้องมีสิทธิ์แก้งานคนอื่นจึงจะแก้หรือยกเลิกได้';
            } elseif ($reason === '') {
                $err = 'การยกเลิกหรือแก้ไขใบรับเข้าต้องระบุหมายเหตุทุกครั้ง';
            } elseif ($redo && rdraft_count() > 0) {
                $err = 'ยังมีใบที่ทำค้างอยู่ บันทึกหรือล้างใบนั้นก่อนจึงจะแก้ใบเก่าได้';
            } else {
                $v = receive_void($code, $no, $user, $reason, $redo);
                if ($v === null) {
                    $err = 'ไม่พบใบนี้ หรือถูกยกเลิกไปแล้ว';
                } elseif (isset($v['error']) && $v['error'] === 'sold') {
                    $err = 'ยกเลิกไม่ได้ เพราะของบางส่วนถูกขายออกไปแล้ว — '
                         . implode(' · ', $v['items'])
                         . ' · กรณีนี้ให้ใช้การตรวจนับ/ปรับยอดแทน';
                } else {
                    $voided = $v;
                    if ($redo) {
                        rdraft_from_receive($v);
                    }
                }
            }
        } elseif ($act === 'save') {
            $ref  = isset($_POST['ref'])  ? trim($_POST['ref'])  : '';
            $note = isset($_POST['note']) ? trim($_POST['note']) : '';

            $oldRef  = $ref;
            $oldNote = $note;

            $lines = rdraft_lines($code);

            if (!$lines) {
                $err = 'ยังไม่ได้เลือกสินค้าเข้าใบสักรายการ';
            } elseif ($ref === '') {
                $err = 'กรุณากรอกเลขที่เอกสารอ้างอิง (ใบส่งของ / ใบกำกับ) ทุกครั้ง';
            } else {
                $done = receive_save($code, $user, $ref, $note, $lines);
                if (isset($done['error'])) {
                    $err  = $done['error'];
                    $done = null;
                } else {
                    rdraft_clear();
                    header('Location: ' . url('receive.php?done=' . rawurlencode($done['no'])));
                    exit;
                }
            }
        }

        /* ปุ่มเพิ่ม/ลด ถ้าไม่ได้มาจาก htmx ก็ redirect กลับตามปกติ */
        if (!$isHx && $err === '' && $act !== 'save') {
            if ($voided !== null) {
                header('Location: ' . url('receive.php?voided=' . rawurlencode($voided['no'])));
                exit;
            }
            $to = 'receive.php';
            if ($q !== '' || $cat !== '') {
                $to .= '?' . ($q !== '' ? 'q=' . rawurlencode($q) : '')
                     . ($cat !== '' ? ($q !== '' ? '&' : '') . 'cat=' . rawurlencode($cat) : '');
            }
            header('Location: ' . url($to));
            exit;
        }
    }
}

/* คำขอจาก htmx: ส่งกลับเฉพาะส่วนที่เปลี่ยน */
if ($isHx) {
    require dirname(__FILE__) . '/inc/receive-live.php';
    exit;
}

if (isset($_GET['done'])) {
    $done = receive_by_no($code, $_GET['done']);
}
if ($voided === null && isset($_GET['voided'])) {
    $b = receive_by_no($code, $_GET['voided']);
    if ($b !== null && !empty($b['void'])) {
        $voided = $b;
    }
}

$branch     = $code;
$PAGE_TITLE = 'นำเข้าสินค้า';
$PAGE_SUB   = branch_name($code) . ' · หนึ่งใบส่งของ = หนึ่งเอกสารรับเข้า';
$NAV_ACTIVE = 'receive.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($err !== '') { ?>
  <div class="alert alert-error" role="alert">
    <svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span>
  </div>
<?php } ?>

<?php if ($voided !== null) { ?>
  <?php $isEdit = (isset($voided['void_mode']) && $voided['void_mode'] === 'edit'); ?>
  <div class="alert <?php echo $isEdit ? 'alert-info' : 'alert-warn' ?>" role="status">
    <svg class="ico"><use href="#<?php echo $isEdit ? 'i-arrow' : 'i-ban' ?>"/></svg>
    <span>
      <?php if ($isEdit) { ?>
        ยกเลิกใบ <b><?php echo e($voided['no']) ?></b> และดึง <?php echo (int) $voided['items'] ?> รายการกลับเข้าใบให้แล้ว —
        แก้จำนวนที่ผิดแล้วบันทึกใหม่ได้เลย ระบบจะออกเลขใบใหม่ให้
      <?php } else { ?>
        ยกเลิกใบรับเข้า <b><?php echo e($voided['no']) ?></b> แล้ว ·
        ถอนออกจากสต๊อก <b class="num"><?php echo number_format($voided['qty']) ?></b> ชิ้น ·
        เหตุผล: <?php echo e($voided['void_reason']) ?>
      <?php } ?>
    </span>
  </div>
<?php } ?>

<?php if ($done !== null && empty($done['void'])) { ?>
  <div class="alert alert-ok" role="status">
    <svg class="ico"><use href="#i-check"/></svg>
    <span>
      รับเข้าเรียบร้อย เอกสาร <b><?php echo e($done['no']) ?></b> ·
      <?php echo (int) $done['items'] ?> รายการ · <b class="num"><?php echo number_format($done['qty']) ?></b> ชิ้น ·
      อ้างอิง <?php echo e($done['ref']) ?>
    </span>
    <?php
    /* รับเข้าเกิน/ผิด แก้ได้ทันทีจากตรงนี้ ทั้งสองทางต้องกรอกหมายเหตุก่อน */
    $dat = ' data-bill="' . e($done['no']) . '" data-qty="' . (int) $done['qty']
         . '" data-items="' . (int) $done['items'] . '"';
    ?>
    <div class="alert-act">
      <?php foreach ((can_void_doc($user, $done) ? array(
          array('edit', 'แก้ไขใบนี้',  'i-arrow', 'หมายเหตุการแก้ไขใบรับเข้า'),
          array('void', 'ยกเลิกใบนี้', 'i-ban',   'หมายเหตุการยกเลิกใบรับเข้า'),
      ) : array()) as $b) { ?>
        <form method="post" action="receive.php" data-confirm="<?php echo e($b[0]) ?>"<?php echo $dat ?>>
          <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
          <input type="hidden" name="act" value="<?php echo e($b[0]) ?>">
          <input type="hidden" name="no" value="<?php echo e($done['no']) ?>">
          <input class="reason-fb" type="text" name="reason" value=""
                 placeholder="หมายเหตุ (จำเป็น)" aria-label="<?php echo e($b[3]) ?>">
          <button class="btn btn-ghost btn-sm" type="submit">
            <svg class="ico"><use href="#<?php echo e($b[2]) ?>"/></svg> <?php echo e($b[1]) ?>
          </button>
        </form>
      <?php } ?>
      <?php if (page_ok($user, 'products.php')) { ?>
      <a class="btn btn-ghost btn-sm" href="products.php">
        <svg class="ico"><use href="#i-boxes"/></svg> ดูยอดคงเหลือ
      </a>
      <?php } ?>
    </div>
  </div>
<?php } ?>

<form method="post" action="receive.php" id="recv-form" data-confirm="receive">
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
  <input type="hidden" name="act" value="save">

  <!-- ===== หัวเอกสาร ===== -->
  <section class="card recv-head">
    <div class="card-head">
      <div>
        <h2>ข้อมูลการรับเข้า</h2>
        <span class="sub">ทุกครั้งที่รับของต้องมีเอกสารอ้างอิง เพื่อให้ตรวจย้อนหลังได้</span>
      </div>
    </div>
    <div class="recv-fields">
      <div class="field">
        <label for="ref">เลขที่เอกสารอ้างอิง <i class="req">จำเป็น</i></label>
        <input class="input" type="text" id="ref" name="ref" autocomplete="off"
               value="<?php echo e($oldRef) ?>" placeholder="เช่น INV-2609-0142 หรือเลขใบส่งของ">
      </div>

      <?php /* ผู้รับเข้าและสาขา ดึงจากบัญชีที่ล็อกอินอยู่ ไม่ต้องกรอกซ้ำ */ ?>
      <div class="field">
        <label>ผู้รับเข้า</label>
        <div class="who-box">
          <span class="av"><?php echo e($user['initials']) ?></span>
          <div>
            <b><?php echo e($user['name']) ?></b>
            <small><?php echo e(role_name($user['role'])) ?> · <?php echo e(branch_name($code)) ?></small>
          </div>
        </div>
      </div>

      <div class="field field-wide">
        <label for="note">หมายเหตุ</label>
        <input class="input" type="text" id="note" name="note" autocomplete="off"
               value="<?php echo e($oldNote) ?>" placeholder="เช่น ของมาไม่ครบ 3 ชิ้น รอส่งตามภายหลัง">
      </div>
    </div>
  </section>

  <?php require dirname(__FILE__) . '/inc/receive-live.php'; ?>

  <!-- ===== แถบสรุปติดขอบล่าง ===== -->
  <div class="recv-bar" id="recv-bar">
    <div class="rb-t">
      <b id="rb-items">0</b> รายการ ·
      <b class="num" id="rb-qty">0</b> ชิ้น
      <span class="rb-diff" id="rb-diff" hidden></span>
      <small id="rb-hint">เลือกสินค้าเข้าใบก่อน</small>
    </div>
    <button class="btn-xl btn-in" type="submit" id="rb-go" disabled>
      <svg class="ico"><use href="#i-in"/></svg> บันทึกการรับเข้า
    </button>
  </div>
</form>

<script>
(function () {
  var items = document.getElementById('rb-items');
  var qtyEl = document.getElementById('rb-qty');
  var hint  = document.getElementById('rb-hint');
  var go    = document.getElementById('rb-go');
  var bar   = document.getElementById('recv-bar');
  var refEl = document.getElementById('ref');

  function num(v) { return Number(String(v).replace(/[^0-9]/g, '')) || 0; }

  /* เรียกทุกครั้งที่ htmx สลับรายการในใบ (ดูท้ายไฟล์ inc/receive-live.php) */
  window.recvSync = function () {
    var sumEl = document.getElementById('recv-sum');
    var n = document.querySelectorAll('#recv-live .recv-list li').length;
    var q = sumEl ? num(sumEl.getAttribute('data-v')) : 0;
    var hasRef = refEl.value.replace(/\s/g, '') !== '';

    items.textContent = n.toLocaleString('th-TH');
    qtyEl.textContent = q.toLocaleString('th-TH');

    go.disabled   = (n === 0 || !hasRef);
    bar.className = go.disabled ? 'recv-bar' : 'recv-bar on';

    if (n === 0)       { hint.textContent = 'เลือกสินค้าเข้าใบก่อน'; }
    else if (!hasRef)  { hint.textContent = 'ยังไม่ได้กรอกเลขที่เอกสารอ้างอิง'; }
    else               { hint.textContent = 'ตรวจอีกครั้งก่อนกดบันทึก'; }
  };

  refEl.addEventListener('input', window.recvSync);
  document.body.addEventListener('htmx:afterSettle', function () { window.recvSync(); });
  window.recvSync();

  /* ข้อมูลให้กล่องยืนยันไปสรุป */
  var form = document.getElementById('recv-form');
  form.recvSummary = function () {
    var out = [];
    var rows = document.querySelectorAll('#recv-live .recv-list li');
    for (var i = 0; i < rows.length; i++) {
      out.push({
        name: rows[i].querySelector('.cl-t b').textContent,
        qty:  num(rows[i].querySelector('.rq-set').value),
        unit: rows[i].querySelector('.cl-s').textContent.trim()
      });
    }
    return out;
  };
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
