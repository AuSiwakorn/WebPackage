<?php
include 'inc_action.php';

?>
<style>
	input[type="file"] {
		display: none;
	}

	.custom-file-upload {
		border: 1px solid #ccc;
		display: inline-block;
		padding: 6px 12px;
		cursor: pointer;
	}
</style>
<div id="page-head">
	<div id="page-title">
		<h1 class="page-header text-overflow">SEO</h1>
	</div>
	<ol class="breadcrumb">
		<li><a href="<?php echo URL_ADMIN; ?>"><i class="demo-pli-home"></i></a></li>
		<li><a href="#">SEO</a></li>
		<li class="active">ตั้งค่า SEO Meta Tag</li>
	</ol>
</div>
<div id="page-content">
	<div class="row">
		<div class="col-xs-12">
			<form id="form1" name="form1" method="post" action="" enctype="multipart/form-data">
				<div class="tab-base">
					<ul class="nav nav-tabs dbfont20">
						<?php foreach ($aConfig['language'] as $kLang => $vLang) { ?>
							<li class="<?php echo ($kLang == DEFAULT_LANGEUAGE) ? 'active' : 'none_active'; ?>">
								<a data-toggle="tab" href="#demo-lft-tab-<?php echo $kLang; ?>"><?php echo $vLang; ?> <!-- <span class="badge badge-purple">27</span> --></a>
							</li>
						<?php } ?>
						<li><a data-toggle="tab" href="#demo-lft-tab-help">คำอธิบาย Meta Tags</a></li>
					</ul>
					<div class="tab-content">
						<?php echo displayRaiseMsg(); ?>
						<?php
						foreach (@$aConfig['language'] as $kLang => $vLang) {
						?>
							<div id="demo-lft-tab-<?php echo $kLang; ?>" class="tab-pane fade <?php echo ($kLang == DEFAULT_LANGEUAGE) ? 'active in' : ' in'; ?>">
								<?php
								foreach ($aDefaultMetaTxt as $k => $v) {
									if ($k == 'icon') {

										if (!empty($aMetaUse[$kLang][$k])) {
											echo '
										<div class="row" style="margin-top:10px;">
											<label class="col-md-2 control-label text-right">icon</label>
											<div class="col-md-10">
												<a href="' . URL_UPLOAD . '/' . htmlspecialchars($aMetaUse[$kLang][$k], ENT_QUOTES, "UTF-8") . '" target="_blank">
													' . htmlspecialchars($aMetaUse[$kLang][$k], ENT_QUOTES, "UTF-8") . '
												</a>
												&nbsp;
												<a href="index.php?module=seo&mp=metas&metakey=' . $key_meta . '&lang=' . urlencode($kLang) . '&ac=del' . '"
												class="btn btn-danger btn-xs"
												onclick="return confirm(\'ยืนยันการลบ icon นี้หรือไม่?\');">
													<i class="fa fa-trash"></i> ลบ
												</a>
											</div>
										</div>
										';
										}

										include('icon.php');

									} else {
										echo '
									<div class="row" style="margin-top:10px;">
										<label class="col-md-2 control-label text-right">' . $k . '</label>
										<div class="col-md-10">
											<input type="text"
												name="meta[' . $kLang . '][' . $k . ']"
												class="form-control"
												placeholder="' . htmlspecialchars(@$aMetaUse[$kLang][$k], ENT_QUOTES, "UTF-8") . '"
												value="' . htmlspecialchars(@$aMetaUse[$kLang][$k], ENT_QUOTES, "UTF-8") . '"
												style="width:85%;"/>
										</div>
									</div>
									';
									}
								}

								include('Schema.php');
								?>
								<div class="row" style="margin-top:10px;">
									<label class="col-md-2 control-label text-right">&nbsp;</label>
									<div class="col-md-10"><button id="demo-btn-addrow" class="btn btn-mint">UPDATE SEO META</button></div>
								</div>
								<br clear="all" />
							</div>
						<?php } ?>
						<div id="demo-lft-tab-help" class="tab-pane fade in"><?php include('include/meta-tag-help.html'); ?></div>
					</div>
				</div>
				<div class="well well-lg">SiteConfig_viewMetaTags('<?php echo @$key_meta; ?>');</div>
				<input name="ac" type="hidden" value="save" />
			</form>
		</div>

		<div class="col-xs-12">
			<div class="panel">
				<div class="panel-body">
					<table width="98%" border="0" align="center" cellpadding="5" cellspacing="0">
						<tr>
							<td> <?php ___lang('การตั้งค่า เหล่านี้บางจุดเป็นส่วนสำคัญ ที่เจ้าของเว็บไซต์ควรที่จะตั้งค่าให้ถูกตรงและครบถ้วน ซึ่งจะเป็นผลดีทั้งในด้านของ SEO และในส่วนของการแสดงผลข้อมูลต่างๆบนเว็บไซต์ของท่านอย่างถูกต้อง ตรงกับที่ออกแบบไว้ ข้อมูลบางอย่างที่ไม่รู้ก็ไม่จำเป็นต้องกรอกก็ได้ครับ ซึ่งพื้นฐานแล้วทางทีมงานจะดำเนินการใส่ข้อมูลให้เริ่มต้นอยู่แล้วครับ'); ?> </td>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>