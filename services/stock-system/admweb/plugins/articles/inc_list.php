<?php
$page 		= REQ_get('page', 'get', 'int', 1);
$ac 		= REQ_get('ac', 'request', 'str');
$id 		= REQ_get('id', 'get', 'int');
$gid 	= REQ_get('gid', 'request', 'int');
$ch 		= REQ_get('ch', 'get', 'str');
$isStatus 	= REQ_get('isStatus', 'get', 'str', 'all');
$keysword 	= REQ_get('keysword', 'request', 'str');
$keysname 	= REQ_get('keysname', 'request', 'str', '');
$viewyear = REQ_get('veiwyear', 'get', 'str');
$numlist = REQ_get('numlist', 'get', 'int', '20');

$time 		= _TIME_;
///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array($ac, array('search', 'sortlist', 'deletearticle')) && $isSecurMode == 1) {
	setRaiseMsg('Secur Mode.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listtype&keysname=" . $keysname);
	exit;
}
///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////

if ($ac == 'search') {
	$aData = pg_getAllArticles_search($keysname, $keysword, 30, $page);
	$url = _admin_buil_link('?module=' . $module . '&mp=' . $mp . '&inc=list&keysname=' . $keysname . '&keysword=' . $keysword . '&ac=' . $ac);
} elseif ($ac == 'changstatus' && $id != '') {
	PG_changeArticlesStatus($id, $ch);
	setRaiseMsg('Database successfully change status.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=list&keysname=" . $keysname . "&page=" . $page . "&viewyear=" . $viewyear . "&numlist=" . $numlist . "&gid=" . $gid);
	exit;
} elseif ($ac == 'sortlist') {
	$aSortList = @$_POST['aSortList'];
	if (count(@$aSortList) > 0) {
		foreach ($aSortList as $k => $v) {
			PG_setArticlesSort($k, $v);
		}
	}

	setRaiseMsg('Database successfully set to top.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=list&keysname=" . $keysname . "&page=" . $page . "&viewyear=" . $viewyear . "&numlist=" . $numlist . "&gid=" . $gid);
	exit;
} elseif ($ac == 'deletearticle') {
	$aIDList = $_POST['aIDList'];
	if (count(@$aIDList) > 0) {
		foreach ($aIDList as $k => $v) {
			PG_deleteArticles($v);
			Func_Addlogs("[{$keysname}] Delete Article ID {$v} ");
		}
		setRaiseMsg('Delete data is successfully.', _TIME_, 0);
	} else {
		setRaiseMsg('Please selete article for delete.', _TIME_, 1);
	}
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=list&keysname=" . $keysname . "&viewyear=" . $viewyear . "&numlist=" . $numlist . "&gid=" . $gid);
} else {
	if ($isOpens['isOpenGroup'] == true && $gid != '') {
		$aGroupList = PG_getAllGroupSelect($keysname);
		if ($viewyear != '') {
			$aData = pg_getAllArticlesByGroupYear($viewyear, $gid, $keysname, $numlist, $page);
		} else {
			$aData = pg_getAllArticlesByGroup($gid, $keysname, $numlist, $page);
		}
		$url = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=list&keysname=' . $keysname . '&gid=' . $gid . '&viewyear=' . $viewyear . '&numlist=' . $numlist);
	} else {
		$aData = pg_getAllArticles($keysname, $numlist, $page);
		$url = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=list&keysname=' . $keysname . '&numlist=' . $numlist);
	}
}

if ($isOpens['isOpenGroup'] == true) {
	$aGroupList = PG_getAllGroupSelect($keysname);
}

$urladd = _admin_buil_link("index.php?module=" . $module . "&mp=" . $mp . "&inc=add&keysname=" . $keysname . "&gid=" . $gid);
$urlsearch = _admin_buil_link("index.php?module=" . $module . "&mp=" . $mp . "&inc=list&keysname=" . $keysname . "&gid=" . $gid);
$aGroupAll = pg_getAllGroup($keysname, 0, 1);
$r = $rMain = array();
if (is_array(@$aGroupAll['data']) && @$aGroupAll['num_rows'] > 0) {
	foreach (@$aGroupAll['data'] as $k => $v) {
		if ($v['group_parent_id'] != 0) {
			$r[$v['group_parent_id']][$v['group_id']] = $v;
		} else {
			$rMain[$k] = $v;
		}
	}
	foreach ($rMain as $k => $v) {
		$rMain[$k] = $v;
		$rMain[$k]['sub'] = @$r[$v['group_id']];
	}
}

if ($isOpens['isOpenGroup'] == true) {
	$listgroup = _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=' . _MP_ . '&inc=listgroup&keysname=' . $keysname);
	$addgroup = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=addgroup&keysname=' . $keysname);
}
?>

<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow"><?php echo ($tagname['name']) ? $tagname['name'] : 'Category'; ?></h1>
	</div>
</div>
<?php if ($isOpens['isSearch'] == true) { ?>
	<div id="page-content">
		<div class="row">
			<div class="col-md-12 pad-hor">
				<form action="<?php echo $urlsearch; ?>" method="post">
					<input type="hidden" name="ac" value="search">
					<div class="input-group mar-btm">
						<input type="text" placeholder="Search posts..." class="form-control input-lg" name="keysword" value="<?php echo $keysword; ?>">
						<span class="input-group-btn">
							<button class="btn btn-primary btn-lg input-lg" type="submit" style="margin-left: 5px;">Search</button>
						</span>
					</div>
				</form>
			</div>
		</div>
	</div>
<?php } ?>

<form id="form1" name="form1" method="post" action="">
	<input type="hidden" name="ac" value="sortlist" class="ac" />
	<div id="page-content">
		<div class="row">
			<div class="col-xs-12">
				<?php if ($isOpens['isSearch'] == true) { ?>
					<div class="row">
						<?php if ($isOpens['isOpenGroup'] == false && $ac != 'search') {
						?>
							<div class="col-md-12">
								<div class="pad-all text-center dbfont20">
									<?php
									if ($isOpens['isOpenGroup'] == true && $ac != 'search') {
									?>
										<div class="box-inline">
											Only in categories :
											<div class="select">
												<select name="gouplist" onchange="window.location = window.location+'&viewyear=<?php echo $viewyear; ?>&numlist=<?php echo $numlist; ?>&gid='+this.value;" style="width: 250px;">
													<option value="">All Group</option>
													<?php
													$curparent_id = @$aGroupEdit['group_parent_id'];
													if (count($rMain) > 0) {
														foreach ($rMain as $k => $v) {
															$rMain[$k] = $v;
															$rMain[$k]['sub'] = @$r[$v['group_id']];
															$se = ($rMain[$k]['group_id'] == $gid) ? ' selected="selected" ' : '';
															echo '<option value="' . $rMain[$k]['group_id'] . '" ' . $se . '> ' . $rMain[$k]['group_name'] . '</option>';
															if ($rMain[$k]['sub'] != '') {
																foreach ($rMain[$k]['sub'] as $kk => $vv) {
																	$rMain[$k] = $vv;
																	$se = ($rMain[$k]['group_id'] == $gid) ? ' selected="selected" ' : '';
																	echo '<option value="' . $vv['group_id'] . '" ' . $se . ' style="color:#969696;text-shadow: black;">&nbsp;&nbsp;&rArr;' . $vv['group_name'] . '</option>';
																	$num = PG_getAllGroupByGID($vv['group_id']);
																	foreach ($num as $kkk => $vvv) {
																		$se = ($rMain[$k]['group_id'] == $gid) ? ' selected="selected" ' : '';
																		echo '<option value="' . $vvv['group_id'] . '" ' . $se . ' style="color:#817F82;"> &nbsp;&nbsp;&nbsp;&nbsp;&rarr;' . $vvv['group_name'] . '</option>';
																	}
																}
															}
														}
													}
													?>
												</select>
											</div>
										</div>
									<?php } ?>

									<div class="box-inline">
										<?php
										$startYear = date('Y') + 1;
										$endYear = $startYear - 5;
										if ($isOpens['isDisplaydate']) {
										?>
											<div class="select">
												<select id="demo-ease" name="yearlist" onchange="window.location = window.location+'&gid=<?php echo $gid; ?>&numlist=<?php echo $numlist; ?>&viewyear='+this.value;">
													<option value="">ดูข้อมูลทุกปี</option>
													<?php
													for ($i = $startYear; $i > $endYear; $i--) {
														$se = ($viewyear == $i) ? ' selected="selected" ' : '';
													?>
														<option <?php echo $se; ?> value="<?php echo $i; ?>"><?php echo $i; ?></option>
													<?php } ?>
												</select>
											</div>
										<?php } ?>
									</div>

									<div class="box-inline">
										<div class="select">
											<select id="demo-ease2" name="numlist" onchange="window.location = window.location+'&viewyear=<?php echo $viewyear; ?>&gid=<?php echo $gid; ?>&numlist='+this.value;">
												<option <?php echo ($numlist == 100) ? 'selected="selected"' : ''; ?> value="100">แสดง 100</option>
												<option <?php echo ($numlist == 50) ? 'selected="selected"' : ''; ?> value="50">แสดง 50</option>
												<option <?php echo ($numlist == 25) ? 'selected="selected"' : ''; ?> value="25">แสดง 25</option>
												<option <?php echo ($numlist == 20) ? 'selected="selected"' : ''; ?> value="20">แสดง 20</option>
												<option <?php echo ($numlist == 10) ? 'selected="selected"' : ''; ?> value="10">แสดง 10</option>
											</select>
										</div>
									</div>
									<button class="btn btn-dark">Filter</button>
								</div>
							</div>
						<?php } ?>
					</div>
				<?php } ?>
				<?php echo displayRaiseMsg(); ?>

				<div class="row">
					<div class="col-md-6 text-left">
						<?php if ($isOpens['isOpenGroup'] != false) {
						?>
							<a href="<?php echo $listgroup; ?>">
								<div id="demo-btn-addrow" class="btn btn-info"><i class="fa fa-server" style="font-size: 12px;"></i> จัดการหมวดหมู่ทั้งหมด </div>
							</a>
							<?php /* siwakorn ?>
							<a href="<?php echo $addgroup; ?>">
								<div id="demo-btn-addrow" class="btn btn-mint"><i class="fa fa-plus-circle" style="font-size: 12px;"></i> เพิ่มหมวดหมู่ </div>
							</a>
							<?php */ ?>
						<?php } ?>
						<a href="<?php echo $urladd; ?>">
							<div id="demo-btn-addrow" class="btn btn-success"><i class="fa fa-plus" style="font-size: 12px;"></i> New Post </div>
						</a>
					</div>
					<div class="col-md-6 text-right">
						<button id="demo-btn-addrow" class="btn btn-success" onclick="$('.ac').val('sortlist');"> <i class="fa fa-sort-numeric-asc" style="font-size: 12px;"></i> Reorder</button>
						<button id="demo-btn-addrow" class="btn btn-danger" onclick="return confirmPostAction();"> <i class="fa fa-times-circle" style="font-size: 12px;"></i> Delete the selected item. </button>
					</div>
				</div>
				<br>
				<div class="panel">
					<div class="panel-body">
						<div class="table-responsive">
							<table class="table table-striped table-vcenter">
								<thead>
									<tr>
										<th>ID</th>
										<?php if ($isOpens['isIcon']) { ?><th></th><?php } ?>
										<th>Post Title</th>
										<?php if ($isOpens['isDisplaydate']) { ?><th>ตั้งเวลา</th> <?php } ?>
										<th>Creation Date</th>
										<?php if ($isOpens['isOpenGroup'] == true) { ?><th>Categories</th><?php } ?>
										<?php if ($isOpens['isPreview'] == true) { ?><th class="text-center">Preview</th><?php } ?>
										<th>Sort</th>
										<th>Status</th>
										<th class="text-center" colspan="2">Action</th>
									</tr>
								</thead>
								<tbody style="font-size: 18px !important;">
									<?php
									if (@$aData['num_rows'] > 0) {
										//<img data-src="holder.js/200x200" class="rounded mx-auto d-block" alt="200x200" src="" data-holder-rendered="true" style="width: 200px; height: 200px;">
										foreach ($aData['data'] as $k => $v) {
											if ($gid != '') {
												$urledit = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=edit&id=' . $v['articles_id'] . '&gid=' . $gid . '&keysname=' . $keysname);
											} else {
												$urledit = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=edit&id=' . $v['articles_id'] . '&keysname=' . $keysname);
											}
											if ($v['status'] != 1) {
												$statusTitle = '<i class="fa fa-toggle-on" style="font-size: 20px;color: #8BC34A;"></i>';
												$urlchangestatus = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=list&id=' . $v['articles_id'] . '&ch=1&ac=changstatus&viewyear=' . $viewyear . '&numlist=' . $numlist . '&page=' . $page . '&keysname=' . $keysname . "&gid=" . $gid);
											} else {
												$statusTitle = '<i class="fa fa-toggle-off" style="font-size: 20px;"></i>';
												$urlchangestatus = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=list&id=' . $v['articles_id'] . '&ch=0&ac=changstatus&viewyear=' . $viewyear . '&numlist=' . $numlist . '&page=' . $page . '&keysname=' . $keysname . "&gid=" . $gid);
											}

											$img = ($v['icon']) ? URL_UPLOAD . '/' . $v['icon'] : 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1632068b8de%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1632068b8de%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.4296875%22%20y%3D%22104.5%22%3E200x200%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
									?>
											<tr>
												<td><?php echo $v['articles_id']; ?></td>
												<?php if ($isOpens['isIcon']) { ?><td><img class="img-responsive img-sm" src="<?php echo $img; ?>" alt="thumbs"></td><?php } ?>
												<td>
													<div class="row">
														<div class="col-md-12">
															<a style="font-size: 20px;" class="btn-link" href="<?php echo $urledit; ?>"><?php echo mb_substr(($v['title']) ? $v['title'] : '---', 0, 200, "utf-8"); ?></a> &nbsp; &nbsp;
															<?php
															if ($isOpens['isAttach']) {
																if ($v['file_attach'] != '') {
																	$u_attc = URL_UPLOAD . '/' . $v['file_attach'];
																	echo '<a href="' . $u_attc . '" target="_blank"><i class="fa fa-file-pdf-o" style="font-size: 18px;color: #e61414;"></i></a>';
																}
															}
															?>
														</div>
													</div>
												</td>
												<?php if ($isOpens['isDisplaydate']) { ?>
													<td>
														<?php

														echo strTimeFormat($v['displaytime'], "d-m-Y");
														if ($isOpens['isEndDate']) {
															if ($v['end_time'] < $time) {
																echo ($v['end_time'] == 0) ? '<span style="color: #9cc56c;"> ไม่มีวันหมดอายุ </span>' : '<span style="color: #ffb300;"> หมดเวลาที่ตั้งไว้ </span>';
															} else {
																echo 'ถึง : ' . strTimeFormat($v['end_time'], "d-m-Y");
															}
														}
														?>
													</td>
												<?php } ?>
												<td><span class="text-muted"><?php echo strTimeFormat($v['displaytime'], "d-m-Y"); ?></span></td>
												<?php if ($isOpens['isOpenGroup'] == true) { ?><td><?php if ($v['group_id'] > 0) {
																										$ag = PG_getArticlesGroupByID($v['group_id']);
																										echo $ag['content'][$lang]['group_name'];
																									} else {
																										echo '-- ยังไม่เลือก --';
																									} ?></td><?php } ?>
												<?php if ($isOpens['isPreview'] == true) { ?><td class="text-center"><?php echo $v['preview']; ?></td><?php } ?>
												<td><input type="text" name="aSortList[<?php echo $v['articles_id']; ?>]" value="<?php echo $v['sorttime']; ?>" style="width:70px;text-align: center;" placeholder="เลขน้อย-มาก" /></td>
												<td><a href="<?php echo $urlchangestatus; ?>" title="คลิกเพื่อทำการเปลี่ยน status"><?php echo $statusTitle; ?></a></td>
												<td class="min-width">
													<div class="btn-groups">
														<!-- <a href="#" class="btn btn-icon demo-pli-file-text-image icon-lg add-tooltip" data-original-title="View post" data-container="body"></a>  -->
														<a href="<?php echo $urledit; ?>" class="btn btn-icon demo-pli-pen-5 icon-lg add-tooltip" data-original-title="Edit Post" data-container="body"></a>
														<!-- <a href="#" class="btn btn-icon demo-pli-trash icon-lg add-tooltip" data-original-title="Remove" data-container="body"></a> -->
													</div>
												</td>
												<td><input type="checkbox" name="aIDList[]" value="<?php echo $v['articles_id']; ?>" /></td>
											</tr>
									<?php }
									} ?>
								</tbody>
							</table>
						</div>

						<div class="row">
							<div class="col-sm-5">
								<div>Showing of <span style="font-size: 18px;padding: 2px 8px;font-weight: bold;"><?php echo @$aData['num_rows']; ?></span> entries</div>
							</div>
							<div class="col-sm-7 text-right">
								<?php BuilListPage(@$aData, @$url, @$page); ?>
							</div>
						</div>
					</div>
				</div>

			</div>
		</div>
	</div>
</form>
<?php
if ($isOpens['isSeoArticleList'] == true) {
	echo '<div id="page-content"><div class="row"><div class="col-xs-12">';
	Func_SEOSet($keysname);
	echo '</div></div></div>';
}
?>