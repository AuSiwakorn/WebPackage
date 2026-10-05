<?php
/**
 * FILE: admweb/core/counter/aModuleConfig.php
 * ROLE: ลงทะเบียนตารางและ permission ของ counter module
 * DEPENDS: databases/mysql_default.sql.php
 * TABLES: site_counter
 * TODO:
 *   - [x] ลงทะเบียนตาราง site_counter
 */

$sqlType    = 'php';
$aTablename = array(
    _DBPREFIX_ . 'site_counter',
    _DBPREFIX_ . 'site_counter_ref',
);
$aPermission = array(
    PATH_UPLOAD . '/counter',
);
