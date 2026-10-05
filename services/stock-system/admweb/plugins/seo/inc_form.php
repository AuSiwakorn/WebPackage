
		<div class="col-xs-12">
			<form id="form1" name="form1" method="post" action="">
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
					<?php foreach (@$aConfig['language'] as $kLang => $vLang) { ?>
					<div id="demo-lft-tab-<?php echo $kLang; ?>" class="tab-pane fade <?php echo ($kLang == DEFAULT_LANGEUAGE) ? 'active in' : ' in'; ?>">
						<?php 
						foreach ($aDefaultMetaTxt as $k => $v) {
							echo '
										<div class="form-group">
						                    <label class="col-md-2 control-label text-right">'.$k.'</label>
						                    <div class="col-md-10"><input type="text" name="meta['. $kLang .']['. $k .']" class="form-control" placeholder="'. @$aMetaUse[$kLang][$k] .'" value="'. @$aMetaUse[$kLang][$k] .'" style="width:85%;"/></div>
						                </div>
                                ';
						}
						?>
						<br clear="all" /><br clear="all" />
						<div class="form-group">
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
