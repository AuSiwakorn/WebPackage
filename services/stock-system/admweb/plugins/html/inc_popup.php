<?php
	$name = (isset($_GET['n']) && $_GET['n'] != '') ? $_GET['n'] : '';
	$ac = REQ_get('ac', 'post', 'str'); 

	if ($name == '') {
		setRaiseMsg('Cannot get name html page.',_TIME_,1);
		CustomRedirectToUrl("index.php?module=".$module."&mp=".$mp."&inc=".$inc."&ty=".$ty."&n=noname");
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
		
    	CustomRedirectToUrl("popFrame.php?module=".$module."&mp=".$mp."&inc=".$inc."&ty=".$ty."&n=".$name);
		exit;
    }
	
	//$aDefaultMetaTxt = getDefaultMetatags();
	$content = array();
	foreach ($aConfig['language'] as $kLang => $vLang) {
		$pathAdsHtml = PATH_UPLOAD . '/html/html_' . $name . '_'.$kLang.'.html';
		$content[$kLang] = getHTMLContent($pathAdsHtml);
		$aMeta[$kLang] = getMetaTagsByLang($name, $kLang);
	}
	
?>
<style type="text/css">
td {font-size:12px;}
</style>
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
<h2> &nbsp; <?php echo (isset($pageTitleList[$name])) ? $pageTitleList[$name] : ucwords('Html '.$name.' Page'); ?></h2>
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
		<div style="padding:10px;" align="center">Edit for language : <b><?php echo $vLang; ?></b> (<?php echo $name . '_'.$kLang; ?>)</div>
       
	<table width="100%" border="0" cellspacing="0" cellpadding="5">
	  <tr>
		<td align="center">
			<textarea class="ckeditor" name="content[<?php echo $kLang; ?>]" rows="15"><?php echo (@$content[$kLang] != '') ? @$content[$kLang] : '<p>&nbsp;</p>'; ?></textarea>
		</td>
	  </tr>
	  <tr>
		<td align="left"><input type="submit" class="submit" name="Submit" value="แก้ข้อมูลทันที" /></td>
	  </tr>
	  <tr>
	  	<td>
			<?php /*foreach ($aDefaultMetaTxt as $k => $v) { ?>
						<div>
						  <table width="100%" border="0" cellspacing="0" cellpadding="5">
                            <tr>
                              <td width="130" align="right" valign="top"><?php echo $k; ?> : </td>
							   <td align="left"><?php echo ($v != '') ? '* ex : ' . $v . '<br />' : ''; ?><input type="text" name="meta[<?php echo $kLang; ?>][<?php echo $k; ?>]" value="<?php echo @$aMeta[$kLang][$k]; ?>" style="width:75%;"/> </td>
                            </tr>
                          </table>
						</div>	
			<?php } */?>
		</td>
	    </tr>
		<tr>
		<td align="left"><input type="submit" class="submit" name="Submit" value="แก้ข้อมูลทันที" /></td>
	  </tr>
	</table>
		</div>
		<?php } ?>
	</div>
</div>

</form>
<?php 
$aConfig['editor_height'] = (@$isOpens['heightEditorHtml'] != '') ? $isOpens['heightEditorHtml'] : 400;
include('include/pages_inc/editor.php');
?>