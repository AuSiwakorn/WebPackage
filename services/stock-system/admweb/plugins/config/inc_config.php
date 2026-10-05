<?php
$namekeys = REQ_get('namekeys', 'request', 'str', '');
if ($ac == 'update') {
	$keys = REQ_get('keys', 'post', 'array', '');
	$time = _TIME_;
	if (count($_FILES) > 0) {
		$i = 0;
		foreach ($_FILES as $k => $v) {
			if ($v['name'] != '') {
				$extention = strtolower(end(explode('.', $v['name'])));
				//if(in_array($v['type'], $aFileAllow))keeree
				if (in_array($extention, $aPgConfig['upload_extention'])) {
					$time = $time + $i;
					$filename = 'config_file/' . $time . '.' . $extention;
					$filePath = PATH_UPLOAD . '/' . $filename;
					if (!is_dir(PATH_UPLOAD . '/config_file')) {
						@mkdir(PATH_UPLOAD . '/config_file', 0777);
					}

					if (is_file($filePath)) {
						unlink($filePath);
					}
					$aFileDelete = _get_config_value_on_keys($k);
					if ($aFileDelete['val'] != '' && is_file(PATH_UPLOAD . '/' . $aFileDelete['val'])) {
						unlink(PATH_UPLOAD . '/' . $aFileDelete['val']);
					}
					@move_uploaded_file($v['tmp_name'], $filePath);
					$keys[$k] = $filename;
					$i++;
				}
			}
		}
	}

	$__dbConfigWebsite = array();
	if (is_file($aPgConfig['load_config_file'])) {
		include($aPgConfig['load_config_file']);
	}

	foreach ($__dbConfigWebsite as $kdata => $vdata) {
		foreach ($vdata as $k => $v) {
			if ($v['type'] == 'checkbox') {
				$keys[$k] = @$keys[$k];
			}
		}
	}

	foreach ($keys as $k => $v) {
		_update_config_keys($k, $v);
	}

	setRaiseMsg('SAVE CONFIG UPDATED.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&keysname=" . $keysname);
	exit;
} elseif ($ac == 'deletefile' && $namekeys != '') {
	$aFileDelete = _get_config_value_on_keys($namekeys);
	if ($aFileDelete['val'] != '' && is_file(PATH_UPLOAD . '/' . $aFileDelete['val'])) {
		unlink(PATH_UPLOAD . '/' . $aFileDelete['val']);
		_update_config_keys($namekeys, '');
	} else {
		_update_config_keys($namekeys, '');
	}

	setRaiseMsg('DELETE FILE CONFIG UPDATED.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&keysname=" . $keysname);
	exit;
}
$__dbConfigWebsite = array();
if (is_file($aPgConfig['load_config_file'])) {
	include($aPgConfig['load_config_file']);
}
$aConfigUpdate = _get_config_all();
?>
<style>
	.k-plus {
		padding: 0px;
		color: #333333;
		border-top: 1px dashed rgba(0, 0, 0, 0.07);
	}

	.txtEcho {
		color: #7a878eb3;
		font-size: 18px;
	}

	.checkbox {
		margin: 0px;
	}
</style>
<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">
	<input name="ac" type="hidden" value="update" />
	<div id="page-head">
		<div id="page-title">
			<div class="row">
				<div class="col-md-6 text-white">
					<h1 class="page-header text-overflow"><?php echo @$tagname['name']; ?></h1>
				</div>
				<div class="col-md-6 toolbar-right text-right">
					<button class="btn btn-mint submit" id="add-and-publish" style="font-size: 24px; font-family: db_ozone_xregular; "> <i class="fa fa-save" style="font-size: 18px;"></i> Update</button>
				</div>
			</div>
		</div>
	</div>
	<div id="page-content">
	<div class="row pad-btm">
			<?php
			$_content_col = 'col-xs-12';
			$fmodule = PATH_MODULE . '/' . _MODULE_ . '/images/left_' . $keysname . '.jpg';
			$fcore = PATH_CORE . '/' . _MODULE_ . '/images/left_' . $keysname . '.jpg';

			$fmodule2 = PATH_MODULE . '/' . _MODULE_ . '/images/top_' . $keysname . '.jpg';
			$fcore2 = PATH_CORE . '/' . _MODULE_ . '/images/top_' . $keysname . '.jpg';

			$leftimg = '';
			if (is_file($fmodule)) {
				$leftimg = URL_MODULE . '/' . _MODULE_ . '/images/left_' . $keysname . '.jpg';
			} else {
				if (is_file($fcore)) {
					$leftimg = URL_CORE . '/' . _MODULE_ . '/images/left_' . $keysname . '.jpg';
				}
			}

			$topimg = '';
			if (is_file($fmodule2)) {
				$topimg = URL_MODULE . '/' . _MODULE_ . '/images/top_' . $keysname . '.jpg';
			} else {
				if (is_file($fcore2)) {
					$topimg = URL_CORE . '/' . _MODULE_ . '/images/top_' . $keysname . '.jpg';
				}
			}
			
			if ($leftimg != '') {
				$_content_col = 'col-xs-9';
			}

			?>
			<?php if ($leftimg != '') { ?> <div class="col-xs-3"><img src="<?php echo $leftimg; ?>" width="100%"></div> <?php } ?>
			<div class="<?php echo $_content_col; ?>">
				<?php if ($topimg != '') { ?> <div style="padding-bottom: 25px;"><img src="<?php echo $topimg; ?>" width="100%"></div> <?php } ?>
				<?php displayRaiseMsg(); ?>
				<?php
				$module 	= REQ_get('module', 'request', 'str', '');
				$mp 		= REQ_get('mp', 'request', 'str', '');
				$keysurl 	= REQ_get('keysname', 'request', 'str', '');
				$customtags = @$aDefaultData['customtags'];
				$num_rows = count($__dbConfigWebsite);
				if (count($__dbConfigWebsite) && is_array($__dbConfigWebsite)) {
					foreach ($__dbConfigWebsite as $k => $vKeysWord) {
				?>
						<div class="panel">
							<br>
							<div class="panel-body">
								<?php
								foreach ($vKeysWord as $k => $v) {
									$title = @$v['title'];
									$class_con = (@$v['type'] != 'detail' && @$v['type'] != 'link') ? "<div class='txtEcho' >SiteConfig_get('" . $k . "');</div>" : '';
									if (@$v['type'] == 'text') {
								?>
										<div class="form-group">
											<label class="col-lg-3 col-md-3 control-label text-right"><?php echo $title . $class_con; ?></label>
											<div class="col-lg-9 col-md-9 col-sm-9">
												<?php if ($v['des'] != '') { ?><small class="help-block"><?php echo (isset($v['des']) && $v['des'] != "") ? htmlspecialchars(@$v['des']) : ""; ?></small><?php } ?>
												<input type="text" class="form-control" name="keys[<?php echo $k; ?>]" value="<?php echo (isset($aConfigUpdate[$k]) && $aConfigUpdate[$k] != "") ? htmlspecialchars(@$aConfigUpdate[$k]) : ""; ?>" placeholder="<?php echo (isset($aConfigUpdate[$k]) && $aConfigUpdate[$k] != '') ? htmlspecialchars(@$aConfigUpdate[$k]) : ''; ?>">
											</div>
											<br clear="all">
										</div>

									<?php } elseif (@$v['type'] == 'password') { ?>
										<div class="form-group">
											<label class="col-lg-3 col-md-3 control-label text-right"><?php echo $title . $class_con; ?></label>
											<div class="col-lg-9 col-md-9 col-sm-9">
												<?php if ($v['des'] != '') { ?><small class="help-block"><?php echo (isset($v['des']) && $v['des'] != "") ? htmlspecialchars(@$v['des']) : ""; ?></small><?php } ?>
												<div class="input-group">
													<input type="password" class="form-control" name="keys[<?php echo $k; ?>]" <?php echo @$v['customtags']; ?> value="<?php echo (isset($aConfigUpdate[$k]) && $aConfigUpdate[$k] != "") ? htmlspecialchars(@$aConfigUpdate[$k]) : ""; ?>" autocomplete="new-password">
													<span class="input-group-btn">
														<button type="button" class="btn btn-default toggle-password" onclick="togglePassword(this);" title="แสดง/ซ่อนรหัสผ่าน"><i class="fa fa-eye"></i></button>
													</span>
												</div>
												<?php if ($v['notics'] != '') { ?><small class="help-block"><?php echo @$v['notics']; ?></small><?php } ?>
											</div>
											<br clear="all">
										</div>

									<?php } elseif (@$v['type'] == 'select') { ?>
										<div class="form-group">
											<label class="col-lg-3 col-md-3 control-label text-right"><?php echo $title . $class_con; ?></label>
											<div class="col-lg-9 col-md-9 col-sm-9">
												<?php if ($v['des'] != '') { ?><small class="help-block"><?php echo @$v['des']; ?></small><?php } ?>
												<select class="form-control" name="keys[<?php echo $k; ?>]">
													<?php foreach ($v['data'] as $dk => $dv) {
														$se = ($dk == $aConfigUpdate[$k]) ? ' selected="selected" ' : ''; ?><option <?php echo $se ?> value="<?php echo $dk ?>"><?php echo $dv ?></option><?php } ?>
												</select>
											</div>
											<br clear="all">
										</div>

									<?php } elseif (@$v['type'] == 'checkbox') { ?>
										<div class="form-group">
											<label class="col-lg-3 col-md-3 control-label text-right"><?php echo $title . $class_con; ?></label>
											<div class="col-lg-9 col-md-9 col-sm-9 checkbox">
												<input id="demo-form-inline-checkbox-<?php echo $k; ?>" class="magic-checkbox" type="checkbox" name="keys[<?php echo $k; ?>]" value="1" <?php echo ((@$aConfigUpdate[$k] == 1) ? ' checked="checked" ' : ''); ?>>
												<label for="demo-form-inline-checkbox-<?php echo $k; ?>"><?php echo @$v['des']; ?></label>
												<?php if ($v['notics'] != '') { ?><small class="help-block"><?php echo @$v['notics']; ?></small><?php } ?>
											</div>
											<br clear="all">
										</div>

									<?php } elseif (@$v['type'] == 'textarea') { ?>
										<div class="form-group">
											<label class="col-lg-3 col-md-3 control-label text-right"><?php echo $title . $class_con; ?></label>
											<div class="col-lg-9 col-md-9 col-sm-9">
												<?php if ($v['des'] != '') { ?><small class="help-block"><?php echo @$v['des']; ?></small><?php } ?>
												<textarea rows="9" class="form-control" name="keys[<?php echo $k; ?>]" placeholder="<?php echo @$v['notics']; ?>"><?php echo @$aConfigUpdate[$k]; ?></textarea>
											</div>
											<br clear="all">
										</div>

									<?php } elseif (@$v['type'] == 'file') {
										$txtdeletefile = (@$aConfigUpdate[$k] != '') ? ' [ <a href="' . _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&ac=deletefile&keysname=' . $keysurl . '&namekeys=' . $k) . '" class="notload text-danger" onclick="return confirm(\'Delete ?\');">DELETE</a> ]' : '';
										$txt = (@$aConfigUpdate[$k] != '') ? '<a href="' . URL_UPLOAD . '/' . @$aConfigUpdate[$k] . '" target="_blank" class="notload text-danger">' . @$aConfigUpdate[$k] . '</a> &nbsp; &nbsp; &nbsp; ' . $txtdeletefile : '';
										$class_con = (@$v['type'] != 'detail' && @$v['type'] != 'link') ? "<div class='txtEcho' >SiteConfig_get('" . $k . "','', 'upload');</div>" : '';
									?>
										<div class="form-group">
											<label class="col-lg-3 col-md-3 control-label text-right"><?php echo $title . $class_con; ?></label>
											<div class="col-lg-9 col-md-9 col-sm-9">
												<?php echo $txt; ?>
												<?php if ($v['notics'] != '') { ?><small class="help-block"><?php echo @$v['notics']; ?></small><?php } ?>
												<?php if ($v['des'] != '') { ?><div><small class="help-block"><?php echo @$v['des']; ?></small></div><?php } ?>
												<span class="pull-left btn btn-primary btn-file">Choose a file from your device.<input type="file" name="<?php echo $k; ?>" onchange="readImageURL(this, '<?php echo $k; ?>');"></span>
												<input hidden type="text" class="<?php echo $k; ?>Base64" name="<?php echo $k; ?>Base64" value="<?php echo (isset($aConfigUpdate[$k])) ? URL_UPLOAD . '/' . @$aConfigUpdate[$k] : ''; ?>">
											</div>
											<br clear="all">
										</div>

									<?php } elseif (@$v['type'] == 'detail') { ?>
										<div class="form-group">
											<label class="col-lg-3 col-md-3 control-label text-right"><?php echo $title . $class_con; ?></label>
											<div class="col-lg-9 col-md-9 col-sm-9">
												<?php if ($v['des'] != '') { ?><small class="help-block"><?php echo @$v['des']; ?></small><?php } ?>
												<?php _switch_input($v, $k, @$aConfigUpdate[$k]); ?>
											</div>
											<br clear="all">
										</div>
									<?php } ?>
									<?php //if (@$v['type'] == 'link') { } 
									?>
								<?php }
								$ch = $num_rows - 1;
								echo ($ch > 0) ? '<div class="col-lg-12 k-plus"> &nbsp; </div>' : '';
								$num_rows = $ch;
								?>
							</div>
						</div>
				<?php }
				}
				?>
			</div>
		</div>
		<?php Func_HelpUse('config/help/config.txt'); ?>
	</div>
</form>
<script>
	function readImageURL(input, name) {
		if (input.files && input.files[0]) {
			const reader = new FileReader();
			reader.onload = (e) => {
				$('.' + name + 'Base64').attr('value', e.target.result);
			};
			reader.readAsDataURL(input.files[0]);
		}
	}

	// TODO: [x] สลับซ่อน/แสดงรหัสผ่านสำหรับ input type=password
	function togglePassword(btn) {
		var input = btn.closest('.input-group').querySelector('input');
		var icon = btn.querySelector('i');
		if (input.type === 'password') {
			input.type = 'text';
			icon.classList.remove('fa-eye');
			icon.classList.add('fa-eye-slash');
		} else {
			input.type = 'password';
			icon.classList.remove('fa-eye-slash');
			icon.classList.add('fa-eye');
		}
	}
</script>