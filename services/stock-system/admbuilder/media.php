<?php

/**
 * FILE: admbuilder/media.php
 * ROLE: AO Media Library backend (ac=list/upload/delete) สำหรับ CK5 — ใช้ UpFile plugin, pool = uploads/media
 * DEPENDS: plugins/uploadfile/UpFile.php
 * TABLES: -
 * TODO:
 *   - [ ] ต้องมีโฟลเดอร์ uploads/media/ เขียนได้; ดู AOBUILDER-THEME-GUIDE.md
 */

include dirname(dirname(__FILE__)) . '/admweb/mainApi.php';
include dirname(dirname(__FILE__)) . '/admweb/api/oApi.php';
include dirname(__FILE__) . '/config.php';
include dirname(__FILE__) . '/autoload.php';

header('Content-Type: application/json; charset=utf-8');

if (!ADMBUILDER::isLoginAdmin()) {
    echo json_encode(['error' => ['message' => 'Permission denied']]);
    exit;
}

require_once PATH_PLUGIN . '/uploadfile/UpFile.php';

$ac       = REQ_get('ac', 'request', 'str', '');
$mediaDir = PATH_UPLOAD . '/media';
$aExt     = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

if ($ac === 'list') {
    $files = [];
    if (is_dir($mediaDir)) {
        foreach (scandir($mediaDir) as $f) {
            if ($f === '.' || $f === '..') continue;
            if (!in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $aExt, true)) continue;
            $files[] = [
                'name'  => $f,
                'url'   => URL_UPLOAD . '/media/' . $f,
                'mtime' => @filemtime($mediaDir . '/' . $f) ?: 0,
            ];
        }
        usort($files, function ($a, $b) { return $b['mtime'] - $a['mtime']; });
    }
    echo json_encode(['ok' => true, 'files' => $files], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($ac === 'upload') {
    if (empty($_FILES['upload']) || $_FILES['upload']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => ['message' => 'อัปโหลดไม่สำเร็จ']]);
        exit;
    }
    $up  = new UpFile(PATH_UPLOAD);
    $rel = $up->uploadStandard($aExt, $_FILES['upload']['tmp_name'], $_FILES['upload']['name'], 'media');
    if ($rel === false) {
        echo json_encode(['error' => ['message' => 'ไฟล์ไม่รองรับ หรือบันทึกไม่ได้']]);
        exit;
    }
    echo json_encode(['url' => URL_UPLOAD . '/' . $rel], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($ac === 'delete') {
    $name = basename(REQ_get('name', 'post', 'str', ''));
    if ($name === '') {
        echo json_encode(['error' => ['message' => 'ชื่อไฟล์ไม่ถูกต้อง']]);
        exit;
    }
    $ok = (new UpFile(PATH_UPLOAD))->deleteFile('media/' . $name);
    echo json_encode(['ok' => (bool) $ok], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['error' => ['message' => 'unknown action']]);
