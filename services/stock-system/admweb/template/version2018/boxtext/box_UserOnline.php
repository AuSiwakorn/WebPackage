<?php

$isOnOff = GlobalConfig_get('onoff_user_online', 'off');
if ($isOnOff == 'on') {
	$oUser = login_logout::getLoginData();
	$aMemOnline = login_logout::getAllMemberOnline('user');
	$aGuestOnline = login_logout::getAllMemberOnline('guest');
	$_onlinetime_aside = GlobalConfig_get('useronline_count_time', 30);
?>
	<aside id="aside-container">
		<div id="aside">
			<div class="nano">
				<div class="nano-content">
					<ul class="nav nav-tabs nav-justified">
						<li class="active">
							<a href="#asd-tab-1" data-toggle="tab">
								<i class="fa fa-user icon-lg icon-fw"></i>
								<div style="font-size: 12px;">MEMBER <span class="badge badge-dark"><?php echo $aMemOnline['num_rows']; ?></span></div>
							</a>
						</li>
						<li>
							<a href="#asd-tab-2" data-toggle="tab">
								<i class="fa fa-user-o icon-lg icon-fw"></i>
								<div style="font-size: 12px;">GUEST <span class="badge badge-purple"><?php echo $aGuestOnline['num_rows']; ?></span></div>
							</a>
						</li>
					</ul>

					<div class="tab-content">
						<div class="tab-pane fade in active" id="asd-tab-1">
							<p class="pad-all text-main text-sm text-uppercase text-bold">
								<span class="pull-right badge badge-dark" style="padding: 4px 7px;font-weight:normal;"><?php echo $aMemOnline['num_rows']; ?></span> Member Online <?php echo $_onlinetime_aside; ?> นาทีที่ผ่านมา
							</p>
							<div class="list-group bg-trans">
								<?php
								if ($aMemOnline['num_rows'] > 0) {
									foreach ($aMemOnline['data'] as $k => $v) {
										if ($v['user_id'] == 1 && $oUser->user_id != 1) {
											continue;
										}
										$_ex = explode('|', $v['actionView']);
										$_ex[1] = (isset($_ex[1]) && $_ex[1] != '') ? $_ex[1] : 'Dashboard';
										$pageName = (isset($_ex[3]) && $_ex[3] != '') ? $_ex[3] : 'MP : ' . $_ex[1];
										$userPicture = Func_Pic_Corp($v['picture'], TEMPLATE_URL . '/img/profile-photos/1.png', 'w=150&h=150');
										$def = _TIME_ - $v['onlineTime'];
										$natee = ($def / 60);
										$natee = number_format($natee, 2);
								?>
										<div class="list-group-item" title="<?php echo $_ex[2]; ?>">
											<div class="media-left pos-rel">
												<img class="img-circle img-xs" src="<?php echo $userPicture; ?>" alt="Profile Picture"> <i class="badge badge-success badge-stat badge-icon pull-left"></i>
											</div>
											<div class="media-body side-left-online" relUser_id="<?php echo $v['user_id']; ?>">
												<p class="mar-no text-main" style="font-size: 16px;"><?php echo $v['firstname']; ?> <i class="fa fa-sign-out"></i></p>
												<small class="text-muteds" style="font-size: 14px;">Online <?php echo displayTimeSize($v['onlineTime']); ?></small>
												<span class="pull-right" style="font-size: 14px;"><?php echo $natee; ?> นาที</span>
												<div style="font-size: 12px;" class="text-success"><?php echo $pageName; ?></div>
											</div>
											<div class="bord-btm" style="margin-top: 10px;"></div>
										</div>
								<?php }
								} ?>
							</div>
						</div>
						<div class="tab-pane" id="asd-tab-2">
							<p class="pad-all text-main text-sm text-uppercase text-bold">
								<span class="pull-right badge badge-purple" style="padding: 4px 7px;font-weight:normal;"><?php echo $aGuestOnline['num_rows']; ?></span> Guest Online <?php echo $_onlinetime_aside; ?> นาทีที่ผ่านมา
							</p>
							<div class="list-group bg-trans">
								<?php
								if ($aGuestOnline['num_rows'] > 0) {
									foreach ($aGuestOnline['data'] as $k => $v) {
										$pageName = $v['viewurl'];
										$userPicture = Func_Pic_Corp($v['picture'], TEMPLATE_URL . '/img/profile-photos/1.png', 'w=150&h=150');
										$def = _TIME_ - $v['onlineTime'];
										$natee = ($def / 60);
										$natee = number_format($natee, 2);
								?>
										<div class="list-group-item" title="<?php echo $v['ipaddress']; ?>">
											<div class="media-left pos-rel">
												<img class="img-circle img-xs" src="<?php echo TEMPLATE_URL; ?>/img/profile-photos/1.png" alt="Profile Picture"> <i class="badge badge-success badge-stat badge-icon pull-left"></i>
											</div>
											<div class="media-body">
												<p class="mar-no text-main" style="font-size: 16px;"><?php echo $v['ipaddress']; ?></p>
												<small class="text-muteds" style="font-size: 14px;">Online <?php echo displayTimeSize($v['onlineTime']); ?></small>
												<span class="pull-right" style="font-size: 14px;"><?php echo $natee; ?> นาที</span>
												<div style="font-size: 12px;" class="text-success"><?php echo $pageName; ?></div>
											</div>
											<div class="bord-btm" style="margin-top: 10px;"></div>
										</div>
								<?php }
								} ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</aside>
<?php } ?>