<?php
/**
 * FILE: themes/aostock/adm-categories.php
 * ROLE: [ผู้ดูแล] หมวดสินค้า (ดูอย่างเดียว)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_category, ao_stock_product, ao_stock_log (ผ่าน api.php — cat_registry / cat_add / cat_delete)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] อ่าน / เขียนข้อมูลจากตาราง ao_stock_* ผ่าน api.php (ช่วงที่ 5–9)
 *   - [x] ช่วงที่ 10: แก้ File Header ให้ตรงกับระบบจริง
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   AOSTOCK — [ผู้ดูแล] หมวดสินค้า
   ----------------------------------------------------------
   แสดงว่ามีหมวดอะไรบ้าง แต่ละหมวดมีสินค้าอะไร กี่รายการ (แอคคอร์เดียน — เปิดหมวดแรกไว้)
   ผู้ดูแลเพิ่ม / ลบหมวดได้ที่นี่ (ระบบใหม่ต้องมีหมวดก่อนเพิ่มสินค้า) · พนักงานที่มีสิทธิ์ “จัดการหมวดสินค้า” ทำได้ที่ categories.php
   ลบได้เฉพาะหมวดที่ไม่มีสินค้าอ้างถึงเลย (รวมสินค้าที่เลิกขาย)
   ?q = ค้นชื่อหมวด หรือชื่อสินค้า / SKU (เปิดทุกหมวดที่เจอ)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

/* ---------- เพิ่ม / ลบหมวด (cat_add / cat_delete ใน api.php — ลงประวัติของสาขาที่ผู้ดูแลเลือกอยู่) ---------- */
$err     = '';
$newName = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act  = isset($_POST['act']) ? $_POST['act'] : '';
    $code = work_branch($user);
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } elseif ($act === 'add') {
        $newName = isset($_POST['name']) ? (string) $_POST['name'] : '';
        $err     = cat_add($newName, $user, $code);
        if ($err === '') {
            $_SESSION['flash'] = 'เพิ่มหมวด “' . trim($newName) . '” แล้ว — ใช้ได้ทุกสาขา';
        }
    } elseif ($act === 'delete') {
        $del = isset($_POST['c']) ? (string) $_POST['c'] : '';
        $err = cat_delete($del, $user, $code);
        if ($err === '') {
            $_SESSION['flash'] = 'ลบหมวด “' . $del . '” แล้ว';
        }
    }
    if ($err === '') {
        header('Location: ' . url('adm-categories.php'));
        exit;
    }
}

$q     = isset($_GET['q']) && is_string($_GET['q']) ? (string) substr(trim($_GET['q']), 0, 120) : '';
$br    = branches_active();
$cats  = cat_registry();
$sum   = array('cats' => count($cats), 'items' => 0, 'empty' => 0, 'added' => 0);
$rows  = array();
foreach ($cats as $c => $x) {
    $sum['items'] += $x['count'];
    $sum['empty'] += $x['count'] === 0 ? 1 : 0;
    $sum['added'] += $x['added'] ? 1 : 0;
    $prods = cat_products($c);
    $hit   = ($q === '' || stripos($c, $q) !== false);
    if ($q !== '' && !$hit) {
        $prods = array_values(array_filter($prods, function ($p) use ($q) {
            return stripos($p['name'], $q) !== false || stripos($p['sku'], $q) !== false;
        }));
        if (!$prods) {
            continue;
        }
    }
    $qty = 0;
    $val = 0;
    foreach (cat_products($c) as $p) {
        $n    = product_qty($p, 'ALL');
        $qty += $n;
        $val += $n * $p['cost'];
    }
    $rows[$c] = array('meta' => $x, 'prods' => $prods, 'qty' => $qty, 'value' => $val);
}
$first = key($rows);

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = 'หมวดสินค้า';
$PAGE_SUB       = $sum['cats'] . ' หมวด · สินค้า ' . $sum['items'] . ' รายการ · หมวดใช้ร่วมกันทุกสาขา';
$NAV_ACTIVE     = 'adm-categories.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<section class="mini num adm-kpi" aria-label="สรุปหมวดสินค้า">
  <div class="m"><div class="lb">หมวดสินค้า</div><div class="nm"><?php echo number_format($sum['cats']) ?></div>
    <div class="sb"><?php echo $sum['added'] ? 'เพิ่มในระบบ ' . number_format($sum['added']) . ' หมวด' : 'หมวดตั้งต้นทั้งหมด' ?></div></div>
  <div class="m"><div class="lb">สินค้าทั้งหมด</div><div class="nm"><?php echo number_format($sum['items']) ?></div>
    <div class="sb">เฉลี่ย <?php echo $sum['cats'] ? number_format($sum['items'] / $sum['cats'], 1) : 0 ?> รายการ / หมวด</div></div>
  <div class="m"><div class="lb">หมวดที่ยังไม่มีสินค้า</div><div class="nm"><?php echo number_format($sum['empty']) ?></div>
    <div class="sb">ลบได้ (ปุ่มลบอยู่ในหมวดนั้น)</div></div>
</section>

<?php if ($err !== '') { ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span></div>
<?php } ?>

<section class="card">
  <div class="card-head"><div><h2>เพิ่มหมวดสินค้า</h2><span class="sub">ชื่อห้ามซ้ำ · ไม่เกิน 50 ตัวอักษร · ใช้ได้ทุกสาขา</span></div></div>
  <form class="adm-sec acct-row" method="post" action="adm-categories.php">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
    <input type="hidden" name="act" value="add">
    <label class="sr-only" for="cn">ชื่อหมวด</label>
    <input class="input adm-q" type="text" id="cn" name="name" value="<?php echo e($newName) ?>" maxlength="50" required autocomplete="off" placeholder="เช่น หูฟัง">
    <button class="btn btn-primary btn-sm" type="submit"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มหมวด</button>
  </form>
</section>

<section class="card acct-filter hist-filter">
  <form method="get" action="adm-categories.php" class="acct-row">
    <label class="sr-only" for="cq">ค้นหา</label>
    <input class="input adm-q" type="search" id="cq" name="q" value="<?php echo e($q) ?>" placeholder="ค้นชื่อหมวด / ชื่อสินค้า / SKU">
    <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-search"/></svg> ค้นหา</button>
    <?php if ($q !== '') { ?><a class="btn btn-ghost btn-sm" href="adm-categories.php">ล้าง</a><?php } ?>
  </form>
</section>

<?php if (!$rows) { ?>
  <section class="card"><p class="empty"><svg class="ico"><use href="#i-tag"/></svg>ไม่พบหมวดหรือสินค้าที่ตรงกับ “<?php echo e($q) ?>”</p></section>
<?php } ?>

<?php foreach ($rows as $c => $r) { $x = $r['meta']; ?>
  <details class="card acc-item" id="c-<?php echo e(substr(md5($c), 0, 8)) ?>"<?php echo ($q !== '' || $c === $first) ? ' open' : '' ?>>
    <summary class="card-head">
      <div>
        <h2><?php echo e($c) ?> <span class="bdg <?php echo $x['count'] ? 'bdg-adj' : 'bdg-out' ?>"><?php echo number_format($x['count']) ?> รายการ</span></h2>
        <span class="sub">
          <?php echo $x['count'] ? 'คงเหลือรวม ' . number_format($r['qty']) . ' ชิ้น · มูลค่า (ทุน) ' . e(money2($r['value'])) . ' บาท' : 'ยังไม่มีสินค้าในหมวดนี้' ?>
          <?php echo $x['added'] ? ' · เพิ่ม' . ($x['by'] !== '' ? 'โดย ' . e($x['by']) : '') . ' ' . e($x['at']) : '' ?>
        </span>
      </div>
      <span class="acc-right"><svg class="ico acc-chev" aria-hidden="true"><use href="#i-arrow"/></svg></span>
    </summary>
    <?php if ($x['count'] === 0) { ?>
      <form class="adm-sec" method="post" action="adm-categories.php">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="act" value="delete">
        <input type="hidden" name="c" value="<?php echo e($c) ?>">
        <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-trash"/></svg> ลบหมวดนี้</button>
      </form>
    <?php } ?>
    <?php if (!$r['prods']) { ?>
      <p class="empty">ยังไม่มีสินค้าในหมวดนี้</p>
    <?php } else { ?>
      <div class="tbl-wrap">
        <table class="tbl hist-all">
          <thead>
            <tr>
              <th>สินค้า</th>
              <?php foreach ($br as $bc => $b) { ?><th class="r"><?php echo e($b['short']) ?></th><?php } ?>
              <th class="r">รวม</th>
              <th class="r">ราคาทุน / ขาย</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($r['prods'] as $p) { ?>
              <tr>
                <td data-label="สินค้า"><b class="hist-t"><?php echo e($p['name']) ?></b><small class="hist-n"><?php echo e($p['sku']) ?> · จุดสั่งซื้อ <?php echo number_format($p['reorder']) ?> <?php echo e($p['unit']) ?></small></td>
                <?php foreach ($br as $bc => $b) { $st = branch_status($p, $bc); ?>
                  <td data-label="<?php echo e($b['short']) ?>" class="r num"><span class="stk stk-<?php echo e($st) ?>"><?php echo number_format(product_qty($p, $bc)) ?></span></td>
                <?php } ?>
                <td data-label="รวม" class="r num"><b><?php echo number_format(product_qty($p, 'ALL')) ?></b> <small><?php echo e($p['unit']) ?></small></td>
                <td data-label="ราคาทุน / ขาย" class="r num nowrap"><?php echo e(money2($p['cost'])) ?> / <?php echo e(money2($p['price'])) ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } ?>
  </details>
<?php } ?>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
