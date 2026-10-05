(function () {
  'use strict';

  const ALLOWED_EXT = [
    'jpg', 'mov', 'mp3', 'mp4', 'png', 'gif', 'pdf', 'docx',
    'xlsx', 'txt', 'zip', 'rar', 'tar', '7z', 'csv', 'json', 'xml'
  ];
  const CHUNK_SIZE = 2 * 1024 * 1024; // 2MB

  const els = {
    file: document.getElementById('upFileInput'),
    btn: document.getElementById('upFileBtn'),
    id: document.getElementById('upFileId'),
    old: document.getElementById('upFileOld'),
    status: document.getElementById('upFileStatus'),
    progress: document.getElementById('upFileProgress'),
    link: document.getElementById('upFileLink'),
    linkWrap: document.getElementById('upFileLinkWrap'),
    delBtn: document.getElementById('upFileDelBtn'),
  };

  if (!els.file && !els.delBtn) return;

  const scriptSrc = (document.currentScript && document.currentScript.src) || '';
  const UPLOAD_URL = scriptSrc.replace(/script\.js(\?.*)?$/, 'upload.php');
  const DELETE_URL = scriptSrc.replace(/script\.js(\?.*)?$/, 'delete.php');

  // ───── Helpers ─────
  const setStatus = (msg) => { if (els.status) els.status.innerText = msg; };
  const setProgress = (val) => { if (els.progress) els.progress.value = val; };
  const getValue = (el) => (el && el.value) ? el.value : '';
  const updateLink = (path) => {
    if (els.old) els.old.value = path;
    if (els.link) {
      const base = els.link.dataset.urlBase || '';
      els.link.href = base + path;
      els.link.innerText = path ? path.split('/').pop() : '';
    }
    if (els.linkWrap) els.linkWrap.style.display = path ? '' : 'none';
  };
  const postForm = async (url, formData) => {
    const res = await fetch(url, { method: 'POST', body: formData });
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'request failed');
    return data;
  };

  // ───── Delete handler ─────
  if (els.delBtn) {
    els.delBtn.addEventListener('click', async () => {
      const filePath = getValue(els.old);
      const uploadId = getValue(els.id);
      if (!filePath) return;
      if (!confirm('ยืนยันการลบไฟล์นี้?')) return;

      const originalText = els.delBtn.innerText;
      els.delBtn.disabled = true;
      els.delBtn.innerText = 'กำลังลบ...';

      try {
        const form = new FormData();
        form.append('uploadId', uploadId);
        form.append('filePath', filePath);
        await postForm(DELETE_URL, form);
        updateLink('');
        setStatus('ลบไฟล์เรียบร้อย');
      } catch (err) {
        alert('ลบไฟล์ไม่สำเร็จ: ' + err.message);
        els.delBtn.disabled = false;
        els.delBtn.innerText = originalText;
      }
    });
  }

  if (!els.file) return;

  // ───── File select ─────
  let selectedFile = null;

  els.file.addEventListener('change', () => {
    const file = els.file.files[0];
    setStatus('');
    setProgress(0);

    if (!file) {
      els.btn.style.display = 'none';
      return;
    }

    const ext = file.name.split('.').pop().toLowerCase();
    if (!ALLOWED_EXT.includes(ext)) {
      setStatus(`JS ไม่อนุญาตให้อัปโหลดไฟล์ .${ext}`);
      els.file.value = '';
      els.btn.style.display = 'none';
      return;
    }

    selectedFile = file;
    els.btn.style.display = 'inline-block';
    els.btn.disabled = false;
    els.btn.innerText = 'อัปโหลด';
  });

  // ───── Upload (chunked) ─────
  els.btn.addEventListener('click', async () => {
    if (!selectedFile) {
      setStatus('กรุณาเลือกไฟล์ก่อนอัปโหลด');
      return;
    }

    els.btn.disabled = true;
    els.btn.innerText = 'กำลังอัปโหลด...';

    const file = selectedFile;
    const uploadId = getValue(els.id);
    const oldFilePath = getValue(els.old);
    const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
    const fileId = btoa(file.name + file.size);
    let finalFileName = null;

    for (let i = 0; i < totalChunks; i++) {
      const start = i * CHUNK_SIZE;
      const end = Math.min(start + CHUNK_SIZE, file.size);
      const chunk = file.slice(start, end);

      const form = new FormData();
      form.append('file', chunk);
      form.append('fileName', file.name);
      form.append('fileId', fileId);
      form.append('chunkIndex', i);
      form.append('totalChunks', totalChunks);
      form.append('uploadId', uploadId);
      form.append('oldFilePath', oldFilePath);

      try {
        const result = await postForm(UPLOAD_URL, form);
        if (result.fileName) finalFileName = result.fileName;

        const percent = Math.floor(((i + 1) / totalChunks) * 100);
        setProgress(percent);
        setStatus(`อัปโหลด ${percent}%`);
      } catch (err) {
        setStatus(`เกิดข้อผิดพลาดที่ chunk ${i}: ${err.message}`);
        els.btn.disabled = false;
        els.btn.innerText = 'อัปโหลดอีกครั้ง';
        return;
      }
    }

    if (finalFileName) {
      setStatus(`อัปโหลดเสร็จสมบูรณ์! ${finalFileName}`);
      els.btn.innerText = 'อัปโหลดสำเร็จ';
      updateLink(finalFileName);
    } else {
      setStatus('อัปโหลดไม่ครบ');
    }

    selectedFile = null;
    els.file.value = '';
  });
})();
