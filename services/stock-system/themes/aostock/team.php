<?php
/**
 * FILE: themes/aostock/team.php
 * ROLE: พนักงานในสาขา — ผู้จัดการสาขาเพิ่ม / แก้ไข / รีเซ็ต PIN / พักงาน / ลบ พนักงานในสาขาของตัวเอง
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_staff, ao_stock_staff_branch, ao_stock_remember, ao_stock_log · ตาราง ao_stock_* อื่น (เช็กก่อนลบพนักงาน) — ผ่าน api.php
 * TODO:
 *   - [x] ช่วงที่ 12: หน้าใหม่ของผู้จัดการสาขา (ข้อ 1ก หน้าแยกฝั่งพนักงาน · 2ก ติ๊กได้เฉพาะสิทธิ์ที่ไม่ใช่สิทธิ์เสริม · 3ก ยืนยัน PIN ทุกครั้งที่บันทึก)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   พนักงานในสาขา (ผู้จัดการสาขา)
   ----------------------------------------------------------
   เข้าได้เฉพาะพนักงานที่ผู้ดูแลติ๊ก "ผู้จัดการสาขา" (page_perm team.php = manager) · ผู้ดูแลเปิดหน้านี้ → adm-users.php
   - เห็นพนักงานทุกคนที่ประจำสาขาตัวเอง (รวมคนที่พักงาน)
   - เพิ่มพนักงานเข้าสาขาตัวเอง · แก้ชื่อ / อักษรย่อ / สิทธิ์ · รีเซ็ต PIN · พักงาน / เปิดใช้งาน · ลบ (เฉพาะคนที่ยังไม่เคยทำรายการ)
   - ติ๊กได้เฉพาะสิทธิ์หมวดหน้าร้าน / งานคลัง / รับคืน / หมวดสินค้า / ดูข้อมูล — สิทธิ์เสริมที่ผู้ดูแลให้ไว้เดิมไม่หาย (perm_merge_by_manager)
   - ผู้จัดการคนอื่น (รวมตัวเอง) / ย้ายสาขา / ตั้งหรือปลดผู้จัดการ = ผู้ดูแลเท่านั้น (manager_target_error)
   - ทุกครั้งที่กดบันทึกต้องใส่ PIN ของตัวเอง (เครื่อง POS ใช้ร่วมกัน) · กรอกผิด 5 ครั้งล็อก 15 นาที (staff_pin_confirm)
   ตรวจข้อมูล / เขียน / ลงประวัติ ใช้ staff_act_* ตัวเดียวกับหน้าผู้ดูแล (api/branch-staff.php)
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user  = require_login();                 // ไม่ใช่ผู้จัดการสาขา → กลับหน้าแรก · ผู้ดูแล → adm-users.php
$code  = work_branch($user);
$err   = '';
$errAt = '';                              // 'new' หรือ username ที่มีข้อผิดพลาด
$sel   = (isset($_GET['u']) && is_string($_GET['u'])) ? trim($_GET['u']) : '';
$add   = isset($_GET['add']);
$old   = array();
$grant = perm_manager_grantable();
$only  = array_values(array_diff(array_keys(perm_groups()), array('extra')));   // หมวดที่ผู้จัดการติ๊กให้ได้

/* ---------- บันทึก ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? (string) $_POST['act'] : '';
    $sel = isset($_POST['u']) ? trim((string) $_POST['u']) : '';
    $go  = '';

    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';

    } elseif (!in_array($act, array('add', 'save', 'pin', 'off', 'on', 'delete'), true)) {
        $err = 'ไม่พบคำสั่งนี้';

    } else {
        $errAt = ($act === 'add') ? 'new' : $sel;
        $add   = ($act === 'add');
        /* จำค่าที่กรอกไว้ — ยืนยัน PIN ผิดจะได้ไม่ต้องกรอกใหม่ */
        if ($act === 'add') {
            $in = array(
                'name'     => trim(preg_replace('/\s+/u', ' ', isset($_POST['name']) ? $_POST['name'] : '')),
                'username' => strtolower(trim(isset($_POST['username']) ? $_POST['username'] : '')),
                'initials' => trim(isset($_POST['initials']) ? $_POST['initials'] : ''),
                'branch'   => $code,                                          // เพิ่มได้เฉพาะสาขาตัวเอง
                'pin'      => isset($_POST['pin']) ? $_POST['pin'] : '',
                'perms'    => array_values(array_intersect(read_perms(), $grant)),
            );
            $old['new'] = array('name' => $in['name'], 'username' => $in['username'], 'initials' => $in['initials'], 'perms' => $in['perms']);
        } elseif ($act === 'save') {
            $name  = trim(preg_replace('/\s+/u', ' ', isset($_POST['name']) ? $_POST['name'] : ''));
            $ini   = trim(isset($_POST['initials']) ? $_POST['initials'] : '');
            $ticked = array_values(array_intersect(read_perms(), $grant));
            $old[$sel] = array('name' => $name, 'initials' => $ini, 'perms' => $ticked);
        }

        /* 1) ยืนยัน PIN ของผู้จัดการ  2) แก้คนนี้ได้ไหม  3) ทำงาน */
        $err = staff_pin_confirm($user['username'], isset($_POST['my_pin']) ? $_POST['my_pin'] : '');
        if ($err === '' && $act !== 'add') {
            $err = manager_target_error($user, $sel);
        }
        if ($err === '') {
            if ($act === 'add') {
                $r = staff_act_add($in, $user);
                if (isset($r['error'])) {
                    $err = $r['error'];
                } else {
                    $go = 'team.php?u=' . rawurlencode($r['username']) . '&ok=add';
                }
            } elseif ($act === 'save') {
                $all = staff_all();
                $err = staff_act_save($sel, $name, $ini, perm_merge_by_manager($ticked, $all[$sel]['perms']), $user);
                $go  = 'team.php?u=' . rawurlencode($sel) . '&ok=save';
            } elseif ($act === 'pin') {
                $err = staff_act_pin($sel, isset($_POST['pin']) ? $_POST['pin'] : '', $user);
                $go  = 'team.php?u=' . rawurlencode($sel) . '&ok=pin';
            } elseif ($act === 'off' || $act === 'on') {
                $err = staff_act_active($sel, $act === 'on', isset($_POST['why']) ? $_POST['why'] : '', $user);
                $go  = 'team.php?u=' . rawurlencode($sel) . '&ok=' . $act;
            } elseif ($act === 'delete') {
                $all = staff_all();
                $nm  = $all[$sel]['name'];
                $err = staff_act_delete($sel, !empty($_POST['sure']), $user);
                if ($err === '') {
                    $_SESSION['flash'] = 'ลบ ' . $nm . ' แล้ว';
                    $go = 'team.php';
                }
            }
        }
    }

    if ($go !== '' && $err === '') {
        header('Location: ' . url($go));
        exit;
    }
}

/* ---------- ข้อมูลที่แสดง ---------- */
$staff = array();
foreach (staff_all() as $k => $u) {
    if ($u['branch'] === $code) {
        $staff[$k] = $u;
    }
}
$picked  = (!$add && isset($staff[$sel])) ? $staff[$sel] : null;
$canEdit = ($picked !== null && manager_target_error($user, $sel) === '');
$okMsg   = array(
    'add'  => 'เพิ่มพนักงานแล้ว — เข้าระบบด้วยการแตะชื่อ + PIN ได้ทันที',
    'save' => 'บันทึกแล้ว สิทธิ์มีผลทันที',
    'pin'  => 'รีเซ็ต PIN แล้ว — แจ้ง PIN ใหม่ให้พนักงานโดยตรง',
    'off'  => 'พักงานแล้ว — เข้าระบบไม่ได้ แต่ชื่อในเอกสารเก่ายังอยู่',
    'on'   => 'เปิดใช้งานอีกครั้งแล้ว',
);
$ok = (isset($_GET['ok']) && isset($okMsg[$_GET['ok']])) ? $okMsg[$_GET['ok']] : '';
$nActive = 0;
foreach ($staff as $s) {
    $nActive += user_active($s) ? 1 : 0;
}
/* ช่อง PIN ยืนยันตัวตนของผู้จัดการ — ใส่ทุกฟอร์ม */
$myPin = function ($id) {
    return '<label class="sr-only" for="' . e($id) . '">PIN ของคุณ (ยืนยันตัวตน)</label>'
         . '<input class="input adm-pin team-pin" type="password" id="' . e($id) . '" name="my_pin" inputmode="numeric" maxlength="4"'
         . ' pattern="[0-9]{4}" placeholder="PIN ของคุณ" autocomplete="off" required>';
};

$branch     = $code;
$PAGE_TITLE = 'พนักงานในสาขา';
$PAGE_SUB   = branch_name($code) . ' · ใช้งาน ' . $nActive . ' คน' . (count($staff) > $nActive ? ' · พักงาน ' . (count($staff) - $nActive) . ' คน' : '');
$NAV_ACTIVE = 'team.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($err !== '' && ($errAt === '' || ($errAt !== 'new' && $picked === null))) { /* คนที่ไม่ได้อยู่สาขานี้ไม่มีการ์ดแก้ไข → แสดงข้อผิดพลาดด้านบน */ ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span></div>
<?php } ?>
<?php if ($ok !== '') { ?>
  <div class="alert alert-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span><?php echo e($ok) ?></span></div>
<?php } ?>

<p class="team-note"><svg class="ico"><use href="#i-info"/></svg>
  ทุกครั้งที่กดบันทึกต้องใส่ PIN ของคุณเพื่อยืนยันตัวตน · ย้ายสาขา ตั้ง / ปลดผู้จัดการ และแก้ข้อมูลผู้จัดการ ทำได้เฉพาะผู้ดูแล</p>

<?php if ($add) {
    $nv = isset($old['new']) ? $old['new'] : array('name' => '', 'username' => '', 'initials' => '', 'perms' => array_values(array_intersect(perm_default(), $grant))); ?>
<!-- ==================== เพิ่มพนักงาน ==================== -->
<p class="hist-back"><a class="btn btn-ghost btn-sm" href="team.php">‹ กลับไปรายชื่อพนักงาน</a></p>
<section class="card">
  <div class="card-head">
    <div><h2>เพิ่มพนักงานเข้า<?php echo e(branch_name($code)) ?></h2><span class="sub">พนักงานเข้าระบบด้วยการแตะชื่อ + PIN 4 หลัก · สิทธิ์แก้ภายหลังได้</span></div>
  </div>
  <?php if ($errAt === 'new' && $err !== '') { ?>
    <div class="alert alert-error adm-ok" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span></div>
  <?php } ?>
  <form class="adm-sec" method="post" action="team.php?add=1">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
    <input type="hidden" name="act" value="add">
    <div class="adm-fields">
      <div class="field">
        <label for="n-name">ชื่อ–นามสกุล</label>
        <input class="input" type="text" id="n-name" name="name" value="<?php echo e($nv['name']) ?>" maxlength="60" required autocomplete="off" placeholder="เช่น ปิยะ ขยันดี">
      </div>
      <div class="field">
        <label for="n-pin">PIN 4 หลักของพนักงานใหม่</label>
        <input class="input adm-pin" type="text" id="n-pin" name="pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" required autocomplete="off" placeholder="••••">
        <small class="adm-hint">ห้ามซ้ำกับคนอื่นในสาขาเดียวกัน</small>
      </div>
      <div class="field">
        <label for="n-user">ชื่อผู้ใช้ <small class="adm-none">(ไม่บังคับ)</small></label>
        <input class="input" type="text" id="n-user" name="username" value="<?php echo e($nv['username']) ?>" maxlength="20" autocomplete="off" placeholder="เว้นว่าง = ตั้งให้อัตโนมัติ">
        <small class="adm-hint">ใช้อ้างอิงภายใน · a–z 0–9 _</small>
      </div>
      <div class="field">
        <label for="n-ini">อักษรย่อบนปุ่มเลือกชื่อ <small class="adm-none">(ไม่บังคับ)</small></label>
        <input class="input adm-pin" type="text" id="n-ini" name="initials" value="<?php echo e($nv['initials']) ?>" maxlength="4" autocomplete="off" placeholder="อัตโนมัติ">
      </div>
    </div>
    <?php perm_boxes($nv['perms'], $code, $only); ?>
    <div class="adm-inline team-confirm">
      <?php echo $myPin('pin-add') ?>
      <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มพนักงาน</button>
    </div>
  </form>
</section>

<?php } else { ?>

<?php if ($picked !== null) {
    $pv     = isset($old[$sel]) ? $old[$sel] : array('name' => $picked['name'], 'initials' => $picked['initials'], 'perms' => $picked['perms']);
    $active = user_active($picked);
    $reason = $canEdit ? staff_data_reason($sel) : '';
    $extra  = array_values(array_diff($picked['perms'], $grant)); ?>
<!-- ==================== แก้ไขพนักงานที่เลือก ==================== -->
<section class="card adm-edit">
  <div class="card-head">
    <div class="adm-who">
      <span class="av"><?php echo e(user_initial($picked)) ?></span>
      <div>
        <h2><?php echo e($picked['name']) ?>
          <?php if (in_array('manager', $picked['perms'], true)) { ?><span class="bdg bdg-ok">ผู้จัดการสาขา</span><?php } ?>
          <?php if (!$active) { ?><span class="bdg bdg-out">พักงาน</span><?php } ?></h2>
        <span class="sub"><?php echo e($sel) ?> · <?php echo e(branch_name($picked['branch'])) ?>
          <?php if (!empty($picked['since'])) { ?> · ประจำสาขานี้ตั้งแต่ <?php echo e(thai_date_full(strtotime($picked['since']))) ?><?php } ?></span>
      </div>
    </div>
    <a class="btn btn-ghost btn-sm" href="team.php"><svg class="ico"><use href="#i-x"/></svg> ปิด</a>
  </div>
  <?php if ($errAt === $sel && $err !== '') { ?>
    <div class="alert alert-error adm-ok" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?php echo e($err) ?></span></div>
  <?php } ?>

  <?php if (!$canEdit) { ?>
    <div class="adm-sec">
      <p class="adm-hint"><?php echo e(manager_target_error($user, $sel)) ?> — ติดต่อผู้ดูแลถ้าต้องการเปลี่ยนแปลง</p>
      <p><?php echo e(perm_names($picked['perms'])) ?></p>
    </div>
  <?php } else { ?>

    <?php if ($active) { ?>
    <!-- ข้อมูล + สิทธิ์ -->
    <form class="adm-sec" method="post" action="team.php?u=<?php echo e(rawurlencode($sel)) ?>">
      <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="u" value="<?php echo e($sel) ?>">
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
      <?php perm_boxes($pv['perms'], $picked['branch'], $only); ?>
      <?php if ($extra) { ?>
        <p class="adm-hint">สิทธิ์เสริมจากผู้ดูแล (ไม่เปลี่ยนตามหน้านี้): <?php echo e(perm_names($extra)) ?></p>
      <?php } ?>
      <div class="adm-inline team-confirm">
        <?php echo $myPin('pin-save') ?>
        <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-check"/></svg> บันทึก</button>
      </div>
    </form>

    <div class="adm-row">
      <form class="adm-sec" method="post" action="team.php?u=<?php echo e(rawurlencode($sel)) ?>">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="act" value="pin">
        <input type="hidden" name="u" value="<?php echo e($sel) ?>">
        <h3>รีเซ็ต PIN</h3>
        <p class="adm-hint">ใช้เมื่อพนักงานลืม PIN · ห้ามซ้ำกับคนอื่นในสาขาเดียวกัน</p>
        <div class="adm-inline">
          <label class="sr-only" for="pin">PIN ใหม่ของพนักงาน</label>
          <input class="input adm-pin" type="text" id="pin" name="pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" placeholder="PIN ใหม่" autocomplete="off" required>
          <?php echo $myPin('pin-reset') ?>
          <button class="btn btn-ghost" type="submit">ตั้ง PIN ใหม่</button>
        </div>
      </form>
    </div>
    <?php } ?>

    <!-- พักงาน / เปิดใช้งาน · ลบ -->
    <div class="adm-row br-danger">
      <form class="adm-sec" method="post" action="team.php?u=<?php echo e(rawurlencode($sel)) ?>">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="act" value="<?php echo $active ? 'off' : 'on' ?>">
        <input type="hidden" name="u" value="<?php echo e($sel) ?>">
        <?php if ($active) { ?>
          <h3>พักงาน / ลาออก</h3>
          <p class="adm-hint">เข้าระบบไม่ได้ ไม่ขึ้นในรายชื่อหน้าเข้าระบบ แต่ชื่อในบิลและเอกสารเก่ายังอยู่ · เปิดกลับมาได้</p>
          <div class="adm-inline">
            <input class="input" type="text" name="why" placeholder="เหตุผล เช่น ลาออก / ลาคลอด" autocomplete="off">
            <?php echo $myPin('pin-off') ?>
            <button class="btn btn-ghost" type="submit"><svg class="ico"><use href="#i-ban"/></svg> พักงาน</button>
          </div>
        <?php } else { ?>
          <h3>เปิดใช้งานอีกครั้ง</h3>
          <p class="adm-hint">กลับมาเข้าระบบได้ด้วย PIN เดิม สิทธิ์เดิมยังอยู่</p>
          <div class="adm-inline">
            <?php echo $myPin('pin-on') ?>
            <button class="btn btn-ghost" type="submit"><svg class="ico"><use href="#i-check"/></svg> เปิดใช้งาน</button>
          </div>
        <?php } ?>
      </form>
      <form class="adm-sec" method="post" action="team.php?u=<?php echo e(rawurlencode($sel)) ?>">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
        <input type="hidden" name="act" value="delete">
        <input type="hidden" name="u" value="<?php echo e($sel) ?>">
        <h3>ลบพนักงาน</h3>
        <?php if ($reason !== '') { ?>
          <p class="adm-hint">ลบไม่ได้ เพราะ<?php echo e($reason) ?> — ใช้พักงาน / ลาออกแทน</p>
        <?php } else { ?>
          <p class="adm-hint">ยังไม่เคยทำรายการ ลบได้ · ลบแล้วกู้คืนไม่ได้</p>
          <label class="br-sure"><input type="checkbox" name="sure" value="1" required> ยืนยันลบ <?php echo e($picked['name']) ?></label>
          <div class="adm-inline">
            <?php echo $myPin('pin-del') ?>
            <button class="btn btn-ghost br-del" type="submit"><svg class="ico"><use href="#i-trash"/></svg> ลบพนักงาน</button>
          </div>
        <?php } ?>
      </form>
    </div>
  <?php } ?>
</section>
<?php } ?>

<!-- ==================== รายชื่อพนักงานในสาขา ==================== -->
<section class="card">
  <div class="card-head">
    <div><h2>พนักงาน<?php echo e(branch_name($code)) ?></h2><span class="sub"><?php echo count($staff) ?> คน · กดแก้ไขเพื่อเปลี่ยนสิทธิ์ / PIN / พักงาน</span></div>
    <a class="btn btn-primary btn-sm" href="team.php?add=1"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มพนักงาน</a>
  </div>
  <?php if (!$staff) { ?>
    <p class="empty">ยังไม่มีพนักงานในสาขานี้</p>
  <?php } else { ?>
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th>พนักงาน</th><th>เมนูที่ใช้ได้</th><th>สิทธิ์เสริม</th><th></th></tr></thead>
        <tbody>
          <?php $pg = perm_groups();
          foreach ($staff as $k => $u) { $sum = perm_summary($u['perms']); $isMgr = in_array('manager', $u['perms'], true); ?>
            <tr class="<?php echo $k === $sel ? 'on' : '' ?>">
              <td data-label="พนักงาน"><b><?php echo e($u['name']) ?></b>
                <?php if ($isMgr) { ?><span class="bdg bdg-ok">ผู้จัดการสาขา</span><?php } ?>
                <?php if (!user_active($u)) { ?><span class="bdg bdg-out">พักงาน</span><?php } ?>
                <small><?php echo e($k) ?><?php echo $k === $user['username'] ? ' · คุณ' : '' ?></small></td>
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
              <td class="r">
                <?php if ($isMgr) { ?>
                  <small class="adm-none">ผู้ดูแลจัดการ</small>
                <?php } else { ?>
                  <a class="btn btn-ghost btn-sm" href="team.php?u=<?php echo e(rawurlencode($k)) ?>">แก้ไข</a>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } ?>
</section>
<?php } ?>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
