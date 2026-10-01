<?php
/* ==========================================================
   AOSTOCK DEMO — [ผู้ดูแล] เพิ่มพนักงาน (หน้าแยกจาก adm-users.php)
   ----------------------------------------------------------
   กรอกชื่อ สาขา PIN และสิทธิ์ → บันทึกแล้วกลับไปหน้าจัดการพนักงาน (เปิดคนที่เพิ่งเพิ่ม)
   ?b=รหัสสาขา = เลือกสาขาไว้ให้ก่อน
   กติกา: PIN 4 หลักห้ามซ้ำในสาขาเดียวกัน · ชื่อผู้ใช้เว้นว่าง = ตั้งให้อัตโนมัติ (staffN) · ต้องมีสิทธิ์อย่างน้อย 1 อย่าง
   เดโมเก็บใน $_SESSION['cfg']['newuser'] · ระบบจริง: INSERT ao_stock_staff
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

$err = '';
$old = array();
$br  = demo_branches();
$def = (isset($_GET['b']) && is_string($_GET['b']) && isset($br[$_GET['b']])) ? $_GET['b'] : key($br);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $all = staff_all();
    $go  = '';
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } else {
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
                $go = 'adm-users.php?u=' . rawurlencode($uname) . '&ok=add';
            }
    }
    if ($go !== '' && $err === '') {
        header('Location: ' . url($go));
        exit;
    }
}

$nv = isset($old['new']) ? $old['new'] : array('name' => '', 'username' => '', 'initials' => '', 'branch' => $def, 'perms' => perm_default());

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = 'เพิ่มพนักงาน';
$PAGE_SUB       = 'พนักงานเข้าระบบด้วยการแตะชื่อ + PIN 4 หลัก';
$NAV_ACTIVE     = 'adm-users.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<p class="hist-back"><a class="btn btn-ghost btn-sm" href="adm-users.php">‹ กลับไปจัดการพนักงาน</a></p>

<?php if ($err !== ''): ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
<?php endif; ?>

<section class="card">
  <div class="card-head">
    <div><h2>ข้อมูลพนักงานใหม่</h2><span class="sub">สิทธิ์แก้ภายหลังได้ที่หน้าจัดการพนักงาน · มีผลทันที</span></div>
  </div>
  <form class="adm-sec" method="post" action="adm-user-add.php">
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
</section>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
