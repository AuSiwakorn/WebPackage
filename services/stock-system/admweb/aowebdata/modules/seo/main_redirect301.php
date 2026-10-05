<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp|metakey', 'สามารถใช้งาน 301 Redirect ได้', 'redirect', 'SET');
define("PLUGIN_INC", 'seo');

$ac = REQ_get('ac', 'str', '');
if ($ac == 'add') {
    $source = REQ_get('source', 'string', '');
    $target = REQ_get('target', 'string', '');

    if ($source && $target) {
        $data = array(
            'source' => $source,
            'target' => $target
        );
        DB_ADD('site_seo_redir', $data);
        setRaiseMsg('เพิ่ม Redirect 301 สำเร็จ', _TIME_, 'ok');
        CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
        exit;
    } else {
        setRaiseMsg('กรอกข้อมูลไม่ครบถ้วน', _TIME_, 'error');
        CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
        exit;
    }
}

if ($ac == 'update') {
    $aRedir = REQ_get('aRedir', 'array', array());
    foreach ($aRedir as $id => $source) {
        if (isset($aRedir[$id]) && $source['source'] && $source['target']) {
            DB_UP(
                'site_seo_redir',
                ['source' => $source['source'], 'target' => $source['target']],
                ['redir_id' => $id]
            );
        }
    }
    setRaiseMsg('อัพเดท Redirect 301 สำเร็จ', _TIME_, 'ok');
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
    exit;
}

if ($ac == 'del') {
    $id = REQ_get('id', 'int', 0);
    if ($id > 0) {
        DB_DEL('site_seo_redir', ['redir_id' => $id]);
        setRaiseMsg('ลบ Redirect 301 สำเร็จ', _TIME_, 'ok');
    } else {
        setRaiseMsg('ไม่พบข้อมูลที่ต้องการลบ', _TIME_, 'error');
    }
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
    exit;
}

if ($ac == 'nooff') {
    $chto = REQ_get('chto', 'str', '');
    $chtoGet = DB_GET('site_configs', ['keywords' => 'onoff_redirect_301']);
    if ($chtoGet == '') {
        $data = array(
            'keywords' => 'onoff_redirect_301',
            'val' => $chto
        );

        DB_ADD('site_configs', $data);
        setRaiseMsg('เพิ่มใช้งาน 301 Redirect สำเร็จ', _TIME_, 'ok');
    } else {
        if ($chto == 'on') {
            DB_UP('site_configs', ['val' => 'on'], ['keywords' => 'onoff_redirect_301']);
            setRaiseMsg('เปิดใช้งาน 301 Redirect สำเร็จ', _TIME_, 'ok');
        } else {
            DB_UP('site_configs', ['val' => 'off'], ['keywords' => 'onoff_redirect_301']);
            setRaiseMsg('ปิดใช้งาน 301 Redirect สำเร็จ', _TIME_, 'ok');
        }
    }
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
    exit;
}

$chto = DB_GET('site_configs', ['keywords' => 'onoff_redirect_301']);
$isOn = (isset($chto['val']) && $chto['val'] == 'on') ? 'on' : 'off';

?>
<div id="page-head">
    <div id="page-title">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-header text-overflow">
                    301 Redirect &nbsp; &nbsp;
                    <?php if ($isOn == 'on') { ?>
                    <a href="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&ac=nooff&chto=off'); ?>"><i class="fa fa-toggle-on" style="font-size: 20px;color: #8BC34A;"></i></a> ON
                    <?php } else { ?>
                    <a href="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&ac=nooff&chto=on'); ?>"><i class="fa fa-toggle-off" style="font-size: 20px;color: #F44336;"></i></a> OFF
                    <?php } ?>
                </h1>
            </div>
        </div>
    </div>
</div>
<div id="page-content">
    <div class="row">
        <div class="col-xs-12">
            <?php displayRaiseMsg(); ?>

            <div class="panel">
                <div class="panel-body">
                    <h2>การทำ Redirect 301 คืออะไร?</h2>
                    <p><strong>Redirect 301</strong> (Permanent Redirect) คือการสั่งให้เบราว์เซอร์และ Search Engine ทราบว่า URL เดิม (ต้นทาง) ได้ถูกย้ายไปยัง URL ใหม่ (ปลายทาง) แบบถาวร โดยใช้ HTTP Status Code <strong>301 Moved Permanently</strong></p>


                    <div class="table-responsive">
                        <?php if ($isOn == 'on') { ?>
                            <form method="post">
                                <input type="hidden" name="ac" value="add">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ต้นทาง</th>
                                            <th>ปลายทาง</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="text" class="form-control" name="source" value=""></td>
                                            <td><input type="text" class="form-control" name="target" value=""></td>
                                            <td><button class="btn btn-success">ADD</button> </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </form>
                        <?php } ?>
                        <h2>ตัวอย่างการใช้งาน</h2>
                        <ul>
                            <li>เปลี่ยน URL จาก <b>/about.html</b> &rarr; <b>/about-us/</b></li>
                            <li>ย้ายเว็บจาก <b>/about.html</b> &rarr; <b>https://newsite.com/about.html</b></li>
                            <li>รวมหลายเพจ เช่น <b>/product-a</b>, <b>/product-b</b> &rarr; <b>/products/</b></li>
                        </ul>
                        <?php
                        if ($isOn == 'on') {
                            $rows = DB_LIST('site_seo_redir', []);
                            if ($rows['num_rows'] > 0) {
                        ?>
                                <form method="post">
                                    <input type="hidden" name="ac" value="update">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th width="50">ลบ</th>
                                                <th>ต้นทาง</th>
                                                <th>ปลายทาง</th>
                                                <th class="text-center">ถูกเรียกใช้</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php

                                            foreach ($rows['data'] as $r): ?>
                                                <tr>
                                                    <td><a href="<?php echo _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&ac=del&id=' . $r['redir_id']); ?>" onclick="return confirm('delete ?');" class="btn btn-danger">ลบ</a></td>
                                                    <td><input type="text" class="form-control" name="aRedir[<?php echo $r['redir_id']; ?>][source]" value="<?php echo htmlspecialchars($r['source']); ?>"></td>
                                                    <td><input type="text" class="form-control" name="aRedir[<?php echo $r['redir_id']; ?>][target]" value="<?php echo htmlspecialchars($r['target']); ?>"></td>
                                                    <td class="text-center"><?php echo isset($r['hit_count']) ? $r['hit_count'] : 0; ?> ครั้ง</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <button type="submit" class="btn btn-primary">Update ทั้งหมด</button>
                                </form>
                        <?php }
                        } ?>
                    </div>

                    <h2>ประโยชน์ของการทำ Redirect 301</h2>
                    <ul>
                        <li><strong>รักษาค่า SEO</strong> – Google จะโอนย้ายค่าอันดับ (PageRank / Backlink / Authority) จากหน้าเก่าไปยังหน้าใหม่</li>
                        <li><strong>ป้องกัน Duplicate Content</strong> – ช่วยหลีกเลี่ยงปัญหาหน้าเว็บซ้ำ เช่น www กับ non-www หรือ http กับ https</li>
                        <li><strong>ปรับปรุงประสบการณ์ผู้ใช้</strong> – ผู้เข้าชมที่กดลิงก์เก่า จะถูกพาไปยัง URL ใหม่โดยอัตโนมัติ ไม่เจอหน้า Error</li>
                        <li><strong>รองรับการเปลี่ยนโครงสร้างเว็บไซต์</strong> – ใช้เมื่อต้องย้ายโดเมน เปลี่ยนชื่อไฟล์ หรือรวมหลายเพจให้เหลือเพียงเพจเดียว</li>
                        <li>ป้องกันผู้ใช้เจอหน้า <strong>404 Page Not Found</strong></li>
                        <li>ทำให้เว็บไซต์ดูเป็นมืออาชีพ</li>
                        <li>ส่งผลดีต่อการจัดอันดับใน <strong>Search Engine</strong></li>
                    </ul>
                    <br>
                    การนำไปใช้งาน ตรวจสอบให้แน่ใจว่า URL ต้นทางและปลายทางถูกต้อง และไม่มีการวนลูปของ Redirect (เช่น A &rarr; B &rarr; A) ซึ่งอาจทำให้เกิดปัญหาในการเข้าถึงเว็บไซต์ได้
                    <pre><?php echo 'SEORedir301();'; ?></pre>
                </div>
            </div>
        </div>
    </div>
</div>