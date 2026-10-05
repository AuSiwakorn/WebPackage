<div class="row">

	<div class="col-lg-12">
		<div class="row">
			<div class="col-sm-4 col-lg-4">

				<!--Sparkline Area Chart-->
				<div class="panel panel-purple panel-colorful">
					<div class="pad-all">
						<p class="text-lg text-semibold"><i class="demo-pli-data-storage icon-fw"></i> Articles Usage</p>
						<p class="mar-no">
							<span class="pull-right text-bold"><?php echo hooks_count_article(); ?> Record</span> Article All Record
						</p>
						<p class="mar-no">
							<span class="pull-right text-bold"><?php echo hooks_count_articlePublish(); ?> Record</span> Publish
						</p>
						<p class="mar-no">
							<span class="pull-right text-bold"><?php echo hooks_count_articleHidden(); ?> Record</span> Hidden
						</p>
					</div>
				</div>
			</div>
			<div class="col-sm-4 col-lg-4">

				<!--Sparkline Line Chart-->
				<div class="panel panel-info panel-colorful">
					<div class="pad-all">
						<p class="text-lg text-semibold"> <i class="fa fa-user-o icon-fw"></i> Login User</p>
						<p class="mar-no">
							<span class="pull-right text-bold"><?php echo hooks_count_member('admin'); ?> User</span> User Admin
						</p>
						<p class="mar-no">
							<span class="pull-right text-bold"><?php echo hooks_count_member('operator'); ?> User</span> User Operator
						</p>
						<p class="mar-no">
							<span class="pull-right text-bold"><?php echo hooks_count_member('member'); ?> User</span> User Member
						</p>
					</div>
				</div>
			</div>

			<div class="col-sm-4 col-lg-4">

				<!--Sparkline Line Chart-->
				<div class="panel panel-mint panel-colorful">
					<div class="pad-all">
						<p class="text-lg text-semibold"><i class="demo-pli-camera-2 icon-fw"></i> Picture & File</p>
						<p class="mar-no">
							<span class="pull-right text-bold"><?php echo hooks_count_article_images(); ?> Files</span> Images File
						</p>
						<p class="mar-no">
							<span class="pull-right text-bold"><?php echo hooks_count_article_file(); ?> Files</span> File All Type
						</p>
						<p class="mar-no">
							<span class="pull-right text-bold"><?php echo hooks_count_articleAttach(); ?> Record</span> Article Attach File
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
$oUser = login_logout::getLoginData();
if ($oUser->status == 'admin') {
?>
	<div class="row">
		<div class="col-xs-6">
			<div class="panel">
				<div class="panel-heading">
					<h3 class="panel-title">10 โพสที่เปิดดูมากสุด</h3>
				</div>

				<!--Data Table-->
				<!--===================================================-->
				<div class="panel-body">
					<div class="table-responsive">
						<table class="table table-striped">
							<thead>
								<tr>
									<th class="text-right">ID</th>
									<th>Title</th>
									<th class="text-center">User</th>
									<th class="text-center">Display Date</th>
									<th class="text-center">Preview</th>
									<th class="text-center">Keysname</th>
									<th class="text-center">Status</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$aHooksArticle = hooks_articles_getAll(10, 1);
								foreach ($aHooksArticle['data'] as $k => $v) {
									$aMember = DB_GET('member_member', ['user_id' => $v['user_id']]);
								$fullname = is_array($aMember) ? trim(htmlspecialchars($aMember['firstname']) . ' ' . htmlspecialchars($aMember['lastname'])) : '-';
								?>
									<tr>
										<td class="text-right"><?php echo $v['articles_id']; ?></td>
										<td><?php echo $v['title']; ?></td>
										<td class="text-center"><?php echo $fullname; ?></td>
										<td class="text-center"><?php echo api_strTimeFormat($v['displaytime'], "%d/%m/%Y", true); ?></td>
										<td class="text-center"><?php echo $v['preview']; ?></td>
										<td class="text-center"><?php echo $v['keysname']; ?></td>
										<td class="text-center">
											<?php if ($v['status'] != 0) { ?>
												<div class="label label-table label-dark">Hidden</div>
											<?php } else { ?>
												<div class="label label-table label-success">Publish</div>
											<?php } ?>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
				<!--===================================================-->
				<!--End Data Table-->

			</div>
		</div>
		<div class="col-lg-6">
			<div class="panel">
				<div class="panel-body">
					<iframe src="https://www.aosoft.co.th/user/webservice.php?mode=list&k=article" width="100%" height="437" frameborder="0" name="Articles Box"></iframe>
					<hr class="new-section-xs">
				</div>
			</div>
		</div>
	</div>
<?php } ?>