<?php
/**
 * FILE: themes/aostock/bill-print.php
 * ROLE: ดู / พิมพ์บิลขาย
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_sale, ao_stock_sale_item, ao_stock_branch (ผ่าน api.php)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 7: บิลอ่านจากตาราง (bill_find)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ดู / พิมพ์บิลขาย
   ----------------------------------------------------------
   ?b=สาขา &d=ปปปปดดวว &no=เลขที่บิล        → บิลจริงของวันนั้น
   ?b=สาขา &sample=vat | novat              → บิลตัวอย่างจากหน้าตั้งค่า (ไม่ใช่บิลจริง)
   &size=80 | a4   ขนาดกระดาษ (ไม่ส่ง = ค่าที่ตั้งไว้ของสาขา)
   &embed=1        แสดงใน popup (ซ่อนแถบปุ่มของหน้า — popup มีปุ่มพิมพ์ของตัวเอง)
   &print=1        เปิดแล้วสั่งพิมพ์ทันที (ใช้ตอนเปิดในแท็บใหม่)

   แยกแบบตามชุดเลขที่บิล
     - บิล VAT     → หัวกระดาษตามที่ตั้ง (ค่าเริ่มต้น "ใบเสร็จรับเงิน / ใบกำกับภาษีอย่างย่อ")
                     มีเลขผู้เสียภาษี + สาขา · แยกมูลค่าก่อน VAT / VAT 7% · "ราคารวมภาษีมูลค่าเพิ่มแล้ว"
     - บิลไม่ VAT  → บิลขายทั่วไป (ค่าเริ่มต้น "บิลเงินสด / ใบเสร็จรับเงิน") ไม่แยกภาษี
   ข้อมูลหัวบิล (ชื่อร้าน ที่อยู่ เบอร์ ข้อความท้ายบิล) ตั้งได้ที่หน้า "ตั้งค่าเลขที่บิล"
   บิลที่ยกเลิกแล้วพิมพ์ได้ แต่มีตราประทับ "ยกเลิก" ทับ

   เข้าได้: ฝ่ายบัญชี / ผู้ดูแล ทุกสาขา · พนักงานเฉพาะสาขาที่ตัวเองทำงาน
   ========================================================== */

require_once dirname(__FILE__) . '/include/function.php';

$user = require_login();
$br   = branches_all();
$code = (isset($_GET['b']) && is_string($_GET['b']) && isset($br[$_GET['b']])) ? $_GET['b'] : '';
if ($code === '' || ($user['role'] === 'staff' && $code !== work_branch($user))) {
    http_response_code(404);
    exit('ไม่พบบิล');
}

$bill = null;
if (isset($_GET['sample'])) {
    $bill = bill_sample($code, $_GET['sample'] === 'vat');
} else {
    $ts = isset($_GET['d']) ? strtotime(preg_replace('/\D/', '', (string) $_GET['d'])) : false;
    $no = isset($_GET['no']) ? (string) $_GET['no'] : '';
    if ($ts !== false && $ts <= time()) {
        $bill = bill_find($code, $ts, $no);
    }
}
if (!$bill) {
    http_response_code(404);
    exit('ไม่พบบิล');
}

$h     = bill_head($code);
$vat   = !empty($bill['vat']);
$void  = !empty($bill['void']);
$size  = isset($_GET['size']) && in_array($_GET['size'], array('80', 'a4'), true) ? $_GET['size'] : ($h['paper'] === 'a4' ? 'a4' : '80');
$embed = !empty($_GET['embed']);
$bts   = strtotime(isset($bill['date']) ? $bill['date'] : date('Ymd'));
$when  = date('d/m/', $bts) . (date('Y', $bts) + 543) . ' ' . $bill['time'] . ' น.';
$vs    = $vat ? vat_split($bill['total']) : array($bill['total'], 0);
$title = $vat ? $h['title_vat'] : $h['title_novat'];
$showTax = $vat || $h['novat_tax'] === '1';
$selfQs  = function ($o) {
    return 'bill-print.php?' . http_build_query(array_merge($_GET, $o));
};
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($bill['no']) ?> · <?= e($title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  *{box-sizing:border-box}
  html,body{margin:0;background:#e9ece9;color:#111;font-family:"IBM Plex Sans Thai",Tahoma,sans-serif;font-size:13px;line-height:1.45}
  body.embed{background:#e9ece9}
  .bar{position:sticky;top:0;z-index:2;display:flex;gap:8px;align-items:center;justify-content:center;flex-wrap:wrap;padding:10px 16px;background:#07211b;color:#fff}
  .bar a,.bar button{font:inherit;font-size:14px;border:1px solid rgba(255,255,255,.35);background:transparent;color:#fff;border-radius:8px;padding:6px 12px;text-decoration:none;cursor:pointer}
  .bar .on{background:#fff;color:#07211b;border-color:#fff}
  .bar .go{background:#12946f;border-color:#12946f}
  .sheet{position:relative;margin:16px auto;background:#fff;box-shadow:0 2px 14px rgba(0,0,0,.12);overflow:hidden}
  .num{font-variant-numeric:tabular-nums;white-space:nowrap}
  .r{text-align:right}.c{text-align:center}
  .stamp{position:absolute;left:50%;top:38%;transform:translate(-50%,-50%) rotate(-18deg);border:4px solid #c62828;color:#c62828;
         font-weight:700;letter-spacing:.08em;padding:4px 18px;border-radius:8px;opacity:.75;pointer-events:none;white-space:nowrap;background:rgba(255,255,255,.6)}
  .stamp small{display:block;font-size:.38em;letter-spacing:0;font-weight:600;text-align:center}
  .stamp.smp{border-color:#8a6d1f;color:#8a6d1f}

  /* ---------- 80 มม. ---------- */
  .s80{width:80mm;padding:5mm 4mm 7mm}
  .s80 .hd{text-align:center}
  .s80 .co{font-size:15px;font-weight:700}
  .s80 .sm{font-size:11.5px;color:#333}
  .s80 hr{border:0;border-top:1px dashed #888;margin:7px 0}
  .s80 .tt{text-align:center;font-weight:700;font-size:14px;margin:2px 0}
  .s80 .kv{display:flex;justify-content:space-between;gap:8px}
  .s80 .ln{margin:4px 0}
  .s80 .ln .nm{font-weight:500}
  .s80 .ln .kv{color:#333;font-size:12px}
  .s80 .tot{font-size:16px;font-weight:700}
  .s80 .ft{text-align:center;font-size:11.5px;margin-top:8px;white-space:pre-line}
  .s80 .stamp{font-size:34px}

  /* ---------- A4 ---------- */
  .sa4{width:210mm;min-height:297mm;padding:16mm 15mm}
  .sa4 .top{display:flex;justify-content:space-between;gap:20px;align-items:flex-start}
  .sa4 .co{font-size:19px;font-weight:700}
  .sa4 .sm{color:#333}
  .sa4 .tbox{border:1.5px solid #07211b;border-radius:8px;padding:10px 14px;min-width:70mm;text-align:center}
  .sa4 .tbox h1{font-size:17px;margin:0 0 6px}
  .sa4 .tbox .kv{display:flex;justify-content:space-between;gap:12px;text-align:left}
  .sa4 table{width:100%;border-collapse:collapse;margin-top:18px}
  .sa4 th{background:#eef3f1;font-weight:600;border-bottom:1.5px solid #07211b;padding:7px 8px;text-align:left}
  .sa4 th.r{text-align:right}.sa4 th.c{text-align:center}
  .sa4 td{padding:7px 8px;border-bottom:1px solid #ddd;vertical-align:top}
  .sa4 td small{color:#666}
  .sa4 .sum{display:flex;justify-content:space-between;gap:24px;margin-top:14px;align-items:flex-start}
  .sa4 .words{flex:1;background:#f6f8f7;border-radius:6px;padding:8px 12px}
  .sa4 .sumt{min-width:85mm}
  .sa4 .sumt .kv{display:flex;justify-content:space-between;padding:3px 0}
  .sa4 .sumt .tot{border-top:1.5px solid #07211b;margin-top:4px;padding-top:6px;font-size:16px;font-weight:700}
  .sa4 .pay{margin-top:14px;color:#333}
  .sa4 .sign{display:flex;justify-content:space-around;margin-top:46px;text-align:center}
  .sa4 .sign div{width:62mm;border-top:1px dotted #555;padding-top:6px}
  .sa4 .ft{margin-top:26px;text-align:center;color:#333;white-space:pre-line}
  .sa4 .stamp{font-size:72px}

  @media screen and (max-width:840px){ .sa4{width:100%;min-height:0;padding:20px 16px} .sa4 .top{flex-direction:column} .sa4 .tbox{min-width:0;width:100%} .sa4 .sum{flex-direction:column} .sa4 .sumt{min-width:0;width:100%} }
  @media print{
    html,body{background:#fff}
    .bar{display:none}
    .sheet{margin:0;box-shadow:none}
    .stamp{background:transparent}
    th{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  }
  <?php if ($size === '80'): ?>@page{size:80mm auto;margin:0}<?php else: ?>@page{size:A4;margin:0}<?php endif; ?>
</style>
</head>
<body class="<?= $embed ? 'embed' : '' ?>">

<?php if (!$embed): ?>
  <div class="bar">
    <a class="<?= $size === '80' ? 'on' : '' ?>" href="<?= e($selfQs(array('size' => '80', 'print' => null))) ?>">ใบเสร็จ 80 มม.</a>
    <a class="<?= $size === 'a4' ? 'on' : '' ?>" href="<?= e($selfQs(array('size' => 'a4', 'print' => null))) ?>">กระดาษ A4</a>
    <button type="button" class="go" onclick="window.print()">พิมพ์บิล</button>
  </div>
<?php endif; ?>

<?php if ($size === '80'): ?>
<!-- ==================== ใบเสร็จ 80 มม. ==================== -->
<div class="sheet s80">
  <div class="hd">
    <div class="co"><?= e($h['company']) ?></div>
    <div class="sm"><?= e($h['branch_name']) ?></div>
    <?php if ($h['bill_address'] !== ''): ?><div class="sm"><?= e($h['bill_address']) ?></div><?php endif; ?>
    <?php if ($h['bill_phone'] !== ''): ?><div class="sm">โทร <?= e($h['bill_phone']) ?></div><?php endif; ?>
    <?php if ($h['bill_extra'] !== ''): ?><div class="sm"><?= e($h['bill_extra']) ?></div><?php endif; ?>
    <?php if ($showTax): ?>
      <div class="sm">เลขประจำตัวผู้เสียภาษี <?= e(tax_id_format($h['tax_id'])) ?></div>
      <div class="sm">(<?= e(tax_branch_label($h['tax_branch'])) ?>)</div>
    <?php endif; ?>
  </div>
  <hr>
  <div class="tt"><?= e($title) ?></div>
  <div class="kv"><span>เลขที่</span><b class="num"><?= e($bill['no']) ?></b></div>
  <div class="kv"><span>วันที่</span><span class="num"><?= e($when) ?></span></div>
  <div class="kv"><span>พนักงาน</span><span><?= e($bill['by']) ?></span></div>
  <hr>
  <?php foreach ($bill['lines'] as $l): ?>
    <div class="ln">
      <div class="nm"><?= e($l['name']) ?></div>
      <div class="kv"><span class="num"><?= number_format($l['qty']) ?> <?= e($l['unit']) ?> × <?= e(money2($l['price'])) ?></span><span class="num"><?= e(money2($l['sum'])) ?></span></div>
    </div>
  <?php endforeach; ?>
  <hr>
  <div class="kv"><span>รวม <?= number_format($bill['qty']) ?> ชิ้น</span><span class="num"><?= e(money2($bill['subtotal'])) ?></span></div>
  <?php if ($bill['discount'] > 0): ?>
    <div class="kv"><span>ส่วนลด</span><span class="num">−<?= e(money2($bill['discount'])) ?></span></div>
  <?php endif; ?>
  <div class="kv tot"><span>ยอดสุทธิ</span><span class="num"><?= e(money2($bill['total'])) ?></span></div>
  <?php if ($vat): ?>
    <div class="kv sm"><span>มูลค่าสินค้าก่อนภาษี</span><span class="num"><?= e(money2($vs[0])) ?></span></div>
    <div class="kv sm"><span>ภาษีมูลค่าเพิ่ม <?= e(VAT_RATE) ?>%</span><span class="num"><?= e(money2($vs[1])) ?></span></div>
    <div class="sm c">(ราคารวมภาษีมูลค่าเพิ่มแล้ว)</div>
  <?php endif; ?>
  <hr>
  <div class="kv"><span>ชำระโดย</span><span><?= $bill['method'] === 'cash' ? 'เงินสด' : 'โอน / พร้อมเพย์' ?></span></div>
  <?php if ($bill['method'] === 'cash'): ?>
    <div class="kv"><span>รับเงิน</span><span class="num"><?= e(money2($bill['received'])) ?></span></div>
    <div class="kv"><span>เงินทอน</span><span class="num"><?= e(money2($bill['change'])) ?></span></div>
  <?php endif; ?>
  <?php if (trim($h['footer']) !== ''): ?><hr><div class="ft"><?= e($h['footer']) ?></div><?php endif; ?>
  <?php if ($void): ?><div class="stamp">ยกเลิก<small>บิลนี้ถูกยกเลิกแล้ว</small></div>
  <?php elseif (!empty($bill['sample'])): ?><div class="stamp smp">ตัวอย่าง</div><?php endif; ?>
</div>

<?php else: ?>
<!-- ==================== กระดาษ A4 ==================== -->
<div class="sheet sa4">
  <div class="top">
    <div>
      <div class="co"><?= e($h['company']) ?></div>
      <div class="sm"><?= e($h['branch_name']) ?><?= $h['bill_address'] !== '' ? ' · ' . e($h['bill_address']) : '' ?></div>
      <?php if ($h['bill_phone'] !== '' || $h['bill_extra'] !== ''): ?>
        <div class="sm"><?= $h['bill_phone'] !== '' ? 'โทร ' . e($h['bill_phone']) : '' ?><?= $h['bill_phone'] !== '' && $h['bill_extra'] !== '' ? ' · ' : '' ?><?= e($h['bill_extra']) ?></div>
      <?php endif; ?>
      <?php if ($showTax): ?>
        <div class="sm">เลขประจำตัวผู้เสียภาษี <?= e(tax_id_format($h['tax_id'])) ?> (<?= e(tax_branch_label($h['tax_branch'])) ?>)</div>
      <?php endif; ?>
    </div>
    <div class="tbox">
      <h1><?= e($title) ?></h1>
      <div class="kv"><span>เลขที่</span><b class="num"><?= e($bill['no']) ?></b></div>
      <div class="kv"><span>วันที่</span><span class="num"><?= e($when) ?></span></div>
      <div class="kv"><span>พนักงานขาย</span><span><?= e($bill['by']) ?></span></div>
    </div>
  </div>

  <table>
    <thead><tr><th class="c" style="width:12mm">ลำดับ</th><th>รายการ</th><th class="r">จำนวน</th><th class="r">ราคาต่อหน่วย</th><th class="r">จำนวนเงิน</th></tr></thead>
    <tbody>
      <?php $i = 0; foreach ($bill['lines'] as $l): $i++; ?>
        <tr><td class="c"><?= $i ?></td>
            <td><?= e($l['name']) ?><br><small><?= e($l['sku']) ?></small></td>
            <td class="r num"><?= number_format($l['qty']) ?> <?= e($l['unit']) ?></td>
            <td class="r num"><?= e(money2($l['price'])) ?></td>
            <td class="r num"><?= e(money2($l['sum'])) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="sum">
    <div class="words"><small>จำนวนเงิน (ตัวอักษร)</small><br><b>(<?= e(thai_baht_text($bill['total'])) ?>)</b></div>
    <div class="sumt">
      <div class="kv"><span>รวมเป็นเงิน</span><span class="num"><?= e(money2($bill['subtotal'])) ?></span></div>
      <?php if ($bill['discount'] > 0): ?>
        <div class="kv"><span>ส่วนลด</span><span class="num">−<?= e(money2($bill['discount'])) ?></span></div>
      <?php endif; ?>
      <?php if ($vat): ?>
        <div class="kv"><span>มูลค่าสินค้าก่อนภาษี</span><span class="num"><?= e(money2($vs[0])) ?></span></div>
        <div class="kv"><span>ภาษีมูลค่าเพิ่ม <?= e(VAT_RATE) ?>%</span><span class="num"><?= e(money2($vs[1])) ?></span></div>
      <?php endif; ?>
      <div class="kv tot"><span>ยอดสุทธิ<?= $vat ? ' (รวม VAT)' : '' ?></span><span class="num"><?= e(money2($bill['total'])) ?></span></div>
    </div>
  </div>

  <div class="pay">ชำระโดย <?= $bill['method'] === 'cash' ? 'เงินสด · รับเงิน ' . e(money2($bill['received'])) . ' · เงินทอน ' . e(money2($bill['change'])) : 'โอน / พร้อมเพย์' ?> บาท</div>

  <div class="sign"><div>ผู้รับเงิน</div><div>ผู้ซื้อ / ลูกค้า</div></div>
  <?php if (trim($h['footer']) !== ''): ?><div class="ft"><?= e($h['footer']) ?></div><?php endif; ?>
  <?php if ($void): ?><div class="stamp">ยกเลิก<small>บิลนี้ถูกยกเลิกแล้ว</small></div>
  <?php elseif (!empty($bill['sample'])): ?><div class="stamp smp">ตัวอย่าง</div><?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($_GET['print'])): ?>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
<?php endif; ?>
</body>
</html>
