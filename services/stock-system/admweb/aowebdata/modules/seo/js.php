<script>
function getTemplateSchema(name, lng) {
    // ดึง template จาก element ที่เก็บไว้ (เช่น <pre> หรือ <div hidden>)
    const templateEl = document.querySelector(`.${name}_${lng}`);
    const textareaEl = document.querySelector(`.input_json_${lng}`);

    if (!templateEl || !textareaEl) {
        console.warn('Template หรือ Input JSON ไม่พบ');
        return;
    }

    // ใช้ .textContent แทน .text() เพื่อเก็บ formatting เดิมของ JSON
    const templateText = templateEl.textContent.trim();

    // ใส่ลง textarea
    textareaEl.value = templateText;

    // optional: highlight textarea หลังเปลี่ยนค่า
    textareaEl.style.backgroundColor = '#fff8e1';
    setTimeout(() => textareaEl.style.backgroundColor = '', 800);
}
</script>