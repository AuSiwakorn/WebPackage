<?php
require_once __DIR__ . '/inc/auth.php';

logout_user();
header('Location: ' . url('login.php'));
exit;
