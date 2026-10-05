<?php
/**
 * FILE: themes/aostock/inc/footer.php
 * ROLE: ส่วนท้ายหน้าจอหลัง login + JS กลาง (modal ยืนยัน, หน้าต่างพิมพ์บิล, drawer มือถือ)
 * DEPENDS: themes/aostock/include/function.php
 * TABLES: - (ส่วนแสดงผล + JS กลาง — ไม่อ่านตารางเอง)
 * TODO:
 *   - [x] ย้ายจาก demo/ เข้า themes/aostock/ (วิ่งผ่าน router ของ admweb)
 *   - [x] อ่าน / เขียนข้อมูลจากตาราง ao_stock_* ผ่าน api.php (ช่วงที่ 5–9)
 *   - [x] ช่วงที่ 10: แก้ File Header ให้ตรงกับระบบจริง · กัน XSS — ค่าทุกตัวในกล่องยืนยันผ่าน esc() · ทูลทิปกราฟใช้ textContent
 *   - [x] ช่วงที่ 11: ช่องติ๊กสิทธิ์ที่พ่วงกัน (data-needs) ปิด / เปิดตามตัวหลัก
 *   - [x] เขียนเงื่อนไข / วนลูปแบบวงเล็บปีกกา { } แทน endif / endforeach / endfor · แท็กย่อ (short echo) เปลี่ยนเป็น <?php echo
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
require_once dirname(__FILE__) . '/../include/function.php'; ?>
    </main><!-- /.page -->
  </div><!-- /.main -->
</div><!-- /.app -->

<?php if (in_array($NAV_ACTIVE, array('sale.php', 'history.php', 'receive.php', 'issue.php', 'stocktake.php', 'adm-history.php'), true)) { ?>
<!-- ===== กล่องยืนยัน — ใช้ทั้งรับเงินและยกเลิกบิล ===== -->
<div class="modal" id="confirm-modal" hidden>
  <div class="modal-back" data-close></div>
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="cf-title">
    <div class="modal-head">
      <div>
        <h2 id="cf-title">ยืนยันการรับเงิน</h2>
        <span class="sub" id="cf-sub">ตรวจให้แน่ใจก่อน บันทึกแล้วจะตัดสต๊อกทันที</span>
      </div>
      <button class="icon-btn" type="button" data-close aria-label="ปิด">
        <svg class="ico"><use href="#i-x"/></svg>
      </button>
    </div>
    <div class="modal-body" id="cf-body"></div>
    <div class="modal-foot">
      <button class="btn btn-ghost" type="button" data-close>ย้อนกลับ</button>
      <button class="btn-pay" type="button" id="cf-ok">
        <svg class="ico"><use href="#i-check"/></svg> <span id="cf-ok-t">ยืนยัน บันทึกบิล</span>
      </button>
    </div>
  </div>
</div>
<?php } ?>

<!-- ===== ดู / พิมพ์บิล — ปุ่มที่มี data-bill-print="bill-print.php?…" ทุกหน้า (รวมเนื้อหาที่โหลดเข้า popup ภายหลัง) ===== -->
<dialog class="bill-modal" id="bill-modal" aria-label="ดูบิล">
  <div class="bill-bar">
    <b id="bill-no">บิล</b>
    <div class="segs" role="group" aria-label="ขนาดกระดาษ">
      <button type="button" class="seg" data-bill-size="80">80 มม.</button>
      <button type="button" class="seg" data-bill-size="a4">A4</button>
    </div>
    <span class="bill-sp"></span>
    <a class="btn btn-ghost btn-sm" id="bill-tab" href="#" target="_blank" rel="noopener">เปิดแท็บใหม่</a>
    <button type="button" class="btn btn-primary btn-sm" id="bill-go"><svg class="ico"><use href="#i-print"/></svg> พิมพ์</button>
    <button type="button" class="icon-btn" data-bill-close aria-label="ปิด"><svg class="ico"><use href="#i-x"/></svg></button>
  </div>
  <iframe id="bill-frame" title="บิล" src="about:blank"></iframe>
</dialog>
<script>
(function () {
  var dlg = document.getElementById('bill-modal');
  if (!dlg || !dlg.showModal) { return; }
  var fr = document.getElementById('bill-frame'), no = document.getElementById('bill-no'),
      tab = document.getElementById('bill-tab'), base = '', size = '';
  function load() {
    var u = base + (size ? '&size=' + size : '');
    fr.src = u + '&embed=1';
    tab.href = u + '&print=1';
    var b = dlg.querySelectorAll('[data-bill-size]');
    for (var i = 0; i < b.length; i++) { b[i].classList.toggle('on', b[i].getAttribute('data-bill-size') === size); }
  }
  /* ขนาดจริงที่หน้าเลือกให้ (ตามค่าตั้งของสาขา) — อ่านจากเนื้อหาที่โหลดมา */
  fr.addEventListener('load', function () {
    try {
      if (!size) {
        size = fr.contentDocument.querySelector('.sa4') ? 'a4' : '80';
        var b = dlg.querySelectorAll('[data-bill-size]');
        for (var i = 0; i < b.length; i++) { b[i].classList.toggle('on', b[i].getAttribute('data-bill-size') === size); }
      }
      dlg.classList.toggle('is-a4', size === 'a4');
    } catch (e) {}
  });
  document.addEventListener('click', function (ev) {
    var t = ev.target.closest ? ev.target.closest('[data-bill-print]') : null;
    if (t) {
      ev.preventDefault();
      base = t.getAttribute('data-bill-print');
      size = '';
      no.textContent = t.getAttribute('data-bill-no') || 'บิล';
      load();
      dlg.showModal();
      return;
    }
    var s = ev.target.closest ? ev.target.closest('[data-bill-size]') : null;
    if (s && dlg.contains(s)) { size = s.getAttribute('data-bill-size'); load(); return; }
    if ((ev.target.closest && ev.target.closest('[data-bill-close]')) || ev.target === dlg) { dlg.close(); }
  });
  dlg.addEventListener('close', function () { fr.src = 'about:blank'; });
  document.getElementById('bill-go').addEventListener('click', function () {
    try { fr.contentWindow.focus(); fr.contentWindow.print(); } catch (e) { window.open(tab.href, '_blank'); }
  });
})();
</script>

<!-- htmx: สลับเฉพาะส่วนที่เปลี่ยน ไม่ต้องโหลดหน้าใหม่ (เก็บไฟล์ไว้ในเครื่อง ไม่พึ่ง CDN) -->
<script src="<?php echo e(asset_url('htmx.min.js')) ?>"></script>
<script>
(function () {
  /* เมนูด้านข้างบนจอเล็ก */
  var side = document.getElementById('side');
  var bd   = document.getElementById('side-backdrop');
  var tg   = document.getElementById('side-toggle');
  function setOpen(open) {
    side.classList.toggle('open', open);
    bd.classList.toggle('open', open);
    tg.setAttribute('aria-expanded', String(open));
  }
  var cl = document.getElementById('side-close');
  if (tg) tg.addEventListener('click', function () { setOpen(!side.classList.contains('open')); });
  if (bd) bd.addEventListener('click', function () { setOpen(false); });
  if (cl) cl.addEventListener('click', function () { setOpen(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
  /* แตะเมนูแล้วปิดลิ้นชักทันที + ปิดเองเมื่อหมุนจอกลับเป็นจอกว้าง */
  side.querySelectorAll('a').forEach(function (a) {
    a.addEventListener('click', function () { setOpen(false); });
  });
  window.addEventListener('resize', function () {
    if (window.innerWidth > 900) setOpen(false);
  });

  /* ปุ่มย่อ / ขยายเมนูด้านข้าง (จำไว้ใน cookie 1 ปี) */
  var app  = document.getElementById('app');
  var mode = document.getElementById('nav-mode');
  if (app && mode) {
    mode.addEventListener('click', function () {
      var rail = app.classList.toggle('app--rail');
      document.cookie = 'ao_nav=' + (rail ? 'rail' : 'full') +
                        ';path=/;max-age=31536000;samesite=Lax';
      mode.setAttribute('aria-pressed', String(rail));
    });
  }

  /* htmx สลับเนื้อหาแล้วให้เคอร์เซอร์กลับไปอยู่ช่องเดิม
     (พิมพ์ค้นหาอยู่แล้วผลลัพธ์เปลี่ยน ต้องพิมพ์ต่อได้ทันที) */
  var lastFocus = '';
  document.body.addEventListener('htmx:beforeSwap', function () {
    var a = document.activeElement;
    lastFocus = (a && a.id) ? a.id : '';
  });
  document.body.addEventListener('htmx:afterSettle', function () {
    if (!lastFocus) { return; }
    var el = document.getElementById(lastFocus);
    if (!el || el === document.activeElement || typeof el.focus !== 'function') { return; }
    el.focus();
    try { var v = el.value; el.value = ''; el.value = v; } catch (e) {}   /* เคอร์เซอร์ไปท้ายข้อความ */
  });

  /* ===== กล่องยืนยันก่อนบันทึกจริง =====
     รองรับสองแบบ
       - ฟอร์มที่ใช้ htmx  → ดักที่เหตุการณ์ htmx:confirm
       - ฟอร์มธรรมดา       → ดักที่ submit
     ผูกครั้งเดียว ไม่ผูกซ้ำตอน htmx สลับเนื้อหา */
  var modal = document.getElementById('confirm-modal');
  if (modal && !window.__aoConfirmBound) {
    window.__aoConfirmBound = true;

    var cfBody  = document.getElementById('cf-body');
    var cfTitle = document.getElementById('cf-title');
    var cfSub   = document.getElementById('cf-sub');
    var cfOk    = document.getElementById('cf-ok');
    var cfOkT   = document.getElementById('cf-ok-t');

    var pending = null;      /* ฟังก์ชันที่จะทำงานเมื่อกดยืนยัน */
    var curForm = null;      /* ฟอร์มที่กำลังยืนยัน */
    var lastEl  = null;

    var REASONS = ['กรอกจำนวนผิด', 'คิดเงินผิด', 'ลูกค้าเปลี่ยนใจ', 'ยิงสินค้าผิดตัว'];

    /* ค่าทุกตัวที่มาจากหน้าเว็บ (ชื่อสินค้า เลขอ้างอิง เหตุผล ฯลฯ) ต้องผ่าน esc() ก่อนต่อเป็น HTML ของกล่องยืนยัน (ช่วงที่ 10 — กัน XSS) */
    function esc(s) {
      return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (ch) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
      });
    }

    function closeModal() {
      modal.hidden = true;
      document.body.style.overflow = '';
      pending = null;
      curForm = null;
      if (lastEl && lastEl.focus) { lastEl.focus(); }
    }

    function openModal(opt) {
      cfTitle.textContent = opt.title;
      cfSub.textContent   = opt.sub;
      cfOkT.textContent   = opt.ok;
      cfBody.innerHTML    = opt.html;
      pending = opt.go;
      curForm = opt.form;
      modal.hidden = false;
      document.body.style.overflow = 'hidden';

      var note = document.getElementById('cf-note');
      if (note) {
        /* ต้องมีหมายเหตุก่อนจึงจะกดยืนยันได้ */
        var chips = cfBody.querySelectorAll('.cf-chip');
        for (var i = 0; i < chips.length; i++) {
          chips[i].addEventListener('click', function () {
            note.value = this.textContent;
            sync();
            note.focus();
          });
        }
        note.addEventListener('input', sync);
        sync();
        note.focus();
      } else {
        cfOk.disabled = false;
        cfOk.focus();
      }

      function sync() {
        var ok = note.value.replace(/\s/g, '') !== '';
        cfOk.disabled = !ok;
        var warn = document.getElementById('cf-need');
        if (warn) { warn.style.visibility = ok ? 'hidden' : 'visible'; }
      }
    }

    var closers = modal.querySelectorAll('[data-close]');
    for (var c = 0; c < closers.length; c++) {
      closers[c].addEventListener('click', closeModal);
    }

    cfOk.addEventListener('click', function () {
      if (cfOk.disabled) { return; }
      var note = document.getElementById('cf-note');
      if (note && curForm) {
        var box = curForm.querySelector('input[name="reason"]');
        if (box) { box.value = note.value.trim(); }
      }
      var go = pending;
      closeModal();
      if (go) { go(); }
    });

    document.addEventListener('keydown', function (ev) {
      if (!modal.hidden && ev.key === 'Escape') { ev.preventDefault(); closeModal(); }
    });

    /* ---------- เนื้อหาในกล่อง ---------- */
    function payHtml(form) {
      var rows = document.querySelectorAll('.cart-list li');
      var h = '<ul class="cf-items">';
      for (var i = 0; i < rows.length; i++) {
        var nm = rows[i].querySelector('.cl-t b');
        var qt = rows[i].querySelector('.cl-q input[name="qty"]');
        var sm = rows[i].querySelector('.cl-s');
        h += '<li><span class="n">' + esc(nm ? nm.textContent : '') + '</span>'
           + '<span class="q num">×' + esc(qt ? qt.value.trim() : '') + '</span>'
           + '<span class="s num">' + esc(sm ? sm.textContent.trim() : '') + '</span></li>';
      }
      h += '</ul>';

      var tot    = document.getElementById('total');
      var cash   = form.querySelector('input[name="method"][value="cash"]');
      var isCash = cash && cash.checked;
      var rec    = document.getElementById('received');
      var chg    = document.getElementById('change');

      h += '<div class="cf-sum">'
         + (function () {
             var dbx = document.getElementById('pay-disc');
             if (!dbx || dbx.hidden) { return ''; }
             return '<div class="cf-r"><span>ราคาเต็ม</span><b class="num">' + esc(dbx.querySelector('b').textContent) + ' บาท</b></div>'
                  + '<div class="cf-r cf-warn"><span>ส่วนลด</span><b class="num">' + esc(document.getElementById('disc').textContent) + ' บาท</b></div>';
           })()
         + '<div class="cf-r"><span>ยอดชำระ</span><b class="num">' + esc(tot ? tot.textContent : '') + ' บาท</b></div>'
         + '<div class="cf-r"><span>ชำระโดย</span><b>' + (isCash ? 'เงินสด' : 'โอน / พร้อมเพย์') + '</b></div>';
      var vatOn = form.querySelector('input[name="vat"][value="1"]');
      h += '<div class="cf-r"><span>ประเภทบิล</span><b>' + (vatOn && vatOn.checked ? 'บิล VAT' : 'บิลธรรมดา (ไม่ VAT)') + '</b></div>';
      if (isCash) {
        h += '<div class="cf-r"><span>รับมา</span><b class="num">'
           + (Number(String(rec.value).replace(/[^0-9]/g, '')) || 0).toLocaleString('th-TH') + '.00 บาท</b></div>'
           + '<div class="cf-r cf-big"><span>เงินทอน</span><b class="num">'
           + esc(chg ? chg.textContent : '0.00') + ' บาท</b></div>';
      }
      h += '</div>';
      return h;
    }

    /* doc: 'bill' = บิลขาย · 'recv' = ใบรับเข้า · 'issue' = ใบตัดออก */
    function voidHtml(form, isEdit, doc) {
      var isRecv  = (doc === 'recv');
      var isIssue = (doc === 'issue');
      var isAdj   = (doc === 'adj');
      var isSheet = isRecv || isIssue || isAdj;
      var no  = form.getAttribute('data-bill') || (form.querySelector('input[name="no"]') || {}).value || '';
      var tot = form.getAttribute('data-total') || '';
      var qty = form.getAttribute('data-qty') || '';
      var itm = form.getAttribute('data-items') || '';
      var h = '';
      if (isEdit && isAdj) {
        h += '<p class="cf-lead">ระบบจะ<b>ถอยยอดของใบเดิมกลับเป็นก่อนตรวจนับ</b> '
           + 'แล้ว<b>ดึงจำนวนที่นับไว้กลับเข้าใบ</b>ให้ แก้เฉพาะรายการที่นับผิดแล้วบันทึกใหม่</p>';
      } else if (isEdit && isIssue) {
        h += '<p class="cf-lead">เอกสารที่บันทึกแล้วแก้ตัวเลขทับไม่ได้ ระบบจะ<b>คืนยอดของใบเดิมเข้าสต๊อก</b> '
           + 'แล้ว<b>ดึงรายการและเหตุผลเดิมกลับเข้าใบ</b>ให้ '
           + 'แก้เฉพาะส่วนที่ผิดแล้วบันทึกใหม่ ระบบจะออกเลขใบใหม่ให้เอง</p>';
      } else if (isEdit) {
        h += isRecv
           ? '<p class="cf-lead">เอกสารที่บันทึกแล้วแก้ตัวเลขทับไม่ได้ ระบบจะ<b>ถอนยอดของใบเดิมออกจากสต๊อก</b> '
             + 'แล้ว<b>ดึงรายการทั้งหมดกลับเข้าใบ</b>ให้ ไม่ต้องเลือกสินค้าใหม่ '
             + 'แก้เฉพาะแถวที่ผิดแล้วบันทึกใหม่ ระบบจะออกเลขใบใหม่ให้เอง</p>'
           : '<p class="cf-lead">บิลที่ออกไปแล้วแก้ตัวเลขทับไม่ได้ ระบบจะ<b>ยกเลิกใบเดิม</b> '
             + 'แล้ว<b>ดึงรายการทั้งหมดกลับเข้าตะกร้า</b>ให้ ไม่ต้องยิงหรือกรอกใหม่ '
             + 'แก้เฉพาะแถวที่ผิดแล้วรับเงินใหม่ ระบบจะออกเลขบิลใหม่ให้เอง</p>';
      }
      h += '<div class="cf-sum">'
            + '<div class="cf-r"><span>' + (isEdit ? (isSheet ? 'ใบเดิม' : 'บิลเดิม')
                                                    : (isSheet ? 'ใบที่จะยกเลิก' : 'บิลที่จะยกเลิก'))
            + '</span><b>' + esc(no) + '</b></div>';
      if (tot && !isSheet) { h += '<div class="cf-r"><span>ยอดบิล</span><b class="num">' + esc(tot) + ' บาท</b></div>'; }
      if (isEdit && itm) {
        h += '<div class="cf-r cf-big"><span>' + (isSheet ? 'จะดึงกลับเข้าใบ' : 'จะดึงกลับเข้าตะกร้า')
           + '</span><b class="num">' + esc(itm) + ' รายการ</b></div>';
      } else if (qty) {
        h += '<div class="cf-r cf-warn"><span>' + (isRecv ? 'จะถอนออกจากสต๊อก' : 'ของที่จะคืนเข้าสต๊อก')
           + '</span><b class="num">' + esc(qty) + ' ชิ้น</b></div>';
      }
      h += '</div>'
         + '<div class="cf-note">'
         + '<label for="cf-note-in">หมายเหตุ <i>จำเป็นต้องกรอก</i></label>'
         + '<div class="cf-chips">';
      for (var i = 0; i < REASONS.length; i++) {
        h += '<button type="button" class="cf-chip">' + REASONS[i] + '</button>';
      }
      h += '</div>'
         + '<textarea id="cf-note" rows="2" placeholder="เกิดอะไรขึ้น เช่น กดจำนวนเกินไป 2 ชิ้น"></textarea>'
         + '<span class="cf-need" id="cf-need">ต้องกรอกหมายเหตุก่อนจึงจะยืนยันได้</span>'
         + '</div>';
      return h;
    }

    function askFor(form, go) {
      var kind = form.getAttribute('data-confirm');
      if (kind === 'receive') {
        var lines = form.recvSummary ? form.recvSummary() : [];
        if (!lines.length) { go(); return; }
        var h = '<ul class="cf-items">', tot = 0;
        for (var i = 0; i < lines.length; i++) {
          tot += lines[i].qty;
          h += '<li><span class="n">' + esc(lines[i].name) + '</span>'
             + '<span class="q num">+' + lines[i].qty.toLocaleString('th-TH') + '</span>'
             + '<span class="s">' + esc(lines[i].unit) + '</span></li>';
        }
        h += '</ul><div class="cf-sum">'
           + '<div class="cf-r"><span>เอกสารอ้างอิง</span><b>'
           + esc(form.querySelector('#ref').value.trim() || '—') + '</b></div>'
           + '<div class="cf-r cf-big"><span>รวมที่รับเข้า</span><b class="num">'
           + tot.toLocaleString('th-TH') + ' ชิ้น</b></div></div>';
        openModal({
          title: 'ยืนยันการรับเข้า', sub: 'บันทึกแล้วสต๊อกจะเพิ่มทันที แก้ไขได้ด้วยการปรับยอดเท่านั้น',
          ok: 'ยืนยัน บันทึกรับเข้า', html: h, form: form, go: go
        });
      } else if (kind === 'issue') {
        var sm = form.issueSummary ? form.issueSummary() : { lines: [] };
        if (!sm.lines.length) { go(); return; }
        var hi = '<ul class="cf-items">', tq = 0;
        for (var j = 0; j < sm.lines.length; j++) {
          tq += sm.lines[j].qty;
          hi += '<li><span class="n">' + esc(sm.lines[j].name) + '</span>'
              + '<span class="q num">−' + sm.lines[j].qty.toLocaleString('th-TH') + '</span>'
              + '<span class="s">' + esc(sm.lines[j].unit) + '</span></li>';
        }
        hi += '</ul><div class="cf-sum">'
            + '<div class="cf-r"><span>เหตุผล</span><b>' + esc(sm.reason) + '</b></div>'
            + (sm.cost ? '<div class="cf-r"><span>มูลค่าต้นทุน</span><b class="num">' + esc(sm.cost) + '</b></div>' : '')
            + '<div class="cf-r cf-big cf-warn"><span>รวมที่ตัดออก</span><b class="num">'
            + tq.toLocaleString('th-TH') + ' ชิ้น</b></div></div>';
        openModal({
          title: 'ยืนยันการตัดออกจากสต๊อก', sub: 'บันทึกแล้วสต๊อกจะลดทันที ถ้าผิดยกเลิกใบได้ภายหลัง',
          ok: 'ยืนยัน ตัดออก', html: hi, form: form, go: go
        });
      } else if (kind === 'adjust') {
        var sa = form.adjSummary ? form.adjSummary() : { lines: [] };
        if (!sa.lines.length) { go(); return; }
        var ha = '<ul class="cf-items cf-adj">', bad = 0;
        for (var k = 0; k < sa.lines.length; k++) {
          var L = sa.lines[k];
          if (L.diff !== 0) { bad++; }
          ha += '<li><span class="n">' + esc(L.name) + '<small>ในระบบ ' + esc(L.have) + ' → นับได้ ' + esc(L.cnt) + ' ' + esc(L.unit) + '</small></span>'
              + '<span class="q num ' + (L.diff > 0 ? 'up' : (L.diff < 0 ? 'dn' : '')) + '">'
              + (L.diff === 0 ? 'ตรง' : (L.diff > 0 ? '+' : '−') + Math.abs(L.diff).toLocaleString('th-TH')) + '</span></li>';
        }
        ha += '</ul><div class="cf-sum">'
            + (bad ? '<div class="cf-r"><span>สาเหตุ</span><b>' + esc(sa.reason) + '</b></div>'
                   + '<div class="cf-r cf-big cf-warn"><span>มูลค่าส่วนต่าง</span><b class="num">' + esc(sa.value) + '</b></div>'
                   : '<div class="cf-r cf-big"><span>ผลการนับ</span><b>ตรงกับระบบทุกรายการ</b></div>')
            + '</div>';
        openModal({
          title: bad ? 'ยืนยันการปรับยอดสต๊อก' : 'ยืนยันผลการนับ',
          sub:   bad ? 'ยอดในระบบของ ' + bad + ' รายการจะถูกปรับให้เท่ากับที่นับได้ทันที'
                     : 'บันทึกไว้เป็นหลักฐานว่านับแล้วตรง ไม่มีการเปลี่ยนยอด',
          ok: bad ? 'ยืนยัน ปรับยอด' : 'ยืนยัน บันทึก', html: ha, form: form, go: go
        });
      } else if (kind === 'edit' || kind === 'void') {
        /* ดูจากปลายทางของฟอร์มว่าเป็นเอกสารชนิดไหน จะได้ใช้ถ้อยคำให้ถูก */
        var act = form.getAttribute('action') || '';
        var doc = form.getAttribute('data-doc') ? form.getAttribute('data-doc')
                : act.indexOf('receive.php') === 0 ? 'recv'
                : act.indexOf('issue.php') === 0 ? 'issue'
                : act.indexOf('stocktake.php') === 0 ? 'adj' : 'bill';
        var T = {
          recv:  { e: 'แก้ไขใบรับเข้า', es: 'ถอนยอดของใบเดิมออกจากสต๊อก แล้วเปิดรายการเดิมขึ้นมาแก้',
                   v: 'ยืนยันการยกเลิกใบรับเข้า', vs: 'ยอดที่รับเข้าไว้จะถูกถอนออกจากสต๊อก และบันทึกไว้ในประวัติ',
                   ok: 'ยืนยัน ยกเลิกใบนี้' },
          issue: { e: 'แก้ไขใบตัดออก', es: 'คืนยอดของใบเดิมเข้าสต๊อก แล้วเปิดรายการเดิมขึ้นมาแก้',
                   v: 'ยืนยันการยกเลิกใบตัดออก', vs: 'ของที่ตัดไว้จะถูกคืนเข้าสต๊อก และบันทึกไว้ในประวัติ',
                   ok: 'ยืนยัน ยกเลิกใบนี้' },
          adj:   { e: 'แก้ไขใบตรวจนับ', es: 'ถอยยอดของใบเดิม แล้วเปิดผลการนับเดิมขึ้นมาแก้',
                   v: 'ยืนยันการยกเลิกใบตรวจนับ', vs: 'ยอดทุกรายการในใบจะกลับไปเป็นก่อนตรวจนับ และบันทึกไว้ในประวัติ',
                   ok: 'ยืนยัน ยกเลิกใบนี้' },
          bill:  { e: 'แก้ไขบิล', es: 'ยกเลิกใบเดิมแล้วเปิดรายการเดิมขึ้นมาแก้',
                   v: 'ยืนยันการยกเลิกบิล', vs: 'ของจะถูกคืนเข้าสต๊อก และบันทึกไว้ในประวัติ',
                   ok: 'ยืนยัน ยกเลิกบิล' }
        }[doc];
        if (kind === 'edit') {
          openModal({
            title: T.e, sub: T.es,
            ok: 'ยืนยัน เปิดขึ้นมาแก้', html: voidHtml(form, true, doc), form: form, go: go
          });
        } else {
          openModal({
            title: T.v, sub: T.vs, ok: T.ok,
            html: voidHtml(form, false, doc), form: form, go: go
          });
        }
      } else {
        if (!document.querySelector('.cart-list li')) { go(); return; }
        openModal({
          title: 'ยืนยันการรับเงิน', sub: 'ตรวจให้แน่ใจก่อน บันทึกแล้วจะตัดสต๊อกทันที',
          ok: 'ยืนยัน บันทึกบิล', html: payHtml(form), form: form, go: go
        });
      }
      lastEl = document.activeElement;
    }

    /* ฟอร์มที่ใช้ htmx */
    document.body.addEventListener('htmx:confirm', function (ev) {
      var el = ev.detail.elt;
      if (!el || !el.getAttribute || !el.getAttribute('data-confirm')) { return; }
      ev.preventDefault();
      askFor(el, function () { ev.detail.issueRequest(true); });
    });

    /* ฟอร์มธรรมดา (ไม่มี hx-post) */
    document.body.addEventListener('submit', function (ev) {
      var f = ev.target;
      if (!f.getAttribute || !f.getAttribute('data-confirm')) { return; }
      if (f.hasAttribute('hx-post') || f.getAttribute('data-ok') === '1') { return; }
      ev.preventDefault();
      askFor(f, function () { f.setAttribute('data-ok', '1'); f.submit(); });
    }, false);
  }

  /* ทูลทิปของกราฟ */
  var chart = document.querySelector('[data-chart]');
  if (chart) {
    var tip = chart.querySelector('.chart-tip');
    chart.querySelectorAll('.bar-hit').forEach(function (hit) {
      function show() {
        var tb = document.createElement('b'), ts = document.createElement('span');     // textContent — ไม่ประกอบ HTML จากค่าในหน้า
        tb.textContent = hit.dataset.value + ' บาท';
        ts.textContent = hit.dataset.month;
        tip.textContent = '';
        tip.appendChild(tb);
        tip.appendChild(ts);
        var cb = chart.getBoundingClientRect();
        var hb = hit.getBoundingClientRect();
        tip.style.left = (hb.left - cb.left + hb.width / 2) + 'px';
        tip.style.top  = (hb.top  - cb.top) + 'px';
        tip.style.opacity = '1';
      }
      function hide() { tip.style.opacity = '0'; }
      hit.addEventListener('mouseenter', show);
      hit.addEventListener('focus', show);
      hit.addEventListener('mouseleave', hide);
      hit.addEventListener('blur', hide);
    });
  }
})();
</script>
<script>
/* แอคคอร์เดียน (.acc-item) — เปิดทีละอัน · ลิงก์ที่มี #id ของอันไหน ให้เปิดอันนั้นแล้วเลื่อนไปหา */
(function () {
  var items = document.querySelectorAll('details.acc-item');
  if (!items.length) { return; }
  function only(el) {
    for (var j = 0; j < items.length; j++) {
      if (items[j] !== el && items[j].open) { items[j].open = false; }
    }
  }
  var ready = false;                       // toggle ของอันที่เปิดไว้ตั้งแต่โหลดหน้า ไม่ต้องปิดอันอื่น (เช่นผลค้นหาหลายหมวด)
  window.addEventListener('load', function () { setTimeout(function () { ready = true; }, 0); });
  for (var i = 0; i < items.length; i++) {
    items[i].addEventListener('toggle', function () { if (ready && this.open) { only(this); } });
  }
  var h = location.hash ? document.getElementById(location.hash.slice(1)) : null;
  if (h && h.classList.contains('acc-item')) {
    h.open = true;
    only(h);
    h.scrollIntoView({ block: 'start' });
  }
})();
</script>
<script>
/* ช่องติ๊กสิทธิ์ (perm_boxes · ช่วงที่ 11) — สิทธิ์ที่มี data-needs="a b" ใช้ได้เมื่อติ๊ก a หรือ b อย่างน้อยตัวหนึ่ง
   ตัวหลักถูกเอาออก → ตัวที่พ่วงหลุดและกดไม่ได้ (ฝั่งเซิร์ฟเวอร์ตัดซ้ำอีกชั้นใน read_perms) */
(function () {
  var deps = document.querySelectorAll('input[name="perms[]"][data-needs]');
  if (!deps.length) { return; }
  function sync(form) {
    for (var i = 0; i < deps.length; i++) {
      var d = deps[i];
      if (d.form !== form) { continue; }
      var need = d.getAttribute('data-needs').split(' '), ok = false;
      for (var j = 0; j < need.length; j++) {
        var p = form.querySelector('input[name="perms[]"][value="' + need[j] + '"]');
        if (p && p.checked) { ok = true; }
      }
      if (!ok) { d.checked = false; }
      d.disabled = !ok;
      d.closest('.perm-o').classList.toggle('is-off', !ok);
    }
  }
  var forms = [];
  for (var i = 0; i < deps.length; i++) {
    if (deps[i].form && forms.indexOf(deps[i].form) < 0) { forms.push(deps[i].form); }
  }
  forms.forEach(function (f) {
    f.addEventListener('change', function (ev) {
      if (ev.target.name === 'perms[]') { sync(f); }
    });
    sync(f);
  });
})();
</script>
<?php if (strpos($NAV_ACTIVE, 'adm-report') === 0) { ?>
<!-- ===== popup รายละเอียดยอดขายของวัน — ปุ่มที่มี data-day-sales="สาขา|ปปปปดดวว" ===== -->
<dialog class="ds-modal" id="ds-modal" aria-label="รายละเอียดยอดขาย">
  <button type="button" class="icon-btn ds-close" data-ds-close aria-label="ปิด"><svg class="ico"><use href="#i-x"/></svg></button>
  <div class="ds-body" id="ds-body"></div>
</dialog>
<script>
(function () {
  var dlg = document.getElementById('ds-modal'), body = document.getElementById('ds-body');
  if (!dlg || !dlg.showModal) { return; }
  document.addEventListener('click', function (ev) {
    var t = ev.target.closest ? ev.target.closest('[data-day-sales]') : null;
    if (t) {
      var p = t.getAttribute('data-day-sales').split('|');
      body.innerHTML = '<p class="empty">กำลังโหลด…</p>';
      dlg.showModal();
      fetch('adm-day-sales.php?b=' + encodeURIComponent(p[0]) + '&d=' + encodeURIComponent(p[1]), { credentials: 'same-origin' })
        .then(function (r) { return r.text(); })
        .then(function (h) { body.innerHTML = h; })
        .catch(function () { body.innerHTML = '<p class="empty">โหลดไม่สำเร็จ ลองใหม่อีกครั้ง</p>'; });
      return;
    }
    var tab = ev.target.closest ? ev.target.closest('[data-ds-tab]') : null;
    if (tab) {
      var k = tab.getAttribute('data-ds-tab');
      body.querySelectorAll('[data-ds-tab]').forEach(function (b) { b.classList.toggle('on', b === tab); b.setAttribute('aria-selected', b === tab); });
      body.querySelectorAll('[data-ds-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-ds-pane') !== k; });
      return;
    }
    if (ev.target.closest && ev.target.closest('[data-ds-close]')) { dlg.close(); }
    if (ev.target === dlg) { dlg.close(); }              // กดพื้นหลังเพื่อปิด
  });
})();
</script>
<?php } ?>
</body>
</html>
