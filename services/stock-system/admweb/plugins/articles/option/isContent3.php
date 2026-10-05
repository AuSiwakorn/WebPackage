<?php if ($isOpens['isContent3']) { ?>
	<div class="form-group">
		<div><?php echo @$tagname['isContent3']; ?></div>
		<div>
			<?php if ($isOpens['EDTNAME'] === 'ckeditor5') {
			?>
				<div id="editor3_<?php echo $kLang; ?>" style="min-height: 400px;"><?php echo @$content3; ?></div>
				<textarea name="frm[<?php echo $kLang; ?>][content3]"
					id="output3_<?php echo $kLang; ?>"
					style="display:none;"></textarea>
			<?php } else {
			?>
				<textarea name="frm[<?php echo $kLang; ?>][content3]" class="<?php echo ($isOpens['EDTNAME'] === 'ckeditor') ? 'ckeditor' : 'demo-summernote'; ?>" rows="5" style="width:100%;"><?php echo @$content3; ?></textarea>
			<?php } ?>
		</div>
	</div>
<?php } ?>