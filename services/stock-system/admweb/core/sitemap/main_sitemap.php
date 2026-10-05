<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด Sitemap ได้', 'redirect', 'SET');
$page		= REQ_get('page', 'request', 'int', 1);
$tab		= REQ_get('tab', 'request', 'str', 'generate');
$aSitemap	= DB_LIST('sitemap', [], 50, $page, "ORDER BY priority DESC, lastmod DESC");
$aCustom	= DB_LIST('sitemap_custom', [], 50, $page, "ORDER BY priority DESC, lastmod DESC");

if (_AC_ == 'add') {
	$d = [
		'loc'			=> REQ_get('loc', 'post', 'str', ''),
		'lastmod'		=> REQ_get('lastmod', 'post', 'str', ''),
		'changefreq'	=> REQ_get('changefreq', 'post', 'str', ''),
		'priority'		=> REQ_get('priority', 'post', 'str', '')
	];
	$aData = DB_GET('sitemap', ['loc' => $d['loc']]);
	$aDataCustom = DB_GET('sitemap_custom', ['loc' => $d['loc']]);
	if (!empty($aData) || !empty($aDataCustom)) {
		setRaiseMsg('เพิ่มข้อมูลผิดพลาด: URL นี้มีอยู่แล้ว. [URL : ' . $d['loc'] . ']', _TIME_, 1);
		CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&tab=custom");
		exit;
	}
	$id = DB_ADD('sitemap_custom', $d);
	setRaiseMsg('เพิ่มข้อมูลเรียบร้อยแล้ว. [ID : ' . $id . ']', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&tab=custom");
	exit;
}

if (_AC_ == 'update') {
	$id = REQ_get('id', 'post', 'int', 0);
	$loc = REQ_get('loc', 'post', 'str', '');
	$type = REQ_get('type', 'post', 'str', '');
	$aData = DB_GET('sitemap', ['loc' => $d['loc']]);
	$aDataCustom = DB_GET('sitemap_custom', ['loc' => $d['loc']]);
	if (!empty($aData) || !empty($aDataCustom)) {
		setRaiseMsg('แก้ไขข้อมูลผิดพลาด: URL นี้มีอยู่แล้ว. [URL : ' . $loc . ']', _TIME_, 1);
		CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
		exit;
	}
	$table = $type === 'generate' ? 'sitemap' : 'sitemap_custom';
	DB_UP($table, [
		'loc' => $loc,
		'lastmod' => REQ_get('lastmod', 'post', 'str', ''),
		'changefreq' => REQ_get('changefreq', 'post', 'str', ''),
		'priority' => REQ_get('priority', 'post', 'str', '')
	], ['id' => $id]);
	setRaiseMsg('แก้ไขข้อมูลเรียบร้อยแล้ว. [ID : ' . $id . ']', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
	exit;
}

if (_AC_ == 'del') {
	$id	= REQ_get('id', 'request', 'int', 0);
	$type = REQ_get('type', 'request', 'str', '');
	$table = $type === 'generate' ? 'sitemap' : 'sitemap_custom';
	DB_DEL($table, ['id' => $id]);
	setRaiseMsg('ลบข้อมูลเรียบร้อยแล้ว. [ID : ' . $id . ']', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
	exit;
}

if (_AC_ == 'scan') {
	$result = GenerateSitemap();
	setRaiseMsg("Scan Sitemap เรียบร้อยแล้ว. {$result}", _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
	exit;
}
?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">Sitemap</h1>
	</div>
</div>
<div id="page-content">
	<div><?php displayRaiseMsg(); ?></div>
	<?php
	if (!empty($aSitemap['data']) || !empty($aCustom['data'])) {
		pre(URL_WEB_ROOT . '/sitemap.xml');
	}
	?>
	<div class="row">
		<div class="col-md-12">
			<div class="tab-base">
				<ul class="nav nav-tabs">
					<li class="<?php echo $tab === 'generate' ? 'active' : '' ?>"><a data-toggle="tab" href="#demo-lft-tab-1">Generate</a></li>
					<li class="<?php echo $tab === 'custom' ? 'active' : '' ?>"><a data-toggle="tab" href="#demo-lft-tab-2">Custom</a></li>
				</ul>
				<div class="tab-content" style="padding: 0px">
					<div id="demo-lft-tab-1" class="tab-pane fade <?php echo $tab === 'generate' ? 'active in' : '' ?>">
						<div class="panel">
							<div class="panel-body">
								<div class="pad-btm">
									<div class="row">
										<div class="col-md-6" style="display: flex; align-items: center; justify-content: start;">
											<form method="post">
												<input type="hidden" name="ac" value="scan">
												<button type="submit" class="btn btn-mint" style="display: flex; align-items: center; justify-content: center;"><i class="demo-pli-map"></i>&nbsp;&nbsp;Scan Sitemap</button>
											</form>
										</div>
									</div>
								</div>
								<div class="table-responsive">
									<table class="table table-striped table-vcenter">
										<thead>
											<tr>
												<th width="100" class="text-center">NO.</th>
												<th class="text-left">URL</th>
												<th width="150" class="text-center">Lastmod</th>
												<th width="150" class="text-center">Changefreq</th>
												<th width="150" class="text-center">Priority</th>
												<th width="150" class="text-center">Created at</th>
												<th width="100" class="text-right"></th>
											</tr>
										</thead>
										<tbody>
											<?php
											if (!empty($aSitemap['data'])) {
												$i = 0;
												foreach ($aSitemap['data'] as $v) { ?>
													<tr>
														<td class="text-center"><?php echo ++$i; ?></td>
														<td class="text-left"><?php echo $v['loc']; ?></td>
														<td class="text-center"><?php echo date('d/m/Y', strtotime($v['lastmod'])); ?></td>
														<td class="text-center"><?php echo $v['changefreq']; ?></td>
														<td class="text-center"><?php echo $v['priority']; ?></td>
														<td class="text-center"><?php echo date('d/m/Y', strtotime($v['created_at'])); ?></td>
														<td class="text-right">
															<a href="#" data-target="#demo-default-modal-generate-<?php echo $v['id']; ?>" data-toggle="modal" class="btn btn-icon demo-pli-pen-5 icon-lg add-tooltip" data-original-title="Edit" data-container="body"></a>
															<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&type=generate&ac=del&id=" . $v['id']); ?>" onclick="return confirm('Delete ?'); " class="btn btn-icon demo-pli-trash icon-lg add-tooltip" data-original-title="Delete" data-container="body"></a>
														</td>
													</tr>
											<?php }
											} else {
												echo '<tr><td colspan="7" class="text-center text-main"><strong>ยังไม่มีข้อมูล</strong></td></tr>';
											} ?>
										</tbody>
									</table>
								</div>
								<div class="row">
									<div class="col-sm-5">
										<div>Find all <?php echo $aSitemap['num_rows']; ?> sitemap / display 50 sitemap per page</div>
									</div>
									<div class="col-sm-7 text-right">
										<?php BuilListPage($aSitemap, "index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&tab=generate", $page); ?>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div id="demo-lft-tab-2" class="tab-pane fade <?php echo $tab === 'custom' ? 'active in' : '' ?>">
						<div class="panel">
							<div class="panel-body">
								<div class="pad-btm">
									<div class="row">
										<div class="col-md-6" style="display: flex; align-items: center; justify-content: start;">
											<button class="btn btn-info" data-target="#demo-default-modal" data-toggle="modal" style="display: flex; align-items: center; justify-content: center;"><i class="demo-pli-add"></i>&nbsp;&nbsp;Add Sitemap</button>
										</div>
									</div>
								</div>
								<div class="table-responsive">
									<table class="table table-striped table-vcenter">
										<thead>
											<tr>
												<th width="100" class="text-center">NO.</th>
												<th class="text-left">URL</th>
												<th width="150" class="text-center">Lastmod</th>
												<th width="150" class="text-center">Changefreq</th>
												<th width="150" class="text-center">Priority</th>
												<th width="150" class="text-center">Created at</th>
												<th width="100" class="text-right"></th>
											</tr>
										</thead>
										<tbody>
											<?php
											if (!empty($aCustom['data'])) {
												$i = 0;
												foreach ($aCustom['data'] as $v) { ?>
													<tr>
														<td class="text-center"><?php echo ++$i; ?></td>
														<td class="text-left"><?php echo $v['loc']; ?></td>
														<td class="text-center"><?php echo date('d/m/Y', strtotime($v['lastmod'])); ?></td>
														<td class="text-center"><?php echo $v['changefreq']; ?></td>
														<td class="text-center"><?php echo $v['priority']; ?></td>
														<td class="text-center"><?php echo date('d/m/Y', strtotime($v['created_at'])); ?></td>
														<td class="text-right">
															<a href="#" data-target="#demo-default-modal-custom-<?php echo $v['id']; ?>" data-toggle="modal" class="btn btn-icon demo-pli-pen-5 icon-lg add-tooltip" data-original-title="Edit" data-container="body"></a>
															<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&type=custom&ac=del&id=" . $v['id']); ?>" onclick="return confirm('Delete ?'); " class="btn btn-icon demo-pli-trash icon-lg add-tooltip" data-original-title="Delete" data-container="body"></a>
														</td>
													</tr>
											<?php }
											} else {
												echo '<tr><td colspan="7" class="text-center text-main"><strong>ยังไม่มีข้อมูล</strong></td></tr>';
											} ?>
										</tbody>
									</table>
								</div>
								<div class="row">
									<div class="col-sm-5">
										<div>Find all <?php echo $aCustom['num_rows']; ?> sitemap custom / display 50 sitemap custom per page</div>
									</div>
									<div class="col-sm-7 text-right">
										<?php BuilListPage($aCustom, "index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&tab=custom", $page); ?>
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

<div class="modal fade" id="demo-default-modal" role="dialog" tabindex="-1" aria-labelledby="demo-default-modal" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<form method="post" class="form-horizontal">
				<input type="hidden" name="ac" id="ac" value="add">
				<div class="modal-header bord-btm">
					<button type="button" class="close" data-dismiss="modal"><i class="pci-cross pci-circle"></i></button>
					<h4 class="modal-title">New Sitemap</h4>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<div class="col-md-12">
							<label for="loc">URL <span class="text-danger">*</span></label>
							<input id="loc" name="loc" type="text" placeholder="URL" class="form-control" required>
						</div>
					</div>
					<div class="form-group">
						<div class="col-md-12">
							<label for="lastmod">วันที่แก้ไขล่าสุด <span class="text-danger">*</span></label>
							<input id="lastmod" name="lastmod" type="date" placeholder="วันที่แก้ไขล่าสุด" class="form-control" required>
						</div>
					</div>
					<div class="form-group">
						<div class="col-md-12">
							<label for="changefreq">ความถี่ในการเปลี่ยนแปลง <span class="text-danger">*</span></label>
							<select class="selectpicker form-control" id="changefreq" name="changefreq" data-live-search="true" data-width="100%" required>
								<option selected disabled value="">โปรดเลือกความถี่</option>
								<?php $changefreq = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
								foreach ($changefreq as $v) { ?>
									<option value="<?php echo $v ?>"><?php echo $v ?></option>
								<?php } ?>
							</select>
						</div>
					</div>
					<div class="form-group">
						<div class="col-md-12">
							<label for="priority">ลำดับความสำคัญ <span class="text-danger">*</span></label>
							<select class="selectpicker form-control" id="priority" name="priority" data-live-search="true" data-width="100%" required>
								<option selected disabled value="">โปรดเลือกลำดับความสำคัญ</option>
								<?php $priority = ['1.0', '0.9', '0.8', '0.7', '0.6', '0.5', '0.4', '0.3', '0.2', '0.1'];
								foreach ($priority as $v) { ?>
									<option value="<?php echo $v ?>"><?php echo $v ?></option>
								<?php } ?>
							</select>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button data-dismiss="modal" class="btn btn-danger" type="button">Close</button>
					<button class="btn btn-mint" type="submit">Save</button>
				</div>
			</form>
		</div>
	</div>
</div>

<?php if (!empty($aSitemap['data'])) {
	foreach ($aSitemap['data'] as $value) { ?>
		<div class="modal fade" id="demo-default-modal-generate-<?php echo $value['id']; ?>" role="dialog" tabindex="-1" aria-labelledby="demo-default-modal-generate-<?php echo $value['id']; ?>" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<form method="post" class="form-horizontal">
						<input type="hidden" name="ac" value="update">
						<input type="hidden" name="type" value="generate">
						<input type="hidden" name="id" value="<?php echo $value['id']; ?>">
						<div class="modal-header bord-btm">
							<button type="button" class="close" data-dismiss="modal"><i class="pci-cross pci-circle"></i></button>
							<h4 class="modal-title">Edit Sitemap</h4>
						</div>
						<div class="modal-body">
							<div class="form-group">
								<div class="col-md-12">
									<label for="loc">URL <span class="text-danger">*</span></label>
									<input id="loc" name="loc" type="text" placeholder="URL" class="form-control" value="<?php echo $value['loc']; ?>" required>
								</div>
							</div>
							<div class="form-group">
								<div class="col-md-12">
									<label for="lastmod">วันที่แก้ไขล่าสุด <span class="text-danger">*</span></label>
									<input id="lastmod" name="lastmod" type="date" placeholder="วันที่แก้ไขล่าสุด" class="form-control" value="<?php echo $value['lastmod']; ?>" required>
								</div>
							</div>
							<div class="form-group">
								<div class="col-md-12">
									<label for="changefreq">ความถี่ในการเปลี่ยนแปลง <span class="text-danger">*</span></label>
									<select class="selectpicker form-control" id="changefreq" name="changefreq" data-live-search="true" data-width="100%" required>
										<option disabled value="">โปรดเลือกความถี่</option>
										<?php $changefreq = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
										foreach ($changefreq as $v) { ?>
											<option <?php echo $value['changefreq'] == $v ? 'selected' : ''; ?> value="<?php echo $v ?>"><?php echo $v ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<div class="col-md-12">
									<label for="priority">ลำดับความสำคัญ <span class="text-danger">*</span></label>
									<select class="selectpicker form-control" id="priority" name="priority" data-live-search="true" data-width="100%" required>
										<option disabled value="">โปรดเลือกลำดับความสำคัญ</option>
										<?php $priority = ['1.0', '0.9', '0.8', '0.7', '0.6', '0.5', '0.4', '0.3', '0.2', '0.1'];
										foreach ($priority as $v) { ?>
											<option <?php echo $value['priority'] == $v ? 'selected' : ''; ?> value="<?php echo $v ?>"><?php echo $v ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button data-dismiss="modal" class="btn btn-danger" type="button">Close</button>
							<button class="btn btn-mint" type="submit">Update</button>
						</div>
					</form>
				</div>
			</div>
		</div>
<?php }
} ?>

<?php if (!empty($aCustom['data'])) {
	foreach ($aCustom['data'] as $value) { ?>
		<div class="modal fade" id="demo-default-modal-custom-<?php echo $value['id']; ?>" role="dialog" tabindex="-1" aria-labelledby="demo-default-modal-custom-<?php echo $value['id']; ?>" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<form method="post" class="form-horizontal">
						<input type="hidden" name="ac" value="update">
						<input type="hidden" name="type" value="custom">
						<input type="hidden" name="id" value="<?php echo $value['id']; ?>">
						<div class="modal-header bord-btm">
							<button type="button" class="close" data-dismiss="modal"><i class="pci-cross pci-circle"></i></button>
							<h4 class="modal-title">Edit Sitemap Custom</h4>
						</div>
						<div class="modal-body">
							<div class="form-group">
								<div class="col-md-12">
									<label for="loc">URL <span class="text-danger">*</span></label>
									<input id="loc" name="loc" type="text" placeholder="URL" class="form-control" value="<?php echo $value['loc']; ?>" required>
								</div>
							</div>
							<div class="form-group">
								<div class="col-md-12">
									<label for="lastmod">วันที่แก้ไขล่าสุด <span class="text-danger">*</span></label>
									<input id="lastmod" name="lastmod" type="date" placeholder="วันที่แก้ไขล่าสุด" class="form-control" value="<?php echo $value['lastmod']; ?>" required>
								</div>
							</div>
							<div class="form-group">
								<div class="col-md-12">
									<label for="changefreq">ความถี่ในการเปลี่ยนแปลง <span class="text-danger">*</span></label>
									<select class="selectpicker form-control" id="changefreq" name="changefreq" data-live-search="true" data-width="100%" required>
										<option disabled value="">โปรดเลือกความถี่</option>
										<?php $changefreq = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
										foreach ($changefreq as $v) { ?>
											<option <?php echo $value['changefreq'] == $v ? 'selected' : ''; ?> value="<?php echo $v ?>"><?php echo $v ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
							<div class="form-group">
								<div class="col-md-12">
									<label for="priority">ลำดับความสำคัญ <span class="text-danger">*</span></label>
									<select class="selectpicker form-control" id="priority" name="priority" data-live-search="true" data-width="100%" required>
										<option disabled value="">โปรดเลือกลำดับความสำคัญ</option>
										<?php $priority = ['1.0', '0.9', '0.8', '0.7', '0.6', '0.5', '0.4', '0.3', '0.2', '0.1'];
										foreach ($priority as $v) { ?>
											<option <?php echo $value['priority'] == $v ? 'selected' : ''; ?> value="<?php echo $v ?>"><?php echo $v ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button data-dismiss="modal" class="btn btn-danger" type="button">Close</button>
							<button class="btn btn-mint" type="submit">Update</button>
						</div>
					</form>
				</div>
			</div>
		</div>
<?php }
} ?>