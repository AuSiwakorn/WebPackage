<?php
$ac = REQ_get('ac', 'request', 'str', '');
$id = REQ_get('id', 'request', 'int', '');
$time = _TIME_;
if ($id == '') {
	setRaiseMsg('Cannot get id for edit.', _TIME_, 1);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listtype&keysname=" . $keysname);
	exit;
}

///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array($ac, array('edit', 'deleteicon')) && $isSecurMode == 1) {
	setRaiseMsg('Secur Mode.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listtype&keysname=" . $keysname);
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

if ($ac == 'edit') {
	$name = REQ_get('name', 'request', 'str', '-');
	Func_Addlogs("[{$keysname}] Edit ArticleTags ID {$id} "); 
	PG_updateTagsName($id, $name);
	setRaiseMsg('Database successfully Update.', _TIME_, 0);
	CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=edittags&id={$id}&keysname=" . $keysname);
	exit;
	}




$aEdit = PG_getTags($keysname, $id);
?>


<form action="" method="post" name="form1" id="form1" enctype="multipart/form-data">
	<input type="hidden" name="ac" value="edit" />

	<div id="page-head">
		<div id="page-title">
			<div class="row">
				<div class="col-md-12">
					<h2>แก้ไขข้อมูล</h2>
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
										<td align="left"><input type="text" name="name" style="width:99%;" value="<?php echo $aEdit['tags_name']; ?>" /></td>
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











	