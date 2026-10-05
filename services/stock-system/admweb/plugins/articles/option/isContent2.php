<?php if ($isOpens['isContent2']) { ?>
	<div class="form-group">
		<div><?php echo @$tagname['isContent2']; ?></div>
		<div>
			<?php if ($isOpens['EDTNAME'] === 'ckeditor5') {
			?>
				<div id="editor2_<?php echo $kLang; ?>" style="min-height: 400px;"><?php echo @$content2; ?></div>
				<textarea name="frm[<?php echo $kLang; ?>][content2]"
					id="output2_<?php echo $kLang; ?>"
					style="display:none;"></textarea>
			<?php } else {
			?>
				<textarea name="frm[<?php echo $kLang; ?>][content2]" class="<?php echo ($isOpens['EDTNAME'] === 'ckeditor') ? 'ckeditor' : 'demo-summernote'; ?>" rows="5" style="width:100%;"><?php echo @$content2; ?></textarea>
			<?php } ?>
		</div>
	</div>
<?php } ?>