<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Hash File ได้', 'redirect', 'SET');
if (!is_dir(PATH_UPLOAD . '/hash')) {
    mkdir(PATH_UPLOAD . '/hash', 0777, true);
}
$token = GlobalConfig_get('telegram_token', '');
$chat_id = GlobalConfig_get('chat_id', '');
$pagelink = _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_);
$page = REQ_get('page', 'request', 'int', 1);
$dirs = array_merge(
    glob(PATH_WEB_ROOT . '/*', GLOB_ONLYDIR),
    array_filter(glob(PATH_WEB_ROOT . '/*.*'), 'is_file')
);
if (_AC_ !== '') {
    if (_AC_ == 'Save') {
        PERMIT::_PERMIT(_MODULE_, 'You can process Hashfile', 'สามารถดำเนินการ Hash File ได้', 'redirect', '');
        $folders = REQ_get('folders', 'post', 'array', '');
        if (!empty($folders)) {
            foreach ($folders as $v) {
                $fname = PATH_UPLOAD . '/hash/' . $v . '.txt';
                if (!file_exists($fname)) {
                    $ignore = read_txt_json(PATH_UPLOAD . '/hash/ignore_' . $v . '.txt');
                    $data = fetchFileList(PATH_WEB_ROOT . '/' . $v, $ignore);
                    write_txt_json($fname, $data);
                    unset($aHash);
                }
            }
        }
        foreach ($dirs as $v) {
            $dirname = basename($v);
            $fnamePath = PATH_UPLOAD . '/hash/' . $dirname . '.txt';
            $fnameIgnore = PATH_UPLOAD . '/hash/ignore_' . $dirname . '.txt';
            if (!in_array($dirname, $folders) && file_exists($fnamePath)) {
                unlink($fnamePath);
                updateHash($dirname);
                if (file_exists($fnameIgnore)) {
                    unlink($fnameIgnore);
                }
            }
        }
    } elseif (_AC_ == 'Ignore') {
        PERMIT::_PERMIT(_MODULE_, 'You can process Hashfile', 'สามารถดำเนินการ Hash File ได้', 'redirect', '');
        $dirname = REQ_get('dirname', 'post', 'str', '');
        $ignore = REQ_get('ignore', 'post', 'array', '');
        $fnameIgnore = PATH_UPLOAD . '/hash/ignore_' . $dirname . '.txt';
        $fnamePath = PATH_UPLOAD . '/hash/' . $dirname . '.txt';
        if ($dirname === '' || empty($ignore)) {
            if (file_exists($fnameIgnore)) {
                unlink($fnameIgnore);
            }
        } else {
            write_txt_json($fnameIgnore, $ignore);
        }
        if (file_exists($fnamePath)) {
            $ignore = read_txt_json($fnameIgnore);
            $data = fetchFileList(PATH_WEB_ROOT . '/' . $dirname, $ignore);
            write_txt_json($fnamePath, $data);
            updateHash($dirname);
        }
    } elseif (_AC_ == 'Scan') {
        PERMIT::_PERMIT(_MODULE_, 'You can process Hashfile', 'สามารถดำเนินการ Hash File ได้', 'redirect', '');
        chackHashFile();
        $msg_db = Insert_HashError();
        saveHashLog($msg_db);
    } elseif (_AC_ == 'Update') {
        PERMIT::_PERMIT(_MODULE_, 'You can process Hashfile', 'สามารถดำเนินการ Hash File ได้', 'redirect', '');
        $token = REQ_get('token', 'post', 'str', '');
        $chat_id = REQ_get('chat_id', 'post', 'str', '');
        GlobalConfig_update_config_keys('telegram_token', $token);
        GlobalConfig_update_config_keys('chat_id', $chat_id);
    }
    setRaiseMsg('' . _AC_ . ' file is successfully.', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
    exit;
}
$oneYearAgo = date("Y-m-d H:i:s", strtotime("-1 year"));
$aOld = DB_LIST("hash_log", ["created_at" => ["<", $oneYearAgo]]);
if (!empty($aOld['data'])) {
    foreach ($aOld['data'] as $v) {
        DB_DEL("hash_log", ["id" => $v['id']]);
    }
}
?>
<div id="page-head">
    <div id="page-title">
        <h1 class="page-header">Hash File</h1>
    </div>
</div>
<div id="page-content">
    <?php echo displayRaiseMsg(); ?>
    <form method="post" name="form1" id="form1">
        <input type="hidden" name="ac" id="ac">
        <div class="panel">
            <div class="panel-heading">
                <h3 class="panel-title text-main text-bold">Gennerate File</h3>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <button id="demo-btn-addrow" class="btn btn-primary submit" onclick="setHashAction('Scan')">Scan</button>
                    </div>
                    <div class="col-md-6 text-right">
                        <button id="demo-btn-addrow" class="btn btn-mint submit" onclick="setHashAction('Save')"> <i class="fa fa-save" style="font-size: 12px;"></i> Save</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th width="33%">Path name</th>
                                <th width="32%">Last modified</th>
                                <th width="15%"></th>
                                <th width="20%"></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $data = read_txt_json(PATH_UPLOAD . '/hash/hash.txt');
                            $ckHash = !empty($data) ? $data : ['error' => [], 'newfile' => []];
                            foreach ($dirs as $v) {
                                $folders = basename($v);
                                $time = date("d-m-Y H:i:s", filemtime($v));
                                $ckFile = file_exists(PATH_UPLOAD . '/hash/' . $folders . '.txt');
                                $se = $ckFile ? 'checked' : '';
                                $isIcon = getStatusIcon($folders, $ckFile, $ckHash) ?>
                                <tr>
                                    <td><?php echo $folders; ?></td>
                                    <td><?php echo $time; ?></td>
                                    <td><?php echo $isIcon; ?></td>
                                    <td> <?php if (is_dir($v)) { ?>
                                            <button type="button" name="ignore" class="btn btn-info" data-target="#demo-default-modal" data-toggle="modal" onclick="modalIgnore('<?php echo $folders; ?>');">Ignore</button>
                                    </td> <?php } ?>
                                <td><input <?php echo $se; ?> type="checkbox" name="folders[<?php echo $folders; ?>]" value="<?php echo $folders; ?>"></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal fade" id="demo-default-modal" role="dialog" tabindex="-1" aria-labelledby="demo-default-modal" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bord-btm">
                        <button type="button" class="close" data-dismiss="modal"><i class="pci-cross pci-circle"></i></button>
                        <h4 class="modal-title" id="modal-head">Path name</h4>
                    </div>
                    <div class="modal-body" id="modal-data"></div>
                    <div class="modal-footer" style="display: flex; justify-content: space-between;">
                        <div style="flex-grow: 1; text-align: left;">
                            <button type="button" class="btn btn-danger" onclick="resetIgnore();"><i class="fa fa-times-circle" style="font-size: 12px;"></i> Clear</button>
                        </div>
                        <div style="text-align: right;">
                            <button type="button" data-dismiss="modal" class="btn btn-default">Close</button>
                            <button type="submit" class="btn btn-mint" onclick="setHashAction('Ignore');"><i class="fa fa-save" style="font-size: 12px;"></i> Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <div class="panel">
        <div class="panel-heading">
            <h3 class="panel-title text-main text-bold">Hash History</h3>
        </div>
        <div class="panel-body">
            <?php $aGetLogs = DB_LIST("hash_log", [], 50, $page, "ORDER BY id DESC"); ?>
            <?php if (empty($aGetLogs['data'])) { ?>
                <div class="text-center">ไม่พบประวัติการตรวจสอบ</div>
            <?php } else { ?>
                <div class="table-responsive">
                    <table class="table table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th width=100 class="text-center">ID</th>
                                <th class="text-left">Detail</th>
                                <th width=220 class="text-center">Create At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($aGetLogs['data'] as $k => $v) {
                                $lines = explode("\n", trim($v['message']));

                                $section = [];
                                $current = null;

                                foreach ($lines as $line) {
                                    $line = trim($line);
                                    if ($line === '') continue;

                                    if (strpos($line, 'ไฟล์ที่ถูกแก้ไข') !== false) {
                                        $current = 'ไฟล์ที่ถูกแก้ไข';
                                        $section[$current] = [];
                                    } elseif (strpos($line, 'ไฟล์ที่ถูกเพิ่ม') !== false) {
                                        $current = 'ไฟล์ที่ถูกเพิ่ม';
                                        $section[$current] = [];
                                    } else {
                                        if ($current) {
                                            $section[$current][] = $line;
                                        }
                                    }
                                }

                                $hasFiles = false;
                                foreach ($section as $items) {
                                    if (count($items) > 0) {
                                        $hasFiles = true;
                                        break;
                                    }
                                }
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo $v['id']; ?></td>
                                    <td class="text-left">
                                        <?php if ($hasFiles) { ?>
                                            <?php foreach ($section as $title => $items) {
                                                $count = count($items);
                                                if ($count == 0) continue;

                                                if ($title === 'ไฟล์ที่ถูกแก้ไข') {
                                                    $labelClass = 'label-danger';
                                                    $icon = 'fa-edit';
                                                    $borderColor = '#d9534f';
                                                } else {
                                                    $labelClass = 'label-warning';
                                                    $icon = 'fa-plus-circle';
                                                    $borderColor = '#f0ad4e';
                                                }
                                                $collapseId = "collapse_" . $v['id'] . "_" . md5($title);
                                            ?>
                                                <div style="margin-bottom: 8px;">
                                                    <a data-toggle="collapse"
                                                        href="#<?php echo $collapseId; ?>"
                                                        style="text-decoration: none; display: inline-block; padding: 6px 12px; border-radius: 4px; border-left: 3px solid <?php echo $borderColor; ?>; background: #f9f9f9; width: 100%;">
                                                        <i class="fa <?php echo $icon; ?>"></i>
                                                        <strong><?php echo $title; ?></strong>
                                                        <span class="label <?php echo $labelClass; ?>" style="margin-left: 8px;">
                                                            <?php echo $count; ?>
                                                        </span>
                                                        <i class="fa fa-chevron-down pull-right" style="margin-top: 2px; font-size: 12px;"></i>
                                                    </a>

                                                    <div id="<?php echo $collapseId; ?>" class="collapse" style="margin-top: 8px;">
                                                        <div style="background: #fafafa; border: 1px solid #e0e0e0; border-radius: 3px; padding: 10px; max-height: 300px; overflow-y: auto;">
                                                            <?php foreach ($items as $idx => $file) { ?>
                                                                <div style="padding: 6px 10px; margin-bottom: 4px; background: white; border-left: 2px solid <?php echo $borderColor; ?>; font-family: 'Courier New', Consolas, monospace; font-size: 12px; word-break: break-all;">
                                                                    <i class="fa fa-file-code-o text-muted"></i>
                                                                    <?php echo htmlspecialchars($file); ?>
                                                                </div>
                                                            <?php } ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        <?php } else { ?>
                                            ไม่พบความผิดปกติ
                                        <?php } ?>
                                    </td>
                                    <td class="text-center">
                                        <div style="line-height: 1.6;">
                                            <i class="demo-pli-clock text-purple"></i>
                                            <?php echo date("d/m/Y H:i:s", strtotime($v['created_at'])); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <div class="row">
                    <div class="col-md-6" style="margin-top: 20px;">
                        <div class="text-muted">
                            <i class="fa fa-database"></i>
                            ทั้งหมด <strong><?php echo number_format($aGetLogs['num_rows']); ?></strong> รายการ / แสดง <strong>50</strong> รายการต่อหน้า
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <?php BuilListPage($aGetLogs, $pagelink, $page); ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>