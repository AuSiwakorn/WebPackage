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
    header('Location: ' . url('store.php'));
    exit;
}

$isHx = isset($_SERVER['HTTP_HX_REQUEST']) && $_SERVER['HTTP_HX_REQUEST'] === 'true';
$q    = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat  = isset($_GET['cat']) ? trim($_GET['cat']) : '';
$err    = '';
$done   = null;
$voided = null;

/* ---------- รับคำสั่ง ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่';
    } else {
        $act = isset($_POST['act']) ? $_POST['act'] : '';
        $sku = isset($_POST['sku']) ? trim($_POST['sku']) : '';

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
            $recv   = isset($_POST['received']) ? (int) preg_replace('/\D/', '', $_POST['received']) : 0;
            $vat    = (isset($_POST['vat']) && $_POST['vat'] === '1');
            $netIn  = isset($_POST['net']) ? preg_replace('/[^0-9.]/', '', $_POST['net']) : '';
            $net    = ($netIn === '' || !is_numeric($netIn)) ? null : (float) $netIn;
            if ($net !== null && ($net <= 0 || $net > cart_total() + 0.001)) {
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

if ($done === null && isset($_GET['done'])) {
    $done = bill_by_no($code, $_GET['done']);
}
if ($voided === null && (isset($_GET['voided']) || isset($_GET['edited']))) {
    $b = bill_by_no($code, isset($_GET['voided']) ? $_GET['voided'] : $_GET['edited']);
    if ($b !== null && !empty($b['void'])) {
        $voided = $b;
    }
}

/* ---------- คำขอจาก htmx: ส่งกลับเฉพาะส่วนที่เปลี่ยน ---------- */
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
require dirname(__FILE__) . '/inc/sale-live.php';

require dirname(__FILE__) . '/inc/footer.php';
