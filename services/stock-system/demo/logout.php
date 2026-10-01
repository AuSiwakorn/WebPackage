<?php
require_once dirname(__FILE__) . '/include/function.php';

logout_user();
header('Location: ' . url('login.php'));
exit;
