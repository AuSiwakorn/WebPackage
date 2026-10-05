<?php
PERMIT::_PERMIT(_MODULE_, 'module|mp', 'สามารถเปิด คีย์ที่ยังไม่ได้แปล ได้', 'redirect', 'UNSET');
if (_AC_ == 'del') {
	unlink(PATH_UPLOAD . '/translate/req.txt');
	setRaiseMsg('Clear translate req successfully.', time(), 0);
	CustomRedirectToUrl("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "");
	exit;
}
$aDefaultK = func_translate_get_default();
$abuil = array();
if (is_file(PATH_UPLOAD . '/translate/req.txt') && file_exists(PATH_UPLOAD . '/translate/req.txt')) {
	$a = file(PATH_UPLOAD . '/translate/req.txt');
} else {
	$a = array();
}
if (is_array($a) && count($a) > 0) {
	foreach ($a as $k => $v) {
		$kadd = str_replace("\n", '', trim($v));

		$aKeyAdd = explode('|###|', $kadd);
		$key_req = ($aKeyAdd[1] != '') ? $aKeyAdd[1] : 'key_req';
		$kadd = $aKeyAdd[0];
		if (
			$kadd != '' && @$aDefaultK[$key_req][$aKeyAdd[0]] == ''
		) {
			$abuil[$key_req][$aKeyAdd[0]] = $aKeyAdd[0];
		}
	}
	$a = buil_php_config_data($abuil, $aName = '$default_aosoft_lang');
	if (is_array($abuil) && count($abuil) == 0) {
		unlink(@PATH_UPLOAD . '/translate/req.txt');
		$a = 'ไม่มีข้อมูลการ ที่ต้องการให้เพิ่ม';
	}
} else {
	$a = 'ไม่มีข้อมูลการ ที่ต้องการให้เพิ่ม';
}

?>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">Translate</h1>
	</div>
	<ol class="breadcrumb">
		<li><a href="index.php"><i class="demo-pli-home"></i></a></li>
		<li><a href="#">Translate</a></li>
		<li class="active">คีย์ภาษาที่ยังไม่ได้แปล </li>
	</ol>
</div>
<div id="page-content">
	<div class="row">
		<div class="col-md-8 col-md-offset-2">
			<div class="panel">
				<div class="panel-body">
					<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&ac=del"); ?>" class="btn btn-danger">ล้างข้อมูลส่วนนี้</a>
					<br><br>
					<?php pre($a); ?>
					<a href="<?php echo _admin_buil_link("index.php?module=" . _MODULE_ . "&mp=" . _MP_ . "&ac=del"); ?>" class="btn btn-danger">ล้างข้อมูลส่วนนี้</a>
				</div>
			</div>
		</div>
	</div>
</div>