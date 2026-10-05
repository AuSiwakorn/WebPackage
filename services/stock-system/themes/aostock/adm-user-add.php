<?php
/**
 * FILE: themes/aostock/adm-user-add.php
 * ROLE: [ผู้ดูแล] เพิ่มพนักงาน (หน้าแยกจาก adm-users.php)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_staff, ao_stock_staff_branch, ao_stock_log (ผ่าน api.php — staff_create / pin_owner)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] อ่าน / เขียนข้อมูลจากตาราง ao_stock_* ผ่าน api.php (ช่วงที่ 5–9)
 *   - [x] ช่วงที่ 10: แก้ File Header ให้ตรงกับระบบจริง
 *   - [x] ช่วงที่ 11: ช่องติ๊กสิทธิ์ชุดใหม่ (perm_boxes) · ค่าเริ่มต้นตาม perm_default · ตัดสิทธิ์ที่ขาดตัวที่ต้องมีออกตอนบันทึก (read_perms)
 *   - [x] ช่วงที่ 12: ตรวจ / เพิ่ม / ลงประวัติ ย้ายไป staff_act_add (ใช้ร่วมกับ team.php ของผู้จัดการสาขา — ข้อความเดิมทุกคำ)
 *         · ช่อง "ตำแหน่ง" (พนักงาน / ผู้จัดการสาขา) บนสุดของฟอร์ม
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   [ผู้ดูแล] เพิ่มพนักงาน (หน้าแยกจาก adm-users.php)
   ----------------------------------------------------------
   กรอกชื่อ สาขา PIN และสิทธิ์ → บันทึกแล้วกลับไปหน้าจัดการพนักงาน (เปิดคนที่เพิ่งเพิ่ม)
   ?b=รหัสสาขา = เลือกสาขาไว้ให้ก่อน
   กติกา: PIN 4 หลักห้ามซ้ำในสาขาเดียวกัน · ชื่อผู้ใช้เว้นว่าง = ตั้งให้อัตโนมัติ (staffN) · ต้องมีสิทธิ์อย่างน้อย 1 อย่าง
   เก็บในตาราง ao_stock_staff + ao_stock_staff_branch (staff_create ใน api.php) · PIN เก็บเป็น password_hash
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล

$err = '';
$old = array();
$br  = branches_active();
$def = (isset($_GET['b']) && is_string($_GET['b']) && isset($br[$_GET['b']])) ? $_GET['b'] : key($br);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $go  = '';
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } else {
        $in = array(
            'name'     => trim(preg_replace('/\s+/u', ' ', isset($_POST['name']) ? $_POST['name'] : '')),
            'username' => strtolower(trim(isset($_POST['username']) ? $_POST['username'] : '')),
            'initials' => trim(isset($_POST['initials']) ? $_POST['initials'] : ''),
            'branch'   => isset($_POST['branch']) ? $_POST['branch'] : '',
            'pin'      => isset($_POST['pin']) ? $_POST['pin'] : '',
            'perms'    => perm_set_manager(read_perms(), isset($_POST['position']) && $_POST['position'] === 'manager'),   // ตำแหน่ง (ช่วงที่ 12)
        );
        $old['new'] = array('name' => $in['name'], 'username' => $in['username'], 'initials' => $in['initials'],
                            'branch' => $in['branch'], 'perms' => $in['perms']);
        $r = staff_act_add($in, $user);                      // ตรวจ + เพิ่ม + ลงประวัติ (ใช้ร่วมกับ team.php · ช่วงที่ 12)
        if (isset($r['error'])) {
            $err = $r['error'];
        } else {
            $go = 'adm-users.php?u=' . rawurlencode($r['username']) . '&ok=add';
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
    <?php staff_position_field(in_array('manager', $nv['perms'], true)); /* ตำแหน่ง: พนักงาน / ผู้จัดการสาขา (ช่วงที่ 12) */ ?>
    <div class="adm-fields">
      <div class="field">
        <label for="n-name">ชื่อ–นามสกุล</label>
        <input class="input" type="text" id="n-name" name="name" value="<?= e($nv['name']) ?>" maxlength="60" required autocomplete="off" placeholder="เช่น ปิยะ ขยันดี">
      </div>
      <div class="field">
        <label for="n-branch">สาขาที่ประจำ</label>
        <select class="input" id="n-branch" name="branch" required>
          <?php foreach (branches_active() as $bc => $b): ?>
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
