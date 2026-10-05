<?php
$Images_total = 4;
$Images_num_rows = ($Images_total / 2);
?>
<div class="row">
	<div class="col-sm-12">
		<p class="text-main text-bold text-uppercase mainTxtBorderGreen">ความกว้างของรูปที่เหมาะสมคือ <?php echo @$widthIcon; ?> px</p>
		<div class="dropzone-container">
			<div class="fallback">
				<?php for ($ii = 0; $ii < 2; $ii++) { ?>
					<div class="col-sm-6">
						<?php
						for ($jj = 0; $jj < $Images_num_rows; $jj++) {
							echo '
					<div class="form-group">
						<div class="input-group mar-btm">
							<input type="text" placeholder="Images path" class="form-control imgpathshow_' . $jj . $ii . '" readonly="readonly">
							<span class="input-group-btn">
								<span class="pull-left btn btn-mint btn-file" style="font: 18px \'db_ozone_xregular\' !important;padding: 5.5px 15px;"> 
									<i class="demo-pli-upload-to-cloud icon-5x" style="font-size:18px;"></i> Browse... <input type="file" name="attachFile[]" id="attachFile[]" onchange="getPathShow(\'imgpathshow_' . $jj . $ii . '\', this.value);">
								</span>
							</span>
						</div>
					</div>
					';
						}
						?>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>