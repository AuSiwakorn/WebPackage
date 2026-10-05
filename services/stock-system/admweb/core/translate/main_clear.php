<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด ล้างข้อมูลที่ตั้งค่า ได้', 'redirect', 'UNSET');
$aModuleTransConfig = $aModules['config']['translate'];
if (defined('PATH_UPLOAD') && !is_dir(PATH_UPLOAD . '/translate')) {
	@mkdir(PATH_UPLOAD . '/translate', 0777);
}
if (_AC_ == 'reset') {
	$resetkeys = @$_REQUEST['resetkeys'];
	if (count($resetkeys) > 0) {
		foreach ($resetkeys as $k => $v) {
			$language_custom_file = PATH_UPLOAD . '/translate/' . $v . '.php';
			if (is_file($language_custom_file)) {
				@unlink($language_custom_file);
			}
		}
	}
	setRaiseMsg('Reset translate successfully.', time(), 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
	exit;
}
?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">Translate</h1>
	</div>
	<ol class="breadcrumb">
		<li><a href="index.php"><i class="demo-pli-home"></i></a></li>
		<li><a href="#">Translate</a></li>
		<li class="active">รีเซ็ตคำแปลที่เคยแปลไว้ </li>
	</ol>
</div>
<div id="page-content">
	<div class="row">
		<div class="col-md-8 col-md-offset-2">
			<div class="panel">
				<div class="panel-body">
					<h3>สามารถเลือกรีเซ็ต ได้ตามภาษาที่ต้องการได้ จากนั้นให้ทำการกดที่ปุ่ม reset ข้อมูลใหม่ ข้อมูลเก่าที่ท่านเคยทำการแปลไว้ ณ
						ปัจจุบันนั้นจะหายไปหมด<strong> ดังนั้น</strong> ควรแน่ใจว่าต้องการล้างค่าทั้งหมดที่เคยแปลภาษาไว้</h3>

					<form action="" method="post" enctype="multipart/form-data" name="form1" id="form1">
						<input type="hidden" value="reset" name="ac" />
						<div class="ui-widget-content" style="padding:10px;">
							<div align="left" style="padding:5px;"><input type="submit" class="btn btn-danger" name="submit" value="Reset ข้อมูลใหม่" /></div>
							<hr />
							<?php
							foreach ($aConfig['language'] as $kLang => $vLang) {
								echo '<div style="padding:5px;"><label><input name="resetkeys[' . $kLang . ']" type="checkbox" value="' . $kLang . '" /> ' . $vLang . '</label></div>';
							}
							?>
							<hr />
							<div align="left" style="padding:5px;"><input type="submit" class="btn btn-danger" name="submit" value="Reset ข้อมูลใหม่" /></div>
						</div>
						<div></div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>