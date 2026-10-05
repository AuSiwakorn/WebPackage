<?php 
if ($isOpens['isExtra1']) {
	if ($keysname == 'announcement') {
	?>
				<div class="panel panel-danger">
					<div class="panel-heading"><h3 class="panel-title">PERMISSION</h3></div>
						<div class="panel-body">
				 			<div class="row">
				 				<label class="text-left">Select Permission</label>
				 				<div class="col-lg-12">
				 					<div id="jstree" class="demo" ></div>
	                        		<input type="hidden" class="form-control uidlist" name="uidlist" >
				 				</div>
				 				<div class="col-lg-12">
				 					<hr>
									<button class="btn btn-danger" type="button" onclick="clickAllSelect();">Select All</button>
									<button class="btn btn-info" type="button" onclick="clickResetAllSelect();">Reset</button>
				 				</div>
				 			</div>
				 		</div>
			 	</div>
<?php } else { ?>
	<div class="form-group">
		<label class="col-sm-12 control-label text-left" ><?php echo @$tagname['isExtra1']; ?></label>
		<div class="col-sm-12">
		<input type="text" name="extra1" class="form-control" placeholder="<?php echo @$ex['isExtra1']; ?>" value="<?php echo @$aArticleRows['extra1']; ?>"/>
		</div>
	</div>
<?php }} ?>