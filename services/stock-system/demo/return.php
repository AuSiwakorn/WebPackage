<?php
/* ==========================================================
   AOSTOCK DEMO — รับคืนสินค้า
   พนักงาน (สิทธิ์ refund): ค้นบิลเก่า → เลือกรายการที่คืน → เหตุผล → ยืนยันยอดเงินคืน (เงินสดจากลิ้นชักวันนี้)
   ผู้ดูแล: ตรวจสอบใบรับคืนทุกสาขาที่ adm-return.php — ไม่ได้ทำรับคืนเอง
   กติกาอยู่ใน include/function.php หมวด "รับคืนสินค้า"
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();

$code = work_branch($user);

$allowed = can($user, 'refund');
$isOpen  = store_is_open($code);
$q       = isset($_GET['q']) ? trim($_GET['q']) : '';
$billNo  = isset($_GET['bill']) ? trim($_GET['bill']) : '';
$err     = '';
$old     = array('why' => '', 'note' => '', 'refund' => '', 'refund_note' => '', 'qty' => array());

/* ---------- บันทึกการรับคืน ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $allowed) {
    $billNo = isset($_POST['bill']) ? trim($_POST['bill']) : '';
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่';
    } else {
        $bill = return_bill($code, $billNo);
        $qtys = array();
        if ($bill !== null) {
            foreach ($bill['lines'] as $l) {
                $k = 'q_' . $l['sku'];
                $qtys[$l['sku']] = isset($_POST[$k]) ? (int) preg_replace('/\D/', '', $_POST[$k]) : 0;
            }
        }
        $old = array(
            'why'         => isset($_POST['why']) ? $_POST['why'] : '',
            'note'        => isset($_POST['note']) ? trim($_POST['note']) : '',
            'refund'      => isset($_POST['refund']) ? trim($_POST['refund']) : '',
            'refund_note' => isset($_POST['refund_note']) ? trim($_POST['refund_note']) : '',
            'qty'         => $qtys,
        );

        if ($bill === null) {
            $err = 'ไม่พบบิลนี้ในสาขา';
        } else {
            $refund = preg_replace('/[^0-9.]/', '', $old['refund']);
            $r = return_save($code, $user, $bill, $qtys, $old['why'], $old['note'], $refund, $old['refund_note']);
            if (isset($r['error'])) {
                $err = $r['error'];
            } else {
                header('Location: ' . url('return.php?done=' . rawurlencode($r['doc']['no'])));
                exit;
            }
        }
    }
}

$done  = isset($_GET['done']) ? return_by_no($code, $_GET['done']) : null;
$bill  = $billNo !== '' ? return_bill($code, $billNo) : null;
$found = ($bill === null) ? return_find_bills($code, $q) : array();
$today = array_reverse(returns_today($code));

$branch     = $code;
$PAGE_TITLE = 'รับคืนสินค้า';
$PAGE_SUB   = branch_name($code) . ' · คืนได้ภายใน ' . backdate_days($code) . ' วันหลังวันที่ซื้อ';
$NAV_ACTIVE = 'return.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if (!$allowed): ?>
  <div class="alert alert-warn" role="status">
    <svg class="ico"><use href="#i-ban"/></svg>
    <span>คุณยังไม่มีสิทธิ์รับคืนสินค้า — ให้ผู้ดูแลติ๊กสิทธิ์ “รับคืนสินค้า” ให้ก่อน</span>
  </div>
  <?php require dirname(__FILE__) . '/inc/footer.php'; exit; ?>
<?php endif; ?>

<?php if (!$isOpen): ?>
  <div class="alert alert-info" role="status">
    <svg class="ico"><use href="#i-info"/></svg>
    <span>ร้านยังไม่เปิด — ค้นบิลได้ แต่บันทึกรับคืนไม่ได้ เพราะเงินคืนจ่ายเป็นเงินสดจากลิ้นชักของวันนี้</span>
    <div class="alert-act">
      <a class="btn btn-ghost btn-sm" href="store.php"><svg class="ico"><use href="#i-store"/></svg> ไปเปิดร้าน</a>
    </div>
  </div>
<?php endif; ?>

<?php if ($err !== ''): ?>
  <div class="alert alert-error" role="alert">
    <svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span>
  </div>
<?php endif; ?>

<?php if ($done !== null): ?>
  <div class="alert alert-ok" role="status">
    <svg class="ico"><use href="#i-check"/></svg>
    <span>
      รับคืนเรียบร้อย เอกสาร <b><?= e($done['no']) ?></b> (บิล <?= e($done['bill_no']) ?>) ·
      <?= (int) $done['items'] ?> รายการ · <?= number_format($done['qty']) ?> ชิ้น ·
      <?= $done['restock'] ? 'กลับเข้าสต๊อกแล้ว' : 'ไม่เข้าสต๊อก — แยกเก็บไว้' ?> ·
      คืนเงินสด <b class="num"><?= e(money2($done['refund'])) ?></b> บาท
    </span>
  </div>
<?php endif; ?>

<?php if ($bill === null): ?>

  <!-- ==================== ค้นบิล ==================== -->
  <section class="card">
    <div class="card-head">
      <div>
        <h2>ค้นหาบิลที่ลูกค้าจะคืน</h2>
        <span class="sub">ค้นจากเลขบิล ชื่อสินค้า หรือ SKU · แสดงย้อนหลัง <?= return_lookback_days() ?> วัน</span>
      </div>
    </div>
    <form class="ret-find" method="get" action="return.php">
      <div class="find-in">
        <svg class="ico"><use href="#i-search"/></svg>
        <label class="sr-only" for="rq">ค้นหาบิล</label>
        <input class="input" type="search" id="rq" name="q" value="<?= e($q) ?>"
               placeholder="เช่น RS2026-09-0012 หรือ ฟิล์ม" autocomplete="off">
      </div>
      <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-search"/></svg> ค้นหา</button>
    </form>

    <?php if (!$found): ?>
      <p class="empty"><svg class="ico"><use href="#i-receipt"/></svg>ไม่พบบิลที่ตรงกับคำค้น</p>
    <?php else: ?>
      <div class="tbl-wrap">
        <table class="tbl num">
          <thead>
            <tr><th>เลขบิล</th><th>วันที่ขาย</th><th>รายการ</th><th class="r">ยอด</th><th>สถานะการคืน</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($found as $b):
                $st = bill_return_status($b, $user);
                $names = array();
                foreach ($b['lines'] as $l) { $names[] = $l['name'] . ' ×' . $l['qty']; } ?>
              <tr class="<?= $st['ok'] ? '' : 'off' ?>">
                <td class="doc" data-label="เลขบิล"><?= e($b['no']) ?><small><?= e($b['by']) ?></small></td>
                <td data-label="วันที่ขาย">
                  <?= bill_age_days($b) === 0 ? 'วันนี้' : e(thai_day_month(strtotime($b['date']))) ?>
                  <small><?= e($b['time']) ?> น. · <?= $b['method'] === 'cash' ? 'เงินสด' : 'โอน / พร้อมเพย์' ?></small>
                </td>
                <td data-label="รายการ"><span class="ret-names"><?= e(implode(' · ', $names)) ?></span></td>
                <td class="r" data-label="ยอด"><?= e(money2($b['total'])) ?></td>
                <td data-label="สถานะ">
                  <?php $bc = array('ok' => 'bdg-ok', 'late' => 'bdg-out', 'done' => 'bdg-adj'); ?>
                  <span class="bdg <?= $bc[$st['code']] ?>"><?= e($st['code'] === 'late' ? 'เกินกำหนด' : $st['msg']) ?></span>
                  <?php if (bill_has_returns($b['no'], $b)): ?><small>มีการคืนไปแล้วบางส่วน</small><?php endif; ?>
                </td>
                <td class="r">
                  <?php if ($st['ok']): ?>
                    <a class="btn btn-ghost btn-sm" href="return.php?bill=<?= e(rawurlencode($b['no'])) ?>">เลือกบิลนี้</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
    <div class="card-foot">บิลที่เก่ากว่า <?= backdate_days($code) ?> วันคืนไม่ได้ · จำนวนวันผู้ดูแลตั้งได้ที่หน้าจัดการสาขา</div>
  </section>

<?php else: ?>

  <?php $st = bill_return_status($bill, $user); $lines = return_lines($bill); ?>

  <!-- ==================== บิลที่เลือก ==================== -->
  <section class="card">
    <div class="card-head">
      <div>
        <h2>บิล <?= e($bill['no']) ?></h2>
        <span class="sub">
          ขายเมื่อ <?= e(thai_date_full(strtotime($bill['date']))) ?> <?= e($bill['time']) ?> น. โดย <?= e($bill['by']) ?> ·
          <?= $bill['method'] === 'cash' ? 'เงินสด' : 'โอน / พร้อมเพย์' ?> · ยอด <?= e(money2($bill['total'])) ?> บาท
        </span>
      </div>
      <a class="btn btn-ghost btn-sm" href="return.php"><svg class="ico"><use href="#i-search"/></svg> เลือกบิลอื่น</a>
    </div>
    <div class="ret-status">
      <span class="bdg <?= $st['code'] === 'ok' ? 'bdg-ok' : 'bdg-out' ?>"><?= e($st['msg']) ?></span>
      <small>ผ่านมา <?= bill_age_days($bill) ?> วัน · กำหนดคืน <?= backdate_days($code) ?> วัน</small>
    </div>
  </section>

  <?php if ($st['ok']): ?>
  <form method="post" action="return.php?bill=<?= e(rawurlencode($bill['no'])) ?>" id="ret-form">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="bill" value="<?= e($bill['no']) ?>">

    <section class="card">
      <div class="card-head"><div><h2>รายการที่คืน</h2><span class="sub">ใส่จำนวนเฉพาะรายการที่ลูกค้าคืน · คืนบางชิ้นได้</span></div></div>
      <div class="tbl-wrap">
        <table class="tbl num ret-tbl">
          <thead><tr><th>สินค้า</th><th class="r">ราคาที่ขาย</th><th class="r">ซื้อ</th><th class="r">คืนไปแล้ว</th><th class="r">จำนวนที่คืน</th></tr></thead>
          <tbody>
            <?php foreach ($lines as $l):
                $v = isset($old['qty'][$l['sku']]) ? (int) $old['qty'][$l['sku']] : 0; ?>
              <tr class="<?= $l['remain'] === 0 ? 'off' : '' ?>">
                <td data-label="สินค้า"><b><?= e($l['name']) ?></b><small><?= e($l['sku']) ?></small></td>
                <td class="r" data-label="ราคาที่ขาย"><?= e(money2($l['price'])) ?></td>
                <td class="r" data-label="ซื้อ"><?= (int) $l['qty'] ?> <?= e($l['unit']) ?></td>
                <td class="r" data-label="คืนไปแล้ว"><?= $l['back'] ? (int) $l['back'] : '—' ?></td>
                <td class="r" data-label="จำนวนที่คืน">
                  <?php if ($l['remain'] > 0): ?>
                    <input class="input ret-q" type="number" name="q_<?= e($l['sku']) ?>" value="<?= $v ?>"
                           min="0" max="<?= (int) $l['remain'] ?>" step="1" inputmode="numeric"
                           data-price="<?= e($l['price']) ?>" aria-label="จำนวนที่คืน <?= e($l['name']) ?>">
                    <small>คืนได้อีก <?= (int) $l['remain'] ?></small>
                  <?php else: ?>
                    <small>คืนครบแล้ว</small>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

    <section class="card recv-head">
      <div class="card-head"><div><h2>เหตุผลและเงินคืน</h2><span class="sub">เหตุผลเป็นตัวตัดสินว่าของกลับเข้าสต๊อกหรือไม่</span></div></div>

      <div class="why-wrap">
        <span class="why-lb" id="why-lb">เหตุผลการคืน <i class="req">จำเป็น</i></span>
        <div class="why" role="radiogroup" aria-labelledby="why-lb">
          <?php foreach (return_reasons() as $k => $r): ?>
            <label class="why-o">
              <input type="radio" name="why" value="<?= e($k) ?>" data-note="<?= $r['note'] ? '1' : '0' ?>"
                     data-restock="<?= $r['restock'] ? '1' : '0' ?>" <?= $old['why'] === $k ? 'checked' : '' ?>>
              <span><b><?= e($r['label']) ?></b><small><?= e($r['hint']) ?></small></span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="recv-fields">
        <div class="field">
          <label for="note">รายละเอียด <i class="req" id="note-req" hidden>จำเป็นสำหรับเหตุผลนี้</i></label>
          <input class="input" type="text" id="note" name="note" value="<?= e($old['note']) ?>" autocomplete="off"
                 placeholder="เช่น ลูกค้าซื้อเคสผิดรุ่น ต้องการ iPhone 15 แทน 15 Pro">
        </div>

        <div class="field">
          <label>ผู้รับคืน</label>
          <div class="who-box">
            <span class="av"><?= e($user['initials']) ?></span>
            <div><b><?= e($user['name']) ?></b><small><?= e(role_name($user['role'])) ?> · <?= e(branch_name($code)) ?></small></div>
          </div>
        </div>

        <div class="field">
          <label for="refund">ยอดเงินคืน (เงินสดจากลิ้นชัก)</label>
          <input class="input cash" type="text" id="refund" name="refund" inputmode="decimal" autocomplete="off"
                 value="<?= e($old['refund']) ?>" placeholder="0.00">
          <small class="ret-calc">ตามราคาที่ขาย <b id="calc" class="num">0.00</b> บาท · แก้ได้แต่ไม่เกินยอดนี้</small>
        </div>

        <div class="field" id="rn-wrap" hidden>
          <label for="refund_note">เหตุผลที่ปรับยอดเงินคืน <i class="req">จำเป็น</i></label>
          <input class="input" type="text" id="refund_note" name="refund_note" value="<?= e($old['refund_note']) ?>"
                 autocomplete="off" placeholder="เช่น หักค่ากล่องแกะแล้ว 50 บาท">
        </div>
      </div>

      <p class="ret-stock" id="ret-stock" hidden></p>

      <div class="ret-go">
        <button class="btn btn-primary btn-xl" type="submit" id="ret-go" <?= $isOpen ? '' : 'disabled' ?>>
          <svg class="ico"><use href="#i-coin"/></svg> บันทึกรับคืนและคืนเงิน
        </button>
        <?php if (!$isOpen): ?><small>ต้องเปิดร้านก่อนจึงบันทึกได้</small><?php endif; ?>
      </div>
    </section>
  </form>
  <?php endif; ?>

<?php endif; ?>

<?php if ($today): ?>
  <!-- ==================== รับคืนวันนี้ ==================== -->
  <section class="card">
    <div class="card-head"><div><h2>รับคืนวันนี้</h2><span class="sub"><?= count($today) ?> ใบ · เงินสดออกจากลิ้นชักรวม <?= e(money2(store_refunds($code))) ?> บาท</span></div></div>
    <div class="tbl-wrap">
      <table class="tbl num">
        <thead><tr><th>ใบรับคืน</th><th>บิลเดิม</th><th>เหตุผล</th><th>สต๊อก</th><th class="r">คืนเงิน</th></tr></thead>
        <tbody>
          <?php foreach ($today as $r): ?>
            <tr>
              <td class="doc" data-label="ใบรับคืน"><?= e($r['no']) ?><small><?= e($r['time']) ?> น. · รับคืนโดย <?= e($r['by']) ?></small></td>
              <td data-label="บิลเดิม"><?= e($r['bill_no']) ?><small>ขายโดย <?= e($r['bill_by']) ?></small></td>
              <td data-label="เหตุผล"><?= e(return_reason_label($r['reason'])) ?><?php if ($r['note'] !== ''): ?><small><?= e($r['note']) ?></small><?php endif; ?></td>
              <td data-label="สต๊อก"><span class="bdg <?= $r['restock'] ? 'bdg-in' : 'bdg-adj' ?>"><?= $r['restock'] ? '+' . (int) $r['qty'] . ' เข้าสต๊อก' : 'แยกเก็บ' ?></span></td>
              <td class="r" data-label="คืนเงิน"><?= e(money2($r['refund'])) ?><?php if ($r['refund_note'] !== ''): ?><small>ปรับจาก <?= e(money2($r['calc'])) ?> · <?= e($r['refund_note']) ?></small><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endif; ?>

<script>
(function () {
  var form = document.getElementById('ret-form');
  if (!form) { return; }
  var qs     = form.querySelectorAll('.ret-q');
  var calcEl = document.getElementById('calc');
  var refund = document.getElementById('refund');
  var rnWrap = document.getElementById('rn-wrap');
  var rn     = document.getElementById('refund_note');
  var noteRq = document.getElementById('note-req');
  var note   = document.getElementById('note');
  var stock  = document.getElementById('ret-stock');
  var go     = document.getElementById('ret-go');
  var touched = refund.value !== '';

  function fmt(n) { return n.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
  function num(v) { return Number(String(v).replace(/[^0-9.]/g, '')) || 0; }

  function sync() {
    var calc = 0, pcs = 0;
    for (var i = 0; i < qs.length; i++) {
      var max = Number(qs[i].getAttribute('max'));
      var q   = Math.max(0, Math.min(max, Math.floor(num(qs[i].value))));
      calc += q * Number(qs[i].getAttribute('data-price'));
      pcs  += q;
    }
    calcEl.textContent = fmt(calc);
    if (!touched) { refund.value = calc ? calc.toFixed(2) : ''; }
    var diff = Math.abs(num(refund.value) - calc) >= 0.01 && refund.value !== '';
    rnWrap.hidden = !diff;

    var w = form.querySelector('input[name="why"]:checked');
    noteRq.hidden = !(w && w.getAttribute('data-note') === '1');
    if (w && pcs > 0) {
      stock.hidden = false;
      stock.className = 'ret-stock ' + (w.getAttribute('data-restock') === '1' ? 'in' : 'keep');
      stock.textContent = w.getAttribute('data-restock') === '1'
        ? 'ของ ' + pcs + ' ชิ้นจะกลับเข้าสต๊อกและขายต่อได้'
        : 'ของ ' + pcs + ' ชิ้นจะไม่เข้าสต๊อก — แยกเก็บไว้ ไม่เอาไปขายต่อ';
    } else {
      stock.hidden = true;
    }
    if (!go.hasAttribute('data-closed')) {
      go.disabled = (pcs === 0 || !w || (!noteRq.hidden && note.value.trim() === '')
                     || num(refund.value) > calc + 0.001 || (diff && rn.value.trim() === ''));
    }
  }

  if (go.disabled) { go.setAttribute('data-closed', '1'); }
  refund.addEventListener('input', function () { touched = true; sync(); });
  form.addEventListener('input', sync);
  form.addEventListener('change', sync);
  sync();
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
