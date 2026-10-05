<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Two Factor Authentication ได้', 'redirect', 'SET');
// getuserinfo
$loginData = login_logout::getLoginData();
$aUser = DB_GET('member_user', ['user_id' => $loginData->user_id,]);
$ac = REQ_get('ac', 'request', 'str', '');
if ($ac == 'twofa') {
    $code = REQ_get('code', 'post', 'str', '');
    if (TWOFAverify(['user_id' => $aUser['user_id']], $code)) {
        setRaiseMsg('Two-Factor Authentication ถูกตั้งค่าเรียบร้อยแล้ว', _TIME_, 0);
        CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
        exit;
    } else {
        setRaiseMsg('Two-Factor Authentication กรอกรหัสผ่านไม่ถูกต้อง', _TIME_, 1);
        CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
        exit;
    }
}elseif ($ac == 'untwofa'){
    $gettwfa = TWOAunable($aUser);
    if ($gettwfa) {
        setRaiseMsg('Two-Factor Authentication ถูกปิดเรียบร้อยแล้ว', _TIME_, 0);
        CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
        exit;
    }
}
?>
<div id="page-head">
    <div id="page-title">
        <h1 class="page-header text-overflow">Two Factor Authentication</h1>
    </div>
</div>

<div id="page-content">
    <div class="row">
        <?php displayRaiseMsg(); ?>
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-7 col-sm-12" style="padding: 45px;">
                            <h1 class="text-main text-center">เปิดใช้งาน Two-Factor Authentication (2FA)</h1>
                            <p style="margin:20px">
                                <strong>Authentication (2FA)</strong>
                                คือการยืนยันตัวตนแบบสองขั้นตอน เพื่อเพิ่มความปลอดภัยในการเข้าสู่ระบบ Admin
                                เมื่อเปิดใช้งานแล้ว ผู้ใช้ต้องเข้าสู่ระบบ
                                <br>
                                <br>
                                1. โหลดแอพ <strong>Two-Factor Authentication</strong> เช่น <strong class="text-warning">Google Authenticator</strong> หรือ <strong class="text-primary">Microsoft Authenticator</strong>
                                จาก Play Store (Android) หรือ App Store (iOS)
                                <br>
                                <br>
                                2. ทำตามขั้นตอนแนะนำทางด้านขวามือ


                            </p>
                            <?php if ($aUser['twofa_status'] == 'pending' || $aUser['twofa_status'] == 'off'): ?>
                                <div class="panel panel-bordered">
                                    <div class="panel-body" style="background: linear-gradient(135deg, #2b3990 0%, #1a237e 100%);">
                                        <div class="row align-items-center" style="margin: 5%;">
                                            <div class="col-md-6 text-center">
                                                <?php $secret = TWOFAenable(['user_id' => $aUser['user_id']]); ?>
                                                <img src="<?php echo TWOFAqrcodegen($secret); ?>" alt="qrcode" class="img-thumbnail" style="background: rgba(255,255,255,0.9); padding: 15px; border-radius: 10px;">
                                            </div>
                                            <div class="col-md-6">
                                                <h3 class="text-light" style="margin-top: 5px; font-weight: 300;">รหัสลับ: <strong class="text-warning"><?php echo $secret; ?></strong></h3>
                                                <form action="" method="post">
                                                    <span class="text-light" style="font-size: 24px; display: block; margin-bottom: 15px;">กรอกรหัส 6 หลัก</span>
                                                    <input type="hidden" name="ac" value="twofa">
                                                    <input type="text" placeholder="กรอกรหัส 6 หลัก" name="code" class="form-control input-lg" maxlength="6" style="background: rgba(255,255,255,0.9); border: none; color: #333; font-size: 18px;">
                                                    <br>
                                                    <button class="btn btn-lg btn-warning btn-glow" type="submit">ยืนยันรหัส</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="panel panel-bordered">
                                    <div class="panel-body" style="background: linear-gradient(135deg, #2b3990 0%, #1a237e 100%);">
                                        <div class="row align-items-center" style="margin: 5%;">
                                            <div class="col-md-12 text-center">
                                                <h1 class="text-light" style="font-weight: 300;">
                                                    <i class="fa fa-check-circle text-warning"></i>
                                                    <span class="text-warning">Two-Factor Authentication (2FA)</span>
                                                    <span class="text-light">เปิดใช้งานแล้ว</span>
                                                </h1>
                                                <span class="text-light" style="font-size: 16px; display: block; margin-top: 15px;">
                                                    เมื่อเข้าสู่ระบบครั้งถัดไป กรุณากรอก Username และ Password ตามปกติ
                                                    จากนั้นระบบจะขอรหัส Two-Factor 6 หลักเพื่อยืนยันตัวตน
                                                </span>
                                                <br>
                                                <form action="" method="post">
                                                    <input type="hidden" name="ac" value="untwofa">
                                                    <button class="btn btn-lg btn-danger btn-glow" type="submit" onclick="return confirm('ต้องการปิด Two-Factor Authentication (2FA) ใช่หรือไม่?');">ปิด 2FA</button>
                                                </form>
                                                <br>
                                                <p class="text-light">***หากต้องการปิดการใช้งาน</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-5 col-sm-12" style="padding: 45px;">
                            <div class="text-center">
                                <div class="phone12 blue">
                                    <div class="screenborder">
                                        <div class="screen12">
                                            <div class="iframe">
                                                <iframe class="loadframe" style="display:block" src="<?= URL_CORE . '/member/exsample/twfastep.html' ?>"></iframe>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>