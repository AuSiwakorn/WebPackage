<?php
/* ==========================================================
   AOSTOCK DEMO — หมวดสินค้า (พนักงาน)
   ----------------------------------------------------------
   เข้าได้เฉพาะพนักงานที่ผู้ดูแลติ๊กสิทธิ์ “จัดการหมวดสินค้า” (category)
   - เพิ่มหมวดใหม่ได้ (ชื่อห้ามซ้ำ)
   - ลบได้เฉพาะหมวดที่ยังไม่มีสินค้า — หมวดที่มีสินค้าต้องย้ายสินค้าออกก่อน
   - หมวดใช้ร่วมกันทุกสาขา · ทุกการเพิ่ม / ลบ ลงประวัติของสาขาที่ทำ
   - กดจำนวนสินค้า → popup รายชื่อสินค้าในหมวด พร้อมราคาและคงเหลือของสาขาตัวเอง
   ผู้ดูแลดูอย่างเดียวที่ adm-categories.php
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // ไม่มีสิทธิ์ category → กลับหน้าแรก · ผู้ดูแล → adm-categories.php
$code = work_branch($user);
$err  = '';
$errAt = '';
$name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? $_POST['act'] : '';
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
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
$total = 0;
foreach ($cats as $c) {
    $total += $c['count'];
}

$branch     = $code;
$PAGE_TITLE = 'หมวดสินค้า';
$PAGE_SUB   = count($cats) . ' หมวด · สินค้า ' . $total . ' รายการ · ใช้ร่วมกันทุกสาขา';
$NAV_ACTIVE = 'categories.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($err !== '' && $errAt === ''): ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
<?php endif; ?>

<!-- ==================== เพิ่มหมวด ==================== -->
<section class="card">
  <div class="card-head">
    <div><h2>เพิ่มหมวดสินค้า</h2><span class="sub">ชื่อห้ามซ้ำกับหมวดที่มีอยู่ · เพิ่มแล้วเลือกใช้ได้ทันทีทุกสาขา</span></div>
  </div>
  <?php if ($errAt === 'new'): ?>
    <div class="alert alert-error adm-ok" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
  <?php endif; ?>
  <form class="cat-add" method="post" action="categories.php">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="act" value="add">
    <label class="sr-only" for="cn">ชื่อหมวด</label>
    <input class="input" type="text" id="cn" name="name" value="<?= e($name) ?>" maxlength="50" required
           placeholder="เช่น หูฟัง / ลำโพง" autocomplete="off">
    <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มหมวด</button>
  </form>
</section>

<!-- ==================== รายการหมวด ==================== -->
<section class="card">
  <div class="card-head">
    <div><h2>หมวดทั้งหมด</h2><span class="sub">ลบได้เฉพาะหมวดที่ยังไม่มีสินค้า</span></div>
  </div>
  <div class="tbl-wrap">
    <table class="tbl cat-tbl">
      <thead><tr><th>หมวด</th><th class="r">สินค้า</th><th>ที่มา</th><th class="r"><span class="sr-only">จัดการ</span></th></tr></thead>
      <tbody>
        <?php foreach ($cats as $c => $x): ?>
          <tr>
            <td data-label="หมวด"><b><?= e($c) ?></b></td>
            <td data-label="สินค้า" class="r num">
              <?php if ($x['count']): ?>
                <button type="button" class="cell-link" data-cat-list="cl-<?= e(substr(md5($c), 0, 8)) ?>" title="ดูรายชื่อสินค้าในหมวดนี้"><?= number_format($x['count']) ?> รายการ</button>
              <?php else: ?><span class="adm-none">ยังไม่มีสินค้า</span><?php endif; ?>
            </td>
            <td data-label="ที่มา"><?= $x['added'] ? '<small>เพิ่มโดย ' . e($x['by']) . ' · ' . e($x['at']) . '</small>' : '<small>หมวดตั้งต้น</small>' ?></td>
            <td data-label="" class="r">
              <?php if ($x['count'] === 0): ?>
                <form method="post" action="categories.php" class="cat-del" onsubmit="return confirm('ลบหมวด “<?= e($c) ?>” ?');">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="act" value="delete">
                  <input type="hidden" name="c" value="<?= e($c) ?>">
                  <button class="btn btn-ghost btn-sm br-del" type="submit"><svg class="ico"><use href="#i-trash"/></svg> ลบ</button>
                </form>
              <?php else: ?>
                <small class="adm-none" title="ย้ายสินค้าออกจากหมวดก่อนจึงจะลบได้">ลบไม่ได้ (มีสินค้า)</small>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- ==================== รายชื่อสินค้าในแต่ละหมวด (แสดงใน popup เมื่อกดจำนวน) ==================== -->
<?php foreach ($cats as $c => $x): if (!$x['count']) { continue; } ?>
  <template id="cl-<?= e(substr(md5($c), 0, 8)) ?>">
    <div class="ds-head">
      <h2><?= e($c) ?></h2>
      <p class="sub"><?= number_format($x['count']) ?> รายการ · คงเหลือของ<?= e(branch_name($code)) ?></p>
    </div>
    <div class="ds-pane">
      <table class="ds-lines">
        <thead><tr><th>สินค้า</th><th class="r">ราคาขาย</th><th class="r">คงเหลือ</th></tr></thead>
        <tbody>
          <?php foreach (cat_products($c) as $p): $st = branch_status($p, $code); ?>
            <tr><td><?= e($p['name']) ?> <small><?= e($p['sku']) ?></small></td>
                <td class="r num"><?= e(money2($p['price'])) ?></td>
                <td class="r num"><span class="stk stk-<?= e($st) ?>"><?= number_format(product_qty($p, $code)) ?></span> <small><?= e($p['unit']) ?></small></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </template>
<?php endforeach; ?>

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
      var tpl = document.getElementById(t.getAttribute('data-cat-list'));
      body.innerHTML = '';
      if (tpl) { body.appendChild(tpl.content.cloneNode(true)); }
      dlg.showModal();
      return;
    }
    if ((ev.target.closest && ev.target.closest('[data-cl-close]')) || ev.target === dlg) { dlg.close(); }
  });
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
