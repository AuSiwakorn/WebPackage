<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp|keysname', 'สามารถใช้ Addon Script ได้', 'redirect', 'SET');

$_seo_api = dirname(__FILE__) . '/api.php';
if (file_exists($_seo_api)) {
    include_once $_seo_api;
}
if (function_exists('Seo_ensureScriptsTable')) {
    Seo_ensureScriptsTable();
}

$lang     = getCurrentLang();
$mp       = REQ_get('mp', 'request', 'str', '');
$module   = REQ_get('module', 'request', 'str', '');
$ac       = isset($_POST['ac']) && !empty($_POST['ac']) ? $_POST['ac'] : REQ_get('ac', 'request', 'str', '');
$id       = REQ_get('id', 'request', 'int', 0);
$ch       = REQ_get('ch', 'get', 'str');
$page     = REQ_get('page', 'request', 'int', 1);
$keysword = REQ_get('keysword', 'request', 'str', '');
$numlist  = REQ_get('numlist', 'request', 'int', 20);
$keysname = REQ_get('keysname', 'request', 'str', 'config');
$isSecurMode = GlobalConfig_get('isSecurMode');

// ================================================================
// ── SECTION B: CRUD Script Manager Actions (site_seo_scripts) ──
// ================================================================

if ($ac == 'save') {
    $name     = REQ_get('name', 'post', 'str');
    $position = REQ_get('position', 'post', 'str', 'after_open_head');
    $code     = REQ_get('code', 'post', 'html');
    $block    = REQ_get('block', 'post', 'int', 0);

    if (empty($name) || empty($code)) {
        setRaiseMsg('กรุณากรอกชื่อสคริปต์และโค้ดสคริปต์ให้ครบถ้วน', _TIME_, 1);
        CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . ($id > 0 ? "&ac=edit&id=$id" : "&ac=add"));
        exit;
    }

    $data = [
        'name'     => $name,
        'code'     => $code,
        'position' => $position,
        'block'    => $block,
        'status'   => 1
    ];

    if ($id > 0) {
        $ok = DB_UP('site_seo_scripts', $data, ['id' => $id]);
        setRaiseMsg($ok ? 'Database successfully updated script.' : 'เกิดข้อผิดพลาดในการแก้ไขสคริปต์', _TIME_, $ok ? 0 : 1);
    } else {
        $new_id = DB_ADD('site_seo_scripts', $data);
        setRaiseMsg($new_id > 0 ? 'Database successfully added script.' : 'เกิดข้อผิดพลาดในการบันทึก', _TIME_, $new_id > 0 ? 0 : 1);
    }
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
    exit;
}

if ($ac == 'changestatus' && $id > 0) {
    $new_status = ($ch == '1') ? 1 : 0;
    DB_UP('site_seo_scripts', ['status' => $new_status], ['id' => $id]);
    setRaiseMsg('Database successfully change status.', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&page=" . $page);
    exit;
}

if ($ac == 'sortlist') {
    $aSortList = REQ_get('aSortList', 'post', 'array', []);
    if (is_array($aSortList) && count($aSortList) > 0) {
        foreach ($aSortList as $k => $v) {
            DB_UP('site_seo_scripts', ['sort' => intval($v)], ['id' => intval($k)]);
        }
    }
    setRaiseMsg('Database successfully set to top.', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&page=" . $page);
    exit;
}

if ($ac == 'deletescript') {
    $aIDList = isset($_POST['aIDList']) ? $_POST['aIDList'] : [];
    if (is_array($aIDList) && count($aIDList) > 0) {
        $count = 0;
        foreach ($aIDList as $v) {
            $vid = intval($v);
            if ($vid > 0) { DB_DEL('site_seo_scripts', ['id' => $vid]); $count++; }
        }
        setRaiseMsg('Delete data is successfully (' . $count . ' items).', _TIME_, 0);
    } elseif ($id > 0) {
        DB_DEL('site_seo_scripts', ['id' => $id]);
        setRaiseMsg('Delete data is successfully.', _TIME_, 0);
    } else {
        setRaiseMsg('Please select script for delete.', _TIME_, 1);
    }
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
    exit;
}

// ── ดึงข้อมูลสำหรับ CRUD List ──
$urladd    = _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&ac=add");
$urlsearch = _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
$url       = _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&numlist=" . $numlist);

if ($ac == 'search' && !empty($keysword)) {
    $aData = DB_LIST('site_seo_scripts', ['name' => ['LIKE', '%' . $keysword . '%']], $numlist, $page, 'ORDER BY sort ASC, id DESC');
} else {
    $aData = DB_LIST('site_seo_scripts', [], $numlist, $page, 'ORDER BY sort ASC, id DESC');
}
?>

<!-- ── Page Header ── -->
<div id="page-head">
    <div id="page-title">
        <h1 class="page-header text-overflow">AddOn Script</h1>
    </div>
</div>

<div id="page-content">
    <div class="row">
        <div class="col-xs-12"><?php displayRaiseMsg(); ?></div>
    </div>

    <?php if ($ac === 'add' || $ac === 'edit'):
        $title_page  = ($ac === 'add') ? 'เพิ่มสคริปต์ใหม่ (New Script)' : 'แก้ไขสคริปต์ (Edit Script)';
        $script_data = ['name' => '', 'position' => 'after_open_head', 'code' => '', 'block' => 0];
        if ($ac === 'edit' && $id > 0) {
            $row_data = DB_GET('site_seo_scripts', ['id' => $id]);
            if (is_array($row_data)) {
                $script_data = array_merge($script_data, $row_data);
            }
        }
    ?>

    <!-- ── Add / Edit Form ── -->
    <div class="panel">
        <div class="panel-heading">
            <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $title_page; ?></h3>
        </div>
        <form action="index.php?module=<?php echo _MODULE_; ?>&mp=<?php echo _MP_; ?>&ac=save&id=<?php echo $id; ?>" method="post" class="form-horizontal">
            <div class="panel-body">
                <div class="form-group mar-btm">
                    <label class="col-sm-3 control-label text-bold">ชื่อสคริปต์</label>
                    <div class="col-sm-9">
                        <input type="text" name="name" class="form-control input-lg"
                            value="<?php echo htmlspecialchars($script_data['name']); ?>"
                            placeholder="ระบุชื่อสคริปต์ เช่น Google Analytics, Facebook Pixel" required>
                    </div>
                </div>

                <div class="form-group mar-btm">
                    <label class="col-sm-3 control-label text-bold">ตำแหน่งติดตั้ง</label>
                    <div class="col-sm-9">
                        <select name="position" class="form-control input-lg">
                            <option value="after_open_head" <?php echo $script_data['position'] == 'after_open_head' ? 'selected' : ''; ?>>After Open Head (หลังเปิด &lt;head&gt;)</option>
                            <option value="before_close_head" <?php echo in_array($script_data['position'], ['head', 'before_close_head']) ? 'selected' : ''; ?>>Before Close Header (ก่อนปิด &lt;/head&gt;)</option>
                            <option value="after_open_body" <?php echo $script_data['position'] == 'after_open_body' ? 'selected' : ''; ?>>After Open Body (หลังเปิด &lt;body&gt;)</option>
                            <option value="before_close_body" <?php echo in_array($script_data['position'], ['body', 'before_close_body']) ? 'selected' : ''; ?>>Before Close Body (ก่อนปิด &lt;/body&gt;)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group mar-btm">
                    <label class="col-sm-3 control-label text-bold">โค้ดสคริปต์</label>
                    <div class="col-sm-9">
                        <textarea name="code" rows="12" class="form-control"
                            placeholder="วางโค้ดสคริปต์ที่นี่..." style="font-family: monospace;" required><?php echo htmlspecialchars($script_data['code']); ?></textarea>
                    </div>
                </div>
                <div class="form-group mar-btm">
                    <label class="col-sm-3 control-label text-bold">การบล็อกด้วย Cookie Consent</label>
                    <div class="col-sm-9" style="padding-top:7px;">
                        <?php $is_b = (isset($script_data['block']) && intval($script_data['block']) == 1); ?>
                        <label class="form-checkbox form-normal form-primary <?php echo $is_b ? 'active' : ''; ?>" style="font-size:15px;cursor:pointer;">
                            <input type="checkbox" name="block" value="1" <?php echo $is_b ? 'checked' : ''; ?>>
                            <strong>บล็อกสคริปต์เมื่อยังไม่ยินยอม (เติม <code>type="text/plain"</code>)</strong>
                        </label>
                        <p class="help-block" style="margin-top:5px;"><i class="fa fa-info-circle text-info"></i> ติ๊กเลือก สำหรับ สคริปต์ที่ต้องการใช้ type="text/plain"</p>
                    </div>
                </div>
            </div>
            <div class="panel-footer text-right">
                <a href="<?php echo $urlsearch; ?>" class="btn btn-default"><i class="fa fa-times"></i> ยกเลิก</a>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> บันทึกข้อมูล</button>
            </div>
        </form>
    </div>

    <?php else: ?>

    <!-- ================================================================ -->
    <!-- ── PART 1: CRUD Script Manager (site_seo_scripts) ──             -->
    <!-- ================================================================ -->

    <form id="form1" name="form1" method="post" action="index.php?module=<?php echo _MODULE_; ?>&mp=<?php echo _MP_; ?>">
        <input type="hidden" name="ac" id="form_ac" value="deletescript" class="ac" />

        <!-- Action Bar -->
        <div class="row" style="margin-bottom:10px;">
            <div class="col-md-6 text-left">
                <a href="<?php echo $urladd; ?>">
                    <div class="btn btn-success"><i class="fa fa-plus" style="font-size:12px;"></i> New Script</div>
                </a>
            </div>
            <div class="col-md-6 text-right">
                <button type="submit" class="btn btn-danger" onclick="$('.ac').val('deletescript'); return confirm('คุณต้องการลบสคริปต์ที่เลือกไว้ใช่หรือไม่?');">
                    <i class="fa fa-times-circle" style="font-size:12px;"></i> Delete Selected
                </button>
            </div>
        </div>

        <div class="panel">
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ชื่อสคริปต์</th>
                                <th>ตำแหน่ง</th>
                                <th>Consent Block</th>
                                <th>ตัวอย่างสคริปต์</th>
                                <th>Status</th>
                                <th class="text-center" colspan="2">Action</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:16px;">
                            <?php if (isset($aData['num_rows']) && $aData['num_rows'] > 0): ?>
                                <?php foreach ($aData['data'] as $v):
                                    $row_id      = $v['id'];
                                    $row_name    = !empty($v['name'])     ? $v['name']     : '---';
                                    $row_pos     = !empty($v['position']) ? $v['position'] : 'head';
                                    $row_code    = isset($v['code'])      ? $v['code']     : '';
                                    $row_blocked = isset($v['block'])     ? $v['block']    : 0;
                                    $row_active  = isset($v['status'])    ? $v['status']   : 1;

                                    $urledit   = _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&ac=edit&id=' . $row_id);
                                    $urldelete = _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&ac=deletescript&id=' . $row_id);

                                    if ($row_active == 1) {
                                        $statusIcon = '<i class="fa fa-toggle-on" style="font-size:24px;color:#8BC34A;"></i>';
                                        $urlcs = _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&id=' . $row_id . '&ch=0&ac=changestatus&page=' . $page);
                                    } else {
                                        $statusIcon = '<i class="fa fa-toggle-off" style="font-size:24px;color:#ccc;"></i>';
                                        $urlcs = _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&id=' . $row_id . '&ch=1&ac=changestatus&page=' . $page);
                                    }

                                    if ($row_pos === 'after_open_head') {
                                        $pos_lbl = '<span class="text-success text-bold">After Open Head</span>';
                                    } elseif (in_array($row_pos, ['head', 'before_close_head'])) {
                                        $pos_lbl = '<span class="text-primary text-bold">Before Close Header</span>';
                                    } elseif ($row_pos === 'after_open_body') {
                                        $pos_lbl = '<span class="text-info text-bold">After Open Body</span>';
                                    } else {
                                        $pos_lbl = '<span class="text-purple text-bold">Before Close Body</span>';
                                    }

                                    $block_badge = ($row_blocked == 1)
                                        ? '<span>บล็อกจนกว่ายินยอม</span>'
                                        : '<span>สคริปต์ปกติ</span>';
                                    $preview     = htmlspecialchars(mb_strimwidth($row_code, 0, 80, '...'));
                                ?>
                                    <tr>
                                        <td><?php echo $row_id; ?></td>
                                        <td>
                                            <a style="font-size:18px;" class="btn-link text-bold" href="<?php echo $urledit; ?>">
                                                <?php echo htmlspecialchars($row_name); ?>
                                            </a>
                                        </td>
                                        <td><?php echo $pos_lbl; ?></td>
                                        <td><?php echo $block_badge; ?></td>
                                        <td style="font-family:monospace;font-size:11px;color:#555;"><?php echo $preview; ?></td>
                                        <td>
                                            <a href="<?php echo $urlcs; ?>" title="คลิกเพื่อสลับสถานะ">
                                                <?php echo $statusIcon; ?>
                                            </a>
                                        </td>
                                        <td class="min-width text-center">
                                            <div class="btn-groups">
                                                <a href="<?php echo $urledit; ?>" class="btn btn-icon demo-pli-pen-5 icon-lg add-tooltip" data-original-title="Edit Script" data-container="body"></a>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" name="aIDList[]" value="<?php echo $row_id; ?>" />
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted pad-all" style="padding:20px;">
                                        <strong>ยังไม่มีข้อมูลสคริปต์ สามารถกดปุ่ม "+ New Script" ด้านบนเพื่อเพิ่มข้อมูล</strong>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="row">
                    <div class="col-sm-5">
                        <div>Showing of <span style="font-size:18px;padding:2px 8px;font-weight:bold;"><?php echo intval(@$aData['num_rows']); ?></span> entries</div>
                    </div>
                    <div class="col-sm-7 text-right">
                        <?php if (function_exists('BuilListPage')) { BuilListPage(@$aData, @$url, @$page); } ?>
                    </div>
                </div>           
            </div>
        </div>
        <div>
            <?php
            pre("หลังเปิด head\n" . htmlspecialchars("<?php AddOnScript('AfterOpenHead'); ?>"));
            pre("ก่อนปิด head\n" . htmlspecialchars("<?php AddOnScript('BeforCloseHeader'); ?>"));
            pre("หลังเปิด body\n" . htmlspecialchars("<?php AddOnScript('AfterOpenBody'); ?>"));
            pre("ก่อนปิด body\n" . htmlspecialchars("<?php AddOnScript('BeforCloseBody'); ?>"));
            ?>
        </div>
    </form>

    <?php endif; /* end list/add/edit */ ?>

</div><!-- /#page-content -->