////////////////////////////////// box style //////////////////////////////////////
<div style="padding:5px;">
	<div class="ui-widget-content" style="padding:10px;">
	</div>
</div>


<div class="tableclass">
	<table border="0" align="center" cellpadding="9" cellspacing="0" width="99%">
		<tr class="ttop" style="height:38px;">
			<td>xxx</td>
		</tr>
	</table>
</div>
////////////////////////////////// get images on folder ////////////////////////
<img src="<?php echo url_images_module('banner', 'delete.gif'); ?>" border="0" alt="delete" />


////////////////////////////////// input event loading ////////////////////////
<input type="text" name="money" placeholder="ไม่ต้องใส่เครื่องหมายลูกน้ำ (,)" required style="width:200px;" />

mb_substr($str, 0, 20, "UTF-8");

=============== change lang =============
/admin/api/api_lang.php?set=th
/admin/api/api_lang.php?set=en
<?php echo ($lang == 'th') ? '_active' : ''; ?>

switch ($ty)
{
case 'ajax':
break;
}

///////////////////////////////////////////
<script type="text/javascript">
	$(".contentStyle1 .boxLink a").bind("click", function() {
		$(".content").fadeOut(200, function() {
			$(".content").load(path[getID], function() {
				$(".content").fadeIn(300);
			});
		});
	});
	$(function() {
		$('#tabs').tabs();
		$("#datepicker").datepicker();
		$('.viewImgBox').fancybox();
		$('.openlink').click(function() {
			$.fancybox({
				'href': $(this).attr('src')
			});
		});
	});
</script>


<script type="text/javascript">
	$(document).ready(function() {
		$(".submitdata").click(function() {
			$.post("/admin/api/post_member.php", $("#registerform").serialize(), function(data) {
				if (data == 1) {
					$(".iserror").hide();
					$(".isok").show();
				} else {
					$(".isok").hide();
					$(".iserror").show();
				}
				//$('.datax').html(data);
			});
		});
	});
</script>
<?php echo mb_substr($str, 0, 200, 'UTF-8'); ?>

TXT INI MENU
link="index.php?module=siteconfig&mp=html&inc=txt&n=footeraddress";