	<?php
	$page 	= REQ_get('page', 'get', 'int', 1);
	$ac 	= REQ_get('ac', 'request', 'str');
	$id 	= REQ_get('id', 'get', 'int', '');
	$ch 	= REQ_get('ch', 'get', 'str');
	$keysname 	= REQ_get('keysname', 'request', 'str', '');
	$numlist = REQ_get('numlist', 'get', 'int', '20');
	$time 	= _TIME_;
	///////////////////////////////////////////////////////
	/////////////////// isSecurMode /////////////////////
	///////////////////////////////////////////////////////
	$isSecurMode = GlobalConfig_get('isSecurMode');
	if (in_array($ac, array('changstatus', 'settotop', 'deletearticle', 'sortall')) && $isSecurMode == 1) {
		setRaiseMsg('Secur Mode.', _TIME_, 0);
		CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listtype&keysname=" . $keysname);
		exit;
	}
	///////////////////////////////////////////////////////
	/////////////////// isSecurMode /////////////////////
	///////////////////////////////////////////////////////

	if ($ac == 'settotop' && $id != '') {
		/*s
     PG_setArticlesGroupToTop($id);
     setRaiseMsg('Database successfully set to top.',_TIME_,0);
     CustomRedirectToUrl("index.php?module=".$module."&mp=".$mp."&inc=listgroup&keysname=".$keysname);
     exit;
     */
	} elseif ($ac == 'changstatus' && $id != '') {
		PG_setArticlesGroupToStatus($id, $ch);
		setRaiseMsg('Database successfully change status.', _TIME_, 0);
		CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listgroup&keysname=" . $keysname);
		exit;
	} elseif ($ac == 'sortall') {
		$sortlist = @$_REQUEST['sort'];
		foreach ($sortlist as $k => $v) {
			PG_setArticlesGroupToTopBySort($k, $v);
		}
		setRaiseMsg('เรียงลำดับข้อมูลใหม่เรียบร้อยแล้ว.' . $sortlist, _TIME_, 0);
		CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listgroup&keysname=" . $keysname);
		exit;
	} elseif ($ac == 'deletearticle') {
		$aIDList = $_POST['aIDList'];
		if (count($aIDList) > 0) {
			foreach ($aIDList as $k => $v) {
				PG_deleteArticlesGroup($v);
				Func_Addlogs("[{$keysname}] Delete ArticleGroup ID {$v} ");
			}
			setRaiseMsg('Delete data is successfully.', _TIME_, 0);
			CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listgroup&keysname=" . $keysname);
			exit;
		}
	}

	$urladd = _admin_buil_link("index.php?module=" . $module . "&mp=" . $mp . "&inc=add&keysname=" . $keysname);
	$listgroup = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=listgroup&keysname=' . $keysname);
	$urladdgroup = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=addgroup&keysname=' . $keysname);
	$listarticle = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&keysname=' . $keysname);

	$aGroupAll = pg_getAllGroup($keysname, $numlist, $page);
	$url = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=listgroup&keysname=' . $keysname . '&numlist=' . $numlist);

	?>

	<style>
		tbody td {
			vertical-align: top !important;
		}
	</style>

	<div id="page-head">
		<div id="page-title">
			<h1 class="page-header text-overflow"><?php echo ($tagname['name']) ? $tagname['name'] : 'Category'; ?></h1>
		</div>
	</div>

	<div id="page-content">

		<form id="form1" name="form1" method="post" action="">
			<input type="hidden" name="ac" value="deletearticle" class="ac" />
			<div class="row">
				<div class="col-xs-12">
					<?php echo displayRaiseMsg(); ?>
					<div class="row">
						<div class="col-md-6 text-left">
							<a href="<?php echo $listarticle; ?>">
								<div id="demo-btn-addrow" class="btn btn-primary"><i class="fa fa-angle-double-left" style="font-size: 12px;"></i> ดู Post ทั้งหมด </div>
							</a>
							<a href="<?php echo $listgroup; ?>">
								<div id="demo-btn-addrow" class="btn btn-info"><i class="fa fa-server" style="font-size: 12px;"></i> จัดการหมวดหมู่ทั้งหมด </div>
							</a>
							<a href="<?php echo $urladdgroup; ?>">
								<div id="demo-btn-addrow" class="btn btn-mint"><i class="fa fa-plus-circle" style="font-size: 12px;"></i> เพิ่มหมวดหมู่ </div>
							</a>
							<?php /* siwakorn ?>
							<a href="<?php echo $urladd; ?>">
								<div id="demo-btn-addrow" class="btn btn-success"><i class="fa fa-plus" style="font-size: 12px;"></i> New Post </div>
							</a>
							<?php */ ?>
						</div>
						<div class="col-md-6 text-right">
							<button id="demo-btn-addrow" class="btn btn-success" onclick="$('.ac').val('sortall');"> <i class="fa fa-sort-numeric-asc" style="font-size: 12px;"></i> เรียงลำดับใหม่ </button>
							<button id="demo-btn-addrow" class="btn btn-danger" onclick="return confirm('Delete this selected ?');"> <i class="fa fa-times-circle" style="font-size: 12px;"></i> ลบรายการที่เลือก </button>
						</div>
					</div>
					<hr class="new-section-xs bord-no">
					<div class="panel">
						<div class="panel-body">

							<div class="table-responsive">
								<style>
									tr.level-0 {
										background-color: #f9f9f9;
										font-weight: bold
									}

									tr.level-1 {
										background-color: #fcfcfc
									}

									tr.level-2 {
										background-color: #fff
									}

									tr[data-hidden="true"] {
										display: none
									}

									.toggle-icon {
										cursor: pointer;
										display: inline-block;
										width: 30px;
										text-align: center;
										font-weight: bold;
										color: #00818E;
										font-size: 26px;
										line-height: 1;
										vertical-align: middle;
									}
								</style>
								<table class="table table-striped table-vcenter">
									<thead>
										<tr>
											<th width="50">ID</th>
											<?php if ($isOpens['isIconGroup']) { ?><th width="70"></th><?php } ?>
											<th>Post Title</th>
											<?php if ($isOpens['isSeoGroupInner'] == true) { ?><th width="150">SEO</th><?php } ?>
											<th width="150">View Post</th>
											<th width="150">Update Date</th>
											<?php if ($isOpens['isOpenGroup']) { ?><th></th><?php } ?>
											<th width="65">Sort</th>
											<th width="65">Status</th>
											<th class="text-center">จัดการ</th>
											<th class="text-center" width="65">ลบ</th>
										</tr>
									</thead>

									<tbody style="font-size: 18px !important;">
										<?php
										$aGroupSorted = sortGroupHierarchy($aGroupAll['data']);
										if ($aGroupAll['num_rows'] > 0) {
											foreach ($aGroupSorted as $k => $v) {
												$urlseo = _admin_buil_link("index.php?module=" . $module . "&mp=" . $mp . "&inc=group_seo&keysname=" . $keysname . "&gid=" . $v['group_id']);
												$urledit = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=editgroup&id=' . $v['group_id'] . '&keysname=' . $keysname);
												$urlviewPost = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&gid=' . $v['group_id'] . '&keysname=' . $keysname);

												if ($v['status'] != 1) {
													$statusTitle = '<i class="fa fa-toggle-on" style="font-size: 20px;color: #8BC34A;"></i>';
													$urlchangestatus = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=listgroup&id=' . $v['group_id'] . '&ch=1&ac=changstatus&keysname=' . $keysname);
												} else {
													$statusTitle = '<i class="fa fa-toggle-off" style="font-size: 20px;"></i>';
													$urlchangestatus = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=listgroup&id=' . $v['group_id'] . '&ch=0&ac=changstatus&keysname=' . $keysname);
												}

												$ic = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1632068b8de%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1632068b8de%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.4296875%22%20y%3D%22104.5%22%3E200x200%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
												$img = ($v['img']) ? URL_UPLOAD . '/' . $v['img'] : $ic;
												$countArticle = pg_countArticlesByGroup($v['group_id'], $keysname);

												$prefix = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $v['level']);
												$arrow  = ($v['level'] > 0) ? '↳ ' : '';

												$hasChildren = false;
												foreach ($aGroupSorted as $check) {
													if ($check['group_parent_id'] == $v['group_id']) {
														$hasChildren = true;
														break;
													}
												}

												if ($v['group_parent_id'] > 0) {
													$pathNames = [];
													$currentParent = $v['group_parent_id'];

													while ($currentParent > 0) {
														$parent = PG_getArticlesGroupByID($currentParent);
														if ($parent['group_parent_id'] == 0) {
															$showName = 'หมวดหลัก';
														} else {
															$showName = $parent['content'][$lang]['group_name'];
														}

														if (!empty($showName)) {
															array_unshift($pathNames, $showName);
														}

														if (!empty($parent['group_parent_id']) && $parent['group_parent_id'] > 0) {
															$currentParent = $parent['group_parent_id'];
														} else {
															break;
														}
													}

													$group_name = '<b>' . implode(' → ', $pathNames) . '</b> → ' . $v['group_name'];
												} else {
													$group_name = 'หมวดหลัก';
												}
												$toggleIcon = $hasChildren ? '<span class="toggle-icon" data-id="' . $v['group_id'] . '">[+]</span>' : '';
												$btnClass = ($countArticle > 0) ? 'btn-info' : 'btn-warning';
												$btnTitle = ($countArticle > 0) ? 'View Post' : 'No Post';
										?>
												<tr
													class="level-<?php echo $v['level']; ?>"
													data-id="<?php echo $v['group_id']; ?>"
													data-parent="<?php echo $v['group_parent_id']; ?>"
													data-level="<?php echo $v['level']; ?>"

													<?php if ($v['group_parent_id'] > 0) echo 'data-hidden="true"'; ?>>
													<td><?php echo $v['group_id']; ?></td>
													<?php if ($isOpens['isIconGroup']): ?>
														<td><img class="img-responsive img-sm" src="<?php echo $img; ?>" alt="thumbs"></td>
													<?php endif; ?>
													<td>
														<?php echo $prefix . $arrow; ?>
														<?php echo $toggleIcon; ?>
														<a style="font-size:20px;" class="btn-link" href="<?php echo $urledit; ?>">
															<?php echo htmlspecialchars($v['group_name']); ?>
														</a>
													</td>
													<?php if ($isOpens['isSeoGroupInner'] == true): ?>
														<td><a class="btn btn-mint" href="<?php echo $urlseo; ?>">SET META SEO</a></td>
													<?php endif; ?>
													<td>
														<a class="btn <?php echo $btnClass; ?>" href="<?php echo $urlviewPost; ?>">
															<?php echo $btnTitle; ?> (<?php echo $countArticle; ?>)
														</a>
													</td>
													<td><span class="text-muted"><?php echo strTimeFormat($v['updatetime'], "d-m-Y"); ?></span></td>
													<?php if ($isOpens['isOpenGroup']): ?>
														<td><?php echo $group_name ?></td>
													<?php endif; ?>
													<td class="text-center"><input type="text" name="sort[<?php echo $v['group_id']; ?>]" class="form-control" value="<?php echo $v['sort']; ?>"></td>
													<td class="text-center"><a href="<?php echo $urlchangestatus; ?>" title="คลิกเพื่อทำการเปลี่ยน status"><?php echo $statusTitle; ?></a></td>
													<td class="text-center"><a href="<?php echo $urledit; ?>" class="btn btn-icon demo-pli-pen-5 icon-lg" title="Edit"></a></td>
													<td class="text-center"><input type="checkbox" name="aIDList[]" value="<?php echo $v['group_id']; ?>"></td>
												</tr>
										<?php
											}
										}
										?>
									</tbody>
								</table>
							</div>

							<div class="row">
								<div class="col-sm-5">
									<div>Showing of <b><?php echo @$aGroupAll['num_rows']; ?></b> entries</div>
								</div>
								<div class="col-sm-7 text-right">
									<?php BuilListPage(@$aGroupAll, @$url, @$page); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>