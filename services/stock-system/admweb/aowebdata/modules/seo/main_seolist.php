<?php
$metakey = REQ_get('metakey', 'request', 'str', '');
PERMIT::_PERMIT(_MODULE_, 'module|mp|metakey', 'สามารถเปิด ' . $metakey . ' ได้', 'redirect', 'SET');
define("PLUGIN_INC", 'seo');

if (is_file(PATH_PLUGIN . '/' . PLUGIN_INC . '/inc_action.php')) {
	include(PATH_PLUGIN . '/' . PLUGIN_INC . '/inc_action.php');
}

$keysname = @$_REQUEST['metakey'];
?>
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
		<?php
		if (is_file(PATH_PLUGIN . '/' . PLUGIN_INC . '/inc_form.php')) {
			include(PATH_PLUGIN . '/' . PLUGIN_INC . '/inc_form.php');
		}

		?>

		<div class="col-xs-12">
			<div class="panel">
				<div class="panel-body">
					<div class="table-responsive">
						<table class="table table-striped">
							<thead>
								<tr>
									<th>Title</th>
									<th>Keys</th>
									<th>Use</th>
									<th width="100">set</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$aSeoList = _articles_SEOgetAll($keysname);
								foreach ($aSeoList['data'] as $kkk => $vvv) {
									$url = _admin_buil_link('index.php?module=' . _MODULE_ . '&mp=metas&metakey=' . $keysname . '_' . $vvv['articles_id']);
								?>
									<tr>
										<td><?php echo $vvv['title']; ?></td>
										<td><?php echo $keysname . '_' . $vvv['articles_id']; ?></td>
										<td>SiteConfig_viewMetaTags('<?php echo $keysname . '_' . $vvv['articles_id']; ?>');</td>
										<td><a href="<?php echo $url; ?>" class="btn btn-info">META TAGS</a></td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>