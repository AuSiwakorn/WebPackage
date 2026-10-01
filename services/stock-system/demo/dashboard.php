<?php
require_once dirname(__FILE__) . '/include/function.php';

$user   = require_login();                // ฝ่ายบัญชีถูกพาไปหน้า account.php เอง

/* ผู้ดูแล: เปิดร้านใหม่หลังปิด (ทำจากภาพรวม — ผู้ดูแลไม่เข้าหน้าเปิด/ปิดร้านแล้ว) */
if ($user['role'] === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'reopen') {
    $rb = isset($_POST['b']) ? $_POST['b'] : '';
    $rr = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $bb = demo_branches();
    if (!csrf_check(isset($_POST['csrf']) ? $_POST['csrf'] : null) || !isset($bb[$rb])) {
        $_SESSION['flash'] = 'เซสชันหมดอายุ หรือไม่พบสาขา';
    } elseif ($rr === '') {
        $_SESSION['flash'] = 'การเปิดร้านใหม่หลังปิดต้องระบุเหตุผล';
    } elseif (store_reopen($rb, $user, $rr) === null) {
        $_SESSION['flash'] = branch_name($rb) . ' ยังไม่ได้ปิดร้าน';
    } else {
        $_SESSION['flash'] = 'เปิดร้าน' . branch_name($rb) . 'ใหม่แล้ว — พนักงานขายต่อได้ และต้องปิดร้านอีกครั้ง';
    }
    header('Location: ' . url('dashboard.php?branch=ALL'));
    exit;
}

/* พนักงานหน้างานต้องเปิดร้านก่อนใช้งานในแต่ละวัน */
if ($user['role'] !== 'admin' && can($user, 'sale') && store_state($user['branch']) === null) {
    header('Location: ' . url('store.php'));
    exit;
}
$branch = resolve_branch($user, isset($_GET['branch']) ? $_GET['branch'] : null);
if ($user['role'] === 'admin' && $branch !== 'ALL') {
    $_SESSION['admin_branch'] = $branch;          // หน้าอื่นทำงานกับสาขาเดียวกันต่อ
}
$me     = $user['username'];

/* ---------- ผลงานของฉัน ---------- */
$today     = staff_day_stat($me, time());
$yesterday = staff_day_stat($me, strtotime('-1 day'));
$week      = staff_recent_days($me, 7);
$weekSum   = staff_sum($week);
$monthDays = staff_month_days($me);
$monthSum  = staff_sum($monthDays);
$goal      = staff_goal($me);
$goalPct   = $goal > 0 ? min(100, (int) round($today['qty'] / $goal * 100)) : 0;
$types     = staff_work_types($me);
$tops      = staff_top_products($me);
$rank      = branch_rank_today(work_branch($user));
$maxWeek   = 1;
foreach ($week as $w) {
    if ($w['qty'] > $maxWeek) { $maxWeek = $w['qty']; }
}

/* ---------- ข้อมูลสต๊อกที่ต้องดูแล ---------- */
$sum   = stock_summary($branch);
$lows  = low_stock_products($branch, 4);
$tasks = pending_tasks($branch);

$hour  = (int) date('G');
$greet = $hour < 12 ? 'สวัสดีตอนเช้า' : ($hour < 17 ? 'สวัสดีตอนบ่าย' : 'สวัสดีตอนเย็น');
$first = explode(' ', $user['name']);
$first = $first[0];

$PAGE_TITLE = ($user['role'] === 'admin') ? 'ภาพรวมผู้ดูแล' : 'ภาพรวมของฉัน';
$PAGE_SUB   = branch_label($branch) . ' · ' . thai_date_full(time()) . ' ' . date('H:i') . ' น.';
$NAV_ACTIVE = 'dashboard.php';
require dirname(__FILE__) . '/inc/header.php';

/* ผู้ดูแลเห็นภาพรวมร้าน/รายการที่ต้องตรวจ แทนผลงานส่วนตัวแบบพนักงาน */
if ($user['role'] === 'admin') {
    require dirname(__FILE__) . '/inc/dash-admin.php';
    require dirname(__FILE__) . '/inc/footer.php';
    exit;
}
?>

<div class="demo-bar">
  <svg class="ico"><use href="#i-info"/></svg>
  <span>หน้านี้เป็นตัวอย่างการใช้งาน ข้อมูลทั้งหมดเป็นข้อมูลสมมติและยังไม่เชื่อมต่อฐานข้อมูล</span>
</div>

<div class="grid-2">

  <!-- ==================== คอลัมน์ซ้าย ==================== -->
  <div style="display:grid;gap:20px">

    <!-- ===== การ์ดผลงานของฉันวันนี้ ===== -->
    <section class="hero-me num">
      <div class="hm-hi">
        <span class="av"><?= e($user['initials']) ?></span>
        <span><?= e($greet) ?> <b><?= e($first) ?></b> · <?= e(thai_date_full(time())) ?>
              · <?= e(branch_name($user['branch'])) ?></span>
      </div>
      <?php $myPerms = ($user['role'] === 'admin') ? array() : user_perms($user); ?>
      <?php if ($myPerms): $pl = perm_list(); ?>
        <div class="hm-perms" aria-label="สิทธิ์เสริมของฉัน">
          <span class="hm-perms-lb">สิทธิ์เสริม</span>
          <?php foreach ($myPerms as $pk): if (!isset($pl[$pk]) || $pl[$pk]['group'] !== 'extra') { continue; } ?>
            <span class="hm-perm" title="<?= e($pl[$pk]['label']) ?>"><svg class="ico"><use href="#i-check"/></svg><?= e($pl[$pk]['short']) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($today['off']): ?>
        <div class="hm-lb">วันนี้เป็นวันหยุดของคุณ</div>
        <div class="hm-nm">—</div>
        <div class="hm-sub">ยังไม่มีรายการที่บันทึกในวันนี้</div>
        <div class="hm-off">
          <svg class="ico"><use href="#i-info"/></svg>
          <span>เข้ามาดูภาพรวมสาขาได้ตามปกติ แต่ไม่นับเป็นวันทำงาน</span>
        </div>
      <?php else: ?>
        <div class="hm-lb">ชิ้นที่ฉันจัดการวันนี้</div>
        <div class="hm-nm"><?= number_format($today['qty']) ?><small>ชิ้น</small></div>
        <div class="hm-sub">
          <?= number_format($today['docs']) ?> เอกสาร ·
          <?= number_format($today['items']) ?> รายการ ·
          เฉลี่ย <?= number_format($today['docs'] > 0 ? round($today['qty'] / $today['docs']) : 0) ?> ชิ้น/เอกสาร
        </div>

        <div class="hm-goal<?= $goalPct >= 100 ? ' done' : '' ?>">
          <div class="t">
            <span>เป้าวันนี้ <?= number_format($goal) ?> ชิ้น</span>
            <b><?= $goalPct ?>%</b>
          </div>
          <div class="bar"><i style="width:<?= max($goalPct, 2) ?>%"></i></div>
        </div>
      <?php endif; ?>
    </section>

    <!-- ===== สถิติย่อย ===== -->
    <section class="mini num" aria-label="สรุปผลงานย้อนหลัง">
      <div class="m">
        <div class="lb">เมื่อวาน</div>
        <div class="nm"><?= $yesterday['off'] ? '—' : number_format($yesterday['qty']) ?></div>
        <div class="sb"><?= $yesterday['off'] ? 'วันหยุด' : number_format($yesterday['docs']) . ' เอกสาร' ?></div>
      </div>
      <div class="m">
        <div class="lb">7 วันล่าสุด</div>
        <div class="nm"><?= number_format($weekSum['qty']) ?></div>
        <div class="sb">เฉลี่ย <?= number_format($weekSum['days'] > 0 ? round($weekSum['qty'] / $weekSum['days']) : 0) ?>/วัน</div>
      </div>
      <div class="m">
        <div class="lb">เดือนนี้</div>
        <div class="nm"><?= number_format($monthSum['qty']) ?></div>
        <div class="sb"><?= number_format($monthSum['days']) ?> วันทำงาน</div>
      </div>
    </section>

    <!-- ===== กราฟ 7 วัน ===== -->
    <section class="card">
      <div class="card-head">
        <div>
          <h3>ผลงานของฉัน 7 วันล่าสุด</h3>
          <p>จำนวนชิ้นที่รับเข้า เบิกออก และตรวจนับ</p>
        </div>
      </div>
      <div class="card-body">
        <div class="bars num" role="img" aria-label="กราฟแท่งผลงานรายวันย้อนหลัง 7 วัน">
          <?php foreach ($week as $i => $w):
              $isToday = ($i === count($week) - 1);
              $h = $maxWeek > 0 ? round($w['qty'] / $maxWeek * 100) : 0; ?>
            <div class="b<?= $w['qty'] > 0 ? ' has' : '' ?><?= $isToday ? ' today' : '' ?>">
              <div class="v" style="height:<?= $w['off'] ? 0 : max($h, 4) ?>%">
                <span><?= $w['off'] ? 'หยุด' : number_format($w['qty'] / 1000, 1) . 'k' ?></span>
              </div>
              <div class="l"><?= e(short_day($w['ts'])) ?></div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="sr-only">
          <table>
            <caption>ผลงานรายวัน 7 วันล่าสุด</caption>
            <thead><tr><th>วัน</th><th>ชิ้น</th></tr></thead>
            <tbody>
              <?php foreach ($week as $w): ?>
                <tr><td><?= e(short_day($w['ts'])) ?></td><td><?= $w['off'] ? 'วันหยุด' : number_format($w['qty']) ?></td></tr>
              <?php endforeach; ?>
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
          <p>10 วันล่าสุด จากทั้งหมด <?= number_format(count($monthDays)) ?> วัน</p>
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
              foreach ($show as $d): ?>
                <tr<?= $d['off'] ? ' class="off"' : '' ?>>
                  <td data-label="วันที่"><?= e(short_day($d['ts'])) ?> <?= e(thai_month_short($d['ts'])) ?></td>
                  <?php if ($d['off']): ?>
                    <td class="r" data-label="เอกสาร">—</td>
                    <td class="r" data-label="รายการ">—</td>
                    <td class="r" data-label="ชิ้น">วันหยุด</td>
                  <?php else: ?>
                    <td class="r" data-label="เอกสาร"><?= number_format($d['docs']) ?></td>
                    <td class="r" data-label="รายการ"><?= number_format($d['items']) ?></td>
                    <td class="r" data-label="ชิ้น"><b><?= number_format($d['qty']) ?></b></td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <th>รวม <?= number_format($monthSum['days']) ?> วันทำงาน</th>
                <th class="r"><?= number_format($monthSum['docs']) ?></th>
                <th class="r"><?= number_format($monthSum['items']) ?></th>
                <th class="r"><?= number_format($monthSum['qty']) ?></th>
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
          <p>แยกตามประเภทเอกสาร</p>
        </div>
      </div>
      <div class="card-body">
        <?php if ($today['off']): ?>
          <div class="tasks-empty"><svg class="ico"><use href="#i-info"/></svg> วันนี้เป็นวันหยุดของคุณ</div>
        <?php else: ?>
          <div class="wt num">
            <?php foreach ($types as $t):
                $lbl = movement_type_of($t['type']); ?>
              <div class="wt-row t-<?= e($t['type']) ?>">
                <div class="t">
                  <span><?= e($lbl['label']) ?></span>
                  <b><?= number_format($t['qty']) ?> ชิ้น</b>
                </div>
                <div class="bar"><i style="width:<?= max($t['pct'], 2) ?>%"></i></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- ===== สินค้าที่จัดการบ่อยวันนี้ ===== -->
    <?php if (!$today['off']): ?>
      <section class="card">
        <div class="card-head">
          <div>
            <h3>สินค้าที่ฉันจัดการบ่อย</h3>
            <p>เฉพาะวันนี้</p>
          </div>
        </div>
        <div class="card-body">
          <div class="kv num">
            <?php foreach ($tops as $t): ?>
              <div>
                <span>
                  <?= e($t['product']['name']) ?>
                  <small><?= e($t['product']['sku']) ?></small>
                </span>
                <b><?= number_format($t['qty']) ?> <?= e($t['product']['unit']) ?></b>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <!-- ===== อันดับในสาขาวันนี้ ===== -->
    <section class="card">
      <div class="card-head">
        <div>
          <h3>ทั้งสาขาวันนี้</h3>
          <p><?= e(branch_name($user['branch'])) ?></p>
        </div>
        <?php
        $branchTotal = 0;
        foreach ($rank as $r) { $branchTotal += $r['qty']; }
        ?>
        <span class="card-sum num" style="font-size:.84rem;color:var(--ink-3);white-space:nowrap">
          รวม <?= number_format($branchTotal) ?> ชิ้น
        </span>
      </div>
      <div class="card-body card-body--flush">
        <?php $no = 0; foreach ($rank as $r): $no++; ?>
          <div class="rk num<?= $r['username'] === $me ? ' me' : '' ?><?= $r['off'] ? ' off' : '' ?>">
            <span class="n"><?= $r['off'] ? '–' : $no ?></span>
            <span class="nm">
              <b><?= e($r['name']) ?></b>
              <?php if ($r['username'] === $me): ?><small>ฉัน</small><?php endif; ?>
            </span>
            <span class="a"><?= $r['off'] ? 'วันหยุด' : number_format($r['qty']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="card-foot">แสดงเฉพาะจำนวนงาน ไม่แสดงต้นทุนหรือมูลค่า · เห็นเฉพาะสาขาของตัวเอง</div>
    </section>

    <!-- ===== งานที่รอดำเนินการ ===== -->
    <section class="card">
      <div class="card-head">
        <div>
          <h3>งานที่รอดำเนินการ</h3>
          <p>เอกสารที่ยังไม่ปิดของ<?= e(branch_label($branch)) ?></p>
        </div>
      </div>
      <div class="card-body">
        <?php if (!$tasks): ?>
          <div class="tasks-empty"><svg class="ico"><use href="#i-check"/></svg> ไม่มีเอกสารค้างอยู่</div>
        <?php else: ?>
          <div class="tasks">
            <?php foreach ($tasks as $t):
                $ty = movement_type_of($t['type']);
                $st = movement_status_of($t['status']); ?>
              <div class="task">
                <span class="ti"><svg class="ico"><use href="#i-alert"/></svg></span>
                <div>
                  <b><?= e($ty['label']) ?> <?= e($t['doc']) ?> — <?= e($st['label']) ?></b>
                  <small>
                    <?= e(branch_name($t['branch'])) ?><?= $t['to'] ? ' → ' . e(branch_name($t['to'])) : '' ?>
                    · <?= number_format($t['items']) ?> รายการ · <?= e($t['time']) ?>
                  </small>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- ===== สต๊อกที่ต้องดูแล ===== -->
    <section class="card">
      <div class="card-head">
        <div>
          <h3>สต๊อกที่ต้องดูแล</h3>
          <p><?= e(branch_label($branch)) ?> · มูลค่ารวม <?= e(money($sum['value'])) ?> บาท</p>
        </div>
      </div>
      <div class="card-body card-body--flush">
        <?php if (!$lows): ?>
          <div class="card-body">
            <div class="tasks-empty"><svg class="ico"><use href="#i-check"/></svg> สต๊อกทุกรายการอยู่เหนือจุดสั่งซื้อ</div>
          </div>
        <?php else: ?>
          <div class="lowlist num">
            <?php foreach ($lows as $r):
                $pct   = $r['reorder'] > 0 ? min(100, round($r['qty'] / $r['reorder'] * 100)) : 0;
                $isOut = $r['status'] === 'out'; ?>
              <div class="low <?= $isOut ? 'is-out' : '' ?>">
                <div class="low-t">
                  <b><?= e($r['product']['name']) ?></b>
                  <small>
                    <?= e($r['product']['sku']) ?>
                    <?php if ($branch === 'ALL'): ?>· <?= e(branch_name($r['branch'])) ?><?php endif; ?>
                    · จุดสั่งซื้อ <?= number_format($r['reorder']) ?> <?= e($r['product']['unit']) ?>
                  </small>
                  <div class="low-bar <?= $isOut ? 'out' : '' ?>"><i style="width:<?= max($pct, 3) ?>%"></i></div>
                </div>
                <div class="low-n">
                  <b><?= number_format($r['qty']) ?></b>
                  <small><?= e($r['product']['unit']) ?></small>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="card-foot">
        ใกล้หมด <?= number_format($sum['low']) ?> รายการ · หมดแล้ว <?= number_format($sum['out']) ?> รายการ
      </div>
    </section>

    <!-- ===== แยกตามสาขา (เฉพาะผู้ดูแล) ===== -->
    <?php if ($user['role'] === 'admin'): ?>
      <section class="card">
        <div class="card-head">
          <div>
            <h3>แยกตามสาขา</h3>
            <p>มูลค่าสต๊อกและรายการที่ต้องดูแล</p>
          </div>
        </div>
        <div class="card-body card-body--flush">
          <div class="tbl-wrap">
            <table class="tbl tbl--compact num">
              <thead><tr><th>สาขา</th><th class="r">มูลค่าสต๊อก</th><th class="r">ใกล้หมด</th><th class="r">หมด</th></tr></thead>
              <tbody>
                <?php foreach (demo_branches() as $code => $b):
                    $s = stock_summary($code); ?>
                  <tr>
                    <td><?= e($b['name']) ?></td>
                    <td class="r" data-label="มูลค่าสต๊อก"><?= e(money($s['value'])) ?></td>
                    <td class="r" data-label="ใกล้หมด"><?= $s['low'] ? '<span class="bdg bdg-warn">' . number_format($s['low']) . '</span>' : '—' ?></td>
                    <td class="r" data-label="หมด"><?= $s['out'] ? '<span class="bdg bdg-out">' . number_format($s['out']) . '</span>' : '—' ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    <?php endif; ?>
  </div>
</div>

<?php require dirname(__FILE__) . '/inc/footer.php'; ?>
