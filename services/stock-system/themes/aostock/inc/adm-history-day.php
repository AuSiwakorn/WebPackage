<?php
/**
 * FILE: themes/aostock/inc/adm-history-day.php
 * ROLE: [ผู้ดูแล] รายการของสาขาหนึ่งในวันหนึ่ง (ส่วนหนึ่งของ adm-history.php)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/history-past.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_log (วันนี้ + ลำดับเหตุการณ์วันก่อน) · ao_stock_receive / issue / count · ao_stock_sale (วันก่อน) — ผ่าน api.php
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ประวัติวันนี้ + เอกสารคลังวันก่อนจากตาราง (ช่วงที่ 6)
 *   - [x] ช่วงที่ 8: วันก่อนมีลำดับเหตุการณ์ทั้งวัน (inc/history-past.php)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   [ผู้ดูแล] รายการของสาขาหนึ่งในวันหนึ่ง (ส่วนหนึ่งของ adm-history.php)
   ?view=day&b=รหัสสาขา&d=ปปปปดดวว
   - วันนี้  : ประวัติของสาขา ดูอย่างเดียว (แก้ / ยกเลิกเอกสารของวันนี้เป็นงานของพนักงาน)
   - วันก่อน: ใช้ inc/history-past.php ร่วมกับฝั่งพนักงาน · ผู้ดูแลยกเลิกเอกสารคลังย้อนหลังได้
   ตัวแปรจาก adm-history.php: $user
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$brAll = branches_all();
$code  = (isset($_GET['b']) && is_string($_GET['b']) && isset($brAll[$_GET['b']])) ? $_GET['b'] : '';
$ts    = isset($_GET['d']) ? strtotime(preg_replace('/\D/', '', $_GET['d'])) : false;
if ($code === '' || $ts === false || $ts > time()) {
    header('Location: ' . url('adm-history.php'));
    exit;
}
$ts      = strtotime(date('Y-m-d', $ts));
$pastDay = date('Ymd', $ts);
$isToday = ($pastDay === date('Ymd'));
$pastTs  = $isToday ? null : $ts;
$err     = '';

$HIST_BASE = 'adm-history.php?view=day&b=' . rawurlencode($code);   // ฟอร์มใน history-past.php ส่งกลับมาที่นี่

/* ---------- ยกเลิกเอกสารคลังย้อนหลัง (ผู้ดูแลยกเลิกได้อย่างเดียว ไม่มีแก้ไข) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isToday) {
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
        $err = 'เซสชันหมดอายุ กรุณาเข้าสู่ระบบใหม่';
    } elseif (!isset($_POST['act']) || $_POST['act'] !== 'past_void') {
        $err = 'ผู้ดูแลยกเลิกเอกสารได้อย่างเดียว — การแก้ไขให้พนักงานทำใบใหม่';
    } else {
        $r = past_doc_void($code, isset($_POST['no']) ? trim($_POST['no']) : '', $user,
                           isset($_POST['reason']) ? $_POST['reason'] : '', false);
        if (isset($r['error'])) {
            $err = $r['error'];
        } else {
            $_SESSION['flash'] = 'ยกเลิก ' . $r['doc']['no'] . ' แล้ว — สต๊อกถูกปรับวันนี้และลงประวัติของ' . branch_name($code);
            header('Location: ' . url(hist_url('d=' . $pastDay)));
            exit;
        }
    }
}

$back = 'adm-history.php?mode=day&d=' . date('Y-m-d', $ts) . '&b=' . rawurlencode($code);
$rows = $isToday ? log_today($code) : array();

$branch         = 'ALL';
$NO_BRANCH_PICK = true;
$PAGE_TITLE     = 'ประวัติ' . $brAll[$code]['name'];
$PAGE_SUB       = thai_date_full($ts) . ($isToday ? ' · วันนี้' : ' · ย้อนหลัง ' . (int) round((strtotime(date('Y-m-d')) - $ts) / 86400) . ' วัน');
$NAV_ACTIVE     = 'adm-history.php';
require dirname(__FILE__) . '/header.php';
?>

<p class="hist-back">
  <a class="btn btn-ghost btn-sm" href="<?= e($back) ?>">‹ กลับไปประวัติรวมทุกสาขา</a>
  <span>วันอื่นของสาขานี้:</span>
  <a class="btn btn-ghost btn-sm" href="<?= e(hist_url('d=' . date('Ymd', strtotime('-1 day', $ts)))) ?>">‹ วันก่อน</a>
  <?php if (!$isToday): ?>
    <a class="btn btn-ghost btn-sm" href="<?= e(hist_url('d=' . date('Ymd', strtotime('+1 day', $ts)))) ?>">วันถัดไป ›</a>
  <?php endif; ?>
</p>

<?php if ($err !== ''): ?>
  <div class="alert alert-error" role="alert"><svg class="ico"><use href="#i-alert"/></svg><span><?= e($err) ?></span></div>
<?php endif; ?>

<?php if (!$isToday): ?>
  <?php require dirname(__FILE__) . '/history-past.php'; ?>
<?php else: ?>
  <section class="card">
    <div class="card-head">
      <div>
        <h2>รายการวันนี้ของ<?= e($brAll[$code]['name']) ?></h2>
        <span class="sub">เรียงจากใหม่ไปเก่า · ดูอย่างเดียว — แก้ / ยกเลิกเอกสารของวันนี้เป็นงานของพนักงานที่มีสิทธิ์</span>
      </div>
    </div>
    <?php if (!$rows): ?>
      <p class="empty"><svg class="ico"><use href="#i-history"/></svg>วันนี้ยังไม่มีรายการ</p>
    <?php else: ?>
      <ol class="tl">
        <?php foreach ($rows as $r): $m = log_type_of($r['type']); ?>
          <li class="tl-i tone-<?= e($m['tone']) ?>">
            <span class="tl-ic"><svg class="ico"><use href="#<?= e($m['icon']) ?>"/></svg></span>
            <div class="tl-b">
              <div class="tl-h">
                <b><?= e($r['title']) ?></b>
                <span class="badge b-<?= e($m['tone']) ?>"><?= e($m['label']) ?></span>
                <?php if ($r['amount'] !== null && in_array($r['type'], array('receive', 'rvoid'), true)): ?>
                  <span class="tl-amt num"><?= $r['type'] === 'receive' ? '+' : '−' ?><?= number_format($r['amount']) ?> ชิ้น</span>
                <?php elseif ($r['amount'] !== null): ?>
                  <span class="tl-amt num"><?= money2($r['amount']) ?> ฿</span>
                <?php endif; ?>
              </div>
              <div class="tl-m"><?= e($r['time']) ?> น. · <?= e($r['by']) ?></div>
              <?php if ($r['detail']): ?>
                <dl class="tl-d">
                  <?php foreach ($r['detail'] as $k => $v): ?>
                    <div><dt><?= e($k) ?></dt><dd class="num"><?= e($v) ?></dd></div>
                  <?php endforeach; ?>
                </dl>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php require dirname(__FILE__) . '/footer.php'; ?>
