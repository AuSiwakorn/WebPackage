<?php
$ac = REQ_get('ac', 'request', 'str', '');
$lang = ($lang != '') ? $lang : 'th';
$id = REQ_get('id', 'request', 'int', '');
$keysname = REQ_get('keysname', 'request', 'str');
$file_id = REQ_get('fid', 'request', 'str');

$aProvince = Arrays_province($lang);
@$widthIcon = @$aArticleConfig['widthIcon'];

$time = _TIME_;
$Year = _YY_;
$Month = _MM_;
$Day = _DD_;

if ($id == '') {
    setRaiseMsg('Cannot get id for edit.', _TIME_, 1);
    CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=list&keysname=" . $keysname);
    exit;
}

$gid             = REQ_get('gid', 'get', 'str', '');
$aGroupSelec     = array();

if ($isOpens['isOpenGroup'] == true) {
    //$aData = PG_getArticlesGroupByID($gid);
    //$aGroupSelect = PG_getAllGroupSelect($keysname);
    $group = REQ_get('group', 'post', 'str', '0');
} else {
    //$aData = array();
    //$aGroupSelect = PG_getAllGroupSelect($keysname);
    $group = '0';
}
//pre($gid);
//echo pre('xxxxx');exit;
///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array($ac, array('edit')) && $isSecurMode == 1) {
    setRaiseMsg('Secur Mode.', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=list&keysname=" . $keysname);
    exit;
}
///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////

$aArticleRows = PG_getArticlesByID($id);
if ($aArticleRows == false) {
    setRaiseMsg('Cannot Open Article ID (' . $id . '), ID IS Missing.', _TIME_, 1);
    CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=list&keysname=" . $keysname);
    exit;
}

if ($ac == 'edit') {
    $is_error = 0;
    $txterror = 'Error : ';
    $updatetxt = '';
    $upFile = new \UpFile(PATH_UPLOAD);
    $d['group'] = $group;
    $d['id'] = $id;
    $d['user_id'] = '0';
    $d['status'] = (REQ_get('status', 'post', 'str') == 'y') ? 0 : 1;
    $d['displaytime'] = (@$_REQUEST['displaytime'] != '') ? strtotime($_REQUEST['displaytime']) : $time;
    $d['extra1'] = REQ_get('extra1', 'post', 'str');
    $d['extra2'] = REQ_get('extra2', 'post', 'str');
    $d['extra3'] = REQ_get('extra3', 'post', 'str');
    $d['extra4'] = REQ_get('extra4', 'post', 'str');
    $d['extra5'] = REQ_get('extra5', 'post', 'str');
    $d['extra6'] = REQ_get('extra6', 'post', 'str');
    $d['extra7'] = REQ_get('extra7', 'post', 'str');
    $d['extra8'] = REQ_get('extra8', 'post', 'str');
    $d['extra9'] = REQ_get('extra9', 'post', 'str');
    $d['extra10'] = REQ_get('extra10', 'post', 'str');
    $d['checkOption'] = REQ_get('checkOption', 'post', 'str', 0);
    $d['checkBoxOption'] = REQ_get('checkBoxOption', 'post', 'int', 0);
    ######### Sting no use #############################

    $isResize = (REQ_get('isResize', 'request', 'int', 0) == 1) ? 1 : 0;
    $aSortimg = REQ_get('sortimg', 'request', 'array');
    if (is_array($aSortimg) && count($aSortimg) > 0) {
        foreach ($aSortimg as $k => $v) {
            PG_update_arSort_img($k, $v);
        }
    }

    $aEditimg = @$_REQUEST['edit'];
    if (is_array($aEditimg) && count($aEditimg) > 0) {
        $arEdit = array();
        foreach ($aEditimg['fid'] as $k => $v) {
            $arEdit['title']    = (isset($aEditimg['title'][$v])) ? $aEditimg['title'][$v] : '';
            $arEdit['detail'] = (isset($aEditimg['detail'][$v])) ? $aEditimg['detail'][$v] : '';
            $arEdit['detail2'] = (isset($aEditimg['detail2'][$v])) ? $aEditimg['detail2'][$v] : '';
            PG_update_allData_img($v, $arEdit);
        }
    }

    if ($isOpens['isMark']) {
        $aMark = @$_REQUEST['aMark'];
        if (is_array($aMark) && count($aMark) > 0) {
            foreach ($aMark as $k => $v) {
                $v = ($v == 1) ? 1 : 0;
                PG_updateIsMarkArticlesImg($k, $v);
            }
        }
    }

    $frm = REQ_get('frm', 'post', 'array', array('th'));
    $tranfer = REQ_get('tranfer', 'post', 'str', '') === 't';
    if ($tranfer && isset($frm[DEFAULT_LANGEUAGE])) {
        $baseData = $frm[DEFAULT_LANGEUAGE];
        foreach ($frm as $k => $v) {
            if ($k == DEFAULT_LANGEUAGE) continue;
            foreach ($baseData as $field => $value) {
                $frm[$k][$field] = $value;
            }
        }
    }
    foreach ($frm as $k => $v) {
        $title = str_replace(array("\""), "&quot;", htmlspecialchars($v['subject']));
        $title = str_replace("'", "\'", $title);

        $frm[$k]['articles_id'] = $id;
        $frm[$k]['content_id'] = $v['content_id'];
        $frm[$k]['title'] = mysqlPrep($title);
        $frm[$k]['author'] = mysqlPrep(@$v['author']);
        $frm[$k]['slug'] = sanitizeSlug(@$v['slug']);
        $frm[$k]['content_extra1'] = mysqlPrep(@$v['content_extra1']);
        $frm[$k]['content_extra2'] = mysqlPrep(@$v['content_extra2']);
        $frm[$k]['content_extra3'] = mysqlPrep(@$v['content_extra3']);
        $frm[$k]['shortMessage'] = mysqlPrep(@$v['shortMessage']);
        $frm[$k]['keywords']         = @$v['keywords'];
        $frm[$k]['content']             = (@$v['content'] != '') ? $v['content'] : '';
        $frm[$k]['content2']         = (@$v['content2'] != '') ? $v['content2'] : '';
        $frm[$k]['content3']         = (@$v['content3'] != '') ? $v['content3'] : '';
        $frm[$k]['content4']         = (@$v['content4'] != '') ? $v['content4'] : '';
    }

    $meta = @$_POST['meta'];
    if (is_array($meta)) {
        foreach ($meta as $k => $aMetaAdd) {
            updateMetaTags($keysname . '_' . $id, $k, $aMetaAdd);
        }
    }
    PG_updateArticles($d);
    PG_updateArticlesContent($frm);

    #################################################################################################
    #################################   SET CONFIG  ####################################################
    $aExtenAttc = (!empty($aArticleConfig['attach'])) ? $aArticleConfig['attach'] : ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
    $folder_attc = 'articles_attc/' . $Year . '_' . $Month;
    $aExtenIcon = (count($aArticleConfig['icon']) > 0) ? $aArticleConfig['icon'] : array('jpg', 'png', 'gif');
    $folder_icon = 'articles_icon/' . $Year . '_' . $Month;
    #################################################################################################

    if ($isOpens['isContentAttach']) {
        $contectAttach = REQ_get('contectAttach', 'file', 'array', []);
        if (!empty($contectAttach['name']) && is_array($contectAttach['name'])) {
            foreach ($contectAttach['name'] as $k => $v) {
                if (!empty($v)) {
                    $uploadResult = $upFile->uploadStandard($aExtenAttc, $contectAttach['tmp_name'][$k], $v, $folder_attc, $id . '_contectAttach_' . $time . '_' . $k, $aArticleRows['content'][$k]['contentAttach']);
                    if ($uploadResult !== false) PG_updateArticlesContentAttach($id, $k, $uploadResult);
                }
            }
        }
    }

    if ($isOpens['isContent_icon']) {
        $contectIcon = REQ_get('contectIcon', 'file', 'array', []);
        if (!empty($contectIcon['name']) && is_array($contectIcon['name'])) {
            foreach ($contectIcon['name'] as $k => $v) {
                if (!empty($v)) {
                    $uploadResult = $upFile->uploadStandard($aExtenIcon, $contectIcon['tmp_name'][$k], $v, $folder_icon, $id . '_contectIcon_' . $time . '_' . $k, $aArticleRows['content'][$k]['content_icon']);
                    if ($uploadResult !== false) PG_updateArticlesContentIcon($id, $k, $uploadResult);
                }
            }
        }
    }

    #################################################################################################
    ################################# attach, iconview, iconview2 ###########################################
    $updatetxt .= ' Content ';

    //Delete Iconview
    $isIconView = REQ_get('isIconView', 'request', 'str', '');
    if ($isIconView === 'isiconfalse') {
        PG_unlinkArticlesFileIcon($id, 'icon');
    }

    $iconname = '';
    $fileiconviewUpload = REQ_get('iconview', 'file');
    if (!empty($fileiconviewUpload['name'])) {
        $result = $upFile->uploadStandard(
            $aExtenIcon,                       // 1. นามสกุลที่อนุญาต
            $fileiconviewUpload['tmp_name'],   // 2. ตำแหน่งไฟล์ชั่วคราว
            $fileiconviewUpload['name'],       // 3. ชื่อไฟล์ต้นฉบับ
            $folder_icon,                      // 4. โฟลเดอร์ปลายทาง
            $id . '_iconview_' . $time,        // 5. ชื่อไฟล์ใหม่
            $aArticleRows['icon']              // 6. ไฟล์เก่าที่จะถูกลบ
        );
        if ($result !== false) {
            $iconname = $result;
            PG_unlinkArticlesFileIcon($id, 'icon');
            $updatetxt .= ', Icon 1 ';
        } else {
            $txterror .= " Icon type is not allow or upload failed. ";
            $is_error = 1;
        }
    }

    //Delete Iconview2
    $isIconView2 = REQ_get('isIconView2', 'request', 'str', '');
    if ($isIconView2 === 'isiconfalse') {
        PG_unlinkArticlesFileIcon($id, 'icon2');
    }

    $iconname2 = '';
    $fileiconviewUpload2 = REQ_get('iconview2', 'file');
    if (!empty($fileiconviewUpload2['name'])) {
        $result = $upFile->uploadStandard($aExtenIcon, $fileiconviewUpload2['tmp_name'], $fileiconviewUpload2['name'], $folder_icon, $id . '_iconview2_' . $time, $aArticleRows['icon2']);
        if ($result !== false) {
            $iconname2 = $result;
            PG_unlinkArticlesFileIcon($id, 'icon2');
            $updatetxt .= ', Icon2 ';
        } else {
            $txterror .= " Icon2 type is not allow or upload failed. ";
            $is_error = 1;
        }
    }

    $nameAttc = '';
    $fileExtraUpload = REQ_get('attach', 'file');
    if (!empty($fileExtraUpload['name'])) {
        $result = $upFile->uploadStandard($aExtenAttc, $fileExtraUpload['tmp_name'], $fileExtraUpload['name'], $folder_attc, $id . '_attach_' . $time, $aArticleRows['file_attach']);
        if ($result !== false) {
            $nameAttc = $result;
            PG_unlinkArticlesFileAttc($id);
            $updatetxt .= ', Attach ';
        } else {
            $txterror .= " File Attach type is not allow or upload failed. ";
            $is_error = 1;
        }
    }

    PG_updateIconAttachByid($id, $iconname, $iconname2, $nameAttc);

    #################################################################################################

    if ($isOpens['isAttachPic']) {
        $aFile = REQ_get('attachFile', 'files', 'array', []);
        $aFileText = REQ_get('attachTextFile', 'request', 'array', []);
        if (!empty($aFile['name']) && is_array($aFile['name'])) {
            $folder_pic_base = 'articles_pic/' . $Year . '_' . $Month . '/' . $id;
            $folder_mini = $folder_pic_base . '/mini';
            $aUpName = [];
            $counter = PG_getSortPictureByArticlesID($id);
            $counterSort = $counter['sort'] + 1;
            foreach ($aFile['error'] as $kFile => $vFile) {
                if ($vFile == 0 && !empty($aFile['name'][$kFile])) {
                    $originalName = $aFile['name'][$kFile];
                    $customFileName = $id . '_pic_' . _TIME_ . '_' . $counterSort;
                    $uploadResultBig = $upFile->uploadStandard($aExtenIcon, $aFile['tmp_name'][$kFile], $originalName, $folder_pic_base . '/big', $customFileName);
                    if ($uploadResultBig !== false) {
                        $ext = $upFile->getExtension($originalName);
                        $fileNameWithExt = $customFileName . '.' . $ext;
                        $fileMiniUrl = $folder_mini . '/' . $fileNameWithExt;
                        PG_addArticlesPicture2($counterSort, $id, $fileMiniUrl, $uploadResultBig, @$aFileText[$kFile]);
                        $counterSort++;
                        if (!is_dir(PATH_UPLOAD . '/' . $folder_mini)) mkdir(PATH_UPLOAD . '/' . $folder_mini, 0777, true);
                        $thumb = new thumbnail();
                        $thumbinc = $thumb->resize_thumbnail(PATH_UPLOAD . '/' . $uploadResultBig);
                        $thumb->size_width(300);
                        $thumb->jpeg_quality(99);
                        $thumb->save(PATH_UPLOAD . '/' . $fileMiniUrl);
                        $aUpName[] = $fileNameWithExt;
                    }
                }
            }

            if (!empty($aUpName) && count($aUpName) > 0) {
                $strUpload = implode(' , ', $aUpName);
                $updatetxt .= ', Picture, ' . $strUpload;
                updateKeysnamePicture($id, $keysname);
            }
        }
    }

    if ($isOpens['isAttachFile']) {
        $aFileList = REQ_get('attachFileList', 'files', 'array', []);
        $aFileTextList = REQ_get('attachTextFileList', 'request', 'array', []);
        $aFileTextListEn = REQ_get('attachTextFileListEn', 'request', 'array', []);
        $aDetailChageFile = @$_REQUEST['aDetailChageFile'];
        $aDetailChageFileEn = @$_REQUEST['aDetailChageFileEn'];
        $aDetailSort = @$_REQUEST['sortimgFile'];
        $counterFile = PG_getSortFileByArticlesID($id);
        $counterSortFile = $counterFile['sort'] + 1;
        if (is_array($aDetailChageFile) && count($aDetailChageFile) > 0) {
            foreach ($aDetailChageFile as $k => $v) {
                $detailadd = strip_tags($v);
                $detailadd_en = strip_tags(@$aDetailChageFileEn[$k]);
                $ctime = isset($aDetailSort[$k]) ? $aDetailSort[$k] : $counterSortFile;
                PG_updateDetailFileAttach($k, $detailadd, $detailadd_en, $ctime);
            }
        }
        if (!empty($aFileList['name']) && is_array($aFileList['name'])) {
            foreach ($aFileList['error'] as $kFile => $vFile) {
                if ($vFile == 0 && !empty($aFileList['name'][$kFile])) {
                    $uploadResult = $upFile->uploadStandard($aExtenAttc, $aFileList['tmp_name'][$kFile], $aFileList['name'][$kFile], 'articles_file/' . $Year . '_' . $Month, $id . '_file_' . _TIME_ . '_' . $counterSortFile);
                    if ($uploadResult !== false) {
                        PG_addArticlesFiles($id, $uploadResult, @$aFileTextList[$kFile], @$aFileTextListEn[$kFile], $keysname, $counterSortFile);
                        $counterSortFile++;
                    }
                }
            }
        }
    }

    #################################################################################################
    ##################################  Keywords, Keysuse ###############################################
    $ktype = REQ_get('ktype', 'post', 'array', array('th'));
    if (count($ktype) > 0 && is_array($ktype)) {
        PG_deleteArticleKeyUseType($id);
        PG_addArticleKeyType($id, $ktype);
    }
    #################################################################################################

    $updatetxt .= ', Database ';
    if ($is_error == 0) {
        Func_Addlogs("[{$keysname}] Edit Article ID {$id} "); //ตัวอย่าง Addlogs
        setRaiseMsg('Database successfully updated ( ' . $updatetxt . ' ) .', _TIME_, 0);
    } else {
        setRaiseMsg('Database Error ( ' . $txterror . ' ) .', _TIME_, 1);
    }
    if ($gid != '') {
        CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=list&keysname=" . $keysname . "&gid=" . $gid);
    } else {
        CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=list&keysname=" . $keysname . "&id=" . $id);
    }
    exit;
} elseif ($ac == 'unmark') {
    PG_updateIsMarkArticlesImg($file_id, 0);
    setRaiseMsg('Unmark File successfully.', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=edit&keysname=" . $keysname . "&id=" . $id);
    exit;
} elseif ($ac == 'delContentAttF') {
    $langdel = @$_REQUEST['langdel'];
    if ($langdel != '' && isset($aConfig['language'][$langdel])) {
        PG_unlinkArticleContentFileAttc($id, $langdel);
        setRaiseMsg('Delete Article Content Attach File successfully.', _TIME_, 0);
    }
    CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=edit&keysname=" . $keysname . "&id=" . $id);
    exit;
} elseif ($ac == 'delContentIcon') {
    $langdel = @$_REQUEST['langdel'];
    if ($langdel != '' && isset($aConfig['language'][$langdel])) {
        PG_unlinkArticleContentFileIcon($id, $langdel);
        setRaiseMsg('Delete Article Content ICON File successfully.', _TIME_, 0);
    }
    CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=edit&keysname=" . $keysname . "&id=" . $id);
    exit;
} elseif ($ac == 'delfile') {
    PG_unlinkArticlesFileAttc($id);
    setRaiseMsg('Delete articles Attach File successfully.', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=edit&keysname=" . $keysname . "&id=" . $id);
    exit;
} elseif ($ac == 'delicon') {
    PG_unlinkArticlesFileIcon($id, 'icon');
    setRaiseMsg('Delete articles ICON successfully.', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=edit&keysname=" . $keysname . "&id=" . $id);
    exit;
} elseif ($ac == 'delicon2') {
    PG_unlinkArticlesFileIcon($id, 'icon2');
    setRaiseMsg('Delete articles ICON successfully.', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=edit&keysname=" . $keysname . "&id=" . $id);
    exit;
}
if ($ac == 'delFile') {
    PG_deleteArticlesFileId($file_id);
    setRaiseMsg('Delete File successfully.', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=edit&keysname=" . $keysname . "&id=" . $id);
    exit;
}

if ($isOpens['isAttachPic']) {
    $aPictureAtt = PG_getAllPictureByArticlesID($id);
}



$aTypes = pg_getAllType($keysname, 0, $page = 0);
$aTypesUse = pg_getAllTypeUseById($id);

$aGroupAll = pg_getAllGroup($keysname);
$aFilesAtt = array();
if ($isOpens['isAttachFile']) {
    $aFilesAtt = PG_getAllFilesByArticlesID($id);
}

$aDefaultMetaTxt     = getDefaultMetatags();
foreach ($aConfig['language'] as $kLang => $vLang) {
    $aDefaultMeta[$kLang]     = getMetaTagsByLang('default', $kLang);
    $aMeta[$kLang]             = getMetaTagsByLang($keysname . '_' . $id, $kLang);
    $aMetaUse[$kLang] = arrayMergeMeta($aDefaultMetaTxt, $aDefaultMeta[$kLang], $aMeta[$kLang]);
}

include 'css/edit.php';
?>
<form action="" method="post" name="form1" id="form1" enctype="multipart/form-data">
    <input type="hidden" name="ac" value="edit" />
    <input type="hidden" name="id" value="<?php echo $aArticleRows['articles_id']; ?>" />
    <div id="page-head">
        <div id="page-title">
            <div class="row">
                <div class="col-md-6 text-white">
                    <h1 class="page-header text-overflow"><?php echo @$tagname['name']; ?> Edit</h1>
                </div>
                <div class="col-md-6 toolbar-right text-right">
                    <button class="btn btn-mint" id="add-and-publish" style="font-size: 24px; font-family: db_ozone_xregular; "> <i class="fa fa-save" style="font-size: 18px;"></i> Edit &amp; Publish</button>
                </div>
            </div>
        </div>
    </div>
    <div id="page-content">
        <?php echo displayRaiseMsg(); ?>
        <div class="fixed-fluid">
            <?php if (
                @$isOpens['isOnOff']
                || $isOpens['isDisplaydate']
                || $isOpens['isEndDate']
                || @$isOpens['isCheckBoxOption']
                || @$isOpens['isBigUpload']
                || @$isOpens['isCheckedOption']
                || $isOpens['isExtra1']
                || $isOpens['isExtra2']
                || $isOpens['isExtra3']
                || $isOpens['isExtra4']
                || $isOpens['isExtra5']
                || $isOpens['isExtra6']
                || $isOpens['isExtra7']
                || $isOpens['isExtra8']
                || $isOpens['isExtra9']
                || $isOpens['isExtra10']
            ) { ?>
                <div class="fixed-sm-300 pull-sm-right" style="background-color: transparent !important;">
                    <div class="panel">
                        <div class="panel-body">
                            <?php if (@$isOpens['isOnOff'] == true) { ?>
                                <div class="form-horizontal">
                                    <p class="text-main text-bold text-uppercase mainTxtBorderGreen">Display</p>
                                    <div class="dropzone-container">
                                        <div class="fallback">
                                            <input class="toggle-switch" id="demo-allow-show" value="y" type="checkbox" name="status" <?php echo (isset($aArticleRows['status']) && $aArticleRows['status'] != 1) ? 'checked' : ''; ?>>
                                            <label for="demo-allow-show"> <span style="line-height: 28px;">Close</span> | <span style="color: #8BC34A;line-height: 28px;"> Open </span> </label>
                                        </div>
                                    </div>
                                    <br />
                                </div>
                            <?php } else { ?>
                                <input value="y" type="hidden" name="status" />
                            <?php } ?>
                            <?php if ($isOpens['isOpenGroup'] == true) { ?>
                                <div class="form-group">
                                    <label class="col-sm-12 control-label text-left"><?php echo @$tagname['isGroup']; ?></label>
                                    <div class="col-sm-12">
                                        <div class="select-md" style="width: 100%;margin-bottom: 20px;">
                                            <select name="group" id="group" style="font-size: 18px;width: 100%;">
                                                <option value="0">== Head Group ==</option>
                                                <?php
                                                $curparent_id = @$aArticleRows['group_id'];
                                                foreach ($aGroupAll['data'] as $k => $v) {
                                                    if ($v['group_parent_id'] == 0) {
                                                        $se = ($v['group_id'] == $curparent_id) ? ' selected="selected" ' : '';
                                                        echo '<option value="' . $v['group_id'] . '" ' . $se . '> ' . $v['group_name'] . '</option>';
                                                        $aSubGroup = PG_getAllGroupByGID($v['group_id']);
                                                        if (count($aSubGroup) > 0) {
                                                            foreach ($aSubGroup as $kk => $vv) {
                                                                $se = ($vv['group_id'] == $curparent_id) ? ' selected="selected" ' : '';
                                                                echo '<option value="' . $vv['group_id'] . '" ' . $se . ' style="color:#817F82;"> &nbsp;&nbsp;&nbsp;&nbsp;&rarr; ' . $vv['group_name'] . ' </option>';
                                                            }
                                                        }
                                                    }
                                                } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($isOpens['isDisplaydate']) { ?>
                                <div class="form-group">
                                    <label class="col-sm-12 control-label text-left"><?php echo @$tagname['isDisplaydate']; ?></label>
                                    <div class="col-sm-12">
                                        <div id="demo-dp-component">
                                            <div class="input-group date">
                                                <input type="text" class="form-control" name="displaytime" id="displaytime" value="<?php echo @date('m/d/Y', @$aArticleRows['displaytime']); ?>">
                                                <span class="input-group-addon"><i class="demo-pli-calendar-4"></i></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($isOpens['isEndDate']) { ?>
                                    <div class="form-group">
                                        <label class="col-sm-12 control-label text-left">End Date</label>
                                        <div class="col-sm-12">
                                            <div id="demo-dp-component">
                                                <div class="input-group date">
                                                    <?php
                                                    if ($aArticleRows['end_time'] != '' && $aArticleRows['end_time'] != 0) {
                                                        $curTime = @date('m/d/Y', @$aArticleRows['end_time']);
                                                    } else {
                                                        $curTime = date('m/d/Y');
                                                    } ?>
                                                    <input type="text" class="form-control" name="endDate" id="endDate" value="<?php echo $curTime; ?>">
                                                    <span class="input-group-addon"><i class="demo-pli-calendar-4"></i></span>
                                                </div>
                                                <small class="text-muted">
                                                    <input type="checkbox" class="isEndDateShow" name="isEndDateShow" value="1" <?php echo ($aArticleRows['end_time'] == '0' || $aArticleRows['end_time'] == '') ? ' checked="checked" ' : ''; ?> /> เลือกกรณีที่ไม่มีวันหมดอายุ</label>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } ?>

                        </div>
                    </div>

                    <?php include('option/isAttachAll.php'); ?>

                    <?php if (@$isOpens['isBigUpload']) { ?>
                        <div class="panel">
                            <div class="panel-body">
                                <div class="form-horizontal">
                                    <p class="text-main text-bold text-uppercase mainTxtBorderGreen"><?php echo $tagname['isBigUpload'] ?? 'อัปโหลดไฟล์ขนาดใหญ่'; ?></p>
                                    <div class="dropzone-container">
                                        <div class="fallback">
                                            <div class="text-center" align="center">
                                                <span class="pull-center btn btn-primary btn-file" style="font-size: 18px;">
                                                    <i class="demo-pli-upload-to-cloud icon-5x" style="font-size: 20px;"></i> Browse... <input type="file" id="upFileInput">
                                                </span>
                                                <input type="hidden" id="upFileId" value="<?php echo $id; ?>">
                                                <input type="hidden" id="upFileOld" value="<?php echo @$aArticleRows['bigfile']; ?>">
                                                <progress id="upFileProgress" value="0" max="100" style="width:100%;height:20px;margin-top:10px;"></progress>
                                                <button type="button" id="upFileBtn" class="btn btn-success" style="display:none; margin-bottom:10px;">อัปโหลด</button>
                                                <!-- <p id="upFileStatus" style="margin-top:10px;margin-bottom:0px;font-weight:bold;"></p> -->
                                                <p id="upFileLinkWrap" style="<?php echo empty($aArticleRows['bigfile']) ? 'display:none;' : ''; ?>">
                                                    ไฟล์ปัจจุบัน: <a id="upFileLink" data-url-base="<?php echo URL_UPLOAD; ?>" href="<?php echo URL_UPLOAD . @$aArticleRows['bigfile']; ?>" target="_blank"><?php echo basename(@$aArticleRows['bigfile']); ?></a>
                                                    <button type="button" id="upFileDelBtn" class="btn btn-danger btn-xs" style="margin-left:8px;">ลบไฟล์</button>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if (@$isOpens['isCheckBoxOption']) { ?>
                        <div class="panel">
                            <div class="panel-body">
                                <div class="form-horizontal">
                                    <p class="text-main text-bold text-uppercase mainTxtBorderGreen"><?php echo @$tagname['isCheckBoxOption']; ?></p>
                                    <div class="dropzone-container">
                                        <div class="fallback">
                                            <div class="checkbox">
                                                <input type="checkbox" class="magic-checkbox" id="checkBoxOption" name="checkBoxOption" value="1" <?php echo (!empty($aArticleRows['checkBoxOption']) && $aArticleRows['checkBoxOption'] == 1) ? 'checked="checked" ' : ''; ?>>
                                                <label for="checkBoxOption"><?php echo $tagname['isCheckBoxOption']; ?></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if (@$isOpens['isCheckedOption']) { ?>
                        <div class="panel">
                            <div class="panel-body">
                                <div class="form-horizontal">
                                    <p class="text-main text-bold text-uppercase mainTxtBorderGreen">ประเภท</p>
                                    <div class="dropzone-container">
                                        <div class="fallback">
                                            <?php foreach ($aArticleConfig['isCheckedOptionData'] as $kCheck => $vCheck) { ?>
                                                <div class="radio">
                                                    <input id="ty<?php echo $kCheck; ?>" class="magic-radio" type="radio" name="checkOption" value="<?php echo $kCheck; ?>" <?php echo ($aArticleRows['checkOption'] == $kCheck && $aArticleRows['checkOption'] != '') ? 'checked="checked" ' : ''; ?> />
                                                    <label for="ty<?php echo $kCheck; ?>"><?php echo $vCheck; ?></label>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <?php if ($isOpens['isExtra1'] || $isOpens['isExtra2'] || $isOpens['isExtra3'] || $isOpens['isExtra4']  || $isOpens['isExtra5']  || $isOpens['isExtra6']  || $isOpens['isExtra7']  || $isOpens['isExtra8']  || $isOpens['isExtra9']  || $isOpens['isExtra10']) { ?>
                        <div class="panel">
                            <div class="panel-body">
                                <div class="form-horizontal">
                                    <?php if ($isOpens['isExtra1']) {
                                        include('option/isExtra1.php');
                                    } ?>
                                    <?php if ($isOpens['isExtra2']) {
                                        include('option/isExtra2.php');
                                    } ?>
                                    <?php if ($isOpens['isExtra3']) {
                                        include('option/isExtra3.php');
                                    } ?>
                                    <?php if ($isOpens['isExtra4']) {
                                        include('option/isExtra4.php');
                                    } ?>
                                    <?php if ($isOpens['isExtra5']) {
                                        include('option/isExtra5.php');
                                    } ?>
                                    <?php if ($isOpens['isExtra6']) {
                                        include('option/isExtra6.php');
                                    } ?>
                                    <?php if ($isOpens['isExtra7']) {
                                        include('option/isExtra7.php');
                                    } ?>
                                    <?php if ($isOpens['isExtra8']) {
                                        include('option/isExtra8.php');
                                    } ?>
                                    <?php if ($isOpens['isExtra9']) {
                                        include('option/isExtra9.php');
                                    } ?>
                                    <?php if ($isOpens['isExtra10']) {
                                        include('option/isExtra10.php');
                                    } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if ($isOpens['isTypes']) { ?>

                        <div class="panel">
                            <div class="panel-body">

                                <?php if (@$isOpens['isTypes']) { ?>
                                    <div class="form-group">
                                        <label class="col-sm-12 control-label text-left"><?php echo @$tagname['isTypes']; ?></label>
                                        <?php
                                        if ($aTypes['num_rows'] > 0) {
                                            foreach ($aTypes['data'] as $k => $v) {
                                                $ischeck = (@$aTypesUse['kid_list'][$v['kid']] != '') ? ' checked="checked" ' : '';
                                                $kname = ($v['kname']) ? $v['kname'] : '---';
                                                echo '
															<div class="col-sm-12">
																<label><input type="checkbox" name="ktype[]"  value="' . $v['kid'] . '" ' . $ischeck . '  /> ' . $kname . '</label>
															</div>';
                                            }
                                        } else {
                                            echo '
														<div class="col-sm-12">
															<label> - - - ไม่พบข้อมูล - - - </label>
														</div>';
                                        } ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php }  ?>

                </div>
            <?php } ?>
            <div class="fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="tab-base">
                            <ul class="nav nav-tabs">
                                <?php
                                foreach ($aConfig['language'] as $kLang => $vLang) {
                                    $act = ($kLang == DEFAULT_LANGEUAGE) ? 'active text-bold ' : 'none_active';
                                    echo '<li class="' . $act . '"><a data-toggle="tab" href="#demo-lft-tab-' . $kLang . '">' . $vLang . '</a></li>';
                                }

                                if ($isOpens['isAttachPic']) {
                                    echo '<li class="none_active"><a data-toggle="tab" href="#uploadPic">Uploads Picture</a></li>';
                                }

                                if ($isOpens['isAttachFile']) {
                                    echo '<li class="none_active"><a data-toggle="tab" href="#uploadFile">Uploads File</a></li>';
                                }
                                ?>
                            </ul>

                            <div class="tab-content">

                                <?php
                                foreach (@$aConfig['language'] as $kLang => $vLang) {
                                    $subject             = stripslashes(@isset($aArticleRows['content'][$kLang]['title']) ? $aArticleRows['content'][$kLang]['title'] : '');
                                    $shortMessage   = stripslashes(@isset($aArticleRows['content'][$kLang]['shortMessage']) ? $aArticleRows['content'][$kLang]['shortMessage'] : '');
                                    $slug           = stripslashes(@isset($aArticleRows['content'][$kLang]['slug']) ? $aArticleRows['content'][$kLang]['slug'] : '');
                                    $keywords   = stripslashes(@isset($aArticleRows['content'][$kLang]['keywords']) ? $aArticleRows['content'][$kLang]['keywords'] : '');
                                    $content_id        = @isset($aArticleRows['content'][$kLang]['ar_content_id']) ? $aArticleRows['content'][$kLang]['ar_content_id'] : '';
                                    $content             = stripslashes(@isset($aArticleRows['content'][$kLang]['content']) ? $aArticleRows['content'][$kLang]['content'] : '');
                                    $content2           = stripslashes(@isset($aArticleRows['content'][$kLang]['content2']) ? $aArticleRows['content'][$kLang]['content2'] : '');
                                    $content3           = stripslashes(@isset($aArticleRows['content'][$kLang]['content3']) ? $aArticleRows['content'][$kLang]['content3'] : '');
                                    $content4           = stripslashes(@isset($aArticleRows['content'][$kLang]['content4']) ? $aArticleRows['content'][$kLang]['content4'] : '');
                                    $author               = stripslashes(@isset($aArticleRows['content'][$kLang]['author']) ? $aArticleRows['content'][$kLang]['author'] : '');

                                ?>

                                    <input type="hidden" name="frm[<?php echo $kLang; ?>][content_id]" value="<?php echo $content_id; ?>">
                                    <div id="demo-lft-tab-<?php echo $kLang; ?>" class="tab-pane fade <?php echo ($kLang == DEFAULT_LANGEUAGE) ? 'active in' : ' in'; ?>">

                                        <?php if (count($aConfig['language']) > 1 && $kLang == DEFAULT_LANGEUAGE) { ?>
                                            <div class="col-md-4">
                                                <div class="checkbox">
                                                    <input id="demo-checkbox-2" class="magic-checkbox" type="checkbox" name="tranfer" value="t">
                                                    <label for="demo-checkbox-2">นำข้อมูลทั้งหมดไปอีกภาษา (ข้อมูลจะถูกทับ)</label>
                                                </div>
                                            </div>
                                            <br clear="all" />
                                        <?php } ?>

                                        <?php if ($isOpens['isTitle']) { ?>
                                            <div class="form-group">
                                                <label class="control-label"><?php echo @$tagname['isTitle']; ?></label>
                                                <input type="text" name="frm[<?php echo $kLang; ?>][subject]" placeholder="<?php echo $tagname['isTitle']; ?>" class="form-control input-md" value="<?php echo $subject; ?>" autofocus />
                                            </div>
                                        <?php } ?>

                                        <?php if (@$isOpens['isSlug']) { ?>
                                            <div class="form-group">
                                                <label class="control-label"><?php echo @$tagname['isSlug']; ?></label>
                                                <input type="text" name="frm[<?php echo $kLang; ?>][slug]" placeholder="<?php echo @$tagname['isSlug']; ?>" value="<?php echo $slug; ?>" class="form-control input-md">
                                            </div>
                                        <?php } ?>

                                        <?php if (@$isOpens['isKeyswords']) { ?>
                                            <div class="form-group">
                                                <label class="control-label"><?php echo @$tagname['isKeywords']; ?></label>
                                                <input type="text" name="frm[<?php echo $kLang; ?>][keywords]" placeholder="<?php echo @$tagname['isKeywords']; ?>" value="<?php echo $keywords; ?>" class="form-control input-md">
                                            </div>
                                        <?php } ?>

                                        <?php if (@$isOpens['isAuthor']) { ?>
                                            <div class="form-group">
                                                <label class="control-label"><?php echo @$tagname['isAuthor']; ?></label>
                                                <input type="text" name="frm[<?php echo $kLang; ?>][author]" placeholder="<?php echo $tagname['isAuthor']; ?>" value="<?php echo $author; ?>" class="form-control input-md">
                                            </div>
                                        <?php } ?>

                                        <?php if ($isOpens['isShortMessage']) { ?>
                                            <div class="form-group">
                                                <div><?php echo (@$tagname['isShortMessage'] != '') ? @$tagname['isShortMessage'] : 'ข้อความเกริ่นนำสั้นๆ (คำตัดทอน / มีผลต่อการค้นหา กรุณากรอก)'; ?></div>
                                                <textarea name="frm[<?php echo $kLang; ?>][shortMessage]" class="form-control" rows="3"><?php echo $shortMessage; ?></textarea>
                                            </div>
                                        <?php } ?>

                                        <?php include('option/isContent1.php'); ?>
                                        <?php include('option/isContent2.php'); ?>
                                        <?php include('option/isContent3.php'); ?>
                                        <?php include('option/isContent4.php'); ?>

                                        <?php
                                        if ($isOpens['isContentExtra1'] || $isOpens['isContentExtra2'] || $isOpens['isContentExtra3']) {
                                            $contentExtra1 = stripslashes(@isset($aArticleRows['content'][$kLang]['content_extra1']) ? $aArticleRows['content'][$kLang]['content_extra1'] : '');
                                            $contentExtra2 = stripslashes(@isset($aArticleRows['content'][$kLang]['content_extra2']) ? $aArticleRows['content'][$kLang]['content_extra2'] : '');
                                            $contentExtra3 = stripslashes(@isset($aArticleRows['content'][$kLang]['content_extra3']) ? $aArticleRows['content'][$kLang]['content_extra3'] : '');
                                            include('option/content_extra.php');
                                        }
                                        ?>

                                        <?php if ($isOpens['isContent_icon']) { ?>
                                            <div class="col-sm-6">
                                                <p class="text-main text-bold text-uppercase mainTxtBorderGreen" style="max-width: 190px;"><?php echo @$tagname['isContent_icon']; ?></p>
                                                <div class="dropzone-container">
                                                    <div class="fallback">
                                                        <?php
                                                        $ucaf = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=edit&keysname=' . $keysname . '&id=' . $aArticleRows['articles_id'] . '&ac=delContentIcon&langdel=' . $kLang);
                                                        if ($aArticleRows['content'][$kLang]['content_icon'] != '') {
                                                            echo '<a href="' . URL_UPLOAD . '/' . $aArticleRows['content'][$kLang]['content_icon'] . '" target="_blank"><img src="' . URL_UPLOAD . '/' . $aArticleRows['content'][$kLang]['content_icon'] . '" height="80" border="0" /></a>';
                                                            echo '<br/><a href="' . $ucaf . '" style="color: #FF0048;font: 16px \'db_ozone_xregular\' !important;">[ ลบไฟล์นี้ ]</a>';
                                                        } else {
                                                            echo '
																	<div class="form-group">
																		<span class="pull-left btn btn-primary btn-file" style="font: 18px \'db_ozone_xregular\' !important;"> 
																			<i class="demo-pli-upload-to-cloud icon-5x" style="font-size: 20px;"></i> Browse... <input type="file" name="contectIcon[' . $kLang . ']" id="contectIcon[' . $kLang . ']">
																		</span>
																		<br clear="all" />
																	</div>
																	';
                                                        } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <?php if ($isOpens['isContentAttach']) { ?>
                                            <div class="col-sm-6">
                                                <p class="text-main text-bold text-uppercase mainTxtBorderGreen" style="max-width: 190px;"><?php echo @$tagname['isContentAttach']; ?></p>
                                                <div class="dropzone-container">
                                                    <div class="fallback">
                                                        <?php
                                                        $ucaf2 = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=edit&keysname=' . $keysname . '&id=' . $aArticleRows['articles_id'] . '&ac=delContentAttF&langdel=' . $kLang);
                                                        if ($aArticleRows['content'][$kLang]['contentAttach'] != '') {
                                                            echo '<a href="' . URL_UPLOAD . '/' . $aArticleRows['content'][$kLang]['contentAttach'] . '" target="_blank"><i class="fa fa-file-pdf-o" style="font-size: 22px;color: #FF0048;"></i> เปิดไฟล์แนบ </a>';
                                                            echo '<br/><a href="' . $ucaf2 . '" style="color: #FF0048;font: 16px \'db_ozone_xregular\' !important;">[ ลบไฟล์นี้ ]</a>';
                                                        } else {
                                                            echo '
																	<div class="form-group">
																		<span class="pull-left btn btn-primary btn-file" style="font: 18px \'db_ozone_xregular\' !important;"> 
																			<i class="demo-pli-upload-to-cloud icon-5x" style="font-size: 20px;"></i> Browse... <input type="file" name="contectAttach[' . $kLang . ']" id="contectAttach[' . $kLang . ']">
																		</span>
																		<br clear="all" />
																	</div>
																	';
                                                        } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        <br clear="all" />
                                    </div>
                                <?php } ?>

                                <?php if ($isOpens['isAttachPic']) { ?>
                                    <div id="uploadPic" class="tab-pane fade in">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="panel">
                                                    <div class="panel-heading">
                                                        <h3 class="panel-title "> จัดการรูปภาพแนบ </h3>
                                                    </div>
                                                    <div class="panel-body">
                                                        <?php include('tab-uploads.php'); ?>

                                                        <?php
                                                        if ($aPictureAtt['num_rows'] > 0) {
                                                            echo '<div class="text-main text-bold" style="font-size: 20px;margin-top: 20px;"> รูปภาพแนบทั้งหมด <span class="text-bold" style="color: #f38311e3; font-size: 20px;">(' . @$aPictureAtt['num_rows'] . ')</span> รูป</div>';
                                                            include('tab-uploads-list.php');
                                                        } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <br clear="all" />
                                <?php } ?>
                                <?php if ($isOpens['isAttachFile']) { ?>
                                    <div id="uploadFile" class="tab-pane fade in">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="panel">
                                                    <div class="panel-heading">
                                                        <h3 class="panel-title"> จัดการไฟล์ที่แนบ <?php echo '(' . @$aFilesAtt['num_rows'] . ')' ?></h3>
                                                    </div>
                                                    <div class="panel-body">
                                                        <?php include('tab-uploads-file.php');

                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                if (@$isOpens['isSeo']) {
                    $keysname_new = $keysname . '_' . $id;
                    include('option/isSeo.php');
                }
                ?>
            </div>
        </div>
    </div>
</form>
<?php
if ($isOpens['EDTNAME'] == 'ckeditor' || $isOpens['EDTNAME'] == 'ckeditor5') {
    $aConfig['editor_height'] = $aArticleConfig['editorheight'];
    $aConfig['isTabLoad'] = true;
    include('include/pages_inc/editor.php');
}
?>