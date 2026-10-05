<?php
$time = _TIME_;
$Year = _YY_;
$Month = _MM_;
$Day = _DD_;

@$widthIcon = @$aArticleConfig['widthIcon'];
$ac = REQ_get('ac', 'post', 'str');
$keysname 	= REQ_get('keysname', 'request', 'str', '');
$group = '0';

if ($isOpens['isOpenGroup'] != true) {
	setRaiseMsg('เมนูนี้ไม่ได้เปิดสิทธิ์ การเพิ่มหมวดหมู่" .', _TIME_, 1);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listgroup&keysname=" . $keysname);
	exit;
}

///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array($ac, array('add')) && $isSecurMode == 1) {
	setRaiseMsg('Secur Mode.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listgroup&keysname=" . $keysname);
	exit;
}
///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////
///////////////////////////////////////////////////////

if ($ac == 'add') {
	$upFile = new \UpFile(PATH_UPLOAD);
	$sort 					= REQ_get('sort', 'post', 'int', 0);
	$parent_id = REQ_get('parent_id', 'post', 'int', 0);
	$status 			= (REQ_get('status', 'post', 'str', 0) == 'y') ? 0 : 1;
	$iconname 	= '';
	$extraOption = REQ_get('extraOption', 'request', 'str');
	$extra1 				= REQ_get('extra_group1', 'request', 'str');
	$extra2 				= REQ_get('extra_group2', 'request', 'str');
	$extra3 				= REQ_get('extra_group3', 'request', 'str');
	$extra4 				= REQ_get('extra_group4', 'request', 'str');
	$extra5 				= REQ_get('extra_group5', 'request', 'str');

	$id = PG_addGroup($parent_id, $iconname, $sort, $keysname, $extraOption, $status, $extra1, $extra2, $extra3, $extra4, $extra5);
	$aExtenIcon = (!empty($aArticleConfig['icon'])) ? $aArticleConfig['icon'] : ['jpg', 'jpeg', 'png', 'gif'];
	$folder_icon = 'articles_icon/' . $Year . '_' . $Month;

	$iconname = '';
	$fileiconviewUpload = REQ_get('iconview', 'file');
	if (!empty($fileiconviewUpload['name'])) {
		$result = $upFile->uploadStandard($aExtenIcon, $fileiconviewUpload['tmp_name'], $fileiconviewUpload['name'], $folder_icon, $id . '_group_iconview_' . _TIME_);
		if ($result !== false) $iconname = $result;
	}

	$iconname2 = '';
	$fileiconviewUpload2 = REQ_get('iconview2', 'file');
	if (!empty($fileiconviewUpload2['name'])) {
		$result = $upFile->uploadStandard($aExtenIcon, $fileiconviewUpload2['tmp_name'], $fileiconviewUpload2['name'], $folder_icon, $id . '_group_iconview2_' . _TIME_);
		if ($result !== false) $iconname2 = $result;
	}

	PG_updateIconGroup($id, $iconname, $iconname2);

	$frm = REQ_get('frm', 'post', 'array', array('th'));
	foreach ($frm as $k => $v) {
		$v['name'] 				= @$v['name'] != '' ? $v['name'] : '- - No title - -';
		$frm[$k]['group_id'] 	= $id;
		$frm[$k]['name']		= (@$v['name'] != '') ? mysqlPrep($v['name']) : '';
		$frm[$k]['detail'] 		= (@$v['detail'] != '') ? $v['detail'] : '';
		$frm[$k]['detailExtra1']   = (@$v['detailExtra1'] != '') ? $v['detailExtra1'] : '';
		$frm[$k]['detailExtra2']   = (@$v['detailExtra2'] != '') ? $v['detailExtra2'] : '';
		$frm[$k]['slug']		= (@$v['slug'] != '') ? sanitizeSlug($v['slug']) : '';
	}

	PG_addGroupContent($frm);

	$strUpload = '';
	if ($isOpens['isGroupAttachPic']) {
		$aFile = REQ_get('attachFile', 'files', 'array', []);
		$aFileText = REQ_get('attachTextFile', 'request', 'array', []);
		if (!empty($aFile['name']) && is_array($aFile['name'])) {
			$folder_pic_base = 'articles_pic/' . $Year . '_' . $Month . '/' . $id;
			$folder_mini = $folder_pic_base . '/mini';
			$aUpName = [];
			$counter = 0;
			$i = 0;
			foreach ($aFile['error'] as $kFile => $vFile) {
				if ($vFile == 0 && !empty($aFile['name'][$kFile])) {
					$i++;
					$originalName = $aFile['name'][$kFile];
					$customFileName = $id . '_group_pic_' . _TIME_ . '_' . $i;
					$uploadResultBig = $upFile->uploadStandard($aExtenIcon, $aFile['tmp_name'][$kFile], $originalName, $folder_pic_base . '/big', $customFileName);
					if ($uploadResultBig !== false) {
						$counter++;
						$ext = $upFile->getExtension($originalName);
						$fileNameWithExt = $customFileName . '.' . $ext;
						$fileMiniUrl = $folder_mini . '/' . $fileNameWithExt;
						PG_addArticlesPictureGroup($id, $counter, $fileMiniUrl, $uploadResultBig, @$aFileText[$kFile]);
						if (!is_dir(PATH_UPLOAD . '/' . $folder_mini)) mkdir(PATH_UPLOAD . '/' . $folder_mini, 0777, true);
						$thumb = new thumbnail();
						$thumbinc = $thumb->resize_thumbnail(PATH_UPLOAD . '/' . $uploadResultBig);
						$thumb->size_width(300);
						$thumb->jpeg_quality(99);
						$thumb->save(PATH_UPLOAD . '/' . $fileMiniUrl);
						$aUpName[] = $fileNameWithExt;
					}
				}
			}
			if (!empty($aUpName) && count($aUpName) > 0) {
				$strUpload = implode(' , ', $aUpName);
				$updatetxt .= ', Picture, ' . $strUpload;
				updateKeysnamePictureGroup($id, $keysname);
			}
		}
	}

	Func_Addlogs("[{$keysname}] Add ArticleGroup ID {$id} ");
	setRaiseMsg('Database successfully Add. ' . $strUpload, _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listgroup&keysname=" . $keysname);
	exit;
}

$aGroupAll = pg_getAllGroup($keysname);

include 'css/addgroup.php';
?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow col-md-8"> ADD GROUP </h1>
		<form action="" method="post" name="form1" id="form1" enctype="multipart/form-data">
			<input type="hidden" name="ac" value="add" />
			<div class="col-md-4 text-right"><button class="btn btn-mint submit" style="font-size: 24px; font-family: db_ozone_xregular; "> <i class="fa fa-save" style="font-size: 18px;"></i> Add &amp; Publish</button></div>
	</div>

</div>
<div id="page-content">
	<div class="row">
		<div class="row pad-btm">
		</div>
	</div>

	<br clear="all" />
	<?php echo displayRaiseMsg(); ?>

	<div class="fixed-fluid">
		<div class="fixed-sm-300 pull-sm-right" style="background-color: transparent !important;">
			<div class="panel">
				<div class="panel-body">
					<div class="form-horizontal">
						<p class="text-main text-bold text-uppercase mainTxtBorderGreen">Display</p>
						<div class="dropzone-container">
							<div class="fallback">
								<input class="toggle-switch" id="demo-allow-show" value="y" type="checkbox" name="status" checked>
								<label for="demo-allow-show"> <span style="line-height: 28px;">Close</span> | <span style="color: #8BC34A;line-height: 28px;"> Open </span> </label>
							</div>
						</div>
						<br />
					</div>
					<?php if ($isOpens['isOpenGroup']) { ?>
						<div class="form-group">
							<label class="col-sm-12 control-label text-left"><?php echo @$tagname['isOpenGroup']; ?></label>
							<div class="col-sm-12">
								<div class="select-md" style="width: 100%;margin-bottom: 20px;">
									<select name="parent_id" id="parent_id" style="font-size: 18px;width: 100%;">
										<option value="0">== Head Group ==</option>
										<?php

										foreach ($aGroupAll['data'] as $k => $v) {
											if ($v['group_parent_id'] == 0) {
												echo '<option value="' . $v['group_id'] . '" > ' . $v['group_name'] . '</option>';
												$aSubGroup = PG_getAllGroupByGID($v['group_id']);
												if ($aSubGroup['num_rows'] > 0) {
													foreach ($aSubGroup as $kk => $vv) {
														echo '<option value="' . $vv['group_id'] . '"style="color:#817F82;"> &nbsp;&nbsp;&nbsp;&nbsp;&rarr; ' . $vv['group_name'] . ' </option>';
													}
												}
											}
										}
										?>
									</select>
								</div>
							</div>
						</div>
						<br />
					<?php } ?>
				</div>
			</div>
			<?php include 'option/isAttachGroupAll.php'; ?>
			<?php include 'option/group_extraOption.php'; ?>
			<div class="panel">
				<div class="form-horizontal">
					<div class="panel-body">
						<p class="text-main text-bold text-uppercase mainTxtBorderGreen">Order By</p>
						<div class="dropzone-container">
							<div class="fallback" align="left">
								<div class="col-sm-12" style="padding-bottom: 10px;">
									<input type="text" name="sort" id="sort" class="form-control" placeholder="low to height" />
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php if ($isOpens['isGroupExtra1'] || $isOpens['isGroupExtra2'] || $isOpens['isGroupExtra3'] || $isOpens['isGroupExtra4'] || $isOpens['isGroupExtra5']) { ?>
				<div class="panel">
					<div class="panel-body">
						<div class="form-horizontal">
							<?php if ($isOpens['isGroupExtra1']) {
								include 'option/group_extra1.php';
							} ?>
							<?php if ($isOpens['isGroupExtra2']) {
								include 'option/group_extra2.php';
							} ?>
							<?php if ($isOpens['isGroupExtra3']) {
								include 'option/group_extra3.php';
							} ?>
							<?php if ($isOpens['isGroupExtra4']) {
								include 'option/group_extra4.php';
							} ?>
							<?php if ($isOpens['isGroupExtra5']) {
								include 'option/group_extra5.php';
							} ?>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
		<div class="fluid">
			<div class="row">
				<div class="col-md-12">
					<div class="tab-base">
						<ul class="nav nav-tabs">
							<?php
							foreach ($aConfig['language'] as $kLang => $vLang) {
								$act = ($kLang == DEFAULT_LANGEUAGE) ? 'active text-bold' : 'none_active';
								echo '<li class="' . $act . '"><a data-toggle="tab" href="#demo-lft-tab-' . $kLang . '">' . $vLang . '</a></li>';
							}
							if ($isOpens['isGroupAttachPic']) {
								echo '<li class="none_active"><a data-toggle="tab" href="#uploadPic">Uploads Picture</a></li>';
							} ?>
						</ul>
						<div class="tab-content">
							<?php foreach ($aConfig['language'] as $kLang => $vLang) { ?>
								<div id="demo-lft-tab-<?php echo $kLang; ?>" class="tab-pane fade <?php echo ($kLang == DEFAULT_LANGEUAGE) ? 'active in' : ' in'; ?>">

									<div class="form-group">
										<div><?php echo $tagname['isTitle']; ?></div>
										<input type="text" name="frm[<?php echo $kLang; ?>][name]" placeholder="<?php echo $tagname['isTitle']; ?>" class="form-control input-md" value="" autofocus />
									</div>
									<?php if ($isOpens['isGroupSlug']) { ?>
										<div class="form-group">
											<div><?php echo $tagname['isGroupSlug']; ?></div>
											<input type="text" name="frm[<?php echo $kLang; ?>][slug]" placeholder="<?php echo $tagname['isGroupSlug']; ?>" class="form-control input-md" value="" autofocus />
										</div>
									<?php } ?>
									<?php if ($isOpens['isGroupDetail']) { ?>
										<div class="form-group">
											<div><?php echo $tagname['isGroupDetail']; ?></div>
											<div>
												<?php if ($isOpens['EDTNAME'] === 'ckeditor5') {
												?> <div id="editor1_<?php echo $kLang; ?>" style="min-height: 600px;"><?php echo (@$aGroupEdit['content'][$kLang]['detail']) ? @$aGroupEdit['content'][$kLang]['detail'] : '&nbsp;'; ?></div>
													<textarea name="frm[<?php echo $kLang; ?>][detail]"
														id="output1_<?php echo $kLang; ?>"
														rows="5" style="display:none; width:100%;"></textarea>
												<?php } else {
												?>
													<textarea name="frm[<?php echo $kLang; ?>][detail]" class="<?php echo ($isOpens['EDTNAME'] === 'ckeditor') ? 'ckeditor' : 'demo-summernote'; ?>" rows="5" style="width:100%;font-size: 18px;"><?php echo (@$aGroupEdit['content'][$kLang]['detail']) ? @$aGroupEdit['content'][$kLang]['detail'] : '&nbsp;'; ?></textarea>
												<?php } ?>
											</div>
										</div>
									<?php } ?>
									<?php if ($isOpens['isGroupDetailExtra1']) { ?>
										<div class="form-group">
											<div><?php echo $tagname['isGroupDetailExtra1']; ?></div>
											<div>
												<?php if ($isOpens['EDTNAME'] === 'ckeditor5') {
												?> <div id="editor2_<?php echo $kLang; ?>" style="min-height: 600px;"><?php echo (@$aGroupEdit['content'][$kLang]['detailExtra1']) ? @$aGroupEdit['content'][$kLang]['detailExtra1'] : '&nbsp;'; ?></div>
													<textarea name="frm[<?php echo $kLang; ?>][detailExtra1]"
														id="output2_<?php echo $kLang; ?>"
														rows="5" style="display:none; width:100%;"></textarea>
												<?php } else {
												?>
													<textarea name="frm[<?php echo $kLang; ?>][detailExtra1]" class="<?php echo ($isOpens['EDTNAME'] === 'ckeditor') ? 'ckeditor' : 'demo-summernote'; ?>" rows="5" style="width:100%;font-size: 18px;"><?php echo (@$aGroupEdit['content'][$kLang]['detailExtra1']) ? @$aGroupEdit['content'][$kLang]['detailExtra1'] : '&nbsp;'; ?></textarea>
												<?php } ?>
											</div>
										</div>
									<?php } ?>
									<?php if ($isOpens['isGroupDetailExtra2']) { ?>
										<div class="form-group">
											<div><?php echo $tagname['isGroupDetailExtra2']; ?></div>
											<div>
												<?php if ($isOpens['EDTNAME'] === 'ckeditor5') {
												?> <div id="editor3_<?php echo $kLang; ?>" style="min-height: 600px;"><?php echo (@$aGroupEdit['content'][$kLang]['detailExtra2']) ? @$aGroupEdit['content'][$kLang]['detailExtra2'] : '&nbsp;'; ?></div>
													<textarea name="frm[<?php echo $kLang; ?>][detailExtra2]"
														id="output3_<?php echo $kLang; ?>"
														rows="5" style="display:none; width:100%;"></textarea>
												<?php } else {
												?>
													<textarea name="frm[<?php echo $kLang; ?>][detailExtra2]" class="<?php echo ($isOpens['EDTNAME'] === 'ckeditor') ? 'ckeditor' : 'demo-summernote'; ?>" rows="5" style="width:100%;font-size: 18px;"><?php echo (@$aGroupEdit['content'][$kLang]['detailExtra2']) ? @$aGroupEdit['content'][$kLang]['detailExtra2'] : '&nbsp;'; ?></textarea>
												<?php } ?>
											</div>
										</div>
									<?php } ?>

									<br clear="all" />
								</div>
							<?php } ?>
							<?php if ($isOpens['isGroupAttachPic']) { ?>
								<div id="uploadPic" class="tab-pane fade in">
									<div class="row">
										<div class="col-md-12">
											<div class="panel">
												<div class="panel-heading">
													<h3 class="panel-title"> จัดการรูปภาพที่แนบ</h3>
												</div>
												<div class="panel-body">
													<?php include('tab-uploads.php'); ?>
													<?php include('tab-uploads-list.php'); ?>
												</div>
											</div>
										</div>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	</form>
</div>

<?php
if ($isOpens['EDTNAME'] == 'ckeditor' || $isOpens['EDTNAME'] == 'ckeditor5') {
	$aConfig['editor_height'] = $aArticleConfig['editorheight'];
	$aConfig['isTabLoad'] = true;
	include('include/pages_inc/editor.php');
}
?>