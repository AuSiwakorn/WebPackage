<?php
if (@$__useResize != true) {
	include PATH_PLUGIN . '/writelicense/resize.php';
}

function setDefaultINT($int)
{
	return (isset($int) && $int > 0 && $int != '') ? $int : 0;
}

//include ('function_menu.php');
include('function_uploads.php');
include('function_group.php');
include('function_keysAndTags.php');
include('function_file.php');

////////// Hooks ///////////
include('function_hooks.php');

function PG_unlinkFile($filename)
{
	$upFile = new \UpFile(PATH_UPLOAD);
	$upFile->deleteFile($filename);
}

function PG_updateArticlesContentAttach($id, $k, $nAttc)
{
	$sql = "UPDATE " . _DBPREFIX_ . "site_articles_content SET  `contentAttach` = '{$nAttc}' WHERE articles_id ='{$id}' AND langkeys = '{$k}'; ";
	$db = DB::singleton();
	$ok = $db->query($sql, __FUNCTION__);
}

function PG_updateArticlesContentIcon($id, $k, $nAttc)
{
	$sql = "UPDATE " . _DBPREFIX_ . "site_articles_content SET  `content_icon` = '{$nAttc}' WHERE articles_id ='{$id}' AND langkeys = '{$k}'; ";
	$db = DB::singleton();
	$ok = $db->query($sql, __FUNCTION__);
}

function PG_validateArticleNumInTags($keysname = '', $tagsname = '')
{
	if ($keysname != '') {
		echo $sql = "SELECT articles_id FROM " . _DBPREFIX_ . "site_articles WHERE keysname = '{$keysname}' AND keywords LIKE '%{$tagsname}%'; ";
	} else {
		$sql = "SELECT articles_id FROM " . _DBPREFIX_ . "site_articles WHERE keywords LIKE '%{$tagsname}%'; ";
	}

	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return $db->num_rows();
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_updateIconAttachByid($id, $iconname = '', $iconname2 = '', $nameAttc = '')
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	if ($id && $id != '') {
		$sql = array();
		if ($iconname != '') {
			$sql[] = "UPDATE " . _DBPREFIX_ . "site_articles SET `icon` = '{$iconname}' WHERE articles_id = '{$id}'; ";
		}

		if ($iconname2 != '') {
			$sql[] = "UPDATE " . _DBPREFIX_ . "site_articles SET `icon2` = '{$iconname2}' WHERE articles_id = '{$id}'; ";
		}

		if ($nameAttc != '') {
			$sql[] = "UPDATE " . _DBPREFIX_ . "site_articles SET `file_attach` = '{$nameAttc}' WHERE articles_id = '{$id}'; ";
		}

		$db = DB::singleton();
		if (count($sql) > 0) {
			$time = _TIME_;
			$sql[] = "UPDATE " . _DBPREFIX_ . "site_articles SET `add_time` = '{$time}' WHERE articles_id = '{$id}'; ";

			foreach ($sql as $k => $v) {
				$db->query($v);
			}
		}
		return $id;
	}
	return false;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_addNewArticleGroup($d, $iconname, $iconname2, $nameAttc, $keysname, $sorttime = 0)
{
	$d['group'] = (@$d['group'] == '') ? 0 : $d['group'];
	$d['user_id'] = (@$d['user_id'] == '') ? 0 : $d['user_id'];
	$d['status'] = (@$d['status'] == '') ? 0 : $d['status'];
	$d['end_time'] = (@$d['end_time'] == '') ? 0 : $d['end_time'];
	$d['displaytime'] = (@$d['displaytime'] == '') ? 0 : $d['displaytime'];
	$sql = "INSERT INTO " . _DBPREFIX_ . "site_articles (
					`articles_id` ,
					`group_id` ,
					`user_id` ,
					`keysname`,
					`status` ,
					`add_time`,
					`end_time`,
					`sorttime`,
					`preview`,
					`displaytime`,
					`icon`,
					`icon2`,
					`file_attach`,
					`checkOption`,
					`checkBoxOption`,
					`extra1`,
					`extra2`,
					`extra3`,
                    `extra4`,
                    `extra5`,
                    `extra6`,
                    `extra7`,
                    `extra8`,
                    `extra9`,
                    `extra10`
				) VALUES (
					NULL 
					, '{$d['group']}'
					, '{$d['user_id']}'
					, '{$keysname}'
					, '{$d['status']}'
					, '" . _TIME_ . "'
					, {$d['end_time']}
					, '{$sorttime}'
					, '0'
					, '{$d['displaytime']}'
					, '{$iconname}'
					, '{$iconname2}'
					, '{$nameAttc}'
					, '{$d['checkOption']}'
					, '{$d['checkBoxOption']}'
					, '{$d['extra1']}'
					, '{$d['extra2']}'
					, '{$d['extra3']}'
                    , '{$d['extra4']}'
                    , '{$d['extra5']}'
                    , '{$d['extra6']}'
                    , '{$d['extra7']}'
                    , '{$d['extra8']}'
                    , '{$d['extra9']}'
                    , '{$d['extra10']}'
					); ";
	$db = DB::singleton();
	$ok = $db->query($sql, __FUNCTION__);
	return ($ok) ? $db->getInsertID() : false;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_changeArticlesStatus($id, $changTo = '0')
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	$sql = "
	UPDATE " . _DBPREFIX_ . "site_articles 
	SET `status` = '{$changTo}'
	WHERE articles_id = '{$id}';
	";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return true;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_updateArticles($d)
{
	$d['group'] = setDefaultINT($d['group']);
	$d['user_id'] = setDefaultINT($d['user_id']);
	$d['status'] = setDefaultINT($d['status']);
	$d['end_time'] = isset($d['end_time']) ? setDefaultINT($d['end_time']) : 0;
	$d['displaytime'] = setDefaultINT($d['displaytime']);
	$sql = "
	UPDATE " . _DBPREFIX_ . "site_articles
	SET	`group_id` 		= '{$d['group']}',
			`user_id` 			= '{$d['user_id']}',
			`status` 				= '{$d['status']}',
			`end_time` 		= '{$d['end_time']}',
			`displaytime` 	= '{$d['displaytime']}',
			`checkOption` 	= '{$d['checkOption']}',
			`checkBoxOption` 	= '{$d['checkBoxOption']}',
			`extra1` 			= '{$d['extra1']}',
			`extra2` 			= '{$d['extra2']}',
			`extra3`        		= '{$d['extra3']}',
            `extra4`        		= '{$d['extra4']}',
            `extra5`        		= '{$d['extra5']}',
            `extra6`        		= '{$d['extra6']}',
            `extra7`        		= '{$d['extra7']}',
            `extra8`        		= '{$d['extra8']}',
            `extra9`        		= '{$d['extra9']}',
            `extra10`        	= '{$d['extra10']}'
	WHERE articles_id = '{$d['id']}';
	";
	$db = DB::singleton();
	return $db->query($sql, __FUNCTION__);
}

function PG_updateArticles_page($keysname, $d)
{
	$sql = "
	UPDATE " . _DBPREFIX_ . "site_articles
	SET	 `group_id` 		= '{$d['group']}',
			`user_id` 			= '{$d['user_id']}',
			`status` 				= '{$d['status']}',
			`add_time`			= '" . _TIME_ . "',
			`displaytime` 	= '{$d['displaytime']}',
			`checkOption`   = '{$d['checkOption']}',
			`checkBoxOption`   = '{$d['checkBoxOption']}',
			`extra1` 			= '{$d['extra1']}',
			`extra2` 			= '{$d['extra2']}',
			`extra3`         	= '{$d['extra3']}',
            `extra4`        		= '{$d['extra4']}',
            `extra5`        		= '{$d['extra5']}',
            `extra6`        		= '{$d['extra6']}',
            `extra7`        		= '{$d['extra7']}',
            `extra8`        		= '{$d['extra8']}',
            `extra9`        		= '{$d['extra9']}',
            `extra10`        	= '{$d['extra10']}'
	WHERE keysname = '{$keysname}';
	";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return true;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_setArticlesSort($id, $n = 0)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "
		UPDATE " . _DBPREFIX_ . "site_articles
		SET `sorttime` = '{$n}'
		WHERE articles_id = '{$id}';
	";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return true;
}

function PG_unlinkArticlesImg($filename)
{
	PG_unlinkArticlesAttach($filename);
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_unlinkArticlesAttach($filename)
{
	$upFile = new \UpFile(PATH_UPLOAD);
	$upFile->deleteFile($filename);
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_getArticlesByID($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles WHERE articles_id = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	if ($db->num_rows() > 0) {
		$aRows = $db->allRows();
		$aData = PG_getArticlesContentByArticlesID($id);
		$aPicture = PG_getAllPictureByArticlesID($id);
		$aFiles = PG_getAllFilesByArticlesID($id);
		$aRows['content'] = (isset($aData['data']) && count($aData['data']) > 0) ? $aData['data'] : '';
		$aRows['picture'] = (isset($aPicture['data']) && count($aPicture['data']) > 0) ? $aPicture['data'] : '';
		$aRows['files'] = (isset($aFiles['data']) && count($aFiles['data']) > 0) ? $aFiles['data'] : '';
	} else {
		return false;
	}
	return $aRows;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_getArticles_page($keysname)
{
	$sql = "SELECT * FROM " . _DBPREFIX_ . "site_articles WHERE keysname = '{$keysname}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	if ($db->num_rows() > 0) {
		$aRows = $db->allRows();
		$aData = PG_getArticlesContentByArticlesID($aRows['articles_id']);
		$aPicture = PG_getAllPictureByArticlesID($aRows['articles_id']);
		$aRows['content'] = (isset($aData['data']) && count($aData['data']) > 0) ? $aData['data'] : '';
		$aRows['picture'] = (isset($aPicture['data']) && count($aPicture['data']) > 0) ? $aPicture['data'] : '';
	} else {
		return false;
	}
	return $aRows;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_getArticlesContentByArticlesID($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT *  FROM " . _DBPREFIX_ . "site_articles_content 
      WHERE articles_id = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aData = array();
	while ($db->next_record()) {
		$aRow = $db->allRows();
		$aData['data'][$aRow['langkeys']] = $aRow;
	}
	return (isset($aData['data']) && count($aData['data']) > 0) ? $aData : array();
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function pg_getAllArticles($keysname, $num = 0, $page = 0, $orderBy = 'ASC')
{
	global $lang;
	$num = _input_validate_int($num, true);
	$page = _input_validate_int($page, true);
	$sql = "SELECT a.*, c.content_id, c.langkeys, c.title, c.shortMessage, c.content, c.content2, c.author,
				   c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3
			FROM " . _DBPREFIX_ . "site_articles a
			LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
			AND c.langkeys = '{$lang}'
			WHERE a.keysname = '{$keysname}'
			AND  a.articles_id IS NOT NULL
			ORDER BY a.sorttime {$orderBy} , a.displaytime DESC , a.articles_id DESC ";
	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
	return $aData;
}

function pg_getAllArticles_search($keysname, $keysword = '', $num = 0, $page = 0, $orderBy = 'ASC')
{
	global $lang;
	$num = _input_validate_int($num, true);
	$page = _input_validate_int($page, true);
	$sql = "SELECT 
				a.*, 
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3
			FROM " . _DBPREFIX_ . "site_articles a
			LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
			AND c.langkeys = '{$lang}'
			WHERE a.keysname = '{$keysname}'
			AND (a.articles_id = '{$keysword}' OR c.title LIKE '%{$keysword}%' OR c.shortMessage LIKE '{$keysword}')
			AND  a.articles_id IS NOT NULL
			ORDER BY a.sorttime {$orderBy} , a.displaytime DESC , a.articles_id DESC
			";
	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
	return $aData;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function pg_getAllArticlesByGroup($gid, $keysname, $num = 0, $page = 0, $orderBy = 'ASC')
{
	global $lang;
	////////////// Validate /////////////
	$gid = _input_validate_int($gid, true);
	$num = _input_validate_int($num, true);
	$page = _input_validate_int($page, true);
	/////////// End Validate ///////////

	$sql = "SELECT 
				a.*, 
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3
			FROM " . _DBPREFIX_ . "site_articles a
			LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
			AND c.langkeys = '{$lang}'
			WHERE a.keysname = '{$keysname}'
			AND a.group_id = '{$gid}'
			AND  a.articles_id IS NOT NULL
			ORDER BY a.sorttime {$orderBy} , a.displaytime DESC , a.articles_id DESC
			";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
	return $aData;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function pg_getAllArticlesByGroupYear($year, $gid, $keysname, $num = 0, $page = 0, $orderBy = 'ASC')
{
	global $lang;
	////////////// Validate /////////////
	$gid = _input_validate_int($gid, true);
	$num = _input_validate_int($num, true);
	$page = _input_validate_int($page, true);
	/////////// End Validate ///////////

	$sql = "SELECT
				a.*, FROM_UNIXTIME( a.displaytime,  '%Y' ) as checkY, 
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3
			FROM " . _DBPREFIX_ . "site_articles a
			LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
			AND c.langkeys = '{$lang}'
			WHERE a.keysname = '{$keysname}'
			AND a.group_id = '{$gid}'
			AND FROM_UNIXTIME( a.displaytime,  '%Y' ) = '{$year}'
			AND  a.articles_id IS NOT NULL
			ORDER BY a.sorttime {$orderBy} , a.displaytime DESC , a.articles_id DESC
			";
	$db = DB::singleton();
	$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
	return $aData;
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_updateArticlesContent($d)
{
	$db = DB::singleton();
	foreach ($d as $kLang => $v) {
		$articles_id = $v['articles_id'];
		$content_id = $v['content_id'];
		$title = $v['title'];
		$author = $v['author'];
		$slug = $v['slug'];
		$shortMessage = $v['shortMessage'];
		$keywords = $v['keywords'];
		$content = $v['content'];
		$content = str_replace("'", "&#39;", $content);

		$content2 = $v['content2'];
		$content2 = str_replace("'", "&#39;", $content2);

		$content3 = $v['content3'];
		$content3 = str_replace("'", "&#39;", $content3);

		$content4 = $v['content4'];
		$content4 = str_replace("'", "&#39;", $content4);

		$content_extra1 = $v['content_extra1'];
		$content_extra2 = $v['content_extra2'];
		$content_extra3 = $v['content_extra3'];
		if (PG_getCheckArticleGroupContentByID($articles_id, $kLang) == true) {
			$sql = "UPDATE " . _DBPREFIX_ . "site_articles_content 
						SET  `title` = '{$title}',
								`author` = '{$author}',
								`shortMessage` = '{$shortMessage}',
								`keywords` = '{$keywords}',
								`content` = '{$content}' ,
								`content2` = '{$content2}' ,
								`content3` = '{$content3}' ,
								`content4` = '{$content4}' ,
								`content_extra1` = '{$content_extra1}',
								`content_extra2` = '{$content_extra2}',
								`content_extra3` = '{$content_extra3}',
								`slug` = '{$slug}'
						WHERE articles_id ='{$articles_id}'
						AND langkeys = '{$kLang}'; 
					";
			$db->query($sql, __FUNCTION__);
		} else {
			$aAdd = array();
			$aAdd[$kLang]['articles_id'] = $v['articles_id'];
			$aAdd[$kLang]['title'] = $v['title'];
			$aAdd[$kLang]['author'] = $v['author'];
			$aAdd[$kLang]['shortMessage'] = $v['shortMessage'];
			$aAdd[$kLang]['keywords'] = $v['keywords'];
			$aAdd[$kLang]['content'] = $v['content'];
			$aAdd[$kLang]['content2'] = $v['content2'];
			$aAdd[$kLang]['content3'] = $v['content3'];
			$aAdd[$kLang]['content4'] = $v['content4'];
			$aAdd[$kLang]['content_extra1'] = $v['content_extra1'];
			$aAdd[$kLang]['content_extra2'] = $v['content_extra2'];
			$aAdd[$kLang]['content_extra3'] = $v['content_extra3'];
			$aAdd[$kLang]['slug'] = $v['slug'];
			$id = PG_addArticleGroupContent($aAdd);
			exit;
			unset($aAdd);
		}
	}
}
/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_updateArticlesContent_page($d)
{
	$db = DB::singleton();
	foreach ($d as $kLang => $v) {
		$articles_id = $v['articles_id'];
		$content_id = $v['content_id'];
		$title = $v['title'];
		$author = $v['author'];
		$shortMessage = $v['shortMessage'];
		$content = $v['content'];
		$content = str_replace("'", "&#39;", $content);

		$content2 = $v['content2'];
		$content2 = str_replace("'", "&#39;", $content2);

		$content3 = $v['content3'];
		$content3 = str_replace("'", "&#39;", $content3);

		$content4 = $v['content4'];
		$content4 = str_replace("'", "&#39;", $content4);

		$content4 = $v['content4'];
		$content4 = ($content4 != '') ? str_replace("'", "&#39;", $content4) : '';

		$content_extra1 = $v['content_extra1'];
		$content_extra1 = ($content_extra1 != '') ? str_replace("'", "&#39;", $content_extra1) : '';

		$content_extra2 = $v['content_extra2'];
		$content_extra2 = ($content_extra2 != '') ? str_replace("'", "&#39;", $content_extra2) : '';

		$content_extra3 = $v['content_extra3'];
		$content_extra3 = str_replace("'", "&#39;", $content_extra3);

		if (PG_getCheckArticleGroupContentByID($articles_id, $kLang) == true) {
			$sql = "UPDATE " . _DBPREFIX_ . "site_articles_content 
						SET  `title` = '{$title}',
								`author` = '{$author}',
								`shortMessage` = '{$shortMessage}',
								`content` = '{$content}' ,
								`content2` = '{$content2}' ,
								`content3` = '{$content3}' ,
								`content4` = '{$content4}' ,
								`content_extra1` = '{$content_extra1}',
								`content_extra2` = '{$content_extra2}',
								`content_extra3` = '{$content_extra3}'
						WHERE articles_id ='{$articles_id}'
						AND langkeys = '{$kLang}';
					";
			$db->query($sql, __FUNCTION__);
		} else {
			$aAdd = array();
			$aAdd[$kLang]['articles_id'] = $v['articles_id'];
			$aAdd[$kLang]['title'] = $v['title'];
			$aAdd[$kLang]['author'] = $v['author'];
			$aAdd[$kLang]['shortMessage'] = $v['shortMessage'];
			$aAdd[$kLang]['content'] = $v['content'];
			$aAdd[$kLang]['content2'] = $v['content2'];
			$aAdd[$kLang]['content3'] = $v['content3'];
			$aAdd[$kLang]['content4'] = $v['content4'];
			$aAdd[$kLang]['content_extra1'] = $v['content_extra1'];
			$aAdd[$kLang]['content_extra2'] = $v['content_extra2'];
			$aAdd[$kLang]['content_extra3'] = $v['content_extra3'];
			$id = PG_addArticleGroupContent($aAdd);
			unset($aAdd);
		}
	}
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_getCheckArticleGroupContentByID($id, $langkeys)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "	SELECT content_id 
				FROM " . _DBPREFIX_ . "site_articles_content 
				WHERE langkeys = '{$langkeys}' 
				AND articles_id = '{$id}'; ";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	return ($db->num_rows() > 0) ? true : false;
}

function PG_unlinkArticleContentFileAttc($id, $langdel)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT contentAttach FROM " . _DBPREFIX_ . "site_articles_content WHERE articles_id = '{$id}' AND langkeys = '{$langdel}';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	if ($db->f('contentAttach') != '') {
		$n = $db->f('contentAttach');
		if (file_exists(PATH_UPLOAD . '/' . $n)) {
			unlink(PATH_UPLOAD . '/' . $n);
		}

		$sql = "UPDATE " . _DBPREFIX_ . "site_articles_content SET `contentAttach` = '' WHERE articles_id = '{$id}' AND langkeys = '{$langdel}'; ";
		$db->query($sql, __FUNCTION__);
	}
}

function PG_unlinkArticleContentFileIcon($id, $langdel)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT content_icon FROM " . _DBPREFIX_ . "site_articles_content WHERE articles_id = '{$id}' AND langkeys = '{$langdel}';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	if ($db->f('content_icon') != '') {
		$n = $db->f('content_icon');
		if (file_exists(PATH_UPLOAD . '/' . $n)) {
			unlink(PATH_UPLOAD . '/' . $n);
		}

		$sql = "UPDATE " . _DBPREFIX_ . "site_articles_content SET `content_icon` = '' WHERE articles_id = '{$id}' AND langkeys = '{$langdel}'; ";
		$db->query($sql, __FUNCTION__);
	}
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function PG_unlinkArticlesFileAttc($id)
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT file_attach FROM " . _DBPREFIX_ . "site_articles WHERE articles_id = '{$id}';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	if (is_file(PATH_UPLOAD . '/' . $db->f('file_attach')) && $db->f('file_attach') != '') {
		unlink(PATH_UPLOAD . '/' . $db->f('file_attach'));
	}

	$sql = "UPDATE " . _DBPREFIX_ . "site_articles SET `file_attach` = '' WHERE articles_id = '{$id}'; ";
	$db->query($sql, __FUNCTION__);

	$time = _TIME_;
	$sql = "UPDATE " . _DBPREFIX_ . "site_articles SET `add_time` = '{$time}' WHERE articles_id = '{$id}'; ";
	$db->query($sql, __FUNCTION__);
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */

function PG_unlinkArticlesFileIcon($id, $icon = 'icon')
{
	////////////// Validate /////////////
	$id = _input_validate_int($id, true);
	/////////// End Validate ///////////

	$sql = "SELECT icon,icon2 FROM " . _DBPREFIX_ . "site_articles WHERE articles_id = '{$id}';";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
	$db->next_record();
	if (is_file(PATH_UPLOAD . '/' . $db->f($icon)) && $db->f($icon) != '') {
		unlink(PATH_UPLOAD . '/' . $db->f($icon));
	}
	$time = _TIME_;
	$sql = "UPDATE " . _DBPREFIX_ . "site_articles SET `{$icon}` = '', `add_time` = '{$time}' WHERE articles_id = '{$id}'; ";
	$db->query($sql, __FUNCTION__);
}

function pg_countArticlesByGroup($gid, $keysname)
{
	global $lang;
	////////////// Validate /////////////
	$gid = _input_validate_int($gid, true);
	/////////// End Validate ///////////

	$sql = "SELECT COUNT(articles_id) as num_article FROM " . _DBPREFIX_ . "site_articles WHERE keysname = '{$keysname}' AND group_id = '{$gid}'; ";
	$db = DB::singleton();
	$db->query($sql);
	return ($db->next_record()) ? $db->f('num_article') : 0;
}

function sortGroupHierarchy($groups, $parent_id = 0, $level = 0)
{
	$sorted = [];
	foreach ($groups as $g) {
		if ($g['group_parent_id'] == $parent_id) {
			$g['level'] = $level;
			$sorted[] = $g;
			$sorted = array_merge($sorted, sortGroupHierarchy($groups, $g['group_id'], $level + 1));
		}
	}
	return $sorted;
}
