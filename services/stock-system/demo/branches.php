<?php
/* ==========================================================
   AOSTOCK DEMO — ตั้งค่าสาขา (เฉพาะผู้ดูแล)
   ----------------------------------------------------------
   ค่าที่เคยฝังไว้ในโค้ด ย้ายมาให้ผู้ดูแลตั้งเองรายสาขา
     - วันเริ่มรอบตรวจนับ (1–28)
     - แก้เอกสาร / รับคืนสินค้าย้อนหลังได้กี่วัน
     - ระหว่างร้านเปิด ตรวจนับได้ครั้งละกี่รายการ
     - เงินทอนมาตรฐาน
   ทุกการแก้ไขบันทึกลงประวัติการทำรายการของสาขานั้น

   เดโมเก็บไว้ใน $_SESSION['cfg']['branch'] (ดู branch_setting())
   ระบบจริง: UPDATE ao_stock_branch
   ========================================================== */

require_once dirname(__FILE__) . '/inc/auth.php';
require_once dirname(__FILE__) . '/inc/data.php';
require_once dirname(__FILE__) . '/inc/store.php';

$user = require_login();
if ($user['role'] !== 'admin') {
    header('Location: ' . url('dashboard.php'));
    exit;
}

/* ชื่อ คำอธิบาย และหน่วยของแต่ละค่า */
$fields = array(
    'count_day'        => array('label' => 'รอบตรวจนับเริ่มวันที่',           'unit' => 'ของทุกเดือน',
                                'hint'  => 'ต้องนับสินค้าให้ครบทุกรายการภายในรอบ (รอบละ 1 เดือน) · 1–28'),
    'backdate_days'    => array('label' => 'แก้เอกสาร / รับคืนย้อนหลังได้',    'unit' => 'วัน',
                                'hint'  => 'ใช้กับพนักงานที่มีสิทธิ์เสริม “แก้ย้อนหลัง” หรือ “รับคืนสินค้า” · เกินนี้ผู้ดูแลทำเอง · 0–60'),
    'count_open_limit' => array('label' => 'ระหว่างร้านเปิด ตรวจนับได้ครั้งละ', 'unit' => 'รายการ',
                                'hint'  => 'นับทีละน้อยแล้วบันทึกทันที กันยอดคลาดเพราะมีการขายแทรก · 0 = ไม่จำกัด'),
    'default_float'    => array('label' => 'เงินทอนมาตรฐาน',                 'unit' => 'บาท',
                                'hint'  => 'ค่าเริ่มต้นของเงินทอนที่แยกไว้ตอนปิดร้านสำหรับวันถัดไป'),
);

$err = '';
$okB = isset($_GET['ok']) ? $_GET['ok'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bc = isset($_POST['b']) ? $_POST['b'] : '';
    $br = demo_branches();
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } elseif (!isset($br[$bc])) {
        $err = 'ไม่พบสาขานี้';
    } else {
        $rules   = branch_setting_rules();
        $changes = array();
        foreach ($fields as $k => $f) {
            $raw = isset($_POST[$k]) ? trim($_POST[$k]) : '';
            if ($raw === '' || !preg_match('/^\d+$/', $raw)) {
                $err = $f['label'] . ' ต้องเป็นตัวเลข';
                break;
            }
            $v = (int) $raw;
            if ($v < $rules[$k][0] || $v > $rules[$k][1]) {
                $err = $f['label'] . ' ต้องอยู่ระหว่าง ' . number_format($rules[$k][0]) . '–' . number_format($rules[$k][1]);
                break;
            }
            $old = branch_setting($bc, $k);
            if ($old !== $v) {
                $changes[$f['label']] = number_format($old) . ' → ' . number_format($v) . ' ' . $f['unit'];
            }
        }
        if ($err === '') {
            foreach ($fields as $k => $f) {
                branch_setting_set($bc, $k, (int) $_POST[$k]);
            }
            if ($changes) {
                $changes['แก้โดย'] = $user['name'] . ' (ผู้ดูแล)';
                log_add($bc, 'setting', $user, 'แก้ค่าตั้งของ' . branch_name($bc), $changes);
            }
            header('Location: ' . url('branches.php?ok=' . rawurlencode($bc) . '#b-' . $bc));
            exit;
        }
    }
}

$branch     = work_branch($user);
$PAGE_TITLE = 'ตั้งค่าสาขา';
$PAGE_SUB   = count(demo_branches()) . ' สาขา · มีผลทันทีหลังบันทึก';
$NAV_ACTIVE = 'branches.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($err !== ''): ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
<?php endif; ?>

<?php foreach (demo_branches() as $bc => $b): ?>
  <section class="card" id="b-<?= e($bc) ?>">
    <div class="card-head">
      <div>
        <h2><?= e($b['name']) ?></h2>
        <span class="sub">พนักงาน <?= count(branch_staff($bc)) ?> คน ·
          <?= store_is_open($bc) ? 'ร้านเปิดอยู่' : (store_is_closed($bc) ? 'ปิดร้านแล้ววันนี้' : 'ยังไม่เปิดร้านวันนี้') ?></span>
      </div>
      <a class="btn btn-ghost btn-sm" href="users.php"><svg class="ico"><use href="#i-users"/></svg> ผู้ใช้และสิทธิ์</a>
    </div>

    <?php if ($okB === $bc): ?>
      <div class="alert alert-ok adm-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span>บันทึกแล้ว มีผลทันที</span></div>
    <?php endif; ?>

    <form class="adm-sec" method="post" action="branches.php">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="b" value="<?= e($bc) ?>">
      <div class="adm-fields">
        <?php foreach ($fields as $k => $f): $r = branch_setting_rules(); ?>
          <div class="field">
            <label for="<?= e($bc . '-' . $k) ?>"><?= e($f['label']) ?></label>
            <div class="adm-unit">
              <input class="input" type="number" id="<?= e($bc . '-' . $k) ?>" name="<?= e($k) ?>"
                     value="<?= (int) branch_setting($bc, $k) ?>" min="<?= (int) $r[$k][0] ?>" max="<?= (int) $r[$k][1] ?>"
                     step="<?= $k === 'default_float' ? 100 : 1 ?>" inputmode="numeric" required>
              <span><?= e($f['unit']) ?></span>
            </div>
            <small class="adm-hint"><?= e($f['hint']) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
      <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-check"/></svg> บันทึก<?= e($b['name']) ?></button>
    </form>
  </section>
<?php endforeach; ?>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
