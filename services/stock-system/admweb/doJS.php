<?php
header("Content-Type: application/javascript; charset=UTF-8");
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
$mod = isset($_REQUEST['module']) ? $_REQUEST['module'] : '';
include dirname(__FILE__) . '/include/conf.ini.php';
$fmodule = PATH_MODULE . '/' . $mod . '/__funcGlobal.js.php';
$fcore = PATH_CORE . '/' . $mod . '/__funcGlobal.js.php';
$jsfile = is_file($fmodule) ? $fmodule : $fcore;
if (is_file($jsfile)) {
    include PATH_PLUGIN . '/db/db.php';
    include PATH_PLUGIN . '/mail/function.php';
    include PATH_ADMIN . '/include/function.php';
    include PATH_ADMIN . '/include/function_db.php';
    include PATH_ADMIN . '/include/fix.req.php';
    include PATH_PLUGIN . '/config/global.php';
    include PATH_ADMIN . '/include/func.userinfo.php';
    include PATH_ADMIN . '/include/func.useronline.php';
    include PATH_ADMIN . '/include/class.login.php';
    include PATH_ADMIN . '/include/class.permit.php';

    include PATH_PLUGIN . '/detect/function.php';
    _DETECT_AO();

    if (is_file(PATH_ADMIN . '/function/func.php')) {
        include PATH_ADMIN . '/function/func.php';
    }

    $module             = REQ_get('module', 'get', 'str', '');
    $modulePage         = REQ_get('mp', 'get', 'str', '');
    $ac                  = REQ_get('ac', 'requset', 'str', '');

    define("_MODULE_", $module);
    define("_MP_", $modulePage);
    define("_AC_", $ac);

    $ok = login_logout::checkLogin();

    include $jsfile;
}
