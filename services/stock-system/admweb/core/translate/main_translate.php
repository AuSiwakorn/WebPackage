<?php
$keysname = REQ_get('keysname', 'request', 'str', '');
PERMIT::_PERMIT(_MODULE_, 'You can open all translate', 'สามารถเปิด Translate ได้', 'redirect', 'SET');
$aModuleTransConfig = $aModules['config']['translate'];

if (defined('PATH_UPLOAD') && !is_dir(PATH_UPLOAD . '/translate')) {
	@mkdir(PATH_UPLOAD . '/translate', 0777);
}

if (_AC_ == 'save') {
	PERMIT::_PERMIT(_MODULE_, 'You can update all translate', 'สามารถอัพเดท Translate ได้', 'redirect', '');
	include(PATH_AOWEBDATA . '/languages/default.php');
	$aUpdate = @$_REQUEST['update'];


	foreach ($aUpdate as $klang => $dataadd) {
		$aCustomLang = array();
		$file_cache_lang = array();
		$file_cache_lang = PATH_UPLOAD . '/translate/' . $klang . '.php';
		include $file_cache_lang;

		$aCustomLang = array_merge($default_aosoft_lang, $aCustomLang);
		$aCustomLang = array_merge($aCustomLang, $dataadd);

		if ($klang != '') {
			$data = buil_php_config_data($aCustomLang, '$aCustomLang');
			write_php_config($file_cache_lang, $data);
		}
	}
	AO_Clear_All();
	Func_Addlogs("[Translate] Edit = {$keysname} ");
	setRaiseMsg('Insert translate successfully.', time(), 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&keysname=" . $keysname);
	exit;
}

?>

<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">Translate</h1>
	</div>
</div>

<div id="page-content">
	<div class="row">
		<div class="col-md-8 col-md-offset-2">

			<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">
				<input type="hidden" value="save" name="ac" />



				<div class="tab-base">

					<!--Nav tabs-->
					<ul class="nav nav-tabs tabs-right">
						<?php foreach ($aConfig['language'] as $kLang => $vLang) { ?>
							<li class="<?php echo ($kLang == $lang) ? 'active' : 'noneactive'; ?>">
								<a data-toggle="tab" href="#tab-<?php echo $kLang; ?>"><?php echo $vLang; ?></a>
							</li>
						<?php } ?>
					</ul>

					<!--Tabs Content-->
					<div class="tab-content">
						<?php displayRaiseMsg(); ?>
						<?php
						foreach ($aConfig['language'] as $kLang => $vLang) {
							$aLangText = func_translate_get_lang($kLang);
							$aLangTextCustom = get_data_array_custom_lang($kLang);
							$aLangText = custom_array_merge($aLangText, $aLangTextCustom);

						?>
							<div id="tab-<?php echo $kLang; ?>" class="tab-pane fade <?php echo ($kLang == $lang) ? 'active in' : 'noneactive'; ?>">
								<div>
									<div align="right" style="margin:10px;">
										<input type="submit" class="btn btn-success dbfont" name="submit" value="Update" />
									</div>
									<table class="table table-hover table-vcenter">
										<tr>
											<td height="28" align="left"><a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . ""); ?>">keysword</a></td>
											<td align="right" width="50%">แปลเป็นภาษา ( <?php echo $vLang; ?> ) &nbsp; </td>
										</tr>
										<?php
										foreach ($aLangText as $kname => $vAllKeysLang) {
											if ($keysname != '' && $keysname != $kname) {
												continue;
											}
										?>

											<tr>
												<td colspan="2" align="left">
													<div><a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&keysname=" . $kname); ?>"><?php echo (@$aModuleTransConfig[$kname]['name']) ? @$aModuleTransConfig[$kname]['name'] : $kname; ?></a></div>
												</td>
											</tr>

											<?php foreach ($vAllKeysLang as $kk => $vv) { ?>
												<tr>
													<td><?php echo $kk; ?></td>
													<td align="left" style="padding: 3px;"><input name="update[<?php echo $kLang; ?>][<?php echo $kname; ?>][<?php echo $kk; ?>]" type="text" value="<?php echo $vv; ?>" class="form-control" title="<?php echo @$aDefaultKeyword[$kname][$kk]; ?>" /></td>
												</tr>
										<?php }
										} ?>
									</table>
									<?php
									//$aCustomLang = array();
									//include PATH_UPLOAD.'/translate/'.$kLang.'.php';
									?>
								</div>
							</div>
						<?php } ?>
					</div>
				</div>





			</form>

		</div>
	</div>
</div>