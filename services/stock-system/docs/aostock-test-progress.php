<?php
/**
 * FILE: docs/aostock-test-progress.php
 * ROLE: เก็บ / อ่านผลการติ๊กของหน้ารายการทดสอบ (docs/aostock-test-checklist.html) บนเซิร์ฟเวอร์ — ทุกคนที่เปิดลิงก์เห็นผลชุดเดียวกัน
 * DEPENDS: - (ไฟล์เดี่ยว ไม่โหลด admweb · ผลตั้งต้นอ่านจาก docs/aostock-test-progress.txt)
 * TABLES: - (เก็บเป็น JSON ในไฟล์ uploads/aostock-test/progress.txt — อยู่นอก docs/ เพื่อไม่ให้การอัป docs/ ครั้งหลังเขียนทับผลที่ติ๊กไว้)
 * TODO:
 *   - [x] GET = ผลล่าสุด · POST {ops:[{id,on,at}], note} = ติ๊ก / เอาติ๊กออกทีละข้อ รวมกับของคนอื่นใต้ flock (2 คนติ๊กพร้อมกันไม่ทับกัน)
 *   - [x] POST {reset:true} / {replace:{checked,note}} = ล้าง / โหลดผลเดิมทับ — สำรองไฟล์เก่าเป็น progress-YYYYMMDD-HHMMSS.txt ก่อน
 *   - [x] ใครมีลิงก์ก็ดูและติ๊กได้ (ผู้ใช้เลือก 2026-10-08) — รับเฉพาะ JSON จากหน้าเดียวกัน · ตรวจรูปแบบเลขข้อ / เวลา / ขนาด
 *   - [ ] เลิกทดสอบแล้วลบ docs/ และ uploads/aostock-test/ ออกจากเซิร์ฟเวอร์
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$app  = 'aostock-test-checklist';
$dir  = dirname(__DIR__) . '/uploads/aostock-test';
$file = $dir . '/progress.txt';
$seed = __DIR__ . '/aostock-test-progress.txt';
$json = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
$now  = gmdate('Y-m-d\TH:i:s') . '.000Z';
$idRe = '/^[A-Z][0-9]{1,3}$/';
$atRe = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,3})?Z$/';

$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
if ($method !== 'GET' && $method !== 'POST') {
	http_response_code(405);
	echo json_encode(array('error' => 'ใช้ได้แค่ GET / POST'), $json);
	exit;
}

$in = null;
if ($method === 'POST') {
	// รับเฉพาะ JSON — เว็บอื่นส่งข้ามโดเมนมาแบบนี้ไม่ได้ถ้าไม่ผ่าน CORS (ไม่ได้เปิดไว้)
	$type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
	if (stripos($type, 'application/json') !== 0) {
		http_response_code(415);
		echo json_encode(array('error' => 'ต้องส่งเป็น JSON'), $json);
		exit;
	}
	if (!empty($_SERVER['HTTP_ORIGIN']) && isset($_SERVER['HTTP_HOST'])
		&& strcasecmp((string) parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST), preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'])) !== 0) {
		http_response_code(403);
		echo json_encode(array('error' => 'ส่งจากหน้าอื่นไม่ได้'), $json);
		exit;
	}
	$raw = file_get_contents('php://input', false, null, 0, 262145);
	$in  = (strlen($raw) <= 262144) ? json_decode($raw, true) : null;
	if (!is_array($in)) {
		http_response_code(400);
		echo json_encode(array('error' => 'ข้อมูลไม่ถูกต้อง หรือใหญ่เกิน 256 KB'), $json);
		exit;
	}
}

if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
	http_response_code(500);
	echo json_encode(array('error' => 'สร้างโฟลเดอร์ uploads/aostock-test ไม่ได้ — ตรวจสิทธิ์เขียนโฟลเดอร์ uploads/'), $json);
	exit;
}
$fp = @fopen($file, 'c+');
if (!$fp) {
	http_response_code(500);
	echo json_encode(array('error' => 'เปิดไฟล์ผลไม่ได้ — ตรวจสิทธิ์เขียนโฟลเดอร์ uploads/aostock-test'), $json);
	exit;
}
flock($fp, $method === 'POST' ? LOCK_EX : LOCK_SH);

// อ่านผลล่าสุด · ไฟล์ยังว่าง (เปิดครั้งแรก) = ใช้ผลตั้งต้นจาก docs/
$text = stream_get_contents($fp);
$st   = json_decode((string) $text, true);
if (!is_array($st) && is_file($seed)) {
	$st = json_decode((string) file_get_contents($seed), true);
}
if (!is_array($st)) {
	$st = array();
}
$checked = array();
if (isset($st['checked']) && is_array($st['checked'])) {
	foreach ($st['checked'] as $k => $v) {
		if (preg_match($idRe, (string) $k) && is_string($v) && preg_match($atRe, $v)) {
			$checked[$k] = $v;
		}
	}
}
$state = array(
	'app'       => $app,
	'about'     => 'ผลการติ๊กของหน้า docs/aostock-test-checklist.html — แก้ผ่านหน้านั้น ไม่ต้องแก้ไฟล์นี้เอง',
	'rev'       => isset($st['rev']) ? (int) $st['rev'] : 0,
	'updatedAt' => (isset($st['updatedAt']) && is_string($st['updatedAt'])) ? $st['updatedAt'] : null,
	'checked'   => $checked,
	'note'      => (isset($st['note']) && is_string($st['note'])) ? $st['note'] : '',
	'noteAt'    => (isset($st['noteAt']) && is_string($st['noteAt'])) ? $st['noteAt'] : null,
);

if ($method === 'POST') {
	$bad = '';
	$backup = false;
	if (!empty($in['reset'])) {
		$backup = true;
		$state['checked'] = array();
		$state['note']    = '';
		$state['noteAt']  = $now;
	} elseif (isset($in['replace']) && is_array($in['replace'])) {
		$backup = true;
		$rep = $in['replace'];
		$state['checked'] = array();
		if (isset($rep['checked']) && is_array($rep['checked'])) {
			foreach ($rep['checked'] as $k => $v) {
				if (!preg_match($idRe, (string) $k)) {
					continue;
				}
				if ($v === true || (is_string($v) && $v !== '')) {
					$state['checked'][$k] = (is_string($v) && preg_match($atRe, $v)) ? $v : $now;
				}
			}
		}
		$state['note']   = (isset($rep['note']) && is_string($rep['note'])) ? $rep['note'] : '';
		$state['noteAt'] = $now;
	} else {
		$ops = (isset($in['ops']) && is_array($in['ops'])) ? $in['ops'] : array();
		if (count($ops) > 500) {
			$bad = 'ส่งครั้งละไม่เกิน 500 ข้อ';
		} else {
			foreach ($ops as $op) {
				$id = (is_array($op) && isset($op['id'])) ? (string) $op['id'] : '';
				if (!preg_match($idRe, $id)) {
					continue;
				}
				if (!empty($op['on'])) {
					$at = (isset($op['at']) && is_string($op['at']) && preg_match($atRe, $op['at'])) ? $op['at'] : $now;
					$state['checked'][$id] = $at;
				} else {
					unset($state['checked'][$id]);
				}
			}
			if (isset($in['note']) && is_string($in['note'])) {
				$state['note']   = (string) $in['note'];
				$state['noteAt'] = $now;
			}
		}
	}
	if ($bad === '' && mb_strlen($state['note'], 'UTF-8') > 20000) {
		$bad = 'หมายเหตุยาวเกิน 20,000 ตัวอักษร';
	}
	if ($bad === '' && count($state['checked']) > 1000) {
		$bad = 'จำนวนข้อเกินที่รับได้';
	}
	if ($bad !== '') {
		flock($fp, LOCK_UN);
		fclose($fp);
		http_response_code(400);
		echo json_encode(array('error' => $bad), $json);
		exit;
	}
	if ($backup && trim((string) $text) !== '') {
		@file_put_contents($dir . '/progress-' . date('Ymd-His') . '.txt', $text);
	}
	ksort($state['checked'], SORT_NATURAL);
	$state['rev']++;
	$state['updatedAt'] = $now;
	$out = json_encode($state, $json);
	rewind($fp);
	ftruncate($fp, 0);
	$okWrite = (fwrite($fp, $out) === strlen($out));
	fflush($fp);
	if (!$okWrite) {
		flock($fp, LOCK_UN);
		fclose($fp);
		http_response_code(500);
		echo json_encode(array('error' => 'บันทึกไฟล์ผลไม่สำเร็จ'), $json);
		exit;
	}
}
flock($fp, LOCK_UN);
fclose($fp);

$state['ok']  = true;
$state['now'] = $now;
echo json_encode($state, $json);
