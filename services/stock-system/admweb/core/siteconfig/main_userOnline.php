<?php
/*
$sqlArray[_DBPREFIX_ . 'member_online'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "member_online` (
  `online_id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `onlineTime` int(11) NULL default '0',
  `onlineEndTime` int(11) NULL default '0',
  `actionView` varchar(255) NULL default '',
  `ipaddress` varchar(20) NULL default '',
  `viewurl` varchar(255) NULL default '',
  PRIMARY KEY  (`online_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
";
*/
PERMIT::_PERMIT(_MODULE_, 'module|mp|metakey', 'สามารถดูรายชื่อ User Online ได้', 'redirect', 'SET');
$ac = REQ_get('ac', 'str', '');

if ($ac == 'del') {
    $id = REQ_get('id', 'int', 0);
    if ($id > 0) {
        DB_DEL('member_online', ['online_id' => $id]);
        setRaiseMsg('ลบ User Online สำเร็จ', _TIME_, 'ok');
    } else {
        setRaiseMsg('ไม่พบข้อมูลที่ต้องการลบ', _TIME_, 'error');
    }
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
    exit;
}

if ($ac == 'nooff') {
    $chto = REQ_get('chto', 'str', '');
    $chtoGet = DB_GET('site_configs', ['keywords' => 'onoff_user_online']);
    if ($chtoGet == '') {
        $data = array(
            'keywords' => 'onoff_user_online',
            'val' => $chto
        );
        DB_ADD('site_configs', $data);
    } else {
        DB_UP('site_configs', ['val' => $chto], ['keywords' => 'onoff_user_online']);
    }
    setRaiseMsg('เปลี่ยนสถานะ User Online สำเร็จ', _TIME_, 'ok');
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
    exit;
}

if ($ac == 'setonlinetime') {
    $set_onlinetime = REQ_get('set_onlinetime', 'int', 30);
    if ($set_onlinetime > 0) {
        $checkGet = DB_GET('site_configs', ['keywords' => 'useronline_count_time']);
        if ($checkGet == '') {
            $data = array(
                'keywords' => 'useronline_count_time',
                'val' => $set_onlinetime
            );
            DB_ADD('site_configs', $data);
        } else {
            DB_UP('site_configs', ['val' => $set_onlinetime], ['keywords' => 'useronline_count_time']);
        }
        setRaiseMsg('อัพเดทระยะเวลา Online สำเร็จ', _TIME_, 'ok');
    } else {
        setRaiseMsg('กรุณาระบุระยะเวลาเป็นจำนวนที่มากกว่า 0', _TIME_, 'error');
    }
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
    exit;
}

$chto = DB_GET('site_configs', ['keywords' => 'onoff_user_online']);
$isOn = (isset($chto['val']) && $chto['val'] == 'on') ? 'on' : 'off';

_memberonline_delete_old();
if ($isOn == 'on') {
    $aListUserOnline = DB_LIST(
        'member_online',
        ['user_id' => ['!=', '0']],
        0,
        0,
        'ORDER BY onlineTime DESC'
    );

    $aGuestOnline = DB_LIST(
        'member_online',
        ['user_id' => '0'],
        0,
        0,
        'ORDER BY onlineTime DESC'
    );

    $aListUserOnline['num_rows'] = count($aListUserOnline['data']);
    $aGuestOnline['num_rows'] = count($aGuestOnline['data']);
} else {
    $aListUserOnline['num_rows'] = 0;
    $aGuestOnline['num_rows'] = 0;
}

$_onlinetime = GlobalConfig_get('useronline_count_time', 30);
?>
<div id="page-head">
    <div id="page-title">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header text-overflow">
                    User Online List &nbsp; &nbsp;
                    <?php if ($isOn == 'on') { ?>
                        <a href="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&ac=nooff&chto=off'); ?>"><i class="fa fa-toggle-on" style="font-size: 20px;color: #8BC34A;"></i></a> ON
                    <?php } else { ?>
                        <a href="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&ac=nooff&chto=on'); ?>"><i class="fa fa-toggle-off" style="font-size: 20px;color: #F44336;"></i></a> OFF
                    <?php } ?>
                </h1>
            </div>
        </div>
    </div>
</div>

<div id="page-content">
    <div class="row">
        <div class="col-xs-12">
            <?php displayRaiseMsg(); ?>

            <div class="tab-base">

                <!--Nav Tabs-->
                <ul class="nav nav-tabs">
                    <li class="active">
                        <a data-toggle="tab" href="#demo-lft-tab-1">USER ONLINE <span class="badge badge-purple"><?php echo $aListUserOnline['num_rows']; ?></span></a>
                    </li>
                    <li>
                        <a data-toggle="tab" href="#demo-lft-tab-2">GUEST ONLINE <span class="badge badge-pink"><?php echo $aGuestOnline['num_rows']; ?></span></a>
                    </li>
                    <li>
                        <a data-toggle="tab" href="#demo-lft-tab-3">SETTINGS</a>
                    </li>
                </ul>

                <!--Tabs Content-->
                <div class="tab-content">
                    <div id="demo-lft-tab-1" class="tab-pane fade active in">
                        <p class="text-main text-semibold">แสดงสถานะ User ทั้งหมดในระบบ Online</p>
                        <p>สมาชิกที่ Online ภายในเวลา <b><?php echo $_onlinetime; ?></b> นาที ที่ผ่านมา</p>
                        <?php if ($isOn == 'on') { ?>
                            <?php if ($aListUserOnline['num_rows'] > 0) { ?>
                                <table class="table table-bordered table-striped table-vcenter">
                                    <thead>
                                        <tr>
                                            <th>Delete</th>
                                            <th>User ID</th>
                                            <th>Online Time</th>
                                            <th>End Time</th>
                                            <th>Action View</th>
                                            <th>IP Address</th>
                                            <th>View URL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        foreach ($aListUserOnline['data'] as $userOnline) {
                                            $aMember = DB_GET('member_member', ['user_id' => $userOnline['user_id']]);
                                            $member_user = isset($aMember['username']) ? $aMember['username'] : 'Unknown';
                                            $member_name = isset($aMember['firstname']) ? $aMember['firstname'] . ' ' . $aMember['lastname'] : 'Unknown';
                                        ?>
                                            <tr>
                                                <td><a class="btn btn-danger" href="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&ac=del&id=' . $userOnline['online_id']); ?>" onclick="return confirm('Delete ?');">DEL</a></td>
                                                <td><?php echo htmlspecialchars($userOnline['ipaddress']); ?></td>
                                                <td><b><?php echo $member_name . ' [ ' . $userOnline['user_id'] . ' ]'; ?></b></td>
                                                <td><?php echo date('Y-m-d H:i:s', $userOnline['onlineTime']); ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', $userOnline['onlineEndTime']); ?></td>
                                                <td><?php echo htmlspecialchars($userOnline['actionView']); ?></td>
                                                <td><?php echo htmlspecialchars($userOnline['viewurl']); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                        <?php }
                        } ?>
                    </div>
                    <div id="demo-lft-tab-2" class="tab-pane fade">
                        <p class="text-main text-semibold">แสดงสถานะ Guest หรือบุคคลทั่วไปที่ Online</p>
                        <p>บุคคลทั่วไปที่ Online ล่าสุดภายใน <b><?php echo $_onlinetime; ?></b> นาทีที่ผ่านมา ระบบจะทำการลบทิ้งหากรายชื่อออนไลน์เก่าเกิน 2 Day</p>
                        <?php if ($isOn == 'on') { ?>
                            <?php if ($aGuestOnline['num_rows'] > 0) { ?>
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Delete</th>
                                            <th>IP Address</th>
                                            <th>Online Time</th>
                                            <th>End Time</th>
                                            <th>Action View</th>
                                            <th>View URL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($aGuestOnline['data'] as $userOnline) { ?>
                                            <tr>
                                                <td><a class="btn btn-danger" href="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&ac=del&id=' . $userOnline['online_id']); ?>" onclick="return confirm('Delete ?');">DEL</a></td>
                                                <td><?php echo htmlspecialchars($userOnline['ipaddress']); ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', $userOnline['onlineTime']); ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', $userOnline['onlineEndTime']); ?></td>
                                                <td><?php echo htmlspecialchars($userOnline['actionView']); ?></td>
                                                <td><?php echo htmlspecialchars($userOnline['viewurl']); ?></td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                        <?php }
                        } ?>
                    </div>
                    <div id="demo-lft-tab-3" class="tab-pane fade">
                        <p class="text-main text-semibold">ตั้งค่าต่างๆเกี่ยวกับ User Online</p>
                        <form method="post" action="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_); ?>">
                            <input type="hidden" name="ac" value="setonlinetime">
                            <div class="form-group">
                                <label for="set_onlinetime">ระยะเวลาในการ Online</label>
                                <input type="number" class="form-control" name="set_onlinetime" id="set_onlinetime" value="<?php echo $_onlinetime; ?>" placeholder="ระบุเป็นจำนวน นาที เช่น 30">
                            </div>
                            <button type="submit" class="btn btn-primary">Update Settings</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>