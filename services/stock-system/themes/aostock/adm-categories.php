<?php
/**
 * FILE: themes/aostock/adm-categories.php
 * ROLE: [ผู้ดูแล] หมวดสินค้า — ดูสินค้าในแต่ละหมวด · เพิ่ม / เปลี่ยนชื่อ / ลบหมวด
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_category, ao_stock_product, ao_stock_log (ผ่าน api.php — cat_registry / cat_add / Stock_doRenameCategory / cat_delete)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] อ่าน / เขียนข้อมูลจากตาราง ao_stock_* ผ่าน api.php (ช่วงที่ 5–9)
 *   - [x] ช่วงที่ 10: แก้ File Header ให้ตรงกับระบบจริง
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 *   - [x] 12 ต.ค.: ปุ่ม “ลบหมวดนี้” ถามยืนยันก่อนลบ (เดิมลบเลย)
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 4): ตารางของแต่ละหมวดครั้งละ 60 แถว + ปุ่ม “แสดงเพิ่ม” (htmx ?c=&from=) · หมวดที่พับอยู่โหลดตารางตอนกดเปิด (?part=table)
 *         — เดิมวาดสินค้าทุกตัวทุกหมวด (3,000 ตัว = 2.6 MB)
 *   - [x] 21 ต.ค. (ผลทดสอบ): ถามก่อนลบด้วยกล่องยืนยันของระบบ (data-confirm="ask") แทน confirm() ของเบราว์เซอร์
 *   - [x] 3 พ.ย. (ผู้ใช้ขอ): เปลี่ยนชื่อหมวดได้ — ช่องชื่อ + ปุ่มเปลี่ยนชื่อในแต่ละหมวด
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   AOSTOCK — [ผู้ดูแล] หมวดสินค้า
   ----------------------------------------------------------
   แสดงว่ามีหมวดอะไรบ้าง แต่ละหมวดมีสินค้าอะไร กี่รายการ (แอคคอร์เดียน — เปิดหมวดแรกไว้)
   ผู้ดูแลเพิ่ม / เปลี่ยนชื่อ / ลบหมวดได้ที่นี่ (ระบบใหม่ต้องมีหมวดก่อนเพิ่มสินค้า) · พนักงานที่มีสิทธิ์เพิ่ม / ลบหมวดทำได้ที่ categories.php
   เปลี่ยนชื่อ = ผู้ดูแลเท่านั้น · สินค้าในหมวดตามไปเองทุกสาขา · ทำเสร็จกลับมาที่หมวดนั้น (เปิดไว้ + ข้อความแจ้งผลในหมวด)
   ลบได้เฉพาะหมวดที่ไม่มีสินค้าอ้างถึงเลย (รวมสินค้าที่เลิกขาย)
   ?q = ค้นชื่อหมวด หรือชื่อสินค้า / SKU (เปิดทุกหมวดที่เจอ)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

/* ---------- เพิ่ม / ลบหมวด (cat_add / cat_delete ใน api.php — ลงประวัติของสาขาที่ผู้ดูแลเลือกอยู่) ---------- */
$err     = '';
$newName = '';
$renOf   = '';                           // หมวดที่เพิ่งกดเปลี่ยนชื่อ (ไม่สำเร็จ = เปิดหมวดนั้นไว้ + ชื่อที่พิมพ์ยังอยู่ในช่อง)
$renVal  = '';
$renErr  = '';
$hid     = function ($c) { return 'c-' . substr(md5((string) $c), 0, 8); };
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
    } elseif ($act === 'rename') {
        $renOf  = isset($_POST['c']) && is_string($_POST['c']) ? $_POST['c'] : '';
        $renVal = isset($_POST['name']) && is_string($_POST['name']) ? $_POST['name'] : '';
        $was    = cat_registry();
        $renErr = Stock_doRenameCategory($renOf, $renVal, $user, $code);
        if ($renErr === '') {
            $nn = trim((string) preg_replace('/\s+/u', ' ', $renVal));
            $n  = isset($was[$renOf]) ? $was[$renOf]['count'] : 0;
            $_SESSION['cat_done'] = array('c' => $nn, 'msg' => 'เปลี่ยนชื่อหมวด “' . $renOf . '” เป็น “' . $nn . '” แล้ว'
                . ($n ? ' — สินค้า ' . number_format($n) . ' รายการในหมวดนี้ตามไปด้วย ทุกสาขา' : ''));
            header('Location: ' . url('adm-categories.php') . '#' . $hid($nn));
            exit;
        }
    } elseif ($act === 'delete') {
        $del = isset($_POST['c']) ? (string) $_POST['c'] : '';
        $err = cat_delete($del, $user, $code);
        if ($err === '') {
            $_SESSION['flash'] = 'ลบหมวด “' . $del . '” แล้ว';
        }
    }
    if ($err === '' && $renErr === '') {
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

/* หมวดที่เปิดไว้ให้: เพิ่งเปลี่ยนชื่อสำเร็จ (ข้อความแจ้งผลอ่านครั้งเดียว) หรือเปลี่ยนไม่สำเร็จ · หมวดหายไปแล้ว = แจ้งด้านบน */
$catDone = isset($_SESSION['cat_done']) && is_array($_SESSION['cat_done']) ? $_SESSION['cat_done'] : null;
unset($_SESSION['cat_done']);
$openCat = ($renErr !== '') ? $renOf : ($catDone !== null ? (string) $catDone['c'] : null);
if ($renErr !== '' && !isset($rows[$renOf])) {
    $err    = $renErr;
    $renErr = '';
}

/* แถวสินค้าในตารางของหมวด — ใช้ทั้งตอนวาดหน้า และตอนกด “แสดงเพิ่ม” */
$row = function ($p) use ($br) { ?>
              <tr>
                <td data-label="สินค้า"><b class="hist-t"><?php echo e($p['name']) ?></b><small class="hist-n"><?php echo e($p['sku']) ?> · จำนวนที่ระบบจะแจ้งเตือนสินค้าใกล้หมด <?php echo number_format($p['reorder']) ?> <?php echo e($p['unit']) ?></small></td>
                <?php foreach ($br as $bc => $b) { $st = branch_status($p, $bc); ?>
                  <td data-label="<?php echo e($b['short']) ?>" class="r num"><span class="stk stk-<?php echo e($st) ?>"><?php echo number_format(product_qty($p, $bc)) ?></span></td>
                <?php } ?>
                <td data-label="รวม" class="r num"><b><?php echo number_format(product_qty($p, 'ALL')) ?></b> <small><?php echo e($p['unit']) ?></small></td>
                <td data-label="ราคาทุน / ขาย" class="r num nowrap"><?php echo e(money2($p['cost'])) ?> / <?php echo e(money2($p['price'])) ?></td>
              </tr>
<?php
};
/* ตารางของแต่ละหมวดแสดงครั้งละ 60 แถว + ปุ่ม “แสดงเพิ่ม” (งานความเร็ว ช่วง 4)
   หมวดที่พับอยู่ยังไม่วาดตาราง — โหลดตอนกดเปิด (htmx ?c=หมวด&part=table) · htmx ?c=หมวด&from=N = แค่แถวชุดถัดไป */
list($from, $end) = Stock_getListRange();
$cols    = count($br) + 3;
$moreUrl = function ($c) use ($q) { return 'adm-categories.php?c=' . rawurlencode($c) . ($q !== '' ? '&q=' . rawurlencode($q) : ''); };
$table   = function ($c, $r) use ($br, $row, $cols, $moreUrl) { ?>
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
            <?php foreach (array_slice($r['prods'], 0, PRODUCT_PAGE_SIZE) as $p) { $row($p); } ?>
            <?php Stock_renderMoreButton(PRODUCT_PAGE_SIZE, count($r['prods']), $moreUrl($c), $cols); ?>
          </tbody>
        </table>
      </div>
<?php
};
if (isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true' && (isset($_GET['from']) || isset($_GET['part']))) {
    $c = (isset($_GET['c']) && is_string($_GET['c'])) ? $_GET['c'] : '';
    if (isset($rows[$c]) && isset($_GET['part'])) {
        $table($c, $rows[$c]);
    } elseif (isset($rows[$c])) {
        foreach (array_slice($rows[$c]['prods'], $from, $end - $from) as $p) {
            $row($p);
        }
        Stock_renderMoreButton($end, count($rows[$c]['prods']), $moreUrl($c), $cols);
    }
    exit;
}

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$CONFIRM_MODAL  = true;                  // ถามก่อนลบหมวดด้วยกล่องยืนยันของระบบ (data-confirm="ask" · 21 ต.ค.)
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

<?php /* เข้าหน้ามาพับทุกหมวด · ตอนค้นหาเปิดทุกหมวดที่เจอ · เพิ่งเปลี่ยนชื่อ = เปิดหมวดนั้น */ ?>
<?php foreach ($rows as $c => $r) { $x = $r['meta']; $c = (string) $c; $isOpen = ($q !== '' || $c === $openCat); ?>
  <details class="card acc-item" id="<?php echo e($hid($c)) ?>"<?php echo $isOpen ? ' open' : '' ?>>
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
    <div class="adm-sec cat-edit">
      <?php if ($renErr !== '' && $c === $renOf) { ?>
        <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($renErr) ?></span></div>
      <?php } elseif ($catDone !== null && $c === $openCat) { ?>
        <div class="alert alert-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span><?php echo e($catDone['msg']) ?></span></div>
      <?php } ?>
      <div class="acct-row">
        <form class="acct-row cat-ren" method="post" action="adm-categories.php#<?php echo e($hid($c)) ?>">
          <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
          <input type="hidden" name="act" value="rename">
          <input type="hidden" name="c" value="<?php echo e($c) ?>">
          <label class="sr-only" for="<?php echo e('r' . $hid($c)) ?>">ชื่อหมวด</label>
          <input class="input adm-q" type="text" id="<?php echo e('r' . $hid($c)) ?>" name="name" maxlength="50" required autocomplete="off"
                 value="<?php echo e(($renErr !== '' && $c === $renOf) ? $renVal : $c) ?>">
          <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-edit"/></svg> เปลี่ยนชื่อ</button>
        </form>
        <?php if ($x['count'] === 0) { ?>
          <?php /* ถามก่อนลบแบบเดียวกับหน้าหมวดสินค้าฝั่งพนักงาน */ ?>
          <form class="cat-del" method="post" action="adm-categories.php" data-confirm="ask" data-ask="<?php echo e('ลบหมวด “' . $c . '” ?') ?>"
                data-ask-sub="หมวดนี้ยังไม่มีสินค้า · ลบแล้วเพิ่มกลับได้ภายหลัง" data-ask-ok="ลบหมวด">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
            <input type="hidden" name="act" value="delete">
            <input type="hidden" name="c" value="<?php echo e($c) ?>">
            <button class="btn btn-ghost btn-sm" type="submit"><svg class="ico"><use href="#i-trash"/></svg> ลบหมวดนี้</button>
          </form>
        <?php } ?>
      </div>
      <small class="adm-hint">เปลี่ยนชื่อแล้วทุกสาขาเห็นชื่อใหม่ทันที · สินค้าในหมวดนี้ตามไปด้วย ไม่ต้องย้ายทีละตัว</small>
    </div>
    <?php if (!$r['prods']) { ?>
      <p class="empty">ยังไม่มีสินค้าในหมวดนี้</p>
    <?php } elseif ($isOpen) { ?>
      <?php $table($c, $r); ?>
    <?php } else { /* หมวดที่ยังพับอยู่ — โหลดตารางตอนกดเปิด (เหตุการณ์ toggle ของ details) */ ?>
      <div class="lazy-tbl" hx-get="<?php echo e($moreUrl($c) . '&part=table') ?>" hx-trigger="toggle once from:closest details" hx-swap="outerHTML">
        <p class="empty">กำลังโหลดรายการสินค้า…</p>
      </div>
    <?php } ?>
  </details>
<?php } ?>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
