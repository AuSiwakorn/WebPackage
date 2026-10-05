<?php
/**
 * FILE: themes/aostock/inc/movement-feed.php
 * ROLE: รายการเคลื่อนไหวล่าสุดของทุกสินค้า (ตอนยังไม่ได้เลือกสินค้า)
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_move (ผ่าน api.php — คิวรีเดียวทุกสินค้า)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ความเคลื่อนไหวอ่านจาก ao_stock_move (ช่วงที่ 6)
 *   - [x] บิลขาย / รับคืนเขียน stock_move แล้ว ไม่อ่าน session (ช่วงที่ 7)
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   รายการเคลื่อนไหวล่าสุดของทุกสินค้า (ตอนยังไม่ได้เลือกสินค้า)
   ใช้ร่วมกัน: movements.php (พนักงาน — สาขาตัวเอง) และ adm-movements.php (ผู้ดูแล — สาขาเดียวหรือทุกสาขา)
   ต้องกำหนดก่อน include: $feedCodes (array รหัสสาขา) $period $pers $q $cat
     ไม่บังคับ: $MV_PAGE (หน้าปลายทาง) $MV_EXTRA (ค่าเพิ่มในลิงก์ เช่น b=RS)
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$__page  = isset($MV_PAGE) ? $MV_PAGE : 'movements.php';
$__extra = isset($MV_EXTRA) ? $MV_EXTRA : array();
$__feed  = movement_feed($feedCodes, $period, 100);
$__many  = count($feedCodes) > 1;
$__brs   = branches_all();
?>
<div class="mv-head mv-head--feed">
  <div>
    <h2>เคลื่อนไหวล่าสุด</h2>
    <span class="sub">ทุกสินค้า · ใหม่สุดก่อน · กดชื่อสินค้าเพื่อดูยอดยกมา / คงเหลือของตัวนั้น</span>
  </div>
  <div class="segs mv-per">
    <?php foreach ($pers as $k => $label) { ?>
      <a class="seg<?php echo $period === $k ? ' on' : '' ?>" href="<?php echo e($__page . move_qs($q, $cat, '', $k, $__extra)) ?>"><?php echo e($label) ?></a>
    <?php } ?>
  </div>
</div>

<?php if (!$__feed['rows']) { ?>
  <p class="empty"><svg class="ico"><use href="#i-activity"/></svg>ยังไม่มีความเคลื่อนไหวในช่วงนี้<br><small>ลองเลือกช่วงเวลาที่ยาวขึ้น</small></p>
<?php } else { ?>
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
        <?php foreach ($__feed['rows'] as $r) { $m = move_type_of($r['type']); ?>
          <tr class="mv-r tone-<?php echo e($m['tone']) ?><?php echo date('Ymd', $r['ts']) === date('Ymd') ? ' is-today' : '' ?>">
            <td data-label="เมื่อ" class="mv-when"><?php echo e(move_when($r['ts'])) ?><?php if ($__many) { ?> <span class="hist-br"><?php echo e(isset($__brs[$r['branch']]) ? $__brs[$r['branch']]['short'] : $r['branch']) ?></span><?php } ?></td>
            <td data-label="สินค้า">
              <a class="mv-feed-p" href="<?php echo e($__page . move_qs($q, $cat, $r['p']['sku'], $period, $__extra)) ?>"><b><?php echo e($r['p']['name']) ?></b></a>
              <small><?php echo e($r['p']['sku']) ?></small>
            </td>
            <td data-label="รายการ">
              <span class="badge b-<?php echo e($m['tone']) ?>"><?php echo e($m['label']) ?></span>
              <span class="doc"><?php echo e($r['doc']) ?></span>
              <small class="mv-by-in">โดย <?php echo e($r['by']) ?><?php echo $r['note'] !== '' ? ' · ' . e($r['note']) : '' ?></small>
            </td>
            <td data-label="โดย" class="mv-by-col"><?php echo e($r['by']) ?></td>
            <td data-label="เข้า / ออก" class="r num">
              <?php if ($r['delta'] > 0) { ?><b class="mv-up">+<?php echo number_format($r['delta']) ?></b>
              <?php } elseif ($r['delta'] < 0) { ?><b class="mv-dn">−<?php echo number_format(-$r['delta']) ?></b>
              <?php } else { ?><span class="mv-eq0">0</span><?php } ?>
            </td>
            <td data-label="คงเหลือ" class="r num"><b><?php echo number_format($r['bal']) ?></b></td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
  <p class="mv-foot">
    แสดง <?php echo number_format(count($__feed['rows'])) ?> รายการล่าสุดจากทั้งหมด <?php echo number_format($__feed['total']) ?> รายการใน<?php echo e($period === 'today' ? 'วันนี้' : ' ' . $pers[$period] . 'ล่าสุด') ?>
    · ดูย้อนหลังได้สูงสุด 14 วัน · ยอดคงเหลือหลังแต่ละรายการคำนวณย้อนจากยอดปัจจุบัน
  </p>
<?php } ?>
