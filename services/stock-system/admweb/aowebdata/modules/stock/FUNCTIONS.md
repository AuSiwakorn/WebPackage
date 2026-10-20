# AOSTOCK — สารบัญฟังก์ชันของโมดูล stock

> **สถานะ: ร่างรออนุมัติ (ช่วงที่ 15)** — ยังไม่ได้เปลี่ยนชื่อในโค้ด · ตรวจคอลัมน์ "ชื่อใหม่" แล้วแจ้งชื่อที่อยากแก้ได้เลย

ฟังก์ชันทั้งหมด 489 ตัว (เดิม 468 + ตั้งชื่อตามมาตรฐานแล้ว 19 ตัว: บิล VAT ต้องมีเลขภาษี 20 ต.ค. หนึ่งตัว · เทียบจำนวนบนจอก่อนบันทึกใบ 20 ต.ค. หนึ่งตัว · รูปสินค้า 16 ต.ค. หนึ่งตัว · บิลของฉันวันนี้ 16 ต.ค. สองตัว · ช่วงที่ 16 สองตัว · ปุ่มพิมพ์บิล · ช่วงที่ 17 สองตัว · งานความเร็ว 14 ต.ค. แปดตัว · หลุดจากระบบ 14 ต.ค. หนึ่งตัว) อยู่ใน `admweb/aowebdata/modules/stock/api/*.php` (โหลดผ่าน `api.php` · ลบ staff_goal 15 ต.ค. · ใบนับแบบรายการ: เพิ่ม Stock_setCountDraft ลบ adj_limit · adj_full · ลบ count_open_limit · ลองใบนับแบบตารางแล้วกลับมาใช้การ์ด · กลุ่มของประวัติ: เพิ่ม Stock_getLogGroups · Stock_getLogGroupOf · Stock_sumLogGroups · เมนูประวัติแยกหน้า: เพิ่ม Stock_getAllowedLogGroups · Stock_getHistoryPerms) · ธีม `themes/aostock/` และหน้า admweb ของโมดูลเรียกใช้

## มาตรฐานการตั้งชื่อ

- ขึ้นต้นด้วยชื่อโมดูล ตัวแรกพิมพ์ใหญ่ ตามด้วย `_` → `Stock_`
- ชื่อหลัง `_` ขึ้นต้นด้วยกริยาตัวเล็ก คำถัดไปขึ้นต้นตัวใหญ่ (แบบเดียวกับ `Seo_ensureScriptsTable`, `SiteConfig_getCustomName` ของ admweb) เช่น `Stock_getBranchLimit`
- ห้ามมี `_` เพิ่มหลังชื่อโมดูล · ชื่อฟังก์ชันของ PHP ไม่สนตัวพิมพ์ ห้ามตั้งชื่อที่ต่างกันแค่ตัวเล็ก / ใหญ่

### กริยาที่ใช้ซ้ำ

| ขึ้นต้น | ความหมาย |
|---|---|
| `get / set` | อ่าน / ตั้งค่า (set = เขียนลงฐานข้อมูลหรือ session) |
| `is / has / can` | คืน true / false |
| `check` | ตรวจข้อมูล — คืน `''` เมื่อผ่าน หรือข้อความผิดพลาด |
| `do` | งานทั้งชุดของหน้าจัดการ (ตรวจ → เขียน → ลงประวัติ) — คืน `''` หรือข้อความผิดพลาด |
| `save / void` | บันทึกเอกสาร / ยกเลิกเอกสาร (ใน transaction) |
| `load` | ดึงเอกสารเดิมกลับเข้าร่าง / ตะกร้า เพื่อแก้ไข |
| `shape` | แปลงแถวจากฐานข้อมูลเป็นรูปที่หน้าเว็บใช้ |
| `format` | ตัวเลข / วันที่ / ข้อความสำหรับแสดงผล |
| `build` | ประกอบข้อความ / query string / ข้อมูลชุดใหญ่ |
| `render` | echo HTML ออกไปตรง ๆ |
| `compare` | ตัวเรียงสำหรับ usort / uasort |
| `db` | ตัวช่วยฐานข้อมูล (`Stock_dbQuery`, `Stock_dbFetchAll` …) |
| `…Draft` | ร่างใบที่กำลังทำใน session: Receive (นำเข้า) · Issue (เบิก) · Stocktake (ตรวจนับ) |

## `api/db.php` — ฐานข้อมูล · ค่าตั้งของระบบ · ค่าลับ (17)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_dbTable($name)` | `sdb_tb` | ชื่อตารางของ AOSTOCK พร้อม backtick — Stock_dbTable('branch') = `ao_stock_branch` |
| `Stock_dbConnection()` | `sdb` | การเชื่อมต่อฐานข้อมูล (ตัวเดียวกับ admweb) — ตั้ง utf8mb4 ครั้งเดียวต่อ request · class DB เชื่อมด้วย utf8 (= utf8mb3 บันทึกอีโมจิไม่ได้) จึงตั้ง utf8mb4 ตอนใช้ครั้งแรก · halt() ของ admweb echo SQL ออกหน้าเว็บ → เก็บทิ้งแล้วโยน RuntimeException · DB_UP / DB_DEL เขียนล้มเหลวแต่คืน true และ cache SELECT ไม่ล้าง จึงไม่ใช้ |
| `Stock_dbQuery($sql, $params = array())` | `sdb_q` | รัน SQL พร้อมค่าที่ bind (? หรือ :ชื่อ) — คืน PDOStatement · ผิดพลาดโยน RuntimeException · LIMIT / OFFSET ต่อเป็นตัวเลข (int) — PDO แบบ emulate ใส่ ' ให้ถ้าใช้ ? |
| `Stock_dbFetchAll($sql, $params = array())` | `sdb_rows` | ทุกแถว (array ของ array) |
| `Stock_dbFetchRow($sql, $params = array())` | `sdb_row` | แถวแรก หรือ null |
| `Stock_dbFetchValue($sql, $params = array())` | `sdb_val` | ค่าคอลัมน์แรกของแถวแรก หรือ null |
| `Stock_dbInsert($table, $data)` | `sdb_insert` | INSERT หนึ่งแถว — $data = array(คอลัมน์ => ค่า) · คืน id ที่ได้ |
| `Stock_dbUpdate($table, $data, $where)` | `sdb_update` | UPDATE ตามเงื่อนไขเท่ากับ (AND) — $where ห้ามว่าง · คืนจำนวนแถวที่เปลี่ยน |
| `Stock_dbTransaction($fn)` | `sdb_tx` | ทำงานใน transaction — $fn ทำงานสำเร็จ = COMMIT · โยน exception = ROLLBACK แล้วโยนต่อ |
| `Stock_showErrorPage($e)` | `stock_error_page` | หน้า "ระบบขัดข้อง" — ใช้กับ set_exception_handler() ของหน้า AOSTOCK (themes/aostock/include/function.php) · ใช้กับ set_exception_handler() ของหน้า AOSTOCK (themes/aostock/include/function.php) |
| `Stock_dbWhereIn($col, $vals)` | `sdb_in` | เงื่อนไข IN (?, ?, …) — คืน array(sql, params) · ไม่มีค่า = เงื่อนไขเท็จ (ไม่มีแถว) |
| `Stock_getSettingRows($reset = false)` | `stock_setting_rows` | ทุกแถวของ ao_stock_setting — array( skey => array(svalue, is_secret, updated_at) ) · ค่า array เก็บเป็น JSON (ผู้เรียกแปลงเอง) · ใช้กับแจ้งเตือน / สวิตช์เมนู / สถานะ cron · เขียนแล้วล้าง cache |
| `Stock_getSetting($key, $def = null)` | `stock_setting_get` | ค่าตั้งหนึ่งค่า (string) — ไม่มี / ถอดรหัสไม่ได้ = $def |
| `Stock_setSetting($key, $value, $secret = false, $uid = 0)` | `stock_setting_set` | บันทึกค่าตั้ง (เพิ่มหรือแทนที่) — $secret = เข้ารหัสก่อนเก็บ (ค่าว่างเก็บว่าง) · กุญแจไม่พร้อม = โยน RuntimeException · ค่าว่างเก็บว่าง · ไม่มีกุญแจหรือ openssl = บันทึกค่าลับไม่ได้ (stock_secret_ready) · กุญแจเปลี่ยน = อ่านค่าลับเดิมไม่ออก |
| `Stock_isSecretReady()` | `stock_secret_ready` | เข้ารหัสค่าลับได้ไหม (มีกุญแจยาวพอ + มี openssl) |
| `Stock_encryptSecret($plain)` | `stock_secret_enc` | เข้ารหัส → 'v1:' + base64(iv 12 ไบต์ + tag 16 ไบต์ + ข้อมูล) · ไม่พร้อม = null |
| `Stock_decryptSecret($stored)` | `stock_secret_dec` | ถอดรหัสค่าจาก Stock_encryptSecret — กุญแจผิด / ข้อมูลเสีย = null |

## `api/core.php` — บทบาท · สิทธิ์ · เมนูที่เปิด / ปิด · ตัวช่วยจัดรูปแบบ (44)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_getActiveRoles()` | `active_roles` | บทบาทที่เปิดใช้งานอยู่ตอนนี้ — ฝ่ายบัญชีปิดได้จากหลังบ้าน (กลุ่ม account) |
| `Stock_getFeatureGroups()` | `feature_groups` | กลุ่มฟีเจอร์ที่เปิด–ปิดได้จากหลังบ้าน: คีย์ => array(label, hint, pages = หน้าที่ปิดตาม, role = บทบาทที่ปิดตาม) · ปิดแล้วซ่อนเมนู + เปิด URL ตรงไม่ได้ · เมนูหลัก (ขาย เปิด–ปิดร้าน รับเข้า เบิก สินค้า ประวัติ หน้าผู้ดูแล) ปิดไม่ได้ |
| `Stock_getFeaturesOff()` | `features_off` | กลุ่มที่ปิดอยู่ (คีย์ของ Stock_getFeatureGroups) |
| `Stock_isFeatureGroupOn($key)` | `feature_group_on` | กลุ่มฟีเจอร์นี้เปิดอยู่ไหม (ไม่ได้ถูกปิดจากหลังบ้าน) |
| `Stock_setFeaturesOff($off, $uid = 0)` | `features_off_set` | บันทึกกลุ่มที่ปิด — คืนรายการที่เปลี่ยน array(คีย์ => true เปิด \| false ปิด) |
| `Stock_getPagesOff()` | `feature_pages_off` | หน้าที่ถูกปิดอยู่ตอนนี้ (ตามกลุ่มที่ปิด) |
| `Stock_getActiveMenus()` | `active_menus` | เมนูที่ทำเสร็จแล้วและเปิดให้ใช้ (ยังไม่หักกลุ่มที่ปิด — ใช้ Stock_isMenuEnabled) |
| `Stock_getActiveFeatures()` | `active_features` | ส่วนประกอบที่ยังไม่ได้ใช้ เปิดทีหลังโดยเติมชื่อลงใน array นี้ · 'search' = ช่องค้นหาบนแถบบน · 'branch_pick' = ตัวเลือกสาขาบนแถบบน |
| `Stock_isFeatureEnabled($f)` | `feature_enabled` | ส่วนประกอบนี้เปิดใช้ไหม (ดู Stock_getActiveFeatures) |
| `Stock_isRoleEnabled($role)` | `role_enabled` | บทบาทนี้เปิดใช้งานอยู่ไหม |
| `Stock_isMenuEnabled($file)` | `menu_enabled` | เมนูนี้เปิดให้ใช้ไหม — ต้องมีในระบบ และกลุ่มของเมนูไม่ได้ถูกปิดจากหลังบ้าน |
| `Stock_getRoles()` | `roles_all` | บทบาทในระบบ: คีย์ => array(name ชื่อ, scope ขอบเขตที่เห็น) |
| `Stock_getPermList()` | `perm_list` | รายการสิทธิ์ทั้งหมด เรียงตามที่แสดงบนหน้าติ๊กสิทธิ์ |
| `Stock_getManagerImpliedPerms()` | `perm_manager_implies` | สิทธิ์ที่ผู้จัดการสาขาได้อัตโนมัติ (ไม่ต้องติ๊กซ้ำ) — ดูข้อมูลทุกอย่าง + สิทธิ์เสริมทุกตัว |
| `Stock_getHistoryPerms()` | — (ใหม่ 15 ต.ค.) | สิทธิ์ดูประวัติรายเมนู 6 ตัว (แทนคีย์เดิม history — Stock_cleanPerms แปลงให้ตอนอ่าน) |
| `Stock_getManagerGrantablePerms()` | `perm_manager_grantable` | สิทธิ์ที่ผู้จัดการสาขาติ๊กให้พนักงานในสาขาได้ = ทุกหมวดยกเว้นสิทธิ์เสริม (สิทธิ์เสริม + ตั้งผู้จัดการ เป็นของผู้ดูแล) |
| `Stock_isBranchManager($user)` | `is_branch_manager` | เป็นผู้จัดการสาขาไหม (พนักงานที่ผู้ดูแลติ๊กสิทธิ์ manager — สาขาหนึ่งมีได้หลายคน) |
| `Stock_getUserRoleLabel($user)` | `user_role_label` | ชื่อบทบาทที่แสดงใต้ชื่อ (แถบบน / หน้าเข้าระบบ) — ผู้จัดการสาขาแยกจากพนักงาน |
| `Stock_getPermGroups()` | `perm_groups` | หมวดของสิทธิ์ (หัวข้อบนหน้าติ๊กสิทธิ์) — extra = สิทธิ์เสริมที่ต้องไว้ใจ |
| `Stock_getDefaultPerms()` | `perm_default` | สิทธิ์เริ่มต้นของพนักงานที่เพิ่มใหม่ — เท่ากับที่พนักงานใหม่ได้ก่อนช่วงที่ 11 · ไม่รวมเปิดร้านอีกครั้ง / รับคืน / หมวดสินค้า / สิทธิ์เสริม (ผู้ดูแลติ๊กเองรายคน) |
| `Stock_cleanPerms($keys)` | `perm_clean` | เรียงตาม Stock_getPermList · ตัดคีย์ที่ไม่รู้จัก · ตัดสิทธิ์ที่ขาดตัวที่ต้องมี (needs) ออก · คีย์เดิม history → สิทธิ์ดูประวัติ 6 ตัว (15 ต.ค.) |
| `Stock_convertLegacyPerms($old)` | `perm_from_legacy` | สิทธิ์ชุดเดิม (ก่อนช่วงที่ 11) → ชุดใหม่ ให้ทำได้เท่าเดิมทุกอย่าง ไม่มีใครเสียสิทธิ์ · sale → ขาย ส่วนลด เปิด–ปิดร้าน เงินเข้าออก แก้บิลตัวเอง · receive / issue / stocktake → + แก้เอกสารคลังตัวเอง · refund → + คืนเงินสด · category → เพิ่ม + ลบหมวด · ทุกคนได้ดูสินค้า / เคลื่อนไหวรายสินค้า / รายงานของตัวเอง |
| `Stock_getPagePerm($file)` | `page_perm` | สิทธิ์ที่ต้องมีเพื่อเปิดหน้านั้น — '' = ไม่ต้องมี · array = มีตัวใดตัวหนึ่งก็พอ |
| `Stock_canOpenPage($user, $file)` | `page_perm_ok` | ผู้ใช้คนนี้เปิดหน้านี้ได้ไหม (ไม่สนว่าเมนูถูกปิดจากหลังบ้านหรือเปล่า — ใช้ Stock_isPageLinkVisible สำหรับลิงก์) |
| `Stock_isPageLinkVisible($user, $file)` | `page_ok` | ลิงก์ไปหน้านี้ควรแสดงไหม — เมนูเปิดอยู่ และผู้ใช้มีสิทธิ์ |
| `Stock_getUserPerms($user)` | `user_perms` | สิทธิ์เสริมที่ผู้ใช้คนนี้มี |
| `Stock_userCan($user, $perm)` | `can` | ผู้ใช้คนนี้มีสิทธิ์นี้ไหม |
| `Stock_canVoidDoc($user, $doc, $kind = 'doc')` | `can_void_doc` | แก้/ยกเลิกเอกสารใบนี้ได้ไหม — ของตัวเองต้องมีสิทธิ์ bill_fix (บิล) / doc_fix (เอกสารคลัง) · ของคนอื่นต้องมีสิทธิ์ void_others · $kind = 'bill' บิลขาย · 'doc' ใบรับเข้า / เบิก / ตรวจนับ · ใช้กับเอกสารวันนี้ (วันก่อนใช้ past_can_edit ที่เช็ก backdate + จำนวนวัน) |
| `Stock_escHtml($v)` | `e` | กัน XSS — แปลงข้อความเป็น HTML ที่ปลอดภัย (htmlspecialchars) |
| `Stock_getRoleName($code)` | `role_name` | ชื่อบทบาทเป็นภาษาไทย |
| `Stock_getRoleScope($code)` | `role_scope` | ขอบเขตข้อมูลที่บทบาทนี้เห็น |
| `Stock_pageUrl($path)` | `url` | URL ของหน้าในระบบ (ต่อท้าย APP_BASE) |
| `Stock_assetUrl($path)` | `asset_url` | URL ของไฟล์ใน themes/aostock/assets/ (css, js, รูป) · หน้าเว็บวิ่งผ่าน router ของ admweb จึงต่อจาก THEME_URL (inc.php) แทน path relative |
| `Stock_formatMoneyWhole($n)` | `money` | ตัวเลขเงินแบบไม่มีทศนิยม เช่น 1,250 |
| `Stock_formatShortDay($ts)` | `short_day` | ชื่อวันแบบสั้น เช่น "อา 21" |
| `Stock_formatThaiMonthShort($ts)` | `thai_month_short` | ชื่อเดือนไทยแบบย่อ เช่น ก.ย. |
| `Stock_formatThaiDateFull($ts)` | `thai_date_full` | วันที่ไทยแบบเต็ม เช่น 5 ตุลาคม 2569 |
| `Stock_formatMoney($n)` | `money2` | ตัวเลขเงินทศนิยม 2 ตำแหน่ง เช่น 1,250.00 |
| `Stock_formatThaiWeekdayShort($ts)` | `thai_dow_short` | ชื่อวันแบบย่อ อา. จ. อ. … |
| `Stock_sendCsv($filename, $head, $rows)` | `csv_send` | ส่งไฟล์ CSV ให้ดาวน์โหลดแล้วจบการทำงาน — ใส่ BOM ให้ Excel อ่านภาษาไทยถูก · $head = หัวคอลัมน์ · $rows = array ของแถว (ตัวเลขส่งดิบ ไม่ใส่ comma) |
| `Stock_formatCsvMoney($n)` | `csv_money` | ตัวเลขเงินสำหรับ CSV (ทศนิยม 2 ตำแหน่ง ไม่มี comma) |
| `Stock_formatThaiDayMonth($ts)` | `thai_day_month` | วันที่ + เดือนไทยแบบย่อ เช่น 5 ต.ค. |
| `Stock_compareTotalDesc($a, $b)` | `cmp_total_desc` | ตัวเรียง (usort): ยอด total มากไปน้อย |
| `Stock_formatThaiMonthFull($ts)` | `thai_month_full` | ชื่อเดือนไทยเต็ม + ปี พ.ศ. เช่น ตุลาคม 2569 |

## `api/auth.php` — เข้า / ออกจากระบบ · CSRF · จดจำเครื่อง · ล็อกตาม IP · กันหน้า (39)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_randomToken()` | `csrf_random_token` | สุ่มสตริงสำหรับ CSRF token (64 ตัวอักษร hex) |
| `Stock_getCsrfToken()` | `csrf_token` | CSRF token ของ session นี้ (สร้างครั้งแรกที่เรียก) |
| `Stock_checkCsrf($token)` | `csrf_check` | ตรวจ CSRF token ที่ส่งมากับฟอร์ม |
| `Stock_getCurrentUser()` | `current_user` | ผู้ใช้ที่เข้าระบบอยู่ (จาก session) หรือ null |
| `Stock_isLoggedIn()` | `is_logged_in` | เข้าระบบอยู่ไหม |
| `Stock_getSessionLockLeft()` | `login_locked` | session นี้ถูกล็อกจากการกรอกผิดอยู่อีกกี่วินาที (0 = ไม่ล็อก) |
| `Stock_addSessionLoginFail()` | `login_failed` | นับการกรอกผิดใน session — ครบ 5 ครั้งล็อก 1 นาที |
| `Stock_resetSessionLoginFail()` | `login_reset_fail` | ล้างตัวนับการกรอกผิดของ session |
| `Stock_buildSessionUser($key, $u)` | `user_session_row` | แปลงข้อมูลผู้ใช้เป็นรูปที่เก็บใน session |
| `Stock_checkPinLogin($username, $pin)` | `attempt_pin_login` | เข้าระบบด้วย PIN — ใช้กับพนักงานหน้างาน (แท็บเล็ต/มือถือ) · ผู้เรียกต้องเช็ก staff_locked_left() ก่อน แล้วเรียก staff_login_failed() / staff_login_ok() ตามผล · ผิด 5 ครั้งล็อกบัญชี 15 นาที + ล็อก session 1 นาที |
| `Stock_checkPasswordLogin($username, $password)` | `attempt_login` | ตรวจรหัสผ่านของผู้ดูแล / ฝ่ายบัญชี |
| `Stock_loginUser($user)` | `login_user` | เข้าระบบ — เก็บผู้ใช้ลง session + เปลี่ยน session id |
| `Stock_logoutUser()` | `logout_user` | ออกจากระบบ — ล้างเฉพาะข้อมูลของ POS ใน session |
| `Stock_setCookie($name, $value, $expires)` | `pos_cookie` | ตั้ง / ลบ cookie ของ POS ($value = '' คือลบ) — httponly · SameSite=Lax · secure เมื่อเป็น https · path = โฟลเดอร์ของเว็บ |
| `Stock_setRememberCookie($value, $expires)` | `remember_cookie` | ตั้ง / ลบ cookie จดจำ ($value = '' คือลบ) |
| `Stock_rememberDevice($user)` | `remember_issue` | เริ่มจดจำเครื่องนี้ให้ผู้ใช้ที่เพิ่งเข้าระบบ (เรียกหลัง Stock_loginUser) |
| `Stock_loginByRememberCookie()` | `remember_login` | ยังไม่ได้เข้าระบบแต่มี cookie จดจำ → ตรวจแล้วเข้าระบบให้ (เปลี่ยน validator ใหม่) · คืนผู้ใช้ หรือ null · ไม่เข้าให้ถ้าหมดอายุ / พักงาน / บทบาทถูกปิด / ไม่ใช่ผู้ดูแลหรือบัญชี / บัญชีถูกล็อก · validator ผิด / เปลี่ยนรหัสผ่าน / พักงาน / ลบบัญชี = ลบทุกเครื่องของคนนั้น · ออกจากระบบ = ลบเครื่องนี้ · cookie httponly + SameSite=Lax + secure เมื่อ https |
| `Stock_forgetThisDevice()` | `remember_forget` | ลบการจดจำของเครื่องนี้ (ออกจากระบบ) |
| `Stock_forgetAllDevicesOfUser($username)` | `remember_forget_staff` | ลบการจดจำทุกเครื่องของผู้ใช้คนนี้ (เปลี่ยนรหัสผ่าน / พักงาน) |
| `Stock_getCloudflareIpRanges()` | `cf_ip_ranges` | ช่วง IP ของ Cloudflare (https://www.cloudflare.com/ips/) — เว็บอยู่หลัง Cloudflare: REMOTE_ADDR เป็นเครื่องของ Cloudflare ไม่ใช่ผู้ใช้ · ที่มา https://www.cloudflare.com/ips/ · ไม่เติมช่วงใหม่ = ผู้ใช้ที่ผ่านช่วงใหม่ถูกนับเป็น IP ของ Cloudflare |
| `Stock_isIpInCidr($ip, $cidr)` | `ip_in_cidr` | $ip อยู่ในช่วง $cidr ไหม (IPv4 / IPv6) |
| `Stock_getClientIp()` | `client_ip` | IP ของผู้ใช้ที่ส่งคำขอนี้ · header ที่คนนอกปลอมส่งตรงเข้าเซิร์ฟเวอร์ไม่มีผล |
| `Stock_getIpKey($ip)` | `ip_key` | คีย์ของ IP ในตาราง — IPv6 นับรวมทั้งวง /64 (เครื่องเดียวเปลี่ยน IPv6 ในวงเดียวกันได้เรื่อย ๆ) |
| `Stock_hasLoginIpTable()` | `ip_table_ok` | มีตาราง ao_stock_login_ip แล้วหรือยัง (ยังไม่ได้กด Reinstall = ยังไม่มี) — เช็กครั้งเดียวต่อ request ไม่ให้ error_log เต็ม |
| `Stock_getIpLockLeft()` | `ip_locked_left` | IP นี้ถูกล็อกจากการกรอกผิดอยู่ไหม — คืนจำนวนวินาทีที่เหลือ (0 = ไม่ล็อก) |
| `Stock_addIpLoginFail()` | `ip_login_failed` | กรอกผิด 1 ครั้งจาก IP นี้ (บัญชีไหนก็ได้ รวมชื่อผู้ใช้ที่ไม่มีจริง) — รอบละ IP_LOCK_MINUTES นาทีนับจากครั้งแรก · นับทุกบัญชีรวมกัน (รวมชื่อที่ไม่มีจริง) กันการสุ่มหลายบัญชี · เข้าสำเร็จไม่ล้างตัวนับ (หมดรอบเอง) · ยังไม่มีตาราง ao_stock_login_ip = ข้าม |
| `Stock_signDeviceStaffIds($raw)` | `known_sign` | ลายเซ็นของรายการ id ใน cookie (null = ไม่มีกุญแจ) |
| `Stock_getDeviceStaffIds()` | `known_ids` | staff_id ที่เครื่องนี้จำไว้ (คนล่าสุดก่อน) — cookie ผิดรูป / ลายเซ็นไม่ตรง = ไม่มี |
| `Stock_saveDeviceStaffIds($ids)` | `known_save` | เขียนรายการ id ลง cookie (ว่าง = ลบ cookie) |
| `Stock_addDeviceStaff($user)` | `known_add` | จำคนนี้ไว้บนเครื่อง (ขึ้นเป็นคนแรก) — เรียกหลังเข้าระบบด้วย PIN สำเร็จ |
| `Stock_forgetDeviceStaff($id)` | `known_forget` | เอาชื่อนี้ออกจากเครื่อง |
| `Stock_getDeviceStaff()` | `known_staffs` | พนักงานที่เครื่องนี้จำไว้และยังเข้าระบบด้วย PIN ได้ — array( username => ข้อมูล ) คนล่าสุดก่อน · cookie aostock_known ลงลายเซ็น HMAC (AOSTOCK_SECRET_KEY — ไม่มีกุญแจ = ไม่จำ) · สูงสุด KNOWN_MAX คน / KNOWN_DAYS วัน · คนพักงาน / ถูกลบไม่แสดง · ออกจากระบบไม่ลบ (ลบรายคนที่ปุ่ม ×) |
| `Stock_getCurrentPage()` | `current_page` | ชื่อไฟล์ของหน้าที่กำลังเปิด เช่น sale.php · หน้าเว็บวิ่งผ่าน index.php ของ admweb จึงอ่านจาก $_getdata['page'] |
| `Stock_sendRedirect($to)` | — (ใหม่ 14 ต.ค.) | พาไปหน้าอื่นแล้วจบ request — คำขอจาก htmx ใช้ HX-Redirect (เปลี่ยนทั้งหน้า) · อื่น ๆ ใช้ Location |
| `Stock_requireLogin()` | `require_login` | ใส่บรรทัดนี้ไว้บนสุดของทุกหน้าที่ต้องล็อกอินก่อน |
| `Stock_getAccountPages()` | `account_pages` | หน้าที่ฝ่ายบัญชีเข้าได้ |
| `Stock_getHomePage($user)` | `home_page` | หน้าแรกหลังเข้าระบบของแต่ละบทบาท |
| `Stock_getAdminPageMap()` | `admin_page_map` | หน้าของพนักงาน → หน้าชุด adm- ที่คู่กันของผู้ดูแล ('' = ผู้ดูแลไม่ใช้หน้านี้ กลับหน้าแรก) |
| `Stock_getAdminSharedPages()` | `admin_shared_pages` | หน้าที่ผู้ดูแลใช้ร่วมกับบทบาทอื่น (ไม่ต้องมีชุด adm-) |

## `api/log.php` — ประวัติการทำรายการ (14)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_addLog($code, $type, $user, $title, $detail = array(), $amount = null, $ref = '')` | `log_add` | บันทึกหนึ่งรายการ — สาขาที่ไม่มีในระบบ (เช่น ผู้ดูแลยังไม่เลือกสาขา) ลงเป็น branch_id 0 |
| `Stock_shapeLogRow($r)` | `log_shape` | แถวใน ao_stock_log (+ ชื่อผู้ทำ) → รูปแบบที่หน้าเว็บใช้ · แถวที่หน้าเว็บใช้: ts · time HH:MM · type · by · by_user · title · amount (หรือ null) · ref · detail array(หัวข้อ => ค่า) · เอกสารคลังเขียนประวัติในทรานแซกชันเดียวกับเอกสาร |
| `Stock_getLogToday($code, $limit = 0, $noSetting = false)` | `log_today` | ประวัติของสาขาวันนี้ — เรียงใหม่สุดขึ้นก่อน |
| `Stock_getLogOfDay($code, $ts, $limit = 0, $noSetting = false)` | `log_of_day` | ประวัติของสาขาในวันหนึ่ง — เรียงใหม่สุดขึ้นก่อน · รายการ "หมวดสินค้า" ทุกคนเห็น · ผู้จัดการ / ผู้ดูแลเห็นรายการตั้งค่าด้วย |
| `Stock_getLogRange($codes, $from, $to, $type = '', $dir = 'desc', $page = 1, $per = 100, $noSetting = false)` | `log_range` | ประวัติของหลายสาขา ช่วงวันที่ $from–$to (ประวัติรวมของผู้ดูแล · ประวัติรายเดือน / รายปีของพนักงาน 20 ต.ค.) — นับและแบ่งหน้าใน SQL · $noSetting = ไม่เอารายการตั้งค่า ทั้งตัวนับและแถว (พนักงานทั่วไป) · $type เป็น array ของชนิดได้ (ปุ่มกรองย่อยของประวัติขาย 20 ต.ค. — ชนิดที่ไม่รู้จักไม่มีแถว) · $type = คีย์กลุ่ม (หรือชนิด — นับเป็นกลุ่มของมัน · 15 ต.ค.) · rows = log_shape + branch + date (Ymd) + void / void_mode / void_by + returned · total = จำนวนตามกลุ่ม · counts = ต่อชนิดทั้งช่วง · perB = ต่อสาขาทุกชนิด · perBG = ต่อสาขาเฉพาะกลุ่ม · $dir = desc / asc · $page เริ่มที่ 1 |
| `Stock_getLogTypes()` | `log_types` | ชนิดของรายการ — ป้ายกำกับ สี และไอคอน |
| `Stock_getLogType($key)` | `log_type_of` | ข้อมูลของชนิดรายการหนึ่ง (ป้าย สี ไอคอน) |
| `Stock_filterLogs($rows, $type)` | `log_filter` | กรองตามกลุ่ม (หรือชนิด — นับเป็นกลุ่มของมัน) — ใช้กับปุ่มกรองบนหน้าประวัติ |
| `Stock_getLogGroups()` | — (ใหม่ 15 ต.ค.) | กลุ่มของประวัติ 1 กลุ่ม = 1 เมนู — รายการยกเลิกอยู่กลุ่มเดียวกับเอกสารของมัน (ขาย / ตั้งค่า · นำเข้า · ตัดออก · ตรวจนับ · รับคืน · เปิด / ปิดร้าน) + perm / feature ของกลุ่ม |
| `Stock_getAllowedLogGroups($user)` | — (ใหม่ 15 ต.ค.) | กลุ่มประวัติที่ผู้ใช้คนนี้ดูได้ — มีสิทธิ์ของกลุ่ม และฟีเจอร์ของกลุ่มไม่ได้ถูกปิด |
| `Stock_getLogGroupOf($key)` | — (ใหม่ 15 ต.ค.) | คีย์กลุ่มของคีย์กลุ่มหรือคีย์ชนิด (ลิงก์เก่า ?t=ivoid → issue) · ไม่รู้จัก = '' |
| `Stock_sumLogGroups($counts)` | — (ใหม่ 15 ต.ค.) | จำนวนต่อกลุ่ม จากจำนวนต่อชนิด |
| `Stock_countLogsByType($rows)` | `log_counts` | สรุปจำนวนรายการแยกตามชนิด (ไว้โชว์บนปุ่มกรอง) |
| `Stock_historyUrl($q = '')` | `hist_url` | ลิงก์ของหน้าประวัติ — ใช้ร่วมกันระหว่าง history.php (พนักงาน) กับ adm-history.php (ผู้ดูแล) · ใช้ร่วม history.php กับ adm-history.php · เช่น $HIST_BASE = 'history.php?t=sale' หรือ 'adm-history.php?view=day&b=RS' |

## `api/branch-staff.php` — สาขา · พนักงาน / ผู้ดูแล · สิทธิ์ · PIN (76)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_getBranchRows($reset = false)` | `branch_db_rows` | ทุกแถวของ ao_stock_branch เรียงตาม sort — array( รหัส => แถว ) · อ่านทั้งตารางครั้งเดียวต่อ request (static) — function ที่เขียนล้าง cache ให้เอง |
| `Stock_getBranchRow($code)` | `branch_row` | แถวของสาขาเดียว หรือ null |
| `Stock_getAllBranches()` | `branches_all` | ทุกสาขา รวมที่ปิดใช้งาน — ใช้ตอนต้องแสดงประวัติ / ชื่อสาขาเก่า |
| `Stock_getActiveBranches()` | `branches_active` | สาขาที่เปิดใช้งาน — ใช้ทั่วไป (ตัวเลือกสาขา ย้ายพนักงาน ภาพรวม) |
| `Stock_createBranch($code, $info, $settings)` | `branch_create` | เพิ่มสาขาใหม่ — $info: name short address phone · $settings: ค่าตั้ง 4 ค่า (Stock_getBranchSettingRules) · $info: name short address phone · $settings: ค่าตั้ง 4 ค่า (branch_setting_rules) · เลขที่บิลเริ่ม {รหัส}V / {รหัส} · เลขผู้เสียภาษีคัดลอกจากสาขาแรก · เลขที่สาขา (ภาษี) = สูงสุด + 1 · ตรวจจำนวนสาขาซ้ำใน transaction |
| `Stock_updateBranchInfo($code, $info)` | `branch_update_info` | แก้ชื่อ / ชื่อย่อ / ที่อยู่ / เบอร์โทรของสาขา — $info: name short address phone |
| `Stock_setBranchActive($code, $on)` | `branch_set_active` | เปิด / ปิดใช้งานสาขา |
| `Stock_deleteBranch($code)` | `branch_delete` | ลบสาขา — เรียกหลังเช็ก Stock_getBranchDataReason() แล้วเท่านั้น (สาขาที่ยังไม่มีข้อมูล) · ประวัติชนิด setting ของสาขานี้ลบไปด้วย ไม่ให้เหลือแถวที่ชี้สาขาที่ไม่มีแล้ว |
| `Stock_getBranchLimit()` | `branch_limit` | จำนวนสาขาสูงสุด (0 = ไม่จำกัด) · ตั้งที่หลังบ้าน (ao_stock_setting key branch_limit) · นับรวมสาขาที่ปิดใช้งาน (กันปิดเก่าเปิดใหม่วนไป) · เปิดสาขาที่ปิดไว้กลับได้เสมอ · ลบสาขาได้โควตาคืน · ตั้งต่ำกว่าที่มี = สาขาเดิมใช้ต่อได้ แค่เพิ่มใหม่ไม่ได้ |
| `Stock_formatBranchLimit($n)` | `branch_limit_label` | ข้อความของจำนวนสาขาสูงสุด เช่น "3 สาขา" / "ไม่จำกัด" |
| `Stock_setBranchLimit($n, $by)` | `branch_limit_set` | ตั้งจำนวนสาขาสูงสุด (หลังบ้าน admweb) + ลงประวัติของระบบ — คืน true ถ้าค่าเปลี่ยน · $by = ชื่อผู้แก้สำหรับประวัติ |
| `Stock_getBranchLimitError()` | `branch_limit_error` | เพิ่มสาขาใหม่ได้อีกไหม — '' = ได้ · ไม่ได้ = ข้อความบอกผู้ดูแล POS |
| `Stock_getBranchSettingRules()` | `branch_setting_rules` | ช่วงค่าที่ยอมให้ตั้ง: array(ต่ำสุด, สูงสุด) · count_day = รอบตรวจนับเริ่มวันที่ (1–28) · backdate_days = แก้เอกสาร / รับคืนย้อนหลังได้กี่วัน · default_float = เงินทอนมาตรฐาน |
| `Stock_getBranchSetting($code, $key)` | `branch_setting` | ค่าตั้งหนึ่งค่าของสาขา (รอบนับ / ย้อนหลัง / นับครั้งละ / เงินทอน) |
| `Stock_setBranchSetting($code, $key, $val)` | `branch_setting_set` | บันทึกค่าตั้งหนึ่งค่าของสาขา (ตัดให้อยู่ในช่วงที่ยอม) |
| `Stock_getStocktakeRoundDay($code)` | `count_round_day` | รอบตรวจนับเริ่มวันที่เท่าไรของเดือน |
| `Stock_getBackdateDays($code = null)` | `backdate_days` | ย้อนหลังได้ไม่เกินกี่วัน (สิทธิ์ backdate / refund) — ผู้ดูแลตั้งรายสาขา |
| `Stock_getStaffRows($reset = false)` | `staff_db_rows` | ทุกแถวของ ao_stock_staff (+ รหัสสาขา) — array( username => แถว ) · มี hash ด้วย ใช้ภายในเท่านั้น · พนักงาน = ชื่อผู้ใช้ + PIN 4 หลัก · ผู้ดูแล / บัญชี = ชื่อผู้ใช้ + รหัสผ่าน · ผิด STAFF_LOCK_FAILS ครั้งล็อก STAFF_LOCK_MINUTES นาที · ผู้ดูแล / บัญชีจัดการที่หลังบ้าน · ประวัติประจำสาขาใน ao_stock_staff_branch (แถวปัจจุบัน date_to = NULL) |
| `Stock_getStaffBranchHistory($reset = false)` | `staff_db_history` | ประวัติการประจำสาขาของทุกคน — array( staff_id => array( array(branch, from, to\|null), ... ) ) เรียงตามวันที่ |
| `Stock_resetStaffCache()` | `staff_db_reset` | ล้าง cache ของผู้ใช้หลังเขียน |
| `Stock_parsePermsCsv($csv)` | `staff_perms_csv` | สิทธิ์จากคอลัมน์ perms (คั่นด้วย ,) — เฉพาะที่มีใน Stock_getPermList เรียงตามลำดับเดิม |
| `Stock_buildPermsCsv($perms)` | `perm_csv` | สิทธิ์ → ค่าที่เก็บในคอลัมน์ perms (มีตัวบอกรุ่นนำหน้าเสมอ) |
| `Stock_getAllUsers()` | `users_all` | ผู้ใช้ทุกคน รวมที่พักงาน — array( username => ข้อมูล ) · ข้อมูล: id name role branch initials perms active · since = วันเริ่มประจำสาขาปัจจุบัน · history = array(branch, from, to) · hash อยู่ที่ staff_auth_row |
| `Stock_getStaffAuthRow($username)` | `staff_auth_row` | แถวที่ใช้ตรวจ PIN / รหัสผ่าน (มี hash + ตัวนับกรอกผิด) หรือ null |
| `Stock_getBranchId($code)` | `branch_id_of` | id ของสาขาจากรหัส (ไม่มี = 0) |
| `Stock_createStaff($d)` | `staff_create` | เพิ่มผู้ใช้ — $d: username name initials role branch(รหัส) perms(array) และ pin (staff) หรือ password (admin / account) |
| `Stock_updateStaff($username, $name, $initials, $perms)` | `staff_update` | แก้ชื่อ / อักษรย่อ / สิทธิ์ |
| `Stock_renameStaff($username, $new)` | `staff_rename` | เปลี่ยนชื่อผู้ใช้ (ตรวจด้วย Stock_checkStaffUsername ก่อนเรียก) — เอกสาร / ประวัติ / การจดจำเครื่อง ผูกกับ staff_id จึงไม่ต้องแก้ที่อื่น · คนที่เข้าระบบอยู่ด้วยชื่อเดิมถูกพาออกที่หน้าถัดไป แล้วเข้าใหม่ด้วยชื่อใหม่ |
| `Stock_setStaffPin($username, $pin)` | `staff_set_pin` | ตั้ง PIN ใหม่ (ปลดล็อกด้วย) |
| `Stock_setStaffPassword($username, $password)` | `staff_set_password` | ตั้งรหัสผ่านใหม่ของผู้ดูแล / บัญชี (ปลดล็อกด้วย) |
| `Stock_moveStaff($username, $toCode)` | `staff_move` | ย้ายสาขาตั้งแต่วันนี้ — ปิดช่วงเดิม (ถึงเมื่อวาน) แล้วเปิดช่วงใหม่ · ย้ายซ้ำในวันเดียวกัน = แก้แถวของวันนี้ |
| `Stock_setStaffActive($username, $on)` | `staff_set_active` | พักงาน / เปิดใช้งาน |
| `Stock_deleteStaff($username)` | `staff_delete` | ลบผู้ใช้ — เรียกหลังเช็ก Stock_getStaffDataReason() แล้วเท่านั้น (คนที่ยังไม่เคยทำรายการ) |
| `Stock_getStaffLockLeft($username)` | `staff_locked_left` | ถูกล็อกเพราะกรอกผิดหลายครั้งอยู่ไหม — คืนจำนวนวินาทีที่เหลือ (0 = ไม่ล็อก) |
| `Stock_addStaffLoginFail($username)` | `staff_login_failed` | กรอกผิด 1 ครั้ง — ครบ STAFF_LOCK_FAILS ครั้ง ล็อก STAFF_LOCK_MINUTES นาที แล้วเริ่มนับใหม่ |
| `Stock_markStaffLoginOk($username)` | `staff_login_ok` | เข้าระบบสำเร็จ — ล้างตัวนับ + บันทึกเวลาเข้าล่าสุด |
| `Stock_getWorkBranch($user)` | `work_branch` | สาขาที่กำลังทำงานอยู่ · ผู้ดูแลเลือกจาก ?branch= แล้วระบบจำไว้ |
| `Stock_getBranchStaff($code)` | `branch_staff` | พนักงาน (ไม่รวมผู้ดูแล) ที่ประจำสาขานี้ตอนนี้ |
| `Stock_isUserActive($u)` | `user_active` | บัญชีนี้ยังใช้งานอยู่ไหม (พนักงานที่พักงาน / ลาออก = ไม่ใช้งาน แต่ประวัติยังอยู่) |
| `Stock_getActiveUsers()` | `users_active` | ผู้ใช้ที่ยังใช้งานอยู่และบทบาทเปิดอยู่ — array( username => ข้อมูล ) |
| `Stock_getUserStartDate($u)` | `user_start_date` | วันแรกที่เริ่มงาน (ไว้กำหนดช่วง "ทั้งหมด") |
| `Stock_getUserInitial($u)` | `user_initial` | ชื่อย่อสำหรับวงกลม avatar |
| `Stock_getBranchName($code)` | `branch_name` | ชื่อเต็มของสาขาจากรหัส |
| `Stock_getVisibleBranches($user)` | `visible_branches` | สาขาที่ผู้ใช้คนนี้เลือกดูได้ |
| `Stock_resolveViewBranch($user, $requested)` | `resolve_branch` | สาขาที่จะแสดง — ค่าที่ขอมาถ้าผู้ใช้ดูได้ ไม่งั้นผู้ดูแล = ALL · พนักงาน = สาขาตัวเอง |
| `Stock_getBranchLabel($code)` | `branch_label` | ชื่อสาขา หรือ "ทุกสาขา" เมื่อรหัสเป็น ALL |
| `Stock_getFullUser($user)` | `full_user` | ข้อมูลเต็มของผู้ใช้ (รวมประวัติการย้ายสาขา) — session เก็บไว้แค่ไม่กี่ฟิลด์เพื่อให้เบา |
| `Stock_getBranchDataReason($code)` | `branch_data_reason` | สาขานี้มีข้อมูลแล้วหรือยัง — คืนเหตุผล (ว่าง = ยังไม่มี ลบได้) · ประวัติชนิด setting และ category ไม่นับ (ลบไปพร้อมสาขา) |
| `Stock_readBranchSettingsForm($fields)` | `read_settings` | อ่านค่าตั้ง 4 ค่าจากฟอร์ม — คืน array(ค่า, ข้อผิดพลาด) |
| `Stock_readBranchInfoForm($info)` | `read_info` | อ่านชื่อ / ชื่อย่อ / ที่อยู่ / เบอร์โทรของสาขาจากฟอร์ม — คืน array(ค่า, ข้อผิดพลาด) |
| `Stock_compareBranchList($a, $b)` | `branch_list_cmp` | ตัวเรียง (uasort): สาขาที่เปิดก่อน แล้วตามลำดับ |
| `Stock_getAllStaff()` | `staff_all` | พนักงานทั้งหมด (รวมที่พักงาน ไม่รวมผู้ดูแล / บัญชี) |
| `Stock_getPermNames($keys)` | `perm_names` | ชื่อสิทธิ์ภาษาไทยของรายการคีย์ คั่นด้วย · |
| `Stock_getPermChanges($before, $after)` | `perm_changes` | สิทธิ์ที่เปลี่ยน สำหรับลงประวัติ — array('เพิ่มสิทธิ์' => ชื่อ, 'เอาสิทธิ์ออก' => ชื่อ) เฉพาะฝั่งที่มี |
| `Stock_getPermSummary($perms)` | `perm_summary` | สรุปสิทธิ์รายหมวด สำหรับตารางรายชื่อ — array( หมวด => array(have, total, names) ) เฉพาะหมวดที่มีอย่างน้อย 1 ตัว · คืน array(หมวด => array(have, total, names)) เฉพาะหมวดที่มี ≥ 1 ตัว · ผู้จัดการแสดงเป็นป้ายข้างชื่อแทน |
| `Stock_makeInitials($name)` | `auto_initials` | อักษรย่อจากชื่อ — ตัวแรกของชื่อและนามสกุล (ข้ามสระ/วรรณยุกต์ที่วางบน-ล่าง) |
| `Stock_getStaffDataReason($k)` | `staff_data_reason` | เคยทำรายการแล้วหรือยัง — คืนเหตุผล (ว่าง = ยังไม่เคย ลบได้) |
| `Stock_readPermsForm()` | `read_perms` | อ่านสิทธิ์จากฟอร์ม (เรียงตามลำดับใน Stock_getPermList · ตัดสิทธิ์ที่ขาดตัวที่ต้องมีออก — ช่วงที่ 11) |
| `Stock_getPinFingerprint($pin)` | `pin_fp` | ลายนิ้วมือของ PIN = HMAC-SHA256 ด้วย AOSTOCK_SECRET_KEY — เทียบ PIN ซ้ำได้โดยไม่ต้องรู้ PIN จริง · ไม่มีกุญแจ = null |
| `Stock_findPinOwner($branch, $pin, $except)` | `pin_owner` | PIN ซ้ำกับใครในสาขา (ว่าง = ไม่ซ้ำ) — ใช้ตอนตั้ง PIN ใหม่ (รู้ PIN จริง) · คนที่มีลายนิ้วมือเทียบด้วย pin_fp · คนที่ยังไม่มีเทียบด้วย password_verify |
| `Stock_findPinConflict($username, $branch)` | `staff_pin_conflict` | PIN ของ $username ซ้ำกับพนักงานที่ใช้งานอยู่ในสาขา $branch ไหม (ใช้ตอนย้ายสาขา / เปิดใช้งานกลับ — ไม่รู้ PIN จริง) · ไม่รู้ PIN จริง จึงเทียบลายนิ้วมือ — คนใดคนหนึ่งยังไม่มีลายนิ้วมือ = เช็กไม่ได้ |
| `Stock_renderPermBoxes($checked, $branch, $only = null)` | `perm_boxes` | ช่องติ๊กสิทธิ์ แยกตามหมวด (Stock_getPermGroups) — สิทธิ์ที่ต้องมีตัวอื่นก่อนมี data-needs ให้ footer.php ปิด/เปิดช่องตาม · สิทธิ์ที่ต้องมีตัวอื่นก่อนมี data-needs ให้ footer.php ปิด / เปิดช่องตาม · หน้าผู้จัดการไม่แสดงหมวดสิทธิ์เสริม |
| `Stock_getActorLabel($user)` | `actor_label` | บทบาทของผู้ทำ ต่อท้ายชื่อในประวัติ — (ผู้ดูแล) / (ผู้จัดการสาขา) |
| `Stock_checkStaffUsername($uname, $except = '')` | `staff_username_error` | ตรวจชื่อผู้ใช้ของพนักงาน — '' = ใช้ได้ · $except = ชื่อเดิมของคนที่กำลังแก้ (ไม่เปลี่ยน = ผ่านเสมอ แม้รูปแบบเก่าจะไม่ตรงกติกา) · พนักงานพิมพ์ชื่อนี้คู่กับ PIN ตอนเข้าระบบบนเครื่องใหม่ จึงต้องกรอกเอง |
| `Stock_doAddStaff($in, $actor)` | `staff_act_add` | เพิ่มพนักงาน — $in: name username initials branch pin perms · staff_act_* ใช้ร่วมกันระหว่าง adm-users.php / adm-user-add.php กับ team.php · ใครแก้ใครได้เช็กที่หน้า (ผู้จัดการใช้ manager_target_error ก่อนเรียก) |
| `Stock_doSaveStaff($username, $name, $ini, $perms, $actor, $newUser = null)` | `staff_act_save` | แก้ชื่อ / อักษรย่อ / สิทธิ์ / ชื่อผู้ใช้ ($name ตัดช่องว่างซ้ำแล้ว · $newUser = null คือไม่เปลี่ยนชื่อผู้ใช้) |
| `Stock_doResetStaffPin($username, $pin, $actor)` | `staff_act_pin` | รีเซ็ต PIN (ห้ามซ้ำกับคนอื่นในสาขา · ประวัติไม่แสดงเลข PIN) |
| `Stock_doSetStaffActive($username, $on, $why, $actor)` | `staff_act_active` | พักงาน / ลาออก ($on = false) หรือเปิดใช้งานอีกครั้ง ($on = true) |
| `Stock_doDeleteStaff($username, $sure, $actor)` | `staff_act_delete` | ลบพนักงาน — ได้เฉพาะคนที่ยังไม่เคยทำรายการ และต้องติ๊กยืนยัน ($sure) |
| `Stock_checkManagerTarget($manager, $username)` | `manager_target_error` | ผู้จัดการสาขาแก้พนักงานคนนี้ได้ไหม — '' = ได้ หรือเหตุผลที่ไม่ได้ · ได้เฉพาะพนักงาน (ไม่ใช่ผู้จัดการ รวมตัวเอง) ในสาขาเดียวกัน · ผู้จัดการคนอื่น / ย้ายสาขา / ตั้งผู้จัดการเป็นของผู้ดูแล |
| `Stock_mergeManagerPerms($ticked, $before)` | `perm_merge_by_manager` | สิทธิ์หลังผู้จัดการสาขาบันทึก = สิทธิ์ที่ติ๊กมา (เฉพาะหมวดที่ผู้จัดการให้ได้) + สิทธิ์เสริมเดิมของคนนั้น (ผู้ดูแลให้ไว้ ห้ามหาย) |
| `Stock_confirmStaffPin($username, $pin)` | `staff_pin_confirm` | ยืนยันตัวตนด้วย PIN ของตัวเองก่อนจัดการพนักงาน (เครื่อง POS ใช้ร่วมกัน) — '' = ผ่าน · เครื่อง POS ใช้ร่วมกัน · ผิดครบ STAFF_LOCK_FAILS ครั้งล็อก STAFF_LOCK_MINUTES นาที (เข้าระบบก็ไม่ได้จนพ้นเวลา) |
| `Stock_setManagerPerm($perms, $on)` | `perm_set_manager` | สิทธิ์ชุดนี้ + ตั้ง / ปลดผู้จัดการ |
| `Stock_renderPositionField($isManager)` | `staff_position_field` | ช่องเลือกตำแหน่ง (name="position" = staff \| manager) — วางบนสุดของฟอร์มเพิ่ม / แก้พนักงานของผู้ดูแล · ผู้จัดการสาขา = สิทธิ์ manager ใน perms · ตั้งได้ที่หน้าจัดการพนักงาน (ช่องตำแหน่ง) และหน้าจัดการสาขา |
| `Stock_setStaffPerms($username, $perms)` | `staff_set_perms` | เขียนเฉพาะสิทธิ์ (ไม่แตะชื่อ / อักษรย่อ) |
| `Stock_doSetBranchManager($username, $on, $actor)` | `staff_act_set_manager` | ตั้ง / ปลดผู้จัดการสาขา (หน้าจัดการสาขา) — ไม่เปลี่ยน = ไม่ทำอะไร · คืน '' หรือข้อความผิดพลาด |

## `api/product.php` — สินค้า · หมวด · สต๊อกคงเหลือ (54)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_getProductRows($reset = false, $peek = false)` | `product_db_rows` | ทุกแถวของ ao_stock_product (+ ชื่อหมวด) — array( SKU => แถว ) เรียงตามหมวด แล้วตาม SKU |
| `Stock_getBalanceRows($reset = false)` | `product_balance_rows` | ยอดคงเหลือจาก ao_stock_balance — array( product_id => array( รหัสสาขา => จำนวน ) ) |
| `Stock_shapeProduct($r)` | `product_shape` | แถวในฐานข้อมูล → รูปแบบสินค้าที่หน้าเว็บใช้ (sku name cat unit cost price reorder stock ...) · แถวที่มี __stock (ดึงทีละตัว / ค้นแบบแบ่งหน้า) ใช้ยอดนั้นเลย ไม่โหลดยอดทั้งร้าน |
| `Stock_getProducts($withInactive = false)` | `products_list` | สินค้าที่ขายอยู่ (หรือทั้งหมดรวมที่เลิกขาย) — รูปแบบเดียวกับ Stock_shapeProduct |
| `Stock_resetProductCache()` | `product_db_reset` | ล้าง cache ของสินค้า + ยอด หลังเขียน |
| `Stock_getProductUnits()` | `product_units` | หน่วยที่เคยใช้ (ไว้เติมในช่องหน่วยของฟอร์มสินค้า) |
| `Stock_readProductForm($isNew, $sku)` | `product_read_form` | ตรวจค่าจากฟอร์มสินค้า — คืน array(ค่า, ข้อผิดพลาด) · $isNew = ตรวจ SKU ด้วย |
| `Stock_saveProduct($isNew, $d)` | `product_save` | เพิ่ม / แก้สินค้า — $d จาก Stock_readProductForm · คืน product_id |
| `Stock_nextProductSku()` | — (ใหม่ ช่วงที่ 16) | SKU อัตโนมัติถัดไป P0001, P0002, … (ตัวนับใน ao_stock_doc_seq ชุด SKU · ข้ามเลขที่กรอกเองไปแล้ว) — ต้องเรียกใน Stock_dbTransaction · ตัวนับแถวเดียวใน ao_stock_doc_seq (upsert) — เพิ่มพร้อมกันไม่ได้เลขซ้ำ · ข้ามเลขที่มีคนกรอกเองแล้ว |
| `Stock_doAddProduct($user, $code, $from = 'หน้านำเข้าสินค้า')` | — (ใหม่ ช่วงที่ 16) | พนักงานเพิ่มสินค้าใหม่จากหน้านำเข้า หรือหน้าเพิ่มสินค้า (product-add.php · 15 ต.ค.) (สิทธิ์ product_add) — หมวดใหม่ + สินค้า + ประวัติใน transaction เดียว · คืน array(sku, name) หรือ array(error) · ฟอร์ม: sku_mode (auto / manual) · sku · name · cat · cat_new (ต้องมีสิทธิ์ category_add) · unit · barcode · cost · price · reorder · หมวดใหม่ + สินค้า + ประวัติใน transaction เดียว · $from = หน้าที่เพิ่ม (ลงประวัติ) |
| `Stock_setProductActive($sku, $on)` | `product_set_active` | เปิดขาย / เลิกขาย |
| `Stock_checkProductImage($field)` | — (ใหม่ 16 ต.ค.) | ตรวจรูปสินค้าที่อัปโหลดโดยยังไม่เก็บ — คืน '' (ผ่าน / ไม่ได้เลือกรูป) หรือข้อความ · อัปโหลดสำเร็จ · ไม่เกิน 2 MB · เป็น JPG / PNG / WEBP จริง (getimagesize) · ใช้ก่อนเพิ่มสินค้าใหม่ (รูปไม่ผ่าน = ยังไม่เพิ่ม) และใน product_image_store |
| `Stock_storeProductImage($sku, $field, $oldPath)` | `product_image_store` | รูปสินค้าที่อัปโหลด — ตรวจด้วย Stock_checkProductImage แล้วเก็บด้วย UpFile ของ admweb (แทนรูปเดิม) · เก็บที่ uploads/stock/products/ · ชื่อไฟล์ = SKU + เวลา |
| `Stock_removeProductImage($sku, $oldPath)` | `product_image_remove` | ลบรูปสินค้า (ไฟล์ + ค่าในฐานข้อมูล) |
| `Stock_getBaseQty($p, $code)` | `product_base_qty` | ยอดคงเหลือของสินค้าในสาขา จาก ao_stock_balance (ไม่มีแถว = 0) |
| `Stock_getProductQty($p, $branch)` | `product_qty` | ยอดคงเหลือตอนนี้ (ao_stock_balance) — $branch = รหัสสาขา หรือ 'ALL' (รวมสาขาที่เปิดใช้งาน) |
| `Stock_getProductPrice($p)` | `product_price` | ราคาขายต่อหน่วย = ราคาที่ร้านตั้ง (ao_stock_product.sell_price) |
| `Stock_getBranchStockStatus($p, $code)` | `branch_status` | สถานะของสินค้าในสาขาหนึ่ง |
| `Stock_getProductBySku($sku)` | `product_by_sku` | ค้นสินค้าจาก SKU (รวมที่เลิกขาย — เอกสารเก่ายังอ้างถึงได้) |
| `Stock_getProductStatus($p, $branch)` | `product_status` | สถานะรวมของสินค้า |
| `Stock_getWorstBranch($p)` | `worst_branch` | สาขาที่วิกฤตที่สุดของสินค้านี้ (ใช้ตอนดูทุกสาขา) |
| `Stock_getStockSummary($branch)` | `stock_summary` | สรุปสต๊อกของสาขา: จำนวนรายการ มูลค่า ใกล้หมด หมด |
| `Stock_getLowStockProducts($branch, $limit = 6)` | `low_stock_products` | สินค้าที่ถึงจุดสั่งซื้อหรือหมด เรียงจากวิกฤตที่สุด |
| `Stock_compareLowRatio($a, $b)` | `compare_low_ratio` | ตัวเรียง (usort): สัดส่วนคงเหลือต่อจุดสั่งซื้อ น้อยก่อน |
| `Stock_getCategoryNames()` | `product_cats` | ชื่อหมวดสินค้าทั้งหมด (เรียงตามลำดับในทะเบียน) — ใช้กับตัวกรองหมวดทุกหน้า |
| `Stock_getCategories($reset = false)` | `cat_registry` | ทะเบียนหมวด: ชื่อ => array(id, count จำนวนสินค้า (รวมเลิกขาย), added เพิ่มโดยผู้ใช้ไหม, by, at) |
| `Stock_getCategoryProducts($name)` | `cat_products` | สินค้าในหมวด (เฉพาะที่ขายอยู่) |
| `Stock_addCategory($name, $user, $code)` | `cat_add` | เพิ่มหมวด — คืนข้อความผิดพลาด ('' = สำเร็จ) · created_by = staff_id ผู้เพิ่ม (0 = ระบบ / นำเข้า แสดงเป็น "หมวดตั้งต้น") |
| `Stock_deleteCategory($name, $user, $code)` | `cat_delete` | ลบหมวด — ได้เฉพาะหมวดที่ไม่มีสินค้าอ้างถึง · คืนข้อความผิดพลาด ('' = สำเร็จ) |
| `Stock_searchSaleProducts($branch, $q, $cat)` | `sale_products` | ค้นหาสินค้าสำหรับหน้าขาย |
| `Stock_getPickProducts($branch, $skus)` | — (ใหม่ 14 ต.ค.) | สินค้าเฉพาะ SKU ที่ระบุ (ที่ยังขายอยู่) รูปแบบเดียวกับ sale_products — วาดการ์ดใบที่เพิ่งแตะใหม่ตอนหน้าเลือกสินค้าส่งกลับแค่ตะกร้า / ใบ · หน้าขาย / นำเข้า / เบิก / ตรวจนับส่งกลับแค่ตะกร้าหรือใบ ไม่ส่งรายการสินค้าทั้งหมดทุกครั้งที่แตะ |
| `Stock_getProductRowBySku($sku, $reset = false)` | — (ใหม่ 14 ต.ค.) | แถวสินค้าตัวเดียว + ยอดทุกสาขา (ไม่โหลดทั้งร้าน) — product_by_sku ใช้ตอนยังไม่ได้โหลดทั้งร้าน · ที่เก็บมีครบ 24 ตัวแล้วยังต้องดึงตัวใหม่ → คืน false ให้โหลดทั้งร้านแทน (ตัวที่อยู่ในที่เก็บแล้วคืนได้เสมอ) · แตะสินค้า / ปุ่มในตะกร้าใช้แค่ไม่กี่ตัว (เดิมโหลดทั้งร้านทุกครั้ง ≈ 90 ms ต่อการแตะที่ 3,000 สินค้า) |
| `Stock_searchPickProducts($branch, $q, $cat, $offset = 0, $limit = PRODUCT_PAGE_SIZE, $only = null, $skip = null)` | — (ใหม่ 14 ต.ค.) | ค้นสินค้าที่ขายอยู่ด้วย SQL แบบแบ่งหน้า — array(rows, total) · ผลเหมือน sale_products แต่ฐานข้อมูลกรอง / นับ และส่งมาแค่ช่วงที่ขอ · ค้นบาร์โค้ดได้ (ตรงทั้งรหัส · 15 ต.ค.) · $only / $skip = แท็บนับแล้ว / ยังไม่นับ · ผลเหมือน sale_products (หมวดตรงชื่อ · คำค้นอยู่ในชื่อหรือ SKU ไม่สนตัวพิมพ์ หรือตรงบาร์โค้ดทั้งรหัส · เรียงหมวดแล้ว SKU) · $limit = 0 → นับอย่างเดียว · $only / $skip = เฉพาะ / ยกเว้น SKU (แท็บหน้าตรวจนับ) |
| `Stock_getProductRowCache()` | — (ใหม่ 14 ต.ค.) | ที่เก็บแถวสินค้าที่ดึงทีละตัว / ทีละชุดต่อ request (คืนแบบ reference) — ใช้ร่วมกันระหว่าง Stock_getProductRowBySku กับ Stock_prefetchProducts |
| `Stock_prefetchProducts($skus)` | — (ใหม่ 14 ต.ค.) | ดึงสินค้าหลายตัว + ยอดทุกสาขาในคิวรีเดียว เก็บไว้ให้ product_by_sku — เรียกก่อนวนรายการ (ตะกร้า · ร่างใบ · ความเคลื่อนไหว · รายงาน) · เกิน 300 ตัวโหลดทั้งร้านแทน · โหลดทั้งร้านไว้แล้ว = ไม่ทำอะไร · เกิน 300 ตัว = โหลดทั้งร้านครั้งเดียวแทน |
| `Stock_buildTileId($sku)` | — (ใหม่ 14 ต.ค.) | id ของการ์ดสินค้าในหน้าเลือกสินค้า (g- + hash ของ SKU) ใช้สลับการ์ดใบเดียวแบบ hx-swap-oob |
| `Stock_getListRange()` | — (ใหม่ 14 ต.ค.) | ช่วงแถวที่จะวาด array(เริ่ม, จบ, เป็นคำขอแสดงเพิ่มไหม) จาก ?from (htmx กด “แสดงเพิ่ม”) / ?n (เปิดหน้า) — ทีละ PRODUCT_PAGE_SIZE (60) |
| `Stock_renderMoreButton($end, $total, $url, $cols = 0, $tag = 'div')` | — (ใหม่ 14 ต.ค.) | ปุ่ม “แสดงเพิ่ม” ท้ายรายการสินค้า (div ในกริดการ์ด · li ใน ul · tr ในตาราง) — แสดงครบแล้วไม่ echo อะไร · $url = หน้าเดิมพร้อมตัวกรอง (ไม่มี n / from) · $cols > 0 → <tr> colspan · $cols = 0 → แท็ก $tag (div / li) · ไม่มี JavaScript = ลิงก์เปิดหน้าเดิม n + 60 |
| `Stock_getProductImageUrl($p)` | `product_img` | URL รูปสินค้า — รูปที่อัปโหลด หรือรูปตาม SKU ในธีม · ไม่มี = ว่าง |
| `Stock_getCategoryStyle($cat)` | `cat_style` | ไอคอนและโทนสีประจำหมวด ใช้ตอนที่ยังไม่มีรูปจริง |
| `Stock_renderThumb($p, $extra = '')` | `thumb_html` | กล่องรูปสินค้า — ใช้ได้ทั้งในตารางและบนการ์ดหน้าขาย · มีรูป = คลาส thumb--img (พื้นขาว เห็นทั้งรูป) · ไม่มีรูป = ไอคอน + โทนสีของหมวด |
| `Stock_getStockStatusTabs()` | `stock_status_tabs` | แท็บกรองสถานะสต๊อก (ทั้งหมด / ใกล้หมด / หมด …) |
| `Stock_getStockSorts()` | `stock_sorts` | ตัวเลือกการเรียงในหน้าสินค้าในสต๊อก |
| `Stock_getStockLabel($st)` | `stock_label` | ป้ายสถานะสต๊อกภาษาไทย |
| `Stock_getStockTone($st)` | `stock_tone` | โทนสีของสถานะสต๊อก |
| `Stock_buildStockQuery($q, $cat, $st, $sort)` | `stock_qs` | ประกอบ query string ของตัวกรอง โดยข้ามค่าที่ว่าง |
| `Stock_getStockRows($code, $q, $cat, $st, $sort)` | `stock_rows` | รายการสินค้าของสาขาหนึ่ง พร้อมสถานะและระดับสต๊อก |
| `Stock_getStockSorter($sort)` | `stock_sorter` | ชื่อตัวเรียงตามตัวเลือกการเรียง |
| `Stock_compareByName($a, $b)` | `stock_cmp_name` | ตัวเรียง: ชื่อสินค้า |
| `Stock_compareByQtyDesc($a, $b)` | `stock_cmp_qty_desc` | ตัวเรียง: คงเหลือมากไปน้อย |
| `Stock_compareByQtyAsc($a, $b)` | `stock_cmp_qty_asc` | ตัวเรียง: คงเหลือน้อยไปมาก |
| `Stock_compareByCategory($a, $b)` | `stock_cmp_cat` | ตัวเรียง: หมวด แล้วชื่อ |
| `Stock_compareByUrgency($a, $b)` | `stock_cmp_urgent` | ของที่ใกล้หมดที่สุดขึ้นก่อน แล้วค่อยเรียงตามชื่อ |
| `Stock_sumStockView($rows)` | `stock_view_sum` | สรุปเฉพาะรายการที่แสดงอยู่ตอนนี้ — การ์ดด้านบนใช้ชุดนี้ ตัวเลขจะได้ตรงกับตาราง |
| `Stock_describeStockFilter($q, $cat, $st)` | `stock_filter_words` | มีตัวกรองอะไรเปิดอยู่บ้าง — ไว้บอกผู้ใช้ว่าตัวเลขที่เห็นมาจากอะไร |
| `Stock_sumBranchStock($code)` | `stock_branch_sum` | สรุปทั้งสาขา (ไม่สนตัวกรอง) ไว้โชว์เป็นการ์ดด้านบน |
| `Stock_getProductFlowStats($codes, $days = 30)` | `product_flow_stats` | ตัวเลขประกอบหน้าสินค้าในสต๊อกของผู้ดูแล (adm-products.php) ย้อนหลัง $days วัน รวมวันนี้ · คืน array('sold' => SKU => สาขา => จำนวน (ไม่นับบิลยกเลิก), 'recv' => SKU => สาขา => วันรับเข้าล่าสุด Ymd (ไม่นับใบยกเลิก)) |

## `api/ledger.php` — สมุดสต๊อก · เอกสารคลัง · ความเคลื่อนไหว · แก้ย้อนหลัง (33)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_getDocKinds()` | `stock_doc_kinds` | ชนิดเอกสาร: ตาราง · คอลัมน์ id · ชนิดใน stock_move · ชนิดใน stock_log (บันทึก / ยกเลิก) · RC / IS / AD ใช้ stock_docs_query / stock_doc_void_core · SA (บิลขาย) ใช้เฉพาะ stock_draft_edit_of · บิลขาย (SA) / ใบรับคืน (RT) ใช้ ledger เดียวกัน (move ชนิด sale / sale_void / return) |
| `Stock_getUserId($user)` | `stock_uid` | staff_id ของผู้ใช้ที่ล็อกอิน (session เก่าที่ยังไม่มี id → หาจาก username) · ไม่พบ = 0 |
| `Stock_cutText($s, $len)` | `stock_cut` | ตัดข้อความให้ไม่เกินความยาวคอลัมน์ (นับเป็นตัวอักษร ไม่ใช่ byte) |
| `Stock_nextSequence($branchId, $series, $period)` | `stock_seq_next` | เลขรันถัดไปของชุด (สาขา + ชุด + งวด) — ต้องเรียกใน Stock_dbTransaction · INSERT … ON DUPLICATE KEY UPDATE ล็อกแถวตัวนับจน COMMIT — บันทึกพร้อมกันต้องรอ เลขไม่ซ้ำ ไม่ข้าม |
| `Stock_nextDocNo($branchId, $series)` | `stock_doc_next_no` | ออกเลขที่เอกสาร {ชุด}-{ปปดดวว}-{NNNN} รันต่อสาขาต่อวัน — ต้องเรียกใน Stock_dbTransaction |
| `Stock_lockQty($branchId, $productIds)` | `stock_lock_qty` | ล็อกยอดคงเหลือของสินค้าในสาขา (FOR UPDATE) — ต้องเรียกใน Stock_dbTransaction · คืน array( product_id => ยอดในฐานข้อมูล ) · สินค้าที่ยังไม่มีแถวใน stock_balance สร้างแถว 0 ก่อน · เรียง product_id เสมอกัน deadlock · เวลาในเอกสารใช้นาฬิกา PHP (Asia/Bangkok) ไม่ใช่ CURRENT_TIMESTAMP ของ MySQL |
| `Stock_addMove($branchId, $productId, $type, $qty, $qtyAfter, $unitCost, $refType, $refId, $docNo, $note, $uid, $at)` | `stock_move_add` | เขียนความเคลื่อนไหว 1 แถว + ตั้งยอดใหม่ใน stock_balance — ต้องเรียกใน Stock_dbTransaction หลัง Stock_lockQty() |
| `Stock_getDraftEditOf($kind, $branchId)` | `stock_draft_edit_of` | ใบที่ "ยกเลิกเพื่อแก้ไข" ซึ่งร่างตอนนี้ทำแทน — ใช้เป็น edit_of_id ของใบใหม่ (ต้องเรียกใน Stock_dbTransaction) · id เก็บใน $_SESSION['draft_edit_of'][ชนิด] ตอนดึงรายการกลับเข้าร่าง · ใบเดิมมีใบแทนแล้วไม่ใช้ซ้ำ |
| `Stock_checkScreenQty($draft, $raw, $keepZero = false)` | — (ใหม่ 20 ต.ค.) | ก่อนบันทึกใบนำเข้า / ตัดออก / ตรวจนับ: เทียบจำนวนบนจอ (ช่องซ่อน screen_qty — JSON SKU => ตัวเลขในช่อง ชุดเดียวกับที่กล่องยืนยันแสดง) กับใบร่างใน session ทุกแถว · $keepZero = true: 0 ยังอยู่ในใบ (ตรวจนับ) · false: 0 = ไม่อยู่ในใบ · คืน '' = ตรงทุกแถว หรือข้อความบอกแถวที่ไม่ตรง (สูงสุด 5 แถว) / อ่านช่องไม่ได้ (หน้าเก่า) — ผู้เรียกไม่บันทึก ร่างคงเดิม |
| `Stock_queryDocs($kind, $where, $params)` | `stock_docs_query` | อ่านเอกสารคลังชนิดหนึ่ง — $where ต่อท้าย WHERE (alias d = หัวใบ · b = สาขา) · คืน array ของ Stock_shapeDoc() เรียงเก่า → ใหม่ |
| `Stock_shapeDoc($kind, $h, $items)` | `stock_doc_shape` | แถวในฐานข้อมูล → รูปเอกสารที่หน้าเว็บใช้ (no time by lines items qty value void_* ...) เหมือนตอนเก็บใน session |
| `Stock_getDocsRange($codes, $from, $to, $kinds = array('RC', 'IS', 'AD'))` | `stock_docs_range` | เอกสารคลังของสาขาใน $codes ช่วงวันที่ $from–$to (timestamp) รวมใบที่ยกเลิก — เรียงตามเวลา |
| `Stock_getDocByNo($code, $no, $day = '', $kind = '')` | `stock_doc_by_no` | เอกสารคลังใบเดียวจากเลขที่ (ชนิดดูจากตัวหน้า RC / IS / AD) · ชนิดดูจากตัวหน้า RC / IS / AD |
| `Stock_voidDocCore($doc, $user, $reason, $redo, $log)` | `stock_doc_void_core` | ยกเลิกเอกสารคลัง: status void + stock_move กลับรายการ + ประวัติ — ทั้งหมดใน transaction เดียว · แก้ไข = ยกเลิกแบบ void_mode 'edit' แล้วใบใหม่ใส่ edit_of_id · $log = array(หัวข้อ, รายละเอียด, จำนวน) · คืน true / null (ยกเลิกไปก่อนแล้ว) / array('short' => รายการที่ของไม่พอให้ถอย) |
| `Stock_getMoveTypes()` | `move_types` | ชนิดความเคลื่อนไหวของสต๊อก — ป้าย สี ไอคอน กลุ่ม |
| `Stock_getMoveType($k)` | `move_type_of` | ข้อมูลของชนิดความเคลื่อนไหวหนึ่ง |
| `Stock_getMoveGroup($type)` | `move_group` | กลุ่มสรุปบนหัวหน้า: รับเข้า / ขาย / ตัดออก / ตรวจนับ |
| `Stock_getMoveTypeKey($t)` | `move_type_key` | ชนิดใน stock_move → คีย์ของ Stock_getMoveTypes() |
| `Stock_getMoveDbRows($codes, $pid, $fromTs)` | `move_db_rows` | ความเคลื่อนไหวใน ao_stock_move ตั้งแต่ $fromTs ของสาขาใน $codes (เฉพาะสินค้า $pid ถ้าระบุ) · คืน สาขา => SKU => แถว (ts seq type delta doc by note) |
| `Stock_getMovesOfBranches($codes, $pid = 0)` | `move_rows_multi` | ความเคลื่อนไหวของสินค้าในสาขาใน $codes (ทุกสินค้า หรือเฉพาะ $pid) ย้อนหลัง MOVE_LOOKBACK_DAYS วัน + วันนี้ · bal คำนวณย้อนจากยอดปัจจุบัน (product_qty) · สินค้าที่เลิกขายไม่แสดง |
| `Stock_getMovesOfProduct($code, $p)` | `move_rows` | ความเคลื่อนไหวทั้งหมดของสินค้าหนึ่งตัวในสาขาหนึ่ง เรียงเก่า → ใหม่ พร้อม 'bal' = คงเหลือหลังรายการนั้น |
| `Stock_getMovePeriods()` | `move_periods` | ช่วงเวลาที่เลือกดูได้ในหน้าประวัติเคลื่อนไหว |
| `Stock_buildMoveView($rows, $period, $now)` | `move_view` | ตัดตามช่วงเวลา แล้วสรุปยอดต้นงวด/ปลายงวด และยอดรวมของแต่ละกลุ่ม |
| `Stock_formatMoveTime($ts)` | `move_when` | เวลาแบบอ่านง่าย: วันนี้ 10:32 / เมื่อวาน 18:05 / จ. 3 ต.ค. 09:10 |
| `Stock_buildMoveQuery($q, $cat, $sku, $period, $extra = array())` | `move_qs` | ประกอบ query string ของหน้าประวัติเคลื่อนไหว |
| `Stock_getMovementFeed($codes, $period, $limit = 100)` | `movement_feed` | รายการเคลื่อนไหวล่าสุดของทุกสินค้า (ใช้ตอนยังไม่ได้เลือกสินค้า) · ใหม่สุดก่อน ไม่เกิน $limit แถว · แต่ละแถวมี p (สินค้า) และ branch เพิ่มจาก move_rows |
| `Stock_getPastDocTypes()` | `past_types` | ชนิดเอกสารที่แก้ย้อนหลังได้ |
| `Stock_getBackdateLimit($user, $code)` | `backdate_limit` | ผู้ใช้คนนี้ย้อนดู/แก้ได้กี่วันในสาขานี้ (0 = ไม่มีสิทธิ์) |
| `Stock_getPastDocs($code, $ts)` | `past_docs` | เอกสารคลังของสาขาในวันหนึ่ง (รวมใบที่ยกเลิก) เรียงตามเวลา — ใช้กับประวัติย้อนหลัง |
| `Stock_getPastDoc($code, $no)` | `past_doc` | หาเอกสารของวันก่อนจากเลขที่ (XX-YYMMDD-NNNN) — ใบของวันนี้แก้ / ยกเลิกจากหน้างานคลังแทน |
| `Stock_getPastDocAgeDays($doc)` | `past_age` | เอกสารนี้ผ่านมากี่วันแล้ว (วันนี้ = 0) |
| `Stock_canEditPastDoc($user, $doc)` | `past_can_edit` | แก้/ยกเลิกใบนี้ได้ไหม — คืน array(ok, msg) |
| `Stock_voidPastDoc($code, $no, $user, $reason, $redo)` | `past_doc_void` | ยกเลิก (หรือยกเลิกเพื่อแก้ไข) เอกสารย้อนหลัง — สต๊อกถูกปรับวันนี้ และลงประวัติของวันนี้ · พนักงานต้องมีสิทธิ์ backdate และย้อนไม่เกิน backdate_days ของสาขา · ใบของคนอื่นต้องมี void_others · ใบเดิมขึ้นว่ายกเลิกย้อนหลังพร้อมชื่อคนยกเลิก · stock_move ลงวันนี้อ้าง ref_id ใบเดิม · แก้ไข = ดึงรายการเป็นใบใหม่ของวันนี้ (edit_of_id = ใบเดิม) |

## `api/receive.php` — นำเข้าสินค้า (13)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_getReceiveDraft()` | `rdraft_all` | ใบรับเข้าที่กำลังทำ (ร่างใน session) |
| `Stock_countReceiveDraft()` | `rdraft_count` | จำนวนชิ้นรวมในร่าง |
| `Stock_addToReceiveDraft($sku, $step = 1)` | `rdraft_add` | เพิ่มจำนวนในร่าง |
| `Stock_setReceiveDraftQty($sku, $qty)` | `rdraft_set` | ตั้งจำนวนของรายการในร่าง |
| `Stock_removeFromReceiveDraft($sku)` | `rdraft_remove` | เอารายการออกจากร่าง |
| `Stock_clearReceiveDraft()` | `rdraft_clear` | ล้างร่างทั้งใบ |
| `Stock_getReceiveDraftLines($code)` | `rdraft_lines` | แปลงร่างเป็นรายการพร้อมยอดคงเหลือก่อน/หลัง |
| `Stock_saveReceive($code, $user, $ref, $note, $lines)` | `receive_save` | บันทึกการรับเข้า: บวกสต๊อก + ลง ledger + ประวัติ ในทรานแซกชันเดียว · ผู้รับเข้าและสาขามาจากบัญชีที่เข้าระบบ · คืนรูปเดียวกับ receive_by_no |
| `Stock_voidReceive($code, $no, $user, $reason, $reopen = false)` | `receive_void` | ยกเลิกใบรับเข้าของวันนี้ — ถอนยอดที่เคยบวกไว้ออกจากสต๊อก (ใบของวันก่อนใช้ Stock_voidPastDoc) · ของถูกขาย / เบิกออกไปบางส่วนแล้ว = ถอนแล้วติดลบ ยกเลิกไม่ได้ ให้ตรวจนับแทน · ใบวันก่อนใช้ past_doc_void |
| `Stock_loadReceiveDraft($doc)` | `rdraft_from_receive` | ดึงรายการทั้งใบกลับเข้าร่าง เพื่อแก้แล้วรับเข้าใหม่ (ใบใหม่จะชี้ edit_of_id กลับมาที่ใบนี้) |
| `Stock_getReceiveByNo($code, $no)` | `receive_by_no` | ใบรับเข้าของวันนี้จากเลขที่ — แก้ / ยกเลิกจากหน้ารับเข้าได้เฉพาะใบของวันนี้ |
| `Stock_getReceivesRange($codes, $from, $to)` | `receive_docs_range` | ใบรับเข้าของสาขาใน $codes ช่วง $from–$to (รวมที่ยกเลิก) — มี date / value / void ครบทุกใบ |
| `Stock_findReceive($code, $no)` | `receive_doc_find` | หาใบรับเข้าจากเลขที่ RC-ปปดดวว-NNNN (วันไหนก็ได้) |

## `api/issue.php` — เบิก / ตัดออก (20)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_getIssueReasons()` | `issue_reasons` | เหตุผลการเบิก / ตัดออก |
| `Stock_getIssueReasonLabel($key)` | `issue_reason_label` | ชื่อเหตุผล |
| `Stock_issueReasonNeedsNote($key)` | `issue_reason_needs_note` | เหตุผลนี้ต้องเขียนหมายเหตุไหม |
| `Stock_getIssueDraft()` | `idraft_all` | ใบเบิกที่กำลังทำ (ร่างใน session) · $_SESSION['issue_draft'][SKU] = จำนวน · issue_meta = reason / ref / note · ต่างจากรับเข้าตรงที่ตัดเกินของที่มีไม่ได้ |
| `Stock_countIssueDraft()` | `idraft_count` | จำนวนชิ้นรวมในร่าง |
| `Stock_issueDraftFlash($msg = null)` | `idraft_flash` | จำข้อความเตือนไว้แสดงครั้งถัดไป (เช่น ปรับจำนวนลงให้เพราะของไม่พอ) |
| `Stock_setIssueDraftQty($code, $sku, $qty)` | `idraft_set` | ตั้งจำนวน โดยไม่ให้เกินยอดคงเหลือ — คืนค่าจำนวนที่ตั้งได้จริง |
| `Stock_addToIssueDraft($code, $sku, $step = 1)` | `idraft_add` | เพิ่มจำนวนในร่าง ไม่ให้เกินยอดคงเหลือ |
| `Stock_removeFromIssueDraft($sku)` | `idraft_remove` | เอารายการออกจากร่าง |
| `Stock_clearIssueDraft()` | `idraft_clear` | ล้างร่างทั้งใบ |
| `Stock_getIssueDraftMeta()` | `idraft_meta` | หัวใบที่จำไว้ (ตอนดึงใบเก่ามาแก้ จะได้ไม่ต้องเลือกเหตุผลใหม่) |
| `Stock_getIssueDraftLines($code)` | `idraft_lines` | แปลงร่างเป็นรายการ พร้อมยอดก่อน/หลัง และมูลค่าต้นทุนที่ตัดออก |
| `Stock_sumIssueDraftCost($lines)` | `idraft_cost` | มูลค่าต้นทุนรวมของรายการในร่าง |
| `Stock_getIssuesToday($code)` | `issues_today` | ใบเบิก / ตัดออกของสาขาวันนี้ (รวมที่ยกเลิก) เรียงตามเวลา |
| `Stock_getIssueByNo($code, $no)` | `issue_by_no` | ใบเบิก / ตัดออกของวันนี้จากเลขที่ — แก้ / ยกเลิกจากหน้าเบิกได้เฉพาะใบของวันนี้ |
| `Stock_checkIssueShortage($lines)` | `issue_shortage` | ตรวจก่อนบันทึกว่าของยังพอ (ระหว่างทำใบ อาจมีคนขายตัวเดียวกันออกไปแล้ว) |
| `Stock_saveIssue($code, $user, $reason, $ref, $note, $lines)` | `issue_save` | บันทึกการตัดออก: ลดสต๊อก + ลง ledger + ประวัติ ในทรานแซกชันเดียว |
| `Stock_voidIssue($code, $no, $user, $reason, $reopen = false)` | `issue_void` | ยกเลิกใบตัดออกของวันนี้ — คืนยอดกลับเข้าสต๊อก (ทำได้เสมอ เพราะเป็นการบวกกลับ) · ทำได้เสมอเพราะเป็นการบวกกลับ |
| `Stock_loadIssueDraft($doc)` | `idraft_from_issue` | ดึงรายการทั้งใบกลับเข้าร่าง พร้อมหัวใบเดิม (ใบใหม่จะชี้ edit_of_id กลับมาที่ใบนี้) |
| `Stock_getIssueRowsRange($codes, $from, $to)` | `issue_rows_range` | หนึ่งแถวต่อสินค้าหนึ่งรายการในใบเบิก ของสาขาใน $codes ช่วง $from–$to · ราคาทุน / มูลค่าตาม snapshot ในใบ · ราคาขาย = ราคาปัจจุบัน |

## `api/count.php` — ตรวจนับสต๊อก (25)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_getStocktakeReasons()` | `adj_reasons` | สาเหตุที่ยอดนับไม่ตรง |
| `Stock_getStocktakeReasonLabel($key)` | `adj_reason_label` | ชื่อสาเหตุ |
| `Stock_stocktakeReasonNeedsNote($key)` | `adj_reason_needs_note` | สาเหตุนี้ต้องเขียนหมายเหตุไหม |
| `Stock_getStocktakeDraft()` | `adraft_all` | ใบตรวจนับที่กำลังทำ (ร่างใน session) · $_SESSION[adj_draft][SKU] = จำนวนที่นับได้ · adj_snap = ยอดตอนเริ่มนับ · adj_meta = หัวใบที่ค้าง |
| `Stock_countStocktakeDraft()` | `adraft_size` | จำนวนรายการในร่าง |
| `Stock_pickToStocktakeDraft($code, $sku)` | `adraft_pick` | แตะสินค้าเข้าใบ: เริ่มที่ยอดในระบบ (ส่วนต่าง 0) แล้วค่อยแก้เป็นยอดที่นับได้ · ตอนบันทึกถ้ายอดเปลี่ยนจาก snap = มีรายการแทรก ต้องนับใหม่ |
| `Stock_getStocktakeMoved($code)` | `adj_moved` | รายการที่ยอดในระบบเปลี่ยนไประหว่างนับ (มีการขาย/รับเข้า/ตัดออกแทรก) |
| `Stock_setCountDraft($code, $sku, $raw, $seen = null)` | — (ใหม่ 15 ต.ค.) | ใบนับแบบรายการ: ยอดที่นับได้ของแถวหนึ่ง (ว่าง = เอาออกจากใบ) · $seen = ยอดในระบบที่แถวโชว์ตอนกรอก ใช้เช็กการขายแทรกตอนบันทึก · ไม่ส่ง = คงยอดที่จดไว้ตอนแตะการ์ด · $seen ไม่ส่งมา = คงยอดที่จดตอนแตะการ์ด (ยังไม่มี = ยอดตอนนี้) |
| `Stock_setStocktakeDraftQty($sku, $qty)` | `adraft_set` | ตั้งยอดที่นับได้ของรายการในร่าง |
| `Stock_stepStocktakeDraftQty($sku, $step)` | `adraft_step` | เพิ่ม / ลดยอดที่นับได้ทีละขั้น |
| `Stock_removeFromStocktakeDraft($sku)` | `adraft_remove` | เอารายการออกจากร่าง |
| `Stock_clearStocktakeDraft()` | `adraft_clear` | ล้างร่างทั้งใบ |
| `Stock_getStocktakeDraftMeta()` | `adraft_meta` | หัวใบที่จำไว้ (สาเหตุ / หมายเหตุ ตอนดึงใบเก่ามาแก้) |
| `Stock_getStocktakeDraftLines($code)` | `adraft_lines` | แปลงร่างเป็นรายการ: ยอดในระบบตอนนี้ · นับได้ · ส่วนต่าง · มูลค่าส่วนต่าง |
| `Stock_sumStocktake($lines)` | `adj_sum` | สรุปส่วนต่างของทั้งใบ |
| `Stock_getStocktakeByNo($code, $no)` | `adj_by_no` | ใบตรวจนับของวันนี้จากเลขที่ — แก้ / ยกเลิกจากหน้าตรวจนับได้เฉพาะใบของวันนี้ |
| `Stock_saveStocktake($code, $user, $reason, $note, $lines)` | `adj_save` | บันทึกผลการนับ: ปรับสต๊อกเท่ากับส่วนต่าง + ledger + ประวัติ ในทรานแซกชันเดียว · ปรับสต๊อกเท่าส่วนต่าง + ledger + ประวัติ · $reason ว่างได้ถ้าตรงหมด · ทุกรายการลง count_item และ stock_move (ตรง = qty 0) · ยอดที่ล็อกได้ไม่ตรงกับตอนแสดงหน้า = ไม่บันทึก ให้นับตัวนั้นใหม่ |
| `Stock_voidStocktake($code, $no, $user, $reason, $reopen = false)` | `adj_void` | ยกเลิกใบตรวจนับของวันนี้ — ถอยส่วนต่างที่เคยปรับไว้กลับ · ใบเดิมบวกของแล้วของถูกขายไป → ถอยแล้วติดลบ ยกเลิกไม่ได้ ให้นับใหม่แทน · คืนใบ / array('error' => 'sold', 'items') / null ถ้าไม่พบหรือยกเลิกแล้ว |
| `Stock_loadStocktakeDraft($doc)` | `adraft_from_adj` | ดึงรายการทั้งใบกลับเข้าร่าง (ใบใหม่จะชี้ edit_of_id กลับมาที่ใบนี้) — ยอดตั้งต้นของการนับ = ยอดหลังถอยใบเดิมแล้ว · ยอดตั้งต้นของการนับ = ยอดหลังถอยใบเดิมแล้ว |
| `Stock_getStocktakeRound($code, $now = null)` | `count_round` | รอบปัจจุบันของสาขา: array(start, end, due) เป็น timestamp เที่ยงคืน |
| `Stock_getStocktakeStatus($code)` | `count_status_all` | สถานะการนับของทุกสินค้าในรอบนี้: array( SKU => array(at, diff, by, no) ) · "นับแล้ว" = มี count_item ในใบที่ไม่ยกเลิกตั้งแต่วันเริ่มรอบปัจจุบัน — ใบหลังสุดชนะ |
| `Stock_buildStocktakeQuery($q, $cat, $tab)` | `adj_qs` | query string ของหน้าตรวจนับ (แท็บ "ทั้งหมด" เป็นค่าเริ่มต้น ไม่ต้องใส่) |
| `Stock_getStocktakeTabs()` | `count_tabs` | แท็บของหน้าตรวจนับ (ทั้งหมด / ยังไม่นับ / นับแล้ว) |
| `Stock_formatStocktakeNote($st)` | `count_note` | ข้อความสั้นบนการ์ด: "นับ 12 ก.ย. · ตรง" / "นับวันนี้ 10:32 · ขาด 2" |
| `Stock_renderDiffChip($d)` | `diff_chip` | ป้ายส่วนต่าง: +3 / −2 / ตรง |

## `api/sale.php` — ขาย · ตะกร้า · บิล (17)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_getCart()` | `cart_all` | ตะกร้าของ session นี้ — array( SKU => จำนวน ) · $_SESSION[cart] = SKU => จำนวน (ของผู้ใช้คนนั้น ยังไม่ใช่ข้อมูลจริง) |
| `Stock_countCart()` | `cart_count` | จำนวนชิ้นรวมในตะกร้า |
| `Stock_addToCart($sku, $branch, $step = 1)` | `cart_add` | เพิ่มจำนวนในตะกร้า ไม่ให้เกินยอดคงเหลือของสาขา |
| `Stock_setCartQty($sku, $branch, $qty)` | `cart_set` | กำหนดจำนวนตรง ๆ (พนักงานพิมพ์แก้เองเมื่อกดผิด) |
| `Stock_removeFromCart($sku)` | `cart_remove` | เอาสินค้าออกจากตะกร้า |
| `Stock_clearCart()` | `cart_clear` | ล้างตะกร้า |
| `Stock_getCartLines()` | `cart_lines` | แปลงตะกร้าเป็นรายการพร้อมราคา (ราคาขายปัจจุบันของสินค้า) |
| `Stock_getCartTotal()` | `cart_total` | ยอดรวมของตะกร้า |
| `Stock_queryBills($where, $params, $order = 's.add_date, s.sale_id', $limit = 0)` | `sale_bills_query` | อ่านบิล — $where ต่อท้าย WHERE (alias s = บิล · b = สาขา) · คืน array ของบิลรูปเดียวกับที่หน้าเว็บใช้ |
| `Stock_shapeBill($h, $items)` | `sale_bill_shape` | แถวในฐานข้อมูล → รูปบิลที่หน้าเว็บใช้ (no vat time by method lines total received change void_* ...) |
| `Stock_getBillByNo($code, $no)` | `bill_by_no` | บิลของวันนี้จากเลขที่ — แก้ / ยกเลิกได้เฉพาะบิลของวันนี้ |
| `Stock_canSeeBill($user, $bill)` | — (ใหม่ 16 ต.ค.) | ดู / พิมพ์บิลนี้ได้ไหม — ผู้ดูแล · ฝ่ายบัญชี · สิทธิ์ hist_sale (ประวัติขาย) = ทุกบิล · คนอื่น = เฉพาะบิลที่ตัวเองขาย (by_user) · สาขาตรวจที่หน้าที่เรียก · ใช้ใน bill-print.php และ sale.php?done= / ?voided= |
| `Stock_getMyBillsToday($code, $user, $from = '', $to = '', $limit = 200)` | — (ใหม่ 16 ต.ค.) | บิลที่ผู้ใช้คนนี้ขายวันนี้ที่สาขานี้ ใหม่สุดก่อน (รวมบิลที่ยกเลิก) · $from / $to = 'HH:MM' ของเวลาขาย (ไม่ถูกรูปแบบ = ทั้งวัน) · $limit = 0 → คืนจำนวนอย่างเดียว · รายการ "บิลของฉันวันนี้" ในหน้าขาย |
| `Stock_saveBill($code, $user, $method, $received, $vat = false, $net = null)` | `bill_save` | บันทึกบิลจากตะกร้า: ล็อกยอด → เช็กร้านเปิด + ของพอ → ออกเลขบิล → บิล + รายการ + stock_move + ประวัติ (ทรานแซกชันเดียว) · ล็อกยอด → เช็กร้านเปิด + ของพอ → ออกเลขบิล (ชุด vat / novat งวด YYYYMM) → บิล + รายการ + move + ประวัติ · ขายได้เฉพาะตอนร้านเปิด |
| `Stock_voidBill($code, $no, $user, $reason, $reopen = false)` | `bill_void` | ยกเลิกบิลของวันนี้ — คืนสต๊อกด้วย stock_move sale_void แล้วทำเครื่องหมายว่ายกเลิก (บิลไม่ถูกลบ) · ร้านต้องยังเปิด (ปิดแล้วเงินในลิ้นชักสรุปแล้ว) และบิลยังไม่มีรับคืน · วันก่อนใช้รับคืนสินค้า |
| `Stock_loadCartFromBill($bill, $branch)` | `cart_from_bill` | ดึงรายการทั้งบิลกลับเข้าตะกร้า — ใช้คู่กับการยกเลิกเพื่อแก้ไข (บิลใหม่ชี้ edit_of_id กลับมาที่บิลนี้) · แก้เฉพาะแถวที่ผิดแล้วรับเงินใหม่ · ของที่คืนเข้าสต๊อกจากการยกเลิกทำให้ใส่กลับได้ครบเสมอ |
| `Stock_billHasAnyReturn($no)` | `bill_returned_any` | บิลนี้มีการรับคืนสินค้าไปแล้วหรือยัง — ถ้ามีแล้ว ห้ามยกเลิก/แก้ทั้งบิล (ของจะถูกคืนซ้ำ) · เลขที่บิลไม่ซ้ำข้ามสาขา (รหัสนำหน้าห้ามซ้ำ — acct_prefix_error) จึงหาจากเลขที่อย่างเดียว |
| `Stock_getSaleSummary($code)` | `sale_summary` | สรุปยอดขายวันนี้ของสาขา (บิลที่ยกเลิกนับแยก ไม่นับเป็นยอดขาย) |
| `Stock_renderSaleFlash($err, $done, $voided, $oob)` | `sale_flash` | ข้อความแจ้งผลบนหน้าขาย (ส่งกลับแบบ out-of-band ให้ htmx ด้วย) |

## `api/store.php` — เปิด–ปิดร้าน · เงินสดในลิ้นชัก (20)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_getDefaultFloat($code)` | `branch_default_float` | เงินทอนมาตรฐานของสาขา (ฟิลด์ default_float) |
| `Stock_getStoreDayRow($code, $date = null, $reset = false)` | `store_day_row` | แถวเปิด–ปิดร้านของสาขาในวันที่ $date (Y-m-d · ค่าเริ่มต้นวันนี้) + ชื่อคนเปิด / ปิด หรือ null |
| `Stock_getStoreDayResult($code, $ts)` | `store_day_result` | ผลเปิด–ปิดร้านของสาขาในวันหนึ่ง (อีเมลสรุปรายวัน / ตัวอย่างข้อความแจ้งเตือน) — ไม่มีการเปิดร้านวันนั้น = null · ใช้ในอีเมลรายวัน / ตัวอย่างข้อความแจ้งเตือน · คืน opened_at, opened_by, float, closed, closed_at, closed_by, expect, counted, diff, keep, handover, note (ยังไม่ปิด = closed false) |
| `Stock_resetStoreCache()` | `store_db_reset` | ล้าง cache แถวเปิด–ปิดร้านหลังเขียน |
| `Stock_getStoreCarry($code)` | `store_carry` | เงินทอนยกมาสำหรับเปิดร้านวันนี้ — array(amount, by, time, unclosed) · = keep_cash ของวันล่าสุดก่อนวันนี้ (ปิดแล้ว) · วันนั้นยังไม่ปิด → เงินทอนมาตรฐาน + unclosed = ปปปปดดวว · ยังไม่เคยเปิด → เงินทอนมาตรฐาน |
| `Stock_getUnclosedDays($code)` | `store_unclosed_days` | วันก่อนวันนี้ที่ยังไม่ได้ปิดร้าน (ลืมปิด) — array ของ ปปปปดดวว ใหม่สุดก่อน |
| `Stock_getDailyCash($code)` | `store_daily_cash` | เงินที่เติม / หยิบออกจากลิ้นชักวันนี้ — array(topup, withdraw) |
| `Stock_getCashMoves($code)` | `store_cash_list` | รายการเติม / หยิบเงินของวันนี้ (เก่า → ใหม่) — array(time, dir in\|out, amount, reason, by) |
| `Stock_getStoreState($code)` | `store_state` | สถานะร้านของสาขาวันนี้ — null = ยังไม่เปิด · คืน id, branch, opened_at, opened_by, opened_user, carry, topup, float, counted, reason, closed_at (ว่าง = เปิดอยู่), closed_by, cash_counted, keep, note, expected, reopen_count, last_diff |
| `Stock_isStoreOpen($code)` | `store_is_open` | ร้านของสาขาเปิดอยู่ตอนนี้ไหม |
| `Stock_isStoreClosed($code)` | `store_is_closed` | ร้านของสาขาปิดไปแล้ววันนี้ไหม |
| `Stock_openStore($code, $user, $topup, $counted, $reason)` | `store_open` | บันทึกการเปิดร้าน — คืนสถานะร้าน (อีกเครื่องเปิดไปก่อนแล้ว = สถานะของเครื่องนั้น ไม่ลงประวัติซ้ำ) · ร้านเปิดวันละครั้งต่อสาขา (UNIQUE สาขา + วัน) · อีกเครื่องเปิดไปก่อน = คืนสถานะของเครื่องนั้น ไม่ลงประวัติซ้ำ |
| `Stock_getExpectedCash($code)` | `store_expected_cash` | ยอดเงินที่ควรมีในลิ้นชักตอนนี้ · = เงินทอนเริ่มวัน + เติม + ขายเงินสด − หยิบออก − คืนเงินลูกค้า |
| `Stock_closeStore($code, $user, $counted, $keep, $note)` | `store_close` | บันทึกการปิดร้าน — เงินที่แยกไว้พรุ่งนี้ไม่เกินเงินที่นับได้ · นำส่ง = นับได้ − แยกไว้ · เงินที่แยกไว้พรุ่งนี้ไม่เกินที่นับได้ · นำส่ง = นับได้ − แยกไว้ · เก็บยอดที่ควรมี + นับได้ + แยกไว้ + นำส่งลงแถวของวัน |
| `Stock_getDayCash($dayId)` | — (ใหม่ ช่วงที่ 17) | เงินในลิ้นชักของวันหนึ่ง (เงินทอนเริ่มวัน / เติม / ขายเงินสด / หยิบออก / คืนเงิน / ควรมี) — ใช้ได้ทั้งวันนี้และวันก่อน · expected = เงินทอนเริ่มวัน + เติม + ขายเงินสด − หยิบออก − คืนเงินลูกค้า (ทุกรายการผูก day_id ของวันนั้น) |
| `Stock_closePastDay($code, $ymd, $user, $counted, $keep, $note)` | — (ใหม่ ช่วงที่ 17) | ปิดร้านย้อนหลังของวันที่ลืมปิด — ต้องมีหมายเหตุ · ลงประวัติ “ปิดร้านย้อนหลัง” · แจ้งเตือนแบบปิดร้าน · พนักงาน (สิทธิ์ store) ทำตอนเปิดร้านวันใหม่ก่อนขาย — ที่แยกไว้ยกเป็นเงินทอนวันนี้ · ผู้ดูแลปิดวันไหนก็ได้จากหน้าภาพรวม · ลงประวัติวันนี้ “ปิดร้านย้อนหลัง …” + แจ้งเตือนแบบปิดร้าน |
| `Stock_reopenStore($code, $user, $reason)` | `store_reopen` | เปิดร้านใหม่หลังปิดไปแล้ว — ผู้ดูแลเท่านั้น ต้องมีเหตุผล · ผลของรอบที่ปิดไป (นับได้ / ควรมี) ยังอยู่ในแถวและประวัติจนกว่าจะปิดใหม่ |
| `Stock_addCashMove($code, $user, $dir, $amount, $reason)` | `store_cash_add` | เติมเงินทอน (in) / หยิบเงินออก (out) ระหว่างวัน — ร้านต้องเปิดอยู่ · ต้องมีเหตุผล · หยิบเกินเงินที่ควรมีในลิ้นชักไม่ได้ · ร้านต้องเปิดอยู่ · ต้องมีเหตุผล · หยิบเกินเงินที่ควรมีในลิ้นชักไม่ได้ |
| `Stock_getStoreRefunds($code)` | `store_refunds` | เงินสดที่คืนลูกค้าวันนี้ (รับคืนสินค้า) — จ่ายออกจากลิ้นชักของวันนี้เสมอ แม้บิลเดิมจะเป็นของวันก่อน |
| `Stock_getCashSales($code)` | `store_cash_sales` | ยอดขายเงินสดวันนี้ (ไม่นับบิลที่ยกเลิก · บิลโอน/พร้อมเพย์ไม่เข้าลิ้นชัก) |

## `api/return.php` — รับคืนสินค้า (21)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_getReturnLookbackDays()` | `return_lookback_days` | ค้นบิลย้อนหลังได้กี่วัน = กำหนดคืนของสาขา + 7 อย่างน้อย 14 (รับ `$code` · ไม่ระบุ = 14) — มากกว่ากำหนดคืน เพื่อให้เห็นบิลที่เกินกำหนดด้วย · ไม่ระบุสาขา = 14 |
| `Stock_getReturnReasons()` | `return_reasons` | เหตุผลการรับคืน |
| `Stock_getReturnReasonLabel($k)` | `return_reason_label` | ชื่อเหตุผล |
| `Stock_getReturnProduct($sku)` | `ret_product` | สินค้าจาก SKU (รวมที่เลิกขาย — บิลเก่ายังอ้างถึงได้) |
| `Stock_getPastBills($code, $ts)` | `past_bills` | บิลทั้งหมดของสาขาในวันหนึ่ง รวมบิลที่ยกเลิก — ใช้กับประวัติย้อนหลัง |
| `Stock_searchReturnBills($code, $q, $limit = 100)` | `return_find_bills` | ค้นบิลย้อนหลัง Stock_getReturnLookbackDays() วัน — เลขบิล ชื่อสินค้า หรือ SKU · ใหม่สุดขึ้นก่อน (ไม่รวมบิลที่ยกเลิก) |
| `Stock_getReturnBill($code, $no)` | `return_bill` | หาบิลจากเลขที่บิล (เช่น RS2026-09-0012 / RSV2026-09-0003) — ไม่รวมบิลที่ยกเลิก · เกินกำหนดคืนให้ Stock_getBillReturnStatus บอก |
| `Stock_getBillAgeDays($b)` | `bill_age_days` | บิลนี้ผ่านมากี่วันแล้ว (วันนี้ = 0) |
| `Stock_getReturnedQtyOfBill($no, $bill = null)` | `returned_of_bill` | จำนวนที่คืนไปแล้วของบิล — array( SKU => จำนวน ) (1 SKU มีแถวเดียวในบิล เพราะตะกร้าแยกตาม SKU) |
| `Stock_billHasReturns($no, $bill = null)` | `bill_has_returns` | บิลนี้มีของที่คืนไปแล้วไหม (นับจากจำนวนที่คืน) |
| `Stock_getReturnLines($bill)` | `return_lines` | แต่ละรายการในบิล + คืนไปแล้ว + คืนได้อีก |
| `Stock_getBillReturnStatus($bill, $user = null)` | `bill_return_status` | สถานะของบิลสำหรับการคืน · เกินกำหนดคืนไม่ได้ (ผู้ดูแลขยายจำนวนวันของสาขาได้) |
| `Stock_queryReturns($where, $params)` | `returns_query` | อ่านใบรับคืน — $where ต่อท้าย WHERE (alias r = ใบรับคืน · b = สาขา · s = บิลเดิม) · เรียงตามเวลา |
| `Stock_getReturnsToday($code)` | `returns_today` | ใบรับคืนของสาขาวันนี้ |
| `Stock_getReturnByNo($code, $no)` | `return_by_no` | ใบรับคืนของวันนี้จากเลขที่ |
| `Stock_saveReturn($code, $user, $bill, $qtys, $reason, $note, $refund, $refundNote)` | `return_save` | บันทึกการรับคืน — ล็อกบิลเดิม → เช็กร้านเปิด + คืนไม่เกินที่เหลือ → ใบรับคืน + รายการ + stock_move (ถ้าเข้าสต๊อก) + ประวัติ · ต้องมีสิทธิ์ refund · บิลไม่เก่ากว่า backdate_days ของสาขา · คืนบางรายการ / บางชิ้นได้ ไม่เกินที่ซื้อหักที่คืนแล้ว (เช็กซ้ำหลังล็อกบิล กันสองเครื่อง) · เข้าสต๊อก (move return) เฉพาะซื้อผิดรุ่น / ผิดแบบ · ต้องเปิดร้าน · ยอดเงินคืนแก้ได้ไม่เกินที่จ่าย (แก้ต้องมีเหตุผล) · $qtys = SKU => จำนวน · $refund ว่าง = ยอดคำนวณ |
| `Stock_checkReturnPhotos($field)` | `return_photos_check` | ตรวจรูปที่อัปโหลดมา — คืน array('files' => รายการไฟล์ที่ผ่าน) หรือ array('error' => ข้อความ) |
| `Stock_storeReturnPhotos($code, $no, $files)` | `return_photos_store` | เก็บรูปที่ตรวจแล้ว แล้วผูกกับใบรับคืน (stock_return.photos) — คืน path ที่บันทึกได้ (ใต้ uploads/) · เก็บที่ uploads/stock/returns/{เลขที่ใบ}-{ลำดับ}.{jpg / png / webp} ด้วย UpFile · path เป็น JSON ใน stock_return.photos · uploads/.htaccess กันไฟล์สคริปต์ |
| `Stock_getReturnsOfDay($code, $ts)` | `returns_of_day` | ใบรับคืนของวันหนึ่ง |
| `Stock_getReturnsRange($codes, $from, $to)` | `returns_range` | ใบรับคืนของหลายสาขา ช่วงวันที่ $from–$to (หน้าตรวจสอบของผู้ดูแล) — หัวใบ + รายการ 2 คิวรี เรียงตามเวลาที่ออกใบ |
| `Stock_findReturn($code, $no)` | `return_doc_find` | หาใบรับคืนจากเลขที่ RT-ปปดดวว-NNNN (วันไหนก็ได้) |

## `api/bill.php` — บัญชี · หัวบิล · เลขที่บิล · VAT (30)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_getAccountSettingColumn($key)` | `acct_setting_col` | ชื่อค่าตั้งบัญชี / หัวบิล → คอลัมน์ใน ao_stock_branch ('' = ไม่มีค่านี้) · เก็บในคอลัมน์ของ ao_stock_branch · เลขที่ออกตอนบันทึกบิล (stock_doc_seq) แล้วเก็บในบิล · เปลี่ยนรหัสนำหน้ากลางเดือน = ใบถัดไปใช้รหัสใหม่ เลขรันนับต่อ |
| `Stock_getAccountSetting($code, $key)` | `acct_setting` | ค่าตั้งบัญชี / หัวบิลของสาขา (string เสมอ — novat_tax = '1' / '0') |
| `Stock_getBillHeadKeys()` | `bill_head_keys` | สิ่งที่พิมพ์บนบิล (ตั้งได้ทุกสาขาที่หน้า "ตั้งค่าเลขที่บิล" ของฝ่ายบัญชี) · company · bill_address (ว่าง = ที่อยู่สาขา) · bill_phone (ว่าง = เบอร์สาขา) · bill_extra · title_vat · title_novat · footer · paper (80 / a4) · novat_tax (1 / 0) |
| `Stock_getBillHead($code)` | `bill_head` | ข้อมูลหัวบิลที่จะพิมพ์จริงของสาขา (ที่อยู่ / เบอร์ว่าง → ใช้ของสาขา) |
| `Stock_getVatMissing($code)` | — (ใหม่ 20 ต.ค.) | สิ่งที่สาขายังขาดสำหรับออกบิล VAT (ใบกำกับภาษีอย่างย่อ): เลขประจำตัวผู้เสียภาษี 13 หลัก · ชื่อร้าน / บริษัทบนหัวบิล · เลขที่สาขา 5 หลัก — array ของชื่อที่ขาด (ว่าง = ออกได้) · Stock_saveBill ปฏิเสธบิล VAT · หน้าขายปิดปุ่ม VAT · หน้าตั้งค่าเลขที่บิลขึ้นป้าย |
| `Stock_formatTaxId($id)` | `tax_id_format` | เลขผู้เสียภาษีแบบอ่านง่าย 0-1055-66012-34-5 |
| `Stock_formatTaxBranch($no)` | `tax_branch_label` | ข้อความสาขาตามแบบกรมสรรพากร: 00000 = สำนักงานใหญ่ |
| `Stock_findBill($code, $ts, $no)` | `bill_find` | หาบิลจากเลขที่ของวันหนึ่ง (รวมบิลที่ยกเลิก — หน้าพิมพ์แสดงตราว่ายกเลิก) |
| `Stock_fillBillForPrint($b)` | `bill_print_fill` | เติมค่าที่บิลตัวอย่างไม่มี (ราคาเต็ม / ส่วนลด / รับเงิน / เงินทอน) ให้พิมพ์ได้ครบ |
| `Stock_getSampleBill($code, $vat)` | `bill_sample` | บิลตัวอย่างสำหรับดูหน้าตาจากหน้าตั้งค่า (ไม่ใช่บิลจริง ไม่มีเลขรัน) |
| `Stock_bahtText($amount)` | `thai_baht_text` | จำนวนเงินเป็นตัวอักษรไทย เช่น 1,250.50 → หนึ่งพันสองร้อยห้าสิบบาทห้าสิบสตางค์ |
| `Stock_thaiNumberText($n)` | `thai_number_text` | ตัวเลขจำนวนเต็มเป็นคำอ่านไทย (รองรับหลักล้านซ้อน) |
| `Stock_billPrintUrl($code, $ts, $no, $more = array())` | `bill_print_url` | ลิงก์หน้าพิมพ์บิล |
| `Stock_buildBillPrintButton($bill)` | — (ใหม่ หลังช่วงที่ 16) | HTML ปุ่ม “ดู / พิมพ์บิล” ที่เปิด popup บิล — หน้าขาย (หลังบันทึก) · ประวัติวันนี้ · บิลของวันก่อน · $bill จาก sale_bills_query (ใช้ branch / ts / no) · พนักงานเปิดได้เฉพาะบิลสาขาตัวเอง (bill-print.php เช็กซ้ำ) |
| `Stock_setAccountSetting($code, $key, $val)` | `acct_setting_set` | บันทึกค่าตั้งบัญชี / หัวบิลของสาขา |
| `Stock_getBillPrefix($code, $vat)` | `bill_prefix` | รหัสนำหน้าของชุดเลข: $vat = true → ชุด VAT |
| `Stock_formatBillNo($prefix, $ts, $n)` | `bill_no_format` | ประกอบเลขที่บิล เช่น BP2026-01-0001 · รหัสลงท้ายด้วยตัวเลขมีขีดคั่นหน้าปี QA1-2026-01-0001 (20 ต.ค.) |
| `Stock_splitVat($total)` | `vat_split` | แยกยอดรวม (รวม VAT แล้ว) เป็น array(ก่อน VAT, VAT) |
| `Stock_getBillTypeLabel($vat)` | `bill_type_label` | ชื่อชุดเลข |
| `Stock_getNextBillNoPreview($code, $vat)` | `bill_next_no_series` | เลขที่บิลใบถัดไปของเดือนนี้ (แสดงตัวอย่างเท่านั้น — เลขจริงออกตอนบันทึกบิลใน Stock_saveBill) · เลขของบิลที่ถูกยกเลิกไม่นำมาใช้ซ้ำ |
| `Stock_checkBillPrefix($code, $key, $val, $all)` | `acct_prefix_error` | ตรวจรหัสนำหน้า: A–Z / 0–9 ยาว 1–6 ตัว และไม่ซ้ำกับชุดอื่นทุกสาขา |
| `Stock_getAccountBillsOfDay($code, $ts)` | `acct_bills` | บิลทั้งหมดของสาขาในวันหนึ่ง รวมบิลที่ยกเลิก (บัญชีต้องเห็นเลขที่ครบทุกใบ) เรียงตามเวลา |
| `Stock_getAccountRefundsOfDay($code, $ts)` | `acct_refunds` | เงินสดที่คืนลูกค้าในวันหนึ่งของสาขา (ใบรับคืนที่ออกวันนั้น) |
| `Stock_addVatToBill($b, $code)` | `acct_bill_row` | เพิ่มยอดก่อน VAT / VAT ให้บิล |
| `Stock_getAccountDay($code, $ts)` | `acct_day` | สรุปของสาขาในวันหนึ่ง · คืน array(bills, v, n, void, total, base, vat, cash, transfer, refund, net, rows) |
| `Stock_getAccountRange($code, $from, $to)` | `acct_range` | สรุปของสาขาช่วง $from–$to แบบเดียวกับ Stock_getAccountDay แต่ไม่มีรายการบิล — SQL 2 คำสั่ง (บิล + เงินคืน) · คืนแบบ acct_day ไม่มี rows · bills / v / n นับรวมบิลยกเลิก (เลขที่ต้องครบ) · ยอดเงินไม่นับบิลยกเลิก · ก่อน VAT / VAT ใช้ค่าที่เก็บในบิล |
| `Stock_getAccountMonth($code, $ts)` | `acct_month` | สรุปทั้งเดือน (ตั้งแต่วันที่ 1 ถึงวันนี้หรือสิ้นเดือน) ของสาขา |
| `Stock_getAccountBillsRange($codes, $from, $to)` | `acct_bills_range` | บิลทั้งหมด (รวมที่ยกเลิก) ของหลายสาขา ช่วง $from–$to — เรียงวัน → ลำดับสาขา → เวลา (ไฟล์ CSV ของฝ่ายบัญชี) · เรียงวัน → ลำดับสาขา → เวลา |
| `Stock_compareAccountRows($a, $b)` | `acct_row_cmp` | ตัวเรียง (usort): วัน → ลำดับสาขา → เวลา |
| `Stock_isBillPrefixUsed($p)` | `prefix_in_use` | รหัสเลขที่บิลนี้ถูกใช้แล้วหรือยัง (ทุกสาขา ทุกชุด) |

## `api/report.php` — รายงาน · ผลงานพนักงาน (21)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_getPendingTasks($branch)` | `pending_tasks` | งานค้างของสาขา (การ์ด "งานที่รอดำเนินการ" ในภาพรวมพนักงาน) — ว่าง = ไม่มีงานค้าง · คืน array ของ array(type count / store, title, sub, link) |
| `Stock_getStaffWorkKinds()` | `staff_work_kinds` | ชนิดงานที่นับ — ลำดับนี้คือลำดับที่แสดงในหน้า |
| `Stock_getStaffWorkRows($col, $id, $from, $to)` | `staff_work_rows` | เอกสารทุกชนิดที่ไม่ถูกยกเลิก ช่วงวันที่ $from–$to (timestamp) ของคนหนึ่ง ($col = created_by) หรือสาขาหนึ่ง ($col = branch_id) · $col = created_by (คน) หรือ branch_id (สาขา) · คืนแถว array(k ชนิด, d Y-m-d, uid, docs, items, qty) รวมต่อ (ชนิด, วัน, คน) · ตรวจนับ: ชิ้น = จำนวนที่นับได้ · แก้ไขใบ = นับใบใหม่แทน |
| `Stock_getStaffDays($username, $from, $to)` | `staff_days` | ผลงานรายวันของคนหนึ่ง ครบทุกวันในช่วง $from–$to (เรียงจากเก่าไปใหม่) · คืน array(Y-m-d => array(ts, idle, docs, items, qty, kinds)) · idle = ไม่มีเอกสารวันนั้น (ระบบไม่มีตารางวันหยุด) |
| `Stock_sumStaffWork($rows)` | `staff_sum` | รวมผลงานหลายวัน — days = จำนวนวันที่มีเอกสาร |
| `Stock_splitStaffWorkTypes($day)` | `staff_work_types` | แบ่งงานของวันหนึ่งตามชนิดเอกสาร (แถวจาก Stock_getStaffDays) — เฉพาะชนิดที่มีเอกสาร · คืน array ของ array(type, label, docs, qty, pct = สัดส่วนชิ้น) |
| `Stock_getStaffTopProducts($username, $limit = 4)` | `staff_top_products` | สินค้าที่คนนี้จัดการมากที่สุดวันนี้ (ทุกชนิดเอกสาร ไม่นับใบที่ยกเลิก) — array ของ array(product, qty) |
| `Stock_getBranchRankToday($branchCode)` | `branch_rank_today` | อันดับงานวันนี้ในสาขา: พนักงานที่ประจำสาขา + คนอื่นที่มาทำเอกสารของสาขานี้วันนี้ (เช่น ผู้ดูแล / ช่วยงานข้ามสาขา) · คืน array(username, name, branch, idle, docs, qty) เรียงชิ้นมากไปน้อย · คนที่ยังไม่มีรายการอยู่ท้าย |
| `Stock_compareRankQty($a, $b)` | `compare_rank_qty` | ตัวเรียง (usort): จำนวนชิ้นมากก่อน |
| `Stock_getAdminRange($mode, $dayTs, $monTs, $year)` | `adm_range` | ช่วงวันที่ของหน้าตรวจสอบฝั่งผู้ดูแล — คืน array(จาก, ถึง, ข้อความ) |
| `Stock_aggregateSales($codes, $from, $to, $uid = 0)` | `sales_agg` | ยอดขายของบิลที่ไม่ยกเลิก ช่วง $from–$to (timestamp) ของสาขาใน $codes · $uid > 0 = เฉพาะคนขายคนนี้ (staff_id) · สาขาถูกปั๊มลงบิลตอนบันทึก ไม่อ่านจากโปรไฟล์ตอนออกรายงาน (พนักงานย้ายสาขาแล้วยอดเก่าไม่ย้ายตาม) · บิลยกเลิกไม่นับ · ทั้งช่วงในคิวรีเดียว · แถว: d · branch · uid · user · name · bills · qty · total · disc · cash · vat (จำนวนบิล VAT) เรียงวันแล้วลำดับสาขา |
| `Stock_countVoidBills($codes, $from, $to)` | `sales_void_count` | จำนวนบิลที่ยกเลิก ช่วง $from–$to ของสาขาใน $codes |
| `Stock_getFirstSaleDay()` | `sales_first_day` | วันแรกที่มีบิลขาย (timestamp เที่ยงคืน) — ยังไม่มีบิลเลย = วันนี้ · จุดเริ่ม "ตั้งแต่เริ่มใช้ระบบ" และเดือนแรกที่เลือกดูได้ในรายงาน |
| `Stock_sumSales($codes, $from, $to)` | `sales_scan` | รวมยอดขาย (ไม่นับบิลยกเลิก) ช่วง $from–$to ของสาขาใน $codes · ใช้กับ adm-report-branch / adm-report-staff · daily_matrix · อีเมลรายวัน · คืน 'day' => Ymd => สาขา => (total, bills, qty) ครบทุกวัน × สาขา · 'staff' => username => (name, branch, bills, qty, total, disc, days) |
| `Stock_getDailyMatrix($branches, $monTs, $v)` | `daily_matrix` | ตารางยอดขายรายวันของเดือน: แถว = ทุกวันของเดือน (1 → สิ้นเดือน) · คอลัมน์ = สาขา · $branches = รหัส => ข้อมูลสาขา · คืน rows (ปปปปดดวว => ts, future, cells, sum) · col · open · best · grand · cellMax · bestDay |
| `Stock_getProductSales($codes, $from, $to)` | `product_sales` | ยอดขายรายสินค้า (ไม่นับบิลยกเลิก) ช่วง $from–$to ของสาขาใน $codes — SQL คำสั่งเดียว · ยอดรายสินค้า = ราคารายการ × ยอดจ่ายจริง ÷ ราคาเต็มของบิล · ชื่อ / หมวด / หน่วยจากสินค้าปัจจุบัน (ไม่มีแล้วใช้ชื่อในบิล) · คืน SKU => (sku, name, cat, unit, qty, total, bills, branches) |
| `Stock_getReportRange($mode, $ref, $user)` | `report_range` | ช่วงวันที่ของแต่ละโหมด — คืน array(from_ts, to_ts, label) |
| `Stock_getSalesReport($user, $scope, $mode, $ref)` | `sales_report` | ดึงรายงานตามเงื่อนไข (report-sales.php) · mine = เฉพาะฉันทุกสาขาที่เคยขาย · branch = ทั้งสาขาของฉัน · คืน label, from, to, sum, days (ครบทุกวัน), people (เรียงยอด), branches |
| `Stock_groupByMonth($days)` | `group_by_month` | ยุบรายวันเป็นรายเดือน ใช้ตอนช่วงยาวเกินกว่าจะวาดทีละวัน |
| `Stock_getReportMonths($user, $limit = 6)` | `report_months` | เดือนย้อนหลังที่เลือกได้ |
| `Stock_compareReviewItems($a, $b)` | `dash_review_cmp` | เรียงรายการที่ต้องตรวจ ใหม่สุดขึ้นก่อน |

## `api/notify.php` — แจ้งเตือน Telegram · อีเมลสรุปรายวัน · cron (40)

| ชื่อใหม่ | ชื่อเดิม | ทำอะไร |
|---|---|---|
| `Stock_getNotifyDefaults()` | `notify_defaults` | ค่าเริ่มต้นของการแจ้งเตือน |
| `Stock_getNotifySecretKeys()` | `notify_secret_keys` | คีย์ที่เป็นค่าลับ (เก็บแบบเข้ารหัส ไม่ส่งกลับไปแสดงบนหน้าเว็บ) |
| `Stock_getNotifySetting($k)` | `notify_get` | ค่าตั้งการแจ้งเตือน — ค่าที่ค่าเริ่มต้นเป็น array คืนเป็น array (เก็บเป็น JSON) |
| `Stock_setNotifySetting($k, $v)` | `notify_set` | บันทึกค่าตั้งการแจ้งเตือน — ผู้แก้ = ผู้ใช้ที่เข้าระบบอยู่ (cron = 0) |
| `Stock_isNotifyEnabled()` | `notify_enabled` | ส่งแจ้งเตือนได้ไหมโดยรวม — กลุ่มฟีเจอร์ notify ไม่ได้ถูกปิดจากหลังบ้าน |
| `Stock_getTelegramEvents()` | `notify_tg_events` | เหตุการณ์ที่เลือกส่งเข้า Telegram ได้: key => array(ชื่อ, คำอธิบาย) |
| `Stock_getMailParts()` | `notify_mail_parts` | ส่วนที่เลือกใส่ในอีเมลสรุปรายวันได้ (ยอดขายแยกสาขามีเสมอ) |
| `Stock_maskSecret($v)` | `mask_secret` | ซ่อนค่าลับ เหลือ 4 ตัวท้าย |
| `Stock_isTelegramTokenValid($t)` | `tg_token_valid` | ตรวจรูปแบบ Bot token ของ Telegram เช่น 123456789:AA… |
| `Stock_parseTelegramChats($s)` | `tg_chats_parse` | แยก Chat ID หลายค่า (คั่นด้วยบรรทัด / จุลภาค / ช่องว่าง) คืน array(ids, ค่าที่ผิด) |
| `Stock_parseMailList($s)` | `mail_list_parse` | แยกอีเมลหลายรายการ คืน array(อีเมล, ค่าที่ผิด) |
| `Stock_callTelegramApi($token, $method, $params = array(), $timeout = 10)` | `tg_api` | เรียก Telegram Bot API · คืน array(ok, ข้อมูล \| ข้อความผิดพลาด, เชื่อมต่อไม่ได้เลย true/false) · $timeout = วินาทีรวม (ต่อสาย = ครึ่งหนึ่ง · แจ้งเหตุการณ์ใช้ 5 วินาที) · AOSTOCK_TG_API (ตั้งใน fix.localhost.php เท่านั้น) = ส่งไปเซิร์ฟเวอร์จำลองตอนทดสอบ |
| `Stock_findTelegramChats($token)` | `tg_find_chats` | หา Chat ID จากข้อความล่าสุดที่คนทักบอท (getUpdates) คืน array(ok, array(id => ชื่อ) \| ข้อความผิดพลาด) |
| `Stock_getLastSalesDay($codes)` | `notify_last_sales_day` | วันล่าสุดก่อนวันนี้ที่มีบิลขาย (ไว้ทำตัวอย่าง) — ยังไม่มีเลย = เมื่อวาน |
| `Stock_buildTelegramText($event, $d)` | `notify_tg_text` | ข้อความของเหตุการณ์ — คืน '' ถ้าไม่รู้จักชนิด · ใช้ทั้งเหตุการณ์จริง (notify_on_*) และตัวอย่างในหน้าตั้งค่า (notify_tg_sample) |
| `Stock_buildNotifyBase($code, $by = '', $ts = null)` | `notify_d_base` | ข้อมูลพื้นฐานของข้อความ: สาขา วันที่ เวลา ผู้ทำ |
| `Stock_getNotifyDocLabel($kind)` | `notify_doc_label` | ชื่อชนิดเอกสารในข้อความ |
| `Stock_buildItemsText($lines, $qtyKey = 'qty')` | `notify_items_text` | รายชื่อสินค้าแบบสั้นในข้อความ — "ชื่อ ×จำนวน · …" ไม่เกิน 5 รายการ |
| `Stock_buildNotifyStoreData($code, $ts)` | `notify_d_store` | ข้อมูลข้อความเปิดร้าน / ปิดร้าน / เงินขาดเกินของวันหนึ่ง (จาก ao_stock_store_day + บิลของวันนั้น) — ไม่มีการเปิดร้าน = null · จาก ao_stock_store_day + บิลของวันนั้น |
| `Stock_wantsNotify($event, $code)` | `notify_wants` | เหตุการณ์นี้ของสาขานี้ต้องส่งเข้า Telegram ไหม (เปิดใช้ · เลือกเหตุการณ์ไว้ · สาขาอยู่ในขอบเขต · กลุ่มฟีเจอร์ไม่ถูกปิด) · เปิดใช้ · เลือกเหตุการณ์ไว้ · สาขาอยู่ในขอบเขต · กลุ่มฟีเจอร์ไม่ถูกปิด |
| `Stock_queueNotifyEvent($event, $code, $text, $ref = '')` | `notify_event` | แจ้งเหตุการณ์เข้า Telegram — เข้าคิวไว้ ส่งตอนจบ request (หลังบันทึกลงฐานข้อมูลแล้ว) · คืน true ถ้าเข้าคิว · ค่าตั้งอยู่ใน ao_stock_setting (token / รหัสผ่าน SMTP เข้ารหัส) · กลุ่มฟีเจอร์ notify ถูกปิด = ไม่ส่งอะไรเลย |
| `Stock_notifyQueue($item = null)` | `notify_queue` | คิวของ request นี้ — ใส่ $item = เพิ่ม (ลงทะเบียน Stock_flushNotifyQueue ตอนจบ request ครั้งแรก) · ไม่ใส่ = ดึงทั้งคิวออก |
| `Stock_flushNotifyQueue()` | `notify_flush` | ส่งทุกข้อความในคิวเข้าทุกแชต แล้วลงประวัติ — ทำงานตอนจบ request (register_shutdown_function) · register_shutdown_function · PHP-FPM ตอบหน้าเว็บก่อน (fastcgi_finish_request) · ที่ข้ามลงประวัติว่าข้าม ไม่ให้หน้าร้านรอซ้ำ |
| `Stock_getNotifySubject($text)` | `notify_subject` | หัวเรื่องสั้นของข้อความ (บรรทัดแรก ไม่มีแท็ก) ไว้ลงประวัติ |
| `Stock_notifyOnOpen($code)` | `notify_on_open` | เปิดร้าน (+ สินค้าใกล้หมดของสาขา วันละครั้งตอนเปิดร้าน) |
| `Stock_notifyOnClose($code)` | `notify_on_close` | ปิดร้าน + เงินขาด / เกิน · วันก่อน = ปิดร้านย้อนหลัง |
| `Stock_notifyOnReopen($code, $user, $reason, $st)` | `notify_on_reopen` | เปิดร้านใหม่หลังปิด — $st = สถานะก่อนเปิดใหม่ (ยังมีเวลา / คนปิด / เงินที่นับได้) |
| `Stock_notifyOnVoid($code, $kind, $doc, $user, $reason, $edit)` | `notify_on_void` | ยกเลิก / ยกเลิกเพื่อแก้ไข บิลหรือเอกสารคลังของวันนี้ — $doc = บิล (SA) หรือเอกสาร (RC/IS/AD) ก่อนยกเลิก |
| `Stock_notifyOnRefund($code, $rt)` | `notify_on_refund` | รับคืนสินค้า — $rt = ใบรับคืนที่บันทึกแล้ว (Stock_getReturnByNo) |
| `Stock_notifyOnIssue($code, $doc)` | `notify_on_issue` | ตัดออกเพราะสูญหาย / ชำรุด — $doc = ใบตัดออกที่บันทึกแล้ว (เหตุผลอื่นไม่แจ้ง) |
| `Stock_notifyOnBackdate($code, $doc, $user, $reason, $edit)` | `notify_on_backdate` | ยกเลิกเอกสารคลังย้อนหลัง — $doc = เอกสารก่อนยกเลิก |
| `Stock_getTelegramSample($event, $code = '')` | `notify_tg_sample` | ตัวอย่างข้อความของแต่ละเหตุการณ์ — ใช้ข้อมูลล่าสุดที่มีจริงของสาขา (ยังไม่มีเหตุการณ์ชนิดนั้น = ค่าตัวอย่าง) |
| `Stock_getDailySummaryData($ts, $codes)` | `daily_summary_data` | ข้อมูลสรุปยอดขายของวันหนึ่ง สำหรับอีเมลรายวัน · คืน array(rows => สาขา => สรุป, sum, prev = ยอดวันขายก่อนหน้า, month = ยอดสะสมเดือน, top, low) |
| `Stock_getDailySummarySubject($ts, $data)` | `daily_summary_subject` | หัวเรื่องอีเมลสรุปรายวัน |
| `Stock_buildDailySummaryHtml($ts, $data, $parts)` | `daily_summary_email_html` | เนื้อหาอีเมลสรุปยอดขายรายวัน (HTML แบบ inline style ให้เปิดได้ทุกโปรแกรมอีเมล) |
| `Stock_sendSmtpMail($c, $to, $subject, $html)` | `smtp_send` | ส่งอีเมล HTML ผ่าน SMTP (รองรับ TLS / SSL / ไม่เข้ารหัส + AUTH LOGIN) · ไม่ตั้ง smtp_host = ใช้ mail() ของ PHP · รองรับ TLS / SSL / ไม่เข้ารหัส + AUTH LOGIN · $c = smtp_host, smtp_port, smtp_secure, smtp_user, smtp_pass, from_name, from_email |
| `Stock_addNotifyLog($channel, $event, $to, $ok, $subject, $error = '', $code = '', $ref = '', $uid = null)` | `notify_log_add` | บันทึกประวัติการส่งลง ao_stock_notify_log — $ok + $error ไม่ว่าง = ข้าม (เช่น วันนี้ไม่มียอดขาย) · $code = สาขา ('' = ทุกสาขา) · $uid = ผู้ทำ (null = ผู้ใช้ที่เข้าระบบอยู่ · cron = 0) |
| `Stock_getNotifyLogRows($limit = 30)` | `notify_log_rows` | ประวัติการส่งล่าสุด — array ของ array(ts, channel, event, label, to, ok, skip, subject, error, branch, ref) |
| `Stock_getSmtpConfig()` | `notify_smtp_cfg` | ค่า SMTP ที่ใช้ส่ง (Stock_sendSmtpMail) |
| `Stock_runNotifyCron($now = null)` | `notify_cron_run` | งานของ cron (admweb/aowebdata/modules/stock/cron.php — Cron Jobs ของโฮสต์ทุก 5 นาที) · ส่งเมื่อกลุ่มแจ้งเตือนไม่ถูกปิด + เปิดอีเมลรายวัน + เลยเวลา + วันนี้ยังไม่ส่ง · "เฉพาะวันที่มียอดขาย" แต่ไม่มีบิล = ข้าม · ส่งไม่สำเร็จลองใหม่ ≤ 3 ครั้ง / วัน (mail_fail) · cron ซ้อนกัน: จองวันใน mail_last_sent ด้วย UPDATE มีเงื่อนไขก่อนส่ง |
