<?php
/**
 * FILE: admweb/core/counter/hooks/hooks_admin_main.php
 * ROLE: แสดง counter analytics บน admin dashboard
 * DEPENDS: hooks_function.php, func.counter.php
 * TABLES: site_counter, site_counter_ref
 * TODO:
 *   - [x] ตาราง monthly stats (web + admin)
 *   - [x] ตาราง per-page views
 *   - [x] Peak Hour chart (Highcharts)
 *   - [x] Device split (mobile vs desktop)
 *   - [x] Top Referrers table
 */

$aM  = Arrays_months('th');
$cyc = date('Y');
$cm  = (int)date('m');
// อ่านปีจาก ?year= ผ่าน REQ_get — ไม่ส่งมา/ไม่ถูกต้องให้ fallback เป็นปีปัจจุบัน
$cy  = (int)REQ_get('year', 'request', 'int', $cyc);
?>

<!-- ── Row 1: Monthly stats + Year selector ── -->
<div class="row">
  <div class="col-xs-8">
    <div class="panel">
      <div class="panel-heading">
        <h3 class="panel-title">Counter Page View Chart</h3>
      </div>
      <div class="panel-body" style="min-height:550px;">
        <div id="statsChart"></div>
      </div>
    </div>
  </div>
  <div class="col-xs-4">
    <div class="panel">
      <div class="panel-heading">
        <h3 class="panel-title">สถิติย้อนหลังรายเดือน ปี <?php echo $cy; ?></h3>
      </div>
      <div class="panel-body">
        <div class="mar-btm">
          <?php for ($iy = 2022; $iy <= $cyc; $iy++) { ?>
            <a href="index.php?year=<?php echo $iy; ?>" class="btn btn-<?php echo $iy == $cy ? 'primary' : 'default'; ?> btn-sm"><?php echo $iy; ?></a>
          <?php } ?>
        </div>
        <table class="table table-striped table-condensed">
          <thead>
            <tr>
              <th>เดือน</th>
              <th class="text-center">Admin</th>
              <th class="text-center">Web</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $interSum = 0;
            $intraSum = 0;
            foreach ($aM as $k => $v) {
              $aCounterInter = func_counter_get($k . '-' . $cy, 'web');
              $aCounterIntra = func_counter_get($k . '-' . $cy, 'admin');
              $inter = isset($aCounterInter['month'][0]) ? $aCounterInter['month'][0] : 0;
              $intra = isset($aCounterIntra['month'][0]) ? $aCounterIntra['month'][0] : 0;
              $interSum += (int)$inter;
              $intraSum += (int)$intra;
              $inter = $inter > 0 ? number_format($inter) : '<span style="color:#ddd">0</span>';
              $intra = $intra > 0 ? number_format($intra) : '<span style="color:#ddd">0</span>';
            ?>
              <tr>
                <td><?php echo $v . ' ' . $cy; ?></td>
                <td class="text-center"><?php echo $intra; ?></td>
                <td class="text-center"><?php echo $inter; ?></td>
              </tr>
            <?php } ?>
            <tr class="active">
              <td><strong>รวม</strong></td>
              <td class="text-center"><strong><?php echo number_format($intraSum); ?></strong></td>
              <td class="text-center"><strong><?php echo number_format($interSum); ?></strong></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ── Row 2: Peak Hour + Device Split ── -->
<div class="row">
  <div class="col-xs-8">
    <div class="panel">
      <div class="panel-heading">
        <h3 class="panel-title">
          Peak Hour — ชั่วโมงที่มีผู้เข้าชมมากสุด
          <small class="text-muted"> (เดือนนี้)</small>
        </h3>
      </div>
      <div class="panel-body" style="min-height:280px;">
        <div id="hourChart"></div>
      </div>
    </div>
  </div>
  <div class="col-xs-4">
    <div class="panel">
      <div class="panel-heading">
        <h3 class="panel-title">Device Split <small class="text-muted">(เดือนนี้)</small></h3>
      </div>
      <div class="panel-body">
        <?php
        $aDevices = func_counter_get_devices($cy, $cm, 'web');
        $devTotal = array_sum($aDevices);
        foreach ([
          'desktop' => ['fa-desktop',    'text-info',    'panel-info'],
          'mobile'  => ['fa-mobile-alt', 'text-success', 'panel-mint'],
        ] as $key => [$icon, $textCls, $panelCls]) {
          $count = $aDevices[$key] ?? 0;
          $pct   = $devTotal > 0 ? round($count / $devTotal * 100) : 0;
        ?>
          <div class="panel <?php echo $panelCls; ?> panel-colorful mar-btm">
            <div class="pad-all">
              <i class="fa <?php echo $icon; ?> fa-2x pull-left mar-rgt"></i>
              <p class="text-2x text-semibold mar-no"><?php echo number_format($count); ?></p>
              <p class="mar-no"><?php echo ucfirst($key); ?> — <?php echo $pct; ?>%</p>
            </div>
            <div style="background:rgba(255,255,255,.2);height:4px;">
              <div style="background:#fff;height:4px;width:<?php echo $pct; ?>%"></div>
            </div>
          </div>
        <?php } ?>
        <?php if ($devTotal == 0): ?>
          <p class="text-muted text-center">ยังไม่มีข้อมูล</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── Row 3: Per-page views ── -->
<div class="row">
  <div class="col-xs-12">
    <div class="panel">
      <div class="panel-heading">
        <h3 class="panel-title">จำนวนการเปิดดูในแต่ละหน้า</h3>
      </div>
      <div class="panel-body">
        <div class="table-responsive">
          <table class="table table-striped table-condensed">
            <thead>
              <tr>
                <th>Pages</th>
                <?php foreach ($aM as $k => $v) { ?><th class="text-center"><?php echo $v; ?></th><?php } ?>
                <th class="text-center">รวม</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $year   = $cy;
              $aPages = func_counter_page_get($year);
              foreach ($aPages as $LoopPageName => $vPages) {
                $rowTotal = array_sum($vPages[$year] ?? []);
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($LoopPageName); ?></td>
                  <?php
                  foreach ($aM as $k => $v) {
                    $n = (int)($vPages[$year][$k + 0] ?? 0);
                  ?>
                    <td class="text-center"><?php echo $n > 0 ? number_format($n) : '<span style="color:#ddd">0</span>'; ?></td>
                  <?php } ?>
                  <td class="text-center"><strong><?php echo number_format($rowTotal); ?></strong></td>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── Row 4: Top Referrers ── -->
<div class="row">
  <div class="col-xs-12">
    <div class="panel">
      <div class="panel-heading">
        <h3 class="panel-title">
          Top Referrer Domains
          <small class="text-muted"> — แหล่งที่มาของ traffic (ปี <?php echo $cy; ?>)</small>
        </h3>
      </div>
      <div class="panel-body">
        <?php
        $aRefs    = func_counter_get_top_refs($cy, 0, 15);
        $refTotal = array_sum(array_column($aRefs, 'total'));
        ?>
        <?php if (count($aRefs) > 0): ?>
          <table class="table table-striped table-condensed">
            <thead>
              <tr>
                <th>#</th>
                <th>Domain</th>
                <th class="text-center">ยอดรวม</th>
                <th>สัดส่วน</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($aRefs as $i => $ref): ?>
                <?php $pct = $refTotal > 0 ? round($ref['total'] / $refTotal * 100) : 0; ?>
                <tr>
                  <td class="text-muted"><?php echo $i + 1; ?></td>
                  <td><?php echo htmlspecialchars($ref['ref_domain']); ?></td>
                  <td class="text-center"><?php echo number_format($ref['total']); ?></td>
                  <td style="min-width:120px;">
                    <div style="background:#eee;border-radius:3px;height:8px;overflow:hidden;">
                      <div style="background:#0984e3;height:8px;width:<?php echo $pct; ?>%"></div>
                    </div>
                    <small class="text-muted"><?php echo $pct; ?>%</small>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted text-center pad-ver">ยังไม่มีข้อมูล referrer — ข้อมูลจะปรากฏหลังติดตั้งตาราง site_counter_ref</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── Hour Chart JS (Highcharts ถูกโหลดแล้วจาก dashboard.php) ── -->
<?php
$aHours     = func_counter_get_hours($cy, $cm, 'web');
$hoursJson  = json_encode(array_values($aHours));
$hoursMax   = max($aHours) ?: 1;
?>
<script>
(function() {
  if (typeof Highcharts === 'undefined') return;
  var hours = <?php echo $hoursJson; ?>;
  var cats  = [];
  for (var i = 0; i < 24; i++) { cats.push(i + ':00'); }

  Highcharts.chart('hourChart', {
    chart: { type: 'column', height: 260 },
    title: { text: null },
    credits: { enabled: false },
    legend: { enabled: false },
    xAxis: { categories: cats, labels: { style: { fontSize: '10px' } } },
    yAxis: { title: { text: 'ยอดผู้เข้าชม' }, allowDecimals: false },
    tooltip: { formatter: function() { return this.x + '<br><b>' + this.y + ' ครั้ง</b>'; } },
    series: [{
      name: 'ยอดผู้เข้าชม',
      data: hours,
      color: '#0984e3',
      zones: [{ value: <?php echo $hoursMax * 0.7; ?>, color: '#74b9ff' }, { color: '#0984e3' }]
    }]
  });
})();
</script>
