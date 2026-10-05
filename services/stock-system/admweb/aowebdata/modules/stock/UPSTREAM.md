# AOSTOCK — บั๊ก / ข้อสังเกตของ admweb ที่เจอระหว่างทำ (ส่งให้เจ้าของ admweb)

> ไฟล์นี้อยู่ในโมดูลของเราเอง (อัปเกรด admweb ไม่ทับ) · `.md` ถูกปิดจากเว็บด้วย `.htaccess` ที่ root
> หลักของโปรเจกต์: **ไม่แก้ไฟล์ใน `admweb/` `admbuilder/` `uploads/piccorp/`** — แก้จากข้างนอกก่อน
> ถ้าเลี่ยงไม่ได้ แก้เป็น patch ชั่วคราว คอมเมนต์ `AOSTOCK:` ทุกจุด และจดไว้ในหมวด A
> อัปเดตล่าสุด: 2 ต.ค. 2026 · ทดสอบกับ admweb 8.0 (ADMIN_VERSION 4.8)

---

## A. patch ที่แก้ในไฟล์ของ admweb ตรง ๆ ⚠ อัปเกรดแล้วต้องเช็ก / แก้ซ้ำ

### A1. `admweb/include/class.login.php` — เก็บรหัสผ่านที่กรอกผิดลง log เป็นข้อความธรรมดา
- เดิม `adminLogin()` ส่ง `'u=' . $username . '/p=' . $password . '/fail'` และ `$username . '|' . $password` เข้า `loginLogsAcc()` → ลงตาราง `ao_logs_login.loginDes`
  (คนพิมพ์ผิดบ่อยคือพิมพ์รหัสของระบบอื่น / รหัสจริงที่ขาดตัวเดียว)
- patch: เก็บเฉพาะ username (`'u=' . $username . '/fail'`, `'u=' . $username . '/error'`)

### A2. `admweb/include/class.login.php` — ระบบล็อกเมื่อกรอกผิดหลายครั้งทำงานผิด
- เดิม `loginLogsAcc()` ตั้ง `$_SESSION['loginfailTime']` แต่ `adminLogin()` อ่าน `$_SESSION['loginfialTime']` (สะกดผิด)
  และเงื่อนไขกลับด้าน: `if (loginfialTime <= now - banTime)` → บล็อก · ไม่งั้น unset แล้ว return false เงียบ ๆ
  ผล: กรอกผิดครบ limit แล้ว session นั้นถูกล็อกตลอดไป ไม่ปลดเมื่อครบ `$loginBanTime`
- patch: อ่าน `loginfailTime` · ยังอยู่ในช่วงแบน = บล็อก · พ้นช่วงแบน = ล้างตัวนับแล้วให้ลองใหม่
- **ยังไม่แก้ (ข้อจำกัดเดิม):** ตัวนับอยู่ใน session → ทิ้ง cookie แล้วเดารหัสต่อได้ไม่จำกัด
  (`_check_loginLogsAcc()` แบน IP ได้ก็ต่อเมื่อ session เดียวกรอกผิดครบ limit) — ควรนับต่อ IP / username ในฐานข้อมูล

---

## B. แก้ในไฟล์ของโปรเจกต์ (root) เพราะ template ของ admweb ใช้ในโฟลเดอร์ย่อยไม่ได้ / มีบั๊ก

### B1. `index.php` (router) — ลิงก์แบบ `xxx.php` ไม่เคยทำงาน
- ข้อ 4 `preg_replace('/[^a-zA-Z0-9\/_-]/', '', $route)` ลบ `.` ทิ้งก่อน → `login.php` กลายเป็น `loginphp`
  ข้อ 6.5 ที่ตั้งใจตัด `.php` ออกจึงไม่เคย match
- แก้ในโปรเจกต์: ตัด `.php` ท้าย segment ก่อนข้อ 4 (ข้อ 3.5)

### B2. `index.php` / `.htaccess` / `fix.*.php` — ไม่รองรับการติดตั้งในโฟลเดอร์ย่อย
- `index.php` route จาก `REQUEST_URI` ตรง ๆ · `.htaccess` ใช้ `RewriteBase /`, `!^/admweb/`, redirect ไป `/$1`
  · `URL_WEB_ROOT = '//' . HTTP_HOST` ไม่มี path
- แก้ในโปรเจกต์: `$webBase` ใน fix file → `URL_WEB_ROOT` · index.php ตัด base path · `.htaccess` เขียนแบบ relative + `RewriteBase /aostock/`

### B3. `index.php` — warning เมื่อ URL ไม่มี segment ที่ 2 (`$_getdata['slug'] = $parts[1]`)
- แก้ในโปรเจกต์: `$parts[1] ?? ''`

### B4. `admweb/mainApi.php` — `@session_start()` ไม่ตั้ง cookie (ไม่มี HttpOnly / SameSite / Secure)
- แก้ในโปรเจกต์: `inc.php` เรียก `session_set_cookie_params()` ก่อน include mainApi.php

---

## C. แก้จากข้างนอก (ไม่แตะไฟล์ของ admweb)

### C1. SQL injection — `admweb/api/post_ajax.php` → `CMC_add_Contact()` (`admweb/core/siteconfig/api.php:101`)
- เอา `$_POST` name / subject / message / urlname ต่อเข้า INSERT ตรง ๆ · เรียกได้โดยไม่ต้อง login
- `post_*.php` ทั้งชุดเป็นของโปรเจกต์อื่น (ข้อความในไฟล์ยังเป็น cmcgroupasia.com)
- `post_*.php`, `api/inc.php`, `api/api_lang.php` include `dirname(__FILE__) . 'oApi.php'` ขาด `/` → หาไฟล์ไม่เจอ
- ข้างนอก: `.htaccess` ที่ root ปิด `admweb/api/(post_*|inc|api_lang|oApi).php`

### C2. backup / monitor — `admweb/api/BACKUP/monitor.php`, `MONITOR_SCAN/monitor.php`, `THREAT/*.php`
- ตรวจแค่ `HTTP_REFERER` / `HTTP_ORIGIN` / `HTTP_HOST` มีคำว่า `aosoft.co.th` — ปลอม header ได้
- BACKUP สั่ง `CORN_BACKUP_Create()` เขียน SQL dump ของทุกตารางไว้ที่ `uploads/autobackup/<วัน>/` ซึ่งเว็บเสิร์ฟตรง
- ข้างนอก: `uploads/.htaccess` ปิด `autobackup/ backdb/ hash/ logs/ permission/ config_file/` (endpoint ยังเปิด เพราะระบบ monitor ของบริษัทเรียกใช้)
- ควรแก้ต้นทาง: ใช้ token ลับต่อเว็บ / HMAC แทนการเช็ก header · เก็บ backup นอก web root

### C3. `admweb/phpinfo.php` — กันด้วย HTTP Basic auth ที่ user / hash เหมือนกันทุกเว็บ
- ข้างนอก: `.htaccess` ที่ root ปิด `admweb/phpinfo.php`

### C4. `uploads/piccorp` (CImage) — ไลบรารีที่มีสคริปต์ PHP อยู่ใน `uploads/`
- `webroot/img_config.php` ตั้ง `'mode' => 'development'` (แสดงรายละเอียด error / path)
- สคริปต์ทดสอบเปิดได้: `check_system.php`, `tests.php`, `test/`, `compare/`, `imgd.php` ฯลฯ
- `webroot/.htaccess` มี `RewriteEngine on` + `Require all granted` ของตัวเอง → กฎ RewriteRule จาก `uploads/.htaccess` ไม่มีผลในโฟลเดอร์นี้
- ข้างนอก: `uploads/.htaccess` ใช้ `<FilesMatch>` ปิดสคริปต์ทุกชนิด แล้ว `<Files "img.php">` เปิดเฉพาะตัวย่อรูป (ทดสอบกับ Apache 2.4 แล้ว)
- **ยังไม่แก้:** mode development — ต้องแก้ที่ต้นทาง (หรือย้ายไลบรารีออกจาก uploads/)

### C5. `admweb/plugins/db/function/php_v8.php` — เชื่อมต่อด้วย `charset=utf8` (= utf8mb3)
- บันทึกอีโมจิไม่ได้ (`SQLSTATE[22007] 1366 Incorrect string value`)
- ข้างนอก: โค้ดของเราสั่ง `DB::singleton()->query('SET NAMES utf8mb4')` (`modules/stock/function.php`, `themes/aostock/include/function.php`) — ทดสอบแล้วบันทึก / อ่านอีโมจิ + `' " \` ได้ถูกต้อง

### C6. `admweb/include/fix.req.php` — แก้ค่าใน `$_GET/$_POST/$_REQUEST` ของ 17 key (username, password, email, id, ac, ...)
- ตัด `'` กับ `=` ทิ้ง แปลง `"` เป็น `&quot;` และ addslashes — รหัสผ่านที่มี `'` หรือ `=` ใช้ไม่ได้ในฟอร์มที่ใช้ชื่อช่องพวกนี้
- ข้างนอก: ฟอร์มของ AOSTOCK ใช้ชื่อช่องอื่น (`adm_user`, `adm_pass`)

---

## D. ยังไม่แก้ — แจ้งให้ทราบ

| # | ที่ | เรื่อง |
|---|---|---|
| D1 | `plugins/db/function/php_v8.php` | ไม่มี `beginTransaction / commit / rollBack` (`$pdo` เป็น private) — AOSTOCK ใช้ `query('START TRANSACTION')` แทน |
| D2 | `plugins/db/function/php_v8.php:79` `halt()` | echo SQL + params + ข้อความ error ออกหน้าเว็บ |
| D3 | `plugins/db/function/func_v8.php` `DB_UP` / `DB_DEL` / `DB_UPSERT` | `prepare()` จับ exception เองแล้วคืน false แต่ helper ไม่เช็ก → คืน `true` แม้ล้มเหลว · `DB_ADD` คืน `lastInsertId` แม้ล้มเหลว |
| D4 | `plugins/db/function/func_v8.php` `$aQData` | cache ผล SELECT ต่อ request แต่ไม่ล้างหลังเขียน → อ่าน–เขียน–อ่านใน request เดียวได้ค่าเก่า |
| D5 | `plugins/config/function.php` `_update_config_keys` | ต่อค่าเข้า SQL ตรง ๆ — ค่าที่มี `'` บันทึกไม่ได้ / SQL injection ฝั่งแอดมิน |
| D6 | `include/class.permit.php:64` `_PERMIT` | หน้าที่ยังไม่ได้ลงทะเบียนสิทธิ์ → ให้ทุกคนที่ login เปิดได้ (fail-open) จนกว่า superadmin จะลงทะเบียน |
| D7 | `include/class.login.php` (หลัง login สำเร็จ) | `file_get_contents()` ไป `http://www.aosoft.co.th/user/request.php` (URL ซ่อนด้วย base64, ไม่ใช่ https) ทุกครั้ง · `notifyLineMessage()` ใช้ token ฝังในโค้ด — LINE Notify ปิดบริการแล้ว (31 มี.ค. 2025) |
| D8 | `setup.php:48` | รหัสผ่านเริ่มต้นของ admin = `ADMIN_INSTALL_PASSWORD` ในไฟล์ fix (ค่าตัวอย่างเดาง่าย) |
| D9 | `include/conf.ini.php` | เปิด PDO อีกตัว + `SHOW TABLES` ทุก request · เลือกไฟล์ fix จาก `SERVER_NAME` (ไม่มี fix ของ localhost) |
| D10 | `admbuilder/views/html.nav.php` (5 ลิงก์), `template/version2018/js/nifty-demo.php:8`, โมดูล aobuilder (`'page' => '/home'`) | เขียน `/admweb/...` / `/...` ตายตัว — ใช้ในโฟลเดอร์ย่อยไม่ได้ (ตอนนี้สองไฟล์แรกไม่มีใครเรียก) |
| D11 | `mainApi.php:3` | `error_reporting(0)` ปิด error ของทุกหน้าที่ include |
| D12 | `include/class.threat.php` | WAF แบบ regex บล็อกข้อความปกติได้ (เช่น `"Shirt; type A"`) · ข้ามทั้งหมดเมื่อ admin login อยู่ · บล็อก user-agent curl / wget (cron ที่เรียกผ่านเว็บโดนด้วย) |

---

## E. เอกสาร `ADMWEB.md` ไม่ตรงกับโค้ด

- URL Reinstall คือ `index.php?module=siteconfig&mp=db` (ไม่ใช่ `module=db&mp=db`)
- `$sqlAlter` ต้องเป็น `$sqlAlter[ตาราง][คอลัมน์] = "ALTER ..."` — แบบ list `$sqlAlter[] = ...` ตัวติดตั้งไม่อ่าน
  และไม่ต้องใส่ `ADD COLUMN IF NOT EXISTS` (ตัวติดตั้งเช็กคอลัมน์ให้เอง · syntax นี้เป็นของ MariaDB)
- ค่าคงที่ (`_DBPREFIX_`, `URL_WEB_ROOT`, `PATH_UPLOAD` ฯลฯ) มาจากไฟล์ `fix.<โดเมน>.php` ไม่ใช่ `conf.ini.php`
- `DB_LIST_OR`, `DB_LIST_CUS` ไม่มีในโค้ด · `REQ_get(..., 'float')` ไม่รองรับ (คืนค่าดิบ)
- `DB_UP` / `DB_DEL` ไม่ยอมทำงานถ้า WHERE ว่าง (เอกสารบอกว่าจะ update ทั้งตาราง) และคืน true แม้ล้มเหลว (ดู D3)
- `doAjax.php` redirect ไปหน้า login เมื่อยังไม่ login (เอกสารบอกว่าไม่ redirect)
- `PERMIT` `'SET'` ไม่ได้ลงทะเบียนสิทธิ์เอง — เขียนลง `uploads/permission/req.txt` แล้ว superadmin ต้องลงทะเบียน (ดู D6)
