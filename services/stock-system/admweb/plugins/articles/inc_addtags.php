<?php
$keysname = REQ_get('keysname', 'request', 'str', '');
$ac = REQ_get('ac', 'request', 'str', '');
$id = REQ_get('id', 'request', 'int', '');
$time = _TIME_;

///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array($ac, array('add')) && $isSecurMode == 1) {
	setRaiseMsg('Secur Mode.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listtags&keysname=" . $keysname);
	exit;
}
///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
if (!$isOpens['isTags']) { 
	setRaiseMsg('isTags Config False Mode.', _TIME_, 0);
	CustomRedirectToUrl("index.php");
	exit;
}
///////////////////////////////////////////////////////

if ($ac == 'add') {
	$name = REQ_get('name', 'request', 'str', '-');
	$id_tag = PG_addTagsNum($keysname, $name, '');
	Func_Addlogs("[{$keysname}] Add ArticleTags ID {$id_tag} "); 
	setRaiseMsg('Database successfully Add.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listtags&keysname=" . $keysname);
	exit;
	}
?>
 

<form action="" method="post" name="form1" id="form1" enctype="multipart/form-data">
	<input type="hidden" name="ac" value="add" />

	<div id="page-head">
		<div id="page-title">
			<div class="row">
				<div class="col-md-12">
					<h2>แก้ไขข้อมูล <?php echo $tagname['typetitle']; ?></h2>
				</div>
			</div>
		</div>
	</div>

	<div id="page-content">
		<div class="row">
			<div class="col-xs-12">
				<?php echo displayRaiseMsg(); ?>
				<div class="panel">
					<div class="panel-body">
						<div class="table-responsive">
							<table class="table table-sm">
									<tr>
										<td align="right" width="140">Tags name</td>
										<td align="left"><input type="text" name="name" style="width:99%;" value="" /></td>
									</tr>
							
									<tr>
										<td align="right">&nbsp;</td>
										<td align="left"><input type="submit" class="btn btn-mint" name="Submit" value="แก้ไขข้อมูล" class="submit" /></td>
									</tr>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	</form>


	<?php
$aConfig['isTabLoad'] = true;
?>







	