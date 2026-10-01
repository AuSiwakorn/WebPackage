<?php
/* ==========================================================
   AOSTOCK DEMO — รายการเคลื่อนไหวล่าสุดของทุกสินค้า (ตอนยังไม่ได้เลือกสินค้า)
   ใช้ร่วมกัน: movements.php (พนักงาน — สาขาตัวเอง) และ adm-movements.php (ผู้ดูแล — สาขาเดียวหรือทุกสาขา)
   ต้องกำหนดก่อน include: $feedCodes (array รหัสสาขา) $period $pers $q $cat
     ไม่บังคับ: $MV_PAGE (หน้าปลายทาง) $MV_EXTRA (ค่าเพิ่มในลิงก์ เช่น b=RS)
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$__page  = isset($MV_PAGE) ? $MV_PAGE : 'movements.php';
$__extra = isset($MV_EXTRA) ? $MV_EXTRA : array();
$__feed  = movement_feed($feedCodes, $period, 100);
$__many  = count($feedCodes) > 1;
$__brs   = demo_branches_all();
?>
<div class="mv-head mv-head--feed">
  <div>
    <h2>เคลื่อนไหวล่าสุด</h2>
    <span class="sub">ทุกสินค้า · ใหม่สุดก่อน · กดชื่อสินค้าเพื่อดูยอดยกมา / คงเหลือของตัวนั้น</span>
  </div>
  <div class="segs mv-per">
    <?php foreach ($pers as $k => $label): ?>
      <a class="seg<?= $period === $k ? ' on' : '' ?>" href="<?= e($__page . move_qs($q, $cat, '', $k, $__extra)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if (!$__feed['rows']): ?>
  <p class="empty"><svg class="ico"><use href="#i-activity"/></svg>ยังไม่มีความเคลื่อนไหวในช่วงนี้<br><small>ลองเลือกช่วงเวลาที่ยาวขึ้น</small></p>
<?php else: ?>
  <div class="tbl-wrap">
    <table class="tbl tbl-mv mv-feed">
      <thead>
        <tr>
          <th>เมื่อ</th>
          <th>สินค้า</th>
          <th>รายการ</th>
          <th class="mv-by-col">โดย</th>
          <th class="r">เข้า / ออก</th>
          <th class="r">คงเหลือ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($__feed['rows'] as $r): $m = move_type_of($r['type']); ?>
          <tr class="mv-r tone-<?= e($m['tone']) ?><?= date('Ymd', $r['ts']) === date('Ymd') ? ' is-today' : '' ?>">
            <td data-label="เมื่อ" class="mv-when"><?= e(move_when($r['ts'])) ?><?php if ($__many): ?> <span class="hist-br"><?= e(isset($__brs[$r['branch']]) ? $__brs[$r['branch']]['short'] : $r['branch']) ?></span><?php endif; ?></td>
            <td data-label="สินค้า">
              <a class="mv-feed-p" href="<?= e($__page . move_qs($q, $cat, $r['p']['sku'], $period, $__extra)) ?>"><b><?= e($r['p']['name']) ?></b></a>
              <small><?= e($r['p']['sku']) ?></small>
            </td>
            <td data-label="รายการ">
              <span class="badge b-<?= e($m['tone']) ?>"><?= e($m['label']) ?></span>
              <span class="doc"><?= e($r['doc']) ?></span>
              <small class="mv-by-in">โดย <?= e($r['by']) ?><?= $r['note'] !== '' ? ' · ' . e($r['note']) : '' ?></small>
            </td>
            <td data-label="โดย" class="mv-by-col"><?= e($r['by']) ?></td>
            <td data-label="เข้า / ออก" class="r num">
              <?php if ($r['delta'] > 0): ?><b class="mv-up">+<?= number_format($r['delta']) ?></b>
              <?php elseif ($r['delta'] < 0): ?><b class="mv-dn">−<?= number_format(-$r['delta']) ?></b>
              <?php else: ?><span class="mv-eq0">0</span><?php endif; ?>
            </td>
            <td data-label="คงเหลือ" class="r num"><b><?= number_format($r['bal']) ?></b></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="mv-foot">
    แสดง <?= number_format(count($__feed['rows'])) ?> รายการล่าสุดจากทั้งหมด <?= number_format($__feed['total']) ?> รายการใน<?= e($period === 'today' ? 'วันนี้' : ' ' . $pers[$period] . 'ล่าสุด') ?>
    · ข้อมูลของวันนี้มาจากรายการที่ทำจริงในเดโม ส่วนวันก่อนหน้าเป็นข้อมูลสมมติ
  </p>
<?php endif; ?>
