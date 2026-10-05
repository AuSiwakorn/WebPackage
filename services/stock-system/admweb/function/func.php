<?php
include PATH_ADMIN . '/function/config.php';
/**
 * TODO: [ ] รองรับการ resize / compress รูปหลัง upload (เช่น GD หรือ Imagick)
 * TODO: [ ] เพิ่ม MIME type validation ควบคู่กับ extension (ป้องกัน extension spoofing)
 * TODO: [ ] เพิ่ม max file size check ก่อน move_uploaded_file
 *
 * อัปโหลดไฟล์จาก $_FILES ไปยัง PATH_UPLOAD
 *
 * @param array  $file               ข้อมูลจาก $_FILES['input_name']
 * @param array  $allowed_extensions นามสกุลที่อนุญาต — ถ้าไม่ส่งมาจะดึงจาก $type
 * @param string $target_dir         โฟลเดอร์ปลายทาง (relative กับ PATH_UPLOAD)
 * @param string $type               'picture' = ใช้ $allowExtention จาก fix file
 *                                   'file'    = ใช้ list ไฟล์ทั่วไป (pdf, docx, mp4 ฯลฯ)
 * @return string path ของไฟล์ที่บันทึก (relative) หรือ '' ถ้าล้มเหลว
 */
function Func_uploads_file(
    array $file,
    array $allowed_extensions = [],
    string $target_dir = 'products',
    string $type = 'picture'
): string {
    if (
        !isset($file['error'], $file['tmp_name'], $file['name']) ||
        $file['error'] !== UPLOAD_ERR_OK ||
        !is_uploaded_file($file['tmp_name'])
    ) {
        return '';
    }

    if (empty($allowed_extensions)) {
        global $allowExtention;
        if ($type === 'picture') {
            $allowed_extensions = $allowExtention ?? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        } else {
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'pdf', 'docx', 'xlsx', 'txt', 'zip', 'rar', 'mp4', 'mov', 'mp3'];
        }
    }
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_extensions, true)) {
        return '';
    }
    $clean_target_dir = trim($target_dir, '/');
    $full_path_dir = rtrim(PATH_UPLOAD, '/') . '/' . $clean_target_dir;
    if (!is_dir($full_path_dir)) {
        if (!mkdir($full_path_dir, 0777, true) && !is_dir($full_path_dir)) {
            return '';
        }
    }
    $new_file_name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
    $destination_relative = $clean_target_dir . '/' . $new_file_name;
    $destination_full = $full_path_dir . '/' . $new_file_name;
    if (!move_uploaded_file($file['tmp_name'], $destination_full)) {
        return '';
    }
    return $destination_relative;
}

function DifDate($date)
{
    $today = date('Y-m-d');           // วันที่วันนี้

    // แปลงเป็น timestamp
    $timestamp1 = strtotime($date);
    $timestamp2 = strtotime($today);

    // คำนวณความต่าง (เป็นวินาที)
    $diffInSeconds = $timestamp1 - $timestamp2;

    // แปลงเป็นวัน
    $diffInDays = floor($diffInSeconds / (60 * 60 * 24));

    return $diffInDays;
    /*
	echo "ต่างกัน $diffInDays วัน";

	if ($diffInDays > 0) {
		echo " (ในอดีต)";
	} elseif ($diffInDays < 0) {
		echo " (ในอนาคต)";
	} else {
		echo " (วันนี้)";
	}*/
}

function PG_unlinkMetaIcon($metaKey, $lang, $icon = 'icon')
{
    //////////// Validate /////////////
    $metaKey = trim((string)$metaKey);
    $lang    = trim((string)$lang);

    if ($metaKey === '' || $lang === '') {
        return false;
    }
    $icon = preg_replace('/[^a-zA-Z0-9_]/', '', $icon);
    if ($icon === '') {
        $icon = 'icon';
    }
    ////////// End Validate ///////////

    $db = DB::singleton();

    $sql = "
        SELECT `{$icon}`
        FROM " . _DBPREFIX_ . "site_metatags
        WHERE meta_key = '" . addslashes($metaKey) . "'
          AND lang     = '" . addslashes($lang) . "'
        LIMIT 1;
    ";
    $db->query($sql, __FUNCTION__);
    if ($db->num_rows() <= 0) {
        return false;
    }

    $db->next_record();
    $filename = $db->f($icon);

    if ($filename != '' && is_file(PATH_UPLOAD . '/' . $filename)) {
        @unlink(PATH_UPLOAD . '/' . $filename);
    }

    $sql = "
        UPDATE " . _DBPREFIX_ . "site_metatags
        SET `{$icon}` = ''
        WHERE meta_key = '" . addslashes($metaKey) . "'
          AND lang     = '" . addslashes($lang) . "';
    ";
    $db->query($sql, __FUNCTION__);

    return true;
}
