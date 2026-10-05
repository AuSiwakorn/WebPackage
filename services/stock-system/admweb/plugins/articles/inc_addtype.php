<?php
$isOpens['isIconType'] = true;
$t = _TIME_;
@$widthtypeIcon = @$aArticleConfig['widthtypeIcon'];
$ac = REQ_get('ac', 'post', 'str');
$id = REQ_get('id', 'request', 'int', '');
$keysname = REQ_get('keysname', 'request', 'str', '');

///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array($ac, array('add')) && $isSecurMode == 1) {
	setRaiseMsg('Secur Mode.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listtype&keysname=" . $keysname);
	exit;
}

///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////

if ($ac == 'add') {
	$sort = REQ_get('sort', 'post', 'int', 0);
	$name = REQ_get('kname', 'request', 'str', '');
	$id = REQ_get('id', 'request', 'int', '');

	$iconname = '';
	$fileiconviewUpload = REQ_get('iconview', 'file');
	if (!empty($fileiconviewUpload['name'])) {
		$upFile = new \UpFile(PATH_UPLOAD);
		$result = $upFile->uploadStandard(
			['jpg', 'jpeg', 'png', 'gif'],
			$fileiconviewUpload['tmp_name'],
			$fileiconviewUpload['name'],
			'articles_icon/' . _YY_ . '_' . _MM_,
			'type_' . _TIME_
		);
		if ($result !== false) $iconname = $result;
	}

	$id = PG_addType($name, $iconname, $sort, $keysname);
	Func_Addlogs("[{$keysname}] Add ArticleType ID {$id} ");
	setRaiseMsg('Database successfully Add.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listtype&keysname=" . $keysname);
	exit;
}
?>
<form action="" method="post" name="form1" id="form1" enctype="multipart/form-data">
	<input type="hidden" name="ac" value="add" />
	<div id="page-head">
		<div id="page-title">
			<h1 class="page-header text-overflow col-md-8"> ADD TYPE </h1>
			<div class="col-md-4 text-right"><button class="btn btn-mint submit" style="font-size: 24px; font-family: db_ozone_xregular; "> <i class="fa fa-save" style="font-size: 18px;"></i> Add &amp; Publish</button></div>
		</div>
	</div>
	<div id="page-content">
		<div class="row">
			<div class="col-md-12">
				<?php displayRaiseMsg(); ?>
				<div class="col-md-9" style="padding-top: 40px">
					<div class="tab-content">
						<div class="panel">
							<div class="panel-body">
								<div class="form-horizontal">
									<p class="text-main text-bold text-uppercase mainTxtBorderGreen"><?php echo $tagname['typetitle']; ?></p>
									<div class="dropzone-container">
										<div class="fallback" align="left">
											<div class="col-sm-12" style="padding-bottom: 10px;">
												<input type="text" name="kname" class="form-control" value="" />
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php  ?>
					</div>
				</div>
				<div class="col-md-3" style="padding-top: 40px">
					<div class="panel">
						<div class="panel-body">
							<div class="form-horizontal">
								<p class="text-main text-bold text-uppercase mainTxtBorderGreen"><?php echo @$tagname['isIcon']; ?></p>
								<div class="dropzone-container">
									<div class="fallback" align="center">
										<div style="height: 80px;overflow: hidden;">
											<?php
											if (isset($aArticleRows['icon']) && $aArticleRows['icon'] != '') {
												$display = '';
												$isIcon = 'isicontrue';
												$url_img = URL_UPLOAD . '/' . $aArticleRows['icon'];
												$text_del = '<a href="#" id="textDel" onclick="event.preventDefault();hiddenImage();" style="color: #FF0048;font: 16px \'db_ozone_xregular\' !important;">[ ลบไฟล์นี้ ]</a>';
											} else {
												$url_img = '';
												$text_del = '';
												$isIcon = '';
												$display = 'display: none;';
											}
											?>
											<span class="btn displayselect" style="padding-top: 8px;font-size: 18px;height: 80px;<?php echo $display; ?>">
												<a href="<?php echo $url_img; ?>" target="_blank"><img src="<?php echo $url_img; ?>" border="0" class="blah" style="margin-top: -9px;margin-left: -14px; width: auto; height: 80px;" /></a>
											</span>
											<span class="btn btn-mint btn-file" style="padding-top: 8px;font-size: 18px;width: 80px;height: 80px;">
												<i class="demo-pli-upload-to-cloud" style="font-size: 25px;"></i><br />เลือกไฟล์..<input type="file" name="iconview" id="iconview" onchange="get2Path('iconview', 'showfile');readImageURL(this);">
											</span>
										</div>
										<?php echo '<div style="margin:2px;font-size: 18px;color: #efa660;">' . @$aArticleConfig['widthIcon_notics'] . '</div>'; ?>
										<?php echo $text_del; ?>
										<div id="showfile" style="height: 20px;"></div>
										<input hidden type="text" class="icon" name="icon" value="<?php echo $url_img ?>">
										<input hidden type="text" id="isIconView" name="isIconView" value="<?php echo $isIcon; ?>">
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="panel">
						<div class="panel-body">
							<div class="form-horizontal">
								<p class="text-main text-bold text-uppercase mainTxtBorderGreen">Order By</p>
								<div class="dropzone-container">
									<div class="fallback" align="left">
										<div class="col-sm-12" style="padding-bottom: 10px;">
											<input type="number" name="sort" id="sort" class="form-control" placeholder="low to height" />
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

</form>

<?php
$aConfig['isTabLoad'] = true;
?>