
<?php /* ?>
<div class="modal fade ao-builder-modal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog">
    <div class="modal-content ao-builder-content">
    </div>
  </div>
</div>
<?php */ ?>

<!-- ตัว popup -->
<!--
  ช่วง 2 (iframe isolation): เปลี่ยนจาก <div class="ao-builder-content"> ที่เคยรับ HTML จาก HTMX
  มาเป็น <iframe> ฝากหน้า editor.php (เอกสารเดี่ยว คุม CSS เอง) — กัน CSS ของ template ตีกับ editor
  sandbox: allow-same-origin จำเป็นเพื่อให้ session/cookie + อ่าน DB ทำงาน
  การ set src ตอนคลิก element ทำในช่วง 3
-->
<div id="modalOverlay" class="aoweb-modal-overlay">
  <div class="aoweb-modal">
    <button class="aoweb-close-btn" id="closeModalBtn">&times;</button>
    <iframe
      id="aoEditorFrame"
      class="ao-builder-content"
      src="about:blank"
      sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-popups-to-escape-sandbox"
      title="AO Builder Editor"></iframe>
    <div id="aoEditorLoading" class="aoweb-modal-loading" aria-hidden="true"><div class="aoweb-spinner"></div></div>
  </div>
</div>