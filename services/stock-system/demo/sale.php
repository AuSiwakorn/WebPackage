<?php
/* ==========================================================
   AOSTOCK DEMO — ขายสินค้า (ฝั่งพนักงาน)
   ----------------------------------------------------------
   ยังไม่มีฐานข้อมูล ตะกร้า บิล และสต๊อกที่ขยับ อยู่ใน session ทั้งหมด

   ใช้ htmx ช่วยให้การกดสินค้า / แก้จำนวน / รับเงิน
   สลับเฉพาะส่วน #sale-live โดยไม่โหลดหน้าใหม่
   ถ้าเบราว์เซอร์ปิด JavaScript ฟอร์มทุกอันยังส่งแบบปกติได้ (PRG)
   ========================================================== */

require_once dirname(__FILE__) . '/inc/auth.php';
require_once dirname(__FILE__) . '/inc/store.php';
require_once dirname(__FILE__) . '/inc/sale.php';

$user = require_login();
$code = work_branch($user);          // ผู้ดูแลเลือกสาขาได้จากแถบบน

/* ขายได้ต่อเมื่อเปิดร้านแล้ว */
if (!store_is_open($code)) {
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

            if ($chk !== null && !can_void_doc($user, $chk)) {
                $err = 'บิลนี้เป็นของพนักงานคนอื่น — ต้องมีสิทธิ์แก้งานคนอื่นจึงจะแก้หรือยกเลิกได้';
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

/* ---------- ข้อความแจ้งผล (ส่งกลับแบบ out-of-band ให้ htmx ด้วย) ---------- */
function sale_flash($err, $done, $voided, $oob)
{
    echo '<div id="sale-flash"' . ($oob ? ' hx-swap-oob="true"' : '') . '>';

    if ($err !== '') {
        echo '<div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span>'
           . e($err) . '</span></div>';

    } elseif ($voided !== null) {
        $isEdit = (isset($voided['void_mode']) && $voided['void_mode'] === 'edit');
        echo '<div class="alert ' . ($isEdit ? 'alert-info' : 'alert-warn') . '" role="status">'
           . '<svg class="ico"><use href="#' . ($isEdit ? 'i-arrow' : 'i-ban') . '"/></svg><span>';
        if ($isEdit) {
            echo 'ยกเลิกบิล <b>' . e($voided['no']) . '</b> และดึง ' . (int) $voided['items']
               . ' รายการกลับเข้าตะกร้าให้แล้ว — แก้จำนวนที่ผิดแล้วกดรับเงินใหม่ได้เลย '
               . 'ระบบจะออกเลขบิลใหม่ให้อัตโนมัติ';
        } else {
            echo 'ยกเลิกบิล <b>' . e($voided['no']) . '</b> แล้ว · คืนสต๊อก '
               . (int) $voided['qty'] . ' ชิ้น · เหตุผล: ' . e($voided['void_reason']);
        }
        echo '</span></div>';

    } elseif ($done !== null && empty($done['void'])) {
        echo '<div class="alert alert-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span>'
           . 'บันทึกบิล <b>' . e($done['no']) . '</b> แล้ว · ยอด <b class="num">' . money2($done['total']) . '</b> บาท · '
           . (!empty($done['discount']) ? 'ส่วนลด <b class="num">' . money2($done['discount']) . '</b> บาท · ' : '');
        if ($done['method'] === 'cash') {
            echo 'รับเงิน <span class="num">' . money2($done['received']) . '</span> บาท · '
               . 'เงินทอน <b class="num">' . money2($done['change']) . '</b> บาท';
        } else {
            echo 'ชำระโดยการโอน / พร้อมเพย์';
        }
        echo '</span>';

        /* กดผิดก็แก้ได้ทันทีจากตรงนี้ — ทั้งสองทางต้องกรอกหมายเหตุก่อน */
        $atts = ' data-bill="' . e($done['no']) . '" data-total="' . money2($done['total']) . '"'
              . ' data-qty="' . (int) $done['qty'] . '" data-items="' . (int) $done['items'] . '"'
              . ' hx-post="sale.php" hx-target="#sale-live" hx-swap="outerHTML"';

        echo '<div class="alert-act">';

        echo '<form method="post" action="sale.php" data-confirm="edit" hx-confirm="แก้ไขบิล"' . $atts . '>'
           . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
           . '<input type="hidden" name="act" value="edit">'
           . '<input type="hidden" name="no" value="' . e($done['no']) . '">'
           . '<input class="reason-fb" type="text" name="reason" value=""'
           . ' placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุการแก้ไขบิล">'
           . '<button class="btn btn-ghost btn-sm" type="submit">'
           . '<svg class="ico"><use href="#i-arrow"/></svg> แก้ไขบิลนี้</button>'
           . '</form>';

        echo '<form method="post" action="sale.php" data-confirm="void" hx-confirm="ยกเลิกบิล"' . $atts . '>'
           . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
           . '<input type="hidden" name="act" value="void">'
           . '<input type="hidden" name="no" value="' . e($done['no']) . '">'
           . '<input class="reason-fb" type="text" name="reason" value=""'
           . ' placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุการยกเลิกบิล">'
           . '<button class="btn btn-ghost btn-sm" type="submit">'
           . '<svg class="ico"><use href="#i-ban"/></svg> ยกเลิกบิลนี้</button>'
           . '</form>';

        echo '</div>';

        echo '</div>';
    }
    echo '</div>';
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
