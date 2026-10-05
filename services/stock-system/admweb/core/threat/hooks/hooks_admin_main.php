<?php
$m      = date('m');
$year   = REQ_get('year', 'request', 'str', date('Y'));
$aMonth = Arrays_months('th');
$config = [
    'sqli'              => ['icon' => 'fa-bug',         'title' => 'SQL Injection', 'class' => 'panel-purple'],
    'xss'               => ['icon' => 'fa-code',        'title' => 'XSS Attack', 'class' => 'panel-primary'],
    'lfi_rfi_traversal' => ['icon' => 'fa-folder-open', 'title' => 'Path Traversal', 'class' => 'panel-info'],
    'rce'               => ['icon' => 'fa-bomb',        'title' => 'Command (RCE)', 'class' => 'panel-dark'],
    'bad_bot'           => ['icon' => 'fa-user-secret', 'title' => 'Scanner Bots', 'class' => 'panel-warning'],
    'malicious_file'    => ['icon' => 'fa-crosshairs',  'title' => 'Malicious File', 'class' => 'panel-pink']
];
$count = [];
$aThreats = DB_THREAT($year);
if (!empty($aThreats['data'])) {
    foreach ($aThreats['data'] as $row) $count[$row['name']] = $row['amount'];
}
$summary = [];
$aReport = DB_THREAT_REPORT($year);
if (!empty($aReport['data'])) {
    foreach ($aReport['data'] as $row) {
        $summary[$row['name']][$row['month_num']] = $row['amount'];
    }
}
?>
<div class="row">
    <div class="pad-btm text-center">
        <?php for ($i = date('Y') + 1; $i > 2025; $i--) { ?>
            <a class="btn <?php echo $year == $i ? 'btn-purple ' : 'btn-default'; ?>" href="<?php echo _admin_buil_link('index.php?&year=' . $i, true); ?>"><?php echo $i; ?></a>
        <?php } ?>
    </div>
    <?php foreach ($config as $k => $v) {
        $amount = isset($count[$k]) ? $count[$k] : 0; ?>
        <div class="col-md-3 col-lg-2">
            <div class="panel <?php echo $v['class']; ?> panel-colorful media middle pad-all">
                <div class="media-left">
                    <div class="pad-hor"><i class="fa <?php echo $v['icon']; ?>" style="font-size: 3em; line-height: 1em;"></i></div>
                </div>
                <div class="media-body">
                    <p class="text-2x mar-no text-semibold"><?php echo number_format($amount); ?></p>
                    <p class="mar-no text-uppercase text-sm"><?php echo $v['title']; ?><br>จำนวนป้องกัน<?php echo GlobalConfig_get('threat_status') != 1 ? '<br>จำกัด 10 ครั้งต่อวัน' : '';  ?></p>
                </div>
            </div>
        </div>
    <?php } ?>
    <div class="col-md-12 col-lg-12">
        <div class="panel">
            <div class="panel-heading">
                <h3 class="panel-title">จำนวนการป้องกันในแต่ละเดือน</h3>
            </div>
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-vcenter table-striped">
                        <thead>
                            <tr>
                                <th class="text-left">ประเภทการป้องกัน</th>
                                <?php foreach ($aMonth as $monthNum => $monthName) {
                                    $current = $m == $monthNum ? 'background-color: rgb(0, 207, 104);' : ''; ?>
                                    <th class="text-center" style="<?php echo $current; ?>"><?php echo $monthName; ?></th>
                                <?php } ?>
                                <th class="text-center">รวมทั้งปี</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $grandTotal = 0;
                            foreach ($config as $threatKey => $details) {
                                $rowTotal = 0; ?>
                                <tr>
                                    <td><strong><?php echo $details['title']; ?></strong></td>
                                    <?php foreach ($aMonth as $monthNum => $monthName) {
                                        $amount = isset($summary[$threatKey][$monthNum]) ? $summary[$threatKey][$monthNum] : 0;
                                        $rowTotal += $amount;
                                        $textClass = $amount > 0 ? 'text-success text-bold' : 'text-muted';
                                        $current   = $m == $monthNum ? 'background-color: #ffe38e;' : ''; ?>
                                        <td class="text-center" style="<?php echo $current; ?>"><span class="<?php echo $textClass; ?>"><?php echo number_format($amount); ?></span></td>
                                    <?php } ?>
                                    <td class="text-center"><strong><?php echo number_format($rowTotal); ?></strong></td>
                                </tr>
                            <?php $grandTotal += $rowTotal;
                            } ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="13" class="text-right"><strong>ยอดรวมการป้องกันทั้งหมดในปี <?php echo $year; ?></strong></td>
                                <td class="text-center"><strong><?php echo number_format($grandTotal); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>