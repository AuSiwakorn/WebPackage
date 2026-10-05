<?php
/**
 * FILE: themes/aostock/adm-users.php
 * ROLE: จัดการพนักงาน (เฉพาะผู้ดูแล)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_staff, ao_stock_staff_branch, ao_stock_remember, ao_stock_log · ตาราง ao_stock_* อื่น (เช็กก่อนลบพนักงาน) — ผ่าน api.php
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] อ่าน / เขียนข้อมูลจากตาราง ao_stock_* ผ่าน api.php (ช่วงที่ 5–9)
 *   - [x] ช่วงที่ 10: แก้ File Header ให้ตรงกับระบบจริง · เช็ก PIN ซ้ำในสาขาตอนย้ายสาขา / เปิดใช้งานกลับ (staff_pin_conflict)
 *   - [x] ช่วงที่ 11: สิทธิ์ละเอียดขึ้น (ช่องติ๊กแบ่ง 6 หมวด) · ตารางสรุปสิทธิ์รายหมวด · ประวัติบอกสิทธิ์ที่เพิ่ม / เอาออก
 *   - [x] ช่วงที่ 12: แก้ข้อมูล / PIN / พักงาน / ลบ ย้ายไปใช้ staff_act_* (ใช้ร่วมกับ team.php ของผู้จัดการสาขา — ข้อความเดิมทุกคำ)
 *         · ตั้ง / ปลด "ผู้จัดการสาขา" ที่ช่อง "ตำแหน่ง" บนสุดของฟอร์ม (หรือที่หน้าจัดการสาขา) · ป้ายผู้จัดการข้างชื่อ · ย้ายสาขายังอยู่ในหน้านี้ (เฉพาะผู้ดูแล)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   จัดการพนักงาน (เฉพาะผู้ดูแล)
   ----------------------------------------------------------
   แก้ไข · พักงาน / เปิดใช้งาน · ลบ  (เพิ่มพนักงานอยู่หน้าแยก adm-user-add.php)
   - สิทธิ์การเข้าถึงกำหนดรายคน (ช่วงที่ 11 แตกละเอียด — รายการเต็มดู perm_list ใน api/core.php)
       หน้าร้าน:     ขาย · ให้ส่วนลด · เปิด/ปิดร้าน · เปิดร้านอีกครั้ง · เงินเข้า/ออก · แก้บิลตัวเอง
       งานคลัง:      นำเข้า · เบิก/ตัดออก · ตรวจนับ · แก้เอกสารคลังตัวเอง
       รับคืน:       รับคืน · คืนเงินสด      หมวดสินค้า: เพิ่ม · ลบ
       ดูข้อมูล:     สินค้าในสต๊อก · ประวัติเคลื่อนไหว · ประวัติรายการ · รายงานยอดขาย
       สิทธิ์เสริม:   แก้งานคนอื่น · แก้ย้อนหลัง · รายงานทั้งสาขา
   - PIN 4 หลัก ห้ามซ้ำกับคนอื่นในสาขาเดียวกัน — เช็กตอนตั้ง PIN และตอนย้ายสาขา / เปิดใช้งานกลับ (เทียบลายนิ้วมือ PIN · ช่วงที่ 10)
     พนักงานที่ยังไม่ได้เข้าระบบด้วย PIN หลังช่วงที่ 10 ยังไม่มีลายนิ้วมือ — ตอนย้าย / เปิดใช้งานจะยังเช็กไม่ได้
   - ย้ายสาขามีผลตั้งแต่วันนี้ ยอดเก่ายังผูกกับสาขาเดิม
   - พักงาน / ลาออก: เข้าระบบไม่ได้ ไม่ขึ้นในรายชื่อ แต่ชื่อในเอกสารเก่ายังอยู่
   - ลบ: ได้เฉพาะคนที่ยังไม่เคยทำรายการเลย คนที่มีประวัติแล้วให้ใช้ "พักงาน / ลาออก"
   ทุกการแก้ไขบันทึกลงประวัติของสาขา

   เก็บในตาราง ao_stock_staff + ao_stock_staff_branch (staff_update / staff_set_pin / staff_move /
   staff_set_active / staff_delete ใน api.php) · พักงาน = is_active = 0
   หน้านี้จัดการเฉพาะพนักงาน — ผู้ดูแล / ฝ่ายบัญชี จัดการที่หลังบ้าน admweb (โมดูล stock)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล (พนักงานถูกพากลับเอง)

$err   = '';
$errAt = '';                                  // 'new' หรือ username ที่มีข้อผิดพลาด
$sel   = isset($_GET['u']) ? trim($_GET['u']) : '';
$old   = array();

/* ---------- บันทึก ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? $_POST['act'] : '';
    $sel = isset($_POST['u']) ? trim($_POST['u']) : '';
    $all = staff_all();
    $br  = branches_active();
    $go  = '';

    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';

    } elseif (!isset($all[$sel])) {
        $err = 'ไม่พบพนักงานคนนี้';

    } else {
        $u     = $all[$sel];
        $errAt = $sel;

        /* แก้ข้อมูล / PIN / พักงาน / ลบ ใช้ staff_act_* ร่วมกับหน้าผู้จัดการสาขา (ช่วงที่ 12) · ย้ายสาขาเป็นของผู้ดูแลอย่างเดียว */
        if ($act === 'save') {                               // ชื่อ อักษรย่อ สิทธิ์
            $name  = trim(preg_replace('/\s+/u', ' ', isset($_POST['name']) ? $_POST['name'] : ''));
            $ini   = trim(isset($_POST['initials']) ? $_POST['initials'] : '');
            $perms = perm_set_manager(read_perms(), isset($_POST['position']) && $_POST['position'] === 'manager');   // ตำแหน่ง (ช่วงที่ 12)
            $old[$sel] = array('name' => $name, 'initials' => $ini, 'perms' => $perms);
            $err = staff_act_save($sel, $name, $ini, $perms, $user);
            $go  = 'adm-users.php?u=' . rawurlencode($sel) . '&ok=save';

        } elseif ($act === 'pin') {
            $err = staff_act_pin($sel, isset($_POST['pin']) ? $_POST['pin'] : '', $user);
            $go  = 'adm-users.php?u=' . rawurlencode($sel) . '&ok=pin';

        } elseif ($act === 'move') {
            $to = isset($_POST['branch']) ? $_POST['branch'] : '';
            if (!isset($br[$to])) {
                $err = 'กรุณาเลือกสาขาปลายทาง';
            } elseif ($to === $u['branch']) {
                $err = $u['name'] . ' อยู่' . branch_name($to) . 'อยู่แล้ว';
            } elseif ($u['role'] === 'staff' && ($dup = staff_pin_conflict($sel, $to)) !== '') {
                $err = 'PIN ของ ' . $u['name'] . ' ซ้ำกับ ' . $dup . ' ใน' . branch_name($to) . ' — รีเซ็ต PIN ก่อนแล้วค่อยย้าย';     // ช่วงที่ 10
            } else {
                staff_move($sel, $to);
                $detail = array(
                    'จาก' => branch_name($u['branch']), 'ไป' => branch_name($to),
                    'มีผล' => 'ตั้งแต่วันนี้ ' . thai_date_full(time()),
                    'ยอดเก่า' => 'ยังผูกกับ' . branch_name($u['branch']) . 'เหมือนเดิม',
                    'แก้โดย' => $user['name'] . ' (ผู้ดูแล)',
                );
                log_add($u['branch'], 'setting', $user, 'ย้าย ' . $u['name'] . ' ไป' . branch_name($to), $detail);
                log_add($to, 'setting', $user, 'รับ ' . $u['name'] . ' ย้ายมาจาก' . branch_name($u['branch']), $detail);
                $go = 'adm-users.php?u=' . rawurlencode($sel) . '&ok=move';
            }

        } elseif ($act === 'off' || $act === 'on') {
            $err = staff_act_active($sel, $act === 'on', isset($_POST['why']) ? $_POST['why'] : '', $user);
            $go  = 'adm-users.php?u=' . rawurlencode($sel) . '&ok=' . $act;

        } elseif ($act === 'delete') {
            $err = staff_act_delete($sel, !empty($_POST['sure']), $user);
            if ($err === '') {
                $_SESSION['flash'] = 'ลบ ' . $u['name'] . ' แล้ว';
                $go = 'adm-users.php';
            }
        }
    }

    if ($go !== '' && $err === '') {
        header('Location: ' . url($go));
        exit;
    }
}

$staff  = staff_all();
$picked = isset($staff[$sel]) ? $staff[$sel] : null;
$okMsg  = array(
    'add'  => 'เพิ่มพนักงานแล้ว — เข้าระบบด้วยการแตะชื่อ + PIN ได้ทันที',
    'save' => 'บันทึกแล้ว สิทธิ์มีผลทันที',
    'pin'  => 'รีเซ็ต PIN แล้ว — แจ้ง PIN ใหม่ให้พนักงานโดยตรง',
    'move' => 'ย้ายสาขาแล้ว มีผลตั้งแต่วันนี้ ยอดเก่ายังอยู่ที่สาขาเดิม',
    'off'  => 'พักงานแล้ว — เข้าระบบไม่ได้ แต่ชื่อในเอกสารเก่ายังอยู่',
    'on'   => 'เปิดใช้งานอีกครั้งแล้ว',
);
$ok = (isset($_GET['ok']) && isset($okMsg[$_GET['ok']])) ? $okMsg[$_GET['ok']] : '';
$nActive = 0;
foreach ($staff as $s) {
    $nActive += user_active($s) ? 1 : 0;
}

$branch     = work_branch($user);
$PAGE_TITLE = 'จัดการพนักงาน';
$PAGE_SUB   = 'ใช้งาน ' . $nActive . ' คน' . (count($staff) > $nActive ? ' · พักงาน ' . (count($staff) - $nActive) . ' คน' : '');
$NAV_ACTIVE = 'adm-users.php';
$NO_BRANCH_PICK = true;                   // หน้านี้แบ่งกลุ่มตามสาขาให้แล้ว
require dirname(__FILE__) . '/inc/header.php';

?>

<?php if ($err !== '' && $errAt === '') { ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span></div>
<?php } ?>

<?php if ($picked === null) { ?>
<!-- ==================== เพิ่มพนักงาน (หน้าแยก adm-user-add.php) ==================== -->
<section class="card br-add">
  <div class="card-head">
    <div><h2>เพิ่มพนักงาน</h2><span class="sub">พนักงานเข้าระบบด้วยการแตะชื่อ + PIN 4 หลัก</span></div>
    <a class="btn btn-primary btn-sm" href="adm-user-add.php"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มพนักงาน</a>
  </div>
</section>
<?php } ?>

<?php if ($ok !== '') { ?>
  <div class="alert alert-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span><?php echo e($ok) ?></span></div>
<?php } ?>

<?php if ($picked !== null) {
    $pv     = isset($old[$sel]) ? $old[$sel] : array('name' => $picked['name'], 'initials' => $picked['initials'],
                                                      'perms' => isset($picked['perms']) ? $picked['perms'] : array());
    $active = user_active($picked);
    $reason = staff_data_reason($sel); ?>
  <!-- ==================== แก้ไขพนักงานที่เลือก ==================== -->
  <section class="card adm-edit">
    <div class="card-head">
      <div class="adm-who">
        <span class="av"><?php echo e(user_initial($picked)) ?></span>
        <div>
          <h2><?php echo e($picked['name']) ?> <?php if (in_array('manager', $picked['perms'], true)) { ?><span class="bdg bdg-ok">ผู้จัดการสาขา</span><?php } ?><?php if (!$active) { ?><span class="bdg bdg-out">พักงาน</span><?php } ?></h2>
          <span class="sub"><?php echo e($sel) ?> · <?php echo e(branch_name($picked['branch'])) ?>
            <?php if (!empty($picked['since'])) { ?> · ประจำสาขานี้ตั้งแต่ <?php echo e(thai_date_full(strtotime($picked['since']))) ?><?php } ?></span>
        </div>
      </div>
      <a class="btn btn-ghost btn-sm" href="adm-users.php"><svg class="ico"><use href="#i-x"/></svg> ปิด</a>
    </div>
    <?php if ($errAt === $sel && $err !== '') { ?>
      <div class="alert alert-error adm-ok" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span></div>
    <?php } ?>

    <?php if ($active) { ?>
    <!-- ข้อมูล + สิทธิ์ -->
    <form class="adm-sec" method="post" action="adm-users.php?u=<?php echo e(rawurlencode($sel)) ?>">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="u" value="<?php echo e($sel) ?>">
      <?php staff_position_field(in_array('manager', $pv['perms'], true)); /* ตำแหน่ง: พนักงาน / ผู้จัดการสาขา (ช่วงที่ 12) */ ?>
      <div class="adm-fields">
        <div class="field">
          <label for="e-name">ชื่อ–นามสกุล</label>
          <input class="input" type="text" id="e-name" name="name" value="<?php echo e($pv['name']) ?>" maxlength="60" required autocomplete="off">
        </div>
        <div class="field">
          <label for="e-ini">อักษรย่อ</label>
          <input class="input adm-pin" type="text" id="e-ini" name="initials" value="<?php echo e($pv['initials']) ?>" maxlength="4" autocomplete="off">
        </div>
      </div>
      <?php perm_boxes($pv['perms'], $picked['branch']); ?>
      <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-check"/></svg> บันทึก</button>
    </form>

    <div class="adm-row">
      <form class="adm-sec" method="post" action="adm-users.php?u=<?php echo e(rawurlencode($sel)) ?>">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="act" value="pin">
        <input type="hidden" name="u" value="<?php echo e($sel) ?>">
        <h3>รีเซ็ต PIN</h3>
        <p class="adm-hint">ใช้เมื่อพนักงานลืม PIN · ห้ามซ้ำกับคนอื่นในสาขาเดียวกัน</p>
        <div class="adm-inline">
          <label class="sr-only" for="pin">PIN ใหม่</label>
          <input class="input adm-pin" type="text" id="pin" name="pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" placeholder="4 หลัก" autocomplete="off" required>
          <button class="btn btn-ghost" type="submit">ตั้ง PIN ใหม่</button>
        </div>
      </form>
      <form class="adm-sec" method="post" action="adm-users.php?u=<?php echo e(rawurlencode($sel)) ?>">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="act" value="move">
        <input type="hidden" name="u" value="<?php echo e($sel) ?>">
        <h3>ย้ายสาขา</h3>
        <p class="adm-hint">มีผลตั้งแต่วันนี้ · ยอดขายและเอกสารเก่ายังผูกกับสาขาเดิม</p>
        <div class="adm-inline">
          <label class="sr-only" for="to">สาขาปลายทาง</label>
          <select class="input" id="to" name="branch" required>
            <option value="">เลือกสาขาปลายทาง</option>
            <?php foreach (branches_active() as $bc => $b) { if ($bc === $picked['branch']) { continue; } ?>
              <option value="<?php echo e($bc) ?>"><?php echo e($b['name']) ?></option>
            <?php } ?>
          </select>
          <button class="btn btn-ghost" type="submit">ย้าย</button>
        </div>
        <?php if (!empty($picked['history'])) { ?>
          <ul class="adm-hist">
            <?php foreach ($picked['history'] as $h) { ?>
              <li><?php echo e(branch_name($h['branch'])) ?> · <?php echo e(thai_date_full(strtotime($h['from']))) ?> – <?php echo e(thai_date_full(strtotime($h['to']))) ?></li>
            <?php } ?>
          </ul>
        <?php } ?>
      </form>
    </div>
    <?php } ?>

    <!-- พักงาน / เปิดใช้งาน · ลบ -->
    <div class="adm-row br-danger">
      <form class="adm-sec" method="post" action="adm-users.php?u=<?php echo e(rawurlencode($sel)) ?>">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="act" value="<?php echo $active ? 'off' : 'on' ?>">
        <input type="hidden" name="u" value="<?php echo e($sel) ?>">
        <?php if ($active) { ?>
          <h3>พักงาน / ลาออก</h3>
          <p class="adm-hint">เข้าระบบไม่ได้ ไม่ขึ้นในรายชื่อหน้าเข้าระบบ แต่ชื่อในบิลและเอกสารเก่ายังอยู่ · เปิดกลับมาได้</p>
          <div class="adm-inline">
            <input class="input" type="text" name="why" placeholder="เหตุผล เช่น ลาออก / ลาคลอด" autocomplete="off">
            <button class="btn btn-ghost" type="submit"><svg class="ico"><use href="#i-ban"/></svg> พักงาน</button>
          </div>
        <?php } else { ?>
          <h3>เปิดใช้งานอีกครั้ง</h3>
          <p class="adm-hint">กลับมาเข้าระบบได้ด้วย PIN เดิม สิทธิ์เดิมยังอยู่</p>
          <button class="btn btn-ghost" type="submit"><svg class="ico"><use href="#i-check"/></svg> เปิดใช้งาน</button>
        <?php } ?>
      </form>
      <form class="adm-sec" method="post" action="adm-users.php?u=<?php echo e(rawurlencode($sel)) ?>">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="act" value="delete">
        <input type="hidden" name="u" value="<?php echo e($sel) ?>">
        <h3>ลบพนักงาน</h3>
        <?php if ($reason !== '') { ?>
          <p class="adm-hint">ลบไม่ได้ เพราะ<?php echo e($reason) ?> — ใช้พักงาน / ลาออกแทน</p>
        <?php } else { ?>
          <p class="adm-hint">ยังไม่เคยทำรายการ ลบได้ · ลบแล้วกู้คืนไม่ได้</p>
          <label class="br-sure"><input type="checkbox" name="sure" value="1" required> ยืนยันลบ <?php echo e($picked['name']) ?></label>
          <button class="btn btn-ghost br-del" type="submit"><svg class="ico"><use href="#i-trash"/></svg> ลบพนักงาน</button>
        <?php } ?>
      </form>
    </div>
  </section>
<?php } ?>

<!-- ==================== รายชื่อพนักงานแยกสาขา ==================== -->
<?php
$groups = array();
foreach ($staff as $k => $u) {
    $g = user_active($u) ? $u['branch'] : '_off';
    $groups[$g][$k] = $u;
}
$order = array_keys(branches_active());
$order[] = '_off';
/* แอคคอร์เดียน: เปิดกลุ่มแรกไว้ · กำลังแก้พนักงานคนไหน ให้เปิดกลุ่มของคนนั้น */
$openG = $order[0];
if ($picked !== null) {
    $openG = user_active($picked) ? $picked['branch'] : '_off';
}
foreach ($order as $g) {
    if ($g !== '_off' && !isset($groups[$g])) { $groups[$g] = array(); }
    if ($g === '_off' && empty($groups[$g])) { continue; }
    $list = $groups[$g]; ?>
  <details class="card acc-item<?php echo $g === '_off' ? ' br-off' : '' ?>" id="g-<?php echo e($g) ?>"<?php echo $g === $openG ? ' open' : '' ?>>
    <summary class="card-head">
      <div><h2><?php echo $g === '_off' ? 'พักงาน / ลาออก' : e(branch_name($g)) ?></h2>
        <span class="sub"><?php echo count($list) ?> คน<?php if ($list) { $nm = array(); foreach ($list as $u) { $nm[] = $u['name']; } ?> · <?php echo e(implode(', ', $nm)) ?><?php } ?></span></div>
      <span class="acc-right">
        <?php if ($g !== '_off') { ?>
          <a class="btn btn-ghost btn-sm" href="adm-branches.php#b-<?php echo e($g) ?>"><svg class="ico"><use href="#i-settings"/></svg> จัดการสาขา</a>
        <?php } ?>
        <svg class="ico acc-chev" aria-hidden="true"><use href="#i-arrow"/></svg>
      </span>
    </summary>
    <?php if (!$list) { ?>
      <p class="empty">ยังไม่มีพนักงานในสาขานี้</p>
    <?php } else { ?>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr><th>พนักงาน</th><th>เมนูที่ใช้ได้</th><th>สิทธิ์เสริม</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($list as $k => $u) { $pp = isset($u['perms']) ? $u['perms'] : array(); ?>
              <tr class="<?php echo $k === $sel ? 'on' : '' ?>">
                <td data-label="พนักงาน"><b><?php echo e($u['name']) ?></b><?php if (in_array('manager', $pp, true)) { ?> <span class="bdg bdg-ok">ผู้จัดการสาขา</span><?php } ?><small><?php echo e($k) ?><?php echo $g === '_off' ? ' · ' . e(branch_name($u['branch'])) : '' ?></small></td>
                <?php $sum = perm_summary($pp); $pg = perm_groups(); /* ช่วงที่ 11: สิทธิ์มีหลายตัว — สรุปเป็นรายหมวด (ชี้ดูชื่อสิทธิ์) */ ?>
                <td data-label="เมนูที่ใช้ได้">
                  <?php $n = 0; foreach ($sum as $grp => $s) { if ($grp === 'extra') { continue; } $n++; ?>
                    <span class="bdg bdg-adj" title="<?php echo e(implode(' · ', $s['names'])) ?>"><?php echo e($pg[$grp]) ?> <?php echo $s['have'] === $s['total'] ? 'ครบ' : $s['have'] . '/' . $s['total'] ?></span>
                  <?php } ?>
                  <?php if (!$n) { ?><span class="adm-none">—</span><?php } ?>
                </td>
                <td data-label="สิทธิ์เสริม">
                  <?php if (isset($sum['extra'])) { foreach ($sum['extra']['names'] as $nm) { ?>
                    <span class="bdg bdg-ok"><?php echo e($nm) ?></span>
                  <?php } } else { ?><span class="adm-none">—</span><?php } ?>
                </td>
                <td class="r"><a class="btn btn-ghost btn-sm" href="adm-users.php?u=<?php echo e(rawurlencode($k)) ?>">แก้ไข</a></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } ?>
  </details>
<?php } ?>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
