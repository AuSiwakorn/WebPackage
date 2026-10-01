<?php
/* ==========================================================
   AOSTOCK DEMO — ข้อมูลตัวอย่าง (ยังไม่ต่อฐานข้อมูล)
   ทุกตัวเลขบนหน้าจอคำนวณจากข้อมูลในไฟล์นี้
   เมื่อทำระบบจริง: แทนที่ฟังก์ชันด้านล่างด้วย query ไปยัง MySQL
   รองรับ PHP 5.4 ขึ้นไป
   ========================================================== */

require_once dirname(__FILE__) . '/config.php';

/* ---------- สินค้า (ข้อมูลทดสอบ: ร้านอุปกรณ์มือถือ) ----------
   price = ราคาขายหน้าร้าน · cost = ต้นทุนต่อหน่วย (สมมติ ~50% ของราคาขาย)
   stock = ยอดคงเหลือแยกตามสาขา · reorder = จุดสั่งซื้อ
   สินค้าที่ราคาเป็นช่วง (เช่น 290–590) แยกเป็นหลาย SKU ตามรุ่น            */
function demo_products()
{
    return array(
        array('sku' => 'CS-001', 'name' => 'เคสมือถือ แฟชั่น', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 50.00, 'price' => 100, 'reorder' => 20, 'stock' => array('HQ' => 60, 'RS' => 2, 'BN' => 50)),
        array('sku' => 'CS-002', 'name' => 'เคสมือถือ แฟชั่น MOOPHE รุ่นมาตรฐาน', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 145.00, 'price' => 290, 'reorder' => 8, 'stock' => array('HQ' => 7, 'RS' => 7, 'BN' => 20)),
        array('sku' => 'CS-003', 'name' => 'เคสมือถือ แฟชั่น MOOPHE รุ่นพรีเมียม', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 295.00, 'price' => 590, 'reorder' => 8, 'stock' => array('HQ' => 21, 'RS' => 17, 'BN' => 13)),
        array('sku' => 'CS-004', 'name' => 'เคสมือถือ SwitchEasy รุ่นมาตรฐาน', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 345.00, 'price' => 690, 'reorder' => 5, 'stock' => array('HQ' => 1, 'RS' => 4, 'BN' => 14)),
        array('sku' => 'CS-005', 'name' => 'เคสมือถือ SwitchEasy รุ่นพรีเมียม', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 445.00, 'price' => 890, 'reorder' => 5, 'stock' => array('HQ' => 10, 'RS' => 9, 'BN' => 4)),
        array('sku' => 'CS-006', 'name' => 'เคสมือถือ Mutural รุ่นมาตรฐาน', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 345.00, 'price' => 690, 'reorder' => 5, 'stock' => array('HQ' => 17, 'RS' => 10, 'BN' => 13)),
        array('sku' => 'CS-007', 'name' => 'เคสมือถือ Mutural รุ่นพรีเมียม', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 445.00, 'price' => 890, 'reorder' => 5, 'stock' => array('HQ' => 14, 'RS' => 1, 'BN' => 12)),
        array('sku' => 'CS-008', 'name' => 'เคสมือถือ HI Shield รุ่นมาตรฐาน', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 445.00, 'price' => 890, 'reorder' => 4, 'stock' => array('HQ' => 3, 'RS' => 0, 'BN' => 7)),
        array('sku' => 'CS-009', 'name' => 'เคสมือถือ HI Shield รุ่นพรีเมียม', 'cat' => 'เคสมือถือ', 'unit' => 'ชิ้น', 'cost' => 745.00, 'price' => 1490, 'reorder' => 4, 'stock' => array('HQ' => 14, 'RS' => 4, 'BN' => 6)),
        array('sku' => 'TC-001', 'name' => 'เคส iPad MOOPHE รุ่นมาตรฐาน', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 245.00, 'price' => 490, 'reorder' => 4, 'stock' => array('HQ' => 3, 'RS' => 9, 'BN' => 7)),
        array('sku' => 'TC-002', 'name' => 'เคส iPad MOOPHE รุ่นพรีเมียม', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 295.00, 'price' => 590, 'reorder' => 4, 'stock' => array('HQ' => 7, 'RS' => 8, 'BN' => 5)),
        array('sku' => 'TC-003', 'name' => 'เคส iPad Domo รุ่นมาตรฐาน', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 345.00, 'price' => 690, 'reorder' => 3, 'stock' => array('HQ' => 3, 'RS' => 3, 'BN' => 0)),
        array('sku' => 'TC-004', 'name' => 'เคส iPad Domo รุ่นพรีเมียม', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 395.00, 'price' => 790, 'reorder' => 3, 'stock' => array('HQ' => 4, 'RS' => 4, 'BN' => 3)),
        array('sku' => 'TC-005', 'name' => 'เคส iPad Moshi รุ่นมาตรฐาน', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 395.00, 'price' => 790, 'reorder' => 3, 'stock' => array('HQ' => 8, 'RS' => 4, 'BN' => 8)),
        array('sku' => 'TC-006', 'name' => 'เคส iPad Moshi รุ่นพรีเมียม', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 495.00, 'price' => 990, 'reorder' => 3, 'stock' => array('HQ' => 6, 'RS' => 1, 'BN' => 6)),
        array('sku' => 'TC-007', 'name' => 'เคส iPad ลายการ์ตูน รุ่นมาตรฐาน', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 245.00, 'price' => 490, 'reorder' => 4, 'stock' => array('HQ' => 10, 'RS' => 1, 'BN' => 10)),
        array('sku' => 'TC-008', 'name' => 'เคส iPad ลายการ์ตูน รุ่นพรีเมียม', 'cat' => 'เคส iPad & Tablet', 'unit' => 'ชิ้น', 'cost' => 295.00, 'price' => 590, 'reorder' => 4, 'stock' => array('HQ' => 12, 'RS' => 9, 'BN' => 3)),
        array('sku' => 'FM-001', 'name' => 'กระจกใส ไร้ขอบ', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 40.00, 'price' => 79, 'reorder' => 30, 'stock' => array('HQ' => 60, 'RS' => 16, 'BN' => 85)),
        array('sku' => 'FM-002', 'name' => 'กระจกใส เต็มจอ', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 75.00, 'price' => 150, 'reorder' => 25, 'stock' => array('HQ' => 33, 'RS' => 54, 'BN' => 54)),
        array('sku' => 'FM-003', 'name' => 'กระจกใส เต็มจอ เกรดพิเศษ', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 100.00, 'price' => 200, 'reorder' => 20, 'stock' => array('HQ' => 51, 'RS' => 29, 'BN' => 36)),
        array('sku' => 'FM-004', 'name' => 'กระจกใส Focus iPhone', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 175.00, 'price' => 349, 'reorder' => 10, 'stock' => array('HQ' => 29, 'RS' => 18, 'BN' => 22)),
        array('sku' => 'FM-005', 'name' => 'กระจกใส Focus Android', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 125.00, 'price' => 249, 'reorder' => 10, 'stock' => array('HQ' => 11, 'RS' => 22, 'BN' => 11)),
        array('sku' => 'FM-006', 'name' => 'กระจกด้าน Focus', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 245.00, 'price' => 490, 'reorder' => 6, 'stock' => array('HQ' => 8, 'RS' => 2, 'BN' => 13)),
        array('sku' => 'FM-007', 'name' => 'กระจกด้าน U&i', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 145.00, 'price' => 290, 'reorder' => 8, 'stock' => array('HQ' => 5, 'RS' => 13, 'BN' => 14)),
        array('sku' => 'FM-008', 'name' => 'กระจกด้าน คิงคอง', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 195.00, 'price' => 390, 'reorder' => 8, 'stock' => array('HQ' => 30, 'RS' => 16, 'BN' => 6)),
        array('sku' => 'FM-009', 'name' => 'กระจกกรองแสงสีฟ้า Focus รุ่นมาตรฐาน', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 245.00, 'price' => 490, 'reorder' => 5, 'stock' => array('HQ' => 11, 'RS' => 13, 'BN' => 12)),
        array('sku' => 'FM-010', 'name' => 'กระจกกรองแสงสีฟ้า Focus รุ่นพรีเมียม', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 5, 'stock' => array('HQ' => 8, 'RS' => 10, 'BN' => 14)),
        array('sku' => 'FM-011', 'name' => 'กระจกกันมอง ใส U&i', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 145.00, 'price' => 290, 'reorder' => 8, 'stock' => array('HQ' => 26, 'RS' => 9, 'BN' => 10)),
        array('sku' => 'FM-012', 'name' => 'กระจกกันมอง ใส คิงคอง', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 195.00, 'price' => 390, 'reorder' => 8, 'stock' => array('HQ' => 2, 'RS' => 0, 'BN' => 16)),
        array('sku' => 'FM-013', 'name' => 'กระจกกันมอง ใส Leeplus', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 245.00, 'price' => 490, 'reorder' => 6, 'stock' => array('HQ' => 1, 'RS' => 6, 'BN' => 15)),
        array('sku' => 'FM-014', 'name' => 'กระจกกันมอง ใส Focus', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 6, 'stock' => array('HQ' => 15, 'RS' => 11, 'BN' => 7)),
        array('sku' => 'FM-015', 'name' => 'กระจกกันมอง ด้าน Focus', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 5, 'stock' => array('HQ' => 8, 'RS' => 14, 'BN' => 0)),
        array('sku' => 'FM-016', 'name' => 'กระจกประกัน 1 ปี ใส Focus รุ่นมาตรฐาน', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 4, 'stock' => array('HQ' => 14, 'RS' => 8, 'BN' => 10)),
        array('sku' => 'FM-017', 'name' => 'กระจกประกัน 1 ปี ใส Focus รุ่นพรีเมียม', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 445.00, 'price' => 890, 'reorder' => 4, 'stock' => array('HQ' => 1, 'RS' => 8, 'BN' => 11)),
        array('sku' => 'FM-018', 'name' => 'กระจกประกัน 1 ปี กันมอง Focus รุ่นมาตรฐาน', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 495.00, 'price' => 990, 'reorder' => 3, 'stock' => array('HQ' => 6, 'RS' => 0, 'BN' => 8)),
        array('sku' => 'FM-019', 'name' => 'กระจกประกัน 1 ปี กันมอง Focus รุ่นพรีเมียม', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 645.00, 'price' => 1290, 'reorder' => 3, 'stock' => array('HQ' => 1, 'RS' => 8, 'BN' => 7)),
        array('sku' => 'FM-020', 'name' => 'กระจกประกัน 1 ปี AR Focus', 'cat' => 'ฟิล์มมือถือ', 'unit' => 'แผ่น', 'cost' => 595.00, 'price' => 1190, 'reorder' => 3, 'stock' => array('HQ' => 8, 'RS' => 5, 'BN' => 6)),
        array('sku' => 'FT-001', 'name' => 'ฟิล์ม iPad กระจกใส Startec รุ่นมาตรฐาน', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 195.00, 'price' => 390, 'reorder' => 4, 'stock' => array('HQ' => 4, 'RS' => 9, 'BN' => 8)),
        array('sku' => 'FT-002', 'name' => 'ฟิล์ม iPad กระจกใส Startec รุ่นพรีเมียม', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 245.00, 'price' => 490, 'reorder' => 4, 'stock' => array('HQ' => 11, 'RS' => 8, 'BN' => 10)),
        array('sku' => 'FT-003', 'name' => 'ฟิล์ม iPad กระจกใส Focus รุ่นมาตรฐาน', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 4, 'stock' => array('HQ' => 9, 'RS' => 8, 'BN' => 11)),
        array('sku' => 'FT-004', 'name' => 'ฟิล์ม iPad กระจกใส Focus รุ่นพรีเมียม', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 4, 'stock' => array('HQ' => 0, 'RS' => 2, 'BN' => 7)),
        array('sku' => 'FT-005', 'name' => 'ฟิล์ม iPad กระจกด้าน Startec', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 3, 'stock' => array('HQ' => 0, 'RS' => 2, 'BN' => 5)),
        array('sku' => 'FT-006', 'name' => 'ฟิล์ม iPad กระจกด้าน Focus', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 3, 'stock' => array('HQ' => 5, 'RS' => 7, 'BN' => 2)),
        array('sku' => 'FT-007', 'name' => 'ฟิล์ม iPad ผิวกระดาษ Startec', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 3, 'stock' => array('HQ' => 9, 'RS' => 7, 'BN' => 8)),
        array('sku' => 'FT-008', 'name' => 'ฟิล์ม iPad ผิวกระดาษ Focus', 'cat' => 'ฟิล์ม iPad & Tablet', 'unit' => 'แผ่น', 'cost' => 395.00, 'price' => 790, 'reorder' => 3, 'stock' => array('HQ' => 5, 'RS' => 4, 'BN' => 2)),
        array('sku' => 'LN-001', 'name' => 'Color Ring Android รุ่นมาตรฐาน', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 100.00, 'price' => 200, 'reorder' => 6, 'stock' => array('HQ' => 16, 'RS' => 15, 'BN' => 15)),
        array('sku' => 'LN-002', 'name' => 'Color Ring Android รุ่นพรีเมียม', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 125.00, 'price' => 250, 'reorder' => 6, 'stock' => array('HQ' => 21, 'RS' => 2, 'BN' => 17)),
        array('sku' => 'LN-003', 'name' => 'Color Ring ZEON รุ่นมาตรฐาน', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 125.00, 'price' => 250, 'reorder' => 6, 'stock' => array('HQ' => 21, 'RS' => 5, 'BN' => 16)),
        array('sku' => 'LN-004', 'name' => 'Color Ring ZEON รุ่นพรีเมียม', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 145.00, 'price' => 290, 'reorder' => 6, 'stock' => array('HQ' => 21, 'RS' => 15, 'BN' => 8)),
        array('sku' => 'LN-005', 'name' => 'Color Ring Startec', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 195.00, 'price' => 390, 'reorder' => 5, 'stock' => array('HQ' => 10, 'RS' => 14, 'BN' => 8)),
        array('sku' => 'LN-006', 'name' => 'Color Ring Focus', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 245.00, 'price' => 490, 'reorder' => 5, 'stock' => array('HQ' => 19, 'RS' => 9, 'BN' => 8)),
        array('sku' => 'LN-007', 'name' => 'Color Ring Hi Shield', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 295.00, 'price' => 590, 'reorder' => 4, 'stock' => array('HQ' => 14, 'RS' => 3, 'BN' => 8)),
        array('sku' => 'LN-008', 'name' => 'Color Ring Hi Shield SAPPHIRE รุ่นมาตรฐาน', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 595.00, 'price' => 1190, 'reorder' => 2, 'stock' => array('HQ' => 2, 'RS' => 3, 'BN' => 2)),
        array('sku' => 'LN-009', 'name' => 'Color Ring Hi Shield SAPPHIRE รุ่นพรีเมียม', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 645.00, 'price' => 1290, 'reorder' => 2, 'stock' => array('HQ' => 4, 'RS' => 3, 'BN' => 3)),
        array('sku' => 'LN-010', 'name' => 'Diamond Look ZEON', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 145.00, 'price' => 290, 'reorder' => 5, 'stock' => array('HQ' => 3, 'RS' => 13, 'BN' => 10)),
        array('sku' => 'LN-011', 'name' => 'Diamond Look Startec รุ่นมาตรฐาน', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 195.00, 'price' => 390, 'reorder' => 4, 'stock' => array('HQ' => 2, 'RS' => 4, 'BN' => 4)),
        array('sku' => 'LN-012', 'name' => 'Diamond Look Startec รุ่นพรีเมียม', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 245.00, 'price' => 490, 'reorder' => 4, 'stock' => array('HQ' => 3, 'RS' => 5, 'BN' => 0)),
        array('sku' => 'LN-013', 'name' => 'Diamond Look Hi Shield', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 345.00, 'price' => 690, 'reorder' => 3, 'stock' => array('HQ' => 1, 'RS' => 8, 'BN' => 8)),
        array('sku' => 'LN-014', 'name' => 'ฟิล์มเลนส์ ZEON', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 100.00, 'price' => 200, 'reorder' => 6, 'stock' => array('HQ' => 11, 'RS' => 0, 'BN' => 9)),
        array('sku' => 'LN-015', 'name' => 'ฟิล์มเลนส์ Focus รุ่นมาตรฐาน', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 145.00, 'price' => 290, 'reorder' => 5, 'stock' => array('HQ' => 10, 'RS' => 5, 'BN' => 11)),
        array('sku' => 'LN-016', 'name' => 'ฟิล์มเลนส์ Focus รุ่นพรีเมียม', 'cat' => 'กระจกเลนส์กล้อง', 'unit' => 'ชิ้น', 'cost' => 195.00, 'price' => 390, 'reorder' => 5, 'stock' => array('HQ' => 16, 'RS' => 6, 'BN' => 9)),
        array('sku' => 'HG-001', 'name' => 'Hydrogel Clear STARTEC Lite', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 125.00, 'price' => 250, 'reorder' => 5, 'stock' => array('HQ' => 18, 'RS' => 10, 'BN' => 13)),
        array('sku' => 'HG-002', 'name' => 'Hydrogel Clear STARTEC Standard', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 195.00, 'price' => 390, 'reorder' => 5, 'stock' => array('HQ' => 9, 'RS' => 3, 'BN' => 5)),
        array('sku' => 'HG-003', 'name' => 'Hydrogel Clear STARTEC Plus', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 245.00, 'price' => 490, 'reorder' => 5, 'stock' => array('HQ' => 11, 'RS' => 9, 'BN' => 8)),
        array('sku' => 'HG-004', 'name' => 'Hydrogel Clear STARTEC Pro', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 5, 'stock' => array('HQ' => 5, 'RS' => 10, 'BN' => 8)),
        array('sku' => 'HG-005', 'name' => 'Hydrogel Clear FOCUS', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 145.00, 'price' => 290, 'reorder' => 8, 'stock' => array('HQ' => 27, 'RS' => 21, 'BN' => 14)),
        array('sku' => 'HG-006', 'name' => 'Hydrogel Matte STARTEC Lite', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 145.00, 'price' => 290, 'reorder' => 5, 'stock' => array('HQ' => 15, 'RS' => 11, 'BN' => 9)),
        array('sku' => 'HG-007', 'name' => 'Hydrogel Matte STARTEC Standard', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 245.00, 'price' => 490, 'reorder' => 5, 'stock' => array('HQ' => 4, 'RS' => 11, 'BN' => 12)),
        array('sku' => 'HG-008', 'name' => 'Hydrogel Matte STARTEC Plus', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 5, 'stock' => array('HQ' => 14, 'RS' => 8, 'BN' => 0)),
        array('sku' => 'HG-009', 'name' => 'Hydrogel Matte STARTEC Pro', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 345.00, 'price' => 690, 'reorder' => 5, 'stock' => array('HQ' => 0, 'RS' => 8, 'BN' => 11)),
        array('sku' => 'HG-010', 'name' => 'Hydrogel Matte FOCUS', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 175.00, 'price' => 350, 'reorder' => 8, 'stock' => array('HQ' => 18, 'RS' => 0, 'BN' => 16)),
        array('sku' => 'HG-011', 'name' => 'Hydrogel Privacy STARTEC', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 195.00, 'price' => 390, 'reorder' => 5, 'stock' => array('HQ' => 5, 'RS' => 5, 'BN' => 4)),
        array('sku' => 'HG-012', 'name' => 'UV Film STARTEC', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 3, 'stock' => array('HQ' => 0, 'RS' => 4, 'BN' => 8)),
        array('sku' => 'HG-013', 'name' => 'UV Film Focus', 'cat' => 'ฟิล์มไฮโดรเจล', 'unit' => 'แผ่น', 'cost' => 295.00, 'price' => 590, 'reorder' => 3, 'stock' => array('HQ' => 4, 'RS' => 4, 'BN' => 3)),
        array('sku' => 'CB-001', 'name' => 'สายชาร์จ USB to Type-C UB-66C', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 195.00, 'price' => 390, 'reorder' => 5, 'stock' => array('HQ' => 18, 'RS' => 3, 'BN' => 6)),
        array('sku' => 'CB-002', 'name' => 'สายชาร์จ USB to Type-C UB-65', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 145.00, 'price' => 290, 'reorder' => 5, 'stock' => array('HQ' => 11, 'RS' => 6, 'BN' => 12)),
        array('sku' => 'CB-003', 'name' => 'สายชาร์จ USB to Type-C CB-N02C', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 100.00, 'price' => 200, 'reorder' => 8, 'stock' => array('HQ' => 19, 'RS' => 15, 'BN' => 8)),
        array('sku' => 'CB-004', 'name' => 'สายชาร์จ USB to Type-C CB-R01C', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 50.00, 'price' => 100, 'reorder' => 10, 'stock' => array('HQ' => 5, 'RS' => 28, 'BN' => 4)),
        array('sku' => 'CB-005', 'name' => 'สายชาร์จ Type-C to Type-C C3X-Pro', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 130.00, 'price' => 259, 'reorder' => 6, 'stock' => array('HQ' => 0, 'RS' => 16, 'BN' => 0)),
        array('sku' => 'CB-006', 'name' => 'สายชาร์จ Type-C to Type-C BO-X288C-C', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 195.00, 'price' => 390, 'reorder' => 5, 'stock' => array('HQ' => 16, 'RS' => 8, 'BN' => 10)),
        array('sku' => 'CB-007', 'name' => 'สายชาร์จ Type-C to Type-C BO-X288C-2M (2 ม.)', 'cat' => 'สายชาร์จ', 'unit' => 'เส้น', 'cost' => 245.00, 'price' => 490, 'reorder' => 5, 'stock' => array('HQ' => 1, 'RS' => 12, 'BN' => 9)),
    );
}

/* ---------- ความเคลื่อนไหวล่าสุด ----------
   type: receive | issue | adjust | count
   (โอนระหว่างสาขา — ยังไม่ทำ ใช้เบิกออก/นำเข้าแทน)
   status: done | pending | waiting  */
function demo_movements()
{
    return array(
        array('doc' => 'RC-2609-0142', 'type' => 'receive',  'branch' => 'HQ', 'to' => null, 'time' => '23 ก.ย. 09:12', 'items' => 3,  'qty' => 86,   'by' => 'สมชาย ใจดี',   'status' => 'done'),
        array('doc' => 'IS-2609-0208', 'type' => 'issue',    'branch' => 'BN', 'to' => null, 'time' => '22 ก.ย. 17:30', 'items' => 5,  'qty' => -12,  'by' => 'อนันต์ ศรีสุข', 'status' => 'done'),
        array('doc' => 'AD-2609-0011', 'type' => 'adjust',   'branch' => 'RS', 'to' => null, 'time' => '22 ก.ย. 15:02', 'items' => 1,  'qty' => -2,   'by' => 'นิภา วงศ์ทอง',  'status' => 'done'),
        array('doc' => 'RC-2609-0141', 'type' => 'receive',  'branch' => 'RS', 'to' => null, 'time' => '22 ก.ย. 11:20', 'items' => 4,  'qty' => 60,   'by' => 'นิภา วงศ์ทอง',  'status' => 'done'),
        array('doc' => 'IS-2609-0207', 'type' => 'issue',    'branch' => 'HQ', 'to' => null, 'time' => '21 ก.ย. 16:10', 'items' => 2,  'qty' => -6,   'by' => 'สมชาย ใจดี',   'status' => 'done'),
        array('doc' => 'ST-2609-0004', 'type' => 'count',    'branch' => 'BN', 'to' => null, 'time' => '20 ก.ย. 18:00', 'items' => 48, 'qty' => -3,   'by' => 'อนันต์ ศรีสุข', 'status' => 'pending'),
    );
}

function movement_types()
{
    return array(
        'receive'  => array('label' => 'รับเข้า', 'tone' => 'in'),
        'issue'    => array('label' => 'เบิกออก', 'tone' => 'out'),
        'adjust'   => array('label' => 'ปรับยอด', 'tone' => 'adj'),
        'count'    => array('label' => 'ตรวจนับ', 'tone' => 'adj'),
    );
}

function movement_status()
{
    return array(
        'done'    => array('label' => 'สำเร็จ',    'tone' => 'ok'),
        'waiting' => array('label' => 'รอรับของ',  'tone' => 'warn'),
        'pending' => array('label' => 'รออนุมัติ', 'tone' => 'warn'),
    );
}

function movement_type_of($key)
{
    $t = movement_types();
    return isset($t[$key]) ? $t[$key] : array('label' => $key, 'tone' => 'adj');
}

function movement_status_of($key)
{
    $s = movement_status();
    return isset($s[$key]) ? $s[$key] : array('label' => $key, 'tone' => 'ok');
}

/* รูปทรงของกราฟ 12 เดือน (สัดส่วนเทียบเดือนล่าสุด) */
function stock_trend_shape()
{
    return array(0.78, 0.82, 0.86, 0.81, 0.88, 0.92, 0.87, 0.94, 0.97, 0.91, 0.96, 1.00);
}

function stock_trend_months()
{
    return array('ต.ค.', 'พ.ย.', 'ธ.ค.', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.');
}

/* ==========================================================
   ฟังก์ชันสรุปข้อมูล — $branch = รหัสสาขา หรือ 'ALL'
   ========================================================== */

/* ---------- ยอดที่ขยับระหว่างวัน (เก็บใน session) ----------
   ยังไม่มีฐานข้อมูล จึงจำ "ส่วนต่าง" ของแต่ละสาขา/SKU ไว้ใน session
   $_SESSION['stock_adj'][ รหัสสาขา ][ SKU ] = จำนวนที่บวก/ลบจากยอดตั้งต้น
   ระบบจริง: ยอดนี้มาจากตาราง stock_move                                */

function stock_adj_get($code, $sku)
{
    return isset($_SESSION['stock_adj'][$code][$sku]) ? (int) $_SESSION['stock_adj'][$code][$sku] : 0;
}

function stock_adj_add($code, $sku, $delta)
{
    if (!isset($_SESSION['stock_adj'][$code])) {
        $_SESSION['stock_adj'][$code] = array();
    }
    $_SESSION['stock_adj'][$code][$sku] = stock_adj_get($code, $sku) + (int) $delta;
}

/** ยอดคงเหลือจริงตอนนี้ = ยอดตั้งต้น + ส่วนต่างใน session */
function product_qty($p, $branch)
{
    if ($branch === 'ALL') {
        $sum = 0;
        foreach ($p['stock'] as $code => $n) {
            $sum += (int) $n + stock_adj_get($code, $p['sku']);
        }
        return $sum;
    }
    $base = isset($p['stock'][$branch]) ? (int) $p['stock'][$branch] : 0;
    return $base + stock_adj_get($branch, $p['sku']);
}

/** ราคาขายต่อหน่วย (เดโมคิดจากต้นทุน + กำไร แล้วปัดให้ลงตัว 5 บาท) */
function product_price($p)
{
    if (isset($p['price'])) {
        return (float) $p['price'];                  // ราคาตั้งจริงจากร้าน
    }
    $raw = $p['cost'] * 1.4;
    $r   = round($raw / 5) * 5;
    return $r > 0 ? $r : 5;
}

/** สถานะของสินค้าในสาขาหนึ่ง */
function branch_status($p, $code)
{
    $qty = product_qty($p, $code);
    if ($qty <= 0) {
        return 'out';
    }
    return $qty <= $p['reorder'] ? 'low' : 'ok';
}

/** ค้นสินค้าจาก SKU */
function product_by_sku($sku)
{
    foreach (demo_products() as $p) {
        if ($p['sku'] === $sku) {
            return $p;
        }
    }
    return null;
}

/**
 * สถานะรวมของสินค้า
 * - ดูรายสาขา: เทียบยอดสาขานั้นกับจุดสั่งซื้อ
 * - ดูทุกสาขา: ใช้สถานะที่แย่ที่สุดในบรรดาสาขาทั้งหมด
 */
function product_status($p, $branch)
{
    if ($branch !== 'ALL') {
        return branch_status($p, $branch);
    }
    $worst = 'ok';
    foreach (array_keys(demo_branches()) as $code) {
        $st = branch_status($p, $code);
        if ($st === 'out') {
            return 'out';
        }
        if ($st === 'low') {
            $worst = 'low';
        }
    }
    return $worst;
}

/** สาขาที่วิกฤตที่สุดของสินค้านี้ (ใช้ตอนดูทุกสาขา) */
function worst_branch($p)
{
    $best      = null;
    $bestRatio = INF;
    foreach (array_keys(demo_branches()) as $code) {
        $qty   = isset($p['stock'][$code]) ? $p['stock'][$code] : 0;
        $ratio = $p['reorder'] > 0 ? $qty / $p['reorder'] : 0;
        if ($ratio < $bestRatio) {
            $bestRatio = $ratio;
            $best      = $code;
        }
    }
    if ($best === null) {
        $keys = array_keys(demo_branches());
        $best = $keys[0];
    }
    return $best;
}

function stock_summary($branch)
{
    $items = 0;
    $value = 0.0;
    $low   = 0;
    $out   = 0;

    foreach (demo_products() as $p) {
        $items++;
        $value += product_qty($p, $branch) * $p['cost'];
        $st = product_status($p, $branch);
        if ($st === 'low') {
            $low++;
        } elseif ($st === 'out') {
            $out++;
        }
    }

    return array('items' => $items, 'value' => $value, 'low' => $low, 'out' => $out);
}

/** สินค้าที่ถึงจุดสั่งซื้อหรือหมด เรียงจากวิกฤตที่สุด */
function low_stock_products($branch, $limit = 6)
{
    $rows = array();
    foreach (demo_products() as $p) {
        $st = product_status($p, $branch);
        if ($st === 'ok') {
            continue;
        }
        $code    = $branch === 'ALL' ? worst_branch($p) : $branch;
        $qty     = isset($p['stock'][$code]) ? (int) $p['stock'][$code] : 0;
        $reorder = $p['reorder'];
        $rows[]  = array(
            'product' => $p,
            'branch'  => $code,
            'qty'     => $qty,
            'reorder' => $reorder,
            'status'  => $st,
            'ratio'   => $reorder > 0 ? $qty / $reorder : 0,
        );
    }
    usort($rows, 'compare_low_ratio');

    return array_slice($rows, 0, $limit);
}

function compare_low_ratio($a, $b)
{
    if ($a['ratio'] == $b['ratio']) {
        return 0;
    }
    return $a['ratio'] < $b['ratio'] ? -1 : 1;
}

function recent_movements($branch, $limit = 7)
{
    $rows = array();
    foreach (demo_movements() as $m) {
        if ($branch === 'ALL' || $m['branch'] === $branch || $m['to'] === $branch) {
            $rows[] = $m;
        }
    }
    return array_slice($rows, 0, $limit);
}

function pending_tasks($branch)
{
    $rows = array();
    foreach (demo_movements() as $m) {
        if ($m['status'] === 'done') {
            continue;
        }
        if ($branch === 'ALL' || $m['branch'] === $branch || $m['to'] === $branch) {
            $rows[] = $m;
        }
    }
    return $rows;
}

/** ชุดข้อมูลกราฟ 12 เดือน — เดือนล่าสุดเท่ากับมูลค่าสต๊อกปัจจุบันเสมอ */
function stock_trend($branch)
{
    $sum     = stock_summary($branch);
    $current = $sum['value'];
    $months  = stock_trend_months();
    $out     = array();

    foreach (stock_trend_shape() as $i => $ratio) {
        $out[] = array(
            'month' => isset($months[$i]) ? $months[$i] : '',
            'value' => round($current * $ratio),
        );
    }
    return $out;
}

/** สาขาที่ผู้ใช้คนนี้เลือกดูได้ */
function visible_branches($user)
{
    $all = demo_branches();
    if ($user['role'] === 'admin') {
        return array_merge(
            array('ALL' => array('name' => 'ทุกสาขา', 'short' => 'ทุกสาขา')),
            $all
        );
    }
    $code = $user['branch'];
    return array($code => $all[$code]);
}

function resolve_branch($user, $requested)
{
    $allowed = array_keys(visible_branches($user));
    if ($requested !== null && in_array($requested, $allowed, true)) {
        return $requested;
    }
    return $user['role'] === 'admin' ? 'ALL' : $user['branch'];
}

function branch_label($code)
{
    return $code === 'ALL' ? 'ทุกสาขา' : branch_name($code);
}

function money($n)
{
    return number_format($n, 0);
}

/** ย่อตัวเลขให้อ่านง่ายบนการ์ด เช่น 2,847,500 → 2.85 ล. */
function money_short($n)
{
    if ($n >= 1000000) {
        return number_format($n / 1000000, 2) . ' ล.';
    }
    if ($n >= 1000) {
        return number_format($n / 1000, 1) . ' พ.';
    }
    return number_format($n, 0);
}

/* ==========================================================
   ผลงานรายบุคคล — ใช้ในหน้า "ภาพรวมของฉัน" ของพนักงาน
   ----------------------------------------------------------
   เดโมสร้างตัวเลขแบบคงที่จากชื่อผู้ใช้ + วันที่ (ค่าเดิมทุกครั้งที่เปิด)
   ระบบจริง: แทนด้วย SELECT จากตาราง stock_move ตาม created_by + วันที่
   ========================================================== */

function staff_seed($username, $ts)
{
    return abs(crc32($username . '|' . date('Ymd', $ts)));
}

/** วันหยุดประจำสัปดาห์ของแต่ละคน (คนละวัน) */
function staff_dayoff($username)
{
    return abs(crc32($username)) % 7;      // 0 = อาทิตย์
}

/** ผลงานของพนักงานหนึ่งคนในหนึ่งวัน */
function staff_day_stat($username, $ts)
{
    $off = ((int) date('w', $ts) === staff_dayoff($username));
    if ($off) {
        return array('off' => true, 'docs' => 0, 'items' => 0, 'qty' => 0);
    }
    $s     = staff_seed($username, $ts);
    $docs  = 4 + ($s % 9);                               // 4–12 เอกสาร
    $items = $docs * (3 + (($s >> 4) % 5));              // 3–7 รายการ/เอกสาร
    $qty   = $items * (2 + (($s >> 9) % 8));             // 2–9 ชิ้น/รายการ

    return array('off' => false, 'docs' => $docs, 'items' => $items, 'qty' => $qty);
}

/** ผลงานย้อนหลัง n วัน (เรียงจากเก่าไปใหม่ วันสุดท้ายคือวันนี้) */
function staff_recent_days($username, $n = 7)
{
    $out = array();
    for ($i = $n - 1; $i >= 0; $i--) {
        $ts  = strtotime('-' . $i . ' day');
        $row = staff_day_stat($username, $ts);
        $row['ts'] = $ts;
        $out[] = $row;
    }
    return $out;
}

/** รวมผลงานตั้งแต่วันที่ 1 ของเดือนถึงวันนี้ */
function staff_month_days($username)
{
    $out   = array();
    $today = (int) date('j');
    for ($d = 1; $d <= $today; $d++) {
        $ts  = mktime(0, 0, 0, (int) date('n'), $d, (int) date('Y'));
        $row = staff_day_stat($username, $ts);
        $row['ts'] = $ts;
        $out[] = $row;
    }
    return $out;
}

function staff_sum($rows)
{
    $t = array('docs' => 0, 'items' => 0, 'qty' => 0, 'days' => 0);
    foreach ($rows as $r) {
        if ($r['off']) {
            continue;
        }
        $t['docs']  += $r['docs'];
        $t['items'] += $r['items'];
        $t['qty']   += $r['qty'];
        $t['days']++;
    }
    return $t;
}

/** เป้าประจำวัน (ชิ้น) — ระบบจริงตั้งค่าได้รายคน/รายสาขา */
function staff_goal($username)
{
    return 300 + (abs(crc32($username)) % 5) * 50;       // 300–500 ชิ้น (ร้านอุปกรณ์มือถือ)
}

/** แบ่งงานวันนี้ตามประเภทเอกสาร */
function staff_work_types($username)
{
    $today = staff_day_stat($username, time());
    $s     = staff_seed($username, time());
    $keys  = array('receive', 'issue', 'count');
    $w     = array(
        30 + ($s % 20),
        20 + (($s >> 3) % 20),
        5  + (($s >> 9) % 10),
    );
    $total = array_sum($w);
    $out   = array();
    foreach ($keys as $i => $k) {
        $out[] = array(
            'type' => $k,
            'qty'  => (int) round($today['qty'] * $w[$i] / $total),
            'pct'  => (int) round(100 * $w[$i] / $total),
        );
    }
    return $out;
}

/** สินค้าที่พนักงานคนนี้แตะบ่อยที่สุดวันนี้ */
function staff_top_products($username, $limit = 4)
{
    $p   = demo_products();
    $n   = count($p);
    $s   = staff_seed($username, time());
    $out = array();
    for ($i = 0; $i < $limit; $i++) {
        $idx   = ($s >> ($i * 3)) % $n;
        $out[] = array(
            'product' => $p[$idx],
            'qty'     => 18 + (($s >> ($i * 5)) % 120),
        );
    }
    return $out;
}

/** อันดับผลงานวันนี้ของพนักงานในสาขาเดียวกัน */
function branch_rank_today($branchCode)
{
    $rows = array();
    foreach (demo_users() as $uname => $u) {
        if ($branchCode !== 'ALL' && $u['branch'] !== $branchCode) {
            continue;
        }
        $d      = staff_day_stat($uname, time());
        $rows[] = array(
            'username' => $uname,
            'name'     => $u['name'],
            'branch'   => $u['branch'],
            'off'      => $d['off'],
            'docs'     => $d['docs'],
            'qty'      => $d['qty'],
        );
    }
    usort($rows, 'compare_rank_qty');
    return $rows;
}

function compare_rank_qty($a, $b)
{
    if ($a['qty'] == $b['qty']) {
        return 0;
    }
    return $a['qty'] > $b['qty'] ? -1 : 1;
}

/** ชื่อวันแบบสั้น เช่น "อา 21" */
function short_day($ts)
{
    $d = array('อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส');
    return $d[(int) date('w', $ts)] . ' ' . (int) date('j', $ts);
}

function thai_month_short($ts)
{
    $m = array('', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
               'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.');
    return $m[(int) date('n', $ts)];
}

function thai_date_full($ts)
{
    $d = array('อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์');
    return $d[(int) date('w', $ts)] . ' ' . (int) date('j', $ts) . ' ' . thai_month_short($ts);
}
