<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/mainApi.php';
require_once __DIR__ . '/UpFile.php';

function jsonOut(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $uploadId = (int) ($_POST['uploadId'] ?? 0);
    $filePath = trim($_POST['filePath'] ?? '');

    if ($uploadId <= 0) {
        throw new RuntimeException('uploadId ไม่ถูกต้อง');
    }
    if ($filePath === '') {
        throw new RuntimeException('filePath ไม่ถูกต้อง');
    }
    if (strpos($filePath, '..') !== false) {
        throw new RuntimeException('filePath ไม่ถูกต้อง');
    }

    $upFile  = new UpFile(PATH_UPLOAD);
    $deleted = $upFile->deleteFile($filePath);

    DB_UP('site_articles', ['bigfile' => ''], ['articles_id' => $uploadId]);

    jsonOut([
        'success' => true,
        'deleted' => $deleted,
        'message' => 'ลบไฟล์เรียบร้อย',
    ]);
} catch (Throwable $e) {
    jsonOut(['success' => false, 'message' => $e->getMessage()], 400);
}
