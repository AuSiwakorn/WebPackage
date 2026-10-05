<?php
/**
 * FILE: admbuilder/editor.php
 * ROLE: หน้า editor แบบ standalone สำหรับฝังเป็น iframe (style isolation) — render ฟอร์มแก้ไข textbox/articles เป็นเอกสารเดี่ยวที่คุม CSS เอง
 * DEPENDS: ../admweb/mainApi.php, ../admweb/api/oApi.php, config.php, autoload.php, views/inner.model.php, views/inner.articles.edit.php, views/inner.articles.php, include/editor.css
 * TABLES: webbuilder, site_articles, site_articles_content
 * TODO:
 *   - [x] bootstrap + auth guard (ช่วง 1.1)
 *   - [x] รับพารามิเตอร์ + โหลดค่าเดิมจาก DB (ช่วง 1.2)
 *   - [x] render ฟอร์มตามชนิด editor: textbox/articlesEdit (ช่วง 1.3)
 *   - [x] head/body + โหลด asset CDN + init summernote (ช่วง 1.4)
 *   - [x] รองรับ ac=articles (list) + โหลด HTMX สำหรับ navigation ใน iframe (ช่วง 3.5)
 *   - [ ] submit/อัปโหลดรูปภายใน iframe (ช่วง 4)
 *   - [ ] postMessage กลับ parent: saved/close/resize (ช่วง 4-5)
 *   - [ ] drag-sort ตาราง articles ภายใน iframe (ยังพึ่ง script.js ของ parent — ช่วง 5/6)
 */

include dirname(dirname(__FILE__)) . '/admweb/mainApi.php';
include dirname(dirname(__FILE__)) . '/admweb/api/oApi.php';
include dirname(__FILE__) . '/config.php';
include dirname(__FILE__) . '/autoload.php';

define("_LANG_", ($lang) ? $lang : DEFAULT_LANGEUAGE);

if (!ADMBUILDER::isLoginAdmin()) {
    http_response_code(403);
    exit('Permission denied');
}

$ac       = REQ_get('ac', 'request', 'str', '');
$keysname = REQ_get('keysname', 'request', 'str', '');
$clang    = REQ_get('lang', 'get', 'str', 'th');

$editortype = SiteConfig_get('editortype', 'ckeditor5');
if (!in_array($editortype, ['summernote', 'ckeditor', 'ckeditor5'], true)) {
    $editortype = 'ckeditor5';
}

// ===== เตรียมตัวแปรตามชนิดฟอร์ม (มิเรอร์ตรรกะจาก load.php) =====
$viewFile = '';

if ($ac == 'textbox') {
    // ---- โหลดค่าเดิมของทุกภาษา (คนละแถวใน webbuilder: <keysname><langkey>) ----
    $aLangs = ADMBUILDER::AOLANGS();
    $aBox   = ADMBUILDER::AOBOXDATA($keysname);

    // ---- ถอด data (base64 -> json) เป็นรายการฟิลด์ที่ต้องแสดง ----
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
        $isImgAlt  = $vals['isImgAlt'] ?? '';
        $isExtra1  = $vals['isExtra1'] ?? '';
        $isExtra2  = $vals['isExtra2'] ?? '';
    }

    $viewFile = __DIR__ . '/views/inner.model.php';
} elseif ($ac == 'articlesEdit') {
    $id      = REQ_get('id', 'request', 'int', '');
    $size    = REQ_get('size', 'request', 'str', '');
    $inner   = REQ_get('inner', 'request', 'str', '');
    $options = REQ_get('data', 'request', 'str', '');
    $data    = REQ_get('data', 'request', 'str', '');

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

    $viewFile = __DIR__ . '/views/inner.articles.edit.php';
} elseif ($ac == 'articles') {
    // หน้า list บทความ (initial load ของ iframe) — การ navigate list<->form ภายใน iframe
    // ใช้ HTMX ยิงไป load.php (fragment) swap เข้า .ao-builder-content ของ iframe เอง
    $size    = REQ_get('size', 'request', 'str', '');
    $inner   = REQ_get('inner', 'request', 'str', '');
    $id      = REQ_get('id', 'request', 'int', '');
    $gid     = REQ_get('gid', 'request', 'int', 0);
    $options = REQ_get('data', 'request', 'str', '');

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
        $viewFile = __DIR__ . '/views/inner.articles.edit.php';
    } else {
        // เปิด isGroup → หน้าแรกของ modal คือลิสต์หมวดหมู่ (กด "ดูเนื้อหา" ค่อยเข้าไปในหมวด)
        $viewFile = (($vals['isGroup'] ?? '') != '')
            ? __DIR__ . '/views/inner.articles.group.php'
            : __DIR__ . '/views/inner.articles.php';
    }
} elseif ($ac == 'subpost') {
    // list subpost — parent (group_id/articles_id) มากับ vals ตอนเปิดจาก AOSET
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
    $viewFile = __DIR__ . '/views/inner.articles.subpost.php';
} else {
    http_response_code(400);
    exit('action is not viewable');
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AO Builder — Editor</title>
    <!-- default swap = none: ฟอร์ม save (ไม่มี hx-swap) จะไม่ swap ทับตัวเอง; ปุ่ม nav ที่ใส่ hx-swap="innerHTML" ยังทำงานปกติ -->
    <meta name="htmx-config" content='{"defaultSwapStyle":"none"}'>

    <!-- โหลดเฉพาะ asset ของหน้า editor เท่านั้น (isolation) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css">
    <?php if ($editortype === 'summernote') { ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css">
    <?php } elseif ($editortype === 'ckeditor5') { ?>
        <link rel="stylesheet" href="/admweb/include/editor/ckeditor5/ckeditor5.css">
        <script type="importmap">
            { "imports": { "ckeditor5": "/admweb/include/editor/ckeditor5/ckeditor5.js", "ckeditor5/": "/admweb/include/editor/ckeditor5/" } }
        </script>
        <script>
            window.aoCK5Feature = {
                codeBlock: <?php echo (@$aConfig['codeBlock'] === 'true' || @$aConfig['codeBlock'] === true) ? 'true' : 'false'; ?>,
                insertHTML: <?php echo (@$aConfig['insertHTML'] === 'true' || @$aConfig['insertHTML'] === true) ? 'true' : 'false'; ?>
            };
        </script>
    <?php } ?>
    <script>window.AO_EDITOR_TYPE = '<?php echo $editortype; ?>';</script>
    <!-- ช่วง 6: เลิกโหลด style.css ของ builder ใน iframe แล้ว (isolation เต็มรูปแบบ) — ใช้ editor.css ที่ scope แล้วแทน -->
    <link rel="stylesheet" href="/admbuilder/include/editor.css?v=<?php echo time(); ?>">
</head>

<body class="ao-editor-body">
    <div class="ao-builder-content">
        <?php include $viewFile; ?>
    </div>

    <!-- jQuery -> Bootstrap bundle -> Summernote -> SweetAlert2 (ลำดับสำคัญ) -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($editortype === 'summernote') { ?>
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
    <?php } elseif ($editortype === 'ckeditor') { ?>
        <script src="/admweb/include/editor/ckeditor/ckeditor.js"></script>
    <?php } elseif ($editortype === 'ckeditor5') { ?>
        <script src="/admbuilder/include/ao-media.js?v=<?php echo @filemtime(dirname(__FILE__) . '/include/ao-media.js'); ?>"></script>
        <script type="module" src="/admbuilder/include/ao-ck5-boot.js?v=<?php echo @filemtime(dirname(__FILE__) . '/include/ao-ck5-boot.js'); ?>"></script>
    <?php } ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- HTMX: ใช้กับ navigation ภายใน iframe (ac=articles: list<->edit form) ยิงไป load.php (fragment) swap เข้า .ao-builder-content -->
    <script src="https://unpkg.com/htmx.org@1.9.12"></script>

    <script>
        // แจ้ง parent ให้ปิด modal (ปุ่มยกเลิก/ปิดในฟอร์ม)
        function closeAowebModal() {
            aoNotifyParent('close');
        }

        // ===== ช่วง 4-5: สื่อสารกับ parent ผ่าน postMessage =====
        function aoNotifyParent(action, extra) {
            if (window.parent) {
                window.parent.postMessage(
                    Object.assign({ type: 'aobuilder', action: action }, extra || {}),
                    location.origin
                );
            }
        }

        document.body.addEventListener('htmx:confirm', function (evt) {
            if (!evt.detail.question) return;
            evt.preventDefault();

            const el     = evt.detail.elt || evt.target;
            const url    = el && (el.getAttribute('hx-post') || el.getAttribute('hx-get'));
            const method = (el && el.getAttribute('hx-post')) ? 'POST' : 'GET';
            const isDelete = /[?&]ac=(delete|groupDelete|subpostDelete)\b/.test(url || '');

            Swal.fire({
                title: evt.detail.question,
                icon: 'warning',
                reverseButtons: true,
                showCancelButton: true,
                cancelButtonText: 'ยกเลิก',
                confirmButtonText: 'ยืนยัน',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
            }).then(function (result) {
                if (!result.isConfirmed) return;
                if (!url) { Swal.fire({ icon: 'error', title: 'ลิงก์ไม่ถูกต้อง' }); return; }

                aoNotifyParent('busy');   // กำลังทำงาน → ล็อกไม่ให้ปิด modal
                Swal.fire({
                    title: isDelete ? 'กำลังลบข้อมูล...' : 'กำลังดำเนินการ...',
                    allowOutsideClick: false, allowEscapeKey: false,
                    didOpen: function () { Swal.showLoading(); }
                });

                fetch(url, {
                    method: method,
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function (res) {
                    return res.text().then(function (body) { return { ok: res.ok, status: res.status, body: body }; });
                }).then(function (r) {
                    if (!r.ok) {
                        aoNotifyParent('idle');
                        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', html: (r.body || '').trim() || ('ทำรายการไม่สำเร็จ (รหัส ' + r.status + ')') });
                        return;
                    }
                    aoNotifyParent('dirty');   // สำเร็จ → ปลดล็อก + ให้พรีวิว reload ตอนปิด modal
                    Swal.fire({ icon: 'success', title: isDelete ? 'ลบข้อมูลเรียบร้อย' : 'ดำเนินการเรียบร้อย', timer: 1000, showConfirmButton: false })
                        .then(function () { location.reload(); });
                }).catch(function (e) {
                    aoNotifyParent('idle');
                    Swal.fire({ icon: 'error', title: 'เชื่อมต่อไม่สำเร็จ', text: String(e) });
                });
            });
        });

        document.body.addEventListener('htmx:beforeRequest', function (evt) {
            const cfg = evt.detail.requestConfig || {};
            if ((cfg.verb || '').toUpperCase() !== 'POST') return;
            aoNotifyParent('busy');   // กำลังบันทึก → ล็อกไม่ให้ปิด modal
            Swal.fire({
                title: 'กำลังบันทึก...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function () { Swal.showLoading(); }
            });
        });

        document.body.addEventListener('htmx:afterRequest', function (evt) {
            const cfg = evt.detail.requestConfig || {};
            if ((cfg.verb || '').toUpperCase() !== 'POST') return;
            const xhr = evt.detail.xhr;
            const ok = evt.detail.successful || (xhr && xhr.status >= 200 && xhr.status < 300);
            if (!ok) return;

            // subpost: เซฟแล้วเด้งกลับหน้าแก้กรุ๊ป (Related Ingredients) — modal ไม่ปิด ทำต่อได้เลย
            const form = evt.detail.elt;
            const back = form && form.getAttribute && form.getAttribute('data-ao-return');
            if (back) {
                aoNotifyParent('dirty');   // subpost มีผลกับหน้าเว็บ → พรีวิว reload ตอนปิด modal
                Swal.fire({ icon: 'success', title: 'บันทึกเรียบร้อย', timer: 800, showConfirmButton: false })
                    .then(function () {
                        htmx.ajax('GET', back, {
                            target: '.ao-builder-content', swap: 'innerHTML',
                            values: {
                                data: form.getAttribute('data-ao-return-data') || '',
                                size: form.getAttribute('data-ao-return-size') || ''
                            }
                        });
                    });
                return;
            }

            Swal.fire({ icon: 'success', title: 'บันทึกเรียบร้อย', timer: 900, showConfirmButton: false })
                .then(function () { aoNotifyParent('saved'); });
        });

        document.body.addEventListener('htmx:responseError', function (evt) {
            aoNotifyParent('idle');   // พลาด → ปลดล็อก ให้ปิด/แก้ต่อได้
            const xhr = evt.detail && evt.detail.xhr;
            const msg = ((xhr && xhr.responseText) || '').trim();
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                html: msg || 'บันทึกหรือโหลดข้อมูลไม่สำเร็จ'
            });
        });

        document.body.addEventListener('htmx:sendError', function () {
            aoNotifyParent('idle');   // ยิงไม่ถึงเซิร์ฟเวอร์ → ปลดล็อก
            Swal.fire({ icon: 'error', title: 'เชื่อมต่อไม่สำเร็จ', text: 'ตรวจสอบอินเทอร์เน็ตแล้วลองใหม่อีกครั้ง' });
        });

        // ช่วง 5 (ฝั่ง iframe): ส่งความสูงเนื้อหาให้ parent ปรับ iframe (auto-resize ไม่มี scroll ซ้อน)
        function aoSendResize() {
            aoNotifyParent('resize', { height: document.body.scrollHeight });
        }
        window.addEventListener('load', aoSendResize);
        document.body.addEventListener('htmx:afterSettle', aoSendResize);

        // ===== จัดลำดับ articles/หมวดหมู่: ลากด้วย handle หรือพิมพ์เลขในช่อง แล้วบันทึกลำดับ =====
        // ตารางกำหนด endpoint เองได้ผ่าน data-sort-url (เช่นหมวดหมู่ใช้ ac=sortgroups)
        function aoSaveOrder(tbody) {
            var table = tbody.closest('table');
            var sortUrl = (table && table.dataset.sortUrl) || '/admbuilder/save.php?ac=sortarticles';
            var rows = Array.prototype.slice.call(tbody.rows);
            var fd = new FormData();
            rows.forEach(function (row, i) {
                var pos = i + 1;
                var input = row.querySelector('.ao-sort-input');
                if (input) input.value = pos;                 // ช่องกรอกให้ตรงกับตำแหน่งจริง
                if (row.dataset.id) fd.append('aSort[' + row.dataset.id + ']', pos);
            });
            fetch(sortUrl, { method: 'POST', credentials: 'same-origin', body: fd })
                .then(function (res) { return res.text().then(function (t) { return { ok: res.ok, t: t }; }); })
                .then(function (r) {
                    if (!r.ok) { Swal.fire({ icon: 'error', title: 'จัดลำดับไม่สำเร็จ', html: (r.t || '').trim() }); return; }
                    aoNotifyParent('dirty');   // จัดลำดับแล้ว → ให้พรีวิว reload ตอนปิด modal
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'อัปเดตลำดับแล้ว', timer: 1200, showConfirmButton: false });
                })
                .catch(function (e) { Swal.fire({ icon: 'error', title: 'เชื่อมต่อไม่สำเร็จ', text: String(e) }); });
        }

        // เรียงแถวใหม่ตามเลขในช่องกรอก (stable) แล้วบันทึก
        function aoReorderByInputs(tbody) {
            var rows = Array.prototype.slice.call(tbody.rows);
            rows.map(function (row, i) { return { row: row, i: i, v: parseFloat((row.querySelector('.ao-sort-input') || {}).value) }; })
                .sort(function (a, b) {
                    var av = isNaN(a.v) ? Infinity : a.v, bv = isNaN(b.v) ? Infinity : b.v;
                    return av !== bv ? av - bv : a.i - b.i;      // ค่าเท่ากันคงลำดับเดิม
                })
                .forEach(function (o) { tbody.appendChild(o.row); });
            aoSaveOrder(tbody);
        }

        function aoInitSortableTable() {
            var table = document.getElementById('ao-table-sortable');
            if (!table) return;
            var tbody = table.querySelector('tbody');
            if (!tbody || tbody.dataset.aoSortable === '1') return;   // กัน bind ซ้ำ
            tbody.dataset.aoSortable = '1';

            var draggingRow = null;

            tbody.querySelectorAll('.ao-drag-handle').forEach(function (handle) {
                handle.addEventListener('mousedown', function (e) { var tr = e.target.closest('tr'); if (tr) tr.setAttribute('draggable', 'true'); });
                handle.addEventListener('mouseup', function (e) { var tr = e.target.closest('tr'); if (tr) tr.removeAttribute('draggable'); });
            });

            tbody.addEventListener('dragstart', function (e) {
                draggingRow = e.target.closest('tr');
                if (!draggingRow) return;
                e.dataTransfer.effectAllowed = 'move';
                draggingRow.classList.add('ao-dragging');
            });
            tbody.addEventListener('dragend', function () {
                if (draggingRow) draggingRow.classList.remove('ao-dragging');
                draggingRow = null;
            });
            tbody.addEventListener('dragover', function (e) {
                e.preventDefault();
                var targetRow = e.target.closest('tr');
                if (!targetRow || targetRow === draggingRow) return;
                var rect = targetRow.getBoundingClientRect();
                var next = (e.clientY - rect.top) / rect.height > 0.5;
                tbody.insertBefore(draggingRow, next ? targetRow.nextSibling : targetRow);
            });
            tbody.addEventListener('drop', function () { aoSaveOrder(tbody); });

            // พิมพ์เลขในช่องแล้ว blur/enter → เรียงใหม่ + บันทึก
            tbody.addEventListener('change', function (e) {
                if (!e.target.classList.contains('ao-sort-input')) return;
                aoReorderByInputs(tbody);
            });
        }

        window.addEventListener('load', aoInitSortableTable);
        document.body.addEventListener('htmx:afterSwap', function (evt) {
            if (evt.target && evt.target.querySelector && evt.target.querySelector('#ao-table-sortable')) aoInitSortableTable();
        });

    </script>
    <script src="/admbuilder/include/ao-editor.js"></script>
    <script>
        $(function () {
            $(document).on('click', '.click-edit-minibox, .click-edit-content', function (e) {
                e.stopPropagation();
                window.AOEditor.attach(this, $(this).data('target'));
            });
        });
    </script>
</body>

</html>
