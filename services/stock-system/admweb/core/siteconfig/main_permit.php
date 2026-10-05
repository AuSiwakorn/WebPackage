<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Permission ได้', 'redirect', 'UNSET');
$fnameReq = PATH_UPLOAD . '/permission/req.txt';
$fnameChack = file_get_contents(PATH_AOWEBDATA . '/permission/default.php');
if (_AC_ == 'del') {
    unlink($fnameReq);
    setRaiseMsg('Clear permission successfully.', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
    exit;
}
$txt = 'ไม่มีข้อมูลที่ต้องการให้เพิ่ม';
if (is_file($fnameReq) && file_exists($fnameReq)) {
    $txt = builStringJson(read_txt_json_a($fnameReq), $fnameChack, '');
    if ($txt === '') {
        unlink($fnameReq);
        $txt = 'ไม่มีข้อมูลที่ต้องการให้เพิ่ม';
    }
}
?>
<div id="page-head">
    <div id="page-title">
        <h1 class="page-header text-overflow">Permission</h1>
    </div>
</div>
<div id="page-content">
    <?php displayRaiseMsg(); ?>
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel">
                <div class="panel-body">
                    <a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&ac=del"); ?>" class="btn btn-danger">ล้างข้อมูลส่วนนี้</a>
                    <br><br>
                    <?php pre(htmlspecialchars($txt)); ?>
                    <a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&ac=del"); ?>" class="btn btn-danger">ล้างข้อมูลส่วนนี้</a>
                </div>
            </div>
        </div>
    </div>
</div>