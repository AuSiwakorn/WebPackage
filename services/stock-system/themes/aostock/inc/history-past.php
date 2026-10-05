<?php
/**
 * FILE: themes/aostock/inc/history-past.php
 * ROLE: ประวัติของวันก่อน (ส่วนหนึ่งของ history.php)
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: ao_stock_receive / issue / count (เอกสารคลังวันก่อน) · ao_stock_sale (บิลวันก่อน) · ao_stock_log (ลำดับเหตุการณ์) — ผ่าน api.php
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] เอกสารคลังวันก่อน + ยกเลิก / แก้ไขย้อนหลัง อ่านเขียนตาราง (ช่วงที่ 6)
 *   - [x] บิลขายวันก่อน (ช่วงที่ 7) · บิลที่ยกเลิกแสดงป้าย ไม่มีปุ่มรับคืน
 *   - [x] ช่วงที่ 8: ลำดับเหตุการณ์ทั้งวันจาก ao_stock_log (เปิด–ปิดร้าน เงินเข้าออก รับคืน ยกเลิก ฯลฯ) ใต้บิลขาย
 *   - [x] ช่วงที่ 9: ปุ่ม "รับคืน" ซ่อนเมื่อเมนูรับคืนถูกปิดจากหลังบ้าน
 *   - [x] ช่วงที่ 11: ปุ่ม "แก้ไขใบนี้" เฉพาะคนที่เข้าหน้างานคลังชนิดนั้นได้ · ปุ่ม "รับคืน" ตามสิทธิ์ (page_ok)
 *   - [x] ช่วงที่ 12: ลำดับเหตุการณ์ไม่แสดงรายการ "ตั้งค่า" ให้พนักงานทั่วไป
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
/* ==========================================================
   ประวัติของวันก่อน (ส่วนหนึ่งของ history.php)
   แสดงเอกสารคลังของวันนั้น พร้อมปุ่มแก้/ยกเลิกย้อนหลังตามสิทธิ์
   บิลขายของวันนั้น (ดูอย่างเดียว — คืนของใช้หน้า "รับคืนสินค้า")
   และลำดับเหตุการณ์ทั้งวันจากประวัติ (log_of_day) เรียงจากเช้าไปเย็น
   ใช้ทั้งใน history.php (พนักงาน) และ inc/adm-history-day.php (ผู้ดูแล)
   ตัวแปรที่ต้องมี: $user, $code, $pastTs, $pastDay · ฐานลิงก์ของฟอร์มมาจาก hist_url()
   ========================================================== */

require_once dirname(__FILE__) . '/../include/function.php';

$docs  = past_docs($code, $pastTs);
$bills = past_bills($code, $pastTs);
$types = past_types();
$icons = array('RC' => 'i-in', 'IS' => 'i-out', 'AD' => 'i-clipboard');
$docK  = array('RC' => 'recv', 'IS' => 'issue', 'AD' => 'adj');
$age   = (int) round((strtotime(date('Y-m-d')) - $pastTs) / 86400);
?>

<section class="card">
  <div class="card-head">
    <div>
      <h2>เอกสารคลังของวันที่ <?php echo e(thai_date_full($pastTs)) ?></h2>
      <span class="sub">ย้อนหลัง <?php echo $age ?> วัน · ยกเลิก/แก้ไขแล้วสต๊อกจะถูกปรับวันนี้ และลงประวัติของวันนี้</span>
    </div>
  </div>

  <?php if (!$docs) { ?>
    <p class="empty"><svg class="ico"><use href="#i-history"/></svg>วันนี้ไม่มีเอกสารคลัง<br><small>(วันอาทิตย์ร้านปิด หรือไม่มีการรับเข้า/เบิก/ตรวจนับ)</small></p>
  <?php } else { ?>
    <ol class="tl">
      <?php foreach ($docs as $d) {
          $t   = $types[$d['kind']];
          $chk = past_can_edit($user, $d); ?>
        <li class="tl-i tone-<?php echo e($t['tone']) ?><?php echo $d['by_user'] === $user['username'] ? ' me' : '' ?>">
          <span class="tl-ic"><svg class="ico"><use href="#<?php echo e($icons[$d['kind']]) ?>"/></svg></span>
          <div class="tl-b">
            <div class="tl-h">
              <b><?php echo e($t['label']) ?> <?php echo e($d['no']) ?></b>
              <span class="badge b-<?php echo e($t['tone']) ?>"><?php echo e($t['label']) ?></span>
              <span class="tl-amt num">
                <?php if ($d['kind'] === 'RC') { ?>+<?php echo number_format($d['qty']) ?> ชิ้น
                <?php } elseif ($d['kind'] === 'IS') { ?>−<?php echo number_format($d['qty']) ?> ชิ้น
                <?php } else { ?><?php echo (int) $d['items'] ?> รายการ<?php } ?>
              </span>
            </div>
            <div class="tl-m"><?php echo e($d['time']) ?> น. · <?php echo e($d['by']) ?></div>

            <dl class="tl-d">
              <?php foreach ($d['lines'] as $l) { ?>
                <div><dt><?php echo e($l['name']) ?></dt>
                  <dd class="num"><?php if ($d['kind'] === 'AD') { ?>ระบบ <?php echo (int) $l['have'] ?> → นับได้ <?php echo (int) $l['counted'] ?> (<?php echo $l['diff'] > 0 ? '+' : '' ?><?php echo (int) $l['diff'] ?>)<?php } else { ?><?php echo (int) $l['qty'] ?> <?php echo e($l['unit']) ?><?php } ?></dd></div>
              <?php } ?>
              <?php if ($d['ref'] !== '') { ?><div><dt>เอกสารอ้างอิง</dt><dd><?php echo e($d['ref']) ?></dd></div><?php } ?>
              <?php if ($d['kind'] === 'IS') { ?><div><dt>เหตุผล</dt><dd><?php echo e(issue_reason_label($d['reason'])) ?><?php echo $d['note'] !== '' ? ' — ' . e($d['note']) : '' ?></dd></div><?php } ?>
              <?php if ($d['kind'] === 'AD') { ?><div><dt>สาเหตุ</dt><dd><?php echo e(adj_reason_label($d['reason'])) ?></dd></div><?php } ?>
            </dl>

            <?php if (!empty($d['void'])) { ?>
              <p class="tl-voided">
                <svg class="ico"><use href="#i-ban"/></svg>
                ใบนี้ถูก<?php echo $d['void_mode'] === 'edit' ? 'ยกเลิกเพื่อแก้ไข' : 'ยกเลิก' ?>ย้อนหลังเมื่อ <?php echo e($d['void_at']) ?> น.
                โดย <?php echo e($d['void_by']) ?> — <?php echo e($d['void_reason']) ?>
              </p>
            <?php } elseif ($chk[0]) { ?>
              <?php $dat = ' data-doc="' . e($docK[$d['kind']]) . '" data-bill="' . e($d['no']) . '" data-qty="' . (int) $d['qty']
                         . '" data-items="' . (int) $d['items'] . '"'; ?>
              <div class="tl-act">
                <?php
                $btns = array(array('void', 'past_void', 'ยกเลิกใบนี้', 'i-ban'));
                if ($user['role'] === 'staff' && page_perm_ok($user, $t['page'])) {   // แก้ไข = ทำใบใหม่ในหน้างานคลัง → เฉพาะพนักงานที่เข้าหน้านั้นได้ (ช่วงที่ 11)
                    array_unshift($btns, array('edit', 'past_edit', 'แก้ไขใบนี้', 'i-arrow'));
                }
                foreach ($btns as $b) { ?>
                  <form method="post" action="<?php echo e(hist_url('d=' . $pastDay)) ?>" data-confirm="<?php echo e($b[0]) ?>"<?php echo $dat ?>>
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()) ?>">
                    <input type="hidden" name="act" value="<?php echo e($b[1]) ?>">
                    <input type="hidden" name="no" value="<?php echo e($d['no']) ?>">
                    <input class="reason-fb" type="text" name="reason" value=""
                           placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุ <?php echo e($d['no']) ?>">
                    <button class="btn btn-ghost btn-sm" type="submit">
                      <svg class="ico"><use href="#<?php echo e($b[3]) ?>"/></svg> <?php echo e($b[2]) ?>
                    </button>
                  </form>
                <?php } ?>
              </div>
            <?php } else { ?>
              <p class="tl-lock"><svg class="ico"><use href="#i-info"/></svg><?php echo e($chk[1]) ?></p>
            <?php } ?>
          </div>
        </li>
      <?php } ?>
    </ol>
  <?php } ?>
</section>

<section class="card">
  <div class="card-head">
    <div>
      <h2>บิลขายของวันที่ <?php echo e(thai_date_full($pastTs)) ?></h2>
      <span class="sub">บิลของวันก่อนยกเลิกย้อนหลังไม่ได้ — ลูกค้าคืนของให้ใช้ “รับคืนสินค้า” (คืนเงินสดจากลิ้นชักวันนี้)</span>
    </div>
  </div>
  <?php if (!$bills) { ?>
    <p class="empty">ไม่มีบิลขายในวันนี้</p>
  <?php } else { ?>
    <div class="tbl-wrap">
      <table class="tbl num">
        <thead><tr><th>เลขบิล</th><th>รายการ</th><th class="r">ยอด</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($bills as $b) {
              $names = array();
              foreach ($b['lines'] as $l) { $names[] = $l['name'] . ' ×' . $l['qty']; } ?>
            <tr>
              <td class="doc" data-label="เลขบิล"><?php echo e($b['no']) ?><small><?php echo e($b['time']) ?> น. · <?php echo e($b['by']) ?> · <?php echo $b['method'] === 'cash' ? 'เงินสด' : 'โอน' ?></small></td>
              <td data-label="รายการ"><span class="ret-names"><?php echo e(implode(' · ', $names)) ?></span></td>
              <td class="r" data-label="ยอด"><?php echo e(money2($b['total'])) ?></td>
              <td class="r">
                <?php if (!empty($b['void'])) { ?>
                  <span class="bdg bdg-adj">ยกเลิกแล้ว</span>
                <?php } elseif ($user['role'] === 'staff' && page_ok($user, 'return.php')) { ?>
                  <a class="btn btn-ghost btn-sm" href="return.php?bill=<?php echo e(rawurlencode($b['no'])) ?>">รับคืน</a>
                <?php } ?>
              </td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } ?>
</section>

<?php $events = array_reverse(log_of_day($code, $pastTs, 0, !is_branch_manager($user))); /* ช่วงที่ 12: พนักงานทั่วไปไม่เห็นรายการตั้งค่า */ ?>
<section class="card">
  <div class="card-head">
    <div>
      <h2>ลำดับเหตุการณ์ทั้งวัน</h2>
      <span class="sub">ทุกรายการที่บันทึกของ<?php echo e(branch_name($code)) ?>วันที่ <?php echo e(thai_date_full($pastTs)) ?> เรียงจากเช้าไปเย็น
        · เปิด–ปิดร้าน เงินเข้า / ออกลิ้นชัก บิล ยกเลิก รับคืน และเอกสารคลัง</span>
    </div>
  </div>
  <?php if (!$events) { ?>
    <p class="empty"><svg class="ico"><use href="#i-history"/></svg>ไม่มีรายการในวันนี้</p>
  <?php } else { ?>
    <ol class="tl">
      <?php foreach ($events as $r) { $m = log_type_of($r['type']); ?>
        <li class="tl-i tone-<?php echo e($m['tone']) ?><?php echo $r['by_user'] === $user['username'] ? ' me' : '' ?>">
          <span class="tl-ic"><svg class="ico"><use href="#<?php echo e($m['icon']) ?>"/></svg></span>
          <div class="tl-b">
            <div class="tl-h">
              <b><?php echo e($r['title']) ?></b>
              <span class="badge b-<?php echo e($m['tone']) ?>"><?php echo e($m['label']) ?></span>
              <?php if ($r['amount'] !== null && in_array($r['type'], array('receive', 'rvoid'), true)) { ?>
                <span class="tl-amt num"><?php echo $r['type'] === 'receive' ? '+' : '−' ?><?php echo number_format($r['amount']) ?> ชิ้น</span>
              <?php } elseif ($r['amount'] !== null) { ?>
                <span class="tl-amt num"><?php echo money2($r['amount']) ?> ฿</span>
              <?php } ?>
            </div>
            <div class="tl-m"><?php echo e($r['time']) ?> น. · <?php echo e($r['by']) ?></div>
            <?php if ($r['detail']) { ?>
              <dl class="tl-d">
                <?php foreach ($r['detail'] as $k => $v) { ?>
                  <div><dt><?php echo e($k) ?></dt><dd class="num"><?php echo e($v) ?></dd></div>
                <?php } ?>
              </dl>
            <?php } ?>
          </div>
        </li>
      <?php } ?>
    </ol>
  <?php } ?>
</section>
