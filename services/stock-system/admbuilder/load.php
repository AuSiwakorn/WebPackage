<?php
include dirname(dirname(__FILE__)) . '/admweb/mainApi.php';
include dirname(dirname(__FILE__)) . '/admweb/api/oApi.php';
include dirname(__FILE__) . '/config.php';
include dirname(__FILE__) . '/autoload.php';

define("_LANG_", ($lang) ? $lang : DEFAULT_LANGEUAGE);

if (!ADMBUILDER::isLoginAdmin()) {
    exit;
}

$ac = REQ_get('ac', 'request', 'str', '');
$keysname = REQ_get('keysname', 'request', 'str', '');
$clang = REQ_get('lang', 'get', 'str', 'th');

if ($ac == 'textbox') {
    // โหลดค่าเดิมของทุกภาษา (คนละแถวใน webbuilder: <keysname><langkey>)
    $aLangs = ADMBUILDER::AOLANGS();
    $aBox   = ADMBUILDER::AOBOXDATA($keysname);

    $data = REQ_get('data', 'request', 'str', '');
    if (!empty($data)) {
        $json = base64_decode($data);
        $vals = json_decode($json, true);
        $isImg     = $vals['isImg'] ?? '';
        $isDesc    = $vals['isDesc'] ?? '';
        $isLink    = $vals['isLink'] ?? '';
        $isTitle   = $vals['isTitle'] ?? '';
        $isMiniBox = $vals['isMiniBox'] ?? '';
        $isContent = $vals['isContent'] ?? '';
        $isImgAlt = $vals['isImgAlt'] ?? '';
        $isExtra1 = $vals['isExtra1'] ?? '';
        $isExtra2 = $vals['isExtra2'] ?? '';
    }

    include __DIR__ . '/views/inner.model.php';
} elseif ($ac == 'articlesEdit') {
    $id = REQ_get('id', 'request', 'int', '');
    $size = REQ_get('size', 'request', 'str', '');
    $inner = REQ_get('inner', 'request', 'str', '');
    $gid = REQ_get('gid', 'request', 'int', 0); // หมวดต้นทาง — ใช้กับปุ่มกลับ/ค่าเริ่มต้นของ dropdown หมวด
    $options = REQ_get('data', 'request', 'str', '');
    $data = REQ_get('data', 'request', 'str', '');



    if (!empty($data)) {
        $json = base64_decode($data);
        $vals = json_decode($json, true);

        $isStatus        = $vals['isStatus'] ?? '';
        $isDisplayTime   = $vals['isDisplayTime'] ?? '';
        $isEndTime       = $vals['isEndTime'] ?? '';
        $isCheckOption   = $vals['isCheckOption'] ?? [];
        $isIcon          = $vals['isIcon'] ?? '';
        $isIcon2         = $vals['isIcon2'] ?? '';
        $isfileAttach    = $vals['isfileAttach'] ?? '';
        $isExtra1        = $vals['isExtra1'] ?? '';
        $isExtra2        = $vals['isExtra2'] ?? '';
        $isExtra3        = $vals['isExtra3'] ?? '';
        $isExtra4        = $vals['isExtra4'] ?? '';
        $isExtra5        = $vals['isExtra5'] ?? '';
        $isExtra6        = $vals['isExtra6'] ?? '';
        $isExtra7        = $vals['isExtra7'] ?? '';
        $isExtra8        = $vals['isExtra8'] ?? '';
        $isExtra9        = $vals['isExtra9'] ?? '';
        $isExtra10       = $vals['isExtra10'] ?? '';
        $isTitle         = $vals['isTitle'] ?? '';
        $isAuthor        = $vals['isAuthor'] ?? '';
        $isSlug          = $vals['isSlug'] ?? '';
        $isShortMessage  = $vals['isShortMessage'] ?? '';
        $isContent       = $vals['isContent'] ?? '';
        $isContent2      = $vals['isContent2'] ?? '';
        $isContent3      = $vals['isContent3'] ?? '';
        $isContent4      = $vals['isContent4'] ?? '';
        $isContentExtra1 = $vals['isContentExtra1'] ?? '';
        $isContentExtra2 = $vals['isContentExtra2'] ?? '';
        $isContentExtra3 = $vals['isContentExtra3'] ?? '';
        $isContentIcon   = $vals['isContentIcon'] ?? '';
        $isContentAttach = $vals['isContentAttach'] ?? '';
    }


    $showMain = (
        $isStatus != ''
        || $isDisplayTime != ''
        || $isEndTime != ''
        || !empty($isCheckOption)
        || $isIcon != ''
        || $isIcon2 != ''
        || $isfileAttach != ''
        || $isExtra1 != ''
        || $isExtra2 != ''
        || $isExtra3 != ''
        || $isExtra4 != ''
        || $isExtra5 != ''
        || $isExtra6 != ''
        || $isExtra7 != ''
        || $isExtra8 != ''
        || $isExtra9 != ''
        || $isExtra10 != ''
    ) ? true : false;

    include __DIR__ . '/views/inner.articles.edit.php';
} elseif ($ac == 'articles') {
    $size = REQ_get('size', 'request', 'str', '');
    $inner = REQ_get('inner', 'request', 'str', '');
    $gid = REQ_get('gid', 'request', 'int', 0); // ตัวกรองหมวดหมู่ของหน้า list (0 = ทั้งหมด)
    $options = REQ_get('data', 'request', 'str', '');
    //$data = REQ_get('data', 'request', 'str', '');
    if (!empty($options)) {
        //$options = $data;
        $json = base64_decode($options);
        $vals = json_decode($json, true);
        $isStatus        = $vals['isStatus'] ?? '';
        $isDisplayTime   = $vals['isDisplayTime'] ?? '';
        $isEndTime       = $vals['isEndTime'] ?? '';
        $isCheckOption   = $vals['isCheckOption'] ?? [];
        $isIcon          = $vals['isIcon'] ?? '';
        $isIcon2         = $vals['isIcon2'] ?? '';
        $isfileAttach    = $vals['isfileAttach'] ?? '';
        $isExtra1        = $vals['isExtra1'] ?? '';
        $isExtra2        = $vals['isExtra2'] ?? '';
        $isExtra3        = $vals['isExtra3'] ?? '';
        $isExtra4        = $vals['isExtra4'] ?? '';
        $isExtra5        = $vals['isExtra5'] ?? '';
        $isExtra6        = $vals['isExtra6'] ?? '';
        $isExtra7        = $vals['isExtra7'] ?? '';
        $isExtra8        = $vals['isExtra8'] ?? '';
        $isExtra9        = $vals['isExtra9'] ?? '';
        $isExtra10       = $vals['isExtra10'] ?? '';
        $isTitle         = $vals['isTitle'] ?? '';
        $isAuthor        = $vals['isAuthor'] ?? '';
        $isSlug          = $vals['isSlug'] ?? '';
        $isShortMessage  = $vals['isShortMessage'] ?? '';
        $isContent       = $vals['isContent'] ?? '';
        $isContent2      = $vals['isContent2'] ?? '';
        $isContent3      = $vals['isContent3'] ?? '';
        $isContent4      = $vals['isContent4'] ?? '';
        $isContentExtra1 = $vals['isContentExtra1'] ?? '';
        $isContentExtra2 = $vals['isContentExtra2'] ?? '';
        $isContentExtra3 = $vals['isContentExtra3'] ?? '';
        $isContentIcon   = $vals['isContentIcon'] ?? '';
        $isContentAttach = $vals['isContentAttach'] ?? '';
    }

    if ($inner == 'form') {
        $showMain = (
            $isStatus != ''
            || $isDisplayTime != ''
            || $isEndTime != ''
            || !empty($isCheckOption)
            || $isIcon != ''
            || $isIcon2 != ''
            || $isfileAttach != ''
            || $isExtra1 != ''
            || $isExtra2 != ''
            || $isExtra3 != ''
            || $isExtra4 != ''
            || $isExtra5 != ''
            || $isExtra6 != ''
            || $isExtra7 != ''
            || $isExtra8 != ''
            || $isExtra9 != ''
            || $isExtra10 != ''
        ) ? true : false;
        include __DIR__ . '/views/inner.articles.edit.php';
    } else {
        include __DIR__ . '/views/inner.articles.php';
    }
    exit;
} elseif ($ac == 'articlesGroup') {
    // หน้า list หมวดหมู่ (คู่กับ option isGroup ของ AOSET) — fragment ใน iframe เดียวกับ articles
    $size = REQ_get('size', 'request', 'str', '');
    $options = REQ_get('data', 'request', 'str', '');
    if (!empty($options)) {
        $json = base64_decode($options);
        $vals = json_decode($json, true);
    }
    include __DIR__ . '/views/inner.articles.group.php';
    exit;
} elseif ($ac == 'articlesGroupEdit') {
    // ฟอร์มเพิ่ม/แก้ไขหมวดหมู่
    $id = REQ_get('id', 'request', 'int', 0);
    $size = REQ_get('size', 'request', 'str', '');
    $options = REQ_get('data', 'request', 'str', '');
    if (!empty($options)) {
        $json = base64_decode($options);
        $vals = json_decode($json, true);
    }
    include __DIR__ . '/views/inner.articles.group.edit.php';
    exit;
} elseif ($ac == 'subpost') {
    // list subpost ของแม่ (group_id/articles_id)
    $size        = REQ_get('size', 'request', 'str', '');
    $options     = REQ_get('data', 'request', 'str', '');
    $group_id    = REQ_get('group_id', 'request', 'int', 0);
    $articles_id = REQ_get('articles_id', 'request', 'int', 0);
    if (!empty($options)) {
        $json = base64_decode($options);
        $vals = json_decode($json, true);
    }
    if ($group_id == 0 && isset($vals['group_id'])) $group_id = (int) $vals['group_id'];
    if ($articles_id == 0 && isset($vals['articles_id'])) $articles_id = (int) $vals['articles_id'];
    include __DIR__ . '/views/inner.articles.subpost.php';
    exit;
} elseif ($ac == 'subpostEdit') {
    // ฟอร์มเพิ่ม/แก้ไข subpost
    $id          = REQ_get('id', 'request', 'int', 0);
    $size        = REQ_get('size', 'request', 'str', '');
    $options     = REQ_get('data', 'request', 'str', '');
    $group_id    = REQ_get('group_id', 'request', 'int', 0);
    $articles_id = REQ_get('articles_id', 'request', 'int', 0);
    if (!empty($options)) {
        $json = base64_decode($options);
        $vals = json_decode($json, true);
    }
    if ($group_id == 0 && isset($vals['group_id'])) $group_id = (int) $vals['group_id'];
    if ($articles_id == 0 && isset($vals['articles_id'])) $articles_id = (int) $vals['articles_id'];
    include __DIR__ . '/views/inner.articles.subpost.edit.php';
    exit;
} elseif ($ac == 'page') {
    $options = REQ_get('data', 'request', 'str', '');
    include __DIR__ . '/views/inner.articles.page.php';
    exit;
} elseif ($ac == 'meta') {
    $options = REQ_get('data', 'request', 'str', '');
    include __DIR__ . '/views/inner.textbox.php';
    exit;
} elseif ($ac == 'config') {
    $options = [];
    $options['module'] = REQ_get('module', 'request', 'str', '');
    $options['confname'] = REQ_get('confname', 'request', 'str', '');
    include __DIR__ . '/views/inner.config.php';
    exit;
} elseif ($ac == 'loadmeta') {
    $meta_key = REQ_get('meta_key', 'request', 'str', '');
    $schema_key = REQ_get('schema_key', 'request', 'str', '');
    $panelLang = REQ_get('seolang', 'request', 'str', _LANG_);
    SEO::set($meta_key, $schema_key);
    SEO::useLang($panelLang);
    include __DIR__ . '/views/inner.meta.php';
    exit;
} elseif ($ac == 'loadschema') {
    $meta_key = REQ_get('meta_key', 'request', 'str', '');
    $schema_key = REQ_get('schema_key', 'request', 'str', '');
    $panelLang = REQ_get('seolang', 'request', 'str', _LANG_);
    SEO::set($meta_key, $schema_key);
    SEO::useLang($panelLang);
    include __DIR__ . '/views/inner.schema.php';
    exit;
} elseif ($ac === 'editmode') {
    $edit_mode = REQ_get('edit_mode', 'request', 'str', '');

    if ($edit_mode === 'true') {
        $_SESSION['edit_mode'] = true;
    } else {
        unset($_SESSION['edit_mode']);
    }
?>
    <div
        id="btnToggleEditMode"
        hx-get="admbuilder/load.php"
        hx-vals='{
                "ac": "editmode",
                "edit_mode": "<?php echo !empty($_SESSION['edit_mode']) ? 'false' : 'true'; ?>"
            }'
        hx-trigger="click"
        hx-target="#btnToggleEditMode"
        hx-swap="outerHTML"
        data-key="reloadpage"
        style="cursor:pointer;">
        <i class="fa <?php echo !empty($_SESSION['edit_mode']) ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"
            style="margin-left:15px; font-size:20px; color:<?php echo !empty($_SESSION['edit_mode']) ? '#4e9200ff' : '#8f8f8fff'; ?>;">
        </i>
        &nbsp; เปิดแก้ไข
    </div>
    <?php
    exit;
} elseif ($ac === 'language') {
    if (@$clang != '' && isset($aConfig['language'][@$clang])) {
        $oApi->setLanguage($clang);
    }

    echo '<div class="me-2 aolanguage">';
    foreach ($aConfig['language'] as $klang => $label) {
        $active = $clang == $klang ? 'active btn-info' : '';
    ?>
        <button
            class="btn btn-outline-secondary btn-sm <?php echo $active; ?>"
            hx-get="admbuilder/load.php?ac=language&lang=<?php echo $klang; ?>"
            hx-target=".aolanguage"
            data-key="reloadpage"
            hx-swap="outerHTML">
            <?php echo strtoupper($klang); ?>
        </button>
<?php
    }
    echo '</div>';
    exit;
} else {
    if (!oApi::is_Login() || !oApi::is_Admin()) {
        http_response_code(403);
        exit('Permission denied');
    } else {
        http_response_code(403);
        exit('action is not viewable');
    }
}
