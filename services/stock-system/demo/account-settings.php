<?php
/* ==========================================================
   AOSTOCK DEMO — ตั้งค่าเลขที่บิล (ฝ่ายบัญชี / ผู้ดูแล)
   ----------------------------------------------------------
   แต่ละสาขามีรหัสนำหน้า 2 ชุด: บิล VAT และบิลไม่ VAT
   รูปแบบเลขที่ {รหัส}{ปี}-{เดือน}-{เลขรัน 4 หลัก} เช่น BP2026-01-0001 · เลขรันนับใหม่ทุกเดือน
   พร้อมเลขประจำตัวผู้เสียภาษี และเลขที่สาขา (00000 = สำนักงานใหญ่) ที่พิมพ์บนบิล VAT
   การแก้ทุกครั้งบันทึกลงประวัติของสาขานั้น

   เดโม: เปลี่ยนรหัสแล้วเลขที่ของบิลเดิมที่เป็นข้อมูลสมมติจะเปลี่ยนตาม
   ระบบจริง: เลขที่ถูกเก็บตอนออกบิล เปลี่ยนรหัสมีผลกับบิลใบถัดไปเท่านั้น
   ========================================================== */

require_once dirname(__FILE__) . '/inc/auth.php';
require_once dirname(__FILE__) . '/inc/acct.php';

$user = require_login();
if (!in_array($user['role'], array('account', 'admin'), true)) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$br  = demo_branches_all();               // รวมสาขาที่ปิดใช้งาน (เลขที่บิลเดิมยังอ้างอิงอยู่)
$err = '';
$okB = isset($_GET['ok']) ? $_GET['ok'] : '';
$old = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bc = isset($_POST['b']) ? $_POST['b'] : '';
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาลองใหม่อีกครั้ง';
    } elseif (!isset($br[$bc])) {
        $err = 'ไม่พบสาขานี้';
    } else {
        $in = array(
            'prefix_vat'   => strtoupper(trim(isset($_POST['prefix_vat']) ? $_POST['prefix_vat'] : '')),
            'prefix_novat' => strtoupper(trim(isset($_POST['prefix_novat']) ? $_POST['prefix_novat'] : '')),
            'tax_id'       => preg_replace('/\D/', '', isset($_POST['tax_id']) ? $_POST['tax_id'] : ''),
            'tax_branch'   => preg_replace('/\D/', '', isset($_POST['tax_branch']) ? $_POST['tax_branch'] : ''),
        );
        $old[$bc] = $in;

        /* รหัสทุกชุดของทุกสาขา (แทนของสาขานี้ด้วยค่าที่กรอกมา) ไว้เช็กซ้ำ */
        $all = array();
        foreach (array_keys($br) as $c) {
            $all[$c] = array('prefix_vat' => acct_setting($c, 'prefix_vat'), 'prefix_novat' => acct_setting($c, 'prefix_novat'));
        }
        $all[$bc] = array('prefix_vat' => $in['prefix_vat'], 'prefix_novat' => $in['prefix_novat']);

        if (($m = acct_prefix_error($bc, 'prefix_vat', $in['prefix_vat'], $all)) !== '') {
            $err = 'รหัสบิล VAT: ' . $m;
        } elseif (($m = acct_prefix_error($bc, 'prefix_novat', $in['prefix_novat'], $all)) !== '') {
            $err = 'รหัสบิลไม่ VAT: ' . $m;
        } elseif (strlen($in['tax_id']) !== 13) {
            $err = 'เลขประจำตัวผู้เสียภาษีต้องมี 13 หลัก';
        } elseif (strlen($in['tax_branch']) !== 5) {
            $err = 'เลขที่สาขาต้องมี 5 หลัก (สำนักงานใหญ่ = 00000)';
        } else {
            $labels  = array('prefix_vat' => 'รหัสบิล VAT', 'prefix_novat' => 'รหัสบิลไม่ VAT',
                             'tax_id' => 'เลขผู้เสียภาษี', 'tax_branch' => 'เลขที่สาขา');
            $changes = array();
            foreach ($in as $k => $v) {
                if (acct_setting($bc, $k) !== $v) {
                    $changes[$labels[$k]] = acct_setting($bc, $k) . ' → ' . $v;
                }
                acct_setting_set($bc, $k, $v);
            }
            if ($changes) {
                $changes['แก้โดย'] = $user['name'] . ' (' . role_name($user['role']) . ')';
                log_add($bc, 'setting', $user, 'แก้เลขที่บิลของ' . branch_name($bc), $changes);
            }
            header('Location: ' . url('account-settings.php?ok=' . rawurlencode($bc) . '#b-' . $bc));
            exit;
        }
    }
}

$branch     = 'ALL';
$PAGE_TITLE = 'ตั้งค่าเลขที่บิล';
$PAGE_SUB   = 'รหัสนำหน้าแยกบิล VAT / ไม่ VAT ของแต่ละสาขา · เลขรัน 4 หลัก นับใหม่ทุกเดือน';
$NAV_ACTIVE = 'account-settings.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($err !== ''): ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
<?php endif; ?>

<?php foreach ($br as $bc => $b):
    $v = isset($old[$bc]) ? $old[$bc] : array(
        'prefix_vat' => acct_setting($bc, 'prefix_vat'), 'prefix_novat' => acct_setting($bc, 'prefix_novat'),
        'tax_id' => acct_setting($bc, 'tax_id'), 'tax_branch' => acct_setting($bc, 'tax_branch')); ?>
  <section class="card" id="b-<?= e($bc) ?>">
    <div class="card-head">
      <div>
        <h2><?= e($b['name']) ?></h2>
        <span class="sub">บิลใบถัดไป: VAT <b><?= e(bill_next_no_series($bc, true)) ?></b> · ไม่ VAT <b><?= e(bill_next_no_series($bc, false)) ?></b></span>
      </div>
    </div>

    <?php if ($okB === $bc): ?>
      <div class="alert alert-ok adm-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span>บันทึกแล้ว — ใช้กับบิลใบถัดไปทันที</span></div>
    <?php endif; ?>

    <form class="adm-sec" method="post" action="account-settings.php#b-<?= e($bc) ?>">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="b" value="<?= e($bc) ?>">
      <div class="adm-fields">
        <div class="field">
          <label for="<?= e($bc) ?>-pv">รหัสนำหน้าบิล VAT</label>
          <div class="adm-unit">
            <input class="input acct-prefix" type="text" id="<?= e($bc) ?>-pv" name="prefix_vat" value="<?= e($v['prefix_vat']) ?>"
                   maxlength="6" pattern="[A-Za-z0-9]{1,6}" required autocomplete="off" data-demo="<?= e(date('Y-m')) ?>">
            <span class="acct-eg"><?= e($v['prefix_vat'] . date('Y-m') . '-0001') ?></span>
          </div>
          <small class="adm-hint">ภาษาอังกฤษพิมพ์ใหญ่หรือตัวเลข 1–6 ตัว · ห้ามซ้ำกับชุดอื่น</small>
        </div>
        <div class="field">
          <label for="<?= e($bc) ?>-pn">รหัสนำหน้าบิลไม่ VAT</label>
          <div class="adm-unit">
            <input class="input acct-prefix" type="text" id="<?= e($bc) ?>-pn" name="prefix_novat" value="<?= e($v['prefix_novat']) ?>"
                   maxlength="6" pattern="[A-Za-z0-9]{1,6}" required autocomplete="off" data-demo="<?= e(date('Y-m')) ?>">
            <span class="acct-eg"><?= e($v['prefix_novat'] . date('Y-m') . '-0001') ?></span>
          </div>
          <small class="adm-hint">เช่น BP → BP<?= e(date('Y-m')) ?>-0001</small>
        </div>
        <div class="field">
          <label for="<?= e($bc) ?>-tx">เลขประจำตัวผู้เสียภาษี</label>
          <input class="input" type="text" id="<?= e($bc) ?>-tx" name="tax_id" value="<?= e($v['tax_id']) ?>"
                 inputmode="numeric" maxlength="13" required autocomplete="off">
          <small class="adm-hint">13 หลัก · พิมพ์บนบิล VAT</small>
        </div>
        <div class="field">
          <label for="<?= e($bc) ?>-tb">เลขที่สาขา (ตามที่จดทะเบียน VAT)</label>
          <input class="input" type="text" id="<?= e($bc) ?>-tb" name="tax_branch" value="<?= e($v['tax_branch']) ?>"
                 inputmode="numeric" maxlength="5" required autocomplete="off">
          <small class="adm-hint">5 หลัก · สำนักงานใหญ่ = 00000</small>
        </div>
      </div>
      <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-check"/></svg> บันทึก<?= e($b['name']) ?></button>
    </form>
  </section>
<?php endforeach; ?>

<script>
/* ตัวอย่างเลขที่เปลี่ยนตามที่พิมพ์ */
(function () {
  var ins = document.querySelectorAll('.acct-prefix');
  for (var i = 0; i < ins.length; i++) {
    ins[i].addEventListener('input', function () {
      this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
      var eg = this.parentNode.querySelector('.acct-eg');
      if (eg) { eg.textContent = (this.value || '—') + this.getAttribute('data-demo') + '-0001'; }
    });
  }
})();
</script>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
