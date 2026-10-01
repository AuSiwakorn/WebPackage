<?php
/* ==========================================================
   AOSTOCK DEMO — ตรวจนับ / ปรับยอด (แบบเบา)
   ----------------------------------------------------------
   แตะสินค้า → กรอกจำนวนที่นับได้จริง → ระบบบอกส่วนต่าง → บันทึก
   - ตรงทุกรายการ = บันทึกเป็น "ตรวจนับแล้วตรง" ไม่ต้องใส่สาเหตุ
   - มีรายการไม่ตรง = ต้องเลือกสาเหตุ (ของหาย / อื่น ๆ ต้องกรอกหมายเหตุ)
   งานคลัง ไม่ต้องเปิดร้านก่อน
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();
$code = work_branch($user);          // ผู้ดูแลเลือกสาขาได้จากแถบบน

$isHx   = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
$q      = isset($_GET['q'])   ? trim($_GET['q'])   : '';
$cat    = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$tab    = isset($_GET['t'])   ? trim($_GET['t'])   : 'todo';
$err    = '';
$done   = null;
$voided = null;
$old    = adraft_meta();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่';
    } else {
        $act = isset($_POST['act']) ? $_POST['act'] : '';
        $sku = isset($_POST['sku']) ? trim($_POST['sku']) : '';

        if ($act === 'pick') {
            adraft_pick($code, $sku);
        } elseif ($act === 'plus') {
            adraft_step($sku, 1);
        } elseif ($act === 'minus') {
            adraft_step($sku, -1);
        } elseif ($act === 'set') {
            $k = 'aq_' . $sku;
            adraft_set($sku, isset($_POST[$k]) ? preg_replace('/\D/', '', $_POST[$k]) : 0);
        } elseif ($act === 'del') {
            adraft_remove($sku);
        } elseif ($act === 'clear') {
            adraft_clear();
        } elseif ($act === 'void' || $act === 'edit') {
            $no     = isset($_POST['no']) ? trim($_POST['no']) : '';
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            $redo   = ($act === 'edit');

            $chk    = adj_by_no($code, $no);

            if ($chk !== null && !can_void_doc($user, $chk)) {
                $err = 'ใบตรวจนับนี้เป็นของพนักงานคนอื่น — ต้องมีสิทธิ์แก้งานคนอื่นจึงจะแก้หรือยกเลิกได้';
            } elseif ($reason === '') {
                $err = 'การยกเลิกหรือแก้ไขใบตรวจนับต้องระบุหมายเหตุทุกครั้ง';
            } elseif ($redo && adraft_size() > 0) {
                $err = 'ยังมีใบที่ทำค้างอยู่ บันทึกหรือล้างใบนั้นก่อนจึงจะแก้ใบเก่าได้';
            } else {
                $v = adj_void($code, $no, $user, $reason, $redo);
                if ($v === null) {
                    $err = 'ไม่พบใบนี้ หรือถูกยกเลิกไปแล้ว';
                } elseif (isset($v['error'])) {
                    $err = 'ยกเลิกไม่ได้ เพราะของที่เคยปรับเพิ่มถูกขายออกไปแล้ว — '
                         . implode(' · ', $v['items']) . ' · ให้นับใหม่อีกรอบแทน';
                } else {
                    $voided = $v;
                    if ($redo) {
                        adraft_from_adj($v);
                    }
                }
            }
        } elseif ($act === 'save') {
            $why  = isset($_POST['why'])  ? trim($_POST['why'])  : '';
            $note = isset($_POST['note']) ? trim($_POST['note']) : '';
            $old  = array('reason' => $why, 'note' => $note);
            $_SESSION['adj_meta'] = $old;      // บันทึกไม่ผ่าน ค่าที่เลือกไว้ต้องไม่หาย

            $lines   = adraft_lines($code);
            $sum     = adj_sum($lines);
            $reasons = adj_reasons();
            $hasDiff = ($sum['over'] + $sum['short']) > 0;

            if (!$lines) {
                $err = 'ยังไม่ได้เลือกสินค้าที่นับสักรายการ';
            } elseif ($moved = adj_moved($code)) {
                $err = 'ยอดในระบบเปลี่ยนไประหว่างที่นับ (มีการขายหรือรับเข้าแทรก) — '
                     . implode(' · ', $moved) . ' · กรุณานับตัวนี้ใหม่แล้วกดบันทึกอีกครั้ง';
            } elseif ($hasDiff && !isset($reasons[$why])) {
                $err = 'มีรายการที่ยอดไม่ตรง กรุณาเลือกสาเหตุ';
            } elseif ($hasDiff && adj_reason_needs_note($why) && $note === '') {
                $err = 'กรณี “' . adj_reason_label($why) . '” ต้องกรอกหมายเหตุอธิบายทุกครั้ง';
            } else {
                $done = adj_save($code, $user, $hasDiff ? $why : '', $note, $lines);
                adraft_clear();
                header('Location: ' . url('stocktake.php?done=' . rawurlencode($done['no'])));
                exit;
            }
        }

        if (!$isHx && $err === '' && $act !== 'save') {
            if ($voided !== null) {
                header('Location: ' . url('stocktake.php?voided=' . rawurlencode($voided['no'])));
                exit;
            }
            header('Location: ' . url('stocktake.php' . adj_qs($q, $cat, $tab)));
            exit;
        }
    }
}

if ($isHx) {
    require dirname(__FILE__) . '/inc/adjust-live.php';
    exit;
}

if (isset($_GET['done'])) {
    $done = adj_by_no($code, $_GET['done']);
}
if ($voided === null && isset($_GET['voided'])) {
    $b = adj_by_no($code, $_GET['voided']);
    if ($b !== null && !empty($b['void'])) {
        $voided = $b;
    }
}
if ($voided !== null && isset($voided['void_mode']) && $voided['void_mode'] === 'edit') {
    $old = adraft_meta();
}

$branch     = $code;
$PAGE_TITLE = 'ตรวจนับ / ปรับยอด';
$PAGE_SUB   = branch_name($code) . ' · ของจริงไม่ตรงกับระบบ ปรับให้ตรงที่นี่';
$NAV_ACTIVE = 'stocktake.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($err !== ''): ?>
  <div class="alert alert-error" role="alert">
    <svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span>
  </div>
<?php endif; ?>

<?php if ($voided !== null): ?>
  <?php $isEdit = (isset($voided['void_mode']) && $voided['void_mode'] === 'edit'); ?>
  <div class="alert <?= $isEdit ? 'alert-info' : 'alert-warn' ?>" role="status">
    <svg class="ico"><use href="#<?= $isEdit ? 'i-arrow' : 'i-ban' ?>"/></svg>
    <span>
      <?php if ($isEdit): ?>
        ยกเลิกใบ <b><?= e($voided['no']) ?></b> และดึง <?= (int) $voided['items'] ?> รายการกลับเข้าใบให้แล้ว —
        แก้จำนวนที่นับได้แล้วบันทึกใหม่ ระบบจะออกเลขใบใหม่ให้
      <?php else: ?>
        ยกเลิกใบตรวจนับ <b><?= e($voided['no']) ?></b> แล้ว · ยอดทุกรายการกลับเป็นก่อนตรวจนับ ·
        เหตุผล: <?= e($voided['void_reason']) ?>
      <?php endif; ?>
    </span>
  </div>
<?php endif; ?>

<?php if ($done !== null && empty($done['void'])): ?>
  <?php $s = $done['sum']; $net = $s['plus'] - $s['minus']; ?>
  <div class="alert alert-ok" role="status">
    <svg class="ico"><use href="#i-check"/></svg>
    <span>
      <?php if ($s['over'] + $s['short'] === 0): ?>
        บันทึกแล้ว เอกสาร <b><?= e($done['no']) ?></b> · นับ <?= (int) $s['items'] ?> รายการ <b>ตรงกับระบบทั้งหมด</b>
      <?php else: ?>
        ปรับยอดเรียบร้อย เอกสาร <b><?= e($done['no']) ?></b> ·
        นับ <?= (int) $s['items'] ?> รายการ (ตรง <?= $s['same'] ?> · เกิน <?= $s['over'] ?> · ขาด <?= $s['short'] ?>) ·
        สุทธิ <b class="num"><?= $net >= 0 ? '+' : '−' ?><?= number_format(abs($net)) ?></b> ชิ้น ·
        <?= $s['value'] < 0 ? '−' : '' ?><?= money2(abs($s['value'])) ?> บาท
      <?php endif; ?>
    </span>
    <?php $dat = ' data-bill="' . e($done['no']) . '" data-items="' . (int) $done['items'] . '"'; ?>
    <div class="alert-act">
      <?php foreach (array(
          array('edit', 'แก้ไขใบนี้',  'i-arrow', 'หมายเหตุการแก้ไขใบตรวจนับ'),
          array('void', 'ยกเลิกใบนี้', 'i-ban',   'หมายเหตุการยกเลิกใบตรวจนับ'),
      ) as $b): ?>
        <form method="post" action="stocktake.php" data-confirm="<?= e($b[0]) ?>"<?= $dat ?>>
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="act" value="<?= e($b[0]) ?>">
          <input type="hidden" name="no" value="<?= e($done['no']) ?>">
          <input class="reason-fb" type="text" name="reason" value=""
                 placeholder="หมายเหตุ (จำเป็น)" aria-label="<?= e($b[3]) ?>">
          <button class="btn btn-ghost btn-sm" type="submit">
            <svg class="ico"><use href="#<?= e($b[2]) ?>"/></svg> <?= e($b[1]) ?>
          </button>
        </form>
      <?php endforeach; ?>
      <a class="btn btn-ghost btn-sm" href="products.php">
        <svg class="ico"><use href="#i-boxes"/></svg> ดูยอดคงเหลือ
      </a>
    </div>
  </div>
<?php endif; ?>

<form method="post" action="stocktake.php" id="adj-form" data-confirm="adjust">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <input type="hidden" name="act" value="save">

  <?php require dirname(__FILE__) . '/inc/adjust-live.php'; ?>


  <!-- ===== แถบสรุปติดขอบล่าง ===== -->
  <div class="recv-bar adj-bar" id="adj-bar">
    <div class="rb-t">
      นับแล้ว <b id="ab-items">0</b> รายการ
      <span id="ab-diff" class="ab-diff"></span>
      <small id="ab-hint">แตะสินค้าที่ต้องการนับ</small>
    </div>
    <button class="btn-xl btn-in" type="submit" id="ab-go" disabled>
      <svg class="ico"><use href="#i-clipboard"/></svg> <span id="ab-go-t">บันทึกผลการนับ</span>
    </button>
  </div>
</form>

<script>
(function () {
  var form   = document.getElementById('adj-form');
  var why    = document.getElementById('adj-why');
  var noteEl = document.getElementById('note');
  var noteRq = document.getElementById('note-req');
  var go     = document.getElementById('ab-go');
  var goT    = document.getElementById('ab-go-t');
  var bar    = document.getElementById('adj-bar');
  var items  = document.getElementById('ab-items');
  var diffEl = document.getElementById('ab-diff');
  var hint   = document.getElementById('ab-hint');

  function picked() { return form.querySelector('input[name="why"]:checked'); }
  function attr(el, k) { return el ? Number(el.getAttribute(k)) || 0 : 0; }

  window.adjSync = function () {
    var s     = document.getElementById('adj-sum');
    var n     = attr(s, 'data-items');
    var over  = attr(s, 'data-over');
    var short = attr(s, 'data-short');
    var bad   = over + short;
    var w     = picked();
    var needNote = !!(w && w.getAttribute('data-note') === '1');
    var hasNote  = noteEl.value.replace(/\s/g, '') !== '';

    why.hidden    = (bad === 0);
    noteRq.hidden = !needNote;
    items.textContent = n.toLocaleString('th-TH');
    diffEl.innerHTML  = n === 0 ? ''
      : (bad === 0 ? '· <span class="dchip eq">ตรงทั้งหมด</span>'
                   : '· <span class="dchip up">เกิน ' + over + '</span> <span class="dchip dn">ขาด ' + short + '</span>');

    go.disabled = (n === 0 || (bad > 0 && (!w || (needNote && !hasNote))));
    goT.textContent = (n > 0 && bad === 0) ? 'ยืนยัน นับแล้วตรง' : 'บันทึกและปรับยอด';
    bar.className = go.disabled ? 'recv-bar adj-bar' : 'recv-bar adj-bar on';

    if (n === 0)                         { hint.textContent = 'แตะสินค้าที่ต้องการนับ แล้วกรอกจำนวนที่นับได้จริง'; }
    else if (bad === 0)                  { hint.textContent = 'ทุกรายการตรงกับระบบ บันทึกไว้เป็นหลักฐานว่านับแล้ว'; }
    else if (!w)                         { hint.innerHTML = 'มีรายการไม่ตรง — <a href="#adj-why" class="ab-jump">เลือกสาเหตุ</a> ก่อน'; }
    else if (needNote && !hasNote)       { hint.textContent = 'สาเหตุนี้ต้องกรอกหมายเหตุด้วย'; }
    else                                 { hint.textContent = 'ระบบจะปรับยอดในระบบให้เท่ากับที่นับได้'; }
  };

  noteEl.addEventListener('input', window.adjSync);
  /* ลิงก์ "เลือกสาเหตุ" ในแถบล่าง → เลื่อนไปที่กล่องสาเหตุแล้วกะพริบให้เห็น */
  hint.addEventListener('click', function (ev) {
    var a = ev.target.closest ? ev.target.closest('.ab-jump') : null;
    if (!a) { return; }
    ev.preventDefault();
    why.scrollIntoView({ behavior: 'smooth', block: 'center' });
    why.classList.remove('flash'); void why.offsetWidth; why.classList.add('flash');
  });
  form.addEventListener('change', function (ev) {
    if (ev.target && ev.target.name === 'why') { window.adjSync(); }
  });
  document.body.addEventListener('htmx:afterSettle', function () { window.adjSync(); });
  window.adjSync();

  /* ข้อมูลให้กล่องยืนยัน */
  form.adjSummary = function () {
    var rows = document.querySelectorAll('#adj-live .adj-list li');
    var out = [];
    for (var i = 0; i < rows.length; i++) {
      out.push({
        name: rows[i].querySelector('.cl-t b').textContent,
        have: rows[i].getAttribute('data-have'),
        cnt:  rows[i].querySelector('.rq-set').value,
        diff: Number(rows[i].getAttribute('data-diff')) || 0,
        unit: rows[i].querySelector('.cl-s').textContent.trim()
      });
    }
    var w = picked();
    var s = document.getElementById('adj-sum');
    return {
      lines:  out,
      reason: w ? w.parentNode.querySelector('b').textContent : '',
      value:  s ? s.getAttribute('data-value-t') : ''
    };
  };
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
