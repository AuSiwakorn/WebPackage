<nav id="mainnav-container">
	<div id="mainnav">
		<?php
		//$keysname = @$_REQUEST['keysname'];
		//$keyshead  = @$_REQUEST['keyshead'];

		$aMenuAdmin = getMenuAdmin3();
		$oUser = login_logout::getLoginData();
		$oUsername = login_logout::getAdminUsername();
		login_logout::updateLoginData();
		$userPic = Func_Pic_Corp($oUser->picture, TEMPLATE_URL . '/img/profile-photos/1.png', 'w=150&h=150');

		?>
		<div id="mainnav-menu-wrap">
			<div class="nano">
				<div class="nano-content">
					<div id="mainnav-profile" class="mainnav-profile">
						<div class="profile-wrap text-center">
							<div class="pad-btm">
								<img class="img-circle img-md" src="<?php echo $userPic; ?>?time=<?php echo _TIME_; ?>" alt="Profile Picture">
							</div>
							<a href="#profile-nav" class="box-block" data-toggle="collapse" aria-expanded="false">
								<span class="pull-right dropdown-toggle">
									<i class="dropdown-caret"></i>
								</span>
								<p class="mnp-name"><?php echo $oUser->name; ?></p>
								<span class="mnp-desc"><?php echo ucfirst(($oUser->status == 'member') ? 'Distributor' : $oUser->status); ?></span>
							</a>
						</div>
						<div id="profile-nav" class="list-group bg-trans collapse" aria-expanded="false" style="height: 0px;">
							<a href="<?php echo _admin_buil_link("index.php?module=member&mp=profile", $isNotOTB = true); ?>" class="list-group-item" style="font-size: 13px;">
								<i class="demo-pli-male"></i>&nbsp;&nbsp;&nbsp;My Profile
							</a>
							<!-- 2 Factor Authentication -->
							<a href="<?php echo _admin_buil_link("index.php?module=member&mp=twofa"); ?>" class="list-group-item" style="font-size: 13px;">
								<i class="demo-pli-lock-user"></i>&nbsp;&nbsp;&nbsp;2FA Authentication
							</a>
							<a href="<?php echo _admin_buil_link("index.php?module=member&mp=changePass", $isNotOTB = true); ?>" class="list-group-item" style="font-size: 13px;">
								<i class="demo-pli-gear"></i>&nbsp;&nbsp;&nbsp;Change Password
							</a>
							<a href="<?php echo URL_ADMIN . '/logout.php'; ?>" class="list-group-item" style="font-size: 13px;">
								<i class="demo-pli-unlock"></i>&nbsp;&nbsp;&nbsp;Logout
							</a>
						</div>
					</div>
					<div id="mainnav-shortcut" class="hidden">
						<ul class="list-unstyled shortcut-wrap">
							<li class="col-xs-3" data-content="My Profile">
								<a class="shortcut-grid" href="#">
									<div class="icon-wrap icon-wrap-sm icon-circle bg-mint"><i class="demo-pli-male"></i></div>
								</a>
							</li>
							<li class="col-xs-3" data-content="Messages">
								<a class="shortcut-grid" href="#">
									<div class="icon-wrap icon-wrap-sm icon-circle bg-warning"><i class="demo-pli-speech-bubble-3"></i></div>
								</a>
							</li>
							<li class="col-xs-3" data-content="Activity">
								<a class="shortcut-grid" href="#">
									<div class="icon-wrap icon-wrap-sm icon-circle bg-success"><i class="demo-pli-thunder"></i></div>
								</a>
							</li>
							<li class="col-xs-3" data-content="Lock Screen">
								<a class="shortcut-grid" href="#">
									<div class="icon-wrap icon-wrap-sm icon-circle bg-purple"><i class="demo-pli-lock-2"></i></div>
								</a>
							</li>
						</ul>
					</div>

					<ul id="mainnav-menu" class="list-group" style="margin-bottom: 0px;padding-top: 10px;">
						<li class="<?php echo ($_SERVER['QUERY_STRING'] == '') ? 'active-link' : 'active-none'; ?>"><a href="<?php echo URL_ADMIN; ?>"><i class="demo-pli-home"></i><span class="menu-title">Dashboard</span></a></li>
						<li class="list-divider"></li>
						<?php
						$c = getOtbLink();
						foreach ($aMenuAdmin as $_kMenu => $_vMenu) {
							if (@$_vMenu['title'] != '') {
								echo '<li class="list-header">' . @$_vMenu['title'] . '</li>';
							}
							foreach ($_vMenu['subhead'] as $k => $v) {
								if (count($v) == 0) {
									continue;
								}
								if (($k == 'installation' || $k == 'transsetting') && $oUsername !== 'superadmin') {
									continue;
								}
								$hurl = (@$v['target'] == '_blank') ? @$v['link'] :  @$v['link'] . '&otb=' . $k . '|';
								$txtNum = (isset($v['num']) && $v['num'] != '') ? '<span class="label pull-right badge badge-mint">' . $v['num'] . '</span>' : '';
								if (count($v['menu']) > 0) {
									if ($c[0] == $k && $_kMenu == $module) {
										$cactive = 'active-sub';
										$in = ' in';
									} else {
										$cactive = 'active-none';
										$in = '';
									}

									echo '
									<li class="' . $cactive . '">
										<a href="#"> 
											<i class="' . $v['class'] . '"></i> 
											<span class="menu-title">' . $v['name'] . $txtNum . '</span>
											' . ($txtNum != '' ? '' : '<i class="arrow"></i>') . '
										</a>
										<ul class="collapse ' . $in . '">';
									foreach ($v['menu'] as $kk => $vv) {
										$cururl = '&otb=' . $k . '|' . $kk;
										$hurl =  (@$vv['target'] == '_blank') ? @$vv['link'] :  @$vv['link'] . $cururl;
										$txtNum2 = (isset($vv['num']) && $vv['num'] != '') ? '<span class="label pull-right badge badge-mint">' . $vv['num'] . '</span>' : '';
										$cactive = ($c[0] == $k && $c[1] == $kk && $_kMenu == $module) ? 'active-link ' : 'active-none';
										$spantext = $cactive == 'active-link ' ? 'text-bold' : '';
										$active = $cactive == 'active-link ' ? 'active' : '';
										$iconHeader = ($c[0] == $k && $c[1] == $kk && $_kMenu == $module) ? '<i class="' . @$v['class'] . '"></i>' : '<i class="fa fa-book"></i>';
										if (isset($vv['menu']) && count((array)$vv['menu']) > 0) {
											echo '<li class="' . $active . '"><a href="#"><span class="' . $spantext . '">&raquo; ' . @$vv['name'] . $txtNum2 . ' <i class="arrow"></i></span></a>';
											$in3 = (trim($cactive) == 'active-link') ? ' class="collapse in" aria-expanded="true" ' : ' class="collapse" ';
											echo '
													<!--Submenu-->
	                                                <ul ' . $in3 . '>';
											foreach ($vv['menu'] as $kkk => $vvv) {
												$cururl = '&otb=' . $k . '|' . $kk . '|' . $kkk;
												$hurl2 = @$vvv['link'] . $cururl;
												$cactive = ($c[0] == $k && $c[1] == $kk && $c[2] == $kkk && $_kMenu == $module) ? 'active-link ' : 'active-none';
												echo '<li class="' . $cactive . '"><a href="' . $hurl2 . '" class="textMenuLevel3"> - ' . $vvv['name'] . '</a></li>';
											}
											echo '</ul></li>';
										} else {
											echo '<li class="' . $cactive . '"><a href="' . $hurl . '"> &raquo; ' . @$vv['name'] . $txtNum2 . '</a></li>';
										}
									}
									echo '</ul></li>';
								} else {
									$target = @$v['target'];
									$cactive = ($c[0] == $k) ? 'active-link ' : 'active-none';
									echo '<li class="' . $cactive . '"><a href="' . $hurl . '" target="' . $target . '"> <i class="' . $v['class'] . '"></i> <span class="menu-title">' . $v['name'] . $txtNum . '</span></a></li>';
								}
							}

							/////////////////////// 12-06-2020 //////////////////////////
							/////////////////////// Parn Develop  ///////////////////////
							///////////////////////// Admin Fix Menu //////////////////
							/*
								if ($_kMenu == 'member' && $oUser->status== 'admin') {
								    $url = _admin_buil_link('index.php?module=menu&mp=list_menu&keysname=add_menu_'.$oUser->department_id, true);
								    echo '<li><a href=" '.$url.' "><i class="fa fa-plus" style="color:#FF0000;"></i> <span class="menu-title">Create Menu</span></a></li>';
								} 
								*/
							//////////////////////// User Fix Menu //////////////////////// 
							echo '<li class="list-divider"></li>';
						} ?>
					</ul>



					<?php if (in_array($oUser->status, array('admin')) && DISPLAY_MAX_UPLOAD == true) { ?>
						<div class="mainnav-widget">
							<div class="show-small">
								<a href="#" data-toggle="menu-widget" data-target="#demo-wg-server"><i class="demo-pli-monitor-2"></i></a>
							</div>

							<!-- Hide the content on collapsed navigation -->
							<div id="demo-wg-server" class="hide-small mainnav-widget-content">
								<div style="padding: 15px;">
									<ul class="list-group">
										<li class="list-header pad-no mar-ver" style="margin-top: 0px;">MAX UPLOAD</li>
										<li class="mar-btm">
											<p style="font-size: 12px;">Upload Max Filesize : 
												<span class="text-primary text-bold"><?php echo @ini_get('upload_max_filesize'); ?></span>
											</p>
										</li>
										<li class="mar-btm">
											<p style="font-size: 12px;">Post Max Size : 
												<span class="text-purple text-bold"><?php echo @ini_get('post_max_size'); ?></span>
											</p>
										</li>
										<li class="mar-btm">
											<p style="font-size: 12px;">Memory Limit : 
												<span class="text-danger text-bold"><?php echo @ini_get('memory_limit'); ?></span>
											</p>
										</li>
										<li class="mar-btm">
											<p style="font-size: 12px;">Max Input Time : 
												<span class="text-warning text-bold"><?php echo @ini_get('max_input_time'); ?></span>
											</p>
										</li>
									</ul>
								</div>
							</div>
						</div>
						<!--================================-->
						<!--End widget-->
					<?php } ?>

				</div>
			</div>
		</div>
		<!--================================-->
		<!--End menu-->

	</div>
</nav>