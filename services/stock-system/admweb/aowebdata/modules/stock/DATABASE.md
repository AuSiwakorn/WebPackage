# ตารางของ AOSTOCK (module stock)

คำอธิบายตารางใน `database.php` — ในไฟล์นั้นเหลือแค่ชื่อตารางสั้น ๆ ส่วนความหมายของแต่ละคอลัมน์อยู่ใน `COMMENT '...'` ของ SQL
function ที่อ่าน / เขียนตารางเหล่านี้ดูที่ [`FUNCTIONS.md`](./FUNCTIONS.md)

## ภาพรวม

| หัวข้อ | รายละเอียด |
|---|---|
| ชื่อตาราง | `_DBPREFIX_` ของ admweb (= `ao_`) + `stock_` → `ao_stock_{ชื่อตาราง}` |
| ติดตั้ง | หลังบ้าน admweb → `index.php?module=siteconfig&mp=db` → Run (ข้ามตารางที่มีอยู่แล้ว) · คอลัมน์ที่เพิ่มทีหลังอยู่ใน `$sqlAlter` — กด Reinstall อีกครั้งเพื่อเพิ่ม |
| MySQL | InnoDB · utf8mb4 / utf8mb4_unicode_ci · MySQL 5.6.5+ หรือ MariaDB 10.0+ (DATETIME DEFAULT CURRENT_TIMESTAMP) · index ที่เป็น varchar ยาวไม่เกิน 191 ตัวอักษร (ขีดจำกัด 767 byte ของ InnoDB รุ่นเก่ากับ utf8mb4) |
| PHP | `database.php` มีแค่ string · admweb ต้องการ PHP 8 |
| ลงทะเบียน | ทุกตารางต้องอยู่ใน `$aTablename` ของ `aModuleConfig.php` ไม่งั้นตัวติดตั้ง (`inc_install.php`) ข้าม ไม่สร้างตารางนั้น |

### หลักการ

1. สต๊อกคงเหลือ = ผลรวมของ `stock_move` — ทุกการเปลี่ยนแปลงสต๊อกต้องเขียน `stock_move` ห้ามแก้ยอดตรง ๆ · `stock_balance` เป็นแค่ cache ให้อ่านเร็ว สร้างใหม่จาก `stock_move` ได้เสมอ
2. สาขาเป็นแกนกลาง — เอกสารทุกใบผูก `branch_id` ของ "ตอนทำรายการ" (ไม่อ่านจากโปรไฟล์พนักงาน)
3. เอกสารไม่ลบ — ยกเลิกด้วย `status='void'` + เหตุผล แล้วเขียน `stock_move` กลับรายการ · "แก้ไขใบ" = ยกเลิกใบเดิม (`void_mode='edit'`) แล้วออกใบใหม่ที่ `edit_of_id` ชี้กลับมา
4. รายการในใบเก็บ snapshot (sku, ชื่อ, ราคา / ทุน ณ ตอนนั้น) ให้รายงานย้อนหลังถูก
5. ผู้ทำรายการทุกคน (พนักงาน PIN และผู้ดูแล) อยู่ใน `stock_staff` → `created_by` ใช้ `staff_id` ฟิลด์เดียว

### ขอบเขต

- แกน AOSTOCK: ตาราง 1–11
- โมดูล POS (Option): เปิด–ปิดร้าน · เงินสด · บิลขาย · รับคืนสินค้า (ตาราง 12–17) — ถ้าไม่ใช้ ตัดออกได้โดยแกนยังทำงานครบ (ห้ามตัดตาราง 18)
- ยังไม่ทำ: โอนระหว่างสาขา (ใช้เบิกออก `reason='branch'` + นำเข้าแทน) · IMEI / Serial · ผู้จำหน่าย · ลูกค้า / สะสมแต้ม · จ่ายหลายช่องทางในบิลเดียว

## 1. ข้อมูลหลัก

### 1) `stock_branch` — สาขา

รวมค่าตั้งของสาขาที่ผู้ดูแลกำหนด: วันเริ่มรอบตรวจนับ (`count_day`) · วันย้อนหลัง (`backdate_days`) · เงินทอนมาตรฐาน (`default_float`) และค่าของหัวบิล / เลขที่บิล
คอลัมน์ `daily_goal` และ `count_open_limit` เลิกใช้แล้ว — ฐานข้อมูลเดิมที่มีคอลัมน์นี้ปล่อยไว้ได้ ไม่มีโค้ดใช้

### 2) `stock_staff` — ผู้ใช้ระบบ

พนักงาน (ชื่อผู้ใช้ + PIN 4 หลัก) และผู้ดูแล / ฝ่ายบัญชี (ชื่อผู้ใช้ + รหัสผ่าน) อยู่ตารางเดียวกัน

- `role`: `staff` = พนักงาน (เมนูตามสิทธิ์ใน `perms` เฉพาะสาขาตัวเอง) · `admin` = ผู้ดูแล (ทุกสาขา ไม่ขาย) · `account` = ฝ่ายบัญชี — ไม่มีบทบาทหัวหน้าคลัง ใช้สิทธิ์เสริมใน `perms` แทน
- ผู้ดูแลเพิ่ม / แก้ / พักงาน / ลบพนักงานได้ที่หน้า "จัดการพนักงาน" (ลบได้เฉพาะคนที่ยังไม่เคยทำรายการ)
- `perms` คั่นด้วย `,` ขึ้นต้นด้วยตัวบอกรุ่น `v2` (รายการเต็ม: `perm_list` ใน `api/core.php`) · ชนิด `text` (สิทธิ์ครบทุกตัวยาว 275 ตัวอักษร เกิน varchar(255) เดิม)
  - หน้าร้าน: `sale discount store store_reopen cash bill_fix`
  - งานคลัง: `receive product_add issue stocktake doc_fix`
  - รับคืน: `refund refund_cash` · หมวดสินค้า: `category_add category_del`
  - ดูข้อมูล: `products report`
  - ดูประวัติ: `hist_sale hist_receive hist_issue hist_count hist_return hist_store movements` (คีย์เดิม `history` แปลงเป็น 6 ตัวตอนอ่าน)
  - สิทธิ์เสริม: `void_others backdate report_branch manager`
  - แถวที่ไม่มี `v2` = สิทธิ์ชุดเดิม 7 ตัว (`sale receive issue stocktake history refund category`) → แปลงตอนอ่าน ไม่ต้อง Reinstall
- admin ได้ทุกสิทธิ์ ยกเว้นงานหน้าร้าน / งานคลัง / หมวดสินค้าฝั่งพนักงาน
- `branch_id` = สาขาปัจจุบัน (admin ใส่สาขาหลักได้ แต่สลับดูทุกสาขา)
- `fail_count` / `locked_until` = กรอกผิด 5 ครั้งล็อก 15 นาที
- `pin_fp` = ลายนิ้วมือของ PIN (HMAC-SHA256 ด้วย `AOSTOCK_SECRET_KEY`) ไว้เช็ก PIN ซ้ำในสาขาตอนย้ายสาขา / เปิดใช้งานกลับ (`pin_hash` เทียบกันเองไม่ได้) · พนักงานเดิมได้ค่าตอนเข้าระบบด้วย PIN ครั้งถัดไป

### 2b) `stock_remember` — จดจำการเข้าสู่ระบบ 30 วัน

ช่อง "จดจำการเข้าสู่ระบบ" ของผู้ดูแล / ฝ่ายบัญชี — 1 แถว / 1 เครื่อง

- cookie = `selector:validator` · เก็บเฉพาะ sha256 ของ validator · ใช้แล้วเปลี่ยน validator ใหม่ทุกครั้ง
- ออกจากระบบ = ลบแถวของเครื่องนั้น · เปลี่ยนรหัสผ่าน / พักงาน / ลบบัญชี / validator ผิด = ลบทุกเครื่องของคนนั้น

### 2c) `stock_login_ip` — นับการกรอกผิดตาม IP

1 แถว / 1 IP — ล็อกรายบัญชี (`fail_count`) กันการสุ่ม PIN ของคนเดียว ตารางนี้กันการสุ่มหลายบัญชีจากที่เดียว

- ผิดครบ `IP_LOCK_FAILS` ครั้งภายใน `IP_LOCK_MINUTES` นาทีนับจากครั้งแรก → IP นั้นเข้าระบบไม่ได้ `IP_LOCK_MINUTES` นาที (ค่าใน `api/auth.php`)
- `ip` = IP จริงของผู้ใช้ (`client_ip` — หลัง Cloudflare อ่าน CF-Connecting-IP เฉพาะคำขอที่มาจาก Cloudflare) · IPv6 นับรวมทั้งวง /64
- เข้าระบบสำเร็จไม่ล้างตัวนับ (หมดรอบเอง) · แถวที่ไม่มีการกรอกผิดเกิน 1 วันลบทิ้งตอนมีการกรอกผิดครั้งถัดไป

### 3) `stock_staff_branch` — ประวัติการประจำสาขาของพนักงาน

ใช้ตอบ "พนักงานคนนี้อยู่สาขาไหน ณ วันที่ X" · แถวปัจจุบัน `date_to = NULL` · ย้ายสาขา = ปิดแถวเดิม + เพิ่มแถวใหม่ + อัปเดต `stock_staff.branch_id`

### 4) `stock_category` — หมวดสินค้า

พนักงานที่มีสิทธิ์เพิ่มหมวด / ผู้ดูแลเพิ่มได้ · `created_by` 0 = ระบบ / นำเข้า (แสดงเป็น "หมวดตั้งต้น")

### 5) `stock_product` — สินค้า (SKU)

ไม่จำกัดจำนวน · 1 สี / รุ่น = 1 SKU · `barcode` ค้นได้แบบตรงทั้งรหัส · `image` = path จาก UpFile

## 2. สต๊อก

### 6) `stock_balance` — ยอดคงเหลือต่อสาขา (cache)

อัปเดตพร้อมกับทุกครั้งที่เขียน `stock_move` ในทรานแซกชันเดียวกัน

### 7) `stock_move` — สมุดความเคลื่อนไหว (ledger)

แหล่งความจริงหนึ่งเดียว

- `type`: `receive` / `receive_void` · `issue` / `issue_void` · `adjust` / `adjust_void` · `sale` / `sale_void` / `return` (POS)
- `qty`: มีเครื่องหมาย + เพิ่ม / − ลด
- `ref_type` + `ref_id` = เอกสารต้นทาง (receive, issue, count, sale) · `doc_no` เก็บซ้ำไว้แสดงผลเร็ว

## 3. เอกสารคลัง — รับเข้า · เบิก / ตัดออก · ตรวจนับ

ทุกใบมีโครงเดียวกัน: `doc_no` (`XX-YYMMDD-NNNN` รันต่อสาขาต่อวัน) · `status` posted / void · `void_mode` void = ยกเลิก / edit = ยกเลิกเพื่อแก้ · `edit_of_id` = ใบเดิมที่ถูกแก้ · `void_reason` บังคับกรอก

### 8) `stock_receive` + `stock_receive_item` — ใบรับเข้า (RC-)

ยกเลิกไม่ได้ถ้าของขาย / ตัดออกไปจนยอดไม่พอถอน · ต้องมี `ref_no` (เลขที่เอกสารอ้างอิง) หรือ `note` อย่างใดอย่างหนึ่ง — ไม่มีเอกสาร (เช่น ลูกค้าคืนไม่มีบิล) เขียนเหตุใน `note`

### 9) `stock_issue` + `stock_issue_item` — ใบเบิก / ตัดออก (IS-)

ตัดเกินยอดคงเหลือไม่ได้ · `reason`: `use` เบิกใช้ · `damaged` ชำรุด · `expired` หมดอายุ · `return` คืนผู้จำหน่าย · `branch` ส่งไปสาขาอื่น (ใช้แทนการโอน ต้องกรอก note) · `lost` สูญหาย · `other` อื่น ๆ

### 10) `stock_count` + `stock_count_item` — ใบตรวจนับสต๊อก (AD-)

กรอก "ยอดที่นับได้จริง" ระบบคิดส่วนต่างกับยอด ณ ตอนบันทึก

- `reason`: `miscount` นับผิดครั้งก่อน · `unlogged` ใช้ / เสียไม่ได้บันทึก · `wrongkey` บันทึกผิด · `found` เจอของเพิ่ม · `lost` ของหาย · `other` อื่น ๆ (lost / other ต้องกรอก note)
- รอบการนับคำนวณจาก `stock_branch.count_day` (ไม่มีตารางรอบแยก) · "นับแล้วในรอบนี้" = มี `count_item` ของสินค้านั้นในช่วงรอบ ที่ใบยังไม่ถูก void

## 4. ประวัติการทำรายการ

### 11) `stock_log` — ประวัติรายวันต่อสาขา

`type`: `open close sale void receive rvoid issue ivoid adjust avoid return setting category cash stock` · `detail` = JSON ของคู่ "หัวข้อ => ค่า" ที่แสดงในหน้าประวัติ

## 5. โมดูล POS — เปิด–ปิดร้าน · เงินสด · บิลขาย · รับคืน

### 12) `stock_store_day` — เปิด–ปิดร้าน (1 แถว / สาขา / วัน)

- เงินทอนเป็นก้อนหมุนเวียน: `keep_cash` → `float_carried` ของวันถัดไป
- เปิดร้านใหม่หลังปิด: `status` กลับเป็น open · `reopen_count` + 1 · ค่า `closed_*` / `counted_cash` / `expected_cash` ยังเก็บผลของรอบที่ปิดไปจนกว่าจะปิดใหม่
- วันที่ลืมปิดค้าง `status` open ไว้ — เปิดวันใหม่ต้องปิดวันที่ค้างก่อน (ปิดร้านย้อนหลัง)

### 13) `stock_cash_move` — เงินเข้า / ออกลิ้นชักระหว่างวัน

เติมเงินทอน / หยิบออกที่พนักงานกรอกเองในหน้าเปิด–ปิดร้าน · เงินคืนลูกค้า (รับคืนสินค้า) ไม่ลงตารางนี้ — คิดจาก `stock_return.refund` ของ `day_id` เดียวกัน (ไม่เก็บซ้ำสองที่)

### 14) `stock_sale` + `stock_sale_item` — บิลขาย

- ชำระ 1 ช่องทางต่อบิล (เงินสด / โอน-พร้อมเพย์)
- พนักงานเลือกตอนขายว่า VAT หรือไม่ · เลขที่แยกชุดตามสาขา + ชุด: `{prefix}{YYYY}-{MM}-{เลขรัน 4 หลัก}` เช่น BP2026-01-0001 (prefix ลงท้ายด้วยตัวเลขมี `-` คั่นหน้าปี เช่น QA1-2026-01-0001) นับใหม่ทุกเดือน (ดู `stock_doc_seq`)
- ราคาขายรวม VAT แล้ว → `base_amount + vat_amount = total`
- ส่วนลดท้ายบิล: คนขายแก้ "ยอดที่ต้องชำระ" เอง → `discount = subtotal − total` เช่น ราคาเต็ม 520 ลูกค้าต่อเหลือ 500 → subtotal 520 · discount 20 · total 500 (total ต้อง > 0 และไม่เกิน subtotal)

### 17) `stock_return` + `stock_return_item` — ใบรับคืนสินค้า (RT-)

อ้างอิงบิลเดิม คืนบางรายการ / บางชิ้นได้

- ต้องมีสิทธิ์ `refund` · บิลไม่เก่ากว่า `stock_branch.backdate_days`
- คืนเป็นเงินสดจากลิ้นชักของวันนี้เท่านั้น (`day_id` = วันนี้ · ยอดที่ควรมีในลิ้นชักหัก refund ของ `day_id` นี้)
- `reason` กำหนดว่าของกลับเข้าสต๊อกไหม: `wrong` ซื้อผิดรุ่น / ผิดแบบ → `restock=1` (`stock_move` type=return) · `defect` ชำรุด · `used` ใช้แล้ว · `other` อื่น ๆ → `restock=0` ไม่เข้าสต๊อก แยกเก็บ
- `refund` แก้ได้ 0..`calc_amount` ถ้าไม่เท่ากับ calc ต้องมี `refund_note`
- `sale_item_id` ชี้แถวในบิลเดิม · รูปถ่ายเก็บ path เป็น JSON ใน `photos`
- บิลที่มีการรับคืนแล้ว ยกเลิก / แก้ทั้งบิลไม่ได้

### 18) `stock_doc_seq` — ตัวนับเลขที่เอกสาร

กันเลขซ้ำ / ข้ามเมื่อทำรายการพร้อมกันหลายเครื่อง · ใช้ทั้งแกนและโมดูล POS (ตัดโมดูล POS ออก ห้ามตัดตารางนี้)

- ออกเลข: ล็อกแถว (branch, series, period) → `last_no + 1` → UPDATE ในทรานแซกชันเดียวกับ INSERT เอกสาร
- series + period: `vat` / `novat` + `YYYYMM` = เลขบิลขาย นับใหม่ทุกเดือน · `RC` / `IS` / `AD` / `RT` + `YYMMDD` = ใบรับเข้า / เบิก / ตรวจนับ / รับคืน นับใหม่ทุกวัน · `SKU` = รหัสสินค้าอัตโนมัติ

### 19) `stock_setting` — ค่าตั้งของระบบแบบ key / value (ใช้ร่วมทุกสาขา)

- แจ้งเตือน Telegram: `tg_on`, `tg_token`\*, `tg_chats`, `tg_events` (JSON), `tg_branches` (JSON, ว่าง = ทุกสาขา), `tg_silent`
- อีเมลรายวัน: `mail_on`, `mail_to`, `mail_time` (HH:MM), `mail_days` (open / all), `mail_branches` (JSON), `mail_parts` (JSON), `mail_last_sent` (วันที่ส่งแล้ว — กันส่งซ้ำ), `mail_fail`
- SMTP: `smtp_host`, `smtp_port`, `smtp_secure` (tls / ssl / none), `smtp_user`, `smtp_pass`\*, `from_name`, `from_email`
- ระบบ: `features_off` (JSON กลุ่มฟีเจอร์ที่ปิด) · `branch_limit` (จำนวนสาขาสูงสุด)
- \* = ค่าลับ เก็บแบบเข้ารหัส (`is_secret = 1`) ไม่ส่งกลับไปแสดงบนหน้าเว็บ
- อีเมลรายวัน: cron ทุก 5 นาที → `mail_on` = 1 และเลย `mail_time` และ `mail_last_sent` ≠ วันนี้ → ส่งแล้วบันทึก log

### 20) `stock_notify_log` — ประวัติการส่งแจ้งเตือน

ทั้งที่สำเร็จและไม่สำเร็จ (แสดง "ประวัติการส่งล่าสุด" และไล่ปัญหา) · `channel`: tg / mail · `event`: close, cash_diff, void, refund, reopen, lost, open, low, backdate, daily, test
