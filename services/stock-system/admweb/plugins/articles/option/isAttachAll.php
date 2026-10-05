<?php if ($isOpens['isIcon']) { ?>
	<div class="panel">
		<div class="panel-body">
			<div class="form-horizontal">
				<p class="text-main text-bold text-uppercase mainTxtBorderGreen"><?php echo @$tagname['isIcon']; ?></p>
				<div class="dropzone-container">
					<div class="fallback" align="center">
						<div style="height: 80px;overflow: hidden;">
							<?php
							if (isset($aArticleRows['icon']) && $aArticleRows['icon'] != '') {
								$display = '';
								$isIcon = 'isicontrue';
								$url_img = URL_UPLOAD . '/' . $aArticleRows['icon'];
								$text_del = '<a href="#" id="textDel" onclick="event.preventDefault();hiddenImage();" style="color: #FF0048;font: 16px \'db_ozone_xregular\' !important;">[ ลบไฟล์นี้ ]</a>';
							} else {
								$url_img = '';
								$text_del = '';
								$isIcon = '';
								$display = 'display: none;';
							}
							?>
							<span class="btn displayselect" style="padding-top: 8px;font-size: 18px;height: 80px;<?php echo $display; ?>">
								<a href="<?php echo $url_img; ?>" target="_blank"><img src="<?php echo $url_img; ?>" border="0" class="blah" style="margin-top: -9px;margin-left: -14px; width: auto; height: 80px;" /></a>
							</span>
							<span class="btn btn-mint btn-file" style="padding-top: 8px;font-size: 18px;width: 80px;height: 80px;">
								<i class="demo-pli-upload-to-cloud" style="font-size: 25px;"></i><br />เลือกไฟล์..<input type="file" name="iconview" id="iconview" onchange="get2Path('iconview', 'showfile');readImageURL(this);">
							</span>
						</div>
						<?php echo '<div style="margin:2px;font-size: 18px;color: #efa660;"> ขนาดที่แนะนำ : ' . @$aArticleConfig['widthIcon_notics'] . '</div>'; ?>
						<?php echo $text_del; ?>
						<div id="showfile" style="height: 20px;"></div>
						<input hidden type="text" class="icon" name="icon" value="<?php echo $url_img ?>">
						<input hidden type="text" id="isIconView" name="isIconView" value="<?php echo $isIcon; ?>">
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>

<?php if ($isOpens['isIcon2']) { ?>
	<div class="panel">
		<div class="panel-body">
			<div class="form-horizontal">
				<p class="text-main text-bold text-uppercase mainTxtBorderGreen"><?php echo @$tagname['isIcon2']; ?></p>
				<div class="dropzone-container">
					<div class="fallback" align="center">
						<div style=" height: 80px;overflow: hidden;">
							<?php
							if (isset($aArticleRows['icon2']) && $aArticleRows['icon2'] != '') {
								$display = '';
								$isIcon2 = 'isicontrue';
								$url_img = URL_UPLOAD . '/' . $aArticleRows['icon2'];
								$text_del = '<a href="#" id="textDel2" onclick="event.preventDefault();hiddenImage(\'2\');" style="color: #FF0048;font: 16px \'db_ozone_xregular\' !important;">[ ลบไฟล์นี้ ]</a>';
							} else {
								$url_img = '';
								$isIcon2 = '';
								$text_del = '';
								$display = 'display: none;';
							}
							?>
							<span class="btn displayselect2" style="padding-top: 8px;font-size: 18px;height: 80px;<?php echo $display; ?>">
								<a href="<?php echo $url_img; ?>" target="_blank"><img src="<?php echo $url_img; ?>" border="0" class="blah2" style="margin-top: -9px;margin-left: -14px; width: auto; height: 80px;" /></a>
							</span>
							<span class="btn btn-mint btn-file" style="padding-top: 8px;font-size: 18px;width: 80px;height: 80px;">
								<i class="demo-pli-upload-to-cloud" style="font-size: 25px;"></i><br />เลือกไฟล์..<input type="file" name="iconview2" id="iconview2" onchange="get2Path('iconview2', 'showfile2');readImageURL(this,'2');">
							</span>
						</div>
						<?php echo '<div style="margin:2px;font-size: 18px;color: #efa660;"> ขนาดที่แนะนำ : ' . @$aArticleConfig['widthIcon_notics2'] . '</div>'; ?>
						<?php echo $text_del; ?>
						<div id="showfile2" style="height: 20px;"></div>
						<input hidden type="text" class="icon2" name="icon2" value="<?php echo $url_img ?>">
						<input hidden type="text" id="isIconView2" name="isIconView2" value="<?php echo $isIcon2; ?>">
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>

<?php if ($isOpens['isAttach']) { ?>
	<div class="panel">
		<div class="panel-body">
			<div class="form-horizontal">
				<p class="text-main text-bold text-uppercase mainTxtBorderGreen">File Attach</p>
				<div class="dropzone-container">
					<div class="fallback">
						<?php
						if (isset($aArticleRows['file_attach']) && $aArticleRows['file_attach'] != '') {
							echo '<a href="' . URL_UPLOAD . '/' . @$aArticleRows['file_attach'] . '" target="_blank"><i class="fa fa-file-pdf-o" style="font-size: 22px;color: #FF0048;"></i> เปิดไฟล์แนบ </a>';
							echo '<br/><a href="' . _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=edit&keysname=' . $keysname . '&id=' . $aArticleRows['articles_id'] . '&ac=delfile') . '" style="color: #FF0048;font-size: 16px;">[ ลบไฟล์นี้ ]</a>';
						} else {
							echo '
						<div class="form-group text-center" align="center">
							<span class="pull-center btn btn-primary btn-file" style="font-size: 18px;"> 
								<i class="demo-pli-upload-to-cloud icon-5x" style="font-size: 20px;"></i> Browse... <input type="file" name="attach" id="fileupA" onchange="get2Path(\'fileupA\', \'fileup\');">
							</span>
							<br clear="all" />
							<div id="fileup" style="height: 20px;"></div>
						</div>
						';
						} ?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>