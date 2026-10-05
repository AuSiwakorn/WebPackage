<?php
/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
include('api/api.keys.php');
/* ========================= */
include('api/api.articles.php');
/* ========================= */
include('api/api.tags.php');
/* ========================= */
include('api/api.group.php');
include('api/api.subpost.php');
/* ========================= */
include('api/api.picture.php');
include('api/api.file.php');
/* ========================= */
include('api/api.pages.php');
/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function plugin_articleEdit($module, $mp, $id, $keysname)
{
	if (is_Admin()) {
		echo '<hr style="border:0px; border-bottom: 1px dashed #ccc;" />';
		echo '<style type="text/css">.editarticle {padding:2px 8px; color:#FFF; background-color:#000; text-decoration:none;}</style>';
		echo '<a href="'.URL_ADMIN.'/index.php?module='.$module.'&mp='.$mp.'&inc=edit&id='.$id.'&keysname='.$keysname.'" target="_blank" class="editarticle"><span style="color:#FFF;">Edit Article</span></a>';
	}
}

function plugin_articlePageEdit($module, $mp, $keysname)
{
	if (is_Admin()) {
		echo '<hr style="border:0px; border-bottom: 1px dashed #ccc;" />';
		echo '<style type="text/css">.editarticle {padding:2px 8px; color:#FFF; background-color:#000; text-decoration:none;}</style>';
		echo '<a href="'.URL_ADMIN.'/index.php?module='.$module.'&mp='.$mp.'&keysname='.$keysname.'" target="_blank" class="editarticle"><span style="color:#FFF;">Edit Page</span></a>';
	}
}
?>