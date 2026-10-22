<?php
/**
 * FILE: themes/aostock/categories.php
 * ROLE: หมวดสินค้า (พนักงาน)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_category, ao_stock_product, ao_stock_log (ผ่าน api.php)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] อ่าน / เขียนข้อมูลจากตาราง ao_stock_* ผ่าน api.php (ช่วงที่ 5–9)
 *   - [x] ช่วงที่ 10: แก้ File Header ให้ตรงกับระบบจริง · กัน XSS — ชื่อหมวดในกล่องยืนยันลบอ่านจาก data-ask (ไม่ต่อลง JavaScript ตรง ๆ)
 *   - [x] ช่วงที่ 11: แยกสิทธิ์เพิ่มหมวด (category_add) / ลบหมวด (category_del) — เช็กทั้งตอนแสดงฟอร์มและตอนบันทึก
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 4): popup รายชื่อสินค้าในหมวดโหลดตอนกด (?list=) ครั้งละ 60 + “แสดงเพิ่ม” — เดิมฝังสินค้าทุกหมวดไว้ใน <template>
 *   - [x] 21 ต.ค. (ผลทดสอบ): ถามก่อนลบหมวดด้วยกล่องยืนยันของระบบ (data-confirm="ask") แทน confirm() ของเบราว์เซอร์
 *   - [x] 22 ต.ค. (สินค้าของสาขา): จำนวน / รายชื่อสินค้าในหมวดนับเฉพาะส่วนกลาง + ของสาขานี้ · ลบหมวดยังดูทั้งระบบ
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   หมวดสินค้า (พนักงาน)
   ----------------------------------------------------------
   เข้าได้เฉพาะพนักงานที่ผู้ดูแลติ๊กสิทธิ์ “เพิ่มหมวด” หรือ “ลบหมวด” (ช่วงที่ 11 แยกเป็น 2 สิทธิ์)
   - เพิ่มหมวดใหม่ได้ (ชื่อห้ามซ้ำ) — ต้องมีสิทธิ์ category_add
   - ลบได้เฉพาะหมวดที่ยังไม่มีสินค้า — หมวดที่มีสินค้าต้องย้ายสินค้าออกก่อน · ต้องมีสิทธิ์ category_del
   - หมวดใช้ร่วมกันทุกสาขา · ทุกการเพิ่ม / ลบ ลงประวัติของสาขาที่ทำ
   - กดจำนวนสินค้า → popup รายชื่อสินค้าในหมวด พร้อมราคาและคงเหลือของสาขาตัวเอง (โหลดตอนกด ครั้งละ 60)
   ผู้ดูแลเพิ่ม / ลบหมวดได้ที่ adm-categories.php ด้วย
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // ไม่มีสิทธิ์เพิ่ม / ลบหมวดเลย → กลับหน้าแรก · ผู้ดูแล → adm-categories.php
$code = work_branch($user);
$err  = '';
$errAt = '';
$name = '';
$canAdd = can($user, 'category_add');
$canDel = can($user, 'category_del');

/* ---------- popup รายชื่อสินค้าในหมวด — โหลดตอนกด (งานความเร็ว ช่วง 4) ----------
   เดิมฝังรายชื่อสินค้าของทุกหมวดไว้ในหน้า (<template>) — สินค้าเป็นพัน = หน้าใหญ่หลาย MB
   ?list=ชื่อหมวด → หัว popup + ตาราง 60 แถวแรก · &from=N (htmx “แสดงเพิ่ม”) → แค่แถวชุดถัดไป */
if (isset($_GET['list']) && is_string($_GET['list'])) {
    $lc  = $_GET['list'];
    $reg = cat_registry();
    if (!isset($reg[$lc])) {
        http_response_code(404);
        exit;
    }
    $prods = cat_products($lc, $code);                                // ส่วนกลาง + สินค้าของสาขานี้ (22 ต.ค.)
    list($from, $end, $isMore) = Stock_getListRange();
    $url   = 'categories.php?list=' . rawurlencode($lc);
    if (!$isMore) { ?>
    <div class="ds-head">
      <h2><?php echo e($lc) ?></h2>
      <p class="sub"><?php echo number_format(count($prods)) ?> รายการ · คงเหลือของ<?php echo e(branch_name($code)) ?></p>
    </div>
    <div class="ds-pane">
      <table class="ds-lines">
        <thead><tr><th>สินค้า</th><th class="r">ราคาขาย</th><th class="r">คงเหลือ</th></tr></thead>
        <tbody>
<?php
    }
    foreach (array_slice($prods, $from, $end - $from) as $p) {
        $st = branch_status($p, $code); ?>
            <tr><td><?php echo e($p['name']) ?> <small><?php echo e($p['sku']) ?></small></td>
                <td class="r num"><?php echo e(money2($p['price'])) ?></td>
                <td class="r num"><span class="stk stk-<?php echo e($st) ?>"><?php echo number_format(product_qty($p, $code)) ?></span> <small><?php echo e($p['unit']) ?></small></td></tr>
<?php
    }
    Stock_renderMoreButton($end, count($prods), $url, 3);
    if (!$isMore) { ?>
        </tbody>
      </table>
    </div>
<?php
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? $_POST['act'] : '';
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } elseif (($act === 'add' && !$canAdd) || ($act === 'delete' && !$canDel)) {
        $err = 'ไม่มีสิทธิ์' . ($act === 'add' ? 'เพิ่ม' : 'ลบ') . 'หมวดสินค้า — ติดต่อผู้ดูแลเพื่อเปิดสิทธิ์';
    } elseif ($act === 'add') {
        $name  = isset($_POST['name']) ? (string) $_POST['name'] : '';
        $err   = cat_add($name, $user, $code);
        $errAt = 'new';
        if ($err === '') {
            $_SESSION['flash'] = 'เพิ่มหมวด “' . trim($name) . '” แล้ว — ใช้ได้ทุกสาขา';
        }
    } elseif ($act === 'delete') {
        $del = isset($_POST['c']) ? (string) $_POST['c'] : '';
        $err = cat_delete($del, $user, $code);
        if ($err === '') {
            $_SESSION['flash'] = 'ลบหมวด “' . $del . '” แล้ว';
        }
    }
    if ($err === '') {
        header('Location: ' . url('categories.php'));
        exit;
    }
}

$cats  = cat_registry();
/* จำนวนสินค้าที่สาขานี้เห็น (ส่วนกลาง + ของสาขานี้ · รวมเลิกขาย) — ลบหมวดยังดูจำนวนทั้งระบบ ($cats[..]['count']) (22 ต.ค.) */
$mine = array();
foreach (products_list(true) as $p) {
    if (Stock_isProductInBranch($p, $code)) {
        $mine[$p['cat']] = (isset($mine[$p['cat']]) ? $mine[$p['cat']] : 0) + 1;
    }
}
$total = array_sum($mine);

$branch     = $code;
$CONFIRM_MODAL = true;                   // ถามก่อนลบหมวดด้วยกล่องยืนยันของระบบ (data-confirm="ask" · 21 ต.ค.)
$PAGE_TITLE = 'หมวดสินค้า';
$PAGE_SUB   = count($cats) . ' หมวด · สินค้า ' . $total . ' รายการ · ใช้ร่วมกันทุกสาขา';
$NAV_ACTIVE = 'categories.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($err !== '' && $errAt === '') { ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span></div>
<?php } ?>

<!-- ==================== เพิ่มหมวด ==================== -->
<?php if ($canAdd) { ?>
<section class="card">
  <div class="card-head">
    <div><h2>เพิ่มหมวดสินค้า</h2><span class="sub">ชื่อห้ามซ้ำกับหมวดที่มีอยู่ · เพิ่มแล้วเลือกใช้ได้ทันทีทุกสาขา</span></div>
  </div>
  <?php if ($errAt === 'new') { ?>
    <div class="alert alert-error adm-ok" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span></div>
  <?php } ?>
  <form class="cat-add" method="post" action="categories.php">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
    <input type="hidden" name="act" value="add">
    <label class="sr-only" for="cn">ชื่อหมวด</label>
    <input class="input" type="text" id="cn" name="name" value="<?php echo e($name) ?>" maxlength="50" required
           placeholder="เช่น หูฟัง / ลำโพง" autocomplete="off">
    <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มหมวด</button>
  </form>
</section>
<?php } ?>

<!-- ==================== รายการหมวด ==================== -->
<section class="card">
  <div class="card-head">
    <div><h2>หมวดทั้งหมด</h2><span class="sub"><?php echo $canDel ? 'ลบได้เฉพาะหมวดที่ยังไม่มีสินค้า' : 'ดูรายชื่อสินค้าในหมวดได้ · ลบหมวดต้องมีสิทธิ์ “ลบหมวด”' ?></span></div>
  </div>
  <div class="tbl-wrap">
    <table class="tbl cat-tbl">
      <thead><tr><th>หมวด</th><th class="r">สินค้า</th><th>ที่มา</th><th class="r"><span class="sr-only">จัดการ</span></th></tr></thead>
      <tbody>
        <?php foreach ($cats as $c => $x) { $n = isset($mine[$c]) ? $mine[$c] : 0; ?>
          <tr>
            <td data-label="หมวด"><b><?php echo e($c) ?></b></td>
            <td data-label="สินค้า" class="r num">
              <?php if ($n) { ?>
                <button type="button" class="cell-link" data-cat-list="<?php echo e($c) ?>" title="ดูรายชื่อสินค้าในหมวดนี้"><?php echo number_format($n) ?> รายการ</button>
              <?php } else { ?><span class="adm-none">ยังไม่มีสินค้า</span><?php } ?>
            </td>
            <td data-label="ที่มา"><?php echo $x['added'] ? '<small>เพิ่ม' . ($x['by'] !== '' ? 'โดย ' . e($x['by']) . ' · ' : 'เมื่อ ') . e($x['at']) . '</small>' : '<small>หมวดตั้งต้น</small>' ?></td>
            <td data-label="" class="r">
              <?php if (!$canDel) { ?>
              <?php } elseif ($x['count'] === 0) { ?>
                <form method="post" action="categories.php" class="cat-del" data-confirm="ask" data-ask="<?php echo e('ลบหมวด “' . $c . '” ?') ?>"
                      data-ask-sub="หมวดนี้ยังไม่มีสินค้า · ลบแล้วเพิ่มกลับได้ภายหลัง" data-ask-ok="ลบหมวด">
                  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="c" value="<?php echo e($c) ?>">
                  <button class="btn btn-ghost btn-sm br-del" type="submit"><svg class="ico"><use href="#i-trash"/></svg> ลบ</button>
                </form>
              <?php } else { ?>
                <small class="adm-none" title="ย้ายสินค้าออกจากหมวดก่อนจึงจะลบได้">ลบไม่ได้ (<?php echo $n ? 'มีสินค้า' : 'มีสินค้าของสาขาอื่น' ?>)</small>
              <?php } ?>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
</section>

<!-- ==================== รายชื่อสินค้าในหมวด — popup โหลดตอนกดจำนวน (categories.php?list=) ==================== -->
<dialog class="ds-modal" id="cl-modal" aria-label="สินค้าในหมวด">
  <button type="button" class="icon-btn ds-close" data-cl-close aria-label="ปิด"><svg class="ico"><use href="#i-x"/></svg></button>
  <div class="ds-body" id="cl-body"></div>
</dialog>
<script>
(function () {
  var dlg = document.getElementById('cl-modal'), body = document.getElementById('cl-body');
  if (!dlg || !dlg.showModal) { return; }
  document.addEventListener('click', function (ev) {
    var t = ev.target.closest ? ev.target.closest('[data-cat-list]') : null;
    if (t) {
      body.innerHTML = '<p class="empty">กำลังโหลด…</p>';
      dlg.showModal();
      fetch('categories.php?list=' + encodeURIComponent(t.getAttribute('data-cat-list')), { credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.text(); })
        .then(function (h) { body.innerHTML = h; if (window.htmx) { htmx.process(body); } })   /* ปุ่ม “แสดงเพิ่ม” ใช้ htmx */
        .catch(function () { body.innerHTML = '<p class="empty">โหลดไม่สำเร็จ ลองใหม่อีกครั้ง</p>'; });
      return;
    }
    if ((ev.target.closest && ev.target.closest('[data-cl-close]')) || ev.target === dlg) { dlg.close(); }
  });
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
