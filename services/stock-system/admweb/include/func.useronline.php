<?php
function UserOnline_Func_Global_Page_Name()
{
    $id         = REQ_get('id', 'request', 'int', '');
    $keysname     = REQ_get('keysname', 'request', 'str', '');
    $module     = REQ_get('module', 'request', 'str', '');
    $mp         = REQ_get('mp', 'request', 'str', '');
    $strCh         = '';

    if ($mp == 'articles') {
        $strCh = '[' . $module . '][' . $mp . '][' . $keysname . ']';
    }

    $a = array();
    $a['admin']          = 'List Bix ผู้ดูแล';
    $a['adminList']     = 'List Table ผู้ดูแล';
    $a['admin_add']     = '+เพิ่มผู้ดูแล';
    $a['admin_edit']     = 'Edit Admin ID : ' . $id;
    $a['group']         = 'List กลุ่มผู้ดูแล';
    $a['profile']         = 'ข้อมูลส่วนตัว';
    $a['articles']         = $strCh;
    return $a;
}

function displayTimeSize($times)
{
    $curTime = _TIME_;
    $difTime = $curTime - $times;
    $difSec = ceil($difTime / 60);
    return '<span class="text-info">' . (($difSec > 0) ? $difSec . ' นาที' : 'เมื่อสักครู่') . '</span>';
    /*
	if ($difTime > 0) {
		echo $difSec.' นาที';
	} else {
		echo $difSec.' นาที';
	}*/
}

function MemberOnline($page = 'home')
{
    $ip = getIP();
    $aUser = oApi::getLoginData();
    $isOnOff = SiteConfig_get('onoff_user_online', 'off');
    $_onlinetime = GlobalConfig_get('useronline_count_time', 30);
    if ($isOnOff == 'off') {
        return; // If the online tracking is off, do not proceed
    }
    
    $onlineInDb = DB_GET('member_online', ['ipaddress' => $ip]);
    $userid = isset($aUser->user_id) ? $aUser->user_id : 0;
    $onlineEndTime = _TIME_ + (60 * $_onlinetime);
    if (isset($onlineInDb['online_id']) && $onlineInDb['online_id'] > 0) {
        DB_UP('member_online', [
            'viewurl' => $page, 
            'onlineTime' => _TIME_,
            'onlineEndTime' => $onlineEndTime
        ], ['ipaddress' => $ip, 'online_id' => $onlineInDb['online_id']]);
    } else {
        DB_ADD('member_online', [
            'user_id' => $userid,
            'actionView' => '-',
            'onlineTime' => _TIME_,
            'onlineEndTime' => $onlineEndTime,
            'ipaddress' => $ip,
            'viewurl' => $page,
        ]);
    }
}
