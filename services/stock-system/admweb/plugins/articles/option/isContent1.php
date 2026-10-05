<?php if ($isOpens['isContent']) { ?>
	<div class="form-group">
		<div><?php echo @$tagname['isContent']; ?></div>
		<div>
			<?php if ($isOpens['EDTNAME'] === 'ckeditor5') {
			?>
				<div id="editor1_<?php echo $kLang; ?>" style="min-height: 600px;"><?php echo @$content; ?></div>
				<textarea name="frm[<?php echo $kLang; ?>][content]"
					id="output1_<?php echo $kLang; ?>"
					rows="5" style="display:none; width:100%;"></textarea>
			<?php } else {
			?>
				<textarea name="frm[<?php echo $kLang; ?>][content]" class="<?php echo ($isOpens['EDTNAME'] === 'ckeditor') ? 'ckeditor' : 'demo-summernote'; ?>" rows="5" style="width:100%;"><?php echo @$content; ?></textarea>
			<?php } ?>
		</div>
	</div>
<?php } ?>