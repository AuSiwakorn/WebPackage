<?php if (isset($aPictureAttMark['data']) && count($aPictureAttMark['data']) > 0) { ?>
	<table width="100%" border="0" cellpadding="5" cellspacing="1" bgcolor="#CCCCCC">
		<tr>
			<td bgcolor="#F9F9F9">
				<?php
				foreach ($aPictureAttMark['data'] as $kPic => $vPic) {
					$udelpic = _admin_buil_link("index.php?module=" . $module . "&mp=" . $mp . "&inc=edit&keysname=" . $keysname . "&id=" . $id . "&ac=delpic&fid=" . $vPic['file_id']);
					$uunmark = _admin_buil_link("index.php?module=" . $module . "&mp=" . $mp . "&inc=edit&keysname=" . $keysname . "&id=" . $id . "&ac=unmark&fid=" . $vPic['file_id']);
					$imgdetail = get_images_detail(PATH_UPLOAD . '/' . $vPic['imgPathBig']);
					$urldetail = 'doAjax.php?ty=plugin&module=' . $module . '&mp=' . $mp . '&inc=detailimg&keysname=' . $keysname . '&id=' . $vPic['file_id'];
					$markdiv = '';
					if ($isOpens['isMark'] == true) {
						if ($vPic['ismark'] == 1) {
							$isMark = 'Unmark';
							$isMarkSelect = ' checked="checked" ';
							$markdiv = ' <div class="ismark"><div class="' . $isMark . '"><a href="' . $uunmark . '" style="color:#fff;">' . $isMark . '</a></div></div> ';
						} else {
							$isMark = 'Mark';
							$isMarkSelect = '';
							$markdiv = ' <div class="ismark"><div class="' . $isMark . '"><label><input type="checkbox" name="aMark[' . $vPic['file_id'] . ']" value="1" ' . $isMarkSelect . ' />' . $isMark . '</label></div></div> ';
						}
					}

					echo '
						<div class="imgBox boxid-' . $vPic['file_id'] . ' listmark">
						' . $markdiv . '
						<div><a href="' . URL_UPLOAD . '/' . $vPic['imgPathBig'] . '" class="popupimg viewImgBox" target="_blank"><img src="' . URL_UPLOAD . '/' . $vPic['imgPathMini'] . '" width="280" border="0" /></a></div>
						<div align="center" class="manageImgBox">
							<a href="' . URL_UPLOAD . '/' . $vPic['imgPathMini'] . '" class="popupimg" target="_blank">MINI File</a> | &nbsp; 
							<a href="' . URL_UPLOAD . '/' . $vPic['imgPathBig'] . '" class="popupimg" target="_blank">BIG File</a></div>
							<div style="margin-top:2px;" align="left">
								<div style="float: left;padding:10px;"><a href="' . $urldetail . '" class="popupiframe">ใส่รายละเอียด</a></div>
								<div style="padding:10px;float: right;">' . @displaySize($imgdetail['size']) . '</div>
							</div>
									<br clear="all" />
							<div style="color:#CCC;font-size:10px;">' . @$imgdetail['all'] . '</div>
						</div>
						
						';
				} ?>
			</td>
		</tr>
	</table>
<?php } else { ?>
	<table width="100%" border="0" cellpadding="5" cellspacing="1" bgcolor="#CCCCCC">
		<tr>
			<td bgcolor="#F9F9F9">ยังไม่มีการแนบรูปในบทความนี้</td>
		</tr>
	</table>
<?php } ?>