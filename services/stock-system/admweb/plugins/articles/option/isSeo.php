<div class="row">
	<div class="col-md-12">
		<div class="tab-base">
			<ul class="nav nav-tabs">
				<?php
				foreach ($aConfig['language'] as $kLang => $vLang) {
					$act = ($kLang == DEFAULT_LANGEUAGE) ? 'active text-bold ' : 'none_active';
					echo '<li class="' . $act . '"><a data-toggle="tab" href="#meta-tab-' . $kLang . '">' . $vLang . '</a></li>';
				}
				?>
			</ul>
			<div class="tab-content">
				<?php foreach ($aConfig['language'] as $kLang => $vLang) {  ?>
					<div id="meta-tab-<?php echo $kLang; ?>" class="tab-pane fade <?php echo ($kLang == DEFAULT_LANGEUAGE) ? 'active in' : ' in'; ?>">

						<?php
						foreach ($aDefaultMetaTxt as $k => $v) {
							echo '<br><br>
														<div class="form-group">
										                    <label class="col-md-2 control-label text-right">' . $k . '</label>
										                    <div class="col-md-10"><input type="text" name="meta[' . $kLang . '][' . $k . ']" class="form-control" placeholder="' . @$aMetaUse[$kLang][$k] . '" value="' . @$aMetaUse[$kLang][$k] . '" style="width:95%;"/></div>
										                </div>
				                                	';
						}
						?>

					</div>
				<?php } ?>
				<br clear="all">
				<br clear="all">
			</div>
		</div>
		<br clear="all">
		<div class="well well-lg">
			SiteConfig_viewMetaTags('<?php echo (@$keysname_new) ? $keysname_new : $keysname; ?>');
		</div>
	</div>
</div>