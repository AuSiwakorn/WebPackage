<?php
class UpFile
{
    private $baseDir;
    private $allowedExtensions = ['jpg', 'jpeg', 'mov', 'mp3', 'mp4', 'png', 'gif', 'webp', 'avif', 'pdf', 'docx', 'xlsx', 'txt', 'zip', 'rar'];

    /**
     * Constructor
     *
     * @param string $baseDir  base directory สำหรับเก็บไฟล์ที่อัปโหลด (default: PATH_UPLOAD)
     * @param string $type     'picture' = ใช้ $allowExtention จาก fix file (รูปภาพเท่านั้น)
     *                         'file'    = ใช้ default list (รูป + เอกสาร + มีเดีย) [default]
     */
    public function __construct($baseDir = PATH_UPLOAD, string $type = 'file')
    {
        $this->baseDir = rtrim($baseDir, '/');
        if ($type === 'picture') {
            global $allowExtention;
            $this->allowedExtensions = $allowExtention ?? ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        }
        if (!is_dir($this->baseDir) && !mkdir($this->baseDir, 0755, true) && !is_dir($this->baseDir)) {
            throw new RuntimeException('ไม่สามารถสร้าง base directory: ' . $this->baseDir);
        }
    }

    /**
     * Get the file extension of a given filename.
     *
     * @param string $fileName the filename to get the extension from.
     *
     * @return string the file extension in lowercase.
     */
    public function getExtension($filename)
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * ลบไฟล์โดยอ้าง path แบบ relative กับ baseDir
     * ป้องกัน path traversal โดยตรวจสอบว่า resolved path อยู่ภายใน baseDir จริง
     *
     * @param string $filePath path ของไฟล์ที่ต้องการลบ (relative กับ baseDir)
     * @return bool true ถ้าลบสำเร็จ, false ถ้าไฟล์ไม่มีอยู่หรือ path ไม่ถูกต้อง
     */
    public function deleteFile($filePath)
    {
        if (empty($filePath)) return false;

        $fullPath = $this->baseDir . '/' . ltrim($filePath, '/');
        $real = realpath($fullPath);
        $base = realpath($this->baseDir);
        if ($real === false || $base === false || strpos($real, $base) !== 0) {
            return false;
        }
        if (!is_file($real)) return false;

        return unlink($real);
    }

    /**
     * Upload a file to the server.
     *
     * @param array $allowedExts ระบุนามสกุลไฟล์ที่อนุญาตให้อัปโหลด
     * @param string $tmpName ชื่อไฟล์ชั่วคราวของไฟล์ที่ถูกอัปโหลด
     * @param string $originalName ชื่อไฟล์ต้นฉบับของไฟล์ที่ถูกอัปโหลด (เพื่อเอานามสกุลไฟล์)
     * @param string $targetFolder โฟลเดอร์ปลายทางสำหรับเก็บไฟล์
     * @param string $customFileName (ไม่บังคับ) ระบุชื่อไฟล์ที่ต้องการกำหนดเอง
     * @param string $oldFilePath (ไม่บังคับ) ระบุพาธของไฟล์เก่าที่ต้องการลบ
     * @return string พาธของไฟล์ที่อัปโหลดสำเร็จ หรือ false หากล้มเหลว
     *
     * Example (Add - เพิ่มข้อมูลใหม่):
     * <code>
     * $up = new UpFile();
     * $savedPath = false;
     *
     * if (!empty($_FILES['image']['tmp_name'])) {
     *     $savedPath = $up->uploadStandard(
     *         ['jpg', 'jpeg', 'png', 'gif'],   // นามสกุลที่อนุญาต
     *         $_FILES['image']['tmp_name'],    // tmp name จาก $_FILES
     *         $_FILES['image']['name'],        // ชื่อไฟล์ต้นฉบับ
     *         'products/images'                // โฟลเดอร์ปลายทาง (relative กับ baseDir)
     *     );
     *     // $savedPath ตัวอย่าง: "products/images/64f1a2_1715500000.jpg"
     * }
     *
     * if ($savedPath !== false) {
     *     // บันทึก $savedPath ลงฐานข้อมูล
     *     // INSERT INTO products (image, ...) VALUES (?, ...)
     * }
     * </code>
     *
     * Example (Update - แก้ไขข้อมูลเดิม):
     * <code>
     * $up = new UpFile();
     *
     * // ดึง path ไฟล์เก่าจากฐานข้อมูลก่อน เช่น $oldPath = "products/images/abc_123.jpg";
     * $oldPath = $row['image'];
     * $savedPath = $oldPath; // ค่าเริ่มต้น = ใช้รูปเดิมถ้าผู้ใช้ไม่อัปโหลดใหม่
     *
     * if (!empty($_FILES['image']['tmp_name'])) {
     *     $newPath = $up->uploadStandard(
     *         ['jpg', 'jpeg', 'png', 'gif'],
     *         $_FILES['image']['tmp_name'],
     *         $_FILES['image']['name'],
     *         'products/images',
     *         null,        // ใช้ชื่อไฟล์อัตโนมัติ (uniqid + time)
     *         $oldPath     // ลบไฟล์เก่าออกอัตโนมัติ
     *     );
     *     if ($newPath !== false) $savedPath = $newPath;
     * }
     *
     * // UPDATE products SET image = ? WHERE id = ?  ด้วยค่า $savedPath
     * </code>
     */
    public function uploadStandard($allowedExts, $tmpName, $originalName, $targetFolder, $customFileName = null, $oldFilePath = null)
    {
        if (!is_uploaded_file($tmpName)) return false;

        $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!empty($allowedExts)) {
            $allowedExts = array_map('strtolower', $allowedExts);
            if (!in_array($fileExt, $allowedExts)) return false;
        } else {
            if (!in_array($fileExt, $this->allowedExtensions)) return false;
        }

        $targetFolder = trim($targetFolder, '/');
        $fullDirPath = $this->baseDir . '/' . $targetFolder;

        if (!is_dir($fullDirPath)) mkdir($fullDirPath, 0755, true);

        if (!empty($oldFilePath)) {
            $fullOldPath = $this->baseDir . '/' . ltrim($oldFilePath, '/');
            if (is_file($fullOldPath)) unlink($fullOldPath);
        }

        $fileName = $customFileName ? $customFileName . '.' . $fileExt : uniqid() . '_' . time() . '.' . $fileExt;
        $fullFilePath = $fullDirPath . '/' . $fileName;

        if (is_file($fullFilePath)) unlink($fullFilePath);

        if (move_uploaded_file($tmpName, $fullFilePath)) return $targetFolder . '/' . $fileName;

        return false;
    }

    /**
     * รับไฟล์แบบ chunk แล้วประกอบกลับเป็นไฟล์เดียวเมื่อครบทุก chunk
     *
     * @param array $file ข้อมูลไฟล์จาก $_FILES['file']
     * @param array $post ข้อมูลจาก $_POST (ต้องมี fileId, chunkIndex, totalChunks, fileName)
     * @param array|null $allowedExts (ไม่บังคับ) ระบุนามสกุลไฟล์ที่อนุญาต ถ้าเป็น null ใช้ค่า default
     * @return string|null ชื่อไฟล์ปลายทางเมื่ออัปโหลดครบ หรือ null ถ้ายังรอ chunk
     * @throws RuntimeException เมื่ออัปโหลดล้มเหลวหรือข้อมูลไม่ถูกต้อง
     */
    public function handleUpload($file, $post, $allowedExts = null)
    {
        if (empty($file['tmp_name'])) {
            throw new RuntimeException('ไม่พบไฟล์ที่อัปโหลด');
        }

        $fileId      = preg_replace('/[^a-zA-Z0-9]/', '', $post['fileId'] ?? '');
        $chunkIndex  = (int) ($post['chunkIndex'] ?? 0);
        $totalChunks = (int) ($post['totalChunks'] ?? 0);
        $originalName = basename($post['fileName'] ?? '');

        if ($fileId === '' || $totalChunks <= 0 || $originalName === '') {
            throw new RuntimeException('ข้อมูล chunk ไม่ถูกต้อง');
        }

        $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = !empty($allowedExts) ? array_map('strtolower', $allowedExts) : $this->allowedExtensions;
        if (!in_array($fileExt, $allowed, true)) {
            throw new RuntimeException("ไม่อนุญาตให้อัปโหลดไฟล์ .$fileExt");
        }

        $tmpDir = $this->baseDir . '/' . $fileId;
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0755, true) && !is_dir($tmpDir)) {
            throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์ชั่วคราว');
        }

        $chunkPath = $tmpDir . '/chunk_' . $chunkIndex;
        if (!move_uploaded_file($file['tmp_name'], $chunkPath)) {
            throw new RuntimeException("ไม่สามารถบันทึก chunk ที่ $chunkIndex");
        }

        if (count(glob($tmpDir . '/chunk_*')) < $totalChunks) {
            return null;
        }

        $finalName = time() . '_' . uniqid() . '.' . $fileExt;
        $finalPath = $this->baseDir . '/' . $finalName;
        $out = fopen($finalPath, 'wb');
        if ($out === false) {
            throw new RuntimeException('ไม่สามารถสร้างไฟล์ปลายทาง');
        }

        try {
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkFile = $tmpDir . '/chunk_' . $i;
                if (!is_file($chunkFile)) {
                    throw new RuntimeException("Chunk หายไป: chunk_$i");
                }
                $in = fopen($chunkFile, 'rb');
                stream_copy_to_stream($in, $out);
                fclose($in);
            }
        } finally {
            fclose($out);
        }

        array_map('unlink', glob($tmpDir . '/chunk_*'));
        @rmdir($tmpDir);

        return $finalName;
    }
}
