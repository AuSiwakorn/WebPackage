<?php
$aTable = array();
$sqlArray = array();
$aPermissionReal = @$aPermission;
include PATH_ADMIN . '/databases/mysql_default.sql.php';
pre($aConfig['aModuleUse']);
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
if ($aTable != false && $ac != '') {
	if ($ac == 'run') {
		$db = DB::singleton();
		if (is_array($aTable)) {
			foreach ($aTable as $k => $v) {
				foreach ($v as $kk => $vv) {
					echo $kk;
					if (!in_array($kk, $aData) && isset($sqlArray[$kk]) && @$sqlArray[$kk] != '') {
						$o = $db->query($sqlArray[$kk]);
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
<style type="text/css">
	<!--
	.g {
		color: #003300;
		font-weight: bold;
	}

	.r {
		color: #FF0000;
		font-weight: bold;
	}

	.style3 {
		color: #006600;
		font-weight: bold;
	}

	.validate_functionexisis {
		float: left;
		display: block;
		padding: 15px;
		margin: 5px;
		border: 1px #999 double;
		width: 180px;
		text-align: left;
		background-color: #EEE;
	}
	-->
</style>

<h2 align="center"><?php ___lang('Database installation system'); ?></h2>
<div align="center" style="padding: 10px;">
	<?php ___lang('Details are below.
Website archiver review status
That the system has been installed or not
Advantages for those who migrate to another place or
Delete the database tables to install the system again.
But if this section is reinstalled it means
Your original information has disappeared from the system.'); ?></div>
<?php if (count($aTable) > 0 && is_array($aTable)) { ?>
	<div style="height: 5px;"></div>
	<div align="center">
		<?php
		if (!checkSetupDatabase($aData, $aTable) && count($aTable) > 0 && is_array($aTable)) {
		?>
			<a
				href="<?php echo _admin_buil_link("index.php?module=" . $module . "&mp=db&ac=run"); ?>"><img
					src="template/<?php echo TEMPLATE_NAME; ?>/img/install.png" border="0"
					alt="Install system" /></a>
		<?php
		}
		?>
	</div>
	<div style="height: 5px;"></div>
	<div class="tableclass">
		<table border="0" align="center" cellpadding="8" cellspacing="0">
			<tr class="ttop">
				<td colspan="4"><strong>Database Table List</strong></td>
			</tr>
			<?php
			if (count($aTable) > 0 && is_array($aTable)) {
				foreach ($aTable as $km => $vm) {
			?>
					<tr>
						<td colspan="5" align="left" style="text-transform: uppercase;background-color: #DDD;font-weight: bold;"><?php echo $km; ?></td>
					</tr>
					<?php foreach ($vm as $k => $v) { ?>
						<tr>
							<td align="center" bgcolor="#eeeeee" width="50"><?php if (in_array($v, $aData)) { ?> <img
										src="template/<?php echo TEMPLATE_NAME; ?>/img/icon-installed.png"
										border="0" alt="This table is installed." />
								<?php } else { ?> <span class="r"><img
											src="template/<?php echo TEMPLATE_NAME; ?>/img/icon-noneinstall.png"
											border="0" alt="Not installed" /></span> <?PHP } ?></td>
							<td align="left" bgcolor="#eeeeee"><?php echo $v; ?></td>
							<td align="center" bgcolor="#eeeeee" width="90">
								<?php if (in_array($v, $aData)) { ?>
									<a
										href="<?php echo _admin_buil_link("index.php?module=" . $module . "&mp=db&tb=" . $v . "&ac=optimize"); ?>"
										class="notload"><img
											src="template/<?php echo TEMPLATE_NAME; ?>/img/optimize.png"
											border="0" alt="Repair" height="25" /></a>
								<?php } else { ?>
									-
								<?PHP } ?>
							</td>
							<td align="center" bgcolor="#eeeeee" width="90">
								<?php if (in_array($v, $aData)) { ?>
									<a
										href="<?php echo _admin_buil_link("index.php?module=" . $module . "&mp=db&tb=" . $v . "&ac=repair"); ?>"
										class="notload"><img
											src="template/<?php echo TEMPLATE_NAME; ?>/img/repair.png"
											border="0" alt="Repair" height="25" /></a>
								<?php } else { ?>
									-
								<?PHP } ?>
							</td>
							<td align="center" bgcolor="#eeeeee" width="50">
								<?php if (in_array($v, $aData)) { ?>
									<a
										href="<?php echo _admin_buil_link("index.php?module=" . $module . "&mp=db&tb=" . $v . "&ac=drop"); ?>"
										onclick="return confirm('Dangerous data will be deleted. / Delete !.');"
										class="notload"><img
											src="template/<?php echo TEMPLATE_NAME; ?>/img/icon-uninstall.png"
											border="0" alt="Delete" /></a>
								<?php } else { ?>
									-
								<?PHP } ?>
							</td>
						</tr>
			<?php
					}
				}
			} ?>
		</table>
	</div>
<?php } ?>
<?php if (isset($aPermission) && count($aPermission) > 0) { ?>
	<br />
	<div class="tableclass">
		<table border="0" align="center" cellpadding="8" cellspacing="0">
			<tr class="ttop">
				<td colspan="3"><strong>UPLOADS PERMISSION</strong></td>
			</tr>
			<?php
			foreach ($aPermission as $k => $v) {
				if (@is_writable($v)) {
					$status = '<img src="template/' . TEMPLATE_NAME . '/img/ok.gif" alt="ok"/>';
				} else {
					@mkdir($v, 0777);
					$status = '<img src="template/' . TEMPLATE_NAME . '/img/no.gif" alt="no"/>';
				}
			?>
				<tr>
					<td align="left" bgcolor="#eeeeee"><span
							style="font-size: 11px; color: #666666;"><?php echo $v; ?></span></td>
					<?php if (IS_SHOW_UPLOAD_SIZE == true) { ?>
						<td align="center" width="70"><?php echo displaySize(disk_used_space2($v)); ?></td>
					<?php } ?>
					<td align="center" bgcolor="#eeeeee" width="50"><?php echo $status; ?></td>
				</tr>
			<?php
			}
			?>
		</table>
	</div>
<?php } ?>




<?php if (isset($aFunctionReq) && count($aFunctionReq) > 0) { ?>
	<br />
	<div class="tableclass">
		<table border="0" align="center" cellpadding="8" cellspacing="0">
			<tr class="ttop">
				<td><strong>FUNCTION PERMISSION</strong></td>
			</tr>
			<tr>
				<td align="center" width="70">
					<?php
					foreach ($aFunctionReq as $k => $v) {
						if (@function_exists($v)) {
							$status = '<img src="template/' . TEMPLATE_NAME . '/img/ok.gif" alt="ok"/>';
						} else {
							@mkdir($v, 0777);
							$status = '<img src="template/' . TEMPLATE_NAME . '/img/no.gif" alt="no"/>';
						}

						echo '<div class="validate_functionexisis">' . $status . ' ' . $v . '</div>';
					}
					?></td>
			</tr>
		</table>
	</div>
<?php } ?>