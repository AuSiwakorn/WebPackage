# เงินโอนของสาขา (จ่ายผสม · ทอนเป็นโอน · บัญชีโอนเหมือนลิ้นชักที่สอง) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** บิลรับเงินสด + โอนได้ ทอนเป็นโอนได้ ยกเลิกบิล / รับคืนเลือกช่องทางคืนเงินได้ และแต่ละสาขานับ “บัญชีโอน” ต่อเนื่องข้ามวันเหมือนลิ้นชัก

**Architecture:** เติมคอลัมน์ในตารางเดิม (`stock_sale` · `stock_cash_move` · `stock_return` · `stock_store_day` · `stock_branch`) บิลเก่าเป็น `NULL` แล้วอ่านผ่านชิ้น SQL จากฟังก์ชันกลางตัวเดียว (`Stock_buildPaySql`) · สูตรลิ้นชักเดิม (`Stock_getDayCash`) เปลี่ยนเป็นเงินสดจริง และมีสูตรบัญชีโอนคู่กัน (`Stock_getDayBank`) · หน้าจอทุกที่อ่านจากสองฟังก์ชันนี้

**Tech Stack:** PHP 8 + PDO (ตัวช่วย `sdb_*` ของโมดูล) · MariaDB · เทมเพลต PHP ธีม `aostock` + htmx + JS ล้วนใน `inc/footer.php` · ทดสอบด้วยสคริปต์ PHP ยิงเว็บทดสอบในเครื่อง (ไม่มี test framework ในโปรเจกต์)

**Spec:** `docs/superpowers/specs/2026-11-17-transfer-account-design.md` (อนุมัติ 23 พ.ย. 2569)

## Global Constraints

- แก้ได้เฉพาะ `admweb/aowebdata/modules/stock/` · `admweb/aowebdata/fix.<domain>.php` · `themes/aostock/` — ห้ามแก้ admweb core
- ฟังก์ชันใหม่ชื่อ `Stock_verbCamel` (กริยาตามตารางใน `modules/stock/FUNCTIONS.md`) + มี TODO comment + เติมแถวใน `FUNCTIONS.md`
- ไฟล์ PHP มี File Header (FILE / ROLE / DEPENDS / TABLES / TODO) · คอมเมนต์สั้นบรรทัดเดียว ประวัติไปที่ `docs/CHANGELOG.md`
- เทมเพลตธีมใช้ `{ }` + `<?php echo` (ห้าม `endif` / `endforeach` / `<?=`)
- คำบนจอเป็นภาษาที่พนักงานร้านเข้าใจ — คำใหม่ที่ไม่ได้ตกลงไว้ในสเปก เสนอผู้ใช้ตอนรายงานช่วง · ห้ามมีคำว่า admweb / หลังบ้าน บนจอ POS (ใช้ “คนดูแลเว็บ”)
- ทำทีละช่วง (Task) · จบช่วงต้องรายงานเป็นภาษาไทย + รายชื่อไฟล์ที่แก้ / เพิ่ม แล้ว **รอผู้ใช้พิมพ์ “เริ่มช่วง N ได้เลย”** ก่อนช่วงถัดไป
- **ห้าม commit / push** — ผู้ใช้ commit เอง (ขั้น “Commit” ของ skill แทนด้วยขั้นรายงาน)
- ห้ามพิมพ์รหัสผ่าน / PIN ของบัญชีทดสอบในแชต · ห้ามอัป `fix.localhost.php` · `docs/aostock-test-accounts.js` · `docs/superpowers/`
- ช่องทางเงินมี 2 ค่าเท่านั้น: `'cash'` · `'transfer'` · วิธีจ่ายของบิล: `'cash'` · `'transfer'` · `'mixed'`
- ค่าเงินปัด 2 ตำแหน่ง (`round(x, 2)`) · เทียบ “ตรง” ด้วย `abs(diff) < 0.005` (แบบเดียวกับโค้ดเดิม)
- เว็บยังอยู่ช่วงทดสอบ (ผู้ใช้แจ้ง 23 พ.ย.) — ไม่ต้องทำตัวเช็กคอลัมน์กันพังระหว่างอัปกับ Reinstall แค่บอกลำดับ: อัป `database.php` → Reinstall → อัปไฟล์ที่เหลือ

## Review Focus

1. **ยกเลิกบิลที่ทอนเป็นโอน แล้วคืนเงินลูกค้าเป็นเงินสด** — บิล 400 รับสด 1,000 ทอนโอน 600 → ยกเลิก คืนสด → ลิ้นชัก +600 · บัญชีโอน −600 (ทดสอบใน Task 6: `is6_void_mixed`)
2. **จ่ายผสมที่เงินสดไม่อยู่ในช่วง** — เงินสด 0 / เท่ายอดบิล / มากกว่ายอดบิล ต้องปฏิเสธ ไม่บันทึกเป็นบิลผสมผิด ๆ (Task 2: `tp2_mixed_bounds`)
3. **เงินสดรับพอดี (ช่องว่าง) + เลือกทอนเป็นโอน** — ไม่มีเงินทอน → `change_transfer = 0` ไม่ใช่ติดลบ / ไม่ใช่ยอดบิล (Task 2: `tp2_exact_with_transfer_change`)
4. **บิล / วันก่อนเปิดใช้** — บิลยกเลิกเก่า (`void_refund_channel` NULL) ไม่นับ · ตัวเลขย้อนหลังทุกหน้าเท่ากับก่อนแก้ (Task 1 เก็บค่าเดิม · Task 9 เทียบ: `t9_baseline_equal`)
5. **วันที่เปิดร้านก่อน Reinstall** (`bank_carried` NULL) — ปิดร้านได้โดยไม่ต้องกรอกยอดในแอป ไม่มีการ์ดบัญชีโอน ไม่มีบรรทัดบัญชีโอนใน Telegram (Task 4: `tp4_preexisting_day` · Task 5: `tp5_close_no_bank_card`)

---

## ไฟล์ที่แตะ

| ไฟล์ | หน้าที่ในงานนี้ |
|---|---|
| `admweb/aowebdata/modules/stock/database.php` | คอลัมน์ใหม่ทั้งหมด (`$sqlAlter`) |
| `admweb/aowebdata/modules/stock/api/sale.php` | `Stock_buildPaySql` · `Stock_formatPayMethod` · `sale_bill_shape` · `bill_save` · `bill_void` · `cart_from_bill` / `Stock_getBillRedo` · `sale_summary` · `sale_flash` |
| `admweb/aowebdata/modules/stock/api/store.php` | `Stock_getDayCash` · `Stock_getDayBank` · `Stock_getBankCarry` · `Stock_getTransferList` · `store_open` · `store_close` · `Stock_closePastDay` · `store_cash_add` · `store_cash_list` · `store_daily_cash` · `store_cash_sales` · `store_refunds` · `store_state` · `store_day_result` |
| `admweb/aowebdata/modules/stock/api/return.php` | `return_save` ช่องทางคืน · `returns_query` คืนค่า `refund_channel` |
| `admweb/aowebdata/modules/stock/api/bill.php` | `acct_day` · `acct_range` |
| `admweb/aowebdata/modules/stock/api/report.php` | รายงานรายวัน / พนักงาน (บรรทัด ~231) |
| `admweb/aowebdata/modules/stock/api/notify.php` | ข้อความ open / close / cash_diff / void / refund + `notify_d_store` |
| `admweb/aowebdata/modules/stock/api/core.php` | ป้ายสิทธิ์ `refund_cash` |
| `admweb/aowebdata/modules/stock/api/branch-staff.php` | กฎค่าตั้ง `bank_start` |
| `themes/aostock/sale.php` · `inc/sale-live.php` · `inc/footer.php` | ช่องรับเงิน 3 แบบ · กล่องยืนยันรับเงิน / ยกเลิกบิล |
| `themes/aostock/history.php` | ยกเลิกบิลจากประวัติ ส่งช่องทางคืน |
| `themes/aostock/bill-print.php` | ใบเสร็จ |
| `themes/aostock/store.php` | เปิดร้าน (เช็กบัญชียกมา) · การ์ดเติม / หยิบ · ปิดร้าน (การ์ดบัญชีโอน) · ปิดย้อนหลัง · สรุปหลังปิด |
| `themes/aostock/return.php` | เลือกคืนเป็นเงินสด / โอน |
| `themes/aostock/adm-dashboard.php` · `adm-day-sales.php` · `adm-branches.php` · `account.php` · `inc/history-past.php` · `inc/sale-bills.php` | ยอดจริงต่อช่องทาง · ป้ายวิธีจ่าย · ยอดตั้งต้นบัญชีโอน |
| `themes/aostock/assets/app.css` | หน้าตาส่วนใหม่ |
| `admweb/aowebdata/modules/stock/FUNCTIONS.md` · `DATABASE.md` · `docs/CHANGELOG.md` | เอกสาร |

**เว็บทดสอบในเครื่อง** (นอก repo): `C:\Users\admin\AppData\Local\Temp\claude\D--Work-W-WebPackage-services-stock-system\a87165e4-3011-4071-9e6e-e9adb210458b\scratchpad\env\` (เรียกว่า `$ENV`) — ฐานข้อมูล `maria/aostock_local` ยังอยู่ สคริปต์ถูกล้างไปแล้ว สร้างใหม่ใน Task 1

---

### Task 1: เว็บทดสอบ + เก็บตัวเลขเดิม + ตาราง + สูตรเงินของบิล

**Files:**
- Create (นอก repo): `$ENV/httpd.conf` · `$ENV/drive.php` · `$ENV/smoke.php` · `$ENV/baseline.php` · `$ENV/t1_test.php` · `$ENV/www/t_fn.php`
- Modify: `admweb/aowebdata/modules/stock/database.php` (ต่อท้ายส่วน 12–17 ของตาราง POS + `stock_branch`)
- Modify: `admweb/aowebdata/modules/stock/api/sale.php` (`sale_bill_shape` ~บรรทัด 173 · ฟังก์ชันใหม่ต่อจากนั้น)

**Interfaces:**
- Produces:
  - `Stock_buildPaySql(string $a = 's'): array` → `array('cash_in' => SQL, 'cash_out' => SQL, 'tr_in' => SQL, 'tr_out' => SQL)` ชิ้น SQL ต่อบิลหนึ่งแถว ใช้ alias `$a` ของตาราง `stock_sale` · สูตรตามสเปก 6.1 (COALESCE + IF จาก `pay_method` / `received` / `change_amount` / `total`)
  - `sale_bill_shape()` คืนเพิ่ม: `'pay_cash'` `'pay_transfer'` `'change_cash'` `'change_transfer'` (float) · `'void_refund_channel'` (`'cash'|'transfer'|null`)
  - `Stock_formatPayMethod(array $bill): string` → `'เงินสด'` · `'โอน / พร้อมเพย์'` · `'สด + โอน'` · ต่อท้าย `' · ทอนเป็นโอน'` เมื่อ `change_transfer > 0`
  - `$ENV/drive.php`: `login($who)` · `req($jar, $url, $post = null, $hx = false)` · `csrf($body)` · `db()` · `ok($cond, $label, $extra = '')` · `$PASS` `$FAIL` (แบบเดิม — รหัสอ่านจากหัว `sql/test-data.sql` ห้ามพิมพ์ออก)
  - `$ENV/www/t_fn.php?a=<action>&...` ตัวเรียกฟังก์ชันตรง (bootstrap `inc.php` · ต้องส่ง User-Agent แบบเบราว์เซอร์) คืน JSON — แต่ละ Task เติม action ของตัวเอง

- [ ] **Step 1: สร้างเว็บทดสอบคืน** — `httpd.conf` (Apache :8098 · DocumentRoot = repo · `Alias /_snap $ENV/www` · `<Directory D:/> AllowOverride None` กัน `.htaccess` ของ `D:/Work`) · สตาร์ต MariaDB (`mysqld --no-defaults --datadir=$ENV/maria --port=3399`) + Apache เป็นงานเบื้องหลัง · เขียน `drive.php` + `smoke.php` (เปิดทุกหน้าใน `aConfigPages` ด้วย 5 บทบาท somchai / pim / nipa / bee / mint ต้องได้ 200 หรือ redirect ตามสิทธิ์ และ `logs/error.log` ไม่มีบรรทัดใหม่)

Run: `php $ENV/smoke.php`
Expected: `== pass 5 · fail 0`

- [ ] **Step 2: เก็บตัวเลขเดิมก่อนแก้** — `baseline.php save` เขียน `$ENV/baseline.json` ทุกสาขา ทุกวันที่มีข้อมูล: `acct_day()` (cash / transfer / total / refund / net) · `Stock_getDayCash()` (expected) · `sale_summary`-แบบวัน · ข้อความ HTML ของ `adm-day-sales.php?d=...` ส่วนตัวเลข · ผลรายงาน `report.php` ช่วงทั้งหมด (ผ่าน `t_fn.php`)

Run: `php $ENV/baseline.php save`
Expected: พิมพ์จำนวนวัน × สาขาที่เก็บ > 0

- [ ] **Step 3: เขียนเทสต์ที่ยังไม่ผ่าน `t1_test.php`**
  - `t1_columns_exist`: หลัง Reinstall มีคอลัมน์ `stock_sale.pay_cash/pay_transfer/change_cash/change_transfer/void_refund_channel` · `pay_method` enum มี `mixed` · `stock_cash_move.channel` · `stock_return.refund_channel` · `stock_store_day.bank_carried/bank_open_counted/bank_open_reason/bank_expected/bank_counted` · `stock_branch.bank_start`
  - `t1_old_bills_shape`: ทุกบิลเก่า `pay_cash − change_cash + pay_transfer − change_transfer == total` (±0.005) · บิล `pay_method='transfer'` → `pay_transfer == total`, `pay_cash == 0`
  - `t1_sql_matches_shape`: SUM ของ 4 ชิ้นจาก `Stock_buildPaySql('s')` ต่อวัน == SUM จาก `sale_bill_shape` วันเดียวกัน
  - `t1_label`: `Stock_formatPayMethod` คืน 4 แบบตามบิลจำลอง (cash · transfer · mixed · cash+ทอนโอน)

Run: `php $ENV/t1_test.php`
Expected: FAIL (ยังไม่มีคอลัมน์ / ฟังก์ชัน)

- [ ] **Step 4: เพิ่มคอลัมน์ใน `database.php`** ตามตาราง 6.1 ของสเปก · `pay_method` ใช้คีย์ที่ไม่ใช่คอลัมน์จริง เช่น `$sqlAlter[...stock_sale]['pay_method_mixed'] = "ALTER TABLE ... MODIFY COLUMN pay_method enum('cash','transfer','mixed') NOT NULL DEFAULT 'cash'"` (แบบ `perms_text`) · คอลัมน์ใหม่ต่อท้ายคอลัมน์ที่เกี่ยว (`AFTER`) · แก้คอมเมนต์ `stock_return.refund` เป็น “เงินที่คืนจริง” ผ่าน `CREATE TABLE` เท่านั้น (ไม่ ALTER) · กด Reinstall ในเว็บทดสอบ (ใช้ตัวติดตั้งของ admweb ผ่าน `t_fn.php?a=install` หรือหน้า Reinstall ในเครื่อง)

- [ ] **Step 5: เขียน `Stock_buildPaySql` · `Stock_formatPayMethod` · เติม 5 ค่าใน `sale_bill_shape`** (บิลเก่าค่าที่ NULL คิดตามสูตรเดียวกับ SQL — เขียนเป็นฟังก์ชันภายใน PHP คู่กัน ไม่ใช่ copy สองที่)

- [ ] **Step 6: เทสต์ผ่าน**

Run: `php $ENV/t1_test.php` แล้ว `php $ENV/smoke.php`
Expected: `fail 0` ทั้งสอง

- [ ] **Step 7: รายงานช่วง 1** — ไฟล์ที่แก้ (`database.php` · `api/sale.php`) · ผลเทสต์ · รอ “เริ่มช่วง 2 ได้เลย”

---

### Task 2: บันทึกบิล — จ่ายผสม + ทอนเป็นโอน (ฝั่งเซิร์ฟเวอร์)

**Files:**
- Modify: `admweb/aowebdata/modules/stock/api/sale.php` (`bill_save` ~290 · `Stock_getBillRedo` ~90 · `cart_from_bill` ~490 · `sale_summary` ~510)
- Modify: `themes/aostock/sale.php` (act=pay ~128)
- Test: `$ENV/t2_test.php`

**Interfaces:**
- Consumes: `Stock_buildPaySql` (Task 1)
- Produces:
  - `bill_save($code, $user, $method, $received, $vat = false, $net = null, $changeTo = 'cash')` · `$method` ∈ cash/transfer/mixed · `$received` = เงินสดที่รับ (null = พอดีสำหรับ cash · บังคับสำหรับ mixed) · `$changeTo` ∈ cash/transfer · บันทึก 4 ช่อง + `received` = สด + โอน · `change_amount` = ทอนรวม
  - `Stock_getBillRedo()` คืนเพิ่ม `'change_to'` และ `'method'` รองรับ `'mixed'`
  - `sale_summary($code)` คืน `'cash'` / `'transfer'` เป็นยอดสุทธิต่อช่องทาง (ผลรวมเท่า `'total'`)
  - POST หน้าขาย: `method` · `received` · `change_to`

- [ ] **Step 1: เขียนเทสต์ที่ยังไม่ผ่าน** (ยิง `sale.php` act=pay ด้วยบัญชีพนักงานที่ขายได้ หลังเปิดร้าน · อ่านแถวจาก DB)
  - `tp2_cash_change_cash`: ยอด 400 รับ 1000 → pay_cash 1000 · change_cash 600 · change_transfer 0 · pay_method cash
  - `tp2_cash_change_transfer`: ยอด 400 รับ 1000 `change_to=transfer` → change_cash 0 · change_transfer 600
  - `tp2_exact_with_transfer_change`: ยอด 400 `received` ว่าง `change_to=transfer` → pay_cash 400 · change 0 ทั้งคู่
  - `tp2_transfer`: method transfer → pay_transfer = ยอด · pay_cash 0 · `change_to` ถูกเมิน
  - `tp2_mixed`: ยอด 2000 method mixed received 1000 → pay_cash 1000 · pay_transfer 1000 · change 0 · pay_method mixed
  - `tp2_mixed_bounds`: mixed received 0 / ว่าง / 2000 / 2500 → ไม่บันทึก + ข้อความ “เงินสดในบิลสด + โอนต้องมากกว่า 0 และน้อยกว่ายอดที่ต้องชำระ (… บาท)” · ตะกร้ายังอยู่
  - `tp2_cash_short`: cash รับ 300 ยอด 400 → ไม่บันทึก (ข้อความเดิม)
  - `tp2_redo`: แก้ไขบิล mixed / ทอนเป็นโอน → `Stock_getBillRedo()` คืน method + change_to เดิม
  - `tp2_summary`: `sale_summary` cash + transfer == total หลังขาย 5 บิลด้านบน

Run: `php $ENV/t2_test.php` · Expected: FAIL

- [ ] **Step 2: แก้ `bill_save` + POST หน้าขาย + redo + `sale_summary`** — `sale_summary` ใช้ชิ้นจาก `Stock_buildPaySql` · ข้อความผิดพลาดตาม `tp2_mixed_bounds`

- [ ] **Step 3: เทสต์ผ่าน** — `php $ENV/t2_test.php` + `php $ENV/smoke.php` → `fail 0`

- [ ] **Step 4: รายงานช่วง 2** · รอ “เริ่มช่วง 3 ได้เลย”

---

### Task 3: หน้าขาย / ใบเสร็จ — ช่องรับเงิน 3 แบบ

**Files:**
- Modify: `themes/aostock/inc/sale-live.php` (กล่องวิธีจ่าย ~293–322 + สคริปต์ช่องรับเงินใน aside)
- Modify: `themes/aostock/inc/footer.php` (`payHtml` ~996)
- Modify: `admweb/aowebdata/modules/stock/api/sale.php` (`sale_flash` ~545)
- Modify: `themes/aostock/bill-print.php` (~200–203 · ~260)
- Modify: `themes/aostock/assets/app.css`
- Test: `$ENV/t3_test.php` + เบราว์เซอร์ (snapshot `$ENV/snap.php`)

**Interfaces:**
- Consumes: POST `method` / `received` / `change_to` (Task 2) · `Stock_formatPayMethod` (Task 1)
- Produces: ฟอร์ม `.pay` มี radio `method` 3 ค่า (`.pay-how` 3 ช่อง) · กล่อง `#cash-box` (เงินสด) · กล่องใหม่ `#mix-box` (สด + โอน: ช่อง `received` เดียวกัน + “ที่เหลือโอน N”) · `#chg-to` radio `change_to` (ขึ้นเมื่อเงินทอน > 0)

- [ ] **Step 1: เทสต์ที่ยังไม่ผ่าน `t3_test.php`** (อ่าน HTML)
  - `t3_pay_radios`: ตะกร้ามีของ → มี `value="mixed"` และ `name="change_to"`
  - `t3_redo_mixed`: แก้ไขบิล mixed → radio mixed `checked`
  - `t3_flash`: หลังขายบิลทอนเป็นโอน ข้อความมี “ทอนเป็นโอน 600.00 บาท” · บิล mixed มี “เงินสด 1,000.00 · โอน 1,000.00”
  - `t3_receipt`: `bill-print.php?no=` บิล mixed มีบรรทัด “รับเงินสด” “รับโอน” · บิลทอนโอนมี “ทอนเป็นโอน” · บิลเก่าเงินสดยังเป็น “รับเงิน / เงินทอน” เท่าเดิม

Run: `php $ENV/t3_test.php` · Expected: FAIL

- [ ] **Step 2: ทำฟอร์ม + สคริปต์** — เงินสดน้อยกว่ายอด = ปุ่มรับเงินกดไม่ได้ (เดิม) · สด + โอน: เงินสดนอกช่วง = ปุ่มกดไม่ได้ + `#recv-err` ข้อความเดียวกับเซิร์ฟเวอร์ · เปลี่ยนวิธีแล้วค่าที่โชว์คิดใหม่ทันที
- [ ] **Step 3: `payHtml` + `sale_flash` + ใบเสร็จ** ตามเทสต์
- [ ] **Step 4: เทสต์ผ่าน + ดูในเบราว์เซอร์** 1180 / 768 / 375 (ไม่มีเลื่อนข้าง · ปุ่มสลับ 3 ช่องไม่ล้น) · smoke ผ่าน
- [ ] **Step 5: รายงานช่วง 3** (แนบคำบนจอใหม่ให้ผู้ใช้ดู) · รอ “เริ่มช่วง 4 ได้เลย”

---

### Task 4: สูตรลิ้นชัก + บัญชีโอน (ฝั่งเซิร์ฟเวอร์ของเปิด / ปิดร้าน)

**Files:**
- Modify: `admweb/aowebdata/modules/stock/api/store.php`
- Test: `$ENV/t4_test.php`

**Interfaces:**
- Consumes: `Stock_buildPaySql` (Task 1) · บิลจาก Task 2
- Produces:
  - `Stock_getDayCash($dayId)` คืนเพิ่ม `'cash_in'` `'cash_out'` `'void_refunds'` · `'sales'` = cash_in − cash_out · `'expected'` ตามสูตรสเปก 6.2 (นับบิล paid + บิลยกเลิกที่ `void_refund_channel` ไม่ NULL · `'refunds'` เฉพาะ `refund_channel='cash'` · เติม / หยิบเฉพาะ `channel='cash'`)
  - `Stock_getDayBank($dayId): ?array` → null เมื่อ `bank_carried` NULL · ไม่งั้น `array('carried','open_counted','start','topup','withdraw','tr_in','tr_out','refunds','void_refunds','expected')` · `'start'` = `COALESCE(bank_open_counted, bank_carried)`
  - `Stock_getBankCarry($code): array('amount' => float, 'from' => 'day'|'start', 'ymd' => 'Ymd'|'' )` ตามสเปก 6.2 ข้อยกมา
  - `Stock_getTransferList($dayId): array` แถว `array('time','kind' => sale|change|refund|void|topup|withdraw,'ref','dir' => in|out,'amount','by')` เรียงเวลา
  - `store_open($code, $user, $topup, $counted, $reason, $bankCounted = null, $bankReason = '')`
  - `store_close($code, $user, $counted, $keep, $note, $bankCounted = null)`
  - `Stock_closePastDay($code, $ymd, $user, $counted, $keep, $note, $bankCounted = '')`
  - `store_cash_add($code, $user, $dir, $amount, $reason, $channel = 'cash')`
  - `store_cash_list()` แถวมี `'channel'` · `store_daily_cash()` / `store_cash_sales()` / `store_refunds()` นับเฉพาะเงินสด
  - `store_state()` คืนเพิ่ม `'bank'` (ผล `Stock_getDayBank` หรือ null) · `store_day_result()` คืนเพิ่ม `'bank_expect'` `'bank_counted'` `'bank_diff'` (null เมื่อไม่มีบัญชีโอน)
  - `Stock_setBankStart($code, $amount): void` — บันทึก `stock_branch.bank_start` และปรับ `bank_carried` ของวันที่เปิดอยู่ เฉพาะเมื่อสาขายังไม่มีวันไหนที่ `bank_counted` / `bank_expected` ไม่ NULL (Task 8 เรียกตอนบันทึกสาขา)

- [ ] **Step 1: เทสต์ที่ยังไม่ผ่าน `t4_test.php`** (เรียกตรงผ่าน `t_fn.php` + ขายจริงผ่านหน้าเว็บ)
  - `tp4_drawer_real_cash`: วันที่มีบิลทอนเป็นโอน (400 รับ 1000 ทอนโอน 600) → ลิ้นชัก expected เพิ่ม 1000 · บัญชีโอน expected ลด 600
  - `tp4_bank_formula`: เติมโอน 5000 · หยิบโอน 200 · ขายโอน 300 · mixed (สด 1000 โอน 1000) → bank expected = start + 5000 − 200 + 300 + 1000 − 600
  - `tp4_carry`: ปิดวันที่ 1 กรอกยอดในแอป 12,345.50 → เปิดวันที่ 2 `bank_carried` = 12,345.50 · วันก่อนปิดย้อนหลังเว้นยอดในแอป → ยกมา = bank_expected ของวันนั้น · ไม่เคยมีตัวเลขบัญชี → `bank_start` ของสาขา
  - `tp4_open_mismatch`: เปิดร้านส่ง bankCounted ≠ ยกมา + เหตุผล → `bank_open_counted` เก็บ · start = ยอดที่กรอก · ประวัติเปิดร้านมี “เงินขยับระหว่างร้านปิด” · ไม่มีเหตุผล = ไม่บันทึก (ข้อความ “กรุณาระบุเหตุผลเมื่อยอดบัญชีโอนไม่ตรงกับที่ยกมา”)
  - `tp4_close_bank`: ปิดร้าน bank ไม่ตรง + note ว่าง → ไม่ปิด (“ยอดในบัญชีโอนไม่ตรงกับยอดที่ควรมี กรุณากรอกหมายเหตุว่าเกิดจากอะไร”) · มี note → บันทึก bank_expected / bank_counted · ประวัติปิดร้านมี “บัญชีโอน ควรมี … · ยอดในแอป … · ผลต่าง …”
  - `tp4_cash_withdraw_limit`: หยิบเงินสดเกินลิ้นชัก = ปฏิเสธ (เดิม) · หยิบโอนเกิน bank expected = **บันทึกได้**
  - `tp4_preexisting_day`: วันที่ `bank_carried` NULL → `Stock_getDayBank` null · ปิดร้านโดยไม่ส่ง bankCounted ได้
  - `tp4_bank_start_edit_day1`: สาขาที่ยังไม่เคยปิดพร้อมตัวเลขบัญชี แก้ `bank_start` → `bank_carried` ของวันที่เปิดอยู่เปลี่ยนตาม (ฟังก์ชันที่ Task 8 เรียกตอนบันทึกสาขา: `Stock_setBankStart($code, $amount)`)
  - `tp4_old_days_equal`: `Stock_getDayCash` ของทุกวันเก่า == `baseline.json`

Run: `php $ENV/t4_test.php` · Expected: FAIL

- [ ] **Step 2: เขียนฟังก์ชันตาม Interfaces** — ข้อความประวัติใช้คำในสเปก 5.4 / 6.4
- [ ] **Step 3: เทสต์ผ่าน** — `t4_test.php` + `t2_test.php` + smoke → `fail 0`
- [ ] **Step 4: รายงานช่วง 4** · รอ “เริ่มช่วง 5 ได้เลย”

---

### Task 5: หน้าเปิด / ปิดร้าน

**Files:**
- Modify: `themes/aostock/store.php` (handler ~40–122 · ฟอร์มเปิด ~248–300 · การ์ดเติม / หยิบ ~348–392 · ฟอร์มปิด ~395–480 · ปิดย้อนหลัง ~200–246 · สรุปหลังปิด ~498)
- Modify: `themes/aostock/adm-dashboard.php` (ฟอร์มปิดย้อนหลัง ~64 · ผลต่างรายสาขา ~93)
- Modify: `themes/aostock/assets/app.css`
- Test: `$ENV/t5_test.php` + เบราว์เซอร์

**Interfaces:**
- Consumes: ทุกฟังก์ชันของ Task 4
- Produces: POST `open`: `bank_counted` `bank_reason` · `close` / `close_past`: `bank_counted` · `cash`: `channel` (cash|transfer)

- [ ] **Step 1: เทสต์ที่ยังไม่ผ่าน `t5_test.php`** (HTML + POST)
  - `tp5_open_form`: ฟอร์มเปิดร้านมี “ยอดบัญชีโอนยกมา” + ลิงก์ “ยอดในแอปไม่ตรง” → ช่อง `bank_counted` `bank_reason` (ซ่อนจนกด · แบบเดียวกับ `#adj`)
  - `tp5_cash_card_channel`: การ์ดเติม / หยิบมี `name="channel"` (เงินสด / โอน) · รายการบอกช่องทาง (“โอน” ต่อท้าย)
  - `tp5_close_bank_card`: ร้านเปิด → การ์ด “บัญชีโอนของสาขา” มีทุกบรรทัดของสูตร + ช่อง `bank_counted` (required) + รายการโอนทีละรายการ (`Stock_getTransferList`)
  - `tp5_close_no_bank_card`: วันที่ `bank_carried` NULL → ไม่มีการ์ดบัญชีโอน · ปิดได้
  - `tp5_closed_summary`: หลังปิด การ์ดสรุปมีแถวบัญชีโอน (ควรมี · ยอดในแอป · ผลต่าง)
  - `tp5_past_close`: ฟอร์มปิดย้อนหลัง (store.php + adm-dashboard) มีช่องยอดในแอป **ไม่ required**
  - `tp5_dashboard_bank_diff`: ภาพรวมผู้ดูแลแสดงผลต่างบัญชีโอนรายสาขาคู่กับเงินสด

Run: `php $ENV/t5_test.php` · Expected: FAIL

- [ ] **Step 2: ทำหน้าจอ** — การ์ดเงินสดเดิมเปลี่ยนบรรทัดเป็น “+ รับเงินสดจากขาย / − ทอนเงินสด / − คืนเงินสด (รับคืน · ยกเลิกบิล)” · การ์ดบัญชีโอนใหม่วางคู่การ์ดเงินสดใน `.store-two` · สคริปต์ผลต่างของบัญชีโอนแบบเดียวกับ `#diff`
- [ ] **Step 3: เทสต์ผ่าน + เบราว์เซอร์** 1180 / 768 / 375 · smoke ผ่าน
- [ ] **Step 4: รายงานช่วง 5** (คำบนจอใหม่ให้ผู้ใช้ดู) · รอ “เริ่มช่วง 6 ได้เลย”

---

### Task 6: ยกเลิกบิล — เลือกช่องทางคืนเงิน

**Files:**
- Modify: `admweb/aowebdata/modules/stock/api/sale.php` (`bill_void` ~429 · ฟอร์มใน `sale_flash` ~580)
- Modify: `themes/aostock/sale.php` (~98–124) · `themes/aostock/history.php` (handler ~120–140 · ฟอร์ม ~546–566)
- Modify: `themes/aostock/inc/footer.php` (`voidHtml` ~1037 · kind void ~1194)
- Modify: `admweb/aowebdata/modules/stock/api/notify.php` (`notify_on_void` ~504)
- Test: `$ENV/t6_test.php`

**Interfaces:**
- Consumes: สูตร Task 4 (นับ `void_refund_channel` แล้ว)
- Produces:
  - `bill_void($code, $no, $user, $reason, $reopen = false, $refundCh = null)` — `$reopen=false` ต้องมี `$refundCh` ∈ cash|transfer (null/ค่าอื่น = ใช้ค่าตั้งต้นจาก `Stock_getVoidRefundDefault($bill)`) · `$reopen=true` เมิน `$refundCh` และเก็บ NULL
  - `Stock_getVoidRefundDefault(array $bill): string` → `'cash'` ถ้า `pay_cash > 0` ไม่งั้น `'transfer'`
  - ฟอร์มยกเลิกบิลทุกที่มี `<input type="hidden" name="refund_ch" value="…">` + `data-refund="cash|transfer"` · footer วาด radio “คืนเงินลูกค้าเป็น เงินสด / โอน” ในกล่องยืนยัน (เฉพาะ doc=bill · kind=void) แล้วเขียนค่ากลับลงช่องนั้นตอนยืนยัน

- [ ] **Step 1: เทสต์ที่ยังไม่ผ่าน `t6_test.php`**
  - `is6_void_mixed`: บิล 400 รับสด 1000 ทอนโอน 600 → ยกเลิก `refund_ch=cash` → ลิ้นชัก expected สุทธิ +600 · บัญชีโอน −600 เทียบก่อนขาย
  - `is6_void_transfer_bill`: บิลโอน 300 ยกเลิก `refund_ch=transfer` → ทั้งสองกองกลับเท่าก่อนขาย
  - `is6_void_default`: ไม่ส่ง `refund_ch` → เก็บค่าตั้งต้นตาม `Stock_getVoidRefundDefault`
  - `is6_edit_null`: แก้ไขบิล (act=edit) → `void_refund_channel` NULL · บิลเดิมไม่นับทั้งใบ
  - `is6_history_void`: ยกเลิกจาก `history.php` ส่งช่องทางได้เหมือนหน้าขาย
  - `is6_forms`: ฟอร์มยกเลิกบิลใน sale flash + history มี `name="refund_ch"` และ `data-refund`
  - `is6_log`: ประวัติยกเลิกบิล + ข้อความ Telegram (ผ่าน `notify_tg_text('void', …)`) มี “คืนเงินลูกค้าเป็นเงินสด / โอน … บาท”

Run: `php $ENV/t6_test.php` · Expected: FAIL

- [ ] **Step 2: แก้ตาม Interfaces** · ดูกล่องยืนยันในเบราว์เซอร์ (คอม + มือถือ)
- [ ] **Step 3: เทสต์ผ่าน** — `t6_test.php` + `t4_test.php` + smoke → `fail 0`
- [ ] **Step 4: รายงานช่วง 6** · รอ “เริ่มช่วง 7 ได้เลย”

---

### Task 7: รับคืนสินค้า — คืนเป็นเงินสด / โอน

**Files:**
- Modify: `admweb/aowebdata/modules/stock/api/return.php` (`return_save` ~231 · `returns_query` ~154)
- Modify: `themes/aostock/return.php` (POST ~64–76 · ฟอร์ม ~306–333 · กล่องผล ~144 · การ์ดวันนี้ ~348)
- Modify: `admweb/aowebdata/modules/stock/api/core.php` (ป้าย `refund_cash` ใน `perm_list`)
- Modify: `admweb/aowebdata/modules/stock/api/notify.php` (`notify_tg_text` case refund)
- Test: `$ENV/t7_test.php`

**Interfaces:**
- Produces: `return_save($code, $user, $bill, $qtys, $reason, $note, $refund, $refundNote, $refundCh = 'cash')` บันทึก `refund_channel` · ใบรับคืนที่คืนค่า (`returns_query`) มี `'refund_channel'` · POST `refund_ch`

- [ ] **Step 1: เทสต์ที่ยังไม่ผ่าน `t7_test.php`**
  - `t7_refund_transfer`: คืนแบบโอน 250 → ลิ้นชักไม่ขยับ · บัญชีโอน −250 · `refund_channel='transfer'`
  - `t7_refund_cash`: คืนเงินสด → ลิ้นชัก −ยอด (เหมือนเดิม)
  - `t7_ui`: ฟอร์มมีตัวเลือก เงินสด / โอน · ป้ายช่อง “ยอดเงินคืน” ไม่ผูกกับ “จากลิ้นชัก” เมื่อเลือกโอน · การ์ดวันนี้แยก “เงินสดออกจากลิ้นชัก … · โอนคืน …”
  - `t7_perm_label`: หน้าตั้งสิทธิ์พนักงานเขียน “คืนเงินให้ลูกค้า” (คีย์ `refund_cash` เดิม)
  - `t7_telegram`: ข้อความ refund บอกช่องทาง (“คืนเงินสด” / “โอนคืน”)

Run: `php $ENV/t7_test.php` · Expected: FAIL

- [ ] **Step 2: แก้ตาม Interfaces**
- [ ] **Step 3: เทสต์ผ่าน** — `t7_test.php` + `t4_test.php` + smoke
- [ ] **Step 4: รายงานช่วง 7** · รอ “เริ่มช่วง 8 ได้เลย”

---

### Task 8: รายงาน · ผู้ดูแล · ป้ายวิธีจ่าย · Telegram / อีเมล

**Files:**
- Modify: `admweb/aowebdata/modules/stock/api/bill.php` (`acct_day` ~301 · `acct_range` ~325)
- Modify: `admweb/aowebdata/modules/stock/api/report.php` (~231)
- Modify: `admweb/aowebdata/modules/stock/api/notify.php` (`notify_d_store` ~343 · `notify_tg_text` open / close / cash_diff · ตัวอย่างพรีวิว ~576–630)
- Modify: `admweb/aowebdata/modules/stock/api/branch-staff.php` (กฎค่าตั้งสาขา ~234 เพิ่ม `bank_start` 0–10,000,000)
- Modify: `themes/aostock/adm-branches.php` (ช่องในฟอร์มเพิ่ม / แก้สาขา ~322 / ~403 → เรียก `Stock_setBankStart`)
- Modify: `themes/aostock/adm-day-sales.php` (~51 · ~81) · `account.php` (~70 · ~205) · `inc/history-past.php` (~158) · `return.php` (~186 · ~221) · `inc/sale-bills.php`
- Test: `$ENV/t8_test.php`

**Interfaces:**
- Consumes: `Stock_buildPaySql` · `Stock_formatPayMethod` · `store_day_result` (bank) · `Stock_setBankStart`
- Produces: `acct_day` / `acct_range` คืน `'cash'` / `'transfer'` เป็นยอดสุทธิต่อช่องทาง (บิล paid) · ข้อความ Telegram open / close / cash_diff มีบรรทัดบัญชีโอนเมื่อวันนั้นมีบัญชีโอน

- [ ] **Step 1: เทสต์ที่ยังไม่ผ่าน `t8_test.php`**
  - `t8_acct_net`: วันที่มีบิลทอนโอน → acct cash + transfer == total · บิลทอนโอนทำให้ transfer ติดลบได้
  - `t8_labels`: บัญชี / ยอดขายรายวัน / รับคืน / ประวัติ / บิลของฉัน แสดง “สด + โอน” และ “ทอนเป็นโอน” ตาม `Stock_formatPayMethod`
  - `t8_branch_bank_start`: ผู้ดูแลบันทึก “ยอดเงินในบัญชีโอนตอนเริ่มใช้” → `stock_branch.bank_start` · ใต้ช่องมีคำอธิบายว่าใช้ได้ถึงปิดร้านครั้งแรก
  - `t8_telegram_close`: `notify_tg_text('close', …)` มี “บัญชีโอน ควรมี … · ยอดในแอป …” + ขาด / เกิน · วันไม่มีบัญชีโอน = ไม่มีบรรทัดนี้
  - `t8_telegram_open`: เปิดร้านแบบยอดบัญชีไม่ตรง → ข้อความ open มี “⚠️ ยอดบัญชีโอนยกมา … ยอดในแอป … — เหตุผล”
  - `t8_day_sales_money`: `adm-day-sales.php` เลือกสาขา + วัน → มีสรุปเงินของวันนั้น (ลิ้นชัก: ควรมี / นับได้ / ผลต่าง · บัญชีโอน: ควรมี / ยอดในแอป / ผลต่าง เมื่อวันนั้นมีบัญชีโอน)
  - `t8_mail_pay`: อีเมลสรุปรายวันส่วน “เงินสด / โอน แยกช่องทาง” ใช้ยอดสุทธิต่อช่องทาง (เทียบกับ `acct_day`)
  - `t8_old_equal`: `acct_day` / `acct_range` / report ของทุกวันเก่า == `baseline.json`

Run: `php $ENV/t8_test.php` · Expected: FAIL

- [ ] **Step 2: แก้ตาม Interfaces** (ทุก query แยกเงินสด / โอนใช้ `Stock_buildPaySql` เท่านั้น — grep `pay_method` แล้วเหลือแค่ที่บันทึก / ป้าย)
- [ ] **Step 3: เทสต์ผ่าน** — `t8_test.php` + smoke
- [ ] **Step 4: รายงานช่วง 8** · รอ “เริ่มช่วง 9 ได้เลย”

---

### Task 9: ทดสอบรวม + เอกสาร

**Files:**
- Modify: `admweb/aowebdata/modules/stock/FUNCTIONS.md` · `DATABASE.md` · `docs/CHANGELOG.md`
- Test: `$ENV/t9_test.php` · ทุกเทสต์ t1–t8 · smoke · เบราว์เซอร์

- [ ] **Step 1: `t9_baseline_equal`** — `php $ENV/baseline.php compare` ทุกค่าตรงกับก่อนแก้ (วันเก่าทั้งหมด)
- [ ] **Step 2: ไล่ทั้งหมด** — `t1`–`t8` + smoke 5 บทบาท + ยอดสต๊อกตรงประวัติ + `logs/error.log` ไม่มีบรรทัดใหม่
- [ ] **Step 3: grep ตกหล่น** — `grep -rn "pay_method\|\['method'\]" themes/aostock admweb/aowebdata/modules/stock/api` เหลือเฉพาะที่บันทึก / ป้ายผ่าน `Stock_formatPayMethod` / เงื่อนไขวิธีจ่ายในหน้าขาย
- [ ] **Step 4: เบราว์เซอร์รอบสุดท้าย** — วันเต็ม: เปิดร้าน (ยอดบัญชีไม่ตรง) → ขาย 3 แบบ + ทอนโอน → เติม / หยิบโอน → รับคืนแบบโอน → ยกเลิกบิลคืนสด → ปิดร้าน (กรอกยอดในแอป) → เปิดวันถัดไปเห็นยอดยกมาถูก · คอม / iPad / มือถือ
- [ ] **Step 5: เอกสาร** — `FUNCTIONS.md` (ฟังก์ชันใหม่ + ยอดรวม + หัวข้อไฟล์) · `DATABASE.md` (คอลัมน์ใหม่) · `CHANGELOG.md` (หัวข้อใหม่ + ลำดับขึ้นเซิร์ฟเวอร์: อัป `database.php` → Reinstall → ไฟล์ที่เหลือ · ผู้ดูแลกรอกยอดตั้งต้นทุกสาขาก่อนเปิดร้าน · `docs/superpowers/` ไม่ต้องอัป) · คู่มือ PDF / เช็กลิสต์ ยกไปงานเอกสารที่ค้าง
- [ ] **Step 6: รายงานจบงาน** — สรุปผล · รายชื่อไฟล์ทั้งหมดที่ต้องอัป · ลำดับอัป
