<?php
$allowedDomain = 'aosoft.co.th';

if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    if (strpos($referer, $allowedDomain) === false) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied: Invalid referer');
    }
} elseif (isset($_SERVER['HTTP_ORIGIN'])) {
    $origin = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
    if (strpos($origin, $allowedDomain) === false) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied: Invalid origin');
    }
} else {
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    if (strpos($host, $allowedDomain) === false) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access denied: Host mismatch');
    }
}

header('Content-Type: application/json');
$token    = isset($_GET['token']) ? $_GET['token'] : '';
$expected = md5('threat_current' . 'ThreatSecretKey999');
if ($token === $expected) {
    include dirname(dirname(dirname(__FILE__))) . '/include/conf.ini.php';
    include PATH_PLUGIN . '/db/db.php';
    include PATH_PLUGIN . '/config/global.php';
    $month  = date('Y-m');
    $db     = DB::singleton();
    $sql    = "SELECT name, COUNT(*) as amount FROM `" . _DBPREFIX_ . "threat` WHERE created_at LIKE '{$month}-%' GROUP BY name ORDER BY amount DESC";
    $aData  = $db->pager(__FUNCTION__, $sql, 0, 0);
    $result = [];
    $config = [
        'sqli'              => ['title' => 'SQL Injection'],
        'xss'               => ['title' => 'XSS Attack'],
        'lfi_rfi_traversal' => ['title' => 'Path Traversal'],
        'rce'               => ['title' => 'Command (RCE)'],
        'bad_bot'           => ['title' => 'Scanner Bots'],
        'malicious_file'    => ['title' => 'Malicious File']
    ];
    if (!empty($aData['data'])) {
        foreach ($aData['data'] as $row) {
            $result[$config[$row['name']]['title']] = (int)$row['amount'];
        }
    }
    echo json_encode(['data' => $result]);
} else {
    echo false;
}
