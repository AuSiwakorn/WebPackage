<?php
	$keysname = (isset($_GET['keysname']) && $_GET['keysname'] != '') ? $_GET['keysname'] : '';
	$name = (isset($_GET['n']) && $_GET['n'] != '') ? $_GET['n'] : '';
	$name = ($name == '') ? $keysname : $name;
	$ac = REQ_get('ac', 'post', 'str'); 
	
	if ($name == '') {
		setRaiseMsg('Cannot get name html page. Please set "keysname" OR "n" . ',_TIME_,1);
		CustomRedirectToUrl("index.php?module=".$module."&mp=".$mp."&inc=txt&n=noname");
		exit;
	}
	
	if (!is_dir(PATH_UPLOAD . '/html') && PATH_UPLOAD != '') {
		@mkdir(PATH_UPLOAD . '/html',0777);
	}
	
    if ($ac == 'save') {
		$content = array();
		$cateID = isset($_POST['id']) ? $_POST['id'] : 'non'; //ไม่แแน่ใจ
		$content = (isset($_POST['content']) && count($_POST['content']) > 0) ? $_POST['content'] : '';
		
		if ($content != '') {
			foreach ($content as $k => $v) {
				$pathAdsHtml = PATH_UPLOAD . '/html/html_' . $name . '_'.$k.'.html';
    			writeHTMLContent($pathAdsHtml, @$content[$k]);
			}
		}
		
    	CustomRedirectToUrl("index.php?module=".$module."&mp=".$mp."&inc=txt&n=".$name);
		exit;
    }
	
	$content = array();
	foreach ($aConfig['language'] as $kLang => $vLang) {
		$pathAdsHtml = PATH_UPLOAD . '/html/html_' . $name . '_'.$kLang.'.html';
		$content[$kLang] = getHTMLContent($pathAdsHtml);
	}
	
?>

<script type="text/javascript">
	$(function()
			{
		$('#tabssHtml').tabs({
			selected:
				<?php 
						if($lang == 'en'){
							echo '1';
						} elseif ($lang == 'vn'){
							echo '2'; 	
						} elseif ($lang == 'id'){
							echo '3'; 	
						} else {
							echo '0';
						}
						?>
			});
		$(".submit").button();
	});
</script>
<center><h2><?php echo (isset($pageTitleList[$name])) ? $pageTitleList[$name] : ucwords('Html '.$name.' Box'); ?></h2></center>
<form id="form1" name="form1" method="post" action="">
<input type="hidden" name="ac" value="save" />
<div style="padding:8px;">
	<div id="tabssHtml">
		<ul>
		<?php 
		// tab bar menu
		foreach ($aConfig['language'] as $kLang => $vLang) {
		?>
			<li><a href="#tabs-<?php echo $kLang; ?>"><?php echo $vLang; ?></a></li>
		<?php
		}
		?>
		</ul>
		
		
		<?php 
		// tab content menu
		foreach ($aConfig['language'] as $kLang => $vLang) {
		?>
		<input type="hidden" name="name[<?php echo $kLang; ?>]" value="<?php echo $name; ?>" />
		<div id="tabs-<?php echo $kLang; ?>">
		<div style="padding:10px;" align="center">Edit for language : <b><?php echo $vLang; ?></b></div>
	<table width="100%" border="0" cellspacing="0" cellpadding="5">
	  <tr>
		<td align="center"><textarea class="ckeditor" name="content[<?php echo $kLang; ?>]" style="width:99%; background-color:#FCFCFC; border:2px double #CCCCCC;" rows="15"><?php echo (@$content[$kLang] != '') ? @$content[$kLang] : '<p>&nbsp;</p>'; ?></textarea></td>
	  </tr>
	  <tr>
		<td align="left">
		<input type="submit" class="submit" name="Submit" value=<?php ___lang('แก้ไขข้อมูลทันที');?> />
		<div style="color: #555;padding: 5px;" align="center"><?php ___lang('วิธีเรียกใช้งาน');?> : &lt;?php echo ManageHtml_get('<?php echo $name; ?>'); ?&gt;</div>
		</td>
	  </tr>
	</table>
		</div>
		<?php } ?>
	</div>
</div>
</form>

<?php 
$aConfig['editor_height'] = (@$isOpens['heightEditorTxt'] != '') ? $isOpens['heightEditorTxt'] : 350;
include('include/pages_inc/editor.php');
?>