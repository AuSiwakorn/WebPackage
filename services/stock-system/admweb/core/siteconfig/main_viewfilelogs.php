<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด View File Logs ได้', 'redirect', 'SET');
$fname = PATH_UPLOAD . "/logs/logs.txt";
if (file_exists($fname)) {
    $data = file($fname);
    $data = array_slice($data, -200);
    file_put_contents($fname, implode('', $data));
}
if (_AC_ == "delete") {
    PERMIT::_PERMIT(_MODULE_, 'module|mp|ac', 'สามารถลบ View File Logs ได้', 'redirect', '');
    $oUser = login_logout::getLoginData();
    $fileLog = PATH_UPLOAD . "/logs/logs.txt";
    if (file_exists($fileLog)) {
        unlink($fileLog);
        Func_Addlogs('Clear Logs'); //ตัวอย่าง Addlogs
    }

    setRaiseMsg('Data deletion is complete. please wait.', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
    exit;
}

?>
<div id="page-head">
    <div id="page-title">
        <h1 class="page-header text-overflow">Logs การทำงานของระบบ</h1>
    </div>
</div>

<div id="page-content">
    <div class="row">
        <?php displayRaiseMsg(); ?>
        <div class="col-sm-12">
            <div class="panel">

                <!--===================================================-->
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12 toolbar-right text-right padding-bottom: 10px;">
                            <a class="btn btn-danger" href="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&ac=delete'); ?>" onclick="return confirm('Delete All?');">Clear All Logs</a>
                        </div>
                        <div class="col-md-12"><iframe src="<?php echo URL_UPLOAD . "/logs/logs.txt?time=" . _TIME_; ?>" style="min-height: 500px;" width="100%" title="Iframe Example"></iframe></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>