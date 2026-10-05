<?php
/**
 * FILE: aowebdata/modules/dashboard/widgets.php
 * ROLE: ทะเบียน widget ทั้งหมดของหน้า dashboard (v2)
 * DEPENDS: อ่านโดย hooks/main_v2.php, core/member/main_edit_group.php, aowebdata/permission/default.php
 * TABLES: -
 */
$_aWidgetList = array();

// id ต้องตรงชื่อไฟล์ widgets/{id}.php · perm = dw_{id}
$_aWidgetList['admin_count']        = array('name' => 'การ์ด: จำนวนผู้ดูแล',    'type' => 'card',  'col' => 3,  'perm' => 'dw_admin_count');
$_aWidgetList['member_count']       = array('name' => 'การ์ด: จำนวนสมาชิก',     'type' => 'card',  'col' => 3,  'perm' => 'dw_member_count');
$_aWidgetList['online_now']         = array('name' => 'การ์ด: ออนไลน์ตอนนี้',   'type' => 'card',  'col' => 3,  'perm' => 'dw_online_now');
$_aWidgetList['login_today']        = array('name' => 'การ์ด: เข้าระบบวันนี้',   'type' => 'card',  'col' => 3,  'perm' => 'dw_login_today');
$_aWidgetList['recent_admin_login'] = array('name' => 'ตาราง: เข้าสู่ระบบล่าสุด', 'type' => 'table', 'col' => 12, 'perm' => 'dw_recent_admin_login');
