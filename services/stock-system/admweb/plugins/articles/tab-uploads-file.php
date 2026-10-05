<div class="row">
	<div class="col-sm-12">
		<div id="inforeturn"></div>
		<p class="text-main text-bold text-uppercase mainTxtBorderGreen">
			รองรับเฉพาะไฟล์ :
			<?php echo (is_array($aArticleConfig['attach'])) ? implode(',', $aArticleConfig['attach']) : '-'; ?>
		</p>

		<div class="dropzone-container">
			<div class="fallback">

				<div class="col-sm-6">
					<?php for ($iatlist = 0; $iatlist < 2; $iatlist++) { ?>
						<div class="form-group">
							<div class="input-group mar-btm">
								<input type="text"
									name="attachTextFileList[]"
									placeholder="Image Path"
									class="form-control <?php echo 'imgpathshow_L' . $iatlist; ?>"
									readonly>
								<span class="input-group-btn">
									<span class="pull-left btn btn-mint btn-file"
										style="font: 18px 'db_ozone_xregular'; padding: 5.5px 15px;">
										<i class="demo-pli-upload-to-cloud icon-5x" style="font-size:18px;"></i> Browse...
										<input type="file"
											name="attachFileList[]"
											onchange="getPathShow_forattachfile('<?php echo 'imgpathshow_L' . $iatlist; ?>', this)">
									</span>
								</span>
							</div>
						</div>
					<?php } ?>
				</div>

				<div class="col-sm-6">
					<?php for ($iatlist = 2; $iatlist < 4; $iatlist++) { ?>
						<div class="form-group">
							<div class="input-group mar-btm">
								<input type="text"
									name="attachTextFileList[]"
									placeholder="Image Path"
									class="form-control <?php echo 'imgpathshow_R' . $iatlist; ?>"
									readonly>
								<span class="input-group-btn">
									<span class="pull-left btn btn-mint btn-file"
										style="font: 18px 'db_ozone_xregular'; padding: 5.5px 15px;">
										<i class="demo-pli-upload-to-cloud icon-5x" style="font-size:18px;"></i> Browse...
										<input type="file"
											name="attachFileList[]"
											onchange="getPathShow_forattachfile('<?php echo 'imgpathshow_R' . $iatlist; ?>', this)">
									</span>
								</span>
							</div>
						</div>
					<?php } ?>
				</div>

			</div>
		</div>
	</div>
</div>
<br clear="all">
<div class="row">
	<div class="col-sm-12">
		<div class="row">
			<?php if (isset($aFilesAtt['data']) && count($aFilesAtt['data']) > 0) { ?>
				<script type="text/javascript">
					function confdeleteFile(id) {
						if (confirm('Delete This File?')) {
							u = 'doAjax.php?ty=plugin&module=<?php echo $module; ?>&mp=<?php echo $mp; ?>&inc=ajaximg&ac=delfile&id=' + id;
							$('#inforeturn').load(u, function(response, status, xhr) {
								if (status == "error") {
									alert('error' + xhr.status + " " + xhr.statusText);
								} else {
									$('.boxidfile-' + id).fadeOut();
								}
							});
						}
						return false;
					}
				</script>
				<!-- ตารางไฟล์แนบ -->
				<table id="fileAttach" class="table table-striped table-bordered" cellspacing="0" width="100%">
					<thead>
						<tr>
							<th style="width: 50px;">Sort</th>
							<th style="width: 120px;">Preview</th>
							<th>รายละเอียด (TH)</th>
							<th>รายละเอียด (EN)</th>
							<th style="width: 35px;">จัดการ</th>
						</tr>
					</thead>
					<tbody>
						<?php if (isset($aFilesAtt['data']) && count($aFilesAtt['data']) > 0) {
							foreach ($aFilesAtt['data'] as $kFile => $vFile) {
								$no        = $kFile + 1;
								$imgdetail = get_images_detail(PATH_UPLOAD . '/' . $vFile['filename']);
								$fileUrl   = URL_UPLOAD . '/' . $vFile['filename'];
								$fileExt   = strtolower(pathinfo($vFile['filename'], PATHINFO_EXTENSION));
								$isImage   = in_array($fileExt, array('jpg', 'jpeg', 'png', 'gif'));
						?>
								<tr class="boxidfile-<?php echo $vFile['file_id']; ?>">
									<td>
										<input type="number" name="sortimgFile[<?php echo $vFile['file_id']; ?>]" value="<?php echo $vFile['ctime']; ?>" class="form-control" style="width:70px;" />
									</td>
									<td>
										<?php if ($isImage) { ?>
											<a href="<?php echo $fileUrl; ?>" class="popupimg" target="_blank">
												<img src="<?php echo $fileUrl; ?>" style="max-height:60px; border:1px solid #ccc; border-radius:4px;" />
											</a>
										<?php } else { ?>
											<a href="<?php echo $fileUrl; ?>" target="_blank">
												<?php echo basename($fileUrl); ?>
											</a>
										<?php } ?>
										<br>
										<a href="<?php echo $fileUrl; ?>" target="_blank"><small>ขนาด: <?php echo @displaySize($imgdetail['size']); ?></small></a>
									</td>
									<td>
										<input type="text" name="aDetailChageFile[<?php echo $vFile['file_id']; ?>]" class="form-control" placeholder="รายละเอียด (TH)" value="<?php echo htmlspecialchars($vFile['detail']); ?>">
									</td>
									<td>
										<input type="text" name="aDetailChageFileEn[<?php echo $vFile['file_id']; ?>]" class="form-control" placeholder="รายละเอียด (EN)" value="<?php echo htmlspecialchars($vFile['detail_en']); ?>">
									</td>
									<td>
										<a href="javascript:;" onclick="return confdeleteFile(<?php echo $vFile['file_id']; ?>);" class="btn btn-danger btn-sm">ลบ</a>
									</td>
								</tr>
							<?php }
						} else { ?>
							<tr>
								<td colspan="6" align="center" style="color:#ffb900;">
									- - - ยังไม่มีการแนบไฟล์ในบทความนี้ - - -
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			<?php } ?>
		</div>
	</div>
</div>