document.addEventListener("DOMContentLoaded", function () {

    /* ################## beforeRequest ################### */
    document.body.addEventListener("htmx:beforeRequest", function (evt) {
        console.log('beforeRequest : Start');
        const aomodal = evt.detail.target.closest('.aoweb-modal-overlay');
        if (!aomodal) {

            const form = evt.target.closest('form');
            if (form) {
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerText = '⏳ กำลังบันทึก...';
                }
            }

            console.log('ไม่ใช่การโหลดจาก aoweb-modal-overlay, ข้ามการแสดงผล modal โหลด');
            return;
        }

        if (evt.target.closest(".aoweb-modal-overlay form")) {
            Swal.fire({
                title: "กำลังบันทึกข้อมูล...",
                allowEscapeKey: false,
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => {
                    closeAowebModal();
                    Swal.showLoading();
                }
            });
            console.log('beforeRequest : End');
            return;
        }

        if (evt.target.matches("[data-skip-before='true'], [data-skip-before='true'] *")) {
            console.log('beforeRequest : Skipping+End');
            return;
        }

        const controller = new AbortController();
        Swal.fire({
            title: 'กำลังโหลดข้อมูล...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            showCancelButton: true,
            cancelButtonText: 'ยกเลิก',
            didOpen: () => {
                Swal.getPopup().style.height = '350px';
                Swal.showLoading();
                const actions = Swal.getActions();
                if (actions) actions.style.flexDirection = 'column';
                const cancelButton = Swal.getCancelButton();
                if (cancelButton) {
                    cancelButton.style.marginTop = '40px';
                    cancelButton.style.width = '80px';
                }
            }
        }).then(result => {
            if (result.dismiss === Swal.DismissReason.cancel) {
                controller.abort();
                Swal.fire({
                    icon: 'info',
                    title: 'ยกเลิกการโหลดแล้ว',
                    timer: 1200,
                    showConfirmButton: false
                });
                closeAowebModal();
            }
        });
        console.log('beforeRequest : End');
    });

    /* ################## afterSwap ################### */
    document.body.addEventListener('htmx:afterSwap', evt => {
        console.log('afterSwap : Start');

        const modal = evt.detail.target.closest('.aoweb-modal-overlay');
        if (!modal) return;

        if (evt.target.matches("[data-skip-before='true'], [data-skip-before='true'] *")) {
            console.log('afterSwap : Skipping');
            return;
        }

        Swal.close();
        const triggerEl = evt.detail.requestConfig.elt;
        const size = triggerEl?.getAttribute('data-size') || '';
        setModalWidth(size);
        document.getElementById('modalOverlay').style.display = 'flex';
        aoPlaceModal();
        console.log('afterSwap : End');
    });

    /* ################## afterRequest ################### */
    document.body.addEventListener("htmx:afterRequest", function (evt) {
        const verb = evt.detail.requestConfig?.verb?.toUpperCase() || '';
        const modalForm = evt.target.closest(".aoweb-modal-overlay form");
        const trigger = evt.detail.requestConfig?.elt?.closest('[data-key]');
        console.log('afterRequest : Start ' + verb);

        let keymode = '';
        if (trigger) {
            keymode = trigger.dataset.key || '';
            console.log("Triggered by key:", keymode);
        }

        if (keymode === 'reloadpage') {
            const iframe = document.querySelector('.ao-preview__frame');
            if (iframe && iframe.contentWindow) {
                console.log("afterOnLoad : reloadpage+End");
                iframe.contentWindow.location.reload();
            }
        }

        if (verb === 'GET') {
            console.log('Skip success alert: GET request detected');
            return;
        }

        if (!modalForm) {
            const form = evt.target.closest('form');
            if (form) {
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'บันทึก';
                }
            } else {
                console.log('Skip success alert');
                return;
            }

            if (evt.detail.xhr.status === 200) {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกเรียบร้อย 02',
                    text: 'ข้อมูลถูกบันทึกสำเร็จแล้ว!',
                    timer: 3000,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'ao-swal-top-layer'
                    }
                });
            }
            console.log('afterRequest : End Missing modal');
            return;
        }

        const reloadFlag = modalForm.getAttribute('data-none-reload');
        if (reloadFlag === 'false') {
            closeAowebModal()
            console.log('afterRequest : End with no reload');
            return;
        }

        Swal.fire({
            icon: "success",
            title: "บันทึกเรียบร้อย 01",
            showConfirmButton: false,
            timer: 1200
        }).then(() => {
            if (reloadFlag !== 'false' && reloadFlag != null) {
                alert(reloadFlag);
                console.log('reloadFlag: ' + reloadFlag);
                console.log('afterRequest Skip reload: true');
            } else {
                location.reload();
            }
        });
        console.log('afterRequest : End');
    });

    /* ################## responseError ################### */
    document.body.addEventListener('htmx:responseError', evt => {
        console.log('responseError : Start');
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถโหลดข้อมูลหรือบันทึกข้อมูลได้',
        });
        console.error('❌ โหลดล้มเหลว:', evt.detail.xhr.status, evt.detail.xhr.responseText);
        console.log('responseError : End');
    });

    /* ################## afterOnLoad ################### */
    document.body.addEventListener("htmx:afterOnLoad", e => {
        console.log('afterOnLoad : Start');
        if (e.detail.xhr.getResponseHeader("HX-Trigger") !== "reloadPage") return;

        const reloadFlag = modalForm.getAttribute('data-none-reload');
        closeAowebModal();
        Swal.fire({
            icon: "success",
            title: "บันทึกเรียบร้อย 03",
            showConfirmButton: !1,
            timer: 1200
        }).then(() => {
            if (reloadFlag !== 'false') {
                console.log('afterOnLoad Skip reload: true');
            } else {
                location.reload();
            }
        });
        console.log('afterOnLoad : End');
    });

    /* ################## confirm ################### */
    document.body.addEventListener('htmx:confirm', function (event) {
        const question = event.detail.question;
        if (!question) return;

        if (!event.detail.question) return;


        console.log('confirm : Start');
        event.preventDefault();
        const el = event.target;
        const url = el.getAttribute('hx-post') || el.getAttribute('hx-get');

        Swal.fire({
            title: event.detail.question || 'ยืนยันการลบข้อมูลนี้',
            icon: 'warning',
            reverseButtons: true,
            showCancelButton: true,
            cancelButtonText: 'ยกเลิก',
            confirmButtonText: 'ยืนยัน',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
        }).then((result) => {
            if (result.isConfirmed && url) {
                Swal.fire({
                    title: 'กำลังลบข้อมูล...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                htmx.ajax('POST', url)
                    .then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'ลบข้อมูลเรียบร้อย',
                            timer: 1200,
                            showConfirmButton: false
                        }).then(() => {
                            closeAowebModal();
                            location.reload();
                        });
                    });
            }
        });
        console.log('confirm : End');
    });

    /* ################## เปิด editor ใน iframe (ช่วง 3) ################### */
    // คลิก element ที่ AOSET ติด data-ao="aobuilder" → อ่าน data-* → set src ของ iframe + เปิด modal
    document.body.addEventListener('click', function (e) {
        const el = e.target.closest('[data-ao="aobuilder"]');
        if (!el) return;
        e.preventDefault();
        aoOpenEditor(el);
    });

    $(document).on('click', '.click-edit-content', function (e) {
        e.stopPropagation();
        const $this = $(this);
        const target = $this.data('target');
        if (!$this.hasClass('is-editing')) {
            $this.addClass('is-editing');
            $this.summernote({
                placeholder: 'เขียนเนื้อหาที่นี่...',
                height: 200,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['codeview']]
                ],
                callbacks: {
                    onBlur: function () {
                        const markup = $this.summernote('code');
                        $this.html(markup);
                        $(target).val(markup);
                    }
                }
            });
        }
    });

    $(document).on('click', '.click-edit-minibox', function (e) {
        e.stopPropagation();
        const $this = $(this);
        const target = $this.data('target');
        if (!$this.hasClass('is-editing')) {
            $this.addClass('is-editing');
            $this.summernote({
                placeholder: 'เขียนเนื้อหาที่นี่...',
                height: 190,
                toolbar: [
                    ['font', ['bold', 'italic', 'underline']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                    ['view', ['codeview']]
                ],
                callbacks: {
                    onBlur: function () {
                        const markup = $this.summernote('code');
                        $this.html(markup);
                        $(target).val(markup);
                    }
                }
            });
        }
    });
    /*
        $(document).on('click', function (e) {
            const $openEditor = $('.click-edit-minibox.is-editing, .click-edit-content.is-editing');
            if ($openEditor.length > 0) {
                if (
                    $(e.target).closest('.note-editor').length === 0 &&
                    $(e.target).closest('.note-toolbar').length === 0
                ) {
                    $openEditor.each(function () {
                        const $el = $(this);
                        if ($el.hasClass('is-editing')) {
                            try {
                                $el.summernote('destroy');
                            } catch (err) {
                                console.warn('skip destroy:', err.message);
                            }
                            $el.removeClass('is-editing');
                        }
                    });
                }
            }
        });
        */
});

document.getElementById('closeModalBtn').addEventListener('click', function () {
    closeAowebModal();
});

// ปิดเมื่อคลิกพื้นหลัง (ผ่าน closeAowebModal เพื่อให้ reload พรีวิวถ้ามีการเปลี่ยนแปลง)
document.getElementById('modalOverlay').addEventListener('click', function (e) {
    if (e.target === this) {
        closeAowebModal();
    }
});

/* ################## ช่วง 5: รับสัญญาณจาก editor iframe (postMessage) ################### */
window.addEventListener('message', function (e) {
    if (e.origin !== location.origin) return;          // กัน message ปลอมข้าม origin
    const d = e.data || {};
    if (d.type !== 'aobuilder') return;

    switch (d.action) {
        case 'busy':
            // editor กำลังบันทึก/ลบ → ล็อกไม่ให้ปิด modal ระหว่างโหลด
            aoSetBusy(true);
            break;
        case 'idle':
            // จบงาน (พลาด/ยกเลิก) → ปลดล็อก
            aoSetBusy(false);
            break;
        case 'saved':
            // บันทึกสำเร็จ → ปลดล็อก + ปิด modal + reload หน้า preview ให้เห็นค่าที่อัปเดต
            aoSetBusy(false);
            closeAowebModal(true);
            break;
        case 'dirty':
            // จัดลำดับ/ลบสำเร็จ → ปลดล็อก, modal ยังเปิด, รอ reload ตอนปิด
            aoSetBusy(false);
            window.__aoDirty = true;
            break;
        case 'close':
            closeAowebModal();
            break;
        case 'resize': {
            // ปรับความสูง iframe ตามเนื้อหา (กัน scroll ซ้อน) แต่ไม่เกินจอที่มองเห็นจริง
            // ห้ามใช้ window.innerHeight ตรง ๆ — หน้านี้อยู่ใน iframe ที่ถูกยืดสูงเท่าเนื้อหา
            // innerHeight จึงเท่ากับความสูงทั้งหน้า ไม่ใช่ความสูงจอ ต้องถามจาก parent แทน
            const f = document.getElementById('aoEditorFrame');
            if (f && d.height) {
                const band = aoViewBand();
                f.style.height = Math.min(d.height, Math.floor(band.height * 0.78)) + 'px';
                aoPlaceModal();
            }
            break;
        }
    }
});

/* ################## modal ตามตำแหน่ง scroll ################## */
// หน้าเว็บถูกฝังใน iframe ของหน้าแอดมินซึ่งยืด iframe สูงเท่าเนื้อหา (scroll จริงอยู่ที่หน้าแอดมิน)
// position:fixed ในเอกสารนี้จึงปักอยู่ "บนสุดของทั้งหน้า" — กดแก้ส่วนล่าง ๆ modal เลยหลุดจอ
// ต้องถาม parent ว่าจอกำลังมองเห็นช่วงไหนของ iframe แล้วเลื่อน modal ลงไปตรงนั้น

// คืนแถบที่มองเห็นจริง: top = จุดเริ่มในพิกัดเอกสารนี้, height = ความสูงจอที่เห็น
function aoViewBand() {
    try {
        if (window.frameElement && window.parent !== window) {
            const r = window.frameElement.getBoundingClientRect();
            const vh = window.parent.innerHeight || window.innerHeight;
            return { top: Math.max(0, -r.top), height: vh };
        }
    } catch (e) { /* cross-origin: ใช้ viewport ของตัวเองตามเดิม */ }
    return { top: 0, height: window.innerHeight };
}

function aoPlaceModal() {
    const overlay = document.getElementById('modalOverlay');
    if (!overlay || overlay.style.display !== 'flex') return;
    const band = aoViewBand();
    // วาง modal ห่างขอบบนของจอราว 5% ของความสูงจอ (อย่างน้อย 24px เผื่อปุ่มปิดที่ล้นกรอบ)
    overlay.style.paddingTop = Math.round(band.top + Math.max(24, band.height * 0.05)) + 'px';
}

// ตามจอเมื่อหน้าแอดมิน scroll/ย่อขยาย — เรียกตรงได้เลย: scroll event ถูกจัดจังหวะตาม frame
// อยู่แล้ว และงานใน handler มีแค่เขียน style ค่าเดียว; ตอน modal ปิด aoPlaceModal จะ no-op เอง
(function () {
    try {
        if (window.parent !== window) {
            window.parent.addEventListener('scroll', aoPlaceModal, { passive: true });
            window.parent.addEventListener('resize', aoPlaceModal, { passive: true });
        }
    } catch (e) { /* cross-origin: modal จะวางตำแหน่งครั้งเดียวตอนเปิด */ }
})();

function setModalWidth(width) {
    const modal = document.querySelector('.aoweb-modal');
    if (!modal) return;
    modal.style.width = '';
    modal.style.maxWidth = '';
    if (width.endsWith('%') || width.endsWith('px') || width.endsWith('vw')) {
        modal.style.width = width;
    } else {
        modal.style.width = width + 'px';
    }
}

// ล็อกการปิด modal ระหว่าง editor กำลังบันทึก/ลบ (มี backstop ปลดเองกันค้าง)
var __aoBusyTimer = null;
function aoSetBusy(b) {
    window.__aoBusy = !!b;
    clearTimeout(__aoBusyTimer);
    if (b) __aoBusyTimer = setTimeout(function () { window.__aoBusy = false; }, 20000);
}

function closeAowebModal(forceReload) {
    if (window.__aoBusy) return;   // กำลังบันทึก/ลบ → ปิดไม่ได้
    const ov = document.getElementById('modalOverlay');
    if (ov) ov.style.display = 'none';
    // reload พรีวิวเมื่อบันทึก (forceReload) หรือมีการจัดลำดับ/ลบค้างไว้ (__aoDirty)
    if (forceReload || window.__aoDirty) {
        window.__aoDirty = false;
        location.reload();
    }
}

// เปิด editor modal จาก element ที่มี data-ao="aobuilder" — ใช้ร่วม left-click + right-click "แก้ไขข้อมูล"
function aoOpenEditor(el) {
    if (!el) return;
    const ac = el.getAttribute('data-ac') || '';
    const keysname = el.getAttribute('data-keysname') || '';
    const vals = el.getAttribute('data-vals') || '';
    const size = el.getAttribute('data-size') || '';

    const frame = document.getElementById('aoEditorFrame');
    const overlay = document.getElementById('modalOverlay');
    if (!frame || !overlay) return;

    const url = '/admbuilder/editor.php?ac=' + encodeURIComponent(ac) +
        '&keysname=' + encodeURIComponent(keysname) +
        '&data=' + encodeURIComponent(vals);

    // spinner คลุม iframe จนกว่า editor ใหม่จะโหลดเสร็จ (กันเนื้อหาอันเก่าค้างแวบ)
    aoEditorLoading(true);

    // reset ก่อน เพื่อล้าง summernote/ค่าเก่า และให้ reclick element เดิมโหลดใหม่เสมอ
    frame.src = 'about:blank';
    setTimeout(function () { frame.src = url; }, 0);
    setTimeout(function () { aoEditorLoading(false); }, 8000); // backstop กัน spinner ค้าง

    setModalWidth(size);
    overlay.style.display = 'flex';
    aoPlaceModal();
    console.log('openEditor : ' + url);
}

// แสดง/ซ่อน spinner ในกล่อง editor
function aoEditorLoading(show) {
    const l = document.getElementById('aoEditorLoading');
    if (l) l.style.display = show ? 'flex' : 'none';
}

// ซ่อน spinner เมื่อ editor โหลดจริงเสร็จ (ข้าม about:blank ตอน reset)
(function () {
    const frame = document.getElementById('aoEditorFrame');
    if (!frame) return;
    frame.addEventListener('load', function () {
        let href = '';
        try { href = frame.contentWindow.location.href; } catch (e) {}
        if (href.indexOf('about:blank') === -1) aoEditorLoading(false);
    });
})();

(function () {
    // ใช้ namespace ao เพื่อกันชน
    function aoInitSortableTable() {
        const aoTable = document.querySelector("#ao-table-sortable");
        if (!aoTable) return; // ถ้าไม่มีตารางนี้ ให้ข้าม

        const aoTbody = aoTable.querySelector("tbody");
        if (!aoTbody || aoTbody.dataset.aoSortable === "1") return; // ป้องกัน bind ซ้ำ
        aoTbody.dataset.aoSortable = "1"; // mark ว่าผูกแล้ว

        let draggingRow = null;

        // เฉพาะไอคอนที่กำหนดเป็น handle เท่านั้น
        aoTbody.querySelectorAll(".ao-drag-handle").forEach((handle) => {
            handle.addEventListener("mousedown", (e) => {
                const tr = e.target.closest("tr");
                tr.setAttribute("draggable", "true");
            });
            handle.addEventListener("mouseup", (e) => {
                const tr = e.target.closest("tr");
                tr.removeAttribute("draggable");
            });
        });

        aoTbody.addEventListener("dragstart", (e) => {
            draggingRow = e.target.closest("tr");
            if (!draggingRow) return;
            e.dataTransfer.effectAllowed = "move";
            draggingRow.classList.add("ao-dragging");
        });

        aoTbody.addEventListener("dragend", () => {
            if (draggingRow) draggingRow.classList.remove("ao-dragging");
            draggingRow = null;
        });

        aoTbody.addEventListener("dragover", (e) => {
            e.preventDefault();
            const targetRow = e.target.closest("tr");
            if (!targetRow || targetRow === draggingRow) return;
            const rect = targetRow.getBoundingClientRect();
            const next = (e.clientY - rect.top) / rect.height > 0.5;
            aoTbody.insertBefore(
                draggingRow,
                next ? targetRow.nextSibling : targetRow
            );
        });

        aoTbody.addEventListener("drop", () => {
            aoUpdateOrder(aoTbody);
        });
    }

    function aoUpdateOrder(tbody) {
        const aoOrder = {};

        // เก็บค่าลำดับใหม่
        [...tbody.rows].forEach((row, index) => {
            //const id = row.querySelector("td:nth-child(2)")?.innerText.trim();
            const id = row.dataset.id;
            const input = row.querySelector('input[type="number"]');
            const order = index + 1;
            if (input) input.value = order;
            if (id) aoOrder[id] = order;
        });

        console.log("aoOrder:", aoOrder);
        const formData = new FormData();
        for (const id in aoOrder) {
            //formData.append(id, aoOrder[id]);
            formData.append(`aSort[${id}]`, aoOrder[id]);
        }
        fetch("/admbuilder/save.php?ac=sortarticles", {
            method: "POST",
            body: formData
        })
            .then((res) => res.text())
            .then((txt) => {
                console.log("response:", txt);
                // แสดงข้อความ success
                alert("อัปเดตลำดับเรียบร้อยแล้ว");
            }).catch((err) => {
                console.error("เกิดข้อผิดพลาด:", err);
            });
    }





    // ✅ เรียกตอนหน้าโหลดครั้งแรก
    document.addEventListener("DOMContentLoaded", aoInitSortableTable);

    // ✅ เรียกใหม่ทุกครั้งที่ htmx โหลดส่วนตารางเข้ามา
    document.body.addEventListener("htmx:afterSwap", (evt) => {
        if (
            evt.target.querySelector &&
            evt.target.querySelector("#ao-table-sortable")
        ) {
            aoInitSortableTable();
        }
    });
})();

// right-click ลิงก์ภายในตอน edit mode → "เข้าหน้านี้" + "แก้ไขข้อมูล" (ถ้าลิงก์อยู่ในบล็อกที่แก้ได้)
(function () {
    let aoNavMenu = null, aoHiliteEl = null, aoHilitePrev = '';

    function aoClearHilite() {
        if (aoHiliteEl) { aoHiliteEl.style.backgroundColor = aoHilitePrev; aoHiliteEl = null; }
    }
    function aoCloseNav() {
        if (aoNavMenu) { aoNavMenu.remove(); aoNavMenu = null; }
        aoClearHilite();
    }
    function aoNavItem(label, onClick) {
        const item = document.createElement('div');
        item.textContent = label;
        item.style.cssText = 'padding:8px 12px;cursor:pointer;border-radius:6px;color:#222;';
        item.addEventListener('mouseenter', function () { item.style.background = '#f0f0f0'; });
        item.addEventListener('mouseleave', function () { item.style.background = ''; });
        item.addEventListener('click', onClick);
        return item;
    }

    document.addEventListener('contextmenu', function (e) {
        const a = e.target.closest('a[href]');
        if (!a) return;
        const href = a.getAttribute('href') || '';
        if (href === '' || href === '#' || /^(javascript:|mailto:|tel:)/i.test(href) || a.target === '_blank') return;
        if (/^https?:\/\//i.test(href) && href.indexOf(location.host) === -1) return;

        e.preventDefault();
        aoCloseNav();

        const editable = e.target.closest('[data-ao="aobuilder"]');

        aoNavMenu = document.createElement('div');
        aoNavMenu.style.cssText = 'position:fixed;z-index:2147483647;background:#fff;border:1px solid #ccc;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.2);padding:4px;font-size:14px;min-width:150px;';
        aoNavMenu.style.left = Math.min(e.clientX, window.innerWidth - 170) + 'px';
        aoNavMenu.style.top = Math.min(e.clientY, window.innerHeight - 90) + 'px';

        aoNavMenu.appendChild(aoNavItem('➜ เข้าหน้านี้', function () { window.location.href = a.href; }));
        if (editable) {
            aoNavMenu.appendChild(aoNavItem('✎ แก้ไขข้อมูล', function () { aoCloseNav(); aoOpenEditor(editable); }));
        }
        document.body.appendChild(aoNavMenu);

        // ไฮไลต์ตัวที่คลิกขวาเทาอ่อน ๆ ระหว่างเมนูเปิด
        aoHiliteEl = a;
        aoHilitePrev = a.style.backgroundColor || '';
        a.style.backgroundColor = 'rgba(0,0,0,.06)';
    });

    document.addEventListener('click', aoCloseNav);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') aoCloseNav(); });
})();
