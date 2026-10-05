<?php

function _systemlogs_delete_Member_oldlogs($num = 50)
{
    $db = DB::singleton();
    $sql = "
    SELECT logs_id as minid
    FROM " . _DBPREFIX_ . "member_member_login_logs
    ORDER BY logs_id DESC
     ";
    $db->query($sql, __FUNCTION__);
    if ($db->num_rows() > $num) {
        $sql .= "LIMIT 0,{$num};";
        $db->query($sql, __FUNCTION__);
        $aData = array();
        while ($db->next_record()) {
            $aData[] = $db->allRows();
        }
        $minid = min($aData);
        $minid = @$minid['minid'];

        if (is_array($aData) && count($aData) >= $num) {
            $sql = "DELETE FROM " . _DBPREFIX_ . "member_member_login_logs WHERE logs_id < '{$minid}'; ";
            $db->query($sql, __FUNCTION__);
        }
    }
}

function _systemlogs_delete_MemberLogs($id)
{
    $db = DB::singleton();
    $sql = "DELETE FROM " . _DBPREFIX_ . "member_member_login_logs WHERE logs_id = '{$id}'; ";
    $db->query($sql, __FUNCTION__);
}

function _systemlogs_getMemberLogs($num = 50, $page = 1)
{
    $sql = "
    SELECT  ma.logs_id,m.user_id, m.firstname, m.lastname, ma.logs_time, ma.member_ip
    FROM " . _DBPREFIX_ . "member_member m,
         " . _DBPREFIX_ . "member_member_login_logs ma
    WHERE m.user_id = ma.user_id
    ORDER BY ma.logs_id DESC
    ";
    $db = DB::singleton();
    $aData = $db->pager(__FUNCTION__, $sql, $num, $page);
    return $aData;
}

// keeree 25/8/2015//
function _systemlogs_getAdminLogs($num = 50, $page = 1)
{
    $db = DB::singleton();
    $sql = "
	SELECT m.*, ma.*
	FROM " . _DBPREFIX_ . "member_user m,
	     " . _DBPREFIX_ . "member_admin_login_logs ma
	WHERE m.user_id = ma.user_id
	ORDER BY ma.logs_id DESC, m.username DESC
	";
    $db->query($sql, __FUNCTION__);
    $aData = array();
    $aData['num_rows'] = $db->num_rows();
    $aData['maxpage'] = ($num > 0) ? ceil($aData['num_rows'] / $num) : 0;
    $aData['nextpage'] = (($page + 1) <= $aData['maxpage']) ? ($page + 1) : $aData['maxpage'];
    $aData['backpage'] = (($page - 1) > 1) ? ($page - 1) : 1;
    if ($num < $aData['num_rows']) {
        if ($num > 0) {
            $start = ($page == 0 || $page == 1) ? 0 : (($num * $page) - $num);
            $sql .= ($num > 0) ? ' LIMIT ' . $start . ' , ' . $num . ' ;' : ';';
        }
        $db->query($sql, __FUNCTION__);
    }
    $aData['sql'] = $sql;
    while ($db->next_record()) {
        $aData['data'][] = $db->allRows();
    }
    return $aData;
}

function _systemlogs_getAdminLogs2($num = 50, $page = 1)
{
    $db = DB::singleton();
    $sql = "
	SELECT m.*, ma.*
	FROM " . _DBPREFIX_ . "member_user m,
	     " . _DBPREFIX_ . "member_admin_login_logs ma
	WHERE m.user_id = ma.user_id AND m.username != 'superadmin'
	ORDER BY ma.logs_id DESC, m.username DESC	
	";
    $db->query($sql, __FUNCTION__);
    $aData = array();
    $aData['num_rows'] = $db->num_rows();
    $aData['maxpage'] = ($num > 0) ? ceil($aData['num_rows'] / $num) : 0;
    $aData['nextpage'] = (($page + 1) <= $aData['maxpage']) ? ($page + 1) : $aData['maxpage'];
    $aData['backpage'] = (($page - 1) > 1) ? ($page - 1) : 1;
    if ($num < $aData['num_rows']) {
        if ($num > 0) {
            $start = ($page == 0 || $page == 1) ? 0 : (($num * $page) - $num);
            $sql .= ($num > 0) ? ' LIMIT ' . $start . ' , ' . $num . ' ;' : ';';
        }
        $db->query($sql, __FUNCTION__);
    }
    $aData['sql'] = $sql;
    while ($db->next_record()) {
        $aData['data'][] = $db->allRows();
    }
    return $aData;
}

function _systemlogs_getAdminLogsCount()
{
    $db = DB::singleton();
    $sql = "
	SELECT m.*, ma.*
	FROM " . _DBPREFIX_ . "member_user m,
	     " . _DBPREFIX_ . "member_admin_login_logs ma
	WHERE m.user_id = ma.user_id
	ORDER BY ma.logs_id DESC, m.username DESC
	";
    $db->query($sql, __FUNCTION__);
    $aData = array();
    $aData['num_rows'] = $db->num_rows();
    $db->query($sql, __FUNCTION__);
    $aData['sql'] = $sql;
    while ($db->next_record()) {
        $aData['data'][] = $db->allRows();
    }
    return $aData;
}

function _systemlogs_getAdminLogsCount2()
{
    $db = DB::singleton();
    $sql = "
	SELECT m.*, ma.*
	FROM " . _DBPREFIX_ . "member_user m,
	     " . _DBPREFIX_ . "member_admin_login_logs ma
	WHERE m.user_id = ma.user_id AND m.username != 'superadmin'
	ORDER BY ma.logs_id DESC, m.username DESC
	";
    $db->query($sql, __FUNCTION__);
    $aData = array();
    $aData['num_rows'] = $db->num_rows();
    $db->query($sql, __FUNCTION__);
    $aData['sql'] = $sql;
    while ($db->next_record()) {
        $aData['data'][] = $db->allRows();
    }
    return $aData;
}

//siwakorn 21/10/2016
function _systemlogs_getLoginLogs($num = 50, $page = 1)
{
    $db = DB::singleton();
    $sql = "SELECT * FROM " . _DBPREFIX_ . "logs_login ORDER BY logs_id DESC";
    $aData = $db->pager(__FUNCTION__, $sql, $num, $page);
    return $aData;
}

function _systemlogs_delete_adminLogs($id)
{
    $db = DB::singleton();
    $sql = "DELETE FROM " . _DBPREFIX_ . "member_admin_login_logs WHERE logs_id = '{$id}'; ";
    $db->query($sql, __FUNCTION__);
}


function _systemlogs_delete_admin_oldLoginlogs($num = 50)
{
    $db = DB::singleton();
    $sql = "
	SELECT logs_id
	FROM " . _DBPREFIX_ . "logs_login
	WHERE loginBan = '0'
	ORDER BY logs_id DESC
	 ";
    $db->query($sql, __FUNCTION__);
    if ($db->num_rows() > $num) {
        $sql .= "LIMIT 0,{$num};";
        $db->query($sql, __FUNCTION__);
        $aData = array();
        while ($db->next_record()) {
            $aData[] = $db->f('logs_id');
        }
        $minid = min($aData);
        $minid = @$minid['logs_id'];

        pre($aData);
        echo $minid;
        if (is_array($aData) && count($aData) >= $num) {
            $sql = "DELETE FROM " . _DBPREFIX_ . "logs_login WHERE logs_id < '{$minid}' AND loginBan = '0'; ";
            //$db->query($sql, __FUNCTION__);
        }
    }
}


function _systemlogs_delete_admin_oldlogs($num = 50)
{
    $db = DB::singleton();
    $sql = "
	SELECT logs_id as minid
	FROM " . _DBPREFIX_ . "member_admin_login_logs
	ORDER BY logs_id DESC
	 ";
    $db->query($sql, __FUNCTION__);
    if ($db->num_rows() > $num) {
        $sql .= "LIMIT 0,{$num};";
        $db->query($sql, __FUNCTION__);
        $aData = array();
        while ($db->next_record()) {
            $aData[] = $db->allRows();
        }
        $minid = min($aData);
        $minid = @$minid['minid'];

        if (is_array($aData) && count($aData) >= $num) {
            $sql = "DELETE FROM " . _DBPREFIX_ . "member_admin_login_logs WHERE logs_id < '{$minid}'; ";
            $db->query($sql, __FUNCTION__);
        }
    }
}

/* ===================================================== */

function _systemlogs_getMailLogs($num = 50, $page = 1)
{
    $sql = "SELECT 	* FROM " . _DBPREFIX_ . "logs_mail ORDER BY logs_time DESC ";
    $db = DB::singleton();
    $aData = $db->pager(__FUNCTION__, $sql, $num, $page);
    return $aData;
}

function _systemlogs_getMailLogsId($id)
{
    $db = DB::singleton();
    $sql = "SELECT 	* FROM " . _DBPREFIX_ . "logs_mail WHERE logs_id='{$id}'; ";
    $db->query($sql, __FUNCTION__);
    return $db->next_record() ? $db->allRows() : array();
}

function _systemlogs_delete_mailLogs($id)
{
    $db = DB::singleton();
    $sql = "DELETE FROM " . _DBPREFIX_ . "logs_mail WHERE logs_id = '{$id}'; ";
    $db->query($sql, __FUNCTION__);
}

function builStringJson($data, $fnameChack, $baseKey = '')
{
    $str = '';
    foreach ($data as $k => $v) {
        if (is_array($v)) {
            if (!is_numeric($k)) {
                $str .= builStringJson($v, $fnameChack, $baseKey . "['" . $k . "']");
            } else {
                $str .= builStringJson($v, $fnameChack, $baseKey);
            }
        } else {
            $found = '$aosoft_permit' . $baseKey . "['" . $k . "'] = '" . $v . "';";
            if (strpos($fnameChack, $found) === false) {
                $str .= $found . "\n";
            }
        }
    }
    return $str;
}

function BACKUP_Create()
{
    $backupDir = PATH_UPLOAD . '/backdb/' . _TIME_;
    if (!is_dir($backupDir)) {
        if (!@mkdir($backupDir, 0777, true)) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถสร้างโฟลเดอร์ backup ได้: ' . $backupDir
            ];
        }
    }

    try {
        // สร้างไฟล์ structure.sql
        $structureContent = "-- Database Structure Backup\n";
        $structureContent .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $structureContent .= "-- Database: " . DB_NAME . "\n\n";
        $structureContent .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $structureContent .= "SET time_zone = \"+00:00\";\n\n";
        $structureContent .= BACKUP_getTableStructure();

        $structureFile = $backupDir . '/structure.sql';
        if (!file_put_contents($structureFile, $structureContent)) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถสร้างไฟล์ structure.sql ได้'
            ];
        }

        $dataFiles = BACKUP_getTableData($backupDir);
        if (empty($dataFiles)) {
            return [
                'success' => false,
                'message' => 'ไม่สามารถสร้างไฟล์ข้อมูลได้'
            ];
        }

        return [
            'success' => true,
            'message' => 'สร้าง Snapshot Backup เรียบร้อยแล้ว',
            'timestamp' => _TIME_,
            'path' => 'uploads/backdb/' . _TIME_,
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}
// มาทำต่อให้เมื่อปุ่มโหลดแล้วไม่ต้องสร้างไฟล์ใหม่ให้ไปรวบโฟเดอร์เอา
function BACKUP_CreateZIP($namefile)
{
    $sourceFolder = PATH_UPLOAD . '/backdb/' . $namefile;
    $zipFileName = $namefile . '.zip';
    $zipFilePath = PATH_UPLOAD . '/backdb/' . $zipFileName;

    // ตรวจสอบว่าโฟลเดอร์ source มีอยู่จริงหรือไม่
    if (!is_dir($sourceFolder)) {
        setRaiseMsg('ไม่พบโฟลเดอร์ที่ต้องการสำรองข้อมูล: ' . $namefile, _TIME_, 1);
        CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
        exit();
    }

    // ตรวจสอบว่าโฟลเดอร์ไม่ว่างเปล่า
    $files = glob($sourceFolder . '/*');
    if (empty($files)) {
        setRaiseMsg('โฟลเดอร์ที่ต้องการสำรองข้อมูลว่างเปล่า: ' . $namefile, _TIME_, 1);
        CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
        exit();
    }

    try {
        $zip = new ZipArchive();
        $result = $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // ตรวจสอบผลลัพธ์จาก ZipArchive::open()
        if ($result !== TRUE) {
            $errorMsg = 'ไม่สามารถสร้างไฟล์ ZIP ได้ ';
            switch ($result) {
                case ZipArchive::ER_OK:
                    $errorMsg .= '(สำเร็จ)';
                    break;
                case ZipArchive::ER_MEMORY:
                    $errorMsg .= '(หน่วยความจำไม่เพียงพอ)';
                    break;
                case ZipArchive::ER_NOENT:
                    $errorMsg .= '(ไม่พบไฟล์)';
                    break;
                case ZipArchive::ER_OPEN:
                    $errorMsg .= '(ไม่สามารถเปิดไฟล์ได้)';
                    break;
                case ZipArchive::ER_READ:
                    $errorMsg .= '(ไม่สามารถอ่านไฟล์ได้)';
                    break;
                case ZipArchive::ER_WRITE:
                    $errorMsg .= '(ไม่สามารถเขียนไฟล์ได้)';
                    break;
                default:
                    $errorMsg .= '(รหัสข้อผิดพลาด: ' . $result . ')';
                    break;
            }
            setRaiseMsg($errorMsg, _TIME_, 1);
            CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
            exit();
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceFolder, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $fileCount = 0;
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $relativePath = str_replace($sourceFolder . DIRECTORY_SEPARATOR, '', $filePath);

                // แปลง path separator สำหรับ ZIP (ใช้ / เสมอ)
                $relativePath = str_replace('\\', '/', $relativePath);

                // เพิ่มไฟล์ลงใน ZIP
                if ($zip->addFile($filePath, $relativePath)) {
                    $fileCount++;
                } else {
                    $zip->close();
                    BACKUP_cleanupTemp('', $zipFilePath);
                    setRaiseMsg('ไม่สามารถเพิ่มไฟล์ลงใน ZIP ได้: ' . $relativePath, _TIME_, 1);
                    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
                    exit();
                }
            }
        }

        // ตรวจสอบว่ามีไฟล์ถูกเพิ่มลงใน ZIP หรือไม่
        if ($fileCount === 0) {
            $zip->close();
            BACKUP_cleanupTemp('', $zipFilePath);
            setRaiseMsg('ไม่พบไฟล์ที่จะเพิ่มลงใน ZIP', _TIME_, 1);
            CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
            exit();
        }

        $zip->close();

        // ตรวจสอบว่าไฟล์ ZIP ถูกสร้างสำเร็จ
        if (file_exists($zipFilePath) && filesize($zipFilePath) > 0) {
            // ส่งไฟล์ให้ดาวน์โหลด
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
            header('Content-Length: ' . filesize($zipFilePath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

            // ล้าง output buffer ก่อนส่งไฟล์
            if (ob_get_level()) {
                ob_end_clean();
            }

            readfile($zipFilePath);
            BACKUP_cleanupTemp('', $zipFilePath);
            exit();
        } else {
            BACKUP_cleanupTemp('', $zipFilePath);
            setRaiseMsg('ไม่สามารถสร้างไฟล์ ZIP ได้หรือไฟล์ว่างเปล่า', _TIME_, 1);
            CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
            exit();
        }
    } catch (Exception $e) {
        BACKUP_cleanupTemp('', $zipFilePath);
        setRaiseMsg('เกิดข้อผิดพลาด: ' . $e->getMessage(), _TIME_, 1);
        CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
        exit();
    }
}

function BACKUP_CreateZIP2($namefile)
{
    $sourceFolder = PATH_UPLOAD . '/autobackup/' . $namefile;
    $zipFileName = $namefile . '.zip';
    $zipFilePath = PATH_UPLOAD . '/autobackup/' . $zipFileName;

    // ตรวจสอบว่าโฟลเดอร์ source มีอยู่จริงหรือไม่
    if (!is_dir($sourceFolder)) {
        setRaiseMsg('ไม่พบโฟลเดอร์ที่ต้องการสำรองข้อมูล: ' . $namefile, _TIME_, 1);
        CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
        exit();
    }

    // ตรวจสอบว่าโฟลเดอร์ไม่ว่างเปล่า
    $files = glob($sourceFolder . '/*');
    if (empty($files)) {
        setRaiseMsg('โฟลเดอร์ที่ต้องการสำรองข้อมูลว่างเปล่า: ' . $namefile, _TIME_, 1);
        CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
        exit();
    }

    try {
        $zip = new ZipArchive();
        $result = $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // ตรวจสอบผลลัพธ์จาก ZipArchive::open()
        if ($result !== TRUE) {
            $errorMsg = 'ไม่สามารถสร้างไฟล์ ZIP ได้ ';
            switch ($result) {
                case ZipArchive::ER_OK:
                    $errorMsg .= '(สำเร็จ)';
                    break;
                case ZipArchive::ER_MEMORY:
                    $errorMsg .= '(หน่วยความจำไม่เพียงพอ)';
                    break;
                case ZipArchive::ER_NOENT:
                    $errorMsg .= '(ไม่พบไฟล์)';
                    break;
                case ZipArchive::ER_OPEN:
                    $errorMsg .= '(ไม่สามารถเปิดไฟล์ได้)';
                    break;
                case ZipArchive::ER_READ:
                    $errorMsg .= '(ไม่สามารถอ่านไฟล์ได้)';
                    break;
                case ZipArchive::ER_WRITE:
                    $errorMsg .= '(ไม่สามารถเขียนไฟล์ได้)';
                    break;
                default:
                    $errorMsg .= '(รหัสข้อผิดพลาด: ' . $result . ')';
                    break;
            }
            setRaiseMsg($errorMsg, _TIME_, 1);
            CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
            exit();
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceFolder, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $fileCount = 0;
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $relativePath = str_replace($sourceFolder . DIRECTORY_SEPARATOR, '', $filePath);

                // แปลง path separator สำหรับ ZIP (ใช้ / เสมอ)
                $relativePath = str_replace('\\', '/', $relativePath);

                // เพิ่มไฟล์ลงใน ZIP
                if ($zip->addFile($filePath, $relativePath)) {
                    $fileCount++;
                } else {
                    $zip->close();
                    BACKUP_cleanupTemp2('', $zipFilePath);
                    setRaiseMsg('ไม่สามารถเพิ่มไฟล์ลงใน ZIP ได้: ' . $relativePath, _TIME_, 1);
                    CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
                    exit();
                }
            }
        }

        // ตรวจสอบว่ามีไฟล์ถูกเพิ่มลงใน ZIP หรือไม่
        if ($fileCount === 0) {
            $zip->close();
            BACKUP_cleanupTemp2('', $zipFilePath);
            setRaiseMsg('ไม่พบไฟล์ที่จะเพิ่มลงใน ZIP', _TIME_, 1);
            CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
            exit();
        }

        $zip->close();

        // ตรวจสอบว่าไฟล์ ZIP ถูกสร้างสำเร็จ
        if (file_exists($zipFilePath) && filesize($zipFilePath) > 0) {
            // ส่งไฟล์ให้ดาวน์โหลด
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
            header('Content-Length: ' . filesize($zipFilePath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

            // ล้าง output buffer ก่อนส่งไฟล์
            if (ob_get_level()) {
                ob_end_clean();
            }

            readfile($zipFilePath);
            BACKUP_cleanupTemp2('', $zipFilePath);
            exit();
        } else {
            BACKUP_cleanupTemp2('', $zipFilePath);
            setRaiseMsg('ไม่สามารถสร้างไฟล์ ZIP ได้หรือไฟล์ว่างเปล่า', _TIME_, 1);
            CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
            exit();
        }
    } catch (Exception $e) {
        BACKUP_cleanupTemp2('', $zipFilePath);
        setRaiseMsg('เกิดข้อผิดพลาด: ' . $e->getMessage(), _TIME_, 1);
        CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
        exit();
    }
}

function BACKUP_cleanupTemp($tempDir, $zipFile)
{
    $baseDir = realpath(PATH_UPLOAD . '/backdb/');

    if (!empty($zipFile)) {
        $realZipFile = realpath($zipFile);
        if ($realZipFile !== false && strpos($realZipFile, $baseDir) === 0 && is_file($realZipFile)) {
            @unlink($realZipFile);
        }
    }

    if (!empty($tempDir)) {
        $realTempDir = realpath($tempDir);
        if ($realTempDir !== false && strpos($realTempDir, $baseDir) === 0) {
            if (is_dir($realTempDir)) {
                $files = glob($realTempDir . '/*');
                foreach ($files as $file) {
                    $realFile = realpath($file);
                    if ($realFile !== false && is_file($realFile) && strpos($realFile, $baseDir) === 0) {
                        unlink($realFile);
                    }
                }
                @rmdir($realTempDir);
            }
        }
    }
}

function BACKUP_cleanupTemp2($tempDir, $zipFile)
{
    $baseDir = realpath(PATH_UPLOAD . '/autobackup/');

    if (!empty($zipFile)) {
        $realZipFile = realpath($zipFile);
        if ($realZipFile !== false && strpos($realZipFile, $baseDir) === 0 && is_file($realZipFile)) {
            @unlink($realZipFile);
        }
    }

    if (!empty($tempDir)) {
        $realTempDir = realpath($tempDir);
        if ($realTempDir !== false && strpos($realTempDir, $baseDir) === 0) {
            if (is_dir($realTempDir)) {
                $files = glob($realTempDir . '/*');
                foreach ($files as $file) {
                    $realFile = realpath($file);
                    if ($realFile !== false && is_file($realFile) && strpos($realFile, $baseDir) === 0) {
                        unlink($realFile);
                    }
                }
                @rmdir($realTempDir);
            }
        }
    }
}

function BACKUP_DeleteFile($fileName)
{
    $pathlog = PATH_UPLOAD . '/backdb/';
    $targetPath = $pathlog . $fileName;
    if (is_dir($targetPath)) {
        $realDir = realpath($targetPath);
        if ($realDir !== false && strpos($realDir, realpath($pathlog)) === 0) {
            BACKUP_cleanupTemp($targetPath, '');
        }
    }
}

function BACKUP_DeleteFile2($fileName)
{
    $pathlog = PATH_UPLOAD . '/autobackup/';
    $targetPath = $pathlog . $fileName;
    if (is_dir($targetPath)) {
        $realDir = realpath($targetPath);
        if ($realDir !== false && strpos($realDir, realpath($pathlog)) === 0) {
            BACKUP_cleanupTemp2($targetPath, '');
        }
    }
}

function BACKUP_Process($bkAction)
{
    $pathlog = PATH_UPLOAD . '/backdb/';
    if (!is_dir($pathlog)) {
        @mkdir($pathlog, 0777, true);
    }

    if ($bkAction == 'server') {
        return BACKUP_Create();
    } elseif ($bkAction == 'local') {
        return BACKUP_CreateZIP($_POST['backup_action']);
    }

    return false;
}

function _memberonline_delete_old($days = 2)
{
    $db = DB::singleton();
    $expireTime = time() - ($days * 24 * 60 * 60);
    $sql = "DELETE FROM " . _DBPREFIX_ . "member_online WHERE onlineTime < '{$expireTime}'";
    $db->query($sql, __FUNCTION__);
}