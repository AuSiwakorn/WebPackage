<?php
$id = $keysname;
if (!empty($options)) {
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

$showMain = ($isStatus != '' || $isDisplayTime != '' || $isEndTime != '' || !empty($isCheckOption) || $isIcon != '' || $isIcon2 != '' || $isfileAttach != '' || $isExtra1 != '' || $isExtra2 != '' || $isExtra3 != '' || $isExtra4 != '' || $isExtra5 != '' || $isExtra6 != '' || $isExtra7 != '' || $isExtra8 != '' || $isExtra9 != '' || $isExtra10 != '') ? true : false;
?>
<div class="modal-header">
    <h5 class="modal-title">แก้ไขข้อมูล</h5>
</div>
<form method="post" enctype="multipart/form-data" hx-encoding="multipart/form-data" autocomplete="off" hx-post="/admbuilder/save.articles.php" data-skip-before="true">
    <div class="modal-body">
        <input type="hidden" name="id" value="<?php echo $id ?>">
        <input type="hidden" name="options" value='<?php echo htmlspecialchars(json_encode($vals), ENT_QUOTES, "UTF-8"); ?>'>
        <input type="hidden" name="ac" value="update">
        <div class="row">
            <div class="col-md-12 mb-3">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <?php foreach ($aConfig['language'] as $k => $v): ?>
                                <li class="nav-item">
                                    <button class="nav-link <?= ($lang == $k ? 'active' : '') ?>"
                                        data-bs-toggle="tab"
                                        data-bs-target="#lang-<?= $k ?>"
                                        type="button" role="tab">
                                        <?= $v ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="tab-content mt-3">
                            <?php foreach ($aConfig['language'] as $inlang => $v):
                                if (!empty($id)) {
                                    $aArticle = DB_GET('site_articles', ['articles_id' => $id]);
                                    $aArticleCont = DB_GET('site_articles_content', ['articles_id' => $id, 'langkeys' => $inlang]);
                                } ?>
                                <div class="tab-pane fade <?= $lang == $inlang ? 'show active' : '' ?>" id="lang-<?= $inlang ?>" role="tabpanel">
                                    <div class="col-12 mb-3">
                                        <label for="title" class="form-label"><?php echo $isTitle; ?></label>
                                        <input type="text" class="form-control" id="title" name="frm[<?php echo $inlang; ?>][title]" value="<?php echo $aArticleCont['title']; ?>">
                                    </div>
                                    <?php if ($isAuthor != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="author" class="form-label"><?php echo $isAuthor; ?></label>
                                            <input type="text" class="form-control" id="author" name="frm[<?php echo $inlang; ?>][author]" value="<?php echo $aArticleCont['author']; ?>">
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isSlug != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="slug" class="form-label"><?php echo $isSlug; ?></label>
                                            <input type="text" class="form-control" id="slug" name="frm[<?php echo $inlang; ?>][slug]" value="<?php echo $aArticleCont['slug']; ?>">
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isShortMessage != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="shortMessage" class="form-label"><?php echo $isShortMessage; ?></label>
                                            <textarea class="form-control" id="shortMessage" name="frm[<?php echo $inlang; ?>][shortMessage]" style="height: 100px !important;"><?php echo $aArticleCont['shortMessage']; ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isContent != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="content" class="form-label"><?php echo $isContent; ?></label>
                                            <div class="form-control click-edit-content border p-2" style="cursor:pointer; height: 254px;" data-target="#content<?php echo $inlang; ?>"><?php echo $aArticleCont['content']; ?></div>
                                            <textarea id="content<?php echo $inlang; ?>" name="frm[<?php echo $inlang; ?>][content]" style="display: none;"><?php echo $aArticleCont['content']; ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isContent2 != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="content2" class="form-label"><?php echo $isContent2; ?></label>
                                            <div class="form-control click-edit-content border p-2" style="cursor:pointer; height: 254px;" data-target="#content2<?php echo $kinlang; ?>"><?php echo $aArticleCont['content2']; ?></div>
                                            <textarea id="content2<?php echo $inlang; ?>" name="frm[<?php echo $inlang; ?>][content2]" style="display: none;"><?php echo $aArticleCont['content2']; ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isContent3 != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="content3" class="form-label"><?php echo $isContent3; ?></label>
                                            <div class="form-control click-edit-content border p-2" style="cursor:pointer; height: 254px;" data-target="#content3<?php echo $inlang; ?>"><?php echo $aArticleCont['content3']; ?></div>
                                            <textarea id="content3<?php echo $inlang; ?>" name="frm[<?php echo $inlang; ?>][content3]" style="display: none;"><?php echo $aArticleCont['content3']; ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isContent4 != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="content4" class="form-label"><?php echo $isContent4; ?></label>
                                            <div class="form-control click-edit-content border p-2" style="cursor:pointer; height: 254px;" data-target="#content4<?php echo $inlang; ?>"><?php echo $aArticleCont['content4']; ?></div>
                                            <textarea id="content4<?php echo $inlang; ?>" name="frm[<?php echo $inlang; ?>][content4]" style="display: none;"><?php echo $aArticleCont['content4']; ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isContentExtra1 != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="content_extra1" class="form-label"><?php echo $isContentExtra1; ?></label>
                                            <input type="text" class="form-control" id="content_extra1" name="frm[<?php echo $inlang; ?>][content_extra1]" value="<?php echo $aArticleCont['content_extra1']; ?>">
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isContentExtra2 != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="content_extra2" class="form-label"><?php echo $isContentExtra2; ?></label>
                                            <input type="text" class="form-control" id="content_extra2" name="frm[<?php echo $inlang; ?>][content_extra2]" value="<?php echo $aArticleCont['content_extra2']; ?>">
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isContentExtra3 != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="content_extra3" class="form-label"><?php echo $isContentExtra3; ?></label>
                                            <input type="text" class="form-control" id="content_extra3" name="frm[<?php echo $inlang; ?>][content_extra3]" value="<?php echo $aArticleCont['content_extra3']; ?>">
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isContentIcon != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="content_icon" class="form-label"><?php echo $isContentIcon; ?></label>
                                            <input type="file" class="form-control" id="content_icon" name="content_icon[<?php echo $inlang; ?>]">
                                            <?php if (!empty($aArticleCont['content_icon'])): ?>
                                                <div class="text-center mt-2">
                                                    <a href="<?php echo URL_UPLOAD . '/' . $aArticleCont['content_icon']; ?>" target=" _blank" data-allow-link="true">
                                                        <img src="<?php echo URL_UPLOAD . '/' . $aArticleCont['content_icon']; ?>" width="20%" height="20%"></img>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isContentAttach != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="contentAttach" class="form-label"><?php echo $isContentAttach; ?></label>
                                            <input type="file" class="form-control" id="contentAttach" name="contentAttach[<?php echo $inlang; ?>]">
                                            <?php if (!empty($aArticleCont['contentAttach'])): ?>
                                                <small class="text-muted d-block mt-2 ms-2">
                                                    ไฟล์ปัจจุบัน:&nbsp;&nbsp;<a href="<?php echo URL_UPLOAD . '/' . $aArticleCont['contentAttach']; ?>" target="_blank" data-allow-link="true"><?php echo basename($aArticleCont['contentAttach']); ?></a>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($showMain): ?>
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <?php if ($isStatus != ''): ?>
                                <div class="col-12 mb-3 form-check form-switch">
                                    <input type="checkbox" class="form-check-input" id="status" name="status" <?php echo $aArticle['status'] == 0 ? 'checked' : ''; ?>>
                                    <label for="status" class="form-check-label"><?php echo $isStatus; ?></label>
                                </div>
                            <?php endif; ?>
                            <?php if ($isDisplayTime != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="displaytime" class="form-label"><?php echo $isDisplayTime; ?></label>
                                    <input type="date" class="form-control" id="displaytime" name="displaytime" value="<?php echo ($aArticle['displaytime'] > 0) ? date('Y-m-d', $aArticle['displaytime']) : ''; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isEndTime != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="end_time" class="form-label"><?php echo $isEndTime; ?></label>
                                    <input type="date" class="form-control" id="end_time" name="end_time" value="<?php echo ($aArticle['end_time'] > 0) ? date('Y-m-d', $aArticle['end_time']) : ''; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isIcon != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="icon" class="form-label"><?php echo $isIcon; ?></label>
                                    <input type="file" class="form-control" id="icon" name="icon">
                                    <?php if (!empty($aArticle['icon'])): ?>
                                        <div class="text-center mt-2">
                                            <a href="<?php echo URL_UPLOAD . '/' . $aArticle['icon']; ?>" target=" _blank" data-allow-link="true">
                                                <img src="<?php echo URL_UPLOAD . '/' . $aArticle['icon']; ?>" width="20%" height="20%"></img>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($isIcon2 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="icon2" class="form-label"><?php echo $isIcon2; ?></label>
                                    <input type="file" class="form-control" id="icon2" name="icon2">
                                    <?php if (!empty($aArticle['icon2'])): ?>
                                        <div class="text-center mt-2">
                                            <a href="<?php echo URL_UPLOAD . '/' . $aArticle['icon2']; ?>" target=" _blank" data-allow-link="true">
                                                <img src="<?php echo URL_UPLOAD . '/' . $aArticle['icon2']; ?>" width="20%" height="20%"></img>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($isfileAttach != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="file_attach" class="form-label"><?php echo $isfileAttach; ?></label>
                                    <input type="file" class="form-control" id="file_attach" name="file_attach">
                                    <?php if (!empty($aArticle['file_attach'])): ?>
                                        <small class="text-muted d-block mt-2 ms-2">
                                            ไฟล์ปัจจุบัน:&nbsp;&nbsp;<a href="<?php echo URL_UPLOAD . '/' . $aArticle['file_attach']; ?>" target="_blank" data-allow-link="true"><?php echo basename($aData['file_attach']); ?></a>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($isCheckOption)): ?>
                                <div class="col-12 mb-3">
                                    <div>ตัวเลือก</div>
                                    <?php foreach ($isCheckOption as $k => $v): ?>
                                        <div class="form-check">
                                            <input type="radio" class="form-check-input" id="<?php echo $v . '_' . $k; ?>" name="checkOption" value="<?php echo $k; ?>" <?php echo $aArticle['checkOption'] == $k ? 'checked' : ''; ?>>
                                            <label for="<?php echo $v . '_' . $k; ?>" class="form-check-label"><?php echo $v; ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($isExtra1 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra1" class="form-label"><?php echo $isExtra1; ?></label>
                                    <input type="text" class="form-control" id="extra1" name="extra1" value="<?php echo $aArticle['extra1']; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isExtra2 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra2" class="form-label"><?php echo $isExtra2; ?></label>
                                    <input type="text" class="form-control" id="extra2" name="extra2" value="<?php echo $aArticle['extra2']; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isExtra3 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra3" class="form-label"><?php echo $isExtra3; ?></label>
                                    <input type="text" class="form-control" id="extra3" name="extra3" value="<?php echo $aArticle['extra3']; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isExtra4 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra4" class="form-label"><?php echo $isExtra4; ?></label>
                                    <input type="text" class="form-control" id="extra4" name="extra4" value="<?php echo $aArticle['extra4']; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isExtra5 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra5" class="form-label"><?php echo $isExtra5; ?></label>
                                    <input type="text" class="form-control" id="extra5" name="extra5" value="<?php echo $aArticle['extra5']; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isExtra6 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra6" class="form-label"><?php echo $isExtra6; ?></label>
                                    <input type="text" class="form-control" id="extra6" name="extra6" value="<?php echo $aArticle['extra6']; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isExtra7 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra7" class="form-label"><?php echo $isExtra7; ?></label>
                                    <input type="text" class="form-control" id="extra7" name="extra7" value="<?php echo $aArticle['extra7']; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isExtra8 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra8" class="form-label"><?php echo $isExtra8; ?></label>
                                    <input type="text" class="form-control" id="extra8" name="extra8" value="<?php echo $aArticle['extra8']; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isExtra9 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra9" class="form-label"><?php echo $isExtra9; ?></label>
                                    <input type="text" class="form-control" id="extra9" name="extra9" value="<?php echo $aArticle['extra9']; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isExtra10 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra10" class="form-label"><?php echo $isExtra10; ?></label>
                                    <input type="text" class="form-control" id="extra10" name="extra10" value="<?php echo $aArticle['extra10']; ?>">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeAowebModal();">ปิด</button>
        <button type="submit" class="btn btn-success">อัปเดต</button>
    </div>
</form>