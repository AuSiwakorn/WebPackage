<?php
global $aQData;
/* ========================= */
////////////////////////////////////////////////////
//UNION ALL
// + Cache Global Is Ok
//c.shortMessage  gc.detail
/* ========================= */
function plugin_searchAll($keysword, $num = 0, $page = 1)
{
	global $lang, $aQData;
	$aData = array();
	$aData['data'] = array();
	$aData['error'] = array();
	$aData['sql'] = '';

	if ($keysword != '') {
		$sql = "SELECT 
                'articles' as type, 
                a.articles_id as id, 
                a.keysname, 
                c.title, 
                a.icon, 
                c.shortMessage as detail FROM " . _DBPREFIX_ . "site_articles a LEFT JOIN " . _DBPREFIX_ . "site_articles_content c 
																ON c.articles_id = a.articles_id  
																WHERE c.title LIKE '%{$keysword}%'
              UNION ALL

          SELECT 
                'group' as type, 
                g.group_id as id, 
                g.keysname, 
                gc.group_name as title, 
                g.img as icon, 
                gc.detail as detail, gc.detailExtra1 as detailExtra1, gc.detailExtra2 as detailExtra2 FROM " . _DBPREFIX_ . "site_articles_group g LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content gc 
																ON gc.group_id = g.group_id 
                WHERE gc.group_name LIKE '%{$keysword}%'";

		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$aData = array();
		$aData['num_rows']  = $db->num_rows();
		$aData['maxpage']   = ($num > 0) ? ceil($aData['num_rows'] / $num) : 0;
		$aData['nextpage']  = (($page + 1) <= $aData['maxpage']) ? ($page + 1) : $aData['maxpage'];
		$aData['backpage']  = (($page - 1) > 1) ? ($page - 1) : 1;
		if ($num > 0) {
			$start = ($page == 0 || $page == 1) ? 0 : (($num * $page) - $num);
			$sql .= ($num > 0) ? ' LIMIT ' . $start . ' , ' . $num . ' ;' : ';';
		}

		$md5Q = md5($sql.$num.$page);
		if (isset($aQData[$md5Q])) {
			return $aQData[$md5Q];
		} else {
			$aData['sql']	= $sql;
			$db->query($sql, __FUNCTION__);
			while ($db->next_record()) {
				$a = $db->allRows();
				$a['detail'] = strip_tags($a['detail']);
				$a['detail'] = ($a['detail'] != '') ? mb_substr($a['detail'], 0, 150) . '...' : '';
				$aData['data'][] = $a;
			}
			$aQData[$md5Q] = $aData;
		}
	} else {
		$aData['error'][] = 'คำที่ใช้ในการค้นหาน้อยเกินไป';
	}
	return $aData;
}

/* ========================= */
////////site_articles//////////
// + Cache Global Is Ok 
/* ========================= */
function plugin_articles_get($keysname, $id)
{
	global $lang, $aQData;
	$sql = "SELECT 
				a.*, 
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
		 	FROM " . _DBPREFIX_ . "site_articles a
		 	LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
		 	AND c.langkeys = '{$lang}'
		 	WHERE a.keysname = '{$keysname}'
		  AND a.status = '0'
		 	AND a.displaytime <= '" . _TIME_ . "'
		 	AND  a.articles_id = '{$id}'";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		$a = ($db->num_rows() > 0) ? $db->allRows() : array();
		if ($a['isContent_icon'] != '') {
			$a['icon_view'] = $a['isContent_icon'];
		} else {
			$a['icon_view'] = $a['icon'];
		}
		$aQData[$md5Q] = $a;
		return $a;
	}
}

// + Cache Global Is Ok
function plugin_articles_byGid_byExtra5($gid, $extra5 = '', $num = 0, $page = 0)
{
	global $aQData, $lang;
	$sql = "SELECT 
	  	a.*, 
		  c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
    FROM " . _DBPREFIX_ . "site_articles a
    LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
    AND c.langkeys = '{$lang}'
    WHERE a.group_id = '{$gid}'
    AND a.extra5 LIKE '%{$extra5}%'
    AND a.status = '0'
    ORDER BY a.sorttime ASC, a.group_id ASC, a.add_time DESC , a.articles_id DESC";

	$md5Q = md5($sql.$num.$page);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

		$aQData[$md5Q] = $aData;
		return $aData;
	}
}

// + Cache Global Is Ok
function plugin_articles_id($id)
{
	global $lang, $aQData;
	$sql = "SELECT 
				a.*, 
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
		 	FROM " . _DBPREFIX_ . "site_articles a
		 	LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
		 	AND c.langkeys = '{$lang}'
		 	WHERE a.status = '0'
			 AND a.displaytime <= '" . _TIME_ . "'
			 AND  a.articles_id = '{$id}'";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		$a = ($db->num_rows() > 0) ? $db->allRows() : array();
		$aQData[$md5Q] = $a;
		return $aQData[$md5Q];
	}
}
function plugin_articles_bySlug($slug)
{
	global $lang, $aQData;
	$sql = "SELECT 
				a.*, 
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
		 	FROM " . _DBPREFIX_ . "site_articles a
		 	LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
		 	AND c.langkeys = '{$lang}'
		 	WHERE a.status = '0'
			 AND a.displaytime <= '" . _TIME_ . "'
			 AND c.slug = '{$slug}'";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		$a = ($db->num_rows() > 0) ? $db->allRows() : array();
		$aQData[$md5Q] = $a;
		return $aQData[$md5Q];
	}
}

////////////From Rcd next Product ////////////////////////////////////////
// + Cache Global Is Ok
function plugin_articles_NextAndBack_id($isNext = 'next', $keysname = '', $id = '')
{
	global $lang, $aQData;
	$sqlNextBack = ($isNext == 'next') ? " AND  a.articles_id > '{$id}' " : " AND  a.articles_id < '{$id}' ";
	$sql = "SELECT 
				a.*, 
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
			 FROM " . _DBPREFIX_ . "site_articles a
		 	LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
			 AND c.langkeys = '{$lang}'
		 	WHERE a.status = '0'
			 AND a.displaytime <= '" . _TIME_ . "'
			 AND a.keysname = '{$keysname}'
			 {$sqlNextBack}
			 ORDER BY a.sorttime ASC, a.group_id ASC, a.add_time DESC , a.articles_id DESC";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		$a = ($db->num_rows() > 0) ? $db->allRows() : array();
		$aQData[$md5Q] = $a;
		return $aQData[$md5Q];
	}
}
//////////////End ///////////////////////

function plugin_articles_preview($id)
{
	global $aQData;
	$sql = "
		UPDATE `" . _DBPREFIX_ . "site_articles` 
		SET preview = preview+1 
		WHERE `articles_id` = '{$id}'";
	$db = DB::singleton();
	$db->query($sql, __FUNCTION__);
}

/* ========================= */
//////////// Extra ////////////
// + Cache Global Is Ok
/* ========================= */
function plugin_articlesAll_byExtra($extraname = 'extra1', $valueextra = '', $num = 0, $page = 0)
{
	global $lang, $aQData;
	$sql = "SELECT 
    a.*, 
    c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
    FROM " . _DBPREFIX_ . "site_articles a
    LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
    AND c.langkeys = '{$lang}'
    WHERE a.{$extraname} = '{$valueextra}'
    AND a.status = '0'
    AND a.displaytime <= '" . _TIME_ . "'
    AND  a.articles_id IS NOT NULL
    ORDER BY a.group_id ASC, a.sorttime DESC , a.add_time DESC , a.articles_id DESC";


	$md5Q = md5($sql.$num.$page);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
	}

	$aQData[$md5Q] = $aData;
	return $aData;
}

// + Cache Global Is Ok
function plugin_articlesAll_byExtraExistsAndKeys($keysname, $extraname = 'extra1', $num = 0, $page = 0)
{
	global $lang, $aQData;
	if (is_array($keysname)) {
		$ex = implode("','", $keysname);
		$ksearch = " AND a.keysname IN ('" . $ex . "') ";
	} else {
		$ksearch = " AND a.keysname = '{$keysname}' ";
	}

	$sql = "SELECT
				a.*,
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
				FROM " . _DBPREFIX_ . "site_articles a
				LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
				AND c.langkeys = '{$lang}'
				WHERE a.status = '0'
				AND a.{$extraname} != ''
				{$ksearch}
				AND a.displaytime <= '" . _TIME_ . "'
				AND  a.articles_id IS NOT NULL
				ORDER BY a.group_id ASC, a.sorttime DESC , a.add_time DESC , a.articles_id DESC";

	$md5Q = md5($sql.$num.$page);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
		$aQData[$md5Q] = $aData;
		return $aData;
	}
}

// + Cache Global Is Ok
function plugin_articlesAll_byExtraAndKeys($keysname, $extraname = 'extra1', $valueextra = '', $num = 0, $page = 0)
{
	global $lang, $aQData;
	$sql = "SELECT 
    a.*, 
    c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
    FROM " . _DBPREFIX_ . "site_articles a
    LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
    AND c.langkeys = '{$lang}'
    WHERE a.keysname = '{$keysname}'
    AND a.{$extraname} = '{$valueextra}'
    AND a.status = '0'
    AND a.displaytime <= '" . _TIME_ . "'
    AND  a.articles_id IS NOT NULL
    ORDER BY a.group_id ASC, a.sorttime DESC , a.add_time DESC , a.articles_id DESC";


	$md5Q = md5($sql.$num.$page);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
		$aQData[$md5Q] = $aData;
		return $aData;
	}
}

// + Cache Global Is Ok
function plugin_articlesAll_byCheckOption($keysname, $checkOption = '0', $num = 0, $page = 0)
{
	global $lang, $aQData;
	$sql = "SELECT 
    a.*, 
    c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
    FROM " . _DBPREFIX_ . "site_articles a
    LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
    AND c.langkeys = '{$lang}'
    WHERE a.keysname = '{$keysname}'
    AND a.checkOption= '{$checkOption}'
    AND a.status = '0'
    AND a.displaytime <= '" . _TIME_ . "'
    AND  a.articles_id IS NOT NULL
    ORDER BY a.group_id ASC, a.sorttime DESC , a.add_time DESC , a.articles_id DESC";

	$md5Q = md5($sql.$num.$page);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

		$aQData[$md5Q] = $aData;
		return $aData;
	}
}

/* ========================= */
//////////////////////site_articles/////////////////
// + Cache Global Is Ok
/* ========================= */
function plugin_articles_getAll_ontime($keysname, $starttime, $endtime, $num = 0, $page = 0)
{
	global $lang, $aQData;
	$sql = "SELECT 
			 a.*, 
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
				FROM " . _DBPREFIX_ . "site_articles a
				LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
				AND c.langkeys = '{$lang}'
				WHERE a.keysname = '{$keysname}'
				AND a.status = '0'
				AND a.displaytime >= '{$starttime}'
				AND a.displaytime <= '{$endtime}'
				AND  a.articles_id IS NOT NULL
				ORDER BY a.group_id ASC, a.sorttime DESC , a.add_time DESC , a.articles_id DESC";

	$md5Q = md5($sql.$num.$page);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
		$aQData[$md5Q] = $aData;
		return $aData;
	}
}

/* ========================= */
////////////////////////////////////////////////////
// + Cache Global Is Ok
/* ========================= */
function plugin_articles_getStart($keysname)
{
	global $aQData, $lang;
	$sql = "SELECT 
				a.*, 
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
			FROM " . _DBPREFIX_ . "site_articles a
			LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
			AND c.langkeys = '{$lang}'
			WHERE a.keysname = '{$keysname}'
			AND a.displaytime <= '" . _TIME_ . "'
			AND a.status = '0'
			AND  a.articles_id IS NOT NULL
			ORDER BY a.group_id ASC, a.sorttime DESC , a.add_time DESC , a.articles_id DESC
			 LIMIT 1;";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$db->next_record();
		$aQData[$md5Q] = $db->allRows();
		return $aQData[$md5Q];
	}
}
/* ========================= */
////////////////////////////////////////////////////
// + Cache Global Is Ok
/* ========================= */
function plugin_articles_getAll($keysname, $num = 0, $page = 0)
{
	global $lang, $aQData;
	$sql = "SELECT 
				a.*, 
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
			 FROM " . _DBPREFIX_ . "site_articles a
			 LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
			 AND c.langkeys = '{$lang}'
			 WHERE a.keysname = '{$keysname}'
			 AND a.displaytime <= '" . _TIME_ . "'
			 AND a.status = '0'
			 AND  a.articles_id IS NOT NULL
			 ORDER BY a.sorttime ASC, a.group_id ASC, a.displaytime DESC , a.articles_id DESC";

	$md5Q = md5($sql.$num.$page);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
		$aQData[$md5Q] = $aData;
		return $aQData[$md5Q];
	}
}

function plugin_articles_getAllSearch($keysname, $keysword = '', $num = 0, $page = 0)
{
	global $lang, $aQData;
	$sql = "SELECT
				a.*,
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
			FROM " . _DBPREFIX_ . "site_articles a
			LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
			AND c.langkeys = '{$lang}'
			WHERE a.keysname = '{$keysname}'
			AND (c.title LIKE '%{$keysword}%' OR c.shortMessage LIKE '%{$keysword}%')
			AND a.displaytime <= '" . _TIME_ . "'
			AND a.status = '0'
			AND  a.articles_id IS NOT NULL
			ORDER BY a.sorttime ASC, a.group_id ASC, a.add_time DESC , a.articles_id DESC";

	$md5Q = md5($sql.$num.$page);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

		$aQData[$md5Q] = $aData;
		return $aQData[$md5Q];
	}
}

/* ========================= */
//UNION ALL//
// + Cache Global Is Ok
/* ========================= */
function plugin_searchArticles($keysword, $content = '', $tags = '', $author = '', $charkeys = '', $num = 10, $page = 1)
{
	global $lang, $aQData;
	$aData = array();
	$aData['data'] = array();
	$aData['error'] = array();
	$aData['sql'] = '';

	if ($keysword != '') {
		$db = DB::singleton();
		$searchname = " c.title LIKE '%{$keysword}%' OR c.shortMessage LIKE '%{$keysword}%' ";
		$searchcontent = ($content != '') ? " OR c.content LIKE '%{$content}%'" : '';
		$searchtags = ($tags != '') ? " OR a.keywords LIKE '%{$tags}%'" : '';
		$searchauthor = ($author != '') ? " OR c.author LIKE '%{$author}%'" : '';
		$searchcharkeys = ($charkeys != '') ? " OR a.charkeys = '{$charkeys}'" : '';
		$sql = "SELECT 
					a.*, 
					c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
				FROM " . _DBPREFIX_ . "site_articles a
				LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
				AND c.langkeys = '{$lang}'
				WHERE a.displaytime <= '" . _TIME_ . "'
				AND a.status = '0'
				AND  a.articles_id IS NOT NULL
				AND a.keysname != 'downloadfile'
				AND (
				{$searchcharkeys}
				{$searchname}
				{$searchtags} 
				{$searchauthor}
				)
				ORDER BY a.group_id ASC, a.sorttime DESC , a.add_time DESC , a.articles_id DESC";
		$db->query($sql, __FUNCTION__);
		$aData['num_rows'] = $db->num_rows();
		$aData['maxpage']	= ($num > 0) ? ceil($aData['num_rows'] / $num) : 0;
		$aData['nextpage']	= (($page + 1) <= $aData['maxpage']) ? ($page + 1) : $aData['maxpage'];
		$aData['backpage']	= (($page - 1) > 1) ? ($page - 1) : 1;
		if ($num > 0) {
			$start = ($page == 0 || $page == 1) ? 0 : (($num * $page) - $num);
			$sql .= ($num > 0) ? ' LIMIT ' . $start . ' , ' . $num . ' ;' : ';';
		}

		$md5Q = md5($sql.$num.$page);
		if (isset($aQData[$md5Q])) {
			return $aQData[$md5Q];
		} else {
			$db->query($sql, __FUNCTION__);
			while ($db->next_record()) {
				$row = $db->allRows();
				if ($row['shortMessage'] != '') {
					$row['searchMessage'] = $row['shortMessage'];
				} else {
					$search = array(
						'@<script[^>]*?>.*?</script>@si',  // Strip out javascript
						'@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
						'@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
						'@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
					);
					$row['searchMessage'] = preg_replace($search, '', $row['content']);
					$row['searchMessage'] = mb_substr(strip_tags($row['content']), 0, 100, 'UTF-8');
				}

				$aData['data'][] = $row;
			}
			$aData['sql'] = $sql;
			$aQData[$md5Q] = $aData;
			return $aData;
		}
	} else {
		$aData['error'][] = 'คำที่ใช้ในการค้นหาน้อยเกินไป';
	}
	return $aData;
}

/* ========================= */
////////////////////////////////////////////////////
// + Cache Global Is Ok
/* ========================= */
function plugin_searchArticles2($keysword = '', $num = 10, $page = 1)
{
	global $lang, $aQData;
	$aData = array();
	$aData['data'] = array();
	$aData['error'] = array();
	$aData['sql'] = '';
	if ($keysword != '') {
		$db = DB::singleton();
		$searchname = ($keysword != '') ? "c.title LIKE '%{$keysword}%' OR c.shortMessage LIKE '%{$keysword}%' OR gc.group_name LIKE '%{$keysword}%' OR gc.detail LIKE '%{$keysword}%'" : " c.title IS NOT NULL ";		//OR c.content LIKE '%{$keysword}%'
		$sql = "SELECT 
				g.group_id, g.group_parent_id, g.img, g.sort, g.extraOption, g.status, g.extra_group1, g.extra_group2, g.extra_group3, g.extra_group4, g.extra_group5,
				gc.group_content_id, gc.langkeys, gc.group_name, gc.detail, gc.detailExtra1, gc.detailExtra2,
				a.*, 
				c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
				
				FROM " . _DBPREFIX_ . "site_articles_group g
				LEFT JOIN " . _DBPREFIX_ . "site_articles_group_content gc ON gc.group_id = g.group_id AND gc.langkeys = '{$lang}'
				LEFT JOIN " . _DBPREFIX_ . "site_articles a ON a.group_id = gc.group_id
				LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id 
				AND c.langkeys = '{$lang}'
				
				WHERE g.group_id IS NOT NULL
				AND a.displaytime <= '" . _TIME_ . "'
				AND a.status = '0'
				AND a.articles_id IS NOT NULL
				AND ({$searchname})
				ORDER BY a.group_id DESC, a.sorttime DESC , a.add_time DESC , a.articles_id DESC
				";
		$db->query($sql, __FUNCTION__);
		$aData['num_rows'] = $db->num_rows();
		$aData['maxpage']	= ($num > 0) ? ceil($aData['num_rows'] / $num) : 0;
		$aData['nextpage']	= (($page + 1) <= $aData['maxpage']) ? ($page + 1) : $aData['maxpage'];
		$aData['backpage']	= (($page - 1) > 1) ? ($page - 1) : 1;
		if ($num > 0) {
			$start = ($page == 0 || $page == 1) ? 0 : (($num * $page) - $num);
			$sql .= ($num > 0) ? ' LIMIT ' . $start . ' , ' . $num . ' ;' : ';';
		}

		$md5Q = md5($sql.$num.$page);
		if (isset($aQData[$md5Q])) {
			return $aQData[$md5Q];
		} else {
			$db->query($sql, __FUNCTION__);
			while ($db->next_record()) {
				$row = $db->allRows();
				if ($row['shortMessage'] != '') {
					$row['searchMessage'] = $row['shortMessage'];
				} else {
					$search = array(
						'@<script[^>]*?>.*?</script>@si',  // Strip out javascript
						'@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
						'@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
						'@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
					);
					$row['searchMessage'] = preg_replace($search, '', $row['content']);
					$row['searchMessage'] = mb_substr(strip_tags($row['content']), 0, 100, 'UTF-8');
				}

				$aData['data'][] = $row;
			}
			$aData['sql'] = $sql;
			if ($aData['num_rows'] <= 0) {
				$aData['sql'] = '';
				$db = DB::singleton();
				$sql = "SELECT 
					a.*, 
					c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
				FROM " . _DBPREFIX_ . "site_articles a
				LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
				AND c.langkeys = '{$lang}'
				WHERE a.displaytime <= '" . _TIME_ . "'
				AND a.status = '0'
				AND a.articles_id IS NOT NULL
				AND a.keysname != 'downloadfile'
				AND c.title LIKE '%{$keysword}%' OR c.shortMessage LIKE '%{$keysword}%' OR c.content LIKE '%{$keysword}%'
				OR a.keywords LIKE '%{$keysword}%' OR c.author LIKE '%{$keysword}%' OR a.charkeys = '{$keysword}'
				
				ORDER BY a.sorttime DESC , a.add_time DESC , a.articles_id DESC
				";
				$db->query($sql, __FUNCTION__);
				$aData['num_rows'] = $db->num_rows();
				$db->query($sql, __FUNCTION__);
				$aData['num_rows'] = $db->num_rows();
				$aData['maxpage']	= ($num > 0) ? ceil($aData['num_rows'] / $num) : 0;
				$aData['nextpage']	= (($page + 1) <= $aData['maxpage']) ? ($page + 1) : $aData['maxpage'];
				$aData['backpage']	= (($page - 1) > 1) ? ($page - 1) : 1;
				if ($num > 0) {
					$start = ($page == 0 || $page == 1) ? 0 : (($num * $page) - $num);
					$sql .= ($num > 0) ? ' LIMIT ' . $start . ' , ' . $num . ' ;' : ';';
				}

				$db->query($sql, __FUNCTION__);
				while ($db->next_record()) {
					$row = $db->allRows();
					if ($row['shortMessage'] != '') {
						$row['searchMessage'] = $row['shortMessage'];
					} else {
						$search = array(
							'@<script[^>]*?>.*?</script>@si',  // Strip out javascript
							'@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
							'@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
							'@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments including CDATA
						);
						$row['searchMessage'] = preg_replace($search, '', $row['content']);
						$row['searchMessage'] = mb_substr(strip_tags($row['content']), 0, 100, 'UTF-8');
					}

					$aData['data'][] = $row;
				}
				$aData['sql'] = $sql;
			}

			$aQData[$md5Q] = $aData;
		}
	} else {
		$aData['error'][] = 'คำที่ใช้ในการค้นหาน้อยเกินไป';
	}
	return $aData;
}
/* ========================= */
////////////////////////////////////////////////////
// + Cache Global Is Ok
/* ========================= */
function plugin_articles_getAllByGroup($keysname, $group_id = 0, $num = 0, $page = 0)
{
	global $aQData, $lang;
	if ($group_id == 'all') {
		$sql = "SELECT 
					a.*, 
					c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
				FROM " . _DBPREFIX_ . "site_articles_content
				LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
				AND c.langkeys = '{$lang}'
				WHERE a.keysname = '{$keysname}'
				AND a.status = '0'
				AND a.displaytime <= '" . _TIME_ . "'
				AND  a.articles_id IS NOT NULL
				ORDER BY a.sorttime ASC , a.displaytime DESC, a.add_time DESC , a.articles_id DESC
			";
	} else {
		$sql = "SELECT 
					a.*, 
					c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
				FROM " . _DBPREFIX_ . "site_articles a
				LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
				AND c.langkeys = '{$lang}'
				WHERE a.keysname = '{$keysname}'
				AND a.status = '0'
				AND a.group_id = '{$group_id}'
				AND a.displaytime <= '" . _TIME_ . "'
				AND  a.articles_id IS NOT NULL
				ORDER BY a.sorttime ASC , a.displaytime DESC, a.add_time DESC , a.articles_id DESC
			";
	}

	$md5Q = md5($sql.$num.$page);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

		$aQData[$md5Q] = $aData;
		return $aData;
	}
}
/* ========================= */
////////////////////////////////////////////////////
// + Cache Global Is Ok
/* ========================= */
function plugin_articles_getAllByGroupID($group_id = 0, $num = 0, $page = 0)
{
	global $lang, $aQData;
	if ($group_id == 'all') {
		$sql = "SELECT 
					a.*, 
					c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
				FROM " . _DBPREFIX_ . "site_articles a
				LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
				AND c.langkeys = '{$lang}'
				WHERE a.status = '0'
				AND a.displaytime <= '" . _TIME_ . "'
				AND  a.articles_id IS NOT NULL
				ORDER BY a.sorttime ASC , a.displaytime DESC, a.add_time DESC , a.articles_id DESC
			";
	} else {
		$sql = "SELECT 
					a.*, 
					c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.contentAttach
				FROM " . _DBPREFIX_ . "site_articles a
				LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
				AND c.langkeys = '{$lang}'
				WHERE a.status = '0'
				AND a.group_id = '{$group_id}'
				AND a.displaytime <= '" . _TIME_ . "'
				AND  a.articles_id IS NOT NULL
				ORDER BY a.sorttime ASC , a.displaytime DESC, a.add_time DESC , a.articles_id DESC
			";
	}

	$md5Q = md5($sql.$num.$page);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

		$aQData[$md5Q] = $aData;
		return $aData;
	}
}

/* ========================= */
////////////////////////////////////////////////////
// + Cache Global Is Ok
/* ========================= */
function plugin_articles_getAllByCharKeysAndGroupID($group_id = 0, $charKeys = '0', $num = 0, $page = 0)
{
	global $lang, $aQData;
	if ($group_id == 'all') {
		$sql = "SELECT 
					a.*, 
					c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
				FROM " . _DBPREFIX_ . "site_articles a
				LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
				AND c.langkeys = '{$lang}'
				WHERE a.status = '0'
				AND a.charkeys = '{$charKeys}'
				AND a.displaytime <= '" . _TIME_ . "'
				AND  a.articles_id IS NOT NULL
				ORDER BY a.group_id ASC, a.sorttime DESC , a.add_time DESC , a.articles_id DESC
			";
	} else {
		$sql = "SELECT 
					a.*, 
					c.content_id, c.langkeys, c.title, c.shortMessage, c.author, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
				FROM " . _DBPREFIX_ . "site_articles a
				LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
				AND c.langkeys = '{$lang}'
				WHERE a.status = '0'
				AND a.charkeys = '{$charKeys}'
				AND a.group_id = '{$group_id}'
				AND a.displaytime <= '" . _TIME_ . "'
				AND  a.articles_id IS NOT NULL
				ORDER BY a.group_id ASC, a.sorttime DESC , a.add_time DESC , a.articles_id DESC
			";
	}

	$md5Q = md5($sql.$num.$page);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

		$aQData[$md5Q] = $aData;
		return $aData;
	}
}

/* ========================= */
////////////////////////////////////////////////////
/* ========================= */
function plugin_articles_getAll_ontime_bygroup($keysname = '', $group_id = 0, $starttime = 0, $endtime = 0, $num = 0, $page = 0)
{
	global $lang, $aQData;
	$db = DB::singleton();
	$sql = "SELECT
				a.*,
				c.content_id, c.langkeys, c.title, c.shortMessage, c.content, c.content2, c.content3, c.content4, c.contentAttach, c.content_icon, c.content_extra1, c.content_extra2, c.content_extra3, c.slug
			FROM " . _DBPREFIX_ . "site_articles a
			LEFT JOIN " . _DBPREFIX_ . "site_articles_content c ON c.articles_id = a.articles_id
			AND c.langkeys = '{$lang}'
			WHERE a.keysname = '{$keysname}'
			AND a.status = '0'
			AND a.group_id = '{$group_id}'
			AND a.displaytime >= '{$starttime}'
			AND a.displaytime <= '{$endtime}'
			AND  a.articles_id IS NOT NULL
			ORDER BY a.group_id ASC, a.sorttime DESC , a.add_time DESC , a.articles_id DESC
			";

	$md5Q = md5($sql.$num.$page);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);

		$aQData[$md5Q] = $aData;
		return $aData;
	}
}

function plugin_cut_URL($URL)
{
	if ($URL != '' && $URL != 'http://') {
		preg_match('@^(?:http://)?([^/]+)@i', $URL);
	} else {
		$URL = 'javascript:;';
	}
	return $URL;
}

function strTimeFormat($timestamp = '', $format = 'd-m-Y', $isToday = false)
{
	$timestamp = ($timestamp) ? $timestamp : _TIME_;
	if ($format == '') {
		$format = "d-m-Y";
	}

	if ($isToday == true) {
		$d = date('d', _TIME_);
		$m = date('m', _TIME_);
		$dcheck = date('d-m', $timestamp);
		if (($d . '-' . $m) == $dcheck) {
			return '<b>วันนี้</b> ' . date('H:i:s', $timestamp);
		}
	}
	$format = preg_replace('~%~', '', $format);
	return date($format, $timestamp);
}
