<?php 
$ucaf = _admin_buil_link('index.php?module='.$module.'&mp='.$mp.'&inc=edit&keysname='.$keysname.'&id='.$aArticleRows['articles_id'].'&ac=delContentAttF&langdel='.$kLang);
?>
			<div style="margin-bottom: 10px;">
				<?php echo @$tagname['isContentAttach']; ?>
				<div style="background-color: #EEE;border: 1px solid #CCC;padding: 5px;">
					<?php if ($aArticleRows['content'][$kLang]['contentAttach'] == '') {?>
					<input type="file" name="contectAttach[<?php echo $kLang; ?>]">
					<?php } else { ?>
					<a href="<?php echo $ucaf; ?>" style="color: #ff0000;" onclick="return confirm('Delete?');">[ ลบไฟล์นี้ ]</a>
					<a href="<?php echo URL_UPLOAD.'/'.$aArticleRows['content'][$kLang]['contentAttach']; ?>" target="_blank"><?php echo $aArticleRows['content'][$kLang]['contentAttach']; ?></a>
					<?php } ?>
				</div>
			</div>