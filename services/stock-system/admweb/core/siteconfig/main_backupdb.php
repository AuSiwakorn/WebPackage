<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด backdb ได้', 'redirect', 'SET');
$ac            = REQ_get('ac', 'requset', 'str', '');
if ($ac == 'bk') {
    $bkAction = REQ_get('backup_action', 'requset', 'str', 'local');
    if ($bkAction === 'server') {
        $result = BACKUP_Process($bkAction);
        if ($result) {
            setRaiseMsg('สำรองฐานข้อมูลเรียบร้อยแล้ว.', _TIME_, 0);
            CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
        } else {
            setRaiseMsg('เกิดข้อผิดพลาดในการสำรองฐานข้อมูล', _TIME_, 1);
            CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
        }
    } else {
        $backupName = $bkAction;
        $zipResult = BACKUP_CreateZIP($backupName);
        if (!$zipResult) {
            setRaiseMsg('เกิดข้อผิดพลาดในการสร้างไฟล์ ZIP', _TIME_, 1);
            CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
        }
    }
    exit;
}
if ($ac == 'bk2') {
    $bkAction = REQ_get('backup_action2', 'requset', 'str', 'local');
    if ($bkAction != '') {
        $zipResult = BACKUP_CreateZIP2($bkAction);
        if (!$zipResult) {
            setRaiseMsg('เกิดข้อผิดพลาดในการสร้างไฟล์ ZIP', _TIME_, 1);
            CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
        }
    }
    exit;
}
if ($ac == 'dl') {
    $fileName = REQ_get('backup_action', 'requset', 'str', '');
    if (strlen($fileName) != 10 || $fileName == '') {
        CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
        exit;
    }

    if ($fileName) {
        BACKUP_DeleteFile($fileName);
    }
    setRaiseMsg('ลบฐานข้อมูลสำรอง ' . $fileName . ' แล้ว', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
}
if ($ac == 'dl2') {
    $fileName = REQ_get('backup_action2', 'requset', 'str', '');
    if ($fileName != '') {
        BACKUP_DeleteFile2(strtolower($fileName));
    }
    setRaiseMsg('ลบฐานข้อมูลสำรอง ' . $fileName . ' แล้ว', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
    exit;
}
$a = dirList(PATH_UPLOAD . '/backdb');
$bData = [];
foreach ($a as $value) {
    $bData[] = [
        'name' => $value['name'],
        'type' => $value['type'],
        'time' => $value['time'],
        'size' => disk_used_space2(PATH_UPLOAD . '/backdb/' . $value['name'])
    ];
}
usort($bData, function ($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

$b = dirList(PATH_UPLOAD . '/autobackup');
$cData = [];
foreach ($b as $value) {
    $cData[] = [
        'name' => ucfirst($value['name']),
        'type' => $value['type'],
        'time' => $value['time'],
        'size' => disk_used_space2(PATH_UPLOAD . '/autobackup/' . $value['name'])
    ];
}
usort($cData, function ($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
?>
<div id="page-head">
    <div id="page-title">
        <div class="row">
            <div class="col-md-6">
                <h1 class="page-header text-overflow">Backup Database</h1>
            </div>
            <div class="col-md-6 text-right">
                <form method="post" action="">
                    <input name="ac" value="bk" type="hidden" />
                    <button class="btn btn-info" name="backup_action" value="server" type="submit" onclick="return confirm('ต้องการสำรองฐานข้อมูล แบบสำรองในเซิร์ฟเวอร์ ?');">
                        CREATE BACKUP
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="page-content">
    <div class="panel">
        <div class="panel-heading">
            <h3 class="panel-title text-main text-bold">Gennerate Backup Database File</h3>
        </div>
        <div class="panel-body">
            <?php displayRaiseMsg(); ?>

            <div class="row">
                <div class="col-xs-12">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th class="text-center" width="80">TYPE</th>
                                <th class="text-center" width="100">HASH Time</th>
                                <th class="text-left" width="250">Download File</th>
                                <th class="text-left">Timestamp</th>
                                <th class="text-right" width="100"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($bData)) {
                                $i = 0;
                                foreach ($bData as $log) {
                                    $i++;
                            ?>
                                    <tr>
                                        <td class="text-center"><?php echo strtoupper($log['type']); ?></td>
                                        <td class="text-center"><?php echo $log['name']; ?></td>
                                        <td class="text-left">
                                            <form method="POST">
                                                <input name="ac" value="bk" type="hidden" />
                                                <button class="btn btn-success" name="backup_action" value="<?php echo htmlspecialchars($log['name']); ?>" type="submit">DOWNLOAD ZIP &nbsp; ( <?php echo displaySize($log['size']); ?> )</button>
                                            </form>
                                        </td>
                                        <td class="text-left"><?php echo $log['time']; ?></td>
                                        <td class="text-right">
                                            <form method="POST">
                                                <input name="ac" value="dl" type="hidden" />
                                                <input name="backup_action" value="<?php echo htmlspecialchars($log['name']); ?>" type="hidden" />
                                                <button class="btn btn-danger" type="submit" onclick="return confirm('คุณต้องการลบไฟล์สำรองข้อมูลนี้ใช่หรือไม่?');">DELETE</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php
                                }
                            } else {
                                ?>
                                <tr height="50">
                                    <td colspan="6" align="center">There is no backup log information in the system.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="panel">
        <div class="panel-heading">
            <h3 class="panel-title text-main text-bold">Auto Backup Database</h3>
        </div>
        <div class="panel-body">
            <?php displayRaiseMsg(); ?>
            <div class="row">
                <div class="col-xs-12">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th class="text-center" width="80">TYPE</th>
                                <th class="text-center" width="100">HASH Time</th>
                                <th class="text-left" width="250">Download File</th>
                                <th class="text-left">Timestamp</th>
                                <th class="text-right" width="100"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($cData)) {
                                $i = 0;
                                foreach ($cData as $log) {
                                    $i++;
                            ?>
                                    <tr>
                                        <td class="text-center"><?php echo strtoupper($log['type']); ?></td>
                                        <td class="text-center"><?php echo $log['name']; ?></td>
                                        <td class="text-left">
                                            <form method="POST">
                                                <input name="ac" value="bk2" type="hidden" />
                                                <button class="btn btn-success" name="backup_action2" value="<?php echo htmlspecialchars($log['name']); ?>" type="submit">DOWNLOAD ZIP &nbsp; ( <?php echo displaySize($log['size']); ?> )</button>
                                            </form>
                                        </td>
                                        <td class="text-left"><?php echo $log['time']; ?></td>
                                        <td class="text-right">
                                            <form method="POST">
                                                <input name="ac" value="dl2" type="hidden" />
                                                <input name="backup_action2" value="<?php echo htmlspecialchars($log['name']); ?>" type="hidden" />
                                                <button class="btn btn-danger" type="submit" onclick="return confirm('คุณต้องการลบไฟล์สำรองข้อมูลนี้ใช่หรือไม่?');">DELETE</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php
                                }
                            } else {
                                ?>
                                <tr height="50">
                                    <td colspan="6" align="center">There is no backup log information in the system.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>