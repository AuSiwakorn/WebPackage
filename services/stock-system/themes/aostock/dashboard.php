<?php
/**
 * FILE: themes/aostock/dashboard.php
 * ROLE: ภาพรวมของฉัน — หน้าแรกของพนักงาน (งานของตัวเอง งานค้างของสาขา สินค้าใกล้หมด)
 * DEPENDS: themes/aostock/include/function.php, themes/aostock/inc/header.php, themes/aostock/inc/footer.php
 * TABLES: ao_stock_sale / receive / issue / count / return (+ _item) · ao_stock_store_day · ao_stock_balance (ผ่าน api.php)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] ช่วงที่ 8: ตัวเลขจริงจากเอกสาร (ขาย รับเข้า ตัดออก ตรวจนับ รับคืน) · เอาแถบ "ข้อมูลสมมติ" ออก
 *   - [x] ช่วงที่ 10: "เป้าวันนี้" กลับมา — ตั้งรายสาขาในหน้าจัดการสาขา (0 = ไม่แสดง)
 *         · งานที่รอดำเนินการ = ตรวจนับรอบนี้ยังไม่ครบ + วันก่อนที่ยังไม่ได้ปิดร้าน
 *   - [x] ช่วงที่ 11: พาไปเปิดร้านเฉพาะคนที่มีสิทธิ์เปิด / ปิดร้าน · ป้าย "สิทธิ์เสริม" แสดงเฉพาะเมื่อมีสิทธิ์เสริมจริง · ลิงก์งานค้างตามสิทธิ์
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 *   - [x] แจ้งเหตุผลตอนพาไปหน้าเปิดร้าน (เดิมพาไปเงียบ ๆ ผู้ใช้นึกว่าเป็นบั๊ก)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
require_once dirname(__FILE__) . '/include/function.php';

$user   = require_login();                // บัญชี → account.php · ผู้ดูแล → adm-dashboard.php (require_login พาไปเอง)

/* พนักงานที่มีสิทธิ์เปิดร้านต้องเปิดร้านก่อนใช้งานในแต่ละวัน (ช่วงที่ 11: เดิมดูสิทธิ์ขาย) */
if (can($user, 'store') && store_state($user['branch']) === null) {
    /* บอกเหตุผลบนหน้าเปิดร้าน — เดิมพาไปเงียบ ๆ ผู้ใช้นึกว่ากดเมนูแล้วเด้งผิดหน้า (เมนูข้างมีป้าย "เปิดร้านก่อน" ด้วย) */
    $_SESSION['flash'] = 'ร้านยังไม่เปิดวันนี้ — เปิดร้านก่อน แล้วจึงเข้าหน้า “ภาพรวมของฉัน” และ “ขายสินค้า” ได้';
    header('Location: ' . url('store.php'));
    exit;
}
$branch = resolve_branch($user, isset($_GET['branch']) ? $_GET['branch'] : null);
$me     = $user['username'];

/* ---------- ผลงานของฉัน — อ่านครั้งเดียวตั้งแต่ต้นเดือน (หรือ 7 วันก่อน ถ้าเก่ากว่า) ถึงวันนี้ ---------- */
$todayTs   = strtotime(date('Y-m-d'));
$weekTs    = strtotime('-6 day', $todayTs);
$monthTs   = strtotime(date('Y-m-01'));
$days      = staff_days($me, min($weekTs, $monthTs), $todayTs);
$today     = $days[date('Y-m-d', $todayTs)];
$yesterday = $days[date('Y-m-d', strtotime('-1 day', $todayTs))];
$week      = array_values(array_filter($days, function ($d) use ($weekTs) { return $d['ts'] >= $weekTs; }));
$weekSum   = staff_sum($week);
$monthDays = array_values(array_filter($days, function ($d) use ($monthTs) { return $d['ts'] >= $monthTs; }));
$monthSum  = staff_sum($monthDays);
$types     = staff_work_types($today);
$goal      = staff_goal($me);                       // ตั้งรายสาขา (0 = ไม่ตั้งเป้า)
$goalPct   = $goal > 0 ? min(100, (int) round($today['qty'] / $goal * 100)) : 0;
$tops      = staff_top_products($me);
$rank      = branch_rank_today(work_branch($user));
$maxWeek   = 1;
foreach ($week as $w) {
    if ($w['qty'] > $maxWeek) { $maxWeek = $w['qty']; }
}
$short = function ($n) { return $n >= 1000 ? number_format($n / 1000, 1) . 'k' : number_format($n); };

/* ---------- ข้อมูลสต๊อกที่ต้องดูแล ---------- */
$sum   = stock_summary($branch);
$lows  = low_stock_products($branch, 4);
$tasks = pending_tasks($branch);

$hour  = (int) date('G');
$greet = $hour < 12 ? 'สวัสดีตอนเช้า' : ($hour < 17 ? 'สวัสดีตอนบ่าย' : 'สวัสดีตอนเย็น');
$first = explode(' ', $user['name']);
$first = $first[0];

$PAGE_TITLE = 'ภาพรวมของฉัน';
$PAGE_SUB   = branch_label($branch) . ' · ' . thai_date_full(time()) . ' ' . date('H:i') . ' น.';
$NAV_ACTIVE = 'dashboard.php';
require dirname(__FILE__) . '/inc/header.php';

?>

<div class="grid-2">

  <!-- ==================== คอลัมน์ซ้าย ==================== -->
  <div style="display:grid;gap:20px">

    <!-- ===== การ์ดผลงานของฉันวันนี้ ===== -->
    <section class="hero-me num">
      <div class="hm-hi">
        <span class="av"><?php echo e($user['initials']) ?></span>
        <span><?php echo e($greet) ?> <b><?php echo e($first) ?></b> · <?php echo e(thai_date_full(time())) ?>
              · <?php echo e(branch_name($user['branch'])) ?></span>
      </div>
      <?php
      $pl      = perm_list();
      $myPerms = array_values(array_filter(user_perms($user), function ($k) use ($pl) { return isset($pl[$k]) && $pl[$k]['group'] === 'extra'; }));
      ?>
      <?php if ($myPerms) { ?>
        <div class="hm-perms" aria-label="สิทธิ์เสริมของฉัน">
          <span class="hm-perms-lb">สิทธิ์เสริม</span>
          <?php foreach ($myPerms as $pk) { if (!isset($pl[$pk]) || $pl[$pk]['group'] !== 'extra') { continue; } ?>
            <span class="hm-perm" title="<?php echo e($pl[$pk]['label']) ?>"><svg class="ico"><use href="#i-check"/></svg><?php echo e($pl[$pk]['short']) ?></span>
          <?php } ?>
        </div>
      <?php } ?>

      <div class="hm-lb">ชิ้นที่ฉันจัดการวันนี้</div>
      <div class="hm-nm"><?php echo number_format($today['qty']) ?><small>ชิ้น</small></div>
      <div class="hm-sub">
        <?php if ($today['idle']) { ?>
          ยังไม่มีรายการที่บันทึกวันนี้ — นับรวมการขาย รับเข้า เบิก ตรวจนับ และรับคืน
        <?php } else { ?>
          <?php echo number_format($today['docs']) ?> เอกสาร ·
          <?php echo number_format($today['items']) ?> รายการ ·
          เฉลี่ย <?php echo number_format(round($today['qty'] / $today['docs'])) ?> ชิ้น/เอกสาร
        <?php } ?>
      </div>

      <?php if ($goal > 0) { ?>
        <div class="hm-goal<?php echo $goalPct >= 100 ? ' done' : '' ?>">
          <div class="t">
            <span>เป้าวันนี้ <?php echo number_format($goal) ?> ชิ้น</span>
            <b><?php echo $goalPct ?>%</b>
          </div>
          <div class="bar"><i style="width:<?php echo max($goalPct, 2) ?>%"></i></div>
        </div>
      <?php } ?>
    </section>

    <!-- ===== สถิติย่อย ===== -->
    <section class="mini num" aria-label="สรุปผลงานย้อนหลัง">
      <div class="m">
        <div class="lb">เมื่อวาน</div>
        <div class="nm"><?php echo $yesterday['idle'] ? '—' : number_format($yesterday['qty']) ?></div>
        <div class="sb"><?php echo $yesterday['idle'] ? 'ไม่มีรายการ' : number_format($yesterday['docs']) . ' เอกสาร' ?></div>
      </div>
      <div class="m">
        <div class="lb">7 วันล่าสุด</div>
        <div class="nm"><?php echo number_format($weekSum['qty']) ?></div>
        <div class="sb">เฉลี่ย <?php echo number_format($weekSum['days'] > 0 ? round($weekSum['qty'] / $weekSum['days']) : 0) ?>/วันทำงาน</div>
      </div>
      <div class="m">
        <div class="lb">เดือนนี้</div>
        <div class="nm"><?php echo number_format($monthSum['qty']) ?></div>
        <div class="sb"><?php echo number_format($monthSum['days']) ?> วันทำงาน</div>
      </div>
    </section>

    <!-- ===== กราฟ 7 วัน ===== -->
    <section class="card">
      <div class="card-head">
        <div>
          <h3>ผลงานของฉัน 7 วันล่าสุด</h3>
          <p>จำนวนชิ้นที่ขาย รับเข้า เบิกออก ตรวจนับ และรับคืน</p>
        </div>
      </div>
      <div class="card-body">
        <div class="bars num" role="img" aria-label="กราฟแท่งผลงานรายวันย้อนหลัง 7 วัน">
          <?php foreach ($week as $i => $w) {
              $isToday = ($i === count($week) - 1);
              $h = $maxWeek > 0 ? round($w['qty'] / $maxWeek * 100) : 0; ?>
            <div class="b<?php echo $w['qty'] > 0 ? ' has' : '' ?><?php echo $isToday ? ' today' : '' ?>">
              <div class="v" style="height:<?php echo $w['idle'] ? 0 : max($h, 4) ?>%">
                <span><?php echo $w['idle'] ? '—' : e($short($w['qty'])) ?></span>
              </div>
              <div class="l"><?php echo e(short_day($w['ts'])) ?></div>
            </div>
          <?php } ?>
        </div>

        <div class="sr-only">
          <table>
            <caption>ผลงานรายวัน 7 วันล่าสุด</caption>
            <thead><tr><th>วัน</th><th>ชิ้น</th></tr></thead>
            <tbody>
              <?php foreach ($week as $w) { ?>
                <tr><td><?php echo e(short_day($w['ts'])) ?></td><td><?php echo $w['idle'] ? 'ไม่มีรายการ' : number_format($w['qty']) ?></td></tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- ===== ตารางรายวันของเดือนนี้ ===== -->
    <section class="card">
      <div class="card-head">
        <div>
          <h3>รายวัน · เดือนนี้</h3>
          <p>10 วันล่าสุด จากทั้งหมด <?php echo number_format(count($monthDays)) ?> วัน</p>
        </div>
      </div>
      <div class="card-body card-body--flush">
        <div class="tbl-wrap">
          <table class="tbl tbl--compact num">
            <thead>
              <tr><th>วันที่</th><th class="r">เอกสาร</th><th class="r">รายการ</th><th class="r">ชิ้น</th></tr>
            </thead>
            <tbody>
              <?php
              $show = array_slice($monthDays, -10);
              $show = array_reverse($show);
              foreach ($show as $d) { ?>
                <tr<?php echo $d['idle'] ? ' class="off"' : '' ?>>
                  <td data-label="วันที่"><?php echo e(short_day($d['ts'])) ?> <?php echo e(thai_month_short($d['ts'])) ?></td>
                  <?php if ($d['idle']) { ?>
                    <td class="r" data-label="เอกสาร">—</td>
                    <td class="r" data-label="รายการ">—</td>
                    <td class="r" data-label="ชิ้น">ไม่มีรายการ</td>
                  <?php } else { ?>
                    <td class="r" data-label="เอกสาร"><?php echo number_format($d['docs']) ?></td>
                    <td class="r" data-label="รายการ"><?php echo number_format($d['items']) ?></td>
                    <td class="r" data-label="ชิ้น"><b><?php echo number_format($d['qty']) ?></b></td>
                  <?php } ?>
                </tr>
              <?php } ?>
            </tbody>
            <tfoot>
              <tr>
                <th>รวม <?php echo number_format($monthSum['days']) ?> วันทำงาน</th>
                <th class="r"><?php echo number_format($monthSum['docs']) ?></th>
                <th class="r"><?php echo number_format($monthSum['items']) ?></th>
                <th class="r"><?php echo number_format($monthSum['qty']) ?></th>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </section>
  </div>

  <!-- ==================== คอลัมน์ขวา ==================== -->
  <div style="display:grid;gap:20px">

    <!-- ===== งานวันนี้แยกประเภท ===== -->
    <section class="card">
      <div class="card-head">
        <div>
          <h3>งานของฉันวันนี้</h3>
          <p>แยกตามประเภทเอกสาร · ไม่นับใบที่ยกเลิก</p>
        </div>
      </div>
      <div class="card-body">
        <?php if (!$types) { ?>
          <div class="tasks-empty"><svg class="ico"><use href="#i-info"/></svg> ยังไม่มีรายการวันนี้</div>
        <?php } else { ?>
          <div class="wt num">
            <?php foreach ($types as $t) { ?>
              <div class="wt-row t-<?php echo e($t['type']) ?>">
                <div class="t">
                  <span><?php echo e($t['label']) ?> · <?php echo number_format($t['docs']) ?> ใบ</span>
                  <b><?php echo number_format($t['qty']) ?> ชิ้น</b>
                </div>
                <div class="bar"><i style="width:<?php echo max($t['pct'], 2) ?>%"></i></div>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </section>

    <!-- ===== สินค้าที่จัดการบ่อยวันนี้ ===== -->
    <?php if ($tops) { ?>
      <section class="card">
        <div class="card-head">
          <div>
            <h3>สินค้าที่ฉันจัดการบ่อย</h3>
            <p>เฉพาะวันนี้</p>
          </div>
        </div>
        <div class="card-body">
          <div class="kv num">
            <?php foreach ($tops as $t) { ?>
              <div>
                <span>
                  <?php echo e($t['product']['name']) ?>
                  <small><?php echo e($t['product']['sku']) ?></small>
                </span>
                <b><?php echo number_format($t['qty']) ?> <?php echo e($t['product']['unit']) ?></b>
              </div>
            <?php } ?>
          </div>
        </div>
      </section>
    <?php } ?>

    <!-- ===== อันดับในสาขาวันนี้ ===== -->
    <section class="card">
      <div class="card-head">
        <div>
          <h3>ทั้งสาขาวันนี้</h3>
          <p><?php echo e(branch_name($user['branch'])) ?></p>
        </div>
        <?php
        $branchTotal = 0;
        foreach ($rank as $r) { $branchTotal += $r['qty']; }
        ?>
        <span class="card-sum num" style="font-size:.84rem;color:var(--ink-3);white-space:nowrap">
          รวม <?php echo number_format($branchTotal) ?> ชิ้น
        </span>
      </div>
      <div class="card-body card-body--flush">
        <?php $no = 0; foreach ($rank as $r) { $no++; ?>
          <div class="rk num<?php echo $r['username'] === $me ? ' me' : '' ?><?php echo $r['idle'] ? ' off' : '' ?>">
            <span class="n"><?php echo $r['idle'] ? '–' : $no ?></span>
            <span class="nm">
              <b><?php echo e($r['name']) ?></b>
              <?php if ($r['username'] === $me) { ?><small>ฉัน</small><?php } ?>
            </span>
            <span class="a"><?php echo $r['idle'] ? 'ยังไม่มีรายการ' : number_format($r['qty']) ?></span>
          </div>
        <?php } ?>
      </div>
      <div class="card-foot">แสดงเฉพาะจำนวนชิ้น ไม่แสดงต้นทุนหรือมูลค่า · เห็นเฉพาะสาขาของตัวเอง</div>
    </section>

    <!-- ===== งานที่รอดำเนินการ ===== -->
    <section class="card">
      <div class="card-head">
        <div>
          <h3>งานที่รอดำเนินการ</h3>
          <p>งานค้างของ<?php echo e(branch_label($branch)) ?></p>
        </div>
      </div>
      <div class="card-body">
        <?php if (!$tasks) { ?>
          <div class="tasks-empty"><svg class="ico"><use href="#i-check"/></svg> ไม่มีงานค้าง — ตรวจนับรอบนี้ครบแล้ว และปิดร้านครบทุกวัน</div>
        <?php } else { ?>
          <div class="tasks">
            <?php foreach ($tasks as $t) {
                $go = $t['link'] !== '' && page_ok($user, $t['link']); ?>
              <div class="task">
                <span class="ti"><svg class="ico"><use href="#<?php echo $t['type'] === 'count' ? 'i-clipboard' : 'i-alert' ?>"/></svg></span>
                <div>
                  <b><?php echo e($t['title']) ?></b>
                  <small><?php echo e($t['sub']) ?><?php if ($go) { ?> · <a href="<?php echo e($t['link']) ?>">ไปตรวจนับ</a><?php } ?></small>
                </div>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
    </section>

    <!-- ===== สต๊อกที่ต้องดูแล ===== -->
    <section class="card">
      <div class="card-head">
        <div>
          <h3>สต๊อกที่ต้องดูแล</h3>
          <p><?php echo e(branch_label($branch)) ?> · มูลค่ารวม <?php echo e(money($sum['value'])) ?> บาท</p>
        </div>
      </div>
      <div class="card-body card-body--flush">
        <?php if (!$lows) { ?>
          <div class="card-body">
            <div class="tasks-empty"><svg class="ico"><use href="#i-check"/></svg> สต๊อกทุกรายการอยู่เหนือจุดสั่งซื้อ</div>
          </div>
        <?php } else { ?>
          <div class="lowlist num">
            <?php foreach ($lows as $r) {
                $pct   = $r['reorder'] > 0 ? min(100, round($r['qty'] / $r['reorder'] * 100)) : 0;
                $isOut = $r['status'] === 'out'; ?>
              <div class="low <?php echo $isOut ? 'is-out' : '' ?>">
                <div class="low-t">
                  <b><?php echo e($r['product']['name']) ?></b>
                  <small>
                    <?php echo e($r['product']['sku']) ?>
                    <?php if ($branch === 'ALL') { ?>· <?php echo e(branch_name($r['branch'])) ?><?php } ?>
                    · จุดสั่งซื้อ <?php echo number_format($r['reorder']) ?> <?php echo e($r['product']['unit']) ?>
                  </small>
                  <div class="low-bar <?php echo $isOut ? 'out' : '' ?>"><i style="width:<?php echo max($pct, 3) ?>%"></i></div>
                </div>
                <div class="low-n">
                  <b><?php echo number_format($r['qty']) ?></b>
                  <small><?php echo e($r['product']['unit']) ?></small>
                </div>
              </div>
            <?php } ?>
          </div>
        <?php } ?>
      </div>
      <div class="card-foot">
        ใกล้หมด <?php echo number_format($sum['low']) ?> รายการ · หมดแล้ว <?php echo number_format($sum['out']) ?> รายการ
      </div>
    </section>

  </div>
</div>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
