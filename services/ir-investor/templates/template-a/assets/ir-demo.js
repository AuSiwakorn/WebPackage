/* AOINVESTOR IR — demo template switcher
   ใช้เฉพาะไฟล์สาธิต: จำ template ที่เลือกไว้ใน localStorage แล้วใช้กับทุกหน้า
   ระบบจริง: Admweb render <html data-template="..."> จาก ir_setting.template แทน */
(function () {
  var KEY = 'aoinvestor_tpl';
  var html = document.documentElement;
  var bar = document.querySelector('.demo-bar');
  if (!bar) return;
  var btns = bar.querySelectorAll('button[data-tpl]');
  function apply(t, save) {
    html.setAttribute('data-template', t);
    btns.forEach(function (b) { b.setAttribute('aria-pressed', String(b.dataset.tpl === t)); });
    if (save) { try { localStorage.setItem(KEY, t); } catch (e) {} }
  }
  btns.forEach(function (b) { b.addEventListener('click', function () { apply(b.dataset.tpl, true); }); });
  apply(html.getAttribute('data-template') || 'a', false);
})();
