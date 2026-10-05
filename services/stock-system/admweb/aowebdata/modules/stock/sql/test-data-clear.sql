-- ==========================================================
-- AOSTOCK — ล้างข้อมูลทั้งหมดในตาราง ao_stock_* (ใช้ลบข้อมูลทดสอบก่อนเริ่มใช้งานจริง)
-- FILE : admweb/aowebdata/modules/stock/sql/test-data-clear.sql
-- ⚠⚠ ลบข้อมูลทุกแถวของ AOSTOCK ทุกสาขา (สาขา พนักงาน สินค้า บิล เอกสาร ประวัติ ค่าตั้ง) — กู้คืนไม่ได้
--     ห้ามรันกับฐานข้อมูลที่ใช้งานจริงแล้ว · ตารางของ admweb (ao_member_* ฯลฯ) ไม่ถูกแตะ
-- ==========================================================

TRUNCATE TABLE `ao_stock_branch`;
TRUNCATE TABLE `ao_stock_staff`;
TRUNCATE TABLE `ao_stock_staff_branch`;
TRUNCATE TABLE `ao_stock_category`;
TRUNCATE TABLE `ao_stock_product`;
TRUNCATE TABLE `ao_stock_balance`;
TRUNCATE TABLE `ao_stock_move`;
TRUNCATE TABLE `ao_stock_receive`;
TRUNCATE TABLE `ao_stock_receive_item`;
TRUNCATE TABLE `ao_stock_issue`;
TRUNCATE TABLE `ao_stock_issue_item`;
TRUNCATE TABLE `ao_stock_count`;
TRUNCATE TABLE `ao_stock_count_item`;
TRUNCATE TABLE `ao_stock_log`;
TRUNCATE TABLE `ao_stock_doc_seq`;
TRUNCATE TABLE `ao_stock_store_day`;
TRUNCATE TABLE `ao_stock_cash_move`;
TRUNCATE TABLE `ao_stock_sale`;
TRUNCATE TABLE `ao_stock_sale_item`;
TRUNCATE TABLE `ao_stock_return`;
TRUNCATE TABLE `ao_stock_return_item`;
TRUNCATE TABLE `ao_stock_setting`;
TRUNCATE TABLE `ao_stock_notify_log`;
TRUNCATE TABLE `ao_stock_remember`;
TRUNCATE TABLE `ao_stock_login_ip`;
