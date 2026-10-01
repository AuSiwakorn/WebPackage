<?php
/* เข้าที่โฟลเดอร์ demo/ → ส่งไปหน้า login หรือหน้าแรกของระบบ */
require_once dirname(__FILE__) . '/include/function.php';

header('Location: ' . url(is_logged_in() ? 'dashboard.php' : 'login.php'));
exit;
