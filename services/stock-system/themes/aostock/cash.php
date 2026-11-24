<?php
/**
 * FILE: themes/aostock/cash.php
 * ROLE: เติมเงินทอน / หยิบเงินออกจากลิ้นชักระหว่างวัน (เงินสดอย่างเดียว · สิทธิ์ “เงินเข้า / ออกลิ้นชัก”)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_store_day, ao_stock_cash_move, ao_stock_log (ผ่าน api.php)
 * TODO:
 *   - [x] ประวัติการแก้ไขดู docs/CHANGELOG.md
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
require_once dirname(__FILE__) . '/include/function.php';

$user  = require_login();                 // สิทธิ์ cash ตรวจใน page_perm
$code  = work_branch($user);
$error = '';
$post  = array();                         // ค่าที่กรอกไว้ — บันทึกไม่ผ่านแล้วไม่หาย

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post = $_POST;
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $error = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } elseif (isset($_POST['act']) && $_POST['act'] === 'cash') {
        $dir = (isset($_POST['dir']) && $_POST['dir'] === 'out') ? 'out' : 'in';
        $amt = (isset($_POST['amount']) && is_numeric(trim((string) $_POST['amount']))) ? round((float) trim((string) $_POST['amount']), 2) : 0;
        $r   = store_cash_add($code, $user, $dir, $amt, isset($_POST['why']) ? $_POST['why'] : '');
        if (isset($r['error'])) {
            $error = $r['error'];
        } else {
            header('Location: ' . url('cash.php?ok=1'));
            exit;
        }
    }
}

$isOpen   = store_is_open($code);
$isClosed = store_is_closed($code);
$list     = $isOpen ? store_cash_list($code) : array();
$expected = $isOpen ? store_expected_cash($code) : 0;
$pv       = function ($k) use ($post) { return isset($post[$k]) ? trim((string) $post[$k]) : ''; };
$isOut    = ($pv('dir') === 'out');
$dirs     = array('in' => array('i-plus', 'เติมเงินทอนเข้าลิ้นชัก'), 'out' => array('i-minus', 'หยิบเงินออกจากลิ้นชัก'));

$PAGE_TITLE = 'เติมเงินทอน / หยิบเงินออก';
$PAGE_SUB   = branch_name($code) . ' · ' . thai_date_full(time());
$NAV_ACTIVE = 'cash.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<div class="store-wrap">

<?php if (!$isOpen) { ?>
  <?php if ($error !== '') { ?>
    <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($error) ?></span></div>
  <?php } ?>
  <div class="store-hero">
    <span class="ic ic--off"><svg class="ico"><use href="#i-store-off"/></svg></span>
    <h2><?php echo $isClosed ? 'ร้านปิดแล้ววันนี้' : 'ร้านยังไม่เปิด' ?></h2>
    <p>เติมเงินทอน / หยิบเงินออกได้เฉพาะตอนร้านเปิด</p>
    <?php if (!$isClosed && page_ok($user, 'store.php')) { ?>
    <a class="btn btn-primary" href="store.php"><svg class="ico"><use href="#i-store"/></svg> ไปเปิดร้าน</a>
    <?php } ?>
  </div>

<?php } else { ?>
  <?php if ($error !== '') { ?>
    <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($error) ?></span></div>
  <?php } elseif (isset($_GET['ok'])) { ?>
    <div class="alert alert-info" role="status"><svg class="ico"><use href="#i-check"/></svg><span>บันทึกเงินเข้า / ออกลิ้นชักแล้ว</span></div>
  <?php } ?>

  <section class="card store-cash">
    <div class="card-head"><div><h3>เติมเงินทอน / หยิบเงินออก</h3><p>เงินสดในลิ้นชัก · ต้องระบุเหตุผลทุกครั้ง · ลงประวัติให้ผู้ดูแลเห็น</p></div></div>
    <div class="card-body num">
      <div class="kvl">
        <div class="tot"><span>ควรมีในลิ้นชักตอนนี้</span><b><?php echo e(money2($expected)) ?></b></div>
      </div>
      <form method="post" action="cash.php" class="store-cash-f">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="act" value="cash">
        <div class="field cash-pick">
          <span class="lbl" id="cash-dir-lb">รายการ</span>
          <div class="segs cash-segs cash-segs-dir" id="cash-dir" role="radiogroup" aria-labelledby="cash-dir-lb">
            <?php foreach ($dirs as $dk => $dv) { $on = (($dk === 'out') === $isOut); ?>
            <label class="seg<?php echo $on ? ' on' : '' ?>"><input type="radio" name="dir" value="<?php echo $dk ?>"<?php echo $on ? ' checked' : '' ?>><svg class="ico"><use href="#<?php echo $dv[0] ?>"/></svg> <?php echo e($dv[1]) ?></label>
            <?php } ?>
          </div>
        </div>
        <div class="field cash-amt-f">
          <label for="cash-amt">จำนวนเงิน</label>
          <div class="adm-unit">
            <input class="input" type="number" id="cash-amt" name="amount" min="0.01" max="1000000" step="0.01" inputmode="decimal" required
                   value="<?php echo e($pv('amount')) ?>" placeholder="0.00">
            <span>บาท</span>
          </div>
        </div>
        <div class="field field-why">
          <label for="cash-why">เหตุผล</label>
          <input class="input" type="text" id="cash-why" name="why" maxlength="200" required autocomplete="off"
                 value="<?php echo e($pv('why')) ?>" placeholder="เช่น แลกเหรียญเพิ่ม / ซื้อถุงใส่ของ">
        </div>
        <button class="btn btn-primary btn-block" type="submit"><svg class="ico"><use href="#i-coin"/></svg> บันทึก</button>
      </form>
    </div>
  </section>

  <section class="card">
    <div class="card-head"><div><h3>รายการวันนี้</h3><p><?php echo number_format(count($list)) ?> รายการ</p></div></div>
    <?php if (!$list) { ?>
      <p class="empty">ยังไม่มีการเติม / หยิบเงินวันนี้</p>
    <?php } else { ?>
    <div class="card-body num">
      <div class="kvl">
        <?php foreach ($list as $cm) { ?>
          <div><span><?php echo e($cm['time']) ?> น. · <?php echo e($cm['reason']) ?> <small>(<?php echo e($cm['by']) ?>)</small></span>
               <b class="<?php echo $cm['dir'] === 'in' ? 'qty-in' : 'qty-out' ?>"><?php echo $cm['dir'] === 'in' ? '+' : '−' ?><?php echo e(money2($cm['amount'])) ?></b></div>
        <?php } ?>
      </div>
    </div>
    <?php } ?>
  </section>
<?php } ?>

</div>

<script>
(function () {
  /* ปุ่มเลือก เติม / หยิบ — ไฮไลต์ตัวที่เลือก */
  var box = document.getElementById('cash-dir');
  if (!box) { return; }
  box.addEventListener('change', function () {
    var ls = box.querySelectorAll('label.seg');
    for (var i = 0; i < ls.length; i++) { ls[i].classList.toggle('on', ls[i].querySelector('input').checked); }
  });
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
