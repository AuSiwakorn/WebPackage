<?php
$keysname 	= REQ_get('keysname', 'request', 'str', '');
$ac 		= REQ_get('ac', 'request', 'str', '');
$id 		= REQ_get('id', 'request', 'int', '');
$t = _TIME_;
$pathBanner = @$aBannerConfig['path'];
$aBannerEdit = array();
if (defined('PATH_UPLOAD') && !is_dir(PATH_UPLOAD . '/' . $pathBanner) && $pathBanner != '') {
	@mkdir(PATH_UPLOAD . '/' . $pathBanner, 0777);
}

if ($ac == 'add' && $keysname != '') {
	$title 		= REQ_get('title', 'request', 'text');
	$isResize 	= REQ_get('isResize', 'request', 'str', '');
	if ($title != '') {
		$aBanner = array();
		$aBanner['title'] = $title;
		$aBanner['detail'] = @$_REQUEST['detail'];
		$aBanner['link'] = @$_REQUEST['link'];
		$aBanner['target'] = @$_REQUEST['target'];
		$aBanner['sort'] = 0;
		$aBanner['bannertype'] = 'img';
		$aBanner['filename'] = '';
		$aBanner['extra1'] = @$_REQUEST['extra1'];
		$aBanner['extra2'] = @$_REQUEST['extra2'];
		$extention = strtolower(end(explode('.', $_FILES['banner']['name'])));

		if (in_array($extention, $aBannerConfig['icontype'])) {
			$filePath = PATH_UPLOAD . '/' . $pathBanner . '/' . $t . '.' . $extention;
			if (isset($_FILES['banner']['tmp_name']) && $_FILES['banner']['tmp_name'] != '') {
				move_uploaded_file($_FILES['banner']['tmp_name'], $filePath);
				$aBanner['filename'] = $pathBanner . '/' . $t . '.' . $extention;
			}
		}

		$upto = (@$_REQUEST['status'] > 0) ? 1 : 0;
		$id = PG_banner_insert($keysname, $aBanner);
		PG_banner_update_status($id, $upto);

		setRaiseMsg('Insert banner successfully.', _TIME_, 0);
		CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&keysname=" . $keysname);
		exit;
	} else {
		setRaiseMsg('กรุณากรอกชื่อแบนเนอร์ และ เลือกไฟล์ด้วยครับ.', _TIME_, 1);
		CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&keysname=" . $keysname);
		exit;
	}
} elseif ($ac == 'status' && $keysname != '' && $id != '') {
	$upto = (@$_REQUEST['status'] > 0) ? 1 : 0;
	PG_banner_update_status($id, $upto);
	setRaiseMsg('Status updated.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&keysname=" . $keysname);
	exit;
} elseif ($ac == 'del' && $keysname != '' && $id != '') {
	PG_banner_delete_by_id($id);
	setRaiseMsg('Deleted banner successfully.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&keysname=" . $keysname);
	exit;
} elseif ($ac == 'editview' && $keysname != '' && $id != '') {
	$aBannerEdit = PG_banner_get_banner_by_id($id);
} elseif ($ac == 'sort' && $keysname != '') {
	$a = @$_REQUEST['aSortNum'];
	foreach ($a as $k => $v) {
		PG_banner_update_sort_banner($k, $v);
	}
	setRaiseMsg('Update sort banner successfully.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&keysname=" . $keysname);
	exit;
} elseif ($ac == 'edit' && $keysname != '' && $id != '') {
	$aBannerEdit = PG_banner_get_banner_by_id($id);

	$aUpdate = array();
	$aUpdate['title'] = @$_REQUEST['title'];
	$aUpdate['detail'] = @$_REQUEST['detail'];
	$aUpdate['link'] = @$_REQUEST['link'];
	$aUpdate['target'] = @$_REQUEST['target'];
	$aUpdate['filename'] = $aBannerEdit['filename'];
	$aUpdate['extra1'] = @$_REQUEST['extra1'];
	$aUpdate['extra2'] = @$_REQUEST['extra2'];
	$extention = strtolower(end(explode('.', $_FILES['banner']['name'])));
	if (in_array($extention, $aBannerConfig['icontype'])) {
		$filePath = PATH_UPLOAD . '/' . $pathBanner . '/' . $t . '.' . $extention;
		if (isset($_FILES['banner']['tmp_name']) && $_FILES['banner']['tmp_name'] != '') {
			move_uploaded_file($_FILES['banner']['tmp_name'], $filePath);
			unlink(PATH_UPLOAD . '/' . $aUpdate['filename']);
			$aUpdate['filename'] = $pathBanner . '/' . $t . '.' . $extention;
		}
	}
	PG_banner_update($id, $aUpdate);

	setRaiseMsg('Edit banner successfully.' . $extention, _TIME_, 0);
	//CustomRedirectToUrl("index.php?module=".$module."&mp=".$mp."&keysname=".$keysname."&ac=editview&id=".$id);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&keysname=" . $keysname);
	exit;
}
$aBanner = PG_banner_get_all_banner_by_keys($keysname, 'all');
$aBannerNone = PG_banner_get_all_banner_by_keys($keysname, 1);
?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">จัดการแบนเนอร์</h1>
	</div>
	<!-- <ol class="breadcrumb">  // path way Delete
			<li><a href="#"><i class="demo-pli-home"></i></a></li>
			<li><a href="#">Banner</a></li>
			<li class="active"><?php // echo @$aBannerConfig['title']; 
								?></li>
		</ol> -->
</div>
<div id="page-content">
	<div class="row">
		<?php
		if (count($aBanner) > 0) {
		?>
			<div class="col-lg-6 col-md-12">
				<div class="panel">
					<div class="panel-heading">
						<h3 class="panel-title"><?php echo @$aBannerConfig['titlesort']; ?></h3>
					</div>
					<div class="panel-body">
						<form id="form1" name="form1" method="post" action="">
							<input type="hidden" name="ac" value="sort" />
							<?php displayRaiseMsg(); ?>
							<div>การเรียงลำดับรูปภาพแบนเนอร์ เพื่อให้ลูกค้าสามารถเรียงลำดับความสำคัญของแบนเนอร์ และ สามารถ update ข้อมูลในส่วนของ banner ได้เอง การใช้งานที่ง่ายเพียง คลิกเมาส์ลาก เรียงลำดับตามต้องการแล้วกดบันทัก</div>
							<table class="table table-hover table-vcenter">
								<thead>
									<tr>
										<th class="min-width">IMG</th>
										<th>Name</th>
										<th class="text-center">Order Sort</th>
										<th class="text-center">Status</th>
										<th class="text-center">Edit</th>
									</tr>
								</thead>
								<tbody>
									<?php
									foreach ($aBanner as $k => $v) {
										if ($v['filename'] == '') {
											$imgurl = url_images_module('banner', 'nopic.jpg');
										} else {
											$imgurl = URL_UPLOAD . '/' . $v['filename'];
										}
										if ($v['status'] == 0) {
											$chto = 1;
											$chname = '<span class="text-success text-semibold">Open</span>';
										} else {
											$chto = 0;
											$chname = '<span class="text-danger text-semibold">Close</span>';
										}
									?>
										<tr>
											<td class="text-center"><a href="<?php echo $imgurl; ?>" target="_blank"><?php echo '<img class="img-responsive" src="' . $imgurl . '" border="0" alt="' . $v['title'] . '" />';  ?></a></td>
											<td>
												<span class="text-main text-semibold"><?php echo $v['title']; ?></span><br>
												<small class="text-muted" style="font-size:11px;"><?php echo $imgurl; ?></small><br>
												<?php if ($isOpens['isLink'] == true) { ?><small class="text-muted"><a href="<?php echo $v['link']; ?>" target="_blank"><?php echo $v['link']; ?>(<?php echo $v['target']; ?>)</a></small><br><?php } ?>
												<?php if ($v['extra1'] != '') {  ?><small class="text-muted"><?php echo $v['extra1']; ?></small><br><?php } ?>
												<?php if ($v['extra2'] != '') {  ?><small class="text-muted"><?php echo $v['extra2']; ?></small><br><?php } ?>
												<?php if ($v['detail'] != '') { ?><small class="text-muted"><?php echo $v['detail']; ?></small><br><?php } ?>
												<small class="text-muted"><?php echo strTimeFormat($v['adddate'], "d-m-Y"); ?></small>
											</td>
											<td class="text-center">
												<div class="form-group col-sm-11"><input type="text" name="aSortNum[<?php echo $v['banner_id']; ?>]" class="form-control" placeholder="เลข น้อย..มาก" value="<?php echo $v['sort']; ?>" /></div>
											</td>
											<td class="text-center"><a href="<?php echo _admin_buil_link("index.php?module=" . $module . "&mp=" . $mp . "&keysname=" . $keysname . "&ac=status&id=" . $v['banner_id'] . "&status=" . $chto); ?>" class="button"><?php echo $chname; ?></a></td>
											<td>
												<a href="<?php echo _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&keysname=' . $keysname . '&ac=editview&id=' . $v['banner_id']); ?>"><img src="<?php echo url_images_module('siteconfig', 'edit.gif'); ?>" border="0" alt="Edit" /></a>
												<a href="<?php echo _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&keysname=' . $keysname . '&ac=del&id=' . $v['banner_id']); ?>" onclick="return confirm('Delete ?');"><img src="<?php echo url_images_module('siteconfig', 'delete.gif'); ?>" border="0" alt="delete" /></a>
											</td>
										</tr>
									<?php }  ?>
								</tbody>
							</table>
							<div class="text-right">
								<button class="btn btn-mint" type="submit">Update Number</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		<?php }  ?>

		<div class="<?php echo (count($aBanner) > 0) ? 'col-lg-6 col-md-12' : 'col-lg-12 col-md-12'; ?>">
			<div class="panel">
				<div class="panel-body form-horizontal form-padding">
					<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">
						<input type="hidden" name="isResize" value="0" />
						<input type="hidden" name="bannertype" value="img" />
						<input type="hidden" name="ac" value="<?php echo (count($aBannerEdit) > 0 && $aBannerEdit['banner_id'] != '') ? 'edit' : 'add'; ?>" />
						<?php if ($isOpens['isTitle'] == false) { ?>
							<input name="title" type="hidden" value="notitle" />
						<?php } ?>
						<?php if (count($aBannerEdit) > 0 && $aBannerEdit['banner_id'] != '') { ?>
							<div class="row"><img src="<?php echo URL_UPLOAD . '/' . $aBannerEdit['filename']; ?>" width="100%" /></div>
							<br>
						<?php } ?>
						<div class="form-group">
							<label class="col-md-3 control-label">แสดงผล</label>
							<div class="col-md-9">
								<div class="radio">
									<!-- Inline radio buttons -->
									<input id="open" class="magic-radio" name="status" type="radio" value="0" name="inline-form-radio" <?php echo (@$aBannerEdit['status'] == 0 || $aBannerEdit['status'] == '') ? 'checked' : ''; ?>>
									<label for="open">เปิดใช้งาน</label>
									<input id="close" class="magic-radio" name="status" type="radio" value="1" name="inline-form-radio" <?php echo (@$aBannerEdit['status'] == 1) ? 'checked' : ''; ?>>
									<label for="close">ซ่อนการแสดงผล</label>
								</div>
							</div>
						</div>
						<?php if ($isOpens['isTitle'] == true) { ?>
							<div class="form-group">
								<label class="col-md-3 control-label">ชื่อแบนเนอร์</label>
								<div class="col-md-9">
									<input type="text" class="form-control" name="title" placeholder="ชื่อแบนเนอร์" size="50" value="<?php echo @$aBannerEdit['title']; ?>">
								</div>
							</div>
						<?php } ?>
						<?php if ($isOpens['isDetail'] == true) { ?>
							<div class="form-group">
								<label class="col-md-3 control-label">คำอธิบายแบนเนอร์</label>
								<div class="col-md-9">
									<input type="text" class="form-control" placeholder="คำอธิบายแบนเนอร์" size="80" name="detail" value="<?php echo @$aBannerEdit['detail']; ?>">
								</div>
							</div>
						<?php } ?>
						<?php if ($isOpens['isLink'] == true) { ?>
							<div class="form-group">
								<label class="col-md-3 control-label">ลิ้งปลายทาง</label>
								<div class="col-md-9">
									<input type="text" class="form-control" placeholder="http://" size="80" name="link">
								</div>
							</div>
							<div class="form-group pad-ver">
								<label class="col-md-3 control-label">รูปแบบการเปิด</label>
								<div class="col-md-9">
									<div class="radio">
										<!-- Inline radio buttons -->
										<input id="blank" class="magic-radio" name="target" type="radio" value="_blank" name="inline-form-radio" checked>
										<label for="blank">เปิดหน้าต่างใหม่</label>
										<input id="parent" class="magic-radio" name="target" type="radio" value="_parent" name="inline-form-radio">
										<label for="parent">เปิดในหน้าเดิม</label>
									</div>
								</div>
							</div>
						<?php } ?>
						<?php if ($isOpens['extra1'] == true) { ?>
							<div class="form-group">
								<label class="col-md-3 control-label"><?php echo @$aBannerConfig['extra1']; ?></label>
								<div class="col-md-9">
									<input name="extra1" type="text" style="width: <?php echo @$isSize['extra1']; ?>px;" class="form-control" placeholder="" />
									<?php if (@$aBannerEdit['extra1'] != '') { ?><small class="help-block"><?php echo @$aBannerEdit['extra1']; ?></small><?php } ?>
								</div>
							</div>
						<?php } ?>
						<?php if ($isOpens['extra2'] == true) { ?>
							<div class="form-group">
								<label class="col-md-3 control-label"><?php echo @$aBannerConfig['extra2']; ?></label>
								<div class="col-md-9">
									<input name="extra2" type="text" style="width: <?php echo @$isSize['extra2']; ?>px;" class="form-control" placeholder="" />
									<?php if (@$aBannerEdit['extra2'] != '') { ?><small class="help-block"><?php echo @$aBannerEdit['extra2']; ?></small><?php } ?>
								</div>
							</div>
						<?php } ?>
						<div class="form-group">
							<label class="col-md-3 control-label">เลือกไฟล์</label>
							<div class="col-md-9">
								<span class="pull-left btn btn-primary btn-file">
									เลือกไฟล์จากเครื่อง / JPG , GIF , PNG<input type="file" name="banner">
								</span>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label"></label>
							<div class="col-sm-9">
								<button class="btn btn-mint" type="submit">Upload Banner</button>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">ขนาดแบนเนอร์</label>
							<div class="col-md-9">
								<p class="form-control-static"><em><?php echo $aBannerConfig['width']; ?>px </em> <strong> &nbsp; x &nbsp; </strong> <em><?php echo $aBannerConfig['height']; ?>px</em></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">หมายเหตุ</label>
							<div class="col-md-9">
								<?php foreach ($aBannerConfig['iconNotics'] as $k => $v) { ?>
									<p class="form-control-static"><?php echo $v; ?></p>
								<?php } ?>
							</div>
						</div>
						<?php
						if (is_writable(PATH_UPLOAD . '/banner')) {
							$txt_writeable = '<span class="text-success text-semibold">' . PATH_UPLOAD . '/banner</span>';
						} else {
							$txt_writeable = '<span class="text-danger text-semibold">' . PATH_UPLOAD . '/banner</span>';
						}
						?>
						<div class="form-group">
							<label class="col-md-3 control-label">Folder writeable</label>
							<div class="col-md-9">
								<p class="form-control-static"><?php echo $txt_writeable; ?></p>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-3 control-label">Keysname</label>
							<div class="col-md-9">
								<p class="form-control-static"><?php echo $keysname; ?></p>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>