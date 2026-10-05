<?php
define("DB_FUNC", "FUNC_V8");

function _validateOrderBy(string $orderby): string
{
    $orderby = str_replace(';', '', $orderby);
    if ($orderby && !preg_match('/^(ORDER BY\s+(RAND\(\)|[a-zA-Z0-9_,\s.`]+))$/i', $orderby)) {
        return '';
    }
    return $orderby;
}
/*
ดึงทุก row จากตาราง users โดยไม่ระบุเงื่อนไข
$data = DB_LIST('users');

ดึงเฉพาะ row ที่ status = 'active'
$data = DB_LIST('users', ['status' => 'active']);

ดึงเฉพาะผู้ใช้ที่ id มากกว่า 10
$data = DB_LIST('users', ['id' => ['>', 10]]);

ดึงเฉพาะที่ status ไม่เท่ากับ 'banned'
$data = DB_LIST('users', ['status' => ['!=', 'banned']]);

ดึงเฉพาะที่ user_id อยู่ในลิสต์
$data = DB_LIST('users', ['user_id' => ['IN', [1, 2, 3, 4]]]);

ดึงเฉพาะชื่อที่ขึ้นต้นด้วย 'S' โดยใช้ LIKE
$data = DB_LIST('users', ['name' => ['LIKE', 'S%']]);

ดึงเฉพาะหน้าที่ 2 ของข้อมูล โดยจำกัดหน้าละ 10 รายการ
$data = DB_LIST('users', ['status' => 'active'], 10, 2, 'ORDER BY created_at DESC');

ใช้ NOT IN
$data = DB_LIST('users', ['role' => ['NOT IN', ['admin', 'superadmin']]]);

ดึงเฉพาะที่ status = 'active' และ level > 3 พร้อมเรียงลำดับชื่อ
$data = DB_LIST('users', [
    'status' => 'active',
    'level' => ['>', 3]
], 'ORDER BY name ASC');

WEB_UP($table, 
    ['name' => 'John Doe','email' => 'john.doe@example.com'], 
    ['id' => 10,'status' => 'active']
);
*/

function DB_GET($table, $where = [], $orderby = '')
{
    global $aQData;
    if (empty($where) || !is_array($where)) {
        return false; // ถ้าไม่มีเงื่อนไข where ไม่ทำอะไร
    }

    $md5 = md5($table . serialize($where) . $orderby);
    if (isset($aQData[$md5])) {
        return $aQData[$md5];
    }

    $db = DB::singleton();
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    $conditions = [];
    $params = [];
    $paramIndex = 0;

    foreach ($where as $col => $val) {
        $safe_col = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
        $param_key = ":param_$paramIndex";

        if (is_array($val) && count($val) === 2) {
            list($operator, $value) = $val;

            $allowed_ops = ['=', '!=', '<', '<=', '>', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];
            $operator = strtoupper(trim($operator));
            if (!in_array($operator, $allowed_ops)) {
                continue;
            }

            if (in_array($operator, ['IN', 'NOT IN']) && is_array($value)) {
                $placeholders = [];
                foreach ($value as $i => $v) {
                    $ph = ":{$safe_col}_{$paramIndex}_$i";
                    $placeholders[] = $ph;
                    $params[$ph] = $v;
                }
                $conditions[] = "`$safe_col` $operator (" . implode(', ', $placeholders) . ")";
            } else {
                $conditions[] = "`$safe_col` $operator $param_key";
                $params[$param_key] = $value;
            }
        } else {
            $conditions[] = "`$safe_col` = $param_key";
            $params[$param_key] = $val;
        }

        $paramIndex++;
    }

    $where_sql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $orderby = _validateOrderBy($orderby);

    $sqlData = "SELECT * FROM `" . _DBPREFIX_ . "$table` $where_sql $orderby LIMIT 1";
    $row = $db->getRow($sqlData, $params);
    $aQData[$md5] = $row;
    return $row;
}

function DB_JOIN($sql, $aparams = [], $limit = 0, $page = 1, $orderby = '')
{
    global $aQData;
    $db = DB::singleton();
    $orderby = _validateOrderBy($orderby);

    $sqlBase = preg_replace('/\s+LIMIT\s+\d+(\s*,\s*\d+)?\s*$/i', '', trim($sql));
    if ($limit <= 0) {
        $sqlData = $sqlBase . ' ' . $orderby;
        $rows = $db->getAll($sqlData, $aparams);
        $data = [
        'sql'      => $sqlData,
        'orderby'  => $orderby,
        'params'   => $aparams,
        'num_rows' => count($rows),
        'maxpage'  => 1,
        'nextpage' => 1,
        'data'     => $rows
        ];
        return $data;
    }

    $md5 = md5($sqlBase . serialize($aparams) . $limit . $page . $orderby);
    if (isset($aQData[$md5])) {
        return $aQData[$md5];
    }

    $sqlCount = "SELECT COUNT(*) FROM (\n" . $sqlBase . "\n) AS _cnt";
    $totalRows = (int)$db->getOne($sqlCount, $aparams);
    $offset = max(0, ($page - 1)) * $limit;
    $limitSql = " LIMIT $offset, $limit";
    $sqlData = $sqlBase . ' ' . $orderby . $limitSql;
    $rows = $db->getAll($sqlData, $aparams);
    $maxPage = $limit > 0 ? (int)ceil($totalRows / $limit) : 1;
    $nextPage = ($page < $maxPage) ? $page + 1 : $maxPage;

    $data = [
        'sql'      => $sqlData,
        'orderby'  => $orderby,
        'params'   => $aparams,
        'num_rows' => $totalRows,
        'maxpage'  => $maxPage,
        'nextpage' => $nextPage,
        'data'     => $rows,
    ];
    
    $aQData[$md5] = $data;
    return $data;
}


function DB_LIST($table, $where = [], $limit = 0, $page = 1, $orderby = '')
{
    global $aQData;
    if (!is_array($where)) {
        return false; // ถ้าไม่มีเงื่อนไข where ไม่ทำอะไร
    }

    $md5 = md5($table . serialize($where) . $orderby . $limit . $page);
    if (isset($aQData[$md5])) {
        return $aQData[$md5];
    }


    $conditions = [];
    $params = [];
    $paramIndex = 0;
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    foreach ($where as $col => $val) {
        $safe_col = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
        $param_key = ":param_$paramIndex";

        if (is_array($val) && count($val) === 2) {
            list($operator, $value) = $val;

            $allowed_ops = ['=', '!=', '<', '<=', '>', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];
            $operator = strtoupper(trim($operator));
            if (!in_array($operator, $allowed_ops)) {
                continue;
            }

            if (in_array($operator, ['IN', 'NOT IN']) && is_array($value)) {
                $placeholders = [];
                foreach ($value as $i => $v) {
                    $ph = ":{$safe_col}_{$paramIndex}_$i";
                    $placeholders[] = $ph;
                    $params[$ph] = $v;
                }
                $conditions[] = "`$safe_col` $operator (" . implode(', ', $placeholders) . ")";
            } else {
                $conditions[] = "`$safe_col` $operator $param_key";
                $params[$param_key] = $value;
            }
        } else {
            $conditions[] = "`$safe_col` = $param_key";
            $params[$param_key] = $val;
        }

        $paramIndex++;
    }

    $where_sql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $orderby = _validateOrderBy($orderby);

    // นับจำนวนแถวทั้งหมด    
    $db = DB::singleton();
    $sqlCount = "SELECT COUNT(*) FROM `" . _DBPREFIX_ . "$table` $where_sql";
    $totalRows = $db->getOne($sqlCount, $params);  // <-- ใช้ getRow() เพราะคืน fetchColumn()

    // คำนวณ LIMIT OFFSET
    $limit_sql = '';
    if ($limit > 0) {
        $offset = max(0, ($page - 1)) * $limit;
        $limit_sql = "LIMIT $offset, $limit";
    }

    $sqlData = "SELECT * FROM `" . _DBPREFIX_ . "$table` $where_sql $orderby $limit_sql";
    $rows = $db->getAll($sqlData, $params);

    // คำนวณจำนวนหน้าและหน้าถัดไป
    $maxPage = $limit > 0 ? (int)ceil($totalRows / $limit) : 1;
    $nextPage = ($page < $maxPage) ? $page + 1 : $maxPage;

    $data = [
        'sql' => $sqlData,
        'orderby' => $orderby,
        'params' => $params,
        'num_rows' => (int)$totalRows,
        'maxpage' => $maxPage,
        'nextpage' => $nextPage,
        'data' => $rows,
    ];

    $aQData[$md5] = $data;
    return $data;
}

function DB_ADD($table, $a)
{
    $db = DB::singleton();

    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    $fields = [];
    $placeholders = [];
    $values = [];

    foreach ($a as $k => $v) {
        $safeField = preg_replace('/[^a-zA-Z0-9_]/', '', $k);
        $fields[] = "`{$safeField}`";
        $placeholders[] = ":" . $safeField;
        $values[":" . $safeField] = $v;
    }

    $sql = "INSERT INTO `" . _DBPREFIX_ . $safeTable . "` (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";

    try {
        $db->prepare($sql, $values);
        return $db->getInsertID();
    } catch (PDOException $e) {
        error_log("[DB_ADD ERROR] SQL: $sql");
        error_log("[DB_ADD ERROR] PARAMS: " . json_encode($values));
        error_log("[DB_ADD ERROR] MESSAGE: " . $e->getMessage());
        return false;
    }
}


/**
 * TODO: [x] INSERT ... ON DUPLICATE KEY UPDATE ด้วย named params + EXPR syntax
 *
 * INSERT row ถ้ายังไม่มี หรือ UPDATE ถ้า UNIQUE/PK ชนกัน (Upsert)
 *
 * @param  string $table       ชื่อตาราง (ไม่ต้องใส่ prefix)
 * @param  array  $insertData  ข้อมูลสำหรับ INSERT ['col' => $val, ...]
 * @param  array  $updateData  ข้อมูลสำหรับ ON DUPLICATE KEY UPDATE
 *                             - scalar → parameterized: col = :u_col
 *                             - ['EXPR', 'col + 1'] → raw expression (ตาม operator syntax เดิม)
 *                             - ถ้าว่าง → ใช้ VALUES() reference ของทุก insert column
 * @return bool   true = สำเร็จ, false = ล้มเหลว
 *
 * ตัวอย่าง:
 *   DB_UPSERT('site_counter',
 *       ['page'=>$p, 'type'=>$t, 'year'=>$y, 'month'=>$m, 'day'=>$d, 'count'=>1],
 *       ['count' => ['EXPR', 'count + 1']]
 *   );
 */
function DB_UPSERT($table, $insertData, $updateData = [])
{
    if (empty($insertData) || !is_array($insertData)) {
        return false;
    }

    $db        = DB::singleton();
    $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    $fields       = [];
    $placeholders = [];
    $params       = [];

    foreach ($insertData as $k => $v) {
        $safeField = preg_replace('/[^a-zA-Z0-9_]/', '', $k);
        if ($safeField === '') continue;
        $fields[]        = "`{$safeField}`";
        $placeholders[]  = ":i_{$safeField}";
        $params[":i_{$safeField}"] = $v;
    }

    if (empty($fields)) {
        return false;
    }

    // ── ON DUPLICATE KEY UPDATE ────────────────────────────────────────
    $updateParts = [];
    if (!empty($updateData) && is_array($updateData)) {
        foreach ($updateData as $k => $v) {
            $safeField = preg_replace('/[^a-zA-Z0-9_]/', '', $k);
            if ($safeField === '') continue;

            if (is_array($v) && count($v) === 2 && strtoupper($v[0]) === 'EXPR') {
                // ['EXPR', 'count + 1'] → raw expression (whitelist chars เพื่อความปลอดภัย)
                $expr = preg_replace('/[^a-zA-Z0-9_\s\+\-\*\/`\.]/', '', $v[1]);
                $updateParts[] = "`{$safeField}` = {$expr}";
            } else {
                // scalar → parameterized
                $updateParts[] = "`{$safeField}` = :u_{$safeField}";
                $params[":u_{$safeField}"] = $v;
            }
        }
    }

    if (empty($updateParts)) {
        // ถ้าไม่ระบุ updateData → ใช้ VALUES() reference ของทุก insert field
        foreach ($insertData as $k => $v) {
            $safeField = preg_replace('/[^a-zA-Z0-9_]/', '', $k);
            if ($safeField === '') continue;
            $updateParts[] = "`{$safeField}` = VALUES(`{$safeField}`)";
        }
    }

    $sql = "INSERT INTO `" . _DBPREFIX_ . "{$safeTable}` "
         . "(" . implode(', ', $fields) . ") "
         . "VALUES (" . implode(', ', $placeholders) . ") "
         . "ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);

    try {
        $db->prepare($sql, $params);
        return true;
    } catch (PDOException $e) {
        error_log("[DB_UPSERT ERROR] SQL: $sql");
        error_log("[DB_UPSERT ERROR] PARAMS: " . json_encode($params));
        error_log("[DB_UPSERT ERROR] MESSAGE: " . $e->getMessage());
        return false;
    }
}


function DB_UP($table, $set = [], $where = [])
{
    if (empty($set) || empty($where)) {
        return false; // ถ้าไม่มีข้อมูล set หรือ where ไม่ทำอะไร
    }

    $db = DB::singleton();
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    $set_parts = [];
    $params = [];

    foreach ($set as $col => $val) {
        $col_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
        $set_parts[] = "`$col_safe` = :set_$col_safe";
        $params[":set_$col_safe"] = $val;
    }

    $where_parts = [];
    foreach ($where as $col => $val) {
        $col_safe = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
        $where_parts[] = "`$col_safe` = :where_$col_safe";
        $params[":where_$col_safe"] = $val;
    }

    $sql = "UPDATE `" . _DBPREFIX_ . $table . "` 
            SET " . implode(", ", $set_parts) . " 
            WHERE " . implode(" AND ", $where_parts);

    try {
        // ✅ execute ทันทีใน prepare
        $db->prepare($sql, $params);
        return true; // สำเร็จ
    } catch (PDOException $e) {
        error_log("[DB_UP ERROR] SQL: $sql");
        error_log("[DB_UP ERROR] PARAMS: " . json_encode($params));
        error_log("[DB_UP ERROR] MESSAGE: " . $e->getMessage());
        return false;
    }
}

function DB_DEL($table, $where = [], $isReturnQuery = false)
{
    if (empty($where) || !is_array($where)) {
        return false; // ถ้าไม่มีเงื่อนไข where ไม่ทำอะไร
    }

    $db = DB::singleton();
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);

    $conditions = [];
    $params = [];

    foreach ($where as $col => $val) {
        $safe_col = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
        $conditions[] = "`$safe_col` = :$safe_col";
        $params[":$safe_col"] = $val;
    }

    if (empty($conditions)) {
        return false;
    }

    $where_sql = implode(' AND ', $conditions);
    $sql = "DELETE FROM `" . _DBPREFIX_ . $table . "` WHERE $where_sql";

    // ✅ ถ้าต้องการแค่ query string
    if ($isReturnQuery === true) {
        return $sql;
    }

    try {
        // ✅ execute ใน prepare ทีเดียว
        $db->prepare($sql, $params);
        return true;
    } catch (PDOException $e) {
        error_log("[DB_DEL ERROR] SQL: $sql");
        error_log("[DB_DEL ERROR] PARAMS: " . json_encode($params));
        error_log("[DB_DEL ERROR] MESSAGE: " . $e->getMessage());
        return false;
    }
}
