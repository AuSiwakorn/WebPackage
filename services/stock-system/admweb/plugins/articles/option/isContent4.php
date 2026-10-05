<?php if ($isOpens['isContent4']) { ?>
	<div class="form-group">
		<div><?php echo @$tagname['isContent4']; ?></div>
		<div>
			<?php if ($isOpens['EDTNAME'] === 'ckeditor5') {
			?>
				<div id="editor4_<?php echo $kLang; ?>" style="min-height: 400px;"><?php echo @$content4; ?></div>
				<textarea name="frm[<?php echo $kLang; ?>][content4]"
					id="output4_<?php echo $kLang; ?>"
					style="display:none;"></textarea>
			<?php } else {
			?>
				<textarea name="frm[<?php echo $kLang; ?>][content4]" class="<?php echo ($isOpens['EDTNAME'] === 'ckeditor') ? 'ckeditor' : 'demo-summernote'; ?>" rows="5" style="width:100%;"><?php echo @$content4; ?></textarea>
			<?php } ?>
		</div>
	</div>
<?php } ?>