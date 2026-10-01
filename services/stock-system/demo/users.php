<?php
/* ==========================================================
   AOSTOCK DEMO — ผู้ใช้และสิทธิ์ (เฉพาะผู้ดูแล)
   ----------------------------------------------------------
   - ติ๊กสิทธิ์เสริมให้พนักงานรายคน (แก้งานคนอื่น / แก้ย้อนหลัง / รับคืนสินค้า / รายงานทั้งสาขา)
   - รีเซ็ต PIN 4 หลัก (ห้ามซ้ำกับคนอื่นในสาขาเดียวกัน)
   - ย้ายสาขา — มีผลตั้งแต่วันนี้ ยอดเก่ายังผูกกับสาขาเดิมเสมอ
   ทุกการแก้ไขบันทึกลงประวัติการทำรายการของสาขานั้น

   เดโมเก็บสิ่งที่แก้ไว้ใน $_SESSION['cfg']['user'] (ดู demo_users_all())
   ระบบจริง: UPDATE ao_stock_staff / INSERT ao_stock_staff_branch
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
$sel   = isset($_GET['u']) ? trim($_GET['u']) : '';

/** พนักงาน (ไม่รวมผู้ดูแล) */
function staff_only()
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

/* ---------- บันทึก ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sel   = isset($_POST['u']) ? trim($_POST['u']) : '';
    $act   = isset($_POST['act']) ? $_POST['act'] : '';
    $staff = staff_only();

    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } elseif (!isset($staff[$sel])) {
        $err = 'ไม่พบพนักงานคนนี้';
    } else {
        $u    = $staff[$sel];
        $done = '';

        if ($act === 'perms') {
            $want = array();
            $in   = (isset($_POST['perms']) && is_array($_POST['perms'])) ? $_POST['perms'] : array();
            foreach (array_keys($plist) as $k) {
                if (in_array($k, $in, true)) {
                    $want[] = $k;
                }
            }
            $before = isset($u['perms']) ? $u['perms'] : array();
            $_SESSION['cfg']['user'][$sel]['perms'] = $want;
            log_add($u['branch'], 'setting', $user, 'แก้สิทธิ์เสริมของ ' . $u['name'], array(
                'เดิม'      => perm_names($before),
                'ใหม่'      => perm_names($want),
                'แก้โดย'    => $user['name'] . ' (ผู้ดูแล)',
            ));
            $done = 'perms';

        } elseif ($act === 'pin') {
            $pin = isset($_POST['pin']) ? preg_replace('/\D/', '', $_POST['pin']) : '';
            $dup = '';
            foreach (branch_staff($u['branch']) as $k => $o) {
                if ($k !== $sel && $o['pin'] === $pin) {
                    $dup = $o['name'];
                }
            }
            if (strlen($pin) !== 4) {
                $err = 'PIN ต้องเป็นตัวเลข 4 หลัก';
            } elseif ($dup !== '') {
                $err = 'PIN นี้ซ้ำกับ ' . $dup . ' ในสาขาเดียวกัน กรุณาใช้เลขอื่น';
            } else {
                $_SESSION['cfg']['user'][$sel]['pin'] = $pin;
                log_add($u['branch'], 'setting', $user, 'รีเซ็ต PIN ของ ' . $u['name'], array(
                    'แก้โดย' => $user['name'] . ' (ผู้ดูแล)',
                    'หมายเหตุ' => 'ไม่แสดงเลข PIN ในประวัติ',
                ));
                $done = 'pin';
            }

        } elseif ($act === 'move') {
            $to = isset($_POST['branch']) ? $_POST['branch'] : '';
            $br = demo_branches();
            if (!isset($br[$to])) {
                $err = 'กรุณาเลือกสาขาปลายทาง';
            } elseif ($to === $u['branch']) {
                $err = $u['name'] . ' อยู่' . branch_name($to) . 'อยู่แล้ว';
            } else {
                $pinDup = false;
                foreach (branch_staff($to) as $o) {
                    if ($o['pin'] === $u['pin']) {
                        $pinDup = true;
                    }
                }
                if ($pinDup) {
                    $err = 'PIN ของ ' . $u['name'] . ' ซ้ำกับพนักงานใน' . branch_name($to) . ' — รีเซ็ต PIN ก่อนแล้วค่อยย้าย';
                } else {
                    $_SESSION['cfg']['user'][$sel]['moves'][] = array('branch' => $to, 'from' => date('Y-m-d'));
                    $detail = array(
                        'จาก'      => branch_name($u['branch']),
                        'ไป'       => branch_name($to),
                        'มีผล'     => 'ตั้งแต่วันนี้ ' . thai_date_full(time()),
                        'ยอดเก่า'  => 'ยังผูกกับ' . branch_name($u['branch']) . 'เหมือนเดิม',
                        'แก้โดย'   => $user['name'] . ' (ผู้ดูแล)',
                    );
                    log_add($u['branch'], 'setting', $user, 'ย้าย ' . $u['name'] . ' ไป' . branch_name($to), $detail);
                    log_add($to, 'setting', $user, 'รับ ' . $u['name'] . ' ย้ายมาจาก' . branch_name($u['branch']), $detail);
                    $done = 'move';
                }
            }
        }

        if ($err === '' && $done !== '') {
            header('Location: ' . url('users.php?u=' . rawurlencode($sel) . '&ok=' . $done));
            exit;
        }
    }
}

$staff  = staff_only();
$picked = isset($staff[$sel]) ? $staff[$sel] : null;
$okMsg  = array(
    'perms' => 'บันทึกสิทธิ์เสริมแล้ว มีผลทันที',
    'pin'   => 'รีเซ็ต PIN แล้ว — แจ้ง PIN ใหม่ให้พนักงานโดยตรง',
    'move'  => 'ย้ายสาขาแล้ว มีผลตั้งแต่วันนี้ ยอดเก่ายังอยู่ที่สาขาเดิม',
);
$ok = (isset($_GET['ok']) && isset($okMsg[$_GET['ok']])) ? $okMsg[$_GET['ok']] : '';

$branch     = work_branch($user);
$PAGE_TITLE = 'ผู้ใช้และสิทธิ์';
$PAGE_SUB   = 'พนักงานทุกสาขา · ' . count($staff) . ' คน';
$NAV_ACTIVE = 'users.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($err !== ''): ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
<?php endif; ?>
<?php if ($ok !== ''): ?>
  <div class="alert alert-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span><?= e($ok) ?></span></div>
<?php endif; ?>

<?php if ($picked !== null): ?>
  <?php $mine = isset($picked['perms']) ? $picked['perms'] : array(); ?>
  <!-- ==================== แก้ไขพนักงานที่เลือก ==================== -->
  <section class="card adm-edit">
    <div class="card-head">
      <div class="adm-who">
        <span class="av"><?= e(user_initial($picked)) ?></span>
        <div>
          <h2><?= e($picked['name']) ?></h2>
          <span class="sub"><?= e(role_name($picked['role'])) ?> · <?= e(branch_name($picked['branch'])) ?>
            <?php if (!empty($picked['since'])): ?> · ประจำสาขานี้ตั้งแต่ <?= e(thai_date_full(strtotime($picked['since']))) ?><?php endif; ?></span>
        </div>
      </div>
      <a class="btn btn-ghost btn-sm" href="users.php"><svg class="ico"><use href="#i-x"/></svg> ปิด</a>
    </div>

    <!-- สิทธิ์เสริม -->
    <form class="adm-sec" method="post" action="users.php">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="act" value="perms">
      <input type="hidden" name="u" value="<?= e($sel) ?>">
      <h3>สิทธิ์เสริม</h3>
      <p class="adm-hint">งานพื้นฐานทำได้ทุกคนอยู่แล้ว: ขาย เปิด–ปิดร้าน รับเข้า เบิก ตรวจนับ และแก้/ยกเลิกเอกสารของตัวเองที่ทำวันนี้</p>
      <div class="perm-grid">
        <?php foreach ($plist as $k => $p): ?>
          <label class="perm-o">
            <input type="checkbox" name="perms[]" value="<?= e($k) ?>" <?= in_array($k, $mine, true) ? 'checked' : '' ?>>
            <span><svg class="ico"><use href="#i-check"/></svg><b><?= e($p['label']) ?></b>
              <?php if ($k === 'backdate' || $k === 'refund'): ?><small>ย้อนหลังได้ <?= backdate_days($picked['branch']) ?> วัน (ตั้งที่หน้าตั้งค่าสาขา)</small><?php endif; ?>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
      <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-check"/></svg> บันทึกสิทธิ์</button>
    </form>

    <div class="adm-row">
      <!-- รีเซ็ต PIN -->
      <form class="adm-sec" method="post" action="users.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="act" value="pin">
        <input type="hidden" name="u" value="<?= e($sel) ?>">
        <h3>รีเซ็ต PIN</h3>
        <p class="adm-hint">ใช้เมื่อพนักงานลืม PIN · ห้ามซ้ำกับคนอื่นในสาขาเดียวกัน</p>
        <div class="adm-inline">
          <label class="sr-only" for="pin">PIN ใหม่</label>
          <input class="input adm-pin" type="text" id="pin" name="pin" inputmode="numeric" maxlength="4"
                 pattern="[0-9]{4}" placeholder="4 หลัก" autocomplete="off" required>
          <button class="btn btn-ghost" type="submit">ตั้ง PIN ใหม่</button>
        </div>
      </form>

      <!-- ย้ายสาขา -->
      <form class="adm-sec" method="post" action="users.php">
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
  </section>
<?php endif; ?>

<!-- ==================== รายชื่อพนักงานแยกสาขา ==================== -->
<?php foreach (demo_branches() as $bc => $b): $list = branch_staff($bc); ?>
  <section class="card">
    <div class="card-head">
      <div><h2><?= e($b['name']) ?></h2><span class="sub"><?= count($list) ?> คน</span></div>
      <a class="btn btn-ghost btn-sm" href="branches.php#b-<?= e($bc) ?>"><svg class="ico"><use href="#i-settings"/></svg> ตั้งค่าสาขา</a>
    </div>
    <?php if (!$list): ?>
      <p class="empty">ยังไม่มีพนักงานในสาขานี้</p>
    <?php else: ?>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr><th>พนักงาน</th><th>สิทธิ์เสริม</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($list as $k => $u): $pp = isset($u['perms']) ? $u['perms'] : array(); ?>
              <tr class="<?= $k === $sel ? 'on' : '' ?>">
                <td data-label="พนักงาน"><b><?= e($u['name']) ?></b><small><?= e($k) ?></small></td>
                <td data-label="สิทธิ์เสริม">
                  <?php if (!$pp): ?><span class="adm-none">งานพื้นฐาน</span><?php endif; ?>
                  <?php foreach ($pp as $pk): if (!isset($plist[$pk])) { continue; } ?>
                    <span class="bdg bdg-ok"><?= e($plist[$pk]['short']) ?></span>
                  <?php endforeach; ?>
                </td>
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
