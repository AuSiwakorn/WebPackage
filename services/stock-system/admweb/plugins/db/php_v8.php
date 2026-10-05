<?php
class DB
{
    private $pdo;
    private $stmt;
    private $data;
    private $insert_id;
    public $error = "";
    public $errno = "";
    private static $instance;

    public static function &singleton()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
            $this->pdo = new PDO($dsn, DB_USER, DB_PWD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function query($sql, $funcname = '')
    {
        try {
            $this->stmt = $this->pdo->query($sql);
            return $this->stmt;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $this->halt("Query Failed: " . $sql . " | " . $this->error);
            return false;
        }
    }

    public function next_record()
    {
        if ($this->stmt) {
            $this->data = $this->stmt->fetch();
            return $this->data !== false;
        }
        return false;
    }

    public function f($field_name)
    {
        return $this->data[$field_name] ?? null;
    }

    public function allRows()
    {
        return $this->data;
    }

    public function num_rows()
    {
        return $this->stmt ? $this->stmt->rowCount() : 0;
    }

    public function getInsertID()
    {
        return $this->pdo->lastInsertId();
    }

    public function escape($val)
    {
        return $this->pdo->quote($val);
    }

    public function halt($msg)
    {
        echo "<pre style='color:red;'>DB ERROR: $msg</pre>";
    }

    public function close()
    {
        $this->pdo = null;
    }

    public function prepare($sql, $params = [])
    {
        try {
            $this->stmt = $this->pdo->prepare($sql);
            $result = $this->stmt->execute($params);

            // ถ้าเป็น SELECT ให้ fetch data
            if (stripos(trim($sql), 'SELECT') === 0) {
                return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // เก็บ lastInsertId ถ้าเป็น INSERT
            if (stripos(trim($sql), 'INSERT') === 0) {
                $this->insert_id = $this->pdo->lastInsertId();
            }

            return $result;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $this->halt("Prepare Failed: " . $sql . " | " . $this->error);
            return false;
        }
    }

    function pager($func_name, $sql, $num=0, $page=1)
    {
        // ตรวจสอบและ sanitize input
        $func_name = is_string($func_name) ? $func_name : '';
        $sql = is_string($sql) ? trim($sql) : '';
        $num = (is_numeric($num) && $num > 0) ? (int)$num : 0;
        $page = (is_numeric($page) && $page > 0) ? (int)$page : 1;

        // ป้องกัน SQL ที่ไม่ใช่ SELECT
        if (!preg_match('/^\s*SELECT/i', $sql)) {
            throw new InvalidArgumentException("Only SELECT queries are allowed.");
        }

        // เรียก query หลักเพื่อนับจำนวนทั้งหมด
        $this->query($sql, $func_name);

        $aData = [
            'data' => [],
            'num_rows' => $this->num_rows(),
            'maxpage' => ($num > 0) ? (int)ceil($this->num_rows() / $num) : 0,
            'nextpage' => ($page + 1),
            'backpage' => ($page - 1 > 0) ? ($page - 1) : 1,
        ];

        // ป้องกันไม่ให้ nextpage เกิน maxpage
        if ($aData['nextpage'] > $aData['maxpage']) {
            $aData['nextpage'] = $aData['maxpage'];
        }

        // ดึงเฉพาะหน้าที่ร้องขอ
        if ($num > 0 && $aData['maxpage'] > 1) {
            $start = ($page === 1) ? 0 : (($num * $page) - $num);
            $sqlWithLimit = $sql . ' LIMIT ' . $start . ' , ' . $num . ' ;';
            $this->query($sqlWithLimit, $func_name);
            $aData['sql'] = $sqlWithLimit;
        } else {
            $aData['sql'] = $sql;
        }

        while ($this->next_record()) {
            $aData['data'][] = $this->allRows();
        }

        return $aData;
    }
}
