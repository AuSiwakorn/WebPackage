<?php
/**
 * FILE: themes/aostock/sale.php
 * ROLE: ขายสินค้า (ฝั่งพนักงาน)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/sale-live.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_sale, ao_stock_sale_item, ao_stock_move, ao_stock_balance, ao_stock_doc_seq, ao_stock_log (ผ่าน api.php) · ตะกร้าอยู่ใน $_SESSION['cart']
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 7: บิลลงตาราง · บันทึก / ยกเลิกไม่ผ่าน (ของไม่พอ ร้านปิด มีรับคืนแล้ว) แสดงข้อความ
 *   - [x] ช่วงที่ 11: แก้ / ยกเลิกบิลของตัวเองต้องมีสิทธิ์ bill_fix · ร้านยังไม่เปิดและไม่มีสิทธิ์เปิดร้าน → กลับหน้าแรกพร้อมข้อความ
 *   - [x] แจ้งเหตุผลตอนพาไปหน้าเปิดร้าน — ร้านยังไม่เปิด / ปิดแล้ว (บอกวิธีเปิดร้านอีกครั้งตามสิทธิ์)
 *   - [x] ข้อความร้านปิดเรียกปุ่มตามชื่อจริง “เปิดร้านใหม่” (เดิมเขียน “เปิดร้านอีกครั้ง” ซึ่งเป็นชื่อสิทธิ์)
 *   - [x] 12 ต.ค.: ช่อง “รับเงินมา” รับสตางค์ได้ 2 ตำแหน่ง — เดิมตัดจุดทิ้ง พิมพ์ 100.50 กลายเป็น 10,050
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 3): htmx แตะสินค้า / ปุ่มในตะกร้า / รับเงิน ส่งกลับแค่ตะกร้า + การ์ดที่ยอดเปลี่ยน ($LIVE_PART / $OOB_SKUS)
 *   - [x] 14 ต.ค. (งานความเร็ว ช่วง 4): การ์ดครั้งละ 60 ใบ — htmx ?from= ส่งกลับแค่ชุดถัดไป
 *   - [x] 14 ต.ค.: รับเงินเทียบยอดที่จอเห็น (full) กับตะกร้าจริง — ไม่ตรง = ไม่บันทึก (กันส่วนลดผิดจากยอดบนจอที่ยังไม่อัปเดต)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ขายสินค้า (ฝั่งพนักงาน)
   ----------------------------------------------------------
   ตะกร้าอยู่ใน session ของผู้ใช้ · กดรับเงินแล้วบิล + การตัดสต๊อกลงฐานข้อมูลในทรานแซกชันเดียว (bill_save)

   ใช้ htmx ช่วยให้การกดสินค้า / แก้จำนวน / รับเงิน
   สลับเฉพาะส่วน #sale-live โดยไม่โหลดหน้าใหม่
   ถ้าเบราว์เซอร์ปิด JavaScript ฟอร์มทุกอันยังส่งแบบปกติได้ (PRG)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();
$code = work_branch($user);          // ผู้ดูแลเลือกสาขาได้จากแถบบน

/* ขายได้ต่อเมื่อเปิดร้านแล้ว — ไม่มีสิทธิ์เปิดร้าน (ช่วงที่ 11) ไปหน้าเปิดร้านไม่ได้ → กลับหน้าแรกพร้อมบอกเหตุผล */
if (!store_is_open($code)) {
    if (!page_ok($user, 'store.php')) {
        $_SESSION['flash'] = store_is_closed($code) ? 'ร้านปิดแล้วสำหรับวันนี้ — ขายต่อไม่ได้' : 'ร้านยังไม่เปิด — รอคนที่มีสิทธิ์ “เปิด / ปิดร้าน” เปิดร้านก่อน';
        header('Location: ' . url(home_page($user)));
        exit;
    }
    /* บอกเหตุผลบนหน้าเปิดร้าน — เดิมพาไปเงียบ ๆ ผู้ใช้นึกว่าเป็นบั๊ก */
    if (store_is_closed($code)) {
        $_SESSION['flash'] = 'ร้านปิดแล้วสำหรับวันนี้ — ขายต่อไม่ได้'
                           . (can($user, 'store_reopen') ? ' ถ้าต้องขายต่อ กด “เปิดร้านใหม่” ด้านล่าง' : ' ถ้าต้องขายต่อ ติดต่อผู้จัดการสาขาหรือผู้ดูแลให้เปิดร้านอีกครั้ง');
    } else {
        $_SESSION['flash'] = 'ร้านยังไม่เปิดวันนี้ — เปิดร้านก่อนจึงจะขายสินค้าได้';
    }
    header('Location: ' . url('store.php'));
    exit;
}

$isHx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
$q    = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat  = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$err    = '';
$done   = null;
$voided = null;
/* htmx ส่งกลับส่วนไหน (งานความเร็ว ช่วง 3): แตะสินค้า / ปุ่มในตะกร้า / รับเงิน = แค่ตะกร้า + การ์ดที่ยอดเปลี่ยน
   ค้นหา / เลือกหมวด / แก้ไข / ยกเลิกบิล = ทั้งส่วน (#sale-live) */
$LIVE_PART = 'all';
$OOB_SKUS  = array();

/* ---------- รับคำสั่ง ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่';
    } else {
        $act = isset($_POST['act']) ? $_POST['act'] : '';
        $sku = isset($_POST['sku']) ? trim($_POST['sku']) : '';
        if (in_array($act, array('add', 'minus', 'set', 'del'), true)) {
            $LIVE_PART = 'cart';
            $OOB_SKUS  = array($sku);
        } elseif ($act === 'clear' || $act === 'pay') {
            $LIVE_PART = 'cart';
            $OOB_SKUS  = array_keys(cart_all());      // ล้างตะกร้า = ป้ายบนการ์ดหาย · รับเงินแล้ว = ยอดคงเหลือบนการ์ดลดลง
        }

        if ($act === 'add') {
            if (!cart_add($sku, $code, 1)) {
                $err = 'สินค้านี้เหลือไม่พอ ใส่เพิ่มไม่ได้แล้ว';
            }
        } elseif ($act === 'minus') {
            cart_add($sku, $code, -1);
        } elseif ($act === 'set') {
            $qty = isset($_POST['qty']) ? (int) preg_replace('/\D/', '', $_POST['qty']) : 0;
            if (!cart_set($sku, $code, $qty)) {
                $err = 'ใส่ได้ไม่เกินยอดคงเหลือ ปรับให้เป็นจำนวนสูงสุดที่มีแล้ว';
            }
        } elseif ($act === 'del') {
            cart_remove($sku);
        } elseif ($act === 'clear') {
            cart_clear();
        } elseif ($act === 'void' || $act === 'edit') {
            /* ยกเลิกบิลที่บันทึกไปแล้ว — ต้องมีหมายเหตุเสมอ
               act=edit คือยกเลิกแล้วดึงรายการเดิมกลับเข้าตะกร้าเพื่อออกบิลใหม่ */
            $no     = isset($_POST['no']) ? trim($_POST['no']) : '';
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            $redo   = ($act === 'edit');

            $chk    = bill_by_no($code, $no);

            if ($chk !== null && !can_void_doc($user, $chk, 'bill')) {
                $err = ($chk['by_user'] === $user['username'])
                     ? 'ไม่มีสิทธิ์ “แก้บิลตัวเอง” — ติดต่อผู้ดูแลเพื่อเปิดสิทธิ์'
                     : 'บิลนี้เป็นของพนักงานคนอื่น — ต้องมีสิทธิ์แก้งานคนอื่นจึงจะแก้หรือยกเลิกได้';
            } elseif (bill_returned_any($no)) {
                $err = 'บิลนี้มีการรับคืนสินค้าไปแล้ว — ยกเลิกหรือแก้ทั้งบิลไม่ได้ ให้ใช้การรับคืนแทน';
            } elseif ($reason === '') {
                $err = 'การยกเลิกหรือแก้ไขบิลต้องระบุหมายเหตุทุกครั้ง';
            } elseif ($redo && cart_count() > 0) {
                $err = 'ตะกร้ายังมีสินค้าค้างอยู่ ปิดรายการนั้นให้เสร็จก่อนจึงจะแก้ไขบิลเก่าได้';
            } else {
                $voided = bill_void($code, $no, $user, $reason, $redo);
                if ($voided === null) {
                    $err = 'ไม่พบบิลนี้ หรือถูกยกเลิกไปแล้ว';
                } elseif (isset($voided['error'])) {
                    $err    = $voided['error'];
                    $voided = null;
                } elseif ($redo) {
                    cart_from_bill($voided, $code);
                }
            }
        } elseif ($act === 'pay') {
            $method = (isset($_POST['method']) && $_POST['method'] === 'transfer') ? 'transfer' : 'cash';
            /* รับสตางค์ได้ — เดิมตัดทุกอย่างที่ไม่ใช่ตัวเลข พิมพ์ 100.50 แล้วกลายเป็น 10,050 (12 ต.ค.) */
            $recvIn = isset($_POST['received']) ? preg_replace('/[^0-9.]/', '', $_POST['received']) : '';
            $recv   = ($recvIn === '' || !is_numeric($recvIn)) ? 0 : round((float) $recvIn, 2);
            $vat    = (isset($_POST['vat']) && $_POST['vat'] === '1');
            $netIn  = isset($_POST['net']) ? preg_replace('/[^0-9.]/', '', $_POST['net']) : '';
            $net    = ($netIn === '' || !is_numeric($netIn)) ? null : (float) $netIn;
            if (isset($_POST['full']) && abs((float) $_POST['full'] - cart_total()) > 0.005) {
                $err = 'ยอดในตะกร้าเพิ่งเปลี่ยน — ตรวจยอดอีกครั้งแล้วกดรับเงินใหม่';   // จอยังแสดงยอดเก่า (ส่วนลดจะคิดผิด)
            } elseif ($net !== null && ($net <= 0 || $net > cart_total() + 0.001)) {
                $err = 'ยอดที่ต้องชำระต้องมากกว่า 0 และไม่เกินราคาเต็ม ' . money2(cart_total()) . ' บาท';
            } else {
                $done = bill_save($code, $user, $method, $recv, $vat, $net);
                if ($done === null) {
                    $err = 'ยังไม่มีสินค้าในตะกร้า';
                } elseif (isset($done['error'])) {
                    $err  = $done['error'];          // ของไม่พอ (เครื่องอื่นขายตัดหน้า) / ร้านปิดแล้ว — ตะกร้ายังอยู่
                    $done = null;
                }
            }
        }
    }

    /* เบราว์เซอร์ธรรมดา: redirect กันกด refresh ซ้ำ (PRG) */
    if (!$isHx && $err === '') {
        $to = 'sale.php';
        if ($done !== null) {
            $to .= '?done=' . rawurlencode($done['no']);
        } elseif ($voided !== null) {
            $to .= (isset($voided['void_mode']) && $voided['void_mode'] === 'edit' ? '?edited=' : '?voided=')
                 . rawurlencode($voided['no']);
        } elseif ($q !== '' || $cat !== '') {
            $to .= '?' . ($q !== '' ? 'q=' . rawurlencode($q) : '')
                 . ($cat !== '' ? ($q !== '' ? '&' : '') . 'cat=' . rawurlencode($cat) : '');
        }
        header('Location: ' . url($to));
        exit;
    }
}

/* เปิดจากลิงก์ ?done= / ?voided= — เห็นเฉพาะบิลที่มีสิทธิ์ (ไม่มีสิทธิ์ประวัติขาย = บิลของตัวเอง) */
if ($done === null && isset($_GET['done'])) {
    $done = bill_by_no($code, $_GET['done']);
    $done = Stock_canSeeBill($user, $done) ? $done : null;
}
if ($voided === null && (isset($_GET['voided']) || isset($_GET['edited']))) {
    $b = bill_by_no($code, isset($_GET['voided']) ? $_GET['voided'] : $_GET['edited']);
    if ($b !== null && !empty($b['void']) && Stock_canSeeBill($user, $b)) {
        $voided = $b;
    }
}

/* ---------- คำขอจาก htmx: ส่งกลับเฉพาะส่วนที่เปลี่ยน ---------- */
/* หน้าต่าง "บิลของฉันวันนี้" (?bills=1&bf=&bt=) → แค่รายการ */
if ($isHx && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['bills'])) {
    $BILLS_PART = 'list';
    require dirname(__FILE__) . '/inc/sale-bills.php';
    exit;
}
/* กด “แสดงเพิ่ม” (?from=) → แค่การ์ดชุดถัดไป — ไม่ส่งข้อความแจ้งผล (#sale-flash) ไปทับข้อความเดิมบนจอ (ช่วง 4) */
if ($isHx && $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['from'])) {
    $LIVE_PART = 'more';
    require dirname(__FILE__) . '/inc/sale-live.php';
    exit;
}
if ($isHx) {
    sale_flash($err, $done, $voided, true);
    echo '<i id="cart-bdg" class="bdg' . (cart_count() > 0 ? '' : ' none') . '" hx-swap-oob="true">'
       . (cart_count() > 0 ? (int) cart_count() : '') . '</i>';
    require dirname(__FILE__) . '/inc/sale-live.php';
    exit;
}

/* ---------- โหลดหน้าเต็ม ---------- */
$branch     = $code;
$PAGE_TITLE = 'ขายสินค้า';
$PAGE_SUB   = branch_name($code) . ' · แตะสินค้าเพื่อใส่ตะกร้า';
$NAV_ACTIVE = 'sale.php';
require dirname(__FILE__) . '/inc/header.php';

sale_flash($err, $done, $voided, false);
$LIVE_PART = 'all';                  // หน้าเต็มวาดทั้งส่วนเสมอ (ฟอร์มธรรมดาที่บันทึกไม่ผ่านก็มาถึงตรงนี้)
require dirname(__FILE__) . '/inc/sale-live.php';

$BILLS_PART = 'dialog';              // หน้าต่าง "บิลของฉันวันนี้" (ปุ่มเปิดอยู่ในกล่องยอดขายวันนี้)
require dirname(__FILE__) . '/inc/sale-bills.php';

require dirname(__FILE__) . '/inc/footer.php';
