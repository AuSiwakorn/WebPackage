<?php
$ac = REQ_get('ac', 'request', 'str', '');
$aBrowser = REQ_get('aBrowser', 'request', 'array', '');
$aOS = REQ_get('aOS', 'request', 'array', '');
$aDevice = REQ_get('aDevice', 'request', 'array', '');
$aTime = REQ_get('aTime', 'request', 'array', '');
$aIP = REQ_get('aIP', 'request', 'str', '');
$fnameBrowser = PATH_UPLOAD . '/config/browser.txt';
$fnameOS = PATH_UPLOAD . '/config/os.txt';
$fnameDevice = PATH_UPLOAD . '/config/device.txt';
$fnameTime = PATH_UPLOAD . '/config/time.txt';
$fnameIP = PATH_UPLOAD . '/config/ip.txt';
$rBrowser = read_txt_json($fnameBrowser);
$rOS = read_txt_json($fnameOS);
$rDevice = read_txt_json($fnameDevice);
$rTime = read_txt_json($fnameTime);
$rIP = read_txt_json($fnameIP);
$userBrowser = $_aDetectConfig['Browser'][UserInfo::get_browser()];
$userOS = $_aDetectConfig['OS'][UserInfo::get_os()];
$userDevice = $_aDetectConfig['Device'][UserInfo::get_device()];
$userTime = date('H:i', _TIME_);
$userIP = UserInfo::get_ip();
if ($ac == 'add') {
    PERMIT::_PERMIT(_MODULE_, 'module|mp|ac', 'สามารถบันทึก Detect Config ได้', 'redirect');
    _getConfig($aBrowser, $fnameBrowser);
    _getConfig($aOS, $fnameOS);
    _getConfig($aDevice, $fnameDevice);
    _getConfig($aTime, $fnameTime);
    _getConfigIP($aIP, $userIP, $fnameIP);
    $isBrowser = !empty($aBrowser) ? 'Browser' : 'Browser_All';
    $isOS = !empty($aOS) ? 'OS' : 'OS_All';
    $isDevice = !empty($aDevice) ? 'Device' : 'Device_All';
    $isIP = !empty($aIP) ? 'IP' : 'IP_All';
    $isTime = !empty($aTime) ? 'Time' : 'Time_All';
    Func_Addlogs("[Detect] Set DetectConfig {$isBrowser} {$isOS} {$isDevice} {$isIP} {$isTime}");
    setRaiseMsg('Add data is successfully.', _TIME_, 0);
    CustomRedirectToUrl('index.php?module=siteconfig&mp=detect');
    exit;
}
?>
<form method="post" name="form1" id="form1">
    <input type="hidden" name="ac" value="add">
    <div id="page-head">
        <div id="page-title">
            <h1 class="page-header text-overflow col-md-8">Detect Config</h1>
            <div class="col-md-4 text-right"><button class="btn btn-mint submit" style="font-size: 24px; font-family: db_ozone_xregular; " type="submit"> <i class="fa fa-save" style="font-size: 18px;"></i> Save</button></div>
        </div>
    </div>
    <div id="page-content">
        <?php echo displayRaiseMsg(); ?>
        <div class="fixed-fluid">
            <div class="fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="tab-base">
                            <ul class="nav nav-tabs">
                                <li class="text-bold active"><a data-toggle="tab" href="#demo-lft-tab-browser" aria-expanded="true">Browser</a></li>
                                <li class="text-bold"><a data-toggle="tab" href="#demo-lft-tab-os" aria-expanded="false">OS</a></li>
                                <li class="text-bold"><a data-toggle="tab" href="#demo-lft-tab-device" aria-expanded="false">Device</a></li>
                                <li class="text-bold"><a data-toggle="tab" href="#demo-lft-tab-time" aria-expanded="false">Time</a></li>
                                <li class="text-bold"><a data-toggle="tab" href="#demo-lft-tab-ip" aria-expanded="false">IP</a></li>
                            </ul>
                            <div class="tab-content">
                                <div id="demo-lft-tab-browser" class="tab-pane fade active in">
                                    <div class="form-group">
                                        <div class="text-main text-bold">ขณะนี้ผู้ใช้งานกำลังใช้เบราว์เซอร์ <?php echo $userBrowser; ?> อยู่</div>
                                        <div class="text-main text-bold">ตรวจสอบ Browser เพื่อเข้าใช้งาน อนุญาตให้เข้าใช้งานเฉพาะ</div>
                                        <?php foreach ($_aDetectConfig['Browser'] as $k => $v) {
                                            $md5 = md5($k);
                                            $se = (!empty($rBrowser) && array_key_exists($md5, $rBrowser)) ? 'checked' : ''; ?>
                                            <div class="form-group checkbox">
                                                <input <?php echo $se; ?> class="magic-checkbox" id="checkConfig-<?php echo $md5; ?>" type="checkbox" name="aBrowser[<?php echo $md5; ?>]" value="<?php echo md5($v); ?>">
                                                <label for="checkConfig-<?php echo $md5; ?>"><?php echo $v; ?></label>
                                            </div>
                                        <?php }
                                        if (!file_exists($fnameBrowser)) { ?><div class="text-main text-bold">ขณะนี้ผู้ใช้งานกำลังใช้งานทุกเบราว์เซอร์อยู่</div><?php } ?>
                                    </div>
                                </div>
                                <div id="demo-lft-tab-os" class="tab-pane fade">
                                    <div class="form-group">
                                        <div class="text-main text-bold">ขณะนี้ผู้ใช้งานกำลังใช้ระบบปฏิบัติการ <?php echo $userOS; ?> อยู่</div>
                                        <div class="text-main text-bold">ตรวจสอบ OS เพื่อเข้าใช้งาน อนุญาตให้เข้าใช้งานเฉพาะ</div>
                                        <?php foreach ($_aDetectConfig['OS'] as $k => $v) {
                                            $md5 = md5($k);
                                            $se = (!empty($rOS) && array_key_exists($md5, $rOS)) ? 'checked' : ''; ?>
                                            <div class="form-group checkbox">
                                                <input <?php echo $se; ?> class="magic-checkbox" id="checkConfig-<?php echo $md5; ?>" type="checkbox" name="aOS[<?php echo $md5; ?>]" value="<?php echo md5($v); ?>">
                                                <label for="checkConfig-<?php echo $md5; ?>"><?php echo $v; ?></label>
                                            </div>
                                        <?php }
                                        if (!file_exists($fnameOS)) { ?><div class="text-main text-bold">ขณะนี้ผู้ใช้งานกำลังใช้งานทุกระบบปฏิบัติการอยู่</div><?php } ?>
                                    </div>
                                </div>
                                <div id="demo-lft-tab-device" class="tab-pane fade">
                                    <div class="form-group">
                                        <div class="text-main text-bold">ขณะนี้ผู้ใช้งานกำลังใช้อุปกรณ์ <?php echo $userDevice; ?> อยู่</div>
                                        <div class="text-main text-bold">ตรวจสอบ Device เพื่อเข้าใช้งาน อนุญาตให้เข้าใช้งานเฉพาะ</div>
                                        <?php foreach ($_aDetectConfig['Device'] as $k => $v) {
                                            $md5 = md5($k);
                                            $se = (!empty($rDevice) && array_key_exists($md5, $rDevice)) ? 'checked' : ''; ?>
                                            <div class="form-group checkbox">
                                                <input <?php echo $se; ?> class="magic-checkbox" id="checkConfig-<?php echo $md5; ?>" type="checkbox" name="aDevice[<?php echo $md5; ?>]" value="<?php echo md5($v); ?>">
                                                <label for="checkConfig-<?php echo $md5; ?>"><?php echo $v; ?></label>
                                            </div>
                                        <?php }
                                        if (!file_exists($fnameDevice)) { ?><div class="text-main text-bold">ขณะนี้ผู้ใช้งานกำลังใช้งานทุกอุปกรณ์อยู่</div><?php } ?>
                                    </div>
                                </div>
                                <div id="demo-lft-tab-time" class="tab-pane fade">
                                    <div class="form-group">
                                        <div class="text-main text-bold">ขณะนี้ผู้ใช้งานกำลังใช้ในเวลา <?php echo $userTime; ?> อยู่</div>
                                        <div class="text-main text-bold">ตรวจสอบ ช่วงเวลา เพื่อเข้าใช้งาน อนุญาตให้เข้าใช้งานเฉพาะ</div>
                                        <?php foreach ($_aDetectConfig['Time'] as $k => $v) {
                                            $se = (!empty($rTime) && array_key_exists($k, $rTime)) ? 'checked' : ''; ?>
                                            <div class="form-group checkbox">
                                                <input <?php echo $se; ?> class="magic-checkbox" id="checkConfig-<?php echo md5($k); ?>" type="checkbox" name="aTime[<?php echo $k; ?>]" value="<?php echo md5($v); ?>">
                                                <label for="checkConfig-<?php echo md5($k); ?>"><?php echo $v; ?></label>
                                            </div>
                                        <?php }
                                        if (!file_exists($fnameTime)) { ?><div class="text-main text-bold">ขณะนี้ผู้ใช้งานกำลังใช้งานทุกช่วงเวลาอยู่</div><?php } ?>
                                    </div>
                                </div>
                                <div id="demo-lft-tab-ip" class="tab-pane fade">
                                    <div class="form-group">
                                        <div class="text-main text-bold">ขณะนี้ผู้ใช้งานกำลังใช้ไอพี <?php echo $userIP; ?> อยู่</div>
                                        <div class="text-main text-bold">ตรวจสอบ IP เพื่อเข้าใช้งาน อนุญาตให้เข้าใช้งานเฉพาะ</div>
                                        <div class="text-main text-bold">กรุณากรอกในรูปแบบ xxx.xxx.xxx.xxx หรือ zzz.zzz.zzz.zzz/zz</div>
                                        <?php $vIP = file_exists($fnameIP) ? $rIP : []; ?>
                                        <div class="form-group">
                                            <textarea class="form-control" name="aIP" id="aIP" rows="15" style="resize: none; height: auto;"><?php echo implode("\n", $vIP); ?></textarea>
                                        </div>
                                        <?php if (!file_exists($fnameIP)) { ?><div class="text-main text-bold">ขณะนี้ผู้ใช้งานกำลังใช้งานทุกไอพีอยู่</div><?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<script>
    document.getElementById('aIP').addEventListener('input', function(e) {
        var value = e.target.value;
        e.target.value = value.replace(/[^0-9.,/\n]/g, '');
    });
</script>