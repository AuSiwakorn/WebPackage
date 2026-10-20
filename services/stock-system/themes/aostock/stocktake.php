<?php
/**
 * FILE: themes/aostock/stocktake.php
 * ROLE: ตรวจนับสต๊อก (แบบเบา)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/adjust-live.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_count, ao_stock_count_item, ao_stock_move, ao_stock_balance, ao_stock_doc_seq, ao_stock_log (ผ่าน api.php) · ใบที่กำลังทำ (ร่าง) อยู่ใน $_SESSION['adj_draft']
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ใบที่บันทึกแล้วเก็บในตาราง ao_stock_* (ช่วงที่ 6) · ยอดขยับระหว่างกดบันทึก แสดงข้อความให้นับใหม่แทนหน้า error
 *   - [x] ช่วงที่ 11: แก้ / ยกเลิกใบของตัวเองต้องมีสิทธิ์ doc_fix · ปุ่มแก้ / ยกเลิกและลิงก์ยอดคงเหลือแสดงตามสิทธิ์
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 *   - [x] 8 ต.ค.: ยังกรอกไม่ครบแล้วกดบันทึก → เลื่อนไปช่องแรกที่ขาด กะพริบ + ข้อความ (data-need · aoNeedSync ใน inc/footer.php)
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 3): htmx แตะสินค้า / ปุ่มในใบ ส่งกลับแค่ผลการนับ + การ์ดที่แตะ ($LIVE_PART / $OOB_SKUS) · ใบเต็ม ↔ ไม่เต็ม ล็อกการ์ดในเบราว์เซอร์
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 4): การ์ดครั้งละ 60 ใบ — htmx ?from= ส่งกลับแค่ชุดถัดไป
 *   - [x] 14 ต.ค.: ปุ่มบันทึกใบ data-qty-guard — กด + / − แล้วกดบันทึกทันที ระบบส่งจำนวนที่ค้างก่อน (inc/footer.php)
 *   - [x] 15 ต.ค. (ใบนับแบบรายการ ช่วง 1): act=set ใช้ Stock_setCountDraft (ว่าง = เอาออก) · ไม่จำกัดครั้งละ N ตอนร้านเปิดแล้ว
 *   - [x] 15 ต.ค. (ใบนับแบบรายการ ช่วง 5): เปลี่ยนชื่อ “ตรวจนับ / ปรับยอด” เป็น “ตรวจนับสต๊อก” · หัวหน้า / ปุ่ม / ข้อความหลังบันทึก
 *   - [x] 15 ต.ค. (ผู้ใช้เลือก): ลองใบนับแบบตาราง (ช่วง 2–3) แล้วกลับมาใช้การ์ด · เปิดหน้ามาที่แท็บ “ทั้งหมด”
 *         · Enter ในช่องจำนวน = ใช้ค่านี้ (ช่องไม่ผูกกับฟอร์มบันทึกแล้ว)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ตรวจนับสต๊อก (แบบเบา)
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
$tab    = isset($_GET['t'])   ? trim($_GET['t'])   : 'all';
$err    = '';
$done   = null;
$voided = null;
$old    = adraft_meta();
/* htmx ส่งกลับส่วนไหน (งานความเร็ว ช่วง 3): แตะสินค้า / ปุ่มในใบ = แค่ผลการนับ + การ์ดที่แตะ · ค้นหา / แท็บ / หมวด / แก้ไข / ยกเลิก = ทั้งส่วน */
$LIVE_PART = 'all';
$OOB_SKUS  = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่';
    } else {
        $act = isset($_POST['act']) ? $_POST['act'] : '';
        $sku = (isset($_POST['sku']) && is_string($_POST['sku'])) ? trim($_POST['sku']) : '';
        if (in_array($act, array('pick', 'plus', 'minus', 'set', 'del'), true)) {
            $LIVE_PART = 'cart';
            $OOB_SKUS  = array($sku);
        } elseif ($act === 'clear') {
            $LIVE_PART = 'cart';
            $OOB_SKUS  = array_keys(isset($_SESSION['adj_draft']) && is_array($_SESSION['adj_draft']) ? $_SESSION['adj_draft'] : array());
        }

        if ($act === 'pick') {
            adraft_pick($code, $sku);
        } elseif ($act === 'plus') {
            adraft_step($sku, 1);
        } elseif ($act === 'minus') {
            adraft_step($sku, -1);
        } elseif ($act === 'set') {
            $k = 'aq_' . $sku;                       // ว่าง = เอาออกจากใบ · ยอดที่จดไว้ตอนแตะการ์ดยังเป็นตัวเทียบตอนบันทึก
            Stock_setCountDraft($code, $sku, isset($_POST[$k]) ? $_POST[$k] : '', isset($_POST['seen']) ? $_POST['seen'] : null);
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
                $err = ($chk['by_user'] === $user['username'])
                     ? 'ไม่มีสิทธิ์ “แก้เอกสารคลังตัวเอง” — ติดต่อผู้ดูแลเพื่อเปิดสิทธิ์'
                     : 'ใบตรวจนับนี้เป็นของพนักงานคนอื่น — ต้องมีสิทธิ์แก้งานคนอื่นจึงจะแก้หรือยกเลิกได้';
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
                if (isset($done['error'])) {
                    $err  = $done['error'];
                    $done = null;
                } else {
                    adraft_clear();
                    header('Location: ' . url('stocktake.php?done=' . rawurlencode($done['no'])));
                    exit;
                }
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

/* กด “แสดงเพิ่ม” (?from=) → แค่การ์ดชุดถัดไป (ช่วง 4) */
if ($isHx && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['from'])) {
    $LIVE_PART = 'more';
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
$PAGE_TITLE = 'ตรวจนับสต๊อก';
$PAGE_SUB   = branch_name($code) . ' · นับของจริงเทียบกับยอดในระบบ';
$NAV_ACTIVE = 'stocktake.php';
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
        แก้จำนวนที่นับได้แล้วบันทึกใหม่ ระบบจะออกเลขใบใหม่ให้
      <?php } else { ?>
        ยกเลิกใบตรวจนับ <b><?php echo e($voided['no']) ?></b> แล้ว · ยอดทุกรายการกลับเป็นก่อนตรวจนับ ·
        เหตุผล: <?php echo e($voided['void_reason']) ?>
      <?php } ?>
    </span>
  </div>
<?php } ?>

<?php if ($done !== null && empty($done['void'])) { ?>
  <?php $s = $done['sum']; $net = $s['plus'] - $s['minus']; ?>
  <div class="alert alert-ok" role="status">
    <svg class="ico"><use href="#i-check"/></svg>
    <span>
      <?php if ($s['over'] + $s['short'] === 0) { ?>
        บันทึกแล้ว เอกสาร <b><?php echo e($done['no']) ?></b> · นับ <?php echo (int) $s['items'] ?> รายการ <b>ตรงกับระบบทั้งหมด</b>
      <?php } else { ?>
        บันทึกผลการนับแล้ว เอกสาร <b><?php echo e($done['no']) ?></b> ·
        นับ <?php echo (int) $s['items'] ?> รายการ (ตรง <?php echo $s['same'] ?> · เกิน <?php echo $s['over'] ?> · ขาด <?php echo $s['short'] ?>) ·
        สุทธิ <b class="num"><?php echo $net >= 0 ? '+' : '−' ?><?php echo number_format(abs($net)) ?></b> ชิ้น ·
        <?php echo $s['value'] < 0 ? '−' : '' ?><?php echo money2(abs($s['value'])) ?> บาท
      <?php } ?>
    </span>
    <?php $dat = ' data-bill="' . e($done['no']) . '" data-items="' . (int) $done['items'] . '"'; ?>
    <div class="alert-act">
      <?php foreach ((can_void_doc($user, $done) ? array(
          array('edit', 'แก้ไขใบนี้',  'i-arrow', 'หมายเหตุการแก้ไขใบตรวจนับ'),
          array('void', 'ยกเลิกใบนี้', 'i-ban',   'หมายเหตุการยกเลิกใบตรวจนับ'),
      ) : array()) as $b) { ?>
        <form method="post" action="stocktake.php" data-confirm="<?php echo e($b[0]) ?>"<?php echo $dat ?>>
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

<form method="post" action="stocktake.php" id="adj-form" data-confirm="adjust">
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
  <input type="hidden" name="act" value="save">

  <?php $LIVE_PART = 'all'; /* หน้าเต็มวาดทั้งส่วนเสมอ */ require dirname(__FILE__) . '/inc/adjust-live.php'; ?>


  <!-- ===== แถบสรุปติดขอบล่าง ===== -->
  <div class="recv-bar adj-bar" id="adj-bar">
    <div class="rb-t">
      นับแล้ว <b id="ab-items">0</b> รายการ
      <span id="ab-diff" class="ab-diff"></span>
      <small id="ab-hint">แตะสินค้าที่ต้องการนับ</small>
    </div>
    <button class="btn-xl btn-in" type="submit" id="ab-go" data-need data-qty-guard="save" aria-disabled="true">
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

  var lack   = '';                    // ช่องแรกที่ยังขาด — ให้ปุ่มบันทึกพาไป (aoNeedSync ใน inc/footer.php)

  function picked() { return form.querySelector('input[name="why"]:checked'); }
  function attr(el, k) { return el ? Number(el.getAttribute(k)) || 0 : 0; }

  go.aoNeed = function () {
    if (lack === 'pick') {
      return { box: document.querySelector('#adj-live .sale-pick'), field: document.getElementById('aq'), mouseOnly: true,
               msg: 'แตะสินค้าที่ต้องการนับ แล้วกรอกจำนวนที่นับได้จริง' };
    }
    if (lack === 'why') {
      var g = why.querySelector('.why');
      return { box: g, field: g.querySelector('input[name="why"]'), after: g, msg: 'มีรายการที่ยอดไม่ตรง — เลือกสาเหตุก่อนบันทึก' };
    }
    if (lack === 'note') { return { box: noteEl, field: noteEl, after: noteEl, msg: 'สาเหตุนี้ต้องกรอกหมายเหตุด้วย' }; }
    return null;
  };

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

    lack = (n === 0) ? 'pick' : (bad === 0) ? '' : !w ? 'why' : (needNote && !hasNote) ? 'note' : '';
    if (window.aoNeedSync) { window.aoNeedSync(go); }
    goT.textContent = (n > 0 && bad === 0) ? 'ยืนยัน นับแล้วตรง' : 'บันทึกผลการนับ';
    bar.className = lack ? 'recv-bar adj-bar' : 'recv-bar adj-bar on';

    if (n === 0)                         { hint.textContent = 'แตะสินค้าที่ต้องการนับ แล้วกรอกจำนวนที่นับได้จริง'; }
    else if (bad === 0)                  { hint.textContent = 'ทุกรายการตรงกับระบบ บันทึกไว้เป็นหลักฐานว่านับแล้ว'; }
    else if (!w)                         { hint.innerHTML = 'มีรายการไม่ตรง — <a href="#adj-why" class="ab-jump">เลือกสาเหตุ</a> ก่อน'; }
    else if (needNote && !hasNote)       { hint.textContent = 'สาเหตุนี้ต้องกรอกหมายเหตุด้วย'; }
    else                                 { hint.textContent = 'บันทึกแล้วยอดในระบบจะเท่ากับที่นับได้'; }
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
  /* Enter ในช่องจำนวน = ใช้ค่านี้ (ช่องไม่ผูกกับฟอร์มบันทึก กด Enter จึงไม่ส่งอะไรเอง) */
  document.addEventListener('keydown', function (ev) {
    var i = ev.target;
    if (ev.key === 'Enter' && i && i.matches && i.matches('#adj-cart .adj-list input[data-qty]')) {
      ev.preventDefault();
      i.blur();
    }
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
