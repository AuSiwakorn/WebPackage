<?php
/**
 * FILE: themes/aostock/receive.php
 * ROLE: นำเข้าสินค้า (รับของเข้าสต๊อกสาขา)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/receive-live.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_receive, ao_stock_receive_item, ao_stock_move, ao_stock_balance, ao_stock_doc_seq, ao_stock_log (ผ่าน api.php) · ใบที่กำลังทำ (ร่าง) อยู่ใน $_SESSION['recv_draft']
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ใบที่บันทึกแล้วเก็บในตาราง ao_stock_* (ช่วงที่ 6) · บันทึกไม่ผ่านแสดงข้อความแทนหน้า error
 *   - [x] ช่วงที่ 11: แก้ / ยกเลิกใบของตัวเองต้องมีสิทธิ์ doc_fix · ปุ่มแก้ / ยกเลิกและลิงก์ยอดคงเหลือแสดงตามสิทธิ์
 *   - [x] ช่วงที่ 16: ปุ่ม "เพิ่มสินค้าใหม่" (สิทธิ์ product_add) → หน้าต่างฟอร์ม · บันทึกแล้วใส่เข้าใบ 1 ชิ้นทันที
 *         · SKU ตั้งให้อัตโนมัติ (ค่าเริ่มต้น) หรือกรอกเอง · หมวดใหม่ได้ถ้ามีสิทธิ์เพิ่มหมวด · ระบบยังไม่มีสินค้า = กล่อง "เพิ่มสินค้าแรก"
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 *   - [x] 8 ต.ค.: ยังกรอกไม่ครบแล้วกดบันทึก → เลื่อนไปช่องแรกที่ขาด กะพริบ + ข้อความ (data-need · aoNeedSync ใน inc/footer.php)
 *   - [x] 12 ต.ค.: ช่อง “ผู้รับเข้า” ขึ้น “พนักงาน” แม้เป็นผู้จัดการสาขา → ใช้ user_role_label() แบบเดียวกับแถบบน
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 3): htmx แตะสินค้า / ปุ่มในใบ ส่งกลับแค่ใบ + การ์ดที่แตะ ($LIVE_PART / $OOB_SKUS)
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 4): การ์ดครั้งละ 60 ใบ — htmx ?from= ส่งกลับแค่ชุดถัดไป
 *   - [x] 14 ต.ค.: ปุ่มบันทึกใบ data-qty-guard — กด + / − แล้วกดบันทึกทันที ระบบส่งจำนวนที่ค้างก่อน (inc/footer.php)
 *   - [x] 15 ต.ค. (ใบนับแบบรายการ ช่วง 5): เปลี่ยนชื่อ “ตรวจนับ / ปรับยอด” เป็น “ตรวจนับสต๊อก” (ข้อความยกเลิกไม่ได้)
 *   - [x] 20 ต.ค.: บันทึกใบเทียบจำนวนบนจอ (ช่องซ่อน screen_qty) กับร่างก่อน — ไม่ตรง = ไม่บันทึก แจ้งแถวที่ต่าง (Stock_checkScreenQty)
 *         · เดิมบันทึกจากร่างใน session เลย ถ้าคำขอบันทึกจำนวนเบื้องหลังยังไม่ถึง ใบได้จำนวนเก่า ทั้งที่กล่องยืนยันโชว์จำนวนใหม่
 *         · act=set อ่านชื่อช่องแบบที่ PHP เปลี่ยน . เป็น _ — SKU ที่มีจุดแก้จำนวนแล้วแถวไม่หาย
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   นำเข้าสินค้า (รับของเข้าสต๊อกสาขา)
   ----------------------------------------------------------
   วิธีทำงาน: ค้นหา → แตะเพิ่มเข้าใบ → ปรับจำนวน → บันทึกทั้งใบครั้งเดียว
   ของมาเป็นใบส่งของ จึงควรเป็นเอกสารรับเข้าใบเดียว เลขอ้างอิงเดียว
   ยังไม่กดบันทึก = ยังไม่แตะสต๊อก เลิกกลางคันได้

   ใบที่กำลังทำอยู่ใน $_SESSION['recv_draft'] (ร่าง)
   บันทึกแล้ว: ใบรับเข้า + stock_move + ยอดใน stock_balance + ประวัติ ในทรานแซกชันเดียว (receive_save)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();
$code = work_branch($user);          // ผู้ดูแลเลือกสาขาได้จากแถบบน

/* งานคลังไม่ผูกกับการเปิดร้าน — ของมาส่งเช้าก่อนเปิดร้านก็รับเข้าได้
   มีแค่หน้าขายสินค้าที่ต้องเปิดร้านก่อน (เพราะเกี่ยวกับลิ้นชักเงินสด) */

$isHx        = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
$q           = isset($_GET['q'])   ? trim($_GET['q'])   : '';
$cat         = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$err         = '';
$done        = null;
$oldRef  = '';
$oldNote = '';
$voided  = null;
$npErr   = '';                       // ข้อผิดพลาดของฟอร์มเพิ่มสินค้าใหม่ (ช่วงที่ 16)
$npBad   = '';                       // รูปไม่ผ่าน (ยังไม่เพิ่มสินค้า) — ข้อความแดงใต้ช่องรูป
/* htmx ส่งกลับส่วนไหน (งานความเร็ว ช่วง 3): แตะสินค้า / ปุ่มในใบ = แค่ใบ + การ์ดที่แตะ · ค้นหา / เลือกหมวด / แก้ไข / ยกเลิก = ทั้งส่วน */
$LIVE_PART = 'all';
$OOB_SKUS  = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$_POST && !empty($_SERVER['CONTENT_LENGTH'])) {          // ไฟล์ใหญ่เกิน post_max_size → PHP ทิ้งทั้งฟอร์ม (มีแค่ฟอร์มเพิ่มสินค้าใหม่ที่ส่งไฟล์)
        $big = 'ไฟล์ที่ส่งมาใหญ่เกินที่เซิร์ฟเวอร์รับได้ — ยังไม่ได้เพิ่มสินค้า ลองเลือกรูปที่เล็กลงแล้วกดเพิ่มอีกครั้ง';
        if (can($user, 'product_add')) { $npErr = $big; } else { $err = $big; }
    } elseif (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่';
    } else {
        $act = isset($_POST['act']) ? $_POST['act'] : '';
        $sku = isset($_POST['sku']) ? trim($_POST['sku']) : '';
        if (in_array($act, array('add', 'minus', 'set', 'del'), true)) {
            $LIVE_PART = 'cart';
            $OOB_SKUS  = array($sku);
        } elseif ($act === 'clear') {
            $LIVE_PART = 'cart';
            $OOB_SKUS  = array_keys(isset($_SESSION['recv_draft']) && is_array($_SESSION['recv_draft']) ? $_SESSION['recv_draft'] : array());
        }

        if ($act === 'newprod') {
            /* ช่วงที่ 16: เพิ่มสินค้าใหม่ → ใส่เข้าใบ 1 ชิ้นทันที · ไม่ผ่าน = เปิดหน้าต่างค้างไว้พร้อมข้อความและค่าที่กรอก */
            $npBad = can($user, 'product_add') ? Stock_checkProductImage('image') : '';   // รูปไม่ผ่าน = ยังไม่เพิ่มสินค้า
            $np    = ($npBad !== '') ? array('error' => 'ยังไม่ได้เพิ่มสินค้า — ' . $npBad . ' · เลือกรูปใหม่ (หรือไม่ใส่รูป) แล้วกดเพิ่มอีกครั้ง')
                                     : Stock_doAddProduct($user, $code);
            if (isset($np['error']) && !can($user, 'product_add')) {
                $err = $np['error'];                                   // ไม่มีสิทธิ์ = ไม่มีหน้าต่างฟอร์ม → แสดงด้านบนแทน
            } elseif (isset($np['error'])) {
                $npErr = $np['error'];
            } else {
                rdraft_add($np['sku'], 1);
                $img = product_image_store($np['sku'], 'image', '');
                $_SESSION['flash'] = 'เพิ่มสินค้าใหม่ “' . $np['name'] . '” (SKU ' . $np['sku'] . ') แล้ว และใส่ในใบนี้ 1 ชิ้น — แก้จำนวนได้ในรายการของใบนี้'
                                   . (isset($img['error']) ? ' · แต่รูปบันทึกไม่ผ่าน (' . $img['error'] . ') ให้ผู้ดูแลเพิ่มรูปที่หน้าแก้ไขสินค้า' : '');
                header('Location: ' . url('receive.php'));
                exit;
            }
        } elseif ($act === 'add') {
            rdraft_add($sku, 1);
        } elseif ($act === 'minus') {
            rdraft_add($sku, -1);
        } elseif ($act === 'set') {
            $k = strtr('rq_' . $sku, '. ', '__');    // PHP เปลี่ยน . ในชื่อช่องเป็น _ (SKU กรอกเองมีจุดได้ — เดิมอ่านไม่เจอ = 0 แถวหายจากใบ)
            rdraft_set($sku, isset($_POST[$k]) ? preg_replace('/\D/', '', $_POST[$k]) : 0);
        } elseif ($act === 'del') {
            rdraft_remove($sku);
        } elseif ($act === 'clear') {
            rdraft_clear();
        } elseif ($act === 'void' || $act === 'edit') {
            /* ยกเลิกใบที่บันทึกไปแล้ว — ต้องมีหมายเหตุเสมอ
               act=edit คือยกเลิกแล้วดึงรายการเดิมกลับเข้าใบเพื่อแก้ */
            $no     = isset($_POST['no']) ? trim($_POST['no']) : '';
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            $redo   = ($act === 'edit');

            $chk    = receive_by_no($code, $no);

            if ($chk !== null && !can_void_doc($user, $chk)) {
                $err = ($chk['by_user'] === $user['username'])
                     ? 'ไม่มีสิทธิ์ “แก้เอกสารคลังตัวเอง” — ติดต่อผู้ดูแลเพื่อเปิดสิทธิ์'
                     : 'ใบรับเข้านี้เป็นของพนักงานคนอื่น — ต้องมีสิทธิ์แก้งานคนอื่นจึงจะแก้หรือยกเลิกได้';
            } elseif ($reason === '') {
                $err = 'การยกเลิกหรือแก้ไขใบรับเข้าต้องระบุหมายเหตุทุกครั้ง';
            } elseif ($redo && rdraft_count() > 0) {
                $err = 'ยังมีใบที่ทำค้างอยู่ บันทึกหรือล้างใบนั้นก่อนจึงจะแก้ใบเก่าได้';
            } else {
                $v = receive_void($code, $no, $user, $reason, $redo);
                if ($v === null) {
                    $err = 'ไม่พบใบนี้ หรือถูกยกเลิกไปแล้ว';
                } elseif (isset($v['error']) && $v['error'] === 'sold') {
                    $err = 'ยกเลิกไม่ได้ เพราะของบางส่วนถูกขายออกไปแล้ว — '
                         . implode(' · ', $v['items'])
                         . ' · กรณีนี้ให้ตรวจนับสต๊อกแทน';
                } else {
                    $voided = $v;
                    if ($redo) {
                        rdraft_from_receive($v);
                    }
                }
            }
        } elseif ($act === 'save') {
            $ref  = isset($_POST['ref'])  ? trim($_POST['ref'])  : '';
            $note = isset($_POST['note']) ? trim($_POST['note']) : '';

            $oldRef  = $ref;
            $oldNote = $note;

            $lines = rdraft_lines($code);

            if (!$lines) {
                $err = 'ยังไม่ได้เลือกสินค้าเข้าใบสักรายการ';
            } elseif (($bad = Stock_checkScreenQty(rdraft_all(), isset($_POST['screen_qty']) ? $_POST['screen_qty'] : null)) !== '') {
                $err = $bad;                                // จำนวนที่ยืนยันบนจอไม่ตรงกับร่าง (บันทึกจำนวนเบื้องหลังยังไม่ถึง) — ไม่บันทึก ร่างคงเดิม
            } elseif ($ref === '' && $note === '') {     // ไม่มีเอกสาร (เช่น ลูกค้าคืนไม่มีบิล) = เขียนเหตุในหมายเหตุแทน
                $err = 'กรอกเลขที่เอกสารอ้างอิง หรือหมายเหตุ อย่างใดอย่างหนึ่ง (ไม่มีเอกสาร เช่น ลูกค้าคืนไม่มีบิล ให้เขียนในหมายเหตุ)';
            } else {
                $done = receive_save($code, $user, $ref, $note, $lines);
                if (isset($done['error'])) {
                    $err  = $done['error'];
                    $done = null;
                } else {
                    rdraft_clear();
                    header('Location: ' . url('receive.php?done=' . rawurlencode($done['no'])));
                    exit;
                }
            }
        }

        /* ปุ่มเพิ่ม/ลด ถ้าไม่ได้มาจาก htmx ก็ redirect กลับตามปกติ */
        if (!$isHx && $err === '' && $npErr === '' && $act !== 'save') {
            if ($voided !== null) {
                header('Location: ' . url('receive.php?voided=' . rawurlencode($voided['no'])));
                exit;
            }
            $to = 'receive.php';
            if ($q !== '' || $cat !== '') {
                $to .= '?' . ($q !== '' ? 'q=' . rawurlencode($q) : '')
                     . ($cat !== '' ? ($q !== '' ? '&' : '') . 'cat=' . rawurlencode($cat) : '');
            }
            header('Location: ' . url($to));
            exit;
        }
    }
}

/* กด “แสดงเพิ่ม” (?from=) → แค่การ์ดชุดถัดไป (ช่วง 4) */
if ($isHx && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['from'])) {
    $LIVE_PART = 'more';
}
/* คำขอจาก htmx: ส่งกลับเฉพาะส่วนที่เปลี่ยน */
if ($isHx) {
    require dirname(__FILE__) . '/inc/receive-live.php';
    exit;
}

if (isset($_GET['done'])) {
    $done = receive_by_no($code, $_GET['done']);
}
if ($voided === null && isset($_GET['voided'])) {
    $b = receive_by_no($code, $_GET['voided']);
    if ($b !== null && !empty($b['void'])) {
        $voided = $b;
    }
}

$branch     = $code;
$PAGE_TITLE = 'นำเข้าสินค้า';
$PAGE_SUB   = branch_name($code) . ' · หนึ่งใบส่งของ = หนึ่งเอกสารรับเข้า';
$NAV_ACTIVE = 'receive.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($err !== '') { ?>
  <div class="alert alert-error" role="alert">
    <svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span>
  </div>
<?php } ?>

<?php if ($voided !== null) { ?>
  <?php $isEdit = (isset($voided['void_mode']) && $voided['void_mode'] === 'edit'); ?>
  <div class="alert <?php echo $isEdit ? 'alert-info' : 'alert-warn' ?>" role="status">
    <svg class="ico"><use href="#<?php echo $isEdit ? 'i-arrow' : 'i-ban' ?>"/></svg>
    <span>
      <?php if ($isEdit) { ?>
        ยกเลิกใบ <b><?php echo e($voided['no']) ?></b> และดึง <?php echo (int) $voided['items'] ?> รายการกลับเข้าใบให้แล้ว —
        แก้จำนวนที่ผิดแล้วบันทึกใหม่ได้เลย ระบบจะออกเลขใบใหม่ให้
      <?php } else { ?>
        ยกเลิกใบรับเข้า <b><?php echo e($voided['no']) ?></b> แล้ว ·
        ถอนออกจากสต๊อก <b class="num"><?php echo number_format($voided['qty']) ?></b> ชิ้น ·
        เหตุผล: <?php echo e($voided['void_reason']) ?>
      <?php } ?>
    </span>
  </div>
<?php } ?>

<?php if ($done !== null && empty($done['void'])) { ?>
  <div class="alert alert-ok" role="status">
    <svg class="ico"><use href="#i-check"/></svg>
    <span>
      รับเข้าเรียบร้อย เอกสาร <b><?php echo e($done['no']) ?></b> ·
      <?php echo (int) $done['items'] ?> รายการ · <b class="num"><?php echo number_format($done['qty']) ?></b> ชิ้น ·
      <?php echo $done['ref'] !== '' ? 'อ้างอิง ' . e($done['ref']) : 'หมายเหตุ: ' . e($done['note']) ?>
    </span>
    <?php
    /* รับเข้าเกิน/ผิด แก้ได้ทันทีจากตรงนี้ ทั้งสองทางต้องกรอกหมายเหตุก่อน */
    $dat = ' data-bill="' . e($done['no']) . '" data-qty="' . (int) $done['qty']
         . '" data-items="' . (int) $done['items'] . '"';
    ?>
    <div class="alert-act">
      <?php foreach ((can_void_doc($user, $done) ? array(
          array('edit', 'แก้ไขใบนี้',  'i-arrow', 'หมายเหตุการแก้ไขใบรับเข้า'),
          array('void', 'ยกเลิกใบนี้', 'i-ban',   'หมายเหตุการยกเลิกใบรับเข้า'),
      ) : array()) as $b) { ?>
        <form method="post" action="receive.php" data-confirm="<?php echo e($b[0]) ?>"<?php echo $dat ?>>
          <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
          <input type="hidden" name="act" value="<?php echo e($b[0]) ?>">
          <input type="hidden" name="no" value="<?php echo e($done['no']) ?>">
          <input class="reason-fb" type="text" name="reason" value=""
                 placeholder="หมายเหตุ (จำเป็น)" aria-label="<?php echo e($b[3]) ?>">
          <button class="btn btn-ghost btn-sm" type="submit">
            <svg class="ico"><use href="#<?php echo e($b[2]) ?>"/></svg> <?php echo e($b[1]) ?>
          </button>
        </form>
      <?php } ?>
      <?php if (page_ok($user, 'products.php')) { ?>
      <a class="btn btn-ghost btn-sm" href="products.php">
        <svg class="ico"><use href="#i-boxes"/></svg> ดูยอดคงเหลือ
      </a>
      <?php } ?>
    </div>
  </div>
<?php } ?>

<form method="post" action="receive.php" id="recv-form" data-confirm="receive">
  <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
  <input type="hidden" name="act" value="save">
  <?php /* จำนวนบนจอทุกแถว (JSON) — กล่องยืนยันใส่ให้ตอนเปิด (snapQty ใน inc/footer.php) · เทียบกับร่างด้วย Stock_checkScreenQty ก่อนบันทึก */ ?>
  <input type="hidden" name="screen_qty" value="">

  <!-- ===== หัวเอกสาร ===== -->
  <section class="card recv-head">
    <div class="card-head">
      <div>
        <h2>ข้อมูลการรับเข้า</h2>
        <span class="sub">ทุกครั้งที่รับของต้องมีเอกสารอ้างอิง หรือหมายเหตุ เพื่อให้ตรวจย้อนหลังได้</span>
      </div>
    </div>
    <div class="recv-fields">
      <div class="field">
        <label for="ref">เลขที่เอกสารอ้างอิง <i class="req">ไม่มี ให้กรอกหมายเหตุแทน</i></label>
        <input class="input" type="text" id="ref" name="ref" autocomplete="off"
               value="<?php echo e($oldRef) ?>" placeholder="เช่น INV-2609-0142 หรือเลขใบส่งของ">
      </div>

      <?php /* ผู้รับเข้าและสาขา ดึงจากบัญชีที่ล็อกอินอยู่ ไม่ต้องกรอกซ้ำ */ ?>
      <div class="field">
        <label>ผู้รับเข้า</label>
        <div class="who-box">
          <span class="av"><?php echo e($user['initials']) ?></span>
          <div>
            <b><?php echo e($user['name']) ?></b>
            <small><?php echo e(user_role_label($user)) ?> · <?php echo e(branch_name($code)) ?></small>
          </div>
        </div>
      </div>

      <div class="field field-wide">
        <label for="note">หมายเหตุ</label>
        <input class="input" type="text" id="note" name="note" autocomplete="off"
               value="<?php echo e($oldNote) ?>" placeholder="เช่น ลูกค้าคืน ไม่มีบิล · ของมาไม่ครบ 3 ชิ้น รอส่งตามภายหลัง">
      </div>
    </div>
  </section>

  <?php $LIVE_PART = 'all'; /* หน้าเต็มวาดทั้งส่วนเสมอ */ require dirname(__FILE__) . '/inc/receive-live.php'; ?>

  <!-- ===== แถบสรุปติดขอบล่าง ===== -->
  <div class="recv-bar" id="recv-bar">
    <div class="rb-t">
      <b id="rb-items">0</b> รายการ ·
      <b class="num" id="rb-qty">0</b> ชิ้น
      <span class="rb-diff" id="rb-diff" hidden></span>
      <small id="rb-hint">เลือกสินค้าเข้าใบก่อน</small>
    </div>
    <button class="btn-xl btn-in" type="submit" id="rb-go" data-need data-qty-guard="save" aria-disabled="true">
      <svg class="ico"><use href="#i-in"/></svg> บันทึกการรับเข้า
    </button>
  </div>
</form>

<?php if (can($user, 'product_add')) {
    /* ==================== เพิ่มสินค้าใหม่ (ช่วงที่ 16) — อยู่นอกฟอร์มบันทึกใบ เพราะฟอร์มซ้อนฟอร์มไม่ได้ ==================== */
    $npCats = product_cats();
    $npCanCat = can($user, 'category_add');
    $npv = function ($k, $def = '') use ($npErr) {                       // ค่าที่กรอกไว้ (ตอนบันทึกไม่ผ่าน)
        return ($npErr !== '' && isset($_POST[$k]) && is_string($_POST[$k])) ? $_POST[$k] : $def;
    };
    $npManual = ($npv('sku_mode') === 'manual'); ?>
<dialog class="fm-modal np-modal" id="np-modal" aria-labelledby="np-t"<?php echo $npErr !== '' ? ' data-fm-auto' : '' ?>>
  <div class="fm-head">
    <div><h2 id="np-t">เพิ่มสินค้าใหม่</h2><span class="sub">เพิ่มเข้าระบบ (ใช้ได้ทุกสาขา) แล้วใส่ในใบรับเข้านี้ 1 ชิ้นทันที</span></div>
    <button type="button" class="icon-btn" data-fm-close aria-label="ปิด"><svg class="ico"><use href="#i-x"/></svg></button>
  </div>
  <?php if ($npErr !== '') { ?>
    <div class="alert alert-error adm-ok" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($npErr) ?></span></div>
  <?php } ?>
  <?php if (!$npCats && !$npCanCat) { ?>
    <p class="adm-sec np-nocat">ยังไม่มีหมวดสินค้าในระบบ — ต้องมีหมวดก่อนจึงจะเพิ่มสินค้าได้ ติดต่อผู้จัดการสาขาหรือผู้ดูแลให้เพิ่มหมวด (หรือเปิดสิทธิ์ “เพิ่มหมวด”)</p>
  <?php } else { ?>
  <form class="adm-sec" method="post" action="receive.php" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
    <input type="hidden" name="act" value="newprod">
    <div class="adm-fields">
      <div class="field">
        <label for="np-name">ชื่อสินค้า</label>
        <input class="input" type="text" id="np-name" name="name" value="<?php echo e($npv('name')) ?>" maxlength="200" required autocomplete="off" placeholder="เช่น สายชาร์จ USB-C 1 เมตร">
      </div>
      <div class="field">
        <span class="lbl" id="np-sku-l">SKU (รหัสสินค้า)</span>
        <div class="np-sku" role="radiogroup" aria-labelledby="np-sku-l">
          <label class="check"><input type="radio" name="sku_mode" value="auto"<?php echo $npManual ? '' : ' checked' ?>> ตั้งให้อัตโนมัติ (P0001 …)</label>
          <label class="check"><input type="radio" name="sku_mode" value="manual"<?php echo $npManual ? ' checked' : '' ?>> กรอกเอง</label>
        </div>
        <input class="input np-sku-in" type="text" id="np-sku" name="sku" value="<?php echo e($npv('sku')) ?>" maxlength="40" autocomplete="off" autocapitalize="characters"
               placeholder="เช่น CS-010" aria-label="SKU ที่กรอกเอง"<?php echo $npManual ? ' required' : ' hidden' ?>>
        <small class="adm-hint">A–Z 0–9 . _ - · ตั้งแล้วเปลี่ยนไม่ได้</small>
      </div>
      <div class="field">
        <label for="np-cat">หมวด</label>
        <select class="input" id="np-cat" name="cat">
          <option value="">— เลือกหมวด —</option>
          <?php $cv = $npv('cat'); foreach ($npCats as $c) { ?>
            <option value="<?php echo e($c) ?>"<?php echo $cv === $c ? ' selected' : '' ?>><?php echo e($c) ?></option>
          <?php } ?>
        </select>
        <?php if ($npCanCat) { ?>
          <input class="input np-cat-new" type="text" name="cat_new" value="<?php echo e($npv('cat_new')) ?>" maxlength="50" autocomplete="off"
                 placeholder="หรือพิมพ์หมวดใหม่ เช่น หูฟัง" aria-label="หมวดใหม่">
          <small class="adm-hint">พิมพ์หมวดใหม่ = สร้างหมวดนั้นให้ด้วย (ใช้ได้ทุกสาขา)</small>
        <?php } ?>
      </div>
      <div class="field">
        <label for="np-unit">หน่วย</label>
        <input class="input" type="text" id="np-unit" name="unit" value="<?php echo e($npv('unit', 'ชิ้น')) ?>" maxlength="20" list="np-units" autocomplete="off">
        <datalist id="np-units"><?php foreach (product_units() as $un) { ?><option value="<?php echo e($un) ?>"><?php } ?></datalist>
      </div>
      <div class="field">
        <label for="np-cost">ทุนต่อหน่วย (บาท)</label>
        <input class="input" type="text" id="np-cost" name="cost" value="<?php echo e($npv('cost')) ?>" inputmode="decimal" autocomplete="off" placeholder="0.00">
        <small class="adm-hint">ใช้คิดมูลค่าการรับเข้าครั้งนี้</small>
      </div>
      <div class="field">
        <label for="np-price">ราคาขาย (บาท · รวม VAT)</label>
        <input class="input" type="text" id="np-price" name="price" value="<?php echo e($npv('price')) ?>" inputmode="decimal" autocomplete="off" placeholder="0.00">
        <small class="adm-hint">มีผลกับบิลทันทีที่บันทึก</small>
      </div>
      <div class="field">
        <label for="np-reorder">จุดสั่งซื้อ</label>
        <input class="input" type="text" id="np-reorder" name="reorder" value="<?php echo e($npv('reorder')) ?>" inputmode="numeric" autocomplete="off" placeholder="0">
        <small class="adm-hint">เหลือเท่านี้หรือน้อยกว่า = ใกล้หมด</small>
      </div>
      <div class="field">
        <label for="np-barcode">บาร์โค้ด <small class="adm-none">(ไม่บังคับ)</small></label>
        <input class="input" type="text" id="np-barcode" name="barcode" value="<?php echo e($npv('barcode')) ?>" maxlength="40" autocomplete="off">
      </div>
      <div class="field">
        <label for="np-image">รูปสินค้า <small class="adm-none">(ไม่บังคับ · JPG / PNG / WEBP · รูปใหญ่ระบบย่อให้เอง)</small></label>
        <input class="input" type="file" id="np-image" name="image" accept="image/jpeg,image/png,image/webp" data-shrink data-prev="np-image-prev" data-note="np-image-note">
        <?php if ($npBad !== '') { ?><small class="adm-hint hist-void">รูปไม่ผ่าน: <?php echo e($npBad) ?> — เลือกรูปใหม่อีกครั้ง</small><?php } ?>
        <img class="prod-edit-img img-prev" id="np-image-prev" alt="" hidden>
        <small class="adm-hint img-note" id="np-image-note" hidden></small>
      </div>
    </div>
    <p class="adm-hint">เพิ่มแล้วแก้เองไม่ได้ — ตรวจข้อมูลและรูปก่อนกดเพิ่ม</p>
    <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มสินค้าและใส่ในใบนี้</button>
  </form>
  <?php } ?>
</dialog>
<script>
/* หน้าต่างเพิ่มสินค้าใหม่ — ปุ่มเปิดอยู่ในส่วนที่ htmx สลับ จึงผูกแบบ delegation · บันทึกไม่ผ่าน → เปิดค้างไว้ */
(function () {
  var dlg = document.getElementById('np-modal');
  if (!dlg || !dlg.showModal) { return; }
  document.addEventListener('click', function (ev) {
    if (ev.target.closest('[data-fm-open="np-modal"]')) { dlg.showModal(); var n = document.getElementById('np-name'); if (n) { n.focus(); } return; }
    if (ev.target.closest('#np-modal [data-fm-close]')) { dlg.close(); }
  });
  /* SKU: ตั้งให้อัตโนมัติ / กรอกเอง */
  var skuIn = document.getElementById('np-sku');
  dlg.addEventListener('change', function (ev) {
    if (ev.target.name !== 'sku_mode' || !skuIn) { return; }
    var manual = (ev.target.value === 'manual');
    skuIn.hidden = !manual;
    skuIn.required = manual;
    if (manual) { skuIn.focus(); }
  });
  if (dlg.hasAttribute('data-fm-auto')) { dlg.showModal(); }
})();
</script>
<?php } ?>

<script>
(function () {
  var items = document.getElementById('rb-items');
  var qtyEl = document.getElementById('rb-qty');
  var hint  = document.getElementById('rb-hint');
  var go    = document.getElementById('rb-go');
  var bar   = document.getElementById('recv-bar');
  var refEl = document.getElementById('ref');
  var noteEl = document.getElementById('note');
  var lack  = '';                     // ช่องแรกที่ยังขาด — ให้ปุ่มบันทึกพาไป (aoNeedSync ใน inc/footer.php)

  function num(v) { return Number(String(v).replace(/[^0-9]/g, '')) || 0; }

  go.aoNeed = function () {
    if (lack === 'pick') {
      return { box: document.querySelector('#recv-live .sale-pick'), field: document.getElementById('rq'), mouseOnly: true,
               msg: 'แตะสินค้าที่รับเข้าอย่างน้อย 1 รายการ (ค้นด้วยชื่อหรือ SKU ได้)' };
    }
    if (lack === 'ref') { return { box: refEl, field: refEl, after: refEl, msg: 'กรอกเลขที่เอกสารอ้างอิง หรือหมายเหตุ (ไม่มีเอกสาร เช่น ลูกค้าคืนไม่มีบิล) ก่อนบันทึก' }; }
    return null;
  };

  /* เรียกทุกครั้งที่ htmx สลับรายการในใบ (ดูท้ายไฟล์ inc/receive-live.php) */
  window.recvSync = function () {
    var sumEl = document.getElementById('recv-sum');
    var n = document.querySelectorAll('#recv-live .recv-list li').length;
    var q = sumEl ? num(sumEl.getAttribute('data-v')) : 0;
    var hasRef = (refEl.value + noteEl.value).replace(/\s/g, '') !== '';   // เลขเอกสาร หรือหมายเหตุ อย่างใดอย่างหนึ่ง

    items.textContent = n.toLocaleString('th-TH');
    qtyEl.textContent = q.toLocaleString('th-TH');

    lack = (n === 0) ? 'pick' : (!hasRef ? 'ref' : '');
    if (window.aoNeedSync) { window.aoNeedSync(go); }
    bar.className = lack ? 'recv-bar' : 'recv-bar on';

    if (n === 0)       { hint.textContent = 'เลือกสินค้าเข้าใบก่อน'; }
    else if (!hasRef)  { hint.textContent = 'ยังไม่ได้กรอกเลขที่เอกสาร หรือหมายเหตุ'; }
    else               { hint.textContent = 'ตรวจอีกครั้งก่อนกดบันทึก'; }
  };

  refEl.addEventListener('input', window.recvSync);
  noteEl.addEventListener('input', window.recvSync);
  document.body.addEventListener('htmx:afterSettle', function () { window.recvSync(); });
  window.recvSync();

  /* ข้อมูลให้กล่องยืนยันไปสรุป */
  var form = document.getElementById('recv-form');
  form.recvSummary = function () {
    var out = [];
    var rows = document.querySelectorAll('#recv-live .recv-list li');
    for (var i = 0; i < rows.length; i++) {
      out.push({
        name: rows[i].querySelector('.cl-t b').textContent,
        qty:  num(rows[i].querySelector('.rq-set').value),
        unit: rows[i].querySelector('.cl-s').textContent.trim()
      });
    }
    return out;
  };
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
