<?php
/* ==========================================================
   AOSTOCK DEMO — จัดการพนักงาน (เฉพาะผู้ดูแล)
   ----------------------------------------------------------
   เพิ่ม · แก้ไข · พักงาน / เปิดใช้งาน · ลบ
   - สิทธิ์การเข้าถึงกำหนดรายคน
       เมนูที่ใช้ได้: ขายสินค้า+เปิด/ปิดร้าน · นำเข้าสินค้า · เบิก/ตัดออก · ตรวจนับ/ปรับยอด
                      · ประวัติการทำรายการ · รับคืนสินค้า
       สิทธิ์เสริม:   แก้งานคนอื่น · แก้ย้อนหลัง · รายงานทั้งสาขา
   - PIN 4 หลัก ห้ามซ้ำกับคนอื่นในสาขาเดียวกัน
   - ย้ายสาขามีผลตั้งแต่วันนี้ ยอดเก่ายังผูกกับสาขาเดิม
   - พักงาน / ลาออก: เข้าระบบไม่ได้ ไม่ขึ้นในรายชื่อ แต่ชื่อในเอกสารเก่ายังอยู่
   - ลบ: ได้เฉพาะคนที่ยังไม่เคยทำรายการเลย คนที่มีประวัติแล้วให้ใช้ "พักงาน / ลาออก"
   ทุกการแก้ไขบันทึกลงประวัติของสาขา

   เดโมเก็บไว้ใน $_SESSION['cfg']['newuser'] (คนที่เพิ่มใหม่) และ $_SESSION['cfg']['user'] (สิ่งที่แก้)
   ระบบจริง: INSERT / UPDATE ao_stock_staff · พักงาน = is_active = 0
   เขียนให้รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

require_once dirname(__FILE__) . '/inc/auth.php';
require_once dirname(__FILE__) . '/inc/data.php';
require_once dirname(__FILE__) . '/inc/store.php';

$user = require_login();
if ($user['role'] !== 'admin') {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$plist = perm_list();
$err   = '';
$errAt = '';                                  // 'new' หรือ username ที่มีข้อผิดพลาด
$sel   = isset($_GET['u']) ? trim($_GET['u']) : '';
$old   = array();

/** พนักงานทั้งหมด (รวมที่พักงาน ไม่รวมผู้ดูแล / บัญชี) */
function staff_all()
{
    $out = array();
    foreach (demo_users_all() as $k => $u) {
        if ($u['role'] === 'staff') {
            $out[$k] = $u;
        }
    }
    return $out;
}

function perm_names($keys)
{
    $pl  = perm_list();
    $out = array();
    foreach ($keys as $k) {
        if (isset($pl[$k])) {
            $out[] = $pl[$k]['short'];
        }
    }
    return $out ? implode(' · ', $out) : 'ไม่มี';
}

/** อักษรย่อจากชื่อ — ตัวแรกของชื่อและนามสกุล (ข้ามสระ/วรรณยุกต์ที่วางบน-ล่าง) */
function auto_initials($name)
{
    $parts = preg_split('/\s+/u', trim($name));
    $out   = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        if (preg_match('/[\p{L}\p{N}]/u', $p, $m)) {
            $out .= $m[0];
        }
    }
    return $out !== '' ? $out : '-';
}

/** เคยทำรายการแล้วหรือยัง — คืนเหตุผล (ว่าง = ยังไม่เคย ลบได้) */
function staff_data_reason($k)
{
    if (array_key_exists($k, demo_users_base())) {
        return 'มียอดขายและประวัติการทำงานแล้ว';
    }
    foreach (array('sale' => 'บิลขาย', 'recv' => 'ใบรับเข้า', 'issue' => 'ใบเบิก', 'adj' => 'ใบตรวจนับ', 'ret' => 'ใบรับคืน') as $s => $lb) {
        if (!empty($_SESSION[$s])) {
            foreach ($_SESSION[$s] as $rows) {
                foreach ($rows as $r) {
                    if ((isset($r['by_user']) && $r['by_user'] === $k)) {
                        return 'มี' . $lb . 'ที่เคยทำแล้ว';
                    }
                }
            }
        }
    }
    if (!empty($_SESSION['store'])) {
        foreach ($_SESSION['store'] as $st) {
            if (isset($st['opened_user']) && $st['opened_user'] === $k) {
                return 'เคยเปิดร้านแล้ว';
            }
        }
    }
    return '';
}

/** อ่านสิทธิ์จากฟอร์ม (เรียงตามลำดับใน perm_list) */
function read_perms()
{
    $in  = (isset($_POST['perms']) && is_array($_POST['perms'])) ? $_POST['perms'] : array();
    $out = array();
    foreach (array_keys(perm_list()) as $p) {
        if (in_array($p, $in, true)) {
            $out[] = $p;
        }
    }
    return $out;
}

/** PIN ซ้ำกับใครในสาขา (ว่าง = ไม่ซ้ำ) */
function pin_owner($branch, $pin, $except)
{
    foreach (branch_staff($branch) as $k => $o) {
        if ($k !== $except && $o['pin'] === $pin) {
            return $o['name'];
        }
    }
    return '';
}

/* ---------- บันทึก ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? $_POST['act'] : '';
    $sel = isset($_POST['u']) ? trim($_POST['u']) : '';
    $all = staff_all();
    $br  = demo_branches();
    $go  = '';

    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';

    } elseif ($act === 'add') {
        $errAt = 'new';
        $name  = trim(preg_replace('/\s+/u', ' ', isset($_POST['name']) ? $_POST['name'] : ''));
        $uname = strtolower(trim(isset($_POST['username']) ? $_POST['username'] : ''));
        $ini   = trim(isset($_POST['initials']) ? $_POST['initials'] : '');
        $bc    = isset($_POST['branch']) ? $_POST['branch'] : '';
        $pin   = preg_replace('/\D/', '', isset($_POST['pin']) ? $_POST['pin'] : '');
        $perms = read_perms();
        $old['new'] = array('name' => $name, 'username' => $uname, 'initials' => $ini, 'branch' => $bc, 'perms' => $perms);

        if ($uname === '') {                                 // ไม่กรอก → ตั้งให้อัตโนมัติ
            $n = 1;
            while (isset($all['staff' . $n]) || array_key_exists('staff' . $n, demo_users_all())) {
                $n++;
            }
            $uname = 'staff' . $n;
        }
        $everyone = demo_users_all();
        if ($name === '') {
            $err = 'กรุณากรอกชื่อพนักงาน';
        } elseif (!preg_match('/^[a-z0-9_]{3,20}$/', $uname)) {
            $err = 'ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษพิมพ์เล็ก ตัวเลข หรือ _ ยาว 3–20 ตัว';
        } elseif (isset($everyone[$uname]) || isset($_SESSION['cfg']['newuser'][$uname])) {
            $err = 'ชื่อผู้ใช้ ' . $uname . ' มีคนใช้แล้ว';
        } elseif (!isset($br[$bc])) {
            $err = 'กรุณาเลือกสาขาที่ประจำ';
        } elseif (strlen($pin) !== 4) {
            $err = 'PIN ต้องเป็นตัวเลข 4 หลัก';
        } elseif (($dup = pin_owner($bc, $pin, '')) !== '') {
            $err = 'PIN นี้ซ้ำกับ ' . $dup . ' ในสาขาเดียวกัน กรุณาใช้เลขอื่น';
        } elseif (!$perms) {
            $err = 'กรุณาเลือกสิทธิ์อย่างน้อย 1 อย่าง';
        } else {
            $_SESSION['cfg']['newuser'][$uname] = array(
                'pin' => $pin, 'password' => '', 'name' => $name, 'role' => 'staff', 'branch' => $bc,
                'initials' => $ini !== '' ? $ini : auto_initials($name), 'perms' => $perms,
                'since' => date('Y-m-d'), 'history' => array(), 'active' => true,
            );
            unset($_SESSION['cfg']['user'][$uname]);         // เคยลบชื่อนี้ไปแล้ว → เริ่มใหม่
            log_add($bc, 'setting', $user, 'เพิ่มพนักงานใหม่ ' . $name, array(
                'ชื่อผู้ใช้' => $uname,
                'สิทธิ์'    => perm_names($perms),
                'เพิ่มโดย'  => $user['name'] . ' (ผู้ดูแล)',
            ));
            $go = 'users.php?u=' . rawurlencode($uname) . '&ok=add';
        }

    } elseif (!isset($all[$sel])) {
        $err = 'ไม่พบพนักงานคนนี้';

    } else {
        $u     = $all[$sel];
        $errAt = $sel;

        if ($act === 'save') {                               // ชื่อ อักษรย่อ สิทธิ์
            $name  = trim(preg_replace('/\s+/u', ' ', isset($_POST['name']) ? $_POST['name'] : ''));
            $ini   = trim(isset($_POST['initials']) ? $_POST['initials'] : '');
            $perms = read_perms();
            $old[$sel] = array('name' => $name, 'initials' => $ini, 'perms' => $perms);
            if ($name === '') {
                $err = 'กรุณากรอกชื่อพนักงาน';
            } elseif (!$perms) {
                $err = 'กรุณาเลือกสิทธิ์อย่างน้อย 1 อย่าง — ถ้าไม่ให้ใช้งานแล้ว ใช้ “พักงาน / ลาออก” แทน';
            } else {
                $before  = isset($u['perms']) ? $u['perms'] : array();
                $changes = array();
                if ($name !== $u['name']) {
                    $changes['ชื่อ'] = $u['name'] . ' → ' . $name;
                }
                if ($before != $perms) {
                    $changes['สิทธิ์เดิม'] = perm_names($before);
                    $changes['สิทธิ์ใหม่'] = perm_names($perms);
                }
                $_SESSION['cfg']['user'][$sel]['name']     = $name;
                $_SESSION['cfg']['user'][$sel]['initials'] = $ini !== '' ? $ini : auto_initials($name);
                $_SESSION['cfg']['user'][$sel]['perms']    = $perms;
                if ($changes) {
                    $changes['แก้โดย'] = $user['name'] . ' (ผู้ดูแล)';
                    log_add($u['branch'], 'setting', $user, 'แก้ข้อมูลพนักงาน ' . $name, $changes);
                }
                $go = 'users.php?u=' . rawurlencode($sel) . '&ok=save';
            }

        } elseif ($act === 'pin') {
            $pin = preg_replace('/\D/', '', isset($_POST['pin']) ? $_POST['pin'] : '');
            if (strlen($pin) !== 4) {
                $err = 'PIN ต้องเป็นตัวเลข 4 หลัก';
            } elseif (($dup = pin_owner($u['branch'], $pin, $sel)) !== '') {
                $err = 'PIN นี้ซ้ำกับ ' . $dup . ' ในสาขาเดียวกัน กรุณาใช้เลขอื่น';
            } else {
                $_SESSION['cfg']['user'][$sel]['pin'] = $pin;
                log_add($u['branch'], 'setting', $user, 'รีเซ็ต PIN ของ ' . $u['name'], array(
                    'แก้โดย' => $user['name'] . ' (ผู้ดูแล)', 'หมายเหตุ' => 'ไม่แสดงเลข PIN ในประวัติ',
                ));
                $go = 'users.php?u=' . rawurlencode($sel) . '&ok=pin';
            }

        } elseif ($act === 'move') {
            $to = isset($_POST['branch']) ? $_POST['branch'] : '';
            if (!isset($br[$to])) {
                $err = 'กรุณาเลือกสาขาปลายทาง';
            } elseif ($to === $u['branch']) {
                $err = $u['name'] . ' อยู่' . branch_name($to) . 'อยู่แล้ว';
            } elseif (pin_owner($to, $u['pin'], $sel) !== '') {
                $err = 'PIN ของ ' . $u['name'] . ' ซ้ำกับพนักงานใน' . branch_name($to) . ' — รีเซ็ต PIN ก่อนแล้วค่อยย้าย';
            } else {
                $_SESSION['cfg']['user'][$sel]['moves'][] = array('branch' => $to, 'from' => date('Y-m-d'));
                $detail = array(
                    'จาก' => branch_name($u['branch']), 'ไป' => branch_name($to),
                    'มีผล' => 'ตั้งแต่วันนี้ ' . thai_date_full(time()),
                    'ยอดเก่า' => 'ยังผูกกับ' . branch_name($u['branch']) . 'เหมือนเดิม',
                    'แก้โดย' => $user['name'] . ' (ผู้ดูแล)',
                );
                log_add($u['branch'], 'setting', $user, 'ย้าย ' . $u['name'] . ' ไป' . branch_name($to), $detail);
                log_add($to, 'setting', $user, 'รับ ' . $u['name'] . ' ย้ายมาจาก' . branch_name($u['branch']), $detail);
                $go = 'users.php?u=' . rawurlencode($sel) . '&ok=move';
            }

        } elseif ($act === 'off' || $act === 'on') {
            $on  = ($act === 'on');
            $why = isset($_POST['why']) ? trim($_POST['why']) : '';
            if ($on && !isset($br[$u['branch']])) {
                $err = 'สาขาเดิมของ ' . $u['name'] . ' ถูกปิดใช้งานแล้ว — เปิดสาขาก่อน หรือย้ายสาขาก่อนเปิดใช้งาน';
            } elseif ($on && pin_owner($u['branch'], $u['pin'], $sel) !== '') {
                $err = 'PIN ของ ' . $u['name'] . ' ซ้ำกับคนอื่นในสาขาแล้ว — รีเซ็ต PIN ก่อนเปิดใช้งาน';
            } else {
                $_SESSION['cfg']['user'][$sel]['active'] = $on;
                log_add($u['branch'], 'setting', $user, ($on ? 'เปิดใช้งาน ' : 'พักงาน / ลาออก ') . $u['name'], array(
                    'เหตุผล' => $on ? '—' : ($why !== '' ? $why : 'ไม่ได้ระบุ'),
                    'แก้โดย' => $user['name'] . ' (ผู้ดูแล)',
                ));
                $go = 'users.php?u=' . rawurlencode($sel) . '&ok=' . $act;
            }

        } elseif ($act === 'delete') {
            $why = staff_data_reason($sel);
            if ($why !== '') {
                $err = 'ลบไม่ได้ เพราะ' . $why . ' — ใช้ “พักงาน / ลาออก” แทน ชื่อในเอกสารเก่าจะไม่หาย';
            } elseif (empty($_POST['sure'])) {
                $err = 'กรุณาติ๊กยืนยันก่อนลบพนักงาน';
            } else {
                unset($_SESSION['cfg']['newuser'][$sel]);
                $_SESSION['cfg']['user'][$sel] = array('deleted' => true);
                log_add($u['branch'], 'setting', $user, 'ลบพนักงาน ' . $u['name'], array('แก้โดย' => $user['name'] . ' (ผู้ดูแล)'));
                $_SESSION['flash'] = 'ลบ ' . $u['name'] . ' แล้ว';
                $go = 'users.php';
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
$NAV_ACTIVE = 'users.php';
require dirname(__FILE__) . '/inc/header.php';

/** ช่องติ๊กสิทธิ์ แยกกลุ่ม "เมนูที่ใช้ได้" / "สิทธิ์เสริม" */
function perm_boxes($checked, $branch)
{
    $pl = perm_list();
    foreach (array('menu' => 'เมนูที่ใช้ได้', 'extra' => 'สิทธิ์เสริม (ให้เฉพาะคนที่ไว้ใจ)') as $g => $title) {
        echo '<h4 class="perm-h">' . e($title) . '</h4><div class="perm-grid">';
        foreach ($pl as $k => $p) {
            if ($p['group'] !== $g) {
                continue;
            }
            echo '<label class="perm-o"><input type="checkbox" name="perms[]" value="' . e($k) . '"'
               . (in_array($k, $checked, true) ? ' checked' : '') . '><span><svg class="ico"><use href="#i-check"/></svg><b>'
               . e($p['label']) . '</b>';
            if ($k === 'backdate' || $k === 'refund') {
                echo '<small>ย้อนหลังได้ ' . (int) backdate_days($branch) . ' วัน (ตั้งที่หน้าจัดการสาขา)</small>';
            }
            if ($k === 'sale') {
                echo '<small>ไม่ติ๊ก = พนักงานคลังอย่างเดียว ไม่ต้องเปิดร้านก่อนใช้งาน</small>';
            }
            echo '</span></label>';
        }
        echo '</div>';
    }
}
?>

<?php if ($err !== '' && $errAt === ''): ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
<?php endif; ?>

<?php if ($picked === null): ?>
<!-- ==================== เพิ่มพนักงาน ==================== -->
<?php $nv = isset($old['new']) ? $old['new'] : array('name' => '', 'username' => '', 'initials' => '', 'branch' => $branch, 'perms' => perm_default()); ?>
<details class="card br-add"<?= $errAt === 'new' ? ' open' : '' ?>>
  <summary class="card-head">
    <div><h2>เพิ่มพนักงาน</h2><span class="sub">พนักงานเข้าระบบด้วยการแตะชื่อ + PIN 4 หลัก</span></div>
    <span class="btn btn-primary btn-sm"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มพนักงาน</span>
  </summary>
  <?php if ($errAt === 'new'): ?>
    <div class="alert alert-error adm-ok" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
  <?php endif; ?>
  <form class="adm-sec" method="post" action="users.php">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="act" value="add">
    <div class="adm-fields">
      <div class="field">
        <label for="n-name">ชื่อ–นามสกุล</label>
        <input class="input" type="text" id="n-name" name="name" value="<?= e($nv['name']) ?>" maxlength="60" required autocomplete="off" placeholder="เช่น ปิยะ ขยันดี">
      </div>
      <div class="field">
        <label for="n-branch">สาขาที่ประจำ</label>
        <select class="input" id="n-branch" name="branch" required>
          <?php foreach (demo_branches() as $bc => $b): ?>
            <option value="<?= e($bc) ?>" <?= $nv['branch'] === $bc ? 'selected' : '' ?>><?= e($b['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="n-pin">PIN 4 หลัก</label>
        <input class="input adm-pin" type="text" id="n-pin" name="pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" required autocomplete="off" placeholder="••••">
        <small class="adm-hint">ห้ามซ้ำกับคนอื่นในสาขาเดียวกัน</small>
      </div>
      <div class="field">
        <label for="n-user">ชื่อผู้ใช้ <small class="adm-none">(ไม่บังคับ)</small></label>
        <input class="input" type="text" id="n-user" name="username" value="<?= e($nv['username']) ?>" maxlength="20" autocomplete="off" placeholder="เว้นว่าง = ตั้งให้อัตโนมัติ">
        <small class="adm-hint">ใช้อ้างอิงภายใน · a–z 0–9 _</small>
      </div>
      <div class="field">
        <label for="n-ini">อักษรย่อบนปุ่มเลือกชื่อ <small class="adm-none">(ไม่บังคับ)</small></label>
        <input class="input adm-pin" type="text" id="n-ini" name="initials" value="<?= e($nv['initials']) ?>" maxlength="4" autocomplete="off" placeholder="อัตโนมัติ">
      </div>
    </div>
    <?php perm_boxes($nv['perms'], $nv['branch']); ?>
    <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มพนักงาน</button>
  </form>
</details>
<?php endif; ?>

<?php if ($ok !== ''): ?>
  <div class="alert alert-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span><?= e($ok) ?></span></div>
<?php endif; ?>

<?php if ($picked !== null):
    $pv     = isset($old[$sel]) ? $old[$sel] : array('name' => $picked['name'], 'initials' => $picked['initials'],
                                                      'perms' => isset($picked['perms']) ? $picked['perms'] : array());
    $active = user_active($picked);
    $reason = staff_data_reason($sel); ?>
  <!-- ==================== แก้ไขพนักงานที่เลือก ==================== -->
  <section class="card adm-edit">
    <div class="card-head">
      <div class="adm-who">
        <span class="av"><?= e(user_initial($picked)) ?></span>
        <div>
          <h2><?= e($picked['name']) ?> <?php if (!$active): ?><span class="bdg bdg-out">พักงาน</span><?php endif; ?></h2>
          <span class="sub"><?= e($sel) ?> · <?= e(branch_name($picked['branch'])) ?>
            <?php if (!empty($picked['since'])): ?> · ประจำสาขานี้ตั้งแต่ <?= e(thai_date_full(strtotime($picked['since']))) ?><?php endif; ?></span>
        </div>
      </div>
      <a class="btn btn-ghost btn-sm" href="users.php"><svg class="ico"><use href="#i-x"/></svg> ปิด</a>
    </div>
    <?php if ($errAt === $sel && $err !== ''): ?>
      <div class="alert alert-error adm-ok" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
    <?php endif; ?>

    <?php if ($active): ?>
    <!-- ข้อมูล + สิทธิ์ -->
    <form class="adm-sec" method="post" action="users.php?u=<?= e(rawurlencode($sel)) ?>">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="u" value="<?= e($sel) ?>">
      <div class="adm-fields">
        <div class="field">
          <label for="e-name">ชื่อ–นามสกุล</label>
          <input class="input" type="text" id="e-name" name="name" value="<?= e($pv['name']) ?>" maxlength="60" required autocomplete="off">
        </div>
        <div class="field">
          <label for="e-ini">อักษรย่อ</label>
          <input class="input adm-pin" type="text" id="e-ini" name="initials" value="<?= e($pv['initials']) ?>" maxlength="4" autocomplete="off">
        </div>
      </div>
      <?php perm_boxes($pv['perms'], $picked['branch']); ?>
      <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-check"/></svg> บันทึก</button>
    </form>

    <div class="adm-row">
      <form class="adm-sec" method="post" action="users.php?u=<?= e(rawurlencode($sel)) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="act" value="pin">
        <input type="hidden" name="u" value="<?= e($sel) ?>">
        <h3>รีเซ็ต PIN</h3>
        <p class="adm-hint">ใช้เมื่อพนักงานลืม PIN · ห้ามซ้ำกับคนอื่นในสาขาเดียวกัน</p>
        <div class="adm-inline">
          <label class="sr-only" for="pin">PIN ใหม่</label>
          <input class="input adm-pin" type="text" id="pin" name="pin" inputmode="numeric" maxlength="4" pattern="[0-9]{4}" placeholder="4 หลัก" autocomplete="off" required>
          <button class="btn btn-ghost" type="submit">ตั้ง PIN ใหม่</button>
        </div>
      </form>
      <form class="adm-sec" method="post" action="users.php?u=<?= e(rawurlencode($sel)) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="act" value="move">
        <input type="hidden" name="u" value="<?= e($sel) ?>">
        <h3>ย้ายสาขา</h3>
        <p class="adm-hint">มีผลตั้งแต่วันนี้ · ยอดขายและเอกสารเก่ายังผูกกับสาขาเดิม</p>
        <div class="adm-inline">
          <label class="sr-only" for="to">สาขาปลายทาง</label>
          <select class="input" id="to" name="branch" required>
            <option value="">เลือกสาขาปลายทาง</option>
            <?php foreach (demo_branches() as $bc => $b): if ($bc === $picked['branch']) { continue; } ?>
              <option value="<?= e($bc) ?>"><?= e($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-ghost" type="submit">ย้าย</button>
        </div>
        <?php if (!empty($picked['history'])): ?>
          <ul class="adm-hist">
            <?php foreach ($picked['history'] as $h): ?>
              <li><?= e(branch_name($h['branch'])) ?> · <?= e(thai_date_full(strtotime($h['from']))) ?> – <?= e(thai_date_full(strtotime($h['to']))) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </form>
    </div>
    <?php endif; ?>

    <!-- พักงาน / เปิดใช้งาน · ลบ -->
    <div class="adm-row br-danger">
      <form class="adm-sec" method="post" action="users.php?u=<?= e(rawurlencode($sel)) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="act" value="<?= $active ? 'off' : 'on' ?>">
        <input type="hidden" name="u" value="<?= e($sel) ?>">
        <?php if ($active): ?>
          <h3>พักงาน / ลาออก</h3>
          <p class="adm-hint">เข้าระบบไม่ได้ ไม่ขึ้นในรายชื่อหน้าเข้าระบบ แต่ชื่อในบิลและเอกสารเก่ายังอยู่ · เปิดกลับมาได้</p>
          <div class="adm-inline">
            <input class="input" type="text" name="why" placeholder="เหตุผล เช่น ลาออก / ลาคลอด" autocomplete="off">
            <button class="btn btn-ghost" type="submit"><svg class="ico"><use href="#i-ban"/></svg> พักงาน</button>
          </div>
        <?php else: ?>
          <h3>เปิดใช้งานอีกครั้ง</h3>
          <p class="adm-hint">กลับมาเข้าระบบได้ด้วย PIN เดิม สิทธิ์เดิมยังอยู่</p>
          <button class="btn btn-ghost" type="submit"><svg class="ico"><use href="#i-check"/></svg> เปิดใช้งาน</button>
        <?php endif; ?>
      </form>
      <form class="adm-sec" method="post" action="users.php?u=<?= e(rawurlencode($sel)) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="act" value="delete">
        <input type="hidden" name="u" value="<?= e($sel) ?>">
        <h3>ลบพนักงาน</h3>
        <?php if ($reason !== ''): ?>
          <p class="adm-hint">ลบไม่ได้ เพราะ<?= e($reason) ?> — ใช้พักงาน / ลาออกแทน</p>
        <?php else: ?>
          <p class="adm-hint">ยังไม่เคยทำรายการ ลบได้ · ลบแล้วกู้คืนไม่ได้</p>
          <label class="br-sure"><input type="checkbox" name="sure" value="1" required> ยืนยันลบ <?= e($picked['name']) ?></label>
          <button class="btn btn-ghost br-del" type="submit"><svg class="ico"><use href="#i-trash"/></svg> ลบพนักงาน</button>
        <?php endif; ?>
      </form>
    </div>
  </section>
<?php endif; ?>

<!-- ==================== รายชื่อพนักงานแยกสาขา ==================== -->
<?php
$groups = array();
foreach ($staff as $k => $u) {
    $g = user_active($u) ? $u['branch'] : '_off';
    $groups[$g][$k] = $u;
}
$order = array_keys(demo_branches());
$order[] = '_off';
foreach ($order as $g):
    if ($g !== '_off' && !isset($groups[$g])) { $groups[$g] = array(); }
    if ($g === '_off' && empty($groups[$g])) { continue; }
    $list = $groups[$g]; ?>
  <section class="card<?= $g === '_off' ? ' br-off' : '' ?>">
    <div class="card-head">
      <div><h2><?= $g === '_off' ? 'พักงาน / ลาออก' : e(branch_name($g)) ?></h2><span class="sub"><?= count($list) ?> คน</span></div>
      <?php if ($g !== '_off'): ?>
        <a class="btn btn-ghost btn-sm" href="branches.php#b-<?= e($g) ?>"><svg class="ico"><use href="#i-settings"/></svg> จัดการสาขา</a>
      <?php endif; ?>
    </div>
    <?php if (!$list): ?>
      <p class="empty">ยังไม่มีพนักงานในสาขานี้</p>
    <?php else: ?>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr><th>พนักงาน</th><th>เมนูที่ใช้ได้</th><th>สิทธิ์เสริม</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($list as $k => $u): $pp = isset($u['perms']) ? $u['perms'] : array(); ?>
              <tr class="<?= $k === $sel ? 'on' : '' ?>">
                <td data-label="พนักงาน"><b><?= e($u['name']) ?></b><small><?= e($k) ?><?= $g === '_off' ? ' · ' . e(branch_name($u['branch'])) : '' ?></small></td>
                <?php foreach (array('menu', 'extra') as $grp): ?>
                  <td data-label="<?= $grp === 'menu' ? 'เมนูที่ใช้ได้' : 'สิทธิ์เสริม' ?>">
                    <?php $n = 0; foreach ($pp as $pk): if (!isset($plist[$pk]) || $plist[$pk]['group'] !== $grp) { continue; } $n++; ?>
                      <span class="bdg <?= $grp === 'menu' ? 'bdg-adj' : 'bdg-ok' ?>"><?= e($plist[$pk]['short']) ?></span>
                    <?php endforeach; ?>
                    <?php if (!$n): ?><span class="adm-none">—</span><?php endif; ?>
                  </td>
                <?php endforeach; ?>
                <td class="r"><a class="btn btn-ghost btn-sm" href="users.php?u=<?= e(rawurlencode($k)) ?>">แก้ไข</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
<?php endforeach; ?>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
