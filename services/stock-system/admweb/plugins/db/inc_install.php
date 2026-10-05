<?php
$aTable = array();
$sqlArray = array();
$sqlAlter = array();
$aPermissionReal = @$aPermission;
include PATH_ADMIN . '/databases/mysql_default.sql.php';
function __get_DB_Details($tableName)
{
	$sql = "SHOW TABLE STATUS LIKE '" . $tableName . "';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	if ($db->next_record()) {
		return $db->allRows();
	}
	return false;
}

function __get_DB_Columns($tableName)
{
	global $aQData;
	$sql = "SHOW FULL COLUMNS FROM `" . $tableName . "`;";
	$md5 = md5($sql);
	if (isset($aQData[$md5])) {
		return $aQData[$md5];
	}

	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$result = [];
	while ($db->next_record()) {
		$result[$db->f('Field')] = [
			'Field' => $db->f('Field'),
			'Type' => $db->f('Type'),
			'Null' => $db->f('Null'),
			'Key' => $db->f('Key'),
			'Default' => $db->f('Default'),
			'Comment' => $db->f('Comment')
		];
	}

	$aQData[$md5] = $result;
	return $result;
}

foreach ($aConfig['aModuleUse'] as $k => $v) {
	$fmodule = PATH_MODULE . '/' . $v . '/aModuleConfig.php';
	$fcore = PATH_CORE . '/' . $v . '/aModuleConfig.php';
	$fmodule2 = PATH_MODULE . '/' . $v . '/database.php';
	$fcore2 = PATH_CORE . '/' . $v . '/database.php';
	$incDb = is_file($fmodule) ? $fmodule : $fcore;
	$incMysql = is_file($fmodule2) ? $fmodule2 : $fcore2;
	if (is_file($incDb)) {
		include $incDb;
		foreach ($aTablename as $kk => $vv) {
			$aTable[$v][$vv] = $vv;
		}

		foreach ($aPermission as $perKeys => $perVal) {
			if (!in_array($perVal, $aPermissionReal)) {
				$aPermissionReal[] = $perVal;
			}
		}
	}
	if (is_file($incMysql)) {
		include $incMysql;
	}
}

$aData = array();
$db = DB::singleton();
$sql = "SHOW TABLES;";
$db->query($sql, __FUNCTION__);
while ($db->next_record()) {
	$a = array_values($db->allRows());
	$aData[] = trim($a[0]);
}

$aTablename = (count(@$aTablename) > 0) ? $aTablename : false;
$aPermission = $aPermissionReal;
$ac = @$_GET['ac'];
$tb = @$_GET['tb'];
$ires = 0;
if ($aTable != false && $ac != '') {
	if ($ac == 'run') {
		$db = DB::singleton();
		if (is_array($aTable)) {
			foreach ($aTable as $k => $v) {
				foreach ($v as $kk => $vv) {
					if (!in_array($kk, $aData) && isset($sqlArray[$kk]) && @$sqlArray[$kk] != '') {
						$o = $db->query($sqlArray[$kk]);
						if ($o == false) {
							setRaiseMsg('Error TABLE ' . $kk, $ires++, 1);
						}
					}

					if (isset($sqlAlter[$kk]) && is_array($sqlAlter[$kk]) && count($sqlAlter[$kk]) > 0) {
						$aCol = __get_DB_Columns($kk);
						foreach ($sqlAlter[$kk] as $col => $sqlq) {
							if (!isset($aCol[$col])) {
								$o = $db->query($sqlq, __FUNCTION__);
								if ($o == false) {
									setRaiseMsg('Error ' .$sqlq, $ires++, 1);
								} else {
									setRaiseMsg('SET ALTER TABLE ' . $kk, $ires++, 0);
								}
							} 
						}
					}
				}
			}
			setRaiseMsg('Setup module is successfully.', _TIME_, 0);
		}
		CustomRedirectToUrl("index.php?module=" . $module . "&mp=db");
		exit;
	} elseif ($ac == 'drop' && $tb != '') {
		$db = DB::singleton();
		$sql = "DROP TABLE " . $tb . ";";
		$db->query($sql, __FUNCTION__);
		setRaiseMsg('DROP ' . $tb . ' TABLE module is successfully.', _TIME_, 0);
		CustomRedirectToUrl("index.php?module=" . $module . "&mp=db");
		exit;
	} elseif ($ac == 'optimize' && $tb != '') {
		$db = DB::singleton();
		$sql = "OPTIMIZE TABLE " . $tb . "; ";
		$db->query($sql, __FUNCTION__);
		setRaiseMsg('OPTIMIZE ' . $tb . ' TABLE module is successfully.', _TIME_, 0);
		CustomRedirectToUrl("index.php?module=" . $module . "&mp=db");
		exit;
	} elseif ($ac == 'repair' && $tb != '') {
		$db = DB::singleton();
		$sql = " REPAIR TABLE `{$tb}` ";
		$db->query($sql, __FUNCTION__);
		setRaiseMsg('REPAIR ' . $tb . ' TABLE module is successfully.', _TIME_, 0);
		CustomRedirectToUrl("index.php?module=" . $module . "&mp=db");
		exit;
	}
}

/*
$sql = "CHECK TABLE `banner` ;";
$db->query($sql, __FUNCTION__);
while ($db->next_record()) {
	$a[] = $db->allRows();
}
pre($a);
*/

function checkSetupDatabase($aDBAllTable = array(), $aDBForModule = array())
{
	if (count($aDBForModule) > 0 && is_array($aDBForModule)) {
		foreach ($aDBForModule as $v) {
			if (!in_array($v, $aDBAllTable)) {
				return false;
			}
		}
		return true;
	}
	return false;
}
?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">INSTALL DB</h1>
	</div>
</div>
<div id="page-content">
	<div class="row">
		<?php if (!checkSetupDatabase($aData, $aTable) && count($aTable) > 0 && is_array($aTable)) { ?>
			<div class="col-sm-12 toolbar-left">
				<a href="<?php echo _admin_buil_link("index.php?module=" . $module . "&mp=db&ac=run"); ?>"><button id="demo-btn-addrow" class="btn btn-danger">Re Install Database</button></a>
			</div>
		<?php } ?>
		<div class="col-xs-12">
			<div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">Database Management</h3>
				</div>
				<div class="panel-body">
					<?php displayRaiseMsg(); ?>
					<div>Details are below.
						Website archiver review status
						That the system has been installed or not
						Advantages for those who migrate to another place or
						Delete the database tables to install the system again.
						But if this section is reinstalled it means
						Your original information has disappeared from the system.</div>

					<?php if (count($aTable) > 0 && is_array($aTable)) { ?>
						<table class="table table-hover table-vcenter">
							<thead>
								<tr>
									<th class="min-width"></th>
									<th>Table Name</th>
									<th>Modules</th>
									<th class="text-center">Record</th>
									<th class="text-right">Size</th>
									<th></th>
									<th class="text-center">Status</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								<?php
								if (count($aTable) > 0 && is_array($aTable)) {
									foreach ($aTable as $km => $vm) {
								?>
										<?php
										foreach ($vm as $k => $v) {
											if (in_array($v, $aData)) {
												$dbdetail = __get_DB_Details($v);
												$total_size = $dbdetail['Data_length'] + $dbdetail['Index_length'];
												$Rows = $dbdetail['Rows'];
												$Collation = $dbdetail['Collation'];
											} else {
												$total_size = 0;
												$Rows = 0;
												$Collation = 'none';
											}
										?>
											<tr>
												<td class="text-center"><i class="demo-pli-data-settings icon-2x"></i></td>
												<td>
													<span class="text-main text-semibold"><?php echo $v; ?></span>
													<!-- <br><small class="text-muted">Last 7 days : 4,234k</small> -->
												</td>
												<td><?php echo $km; ?></td>
												<td class="text-center"><?php echo $Rows; ?></td>
												<td class="text-right"><?php echo round($total_size / 1024 / 1024, 2) . ' MB'; ?></td>
												<td class="text-center"><?php echo $Collation; ?></td>
												<?php if (in_array($v, $aData)) { ?>
													<td class="text-center"><span class="text-success text-semibold">Installed</span></td>
												<?php } else { ?>
													<td class="text-center"><span class="text-danger text-semibold">Not installed</span></td>
												<?php } ?>
												<td>
													<?php if (in_array($v, $aData)) { ?>
														<a href="<?php echo _admin_buil_link("index.php?module=" . $module . "&mp=db&tb=" . $v . "&ac=optimize"); ?>"><button id="demo-btn-addrow" class="btn btn-info">Optimize</button></a>
														<a href="<?php echo _admin_buil_link("index.php?module=" . $module . "&mp=db&tb=" . $v . "&ac=repair"); ?>"><button id="demo-btn-addrow" class="btn btn-mint">Repair</button></a>
														<a href="<?php echo _admin_buil_link("index.php?module=" . $module . "&mp=db&tb=" . $v . "&ac=drop"); ?>" onclick="return confirm('อันตรายข้อมูลจะถูกลบทิ้ง / Delete !.');"><button id="demo-btn-addrow" class="btn btn-danger">Drop</button></a>
													<?php } ?>
												</td>
											</tr>
								<?php }
									}
								} ?>
							</tbody>
						</table>
					<?php } ?>
				</div>
			</div>
		</div>

		<?php if (isset($aPermission) && count($aPermission) > 0) { ?>
			<div class="col-xs-12">
				<div class="panel">
					<div class="panel-heading">
						<h3 class="panel-title">Uploads Permission</h3>
					</div>
					<div class="panel-body">
						<table class="table table-hover table-vcenter">
							<thead>
								<tr>
									<th class="min-width"></th>
									<th>Table Name</th>
									<?php if (IS_SHOW_UPLOAD_SIZE == true) { ?>
										<th class="text-center">Folder Size</th>
									<?php } ?>
									<th class="text-right">Status</th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ($aPermission as $k => $v) {
									if (@is_writable($v)) {
										$htmlcode = '<span class="text-success text-semibold">Allow</span>';
									} else {
										@mkdir($v, 0777);
										$htmlcode = '<span class="text-danger text-semibold">Close</span>';
									}
								?>
									<tr>
										<td class="text-center"><i class="demo-pli-folder icon-2x"></i></td>
										<td>
											<span class="text-main text-semibold"><?php echo $v; ?></span>
										</td>
										<?php if (IS_SHOW_UPLOAD_SIZE == true) { ?>
											<td class="text-center"><?php echo displaySize(disk_used_space2($v)); ?></td>
										<?php } ?>
										<td class="text-right"><?php echo $htmlcode; ?></td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		<?php } ?>

		<?php if (isset($aFunctionReq) && count($aFunctionReq) > 0) { ?>
			<div class="col-xs-12">
				<div class="panel">
					<div class="panel-heading">
						<h3 class="panel-title">Function Permission</h3>
					</div>
					<div class="panel-body">
						<table class="table table-hover table-vcenter">
							<thead>
								<tr>
									<th class="min-width"></th>
									<th>Table Name</th>
									<th class="text-right">Status</th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ($aFunctionReq as $k => $v) {
									if (@function_exists($v)) {
										$htmlcode = '<span class="text-success text-semibold">Open</span>';
									} else {
										$htmlcode = '<span class="text-danger text-semibold">Close</span>';
									}
								?>
									<tr>
										<td class="text-center">
											<i class="demo-psi-coding icon-2x"></i>
										</td>
										<td>
											<span class="text-main text-semibold"><?php echo $v; ?></span>
										</td>
										<td class="text-right"><?php echo $htmlcode; ?></td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		<?php } ?>
	</div>
</div>