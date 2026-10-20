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
 *   - [x] ผลทดสอบ 7 ต.ค. (S2): เปิดเมนูบนมือถือแล้วล็อกการเลื่อนหน้าด้านหลัง (html.nav-lock)
 *   - [x] 8 ต.ค.: เมนูแบบย่อเลื่อนได้ — ป้ายชื่อเมนูตอนชี้เมาส์ย้ายจาก CSS ::after มาเป็นกล่อง .rail-tip (position:fixed)
 *   - [x] 8 ต.ค.: ปุ่มบันทึกที่ยังกรอกไม่ครบ (button[data-need] + aoNeedSync) — กดแล้วเลื่อนไปช่องที่ขาด กะพริบ + ข้อความ แทนปุ่ม disabled ที่กดแล้วเงียบ
 *   - [x] 8 ต.ค.: คอลัมน์ขวา (.sale-cart) สูงเกินจอ → ค้างที่ขอบล่างแทน (--cart-top · aoCartFit) ปุ่มรับเงิน / ช่องหมายเหตุไม่จมใต้ขอบจอ
 *   - [x] 12 ต.ค.: กล่องยืนยันการรับเงินแสดง “รับมา” มีสตางค์ได้ · ยกเลิกใบตรวจนับบอก “ส่วนต่างที่จะปรับกลับเป็นก่อนนับ” (เดิมใช้คำของใบตัดออก)
 *   - [x] 14 ต.ค.: ตัวโหลดกลางจอ (#ld-busy) ขึ้นเมื่อรอ htmx / เปลี่ยนหน้าเกิน 0.3 วินาที ไม่บังการแตะ · เน็ตหลุด / เซิร์ฟเวอร์ตอบผิดพลาด / รอเกิน 30 วินาที ขึ้นข้อความล่างจอ (#ld-err)
 *   - [x] 14 ต.ค. (ผู้ใช้ขอ): กล่องโหลดขึ้นแล้วจอเป็นสีเทาและกดอะไรไม่ได้จนโหลดเสร็จ (เดิมแตะทะลุได้) — สไตล์ .ld-busy ใน assets/app.css
 *   - [x] 14 ต.ค. (ผู้ใช้เลือก): + / − นับในเครื่องก่อนแล้วบันทึกเบื้องหลัง (data-step · input[data-qty]) — เดิมกดรัว ๆ แล้วหาย · ไม่บังจอตอนบันทึกจำนวน (data-no-busy)
 *         · กดรับเงิน / บันทึก ([data-qty-guard]) ตอนยังส่งไม่ครบ = ส่งก่อนแล้วกดให้เอง · ตะกร้าวาดใหม่แล้วเงินที่พิมพ์ในช่องรับเงินไม่หาย
 *   - [x] 14 ต.ค. (ผู้ใช้เลือก): แตะสินค้าตัวใหม่ขึ้นตะกร้า / ใบทันที (addRow จากแม่แบบ #pick-row) แล้วบันทึกเบื้องหลังแบบ + / −
 *   - [x] 15 ต.ค. (ใบนับแบบรายการ ช่วง 5): เปลี่ยนชื่อ “ตรวจนับ / ปรับยอด” เป็น “ตรวจนับสต๊อก” (กล่องยืนยัน) · กล่องยืนยันรับเข้าบอก “ถ้าผิดยกเลิกใบได้ภายหลัง”
 *   - [x] 15 ต.ค. (ผู้ใช้เลือก): ตรวจนับกลับเป็นหน้าการ์ดแบบเดิม — กล่องยืนยันแสดงทุกแถวเหมือนเดิม (ใบนับแบบตาราง ช่วง 2–3 ถอดออก)
 *   - [x] 15 ต.ค. (เมนูประวัติแยกหน้า ช่วง 2): หน้าที่ตั้ง $CONFIRM_MODAL ได้กล่องยืนยันด้วย (รายละเอียดวันของผู้ดูแล — เมนูที่เขียวเปลี่ยนตามกลุ่มแล้ว)
 */
if (!defined('ALLOW_DIRECT_ACCESS')) {
    http_response_code(403);
    exit('403 Forbidden');
}
require_once dirname(__FILE__) . '/../include/function.php'; ?>
    </main><!-- /.page -->
  </div><!-- /.main -->
</div><!-- /.app -->

<?php if (in_array($NAV_ACTIVE, array('sale.php', 'history.php', 'receive.php', 'issue.php', 'stocktake.php'), true) || !empty($CONFIRM_MODAL)) { ?>
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

<!-- ===== กำลังโหลด: กล่องกลางจอระหว่างรอ (แตะปุ่ม / สินค้า และเปลี่ยนหน้า) — ขึ้นเมื่อรอเกิน 0.3 วินาที จอเป็นสีเทา กดอะไรไม่ได้จนเสร็จ ===== -->
<div class="ld-busy" id="ld-busy" role="status" aria-live="polite" hidden>
  <div class="ld-box">
    <span class="lg-spin" aria-hidden="true"></span>
    <b>กำลังโหลด…</b>
  </div>
</div>
<div class="alert alert-error ld-err" id="ld-err" role="alert" hidden>
  <svg class="ico"><use href="#i-info"/></svg>
  <span id="ld-err-t"></span>
  <button class="icon-btn" type="button" id="ld-err-x" aria-label="ปิด"><svg class="ico"><use href="#i-x"/></svg></button>
</div>

<!-- htmx: สลับเฉพาะส่วนที่เปลี่ยน ไม่ต้องโหลดหน้าใหม่ (เก็บไฟล์ไว้ในเครื่อง ไม่พึ่ง CDN) -->
<script src="<?php echo e(asset_url('htmx.min.js')) ?>"></script>
<script>
/* ===== กำลังโหลด + แจ้งเมื่อทำรายการไม่สำเร็จ =====
   - htmx: จำคำขอที่ค้างอยู่ (xhr) ซ่อนเมื่อทุกคำขอวาดเสร็จ (afterSettle) — ไม่ใช่ตอนได้คำตอบ (afterRequest) เพราะส่วนที่ได้มาใหญ่
     จอยังค้างวาดอยู่อีกพัก · ไม่สำเร็จ = ซ่อนทันที · สำเร็จแต่ไม่มีการสลับเนื้อหา = ซ่อนใน 0.4 วินาที
     เน็ตหลุด / เซิร์ฟเวอร์ตอบผิดพลาด / รอเกิน 30 วินาที → ขึ้นข้อความล่างจอ
   - เปลี่ยนหน้า: beforeunload ครอบทุกทาง (ลิงก์ ฟอร์ม และ this.form.submit()) · ลิงก์ดาวน์โหลด (a[download]) ไม่นับ
   - กลับมาด้วยปุ่มย้อนกลับ (pageshow) → ซ่อน */
(function () {
  var box = document.getElementById('ld-busy'), err = document.getElementById('ld-err'), errT = document.getElementById('ld-err-t');
  if (!box) { return; }
  var live = [], tm = null, errTm = null, noBusy = false;
  function show() {
    if (tm || !box.hidden) { return; }
    tm = setTimeout(function () { tm = null; box.hidden = false; }, 300);
  }
  function hide() {
    if (tm) { clearTimeout(tm); tm = null; }
    box.hidden = true;
  }
  function fail(msg) {
    errT.textContent = msg;
    err.hidden = false;
    if (errTm) { clearTimeout(errTm); }
    errTm = setTimeout(function () { err.hidden = true; }, 8000);
  }
  document.getElementById('ld-err-x').addEventListener('click', function () { err.hidden = true; });
  if (window.htmx) {
    htmx.config.timeout = 30000;
    /* คำตอบที่มาหลังตะกร้า / ใบถูกวาดใหม่แล้ว → ลงตัวที่อยู่บนจอ (id เดียวกัน) แทนตัวเก่าที่หลุดจากหน้า · ใช้กับปลายทางที่มี hx-ext="ao-live" */
    htmx.defineExtension('ao-live', {
      onEvent: function (name, ev) {
        var t = ev.detail && ev.detail.target;
        if (name === 'htmx:beforeSwap' && t && t.id && !document.body.contains(t) && document.getElementById(t.id)) {
          ev.detail.target = document.getElementById(t.id);
        }
      }
    });
  }
  function done(x) {
    var i = live.indexOf(x);
    if (i !== -1) { live.splice(i, 1); }
    if (!live.length) { hide(); }
  }
  document.body.addEventListener('htmx:beforeRequest', function (e) {
    if (e.detail.elt && e.detail.elt.closest && e.detail.elt.closest('[data-no-busy]')) { return; }   // บันทึกจำนวนเบื้องหลัง (+ / −) ไม่บังจอ
    var x = e.detail.xhr;
    live.push(x); err.hidden = true; show();
    /* ตัวสำรองจากตัวคำขอเอง — htmx:afterRequest ยิงที่ element เดิม ถ้าถูกวาดทับไปแล้วจะไม่ถึง body */
    if (x && x.addEventListener) {
      x.addEventListener('loadend', function () {
        if (x.status >= 200 && x.status < 300) { setTimeout(function () { done(x); }, 400); } else { done(x); }
      });
    }
  });
  document.body.addEventListener('htmx:afterSettle', function (e) { done(e.detail.xhr); });
  document.body.addEventListener('htmx:sendError', function () { fail('เชื่อมต่อไม่สำเร็จ — ตรวจอินเทอร์เน็ตแล้วแตะใหม่อีกครั้ง'); });
  document.body.addEventListener('htmx:timeout', function () { fail('ระบบตอบช้าเกินไป — แตะใหม่อีกครั้ง'); });
  document.body.addEventListener('htmx:responseError', function (e) {
    fail('ทำรายการไม่สำเร็จ (รหัส ' + e.detail.xhr.status + ') — แตะใหม่อีกครั้ง ถ้ายังเป็นให้กลับหน้าแรกแล้วลองใหม่');
  });
  document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a[download]') : null;
    if (a) { noBusy = true; setTimeout(function () { noBusy = false; }, 1000); }
  }, true);
  window.addEventListener('beforeunload', function () { if (!noBusy) { show(); } });
  window.addEventListener('pageshow', function () { live = []; hide(); });
})();

/* ===== + / − นับในเครื่องก่อน แล้วค่อยบันทึกเบื้องหลัง (ผู้ใช้เลือก 14 ต.ค.) =====
   เดิมทุกครั้งที่กด + / − ต้องรอเซิร์ฟเวอร์ตอบก่อนตัวเลขจะเปลี่ยน และระหว่างรอ htmx เก็บไว้แค่การกดครั้งสุดท้าย
   (เน็ตไป-กลับ 0.25 วินาที กดรัว ๆ 5 ครั้ง ได้แค่ 2)
   - ปุ่ม [data-step] ในแถวตะกร้า / ใบ → ตัวเลขในช่อง input[data-qty] เปลี่ยนทันที (ไม่ต่ำกว่า data-min · ไม่เกิน data-max)
   - แตะการ์ดสินค้าที่อยู่ในตะกร้า / ใบแล้ว (ช่องมี data-tile) = กด + ของแถวนั้น · ป้ายจำนวนบนการ์ดเปลี่ยนตาม
   - หยุดกด 0.4 วินาที → ส่ง “จำนวน = N” ครั้งเดียว (change ของช่อง → act=set) · ไม่ขึ้นกล่องโหลด (data-no-busy)
   - ส่งไปแล้วกดต่อ → คำตอบที่มาทีหลังไม่ทับตัวเลขใหม่ (จำค่าที่ต้องการใน want แล้วส่งซ้ำ) · เซิร์ฟเวอร์ปรับลง (เกินของที่มี) = ใช้ค่าของเซิร์ฟเวอร์
   - ตรวจนับ: นับเกิน / ขาดคิดจากตัวเลขในช่องทันที → กล่องสาเหตุขึ้น / หายทันที (adjSync)
   - กดรับเงิน / บันทึกใบ ([data-qty-guard]) ตอนยังส่งไม่ครบ → ส่งที่ค้างทันที รอคำตอบ แล้วกดปุ่มนั้นให้อีกครั้งเอง
   - ล้างทั้งตะกร้า / ใบ หรือเอาแถวออก → ลืมตัวเลขที่ยังไม่ได้ส่งของแถวนั้น
   - แตะสินค้าตัวใหม่ → สร้างแถวจากแม่แบบ #pick-row + data-pick ของการ์ดทันที แล้วส่งแบบเดียวกับ + / − (fresh = แถวที่เซิร์ฟเวอร์ยังไม่มี)
   - พิมพ์จำนวนเอง = แบบเดียวกับ + / − (หยุดพิมพ์ 0.7 วินาทีแล้วส่ง) · ใบวาดใหม่ระหว่างพิมพ์ → ช่องใหม่ได้โฟกัสต่อ */
(function () {
  if (!window.htmx) { return; }
  var want = {}, sent = {}, timer = null, inflight = 0, again = '', fresh = {}, pend = {}, wait = 400, typing = null;
  var esc = function (s) { return (window.CSS && CSS.escape) ? CSS.escape(s) : String(s).replace(/["\\]/g, '\\$&'); };
  function field(sku) { return document.querySelector('input[data-qty][data-sku="' + esc(sku) + '"]'); }
  var commas = function (s) { return s.replace(/\B(?=(\d{3})+(?!\d))/g, ','); };
  /* สร้างแถวของสินค้าที่ยังไม่อยู่ในตะกร้า / ใบ — คืนช่องจำนวน หรือ null ถ้าหน้านี้ไม่มีแม่แบบ */
  function addRow(sku) {
    var tpl = document.getElementById('pick-row'), d = fresh[sku];
    if (!tpl || !d) { return null; }
    var box = tpl.parentNode, list = box.querySelector('.cart-list'), empty = box.querySelector('.empty-cart');
    if (!list && !empty) { return null; }
    var ul = tpl.content.firstElementChild.cloneNode(true), li = ul.firstElementChild;
    var js = JSON.stringify(sku).slice(1, -1);
    var put = function (s, json) { return s.split('{sku}').join(json ? js : sku).split('{name}').join(d.name).split('{unit}').join(d.unit); };
    [li].concat([].slice.call(li.querySelectorAll('*'))).forEach(function (el) {
      for (var k = 0; k < el.attributes.length; k++) {
        if (el.attributes[k].value.indexOf('{') !== -1) { el.attributes[k].value = put(el.attributes[k].value, el.attributes[k].name === 'hx-vals'); }
      }
    });
    var w = document.createTreeWalker(li, NodeFilter.SHOW_TEXT), n;
    while ((n = w.nextNode())) { if (n.nodeValue.indexOf('{') !== -1) { n.nodeValue = put(n.nodeValue, false); } }
    var have = d.have || 0, dir = parseInt(tpl.getAttribute('data-dir') || '0', 10), price = commas((d.price || 0).toFixed(2));
    var val = { price: price, qty: '1', sum: price, have: commas(String(have)), after: commas(String(have + dir)) };
    li.querySelectorAll('[data-f]').forEach(function (s) { s.textContent = val[s.getAttribute('data-f')]; });
    var inp = li.querySelector('input[data-qty]');
    inp.value = '0';
    inp.setAttribute('data-max', d.max);
    li.setAttribute('data-fresh', '');
    if (list) { list.appendChild(li); } else { empty.parentNode.replaceChild(ul, empty); }
    htmx.process(li);
    if (want[sku] !== undefined) { paint(inp, want[sku]); }
    if (window.recvSync) { window.recvSync(); }        // แถบล่างหน้านำเข้า / ตัดออกนับแถวใหม่ทันที
    if (window.issueSync) { window.issueSync(); }
    return inp;
  }
  /* ตะกร้าวาดใหม่ก่อนเซิร์ฟเวอร์รับแถวใหม่ → ใส่แถวกลับ · เซิร์ฟเวอร์มีแถวนั้นแล้ว → เลิกจำ */
  function keepFresh() {
    Object.keys(fresh).forEach(function (sku) {
      var i = field(sku);
      if (i) { if (!i.closest('[data-fresh]')) { delete fresh[sku]; } return; }
      if (want[sku] > 0 && (pend[sku] > 0 || sent[sku] !== want[sku])) { addRow(sku); }
    });
  }
  function dirty() { return timer !== null || inflight > 0 || Object.keys(want).length > 0; }
  function bounds(inp) {
    return [parseInt(inp.getAttribute('data-min') || '0', 10),
            inp.hasAttribute('data-max') ? parseInt(inp.getAttribute('data-max'), 10) : 99999];
  }
  function tile(sku, v) {
    var t = document.querySelector('.goods [data-sku="' + esc(sku) + '"]');
    var b = t ? (t.matches('button') ? t : t.querySelector('button')) : null;
    if (!b) { return; }
    var g = b.querySelector('.g-bdg');
    if (v > 0) {
      if (!g) { g = document.createElement('i'); g.className = 'g-bdg'; b.insertBefore(g, b.firstChild); }
      g.textContent = v;
      b.classList.add('is-in');
    } else {
      if (g) { g.parentNode.removeChild(g); }
      b.classList.remove('is-in');
    }
  }
  /* ตัวเลขที่คิดจากจำนวนได้ทันที (ยอดเงินรอเซิร์ฟเวอร์ — จางไว้ใน app.css)
     แถว: คงเหลือ → หลังรับเข้า / ตัดออก (data-dir ของ #pick-row) · ราคา × จำนวน (หน้าขาย) · ส่วนต่างที่นับ (ตรวจนับ) */
  var num = function (s) { return parseInt(String(s).replace(/[^\d-]/g, ''), 10) || 0; };
  function rowLocal(li, v) {
    var box = li.closest('.sale-cart'), tpl = box ? box.querySelector('#pick-row') : null;
    var dir = tpl ? parseInt(tpl.getAttribute('data-dir') || '0', 10) : 0;
    var h = li.querySelector('[data-f="have"]'), a = li.querySelector('[data-f="after"]'), q = li.querySelector('[data-f="qty"]');
    if (h && a && dir) {
      var after = num(h.textContent) + dir * v;
      a.textContent = commas(String(after));
      if (dir < 0) { a.classList.toggle('zero', after <= 0); }
    }
    if (q) { q.textContent = commas(String(v)); }
    if (li.hasAttribute('data-have')) {
      var d = v - num(li.getAttribute('data-have')), c = li.querySelector('.dchip'), mv = li.querySelector('.moved');
      if (mv) { mv.parentNode.removeChild(mv); }                 // นับใหม่แล้ว = รับรู้ยอดที่เปลี่ยน (เซิร์ฟเวอร์ได้ seen)
      li.setAttribute('data-diff', d);
      li.classList.toggle('is-eq', d === 0);
      li.classList.toggle('is-diff', d !== 0);
      if (c) {
        c.className = 'dchip ' + (d > 0 ? 'up' : (d < 0 ? 'dn' : 'eq'));
        c.textContent = d > 0 ? '+' + commas(String(d)) : (d < 0 ? '−' + commas(String(-d)) : 'ตรง');
      }
    }
  }
  /* ยอดรวมของใบ: หัวใบ (รายการ · ชิ้น) · กล่องรวมชิ้น · ตรวจนับ = ตรง / เกิน / ขาด + ส่วนต่างสุทธิ · แถบล่างตามด้วย xxxSync */
  function pcs(el, t) {
    var sm = document.createElement('small');
    sm.textContent = 'ชิ้น';
    el.textContent = t;
    el.appendChild(sm);
  }
  function sums(box) {
    if (!box) { return; }
    var rows = box.querySelectorAll('.cart-list li'), n = 0, all = 0, same = 0, over = 0, short = 0, net = 0;
    for (var k = 0; k < rows.length; k++) {
      var i = rows[k].querySelector('input[data-qty]');
      if (!i) { continue; }
      var v = num(i.value);
      n++;
      all += v;
      if (rows[k].hasAttribute('data-have')) {
        var d = v - num(rows[k].getAttribute('data-have'));
        net += d;
        if (d > 0) { over++; } else if (d < 0) { short++; } else { same++; }
      }
    }
    var sub = box.querySelector('.card-head .sub'), adj = box.querySelector('#adj-sum'), tot = box.querySelector('#issue-sum, #recv-sum');
    if (adj) {
      if (sub) { sub.textContent = n + ' รายการ · ตรง ' + same + ' · เกิน ' + over + ' · ขาด ' + short; }
      adj.setAttribute('data-items', n);
      adj.setAttribute('data-over', over);
      adj.setAttribute('data-short', short);
      pcs(adj, net === 0 ? '0' : (net > 0 ? '+' : '−') + commas(String(Math.abs(net))));
      if (window.adjSync) { window.adjSync(); }
      return;
    }
    if (sub) { sub.textContent = n + ' รายการ · ' + commas(String(all)) + ' ชิ้น'; }
    if (tot) {
      tot.setAttribute('data-v', all);
      pcs(tot, commas(String(all)));
      if (tot.id === 'issue-sum' && window.issueSync) { window.issueSync(); }
      if (tot.id === 'recv-sum' && window.recvSync) { window.recvSync(); }
    }
  }
  function paint(inp, v) {
    var b = bounds(inp), li = inp.closest('li'), box = inp.closest('.sale-cart');
    inp.value = v;
    if (li) {
      li.classList.add('is-pending');
      li.querySelectorAll('[data-step]').forEach(function (x) {
        var d = parseInt(x.getAttribute('data-step'), 10) || 0;
        x.disabled = (d < 0) ? v <= b[0] : v >= b[1];
      });
      rowLocal(li, v);
    }
    if (box) { box.classList.add('is-dirty'); sums(box); }
    if (inp.hasAttribute('data-tile')) { tile(inp.getAttribute('data-sku'), v); }
  }
  /* ส่งทีละแถว — ส่งพร้อมกันแล้วคำตอบที่มาทีหลังเป็นของตะกร้าเก่า (ยอดรวมบนจอไม่ตรง) */
  function flush() {
    if (timer) { clearTimeout(timer); timer = null; }
    if (inflight > 0) { return; }                         // รอคำตอบก่อน — afterSettle ตั้งเวลาส่งที่เหลือให้เอง
    var keys = Object.keys(want);
    for (var k = 0; k < keys.length; k++) {
      var sku = keys[k];
      if (sent[sku] === want[sku]) { continue; }          // ส่งค่านี้ไปแล้ว
      var i = field(sku);
      if (!i) { delete want[sku]; continue; }
      i.value = want[sku];
      sent[sku] = want[sku];
      htmx.trigger(i, 'change');
      return;
    }
  }
  var lastTap = 0;
  function schedule() {
    if (timer) { return; }
    timer = setTimeout(flush, Math.max(0, wait - (Date.now() - lastTap)));
  }
  function step(inp, d) {
    var b = bounds(inp);
    var v = parseInt(String(inp.value).replace(/\D/g, ''), 10) || 0;
    var n = Math.max(b[0], Math.min(b[1], v + d));
    if (n === v) { return; }
    want[inp.getAttribute('data-sku')] = n;
    paint(inp, n);
    lastTap = Date.now();
    wait = 400;
    if (timer) { clearTimeout(timer); timer = null; }
    schedule();
  }
  function forget(sku) {
    if (sku === null) { want = {}; sent = {}; fresh = {}; if (timer) { clearTimeout(timer); timer = null; } return; }
    delete want[sku];
    delete sent[sku];
    delete fresh[sku];
  }
  document.addEventListener('click', function (e) {
    if (!e.target.closest) { return; }
    var b = e.target.closest('[data-step]');
    if (b) {
      e.preventDefault();
      e.stopPropagation();
      var li = b.closest('li'), inp = li ? li.querySelector('input[data-qty]') : null;
      if (inp && !b.disabled) { step(inp, parseInt(b.getAttribute('data-step'), 10) || 0); }
      return;
    }
    var t = e.target.closest('.goods [data-sku]');
    if (t) {
      var sk = t.getAttribute('data-sku'), f = field(sk);
      if (!f && t.hasAttribute('data-pick')) {
        try { fresh[sk] = JSON.parse(t.getAttribute('data-pick')); } catch (er) { fresh[sk] = null; }
        f = addRow(sk);
        if (!f) { delete fresh[sk]; }                   // ไม่มีแม่แบบ → ส่งแบบเดิม (รอเซิร์ฟเวอร์)
      }
      if (f && f.hasAttribute('data-tile')) { e.preventDefault(); e.stopPropagation(); step(f, 1); }
      return;
    }
    var x = e.target.closest('.sale-cart .cl-x, .sale-cart .cl-del');
    if (x) {
      var row = x.closest('li'), ri = row ? row.querySelector('input[data-qty]') : null;
      if (ri) { forget(ri.getAttribute('data-sku')); }
      return;
    }
    if (e.target.closest('.sale-cart .card-head')) { forget(null); return; }   // ล้างทั้งตะกร้า / ใบ
    var g = e.target.closest('[data-qty-guard]');
    if (g && dirty()) {
      e.preventDefault();
      e.stopPropagation();
      again = g.getAttribute('data-qty-guard');
      flush();
    }
  }, true);
  /* พิมพ์จำนวนเอง → ใช้ทันที (ปรับเข้าช่วง data-min–max) แล้วส่งเบื้องหลัง · ช่องว่าง = รอพิมพ์ต่อ ไม่ส่ง */
  document.addEventListener('input', function (e) {
    var i = e.target;
    if (!i || !i.matches || !i.matches('input[data-qty]')) { return; }
    var sku = i.getAttribute('data-sku'), s = String(i.value).replace(/\D/g, '');
    if (s === '') { delete want[sku]; return; }
    var b = bounds(i);
    want[sku] = Math.max(b[0], Math.min(b[1], parseInt(s, 10)));
    paint(i, want[sku]);
    lastTap = Date.now();
    wait = 700;
    if (timer) { clearTimeout(timer); timer = null; }
    schedule();
  }, true);
  /* change ของเบราว์เซอร์ (แตะออก / Enter) → ส่งที่ค้างทันทีแทน htmx (กันส่งซ้ำ) · ช่องว่าง = คืนค่าเดิม
     change ที่ flush() สั่งเอง (htmx.trigger) ไม่ใช่ isTrusted → ผ่านไปให้ htmx ตามปกติ */
  document.addEventListener('change', function (e) {
    var i = e.target;
    if (!e.isTrusted || !i || !i.matches || !i.matches('input[data-qty]')) { return; }
    e.stopPropagation();
    var sku = i.getAttribute('data-sku');
    if (want[sku] !== undefined) { flush(); return; }
    i.value = (sent[sku] !== undefined) ? sent[sku] : i.defaultValue;
    var li = i.closest('li'), box = i.closest('.sale-cart');
    if (li) { rowLocal(li, num(i.value)); }
    sums(box);
    if (!pend[sku] && li) { li.classList.remove('is-pending'); }
    if (box && !box.querySelector('li.is-pending')) { box.classList.remove('is-dirty'); }
  }, true);
  /* หน้าขาย: Enter ในช่องจำนวน (ฟอร์ม .cl-set) → ไม่ส่งฟอร์มซ้ำ — change ด้านบนส่งให้แล้ว */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f || !f.matches || !f.matches('form.cl-set')) { return; }
    e.preventDefault();
    e.stopPropagation();
    flush();
  }, true);
  /* นับคำขอที่ค้างจากตัวคำขอเอง (loadend ของ XHR) — htmx:afterRequest ยิงที่ช่องเดิม ซึ่งถูกวาดทับไปแล้ว จึงไม่ถึง body
     (เคยทำให้นับค้างที่ 1 แล้วไม่ส่งครั้งต่อไปเลย) · loadend มาหลังสลับเนื้อหา ก่อน afterSettle */
  document.body.addEventListener('htmx:beforeRequest', function (e) {
    var el = e.detail.elt, x = e.detail.xhr;
    if (!el || !el.closest || !el.closest('[data-no-busy]') || !x) { return; }
    var i = el.matches('input[data-qty]') ? el : el.querySelector('input[data-qty]');
    var sku = i ? i.getAttribute('data-sku') : null;
    inflight++;
    if (sku !== null) { pend[sku] = (pend[sku] || 0) + 1; }
    x.addEventListener('loadend', function () {
      inflight = Math.max(0, inflight - 1);
      if (sku !== null) { pend[sku] = Math.max(0, (pend[sku] || 1) - 1); }
      if (x.status < 200 || x.status >= 300) {            // ส่งไม่สำเร็จ (เน็ตหลุด ฯลฯ — ข้อความแจ้งขึ้นจากตัวโหลด) → ค่านี้ยังไม่ถึงเซิร์ฟเวอร์
        if (sku !== null) { delete sent[sku]; }
        again = '';                                       // ไม่กดรับเงิน / บันทึกให้เอง — กดใหม่แล้วระบบส่งที่ค้างก่อน
      }
    });
  });
  /* หน้าขาย: ตะกร้าวาดใหม่หลังบันทึกจำนวนเบื้องหลัง → เงินที่พิมพ์ไว้ในช่อง “รับเงินมา” (เมื่อพิมพ์เอง) · VAT · วิธีจ่าย ไม่หาย */
  var keep = null;
  document.body.addEventListener('htmx:beforeSwap', function (e) {
    var t = e.detail.target;
    keep = null;
    if (!t || t.id !== 'sale-cart') { return; }
    var rec = document.getElementById('received');
    var pick = function (n) { var r = t.querySelector('input[name="' + n + '"]:checked'); return r ? r.value : null; };
    keep = { rec: (rec && rec.getAttribute('data-touched')) ? rec.value : null, vat: pick('vat'), method: pick('method') };
  });
  function restore() {
    if (!keep) { return; }
    var k = keep, c = document.getElementById('sale-cart');
    keep = null;
    if (!c) { return; }
    ['vat', 'method'].forEach(function (n) {
      var r = k[n] !== null ? c.querySelector('input[name="' + n + '"][value="' + k[n] + '"]') : null;
      if (r && !r.checked && !r.disabled) { r.checked = true; r.dispatchEvent(new Event('change', { bubbles: true })); }   // VAT ที่สาขายังตั้งไม่ครบ = ปิดไว้ ไม่ติ๊กคืน
    });
    var rec = document.getElementById('received');
    if (rec && k.rec !== null) {
      rec.value = k.rec;
      rec.setAttribute('data-touched', '1');
      rec.dispatchEvent(new Event('input', { bubbles: true }));
    }
  }
  document.body.addEventListener('htmx:afterSwap', keepFresh);
  /* ใบวาดใหม่ระหว่างพิมพ์ → ช่องใหม่ได้โฟกัส + ตัวเลขที่พิมพ์ค้าง (ทำในงานเดียวกับการวาด ปุ่มที่กดถัดไปจึงไม่หลุด) */
  document.body.addEventListener('htmx:beforeSwap', function () {
    var a = document.activeElement;
    typing = (a && a.matches && a.matches('input[data-qty]')) ? a.getAttribute('data-sku') : null;
  });
  document.body.addEventListener('htmx:afterSwap', function () {
    var sku = typing, i = (sku !== null) ? field(sku) : null;
    typing = null;
    if (!i || i === document.activeElement) { return; }
    if (want[sku] !== undefined && String(want[sku]) !== i.value) { paint(i, want[sku]); }   // ตรงกับเซิร์ฟเวอร์แล้ว = ไม่ต้องจาง
    i.focus({ preventScroll: true });
    try { i.setSelectionRange(i.value.length, i.value.length); } catch (er) { }
  });
  document.body.addEventListener('htmx:afterSettle', function () {
    restore();
    keepFresh();
    Object.keys(want).forEach(function (sku) {
      var i = field(sku);
      if (!i) { forget(sku); return; }
      if (sent[sku] === want[sku] && inflight === 0) { forget(sku); return; }   // คำตอบของค่าล่าสุดมาแล้ว — ค่าของเซิร์ฟเวอร์คือค่าจริง
      paint(i, want[sku]);                                                    // คำตอบเก่ากว่าที่กดไว้ — ทาตัวเลขที่กดไว้กลับ แล้วส่งใหม่
      if (sent[sku] !== want[sku]) { schedule(); }
    });
    if (again && !dirty()) {
      var g = document.querySelector('[data-qty-guard="' + again + '"]');
      again = '';
      if (g) { g.click(); }
    }
  });
})();
</script>
<script>
/* ===== รูปสินค้า: ย่อในเครื่องก่อนส่ง (ด้านยาวสุด 1200 px · JPG) + แสดงตัวอย่าง — รูปจากมือถือไม่ติดเพดาน 2 MB =====
   ช่อง input[type=file][data-shrink] · data-prev = id ของ <img> ตัวอย่าง · data-note = id ของข้อความใต้ช่อง
   ระหว่างย่อ ปุ่มบันทึกของฟอร์มกดไม่ได้ · เล็กกว่า 400 KB และไม่เกิน 1200 px = ส่งไฟล์เดิม */
(function () {
  var MAX = 1200, KEEP = 400 * 1024, urls = {};
  function kb(n) { return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(n / 1024)) + ' KB'; }
  document.addEventListener('change', function (e) {
    var inp = e.target;
    if (!inp || !inp.matches || !inp.matches('input[type="file"][data-shrink]')) { return; }
    var prev = document.getElementById(inp.getAttribute('data-prev') || ''), note = document.getElementById(inp.getAttribute('data-note') || '');
    var btns = inp.form ? [].slice.call(inp.form.querySelectorAll('button[type="submit"]')) : [];
    var tok = inp.__shrink = (inp.__shrink || 0) + 1;                // เลือกรูปใหม่ระหว่างย่อ = ทิ้งผลของรูปก่อน
    var f = inp.files && inp.files[0];
    function say(t) { if (note) { note.textContent = t; note.hidden = (t === ''); } }
    function busy(on) {
      btns.forEach(function (b) {
        if (on && !b.disabled) { b.disabled = true; b.setAttribute('data-shrinking', ''); }
        if (!on && b.hasAttribute('data-shrinking')) { b.disabled = false; b.removeAttribute('data-shrinking'); }
      });
    }
    function show(blob) {
      if (!prev) { return; }
      if (urls[inp.name]) { URL.revokeObjectURL(urls[inp.name]); }
      urls[inp.name] = blob ? URL.createObjectURL(blob) : '';
      prev.hidden = !blob;
      if (blob) { prev.src = urls[inp.name]; } else { prev.removeAttribute('src'); }
    }
    show(null); say('');
    if (!f) { busy(false); return; }
    if (!/^image\/(jpeg|png|webp)$/.test(f.type)) { inp.value = ''; busy(false); say('ไฟล์นี้ไม่ใช่รูป JPG / PNG / WEBP — เลือกใหม่อีกครั้ง'); return; }
    var img = new Image(), src = URL.createObjectURL(f);
    busy(true); say('กำลังย่อรูป…');
    img.onerror = function () {
      URL.revokeObjectURL(src);
      if (tok !== inp.__shrink) { return; }
      busy(false); inp.value = '';
      say('เปิดรูปนี้ไม่ได้ — ลองรูปอื่น หรือบันทึกเป็น JPG ก่อน');
    };
    img.onload = function () {
      URL.revokeObjectURL(src);
      if (tok !== inp.__shrink) { return; }
      var w = img.naturalWidth, h = img.naturalHeight, k = Math.min(1, MAX / Math.max(w, h));
      if (k === 1 && f.size <= KEEP) { busy(false); show(f); say(''); return; }
      var c = document.createElement('canvas'), g;
      c.width = Math.round(w * k); c.height = Math.round(h * k);
      g = c.getContext('2d');
      g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height);   // พื้นโปร่งใสของ PNG → ขาว
      g.drawImage(img, 0, 0, c.width, c.height);
      c.toBlob(function (b) {
        if (tok !== inp.__shrink) { return; }
        busy(false);
        if (!b || !window.DataTransfer || b.size >= f.size) {        // ย่อไม่ได้ / ไม่เล็กลง → ส่งไฟล์เดิม
          show(f);
          say(f.size > 2 * 1048576 ? 'รูปนี้ ' + kb(f.size) + ' — เบราว์เซอร์นี้ย่อรูปไม่ได้ และเกิน 2 MB จะบันทึกรูปไม่ผ่าน' : '');
          return;
        }
        var dt = new DataTransfer();
        dt.items.add(new File([b], f.name.replace(/\.[^.]*$/, '') + '.jpg', { type: 'image/jpeg' }));
        inp.files = dt.files;
        show(b);
        say('ย่อรูปแล้ว ' + kb(f.size) + ' → ' + kb(b.size) + ' (' + c.width + ' × ' + c.height + ' px)');
      }, 'image/jpeg', 0.85);
    };
    img.src = src;
  }, true);
})();
</script>
<script>
(function () {
  /* เมนูด้านข้างบนจอเล็ก */
  var side = document.getElementById('side');
  var bd   = document.getElementById('side-backdrop');
  var tg   = document.getElementById('side-toggle');
  function setOpen(open) {
    side.classList.toggle('open', open);
    bd.classList.toggle('open', open);
    document.documentElement.classList.toggle('nav-lock', open);     // หน้าด้านหลังไม่เลื่อนตามตอนเลื่อนเมนู (ผลทดสอบ S2)
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

  /* เมนูแบบย่อ: ป้ายชื่อเมนูตอนชี้เมาส์ / โฟกัสด้วยคีย์บอร์ด
     กล่องเดียว position:fixed (.rail-tip) — เมนูเลื่อนได้แล้ว ป้ายแบบ ::after เดิมจะโดนตัดที่ขอบเมนู */
  var tip = document.createElement('div');
  tip.className = 'rail-tip';
  tip.setAttribute('aria-hidden', 'true');
  document.body.appendChild(tip);
  var railHover = window.matchMedia ? window.matchMedia('(hover: hover) and (min-width: 901px)') : null;
  function tipHide() { tip.classList.remove('on'); }
  function tipShow(a) {
    var t = a.getAttribute('data-t');
    if (!t || !app || !app.classList.contains('app--rail') || !railHover || !railHover.matches) { tipHide(); return; }
    var r = a.getBoundingClientRect();
    tip.textContent = t;
    tip.style.left = Math.round(r.right + 10) + 'px';
    tip.style.top = Math.round(r.top + r.height / 2) + 'px';
    tip.classList.add('on');
  }
  side.addEventListener('mouseover', function (ev) {
    var a = ev.target.closest ? ev.target.closest('.side-nav a') : null;
    if (a) { tipShow(a); } else { tipHide(); }
  });
  side.addEventListener('mouseleave', tipHide);
  side.addEventListener('scroll', tipHide, { passive: true });
  side.addEventListener('focusin', function (ev) {
    var a = ev.target.closest ? ev.target.closest('.side-nav a') : null;
    if (a && a.matches && a.matches(':focus-visible')) { tipShow(a); }
  });
  side.addEventListener('focusout', tipHide);
  if (mode) { mode.addEventListener('click', tipHide); }

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
      h += '<div class="cf-r"><span>ประเภทบิล</span><b>' + (vatOn && vatOn.checked && !vatOn.disabled ? 'บิล VAT' : 'บิลธรรมดา (ไม่ VAT)') + '</b></div>';
      if (isCash) {
        h += '<div class="cf-r"><span>รับมา</span><b class="num">'
           + (Number(String(rec.value).replace(/[^0-9.]/g, '')) || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' บาท</b></div>'
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
        /* ใบตรวจนับ: ยกเลิกแล้วยอดกลับเป็นก่อนปรับ (มีทั้งเพิ่มและลด) — qty คือส่วนต่างรวม ไม่ใช่ของที่คืนเข้าสต๊อก (12 ต.ค.) */
        h += '<div class="cf-r cf-warn"><span>' + (isRecv ? 'จะถอนออกจากสต๊อก' : (isAdj ? 'ส่วนต่างที่จะปรับกลับเป็นก่อนนับ' : 'ของที่จะคืนเข้าสต๊อก'))
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
        var ref = form.querySelector('#ref').value.trim(), note = form.querySelector('#note').value.trim();   // ไม่มีเลขเอกสาร = แสดงหมายเหตุแทน
        h += '</ul><div class="cf-sum">'
           + '<div class="cf-r"><span>' + (ref || !note ? 'เอกสารอ้างอิง' : 'หมายเหตุ') + '</span><b>'
           + esc(ref || note || '—') + '</b></div>'
           + '<div class="cf-r cf-big"><span>รวมที่รับเข้า</span><b class="num">'
           + tot.toLocaleString('th-TH') + ' ชิ้น</b></div></div>';
        openModal({
          title: 'ยืนยันการรับเข้า', sub: 'บันทึกแล้วสต๊อกจะเพิ่มทันที ถ้าผิดยกเลิกใบได้ภายหลัง',
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
          title: 'ยืนยันผลการนับ',
          sub:   bad ? 'ยอดในระบบของ ' + bad + ' รายการจะถูกปรับให้เท่ากับที่นับได้ทันที'
                     : 'บันทึกไว้เป็นหลักฐานว่านับแล้วตรง ไม่มีการเปลี่ยนยอด',
          ok: 'ยืนยัน บันทึกผลการนับ', html: ha, form: form, go: go
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
<script>
/* คอลัมน์ขวา (.sale-cart — ตะกร้าหน้าขาย / ใบรายการหน้านำเข้า ตัดออก ตรวจนับ) ค้างที่ขอบบนจอ (sticky) — 8 ต.ค.
   สูงเกินจอเมื่อไร ส่วนล่าง (ปุ่มรับเงิน / ช่องหมายเหตุ) จมใต้ขอบจอหรือแถบสรุปล่าง จนกว่าจะเลื่อนผ่านสินค้าทั้งหมด
   → ตอนสูงเกิน ตั้ง --cart-top ติดลบ: คอลัมน์เลื่อนตามหน้าจนเห็นท้ายคอลัมน์ แล้วค้างไว้เหนือขอบล่าง · พอดีจอ = ค้างขอบบนเหมือนเดิม
   วัดใหม่ทุกครั้งที่ขนาดเปลี่ยน (เพิ่ม / ลบรายการ · htmx สลับเนื้อหา · กล่องสาเหตุโผล่ · หมุนจอ) */
(function () {
  if (!document.querySelector('.sale-cart')) { return; }
  var seen = null, ro = null;
  function fit() {
    var a = document.querySelector('.sale-cart');
    if (!a) { return; }
    a.style.removeProperty('--cart-top');
    var cs = window.getComputedStyle(a);
    if (cs.position === 'sticky') {
      var bar  = document.querySelector('.recv-bar');
      var room = window.innerHeight - (bar ? bar.offsetHeight : 0) - 16;     // ที่ว่างถึงขอบล่าง หรือถึงแถบสรุปล่างจอ
      var h    = a.offsetHeight;
      if ((parseFloat(cs.top) || 0) + h > room) { a.style.setProperty('--cart-top', Math.round(room - h) + 'px'); }
    }
    if (a !== seen && window.ResizeObserver) {                                // htmx สลับเนื้อหา = คอลัมน์ตัวใหม่
      if (ro) { ro.disconnect(); }
      ro = new ResizeObserver(fit);
      ro.observe(a);
      seen = a;
    }
  }
  window.aoCartFit = fit;
  window.addEventListener('resize', fit);
  document.body.addEventListener('htmx:afterSettle', fit);
  fit();
})();
</script>
<script>
/* ปุ่มบันทึกที่ยังกรอกไม่ครบ (8 ต.ค. · ผลทดสอบ: “บอกแค่ใต้ปุ่ม”) — หน้ารับคืน / นำเข้า / เบิก-ตัดออก / ตรวจนับ
   เดิมปุ่มเป็น disabled กดแล้วเงียบ · ตอนนี้ปุ่มดูจาง (aria-disabled="true") แต่กดได้ — กดแล้วไม่ส่งฟอร์ม
   เลื่อนจอไปที่ช่องแรกที่ยังขาด กรอบแดงกะพริบ + ข้อความบอกว่าต้องทำอะไร + วางเคอร์เซอร์ในช่อง
   หน้าที่ใช้: ปุ่มมี data-need · ตั้ง btn.aoNeed = function () { return null (ครบแล้ว) | {
     box: กล่องที่เลื่อนไปและกะพริบ, field: ช่องที่วางเคอร์เซอร์ (ไม่ใส่ได้), msg: ข้อความ (ไม่ใส่ได้),
     after: วางข้อความต่อจากตัวนี้ (ไม่ใส่ = ใต้หัวการ์ด หรือท้ายกล่อง), mouseOnly: true = วางเคอร์เซอร์เฉพาะเครื่องที่ใช้เมาส์ } }
   แล้วเรียก aoNeedSync(btn) ทุกครั้งที่ค่าในฟอร์มเปลี่ยน · ฝั่งเซิร์ฟเวอร์ยังตรวจซ้ำทุกข้อเหมือนเดิม */
(function () {
  var shown = null;                            /* { btn, box, el } กล่องที่ชี้อยู่ตอนนี้ */
  var calm  = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function clear() {
    if (!shown) { return; }
    if (shown.el && shown.el.parentNode) { shown.el.parentNode.removeChild(shown.el); }
    shown.box.classList.remove('need-on', 'need-flash');
    shown = null;
  }

  window.aoNeedSync = function (btn) {
    var n = btn.aoNeed ? btn.aoNeed() : null;
    if (n) { btn.setAttribute('aria-disabled', 'true'); } else { btn.removeAttribute('aria-disabled'); }
    /* กรอกช่องที่ชี้ไว้แล้ว (หรือ htmx สลับกล่องนั้นออกไป) → เอาข้อความกับกรอบแดงออก */
    if (shown && shown.btn === btn && (!n || n.box !== shown.box || !document.body.contains(shown.box))) { clear(); }
  };

  function stickyOf(el) {
    for (var p = el.parentElement; p && p !== document.body; p = p.parentElement) {
      if (window.getComputedStyle(p).position === 'sticky') { return p; }
    }
    return null;
  }

  /* เลื่อนให้กล่อง (รวมข้อความเตือน) อยู่กลางที่ว่างระหว่างแถบหัวจอกับแถบสรุปล่างจอ · สูงกว่าที่ว่าง = ชิดบน
     กล่องในคอลัมน์ที่ติดขอบ (sticky — ใบรายการด้านขวาของหน้าตรวจนับ) ไม่ขยับตามการเลื่อนจนกว่าคอลัมน์จะหลุด
     → ถ้าตอนติดขอบยังจมใต้แถบล่าง เลื่อนจนคอลัมน์หลุดแล้วกล่องโผล่เหนือแถบล่างพอดี */
  function scrollToBox(box, el) {
    var a    = box.getBoundingClientRect(), m = el ? el.getBoundingClientRect() : a;
    var rTop = Math.min(a.top, m.top), rBot = Math.max(a.bottom, m.bottom), h = rBot - rTop;
    var top  = document.querySelector('.top');
    var bar  = document.querySelector('.recv-bar');
    var t    = top ? Math.max(0, top.getBoundingClientRect().bottom) : 0;
    var lim  = window.innerHeight - (bar ? bar.offsetHeight : 0);
    var room = lim - t, dy = null;
    var s    = stickyOf(box);
    if (s) {
      if (rTop >= t && rBot <= lim) { return; }                                        // ติดขอบอยู่และเห็นครบแล้ว
      var sr = s.getBoundingClientRect();
      if ((parseFloat(window.getComputedStyle(s).top) || 0) + (rBot - sr.top) > lim - 16) {   // ตอนติดขอบก็ยังจม
        var p  = s.parentElement;
        var pb = p.getBoundingClientRect().bottom - (parseFloat(window.getComputedStyle(p).paddingBottom) || 0);
        dy = pb - (sr.bottom - rBot) - (lim - 16);
      } else if (rTop >= t) {
        dy = rBot - (lim - 16);                    // ค้างแล้วเห็น — เลื่อนแค่พอให้คอลัมน์ไปค้าง สินค้าทางซ้ายไม่วิ่งไกล
      }
    }
    if (dy === null) { dy = (h > room - 48) ? rTop - t - 16 : rTop - t - (room - h) / 2; }
    if (Math.abs(dy) < 4) { return; }
    try { window.scrollBy({ top: dy, behavior: calm ? 'auto' : 'smooth' }); } catch (e) { window.scrollBy(0, dy); }
  }

  function jump(btn) {
    var n = btn.aoNeed ? btn.aoNeed() : null;
    if (!n || !n.box) { return; }
    clear();
    var box = n.box, el = null;
    if (n.msg) {
      el = document.createElement('p');
      el.className = 'need-msg';
      el.setAttribute('role', 'alert');
      el.innerHTML = '<svg class="ico" aria-hidden="true"><use href="#i-alert"/></svg><span></span>';
      el.lastChild.textContent = n.msg;
      var head = n.after ? null : box.querySelector(':scope > .card-head');
      var ref  = n.after || head;
      if (ref) { ref.parentNode.insertBefore(el, ref.nextSibling); } else { box.appendChild(el); }
    }
    shown = { btn: btn, box: box, el: el };
    box.classList.add('need-on');
    box.classList.remove('need-flash'); void box.offsetWidth; box.classList.add('need-flash');
    if (window.aoCartFit) { window.aoCartFit(); }            // ข้อความที่เพิ่งใส่ทำให้คอลัมน์ขวาสูงขึ้น — วัดใหม่ก่อนคำนวณการเลื่อน
    scrollToBox(box, el);
    var fine = !window.matchMedia || window.matchMedia('(pointer: fine)').matches;
    if (n.field && (fine || !n.mouseOnly)) {
      try { n.field.focus({ preventScroll: true }); } catch (e) { n.field.focus(); }
    }
  }

  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest ? ev.target.closest('button[data-need][aria-disabled="true"]') : null;
    if (!btn) { return; }
    ev.preventDefault();
    ev.stopPropagation();
    /* กด Enter ในช่องกรอก (เช่น ยิงบาร์โค้ดในช่องค้นหา) เบราว์เซอร์จะกดปุ่มบันทึกให้เอง — แค่ไม่ส่ง ไม่เลื่อนจอ */
    var a = document.activeElement;
    if (ev.detail === 0 && a && a !== btn && a !== document.body) { return; }
    jump(btn);
  }, true);

  /* กันอีกชั้น: ฟอร์มที่ปุ่มบันทึกยังจางอยู่ไม่ส่ง (ก่อนถึงกล่องยืนยันใน data-confirm) */
  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (f && f.querySelector && f.querySelector('button[data-need][aria-disabled="true"]')) {
      ev.preventDefault();
      ev.stopPropagation();
    }
  }, true);

  var btns = document.querySelectorAll('button[data-need]');
  for (var i = 0; i < btns.length; i++) { window.aoNeedSync(btns[i]); }
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
