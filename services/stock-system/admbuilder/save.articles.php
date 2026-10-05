<?php
include dirname(dirname(__FILE__)) . '/admweb/mainApi.php';
include dirname(dirname(__FILE__)) . '/admweb/api/oApi.php';
include dirname(__FILE__) . '/config.php';
include dirname(__FILE__) . '/autoload.php';

if (!ADMBUILDER::isLoginAdmin()) {
    exit;
}

$oUserGet = oApi::getLoginData();
$id = REQ_get('id', 'request', 'int', 0);
$ac = REQ_get('ac', 'request', 'str', '');
$frm = REQ_get('frm', 'post', 'array', ['th']);
$keysname = REQ_get('keysname', 'request', 'str', '');

// ติ๊ก "ใช้เนื้อหาภาษาไทยกับทุกภาษา" → เอาข้อมูลแท็บ th ทับทุกภาษา ก่อนเข้า handler
$copyThAll = REQ_get('copyThAll', 'post', 'int', 0);
if ($copyThAll == 1 && isset($frm['th']) && is_array($frm['th'])) {
    foreach ($frm as $lk => $v) {
        $frm[$lk] = $frm['th'];
    }
}

if ($ac == 'add') {
    $icon = REQ_get('icon', 'file', 'str', '');
    $icon2 = REQ_get('icon2', 'file', 'str', '');
    $extra1 = REQ_get('extra1', 'post', 'str', '');
    $extra2 = REQ_get('extra2', 'post', 'str', '');
    $extra3 = REQ_get('extra3', 'post', 'str', '');
    $extra4 = REQ_get('extra4', 'post', 'str', '');
    $extra5 = REQ_get('extra5', 'post', 'str', '');
    $extra6 = REQ_get('extra6', 'post', 'str', '');
    $extra7 = REQ_get('extra7', 'post', 'str', '');
    $extra8 = REQ_get('extra8', 'post', 'str', '');
    $extra9 = REQ_get('extra9', 'post', 'str', '');
    $extra10 = REQ_get('extra10', 'post', 'str', '');
    $end_time = REQ_get('end_time', 'post', 'str', 0);
    $displaytime = REQ_get('displaytime', 'post', 'str', 0);
    $checkOption = REQ_get('checkOption', 'post', 'int', 0);
    $file_attach = REQ_get('file_attach', 'file', 'str', '');
    //$status = REQ_get('status', 'post', 'str', '') == 'on' ? 0 : 1;
    //$sort = DB_LIST('site_articles', ['keysname' => $keysname]);

    $icon = isset($icon['error']) && $icon['error'] === UPLOAD_ERR_OK ? Func_uploads_file($icon, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme') : '';
    $icon2 = isset($icon2['error']) && $icon2['error'] === UPLOAD_ERR_OK ? Func_uploads_file($icon2, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme') : '';
    $file = isset($file_attach['error']) && $file_attach['error'] === UPLOAD_ERR_OK ? Func_uploads_file($file_attach, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar'], 'theme') : '';

    $articleData = [
        'user_id'   => (int)$oUserGet->user_id,
        'group_id'  => REQ_get('group_id', 'post', 'int', 0),
        'keysname'  => htmlspecialchars(trim($keysname ?? ''), ENT_QUOTES, 'UTF-8'),
        'status'    => 0, //(int)$status,
        'add_time'  => (int)_TIME_,
        'end_time'    => (int)strtotime($end_time),
        'sorttime'  => 0, //(int)($sort['num_rows'] ?? 0) + 1,
        'displaytime' => (int)strtotime($displaytime),
        'icon' => $icon ?? '',
        'icon2' => $icon2 ?? '',
        'file_attach' => $file ?? '',
        'checkOption' => (int)$checkOption,
        'extra1'  => htmlspecialchars(trim($extra1 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra2'  => htmlspecialchars(trim($extra2 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra3'  => htmlspecialchars(trim($extra3 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra4'  => htmlspecialchars(trim($extra4 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra5'  => htmlspecialchars(trim($extra5 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra6'  => htmlspecialchars(trim($extra6 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra7'  => htmlspecialchars(trim($extra7 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra8'  => htmlspecialchars(trim($extra8 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra9'  => htmlspecialchars(trim($extra9 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra10' => htmlspecialchars(trim($extra10 ?? ''), ENT_QUOTES, 'UTF-8'),
    ];


    $aid = DB_ADD('site_articles', $articleData);

    foreach ($frm as $lang => $data) {
        if (!isset($aConfig['language'][$lang])) continue;

        $contentIcon = REQ_get('content_icon', 'file', 'array', []);
        $contentAttach = REQ_get('contentAttach', 'file', 'array', []);
        if (isset($contentIcon['error'][$lang]) && $contentIcon['error'][$lang] === UPLOAD_ERR_OK) {
            $iconData = [
                'name'     => $contentIcon['name'][$lang],
                'type'     => $contentIcon['type'][$lang],
                'tmp_name' => $contentIcon['tmp_name'][$lang],
                'error'    => $contentIcon['error'][$lang],
                'size'     => $contentIcon['size'][$lang],
            ];
            $contentIcon2 = Func_uploads_file($iconData, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
        }

        if (isset($contentAttach['error'][$lang]) && $contentAttach['error'][$lang] === UPLOAD_ERR_OK) {
            $fileData = [
                'name'     => $contentAttach['name'][$lang],
                'type'     => $contentAttach['type'][$lang],
                'tmp_name' => $contentAttach['tmp_name'][$lang],
                'error'    => $contentAttach['error'][$lang],
                'size'     => $contentAttach['size'][$lang],
            ];
            $contentAttach2 = Func_uploads_file($fileData, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar'], 'theme');
        }

        $ogImg = REQ_get('og_image', 'file', 'array', []);
        $ogImage2 = '';
        if (isset($ogImg['error'][$lang]) && $ogImg['error'][$lang] === UPLOAD_ERR_OK) {
            $ogImage2 = Func_uploads_file([
                'name' => $ogImg['name'][$lang], 'type' => $ogImg['type'][$lang],
                'tmp_name' => $ogImg['tmp_name'][$lang], 'error' => $ogImg['error'][$lang], 'size' => $ogImg['size'][$lang],
            ], ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
        }

        $contentData = [
            'articles_id'  => $aid,
            'langkeys'     => $lang,
            'title'        => htmlspecialchars(trim($data['title'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'author'       => htmlspecialchars(trim($data['author'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'slug'         => htmlspecialchars(trim($data['slug'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'shortMessage' => htmlspecialchars(trim($data['shortMessage'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'content'      => ADMBUILDER::sanitizeHtml(trim($data['content'] ?? '')),
            'content2'      => ADMBUILDER::sanitizeHtml(trim($data['content2'] ?? '')),
            'content3'      => ADMBUILDER::sanitizeHtml(trim($data['content3'] ?? '')),
            'content4'      => ADMBUILDER::sanitizeHtml(trim($data['content4'] ?? '')),
            'content_extra1' => htmlspecialchars(trim($data['content_extra1'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'content_extra2' => htmlspecialchars(trim($data['content_extra2'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'content_extra3' => htmlspecialchars(trim($data['content_extra3'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'content_icon' => $contentIcon2 ?? '',
            'contentAttach' => $contentAttach2 ?? '',
            'keywords'         => htmlspecialchars(trim($data['keywords'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'meta_title'       => htmlspecialchars(trim($data['meta_title'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'meta_description' => htmlspecialchars(trim($data['meta_description'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'meta_robots'      => htmlspecialchars(trim($data['meta_robots'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'og_image'         => $ogImage2,
        ];
        DB_ADD('site_articles_content', $contentData);
    }
    echo 'ok'; // ช่วง 4: คืนสถานะ 200 ให้ editor ใน iframe อ่านผล
}

if ($ac == 'update') {
    $icon = REQ_get('icon', 'file', 'str', '');
    $icon2 = REQ_get('icon2', 'file', 'str', '');
    $extra1 = REQ_get('extra1', 'post', 'str', '');
    $extra2 = REQ_get('extra2', 'post', 'str', '');
    $extra3 = REQ_get('extra3', 'post', 'str', '');
    $extra4 = REQ_get('extra4', 'post', 'str', '');
    $extra5 = REQ_get('extra5', 'post', 'str', '');
    $extra6 = REQ_get('extra6', 'post', 'str', '');
    $extra7 = REQ_get('extra7', 'post', 'str', '');
    $extra8 = REQ_get('extra8', 'post', 'str', '');
    $extra9 = REQ_get('extra9', 'post', 'str', '');
    $extra10 = REQ_get('extra10', 'post', 'str', '');
    $end_time = REQ_get('end_time', 'post', 'str', 0);
    $displaytime = REQ_get('displaytime', 'post', 'str', 0);
    $checkOption = REQ_get('checkOption', 'post', 'int', 0);
    $file_attach = REQ_get('file_attach', 'file', 'str', '');
    //$status = REQ_get('status', 'post', 'str', '') == 'on' ? 0 : 1;
    $aData = DB_GET('site_articles', ['articles_id' => $id]);

    if (isset($icon['error']) && $icon['error'] === UPLOAD_ERR_OK) {
        if (!empty($aData['icon'])) {
            $oldPath = PATH_UPLOAD . '/' . $aData['icon'];
            if (file_exists($oldPath)) unlink($oldPath);
        }
        $icon =  Func_uploads_file($icon, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
    } else {
        $icon = $aData['icon'] ?? '';
    }

    if (isset($icon2['error']) && $icon2['error'] === UPLOAD_ERR_OK) {
        if (!empty($aData['icon2'])) {
            $oldPath = PATH_UPLOAD . '/' . $aData['icon2'];
            if (file_exists($oldPath)) unlink($oldPath);
        }
        $icon2 =  Func_uploads_file($icon2, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
    } else {
        $icon2 = $aData['icon2'] ?? '';
    }

    if (isset($file_attach['error']) && $file_attach['error'] === UPLOAD_ERR_OK) {
        if (!empty($aData['file_attach'])) {
            $oldPath = PATH_UPLOAD . '/' . $aData['file_attach'];
            if (file_exists($oldPath)) unlink($oldPath);
        }
        $file =  Func_uploads_file($file_attach, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar'], 'theme');
    } else {
        $file = $aData['file_attach'] ?? '';
    }

    $articleUpdate = [
        'user_id'   => (int)$oUserGet->user_id,
        //'status'    => (int)$status,
        'end_time'    => (int)strtotime($end_time),
        'displaytime' => (int)strtotime($displaytime),
        'icon' => $icon ?? '',
        'icon2' => $icon2 ?? '',
        'file_attach' => $file ?? '',
        'checkOption' => (int)$checkOption,
        'extra1'  => htmlspecialchars(trim($extra1 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra2'  => htmlspecialchars(trim($extra2 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra3'  => htmlspecialchars(trim($extra3 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra4'  => htmlspecialchars(trim($extra4 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra5'  => htmlspecialchars(trim($extra5 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra6'  => htmlspecialchars(trim($extra6 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra7'  => htmlspecialchars(trim($extra7 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra8'  => htmlspecialchars(trim($extra8 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra9'  => htmlspecialchars(trim($extra9 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra10' => htmlspecialchars(trim($extra10 ?? ''), ENT_QUOTES, 'UTF-8'),
    ];
    // ฟอร์มที่ไม่ได้เปิด isGroup จะไม่ส่ง group_id มา — ห้ามไปทับค่าเดิม
    if (isset($_POST['group_id'])) {
        $articleUpdate['group_id'] = REQ_get('group_id', 'post', 'int', 0);
    }
    DB_UP('site_articles', $articleUpdate, ['articles_id' => $id]);

    foreach ($frm as $lang => $data) {
        if (!isset($aConfig['language'][$lang])) continue;

        $aData = DB_GET('site_articles_content', ['articles_id' => $id, 'langkeys' => $lang]);
        $contentIcon = REQ_get('content_icon', 'file', 'array', []);
        $contentAttach = REQ_get('contentAttach', 'file', 'array', []);

        if (isset($contentIcon['error'][$lang]) && $contentIcon['error'][$lang] === UPLOAD_ERR_OK) {
            if (!empty($aData['content_icon'])) {
                $oldPath = PATH_UPLOAD . '/' . $aData['content_icon'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $iconData = [
                'name'     => $contentIcon['name'][$lang],
                'type'     => $contentIcon['type'][$lang],
                'tmp_name' => $contentIcon['tmp_name'][$lang],
                'error'    => $contentIcon['error'][$lang],
                'size'     => $contentIcon['size'][$lang],
            ];
            $contentIcon2 = Func_uploads_file($iconData, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
        } else {
            $contentIcon2 = $aData['content_icon'] ?? '';
        }

        if (isset($contentAttach['error'][$lang]) && $contentAttach['error'][$lang] === UPLOAD_ERR_OK) {
            if (!empty($aData['contentAttach'])) {
                $oldPath = PATH_UPLOAD . '/' . $aData['contentAttach'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $fileData = [
                'name'     => $contentAttach['name'][$lang],
                'type'     => $contentAttach['type'][$lang],
                'tmp_name' => $contentAttach['tmp_name'][$lang],
                'error'    => $contentAttach['error'][$lang],
                'size'     => $contentAttach['size'][$lang],
            ];
            $contentAttach2 = Func_uploads_file($fileData, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar'], 'theme');
        } else {
            $contentAttach2 = $aData['contentAttach'] ?? '';
        }

        $updateData = [];
        $fields = [
            'title',
            'author',
            'slug',
            'shortMessage',
            'content',
            'content2',
            'content3',
            'content4',
            'content_extra1',
            'content_extra2',
            'content_extra3',
            'keywords',
            'meta_title',
            'meta_description',
            'meta_robots'
        ];

        foreach ($fields as $f) {
            if (array_key_exists($f, $data)) {
                if (in_array($f, ['content', 'content2', 'content3', 'content4'])) {
                    $updateData[$f] = ADMBUILDER::sanitizeHtml(trim($data[$f]));
                } else {
                    $updateData[$f] = htmlspecialchars(trim($data[$f] ?? ''), ENT_QUOTES, 'UTF-8');
                }
            }
        }

        $ogImg = REQ_get('og_image', 'file', 'array', []);
        if (isset($ogImg['error'][$lang]) && $ogImg['error'][$lang] === UPLOAD_ERR_OK) {
            if (!empty($aData['og_image']) && is_file(PATH_UPLOAD . '/' . $aData['og_image'])) unlink(PATH_UPLOAD . '/' . $aData['og_image']);
            $updateData['og_image'] = Func_uploads_file([
                'name' => $ogImg['name'][$lang], 'type' => $ogImg['type'][$lang],
                'tmp_name' => $ogImg['tmp_name'][$lang], 'error' => $ogImg['error'][$lang], 'size' => $ogImg['size'][$lang],
            ], ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
        }
        if (!empty($contentIcon2)) $updateData['content_icon'] = $contentIcon2;
        if (!empty($contentAttach2)) $updateData['contentAttach'] = $contentAttach2;
        if (!empty($updateData)) {
            DB_UP('site_articles_content', $updateData, ['articles_id' => $id, 'langkeys' => $lang]);
        }
    }
    echo 'ok'; // ช่วง 4: คืนสถานะ 200 ให้ editor ใน iframe อ่านผล
}

if ($ac == 'delete') {
    $aData = DB_GET('site_articles', ['articles_id' => $id, 'keysname' => $keysname]);

    if (!empty($aData['icon'])) {
        $oldPath = PATH_UPLOAD . '/' . $aData['icon'];
        if (file_exists($oldPath)) unlink($oldPath);
    }

    if (!empty($aData['icon2'])) {
        $oldPath = PATH_UPLOAD . '/' . $aData['icon2'];
        if (file_exists($oldPath)) unlink($oldPath);
    }

    if (!empty($aData['file_attach'])) {
        $oldPath = PATH_UPLOAD . '/' . $aData['file_attach'];
        if (file_exists($oldPath)) unlink($oldPath);
    }

    foreach ($aConfig['language'] as $lang => $label) {
        $aData2 = DB_GET('site_articles_content', ['articles_id' => $id, 'langkeys' => $lang]);

        if (!empty($aData2['content_icon'])) {
            $oldPath = PATH_UPLOAD . '/' . $aData2['content_icon'];
            if (file_exists($oldPath)) unlink($oldPath);
        }

        if (!empty($aData2['contentAttach'])) {
            $oldPath = PATH_UPLOAD . '/' . $aData2['contentAttach'];
            if (file_exists($oldPath)) unlink($oldPath);
        }
    }

    DB_DEL('site_articles', ['articles_id' => $id]);
    DB_DEL('site_articles_content', ['articles_id' => $id]);
    echo 'ok'; // ช่วง 4: คืนสถานะ 200 ให้ editor ใน iframe อ่านผล
}

/* ===== หมวดหมู่ (site_articles_group) — คู่กับ option isGroup ของ AOSET ===== */

if ($ac == 'groupAdd') {
    $icon = REQ_get('icon', 'file', 'str', '');
    $img = REQ_get('img', 'file', 'str', '');
    $img2 = REQ_get('img2', 'file', 'str', '');
    $extra_group1 = REQ_get('extra_group1', 'post', 'str', '');
    $extra_group2 = REQ_get('extra_group2', 'post', 'str', '');
    $extra_group3 = REQ_get('extra_group3', 'post', 'str', '');
    $extra_group4 = REQ_get('extra_group4', 'post', 'str', '');
    $extra_group5 = REQ_get('extra_group5', 'post', 'str', '');

    $icon = isset($icon['error']) && $icon['error'] === UPLOAD_ERR_OK ? Func_uploads_file($icon, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme') : '';
    $img = isset($img['error']) && $img['error'] === UPLOAD_ERR_OK ? Func_uploads_file($img, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme') : '';
    $img2 = isset($img2['error']) && $img2['error'] === UPLOAD_ERR_OK ? Func_uploads_file($img2, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme') : '';

    // ต่อท้ายลำดับเดิม (จัดใหม่ได้ด้วยการลากในตารางหมวดหมู่)
    $aSortRow = DB_LIST('site_articles_group', ['keysname' => $keysname]);

    $gid = DB_ADD('site_articles_group', [
        'group_parent_id' => 0,
        'keysname'     => htmlspecialchars(trim($keysname ?? ''), ENT_QUOTES, 'UTF-8'),
        'img'          => $img ?? '',
        'img2'         => $img2 ?? '',
        'icon'         => $icon ?? '',
        'sort'         => (int)($aSortRow['num_rows'] ?? 0) + 1,
        'updatetime'   => (int)_TIME_,
        'extraOption'  => '',
        'status'       => 0,
        'extra_group1' => htmlspecialchars(trim($extra_group1 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra_group2' => htmlspecialchars(trim($extra_group2 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra_group3' => htmlspecialchars(trim($extra_group3 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra_group4' => htmlspecialchars(trim($extra_group4 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra_group5' => htmlspecialchars(trim($extra_group5 ?? ''), ENT_QUOTES, 'UTF-8'),
    ]);

    foreach ($frm as $lang => $data) {
        if (!isset($aConfig['language'][$lang])) continue;

        $ogImg = REQ_get('og_image', 'file', 'array', []);
        $ogImage2 = '';
        if (isset($ogImg['error'][$lang]) && $ogImg['error'][$lang] === UPLOAD_ERR_OK) {
            $ogImage2 = Func_uploads_file([
                'name' => $ogImg['name'][$lang], 'type' => $ogImg['type'][$lang],
                'tmp_name' => $ogImg['tmp_name'][$lang], 'error' => $ogImg['error'][$lang], 'size' => $ogImg['size'][$lang],
            ], ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
        }
        DB_ADD('site_articles_group_content', [
            'group_id'     => $gid,
            'langkeys'     => $lang,
            'group_name'   => htmlspecialchars(trim($data['group_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'detail'       => ADMBUILDER::sanitizeHtml(trim($data['detail'] ?? '')),
            'detailExtra1' => ADMBUILDER::sanitizeHtml(trim($data['detailExtra1'] ?? '')),
            'detailExtra2' => ADMBUILDER::sanitizeHtml(trim($data['detailExtra2'] ?? '')),
            'group_slug'   => htmlspecialchars(trim($data['group_slug'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'meta_title'       => htmlspecialchars(trim($data['meta_title'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'meta_description' => htmlspecialchars(trim($data['meta_description'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'meta_robots'      => htmlspecialchars(trim($data['meta_robots'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'og_image'         => $ogImage2,
        ]);
    }
    echo 'ok';
    exit;
}

if ($ac == 'groupUpdate') {
    $icon = REQ_get('icon', 'file', 'str', '');
    $img = REQ_get('img', 'file', 'str', '');
    $img2 = REQ_get('img2', 'file', 'str', '');
    $extra_group1 = REQ_get('extra_group1', 'post', 'str', '');
    $extra_group2 = REQ_get('extra_group2', 'post', 'str', '');
    $extra_group3 = REQ_get('extra_group3', 'post', 'str', '');
    $extra_group4 = REQ_get('extra_group4', 'post', 'str', '');
    $extra_group5 = REQ_get('extra_group5', 'post', 'str', '');
    $aGroup = DB_GET('site_articles_group', ['group_id' => $id, 'keysname' => $keysname]);

    if (!is_array($aGroup)) {
        http_response_code(404);
        exit('ไม่พบหมวดหมู่นี้');
    }

    if (isset($icon['error']) && $icon['error'] === UPLOAD_ERR_OK) {
        if (!empty($aGroup['icon'])) {
            $oldPath = PATH_UPLOAD . '/' . $aGroup['icon'];
            if (file_exists($oldPath)) unlink($oldPath);
        }
        $icon = Func_uploads_file($icon, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
    } else {
        $icon = $aGroup['icon'] ?? '';
    }

    if (isset($img['error']) && $img['error'] === UPLOAD_ERR_OK) {
        if (!empty($aGroup['img'])) {
            $oldPath = PATH_UPLOAD . '/' . $aGroup['img'];
            if (file_exists($oldPath)) unlink($oldPath);
        }
        $img = Func_uploads_file($img, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
    } else {
        $img = $aGroup['img'] ?? '';
    }

    if (isset($img2['error']) && $img2['error'] === UPLOAD_ERR_OK) {
        if (!empty($aGroup['img2'])) {
            $oldPath = PATH_UPLOAD . '/' . $aGroup['img2'];
            if (file_exists($oldPath)) unlink($oldPath);
        }
        $img2 = Func_uploads_file($img2, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
    } else {
        $img2 = $aGroup['img2'] ?? '';
    }

    DB_UP('site_articles_group', [
        'img'          => $img ?? '',
        'img2'         => $img2 ?? '',
        'icon'         => $icon ?? '',
        'updatetime'   => (int)_TIME_,
        'extra_group1' => htmlspecialchars(trim($extra_group1 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra_group2' => htmlspecialchars(trim($extra_group2 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra_group3' => htmlspecialchars(trim($extra_group3 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra_group4' => htmlspecialchars(trim($extra_group4 ?? ''), ENT_QUOTES, 'UTF-8'),
        'extra_group5' => htmlspecialchars(trim($extra_group5 ?? ''), ENT_QUOTES, 'UTF-8'),
    ], ['group_id' => $id]);

    foreach ($frm as $lang => $data) {
        if (!isset($aConfig['language'][$lang])) continue;

        $contentData = [
            'group_name'   => htmlspecialchars(trim($data['group_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'detail'       => ADMBUILDER::sanitizeHtml(trim($data['detail'] ?? '')),
            'detailExtra1' => ADMBUILDER::sanitizeHtml(trim($data['detailExtra1'] ?? '')),
            'detailExtra2' => ADMBUILDER::sanitizeHtml(trim($data['detailExtra2'] ?? '')),
            'group_slug'   => htmlspecialchars(trim($data['group_slug'] ?? ''), ENT_QUOTES, 'UTF-8'),
        ];

        $aCont = DB_GET('site_articles_group_content', ['group_id' => $id, 'langkeys' => $lang]);
        $ogImg = REQ_get('og_image', 'file', 'array', []);
        if (isset($ogImg['error'][$lang]) && $ogImg['error'][$lang] === UPLOAD_ERR_OK) {
            if (is_array($aCont) && !empty($aCont['og_image']) && is_file(PATH_UPLOAD . '/' . $aCont['og_image'])) unlink(PATH_UPLOAD . '/' . $aCont['og_image']);
            $contentData['og_image'] = Func_uploads_file([
                'name' => $ogImg['name'][$lang], 'type' => $ogImg['type'][$lang],
                'tmp_name' => $ogImg['tmp_name'][$lang], 'error' => $ogImg['error'][$lang], 'size' => $ogImg['size'][$lang],
            ], ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
        }
        $contentData['meta_title']       = htmlspecialchars(trim($data['meta_title'] ?? ''), ENT_QUOTES, 'UTF-8');
        $contentData['meta_description'] = htmlspecialchars(trim($data['meta_description'] ?? ''), ENT_QUOTES, 'UTF-8');
        $contentData['meta_robots']      = htmlspecialchars(trim($data['meta_robots'] ?? ''), ENT_QUOTES, 'UTF-8');
        if (is_array($aCont)) {
            DB_UP('site_articles_group_content', $contentData, ['group_id' => $id, 'langkeys' => $lang]);
        } else {
            $contentData['group_id'] = $id;
            $contentData['langkeys'] = $lang;
            DB_ADD('site_articles_group_content', $contentData);
        }
    }
    echo 'ok';
    exit;
}

if ($ac == 'groupDelete') {
    $aGroup = DB_GET('site_articles_group', ['group_id' => $id, 'keysname' => $keysname]);

    if (!is_array($aGroup)) {
        http_response_code(404);
        exit('ไม่พบหมวดหมู่นี้');
    }

    // ห้ามลบถ้ายังมีเนื้อหาอยู่ในหมวด — ต้องย้าย/ลบเนื้อหาออกก่อน
    $aInGroup = DB_LIST('site_articles', ['group_id' => $id, 'keysname' => $keysname]);
    if ((int)($aInGroup['num_rows'] ?? 0) > 0) {
        http_response_code(400);
        exit('ลบหมวดหมู่นี้ไม่ได้: ยังมีเนื้อหาอยู่ ' . (int)$aInGroup['num_rows'] . ' รายการ<br>กรุณาย้ายหรือลบเนื้อหาในหมวดนี้ออกก่อน');
    }

    if (!empty($aGroup['icon'])) {
        $oldPath = PATH_UPLOAD . '/' . $aGroup['icon'];
        if (file_exists($oldPath)) unlink($oldPath);
    }

    if (!empty($aGroup['img'])) {
        $oldPath = PATH_UPLOAD . '/' . $aGroup['img'];
        if (file_exists($oldPath)) unlink($oldPath);
    }

    DB_DEL('site_articles_group', ['group_id' => $id]);
    DB_DEL('site_articles_group_content', ['group_id' => $id]);
    echo 'ok';
    exit;
}

/* ===== subpost (site_articles_subpost) — รายการย่อยใต้ group/article ===== */

if ($ac == 'subpostAdd') {
    $group_id = REQ_get('group_id', 'post', 'int', 0);
    $articles_id = REQ_get('articles_id', 'post', 'int', 0);
    $subIcon = REQ_get('subIcon', 'file', 'str', '');
    $subIcon = isset($subIcon['error']) && $subIcon['error'] === UPLOAD_ERR_OK ? Func_uploads_file($subIcon, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme') : '';

    $aSortRow = ($articles_id > 0)
        ? DB_LIST('site_articles_subpost', ['articles_id' => $articles_id])
        : DB_LIST('site_articles_subpost', ['group_id' => $group_id]);

    $sid = DB_ADD('site_articles_subpost', [
        'group_id'      => $group_id,
        'articles_id'   => $articles_id,
        'keysname'      => htmlspecialchars(trim($keysname ?? ''), ENT_QUOTES, 'UTF-8'),
        'subIcon'       => $subIcon ?? '',
        'sort'          => (int)($aSortRow['num_rows'] ?? 0) + 1,
        'status'        => 0,
        'subAddDate'    => (int)_TIME_,
        'subModifyDate' => (int)_TIME_,
    ]);

    foreach ($frm as $lang => $data) {
        if (!isset($aConfig['language'][$lang])) continue;
        DB_ADD('site_articles_subpost_content', [
            'subpost_id' => $sid,
            'langkeys'   => $lang,
            'subtitle'   => htmlspecialchars(trim($data['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'subMessage' => htmlspecialchars(trim($data['subMessage'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'subText'    => ADMBUILDER::sanitizeHtml(trim($data['subText'] ?? '')),
        ]);
    }
    echo 'ok';
    exit;
}

if ($ac == 'subpostUpdate') {
    $subIcon = REQ_get('subIcon', 'file', 'str', '');
    $aSub = DB_GET('site_articles_subpost', ['subpost_id' => $id]);
    if (!is_array($aSub)) {
        http_response_code(404);
        exit('ไม่พบรายการนี้');
    }

    if (isset($subIcon['error']) && $subIcon['error'] === UPLOAD_ERR_OK) {
        if (!empty($aSub['subIcon'])) {
            $oldPath = PATH_UPLOAD . '/' . $aSub['subIcon'];
            if (file_exists($oldPath)) unlink($oldPath);
        }
        $subIcon = Func_uploads_file($subIcon, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
    } else {
        $subIcon = $aSub['subIcon'] ?? '';
    }

    DB_UP('site_articles_subpost', [
        'subIcon'       => $subIcon ?? '',
        'subModifyDate' => (int)_TIME_,
    ], ['subpost_id' => $id]);

    foreach ($frm as $lang => $data) {
        if (!isset($aConfig['language'][$lang])) continue;
        $contentData = [
            'subtitle'   => htmlspecialchars(trim($data['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'subMessage' => htmlspecialchars(trim($data['subMessage'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'subText'    => ADMBUILDER::sanitizeHtml(trim($data['subText'] ?? '')),
        ];
        $aCont = DB_GET('site_articles_subpost_content', ['subpost_id' => $id, 'langkeys' => $lang]);
        if (is_array($aCont)) {
            DB_UP('site_articles_subpost_content', $contentData, ['subpost_id' => $id, 'langkeys' => $lang]);
        } else {
            $contentData['subpost_id'] = $id;
            $contentData['langkeys'] = $lang;
            DB_ADD('site_articles_subpost_content', $contentData);
        }
    }
    echo 'ok';
    exit;
}

if ($ac == 'subpostDelete') {
    $aSub = DB_GET('site_articles_subpost', ['subpost_id' => $id]);
    if (!is_array($aSub)) {
        http_response_code(404);
        exit('ไม่พบรายการนี้');
    }
    if (!empty($aSub['subIcon'])) {
        $oldPath = PATH_UPLOAD . '/' . $aSub['subIcon'];
        if (file_exists($oldPath)) unlink($oldPath);
    }
    DB_DEL('site_articles_subpost', ['subpost_id' => $id]);
    DB_DEL('site_articles_subpost_content', ['subpost_id' => $id]);
    echo 'ok';
    exit;
}

if ($ac == 'updatepage') {
    $oUserGet = oApi::getLoginData();
    $id = REQ_get('id', 'request', 'int', 0);
    $frm = REQ_get('frm', 'post', 'array', []);
    $options = json_decode($_POST['options'], true);

    $icon = REQ_get('icon', 'file', 'str', '');
    $icon2 = REQ_get('icon2', 'file', 'str', '');
    $extra1 = REQ_get('extra1', 'post', 'str', '');
    $extra2 = REQ_get('extra2', 'post', 'str', '');
    $extra3 = REQ_get('extra3', 'post', 'str', '');
    $extra4 = REQ_get('extra4', 'post', 'str', '');
    $extra5 = REQ_get('extra5', 'post', 'str', '');
    $extra6 = REQ_get('extra6', 'post', 'str', '');
    $extra7 = REQ_get('extra7', 'post', 'str', '');
    $extra8 = REQ_get('extra8', 'post', 'str', '');
    $extra9 = REQ_get('extra9', 'post', 'str', '');
    $extra10 = REQ_get('extra10', 'post', 'str', '');
    $end_time = REQ_get('end_time', 'post', 'str', 0);
    $displaytime = REQ_get('displaytime', 'post', 'str', 0);
    $checkOption = REQ_get('checkOption', 'post', 'int', 0);
    $file_attach = REQ_get('file_attach', 'file', 'str', '');
    $status = REQ_get('status', 'post', 'str', '') == 'on' ? 0 : 1;
    $aData = DB_GET('site_articles', ['articles_id' => $id]);

    if (isset($options['isIcon'])) {
        if (isset($icon['error']) && $icon['error'] === UPLOAD_ERR_OK) {
            if (!empty($aData['icon'])) {
                $oldPath = PATH_UPLOAD . '/' . $aData['icon'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $icon = Func_uploads_file($icon, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
        } else {
            $icon = $aData['icon'] ?? '';
        }
    }

    if (isset($options['isIcon2'])) {
        if (isset($icon2['error']) && $icon2['error'] === UPLOAD_ERR_OK) {
            if (!empty($aData['icon2'])) {
                $oldPath = PATH_UPLOAD . '/' . $aData['icon2'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $icon2 = Func_uploads_file($icon2, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
        } else {
            $icon2 = $aData['icon2'] ?? '';
        }
    }

    if (isset($options['isfileAttach'])) {
        if (isset($file_attach['error']) && $file_attach['error'] === UPLOAD_ERR_OK) {
            if (!empty($aData['file_attach'])) {
                $oldPath = PATH_UPLOAD . '/' . $aData['file_attach'];
                if (file_exists($oldPath)) unlink($oldPath);
            }
            $file = Func_uploads_file(
                $file_attach,
                ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar'],
                'theme'
            );
        } else {
            $file = $aData['file_attach'] ?? '';
        }
    }

    $updateData = [];

    if (isset($options['isStatus'])) {
        $updateData['status'] = (int)$status;
    }
    if (isset($options['isDisplayTime'])) {
        $updateData['displaytime'] = (int)strtotime($displaytime);
    }
    if (isset($options['isEndTime'])) {
        $updateData['end_time'] = (int)strtotime($end_time);
    }
    if (isset($options['isCheckOption'])) {
        $updateData['checkOption'] = (int)$checkOption;
    }
    if (isset($options['isIcon'])) {
        $updateData['icon'] = $icon ?? '';
    }
    if (isset($options['isIcon2'])) {
        $updateData['icon2'] = $icon2 ?? '';
    }
    if (isset($options['isfileAttach'])) {
        $updateData['file_attach'] = $file ?? '';
    }

    for ($i = 1; $i <= 10; $i++) {
        $key = "isExtra{$i}";
        if (isset($options[$key])) {
            $updateData["extra{$i}"] = htmlspecialchars(trim(${"extra{$i}"} ?? ''), ENT_QUOTES, 'UTF-8');
        }
    }

    $updateData['user_id'] = (int)$oUserGet->user_id;
    if (!empty($updateData)) {
        DB_UP('site_articles', $updateData, ['articles_id' => $id]);
    }

    foreach ($frm as $lang => $data) {
        if (!isset($aConfig['language'][$lang])) continue;

        $aData = DB_GET('site_articles_content', ['articles_id' => $id, 'langkeys' => $lang]);
        $contentIcon = REQ_get('content_icon', 'file', 'array', []);
        $contentAttach = REQ_get('contentAttach', 'file', 'array', []);

        $updateContent = [];

        if (isset($options['isContentIcon'])) {
            if (isset($contentIcon['error'][$lang]) && $contentIcon['error'][$lang] === UPLOAD_ERR_OK) {
                if (!empty($aData['content_icon'])) {
                    $oldPath = PATH_UPLOAD . '/' . $aData['content_icon'];
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $iconData = [
                    'name'     => $contentIcon['name'][$lang],
                    'type'     => $contentIcon['type'][$lang],
                    'tmp_name' => $contentIcon['tmp_name'][$lang],
                    'error'    => $contentIcon['error'][$lang],
                    'size'     => $contentIcon['size'][$lang],
                ];
                $updateContent['content_icon'] = Func_uploads_file(
                    $iconData,
                    ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                    'theme'
                );
            } else {
                $updateContent['content_icon'] = $aData['content_icon'] ?? '';
            }
        }

        if (isset($options['isContentAttach'])) {
            if (isset($contentAttach['error'][$lang]) && $contentAttach['error'][$lang] === UPLOAD_ERR_OK) {
                if (!empty($aData['contentAttach'])) {
                    $oldPath = PATH_UPLOAD . '/' . $aData['contentAttach'];
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $fileData = [
                    'name'     => $contentAttach['name'][$lang],
                    'type'     => $contentAttach['type'][$lang],
                    'tmp_name' => $contentAttach['tmp_name'][$lang],
                    'error'    => $contentAttach['error'][$lang],
                    'size'     => $contentAttach['size'][$lang],
                ];
                $updateContent['contentAttach'] = Func_uploads_file(
                    $fileData,
                    ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar'],
                    'theme'
                );
            } else {
                $updateContent['contentAttach'] = $aData['contentAttach'] ?? '';
            }
        }

        if (isset($options['isTitle'])) {
            $updateContent['title'] = htmlspecialchars(trim($data['title'] ?? ''), ENT_QUOTES, 'UTF-8');
        }

        if (isset($options['isAuthor'])) {
            $updateContent['author'] = htmlspecialchars(trim($data['author'] ?? ''), ENT_QUOTES, 'UTF-8');
        }

        if (isset($options['isSlug'])) {
            $updateContent['slug'] = htmlspecialchars(trim($data['slug'] ?? ''), ENT_QUOTES, 'UTF-8');
        }

        if (isset($options['isShortMessage'])) {
            $updateContent['shortMessage'] = htmlspecialchars(trim($data['shortMessage'] ?? ''), ENT_QUOTES, 'UTF-8');
        }

        if (isset($options['isContent'])) {
            $updateContent['content'] = ADMBUILDER::sanitizeHtml(trim($data['content'] ?? ''));
        }

        if (isset($options['isContent2'])) {
            $updateContent['content2'] = ADMBUILDER::sanitizeHtml(trim($data['content2'] ?? ''));
        }

        if (isset($options['isContent3'])) {
            $updateContent['content3'] = ADMBUILDER::sanitizeHtml(trim($data['content3'] ?? ''));
        }

        if (isset($options['isContent4'])) {
            $updateContent['content4'] = ADMBUILDER::sanitizeHtml(trim($data['content4'] ?? ''));
        }

        if (isset($options['isContentExtra1'])) {
            $updateContent['content_extra1'] = htmlspecialchars(trim($data['content_extra1'] ?? ''), ENT_QUOTES, 'UTF-8');
        }

        if (isset($options['isContentExtra2'])) {
            $updateContent['content_extra2'] = htmlspecialchars(trim($data['content_extra2'] ?? ''), ENT_QUOTES, 'UTF-8');
        }

        if (isset($options['isContentExtra3'])) {
            $updateContent['content_extra3'] = htmlspecialchars(trim($data['content_extra3'] ?? ''), ENT_QUOTES, 'UTF-8');
        }

        if (!empty($updateContent)) {
            DB_UP('site_articles_content', $updateContent, [
                'articles_id' => $id,
                'langkeys' => $lang
            ]);
        }
    }
}

if ($ac == 'sort') {
    $sort = REQ_get('sort', 'post', 'array', []);
    if (!empty($sort)) {
        foreach ($sort as $k => $v) {
            DB_UP('site_articles', [
                'user_id'  => (int)$oUserGet->user_id,
                'sorttime' => (int)$v ?? 0,
            ], ['articles_id' => $k]);
        }
    }
    header('HX-Trigger: reloadPage');
    exit;
}
