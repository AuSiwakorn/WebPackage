<?php
	$keysname = REQ_get('keysname', 'get', 'str', '');
	$name = (isset($_GET['n']) && $_GET['n'] != '') ? $_GET['n'] : '';
	$name = ($name == '') ? $keysname : $name;
	$ac = REQ_get('ac', 'post', 'str'); 

	if ($name == '') {
		setRaiseMsg('Cannot get name html page. Please set "keysname" OR "n" . ',_TIME_,1);
		CustomRedirectToUrl("index.php?module=".$module."&mp=".$mp."&n=noname");
		exit;
	}
	
	if (!is_dir(PATH_UPLOAD . '/html') && PATH_UPLOAD != '') {
		@mkdir(PATH_UPLOAD . '/html',0777);
	}
	
    if ($ac == 'save') {
		$content = array();
		$cateID = isset($_POST['id']) ? $_POST['id'] : 'non'; //ไม่แน่ใจ
		$content = (isset($_POST['content']) && count($_POST['content']) > 0) ? $_POST['content'] : '';
		$meta = @$_POST['meta'];
		
		if ($content != '') {
			foreach ($content as $k => $v) {
				$pathAdsHtml = PATH_UPLOAD . '/html/html_' . $name . '_'.$k.'.html';
    			writeHTMLContent($pathAdsHtml, @$content[$k]);
				
				//option add meta
				updateMetaTags($name, $k, $meta[$k]);
			}
		}
		
    	CustomRedirectToUrl("index.php?module=".$module."&mp=".$mp."&n=".$name);
		exit;
    }
	
	$content = array();
	foreach ($aConfig['language'] as $kLang => $vLang) {
		$pathAdsHtml = PATH_UPLOAD . '/html/html_' . $name . '_'.$kLang.'.html';
		$content[$kLang] = getHTMLContent($pathAdsHtml);
		$aMeta[$kLang] = getMetaTagsByLang($name, $kLang);
	}
	
?>

<div id="page-head">
	<div id="page-title">
    	<h1 class="page-header text-overflow">แก้ไขข้อมูลเฉพาะส่วน</h1>
	</div>
		<ol class="breadcrumb">
			<li><a href="#"><i class="demo-pli-home"></i></a></li>
			<li><a href="#">แก้ไขข้อมูลเฉพาะส่วน</a></li>
			<li class="active"><?php echo (isset($pageTitleList[$name])) ? $pageTitleList[$name] : ucwords('Html '.$name.' Page'); ?></li>
		</ol>
</div>
<div id="page-content">
	<div class="row">
		<div class="col-md-12">
					<?php displayRaiseMsg(); ?>
				  <form id="form1" name="form1" method="post" action="">
				  	<input type="hidden" name="ac" value="save" />
				  		<div class="tab-base">
								<ul class="nav nav-tabs">
								<?php foreach ($aConfig['language'] as $kLang => $vLang) { ?>
									<li class="<?php echo ($kLang == DEFAULT_LANGEUAGE) ? 'active' : 'none_active'; ?>">
										<a data-toggle="tab" href="#demo-lft-tab-<?php echo $kLang; ?>"><?php echo $vLang; ?> <!-- <span class="badge badge-purple">27</span> --></a>
									</li>
								<?php } ?>
								</ul>
								<div class="tab-content">
									<?php foreach (@$aConfig['language'] as $kLang => $vLang) { ?>
									<input type="hidden" name="name[<?php echo $kLang; ?>]" value="<?php echo $name; ?>" />
									<div id="demo-lft-tab-<?php echo $kLang; ?>" class="tab-pane fade <?php echo ($kLang == DEFAULT_LANGEUAGE) ? 'active in' : ' in'; ?>">
										<div class="form-group">
											<div class="col-md-12">
												<div style="padding:10px;" align="center">Edit for language : <b><?php echo $vLang; ?></b> (<?php echo $name . '_'.$kLang; ?>)</div>
												<textarea class="ckeditor demo-summernote" name="content[<?php echo $kLang; ?>]" rows="15"><?php echo (@$content[$kLang] != '') ? @$content[$kLang] : '<p>&nbsp;</p>'; ?></textarea>
												<div class="text-right"><button id="demo-btn-addrow" class="btn btn-mint">UPDATE</button></div>
											</div>
										</div>
										<br clear="all" />
									</div>
									<?php } ?>
								</div>
							</div>
							
									<div class="well well-sm text-center">วิธีเรียกใช้งานสำหรับหน้าเว็บ : &lt;?php echo ManageHtml_get('<?php echo $name; ?>'); ?&gt;</div>
				  </form>
		</div>
	</div>
</div>