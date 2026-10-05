# ADMWEB (Aowebdata v8) — คู่มือฉบับรวม

> ไฟล์นี้รวม **SKILL.md** (สรุป conventions หลัก) เข้ากับ **admweb-guide.md** (คู่มือฉบับเต็ม) ไว้ในไฟล์เดียว
> ส่วนที่ 1 คือสรุปย่อ, ส่วนที่ 2 คือคู่มือฉบับเต็มที่อ้างถึง (§0–§9)

---

# ส่วนที่ 1 — สรุป (จาก SKILL.md)



admweb is a **module-based admin backend**. Each feature is one module folder under
`admweb/aowebdata/modules/{name}/`. A central router in `admweb/index.php` maps URL params
to page files. When writing any admweb code, follow the conventions below — they are what
make the code actually work in this framework.

For anything not covered here in full detail, read **`references/admweb-guide.md`** (the
complete guide). Section pointers are given throughout.

## Working rules (follow every time)

1. **Plan first, then wait for confirmation** before writing code.
2. **Report every file changed** — after each edit, list which files were **added/edited**
   with their paths, without being asked.
3. **Ask before deciding** when you hit a choice or are unsure — don't decide unilaterally.
4. **Spell out the work before asking for confirmation** — say *which file + what change*,
   never a vague "shall I proceed?".

## Router & boot (guide §0)

- URL pattern: `index.php?module={module}&mp={page}&ac={action}&id={id}`
- `module=x&mp=y` loads `aowebdata/modules/{x}/main_{y}.php` (fallback `core/{x}/...`).
- Constants: `_MODULE_`, `_MP_`, `_AC_` (= the `ac` value), `_DBPREFIX_` (table prefix),
  `_TIME_`, `PATH_UPLOAD`, `URL_UPLOAD`, `PATH_PLUGIN`, `URL_WEB_ROOT` (absolute site URL).
- A module's `function.php` is auto-included on the **admin** side; its `api.php` is
  auto-included on the **public** side (via `admweb/mainApi.php`).

## Database API (guide §1) — the core helpers

Never call `DB::singleton()` directly; use these (from `plugins/db/function/func_v8.php`):

```php
DB_GET($table, $where=[], $orderby='')          // 1 row (assoc array) or false
DB_LIST($table, $where=[], $limit=0, $page=1, $orderby='')  // wrapper: ['data','num_rows','maxpage','nextpage']
DB_LIST_OR($table, $whereOR=[], $limit, $page, $orderby)    // WHERE joined by OR
DB_JOIN($sql, $params=[], $limit=0, $page=1, $orderby='')   // raw SELECT + JOIN (SELECT only!)
DB_ADD($table, $data)                            // INSERT → returns insert_id
DB_UP($table, $data, $where)                     // UPDATE (multi-AND where) → bool
DB_DEL($table, $where)                           // DELETE (multi-AND where) → bool
```

WHERE array syntax:

```php
['col' => $v]                    // col = v   (multiple keys = AND)
['col' => ['>=', $v]]            // operators: =,!=,<,<=,>,>=,LIKE,NOT LIKE
['col' => ['IN', [1,2,3]]]       // IN / NOT IN
// orderby is the LAST arg: 'ORDER BY add_date DESC'
```

Critical rules:
- `DB_GET` returns **false** when not found → guard with `is_array()` before using keys.
- `DB_LIST` returns a **wrapper**, not rows → iterate `$x['data']`, count `$x['num_rows']`.
- Always pass a WHERE to `DB_UP`/`DB_DEL` (missing where = whole table). For user-owned
  data, include `user_id` in the where.
- No native atomic increment helper — for a counter, read then `DB_UP(..., value+1)`.
- `DB_JOIN` is SELECT-only (it fetches rows); don't use it for UPDATE/DELETE.

## Standard admin page (guide §4) — `main_xxx.php`

Every page = **Permission check → read params → action handler (do + redirect + exit) →
fetch data → render**:

```php
<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด [ชื่อหน้า] ได้', 'redirect', 'SET');
$ac = REQ_get('ac', 'request', 'str');
$id = REQ_get('id', 'get', 'int');

if ($ac == 'delete' && $id > 0) {
    $row = DB_GET('items', ['item_id' => $id]);
    if (is_array($row)) { DB_DEL('items', ['item_id' => $id]); setRaiseMsg('ลบสำเร็จ', _TIME_, 0); }
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_); exit;
}

$aData = DB_LIST('items', [], 100, 1, 'ORDER BY add_date DESC');
?>
<!-- render with Nifty theme classes; echo displayRaiseMsg(); ...  -->
```

Helper functions: `REQ_get($key,'get|post|request','str|int|float',$default)`,
`setRaiseMsg($msg,$time,$isError)` (isError 0=ok,1=error), `displayRaiseMsg()`,
`CustomRedirectToUrl($url)`, `_admin_buil_link($url)`, `BuilListPage($aData,$url,$page)`,
`login_logout::getLoginData()` (`->user_id`, `->status` where `'admin'` = admin/superadmin).

## Creating a module (guide §3)

Minimum files in `aowebdata/modules/{name}/`: `aModuleConfig.php` (register `$aTablename`,
`$aPermission`), `__menu.php` (sidebar), and at least one `main_*.php`. Then **register the
module name in `$aModuleUse`** inside `aowebdata/fix.{domain}.php` or the menu won't load.
Optional: `function.php` (helper ของ module — โหลดเฉพาะตอนเปิด module นั้นบน admin), `api.php` (ฟังก์ชันสำหรับ endpoint นอก admweb — โหลดบน public `mainApi.php`), `__funcGlobal.php` (helper ที่เรียกได้แม้ไม่เปิด module นั้น — โหลดทุก module ทั้ง admin+public), `database.php` (schema), `js.php`/`css.php`.

## Tables & install (guide §2)

Define schema with `$sqlArray[_DBPREFIX_ . 'tbl'] = "CREATE TABLE IF NOT EXISTS ..."` and
add columns with `$sqlAlter[] = "ALTER TABLE ... ADD COLUMN IF NOT EXISTS ..."`. Preferred
location for a self-contained module = **`modules/{name}/database.php`** (auto-loaded by
`inc_install.php`). Also list every table in the module's `aModuleConfig.php` `$aTablename`.
Apply changes via **Reinstall**: `index.php?module=db&mp=db` (idempotent — skips existing).
Central project schema lives in `admweb/databases/mysql_*.sql.php` (included via
`mysql_default.sql.php`).

## Permissions (guide §5)

First line of every admin page:
`PERMIT::_PERMIT(_MODULE_, 'module|mp', 'ข้อความสิทธิ์', 'redirect', 'SET');`
`'module|mp'` = per-page (most common). `'SET'` auto-registers the permission when a
superadmin first opens the page. Multi-tenant tip: filter reads by the owner's `user_id`
and verify ownership before edit/delete to prevent IDOR; `status='admin'` bypasses.

## File uploads — UpFile plugin (guide §6.5)

Use the plugin, don't hand-roll `move_uploaded_file`. It is **not auto-loaded**:

```php
require_once PATH_PLUGIN . '/uploadfile/UpFile.php';
$up = new UpFile();                       // baseDir = PATH_UPLOAD
$savedRel = $up->uploadStandard(['pdf','doc','docx'], $_FILES['f']['tmp_name'],
    $_FILES['f']['name'], 'subfolder/'.$id, $customNameNoExt /*optional*/, $oldPath /*optional, deletes it*/);
// returns relative path from PATH_UPLOAD, or false. Does NOT check size — check $_FILES size yourself.
```

## Public endpoints (guide §0, §3)

A public root file just does `include __DIR__ . '/inc.php';` (→ bootstraps `mainApi.php`,
loading DB helpers, constants, and each module's `api.php`) then calls a function defined in
the module's `api.php`. Put reusable public functions in `modules/{name}/api.php`.

## Common pitfalls (guide §8)

- "Undefined array key" → `DB_GET` returned false; add `is_array()` check.
- Redirects to login → permission not yet registered; open the page as superadmin once.
- Menu missing → forgot `__menu.php` or the module in `$aModuleUse`.
- Schema not created → missing `IF NOT EXISTS` or table not in `aModuleConfig.php`.
- Escape output with `htmlspecialchars()`; only mutate through the DB helpers (no raw SQL
  with user input).

**Full reference:** `references/admweb-guide.md` contains the complete guide with every
example, the full cheat sheet (§7), troubleshooting table (§8), and FAQ (§9). Read it when
you need detail beyond the summary above.

## AJAX — `doAjax.php` (guide §6.6)

AJAX ไม่ผ่าน `index.php` แต่ผ่าน entry แยก **`doAjax.php`** (root เดียวกับ index.php) ซึ่ง
**ไม่ครอบ layout** ตั้งชื่อไฟล์ปลายทางเป็น **`ajax_{mp}.php`** (ไม่ใช่ `main_{mp}.php`)
เรียกแบบลิงก์ปกติ `doAjax.php?module=X&mp=Y&ac=Z` แล้ว `echo json_encode(...)` ออกได้ตรง ๆ
doAjax โหลด DB/REQ_get/`function.php`/`__funcGlobal.php` ให้ และเช็ก login เก็บใน `$ok`
(ไม่ redirect — ต้องกันสิทธิ์เอง) รายละเอียด/ตัวอย่างอยู่ใน §6.6 ของ `references/admweb-guide.md`

## CSS / JS — `css.php` / `js.php` (guide §6.7)

CSS และ JS ของ module ต้องเขียนใน `modules/{module}/css.php` (โหลดเข้า `<head>`) และ
`js.php` (โหลดก่อน `</body>`) ที่ธีม auto-load ให้ — **ห้าม** เขียน `<style>`/`<script>` inline
ใน `main_*.php` และ **ห้าม** ใส่ `style="..."` ใน HTML tag ให้ตั้ง class + เขียน rule ใน css.php,
ผูก event แบบ delegation ใน js.php, ส่งค่า PHP→JS ผ่าน `data-*`. JS ที่ใช้ร่วมทุก module ไปที่
`__funcGlobal.js.php`. รายละเอียดใน §6.7 ของ `references/admweb-guide.md`

**ห้ามกำหนดขนาด font เอง** — อย่า hardcode `font-size` (inline/`css.php`) ปล่อยตามค่าปกติของ template ถ้าจุดไหนต้องปรับ ผู้ใช้จะแจ้งเป็นรายกรณีตอนทำงาน

**PHP style** — format ด้วย Intelephense (VSCode), ยึดไฟล์เดิม, **tab indent**; เงื่อนไขสั้นใช้ ternary `($a == $b) ? 'x' : 'y'` แทน `if` ไม่มีวงเล็บ, if/else จริงใส่ `{ }` เสมอ; เปิด PHP ด้วย `<?php` เสมอ ห้าม short tag (`<?`/`<?=`)


---

# ส่วนที่ 2 — คู่มือฉบับเต็ม (admweb-guide.md)

# ADMWEB Developer Guide

**แก้ไขล่าสุด :** 01/07/2026  
**ใช้งานใน :** pdpa

คู่มือสำหรับนักพัฒนา (และ AI) ในการต่อยอด/แก้ไขระบบ **Aowebdata v8** (admweb)
ครอบคลุม: DB API, การสร้างตาราง, การสร้าง module, โครงสร้างไฟล์หน้า, permission system

---
## แนวทางการทำงาน (สำคัญ — ยึดทุกครั้ง)

1. **วางแผนก่อนเสมอ** 
2. **รอการยืนยันก่อนเริ่ม** — เมื่อวางแผนเสร็จต้องรอ user ยืนยันก่อนลงมือ
3. **แจ้งไฟล์ที่แก้ไขทุกครั้ง** — หลังแก้ไขเสร็จทุกครั้งต้องสรุปว่ามีไฟล์ใดถูก **แก้ไข/เพิ่มใหม่** พร้อม path ทุกครั้งโดยไม่ต้องรอให้ถาม
4. **ถามก่อนตัดสินใจ** — เจอทางเลือกหรือไม่แน่ใจต้องถามก่อน ห้ามตัดสินใจเองโดยพลการ
5. **แจกแจงงานก่อนขอยืนยันเสมอ** — ระบุ **ไฟล์ใด + ทำอะไร** ก่อนขอยืนยัน ห้ามถามลอยๆ ว่า "จะทำอะไร"

## 0. ภาพรวมระบบ admweb

ระบบ admweb คือ **admin backend framework** ที่ใช้ pattern แบบ "module-based" — แต่ละ feature เป็น 1 module อยู่ใน folder แยก โดยมี router กลางใน `admweb/index.php` ที่ map URL params (`module=xxx&mp=yyy`) เป็นไฟล์ `main_yyy.php` ในโฟลเดอร์ module นั้น

**URL pattern:** `index.php?module={moduleName}&mp={page}&ac={action}&id={id}`

### Boot flow ของ admin
1. Browser เข้า `admweb/index.php?module=mymodule&mp=list`
2. `index.php` โหลด config (`include/conf.ini.php`) → กำหนด constants เช่น `PATH_ADMIN`, `PATH_MODULE`, `PATH_CORE`, `_DBPREFIX_`
3. โหลด core libraries: `db.php`, `function.php`, `func.userinfo.php`, `func.useronline.php`
4. แปลง `$_GET['module']` → constant `_MODULE_` และ `$_GET['mp']` → `_MP_`
5. หา file: `aowebdata/modules/{module}/main_{mp}.php` → ถ้าไม่เจอ fallback ไป `core/{module}/main_{mp}.php`
6. include `function.php` ของ module (ถ้ามี)
7. include ไฟล์หน้า → render ภายใน template `main.php`

### Path constants ที่ใช้บ่อย

| Constant | ค่า | ใช้สำหรับ |
|---|---|---|
| `PATH_ADMIN` | `/admweb` | root ของ admin |
| `PATH_MODULE` | `/admweb/aowebdata/modules` | **module ที่เราเขียนเอง** |
| `PATH_CORE` | `/admweb/core` | core module ที่มากับ framework (member, siteconfig, html, chat, ...) |
| `PATH_PLUGIN` | `/admweb/plugins` | plugin (db, articles, banner, seo, mail, ...) |
| `PATH_AOWEBDATA` | `/admweb/aowebdata` | data ของแต่ละโปรเจกต์ (databases, modules, permission) |
| `PATH_UPLOAD` | `/uploads` | upload directory (รูป, ไฟล์) |
| `URL_UPLOAD` | `/uploads` | URL public ของ upload |
| `_DBPREFIX_` | (เช่น `ao_`) | prefix ของชื่อตาราง |
| `_TIME_` | `time()` ตอน boot | Unix timestamp |

### โครงสร้างโฟลเดอร์ที่ควรรู้

```
admweb/
├─ index.php              ← router หลัก
├─ mainApi.php            ← public bootstrap (สำหรับฝั่ง public ที่ include)
├─ include/
│   ├─ conf.ini.php       ← constants & path
│   ├─ class.login.php    ← login + member_online (class-based)
│   ├─ class.permit.php   ← permission system
│   ├─ func.useronline.php ← MemberOnline() function
│   └─ function.php       ← common helpers
├─ plugins/db/function/
│   ├─ func_v8.php        ← DB_GET / DB_LIST / DB_ADD / DB_UP / DB_DEL ⭐
│   └─ php_v8.php         ← DB class (PDO singleton)
├─ core/                  ← core modules (อย่าแก้ตรงๆ)
│   ├─ member/
│   ├─ siteconfig/
│   ├─ html/
│   ├─ chat/
│   └─ ...
├─ aowebdata/             ← code ของโปรเจกต์
│   ├─ databases/         ← SQL schema (.sql.php files)
│   ├─ modules/           ← module ของโปรเจกต์ ⭐
│   ├─ permission/        ← permission registry
│   ├─ template/          ← template
│   └─ ...
└─ plugins/               ← reusable plugins (banner, articles, seo)
```

---

## 1. การเชื่อมต่อฐานข้อมูล — DB API

ระบบใช้ PDO singleton ผ่าน helper function ใน `plugins/db/function/func_v8.php` — **อย่า** เรียก `DB::singleton()` โดยตรง (เลือก helper ที่เหมาะกับ use case)

### 1.1 ฟังก์ชันหลักที่ต้องรู้

```php
// ── ดึงข้อมูล ────────────────────────────────────────────────────
DB_GET($table, $where = [], $orderby = '')
// ดึง 1 record (single row) — มี LIMIT 1 อัตโนมัติ
// คืน associative array, หรือ false ถ้าไม่เจอ

DB_LIST($table, $where = [], $limit = 0, $page = 1, $orderby = '')
// ดึงหลาย record + pagination
// คืน: ['data' => [...rows], 'num_rows' => N, 'maxpage' => N, 'nextpage' => N]

DB_LIST_OR($table, $whereOR = [], $limit = 0, $page = 1, $orderby = '')
// ดึงหลาย record แบบ OR (เงื่อนไขเชื่อมด้วย OR แทน AND)

DB_LIST_CUS($table, $whereStr)
// ดึงหลาย record แบบ raw WHERE — คืน flat array โดยตรง (ไม่มี wrapper)
// ใช้เมื่อ array syntax ไม่พอ เช่น BETWEEN, complex SQL function, 2 เงื่อนไขบนคอลัมน์เดียวกัน
// ⚠️ ใช้น้อยที่สุด — ไม่ปลอดภัยจาก SQL injection ถ้าใส่ user input ตรงๆ
// ถ้าในระบบไม่มีเรียกใช้ให้ลบ function นี้ออกเลย พร้อมเอาออกจาก admweb.md ให้ด้วย

DB_JOIN($sql, $aparams = [], $limit = 0, $page = 1, $orderby = '')
// ดึงข้อมูลแบบ raw SQL + JOIN + pagination (คืน wrapper เหมือน DB_LIST)

// ── เปลี่ยนแปลงข้อมูล ────────────────────────────────────────────
DB_ADD($table, $arrayData)
// INSERT — คืน insert_id

DB_UP($table, $arrayData, $whereArray)
// UPDATE — รองรับ WHERE หลายเงื่อนไข AND
// คืน bool

DB_DEL($table, $whereArray)
// DELETE — รองรับ WHERE หลายเงื่อนไข AND
// คืน bool (false ถ้า prepare fail)
```

### 1.2 รูปแบบ WHERE array (สำคัญมาก)

```php
// ── เปรียบเทียบเท่ากัน ────────────────────────────
['col' => $val]                   // col = val
['col1' => $v1, 'col2' => $v2]    // col1 = v1 AND col2 = v2

// ── operator อื่นๆ ─────────────────────────────────
['col' => ['>=', $val]]           // col >= val
['col' => ['!=', $val]]           // col != val
['col' => ['LIKE', '%abc%']]      // col LIKE '%abc%'
// รองรับ: =, !=, <, <=, >, >=, LIKE, NOT LIKE

// ── IN / NOT IN ────────────────────────────────────
['col' => ['IN', [1, 2, 3]]]      // col IN (1, 2, 3)
['col' => ['NOT IN', ['a', 'b']]] // col NOT IN ('a', 'b')

// ── ORDER BY (parameter ตัวสุดท้าย) ─────────────────
'ORDER BY add_date DESC'
'ORDER BY RAND()'                 // รองรับ
'ORDER BY col1 ASC, col2 DESC'
```

### 1.3 ตัวอย่างการใช้งานจริง

```php
// ดึง user 1 คน
$user = DB_GET('member_user', ['user_id' => $userId]);
if (is_array($user)) { ... }

// ดึง banner ที่ active 1 ตัวแบบสุ่ม
$banner = DB_GET('banners', [
    'position' => 'Home-A',
    'status'   => 1,
    'end_date' => ['>=', date('Y-m-d H:i:s')],
], 'ORDER BY RAND()');

// list รายการ 10 รายการ หน้าที่ 2
$items = DB_LIST('items', ['display' => 1], 10, 2, 'ORDER BY sort_date DESC');
foreach (($items['data'] ?? []) as $row) { ... }
echo "ทั้งหมด: " . $items['num_rows'];

// list user ที่อยู่ใน list ID
$users = DB_LIST('member_user', ['user_id' => ['IN', [1, 5, 9]]]);

// search แบบ OR (LIKE หลาย field)
$result = DB_LIST_OR('items', [
    'item_name' => ['LIKE', "%$kw%"],
    'desc'      => ['LIKE', "%$kw%"],
], 20, 1, 'ORDER BY add_date DESC');

// JOIN query
$result = DB_JOIN("
    SELECT p.*, u.name AS author_name
    FROM `" . _DBPREFIX_ . "items` p
    LEFT JOIN `" . _DBPREFIX_ . "member_user` u ON u.user_id = p.user_id
    WHERE p.display = :display
", [':display' => 1], 20, $page, 'ORDER BY p.add_date DESC');

// ใส่ record ใหม่
$newId = DB_ADD('items', [
    'title'    => $title,
    'content'  => $content,
    'user_id'  => $userId,
    'add_date' => date('Y-m-d H:i:s'),
]);

// อัปเดต
DB_UP('items',
    ['display' => 0, 'edit_date' => date('Y-m-d H:i:s')],
    ['item_id' => $id]
);

// ลบ
DB_DEL('items', [
    'item_id' => $id,
    'user_id' => $userId,  // ป้องกันลบของคนอื่น
]);
```

### 1.4 กฎที่ต้องจำ

1. **ตรวจค่า return ทุกครั้ง** — `DB_GET` คืน `false` ถ้าไม่เจอ; ห้ามใช้ `$x['key']` โดยไม่ check
   ```php
   $row = DB_GET('items', ['item_id' => $id]);
   if (!is_array($row)) { /* not found */ return; }
   ```

2. **`DB_LIST` คืน wrapper** — ไม่ใช่ rows ตรงๆ
   ```php
   $list = DB_LIST('items');
   foreach (($list['data'] ?? []) as $row) { ... }
   $count = $list['num_rows'] ?? 0;
   ```

3. **`DB_LIST_CUS` คืน flat array** — ไม่มี wrapper
   ```php
   $rows = DB_LIST_CUS('member_online', "onlineEndTime >= UNIX_TIMESTAMP()");
   foreach ($rows as $r) { ... }
   ```

4. **ใส่ where ทุกครั้งใน DB_UP / DB_DEL** — ลืม where = อัปเดต/ลบทั้งตาราง

5. **`DB_UP` กับ user data** — ใส่ user_id ใน where เสมอ กันคนอื่นอัปเดตของคนอื่น
   ```php
   DB_UP('items', $newData, ['item_id' => $id, 'user_id' => $userId]);
   ```

### 1.7 Counter System (DB-based)

ระบบนับ page view — เก็บข้อมูลใน `site_counter` แบบ **atomic** ไม่มี race condition

**ตาราง:**
- `site_counter` — Composite PK `(page, type, year, month, day, hour, device)`
- `site_counter_ref` — Composite PK `(page, ref_domain, year, month)` สำหรับ referrer tracking

**Schema อยู่ที่:** `admweb/core/counter/database.php` (auto-load ผ่าน module system)

```php
// ── เรียกที่หน้า public (ต้น file ก่อน HTML output) ──────────
func_counter_set('home.php', 'web');   // ระบุชื่อหน้าชัดเจน (แนะนำ)
func_counter_set();                    // auto-detect จาก SCRIPT_NAME

// ── อ่านข้อมูล ────────────────────────────────────────────────
// ยอดรายเดือน (ใช้ใน dashboard)
$data = func_counter_get('05-2026', 'web');
$monthTotal = $data['month'][0];  // ยอดรวมทั้งเดือน
$day1       = $data['month'][1];  // ยอดวันที่ 1

// ยอดรวมทั้งหมด
$data = func_counter_get('', 'web');
$allTime = $data['all'];
$perYear = $data['year'];  // [2025 => N, 2026 => N]

// ยอดเดี่ยว
$today = func_counter_txt('d', 'web');   // วันนี้
$month = func_counter_txt('m', 'web');   // เดือนนี้
$year  = func_counter_txt('y', 'web');   // ปีนี้
$all   = func_counter_txt('', 'web');    // ทั้งหมด

// top pages
$pages = func_counter_page_get(2026);
// ['home.php' => [2026 => [1 => 100, 2 => 200, ...]], ...]

// ── Analytics (Phase 2) ───────────────────────────────────
// peak hour — [0=>N, 1=>N, ..., 23=>N]
$hours = func_counter_get_hours(2026, 5, 'web');

// device split — ['mobile'=>N, 'desktop'=>N]
$devices = func_counter_get_devices(2026, 5, 'web');

// top referrers — [['ref_domain'=>'...', 'total'=>N], ...]
$refs = func_counter_get_top_refs(2026, 0, 10);  // month=0 = ทั้งปี
```

**หมายเหตุ:** ถ้าตารางยังไม่ถูกสร้าง ทุก function return เงียบๆ ไม่ throw error
- `_counter_table_exists()` — เช็คตาราง site_counter
- `_counter_ref_table_exists()` — เช็คตาราง site_counter_ref

---

## 2. การสร้างตารางใหม่

ตาราง schema จัดเก็บใน `admweb/databases/mysql_*.sql.php` — แต่ละไฟล์ define `$sqlArray[ชื่อตาราง]` ที่เก็บ CREATE TABLE statement (auto-install ใช้ตอน setup)

### 2.1 รูปแบบ schema file

ไฟล์ที่ใช้ในโปรเจกต์:
- `mysql_default.sql.php` — ตารางพื้นฐาน (member_user, member_online, site_configs)
- `mysql_{projectname}.sql.php` — ตาราง custom ของโปรเจกต์
- `mysql_articles.sql.php` — ตาราง articles
- `mysql_insert.sql.php` — INSERT default data (categories, configs)

### 2.2 รูปแบบ define ตาราง

```php
<?php
// admweb/databases/mysql_{projectname}.sql.php

$sqlArray[_DBPREFIX_ . 'categories'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "categories` (
  `cate_id`   int AUTO_INCREMENT PRIMARY KEY,
  `cate_name` varchar(32) NOT NULL,
  `cate_icon` varchar(255),
  `sort`      int(4) NOT NULL DEFAULT 0,
  `display`   tinyint(1) NOT NULL DEFAULT 1,
  `add_date`  datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
";
```

**กฎที่ต้องทำตาม:**

1. **ใช้ `IF NOT EXISTS`** เพื่อไม่ error ตอน install ซ้ำ
2. **ใช้ `_DBPREFIX_`** ทั้งใน key ของ `$sqlArray` และในชื่อตารางใน SQL
3. **PK ต้องชัดเจน** — `int AUTO_INCREMENT PRIMARY KEY` หรือ composite PK
4. **add_date / edit_date** ใช้ `datetime DEFAULT CURRENT_TIMESTAMP` (และ `ON UPDATE CURRENT_TIMESTAMP` สำหรับ edit_date)
5. **ใส่ comment ด้วย**
6. **InnoDB + utf8** เป็น default

### 2.3 ขั้นตอนเพิ่มตารางใหม่

**กรณีโปรเจกต์เริ่มต้น (auto-install)**

1. เปิด `admweb/databases/mysql_{ชื่อหมวด}.sql.php` (สร้างไฟล์ใหม่ได้ถ้าจำเป็น)
2. เพิ่ม `$sqlArray[_DBPREFIX_ . 'newtable']` พร้อม CREATE TABLE
3. **เพิ่มชื่อตารางใน `aModuleConfig.php` ของ module ที่เกี่ยวข้องเสมอ** — ดู section 3.2
   ```php
   // aowebdata/modules/{name}/aModuleConfig.php
   $aTablename = array(
       _DBPREFIX_ . 'items',
       _DBPREFIX_ . 'newtable',  // ← เพิ่มตรงนี้
   );
   ```
4. ถ้าสร้างไฟล์ `.sql.php` ใหม่ → register ไฟล์นั้นใน `aowebdata/databases/aModuleConfig.php` ด้วย
5. ทำ comment header (FILE/ROLE/DEPENDS/TABLES/TODO) เพื่อให้ AI เข้าใจ

**กรณีโปรเจกต์ที่ deploy แล้ว (live DB)**

**ไม่ต้องสร้างไฟล์ `.sql` แยก** — ระบบ admweb จัดการให้อัตโนมัติผ่าน `inc_install.php`:

- **ตารางใหม่** → เพิ่ม `$sqlArray` แล้วสั่ง **Reinstall** ผ่านหน้า `index.php?module=db&mp=db` ระบบจะ CREATE TABLE ที่ยังไม่มีให้อัตโนมัติ (ข้ามตารางที่มีอยู่แล้ว)
- **column ใหม่** → เพิ่ม `$sqlAlter` แล้วสั่ง Reinstall ระบบจะเช็คว่า column มีอยู่แล้วหรือไม่ก่อนรัน ALTER — ไม่ re-run ซ้ำ

```php
// ใช้ $sqlAlter แทนการสร้างไฟล์ .sql แยก
$sqlAlter[_DBPREFIX_ . 'items']['new_column'] = "
ALTER TABLE `" . _DBPREFIX_ . "items`
ADD COLUMN IF NOT EXISTS `new_column` varchar(255) NULL AFTER `title`;";
```

> **สรุป:** `$sqlArray` + `$sqlAlter` + Reinstall = ไม่ต้องสร้างไฟล์ `.sql` สำหรับ live DB เลย

### 2.4 การเพิ่ม Column ด้วย ALTER TABLE (`$sqlAlter`)

ใช้ตัวแปร `$sqlAlter` ในไฟล์ `.sql.php` เดียวกัน — ระบบจะรัน ALTER เหล่านี้ตอน setup/update อัตโนมัติ (เหมือน `$sqlArray` แต่ใช้สำหรับ ALTER แทน CREATE)

```php
<?php
// admweb/databases/mysql_{projectname}.sql.php

// ── สร้างตาราง ────────────────────────────────────────────────────
$sqlArray[_DBPREFIX_ . 'items'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "items` (
  `item_id`   int AUTO_INCREMENT PRIMARY KEY,
  `title`     varchar(255) NOT NULL,
  `display`   tinyint(1) NOT NULL DEFAULT 1,
  `add_date`  datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
";

// ── เพิ่ม column ในตารางที่มีอยู่แล้ว ────────────────────────────
$sqlAlter[] = "ALTER TABLE `" . _DBPREFIX_ . "items`
    ADD COLUMN IF NOT EXISTS `slug` varchar(255) DEFAULT NULL AFTER `title`;";

$sqlAlter[] = "ALTER TABLE `" . _DBPREFIX_ . "items`
    ADD COLUMN IF NOT EXISTS `sort` int(4) NOT NULL DEFAULT 0 AFTER `slug`;";

// เพิ่ม INDEX
$sqlAlter[] = "ALTER TABLE `" . _DBPREFIX_ . "items`
    ADD INDEX IF NOT EXISTS `idx_display` (`display`);";
```

**กฎของ `$sqlAlter`:**

1. **ใช้ `ADD COLUMN IF NOT EXISTS`** — กัน error ถ้ารัน migration ซ้ำ (MySQL 8.0+)
2. **`$sqlAlter[]`** — เป็น array ธรรมดา ไม่ต้องมี key (ระบบ loop รันตามลำดับ)
3. **ใส่ทีละ statement** — 1 `$sqlAlter[]` = 1 ALTER statement
4. **ใช้ `_DBPREFIX_`** ในชื่อตารางเสมอ
5. **สำหรับ MySQL < 8.0** ที่ไม่รองรับ `IF NOT EXISTS` ใน ALTER → ต้องเช็ค column ก่อนรันเอง หรือใช้ไฟล์ `.sql` migration แทน

**เมื่อไรใช้ `$sqlAlter` vs ไฟล์ `.sql` migration:**

| กรณี | ใช้ |
|---|---|
| โปรเจกต์ยัง dev / reinstall ใหม่ได้ | `$sqlAlter[]` ใน `.sql.php` |

### 2.5 Schema แบบ Module-based (`database.php` ใน module)

แทนที่จะเพิ่ม schema ลง `mysql_default.sql.php` ตรงๆ สามารถสร้าง **`database.php` ไว้ใน module เอง** เพื่อให้ module เป็นอิสระ พกตัวเองไปติดตั้งที่อื่นได้โดยไม่ต้องแก้ไฟล์กลาง

ระบบจะ **auto-load** `database.php` ของทุก module ใน `$aModuleUse` อัตโนมัติผ่าน `setupModuleDB.php` และ `inc_install.php`

```php
<?php
// admweb/core/{module}/database.php  หรือ  admweb/aowebdata/modules/{module}/database.php

/**
 * FILE: admweb/core/counter/database.php
 * ROLE: Schema ของ counter module
 * TABLES: site_counter
 */

$sqlArray[_DBPREFIX_ . 'site_counter'] = "
CREATE TABLE IF NOT EXISTS `" . _DBPREFIX_ . "site_counter` (
  `page`   varchar(255) NOT NULL DEFAULT '',
  `type`   varchar(20)  NOT NULL DEFAULT 'web',
  `year`   smallint     NOT NULL,
  `month`  tinyint      NOT NULL,
  `day`    tinyint      NOT NULL,
  `count`  int UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (page, type, year, month, day),
  INDEX idx_ym (year, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";
```

**เมื่อไรใช้ `database.php` ใน module vs `mysql_*.sql.php` กลาง:**

| กรณี | ใช้ |
|---|---|
| ตาราง core framework (member, online, config) | `mysql_default.sql.php` |
| ตาราง custom ของโปรเจกต์ (project-specific) | `mysql_{projectname}.sql.php` |
| Module ที่ต้องการ **เป็นอิสระ** / reusable ข้ามโปรเจกต์ | `{module}/database.php` ⭐ |

**กฎ:** ไฟล์ `database.php` ใช้ตัวแปร `$sqlArray` และ `$sqlAlter` เหมือนกันทุกอย่าง ต่างแค่ที่วาง

### 2.6 ตัวอย่างชื่อ field ที่ตรงกับ convention

```
id field ของตาราง xxx:  xxx_id  (cate_id, item_id, shop_id)
foreign key ของ user:   user_id
timestamps:             add_date, edit_date, sort_date
status flag:            display (0/1), is_open (0/1), status (string)
sort order:             sort (int)
count:                  view_count, rating
```

---

## 3. การสร้าง module ใหม่

Module = โฟลเดอร์ใน `admweb/aowebdata/modules/{name}/`

### 3.1 ไฟล์ที่ต้องมีในทุก module

```
aowebdata/modules/{name}/
├─ aModuleConfig.php       ← required: register ตาราง + permission + dependencies
├─ __menu.php              ← required: menu บน sidebar admin
├─ function.php            ← optional: helper functions ของ module นี้
├─ main_{page}.php         ← หน้าต่างๆ (1 หน้า = 1 ไฟล์)
├─ main_{page}_edit.php    ← หน้าฟอร์ม add/edit
├─ hooks/                  ← optional: hook (เช่น hooks_admin_main.php สำหรับ dashboard)
├─ config/                 ← optional: data schema ของ config (form fields)
├─ option/                 ← optional: partial template (re-usable section)
└─ js.php                     ← optional: JS ของ module
└─ css.php                     ← optional: CSS ของ module
```

### 3.2 `aModuleConfig.php` (required)

ไฟล์นี้ register สิ่งที่ module ต้องการเพื่อระบบ install / permission ทำงานถูก

```php
<?php
// admweb/aowebdata/modules/{name}/aModuleConfig.php

// ตารางที่ module นี้ใช้/สร้าง (ใช้ตอน backup และ install)
$aTablename = array(
    _DBPREFIX_ . 'items',
    _DBPREFIX_ . 'item_images',
);

// path/file permission ที่ต้องการ writable
$aPermission = array(
    PATH_UPLOAD,
);

// PHP function ที่ต้องเปิด (เช็คตอน install)
$aFunctionReq = array(
    'filesize',
    'imagecreate',
);
```

### 3.3 `__menu.php` (required)

ไฟล์นี้ define sidebar menu สำหรับ admin

```php
<?php
$title       = 'MY MODULE';
$des         = 'อธิบายว่า module นี้ใช้ทำอะไร';
$_aMenuList  = array();
$_aMenuList['title'] = $title;
$_aMenuList['name']  = $title;
$_aMenuList['node']  = 'head';

$module_name = 'mymodule';  // ชื่อ folder ของ module ตัวเอง

// build link helper
$link_list = 'index.php?module=' . $module_name . '&mp=list';
$link_edit = 'index.php?module=' . $module_name . '&mp=edit';

// ── เมนูระดับ 1 (parent) ──
$subname = 'list';
$_aMenuList['subhead'][$subname] = array(
    'name'           => 'รายการทั้งหมด',
    'headertitle'    => '',
    'link'           => $link_list,
    'target'         => '',
    'openPermission' => array('admin'),
    'class'          => 'fa fa-list',
    'menu'           => array(),
);

// ── ถ้าจะมี sub-menu ──
$_aMenuList['subhead'][$subname]['menu'][] = array(
    'name'   => 'หน้าย่อย 1',
    'link'   => $link_list . '&filter=foo',
    'help'   => '',
    'target' => '',
);
```

### 3.4 `function.php` (optional)

Helper function ที่ใช้เฉพาะ module — function.php ของ module จะถูก auto-include เมื่อ module นั้นถูกเปิด

```php
<?php
// admweb/aowebdata/modules/{name}/function.php

function MyModule_getRecentItems($limit = 10)
{
    return DB_LIST('items', ['display' => 1], $limit, 1, 'ORDER BY add_date DESC');
}

function MyModule_formatStatus($status)
{
    $map = [0 => 'ปิด', 1 => 'เปิด', 2 => 'รออนุมัติ'];
    return $map[$status] ?? '-';
}
```

### 3.4b บทบาทของ 3 ไฟล์ฟังก์ชัน — วางฟังก์ชันไฟล์ไหน ⭐

| ไฟล์ | ใช้เมื่อ | โหลดโดย |
|------|---------|---------|
| `api.php` | ฟังก์ชันสำหรับใช้ **นอก admweb** (public/external endpoint) | public path `mainApi.php` ทุก module · **ไม่โหลด**บน admin `index.php` |
| `function.php` | helper ที่ใช้ **ใน admweb** และเรียกได้ **เฉพาะตอนเปิด module นั้น** | admin `index.php` (เฉพาะ `_MODULE_` ปัจจุบัน) · `doAjax.php` (module ปัจจุบัน) |
| `__funcGlobal.php` | helper ที่เรียกได้ **แม้ไม่ได้เปิด module นั้น** แต่ยังอยู่ **ใน admweb** | ทุก module ใน `aModuleUse` ทั้งบน admin `index.php`, `doAjax.php`, และ public `mainApi.php` |

สรุปการตัดสินใจ: ฟังก์ชันที่ endpoint สาธารณะเรียก → `api.php` · helper เฉพาะหน้าใน module → `function.php` · helper ที่ใช้ร่วมหลาย module หรือทั้ง admin+public (เช่น `PdpaUser_ownerScope`, `PdpaUser_cookieDefaults`) → `__funcGlobal.php`

### 3.5 ขั้นตอนสร้าง module ใหม่ทั้งหมด (checklist)

1. [ ] สร้าง folder `admweb/aowebdata/modules/{name}/`
2. [ ] สร้าง `aModuleConfig.php` — ใส่ตารางที่ใช้
3. [ ] สร้าง `__menu.php` — define sidebar menu
4. [ ] สร้าง `function.php` (ถ้ามี helper)
5. [ ] สร้างไฟล์หน้าแต่ละหน้า: `main_xxx.php`, `main_xxx_edit.php`
6. [ ] เพิ่ม schema ใน `admweb/databases/mysql_{name}.sql.php` (ถ้ามีตาราง)
7. [ ] **เพิ่มชื่อ module ใน `$aModuleUse`** ที่ไฟล์ `admweb/aowebdata/fix.{domain}.php`
   ```php
   $aModuleUse = array(
       'member',
       'siteconfig',
       // ...
       'mymodule',  // ← เพิ่มตรงนี้
   );
   ```
   > ถ้าไม่เพิ่ม module จะไม่โหลดและ menu จะไม่ขึ้น
8. [ ] เพิ่ม permission strings ใน `aowebdata/permission/default.php` (ถ้าต้องการ permission ละเอียด)
9. [ ] ถ้ามีหน้า frontend → ลงทะเบียนใน `$aConfig['aConfigSitemap']` ที่ `fix.{domain}.php` — ดู section 3.7
10. [ ] ทดสอบ URL: `admweb/index.php?module={name}&mp={page}`

### 3.6 โครงสร้าง module ตัวอย่าง (CRUD พื้นฐาน)

```
aowebdata/modules/mymodule/
├─ aModuleConfig.php           ← register ตาราง
├─ __menu.php                  ← sidebar menu
├─ function.php                ← helper functions
│
├─ hooks/
│   └─ hooks_admin_main.php    ← dashboard widget
│
├─ config/
│   └─ db_conf.php             ← form fields ของ config
│
├─ option/
│   └─ isAttachAll.php         ← partial: file attach UI
│
├─ js/
│   └─ ...                     ← JS เฉพาะ module
│
├─ main_list.php               ← list รายการ
├─ main_edit.php               ← add/edit รายการ
├─ main_config.php             ← page ตั้งค่า
└─ main_banners.php            ← จัดการ banner (ถ้ามี)
```

### 3.7 การลงทะเบียนหน้าเว็บใน Router (`$aConfig['aConfigSitemap']`)

เมื่อสร้างหน้า frontend ใหม่ต้องลงทะเบียน URL ใน `$aConfig['aConfigSitemap']` ที่ไฟล์ `admweb/aowebdata/fix.{domain}.php` เสมอ — ระบบใช้ตัวแปรนี้ทำหน้าที่เป็น **router** ของ frontend และสร้าง `sitemap.xml` ให้อัตโนมัติ

```php
// admweb/aowebdata/fix.{domain}.php

$aConfig['aConfigSitemap'] = [

    // ── หน้าที่ไม่มี slug ต่อท้าย (static page) ───────────────────
    'home'    => ['keysname' => 'home'],
    'about'   => ['keysname' => 'about'],
    'contact' => ['keysname' => 'contact'],

    // ── หน้าที่มี slug/id ต่อท้าย (dynamic page) ──────────────────
    'blog-detail' => [
        'keysname'   => 'blog_detail',
        'changefreq' => 'monthly',
        'priority'   => '0.7',
    ],

    'portfolio-detail' => [
        'keysname'   => 'portfolio_detail',
        'changefreq' => 'monthly',
        'priority'   => '0.6',
    ],
];
```

**โครงสร้าง entry:**

| key | ความหมาย | required |
|---|---|:---:|
| key ของ array | URL slug ของหน้า (ใช้ใน URL) | ✅ |
| `keysname` | ชื่อ key สำหรับ query ข้อมูล (เชื่อมกับ articles หรือ config) | ✅ |
| `changefreq` | ความถี่การเปลี่ยนแปลง (สำหรับ sitemap.xml) | — |
| `priority` | ลำดับความสำคัญ 0.0–1.0 (สำหรับ sitemap.xml) | — |

**ค่า `changefreq` ที่ใช้บ่อย:**

| ค่า | ใช้กับ |
|---|---|
| `always` | หน้าแรก, feed ข่าว |
| `daily` | หน้าข่าว, บทความใหม่ |
| `weekly` | หน้าสินค้า, บทความทั่วไป |
| `monthly` | หน้า detail, portfolio |
| `yearly` | หน้าเกี่ยวกับเรา, นโยบาย |
| `never` | archive เก่า |

**กฎ:**

1. **ทุกหน้า frontend ต้องลงทะเบียน** — ถ้าไม่ลง router จะไม่รู้จักหน้านั้น
2. **key ของ array = URL path** — เช่น `'blog-detail'` → URL จะเป็น `/blog-detail/`
3. **`keysname` ต้องไม่ซ้ำกัน** ในระบบ เพราะใช้เป็น identifier query ข้อมูล
4. เพิ่มใน checklist สร้าง module ด้วย (ดู 3.5 ข้อ 10)

---

## 4. รูปแบบไฟล์หน้า (main_xxx.php pattern)

ทุกไฟล์ `main_xxx.php` ต้องเริ่มด้วย structure 3 ส่วน: **Permission check → Action handler → Render**

### 4.1 Template มาตรฐาน

```php
<?php
// ──────────────────────────────────────────────────────────
// 1. PERMISSION CHECK (บรรทัดแรกเสมอ)
// ──────────────────────────────────────────────────────────
PERMIT::_PERMIT(
    _MODULE_,                              // module ปัจจุบัน (constant)
    'module|mp',                           // key สำหรับ check (ปกติใช้ module|mp)
    'สามารถเปิด [ชื่อหน้า] ได้',          // ข้อความ permission (โชว์ใน permission UI)
    'redirect',                            // ถ้าไม่มีสิทธิ์ → redirect
    'SET'                                  // SET = บันทึก permission นี้ใน registry
);

// ──────────────────────────────────────────────────────────
// 2. ดึง parameters
// ──────────────────────────────────────────────────────────
$page     = REQ_get('page', 'get', 'int', 1);
$ac       = REQ_get('ac', 'request', 'str');     // action ที่ user ทำ
$id       = REQ_get('id', 'get', 'int');
$keysword = REQ_get('keysword', 'request', 'str');

// ──────────────────────────────────────────────────────────
// 3. ACTION HANDLER (POST/GET action — ทำแล้ว redirect ออก)
// ──────────────────────────────────────────────────────────
if ($ac == 'search') {
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&ac=searchtext&keysword=" . $keysword);
    exit;
}
elseif ($ac == 'changestatus' && $id > 0) {
    $row = DB_GET('items', ['item_id' => $id]);
    if (is_array($row)) {
        DB_UP('items', ['display' => !$row['display']], ['item_id' => $id]);
        setRaiseMsg('เปลี่ยนสถานะสำเร็จ', _TIME_, 0);
    } else {
        setRaiseMsg('ไม่พบข้อมูล', _TIME_, 1);
    }
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
    exit;
}
elseif ($ac == 'delete') {
    $aIDList = $_POST['aIDList'] ?? [];
    if (is_array($aIDList) && count($aIDList) > 0) {
        foreach ($aIDList as $v) {
            DB_DEL('items', ['item_id' => $v]);
        }
        setRaiseMsg('ลบสำเร็จ ' . count($aIDList) . ' รายการ', _TIME_, 0);
    } else {
        setRaiseMsg('กรุณาเลือกรายการ', _TIME_, 1);
    }
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
    exit;
}

// ──────────────────────────────────────────────────────────
// 4. ดึงข้อมูลสำหรับแสดงผล (GET data)
// ──────────────────────────────────────────────────────────
if ($ac == 'searchtext') {
    $aData = DB_LIST_OR('items', [
        'name' => ['LIKE', "%$keysword%"],
        'desc' => ['LIKE', "%$keysword%"],
    ], 100, $page);
} else {
    $aData = DB_LIST('items', [], 100, $page, 'ORDER BY add_date DESC');
}

$urlsearch = _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
$url       = _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_);
?>

<!-- ──────────────────────────────────────────────────── -->
<!-- 5. RENDER (HTML — ใช้ Nifty admin theme classes)  -->
<!-- ──────────────────────────────────────────────────── -->

<div id="page-head">
    <div id="page-title">
        <h1 class="page-header text-overflow">รายการทั้งหมด</h1>
    </div>
</div>

<div id="page-content">
    <div class="row">
        <div class="col-md-12 pad-hor">

            <!-- Search form -->
            <form action="<?php echo $urlsearch; ?>" method="post">
                <input type="hidden" name="ac" value="search">
                <div class="input-group mar-btm">
                    <input type="text" placeholder="ค้นหา..." class="form-control input-lg"
                           name="keysword" value="<?php echo htmlspecialchars($keysword); ?>">
                    <span class="input-group-btn">
                        <button class="btn btn-primary btn-lg input-lg" type="submit">Search</button>
                    </span>
                </div>
            </form>

            <?php echo displayRaiseMsg(); ?>

            <!-- Table form (สำหรับ delete หลายรายการ) -->
            <form id="form1" name="form1" method="post" action="">
                <input type="hidden" name="ac" value="sortlist" class="ac" />

                <button class="btn btn-danger" onclick="$('.ac').val('delete'); return confirmPostAction();">
                    <i class="fa fa-times-circle"></i> ลบรายการที่เลือก
                </button>

                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ชื่อ</th>
                            <th>วันที่</th>
                            <th>สถานะ</th>
                            <th>เลือก</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (@$aData['num_rows'] > 0): ?>
                            <?php foreach ($aData['data'] as $v): ?>
                                <tr>
                                    <td><?php echo $v['item_id']; ?></td>
                                    <td><?php echo htmlspecialchars($v['name']); ?></td>
                                    <td><?php echo $v['add_date']; ?></td>
                                    <td>
                                        <?php $url_ch = _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&ac=changestatus&id=" . $v['item_id']); ?>
                                        <a href="<?php echo $url_ch; ?>">
                                            <i class="fa fa-toggle-<?php echo $v['display'] ? 'on text-success' : 'off'; ?>"></i>
                                        </a>
                                    </td>
                                    <td><input type="checkbox" name="aIDList[]" value="<?php echo $v['item_id']; ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php BuilListPage(@$aData, @$url, @$page); ?>
            </form>

        </div>
    </div>
</div>
```

### 4.2 ฟังก์ชัน helper ของ admin ที่ใช้บ่อย

```php
REQ_get($key, $type = 'request', $cast = 'str', $default = '')
// ดึง $_GET / $_POST / $_REQUEST อย่างปลอดภัย
//   $type: 'get', 'post', 'request'
//   $cast: 'str', 'int', 'float'

setRaiseMsg($msg, $time, $isError)
// แสดง flash message
//   $isError: 0 = success (เขียว), 1 = error (แดง)

displayRaiseMsg()
// แสดง flash message ใน HTML (ใส่ใน view)

CustomRedirectToUrl($url)
// header(Location:) + exit

_admin_buil_link($url)
// build internal admin link (เติม base path)

BuilListPage($aData, $url, $page)
// แสดง pagination
```

### 4.3 รูปแบบ edit page (`main_xxx_edit.php`)

```php
<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด [Edit] ได้', 'redirect', 'SET');

$ac = REQ_get('ac', 'post', 'str');
$id = REQ_get('id', 'request', 'int', '');

// โหลดข้อมูลปัจจุบัน (ถ้า edit, ถ้า add → คืน false)
$aData = DB_GET('items', ['item_id' => $id]);
if (!is_array($aData)) { $aData = []; }

// Handle form submit
if ($ac == 'save') {
    $frm   = $_POST['frm'] ?? [];
    $aFile = $_FILES['picture'] ?? [];

    // upload รูป (ถ้ามี)
    if (!empty($aFile['name']) && $aFile['error'] === UPLOAD_ERR_OK) {
        if (!empty($aData['picture'])) {
            @unlink(PATH_UPLOAD . '/' . $aData['picture']);
        }
        $frm['picture'] = Func_uploads_file($aFile, ['jpg', 'png', 'webp'], 'foldername');
    }

    if ($id > 0) {
        DB_UP('items', $frm, ['item_id' => $id]);
        $msg = 'แก้ไขสำเร็จ';
    } else {
        $frm['add_date'] = date('Y-m-d H:i:s');
        $id = DB_ADD('items', $frm);
        $msg = 'เพิ่มสำเร็จ';
    }

    setRaiseMsg($msg, _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "_edit&id=" . $id);
    exit;
}
?>

<form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="ac" value="save">

    <div id="page-head">
        <div id="page-title">
            <h1><?php echo $id > 0 ? 'แก้ไข' : 'เพิ่มใหม่'; ?></h1>
        </div>
    </div>

    <?php echo displayRaiseMsg(); ?>

    <div class="form-group">
        <label>ชื่อ</label>
        <input type="text" name="frm[name]" class="form-control"
               value="<?php echo htmlspecialchars($aData['name'] ?? ''); ?>" required>
    </div>

    <div class="form-group">
        <label>รายละเอียด</label>
        <textarea name="frm[desc]" class="form-control"><?php echo htmlspecialchars($aData['desc'] ?? ''); ?></textarea>
    </div>

    <div class="form-group">
        <label>รูปภาพ</label>
        <input type="file" name="picture">
        <?php if (!empty($aData['picture'])): ?>
            <img src="<?php echo URL_UPLOAD . '/' . $aData['picture']; ?>" style="max-height:100px;">
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary">บันทึก</button>
</form>
```

### 4.4 Nifty theme CSS classes ที่ใช้บ่อย

```
Layout:
  #page-head, #page-title, #page-content
  .container-fluid, .row, .col-md-{N}, .pad-hor

Panel:
  .panel, .panel-body, .panel-heading
  .panel-colorful, .panel-warning, .panel-info, .panel-mint, .panel-danger

Button:
  .btn-primary, .btn-success, .btn-danger, .btn-warning, .btn-mint, .btn-info
  .btn-lg (large), .btn-sm (small)
  .btn-icon (icon only)

Form:
  .form-control, .form-group, .form-horizontal
  .input-lg, .input-group

Table:
  .table .table-striped .table-bordered .table-vcenter

Media (avatar + text):
  .media, .media-left, .media-body, .pad-all

Badge:
  .badge .badge-purple .badge-dark .badge-pink .badge-icon

Text:
  .text-success, .text-danger, .text-warning, .text-info
  .text-main, .text-content, .text-muted
  .text-overflow (ellipsis)

FA icons (Font Awesome 4):
  fa fa-check, fa fa-times, fa fa-pencil, fa fa-trash
  fa fa-toggle-on, fa fa-toggle-off, fa fa-eye, fa fa-search
  fa fa-shield, fa fa-cube, fa fa-user, fa fa-cog
```

---

## 5. ระบบ Permission

ระบบ permission ใช้ class `PERMIT` ใน `admweb/include/class.permit.php` — ทุก page หลังบ้านต้องเรียก `PERMIT::_PERMIT()` ที่บรรทัดแรกเพื่อตรวจสิทธิ์

### 5.1 รูปแบบ call

```php
PERMIT::_PERMIT(
    $module,    // module ปัจจุบัน — ใช้ constant _MODULE_
    $keys,      // permission key — ดูตัวเลือกข้างล่าง
    $name,      // ข้อความ permission (โชว์ใน permission management UI)
    $action,    // 'redirect' = redirect ถ้าไม่มีสิทธิ์
    $isPer      // 'SET' = register permission นี้, 'UNSET' = ลบ permission, 'none' = ไม่ register
);
```

### 5.2 ค่า `$keys` มี 4 แบบ

| Key | ความหมาย | กรณีใช้ |
|---|---|---|
| `'module'` | check ระดับ module | สิทธิ์เข้าทั้ง module |
| `'module\|mp'` | check ระดับหน้า ⭐ | **ใช้บ่อยที่สุด** — สิทธิ์เข้าหน้าเฉพาะ |
| `'module\|mp\|keysname'` | check ระดับ sub-section | เช่น page config มีหลายกลุ่ม |
| `'module\|mp\|keysname\|inc'` | check ระดับ subaction | เช่น list/edit ใน sub-section เดียวกัน |

### 5.3 ตัวอย่าง

```php
// หน้า list — เข้าได้ทุก admin ที่มีสิทธิ์ module + mp นี้
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Item List ได้', 'redirect', 'SET');

// หน้า edit — สิทธิ์แยกจาก list
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Item Edit ได้', 'redirect', 'SET');

// permission พิเศษ (ระบบ skip check อัตโนมัติ):
//   'สามารถเปิด Permission ได้'
//   'สามารถเปิด System setup'
//   'สามารถเปิด ล้างข้อมูลที่ตั้งค่า ได้'
//   'สามารถเปิด คีย์ที่ยังไม่ได้แปล ได้'
```

### 5.4 ค่า `$isPer`

- **`'SET'`** — ระบบจะลงทะเบียน permission นี้ใน permission registry (`aowebdata/permission/default.php`) **อัตโนมัติ** เมื่อ superadmin เปิดหน้านี้ครั้งแรก → admin คนอื่นๆ จะเห็น checkbox ของ permission นี้ใน "Permission Management" UI
- **`'UNSET'`** — ลบ permission ออกจาก registry (superadmin only)
- **`'none'`** (default) — ไม่ทำอะไรกับ registry

### 5.5 Permission registry

`admweb/aowebdata/permission/default.php` คือ database ของ permission ทั้งหมด — แต่ละ entry คือ:

```php
$aosoft_permit['{module}']['{md5_hash}'] = '{ชื่อ permission}';
```

`md5_hash` คำนวณจาก `module|mp|...` → ระบบจะแปลงให้อัตโนมัติ

### 5.6 Frontend (public side) permission

หน้า public ไม่ได้ใช้ `PERMIT::` แต่ใช้ `oApi::checkLogin()` แทน:

```php
oApi::checkLogin('/signin');
// ↑ ถ้ายังไม่ login → redirect ไป /signin
```

หรือเช็ค admin role:
```php
$aUserCheck = oApi::getLoginData();
if ($aUserCheck->status == 'admin') {
    header("Location: admweb/");
    exit;
}
```

หรือใน hooks (เช่น dashboard widget):
```php
// admweb/aowebdata/modules/{name}/hooks/hooks_admin_main.php
if (!login_logout::is_Admin()) {
    header('Location: /');
    exit;
}
```

---

## 6. Best Practices สำหรับการพัฒนาต่อ

### 6.1 Code style

**Formatting:** โปรเจกต์นี้จัด format ด้วย **PHP Intelephense (VSCode)** — ไม่มี php-cs-fixer/phpcs ยึด style ตามไฟล์เดิม: **tab indent**, `{` ต่อท้าย control, เว้นวรรค `foreach ($x as $y)`; ผู้ใช้จะกด **Format Document** ให้เอง — อย่าไปสู้กับ formatter

**เงื่อนไขสั้น ๆ ใช้ ternary ห้าม if ไม่มีวงเล็บ:** สำหรับกำหนด/คืนค่าตามเงื่อนไข ให้ใช้ `$v = ($a == $b) ? 'x' : 'y';` แทนการเขียน `if` แบบไม่มีวงเล็บ · ถ้าเป็น `if`/`else` ที่เป็น statement จริง ให้ใส่ `{ }` เสมอ (ห้าม brace-less one-liner)

**เปิด PHP ด้วย `<?php` เสมอ:** ห้ามใช้ short tag `<?` หรือ `<?=` — ใช้ `<?php echo $x; ?>` ไม่ใช่ `<?= $x ?>` (กันปัญหาเมื่อ short_open_tag ปิดบนบางเซิร์ฟเวอร์)


1. **Comment header ทุกไฟล์** — บอก purpose + depends
   ```php
   /**
    * FILE: admweb/aowebdata/modules/xxx/main_yyy.php
    * ROLE: หน้าจัดการ yyy ของ module xxx
    * DEPENDS: xxx table, function MyHelper()
    * URL:    index.php?module=xxx&mp=yyy
    */
   ```

2. **escape ทุก output** — `htmlspecialchars()` ใน HTML, htmlentities ใน attribute
3. **ใช้ prepared statements** ผ่าน DB helper เท่านั้น — ห้าม concat SQL กับ user input
4. **กัน CSRF/double-submit** — ตอน critical action ใช้ session token หรือ POST + redirect

### 6.2 PHP 8.1+ safety

```php
// ❌ "Trying to access array offset on value of type bool"
$row = DB_GET('items', ['item_id' => 1]);
echo $row['name'];  // ถ้า DB_GET คืน false → error

// ✅ ปลอดภัย
$row = DB_GET('items', ['item_id' => 1]);
if (!is_array($row)) { $row = []; }
echo $row['name'] ?? '-';
```

```php
// ❌ "explode(): Passing null to parameter #2"
$arr = explode(',', $row['list']);  // ถ้า list = null

// ✅
$arr = explode(',', (string)($row['list'] ?? ''));
```

### 6.3 การจัดการรูป/ไฟล์

```php
// upload (ใช้ helper)
$pic = Func_uploads_file($aFile, ['jpg', 'png', 'webp'], 'subfolder');

// delete แบบปลอดภัย — เช็คว่าไฟล์อยู่จริงและอยู่ใน PATH_UPLOAD
function safe_unlink_upload($relativePath) {
    if (empty($relativePath)) return false;
    $full = PATH_UPLOAD . '/' . $relativePath;
    $real = realpath($full);
    $base = realpath(PATH_UPLOAD);
    if ($real && $base && strpos($real, $base) === 0 && is_file($real)) {
        return @unlink($real);
    }
    return false;
}

// แสดง URL ของรูป
$url = URL_UPLOAD . '/' . $row['picture'];

// crop/resize (ใช้ helper)
$url = PIC_Corp($row['picture'], 'assets/default.png', 'w=300&h=300');
```

### 6.4 การ deploy

1. แยก SQL migration เป็น `.sql` ไฟล์ (ไม่ใช่ schema definition)
2. ทำ git commit ที่ revertable (1 feature = 1 commit)
3. ทำ TODO ที่ comment header ของไฟล์ → ระบุ status `[x] done` / `[ ] pending`
4. ทดสอบ on local ก่อนทุกครั้งโดยใช้ MAMP/XAMPP

### 6.5 การอัปโหลดไฟล์ด้วย plugin UpFile ⭐

ระบบมี plugin กลางสำหรับอัปโหลดไฟล์ที่ `admweb/plugins/uploadfile/UpFile.php` (class `UpFile`) — **แนะนำให้ใช้แทนการเขียน `move_uploaded_file()` เอง** เพราะจัดการ validate นามสกุล + สร้างโฟลเดอร์ + ตั้งชื่อกันชนกัน + ลบไฟล์เก่าให้ครบในที่เดียว

> **สำคัญ:** class นี้ **ไม่ auto-load** ต้อง `require_once PATH_PLUGIN . '/uploadfile/UpFile.php';` เองก่อนใช้

#### เมธอดหลัก

```php
// ── อัปโหลดไฟล์เดี่ยว (มาตรฐาน) ─────────────────────────────
uploadStandard($allowedExts, $tmpName, $originalName, $targetFolder, $customFileName = null, $oldFilePath = null)
//   $allowedExts    : array นามสกุลที่อนุญาต เช่น ['pdf','doc','docx'] (ถ้าส่ง [] จะใช้ default ของ plugin)
//   $tmpName        : $_FILES['x']['tmp_name']
//   $originalName   : $_FILES['x']['name']
//   $targetFolder   : โฟลเดอร์ปลายทาง (relative จาก PATH_UPLOAD) เช่น 'pdpa_documents/5/12'
//   $customFileName : (optional) ตั้งชื่อไฟล์เอง โดย "ไม่ต้องใส่ .ext" (plugin เติมให้) — ถ้าไม่ส่งจะใช้ uniqid()_time()
//   $oldFilePath    : (optional) path ไฟล์เก่า (relative) ที่จะลบทิ้งตอน update
// คืน: string path ที่เก็บ (relative จาก PATH_UPLOAD) เช่น 'pdpa_documents/5/12/xxx.pdf'
//       หรือ false ถ้าล้มเหลว (ไม่ใช่ไฟล์ upload / นามสกุลไม่อนุญาต / move ไม่สำเร็จ)

getExtension($filename)     // คืนนามสกุลไฟล์ (lowercase)
deleteFile($filePath)       // ลบไฟล์ (path relative จาก PATH_UPLOAD) — คืน bool
handleUpload($file, $post)  // อัปโหลดไฟล์ใหญ่แบบแบ่ง chunk (ใช้คู่กับ plugins/uploadfile/script.js)
```

#### ตัวอย่างใช้งานจริง (บันทึกเอกสารพร้อมระบบเวอร์ชัน)

```php
// จำกัดขนาดเอง (uploadStandard ไม่เช็คขนาดไฟล์ให้)
if ($_FILES['file_name']['size'] > 10 * 1024 * 1024) {
    setRaiseMsg('ไฟล์ต้องไม่เกิน 10MB', _TIME_, 1);
    CustomRedirectToUrl($backUrl);
    exit;
}

require_once PATH_PLUGIN . '/uploadfile/UpFile.php';

$upFile       = new UpFile();                       // baseDir = PATH_UPLOAD (ค่า default)
$targetFolder = 'pdpa_documents/' . $company_id . '/' . $doc_cate_id;
$customName   = $doc_cate_id . '_v' . $versionStr . '_' . time();   // ชื่อไฟล์กันชนกัน (ไม่ต้องใส่ .ext)

$savedRel = $upFile->uploadStandard(
    ['pdf', 'doc', 'docx'],
    $_FILES['file_name']['tmp_name'],
    $_FILES['file_name']['name'],
    $targetFolder,
    $customName
);

if ($savedRel === false) {
    setRaiseMsg('อัปโหลดไม่สำเร็จ (รองรับเฉพาะ .pdf .doc .docx)', _TIME_, 1);
    CustomRedirectToUrl($backUrl);
    exit;
}

$storedName = basename($savedRel);                                 // ชื่อไฟล์ที่เก็บบนดิสก์
$ext        = $upFile->getExtension($_FILES['file_name']['name']);
$fileUrl    = URL_UPLOAD . '/' . $savedRel;                        // URL สำหรับแสดง/ดาวน์โหลด

DB_ADD('pdpa_doc', [
    'company_id' => $company_id,
    'file_name'  => $storedName,                    // ชื่อบนดิสก์ (ไว้ประกอบ path)
    'doc_name'   => $_FILES['file_name']['name'],   // ชื่อไฟล์เดิม (ไว้แสดงผล)
    'doc_type'   => $ext,
    'created_at' => date('Y-m-d H:i:s'),
]);
```

#### ตัวอย่างตอน "แก้ไข" (อัปโหลดทับ + ลบไฟล์เก่าอัตโนมัติ)

```php
// ส่ง $oldFilePath (relative) เป็น argument สุดท้าย → plugin ลบไฟล์เก่าให้เอง
$savedRel = $upFile->uploadStandard(
    ['jpg', 'png', 'webp'],
    $_FILES['picture']['tmp_name'],
    $_FILES['picture']['name'],
    'products/' . $shop_id,
    null,                          // ให้ plugin ตั้งชื่อ unique เอง
    $aData['picture']              // ไฟล์เก่าที่จะลบ (relative จาก PATH_UPLOAD)
);
```

**ข้อควรรู้:**

1. `uploadStandard()` **ไม่เช็คขนาดไฟล์** — ถ้าต้องจำกัด ให้เช็ค `$_FILES['x']['size']` เองก่อนเรียก
2. นามสกุล default ที่ plugin อนุญาต: `jpg, jpeg, mov, mp3, mp4, png, gif, pdf, docx, xlsx, txt, zip, rar` — **ไม่มี `doc`** ต้องส่ง `$allowedExts` เองถ้าต้องรองรับ
3. return เป็น path **relative จาก PATH_UPLOAD** → เก็บลง DB ได้เลย และประกอบ URL ด้วย `URL_UPLOAD . '/' . $savedRel`
4. อย่าลืมประกาศ path upload ใน `aModuleConfig.php` (`$aPermission`) ให้ writable
5. ไฟล์ใหญ่เกิน `upload_max_filesize` ของ PHP ให้ใช้ `handleUpload()` (chunked) คู่กับ `script.js` แทน


### 6.6 AJAX / JSON endpoints — `doAjax.php` ⭐

admweb ยิง AJAX ผ่าน **entry file แยกชื่อ `doAjax.php`** (อยู่ root เดียวกับ `index.php`)
ไม่ใช่ผ่าน `index.php` — เพราะ `doAjax.php` **ไม่ครอบ layout/ธีม** จึง echo ผลลัพธ์
(JSON หรือ HTML fragment) ออกได้ตรง ๆ

**กฎการตั้งชื่อไฟล์ (สำคัญ):** ปกติเราสร้างหน้าเป็น `main_{mp}.php` — แต่สำหรับ AJAX ให้ตั้งชื่อ
เป็น **`ajax_{mp}.php`** แทน วางในโฟลเดอร์ module เดียวกัน

**เรียกใช้ = ลิงก์ปกติ** (ส่ง `module` / `mp` / `ac` เหมือน index.php แต่เปลี่ยน entry เป็น `doAjax.php`):

```
doAjax.php?module={module}&mp={mp}&ac={action}
```

ค่าที่ `doAjax.php` รับ (query string): `module`→`_MODULE_`, `mp`→`_MP_`, `ac`→`_AC_`
และเสริม `ty`, `pg`, `inc`, `sef_log`

การเลือกไฟล์ปลายทางใน `doAjax.php`:

| `ty`            | ไฟล์ที่โหลด                                            |
|-----------------|--------------------------------------------------------|
| (ว่าง) ปกติ     | `modules/{module}/ajax_{mp}.php` (mp ว่าง → `ajax_{module}.php`) |
| `plugin`        | `modules/{module}/main_{mp}.php` (ใช้หน้าเดิมซ้ำ)      |
| `ajax` + `inc`  | `plugins/{pg}/ajax_{inc}.php`                          |

**doAjax.php โหลดให้อัตโนมัติ** ก่อน include ไฟล์ ajax: conf/DB/mail, `fix.req.php` (`REQ_get`),
login+permit class, module `function.php`, และ `__funcGlobal.php` ของทุก module ใน `aModuleUse`
— ดังนั้นใน `ajax_*.php` ใช้ `DB_GET/DB_LIST/...`, `REQ_get`, helper ใน `function.php`/`__funcGlobal.php`
ได้เลย

**ความปลอดภัย:** `doAjax.php` เรียก `login_logout::checkLogin()` เก็บผลใน `$ok` แต่
**ไม่ redirect เอง** → ต้องเช็ก `$ok` หรือ `PERMIT::_PERMIT(...)` ในไฟล์ ajax เองถ้าต้องกันสิทธิ์

#### ตัวอย่าง: `modules/{module}/ajax_list.php` (ตอบ JSON)

```php
<?php
// doAjax โหลด login/DB/REQ_get ให้แล้ว — เช็กสิทธิ์เองถ้าต้องการ
if (!$ok) { echo json_encode(['ok' => false, 'error' => 'unauthorized']); exit; }

$uid  = login_logout::getLoginData()->user_id;
$rows = DB_LIST('items', ['user_id' => $uid], 20, 1, 'ORDER BY add_date DESC')['data'] ?? [];

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
// ไม่มี layout ครอบ — echo ออกได้ตรง ๆ (ต่างจาก main_*.php ใน index.php)
```

#### เรียกจาก JS (ธีม admin โหลด jQuery ให้แล้ว)

```js
// GET / โหลด JSON
$.getJSON('doAjax.php?module=<?php echo _MODULE_; ?>&mp=list&ac=fetch', function (r) {
    if (r.ok) { /* ใช้ r.data */ }
});

// POST (บันทึกข้อมูล) — เรียก ajax_save.php
$.post('doAjax.php?module=<?php echo _MODULE_; ?>&mp=save&ac=add',
    { id: 5, name: 'x' },
    function (r) { /* r = JSON */ },
    'json'
);

// หรือ fetch
fetch('doAjax.php?module=Foo&mp=save&ac=add', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ id: 5, name: 'x' })
}).then(r => r.json());
```

#### จุดพลาดบ่อย

1. ตั้งชื่อไฟล์เป็น `ajax_{mp}.php` (ไม่ใช่ `main_`) และเรียกผ่าน `doAjax.php` (ไม่ใช่ `index.php`)
2. `REQ_get($k,'post','str')` รัน `strip_tags`+`htmlspecialchars` และตัด `;` `=` `script`
   → ทำลาย HTML/JSON/URL ที่มี query string ถ้าต้องได้ค่าดิบให้อ่าน `$_POST[$k]` ตรง ๆ
3. `doAjax.php` ไม่บังคับ login — เช็ก `$ok`/`PERMIT` ในไฟล์ ajax เอง
4. debug ได้ด้วย `pre($var)` (print_r wrapper ของ framework) เหมือนใน `example/ajax_form_preview.php`

### 6.7 CSS / JS ต้องอยู่ที่ `css.php` / `js.php` ของ module — อย่าเขียนปนในหน้า ⭐

ธีมโหลดไฟล์เหล่านี้ให้อัตโนมัติ (ดู `template/version2018/mainGlobal.php`):

- `modules/{module}/css.php` → include เข้า **`<head>`** ของทุกหน้าใน module นั้น
- `modules/{module}/js.php` → include **ก่อนปิด `</body>`** (jQuery + ธีมโหลดแล้ว ใช้ `$(...)` ได้เลย)
- `modules/{module}/__funcGlobal.js.php` → JS ที่ใช้ร่วมทุก module โหลดผ่าน
  `<script src="doJS.php?module={module}">` (สำหรับทุก module ใน `aModuleUse`)

ทั้ง `css.php` และ `js.php` โหลดตาม **`_MODULE_` ปัจจุบันเท่านั้น** และใช้ร่วมทุกหน้า (`mp`) ใน
module → ต้องผูก event แบบ guard/delegation ให้ทำงานเฉพาะเมื่อมี element ที่เกี่ยวข้อง

**กฎ:**

1. **CSS ทั้งหมดไปไว้ที่ `css.php`** — ห้ามใส่ `<style>` ใน `main_*.php` และ **ห้ามใส่
   `style="..."` ใน HTML tag** (inline style) → ตั้ง `class` แล้วเขียน rule ใน `css.php` แทน
2. **JS ทั้งหมดไปไว้ที่ `js.php`** — ห้ามใส่ `<script>` inline ใน `main_*.php` ผูก event ด้วย
   delegation `$(document).on('click', '.myBtn', ...)` เพื่อรองรับ element ที่ render ทีหลัง/ใน modal
3. ส่งค่า PHP → JS ผ่าน **`data-*` attribute** บน element (อ่านด้วย `$(this).data('x')`)
   ไม่ใช่ echo ตัวแปร PHP ลงใน `<script>` กลางหน้า
4. โครงไฟล์: `js.php` = `<script>$(document).ready(function(){ ... });</script>` (ดูจริง
   `modules/PdpaUser/js.php`) · `css.php` = `<style> .class{...} </style>`
5. ถ้าต้องมี dynamic value จริง ๆ (สี/URL จาก config) ให้ตั้งเป็น CSS variable หรือ `data-*`
   แล้วให้ `css.php`/`js.php` อ่าน — เลี่ยง inline

> ⚠️ งานเก่าบางไฟล์ (เช่น `main_cookie_manage.php`) ยังมี `<style>`/`<script>` inline และ
> `style="..."` เยอะ ควรทยอยย้ายเข้า `css.php`/`js.php` เมื่อแก้ไขรอบถัดไป

**อย่ากำหนดขนาด font เอง (`font-size`):** ห้าม hardcode `font-size` ทั้งแบบ inline และใน `css.php` — ปล่อยให้เป็นค่าปกติของ template เสมอ (ใช้ element/heading + คลาสของธีมตามเดิม อย่าไปยุ่งกับขนาดตัวอักษร) ถ้าจุดไหนจำเป็นต้องปรับขนาดจริง ๆ ผู้ใช้จะแจ้งเป็นรายกรณีตอนทำงาน — อย่าปรับเอง

---

## 7. Cheat Sheet

```php
// ── DB ────────────────────────────────────────────────
DB_GET('table', ['col' => $val], 'ORDER BY ...')
DB_LIST('table', ['col' => $val], $limit, $page, 'ORDER BY ...')
DB_LIST_OR('table', $whereOR, $limit, $page, $orderby)
DB_LIST_CUS('table', "raw WHERE")
DB_JOIN($sql, $params, $limit, $page, $orderby)
DB_ADD('table', $arrayData)              // return insert_id
DB_UP('table', $newData, $whereArray)    // return bool
DB_DEL('table', $whereArray)             // return bool

// Operators ใน where:
['col' => ['>=', $val]]
['col' => ['LIKE', "%$kw%"]]
['col' => ['IN', [1,2,3]]]

// ── Request ───────────────────────────────────────────
REQ_get('key', 'get|post|request', 'str|int|float', $default)

// ── Permission ────────────────────────────────────────
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'desc', 'redirect', 'SET')

// ── Flash message ─────────────────────────────────────
setRaiseMsg('OK', _TIME_, 0)      // success
setRaiseMsg('Error', _TIME_, 1)   // error
displayRaiseMsg()                  // render

// ── Redirect ──────────────────────────────────────────
CustomRedirectToUrl($url); exit;
_admin_buil_link("index.php?module=xxx&mp=yyy")

// ── User ──────────────────────────────────────────────
$oUser = oApi::getLoginData()
oApi::checkLogin('/signin')
login_logout::is_Admin()

// ── Config ────────────────────────────────────────────
SiteConfig_get('keyname', $default)
GlobalConfig_get('keyname', $default)

// ── Upload ────────────────────────────────────────────
Func_uploads_file($_FILES['xxx'], ['jpg', 'png'], 'subfolder')
PIC_Corp($pic, $fallback, 'w=300&h=200')

// ── Counter (DB-based, atomic) ────────────────────────
func_counter_set('page.html', 'web')        // บันทึก pageview (เรียกที่หน้า public)
func_counter_set()                          // auto-detect pagename จาก SCRIPT_NAME
func_counter_get('m-Y', 'web')             // ดึงยอดรายเดือน → ['month'=>[0=>total,1-31=>daily]]
func_counter_get('', 'web')               // ดึงยอดรวม → ['all'=>N,'year'=>[...],'month'=>[...]]
func_counter_txt('d'|'m'|'y'|'', 'web')  // ดึงยอดวัน/เดือน/ปี/ทั้งหมด (int)
func_counter_page_get($year)              // top pages → ['page.php'=>[year=>[month=>N]]]
// หมายเหตุ: ถ้า site_counter ยังไม่ถูกสร้าง ทุก function return เงียบๆ ไม่ error

// ── Online tracking ───────────────────────────────────
MemberOnline('ชื่อหน้า')                  // ที่หน้า public
MemberOnline_Count('member|guest|all')   // นับจำนวน online

// ── Pagination ────────────────────────────────────────
BuilListPage($aData, $url, $page)
```

---

## 8. Troubleshooting

| อาการ | ตรวจ |
|---|---|
| "PERMIT : ERROR ... IS NULL" | ลืม `$keys` หรือ `$name` ใน `PERMIT::_PERMIT()` |
| Redirect ไป login ทันที | Permission ไม่พอ → login เป็น superadmin แล้วเปิดหน้านี้เพื่อ auto-register |
| "Undefined array key" | DB_GET คืน false — เพิ่ม `is_array()` check |
| "Failed to del" | DB_DEL prepare fail — ตรวจ where condition |
| 404 ตอนเปิด module | path module ผิด, ตรวจ `aowebdata/modules/{name}/main_{mp}.php` |
| Menu ไม่ขึ้น | ลืม `__menu.php` หรือ `$_aMenuList['subhead']` syntax ผิด |
| Schema ไม่สร้าง | ลืม `IF NOT EXISTS`, หรือ `aModuleConfig.php` ไม่ register table |

---

## 9. คำถามที่พบบ่อย

**Q: อยาก add module ใหม่ทั้งหมด ทำอย่างไร?**
A: ทำตาม checklist ข้อ 3.5 — ขั้นต่ำต้องมี `aModuleConfig.php` + `__menu.php` + อย่างน้อย 1 `main_*.php`

**Q: เพิ่ม column ในตารางที่มีอยู่แล้วทำอย่างไร?**
A:
1. แก้ schema ใน `mysql_*.sql.php` (เพื่อ install ใหม่)
2. สร้าง `alter_xxx.sql` migration file
3. รัน ALTER บน live DB ก่อน deploy

**Q: จะใส่หน้าใหม่ใน module เดิมได้อย่างไร?**
A:
1. สร้างไฟล์ `main_newpage.php` ใน folder module
2. เพิ่ม menu entry ใน `__menu.php` ของ module นั้น
3. เริ่มเขียนด้วย template ใน 4.1
4. Login เป็น superadmin แล้วเปิดหน้านี้ครั้งแรกเพื่อ register permission

**Q: ทำไม `DB_LIST_CUS` ถูกแนะนำให้ใช้น้อย?**
A: คืน flat array ที่สับสนกับ `DB_LIST` (มี wrapper), รับ raw WHERE ที่เสี่ยง SQL injection ถ้าใส่ user input ตรงๆ — ใช้เฉพาะ case ที่ array syntax ทำไม่ได้จริงๆ เช่น BETWEEN หรือ UNIX_TIMESTAMP()

**Q: เพิ่ม FA icon ใน menu ทำอย่างไร?**
A: ระบบใช้ Font Awesome 4 — ใส่ class ใน `__menu.php`:
```php
'class' => 'fa fa-shield',           // single
'class' => 'fa fa-solid fa fa-cube', // multiple
```

---

_ไฟล์นี้ควรอัปเดตเมื่อมีการเปลี่ยนแปลง pattern หลักของระบบ_
