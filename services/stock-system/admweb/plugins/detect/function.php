<?php
function _getConfig($data, $fname)
{
    $data ? write_txt_json($fname, $data) : unlink($fname);
}
function _getConfigIP($data, $userIP, $fname)
{
    if ($data) {
        $str = array_map('trim', preg_split('/[,\n]/', $data));
        $strData = [];
        if (!in_array($userIP, $str)) {
            $strData[] = $userIP;
        }
        foreach ($str as $v) {
            if (!empty($v) && strlen($v) <= 18) {
                if (strpos($v, '/') !== false) {
                    list(, $n) = explode('/', $v);
                    if ((int)$n >= 20) {
                        $strData[] = $v;
                    }
                } else {
                    $strData[] = $v;
                }
            }
        }
        write_txt_json($fname, $strData);
    } else {
        unlink($fname);
    }
}
function _getRangeIP($ipRange)
{
    list($ip, $mask) = explode('/', $ipRange);
    $maskBinStr = str_repeat("1", $mask) . str_repeat("0", 32 - $mask);
    $inverseMaskBinStr = str_repeat("0", $mask) . str_repeat("1",  32 - $mask);
    $ipLong = ip2long($ip);
    $ipMaskLong = bindec($maskBinStr);
    $inverseIpMaskLong = bindec($inverseMaskBinStr);
    $netWork = $ipLong & $ipMaskLong;
    $start = $netWork + 1;
    $end = ($netWork | $inverseIpMaskLong) - 1;
    return array('firstIP' => $start, 'lastIP' => $end);
}

function _getEachInIP($ipSubnet)
{
    $ips = array();
    $range = _getRangeIP($ipSubnet);
    for ($ip = $range['firstIP']; $ip <= $range['lastIP']; $ip++) {
        $ips[] = long2ip($ip);
    }
    return $ips;
}

function _DETECT_AO($ret = false)
{
    $oid = login_logout::getAdminId();
    $oUser = login_logout::getAdminUsername();
    if ($oid !== 1 && $oUser !== 'superadmin') {
        $fnameBrowser = PATH_UPLOAD . '/config/browser.txt';
        $fnameOS = PATH_UPLOAD . '/config/os.txt';
        $fnameDevice = PATH_UPLOAD . '/config/device.txt';
        $fnameTime = PATH_UPLOAD . '/config/time.txt';
        $fnameIP = PATH_UPLOAD . '/config/ip.txt';
        if (file_exists($fnameBrowser)) {
            $rBrowser = read_txt_json($fnameBrowser);
            $userBrowser = UserInfo::get_browser();
            if (!array_key_exists(md5($userBrowser), $rBrowser)) {
                setRaiseMsg('This Browser is not configured.', _TIME_, 1);
                if ($ret == false) {
                    CustomRedirectToUrl('logout.php', 1);
                    exit;
                } else {
                    return 'error';
                }
            }
        }
        if (file_exists($fnameOS)) {
            $rOS = read_txt_json($fnameOS);
            $userOS = UserInfo::get_os();
            if (!array_key_exists(md5($userOS), $rOS)) {
                setRaiseMsg('This OS is not configured.', _TIME_, 1);
                if ($ret == false) {
                    CustomRedirectToUrl('logout.php', 1);
                    exit;
                } else {
                    return 'error';
                }
            }
        }
        if (file_exists($fnameDevice)) {
            $rDevice = read_txt_json($fnameDevice);
            $userDevice = UserInfo::get_device();
            if (!array_key_exists(md5($userDevice), $rDevice)) {
                setRaiseMsg('This Device is not configured.', _TIME_, 1);
                if ($ret == false) {
                    CustomRedirectToUrl('logout.php', 1);
                    exit;
                } else {
                    return 'error';
                }
            }
        }
        if (file_exists($fnameTime)) {
            $rTime = read_txt_json($fnameTime);
            $cTime = _TIME_;
            $timeConf = false;
            foreach ($rTime as $k => $v) {
                list($sTime, $eTime) = explode('-', $k);
                $sTime = strtotime($sTime);
                $eTime = strtotime($eTime);
                if ($cTime >= $sTime && $cTime <= $eTime) {
                    $timeConf = true;
                    break;
                }
            }
            if (!$timeConf) {
                setRaiseMsg('This Time is not configured.', _TIME_, 1);
                if ($ret == false) {
                    CustomRedirectToUrl('logout.php', 1);
                    exit;
                } else {
                    return 'error';
                }
            }
        }
        if (file_exists($fnameIP)) {
            $rIP = read_txt_json($fnameIP);
            $userIP = UserInfo::get_ip();
            $rIP2 = [];
            foreach ($rIP as $v) {
                if (strpos($v, '/') !== false) {
                    $rIP2 = array_merge($rIP2, _getEachInIP($v));
                }
            }
            if (!in_array($userIP, $rIP) && !in_array($userIP, $rIP2)) {
                setRaiseMsg('This IP is not configured.', _TIME_, 1);
                if ($ret == false) {
                    CustomRedirectToUrl('logout.php', 1);
                    exit;
                } else {
                    return 'error';
                }
            }
        }
    }
    return '';
}
