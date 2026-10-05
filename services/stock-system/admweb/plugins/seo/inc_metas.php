<?php
$ac = REQ_get('ac', 'post', 'str'); 
$meta = @$_POST['meta'];
#set keys meta tag
$metakey = (@$_REQUEST['metakey'] != '') ? $_REQUEST['metakey'] : $module;
$key_meta = $metakey;

if ($ac == '_save_meta') {
	foreach ($meta as $k => $aMetaAdd) {
		updateMetaTags($key_meta, $k, $aMetaAdd);
	}
	
	setRaiseMsg('Save meta tags is successfully.',_TIME_,0);
	CustomRedirectToUrl("refresh");
    exit;
}

$aDefaultMetaTxt 	= getDefaultMetatags();
foreach ($aConfig['language'] as $kLang => $vLang) {
	$aDefaultMeta[$kLang] 	= getMetaTagsByLang('default', $kLang);
	$aMeta[$kLang] 			= getMetaTagsByLang($key_meta, $kLang);
	$aMetaUse[$kLang] = arrayMergeMeta($aDefaultMetaTxt, $aDefaultMeta[$kLang], $aMeta[$kLang]);
}

?>
	<div class="row">
		<div class="col-xs-12">
				<form id="form1" name="form1" method="post" action="">
				  	<input name="ac" type="hidden" value="_save_meta" />
				  		<div class="tab-base">
								<ul class="nav nav-tabs">
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
				  </form>
		</div>
	</div>