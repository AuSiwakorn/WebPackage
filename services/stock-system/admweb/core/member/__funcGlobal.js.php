function checkAllPermission() {
if ($('.checkall').is(':checked')) {
$('.permissioncheck').attr('checked', 'checked');
} else {
$('.permissioncheck').attr('checked', false);
}
}