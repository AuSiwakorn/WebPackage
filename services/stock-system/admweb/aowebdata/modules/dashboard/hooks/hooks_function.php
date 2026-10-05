<?php
function hooks_count_article()
{
	$db = DB::singleton();
	$sql = "SELECT COUNT(articles_id) as num FROM " . _DBPREFIX_ . "site_articles; ";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->f('num');
}

function hooks_getUserOnline($minute = 10)
{
	$db = DB::singleton();
	$time = _TIME_;
	$sql = "SELECT COUNT(online_id) as num FROM " . _DBPREFIX_ . "member_online WHERE onlineEndTime >= '{$time}'; ";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->f('num');
}

function hooks_count_articleAttach()
{
	$db = DB::singleton();
	$sql = "SELECT COUNT(articles_id) as num FROM " . _DBPREFIX_ . "site_articles WHERE file_attach != ''; ";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->f('num');
}

function hooks_count_articlePublish()
{
	$db = DB::singleton();
	$sql = "SELECT COUNT(articles_id) as num FROM " . _DBPREFIX_ . "site_articles WHERE status=0; ";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->f('num');
}

function hooks_count_articleHidden()
{
	$db = DB::singleton();
	$sql = "SELECT COUNT(articles_id) as num FROM " . _DBPREFIX_ . "site_articles WHERE status != '0'; ";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->f('num');
}

function hooks_count_article_images()
{
	$db = DB::singleton();
	$sql = "SELECT COUNT(file_id) as num FROM " . _DBPREFIX_ . "site_articles_img; ";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->f('num');
}

function hooks_count_article_file()
{
	$db = DB::singleton();
	$sql = "SELECT COUNT(file_id) as num FROM " . _DBPREFIX_ . "site_articles_file; ";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->f('num');
}

function hooks_count_member($status)
{
	$db = DB::singleton();
	$sql = "SELECT COUNT(user_id) as num FROM " . _DBPREFIX_ . "member_user WHERE status = '{$status}'; ";
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	return $db->f('num');
}

function hooks_articles_getAll($num = 0, $page = 0)
{
	global $lang, $aQData;
	$sql = "SELECT
				a.*,
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3
			FROM " . _DBPREFIX_ . "site_articles a
			LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
			AND c.langkeys = '{$lang}'
			WHERE a.displaytime <= '" . _TIME_ . "'
			AND  a.articles_id IS NOT NULL
			ORDER BY a.preview DESC
			";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
		$aQData[$md5Q] = $aData;
		return $aQData[$md5Q];
	}
}
