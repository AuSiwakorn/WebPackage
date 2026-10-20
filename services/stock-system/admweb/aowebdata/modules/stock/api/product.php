<?php
/**
 * FILE: admweb/aowebdata/modules/stock/api/product.php
 * ROLE: สินค้า · หมวดสินค้า · รูปสินค้า · ยอดคงเหลือ / สถานะสต๊อก · ตัวช่วยของหน้าสินค้าในสต๊อกและหน้าเลือกสินค้า
 * DEPENDS: api.php (ตัวโหลด — ห้าม include ตรง)
 * TABLES: ao_stock_product, ao_stock_category, ao_stock_balance, ao_stock_sale / sale_item + ao_stock_receive / receive_item (product_flow_stats)
 * TODO:
 *   - [x] ประวัติการแก้ไขดู docs/CHANGELOG.md
 * ⚠ โหลดทุก request — มีได้แค่ define() + function (ห้าม echo / header / query ตอนโหลด)
 */

/* ---------- สินค้า: SKU ตั้งแล้วเปลี่ยนไม่ได้ (เอกสารเก็บ snapshot) · เลิกขาย = is_active 0 · ยอด = ao_stock_balance ---------- */

/** ทุกแถวของ ao_stock_product (+ ชื่อหมวด) — array( SKU => แถว ) เรียงตามหมวด แล้วตาม SKU */
function product_db_rows($reset = false, $peek = false)
{
    static $rows = null;
    if ($peek) {
        return $rows !== null;
    }
    if ($reset) {
        $rows = null;
        return array();
    }
    if ($rows === null) {
        $rows = array();
        $sql  = 'SELECT p.*, c.name AS cat_name FROM ' . sdb_tb('product') . ' p'
              . ' LEFT JOIN ' . sdb_tb('category') . ' c ON c.cate_id = p.cate_id'
              . ' ORDER BY (c.sort IS NULL), c.sort, c.name, p.sku';
        foreach (sdb_rows($sql) as $r) {
            $rows[$r['sku']] = $r;
        }
    }
    return $rows;
}

/** ยอดคงเหลือจาก ao_stock_balance — array( product_id => array( รหัสสาขา => จำนวน ) ) */
function product_balance_rows($reset = false)
{
    static $rows = null;
    if ($reset) {
        $rows = null;
        return array();
    }
    if ($rows === null) {
        $rows = array();
        $sql  = 'SELECT b.code, s.product_id, s.qty FROM ' . sdb_tb('balance') . ' s'
              . ' JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = s.branch_id';
        foreach (sdb_rows($sql) as $r) {
            $rows[(int) $r['product_id']][$r['code']] = (int) $r['qty'];
        }
    }
    return $rows;
}

/** แถวในฐานข้อมูล → รูปแบบสินค้าที่หน้าเว็บใช้ */
function product_shape($r)
{
    $id  = (int) $r['product_id'];
    if (isset($r['__stock'])) {
        $bal = array($id => $r['__stock']);
    } else {
        $bal = product_balance_rows();
    }
    return array(
        'id'      => $id,
        'sku'     => $r['sku'],
        'barcode' => (string) $r['barcode'],
        'name'    => $r['name'],
        'cat'     => (string) $r['cat_name'],
        'cate_id' => (int) $r['cate_id'],
        'unit'    => $r['unit'],
        'cost'    => (float) $r['cost_price'],
        'price'   => (float) $r['sell_price'],
        'reorder' => (int) $r['reorder_point'],
        'image'   => (string) $r['image'],
        'active'  => ((int) $r['is_active'] === 1),
        'stock'   => isset($bal[$id]) ? $bal[$id] : array(),
    );
}

/** สินค้าที่ขายอยู่ (หรือทั้งหมดรวมที่เลิกขาย) — รูปแบบเดียวกับ product_shape */
function products_list($withInactive = false)
{
    $out = array();
    foreach (product_db_rows() as $r) {
        if ($withInactive || (int) $r['is_active'] === 1) {
            $out[] = product_shape($r);
        }
    }
    return $out;
}

/** ล้าง cache ของสินค้า + ยอด หลังเขียน */
function product_db_reset()
{
    product_db_rows(true);
    product_balance_rows(true);
    Stock_getProductRowBySku('', true);
}

/** แถวสินค้าตัวเดียว + ยอดทุกสาขา · null = ไม่มี · false = ครบเพดาน (ให้โหลดทั้งร้าน) */
function Stock_getProductRowBySku($sku, $reset = false)
{
    $one = &Stock_getProductRowCache();
    if ($reset) {
        $one = array();
        return null;
    }
    $sku = (string) $sku;
    if (!array_key_exists($sku, $one)) {
        if (count($one) >= 24) {
            return false;
        }
        Stock_prefetchProducts(array($sku));
    }
    return $one[$sku];
}

/** ที่เก็บแถวสินค้าที่ดึงทีละตัว / ทีละชุด ต่อ request — SKU => แถว (มี __stock) หรือ null (ไม่มี SKU นี้) */
function &Stock_getProductRowCache()
{
    static $cache = array();
    return $cache;
}

/** ดึงสินค้าหลายตัวในคิวรีเดียวเก็บไว้ให้ product_by_sku (ใช้ก่อนวนรายการ) */
function Stock_prefetchProducts($skus)
{
    if (product_db_rows(false, true)) {
        return;
    }
    $one  = &Stock_getProductRowCache();
    $need = array();
    foreach ($skus as $s) {
        $s = (string) $s;
        if (!array_key_exists($s, $one)) {
            $need[$s] = true;
        }
    }
    if (!$need) {
        return;
    }
    if (count($need) > 300) {
        product_db_rows();
        return;
    }
    $need = array_keys($need);
    $ph   = implode(',', array_fill(0, count($need), '?'));
    $byId = array();
    foreach ($need as $s) {
        $one[$s] = null;
    }
    $sql = 'SELECT p.*, c.name AS cat_name FROM ' . sdb_tb('product') . ' p'
         . ' LEFT JOIN ' . sdb_tb('category') . ' c ON c.cate_id = p.cate_id WHERE p.sku IN (' . $ph . ')';
    foreach (sdb_rows($sql, $need) as $r) {
        $r['__stock'] = array();
        $one[$r['sku']] = $r;
        $byId[(int) $r['product_id']] = $r['sku'];
    }
    if ($byId) {
        $sql = 'SELECT b.code, s.product_id, s.qty FROM ' . sdb_tb('balance') . ' s JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = s.branch_id'
             . ' WHERE s.product_id IN (' . implode(',', array_keys($byId)) . ')';
        foreach (sdb_rows($sql) as $x) {
            $one[$byId[(int) $x['product_id']]]['__stock'][$x['code']] = (int) $x['qty'];
        }
    }
}

/** ค้นสินค้าที่ขายอยู่แบบแบ่งหน้าใน SQL · คืน array('rows', 'total') */
function Stock_searchPickProducts($branch, $q, $cat, $offset = 0, $limit = PRODUCT_PAGE_SIZE, $only = null, $skip = null)
{
    $where = array('p.is_active = 1');
    $args  = array();
    if ($cat !== '') {
        $where[] = 'c.name = ?';
        $args[]  = $cat;
    }
    $q = trim((string) $q);
    if ($q !== '') {
        /* LOWER + utf8mb4_bin = ไม่สนตัวพิมพ์แต่ไม่มองข้ามวรรณยุกต์ · บาร์โค้ดต้องตรงทั้งรหัส */
        $like    = '%' . addcslashes(mb_strtolower($q, 'UTF-8'), '\\%_') . '%';
        $where[] = '(LOWER(p.name) COLLATE utf8mb4_bin LIKE ? OR LOWER(p.sku) COLLATE utf8mb4_bin LIKE ? OR p.barcode = ?)';
        $args[]  = $like;
        $args[]  = $like;
        $args[]  = preg_replace('/\s+/', '', $q);
    }
    foreach (array('IN' => $only, 'NOT IN' => $skip) as $op => $skus) {
        if ($skus === null) {
            continue;
        }
        if (!$skus) {
            if ($op === 'IN') {
                return array('rows' => array(), 'total' => 0);
            }
            continue;
        }
        $where[] = 'p.sku ' . $op . ' (' . implode(',', array_fill(0, count($skus), '?')) . ')';
        $args    = array_merge($args, array_map('strval', array_values($skus)));
    }
    $cond  = ' FROM ' . sdb_tb('product') . ' p LEFT JOIN ' . sdb_tb('category') . ' c ON c.cate_id = p.cate_id'
           . ' WHERE ' . implode(' AND ', $where);
    $total = (int) sdb_val('SELECT COUNT(*)' . $cond, $args);
    $rows  = array();
    if ($limit <= 0 || $total <= $offset) {
        return array('rows' => $rows, 'total' => $total);
    }

    /* ยอดของสาขานี้ (หรือรวมทุกสาขาที่เปิดใช้งาน) */
    if ($branch === 'ALL') {
        $ids = array();
        foreach (branches_active() as $b) {
            $ids[] = (int) $b['id'];
        }
        $bal = $ids ? '(SELECT product_id, SUM(qty) AS qty FROM ' . sdb_tb('balance') . ' WHERE branch_id IN (' . implode(',', $ids) . ') GROUP BY product_id)'
                    : '(SELECT 0 AS product_id, 0 AS qty)';
        $on  = '';
        $pre = array();
    } else {
        $all = branches_all();
        $bal = sdb_tb('balance');
        $on  = ' AND s.branch_id = ?';
        $pre = array(isset($all[$branch]) ? (int) $all[$branch]['id'] : 0);
    }
    $sql = 'SELECT p.*, c.name AS cat_name, COALESCE(s.qty, 0) AS b_qty FROM ' . sdb_tb('product') . ' p'
         . ' LEFT JOIN ' . sdb_tb('category') . ' c ON c.cate_id = p.cate_id'
         . ' LEFT JOIN ' . $bal . ' s ON s.product_id = p.product_id' . $on
         . ' WHERE ' . implode(' AND ', $where)
         . ' ORDER BY (c.sort IS NULL), c.sort, c.name, p.sku LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;
    foreach (sdb_rows($sql, array_merge($pre, $args)) as $r) {
        $r['__stock'] = ($branch === 'ALL') ? array() : array($branch => (int) $r['b_qty']);
        $p = product_shape($r);
        $p['qty']   = (int) $r['b_qty'];
        $p['price'] = product_price($p);
        $rows[]     = $p;
    }
    return array('rows' => $rows, 'total' => $total);
}

/** หน่วยที่เคยใช้ (ไว้เติมในช่องหน่วยของฟอร์มสินค้า) */
function product_units()
{
    $out = array('ชิ้น' => true);
    foreach (product_db_rows() as $r) {
        $out[$r['unit']] = true;
    }
    return array_keys($out);
}

/** ตรวจค่าจากฟอร์มสินค้า — คืน array(ค่า, ข้อผิดพลาด) · $isNew = ตรวจ SKU ด้วย */
function product_read_form($isNew, $sku)
{
    $txt = function ($k, $max) {
        $v = isset($_POST[$k]) && is_string($_POST[$k]) ? trim(preg_replace('/\s+/u', ' ', $_POST[$k])) : '';
        return mb_substr($v, 0, $max, 'UTF-8');
    };
    $num = function ($k) {
        $v = isset($_POST[$k]) && is_string($_POST[$k]) ? str_replace(',', '', trim($_POST[$k])) : '';
        return ($v === '') ? 0.0 : (is_numeric($v) ? (float) $v : -1.0);
    };
    $d = array(
        'sku'     => $isNew ? strtoupper($txt('sku', 40)) : $sku,
        'name'    => $txt('name', 200),
        'cat'     => $txt('cat', 100),
        'unit'    => $txt('unit', 20),
        'barcode' => preg_replace('/\s+/', '', $txt('barcode', 40)),
        'cost'    => round($num('cost'), 2),
        'price'   => round($num('price'), 2),
        'reorder' => (int) $num('reorder'),
    );
    $rows = product_db_rows();
    $cats = cat_registry();
    if ($isNew && !preg_match('/^[A-Z0-9][A-Z0-9._-]{0,39}$/', $d['sku'])) {
        return array($d, 'SKU ใช้ได้เฉพาะ A–Z 0–9 . _ - (ขึ้นต้นด้วยตัวอักษรหรือตัวเลข)');
    }
    if ($isNew && isset($rows[$d['sku']])) {
        return array($d, 'SKU ' . $d['sku'] . ' มีอยู่แล้ว' . ((int) $rows[$d['sku']]['is_active'] === 1 ? '' : ' (เลิกขายแล้ว — เปิดขายใหม่ได้ที่หน้าแก้ไข)'));
    }
    if ($d['name'] === '') {
        return array($d, 'กรุณากรอกชื่อสินค้า');
    }
    if (!isset($cats[$d['cat']])) {
        return array($d, 'กรุณาเลือกหมวดสินค้า');
    }
    if ($d['unit'] === '') {
        $d['unit'] = 'ชิ้น';
    }
    if ($d['cost'] < 0 || $d['price'] < 0 || $d['reorder'] < 0) {
        return array($d, 'ทุน / ราคาขาย / จุดสั่งซื้อ ต้องเป็นตัวเลขไม่ติดลบ');
    }
    if ($d['cost'] > 9999999 || $d['price'] > 9999999 || $d['reorder'] > 1000000) {
        return array($d, 'ตัวเลขมากเกินไป');
    }
    if ($d['barcode'] !== '') {
        foreach ($rows as $s => $r) {
            if ($s !== $d['sku'] && (string) $r['barcode'] === $d['barcode']) {
                return array($d, 'บาร์โค้ด ' . $d['barcode'] . ' ซ้ำกับ ' . $s . ' ' . $r['name']);
            }
        }
    }
    return array($d, '');
}

/** เพิ่ม / แก้สินค้า — $d จาก product_read_form · คืน product_id */
function product_save($isNew, $d)
{
    $cats = cat_registry();
    $row  = array(
        'name'          => $d['name'],
        'cate_id'       => $cats[$d['cat']]['id'],
        'unit'          => $d['unit'],
        'barcode'       => ($d['barcode'] !== '') ? $d['barcode'] : null,
        'cost_price'    => $d['cost'],
        'sell_price'    => $d['price'],
        'reorder_point' => $d['reorder'],
    );
    if ($isNew) {
        $row['sku'] = $d['sku'];
        $id = sdb_insert('product', $row);
    } else {
        sdb_update('product', $row, array('sku' => $d['sku']));
        $id = (int) product_db_rows()[$d['sku']]['product_id'];
    }
    product_db_reset();
    return $id;
}

/** SKU อัตโนมัติถัดไป P0001, P0002, … — ต้องเรียกใน sdb_tx */
function Stock_nextProductSku()
{
    do {
        $sku = 'P' . str_pad((string) stock_seq_next(0, 'SKU', '-'), 4, '0', STR_PAD_LEFT);
    } while (sdb_val('SELECT 1 FROM ' . sdb_tb('product') . ' WHERE sku = ?', array($sku)) !== null);
    return $sku;
}

/** เพิ่มสินค้าใหม่ (พนักงานที่มีสิทธิ์ product_add) · คืน array('sku', 'name') หรือ array('error') */
function Stock_doAddProduct($user, $code, $from = 'หน้านำเข้าสินค้า')
{
    if (!can($user, 'product_add')) {
        return array('error' => 'ไม่มีสิทธิ์ “เพิ่มสินค้าใหม่” — ติดต่อผู้จัดการสาขาหรือผู้ดูแลเพื่อเปิดสิทธิ์');
    }
    $auto   = !(isset($_POST['sku_mode']) && $_POST['sku_mode'] === 'manual');
    $newCat = (isset($_POST['cat_new']) && is_string($_POST['cat_new'])) ? trim(preg_replace('/\s+/u', ' ', $_POST['cat_new'])) : '';
    if ($newCat !== '') {
        foreach (array_keys(cat_registry()) as $c) {                     // พิมพ์ชื่อหมวดที่มีอยู่แล้ว = ใช้หมวดนั้น
            if (mb_strtolower($c, 'UTF-8') === mb_strtolower($newCat, 'UTF-8')) {
                $_POST['cat'] = $c;
                $newCat = '';
                break;
            }
        }
    }
    if ($newCat !== '' && !can($user, 'category_add')) {
        return array('error' => 'ไม่มีสิทธิ์เพิ่มหมวดสินค้าใหม่ — เลือกจากหมวดที่มีอยู่ หรือติดต่อผู้จัดการสาขา');
    }
    try {
        return sdb_tx(function () use ($user, $code, $auto, $newCat, $from) {
            if ($newCat !== '') {
                $e = cat_add($newCat, $user, $code);
                if ($e !== '') {
                    throw new InvalidArgumentException($e);
                }
                $_POST['cat'] = $newCat;
            }
            if ($auto) {
                $_POST['sku'] = Stock_nextProductSku();
            }
            product_db_reset();
            list($d, $err) = product_read_form(true, '');
            if ($err !== '') {
                throw new InvalidArgumentException($err);
            }
            product_save(true, $d);
            log_add($code, 'stock', $user, 'เพิ่มสินค้าใหม่ ' . $d['name'], array(
                'SKU'      => $d['sku'] . ($auto ? ' (ตั้งให้อัตโนมัติ)' : ''),
                'หมวด'     => $d['cat'],
                'ราคาขาย'  => money2($d['price']) . ' บาท / ' . $d['unit'],
                'ทุน'      => money2($d['cost']) . ' บาท',
                'เพิ่มโดย' => $user['name'] . ' (จาก' . $from . ')',
            ));
            return array('sku' => $d['sku'], 'name' => $d['name']);
        });
    } catch (InvalidArgumentException $ex) {
        cat_registry(true);                                              // ยกเลิกทั้งชุดแล้ว — ล้าง cache ที่อาจจำหมวดใหม่ไว้
        product_db_reset();
        return array('error' => $ex->getMessage());
    }
}

/** เปิดขาย / เลิกขาย */
function product_set_active($sku, $on)
{
    sdb_update('product', array('is_active' => $on ? 1 : 0), array('sku' => $sku));
    product_db_reset();
}

/** ตรวจรูปสินค้าที่อัปโหลด (ยังไม่เก็บ) · คืน '' = ผ่าน หรือไม่ได้เลือกรูป · ข้อความ = ไม่ผ่าน */
function Stock_checkProductImage($field)
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        return 'อัปโหลดรูปไม่สำเร็จ (ไฟล์อาจใหญ่เกินที่เซิร์ฟเวอร์รับได้)';
    }
    if ($f['size'] > 2 * 1024 * 1024) {
        return 'รูปใหญ่เกิน 2 MB';
    }
    $info = @getimagesize($f['tmp_name']);
    if ($info === false || !in_array($info[2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP), true)) {
        return 'ไฟล์ไม่ใช่รูป JPG / PNG / WEBP';
    }
    return '';
}

/** เก็บรูปสินค้าที่อัปโหลด (แทนรูปเดิม) · คืน array('ok' => path) | array('error') | array() */
function product_image_store($sku, $field, $oldPath)
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return array();
    }
    $e = Stock_checkProductImage($field);
    if ($e !== '') {
        return array('error' => $e);
    }
    $f     = $_FILES[$field];
    $info  = getimagesize($f['tmp_name']);
    $types = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp');
    require_once PATH_PLUGIN . '/uploadfile/UpFile.php';
    $up    = new UpFile();
    $name  = preg_replace('/[^A-Za-z0-9._-]/', '', $sku) . '-' . date('YmdHis') . '.' . $types[$info[2]];
    $saved = $up->uploadStandard(array($types[$info[2]]), $f['tmp_name'], $name, 'stock/products',
                                 pathinfo($name, PATHINFO_FILENAME), ($oldPath !== '') ? $oldPath : null);
    if ($saved === false) {
        return array('error' => 'บันทึกรูปไม่สำเร็จ — ตรวจสิทธิ์เขียนโฟลเดอร์ uploads/stock');
    }
    sdb_update('product', array('image' => $saved), array('sku' => $sku));
    product_db_reset();
    return array('ok' => $saved);
}

/** ลบรูปสินค้า (ไฟล์ + ค่าในฐานข้อมูล) */
function product_image_remove($sku, $oldPath)
{
    if ($oldPath !== '') {
        $full = PATH_UPLOAD . '/' . ltrim($oldPath, '/');
        if (strpos(realpath(dirname($full)) ?: '', realpath(PATH_UPLOAD . '/stock/products') ?: '#') === 0 && is_file($full)) {
            @unlink($full);
        }
    }
    sdb_update('product', array('image' => ''), array('sku' => $sku));
    product_db_reset();
}

/* ---------- สรุปข้อมูล — $branch = รหัสสาขา หรือ 'ALL' ---------- */

/** ยอดคงเหลือของสินค้าในสาขา จาก ao_stock_balance (ไม่มีแถว = 0) */
function product_base_qty($p, $code)
{
    return isset($p['stock'][$code]) ? (int) $p['stock'][$code] : 0;
}

/** ยอดคงเหลือตอนนี้ (ao_stock_balance) — $branch = รหัสสาขา หรือ 'ALL' (รวมสาขาที่เปิดใช้งาน) */
function product_qty($p, $branch)
{
    if ($branch === 'ALL') {
        $sum = 0;
        foreach (array_keys(branches_active()) as $code) {     // รวมสาขาที่เพิ่มใหม่ด้วย
            $sum += product_base_qty($p, $code);
        }
        return $sum;
    }
    return product_base_qty($p, $branch);
}

/** ราคาขายต่อหน่วย = ราคาที่ร้านตั้ง (ao_stock_product.sell_price) */
function product_price($p)
{
    return isset($p['price']) ? (float) $p['price'] : 0.0;
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

/** ค้นสินค้าจาก SKU (รวมที่เลิกขาย — เอกสารเก่ายังอ้างถึงได้) */
function product_by_sku($sku)
{
    $sku = (string) $sku;
    if (!product_db_rows(false, true)) {
        $r = Stock_getProductRowBySku($sku);
        if ($r !== false) {
            return ($r === null) ? null : product_shape($r);
        }
    }
    $rows = product_db_rows();
    return isset($rows[$sku]) ? product_shape($rows[$sku]) : null;
}

/** สถานะรวมของสินค้า (ทุกสาขา = สถานะที่แย่ที่สุด) */
function product_status($p, $branch)
{
    if ($branch !== 'ALL') {
        return branch_status($p, $branch);
    }
    $worst = 'ok';
    foreach (array_keys(branches_active()) as $code) {
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
    foreach (array_keys(branches_active()) as $code) {
        $qty   = product_qty($p, $code);
        $ratio = $p['reorder'] > 0 ? $qty / $p['reorder'] : 0;
        if ($ratio < $bestRatio) {
            $bestRatio = $ratio;
            $best      = $code;
        }
    }
    if ($best === null) {
        $keys = array_keys(branches_active());
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

    foreach (products_list() as $p) {
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
    foreach (products_list() as $p) {
        $st = product_status($p, $branch);
        if ($st === 'ok') {
            continue;
        }
        $code    = $branch === 'ALL' ? worst_branch($p) : $branch;
        $qty     = product_qty($p, $code);
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

/** หมวดสินค้าทั้งหมด (ไว้ทำปุ่มกรอง) */

/** ชื่อหมวดสินค้าทั้งหมด (เรียงตามลำดับในทะเบียน) — ใช้กับตัวกรองหมวดทุกหน้า */
function product_cats()
{
    return array_keys(cat_registry());
}

/* ---------- หมวดสินค้า: ใช้ร่วมทุกสาขา · ลบได้เฉพาะหมวดที่ไม่มีสินค้าอ้างถึง ---------- */

/** ทะเบียนหมวด: ชื่อ => array(id, count จำนวนสินค้า (รวมเลิกขาย), added เพิ่มโดยผู้ใช้ไหม, by, at) */
function cat_registry($reset = false)
{
    static $out = null;
    if ($reset) {
        $out = null;
        return array();
    }
    if ($out !== null) {
        return $out;
    }
    $names = array();
    foreach (users_all() as $u) {
        $names[$u['id']] = $u['name'];
    }
    $out = array();
    $sql = 'SELECT c.cate_id, c.name, c.created_by, c.add_date, COUNT(p.product_id) AS n FROM ' . sdb_tb('category') . ' c'
         . ' LEFT JOIN ' . sdb_tb('product') . ' p ON p.cate_id = c.cate_id'
         . ' WHERE c.is_active = 1 GROUP BY c.cate_id, c.name, c.created_by, c.add_date, c.sort ORDER BY c.sort, c.name';
    foreach (sdb_rows($sql) as $r) {
        $by = (int) $r['created_by'];
        $out[$r['name']] = array(
            'id'    => (int) $r['cate_id'],
            'count' => (int) $r['n'],
            'added' => ($by > 0),
            'by'    => ($by > 0 && isset($names[$by])) ? $names[$by] : '',
            'at'    => substr((string) $r['add_date'], 0, 16),
        );
    }
    return $out;
}

/** สินค้าในหมวด (เฉพาะที่ขายอยู่) */
function cat_products($name)
{
    $out = array();
    foreach (products_list() as $p) {
        if ($p['cat'] === $name) {
            $out[] = $p;
        }
    }
    return $out;
}

/** เพิ่มหมวด — คืนข้อความผิดพลาด ('' = สำเร็จ) */
function cat_add($name, $user, $code)
{
    $name = trim(preg_replace('/\s+/u', ' ', $name));
    if ($name === '') {
        return 'กรุณากรอกชื่อหมวด';
    }
    if (mb_strlen($name, 'UTF-8') > 50) {
        return 'ชื่อหมวดยาวเกินไป (ไม่เกิน 50 ตัวอักษร)';
    }
    foreach (array_keys(cat_registry()) as $c) {
        if (mb_strtolower($c, 'UTF-8') === mb_strtolower($name, 'UTF-8')) {
            return 'มีหมวด “' . $c . '” อยู่แล้ว';
        }
    }
    sdb_insert('category', array(
        'name'       => $name,
        'sort'       => (int) sdb_val('SELECT MAX(sort) FROM ' . sdb_tb('category')) + 1,
        'created_by' => isset($user['id']) ? (int) $user['id'] : 0,
    ));
    cat_registry(true);
    log_add($code, 'category', $user, 'เพิ่มหมวดสินค้า “' . $name . '”', array('ใช้ได้' => 'ทุกสาขา'));
    return '';
}

/** ลบหมวด — ได้เฉพาะหมวดที่ไม่มีสินค้าอ้างถึง · คืนข้อความผิดพลาด ('' = สำเร็จ) */
function cat_delete($name, $user, $code)
{
    $all = cat_registry();
    if (!isset($all[$name])) {
        return 'ไม่พบหมวดนี้';
    }
    if ($all[$name]['count'] > 0) {
        return 'ลบไม่ได้ เพราะหมวด “' . $name . '” ยังมีสินค้า ' . $all[$name]['count'] . ' รายการ (รวมที่เลิกขาย) — ย้ายสินค้าไปหมวดอื่นก่อน';
    }
    sdb_q('DELETE FROM ' . sdb_tb('category') . ' WHERE cate_id = ?', array($all[$name]['id']));
    cat_registry(true);
    log_add($code, 'category', $user, 'ลบหมวดสินค้า “' . $name . '”', array('สินค้าในหมวด' => '0 รายการ'));
    return '';
}

/** ค้นหาสินค้าสำหรับหน้าขาย */
function sale_products($branch, $q, $cat)
{
    $q   = trim($q);
    $out = array();
    foreach (products_list() as $p) {
        if ($cat !== '' && $p['cat'] !== $cat) {
            continue;
        }
        if ($q !== '' && stripos($p['name'], $q) === false && stripos($p['sku'], $q) === false) {
            continue;
        }
        $p['qty']   = product_qty($p, $branch);
        $p['price'] = product_price($p);
        $out[]      = $p;
    }
    return $out;
}

/** สินค้าเฉพาะ SKU ที่ระบุ (ที่ยังขาย) — วาดการ์ดใบที่เพิ่งแตะ */
function Stock_getPickProducts($branch, $skus)
{
    $out = array();
    Stock_prefetchProducts($skus);                       // คิวรีเดียวทุกใบที่ต้องวาดใหม่ (ช่วง 5)
    foreach (array_unique($skus) as $sku) {
        $p = product_by_sku((string) $sku);
        if ($p === null || !$p['active']) {
            continue;
        }
        $p['qty']   = product_qty($p, $branch);
        $p['price'] = product_price($p);
        $out[]      = $p;
    }
    return $out;
}

/** id ของการ์ดสินค้า (hash ของ SKU) ใช้สลับการ์ดใบเดียวแบบ hx-swap-oob */
function Stock_buildTileId($sku)
{
    return 'g-' . substr(md5((string) $sku), 0, 12);
}

/* ---------- แสดงสินค้าครั้งละ 60 + ปุ่ม “แสดงเพิ่ม” · ?n=N เปิดหน้า · ?from=N htmx ชุดถัดไป ---------- */
if (!defined('PRODUCT_PAGE_SIZE')) {
    define('PRODUCT_PAGE_SIZE', 60);
}

/** ช่วงแถวที่จะวาด — array(แถวเริ่ม, แถวจบ (ไม่รวม), เป็นคำขอ “แสดงเพิ่ม” ไหม) ตาม ?from / ?n ด้านบน */
function Stock_getListRange()
{
    $size = PRODUCT_PAGE_SIZE;
    if (isset($_GET['from']) && is_string($_GET['from']) && ctype_digit($_GET['from'])) {
        $from = min(100000, (int) $_GET['from']);
        return array($from, $from + $size, true);
    }
    $n = (isset($_GET['n']) && is_string($_GET['n']) && ctype_digit($_GET['n'])) ? (int) $_GET['n'] : $size;
    $n = max($size, min(100000, (int) (ceil($n / $size) * $size)));
    return array(0, $n, false);
}

/** ปุ่ม “แสดงเพิ่ม” ท้ายรายการ (แสดงครบแล้ว = ไม่ echo) */
function Stock_renderMoreButton($end, $total, $url, $cols = 0, $tag = 'div')
{
    if ($end >= $total) {
        return;
    }
    $sep  = (strpos($url, '?') === false) ? '?' : '&';
    $body = '<span class="more-t">แสดง <b class="num">' . number_format($end) . '</b> จาก <b class="num">' . number_format($total) . '</b> รายการ'
          . ' · พิมพ์ค้นหาหรือเลือกหมวดเพื่อหาเร็วขึ้น</span>'
          . '<a class="btn btn-ghost btn-sm more-btn" href="' . e($url . $sep . 'n=' . ($end + PRODUCT_PAGE_SIZE)) . '"'
          . ' hx-get="' . e($url . $sep . 'from=' . $end) . '" hx-target="closest .more-row" hx-swap="outerHTML" hx-push-url="false">'
          . 'แสดงเพิ่มอีก ' . number_format(min(PRODUCT_PAGE_SIZE, $total - $end)) . ' รายการ</a>';
    if ($cols > 0) {
        echo '<tr class="more-row"><td colspan="' . (int) $cols . '"><div class="more-in">' . $body . '</div></td></tr>';
    } else {
        $tag = ($tag === 'li') ? 'li' : 'div';
        echo '<' . $tag . ' class="more-row">' . $body . '</' . $tag . '>';
    }
}

/* ---------- ตัวช่วยของหน้าสินค้าในสต๊อก ---------- */

/** URL รูปสินค้า: รูปที่อัปโหลด → assets/products/<SKU>.* → '' (ใช้รูปแทนจากหมวด) */

function product_img($p)
{
    if (!empty($p['image']) && defined('URL_UPLOAD')) {                   // รูปที่อัปโหลด (uploads/stock/products/)
        return URL_UPLOAD . '/' . ltrim($p['image'], '/');
    }
    if (!defined('THEME_DIR')) {
        return '';
    }
    $dir = THEME_DIR . '/assets/products/';                              // รูปที่วางไว้ตาม SKU (themes/aostock/assets/products/)
    foreach (array('jpg', 'jpeg', 'png', 'webp') as $ext) {
        if (is_file($dir . $p['sku'] . '.' . $ext)) {
            return asset_url('products/' . $p['sku'] . '.' . $ext);
        }
    }
    return '';
}

/** ไอคอนและโทนสีประจำหมวด ใช้ตอนที่ยังไม่มีรูปจริง */
function cat_style($cat)
{
    $map = array(
        'เคสมือถือ'          => array('i-phone',   'a'),
        'เคส iPad & Tablet'  => array('i-tablet',  'b'),
        'ฟิล์มมือถือ'         => array('i-shield',  'c'),
        'ฟิล์ม iPad & Tablet' => array('i-shield',  'b'),
        'กระจกเลนส์กล้อง'    => array('i-camera',  'd'),
        'ฟิล์มไฮโดรเจล'       => array('i-shield',  'a'),
        'สายชาร์จ'           => array('i-cable',   'd'),
    );
    if (isset($map[$cat])) {
        return $map[$cat];
    }
    $tones = array('a', 'b', 'c', 'd');
    return array('i-box', $tones[abs(crc32($cat)) % 4]);
}

/** กล่องรูปสินค้า — ใช้ได้ทั้งในตารางและบนการ์ดหน้าขาย */
function thumb_html($p, $extra = '')
{
    $img = product_img($p);
    $sty = cat_style($p['cat']);
    $cls = 'thumb thumb--' . ($img !== '' ? 'img' : $sty[1]) . ($extra !== '' ? ' ' . $extra : '');

    if ($img !== '') {
        return '<span class="' . $cls . '"><img src="' . e($img) . '" alt="" loading="lazy"></span>';
    }
    return '<span class="' . $cls . '" aria-hidden="true">'
         . '<svg class="ico"><use href="#' . $sty[0] . '"/></svg></span>';
}

function stock_status_tabs()
{
    return array(
        ''    => 'ทั้งหมด',
        'low' => 'ใกล้หมด',
        'out' => 'หมดแล้ว',
        'ok'  => 'พอใช้',
    );
}

function stock_sorts()
{
    return array(
        'urgent' => 'ด่วนก่อน (ใกล้หมดขึ้นก่อน)',
        'name'   => 'ชื่อสินค้า ก–ฮ',
        'qty'    => 'คงเหลือมากไปน้อย',
        'qtyasc' => 'คงเหลือน้อยไปมาก',
        'cat'    => 'หมวดสินค้า',
    );
}

function stock_label($st)
{
    if ($st === 'out') { return 'หมดแล้ว'; }
    if ($st === 'low') { return 'ใกล้หมด'; }
    return 'พอใช้';
}

function stock_tone($st)
{
    if ($st === 'out') { return 'out'; }
    if ($st === 'low') { return 'adj'; }
    return 'in';
}

/** ประกอบ query string ของตัวกรอง โดยข้ามค่าที่ว่าง */
function stock_qs($q, $cat, $st, $sort)
{
    $parts = array();
    if ($q !== '')              { $parts[] = 'q='    . rawurlencode($q); }
    if ($cat !== '')            { $parts[] = 'cat='  . rawurlencode($cat); }
    if ($st !== '')             { $parts[] = 'st='   . rawurlencode($st); }
    if ($sort !== 'urgent' && $sort !== '') { $parts[] = 'sort=' . rawurlencode($sort); }
    return $parts ? '?' . implode('&', $parts) : '';
}

/** รายการสินค้าของสาขาหนึ่ง พร้อมสถานะและ pct (ยอดเทียบจุดสั่งซื้อ) */
function stock_rows($code, $q, $cat, $st, $sort)
{
    $q    = trim($q);
    $rows = array();

    foreach (products_list() as $p) {
        if ($cat !== '' && $p['cat'] !== $cat) {
            continue;
        }
        if ($q !== '' && stripos($p['name'], $q) === false && stripos($p['sku'], $q) === false) {
            continue;
        }
        $qty    = product_qty($p, $code);
        $status = branch_status($p, $code);
        if ($st !== '' && $status !== $st) {
            continue;
        }
        $rows[] = array(
            'p'      => $p,
            'qty'    => $qty,
            'status' => $status,
            'pct'    => $p['reorder'] > 0 ? (int) round($qty / $p['reorder'] * 100) : 100,
        );
    }

    usort($rows, stock_sorter($sort));
    return $rows;
}

function stock_sorter($sort)
{
    if ($sort === 'name')   { return 'stock_cmp_name'; }
    if ($sort === 'qty')    { return 'stock_cmp_qty_desc'; }
    if ($sort === 'qtyasc') { return 'stock_cmp_qty_asc'; }
    if ($sort === 'cat')    { return 'stock_cmp_cat'; }
    return 'stock_cmp_urgent';
}

function stock_cmp_name($a, $b)
{
    return strcmp($a['p']['name'], $b['p']['name']);
}

function stock_cmp_qty_desc($a, $b)
{
    if ($a['qty'] === $b['qty']) { return stock_cmp_name($a, $b); }
    return ($a['qty'] < $b['qty']) ? 1 : -1;
}

function stock_cmp_qty_asc($a, $b)
{
    if ($a['qty'] === $b['qty']) { return stock_cmp_name($a, $b); }
    return ($a['qty'] > $b['qty']) ? 1 : -1;
}

function stock_cmp_cat($a, $b)
{
    $c = strcmp($a['p']['cat'], $b['p']['cat']);
    return ($c !== 0) ? $c : stock_cmp_name($a, $b);
}

/** ของที่ใกล้หมดที่สุดขึ้นก่อน แล้วค่อยเรียงตามชื่อ */
function stock_cmp_urgent($a, $b)
{
    if ($a['pct'] === $b['pct']) { return stock_cmp_name($a, $b); }
    return ($a['pct'] > $b['pct']) ? 1 : -1;
}

/** สรุปเฉพาะรายการที่แสดงอยู่ตอนนี้ — การ์ดด้านบนใช้ชุดนี้ ตัวเลขจะได้ตรงกับตาราง */
function stock_view_sum($rows)
{
    $sum = array('skus' => 0, 'qty' => 0, 'low' => 0, 'out' => 0);
    foreach ($rows as $r) {
        $sum['skus']++;
        $sum['qty'] += $r['qty'];
        if ($r['status'] === 'low') { $sum['low']++; }
        if ($r['status'] === 'out') { $sum['out']++; }
    }
    return $sum;
}

/** มีตัวกรองอะไรเปิดอยู่บ้าง — ไว้บอกผู้ใช้ว่าตัวเลขที่เห็นมาจากอะไร */
function stock_filter_words($q, $cat, $st)
{
    $w = array();
    if ($cat !== '') { $w[] = 'หมวด ' . $cat; }
    if ($st !== '')  {
        $tabs = stock_status_tabs();
        $w[]  = isset($tabs[$st]) ? $tabs[$st] : $st;
    }
    if ($q !== '')   { $w[] = 'คำค้น “' . $q . '”'; }
    return $w;
}

/** สรุปทั้งสาขา (ไม่สนตัวกรอง) ไว้โชว์เป็นการ์ดด้านบน */
function stock_branch_sum($code)
{
    $sum = array('skus' => 0, 'qty' => 0, 'low' => 0, 'out' => 0, 'value' => 0);
    foreach (products_list() as $p) {
        $qty = product_qty($p, $code);
        $sum['skus']++;
        $sum['qty']   += $qty;
        $sum['value'] += $qty * product_price($p);
        $status = branch_status($p, $code);
        if ($status === 'low') { $sum['low']++; }
        if ($status === 'out') { $sum['out']++; }
    }
    return $sum;
}

/** ยอดขาย + วันรับเข้าล่าสุดรายสินค้ารายสาขา ย้อนหลัง $days วัน (adm-products.php) */
function product_flow_stats($codes, $days = 30)
{
    $sold  = array();
    $recv  = array();
    $today = strtotime(date('Y-m-d'));
    $codes = array_values($codes);
    if ($codes) {
        /* ขายไป — คิวรีเดียวจาก ao_stock_sale_item (ไม่นับบิลที่ยกเลิก · ยังไม่หักรับคืน) */
        $in  = implode(', ', array_fill(0, count($codes), '?'));
        $sql = 'SELECT b.code, i.sku, SUM(i.qty) AS q FROM ' . sdb_tb('sale_item') . ' i'
             . ' JOIN ' . sdb_tb('sale') . ' s ON s.sale_id = i.sale_id'
             . ' JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = s.branch_id'
             . ' WHERE s.status = \'paid\' AND s.sale_date >= ? AND b.code IN (' . $in . ') GROUP BY b.code, i.sku';
        foreach (sdb_rows($sql, array_merge(array(date('Y-m-d', strtotime('-' . ($days - 1) . ' day', $today))), $codes)) as $r) {
            $sold[$r['sku']][$r['code']] = (int) $r['q'];
        }
    }
    /* รับเข้าล่าสุด — คิวรีเดียวจาก ao_stock_receive (ไม่นับใบที่ยกเลิก) */
    if ($codes) {
        $in  = implode(', ', array_fill(0, count($codes), '?'));
        $sql = 'SELECT b.code, i.sku, MAX(r.doc_date) AS last_date FROM ' . sdb_tb('receive_item') . ' i'
             . ' JOIN ' . sdb_tb('receive') . ' r ON r.receive_id = i.receive_id'
             . ' JOIN ' . sdb_tb('branch') . ' b ON b.branch_id = r.branch_id'
             . ' WHERE r.status = \'posted\' AND r.doc_date >= ? AND b.code IN (' . $in . ') GROUP BY b.code, i.sku';
        $params = array_merge(array(date('Y-m-d', strtotime('-' . ($days - 1) . ' day', $today))), $codes);
        foreach (sdb_rows($sql, $params) as $r) {
            $recv[$r['sku']][$r['code']] = date('Ymd', strtotime($r['last_date']));
        }
    }
    return array('sold' => $sold, 'recv' => $recv);
}
