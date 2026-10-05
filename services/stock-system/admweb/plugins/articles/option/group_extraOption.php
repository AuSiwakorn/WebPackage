<?php if ($isOpens['isExtraOptionGroup']) { ?>
<div class="panel">
	<div class="panel-body">
		<div class="form-horizontal">
			<p class="text-main text-bold text-uppercase mainTxtBorderGreen"><?php echo $tagname['isExtraOptionGroup']; ?></p>
			<div class="dropzone-container">
				<div class="fallback" align="left">
					<div class="col-sm-12" style="padding-bottom: 10px;">
						<?php 
						foreach ($aArticleConfig['aExtraOptionGroup'] as $k => $v){
							$se = (@$aGroupEdit['extraOption'] == $k) ? ' checked="checked" ' : '';
							echo '<label class="col-sm-12"><div class="extraOption"><input name="extraOption" type="radio" id="extraOption" class="permissioncheck" value="'.$k.'" '.@$se.' /> '. $v .' </div></label>';
						}
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php } ?>