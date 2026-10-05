<?php
/**
 * FILE: themes/aostock/account-settings.php
 * ROLE: ตั้งค่าเลขที่บิล (ฝ่ายบัญชี / ผู้ดูแล)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_branch (ผ่าน api.php — acct_setting / acct_setting_set)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ค่าตั้งเลขที่บิล / หัวบิลเก็บใน ao_stock_branch (ช่วงที่ 5)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ตั้งค่าเลขที่บิล (ฝ่ายบัญชี / ผู้ดูแล)
   ----------------------------------------------------------
   แต่ละสาขาตั้งค่าได้ 3 ส่วน
     1) หัวบิล — ชื่อร้าน/บริษัท ที่อยู่ เบอร์โทร บรรทัดเสริม ข้อความท้ายบิล ขนาดกระดาษเริ่มต้น
        (ที่อยู่ / เบอร์ปล่อยว่าง = ใช้ของสาขาจากหน้า "จัดการสาขา")
     2) ชุดบิล VAT — รหัสนำหน้า หัวกระดาษ เลขผู้เสียภาษี เลขที่สาขา
     3) ชุดบิลไม่ VAT — รหัสนำหน้า หัวกระดาษ และเลือกได้ว่าจะพิมพ์เลขผู้เสียภาษีด้วยไหม
   กด "ดูตัวอย่างบิล" เปิดบิลตัวอย่างตามค่าที่บันทึกแล้ว (bill-print.php?sample=…)
   แต่ละสาขามีรหัสนำหน้า 2 ชุด: บิล VAT และบิลไม่ VAT
   รูปแบบเลขที่ {รหัส}{ปี}-{เดือน}-{เลขรัน 4 หลัก} เช่น BP2026-01-0001 · เลขรันนับใหม่ทุกเดือน
   พร้อมเลขประจำตัวผู้เสียภาษี และเลขที่สาขา (00000 = สำนักงานใหญ่) ที่พิมพ์บนบิล VAT
   การแก้ทุกครั้งบันทึกลงประวัติของสาขานั้น
   แสดงแบบแอคคอร์เดียน (เปิดทีละสาขา · สาขาแรกเปิดไว้)

   บิลที่ออกไปแล้วเก็บเลขที่เดิมไว้ในบิล (ao_stock_sale.doc_no) — เปลี่ยนรหัสมีผลกับบิลใหม่เท่านั้น
   ระบบจริง: เลขที่ถูกเก็บตอนออกบิล เปลี่ยนรหัสมีผลกับบิลใบถัดไปเท่านั้น
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();
if (!in_array($user['role'], array('account', 'admin'), true)) {
    header('Location: ' . url('dashboard.php'));
    exit;
}

$br  = branches_all();               // รวมสาขาที่ปิดใช้งาน (เลขที่บิลเดิมยังอ้างอิงอยู่)
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
        $txt = function ($k, $max) {
            $v = isset($_POST[$k]) ? (string) $_POST[$k] : '';
            $v = trim(str_replace("\r", '', $v));
            return function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : substr($v, 0, $max * 3);
        };
        $in = array(
            'prefix_vat'   => strtoupper(trim(isset($_POST['prefix_vat']) ? $_POST['prefix_vat'] : '')),
            'prefix_novat' => strtoupper(trim(isset($_POST['prefix_novat']) ? $_POST['prefix_novat'] : '')),
            'tax_id'       => preg_replace('/\D/', '', isset($_POST['tax_id']) ? $_POST['tax_id'] : ''),
            'tax_branch'   => preg_replace('/\D/', '', isset($_POST['tax_branch']) ? $_POST['tax_branch'] : ''),
            'company'      => $txt('company', 120),
            'bill_address' => preg_replace('/\s*\n\s*/', ' ', $txt('bill_address', 255)),
            'bill_phone'   => $txt('bill_phone', 40),
            'bill_extra'   => $txt('bill_extra', 120),
            'title_vat'    => $txt('title_vat', 60),
            'title_novat'  => $txt('title_novat', 60),
            'footer'       => $txt('footer', 300),
            'paper'        => (isset($_POST['paper']) && $_POST['paper'] === 'a4') ? 'a4' : '80',
            'novat_tax'    => !empty($_POST['novat_tax']) ? '1' : '0',
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
        } elseif ($in['company'] === '') {
            $err = 'กรุณากรอกชื่อร้าน / ชื่อบริษัทที่พิมพ์บนหัวบิล';
        } elseif ($in['title_vat'] === '' || $in['title_novat'] === '') {
            $err = 'กรุณากรอกหัวกระดาษของบิลทั้งสองชุด';
        } elseif ($in['title_vat'] === $in['title_novat']) {
            $err = 'หัวกระดาษบิล VAT กับบิลไม่ VAT ต้องไม่เหมือนกัน — ลูกค้าต้องแยกออกว่าเป็นใบกำกับภาษีหรือไม่';
        } else {
            $labels  = array('prefix_vat' => 'รหัสบิล VAT', 'prefix_novat' => 'รหัสบิลไม่ VAT',
                             'tax_id' => 'เลขผู้เสียภาษี', 'tax_branch' => 'เลขที่สาขา',
                             'company' => 'ชื่อบนหัวบิล', 'bill_address' => 'ที่อยู่บนบิล', 'bill_phone' => 'เบอร์บนบิล',
                             'bill_extra' => 'บรรทัดเสริม', 'title_vat' => 'หัวกระดาษบิล VAT', 'title_novat' => 'หัวกระดาษบิลไม่ VAT',
                             'footer' => 'ข้อความท้ายบิล', 'paper' => 'ขนาดกระดาษ', 'novat_tax' => 'เลขภาษีบนบิลไม่ VAT');
            $changes = array();
            foreach ($in as $k => $v) {
                if (acct_setting($bc, $k) !== $v) {
                    $changes[$labels[$k]] = (acct_setting($bc, $k) === '' ? '(ว่าง)' : str_replace("\n", ' / ', acct_setting($bc, $k)))
                                          . ' → ' . ($v === '' ? '(ว่าง)' : str_replace("\n", ' / ', $v));
                }
                acct_setting_set($bc, $k, $v);
            }
            if ($changes) {
                $changes['แก้โดย'] = $user['name'] . ' (' . role_name($user['role']) . ')';
                log_add($bc, 'setting', $user, 'แก้ตั้งค่าบิลของ' . branch_name($bc), $changes);
            }
            header('Location: ' . url('account-settings.php?ok=' . rawurlencode($bc) . '#b-' . $bc));
            exit;
        }
    }
}

$branch     = 'ALL';
$PAGE_TITLE = 'ตั้งค่าเลขที่บิล';
$PAGE_SUB   = 'หัวบิล (ชื่อร้าน ที่อยู่ เบอร์ เลขภาษี) และชุดเลขที่แยกบิล VAT / ไม่ VAT ของแต่ละสาขา · เลขรัน 4 หลัก นับใหม่ทุกเดือน';
$NAV_ACTIVE = 'account-settings.php';
require dirname(__FILE__) . '/inc/header.php';
?>

<?php if ($err !== ''): ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
<?php endif; ?>

<?php
/* แสดงแบบแอคคอร์เดียน — เปิดสาขาแรกไว้ · ถ้าเพิ่งบันทึก / บันทึกไม่ผ่าน ให้เปิดสาขานั้นแทน */
$openB = $okB !== '' && isset($br[$okB]) ? $okB : ($old ? key($old) : key($br));
?>
<?php foreach ($br as $bc => $b):
    if (isset($old[$bc])) {
        $v = $old[$bc];
    } else {
        $v = array();
        foreach (array('prefix_vat', 'prefix_novat', 'tax_id', 'tax_branch', 'company', 'bill_address', 'bill_phone',
                       'bill_extra', 'title_vat', 'title_novat', 'footer', 'paper', 'novat_tax') as $k) {
            $v[$k] = acct_setting($bc, $k);
        }
    } ?>
  <details class="card acc-item" id="b-<?= e($bc) ?>"<?= $bc === $openB ? ' open' : '' ?>>
    <summary class="card-head">
      <div>
        <h2><?= e($b['name']) ?></h2>
        <span class="sub">VAT <b><?= e(acct_setting($bc, 'prefix_vat')) ?></b> · ไม่ VAT <b><?= e(acct_setting($bc, 'prefix_novat')) ?></b>
          · บิลใบถัดไป <?= e(bill_next_no_series($bc, true)) ?> / <?= e(bill_next_no_series($bc, false)) ?></span>
      </div>
      <svg class="ico acc-chev" aria-hidden="true"><use href="#i-arrow"/></svg>
    </summary>

    <?php if ($okB === $bc): ?>
      <div class="alert alert-ok adm-ok" role="status"><svg class="ico"><use href="#i-check"/></svg><span>บันทึกแล้ว — ใช้กับบิลใบถัดไปทันที</span></div>
    <?php endif; ?>

    <form class="adm-sec" method="post" action="account-settings.php#b-<?= e($bc) ?>">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="b" value="<?= e($bc) ?>">
      <!-- ---------- 1) หัวบิล ---------- -->
      <h3 class="bs-h">หัวบิล <small>พิมพ์บนบิลทั้งสองชุด</small></h3>
      <div class="adm-fields">
        <div class="field">
          <label for="<?= e($bc) ?>-co">ชื่อร้าน / ชื่อบริษัท</label>
          <input class="input" type="text" id="<?= e($bc) ?>-co" name="company" value="<?= e($v['company']) ?>" maxlength="120" required>
          <small class="adm-hint">บรรทัดแรกของบิล · บิล VAT ควรตรงกับชื่อที่จดทะเบียน ภ.พ.20</small>
        </div>
        <div class="field">
          <label for="<?= e($bc) ?>-ph">เบอร์โทร</label>
          <input class="input" type="text" id="<?= e($bc) ?>-ph" name="bill_phone" value="<?= e($v['bill_phone']) ?>" maxlength="40"
                 placeholder="<?= e($b['phone']) ?>" inputmode="tel">
          <small class="adm-hint">ว่าง = ใช้เบอร์ของสาขา<?= $b['phone'] !== '' ? ' (' . e($b['phone']) . ')' : '' ?></small>
        </div>
        <div class="field bs-wide">
          <label for="<?= e($bc) ?>-ad">ที่อยู่</label>
          <textarea class="input" id="<?= e($bc) ?>-ad" name="bill_address" rows="2" maxlength="255"
                    placeholder="<?= e($b['address']) ?>"><?= e($v['bill_address']) ?></textarea>
          <small class="adm-hint">ว่าง = ใช้ที่อยู่ของสาขาจากหน้า “จัดการสาขา” · บิล VAT ต้องเป็นที่อยู่ตามที่จดทะเบียน</small>
        </div>
        <div class="field">
          <label for="<?= e($bc) ?>-ex">บรรทัดเสริม <small>(ไม่บังคับ)</small></label>
          <input class="input" type="text" id="<?= e($bc) ?>-ex" name="bill_extra" value="<?= e($v['bill_extra']) ?>" maxlength="120"
                 placeholder="เช่น LINE: @ร้านของคุณ · www.example.com">
        </div>
        <div class="field">
          <span class="lbl">ขนาดกระดาษเริ่มต้น</span>
          <div class="segs bs-paper">
            <label class="seg<?= $v['paper'] !== 'a4' ? ' on' : '' ?>"><input type="radio" name="paper" value="80" <?= $v['paper'] !== 'a4' ? 'checked' : '' ?>> ใบเสร็จ 80 มม.</label>
            <label class="seg<?= $v['paper'] === 'a4' ? ' on' : '' ?>"><input type="radio" name="paper" value="a4" <?= $v['paper'] === 'a4' ? 'checked' : '' ?>> A4</label>
          </div>
          <small class="adm-hint">ตอนเปิดดูบิลเปลี่ยนขนาดได้ทุกครั้ง</small>
        </div>
        <div class="field bs-wide">
          <label for="<?= e($bc) ?>-ft">ข้อความท้ายบิล</label>
          <textarea class="input" id="<?= e($bc) ?>-ft" name="footer" rows="2" maxlength="300"><?= e($v['footer']) ?></textarea>
          <small class="adm-hint">ขึ้นบรรทัดใหม่ได้ · เช่น ขอบคุณที่ใช้บริการ / เงื่อนไขการเปลี่ยนคืนสินค้า</small>
        </div>
      </div>

      <div class="bs-sets">
        <!-- ---------- 2) ชุดบิล VAT ---------- -->
        <fieldset class="bs-set bs-vat">
          <legend><span class="bdg bdg-move">VAT</span> ชุดบิล VAT</legend>
          <div class="field">
            <label for="<?= e($bc) ?>-pv">รหัสนำหน้าเลขที่</label>
            <div class="adm-unit">
              <input class="input acct-prefix" type="text" id="<?= e($bc) ?>-pv" name="prefix_vat" value="<?= e($v['prefix_vat']) ?>"
                     maxlength="6" pattern="[A-Za-z0-9]{1,6}" required autocomplete="off" data-demo="<?= e(date('Y-m')) ?>">
              <span class="acct-eg"><?= e($v['prefix_vat'] . date('Y-m') . '-0001') ?></span>
            </div>
            <small class="adm-hint">ภาษาอังกฤษพิมพ์ใหญ่หรือตัวเลข 1–6 ตัว · ห้ามซ้ำกับชุดอื่น · เลขรันแยกจากบิลไม่ VAT</small>
          </div>
          <div class="field">
            <label for="<?= e($bc) ?>-tv">หัวกระดาษ</label>
            <input class="input" type="text" id="<?= e($bc) ?>-tv" name="title_vat" value="<?= e($v['title_vat']) ?>" maxlength="60" required>
            <small class="adm-hint">ต้องมีคำว่า “ใบกำกับภาษี” ตามประมวลรัษฎากร</small>
          </div>
          <div class="field">
            <label for="<?= e($bc) ?>-tx">เลขประจำตัวผู้เสียภาษี</label>
            <input class="input" type="text" id="<?= e($bc) ?>-tx" name="tax_id" value="<?= e($v['tax_id']) ?>"
                   inputmode="numeric" maxlength="13" required autocomplete="off">
            <small class="adm-hint">13 หลัก</small>
          </div>
          <div class="field">
            <label for="<?= e($bc) ?>-tb">เลขที่สาขา (ตามที่จดทะเบียน VAT)</label>
            <input class="input" type="text" id="<?= e($bc) ?>-tb" name="tax_branch" value="<?= e($v['tax_branch']) ?>"
                   inputmode="numeric" maxlength="5" required autocomplete="off">
            <small class="adm-hint">5 หลัก · สำนักงานใหญ่ = 00000</small>
          </div>
          <button type="button" class="btn btn-ghost btn-sm" data-bill-print="bill-print.php?b=<?= e(rawurlencode($bc)) ?>&amp;sample=vat"
                  data-bill-no="ตัวอย่างบิล VAT · <?= e($b['name']) ?>"><svg class="ico"><use href="#i-print"/></svg> ดูตัวอย่างบิล VAT</button>
        </fieldset>

        <!-- ---------- 3) ชุดบิลไม่ VAT ---------- -->
        <fieldset class="bs-set">
          <legend><span class="bdg bdg-adj">ไม่ VAT</span> ชุดบิลไม่ VAT (บิลขายทั่วไป)</legend>
          <div class="field">
            <label for="<?= e($bc) ?>-pn">รหัสนำหน้าเลขที่</label>
            <div class="adm-unit">
              <input class="input acct-prefix" type="text" id="<?= e($bc) ?>-pn" name="prefix_novat" value="<?= e($v['prefix_novat']) ?>"
                     maxlength="6" pattern="[A-Za-z0-9]{1,6}" required autocomplete="off" data-demo="<?= e(date('Y-m')) ?>">
              <span class="acct-eg"><?= e($v['prefix_novat'] . date('Y-m') . '-0001') ?></span>
            </div>
            <small class="adm-hint">เช่น BP → BP<?= e(date('Y-m')) ?>-0001 · เลขรันแยกจากบิล VAT</small>
          </div>
          <div class="field">
            <label for="<?= e($bc) ?>-tn">หัวกระดาษ</label>
            <input class="input" type="text" id="<?= e($bc) ?>-tn" name="title_novat" value="<?= e($v['title_novat']) ?>" maxlength="60" required>
            <small class="adm-hint">ห้ามใช้คำว่า “ใบกำกับภาษี” — บิลนี้ไม่แยก VAT</small>
          </div>
          <label class="bs-check"><input type="checkbox" name="novat_tax" value="1" <?= $v['novat_tax'] === '1' ? 'checked' : '' ?>>
            พิมพ์เลขผู้เสียภาษีบนบิลไม่ VAT ด้วย</label>
          <button type="button" class="btn btn-ghost btn-sm" data-bill-print="bill-print.php?b=<?= e(rawurlencode($bc)) ?>&amp;sample=novat"
                  data-bill-no="ตัวอย่างบิลไม่ VAT · <?= e($b['name']) ?>"><svg class="ico"><use href="#i-print"/></svg> ดูตัวอย่างบิลไม่ VAT</button>
        </fieldset>
      </div>
      <p class="adm-hint bs-note">ตัวอย่างบิลแสดงตามค่าที่บันทึกแล้ว — แก้แล้วกดบันทึกก่อนจึงเห็นผล</p>
      <button class="btn btn-primary" type="submit"><svg class="ico"><use href="#i-check"/></svg> บันทึก<?= e($b['name']) ?></button>
    </form>
  </details>
<?php endforeach; ?>

<script>
/* ปุ่มเลือกขนาดกระดาษ */
document.addEventListener('change', function (ev) {
  if (ev.target.name !== 'paper') { return; }
  var ls = ev.target.closest('.bs-paper').querySelectorAll('.seg');
  for (var i = 0; i < ls.length; i++) { ls[i].classList.toggle('on', ls[i].querySelector('input').checked); }
});
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
