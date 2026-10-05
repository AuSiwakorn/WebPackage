<?php if ($isOpens['isContentExtra1']) { ?>
	<div class="form-group">
		<div><?php echo $tagname['isContentExtra1']; ?></div>
		<div><input type="text" name="frm[<?php echo $kLang; ?>][content_extra1]" class="form-control" placeholder="<?php echo $ex['isContentExtra1']; ?>" value="<?php echo htmlspecialchars($contentExtra1, ENT_QUOTES, 'UTF-8'); ?>" /></div>
	</div>
<?php } ?>

<?php if ($isOpens['isContentExtra2']) { ?>
	<div class="form-group">
		<div><?php echo $tagname['isContentExtra2']; ?></div>
		<div><input type="text" name="frm[<?php echo $kLang; ?>][content_extra2]" class="form-control" placeholder="<?php echo $ex['isContentExtra2']; ?>" value="<?php echo htmlspecialchars($contentExtra2, ENT_QUOTES, 'UTF-8'); ?>" /></div>
	</div>
<?php } ?>

<?php if ($isOpens['isContentExtra3']) { ?>
	<div class="form-group">
		<div><?php echo $tagname['isContentExtra3']; ?></div>
		<div><input type="text" name="frm[<?php echo $kLang; ?>][content_extra3]" class="form-control" placeholder="<?php echo $ex['isContentExtra3']; ?>" value="<?php echo htmlspecialchars($contentExtra3, ENT_QUOTES, 'UTF-8'); ?>" /></div>
	</div>
<?php } ?>