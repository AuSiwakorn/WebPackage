<?php
include dirname(dirname(__FILE__)) . '/admweb/mainApi.php';
include dirname(dirname(__FILE__)) . '/admweb/api/oApi.php';
include dirname(__FILE__) . '/config.php';
include dirname(__FILE__) . '/autoload.php';

if (!ADMBUILDER::isLoginAdmin()) {
    exit;
}

$oUserGet = oApi::getLoginData();
$ac = REQ_get('ac', 'request', 'str', '');

/**
 * แปลงรหัส error ของ $_FILES เป็นข้อความไทย — ไม่งั้นอัปโหลดพลาดแล้วเงียบ ผู้ใช้ไม่รู้สาเหตุ
 */
function aoUploadErrText($code)
{
    switch ((int) $code) {
        case UPLOAD_ERR_INI_SIZE:
            return 'ไฟล์ใหญ่เกิน upload_max_filesize ของเซิร์ฟเวอร์ (' . ini_get('upload_max_filesize') . ') — ย่อรูปก่อนอัปโหลด';
        case UPLOAD_ERR_FORM_SIZE:
            return 'ไฟล์ใหญ่เกินที่ฟอร์มกำหนด';
        case UPLOAD_ERR_PARTIAL:
            return 'อัปโหลดไม่ครบไฟล์ — ลองใหม่อีกครั้ง';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'เซิร์ฟเวอร์ไม่มีโฟลเดอร์ชั่วคราวสำหรับอัปโหลด';
        case UPLOAD_ERR_CANT_WRITE:
            return 'เซิร์ฟเวอร์เขียนไฟล์ลงดิสก์ไม่ได้';
        case UPLOAD_ERR_EXTENSION:
            return 'มี PHP extension บล็อกการอัปโหลดไว้';
        default:
            return 'อัปโหลดไม่สำเร็จ (รหัส ' . (int) $code . ')';
    }
}

if ($ac == 'savebox') {
    // ฟอร์มส่งค่ามาแยกตามภาษา: frm[<langkey>][field] และไฟล์ img[<langkey>]
    // เก็บลง webbuilder คนละแถวต่อภาษา คีย์ = <keysname><langkey>
    // (ธีมอ่านด้วย AOGET($keysname . _LANG_, ...) ได้เหมือนเดิม)
    $keysname = REQ_get('keysname', 'request', 'str', '');
    $frm      = REQ_get('frm', 'post', 'array', []);
    $imgAll   = REQ_get('img', 'file', 'array', []);
    $aLangs   = ADMBUILDER::AOLANGS();

    if (trim($keysname) === '' || !is_array($frm)) {
        echo 'invalid request.';
        exit;
    }

    // นามสกุลที่ยอมให้อัปโหลด — ต้องตรงกับ save.articles.php (ธีมนี้ใช้ .webp เป็นหลัก)
    $aAllowExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $aUpErr    = array();   // เหตุผลที่รูปไม่เข้า — ต้องแจ้งกลับ ห้ามเงียบ

    // ตรวจว่าฟอร์มส่งไฟล์มาแบบ multipart จริงไหม
    // ถ้า htmx ส่งแบบ urlencoded (ขาด hx-encoding) ช่อง img จะโผล่ใน $_POST เป็นข้อความ "[object File]" แทน $_FILES
    $imgPost = REQ_get('img', 'post', 'array', array());
    if (empty($imgAll) && !empty($imgPost)) {
        $aUpErr[] = 'ฟอร์มไม่ได้ส่งไฟล์แบบ multipart (ขาด hx-encoding) — อัปโหลดไฟล์ admbuilder/views/inner.model.php เวอร์ชันล่าสุดขึ้นเซิร์ฟเวอร์';
    }

    foreach ($frm as $lk => $data) {
        if (!isset($aLangs[$lk]) || !is_array($data)) {
            continue;
        }

        $rowKey = $keysname . $lk;
        $aData  = DB_GET('webbuilder', ['web_keysname' => $rowKey]);
        $aData  = is_array($aData) ? $aData : [];

        // ไฟล์รูปของภาษานี้ (โครง $_FILES แบบ array: name/tmp_name/... แยกตาม key)
        $oldImg  = $aData['web_img'] ?? '';
        $path    = $oldImg;
        $errCode = isset($imgAll['error'][$lk]) ? $imgAll['error'][$lk] : UPLOAD_ERR_NO_FILE;

        if ($errCode === UPLOAD_ERR_OK) {
            $imgOne = [
                'name'     => $imgAll['name'][$lk],
                'type'     => $imgAll['type'][$lk],
                'tmp_name' => $imgAll['tmp_name'][$lk],
                'error'    => $imgAll['error'][$lk],
                'size'     => $imgAll['size'][$lk],
            ];
            // อัปโหลดให้สำเร็จก่อน แล้วค่อยลบรูปเดิม — กันรูปเดิมหายเมื่อไฟล์ใหม่ถูกปฏิเสธ
            $newPath = ADMBUILDER::AOUPFILE($imgOne, $aAllowExt, 'theme');
            if ($newPath !== '') {
                if ($oldImg !== '' && is_file(PATH_UPLOAD . '/' . $oldImg)) {
                    unlink(PATH_UPLOAD . '/' . $oldImg);
                }
                $path = $newPath;
            } else {
                $ext = strtolower(pathinfo($imgOne['name'], PATHINFO_EXTENSION));
                $aUpErr[] = '[' . $lk . '] ' . (!in_array($ext, $aAllowExt, true)
                    ? 'ไม่รองรับไฟล์ .' . $ext . ' (รองรับ: ' . implode(', ', $aAllowExt) . ')'
                    : 'เขียนไฟล์ลง ' . PATH_UPLOAD . '/theme ไม่ได้ — ตรวจสิทธิ์โฟลเดอร์');
            }
        } elseif ($errCode !== UPLOAD_ERR_NO_FILE) {
            $aUpErr[] = '[' . $lk . '] ' . aoUploadErrText($errCode);
        }

        $a = [
            'web_title'   => htmlspecialchars(trim($data['title'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'web_desc'    => htmlspecialchars(trim($data['desc'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'web_link'    => htmlspecialchars(trim($data['link'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'web_img'     => $path,
            'web_img_alt' => htmlspecialchars(trim($data['imgAlt'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'web_minibox' => ADMBUILDER::sanitizeHtml(trim($data['minibox'] ?? '')),
            'web_content' => ADMBUILDER::sanitizeHtml(trim($data['content'] ?? '')),
            'web_extra1'  => htmlspecialchars(trim($data['extra1'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'web_extra2'  => htmlspecialchars(trim($data['extra2'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'user_id'     => $oUserGet->user_id,
            'web_uptime'  => date('Y-m-d H:i:s')
        ];

        if (!empty($aData)) {
            DB_UP('webbuilder', $a, ['web_keysname' => $rowKey]);
        } else {
            $a['web_keysname'] = $rowKey;
            DB_ADD('webbuilder', $a);
        }
    }

    if (!empty($aUpErr)) {
        // ข้อความบันทึกแล้ว แต่รูปไม่เข้า — ตอบ 4xx ให้ editor.php เด้งแจ้งสาเหตุจริง
        // (htmx ไม่ swap เมื่อเป็น 4xx ฟอร์มจึงยังอยู่ ข้อมูลที่พิมพ์ไว้ไม่หาย)
        http_response_code(422);
        echo 'บันทึกข้อความเรียบร้อย แต่รูปยังไม่เข้า:<br>' . implode('<br>', $aUpErr);
        exit;
    }

    echo 'save success.';
    exit;
} elseif ($ac == 'savemeta') {
    $meta_title   = REQ_get('meta_title', 'post', 'str', '');
    $meta_desc    = REQ_get('meta_description', 'post', 'str', '');
    $meta_keyword = REQ_get('meta_keywords', 'post', 'str', '');
    $keysname     = REQ_get('keysname', 'request', 'str', '');
    $savelang     = REQ_get('savelang', 'request', 'str', '');
    $robots       = REQ_get('robots', 'request', 'str', 'index, follow');
    $author       = REQ_get('author', 'request', 'str', '');

    $aData   = DB_GET('site_metatags', ['meta_key' => $keysname, 'lang' => $savelang]);
    $oldIcon = isset($aData['icon']) ? $aData['icon'] : '';

    // OG image (อัปเฉพาะเมื่อมีไฟล์) — เก็บ path ลงคอลัมน์ icon
    $iconPath = $oldIcon;
    $fileMeta = REQ_get('filemeta', 'file', 'array', []);
    if (isset($fileMeta['error']) && $fileMeta['error'] === UPLOAD_ERR_OK) {
        $newPath = ADMBUILDER::AOUPFILE($fileMeta, ['jpg', 'jpeg', 'png', 'gif', 'webp'], 'theme');
        if ($newPath !== '') {
            if ($oldIcon !== '' && is_file(PATH_UPLOAD . '/' . $oldIcon)) {
                unlink(PATH_UPLOAD . '/' . $oldIcon);
            }
            $iconPath = $newPath;
        }
    }

    $aSave = [
        'title'       => htmlspecialchars(trim($meta_title), ENT_QUOTES, 'UTF-8'),
        'description' => htmlspecialchars(trim($meta_desc), ENT_QUOTES, 'UTF-8'),
        'keywords'    => htmlspecialchars(trim($meta_keyword), ENT_QUOTES, 'UTF-8'),
        'robots'      => htmlspecialchars(trim($robots), ENT_QUOTES, 'UTF-8'),
        'googlebot'   => htmlspecialchars(trim($robots), ENT_QUOTES, 'UTF-8'),
        'author'      => htmlspecialchars(trim($author), ENT_QUOTES, 'UTF-8'),
        'icon'        => $iconPath,
    ];

    if (isset($aData['meta_id']) && !empty($aData['meta_id'])) {
        DB_UP('site_metatags', $aSave, ['meta_key' => $keysname, 'lang' => $savelang]);
    } else {
        $aSave['meta_key'] = trim($keysname);
        $aSave['lang']     = $savelang;
        DB_ADD('site_metatags', $aSave);
    }
    // ผลบันทึกแสดงผ่าน Swal กลาง panel (js.php) — ไม่ echo ลง #responseMeta
} elseif ($ac == 'saveschema') {
    // ⚠️ ใช้ 'data' (raw) — ห้าม 'str' เพราะ _input_validate_str ลบ " ทิ้ง JSON พัง
    $schema_json = REQ_get('schema_json', 'post', 'data', '');
    $keysname    = REQ_get('keysname', 'request', 'str', '');
    $savelang    = REQ_get('savelang', 'request', 'str', '');

    $raw   = trim($schema_json);
    $store = '';
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(422);
            echo 'JSON ไม่ถูกต้อง: ' . json_last_error_msg() . ' — ตรวจวงเล็บ/comma แล้วบันทึกใหม่';
            exit;
        }
        $store = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    $aData = DB_GET('site_metatags', ['meta_key' => $keysname, 'lang' => $savelang]);
    if (isset($aData['meta_id']) && !empty($aData['meta_id'])) {
        DB_UP('site_metatags', ['schema_json' => $store], ['meta_id' => $aData['meta_id']]);
    } else {
        DB_ADD('site_metatags', [
            'meta_key'    => trim($keysname),
            'schema_json' => $store,
            'lang'        => $savelang,
        ]);
    }
    // ผลบันทึกแสดงผ่าน Swal กลาง panel (js.php) — ไม่ echo ลง #responseMeta
} elseif ($ac == 'saveconfig') {
    $aKeysSave = REQ_get('keys', 'post', 'array', '');
    foreach ($aKeysSave as $key => $value) {
        $aData = DB_GET('site_configs', ['keywords' => $key]);
        $value = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        if (isset($aData['conf_id ']) && !empty($aData['conf_id '])) {
            DB_UP('site_configs', ['val' =>  $value], ['keywords' => $key]);
        } else {
            $a = ['keywords' => trim($key), 'val' =>  $value];
            DB_ADD('site_configs', $a);
        }
    }
} elseif ($ac == 'sortarticles') {
    $aSort = REQ_get('aSort', 'post', 'array', '');
    foreach ($aSort as $articles_id => $sorttime) {
        DB_UP('site_articles', ['sorttime' =>  $sorttime], ['articles_id' => $articles_id]);
    }
} elseif ($ac == 'sortgroups') {
    $aSort = REQ_get('aSort', 'post', 'array', '');
    foreach ($aSort as $group_id => $sort) {
        DB_UP('site_articles_group', ['sort' => (int)$sort], ['group_id' => $group_id]);
    }
} elseif ($ac == 'sortsubpost') {
    $aSort = REQ_get('aSort', 'post', 'array', '');
    foreach ($aSort as $subpost_id => $sort) {
        DB_UP('site_articles_subpost', ['sort' => (int)$sort], ['subpost_id' => (int)$subpost_id]);
    }
} else {
    if (!oApi::is_Login() || !oApi::is_Admin()) {
        http_response_code(403);
        exit('Permission denied');
    } else {
        http_response_code(403);
        exit('action is not viewable');
    }
}
