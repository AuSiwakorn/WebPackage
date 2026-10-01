<?php
/* ==========================================================
   AOSTOCK DEMO — ประวัติการทำรายการ
   อ่านจาก $_SESSION['log'][ สาขา|วันที่ ] ทั้งหมด
   ผู้ดูแล: เปิดมาเจอภาพรวมทุกสาขา (inc/history-all.php)
            กด "ดู" ที่รายการ → ?view=branch&branch=XX ดู/แก้ของสาขาเดียวแบบเดิม
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user    = require_login();
$isAdmin = ($user['role'] === 'admin');

/* ผู้ดูแล: หน้าแรกเป็นประวัติรวมทุกสาขา */
if ($isAdmin && $_SERVER['REQUEST_METHOD'] !== 'POST' && (!isset($_GET['view']) || $_GET['view'] !== 'branch')) {
    require dirname(__FILE__) . '/inc/history-all.php';
    exit;
}

$code = work_branch($user);          // ผู้ดูแลเลือกสาขาได้จากแถบบน

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

        if ($chk !== null && !can_void_doc($user, $chk)) {
            $err = 'บิลนี้เป็นของพนักงานคนอื่น — ต้องมีสิทธิ์แก้งานคนอื่นจึงจะแก้หรือยกเลิกได้';
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
            } elseif ($redo) {
                /* ดึงรายการกลับเข้าตะกร้าแล้วพาไปหน้าขายต่อทันที */
                cart_from_bill($v, $code);
                header('Location: ' . url('sale.php?edited=' . rawurlencode($v['no'])));
                exit;
            }
        }
    }
    if ($err === '') {
        header('Location: ' . url(hist_url(isset($_GET['t']) ? 't=' . rawurlencode($_GET['t']) : '')));
        exit;
    }
}

$all    = log_today($code);
$counts = log_counts($all);
$t      = isset($_GET['t']) ? trim($_GET['t']) : '';
if ($t !== '' && !isset($counts[$t])) {
    $t = '';
}
$rows  = log_filter($all, $t);
$mine  = 0;
foreach ($all as $r) {
    if ($r['by_user'] === $user['username']) {
        $mine++;
    }
}

$branch     = $code;
$PAGE_TITLE = 'ประวัติการทำรายการ';
$PAGE_SUB   = branch_name($code) . ' · ' . thai_date_full($pastTs ? $pastTs : time()) . ($pastTs ? ' (ย้อนหลัง)' : '');
$NAV_ACTIVE = 'history.php';
if ($isAdmin) {
    $PICK_HIDDEN = array('view' => 'branch');   // เปลี่ยนสาขาจากแถบบนแล้วยังอยู่หน้าสาขาเดียว
}
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($isAdmin): ?>
  <p class="hist-back">
    <a class="btn btn-ghost btn-sm" href="history.php?mode=day&amp;d=<?= e(date('Y-m-d', $pastTs ? $pastTs : time())) ?>">‹ กลับไปประวัติรวมทุกสาขา</a>
    <span>กำลังดูเฉพาะ<?= e(branch_name($code)) ?> — แก้ / ยกเลิกเอกสารได้จากหน้านี้</span>
  </p>
<?php endif; ?>

<?php if ($notOpened): ?>
  <div class="alert alert-info" role="status">
    <svg class="ico"><use href="#i-info"/></svg>
    <span>ยังไม่ได้เปิดร้านวันนี้ — ดูประวัติได้ตามปกติ งานคลัง (รับเข้า / ตัดออก / ตรวจนับ) ทำได้เลย ส่วนการขายต้องเปิดร้านก่อน</span>
    <div class="alert-act">
      <?php if (can($user, 'sale')): ?>
      <a class="btn btn-ghost btn-sm" href="store.php"><svg class="ico"><use href="#i-store"/></svg> ไปเปิดร้าน</a>
      <?php endif; ?>
      <a class="btn btn-ghost btn-sm" href="report-sales.php"><svg class="ico"><use href="#i-chart"/></svg> ดูยอดขายย้อนหลัง</a>
    </div>
  </div>
<?php endif; ?>

<?php if ($err !== ''): ?>
  <div class="alert alert-error" role="alert">
    <svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span>
  </div>
<?php endif; ?>

<?php if ($pastLimit > 0): ?>
  <!-- เลือกวัน — เห็นเฉพาะคนที่มีสิทธิ์แก้ย้อนหลัง (ผู้ดูแลย้อนได้ทุกวันที่มีข้อมูล) -->
  <nav class="cats day-pick" aria-label="เลือกวันที่">
    <a class="cat<?= $pastTs ? '' : ' on' ?>" href="<?= e(hist_url()) ?>">วันนี้</a>
    <?php for ($i = 1; $i <= $pastLimit; $i++): $dts = strtotime('-' . $i . ' day', strtotime(date('Y-m-d'))); ?>
      <a class="cat<?= ($pastTs && date('Ymd', $dts) === $pastDay) ? ' on' : '' ?>" href="<?= e(hist_url('d=' . date('Ymd', $dts))) ?>"><?= e(thai_day_month($dts)) ?></a>
    <?php endfor; ?>
  </nav>
<?php endif; ?>

<?php if ($pastTs): ?>
  <?php require dirname(__FILE__) . '/inc/history-past.php'; ?>
  <?php require dirname(__FILE__) . '/inc/footer.php'; exit; ?>
<?php endif; ?>

<section class="mini num" aria-label="สรุปรายการวันนี้">
  <div class="m"><div class="lb">รายการทั้งหมด</div><div class="nm"><?= number_format(count($all)) ?></div><div class="sb">ของสาขาวันนี้</div></div>
  <div class="m"><div class="lb">ของฉัน</div><div class="nm"><?= number_format($mine) ?></div><div class="sb">ที่ฉันเป็นคนทำ</div></div>
  <div class="m"><div class="lb">บิลขาย</div><div class="nm"><?= number_format($counts['sale']) ?></div><div class="sb">ใบ</div></div>
</section>

<section class="card">
  <div class="card-head">
    <div>
      <h2>รายการวันนี้</h2>
      <span class="sub">เรียงจากใหม่ไปเก่า · เก็บไว้ใน session ของเดโม</span>
    </div>
  </div>

  <div class="cats">
    <a class="cat<?= $t === '' ? ' on' : '' ?>" href="<?= e(hist_url()) ?>">ทั้งหมด <i><?= count($all) ?></i></a>
    <?php foreach (log_types() as $k => $meta): ?>
      <?php if ($counts[$k] === 0) { continue; } ?>
      <a class="cat<?= $t === $k ? ' on' : '' ?>" href="<?= e(hist_url('t=' . rawurlencode($k))) ?>">
        <?= e($meta['label']) ?> <i><?= $counts[$k] ?></i>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$rows): ?>
    <p class="empty">
      <svg class="ico"><use href="#i-history"/></svg>
      <?= $notOpened ? 'วันนี้ยังไม่มีรายการ' : 'ยังไม่มีรายการในหมวดนี้' ?><br><small>รายการจะถูกบันทึกอัตโนมัติเมื่อเปิดร้าน ขาย รับเข้า ตัดออก หรือปิดร้าน</small>
    </p>
  <?php else: ?>
    <ol class="tl">
      <?php foreach ($rows as $r): ?>
        <?php $m = log_type_of($r['type']); ?>
        <li class="tl-i tone-<?= e($m['tone']) ?><?= $r['by_user'] === $user['username'] ? ' me' : '' ?>">
          <span class="tl-ic"><svg class="ico"><use href="#<?= e($m['icon']) ?>"/></svg></span>
          <div class="tl-b">
            <div class="tl-h">
              <b><?= e($r['title']) ?></b>
              <span class="badge b-<?= e($m['tone']) ?>"><?= e($m['label']) ?></span>
              <?php if ($r['amount'] !== null && in_array($r['type'], array('receive', 'rvoid'), true)): ?>
                <?php /* รับเข้าเก็บ amount เป็นจำนวนชิ้น ไม่ใช่เงิน */ ?>
                <span class="tl-amt num"><?= $r['type'] === 'receive' ? '+' : '−' ?><?= number_format($r['amount']) ?> ชิ้น</span>
              <?php elseif ($r['amount'] !== null): ?>
                <span class="tl-amt num"><?= money2($r['amount']) ?> ฿</span>
              <?php endif; ?>
            </div>
            <div class="tl-m"><?= e($r['time']) ?> น. · <?= e($r['by']) ?></div>

            <?php
            /* บิลขาย / ใบรับเข้า ที่ยังไม่ถูกยกเลิก — แก้หรือยกเลิกได้จากตรงนี้
               ทั้งสองทางต้องกรอกหมายเหตุก่อนเสมอ */
            $bill = ($r['type'] === 'sale' && $r['ref'] !== '') ? bill_by_no($code, $r['ref']) : null;
            $rdoc = ($r['type'] === 'receive' && $r['ref'] !== '') ? receive_by_no($code, $r['ref']) : null;
            $idoc = ($r['type'] === 'issue' && $r['ref'] !== '') ? issue_by_no($code, $r['ref']) : null;
            $adoc = ($r['type'] === 'adjust' && $r['ref'] !== '') ? adj_by_no($code, $r['ref']) : null;
            ?>

            <?php if ($adoc !== null && empty($adoc['void']) && can_void_doc($user, $adoc)): ?>
              <?php $dat = ' data-bill="' . e($adoc['no']) . '" data-items="' . (int) $adoc['items'] . '"'; ?>
              <div class="tl-act">
                <?php foreach (array(
                    array('edit', 'แก้ไขใบนี้',  'i-arrow'),
                    array('void', 'ยกเลิกใบนี้', 'i-ban'),
                ) as $b): ?>
                  <form method="post" action="stocktake.php" data-confirm="<?= e($b[0]) ?>"<?= $dat ?>>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="act" value="<?= e($b[0]) ?>">
                    <input type="hidden" name="no" value="<?= e($adoc['no']) ?>">
                    <input class="reason-fb" type="text" name="reason" value=""
                           placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุ <?= e($adoc['no']) ?>">
                    <button class="btn btn-ghost btn-sm" type="submit">
                      <svg class="ico"><use href="#<?= e($b[2]) ?>"/></svg> <?= e($b[1]) ?>
                    </button>
                  </form>
                <?php endforeach; ?>
              </div>
            <?php elseif ($adoc !== null && !empty($adoc['void'])): ?>
              <p class="tl-voided">
                <svg class="ico"><use href="#i-ban"/></svg>
                ใบนี้ถูก<?= (isset($adoc['void_mode']) && $adoc['void_mode'] === 'edit') ? 'ยกเลิกเพื่อแก้ไข' : 'ยกเลิก' ?>
                เมื่อ <?= e($adoc['void_at']) ?> น. โดย <?= e($adoc['void_by']) ?>
                — <?= e($adoc['void_reason']) ?>
              </p>
            <?php endif; ?>

            <?php if ($idoc !== null && empty($idoc['void']) && can_void_doc($user, $idoc)): ?>
              <?php $dat = ' data-bill="' . e($idoc['no']) . '" data-qty="' . (int) $idoc['qty']
                         . '" data-items="' . (int) $idoc['items'] . '"'; ?>
              <div class="tl-act">
                <?php foreach (array(
                    array('edit', 'แก้ไขใบนี้',  'i-arrow'),
                    array('void', 'ยกเลิกใบนี้', 'i-ban'),
                ) as $b): ?>
                  <form method="post" action="issue.php" data-confirm="<?= e($b[0]) ?>"<?= $dat ?>>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="act" value="<?= e($b[0]) ?>">
                    <input type="hidden" name="no" value="<?= e($idoc['no']) ?>">
                    <input class="reason-fb" type="text" name="reason" value=""
                           placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุ <?= e($idoc['no']) ?>">
                    <button class="btn btn-ghost btn-sm" type="submit">
                      <svg class="ico"><use href="#<?= e($b[2]) ?>"/></svg> <?= e($b[1]) ?>
                    </button>
                  </form>
                <?php endforeach; ?>
              </div>
            <?php elseif ($idoc !== null && !empty($idoc['void'])): ?>
              <p class="tl-voided">
                <svg class="ico"><use href="#i-ban"/></svg>
                ใบนี้ถูก<?= (isset($idoc['void_mode']) && $idoc['void_mode'] === 'edit') ? 'ยกเลิกเพื่อแก้ไข' : 'ยกเลิก' ?>
                เมื่อ <?= e($idoc['void_at']) ?> น. โดย <?= e($idoc['void_by']) ?>
                — <?= e($idoc['void_reason']) ?>
              </p>
            <?php endif; ?>

            <?php if ($rdoc !== null && empty($rdoc['void']) && can_void_doc($user, $rdoc)): ?>
              <?php $dat = ' data-bill="' . e($rdoc['no']) . '" data-qty="' . (int) $rdoc['qty']
                         . '" data-items="' . (int) $rdoc['items'] . '"'; ?>
              <div class="tl-act">
                <?php foreach (array(
                    array('edit', 'แก้ไขใบนี้',  'i-arrow'),
                    array('void', 'ยกเลิกใบนี้', 'i-ban'),
                ) as $b): ?>
                  <form method="post" action="receive.php" data-confirm="<?= e($b[0]) ?>"<?= $dat ?>>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="act" value="<?= e($b[0]) ?>">
                    <input type="hidden" name="no" value="<?= e($rdoc['no']) ?>">
                    <input class="reason-fb" type="text" name="reason" value=""
                           placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุ <?= e($rdoc['no']) ?>">
                    <button class="btn btn-ghost btn-sm" type="submit">
                      <svg class="ico"><use href="#<?= e($b[2]) ?>"/></svg> <?= e($b[1]) ?>
                    </button>
                  </form>
                <?php endforeach; ?>
              </div>
            <?php elseif ($rdoc !== null && !empty($rdoc['void'])): ?>
              <p class="tl-voided">
                <svg class="ico"><use href="#i-ban"/></svg>
                ใบนี้ถูก<?= (isset($rdoc['void_mode']) && $rdoc['void_mode'] === 'edit') ? 'ยกเลิกเพื่อแก้ไข' : 'ยกเลิก' ?>
                เมื่อ <?= e($rdoc['void_at']) ?> น. โดย <?= e($rdoc['void_by']) ?>
                — <?= e($rdoc['void_reason']) ?>
              </p>
            <?php endif; ?>
            <?php if ($bill !== null && empty($bill['void']) && can_void_doc($user, $bill) && !bill_returned_any($bill['no'])): ?>
              <?php
              $act = hist_url($t !== '' ? 't=' . rawurlencode($t) : '');
              $dat = ' data-bill="' . e($bill['no']) . '" data-total="' . e(money2($bill['total']))
                   . '" data-qty="' . (int) $bill['qty'] . '" data-items="' . (int) $bill['items'] . '"';
              ?>
              <div class="tl-act">
                <?php foreach (array(
                    array('edit', 'แก้ไขบิลนี้',  'i-arrow', 'หมายเหตุการแก้ไขบิล'),
                    array('void', 'ยกเลิกบิลนี้', 'i-ban',   'หมายเหตุการยกเลิกบิล'),
                ) as $b): ?>
                  <form method="post" action="<?= e($act) ?>" data-confirm="<?= e($b[0]) ?>"<?= $dat ?>>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="act" value="<?= e($b[0]) ?>">
                    <input type="hidden" name="no" value="<?= e($bill['no']) ?>">
                    <input class="reason-fb" type="text" name="reason" value=""
                           placeholder="หมายเหตุ (จำเป็น)"
                           aria-label="<?= e($b[3]) ?> <?= e($bill['no']) ?>">
                    <button class="btn btn-ghost btn-sm" type="submit">
                      <svg class="ico"><use href="#<?= e($b[2]) ?>"/></svg> <?= e($b[1]) ?>
                    </button>
                  </form>
                <?php endforeach; ?>
              </div>
            <?php elseif ($bill !== null && !empty($bill['void'])): ?>
              <p class="tl-voided">
                <svg class="ico"><use href="#i-ban"/></svg>
                บิลนี้ถูก<?= (isset($bill['void_mode']) && $bill['void_mode'] === 'edit') ? 'ยกเลิกเพื่อแก้ไข' : 'ยกเลิก' ?>
                เมื่อ <?= e($bill['void_at']) ?> น. โดย <?= e($bill['void_by']) ?>
                — <?= e($bill['void_reason']) ?>
              </p>
            <?php endif; ?>
            <?php if ($r['detail']): ?>
              <dl class="tl-d">
                <?php foreach ($r['detail'] as $k => $v): ?>
                  <div><dt><?= e($k) ?></dt><dd class="num"><?= e($v) ?></dd></div>
                <?php endforeach; ?>
              </dl>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
