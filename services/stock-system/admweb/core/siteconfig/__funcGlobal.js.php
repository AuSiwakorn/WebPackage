$(document).ready(function() {
$(".popupimg").colorbox({
height: "85%"
});
});

<?php if (isset($aConfig['isTabLoad']) && $aConfig['isTabLoad'] == true) { ?>

	$(function() {
	//$('#tabs, #tabss').tabs({selected:<?php echo ($lang == 'en') ? '1' : '0'; ?>});
	$(".datepicker").datepicker({
	showButtonPanel: true,
	changeMonth: true,
	changeYear: true
	});
	$(".datepicker2").datepicker({
	showButtonPanel: true,
	changeMonth: true,
	changeYear: true,
	onSelect: function(dateText) {
	$('.isNotEndDate').val(dateText);
	}
	});
	$('.isEndDateShow').click(function() {
	if ($('.isEndDateShow').is(':checked')) {
	$('.isEnddate').hide();
	} else {
	$('.isEnddate').show();
	}
	});
	});
<?php  } ?>

function FunCheckedAll(ch, cname) {
if (ch == true) {
$('.' + cname).attr('checked', true);
} else {
$('.' + cname).attr('checked', false);
}
}

// function displayPopup(id) {
// var html = $('#' + id + ' .html').html();
// var title = $('#' + id + ' .title').text();
// bootbox.dialog({
// title: title,
// message: html,
// /*
// buttons: {
// confirm: {
// label: "Save"
// }
// }
// */
// });
// }

function getPathShow(dname, v) {
$('.' + dname).val(v);
}
function getPathShow_forattachfile(dname, input) {
if (input.files && input.files.length > 0) {
$('.' + dname).val(input.files[0].name); // ✅ เอาเฉพาะชื่อไฟล์มาใส่
} else {
$('.' + dname).val(''); // ❌ ไม่ได้เลือกไฟล์ → เคลียร์ค่า
}
}
function get2Path(gname, pname) {
$('#' + pname).text($('#' + gname).val());
}

function getUpPath(up, idshowtext) {
$('#' + idshowtext).text(up.value);
}