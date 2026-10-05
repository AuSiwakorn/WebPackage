<?php
$page 	= REQ_get('page', 'get', 'int', 1);
$ac 		= REQ_get('ac', 'request', 'str');
$id 		= REQ_get('id', 'get', 'int', 0);
$keysname 	= REQ_get('keysname', 'request', 'str', '');
$numlist = REQ_get('numlist', 'get', 'int', '20');
$time = _TIME_;

$isOpens['isIconType'] = true;

///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array($ac, array('changstatus', 'settotop', 'deletearticle', 'sortall', 'deletetype')) && $isSecurMode == 1) {
	setRaiseMsg('Secur Mode.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listtype&keysname=" . $keysname);
	exit;
}
///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////

if ($ac == 'deletetype') {
	$aIDList = $_POST['aIDList'];
	if (count($aIDList) > 0) {
		foreach ($aIDList as $k => $v) {
			PG_deleteType($v);
			Func_Addlogs("[{$keysname}] Delete ArticleType ID {$v} ");
		}

		setRaiseMsg('Delete data is successfully.', _TIME_, 0);
		CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listtype&keysname=" . $keysname);
		exit;
	}
} elseif ($ac == 'sortall') {
	$aSort = $_POST['sort'];
	if (count($aSort) > 0 && is_array($aSort)) {
		foreach ($aSort as $k => $v) {
			PG_updateTypeKeysSort($k, $v);
		}
	}
	setRaiseMsg('Sort data is successfully.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listtype&keysname=" . $keysname);
	exit;
}


$aData = pg_getAllType($keysname, $numlist, $page);
$urladdtype = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=addtype&keysname=' . $keysname);
$url = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=listtype&keysname=' . $keysname . '&numlist=' . $numlist);
?>
<form id="form1" name="form1" method="post" action="">
	<input type="hidden" name="ac" value="deletetype" class="ac" />

	<div id="page-head">
		<div id="page-title">
			<div class="row">
				<div class="col-md-6">
					<h1 class="page-header text-overflow"><?php echo $tagname['typetitle']; ?></h1>
				</div>
				<div class="col-md-6 text-right">
					<a href="<?php echo $urladdtype; ?>">
						<div id="demo-btn-addrow" class="btn btn-mint"><i class="fa fa-plus-circle" style="font-size: 12px;"></i> เพิ่มข้อมูล </div>
					</a>
					<button id="demo-btn-addrow" class="btn btn-success" onclick="$('.ac').val('sortall');"> <i class="fa fa-sort-numeric-asc" style="font-size: 12px;"></i> เรียงลำดับใหม่ </button>
					<button id="demo-btn-addrow" class="btn btn-danger" onclick="return confirm('Delete this selected ?');"> <i class="fa fa-times-circle" style="font-size: 12px;"></i> ลบรายการที่เลือก </button>
				</div>
			</div>
		</div>
	</div>

	<div id="page-content">
		<div class="row">
			<div class="col-xs-12">
				<?php echo displayRaiseMsg(); ?>
				<div class="panel">
					<div class="panel-body">
						<div class="table-responsive">
							<table class="table table-striped table-vcenter">
								<thead>
									<tr>
										<th width="70">ID</th>
										<?php if ($isOpens['isIconType']) { ?><th width="80">ICON</th><?php } ?>
										<th>Name</th>
										<th width="65">ลำดับ</th>
										<th class="text-center">จัดการ</th>
										<th class="text-center" width="65">ลบ</th>
									</tr>
								</thead>
								<tbody style="font-size: 18px !important;">
									<?php
									if ($aData['num_rows'] > 0) {
										foreach ($aData['data'] as $k => $v) {
											$ic = 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22200%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20200%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1632068b8de%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.75)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A10pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1632068b8de%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23777%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%2274.4296875%22%20y%3D%22104.5%22%3E200x200%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E';
											$urledit = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=edittype&id=' . $v['kid'] . '&keysname=' . $keysname);
									?>
											<tr>
												<td><?php echo $v['kid']; ?></td>
												<?php if ($isOpens['isIconType']) { ?>
													<td><?php echo ($v['kicon'] != '') ? '<img src="' . URL_UPLOAD . '/' . $v['kicon'] . '" border="0" width="50" height="45" />' : '<img src="' . $ic . '" border="0" width="50" height="45" />'; ?></td>
												<?php } ?>
												<td><a style="font-size: 20px;" class="btn-link" href="<?php echo $urledit; ?>"><?php echo mb_substr(($v['kname']) ? $v['kname'] : '---', 0, 200, "utf-8"); ?></a></td>
												<td class="text-center"><input type="number" name="sort[<?php echo $v['kid']; ?>]" class="form-control" value="<?php echo $v['ksort']; ?>"></td>
												<td class="min-width">
													<div class="btn-groups">
														<a href="<?php echo $urledit; ?>" class="btn btn-icon demo-pli-pen-5 icon-lg add-tooltip" data-original-title="Edit Post" data-container="body" style="font-size: 18px;line-height: 0;"></a>
													</div>
												</td>
												<td class="text-center"><input type="checkbox" name="aIDList[]" value="<?php echo $v['kid']; ?>" /></td>
											</tr>
									<?php }
									} ?>
								</tbody>
							</table>
						</div>

						<div class="row">
							<div class="col-sm-5">
								<div>Showing of <span class="label label-info" style="font-size: 18px;padding: 2px 8px;"><?php echo @$aData['num_rows']; ?></span> entries</div>
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