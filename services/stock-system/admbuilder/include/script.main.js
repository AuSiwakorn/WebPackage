let seoMetaKey = null;   // { meta_key, schema_key, page_lang } ของหน้าปัจจุบัน
let seoLang = 'th';      // ภาษาที่กำลังแก้ใน panel SEO

document.addEventListener("DOMContentLoaded", function () {
    initAsideHandlers();
});


function initAsideHandlers() {
    const aobuilderFrame = document.querySelector('.ao-preview__frame');
    const overlay = document.createElement('div');
    overlay.className = 'ao-overlay';
    document.body.appendChild(overlay);

    // ปุ่มเปิดซ้าย
    document.getElementById('AObtnOpenLeft').addEventListener('click', () => {
        document.getElementById('asideLeft').classList.add('show');
        overlay.classList.add('show');
    });

    // ปุ่มเปิดขวา
    document.getElementById('AObtnOpenRight').addEventListener('click', () => {
        const metaKey = readIframeMeta();
        seoMetaKey = metaKey;
        seoLang = (metaKey && metaKey.page_lang) ? metaKey.page_lang : 'th'; // เริ่มที่ภาษาของหน้า

        loadAsideRight();

        document.getElementById('asideRight').classList.add('show');
        overlay.classList.add('show');
    });

    // ปุ่มปิด
    document.querySelectorAll('.btnClose').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById(btn.dataset.target).classList.remove('show');
            overlay.classList.remove('show');
        });
    });

    // คลิก overlay เพื่อปิดทั้งหมด
    overlay.addEventListener('click', () => {
        document.querySelectorAll('.ao_aside, .ao_aside_right').forEach(el => el.classList.remove('show'));
        overlay.classList.remove('show');
    });
}

function readIframeMeta(frameSelector = '.ao-preview__frame') {
    const iframe = document.querySelector(frameSelector);
    if (!iframe) {
        console.warn('ไม่พบ iframe:', frameSelector);
        return null;
    }

    try {
        const innerDoc = iframe.contentDocument || iframe.contentWindow.document;
        const metaKey = innerDoc.querySelector('meta[name="aosoft-meta-key"]')?.content || '';
        const schemaKey = innerDoc.querySelector('meta[name="aosoft-schema-key"]')?.content || '';
        const pageLang = innerDoc.querySelector('meta[name="aosoft-lang"]')?.content || 'th';
        const result = {
            meta_key: metaKey,
            schema_key: schemaKey,
            page_lang: pageLang
        };

        console.log("✅ Meta key จาก iframe:", result);
        return result;
    } catch (e) {
        console.warn("⚠️ ไม่สามารถเข้าถึง iframe (cross-domain):", e);
        return null;
    }
}

// init ตัวนับตัวอักษรของฟอร์ม meta — inner.meta.php ถูกยัดผ่าน innerHTML ซึ่งไม่รัน <script> ในตัว
function initSeoMeta() {
    document.querySelectorAll('#RightMetaBox .ao-counter').forEach(function (c) {
        const el = document.getElementById(c.getAttribute('data-for'));
        const max = parseInt(c.getAttribute('data-max'), 10);
        if (!el) return;
        const upd = function () {
            const n = (el.value || '').length;
            c.textContent = n + '/' + max;
            c.style.color = n > max ? '#c0392b' : '#888';
        };
        el.addEventListener('input', upd);
        upd();
    });
}

async function loadMeta(metaKey, lang) {
    if (!metaKey?.meta_key) return; // ป้องกัน error ถ้าไม่มี key
    const box = document.getElementById('RightMetaBox');
    if (!box) return console.warn('ไม่พบ RightMetaBox');

    try {
        const params = new URLSearchParams({
            target: 'asideRight',
            ac: 'loadmeta',
            meta_key: metaKey.meta_key,
            schema_key: metaKey.schema_key,
            seolang: lang || seoLang
        });

        const response = await fetch('admbuilder/load.php?' + params.toString(), {
            method: 'GET',
            headers: { 'HX-Request': 'true' }
        });

        if (!response.ok) throw new Error('HTTP ' + response.status);

        box.innerHTML = await response.text();
        initSeoMeta();
    } catch (err) {
        console.error('❌ โหลด meta ล้มเหลว:', err);
    }
}

async function loadSchema(metaKey, lang) {
    if (!metaKey?.schema_key) return;
    const box = document.getElementById('RightSchemaBox');
    if (!box) return console.warn('ไม่พบ RightSchemaBox');

    try {
        const params = new URLSearchParams({
            target: 'asideRight',
            ac: 'loadschema',
            meta_key: metaKey.meta_key,
            schema_key: metaKey.schema_key,
            seolang: lang || seoLang
        });

        const response = await fetch('admbuilder/load.php?' + params.toString(), {
            method: 'GET',
            headers: { 'HX-Request': 'true' }
        });

        if (!response.ok) throw new Error('HTTP ' + response.status);

        box.innerHTML = await response.text();
    } catch (err) {
        console.error('❌ โหลด schema ล้มเหลว:', err);
    }
}

async function loadAsideRight() {
    const metaKey = seoMetaKey || readIframeMeta();
    if (!metaKey) {
        console.warn('⚠️ ไม่มีข้อมูล metaKey จาก iframe');
        return;
    }
    seoMetaKey = metaKey;
    setSeoLangActive(seoLang);

    // โหลดพร้อมกันทั้งสองส่วน (ตามภาษาที่เลือก)
    await Promise.all([loadMeta(metaKey, seoLang), loadSchema(metaKey, seoLang)]);
    console.log('✅ โหลด asideRight เรียบร้อย lang=' + seoLang);
}

// สลับภาษาที่แก้ใน panel — โหลดฟอร์มของภาษานั้นมาแทน (คนละแถวใน DB)
function switchSeoLang(lang) {
    seoLang = lang;
    setSeoLangActive(lang);
    if (!seoMetaKey) seoMetaKey = readIframeMeta();
    loadMeta(seoMetaKey, lang);
    loadSchema(seoMetaKey, lang);
}

function setSeoLangActive(lang) {
    document.querySelectorAll('.ao-seo-lang-btn').forEach(function (b) {
        const on = b.getAttribute('data-seolang') === lang;
        b.classList.toggle('active', on);
        b.classList.toggle('btn-info', on);
    });
}

function loadtemplateSchema()
{
    alert(1);
}


document.addEventListener('click', e => {
    const link = e.target.closest('.has-submenu > a');
    if (link) {
        e.preventDefault();
        const parent = link.parentElement;
        parent.classList.toggle('open');
    }
});

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.aotab-content').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        document.querySelector(`#tab-${this.dataset.tab}`).classList.add('active');
    });
});
