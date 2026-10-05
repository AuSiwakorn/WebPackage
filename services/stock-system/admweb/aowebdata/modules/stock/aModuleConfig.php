<?php
/**
 * FILE: admweb/aowebdata/modules/stock/aModuleConfig.php
 * ROLE: ลงทะเบียนตารางและโฟลเดอร์ที่ต้องเขียนได้ของโมดูล stock (AOSTOCK) — inc_install.php อ่านไฟล์นี้
 * DEPENDS: database.php (schema ของตารางด้านล่าง)
 * TABLES: ao_stock_* 24 ตาราง
 * TODO:
 *   - [x] ลงทะเบียน 23 ตารางตาม database.php
 *   - [x] ช่วงที่ 10: เพิ่ม stock_remember (จดจำการเข้าสู่ระบบ)
 *   - [ ] เพิ่มตารางใหม่ใน database.php เมื่อไร ต้องเติมชื่อในนี้ด้วย
 */

$sqlType    = 'php';
$aTablename = array(
	// แกน AOSTOCK — ข้อมูลหลัก
	_DBPREFIX_ . 'stock_branch',
	_DBPREFIX_ . 'stock_staff',
	_DBPREFIX_ . 'stock_staff_branch',
	_DBPREFIX_ . 'stock_remember',
	_DBPREFIX_ . 'stock_category',
	_DBPREFIX_ . 'stock_product',
	// แกน AOSTOCK — สต๊อกและเอกสารคลัง
	_DBPREFIX_ . 'stock_balance',
	_DBPREFIX_ . 'stock_move',
	_DBPREFIX_ . 'stock_receive',
	_DBPREFIX_ . 'stock_receive_item',
	_DBPREFIX_ . 'stock_issue',
	_DBPREFIX_ . 'stock_issue_item',
	_DBPREFIX_ . 'stock_count',
	_DBPREFIX_ . 'stock_count_item',
	_DBPREFIX_ . 'stock_log',
	_DBPREFIX_ . 'stock_doc_seq',
	// โมดูล POS
	_DBPREFIX_ . 'stock_store_day',
	_DBPREFIX_ . 'stock_cash_move',
	_DBPREFIX_ . 'stock_sale',
	_DBPREFIX_ . 'stock_sale_item',
	_DBPREFIX_ . 'stock_return',
	_DBPREFIX_ . 'stock_return_item',
	// ค่าตั้งและการแจ้งเตือน
	_DBPREFIX_ . 'stock_setting',
	_DBPREFIX_ . 'stock_notify_log',
);

// โฟลเดอร์ที่ต้องเขียนได้ — รูปสินค้า uploads/stock/products (ช่วงที่ 5)
// และรูปแนบใบรับคืน uploads/stock/returns (ช่วงที่ 7 — ตอนนี้ POS ยังเก็บที่ uploads/returns)
$aPermission = array(
	PATH_UPLOAD . '/stock',
);
