<?php 
$md5 = @$_REQUEST['md5'];
$filehelp = PATH_HELP . '/'.$md5.'.html'; 
?>
<script type="text/javascript">
	$(document).ready(function() {
		$(".button").button();
	});
</script>
<div style="background-color:#ccc;">
<table width="100%" cellpadding="7">
	<tr>
		<td align="left"><a href="popFrame.php?pg=help&inc=edit&ty=plugin&md5=<?php echo $md5; ?>" class="button">ปรับปรุงไฟล์</a></td>
		<td align="right">รหัสหน้า : <?php echo $md5; ?></td>
	</tr>
</table>
</div>
<div style="padding:2px; font-size:12px;" align="center"><?php displayRaiseMsg(); ?></div>
<div>
<?php
if (is_file($filehelp)) {
	include($filehelp);
} else {
	echo '<div style="padding: 70px; text-align: center;">ไม่มีข้อมูล วิธีการใช้งานสำหรับหน้าการใช้งานนี้</div>';
}
?>
</div>