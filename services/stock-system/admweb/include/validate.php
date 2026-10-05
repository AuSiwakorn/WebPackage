<?php
if ($_SERVER['HTTP_HOST'] != 'localhost') {
	$aFileIsDelete = array(
		PATH_ADMIN.'/include/editor/ckeditor/samples' => 'isdir',
		PATH_ADMIN.'/include/editor/ckfinder/_samples' => 'isdir',
	);
	
	$i = 0;
	$error = '';
	foreach ($aFileIsDelete as $k => $v) {
		if ($v == 'isdir') {
			if (is_dir($k)) {
				setRaiseMsg('Please delete = '.$k,_TIME_+$i,1);
				$i++;
			}
		}
	}
}
