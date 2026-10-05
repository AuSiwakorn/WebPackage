<?php

$setKeys 						= REQ_get('setKeys', 'get', 'str', '');
$setVal 						= REQ_get('val', 'get', 'str', '');
if ($setKeys != '') {
	_update_config_keys($setKeys, $setVal);
	echo 1;
	exit;
} else {
	echo 0;
	exit;
}
