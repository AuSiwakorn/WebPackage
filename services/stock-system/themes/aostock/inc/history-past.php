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
      <h2>เอกสารคลังของวันที่ <?= e(thai_date_full($pastTs)) ?></h2>
      <span class="sub">ย้อนหลัง <?= $age ?> วัน · ยกเลิก/แก้ไขแล้วสต๊อกจะถูกปรับวันนี้ และลงประวัติของวันนี้</span>
    </div>
  </div>

  <?php if (!$docs): ?>
    <p class="empty"><svg class="ico"><use href="#i-history"/></svg>วันนี้ไม่มีเอกสารคลัง<br><small>(วันอาทิตย์ร้านปิด หรือไม่มีการรับเข้า/เบิก/ตรวจนับ)</small></p>
  <?php else: ?>
    <ol class="tl">
      <?php foreach ($docs as $d):
          $t   = $types[$d['kind']];
          $chk = past_can_edit($user, $d); ?>
        <li class="tl-i tone-<?= e($t['tone']) ?><?= $d['by_user'] === $user['username'] ? ' me' : '' ?>">
          <span class="tl-ic"><svg class="ico"><use href="#<?= e($icons[$d['kind']]) ?>"/></svg></span>
          <div class="tl-b">
            <div class="tl-h">
              <b><?= e($t['label']) ?> <?= e($d['no']) ?></b>
              <span class="badge b-<?= e($t['tone']) ?>"><?= e($t['label']) ?></span>
              <span class="tl-amt num">
                <?php if ($d['kind'] === 'RC'): ?>+<?= number_format($d['qty']) ?> ชิ้น
                <?php elseif ($d['kind'] === 'IS'): ?>−<?= number_format($d['qty']) ?> ชิ้น
                <?php else: ?><?= (int) $d['items'] ?> รายการ<?php endif; ?>
              </span>
            </div>
            <div class="tl-m"><?= e($d['time']) ?> น. · <?= e($d['by']) ?></div>

            <dl class="tl-d">
              <?php foreach ($d['lines'] as $l): ?>
                <div><dt><?= e($l['name']) ?></dt>
                  <dd class="num"><?php if ($d['kind'] === 'AD'): ?>ระบบ <?= (int) $l['have'] ?> → นับได้ <?= (int) $l['counted'] ?> (<?= $l['diff'] > 0 ? '+' : '' ?><?= (int) $l['diff'] ?>)<?php else: ?><?= (int) $l['qty'] ?> <?= e($l['unit']) ?><?php endif; ?></dd></div>
              <?php endforeach; ?>
              <?php if ($d['ref'] !== ''): ?><div><dt>เอกสารอ้างอิง</dt><dd><?= e($d['ref']) ?></dd></div><?php endif; ?>
              <?php if ($d['kind'] === 'IS'): ?><div><dt>เหตุผล</dt><dd><?= e(issue_reason_label($d['reason'])) ?><?= $d['note'] !== '' ? ' — ' . e($d['note']) : '' ?></dd></div><?php endif; ?>
              <?php if ($d['kind'] === 'AD'): ?><div><dt>สาเหตุ</dt><dd><?= e(adj_reason_label($d['reason'])) ?></dd></div><?php endif; ?>
            </dl>

            <?php if (!empty($d['void'])): ?>
              <p class="tl-voided">
                <svg class="ico"><use href="#i-ban"/></svg>
                ใบนี้ถูก<?= $d['void_mode'] === 'edit' ? 'ยกเลิกเพื่อแก้ไข' : 'ยกเลิก' ?>ย้อนหลังเมื่อ <?= e($d['void_at']) ?> น.
                โดย <?= e($d['void_by']) ?> — <?= e($d['void_reason']) ?>
              </p>
            <?php elseif ($chk[0]): ?>
              <?php $dat = ' data-doc="' . e($docK[$d['kind']]) . '" data-bill="' . e($d['no']) . '" data-qty="' . (int) $d['qty']
                         . '" data-items="' . (int) $d['items'] . '"'; ?>
              <div class="tl-act">
                <?php
                $btns = array(array('void', 'past_void', 'ยกเลิกใบนี้', 'i-ban'));
                if ($user['role'] === 'staff') {                  // แก้ไข = ทำใบใหม่ในหน้างานคลัง → เฉพาะพนักงาน
                    array_unshift($btns, array('edit', 'past_edit', 'แก้ไขใบนี้', 'i-arrow'));
                }
                foreach ($btns as $b): ?>
                  <form method="post" action="<?= e(hist_url('d=' . $pastDay)) ?>" data-confirm="<?= e($b[0]) ?>"<?= $dat ?>>
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="act" value="<?= e($b[1]) ?>">
                    <input type="hidden" name="no" value="<?= e($d['no']) ?>">
                    <input class="reason-fb" type="text" name="reason" value=""
                           placeholder="หมายเหตุ (จำเป็น)" aria-label="หมายเหตุ <?= e($d['no']) ?>">
                    <button class="btn btn-ghost btn-sm" type="submit">
                      <svg class="ico"><use href="#<?= e($b[3]) ?>"/></svg> <?= e($b[2]) ?>
                    </button>
                  </form>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p class="tl-lock"><svg class="ico"><use href="#i-info"/></svg><?= e($chk[1]) ?></p>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>
</section>

<section class="card">
  <div class="card-head">
    <div>
      <h2>บิลขายของวันที่ <?= e(thai_date_full($pastTs)) ?></h2>
      <span class="sub">บิลของวันก่อนยกเลิกย้อนหลังไม่ได้ — ลูกค้าคืนของให้ใช้ “รับคืนสินค้า” (คืนเงินสดจากลิ้นชักวันนี้)</span>
    </div>
  </div>
  <?php if (!$bills): ?>
    <p class="empty">ไม่มีบิลขายในวันนี้</p>
  <?php else: ?>
    <div class="tbl-wrap">
      <table class="tbl num">
        <thead><tr><th>เลขบิล</th><th>รายการ</th><th class="r">ยอด</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($bills as $b):
              $names = array();
              foreach ($b['lines'] as $l) { $names[] = $l['name'] . ' ×' . $l['qty']; } ?>
            <tr>
              <td class="doc" data-label="เลขบิล"><?= e($b['no']) ?><small><?= e($b['time']) ?> น. · <?= e($b['by']) ?> · <?= $b['method'] === 'cash' ? 'เงินสด' : 'โอน' ?></small></td>
              <td data-label="รายการ"><span class="ret-names"><?= e(implode(' · ', $names)) ?></span></td>
              <td class="r" data-label="ยอด"><?= e(money2($b['total'])) ?></td>
              <td class="r">
                <?php if (!empty($b['void'])): ?>
                  <span class="bdg bdg-adj">ยกเลิกแล้ว</span>
                <?php elseif ($user['role'] === 'staff' && can($user, 'refund') && menu_enabled('return.php')): ?>
                  <a class="btn btn-ghost btn-sm" href="return.php?bill=<?= e(rawurlencode($b['no'])) ?>">รับคืน</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php $events = array_reverse(log_of_day($code, $pastTs)); ?>
<section class="card">
  <div class="card-head">
    <div>
      <h2>ลำดับเหตุการณ์ทั้งวัน</h2>
      <span class="sub">ทุกรายการที่บันทึกของ<?= e(branch_name($code)) ?>วันที่ <?= e(thai_date_full($pastTs)) ?> เรียงจากเช้าไปเย็น
        · เปิด–ปิดร้าน เงินเข้า / ออกลิ้นชัก บิล ยกเลิก รับคืน และเอกสารคลัง</span>
    </div>
  </div>
  <?php if (!$events): ?>
    <p class="empty"><svg class="ico"><use href="#i-history"/></svg>ไม่มีรายการในวันนี้</p>
  <?php else: ?>
    <ol class="tl">
      <?php foreach ($events as $r): $m = log_type_of($r['type']); ?>
        <li class="tl-i tone-<?= e($m['tone']) ?><?= $r['by_user'] === $user['username'] ? ' me' : '' ?>">
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
