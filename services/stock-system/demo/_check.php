<?php
/* ==========================================================
   AOSTOCK DEMO — หน้าตรวจสอบระบบ
   เปิดหน้านี้เมื่อเจอ HTTP 500 เพื่อดูว่าติดตรงไหน
   ใช้เสร็จแล้วลบไฟล์นี้ออกได้เลย
   ========================================================== */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');

$checks = [];

/* 1. เวอร์ชัน PHP */
$checks[] = [
    'name' => 'เวอร์ชัน PHP',
    'ok'   => PHP_VERSION_ID >= 50400,
    'msg'  => PHP_VERSION . ' (ต้องการ 5.4 ขึ้นไป)',
];

/* 2. session */
$checks[] = [
    'name' => 'ส่วนขยาย session',
    'ok'   => function_exists('session_start'),
    'msg'  => function_exists('session_start') ? 'พร้อมใช้งาน' : 'ไม่พบ ต้องเปิดส่วนขยาย session',
];

/* 3. random_bytes (ใช้ทำ CSRF token) */
$rndOk = function_exists('random_bytes') || function_exists('openssl_random_pseudo_bytes');
$checks[] = [
    'name' => 'ตัวสุ่มสำหรับ CSRF',
    'ok'   => true,
    'msg'  => $rndOk ? 'ใช้ ' . (function_exists('random_bytes') ? 'random_bytes()' : 'openssl_random_pseudo_bytes()') : 'ใช้วิธีสำรอง (uniqid + sha256)',
];

/* 4. mbstring — ไม่บังคับ แต่ช่วยเรื่องภาษาไทย */
$checks[] = [
    'name' => 'mbstring (ไม่บังคับ)',
    'ok'   => extension_loaded('mbstring'),
    'msg'  => extension_loaded('mbstring') ? 'พร้อมใช้งาน' : 'ไม่พบ — ระบบยังทำงานได้ แต่ควรเปิดไว้',
];

/* 5. ไฟล์ที่ต้องมี */
$need = [
    'inc/config.php', 'include/function.php',
    'inc/header.php', 'inc/footer.php',
    'assets/app.css', 'login.php', 'dashboard.php', 'logout.php',
];
foreach ($need as $f) {
    $path = __DIR__ . '/' . $f;
    $checks[] = [
        'name' => 'ไฟล์ ' . $f,
        'ok'   => is_readable($path),
        'msg'  => is_readable($path) ? number_format(filesize($path)) . ' bytes' : 'ไม่พบไฟล์ หรืออ่านไม่ได้',
    ];
}

/* 6. ลองโหลดไฟล์จริง */
$loadError = null;
try {
    require_once __DIR__ . '/include/function.php';
    $sum = stock_summary('ALL');
    $loadMsg = 'โหลดสำเร็จ · สินค้า ' . $sum['items'] . ' รายการ · มูลค่า ' . number_format($sum['value']) . ' บาท';
    $loadOk  = true;
} catch (Exception $ex) {
    $loadOk  = false;
    $loadMsg = get_class($ex) . ': ' . $ex->getMessage() . ' (' . basename($ex->getFile()) . ' บรรทัด ' . $ex->getLine() . ')';
}
$checks[] = ['name' => 'โหลด config + function', 'ok' => $loadOk, 'msg' => $loadMsg];

$allOk = true;
foreach ($checks as $c) {
    if (!$c['ok'] && strpos($c['name'], 'ไม่บังคับ') === false) {
        $allOk = false;
    }
}
?><!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ตรวจสอบระบบ | AOSTOCK DEMO</title>
<meta name="robots" content="noindex, nofollow">
<style>
  body{margin:0;background:#F6F9F8;color:#10201C;font-family:system-ui,-apple-system,"Segoe UI",Tahoma,sans-serif;line-height:1.7;padding:40px 20px}
  .wrap{max-width:760px;margin:0 auto}
  h1{font-size:1.4rem;margin:0}
  p.lead{margin:8px 0 0;color:#48605A;font-size:.95rem}
  .sum{margin-top:22px;border-radius:12px;padding:16px 20px;font-weight:600}
  .sum.ok{background:#E9F6F0;color:#0C7A55;border:1px solid #C9E6DC}
  .sum.bad{background:#FDECEA;color:#8B1C15;border:1px solid #F5CFCB}
  table{width:100%;margin-top:20px;border-collapse:separate;border-spacing:0;background:#fff;border:1px solid #E2E9E6;border-radius:12px;overflow:hidden;font-size:.92rem}
  th,td{padding:11px 16px;text-align:left;border-bottom:1px solid #EDF2F0;vertical-align:top}
  tr:last-child td{border-bottom:0}
  th{background:#F6F9F8;font-size:.8rem;color:#6C817B}
  .st{font-weight:700;white-space:nowrap}
  .st.ok{color:#0C7A55}
  .st.bad{color:#B42318}
  .note{margin-top:22px;font-size:.88rem;color:#6C817B}
  code{background:#EDF2F0;border-radius:5px;padding:1px 6px;font-size:.88em}
  a{color:#0E7A5F}
</style>
</head>
<body>
<div class="wrap">
  <h1>ตรวจสอบระบบ AOSTOCK DEMO</h1>
  <p class="lead">หน้านี้ใช้หาสาเหตุเมื่อเจอ HTTP 500 · ใช้เสร็จแล้วลบไฟล์ <code>_check.php</code> ออกได้เลย</p>

  <div class="sum <?= $allOk ? 'ok' : 'bad' ?>">
    <?= $allOk ? 'ผ่านทุกข้อ — ระบบพร้อมใช้งาน ลองเปิด login.php ได้เลย' : 'พบปัญหา — ดูรายการที่ขึ้น “ไม่ผ่าน” ด้านล่าง' ?>
  </div>

  <table>
    <thead><tr><th>รายการ</th><th>ผล</th><th>รายละเอียด</th></tr></thead>
    <tbody>
      <?php foreach ($checks as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td class="st <?= $c['ok'] ? 'ok' : 'bad' ?>"><?= $c['ok'] ? 'ผ่าน' : 'ไม่ผ่าน' ?></td>
          <td><?= htmlspecialchars($c['msg'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <p class="note">
    ถ้าทุกข้อผ่านแต่ <code>login.php</code> ยัง 500 อยู่ ให้ดู error log ของเว็บเซิร์ฟเวอร์
    (DirectAdmin: <code>Errors</code> ในหน้าจัดการโดเมน) แล้วส่งบรรทัดล่าสุดมาได้เลย<br>
    เส้นทางไฟล์จริง: <code><?= htmlspecialchars(__DIR__, ENT_QUOTES, 'UTF-8') ?></code><br>
    <a href="login.php">→ ไปหน้าเข้าสู่ระบบ</a>
  </p>
</div>
</body>
</html>
