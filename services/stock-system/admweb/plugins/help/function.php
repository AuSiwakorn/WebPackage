<?php
function save_help_content($pathHtml, $html)
{
	$fp = fopen($pathHtml, 'w');
	fwrite($fp, $html);
	fclose($fp);
}
?>