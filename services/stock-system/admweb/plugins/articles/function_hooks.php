<?php
/*
 * Delete Article By Article ID 
 * */
/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_deleteArticles($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles WHERE articles_id = '{$id}';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	if ($db->next_record()) {
		PG_unlinkArticlesImg($db->f('icon'));
		PG_unlinkArticlesImg($db->f('icon2'));
		PG_unlinkArticlesAttach($db->f('file_attach'));
		PG_deleteAllArticlesPicture($id);
		PG_deleteAllArticlesFile($id);
		PG_deleteArticleKeyUseType($id);
	}

	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles_content WHERE articles_id = '{$id}';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	while ($db->next_record()) {
		$res = $db->allRows();
		if ($res['contentAttach'] != '') {
			PG_unlinkArticlesAttach($res['contentAttach']);
		}
		if ($res['content_icon'] != '') {
			PG_unlinkArticlesAttach($res['content_icon']);
		}
	}

	$sql = "DELETE FROM " . _DBPREFIX_ . "site_articles WHERE articles_id = '{$id}';";
	$db->query($sql, __FUNCTION__);

	$sql = "DELETE FROM " . _DBPREFIX_ . "site_articles_content WHERE articles_id = '{$id}';";
	$db->query($sql, __FUNCTION__);
	return true;
}
