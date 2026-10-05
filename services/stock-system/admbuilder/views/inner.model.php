<?php
/**
 * ฟอร์มแก้ไข textbox ของ AO Builder — แยกแท็บตามภาษา
 *
 * ตัวแปรที่ต้องมีก่อน include (เตรียมจาก editor.php / load.php):
 *   $keysname : keysname ฐาน (ไม่มี langkey ต่อท้าย)
 *   $aLangs   : [langkey => label] จาก ADMBUILDER::AOLANGS()
 *   $aBox     : [langkey => row]   จาก ADMBUILDER::AOBOXDATA($keysname)
 *   $vals     : [isXxx => ป้ายกำกับ] ที่ decode มาจาก AOSET — "ลำดับคีย์คือลำดับช่องที่แสดง"
 *   $isXxx    : ป้ายกำกับรายช่อง (เผื่อผู้เรียกรุ่นเก่าที่ไม่ได้ส่ง $vals มา)
 *
 * ลำดับช่องยึดตามลำดับที่เขียนใน AOSET ของไฟล์ธีม ไม่ใช่ลำดับตายตัวในไฟล์นี้
 * (json_encode/json_decode รักษาลำดับคีย์ไว้ครบ) อยากสลับช่องก็สลับบรรทัดใน AOSET ได้เลย
 *
 * เก็บลง webbuilder คนละแถวต่อภาษา คีย์ = <keysname><langkey>
 * ธีมจึงอ่านด้วย AOGET($keysname . _LANG_, ...) ได้เหมือนเดิม
 */
$aLangs = isset($aLangs) && is_array($aLangs) ? $aLangs : ADMBUILDER::AOLANGS();
$aBox   = isset($aBox) && is_array($aBox) ? $aBox : ADMBUILDER::AOBOXDATA($keysname);

// คีย์ที่ AOSET รองรับ → ชนิดช่อง, ชื่อ field ที่ save.php อ่าน, คอลัมน์ใน webbuilder
$aFieldMap = array(
    'isTitle'   => array('type' => 'text',     'name' => 'title',   'id' => 'title',   'col' => 'web_title'),
    'isMiniBox' => array('type' => 'richtext', 'name' => 'minibox', 'id' => 'minibox', 'col' => 'web_minibox'),
    'isDesc'    => array('type' => 'textarea', 'name' => 'desc',    'id' => 'desc',    'col' => 'web_desc'),
    'isContent' => array('type' => 'richtext', 'name' => 'content', 'id' => 'content', 'col' => 'web_content'),
    'isLink'    => array('type' => 'text',     'name' => 'link',    'id' => 'link',    'col' => 'web_link'),
    'isImg'     => array('type' => 'file',     'name' => 'img',     'id' => 'img',     'col' => 'web_img'),
    'isImgAlt'  => array('type' => 'text',     'name' => 'imgAlt',  'id' => 'imgAlt',  'col' => 'web_img_alt'),
    'isExtra1'  => array('type' => 'text',     'name' => 'extra1',  'id' => 'extra1',  'col' => 'web_extra1'),
    'isExtra2'  => array('type' => 'text',     'name' => 'extra2',  'id' => 'extra2',  'col' => 'web_extra2'),
);

// ช่องที่จะแสดง + ลำดับ: เอาจาก $vals ก่อน (ลำดับตามที่เขียนในไฟล์ธีม)
// ถ้าไม่มี $vals ค่อยถอยไปอ่านตัวแปร $isXxx ตามลำดับใน $aFieldMap
$aFields = array();
if (isset($vals) && is_array($vals)) {
    foreach ($vals as $fk => $flabel) {
        if (isset($aFieldMap[$fk]) && trim((string) $flabel) !== '') {
            $aFields[$fk] = (string) $flabel;
        }
    }
} else {
    foreach (array_keys($aFieldMap) as $fk) {
        $flabel = isset($$fk) ? (string) $$fk : '';
        if (trim($flabel) !== '') {
            $aFields[$fk] = $flabel;
        }
    }
}
?>
<!-- hx-encoding จำเป็นสำหรับอัปโหลดไฟล์: HTMX ไม่อ่าน enctype ของ <form> ถ้าไม่ใส่ ไฟล์จะไม่ถูกส่งไปเลย -->
<form method="post" enctype="multipart/form-data" hx-encoding="multipart/form-data" autocomplete="off" hx-post="/admbuilder/save.php" data-skip-before="true">
  <div class="modal-header">
    <h5 class="modal-title">แก้ไขข้อมูล</h5>
  </div>
  <div class="modal-body">
    <input type="hidden" name="ac" value="savebox">
    <input type="hidden" name="keysname" value="<?php echo $keysname; ?>">

    <?php if (count($aLangs) > 1): ?>
      <ul class="nav nav-tabs" role="tablist">
        <?php $i = 0;
        foreach ($aLangs as $lk => $label): ?>
          <li class="nav-item">
            <button class="nav-link <?php echo ($i === 0) ? 'active' : ''; ?>"
              data-bs-toggle="tab"
              data-bs-target="#aobox-<?php echo $lk; ?>"
              type="button" role="tab">
              <?php echo htmlspecialchars($label); ?>
            </button>
          </li>
        <?php $i++;
        endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="tab-content mt-3">
      <?php $i = 0;
      foreach ($aLangs as $lk => $label):
        $row = isset($aBox[$lk]) && is_array($aBox[$lk]) ? $aBox[$lk] : array();
      ?>
        <div class="tab-pane fade <?php echo ($i === 0) ? 'show active' : ''; ?>" id="aobox-<?php echo $lk; ?>" role="tabpanel">

          <?php foreach ($aFields as $fk => $flabel):
            $f    = $aFieldMap[$fk];
            $fid  = $f['id'] . '_' . $lk;
            $fval = $row[$f['col']] ?? '';
          ?>
            <div class="col-12 mb-3">
              <label for="<?php echo $fid; ?>" class="form-label"><?php echo $flabel; ?></label>

              <?php if ($f['type'] === 'text'): ?>
                <input type="text" id="<?php echo $fid; ?>" name="frm[<?php echo $lk; ?>][<?php echo $f['name']; ?>]" class="form-control" value="<?php echo $fval; ?>">

              <?php elseif ($f['type'] === 'textarea'): ?>
                <textarea id="<?php echo $fid; ?>" name="frm[<?php echo $lk; ?>][<?php echo $f['name']; ?>]" class="form-control" style="height: 100px !important;"><?php echo $fval; ?></textarea>

              <?php elseif ($f['type'] === 'richtext'): ?>
                <div class="form-control click-edit-minibox border" style="cursor:pointer; min-height: 200px;padding: 15px;overflow-y: auto;" data-target="#<?php echo $fid; ?>"><?php echo $fval; ?></div>
                <textarea id="<?php echo $fid; ?>" name="frm[<?php echo $lk; ?>][<?php echo $f['name']; ?>]" style="display: none;"><?php echo htmlspecialchars($fval); ?></textarea>

              <?php elseif ($f['type'] === 'file'): ?>
                <input type="file" id="<?php echo $fid; ?>" name="img[<?php echo $lk; ?>]" class="form-control">
                <?php if ($fval != ''): ?>
                  <div class="text-center mt-2">
                    <a href="<?php echo URL_UPLOAD . '/' . $fval; ?>" target="_blank" data-allow-link="true">
                      <img src="<?php echo URL_UPLOAD . '/' . $fval; ?>" width="20%" height="20%">
                    </a>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>

        </div>
      <?php $i++;
      endforeach; ?>
    </div>
  </div>
  <div class="modal-footer">
    <button type="button" class="btn btn-secondary" onclick="closeAowebModal();">ปิด</button>
    <button type="submit" class="btn btn-success">บันทึก</button>
  </div>
</form>
