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
    $uploadId    = (int) ($_POST['uploadId'] ?? 0);
    $oldFilePath = trim($_POST['oldFilePath'] ?? '');

    if ($uploadId <= 0) {
        throw new RuntimeException('uploadId ไม่ถูกต้อง');
    }
    if ($oldFilePath !== '' && strpos($oldFilePath, '..') !== false) {
        throw new RuntimeException('oldFilePath ไม่ถูกต้อง');
    }

    $targetFolder = 'bigfiles/' . $uploadId;
    $upFile = new UpFile(PATH_UPLOAD . '/' . $targetFolder);
    $savedFileName = $upFile->handleUpload($_FILES['file'] ?? [], $_POST);

    if ($savedFileName === null) {
        jsonOut([
            'success' => true,
            'message' => 'กำลังรออัปโหลด chunk ที่เหลือ...',
        ]);
    }

    if ($oldFilePath !== '') {
        (new UpFile(PATH_UPLOAD))->deleteFile($oldFilePath);
    }

    $relPath = '/' . $targetFolder . '/' . $savedFileName;
    DB_UP('site_articles', ['bigfile' => $relPath], ['articles_id' => $uploadId]);

    jsonOut([
        'success'  => true,
        'fileName' => $relPath,
        'id'       => $uploadId,
        'message'  => 'อัปโหลดสำเร็จ',
    ]);
} catch (Throwable $e) {
    jsonOut(['success' => false, 'message' => $e->getMessage()], 400);
}
