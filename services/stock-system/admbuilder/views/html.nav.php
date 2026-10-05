<nav class="ao_adminbar d-flex justify-content-between align-items-center px-3 ">
    <div class="d-flex justify-content-start align-items-center">
        <div class="me-2" id="AObtnOpenLeft" style="cursor: pointer;"><i class="fas fa-bars"></i>
            <span class="aoweb-fw-semibold"><b>M</b>ENU</span>
        </div>


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
    </div>

    <h3 class="d-none d-md-block" style="font-size: clamp(14px, 2vw, 24px);"><?php echo date('d M Y H:i:s'); ?></h3>
    <div class="d-flex justify-content-end align-items-center">
        
        <a href="javascript:;" id="AObtnOpenRight" class="btn text-dark"><b>S</b>EO</a>
        <div class="me-2 aolanguage">
            <?php foreach ($aConfig['language'] as $lang => $label):
                $active = _LANG_ == $lang ? 'active btn-info' : ''; ?>
                <button
                    class="btn btn-outline-secondary btn-sm <?php echo $active; ?>"
                    hx-get="admbuilder/load.php?ac=language&lang=<?php echo $lang; ?>"
                    hx-trigger="click"
                    hx-target=".aolanguage"
                    hx-swap="outerHTML"
                    data-key="reloadpage">
                    <?php echo strtoupper($lang); ?>
                </button>
            <?php endforeach ?>
        </div>
        <div class="ao-user-online">
            <i class="fa fa-user"></i>
            <span class="ao-badge"><?php $useronline = ADMBUILDER::USERONLINE();echo @$useronline['num_rows']; ?></span>
        </div>
        <a href="/admweb/logout.php" class="btn text-dark" onclick="return confirm('Please confirm logout?');"><i class="fa fa-sign-out-alt me-2"></i></a>
    </div>
</nav>


<!-- เมนูด้านซ้าย -->
<aside class="ao_aside" id="asideLeft">
    <div class="aside-content">
        <button class="btnClose" data-target="asideLeft" style="color: #FFF;">✕</button>
        <h3>MENU</h3>

        <ul class="ao-menu">
            <li class="menu-section">Home</li>

            <li><a href="/admweb/index.php" data-allow-link="true"><i class="fa fa-chart-line me-2"></i> Dashboard</a></li>
            <li><a href="#"><i class="fa fa-images me-2"></i> HOME POPUP</a></li>
            <li><a
                    hx-get="/admbuilder/load.php?ac=config"
                    hx-target=".ao-builder-content"
                    hx-trigger="click"
                    data-size="85%"
                    hx-vals='{"module":"aobuilder","confname":"config"}'
                    style="cursor: pointer;"><i class="fa fa-sliders-h me-2"></i> ตั้งค่าทั่วไป</a></li>
            <li class="menu-section">CMS</li>
            <li class="has-submenu">
                <a href="#"><i class="fa fa-users me-2"></i> จัดการผู้ดูแล</a>
                <ul class="submenu">
                    <li><a href="/admweb/index.php?module=member&mp=admin&otb=AdminManage|0" target="_blank" data-allow-link="true"><i class="fa fa-user me-2"></i> รายชื่อผู้ดูแล</a></li>
                </ul>
            </li>
            <li class="menu-section">User Control</li>
            <li><a href="/admweb/AdmManual.pdf" target="_blank" data-allow-link="true"><i class="fa fa-question-circle me-2"></i> คู่มือการใช้งาน</a></li>
            <li><a href="/admweb/logout.php" data-allow-link="true"><i class="fa fa-sign-out-alt me-2"></i> ออกจากระบบ</a></li>
        </ul>
    </div>
</aside>

<!-- เมนูด้านขวา -->
<aside class="ao_aside_right" id="asideRight">
    <div class="aside-content">
        <button class="btnClose" data-target="asideRight">✕</button>
        <h3>SEO SETUP</h3>

        <div class="ao-tabs">
            <button class="tab-btn active" data-tab="meta">Meta Tags</button>
            <button class="tab-btn" data-tab="schema">Schema</button>
        </div>

        <div class="ao-seo-lang d-flex align-items-center gap-2 my-2">
            <span style="font-size:12px;color:#888;">ภาษา:</span>
            <?php foreach ($aConfig['language'] as $lang => $label): ?>
                <button type="button" class="btn btn-outline-secondary btn-sm ao-seo-lang-btn" data-seolang="<?php echo $lang; ?>" onclick="switchSeoLang('<?php echo $lang; ?>')"><?php echo strtoupper($lang); ?></button>
            <?php endforeach; ?>
        </div>

        <div id="responseMeta"></div>
        <div class="aotab-content active" id="tab-meta">
            <form id="formMeta"
                hx-post="/admbuilder/save.php"
                hx-encoding="multipart/form-data"
                hx-target="#responseMeta"
                hx-swap="innerHTML"
                hx-trigger="submit">

                <div id="RightMetaBox"></div>
            </form>
        </div>

        <div class="aotab-content" id="tab-schema">
            <form id="formSchema"
                hx-post="/admbuilder/save.php"
                hx-target="#responseMeta"
                hx-swap="innerHTML"
                hx-trigger="submit">
                <input type="hidden" name="keysname" value="<?php echo SEO::get('key'); ?>" />
                <input type="hidden" name="ac" value="saveschema" />
                <input type="hidden" name="savelang" value="<?php echo _LANG_; ?>" />

                <div id="RightSchemaBox"></div>
                <?php //echo SEO::schema_template(); ?>

                
            </form>
        </div>

    </div>
</aside>