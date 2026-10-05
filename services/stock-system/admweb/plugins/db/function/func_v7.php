<?php
define("DB_FUNC", "FUNC_V7");
/*
/////////////////////////
======== DB_GET ========
DB_GET('tablename', 'id', 10);
return array();
/////////////////////////
*/
function DB_GET($table, $where, $where_val, $orderby = '')
{
    global  $aQData;
    $sql = "SELECT *  FROM " . _DBPREFIX_ . $table . "  WHERE {$where} = '{$where_val}' {$orderby}; ";
    $md5Q = md5($sql);
    if (isset($aQData[$md5Q])) {
        return $aQData[$md5Q];
    } else {
        $db = DB::singleton();
        $db->query($sql, __FUNCTION__);
        $db->next_record();
        $a = ($db->num_rows() > 0) ? $db->allRows() : array();
        $aQData[$md5Q] = $a;
        return $aQData[$md5Q];
    }
}

function DB_GET_CUS($table, $where)
{
    global  $aQData;
    $sql = "SELECT *  FROM `" . _DBPREFIX_ . $table . "` WHERE {$where}; ";
    $md5Q = md5($sql);
    if (isset($aQData[$md5Q])) {
        return $aQData[$md5Q];
    } else {
        $db = DB::singleton();
        $db->query($sql, __FUNCTION__);
        $db->next_record();
        $a = ($db->num_rows() > 0) ? $db->allRows() : array();
        $aQData[$md5Q] = $a;
        return $aQData[$md5Q];
    }
}

/*
/////////////////////////
======== DB_UP ========
DB_UP('tablename', 'colname', 'TestUpdateData', 'id', 10);
/////////////////////////
*/
function DB_UP($table, $set_col, $set_val, $where, $where_val = '')
{
    $db = DB::singleton();
    $sql = "UPDATE `" . _DBPREFIX_ . $table . "` SET `{$set_col}` = :set_val WHERE `{$where}` = :where_val";
    $params = [
        ':set_val' => $set_val,
        ':where_val' => $where_val,
    ];
    return $db->prepare($sql, $params);
}

/*
DB_UP_WHERE("users", "status", "active", ["id" => 123]);
$where = ['id' => 5];
*/
function DB_UP_WHERE($table, $set_col, $set_val, $where)
{
    $db = DB::singleton();
    $params = ['val' => $set_val];
    $where_clause = [];

    foreach ($where as $k => $v) {
        $paramKey = "w_{$k}";
        $where_clause[] = "`$k` = :$paramKey";
        $params[$paramKey] = $v;
    }

    $sql = "UPDATE `" . _DBPREFIX_ . $table . "` SET `{$set_col}` = :val WHERE " . implode(' AND ', $where_clause);
    $db->prepare($sql, $params);
}

/*
/////////////////////////
======== DB_ADD ========
$a = array();
$a['id'] = NULL;
$a['col1'] = 1;
$a['col2'] = 'text';

DB_ADD('tablename', $a);
return true/false
/////////////////////////
*/
function DB_ADD($table, $a)
{
    $db = DB::singleton();
    $fields = [];
    $placeholders = [];
    $values = [];

    foreach ($a as $k => $v) {
        $fields[] = "`{$k}`";
        $placeholders[] = ":" . $k;
        $values[$k] = $v;
    }

    $sql = "INSERT INTO `" . _DBPREFIX_ . $table . "` (" . implode(",", $fields) . ") VALUES (" . implode(",", $placeholders) . ")";
    $stmt = $db->prepare($sql, $values);

    return ($stmt) ? $db->getInsertID() : false;
}

/*
/////////////////////////
======== DB_LIST ========
DB_LIST('tablename', " id != 5 AND keysname='test' ", 0, 1);
return array(data);
/////////////////////////
*/
function DB_LIST($table, $where, $num = 0, $page = 0, $orderby = '')
{
    global $aQData;

    $num = _input_validate_int($num, true);
    $page = _input_validate_int($page, true);
    $sql = "SELECT * FROM `" . _DBPREFIX_ . $table . "` WHERE {$where} {$orderby}";
    $md5Q = md5($sql . $num . $page);
    if (isset($aQData[$md5Q])) {
        return $aQData[$md5Q];
    } else {
        $db = DB::singleton();
        $aData = $db->pager(__FUNCTION__, $sql, $num, $page);

        $aQData[$md5Q] = $aData;
        return $aQData[$md5Q];
    }
}

/*
/////////////////////////
======== DB_DEL ========
DB_DEL('tablename', "id", 5);
/////////////////////////
*/
function DB_DEL($table, $where_col, $where_val, $isReturnQuery = false)
{
    $db = DB::singleton();
    $sql = "DELETE FROM `" . _DBPREFIX_ . $table . "` WHERE {$where_col}='{$where_val}'; ";

    if ($isReturnQuery == true) {
        $db->query($sql, __FUNCTION__);
        return $sql;
    } else {
        return $db->query($sql, __FUNCTION__);
    }
}
