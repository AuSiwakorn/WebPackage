<?php
function DB_THREAT($year)
{
    global $aQData;
    $sql = "SELECT name, COUNT(*) as amount FROM `" . _DBPREFIX_ . 'threat' . "` WHERE created_at LIKE '{$year}-%' GROUP BY name ORDER BY amount DESC";
    $md5Q = md5($sql . 0 . 0);
    if (isset($aQData[$md5Q])) {
        return $aQData[$md5Q];
    } else {
        $db = DB::singleton();
        $aData = $db->pager(__FUNCTION__, $sql, 0, 0);
        $aQData[$md5Q] = $aData;
        return $aQData[$md5Q];
    }
}

function DB_THREAT_REPORT($year)
{
    global $aQData;
    $sql = "SELECT name, DATE_FORMAT(created_at, '%m') as month_num, COUNT(id) as amount 
            FROM `" . _DBPREFIX_ . "threat` 
            WHERE created_at LIKE '{$year}-%' 
            GROUP BY name, DATE_FORMAT(created_at, '%m')";
    $md5Q = md5($sql . 0 . 0);
    if (isset($aQData[$md5Q])) {
        return $aQData[$md5Q];
    } else {
        $db = DB::singleton();
        $aData = $db->pager(__FUNCTION__, $sql, 0, 0);
        $aQData[$md5Q] = $aData;
        return $aQData[$md5Q];
    }
}
