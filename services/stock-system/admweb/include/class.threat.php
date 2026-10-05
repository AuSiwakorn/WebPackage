<?php
class Threat
{
    // =========================================================================
    // ส่วนที่ 1: การตั้งค่ากฎ (Rule Sets) สำหรับตรวจจับรูปแบบการโจมตี (Regex)
    // =========================================================================

    /**
     * กฎสำหรับตรวจจับ SQL Injection (SQLi)
     * เน้นตรวจสอบโครงสร้างคำสั่ง SQL ที่ผิดปกติ เช่น ' OR 1=1 หรือการใช้คำสั่งทำลายฐานข้อมูล
     */
    private static $sqlPatterns = [
        '/(?i)([\'"])\s*(OR|AND)\s+[\w\'"%-]+\s*(=|LIKE|>|<)/',                    // ตรวจจับ Tautology (เช่น ' OR '1'='1)
        '/(?i)\bUNION\s+(?:ALL\s+)?SELECT\b/',                                     // ตรวจจับการเชื่อมตารางด้วย UNION SELECT
        '/(?i)\b(DROP\s+TABLE|INSERT\s+INTO|DELETE\s+FROM|UPDATE\s+.*?\s+SET)\b/', // ตรวจจับคำสั่งดัดแปลง/ทำลายข้อมูล
        '/(?i)([\'"])\s*(--|\#|\/\*)/'                                             // ตรวจจับการใช้ Comment ของ SQL แทรกเข้ามา
    ];

    /**
     * กฎสำหรับตรวจจับ Cross-Site Scripting (XSS)
     * เน้นตรวจสอบการฝังสคริปต์อันตรายเพื่อนำไปรันบนหน้าเว็บของผู้ใช้อื่น
     */
    private static $xssPatterns = [
        '/(?i)<\s*script\b[^>]*>[\s\S]{1,}?<\s*\/\s*script\s*>/',
        '/(?i)<\s*[a-z][^>]*\s(on(?:error|load|click|mouseover|focus|submit|abort|change|input|keydown|keyup|keypress))\s*=\s*["\']?[^"\'>\s]/',
        '/(?i)\b(?:javascript|vbscript)\s*:[a-z\/\(]/',
        '/(?i)\bdata\s*:\s*(?:text\/html|application\/(?:javascript|x-javascript|ecmascript))/',
    ];

    /**
     * รายชื่อฟิลด์ที่ยอมให้เนื้อหามี HTML/special character ได้
     * เช่นช่องข้อความยาวใน contact form, ฟิลด์รายละเอียดสมัครงาน ฯลฯ
     * ฟิลด์เหล่านี้จะข้ามการสแกน XSS เท่านั้น (ยังเช็ค SQLi/RCE/Traversal)
     */
    private static $xssExemptFields = [
        'message',
        'detail',
        'description',
        'content',
        'comment',
        'remark',
        'note',
        'body',
        'desc',
        'about',
        'biography',
        'cover_letter',
        'resume_text',
        'experience',
        'education',
        'skill',
        'address',
        'introduce',
    ];

    /**
     * กฎสำหรับตรวจจับ Path Traversal และ Local/Remote File Inclusion (LFI/RFI)
     * เน้นตรวจสอบการพยายามเข้าถึงไฟล์ระบบหรือเรียกสคริปต์จากภายนอก
     */
    private static $fileInclusionPatterns = [
        '/(?i)(?:\.\.[\\\\\/]+){2,}/',                                     // ตรวจจับการย้อน Directory 2 ชั้นขึ้นไป (เช่น ../../)
        '/(?i)(php|file|zlib|data|expect):\/\//',                          // ตรวจจับ PHP Wrappers ที่มักใช้เจาะระบบ
        '/(?i)\/(etc\/passwd|etc\/shadow|windows\\\\system32|boot\.ini)/'  // ตรวจจับการอ้างอิงถึงไฟล์ระบบสำคัญตรงๆ
    ];

    /**
     * กฎสำหรับตรวจจับ Remote Code Execution (RCE) / Command Injection
     * เน้นตรวจสอบความพยายามในการรันคำสั่งระดับ OS (Linux/Windows) บนเซิร์ฟเวอร์
     */
    private static $rcePatterns = [
        '/(?i)(?:;|\|\||&&|`|\$\(.*?\))\s*(?:wget|curl|nc|netcat|bash|sh|ping|cat|type|whoami|id)\b/', // ตรวจจับการต่อคำสั่ง OS (เช่น ; ping 8.8.8.8)
        '/(?i)\b(system|exec|shell_exec|passthru|popen|proc_open)\s*\(/',                             // ตรวจจับการเรียกใช้ฟังก์ชันรันคำสั่งระบบของ PHP
    ];

    /**
     * รายชื่อเครื่องมือสแกนเนอร์และบอทอันตราย (Bad Bots)
     * ใช้สำหรับเทียบกับ HTTP User-Agent ของผู้เข้าชม
     */
    private static $badBots = [
        'sqlmap',          // เครื่องมือเจาะ SQL อัตโนมัติ
        'nikto',           // เครื่องมือสแกนช่องโหว่เว็บ
        'nmap',            // เครื่องมือสแกนพอร์ต/เครือข่าย
        'python-requests', // สคริปต์ยิง Request อัตโนมัติ (มักใช้ทำ Brute Force)
        'curl',            // เครื่องมือโหลดข้อมูลผ่าน Command Line
        'wget'             // เครื่องมือดาวน์โหลดผ่าน Command Line
    ];

    // =========================================================================
    // ส่วนที่ 2: ฟังก์ชันหลัก (Main Execution)
    // =========================================================================

    /**
     * ฟังก์ชันหลักทำหน้าที่รวบรวมข้อมูลทั้งหมดที่ส่งมาและสั่งสแกนทีละขั้นตอน
     */
    public static function runAllChecks()
    {
        if (isset($_SESSION['login']) && $_SESSION['login'] === true) return;

        $detectedThreats = [];

        // 1. อ่านข้อมูล JSON Payload (กรณีระบบรับข้อมูลผ่าน API หรือ Frontend ส่งแบบ application/json)
        $jsonPayload = [];
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput)) {
            $decoded = json_decode($rawInput, true);
            if (is_array($decoded)) {
                $jsonPayload = $decoded;
            }
        }

        // 2. นำข้อมูลทุกช่องทาง (GET, POST, COOKIE, JSON) มารวมเป็นก้อนเดียวเพื่อสแกนรวดเดียว
        $requestData = array_merge($_GET, $_POST, $_COOKIE, $jsonPayload);
        $payloadThreats = self::scanRequest($requestData);
        if (!empty($payloadThreats)) {
            $detectedThreats = array_merge($detectedThreats, $payloadThreats);
        }

        // 3. ตรวจสอบว่าผู้เข้าชมใช้โปรแกรม/บอทสแกนเนอร์หรือไม่ (เช็คจาก User-Agent)
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $botThreat = self::scanUserAgent($userAgent);
        if ($botThreat) {
            $detectedThreats[] = $botThreat;
        }

        // 4. ตรวจสอบไฟล์อัปโหลด (ถ้ามีการแนบไฟล์มาด้วย)
        if (!empty($_FILES)) {
            $fileThreats = self::scanFiles($_FILES);
            if (!empty($fileThreats)) {
                $detectedThreats = array_merge($detectedThreats, $fileThreats);
            }
        }

        // 5. กรองประเภทการโจมตีที่ซ้ำกันออก (เช่น ใน 1 หน้าส่ง SQLi มา 3 จุด ให้นับเป็น SQLi แค่ 1 ประเภท)
        $detectedThreats = array_unique($detectedThreats);

        // 6. หากพบการโจมตีแม้แต่ประเภทเดียว ให้บันทึกสถิติและบล็อกการเข้าถึงทันที
        if (!empty($detectedThreats)) {
            foreach ($detectedThreats as $threatName) {
                self::logThreatCounter($threatName);
            }
            self::blockAccess();
        }
    }

    // =========================================================================
    // ส่วนที่ 3: ฟังก์ชันย่อยสำหรับตรวจสอบข้อมูล (Scanners)
    // =========================================================================

    /**
     * สแกนหา String อันตรายจากข้อมูล Request
     */
    private static function scanRequest($data, $parentKey = '')
    {
        $threats = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // ส่ง key ปัจจุบันเป็น parent ไป เพื่อใช้เช็ค exempt กับ array ซ้อน
                $threats = array_merge($threats, self::scanRequest($value, (string)$key));
                continue;
            }

            $val = urldecode((string)$value);
            $fieldName  = strtolower((string)$key);
            $parentName = strtolower((string)$parentKey);

            // นำค่า String ไปเทียบกับกฎ Regex แต่ละหมวดหมู่
            foreach (self::$sqlPatterns as $pattern) {
                if (preg_match($pattern, $val)) $threats[] = 'sqli';
            }

            // ข้าม XSS scan ถ้าเป็นฟิลด์ที่อนุญาตให้มี HTML/special char ได้
            $isXssExempt = in_array($fieldName, self::$xssExemptFields, true)
                || in_array($parentName, self::$xssExemptFields, true);
            if (!$isXssExempt) {
                foreach (self::$xssPatterns as $pattern) {
                    if (preg_match($pattern, $val)) $threats[] = 'xss';
                }
            }

            foreach (self::$fileInclusionPatterns as $pattern) {
                if (preg_match($pattern, $val)) $threats[] = 'lfi_rfi_traversal';
            }
            foreach (self::$rcePatterns as $pattern) {
                if (preg_match($pattern, $val)) $threats[] = 'rce';
            }
        }
        return $threats;
    }

    /**
     * ตรวจสอบว่า User-Agent ตรงกับรายชื่อบอทอันตรายหรือไม่
     */
    private static function scanUserAgent($userAgent)
    {
        $userAgent = strtolower($userAgent);
        foreach (self::$badBots as $bot) {
            if (strpos($userAgent, $bot) !== false) return 'bad_bot';
        }
        return null;
    }

    /**
     * ตรวจสอบนามสกุลไฟล์อัปโหลดว่าปลอดภัยหรือไม่
     */
    private static function scanFiles($files)
    {
        $threats = [];
        foreach ($files as $fileKey => $fileData) {
            // กรณีอัปโหลดหลายไฟล์ (Multiple) โครงสร้างจะเป็น Array
            if (is_array($fileData['name'])) {
                foreach ($fileData['name'] as $filename) {
                    if (self::isMaliciousFile((string)$filename)) {
                        $threats[] = 'malicious_file';
                    }
                }
            }
            // กรณีอัปโหลดไฟล์เดียว (Single)
            else {
                if (self::isMaliciousFile((string)$fileData['name'])) {
                    $threats[] = 'malicious_file';
                }
            }
        }
        return $threats;
    }

    /**
     * ฟังก์ชันตัวช่วยสำหรับเช็คนามสกุลไฟล์ที่สามารถถูกรันเป็นสคริปต์ได้
     */
    private static function isMaliciousFile($filename)
    {
        return preg_match('/\.(php[34578]?|phtml|phar|exe|sh|bat|cmd|jsp|asp|aspx)$/i', strtolower($filename)) === 1;
    }

    // =========================================================================
    // ส่วนที่ 4: การตอบสนองและบันทึกฐานข้อมูล (Response & Logging)
    // =========================================================================

    private static function getRealIp()
    {
        // 1. ตรวจสอบ Cloudflare (แม่นยำและเชื่อถือได้ที่สุดหากเว็บใช้ Cloudflare)
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            return $_SERVER["HTTP_CF_CONNECTING_IP"];
        }

        // 2. ตรวจสอบ Proxy หรือ Load Balancer ทั่วไป (เช่น Nginx Reverse Proxy)
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // กรณีมี Proxy หลายชั้น มันจะคั่นด้วยลูกน้ำ ให้เอา IP ตัวแรกสุด (ซ้ายสุด)
            $ipArray = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $realIp = trim($ipArray[0]);

            // ตรวจสอบความถูกต้องของรูปแบบ IP
            if (filter_var($realIp, FILTER_VALIDATE_IP)) {
                return $realIp;
            }
        }

        // 3. ตรวจสอบ Header อื่นๆ ที่ Proxy บางค่ายชอบใช้
        $proxyHeaders = [
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED'
        ];

        foreach ($proxyHeaders as $header) {
            if (isset($_SERVER[$header])) {
                $ip = trim($_SERVER[$header]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        // 4. ถ้าไม่มี Proxy ขวางเลย (หรืออ่านค่าไม่ได้จริงๆ) ค่อยกลับมาใช้ REMOTE_ADDR
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
    }

    /**
     * ดึงยอดจำนวนการโจมตีของหมวดหมู่นี้ "เฉพาะในวันนี้"
     */
    private static function DB_DAILY($name)
    {
        global $aQData;
        $today = date('Y-m-d');
        $sql = "SELECT COUNT(*) as amount FROM `" . _DBPREFIX_ . "threat` WHERE name = '{$name}' AND created_at LIKE '{$today}%'";
        $md5Q = md5($sql);
        if (isset($aQData[$md5Q])) {
            return (int)$aQData[$md5Q]['amount'];
        } else {
            $db = DB::singleton();
            $db->query($sql, __FUNCTION__);
            $db->next_record();
            $a = ($db->num_rows() > 0) ? $db->allRows() : [];
            $aQData[$md5Q] = $a;
            return (int)$aQData[$md5Q]['amount'];
        }
    }

    /**
     * บันทึกหรืออัปเดตสถิติการโจมตีลงในฐานข้อมูล
     */
    private static function logThreatCounter($name)
    {
        $ip = self::getRealIp();
        if (GlobalConfig_get('threat_status') == 1) {
            DB_ADD('threat', ['name' => $name, 'ip' => $ip]);
        } else {
            $daily = self::DB_DAILY($name);
            if ($daily < 10) DB_ADD('threat', ['name' => $name, 'ip' => $ip]);
        }
    }

    /**
     * บล็อกการทำงานของระบบและส่ง HTTP Status 403 (Forbidden)
     */
    private static function blockAccess()
    {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>403 Forbidden</h1>";
        echo "<p>ระบบตรวจพบพฤติกรรมที่เข้าข่ายการโจมตีหรือละเมิดนโยบายความปลอดภัย การเข้าถึงของคุณถูกระงับ</p>";
        exit;
    }
}
