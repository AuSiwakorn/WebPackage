<?php
/**
 * FILE: themes/aostock/issue.php
 * ROLE: เบิก / ตัดออก (ของออกจากสต๊อกโดยไม่ได้ขาย)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/issue-live.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_issue, ao_stock_issue_item, ao_stock_move, ao_stock_balance, ao_stock_doc_seq, ao_stock_log (ผ่าน api.php) · ใบที่กำลังทำ (ร่าง) อยู่ใน $_SESSION['issue_draft']
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ใบที่บันทึกแล้วเก็บในตาราง ao_stock_* (ช่วงที่ 6) · ของไม่พอตอนบันทึก (เช็กหลังล็อกยอด) แสดงข้อความแทนหน้า error
 *   - [x] ช่วงที่ 11: แก้ / ยกเลิกใบของตัวเองต้องมีสิทธิ์ doc_fix · ปุ่มแก้ / ยกเลิกและลิงก์ยอดคงเหลือแสดงตามสิทธิ์
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   เบิก / ตัดออก (ของออกจากสต๊อกโดยไม่ได้ขาย)
   ----------------------------------------------------------
   วิธีทำงานเหมือนหน้านำเข้าสินค้า แต่กลับทิศ:
   ค้นหา → แตะเพิ่มเข้าใบ → ปรับจำนวน → เลือกเหตุผล → บันทึกทั้งใบครั้งเดียว
   - ตัดเกินของที่มีไม่ได้ (ระบบปรับลงให้เท่าที่มี)
   - ทุกใบต้องมีเหตุผล · สูญหาย / อื่น ๆ ต้องกรอกหมายเหตุด้วย
   - ยกเลิกได้เสมอ เพราะเป็นการคืนยอดกลับเข้าสต๊อก
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();

/* ผู้ดูแลไม่เบิก / ตัดออกเอง — ใช้หน้าตรวจสอบรายการเบิก / ตัดออกทุกสาขา (adm-issue.php) */
if ($user['role'] === 'admin') {
    header('Location: ' . url('adm-issue.php'));
    exit;
}

$code = work_branch($user);

/* งานคลังไม่ผูกกับการเปิดร้าน — ของมาส่งเช้าก่อนเปิดร้านก็รับเข้าได้
   มีแค่หน้าขายสินค้าที่ต้องเปิดร้านก่อน (เพราะเกี่ยวกับลิ้นชักเงินสด) */

$isHx   = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
$q      = isset($_GET['q'])   ? trim($_GET['q'])   : '';
$cat    = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$err    = '';
$done   = null;
$voided = null;
$meta   = idraft_meta();
$old    = $meta;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่';
    } else {
        $act = isset($_POST['act']) ? $_POST['act'] : '';
        $sku = isset($_POST['sku']) ? trim($_POST['sku']) : '';

        if ($act === 'add') {
            idraft_add($code, $sku, 1);
        } elseif ($act === 'minus') {
            idraft_add($code, $sku, -1);
        } elseif ($act === 'set') {
            $k = 'iq_' . $sku;
            idraft_set($code, $sku, isset($_POST[$k]) ? preg_replace('/\D/', '', $_POST[$k]) : 0);
        } elseif ($act === 'del') {
            idraft_remove($sku);
        } elseif ($act === 'clear') {
            idraft_clear();
        } elseif ($act === 'void' || $act === 'edit') {
            $no     = isset($_POST['no']) ? trim($_POST['no']) : '';
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            $redo   = ($act === 'edit');

            $chk    = issue_by_no($code, $no);

            if ($chk !== null && !can_void_doc($user, $chk)) {
                $err = ($chk['by_user'] === $user['username'])
                     ? 'ไม่มีสิทธิ์ “แก้เอกสารคลังตัวเอง” — ติดต่อผู้ดูแลเพื่อเปิดสิทธิ์'
                     : 'ใบตัดออกนี้เป็นของพนักงานคนอื่น — ต้องมีสิทธิ์แก้งานคนอื่นจึงจะแก้หรือยกเลิกได้';
            } elseif ($reason === '') {
                $err = 'การยกเลิกหรือแก้ไขใบตัดออกต้องระบุหมายเหตุทุกครั้ง';
            } elseif ($redo && idraft_count() > 0) {
                $err = 'ยังมีใบที่ทำค้างอยู่ บันทึกหรือล้างใบนั้นก่อนจึงจะแก้ใบเก่าได้';
            } else {
                $v = issue_void($code, $no, $user, $reason, $redo);
                if ($v === null) {
                    $err = 'ไม่พบใบนี้ หรือถูกยกเลิกไปแล้ว';
                } else {
                    $voided = $v;
                    if ($redo) {
                        idraft_from_issue($v);
                    }
                }
            }
        } elseif ($act === 'save') {
            $why  = isset($_POST['why'])  ? trim($_POST['why'])  : '';
            $ref  = isset($_POST['ref'])  ? trim($_POST['ref'])  : '';
            $note = isset($_POST['note']) ? trim($_POST['note']) : '';
            $old  = array('reason' => $why, 'ref' => $ref, 'note' => $note);

            $lines   = idraft_lines($code);
            $reasons = issue_reasons();

            if (!$lines) {
                $err = 'ยังไม่ได้เลือกสินค้าเข้าใบสักรายการ';
            } elseif (!isset($reasons[$why])) {
                $err = 'กรุณาเลือกเหตุผลของการตัดออก';
            } elseif (issue_reason_needs_note($why) && $note === '') {
                $err = 'กรณี “' . issue_reason_label($why) . '” ต้องกรอกหมายเหตุอธิบายทุกครั้ง';
            } elseif ($short = issue_shortage($lines)) {
                $err = 'ของบางรายการไม่พอแล้ว (อาจถูกขายไประหว่างทำใบ) — ' . implode(' · ', $short);
            } else {
                $done = issue_save($code, $user, $why, $ref, $note, $lines);
                if (isset($done['error'])) {
                    $err  = $done['error'];
                    $done = null;
                } else {
                    idraft_clear();
                    header('Location: ' . url('issue.php?done=' . rawurlencode($done['no'])));
                    exit;
                }
            }
        }

        if (!$isHx && $err === '' && $act !== 'save') {
            if ($voided !== null) {
                header('Location: ' . url('issue.php?voided=' . rawurlencode($voided['no'])));
                exit;
            }
            $to = 'issue.php';
            if ($q !== '' || $cat !== '') {
                $to .= '?' . ($q !== '' ? 'q=' . rawurlencode($q) : '')
                     . ($cat !== '' ? ($q !== '' ? '&' : '') . 'cat=' . rawurlencode($cat) : '');
            }
            header('Location: ' . url($to));
            exit;
        }
    }
}

if ($isHx) {
    require dirname(__FILE__) . '/inc/issue-live.php';
    exit;
}

if (isset($_GET['done'])) {
    $done = issue_by_no($code, $_GET['done']);
}
if ($voided === null && isset($_GET['voided'])) {
    $b = issue_by_no($code, $_GET['voided']);
    if ($b !== null && !empty($b['void'])) {
        $voided = $b;
    }
}
if ($voided !== null && isset($voided['void_mode']) && $voided['void_mode'] === 'edit') {
    $old = idraft_meta();          // ดึงใบเก่ามาแก้ → เติมหัวใบเดิมให้
}

$branch     = $code;
$PAGE_TITLE = 'เบิก / ตัดออก';
$PAGE_SUB   = branch_name($code) . ' · ของที่ออกจากสต๊อกโดยไม่ได้ขาย';
$NAV_ACTIVE = 'issue.php';
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
        แก้ส่วนที่ผิดแล้วบันทึกใหม่ได้เลย ระบบจะออกเลขใบใหม่ให้
      <?php } else { ?>
        ยกเลิกใบตัดออก <b><?php echo e($voided['no']) ?></b> แล้ว ·
        คืนเข้าสต๊อก <b class="num"><?php echo number_format($voided['qty']) ?></b> ชิ้น ·
        เหตุผล: <?php echo e($voided['void_reason']) ?>
      <?php } ?>
    </span>
  </div>
<?php } ?>

<?php if ($done !== null && empty($done['void'])) { ?>
  <div class="alert alert-ok" role="status">
    <svg class="ico"><use href="#i-check"/></svg>
    <span>
      ตัดออกเรียบร้อย เอกสาร <b><?php echo e($done['no']) ?></b> ·
      <?php echo e(issue_reason_label($done['reason'])) ?> ·
      <?php echo (int) $done['items'] ?> รายการ · <b class="num"><?php echo number_format($done['qty']) ?></b> ชิ้น ·
      มูลค่าต้นทุน <?php echo money2($done['cost']) ?> บาท
    </span>
    <?php
    $dat = ' data-bill="' . e($done['no']) . '" data-qty="' . (int) $done['qty']
         . '" data-items="' . (int) $done['items'] . '"';
    ?>
    <div class="alert-act">
      <?php foreach ((can_void_doc($user, $done) ? array(
          array('edit', 'แก้ไขใบนี้',  'i-arrow', 'หมายเหตุการแก้ไขใบตัดออก'),
          array('void', 'ยกเลิกใบนี้', 'i-ban',   'หมายเหตุการยกเลิกใบตัดออก'),
      ) : array()) as $b) { ?>
        <form method="post" action="issue.php" data-confirm="<?php echo e($b[0]) ?>"<?php echo $dat ?>>
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

<form method="post" action="issue.php" id="issue-form" data-confirm="issue">
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
  <input type="hidden" name="act" value="save">

  <!-- ===== หัวเอกสาร ===== -->
  <section class="card recv-head">
    <div class="card-head">
      <div>
        <h2>ข้อมูลการตัดออก</h2>
        <span class="sub">ของที่ออกโดยไม่ได้ขายต้องมีเหตุผลเสมอ ยอดขาดหายจะได้ตรวจย้อนหลังได้</span>
      </div>
    </div>

    <div class="why-wrap">
      <span class="why-lb" id="why-lb">เหตุผล <i class="req">จำเป็น</i></span>
      <div class="why" role="radiogroup" aria-labelledby="why-lb">
        <?php foreach (issue_reasons() as $k => $r) { ?>
          <label class="why-o">
            <input type="radio" name="why" value="<?php echo e($k) ?>"
                   data-note="<?php echo $r['note'] ? '1' : '0' ?>" <?php echo $old['reason'] === $k ? 'checked' : '' ?>>
            <span><b><?php echo e($r['label']) ?></b><small><?php echo e($r['hint']) ?></small></span>
          </label>
        <?php } ?>
      </div>
    </div>

    <div class="recv-fields">
      <div class="field">
        <label for="ref">ผู้ขอเบิก / เลขที่อ้างอิง</label>
        <input class="input" type="text" id="ref" name="ref" autocomplete="off"
               value="<?php echo e($old['ref']) ?>" placeholder="เช่น ใช้เป็นตัวโชว์หน้าร้าน หรือเลขใบเคลม">
      </div>

      <div class="field">
        <label>ผู้ทำรายการ</label>
        <div class="who-box">
          <span class="av"><?php echo e($user['initials']) ?></span>
          <div>
            <b><?php echo e($user['name']) ?></b>
            <small><?php echo e(role_name($user['role'])) ?> · <?php echo e(branch_name($code)) ?></small>
          </div>
        </div>
      </div>

      <div class="field">
        <label for="note">หมายเหตุ <i class="req" id="note-req" hidden>จำเป็นสำหรับเหตุผลนี้</i></label>
        <input class="input" type="text" id="note" name="note" autocomplete="off"
               value="<?php echo e($old['note']) ?>" placeholder="เช่น ฟิล์มกระจกแตกตอนติดให้ลูกค้า 2 แผ่น">
      </div>
    </div>
  </section>

  <?php require dirname(__FILE__) . '/inc/issue-live.php'; ?>

  <!-- ===== แถบสรุปติดขอบล่าง ===== -->
  <div class="recv-bar issue-bar" id="issue-bar">
    <div class="rb-t">
      <b id="ib-items">0</b> รายการ ·
      <b class="num" id="ib-qty">0</b> ชิ้น
      <small id="ib-hint">เลือกสินค้าเข้าใบก่อน</small>
    </div>
    <button class="btn-xl btn-out" type="submit" id="ib-go" disabled>
      <svg class="ico"><use href="#i-out"/></svg> บันทึกการตัดออก
    </button>
  </div>
</form>

<script>
(function () {
  var items  = document.getElementById('ib-items');
  var qtyEl  = document.getElementById('ib-qty');
  var hint   = document.getElementById('ib-hint');
  var go     = document.getElementById('ib-go');
  var bar    = document.getElementById('issue-bar');
  var noteEl = document.getElementById('note');
  var noteRq = document.getElementById('note-req');
  var form   = document.getElementById('issue-form');

  function num(v) { return Number(String(v).replace(/[^0-9]/g, '')) || 0; }
  function picked() { return form.querySelector('input[name="why"]:checked'); }

  window.issueSync = function () {
    var sumEl = document.getElementById('issue-sum');
    var n = document.querySelectorAll('#issue-live .recv-list li').length;
    var q = sumEl ? num(sumEl.getAttribute('data-v')) : 0;
    var w = picked();
    var needNote = !!(w && w.getAttribute('data-note') === '1');
    var hasNote  = noteEl.value.replace(/\s/g, '') !== '';

    noteRq.hidden = !needNote;
    items.textContent = n.toLocaleString('th-TH');
    qtyEl.textContent = q.toLocaleString('th-TH');

    go.disabled   = (n === 0 || !w || (needNote && !hasNote));
    bar.className = go.disabled ? 'recv-bar issue-bar' : 'recv-bar issue-bar on';

    if (n === 0)                    { hint.textContent = 'เลือกสินค้าเข้าใบก่อน'; }
    else if (!w)                    { hint.textContent = 'ยังไม่ได้เลือกเหตุผลของการตัดออก'; }
    else if (needNote && !hasNote)  { hint.textContent = 'เหตุผลนี้ต้องกรอกหมายเหตุด้วย'; }
    else                            { hint.textContent = 'ตรวจอีกครั้งก่อนกดบันทึก — สต๊อกจะลดทันที'; }
  };

  noteEl.addEventListener('input', window.issueSync);
  form.addEventListener('change', function (ev) {
    if (ev.target && ev.target.name === 'why') { window.issueSync(); }
  });
  document.body.addEventListener('htmx:afterSettle', function () { window.issueSync(); });
  window.issueSync();

  /* ข้อมูลให้กล่องยืนยันไปสรุป */
  form.issueSummary = function () {
    var out = [];
    var rows = document.querySelectorAll('#issue-live .recv-list li');
    for (var i = 0; i < rows.length; i++) {
      out.push({
        name: rows[i].querySelector('.cl-t b').textContent,
        qty:  num(rows[i].querySelector('.rq-set').value),
        unit: rows[i].querySelector('.cl-s').textContent.trim()
      });
    }
    var w = picked();
    var cost = document.getElementById('issue-cost');
    return {
      lines:  out,
      reason: w ? w.parentNode.querySelector('b').textContent : '—',
      cost:   cost ? cost.textContent.trim() : ''
    };
  };
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
