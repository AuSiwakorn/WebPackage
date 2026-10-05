<?php
$isGroupOpt = $vals['isGroup'] ?? 'หมวดหมู่';
$isGroupIcon = $vals['isGroupIcon'] ?? '';
$isGroupImg = $vals['isGroupImg'] ?? '';
$isGroupImg2 = $vals['isGroupImg2'] ?? '';
$isGroupDetail = $vals['isGroupDetail'] ?? '';
$isGroupDetailExtra1 = $vals['isGroupDetailExtra1'] ?? '';
$isGroupDetailExtra2 = $vals['isGroupDetailExtra2'] ?? '';
$isGroupSlug = $vals['isGroupSlug'] ?? '';
$isGroupExtra1 = $vals['isGroupExtra1'] ?? '';
$isGroupExtra2 = $vals['isGroupExtra2'] ?? '';
$isGroupExtra3 = $vals['isGroupExtra3'] ?? '';
$isGroupExtra4 = $vals['isGroupExtra4'] ?? '';
$isGroupExtra5 = $vals['isGroupExtra5'] ?? '';

$showSubpost = (($vals['isSubpost'] ?? '') != '' && !empty($id));

$showSide = (
    $isGroupIcon != ''
    || $isGroupImg != ''
    || $isGroupImg2 != ''
    || $isGroupExtra1 != ''
    || $isGroupExtra2 != ''
    || $isGroupExtra3 != ''
    || $isGroupExtra4 != ''
    || $isGroupExtra5 != ''
    || $showSubpost
) ? true : false;

$aGroup = [];
if (!empty($id)) {
    $aGroup = DB_GET('site_articles_group', ['group_id' => $id, 'keysname' => $keysname]);
    $aGroup = is_array($aGroup) ? $aGroup : [];
}
?>
<style>
/* กล่อง preview ก่อนกดแก้: เนื้อหายาว/มีรูป ให้เลื่อนในกล่อง ไม่ทะลุ (ตอน is-editing = summernote จัดการเอง) */
.click-edit-content:not(.is-editing),
.click-edit-minibox:not(.is-editing) {
    overflow-y: auto;
}
.click-edit-content:not(.is-editing) img,
.click-edit-minibox:not(.is-editing) img {
    max-width: 100%;
    height: auto;
}
</style>
<div class="modal-header">
    <h5 class="modal-title"><?php echo $id == 0 ? 'เพิ่ม' . $isGroupOpt : 'แก้ไข' . $isGroupOpt ?></h5>
</div>
<button
    type="button"
    class="btn btn-secondary mt-3 mb-3"
    hx-get="/admbuilder/load.php?ac=articlesGroup&keysname=<?php echo $keysname; ?>"
    hx-target=".ao-builder-content"
    data-size="<?php echo $size; ?>"
    hx-vals='{"data":"<?php echo $options ?>","size":"<?php echo $size ?>"}'
    hx-swap="innerHTML">
    กลับ
</button>
<form method="post" enctype="multipart/form-data" hx-encoding="multipart/form-data" autocomplete="off" hx-post="/admbuilder/save.articles.php" data-skip-before="true">
    <div class="modal-body">
        <input type="hidden" name="keysname" value="<?php echo $keysname; ?>">
        <input type="hidden" name="id" value="<?php echo $id == 0 ? '' : $id ?>">
        <input type="hidden" name="ac" value="<?php echo $id == 0 ? 'groupAdd' : 'groupUpdate' ?>">
        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="copyThAll" name="copyThAll" value="1">
            <label class="form-check-label" for="copyThAll">ใช้เนื้อหาภาษาไทยกับทุกภาษา</label>
        </div>
        <div class="row">
            <?php $col = $showSide ? 'col-md-8' : 'col-md-12'; ?>
            <div class="<?php echo $col; ?>">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <?php foreach ($aConfig['language'] as $lang => $label): ?>
                                <li class="nav-item">
                                    <button class="nav-link <?= $lang == 'th' ? 'active' : '' ?>"
                                        data-bs-toggle="tab"
                                        data-bs-target="#lang-<?= $lang ?>"
                                        type="button" role="tab">
                                        <?= $label ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="tab-content mt-3">
                            <?php
                            $aGroupCont = [];
                            foreach ($aConfig['language'] as $inlang => $label):
                                if (!empty($id)) {
                                    $aGroupCont = DB_GET('site_articles_group_content', ['group_id' => $id, 'langkeys' => $inlang]);
                                    $aGroupCont = is_array($aGroupCont) ? $aGroupCont : [];
                                } ?>
                                <div class="tab-pane fade <?= $inlang == 'th' ? 'show active' : '' ?>" id="lang-<?= $inlang ?>" role="tabpanel">
                                    <div class="col-12 mb-3">
                                        <label for="group_name[<?php echo $inlang; ?>]" class="form-label">ชื่อ<?php echo $isGroupOpt; ?></label>
                                        <input type="text" class="form-control" id="group_name[<?php echo $inlang; ?>]" name="frm[<?php echo $inlang; ?>][group_name]" value="<?php echo $aGroupCont['group_name'] ?? ''; ?>">
                                    </div>
                                    <?php if ($isGroupSlug != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="group_slug[<?php echo $inlang; ?>]" class="form-label"><?php echo $isGroupSlug; ?></label>
                                            <input type="text" class="form-control" id="group_slug[<?php echo $inlang; ?>]" name="frm[<?php echo $inlang; ?>][group_slug]" value="<?php echo $aGroupCont['group_slug'] ?? ''; ?>">
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isGroupDetail != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="detail_<?php echo $inlang; ?>" class="form-label"><?php echo $isGroupDetail; ?></label>
                                            <div class="form-control click-edit-content border rounded p-2" style="cursor:pointer; height: 254px;" data-target="#detail_<?php echo $inlang; ?>"><?php echo $aGroupCont['detail'] ?? ''; ?></div>
                                            <textarea id="detail_<?php echo $inlang; ?>" name="frm[<?php echo $inlang; ?>][detail]" style="display: none;"><?php echo $aGroupCont['detail'] ?? ''; ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isGroupDetailExtra1 != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="detailExtra1_<?php echo $inlang; ?>" class="form-label"><?php echo $isGroupDetailExtra1; ?></label>
                                            <div class="form-control click-edit-content border rounded p-2" style="cursor:pointer; height: 254px;" data-target="#detailExtra1_<?php echo $inlang; ?>"><?php echo $aGroupCont['detailExtra1'] ?? ''; ?></div>
                                            <textarea id="detailExtra1_<?php echo $inlang; ?>" name="frm[<?php echo $inlang; ?>][detailExtra1]" style="display: none;"><?php echo $aGroupCont['detailExtra1'] ?? ''; ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($isGroupDetailExtra2 != ''): ?>
                                        <div class="col-12 mb-3">
                                            <label for="detailExtra2_<?php echo $inlang; ?>" class="form-label"><?php echo $isGroupDetailExtra2; ?></label>
                                            <div class="form-control click-edit-content border rounded p-2" style="cursor:pointer; height: 254px;" data-target="#detailExtra2_<?php echo $inlang; ?>"><?php echo $aGroupCont['detailExtra2'] ?? ''; ?></div>
                                            <textarea id="detailExtra2_<?php echo $inlang; ?>" name="frm[<?php echo $inlang; ?>][detailExtra2]" style="display: none;"><?php echo $aGroupCont['detailExtra2'] ?? ''; ?></textarea>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($showSide): ?>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <?php if ($isGroupIcon != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="icon" class="form-label"><?php echo $isGroupIcon; ?></label>
                                    <input type="file" class="form-control" id="icon" name="icon">
                                    <?php if (!empty($aGroup['icon'])): ?>
                                        <div class="text-center mt-2">
                                            <a href="<?php echo URL_UPLOAD . '/' . $aGroup['icon']; ?>" target=" _blank" data-allow-link="true">
                                                <img src="<?php echo URL_UPLOAD . '/' . $aGroup['icon']; ?>" width="20%" height="20%"></img>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($isGroupImg != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="img" class="form-label"><?php echo $isGroupImg; ?></label>
                                    <input type="file" class="form-control" id="img" name="img">
                                    <?php if (!empty($aGroup['img'])): ?>
                                        <div class="text-center mt-2">
                                            <a href="<?php echo URL_UPLOAD . '/' . $aGroup['img']; ?>" target=" _blank" data-allow-link="true">
                                                <img src="<?php echo URL_UPLOAD . '/' . $aGroup['img']; ?>" width="20%" height="20%"></img>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($isGroupImg2 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="img2" class="form-label"><?php echo $isGroupImg2; ?></label>
                                    <input type="file" class="form-control" id="img2" name="img2">
                                    <?php if (!empty($aGroup['img2'])): ?>
                                        <div class="text-center mt-2">
                                            <a href="<?php echo URL_UPLOAD . '/' . $aGroup['img2']; ?>" target=" _blank" data-allow-link="true">
                                                <img src="<?php echo URL_UPLOAD . '/' . $aGroup['img2']; ?>" width="20%" height="20%"></img>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($isGroupExtra1 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra_group1" class="form-label"><?php echo $isGroupExtra1; ?></label>
                                    <input type="text" class="form-control" id="extra_group1" name="extra_group1" value="<?php echo $aGroup['extra_group1'] ?? ''; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isGroupExtra2 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra_group2" class="form-label"><?php echo $isGroupExtra2; ?></label>
                                    <input type="text" class="form-control" id="extra_group2" name="extra_group2" value="<?php echo $aGroup['extra_group2'] ?? ''; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isGroupExtra3 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra_group3" class="form-label"><?php echo $isGroupExtra3; ?></label>
                                    <input type="text" class="form-control" id="extra_group3" name="extra_group3" value="<?php echo $aGroup['extra_group3'] ?? ''; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isGroupExtra4 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra_group4" class="form-label"><?php echo $isGroupExtra4; ?></label>
                                    <input type="text" class="form-control" id="extra_group4" name="extra_group4" value="<?php echo $aGroup['extra_group4'] ?? ''; ?>">
                                </div>
                            <?php endif; ?>
                            <?php if ($isGroupExtra5 != ''): ?>
                                <div class="col-12 mb-3">
                                    <label for="extra_group5" class="form-label"><?php echo $isGroupExtra5; ?></label>
                                    <input type="text" class="form-control" id="extra_group5" name="extra_group5" value="<?php echo $aGroup['extra_group5'] ?? ''; ?>">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($showSubpost) {
                        $group_id = (int) $id;
                        $articles_id = 0;
                        include __DIR__ . '/inner.articles.subpost.list.php';
                    } ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="modal-footer mt-3">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeAowebModal();">ปิด</button> &nbsp;
        <button type="submit" class="btn btn-success"><?php echo $id == 0 ? 'บันทึก' : 'อัปเดต' ?></button>
    </div>
</form>
