<?php
/* ==========================================================
   AOSTOCK DEMO — [ผู้ดูแล] หมวดสินค้า (ดูอย่างเดียว)
   ----------------------------------------------------------
   แสดงว่ามีหมวดอะไรบ้าง แต่ละหมวดมีสินค้าอะไร กี่รายการ (แอคคอร์เดียน — เปิดหมวดแรกไว้)
   เพิ่ม / ลบหมวดเป็นงานของพนักงานที่ได้รับสิทธิ์ “จัดการหมวดสินค้า” (categories.php)
   ?q = ค้นชื่อหมวด หรือชื่อสินค้า / SKU (เปิดทุกหมวดที่เจอ)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

$q     = isset($_GET['q']) && is_string($_GET['q']) ? substr(trim($_GET['q']), 0, 120) : '';
$br    = demo_branches();
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
$PAGE_SUB       = $sum['cats'] . ' หมวด · สินค้า ' . $sum['items'] . ' รายการ · เพิ่ม / ลบหมวดโดยพนักงานที่ได้รับสิทธิ์';
$NAV_ACTIVE     = 'adm-categories.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<section class="mini num adm-kpi" aria-label="สรุปหมวดสินค้า">
  <div class="m"><div class="lb">หมวดสินค้า</div><div class="nm"><?= number_format($sum['cats']) ?></div>
    <div class="sb"><?= $sum['added'] ? 'พนักงานเพิ่มเอง ' . number_format($sum['added']) . ' หมวด' : 'หมวดตั้งต้นทั้งหมด' ?></div></div>
  <div class="m"><div class="lb">สินค้าทั้งหมด</div><div class="nm"><?= number_format($sum['items']) ?></div>
    <div class="sb">เฉลี่ย <?= $sum['cats'] ? number_format($sum['items'] / $sum['cats'], 1) : 0 ?> รายการ / หมวด</div></div>
  <div class="m"><div class="lb">หมวดที่ยังไม่มีสินค้า</div><div class="nm"><?= number_format($sum['empty']) ?></div>
    <div class="sb">ลบได้โดยพนักงานที่มีสิทธิ์</div></div>
</section>

<section class="card acct-filter hist-filter">
  <form method="get" action="adm-categories.php" class="acct-row">
    <label class="sr-only" for="cq">ค้นหา</label>
    <input class="input adm-q" type="search" id="cq" name="q" value="<?= e($q) ?>" placeholder="ค้นชื่อหมวด / ชื่อสินค้า / SKU">
    <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-search"/></svg> ค้นหา</button>
    <?php if ($q !== ''): ?><a class="btn btn-ghost btn-sm" href="adm-categories.php">ล้าง</a><?php endif; ?>
  </form>
</section>

<?php if (!$rows): ?>
  <section class="card"><p class="empty"><svg class="ico"><use href="#i-tag"/></svg>ไม่พบหมวดหรือสินค้าที่ตรงกับ “<?= e($q) ?>”</p></section>
<?php endif; ?>

<?php foreach ($rows as $c => $r): $x = $r['meta']; ?>
  <details class="card acc-item" id="c-<?= e(substr(md5($c), 0, 8)) ?>"<?= ($q !== '' || $c === $first) ? ' open' : '' ?>>
    <summary class="card-head">
      <div>
        <h2><?= e($c) ?> <span class="bdg <?= $x['count'] ? 'bdg-adj' : 'bdg-out' ?>"><?= number_format($x['count']) ?> รายการ</span></h2>
        <span class="sub">
          <?= $x['count'] ? 'คงเหลือรวม ' . number_format($r['qty']) . ' ชิ้น · มูลค่า (ทุน) ' . e(money2($r['value'])) . ' บาท' : 'ยังไม่มีสินค้าในหมวดนี้' ?>
          <?= $x['added'] ? ' · เพิ่มโดย ' . e($x['by']) . ' ' . e($x['at']) : '' ?>
        </span>
      </div>
      <span class="acc-right"><svg class="ico acc-chev" aria-hidden="true"><use href="#i-arrow"/></svg></span>
    </summary>
    <?php if (!$r['prods']): ?>
      <p class="empty">ยังไม่มีสินค้าในหมวดนี้</p>
    <?php else: ?>
      <div class="tbl-wrap">
        <table class="tbl hist-all">
          <thead>
            <tr>
              <th>สินค้า</th>
              <?php foreach ($br as $bc => $b): ?><th class="r"><?= e($b['short']) ?></th><?php endforeach; ?>
              <th class="r">รวม</th>
              <th class="r">ราคาทุน / ขาย</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($r['prods'] as $p): ?>
              <tr>
                <td data-label="สินค้า"><b class="hist-t"><?= e($p['name']) ?></b><small class="hist-n"><?= e($p['sku']) ?> · จุดสั่งซื้อ <?= number_format($p['reorder']) ?> <?= e($p['unit']) ?></small></td>
                <?php foreach ($br as $bc => $b): $st = branch_status($p, $bc); ?>
                  <td data-label="<?= e($b['short']) ?>" class="r num"><span class="stk stk-<?= e($st) ?>"><?= number_format(product_qty($p, $bc)) ?></span></td>
                <?php endforeach; ?>
                <td data-label="รวม" class="r num"><b><?= number_format(product_qty($p, 'ALL')) ?></b> <small><?= e($p['unit']) ?></small></td>
                <td data-label="ราคาทุน / ขาย" class="r num nowrap"><?= e(money2($p['cost'])) ?> / <?= e(money2($p['price'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </details>
<?php endforeach; ?>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
