<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<base href="<?php echo URL_ADMIN; ?>/">
	<title><?php echo (@ADMIN_TITLE != 'ADMIN_TITLE') ? ADMIN_TITLE : DOMAIN_NAME; ?></title>

	<link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700' rel='stylesheet' type='text/css'>
	<link href="<?php echo TEMPLATE_URL; ?>/css/bootstrap.min.css" rel="stylesheet">

	<link href="<?php echo TEMPLATE_URL; ?>/css/nifty.min.css" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/css/demo/nifty-demo-icons.min.css" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/plugins/font-awesome/css/font-awesome.min.css?v=<?php echo CACHE_VERSION; ?>" rel="stylesheet">
	<link href="<?php echo TEMPLATE_URL; ?>/plugins/colorbox-master/example1/colorbox.css" rel="stylesheet">
	<!-- xxxxxxxxxxxxxxxxxxxxxxx -->
	<link href="<?php echo TEMPLATE_URL; ?>/fonts/stylesheet.css?v=<?php echo CACHE_VERSION; ?>" rel="stylesheet">
	<?php
	$aGetFavicon = GlobalConfig_get('adminfavicon', '', 'upload');
	if (!empty($aGetFavicon)) {
		echo '<link rel="icon" href="' . htmlspecialchars($aGetFavicon, ENT_QUOTES, "UTF-8") . '">';
	}
	?>

	<?php
	if (_MODULE_ == '' || _MODULE_ == 'main') {
		include(TEMPLATE_PATH . '/jsao/dashboard-css.php');
		//if (is_file(TEMPLATE_PATH.'/jsao/dashboard-css.php')) {
		//} else {
		//echo 'CSS Dashboard is not found!';
		//}
	} else {
		if (/*defined('PLUGIN_INC')*/1) {
			$jsLoad = PATH_PLUGIN . '/articles/css/css.php';
			if (is_file($jsLoad)) {
				include($jsLoad);
			}
		}
		$fmodule = PATH_MODULE . '/' . _MODULE_ . '/css.php';
		$fcore = PATH_CORE . '/' . _MODULE_ . '/css.php';
		$cssfile = is_file($fmodule) ? $fmodule : $fcore;
		if (is_file($cssfile)) {
			include($cssfile);
		}
	}
	?>
	<?php
	if (is_file(PATH_WEB_ROOT . '/include/favicon.php')) {
		include(PATH_WEB_ROOT . '/include/favicon.php');
	}
	?>
	<?php
	if (GlobalConfig_get('cssfulltheme')) {
		echo '<link id="theme" href="' . TEMPLATE_URL . '/css/themes/type-full/theme-dark-full.min.css" rel="stylesheet">';
	} else {
		echo '<link id="theme" href="' . TEMPLATE_URL . '/css/themes/' . GlobalConfig_get('csstype', 'type-e') . '/' . GlobalConfig_get('csstheme', 'theme-dark') . '.min.css" rel="stylesheet">';
	}
	?>
</head>
<?php
/*
 * navbar-fixed
 * boxed-layout
 */
$oUser = login_logout::getLoginData();
$classLayout = (in_array($oUser->status, array('admin', 'operator'))) ? '' : ' boxed-layout';
$__signonOrigin = (int) ($_SESSION['signon_origin_id'] ?? 0);
$__inSignon = ($__signonOrigin > 0 && (int) login_logout::getAdminId() !== $__signonOrigin);
?>

<?php if ($__inSignon) { ?>
	<style>
		#container.signon-mode #navbar {
			border-top: 4px solid #e67e22;
		}
		.signon-back {
			background: #e67e22;
			color: #fff !important;
			font-weight: 600;
		}
		@media (max-width:1200px) {
			.signon-back small {
				display: none;
			}
		}
	</style>
<?php } ?>
<body>
	<div id="container" class="effect aside-float aside-bright mainnav-lg <?php echo $classLayout; ?><?php echo $__inSignon ? ' signon-mode' : ''; ?>">
		<header id="navbar">
			<div id="navbar-container" class="boxed">
				<div class="navbar-header">
					<?php
					$aGetLogoHeader =  GlobalConfig_get('adminlogoheader', '', 'upload');
					if (!empty($aGetLogoHeader)) {
					?>
						<a href="<?php echo URL_WEB_ROOT; ?>" class="navbar-brand" target="_blank">
							<div class="text-center"><img src="<?php echo htmlspecialchars($aGetLogoHeader, ENT_QUOTES, "UTF-8") ?>" style=" width:100%;max-width: 150px;"></div>
						</a>
					<?php } else { ?>
						<a href="<?php echo URL_WEB_ROOT; ?>" class="navbar-brand" target="_blank">
							<div class="imglogo"><i class="fa fa-globe"></i></div>
							<div class="brand-text btext"><?php echo ADMIN_PRO_TITLE_MAIN; ?></div>
						</a>
					<?php } ?>
				</div>

				<div class="navbar-content">
					<ul class="nav navbar-top-links">
						<li class="tgl-menu-btn"><a class="mainnav-toggle" href="#"><i class="demo-pli-list-view mcil"></i></a></li>
						<?php if (defined('IS_SHOW_SEARCH') && IS_SHOW_SEARCH === true) { ?>
							<li>
								<div class="custom-search-form text-left">
									<label class="btn btn-trans" for="search-input" data-toggle="collapse" data-target="#nav-searchbox"><i class="demo-pli-magnifi-glass"></i></label>
									<form action="<?php echo IS_LINK_SEARCH; ?>" method="post" class="form-inline">
										<div class="search-container collapse" id="nav-searchbox" style="padding-top: 5px;"><input name="qsearch" value="<?php echo $qsearch; ?>" id="search-input" type="text" class="form-control" placeholder="<?php echo (defined('IS_NAME_SEARCH')) ? IS_NAME_SEARCH : 'SEARCH'; ?>" style="color: #00cc77ff;"></div>
									</form>
								</div>
							</li>
						<?php } else { ?>
							<li>
								<div class="custom-search-form"> &nbsp; </div>
							</li>
						<?php } ?>
					</ul>
					<ul class="nav navbar-top-links">
						<?php
						$currentLang = $_SESSION['current']['lang'];
						if (count(@$aConfig['language']) > 1) {
							echo '<li class="language">';
							foreach ($aConfig['language'] as $k => $v) {
								$cur = ($currentLang == $k) ? 'class="active-link-lang bg-primary"' : '';
								echo '<a href="lang.php?set=' . $k . '" ' . $cur . '>' . $v . '</a>';
							}
							echo '</li>';
						}
						?>
						<?php //include TEMPLATE_PATH .'/boxtext/box_Menu.php'; 
						?>
						<?php /* ?>
         				<li id="dropdown-user" class="dropdown">
                      		<a href="#" data-toggle="dropdown" class="dropdown-toggle text-right"><span class="ic-user pull-right"><i class="demo-pli-male"></i></span></a>
                            <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right panel-default">
                                <ul class="head-list">
                                    <li><a href="#"><i class="demo-pli-male icon-lg icon-fw"></i> Profile</a></li>
                                    <li><a href="#"><span class="badge badge-danger pull-right">9</span><i class="demo-pli-mail icon-lg icon-fw"></i> Messages</a></li>
                                    <li><a href="#"><span class="label label-success pull-right">New</span><i class="demo-pli-gear icon-lg icon-fw"></i> Settings</a></li>
                                    <li><a href="#"><i class="demo-pli-computer-secure icon-lg icon-fw"></i> Lock screen</a></li>
                                    <li><a href="<?php echo URL_ADMIN; ?>/logout.php?v=<?php echo CACHE_VERSION; ?>" style="color: #f44336;"><i class="demo-pli-unlock icon-lg icon-fw" style="color: #f44336;"></i> Logout</a></li>
                                </ul>
                            </div>
                        </li>
                        <li><a href="#" class="aside-toggle"><i class="demo-pli-dot-vertical"></i></a></li>
                        <?php */ ?>

						<?php

						/*
                        ?>
                        <li id="dropdown-user" class="dropdown">
                      		<a href="#" data-toggle="dropdown" class="dropdown-toggle text-right"><span class="ic-user pull-right"><i class="demo-pli-male"></i> Online <?php echo $aMemOnline['num_rows']; ?> User <span class="badge badge-header badge-success"></span></span></a>
                            <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right panel-default">
                                <ul class="head-list">
                                <?php 
                                foreach ($aMemOnline['data'] as $k => $v) {
                                	
                                ?>
                                    <li>
                                    	<a class="media" href="#" title="<?php echo $v['actionView']; ?>">
                                                    <div class="media-left">
                                                        <i class="demo-pli-male"></i>
                                                    </div>
                                                    <div class="media-body">
                                                        <p class="mar-no text-nowrap text-main text-semibold"><?php echo $v['firstname']; ?></p>
                                                        <small style="font-size: 12px;"><?php echo api_strTimeFormat($v['onlineTime'], '%H:%M', true); ?></small><br>
                                                        <small style="font-size: 11px;"><?php echo $v['ipaddress']; ?></small>
                                                    </div>
                                                </a>
                                            
                                    </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                        <?php */ ?>
						<?php if (file_exists(PATH_ADMIN . '/AdmManual.pdf')) { ?>
							<li><a href="<?php echo URL_ADMIN . '/AdmManual.pdf'; ?>" target="_blank">คู่มือการใช้งานระบบ</a></li>
						<?php } ?>
						<?php if ($__inSignon) { ?>
							<li><a href="<?php echo _admin_buil_link('index.php?module=member&mp=admin&ac=signon&id=' . $__signonOrigin); ?>" class="signon-back"><i class="fa fa-reply"></i>&nbsp;กลับร่าง <small>(สิงร่าง: <?php echo htmlspecialchars((string) login_logout::getAdminUsername()); ?>)</small></a></li>
						<?php } ?>
						<li><a href="<?php echo URL_ADMIN . '/logout.php'; ?>"><i class="demo-pli-unlock"></i>&nbsp;Logout</a></li>
						<?php

						$isOnOff = GlobalConfig_get('onoff_user_online', 'off');
						if ($isOnOff == 'on') {
							$aMemOnline = login_logout::getAllMemberOnline('all');
						?>
							<li id="online-top"><a href="#" class="aside-toggle"><i class="demo-pli-male"></i> <span class="badge badge-header badge-mint" style="padding: 2px 0px;font-size: 16px;margin-top: 6px;right: 0px;font-weight:normal;min-width: 20px;"><?php echo $aMemOnline['num_rows']; ?></span></a></li>
						<?php } ?>
					</ul>
				</div>
			</div>
		</header>
		<div class="boxed">
			<?php include TEMPLATE_PATH . '/leftMenu.php'; ?>
			<div id="content-container">
				<?php
				$fname = PATH_UPLOAD . '/hash/hash.txt';
				if (file_exists($fname)) { ?>
					<div id="page-content">
						<div class="panel panel-danger">
							<div class="panel-heading">
								<div class="panel-control">
									<button class="btn btn-default" data-panel="minmax"><i class="demo-psi-chevron-up"></i></button>
								</div>
								<h3 class="panel-title">ไฟล์กำลังถูกดำเนินการบางอย่าง</h3>
							</div>
							<div class="collapse">
								<div class="panel-body">
									<?php $ckHash = read_txt_json($fname);
									if (!empty($ckHash['error']) || !empty($ckHash['newfile'])) {
										$sec = [
											'error' => 'ไฟล์ที่ถูกแก้ไข',
											'newfile' => 'ไฟล์ที่ถูกเพิ่มขึ้นมาใหม่'
										];
										foreach ($sec as $k => $title) {
											if (!empty($ckHash[$k])) {
												echo "<div class='bord-btm' style='margin-bottom: 10px;'>";
												echo "<p class='text-main text-bold'>{$title}</p>";
												foreach ($ckHash[$k] as $v) {
													foreach ($v as $vv) {
														echo "<p>&nbsp;&nbsp;&nbsp;{$vv}</p>";
													}
												}
												echo "</div>";
											}
										}
									} ?>
									<a href="<?php echo _admin_buil_link('index.php?module=siteconfig&mp=hashfile'); ?>" class="btn btn-primary">ไปยังหน้า Hash File</a>
								</div>
							</div>
						</div>
					</div>
				<?php } ?>
				<?php
				include $mainPage;
				$m = getMenuAdmin3();
				$a = getOtbLink();
				?>
			</div>
			<?php include TEMPLATE_PATH . '/boxtext/box_UserOnline.php'; ?>
		</div>
		<footer id="footer">
			<div class="pull-right pad-rgt"><b>จำนวนการใช้ระบบ <?php echo number_format(func_counter_txt('', 'admin')); ?> ครั้ง</b></div>
			<p class="pad-lft"><?php echo ADMIN_FOOTER_TITLE . ' | '; ?><a class="iframe" href="<?php echo base64_decode('aHR0cHM6Ly93d3cuYW9zb2Z0LmNvLnRoL3VzZXIvdmVyc2lvbi5waHA/bW9kZT1saXN0Jms9YWRtdmVyc2lvbiZ2ZXJzaW9uPQ==') . ADMIN_VERSION; ?>"><?php echo 'AdminWeb Version ' . ADMIN_VERSION ?></a></p>
		</footer>
		<button class="scroll-top btn"><i class="pci-chevron chevron-up"></i></button>
	</div>

	<script src="<?php echo TEMPLATE_URL; ?>/js/jquery.min.js"></script>
	<script src="<?php echo TEMPLATE_URL; ?>/js/bootstrap.min.js"></script>
	<script src="<?php echo TEMPLATE_URL; ?>/js/nifty.min.js"></script>
	<script src="<?php echo TEMPLATE_URL; ?>/plugins/bootbox/bootbox.min.js"></script>
	<?php
	if (_MODULE_ == '' || _MODULE_ == 'main') {
		if (is_file(TEMPLATE_PATH . '/jsao/dashboard.php')) {
			include(TEMPLATE_PATH . '/jsao/dashboard.php');
		} else {
			echo 'JS Dashboard is not found!';
		}
	} else {
		if (defined('PLUGIN_INC')) {
			$jsLoad = PATH_PLUGIN . '/' . PLUGIN_INC . '/js/js.php';
			if (is_file($jsLoad)) {
				include($jsLoad);
			}
		}
		$fmodule = PATH_MODULE . '/' . _MODULE_ . '/js.php';
		$fcore = PATH_CORE . '/' . _MODULE_ . '/js.php';
		$jsfile = is_file($fmodule) ? $fmodule : $fcore;
		if (is_file($jsfile)) {
			include($jsfile);
		}
	}

	?>
	<script src="<?php echo TEMPLATE_URL; ?>/plugins/colorbox-master/jquery.colorbox-min.js"></script>
	<script src="<?php echo URL_PLUGIN; ?>/uploadfile/script.js"></script>
	<?php
	foreach ($aModuleUse as $k => $v) {
		$fmodule = PATH_MODULE . '/' . $v . '/__funcGlobal.js.php';
		$fcore = PATH_CORE . '/' . $v . '/__funcGlobal.js.php';
		$jsfile = is_file($fmodule) ? $fmodule : $fcore;
		if (is_file($jsfile)) {
			echo '<script src="doJS.php?module=' . $v . '"></script>' . "\n";
		}
	}
	?>
	<script>
		$(".iframe").colorbox({
			iframe: true,
			width: "80%",
			height: "80%"
		});
	</script>
</body>

</html>