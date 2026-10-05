window.aoMedia = (function () {
    var overlay = null;
    var onPickCb = null;
    var escHandler = null;

    function injectStyle() {
        if (document.getElementById('ao-media-style')) return;
        var css =
            '@keyframes aoMediaFade{from{opacity:0}to{opacity:1}}' +
            '@keyframes aoMediaPop{from{opacity:0;transform:translateY(14px) scale(.98)}to{opacity:1;transform:none}}' +
            '@keyframes aoMediaSpin{to{transform:rotate(360deg)}}' +
            '.ao-media-overlay{position:fixed;inset:0;background:rgba(17,12,28,.55);z-index:100000;display:flex;align-items:center;justify-content:center;animation:aoMediaFade .15s ease;}' +
            '.ao-media-box{background:#fff;border-radius:14px;width:min(940px,94vw);height:min(620px,88vh);display:flex;flex-direction:column;overflow:hidden;box-shadow:0 24px 64px rgba(0,0,0,.35);animation:aoMediaPop .18s ease;}' +
            '.ao-media-head{display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid #eee;}' +
            '.ao-media-title{font-weight:700;font-size:16px;color:#222;}' +
            '.ao-media-count{font-size:12.5px;color:#999;}' +
            '.ao-media-head .ao-media-sp{flex:1;}' +
            '.ao-media-btn{border:0;border-radius:8px;padding:9px 16px;cursor:pointer;font-weight:600;font-size:14px;transition:background .15s;}' +
            '.ao-media-upload{background:#44266a;color:#fff;}' +
            '.ao-media-upload:hover{background:#6b3fa0;}' +
            '.ao-media-upload:disabled{background:#9b8ab5;cursor:wait;}' +
            '.ao-media-x{width:36px;height:36px;border:0;border-radius:8px;background:transparent;color:#888;font-size:16px;cursor:pointer;}' +
            '.ao-media-x:hover{background:#f2f0f6;color:#44266a;}' +
            '.ao-media-body{flex:1;overflow-y:auto;padding:18px;position:relative;}' +
            '.ao-media-body.dragover{outline:2px dashed #6b3fa0;outline-offset:-10px;background:#faf8fd;}' +
            '.ao-media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(132px,1fr));gap:12px;}' +
            '.ao-media-item{position:relative;border:1px solid #e6e6e6;border-radius:10px;overflow:hidden;cursor:pointer;aspect-ratio:1;background:#f7f7f7;transition:border-color .12s,box-shadow .12s;}' +
            '.ao-media-item img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .15s;}' +
            '.ao-media-item:hover{border-color:#6b3fa0;box-shadow:0 4px 14px rgba(68,38,106,.18);}' +
            '.ao-media-item:hover img{transform:scale(1.05);}' +
            '.ao-media-act{position:absolute;inset:0;display:flex;align-items:flex-end;justify-content:center;background:linear-gradient(transparent 55%,rgba(20,10,40,.62));opacity:0;transition:opacity .15s;pointer-events:none;}' +
            '.ao-media-item:hover .ao-media-act{opacity:1;}' +
            '.ao-media-act span{color:#fff;font-size:12.5px;font-weight:600;padding:8px;}' +
            '.ao-media-del{position:absolute;top:6px;right:6px;width:28px;height:28px;border:0;border-radius:7px;background:rgba(20,10,40,.55);color:#fff;cursor:pointer;font-size:12px;opacity:0;transition:opacity .15s,background .15s;}' +
            '.ao-media-item:hover .ao-media-del{opacity:1;}' +
            '.ao-media-del:hover{background:#d9534f;}' +
            '.ao-media-new{box-shadow:0 0 0 3px #6b3fa0 !important;}' +
            '.ao-media-full{grid-column:1/-1;text-align:center;color:#999;padding:56px 20px;}' +
            '.ao-media-full i{font-size:40px;color:#d9d3e6;display:block;margin-bottom:14px;}' +
            '.ao-media-spinner{width:34px;height:34px;border:3px solid #e8e3f2;border-top-color:#6b3fa0;border-radius:50%;margin:0 auto 12px;animation:aoMediaSpin .7s linear infinite;}' +
            '.ao-media-hint{font-size:12.5px;color:#bbb;margin-top:6px;}';
        var s = document.createElement('style');
        s.id = 'ao-media-style';
        s.textContent = css;
        document.head.appendChild(s);
    }

    function close() {
        if (overlay) { overlay.remove(); overlay = null; }
        if (escHandler) { document.removeEventListener('keydown', escHandler); escHandler = null; }
        onPickCb = null;
    }

    function setCount(n) {
        var el = overlay && overlay.querySelector('.ao-media-count');
        if (el) el.textContent = n > 0 ? n + ' รูป' : '';
    }

    function showLoading() {
        var grid = overlay.querySelector('.ao-media-grid');
        grid.innerHTML = '<div class="ao-media-full"><div class="ao-media-spinner"></div>กำลังโหลดรูป...</div>';
    }

    function confirmDelete(f, done) {
        if (window.Swal) {
            Swal.fire({
                title: 'ลบรูปนี้?',
                text: f.name,
                icon: 'warning',
                reverseButtons: true,
                showCancelButton: true,
                cancelButtonText: 'ยกเลิก',
                confirmButtonText: 'ลบ',
                confirmButtonColor: '#d33'
            }).then(function (r) { if (r.isConfirmed) done(); });
        } else if (confirm('ลบรูปนี้?')) {
            done();
        }
    }

    function render(files, highlightUrl) {
        var grid = overlay.querySelector('.ao-media-grid');
        setCount(files.length);
        if (!files.length) {
            grid.innerHTML =
                '<div class="ao-media-full">' +
                '<i class="far fa-images"></i>' +
                'ยังไม่มีรูปในคลัง' +
                '<div class="ao-media-hint">ลากรูปมาวางที่นี่ หรือกดปุ่ม "อัปโหลดรูป"</div>' +
                '</div>';
            return;
        }
        grid.innerHTML = '';
        files.forEach(function (f) {
            var item = document.createElement('div');
            item.className = 'ao-media-item';
            if (highlightUrl && f.url === highlightUrl) item.classList.add('ao-media-new');
            item.title = f.name;
            item.innerHTML =
                '<img src="' + f.url + '" alt="" loading="lazy">' +
                '<div class="ao-media-act"><span><i class="fas fa-plus"></i> คลิกเพื่อแทรก</span></div>' +
                '<button class="ao-media-del" title="ลบรูป"><i class="fas fa-trash-alt"></i></button>';
            item.addEventListener('click', function () {
                if (onPickCb) onPickCb(f.url);
                close();
            });
            item.querySelector('.ao-media-del').addEventListener('click', function (e) {
                e.stopPropagation();
                confirmDelete(f, function () {
                    var fd = new FormData();
                    fd.append('name', f.name);
                    fetch('/admbuilder/media.php?ac=delete', { method: 'POST', credentials: 'same-origin', body: fd })
                        .then(function (r) { return r.json(); })
                        .then(function () { load(); });
                });
            });
            grid.appendChild(item);
        });
        if (highlightUrl) {
            setTimeout(function () {
                var el = grid.querySelector('.ao-media-new');
                if (el) el.classList.remove('ao-media-new');
            }, 1800);
        }
    }

    function load(highlightUrl) {
        if (!overlay) return;
        if (!highlightUrl) showLoading();
        fetch('/admbuilder/media.php?ac=list', { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) { if (overlay) render(d.files || [], highlightUrl); })
            .catch(function () { if (overlay) render([]); });
    }

    function setBusy(busy, doneCount, total) {
        var btn = overlay && overlay.querySelector('.ao-media-upload');
        if (!btn) return;
        btn.disabled = busy;
        btn.innerHTML = busy
            ? '<i class="fas fa-circle-notch fa-spin"></i> กำลังอัปโหลด' + (total > 1 ? ' (' + doneCount + '/' + total + ')' : '') + '...'
            : '<i class="fas fa-cloud-upload-alt"></i> อัปโหลดรูป';
    }

    function uploadFiles(fileList) {
        var files = Array.prototype.filter.call(fileList, function (f) { return /^image\//.test(f.type); });
        if (!files.length || !overlay) return;

        var done = 0;
        var lastUrl = '';
        var errors = [];
        setBusy(true, 0, files.length);

        var jobs = files.map(function (f) {
            var fd = new FormData();
            fd.append('upload', f);
            return fetch('/admbuilder/media.php?ac=upload', { method: 'POST', credentials: 'same-origin', body: fd })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (d.error) { errors.push(f.name + ': ' + (d.error.message || 'ไม่สำเร็จ')); }
                    else if (d.url) { lastUrl = d.url; }
                })
                .catch(function () { errors.push(f.name + ': เชื่อมต่อไม่สำเร็จ'); })
                .then(function () { done++; setBusy(true, done, files.length); });
        });

        Promise.all(jobs).then(function () {
            if (!overlay) return;
            setBusy(false);
            if (errors.length) {
                if (window.Swal) Swal.fire({ icon: 'error', title: 'บางไฟล์อัปโหลดไม่สำเร็จ', html: errors.join('<br>') });
                else alert(errors.join('\n'));
            }
            load(lastUrl);
        });
    }

    function open(onPick) {
        if (overlay) close();
        injectStyle();
        onPickCb = onPick || null;

        overlay = document.createElement('div');
        overlay.className = 'ao-media-overlay';
        overlay.innerHTML =
            '<div class="ao-media-box">' +
              '<div class="ao-media-head">' +
                '<span class="ao-media-title"><i class="far fa-images"></i> คลังรูป</span>' +
                '<span class="ao-media-count"></span>' +
                '<span class="ao-media-sp"></span>' +
                '<button class="ao-media-btn ao-media-upload"><i class="fas fa-cloud-upload-alt"></i> อัปโหลดรูป</button>' +
                '<button class="ao-media-x" title="ปิด"><i class="fas fa-times"></i></button>' +
              '</div>' +
              '<div class="ao-media-body"><div class="ao-media-grid"></div></div>' +
              '<input type="file" accept="image/*" multiple style="display:none">' +
            '</div>';
        document.body.appendChild(overlay);

        var body = overlay.querySelector('.ao-media-body');
        var fileInput = overlay.querySelector('input[type=file]');

        overlay.querySelector('.ao-media-upload').addEventListener('click', function () { fileInput.click(); });
        fileInput.addEventListener('change', function () {
            if (fileInput.files.length) uploadFiles(fileInput.files);
            fileInput.value = '';
        });
        overlay.querySelector('.ao-media-x').addEventListener('click', close);
        overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });

        ['dragenter', 'dragover'].forEach(function (ev) {
            body.addEventListener(ev, function (e) { e.preventDefault(); body.classList.add('dragover'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            body.addEventListener(ev, function (e) { e.preventDefault(); body.classList.remove('dragover'); });
        });
        body.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files.length) uploadFiles(e.dataTransfer.files);
        });

        escHandler = function (e) { if (e.key === 'Escape') close(); };
        document.addEventListener('keydown', escHandler);

        load();
    }

    return { open: open };
})();
