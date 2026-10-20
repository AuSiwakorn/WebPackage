<?php
/**
 * FILE: admweb/aowebdata/modules/stock/function.php
 * ROLE: helper ของโมดูล stock ฝั่งหลังบ้าน — admweb (index.php / doAjax.php) โหลดให้อัตโนมัติเมื่อเปิดหน้าของโมดูลนี้
 * DEPENDS: api.php (function ทั้งหมดของ AOSTOCK — ฝั่งหลังบ้าน admweb ไม่ได้โหลด api.php ให้เอง)
 * TABLES: -
 * TODO:
 *   - [x] ประวัติการแก้ไขดู docs/CHANGELOG.md
 */
require_once __DIR__ . '/api.php';

// class DB ของ admweb เชื่อมต่อด้วย charset=utf8 — sdb() ตั้ง utf8mb4 ให้ (ไม่แก้ไฟล์ของ admweb · อัปเกรดได้ไม่ทับ)
sdb();
