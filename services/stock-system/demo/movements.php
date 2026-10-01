<?php
/* ==========================================================
   AOSTOCK DEMO — ประวัติเคลื่อนไหวรายสินค้า
   ----------------------------------------------------------
   ซ้าย: เลือกสินค้า · ขวา: ทุกครั้งที่ยอดของสินค้านั้นขยับ
   (ขาย รับเข้า ตัดออก ตรวจนับ และการยกเลิก) พร้อมยอดคงเหลือหลังแต่ละรายการ
   ต่างจาก "ประวัติการทำรายการ" ที่ดูทั้งสาขาตามเวลา — หน้านี้ดูทีละสินค้า
   ========================================================== */

require_once dirname(__FILE__) . '/inc/auth.php';
require_once dirname(__FILE__) . '/inc/store.php';
require_once dirname(__FILE__) . '/inc/movement.php';

$user = require_login();
$code = work_branch($user);          // ผู้ดูแลเลือกสาขาได้จากแถบบน

$isHx   = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
$q      = isset($_GET['q'])   ? trim($_GET['q'])   : '';
$cat    = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$sku    = isset($_GET['sku']) ? trim($_GET['sku']) : '';
$period = isset($_GET['p'])   ? trim($_GET['p'])   : '7';
$pers   = move_periods();
if (!isset($pers[$period])) { $period = '7'; }

$prod = ($sku !== '') ? product_by_sku($sku) : null;
if ($prod === null) { $sku = ''; }

/* ---------- รายการสินค้าทางซ้าย ---------- */
$list = sale_products($code, $q, $cat);

if ($isHx) {
    require dirname(__FILE__) . '/inc/movement-list.php';
    exit;
}

if ($prod !== null) {
    $now  = product_qty($prod, $code);
    $view = move_view(move_rows($code, $prod), $period, $now);
}

$branch     = $code;
$PAGE_TITLE = 'ประวัติเคลื่อนไหว';
$PAGE_SUB   = branch_name($code) . ' · ดูทีละสินค้า ว่ายอดขยับเพราะอะไร เมื่อไร โดยใคร';
$NAV_ACTIVE = 'movements.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<div class="mv-wrap<?= $prod !== null ? ' has-pick' : '' ?>">

  <!-- ==================== เลือกสินค้า ==================== -->
  <section class="card mv-pick">
    <div class="card-head">
      <div>
        <h2>เลือกสินค้า</h2>
        <span class="sub"><?= count(demo_products()) ?> รายการในสาขา</span>
      </div>
    </div>
    <div class="mv-find">
      <div class="find-in">
        <svg class="ico"><use href="#i-search"/></svg>
        <label class="sr-only" for="mq">ค้นหาสินค้า</label>
        <input type="search" id="mq" name="q" value="<?= e($q) ?>" placeholder="ชื่อสินค้า หรือ SKU"
               hx-get="movements.php" hx-trigger="input changed delay:300ms, search"
               hx-include="#mcat" hx-vals='<?= e(json_encode(array('sku' => $sku, 'p' => $period))) ?>'
               hx-target="#mv-list" hx-swap="outerHTML" hx-sync="this:replace">
      </div>
      <label class="sr-only" for="mcat">หมวดสินค้า</label>
      <select class="input mv-cat" id="mcat" name="cat"
              hx-get="movements.php" hx-trigger="change" hx-include="#mq"
              hx-vals='<?= e(json_encode(array('sku' => $sku, 'p' => $period))) ?>'
              hx-target="#mv-list" hx-swap="outerHTML">
        <option value="">ทุกหมวด</option>
        <?php foreach (product_cats() as $c): ?>
          <option value="<?= e($c) ?>" <?= $cat === $c ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php require dirname(__FILE__) . '/inc/movement-list.php'; ?>
  </section>

  <!-- ==================== ความเคลื่อนไหว ==================== -->
  <section class="card mv-detail">
    <?php if ($prod === null): ?>
      <p class="empty mv-empty">
        <svg class="ico"><use href="#i-activity"/></svg>
        เลือกสินค้าทางซ้าย<br>
        <small>จะเห็นทุกครั้งที่ยอดของสินค้านั้นขยับ — ขาย รับเข้า ตัดออก ตรวจนับ<br>
               พร้อมยอดคงเหลือหลังแต่ละรายการ</small>
      </p>
    <?php else: ?>
      <?php $s = $view['sum']; ?>
      <div class="mv-head">
        <a class="mv-back" href="movements.php<?= e(move_qs($q, $cat, '', $period)) ?>">
          <svg class="ico"><use href="#i-arrow"/></svg> เลือกสินค้าอื่น
        </a>
        <div class="mv-prod">
          <?= thumb_html($prod, 'thumb--lg') ?>
          <div>
            <small><?= e($prod['sku']) ?> · <?= e($prod['cat']) ?></small>
            <h2><?= e($prod['name']) ?></h2>
            <span class="mv-now">คงเหลือตอนนี้ <b class="num"><?= number_format($view['close']) ?></b> <?= e($prod['unit']) ?></span>
          </div>
        </div>
        <div class="segs mv-per">
          <?php foreach ($pers as $k => $label): ?>
            <a class="seg<?= $period === $k ? ' on' : '' ?>" href="movements.php<?= e(move_qs($q, $cat, $sku, $k)) ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <?php
      /* สมการยอด: ยกมา + รับเข้า − ขาย − ตัดออก ± ปรับยอด = คงเหลือ */
      $cells = array(
          array('ยกมา',    $view['open'],  'b', ''),
          array('รับเข้า',  $s['receive'],  's', 'in'),
          array('ขาย',     $s['sale'],     's', 'sale'),
          array('ตัดออก',  $s['issue'],    's', 'out'),
          array('ปรับยอด', $s['adjust'],   's', 'adj'),
          array('คงเหลือ',  $view['close'], 'b', 'end'),
      );
      ?>
      <div class="mv-eq num" aria-label="สรุปยอดในช่วงที่เลือก">
        <?php foreach ($cells as $c): ?>
          <div class="mv-c<?= $c[3] !== '' ? ' c-' . $c[3] : '' ?>">
            <span><?= e($c[0]) ?></span>
            <b><?php
              if ($c[2] === 'b')     { echo number_format($c[1]); }
              elseif ($c[1] > 0)     { echo '+' . number_format($c[1]); }
              elseif ($c[1] < 0)     { echo '−' . number_format(-$c[1]); }
              else                   { echo '0'; }
            ?></b>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (!$view['rows']): ?>
        <p class="empty">
          <svg class="ico"><use href="#i-activity"/></svg>
          ไม่มีความเคลื่อนไหวใน<?= e($period === 'today' ? 'วันนี้' : ' ' . $pers[$period] . 'ล่าสุด') ?><br>
          <small>ยอดคงที่อยู่ที่ <?= number_format($view['close']) ?> <?= e($prod['unit']) ?> · ลองเลือกช่วงเวลาที่ยาวขึ้น</small>
        </p>
      <?php else: ?>
        <div class="tbl-wrap">
          <table class="tbl tbl-mv">
            <thead>
              <tr>
                <th>เมื่อ</th>
                <th>รายการ</th>
                <th>โดย</th>
                <th class="r">เข้า / ออก</th>
                <th class="r">คงเหลือ</th>
              </tr>
            </thead>
            <tbody>
              <?php $lastDay = ''; ?>
              <?php foreach ($view['rows'] as $r): ?>
                <?php $m = move_type_of($r['type']); ?>
                <tr class="mv-r tone-<?= e($m['tone']) ?><?= date('Ymd', $r['ts']) === date('Ymd') ? ' is-today' : '' ?>">
                  <td data-label="เมื่อ" class="mv-when"><?= e(move_when($r['ts'])) ?></td>
                  <td data-label="รายการ">
                    <span class="badge b-<?= e($m['tone']) ?>"><?= e($m['label']) ?></span>
                    <span class="doc"><?= e($r['doc']) ?></span>
                    <?php if ($r['note'] !== ''): ?><small><?= e($r['note']) ?></small><?php endif; ?>
                  </td>
                  <td data-label="โดย"><?= e($r['by']) ?></td>
                  <td data-label="เข้า / ออก" class="r num">
                    <?php if ($r['delta'] > 0): ?>
                      <b class="mv-up">+<?= number_format($r['delta']) ?></b>
                    <?php elseif ($r['delta'] < 0): ?>
                      <b class="mv-dn">−<?= number_format(-$r['delta']) ?></b>
                    <?php else: ?>
                      <span class="mv-eq0">0</span>
                    <?php endif; ?>
                  </td>
                  <td data-label="คงเหลือ" class="r num"><b><?= number_format($r['bal']) ?></b></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="mv-foot">
          ข้อมูลของวันนี้มาจากรายการที่ทำจริงในเดโม ส่วนวันก่อนหน้าเป็นข้อมูลสมมติ
        </p>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
