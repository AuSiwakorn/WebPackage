<?php
class PERMIT
{
    /**
     * PERMIT::PERMIT(
     *   'example', 
     *   'module|mp|keysname|inc', 
     *   'You can open article',
     *   'redirect',
     *   'SET'
     * );
     * 
     * PERMIT::PERMIT(
     *   'example', 
     *   'module|mp|keysname|inc|ac',
     *   'You can delete article',
     *   'return',
     *   'SET'
     * );
     * 
     * $action = redirect | return
     **/

    public static function _PERMIT($module, $keys = 'module|mp|keysname|inc', $name = '', $action = 'redirect', $isPer = 'none')
    {
        global $aosoft_permit_check;
        if ($isPer == 'UNSET') {
            $oUser = login_logout::getAdminUsername();
            if ($oUser !== 'superadmin') {
                login_logout::reDirectToLogin('index.php');
                exit;
            } else {
                define('IS_SET_PERMIT', true);
            }
        }
        if ($isPer == 'SET') {
            define('IS_SET_PERMIT', true);
        }
        if ($keys == '' || $name == '' || $action == '') {
            $errType = $keys == '' ? 'KEYS' : ($name == '' ? 'NAME' : 'TYPE');
            setRaiseMsg("PERMIT : ERROR " . _MODULE_ . " " . _MP_ . " " . $errType . " IS NULL", _TIME_, 1);
            login_logout::reDirectToLogin('index.php');
            exit;
        }
        if (!isset($aosoft_permit_check) || count($aosoft_permit_check) == 0) {
            include PATH_AOWEBDATA . '/permission/default.php';
        }
        $unset = ['สามารถเปิด Permission ได้', 'สามารถเปิด System setup', 'สามารถเปิด ล้างข้อมูลที่ตั้งค่า ได้', 'สามารถเปิด คีย์ที่ยังไม่ได้แปล ได้'];
        if (!in_array($name, $unset)) {
            $str = PERMIT::PERMIT_BUIL_KEY($keys);
        } else {
            return false;
        }
        if (count($aosoft_permit_check) > 0  && array_key_exists($str, $aosoft_permit_check)) {
            if (PERMIT::PERMIT_VALIDATE($str)) {
                return true;
            } else {
                if ($action == 'return') {
                    return false;
                } else {
                    setRaiseMsg('You not have permission access.', _TIME_, 1);
                    login_logout::reDirectToLogin('index.php');
                    exit;
                }
            }
        } else {
            PERMIT::PERMIT_SET($module, $str, $name);
            return true;
        }
    }

    public static function PERMIT_BUIL_KEY($keys)
    {
        global $aQData;
        $md5 = md5($keys);
        if (isset($aQData[$md5])) {
            return $aQData[$md5];
        }
        $str = '';
        $keyBuil = explode('|', $keys);
        if (count($keyBuil) > 0) {
            foreach ($keyBuil as $v) {
                $strData = REQ_get($v, 'request', 'str', '');
                if ($strData !== '') {
                    $str .= '#' . $v . ':' . $strData . '#';
                }
            }
        }
        $aQData[$md5] = ($str === '' && $keys !== '') ? $keys : $str;
        return $aQData[$md5];
    }

    public static function PERMIT_VALIDATE($keys)
    {
        $oPermit = login_logout::getAdminPermission();
        return in_array('all', $oPermit) || in_array($keys, $oPermit) ? true : false;
    }

    public static function PERMIT_SET($module, $keys, $name)
    {
        global $aosoft_permit;
        $path = PATH_UPLOAD . '/permission';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        if (!isset($aosoft_permit) || !isset($aosoft_permit[$module][$keys])) {
            $fname = $path . '/req.txt';
            $data = [];
            $data[$module][$keys] = [$name];
            write_txt_json_a($fname, $data);
        }
    }

    public static function PERMIT_GET()
    {
        global $aosoft_permit;
        if (!isset($aosoft_permit)) {
            include PATH_AOWEBDATA . '/permission/default.php';
        }
        return $aosoft_permit;
    }

    public static function PERMIT_HAS_KEY($key)
    {
        global $aosoft_permit_check;
        if (!isset($aosoft_permit_check) || count($aosoft_permit_check) == 0) {
            include PATH_AOWEBDATA . '/permission/default.php';
        }
        return is_array($aosoft_permit_check) && array_key_exists($key, $aosoft_permit_check);
    }

    public static function PERMIT_REQ()
    {
        if (!defined("IS_SET_PERMIT")) {
            setRaiseMsg('Please set PERMIT "' . _MODULE_ . '" in "' . _MP_ . '". ERROR LINE: ' . __LINE__, _TIME_, 1);
            login_logout::reDirectToLogin('index.php');
        }
    }
}
