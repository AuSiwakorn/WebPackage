<?php
global $aQData;

// รายการ subpost ของแม่ (article ก่อน ถ้าไม่มีค่อย group) — join content ตามภาษาปัจจุบัน
function plugin_getAllSubpost($group_id = 0, $articles_id = 0, $num = 0, $page = 0)
{
	global $lang, $aQData;
	$where = ((int) $articles_id > 0) ? "s.articles_id = '" . (int) $articles_id . "'" : "s.group_id = '" . (int) $group_id . "'";
	$sql = "SELECT
				s.*,
				c.subpost_content_id, c.langkeys, c.subtitle, c.subMessage, c.subText
			FROM " . _DBPREFIX_ . "site_articles_subpost s
			LEFT JOIN " . _DBPREFIX_ . "site_articles_subpost_content c ON c.subpost_id = s.subpost_id
			AND c.langkeys = '{$lang}'
			WHERE {$where}
			AND s.status = '0'
			ORDER BY s.sort ASC, s.subpost_id ASC";

	$md5Q = md5($sql . $num . $page);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$aData = $db->pager(__FUNCTION__, $sql, $num, $page);
		$aQData[$md5Q] = $aData;
		return $aData;
	}
}

// subpost เดี่ยวด้วย id (ภาษาปัจจุบัน)
function plugin_getSubpostByID($id)
{
	global $lang, $aQData;
	$sql = "SELECT
				s.*,
				c.subpost_content_id, c.langkeys, c.subtitle, c.subMessage, c.subText
			FROM " . _DBPREFIX_ . "site_articles_subpost s
			LEFT JOIN " . _DBPREFIX_ . "site_articles_subpost_content c ON c.subpost_id = s.subpost_id
			AND c.langkeys = '{$lang}'
			WHERE s.subpost_id = '" . (int) $id . "'";

	$md5Q = md5($sql);
	if (isset($aQData[$md5Q])) {
		return $aQData[$md5Q];
	} else {
		$db = DB::singleton();
		$db->query($sql, __FUNCTION__);
		$aQData[$md5Q] = ($db->next_record()) ? $db->allRows() : array();
		return $aQData[$md5Q];
	}
}
