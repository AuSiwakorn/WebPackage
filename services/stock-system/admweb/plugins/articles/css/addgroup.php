<?php
/* ################# SELECT LANGEUAGE ############################ */
$i = 0;
foreach ($aConfig['language'] as $k => $v) {
	$arLang[$k] = $i;
	$i++;
}
$se_lang = (DEFAULT_LANGEUAGE != '') ? $arLang[DEFAULT_LANGEUAGE] : 0;
?>
<style type="text/css">
	select#parent_id {     
		font-size: 14px;
	    padding: 2px 8px;
	    background: #f8f7fb;
	    border-radius: 3px; 
    }
    img#img-group{    
    	background: #a1a3a0 !important;
    	border: 1px solid #a1a3a0;
    	border-radius: 50%;
    }
    .tableclass table#other tr td { border-bottom: 0px; }
	.isGroupImg {
		margin: 30px 5px;
	    background: #fff;
	    padding: 10px;
	    border-radius: 5px;
	    font-size: 15px; 
    }
    .extraOption{ width: 100%;padding: 2px;margin:2px 0px;border: 0px dashed #999;float: left; }
</style>
