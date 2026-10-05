<?php
header("X-Robots-Tag: noindex, nofollow", true);
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
// header("Content-Security-Policy: default-src 'self'; 
//         script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; 
//         style-src  'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; 
//         font-src   'self' https://fonts.gstatic.com;");
ob_start();
session_start();
///////// config /////////
include dirname(__FILE__) . '/include/conf.ini.php';
include PATH_PLUGIN . '/db/db.php';
include PATH_ADMIN . '/include/function.php';
include PATH_ADMIN . '/include/function_db.php';
include PATH_ADMIN . '/include/fix.req.php';

ob_end_flush();
///////////// default logout ///////////////
include PATH_PLUGIN . '/config/global.php';
include PATH_ADMIN . '/include/class.login.php';

//login_logout::adminLogout();
login_logout::reMoveSessionLogin();

//setRaiseMsg(' ออกจากระบบจัดการเรียบร้อยแล้ว ',_TIME_,0);
login_logout::reDirectToLogin(URL_ADMIN . '/login.php');

ob_end_flush();
exit;
