<div class="row">

	<?php if (isset($aPictureAtt['data']) && count($aPictureAtt['data']) > 0) {
		$acdel = (isset($page) && $page === 'group') ? 'delimggroup' : 'del';
	?>
		<script type="text/javascript">
			function confdelete(id) {
				if (confirm('Delete This Photo?')) {
					var u = 'doAjax.php?ty=plugin&module=<?php echo $module; ?>&mp=<?php echo $mp; ?>&inc=ajaximg&ac=<?php echo $acdel ?>&id=' + id;
					$('#inforeturn').load(u, function(response, status, xhr) {
						if (status == "error") {
							alert('error ' + xhr.status + " " + xhr.statusText);
						} else {
							$('.boxid-' + id).hide();
						}
					});
				}
				return false;
			}
		</script>
		<div id="inforeturn"></div>
		<!--ตารางที่ต้องใช้-->
		<table id="picture" class="table table-striped table-bordered" cellspacing="0" width="100%">
			<thead>
				<tr>
					<th style="width: 50px;">No.</th>
					<th style="width: 50px;">Mark</th>
					<th>Icon</th>
					<th class="min-tablet">Name</th>
					<th class="min-desktop" style="width: 150px;">จัดการ</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ($aPictureAtt['data'] as $kPic => $vPic) {
					$no        = $kPic + 1;
					$udelpic   = _admin_buil_link("index.php?module=$module&mp=$mp&inc=edit&keysname=$keysname&id=$id&ac=delpic&fid={$vPic['file_id']}");
					$uunmark   = _admin_buil_link("index.php?module=$module&mp=$mp&inc=edit&keysname=$keysname&id=$id&ac=unmark&fid={$vPic['file_id']}");
					$imgdetail = get_images_detail(PATH_UPLOAD . '/' . $vPic['imgPathBig']);
					$markdiv = '';
					if ($isOpens['isMark']) {
						if ($vPic['ismark'] == 1) {
							$markdiv = '<a href="' . $uunmark . '" class="btn btn-warning btn-xs">Unmark</a>';
						} else {
							$markdiv = '<label><input type="checkbox" name="aMark[' . $vPic['file_id'] . ']" value="1"> Mark</label>';
						}
					}
				?>
					<tr class="boxid-<?php echo $vPic['file_id']; ?>">
						<td>
							<input type="text" name="sortimg[<?php echo $vPic['file_id']; ?>]"
								value="<?php echo $vPic['ctime']; ?>"
								style="width:70px;" class="form-control" />
						</td>
						<td><?php echo $markdiv; ?></td>
						<td>
							<a href="<?php echo URL_UPLOAD . '/' . $vPic['imgPathBig']; ?>" class="popupimg" target="_blank">
								<img src="<?php echo URL_UPLOAD . '/' . $vPic['imgPathBig']; ?>" style="max-height:60px;" />
							</a>
						</td>
						<td><input name="edit[title][<?php echo $vPic['file_id']; ?>]" type="text" class="form-control"
								placeholder="Images Title" value="<?php echo $vPic['title']; ?>"><br>
							<a href="<?php echo URL_UPLOAD . '/' . $vPic['imgPathBig']; ?>" target="_blank">
								<?php echo URL_UPLOAD . '/' . $vPic['imgPathBig']; ?>
							</a>
						</td>
						<td>
							<button type="button" data-target="#box_<?php echo $vPic['file_id']; ?>" data-toggle="modal" class="btn btn-primary btn-sm">
								รายละเอียด
							</button>
							<a href="javascript:;" onclick="return confdelete(<?php echo $vPic['file_id']; ?>);" class="btn btn-danger btn-sm">Delete</a>
							<br />
						</td>
					</tr>

					<!-- Modal -->
					<div class="modal fade" id="box_<?php echo $vPic['file_id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
						<div class="modal-dialog modal-lg">
							<div class="modal-content">
								<div class="modal-header">
									<h5 class="modal-title">Box : Edit Data Images (file id : <?php echo $vPic['file_id']; ?>)</h5>
									<button type="button" class="close" data-dismiss="modal" aria-label="Close">
										<span aria-hidden="true">&times;</span>
									</button>
								</div>

								<div class="modal-body">
									<input type="hidden" name="edit[fid][<?php echo $vPic['file_id']; ?>]" value="<?php echo $vPic['file_id']; ?>" />

									<div class="form-group">
										<label>Title</label>
										<input name="edit[title][<?php echo $vPic['file_id']; ?>]" type="text" class="form-control"
											placeholder="Images Title" value="<?php echo $vPic['title']; ?>">
									</div>

									<div class="form-group">
										<label>รายละเอียด</label>
										<textarea name="edit[detail][<?php echo $vPic['file_id']; ?>]"
											class="demo-summernote form-control" rows="3"><?php echo @$vPic['detail']; ?></textarea>
									</div>

									<div class="form-group">
										<label>รายละเอียด (เพิ่มเติม)</label>
										<textarea name="edit[detail2][<?php echo $vPic['file_id']; ?>]"
											class="demo-summernote form-control" rows="3"><?php echo @$vPic['detail2']; ?></textarea>
									</div>
								</div>

								<div class="modal-footer">
									<input class="btn btn-mint btn-ok" type="submit" value="อัพเดตข้อมูล">
								</div>

							</div>
						</div>
					</div>
				<?php } // end foreach 
				?>
			</tbody>
		</table>
		<!--ตารางที่ต้องใช้-->

	<?php } else { ?>
		<div class="col-sm-12" align="center" style="font:18px 'db_ozone_xregular' !important;color:#ffb900;">
			- - - ยังไม่มีการแนบรูปในบทความนี้ - - -
		</div>
	<?php } ?>

</div>