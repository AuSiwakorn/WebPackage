<?php
$oUser = login_logout::getLoginData();

$lang 								= ($lang != '') ? $lang : 'th';
$aProvince 			= Arrays_province($lang);
$widthIcon 			= @$aArticleConfig['widthIcon'];
$keysname 				= REQ_get('keysname', 'request', 'str', '');
$ac 										= REQ_get('ac', 'request', 'str', '');
$file_id 					= REQ_get('fid', 'get', 'int', '');
$aArticleRows = PG_getArticles_page($keysname);
$id 										= @$aArticleRows['articles_id'];
$meta 								= REQ_get('meta', 'post', 'str', '');

///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array($ac, array('save', 'changstatus', 'settotop', 'deletearticle', 'sortall', 'deletetype', 'delfile', 'delpic', 'delicon', 'delicon2')) && $isSecurMode == 1) {
	setRaiseMsg('Secur Mode.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&inc=" . $inc . "&mp=" . $mp . "&keysname=" . $keysname);
	exit;
}
///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////

$aFilesAtt = array();
if ($isOpens['isAttachFile']) {
	$aFilesAtt = PG_getAllFilesByArticlesID($id);
}

if ($ac == 'save') {
	$is_error = 0;
	$txterror = 'Error : ';
	$updatetxt = '';

	/////////////////////////////////////////////////////////////////////////
	/////////////////////////Edit Pic Detail//////////////////////////
	$aEditimg = REQ_get('edit', 'request', 'array');
	$displaytime = REQ_get('displaytime', 'request', 'str');
	if (is_array($aEditimg) && count($aEditimg) > 0) {
		$arEdit = array();
		foreach ($aEditimg['fid'] as $k => $v) {
			$arEdit['title'] = (isset($aEditimg['title'][$v])) ? $aEditimg['title'][$v] : '';
			$arEdit['detail'] = (isset($aEditimg['detail'][$v])) ? $aEditimg['detail'][$v] : '';
			$arEdit['detail2'] = (isset($aEditimg['detail2'][$v])) ? $aEditimg['detail2'][$v] : '';
			PG_update_allData_img($v, $arEdit);
		}

		//setRaiseMsg('Database successfully picture updated.',_TIME_,0);
		//CustomRedirectToUrl("index.php?module=".$module."&mp=".$mp."&inc=".$inc."&keysname=".$keysname);
		//exit;
	}

	/////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////

	$d['id'] 					= $id;
	$d['group'] 			= ($group != '') ? $group : 0;
	$d['user_id'] 			= 0;
	$d['member_id'] 	= 0;
	$d['status'] 			=  (REQ_get('status', 'post', 'str') == 'y') ? 0 : 1;
	$d['displaytime'] 	= (@$displaytime != '') ? strtotime($displaytime) : _TIME_;  //ไม่แน่ใจ
	$d['charkeys'] 		= 0;
	$d['checkOption'] 	= REQ_get('checkOption', 'post', 'str', 0);
	$d['checkBoxOption']  = REQ_get('checkBoxOption', 'post', 'int', 0);
	$d['articles_url'] = REQ_get('articles_url', 'request', 'str', $aArticleConfig['articles_url']);
	$d['keywords'] = REQ_get('keywords', 'post', 'str', '');
	$d['extra1'] = REQ_get('extra1', 'post', 'str', '');
	$d['extra2'] = REQ_get('extra2', 'post', 'str', '');
	$d['extra3'] = REQ_get('extra3', 'post', 'str', '');
	$d['extra4'] = REQ_get('extra4', 'post', 'str', '');
	$d['extra5'] = REQ_get('extra5', 'post', 'str', '');
	$d['extra6'] = REQ_get('extra6', 'post', 'str', '');
	$d['extra7'] = REQ_get('extra7', 'post', 'str', '');
	$d['extra8'] = REQ_get('extra8', 'post', 'str', '');
	$d['extra9'] = REQ_get('extra9', 'post', 'str', '');
	$d['extra10'] = REQ_get('extra10', 'post', 'str', '');

	if (@$id == '') {
		$id = PG_addNewArticleGroup($d, '', '', '', $keysname);
	} else {
		PG_updateArticles_page($keysname, $d);
	}

	if ($isOpens['isMark']) {
		$aMark = @$_REQUEST['aMark'];
		if (is_array($aMark) && count($aMark) > 0) {
			foreach ($aMark as $k => $v) {
				$v = ($v == 1) ? 1 : 0;
				PG_updateIsMarkArticlesImg($k, $v);
			}
		}
	}

	$frm = REQ_get('frm', 'post', 'array', array('th'));
	$tranfer = REQ_get('tranfer', 'post', 'str', '') === 't';
	if ($tranfer && isset($frm[DEFAULT_LANGEUAGE])) {
		$baseData = $frm[DEFAULT_LANGEUAGE];
		foreach ($frm as $k => $v) {
			if ($k == DEFAULT_LANGEUAGE) continue;
			foreach ($baseData as $field => $value) {
				$frm[$k][$field] = $value;
			}
		}
	}
	foreach ($frm as $k => $v) {
		$title = str_replace(array("\""), "&quot;", htmlspecialchars($v['subject']));
		$title = str_replace("'", "\'", $title);

		$frm[$k]['articles_id'] = $id;
		$frm[$k]['content_id'] = $v['content_id'];
		$frm[$k]['title'] 			= mysqlPrep($title);
		$frm[$k]['author'] 		= mysqlPrep(@$v['author']);
		$frm[$k]['content_extra1'] = mysqlPrep(@$v['content_extra1']);
		$frm[$k]['content_extra2'] = mysqlPrep(@$v['content_extra2']);
		$frm[$k]['content_extra3'] = mysqlPrep(@$v['content_extra3']);
		$frm[$k]['shortMessage'] = mysqlPrep(@$v['shortMessage']);
		$frm[$k]['keywords'] 		= @$v['keywords'];
		$frm[$k]['content'] 			= @$v['content'];
		$frm[$k]['content2'] 		= (@$v['content2'] != '') ? $v['content2'] : '';
		$frm[$k]['content3'] 		= (@$v['content3'] != '') ? $v['content3'] : '';
		$frm[$k]['content4'] 		= (@$v['content4'] != '') ? $v['content4'] : '';
	}

	PG_updateArticlesContent_page($frm);
	foreach ($meta as $k => $aMetaAdd) {
		updateMetaTags($keysname, $k, $aMetaAdd);
	}

	PG_updateArticles($d);
	PG_updateArticlesContent($frm);

	#################################################################################################
	#################################   SET CONFIG  ####################################################
	$aExtenAttc = isset($aArticleConfig['attach']) ? $aArticleConfig['attach'] : array('pdf', 'doc', 'docx', 'xls', 'xlsx');
	$folder_attc = 'articles_attc/' . _YY_ . '_' . _MM_;
	$aExtenIcon = isset($aArticleConfig['icon']) ? $aArticleConfig['icon'] : array('jpg', 'png', 'gif');
	$folder_icon = 'articles_icon/' . _YY_ . '_' . _MM_;

	$path_attc_mkdir = PATH_UPLOAD . '/' . $folder_attc;
	if (!is_dir($path_attc_mkdir)) {
		mkdir($path_attc_mkdir, 0777, true);
	}

	$path_icon_mkdir = PATH_UPLOAD . '/' . $folder_icon;
	if (!is_dir($path_icon_mkdir)) {
		mkdir($path_icon_mkdir, 0777, true);
	}
	#################################################################################################

	if ($isOpens['isContentAttach']) {
		$contectAttach = @$_FILES['contectAttach'];
		foreach ($contectAttach['name'] as $k => $v) {
			if ($v != '') {
				$extention_conAtt = end(explode('.', $v));
				$extention_conAtt = strtolower($extention_conAtt);
				if (in_array($extention_conAtt, $aExtenAttc)) {
					$name_conAtt = $folder_attc . '/' . $id . '_contectAttach_' . _TIME_ . '_' . $k . '.' . $extention_conAtt;
					$file_Attc = PATH_UPLOAD . '/' . $name_conAtt;

					if (is_file($file_Attc)) {
						unlink($file_Attc);
					}
					if (move_uploaded_file($contectAttach['tmp_name'][$k], $file_Attc)) {
						PG_updateArticlesContentAttach($id, $k, $name_conAtt);
					}
				}
			}
		}
	}

	if ($isOpens['isContent_icon']) {
		$contectIcon = @$_FILES['contectIcon'];
		foreach ($contectIcon['name'] as $k => $v) {
			if ($v != '') {
				$extention = end(explode('.', $v));
				$extention = strtolower($extention);
				if (in_array($extention, $aExtenIcon)) {
					$nAttc = $folder_icon . '/' . $id . '_contectIcon_' . _TIME_ . '_' . $k . '.' . $extention;
					$fileAttc = PATH_UPLOAD . '/' . $nAttc;
					if (is_file($fileAttc)) {
						unlink($fileAttc);
					}
					if (move_uploaded_file($contectIcon['tmp_name'][$k], $fileAttc)) {
						PG_updateArticlesContentIcon($id, $k, $nAttc);
					}
				}
			}
		}
	}

	#################################################################################################
	################################# attach, iconview, iconview2 ###########################################

	//Delete Iconview
	$isIconView = REQ_get('isIconView', 'request', 'str', '');
	if ($isIconView === 'isiconfalse') {
		PG_unlinkArticlesFileIcon($id, 'icon');
	}

	$iconname = '';
	$fileiconviewUpload = @$_FILES['iconview'];
	if (isset($fileiconviewUpload) && count($fileiconviewUpload) > 0 && $fileiconviewUpload['error'] == 0) {
		$extention3 = strtolower(end(explode('.', $fileiconviewUpload['name'])));
		if (in_array($extention3, $aExtenIcon)) {
			$iconname = $folder_icon . '/' . $id . '_iconview_' . _TIME_ . '.' . $extention3;
			$filePath = PATH_UPLOAD . '/' . $iconname;
			if (move_uploaded_file($fileiconviewUpload['tmp_name'], $filePath)) {
				PG_unlinkArticlesFileIcon($id, 'icon');
				$updatetxt .= ', Icon 1 ';
			}
		} else {
			$txterror .= "  Icon type is not allow. ";
			$is_error = 1;
		}
	}

	//Delete Iconview2
	$isIconView2 = REQ_get('isIconView2', 'request', 'str', '');
	if ($isIconView2 === 'isiconfalse') {
		PG_unlinkArticlesFileIcon($id, 'icon2');
	}

	$iconname2 = '';
	$fileiconviewUpload2 = @$_FILES['iconview2'];
	if (isset($fileiconviewUpload2) && count($fileiconviewUpload2) > 0 && $fileiconviewUpload2['error'] == 0) {
		$extention2 = strtolower(end(explode('.', $fileiconviewUpload2['name'])));
		if (in_array($extention2, $aExtenIcon)) {

			$iconname2 = $folder_icon . '/' . $id . '_iconview2_' . _TIME_ . '.' . $extention2;
			$filePath2 = PATH_UPLOAD . '/' . $iconname2;
			if (move_uploaded_file($fileiconviewUpload2['tmp_name'], $filePath2)) {
				PG_unlinkArticlesFileIcon($id, 'icon2');
				$updatetxt .= ', Icon2 ';
			}
		} else {
			$txterror .= "  Icon2 type is not allow. ";
			$is_error = 1;
		}
	}

	PG_updateIconAttachByid($id, $iconname, $iconname2, $nameAttc);

	$fileExtraUpload = @$_FILES['attach'];
	$nameAttc = '';
	if ($id != '' && $fileExtraUpload['name'] != '' && $fileExtraUpload['tmp_name'] != '') {
		$extentionAtt = strtolower(end(explode('.', $fileExtraUpload['name'])));
		if (in_array($extentionAtt, $aExtenAttc)) {
			$nameAttc = $folder_attc . '/' . $id . '_attach_' . _TIME_ . '.' . $extentionAtt;
			$fileAttc = PATH_UPLOAD . '/' . $nameAttc;

			if (is_file($fileAttc)) {
				unlink($fileAttc);
			}
			if (move_uploaded_file($fileExtraUpload['tmp_name'], $fileAttc)) {
				PG_unlinkArticlesFileAttc($id);
			}
		}
	}



	if ($isOpens['isAttachFile']) {
		$time = _TIME_;
		$Year = _YY_;
		$Month = _MM_;
		$Day = _DD_;
		$aFileList = REQ_get('attachFileList', 'files', 'array', array());
		$aFileTextList =  REQ_get('attachTextFileList', 'request', 'array', array());

		///////////////////////////////////////////////////////////
		$aDetailChageFile = @$_REQUEST['aDetailChageFile'];
		$aDetailChageFileEn = @$_REQUEST['aDetailChageFileEn'];
		$aDetailSort = @$_REQUEST['sortimgFile'];
		$counterFile = PG_getSortFileByArticlesID($id);
		$counterSortFile = $counterFile['sort'] + 1;

		if (is_array($aDetailChageFile) && count($aDetailChageFile) > 0) {
			foreach ($aDetailChageFile as $k => $v) {
				$detailadd = strip_tags($v);
				$detailadd_en = strip_tags(@$aDetailChageFileEn[$k]);

				$ctime = isset($aDetailSort[$k]) ? $aDetailSort[$k] : $counterSortFile;

				PG_updateDetailFileAttach($k, $detailadd, $detailadd_en, $ctime);
			}
		}

		if (isset($aFileList['name']) && count($aFileList['name']) > 0) {
			$folder_file = 'articles_file/' . $Year . '_' . $Month;
			$filePath = PATH_UPLOAD . '/' . $folder_file . '/' . $id;
			if (!is_dir(PATH_UPLOAD . '/articles_file')) {
				mkdir(PATH_UPLOAD . '/articles_file', 0777);
			}
			if (!is_dir(PATH_UPLOAD . '/' . $folder_file)) {
				mkdir(PATH_UPLOAD . '/' . $folder_file, 0777);
			}
			if (!is_dir($filePath)) {
				mkdir($filePath, 0777);
			}

			$i = 1;
			foreach ($aFileList['error'] as $kFile => $vFile) {
				if ($vFile == 0) {
					$extention = strtolower(end(explode('.', $aFileList['name'][$kFile])));
					if (in_array($extention, $aExtenAttc)) {

						$fileName = $folder_file . '/' . $id . '_file_' . $time . '_' . $i . '.' . $extention;
						$fileUrl = PATH_UPLOAD . '/' . $fileName;

						if (is_file(PATH_UPLOAD . '/' . $fileName)) {
							unlink(PATH_UPLOAD . '/' . $fileName);
						}
						$i = $i + 1;

						if (move_uploaded_file($aFileList['tmp_name'][$kFile], $fileUrl)) {

							PG_addArticlesFiles($id, $fileName, @$aFileTextList[$kFile], @$aFileTextListEn[$kFile], $keysname, $counterSortFile);

							$counterSortFile++;
						}
					}
				}
			}
		}
	}

	############################################### Picture Attach ##################################################
	$aFile = isset($_FILES['attachFile']) ? $_FILES['attachFile'] : array();
	$aFileText = isset($_REQUEST['attachTextFile']) ? $_REQUEST['attachTextFile'] : array();
	$isResizeAttach = (@$_REQUEST['isResizeAttach'] == 1) ? 1 : 0;
	if (isset($aFile['name']) && count($aFile['name']) > 0) {

		/////////////// path for upload ////////////////
		$folder_pic = 'articles_pic/' . _YY_ . '_' . _MM_;
		$miniPath = PATH_UPLOAD . '/' . $folder_pic . '/' . $id . '/mini';
		$bigPath = PATH_UPLOAD . '/' . $folder_pic . '/' . $id . '/big';

		////////////////// check path //////////////////
		$aCheckPath = array(
			PATH_UPLOAD,
			PATH_UPLOAD . '/articles_pic',
			PATH_UPLOAD . '/' . $folder_pic,
			PATH_UPLOAD . '/' . $folder_pic . '/' . $id,
			$miniPath,
			$bigPath
		);

		foreach ($aCheckPath as $vPath) {
			if (!is_dir($vPath)) {
				mkdir($vPath, 0777);
			}
		}
		////////////////// check path //////////////////

		$i = 0;
		$time = _TIME_;
		$aUpName = array();
		$counter = PG_getSortPictureByArticlesID($id);
		$aSortimg = REQ_get('sortimg', 'request', 'array');
		if (is_array($aSortimg) && count($aSortimg) > 0) {
			foreach ($aSortimg as $k => $v) {
				PG_update_arSort_img($k, $v);
			}
		}
		$counterSort = $counter['sort'] + 1;
		foreach ($aFile['error'] as $kFile => $vFile) {
			if ($vFile == 0) {
				$i = $i + 1;
				$extention = strtolower(end(explode('.', $aFile['name'][$kFile])));

				$fileMini = $miniPath . '/' . _TIME_ . '_' . $i . '.' . $extention;
				$fileMiniUrl = $folder_pic . '/' . $id . '/mini/' . _TIME_ . '_' . $i . '.' . $extention;
				if (is_file($fileMini)) {
					unlink($fileMini);
				}

				$fileBig = $bigPath . '/' . _TIME_ . '_' . $i . '.' . $extention;
				$fileBigUrl = $folder_pic . '/' . $id . '/big/' . _TIME_ . '_' . $i . '.' . $extention;
				if (is_file($fileBig)) {
					unlink($fileBig);
				}

				if (move_uploaded_file($aFile['tmp_name'][$kFile], $fileBig)) {
					PG_addArticlesPicture2($counterSort, $id, $fileMiniUrl, $fileBigUrl, @$aFileText[$kFile]);
					$counterSort++;
					$aUpName[] = _TIME_ . '_' . $i . '.' . $extention;
					$thumb = new thumbnail();
					$thumbinc = $thumb->resize_thumbnail($fileBig);

					$thumb->size_width(300);
					$thumb->jpeg_quality(99);
					$thumb->save($fileMini);
					$aUpName[] = _TIME_ . '_' . $i . '.' . $extention;
				}
			}
		}

		if (count($aUpName) > 0) {
			$strUpload = implode(' , ', $aUpName);
			$updatetxt .= ', Picture, ' . $strUpload;
			updateKeysnamePicture($id, $keysname);
		}
	}
	setRaiseMsg('Database successfully updated .', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=" . $inc . "&keysname=" . $keysname);
	exit;
} elseif ($ac == 'delpic' && $file_id != '') {
	PG_deleteArticlesPictureId($file_id);
	PG_deleteArticlesPictureGroupId($file_id);
	setRaiseMsg('Picture successfully deleted.(File ID : ' . $file_id . ')', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=" . $inc . "&keysname=" . $keysname);
	exit;
} elseif ($ac == 'delfile' && $id != '') {
	PG_unlinkArticlesFileAttc($id);
	setRaiseMsg('Delete articles Attach File successfully.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=" . $inc . "&keysname=" . $keysname);
	exit;
} elseif ($ac == 'delicon' && $id != '') {
	PG_unlinkArticlesFileIcon($id, 'icon');
	setRaiseMsg('Delete articles ICON successfully.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=" . $inc . "&keysname=" . $keysname);
	exit;
} elseif ($ac == 'delicon2' && $id != '') {
	PG_unlinkArticlesFileIcon($id, 'icon2');
	setRaiseMsg('Delete articles ICON 2 successfully.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=" . $inc . "&keysname=" . $keysname);
	exit;
} elseif ($ac == 'delContentIcon') {
	$langdel = @$_REQUEST['langdel'];
	if ($langdel != '' && isset($aConfig['language'][$langdel])) {
		PG_unlinkArticleContentFileIcon($id, $langdel);
		setRaiseMsg('Delete Article Content ICON File successfully.', _TIME_, 0);
	}
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=edit&keysname=" . $keysname . "&id=" . $id);
	exit;
} elseif ($ac == 'delContentAttF') {
	$langdel = @$_REQUEST['langdel'];
	if ($langdel != '' && isset($aConfig['language'][$langdel])) {
		PG_unlinkArticleContentFileAttc($id, $langdel);
		setRaiseMsg('Delete Article Content Attach File successfully.', _TIME_, 0);
	}
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=edit&keysname=" . $keysname . "&id=" . $id);
	exit;
} elseif ($ac == 'changeStatus' && $id != '') {
	$ch = (@$_REQUEST['ch'] != 1) ? 0 : 1;
	PG_changeArticlesStatus($id, $ch);
	setRaiseMsg('Change status is successfully.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=" . $inc . "&keysname=" . $keysname);
	exit;
}

if ($isOpens['isAttachPic']) {
	$aPictureAtt = PG_getAllPictureByArticlesID($id);
}

$aDefaultMetaTxt 	= getDefaultMetatags();
foreach ($aConfig['language'] as $kLang => $vLang) {
	$aDefaultMeta[$kLang] 	= getMetaTagsByLang('default', $kLang);
	$aMeta[$kLang] 			= getMetaTagsByLang($keysname, $kLang);
	$aMetaUse[$kLang] = arrayMergeMeta($aDefaultMetaTxt, $aDefaultMeta[$kLang], $aMeta[$kLang]);
}
include 'css/pages.php';
?>
<form id="form1" name="form1" method="post" action="" enctype="multipart/form-data">
	<input type="hidden" name="ac" value="save" />
	<input type="hidden" name="id" value="<?php echo @$id ?>" />
	<div id="page-head">
		<div id="page-title">
			<div class="row">
				<div class="col-md-6">
					<h1 class="page-header text-overflow"><?php echo @$tagname['name']; ?></h1>
				</div>
				<div class="col-md-6 text-right">
					<?php
					if (@$isOpens['isOnOff'] == true) {
						$changeTo = (isset($aArticleRows['status']) && $aArticleRows['status'] != 1) ? 1 : 0;
					?>
						<a class="fallback btn" onclick="window.location.href='<?php echo _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=pages&keysname=' . $keysname . '&ac=changeStatus&ch=' . $changeTo); ?>'; " style="font-family: db_ozone_xregular;font-size: 22px;color: #FFF;" href="#">
							<input class="toggle-switch" id="demo-allow-show" value="y" type="checkbox" name="status" <?php echo (isset($aArticleRows['status']) && $aArticleRows['status'] == 1) ? '' : 'checked'; ?>>
							<label for="demo-allow-show"> <?php echo (isset($aArticleRows['status']) && $aArticleRows['status'] == 1) ? 'Hide' : 'Show'; ?></label>
						</a>
					<?php } else { ?>
						<input value="y" type="hidden" name="status" checked>
					<?php } ?>
					<button class="btn btn-mint submit" id="add-and-publish" style="font-size: 24px; font-family: db_ozone_xregular; "> <i class="fa fa-save" style="font-size: 18px;"></i> Add &amp; Publish</button>
				</div>
			</div>
		</div>
	</div>

	<div id="page-content">
		<?php echo displayRaiseMsg(); ?>
		<div class="fixed-fluid">
			<?php if (
				$isOpens['isDisplaydate']
				|| $isOpens['isEndDate']
				|| @$isOpens['isCheckBoxOption']
				|| @$isOpens['isCheckedOption']
				|| $isOpens['isExtra1']
				|| $isOpens['isExtra2']
				|| $isOpens['isExtra3']
				|| $isOpens['isExtra4']
				|| $isOpens['isExtra5']
				|| $isOpens['isIcon']
				|| $isOpens['isIcon2']
				|| $isOpens['isAttach']
			) { ?>
				<div class="fixed-sm-300 pull-sm-right" style="background-color: transparent !important;">
					<?php if ($isOpens['isDisplaydate']) { ?>
						<div class="panel">
							<div class="panel-body">
								<?php $displaytime = (@$aArticleRows['displaytime'] == '') ? _TIME_ : $aArticleRows['displaytime']; ?>
								<div class="form-group">
									<label class="col-sm-12 control-label text-left"><?php echo @$tagname['isDisplaydate']; ?></label>
									<div class="col-sm-12">
										<div id="demo-dp-component">
											<div class="input-group date">
												<input type="text" class="form-control" name="displaytime" id="displaytime" value="<?php echo @date('m/d/Y', $displaytime); ?>">
												<span class="input-group-addon"><i class="demo-pli-calendar-4"></i></span>
											</div>
										</div>
									</div>
								</div>
								<?php if ($isOpens['isEndDate']) { ?>
									<div class="form-group">
										<label for="endDate" class="col-sm-12 control-label text-left">End Date</label>
										<div class="col-sm-12">
											<div id="demo-dp-component">
												<div class="input-group date">
													<?php $curTime = (@$aArticleRows['end_time'] != '' && @$aArticleRows['end_time'] != 0) ? @date('m/d/Y', @$aArticleRows['end_time']) : @date('m/d/Y'); ?>
													<input type="text" class="form-control" name="endDate" id="endDate" value="<?php echo $curTime; ?>">
													<span class="input-group-addon"><i class="demo-pli-calendar-4"></i></span>
												</div>
												<small class="text-muted">
													<input type="checkbox" class="isEndDateShow" name="isEndDateShow" value="1" <?php echo (@$aArticleRows['end_time'] == '0' || @$aArticleRows['end_time'] == '') ? ' checked="checked" ' : ''; ?> /> เลือกกรณีที่ไม่มีวันหมดอายุ</label>
												</small>
											</div>
										</div>
									</div>
								<?php } ?>
							</div>
						</div>
					<?php } ?>
					<?php include('option/isAttachAll.php'); ?>


					<?php if (@$isOpens['isCheckBoxOption']) { ?>
						<div class="panel">
							<div class="panel-body">
								<div class="form-horizontal">
									<p class="text-main text-bold text-uppercase mainTxtBorderGreen">สถานะ</p>
									<div class="dropzone-container">
										<div class="fallback">
											<div class="checkbox">
												<input type="checkbox" class="magic-checkbox" id="checkBoxOption" name="checkBoxOption" value="1" <?php echo (!empty(@$aArticleRows['checkBoxOption']) && @$aArticleRows['checkBoxOption'] == 1) ? 'checked="checked" ' : ''; ?>>
												<label for="checkBoxOption"><?php echo $tagname['isCheckBoxOption']; ?></label>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?php } ?>

					<?php if (@$isOpens['isCheckedOption']) { ?>
						<div class="panel">
							<div class="panel-body">
								<div class="form-horizontal">
									<p class="text-main text-bold text-uppercase mainTxtBorderGreen">ประเภท</p>
									<div class="dropzone-container">
										<div class="fallback">
											<?php foreach ($aArticleConfig['isCheckedOptionData'] as $kCheck => $vCheck) { ?>
												<div class="radio">
													<input id="ty<?php echo $kCheck; ?>" class="magic-radio" type="radio" name="checkOption" value="<?php echo $kCheck; ?>" <?php echo (@$aArticleRows['checkOption'] == $kCheck || @$aArticleRows['checkOption'] != '') ? 'checked="checked" ' : ''; ?> />
													<label for="ty<?php echo $kCheck; ?>"><?php echo $vCheck; ?></label>
												</div>
											<?php } ?>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?php } ?>

					<?php if ($isOpens['isExtra1'] || $isOpens['isExtra2'] || $isOpens['isExtra3'] || $isOpens['isExtra4'] || $isOpens['isExtra5']) { ?>
						<div class="panel">
							<div class="panel-body">
								<div class="form-horizontal">
									<?php if ($isOpens['isExtra1']) {
										include('option/isExtra1.php');
									} ?>
									<?php if ($isOpens['isExtra2']) {
										include('option/isExtra2.php');
									} ?>
									<?php if ($isOpens['isExtra3']) {
										include('option/isExtra3.php');
									} ?>
									<?php if ($isOpens['isExtra4']) {
										include('option/isExtra4.php');
									} ?>
									<?php if ($isOpens['isExtra5']) {
										include('option/isExtra5.php');
									} ?>
								</div>
							</div>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
			<div class="fluid">
				<div class="row">
					<div class="col-md-12">
						<div class="tab-base">
							<ul class="nav nav-tabs">
								<?php
								foreach ($aConfig['language'] as $kLang => $vLang) {
									$act = ($kLang == DEFAULT_LANGEUAGE) ? 'active text-bold ' : 'none_active';
									echo '<li class="' . $act . '"><a data-toggle="tab" href="#demo-lft-tab-' . $kLang . '">' . $vLang . '</a></li>';
								}

								if ($isOpens['isAttachPic']) {
									echo '<li class="none_active"><a data-toggle="tab" href="#uploadPic">Uploads Picture</a></li>';
								}
								if ($isOpens['isAttachFile']) {
									echo '<li class="none_active"><a data-toggle="tab" href="#uploadFile">Uploads File</a></li>';
								}
								?>
							</ul>

							<div class="tab-content">
								<?php
								foreach (@$aConfig['language'] as $kLang => $vLang) {
									$subject         = stripslashes(@isset($aArticleRows['content'][$kLang]['title']) ? $aArticleRows['content'][$kLang]['title'] : '');
									$shortMessage   	= stripslashes(@isset($aArticleRows['content'][$kLang]['shortMessage']) ? $aArticleRows['content'][$kLang]['shortMessage'] : '');
									$slug		   = stripslashes(@isset($aArticleRows['content'][$kLang]['slug']) ? $aArticleRows['content'][$kLang]['slug'] : '');
									$keywords   					= stripslashes(@isset($aArticleRows['content'][$kLang]['keywords']) ? $aArticleRows['content'][$kLang]['keywords'] : '');
									$content_id      = @isset($aArticleRows['content'][$kLang]['ar_content_id']) ? $aArticleRows['content'][$kLang]['ar_content_id'] : '';
									$content         = stripslashes(@isset($aArticleRows['content'][$kLang]['content']) ? $aArticleRows['content'][$kLang]['content'] : '');
									$content2        = stripslashes(@isset($aArticleRows['content'][$kLang]['content2']) ? $aArticleRows['content'][$kLang]['content2'] : '');
									$content3        = stripslashes(@isset($aArticleRows['content'][$kLang]['content3']) ? $aArticleRows['content'][$kLang]['content3'] : '');
									$content4        = stripslashes(@isset($aArticleRows['content'][$kLang]['content4']) ? $aArticleRows['content'][$kLang]['content4'] : '');
									$author          = stripslashes(@isset($aArticleRows['content'][$kLang]['author']) ? $aArticleRows['content'][$kLang]['author'] : '');
								?>

									<input type="hidden" name="frm[<?php echo $kLang; ?>][content_id]" value="<?php echo $content_id; ?>">
									<div id="demo-lft-tab-<?php echo $kLang; ?>" class="tab-pane fade <?php echo ($kLang == DEFAULT_LANGEUAGE) ? 'active in' : ' in'; ?>">
										<?php if (count($aConfig['language']) > 1 && $kLang == DEFAULT_LANGEUAGE) { ?>
											<div class="col-md-4">
												<div class="checkbox">
													<input id="demo-checkbox-2" class="magic-checkbox" type="checkbox" name="tranfer" value="t">
													<label for="demo-checkbox-2">นำข้อมูลทั้งหมดไปอีกภาษา (ข้อมูลจะถูกทับ)</label>
												</div>
											</div>
											<br clear="all" />
										<?php } ?>

										<?php if ($isOpens['isTitle']) { ?>
											<div class="form-group">
												<label class="control-label"><?php echo $tagname['isTitle']; ?></label>
												<input type="text" name="frm[<?php echo $kLang; ?>][subject]" placeholder="<?php echo $tagname['isTitle']; ?>" class="form-control input-md" value="<?php echo $subject; ?>" autofocus />
											</div>
										<?php } ?>

										<?php if ($isOpens['isSlug']) { ?>
											<div class="form-group">
												<label class="control-label"><?php echo $tagname['isSlug']; ?></label>
												<input type="text" name="frm[<?php echo $kLang; ?>][slug]" placeholder="<?php echo @$tagname['isSlug']; ?>" value="<?php echo $slug; ?>" class="form-control input-md">
											</div>
										<?php } ?>

										<?php if ($isOpens['isKeyswords']) { ?>
											<div class="form-group">
												<label class="control-label"><?php echo $tagname['isKeyswords']; ?></label>
												<input type="text" name="frm[<?php echo $kLang; ?>][keywords]" placeholder="<?php echo @$tagname['isKeyswords']; ?>" value="<?php echo $keywords; ?>" class="form-control input-md">
											</div>
										<?php } ?>

										<?php if ($isOpens['isAuthor']) { ?>
											<div class="form-group">
												<label class="control-label"><?php echo $tagname['isAuthor']; ?></label>
												<input type="text" name="frm[<?php echo $kLang; ?>][author]" placeholder="<?php echo $tagname['isAuthor']; ?>" value="<?php echo $author; ?>" class="form-control input-md">
											</div>
										<?php } ?>

										<?php if ($isOpens['isShortMessage']) { ?>
											<div class="form-group">
												<label class="control-label"><?php echo $tagname['isShortMessage']; ?></label>
												<textarea name="frm[<?php echo $kLang; ?>][shortMessage]" class="form-control" rows="3"><?php echo $shortMessage; ?></textarea>
											</div>
										<?php } ?>

										<?php if ($keysname == 'album') { ?>
											<div id="uploadPic" class="tab-pane fade in">
												<div class="row">
													<div class="col-md-12">
														<div class="panel">
															<div class="panel-heading">
																<h3 class="panel-title "> จัดการรูปภาพแนบ </h3>
															</div>
															<div class="panel-body">
																<?php include('tab-uploads.php'); ?>
																<?php
																if ($aPictureAtt['num_rows'] > 0) {
																	echo '<div class="text-main text-bold" style="font-size: 20px;margin-top: 20px;"> รูปภาพแนบทั้งหมด <span class="text-bold" style="color: #f38311e3; font-size: 20px;">(' . @$aPictureAtt['num_rows'] . ')</span> รูป</div>';
																	include('tab-uploads-list.php');
																} ?>
															</div>
														</div>
													</div>
												</div>
											</div>
										<?php } ?>







										<?php include('option/isContent1.php'); ?>
										<?php include('option/isContent2.php'); ?>
										<?php include('option/isContent3.php'); ?>
										<?php include('option/isContent4.php'); ?>

										<?php
										if ($isOpens['isContentExtra1'] || $isOpens['isContentExtra2'] || $isOpens['isContentExtra3']) {
											$contentExtra1 = stripslashes(@isset($aArticleRows['content'][$kLang]['content_extra1']) ? $aArticleRows['content'][$kLang]['content_extra1'] : '');
											$contentExtra2 = stripslashes(@isset($aArticleRows['content'][$kLang]['content_extra2']) ? $aArticleRows['content'][$kLang]['content_extra2'] : '');
											$contentExtra3 = stripslashes(@isset($aArticleRows['content'][$kLang]['content_extra3']) ? $aArticleRows['content'][$kLang]['content_extra3'] : '');
											include('option/content_extra.php');
										}
										?>

										<?php if ($isOpens['isContent_icon'] || $isOpens['isContentAttach']) { ?>
											<div class="row">
												<?php if ($isOpens['isContent_icon']) { ?>
													<div class="col-sm-6">
														<p class="text-main text-bold text-uppercase mainTxtBorderGreen" style="max-width: 190px;"><?php echo @$tagname['isContent_icon']; ?></p>
														<div class="dropzone-container">
															<div class="fallback">
																<?php
																if (!empty($aArticleRows['content'][$kLang]['content_icon'])) {
																	$ucaf = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=pages&keysname=' . $keysname . '&id=' . $aArticleRows['articles_id'] . '&ac=delContentIcon&langdel=' . $kLang);
																	echo '<a href="' . URL_UPLOAD . '/' . $aArticleRows['content'][$kLang]['content_icon'] . '" target="_blank"><img src="' . URL_UPLOAD . '/' . $aArticleRows['content'][$kLang]['content_icon'] . '" height="80" border="0" /></a>';
																	echo '<br/><a href="' . $ucaf . '" style="color: #FF0048;font: 16px \'db_ozone_xregular\' !important;">[ ลบไฟล์นี้ ]</a>';
																} else {
																	echo '
																		<div class="form-group">
																			<span class="pull-left btn btn-primary btn-file" style="font: 18px \'db_ozone_xregular\' !important;"> 
																				<i class="demo-pli-upload-to-cloud icon-5x" style="font-size: 20px;"></i> Browse... <input type="file" name="contectIcon[' . $kLang . ']" id="contectIcon[' . $kLang . ']">
																			</span>
																			<br clear="all" />
																		</div>
																		';
																} ?>
															</div>
														</div>
													</div>
												<?php } ?>

												<?php if ($isOpens['isContentAttach']) { ?>
													<div class="col-sm-6">
														<p class="text-main text-bold text-uppercase mainTxtBorderGreen" style="max-width: 190px;"><?php echo @$tagname['isContentAttach']; ?></p>
														<div class="dropzone-container">
															<div class="fallback">
																<?php
																if (!empty($aArticleRows['content'][$kLang]['contentAttach'])) {
																	$ucaf2 = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=pages&keysname=' . $keysname . '&id=' . $aArticleRows['articles_id'] . '&ac=delContentAttF&langdel=' . $kLang);
																	echo '<a href="' . URL_UPLOAD . '/' . $aArticleRows['content'][$kLang]['contentAttach'] . '" target="_blank"><i class="fa fa-file-pdf-o" style="font-size: 22px;color: #FF0048;"></i> เปิดไฟล์แนบ </a>';
																	echo '<br/><a href="' . $ucaf2 . '" style="color: #FF0048;font: 16px \'db_ozone_xregular\' !important;">[ ลบไฟล์นี้ ]</a>';
																} else {
																	echo '
																		<div class="form-group">
																			<span class="pull-left btn btn-primary btn-file" style="font: 18px \'db_ozone_xregular\' !important;"> 
																				<i class="demo-pli-upload-to-cloud icon-5x" style="font-size: 20px;"></i> Browse... <input type="file" name="contectAttach[' . $kLang . ']" id="contectAttach[' . $kLang . ']">
																			</span>
																			<br clear="all" />
																		</div>
																		';
																} ?>
															</div>
														</div>
													</div>
												<?php } ?>
											</div>
										<?php } ?>


									</div>
								<?php } ?>


								<?php if ($isOpens['isAttachPic']) { ?>
									<div id="uploadPic" class="tab-pane fade in">
										<div class="row">
											<div class="col-md-12">
												<div class="panel">
													<div class="panel-heading">
														<h3 class="panel-title "> จัดการรูปภาพแนบ </h3>
													</div>
													<div class="panel-body">
														<?php include('tab-uploads.php'); ?>
														<?php
														if ($aPictureAtt['num_rows'] > 0) {
															echo '<div class="text-main text-bold" style="font-size: 20px;margin-top: 20px;"> รูปภาพแนบทั้งหมด <span class="text-bold" style="color: #f38311e3; font-size: 20px;">(' . @$aPictureAtt['num_rows'] . ')</span> รูป</div>';
															include('tab-uploads-list.php');
														} ?>
													</div>
												</div>
											</div>
										</div>
									</div>
								<?php } ?>

								<?php if ($isOpens['isAttachFile']) { ?>
									<div id="uploadFile" class="tab-pane fade in">
										<div class="row">
											<div class="col-md-12">
												<div class="panel">
													<div class="panel-heading">
														<h3 class="panel-title"> จัดการไฟล์ที่แนบ</h3>
													</div>
													<div class="panel-body">
														<?php include('tab-uploads-file.php'); ?>
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
				<?php
				if (@$isOpens['isSeo']) {
					include('option/isSeo.php');
				}
				?>
			</div>
		</div>


		<?php Func_HelpUse('articles/help/pages.txt'); ?>

	</div>
</form>




<?php
if ($isOpens['EDTNAME'] == 'ckeditor' || $isOpens['EDTNAME'] == 'ckeditor5') {
	$aConfig['editor_height'] = $aArticleConfig['editorheight'];
	$aConfig['isTabLoad'] = true;
	include('include/pages_inc/editor.php');
}
?>