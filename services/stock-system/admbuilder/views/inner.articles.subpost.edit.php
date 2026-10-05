<?php
$isSubOpt     = $vals['isSubpost'] ?? 'รายการ';
$isSubIcon    = $vals['isSubIcon'] ?? 'รูป';
$isSubtitle   = $vals['isSubtitle'] ?? 'หัวข้อ';
$isSubMessage = $vals['isSubMessage'] ?? 'ข้อความสั้น';
$isSubText    = $vals['isSubText'] ?? 'เนื้อหา';
$parentQs     = 'keysname=' . urlencode($keysname) . '&group_id=' . (int) $group_id . '&articles_id=' . (int) $articles_id;
$backUrl      = ((int) $articles_id > 0)
    ? '/admbuilder/load.php?ac=articlesEdit&id=' . (int) $articles_id . '&keysname=' . urlencode($keysname)
    : '/admbuilder/load.php?ac=articlesGroupEdit&id=' . (int) $group_id . '&keysname=' . urlencode($keysname);

$aSub = [];
if (!empty($id)) {
    $aSub = DB_GET('site_articles_subpost', ['subpost_id' => $id]);
    $aSub = is_array($aSub) ? $aSub : [];
}
?>
<div class="modal-header">
    <h5 class="modal-title"><?php echo $id == 0 ? 'เพิ่ม' . $isSubOpt : 'แก้ไข' . $isSubOpt; ?></h5>
</div>
<button
    type="button"
    class="btn btn-secondary mt-3 mb-3"
    hx-get="<?php echo $backUrl; ?>"
    hx-target=".ao-builder-content"
    data-size="<?php echo $size; ?>"
    hx-vals='{"data":"<?php echo $options ?>","size":"<?php echo $size ?>"}'
    hx-swap="innerHTML">
    กลับ
</button>
<form method="post" enctype="multipart/form-data" hx-encoding="multipart/form-data" autocomplete="off" hx-post="/admbuilder/save.articles.php" data-skip-before="true"
    data-ao-return="<?php echo htmlspecialchars($backUrl, ENT_QUOTES); ?>"
    data-ao-return-data="<?php echo htmlspecialchars($options ?? '', ENT_QUOTES); ?>"
    data-ao-return-size="<?php echo htmlspecialchars($size ?? '', ENT_QUOTES); ?>">
    <div class="modal-body">
        <input type="hidden" name="keysname" value="<?php echo htmlspecialchars($keysname, ENT_QUOTES); ?>">
        <input type="hidden" name="group_id" value="<?php echo (int) $group_id; ?>">
        <input type="hidden" name="articles_id" value="<?php echo (int) $articles_id; ?>">
        <input type="hidden" name="id" value="<?php echo $id == 0 ? '' : $id; ?>">
        <input type="hidden" name="ac" value="<?php echo $id == 0 ? 'subpostAdd' : 'subpostUpdate'; ?>">
        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="copyThAll" name="copyThAll" value="1">
            <label class="form-check-label" for="copyThAll">ใช้เนื้อหาภาษาไทยกับทุกภาษา</label>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs" role="tablist">
                            <?php foreach ($aConfig['language'] as $lang => $label): ?>
                                <li class="nav-item">
                                    <button class="nav-link <?= $lang == 'th' ? 'active' : '' ?>"
                                        data-bs-toggle="tab"
                                        data-bs-target="#sublang-<?= $lang ?>"
                                        type="button" role="tab">
                                        <?= $label ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="tab-content mt-3">
                            <?php
                            $aCont = [];
                            foreach ($aConfig['language'] as $inlang => $label):
                                if (!empty($id)) {
                                    $aCont = DB_GET('site_articles_subpost_content', ['subpost_id' => $id, 'langkeys' => $inlang]);
                                    $aCont = is_array($aCont) ? $aCont : [];
                                } ?>
                                <div class="tab-pane fade <?= $inlang == 'th' ? 'show active' : '' ?>" id="sublang-<?= $inlang ?>" role="tabpanel">
                                    <div class="col-12 mb-3">
                                        <label class="form-label"><?php echo $isSubtitle; ?></label>
                                        <input type="text" class="form-control" name="frm[<?php echo $inlang; ?>][subtitle]" value="<?php echo htmlspecialchars($aCont['subtitle'] ?? '', ENT_QUOTES); ?>">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label"><?php echo $isSubMessage; ?></label>
                                        <input type="text" class="form-control" name="frm[<?php echo $inlang; ?>][subMessage]" value="<?php echo htmlspecialchars($aCont['subMessage'] ?? '', ENT_QUOTES); ?>">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label"><?php echo $isSubText; ?></label>
                                        <div class="form-control click-edit-content border rounded p-2" style="cursor:pointer; height: 200px;" data-target="#subText_<?php echo $inlang; ?>"><?php echo $aCont['subText'] ?? ''; ?></div>
                                        <textarea id="subText_<?php echo $inlang; ?>" name="frm[<?php echo $inlang; ?>][subText]" style="display: none;"><?php echo $aCont['subText'] ?? ''; ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="col-12 mb-3">
                            <label for="subIcon" class="form-label"><?php echo $isSubIcon; ?></label>
                            <input type="file" class="form-control" id="subIcon" name="subIcon">
                            <?php if (!empty($aSub['subIcon'])): ?>
                                <div class="text-center mt-2">
                                    <a href="<?php echo URL_UPLOAD . '/' . $aSub['subIcon']; ?>" target="_blank" data-allow-link="true">
                                        <img src="<?php echo URL_UPLOAD . '/' . $aSub['subIcon']; ?>" width="40%">
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer mt-3">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="closeAowebModal();">ปิด</button> &nbsp;
        <button type="submit" class="btn btn-success"><?php echo $id == 0 ? 'บันทึก' : 'อัปเดต'; ?></button>
    </div>
</form>
