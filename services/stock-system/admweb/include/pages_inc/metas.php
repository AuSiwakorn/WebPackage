<?php
#set keys meta tag
$ac 				    = REQ_get('ac', 'request', 'str');
$meta 			    = @$_REQUEST['meta']; //REQ_get('meta', 'request', 'str');
$metakey 		= REQ_get('metakey', 'request', 'str', $module);
$key_meta 		= $metakey;

///////////////////////////////////////////////////////
/////////////////// isSecurMode ////////////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array($ac, array('saveedit', 'save', 'add', 'edit', 'delete')) && $isSecurMode == 1) {
	setRaiseMsg('Secur Mode.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=metas" . $u);
	exit;
}
///////////////////////////////////////////////////////
/////////////////// isSecurMode ////////////////////
///////////////////////////////////////////////////////

if ($ac == 'save') {
	foreach ($meta as $k => $aMetaAdd) {
		updateMetaTags($key_meta, $k, $aMetaAdd);
	}

	setRaiseMsg('Save meta tags is successfully.', _TIME_, 0);
	$u = ($metakey != '') ? '&metakey=' . $metakey : '';
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=metas" . $u);
	exit;
}

$aDefaultMetaTxt 	= getDefaultMetatags();
foreach ($aConfig['language'] as $kLang => $vLang) {
	$aDefaultMeta[$kLang] 	= getMetaTagsByLang('default', $kLang);
	$aMeta[$kLang] 			= getMetaTagsByLang($key_meta, $kLang);
	$aMetaUse[$kLang] = arrayMergeMeta($aDefaultMetaTxt, $aDefaultMeta[$kLang], $aMeta[$kLang]);
}
?>
<script type="text/javascript">
	$(function() {
		$('#tabsMeta').tabs();
		$('.submit').button();
	});
</script>
<div align="center">
	<h2><?php ___lang('Basic settings of web pages that use this module'); ?> </h2>
</div>
<div style="padding:5px;">

	<form id="form1" name="form1" method="post" action="">
		<input name="ac" type="hidden" value="save" />
		<table width="100%" border="0" cellspacing="0" cellpadding="5">
			<tr>
				<td align="right" style="padding-right:15px;"><input type="submit" class="submit" name="Submit" value="<?php ___lang('Save information immediately'); ?>" /></td>
			</tr>
			<tr>
				<td align="left">
					<div style="padding:7px;">
						<div id="tabsMeta">
							<ul>
								<?php
								foreach ($aConfig['language'] as $kLang => $vLang) {
									echo '<li><a href="#tabs-' . $kLang . '"> ' . $vLang . '</a></li>';
								} ?>
								<li><a href="#tabs-help"><?php ___lang('Description Tags'); ?></a></li>
							</ul>

							<?php
							foreach (@$aConfig['language'] as $kLang => $vLang) {
								echo '
        					<div id="tabs-' . $kLang . '">
        					   <div style="padding:10px;" align="center">Edit for language : <b>' . $vLang . '</b> ( metakeys = ' . $metakey . ' )</div>
                            ';

								foreach ($aDefaultMetaTxt as $k => $v) {
									$hight = (@$v != '') ? '*' : '';
									echo '
                                    <div>
                                        <table width="100%" border="0" cellspacing="0" cellpadding="5">
                                            <tr>
                                                <td width="140" align="right">' . $k . ' : </td>
                                                <td align="left"><input type="text" name="meta[' . $kLang . '][' . $k . ']" value="' . @$aMetaUse[$kLang][$k] . '" style="width:90%;"/> ' . $hight . '</td>
                                            </tr>
                                        </table>
                                    </div>
                                ';
								}
								echo '</div>';
							} ?>

							<div id="tabs-help">
								<?php include('include/meta-tag-help.html'); ?>
							</div>
						</div>
					</div>
				</td>
			</tr>
			<tr>
				<td align="left" style="padding-left:15px;"><input type="submit" name="Submit2" class="submit" value="<?php ___lang('Save information immediately'); ?>" /></td>
			</tr>
		</table>
	</form>
</div>