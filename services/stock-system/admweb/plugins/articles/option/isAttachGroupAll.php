<?php if ($isOpens['isIconGroup']) { ?>
	<div class="panel">
		<div class="panel-body">
			<div class="form-horizontal">
				<p class="text-main text-bold text-uppercase mainTxtBorderGreen"><?php echo @$tagname['isIconGroup']; ?></p>
				<div class="dropzone-container">
					<div class="fallback" align="center">
						<div style=" width: 80px;height: 80px;border-radius: 50%;overflow: hidden;">
							<?php
							if (isset($aGroupEdit['img']) && $aGroupEdit['img'] != '') {
								$u_del = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=editgroup&id=' . $id . '&keysname=' . $keysname . '&ac=deleteimg');
								$text_del = '<a href="' . $u_del . '" style="color: #FF0048;font: 16px \'db_ozone_xregular\' !important;">[ ลบไฟล์นี้ ]</a>';
								$url_img = URL_UPLOAD . '/' . $aGroupEdit['img'];
								echo '
								<span class="btn" style="padding-top: 8px;font-size: 18px;width: 80px;height: 80px;">
									<a href="' . $url_img . '" target="_blank"><img src="' . $url_img . '" border="0" style="margin-top: -9px;margin-left: -14px; width: auto; height: 80px;" /></a>
								</span>';
							} else {
								echo '
								<span class="btn btn-mint btn-file" style="padding-top: 8px;font-size: 18px;width: 80px;height: 80px;">
									<i class="demo-pli-upload-to-cloud" style="font-size: 25px;"></i><br/>เลือกไฟล์..<input type="file" name="iconview" id="iconview" onchange="get2Path(\'iconview\',\'showfile\');">
								</span>';
							}
							?>
						</div>
						<?php echo '<div style="margin:8px 2px;font-size: 18px;color: #efa660;"> &bull; อัพโหลดไฟล์ขนาดไม่เกิน : ' . (int)(ini_get('upload_max_filesize')) . ' MB  <br /> &bull; เฉพาะไฟล์ JPG, GIF, PNG เท่านั้น</div>'; ?>
						<?php
						if (isset($aGroupEdit['img']) && $aGroupEdit['img'] != '') {
							echo $text_del;
						} else {
							echo "กรุณาใส่รูป";
						}
						?>
						<div id="showfile" tyle="height: 20px;"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>


<?php if ($isOpens['isIconGroup2']) { ?>
	<div class="panel">
		<div class="panel-body">
			<div class="form-horizontal">
				<p class="text-main text-bold text-uppercase mainTxtBorderGreen"><?php echo @$tagname['isIconGroup2']; ?></p>
				<div class="dropzone-container">
					<div class="fallback" align="center">
						<script language="JavaScript">
							function getPath2() {
								var iconview = $('#iconview2').val();
								$('#showfile2').text(iconview);
							}
						</script>
						<div style=" width: 80px;height: 80px;border-radius: 50%;overflow: hidden;">
							<?php
							if (isset($aGroupEdit['icon']) && $aGroupEdit['icon'] != '') {
								$u_del = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=editgroup&id=' . $id . '&keysname=' . $keysname . '&ac=deleteicon');
								$text_del2 = '<a href="' . $u_del . '" style="color: #FF0048;font: 16px \'db_ozone_xregular\' !important;">[ ลบไฟล์นี้ ]</a>';
								$url_img = URL_UPLOAD . '/' . $aGroupEdit['icon'];
								echo '
								<span class="btn" style="padding-top: 8px;font-size: 18px;width: 80px;height: 80px;">
									<a href="' . $url_img . '" target="_blank"><img src="' . $url_img . '" border="0" style="margin-top: -9px;margin-left: -14px; width: auto; height: 80px;" /></a>
								</span>';
							} else {
								echo '
								<span class="btn btn-mint btn-file" style="padding-top: 8px;font-size: 18px;width: 80px;height: 80px;">
									<i class="demo-pli-upload-to-cloud" style="font-size: 25px;"></i><br/>เลือกไฟล์..<input type="file" name="iconview2" id="iconview2" onchange="getPath2();">
								</span>';
							}
							?>
						</div>
						<?php echo '<div style="margin:8px 2px;font-size: 18px;color: #efa660;"> &bull; อัพโหลดไฟล์ขนาดไม่เกิน : ' . (int)(ini_get('upload_max_filesize')) . ' MB  <br /> &bull; เฉพาะไฟล์ JPG, GIF, PNG เท่านั้น</div>'; ?>
						<?php if (isset($aGroupEdit['icon']) && $aGroupEdit['icon'] != '') {
							echo $text_del2;
						} else {
							echo "กรุณาใส่รูป";
						} ?>
						<div id="showfile2" style="height: 20px;"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php }  ?>