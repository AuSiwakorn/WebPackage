<?php
/* ==========================================================
   AOSTOCK DEMO — [ผู้ดูแล] สินค้าในสต๊อก เทียบทุกสาขา
   ----------------------------------------------------------
   แถวละ 1 สินค้า · คอลัมน์ละ 1 สาขา (เฉพาะสาขาที่เปิดใช้งาน) + รวมทุกสาขา
   สีของช่องบอกสถานะของสาขานั้น: หมด / ใกล้หมด (ต่ำกว่าจุดสั่งซื้อ) / พอใช้
   ผู้ดูแลดูอย่างเดียว — นำเข้า / เบิก / ตรวจนับ เป็นงานของพนักงาน

   ตัวกรอง (GET)
     q    = ค้นชื่อสินค้า / SKU · cat = หมวด
     st   = '' | low | out  (สินค้าที่มีอย่างน้อย 1 สาขาใกล้หมด / หมด)
     b    = รหัสสาขาที่ใช้กรองสถานะและเรียงจำนวน ('' = ดูทุกสาขารวมกัน)
     sort = urgent | name | qty | value · dir = desc | asc
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

$br    = demo_branches();
$g     = function ($k, $def = '') { return (isset($_GET[$k]) && is_string($_GET[$k])) ? trim($_GET[$k]) : $def; };
$fQ    = substr($g('q'), 0, 120);
$cats  = product_cats();
$fCat  = in_array($g('cat'), $cats, true) ? $g('cat') : '';
$fSt   = in_array($g('st'), array('low', 'out'), true) ? $g('st') : '';
$fB    = isset($br[$g('b')]) ? $g('b') : '';
$fSort = in_array($g('sort'), array('urgent', 'name', 'qty', 'value'), true) ? $g('sort') : 'urgent';
$fDir  = $g('dir') === 'asc' ? 'asc' : 'desc';

$pq = function ($chg) use ($fQ, $fCat, $fSt, $fB, $fSort, $fDir) {
    $q = array_merge(array('q' => $fQ, 'cat' => $fCat, 'st' => $fSt, 'b' => $fB, 'sort' => $fSort, 'dir' => $fDir), $chg);
    foreach (array('q' => '', 'cat' => '', 'st' => '', 'b' => '', 'sort' => 'urgent', 'dir' => 'desc') as $k => $def) {
        if ((string) $q[$k] === $def) {
            unset($q[$k]);
        }
    }
    return 'adm-products.php' . ($q ? '?' . http_build_query($q) : '');
};

/* ---------- สร้างแถว ---------- */
$rank = array('out' => 0, 'low' => 1, 'ok' => 2);
$rows = array();
$sum  = array('skus' => 0, 'qty' => 0, 'value' => 0, 'sale' => 0, 'low' => 0, 'out' => 0);
$perB = array();
foreach (array_keys($br) as $c) {
    $perB[$c] = array('qty' => 0, 'value' => 0, 'low' => 0, 'out' => 0);
}
foreach (demo_products() as $p) {
    if ($fCat !== '' && $p['cat'] !== $fCat) {
        continue;
    }
    if ($fQ !== '' && stripos($p['name'], $fQ) === false && stripos($p['sku'], $fQ) === false) {
        continue;
    }
    $cells = array();
    $tot   = 0;
    $worst = 'ok';
    foreach (array_keys($br) as $c) {
        $q  = product_qty($p, $c);
        $st = branch_status($p, $c);
        $cells[$c] = array('qty' => $q, 'st' => $st);
        $tot += $q;
        if ($rank[$st] < $rank[$worst]) {
            $worst = $st;
        }
    }
    $focus = $fB !== '' ? $cells[$fB]['st'] : $worst;
    if ($fSt !== '' && !($fSt === 'low' ? $focus !== 'ok' : $focus === 'out')) {
        continue;
    }
    foreach ($cells as $c => $x) {
        $perB[$c]['qty']   += $x['qty'];
        $perB[$c]['value'] += $x['qty'] * $p['cost'];
        if ($x['st'] !== 'ok') {
            $perB[$c][$x['st']]++;
        }
    }
    $sum['skus']++;
    $sum['qty']   += $tot;
    $sum['value'] += $tot * $p['cost'];
    $sum['sale']  += $tot * $p['price'];
    if ($worst !== 'ok') {
        $sum[$worst]++;
    }
    $rows[] = array('p' => $p, 'cells' => $cells, 'tot' => $tot, 'worst' => $worst, 'focus' => $focus,
                    'sortqty' => $fB !== '' ? $cells[$fB]['qty'] : $tot,
                    'value' => ($fB !== '' ? $cells[$fB]['qty'] : $tot) * $p['cost']);
}

$dir = $fDir === 'asc' ? 1 : -1;
usort($rows, function ($a, $b) use ($fSort, $dir, $rank) {
    if ($fSort === 'name') {
        return strcmp($a['p']['name'], $b['p']['name']) * -$dir;      // desc = ก→ฮ ตามค่าเริ่มต้นของหัวคอลัมน์
    }
    if ($fSort === 'qty' && $a['sortqty'] !== $b['sortqty']) {
        return ($a['sortqty'] < $b['sortqty'] ? -1 : 1) * $dir;
    }
    if ($fSort === 'value' && $a['value'] != $b['value']) {
        return ($a['value'] < $b['value'] ? -1 : 1) * $dir;
    }
    if ($rank[$a['focus']] !== $rank[$b['focus']]) {                     // urgent: หมด → ใกล้หมด → พอใช้
        return $rank[$a['focus']] - $rank[$b['focus']];
    }
    return $a['sortqty'] - $b['sortqty'];
});

$th = function ($k, $label, $cls = '') use ($fSort, $fDir, $pq) {
    $on   = ($fSort === $k);
    $next = ($on && $fDir === 'desc') ? 'asc' : 'desc';
    $mark = $on ? ($fDir === 'desc' ? ' ↓' : ' ↑') : '';
    return '<th class="' . $cls . '"><a class="th-sort' . ($on ? ' on' : '') . '" href="' . e($pq(array('sort' => $k, 'dir' => $next))) . '">'
         . e($label) . $mark . '</a></th>';
};

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = 'สินค้าในสต๊อก';
$PAGE_SUB       = 'เทียบยอดคงเหลือทุกสาขา ณ ตอนนี้';
$NAV_ACTIVE     = 'adm-products.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<!-- ==================== ตัวกรอง ==================== -->
<section class="card acct-filter hist-filter">
  <form method="get" action="adm-products.php" class="acct-row">
    <label class="sr-only" for="pq">ค้นหา</label>
    <input class="input adm-q" type="search" id="pq" name="q" value="<?= e($fQ) ?>" placeholder="ค้นชื่อสินค้า / SKU">
    <label class="sr-only" for="pc">หมวด</label>
    <select class="input acct-branch" id="pc" name="cat" onchange="this.form.submit()">
      <option value="">ทุกหมวด</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= e($c) ?>" <?= $fCat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
      <?php endforeach; ?>
    </select>
    <label class="sr-only" for="pb">ดูสถานะของสาขา</label>
    <select class="input acct-branch" id="pb" name="b" onchange="this.form.submit()">
      <option value="">สถานะ: ทุกสาขารวมกัน</option>
      <?php foreach ($br as $c => $x): ?>
        <option value="<?= e($c) ?>" <?= $fB === $c ? 'selected' : '' ?>>สถานะของ<?= e($x['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($fSt !== ''): ?><input type="hidden" name="st" value="<?= e($fSt) ?>"><?php endif; ?>
    <?php if ($fSort !== 'urgent'): ?><input type="hidden" name="sort" value="<?= e($fSort) ?>"><input type="hidden" name="dir" value="<?= e($fDir) ?>"><?php endif; ?>
    <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-search"/></svg> ค้นหา</button>
  </form>
</section>

<!-- ==================== สรุป ==================== -->
<section class="mini num adm-kpi" aria-label="สรุปสต๊อก">
  <div class="m"><div class="lb">สินค้า</div><div class="nm"><?= number_format($sum['skus']) ?></div>
    <div class="sb">รวม <?= number_format($sum['qty']) ?> ชิ้น ทุกสาขา</div></div>
  <div class="m"><div class="lb">มูลค่าสต๊อก (ทุน)</div><div class="nm"><?= e(money2($sum['value'])) ?></div>
    <div class="sb">ตามราคาขาย <?= e(money2($sum['sale'])) ?> บาท</div></div>
  <div class="m"><div class="lb">ต้องเติมของ</div><div class="nm"><?= number_format($sum['out']) ?> / <?= number_format($sum['low']) ?></div>
    <div class="sb">หมดอย่างน้อย 1 สาขา / ใกล้หมด</div></div>
</section>

<!-- ==================== แยกสาขา ==================== -->
<section class="card">
  <div class="card-head"><div><h2>แยกตามสาขา</h2><span class="sub">ตามตัวกรองด้านบน</span></div></div>
  <div class="tbl-wrap">
    <table class="tbl num">
      <thead><tr><th>สาขา</th><th class="r">จำนวน</th><th class="r">มูลค่า (ทุน)</th><th class="r">ใกล้หมด</th><th class="r">หมด</th></tr></thead>
      <tbody>
        <?php foreach ($perB as $c => $x): ?>
          <tr>
            <td data-label="สาขา"><a href="<?= e($pq(array('b' => $c))) ?>"><?= e($br[$c]['name']) ?></a></td>
            <td data-label="จำนวน" class="r"><?= number_format($x['qty']) ?></td>
            <td data-label="มูลค่า (ทุน)" class="r"><?= e(money2($x['value'])) ?></td>
            <td data-label="ใกล้หมด" class="r"><?= $x['low'] ? '<span class="bdg bdg-adj">' . number_format($x['low']) . '</span>' : '—' ?></td>
            <td data-label="หมด" class="r"><?= $x['out'] ? '<span class="bdg bdg-out">' . number_format($x['out']) . '</span>' : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<!-- ==================== รายการสินค้า ==================== -->
<section class="card">
  <div class="card-head">
    <div>
      <h2>ยอดคงเหลือรายสินค้า</h2>
      <span class="sub"><?= $fB !== '' ? 'สถานะและการเรียงจำนวนใช้ของ' . e($br[$fB]['name']) : 'สถานะ = สาขาที่แย่ที่สุด' ?> · ช่องสีแดง = หมด · สีเหลือง = ต่ำกว่าจุดสั่งซื้อ</span>
    </div>
  </div>
  <div class="cats">
    <a class="cat<?= $fSt === '' ? ' on' : '' ?>" href="<?= e($pq(array('st' => ''))) ?>">ทั้งหมด</a>
    <a class="cat<?= $fSt === 'low' ? ' on' : '' ?>" href="<?= e($pq(array('st' => 'low'))) ?>">ต้องเติม (ใกล้หมด + หมด)</a>
    <a class="cat<?= $fSt === 'out' ? ' on' : '' ?>" href="<?= e($pq(array('st' => 'out'))) ?>">หมดแล้ว</a>
  </div>
  <?php if (!$rows): ?>
    <p class="empty"><svg class="ico"><use href="#i-boxes"/></svg>ไม่พบสินค้าตามตัวกรอง</p>
  <?php else: ?>
    <div class="tbl-wrap">
      <table class="tbl hist-all adm-stock">
        <thead>
          <tr>
            <?= $th('name', 'สินค้า') ?>
            <?php foreach ($br as $c => $x): ?><th class="r<?= $fB === $c ? ' is-focus' : '' ?>"><?= e($x['short']) ?></th><?php endforeach; ?>
            <?= $th('qty', $fB !== '' ? 'จำนวน (' . $br[$fB]['short'] . ')' : 'รวม', 'r') ?>
            <th class="r">จุดสั่งซื้อ</th>
            <?= $th('value', 'มูลค่า (ทุน)', 'r') ?>
            <?= $th('urgent', 'สถานะ') ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): $p = $r['p']; ?>
            <tr>
              <td data-label="สินค้า">
                <b class="hist-t"><?= e($p['name']) ?></b>
                <small class="hist-n"><?= e($p['sku']) ?> · <?= e($p['cat']) ?> · ทุน <?= e(money2($p['cost'])) ?> / ขาย <?= e(money2($p['price'])) ?></small>
              </td>
              <?php foreach ($r['cells'] as $c => $x): ?>
                <td data-label="<?= e($br[$c]['short']) ?>" class="r num nowrap"><span class="stk stk-<?= e($x['st']) ?>"><?= number_format($x['qty']) ?></span></td>
              <?php endforeach; ?>
              <td data-label="รวม" class="r num nowrap"><b><?= number_format($r['sortqty']) ?></b> <small><?= e($p['unit']) ?></small></td>
              <td data-label="จุดสั่งซื้อ" class="r num"><?= number_format($p['reorder']) ?></td>
              <td data-label="มูลค่า (ทุน)" class="r num nowrap"><?= e(money2($r['value'])) ?></td>
              <td data-label="สถานะ"><span class="bdg <?= $r['focus'] === 'out' ? 'bdg-out' : ($r['focus'] === 'low' ? 'bdg-adj' : 'bdg-ok') ?>"><?= e(stock_label($r['focus'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
