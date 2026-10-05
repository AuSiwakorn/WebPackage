<?php
$page   = REQ_get('page', 'get', 'int', '0');  
$ac     = REQ_get('ac', 'request', 'str');
$id     = REQ_get('id', 'get', 'int', '');
$keysname = REQ_get('keysname', 'get', 'str', '');
$time = _TIME_;

///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
///////////////////////////////////////////////////////
$isSecurMode = GlobalConfig_get('isSecurMode');
if (in_array($ac, array('deletetype')) && $isSecurMode == 1) {
  setRaiseMsg('Secur Mode.', _TIME_, 0);
  CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listtype&keysname=" . $keysname);
  exit;
}
///////////////////////////////////////////////////////
/////////////////// isSecurMode /////////////////////
if (!$isOpens['isTags']) { 
	setRaiseMsg('isTags Config False Mode.', _TIME_, 1);
	CustomRedirectToUrl("index.php", true);
	exit;
}
///////////////////////////////////////////////////////

if ($ac == 'deletetype') {
  $aIDList = $_POST['aIDList'];
  if (count($aIDList) > 0) {
    foreach ($aIDList as $k => $v) {
      PG_deleteTags($v);
      Func_Addlogs("[{$keysname}] Delete ArticleTags ID {$v} "); 
    }

    setRaiseMsg('Delete data is successfully.', _TIME_, 0);
    CustomRedirectToUrl("index.php?module=" . $module . "&mp=" . $mp . "&inc=listtags&keysname=" . $keysname);
    exit;
  }
} else {
  $aData = PG_getAllTags($keysname, 50, $page);
}
$urladdtags = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=addtags&keysname=' . $keysname);


?>
<form id="form1" name="form1" method="post" action="" onsubmit="return confirmClickAction();">
  <input type="hidden" name="ac" value="deletetype" />

  <div id="page-head">
		<div id="page-title">
			<div class="row">
				<div class="col-md-6">
					<h1 class="page-header text-overflow"><?php //echo $tagname['typetitle']; ?></h1>
				</div>
				<div class="col-md-6 text-right">
					<a href="<?php echo $urladdtags; ?>">
            <button type="button" class="btn btn-mint"> <i class="fa fa-plus-circle" style="font-size: 12px;"></i> Add </button>
					</a>
						<button type="submit" name="Submit" id="demo-btn-addrow" class="btn btn-danger" onclick="return confirm('Delete this selected ?');"> <i class="fa fa-times-circle" style="font-size: 12px;"></i> ลบรายการที่เลือก </button>
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
							<table class="table table-striped table-vcenter">
              <thead>
									<tr>
										<th width="70">ID</th>
										<th>Name</th>
										<th width="120" class="text-center">จำนวนบทความ</th>
										<th width="70" class="text-center">ลบ</th>
									</tr>
								</thead>
                <tbody>
                  <?php
      if ($aData['num_rows'] > 0) {
        foreach ($aData['data'] as $k => $v) {
          $urledit = _admin_buil_link('index.php?module=' . $module . '&mp=' . $mp . '&inc=edittags&id=' . $v['tags_id'] . '&keysname=' . $keysname);
      ?>
          <tr>
            <td><?php echo $v['tags_id']; ?></td>
            <td><a href="<?php echo $urledit; ?>"><?php echo ($v['tags_name']) ? $v['tags_name'] : '---'; ?></a></td>
            <td class="text-center"><?php echo $v['articles_tags_num']; ?></td>
            <td class="text-center"><input type="checkbox" name="aIDList[]" value="<?php echo $v['tags_id']; ?>" /></td>
          </tr>
      <?php
        }
      }
      ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
</div>

</form>