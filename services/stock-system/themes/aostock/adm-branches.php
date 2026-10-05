<?php
/**
 * FILE: themes/aostock/adm-branches.php
 * ROLE: จัดการสาขา (เฉพาะผู้ดูแล)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_branch (+ เช็กข้อมูลของสาขาในตาราง ao_stock_* ก่อนลบ) — ผ่าน api.php
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] สาขาเก็บใน ao_stock_branch (ช่วงที่ 5)
 *   - [x] ช่วงที่ 8: ลบโค้ดล้าง $_SESSION['log'] หลังลบสาขา (ประวัติอยู่ในตารางแล้ว branch_delete ลบให้)
 *   - [x] ช่วงที่ 10: ค่าตั้ง "เป้าต่อคนต่อวัน" (daily_goal) ของภาพรวมพนักงาน
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   จัดการสาขา (เฉพาะผู้ดูแล)
   ----------------------------------------------------------
   เพิ่ม · แก้ไข · ปิดใช้งาน / เปิดใช้งาน · ลบ
   - รหัสสาขา (เช่น HQ, RS) ตั้งตอนเพิ่มแล้วเปลี่ยนไม่ได้ เพราะผูกกับเอกสารทุกใบ
   - แก้ไขได้: ชื่อ ชื่อย่อ ที่อยู่ เบอร์โทร และค่าตั้งของสาขา (รอบนับ / ย้อนหลัง / นับครั้งละ / เงินทอน)
   - ปิดใช้งาน: ซ่อนจากการใช้งาน แต่ประวัติ บิล และรายงานยังอยู่ครบ
       ต้องย้ายพนักงานออกให้หมด และต้องไม่มีร้านเปิดค้างอยู่
   - ลบ: ได้เฉพาะสาขาที่ยังไม่มีข้อมูลเลย (ไม่มีพนักงาน ไม่มีบิล/เอกสาร/สต๊อก)
       สาขาที่มีข้อมูลแล้วให้ "ปิดใช้งาน" แทน เพื่อไม่ให้ยอดเก่าหาย
   - สาขาใหม่ได้รหัสเลขที่บิลอัตโนมัติ ({รหัส}V = VAT, {รหัส} = ไม่ VAT) ฝ่ายบัญชีแก้ทีหลังได้
   ทุกการเปลี่ยนแปลงบันทึกลงประวัติของสาขานั้น

   เก็บในตาราง ao_stock_branch (branch_create / branch_update_info / branch_set_active / branch_delete ใน api.php)
   ปิดใช้งาน = is_active = 0
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();                 // หน้า adm- : เฉพาะผู้ดูแล (พนักงานถูกพากลับเอง)

/* ค่าตั้งของสาขา — ชื่อ คำอธิบาย หน่วย */
$fields = array(
    'count_day'        => array('label' => 'รอบตรวจนับเริ่มวันที่',           'unit' => 'ของทุกเดือน',
                                'hint'  => 'ต้องนับสินค้าให้ครบทุกรายการภายในรอบ (รอบละ 1 เดือน) · 1–28'),
    'backdate_days'    => array('label' => 'แก้เอกสาร / รับคืนย้อนหลังได้',    'unit' => 'วัน',
                                'hint'  => 'ใช้กับพนักงานที่มีสิทธิ์เสริม “แก้ย้อนหลัง” หรือ “รับคืนสินค้า” · เกินนี้ผู้ดูแลทำเอง · 0–60'),
    'count_open_limit' => array('label' => 'ระหว่างร้านเปิด ตรวจนับได้ครั้งละ', 'unit' => 'รายการ',
                                'hint'  => 'นับทีละน้อยแล้วบันทึกทันที กันยอดคลาดเพราะมีการขายแทรก · 0 = ไม่จำกัด'),
    'default_float'    => array('label' => 'เงินทอนมาตรฐาน',                 'unit' => 'บาท',
                                'hint'  => 'ค่าเริ่มต้นของเงินทอนที่แยกไว้ตอนปิดร้านสำหรับวันถัดไป'),
    'daily_goal'       => array('label' => 'เป้าต่อคนต่อวัน',                 'unit' => 'ชิ้น',
                                'hint'  => 'แถบ "เป้าวันนี้" ในภาพรวมของพนักงาน — นับชิ้นที่ขาย รับเข้า เบิก ตรวจนับ และรับคืน · 0 = ไม่ตั้งเป้า'),
);
$info = array(
    'name'    => array('label' => 'ชื่อสาขา',  'max' => 100, 'ph' => 'เช่น สาขาเซ็นทรัลเวสต์เกต', 'req' => true),
    'short'   => array('label' => 'ชื่อย่อ',   'max' => 30,  'ph' => 'เช่น เวสต์เกต (ใช้บนแถบ/การ์ด)', 'req' => false),
    'phone'   => array('label' => 'เบอร์โทร',  'max' => 30,  'ph' => 'เช่น 02-123-4567', 'req' => false),
    'address' => array('label' => 'ที่อยู่',    'max' => 255, 'ph' => 'พิมพ์บนใบเสร็จ', 'req' => false),
);

/* ตัวช่วยของหน้านี้ (branch_data_reason, read_info ฯลฯ) อยู่ที่ admweb/aowebdata/modules/stock/api/branch-staff.php */

/* ---------- บันทึก ---------- */
$err   = '';
$errB  = '';                 // สาขาที่เกิดข้อผิดพลาด ('new' = ฟอร์มเพิ่ม)
$old   = array();
$okB   = isset($_GET['ok']) ? $_GET['ok'] : '';
$okMsg = array('add' => 'เพิ่มสาขาแล้ว — ย้ายพนักงานเข้าสาขาได้ที่หน้าจัดการพนักงาน', 'save' => 'บันทึกแล้ว มีผลทันที',
               'close' => 'ปิดใช้งานสาขาแล้ว — ประวัติและรายงานยังอยู่ครบ', 'open' => 'เปิดใช้งานสาขาอีกครั้งแล้ว');
$okDo  = (isset($_GET['do']) && isset($okMsg[$_GET['do']])) ? $_GET['do'] : 'save';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = isset($_POST['act']) ? $_POST['act'] : '';
    $bc  = isset($_POST['b']) ? strtoupper(trim($_POST['b'])) : '';
    $all = branches_all();
    $go  = '';

    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';

    } elseif ($act === 'add') {
        $errB = 'new';
        list($in, $err) = read_info($info);
        $old['new'] = array_merge($in, $_POST);
        if ($err === '') {
            list($set, $err) = read_settings($fields);
        }
        if ($err === '') {
            if (!preg_match('/^[A-Z0-9]{2,6}$/', $bc)) {
                $err = 'รหัสสาขาต้องเป็นภาษาอังกฤษพิมพ์ใหญ่หรือตัวเลข 2–6 ตัว';
            } elseif (isset($all[$bc])) {
                $err = 'รหัส ' . $bc . ' ใช้กับ' . $all[$bc]['name'] . 'อยู่แล้ว';
            } elseif (prefix_in_use($bc) || prefix_in_use($bc . 'V')) {
                $err = 'รหัส ' . $bc . ' ชนกับรหัสเลขที่บิลที่ฝ่ายบัญชีตั้งไว้ กรุณาใช้รหัสอื่น';
            }
        }
        if ($err === '') {
            /* เลขที่บิลเริ่มต้น + ข้อมูลภาษีตั้งให้ใน branch_create (ฝ่ายบัญชีแก้ได้ที่หน้าตั้งค่าเลขที่บิล) */
            branch_create($bc, $in, $set);
            log_add($bc, 'setting', $user, 'เพิ่มสาขาใหม่ ' . $in['name'], array(
                'รหัสสาขา'   => $bc,
                'เลขที่บิล'   => $bc . 'V… (VAT) · ' . $bc . '… (ไม่ VAT)',
                'เพิ่มโดย'    => $user['name'] . ' (ผู้ดูแล)',
            ));
            $go = 'adm-branches.php?ok=' . rawurlencode($bc) . '&do=add#b-' . $bc;
        }

    } elseif (!isset($all[$bc])) {
        $err = 'ไม่พบสาขานี้';

    } elseif ($act === 'save') {
        $errB = $bc;
        list($in, $err) = read_info($info);
        $old[$bc] = array_merge($in, $_POST);
        if ($err === '') {
            list($set, $err) = read_settings($fields);
        }
        if ($err === '') {
            $changes = array();
            foreach ($in as $k => $v) {
                if ((string) $all[$bc][$k] !== $v) {
                    $changes[$info[$k]['label']] = ($all[$bc][$k] !== '' ? $all[$bc][$k] : '—') . ' → ' . ($v !== '' ? $v : '—');
                }
            }
            branch_update_info($bc, $in);
            foreach ($set as $k => $v) {
                $o = branch_setting($bc, $k);
                if ($o !== $v) {
                    $changes[$fields[$k]['label']] = number_format($o) . ' → ' . number_format($v) . ' ' . $fields[$k]['unit'];
                }
                branch_setting_set($bc, $k, $v);
            }
            if ($changes) {
                $changes['แก้โดย'] = $user['name'] . ' (ผู้ดูแล)';
                log_add($bc, 'setting', $user, 'แก้ข้อมูล' . $in['name'], $changes);
            }
            $go = 'adm-branches.php?ok=' . rawurlencode($bc) . '&do=save#b-' . $bc;
        }

    } elseif ($act === 'close') {
        $errB  = $bc;
        $staff = branch_staff($bc);
        if (empty($all[$bc]['active'])) {
            $err = 'สาขานี้ปิดใช้งานอยู่แล้ว';
        } elseif (count(branches_active()) <= 1) {
            $err = 'ต้องมีสาขาที่เปิดใช้งานอย่างน้อย 1 สาขา';
        } elseif ($staff) {
            $names = array();
            foreach ($staff as $s) {
                $names[] = $s['name'];
            }
            $err = 'ยังมีพนักงานประจำ ' . count($staff) . ' คน (' . implode(', ', $names) . ') — ย้ายไปสาขาอื่นก่อนที่หน้าจัดการพนักงาน';
        } elseif (store_is_open($bc)) {
            $err = 'ร้านของสาขานี้ยังเปิดอยู่วันนี้ — ปิดร้านก่อนจึงจะปิดใช้งานสาขาได้';
        } else {
            branch_set_active($bc, false);
            if (isset($_SESSION['admin_branch']) && $_SESSION['admin_branch'] === $bc) {
                unset($_SESSION['admin_branch']);
            }
            $why = isset($_POST['why']) ? trim($_POST['why']) : '';
            log_add($bc, 'setting', $user, 'ปิดใช้งาน' . $all[$bc]['name'], array(
                'เหตุผล'  => $why !== '' ? $why : 'ไม่ได้ระบุ',
                'ข้อมูลเดิม' => 'ประวัติ บิล และรายงานยังอยู่ครบ',
                'ปิดโดย'  => $user['name'] . ' (ผู้ดูแล)',
            ));
            $go = 'adm-branches.php?ok=' . rawurlencode($bc) . '&do=close#b-' . $bc;
        }

    } elseif ($act === 'open') {
        branch_set_active($bc, true);
        log_add($bc, 'setting', $user, 'เปิดใช้งาน' . $all[$bc]['name'] . 'อีกครั้ง', array('เปิดโดย' => $user['name'] . ' (ผู้ดูแล)'));
        $go = 'adm-branches.php?ok=' . rawurlencode($bc) . '&do=open#b-' . $bc;

    } elseif ($act === 'delete') {
        $errB = $bc;
        $why  = branch_data_reason($bc);
        if ($why !== '') {
            $err = 'ลบไม่ได้ เพราะ' . $why . ' — ใช้ “ปิดใช้งาน” แทน ข้อมูลเก่าจะไม่หาย';
        } elseif (empty($_POST['sure'])) {
            $err = 'กรุณาติ๊กยืนยันก่อนลบสาขา';
        } elseif (!empty($all[$bc]['active']) && count(branches_active()) <= 1) {
            $err = 'ต้องมีสาขาที่เปิดใช้งานอย่างน้อย 1 สาขา';
        } else {
            branch_delete($bc);
            if (isset($_SESSION['admin_branch']) && $_SESSION['admin_branch'] === $bc) {
                unset($_SESSION['admin_branch']);
            }
            $_SESSION['flash'] = 'ลบ' . $all[$bc]['name'] . ' (' . $bc . ') แล้ว';
            $go = 'adm-branches.php';
        }
    }

    if ($go !== '' && $err === '') {
        header('Location: ' . url($go));
        exit;
    }
}

$list = branches_all();
uasort($list, 'branch_list_cmp');
$nOn = count(branches_active());

$branch     = work_branch($user);
$PAGE_TITLE = 'จัดการสาขา';
$PAGE_SUB   = 'เปิดใช้งาน ' . $nOn . ' สาขา' . (count($list) > $nOn ? ' · ปิดใช้งาน ' . (count($list) - $nOn) . ' สาขา' : '');
$NAV_ACTIVE = 'adm-branches.php';
$NO_BRANCH_PICK = true;
require dirname(__FILE__) . '/inc/header.php';

$v = function ($bc, $k, $def) use ($old) {
    return isset($old[$bc][$k]) ? $old[$bc][$k] : $def;
};
?>

<?php if ($err !== '' && $errB === ''): ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
<?php endif; ?>

<!-- ==================== เพิ่มสาขา (ปุ่ม → popup) ==================== -->
<section class="card br-add">
  <div class="card-head">
    <div><h2>เพิ่มสาขาใหม่</h2><span class="sub">รหัสสาขาตั้งครั้งเดียว เปลี่ยนภายหลังไม่ได้</span></div>
    <button type="button" class="btn btn-primary btn-sm" data-fm-open="br-add-modal"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มสาขา</button>
  </div>
</section>

<dialog class="fm-modal" id="br-add-modal" aria-labelledby="br-add-t"<?= $errB === 'new' ? ' data-fm-auto' : '' ?>>
  <div class="fm-head">
    <div><h2 id="br-add-t">เพิ่มสาขาใหม่</h2><span class="sub">รหัสสาขาตั้งครั้งเดียว เปลี่ยนภายหลังไม่ได้</span></div>
    <button type="button" class="icon-btn" data-fm-close aria-label="ปิด"><svg class="ico"><use href="#i-x"/></svg></button>
  </div>
  <?php if ($errB === 'new'): ?>
    <div class="alert alert-error adm-ok" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
  <?php endif; ?>
  <form class="adm-sec" method="post" action="adm-branches.php">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="act" value="add">
    <div class="adm-fields">
      <div class="field">
        <label for="new-b">รหัสสาขา</label>
        <input class="input acct-prefix" type="text" id="new-b" name="b" value="<?= e($v('new', 'b', '')) ?>" maxlength="6"
               pattern="[A-Za-z0-9]{2,6}" required autocomplete="off" placeholder="เช่น WG">
        <small class="adm-hint">ภาษาอังกฤษ/ตัวเลข 2–6 ตัว · เลขที่บิลเริ่มต้นจะเป็น <b id="new-eg">{รหัส}V</b> (VAT) และ {รหัส} (ไม่ VAT)</small>
      </div>
      <?php foreach ($info as $k => $f): ?>
        <div class="field<?= $k === 'address' ? ' br-wide' : '' ?>">
          <label for="new-<?= e($k) ?>"><?= e($f['label']) ?><?= $f['req'] ? '' : ' <small class="adm-none">(ไม่บังคับ)</small>' ?></label>
          <input class="input" type="text" id="new-<?= e($k) ?>" name="<?= e($k) ?>" value="<?= e($v('new', $k, '')) ?>"
                 maxlength="<?= (int) $f['max'] ?>" placeholder="<?= e($f['ph']) ?>" autocomplete="off"<?= $f['req'] ? ' required' : '' ?>>
        </div>
      <?php endforeach; ?>
    </div>
    <h3 class="br-sub">ค่าตั้งของสาขา</h3>
    <div class="adm-fields">
      <?php $r = branch_setting_rules(); $nd = array('count_day' => 1, 'backdate_days' => 7, 'count_open_limit' => 1, 'default_float' => 2000, 'daily_goal' => 0); ?>
      <?php foreach ($fields as $k => $f): ?>
        <div class="field">
          <label for="new-<?= e($k) ?>"><?= e($f['label']) ?></label>
          <div class="adm-unit">
            <input class="input" type="number" id="new-<?= e($k) ?>" name="<?= e($k) ?>" value="<?= (int) $v('new', $k, $nd[$k]) ?>"
                   min="<?= (int) $r[$k][0] ?>" max="<?= (int) $r[$k][1] ?>" step="<?= $k === 'default_float' ? 100 : 1 ?>" inputmode="numeric" required>
            <span><?= e($f['unit']) ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="fm-foot">
      <button class="btn btn-ghost" type="button" data-fm-close>ยกเลิก</button>
      <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-plus"/></svg> เพิ่มสาขา</button>
    </div>
  </form>
</dialog>

<!-- ==================== รายชื่อสาขา (แอคคอร์เดียน) ==================== -->
<?php
/* เปิดสาขาแรกไว้ · ถ้าเพิ่งบันทึก / มีข้อผิดพลาดของสาขาไหน ให้เปิดสาขานั้นแทน */
$openB = ($okB !== '' && isset($list[$okB])) ? $okB : (($errB !== '' && isset($list[$errB])) ? $errB : key($list));
?>
<?php foreach ($list as $bc => $b):
    $on     = !empty($b['active']);
    $staff  = branch_staff($bc);
    $reason = branch_data_reason($bc); ?>
  <details class="card acc-item<?= $on ? '' : ' br-off' ?>" id="b-<?= e($bc) ?>"<?= $bc === $openB ? ' open' : '' ?>>
    <summary class="card-head">
      <div>
        <h2><?= e($b['name']) ?> <span class="bdg bdg-adj"><?= e($bc) ?></span>
          <?php if (!$on): ?><span class="bdg bdg-out">ปิดใช้งาน</span><?php endif; ?></h2>
        <span class="sub">พนักงาน <?= count($staff) ?> คน ·
          <?= $on ? (store_is_open($bc) ? 'ร้านเปิดอยู่' : (store_is_closed($bc) ? 'ปิดร้านแล้ววันนี้' : 'ยังไม่เปิดร้านวันนี้')) : 'ซ่อนจากการใช้งาน ประวัติยังอยู่ครบ' ?>
          · เลขที่บิล <?= e(acct_setting($bc, 'prefix_vat')) ?> / <?= e(acct_setting($bc, 'prefix_novat')) ?></span>
      </div>
      <span class="acc-right">
        <a class="btn btn-ghost btn-sm" href="adm-users.php#g-<?= e($bc) ?>"><svg class="ico"><use href="#i-users"/></svg> จัดการพนักงาน</a>
        <svg class="ico acc-chev" aria-hidden="true"><use href="#i-arrow"/></svg>
      </span>
    </summary>

    <?php if ($okB === $bc): ?>
      <div class="alert alert-ok adm-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span><?= e($okMsg[$okDo]) ?></span></div>
    <?php endif; ?>
    <?php if ($errB === $bc && $err !== ''): ?>
      <div class="alert alert-error adm-ok" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
    <?php endif; ?>

    <?php if ($on): ?>
    <form class="adm-sec" method="post" action="adm-branches.php#b-<?= e($bc) ?>">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="act" value="save">
      <input type="hidden" name="b" value="<?= e($bc) ?>">
      <div class="adm-fields">
        <?php foreach ($info as $k => $f): ?>
          <div class="field<?= $k === 'address' ? ' br-wide' : '' ?>">
            <label for="<?= e($bc . '-' . $k) ?>"><?= e($f['label']) ?></label>
            <input class="input" type="text" id="<?= e($bc . '-' . $k) ?>" name="<?= e($k) ?>" value="<?= e($v($bc, $k, $b[$k])) ?>"
                   maxlength="<?= (int) $f['max'] ?>" placeholder="<?= e($f['ph']) ?>" autocomplete="off"<?= $f['req'] ? ' required' : '' ?>>
          </div>
        <?php endforeach; ?>
      </div>
      <h3 class="br-sub">ค่าตั้งของสาขา</h3>
      <div class="adm-fields">
        <?php foreach ($fields as $k => $f): $r = branch_setting_rules(); ?>
          <div class="field">
            <label for="<?= e($bc . '-' . $k) ?>"><?= e($f['label']) ?></label>
            <div class="adm-unit">
              <input class="input" type="number" id="<?= e($bc . '-' . $k) ?>" name="<?= e($k) ?>"
                     value="<?= (int) $v($bc, $k, branch_setting($bc, $k)) ?>" min="<?= (int) $r[$k][0] ?>" max="<?= (int) $r[$k][1] ?>"
                     step="<?= $k === 'default_float' ? 100 : 1 ?>" inputmode="numeric" required>
              <span><?= e($f['unit']) ?></span>
            </div>
            <small class="adm-hint"><?= e($f['hint']) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
      <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-check"/></svg> บันทึก<?= e($b['name']) ?></button>
    </form>
    <?php endif; ?>

    <!-- ปิด / เปิดใช้งาน · ลบ -->
    <div class="adm-row br-danger">
      <?php if ($on): ?>
        <form class="adm-sec" method="post" action="adm-branches.php#b-<?= e($bc) ?>">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="act" value="close">
          <input type="hidden" name="b" value="<?= e($bc) ?>">
          <h3>ปิดใช้งานสาขา</h3>
          <p class="adm-hint">ซ่อนจากการขายและงานคลัง ประวัติ บิล และรายงานยังอยู่ครบ · ต้องย้ายพนักงานออกให้หมดก่อน<?= $staff ? ' (ตอนนี้มี ' . count($staff) . ' คน)' : '' ?></p>
          <div class="adm-inline">
            <input class="input" type="text" name="why" placeholder="เหตุผล เช่น ปิดสาขาถาวร / ย้ายทำเล" autocomplete="off">
            <button class="btn btn-ghost" type="submit"<?= $staff ? ' disabled' : '' ?>><svg class="ico"><use href="#i-ban"/></svg> ปิดใช้งาน</button>
          </div>
        </form>
      <?php else: ?>
        <form class="adm-sec" method="post" action="adm-branches.php#b-<?= e($bc) ?>">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="act" value="open">
          <input type="hidden" name="b" value="<?= e($bc) ?>">
          <h3>เปิดใช้งานอีกครั้ง</h3>
          <p class="adm-hint">สาขากลับมาใช้งานได้ตามเดิม ข้อมูลและค่าตั้งเดิมยังอยู่</p>
          <button class="btn btn-ghost" type="submit"><svg class="ico"><use href="#i-check"/></svg> เปิดใช้งาน</button>
        </form>
      <?php endif; ?>

      <form class="adm-sec" method="post" action="adm-branches.php#b-<?= e($bc) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="act" value="delete">
        <input type="hidden" name="b" value="<?= e($bc) ?>">
        <h3>ลบสาขา</h3>
        <?php if ($reason !== ''): ?>
          <p class="adm-hint">ลบไม่ได้ เพราะ<?= e($reason) ?> — ใช้ปิดใช้งานแทน เพื่อไม่ให้ยอดเก่าหาย</p>
        <?php else: ?>
          <p class="adm-hint">สาขานี้ยังไม่มีข้อมูล ลบได้ · ลบแล้วกู้คืนไม่ได้</p>
          <label class="br-sure"><input type="checkbox" name="sure" value="1" required> ยืนยันลบ <?= e($b['name']) ?> (<?= e($bc) ?>)</label>
          <button class="btn btn-ghost br-del" type="submit"><svg class="ico"><use href="#i-trash"/></svg> ลบสาขา</button>
        <?php endif; ?>
      </form>
    </div>
  </details>
<?php endforeach; ?>

<script>
/* popup ฟอร์มเพิ่มสาขา · บันทึกไม่ผ่าน → เปิด popup ค้างไว้พร้อมข้อความผิดพลาดและค่าที่กรอก */
(function () {
  var dlg = document.getElementById('br-add-modal');
  if (!dlg || !dlg.showModal) { return; }
  document.addEventListener('click', function (ev) {
    if (ev.target.closest('[data-fm-open="br-add-modal"]')) {
      dlg.showModal();
      var f = document.getElementById('new-b');
      if (f) { f.focus(); }
      return;
    }
    if ((ev.target.closest('[data-fm-close]') && dlg.contains(ev.target)) || ev.target === dlg) { dlg.close(); }
  });
  if (dlg.hasAttribute('data-fm-auto')) { dlg.showModal(); }
})();
(function () {
  var c = document.getElementById('new-b'), eg = document.getElementById('new-eg');
  if (!c) { return; }
  c.addEventListener('input', function () {
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    eg.textContent = (this.value || '{รหัส}') + 'V';
  });
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
