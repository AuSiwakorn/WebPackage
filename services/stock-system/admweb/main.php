<?php
header("X-Robots-Tag: noindex, nofollow", true);
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
// header("Content-Security-Policy: default-src 'self'; 
//         script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; 
//         style-src  'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; 
//         font-src   'self' https://fonts.gstatic.com;");
$oUser = login_logout::getLoginData();
?>

<div id="page-content">
	<?php displayRaiseMsg(); ?>
	<?php
	$h = 0;
	if (count($aConfig['aModuleUse']) > 1) {
		foreach ($aConfig['aModuleUse'] as $k => $v) {
			$h = 1;
			$fmodule = PATH_MODULE . '/' . $v . '/hooks/hooks_admin_main.php';
			$fcore = PATH_CORE . '/' . $v . '/hooks/hooks_admin_main.php';
			$hooksfilename = is_file($fmodule) ? $fmodule : $fcore;
			if (is_file($hooksfilename)) {
				include($hooksfilename);
			}
		}
		echo '<div style="height:10px;"></div>';
	}

	if ($h == 0) {
	?>
		<table width="100%" border="0" cellpadding="50" cellspacing="0">
			<tr>
				<td align="center" style="color:#DDD; font-size:90px;" height="250">
					<?php echo @ADMIN_PRO_TITLE_MAIN; ?>
				</td>
			</tr>
		</table>
	<?php } ?>
</div>