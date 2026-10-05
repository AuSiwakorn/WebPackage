<?php
function fetchFileList($path, $ignore = [])
{
    global $aHash;
    if (is_dir($path)) {
        $dirs = glob($path . '/*', GLOB_ONLYDIR);
        if (count($dirs) > 0) {
            foreach ($dirs as $v) {
                $md5 = md5($v);
                if (!in_array($md5, $ignore)) {
                    fetchFileList($v, $ignore);
                }
                $folder = array_filter(glob($path . '/*'), 'is_file');
                if (count($folder) > 0) {
                    $aHash = buildHashFolder($folder, $aHash, $ignore);
                }
            }
        } else {
            $folder = array_filter(glob($path . '/*'), 'is_file');
            $aHash = buildHashFolder($folder, $aHash, $ignore);
        }
    } else {
        $aHash = buildHashFile($path, $aHash);
    }
    return $aHash;
}

function buildHashFolder($dirs, $aHash, $ignore)
{
    global $aHash, $aHashCheck, $aHashError, $folder;
    foreach ($dirs as $v) {
        $md5 = md5($v);
        if (!in_array($md5, $ignore) && strpos($v, 'error_log') === false) {
            $hash = hash_file('sha256', $v);
            $aHash[$md5] = $hash;
            $path = str_replace(PATH_WEB_ROOT . '/', '', $v);
            if (isset($aHashCheck[$md5])) {
                if ($aHashCheck[$md5] !== '' && $aHashCheck[$md5] !== $hash) {
                    $aHashError['error'][$folder][$md5] = $path;
                }
            } else {
                $aHashError['newfile'][$folder][$md5] = $path;
            }
        }
    }
    return $aHash;
}

function buildHashFile($file, $aHash)
{
    global $aHash, $aHashCheck, $aHashError, $folder;
    $md5 = md5($file);
    $hash = hash_file('sha256', $file);
    $aHash[$md5] = $hash;
    $path = str_replace(PATH_WEB_ROOT . '/', '', $file);
    if (isset($aHashCheck[$md5]) && $aHashCheck[$md5] !== '' && $aHashCheck[$md5] !== $hash) {
        $aHashError['error'][$folder][$md5] = $path;
    }
    return $aHash;
}

function chackHashFile()
{
    global $aHashCheck, $aHashError, $folder;

    $dirs = glob(PATH_WEB_ROOT . '/*');
    foreach ($dirs as $v) {
        $folder = basename($v);
        $fname = PATH_UPLOAD . '/hash/' . $folder . '.txt';
        $ignore = read_txt_json_Hash(PATH_UPLOAD . '/hash/ignore_' . $folder . '.txt');
        if (file_exists($fname)) {
            $aHashCheck = read_txt_json_Hash($fname);
            fetchFileList($v, $ignore);
        }
    }
    checkHashError(PATH_UPLOAD . '/hash/hash.txt', $aHashError);
    if (!empty($aHashError['error']) || !empty($aHashError['newfile'])) {
        $isRequest = (php_sapi_name() !== 'cli');
        $sec = [
            'error' => 'ไฟล์ถูกแก้ไข :',
            'newfile' => 'ไฟล์ถูกเพิ่มขึ้นมาใหม่ :'
        ];
        // foreach ($sec as $k => $title) {
        //     if (!empty($aHashError[$k])) {
        //         $files = count(array_merge(...array_values($aHashError[$k])));
        //         if ($isRequest) {
        //             echo "{$title} {$files} ไฟล์<br>";
        //         } else {
        //             echo "{$title} {$files} ไฟล์\n";
        //         }
        //         foreach ($aHashError[$k] as $v) {
        //             foreach ($v as $vv) {
        //                 if ($isRequest) {
        //                     echo "&nbsp;&nbsp;&nbsp;&nbsp;{$vv}<br>";
        //                 } else {
        //                     echo "    {$vv}\n";
        //                 }
        //             }
        //         }
        //     }
        // }
    }
}

function checkHashError($fname, $data)
{
    if (is_array($data) && count($data) > 0) {
        write_txt_json_Hash($fname, $data);
    } elseif (file_exists($fname)) {
        unlink($fname);
    }
}

function read_txt_json_Hash($fname)
{
    $str = @file($fname);
    if (isset($str[0])) {
        $str = json_decode($str[0], true);
    } else {
        $str = [];
    }
    return $str;
}

function write_txt_json_Hash($fname, $data)
{
    $fp = @fopen($fname, 'w');
    if ($fp) {
        $str = json_encode($data);
        $len = strlen($str);
        @flock($fp, LOCK_EX);
        @fwrite($fp, $str, $len);
        @flock($fp, LOCK_UN);
        @fclose($fp);
        return true;
    }
}

function updateHash($data)
{
    $fname = PATH_UPLOAD . '/hash/hash.txt';
    if (file_exists($fname)) {
        $ckHash = read_txt_json_Hash($fname);
        foreach (['error', 'newfile'] as $v) {
            if (isset($ckHash[$v])) {
                if (array_key_exists($data, $ckHash[$v])) {
                    unset($ckHash[$v][$data]);
                }
            }
        }
        if (!empty($ckHash['error']) || !empty($ckHash['newfile'])) {
            write_txt_json_Hash($fname, $ckHash);
        } else {
            unlink($fname);
        }
    }
}

function getConfigIgnore($path, $ignore)
{
    $dirs = glob($path . '/*', GLOB_ONLYDIR);
    $md5Path = md5($path);
    if (!empty($dirs)) {
        foreach ($dirs as $v) {
            $md5 = md5($v);
            $folder = basename($v);
            $se = in_array($md5, $ignore) ? 'checked' : '';
            $dis = in_array($md5Path, $ignore) ? 'disabled' : '';
            $color = in_array($md5Path, $ignore) ? 'color:gray;' : '';
            $class = "checkHash-{$md5} {$md5Path}";
            echo '<div class="row">';
            echo '<div class="col-xs-12">';
            echo "<input {$se} {$dis} class='{$class}' onclick='return chackHashAll(\"{$md5}\");' type='checkbox' id='gethashing-{$md5}' name='ignore[{$md5}]' value='{$md5}' style='width: 17px;height: 17px;'>";
            echo "<label class='{$class} text-bold text-main' for='gethashing-{$md5}' style='{$color} position: relative; top: -3px;'>&nbsp;&nbsp;{$folder}</label>";
            echo '</div>';
            $subDirs = glob($v . '/*', GLOB_ONLYDIR);
            if (!empty($subDirs) && strpos($path, PATH_UPLOAD) !== 0) {
                foreach ($subDirs as $vv) {
                    $md52 = md5($vv);
                    $folder2 = basename($vv);
                    $se2 = in_array($md52, $ignore) ? 'checked' : '';
                    $dis2 = (in_array($md5Path, $ignore) || in_array($md5, $ignore)) ? 'disabled' : '';
                    $color2 = (in_array($md5Path, $ignore) || in_array($md5, $ignore)) ? 'color:gray;' : '';
                    $class2 = "checkHash-{$md52} {$md5Path} {$md5}";
                    echo '<div class="col-xs-3">';
                    echo "<input {$se2} {$dis2} class='{$class2}' onclick='return chackHashAll(\"{$md52}\");' type='checkbox' id='gethashing-{$md52}' name='ignore[{$md52}]' value='{$md52}' style='width: 17px;height: 17px;'>";
                    echo "<label class='{$class2} text-info' for='gethashing-{$md52}' style='{$color2} position: relative; top: -3px;'>&nbsp;&nbsp;{$folder2}</label>";
                    echo '</div>';
                }
            }
            echo '</div><hr>';
        }
    }
}

function getIgnoreCount($path, $ignore)
{
    $dirs = glob($path . '/*', GLOB_ONLYDIR);
    $cnt = 0;
    $sumDirs = 0;
    if (!empty($dirs)) {
        foreach ($dirs as $v) {
            $sumDirs++;
            $subCnt = 0;
            $md5Dir = md5($v);
            $subDirs = glob($v . '/*', GLOB_ONLYDIR);
            if (!empty($subDirs) && strpos($path, PATH_UPLOAD) !== 0) {
                foreach ($subDirs as $vv) {
                    $sumDirs++;
                    $subCnt++;
                    $md5Sub = md5($vv);
                    if (in_array($md5Sub, $ignore)) {
                        $cnt++;
                    }
                }
            }
            if (in_array($md5Dir, $ignore)) {
                $cnt += $subCnt + 1;
            }
        }
    }
    return [$cnt, $sumDirs];
}

function readHashError()
{
    $fname = PATH_UPLOAD . '/hash/hash.txt';
    if (!file_exists($fname)) return '';

    $msg = [];
    $msg[] = 'วันที่ ' . date('d-m-Y H:i', _TIME_);
    $msg[] = DOMAIN_NAME;
    $ckHash = read_txt_json_Hash($fname);
    if (!empty($ckHash['error'])) {
        $num = count($ckHash['error']);
        $msg[] = "ไฟล์ถูกแก้ไข $num ไฟล์";
        foreach ($ckHash['error'] as $v) {
            $msg = array_merge($msg, $v);
        }
    }
    if (!empty($ckHash['newfile'])) {
        $num = count($ckHash['newfile']);
        $msg[] = "ไฟล์ถูกเพิ่มขึ้นใหม่ $num ไฟล์";
        foreach ($ckHash['newfile'] as $v) {
            $msg = array_merge($msg, $v);
        }
    }
    return implode("\n", $msg);
}

function getStatusIcon($folder, $ckFile, $ckHash)
{
    if (!$ckFile) {
        return '<i class="text-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="1em" height="1em">
                        <path d="M0 64C0 28.7 28.7 0 64 0L224 0l0 128c0 17.7 14.3 32 32 32l128 0 0 38.6C310.1 219.5 256 287.4 256 368c0 59.1 29.1 111.3 73.7 143.3c-3.2 .5-6.4 .7-9.7 .7L64 512c-35.3 0-64-28.7-64-64L0 64zm384 64l-128 0L256 0 384 128zm48 96a144 144 0 1 1 0 288 144 144 0 1 1 0-288zm0 240a24 24 0 1 0 0-48 24 24 0 1 0 0 48zm0-192c-8.8 0-16 7.2-16 16l0 80c0 8.8 7.2 16 16 16s16-7.2 16-16l0-80c0-8.8-7.2-16-16-16z" fill="currentColor" />
                    </svg>
                </i>';
    }
    if (isset($ckHash['error'][$folder])) {
        if (isset($ckHash['newfile'][$folder])) {
            return '<i class="text-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="1em" height="1em">
                            <path d="M0 64C0 28.7 28.7 0 64 0L224 0l0 128c0 17.7 14.3 32 32 32l128 0 0 38.6C310.1 219.5 256 287.4 256 368c0 59.1 29.1 111.3 73.7 143.3c-3.2 .5-6.4 .7-9.7 .7L64 512c-35.3 0-64-28.7-64-64L0 64zm384 64l-128 0L256 0 384 128zm48 96a144 144 0 1 1 0 288 144 144 0 1 1 0-288zm59.3 107.3c6.2-6.2 6.2-16.4 0-22.6s-16.4-6.2-22.6 0L432 345.4l-36.7-36.7c-6.2-6.2-16.4-6.2-22.6 0s-6.2 16.4 0 22.6L409.4 368l-36.7 36.7c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0L432 390.6l36.7 36.7c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6L454.6 368l36.7-36.7z" fill="currentColor" />
                        </svg>
                    </i>
                    <i class="text-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="1em" height="1em">
                            <path d="M0 64C0 28.7 28.7 0 64 0L224 0l0 128c0 17.7 14.3 32 32 32l128 0 0 38.6C310.1 219.5 256 287.4 256 368c0 59.1 29.1 111.3 73.7 143.3c-3.2 .5-6.4 .7-9.7 .7L64 512c-35.3 0-64-28.7-64-64L0 64zm384 64l-128 0L256 0 384 128zm48 96a144 144 0 1 1 0 288 144 144 0 1 1 0-288zm16 80c0-8.8-7.2-16-16-16s-16 7.2-16 16l0 48-48 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l48 0 0 48c0 8.8 7.2 16 16 16s16-7.2 16-16l0-48 48 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-48 0 0-48z" fill="currentColor" />
                        </svg>
                    </i>';
        }
        return '<i class="text-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="1em" height="1em">
                        <path d="M0 64C0 28.7 28.7 0 64 0L224 0l0 128c0 17.7 14.3 32 32 32l128 0 0 38.6C310.1 219.5 256 287.4 256 368c0 59.1 29.1 111.3 73.7 143.3c-3.2 .5-6.4 .7-9.7 .7L64 512c-35.3 0-64-28.7-64-64L0 64zm384 64l-128 0L256 0 384 128zm48 96a144 144 0 1 1 0 288 144 144 0 1 1 0-288zm59.3 107.3c6.2-6.2 6.2-16.4 0-22.6s-16.4-6.2-22.6 0L432 345.4l-36.7-36.7c-6.2-6.2-16.4-6.2-22.6 0s-6.2 16.4 0 22.6L409.4 368l-36.7 36.7c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0L432 390.6l36.7 36.7c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6L454.6 368l36.7-36.7z" fill="currentColor" />
                    </svg>
                </i>';
    }
    if (isset($ckHash['newfile'][$folder])) {
        return '<i class="text-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="1em" height="1em">
                        <path d="M0 64C0 28.7 28.7 0 64 0L224 0l0 128c0 17.7 14.3 32 32 32l128 0 0 38.6C310.1 219.5 256 287.4 256 368c0 59.1 29.1 111.3 73.7 143.3c-3.2 .5-6.4 .7-9.7 .7L64 512c-35.3 0-64-28.7-64-64L0 64zm384 64l-128 0L256 0 384 128zm48 96a144 144 0 1 1 0 288 144 144 0 1 1 0-288zm16 80c0-8.8-7.2-16-16-16s-16 7.2-16 16l0 48-48 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l48 0 0 48c0 8.8 7.2 16 16 16s16-7.2 16-16l0-48 48 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-48 0 0-48z" fill="currentColor" />
                    </svg>
                </i>';
    }
    return '<i class="text-success">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" width="1em" height="1em">
                    <path d="M0 64C0 28.7 28.7 0 64 0L224 0l0 128c0 17.7 14.3 32 32 32l128 0 0 38.6C310.1 219.5 256 287.4 256 368c0 59.1 29.1 111.3 73.7 143.3c-3.2 .5-6.4 .7-9.7 .7L64 512c-35.3 0-64-28.7-64-64L0 64zm384 64l-128 0L256 0 384 128zM288 368a144 144 0 1 1 288 0 144 144 0 1 1 -288 0zm211.3-43.3c-6.2-6.2-16.4-6.2-22.6 0L416 385.4l-28.7-28.7c-6.2-6.2-16.4-6.2-22.6 0s-6.2 16.4 0 22.6l40 40c6.2 6.2 16.4 6.2 22.6 0l72-72c6.2-6.2 6.2-16.4 0-22.6z" fill="currentColor" />
                </svg>
            </i>';
}

function CORN_BACKUP_Create()
{
    $backupDir = PATH_UPLOAD . '/autobackup/' . strtolower(date('l'));
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

function BACKUP_getTableStructure()
{
    $db = DB::singleton();
    $structures = [];
    $allTables = BACKUP_GET_TableUse();

    // ดึงรายการตารางทั้งหมด
    $sql = "SHOW FULL TABLES FROM `" . DB_NAME . "`";
    $db->query($sql, __FUNCTION__);
    $existingTables = ['TABLE' => [], 'VIEW' => []];
    while ($db->next_record()) {
        $row = $db->allRows();
        $name = array_values($row)[0];
        $type = $row['Table_type'];
        $existingTables[$type == 'VIEW' ? 'VIEW' : 'TABLE'][] = $name;
    }

    // วิเคราะห์ dependency จาก foreign key
    $dependencies = [];
    $db->query("
        SELECT TABLE_NAME, REFERENCED_TABLE_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = '" . DB_NAME . "'
          AND REFERENCED_TABLE_NAME IS NOT NULL
    ", __FUNCTION__);

    while ($db->next_record()) {
        $row = $db->allRows();
        $dependencies[$row['TABLE_NAME']][] = $row['REFERENCED_TABLE_NAME'];
    }

    $orderedTables = BACKUP_topologicalSort($existingTables['TABLE'], $dependencies);

    foreach ($orderedTables as $table) {
        if (in_array($table, $allTables)) {
            $sql = "SHOW CREATE TABLE `$table`";
            $db->query($sql, __FUNCTION__);
            $db->next_record();
            $row = $db->allRows();
            $structures[] = $row['Create Table'] . ";\n\n";
        }
    }

    if (isset($existingTables['VIEW'])) {
        foreach ($existingTables['VIEW'] as $view) {
            if (in_array($view, $allTables)) {
                $sql = "SHOW CREATE VIEW `$view`";
                $db->query($sql, __FUNCTION__);
                $db->next_record();
                $viewRow = $db->allRows();
                $structures[] = $viewRow['Create View'] . ";\n\n";
            }
        }
    }

    return implode("", $structures);
}

function BACKUP_topologicalSort($tables, $dependencies)
{
    $sorted = [];
    $visited = [];

    $visit = function ($table) use (&$visit, &$sorted, &$visited, $dependencies) {
        if (isset($visited[$table])) return;
        $visited[$table] = true;

        if (!empty($dependencies[$table])) {
            foreach ($dependencies[$table] as $dep) {
                $visit($dep);
            }
        }
        $sorted[] = $table;
    };

    foreach ($tables as $table) {
        $visit($table);
    }

    return array_unique($sorted);
}

function BACKUP_getTableData($backupDir)
{
    $db = DB::singleton();
    $allTables = BACKUP_GET_TableUse();
    $createdFiles = [];
    $existingTables = [];

    $sql = "SHOW FULL TABLES FROM `" . DB_NAME . "`";
    $db->query($sql, __FUNCTION__);
    while ($db->next_record()) {
        $row = $db->allRows();
        if ($row['Table_type'] != 'VIEW') {
            $existingTables[] = array_values($row)[0];
        }
    }

    foreach ($allTables as $table) {
        if (in_array($table, $existingTables)) {
            $sql = "SELECT * FROM `$table`";
            $db->query($sql, __FUNCTION__);

            if ($db->num_rows() > 0) {
                $insertStatements = [];
                $insertStatements[] = "-- Data for table `$table`\n";
                $insertStatements[] = "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
                $insertStatements[] = "-- Database: " . DB_NAME . "\n\n";
                $insertStatements[] = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
                $insertStatements[] = "SET time_zone = \"+00:00\";\n\n";

                while ($db->next_record()) {
                    $row = $db->allRows();
                    $columns = array_keys($row);
                    $values = [];

                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }

                    $insertStatements[] = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                }

                $tableFile = $backupDir . '/data_' . $table . '.sql';
                $content = implode("", $insertStatements);

                if (file_put_contents($tableFile, $content)) {
                    $createdFiles[] = $tableFile;
                }
            }
        }
    }
    return $createdFiles;
}

function BACKUP_GET_TableUse()
{
    global $aQData, $aConfig;
    $md5Q = md5('BK' . _TIME_);
    if (isset($aQData[$md5Q])) {
        return $aQData[$md5Q];
    } else {
        $allTables = [];
        if (isset($aConfig['aModuleUse']) && count($aConfig['aModuleUse']) > 0) {
            foreach ($aConfig['aModuleUse'] as $v) {
                $fmodule = PATH_MODULE . '/' . $v . '/aModuleConfig.php';
                $fcore = PATH_CORE . '/' . $v . '/aModuleConfig.php';
                $incDb = is_file($fmodule) ? $fmodule : $fcore;

                if (is_file($incDb)) {
                    $aTablename = [];
                    include $incDb;
                    if (isset($aTablename) && is_array($aTablename)) {
                        foreach ($aTablename as $tableName) {
                            if (!in_array($tableName, $allTables)) {
                                $allTables[] = $tableName;
                            }
                        }
                    }
                }
            }
        }
    }
    $aQData[$md5Q] = $allTables;
    return $allTables;
}

function Insert_HashError()
{
    $fname = PATH_UPLOAD . '/hash/hash.txt';
    if (!file_exists($fname)) {
        return '';
    }

    $msg = [];
    $ckHash = read_txt_json_Hash($fname);

    if (!empty($ckHash['error'])) {
        $msg[] = 'ไฟล์ที่ถูกแก้ไข:';
        foreach ($ckHash['error'] as $v) {
            if (is_array($v)) {
                $msg = array_merge($msg, $v);
            } else {
                $msg[] = $v;
            }
        }
    }

    if (!empty($ckHash['newfile'])) {
        $msg[] = '';
        $msg[] = 'ไฟล์ที่ถูกเพิ่มใหม่:';
        foreach ($ckHash['newfile'] as $v) {
            if (is_array($v)) {
                $msg = array_merge($msg, $v);
            } else {
                $msg[] = $v;
            }
        }
    }

    return implode("\n", $msg);
}

function saveHashLog($msg = '')
{
    if ($msg === '' || $msg === null) {
        $msg = 'ไม่พบความผิดปกติ';
    }

    DB_ADD("hash_log", [
        'message' => $msg,
        'created_at' => date("Y-m-d H:i:s")
    ]);
}
