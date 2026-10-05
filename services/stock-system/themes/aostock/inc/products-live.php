<?php
/**
 * FILE: themes/aostock/inc/products-live.php
 * ROLE: ส่วนที่ htmx สลับได้ของหน้ารายการสินค้า
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_product, ao_stock_category, ao_stock_balance (ผ่าน api.php — stock_rows)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] อ่าน / เขียนข้อมูลจากตาราง ao_stock_* ผ่าน api.php (ช่วงที่ 5–9)
 *   - [x] ช่วงที่ 10: แก้ File Header ให้ตรงกับระบบจริง
 *   - [x] ช่วงที่ 11: ลิงก์ไปประวัติเคลื่อนไหวตามสิทธิ์ (page_ok)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ส่วนที่ htmx สลับได้ของหน้ารายการสินค้า
   ต้องกำหนดก่อน include: $user $code $q $cat $st $sort
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$rows = stock_rows($code, $q, $cat, $st, $sort);      // ที่แสดงในตารางจริง ๆ
$view = stock_view_sum($rows);

/* การ์ดด้านบนนับจากคำค้น + หมวด แต่ยังไม่กรองสถานะ
   เพราะการ์ดเองคือปุ่มกรองสถานะ ถ้านับหลังกรองแล้วตัวเลขจะกลายเป็น 0 กดกลับไม่ได้ */
$pool = stock_view_sum(stock_rows($code, $q, $cat, '', 'name'));
$all  = stock_branch_sum($code);                      // ทั้งสาขา ไว้เทียบให้เห็นว่ากรองอยู่
$qs   = stock_qs($q, $cat, $st, $sort);
$words = stock_filter_words($q, $cat, $st);
?>
<div id="stock-live">

  <!-- ===== การ์ดสรุป = ปุ่มกรองสถานะในตัว ===== -->
  <section class="kpis num" aria-label="สรุปสินค้าที่กำลังดูอยู่"
           hx-target="#stock-live" hx-swap="outerHTML" hx-push-url="true">
    <?php
    $allU = 'products.php' . stock_qs($q, $cat, '',    $sort);
    $lowU = 'products.php' . stock_qs($q, $cat, $st === 'low' ? '' : 'low', $sort);
    $outU = 'products.php' . stock_qs($q, $cat, $st === 'out' ? '' : 'out', $sort);

    $cards = array(
        array('รายการทั้งหมด', number_format($pool['skus']), 'แตะเพื่อดูทั้งหมด',
              'i-boxes', '',                                       $allU, $st === ''),
        array('ใกล้หมด',       number_format($pool['low']),  'ถึงหรือต่ำกว่าจุดสั่งซื้อ',
              'i-alert', $pool['low'] > 0 ? ' kpi--warn' : '',     $lowU, $st === 'low'),
        array('หมดแล้ว',       number_format($pool['out']),  'ขายต่อไม่ได้จนกว่าจะรับเข้า',
              'i-ban',   $pool['out'] > 0 ? ' kpi--down' : '',     $outU, $st === 'out'),
        array('จำนวนรวม',      number_format($pool['qty']),  'ชิ้น · ทุกหน่วยนับรวมกัน',
              'i-box',   '',                                       '',    false),
    );

    foreach ($cards as $k):
        $tap = ($k[5] !== '');
        $tag = $tap ? 'a' : 'div';
    ?>
      <<?= $tag ?> class="kpi<?= $k[4] ?><?= $tap ? ' kpi--tap' : '' ?><?= $k[6] ? ' on' : '' ?>"
         <?php if ($tap): ?>href="<?= e($k[5]) ?>" hx-get="<?= e($k[5]) ?>"
         aria-pressed="<?= $k[6] ? 'true' : 'false' ?>"<?php endif; ?>>
        <div class="kpi-top">
          <span><?= e($k[0]) ?></span>
          <span class="kpi-ic"><svg class="ico"><use href="#<?= e($k[3]) ?>"/></svg></span>
        </div>
        <b><?= $k[1] ?></b>
        <small><?= e($k[2]) ?></small>
      </<?= $tag ?>>
    <?php endforeach; ?>
  </section>

  <?php if ($words): ?>
    <p class="filtered" hx-target="#stock-live" hx-swap="outerHTML" hx-push-url="true">
      <svg class="ico"><use href="#i-info"/></svg>
      <span>
        กำลังกรอง: <b><?= e(implode(' · ', $words)) ?></b> —
        เห็น <b><?= number_format($view['skus']) ?></b> รายการ
        จากทั้ง<?= e(branch_name($code)) ?> <b><?= number_format($all['skus']) ?></b> รายการ
      </span>
      <a class="btn btn-ghost btn-sm" href="products.php" hx-get="products.php">
        <svg class="ico"><use href="#i-x"/></svg> ล้างตัวกรอง
      </a>
    </p>
  <?php endif; ?>

  <!-- ===== ตัวกรอง ===== -->
  <section class="card stock-filter">
    <form class="sale-find" method="get" action="products.php"
          hx-get="products.php" hx-target="#stock-live" hx-swap="outerHTML" hx-push-url="true">
      <div class="find-in">
        <svg class="ico"><use href="#i-search"/></svg>
        <label class="sr-only" for="q">ค้นหาสินค้า</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="ชื่อสินค้า หรือ SKU"
               hx-get="products.php" hx-trigger="input changed delay:350ms, search"
               hx-include="closest form" hx-target="#stock-live" hx-swap="outerHTML"
               hx-sync="this:replace" hx-push-url="true" hx-preserve="true">
      </div>
      <?php if ($cat !== ''):  ?><input type="hidden" name="cat"  value="<?= e($cat) ?>"><?php endif; ?>
      <?php if ($st !== ''):   ?><input type="hidden" name="st"   value="<?= e($st) ?>"><?php endif; ?>
      <?php if ($sort !== ''): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>
      <button class="btn" type="submit">ค้นหา</button>
    </form>


    <div class="cats" hx-target="#stock-live" hx-swap="outerHTML" hx-push-url="true">
      <?php $u = 'products.php' . stock_qs($q, '', $st, $sort); ?>
      <a class="cat<?= $cat === '' ? ' on' : '' ?>" href="<?= e($u) ?>" hx-get="<?= e($u) ?>">ทุกหมวด</a>
      <?php foreach (product_cats() as $c): ?>
        <?php $u = 'products.php' . stock_qs($q, $c, $st, $sort); ?>
        <a class="cat<?= $cat === $c ? ' on' : '' ?>" href="<?= e($u) ?>" hx-get="<?= e($u) ?>"><?= e($c) ?></a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ===== ตาราง ===== -->
  <section class="card">
    <div class="card-head">
      <div>
        <h2>สินค้าใน<?= e(branch_name($code)) ?></h2>
        <span class="sub">
          <?= number_format($view['skus']) ?> รายการ · รวม <?= number_format($view['qty']) ?> ชิ้น
          <?= $qs !== '' ? ' (จากการกรอง)' : '' ?>
        </span>
      </div>
      <?php if (can($user, 'receive')): /* ผู้ดูแลไม่นำเข้าสินค้า — ปุ่มนี้มีเฉพาะพนักงานที่มีสิทธิ์ */ ?>
      <div class="head-act">
        <a class="btn btn-in" href="receive.php">
          <svg class="ico"><use href="#i-in"/></svg> นำเข้าสินค้า
        </a>
      </div>
      <?php endif; ?>
      <div class="sortbox" hx-target="#stock-live" hx-swap="outerHTML" hx-push-url="true">
        <label class="sr-only" for="sort">เรียงตาม</label>
        <select class="select" id="sort" name="sort"
                hx-get="products.php" hx-trigger="change"
                hx-vals='<?= e(json_encode(array('q' => $q, 'cat' => $cat, 'st' => $st))) ?>'>
          <?php foreach (stock_sorts() as $key => $lb): ?>
            <option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= e($lb) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <?php if (!$rows): ?>
      <p class="empty">
        <svg class="ico"><use href="#i-boxes"/></svg>
        ไม่พบสินค้าที่ตรงกับเงื่อนไข<br><small>ลองล้างตัวกรอง หรือพิมพ์คำค้นให้สั้นลง</small>
      </p>
    <?php else: ?>
      <div class="tbl-wrap">
        <table class="tbl tbl-stock">
          <thead>
            <tr>
              <th>สินค้า</th>
              <th>หมวด</th>
              <th class="r">คงเหลือ</th>
              <th class="r">จุดสั่งซื้อ</th>
              <th>ระดับสต๊อก</th>
              <th class="r">ราคาขาย</th>
              <th>สถานะ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <?php $p = $r['p']; ?>
              <tr class="st-<?= e($r['status']) ?>">
                <td data-label="สินค้า">
                  <span class="pcell">
                    <?= thumb_html($p) ?>
                    <span class="pcell-t">
                      <?php if (page_ok($user, 'movements.php')): ?>
                        <a class="doc doc-link" href="movements.php?sku=<?= e(rawurlencode($p['sku'])) ?>"
                           title="ดูความเคลื่อนไหวของสินค้านี้"><?= e($p['name']) ?></a>
                      <?php else: ?>
                        <span class="doc"><?= e($p['name']) ?></span>
                      <?php endif; ?>
                      <small><?= e($p['sku']) ?></small>
                    </span>
                  </span>
                </td>
                <td data-label="หมวด"><?= e($p['cat']) ?></td>
                <td data-label="คงเหลือ" class="r">
                  <b class="num qty-<?= e($r['status']) ?>"><?= number_format($r['qty']) ?></b>
                  <small><?= e($p['unit']) ?></small>
                </td>
                <td data-label="จุดสั่งซื้อ" class="r num"><?= number_format($p['reorder']) ?></td>
                <td data-label="ระดับสต๊อก">
                  <span class="lvl" title="<?= $r['pct'] ?>% ของจุดสั่งซื้อ">
                    <i class="lvl-<?= e($r['status']) ?>" style="width:<?= max(2, min(100, $r['pct'])) ?>%"></i>
                  </span>
                </td>
                <td data-label="ราคาขาย" class="r num"><?= money2(product_price($p)) ?></td>
                <td data-label="สถานะ">
                  <span class="badge b-<?= e(stock_tone($r['status'])) ?>"><?= e(stock_label($r['status'])) ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="2">รวมที่แสดงอยู่</th>
              <th class="r num"><?= number_format($view['qty']) ?></th>
              <th colspan="4"><?= number_format($view['skus']) ?> รายการ</th>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>
